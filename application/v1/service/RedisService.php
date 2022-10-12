<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/2/28
 * Time: 16:38
 */

namespace app\v1\service;

use app\v1\util\Redis;

class RedisService
{
    // 注册服务
    public static function register()
    {
        // 注册自定义 Redis 管理类
        self::initRedis();
    }

    public static function initRedis()
    {
        $host = config('cache.host');
        $port = config('cache.port');
        $password = config('cache.password');
        $timeout = config('cache.timeout');
        $prefix = config('cache.prefix');
        $redis = new Redis($host , $port , $timeout);
        $redis->prefix = $prefix;
        if (!empty($password)) {
            $redis->native('auth' , $password);
        }
        // 绑定到容器
        app()->instance('redis' , $redis);
    }
}