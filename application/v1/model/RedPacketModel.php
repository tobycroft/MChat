<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;

use think\Db;

/**
 * Description of RedPacketModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class RedPacketModel {

	protected static $table = 'cq_red_packet';

	public static function api_insert($sender, $msg_id, $order_id, $reciver, $type, $cid, $name, $img, $amount, $remark, $max_number = 1, $pass = null, $opened = 0) {
		$db = \think\Db::table(self::$table);
		$data = [
			'sender' => $sender,
			'msg_id' => $msg_id,
			'order_id' => $order_id,
			'reciver' => $reciver,
			'type' => $type,
			'cid' => $cid,
			'name' => $name,
			'img' => $img,
			'amount' => $amount,
			'remark' => $remark,
			'pass' => $pass,
			'opened' => $opened,
			'start_time' => time(),
			'end_time' => time() + 86400,
			'max_number' => $max_number,
		];
		return $db->insertGetId($data);
	}

	public static function api_find_byMsgId($msg_id) {
		$db = \think\Db::table(self::$table);
		$where = [
			'msg_id' => $msg_id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_find_byMsgIdAdnType($msg_id, $type = 10) {
		$db = \think\Db::table(self::$table);
		$where = [
			'msg_id' => $msg_id,
			'type' => $type,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_find($id) {
		$db = \think\Db::table(self::$table);
		$where = [
			'id' => $id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_update_opened($reciver, $id) {
		$db = \think\Db::table(self::$table);
		$where = [
			'id' => $id,
			'reciver' => $reciver,
			'opened' => 0,
		];
		$db->where($where);
		$data = [
			'opened' => 1,
		];
		$db->data($data);
		return $db->update();
	}

	public static function api_select_byReciever($reciver, $page = 1) {
		$db = \think\Db::table(self::$table);
		$where = [
			'reciver' => $reciver,
		];
		$db->limit(25);
		$db->page($page);
		$db->where($where);
		return $db->select();
	}

	public static function api_sum_byReciever($reciver) {
		$db = \think\Db::table(self::$table);
		$where = [
			'reciver' => $reciver,
		];
		$db->where($where);
		$db->group('cid');
		$db->field('cid,count(0) as count,SUM(amount) as amount');
		return $db->select();
	}

	public static function api_sum_bySender($sender) {
		$db = \think\Db::table(self::$table);
		$where = [
			'sender' => $sender,
		];
		$db->where($where);
		$db->group('cid');
		$db->field('cid,count(0) as count,SUM(amount) as amount');
		return $db->select();
	}

	public static function api_select_bySender($sender, $page = 1) {
		$db = \think\Db::table(self::$table);
		$where = [
			'sender' => $sender,
		];
		$db->where($where);
		$db->limit(25);
		$db->page($page);
		return $db->select();
	}

	// by cxl 我累计发送的红包金额
	public static function api_count_bySender($uid, $coin_id, $year) {
		$year = empty($year) ? date('Y') : $year;
		$where = [];
		$where[] = ['sender', '=', $uid];
		if (!empty($coin_id)) {
			$where[] = ['cid', '=', $coin_id];
		}
		return Db::table(self::$table)
						->where($where)
						->whereRaw('from_unixtime(start_time , "%Y") = :year', ['year' => $year])
						->count('id');
	}

	// by cxl 我累计发送的红包金额
	public static function api_amount_bySender($uid, $coin_id, $year) {
		$year = empty($year) ? date('Y') : $year;
		$where = [];
		$where[] = ['sender', '=', $uid];
		if (!empty($coin_id)) {
			$where[] = ['cid', '=', $coin_id];
		}
		return Db::table(self::$table)
						->where($where)
						->whereRaw('from_unixtime(start_time , "%Y") = :year', ['year' => $year])
						->sum('amount');
	}

	// by cxl 获取指定用户接收到的红包
	public static function api_select_bySender_v1($uid, $page, $coin_id, $year) {
		$page = empty($page) ? 1 : intval($page);
		$year = empty($year) ? date('Y') : $year;
		$where = [
			['sender', '=', $uid]
		];
		if (!empty($coin_id)) {
			$where[] = ['cid', '=', $coin_id];
		}
		return Db::table(self::$table)
						->where($where)
						->whereRaw('from_unixtime(start_time , "%Y") = :year', ['year' => $year])
						->page($page)
						->limit(25)
						->field('* , remark as red_packet_name , name as coin_type')
						->order('start_time', 'desc')
						->select();
	}

	// by cxl 币种类型，发送的币种类型
	public static function api_coinType($uid) {
		return Db::table(self::$table)
						->where('sender', $uid)
						->group('cid')
						->field('cid as coin_id , name as coin_name , img')
						->select();
	}

	// by cxl 获取红包记录
	public static function api_find_byType($msg_id, $type) {
		$where = [
			['msg_id', '=', $msg_id]
		];
		if ($type == 'private') {
			$where[] = ['type', '<', 10];
		} else if ($type == 'group') {
			$where[] = ['type', '>=', 10];
		} else {
			// 其他预留
		}
		return Db::table(self::$table)
						->where($where)
						->find();
	}

    // by cxl 红包到期后更新是否已经自动退还的 flag
    public static function api_update_timeoutRefund(int $pack_id , int $timeout_refund)
    {
        return Db::table(self::$table)
            ->where('id' , $pack_id)
            ->update([
                'timeout_refund' => $timeout_refund
            ]);
    }

}
