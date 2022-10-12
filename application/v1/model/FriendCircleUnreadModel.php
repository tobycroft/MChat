<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/15
 * Time: 16:46
 */

namespace app\v1\model;


use think\Db;

class FriendCircleUnreadModel
{
    public static $table = 'cq_friend_circle_unread';

    // 更新指定好友的未读消息数量
    public static function updateByUserId($user_id , array $param = [])
    {
        return Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->update($param);
    }

    // 检查是否存在
    public static function exists($user_id)
    {
        return Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->count();
    }

    // 更新朋友圈未读消息数量
    public static function countForFriendCircle($user_id)
    {
        return Db::table(self::$table)->where('user_id' , $user_id)
            ->inc('count_for_friend_circle')
            ->update();
    }

    // 更新评论未读消息数量
    public static function countForComment($user_id)
    {
        return Db::table(self::$table)->where('user_id' , $user_id)
            ->inc('count_for_comment')
            ->update();
    }

    // 设置朋友圈未读消息数量为 0
    public static function setCountForFriendCircle($user_id , $count = 0)
    {
        return Db::table(self::$table)->where('user_id' , $user_id)
            ->update([
                'count_for_friend_circle' => $count
            ]);
    }

    // 设置朋友圈未读消息数量为 0
    public static function setCountForComment($user_id , $count = 0)
    {
        return Db::table(self::$table)->where('user_id' , $user_id)
            ->update([
                'count_for_comment' => $count
            ]);
    }

    public static function findByUserId($user_id)
    {
        return Db::table(self::$table)->where('user_id' , $user_id)->find();
    }

    public static function insert($user_id)
    {
        return Db::table(self::$table)->insertGetId([
            'user_id' => $user_id
        ]);
    }

}