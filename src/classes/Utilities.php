<?php
namespace KanbanBoard;

class Utilities
{
    public static function init(){
        $config = fopen(__DIR__."/../config", "r");
        if ($config) {
            while (($envVar = fgets($config, 4096)) !== false) {
                putenv($envVar);
            }
        }
    }
    
    public static function env($name, $default = null) {
        $value = getenv($name);
        if ($default !== null) {
            if (!empty($value)) {
                return $value;
            }
            return $default;
        }
        return (empty($value) && $default === null) ? die('Environment variable ' . $name . ' not found or has no value') : $value;
    }

    public static function hasValue($array, $key) {
        return is_array($array) && array_key_exists($key, $array) && !empty($array[$key]);
    }

    public static function dump($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}