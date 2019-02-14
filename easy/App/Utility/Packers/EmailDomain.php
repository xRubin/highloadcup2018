<?php
namespace App\Utility\Packers;

use EasySwoole\Component\TableManager;

class EmailDomain
{
    /**
     * @param string $value
     * @return string
     */
    public static function register(string $value): string
    {
        $domain_table = TableManager::getInstance()->get('email_domain');
        $index = (string)$domain_table->count(1);
        $domain_table->set($index, ['name' => $value]);
        return $index;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function pack(string $value): string
    {
        $domain_table = TableManager::getInstance()->get('email_domain');
        foreach($domain_table as $key => $row)
        {
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
        $domain_table = TableManager::getInstance()->get('email_domain');
        return $domain_table->get((string)$value, 'name');
    }
}