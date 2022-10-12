<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\GroupDisturb;
use app\v1\model\GroupMemberModel;
use app\v1\model\GroupMemberVerify;
use app\v1\model\RequestListModel;
use app\v1\redis\GroupRedis;
use app\v1\model\GroupInfoModel;
use app\v1\util\Notification;
use app\v1\model\UserInfoModel;
use app\v1\util\Response;
use app\v1\util\Websocket;
use Exception;
use think\Db;
use think\Validate;

/**
 * Description of GroupAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class GroupAction {

	public static function app_group_uids($gid) {
		$group_uids = GroupMemberModel::api_select($gid);
		$arr = [];
		if ($group_uids) {
			foreach ($group_uids as $value) {
				array_push($arr, $value['uid']);
			}
		}
		return $arr;
	}

	public static function app_group_info($gid , $uid = null) {
		$group_info = GroupInfoModel::api_find_byId($gid);
		if ($group_info) {
			$group_info['group_name'] = $group_info['group_name'] ?: '未设定昵称';
			$group_info['img'] = $group_info['img'] ?: config('app.group_avatar');
			$group_info['introduction'] = $group_info['introduction'] ?: '未填写说明';
			$group_info['member_count'] = self::app_group_member_count($gid);
			if (!is_null($uid)) {
			    // 是否置顶
                $group_info['is_top'] = GroupRedis::isTop($uid , $gid);
                // 是否允许打扰
                $group_info['can_disturb'] = GroupDisturb::api_count($gid , $uid) > 0 ? 0 : 1;
            }
		}
		return $group_info;
	}

	// 群成员数量统计
	public static function app_group_member_count($gid) {
		return GroupMemberModel::api_count($gid);
	}

	public static function app_create_group($uid, $group_name, $introduction = '没有填写说明', $category = 'default' , $type = 1 , $expire = null) {
		//check if he had alreay had xx groups
		if (GroupInfoModel::api_count_owner($uid) > 10) {
			return [
				'code' => 204,
				'data' => '请先解散一个群才可以创建',
			];
		}
		//then start to create a group
		Db::startTrans();
		$succ = GroupInfoModel::api_insert($uid, $group_name, $introduction, $category , $type , $expire);
		if ($succ) {
			if (GroupMemberModel::api_insert($succ, $uid, 'owner')) {
				Db::commit();
				return [
					'code' => 0,
				];
			} else {
				Db::rollback();
				return [
					'code' => 501,
					'data' => '创建群权限失败'
				];
			}
		} else {
			Db::rollback();
			return [
				'code' => 500,
				'data' => '创建群失败',
			];
		}
	}

	public static function app_group_list($uid) {
		$ret = GroupMemberModel::api_select_byUid($uid);
		if ($ret) {
		    $res = [];
			foreach ($ret as $key => $value)
			{
				$value['group_info'] = self::app_group_info($value['gid']);
				$res[$key] = $value;
			}
			return [
				'code' => 0,
				'data' => $res
			];
		} else {
			return array(
				'code' => 0,
				"data" => [],
			);
		}
	}

	// by cxl
	public static function util_group_list($uid)
    {
        $ret = GroupMemberModel::api_select_byUid($uid);
        if (empty($ret)) {
            return [];
        }
        foreach ($ret as &$value)
        {
            $value['group_info'] = self::app_group_info($value['gid']);
        }
        return $ret;
    }

    // by cxl
    public static function util_recent_group_list($uid)
    {
        $ret = GroupMemberModel::api_select_byUid($uid);
        if (empty($ret)) {
            return [];
        }
        foreach ($ret as &$value)
        {
            $value['group_info'] = self::app_group_info($value['gid']);
            $value['lastmsg'] = MessageAction::app_group_last_msg($value['gid']);
        }
        return $ret;
    }

	public static function app_search_group($group_name) {
		$ret = GroupInfoModel::api_select_likeGroupName($group_name);
		if ($ret) {
			foreach ($ret as $key => $value) {
				$value['group_info'] = self::app_group_info($value['id']);
				$ret[$key] = $value;
			}
		}

		return [
			'code' => 0,
			'data' => $ret
		];
	}

	public static function app_group_member($gid) {
		$ret = GroupMemberModel::api_select($gid);
		foreach ($ret as $key => $value) {
			$value['user_info'] = MemberAction::app_user_info($value['uid']);
			$ret[$key] = $value;
		}
		return [
			'code' => 0,
			'data' => $ret
		];
	}

	public static function app_exit_group($uid, $gid) {
		$is_exists = GroupMemberModel::api_find($gid, $uid);
		if (!$is_exists) {
			return [
				'code' => 305,
				'data' => '您未在此群不能退出',
			];
		} else {
			if ($is_exists['role'] == 'owner') {
				return [
					'code' => 303,
					'data' => '您是群主只能解散该群不能退出该群',
				];
			}
		}
		Db::startTrans();
		$ret = GroupMemberModel::api_delete($gid, $uid);
		if ($ret) {
			Db::commit();
			return [
				'code' => 0,
			];
		} else {
			Db::rollback();
			return [
				'code' => 1,
				'data' => '您已不在该群',
			];
		}
	}

	public static function app_vanish_group($uid, $gid) {
		$is_exists = GroupMemberModel::api_find($gid, $uid);
		if (!$is_exists) {
			return [
				'code' => 305,
				'data' => '您未在此群不能退出',
			];
		} else {
			if ($is_exists['role'] != 'owner') {
				return [
					'code' => 303,
					'data' => '您不是群主无权解散该群',
				];
			}
		}
		Db::startTrans();
		$members = GroupMemberModel::api_select($gid);
		foreach ($members as $v)
        {
            GroupMemberModel::api_delete($gid , $v['uid']);
        }
        if (GroupInfoModel::api_delete_byId($gid)) {
            Db::commit();
            return [
                'code' => 0,
            ];
        }
        Db::rollback();
        return [
            'code' => 1,
            'data' => '该群不存在',
        ];
	}

	public static function app_invite_to_group($uid, $gid, $invited_uid) {
		$friend = FriendAction::app_is_friend($uid, $invited_uid);
		if (!$friend) {
			return [
				'code' => 403,
				'data' => '你只能邀请好友加群',
			];
		}
		$is_exists = GroupMemberModel::api_find($gid, $invited_uid);
		if ($is_exists) {
			return [
				'code' => 405,
				'data' => '您已经加入该群了',
			];
		}
        try {
            // by cxl 加群验证
            $group = GroupInfoModel::api_find_byId($gid);
            if ($uid != $group['owner_id'] && !$group['direct_join_group']) {
                // by cxl 加群验证
                Db::startTrans();
                // 非群主 && 群主开启群验证
                $user = UserInfoModel::api_find_byUid($uid);
                $invited_user = UserInfoModel::api_find_byUid($invited_uid);
                // 发送一条消息通知
                $msg = '"' . $user['uname'] . '"邀请"' . $invited_user['uname'] . '"加入群"' . $group['group_name'] . '"';
                $request_list_id = RequestListModel::api_insert($group['owner_id'] , $group['id'] , 'group' , 'invite' , null , $msg);
                $request_count = RequestListModel::api_unhandle_count($group['owner_id']);
                // 发送推送
                $res = Websocket::requestCount([$group['owner_id']] , $request_count);
                if ($res['code'] != 0) {
                    Db::rollback();
                    return Response::wsError($res['code'] , $res['data']);
                }
                GroupMemberVerify::api_insert($request_list_id , $invited_uid);
                Notification::groupVerifyPush($group['owner_id'] , $msg , [
                    'request_list_id' => $request_list_id ,
                ]);
                Db::commit();
                return [
                    'code' => 0 ,
                    'data' => []
                ];
            }
        } catch(Exception $e) {
		    Db::rollback();
		    throw $e;
        }
		$ret = GroupMemberModel::api_insert($gid, $invited_uid, 'member');
		if ($ret) {
			return [
				'code' => 0,
			];
		} else {
			return [
				'code' => 1,
				'data' => '暂时不能加入',
			];
		}
	}

	public static function app_invites_to_group($uid, $gid, $invited_uids) {
		if (!$gid) {
			return [
				'code' => 403,
				'data' => '请填写GID',
			];
		}
        $uids = json_decode($invited_uids, true);
		try {
            $group = GroupInfoModel::api_find_byId($gid);
            if ($uid != $group['owner_id'] && !$group['direct_join_group']) {
                // by cxl 加群验证
                Db::startTrans();
                $user = UserInfoModel::api_find_byUid($uid);
                $msg = '"' . $user['uname'] . '"邀请';
                $invited_user_str = '';
                $request_list_id = RequestListModel::api_insert($group['owner_id'] , $group['id'] , 'group' , 'invite');
                $request_count = RequestListModel::api_unhandle_count($group['owner_id']);
                // 发送推送
                $res = Websocket::requestCount([$group['owner_id']] , $request_count);
                if ($res['code'] != 0) {
                    Db::rollback();
                    return Response::wsError($res['code'] , $res['data']);
                }
                foreach ($uids as $value)
                {
                    $friend = FriendAction::app_is_friend($uid, $value);
                    if ($friend) {
                        $is_exists = GroupMemberModel::api_find($gid, $value);
                        if (!$is_exists) {
                            // 非群主 && 群主开启群验证
                            $invited_user = UserInfoModel::api_find_byUid($value);
                            GroupMemberVerify::api_insert($request_list_id , $value);
                            // 发送一条消息通知
                            $invited_user_str .= sprintf('"%s",' , $invited_user['uname']);
                        } else {
                            Db::rollback();
                            return [
                                'code' => 1 ,
                                'data' => '被邀请人已经在本群中',
                            ];
                        }
                    } else {
                        Db::rollback();
                        return [
                            'code' => 2,
                            'data' => '该用户不是你的好友',
                        ];
                    }
                }
                $invited_user_str = mb_substr($invited_user_str , 0 , mb_strlen($invited_user_str) - 1);
                $invited_user_str = mb_strlen($invited_user_str) > 50 ?
                    mb_substr($invited_user_str , 0 , 50) . '...' :
                    $invited_user_str;
                $msg .= $invited_user_str;
                $msg .= '加入群"' . $group['group_name'] . '"';
                // 修改备注
                RequestListModel::api_comment_set($request_list_id , $msg);
                // 邀请进群通知
                Notification::groupVerifyPush($group['owner_id'] , $msg , [
                    'request_list_id' => $request_list_id ,
                ]);
                Db::commit();
                return [
                    'code' => 0,
                    'data' => []
                ];
            }
        } catch(Exception $e){
		    Db::rollback();
		    throw $e;
        }
        // 无需加群验证
        foreach ($uids as $value) {
            $friend = FriendAction::app_is_friend($uid, $value);
            if ($friend) {
                $is_exists = GroupMemberModel::api_find($gid, $value);
                if (!$is_exists) {
                    $ret = GroupMemberModel::api_insert($gid, $value, 'member');
                    if (!$ret) {
                        return [
                            'code' => 1,
                            'data' => '邀请失败',
                        ];
                    }
                } else {
                    return [
                        'code' => 1,
                        'data' => '被邀请人已经在本群中',
                    ];
                }
            } else {
                return [
                    'code' => 2,
                    'data' => '该用户不是你的好友',
                ];
            }
        }
        return [
            'code' => 0,
            'data' => []
        ];
	}

	// 发送加群验证
	public static function app_send_group_request($uid, $gid , $comment = '') {
		$is_exists = GroupMemberModel::api_find($gid, $uid);
		if ($is_exists) {
			return [
				'code' => 405,
				'data' => '您已经加入该群了',
			];
		}
		try {
            // by cxl 加群验证
            $group = GroupInfoModel::api_find_byId($gid);
            if ($uid != $group['owner_id'] && !$group['direct_join_group']) {
                Db::startTrans();
                // 非群主 && 群主开启群验证
                $user = UserInfoModel::api_find_byUid($uid);
                $invited_user = UserInfoModel::api_find_byUid($uid);
                $request_list_id = RequestListModel::api_insert($group['owner_id'] , $group['id'] , 'group' , 'invite' , null , $comment);
                $request_count = RequestListModel::api_unhandle_count($group['owner_id']);
                // 发送推送
                $res = Websocket::requestCount([$group['owner_id']] , $request_count);
                if ($res['code'] != 0) {
                    Db::rollback();
                    return Response::wsError($res['code'] , $res['data']);
                }
                GroupMemberVerify::api_insert($request_list_id , $uid);
                // 发送一条消息通知
                $msg = '"' . $user['uname'] . '"申请"' . $invited_user['uname'] . '"加入群"' . $group['group_name'] . '"';
                Notification::groupVerifyPush($group['owner_id'] , $msg , [
                    'request_list_id' => $request_list_id ,
                ]);
                Db::commit();
                return [
                    'code' => 0,
                    'data' => []
                ];
            }
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
		$ret = GroupMemberModel::api_insert($gid, $uid, 'member');
		if ($ret) {
			return [
				'code' => 0,
			];
		} else {
			return [
				'code' => 1,
				'data' => '暂时不能加入',
			];
		}
	}

	public static function app_kick_user($uid, $gid, $kick_id) {
		if ($uid == $kick_id) {
			return [
				'code' => 304,
				'data' => '不能T自己',
			];
		}
		$is_exists = GroupMemberModel::api_find($gid, $uid);
		if (!$is_exists) {
			return [
				'code' => 305,
				'data' => '您未在此群不能退出',
			];
		}
		$kicker = GroupMemberModel::api_find($gid, $kick_id);
		if ($kicker) {
			if ($is_exists['role'] != 'owner') {
				if ($kicker['role'] == 'owner' || $kicker['role'] == 'admin') {

				} else {
					return [
						'code' => 303,
						'data' => '无权限',
					];
				}
			}
		} else {
			return [
				'code' => 303,
				'data' => '无操作权限',
			];
		}
		try {
            Db::startTrans();
            $ret = GroupMemberModel::api_delete($gid, $kick_id);
            if (!$ret) {
                Db::rollback();
                return [
                    'code' => 1,
                    'data' => '无操作权限',
                ];
            }
            $group = GroupAction::app_group_info($gid);
            $message = '你被群主移出了群“' . $group['group_name'] . '”';
            // 新增一条验证消息
            $request_list_id = RequestListModel::api_insert($kick_id , $gid , 'group' , 'kick' , null , $message);
            // 通知对方被踢出群了
            Notification::KickedForGroup($kick_id , $message , [
                'request_list_id' => $request_list_id
            ]);
            Db::commit();
            return [
                'code' => 0 ,
                'data' => '操作成功'
            ];
        } catch(Exception $e) {
		    Db::rollback();
		    throw $e;
        }

	}

	// todo by cxl 批量删除，待测试
	public static function app_kick_users($uid , $gid , $kick_uids)
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($group['owner_id'] != $uid) {
            return [
                'code' => 403 ,
                'data' => '您不是群主，无权限操作'
            ];
        }
        $kick_uids = json_decode($kick_uids , true);
        try {
            Db::startTrans();
            foreach ($kick_uids as $v)
            {
                // 检查被踢的用户是否是当前群的成员
                $is_exists = GroupMemberModel::api_find($gid, $v);
                if (!$is_exists) {
                    Db::rollback();
                    return [
                        'code' => 305,
                        'data' => '包含未在此群的用户，拒绝操作',
                    ];
                }
                // 检查是否是群组自己踢自己
                if ($uid == $v) {
                    Db::rollback();
                    return [
                        'code' => 403 ,
                        'data' => '您是群主，不能删除自身'
                    ];
                }
                $message = '你被群主移出了群“' . $group['group_name'] . '”';
                GroupMemberModel::api_delete($gid, $v);
                // 新增一条验证消息
                $request_list_id = RequestListModel::api_insert($v , $gid , 'group' , 'kick' , null , $message);
                // 通知对方被踢出群了
                Notification::KickedForGroup($uid , $message , [
                    'request_list_id' => $request_list_id
                ]);
            }
            Db::commit();
            return [
                'code' => 0 ,
                'data' => '操作成功'
            ];
        } catch(Exception $e) {
            Db::rollback();
        }
    }

	public static function app_ban_user($uid, $gid, $ban_id) {
//		if ($uid == $ban_id) {
//			return [
//				'code' => 304,
//				'data' => '不能禁言自己',
//			];
//		}
//		$is_exists = GroupMemberModel::api_find($gid, $uid);
//		if (!$is_exists) {
//			return [
//				'code' => 305,
//				'data' => '您未在此群不能退出',
//			];
//		} else {
//			if ($is_exists['role'] == 'owner' || $is_exists['role'] == 'admin') {
//
//			} else {
//				return [
//					'code' => 303,
//					'data' => '无操作权限',
//				];
//			}
//		}
//		$banner = GroupMemberModel::api_find($gid, $kick_id);
//		if ($banner) {
//			if ($is_exists['role'] != 'owner') {
//				return [
//					'code' => 303,
//					'data' => '无权限',
//				];
//			}
//		} else {
//			return [
//				'code' => 303,
//				'data' => '无操作权限',
//			];
//		}
//		Db::startTrans();
//		$ret = GroupMemberModel::api_delete($gid, $kick_id);
//		if ($ret) {
//			Db::commit();
//			return [
//				'code' => 0,
//			];
//		} else {
//			Db::rollback();
//			return [
//				'code' => 1,
//				'data' => '无操作权限',
//			];
//		}
	}

	public static function app_group_request_approve($uid, $group_id, $approve = true, $comment = '') {
//		if ($approve) {
//			return self::app_add_group($uid, $group_id);
//		} else {
//
//		}
	}

	public static function app_in_group($gid, $uid) {
		if (GroupMemberModel::api_find($gid, $uid)) {
			return true;
		} else {
			return false;
		}
	}

	public static function app_is_in_group($gid, $uid) {
		if (GroupMemberModel::api_find($gid, $uid)) {
			return true;
		} else {
			return false;
		}
	}

	// by cxl 设置消息置顶
	public static function app_group_top_set($uid , $gid , $is_top)
    {
        $is_top = (bool) $is_top;
        if ($is_top) {
            GroupRedis::setTopGroup($uid , $gid);
        } else {
            GroupRedis::unsetTopGroup($uid , $gid);
        }
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 加群验证
    public static function app_direct_join_group_set($uid , $gid , $direct_join_group)
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($group['owner_id'] != $uid) {
            return [
                'code' => 403 ,
                'data' => '您并非群主，无权限设置'
            ];
        }
        $direct_join_group = is_null($direct_join_group) ? 1 : intval($direct_join_group);
        GroupInfoModel::api_direct_join_group_set($gid , $direct_join_group);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 决定加群验证
    public static function app_decide_group_invite_app($request_list_id , $decision)
    {
        $decision = (bool) $decision;
        try {
            Db::startTrans();
            if ($decision) {
                $request = RequestListModel::api_find($request_list_id);
                $gid = $request['exec_id'];
                $sub_type = 'approve';
                RequestListModel::api_sub_type_set($request_list_id , $sub_type);
                // 将 group_member_verify 中的数据更新到 group_member 表中
                $group_member = GroupMemberVerify::api_select($request_list_id);
                foreach ($group_member as $v)
                {
                    // 判断此人是否有进入该群
                    $is_exists = GroupMemberModel::api_find($gid, $v['uid']);
                    if (!$is_exists) {
                        GroupMemberModel::api_insert($request['exec_id'] , $v['uid'] , 'member');
                    }
                }
            } else {
                $sub_type = 'refuse';
                RequestListModel::api_sub_type_set($request_list_id , $sub_type);
            }
            // by cxl
            $request_count = RequestListModel::api_unhandle_count($request['uid']);
            $res = Websocket::requestCount([$request['uid']] , $request_count);
            if ($res['code'] != 0) {
                Db::rollback();
                // return Response::wsError($res['code'] , $res['data']);
                throw new Exception('swoole server error' . (is_object($res['data']) ? json_encode($res['data']) : $res['data']));
            }
            // todo 通知各方验证通知
            // todo 如果群主通过，那么在群里面发送一条消息，加群用户
            // todo 如果群主拒绝，不做任何处理
            Db::commit();
            return [
                'code' => 0 ,
                'data' => []
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
        return [
            'code' => 1 ,
            'data' => []
        ];
    }

    // by cxl 群二维码
    public static function app_group_arcode_data($gid)
    {
        // 加好友链接
        $download = config('app.download');
        // 加一个 uid
        $data = base64_encode(json_encode([
            'type' => 'group' ,
            'id' => $gid
        ]));
        $res = sprintf('%s?add_options=%s' , $download , $data);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // by cxl 群免打扰设置
    public static function app_can_disturb_set($uid , $gid , $can_disturb)
    {
        $can_disturb = (bool) $can_disturb;
        if ($can_disturb) {
            // 允许打扰
            GroupDisturb::api_delete($gid , $uid);
        } else {
           // 免打扰
            GroupDisturb::api_insert($gid , $uid);
        }
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 是否允许被推荐
    public static function app_can_recommend_set($uid , $gid , $can_recommend)
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($uid != $group['owner_id']) {
            return [
                'code' => 403 ,
                'data' => '您并非群主，无权限修改'
            ];
        }
        $can_recommend = $can_recommend == '' ? 1 : intval($can_recommend);
        GroupInfoModel::api_can_recommend_set($gid , $can_recommend);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 群公告
    public static function app_announcement_set($uid , $gid , $announcement = '')
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($uid != $group['owner_id']) {
            return [
                'code' => 403 ,
                'data' => '您并非群主，无权限修改'
            ];
        }
        GroupInfoModel::api_announcement_set($gid , $announcement);
        // 推送消息到群面
        ChatAction::app_group_send($uid, $gid, 31 , $announcement);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 群名称
    public static function app_group_name_set($uid , $gid , $group_name = '')
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($uid != $group['owner_id']) {
            return [
                'code' => 403 ,
                'data' => '您并非群主，无权限修改'
            ];
        }
        GroupInfoModel::api_group_name_set($gid , $group_name);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 群简介
    public static function app_introduction_set($uid , $gid , $introduction = '')
    {
        $group = GroupInfoModel::api_find_byId($gid);
        if ($uid != $group['owner_id']) {
            return [
                'code' => 403 ,
                'data' => '您并非群主，无权限修改'
            ];
        }
        GroupInfoModel::api_introduction_set($gid , $introduction);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    public static function app_updateImage($uid , $param)
    {
        $validator = Validate::make([
            'gid' => 'require' ,
            'img' => 'require' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        // 检查用户是否是群组
        $group = GroupInfoModel::api_find_byId($param['gid']);
        if (empty($group)) {
            return [
                'code' => 404 ,
                'data' => '群已经被删除'
            ];
        }
        if ($group['owner_id'] != $uid) {
            return [
                'code' => 403 ,
                'data' => '你不是群主，无权限修改群头像'
            ];
        }
        GroupInfoModel::api_update_by_cxl($param['gid'] , [
            'img' => $param['img']
        ]);
        return [
            'code' => 0 ,
            'data' => '修改群头像成功'
        ];
    }

    public static function isGroupMaster($uid , array $param)
    {
        $validate = Validate::make([
            'member_id' => 'require' ,
            'gid' => 'require' ,
        ]);
        if (!$validate->check($param)) {
            return [
                'code' => 404 ,
                'data' => $validate->getError()
            ];
        }
        // 检查群是否还存在
        $group = GroupInfoModel::api_find_byId($param['gid']);
        if (empty($group)) {
            return [
                'code' => 404 ,
                'data' => '群不存在'
            ];
        }
        // 检查用户是否在群里面
        $in_group = self::app_is_in_group($param['gid'] , $uid);
        if (!$in_group) {
            return [
                'code' => 403 ,
                'data' => '你不在这个群里面，无法获取该信息' ,
            ];
        }
        // 检查用户是否是群组
        if ($group['owner_id'] != $param['member_id']) {
            return [
                'code' => 0 ,
                'data' => 'n' ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => 'y' ,
        ];
    }
}
