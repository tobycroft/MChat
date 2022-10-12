<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\service;

/**
 * Description of PayService
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class PayService extends AuthService {

	//put your code here


	public static function user_pay($uid, $cid, $amount, $order_id, $password, $remark) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid,
			'cid' => $cid,
			'amount' => $amount,
			'orderid' => $order_id,
			'password' => $password,
			'remark' => $remark,
		];
		$ret = \Net::post_data(self::url() . '/api/auth/user_pay', $data);
		$json = json_decode($ret, 1);
		if ($json) {
			if ($json['code'] == 0) {
				return [
					'code' => 0,
					'data' => '成功'
				];
			} else {
				return [
					'code' => $json['code'],
					'data' => $json['data'],
				];
			}
		} else {
			return [
				'code' => 404,
				'data' => '远程接口故障'
			];
		}
	}

	public static function user_fund($uid, $cid, $amount, $order_id, $remark) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid,
			'cid' => $cid,
			'amount' => $amount,
			'orderid' => $order_id,
			'remark' => $remark,
		];
		$ret = \Net::post_data(self::url() . '/api/auth/user_fund', $data);
		$json = json_decode($ret, 1);
		if ($json) {
			if ($json['code'] == 0) {
				return [
					'code' => 0,
					'data' => '成功'
				];
			} else {
				return [
					'code' => $json['code'],
					'data' => $json['data'],
				];
			}
		} else {
			return [
				'code' => 404,
				'data' => '远程接口故障'
			];
		}
	}

    public static function user_refund($uid, $cid, $amount, $order_id, $remark) {
        $data = [
            'auth' => self::authKey(),
            'uid' => $uid,
            'cid' => $cid,
            'amount' => $amount,
            'orderid' => $order_id,
            'remark' => $remark,
        ];
        $ret = \Net::post_data(self::url() . '/api/auth/user_refund', $data);
        $json = json_decode($ret, 1);
        if ($json) {
            if ($json['code'] == 0) {
                return [
                    'code' => 0,
                    'data' => '成功'
                ];
            } else {
                return [
                    'code' => $json['code'],
                    'data' => $json['data'],
                ];
            }
        } else {
            return [
                'code' => 404,
                'data' => '远程接口故障'
            ];
        }
    }

	public static function serv_get_balance($uid) {
		$data = [
			'auth' => self::authKey(),
			'uid' => $uid
		];
		$ret = \Net::post_data(self::url() . '/api/auth/user_balance', $data);
//		dump($ret);
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
		} else {
			return [
				'code' => 404,
				'data' => '远程接口故障'
			];
		}
	}

}
