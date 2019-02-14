<?php
namespace App\Utility\Packers;

class Interests
{
    public static function pack(array $data): string
    {
        return implode('|', array_map(__NAMESPACE__ . '\Interest::pack', $data));
    }

    public static function unpack($value = null): ?array
    {
        if (empty($value))
            return null;

        return array_map(__NAMESPACE__ . '\Interest::unpack', explode('|', $value));
    }
}