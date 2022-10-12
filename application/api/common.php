<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/2/28
 * Time: 10:42
 */


// by cxl
function redis(){
    return app()->get('redis');
}

// 保留多少位小数点
function fix_number($num , $len = 0){
    $str = number_format($num , $len);
    $str = preg_replace('/[^0-9\.]/' , '' , $str);
    return floatval($str);
}

// 获取数字
function number($str = '' , $len = 0){
    $str = preg_replace('/[^0-9\.]*/' , '' , $str);
    return fix_number($str , $len);
}

// 精确的随机数分配
//function decimal_random($total , $num , $scale = 2)
//{
//    $min = 1;
//    $max = 60;
//    $last = $total;
//    $res = [];
//    for ($i = 1; $i <= $num; ++$i)
//    {
//        if ($i == $num) {
//            // 总额 - 已用
//            $res[] = $last;
//        } else {
//            // 随机分配
//            $cur = mt_rand($min , $max);
//            $ratio = bcdiv($cur , 100 , 2);
//            $amount = bcmul($last , $ratio , $scale);
//            $last = bcsub($last , $amount , $scale);
//            $res[] = $amount;
//        }
//    }
//    return $res;
//}

// 获取给定数组中给定键名对应单元
function array_unit(array $arr = [] , array $keys = [])
{
    $res = [];
    foreach ($keys as $v)
    {
        if (!isset($arr[$v])) {
            continue ;
        }
        $res[$v] = $arr[$v];
    }
    return $res;
}

// 图片完整的访问 url
function image_url($path = '')
{
    if (empty($path)) {
        return '';
    }
//    $image_host = config('app.image_host');
//    $image_host = rtrim($image_host , '/');
//    $path = ltrim($path , '/');
    return $path;
//    return sprintf('%s/%s' , $image_host , $path);
}

// 图片相对路径
function image_path($path = '')
{
    if (empty($path)) {
        return '';
    }
    $path = realpath($path);
    $upload_dir = config('app.upload_dir');
    $upload_dir = addcslashes($upload_dir , '/');
    $reg = "/^{$upload_dir}/";
    return preg_replace($reg , '' , $path);
}

// 图片真实路径
function image_realpath($path = '')
{
    if (empty($path)) {
        return '';
    }
    $upload_dir = config('app.upload_dir');
    return sprintf('%s%s' , $upload_dir , $path);
}

// 获取缓存时间（在给定的缓存时间内 + 随机数字，避免缓存同时过期）
function cache_duration($type = 'l')
{
    $range = ['l' , 'm' , 's' , 'month'];
    if (!in_array($type , $range)) {
        throw new Exception('不支持的类型');
    }
    // 单位 s
    $min = 0;
    $max = 5 * 60;
    return config(sprintf('business.cache_%s' , $type)) + mt_rand($min , $max);
}

function convert_obj($obj)
{
    return json_decode(json_encode($obj));
}

/**
 * 解析排序
 *
 * @param string $order
 * @return array
 */
function parse_order(string $order = '')
{
    if (empty($order)) {
        return [];
    }
    $order = explode('|' , $order);
    if (count($order) != 2) {
        return [];
    }
    return [
        'field' => $order[0] ,
        'value' => $order[1] ,
    ];
}