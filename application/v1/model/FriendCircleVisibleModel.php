<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:59
 */

namespace app\v1\model;

use think\Db;

class FriendCircleVisibleModel
{
    public static $table = 'cq_friend_circle_visible';

    public static function insertGetId(array $data = [])
    {
        return Db::table(self::$table)
                ->insertGetId(array_unit($data , [
                    'friend_circle_id' ,
                    'user_id' ,
                ]));
    }

    // 检查该用户是否存在
    public static function exists($user_id)
    {
        $res = Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->find();
        return !empty($res);
    }

    // 删除指定朋友圈下的所有 可见用户记录
    public static function deleteByFriendCircleId($friend_circle_id)
    {
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->delete();
    }
}
