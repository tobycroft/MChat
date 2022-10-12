<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/22
 * Time: 10:00
 */

namespace app\v1\model;

use think\Db;

class MessageReadStatusModel
{
    public static $table = 'cq_message_read_status';

    public static function api_insert($user_id , $type , $msg_id , $read)
    {
        return Db::table(self::$table)
            ->insert([
                'type'      => $type ,
                'msg_id'    => $msg_id ,
                'user_id'   => $user_id ,
                'read'      => $read
            ]);
    }

    // 检查消息已读/未读
    public static function api_is_read($user_id , $type , $msg_id)
    {
        $res = Db::table(self::$table)
            ->where([
                ['type' , '=' , $type] ,
                ['msg_id' , '=' , $msg_id] ,
                ['user_id' , '=' , $user_id] ,
            ])
            ->value('read');

        // 如果未找到记录，默认已读，兼容旧数据
        // 如果找到记录，那么根据值作 boolean 转换
        return is_null($res) ? 1 : intval($res);
    }

    // 设置消息已读/未读
    public static function api_read_set($uid , $type , $msg_id , $is_read)
    {
        return Db::table(self::$table)
            ->where([
                ['user_id' , '=' , $uid] ,
                ['type' , '=' , $type] ,
                ['msg_id' , '=' , $msg_id]
            ])
            ->update([
                'read' => $is_read
            ]);
    }
}