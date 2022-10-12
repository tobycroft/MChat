<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/7/31
 * Time: 12:14
 */

namespace app\api\controller;


use app\common\controller\CommonController;

use app\v1\model\PacketLogModel;
use app\v1\model\PacketRefundLogModel;
use app\v1\model\RedPacketModel;
use app\v1\model\TimerLogModel;
use app\v1\service\AuthService;
use app\v1\service\PayService;
use Exception;
use think\Db;

class Pack extends CommonController
{
    // 过期红包退款接口（这是一个定时任务接口）
    public function task_refund()
    {
        // 忽略掉客户端断开
        ignore_user_abort(true);
        $memory_limit = @ini_get('memory_limit');
        $max_execution_time = @ini_get('max_execution_time');
        // 解放 php 限制
        ini_set('memory_limit' , '1024M');
        // 最大执行时间设置为 1h
        ini_set('max_execution_time' , 0);
        try {
            $datetime = date('Y-m-d H:i:s' , time());
            $timer_log = sprintf('%s: 定时器执行红包过期退款...' , $datetime);
            $timer_log_id = TimerLogModel::api_insert($timer_log);
            // 获取所有过期红包且领取人数未达到数量的
            $sql = <<<EOT
            select * from cq_red_packet as rp 
            where 
                unix_timestamp(now()) > rp.end_time 
                and
                rp.timeout_refund = 0
                and
                rp.id > 0
                and 
                (
                  (select count(id) from cq_packet_log where pack_id = rp.id) != rp.max_number
                  or
                  (select sum(amount) from cq_packet_log where pack_id = rp.id) < rp.amount
                )
            order by rp.id desc;
EOT;
            $res = Db::query($sql , [
                // 如果有预处理参数，请在这里提供
            ]);
            foreach ($res as $v)
            {
                // 单个红包被领取人数
                $count = PacketLogModel::api_count_byPackId($v['id']);
                // 单个红包累计发出的金额
                $sum = PacketLogModel::api_sum_byPackId($v['id']);
                // 剩余未被领取的金额自动退回
                $balance = bcsub($v['amount'] , $sum , 8);
                // $uid, $cid, $amount, $order_id, $remark
                $log = sprintf('%s: 用户【%s】在 %s 发送的红包【%s】， 金额 %s，数量 %s，被领取 %s 个，剩余金额 %s 到期自动退还' , $datetime , $v['sender'] , $v['start_time'] , $v['id'] , $v['amount'] , $v['max_number'] , $count , $balance);
                $pay_res = PayService::user_refund($v['sender'] , $v['cid'] , $balance , $v['order_id'] , $log);
//                $pay_res = ['code' => 0 , 'data' => '成功'];
                if ($pay_res['code'] != 0) {
                    // 支付失败
                    PacketRefundLogModel::api_insert(sprintf('红包【%s】 在 %s 发起退款失败！远程接口提示的错误：code: %s ; data: %s' , $v['id'] , $datetime , $pay_res['code'] , $pay_res['data']));
                } else {
                    PacketRefundLogModel::api_insert($log);
                    // 目前先把他更新成 4
                    RedPacketModel::api_update_timeoutRefund($v['id'] , 1);
                }
            }
            $timer_log .= '执行成功';
            // 执行成功
            TimerLogModel::api_update($timer_log_id , [
                'log' => $timer_log
            ]);
        } catch(Exception $e) {
            // 恢复 php 配置
            ini_set('memory_limit' , $memory_limit);
            ini_set('max_execution_time' , $max_execution_time);
            throw $e;
        }
        // 恢复 php 配置
        ini_set('memory_limit' , $memory_limit);
        ini_set('max_execution_time' , $max_execution_time);
        $this->succ('操作成功');
    }
}