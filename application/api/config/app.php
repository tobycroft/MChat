<?php

/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/5
 * Time: 16:14
 */
return [
//    'image_host' => 'http://api.lechat.vip/upload/' ,
    'image_host' => '' ,
    // 必须以 / 结尾
    'host' => 'http://api.uda-bank.vip:89' ,
    'admin_host' => 'http://adm.uda-bank.vip:89' ,

//    'aliyun_host' => 'http://lexin.oss-ap-southeast-1.aliyuncs.com/' ,
    'aliyun_host' => 'http://combi.oss-ap-southeast-1.aliyuncs.com' ,

    // 默认：用户头像
    'avatar' => 'http://chat.ddpor.vip:134/logo/logo.png' ,
    'group_avatar' => 'http://chat.ddpor.vip:134/logo/logo.png' ,

    // 远程接口地址
    'remote_api_url' => 'http://api.ddpor.vip:133' ,
    // websocket 地址
//    'websocket_url' => 'http://172.21.81.109:99' ,
    'websocket_url' => 'http://172.21.81.109:140' ,

    // 默认：用户名
    'username' => '未设定昵称' ,
    // 默认：群名称
    'group_name' => '未设定群名称' ,
    // 默认：api 接口域名
    'api' => 'http://chat.t1.tuuz.cc/' ,
    // app 下载链接，请务必以 / 结尾！！
    'download' => 'http://www.baidu.com/' ,
    // 计算结果保存的小数位数
    'len' => 4 ,
    // 群红包最大 50 个
    'max_person_count' => 50 ,
    // 没有选择币种的时候的默认币种
    'coin_type' => 'NBY' ,
    // 红包的默认名称
    'red_packet_name' => '恭喜发财' ,
    // 默认用户签名
    'sign' => '写下个性签名，让更多的人认识你！' ,
    // redis 保存的键值时常
    'duration' => 365 * 24 * 3600  ,
    // 数据分页
    'limit' => 12 ,
    // 应用相关密钥
    'secret' => [
        // 聚合数据 app_key
        'news' => [
            'key' => 'f455253376508bc628217cead552c7cc' ,
            'url' => 'http://v.juhe.cn/toutiao/index' ,
        ]
    ] ,
    'upload_dir' => realpath(__DIR__ . '/../../../public/upload/') ,
    // 图片保存路径
    'image_dir' => realpath(__DIR__ . '/../../../public/upload/image/') ,
    // 缩略图
    'thumb_dir' => realpath(__DIR__ . '/../../../public/upload/thumb/') ,
    // 视频保存路径
    'video_dir' => realpath(__DIR__ . '/../../../public/upload/video/') ,
    // 文件保存路径
    'file_dir' => realpath(__DIR__ . '/../../../public/upload/file/') ,
    // 网站根目录
    'web_dir' => realpath(__DIR__ . '/../../../public/') ,
    // 朋友圈默认背景图片
    'background_image_for_friend_circle' => '' ,
    // 消息撤回时间限制
    'min_for_retract' => 2 ,
];