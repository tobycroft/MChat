<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2017/12/25
 * Time: 10:24
 */

/*
  * 字符串截取函数
  * @param   string   $str
  * @param   int      $s_idx
  * @param   int      $e_idx
  * @return  string
*/
function mb_substring($str = '' , $s_idx = 0 , $e_idx = 0){
    if ($e_idx === 0) {
        return mb_substr($str , $s_idx , mb_strlen($str));
    }

    // 截取从开始位置到最后的位置
    $str_start = mb_substr($str , $s_idx);

    // 截取从结尾位置到最后的字符串
    $str_end = mb_substr($str , $e_idx);

    // 截取从开始位置 到 结尾位置的字符串
    $result = mb_substr($str_start , 0 , mb_strlen($str_start) - mb_strlen($str_end));

    return $result;
}