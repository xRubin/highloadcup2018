<?php

namespace App\Utility\Packers;

use EasySwoole\Component\TableManager;

class City
{
    /**
     * @param string $value
     * @return string
     */
    public static function register(string $value): string
    {
        $city_table = TableManager::getInstance()->get('city');
        $index = (string)$city_table->count(1);
        $city_table->set($index, ['name' => $value]);
        return $index;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function pack(string $value): string
    {
        $city_table = TableManager::getInstance()->get('city');
        foreach ($city_table as $key => $row) {
            if ($row['name'] == $value)
                return $key;
        }
        return self::register($value);
    }

    /**
     * @param string|null $value
     * @return null|string
     */
    public static function unpack(string $value = null): ?string
    {
        $city_table = TableManager::getInstance()->get('city');
        return $city_table->get((string)$value, 'name');
    }
}