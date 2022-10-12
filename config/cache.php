<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
	// 驱动方式
	'type' => 'redis',
	// 缓存保存目录
//	'host' => 'nbynbynbynby.redis.singapore.rds.aliyuncs.com',
//	'host' => 'r-t4nbf21c53144c74.redis.singapore.rds.aliyuncs.com',

//	'host' => 'r-t4n7bf5psviy4dmnho.redis.singapore.rds.aliyuncs.com',
//	'host' => '172.17.0.6' ,
	'host' => '172.21.81.109' ,

//	'host' => '192.168.0.13',
//	'host' => '127.0.0.1',
//	'password' => 'CJl7UFg1',
//    'password' => '364793' ,
	'port' => 6379,
//	'path' => '',
	// 缓存前缀
	'prefix' => 'MChat_puxin-',
	// 缓存有效期 0表示永久缓存
	'expire' => 8640000,
	'persistent' => false,
];
