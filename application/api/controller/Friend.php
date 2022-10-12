<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;

use app\v1\action\FriendAction;
use app\v1\action\MemberAction;

/**
 * Description of Friend
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Friend extends LoginController {

	//put your code here

	public function index() {
		echo 'friend';
	}

	public function has_new_friend() {
		$this->succ(FriendAction::app_has_new_friend($this->uid));
	}

	public function userinfo() {
		$fid = input('post.fid');
        $res = MemberAction::app_user_info_find($this->uid , $fid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
	}

	public function friend_list() {
		$ret = FriendAction::app_friend_list($this->uid);
		if ($ret) {
			$this->succ($ret);
		} else {
			$this->succ([]);
		}
	}

	public function search_friend() {
		$value = input('post.value');
		$ret = MemberAction::app_search($value);
		if ($ret) {
			$this->succ($ret);
		} else {
			$this->succ([]);
		}
	}

	public function delete_friend() {
		//todo   needs a delete recall
		$fid = input('post.fid');
		if (FriendAction::app_delete_friend($this->uid, $fid)) {
			$this->succ();
		} else {
			$this->fail('失败');
		}
	}

	public function add_friend() {
//		$fid = input('post.fid');
//		if (FriendAction::app_add_friend($this->uid, $fid)) {
//			$this->succ();
//		} else {
//			$this->fail('失败');
//		}
	}

	public function request_friend() {
		$fid = input('post.fid');
		$comment = input('post.comment');
		$ret = FriendAction::app_friend_request($this->uid, $fid, $comment);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data']);
		}
	}

	public function request_list() {
		$ret = \app\v1\action\RequestAction::app_friend_request($this->uid);
		if ($ret) {
			$this->succ($ret);
		} else {
			$this->succ([]);
		}
	}

	// 请求处理
	public function request_operation() {
		$id = input('post.id');
		$approve = (boolean) input('post.approve');
		$comment = input('post.comment');
		if (\app\v1\action\RequestAction::app_request_logic($this->uid, $id, $approve, $comment)) {
			$this->succ();
		} else {
			$this->fail('不允许操作');
		}
	}

	public function is_friend() {
		$fid = input('post.fid');
		$ret = FriendAction::app_is_friend($this->uid, $fid);
		if ($ret) {
			$this->succ(true);
		} else {
			$this->fail(false, 0);
		}
	}

	// todo 验证消息
    public function new_friend()
    {
        $res = FriendAction::app_new_friend($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 个人二维码数据
    public function qrcode_data()
    {
        $personal_uid = input('post.personal_uid');
        $res = FriendAction::app_qrcode_data($personal_uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 修改好友其他备注
    public function remark_set()
    {
        $fid = input('post.fid');
        $remark = input('post.remark');
        $res = FriendAction::app_remark_set($this->uid , $fid , $remark);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 修改 uname
    public function uname_set()
    {
        $fid = input('post.fid');
        $uname = input('post.uname');
        $res = FriendAction::app_uname_set($this->uid , $fid , $uname);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 设置黑名单
    public function black_set()
    {
        $fid = input('post.fid');
        $black = input('post.black');
        $res = FriendAction::app_black_set($this->uid , $fid , $black);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 共同群组列表
    public function share_group_list()
    {
        $fid = input('post.fid');
        $res = FriendAction::app_share_group($this->uid , $fid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl
    public function friend_and_group()
    {
        $res = FriendAction::app_friend_and_group($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl
    public function recent_friend_and_group()
    {
        $res = FriendAction::app_recent_friend_and_group($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
}
