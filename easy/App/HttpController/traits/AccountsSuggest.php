<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Exceptions\Exception404;
use App\Utility\Helper;
use App\Utility\IndexerKey;
use App\Utility\Packer;
use App\Utility\Packers\Likes;
use App\Utility\StorageKey;
use App\Utility\Validator;
use EasySwoole\Component\AtomicManager;
use EasySwoole\Component\TableManager;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Trait AccountsSuggest
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsSuggest
{
    public function actionSuggest()
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

        $liked = array_unique(
            array_column(
                $meLikes = @(array)Likes::unpack($redis->get(StorageKey::likes((int)$id))),
                'id'
            )
        );

        $set = 'sims:' . $query_id;
        $set_sims = array_map(function ($tId) {
            return IndexerKey::account_liked($tId);
        }, $liked);
        if (empty($set_sims)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write('{"accounts":[]}');
            $this->response()->end();
            return;
        }

        $redis->sUnionStore($set, ...$set_sims);
        $redis->sRemove($set, $id);

        $sets = [$set];

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'country':
                    if (!$value) {
                        unset($redis);
                        throw new Exception400();
                    }
                    $sets[] = IndexerKey::country($value);
                    break;
                case 'city':
                    if (!$value) {
                        unset($redis);
                        throw new Exception400();
                    }
                    $sets[] = IndexerKey::city($value);
                    break;
                default:
                    $redis->delete($set);
                    throw new Exception400('Unsupported parameter ' . $key);
            }
        }

        $sims = $redis->sInter(...$sets);
        $redis->delete($set);

        if (empty($sims)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write('{"accounts":[]}');
            $this->response()->end();
            return;
        }

        $simArray = [];
        foreach ($sims as $simId) {
            if ($similarity = $this->similarity($redis, $meLikes, (int)$simId))
                $simArray[$simId] = $similarity;
        }

        if (!count($simArray)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write('{"accounts":[]}');
            $this->response()->end();
            return;
        }

        arsort($simArray, SORT_NUMERIC);

        $response = [];

        $alr = array_column($meLikes, 'id');
        foreach ($simArray as $simId => $similarity) {
            if (count($response) >= $limit)
                break;

            $likes = array_column(@(array)Likes::unpack($redis->get(StorageKey::likes((int)$simId))), 'id');
            arsort($likes);
            foreach ($likes as $uid) {
                if (count($response) >= $limit)
                    break;

                if (in_array($uid, $alr)) continue;

                $response[] = Packer::getResultArray($redis, (int)$uid, ['status', 'fname', 'sname']);

                $alr[] = $uid;
            }
        }
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write(json_encode(['accounts' => $response]));
        $this->response()->end();
    }

    /**
     * @param Redis $redis
     * @param array $meLikes
     * @param int $somebodyId
     * @return float
     */
    private function similarity($redis, array $meLikes, int $somebodyId): float
    {
        $somebodyLikes = @(array)Likes::unpack($redis->get(StorageKey::likes($somebodyId)));

        $common = array_intersect(
            array_column($meLikes, 'id'),
            array_column($somebodyLikes, 'id')
        );

        if (empty($common))
            return 0;

        $result = 0;

        foreach ($common as $tId) {
            $meTsSum = 0;
            $meTsCount = 0;
            foreach ($meLikes as $like) {
                if ($like['id'] == $tId) {
                    $meTsSum += $like['ts'];
                    $meTsCount++;
                }
            }

            $somebodyTsSum = 0;
            $somebodyTsCount = 0;
            foreach ($somebodyLikes as $like) {
                if ($like['id'] == $tId) {
                    $somebodyTsSum += $like['ts'];
                    $somebodyTsCount++;
                }
            }

            $und = abs($somebodyTsSum / $somebodyTsCount - $meTsSum / $meTsCount);
            if ($und == 0) {
                $result += 1;
            } else {
                $result += 1 / $und;
            }
        }

        return $result;
    }
}