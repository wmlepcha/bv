<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class CvInputSanitizer
{
    /**
     * Recursively sanitize CV form input: trim, strip null bytes and HTML tags.
     * Files and non-strings are left unchanged.
     */
    public static function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $item) {
                $clean[$key] = self::sanitize($item);
            }

            return $clean;
        }

        if ($value instanceof UploadedFile) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = str_replace("\0", '', $value);
        $value = strip_tags($value);

        return trim($value);
    }
}
