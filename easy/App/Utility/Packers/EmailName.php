<?php
namespace App\Utility\Packers;

use Swoole\Coroutine\Redis;

class EmailName
{
    /**
     * @param Redis $redis
     * @param int $id
     * @param string $value
     */
    public static function register($redis, int $id, string $value)
    {
        $redis->zAdd('pk:en', $id, $value);
    }

    /**
     * @param Redis $redis
     * @param string $value
     */
    public static function remove($redis, string $value)
    {
        $redis->zRem('pk:en', $value);
    }

    /**
     * @param Redis $redis
     * @param int|null $value
     * @return null|string
     */
    public static function unpack($redis, int $value = null): ?string
    {
        if (empty($value))
            return null;

        $result = $redis->zRangeByScore('pk:en', (string)$value, (string)$value);

        return array_shift($result);
    }
}