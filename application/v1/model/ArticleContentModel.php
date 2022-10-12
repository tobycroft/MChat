<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 15:00
 */

namespace app\v1\model;


use think\Db;

class ArticleContentModel
{
    public static $table = 'cq_article_content';

    // 获取文章内容
    public static function api_find_byArticleId($article_id)
    {
        return Db::table(self::$table)
            ->where('article_id' , $article_id)
            ->find();
    }
}