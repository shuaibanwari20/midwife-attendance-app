<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Key-value system settings editable from the admin panel
 * (e.g. work_start / work_end). Falls back to config defaults when the
 * settings table or the key is missing, so a fresh install behaves
 * exactly like the config file says.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** @var array<string, string|null> */
    private static array $cache = [];

    private static ?bool $tableExists = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = $default;

        try {
            if (self::$tableExists === null) {
                self::$tableExists = Schema::hasTable('settings');
            }

            if (self::$tableExists) {
                $row = self::query()->find($key);
                if ($row !== null && $row->value !== null && $row->value !== '') {
                    $value = $row->value;
                }
            }
        } catch (\Throwable $e) {
            // DB not ready (install/migrate) — the config default applies.
        }

        return self::$cache[$key] = $value;
    }

    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache[$key] = $value;
    }
}
