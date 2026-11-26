<?php

namespace App\Support;

/**
 * Feature 44 — panel translation helper.
 * Blades wrap their Dari strings: {{ \App\Support\PanelText::t('داشبورد') }}.
 * In Pashto mode the string is looked up in lang/ps/panel_map.php (keyed by
 * the Dari original); anything missing gracefully stays in Dari.
 */
class PanelText
{
    private static ?array $map = null;

    public static function t(string $dari): string
    {
        if (app()->getLocale() !== 'ps') {
            return $dari;
        }
        if (self::$map === null) {
            $file = lang_path('ps/panel_map.php');
            self::$map = is_file($file) ? require $file : [];
        }

        return self::$map[$dari] ?? $dari;
    }
}
