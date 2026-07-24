<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminSetting extends Model
{
    protected $fillable = ['key', 'value', 'description', 'updated_by'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("admin_setting:{$key}", 3600, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    public static function set(string $key, string $value, ?int $updatedBy = null, ?string $description = null): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => $value,
                'updated_by' => $updatedBy,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );

        Cache::forget("admin_setting:{$key}");

        activity()->causedBy($updatedBy ? User::find($updatedBy) : null)
            ->performedOn($setting)
            ->log("Admin setting '{$key}' updated to '{$value}'");

        return $setting;
    }

}
