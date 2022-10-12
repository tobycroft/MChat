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

class FriendCircleInfoModel
{
    public static $table = 'cq_friend_circle_info';

    // 更新数据
    public static function update($user_id , array $data = [])
    {
        return Db::table(self::$table)
                ->where('user_id' , $user_id)
                ->update(array_unit($data , [
                    'background_image' ,
                ]));
    }

    // 检查是否存在记录
    public static function exists($user_id)
    {
        return Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->count();
    }

    // 查找记录
    public static function find($user_id)
    {
        $res = Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->find();
        return $res;
    }

    // 新增
    public static function insertGetId(array $data = [])
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'user_id' ,
                'background_image'
            ]));
    }

    public static function clearCache($user_id)
    {
        $key = BaseRedis::key('friend_circle_info' , $user_id);
        cache($key , null);
    }
}