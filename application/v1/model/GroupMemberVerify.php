<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/5
 * Time: 10:24
 */

namespace app\v1\model;

use think\Db;

class GroupMemberVerify
{
    public static $table = 'cq_group_member_verify';

    public static function api_insert($request_list_id , $uid)
    {
        return Db::table(self::$table)->insertGetId([
            'request_list_id' => $request_list_id ,
            'uid' => $uid
        ]);
    }

    public static function api_select($request_list_id)
    {
        return Db::table(self::$table)
            ->where('request_list_id' , $request_list_id)
            ->select();
    }
}