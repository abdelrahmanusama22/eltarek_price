<?php

namespace App\Services;

class NumericValueNormalizer
{
    /**
     * Normalize numeric strings from Excel by stripping commas, spaces, and currency symbols.
     * Preserves decimal values. Leaves invalid non-numeric values intact for validation to catch.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function normalize(mixed $value): mixed
    {
        if (is_int($value) || is_float($value) || blank($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // 1. Trim whitespace
        $value = trim($value);

        // 2. Remove thousands separators
        $cleaned = str_replace(',', '', $value);

        // 3. Remove common currency texts or extra spaces (e.g. EGP, جنيه)
        $cleaned = preg_replace('/(EGP|جنيه|\s+)/ui', '', $cleaned);

        // 4. Return as float/int if strictly numeric, else return original
        if (is_numeric($cleaned)) {
            return str_contains($cleaned, '.') ? (float) $cleaned : (int) $cleaned;
        }

        // Return original value so that validation rule 'numeric' or 'integer' will fail correctly
        return $value;
    }
}
