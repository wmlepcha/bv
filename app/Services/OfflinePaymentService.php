<?php

namespace App\Services;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cash, UPI, bank-transfer and cheque money taken outside the payment gateway. This is a ledger only:
 * it never changes the student's access (activation keys still do that).
 */
class OfflinePaymentService
{
    /**
     * @throws DomainException
     */
    public function record(
        User $student,
        ?Plan $plan,
        int $amountRupees,
        OfflinePaymentMethod $method,
        CarbonInterface $receivedOn,
        ?string $reference,
        ?string $notes,
        User $collector,
        ?int $centerId = null,
    ): Payment {
        if (! $student->isStudent()) {
            throw new DomainException('Offline payments can only be recorded for candidates.');
        }

        if (! array_key_exists($method->value, OfflinePaymentMethod::offlineOptions())) {
            throw new DomainException('Choose cash, UPI, bank transfer or cheque.');
        }

        if ($amountRupees <= 0) {
            throw new DomainException('The amount must be more than zero.');
        }

        if ($receivedOn->isFuture()) {
            throw new DomainException('The received date cannot be in the future.');
        }

        return DB::transaction(function () use ($student, $plan, $amountRupees, $method, $receivedOn, $reference, $notes, $collector, $centerId): Payment {
            $payment = Payment::create([
                'user_id' => $student->id,
                'plan_id' => $plan?->id,
                'plan_name' => $plan?->name,
                'duration_months' => $plan?->duration_months,
                'gateway_order_id' => 'OFFLINE-'.Str::ulid(),
                'amount' => $amountRupees * 100,
                'currency' => 'INR',
                'status' => PaymentStatus::Paid,
                'channel' => PaymentChannel::Offline,
                'payment_mode' => $method->value,
                'payment_mode_detail' => filled($reference) ? trim((string) $reference) : null,
                'notes' => filled($notes) ? trim((string) $notes) : null,
                'paid_at' => $receivedOn,
                'collected_by_user_id' => $collector->id,
                'center_id' => $centerId,
            ]);

            $payment->update(['invoice_number' => 'BV/REG-OFF/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);

            return $payment;
        });
    }

    /**
     * Cancels a wrongly recorded offline or mapped payment but keeps the row for the audit trail.
     * Voiding a mapped payment also takes back the access the mapping gave.
     *
     * @throws DomainException
     */
    public function void(Payment $payment, string $reason, User $admin): void
    {
        if (! $payment->isRecordedByStaff()) {
            throw new DomainException('Online payments are managed by the payment gateway and cannot be voided here.');
        }

        if ($payment->status === PaymentStatus::Voided) {
            throw new DomainException('This payment is already voided.');
        }

        if (blank($reason)) {
            throw new DomainException('Give a reason for voiding this payment.');
        }

        DB::transaction(function () use ($payment, $reason, $admin): void {
            $payment->update([
                'status' => PaymentStatus::Voided,
                'voided_at' => now(),
                'voided_by_user_id' => $admin->id,
                'void_reason' => trim($reason),
            ]);

            if ($payment->isLegacy()) {
                app(PaidStudentMappingService::class)->revokeAccess($payment->user);
            }
        });
    }
}
