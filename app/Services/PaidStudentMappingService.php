<?php

namespace App\Services;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Maps older students who paid before payments were tracked here: records the historical payment and
 * gives them working access, counted from the day they are mapped. Existing accounts are mapped silently.
 * Used by the "Mark as already paid" action and by the payment columns of the candidate import.
 */
class PaidStudentMappingService
{
    public const DEFAULT_DURATION_MONTHS = 24;

    public function __construct(
        private readonly RegistrationAccessService $access,
        private readonly StudentNumberGenerator $studentNumbers,
    ) {}

    /**
     * @throws DomainException
     */
    public function map(
        User $student,
        ?Plan $plan,
        int $durationMonths,
        int $amountRupees,
        CarbonInterface $paidOn,
        OfflinePaymentMethod $method,
        ?string $reference,
        ?CarbonInterface $accessUntil,
        User $by,
        ?int $centerId = null,
    ): Payment {
        if (! $student->isStudent()) {
            throw new DomainException('Only candidates can be mapped as already paid.');
        }

        if ($student->hasActiveRegistration()) {
            throw new DomainException('This student already has active access. Use "Record offline payment" for a renewal.');
        }

        if ($amountRupees <= 0) {
            throw new DomainException('The amount must be more than zero.');
        }

        if ($durationMonths < 1 || $durationMonths > 120) {
            throw new DomainException('The plan length must be between 1 and 120 months.');
        }

        if ($paidOn->isFuture()) {
            throw new DomainException('The paid date cannot be in the future.');
        }

        if ($accessUntil !== null && ! $accessUntil->isFuture()) {
            throw new DomainException('The access end date must be in the future.');
        }

        return DB::transaction(function () use ($student, $plan, $durationMonths, $amountRupees, $paidOn, $method, $reference, $accessUntil, $by, $centerId): Payment {
            $payment = Payment::create([
                'user_id' => $student->id,
                'plan_id' => $plan?->id,
                'plan_name' => $plan?->name,
                'duration_months' => $durationMonths,
                'gateway_order_id' => 'LEGACY-'.Str::ulid(),
                'amount' => $amountRupees * 100,
                'currency' => 'INR',
                'status' => PaymentStatus::Paid,
                'channel' => PaymentChannel::Legacy,
                'payment_mode' => $method->value,
                'payment_mode_detail' => filled($reference) ? trim((string) $reference) : null,
                'notes' => 'Mapped from an earlier payment.',
                'paid_at' => $paidOn,
                'collected_by_user_id' => $by->id,
                'center_id' => $centerId,
            ]);

            $payment->update(['invoice_number' => 'BV/REG-OLD/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);

            $this->access->extendAccess($student, $durationMonths, $plan?->id);

            $student->refresh();
            $student->forceFill(['registration_fee_paid_via' => RegistrationFeePaidVia::Mapped]);

            if ($accessUntil !== null) {
                $student->registration_expires_at = $accessUntil;
            }

            if (blank($student->student_number) && filled($student->state_code) && filled($student->special_id) && filled($student->registration_year)) {
                $student->student_number = $this->studentNumbers->generateForUser($student);
            }

            $student->save();

            StudentProfile::query()->firstOrCreate(['user_id' => $student->id]);

            return $payment;
        });
    }

    /**
     * Undo the access a mapping gave, when the mapping is voided.
     */
    public function revokeAccess(User $student): void
    {
        if ($student->registration_fee_paid_via !== RegistrationFeePaidVia::Mapped) {
            return;
        }

        $student->forceFill([
            'registration_paid_at' => null,
            'registration_expires_at' => null,
            'registration_plan_id' => null,
            'registration_fee_paid_via' => null,
            'dashboard_activated_at' => null,
        ])->save();
    }
}
