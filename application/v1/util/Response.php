<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/5/5
 * Time: 15:30
 */

namespace app\v1\util;


class Response
{
    // 成功
    public static function success($data = '' , $code = 0){
        return [
            'code' => $code ,
            'data' => $data
        ];
    }

    // 失败
    public static function error($data = '' , $code = 400)
    {
        return [
            'code' => $code ,
            'data' => $data ,
        ];
    }

    // websocket 失败
    public static function wsError($code , $data)
    {
        $data = is_object($data) ? json_encode($data) : $data;
        $data = 'swoole server error: ' . $data;
        return [
            'code' => $code ,
            'data' =>  $data,
        ];
    }
}