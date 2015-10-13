<?php

namespace Gini\Controller\API\YiQiKong;

class User extends \Gini\Controller\API
{
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
            	return true;
            }
        }

        return false;
	}

}
