<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;

use think\Db;

/**
 * Description of RequestListModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class RequestListModel {

	protected static $table = 'cq_request_list';

	public static function api_find($id) {
		$db = Db::table(self::$table);
		$where = [
			'id' => $id,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->find();
	}

	public static function api_find_byUid($uid, $id) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'id' => $id,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->find();
	}

	public static function api_select() {
		$db = Db::table(self::$table);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_select_byUid($uid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_select_byType($uid, $type = 'friend') {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'type' => $type,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_select_bySubType($uid, $sub_type = 'add') {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'sub_type' => $sub_type,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_select_byTypeandSubType($uid, $type = 'friend', $sub_type = 'add') {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'type' => $type,
			'sub_type' => $sub_type,
		];
		$db->where($where);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_delete($uid, $id) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'id' => $id,
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_delete_byExecId($uid, $exec_id) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'exec_id' => $exec_id,
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_insert($uid, $exec_id, $type = 'friend', $sub_type = 'add', $extra = null, $comment = '') {
		$db = Db::table(self::$table);
		$data = [
			'uid' => $uid,
			'exec_id' => $exec_id,
			'type' => $type, //friend,group
			'sub_type' => $sub_type, //add,invite,refuse,approve
			'extra' => $extra,
			'comment' => $comment,
			'date' => time(),
		];
		return $db->insertGetId($data);
	}

    // by cxl 更新字段：sub_type
    public static function api_sub_type_set($id , $sub_type)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->update([
                'sub_type' => $sub_type
            ]);
    }

    // by cxl 新朋友（等待对方验证的请求）
    public static function api_new_friend($uid)
    {
        return Db::table(self::$table)
            ->where([
                ['uid' , '=' , $uid] ,
                ['type' , '=' , 'friend'] ,
                ['sub_type' , '=' , 'add']
            ])
            ->select();
    }

    // by cxl 获取验证列表
    public static function api_list($uid)
    {
        return Db::table(self::$table)
            ->where('uid' , $uid)
            ->order('date' , 'desc')
            ->select();
    }

    // 备注修改
    public static function api_comment_set($id , $comment)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->update([
                'comment' => $comment
            ]);
    }

    // by cxl 获取未处理的验证请求数量
    public static function api_unhandle_count($uid)
    {
        return Db::table(self::$table)
            ->where([
                ['uid' , '=' , $uid] ,
                ['sub_type' , '<>' , 'refuse'] ,
                ['sub_type' , '<>' , 'approve'] ,
                ['sub_type' , '<>' , 'kick'] ,
            ])
            ->count();
    }

    // by cxl 删除验证消息
    public static function api_delByIds(array $id_list = [])
    {
        return Db::table(self::$table)
            ->whereIn('id' , $id_list)
            ->delete();
    }

    public static function api_find_byIdAndUid($id , $uid)
    {
        return Db::table(self::$table)
            ->where([
                ['id' , '=' , $id] ,
                ['uid' , '=' , $uid]
            ])
            ->find();
    }
}
