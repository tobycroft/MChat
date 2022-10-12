<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/6
 * Time: 17:23
 */

namespace app\v1\model;

use think\Db;

class GroupDisturb
{
    public static $table = 'cq_group_disturb';

    // 新增
    public static function api_insert($gid , $uid)
    {
        return Db::table(self::$table)
            ->insertGetId([
                'gid' => $gid ,
                'uid' => $uid
            ]);
    }

    // 删除
    public static function api_delete($gid , $uid)
    {
        return Db::table(self::$table)
            ->where([
                'gid' => $gid ,
                'uid' => $uid
            ])
            ->delete();
    }

    // 计数
    public static function api_count($gid , $uid)
    {
        return Db::table(self::$table)
            ->where([
                ['gid' , '=' , $gid] ,
                ['uid' , '=' , $uid]
            ])
            ->count();
    }
}