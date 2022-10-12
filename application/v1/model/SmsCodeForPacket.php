<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/7
 * Time: 11:36
 */

namespace app\v1\model;

use think\Db;

class SmsCodeForPacket
{
    public static $table = 'cq_sms_code_for_packet';

    public static function insert($phone , $type , $code , $send_time)
    {
        Db::table(self::$table)
            ->insert([
                'phone' => $phone ,
                'type' => $type ,
                'code' => $code ,
                'send_time' => $send_time ,
            ]);
    }

    public static function updateUsedById($id)
    {
        Db::table(self::$table)->where('id' , $id)->update([
            'used' => 'y'
        ]);
    }

    public static function updateUsedByPhone($phone , $type)
    {
        Db::table(self::$table)->where([
            ['phone' , '=' , $phone] ,
            ['type' , '=' , $type] ,
        ])->update([
            'used' => 'y'
        ]);
    }

    public static function updateByPhone($phone , $type , $code , $send_time)
    {
        Db::table(self::$table)->where([
            ['phone' , '=' , $phone] ,
            ['type' , '=' , $type] ,
        ])->update([
            'code' => $code ,
            'send_time' => $send_time
        ]);
    }

    public static function findByPhone($phone , $type)
    {
        return Db::table(self::$table)->where([
            ['phone' , '=' , $phone] ,
            ['type' , '=' , $type] ,
        ])->find();
    }

    public static function dayCountForInternational($area_code = '' , $phone = '')
    {
        return Db::table(self::$table)
            ->where([
                ['area_code' , '=' , $area_code] ,
                ['phone' , '=' , $phone] ,
            ])
            ->whereRaw(sprintf('date_format(create_time , "%%Y-%%m-%%d") = "%s"' , date('Y-m-d')))
            ->count();
    }
}