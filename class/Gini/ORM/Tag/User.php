<?php

namespace Gini\ORM\Tag;

class User extends \Gini\ORM\Object
{
    public $type = 'int,default:0';    // 对应人员在该tag上的角色, 如管理员/黑名单/普通所属人员
    public $user = 'object:user';
    public $tag = 'object:tag';

    // type: 非管理员
    const IS_NOT_ADMIN = 0;
    // type: 标示管理员
    const IS_ADMIN = 1;

    protected static $db_index = [
        'unique:type,user,tag',
        'type',
        'user',
        'tag',
    ];

    public function save()
    {
        return parent::save();
    }
}