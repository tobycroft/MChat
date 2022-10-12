<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:39
 */

namespace app\v1\model;

use think\Db;

class FriendCircleCommentModel
{
    public static $table = 'cq_friend_circle_comment';

    // 新增
    public static function insertGetId(array $data = [])
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'user_id' ,
                'friend_circle_id' ,
                'content' ,
                'p_id'
            ]));
    }

    // 查询
    public static function find($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->find();
    }

    // 删除指定评论
    public static function delete($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->delete();
    }

    // 获取给定朋友圈评论列表
    public static function getByFriendCircleId($friend_circle_id)
    {
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->order('create_time' , 'desc')
            ->order('id' , 'desc')
            ->select();
    }

    // 删除指定朋友圈下的所有评论
    public static function deleteByFriendCircleId($friend_circle_id)
    {
        return Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->delete();
    }

    // 获取某条朋友圈下所有评论用户
    public static function userIdByFriendCircleId($friend_circle_id)
    {
        $id_list = [];
        $res =  Db::table(self::$table)
            ->where('friend_circle_id' , $friend_circle_id)
            ->group('user_id')
            ->select();
        foreach ($res as $v)
        {
            $id_list[] = $v['user_id'];
        }
        return $id_list;
    }
}