<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/7/21
 * Time: 16:33
 */

namespace extra;

use Exception;

// 字符串长度验证
function check_len($str , $len , $sign = 'eq'){
    $range = ['gt' , 'gte' , 'lt' , 'lte' , 'eq'];
    $sign = in_array($sign , $range) ? $sign : 'eq';
    $str_len = mb_strlen($str);

    switch ($sign)
    {
        case 'gt':
            return $str_len > $len;
        case 'gte':
            return $str_len >= $len;
        case 'lt':
            return $str_len < $len;
        case 'lte':
            return $str_len <= $len;
        case 'eq':
            return $str_len = $len;
        default:
            throw new Exception('不支持的比较符类型');
    }
}

// 检查手机号码
function check_phone($phone){
    return (bool) (preg_match('/^[1][3-8]\d{9}$/u' , $phone) || preg_match('/^\d+\-\d+(\-[0-9\-]+)?$/' , $phone));
}

// 检查价格
function check_price($price){
    return (bool) preg_match('/^[1-9]?\d*(\.\d{0,2})?$/' , $price);
}

// 检查年份
function check_year($year){
    $reg = '/^\d{4}$/';

    return (bool) preg_match($reg , $year);
}

// 检查日期格式
function check_date($date){
    $reg = '/^\d{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2]\d|3[0-1])$/';

    return (bool) preg_match($reg , $date);
}

// 检查数字
function check_num($num , $len = 0){
    if ($len === 0) {
        return (bool) preg_match("/^\d+$/" , $num);
    }

    $reg = "/^\d+(\.\d{0,{$len}})?$/";

    return (bool) preg_match($reg , $num);
}

// 检查密码
function check_password($password){
    $reg = "/^.{6,}$/";
    return (bool) preg_match($reg , $password);
}

// 检查电子邮箱
function check_email($mail){
    $reg = "/^\.+@\.+$/";

    return (bool) preg_match($reg , $mail);
}

// 正则验证
function regexp_check(string $reg = '' , string $str = '')
{
    $reg = addslashes($reg);
    $reg = addcslashes($reg , '/[]()-');
    return (bool) preg_match("/{$reg}/" , $str);
}

// 获取给定数组中给定键名对应单元
function array_unit(array $arr = [] , array $keys = [])
{
    $res = [];
    foreach ($keys as $v)
    {
        if (!isset($arr[$v])) {
            continue ;
        }
        $res[$v] = $arr[$v];
    }
    return $res;
}