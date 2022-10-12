<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:25
 */

namespace app\v1\model;

use think\Db;

class ArticleModel
{
    public static $table = 'cq_article';

    // 获取数据
    public static function api_select_byType($article_type_id , $page , $limit)
    {
        return Db::table(self::$table)
            ->where('article_type_id' , $article_type_id)
            ->page($page)
            ->limit($limit)
            ->order('weight' , 'desc')
            ->order('create_time' , 'desc')
            ->select();
    }

    // 文章数据
    public static function api_find_byId($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->find();
    }
}