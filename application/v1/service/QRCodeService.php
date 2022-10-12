<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/1
 * Time: 16:57
 */

namespace app\v1\service;


class QRCodeService
{
    public static function register()
    {
        require_once __DIR__ . '/../plugin/qrcode/vendor/autoload.php';
    }
}