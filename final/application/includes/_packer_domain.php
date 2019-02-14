<?php declare(strict_types=1);

use Swoole\Table;

$domain_table = new Table(31);
$domain_table->column('name', Table::TYPE_STRING, 50);
$domain_table->create();


/**
 * @param string $value
 * @return string
 */
function _registerNewDomain(string $value): string
{
    global $domain_table;
    $index = (string)$domain_table->count(1);
    $domain_table->set($index, ['name' => $value]);
    return $index;
}

/**
 * @param string $value
 * @return string
 */
function pack_email_domain(string $value): string
{
    global $domain_table;
    foreach($domain_table as $key => $row)
    {
        if ($row['name'] == $value)
            return $key;
    }
    return _registerNewDomain($value);
}

/**
 * @param string|null $value
 * @return null|string
 */
function unpack_email_domain(string $value = null): ?string
{
    global $domain_table;
    return $domain_table->get((string)$value, 'name');
}