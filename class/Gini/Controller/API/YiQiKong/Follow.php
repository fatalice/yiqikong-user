<?php

namespace Gini\Controller\API\YiQiKong;

class Follow extends \Gini\Controller\API
{
    /**
     * @throws exception   1001: 异常参数传入
     * @throws exception   1002: 用户不存在
     * @throws exception   1003: 关注对象异常

    **/
    public static $apiError = [
        1001 => '异常参数传入',
        1002 => '用户不存在',
        1003 => '关注对象异常'
    ];

    public static $sourceList = [
        'equipment'
    ];

    private function _getUser($id)
    {
        if (is_numeric($id) && $id > 0) {
            $user = a('user')->whose('gapper_id')->is($id);
        }
        elseif (is_string($id) && $id) {
            if (preg_match('/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/', $id)) {
                $user = a('user')->whose('email')->is($id);
            } else {
                $user = a('user')->whose('wechat_openid')->is($id);
            }
        }
        else {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1001], 1001);
        }

        return $user;
    }

    //
    public function actionSearchFollows($criteria=[])
    {

        $follows = those('follow');

        if ($criteria['id']) {
            $user = $this->_getUser($criteria['id']);
            $follows = $follows->whose('user')->is($user);
        }

        if ($criteria['source_name']) {
            $follows = $follows->whose('source_name')->is($criteria['source_name']);
        }

        if ($criteria['source_uuid']) {
            $follows = $follows->whose('source_uuid')->is($criteria['source_uuid']);
        }

        $token = 'search'.uniqid();
        $_SESSION[$token] = json_encode($criteria);

        return [
            'total' => $follows->totalCount(),
            'token' => $token
        ];

    }

    public function actionGetFollows($token, $start=0, $end=10)
    {
        $criteria = $_SESSION[$token];
        if ($criteria) {
            $criteria = json_decode($criteria, TRUE);
            $follows = those('follow');

            if ($criteria['id']) {
                $user = $this->_getUser($criteria['id']);
                $follows = $follows->whose('user')->is($user);
            }

            if ($criteria['source_name']) {
                $follows = $follows->whose('source_name')->is($criteria['source_name']);
            }

            if ($criteria['source_uuid']) {
                $follows = $follows->whose('source_uuid')->is($criteria['source_uuid']);
            }

            $follows = $follows->limit($start, $end);

            $fs = [];

            foreach ($follows as $follow) {
                $fs[$follow->id] = [
                    'user_id' => $follow->user->id,
                    'gapper_id' => $follow->user->gapper_id,
                    'source_name' => $follow->source_name,
                    'source_uuid' => $follow->source_uuid,
                    'ctime' => strtotime($follow->ctime)
                ];
            }

            return $fs;
        }

        return [];
    }

    public function actionGetFollow($userId, $sourceName, $sourceId)
    {

        if (!in_array($sourceName, self::$sourceList)) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1001], 1001);
        }
        $user = $this->_getUser($userId);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1002], 1002);
        }

        $source = a($sourceName, $sourceId);
        if (!$source->id) {
            $source = a($sourceName, ['uuid' => $sourceId]);
        }
        if (!$source->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1003], 1003);
        }

        $follow = a('follow')->whose('user')->is($user)
                    ->andWhose('source_name')->is($sourceName)
                    ->andWhose('source_uuid')->is($sourceId);

        return (int)$follow->id;

    }

    public function actionBind($userId, $sourceName, $sourceId)
    {
        if (!in_array($sourceName, self::$sourceList)) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1001], 1001);
        }
        $user = $this->_getUser($userId);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1002], 1002);
        }
        $source = a($sourceName, $sourceId);
        if (!$source->id) {
            $source = a($sourceName, ['uuid' => $sourceId]);
        }
        if (!$source->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1003], 1003);
        }

        $follow = a('follow');
        $follow->user = $user;
        $follow->source_name = $sourceName;
        $follow->source_uuid = $sourceId;

        return $follow->save() ? $follow->id : false;
    }

    public function actionUnbind($userId, $sourceName, $sourceId)
    {
        if (!in_array($sourceName, self::$sourceList)) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1001], 1001);
        }
        $user = $this->_getUser($userId);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1002], 1002);
        }

        $follow = a('follow')->whose('user')->is($user)
                    ->andWhose('source_name')->is($sourceName)
                    ->andWhose('source_uuid')->is($sourceId);

        if (!$follow->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', self::$apiError[1003], 1003);
        }

        return $follow->delete();
    }

}