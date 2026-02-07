<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Automatically casts the value when you access $setting->value
     */
    public function getValueAttribute($value)
{
    return match($this->type) {
        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'integer' => (int) $value,
        default => $value,
    };
}
    /**
     * Static helper to retrieve a value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Static helper to create or update a setting
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): void
    {
        $formattedValue = ($type === 'json') ? json_encode($value) : $value;

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $formattedValue,
                'type' => $type,
                'group' => $group // Added to ensure group persists
            ]
        );
    }


}
