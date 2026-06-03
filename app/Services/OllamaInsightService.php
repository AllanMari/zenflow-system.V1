<?php

namespace App\Services;

use App\Models\AiInsight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaInsightService
{
    private string $endpoint;
    private string $model;
    private int $cacheMinutes;

    public function __construct()
    {
        $this->endpoint = rtrim(config('services.ollama.endpoint', 'http://127.0.0.1:11434'), '/');
        $this->model = config('services.ollama.model', 'qwen2.5:7b');
        $this->cacheMinutes = config('services.ollama.cache_minutes', 30);
    }

    /**
     * Get insights with 90-day historical memory.
     */
    public function getInsights(array $metrics, array $history = []): array
    {
        if (empty($this->endpoint)) {
            Log::warning('Ollama endpoint not configured.');
            return [];
        }

        $cacheKey = 'ollama_insights_' . md5(serialize($metrics) . serialize($history));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($metrics, $history) {
            return $this->fetchFromOllama($metrics, $history);
        });
    }

    /**
     * Chat-based decision support with current metrics context.
     */
    public function chat(string $question, array $currentMetrics): array
    {
        if (empty($this->endpoint)) {
            return ['type' => 'error', 'text' => 'AI assistant is offline. Check Ollama connection.'];
        }

        // SECURITY: Block prompt injection attempts
        $blocked = ['ignore previous', 'forget instructions', 'system prompt', 'you are now', 'act as', 'pretend to', 'override', 'disregard'];
        $lowerQ = strtolower($question);
        foreach ($blocked as $bad) {
            if (str_contains($lowerQ, $bad)) {
                Log::warning('Prompt injection blocked', ['question' => $question]);
                return ['type' => 'error', 'text' => 'Invalid question format. Please ask about spa business topics.'];
            }
        }

        $cacheKey = 'ollama_chat_' . md5($question . serialize($currentMetrics));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($question, $currentMetrics) {
            return $this->fetchChatFromOllama($question, $currentMetrics);
        });
    }

    private function fetchFromOllama(array $metrics, array $history): array
    {
        $prompt = $this->buildPrompt($metrics, $history);
        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->endpoint}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => ['temperature' => 0.3],
                ]);

            $elapsedMs = round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::error('Ollama API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $json = $response->json();
            $text = $json['response'] ?? null;

            if (!$text) {
                Log::error('Ollama response missing text');
                return [];
            }

            $text = preg_replace('/^```json\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $insights = json_decode(trim($text), true);

            if (is_array($insights) && !isset($insights[0])) {
                $insights = [$insights];
            }

            if (!is_array($insights)) {
                Log::error('Ollama response not valid JSON', ['raw' => $text]);
                return [];
            }

            $normalized = $this->normalizeInsights($insights);

            AiInsight::create([
                'period_type' => $metrics['period'] ?? 'custom',
                'period_start' => $metrics['startDate'] ?? now(),
                'period_end' => $metrics['endDate'] ?? now(),
                'metrics_input' => array_merge($metrics, ['history_sent' => count($history)]),
                'insights_output' => $normalized,
                'model_used' => $this->model,
                'response_time_ms' => $elapsedMs,
            ]);

            return $normalized;

        } catch (\Exception $e) {
            Log::error('Ollama request failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchChatFromOllama(string $question, array $m): array
    {
        $currency = '₱';

        $safeQuestion = str_replace(['"', "'", '\\'], '', $question);
        if (strlen($safeQuestion) > 200) {
            $safeQuestion = substr($safeQuestion, 0, 200);
        }

        $previousInsights = AiInsight::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->pluck('insights_output')
            ->flatten(1)
            ->pluck('title')
            ->unique()
            ->implode(', ');

        $prompt = <<<PROMPT
You are Spa Alexandria's AI business advisor. Answer the owner's question using current sales data.

CURRENT BUSINESS STATUS:
- Period: {$m['label']} ({$m['startDate']} to {$m['endDate']})
- Revenue: {$currency}{$m['totalRevenue']} | Transactions: {$m['totalCount']}
- Avg Ticket: {$currency}{$m['avgSale']} | Unique Customers: {$m['uniqueCustomers']}
- Completion: {$m['completionRate']}% | No-Show: {$m['noShowRate']}% | Cancel: {$m['cancellationRate']}%
- Revenue Change: {$m['revenueChange']}% | Conversion: {$m['conversionRate']}%
- Top Service: {$m['topService']} | Top Staff: {$m['topStaff']}

PREVIOUS AI CONCERNS: {$previousInsights}

OWNER QUESTION: {$safeQuestion}

RULES:
1. Return ONLY a JSON object with keys: "answer" (string, max 300 chars), "confidence" (high|medium|low), "action" (string, one concrete step).
2. Be specific. Use numbers from the metrics. Mention ₱ when discussing money.
3. If the question is not business-related, politely redirect to business topics.
4. Do not hallucinate data not in the metrics.
5. If revenue is declining, suggest immediate actions. If healthy, suggest growth moves.

EXAMPLE:
{
  "answer": "Your completion rate dropped 12% this week. Peak cancellations happen at 2pm. I recommend SMS reminders 2 hours before appointments.",
  "confidence": "high",
  "action": "Enable automated SMS reminders for afternoon slots"
}
PROMPT;

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->endpoint}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => ['temperature' => 0.4],
                ]);

            if (!$response->successful()) {
                return ['type' => 'error', 'text' => 'AI service temporarily unavailable.'];
            }

            $json = $response->json();
            $text = $json['response'] ?? null;

            if (!$text) {
                return ['type' => 'error', 'text' => 'No response from AI.'];
            }

            $text = preg_replace('/^```json\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $result = json_decode(trim($text), true);

            if (!is_array($result) || !isset($result['answer'])) {
                return ['type' => 'error', 'text' => 'AI returned invalid format.'];
            }

            return [
                'type' => 'success',
                'answer' => $result['answer'],
                'confidence' => $result['confidence'] ?? 'medium',
                'action' => $result['action'] ?? 'Review metrics dashboard',
            ];

        } catch (\Exception $e) {
            Log::error('Ollama chat failed: ' . $e->getMessage());
            return ['type' => 'error', 'text' => 'Connection error. Is Ollama running?'];
        }
    }

    private function buildPrompt(array $m, array $history): string
    {
        $currency = '₱';

        $historyText = '';
        if (!empty($history)) {
            $historyText = "\n\n90-DAY HISTORICAL CONTEXT:\n";
            foreach (array_slice($history, 0, 12) as $h) {
                $historyText .= "- {$h['label']}: Rev {$currency}{$h['revenue']} | ";
                $historyText .= "Comp {$h['completionRate']}% | ";
                $historyText .= "NoShow {$h['noShowRate']}% | ";
                $historyText .= "Cancel {$h['cancellationRate']}% | ";
                $historyText .= "Avg {$currency}{$h['avgSale']} | ";
                $historyText .= "TopSvc: {$h['topService']} | ";
                $historyText .= "TopStaff: {$h['topStaff']}\n";
            }
        }

        $memoryText = '';
        $previousInsights = AiInsight::where('period_type', $m['period'] ?? 'custom')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($previousInsights->isNotEmpty()) {
            $memoryText = "\n\nPREVIOUS AI ANALYSES (your memory):\n";
            foreach ($previousInsights as $pi) {
                $memoryText .= "- {$pi->period_start->format('M d')}: " . collect($pi->insights_output)->pluck('title')->implode(', ') . "\n";
            }
        }

        return <<<PROMPT
You are a senior spa business analyst with 15 years of experience. Analyze the CURRENT metrics against historical trends and return 3-6 actionable insights.

CURRENT METRICS:
- Period: {$m['label']} ({$m['startDate']} to {$m['endDate']})
- Total Revenue: {$currency}{$m['totalRevenue']}
- Total Transactions: {$m['totalCount']}
- Average Ticket: {$currency}{$m['avgSale']}
- Unique Customers: {$m['uniqueCustomers']}
- Completion Rate: {$m['completionRate']}%
- No-Show Rate: {$m['noShowRate']}%
- Cancellation Rate: {$m['cancellationRate']}%
- Revenue Change vs Previous Period: {$m['revenueChange']}%
- Conversion Rate: {$m['conversionRate']}%
- Deposits Held: {$currency}{$m['deposits']}
- Revenue per Completed Appointment: {$currency}{$m['revPerComp']}
- Top Service: {$m['topService']}
- Top Staff: {$m['topStaff']}
{$historyText}{$memoryText}

STRICT RULES — VIOLATING ANY RULE WILL BREAK THE SYSTEM:
1. Return ONLY a JSON array. No markdown, no explanations, no code blocks, no conversational text.
2. Each object must have exactly these keys: type, icon, title, text, meta.
3. type must be one of: danger, warning, success, info.
4. icon must be a single emoji.
5. title must be 5-40 characters.
6. text must be 80-200 characters, specific and actionable. Mention ₱ when discussing money.
7. meta must be a short label like "Action Required" or "Trend Alert".
8. Prioritize: danger first, then warning, then success, then info.
9. If metrics are healthy, suggest growth strategies (package bundling, loyalty programs, peak hour pricing).
10. Compare current period to historical trends. Detect seasonality, declining staff performance, or service concentration risk.
11. If you see a repeating pattern from previous analyses, acknowledge it and suggest a concrete fix.

EXAMPLE OUTPUT:
[
  {
    "type": "warning",
    "icon": "📉",
    "title": "Completion Rate Dropping",
    "text": "Completion fell from 78% to 67% over 14 days. Peak no-shows happen at 2pm. Enable SMS reminders 2hrs before.",
    "meta": "Trend Alert"
  }
]
PROMPT;
    }

    private function normalizeInsights(array $insights): array
    {
        $validTypes = ['danger', 'warning', 'success', 'info'];
        $bgMap = [
            'danger' => 'bg-red-50 border-red-500 dark:bg-red-900/20',
            'warning' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
            'success' => 'bg-green-50 border-green-500 dark:bg-green-900/20',
            'info' => 'bg-blue-50 border-blue-500 dark:bg-blue-900/20',
        ];
        $iconBgMap = [
            'danger' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200',
            'warning' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200',
            'success' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200',
            'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-200',
        ];

        $normalized = [];

        foreach ($insights as $insight) {
            $type = $insight['type'] ?? 'info';
            if (!in_array($type, $validTypes)) {
                $type = 'info';
            }

            $normalized[] = [
                'type' => $type,
                'icon' => $insight['icon'] ?? '💡',
                'title' => $insight['title'] ?? 'Insight',
                'text' => $insight['text'] ?? 'No details available.',
                'meta' => $insight['meta'] ?? 'Analysis',
                'bg' => $bgMap[$type],
                'iconBg' => $iconBgMap[$type],
            ];
        }

        usort($normalized, fn ($a, $b) => match (true) {
            $a['type'] === 'danger' && $b['type'] !== 'danger' => -1,
            $a['type'] !== 'danger' && $b['type'] === 'danger' => 1,
            $a['type'] === 'warning' && $b['type'] === 'success' => -1,
            $a['type'] === 'warning' && $b['type'] === 'info' => -1,
            default => 0,
        });

        return array_slice($normalized, 0, 6);
    }

    public function healthCheck(): bool
    {
        try {
            // Check if the current endpoint is running locally or through the cloud tunnel
            $isLocal = str_contains($this->endpoint, '127.0.0.1') || str_contains($this->endpoint, 'localhost');
            
            // Give the cloud tunnel a wider timeout window (8s) compared to local (3s)
            $timeout = $isLocal ? 3 : 8;

            $request = Http::timeout($timeout);

            // Dynamically inject the bypass header ONLY if we are routing through ngrok
            if (!$isLocal) {
                $request->withHeaders([
                    'ngrok-skip-browser-warning' => 'true'
                ]);
            }

            $response = $request->get("{$this->endpoint}/api/tags");

            return $response->successful() && str_contains($response->body(), $this->model);
        } catch (\Exception $e) {
            // Logs the exact failure details to laravel.log so you can debug without breaking the UI
            Log::info('Ollama connection check failed: ' . $e->getMessage());
            return false;
        }
    }
}