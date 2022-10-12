<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/23
 * Time: 9:31
 */

namespace app\v1\redis;


class BaseRedis
{

    // 生成 redis 键名：名称 + 标识符（可选）
    public static function key($name , $id = '')
    {
        return sprintf('%s%s__%s__%s' , config('cache.prefix') , 'api' , $name , $id);
    }

    // 删除 redis 键名
    public static function del($key = '')
    {
        return redis()->native('del' , $key);
    }
}