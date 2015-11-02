<?php

namespace Gini\Controller\CLI;

class User extends \Gini\Controller\CLI {

	function actionGetUsersFromGapper() {

		// 循环去调用gapper rpc 获取gapper用户的信息, 尝试获得openId
		// 如果获得 openId, 把用户信息抓取到 yiqikong-user, 包括 openId

        $id = 1;
		$max_id = 378291;
		$count = 0;

		while ($id <= $max_id) {

			$userInfo = \Gini\ORM\RUser::getInfo($id);
			if ($userInfo) {
				$openId = \Gini\ORM\RUser::getIdentity($id);
				if ($openId) {
					$user = a('user', ['gapper_id' => $id]);
					if ($user->id) {
						$id ++;
						continue;
					}
					$user->name = $userInfo['name'];
					$user->gapper_id = $userInfo['id'];
					$user->email = $userInfo['email'];
					$user->phone = $userInfo['phone'];
					$user->wechat_openid = $openId;
					$user->wechat_bind_status = \Gini\ORM\User::BIND_STATUS_SUCCESS;
					$user->atime = date('Y-m-d H:i:s');
					$user->save();
					$count++;
				}
			}
			$id ++;
		}

		echo 'Finished moving ' . $count . ' users' . "\n";
    }
}