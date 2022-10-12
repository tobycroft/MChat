<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:04
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\ImageAction;

class Image extends LoginController
{
    // app-资讯图片轮播图
    public function news()
    {
        // app
        $platform_id = 4;
        // pos
        $pos = 'news';
        $res = ImageAction::app_list($platform_id , $pos);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // app-应用图片轮播图
    public function app()
    {
        // app
        $platform_id = 4;
        // pos
        $pos = 'app';
        $res = ImageAction::app_list($platform_id , $pos);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
}