<?php

namespace Gini\Controller\API\YiQiKong;

class User extends \Gini\Controller\API
{
    /**
     * @throws exception   1001: 异常参数传入
     * @throws exception   1002: 激活连接超时
     * @throws exception   1003: 账户已经被激活
     * @throws exception   1004: 用户不存在
     * @throws exception   1005: gapper用户不存在
     **/

    private function _getUser($id)
    {
        if (!$id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        } else {
            if (is_numeric($id)) {
                $user = a('user')->whose('gapper_id')->is($id);
            } elseif (is_string($id)) {
                if (preg_match('/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/', $id)) {
                    $user = a('user')->whose('email')->is($id);
                } else {
                    $user = a('user')->whose('wechat_openid')->is($id);
                }
            } else {
                throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
            }
        }
        return $user;
    }

    private function _getData($user)
    {
        $labs = [];
        $is_admin_lab = '';

        $tag_users = those('tag/user')->whose('user')->is($user);
        foreach($tag_users as $tag_user) {
            if ($tag_user->id) {
                $name = $tag_user->tag->name;
                if ($tag_user->type == 1) {
                    $is_admin_lab = $name;
                }
                array_push($labs, $name);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'password' => \Gini\ORM\RUser::getInfo($user->gapper_id)['password'],
            'gender' => $user->gender,
            'email' => $user->email,
            'phone' => $user->phone,
            'identity' => $user->identity,
            'residence' => $user->residence,
            'institution' => $user->institution,
            'icon' => $user->icon,
            'gapper_id' => $user->gapper_id,
            'atime' => $user->atime,
            'wechat_bind_status' => $user->wechat_bind_status,
            'wechat_openid' => $user->wechat_openid,
            'lab_ids' => $labs,         // 数组, 已经绑定的站点, ['1', 'admin', 'nankai', ...]
            'is_admin_lab' => $is_admin_lab, // 机主建站后的站点的id
        ];
    }

    // 获取 yiqikong-user 用户信息
    public function actionGetInfo($id)
    {
        $user = $this->_getUser($id);
        if ($user->id) {
            return $this->_getData($user);
        }

        return false;
    }

    // 获取 gapper 用户信息
    public function actionGetGapperInfo($id)
    {
        $gapperUser = \Gini\ORM\RUser::getInfo($id);
        if ($gapperUser) {
            return $gapperUser;
        }

        return false;
    }

    // 当注册用户时候验证注册邮箱/电话/身份证号是否已经存在
    public function actionValidateInfo($data)
    {
        // 去 gapper 验证邮箱
        if (preg_match('/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/', $data)) {
            if (\Gini\ORM\RUser::getInfo($data)) {
                return true;
            }
        // 在yiqikong-user中验证电话号码
        } elseif (strlen($data) != 18) {
            $user = a('user')->whose('phone')->is($data);
            if ($user->id) {
                return true;
            }
        // 在yiqikong-user中验证省份证号的唯一性
        } else {
            $user = a('user')->whose('identity')->is($data);
            if ($user->id) {
                return true;
            }
        }
        return false;
    }

    // 新用户注册调用 (不是gapper用户)
	public function actionCreate($params, $activation=false)
	{
        $check_keys = ['name', 'email', 'password', 'institution', 'phone'];

        // 参数验证
        if (count(array_diff($check_keys, array_keys($params)))) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        }

        $user = a('user');
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->phone = $params['phone'];
        $user->identity = $params['identity'];
        $user->institution = $params['institution'];
        $res = $user->save();
        if ($res) {
            // 调用gapper rpc 来将用户注册为gapper用户
            $gapperUser = a('ruser');
            $gapperUser->name = $params['name'];
            $gapperUser->username = $gapperUser->email = $params['email'];
            $gapperUser->password = $params['password'];
            $gapperUser->phone = $params['phone'];
            $gapperId = $gapperUser->save();
            if ($gapperId) {
                $user->gapper_id = $gapperId;
                if (!$activation) {
                    $user->atime = date('Y-m-d H:i:s');
                }

                $res = $user->save();
                if ($res) {
                    // 用户添加成功, 调用yiqikong-billing API 初始化账户金额信息
                    $billingRPC = \Gini\IoC::construct('\Gini\RPC', \Gini\Config::get('rpc.billing')['url']);
                    $userAccount['user'] = $user->gapper_id;
                    $billingRPC->YiQiKong->Billing->addAccount($userAccount);
                    if ($activation){
                        $key = $user->createActivationKey();
                        if ($key) {
                            return $key;
                        }
                    } else {
                        if ($params['crypt']) {
                            $db = \Gini\Database::db('gapper');
                            $db->query("UPDATE `_auth` SET `password` = '{$params['crypt']}' WHERE `username` = '{$params['email']}'");
                        }
                        return $user->gapper_id;
                    }
                }
            } 
            else {
                $user->delete();
            }
        }

        return false;
	}

