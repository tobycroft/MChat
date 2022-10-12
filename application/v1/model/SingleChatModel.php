<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;

use think\Db;

/**
 * Description of SingleFriendModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class SingleChatModel {

	//put your code here
	protected static $table = 'cq_single_chat';

	public static function api_count_byUidFid($uid, $fid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => ['in', $uid . ',' . $fid],
			'fid' => ['in', $uid . ',' . $fid],
		];
		$db->where($where);
		return $db->count(0);
	}

	public static function api_count_byChatId($chat_id) {
		$db = Db::table(self::$table);
		$where = [
			'chat_id' => $chat_id
		];
		$db->where($where);
		return $db->count(0);
	}

	public static function api_delete_byChatId($chat_id) {
		$db = Db::table(self::$table);
		$where = [
			'chat_id' => $chat_id
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_delete_byUidFid($uid, $fid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'fid' => $fid,
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_insert($uid, $fid, $chat_id) {
		$db = Db::table(self::$table);
		$data = [
			'uid' => $uid,
			'fid' => $fid,
			'chat_id' => $chat_id,
			'date' => time(),
		];
		$db->data($data);
		return $db->insert();
	}

	public static function api_select_byUid($uid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid
		];
		$db->where($where);
		$db->order('id desc');
		return $db->select();
	}

	public static function api_find_byChatId($chat_id)
    {
        return Db::table(self::$table)->where('chat_id' , $chat_id)->find();
    }
}
