<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\MessageAction;
use app\v1\model\ChatMessageModel;

/**
 * Description of Message
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Message extends LoginController {

	public function new_alert() {
		$ret = MessageAction::app_unread_alert_get($this->uid);
		if ($ret) {
			MessageAction::app_unread_alert_clear($this->uid);
			$this->succ($ret);
		} else {
			$this->succ(false);
		}
	}

	// 私聊-新消息
	public function msg_new() {
		$fid = input('post.fid');
		$ret = MessageAction::app_friend_msg_last($this->uid, $fid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
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

	// by cxl 删除聊天室记录
	public function msg_delete() {
		$chat_id = input('post.chat_id');
		$res = MessageAction::app_friend_msg_delete($this->uid , $chat_id);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// by cxl 删除群组的聊天记录
	public function msg_group_delete() {
		$gid = input('post.gid');
		$res = MessageAction::app_group_delete($this->uid, $gid);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// by cxl 删除单条聊天记录
	public function msg_single_delete() {
		$msg_id = input('post.msg_id');
		$res = MessageAction::app_msg_exclude_user_set($this->uid, $msg_id);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// by cxl 删除群组的单条聊天记录
	public function msg_group_single_delete() {
		$msg_id = input('post.msg_id');
		$res = MessageAction::app_group_msg_exclude_user_insert($this->uid, $msg_id);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// by cxl 私人会话撤回消息
	public function msg_retract() {
		// 通知双方撤回了消息
		$msg_id = input('post.msg_id');
		$res = MessageAction::app_msg_retract($this->uid, $msg_id);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// by cxl 群会话撤回消息
	public function msg_group_retract() {
		$msg_id = input('post.msg_id');
		$res = MessageAction::app_group_msg_retract($this->uid, $msg_id);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		} else {
			$this->fail($res['data'], $res['code']);
		}
	}

	// 群-消息列表
	public function group_msg() {
		$gid = input('post.gid');
		$ret = MessageAction::app_group_msg($this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
	}

	// 群-新消息
	public function group_msg_new() {
		$gid = input('post.gid');
		$ret = MessageAction::app_group_msg_last($this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->succ($ret['data']);
		}
	}

	// by cxl 设置群消息状态
	public function read_set() {
		$type = input('post.type');
		$msg_id = input('post.msg_id');
		$read = input('post.read');
		$res = MessageAction::app_read_set($this->uid, $type, $msg_id, $read);
		if ($res['code'] != 0) {
			$this->fail($res['data'], $res['code']);
		}
		$this->succ($res['data']);
	}

	// by cxl 消息转发
	public function forward() {
		// 仅能够转发 文本|图片|语音
		// 不允许转发 红包|撤回消息
		// 消息类型：private-私聊消息 group-群聊消息
		$msg_type = input('post.msg_type');
		$msg_id = input('post.msg_id');
		$msg_ids = input('post.msg_ids');
		// 转发对象类型: user-用户 group-群
		$forward_type = input('post.forward_type');
		$forward_id = input('post.forward_id');
		$res = MessageAction::forward($this->uid, $forward_type, $forward_id, $msg_type, $msg_id, $msg_ids);
		if ($res['code'] != 0) {
			$this->fail($res['data'], $res['code']);
		}
		$this->succ($res['data']);
	}

	// 私聊-更早之前的聊天记录
    public function earlier_private_msg() {
        $msg_id = input('post.msg_id');
        $fid = input('post.fid');
        $res = MessageAction::app_earlier_private_msg($this->uid , $fid , $msg_id);
        if ($res['code'] != 0) {
            $this->fail($res['data'], $res['code']);
        }
        $this->succ($res['data']);
    }

    // 群-更早之前的聊天记录
    public function earlier_group_msg() {
        $msg_id = input('post.msg_id');
        $gid = input('post.gid');
        $res = MessageAction::app_earlier_group_msg($this->uid , $gid , $msg_id);
        if ($res['code'] != 0) {
            $this->fail($res['data'], $res['code']);
        }
        $this->succ($res['data']);
    }
}
