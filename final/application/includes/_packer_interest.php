<?php declare(strict_types=1);

use Swoole\Table;

$interest_table = new Table(127);
$interest_table->column('name', Table::TYPE_STRING, 50);
$interest_table->create();

/**
 * @param string $value
 * @return string
 */
function _registerNewInterest(string $value): string
{
    global $interest_table;
    $index = (string)$interest_table->count(1);
    $interest_table->set($index, ['name' => $value]);
    return $index;
}

/**
 * @param string $value
 * @return string
 */
function pack_interest(string $value): string
{
    global $interest_table;
    foreach ($interest_table as $key => $row) {
        if ($row['name'] == $value)
            return $key;
    }
    return _registerNewInterest($value);
}

/**
 * @param string|null $value
 * @return null|string
 */
function unpack_interest(string $value = null): ?string
{
    if (empty($value))
        return null;

    global $interest_table;
    return $interest_table->get((string)$value, 'name');
}


function pack_interests(array $data): string
{
    return implode('|', array_map('pack_interest', $data));
}

function unpack_interests($value = null): ?array
{
    if (empty($value))
        return null;

    return array_map('unpack_interest', explode('|', $value));
}
