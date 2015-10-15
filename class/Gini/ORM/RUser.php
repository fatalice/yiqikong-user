<?php

namespace Gini\ORM;

class RUser extends Gapper\User
{
	protected static $_RPC = null;

    public function save() 
    {
        try {
            $data = [
                'name' => $this->name,
                'username' => $this->email,
                'email' => $this->email,
                'password' => $this->password
            ];
            
            return self::getRPC()->gapper->user->registerUser($data);
        } catch (\Gini\RPC\Exception $e) {
            return false;
        }
    }

    public function getInfo($id)
    {
        try {
            return $this->getRPC()->gapper->user->getInfo($id);
        } catch (\Gini\RPC\Exception $e) {
            return false;
        } 
    }

    protected static function getRPC()
    {
        if (!self::$_RPC) {
            $config = \Gini\Config::get('gapper.rpc');
            try {
                $rpc = \Gini\IoC::construct('\Gini\RPC', $config['url']);
                $rpc->gapper->app->authorize($config['client_id'], $config['client_secret']);
                self::$_RPC = $rpc;
            } catch (\Gini\RPC\Exception $e) {
                
            }
        }

        return self::$_RPC;
    }

    public static function loginViaGapper($username, $password)
    {
        try {
            return $this->getRPC()->gapper->user->verify($username, $password);    
        } catch (\Gini\RPC\Exception $e) {
            return false;
        }
    }

    public static function getIdentity($id)
    {
        try {
            return $this->getRPC()->gapper->user->getIdentity((int) $id, 'wechat');
        } catch (\Gini\RPC\Exception $e) {

        }
    }

    public static function linkIdentity($id, 'wechat', $openId)
    {
        try {
            return $this->getRPC()->gapper->user->linkIdentity((int) $id, 'wechat', $openId);
        } catch (\Gini\RPC\Exception $e) {

        }

    }

}
