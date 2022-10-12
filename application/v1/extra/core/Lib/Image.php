<?php

namespace Core\Lib;

use Exception;

class Image {
    private $dir = '';

    private $memoryLimit = null;
    private $timeLimit = null;

    function __construct($dir = '')
    {
        if (!File::isDir($dir)) {
            throw new Exception('目录不存在');
        }
        $this->dir = format_path($dir) . '/';
    }

    // 提升性能
    private function powerUp()
    {
        $this->memoryLimit  = ini_get('memory_limit');
        $this->timeLimit    = ini_get('max_execution_time');
        ini_set('memory_limit' , '2048M');
        set_time_limit(0);
    }

    // 性能配置恢复
    private function powerReset()
    {
        ini_set('memory_limit' , $this->memoryLimit);
        set_time_limit($this->timeLimit);
    }

    /*
     * 图片处理函数（单个 || 多个）
     * @param  Mixed    $image					待处理图片路径
     * @param  Array    $option						图片处理设置
        $option = array(
            'width'     => 300 ,	// 处理后图片宽度
            'height'    => 300 ,	// 处理后图片高度
            'extension' => 'jpg'	// 处理后图片保存格式
        );
     * @param  Boolean  $save_original_name		是否保留原名
     * @param  Boolean  $save_original_file		是否保留源文件
     * @param  Boolean  $is_add_domain				是否在返回的 url 中添加域名
     * @param  Mixed
     */
    public function single(string $image = '' , array $option = [] , bool $base64 = true , bool $save_original_name = true){
        if (!File::isFile($image)) {
            throw new Exception('未找到对应文件');
        }
        $default = [
            //              固定比例缩放
            // fix          固定尺寸缩放
            // ratio        按比例缩放
            // fix-width    固定宽度，高度按比例缩放
            // fix-height   固定高度，宽度按比例缩放
            'mode'      => 'ratio' ,
            'ratio'     => 0.5 ,
            'width'     => 300 ,	// 处理后图片宽度
            'height'    => 300 ,	// 处理后图片高度
        ];
        $mode_range = ['ratio' , 'fix' , 'fix-width' , 'fix-height'];
        $mode   = isset($option['mode'])    ? $option['mode'] : $default['mode'];
        $w      = isset($option['width'])   ? intval($option['width']) : $default['width'];
        $h      = isset($option['height'])  ? intval($option['height']) : $default['height'];
        $ratio  = isset($option['ratio'])   ? floatval($option['ratio']) : $default['ratio'];
        if (!in_array($mode , $mode_range)) {
            throw new Exception('不支持的 mode');
        }
        $info = get_image_info($image);
        if ($mode == 'ratio') {
            // 按比例缩放
            $w = $info['width'] * $ratio;
            $h = $info['height'] * $ratio;
        } else if ($mode == 'fix-width') {
            // 固定宽度
            $h = ceil($info['height'] * $w / $info['width']);
        } else if ($mode == 'fix-height') {
            // 固定高度
            $w = ceil($info['width'] * $h / $info['height']);
        } else {
            // 预留
        }
        // 提高脚本性能
        $this->powerUp();
        $type_range = ['gif' , 'jpg' , 'png'];
        if (!in_array($info['extension'] , $type_range)) {
            return new Exception('不支持的文件类型');
        }
        switch ($info['extension'])
        {
            case 'gif':
                $img = imagecreatefromgif($image);
                break;
            case 'jpg':
                $img = imagecreatefromjpeg($image);
                break;
            case 'png':
                $img = imagecreatefrompng($image);
                break;
        }

        $cav = imagecreatetruecolor($w , $h);
        // 平滑缩小到指定大小
        imagecopyresampled($cav , $img , 0 , 0 , 0 , 0 , $w , $h , $info['width'] , $info['height']);
        if (!$save_original_name) {
            $info['filename'] = date('Y-m-d H-i-s' , time()) . md5_file($image) . '.' . $info['extension'];
        }
        $file = $this->dir . $info['filename'];
        // 同名文件处理：删除
        File::dFile($file);
        $file = gbk($file);
        switch ($info['extension'])
        {
            case 'gif':
                $save = imagegif($cav , $file);
                break;
            case 'jpg':
                $save = imagejpeg($cav , $file);
                break;
            case 'png':
                $save = imagepng($cav , $file);
                break;
        }
        if (!$save) {
            throw new Exception('保存图像失败');
        }
        $file = realpath($file);
        if ($base64) {
            // 返回 base64 字符串
            $this->powerReset();
            $content = file_get_contents($file);
            // 删除保存的文件
//            File::dFile($file);
            switch ($info['extension'])
            {
                case 'gif':
                    return sprintf('%s%s' , 'data:image/gif;base64,' , base64_encode($content));
                case 'jpg':
                    return sprintf('%s%s' , 'data:image/jpeg;base64,' , base64_encode($content));
                case 'png':
                    return sprintf('%s%s' , 'data:image/png;base64,' , base64_encode($content));
            }
        }
        $image = utf8($file);
        $this->powerReset();
        return $image;
    }

    public function multiple(array $images = [] , array $option = [] , bool $base64 = true , bool $save_original_name = true)
    {
        $res = [];
        foreach ($images as $v)
        {
            $res[] = $this->single($v , $option , $base64 , $save_original_name);
        }
        return $res;
    }
}