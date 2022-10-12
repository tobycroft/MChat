<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:13
 */

namespace app\v1\model;


use think\Db;

class ImageModel
{
    public static $table = 'cq_image';

    public static function api_list($platform_id , $pos , $limit = 5)
    {
        return Db::table(self::$table)
            ->where([
                ['platform_id' , '=' , $platform_id] ,
                ['pos' , '=' , $pos] ,
            ])
            ->order('weight' , 'desc')
            ->select();
    }
}