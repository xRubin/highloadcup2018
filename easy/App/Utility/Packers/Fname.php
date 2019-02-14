<?php
namespace App\Utility\Packers;

use EasySwoole\Component\TableManager;

class Fname
{

    /**
     * @param string $value
     * @return string
     */
    public static function register(string $value): string
    {
        $fname_table = TableManager::getInstance()->get('fname');
        $index = (string)$fname_table->count(1);
        $fname_table->set($index, ['name' => $value]);
        return $index;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function pack(string $value): string
    {
        $fname_table = TableManager::getInstance()->get('fname');
        foreach ($fname_table as $key => $row) {
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
        $fname_table = TableManager::getInstance()->get('fname');
        return $fname_table->get((string)$value, 'name');
    }

}