<?php

/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/5
 * Time: 9:47
 */

namespace app\v1\util;

use Push\APush;
use app\v1\action\UserAction;

class Notification {

	// 检查是否允许消息推送
	public static function canNotice($uid) {
		return UserAction::app_can_notice($uid);
	}

	// 群验证消息推送
	public static function groupVerifyPush($uid, $message, $extra = []) {
		if (!self::canNotice($uid)) {
			return;
		}
		return APush::push_single($uid, $message, '你有一条加群验证待处理', [
					'module' => 'chat',
					'type' => 'group_verify',
					'data' => $extra
		]);
	}

	// 被踢出群聊通知
	public static function KickedForGroup($uid, $message, $extra = []) {
		if (!self::canNotice($uid)) {
			return;
		}
		return APush::push_single($uid, $message, '退群通知', [
					'module' => 'chat',
					'type' => 'group_kick_user',
					'data' => $extra
		]);
	}

	// 消息撤回通知（静默通知）
	public static function retract($uid, $type, $id) {
		return APush::push_message($uid, '', [
					'module' => 'chat',
					'type' => 'retract',
					'data' => [
						// user|group
						'type' => $type,
						// uid | gid
						'id' => $id,
					]
		]);
	}

	// 语音通话
	public static function voice($sender , $receiver , $msg_type, $msg_id, $channel = '' , array $member = []) {
		return APush::push_single($receiver, '语音通话', '语音通话' , [
					'module' => 'chat',
					'type' => 'voice',
					'data' => [
						'sender' => $sender,
						'channel' => $channel,
						'msg_type' => $msg_type,
						'msg_id' => $msg_id,
                        'member' => $member
					],
		]);
	}

    // 已经加入语音通话-通知发起方更新接听状态
    public static function joined_voice($sender , $receiver , $msg_type, $msg_id) {
//        return APush::push_single($receiver, '通话中', '通话中' , [
        return APush::push_message($receiver, '通话中' , [
            'module' => 'chat',
            'type' => 'joined_voice',
            'data' => [
                'sender'    => $sender,
                'msg_type'  => $msg_type,
                'msg_id'    => $msg_id,
            ],
        ]);
    }

    // 挂断语音聊天
    public static function refuse_voice($sender , $receiver , $msg_type, $msg_id) {
//        return APush::push_single($receiver, '对方已挂断', '语音通话' , [
        return APush::push_message($receiver, '对方已挂断' , [
            'module' => 'chat',
            'type' => 'refuse_voice',
            'data' => [
                'sender'    => $sender,
                'msg_type'  => $msg_type,
                'msg_id'    => $msg_id,
            ],
        ]);
    }

    /**
     * **************************************
     * 视频通话 start
     * **************************************
     */
    // 视频通话
    public static function video($sender , $receiver , $msg_type, $msg_id, $channel = '') {
        return APush::push_single($receiver, '视频通话', '视频通话' , [
            'module' => 'chat',
            'type' => 'video',
            'data' => [
                'sender' => $sender,
                'channel' => $channel,
                'msg_type' => $msg_type,
                'msg_id' => $msg_id,
            ],
        ]);
    }

    // 已经加入视频通话-通知发起方更新接听状态
    public static function joined_video($sender , $receiver , $msg_type, $msg_id) {
//        return APush::push_single($receiver, '通话中', '通话中' , [
        return APush::push_message($receiver, '通话中' , [
            'module' => 'chat',
            'type' => 'joined_video',
            'data' => [
                'sender'    => $sender,
                'msg_type'  => $msg_type,
                'msg_id'    => $msg_id,
            ],
        ]);
    }

    // 挂断视频聊天
    public static function refuse_video($sender , $receiver , $msg_type, $msg_id) {
//        return APush::push_single($receiver, '对方已挂断', '语音通话' , [
        return APush::push_message($receiver, '对方已挂断' , [
            'module' => 'chat',
            'type' => 'refuse_video',
            'data' => [
                'sender'    => $sender,
                'msg_type'  => $msg_type,
                'msg_id'    => $msg_id,
            ],
        ]);
    }
    /**
     * **************************************
     * 视频通话 end
     * **************************************
     */

    public static function outside($sender , $receiver , $msg_type, $msg_id) {
        return APush::push_single($receiver, '对方在国外', '不支持通话' , [
//        return APush::push_message($receiver, '对方已挂断' , [
            'module' => 'chat',
            'type' => 'outside',
            'data' => [
                'sender'    => $sender,
                'msg_type'  => $msg_type,
                'msg_id'    => $msg_id,
            ],
        ]);
    }
}
