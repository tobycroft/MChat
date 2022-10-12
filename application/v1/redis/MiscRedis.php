<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/8
 * Time: 11:58
 */

namespace app\v1\redis;


class MiscRedis extends BaseRedis
{
    // 币类型
    public static $coinType = '__coin_type__';

    // 获取币种
    public static function getCoinType()
    {
        $res = redis()->native('get' , self::$coinType);
        return is_bool($res) ? $res : json_decode($res , true);
    }

    // 设置币种
    public static function setCoinType($value)
    {
        $json = json_encode($value);
        return redis()->native('set' , self::$coinType , $json , cache_duration());
    }
}