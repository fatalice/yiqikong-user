<?php

namespace Gini\ORM;

class Tag extends Object
{
	public $type = 'int,default:0';    // 预留的表示站点的类型, 默认为0, 暂时不使用
    public $name = 'string:120';

    protected static $db_index = [
        'unique:name',
        'type'
    ];
}