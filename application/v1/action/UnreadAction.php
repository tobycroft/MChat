<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\v1\action;

/**
 * Description of UnreadAction
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class UnreadAction {

	public static function add_unread($target_id, $senfer_id) {
		$cache = cache('_unread_' . $target_id . '_' . $senfer_id);
		if ($cache) {
			return cache('_unread_' . $target_id . '_' . $senfer_id, ($cache + 1), 86400 * 30);
		} else {
			return cache('_unread_' . $target_id . '_' . $senfer_id, 1, 86400 * 30);
		}
	}

	public static function set_last_id($target_id, $senfer_id, $last_id) {
		return cache('_lastid_' . $target_id . '_' . $senfer_id, $last_id, 86400 * 30);
	}

	public static function clear_last_id($target_id, $senfer_id) {
		return cache('_lastid_' . $target_id . '_' . $senfer_id, null, 1);
	}

	public static function get_last_id($self_id, $target_id) {
		return cache('_lastid_' . $self_id . '_' . $target_id);
	}

	public static function clear_unread($self_id, $target_id) {
		return cache('_unread_' . $self_id . '_' . $target_id, 0, 1);
	}

	public static function get_unread_count($self_id, $target_id) {
		return cache('_unread_' . $self_id . '_' . $target_id) ?: 0;
	}

	public static function group_add_unread($gid) {
		$cache = cache('_gunread_' . $gid);
		if ($cache) {
			return cache('_gunread_' . $gid, ($cache + 1), 86400 * 30);
		} else {
			return cache('_gunread_' . $gid, 1, 86400 * 30);
		}
	}

	public static function group_set_msg_time($gid) {
		return cache('_gmsgtime_' . $gid, time(), 86400 * 30);
	}

	public static function group_get_msg_time($gid) {
		return cache('_gmsgtime_' . $gid);
	}

	public static function group_msg_id($gid) {
		$cache = cache('_gunread_' . $gid);
		if ($cache) {
			return $cache;
		} else {
			return 0;
		}
	}

	public static function group_get_msgid($gid) {
		return self::group_msg_id($gid);
	}

	public static function group_set_lastid($uid, $gid, $last_id) {
		return cache('_glastid_' . $gid . '_' . $uid, $last_id, 86400 * 365);
	}

	public static function group_get_lastid($uid, $gid) {
		return cache('_glastid_' . $gid . '_' . $uid) ?: 0;
	}

}
