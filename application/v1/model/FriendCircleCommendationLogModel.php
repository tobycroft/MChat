<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:39
 */

namespace app\v1\model;

use think\Db;

class FriendCircleCommendationLogModel
{
    public static $table = 'cq_friend_circle_commendation_log';

    public static function exists($user_id , $friend_circle_id)
    {
        $res = Db::table(self::$table)
            ->where([
                ['friend_circle_id' , '=' , $friend_circle_id] ,
                ['user_id' , '=' , $user_id]
            ])
            ->lock(true)
            ->count();
        return $res > 0;
    }

    public static function delete($user_id , $friend_circle_id)
    {
        return Db::table(self::$table)
            ->where([
                ['friend_circle_id' , '=' , $friend_circle_id] ,
                ['user_id' , '=' , $user_id]
            ])
            ->delete();
    }

    public static function insertGetId($data = [])
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'user_id' ,
                'friend_circle_id'
            ]));
    }

    // 点赞好友列表
    public static function get($friend_circle_id)
    {
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->select();
    }

    // 删除指定朋友圈下的所有 点赞记录
    public static function deleteByFriendCircleId($friend_circle_id)
    {
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->delete();
    }

}