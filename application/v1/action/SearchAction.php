<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/6
 * Time: 10:23
 */

namespace app\v1\action;

use app\v1\model\SingleFriendModel;
use app\v1\model\GroupInfoModel;
use app\v1\model\ChatMessageModel;
use app\v1\model\ChatGmessageModel;

class SearchAction
{
    // 全方位搜索：好友 + 群组 + 聊天记录（私聊记录/群聊记录）
    public static function app_full_search($uid , $text = '')
    {
        // 搜索好友
        $friend = SingleFriendModel::api_search_byUname($uid , $text);
        foreach ($friend as &$v)
        {
            $v['uname'] = empty($v['uname']) ? config('app.username') : $v['uname'];
            $v['face'] = empty($v['face']) ? config('app.avatar') : $v['face'];
        }
        // 搜索群
        $group = GroupInfoModel::api_search_byGroupName($uid , $text);
        foreach ($group as &$v)
        {
            $v['group_name'] = empty($v['group_name']) ? config('app.group_name') : $v['group_name'];
            $v['img'] = empty($v['img']) ? config('app.avatar') : $v['img'];
            // 群成员数量
            $v['member_count'] = GroupAction::app_group_member_count($v['id']);
        }
        // 搜索私聊聊天记录
        $friend_msg = ChatMessageModel::api_search_byMessage($uid , $text);
        foreach ($friend_msg as &$v)
        {
            // 私聊类型
            $v['type'] = 'private';
            $v['count'] = ChatMessageModel::api_count_byMessage($uid , $v['chat_id'] , $text);
        }
        // 搜索群聊聊天记录
        $group_msg = ChatGmessageModel::api_search_byMessage($uid , $text);
        foreach ($group_msg as &$v)
        {
            // 群聊
            $v['type'] = 'group';
            $v['count'] = ChatGmessageModel::api_count_byMessage($uid , $text);
        }
        // 合并私聊 + 群聊
        $his = array_merge($friend_msg , $group_msg);
        usort($his , function($a , $b){
            return $a['date'] >= $b['date'] ? 1 : -1;
        });
        return [
            'code' => 0 ,
            'data' => [
                'friend'    => $friend ,
                'group'     => $group ,
                'history'   => $his
            ]
        ];
    }
}