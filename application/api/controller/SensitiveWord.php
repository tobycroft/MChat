<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/9/10
 * Time: 10:00
 */

namespace app\api\controller;


use app\common\controller\LoginController;
use app\v1\action\SensitiveWordAction;

class SensitiveWord extends LoginController
{
    // todo
    // 列表
    public function list()
    {
        $param = $this->request->post();
        $param['id'] = $param['id'] ?? '';
        $param['order'] = $param['order'] ?? '';
        $param['limit'] = $param['limit'] ?? '';
        $res = SensitiveWordAction::list($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }


    // 新增
    public function add()
    {
        $param = $this->request->post();
        $param['id'] = $param['id'] ?? '';
        $param['str'] = $param['str'] ?? '';
        $res = SensitiveWordAction::add($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 删除
    public function del()
    {
        $param = $this->request->post();
        $param['id_list'] = $param['id_list'] ?? '';
        $res = SensitiveWordAction::del($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }

    // 编辑
    public function edit()
    {
        $param = $this->request->post();
        $param['id'] = $param['id'] ?? '';
        $res = SensitiveWordAction::del($param);
        if ($res['code'] != 0) {
            $this->fail($res['data'] , $res['code']);
        }
        $this->succ($res['data']);
    }
}