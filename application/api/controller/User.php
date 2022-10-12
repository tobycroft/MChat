<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\UserAction;

class User extends LoginController {

	public function sync_rid() {
		$rid = input('post.rid');
		if (\Push\APush::user_sync($this->uid, $rid)) {
			$this->succ();
		} else {
			$this->fail();
		}
	}

	public function myinfo() {
		$this->succ(\app\v1\action\MemberAction::app_user_info($this->uid));
	}

	// by cxl 注释：上传资源文件，图片/语音等资源文件，统一都是该接口
	public function upload_img() {
		$ret = \Uploader\Upload::upload_img();
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function upload_img_64() {
		$ret = \Uploader\Upload::upload_img_64();
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function edit_face() {
		$face = input('post.face');
		$ret = \app\v1\action\MemberAction::app_edit_self_face($this->uid, $face);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data']);
		}
	}

	public function edit_uname() {
		$uname = input('post.uname');
		$ret = \app\v1\action\MemberAction::app_edit_self_uname($this->uid, $uname);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data']);
		}
	}

	public function edit_info() {
		$uname = input('post.uname') ?? '';
		$introduction = input('post.introduction') ?? '';
		$sex = input('post.sex') ?? 1;
		$telephone = input('post.telephone') ?? 0;
		$mail = input('post.mail') ?? '';
		$career = input('post.career') ?? '';
		$company = input('post.company') ?? '';
		$school = input('post.school') ?? '';
		$birthday = input('post.birthday') ?? '';
		$ret = \app\v1\action\MemberAction::app_edit_self_info($this->uid, $uname, $introduction, $sex, $telephone, $mail, $career, $company, $school, $birthday);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data']);
		}
	}

    public function edit_introduction() {
        $introduction = input('post.introduction') ?? '';
        $ret = \app\v1\action\MemberAction::app_edit_introduction($this->uid, $introduction);
        if ($ret['code'] == 0) {
            $this->succ();
        } else {
            $this->fail($ret['data']);
        }
    }

	// 设置推送
	public function can_notice_set() {
		$can_notice = input('post.can_notice');
		$res = UserAction::app_can_notice_set($this->uid, $can_notice);
		if ($res['code'] == 0) {
			$this->succ($res['data']);
		}
		$this->fail($res['data'], $res['code']);
	}

	// 黑名单列表
    public function userInBlack()
    {
        $res = UserAction::app_user_in_black($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    // 检查是否已经注册
    public function isRegisterForGame()
    {
        $param = $this->request->post();
        $param['type'] = $param['type'] ?? '';
        $res = UserAction::isRegisterForGame($this->uid , $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    // 注册接口
    public function registerForGame()
    {
        $param = $this->request->post();
        $param['type'] = $param['type'] ?? '';
        $param['username'] = $param['username'] ?? '';
        $param['password'] = $param['password'] ?? '';
        $res = UserAction::registerForGame($this->uid , $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    // 登录接口
    public function loginForGame()
    {
        $param = $this->request->post();
        $param['type'] = $param['type'] ?? '';
        $res = UserAction::loginForGame($this->uid , $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    // 查询游戏账户余额
    public function balanceForGame()
    {
        $param = $this->request->post();
        $res = UserAction::balanceForGame($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    // 转账
    public function transferForGame()
    {
        $param = $this->request->post();
        $param['amount'] = $param['amount'] ?? 0;
        $res = UserAction::transferForGame($this->uid, $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }

    public function userForGame()
    {
        $res = UserAction::userForGame($this->uid , 1);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'], $res['code']);
    }


}
