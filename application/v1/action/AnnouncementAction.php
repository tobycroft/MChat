<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/15
 * Time: 20:16
 */

namespace app\v1\action;


use app\v1\model\AnnouncementModel;

class AnnouncementAction
{
    public static function app_list($pos)
    {
        $limit = config('app.limit');
        $res = AnnouncementModel::api_select_byPos($pos , $limit);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    public static function app_detail($id)
    {
        $res = AnnouncementModel::api_find($id);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}