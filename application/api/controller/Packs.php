<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\PacketAction;
use app\v1\service\AuthService;
use app\v1\util\Misc;

class Packs extends LoginController {

	public function initialize() {
		parent::initialize();
		if (input('post.tkey') != 'test1936') {
			$this->fail('红包功能调试中');
		}
	}

	public function user_balance() {
		$ret = \app\v1\service\AuthService::serv_get_balance($this->uid);
		if ($ret) {
			$this->succ($ret);
		} else {
			$this->succ([]);
		}
	}

	// 创建个人红包
	public function create_single_pack() {
		$fid = input('post.fid');
		$cid = input('post.coin_id');
		$amount = abs(input('post.amount'));
		$password = input('post.password');
		$remark = input('post.remark');
		// by cxl
		$sms_code = input('post.sms_code');
		$ret = PacketAction::app_create_pack($this->uid, $fid, $cid, $amount, $password, $remark, 40 , null , null , $sms_code);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// 创建口令红包
	public function create_singlepass_pack() {
		$fid = input('post.fid');
		$cid = input('post.coin_id');
		$amount = abs(input('post.amount'));
		$password = input('post.password');
		$pass = input('post.pass');
		$remark = input('post.remark');
        // by cxl
        $sms_code = input('post.sms_code');
		$ret = PacketAction::app_create_pack($this->uid, $fid, $cid, $amount, $password, $remark, 41, $pass , null , $sms_code);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// 收取个人红包
	public function recieve_single_pack() {
		$id = input('post.id');
		$pass = input('post.pass');
		$remark = input('post.remark');
		$ret = PacketAction::app_recieve_single($this->uid, $id, $pass, $remark);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// 红包信息
	public function pack_info() {
		$fid = input('post.fid');
		$msg_id = input('post.msg_id');
		$ret = PacketAction::app_pack_info($msg_id, $this->uid, $fid);
		if ($ret) {
			$this->succ($ret['data']);
		} else {
			$this->fail();
		}
	}

	// 收取的红包列表
	public function pack_list_recv() {
		$page = input('post.page') ?? 1;
		$ret = PacketAction::app_user_pack_list($this->uid, true, false, $page);
		$this->succ($ret);
	}

	public function pack_list_send() {
		$page = input('post.page');
		$ret = PacketAction::app_user_pack_list($this->uid, false, true, $page);
		$this->succ($ret);
	}

	public function pack_list_all() {
		$page = input('post.page');
		$ret = PacketAction::app_user_pack_list($this->uid, true, true, $page);
		$this->succ($ret);
	}

	public function pack_sum() {
		$ret = PacketAction::app_user_pack_count($this->uid, 1, 1);
		$this->succ($ret);
	}

	public function create_group_pack() {
		$gid = input('post.gid');
		$cid = input('post.coin_id');
		$amount = abs(input('post.amount'));
		$password = input('post.password');
		$remark = input('post.remark');
		$num = input('post.num');
		// by cxl 红包类型
		$type = input('post.type');
        $sms_code = input('post.sms_code');
//		$verify_code = input('post.verify_code');
//		$verify_code_key = input('post.verify_code_key');
		$ret = PacketAction::app_create_group_pack($this->uid, $gid, $cid, $amount, $password, $remark, $type , $num , null , null , $sms_code);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// todo receive 而非 recieve！！单词拼错请抽空修正
	public function recieve_group_pack() {
		$id = input('post.id');
		$pass = input('post.pass');
		$remark = input('post.remark');
		$ret = PacketAction::app_recieve_group_packet($this->uid, $id, $pass, $remark);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function test() {
		$ee = PacketAction::app_group_join_pack('2', 1);
		dump($ee);
		$ret = PacketAction::app_group_exec_pack('2');
		dump($ret);
		dump(in_array('2s', $ret));
	}

	public function group_pack_info() {
		$gid = input('post.gid');
		$msg_id = input('post.msg_id');
		$ret = PacketAction::app_group_pack_info($msg_id, $this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function pack_draw() {
		$pack_id = input('post.pack_id');
		$ret = PacketAction::app_pack_draw_info($this->uid, $pack_id);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// by cxl 收取的红包，统计信息
    public function receive_pack_info()
    {
        $coin_id = input('post.coin_id');
        $year = input('post.year');
        $res = PacketAction::app_receive_pack_info($this->uid , $coin_id , $year);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'], $res['code']);
        }
    }

    // by cxl 收取的红包，红包记录
    public function receive_pack_log()
    {
        $page = input('post.page');
        $coin_id = input('post.coin_id');
        $year = input('post.year');
        $res = PacketAction::app_receive_pack_log($this->uid , $page , $coin_id , $year);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'], $res['code']);
        }
    }

    // by cxl 收取的红包，统计信息
    public function send_pack_info()
    {
        $coin_id = input('post.coin_id');
        $year = input('post.year');
        $res = PacketAction::app_send_pack_info($this->uid , $coin_id , $year);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'], $res['code']);
        }
    }

    // by cxl 收取的红包，红包记录
    public function send_pack_log()
    {
        $page = input('post.page');
        $coin_id = input('post.coin_id');
        $year = input('post.year');
        $res = PacketAction::app_send_pack_log($this->uid , $page , $coin_id , $year);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'], $res['code']);
        }
    }

    // 币种列表
    public function coin_type()
    {
        $type = input('post.type');
        $res = PacketAction::app_coin_type($this->uid , $type);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 发送短信验证码
    public function sms_code()
    {
        $type = input('post.type');
        $res = PacketAction::app_sms_code($this->uid , $type);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 获取手续费记录
    public function fee_record()
    {
        $res = PacketAction::app_fee_record($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
}
