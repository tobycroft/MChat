<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\service;

/**
 * Description of AuthService
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class AuthService {

	// auth 修改
	protected static function authKey() {
		return md5(date('Y-m-d') . '438csib7tqcbkzknqxown9ximndun9w');
	}

	// by cxl
	public static function url()
    {
        return config('app.remote_api_url');
    }

	public static function serv_auth($uid, $token, $ip) {
		if (cache(md5($uid . $token))) {
			return true;
		}
		$arr = [
			'uid' => $uid,
			'token' => $token,
			'auth' => self::authKey(),
			'ip' => $ip,
		];
		// todo 生产环境请务必放开注释
		$ret = \Net::post_data(self::url() . '/api/auth/userauth', $arr);
		$ret = json_decode($ret, 1);
		if ($ret['code'] == 0) {
			cache(md5($uid . $token), true, 30);
			return true;
		} else {
			return false;
		}
	}

	public static function serv_get_userinfo($uid) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid
		];
		$ret = \Net::post_data(self::url() . '/api/auth/userinfo', $data);
//		var_dump($ret);
//		exit;
//		var_dump($ret);
		$json = json_decode($ret, 1);
		if ($json) {
			return $json;
		}
	}

	public static function serv_get_balance($uid) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid
		];

		$ret = \Net::post_data(self::url() . '/api/auth/user_balance', $data);
		$json = json_decode($ret, 1);
		if (isset($json['code']) && $json['code'] == 0) {
			$arr = [];
			foreach ($json['data'] as $value) {
				$data = [
					'coin_id' => $value['coin_id'],
					'coin_name' => $value['coin_name'],
					'balance' => $value['balance'],
					'img' => $value['icon'],
				];
				array_push($arr, $data);
			}
			return $arr;
		}
	}

	// 获取手续费扣取记录
	public static function serv_get_fee_record($uid) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid
		];
		$ret = \Net::post_data(self::url() . '/api/auth/user_log', $data);
		return json_decode($ret, true);
	}


}
