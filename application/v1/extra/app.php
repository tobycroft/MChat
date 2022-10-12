<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/2/15
 * Time: 16:00
 *
 * 引导文件，载入此文件就可以享受当前目录的所有功能！
 */

// 引入基础函数
require_once __DIR__ . '/core/Lib/Autoload.php';

use Core\Lib\Autoload;

// 注册类的自动加载
$autoload = new Autoload();
$autoload->register([
    'class' => [
        // 注册自动加载
        'Core\\' => __DIR__ . '/core/'
    ] ,

    'file' => [
        // 功能函数
        __DIR__ . '/core/Function/base.php' ,
        __DIR__ . '/core/Function/array.php' ,
        __DIR__ . '/core/Function/string.php' ,
        __DIR__ . '/core/Function/file.php' ,
        __DIR__ . '/core/Function/time.php' ,
        __DIR__ . '/core/Function/url.php' ,
        __DIR__ . '/core/Function/http.php' ,

        // 项目文件
        __DIR__ . '/common/lib.php' ,
        __DIR__ . '/common/tool.php' ,
    ] ,
]);