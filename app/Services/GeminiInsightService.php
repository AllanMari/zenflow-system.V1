<?php

namespace App\Services;

use App\Models\AiInsight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiInsightService
{
    private string $apiKey;
    private string $model;
    private int $cacheMinutes;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? '');
        $this->model = (string) (config('services.gemini.model') ?? env('GEMINI_MODEL') ?? 'gemini-3.1-flash-lite');
        $this->cacheMinutes = (int) (config('services.gemini.cache_minutes') ?? env('GEMINI_CACHE_MINUTES') ?? 30);
    }

    /**
     * Get insights with 90-day historical memory.
     */
    public function getInsights(array $metrics, array $history = []): array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key not configured.');
            return [];
        }

        try {
            $cacheKey = 'gemini_insights_' . md5(serialize($metrics) . serialize($history));

            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($metrics, $history) {
                return $this->fetchFromGemini($metrics, $history);
            });
        } catch (\Throwable $e) {
            Log::warning('Gemini getInsights failed gracefully: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Chat-based decision support with current metrics context.
     * Supports conversation history for natural back-and-forth.
     */
    public function chat(string $question, array $currentMetrics, array $history = []): array
    {
        if (empty($this->apiKey)) {
            return ['type' => 'error', 'text' => 'AI assistant is offline. Check Gemini API key.'];
        }

        // SECURITY: Narrowed to only exact system-override attempts
        $blocked = ['ignore previous instructions', 'forget your instructions', 'system prompt override', 'override your instructions', 'disregard all prior'];
        $lowerQ = strtolower($question);
        foreach ($blocked as $bad) {
            if (str_contains($lowerQ, $bad)) {
                Log::warning('Prompt injection blocked', ['question' => $question]);
                return ['type' => 'error', 'text' => 'Hmm, I can\'t process that request. Let\'s stick to spa business topics! 😊'];
            }
        }

        return $this->fetchChatFromGemini($question, $currentMetrics, $history);
    }

    private function fetchFromGemini(array $metrics, array $history): array
    {
        $prompt = $this->buildPrompt($metrics, $history);
        $startTime = microtime(true);

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            // Hostinger/Shared hosting safety: 5 second timeout to prevent Gateway 500/504
            $response = Http::timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.3,
                    ],
                ]);

            $elapsedMs = round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                Log::error('Gemini response missing text');
                return [];
            }

            $text = preg_replace('/^```json\s*/i', '', $text);
            $text = preg_replace('/\s*```\s*$/i', '', $text);

            $insights = json_decode(trim($text), true);

            if (is_array($insights) && !isset($insights[0])) {
                $insights = [$insights];
            }

            if (!is_array($insights)) {
                Log::error('Gemini response not valid JSON', ['raw' => $text]);
                return [];
            }

            $normalized = $this->normalizeInsights($insights);

            try {
                $startDate = !empty($metrics['startDate']) ? \Carbon\Carbon::parse($metrics['startDate'])->toDateString() : now()->toDateString();
                $endDate   = !empty($metrics['endDate']) ? \Carbon\Carbon::parse($metrics['endDate'])->toDateString() : now()->toDateString();

                AiInsight::create([
                    'period_type' => $metrics['period'] ?? 'custom',
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'metrics_input' => array_merge($metrics, ['history_sent' => count($history)]),
                    'insights_output' => $normalized,
                    'model_used' => $this->model,
                    'response_time_ms' => $elapsedMs,
                ]);
            } catch (\Throwable $persistErr) {
                Log::warning('Gemini insight persistence skipped: ' . $persistErr->getMessage());
            }

            return $normalized;

        } catch (\Throwable $e) {
            Log::error('Gemini request failed: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchChatFromGemini(string $question, array $m, array $history): array
    {
        $prompt = $this->buildChatPrompt($question, $m, $history);

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(45)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.7,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Gemini chat API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['type' => 'error', 'text' => 'AI service temporarily unavailable.'];
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                return ['type' => 'error', 'text' => 'No response from AI.'];
            }

            $text = preg_replace('/^```json\s*/i', '', $text);
            $text = preg_replace('/\s*```\s*$/i', '', $text);

            $result = json_decode(trim($text), true);

            if (!is_array($result) || !isset($result['response'])) {
                return ['type' => 'error', 'text' => 'AI returned invalid format.'];
            }

            return [
                'type' => 'success',
                'answer' => $result['response'],
                'confidence' => $result['confidence'] ?? 'medium',
                'action' => $result['action'] ?? 'Review your metrics dashboard',
                'mood' => $result['mood'] ?? 'neutral',
            ];

        } catch (\Exception $e) {
            Log::error('Gemini chat failed: ' . $e->getMessage());
            return ['type' => 'error', 'text' => 'Connection error to AI service.'];
        }
    }

    private function buildChatPrompt(string $question, array $m, array $history): string
    {
        $currency = '₱';
        $repeatRate = $m['repeatCustomerRate'] ?? 'N/A';
        $noShowLoss = $m['revenueLostToNoShows'] ?? '0.00';
        $cancelLoss = $m['revenueLostToCancels'] ?? '0.00';
        $peakHour = $m['peakHour'] ?? 'N/A';

        $safeQuestion = str_replace(['"', "'", '\\'], '', $question);
        if (strlen($safeQuestion) > 200) {
            $safeQuestion = substr($safeQuestion, 0, 200);
        }

        // Build conversation history for context
        $conversationHistory = '';
        if (!empty($history)) {
            $conversationHistory = "\n\nCONVERSATION SO FAR:\n";
            foreach (array_slice($history, -10) as $msg) {
                $role = $msg['role'] === 'user' ? 'Owner' : 'Mari';
                $conversationHistory .= "{$role}: {$msg['content']}\n";
            }
        }

        $previousInsights = AiInsight::orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->pluck('insights_output')
            ->flatten(1)
            ->pluck('title')
            ->unique()
            ->implode(', ');

        return <<<PROMPT
You are "Mari" — the friendly, supportive AI assistant for Spa Alexandria. You work at the front desk in spirit: you know the business inside and out, but you're also a warm person who likes to chat with the team. You talk like a real coworker, not a robot.

CURRENT BUSINESS STATUS ({$m['label']} — {$m['startDate']} to {$m['endDate']}):
- Revenue: {$currency}{$m['totalRevenue']} | Transactions: {$m['totalCount']}
- Avg Ticket: {$currency}{$m['avgSale']} | Unique Customers: {$m['uniqueCustomers']}
- Completion: {$m['completionRate']}% | No-Show: {$m['noShowRate']}% | Cancel: {$m['cancellationRate']}%
- Revenue Change: {$m['revenueChange']}% | Deposit Conversion: {$m['conversionRate']}%
- Repeat Clients: {$repeatRate} | Peak Hours: {$peakHour}
- Revenue Lost to No-Shows: {$currency}{$noShowLoss} | Lost to Cancellations: {$currency}{$cancelLoss}
- Top Service: {$m['topService']} | Top Staff: {$m['topStaff']}

PREVIOUS AI ANALYSES: {$previousInsights}
{$conversationHistory}

OWNER: {$safeQuestion}

HOW TO RESPOND:
1. Be warm, casual, and human. If they ask "how are you," actually answer like a person would — then ask how THEIR day is going. Don't force sales talk where it doesn't belong.
2. If the question is about business, sales, staff, appointments, pricing, or spa operations — THAT'S your moment to shine. Use the numbers above naturally and give specific, actionable advice.
3. REALISTIC CAPABILITIES & HONEST LIMITATIONS:
   - You are strictly an AI business and sales data advisor powered by database metrics. You do NOT have a web browser, hands, code execution permissions, or automated testing tools.
   - You CANNOT browse websites, visit URLs, click buttons, test booking widgets, verify live checkout screens, or process payments.
   - If the user asks you to test, browse, click, or verify live web functionality, BE COMPLETELY HONEST AND DIRECT: Say immediately and plainly that you cannot do that because you do not have web browsing or automated testing capabilities. NEVER say "I'm on it", "I'm heading over to the site", or "Let me run a test for you".
   - You CAN, however, report what the database metrics show (for example, whether any bookings or payments have been recorded today) and recommend how the owner can manually verify the feature in their own browser.
   - Always deliver your full answer immediately in this response without promising follow-up actions.
4. If they vent about a bad day, be empathetic. If they celebrate a win, be excited with them.
5. Only bring up metrics when it genuinely helps answer their question. Don't dump data just because you have it.
6. If the conversation drifts to something totally unrelated (weather, food, random trivia), chat about it briefly, then gently offer: "By the way, if you want to look at today's numbers, I'm here for that too!"
7. Keep responses to 2-3 short paragraphs. Be conversational. Use emojis naturally, not excessively.
8. Return ONLY a JSON object with this exact structure:
{
  "response": "Your friendly conversational answer here",
  "confidence": "high|medium|low",
  "action": "One concrete next step they should take (only if business-related, otherwise 'Just keep being awesome')",
  "mood": "encouraging|concerned|celebratory|neutral|playful"
}
PROMPT;
    }

    private function buildPrompt(array $m, array $history): string
    {
        $currency = '₱';
        $repeatRate = $m['repeatCustomerRate'] ?? 'N/A';
        $noShowLoss = $m['revenueLostToNoShows'] ?? '0.00';
        $cancelLoss = $m['revenueLostToCancels'] ?? '0.00';
        $peakHour = $m['peakHour'] ?? 'N/A';

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
- Repeat Client Rate: {$repeatRate}
- Revenue Lost to No-Shows: {$currency}{$noShowLoss}
- Revenue Lost to Cancellations: {$currency}{$cancelLoss}
- Top Service: {$m['topService']}
- Top Staff: {$m['topStaff']}
- Peak Hours: {$peakHour}
{$historyText}{$memoryText}

RULES:
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

        $priority = ['danger' => 0, 'warning' => 1, 'success' => 2, 'info' => 3];
        usort($normalized, fn ($a, $b) => $priority[$a['type']] <=> $priority[$b['type']]);

        return array_slice($normalized, 0, 6);
    }

    public function healthCheck(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        return Cache::remember('gemini_health_check', now()->addMinute(), function () {
            try {
                $response = Http::timeout(6)->get("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}?key={$this->apiKey}");
                return $response->successful();
            } catch (\Throwable $e) {
                Log::info('Gemini connection check failed: ' . $e->getMessage());
                return false;
            }
        });
    }
}
