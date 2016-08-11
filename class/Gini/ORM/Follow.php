<?php

namespace Gini\ORM;

class Follow extends Object
{
    public $user = 'object:user';
    public $source_name = 'string:20';
    public $source_uuid = 'string:50';
    public $ctime = 'datetime';


    protected static $db_index = [
        'user',
        'source_name','source_uuid',
        'ctime'
    ];

    public function save()
    {
        if ($this->ctime == '0000-00-00 00:00:00' || !$this->ctime) {
            $this->ctime = date('Y-m-d H:i:s');
        }

        return parent::save();
    }

}
