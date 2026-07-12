<?php

namespace App\Support;

use App\Models\Setting;

class AmountFormatter
{
    private const DECIMALS_SETTING_KEY = 'amount_decimals_enabled';

    private const ROUND_UP_SETTING_KEY = 'amount_round_up';

    private static ?bool $decimalsEnabled = null;

    private static ?bool $roundUpEnabled = null;

    private function __construct()
    {
        //
    }

    public static function decimalsEnabled(): bool
    {
        return self::$decimalsEnabled ??= Setting::valueFor(self::DECIMALS_SETTING_KEY, '0') === '1';
    }

    public static function roundUpEnabled(): bool
    {
        return self::$roundUpEnabled ??= Setting::valueFor(self::ROUND_UP_SETTING_KEY, '0') === '1';
    }

    /**
     * Format an amount for display, honoring the decimals/round-up settings.
     */
    public static function format(float|int|string|null $amount): string
    {
        if (self::decimalsEnabled()) {
            return number_format((float) $amount, 2);
        }

        return number_format(self::round((float) $amount), 0);
    }

    /**
     * Format an amount as a raw numeric string (no thousands separator), suitable for
     * input `value`/`min`/`max` attributes.
     */
    public static function raw(float|int|string|null $amount): string
    {
        if (self::decimalsEnabled()) {
            return number_format((float) $amount, 2, '.', '');
        }

        return number_format(self::round((float) $amount), 0, '.', '');
    }

    /**
     * Suitable `step` attribute for a numeric amount input.
     */
    public static function step(): string
    {
        return self::decimalsEnabled() ? '0.01' : '1';
    }

    /**
     * Suitable `min` attribute for a positive numeric amount input.
     */
    public static function min(): string
    {
        return self::decimalsEnabled() ? '0.01' : '1';
    }

    /**
     * Config payload for use in front-end (JS) amount formatting.
     *
     * @return array{decimals: bool, roundUp: bool}
     */
    public static function jsConfig(): array
    {
        return [
            'decimals' => self::decimalsEnabled(),
            'roundUp' => self::roundUpEnabled(),
        ];
    }

    private static function round(float $amount): float
    {
        return self::roundUpEnabled() ? ceil($amount) : round($amount);
    }
}
