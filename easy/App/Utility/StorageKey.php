<?php
namespace App\Utility;

class StorageKey
{

    public static function birth(int $id): string
    {
        return $id . ':b';
    }

    public static function likes(int $id): string
    {
        return $id . ':ls';
    }

    public static function sex(int $id): string
    {
        return $id . ':s';
    }

    public static function status(int $id): string
    {
        return $id . ':st';
    }

    public static function phone(int $id): string
    {
        return $id . ':p';
    }

//    public static function email_name(int $id): string
//    {
//        return $id . ':en';
//    }

    public static function email_domain(int $id): string
    {
        return $id . ':ed';
    }

    public static function city(int $id): string
    {
        return $id . ':ct';
    }

    public static function country(int $id): string
    {
        return $id . ':cr';
    }

    public static function joined(int $id): string
    {
        return $id . ':j';
    }

    public static function fname(int $id): string
    {
        return $id . ':fn';
    }

    public static function sname(int $id): string
    {
        return $id . ':sn';
    }

    public static function interests(int $id): string
    {
        return $id . ':int';
    }

    public static function premium_start(int $id): string
    {
        return $id . ':prs';
    }

    public static function premium_finish(int $id): string
    {
        return $id . ':prf';
    }
}