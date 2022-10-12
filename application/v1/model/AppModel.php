<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:33
 */

namespace app\v1\model;


use think\Db;

class AppModel
{
    public static $table = 'cq_app';

    // 获取数据
    public static function api_select($name , $page , $limit)
    {
        return Db::table(self::$table)
            ->where('name' , 'like' , "%{$name}%")
            ->order('weight' , 'desc')
            ->order('create_time' , 'desc')
            ->page($page)
            ->limit($limit)
            ->select();
    }
}