<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/11
 * Time: 16:23
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\AppAction;
use app\v1\action\ArticleAction;

class Article extends LoginController
{
    public function app()
    {
        $page = input('post.page');
        $name = input('post.name');
        $res = AppAction::app_list($name , $page);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 资讯
    public function news()
    {
        $page = input('post.page');
        $article_type_id = 2;
        $res = ArticleAction::app_list($article_type_id , $page);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 媒体
    public function media()
    {
        $page = input('post.page');
        $article_type_id = 1;
        $res = ArticleAction::app_list($article_type_id , $page);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 文章详情
    public function detail()
    {
        $article_id = input('post.article_id');
        $res = ArticleAction::app_find($article_id);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

}