    // 添加 yiqikong-user 用户 (已经是gapper用户)
    public function actionAdd($params)
    {
        $check_keys = ['name', 'email', 'institution', 'phone'];

        // 参数验证
        if (count(array_diff($check_keys, array_keys($params)))) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        }

        // 判断是否为gapper用户
        $guser = \Gini\ORM\RUser::getInfo($params['email']);
        if (!$guser) {
            return false;
        }

        $user = a('user');

        foreach($params as $k => $v) {
            $user->$k = $v;
        }

        // 对于已经是gapper用户, 在yiqikong-user里边添加用户的时候无需再激活
        $user->atime = date('Y-m-d H:i:s');

        $user->gapper_id = $guser['id'];
        if ($user->save()) {
            if ($params['crypt']) {
                $db = \Gini\Database::db('gapper');
                $db->query("UPDATE `_auth` SET `password` = '{$params['crypt']}' WHERE `username` = '{$params['email']}'");
            }
            // 用户添加成功, 调用yiqikong-billing API 初始化账户金额信息
            $billingRPC = \Gini\IoC::construct('\Gini\RPC', \Gini\Config::get('rpc.billing')['url']);
            $userAccount['user'] = $user->gapper_id;
            $billingRPC->YiQiKong->Billing->addAccount($userAccount);

            return $user->gapper_id;
        }

