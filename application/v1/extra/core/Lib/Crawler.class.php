<?php

namespace Lib;

/*
 * php网络爬虫
 * 依赖：
 -----------------
 * 函数库：
 * base.php
 -----------------
 * 类库：
 * File.class.php
 */
class Crawler {
	// url
	private $_url = '';

	// method: get|post
	private $_method = '';

	// send data
	private $_data = [];

	// 发起链接等待时间，单位 s：
	private $_waitConnectTimeOut =  0;

	// 发起链接等待时间，单位 ms：
	private $_waitConnectTimeOutMs = false;

	// 链接持续时间：s
	private $_timeOut = 30;

	// 链接持续时间：ms
	private $_timeOutMs = false;

	// 浏览器表示
	private $_userAgent = '';

	// 默认值
	public $defaultOpt = [
		'url'				   => '' ,
		'method'			   => 'get' , 
		'data'				   => [] , 
		'waitConnectTimeOut'   => 30 , 
		'waitConnectTimeOutMs' => false , 
		'timeOut'			   => 30 , 
		'timeOutMs'			   => false ,
		// 这个值需要时常更新
		'userAgent'			   => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36'
	];

	// 存储网络文件的临时目录
	public $tempDir = 'd:/Website/ScrapPieces/CMD/TempFile/';

	// 存储网络文件的临时文件
	private $tempFileList = [];

	// curl 链接资源
	private $_res = null;

	function __construct(){
		$this->_res = curl_init();
	}

	// 设置要发送的数据
	public function setData(Array $opt = []){
		if (empty($opt)) {
			$opt = $this->defaultOpt;
		}

		$this->_url					 = isset($opt['url'])				   ? $opt['url']					: $this->defaultOpt['url'];
		$this->_method				 = isset($opt['method'])			   ? strtolower($opt['method'])		: $this->defaultOpt['method'];
		$this->_data				 = isset($opt['data'])				   ? $opt['data']					: $this->defaultOpt['data'];
		$this->_waitConnectTimeOut   = isset($opt['waitConnectTimeOut'])   ? $opt['waitConnectTimeOut']		: $this->defaultOpt['waitConnectTimeOut'];
		$this->_waitConnectTimeOutMs = isset($opt['waitConnectTimeOutMs']) ? $opt['waitConnectTimeOutMs']	: $this->defaultOpt['waitConnectTimeOutMs'];
		$this->_timeOut				 = isset($opt['timeOut'])			   ? $opt['timeOut']				: $this->defaultOpt['timeOut'];
		$this->_timeOutMs			 = isset($opt['timeOutMs'])			   ? $opt['timeOutMs']				: $this->defaultOpt['timeOutMs'];
		$this->_userAgent			 = isset($opt['userAgent'])			   ? $opt['userAgent']				: $this->defaultOpt['userAgent'];
		
		curl_setopt($this->_res , CURLOPT_URL , $this->_url);
		curl_setopt($this->_res , CURLOPT_RETURNTRANSFER , true);
		
		// post 请求
		if ($this->_method === 'post') {
			// 注意顺序，要先开启 POST 请求，然后在设置要发送的数据
			curl_setopt($this->_res , CURLOPT_POST , true);
			curl_setopt($this->_res , CURLOPT_POSTFIELDS , $this->_data);
		}

		// 设置 false 之后，即可访问 https 协议的链接了
		curl_setopt($this->_res , CURLOPT_SSL_VERIFYPEER , false);
		curl_setopt($this->_res , $this->_waitConnectTimeOutMs === false ? CURLOPT_CONNECTTIMEOUT : CURLOPT_CONNECTTIMEOUT_MS , $this->_waitConnectTimeOutMs === false ? $this->_waitConnectTimeOut : $this->_waitConnectTimeOutMs);
		curl_setopt($this->_res , $this->_timeOutMs === false ? CURLOPT_TIMEOUT : CURLOPT_TIMEOUT_MS , $this->_timeOutMs === false ? $this->_timeOut : $this->_timeOutMs);
		curl_setopt($this->_res , CURLOPT_USERAGENT , $this->_userAgent);
		curl_setopt($this->_res , CURLOPT_ENCODING , 'UTF-8');
		curl_setopt($this->_res , CURLOPT_HEADER , false);
	}

	// 执行
	public function exec(){
		$result = curl_exec($this->_res);
		$errno  = curl_errno($this->_res);

		if ($errno) {
			// 错误代码对照表（链接）：https://curl.haxx.se/libcurl/c/libcurl-errors.html
			return [
				'status' => 'failed' ,
				'msg'	 => '发生错误了，错误信息：' . $errno . '；错误信息对照表（链接）：https://curl.haxx.se/libcurl/c/libcurl-errors.html'
			];
		}

		return [
			'status' => 'success' , 
			'msg'	 => $result
		];
	}

	// 包装要发送的文件
	public function pack($file = '' , $type = 'local'){
		// 文件类型有两种：一种是本地磁盘上的文件；一种是网络文件
		$type_range = ['local' , 'url'];

		// 本地磁盘上的文件
		if (!in_array($type , $type_range)) {
			throw new \Exception('参数 2 错误');
		}

		if ($type === 'local') {
			if (!File::isFile($file)) {
				return false;
			}

			$finfo  = get_file_info($file);

			$c_file = new \CURLFile(gbk($file) , $finfo['mime'] , $finfo['filename']);
		}

		if ($type === 'url') {
			$filename  = get_filename($file);
			$extension = get_extension($file);
			
			$temp_file = $this->tempDir . md5($filename) . '.' . $extension;

			if (File::isFile($temp_file)) {
				File::dFile($temp_file);
			}

			$temp_res = fopen(gbk($temp_file) , 'x');
			$net_res  = fopen($file , 'r');

			while ($line = fgets($net_res))
				{
					fwrite($temp_res , $line);
				}
			
			fclose($temp_res);
			fclose($net_res);
			
			// 包装成 POST 文件时，需要提供绝对路径
			$temp_file = realpath($temp_file);

			$finfo = get_file_info($temp_file);

			// var_dump($finfo['mime']);

			$c_file = new \CURLFile($temp_file , $finfo['mime'] , $filename);
			
			if (array_search($temp_file , $this->tempFileList) === false) {
				$this->tempFileList[] = $temp_file;
			}
		}

		return $c_file;
	}

	// 删除包装的临时文件
	public function dTempFile(){
		// 删除临时文件（如果存在的话）
		foreach ($this->tempFileList as $v)
			{
				if (File::isFile($v)) {
					File::dFile($v);
				}
			}
	}

	// 获取文件列表
	public function getTempFileList(){
		return $this->tempFileList;
	}

	// 关闭 curl
	public function close(){
		return curl_close($this->_res);
	}
}