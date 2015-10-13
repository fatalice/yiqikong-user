<?php

namespace Gini\ORM;

class Activation extends Object
{
    public $user_id = 'int';
    public $key = 'string:120';
    public $expiration = 'datetime';

    protected static $db_index = [
        'unique:user_id',
        'unique:key',
        'expiration'
    ];
}