<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use ApexUC\Auth;
use ApexUC\UserInfo;
use app\v1\action\MemberAction;
use app\v1\action\PacketAction;
use app\v1\service\AuthService;
use Cache;
use Db;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use app\common\controller\CommonController;
use Net;
use Push\APush;
use SMS\ChuanglanSmsApi;

/**
 * Description of Index
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Index extends CommonController {

	//put your code here
	public function initialize() {
		parent::initialize();
	}

	public function cqa() {
//		dump(\app\v1\service\PayService::user_pay('1', '1', '0.001', 'port-test', '123456', 'test'));
	}

	public function index() {
//		dump(cache('123123', 1, 300));
//		echo cache('123123');
//		echo cache('phone_' . '13107670001');
//
//		echo 'welcome';
//		echo \Payment\Order::generate_order_id();
		echo 'L-' . request()->ip();

//		echo ((microtime(5) - time()) * 10000);
//		echo 123;
	}

	public function login() {
		$uid = input('post.uid');
		if (MemberAction::app_sync_userinfo($uid)) {
			$this->succ();
		} else {
			$this->fail();
		}
	}

	// todo by cxl
	// 相关 tp5.x 系列功能调试请在这个方法内处理
	public function test() {
		var_dump('hello boys and girls');
		// 获取手机号啊
		$res = AuthService::serv_get_userinfo(21);
		var_dump($res);
	}

	public function t() {

	}

    public function push()
    {
        $res = APush::push_single(7217 , '消息推送测试' , '消息推送');
        var_dump($res);
        var_dump('推送服务端已发出，请查看客户端是否接收成功');
    }

    public function test1()
    {
        $uid = 1;
        $token = '';
        $ip = request()->ip();
        $res = AuthService::serv_auth($uid, $token, $ip);
        var_dump($res);
        echo 'hello boys';
    }

    public function send()
    {
        $url = 'http://172.21.189.216:9601';
        $key = 'gn8knscieinuincsiuni4ur0wherur';
        $param = [
            'key' => $key ,
            'uids' => '["1"]' ,
            'data' => '{"id":3890,"chat_id":"1_7217","sender":7217,"type":1,"message":"\u901a\u8fc7 postman \u53d1\u9001\u7684\u6d88\u606f 3333","extra":null,"date":1556590997,"exclude_one":0,"exclude_two":0,"user_info":{"id":3023,"uid":7217,"uname":"grayVTouch","face":"20190422\/7f4cea28c214d65aa00b8191431f9c15.jpg","introduction":"test","sex":1,"telephone":"0","mail":"","exp":0,"career":"","company":"","school":"","birthday":"0000-00-00","change_date":1555912679,"date":1551421578,"can_notice":1,"is_remark":false},"is_read":1}' ,
            'dest' => '1_7217' ,
            'type' => 'private_chat' ,
        ];
//
//        $res = curl_init();
//        curl_setopt_array($res , [
//            CURLOPT_RETURNTRANSFER => true ,
//            CURLOPT_HEADER => false ,
//            CURLOPT_URL => $url,
//            CURLOPT_PORT => $port ,
//            CURLOPT_TIMEOUT => 3 ,
//            CURLOPT_POST => true ,
//            // 要发送的请求头
//            CURLOPT_HTTPHEADER => [] ,
//            CURLOPT_SSL_VERIFYPEER => false ,
//            CURLOPT_POSTFIELDS => http_build_query($param)
//        ]);
//        $str = curl_exec($res);
//
//        var_dump($str);
        $r = Net::post_data($url , $param , 3);
        var_dump($r);
    }

    public function sendSms()
    {
        $sms = new ChuanglanSmsApi();
        $res = $sms->sendInternational('13375086826' , 'nihao');
        var_dump($res);
    }
}
