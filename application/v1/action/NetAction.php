<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/11
 * Time: 16:28
 */

namespace app\v1\action;


use Net;

class NetAction
{
    // 获取资讯列表
    public static function app_list($type)
    {
        $news = config('app.secret.news');
        $data = [
            'key' => $news['key'] ,
            'type' => $type
        ];
        $res = Net::post_data($news['url'] , $data);
        $res = json_decode($res , true);
        if ($res == false) {
            return [
                'code' => 500 ,
                'data' => '调用资讯接口失败！请检查第三方接口是否正常'
            ];
        }
        if ($res['error_code'] != 0) {
            return [
                'code' => 500 ,
                'data' => $res['reason']
            ];
        }
        return [
            'code' => 0 ,
            'data' => $res['result']['data']
        ];
    }
}