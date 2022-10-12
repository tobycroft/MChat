<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/22
 * Time: 16:57
 */

namespace app\v1\util;

use Core\Lib\UploadFile;
use Core\Lib\UploadImage;

class File
{
    private $image = null;
    private $file = null;

    function __construct()
    {
        $this->image = new UploadImage(config('app.image_dir'));
        $this->file = new UploadFile(config('app.file_dir'));
    }

    // 保存图片
    public function image($image)
    {
        if (!UploadImage::isImage($image)) {
            return $this->response('不支持的文件类型，请上传图片' , 400);
        }
        $res = $this->image->save($image);
        $res['path']    = image_path($res['path']);
        $res['url']     = $res['path'];
        return $this->response($res);
    }

    // 响应
    public function response($data , $code = 200)
    {
        return compact('data' , 'code');
    }
}