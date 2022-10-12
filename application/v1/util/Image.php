<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/9
 * Time: 14:09
 */

namespace app\v1\util;

use Core\Lib\Image as ImageLib;

class Image
{
    // 实例
    public static $instance = null;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            $dir = config('app.image_dir');
            self::$instance = new ImageLib($dir);
        }
        return self::$instance;
    }

    // 图片处理
    public static function single(string $image = '')
    {
        $instance = self::getInstance();
        $path = call_user_func([$instance , 'single'] , $image , [
            'mode' => 'fix-width' ,
            'width' => 600
        ] , false);
        return image_path($path);
    }

    // 图片处理
    public static function multiple(array $image = [])
    {
        $instance = self::getInstance();
        $res = call_user_func([$instance , 'multiple'] , $image , [
            'mode' => 'fix-width' ,
            'width' => 600
        ] , false);
        foreach ($res as &$v)
        {
            $v = image_path($v);
        }
        return $res;
    }


}