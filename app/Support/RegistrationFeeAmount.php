<?php

namespace App\Support;

use App\Models\SiteSetting;

class RegistrationFeeAmount
{
    public const SITE_SETTING_KEY = 'payments.registration_fee_rupees';

    public static function amountInRupees(): int
    {
        $fromSetting = SiteSetting::getValue(self::SITE_SETTING_KEY);

        if (filled($fromSetting) && is_numeric($fromSetting)) {
            $rupees = (int) $fromSetting;

            if ($rupees >= 1) {
                return $rupees;
            }
        }

        $fromConfigPaise = (int) config('cashfree.registration_fee_amount');

        if ($fromConfigPaise >= 100) {
            return intdiv($fromConfigPaise, 100);
        }

        return 1;
    }

    public static function amountInPaise(): int
    {
        return self::amountInRupees() * 100;
    }
}
