<?php declare(strict_types=1);

{
    function storageKey_birth(int $id): string
    {
        return $id . ':b';
    }

    function storageKey_likes(int $id): string
    {
        return $id . ':ls';
    }

    function storageKey_sex(int $id): string
    {
        return $id . ':s';
    }

    function storageKey_status(int $id): string
    {
        return $id . ':st';
    }

    function storageKey_phone(int $id): string
    {
        return $id . ':p';
    }

    function storageKey_email_name(int $id): string
    {
        return $id . ':en';
    }

    function storageKey_email_domain(int $id): string
    {
        return $id . ':ed';
    }

    function storageKey_city(int $id): string
    {
        return $id . ':ct';
    }

    function storageKey_country(int $id): string
    {
        return $id . ':cr';
    }

    function storageKey_joined(int $id): string
    {
        return $id . ':j';
    }

    function storageKey_fname(int $id): string
    {
        return $id . ':fn';
    }

    function storageKey_sname(int $id): string
    {
        return $id . ':sn';
    }

    function storageKey_interests(int $id): string
    {
        return $id . ':int';
    }

    function storageKey_premium_start(int $id): string
    {
        return $id . ':prs';
    }

    function storageKey_premium_finish(int $id): string
    {
        return $id . ':prf';
    }
}


/**
 * @param \Swoole\Coroutine\Redis $pipe
 * @param \Swoole\Coroutine\Redis $redis
 * @param Account $account
 */
function storageAccountAdd($pipe, $redis, $account)
{
    global $maxId;

    if ($account->id > $maxId->get())
        $maxId->set($account->id);

    //$redis->multi();
    {
        { // email
            list($email_name, $email_domain) = explode('@', $account->email);
            //$redis->set(storageKey_email_name($account->id), $email_name);
            _registerEmailName($pipe, $account->id, $email_name);
            $domain_index = pack_email_domain($email_domain);
            $pipe->set(storageKey_email_domain($account->id), $domain_index);
            $pipe->sAdd(indexerKey_emailDomain($email_domain), $account->id);
        }
        { // fname
            if (isset($account->fname)) {
                $fname_index = pack_fname($account->fname);
                $pipe->set(storageKey_fname($account->id), $fname_index);
                $pipe->sAdd(indexerKey_fname($account->fname), $account->id);
                $pipe->sAdd(indexerKey_fnameExists(), $account->id);
            } else {
                $pipe->sAdd(indexerKey_fnameNotExists(), $account->id);
            }
        }
        { // sname
            if (isset($account->sname)) {
                $pipe->set(storageKey_sname($account->id), $account->sname);
                $pipe->sAdd(indexerKey_snameBeginsWith(extractIndexFromSname($account->sname)), $account->id);
                $pipe->sAdd(indexerKey_snameExists(), $account->id);
            } else {
                $pipe->sAdd(indexerKey_snameNotExists(), $account->id);
            }
        }
        { // phone

            if (isset($account->phone)) {
                $pipe->set(storageKey_phone($account->id), $account->phone);
                $pipe->sAdd(indexerKey_phoneExists(), $account->id);
                $pipe->sAdd(indexerKey_phoneWithCode(extractCodeFromPhone($account->phone)), $account->id);
            } else {
                $pipe->sAdd(indexerKey_phoneNotExists(), $account->id);
            }
        }
        { // sex
            $pipe->set(storageKey_sex($account->id), $account->sex);
            $pipe->sAdd(indexerKey_sex($account->sex), $account->id);
        }
        { // birth
            $pipe->set(storageKey_birth($account->id), $account->birth);
            $pipe->sAdd(indexerKey_birthYear(date('Y', $account->birth)), $account->id);
            //$redis->zAdd(indexerKey_birthSortedSet(), $account->birth, $account->id);
        }
        { // country
            if (isset($account->country)) {
                $county_index = pack_country($account->country);
                $pipe->set(storageKey_country($account->id), $county_index);
                $pipe->sAdd(indexerKey_countryExists(), $account->id);
                $pipe->sAdd(indexerKey_country($account->country), $account->id);
            } else {
                $pipe->sAdd(indexerKey_countryNotExists(), $account->id);
            }
        }
        { // city
            if (isset($account->city)) {
                $city_index = pack_city($redis, $account->city);
                $pipe->set(storageKey_city($account->id), $city_index);
                $pipe->sAdd(indexerKey_cityExists(), $account->id);
                $pipe->sAdd(indexerKey_city($account->city), $account->id);
            } else {
                $pipe->sAdd(indexerKey_cityNotExists(), $account->id);
            }
        }
        { // joined
            $pipe->set(storageKey_joined($account->id), $account->joined);
            $pipe->sAdd(indexerKey_joinedYear(date('Y', $account->joined)), $account->id);
        }
        { // status
            $pipe->set(storageKey_status($account->id), pack_status($account->status));
            $pipe->sAdd(indexerKey_status($account->status), $account->id);
        }
        { // interests
            if (isset($account->interests) && count($account->interests)) {
                $pipe->set(storageKey_interests($account->id), pack_interests($account->interests));
                foreach ($account->interests as $interest) {
                    $pipe->sAdd(indexerKey_interest($interest), $account->id);
                }
            }
        }
        { // premium
            if (isset($account->premium)) {
                $pipe->set(storageKey_premium_start($account->id), $account->premium->start);
                $pipe->set(storageKey_premium_finish($account->id), $account->premium->finish);
                $pipe->sAdd(indexerKey_premiumExists(), $account->id);
                if ((TIME >= $account->premium->start) && (TIME <= $account->premium->finish)) {
                    $pipe->sAdd(indexerKey_premiumNow(), $account->id);
                }
            } else {
                $pipe->sAdd(indexerKey_premiumNotExists(), $account->id);
            }
        }
        { // likes
            if (isset($account->likes)) {
                $pipe->set(storageKey_likes($account->id), pack_likes($account->likes));
                /** @var Like $like */
                foreach ($account->likes as $like) {
                    $pipe->sAdd(indexerKey_accountLiked($like->id), $account->id);
                }
            }
        }
    }
    //$redis->exec();
}