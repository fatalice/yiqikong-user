<?php

namespace Gini\Module {

    class YiQiKongUser
    {
        public static function setup()
        {
            \Gini\I18N::setup();
            
            date_default_timezone_set(\Gini\Config::get('system.timezone') ?: 'Asia/Shanghai');

            class_exists('\Gini\Those');
        }

        //检测
        public static function diagnose()
        {
            if (!\Gini\Config::get('app')['debade_secret']) {
                $errors[] = 'Need config app debade_secret';
            }

            $queues = \Gini\Config::get('debade')['queues'];

            if (!$queues['Lims-CF']) {
                $errors[] = 'Need config debade queues Lims-CF';
            }

            //不应设置运行环境
            if ($_SERVER['GINI_ENV']) {
                $errors[] = 'NEED unset GINI_ENV';
            }

            $default_cache = \Gini\Config::get('cache')['default'];

            //配置了 默认的 driver 为 none, 或者未配置
            if ($default_cache['driver'] == 'none' || !$default_cache) {
                $errors[] = 'NEED config cache';
            }

            return $errors;
        }
    }
}
