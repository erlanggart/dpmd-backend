<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModuleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_name',
        'display_name',
        'description',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function isModuleEnabled(string $moduleName): bool
    {
        $module = static::where('module_name', $moduleName)->first();
        return $module ? $module->is_enabled : false;
    }

    public static function getEnabledModules(): array
    {
        return static::where('is_enabled', true)->pluck('module_name')->toArray();
    }
}
