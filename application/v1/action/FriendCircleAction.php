<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:30
 */

namespace app\v1\action;

use app\api\controller\FriendCircle;
use app\v1\model\FriendCircleCommendationLogModel;
use app\v1\model\FriendCircleCommentModel;
use app\v1\model\FriendCircleInfoModel;
use app\v1\model\FriendCircleMediaModel;
use app\v1\model\FriendCircleModel;
use app\v1\model\FriendCircleUnreadModel;
use app\v1\model\FriendCircleVisibleModel;
use app\v1\model\FriendPrivilegeModel;
use app\v1\model\SingleFriendModel;
use app\v1\plugin\AliyunOss\AliyunOss;
use app\v1\util\File as FileUtil;
use app\v1\util\Image;
use app\v1\util\Page;
use Exception;
use Net;
use think\Validate;
use think\Db;
use Core\Lib\File;

/**
 * *****************************************************************************
 * 注意前缀
 * 1. 如果带有 util_ 前缀，那么表示未工具方法，直接返回目前值
 * 2. 如果未带，表示控制器对应的逻辑处理方法，固定返回带 code/data 单元的数组
 * *****************************************************************************
 */

class FriendCircleAction
{
    // 发朋友圈
    public static function publish($user_id , $param = [])
    {
        $validator = Validate::make([
            'content' => 'require' ,
            'media' => 'require' ,
            'open_level' => 'require' ,
        ] , [
            // 内容
            'content.require' => '内容尚未提供' ,
            'media.require' => '图片尚未提供' ,
            'open_level.require' => '开放程度尚未提供' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['open_level'] = $param['open_level'] ?? 'public';
        $param['media']     = $param['media'] ?? '';
        $param['content']   = $param['content'] ?? '';
        $param['group']      = $param['group'] ?? '';
        $open_level_range = array_keys(config('business.open_level'));
        if (!in_array($param['open_level'] , $open_level_range)) {
            // 开放程度
            return [
                'code' => 400 ,
                'data' => '不支持的开放程度'
            ];
        }
        if ($param['open_level'] == 'assign') {
            // 指定用户可看
            if (empty($param['group'])) {
                return [
                    'code' => 400 ,
                    'data' => '您尚未指定允许查看的分组'
                ];
            }
            $param['group'] = json_decode($param['group'] , true);
        }
//        if (empty($param['media'])) {
//            // 检查媒体
//            return [
//                'code' => 400 ,
//                'data' => '媒体尚未提供'
//            ];
//        }
        // 检查是否能够发布朋友圈
        $info = FriendCircleInfoModel::find($user_id);
        if (!empty($info) && $info['can_publish'] != 'y') {
            // 禁止发布朋友圈
            return [
                'code' => 403 ,
                'data' => '你已经被禁止发布朋友圈' ,
            ];
        }
        $param['media'] = json_decode($param['media'] , true);
        $param['user_id'] = $user_id;
        try {
            Db::startTrans();
            // 发布朋友圈
            $friend_circle_id = FriendCircleModel::insertGetId($param);
            if ($param['open_level'] == 'assign') {
                // 朋友圈指定好友
                foreach ($param['group'] as $v)
                {
                    FriendCircleVisibleModel::insertGetId([
                        'friend_circle_id'          => $friend_circle_id ,
                        'friend_circle_group_id'    => $v
                    ]);
                }
            }
            // 更新朋友圈媒体所属朋友圈
//            FriendCircleMediaModel::set_friend_circle_id($param['media'] , [
//                'friend_circle_id' => $friend_circle_id
//            ]);
//            var_dump($friend_circle_id);
            foreach ($param['media'] as $v)
            {
                FriendCircleMediaModel::insertGetId([
                    'friend_circle_id' => $friend_circle_id ,
                    'path' => $v ,
                    'url' => $v ,
                    'thumb' => sprintf('%s?%s' , $v , 'x-oss-process=image/resize,w_600') ,
                ]);
            }
            // 更新好友的朋友圈未读消息数量
            $friend = SingleFriendModel::api_select_byUid($user_id);
            foreach ($friend as $v)
            {
                if (FriendCircleModel::can_inc_unread_count($friend_circle_id , $v['uid'] , $v['fid'])) {
                    // 更新该好友的数量
                    if (!FriendCircleUnreadModel::exists($v['fid'])) {
                        FriendCircleUnreadModel::insert($v['fid']);
                    }
                    FriendCircleUnreadModel::countForFriendCircle($v['fid']);
                }
            }
            Db::commit();
            return [
                'code' => 0 ,
                'data' => ''
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    // 保存用户图片
    public static function saveImage($image)
    {
        $oss = Net::upload_file('file' , $image['tmp_name'] , $image['type'] , $image['name']);
        if ($oss['code'] != 0) {
            return [
                'code' => $oss['code'] ,
                'data' => $oss['data'] ,
            ];
        }
        $res = [
            'path' => $oss['data'] ,
            'url' => $oss['data'] ,
        ];
        $res['thumb'] = $oss['data'] . '?x-oss-process=image/resize,w_600';
        // 保存到数据库
        $id = FriendCircleMediaModel::insertGetId(array_merge([
            'type' => 'image'
        ] , $res));
        return [
            'code' => 0 ,
            'data' => $id
        ];
    }

    // 更换背景图片
//    public static function saveBackground($user_id , $image)
//    {
//        $oss = Net::upload_file('file' , $image['tmp_name'] , $image['type'] , $image['name']);
//        if ($oss['code'] != 0) {
//            return [
//                'code' => $oss['code'] ,
//                'data' => $oss['data'] ,
//            ];
//        }
//        FriendCircleInfoModel::update($user_id , [
//            'background_image' => $oss['data']
//        ]);
//        return [
//            'code' => 0 ,
//            'data' => ''
//        ];
//    }

    public static function saveBackground($user_id , $image)
    {
//        $oss = Net::upload_file('file' , $image['tmp_name'] , $image['type'] , $image['name']);
//        if ($oss['code'] != 0) {
//            return [
//                'code' => $oss['code'] ,
//                'data' => $oss['data'] ,
//            ];
//        }
        if (empty($image)) {
            return [
                'code' => 400 ,
                'data' => '请提供图片' ,
            ];
        }
        FriendCircleInfoModel::update($user_id , [
            'background_image' => $image
        ]);
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

    // 发布评论
    public static function comment($user_id , $param = [])
    {
        $validator = Validate::make([
            'friend_circle_id' => 'require' ,
            'content' => 'require' ,
        ] , [
            'friend_circle_id.require' => 'friend_circle_id 尚未提供' ,
            'content.require' => 'content 尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        // 检查是否能够发布朋友圈
        $info = FriendCircleInfoModel::find($user_id);
        if (!empty($info) && $info['can_comment'] != 'y') {
            // 禁止发布朋友圈
            return [
                'code' => 403 ,
                'data' => '你已经被禁止发布评论' ,
            ];
        }
        $param['friend_circle_id']  = $param['friend_circle_id'] ?? 0;
        $param['p_id']              = $param['p_id'] ?? 0;
        $param['content']           = $param['content'] ?? '';
        try {
            Db::startTrans();
            $friend_circle = FriendCircleModel::find($param['friend_circle_id']);
            if (!FriendCircleModel::exists($param['friend_circle_id'])) {
                return [
                    'code' => 404 ,
                    'data' => '未找到相关朋友圈信息，拒绝操作' ,
                ];
            }
            FriendCircleCommentModel::insertGetId(array_merge([
                'user_id' => $user_id
            ] , $param));
            $res = self::util_comment($param['friend_circle_id']);
            // 更新这条评论相关的用户未读消息数量
            $friend = FriendCircleCommentModel::userIdByFriendCircleId($param['friend_circle_id']);
            if (!array_search($friend_circle['user_id'] , $friend)) {
                $friend[] = $friend_circle['user_id'];
            }
            foreach ($friend as $v)
            {
                if ($v == $user_id) {
                    continue ;
                }
                if (!FriendCircleUnreadModel::exists($v)) {
                    FriendCircleUnreadModel::insert($v);
                }
                FriendCircleUnreadModel::countForComment($v);
            }
            Db::commit();
            return [
                'code' => 0 ,
                'data' => $res
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    // 删除评论
    public static function delComment($user_id , $param = [])
    {
        $validator = Validate::make([
            'id' => 'require' ,
        ] , [
            'id.require' => '待删除评论尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['id'] = $param['id'] ?? 0;
        $comment = FriendCircleCommentModel::find($param['id']);
        if (empty($comment)) {
            return [
                'code' => 404 ,
                'data' => '未找到该条评论'
            ];
        }
        if ($user_id != $comment['user_id']) {
            return [
                'code' => 403 ,
                'data' => '你没有权限删除别人的评论' ,
            ];
        }
        FriendCircleCommentModel::delete($comment['id']);
        $res = self::util_comment($comment['friend_circle_id']);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 设置朋友圈赞
    public static function commendation($user_id , $param = [])
    {
        $validator = Validate::make([
            'id' => 'require' ,
        ] , [
            'id.require' => '朋友圈id尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['id'] = $param['id'] ?? 0;
        $friend_circle = FriendCircleModel::find($param['id']);
        // 检查朋友圈是否存在
        if (empty($friend_circle)) {
            return [
                'code' => 404 ,
                'data' => '未找到相关朋友圈信息，拒绝操作'
            ];
        }
        try {
            Db::startTrans();
            // 检查是点赞|还是取消（该条语句会加排他锁！防止并发情况下，A 进程检测不存在|B 进程检测不存在 ..导致单个用户点赞 n 次的情况）
            $exists = FriendCircleCommendationLogModel::exists($user_id , $param['id']);
            if ($exists) {
                // 已经点过赞了
                FriendCircleModel::changeCommendation($param['id'] , 'decrement');
                FriendCircleCommendationLogModel::delete($user_id , $param['id']);
                // 状态更新为 未点赞
            } else {
                // 还未点赞
                FriendCircleModel::changeCommendation($param['id'] , 'increment');
                FriendCircleCommendationLogModel::insertGetId([
                    'user_id'           => $user_id ,
                    'friend_circle_id'  => $param['id']
                ]);
                // 状态更新为 已点赞
            }
            $friend_circle = self::util_detail($user_id , $param['id']);
            Db::commit();
            return [
                'code' => 0 ,
                'data' => $friend_circle
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    // 检查用户针对某条朋友圈的操作是否具有给定权限
    // 好友针对朋友圈的操作：点赞|评论
    // 而像  删除朋友圈|删除评论 ，只要确定所处理的事物是你的所有物即可！
    public static function canDoForFriendCircle($user_id , $friend_circle_id)
    {
        $friend_circle = FriendCircleModel::find($friend_circle_id);
        if ($friend_circle['open_level'] == 'self' && $user_id != $friend_circle['user_id']) {
            // 仅自己可见，你非该条朋友圈本人
            return [
                'code' => 403 ,
                'data' => '该条朋友圈已经设置为仅自己可见'
            ];
        }
        if ($friend_circle['open_level'] == 'assign' && $user_id != $friend_circle['user_id'] && !FriendCircleVisibleModel::exists($user_id)) {
            // 指定用户可见，你非本人，且并非在可见的列表
            return [
                'code' => 403 ,
                'data' => '该条朋友圈已经设置为指定用户可见' ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => '允许的操作'
        ];
    }

    // 获取朋友圈详情
    public static function detail($user_id , $param)
    {
        $validator = Validate::make([
            'id' => 'require' ,
        ] , [
            'id.require' => '待获取详情的朋友圈id尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['id'] = $param['id'] ?? 0;
        $friend_circle = FriendCircleModel::find($param['id']);
        // 检查朋友圈是否存在
        if (empty($friend_circle)) {
            return [
                'code' => 404 ,
                'data' => '未找到相关朋友圈信息，拒绝操作'
            ];
        }
        $res = self::util_detail($user_id , $param['id']);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 获取朋友圈详情
    public static function util_detail($user_id , $id)
    {
        // 获取朋友圈
        $friend_circle = FriendCircleModel::find($id);
        // 朋友圈发布者信息
        $friend_circle['user'] = MemberAction::app_user_info($friend_circle['user_id']);
        // 获取图片
        $friend_circle['media'] = self::util_media($id);
        // 获取点赞好友列表
        $friend_circle['commendation_user'] = self::util_commendationUser($user_id , $id);
        // 获取评论
        $friend_circle['comment'] = self::util_comment($id);
        // 是否点赞：0-未点赞 1-已经点赞
        $friend_circle['commended'] = (int) FriendCircleCommendationLogModel::exists($user_id , $id);

        return $friend_circle;
    }

    // 获取针对当前登录用户展示的点赞用户列表
    public static function util_commendationUser($user_id , $friend_circle_id)
    {
        $log = FriendCircleCommendationLogModel::get($friend_circle_id);
        $user = [];
        foreach ($log as $v)
        {
            $user[] = MemberAction::app_user_info($v['user_id'] , $user_id);
        }
        return $user;
    }

    // 获取朋友圈评论
    public static function util_comment($friend_circle_id)
    {
        // 全部层级排列
        $comment = FriendCircleCommentModel::getByFriendCircleId($friend_circle_id);
        foreach ($comment as &$v)
        {
            if (empty($v['p_id'])) {
                $v['parent'] = null;
            } else {
                // 评论谁
                foreach ($comment as $v1)
                {
                    if ($v['p_id'] == $v1['id']) {
                        $parent = $v1;
                        $parent['user'] = MemberAction::app_user_info($v['user_id']);
                        $v['parent'] = $parent;
                        break;
                    }
                }
            }
            // 当前发布评论的用户
            $v['user'] = MemberAction::app_user_info($v['user_id']);
        }
        return $comment;
    }

    // 获取朋友圈图片
    public static function util_media($friend_circle_id)
    {
        $res = FriendCircleMediaModel::get($friend_circle_id);
        foreach ($res as &$v)
        {
            $v['thumb'] = image_url($v['thumb']);
            $v['url']   = image_url($v['path']);
        }
        return $res;
    }

    // 删除朋友圈
    public static function delete($user_id , array $param = [])
    {
        $validator = Validate::make([
            'id' => 'require' ,
        ] , [
            'id.require' => '待删除的朋友圈id尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['id'] = $param['id'] ?? 0;
        $friend_circle = FriendCircleModel::find($param['id']);
        if ($friend_circle['user_id'] != $user_id) {
            return [
                'code' => 403 ,
                'data' => '你并非该条朋友圈的发布者，无权限删除'
            ];
        }
        try {
            Db::startTrans();
            // 删除朋友圈
            FriendCircleModel::delete($friend_circle['id']);
            // 删除评论
            FriendCircleCommentModel::deleteByFriendCircleId($friend_circle['id']);
            // 删除图片
            self::util_deleteImage($friend_circle['id']);
            // 删除点赞记录
            FriendCircleCommendationLogModel::deleteByFriendCircleId($friend_circle['id']);
            // 删除朋友圈可见的用户记录
            FriendCircleVisibleModel::deleteByFriendCircleId($friend_circle['id']);
            Db::commit();
            return [
                'code' => 0 ,
                'data' => ''
            ];
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    // 删除给定朋友圈的图片
    public static function util_deleteImage($friend_circle_id)
    {
        $media = self::util_media($friend_circle_id);
        foreach ($media as $v)
        {
            $path = image_realpath($v['path']);
            // 删除文件
            File::dFile($path);
            FriendCircleMediaModel::delete($v['id']);
        }
        return count($media);
    }

    // 设置朋友圈权限
    public static function friendPriv($user_id , array $param = [])
    {
        $validator = Validate::make([
            'user_id'   => 'require' ,
            'type'      => 'require' ,
            'value'     => 'require' ,
        ] , [
            'user_id.require' => '待设置权限的 user_id 尚未提供' ,
            'type.require' => 'type 尚未提供' ,
            'value.require' => 'value 尚未提供' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $param['user_id']   = $param['user_id'] ?? 0;
        $param['type']      = $param['type'] ?? null;
        $param['value']     = $param['value'] ?? 0;
        $param['value']     = intval($param['value']);
        $range = array_keys(config('business.friend_privilege'));
        if (!in_array($param['type'] , $range)) {
            return [
                'code' => 400 ,
                'data' => '不支持的设置类型'
            ];
        }
        $exists = FriendPrivilegeModel::exists($user_id , $param['user_id']);
        if (!$exists) {
            $param['friend_id'] = $param['user_id'];
            // 不存在|新增
            FriendPrivilegeModel::insertGetId(array_merge($param , [
                'user_id' => $user_id
            ]));
        }
        FriendPrivilegeModel::setPriv($user_id , $param['user_id'] , $param['type'] , $param['value']);
        return [
            'code' => 0 ,
            'data' => ''
        ];
    }

    // 朋友圈个人信息
    public static function info(array $param)
    {
        $validator = Validate::make([
            'user_id'   => 'require' ,
        ] , [
            'user_id.require' => 'user_id 尚未提供' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        // 当前登录用户信息
        $user = MemberAction::app_user_info($param['user_id']);
        // 登陆用户朋友圈信息
        $info = self::util_info($param['user_id']);
        return [
            'code' => 0 ,
            'data' => [
                'user' => $user ,
                'info' => $info
            ]
        ];
    }

    // 朋友圈列表
    public static function friendCircle($user_id)
    {
        $count = FriendCircleModel::countForAll($user_id);
        $page = Page::deal($count);
        $res = FriendCircleModel::getForAll($user_id , $page['offset'] , $page['limit']);
        foreach ($res as &$v)
        {
            // 获取发布者信息
            $v['user'] = MemberAction::app_user_info($v['user_id']);
            // 获取图片
            $v['media'] = self::util_media($v['id']);
            // 获取点赞好友列表
            $v['commendation_user'] = self::util_commendationUser($user_id , $v['id']);
            // 获取评论
            $v['comment'] = self::util_comment($v['id']);
            // 是否点赞：0-未点赞 1-已经点赞
            $v['commended'] = (int) FriendCircleCommendationLogModel::exists($user_id , $v['id']);
        }
        $res = Page::data($page , $res);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 个人朋友圈发布历史
    public static function history(array $param = [])
    {
        $validator = Validate::make([
            'user_id' => 'require' ,
        ] , [
            'user_id.require' => '待获取朋友圈的 user_id 尚未提供'
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        // 按照年进行分组
        $count = FriendCircleModel::count($param['user_id']);
        $page = Page::deal($count);
        $res = FriendCircleModel::get($param['user_id'] , $page['offset'] , $page['limit']);
        // 按年进行分组
        $year = [];
        $exists = function($y) use(&$year){
            foreach ($year as $v)
            {
                if ($v['year'] == $y) {
                    return true;
                }
            }
            return false;
        };
        foreach ($res as $v)
        {
//            $v['year'] = intval($v['year']);
            // 获取图片
            $v['media'] = self::util_media($v['id']);
            // 获取点赞好友列表
            $v['commendation_user'] = self::util_commendationUser($param['user_id'] , $v['id']);
            // 获取评论
            $v['comment'] = self::util_comment($v['id']);
            // 是否点赞：0-未点赞 1-已经点赞
            $v['commended'] = (int) FriendCircleCommendationLogModel::exists($param['user_id'] , $v['id']);
            if (!$exists($v['year'])) {
                $year[] = [
                    'year' => $v['year'] ,
                    'data' => [$v]
                ];
            } else {
                foreach ($year as &$v1)
                {
                    if ($v1['year'] == $v['year']) {
                        $v1['data'][] = $v;
                    }
                }
            }
        }
        usort($year , function($a , $b){
            if ($a['year'] == $b['year']) {
                return 0;
            }
            return $a['year'] > $b['year'] ? -1 : 1;
        });
        $res = Page::data($page , $year);
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    // 获取用户朋友圈相关信息
    public static function util_info($user_id)
    {
        if (!FriendCircleInfomodel::exists($user_id)) {
            // 不存在，新增
            FriendCircleInfoModel::insertGetId([
                'user_id' => $user_id ,
                'background_image' => '' ,
            ]);
        }
        // 存在
        $res = FriendCircleInfoModel::find($user_id);
        $res['background_image'] = image_url($res['background_image']);
        return $res;
    }


    // 好友权限
    public static function util_friendPriv($user_id , $friend_id)
    {
        $res = FriendPrivilegeModel::findByUserId($user_id , $friend_id);
        if (empty($res)) {
            // 没有权限设置记录
            FriendPrivilegeModel::insertGetId([
                'user_id'   => $user_id ,
                'friend_id' => $friend_id
            ]);
            return FriendPrivilegeModel::findByUserId($user_id , $friend_id);
        }
        return $res;
    }

    // 设置朋友圈未读数量数量
    public static function setCountForFriendCircle($user_id)
    {
        FriendCircleUnreadModel::setCountForFriendCircle($user_id , 0);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // 设置未读评论数量
    public static function setCountForComment($user_id)
    {
        FriendCircleUnreadModel::setCountForComment($user_id , 0);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    // 获取朋友圈相关未读信息
    public static function friendCircleUnread($user_id)
    {
        $res = FriendCircleUnreadModel::findByUserId($user_id);
        if (empty($res)) {
            FriendCircleUnreadModel::insert($user_id);
            $res = FriendCircleUnreadModel::findByUserId($user_id);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }
}