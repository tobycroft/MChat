<?php

namespace app\index\controller;

use Push\APush;
use think\Controller;

class Index extends Controller {

	public function index() {
		return '222';
	}

	public function hello($name = 'ThinkPHP5') {
		return 'hello,' . $name;
	}

	public function test()
    {
        var_dump('hello world!');
    }

    public function push()
    {
        $res = APush::push_single(7217 , '消息推送测试' , '消息推送');
        var_dump($res);
        var_dump('推送服务端已发出，请查看客户端是否接收成功');
    }
}
