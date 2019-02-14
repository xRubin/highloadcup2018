<?php

namespace App\Utility\Packers;

use EasySwoole\Component\TableManager;

class Country
{
    /**
     * @param string $value
     * @return string
     */
    public static function register(string $value): string
    {
        $country_table = TableManager::getInstance()->get('country');
        $index = (string)$country_table->count(1);
        $country_table->set($index, ['name' => $value]);
        return $index;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function pack(string $value): string
    {
        $country_table = TableManager::getInstance()->get('country');
        foreach ($country_table as $key => $row) {
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
        $country_table = TableManager::getInstance()->get('country');
        return $country_table->get((string)$value, 'name');
    }
}