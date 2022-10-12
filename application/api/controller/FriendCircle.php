<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 10:09
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\FriendCircleAction;


class FriendCircle extends LoginController
{
    // 发朋友圈
    public function publish()
    {
        $param = request()->post();
        $res = FriendCircleAction::publish($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 保存图片
    public function saveImage()
    {
        $image = isset($_FILES['image']) ? $_FILES['image'] : [];
        $res = FriendCircleAction::saveImage($image);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 更改背景图片
//    public function saveBackground()
//    {
//        $image = isset($_FILES['image']) ? $_FILES['image'] : [];
//        $res = FriendCircleAction::saveBackground($this->uid , $image);
//        if ($res['code'] != 0) {
//            $this->fail($res['data'] , $res['code']);
//        }
//        $this->succ($res['data']);
//    }

    public function saveBackground()
    {
        $image = request()->post('image') ?? '';
        $res = FriendCircleAction::saveBackground($this->uid , $image);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 发布评论
    public function comment()
    {
        $param = request()->post();
        $res = FriendCircleAction::comment($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 删除评论
    public function delComment()
    {
        $param = request()->post();
        $res = FriendCircleAction::delComment($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 朋友圈点赞
    public function commendation()
    {
        $param = request()->post();
        $res = FriendCircleAction::commendation($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 获取朋友圈详情
    public function detail()
    {
        $param = request()->post();
        $res = FriendCircleAction::detail($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 删除朋友圈
    public function delete()
    {
        $param = request()->post();
        $res = FriendCircleAction::delete($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 设置好友朋友圈权限
    public function friendPriv()
    {
        $param = request()->post();
        $res = FriendCircleAction::friendPriv($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 朋友圈个人信息
    public function info()
    {
        $param = request()->post();
        $param['user_id'] = $param['user_id'] ?? '';
        $res = FriendCircleAction::info($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 朋友圈列表
    public function friendCircle()
    {
        $res = FriendCircleAction::friendCircle($this->uid);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }


    // 个人发布的朋友圈列表
    public function history()
    {
        $param = request()->post();
        $res = FriendCircleAction::history($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // todo 保存视频

    // 朋友圈未读消息数量
    public function friendCircleUnread()
    {
        $res = FriendCircleAction::friendCircleUnread($this->uid);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 设置朋友圈未读消息数量
    public function setCountForFriendCircle()
    {
        $res = FriendCircleAction::setCountForFriendCircle($this->uid);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 设置朋友圈未读消息数量
    public function setCountForComment()
    {
        $res = FriendCircleAction::setCountForComment($this->uid);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

}