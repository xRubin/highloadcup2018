<?php
namespace App\Utility\Packers;

use EasySwoole\Component\TableManager;

class Interest
{


    /**
     * @param string $value
     * @return string
     */
    public static function register(string $value): string
    {
        $interest_table = TableManager::getInstance()->get('interest');
        $index = (string)$interest_table->count(1);
        $interest_table->set($index, ['name' => $value]);
        return $index;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function pack(string $value): string
    {
        $interest_table = TableManager::getInstance()->get('interest');
        foreach ($interest_table as $key => $row) {
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
        if (empty($value))
            return null;

        $interest_table = TableManager::getInstance()->get('interest');
        return $interest_table->get((string)$value, 'name');
    }
}