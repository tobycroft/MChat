<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/7
 * Time: 10:49
 */

namespace app\api\controller;


use app\common\controller\CommonController;
use app\v1\util\VerifyCode;

class Misc extends CommonController
{
    // 获取验证码
    public function verifyCode()
    {
        $this->succ(VerifyCode::make(4));
    }
}