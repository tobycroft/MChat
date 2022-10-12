<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:59
 */

namespace app\v1\model;


use app\v1\redis\BaseRedis;
use think\Db;

class FriendCircleMediaModel
{
    public static $table = 'cq_friend_circle_media';

    // 设置媒体所属的朋友圈
    public static function set_friend_circle_id(array $id_list = [] , array $data = [])
    {
        self::clearCache($data['friend_circle_id']);
        return Db::table(self::$table)
                ->whereIn('id' , $id_list)
                ->update(array_unit($data , [
                    'friend_circle_id' ,
                ]));
    }

    public static function insertGetId($data)
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'type' ,
                'friend_circle_id' ,
                'name' ,
                'mime' ,
                'size' ,
                'path' ,
                'url' ,
                'thumb' ,
            ]));
    }

    // 获取
    public static function get($friend_circle_id)
    {
        $key = BaseRedis::key('friend_circle_media' , $friend_circle_id);
        $res = cache($key);
        if (empty($res)) {
            $res = Db::table(self::$table)
                ->where('friend_circle_id' , $friend_circle_id)
                ->select();;
            cache($key , $res , cache_duration('l'));
        }
        return $res;
    }

    // 删除指定朋友圈下的所有 媒体记录
    public static function deleteByFriendCircleId($friend_circle_id)
    {
        self::clearCache($friend_circle_id);
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->delete();
    }

    // 删除
    public static function delete($id)
    {
        $friend_circle = FriendCircleModel::find($id);
        if (!empty($friend_circle)) {
            self::clearCache($friend_circle['id']);
        }
        return Db::table(self::$table)
            ->where('id' , $id)
            ->delete();
    }

    public static function clearCache($friend_circle_id)
    {
        $key = BaseRedis::key('friend_circle_media' , $friend_circle_id);
        cache($key , null);
    }
}
