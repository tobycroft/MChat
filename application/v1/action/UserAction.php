<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/13
 * Time: 9:51
 */

namespace app\v1\action;


use app\v1\model\ChatBlackListNewModel;
use app\v1\model\GameUserModel;
use app\v1\model\UserInfoModel;
use app\v1\model\UserInfoNewModel;
use app\v1\util\Game;
use app\v1\util\GameUtil;
use think\Validate;

class UserAction
{
    // 检查用户是否设置了消息推送
    public static function app_can_notice($uid)
    {
        $res = UserInfoModel::api_find_byUid($uid);
        return (bool) $res['can_notice'];
    }

    // 设置消息推送
    public static function app_can_notice_set($uid , $can_notice)
    {
        $can_notice = intval($can_notice);
        UserInfoModel::api_can_notice_set($uid , $can_notice);
        return [
            'code' => 0 ,
            'data' => []
        ];
    }

    public static function app_user_in_black($uid)
    {
        $res = ChatBlackListNewModel::userInBlack(['uid' => $uid] , [] , 20);
        foreach ($res->data as $v)
        {
            $v->user = MemberAction::app_user_info($v->fid);
        }
        return [
            'code' => 0 ,
            'data' => $res
        ];
    }

    public static function isRegisterForGame($uid , array $param)
    {
        $validator = Validate::make([
            'type' => 'require' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $game_type = array_keys(config('business.game_type'));
        if (!in_array($param['type'] , $game_type)) {
            return [
                'code' => 400 ,
                'data' => '不支持的 type 类型，当前支持的 type 类型有：' . implode(',' , $game_type)
            ];
        }
        $game_user = GameUserModel::findByUserIdAndType($uid , $param['type']);
        if (empty($game_user)) {
            return [
                'code' => 0 ,
                'data' => 'n'
            ];
        }
        return [
            'code' => 0 ,
            'data' => 'y'
        ];
    }

    public static function registerForGame($uid , array $param)
    {
        $validator = Validate::make([
            'type' => 'require' ,
            'username' => 'require' ,
            'password' => 'require' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $game_type = array_keys(config('business.game_type'));
        if (!in_array($param['type'] , $game_type)) {
            return [
                'code' => 400 ,
                'data' => '不支持的 type 类型，当前支持的 type 类型有：' . implode(',' , $game_type)
            ];
        }
        $game_user = GameUserModel::findByUserIdAndType($uid , $param['type']);
        if (!empty($game_user)) {
            return [
                'code' => 400 ,
                'data' => '已经注册，请直接登录'
            ];
        }
        $res = GameUtil::register($param['type'] , $param['username'] , $param['password']);
        if ($res['code'] != 0) {
            return $res;
        }
        GameUserModel::insertGetId([
            'user_id' => $uid ,
            'type' => $param['type'] ,
            'username' => $param['username'] ,
            'password' => $param['password']
        ]);
        return [
            'code' => 0 ,
            'data' => '注册成功'
        ];
    }

    // 游戏-登录
    public static function loginForGame($uid , array $param)
    {
        $validator = Validate::make([
            'type' => 'require' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $game_type = array_keys(config('business.game_type'));
        if (!in_array($param['type'] , $game_type)) {
            return [
                'code' => 400 ,
                'data' => '不支持的 type 类型，当前支持的 type 类型有：' . implode(',' , $game_type)
            ];
        }
        if ($param['type'] == 6) {
            $type = 2;
            $game_user = GameUserModel::findByUserIdAndType($uid , $type);
        } else {
            $type = 1;
            $game_user = GameUserModel::findByUserIdAndType($uid , $type);
        }
        if (empty($game_user)) {
            // 注册
//            $username = random(12 , 'letter' , true);
            $username = $uid;
            $password = random(12 , 'mixed' , true);
            $res = GameUtil::register($param['type'] , $username , $password);
            if ($res['code'] != 0) {
                return [
                    'code' => 500 ,
                    'data' => '远程接口错误：' . $res['data'] ,
                ];
            }
            $id = GameUserModel::insertGetId([
                'user_id'   => $uid ,
                'username'  => $username ,
                'password'  => $password ,
                'type' => $type
            ]);
            $game_user = GameUserModel::findById($id);
        }

        $res = GameUtil::login($param['type'] , $game_user->username , $game_user->password);
        if ($res['code'] != 0) {
            return [
                'code' => 500 ,
                'data' => '远程接口错误：' . $res['data'] ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => $res['data']
        ];
    }

    public static function createUserForGame($uid , $type = 1)
    {
//        $type = 1;
        $game_user = GameUserModel::findByUserIdAndType($uid , $type);
        if (!empty($game_user)) {
            return $game_user;
        }
        // 注册
//        $username = random(12 , 'letter' , true);
        $username = $uid;
        $password = random(12 , 'mixed' , true);
        $res = GameUtil::register(1 , $username , $password);
        if ($res['code'] != 0) {
            return [
                'code' => 500 ,
                'data' => '远程接口错误：' . $res['data'] ,
            ];
        }
        $id = GameUserModel::insertGetId([
            'user_id'   => $uid ,
            'username'  => $username ,
            'password'  => $password ,
            'type' => $type
        ]);
        return GameUserModel::findById($id);
    }

    public static function balanceForGame($uid)
    {
        $game_user = self::createUserForGame($uid);
        $res = GameUtil::balance($game_user->username , $game_user->password);
        if ($res['code'] != 0) {
            return [
                'code' => 500 ,
                'data' => '远程接口错误：' . $res['data'] ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => $res['data']
        ];
    }

    public static function transferForGame($uid , array $param)
    {
        $validator = Validate::make([
            'amount' => 'require' ,
        ]);
        if (!$validator->check($param)) {
            return [
                'code' => 400 ,
                'data' => $validator->getError()
            ];
        }
        $order_no = GameUtil::create_uuid();
        $game_user = GameUserModel::findByUserIdAndType($uid , 1);
        // type IN-转入 OUT-转出
        $res = GameUtil::transfer($game_user->username , $game_user->password , 'IN' , $param['amount'] , $order_no);
        if ($res['code'] != 0) {
            return [
                'code' => 500 ,
                'data' => '远程接口错误：' . $res['data'] ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => $res['data']
        ];
    }

    public static function userForGame($uid , $type)
    {
        $user = UserInfoModel::api_find_byUid($uid);
        if (empty($user)) {
            return [
                'code' => 404 ,
                'data' => '用户未找到' ,
            ];
        }
        $game_user = GameUserModel::findByUserIdAndType($uid , $type);
        if (empty($game_user)) {
            $game_user = self::createUserForGame($uid , $type);
        }
        return [
            'code' => 0 ,
            'data' => $game_user
        ];
    }

}