<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/9/2
 * Time: 15:22
 */

namespace app\v1\action;


class Action
{
    public static function success($data = '' , $code = 0)
    {
        return compact('data' , 'code');
    }

    public static function error($data = '' , $code = 400)
    {
        return compact('data' , 'code');
    }
}