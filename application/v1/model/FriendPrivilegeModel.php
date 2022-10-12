<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:39
 */

namespace app\v1\model;

use app\v1\redis\BaseRedis;
use think\Db;

class FriendPrivilegeModel
{
    public static $table = 'cq_friend_privilege';

    public static function find($id)
    {
        $res = Db::table(self::$table)
            ->where('id' , $id)
            ->find();
        return $res;
    }

    // 设置朋友圈权限
    public static function setPriv($user_id , $friend_id , $field , $value)
    {
        self::clearCache($user_id);
        return Db::table(self::$table)
            ->where([
                ['user_id' , '=' , $user_id] ,
                ['friend_id' , '=' , $friend_id]
            ])
            ->update([
                $field => $value
            ]);
    }

    // 新增
    public static function insertGetId(array $data = [])
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'user_id' ,
                'friend_id' ,
                'hidden' ,
                'shield'
            ]));
    }

    // 检查
    public static function exists($user_id , $friend_id)
    {
        $count = Db::table(self::$table)
            ->where([
                ['user_id' , '=' , $user_id] ,
                ['friend_id' , '=' , $friend_id]
            ])
            ->count();
        return $count > 0;
    }

    // 获取给定好友的朋友圈权限
    public static function findByUserId($user_id , $friend_id)
    {
        $key = BaseRedis::key('friend_privilege' , $user_id);
        $res = cache($key);
        if (empty($res)) {
            $res = Db::table(self::$table)
                ->where([
                    ['user_id' , '=' , $user_id] ,
                    ['friend_id' , '=' , $friend_id] ,
                ])
                ->find();
            cache($key , $res , cache_duration('l'));
        }
        return $res;
    }

    public static function clearCache($user_id)
    {
        $key = BaseRedis::key('friend_privilege' , $user_id);
        cache($key , null);
    }
}