        return false;
    }

    // 新注册用户进行激活
    public function actionActivation($key)
    {
        $activation = a('activation')->whose('key')->is($key);

        if ($userId = $activation->user_id) {
            if (strtotime($activation->expiration) >= time()) {
                $user = a('user', $userId);
                if ($user->activation()) {
                    $activation->delete();
                    return $user->gapper_id;
                }
            } else {
                throw \Gini\IoC::construct('\Gini\API\Exception', '激活链接超时', 1002);
            }
        } else {
            throw \Gini\IoC::construct('\Gini\API\Exception', '账户已经激活', 1003);
        }
    }

    // 用户更新信息
    public function actionUpdateInfo($params)
    {
        $user = a('user')->whose('email')->is($params['email']);

        $check_keys = [
            'name',
            'gender',
            'phone',
            'identity',
            'residence',
            'institution',
            'icon',
        ];

        if ($user) {
            // 更新用户信息
            foreach($check_keys as $k) {
                $user->$k = $params[$k];
            }

            if ($user->save()) {
                return true;
            }
        }

        return false;
    }

    // 登录时调用gapper rpc进行用户信息账户密码的验证
    public function actionLogin($username, $password)
    {
        $res = \Gini\ORM\RUser::loginViaGapper($username, $password);
        if ($res) {
            // 判断登陆地用户是不是yiqikong-user的用户
            $user = a('user')->whose('email')->is($username);
            if ($user->id) {
                return true;
            }
        }

        return false;
    }

    // 只验证用户是否能够登陆gapper
    public function actionGapperLogin($username, $password)
    {
        $res = \Gini\ORM\RUser::loginViaGapper($username, $password);
        if ($res) {
            return true;
        }

        return false;
    }

    public function actionLinkGapper($username) {
        $gapperUser = \Gini\ORM\RUser::getInfo($username);
        $user = a('user');
        $user->gapper_id = $gapperUser['id'];
        $user->name = $gapperUser['name'];
        $user->email = $gapperUser['email'];
        $user->phone = $gapperUser['phone'];
        $user->atime = date('Y-m-d H:i:s');
        if ($user->save()) {
            // 用户添加成功, 调用yiqikong-billing API 初始化账户金额信息
            $billingRPC = \Gini\IoC::construct('\Gini\RPC', \Gini\Config::get('rpc.billing')['url']);
            $userAccount['user'] = $user->gapper_id;
            $billingRPC->YiQiKong->Billing->addAccount($userAccount);
            return true;
        }
    }

    // 用户进行绑定微信 或者 更新了微信账重新绑定自已原有的账户时调用
    public function actionLinkWechat($id, $openId, $labId='', $is_admin=false)
    {
        // 根据 $id 获取用户
        $user = $this->_getUser($id);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }

        $flag = false;
        //Lock user
        $lock_file = \Gini\Config::get('yiqikong.lock_folder').'gapper-'.$user->gapper_id;
        $fp = fopen($lock_file, 'w+');
        if ($fp) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                try {
                    $user->wechat_bind($openId, $labId, $is_admin);
                    $params = [
                        'user' => (int) $user->gapper_id,
                        'openid' => $openId,
                        'email' => $user->email,
                    ];

                    if ($labId) {
                        $params['labid'] = $labId;
                    } else {
                        $userInfo = $this->_getData($user);
                        $labId = $userInfo['is_admin_lab'];
                        if ($labId) {
                            $params['labid'] = $labId;
                        }
                    }

                    //发送给所有的 Lims-CF 服务器, 要求进行绑定
                    \Gini\Debade\Queue::of('Lims-CF')->push(
                        [
                            'method' => 'wechat/bind',
                            'params' => $params,
                        ], 'Lims-CF');
                    $flag = true;
                } catch (\Gini\PRC\Exception $e) {
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        return $flag;
    }

    // 已绑定微信的账号再绑定另外一个YiQiKong账号时调用
    public function actionSwitchWechat($id, $openId, $new_labId)
    {
        // 根据 $id 获取用户
        $user = $this->_getUser($id);    // 一般根据邮箱获取要绑定的新用户
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }

        $flag = false;
        //Lock user
        $lock_file = \Gini\Config::get('yiqikong.lock_folder').'gapper-'.$user->gapper_id;
        $fp = fopen($lock_file, 'w+');
        if ($fp) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                try {
                    $olduser = $this->_getUser($openId);    // 一般根据openId获取要解绑的旧用户
                    if ($olduser->gapper_id) {
                        $olduser_labs = $this->_getData($olduser)['lab_ids'];
                        // 对老用户进行解绑
                        $olduser->wechat_unbind();
                        foreach ($olduser_labs as $labId) {
                            \Gini\Debade\Queue::of('Lims-CF')->push(
                                [
                                    'method' => 'wechat/unbind',
                                    'params' => [
                                        'user' => (int) $olduser->gapper_id,
                                        'labid' => $labId,
                                    ],
                                ], 'Lims-CF');
                        }
                        // 绑定新用户
                        $user->wechat_bind($openId, $new_labId, false);
                        // push 到远程
                        $params = [
                            'user' => (int) $user->gapper_id,
                            'openid' => $openId,
                            'email' => $user->email,
                            'labid' => $new_labId,
                        ];
                        \Gini\Debade\Queue::of('Lims-CF')->push(
                            [
                                'method' => 'wechat/bind',
                                'params' => $params,
                            ], 'Lims-CF');
                        $flag = true;
                    }
                } catch (\Gini\PRC\Exception $e) {
                }
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $flag;
    }

    // 解绑微信
    public function actionUnbind($gapper_id)
    {
        // 根据 $gapper_id 获取用户
        $user = $this->_getUser($gapper_id);    // 要绑定的新用户
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }

        $lab_ids = $this->_getData($user)['lab_ids'];
        if ($user->wechat_unbind()) {
            // debade push 解绑信息到其他站点进行解绑
            foreach ($lab_ids as $labId) {
                \Gini\Debade\Queue::of('Lims-CF')->push(
                    [
                        'method' => 'wechat/unbind',
                        'params' => [
                            'user' => (int) $user->gapper_id,
                            'labid' => $labId,
                        ],
                    ], 'Lims-CF');
            }
            return true;
        }

        return false;
    }

    public function actionGetActivationKey($email) {
        $user = a('user')->whose('email')->is($email);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }
        $activation = a('activation')->whose('user_id')->is($user->id);
        if (!$activation->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '账户已经激活', 1003);
        }
        return $activation->key;
    }

    public function actionGetUserByActivationKey($key)
    {
        $activation = a('activation')->whose('key')->is($key);

        if ($userId = $activation->user_id) {
            $user = a('user', $userId);
            if ($user->id) {
                return $this->_getData($user);
            }
        }

        return false;
    }

    // 激活链接超时, 需要重新发送激活链接
    public function actionreSendActivationUrl($key)
    {
        $activation = a('activation')->whose('key')->is($key);
        if ($userId = $activation->user_id) {
            $activation->delete();
            $user = a('user', $userId);
            $key = $user->createActivationKey();
            if ($key) {
                return $key;
            }
        }

        return false;
    }

    // 记录用户再次绑定微信时的站点
    public function actionSetLabId($openId, $labId)
    {
        // 根据 openId 获取用户信息
        $user = $this->_getUser($openId);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }

        // 如果站点信息不在tag表中, 将站点信息加入tag表中
        $tag = a('tag')->whose('name')->is($labId);
        if (!$tag->id) {
            $tag->name = $labId;
            $tag->save();
        }

        // 判断站点信息是否已经和用户信息被记录在tag/user表中
        $userInfo = $this->_getData($user);
        if (!in_array($labId, $userInfo['lab_ids'])) {
            $tag_user = a('tag/user');
            $tag_user->user = $user;
            $tag_user->tag = $tag;
            $tag_user->save();

            // 将绑定信息通过debade push 远程的lims站点上去
            $params = [
                'user' => (int) $user->gapper_id,
                'openid' => $openId,
                'email' => $user->email,
                'labid' => $labId,
            ];

            \Gini\Debade\Queue::of('Lims-CF')->push(
                [
                    'method' => 'wechat/bind',
                    'params' => $params,
                ], 'Lims-CF');
        }

        return true;
    }

    public function actionConnect($id, $labId) {
        $user = a('user', $id);
        if (!$user->id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }

        $tag = a('tag')->whose('name')->is($labId);
        if (!$tag->id) {
            $tag->name = $labId;
            $tag->save();
        }

        return $user->connect($tag);
    }

}

