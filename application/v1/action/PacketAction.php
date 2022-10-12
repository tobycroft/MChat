<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\ChatGmessageModel;
use app\v1\model\ChatMessageModel;
use app\v1\model\PacketLogModel;
use app\v1\model\RedPacketModel;
use app\v1\model\SmsCodeForPacket;
use app\v1\model\UserInfoModel;
use app\v1\service\AuthService;
use app\v1\service\SmsService;
use app\v1\util\Misc;
use app\v1\redis\PackRedis;
use app\v1\util\VerifyCode;
use MeiLianSMS;
use SMS\ChuanglanSmsApi;
use SMS\Huyi;
use SMS\Zz253;

/**
 * Description of PacketAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class PacketAction {

	public static function app_pack_info($msg_id, $uid, $fid) {
		$chat_id = ChatAction::app_chatid_gen($uid, $fid);
		$message = ChatMessageModel::api_find($chat_id, $msg_id);
		if ($message) {
			$red = RedPacketModel::api_find_byMsgIdAdnType($message['id'], $message['type']);
			$red['user_info'] = MemberAction::app_user_info($red['sender']);
			// by cxl 获取红包币种
			$red['coin_type'] = Misc::coinType($red['cid']);
			// by cxl 获取红包领取记录
			$log = PacketLogModel::api_select_ByRedPacketId($red['id']);
			$red['amount'] = fix_number($red['amount'], config('app.len'));
			$red['already_received_amount'] = empty($log) ? 0 : $red['amount'];
			foreach ($log as &$v) {
				// 获取领取人信息
				$v['user'] = MemberAction::app_user_info($v['reciever']);
				// 仅用于前端方便数据用币种
				$v['coin_type'] = $red['coin_type'];
				// 领取时间
				$v['create_time'] = date('Y-m-d H:i:s', $v['date']);
			}
			$red['log'] = $log;

			if ($red) {
				return [
					'code' => 0,
					'data' => $red,
				];
			} else {
				return [
					'code' => 404,
					'data' => '没有找到红包',
				];
			}
		} else {
			return [
				'code' => 400,
				'data' => '没有找到消息',
			];
		}
	}

	public static function app_recieve_single($uid, $id, $pass, $remark) {
		$ret = RedPacketModel::api_find($id);
		if (!$ret) {
			return [
				'code' => 404,
				'data' => '没有这个红包',
			];
		}
		if (time() > $ret['end_time']) {
			return [
				'code' => 402,
				'data' => '红包已经过期',
			];
		}
		if ($ret['opened'] == 1) {
			return [
				'code' => 401,
				'data' => '红包已经被领取过了',
			];
		}
        if (!FriendAction::app_is_friend($uid , $ret['sender'])) {
            // by cxl 判断是否朋友
            return [
                'code' => 403 ,
                'data' => '你们不是好友，无权限领取' ,
            ];
        }
        if (FriendAction::app_is_black($ret['sender'] , $uid)) {
            // by cxl 被 sender 拉入了黑名单
            return [
                'code' => 403 ,
                'data' => '你被对方拉入了黑名单，无权限领取'
            ];
        }
		if ($ret['reciver'] != $uid) {
			return [
				'code' => 405,
				'data' => '您没有这个权限领取红包',
			];
		}
		if ($ret['type'] == 2 && $ret['pass'] != $pass) {
			return [
				'code' => 302,
				'data' => '红包口令错误',
			];
		}
		\think\Db::startTrans();
		$ui = UserInfoModel::api_find_byUid($ret['sender']);
		$recv = RedPacketModel::api_update_opened($uid, $id);
		$draw = PacketLogModel::api_insert($uid, $id, $ret['amount'], '(发送人：' . $ui['uname'] . $ui['uid'] . '-' . $uid . ')' . $ret['order_id'] . $remark, 1);
		if ($recv && $draw) {
			$order_id = $ret['order_id'];
			if (\app\v1\action\ChatAction::app_send($uid, $ret['sender'], 101, $pass, $order_id)['code'] == 0) {
				$rtt = \app\v1\service\PayService::user_fund($uid, $ret['cid'], $ret['amount'], $order_id, '(发送人：' . $ui['uname'] . $ui['uid'] . '-' . $uid . ')' . '-' . $ret['order_id'] . $remark);
//				dump($rtt);
				if ($rtt['code'] == 0) {
					\think\Db::commit();
					return [
						'code' => 0,
						'data' => '领取成功',
					];
				} else {
					return [
						'code' => $rtt['code'],
						'data' => $rtt['data'],
					];
				}
			} else {
				return [
					'code' => 500,
					'data' => '领取记录创建失败',
				];
			}
		} else {
			return [
				'code' => 503,
				'data' => '红包领取失败',
			];
		}
		\think\Db::rollback();
	}

	public static function app_create_pack($uid, $fid, $cid, $amount, $password, $remark, $type = 1, $pass = null, $order_id = null , $sms_code = null) {
		if (!$order_id) {
			$order_id = \Payment\Order::generate_order_id();
		}
		$bal = \app\v1\service\AuthService::serv_get_balance($uid);
		if ($amount < 0) {
			return [
				'code' => 400,
				'data' => '金额需要大于0',
			];
		}
		if(!FriendAction::app_is_friend($uid,$fid)){
		    return [
		        'code' => 403,
                'data' => '你们还不是好友',
            ];
        }
        if (FriendAction::app_is_black($fid , $uid)) {
            // by cxl 已经被 接收方 拉入黑名单，无法发送
            return [
                'code' => 403 ,
                'data' => '你已被对方加入黑名单！'
            ];
        }
        $user = AuthService::serv_get_userinfo($uid);
        if (empty($user)) {
            return [
                'code' => 404 ,
                'data' => '未找到当前登录用户信息' ,
            ];
        }
        // 检查验证码
//        if (!self::util_sms_code_check($user['sphone'] , 'private' , $sms_code)) {
//            return [
//                'code' => 400 ,
//                'data' => '短信验证码错误' ,
//            ];
//        }
		if (!$bal) {
			return [
				'code' => 101,
				'data' => '币种获取失败',
			];
		}
		$coininfo = [];
		foreach ($bal as $value) {
			if ($value['coin_id'] == $cid) {
				$coininfo = $value;
			}
		}
//		switch ($type) {
//			case 1:
//				$msg_type = 10;
//				break;
//			case 2:
//				$msg_type = 11;
//				break;
//
//			default:
//				$msg_type = 10;
//				break;
//		}
		\think\Db::startTrans();
        $msg = \app\v1\action\ChatAction::app_send($uid, $fid, $type, '红包消息', '');
		if ($msg['code'] == 0) {
			$last_id = $msg['last_id'];
			if (RedPacketModel::api_insert($uid, $last_id, $order_id, $fid, $type, $cid, $coininfo['coin_name'], $coininfo['img'], $amount, $remark, 1, $pass)) {
				$ret = \app\v1\service\PayService::user_pay($uid, $cid, $amount, $order_id, $password, $remark);
				if ($ret['code'] == 0) {
				    // by cxl 发送 websocket 消息
                    $res = ChatAction::app_send_websocket($last_id);
                    if ($res['code'] != 0) {
                        \think\Db::rollback();
                        // 状态码
                        return [
                            'code' => $res['code'] ,
                            'data' => $res['data']
                        ];
                    }
					\think\Db::commit();
					return [
						'code' => 0,
						'data' => '发送成功',
					];
				} else {
					\think\Db::rollback();
					return [
						'code' => $ret['code'],
						'data' => $ret['data'],
					];
				}
			} else {
				\think\Db::rollback();
				return [
					'code' => 401,
					'data' => '发送失败',
				];
			}
		} else {
			\think\Db::rollback();
			return [
				'code' => 501,
				'data' => lang('发送失败'),
			];
		}
	}

	public static function app_user_pack_list($uid, $show_recv = true, $show_send = false, $page = 1) {
		$arr = [];
		if ($show_recv) {
			$recv = PacketLogModel::api_select($uid);
			foreach ($recv as $value) {
				$value['pack_info'] = RedPacketModel::api_find($value['pack_id']);
				array_push($arr, $value);
			}
			foreach ($arr as &$value) {
				$value['user_info'] = UserInfoModel::api_find_byUid(isset($value['pack_info']['sender']) ? $value['pack_info']['sender'] : $value['pack_info']['uid']);

				if ($value['type'] < 10) {
					// 私聊红包
					$value['msg_info'] = ChatMessageModel::api_find_byMessageId($value['pack_info']['msg_id']);
				}
				if ($value['type'] >= 10) {
					// 群聊红包
					$value['msg_info'] = ChatGmessageModel::api_find_byMessageId($value['pack_info']['msg_id']);
				}
			}
		}
		if ($show_send) {
			$send = RedPacketModel::api_select_bySender($uid, $page);
			foreach ($send as $value) {
				array_push($arr, $value);
			}
			foreach ($arr as $key => $value) {

				$value['user_info'] = UserInfoModel::api_find_byUid($value['sender']);

				if ($value['type'] < 10) {
					$value['msg_info'] = ChatMessageModel::api_find_byMessageId($value['msg_id']);
				} elseif ($value['type'] < 20) {
					$value['msg_info'] = ChatGmessageModel::api_find_byMessageId($value['msg_id']);
				} else {

				}
				$arr[$key] = $value;
			}
		}
		return $arr;
	}

	public static function app_user_pack_count($uid, $show_recv = true, $show_send = false) {
		$arr = [];
		if ($show_recv) {
			$recv = RedPacketModel::api_sum_byReciever($uid);
			$arr['recv'] = $recv;
			$recv_count = 0;
			foreach ($arr['recv'] as $value) {
				$recv_count += $value['count'];
			}
			$arr['recv_count'] = $recv_count;
		}
		if ($show_send) {
			$send = RedPacketModel::api_sum_bySender($uid);
			$arr['sender'] = $send;
			$send_count = 0;
			foreach ($arr['sender'] as $value) {
				$send_count += $value['count'];
			}
			$arr['send_count'] = $send_count;
		}
		return $arr;
	}

	public static function app_group_set_packsingle_amount($last_id, $single_amount) {
		return cache('__GroupRedPack__' . '_amount_' . $last_id, $single_amount, config('app.duration'));
	}

	public static function app_group_get_packsingle_amount($last_id) {
		return cache('__GroupRedPack__' . '_amount_' . $last_id);
	}

	public static function app_group_set_maxium_num($last_id, $num) {
		return cache('__GroupRedPacket__' . '_maxnum_' . $last_id, $num, config('app.duration'));
	}

	public static function app_group_get_maxium_num($last_id) {
		return cache('__GroupRedPacket__' . '_maxnum_' . $last_id);
	}

	public static function app_group_set_current_num($last_id, $current_num = 0) {
		return cache('__GroupRedPacket__' . '_num_' . $last_id, $current_num, config('app.duration'));
	}

	public static function app_group_get_current_num($last_id) {
		return cache('__GroupRedPacket__' . '_num_' . $last_id);
	}

	public static function app_group_join_pack($last_id, $uid) {
		$redis = new \Rredis();
		return $redis->rPush('__GroupRedPacketList__' . $last_id, $uid);
	}

	public static function app_group_kick_pack($last_id, $uid) {
		$redis = new \Rredis();
		return $redis->lrem('__GroupRedPacketList__' . $last_id, 1, $uid);
	}

	public static function app_group_exec_pack($last_id) {
		$redis = new \Rredis();
		return $redis->lrange('__GroupRedPacketList__' . $last_id, 0, -1);
	}

	// 发群红包
	public static function app_create_group_pack($uid, $gid, $cid, $amount, $password, $remark, $type = 10, $num = 1, $pass = null, $order_id = null , $sms_code = null) {
	    // 查手续费

		if (!$order_id) {
			$order_id = \Payment\Order::generate_order_id();
		}
		if ($num > 50 || $num < 1) {
			return [
				'code' => 403,
				'data' => '红包个数不能超过50',
			];
		}
		if ($amount / $num < 0.01) {
			return [
				'code' => 400,
				'data' => $num . '人的红包需要金额大于' . ($num * 0.01),
			];
		}
        // by cxl
		// 10 - 群-普通红包
		// 11 - 群-口令红包（暂时不需要）
		// 12 - 群-拼手气红包
		$type_range = [10,11,12];
		if (!in_array($type, $type_range)) {
			return [
				'code' => 400,
				'data' => 'type 类型错误，支持的类型有：' . implode(', ', $type_range)
			];
		}
		$user = AuthService::serv_get_userinfo($uid);
		if (empty($user)) {
		    return [
		        'code' => 404 ,
                'data' => '未找到当前登录用户信息' ,
            ];
        }
        // 检查验证码
//        if (!self::util_sms_code_check($user['sphone'] , 'group' , $sms_code)) {
//            return [
//                'code' => 400 ,
//                'data' => '短信验证码错误' ,
//            ];
//        }
		$bal = \app\v1\service\AuthService::serv_get_balance($uid);
		if (!$bal) {
			return [
				'code' => 101,
				'data' => '币种获取失败',
			];
		}
		$coininfo = [];
		foreach ($bal as $value) {
			if ($value['coin_id'] == $cid) {
				$coininfo = $value;
			}
		}

//		switch ($type) {
//			case 1:
//				$msg_type = 10;
//				break;
//			case 2:
//				$msg_type = 11;
//				break;
//
//			default:
//				// 红包消息默认为 10
//				$msg_type = 10;
//				break;
//		}
        // by cxl
        if ($type == 10) {
            // 普通红包
            $single_amount = bcdiv($amount, $num, 4);
            $packs = array_pad([], $num, $single_amount);
        } else if ($type == 12) {
            // 拼手气红包
            $packs = decimal_random($amount, $num, 4);
            if ($packs === false) {
                return [
                    'code' => 400 ,
                    'data' => '随机分配金额失败，请调整金额和红包数量'
                ];
            }
        } else {
            // 预留
        }
		\think\Db::startTrans();
		$msg = \app\v1\action\ChatAction::app_group_send($uid, $gid, $type, '红包消息', '');
		if ($msg['code'] == 0) {
			$last_id = $msg['last_id'];
			// 红包分配
			PackRedis::createGroupPack($last_id, $packs);
			if (!self::app_group_set_maxium_num($last_id, $num)) {
				return [
					'code' => 511,
					'data' => '红包状态准备失败',
				];
			}
			if (!self::app_group_set_current_num($last_id)) {
				return [
					'code' => 512,
					'data' => '红包状态准备失败',
				];
			}
			if (RedPacketModel::api_insert($uid, $last_id, $order_id, $gid, $type, $cid, $coininfo['coin_name'], $coininfo['img'], $amount, $remark, $num, $pass, 1)) {
				$ret = \app\v1\service\PayService::user_pay($uid, $cid, $amount, $order_id, $password, $remark);
				if ($ret['code'] == 0) {
                    // by cxl 发送 websocket 消息
                    $res = ChatAction::app_send_group_websocket($last_id);
                    if ($res['code'] != 0) {
                        \think\Db::rollback();
                        // 状态码
                        return [
                            'code' => $res['code'] ,
                            'data' => $res['data']
                        ];
                    }
					\think\Db::commit();
					return [
						'code' => 0,
						'data' => '发送成功：cq_chat_gmessage.id=' . $last_id,
					];
				} else {
					return [
						'code' => $ret['code'],
						'data' => $ret['data'],
					];
				}
			} else {
				return [
					'code' => 501,
					'data' => '红包创建失败',
				];
			}
		} else {
			return [
				'code' => 401,
				'data' => '发送失败',
			];
		}
		return [
			'code' => 500,
			'data' => '红包发送失败',
		];
	}

	public static function app_recieve_group_packet($uid, $id, $pass, $remark) {
		$ret = RedPacketModel::api_find($id);
		if (!$ret) {
			return [
				'code' => 404,
				'data' => '没有这个红包',
			];
		}
		if (time() > $ret['end_time']) {
			return [
				'code' => 402,
				'data' => '红包已经过期',
			];
		}
		if (in_array($uid, self::app_group_exec_pack($ret['msg_id']))) {
			return [
				'code' => 407,
				'data' => '你已经领取过该红包了',
			];
		}
		if (!self::app_group_join_pack($ret['msg_id'], $uid)) {
			return [
				'code' => 518,
				'data' => '当前用户无法写入数据',
			];
		}
		if ($ret['type'] == 11 && $ret['pass'] != $pass) {
			return [
				'code' => 302,
				'data' => '红包口令错误',
			];
		}
		if (!GroupAction::app_is_in_group($ret['reciver'], $uid)) {
			return [
				'code' => 403,
				'data' => '你不是本群用户，无法领取此红包',
			];
		}
		$current_num = self::app_group_get_current_num($ret['msg_id']);
		if ($current_num >= self::app_group_get_maxium_num($ret['msg_id'])) {
			return [
				'code' => 404,
				'data' => '红包已经被领完',
			];
		}
		if (!self::app_group_set_current_num($ret['msg_id'], $current_num + 1)) {
			return [
				'code' => 500,
				'data' => 'Redis故障',
			];
		}
//		$amount = self::app_group_get_packsingle_amount($ret['msg_id']);
		// by cxl
		$amount = PackRedis::receiveGroupPack($ret['msg_id']);
		\think\Db::startTrans();
		$ui = \app\v1\model\GroupInfoModel::api_find_byId($ret['reciver']);
		$order_id = \Payment\Order::generate_order_id();
		$log = '领取记录创建失败';
		$draw = PacketLogModel::api_insert($uid, $id, $amount, '(群名：' . $ui['group_name'] . '，群号：' . $ui['id'] . $uid . ')' . $remark, 2);
		if ($draw) {
			if (\app\v1\action\ChatAction::app_group_send($uid, $ret['reciver'], 101, $pass, $order_id)['code'] == 0) {
				$rtt = \app\v1\service\PayService::user_fund($uid, $ret['cid'], $amount, $order_id, '(群名：' . $ui['group_name'] . '，群号：' . $ui['id'] . $uid . ')' . $remark);
//				dump($rtt);
				if ($rtt['code'] == 0) {
					\think\Db::commit();
					return [
						'code' => 0,
						'data' => '领取成功',
					];
				} else {
					$log = $rtt['data'];
				}
			} else {
				$log = '领取回执创建失败';
			}
		} else {
			$log = '领取记录创建失败';
		}
		self::app_group_set_current_num($ret['msg_id'], $current_num);
		self::app_group_kick_pack($ret['msg_id'], $uid);
		\think\Db::rollback();
		return [
			'code' => 500,
			'data' => $log,
		];
	}

	public static function app_group_pack_info($msg_id, $uid, $gid) {
		if (!GroupAction::app_is_in_group($gid, $uid)) {
			return [
				'code' => 403,
				'data' => '你不在本群无法操作',
			];
		}
		$message = ChatGmessageModel::api_find_byChatid($gid, $msg_id);
		if ($message) {
			$red = RedPacketModel::api_find_byMsgIdAdnType($message['id'], $message['type']);
			// by cxl 领取人数
			$red['num_for_redis'] = intval(self::app_group_get_current_num($message['id']));
			// by cxl 最大人数
			$red['max_number_for_redis'] = intval(self::app_group_get_maxium_num($message['id']));
			// by cxl 单个红包金额
			$red['single_red_amount'] = self::app_group_get_packsingle_amount($message['id']);
			// by cxl 红包的金额
			$red['amount'] = fix_number($red['amount'], config('app.len'));
			// by cxl 统计已经领取的金额
//            $red['already_received_amount'] = bcmul($red['single_red_amount'] , $red['num_for_redis'] , config('app.len'));
			$red['already_received_amount'] = fix_number(PacketLogModel::api_receiveTotal($red['id']), 4);
			// todo 据目前的前端来看，无需提供发红包的人的信息，考虑是否删除
			$red['user_info'] = MemberAction::app_user_info($red['sender']);
			$red['opened'] = (int) in_array($message['uid'], self::app_group_exec_pack($message['id']));
			$red['opened_status'] = $red['opened'];
			// by cxl 群信息
			$red['group'] = GroupAction::app_group_info($gid);
			// by cxl 获取红包币种
			$red['coin_type'] = Misc::coinType($red['cid']);
			// by cxl 获取红包领取记录
			$log = PacketLogModel::api_select_ByRedPacketId($red['id']);
			foreach ($log as &$v) {
				// 获取领取人信息
				$v['user'] = MemberAction::app_user_info($v['reciever']);
				// 仅用于前端方便数据用币种
				$v['coin_type'] = $red['coin_type'];
				// 领取时间
				$v['create_time'] = date('Y-m-d H:i:s', $v['date']);
			}
			$red['log'] = $log;
			if ($red) {
				return [
					'code' => 0,
					'data' => $red,
				];
			} else {
				return [
					'code' => 404,
					'data' => '没有找到红包',
				];
			}
		} else {
			return [
				'code' => 400,
				'data' => '没有找到消息',
			];
		}
	}

	public static function app_pack_draw_info($reciever, $pack_id) {
		$ret = PacketLogModel::api_find($reciever, $pack_id);
		$ret['pack_info'] = RedPacketModel::api_find($pack_id);
		$ret['user_info'] = MemberAction::app_user_info($ret['pack_info']['sender']);
		if ($ret) {
			return [
				'code' => 0,
				'data' => $ret,
			];
		} else {
			return [
				'code' => 404,
				'data' => '没有领取记录',
			];
		}
	}

	// by cxl 统计：收取的红包记录
	public static function app_receive_pack_info($uid, $coin_id, $year) {
		// 当前登录用户信息
		$user = MemberAction::app_user_info($uid);
		$count = PacketLogModel::api_count_byReceiver($uid, $coin_id, $year);
		$amount = PacketLogModel::api_amount_byReceiver($uid, $coin_id, $year);
		$amount = fix_number($amount, config('app.len'));
		$coin_type = Misc::coinType($coin_id);
		// 手气最佳
		$best_count = PacketLogModel::api_best_count_byReceiver($uid, $coin_id, $year);
		return [
			'code' => 0,
			'data' => [
				'user' => $user,
				'count' => $count,
				'best_count' => $best_count,
				'amount' => $amount,
				'coin_type' => $coin_type,
			]
		];
	}

	// by cxl 记录：收取的红包记录
	public static function app_receive_pack_log($uid, $page, $coin_id, $year) {
		// 当前登录用户信息
		$res = PacketLogModel::api_select_byReceiver($uid, $page, $coin_id, $year);
		foreach ($res as &$v) {
			// 红包发送这
			$v['sender_info'] = MemberAction::app_user_info($v['sender']);
			// 接收时间
			$v['create_time'] = date('Y-m-d H:i:s', $v['date']);
		}
		return [
			'code' => 0,
			'data' => $res
		];
	}

	// by cxl 发送的红包数量
	public static function app_send_pack_info($uid, $coin_id, $year) {
		// 当前登录用户信息
		$user = MemberAction::app_user_info($uid);
		$count = RedPacketModel::api_count_bySender($uid, $coin_id, $year);
		$amount = RedPacketModel::api_amount_bySender($uid, $coin_id, $year);
		$amount = fix_number($amount, config('app.len'));
		$coin_type = Misc::coinType($coin_id);
		return [
			'code' => 0,
			'data' => [
				'user' => $user,
				'count' => $count,
				'amount' => $amount,
				'coin_type' => $coin_type,
			]
		];
	}

	// by cxl 记录：收取的红包记录
	public static function app_send_pack_log($uid, $page, $coin_id, $year) {
		// 当前登录用户信息
		$res = RedPacketModel::api_select_bySender_v1($uid, $page, $coin_id, $year);
		foreach ($res as &$v) {

			// 红包类型
			$v['type_explain'] = self::app_red_packet_type_explain($v['type']);
			// 红包币种
			$v['coin_type'] = empty($v['coin_type']) ? config('app.coin_type') : $v['coin_type'];
			// 发送时间
			$v['create_time'] = date('Y-m-d H:i:s', $v['start_time']);
		}
		return [
			'code' => 0,
			'data' => $res
		];
	}

	// by cxl 获取红包名称
	public static function app_red_packet_type_explain($type) {
		$packet_type = config('business.packet_type');
		foreach ($packet_type as $k => $v) {
			if ($k == $type) {
				return $v;
			}
		}
		return '未知的红包类型';
	}

	// by cxl 币种类型
	public static function app_coin_type($uid, $type) {
		$type_range = ['send', 'receive'];
		if (!in_array($type, $type_range)) {
			return [
				'code' => 400,
				'data' => '币种类型错误，支持的币种类型有：' . implode(', ', $type_range)
			];
		}
		if ($type == 'send') {
			$res = RedPacketModel::api_coinType($uid);
		} else {
			$res = PacketLogModel::api_coinType($uid);
		}
		return [
			'code' => 0,
			'data' => $res
		];
	}

	// 发送短信验证码
	public static function app_sms_code($uid , $type)
    {
        $user = AuthService::serv_get_userinfo($uid);
        if (empty($user)) {
            return [
                'code' => 404 ,
                'data' => '没有找到当前登录用户绑定的手机号码' ,
            ];
        }
        $code = random(4 , 'number' , true);
        $time = time();
//        if ($user['quhao'] == '86') {
        if (true) {
            // 国内
//            $res = Zz253::send_msg($user['quhao'] , $user['sphone'] , $code);
            $res = MeiLianSMS::send($user['quhao'] , $user['sphone'] , sprintf("你的验证码是 %s" , $code));
            if ($res['code'] != 0) {
                return [
                    'code' => 500 ,
                    'data' => '发送验证码失败：' .json_encode($res) ,
                ];
            }
        } else {
            // 国际
            // 判断短信发送的次数
            $count = SmsCodeForPacket::dayCountForInternational($user['quhao'] , $user['sphone']);
            if ($count >= 5) {
                return [
                    'code' => 403 ,
                    'data' => 'Today you have been restricted to send SMS verification code, please try again tomorrow' ,
                ];
            }
            $msg = sprintf('you verification code is %s' , $code);
            $sms = new ChuanglanSmsApi();
            $res = $sms->sendInternational(sprintf('%s%s' , $user['quhao'] , $user['sphone']) , $msg);
            if ($res == false) {
                // 发送请求失败
                return [
                    'code' => 500 ,
                    'data' => '短信发送失败（curl 请求返回 false）' ,
                ];
            }
            $res = json_decode($res , true);
            if ($res['code'] != 0) {
                return [
                    'code' => 500 ,
                    'data' => [
                        'message' => '短信服务商提示短信发送失败' ,
                        'reason' => $res
                    ]
                ];
            }
        }
        if (SmsCodeForPacket::findByPhone($user['sphone'] , $type)) {
            SmsCodeForPacket::updateByPhone($user['sphone'] , $type , $code , $time);
        } else {
            SmsCodeForPacket::insert($user['sphone'] , $type , $code , $time);
        }
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

    public static function util_sms_code_check($phone , $type , $code)
    {
        $sms = SmsCodeForPacket::findByPhone($phone , $type);
        if (empty($sms)) {
            return false;
        }
        if ($sms['used'] == 'y') {
            // 使用过
            return false;
        }
        if ($sms['send_time'] + 10 * 60 < time()) {
            // 过期
            return false;
        }
        if ($sms['code'] != $code) {
            return false;
        }
        return true;
    }

    // 获取手续费记录
    public static function app_fee_record($uid)
    {
        return AuthService::serv_get_fee_record($uid);
    }

}
