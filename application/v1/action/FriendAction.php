<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\ChatBlacklistModel;
use app\v1\model\GroupMemberModel;
use app\v1\model\RequestListModel;
use app\v1\model\SingleFriendModel;
use app\v1\model\UserInfoModel;
use app\v1\util\Response;
use app\v1\util\Websocket;
use Exception;
use think\Db;

/**
 * Description of FriendAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class FriendAction {

	//put your code here

	public static function app_search_friend($fid) {

	}

	public static function app_delete_friend($uid, $fid) {
		\think\Db::startTrans();
		if (SingleFriendModel::api_delete_byUidFid($uid, $fid)) {
			if (SingleFriendModel::api_delete_byUidFid($fid, $uid)) {
				\app\v1\model\SingleChatModel::api_delete_byUidFid($uid, $fid);
				\app\v1\model\SingleChatModel::api_delete_byUidFid($fid, $uid);
				\think\Db::commit();
                Websocket::refresh([$uid, $fid], ChatAction::app_chatid_gen($uid , $fid));
				return true;
			}
		}
		\think\Db::rollback();
		return false;
	}

	public static function app_add_friend($uid, $fid) {
		if (SingleFriendModel::api_find_byUidFid($uid, $fid)) {
			return false;
		} else {
			\think\Db::startTrans();
			if (SingleFriendModel::api_insert($uid, $fid)) {
				if (SingleFriendModel::api_insert($fid, $uid)) {
					if (!RequestListModel::api_delete_byExecId($fid, $uid)) {
						\think\Db::rollback();
						return false;
					}
					if (!RequestListModel::api_insert($uid, $fid, 'friend', 'approve')) {
						\think\Db::rollback();
						return false;
					}
					$request_count = RequestListModel::api_unhandle_count($uid);
					$res = Websocket::requestCount([$uid] , $request_count);
					if ($res['code'] != 0) {
					    Db::rollback();
					    // return Response::wsError($res['code'] , $res['data']);
                        throw new Exception('swoole server error' . (is_object($res['data']) ? json_encode($res['data']) : $res['data']));
                    }
					if (!RequestListModel::api_insert($fid, $uid, 'friend', 'approve')) {
						\think\Db::rollback();
						return false;
					}
                    $request_count = RequestListModel::api_unhandle_count($fid);
                    $res = Websocket::requestCount([$fid] , $request_count);
                    if ($res['code'] != 0) {
                        Db::rollback();
                        // return Response::wsError($res['code'] , $res['data']);
                        throw new Exception('swoole server error' . (is_object($res['data']) ? json_encode($res['data']) : $res['data']));
                    }
					\think\Db::commit();
					return true;
				}
			}
			\think\Db::rollback();
			return false;
		}
	}

	public static function app_is_friend($uid, $fid) {
		$ret = SingleFriendModel::api_find_byUidFid($uid, $fid);
		if ($ret) {
			return true;
		} else {
			return false;
		}
	}

	// 请求添加朋友
	public static function app_friend_request($uid, $fid, $comment = '') {
		if ($uid == $fid) {
			return [
				'code' => 10,
				'data' => '不能添加自己为好友'
			];
		}
		if (self::app_is_friend($uid, $fid)) {
			return [
				'code' => 1,
				'data' => '已经是好友'
			];
		}
		try {
		    Db::startTrans();
            RequestListModel::api_delete_byExecId($fid, $uid);
            RequestListModel::api_insert($fid, $uid, 'friend', 'add', null, $comment);
            $request_count = RequestListModel::api_unhandle_count($fid);
            // 发送推送
            $res = Websocket::requestCount([$fid] , $request_count);
            if ($res['code'] != 0) {
                Db::rollback();
                return Response::wsError($res['code'] , $res['data']);
            }
            Db::commit();
            return Response::success($res['data']);
        } catch(Exception $e) {
		    Db::rollback();
            throw $e;
        }
	}

	// 对方同意成为朋友
	public static function app_friend_request_approve($uid, $fid, $approve = true, $comment = '') {
		if ($approve) {
			return self::app_add_friend($uid, $fid);
		} else {
			//fid is the sender who sents the request to the uid
			\think\Db::startTrans();
			if (!RequestListModel::api_delete_byExecId($fid, $uid)) {
				\think\Db::rollback();
				return false;
			}
			if (RequestListModel::api_insert($uid, $fid, 'friend', 'refuse', null, $comment)) {
                $request_count = RequestListModel::api_unhandle_count($uid);
                $res = Websocket::requestCount([$uid] , $request_count);
                if ($res['code'] != 0) {
                    Db::rollback();
                    // return Response::wsError($res['code'] , $res['data']);
                    throw new Exception('swoole server error' . (is_object($res['data']) ? json_encode($res['data']) : $res['data']));
                }
				\think\Db::commit();
				return true;
			} else {
				\think\Db::rollback();
				return false;
			}
		}
	}

	// by cxl
	public static function app_friend_list($uid) {
		$ret = SingleFriendModel::api_select_byUid($uid);
		if ($ret) {
			foreach ($ret as $key => $value) {
				$value['user_info'] = MemberAction::app_user_info($value['fid']);
				$value['uname'] = ($value['uname']) ?: $value['user_info']['uname'];
				$ret[$key] = $value;
			}
			return $ret;
		} else {
			return [];
		}
	}

    // by cxl
    public static function app_recent_friend_list($uid) {
        $ret = SingleFriendModel::api_select_byUid($uid);
        if ($ret) {
            foreach ($ret as &$value) {
                $value['user_info'] = MemberAction::app_user_info($value['fid']);
                $value['uname'] = ($value['uname']) ?: $value['user_info']['uname'];
                // 获取最近一条消息
                $value['lastmsg'] = MessageAction::app_private_last_msg($uid , $value['fid']);
            }
            return $ret;
        } else {
            return [];
        }
    }

	public static function app_has_new_friend($uid) {
		$ret = RequestListModel::api_select_byTypeandSubType($uid);
		return count($ret);
	}

	// by cxl 新朋友
    public static function app_new_friend($uid)
    {
         $res = RequestListModel::api_new_friend($uid);
         foreach ($res as &$v)
         {
             $v['user_info'] = UserInfoModel::api_find_byUid($v['exec_id']);
             if (!empty($v['user_info'])) {
                 $v['user_info']['uname'] = !empty($v['uname']) ? $v['uname'] : config('app.username');
                 $v['user_info']['face'] = !empty($v['face']) ? $v['face'] : config('app.avatar');
             }
         }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // by cxl 个人二维码
    public static function app_qrcode_data($personal_uid)
    {
        // 加好友链接
        $download = config('app.download');
        // 加一个 uid
        $data = base64_encode(json_encode([
            'type'  => 'private' ,
            'id'    => $personal_uid
        ]));
        $res = sprintf('%s?add_options=%s' , $download , $data);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // by cxl 修改好友备注
    public static function app_remark_set($uid , $fid , $remark)
    {
        SingleFriendModel::api_remark_set($uid , $fid , $remark);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl uname 设置
    public static function app_uname_set($uid , $fid , $uname = '')
    {
        SingleFriendModel::api_uname_set($uid , $fid , $uname);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 设置黑名单
    public static function app_black_set($uid , $fid , $black)
    {
        $chat_id = ChatAction::app_chatid_gen($uid , $fid);
        $black = (bool) $black;
        if ($black) {
            ChatBlacklistModel::api_insert($chat_id , $uid , $fid);
        } else {
            ChatBlacklistModel::api_delete($chat_id , $fid);
        }
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // by cxl 共同群组数量
    public static function app_share_group_count($uid , $fid)
    {
        // 查询共同好友数量
        return GroupMemberModel::api_share_group_count($uid , $fid);
    }

    // by cxl 共同群组
    public static function app_share_group($uid , $fid)
    {
        // 查询共同好友数量
        $res = GroupMemberModel::api_share_group($uid , $fid);
        return [
            'code'  => 0 ,
            'data'  => $res
        ];
    }

    // 检查是否被加入黑名单
    public static function app_is_black($uid , $fid)
    {
        $chat_id = ChatAction::app_chatid_gen($uid , $fid);
        return (bool) ChatBlacklistModel::api_count($chat_id , $fid);
    }

    // by cxl
    public static function app_friend_and_group($uid)
    {
        $friend = self::app_friend_list($uid);
        array_walk($friend , function(&$v){
            $v['type'] = 'private';
        });
        $group  = GroupAction::util_group_list($uid);
        array_walk($group , function(&$v){
            $v['type'] = 'group';
        });
        $res = array_merge($friend , $group);
        usort($res , function($a , $b){
            if ($a['date'] == $b['date']) {
                return 0;
            }
            return $a['date'] > $b['date'] ? -1 : 1;
        });
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // by cxl 最近好友 和 群组
    public static function app_recent_friend_and_group($uid)
    {
        $friend = self::app_recent_friend_list($uid);
        array_walk($friend , function(&$v){
            $v['type'] = 'private';
        });
        $group  = GroupAction::util_recent_group_list($uid);
        array_walk($group , function(&$v){
            $v['type'] = 'group';
        });
        $res = array_merge($friend , $group);
        usort($res , function($a , $b){
            if (!isset($a['lastmsg']) && !isset($b['lastmsg'])) {
                return 0;
            }
            if (!isset($a['lastmsg'])) {
                return 1;
            }
            if (!isset($b['lastmsg'])) {
                return -1;
            }
            if ($a['lastmsg']['date'] == $b['lastmsg']['date']) {
                return 0;
            }
            return $a['lastmsg']['date'] > $b['lastmsg']['date'] ? -1 : 1;
        });
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}
