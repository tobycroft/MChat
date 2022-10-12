<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/12
 * Time: 14:12
 */

namespace app\v1\action;


use app\v1\model\ImageModel;

class ImageAction
{
    public static function app_list($platform_id , $pos , $limit = 5)
    {
        $res = ImageModel::api_list($platform_id , $pos , $limit);
        foreach ($res as &$v)
        {
            $v['url'] = image_url($v['url']);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}