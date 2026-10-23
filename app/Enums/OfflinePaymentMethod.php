<?php

namespace App\Enums;

enum OfflinePaymentMethod: string
{
    case Cash = 'cash';
    case Upi = 'upi';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    /** Only used when mapping students who paid earlier on the website. */
    case Online = 'online';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI / QR',
            self::BankTransfer => 'Bank transfer',
            self::Cheque => 'Cheque',
            self::Online => 'Online (earlier website payment)',
            self::Unknown => 'Not recorded',
        };
    }

    /**
     * Every method, for mapping students who paid earlier.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * The methods staff can record for money received now.
     *
     * @return array<string, string>
     */
    public static function offlineOptions(): array
    {
        return array_diff_key(self::options(), [self::Online->value => true, self::Unknown->value => true]);
    }

    /**
     * Offline payments store the method in `payment_mode`; this reads it back.
     */
    public static function fromStored(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }
}
