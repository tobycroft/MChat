<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/18
 * Time: 14:46
 */

namespace Core\Lib;


class Http {
    // 默认模拟的头部
    public static $userAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36';

    // 默认选项
    public static $default = [
        // 请求路径
        'url' => '' ,
        // 发送的数据
        'data' => [] ,
        // 请求方式
        'method' => 'get' ,
        // 请求头
        'header' => [] ,
        // cookie
        'cookie' => '' ,
    ];

    // 发送 post 请求
    public static function post($url , $option = [])
    {
        return self::ajax(array_merge($option , [
            'url' => $url ,
            'method' => 'post'
        ]));
    }

    // 发送 get 请求
    public static function get($url , $option = [])
    {
        return self::ajax(array_merge($option , [
            'url' => $url ,
            'method' => 'get'
        ]));
    }

    // 发送任意请求
    public static function ajax($option = [])
    {
        $option['url'] = $option['url'] ?? self::$default['url'];
        $option['data'] = $option['data'] ?? self::$default['data'];
        $option['method'] = $option['method'] ?? self::$default['method'];
        $option['method'] = strtolower($option['method']);
        $option['header'] = $option['header'] ?? self::$default['header'];
        $option['cookie'] = $option['cookie'] ?? self::$default['cookie'];

        $res = curl_init();
        curl_setopt_array($res , [
            CURLOPT_RETURNTRANSFER => true ,
            CURLOPT_HEADER => false ,
            CURLOPT_URL => $option['url'] ,
            // 要发送的请求头
            CURLOPT_HTTPHEADER => $option['header'] ,
            CURLOPT_POST => $option['method'] == 'post' ,
            CURLOPT_POSTFIELDS => $option['data'] ,
            // user-agent 必须携带！
            CURLOPT_USERAGENT => self::$userAgent ,
            // 要携带的 cookie，不知道能够坚持多久？？
            CURLOPT_COOKIE => $option['cookie'] ,
            CURLOPT_SSL_VERIFYPEER => false ,
            CURLOPT_FOLLOWLOCATION  => true ,
            CURLOPT_MAXREDIRS  => 3 ,
            /*
             * todo 支持代理
            // 启用 http 代理隧道
            CURLOPT_HTTPPROXYTUNNEL => true ,
            CURLOPT_PROXYTYPE   => $cur['type'] ,
            CURLOPT_PROXY       => $cur['ip'] ,
            CURLOPT_PROXYPORT   => $cur['port'] ,
            */
        ]);
        $str = curl_exec($res);
        curl_close($res);
        return $str;
    }
}