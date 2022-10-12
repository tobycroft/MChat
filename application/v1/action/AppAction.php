<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:34
 */

namespace app\v1\action;


use app\v1\model\AppModel;

class AppAction
{
    public static function app_list($name , $page)
    {
        $limit = config('app.limit');
        $res = AppModel::api_select($name , $page , $limit);
        foreach ($res as &$v)
        {
            $v['thumb'] = image_url($v['thumb']);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}