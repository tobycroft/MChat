<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\service;

class SmsService {

	public static function sendinter($quhao, $phone, $code) {
		$url = request()->root(true);
		$target = "http://api.isms.ihuyi.com/webservice/isms.php?method=Submit";


		$time = cache(__CLASS__ . $phone);
		if (!$time) {
			$time = 0;
		}
		cache(__CLASS__ . $phone, $time + 1, 86400);

		if (cache(__CLASS__ . $phone) > 25) {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("1Your verification code is " . $code);
		} elseif (cache(__CLASS__ . $phone) > 20) {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("2Your verification code is " . $code);
		} elseif (cache(__CLASS__ . $phone) > 15) {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("Your verification code is " . $code . "。");
		} elseif (cache(__CLASS__ . $phone) > 10) {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("Your verification code is " . $code . "！");
		} elseif (cache(__CLASS__ . $phone) > 5) {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("Your verification code is " . $code . ".");
		} else {
			$post_data = "account=I37477478&password=178f215fad429e7e5861b582775c89b8&mobile=" . $quhao . ' ' . $phone . "&content=" . rawurlencode("Your verification code is " . $code);
		}
		//国籍短信
		//查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
		$gets = self::xml_to_array(self::post($post_data, $target));
//		dump($gets);
		if ($gets['SubmitResult']['code'] == '2') {
			return true;
		} else {
			\RET::fail($gets['SubmitResult']['msg'] . cache(__CLASS__ . $phone));
		}
	}

	public static function send_nby($phone, $code) {
		$url = request()->root(true);
		$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
		$time = cache(__CLASS__ . $phone);
		if (!$time) {
			$time = 0;
		}
		cache(__CLASS__ . $phone, $time + 1, 86400);

		if (cache(__CLASS__ . $phone) > 25) {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("您的短信验证码为" . $code . "，如非本人操作，请尽快修改您的密码，以免造成财产损失。 ");
		} elseif (cache(__CLASS__ . $phone) > 20) {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("您正在进行手机验证，验证码是" . $code . "，5分钟内有效。");
		} elseif (cache(__CLASS__ . $phone) > 15) {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("您的短信验证码为" . $code . "，请勿向任何人提供此验证码。 ");
		} elseif (cache(__CLASS__ . $phone) > 10) {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("验证码为：" . $code . "，您正在注册成为新会员，感谢您的支持！ ");
		} elseif (cache(__CLASS__ . $phone) > 5) {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("验证码：" . $code . "，请您在5分钟内填写。如非本人操作，请忽略本短信。  ");
		} else {
			$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("您的短信验证码为" . $code . "，如非本人操作，请尽快修改您的密码，以免造成财产损失。 ");
		}



		//查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
		$gets = self::xml_to_array(self::post($post_data, $target));
		if ($gets['SubmitResult']['code'] == '2') {
			return true;
		} else {
			\RET::fail($gets['SubmitResult']['msg'] . cache(__CLASS__ . $phone));
		}
	}

	public static function send($phone, $code) {
		$url = request()->root(true);
		if (strstr($url, 'atchain')) {
//			return self::send_nby($phone, $code);
			return \SMS\Zz253::send_nby(86, $phone, $code);
		} else {
			return \SMS\Zz253::send_msg(86, $phone, $code);
		}
	}

	public static function send_lexin($phone, $code) {
		$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
		$post_data = "account=C03756322&password=ff166d0cdeb67f5cbfca8ae7dd5ae699&mobile=" . $phone . "&content=" . rawurlencode("您的验证码是：" . $code . "。请不要把验证码泄露给其他人。 ");


		//查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
		$gets = self::xml_to_array(self::post($post_data, $target));
		// by cxl 错误提示
		if ($gets['SubmitResult']['code'] == '2') {
			return [
			    'status' => true ,
                'data' => ''
            ];
		} else {
			return [
			    'status' => false ,
                'data' => $gets['SubmitResult']['msg']
            ];
		}
	}

	public static function on_changepass($phone) {

		$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";


		$post_data = "account=C67403256&password=59e5bfe73574d57ed51e176079fa96fa&mobile=" . $phone . "&content=" . rawurlencode("您的密码已被修改，如非本人操作，请尽快修改，以免造成财产损失");


		//查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
		$gets = self::xml_to_array(self::post($post_data, $target));
		if ($gets['SubmitResult']['code'] == '2') {
			return true;
		} else {
			return false;
		}
	}

	private static function post($curlPost, $url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$return_str = curl_exec($curl);
		curl_close($curl);
		return $return_str;
	}

	private static function xml_to_array($xml) {
		$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
		if (preg_match_all($reg, $xml, $matches)) {
			$count = count($matches[0]);
			for ($i = 0; $i < $count; $i++) {
				$subxml = $matches[2][$i];
				$key = $matches[1][$i];
				if (preg_match($reg, $subxml)) {
					$arr[$key] = self::xml_to_array($subxml);
				} else {
					$arr[$key] = $subxml;
				}
			}
		}
		return $arr;
	}

}
