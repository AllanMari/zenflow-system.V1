<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class TherapistCommissionService
{
    public const COMMISSION_RATE = 0.30;

    /**
     * Resolve all assigned therapists for an appointment (primary + assisting).
     */
    public function assignedTherapists(Appointment $appt): Collection
    {
        $therapists = collect();

        if ($appt->relationLoaded('assignedStaff') && $appt->assignedStaff->isNotEmpty()) {
            $therapists = $appt->assignedStaff->values();
        } elseif ($appt->relationLoaded('staff') && $appt->staff) {
            $therapists = collect([$appt->staff]);
        } elseif ($appt->staff) {
            $therapists = collect([$appt->staff]);
        } else {
            $loadedAssigned = $appt->assignedStaff;
            if ($loadedAssigned && $loadedAssigned->isNotEmpty()) {
                $therapists = $loadedAssigned->values();
            } elseif ($appt->staff) {
                $therapists = collect([$appt->staff]);
            }
        }

        if ($appt->staff && !$therapists->contains('id', $appt->staff->id)) {
            $therapists = collect([$appt->staff])->merge($therapists);
        }

        return $therapists->unique('id')->sortBy('id')->values();
    }

    /**
     * Compute therapist commission from the final discounted appointment service amount.
     *
     * @return array{
     *   commissionable_cents: int,
     *   commissionable_amount: float,
     *   total_pool_cents: int,
     *   total_pool: float,
     *   shares: array<int, int>
     * }
     */
    public function calculateForAppointment(Appointment $appt, ?Collection $therapists = null): array
    {
        if ($appt->status !== 'completed') {
            return [
                'commissionable_cents' => 0,
                'commissionable_amount' => 0.0,
                'total_pool_cents' => 0,
                'total_pool' => 0.0,
                'shares' => [],
            ];
        }

        $therapists = ($therapists ?? $this->assignedTherapists($appt))->unique('id')->sortBy('id')->values();
        $count = $therapists->count();

        if ($count === 0) {
            return [
                'commissionable_cents' => 0,
                'commissionable_amount' => 0.0,
                'total_pool_cents' => 0,
                'total_pool' => 0.0,
                'shares' => [],
            ];
        }

        $commissionableAmount = max(0.0, (float) ($appt->total_price ?? 0));
        $commissionableCents = (int) round($commissionableAmount * 100);
        $totalPoolCents = (int) round($commissionableAmount * self::COMMISSION_RATE * 100);

        $baseComCents = intdiv($totalPoolCents, $count);
        $remComCents = $totalPoolCents % $count;

        $shares = [];
        foreach ($therapists as $idx => $therapist) {
            $shares[(int) $therapist->id] = $baseComCents + ($idx < $remComCents ? 1 : 0);
        }

        return [
            'commissionable_cents' => $commissionableCents,
            'commissionable_amount' => $commissionableAmount,
            'total_pool_cents' => $totalPoolCents,
            'total_pool' => $totalPoolCents / 100,
            'shares' => $shares,
        ];
    }
}
