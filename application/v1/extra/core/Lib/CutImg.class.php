<?php
namespace Lib;
/*
 * Author 陈学龙 2016-10-06 
 */

class CutImg {
	private $_cutImgDir  = '';

	/*
	 *  传入裁切后保存图片目录
	 */
	function __construct($cut_img_dir = ''){
		if (!File::isDir($cut_img_dir)) {
			File::cDir($cut_img_dir);
		}

		$this->_cutImgDir = $cut_img_dir;
	}

	/*
	 * 图片处理
	 * @param  String    $img						待裁切的图片
	 * @param  Array     $opt						裁切设置
	 * @param  Boolean   $is_save_original_file		是否保留源文件   true | false
 	 * @param  Boolean   $is_save_original_name     是否保留文件名   true | false
	 */
	public function cutImg($img = '' , array $opt = array() , $is_save_original_file = true , $is_save_original_name = true){
		if (!File::isFile($img)) {
			throw new \Exception('待处理的图片不存在：' . $img);
		}

		$type_range = array('gif' , 'jpg' , 'png');
		$extension  = get_filename($img);

		if (!in_array($extension , $type_range)) {
			return false;
		}

		$info = get_image_info($img);

		if (empty($opt)) {
			$opt = array(
				'x'  => 0 ,                           // 裁切的起点 x 坐标
				'y'  => 0 ,                           // 裁切的起点 y 坐标
				'w'  => $info['width'] * 0.5 ,        // 裁切长度
				'h'  => $info['height'] * 0.5 ,       // 裁切高度
				'ow' => $info['width'] ,              // 容器宽度
				'oh' => $info['height']               // 容器高度
			);
		}

		$is_save_original_file = is_bool($is_save_original_file) ? $is_save_original_file : true;
		$is_save_original_name = is_bool($is_save_original_name) ? $is_save_original_name : true;


		// 提高脚本性能！（处理图片需要耗费较大资源）
		$originial_sys_memory_size = ini_get('memory_limit');
		$originnal_script_run_time = ini_get('max_execution_time');

		ini_set('memory_limit' , '2048M');
		set_time_limit(0);
		ignore_user_abort(true);

		// 计算裁切相关数值
		$endW = floor($opt['w'] / $opt['ow'] * $info['width']); 
		$endH = floor($opt['h'] / $opt['oh'] * $info['height']);    
		$endX = floor($opt['x'] / $opt['ow'] * $info['width']);
		$endY = floor($opt['y'] / $opt['oh'] * $info['height']);

		// 读取源文件
		$img = gbk($img);

		switch ($info['extension'])
			{
				case 'gif' :
					$cav = imagecreatefromgif($img);
					break;
				case 'jpg':
					$cav = imagecreatefromjpeg($img);
					break;
				case 'png' :
					$cav = imagecreatefrompng($img);
					break;
			}

		// 图像裁切
		$cut_img = imagecreatetruecolor($endW , $endH);

		imagecopy($cut_img , $cav , 0 , 0 , $endX , $endY , $endW , $endH);

		// 是否保留文件名
		if ($is_save_original_name) {
			$fname = $info['filename'];
		} else {
			$fname  = date('Y-m-d H-i-s' , time());
			$fname .= '.' . $info['extension'];
		}

		$file = $this->_cutImgDir . '/' . $fname;

		// 删除同名文件
		if (File::isFile($file)){
			File::dFile($file);
		}

		$file = gbk($file);

		switch ($info['extension'])
			{
				case 'gif' :
					imagegif($cut_img , $file);
					break;
				case 'jpg':
					imagejpeg($cut_img , $file);
					break;
				case 'png' :
					imagepng($cut_img , $file);
					break;
			}

		imagedestroy($cav);
		imagedestroy($cut_img);

		$img  = utf8($img);
		$file = utf8($file);

		// 文件处理完成后 判断是否删除源文件
		if (!$is_save_original_file) {
			File::dFile($img);
		}

		// 还原系统设置
		ini_set('memory_limit' , $original_memory_limit);
		set_time_limit($original_run_time);
		ignore_user_abort(false);

		return array(
			'local_path' => $file ,
			'url'        => generate_url($file , false)
		);
	}
}
