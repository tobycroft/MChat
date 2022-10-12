<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/9/5
 * Time: 16:59
 */

namespace app\v1\model;


class SensitiveWordModel extends Model
{
    protected $table = 'cq_sensitive_word';

    public static function isForbidden($val = '')
    {
        return (bool) (self::where('str' , 'like' , "%{$val}%")
            ->count());
    }

    public static function list(array $filter = [] , array $order = [] , int $limit = 20)
    {
        $filter['id'] = $filter['id'] ?? '';
        $order['field'] = $filter['field'] ?? 'id';
        $order['value'] = $filter['value'] ?? 'desc';
        $where = [];
        if ($filter['id'] != '') {
            $where[] = ['id' , '=' , $filter['id']];
        }
        $res = self::where($where)
            ->order($order['field'] , $order['value'])
            ->order('id' , 'asc')
            ->paginate($limit);
        $res = convert_obj($res);
        foreach ($res->data as $v)
        {
            self::single($v);
        }
        return $res;
    }

}