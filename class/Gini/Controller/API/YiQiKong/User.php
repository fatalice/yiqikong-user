<?php

namespace Gini\Controller\API\YiQiKong;

class User extends \Gini\Controller\API
{
    /**
     * @throws exception   1001: 异常参数传入
     * @throws exception   1002: 激活连接超时
     * @throws exception   1003: 账户已经被激活
     * @throws exception   1004: 用户不存在
     **/

    private function _getUser($id)
    {
        if (!$id) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        } else {
            if (is_int($id)) {
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

    private function _getUserData($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
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
            'lab_id' => $user->lab_id,
            'id_admin' => $user->is_admin,
        ];
    }

    // 获取用户信息
    public function actionGetInfo($id)
    {
        $user = $this->_getUser($id);
        if ($user->id) {
            return $this->_getUserData($user);
        }

        return false;
    }

    // 注册
	public function actionSignup($params)
	{
        if (!$params['email']) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        }

        $guser = \Gini\ORM\RUser::getInfo($email);

        // 参数验证
        $check_keys = ['name', 'institution', 'phone'];

        // 如果不是gapper用户而是新用户注册需要验证密码
        if (!$guser) {
            array_push($check_keys, 'password');

            if (count(array_diff($check_keys, array_keys($params)))) {
                throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
            }

            // 调用gapper rpc 来将用户注册为gapper用户
            $gapperUser = a('ruser');
            $gapperUser->name = $params['name'];
            $gapperUser->username = $gapperUser->email = $params['email'];
            $gapperUser->password = $params['password'];
            $gapperId = $gapperUser->save();
        } else {
            // 用户已经是gapper用户

            if (count(array_diff($check_keys, array_keys($params)))) {
                throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
            }

            $gapperId = $guser['id'];
        }

        // 对于已经刷入 yiqikong-user 的用户避免重复注册
        $luser = a('user')->whose('gapper_id')->is($gapperId);
        if ($luser) {
            return;
        }

        // 在 yiqikong_user 存储用户的具体信息
        if ($gapperId) {
        	$user = a('user');
        	$user->name = $params['name'];
            $user->email = $params['email'];
            $user->phone = $params['phone'];
            $user->identity = $params['identity'];
            $user->institution = $params['institution'];
            $user->gapper_id = $gapperId;
            $user->gender = $params['gender'];
            $user->residence = $params['residence'];
            $user->initials = $params['initials'];
            $user->icon = $params['icon'];
            $user->ctime = $params['ctime'];
            $user->atime = $params['atime'];
            $user->wechat_bind_status = $params['wechat_bind_status'];
            $user->wechat_openid = $params['wechat_openid'];
            $user->lab_id = $params['lab_id'];
            $user->is_admin = $params['is_admin'];
            $result = $user->save();
            if ($result) {
                // 对于web新注册的用户生成激活链接中的key存入activation表并返回
                if (!$guser) {
                    $key = $user->createActivationKey();
                    if ($key) {
                        return $key;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
	}

    // 新注册用户进行激活
    public function actionActivation($key)
    {
        $activation = a('activation')->whose('key')->is($key);

        if ($userId = $activation->expiration) {
            if (strtotime($activation->expiration) >= time()) {
                $user = a('user', $userId);
                if ($user->activation()) {
                    $activation->delete();
                    return true;
                }
            } else {
                throw \Gini\IoC::construct('\Gini\API\Exception', '激活链接超时', 1002);
            }
        } else {
            throw \Gini\IoC::construct('\Gini\API\Exception', '账户已经激活', 1003);
        }

        return false;
    }

    // 激活链接超时, 需要重新发送激活链接
    public function actionReSend($key)
    {
        $activation = a('activation')->whose('key')->is($key);
        if ($userId = $activation->user_id) {
            $user = a('user', $userId);
            $key = $user->createActivationKey();
            if ($key) {
                return $key;
            }
        }

        return false;
    }

    // 用户更新信息
    public function actionUpdateInfo($params)
    {

        $user = a('user')->whose('email')->is($params['email']);

        if ($user) {
            // 更新用户信息
            $user->name = $params['name'];
            $user->gender = $params['gender'];
            $user->phone = $params['phone'];
            $user->identity = $params['identity'];
            $user->residence = $params['residence'];
            $user->institution = $params['institution'];
            if ($params['icon']) {
                $user->icon = $params['icon']
            }

            if ($user->save()) {
                return true
            }
        }

        return false;
    }

    // 登录时调用gapper rpc进行用户信息账户密码的验证
    public function actionVerify($username, $password)
    {
        return \Gini\ORM\RUser::loginViaGapper($username, $password);
    }

    public function actionAuthorize($email, $password) {
        if (\Gini\ORM\RUser::loginViaGapper($username, $password)) {
            $gapper = \Gini\ORM\RUser::getInfo($email);
            $user = a('user')->whose('email')->is($email);
            if ($user->id) {
                if ($user->wechat_openid) {
                    return 'wechat|'.$user->wechat_openid;
                } else {
                    return 'yiqikong|'.$user->id;
                }       
            } else {
                return 'gapper|'.$gapper['id'];
            }
        } else {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }
        
    }

    # 用户进行绑定微信 或者 更新了微信账重新绑定自已原有的账户时调用
    public function actionLinkWechat($id, $openId)
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
                    $identity = \Gini\ORM\RUser::getIdentity((int) $user->gapper_id);
                    if (!$identity || $identity != $openId) {
                        $flag = \Gini\ORM\RUser::linkIdentity((int) $user->gapper_id, 'wechat', $openId);

                        if ($flag){
                            $user->wechat_bind($openid);
                            $params = [
                                'user' => (int) $user->gapper_id,
                                'openid' => $openId,
                                'labId' => $user->lab_id,
                            ];

                            //发送给所有的 Lims-CF 服务器, 要求进行绑定
                            \Gini\Debade\Queue::of('Lims-CF')->push(
                                [
                                    'method' => 'wechat/bind',
                                    'params' => $params,
                                ], 'Lims-CF');
                        }
                    } else if ($identity == $openId) {
                        if ($user->wechat_bind($openid)) {
                            $flag = true;
                        }
                    }
                } catch (\Gini\PRC\Exception $e) {
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        return $flag;
    }

    // 已绑定微信的账号再绑定另外一个YiQiKong账号时调用
    public function actionSwitchWechat($id, $openId)
    {
        // 根据 $id 获取用户
        $user = $this->_getUser($id);    // 要绑定的新用户
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
                    // 获取旧 YiQiKong 账号的用户信息
                    $olduser = $this->_getUser($openId);    // 要解绑的旧用户
                    if ($olduser->gapper_id) {
                        // 对老用户进行解绑
                        $flag = \Gini\ORM\RUser::unlinkIdentity((int) $olduser->gapper_id, 'wechat', $openId);

                        if($flag) {
                            $olduser->wechat_unbind();

                            //发送给所有的 Lims-CF 服务器, 要求进行解绑
                            \Gini\Debade\Queue::of('Lims-CF')->push(
                                [
                                    'method' => 'wechat/unbind',
                                    'params' => [
                                        'user' => (int) $olduser->gapper_id,
                                        'labId' => $olduser->lab_id,
                                    ],
                                ], 'Lims-CF');

                            // 绑定新用户
                            $flag = \Gini\ORM\RUser::linkIdentity((int) $user->gapper_id, 'wechat', $openId);

                            if ($flag){
                                $user->wechat_bind($openid);
                                $params = [
                                    'user' => (int) $user->gapper_id,
                                    'openid' => $openId,
                                    'labId' => $user->lab_id,
                                ];

                                //发送给所有的 Lims-CF 服务器, 要求进行绑定
                                \Gini\Debade\Queue::of('Lims-CF')->push(
                                    [
                                        'method' => 'wechat/bind',
                                        'params' => $params,
                                    ], 'Lims-CF');
                            }
                        }
                    }
                } catch (\Gini\PRC\Exception $e) {
                }
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $flag;
    }

}
