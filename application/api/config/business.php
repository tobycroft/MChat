<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/4
 * Time: 10:28
 */

return [
    'msg_type' => [
        1 => '文字' ,
        2 => '图片' ,
        3 => '语音' ,

        4 => '语音通话-失败' ,
        5 => '语音通话-成功' ,

        6 => '语音通话-发起方挂断' ,
        7 => '语音通话-接收方挂断' ,

//        8 => '链接地址' ,

        /**
         * **********
         * 群-红包相关
         * **********
         */
        10 => '普通红包' ,
        11 => '口令红包' ,
        12 => '拼手气红包' ,

        // 消息
        30 => '消息撤回' ,
        31 => '群公告' ,
        32 => '黑名单消息（被屏蔽的消息）' ,

        // 个人-红包
        40 => '普通红包' ,
        41 => '口令红包' ,

        // 视频通话
        50 => '视频通话-失败' ,
        51 => '视频通话-成功' ,

        // 聊天限制
    ] ,
    // 红包类型
    'packet_type' => [
        // 个人红包
        40 => '普通红包' ,
        41 => '口令红包' ,

        // 群红包
        10 => '普通红包' ,
        11 => '口令红包' ,
        12 => '拼手气红包' ,
    ] ,
    'push' => [
        // 加群验证
        'group_invite' => [
            'module' => 'system' ,
            'type' => 'group_verify' ,
            'data' => [
                'request_list_id' => 1
            ]
        ] ,
    ] ,
    // 朋友圈开放程度
    'open_level' => [
        // 公开
        'public' => '公开' ,
        // 仅自己可见
        'self' => '仅自己可见' ,
        // 指定用户可见
        'assign' => '指定用户可见' ,
    ] ,

    // 朋友圈-好友权限
    'friend_privilege' => [
        // 好友权限：hidden-隐身，不让他看；shield-屏蔽，不看他；part-部分可看（具体方式程序控制）；none-不做限定，可看全部
        'hidden' => '不让他看我' ,
        'shield' => '不看他' ,
    ] ,
    // 红包类型
    'packet_type_range' => [10 , 11 , 12 , 40 , 41] ,
    // 验证消息类型
    'msg_auth_type' => [
        'add'       => '申请成为好友' ,
        'invite'    => '邀请进群' ,
        'kick'      => '被提出群通知' ,
        'refuse'    => '拒绝请求' ,
        'approve'   => '同意请求' ,
    ] ,

    // 支持的转发消息：1-文字|2-图片|3-语音
    'forward_type' => [1,2,3] ,
    // 语音消息类型
    'voice_type' => [4,5] ,
    //
    'video_type' => [50,51] ,
    // 媒体类型
    'media_type' => [4,5,50,51] ,
    // 缓存过期时间：长
    'cache_l' => 24 * 60 * 60 ,
    // 缓存过期时间：中
    'cache_m' => 30 * 60 ,
    // 缓存过期时间：短
    'cache_s' => 2 * 60 ,
    // 缓存时常
    'cache_month' => 30 * 24 * 3600 ,

    // 游戏类型
    // 游戏类型: 1-真人 2-棋牌 3-捕鱼 4-电竞 5-电子
    'game_type' => [
        // AG视讯
        1 => 'AG' ,
        // EBET 视讯
        2 => 'EBET' ,
        // 开元棋牌
        3 => 'KY' ,
        // 双赢棋牌
        4 => 'SW' ,
        // 皇冠体育
        5 => 'GJ' ,
    ] ,

];