<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerManagementController extends Controller
{
    /**
     * Display the Customer Management directory with KPIs, search, and filters.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $type = (string) $request->get('type', 'all');

        $query = Customer::query()
            ->withCount([
                'appointments',
                'appointments as completed_appointments_count' => function ($q) {
                    $q->where('status', 'completed');
                },
            ])
            ->withSum([
                'appointments as lifetime_spend' => function ($q) {
                    $q->where('status', 'completed');
                },
            ], 'total_price');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['regular', 'vip'])) {
            $query->where('customer_type', $type);
        }

        $customers = $query->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        // KPIs
        $totalCustomers = Customer::count();
        $vipCustomers = Customer::where('customer_type', 'vip')->count();
        $regularCustomers = Customer::where('customer_type', 'regular')->count();
        $activeCustomers = Customer::has('appointments')->count();
        $completedVisits = Appointment::where('status', 'completed')->count();

        $routeNamePrefix = Auth::user()->isAdmin() ? 'admin.customers' : 'receptionist.customers';

        return view('shared.customers', compact(
            'customers',
            'totalCustomers',
            'vipCustomers',
            'regularCustomers',
            'activeCustomers',
            'completedVisits',
            'search',
            'type',
            'routeNamePrefix'
        ));
    }

    /**
     * Return detailed customer profile and complete appointment history for inspection modal/drawer.
     */
    public function show(Customer $customer)
    {
        $appointments = $customer->appointments()
            ->with([
                'services:id,name',
                'assignedStaff:id,first_name,last_name',
                'staff:id,first_name,last_name',
                'room:id,name',
            ])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($appt) {
                // Collect all therapists assigned equally
                $therapists = collect();
                if ($appt->relationLoaded('assignedStaff') && $appt->assignedStaff->isNotEmpty()) {
                    $therapists = $appt->assignedStaff->map(fn($s) => $s->first_name . ' ' . $s->last_name);
                } elseif ($appt->staff) {
                    $therapists = collect([$appt->staff->first_name . ' ' . $appt->staff->last_name]);
                }

                return [
                    'id'               => $appt->id,
                    'date'             => \Carbon\Carbon::parse($appt->appointment_date)->format('M d, Y'),
                    'time'             => \Carbon\Carbon::parse($appt->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($appt->end_time)->format('g:i A'),
                    'status'           => $appt->status,
                    'total_price'      => '₱' . number_format((float) $appt->total_price, 2),
                    'services'         => $appt->services->pluck('name')->toArray(),
                    'therapists'       => $therapists->values()->toArray(),
                    'room'             => $appt->room ? $appt->room->name : 'N/A',
                    'treatment_notes'  => $appt->treatment_notes ?: '',
                ];
            });

        $totalSpend = (float) $customer->appointments()
            ->where('status', 'completed')
            ->sum('total_price');

        $completedCount = $customer->appointments()
            ->where('status', 'completed')
            ->count();

        $customerData = [
            'id'                   => $customer->id,
            'first_name'           => $customer->first_name,
            'last_name'            => $customer->last_name,
            'full_name'            => $customer->first_name . ' ' . $customer->last_name,
            'nickname'             => $customer->nickname ?: '',
            'email'                => $customer->email ?: '',
            'phone_number'         => $customer->phone_number ?: '',
            'customer_type'        => $customer->customer_type ?: 'regular',
            'medical_notes'        => $customer->medical_notes ?: '',
            'total_appointments'   => $customer->appointments()->count(),
            'completed_visits'     => $completedCount,
            'lifetime_spend'       => '₱' . number_format($totalSpend, 2),
            'member_since'         => $customer->created_at ? $customer->created_at->format('M d, Y') : 'N/A',
        ];

        return response()->json([
            'success'      => true,
            'customer'     => $customerData,
            'appointments' => $appointments,
        ]);
    }

    /**
     * Update customer profile and medical notes.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'nickname'      => 'nullable|string|max:50',
            'phone_number'  => 'nullable|string|max:25',
            'customer_type' => 'nullable|in:regular,vip',
            'medical_notes' => 'nullable|string|max:1500',
        ]);

        $customer->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer profile updated successfully.',
                'customer' => $customer,
            ]);
        }

        return back()->with('success', "Customer {$customer->first_name} {$customer->last_name} updated successfully.");
    }
}
