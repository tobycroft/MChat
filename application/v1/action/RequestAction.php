<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

use app\v1\model\RequestListModel;
use app\v1\model\SingleFriendModel;
use app\v1\model\UserInfoModel;
use app\v1\util\Response;
use app\v1\util\Websocket;
use Exception;
use think\Db;
use think\Validate;

/**
 * Description of RequestAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class RequestAction {

	public static function app_request_logic($uid, $request_id, $approve = true, $comment = '') {
		$ret = \app\v1\model\RequestListModel::api_find_byUid($uid, $request_id);
		if ($ret) {
			switch ($ret['type']) {
				case 'friend':
					$exec = FriendAction::app_friend_request_approve($ret['exec_id'], $uid, $approve, $comment);
					break;

				case 'group':
					$exec = GroupAction::app_group_request_approve($uid, $ret['exec_id'], $approve, $comment);
					break;

				default:
					break;
			}
			return $exec;
		}
	}

	public static function app_friend_request($uid) {
		$ret = \app\v1\model\RequestListModel::api_select_byType($uid, 'friend');
		if ($ret) {
			foreach ($ret as $key => $value) {
				$value['user_info'] = MemberAction::app_user_info($value['exec_id']);
				$ret[$key] = $value;
			}
			return $ret;
		} else {
			return [];
		}
	}

	// 请求
    public static function app_list($uid)
    {
        $res = RequestListModel::api_list($uid);
        foreach ($res as &$v)
        {
            if ($v['type'] == 'friend') {
                $v['user'] = MemberAction::app_user_info($v['exec_id']);
                $v['user'] = empty($v['user']) ? [] : $v['user'];
            }
            if ($v['type'] == 'group') {
                $v['group'] = GroupAction::app_group_info($v['exec_id']);
                $v['group'] = empty($v['group']) ? [
                    'group_name' => '该群已经被解散' ,
                    'img' => config('app.group_avatar') ,
                ] : $v['group'];
            }
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 获取请求数量
    public static function unhandle_count($uid)
    {
        $res = RequestListModel::api_unhandle_count($uid);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 删除验证消息
    public static function delRequest($uid , array $param)
    {
        $validator = Validate::make([
            'id_list' => 'require' ,
        ] , [
            'id_list.require' => '请提供待删除项id 列表'
        ]);
        if (!$validator->check($param)) {
            return Response::error($validator->getError());
        }
        $id_list = json_decode($param['id_list'] , true);
        if (empty($id_list)) {
            return Response::error('请提供待删除项id 列表');
        }
        // 检查这些请求是否是你本人的验证消息（其他人无法删除你的验证消息）
        foreach ($id_list as $v)
        {
            if (!RequestListModel::api_find_byIdAndUid($v , $uid)) {
                return Response::error('包含他人的验证消息，禁止操作' , 403);
            }
        }
        try {
            Db::startTrans();
            $res = RequestListModel::api_delByIds($id_list);
            // 通知前端验证消息更新
            $request_count = RequestListModel::api_unhandle_count($uid);
            $ws_res = Websocket::requestCount([$uid] , $request_count);
            if ($ws_res['code'] != 0) {
                Db::rollback();
                return Response::wsError($ws_res['data'] , $ws_res['code']);
            }
            Db::commit();
            return Response::success($res);
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

}
