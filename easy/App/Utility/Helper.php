<?php

namespace App\Utility;

class Helper
{
    /**
     * @param $value
     * @param array $array
     */
    public static function removeValueFromArray($value, array &$array = [])
    {
        if (($key = array_search($value, $array)) !== false)
            unset($array[$key]);
    }

    public static function combineArrays($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }
            $result = $tmp;
        }
        return $result;
    }

// ==================


    public static function extractIndexFromSname(string $value): string
    {
        return mb_strlen($value) < 3 ? '<3' : mb_substr($value, 0, 3);
    }

    public static function extractCodeFromPhone(string $value): string
    {
        if (preg_match('/\((\d{3})\)/', $value, $matches)) {
            return $matches[1];
        }

        return '';
    }

    // ========

    // ============

    /**
     * @param \Swoole\Coroutine\Redis $redis
     * @param string $key
     * @param string|null $other_keys
     * @return int
     */
    public static function redisSInterCount($redis, $key, $other_keys = null): int
    {

        $args = func_get_args();
        $redis = array_shift($args);
        $keys = array_unique($args);

        return (int)$redis->eval('local i = redis.call(\'SINTER\', unpack(KEYS))
        return #i', $keys, count($keys));
    }


    /**
     * @param \Swoole\Coroutine\Redis $redis
     * @param string $dest
     * @param string $key
     * @param null|string $other_keys
     * @return int
     */
    public static function redisSInterStoreWithSorting($redis, $dest, $key, $other_keys = null)
    {
        $args = func_get_args();
        $redis = array_shift($args);
        $keys = array_unique($args);

        return (int)$redis->eval('local si = redis.call(\'SINTERSTORE\', unpack(KEYS)) redis.call(\'SORT\', KEYS[1], \'DESC\', \'STORE\', KEYS[1]) return 1', $keys, count($keys));
    }

}