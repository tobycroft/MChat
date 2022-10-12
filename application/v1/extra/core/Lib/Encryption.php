<?php

/*
 * author grayVTouch 2016-10-10
 * 加密 || 解密
 */

namespace Core\Lib;

class Encryption {
	protected static $_pwdFile   = '';


	// 检查密码对照文件是否存在！
	private static function _checkPwdFile($pwd_file = ''){
		$pwd_file = empty($pwd_file) ? __DIR__ . '/_passList.sec' : $pwd_file;

		if (!File::isFile($pwd_file)) {
			File::cFile($pwd_file);
		}

		self::$_pwdFile = $pwd_file;
	}

	// 检查是否存在
	public static function exists($val = ''){
		self::_checkPwdFile();

		$f = fopen(self::$_pwdFile , 'r');

		if (!$f) {
			throw new \Exception('无法打开密码对照文件：' . self::$_pwdFile);
		}

		while ($line = fgets($f)) 
        {
            $line   = trim($line);
            $res    = json_decode($line , true);

            if (array_search($val , $res) !== false) {
                return true;
            }
        }

		return false;
	}

	// 检索
    public static function search($val = ''){
        self::_checkPwdFile();

        $f = fopen(self::$_pwdFile , 'r');

        if (!$f) {
            throw new \Exception('无法打开密码对照文件：' . self::$_pwdFile);
        }

        while ($line = fgets($f))
        {
            $line   = trim($line);
            $res    = json_decode($line , true);

            if (($decrypt = array_search($val , $res)) !== false) {
                return $decrypt;
            }
        }

        return false;
    }

	/* 
	 * 加密
	 * @param  Mixed  $val  待加密的字符串
	 * @param  Mixed  $halt 盐值（增加解密难度用）
	 * @return 加密后的字符串
	 */
	public static function encrypt($val = '' , $halt = 'cxl_'){
		$key = $val;
		$val = md5(md5($key) . $halt);

		if (!self::exists($val)) {
			File::wData(self::$_pwdFile , json_encode([$key => $val]) . "\r\n");
		}

		return $val;
	}

	/* 
	 * 解密
	 * @param  Mixed  $val  待解密的字符串
	 * @return 解密后的字符串
	 */
	public static function decrypt($val = ''){
		return self::search($val);
	}

}