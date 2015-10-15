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

		//参数验证
        $check_keys = ['name', 'email', 'institution', 'phone', 'password'];

        if (count(array_diff($check_keys, array_keys($params)))) {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        }

        // 首先调用gapper rpc 来将用户注册为gapper用户
        $gapperUser = a('ruser');
        $gapperUser->name = $params['name'];
        $gapperUser->username = $gapperUser->email = $params['email'];
        $gapperUser->password = $params['password'];
        $gapperId = $gapperUser->save();
        if ($gapperId) {

            // 在 yiqikong_user 存储一些用户的具体信息
        	$user = a('user');
        	$user->name = $params['name'];
            $user->email = $params['email'];
            $user->phone = $params['phone'];
            $user->identity = $params['identity'];
            $user->institution = $params['institution'];
            $user->gapper_id = $gapperId;
            if ($user->save()) {

                //生成激活链接中的key存入activation表并返回
                $key = $user->createActivationKey();
                if ($key) {
                    return $key;
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
            $user = a('user')->whose('email')->is($email);
            if ($user->id) {
                if ($user->wechat_openid) {
                    return 'wechat';
                } else {
                    return 'yiqikong';
                }       
            } else {
                return 'gapper';
            }
        } else {
            throw \Gini\IoC::construct('\Gini\API\Exception', '用户不存在', 1004);
        }
        
    }

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
                    $identity = \Gini\ORM\RUser::getIdentity($user->gapper_id);
                    if (!$identity) {
                        $flag = \Gini\ORM\RUser::linkIdentity($user->gapper_id, 'wechat', $openId);

                        // 这一部分考虑是否放在wechat进行处理，而不再调用rpc
                        /*
                        if ($flag) {
                            $LUser = a('LUser')->whose('gapper_id')->is($this->id);
                            $LUser->gapper_id = $this->id;
                            $lab = $LUser->lab_id;
                            $lab[] = $labId;
                            $LUser->lab_id = $lab;
                            $LUser->save();
                        } */

                        if ($flag && $user->wechat_bind($openid)) {

                            $params = [
                                'user' => (int) $user->gapper_id,
                                'openid' => $openId,
                                'labId' => $labId,
                            ];

                            //发送给所有的 Lims-CF 服务器, 要求进行绑定
                            \Gini\Debade\Queue::of('Lims-CF')->push(
                                [
                                    'method' => 'wechat/bind',
                                    'params' => $params,
                                ], 'Lims-CF');
                        }
                    }


                        /*if ($labId == 'yiqikong') {
                            $conf = \Gini\Config::get('yiqikong.rpc')['web'];
                            $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
                            $rpc->YiQiKong->User->Bind((int) $this->id, $openId);
                        } else {
                            $params = [
                                'user' => (int) $this->id,
                                'openid' => $openId,
                                'labId' => $labId
                            ];
                            //发送给所有的 Lims-CF 服务器, 要求进行绑定
                            \Gini\Debade\Queue::of('Lims-CF')->push(
                                [
                                    'method' => 'wechat/bind',
                                    'params' => $params,
                                ], 'Lims-CF');
                        }
                    }*/
                    else if ($identity == $openId) {
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

}
