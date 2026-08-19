<?php

namespace App\Support;

class PaymentSlipText
{
    public static function monthsInWords(int $months): string
    {
        return match ($months) {
            12 => 'Twelve months',
            24 => 'Twenty-Four months',
            default => ucfirst((string) $months).' months',
        };
    }

    public static function rupeesInWords(int $amountRupees): string
    {
        if ($amountRupees <= 0) {
            return 'Zero Only';
        }

        return self::numberToIndianWords($amountRupees).' Only';
    }

    private static function numberToIndianWords(int $number): string
    {
        $words = [];

        $crore = intdiv($number, 10000000);
        $number %= 10000000;

        $lakh = intdiv($number, 100000);
        $number %= 100000;

        $thousand = intdiv($number, 1000);
        $number %= 1000;

        if ($crore > 0) {
            $words[] = self::twoDigitsOrLessToWords($crore).' Crore';
        }

        if ($lakh > 0) {
            $words[] = self::twoDigitsOrLessToWords($lakh).' Lakh';
        }

        if ($thousand > 0) {
            $words[] = self::twoDigitsOrLessToWords($thousand).' Thousand';
        }

        if ($number > 0) {
            if ($number >= 100) {
                $hundreds = intdiv($number, 100);
                $remainder = $number % 100;

                $words[] = self::twoDigitsOrLessToWords($hundreds).' Hundred';

                if ($remainder > 0) {
                    $words[] = self::twoDigitsOrLessToWords($remainder);
                }
            } else {
                $words[] = self::twoDigitsOrLessToWords($number);
            }
        }

        return implode('-', array_values(array_filter($words)));
    }

    /**
     * Converts 0..99 (and 0..999 segments like lakh/thousand units) into words.
     * Example: 23 => Twenty-Three, 6 => Six, 105 => used via separate Hundred logic.
     */
    private static function twoDigitsOrLessToWords(int $number): string
    {
        $ones = [
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
        ];

        $tens = [
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $t = intdiv($number, 10) * 10;
            $u = $number % 10;

            $uWords = $u > 0 ? '-'.$ones[$u] : '';

            return $tens[$t].$uWords;
        }

        // For values like lakh/thousand segments, keep a fallback.
        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $remainder = $number % 100;

            if ($remainder === 0) {
                return $ones[$hundreds].' Hundred';
            }

            return $ones[$hundreds].' Hundred-'.self::twoDigitsOrLessToWords($remainder);
        }

        // Should not happen with our segmenting logic.
        return (string) $number;
    }
}

