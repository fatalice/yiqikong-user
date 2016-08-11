<?php

namespace Gini\ORM\YiQiKong;

/**
 * Robject 是用于数据获取的特殊类：用于数据模型对象远程rpc获取相应信息的底层支持类.
 **/
abstract class RObject extends \Gini\ORM\Object
{
    //缓存时间
    protected $cacheTimeout = 5;

    /**
     * 获取默认指定API路径的RPC对象
     *
     * @return new RPC
     **/
    protected static $_RPC = [];
    protected static function getRPC($type = 'directory')
    {
        if (!isset(self::$_RPC[$type])) {
            $conf = \Gini\Config::get('rpc')[$type];
            try {
                $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
                self::$_RPC[$type] = $rpc;
            } catch (RPC\Exception $e) {
            }
        }

        return self::$_RPC[$type];
    }

    /**
     * 按照配置设定的path 和 method 来进行RPC远程数据抓取.
     *
     * @return mixed
     **/
    public function fetchRPC($id)
    {
        return false;
    }

    public function db()
    {
        return false;
    }

    public function fetch($force = false)
    {
        if ($force || $this->_db_time == 0) {
            if (is_array($this->_criteria) && count($this->_criteria) > 0) {
                $criteria = $this->normalizeCriteria($this->_criteria);
                if (isset($criteria['id']) || isset($criteria['uuid'])) {
                    $id = isset($criteria['id']) ? $criteria['id'] : $criteria['uuid'];
                    $key = $this->name().'#'.$id;
                    $cacher = \Gini\Cache::of('orm');
                    $data = $cacher->get($key);
                    if (is_array($data)) {
                        \Gini\Logger::of('orm')->debug("cache hits on $key");
                    } else {
                        \Gini\Logger::of('orm')->debug("cache missed on $key");
                        $rdata = $this->fetchRPC($id);
                        if (is_array($rdata) && count($rdata) > 0) {
                            $data = $this->convertRPCData($rdata);
                            // set ttl to cacheTimeout sec
                            $cacher->set($key, $data, $this->cacheTimeout);
                        }
                    }
                }
            }

            $this->setData((array) $data);
        }
    }

    public function delete()
    {
        return false;
    }

    public function save()
    {
        return false;
    }
}
