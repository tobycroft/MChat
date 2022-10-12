<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:22
 */

namespace app\v1\action;


use app\v1\model\ArticleContentModel;
use app\v1\model\ArticleModel;
use app\v1\model\ArticleTypeModel;

class ArticleAction
{
    // 资讯列表
    public static function app_list($article_type_id , $page)
    {
        $limit = config('app.limit');
        $res = ArticleModel::api_select_byType($article_type_id , $page , $limit);
        foreach ($res as &$v)
        {
            $v['thumb'] = image_url($v['thumb']);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 文章详情
    public static function app_find($article_id)
    {
        $article = ArticleModel::api_find_byId($article_id);
        $article['thumb'] = image_url($article['thumb']);
        if (empty($article)) {
            return [
                'code' => 404 ,
                'data' => '未找到给定文章'
            ];
        }
        $article['content'] = ArticleContentModel::api_find_byArticleId($article['id']);
        $article['article_type'] = ArticleTypeModel::api_find_byId($article['article_type_id']);
        return [
            'code' => 0 ,
            'data' => $article
        ];
    }
}