<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ReportPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServicePopularityController extends Controller
{
    private string $timezone = 'Asia/Manila';

    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        $data['routeName'] = Auth::user()->isAdmin()
            ? 'admin.service-popularity'
            : 'receptionist.service-popularity';

        $data['reportTitle'] = 'SERVICE POPULARITY BREAKDOWN REPORT';

        return view('shared.service-popularity', $data);
    }

    public function pdf(Request $request, ReportPdfService $pdfService)
    {
        $data = $this->buildReportData($request);

        $period = $data['range'];

        $filename = 'service-popularity-report.pdf';

        $pdfData = [
            'reportTitle' => 'SERVICE POPULARITY BREAKDOWN REPORT',
            'dateRange' => $data['dateDisplay'],
            'preparedBy' => Auth::user()->full_name ?? Auth::user()->name,
            'generatedAt' => now($this->timezone)->format('F d, Y g:i A'),
            'referenceNumber' => 'SP-' . now()->format('YmdHis'),

            'summary' => $data['summary'],
            'serviceBreakdown' => $data['serviceBreakdown'],
            'packageBreakdown' => $data['packageBreakdown'],
            'categoryBreakdown' => $data['categoryBreakdown'],
        ];

        $action = $request->get('action', 'download');

        return $action === 'stream'
            ? $pdfService->streamPdf(
                'reports.service_popularity_pdf',
                $pdfData,
                $filename
            )
            : $pdfService->generatePdf(
                'reports.service_popularity_pdf',
                $pdfData,
                $filename
            );
    }

    private function buildReportData(Request $request): array
    {
        $timezone = $this->timezone;
        $range = $request->input('range', 'month');
        $now = now($timezone);

        [$startDate, $endDate] = $this->resolveDateRange(
            $range,
            $request,
            $now,
            $timezone
        );

        $categoryId = $request->filled('category_id')
            ? (int) $request->input('category_id')
            : null;

        /*
        |--------------------------------------------------------------------------
        | Get completed appointment service rows
        |--------------------------------------------------------------------------
        */

        $appointmentServiceRows = AppointmentService::query()
            ->whereHas('appointment', function ($query) use (
                $startDate,
                $endDate
            ) {
                $query->where('status', 'completed')
                    ->whereBetween('appointment_date', [
                        $startDate->toDateString(),
                        $endDate->toDateString(),
                    ]);
            })
            ->with([
                'appointment',
                'service.category',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Resolve package component service IDs
        |--------------------------------------------------------------------------
        */

        $componentServiceIds = collect();

        foreach ($appointmentServiceRows as $row) {
            $service = $row->service;

            if (!$service || !$service->is_package) {
                continue;
            }

            foreach ($this->getPackageServiceIds($service) as $serviceId) {
                $componentServiceIds->push($serviceId);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Load the service catalog
        |--------------------------------------------------------------------------
        |
        | Active individual services are needed so the report can show
        | services even when they have no completed appointment records.
        |
        | Package component services are also loaded so package contents
        | can still be resolved correctly.
        |
        */

        $reportServices = Service::query()
            ->with('category')
            ->where(function ($query) use ($componentServiceIds) {
                $query->where(function ($query) {
                    $query->where('is_package', false)
                        ->where('is_active', true);
                });

                if ($componentServiceIds->isNotEmpty()) {
                    $query->orWhereIn(
                        'id',
                        $componentServiceIds
                            ->unique()
                            ->values()
                    );
                }
            })
            ->get()
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Report collections
        |--------------------------------------------------------------------------
        */

        $individualServices = collect();
        $packages = collect();
        $categories = collect();

        /*
        |--------------------------------------------------------------------------
        | Initialize every active individual service
        |--------------------------------------------------------------------------
        |
        | This is the important part.
        |
        | The report no longer depends on an appointment record existing
        | before a service can appear in Individual Service Popularity.
        |
        */

        $reportServices
            ->filter(function ($service) use ($categoryId) {
                if (!$service->is_active) {
                    return false;
                }

                if ($service->is_package) {
                    return false;
                }

                if (
                    $categoryId !== null &&
                    (int) $service->category_id !== $categoryId
                ) {
                    return false;
                }

                return true;
            })
            ->each(function ($service) use (&$individualServices) {
                $individualServices->put($service->id, [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'category' => $service->category?->name
                        ?? 'Uncategorized',
                    'is_package' => false,
                    'completed_count' => 0,
                    'revenue' => 0,
                    'share' => 0,
                ]);
            });

        /*
        |--------------------------------------------------------------------------
        | Track appointments represented by the report
        |--------------------------------------------------------------------------
        */

        $reportAppointmentIds = collect();

        /*
        |--------------------------------------------------------------------------
        | Process appointment services
        |--------------------------------------------------------------------------
        */

        foreach ($appointmentServiceRows as $row) {
            $service = $row->service;

            if (!$service) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Regular individual service
            |--------------------------------------------------------------------------
            */

            if (!$service->is_package) {
                if (
                    $categoryId !== null &&
                    (int) $service->category_id !== $categoryId
                ) {
                    continue;
                }

                $reportAppointmentIds->push(
                    $row->appointment_id
                );

                $price = (float) $row->price_at_booking;

                $this->addIndividualService(
                    $individualServices,
                    $service,
                    1,
                    $price
                );

                $this->addCategory(
                    $categories,
                    $service,
                    1,
                    $price
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Package
            |--------------------------------------------------------------------------
            */

            $includedIds = $this->getPackageServiceIds($service);

            $includedServices = collect($includedIds)
                ->map(
                    fn ($id) => $reportServices->get((int) $id)
                )
                ->filter();

            /*
            |--------------------------------------------------------------------------
            | Category filter
            |--------------------------------------------------------------------------
            |
            | The category filter applies to the individual services
            | contained in the package, rather than only the package's
            | own category.
            |
            */

            if ($categoryId !== null) {
                $matchingComponents = $includedServices->filter(
                    fn ($component) =>
                        (int) $component->category_id === $categoryId
                );
            } else {
                $matchingComponents = $includedServices;
            }

            if ($matchingComponents->isEmpty()) {
                continue;
            }

            $reportAppointmentIds->push(
                $row->appointment_id
            );

            /*
            |--------------------------------------------------------------------------
            | Package breakdown
            |--------------------------------------------------------------------------
            */

            $packageKey = $service->id;

            if (!$packages->has($packageKey)) {
                $packages->put($packageKey, [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'category' => $service->category?->name
                        ?? 'Uncategorized',
                    'completed_count' => 0,
                    'revenue' => 0,
                    'included_services' => [],
                ]);
            }

            $package = $packages->get($packageKey);

            $package['completed_count']++;

            $package['revenue'] +=
                (float) $row->price_at_booking;

            foreach ($matchingComponents as $component) {
                $package['included_services'][$component->id] = [
                    'id' => $component->id,
                    'name' => $component->name,
                ];
            }

            $packages->put(
                $packageKey,
                $package
            );

            /*
            |--------------------------------------------------------------------------
            | Individual services inside package
            |--------------------------------------------------------------------------
            |
            | Each included service receives one popularity count.
            |
            | Package revenue is NOT assigned here.
            |
            */

            foreach ($matchingComponents as $component) {
                $this->addIndividualService(
                    $individualServices,
                    $component,
                    1,
                    0
                );

                $this->addCategory(
                    $categories,
                    $component,
                    1,
                    0
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Individual service breakdown
        |--------------------------------------------------------------------------
        */

        $totalServices = $individualServices
            ->sum('completed_count');

        $individualServiceRevenue = $individualServices
            ->sum('revenue');

        $serviceBreakdown = $individualServices
            ->sort(function ($a, $b) {
                if (
                    $a['completed_count'] ===
                    $b['completed_count']
                ) {
                    return strcasecmp(
                        $a['name'],
                        $b['name']
                    );
                }

                return $b['completed_count']
                    <=> $a['completed_count'];
            })
            ->values()
            ->map(function ($service) use (
                $totalServices
            ) {
                $service['share'] = $totalServices > 0
                    ? round(
                        (
                            $service['completed_count']
                            / $totalServices
                        ) * 100,
                        2
                    )
                    : 0;

                return $service;
            })
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Package breakdown
        |--------------------------------------------------------------------------
        */

        $packageBreakdown = $packages
            ->sort(function ($a, $b) {
                if (
                    $a['completed_count'] ===
                    $b['completed_count']
                ) {
                    return strcasecmp(
                        $a['name'],
                        $b['name']
                    );
                }

                return $b['completed_count']
                    <=> $a['completed_count'];
            })
            ->values()
            ->map(function ($package) {
                $package['included_services'] = collect(
                    $package['included_services']
                )
                    ->values()
                    ->all();

                return $package;
            })
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Category breakdown
        |--------------------------------------------------------------------------
        */

        $categoryBreakdown = $categories
            ->sort(function ($a, $b) {
                if (
                    $a['completed_count'] ===
                    $b['completed_count']
                ) {
                    return strcasecmp(
                        $a['name'],
                        $b['name']
                    );
                }

                return $b['completed_count']
                    <=> $a['completed_count'];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'completed_appointments' => $reportAppointmentIds
                ->unique()
                ->count(),

            'services_completed' => $totalServices,

            'service_revenue' => $individualServiceRevenue,

            'services_represented' => count(
                $serviceBreakdown
            ),

            'packages_booked' => collect(
                $packageBreakdown
            )->sum('completed_count'),

            'package_revenue' => collect(
                $packageBreakdown
            )->sum('revenue'),

            'packages_represented' => count(
                $packageBreakdown
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Category options
        |--------------------------------------------------------------------------
        */

        $categoryOptions = ServiceCategory::query()
            ->orderBy('name')
            ->get();

        $selectedCategoryName = '';

        if ($categoryId !== null) {
            $selectedCategoryName = $categoryOptions
                ->firstWhere('id', $categoryId)
                ?->name ?? '';
        }

        /*
        |--------------------------------------------------------------------------
        | Date display
        |--------------------------------------------------------------------------
        */

        $dateDisplay = match ($range) {
            'today' => $startDate->format('F j, Y'),

            'week' => $startDate->format('M j') .
                ' — ' .
                $endDate->format('M j, Y'),

            'month' => $startDate->format('F Y'),

            'year' => $startDate->format('Y'),

            'custom' => $startDate->format('M j') .
                ' — ' .
                $endDate->format('M j, Y'),

            default => $startDate->format('M j') .
                ' — ' .
                $endDate->format('M j, Y'),
        };

        return [
            'range' => $range,

            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateDisplay' => $dateDisplay,

            'categoryId' => $categoryId,
            'selectedCategoryName' => $selectedCategoryName,
            'categories' => $categoryOptions,

            'summary' => $summary,

            'serviceBreakdown' => $serviceBreakdown,
            'packageBreakdown' => $packageBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
        ];
    }

    private function resolveDateRange(
        string $range,
        Request $request,
        Carbon $now,
        string $timezone
    ): array {
        switch ($range) {
            case 'today':
                return [
                    $now->copy()->startOfDay(),
                    $now->copy()->endOfDay(),
                ];

            case 'week':
                return [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                ];

            case 'month':
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ];

            case 'year':
                return [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                ];

            case 'custom':
                $startDate = $request->filled('start_date')
                    ? Carbon::parse(
                        $request->input('start_date'),
                        $timezone
                    )->startOfDay()
                    : $now->copy()->startOfDay();

                $endDate = $request->filled('end_date')
                    ? Carbon::parse(
                        $request->input('end_date'),
                        $timezone
                    )->endOfDay()
                    : $startDate->copy()->endOfDay();

                if ($endDate->lt($startDate)) {
                    [$startDate, $endDate] = [
                        $endDate,
                        $startDate,
                    ];
                }

                return [
                    $startDate,
                    $endDate,
                ];

            default:
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ];
        }
    }

    private function getPackageServiceIds(
        Service $package
    ): array {
        if (!$package->is_package) {
            return [];
        }

        $included = $package->included_services;

        if (!is_array($included)) {
            return [];
        }

        return collect($included)
            ->map(function ($item) {
                if (is_numeric($item)) {
                    return (int) $item;
                }

                if (is_array($item)) {
                    return $item['service_id']
                        ?? $item['id']
                        ?? null;
                }

                if (is_object($item)) {
                    return $item->service_id
                        ?? $item->id
                        ?? null;
                }

                return null;
            })
            ->filter(
                fn ($id) => is_numeric($id)
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->all();
    }

    private function addIndividualService(
        &$collection,
        Service $service,
        int $count,
        float $revenue = 0
    ): void {
        $key = $service->id;

        if (!$collection->has($key)) {
            $collection->put($key, [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'category' => $service->category?->name
                    ?? 'Uncategorized',
                'is_package' => false,
                'completed_count' => 0,
                'revenue' => 0,
                'share' => 0,
            ]);
        }

        $row = $collection->get($key);

        $row['completed_count'] += $count;
        $row['revenue'] += $revenue;

        $collection->put(
            $key,
            $row
        );
    }

    private function addCategory(
        &$collection,
        Service $service,
        int $count,
        float $revenue
    ): void {
        $key = $service->category_id ?? 0;

        if (!$collection->has($key)) {
            $collection->put($key, [
                'id' => $service->category_id,
                'name' => $service->category?->name
                    ?? 'Uncategorized',
                'completed_count' => 0,
                'revenue' => 0,
            ]);
        }

        $row = $collection->get($key);

        $row['completed_count'] += $count;
        $row['revenue'] += $revenue;

        $collection->put(
            $key,
            $row
        );
    }
}