<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/6
 * Time: 18:05
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\RequestAction;

class Request extends LoginController
{
    // by cxl 验证消息
    public function api_list()
    {
        $res = RequestAction::app_list($this->uid);
        if ($res['code'] == 0) {
            $this->succ($res['data']);
        } else {
            $this->fail($res['data'] , $res['code']);
        }
    }

    // by cxl 未处理的请求数量
    public function count()
    {
        // 获取用户信息
        $res = RequestAction::unhandle_count($this->uid);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);

        }
        $this->succ($res['data']);
    }

    // 清除申请记记录
    public function delRequest()
    {
        $param = request()->post();
        $param['id_list'] = $param['id_list'] ?? '';
        $res = RequestAction::delRequest($this->uid , $param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);

        }
        $this->succ($res['data']);
    }

}