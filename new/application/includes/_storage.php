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
 * @param \Swoole\Coroutine\Redis $redis
 * @param Account $account
 */
function storageAccountAdd($redis, $account)
{
    global $maxId;

    if ($account->id > $maxId->get())
        $maxId->set($account->id);

    //$redis->multi();
    {
        { // email
            list($email_name, $email_domain) = explode('@', $account->email);
            //$redis->set(storageKey_email_name($account->id), $email_name);
            _registerEmailName($redis, $account->id, $email_name);
            $domain_index = pack_email_domain($email_domain);
            $redis->set(storageKey_email_domain($account->id), $domain_index);
            $redis->sAdd(indexerKey_emailDomain($email_domain), $account->id);
        }
        { // fname
            if (isset($account->fname)) {
                $fname_index = pack_fname($account->fname);
                $redis->set(storageKey_fname($account->id), $fname_index);
                $redis->sAdd(indexerKey_fname($account->fname), $account->id);
                $redis->sAdd(indexerKey_fnameExists(), $account->id);
            } else {
                $redis->sAdd(indexerKey_fnameNotExists(), $account->id);
            }
        }
        { // sname
            if (isset($account->sname)) {
                $redis->set(storageKey_sname($account->id), $account->sname);
                $redis->sAdd(indexerKey_snameBeginsWith(extractIndexFromSname($account->sname)), $account->id);
                $redis->sAdd(indexerKey_snameExists(), $account->id);
            } else {
                $redis->sAdd(indexerKey_snameNotExists(), $account->id);
            }
        }
        { // phone

            if (isset($account->phone)) {
                $redis->set(storageKey_phone($account->id), $account->phone);
                $redis->sAdd(indexerKey_phoneExists(), $account->id);
                $redis->sAdd(indexerKey_phoneWithCode(extractCodeFromPhone($account->phone)), $account->id);
            } else {
                $redis->sAdd(indexerKey_phoneNotExists(), $account->id);
            }
        }
        { // sex
            $redis->set(storageKey_sex($account->id), $account->sex);
            $redis->sAdd(indexerKey_sex($account->sex), $account->id);
        }
        { // birth
            $redis->set(storageKey_birth($account->id), $account->birth);
            $redis->sAdd(indexerKey_birthYear(date('Y', $account->birth)), $account->id);
            //$redis->zAdd(indexerKey_birthSortedSet(), $account->birth, $account->id);
        }
        { // country
            if (isset($account->country)) {
                $county_index = pack_country($account->country);
                $redis->set(storageKey_country($account->id), $county_index);
                $redis->sAdd(indexerKey_countryExists(), $account->id);
                $redis->sAdd(indexerKey_country($account->country), $account->id);
            } else {
                $redis->sAdd(indexerKey_countryNotExists(), $account->id);
            }
        }
        { // city
            if (isset($account->city)) {
                $city_index = pack_city($redis, $account->city);
                $redis->set(storageKey_city($account->id), $city_index);
                $redis->sAdd(indexerKey_cityExists(), $account->id);
                $redis->sAdd(indexerKey_city($account->city), $account->id);
            } else {
                $redis->sAdd(indexerKey_cityNotExists(), $account->id);
            }
        }
        { // joined
            $redis->set(storageKey_joined($account->id), $account->joined);
            $redis->sAdd(indexerKey_joinedYear(date('Y', $account->joined)), $account->id);
        }
        { // status
            $redis->set(storageKey_status($account->id), pack_status($account->status));
            $redis->sAdd(indexerKey_status($account->status), $account->id);
        }
        { // interests
            if (isset($account->interests) && count($account->interests)) {
                $redis->set(storageKey_interests($account->id), pack_interests($account->interests));
                foreach ($account->interests as $interest) {
                    $redis->sAdd(indexerKey_interest($interest), $account->id);
                }
            }
        }
        { // premium
            if (isset($account->premium)) {
                $redis->set(storageKey_premium_start($account->id), $account->premium->start);
                $redis->set(storageKey_premium_finish($account->id), $account->premium->finish);
                $redis->sAdd(indexerKey_premiumExists(), $account->id);
                if ((TIME >= $account->premium->start) && (TIME <= $account->premium->finish)) {
                    $redis->sAdd(indexerKey_premiumNow(), $account->id);
                }
            } else {
                $redis->sAdd(indexerKey_premiumNotExists(), $account->id);
            }
        }
        { // likes
            if (isset($account->likes)) {
                $redis->set(storageKey_likes($account->id), pack_likes($account->likes));
                /** @var Like $like */
                foreach ($account->likes as $like) {
                    $redis->sAdd(indexerKey_accountLiked($like->id), $account->id);
                }
            }
        }
    }
    //$redis->exec();
}