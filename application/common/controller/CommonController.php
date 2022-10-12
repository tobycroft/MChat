<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\controller;

use think\Controller;

use app\v1\service\RedisService;
use app\v1\service\CustomizeService;

/**
 * Description of CommonController
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class CommonController extends Controller {

    // phpredis 扩展 redis 实例
    protected $redis = null;

	//put your code here
	public function initialize() {
		header("Access-Control-Allow-Origin: *");
		header('Content-Type: application/json');
		parent::initialize();

		// by cxl 注册自定义 Redis 服务
        RedisService::register();
        CustomizeService::register();

        // by cxl
//        set_exception_handler()
	}

	public function succ($data = '成功', $code = 0) {
		$arr = [
			'data' => $data,
			'code' => $code,
		];
		echo json_encode($arr, 320);
		exit(0);
	}

	public function fail($data = '参数错误', $code = 400) {
		$arr = [
			'data' => $data,
			'code' => $code,
		];
		echo json_encode($arr, 320);
		exit(0);
	}

    // by cxl 获取 redis 实例
    public function redis()
    {
        return $this->redis;
    }
}
