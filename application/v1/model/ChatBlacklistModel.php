<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/7
 * Time: 13:46
 */

namespace app\v1\model;

use think\Db;

class ChatBlacklistModel
{
    public static $table = 'cq_chat_blacklist';

    // 插入
    public static function api_insert($chat_id , $uid , $fid)
    {
        return Db::table(self::$table)
            ->insertGetId([
                'chat_id' => $chat_id ,
                'uid' => $uid ,
                'fid' => $fid ,
            ]);
    }

    // 删除
    public static function api_delete($chat_id , $uid)
    {
        return Db::table(self::$table)
            ->where([
                ['chat_id' , '=' , $chat_id] ,
                ['fid' , '=' , $uid] ,
            ])
            ->delete();
    }

    // 统计数量
    public static function api_count($chat_id , $fid)
    {
        return Db::table(self::$table)
            ->where([
                ['chat_id' , '=' , $chat_id] ,
                ['fid' , '=' , $fid] ,
            ])
            ->count();
    }
}