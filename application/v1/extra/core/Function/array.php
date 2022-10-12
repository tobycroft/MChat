<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/1/23
 * Time: 11:34
 */


// 提取数组指定长度的单元
// 适用于 一维数组
function chunk(array $data = [] , $size = 10){
    $res = [];
    $i   = 0;

    for ($i = 0; $i < count($data); ++$i)
    {
        if ($i < $size) {
            $res[] = $data[$i];
        } else {
            break;
        }
    }

    return $res;
}

// StdClass 转换成数组
function obj_to_array($obj){
    return json_decode(json_encode($obj) , true);
}