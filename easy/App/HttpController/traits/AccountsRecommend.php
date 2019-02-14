<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Exceptions\Exception404;
use App\Utility\Helper;
use App\Utility\IndexerKey;
use App\Utility\Packer;
use App\Utility\StorageKey;
use App\Utility\Validator;
use EasySwoole\Component\AtomicManager;
use App\Utility\Packers\Interest;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Trait AccountsRecommend
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsRecommend
{
    public function actionRecommend()
    {
        $limit = Validator::validateLimit($this->request());
        $params = $this->request()->getQueryParams();
        $query_id = $params['query_id'];
        $id = $params['id'];
        unset($params['query_id']);
        unset($params['limit']);
        unset($params['id']);

        $maxId = AtomicManager::getInstance()->get('maxId');
        if ($maxId->get() < $id)
            throw new Exception404('Account not found');

        $redis = $this->getRedis();

        $accountInterests = (array)explode('|', (string)$redis->get(StorageKey::interests($id)));
        $accountBirth = (int)$redis->get(StorageKey::birth($id));
        if (!$accountBirth)
            throw new Exception404('Account not found');

        $sets = [];
        //$temp = [];
        $sets[] = IndexerKey::sex(($redis->get(StorageKey::sex((int)$id)) == 'm') ? 'f' : 'm');

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'country':
                    if (!$value)
                        throw new Exception400();
                    $sets[] = IndexerKey::country($value);
                    break;
                case 'city':
                    if (!$value)
                        throw new Exception400();
                    $sets[] = IndexerKey::city($value);
                    break;
                default:
                    throw new Exception400('Unsupported parameter ' . $key);
            }
        }

        $interests = array_filter(array_map(function ($interestId) {
            if (empty($interestId))
                return null;
            return IndexerKey::interest(Interest::unpack($interestId));
        }, $accountInterests));

        $targets = 'filtered:' . $query_id;
        if (!count($interests)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write(json_encode(['accounts' => []]));
            $this->response()->end();
            return;
        }

        $withSameInterestsKey = 'recu:' . $query_id;
        $redis->sUnionStore($withSameInterestsKey, ...$interests);
        if (!$redis->sCard($withSameInterestsKey)) {
            $redis->delete($withSameInterestsKey);
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write(json_encode(['accounts' => []]));
            $this->response()->end();
            return;
        }

        Helper::redisSInterStoreWithSorting($redis, $targets, $withSameInterestsKey, ...$sets);

        $i = 0;
        $recArray = [];
        while ($recId = $redis->lIndex($targets, $i++)) {
            if ($compatibility = $this->compatibility($redis, $accountInterests, $accountBirth, (int)$recId))
                $recArray[$recId] = $compatibility;
        }

        $redis->delete($targets, $withSameInterestsKey);

        if (!count($recArray)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write(json_encode(['accounts' => []]));
            $this->response()->end();
            return;
        }

        arsort($recArray, SORT_NUMERIC);

        $response = ['accounts' => array_map(function ($id) use ($redis) {
            return Packer::getResultArray($redis, (int)$id, ['status', 'fname', 'sname', 'birth', 'premium']);
        }, array_slice(array_keys($recArray), 0, $limit))];
        
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write(json_encode($response));
        $this->response()->end();
    }

    /**
     * @param Redis $redis
     * @param array $meInterestKeys
     * @param int $meBirth
     * @param int $somebodyId
     * @return float
     */
    private function compatibility($redis, array $meInterestKeys, int $meBirth, int $somebodyId): float
    {
        $result = 0;

        if ($redis->sismember(IndexerKey::premium_now(), $somebodyId))
            $result += 100000;

        switch ($redis->get(StorageKey::status($somebodyId))) {
            case 0:
                $result += 10000;
                break;
            case 2:
                $result += 5000;
                break;
        }

        $somebodyInterestKeys = $redis->get(StorageKey::interests($somebodyId));
        if (!$somebodyInterestKeys)
            return 0;

        $inter = count(
            array_intersect(
                $meInterestKeys,
                (array)explode('|', $somebodyInterestKeys)
            )
        );
        if (!$inter)
            return 0;

        $result += $inter * 500;

        $result -= abs($meBirth - $redis->get(StorageKey::birth($somebodyId))) / 31556926;

        return $result;
    }
}