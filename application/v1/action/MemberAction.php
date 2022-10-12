<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\SingleFriendModel;
use app\v1\model\UserInfoModel;

/**
 * Description of MemberAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class MemberAction {

	public static function app_sync_userinfo($uid) {
        $remote_user = \app\v1\service\AuthService::serv_get_userinfo($uid);
        if (empty($remote_user)) {
            return false;
        }
        $user = UserInfoModel::api_find_byUid($uid);
        if ($user) {
            UserInfoModel::api_update($uid , $remote_user['phone']);
            return true;
        }
        return UserInfoModel::api_insert_sync($uid, $remote_user['username'], $remote_user['phone']);
	}

	// @param $uid_extra 额外的 uid，用以判断是否获取额外信息 by cxl
	// 由于该方法使用的频率过高，所以无法改动原格式
	// 故而采取附加的方式，$uid_extra 如果提供，那么实际上就是当前登录用户 uid
	public static function app_user_info($uid, $uid_extra = null) {
		$ret = UserInfoModel::api_find_byUid($uid);
		if ($ret) {
			$ret['uname'] = $ret['uname'] ?: config('app.username');
			$ret['is_remark'] = false;
//			$ret['face'] = image_url($ret['face']) ?: config('app.avatar');
			$ret['face'] = $ret['face'] ?: config('app.avatar');
			$ret['introduction'] = $ret['introduction'] ?: config('app.signature');

			if (!empty($uid_extra)) {
				// by cxl 额外用户信息
				$is_friend = FriendAction::app_is_friend($uid_extra, $uid);
				if ($is_friend) {
					// 如果不是好友的话，不做处理
					$friend = SingleFriendModel::api_find_byUidFid($uid_extra, $uid);
					$ret['nickname'] = $ret['uname'];
					$ret['is_remark'] = empty($friend['uname']) ? false : true;
					$ret['uname'] = empty($friend['uname']) ?
							empty($ret['uname']) ? config('app.username') : $ret['uname'] : $friend['uname'];
				} else {
					$ret['nickname'] = null;
					$ret['is_remark'] = false;
				}
				// 共同群组
				$ret['share_group_count'] = FriendAction::app_share_group_count($uid_extra, $uid);
				// 是否加入黑名单
				$ret['is_black'] = FriendAction::app_is_black($uid_extra, $uid) ? 1 : 0;
				// 朋友圈-好友权限
				$ret['priv_for_firend_circle'] = FriendCircleAction::util_friendPriv($uid_extra, $uid);
			}
			return $ret;
		} else {
			return false;
		}
	}

	public static function app_user_info_find($uid, $fid) {
		$res = self::app_user_info($fid, $uid);
		if ($res) {
			return [
				'code' => 0,
				'data' => $res
			];
		}
		return [
			'code' => 400,
			'data' => '未找到对应用户'
		];
	}

    public static function app_search($value) {
        $arr = [];
        $byName = self::app_search_uname($value);
        if ($byName) {
            foreach ($byName as $ret) {
                $ret['uname'] = $ret['uname'] ?: '未设定昵称';
                $ret['face'] = $ret['face'] ?: config('app.avatar');
                array_push($arr, $ret);
            }
        }
        $byPhone = self::app_search_phone($value);
        if ($byPhone) {
            foreach ($byPhone as $ret) {
                $ret['uname'] = $ret['uname'] ?: '未设定昵称';
                $ret['face'] = $ret['face'] ?: config('app.avatar');
                array_push($arr, $ret);
            }
        }
        $byUid = self::app_search_byUid($value);
        if ($byUid) {
            $byUid['uname'] = $byUid['uname'] ?: '未设定昵称';
            $byUid['face'] = $byUid['face'] ?: config('app.avatar');
            array_push($arr, $byUid);
        }
        return $arr;
    }

    public static function app_search_uname($uname) {
        if (empty($uname)) {
            return ;
        }
        $user = UserInfoModel::api_select_byUname($uname);
        return $user;
    }

    public static function app_search_phone($phone) {
        if (empty($phone)) {
            return ;
        }
//        $phone = sprintf('%s%s' , '86' , $phone);
        $user = UserInfoModel::api_select_byPhone($phone);
        return $user;
    }

    public static function app_search_byUid($uid) {
        if (empty($uid)) {
            return ;
        }
        $user = UserInfoModel::api_find_byUid($uid);
        return $user;
    }

	public static function app_edit_self_uname($uid, $uname) {
		if (UserInfoModel::api_update_uname($uid, $uname)) {
			return [
				'code' => 0,
				'data' => 'compelete'
			];
		} else {
			return [
				'code' => 1,
				'data' => 'fail'
			];
		}
	}

	public static function app_edit_self_face($uid, $face) {
		if (UserInfoModel::api_update_face($uid, $face)) {
			return [
				'code' => 0,
				'data' => 'compelete'
			];
		} else {
			return [
				'code' => 1,
				'data' => 'fail'
			];
		}
	}

	public static function app_edit_self_info($uid, $uname, $introduction, $sex, $telephone, $mail, $career, $company, $school, $birthday) {
		if (UserInfoModel::api_update_all($uid, $uname, $introduction, $sex, $telephone, $mail, $career, $company, $school, $birthday)) {
			return [
				'code' => 0,
				'data' => 'compelete'
			];
		} else {
			return [
				'code' => 1,
				'data' => 'fail'
			];
		}
	}

	public static function app_edit_introduction($uid, $introduction)
    {
        UserInfoModel::api_update_introduction($uid, $introduction);
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

}
