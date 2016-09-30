<?php

namespace Gini\Model {

    class User
    {
        public static function search ($criteria) {
            // 暂时不做复杂搜索
            $users = those('user');
            return $users;
        }
    }
}
