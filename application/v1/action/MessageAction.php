<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\ChatGmessageRetractModel;
use app\v1\model\ChatMessageRetractModel;
use app\v1\model\GroupMemberModel;
use app\v1\model\MessageReadStatusModel;
use app\v1\model\SingleChatModel;
use app\v1\model\ChatMessageModel;
use app\v1\model\GroupMessageExcludeUserModel;
use app\v1\model\ChatGmessageModel;
use app\v1\redis\BaseRedis;
use app\v1\redis\GroupRedis;
use app\v1\util\Notification;
use app\v1\util\Websocket;
use Exception;
use think\Db;

/**
 * Description of MessageAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class MessageAction {

	public static function app_unread_alert_set($uid, $text) {
		return cache('__unread__msg__' . $uid, $text, 86400);
	}

	public static function app_unread_alert_get($uid) {
		return cache('__unread__msg__' . $uid);
	}

	public static function app_unread_alert_clear($uid) {
		return cache('__unread__msg__' . $uid, 0, 1);
	}

	// by cxl 屏蔽给定群组
	public static function app_group_hide_set($uid, $gid) {
		return redis()->native('sAdd', '__group_hide__' . $gid, $uid . '_' . $gid);
	}

	// by cxl 删除屏蔽群组
	public static function app_group_hide_del($gid) {
		return redis()->native('del', '__group_hide__' . $gid);
	}

	// by cxl 检查给定群组是否部队当前登录用户开放
	public static function app_group_hide_check($uid, $gid) {
		return redis()->native('sIsMember', '__group_hide__' . $gid, $uid . '_' . $gid);
	}

	public static function app_message_list($uid) {
		$ret = SingleChatModel::api_select_byUid($uid);
		$arr = [];
		// by cxl 消息置顶
		$top_group_gid = GroupRedis::getTopGroup($uid);
		$top_group = [];
		$groups = \app\v1\model\GroupMemberModel::api_select_byUid($uid);
		if ($groups) {
			foreach ($groups as $value)
			{
//				$lastmsg = ChatGmessageModel::api_find_byGid($value['gid']);
				$lastmsg = ChatGmessageModel::api_find_byGidAndUid($value['gid'] , $uid);
				$last_id = UnreadAction::group_get_lastid($uid, $value['gid']);
				$group_last_id = UnreadAction::group_msg_id($value['gid']);
				$unread = $group_last_id - $last_id;
//				if ($unread <= 0) {
//					// 无未读消息
//					continue;
//				}
				if (self::app_group_hide_check($uid, $value['gid'])) {
					// 如果已经被标记为隐藏，则不显示
					continue;
				}
				if ($lastmsg) {
                    // by cxl 群-消息状态
                    $lastmsg['is_read'] = MessageReadStatusModel::api_is_read($uid , 'group' , $lastmsg['id']);
					if ($lastmsg['type'] == 30) {
						// by cxl，消息撤回
						if ($uid == $lastmsg['uid']) {
							$lastmsg['message'] = '你撤回了一条消息';
						} else {
							$user_info = MemberAction::app_user_info($lastmsg['uid']);
							$lastmsg['message'] = '"' . $user_info['uname'] . '"撤回了一条消息';
						}
					}
					if ($lastmsg['type'] == 32) {
						// by cxl
					}
					$gi = GroupAction::app_group_info($value['gid']);
					$gi['last_msg'] = $lastmsg;
					$gi['unread'] = $unread;
					$gi['chat_type'] = 'group';
					$index = array_search($value['gid'], $top_group_gid);
					if ($index !== false) {
						$top_group[$index] = $gi;
					} else {
						$arr[$lastmsg['date']] = $gi;
					}
				}
			}
		}
		if ($ret) {
			foreach ($ret as $value) {
				$value['user_info'] = MemberAction::app_user_info($value['fid'], $uid);
				$value['unread'] = UnreadAction::get_unread_count($uid, $value['fid']);
				$value['last_msg'] = (ChatMessageModel::api_find_byChatId($value['chat_id'], $uid)) ?: [];
				$value['chat_type'] = 'private';
				if (empty($value['last_msg'])) {
                    continue ;
				}
                // by cxl 群-消息状态
                $value['last_msg']['is_read'] = MessageReadStatusModel::api_is_read($uid , 'private' , $value['last_msg']['id']);
                // by cxl-红包消息类型处理
//					if ($value['last_msg']['type'] == 10 || $value['last_msg']['type'] == 11) {
                if (in_array($value['last_msg']['type'] , config('business.packet_type_range'))) {
                    $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['last_msg']['id'] , $value['last_msg']['type']);
                }
                if ($value['last_msg']['type'] == 30) {
                    // by cxl，消息撤回
                    if ($uid == $value['last_msg']['sender']) {
                        $value['last_msg']['message'] = '你撤回了一条消息';
                    } else {
                        $user_info = MemberAction::app_user_info($uid, $value['last_msg']['sender']);
                        $value['last_msg']['message'] = '"' . $user_info['uname'] . '"撤回了一条消息';
                    }
                }
                // todo 这边步骤是多余的
				empty($value['last_msg']) ?
								$arr[$value['date']] = $value :
								$arr[$value['last_msg']['date']] = $value;
			}
		}
//		usort($arr, function ($a, $b) {
//			return ($a > $b) ? -1 : 1;
//		});
		krsort($arr, SORT_NUMERIC);
		array_splice($top_group, count($top_group), 0, $arr);
		return [
			'code' => 0,
			'data' => $top_group,
		];
	}

	public static function app_friend_msg($uid, $fid) {
        $chat_id = ChatAction::app_chatid_gen($uid, $fid);
//        $key = BaseRedis::key('private_msg' , $chat_id);
//        $res = cache($key);
//        if (!empty($res)) {
//            return [
//                'code' => 0 ,
//                'data' => $res
//            ];
//        }
//		$chat_id = ChatAction::app_chatid_gen($uid, $fid);
		$ret = ChatMessageModel::api_select_byChatId($chat_id, $uid);
		if ($ret) {
			UnreadAction::clear_unread($uid, $fid);
			$arr = [];
			$last_id = 0;
			foreach ($ret as $value)
			{
				if ($value['id'] > $last_id) {
					$last_id = $value['id'];
				}
				$value['user_info'] = MemberAction::app_user_info($value['sender']);
                if (in_array($value['type'] , config('business.packet_type_range'))) {
                    $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'] , $value['type']);
                }
				if ($value['type'] == 30) {
					// by cxl，消息撤回
					if ($value['sender'] == $uid) {
						$value['message'] = '你撤回了一条消息';
					} else {
						$value['message'] = '"' . $value['user_info']['uname'] . '"撤回了一条消息';
					}
				}
                // 消息已读/未读
                $value['is_read'] = MessageReadStatusModel::api_is_read($uid , 'private' , $value['id']);
				array_unshift($arr, $value);
			}
//			dump($last_id);
			UnreadAction::set_last_id($uid, $fid, $last_id);
//			cache($key , $arr , cache_duration('month'));
			return [
				'code' => 0,
				'data' => $arr,
			];
		} else {
			return [
				'code' => 1,
				'data' => [],
			];
		}
	}

	// 删除指定用户消息
	public static function app_friend_msg_delete($user_id , $chat_id) {
		$ret = SingleChatModel::api_delete_ByChatId($chat_id);
		if ($ret) {
		    ChatAction::app_send_refresh_list_websocket($user_id , $chat_id);
            return [
                'code' => 0,
                'data' => []
            ];
		}
        return [
            'code' => 0,
            'data' => '数据不存在，默认删除成功'
        ];
	}

	// by cxl 删除用户群聊数据（屏蔽）
	public static function app_group_delete($uid, $gid) {
		self::app_group_hide_set($uid, $gid);
		ChatAction::app_send_group_refresh_list_websocket($uid , $gid);
		return [
			'code' => 0,
			'data' => []
		];
	}

	public static function app_friend_msg_last($uid, $fid) {
		$chat_id = ChatAction::app_chatid_gen($uid, $fid);
		$last_id = UnreadAction::get_last_id($uid, $fid);
		$ret = ChatMessageModel::api_select_byLastid($chat_id, $last_id, $uid);
//		print_r($ret);
//		dump($ret);
		if ($ret) {
			$last_id = 0;
			UnreadAction::clear_unread($uid, $fid);
			foreach ($ret as &$value)
			{
				if ($value['id'] > $last_id) {
					$last_id = $value['id'];
//					echo $value['id'];
				}
				UnreadAction::set_last_id($uid, $fid, $last_id);
                // by cxl
                if (in_array($value['type'] , config('business.packet_type_range'))) {
                    $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'] , $value['type']);
                }
//                if (in_array($value['type'] , config('business.voice_type'))) {
//                    // 语音消息
//                    if ($value['sender'] == $uid) {
//                        $value['message'] = '通信失败';
//                    } else {
//                        $value['message'] = '通信失败';
//                    }
//                }

//				if ($value['type'] == 40 || $value['type'] == 41) {
//                    $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'], $value['type']);
////                    if (empty($value['pack_info'])) {
//                          // 测试用
////                        echo '--- 找不到红包信息 id 是 msg_id---';
////                        var_dump($value['id']);
////                        var_dump($value['type']);
////                        echo '--- 找不到红包信息 ---';
////                        exit;
////                    }
////					if (!$ret[$key]['pack_info']) {
////						$ret[$key]['type'] = 1;
////						$ret[$key]['msg'] = '红包已删除';
////					}
//				}
                $value['user_info'] = MemberAction::app_user_info($value['sender']);
				// by cxl 消息状态
                $value['is_read'] = MessageReadStatusModel::api_is_read($uid , 'private' , $value['id']);
			}
			return [
				'code' => 0,
				'data' => $ret,
			];
		} else {
			return [
				'code' => 1,
				'data' => [],
			];
		}
	}

	public static function app_group_msg($uid, $gid) {
//	    $key = BaseRedis::key('group_message' , $gid);
//	    $res = cache($key);
//	    if (!empty($res)) {
//	        return [
//	            'code' => 0 ,
//                'data' => $res
//            ];
//        }
		$ret = \app\v1\model\ChatGmessageModel::api_select_byGid($gid, $uid);
		if ($ret) {
			$arr = [];
			$last_id = 0;
			foreach ($ret as $value) {
				if ($value['id'] > $last_id) {
					$last_id = $value['id'];
				}
				$value['user_info'] = MemberAction::app_user_info($value['uid']);
				if ($value['uid'] == $uid) {
					$value['is_sender'] = true;
				} else {
					$value['is_sender'] = false;
				}
//				if ($value['type'] >= 10 && $value['type'] <= 20) {
//					$value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'], $value['type']);
//
//
                if (in_array($value['type'] , config('business.packet_type_range'))) {
                    $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'] , $value['type']);
					if (!$value['pack_info']) {
						$value['type'] = 1;
						$value['msg'] = '红包已删除';
					}
					// by redis 中最大数量
					$value['pack_info']['max_num'] = PacketAction::app_group_get_maxium_num($value['id']);
					$value['pack_info']['current_num'] = PacketAction::app_group_get_current_num($value['id']);
					$value['pack_info']['opened'] = (int) in_array($uid, PacketAction::app_group_exec_pack($value['id']));
				}
				if ($value['type'] == 30) {
					// by cxl，消息撤回
					if ($value['uid'] == $uid) {
						$value['message'] = '你撤回了一条消息';
					} else {
						$value['message'] = '"' . $value['user_info']['uname'] . '"撤回了一条消息';
					}
				}
                // 消息已读/未读
                $value['is_read'] = MessageReadStatusModel::api_is_read($uid , 'group' , $value['id']);
				array_unshift($arr, $value);
			}
			UnreadAction::group_set_lastid($uid, $gid, UnreadAction::group_get_msgid($gid));
			// 缓存
//            cache($key , $arr , cache_duration('month'));
			return [
				'code' => 0,
				'data' => $arr,
			];
		} else {
			return [
				'code' => 1,
				'data' => [],
			];
		}
	}

	public static function app_group_msg_last($uid, $gid) {
		$last_count = UnreadAction::group_get_msgid($gid) - UnreadAction::group_get_lastid($uid, $gid);
		if ($last_count > 0) {
			UnreadAction::group_set_lastid($uid, $gid, UnreadAction::group_get_msgid($gid));
			$ret = ChatGmessageModel::api_select_byLastid($gid, $last_count);

			if ($ret) {
				$last_id = 0;
				foreach ($ret as &$value)
				{
					if ($value['id'] > $last_id) {
						$last_id = $value['id'];
//					echo $value['id'];
					}
					if ($value['uid'] == $uid) {
						$value['is_sender'] = true;
					} else {
						$value['is_sender'] = false;
					}
//					if ($value['type'] == 10 || $value['type'] == 11) {
                    if (in_array($value['type'] , config('business.packet_type_range'))) {
                        $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($value['id'] , $value['type']);
//                        $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgId($value['id']);
						$value['pack_info']['max_num'] = PacketAction::app_group_get_maxium_num($value['id']);
						$value['pack_info']['current_num'] = PacketAction::app_group_get_current_num($value['id']);
						$value['pack_info']['opened'] = (int) in_array($uid, PacketAction::app_group_exec_pack($value['id']));
					}
					$value['user_info'] = MemberAction::app_user_info($value['uid']);
                    // 消息已读/未读
                    $value['is_read'] = MessageReadStatusModel::api_is_read($uid , 'group' , $value['id']);
				}

				return [
					'code' => 0,
					'data' => $ret,
				];
			} else {
				return [
					'code' => 0,
					'data' => $ret,
				];
			}
		} else {
			return [
				'code' => 1,
				'data' => [],
			];
		}
	}

	// by cxl 群消息排除的用户
	public static function app_group_msg_exclude_user_insert($uid, $msg_id) {
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        $last_id = GroupMessageExcludeUserModel::insertGetId([
            'uid' => $uid,
            'msg_id' => $msg_id
        ]);
        Websocket::refresh([$uid] , $msg['gid']);
		if ($last_id > 0) {
			return [
				'code' => 0,
				'data' => [],
			];
		}
		return [
			'code' => 1,
			'data' => []
		];
	}

	// by cxl 私聊模式中设置消息排除的用户
	public static function app_msg_exclude_user_set($uid, $msg_id) {
        $msg = ChatMessageModel::api_find_byMessageId($msg_id);
        ChatMessageModel::api_exclude_user_set($msg_id, $uid);
        Websocket::refresh([$uid] , $msg['chat_id']);
		return [
			'code' => 0,
			'data' => []
		];
	}

	// by cxl 私聊消息-消息撤回
	public static function app_msg_retract($operator, $msg_id) {
		// 消息记录
		$msg = ChatMessageModel::api_find_byMessageId($msg_id);
		if ($operator != $msg['sender']) {
			return [
				'code' => 403,
				'data' => '你无法撤回他人消息'
			];
		}
        // 不允许撤回红包消息(11|12|13|40|41)
        if (in_array($msg['type'] , config('business.packet_type_range'))) {
            return [
                'code' => 403 ,
                'data' => '无法撤回红包消息'
            ];
        }
        // 不允许撤回 撤回消息（30）
        if ($msg['type'] == 30) {
            return [
                'code' => 403 ,
                'data' => '禁止操作！无法撤回 撤回消息（type = 30）' ,
            ];
        }
		// 撤回时间限制
        $send_time = $msg['date'];
		$min = config('app.min_for_retract');
		if ($send_time + $min * 60 < time()) {
            return [
                'code' => '504' ,
                'data' => sprintf('消息已经发送超过 %s 分钟，无法撤回' , $min) ,
            ];
        }
		$chat = explode('_', $msg['chat_id']);
		if ($operator == $chat[0]) {
			$another = $chat[1];
		} else {
			$another = $chat[0];
		}
		try {
		    Db::startTrans();
            // 删除该条消息
            ChatMessageModel::api_del_byMessageId($msg_id);
            // 消息撤回成功
            ChatMessageModel::api_insert_overall([
                'id'        => $msg_id ,
                'chat_id'   => $msg['chat_id'] ,
                'sender'    => $msg['sender'] ,
                'message'   => '消息撤回' ,
                'type'      => 30 ,
                'date'      => $msg['date'] ,
            ]);
            $msg['chat_message_id'] = $msg['id'];
            // 撤回消息备份到备份表
            ChatMessageRetractModel::insertGetId(array_unit($msg , [
                'chat_message_id' ,
                'chat_id' ,
                'sender' ,
                'type' ,
                'message' ,
                'extra' ,
                'date' ,
                'exclude_one' ,
                'exclude_two' ,
            ]));
            // 推送给对方
            Notification::retract($another , 'private' , $another);
		    Db::commit();
            return [
                'code' => 0,
                'data' => []
            ];
        } catch(Exception $e) {
		    Db::rollback();
		    throw $e;
        }
	}

	// by cxl 群消息撤回
	public static function app_group_msg_retract($uid, $msg_id) {
        // 消息记录
        $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
        if ($uid != $msg['uid']) {
            return [
                'code' => 403,
                'data' => '你无法撤回他人消息'
            ];
        }
        // 不允许撤回红包消息(11|12|13|40|41)
        if (in_array($msg['type'] , config('business.packet_type_range'))) {
            return [
                'code' => 403 ,
                'data' => '无法撤回红包消息'
            ];
        }
        // 不允许撤回 撤回消息（30）
        if ($msg['type'] == 30) {
            return [
                'code' => 403 ,
                'data' => '禁止操作！无法撤回 撤回消息（type = 30）' ,
            ];
        }
        // 撤回时间限制
        $send_time = $msg['date'];
        $min = config('app.min_for_retract');
        if ($send_time + $min * 60 < time()) {
            return [
                'code' => '504' ,
                'data' => sprintf('消息已经发送超过 %s 分钟，无法撤回' , $min) ,
            ];
        }
        try {
            Db::startTrans();
            // 删除该条消息
            ChatGmessageModel::api_del_byMessageId($msg_id);
            // 消息撤回成功
            ChatGmessageModel::api_insert_overall([
                'id'        => $msg_id ,
                'gid'       => $msg['gid'] ,
                'uid'       => $msg['uid'] ,
                'message'   => '消息撤回' ,
                'type'      => 30 ,
                'date'      => $msg['date'] ,
            ]);
            $msg['chat_gmessage_id'] = $msg['id'];
            // 撤回消息备份到备份表
            ChatGmessageRetractModel::insertGetId(array_unit($msg , [
                'chat_gmessage_id' ,
                'gid' ,
                'uid' ,
                'type' ,
                'message' ,
                'extra' ,
                'date' ,
            ]));
            // 推送给群里面
            $member = GroupMemberModel::api_select($msg['gid']);
            foreach ($member as $v)
            {
                if ($v['uid'] == $uid) {
                    // 不要推送给自身
                    continue ;
                }
                // 通知其他群成员 有人撤回了消息
                Notification::retract($v['uid'] , 'group' , $msg['gid']);
            }
            Db::commit();
            return [
                'code' => 0,
                'data' => []
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
	}

	// by cxl 设置消息已读/未读
    public static function app_read_set($uid , $type , $msg_id , $read)
    {
        MessageReadStatusModel::api_read_set($uid , $type , $msg_id , $read);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 消息转发
    public static function forward($uid , $forward_type , $forward_id , $msg_type , $msg_id = null , $msg_ids = null)
    {
        $forward_type_range = ['user' , 'group'];
        $msg_type_range     = ['private' , 'group'];
        if (!in_array($msg_type , $msg_type_range)) {
            return [
                'code' => 400 ,
                'data' => 'msg_type 参数错误' ,
            ];
        }
        if (!in_array($forward_type , $forward_type_range)) {
            return [
                'code' => 400 ,
                'data' => 'forward_type 参数错误' ,
            ];
        }
        if (empty($msg_ids)) {
            // 单条消息转发
            if ($msg_type == 'private') {
                // 私聊消息转发
                $msg = ChatMessageModel::api_find_byMessageId($msg_id);
                if (empty($msg)) {
                    return [
                        'code' => 404 ,
                        'data' => '未找到要转发的消息' ,
                    ];
                }
                // 检查类型
                if (!in_array($msg['type'] , config('business.forward_type'))) {
                    return [
                        'code' => 403 ,
                        'data' => '包含不支持的转发类型' ,
                    ];
                }
                if ($forward_type == 'user') {
                    // 转发给个人
                    if ($uid == $forward_id) {
                        // 报这个错误，就说明本应该提供 fid 的，却把自己的 uid 提供了服务端
                        return [
                            'code' => 400 ,
                            'data' => 'forward_id 错误' ,
                        ];
                    }
                    return ChatAction::app_send($uid , $forward_id , $msg['type'] , $msg['message'] , $msg['extra']);
                } else {
                    // 转发给群组
                    return ChatAction::app_group_send($uid , $forward_id , $msg['type'] , $msg['message'] , $msg['extra']);
                }
            } else {
                // 群聊消息转发
                $msg = ChatGmessageModel::api_find_byMessageId($msg_id);
                if (empty($msg)) {
                    return [
                        'code' => 404 ,
                        'data' => '未找到要转发的消息' ,
                    ];
                }
                // 检查类型
                if (!in_array($msg['type'] , config('business.forward_type'))) {
                    return [
                        'code' => 403 ,
                        'data' => '包含不支持的转发类型' ,
                    ];
                }
                if ($forward_type == 'user') {
                    // 转发给个人
                    if ($uid == $forward_id) {
                        // 报这个错误，就说明本应该提供 fid 的，却把自己的 uid 提供了服务端
                        return [
                            'code' => 400 ,
                            'data' => 'forward_id 错误' ,
                        ];
                    }
                    return ChatAction::app_send($uid , $forward_id , $msg['type'] , $msg['message'] , $msg['extra']);
                } else {
                    // 转发给群组
                    return ChatAction::app_group_send($uid , $forward_id , $msg['type'] , $msg['message'] , $msg['extra']);
                }
            }
            return ;
        }
        // todo 多条消息转发
    }

    // by cxl 私聊数据附加额外数据
    public static function app_msg_handle($uid , array $msg = [])
    {
        if (in_array($msg['type'] , config('business.packet_type_range'))) {
            $msg['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($msg['id'] , $msg['type']);
        }
        $msg['user_info'] = MemberAction::app_user_info($msg['sender']);
        if ($msg['type'] == 30) {
            // by cxl，消息撤回
            if ($msg['sender'] == $uid) {
                $msg['message'] = '你撤回了一条消息';
            } else {
                $msg['message'] = '"' . $msg['user_info']['uname'] . '"撤回了一条消息';
            }
        }
        // by cxl 消息状态
        $msg['is_read'] = MessageReadStatusModel::api_is_read($uid , 'private' , $msg['id']);
        return $msg;
    }

    // by cxl 群聊数据附加额外数据
    public static function app_group_msg_handle($uid , array $msg = [])
    {
        if ($msg['uid'] == $uid) {
            $msg['is_sender'] = true;
        } else {
            $msg['is_sender'] = false;
        }
//					if ($value['type'] == 10 || $value['type'] == 11) {
        if (in_array($msg['type'] , config('business.packet_type_range'))) {
            $msg['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgIdAdnType($msg['id'] , $msg['type']);
//                        $value['pack_info'] = \app\v1\model\RedPacketModel::api_find_byMsgId($value['id']);
            $msg['pack_info']['max_num'] = PacketAction::app_group_get_maxium_num($msg['id']);
            $msg['pack_info']['current_num'] = PacketAction::app_group_get_current_num($msg['id']);
            $msg['pack_info']['opened'] = (int) in_array($uid, PacketAction::app_group_exec_pack($msg['id']));
        }
        $msg['user_info'] = MemberAction::app_user_info($msg['uid']);
        if ($msg['type'] == 30) {
            // by cxl，消息撤回
            if ($msg['uid'] == $uid) {
                $msg['message'] = '你撤回了一条消息';
            } else {
                $msg['message'] = '"' . $msg['user_info']['uname'] . '"撤回了一条消息';
            }
        }
        // 消息已读/未读
        $msg['is_read'] = MessageReadStatusModel::api_is_read($uid , 'group' , $msg['id']);
        return $msg;
    }

    // by cxl 最新一条消息
    public static function app_private_last_msg($uid , $fid)
    {
        $res = ChatMessageModel::api_last_msg(ChatAction::app_chatid_gen($uid , $fid));
        if (!empty($res)) {
            $res['create_time'] = date('Y-m-d H:i:s' , $res['date']);
        }
        return $res;
    }

    public static function app_group_last_msg($gid)
    {
        $res = ChatGmessageModel::api_last_msg($gid);
        if (!empty($res)) {
            $res['create_time'] = date('Y-m-d H:i:s' , $res['date']);
        }
        return $res;
    }

    // by cxl 清空私聊未读消息
    public static function clear_private_unread($uid , $fid)
    {
        UnreadAction::clear_unread($uid , $fid);
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

    // by cxl 清空群聊未读消息
    public static function clear_group_unread($uid , $gid)
    {
        UnreadAction::group_set_lastid($uid, $gid, UnreadAction::group_get_msgid($gid));
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

    // by cxl
    public static function app_earlier_private_msg($uid , $fid , $msg_id)
    {
        $chat_id = ChatAction::app_chatid_gen($uid , $fid);
        $res = ChatMessageModel::api_earlier_msg($chat_id , $msg_id , 20);
        usort($res , function($a , $b){
            if ($a['id'] == $b['id']) {
                return 0;
            }
            return $a['id'] > $b['id'] ? 1 : -1;
        });
        foreach ($res as $k => $v)
        {
            $res[$k] = self::app_msg_handle($uid , $v);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // by cxl
    public static function app_earlier_group_msg($uid , $gid , $msg_id)
    {
        $res = ChatGmessageModel::api_earlier_msg($gid , $msg_id , 20);
        usort($res , function($a , $b){
            if ($a['id'] == $b['id']) {
                return 0;
            }
            return $a['id'] > $b['id'] ? 1 : -1;
        });
        foreach ($res as $k => $v)
        {
            $res[$k] = self::app_group_msg_handle($uid , $v);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}
