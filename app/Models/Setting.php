<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];
    
    public $timestamps = true;

    /**
     * Get setting value
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if receptionist can manage schedules
     */
    public static function receptionistCanManageSchedules()
    {
        return static::get('receptionist_can_manage_schedules', 'false') === 'true';
    }
}