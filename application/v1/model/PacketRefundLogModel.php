<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/7/31
 * Time: 11:23
 */

namespace app\v1\model;


use think\Db;

class PacketRefundLogModel
{
    protected static $table = 'cq_packet_refund_log';

    public static function api_insert(string $log)
    {
        return Db::table(self::$table)
            ->insertGetId([
                'log' => $log
            ]);
    }
}