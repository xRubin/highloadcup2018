<?php
namespace App\Utility\Packers;

class Likes
{
    public static function pack(array $data): string
    {
        return implode('|', array_map(function ($like) {
            /** @var Like $like */
            return is_array($like) ? ($like['id'] . ':' . $like['ts']) : ($like->id . ':' . $like->ts);
        }, $data));
    }

    public static function unpack($value = null): ?array
    {
        if (empty($value))
            return null;

        return array_map(function ($data) {
            list($id, $ts) = explode(':', $data, 2);
            return [
                'id' => (int)$id,
                'ts' => (int)$ts,
            ];
        }, explode('|', $value));
    }
}