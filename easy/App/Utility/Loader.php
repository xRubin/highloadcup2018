<?php

namespace App\Utility;

use EasySwoole\Component\AtomicManager;
use App\Utility\Packers;
//use Swoole\Coroutine\Redis;

class Loader
{
    /** @var \Redis */
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param \Account $account
     */
    function addAccount($account)
    {
        $maxId = AtomicManager::getInstance()->get('maxId');

        if ($account->id > $maxId->get())
            $maxId->set($account->id);

        //$this->redis->multi();
        {
            { // email
                list($email_name, $email_domain) = explode('@', $account->email);
                //$redis->set(StorageKey::email_name($account->id), $email_name);
                Packers\EmailName::register($this->redis, $account->id, $email_name);
                $domain_index = Packers\EmailDomain::pack($email_domain);
                $this->redis->set(StorageKey::email_domain($account->id), $domain_index);
                $this->redis->sAdd(IndexerKey::email_domain($email_domain), $account->id);
            }
            { // fname
                if (isset($account->fname)) {
                    $fname_index = Packers\Fname::pack($account->fname);
                    $this->redis->set(StorageKey::fname($account->id), $fname_index);
                    $this->redis->sAdd(IndexerKey::fname($account->fname), $account->id);
                    $this->redis->sAdd(IndexerKey::fname_exists(), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::fname_not_exists(), $account->id);
                }
            }
            { // sname
                if (isset($account->sname)) {
                    $this->redis->set(StorageKey::sname($account->id), $account->sname);
                    $this->redis->sAdd(IndexerKey::sname_begins_with(Helper::extractIndexFromSname($account->sname)), $account->id);
                    $this->redis->sAdd(IndexerKey::sname_exists(), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::sname_not_exists(), $account->id);
                }
            }
            { // phone

                if (isset($account->phone)) {
                    $this->redis->set(StorageKey::phone($account->id), $account->phone);
                    $this->redis->sAdd(IndexerKey::phone_exists(), $account->id);
                    $this->redis->sAdd(IndexerKey::phone_with_code(Helper::extractCodeFromPhone($account->phone)), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::phone_not_exists(), $account->id);
                }
            }
            { // sex
                $this->redis->set(StorageKey::sex($account->id), $account->sex);
                $this->redis->sAdd(IndexerKey::sex($account->sex), $account->id);
            }
            { // birth
                $this->redis->set(StorageKey::birth($account->id), $account->birth);
                $this->redis->sAdd(IndexerKey::birth_year(date('Y', $account->birth)), $account->id);
                //$redis->zAdd(IndexerKey::birthSortedSet(), $account->birth, $account->id);
            }
            { // country
                if (isset($account->country)) {
                    $county_index = Packers\Country::pack($account->country);
                    $this->redis->set(StorageKey::country($account->id), $county_index);
                    $this->redis->sAdd(IndexerKey::country_exists(), $account->id);
                    $this->redis->sAdd(IndexerKey::country($account->country), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::country_not_exists(), $account->id);
                }
            }
            { // city
                if (isset($account->city)) {
                    $city_index = Packers\City::pack($account->city);
                    $this->redis->set(StorageKey::city($account->id), $city_index);
                    $this->redis->sAdd(IndexerKey::city_exists(), $account->id);
                    $this->redis->sAdd(IndexerKey::city($account->city), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::city_not_exists(), $account->id);
                }
            }
            { // joined
                $this->redis->set(StorageKey::joined($account->id), $account->joined);
                $this->redis->sAdd(IndexerKey::joined_year(date('Y', $account->joined)), $account->id);
            }
            { // status
                $this->redis->set(StorageKey::status($account->id), Packers\Status::pack($account->status));
                $this->redis->sAdd(IndexerKey::status($account->status), $account->id);
            }
            { // interests
                if (isset($account->interests) && count($account->interests)) {
                    $this->redis->set(StorageKey::interests($account->id), Packers\Interests::pack($account->interests));
                    foreach ($account->interests as $interest) {
                        $this->redis->sAdd(IndexerKey::interest($interest), $account->id);
                    }
                }
            }
            { // premium
                if (isset($account->premium)) {
                    $this->redis->set(StorageKey::premium_start($account->id), $account->premium->start);
                    $this->redis->set(StorageKey::premium_finish($account->id), $account->premium->finish);
                    $this->redis->sAdd(IndexerKey::premium_exists(), $account->id);
                    if ((TIME >= $account->premium->start) && (TIME <= $account->premium->finish)) {
                        $this->redis->sAdd(IndexerKey::premium_now(), $account->id);
                    }
                } else {
                    $this->redis->sAdd(IndexerKey::premium_not_exists(), $account->id);
                }
            }
            { // likes
                if (isset($account->likes)) {
                    $this->redis->set(StorageKey::likes($account->id), Packers\Likes::pack($account->likes));
                    /** @var Like $like */
                    foreach ($account->likes as $like) {
                        $this->redis->sAdd(IndexerKey::account_liked($like->id), $account->id);
                    }
                }
            }
        }
        //$this->redis->exec();
    }
}