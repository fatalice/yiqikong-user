<?php

namespace Gini\Controller\API\YiQiKong;

class Message extends \Gini\Controller\API
{
	// 调用 gapper message 的 RPC 来查询自己的消息
	public function actionsearchMessages($criteria) {
		// 验证 gapper rpc
		$config = \Gini\Config::get('gapper.rpc');
        $rpc = \Gini\IoC::construct('\Gini\RPC', $config['url']);
        $rpc->gapper->app->authorize($config['client_id'], $config['client_secret']);

        // 调用 gapper API
        $return_data = $rpc->Gapper->Message->SearchMessages($criteria);
        return $return_data;
    }

    public function actiongetMessages($criteria) {
    	// 验证 gapper rpc
    	$config = \Gini\Config::get('gapper.rpc');
        $rpc = \Gini\IoC::construct('\Gini\RPC', $config['url']);
        $rpc->gapper->app->authorize($config['client_id'], $config['client_secret']);

        // 调用 gapper API
        $return_data = $rpc->Gapper->Message->SearchMessages($criteria);
        $messages = $rpc->Gapper->Message->getMessages($return_data['token'], $start, $return_data['total']);
        return $messages;
    }


    public function actiongetMessage($id) {
    	// 验证 gapper rpc
    	$config = \Gini\Config::get('gapper.rpc');
        $rpc = \Gini\IoC::construct('\Gini\RPC', $config['url']);
        $rpc->gapper->app->authorize($config['client_id'], $config['client_secret']);

        // 调用 gapper API
        $message = $rpc->Gapper->Message->getMessage($id);
        return $message;
    }

    public function actiondeleteMessages($ids) {
    	// 验证 gapper rpc
    	$config = \Gini\Config::get('gapper.rpc');
        $rpc = \Gini\IoC::construct('\Gini\RPC', $config['url']);
        $rpc->gapper->app->authorize($config['client_id'], $config['client_secret']);

        // 调用 gapper API
        $message = $rpc->Gapper->Message->deleteMessages($ids);
        return $message;
    }


}