<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];
    
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        // Try to decode JSON values
        $value = $setting->value;
        $decoded = json_decode($value, true);
        
        // Return decoded value if it was valid JSON, otherwise return original value
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
    }
    
    public static function set($key, $value)
    {
        // Encode arrays and objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
