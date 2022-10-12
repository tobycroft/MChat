<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;



use app\v1\redis\BaseRedis;
use think\Db;

/**
 * Description of GroupMemberModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class GroupMemberModel {

	public static $table = 'cq_group_member';

	public static function api_insert($gid, $uid, $role = 'member') {
        self::api_clear_cache_for_group($uid);
        self::api_clear_cache_for_member($gid);
		$db = Db::table(self::$table);
		$data = [
			'gid' => $gid,
			'uid' => $uid,
			'role' => $role,
			'date' => time(),
		];
		return $db->insertGetId($data);
	}

	public static function api_find($gid, $uid) {
		$db = Db::table(self::$table);
		$where = [
			'gid' => $gid,
			'uid' => $uid,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_select($gid) {
	    $key = BaseRedis::key('group_member' , $gid);
	    $res = cache($key);
	    if (empty($res)) {
            $res = Db::table(self::$table)
                ->where([
                    'gid' => $gid,
                ])
                ->select();
            cache($key , $res , cache_duration('l'));
        }
        return $res;
	}

	public static function api_count($gid) {
		$db = Db::table(self::$table);
		$where = [
			'gid' => $gid,
		];
		$db->where($where);
		return $db->count();
	}

	public static function api_select_byUid($uid) {
//        $key = BaseRedis::key('group_for_user' , $uid);
//        $res = cache($key);
//        if (empty($res)) {
            $res = Db::table(self::$table)
                ->where('uid' , $uid)
                ->order('id' , 'desc')
                ->select();
//            cache($key , $res , cache_duration('l'));
//        }
        return $res;
	}

	public static function api_delete($gid, $uid) {
	    self::api_clear_cache_for_group($uid);
        self::api_clear_cache_for_member($gid);
		$db = Db::table(self::$table);
		$where = [
			'gid' => $gid,
			'uid' => $uid,
		];
		$db->where($where);
		return $db->delete();
	}

    // by cxl 共同群组
    public static function api_share_group_count($uid , $fid)
    {
        $res = Db::query(
<<<EOT
            SELECT
                count(gi.id) as `count`
            FROM
                cq_group_info AS gi 
            WHERE
                ( SELECT count( id ) FROM cq_group_member WHERE gi.id = gid AND uid = {$uid} ) > 0 
                AND ( SELECT count( id ) FROM cq_group_member WHERE gi.id = gid AND uid = {$fid} ) > 0;
EOT
        );
        return $res[0]['count'];
    }

    // by cxl 共同群组列表
    public static function api_share_group($uid , $fid)
    {
        $res = Db::query(
<<<EOT
            SELECT
                gi.*
            FROM
                cq_group_info AS gi 
            WHERE
                ( SELECT count( id ) FROM cq_group_member WHERE gi.id = gid AND uid = {$uid} ) > 0 
                AND ( SELECT count( id ) FROM cq_group_member WHERE gi.id = gid AND uid = {$fid} ) > 0;
EOT
        );
        return $res;
    }

    public static function api_clear_cache_for_group($uid)
    {
        $key = BaseRedis::key('group_for_user' , $uid);
        cache($key , null);
    }

    public static function api_clear_cache_for_member($gid)
    {
        $key = BaseRedis::key('group_member' , $gid);
        cache($key , null , 1);
    }

    // by cxl
    public static function api_delByGid($gid)
    {
        return Db::table(self::$table)
            ->where('gid' , $gid)
            ->delete();
    }
}
