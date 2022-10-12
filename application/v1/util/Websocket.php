<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/5/5
 * Time: 14:20
 */

namespace app\v1\util;

// websocket 图送
use Net;

class Websocket
{
    // websocket key
    protected static $websocketKey = 'gn8knscieinuincsiuni4ur0wherur';

    public static function url()
    {
        return config('app.websocket_url');
    }

    // 发送消息
    public static function push(array $uids , $data , $dest , $type)
    {
        $res = Net::post_data(self::url() , [
            'key' => self::$websocketKey,
            'uids' => json_encode($uids),
            'data' => json_encode($data),
            'dest' => $dest,
            'type' => $type ,
        ],3);
        return json_decode($res, true);
    }

    // 发送私聊消息推送
    public static function send(array $uids , $data , $chat_id)
    {
        return self::push($uids , $data , $chat_id , 'private_chat');
    }

    // 发送群聊消息推送
    public static function groupSend(array $uids , $data , $gid)
    {
        return self::push($uids , $data , $gid , 'group_chat');
    }

    // 刷新会话列表
    public static function refresh(array $uids , $chat_id)
    {
        return self::push($uids , ['name' => 'ceshi'] , $chat_id , 'refresh_list');
    }

    // 刷新会话列表
    public static function groupRefresh(array $uids , $gid)
    {
        return self::push($uids , ['name' => 'ceshi'] , $gid , 'refresh_list');
    }

    // 申请记录推送
    public static function requestCount(array $uids , $count)
    {
        return self::push($uids , [
            'count' => $count
        ] , 123 , 'request_count');
    }

}