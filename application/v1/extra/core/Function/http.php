<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/2/23
 * Time: 21:59
 */

// 原始数据
function http_raw(){
    return file_get_contents('php://input');
}

// 用户发送的数据
function http_input($key = ''){
    $raw = http_raw();
    $ct = get_request_header('Content-Type');
    $input = [];
    switch ($ct)
    {
        case 'application/x-www-form-urlencoded':
            $one = explode('&' , $raw);
            foreach ($one as $v)
            {
                $v = explode('=' , $v);
                $v[0] = $v[0] ?? '';
                $v[1] = $v[1] ?? '';
                $input[$v[0]] = $v[1];
            }
            break;
        case 'application/json':
            $input = json_decode($raw);
            break;
        default:
            throw new Exception('unknow content-type');
    }
    if (empty($key)) {
        return $input;
    }
    foreach ($input as $k => $v)
    {
        if ($k === $key) {
            return $v;
        }
    }
    return null;
}

/**
 * 根据不同的服务器环境获取请求头
 * 目前支持的服务器有：Apache/Nginx
 * @param String $key 请求头
 * @return Boolean|String 失败时返回 false
 */
function get_request_header($key = ''){
    if (function_exists('getallheaders')) {
        // Apache 服务器
        $headers = getallheaders();
        if (empty($key)) {
            return $headers;
        }
        foreach ($headers as $k => $v)
        {
            if ($k === $key) {
                return $v;
            }
        }
        return '';
    }
    // nginx 服务器
    $headers = [];
    foreach ($_SERVER as $k => $v)
    {
        if ((bool) preg_match('^HTTP_' , $k)) {
            $header = str_replace('HTTP_' , '' , $k);
            $header = str_replace('_' , '-' , $header);
            $header = strtolower($header);
            $header = ucwords($header , '-');
            $headers[$header] = $v;
        }
    }
    if (empty($key)) {
        return $headers;
    }
    $key = ucwords(strtolower($key) , '-');
    foreach ($headers as $k => $v)
    {
        if ($k === $key) {
            return $v;
        }
    }
    return '';
}