<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/7/31
 * Time: 11:23
 */

namespace app\v1\model;


use think\Db;

class TimerLogModel
{
    protected static $table = 'cq_timer_log';

    public static function api_insert(string $log)
    {
        return Db::table(self::$table)
            ->insertGetId([
                'log' => $log
            ]);
    }

    public static function api_update($id , array $data)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->update($data);
    }
}