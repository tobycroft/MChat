<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/15
 * Time: 20:15
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\AnnouncementAction;

class Announcement extends LoginController
{
    // 公告列表
    public function api_list()
    {
        $pos = 'app';
        $res = AnnouncementAction::app_list($pos);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }

    // 公告详情
    public function detail()
    {
        $id = input('post.id');
        $res = AnnouncementAction::app_detail($id);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        }
        $this->fail($res['data'] , $res['code']);
    }
}