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
        'unique:gapper_id',
        //'unique:wechat_openid',
    ];

    public function save()
    {
        if ($this->ctime == '0000-00-00 00:00:00' || !$this->ctime) {
            $this->ctime = date('Y-m-d H:i:s');
        }

        return parent::save();
    }

    public function activation()
    {
        if ($this->atime == '0000-00-00 00:00:00' || !$this->atime) {
            $this->atime = date('Y-m-d H:i:s');
            return $this->save();
        } else {
            return false;
        }
    }

    public function connect ($lab) 
    {
        $tag_user = a('tag/user')->whose('user')->is($this)
            ->andWhose('tag')->is($lab);
        if ($tag_user->id) {
            return true;
        }

        $tag_user = a('tag/user');
        $tag_user->user = $this;
        $tag_user->tag = $lab;
        
        return $tag_user->save();
    }

    public function createActivationKey()
    {
        $key = substr(md5($this->email.time()), 5, 20);
        $expiration = date('Y-m-d H:i:s', strtotime("+2 days", time()));
        $activation = a('activation');
        $activation->user_id = $this->id;
        $activation->key = $key;
        $activation->expiration = $expiration;
        if ($activation->save()) {
            return $key;
        }

        return false;
    }

    public function wechat_bind($openId, $labId, $is_admin) {

        // 记录用户所在站点的信息
        if ($labId) {
            $tag = a('tag')->whose('name')->is($labId);
            if (!$tag->id) {
                $tag->name = $labId;
                $tag->save();
            }

            $tag_user = a('tag/user');
            if ($is_admin) {
                $tag_user->type = 1;
            }
            $tag_user->user = $this;
            $tag_user->tag = $tag;
            $tag_user->save();
        }

        $this->wechat_bind_status = self::BIND_STATUS_SUCCESS;
        $this->wechat_openid = $openId;

        return $this->save();

    }

    public function wechat_unbind() {

        // 删除绑定的站点信息
        $tag_users = those('tag/user')->whose('user')->is($this);
        foreach($tag_users as $tag_user) {
            if ($tag_user->type == 0) {
                $tag_user->delete();
            }
        }

        $this->wechat_bind_status = self::BIND_STATUS_NOT_YET;
        $this->wechat_openid = NULL;

        return $this->save();
    }

}
