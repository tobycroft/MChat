<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\adc\controller;

use app\common\controller\CommonController;
use app\v1\action\MessageAction;
use app\v1\model\ChatMessageModel;

/**
 * Description of Message
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Message extends CommonController {

    public $uid;

	public function initialize() {
	    parent::initialize();
		$this->uid = input('post.uid');
	}

	// 会话列表-群/组
	public function msg_list() {
		$ret = MessageAction::app_message_list($this->uid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
	}

	// 私聊-历史消息
	public function msg() {
		$fid = input('post.fid');
		$ret = MessageAction::app_friend_msg($this->uid, $fid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
	}

	public function group_msg() {
		$gid = input('post.gid');
		$ret = MessageAction::app_group_msg($this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
	}

}
