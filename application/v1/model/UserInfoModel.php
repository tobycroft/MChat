<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\model;

use think\Db;
use app\v1\redis\BaseRedis;

/**
 * Description of UserInfoModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class UserInfoModel {

	//put your code here
	protected static $table = 'cq_user_info';

	public static function api_find($id) {
		$db = Db::table(self::$table);
		$where = [
			'id' => $id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_find_byUid($uid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$ret = $db->find();
		return $ret;
	}

	public static function api_find_byUid2($uid) {
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$ret = $db->find();
		if ($ret) {
			return $ret;
		} else {
			self::api_insert($uid);
			return self::api_find_byUid($uid);
		}
	}

	public static function api_select_inUids($uids) {
		$db = Db::table(self::$table);
		$where = [
			['uid', 'in', $uids]
		];
		$db->where($where);
		return $db->select();
	}

	public static function api_select_byUname($uname) {
		$db = Db::table(self::$table);
		$where = [
			['uname', '=', $uname]
		];
		$db->order('exp desc');
		$db->where($where);
		return $db->select();
	}

	public static function api_select_byPhone($telephone) {
		$db = Db::table(self::$table);
		$where = [
			['telephone', '=', $telephone]
		];
		$db->order('exp desc');
		$db->where($where);
		return $db->select();
	}

	public static function api_insert($uid) {
		$db = Db::table(self::$table);
		$data = [
			'uid' => $uid,
			'change_date' => time(),
			'date' => time(),
		];
		$db->data($data);
		return $db->insert();
	}

	public static function api_insert_sync($uid, $uname, $telephone) {
		$db = Db::table(self::$table);
		$data = [
			'uname' => $uname,
			'telephone' => $telephone,
			'uid' => $uid,
			'change_date' => time(),
			'date' => time(),
		];
		$db->data($data);
		return $db->insert();
	}

	public static function api_update_uname($uid, $uname) {
		self::api_clear_cache($uid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$data = [
			'uname' => $uname,
			'change_date' => time(),
		];
		$db->data($data);
		return $db->update();
	}

	public static function api_update_face($uid, $face) {
		self::api_clear_cache($uid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$data = [
			'face' => $face,
			'change_date' => time(),
		];
		$db->data($data);
		return $db->update();
	}

	public static function api_update_intro($uid, $introduction) {
		self::api_clear_cache($uid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$data = [
			'introduction' => $introduction,
			'change_date' => time(),
		];
		$db->data($data);
		return $db->update();
	}

	public static function api_update_exp($uid, $exp) {
		self::api_clear_cache($uid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$db->inc('exp', $exp);
		$db->data('change_date', time());
		return $db->update();
	}

	public static function api_update_all($uid, $uname, $introduction, $sex, $telephone, $mail, $career, $company, $school, $birthday) {
		self::api_clear_cache($uid);
		$db = Db::table(self::$table);
		$where = [
			'uid' => $uid,
		];
		$db->where($where);
		$data = [
			'uname' => $uname,
			'introduction' => $introduction,
			'sex' => $sex,
			'telephone' => $telephone,
			'mail' => $mail,
			'career' => $career,
			'company' => $company,
			'school' => $school,
			'birthday' => $birthday,
			'change_date' => time(),
		];
		$db->data($data);
		return $db->update();
	}

	public static function api_can_notice_set($uid, $can_notice) {
		self::api_clear_cache($uid);
		return Db::table(self::$table)
						->where('uid', $uid)
						->update([
							'can_notice' => $can_notice
		]);
	}

	public static function api_clear_cache($uid) {
		$key = BaseRedis::key('user2', $uid);
		return cache($key, null);
	}

//	public static function api_update($uid , $username , $phone)
	public static function api_update($uid , $phone)
    {
        self::api_clear_cache($uid);
        return Db::table(self::$table)->where('uid' , $uid)
            ->update([
//                'uname' => $username ,
                'telephone' => $phone
            ]);
    }

    public static function api_update_introduction($uid , $introduction)
    {
        self::api_clear_cache($uid);
        return Db::table(self::$table)
            ->where('uid' , $uid)
            ->update([
                'introduction' => $introduction
            ]);
    }

}
