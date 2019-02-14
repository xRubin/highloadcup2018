<?php declare(strict_types=1);

{
    function indexerKey_sex(string $value): string
    {
        return 'ik:s:' . $value;
    }

    function indexerKey_status(string $value): string
    {
        return 'ik:st:' . $value;
    }

    function indexerKey_joinedYear(string $value): string
    {
        return 'ik:jy:' . $value;
    }

    function indexerKey_birthYear(string $value): string
    {
        return 'ik:by:' . $value;
    }

    function indexerKey_interest(string $value): string
    {
        return 'ik:int:' . $value;
    }

    function indexerKey_emailDomain(string $value): string
    {
        return 'ik:ed:' . $value;
    }

    function indexerKey_fname(string $value): string
    {
        return 'ik:fn:' . $value;
    }

    function indexerKey_fnameExists(): string
    {
        return 'ik:fne';
    }

    function indexerKey_fnameNotExists(): string
    {
        return 'ik:fnne';
    }

    function indexerKey_snameBeginsWith($value): string
    {
        return 'ik:sn:' . $value;
    }

    function indexerKey_snameExists(): string
    {
        return 'ik:sne';
    }

    function indexerKey_snameNotExists(): string
    {
        return 'ik:snne';
    }

    function indexerKey_phoneWithCode($value): string
    {
        return 'ik:pc:' . $value;
    }

    function indexerKey_phoneExists(): string
    {
        return 'ik:pe';
    }

    function indexerKey_phoneNotExists(): string
    {
        return 'ik:pne';
    }

    function indexerKey_country(string $value): string
    {
        return 'ik:cr:' . $value;
    }

    function indexerKey_countryExists(): string
    {
        return 'ik:cre';
    }

    function indexerKey_countryNotExists(): string
    {
        return 'ik:cr:-';
    }

    function indexerKey_city(string $value): string
    {
        return 'ik:ct:' . $value;
    }

    function indexerKey_cityExists(): string
    {
        return 'ik:cte';
    }

    function indexerKey_cityNotExists(): string
    {
        return 'ik:ct:-';
    }

    function indexerKey_premiumNow(): string
    {
        return 'ik:prn';
    }

    function indexerKey_premiumExists(): string
    {
        return 'ik:pre';
    }

    function indexerKey_premiumNotExists(): string
    {
        return 'ik:prne';
    }

    function indexerKey_accountLiked(int $accountId): string
    {
        return 'ik:lkd:' . (string)$accountId;
    }
}