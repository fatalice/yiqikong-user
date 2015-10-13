<?php

namespace Gini\Controller\API\YiQiKong;

class User extends \Gini\Controller\API
{
    // 获取用户信息
    public function actionGetInfo()
    {

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
                    return '1';
                }
            } else {
                return '2';
            }
        } else {
            return '3';
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

        } else {
            throw \Gini\IoC::construct('\Gini\API\Exception', '异常参数传入', 1001);
        }

        return false;
    }

    // 登陆是调用gapper rpc进行用户信息账户密码的验证
    public function actionVerify($username, $password)
    {
        return \Gini\ORM\RUser::loginViaGapper($username, $password);
    }

}
