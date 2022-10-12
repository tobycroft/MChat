<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;

use app\common\controller\LoginController;
use app\v1\action\GroupAction;

/**
 * Description of Group
 *
 * @author Smmy Guergachi <sguergachi at gmail.com>
 */
class Group extends LoginController {

	public function create_group() {
		$group_name = input('post.group_name');
		$introduction = input('post.introduction');
		$type = input('post.type');
		$expire = input('post.expire');
//		var_dump(0)
		$ret = GroupAction::app_create_group($this->uid, $group_name, $introduction , null , $type , $expire);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function group_list() {
		$ret = GroupAction::app_group_list($this->uid);
		if ($ret['code'] == 0) {
			$this->succ($ret['data']);
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function group_search() {
		$group_name = input('post.group_name');
		$ret = GroupAction::app_search_group($group_name);
		$this->succ($ret['data']);
	}

	public function group_info() {
		$gid = input('post.gid');
		$ret = GroupAction::app_group_info($gid , $this->uid);
		if ($ret) {
			$this->succ($ret);
		} else {
			$this->fail('没有这个群');
		}
	}

	public function group_member() {
		$gid = input('post.gid');
		$ret = GroupAction::app_group_member($gid);
		$this->succ($ret['data']);
	}

	public function join_group() {
		$gid = input('post.gid');
        $comment = input('post.comment');
		$ret = GroupAction::app_send_group_request($this->uid, $gid , $comment);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function invite_group() {
		$invited_uids = input('post.invited_uids');
		$invited_uid = input('post.invited_uid');
		$gid = input('post.gid');
		if ($invited_uid) {
			$ret = GroupAction::app_invite_to_group($this->uid, $gid, $invited_uid);
		} else {
			$ret = GroupAction::app_invites_to_group($this->uid, $gid, $invited_uids);
		}

		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function exit_grouop() {
		$gid = input('post.gid');
		$ret = GroupAction::app_exit_group($this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}
    // by cxl 纠正 exit_grouop 接口名称写错
    public function exit_group() {
        $gid = input('post.gid');
        $ret = GroupAction::app_exit_group($this->uid, $gid);
        if ($ret['code'] == 0) {
            $this->succ();
        } else {
            $this->fail($ret['data'], $ret['code']);
        }
    }

	// 删除群（解散群）
	public function vanish_group() {
		$gid = input('post.gid');
		$ret = GroupAction::app_vanish_group($this->uid, $gid);
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	// 单个 群主踢人
	public function group_kick() {
		$gid = input('post.gid');
		$kick_uid = input('post.kick_uid');
		// by cxl 批量删除
        $kick_uids = input('post.kick_uids');
        if ($kick_uid) {
            $ret = GroupAction::app_kick_user($this->uid, $gid, $kick_uid);
        } else {
            $ret = groupAction::app_kick_users($this->uid , $gid , $kick_uids);
        }
		if ($ret['code'] == 0) {
			$this->succ();
		} else {
			$this->fail($ret['data'], $ret['code']);
		}
	}

	public function group_ban() {
//		$gid = input('post.gid');
//		$ban_uid = input('post.ban_uid');
//		$ret = GroupAction::app_ban_user($this->uid, $gid, $ban_uid);
//		if ($ret['code'] == 0) {
//			$this->succ();
//		} else {
//			$this->fail($ret['data'], $ret['code']);
//		}
	}

	// by cxl 群会话置顶
    public function group_top_set()
    {
        $gid = input('post.gid');
        $is_top = input('post.is_top');
        $res = GroupAction::app_group_top_set($this->uid , $gid , $is_top);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 加群验证
    public function group_verify()
    {
        $direct_join_group = input('post.direct_join_group');
        $gid = input('post.gid');
        $res = GroupAction::app_direct_join_group_set($this->uid , $gid , $direct_join_group);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->succ($res['data'] , $res['code']);
        }
    }

    // by cxl 群主决定加群验证
    public function decide_group_invite_app()
    {
        $request_list_id = input('post.request_list_id');
        $decision = input('post.decision');
        $res = GroupAction::app_decide_group_invite_app($request_list_id , $decision);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 加群二维码
    public function qrcode_data()
    {
        $gid = input('post.gid');
        $res = GroupAction::app_group_arcode_data($gid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 群消息免打扰
    public function can_disturb_set()
    {
        $gid = input('post.gid');
        $can_disturb = input('post.can_disturb');
        $res = GroupAction::app_can_disturb_set($this->uid , $gid , $can_disturb);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl group_name
    public function group_name_set()
    {
        $gid = input('post.gid');
        $can_disturb = input('post.group_name');
        $res = GroupAction::app_group_name_set($this->uid ,$gid , $can_disturb);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl announcement
    public function announcement_set()
    {
        $gid = input('post.gid');
        $announcement = input('post.announcement');
        $res = GroupAction::app_announcement_set($this->uid , $gid , $announcement);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl introduction
    public function introduction_set()
    {
        $gid = input('post.gid');
        $introduction = input('post.introduction');
        $res = GroupAction::app_introduction_set($this->uid , $gid , $introduction);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 是否能够被推荐
    public function can_recommend_set()
    {
        $gid = input('post.gid');
        $can_recommend = input('post.can_recommend');
        $res = GroupAction::app_can_recommend_set($this->uid , $gid , $can_recommend);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 是否在给定群中
    public function in_group()
    {
        $member_id = input('post.member_id');
        $gid = input('post.gid');
        $res = (int) GroupAction::app_in_group($gid , $member_id);
        $this->succ($res);
    }

    // by cxl 修改群头像
    public function updateImage()
    {
        $param = request()->post();
        $param['gid'] = $param['gid'] ?? '';
        $param['img'] = $param['img'] ?? '';
        $res = GroupAction::app_updateImage($this->uid , $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 修改群头像
    public function isGroupMaster()
    {
        $param = request()->post();
        $param['gid'] = $param['gid'] ?? '';
        $param['member_id'] = $param['member_id'] ?? '';
        $res = GroupAction::isGroupMaster($this->uid , $param);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }
}
