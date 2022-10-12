<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/18
 * Time: 14:00
 */

namespace app\v1\model;

use think\Db;

class ChatGmessageRetractModel
{
    public static $table = 'cq_chat_gmessage_retract';
    
    public static function insertGetId(array $param = [])
    {
        return Db::table(self::$table)
            ->insertGetId($param);
    }
}