<?php

/*
 * 简单的随机数生成函数
 * 按要求返回随机数
 * @param  Integer    $len        随机码长度                  
 * @param  String     $type       随机码类型  letter | number | mixed
 * @return Array
 */
function random(int $len = 4 , string $type = 'mixed' , bool $is_return_str = true){
	$type_range = array('letter','number','mixed');

	if (!in_array($type , $type_range)){
		throw new Exception('参数 2 类型错误');
	}

	if (!is_int($len) || $len < 1) {
		$len = 1;
	}

	$result = [];
	$letter = array('a' , 'b' , 'c' , 'd' , 'e' , 'f' , 'g' , 'h' , 'i' , 'j' , 'k' , 'l' , 'm' , 'n' , 'o' , 'p' , 'q' , 'r' , 's' , 't' , 'u' , 'v' , 'w' , 'x' , 'y' , 'z');

	for ($i = 0; $i < count($letter) - $i; ++$i)
		{
			$letter[] = strtoupper($letter[$i]);
		}

	if ($type === 'letter'){
		for ($i = 0; $i < $len; ++$i)
			{
				$rand = mt_rand(0 , count($letter) - 1);

				shuffle($letter);
				
				$result[] = $letter[$rand];
			}
	}
	
	if ($type === 'number') {
		for ($i = 0; $i < $len; ++$i)
			{
				$result[] = mt_rand(0 , 9);
			}
	}

	if ($type === 'mixed'){
		for ($i = 0; $i < $len; ++$i)
			{
				$mixed = [];
				$rand  = mt_rand(0 , count($letter) - 1);

				shuffle($letter);

				$mixed[] = $letter[$rand];
				$mixed[] = mt_rand(0,9);

				$rand = mt_rand(0 , count($mixed) - 1);

				shuffle($mixed);

				$result[] = $mixed[$rand];		 
			}
	}

	return $is_return_str ? join('' , $result) : $result;
}

/*
 * 判断是否是无效值
 * @param  Mixed  $val
 * @return Boolean
 */
function is_valid($val){
	// 未定义变量
	if (!isset($val)) {
		return false;
	}
	
	// null
	if (is_null($val)) {
		return false;
	}
	
	// boolean false
	if ($val === false) {
		return false;
	}
	
	// 空值
	if ($val === '') {
		return false;
	}
	
	return true;
}

/*
 * 数组过滤：null 或 空字符串单元
 * @param   Array     $arr
 * @param   Boolean   $is_recursive  是否递归过滤
 * @return  Array    过滤后的数组
 */

function filter_arr(Array $arr = [] , $is_recursive = false){
	if (empty($arr)) {
		return $arr;
	}

	$is_recursive = is_bool($is_recursive) ? $is_recursive : false;

	$filter = function(Array $arr = [] , Array &$rel = []) use (&$filter , $is_recursive) {
		if (empty($arr)) {
			return true;
		}

		if (!$is_recursive) {
			foreach ($arr as $k => $v) 
				{
					if (is_valid($v)) {
						$rel[$k] = $v;
					}
				}
		} else {
			foreach ($arr as $k => $v)
				{
					if (is_array($v) && empty($v)) {
						continue;
					}
					
					if (is_array($v) && !empty($v)) {
						$rel[$k] = [];
						$filter($v , $rel[$k]);
					} else {
						if (is_valid($v)) {
							$rel[$k] = $v;
						}
					}
				}
		}
	};
   
	$rel = [];

	$filter($arr , $rel);

	return $rel;
}


/*
 * 编码转换 gb2312 -> utf-8
 * @param String $string
 * @return String
 */
function utf8($string = ''){
  return mb_convert_encoding($string , 'utf-8' , 'gb2312');
}

/*
 * 编码转换 utf-8 -> gb2312
 * @param String $string
 * @return String
 */
function gbk($string = ''){
  return mb_convert_encoding($string , 'gb2312' , 'utf-8');
}

// 导入数组单元到全局变量 ，并检查是否已存在，若存在则报错
function extract_global(Array $var_list = []){
	if (empty($var_list)) {
		return true;
	}

	foreach ($var_list as $k => $v) 
    {
        if (isset($GLOBALS[$k])) {
            throw new Exception('已存在全局变量： ' . $k);
        }

        $GLOBALS[$k] = $v;
    }
}

/*
 * 给函数绑定参数
 * @param   Callable $func 待绑定参数的函数
 * @return  Closure
 */
function func_bind_args(Callable $func = null){
	$args = func_get_args();

	array_shift($args);

	return function() use($func , $args){
		return call_user_func_array($func , $args);
	};
}

/*
 * 获取当前使用平台：Pc / Mobile
 */
function get_platform(){
	$user_agent   = $_SERVER['HTTP_USER_AGENT'];
	$platform_reg = "/mobile/i";
	
	if (preg_match($platform_reg , $user_agent , $result) === 1) {
		return 'Mobile';
	}

	return 'Pc';
}

// 获取web服务器名称
function get_web_server(){

    $s = $_SERVER['SERVER_SOFTWARE'];
    $s_idx = 0;
    $e_idx = mb_strrpos($s , ' ');
    $server = mb_substring($s , $s_idx , $e_idx);

    return empty($server) ? $_SERVER['SERVER_SOFTWARE'] : $server;
}

/*
 * 判断一个数是偶数还是奇数
 */
function odd_even($num = 0){
	if (!is_numeric($num)) {
		throw new \Exception('参数 1 类型错误');
	}

	$b = 2;

	if ($num % $b !== 0) {
		return 'odd';
	}

	return 'even';
}

// 复杂的随机数生成函数
// 需要 php 支持
function ssl_random(int $len = 256){
    return preg_replace('/[^A-z0-9]*/' , '' , base64_encode(openssl_random_pseudo_bytes($len)));
}


// 精确的随机数分配
// 精确的随机数分配
function decimal_random($total , $num , $scale = 2)
{
    $min = 1;
    $max = 50;
    $last = $total;
    $res = [];
    $count = 0;
    while (true)
    {
        if ($count++ > 10000000) {
            // 死循环！说明参数有问题
            return false;
        }
        if (count($res) == $num - 1) {
//            var_dump(count($res));
//            var_dump($num);
            // 总额 - 已用
            $res[] = $last;
            break;
        } else {
            // 随机分配
            $cur = mt_rand($min , $max);
            $ratio = bcdiv($cur , 100 , 2);
            $amount = bcmul($last , $ratio , $scale);
            if ($amount == 0) {
                $res = [];
                $last = $total;
                continue ;
            }
            $last = bcsub($last , $amount , $scale);
            if ($last == 0) {
                $res = [];
                $last = $total;
                continue ;
            }
            $res[] = $amount;
        }
    }
    return $res;
}


