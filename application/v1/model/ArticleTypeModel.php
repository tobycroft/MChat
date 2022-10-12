<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 15:02
 */

namespace app\v1\model;


use think\Db;

class ArticleTypeModel
{
    public static $table = 'cq_article_type';

    public static function api_find_byId($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->find();
    }
}