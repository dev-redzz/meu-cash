<?php

use Illuminate\Support\Carbon;

if (! function_exists('money')) {
    function money(float|int|string|null $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}

if (! function_exists('date_br')) {
    function date_br(mixed $date, bool $withTime = false): string
    {
        if (blank($date)) {
            return '—';
        }

        $date = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);

        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    }
}

if (! function_exists('to_decimal')) {
    function to_decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $value = preg_replace('/[^\d,.\-]/', '', (string) $value);

        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }
}

if (! function_exists('only_digits')) {
    function only_digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}
