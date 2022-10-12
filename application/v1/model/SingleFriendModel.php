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
 * Description of SingleFriendModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class SingleFriendModel {

	//put your code here
	protected static $table = 'cq_single_friend';

	public static function api_select_byUid($uid) {
	    $key = BaseRedis::key('friend' , $uid);
	    $res = cache($key);
	    if (empty($res)) {
            $db = Db::table(self::$table);
            $where = [
                'uid' => $uid,
            ];
            $db->where($where);
            $res = $db->select();
            cache($key , $res , cache_duration('l'));
        }
        return $res;
	}

	// by cxl 请勿使用该方法！
	public static function api_delete($id) {
        $res = self::api_find_byId($id);
        if (!empty($res)) {
            self::api_clear_cache($res['uid'] , $res['fid']);
        }
		return Db::table(self::$table)
            ->where([
                'id' => $id,
            ])->delete();
	}

	public static function api_delete_byUidFid($uid, $fid) {
	    self::api_clear_cache($uid , $fid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
			'fid' => $fid,
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_find_byUidFid($uid, $fid) {
	    $key = BaseRedis::key('is_friend' , $uid . $fid);
	    $res = cache($key);
	    if (empty($res)) {
            $db = Db::table(self::$table);
            $where = [
                'uid' => $uid,
                'fid' => $fid,
            ];
            $db->where($where);
            $res = $db->find();
            cache($key , $res , cache_duration('l'));
        }
        return $res;
	}

	public static function api_find_byId($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->find();
    }

	public static function api_insert($uid, $fid) {
        self::api_clear_cache($uid , $fid);
		$db = Db::table(self::$table);
		$data = [
			'uid' => $uid,
			'fid' => $fid,
			'date' => time(),
		];
		$db->data($data);
		return $db->insert();
	}

	// by cxl 搜索相关字段
	public static function api_search_byUname($uid , $uname = '')
    {
        return Db::table(self::$table)
            ->alias('sf')
            ->join('cq_user_info ui' , 'sf.fid = ui.uid')
            ->where('sf.uid' , $uid)
            ->where('sf.uname|ui.uname' , 'like' , "%{$uname}%")
            ->select();
    }

    // by cxl remark
    public static function api_remark_set($uid , $fid , $remark)
    {
        self::api_clear_cache($uid , $fid);
        return Db::table(self::$table)
            ->where([
                ['uid' , '=' , $uid] ,
                ['fid' , '=' , $fid] ,
            ])
            ->update([
                'remark' => $remark
            ]);
    }

    // by cxl uname
    public static function api_uname_set($uid , $fid , $uname = '')
    {
        self::api_clear_cache($uid , $fid);
        return Db::table(self::$table)
            ->where([
                ['uid' , '=' , $uid] ,
                ['fid' , '=' , $fid] ,
            ])
            ->update([
                'uname' => $uname
            ]);
    }

    public static function api_clear_cache($uid , $fid)
    {
        $key = BaseRedis::key('friend' , $uid);
        cache($key , null , 1);
        $key = BaseRedis::key('is_friend' , $uid . $fid);
        cache($key , null , 1);
    }

}
