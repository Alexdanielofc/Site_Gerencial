<?php

namespace App\Support;

class Money
{
    /**
     * Format an integer amount in cents to Brazilian Real display format.
     * Null returns '—'.
     */
    public static function format(?int $cents): string
    {
        if ($cents === null) {
            return '—';
        }

        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    /**
     * Calculate the percentage of $part over $total (both in cents).
     * Returns null if $total is zero or null.
     */
    public static function percentOf(?int $part, ?int $total): ?float
    {
        if (! $total) {
            return null;
        }

        return ($part ?? 0) / $total * 100;
    }

    /**
     * Format a float percentage to one decimal place using a comma.
     * Null returns '—'.
     */
    public static function formatPercent(?float $percent): string
    {
        if ($percent === null) {
            return '—';
        }

        return number_format($percent, 1, ',', '.').'%';
    }

    /**
     * Parse a user-typed monetary string to a decimal string suitable for the DB.
     *
     * Accepts:
     *  - '1.234,56' → '1234.56'
     *  - '1234,56'  → '1234.56'
     *  - '1234.56'  → '1234.56'
     *  - '1234.5'   → '1234.5'
     *  - '1.234'    → '1234'   (thousands separator, no decimal)
     *  - '1234'     → '1234'
     *  - 'R$ 1.234,56' → '1234.56'
     *
     * Returns null for invalid or empty input.
     */
    public static function parseToDecimal(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Remove 'R$' prefix and surrounding whitespace
        $value = trim(str_replace('R$', '', $value));
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // If the value has a comma, treat it as Brazilian format (comma = decimal separator)
        if (str_contains($value, ',')) {
            // Remove all dots (thousands separators), replace comma with dot
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);

            // Validate: numeric with up to 2 decimal places
            if (! preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
                return null;
            }

            return $value;
        }

        // No comma: check if there is a dot
        if (str_contains($value, '.')) {
            // Check for a single dot used as decimal separator (e.g. '1234.56', '1234.5')
            if (preg_match('/^\d+\.\d{1,2}$/', $value)) {
                return $value;
            }

            // Check for dots used as thousands separators only (groups of exactly 3 digits after each dot)
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                return str_replace('.', '', $value);
            }

            // Any other pattern with dot is invalid
            return null;
        }

        // Plain integer string
        if (preg_match('/^\d+$/', $value)) {
            return $value;
        }

        return null;
    }
}
