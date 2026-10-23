<?php

namespace App\Models;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $plan_id
 * @property string|null $plan_name
 * @property string $gateway_order_id
 * @property string|null $gateway_payment_id
 * @property string|null $invoice_number
 * @property string|null $payment_mode
 * @property string|null $payment_mode_detail
 * @property int $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property PaymentChannel $channel
 * @property Carbon|null $paid_at
 * @property int|null $collected_by_user_id
 * @property int|null $center_id
 * @property string|null $notes
 * @property Carbon|null $voided_at
 * @property int|null $voided_by_user_id
 * @property string|null $void_reason
 * @property-read User $user
 * @property-read User|null $collector
 * @property-read Center|null $center
 */
class Payment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_name',
        'duration_months',
        'gateway_order_id',
        'gateway_payment_id',
        'invoice_number',
        'payment_mode',
        'payment_mode_detail',
        'amount',
        'currency',
        'status',
        'paid_at',
        'channel',
        'collected_by_user_id',
        'center_id',
        'notes',
        'voided_at',
        'voided_by_user_id',
        'void_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
            'channel' => PaymentChannel::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    /**
     * The training center that took the money. Set by hand by the sales team and blank until then.
     *
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function isOffline(): bool
    {
        return $this->channel === PaymentChannel::Offline;
    }

    public function isLegacy(): bool
    {
        return $this->channel === PaymentChannel::Legacy;
    }

    /**
     * Offline and mapped payments are entered by staff, with the method stored in payment_mode.
     */
    public function isRecordedByStaff(): bool
    {
        return $this->isOffline() || $this->isLegacy();
    }

    public function offlineMethod(): ?OfflinePaymentMethod
    {
        return $this->isRecordedByStaff() ? OfflinePaymentMethod::fromStored($this->payment_mode) : null;
    }

    /**
     * Money actually received: paid and not voided. Every total uses this.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeCollected(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
