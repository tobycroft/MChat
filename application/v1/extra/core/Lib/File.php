<?php
/*
 * ****************************
 * author 陈学龙 2016-10-10
 * 文件/目录 操作类
 * ****************************
 */

namespace Core\Lib;

use Exception;

class File {
	private static $_instance = null;

	function __construct(){
	    if (self::$_instance instanceof self) {
            throw new Exception("不允许重复实例化");
        }

        self::getInstance();
    }

    // 获取实例
	public static function getInstance(){
		if (!self::$_instance instanceof self) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	
	/*
	 * 获取文件绝对路径
	 * @param  String $path 文件路径（相对 | 绝对路径）
	 * @return String 
	 */
	public static function getRelPath($path = ''){
		$path = format_path($path);
		$path = gbk($path);
		$path = realpath($path);

		if (!$path) {
			return false;
		}

		return utf8($path);
	}

	/*
	 * 获取路径指向的地址的类型
	 * @param String $file  文件路径
	 * @return String
	 */
	public static function getType($path = ''){
        $path = format_path($path);
        $path = gbk($path);

		if (!file_exists($path)) {
			return false;
		}

		if (is_dir($path)) {
			return 'dir';
		}

		return 'file';
	}

	/*
	 * 检查文件是否存在
	 * @param String $file 文件路径
	 * @return true | false
	 */
	public static function isFile($path = ''){
        $path = format_path($path);
        $path = gbk($path);

		if (!file_exists($path)) {
			return false;
		} 

		if (is_dir($path)) {
			return false;
		}

		return true;
	}

	/*
	 * 检查目录
	 * @param String             $dir       目录路径
	 * @return true | false
	 */
	public static function isDir($path = ''){
        $path = format_path($path);
        $path = gbk($path);

        if (!file_exists($path)) {
            return false;
        }

        if (is_dir($path)) {
			return true;
		}

		return false;
	}

	/*
	 * 创建目录
	 * @param String   $dir          目录路径
	 * @param Integer  $privilege    目录权限
	 * @return true | false
	 */
	public static function cDir($dir = '' , $privilege = 0755){
		if (!self::isDir($dir)) {
			$dir = format_path($dir);
			$dir = gbk($dir);
			$mk  = mkdir($dir , $privilege);

			if (!$mk) {
				$err_msg  = "创建目录失败\n";
				$err_msg .= "待创建的目录路径： " . utf8($dir) . "\n";
				throw new Exception($err_msg);
			}
		}

		return true;
	}

	/*
	 * 直接根据路径创建文件
	 * @param String   $dir          文件路径
	 * @param Integer  $privilege    文件权限
	 * @return true | false
	 */
	public static function cFile($file = '' , $privilege = 0755){
		if (!self::isFile($file)) {
			$file = format_path($file);
			$file = gbk($file);

			$f = fopen($file , 'x');

			if (!$f) {
				$err_msg  = "创建文件失败！" . "\n";
				$err_msg .= "待创建的文件路径： " . utf8($file) . "\n";
				throw new Exception($err_msg);
			}

			if (!chmod($file , $privilege)) {
				$err_msg  = "设置文件权限失败" . "\n";
				$err_msg .= "待设置权限的文件路径： " . utf8($file) . "\n";
				throw new Exception($err_msg);
			}

			fclose($f);
		}
	  
		return true;
	}

	/*
	 * 写入数据
	 * @param	String       $file       待写入数据的文件路径
	 * @param	$content     $contents   待写入的数据
	 * @param   $write_type  $write_type 写入类型
	 */
	public static function write($file = '' , $content = '' , $type = null){
		if (!self::isFile($file)) { 
			self::cFile($file);
		}

		$file = format_path($file);
		$file = gbk($file);
		$range = ['a' , 'w'];
		$type = in_array($type , $range) ? $type : 'a';
		$f = fopen($file , $type);

		if (!$f) {
			$err_msg  = "打开文件失败\n";
			$err_msg .= "待写入的文件路径：" . utf8($file) . "\n";
			throw new Exception($err_msg);
		}

		if (!flock($f , LOCK_EX)) {
            $err_msg  = "文件已被占用\n";
            $err_msg .= "待锁定的文件路径：" . utf8($file) . "\n";
            throw new Exception($err_msg);
		}

        fwrite($f , $content);
        flock($f , LOCK_UN);
        fclose($f);
	}

	// 获取文件 & 目录
	public static function get($dir = '' , $is_recursive = true){
		if (!self::isDir($dir)){
			throw new Exception("参数 1 不是目录： " . $dir);
		}

		$is_recursive = is_bool($is_recursive) ? $is_recursive : true;

        $get = function($dir = '' , array &$result = []) use(&$get , $is_recursive) {
			$dir = format_path($dir);
			$dir = gbk($dir);
			$d   = dir($dir);

			if (!$d) {
				throw new Exception("无法打开当前目录：" . $dir);
			}

			while ($fname = $d->read())
            {
                if ($fname !== '.' && $fname !== '..'){
                    $fname = utf8($dir) . '/' . utf8($fname);

                    if (self::isDir($fname)) {
                        $result['dir'][] = $fname . '/';

                        if ($is_recursive){
                            $get($fname , $result);
                        }
                    } else {
                        $result['file'][] = $fname;
                    }
                }
            }
		};

		$res = [
			'dir'  => [] ,
			'file' => []
		];

        $get($dir , $res);

		return $res;
	}

	// 获取文件
	public static function getFiles($dir = '' , $is_recursive = true){
		$is_recursive = is_bool($is_recursive) ? $is_recursive : true;
		$res = self::get($dir , $is_recursive);
		return $res['file'];
	}

	// 获取目录
	public static function getDirs($dir , $is_recursive = true){
        $is_recursive = is_bool($is_recursive) ? $is_recursive : true;
        $res = self::get($dir, $is_recursive);
        return $res['dir'];
    }

	// 删除单个文件
	public static function dFile($file = ''){
		if (!self::isFile($file)) {
            return ;
		}

        $file = format_path($file);
        $file = gbk($file);

        if (!unlink($file)) {
            throw new Exception('删除文件失败： ' . utf8($file));
        }
	}

	// 删除多个文件
	public static function dFiles(array $files = []){
        foreach ($files as $v)
        {
            self::dFile($v);
        }
	}

	// 删除单个目录
    public static function dDir($dir){
	    if (!self::isDir($dir)) {
	        return ;
        }

        $dir = format_path($dir);
	    $dir = gbk($dir);

        if (!rmdir($dir)) {
            throw new Exception('删除目录失败：' . utf8($dir));
        }
    }

    // 删除多个目录
    public static function dDirs(array $dirs = []){
        foreach ($dirs as $v)
        {
            self::dDir($v);
        }
    }

	final public function __clone(){
		throw new Exception('不允许克隆');
	}

}


