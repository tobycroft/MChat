<?php
namespace Lib;

/*
 * author 陈学龙 2016-10-04
 * 图片水印 允许多开
 */

class Watermark {
	private $_imgWatermarkDir    = '';
	private $_watermarkPosRange  = array('top' , 'right' , 'bottom' , 'left' , 'center' , 'top_left' , 'top_right' , 'bottom_left' , 'bottom_right');
	private $_typeRange = array('gif' , 'jpg' , 'png');

	function __construct($img_watermark_dir = ''){
		if (!File::isDir($img_watermark_dir)) {
			File::cDir($img_watermark_dir);
		}

		$this->_imgWatermarkDir = format_path($img_watermark_dir);
	}

	/*
	 * 图片水印
	 * 注意：若两次处理时间小于 1 s，请至少等待 1 s后在进行处理
	 * @param String $img_file		  待处理的图片路径
	 * @param String $watermark_file  待处理的水印图片路径
	 * @param String $opt			  水印设置：center | top | right | bottom | left | top_left | top_right | bottom_left | bottom_right

	   $opt = array(
		   'pos'   => 'center'	 // 水印位置
		   'size'  => array(     // 水印尺寸
				 'width' => 100 ,  // 宽
				 'height' => 50    // 高
		    ) , 
		   'opacity' => 100      // 水印的透明度 范围：0 - 100 透明度逐渐增强
		   'extension' => 'jpg'  // 最终生成的图片类型 gif | jpg | png
	   );

	 */
	public function makeWatermark($img_file = '' , $watermark_file = '' , array $opt = array()){
		if (!File::isFile($img_file)){
			return false;
		}

		if (!File::isFile($watermark_file)){
			return false;
		}
		
		$img_file_name       = get_filename($img_file);
		$watermark_file_name = get_filename($watermark_file);

		if (!in_array($img_file_name , $this->_typeRange)) {
			throw new \Exception('参数 1 文件类型错误');
		}

		if (!in_array($watermark_file_name , $this->_typeRange)) {
			throw new \Exception('参数 2 文件类型错误');
		}
		
		if (empty($opt)) {
			$opt = array(
				'pos'   => 'center' , // 水印位置
				'size'  => array(     // 水印尺寸
								'width' => 100 ,  // 宽
								'height' => 50    // 高
						   ) , 
				'opacity' => 100 ,    // 水印的透明度 范围：0 - 100 透明度逐渐增强
				'extension' => 'jpg'  // 最终生成的图片类型
			);
		}

		if (!in_array($opt['pos'] , $this->_watermarkPosRange)) {
			$opt['pos'] = 'center';
		}

		if (!in_array($opt['ext'] , $this->_typeRange)) {
			$opt['extension'] = 'jpg';
		}

		// 提高脚本性能
		$original_memory_limit = ini_get('memory_limit');
		$original_run_time     = ini_get('max_execution_time');

		ini_set('memory_limit' , '2048M');
		set_time_limit(0);
		ignore_user_abort(true);

		// 相关信息
		$original_pic_info = get_image_info($img_file);

		// 处理原图
		switch ($original_pic_info['extension'])
			{
				case 'gif':
					$cav_original = imagecreatefromgif($img_file);
					break;
				case 'jpg':
					$cav_original = imagecreatefromjpeg($img_file);
					break;
				case 'png':
					$cav_original = imagecreatefrompng($img_file);
					break;
			}

		// 处理水印图片
		$cav_watermark		   = imagecreatetruecolor($opt['size']['width'] , $opt['size']['height']);
		$watermark_origin_info = get_image_info($watermark_file);

		switch ($watermark_origin_info['extension'])
			{
				case 'gif':
					$cav_watermark_origin = imagecreatefromgif($watermark_file);
					break;
				case 'jpg':
					$cav_watermark_origin = imagecreatefromjpeg($watermark_file);
					break;
				case 'png':
					$cav_watermark_origin = imagecreatefrompng($watermark_file);
					break;
			}

		imagecopyresampled($cav_watermark , $cav_watermark_origin , 0 , 0 , 0 , 0 , $opt['size']['width'] , $opt['size']['height'] , $watermark_origin_info['width'] , $watermark_origin_info['height']);

		// 计算水印位置
		if ($opt['pos'] === 'left') {
			$dst_x = 0;
			$dst_y = abs($original_pic_info['height'] - $opt['size']['height']) / 2;
		}

		if ($opt['pos'] === 'top') {
			$dst_x = abs($original_pic_info['width'] - $opt['size']['width']) / 2;
			$dst_y = 0;
		} 
		
		if ($opt['pos'] === 'bottom') {
			$dst_x = abs($original_pic_info['width'] - $opt['size']['width']) / 2;
			$dst_y = $original_pic_info['height'] - $opt['size']['height'];
		} 

		if ($opt['pos'] === 'right') {
			$dst_x = $original_pic_info['width'] - $opt['size']['width'];
			$dst_y = abs($original_pic_info['height'] - $opt['size']['height']) / 2;
		}

		if ($opt['pos'] === 'center') {
			$dst_x = abs($original_pic_info['width'] - $opt['size']['width']) / 2;
			$dst_y = abs($original_pic_info['height'] - $opt['size']['height']) / 2;
		}

		if ($opt['pos'] === 'top_left') {
			$dst_x = 0;
			$dst_y = 0;
		}

		if ($opt['pos'] === 'top_right') {
			$dst_x = $original_pic_info['width'] - $opt['size']['width'];
			$dst_y = 0;
		}
		
		if ($opt['pos'] === 'bottom_left') {
			$dst_x = 0;
			$dst_y = $original_pic_info['height'] - $opt['size']['height'];
		} 
		
		if ($opt['pos'] === 'bottom_right') {
			$dst_x = $original_pic_info['width'] - $opt['size']['width'];
			$dst_y = $original_pic_info['height'] - $opt['size']['height'];
		}

		// 合成 原图 + 水印
		if (!imagecopymerge($cav_original , $cav_watermark , $dst_x , $dst_y , 0 , 0 , $opt['size']['width'] , $opt['size']['height'] , $opt['opacity'])) {
			throw new \Exception('合成图像失败');
		}

		// 保存处理后的图片
		$fname		     = date('Y-m-d H-i-s' , time());
		$fname		     = $fname . '.' . $opt['extension'];
		$watermark_file  = $this->_imgWatermarkDir . '/' . $fname;
		$watermark_file  = gbk($watermark_file);

		switch ($opt['extension'])
			{
				case 'gif':
					imagegif($cav_original  , $watermark_file);
					break;
				case 'jpg':
					imagejpeg($cav_original , $watermark_file);
					break;
				case 'png':
					imagepng($cav_original  , $watermark_file);
					break;
			}

		// 还原php系统设置
		ini_set('memory_limit' , $original_memory_limit);
		set_time_limit($original_run_time);
		ignore_user_abort(false);
		
		$watermark_file = utf8($watermark_file);

		return array(
			'local_path' => $watermark_file , 
			'url'        => generate_url($watermark_file , false)
		);
	}
}


