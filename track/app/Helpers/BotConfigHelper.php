<?php

namespace App\Helpers;

use App\Models\BotConfiguration;

/**
 * Helper para obter configurações de Bot com fallback para valores padrão.
 * Escopo global (department_id null) - mesmas regras para todas as organizações.
 */
class BotConfigHelper
{
    private const DEFAULTS = [
        'max_checkout_hours_device' => 8.0,
        'lost_device_timeout_days' => 3.0,
        'delay_retry_interval_minutes' => 10,
        'reminder_percentages' => [90, 99],
    ];

    private static function getValue(string $key): ?string
    {
        return BotConfiguration::where('key', $key)->whereNull('department_id')->value('value');
    }

    public static function getMaxCheckoutHours(): float
    {
        $value = self::getValue('max_checkout_hours_device');
        $hours = $value !== null ? (float) $value : self::DEFAULTS['max_checkout_hours_device'];
        return max(1, min(168, $hours)); // 1h a 1 semana
    }

    public static function getLostDeviceDays(): float
    {
        $value = self::getValue('lost_device_timeout_days');
        $days = $value !== null ? (float) $value : self::DEFAULTS['lost_device_timeout_days'];
        return max(1, min(90, $days)); // 1 a 90 dias
    }

    public static function getDelayRetryIntervalMinutes(): int
    {
        $value = self::getValue('delay_retry_interval_minutes');
        $minutes = $value !== null ? (int) $value : self::DEFAULTS['delay_retry_interval_minutes'];
        return max(5, min(60, $minutes)); // 5 a 60 min
    }

    /**
     * @return int[] Percentuais do prazo para enviar lembrete (ex: [90, 99])
     */
    public static function getReminderPercentages(): array
    {
        $value = self::getValue('reminder_percentages');
        if ($value === null) {
            return self::DEFAULTS['reminder_percentages'];
        }
        $parts = array_map('intval', array_map('trim', explode(',', $value)));
        $parts = array_filter($parts, fn ($p) => $p >= 50 && $p <= 99);
        return !empty($parts) ? array_values(array_unique($parts)) : self::DEFAULTS['reminder_percentages'];
    }
}
