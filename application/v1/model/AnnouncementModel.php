<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:33
 */

namespace app\v1\model;


use think\Db;

class AnnouncementModel
{
    public static $table = 'cq_announcement';

    // 获取数据
    public static function api_select_byPos($pos , $limit)
    {
        return Db::table(self::$table)
            ->where('pos' , $pos)
            ->order('weight' , 'desc')
            ->order('create_time' , 'desc')
            ->field('id,title,text,weight,pos,create_time')
            ->limit($limit)
            ->select();
    }

    public static function api_find($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->find();
    }
}