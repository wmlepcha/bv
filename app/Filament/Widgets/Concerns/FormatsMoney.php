<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

trait FormatsMoney
{
    public function money(int|float $paise): string
    {
        return Number::currency($paise / 100, 'INR') ?: '₹0.00';
    }

    /**
     * Big figure with the paise dimmed, e.g. ₹5,567.00 with ".00" in grey.
     */
    public function bigMoney(int|float $paise): HtmlString
    {
        [$whole, $decimals] = explode('.', number_format($paise / 100, 2));

        return new HtmlString('₹'.e($whole).'<span class="bv-dec">.'.e($decimals).'</span>');
    }
}
