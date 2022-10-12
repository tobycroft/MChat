<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/16
 * Time: 10:55
 */

namespace app\v1\redis;


class PackRedis extends BaseRedis
{
    // 红包
    public static $packKey = '__amount_for_group_pack%s';

    // 创建群红包
    public static function createGroupPack($msg_id , $packs = [])
    {
        $key = sprintf(self::$packKey , $msg_id);
        foreach ($packs as $v)
        {
            redis()->native('rPush' , $key , $v);
        }
    }

    // 领取群红包
    public static function receiveGroupPack($msg_id)
    {
        $key = sprintf(self::$packKey , $msg_id);
        $res = redis()->native('lRange' , $key , 0 , -1);
        // 随机领取
        $min = 0;
        $max = max(0 , count($res) - 1);
        $index = mt_rand($min , $max);
        $amount = $res[$index];
        array_splice($res , $index , 1);
        redis()->native('del' , $key);
        self::createGroupPack($msg_id , $res);
        // 领取的金额
        return $amount;
    }
}