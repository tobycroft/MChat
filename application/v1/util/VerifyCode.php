<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/7
 * Time: 10:43
 */

namespace app\v1\util;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

class VerifyCode
{
    public static $salt = 'abcfsdkfjkdsfkfjkldsfsdakf';
    public static function make($len = 4 , $str = '')
    {
        $phraseBuilder = new PhraseBuilder($len, $str);
        $captcha = new CaptchaBuilder(null, $phraseBuilder);
        $image = $captcha->build()->get();
        $image = base64_encode($image);
        $image = sprintf('data:image/jpg;base64,%s' , $image);
        $code = $captcha->getPhrase();
        $key = self::key($code);
        return [
            'image' => $image ,
            'key'   => $key
        ];
    }

    public static function check($code , $key)
    {
        if (self::key($code) == $key) {
            return true;
        }
        return false;
    }

    public static function key($code = '')
    {
        return md5($code . self::$salt);
    }
}