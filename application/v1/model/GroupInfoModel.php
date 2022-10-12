<?php

namespace app\v1\model;

use app\v1\redis\BaseRedis;
use think\Db;

class GroupInfoModel {

	public static $table = 'cq_group_info';

	public static function api_insert($owner_id, $group_name, $introduction, $category = 'default' , $type = 1 , $expire = null) {
		$db = Db::table(self::$table);
		$data = [
			'owner_id' => $owner_id,
			'group_name' => $group_name,
			'introduction' => $introduction,
			'category' => $category,
			'date' => time(),
			'type' => $type,
			'expire' => $expire,
		];
		return $db->insertGetId($data);
	}

	public static function api_count_owner($owner_id) {
		$db = Db::table(self::$table);
		$where = [
			['owner_id', '=', $owner_id],
		];
		$db->where($where);
		return $db->count();
	}

	public static function api_find_byId($gid) {
//	    $key = BaseRedis::key('group_info' , $gid);
//	    $res = cache($key);
//	    if (empty($res)) {
            $res = Db::table(self::$table)
                ->where([
                    ['id', '=', $gid],
                ])
                ->find();
//            cache($key , $res , cache_duration('l'));
//        }
        return $res;
	}

	public static function api_delete_byId($gid) {
	    self::api_clear_cache($gid);
		$db = Db::table(self::$table);
		$where = [
			['id', '=', $gid],
		];
		$db->where($where);
		return $db->delete();
	}

	public static function api_find_owner($owner_id) {
		$db = Db::table(self::$table);
		$where = [
			['owner_id', '=', $owner_id],
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_select_likeGroupName($group_name) {
		$db = Db::table(self::$table);
		$where = [
			['group_name', 'like', '%' . $group_name . '%'] ,
			['can_recommend', '=', 1] ,
		];
		$db->where($where);
		return $db->select();
	}

	// by cxl 更新字段：direct_join_group
	public static function api_direct_join_group_set($gid , $direct_join_group)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'direct_join_group' => $direct_join_group
            ]);
    }

    // by cxl 搜索符合条件的群组
    public static function api_search_byGroupName($uid , $group_name) {
	    return Db::query(
<<<EOT
            SELECT
                * 
            FROM
                cq_group_info AS gi 
            WHERE
                ( SELECT count( id ) FROM cq_group_member WHERE gid = gi.id AND uid = {$uid} ) > 0 
                AND group_name LIKE '%{$group_name}%';
EOT

        );
    }

    // by cxl 设置 can_disturb
    public static function api_can_disturb_set($gid , $can_disturb)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'can_disturb' => $can_disturb
            ]);
    }

    // by cxl 设置 can_recommend
    public static function api_can_recommend_set($gid , $can_recommend)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'can_recommend' => $can_recommend
            ]);
    }

    // by cxl 设置 announcement
    public static function api_announcement_set($gid , $announcement)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'announcement' => $announcement
            ]);
    }

    // by cxl 设置 group_name
    public static function api_group_name_set($gid , $group_name)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'group_name' => $group_name
            ]);
    }

    // by cxl 设置 introduction
    public static function api_introduction_set($gid , $introduction)
    {
        self::api_clear_cache($gid);
        return Db::table(self::$table)
            ->where('id' , $gid)
            ->update([
                'introduction' => $introduction
            ]);
    }

    // by cxl
    public static function api_clear_cache($gid)
    {
        $key = BaseRedis::key('group_info' , $gid);
        cache($key , null);
    }

    public static function api_update_by_cxl($id , array $data)
    {
        return Db::table(self::$table)->where('id' , $id)->update($data);
    }

    // by cxl 所有过期的取
    public static function expiredGroup()
    {
        $datetime = date('Y-m-d H:i:s' , time());
        $res = Db::table(self::$table)
            ->where('type' , 2)
            ->whereRaw(sprintf('date_format(expire , "%%Y-%%m-%%d %%H:%%i:%%s") < "%s"' , $datetime))
            ->select();
        return $res;
    }
}
