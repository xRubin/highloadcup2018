<?php declare(strict_types=1);


/**
 * @param Redis $redis
 * @param int $id
 * @param string $value
 */
function _registerEmailName($redis, int $id, string $value)
{
    $redis->zAdd('pk:en', $id, $value);
}

/**
 * @param Redis $redis
 * @param string $value
 */
function _removeEmailName($redis, string $value)
{
    $redis->zRem('pk:en', $value);
}

/**
 * @param Redis $redis
 * @param int|null $value
 * @return null|string
 */
function unpack_email_name($redis, int $value = null): ?string
{
    if (empty($value))
        return null;

    $result = $redis->zRangeByScore('pk:en', (string)$value, (string)$value);

    return array_shift($result);
}