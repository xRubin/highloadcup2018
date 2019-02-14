<?php
namespace App\Utility;

use App\Utility\Exceptions\Exception400;
use EasySwoole\Http\Request;

class Validator
{
    public static function validateLimit(Request $request): int
    {
        $limit = $request->getQueryParam('limit');
        if (!is_numeric($limit))
            throw new Exception400('wrong limit');
        $limit = (int)$limit;
        if ($limit < 1)
            throw new Exception400('wrong limit');
        return $limit;
    }

    public static function validateGroupKeys(Request $request): array
    {
        $keys = explode(',', $request->getQueryParam('keys'));
        if (count(array_diff($keys, ['interests','status','sex','country','city'])))
            throw new Exception400('wrong keys');
        return $keys;
    }
}