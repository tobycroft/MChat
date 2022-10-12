<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2017/11/26
 * Time: 23:05
 *
 * 类自动加载
 */
namespace Core\Lib;

class Autoload
{
    // 配置文件名称
    protected $suffix = '.php';

    function __construct(){

    }
    
    // 类自动加载
    public function classLoader($namespace , $dir)
    {
        spl_autoload_register(function($class) use($namespace , $dir){
            $class = preg_replace("/(\\\\)?{$namespace}\/" , '' , $class);
            $file  = str_replace('\\' , '/' , $dir . $class);
            $file .= $this->suffix;
            if (file_exists($file) && !is_dir($file)) {
                require_once $file;
            }
        });
    }

    // 文件载入
    public function fileLoader($file)
    {
        if (file_exists($file) && !is_dir($file)) {
            require_once str_replace('\\' , '/' , $file);
        }
    }

    /**
     * @param array $register
     * 格式如下：
     * [
     *      'file' => [
     *          'one.php' ,
     *      ] ,
     *      'class' => [
     *          'namespace' => 'path' ,
     *      ]
     * ]
     */
    public function register($register)
    {
        foreach ($register as $k => $v)
        {
            if ($k === 'class') {
                foreach ($v as $k1 => $v1)
                {
                    $this->classLoader($k1 , $v1);
                }
            } else if ($k === 'file') {
                foreach ($v as $v1)
                {
                    $this->fileLoader($v1);
                }
            } else {
                // 预留...
            }
        }
    }
}