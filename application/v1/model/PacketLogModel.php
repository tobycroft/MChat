<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;

use think\Db;

/**
 * Description of PacketLogModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class PacketLogModel {

	protected static $table = 'cq_packet_log';

	public static function api_insert($reciever, $pack_id, $amount, $remark, $type = 1) {
		$db = Db::table(self::$table);
		$data = [
			'reciever' => $reciever,
			'pack_id' => $pack_id,
			'amount' => $amount,
			'remark' => $remark,
			'type' => $type,
			'date' => time(),
		];
		$db->data($data);
		return $db->insert();
	}

	public static function api_find_uncompelete() {
		$db = Db::table(self::$table);
		$where = [
			['compelete', '=', 0],
		];
		$db->where($where);
		$db->order('id asc');
		return $db->find();
	}

	public static function api_find($reciever, $pack_id) {
		$db = Db::table(self::$table);
		$where = [
			['reciever', '=', $reciever],
			['pack_id', '=', $pack_id],
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_select($reciever) {
		$db = Db::table(self::$table);
		$where = [
			['reciever', '=', $reciever],
		];
		$db->where($where);
		return $db->select();
	}

	// by cxl 获取领取记录
	public static function api_select_ByRedPacketId($red_packet_id)
    {
        return Db::table(self::$table)
            ->where('pack_id' , $red_packet_id)
            ->select();
    }

    // by cxl 获取指定用户接收到的红包
    public static function api_select_byReceiver($uid , $page , $coin_id , $year)
    {
        $page = empty($page) ? 1 : intval($page);
        $year = empty($year) ? date('Y') : $year;
        $where = [
            ['pl.reciever' , '=' , $uid]
        ];
        if (!empty($coin_id)) {
            $where[] = ['rp.cid' , '=' , $coin_id];
        }
        return Db::table(self::$table)
            ->alias('pl')
            ->join('cq_red_packet rp' , 'pl.pack_id = rp.id')
            ->where($where)
            ->whereRaw('from_unixtime(pl.date , "%Y") = :year' , ['year' => $year])
            ->page($page)
            ->limit(25)
            ->field('pl.* , rp.sender , rp.name as coin_type')
            ->order('pl.date' , 'desc')
            ->select();
    }

    // by cxl 我累计收到的红包数量
    public static function api_count_byReceiver($uid , $coin_id , $year)
    {
        $year = empty($year) ? date('Y') : $year;
        $where = [];
        $where[] = ['pl.reciever' , '=' , $uid];
        if (!empty($coin_id)) {
            $where[] = ['rp.cid' , '=' , $coin_id];
        }
        return Db::table(self::$table)
            ->alias('pl')
            ->join('cq_red_packet rp' , 'pl.pack_id = rp.id')
            ->where($where)
            ->whereRaw('from_unixtime(pl.date , "%Y") = :year' , ['year' => $year])
            ->count('pl.id');
    }

    // by cxl 我累计收到的红包金额
    public static function api_amount_byReceiver($uid , $coin_id , $year)
    {
        $year = empty($year) ? date('Y') : $year;
        $where = [];
        $where[] = ['pl.reciever' , '=' , $uid];
        if (!empty($coin_id)) {
            $where[] = ['rp.cid' , '=' , $coin_id];
        }
        return Db::table(self::$table)
            ->alias('pl')
            ->join('cq_red_packet rp' , 'pl.pack_id = rp.id')
            ->where($where)
            ->whereRaw('from_unixtime(pl.date , "%Y") = :year' , ['year' => $year])
            ->sum('pl.amount');
    }

    // by cxl 手气最佳
    public static function api_best_count_byReceiver($uid , $coin_id , $year)
    {
        $year = empty($year) ? date('Y') : $year;
        $sql =
<<<EOT
            SELECT
                count(pl.id) as `count`
            FROM
                cq_packet_log AS pl
                LEFT JOIN cq_red_packet AS rq ON pl.pack_id = rq.id 
            WHERE
                pl.reciever = {$uid}
EOT;
        if (!empty($coin_id)) {
            $sql .= " AND rq.cid = {$coin_id} ";
        }
        $sql .=
<<<EOT
                AND from_unixtime( pl.date, '%Y' ) = '{$year}' 
                AND ( SELECT id FROM cq_packet_log WHERE pack_id = pl.pack_id ORDER BY amount DESC LIMIT 1 )
EOT;
        $res = Db::query($sql);
        return $res[0]['count'];
    }

    // by cxl 币种类型，接收的币种类型
    public static function api_coinType($uid)
    {
        return Db::table(self::$table)
            ->alias('pl')
            ->join('cq_red_packet rp' , 'pl.pack_id = rp.id')
            ->where('pl.reciever' , $uid)
            ->group('rp.cid')
            ->field('rp.cid as coin_id , rp.name as coin_name , rp.img')
            ->select();
    }

    // by cxl 统计总共收取的红包金额
    public static function api_receiveTotal($pack_id)
    {
        return Db::table(self::$table)
            ->where('pack_id' , $pack_id)
            ->sum('amount');
    }

    // 统计单个红包被领取的金额
    public static function api_sum_byPackId(int $pack_id)
    {
        return Db::table(self::$table)
            ->where('pack_id' , $pack_id)
            ->sum('amount');
    }

    // 统计单个红包被领取的个数
    public static function api_count_byPackId(int $pack_id)
    {
        return Db::table(self::$table)
            ->where('pack_id' , $pack_id)
            ->count();
    }
}
