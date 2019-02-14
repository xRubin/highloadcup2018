<?php
namespace App\Utility;

class IndexerKey
{
    public static function sex(string $value): string
    {
        return 'ik:s:' . $value;
    }

    public static function status(string $value): string
    {
        return 'ik:st:' . $value;
    }

    public static function joined_year(string $value): string
    {
        return 'ik:jy:' . $value;
    }

    public static function birth_year(string $value): string
    {
        return 'ik:by:' . $value;
    }

    public static function interest(string $value): string
    {
        return 'ik:int:' . $value;
    }

    public static function email_domain(string $value): string
    {
        return 'ik:ed:' . $value;
    }

    public static function fname(string $value): string
    {
        return 'ik:fn:' . $value;
    }

    public static function fname_exists(): string
    {
        return 'ik:fne';
    }

    public static function fname_not_exists(): string
    {
        return 'ik:fnne';
    }

    public static function sname_begins_with($value): string
    {
        return 'ik:sn:' . $value;
    }

    public static function sname_exists(): string
    {
        return 'ik:sne';
    }

    public static function sname_not_exists(): string
    {
        return 'ik:snne';
    }

    public static function phone_with_code($value): string
    {
        return 'ik:pc:' . $value;
    }

    public static function phone_exists(): string
    {
        return 'ik:pe';
    }

    public static function phone_not_exists(): string
    {
        return 'ik:pne';
    }

    public static function country(string $value): string
    {
        return 'ik:cr:' . $value;
    }

    public static function country_exists(): string
    {
        return 'ik:cre';
    }

    public static function country_not_exists(): string
    {
        return 'ik:cr:-';
    }

    public static function city(string $value): string
    {
        return 'ik:ct:' . $value;
    }

    public static function city_exists(): string
    {
        return 'ik:cte';
    }

    public static function city_not_exists(): string
    {
        return 'ik:ct:-';
    }

    public static function premium_now(): string
    {
        return 'ik:prn';
    }

    public static function premium_exists(): string
    {
        return 'ik:pre';
    }

    public static function premium_not_exists(): string
    {
        return 'ik:prne';
    }

    public static function account_liked(int $accountId): string
    {
        return 'ik:lkd:' . (string)$accountId;
    }
}