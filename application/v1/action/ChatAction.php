<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\action\UnreadAction;
use app\v1\model\ChatBlacklistModel;
use app\v1\model\ChatGmessageModel;
use app\v1\model\ChatMessageModel;
use app\v1\model\GroupDisturb;
use app\v1\model\MessageReadStatusModel;
use app\v1\model\SensitiveWordModel;
use app\v1\redis\BaseRedis;
use app\v1\util\Notification;
use app\v1\util\Response;
use app\v1\util\Websocket;
use Exception;
use Net;
use think\Db;

/**
 * Description of ChatAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class ChatAction {
	//put your code here
	public static function app_chatid_gen($uid1, $uid2) {
//		return ($uid1 < $uid2) ? $uid1 . '_' . $uid2 : $uid2 . '_' . $uid1;
		if ($uid1 < $uid2) {
			return $uid1 . '_' . $uid2;
		} else {
			return $uid2 . '_' . $uid1;
		}
	}

	public static function app_create_chat($sender_uid, $reciver_uid) {
		$chat_id = ChatAction::app_chatid_gen($sender_uid, $reciver_uid);
		$count = \app\v1\model\SingleChatModel::api_count_byChatId($chat_id);
//		dump($count);
		if ($count < 2) {
			\think\Db::startTrans();
			if ($count > 0) {
				if (!\app\v1\model\SingleChatModel::api_delete_byChatId($chat_id)) {
					\think\Db::rollback();
					return [
						'code' => 1,
						'data' => 'SingleChatClearFail'
					];
				}
			}
			if (\app\v1\model\SingleChatModel::api_insert($sender_uid, $reciver_uid, $chat_id) && \app\v1\model\SingleChatModel::api_insert($reciver_uid, $sender_uid, $chat_id)) {
				\think\Db::commit();
				return [
					'code' => 0,
					'data' => 'success'
				];
			} else {
				\think\Db::rollback();
				return [
					'code' => 1,
					'data' => 'SingleChatCreateFail',
				];
			}
			return [
				'code' => 403,
				'data' => '他还不是你的好友',
			];
		} else {

		}
	}

	public static function app_send($sender_uid, $reciver_uid, $type, $message, $extra = null) {
        $chat_id = self::app_chatid_gen($sender_uid, $reciver_uid);
        $key = BaseRedis::key('private_msg' , $chat_id);
        cache($key , null , 1);
        // todo 敏感词汇过滤

		// by cxl 做转义
		$message = htmlspecialchars($message);
		if (!FriendAction::app_is_friend($sender_uid, $reciver_uid)) {
			return [
				'code' => 1,
				'data' => '还不是好友'
			];
		}
        // 检查是否存在敏感词汇
        $is_forbidden = SensitiveWordModel::isForbidden($message);
		if ($is_forbidden) {
		    // 存在敏感词汇
            return [
                'code' => 403 ,
                'data' => '存在敏感词汇，不允许发送' ,
            ];
        }
		// 做法：删除旧数据 && 插入新数据
		$ret = self::app_create_chat($sender_uid, $reciver_uid);
		if ($ret['code'] != 0) {
			return $ret;
		}
		// by cxl
		$merge = [];
		if (in_array($type, config('business.media_type'))) {
			// 语音消息
			$merge = [
				// todo 频道名称，也许名称仅支持字母
				'channel' => random(30, 'number', true),
			];
			// 保存频道到数据库
			$extra = json_encode($merge); // $merge['channel'];
		}
		try {
			Db::startTrans();
			$chat_id = self::app_chatid_gen($sender_uid, $reciver_uid);
//			$lastid = ChatMessageModel::api_insert($chat_id, $sender_uid, $message, $type, $extra,$flag,$expire);
			$lastid = ChatMessageModel::api_insert($chat_id, $sender_uid, $message, $type, $extra);
			if ((bool) $lastid === false) {
				Db::rollback();
				return [
					'code' => 1,
					'data' => '发送失败'
				];
			}
			// 检查是否被加入黑名单 type = 32
			$black = ChatBlacklistModel::api_count($chat_id, $sender_uid);
			if ($black > 0) {
				// 被 sender 加入了黑名单
				$type = 32;
				// 加入到被删除的消息列表中
				$res = MessageAction::app_msg_exclude_user_set($reciver_uid, $lastid);
				// 更新消息类型
				ChatMessageModel::api_type_set($lastid, $type);
				if ($res['code'] != 0) {
					Db::rollback();
					return $res;
				}
				$msg = '发送成功，但被对方拒收';
			} else {
				// 没有被加入黑名单
				$user_info = MemberAction::app_user_info($sender_uid);
				$data = [
					'user_info' => $user_info,
					'chat_id' => $chat_id,
					'sender' => $sender_uid,
					'reciever' => $reciver_uid,
					'message' => $message,
					'extra' => $extra,
					'type' => $type,
//                    'flag' => $flag ,
//                    'expire' => $expire ,
				];
				$push = [
					'module' => 'chat',
					'type' => 'private_chat',
					'data' => $data,
				];
				// todo 生产环境请务必放开注释
				// by cxl 检查用户是否开启消息推送
				if (UserAction::app_can_notice($reciver_uid)) {
				    if (!in_array($type , config('business.media_type'))) {
                        \Push\APush::push_single($reciver_uid, $message, '你收到了一条好友消息', $push);
                    }
				}
				MessageAction::app_unread_alert_set($reciver_uid, $message);
				// 新增未读消息数
				UnreadAction::add_unread($reciver_uid, $sender_uid);
				if (!in_array($type, config('business.packet_type_range'))) {
					// 红包消息比较特殊
					// 推送给前端的消息
                    // 发送方：已读
                    MessageReadStatusModel::api_insert($sender_uid, 'private', $lastid, 1);
                    // 接收方：未读
                    MessageReadStatusModel::api_insert($reciver_uid, 'private', $lastid, 0);
					$msg_data = ChatMessageModel::api_find_byMessageId($lastid);
					$msg_data = MessageAction::app_msg_handle($sender_uid, $msg_data);
                    $msg_data['is_read'] = 0;
					// 发送 websocket 服务器
					$res = Websocket::send([$sender_uid , $reciver_uid] , $msg_data , $chat_id);
//					print_r('websocket 发送结果:');
//					print_r($res);
					if ($res['code'] != 0) {
						Db::rollback();
						return [
							'code' => $res['code'],
							'data' => $res['data']
						];
					}
					// 发送 websocket 服务器
                    $res = Websocket::refresh([$sender_uid , $reciver_uid] , $chat_id);
					if ($res['code'] != 0) {
						Db::rollback();
						return [
							'code' => $res['code'],
							'data' => $res['data']
						];
					}
				} else {
                    // by cxl 消息已读/未读
                    // 发送方：已读
                    MessageReadStatusModel::api_insert($sender_uid, 'private', $lastid, 1);
                    // 接收方：未读
                    MessageReadStatusModel::api_insert($reciver_uid, 'private', $lastid, 0);
                }
				$msg = '发送成功';
			}
			// 发送成功后获取最新一条消息
			Db::commit();
			return array_merge([
				'code' => 0,
				'data' => $msg,
				'last_id' => $lastid,
					], $merge);
		} catch (Exception $e) {
			Db::rollback();
			throw $e;
		}
	}

	// 私聊-发送 websocket
	public static function app_send_websocket($msg_id) {
		$msg_data = ChatMessageModel::api_find_byMessageId($msg_id);
		$msg_data = MessageAction::app_msg_handle($msg_data['sender'], $msg_data);
		$uids = explode('_', $msg_data['chat_id']);
		$res = Websocket::send($uids , $msg_data , $msg_data['chat_id']);
		if ($res['code'] != 0) {
			return [
				'code' => $res['code'],
				'data' => 'websocket 推送失败：' . $res['data']
			];
		}
        $res = Websocket::refresh($uids , $msg_data['chat_id']);
        if ($res['code'] != 0) {
            Db::rollback();
            return [
                'code' => $res['code'],
                'data' => $res['data']
            ];
        }
		return [
			'code' => 0,
			'data' => 'websocket 推送成功',
		];
	}

	// 私聊-发送 websocket
	public static function app_send_group_websocket($msg_id) {
		// 推送给前端的消息
		$msg_data = ChatGmessageModel::api_find_byMessageId($msg_id);
		$uids = GroupAction::app_group_uids($msg_data['gid']);
		$msg_data = MessageAction::app_group_msg_handle($msg_data['uid'], $msg_data);
        $res = Websocket::groupSend($uids , $msg_data , $msg_data['gid']);
		if ($res['code'] != 0) {
			return [
				'code' => $res['code'],
				'data' => 'websocket服务器返回数据：' . $res['data']
			];
		}
        $res = Websocket::refresh($uids , $msg_data['gid']);
        if ($res['code'] != 0) {
            Db::rollback();
            return [
                'code' => $res['code'],
                'data' => $res['data']
            ];
        }
		return [
			'code' => 0,
			'data' => 'websocket 推送成功',
		];
	}

	// 私聊-列表刷新
	public static function app_send_refresh_list_websocket($user_id, $chat_id) {
        $res = Websocket::refresh([$user_id] , $chat_id);
		if ($res['code'] != 0) {
			return [
				'code' => $res['code'],
				'data' => $res['data']
			];
		}
		return [
			'code' => 0,
			'data' => ''
		];
	}

	// 群聊-列表刷新
	public static function app_send_group_refresh_list_websocket($user_id, $gid)
    {
        $res = Websocket::groupRefresh([$user_id] , $gid);
		if ($res['code'] != 0) {
			return [
				'code' => $res['code'],
				'data' => $res['data']
			];
		}
		return [
			'code' => 0,
			'data' => ''
		];
	}

//	public static function app_group_send($uid, $gid, $type, $message, $extra = null,$flag=0,$expire=null) {
	public static function app_group_send($uid, $gid, $type, $message, $extra = null) {
//	    var_dump($message);
	    // 清除缓存
        $key = BaseRedis::key('group_message' , $gid);
        cache($key , null , 1);
		if (!GroupAction::app_in_group($gid, $uid)) {
			return [
				'code' => 403,
				'data' => '你还不在这个群里'
			];
		}
        // 检查是否存在敏感词汇
        $is_forbidden = SensitiveWordModel::isForbidden($message);
        if ($is_forbidden) {
            // 存在敏感词汇
            return [
                'code' => 403 ,
                'data' => '存在敏感词汇，不允许发送' ,
            ];
        }
		// by cxl
		$merge = [];
		if (in_array($type, config('business.media_type'))) {
			// 语音消息
			$merge = [
				// todo 频道名称，也许名称仅支持字母
				'channel' => random(30 , 'number', true),
			];
			// 保存频道到数据库
			$extra = $merge['channel'];
		}
		//

		//todo if this group has been dismissed ,the message cant be send out successfully,
		//todo if this group has been shut down by admin,the message cant be send out successfully,
		try {
			Db::startTrans();
//			$lastid = \app\v1\model\ChatGmessageModel::api_insert($gid, $uid, $message, $type, $extra,$flag,$expire);
			$lastid = \app\v1\model\ChatGmessageModel::api_insert($gid, $uid, $message, $type, $extra);
			if (!$lastid) {
				Db::rollback();
				return [
					'code' => 1,
					'data' => '发送失败'
				];
			}
			// 推送
			UnreadAction::group_add_unread($gid);
			// by cxl 发送人消息清零
            UnreadAction::group_set_lastid($uid, $gid, UnreadAction::group_get_msgid($gid));
			UnreadAction::group_set_msg_time($gid);
			$uids = GroupAction::app_group_uids($gid);
			$all_uids = $uids;
			$exclude_uids = [];
			// 发送方：已读
			MessageReadStatusModel::api_insert($uid, 'private', $lastid, 1);
			array_walk($uids, function($v) use(&$exclude_uids, $gid, $lastid) {
				// 接收方：未读
				MessageReadStatusModel::api_insert($v, 'group', $lastid, 0);
				// 检查是否允许打扰 || 推送用户是否开启推送通知
				$disturb = GroupDisturb::api_count($gid, $v);
				$can_notice = UserAction::app_can_notice($v);
				if ($disturb > 0 || !$can_notice) {
					// 免打扰
					$exclude_uids[] = $v;
				}
			});
			$uids = array_diff($uids, [$uid], $exclude_uids);
			$group_info = GroupAction::app_group_info($gid);
			$data = [
				'group_info' => $group_info,
				'gid' => $gid,
				'uid' => $uid,
				'message' => $message,
				'extra' => $extra,
				'type' => $type,
//                'flag' => $flag,
//                'expire' => $expire,
			];
			$push = [
				'module' => 'chat',
				'type' => 'group_chat',
				'data' => $data,
			];
			// 发送 websocket 服务器
//			if (!empty($uids)) {
				if (!in_array($type, config('business.packet_type_range'))) {
					// 推送给前端的消息
					$msg_data = ChatGmessageModel::api_find_byMessageId($lastid);
					$msg_data = MessageAction::app_group_msg_handle($uid, $msg_data);
                    $msg_data['is_read'] = 0;
//                    print_r($msg_data);
					$res = Websocket::groupSend($all_uids , $msg_data , $gid);
					if ($res['code'] != 0) {
						Db::rollback();
						return [
							'code' => $res['code'],
							'data' => 'websocket服务器返回数据：' . $res['data']
						];
					}
					$res = Websocket::refresh($all_uids , $gid);
					if ($res['code'] != 0) {
						Db::rollback();
						return [
							'code' => $res['code'],
							'data' => 'websocket服务器返回数据：' . $res['data']
						];
					}
				}
//			}
            if (!in_array($type , config('business.media_type'))) {
                \Push\APush::push_more($uids, $message, '你收到了一条群聊消息', $push);
            }
			// by cxl，取消屏蔽群组
			MessageAction::app_group_hide_del($gid);
			Db::commit();
			return array_merge([
				'code' => 0,
				'data' => '发送成功' ,
				'last_id' => $lastid,
					], $merge);
		} catch (Exception $e) {
			Db::rollback();
			throw $e;
		}
	}

    /**
     * ****************************
     * 语音通话-start
     * ****************************
     */
	// by cxl 推送通知给定 uid 加入语音聊天
	public static function join_voice($sender, $msg_type, $msg_id, $channel, $uid = '', $uids = []) {
		if (empty($uids)) {
			// 推送给单人
			Notification::voice($sender, $uid, $msg_type, $msg_id, $channel);
			return [
				'code' => 0,
				'data' => '',
			];
		}
		$uids = json_decode($uids, true);
		if (empty($uids)) {
			return [
				'code' => 400,
				'data' => 'uids 参数错误'
			];
		}
		$user = [];
		foreach ($uids as $v)
        {
            $user[] = MemberAction::app_user_info($v);
        }
		foreach ($uids as $v)
		{
            if ($v == $sender) {
                continue ;
            }
			Notification::voice($sender, $v, $msg_type, $msg_id, $channel , $user);
		}
		return [
			'code' => 0,
			'data' => ''
		];
	}

	// by cxl 推送通知给定 uid 用户已经加入频道
	public static function joined_voice($sender, $msg_type, $msg_id, $uid = '', $uids = []) {
		if (empty($uids)) {
			// 推送给单人
			Notification::joined_voice($sender, $uid, $msg_type, $msg_id);
			return [
				'code' => 0,
				'data' => '',
			];
		}
		$uids = json_decode($uids, true);
		if (empty($uids)) {
			return [
				'code' => 400,
				'data' => 'uids 参数错误'
			];
		}
		foreach ($uids as $v) {
			Notification::joined_voice($sender, $v, $msg_type, $msg_id);
		}
		return [
			'code' => 0,
			'data' => ''
		];
	}

	// by cxl 通知给定用户-用户拒绝加入
	public static function refuse_voice($sender, $msg_type, $msg_id, $uid = '', $uids = []) {
	    $type_range = ['private' , 'group'];
	    if (!in_array($msg_type , $type_range)) {
	        return Response::error('msg_type 参数错误！');
        }
		if ($msg_type == 'private') {
		    // 私聊
		    $chat_id = self::app_chatid_gen($sender , $uid);
		    $res = Websocket::refresh([$sender , $uid] , $chat_id);
		    if ($res['code'] != 0) {
		        return Response::wsError($res['data'] , $res['code']);
            }
			// 推送给单人
			Notification::refuse_voice($sender, $uid, $msg_type, $msg_id);
			return [
				'code' => 0,
				'data' => '',
			];
		}
		// 群聊
		$uids = json_decode($uids, true);
		if (empty($uids)) {
			return [
				'code' => 400,
				'data' => 'uids 参数错误'
			];
		}
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        $res = Websocket::groupRefresh($uids , $msg['gid']);
        if ($res['code'] != 0) {
            return Response::wsError($res['data'] , $res['code']);
        }
		foreach ($uids as $v) {
			Notification::refuse_voice($sender, $v, $msg_type, $msg_id);
		}
		return [
			'code' => 0,
			'data' => ''
		];
	}

	// by cxl 更新语音消息状态
	public static function set_voice_msg_type($msg_id, $msg_type) {
		$msg = ChatMessageModel::api_find_byMessageId($msg_id);
		if (empty($msg)) {
			return [
				'code' => 404,
				'data' => '未找到当前消息id对应消息记录',
			];
		}
		// 检查是否是语音消息
		if (!in_array($msg['type'], config('business.voice_type'))) {
			return [
				'code' => 403,
				'data' => '你提供的消息并非语音消息，无法更改状态',
			];
		}
		if (!in_array($msg_type, config('business.voice_type'))) {
			return [
				'code' => 400,
				'data' => 'msg_type 参数错误'
			];
		}
		ChatMessageModel::api_updateById($msg_id, [
			'type' => $msg_type,
			'message' => $msg_type == 5 ? '通话结束' : '通话失败',
		]);
		$msg = ChatMessageModel::api_find_byMessageId($msg_id);
		return [
			'code' => 0,
			'data' => $msg
		];
	}

	// by cxl 更新语音消息状态
	public static function set_group_voice_msg_type($msg_id, $msg_type , $user_id) {
		$msg = ChatGmessageModel::api_find_byMessageId($msg_id);
		if (empty($msg)) {
			return [
				'code' => 404,
				'data' => '未找到当前消息id对应消息记录',
			];
		}
		// 检查是否是语音消息
		if (!in_array($msg['type'], config('business.voice_type'))) {
			return [
				'code' => 403,
				'data' => '你提供的消息并非语音消息，无法更改状态',
			];
		}
		if (!in_array($msg_type, config('business.voice_type'))) {
			return [
				'code' => 400,
				'data' => 'msg_type 参数错误'
			];
		}
        ChatGmessageModel::api_updateById($msg_id, [
            'type' => $msg_type,
            'message' => $msg_type == 5 ? '通话结束' : '通话失败',
        ]);
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        self::app_send_group_refresh_list_websocket($user_id , $msg['gid']);
        return [
            'code' => 0,
            'data' => $msg
        ];
	}

	// 私聊-记录开始时间
	public static function app_log_voice_s_time($msg_id, $s_time) {
		$res = ChatMessageModel::api_find_byMessageId($msg_id);
		if (empty($res)) {
			return [
				'code' => 404,
				'data' => '未找到该条消息'
			];
		}
		if (!in_array($res['type'], config('business.voice_type'))) {
			return [
				'code' => 400,
				'data' => '你提供的消息类型错误，请提供语音消息',
			];
		}
		$extra = json_decode($res['extra'], true);
		if (empty($extra)) {
			$extra = [
				's_time' => $s_time,
				'e_time' => $s_time,
				'duration' => 0
			];
			$extra = json_encode($extra);
		} else {
			$extra = array_merge($extra, [
				's_time' => $s_time,
				'e_time' => $s_time,
				'duration' => 0
			]);
			$extra = json_encode($extra);
		}
		ChatMessageModel::api_updateById($msg_id, [
			'extra' => $extra
		]);
		return [
			'code' => 0,
			'data' => ''
		];
	}

	// 私聊-记录结束时间
	public static function app_log_voice_e_time($msg_id, $e_time, $duration) {
		$res = ChatMessageModel::api_find_byMessageId($msg_id);
		if (empty($res)) {
			return [
				'code' => 404,
				'data' => '未找到该条消息'
			];
		}
		if (!in_array($res['type'], config('business.voice_type'))) {
			return [
				'code' => 400,
				'data' => '你提供的消息类型错误，请提供语音消息',
			];
		}
		$extra = json_decode($res['extra'], true);
		if (empty($extra)) {
			$extra = [
				's_time' => $e_time,
				'e_time' => $e_time,
				'duration' => $duration
			];
			$extra = json_encode($extra);
		} else {
            $extra['e_time'] = empty($e_time) ? $extra['e_time'] : $e_time;
            $extra['duration'] = empty($duration) ? $extra['duration'] : $duration;
			$extra = json_encode($extra);
		}
		ChatMessageModel::api_updateById($msg_id, [
			'extra' => $extra
		]);
		return [
			'code' => 0,
			'data' => ''
		];
	}

    // 私聊-记录开始时间
    public static function app_log_group_voice_s_time($msg_id, $s_time) {
        $res = ChatGmessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.voice_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供语音消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ];
            $extra = json_encode($extra);
        } else {
            $extra = array_merge($extra, [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ]);
            $extra = json_encode($extra);
        }
        ChatGmessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // 私聊-记录结束时间
    public static function app_log_group_voice_e_time($msg_id, $e_time, $duration) {
        $res = ChatGmessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.voice_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供语音消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $e_time,
                'e_time' => $e_time,
                'duration' => $duration
            ];
            $extra = json_encode($extra);
        } else {
            $extra['e_time'] = empty($e_time) ? $extra['e_time'] : $e_time;
            $extra['duration'] = empty($duration) ? $extra['duration'] : $duration;
            $extra = json_encode($extra);
        }
        ChatGmessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }
    /**
     * ****************************
     * 语音通话-end
     * ****************************
     */


    /**
     * ****************************
     * 视频通话-start
     * ****************************
     */
    // by cxl 推送通知给定 uid 加入语音聊天
    public static function join_video($sender, $msg_type, $msg_id, $channel, $uid = '', $uids = []) {
        if (empty($uids)) {
            // 推送给单人
            Notification::video($sender, $uid, $msg_type, $msg_id, $channel);
            return [
                'code' => 0,
                'data' => '',
            ];
        }
        $uids = json_decode($uids, true);
        if (empty($uids)) {
            return [
                'code' => 400,
                'data' => 'uids 参数错误'
            ];
        }
        foreach ($uids as $v) {
            Notification::video($sender, $v, $msg_type, $msg_id, $channel);
        }
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // by cxl 推送通知给定 uid 用户已经加入频道
    public static function joined_video($sender, $msg_type, $msg_id, $uid = '', $uids = []) {
        if (empty($uids)) {
            // 推送给单人
            Notification::joined_video($sender, $uid, $msg_type, $msg_id);
            return [
                'code' => 0,
                'data' => '',
            ];
        }
        $uids = json_decode($uids, true);
        if (empty($uids)) {
            return [
                'code' => 400,
                'data' => 'uids 参数错误'
            ];
        }
        foreach ($uids as $v) {
            Notification::joined_video($sender, $v, $msg_type, $msg_id);
        }
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // by cxl 通知给定用户-用户拒绝加入
    public static function refuse_video($sender, $msg_type, $msg_id, $uid = '', $uids = []) {
        $type_range = ['private' , 'group'];
        if (!in_array($msg_type , $type_range)) {
            return Response::error('msg_type 参数错误！');
        }
        if ($msg_type == 'private') {
            // 私聊
            $chat_id = self::app_chatid_gen($sender , $uid);
            $res = Websocket::refresh([$sender , $uid] , $chat_id);
            if ($res['code'] != 0) {
                return Response::wsError($res['data'] , $res['code']);
            }
            // 推送给单人
            Notification::refuse_video($sender, $uid, $msg_type, $msg_id);
            return [
                'code' => 0,
                'data' => '',
            ];
        }
        // 群聊
        $uids = json_decode($uids, true);
        if (empty($uids)) {
            return [
                'code' => 400,
                'data' => 'uids 参数错误'
            ];
        }
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        $res = Websocket::groupRefresh($uids , $msg['gid']);
        if ($res['code'] != 0) {
            return Response::wsError($res['data'] , $res['code']);
        }
        foreach ($uids as $v) {
            Notification::refuse_video($sender, $v, $msg_type, $msg_id);
        }
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // by cxl 更新语音消息状态
    public static function set_video_msg_type($msg_id, $msg_type) {
        $msg = ChatMessageModel::api_find_byMessageId($msg_id);
        if (empty($msg)) {
            return [
                'code' => 404,
                'data' => '未找到当前消息id对应消息记录',
            ];
        }
        // 检查是否是语音消息
        if (!in_array($msg['type'], config('business.video_type'))) {
            return [
                'code' => 403,
                'data' => '你提供的消息并非视频消息，无法更改状态',
            ];
        }
        if (!in_array($msg_type, config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => 'msg_type 参数错误'
            ];
        }
        ChatMessageModel::api_updateById($msg_id, [
            'type' => $msg_type,
            'message' => $msg_type == 51 ? '视频通话结束' : '视频通话失败',
        ]);
        $msg = ChatMessageModel::api_find_byMessageId($msg_id);
        return [
            'code' => 0,
            'data' => $msg
        ];
    }

    // by cxl 更新语音消息状态
    public static function set_group_video_msg_type($msg_id, $msg_type) {
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        if (empty($msg)) {
            return [
                'code' => 404,
                'data' => '未找到当前消息id对应消息记录',
            ];
        }
        // 检查是否是语音消息
        if (!in_array($msg['type'], config('business.video_type'))) {
            return [
                'code' => 403,
                'data' => '你提供的消息并非视频消息，无法更改状态',
            ];
        }
        if (!in_array($msg_type, config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => 'msg_type 参数错误'
            ];
        }
        ChatGmessageModel::api_updateById($msg_id, [
            'type' => $msg_type,
            'message' => $msg_type == 51 ? '视频通话结束' : '视频通话失败',
        ]);
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        // todo 列表刷新推送
        return [
            'code' => 0,
            'data' => $msg
        ];
    }

    // 私聊-记录开始时间
    public static function app_log_video_s_time($msg_id, $s_time) {
        $res = ChatMessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供视频消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ];
            $extra = json_encode($extra);
        } else {
            $extra = array_merge($extra, [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ]);
            $extra = json_encode($extra);
        }
        ChatMessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // 私聊-记录结束时间
    public static function app_log_video_e_time($msg_id, $e_time, $duration) {
        $res = ChatMessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供视频消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $e_time,
                'e_time' => $e_time,
                'duration' => $duration
            ];
            $extra = json_encode($extra);
        } else {
            $extra['e_time'] = empty($e_time) ? $extra['e_time'] : $e_time;
            $extra['duration'] = empty($duration) ? $extra['duration'] : $duration;
            $extra = json_encode($extra);
        }
        ChatMessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // 群聊-视频-记录开始时间
    public static function app_log_group_video_s_time($msg_id, $s_time) {
        $res = ChatGmessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供视频消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ];
            $extra = json_encode($extra);
        } else {
            $extra = array_merge($extra, [
                's_time' => $s_time,
                'e_time' => $s_time,
                'duration' => 0
            ]);
            $extra = json_encode($extra);
        }
        ChatGmessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }

    // 群聊-视频-记录结束时间
    public static function app_log_group_video_e_time($msg_id, $e_time, $duration) {
        $res = ChatGmessageModel::api_find_byMessageId($msg_id);
        if (empty($res)) {
            return [
                'code' => 404,
                'data' => '未找到该条消息'
            ];
        }
        if (!in_array($res['type'], config('business.video_type'))) {
            return [
                'code' => 400,
                'data' => '你提供的消息类型错误，请提供视频消息',
            ];
        }
        $extra = json_decode($res['extra'], true);
        if (empty($extra)) {
            $extra = [
                's_time' => $e_time,
                'e_time' => $e_time,
                'duration' => $duration
            ];
            $extra = json_encode($extra);
        } else {
            $extra['e_time'] = empty($e_time) ? $extra['e_time'] : $e_time;
            $extra['duration'] = empty($duration) ? $extra['duration'] : $duration;
            $extra = json_encode($extra);
        }
        ChatGmessageModel::api_updateById($msg_id, [
            'extra' => $extra
        ]);
        return [
            'code' => 0,
            'data' => ''
        ];
    }
    /**
     * ****************************
     * 视频通话-end
     * ****************************
     */

    // 通知对方
    public static function app_outside($sender , $receiver , $msg_id , $msg_type)
    {
        Notification::outside($sender , $receiver , $msg_type , $msg_id);
        return [
            'code' => 0,
            'data' => '',
        ];
    }
}
