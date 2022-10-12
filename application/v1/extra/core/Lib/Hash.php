<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/5
 * Time: 10:37
 */
namespace Core\Lib;

class Hash
{
    /**
     * @title 生成密码
     * @author by cxl
     * @param $str 待加密的字符串
     * @param $cost 密码的复杂度，默认 10，需根据硬件性能合理设置
     *
     * @return string 加密后的字符串
     */
    public static function make($str , $cost = 10){
        return password_hash($str , PASSWORD_BCRYPT , [
            'cost' => $cost
        ]);
    }
    /**
     * @title 加密字符串
     * @author by cxl
     *
     * @param $str 原始未加密字符串
     * @param $compare 加密后的字符串
     * @return boolean
     */
    public static function check($str , $compare){
        return password_verify($str , $compare);
    }
}