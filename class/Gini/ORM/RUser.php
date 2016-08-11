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

    public static function getInfo($id)
    {
        try {
            return self::getRPC()->gapper->user->getInfo($id);
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
            return self::getRPC()->gapper->user->verify($username, $password);    
        } catch (\Gini\RPC\Exception $e) {
            return false;
        }
    }

    public static function getIdentity($id, $source='wechat')
    {
        try {
            return self::getRPC()->gapper->user->getIdentity($id, $source);
        } catch (\Gini\RPC\Exception $e) {

        }
    }

    public static function linkIdentity($id, $openId, $source='wechat')
    {
        try {
            return self::getRPC()->gapper->user->linkIdentity($id, $source, $openId);
        } catch (\Gini\RPC\Exception $e) {

        }

    }

    public static function unlinkIdentity($id, $openId, $source='wechat')
    {
        try {
            return self::getRPC()->gapper->user->unlinkIdentity($id, $source, $openId);
        } catch (\Gini\RPC\Exception $e) {

        }
    }

    public static function getUserByIdentity($openId, $source='wechat')
    {
        try {
            return self::getRPC()->gapper->user->getUserByIdentity($source, $openId);
        } catch (\Gini\RPC\Exception $e) {

        }
    }

}
