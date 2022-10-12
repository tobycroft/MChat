<?php
namespace Core\Lib;

use Exception;

/**
 * @author grayVTouch
 */

class UploadFile {
    protected $_dir = '';
    function __construct($dir = ''){
        if (!File::isDir($dir)) {
            File::cDir($dir);
        }
        $dir = format_path($dir);
        $this->_dir = $dir;
    }
    // 检查文件上传模式
    public static function isMultiple($files){
        return is_array($files['tmp_name']);
    }
    // 格式化 $_FILES 数组
    public static function format(array $files = []){
        if (empty($files)) {
            return false;
        }
        $result = [];
        if (!self::isMultiple($files)) {
            $result[] = $files;
        } else {
            $len = count($files['tmp_name']);

            // 无上传文件！
            // 这是一种常见的上传文件缺失现象
            if ($len === 1 && empty($files['tmp_name'][0])) {
                return false;
            }
            for ($i = 0; $i < $len; ++$i)
            {
                $file			  = [];
                $file['name']	  = $files['name'][$i];
                $file['size']	  = $files['size'][$i];
                $file['type']     = $files['type'][$i];
                $file['tmp_name'] = $files['tmp_name'][$i];
                $file['error']    = $files['error'][$i];
                $result[]		  = $file;
            }
        }
        return $result;
    }
    private static function _checkError($err_level = 0){
        switch ($err_level)
        {
            case 0:
                return true;
            case 1:
                throw new Exception('单个上传文件超过最大限制，单个上传文件最大允许：' . ini_get('upload_max_filesize'));
            case 2:
                throw new Exception('上传文件总大小超过PHP post_max_size 限制，上传文件总大小最大允许： ' . ini_get('post_max_size'));
            case 3:
                throw new Exception('部分文件上传');
            case 4:
                throw new Exception('上传文件丢失');
            case 5:
                throw new Exception('临时文件夹丢失');
            case 6:
                throw new Exception('写入到临时文件夹错误');
            default:
                throw new Exception('Unkown Upload File Error，Error Level：' . $err_level);
        }
    }
    /*
     * 检查是否存在上传文件
     */
    public static function emptyFile(array $file = []){
        if (empty($file)) {
            return true;
        }
        if (empty($file['tmp_name'])) {
            return true;
        }
        return false;
    }
    /*
     * 保存单个上传文件
     * @param  Array    待上传的文件集合
     * @param  Boolean  是否保存源文件名称
     * @param  Boolean  是否在返回的 url 中添加网站域名
     */
    public function save(array $file = [] , $save_origin = false){
        if (empty($file)) {
            return false;
        }
        $save_origin = is_bool($save_origin) ? $save_origin : false;
        self::_checkError($file['error']);
        $fname     = get_filename($file['name']);
        $extension = get_extension($file['name']);
        if (!$save_origin) {
            $fname = 'upload-' . date('YmdHis-' , time()) . '-' . md5_file($file['tmp_name']) . '.' . $extension;
        }
        // 根据日期创建文件夹，对文件进行分类
        $date   = date('Y-m-d' , time());
        $dir    = $this->_dir . '/' . $date;

        if (!File::isDir($dir)) {
            File::cDir($dir);
        }

        $path = $dir . '/' . $fname;
        $path = gbk($path);
        if (!move_uploaded_file($file['tmp_name'] , $path)) {
            return false;
        }
        // 该函数仅在 php version >= 7 的情况下方正确
        $path = realpath($path);
        // return utf8($path);
        return [
            'name' => $fname ,
            'size' => $file['size'] ,
            'mime' => $file['type'] ,
            'path' => $path
        ];
    }
    public function saveAll(array $files = [] , $save_origin = false){
        $files = self::format($files);

        // 如果没有上传文件时
        if ($files === false) {
            return false;
        }
        $success    = [];
        $error      = [];
        foreach ($files as $v)
        {
            $file = $this->save($v , $save_origin);
            if ($file === false) {
                $error[] = $file;
                continue ;
            }
            $success[] = $file;
        }
        return [
            'success' => $success ,
            'error'   => $error
        ];
    }
}