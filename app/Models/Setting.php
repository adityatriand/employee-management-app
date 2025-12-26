<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'workspace_id' => 'integer',
    ];

    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @param int|null $workspaceId
     * @return mixed
     */
    public static function get($key, $default = null, $workspaceId = null)
    {
        $setting = static::where('key', $key)
            ->where(function($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId)
                      ->orWhereNull('workspace_id');
            })
            ->orderByDesc('workspace_id') // Workspace-specific overrides global
            ->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $workspaceId
     * @return Setting
     */
    public static function set($key, $value, $workspaceId = null, $type = 'string')
    {
        $setting = static::where('key', $key)
            ->where('workspace_id', $workspaceId)
            ->first();

        if ($setting) {
            $setting->update([
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
            ]);
        } else {
            $setting = static::create([
                'workspace_id' => $workspaceId,
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
            ]);
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("setting_{$key}_{$workspaceId}");

        return $setting;
    }

    /**
     * Cast value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get password requirements as array
     *
     * @param int|null $workspaceId
     * @return array
     */
    public static function getPasswordRequirements($workspaceId = null)
    {
        return [
            'min_length' => (int) static::get('password_min_length', 8, $workspaceId),
            'require_uppercase' => (bool) static::get('password_require_uppercase', true, $workspaceId),
            'require_lowercase' => (bool) static::get('password_require_lowercase', true, $workspaceId),
            'require_numbers' => (bool) static::get('password_require_numbers', true, $workspaceId),
            'require_symbols' => (bool) static::get('password_require_symbols', true, $workspaceId),
        ];
    }
}

