<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/6
 * Time: 10:20
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\SearchAction;

class Search extends LoginController
{
    // 全方位搜索：好友 + 群组 + 聊天记录（私聊记录/群聊记录）
    public function full_search()
    {
        $text = input('post.text');
        $res = SearchAction::app_full_search($this->uid , $text);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }
}