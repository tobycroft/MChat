<?php

namespace app\v1\model;

use app\v1\redis\BaseRedis;
use think\Db;

class ChatGmessageModel {

	public static $table = 'cq_chat_gmessage';

//	public static function api_insert($gid, $uid, $message, $type = 1, $extra = null,$flag=0,$expire=null) {
	public static function api_insert($gid, $uid, $message, $type = 1, $extra = null) {
		$db = \think\Db::table(self::$table);
		$data = [
			'gid' => $gid,
			'uid' => $uid,
			'message' => $message,
			'extra' => $extra,
			'type' => $type,
//            'flag' => $flag,
//            'expire' => $expire,
			'date' => time(),
		];
		return $db->insertGetId($data);
	}

    // 清除缓存
    public static function clearCache($gid)
    {
        $key = BaseRedis::key('chat_gmessage' , $gid);
        cache($key , null);
    }

	public static function api_select_byGid($gid, $uid, $limit = 25) {
        $sql = 'select * from cq_chat_gmessage as cg where cg.gid = ' . $gid;
        $sql .= ' and (select count(id) from cq_group_message_exclude_user where msg_id = cg.id and uid = ' . $uid . ') = 0';
        $sql .= ' order by cg.id desc';
        $sql .= ' limit ' . $limit;
        $res = Db::query($sql);
		return $res;
	}

	public static function api_find_byMessageId($id) {
		$db = \think\Db::table(self::$table);
		$where = [
			'id' => $id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_select_byGidandUid($gid, $uid, $limit = 25) {
		$db = \think\Db::table(self::$table);
		$where = [
			['gid', '=', $gid],
			['uid', '=', $uid],
		];
		$db->where($where);
		$db->order('id desc');
		$db->limit($limit);
		return $db->select();
	}

    public static function api_find_byGidAndUid($gid , $uid)
    {
        $sql = <<<EOT
            select * from  cq_chat_gmessage as cg where gid = :gid and 
            (select count(id) from cq_group_message_exclude_user where msg_id = cg.id and uid = :uid) = 0 
            order by id desc 
            limit 1;
EOT;
        $res = Db::query($sql , [
            'gid' => $gid ,
            'uid' => $uid
        ]);
        if (empty($res)) {
            return ;
        }
        return $res[0];
    }

	public static function api_find_byGid($gid) {
		$db = \think\Db::table(self::$table);
		$where = [
			['gid', '=', $gid],
		];
		$db->where($where);
		$db->order('id desc');
		return $db->find();
	}

	public static function api_select_byLastid($gid, $last_num) {
		$db = \think\Db::table(self::$table);
		$where = [
			['gid', '=', $gid],
		];
		$db->where($where);
		$db->order('id desc');
		$db->limit($last_num);
		return $db->select();
	}

	public static function api_find_byChatid($gid, $id) {
		$db = \think\Db::table(self::$table);
		$where = [
			['gid', '=', $gid],
			['id', '=', $id],
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_del_byMessageId($id) {
		return Db::table(self::$table)->where('id', $id)->delete();
	}

	// by cxl 搜索群组消息
	public static function api_search_byMessage($uid, $message) {
		return Db::query(
						<<<EOT
            SELECT
                cgm.*,
                gi.group_name,
                gi.img,
                gi.introduction
            FROM
                (
                    SELECT
                        *
                    FROM
                        cq_group_info AS gi
                    WHERE
                        ( SELECT count( id ) FROM cq_group_member WHERE gid = gi.id AND uid = {$uid} ) > 0
                ) AS gi
                inner JOIN cq_chat_gmessage AS cgm ON gi.id = cgm.gid
            WHERE
                cgm.id = ( SELECT id FROM cq_chat_gmessage WHERE gid = cgm.gid AND message LIKE '%{$message}%' ORDER BY date DESC LIMIT 1 )
                and
                (select count(id) from cq_group_message_exclude_user where uid = {$uid} and msg_id = cgm.id) = 0
EOT
		);
	}

	// by cxl 群组消息数量
	public static function api_count_byMessage($uid, $message) {
		$res = Db::query(
						<<<EOT
            SELECT
              count(cgm.id) as `count`
            FROM
                (
                    SELECT
                        id
                    FROM
                        cq_group_info AS gi
                    WHERE
                        ( SELECT count( id ) FROM cq_group_member WHERE gid = gi.id AND uid = {$uid} ) > 0
                ) AS gi
                inner JOIN cq_chat_gmessage AS cgm ON gi.id = cgm.gid
            WHERE
                cgm.message LIKE '%{$message}%'
                and
                (select count(id) from cq_group_message_exclude_user where uid = {$uid} and msg_id = cgm.id) = 0
EOT
		);
		return $res[0]['count'];
	}

    // by cxl
    public static function api_insert_overall(array $param = [])
    {
        return Db::table(self::$table)->insertGetId($param);
    }

    // by cxl
    public static function api_updateById($id , array $param = [])
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->update($param);
    }

    public static function api_last_msg($gid)
    {
        return Db::table(self::$table)
            ->where('gid' , $gid)
            ->order('date' , 'desc')
            ->order('id' , 'desc')
            ->limit(1)
            ->find();
    }

    public static function api_earlier_msg($gid , $msg_id , $limit = 20)
    {
        $res = Db::table(self::$table)
            ->where([
                ['gid' , '=' , $gid] ,
                ['id' , '<' , $msg_id]
            ])
            ->limit($limit)
            ->order('id' , 'desc')
            ->select();
        return $res;
    }
}
