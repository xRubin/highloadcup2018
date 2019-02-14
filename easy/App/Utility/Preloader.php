<?php

namespace App\Utility;

use Redis;

class Preloader
{
    /** @var \Redis */
    private $redis;

    private $maxId = 0;

    private $cities = [null];
    private $domains = [null];
    private $fnames = [null];
    private $countries = [null];
    private $interests = [null];


    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param \Account $account
     */
    function addAccount($account)
    {
        if ($account->id > $this->maxId)
            $this->maxId = (int)$account->id;

        $this->redis->multi(Redis::PIPELINE);
        {
            { // email
                list($email_name, $email_domain) = explode('@', $account->email);
                //$redis->set(StorageKey::email_name($account->id), $email_name);
                \App\Utility\Packers\EmailName::register($this->redis, $account->id, $email_name);
                $domain_index = $this->packEmailDomain($email_domain);
                $this->redis->set(StorageKey::email_domain($account->id), $domain_index);
                $this->redis->sAdd(IndexerKey::email_domain($email_domain), $account->id);
            }
            { // fname
                if (isset($account->fname)) {
                    $fname_index = $this->packFname($account->fname);
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
                    $county_index = $this->packCountry($account->country);
                    $this->redis->set(StorageKey::country($account->id), $county_index);
                    $this->redis->sAdd(IndexerKey::country_exists(), $account->id);
                    $this->redis->sAdd(IndexerKey::country($account->country), $account->id);
                } else {
                    $this->redis->sAdd(IndexerKey::country_not_exists(), $account->id);
                }
            }
            { // city
                if (isset($account->city)) {
                    $city_index = $this->packCity($account->city);
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
                $this->redis->set(StorageKey::status($account->id), \App\Utility\Packers\Status::pack($account->status));
                $this->redis->sAdd(IndexerKey::status($account->status), $account->id);
            }
            { // interests
                if (isset($account->interests) && count($account->interests)) {
                    $this->redis->set(StorageKey::interests($account->id), $this->packInterests($account->interests));
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
                    $this->redis->set(StorageKey::likes($account->id), \App\Utility\Packers\Likes::pack($account->likes));
                    /** @var Like $like */
                    foreach ($account->likes as $like) {
                        $this->redis->sAdd(IndexerKey::account_liked($like->id), $account->id);
                    }
                }
            }
        }
        $this->redis->exec();
    }

    protected function packEmailDomain(string $value): int
    {
        if (!in_array($value, $this->domains))
            $this->domains[] = $value;

        return array_search($value, $this->domains);
    }

    protected function packFname(string $value): int
    {
        if (!in_array($value, $this->fnames))
            $this->fnames[] = $value;

        return array_search($value, $this->fnames);
    }

    protected function packCity(string $value): int
    {
        if (!in_array($value, $this->cities))
            $this->cities[] = $value;

        return array_search($value, $this->cities);
    }

    protected function packCountry(string $value): int
    {
        if (!in_array($value, $this->countries))
            $this->countries[] = $value;

        return array_search($value, $this->countries);
    }

    protected function packInterest(string $value): int
    {
        if (!in_array($value, $this->interests))
            $this->interests[] = $value;

        return array_search($value, $this->interests);
    }

    protected function packInterests(array $data): string
    {
        return implode('|', array_map('self::packInterest', $data));
    }

    public function dump(string $path)
    {
        file_put_contents($path, json_encode([
            'maxId' => $this->maxId,
            'cities' => array_filter($this->cities),
            'domains' => array_filter($this->domains),
            'fnames' => array_filter($this->fnames),
            'countries' => array_filter($this->countries),
            'interests' => array_filter($this->interests),
        ]));
    }
}
