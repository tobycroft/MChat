<?php

/**
 * author grayVTouch 2017-11-26
 * 缓存类
 */

namespace Core\Lib;

// 前提：开启了 php 缓存。并且显示调用了 ob_start
class Cache {
	// 单位 s
	protected $_duration = 0;
	// 文件
	protected $_cacheFile = '';

    /**
     * Cache constructor.
     * @param $filename
     * @param $cache_dir
     * @param int $duration ，单位：s
     * @throws \Exception
     */
	function __construct($filename , $cache_dir , $duration = 30 * 60){
		if (!File::isDir($cache_dir)) {
            throw new \Exception('缓存目录不存在:' . $cache_dir);
		}
		
		$filename	        = md5($filename) . '.cache';
		$this->_cacheFile  = format_path($cache_dir . $filename);
		$this->_duration    = $duration;
	}

	// 检查缓存文件是否超时
	public function checkOverTime(){
	    // 当前时间 <> 文件修改时间 + 超时时间
		return time() > filemtime($this->_cacheFile) + $this->_duration;
	}

	/*
	 * 检查缓存文件是否存在
	 * 条件1：缓存文件不存在，false
	 * 条件2：缓存文件存在，但是缓存超时，false
	 * 其他：true
	 */
	public function exists(){
		if (!File::isFile($this->_cacheFile)) {
			return false;
		} else {
		    if ($this->checkOverTime()) {
                File::dFile($this->_cacheFile);

                return false;
            }
        }

		return true;
	}

	/*
	 * 缓存
	 * @param callable $get 缓存文件失效时，获取最新数据的回调
	 * @return 返回缓存数据
	 */
	public function data(callable $get){
		// 判断缓存文件是否存在
		if (!$this->exists()) {
			File::cFile($this->_cacheFile);
			File::wData($this->_cacheFile , serialize(call_user_func($get)));
		}

		$contents = file_get_contents($this->_cacheFile);

        return unserialize($contents);
	}
}