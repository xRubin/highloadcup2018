<?php declare(strict_types=1);

use Swoole\Table;

$country_table = new Table(255);
$country_table->column('name', Table::TYPE_STRING, 50);
$country_table->create();


/**
 * @param string $value
 * @return string
 */
function _registerNewCountry(string $value): string
{
    global $country_table;
    $index = (string)$country_table->count(1);
    $country_table->set($index, ['name' => $value]);
    return $index;
}

/**
 * @param string $value
 * @return string
 */
function pack_country(string $value): string
{
    global $country_table;
    foreach($country_table as $key => $row)
    {
        if ($row['name'] == $value)
            return $key;
    }
    return _registerNewCountry($value);
}

/**
 * @param string|null $value
 * @return null|string
 */
function unpack_country(string $value = null): ?string
{
    global $country_table;
    return $country_table->get((string)$value, 'name');
}