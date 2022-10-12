<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/6
 * Time: 9:56
 */

namespace app\v1\util;


use app\v1\service\AuthService;
use app\v1\redis\MiscRedis;

class Misc
{
    // 获取币种类型
    public static function coinType($coin_id = 0)
    {
        $res = MiscRedis::getCoinType();
        if ($res == false) {
            $res = AuthService::serv_get_balance(1);
            foreach ($res as &$v)
            {
                unset($v['balance']);
            }
            MiscRedis::setCoinType($res);
        }
        if (!empty($coin_id)) {
            foreach ($res as $v)
            {
                if ($v['coin_id'] == $coin_id) {
                    return $v;
                }
            }
            return false;
        }
        return $res;
    }
}
