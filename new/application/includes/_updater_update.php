<?php declare(strict_types=1);

function updaterAccountUpdate(int $id, stdClass $data)
{
    $redis = RedisQueue::getRedis();

    if (!$redis->exists(storageKey_email_domain($id)))
        throw new Exception404('Account not found: ' . $id);

    validateUpdate($redis, $data = (array)$data);

    foreach ($data as $key => $value) {
        switch ($key) {
            case 'sex':
                $redis->set(storageKey_sex($id), $value);
                $redis->sRem(indexerKey_sex($value == 'm' ? 'f' : 'm'), $id);
                $redis->sAdd(indexerKey_sex($value), $id);
                break;
            case 'status':
                switch ($value) {
                    case STATUS_1:
                        $redis->sRem(indexerKey_status(STATUS_2), $id);
                        $redis->sRem(indexerKey_status(STATUS_3), $id);
                        break;
                    case STATUS_2:
                        $redis->sRem(indexerKey_status(STATUS_1), $id);
                        $redis->sRem(indexerKey_status(STATUS_3), $id);
                        break;
                    case STATUS_3:
                        $redis->sRem(indexerKey_status(STATUS_1), $id);
                        $redis->sRem(indexerKey_status(STATUS_2), $id);
                        break;
                    default:
                        throw new Exception400('Unsupported status value');
                }
                $redis->sAdd(indexerKey_status($value), $id);
                $redis->set(storageKey_status($id), pack_status($value));
                break;
            case 'email':
                list($email_name, $email_domain) = explode('@', $value);
                $find_email = $redis->zRangeByScore('pk:en', (string)$id, (string)$id);
                if (count($find_email))
                    _removeEmailName($redis, array_shift($find_email));

                _registerEmailName($redis, $id, $email_name);
                $redis->sRem(indexerKey_emailDomain(
                    unpack_email_domain($redis->get(storageKey_email_domain($id)))
                ), $id);
                $redis->set(storageKey_email_domain($id), pack_email_domain($email_domain));
                $redis->sAdd(indexerKey_emailDomain($email_domain), $id);
                break;
            case 'country':
                if ($redis->exists(storageKey_country($id))) {
                    $redis->sRem(indexerKey_country(unpack_country($redis->get(storageKey_country($id)))), $id);
                } else {
                    $redis->sRem(indexerKey_countryNotExists(), $id);
                    $redis->sAdd(indexerKey_countryExists(), $id);
                }
                $redis->sAdd(indexerKey_country($value), $id);
                $redis->set(storageKey_country($id), pack_country($value));
                break;
            case 'city':
                if ($redis->exists(storageKey_city($id))) {
                    $redis->sRem(indexerKey_city(unpack_city($redis, (int)$redis->get(storageKey_city($id)))), $id);
                } else {
                    $redis->sRem(indexerKey_cityNotExists(), $id);
                    $redis->sAdd(indexerKey_cityExists(), $id);
                }
                $redis->sAdd(indexerKey_city($value), $id);
                $redis->set(storageKey_city($id), pack_city($redis, $value));
                break;
            case 'premium':
                if ($redis->exists(storageKey_premium_start($id))) {
                    if ((TIME >= $value->start) && (TIME <= $value->finish)) {
                        $redis->sAdd(indexerKey_premiumNow(), $id);
                    } else {
                        $redis->sRem(indexerKey_premiumNow(), $id);
                    }
                } else {
                    $redis->sRem(indexerKey_premiumNotExists(), $id);
                    $redis->sAdd(indexerKey_premiumExists(), $id);
                }

                $redis->set(storageKey_premium_start($id), $value->start);
                $redis->set(storageKey_premium_finish($id), $value->finish);

                break;
            case 'fname':
                if ($old_index = $redis->get(storageKey_fname($id))) {
                    $old_fname = unpack_fname($old_index);
                    $redis->sRem(indexerKey_fname($old_fname), $id);
                } else {
                    $redis->sRem(indexerKey_fnameNotExists(), $id);
                    $redis->sAdd(indexerKey_fnameExists(), $id);
                }

                $redis->set(storageKey_fname($id), pack_fname($value));
                $redis->sAdd(indexerKey_fname($value), $id);
                break;
            case 'sname':
                if ($sname = $redis->get(storageKey_sname($id))) {
                    $redis->sRem(indexerKey_snameBeginsWith(extractIndexFromSname($sname)), $id);
                } else {
                    $redis->sRem(indexerKey_snameNotExists(), $id);
                    $redis->sAdd(indexerKey_snameExists(), $id);
                }

                $redis->set(storageKey_sname($id), $value);
                $redis->sAdd(indexerKey_snameBeginsWith(extractIndexFromSname($value)), $id);
                break;
            case 'interests':
                $old = (array)unpack_interests($redis->get(storageKey_interests($id)));
                foreach (array_diff($old, (array)$value) as $interest) {
                    if ($interest)
                        $redis->sRem(indexerKey_interest($interest), $id);
                }
                foreach (array_diff((array)$value, $old) as $interest) {
                    if ($interest)
                        $redis->sAdd(indexerKey_interest($interest), $id);
                }

                $redis->set(storageKey_interests($id), pack_interests((array)$value));
                break;
            case 'phone':
                if ($phone = $redis->get(storageKey_phone($id))) {
                    $redis->sRem(indexerKey_phoneWithCode(extractCodeFromPhone($phone)), $id);
                } else {
                    $redis->sRem(indexerKey_phoneNotExists(), $id);
                    $redis->sAdd(indexerKey_phoneExists(), $id);
                }

                $redis->set(storageKey_phone($id), $value);
                $redis->sAdd(indexerKey_phoneWithCode(extractCodeFromPhone($value)), $id);
                break;
            default:
                $redis->close();
                unset($redis);
                throw new Exception400('Unsupported parameter ' . $key);
        }
    }

    $redis->close();
    unset($redis);

    return [];
}

/**
 * @param Redis $redis
 * @param array $data
 */
function validateUpdate($redis, array $data)
{
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'sex':
                if (!in_array($value, ['m', 'f']))
                    throw new Exception400('Unsupported sex value');
                break;
            case 'status':
                if (!in_array($value, ["свободны", "заняты", "всё сложно"]))
                    throw new Exception400('Unsupported status value');
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                    throw new Exception400('Invalid email ' . $value);
                list($email_name, $email_domain) = explode('@', $value);
                if ($arid = (int)$redis->zScore('pk:en', $email_name)) {
                    if ($redis->get(storageKey_email_domain($arid)) === pack_email_domain($email_domain))
                        throw new Exception400('Busy email ' . $value);
                }
                break;
            case 'country':
                break;
            case 'city':
                break;
            case 'premium':
                if (!isset($value->start) || !is_int($value->start) || !isset($value->finish) || !is_int($value->finish))
                    throw new Exception400('Invalid premium value');
                break;
            case 'fname':
                break;
            case 'sname':
                break;
            case 'interests':
                break;
            case 'phone':
                break;
            default:
                throw new Exception400('Unknown parameter' . $key);
        }
    }
}