<?php

namespace Gini\ORM;

class User extends Object
{
    public $name = 'string:120';
    public $gender = 'string:120';
    public $email = 'string:120';
    public $phone = 'string:120';
    public $identity = 'string:120'; // 身份证号
    public $residence = 'string:250';
    public $institution = 'string:120'; // 机主注册对应“所属机构”，普通用户注册对应“所在单位”
    public $initials = 'string:10';
    public $icon = 'string:250';
    public $gapper_id = 'int';
    public $ctime = 'datetime';
    public $atime = 'datetime';
    public $wechat_bind_status = 'int,default:0';
    public $wechat_openid = 'string:50';
    public $lab_id = 'string:50';
    public $is_admin = 'int, default:0';

    // 未绑定
    const BIND_STATUS_NOT_YET = 0;
    // 已绑定
    const BIND_STATUS_SUCCESS = 1;

    // 标识站点管理员
    const IS_ADMIN = 1;

    protected static $db_index = [
        'name',
        'unique:email',
        'phone',
        'institution',
        'unique:gapper_id'
    ];

    public function save()
    {
        if ($this->ctime == '0000-00-00 00:00:00' || !$this->ctime) {
            $this->ctime = date('Y-m-d H:i:s');
        }

        return parent::save();
    }
    
}