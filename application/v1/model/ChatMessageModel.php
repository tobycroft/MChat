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
 * Description of ChatMessageModel
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class ChatMessageModel {

	//put your code here

	protected static $table = 'cq_chat_message';

	public static function api_select_byChatId($chat_id , $uid) {
        $db = Db::table(self::$table);
        $where = [
            ['chat_id' , '=' , $chat_id] ,
            ['exclude_one' , '<>' , $uid] ,
            ['exclude_two' , '<>' , $uid] ,
        ];
        $db->where($where);
        $db->order('id desc');
        $db->limit(20);
        $res = $db->select();
		return $res;
	}

	public static function api_find_byMessageId($id) {
		$db = Db::table(self::$table);
		$where = [
			'id' => $id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_find($chat_id, $id) {
		$db = Db::table(self::$table);
		$where = [
			'id' => $id,
			'chat_id' => $chat_id,
		];
		$db->where($where);
		return $db->find();
	}

	public static function api_find_byChatId($chat_id , $uid) {
		$db = Db::table(self::$table);
        $where = [
            ['chat_id' , '=' , $chat_id] ,
            ['exclude_one' , '<>' , $uid] ,
            ['exclude_two' , '<>' , $uid] ,
        ];
		$db->where($where);
		$db->order('id desc');
		return $db->find();
	}

//	public static function api_insert($chat_id, $sender, $message, $type = 1, $extra = null,$flag=0,$expire=null) {
	public static function api_insert($chat_id, $sender, $message, $type = 1, $extra = null) {
	    // 清除缓存
        self::clearCache($chat_id);
		$db = Db::table(self::$table);
		$data = [
			'chat_id' => $chat_id,
			'sender' => $sender,
			'message' => $message,
			'extra' => $extra,
			'type' => $type,
//			'flag' => $flag,
//			'expire' => $expire,
			'date' => time(),
		];
//		$db->data($data);
		return $db->insertGetId($data);
	}

	public static function api_select_byLastid($chat_id, $last_id , $uid) {
		$db = Db::table(self::$table);
        $where = [
            ['chat_id' , '=' , $chat_id] ,
            ['exclude_one' , '<>' , $uid] ,
            ['exclude_two' , '<>' , $uid] ,
        ];
		$db->where($where)->where('id', '>', $last_id);
//		$db->order('id asc');
		return $db->select();
	}

	// by cxl 更新消息记录屏蔽的用户
    public static function api_exclude_user_set($msg_id , $uid)
    {
        $chat_id = Db::table(self::$table)
            ->where('id' , $msg_id)
            ->value('chat_id');
        $chat_user = explode('_' , $chat_id);
        array_walk($chat_user , function(&$v){
            $v = intval($v);
        });
        $max_id = max($chat_user);
        if ($uid < $max_id) {
            $column = 'exclude_one';
        } else {
            $column = 'exclude_two';
        }
        return Db::table(self::$table)
            ->where('id' , $msg_id)
            ->update([
                $column => $uid
            ]);
    }

    // by cxl 删除消息
    public static function api_del_byMessageId($id)
    {
        return Db::table(self::$table)->where('id' , $id)->delete();
    }

    // by cxl 搜索私聊消息
    public static function api_search_byMessage($uid , $message = '')
    {
        return Db::query(
<<<EOT
                SELECT
                    cm.*,
                    ui.uname ,
                    ui.face
                FROM
                    cq_chat_message AS cm
                    inner JOIN cq_single_chat AS sc ON cm.chat_id = sc.chat_id
                    inner JOIN cq_user_info AS ui ON sc.fid = ui.uid 
                WHERE
                    sc.uid = {$uid}
                    and cm.exclude_one <> {$uid}
                    and cm.exclude_two <> {$uid}
                    AND cm.id = ( SELECT id FROM cq_chat_message WHERE message LIKE '%{$message}%' AND chat_id = cm.chat_id ORDER BY date DESC LIMIT 1 );
EOT
        );
    }

    // by cxl 搜索私聊消息（数量）
    public static function api_count_byMessage($uid , $chat_id , $message = '')
    {
        return Db::table(self::$table)
            ->where([
                ['chat_id' , '=' , $chat_id] ,
                ['message' , 'like' , "%{$message}%"] ,
                ['exclude_one' , '<>' , $uid] ,
                ['exclude_two' , '<>' , $uid]
            ])
            ->count('id');
    }

    // by cxl 类型设置
    public static function api_type_set($msg_id , $type)
    {
        return Db::table(self::$table)
            ->where('id' , $msg_id)
            ->update([
                'type' => $type
            ]);
    }

    // by cxl
    public static function api_updateById($id , array $param = [])
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->update($param);
    }

    // by cxl
    public static function api_insert_overall(array $param = [])
    {
        return Db::table(self::$table)->insertGetId($param);
    }

    // 最新一条消息
    public static function api_last_msg($chat_id)
    {
        return Db::table(self::$table)
            ->where('chat_id' , $chat_id)
            ->order('date' , 'desc')
            ->order('id' , 'desc')
            ->limit(1)
            ->find();
    }

    // by cxl 更早的消息
    public static function api_earlier_msg($chat_id , $msg_id , $limit = 20)
    {
        $res = Db::table(self::$table)
            ->where([
                ['chat_id' , '=' , $chat_id] ,
                ['id' , '<' , $msg_id]
            ])
            ->limit($limit)
            ->order('id' , 'desc')
            ->select();
        return $res;
    }

    // 清除缓存
    public static function clearCache($chat_id)
    {
        $key = BaseRedis::key('chat_message' , $chat_id);
        cache($key , null);
    }
}
