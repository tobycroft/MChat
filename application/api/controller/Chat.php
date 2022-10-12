<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\ChatAction;
use app\v1\action\MessageAction;

use GeoIp2\Database\Reader;

/**
 * Description of Chat
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Chat extends LoginController {

	public function send_msg() {
		$fid = input('post.fid');
		$msg = input('post.msg');
		$extra = input('post.extra');
		// todo 在消息列表输出的时候设置屏蔽项目。
//		$flag = input('post.flag') ?? 0;
//		$expire = input('post.expire') ?? null;
//		$ret = ChatAction::app_send($this->uid, $fid, 1, $msg, $extra , $flag , $expire);
		$ret = ChatAction::app_send($this->uid, $fid, 1, $msg, $extra);
		if ($ret['code'] == 0) {
//			\app\v1\action\UnreadAction::add_unread($fid, $this->uid);
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function send_img() {
		$fid = input('post.fid');
		$msg = input('post.msg');
		$msg = '好友向你发送了一张图片';
		$extra = input('post.extra');
		$ret = ChatAction::app_send($this->uid, $fid, 2, $msg, $extra);
		if ($ret['code'] == 0) {
//			\app\v1\action\UnreadAction::add_unread($fid, $this->uid);
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function send_rec() {
		$fid = input('post.fid');
		$msg = '好友向你发送了一条语音';
		$extra = input('post.extra');
		$ret = ChatAction::app_send($this->uid, $fid, 3, $msg, $extra);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// by cxl 发送视频
    public function send_video() {
        $fid = input('post.fid');
        $msg = '好友向你发送了一条视频';
        $extra = input('post.extra');
        $ret = ChatAction::app_send($this->uid, $fid, 8, $msg, $extra);
        if ($ret['code'] == 0) {
            $this->succ($ret['data']);
        } else {
            $this->fail($ret['data'], $ret['code']);
        }
    }

	public function send_group_msg() {
		$gid = input('post.gid');
		$msg = input('post.msg');
//		$msg = str_replace('http', '', $msg);
		$extra = input('post.extra');
		$ret = ChatAction::app_group_send($this->uid, $gid, 1, $msg, $extra);
		if ($ret['code'] == 0) {
//			\app\v1\action\UnreadAction::add_unread($fid, $this->uid);
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function send_group_img() {
		$gid = input('post.gid');
		$msg = input('post.msg');
		$msg = '你收到了一张群图片';
		$extra = input('post.extra');
		$ret = ChatAction::app_group_send($this->uid, $gid, 2, $msg, $extra);
		if ($ret['code'] == 0) {
//			\app\v1\action\UnreadAction::add_unread($fid, $this->uid);
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function send_group_rec() {
		$gid = input('post.gid');
		$msg = input('post.msg');
		$msg = '你收到了一条群语音';
		$extra = input('post.extra');
		$ret = ChatAction::app_group_send($this->uid, $gid, 3, $msg, $extra);
		if ($ret['code'] == 0) {
//			\app\v1\action\UnreadAction::add_unread($fid, $this->uid);
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// by cxl 发送视频文件
    public function send_group_video() {
        $gid = input('post.gid');
        $msg = '你收到了一条群视频';
        $extra = input('post.extra');
        $ret = ChatAction::app_group_send($this->uid, $gid, 8, $msg, $extra);
        if ($ret['code'] != 0) {
            $this->fail($ret['data'], $ret['code']);
        }
        $this->succ($ret['data']);
    }

    /**
     * ****************************
     * 语音通话 start
     * ****************************
     */
    // by cxl 私聊-语音聊天
    public function voice_chat() {
        $fid = input('post.fid');
        $msg = '语音通话';
        // type = 4，语音通信-失败状态
        $ret = ChatAction::app_send($this->uid, $fid, 4, $msg);
        if ($ret['code'] == 0) {
            $this->succ([
                'data'      => $ret['data'] ,
                'channel'   => $ret['channel'] ,
                'msg_type'  => 'private' ,
                'msg_id'    => $ret['last_id'] ,
            ]);
        }
        $this->fail($ret['data'], $ret['code']);
    }

    // by cxl 群聊-语音聊天
    public function group_voice_chat()
    {
        $gid = input('post.gid');
        $msg = '语音通话';
        $ret = ChatAction::app_group_send($this->uid, $gid, 4, $msg);
        if ($ret['code'] == 0) {
            $this->succ([
                'data'      => $ret['data'] ,
                'channel'   => $ret['channel'] ,
                'msg_type'  => 'group' ,
                'msg_id'    => $ret['last_id'] ,
            ]);
        }
        $this->fail($ret['data'], $ret['code']);
    }

    // by cxl 私聊|群聊静默推送通知-加入语音聊天
    public function join_voice()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $channel = input('post.channel');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::join_voice($this->uid , $msg_type , $msg_id , $channel , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 私聊|群聊静默推送通知-加入语音聊天
    public function joined_voice()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::joined_voice($this->uid , $msg_type , $msg_id , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 私聊|群聊静默推送通知-加入语音聊天
    public function refuse_voice()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::refuse_voice($this->uid , $msg_type , $msg_id , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 私聊-更新语音聊天状态
    public function set_voice_msg_type()
    {
        $msg_id = input('post.msg_id');
        $msg_type = input('post.msg_type');
        $res = ChatAction::set_voice_msg_type($msg_id , $msg_type);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 群聊-更新语音聊天状态
    public function set_group_voice_msg_type()
    {
        $msg_id = input('post.msg_id');
        $msg_type = input('post.msg_type');
        $res = ChatAction::set_group_voice_msg_type($msg_id , $msg_type , $this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

	// 记录开始时间
    public function log_voice_s_time()
    {
        $msg_id = input('post.msg_id');
        // 时间戳，单位：s
        $s_time = input('post.s_time');
        $res = ChatAction::app_log_voice_s_time($msg_id , $s_time);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 记录结束时间
    public function log_voice_e_time()
    {
        $msg_id = input('post.msg_id');
        $e_time = input('post.e_time');
        $duration = input('post.duration');
        $res = ChatAction::app_log_voice_e_time($msg_id , $e_time , $duration);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 群聊-记录开始时间
    public function log_group_voice_s_time()
    {
        $msg_id = input('post.msg_id');
        // 时间戳，单位：s
        $s_time = input('post.s_time');
        $res = ChatAction::app_log_group_voice_s_time($msg_id , $s_time);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 群聊-记录结束时间
    public function log_group_voice_e_time()
    {
        $msg_id = input('post.msg_id');
        $e_time = input('post.e_time');
        $duration = input('post.duration');
        $res = ChatAction::app_log_group_voice_e_time($msg_id , $e_time , $duration);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
    /**
     * ****************************
     * 语音通话 end
     * ****************************
     */

    /**
     * *****************************
     * 视频通话 start
     * *****************************
     */
    // by cxl 私聊-语音聊天
    public function video_chat() {
        $fid = input('post.fid');
        $msg = '视频通话';
        // type = 4，语音通信-失败状态
        $ret = ChatAction::app_send($this->uid, $fid, 50, $msg);
        if ($ret['code'] == 0) {
            $this->succ([
                'data'      => $ret['data'] ,
                'channel'   => $ret['channel'] ,
                'msg_type'  => 'private' ,
                'msg_id'    => $ret['last_id'] ,
            ]);
        }
        $this->fail($ret['data'], $ret['code']);
    }

    // by cxl 群聊-语音聊天
    public function group_video_chat()
    {
        $gid = input('post.gid');
        $msg = '视频通话';
        $ret = ChatAction::app_group_send($this->uid, $gid, 50, $msg);
        if ($ret['code'] == 0) {
            $this->succ([
                'data'      => $ret['data'] ,
                'channel'   => $ret['channel'] ,
                'msg_type'  => 'group' ,
                'msg_id'    => $ret['last_id'] ,
            ]);
        }
        $this->fail($ret['data'], $ret['code']);
    }

    // by cxl 私聊|群聊静默推送通知-加入语音聊天
    public function join_video()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $channel = input('post.channel');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::join_video($this->uid , $msg_type , $msg_id , $channel , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 私聊|群聊静默推送通知-加入语音聊天
    public function joined_video()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::joined_video($this->uid , $msg_type , $msg_id , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // by cxl 私聊|群聊静默推送通知
    public function refuse_video()
    {
        $user_id = input('post.user_id');
        $user_ids = input('post.user_ids');
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $res = ChatAction::refuse_video($this->uid , $msg_type , $msg_id , $user_id , $user_ids);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 私聊-更新语音聊天状态
    public function set_video_msg_type()
    {
        $msg_id = input('post.msg_id');
        $msg_type = input('post.msg_type');
        $res = ChatAction::set_video_msg_type($msg_id , $msg_type);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 群聊-更新语音聊天状态
    public function set_group_video_msg_type()
    {
        $msg_id = input('post.msg_id');
        $msg_type = input('post.msg_type');
        $res = ChatAction::set_group_video_msg_type($msg_id , $msg_type);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 私聊-记录开始时间
    public function log_video_s_time()
    {
        $msg_id = input('post.msg_id');
        // 时间戳，单位：s
        $s_time = input('post.s_time');
        $res = ChatAction::app_log_video_s_time($msg_id , $s_time);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 私聊-记录结束时间
    public function log_video_e_time()
    {
        $msg_id = input('post.msg_id');
        $e_time = input('post.e_time');
        $duration = input('post.duration');
        $res = ChatAction::app_log_video_e_time($msg_id , $e_time , $duration);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 群聊-记录开始时间
    public function log_group_video_s_time()
    {
        $msg_id = input('post.msg_id');
        // 时间戳，单位：s
        $s_time = input('post.s_time');
        $res = ChatAction::app_log_group_video_s_time($msg_id , $s_time);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 群聊-记录结束时间
    public function log_group_video_e_time()
    {
        $msg_id = input('post.msg_id');
        $e_time = input('post.e_time');
        $duration = input('post.duration');
        $res = ChatAction::app_log_group_video_e_time($msg_id , $e_time , $duration);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
    /**
     * *****************************
     * 视频通话 end
     * *****************************
     */

    // 通知发起方：对方在国外，不支持通话
    public function outside()
    {
        $msg_type = input('post.msg_type');
        $msg_id = input('post.msg_id');
        $user_id = input('post.user_id');
        $res = ChatAction::app_outside($this->uid , $user_id , $msg_id , $msg_type , $msg_id);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 判断是否在国外
    public function isOutside()
    {
        $ip = request()->ip();
        $query = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
        $res = file_get_contents($query);
        if (empty($res)) {
            $this->fail("无法识别IP：{$ip}");
        }
        $res = json_decode($res , true);
        $country_id = 'CN';
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $res = $res['data'];
        if ($res['country_id'] != $country_id) {
            $this->succ('y');
        }
        $this->succ('n');

    }

}
