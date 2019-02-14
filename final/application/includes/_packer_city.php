<?php declare(strict_types=1);

$maxCityId = new \Swoole\Atomic(0);

/**
 * @param Redis $redis
 * @param string $value
 * @return int
 */
function _registerNewCity($redis, string $value): int
{
    global $maxCityId;
    $redis->zAdd('pk:c', $index = $maxCityId->add(), $value);
    return $index;
}

/**
 * @param Redis $redis
 * @param string $value
 * @return int
 */
function pack_city($redis, string $value): int
{
    return (int)($redis->zScore('pk:c', $value)) ?: _registerNewCity($redis, $value);
}

/**
 * @param Redis $redis
 * @param int|null $value
 * @return null|string
 */
function unpack_city($redis, int $value = null): ?string
{
    if (empty($value))
        return null;

    $result = $redis->zRangeByScore('pk:c', (string)$value, (string)$value);

    return array_shift($result);
}
