<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/8/21
 * Time: 9:49
 */

namespace app\v1\model;


class ChatBlackListNewModel extends Model
{
    protected $table = 'cq_chat_blacklist';

    public static function userInBlack(array $filter = [] , array $order = [] , int $limit = 20)
    {
        $filter['uid'] = $filter['uid'] ?? '';
        $where = [];
        $order['field'] = $order['field'] ?? 'id';
        $order['value'] = $order['value'] ?? 'desc';

        if ($filter['uid'] != '') {
            $where['uid'] = $filter['uid'];
        }
        $res = ChatBlacklistNewModel::where($where)
            ->order($order['field'] , $order['value'])
            ->paginate($limit);
        $res = convert_obj($res);
        foreach ($res->data as $v)
        {
            self::single($v);
        }
        return $res;

    }
}