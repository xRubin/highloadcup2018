<?php declare(strict_types=1);

use Swoole\Table;

$fname_table = new Table(127);
$fname_table->column('name', Table::TYPE_STRING, 50);
$fname_table->create();

/**
 * @param string $value
 * @return string
 */
function _registerNewFname(string $value): string
{
    global $fname_table;
    $index = (string)$fname_table->count(1);
    $fname_table->set($index, ['name' => $value]);
    return $index;
}

/**
 * @param string $value
 * @return string
 */
function pack_fname(string $value): string
{
    global $fname_table;
    foreach ($fname_table as $key => $row) {
        if ($row['name'] == $value)
            return $key;
    }
    return _registerNewFname($value);
}

/**
 * @param string|null $value
 * @return null|string
 */
function unpack_fname(string $value = null): ?string
{
    global $fname_table;
    return $fname_table->get((string)$value, 'name');
}
