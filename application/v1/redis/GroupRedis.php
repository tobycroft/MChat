<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/4
 * Time: 14:32
 */

namespace app\v1\redis;


class GroupRedis extends BaseRedis
{
    // key 群消息置顶
    public static $groupTopKey = '__group_msg_top__%d';

    // 群会话置顶
    public static function setTopGroup($uid , $gid)
    {
        $key = sprintf(self::$groupTopKey , $uid);
        $list = redis()->native('lRange' , $key , 0 , -1);
        if (!empty($list)) {
            if (in_array($gid , $list)) {
                return ;
            }
            redis()->native('lPush'  , $key , $gid);
            return ;
        }
        redis()->native('lPush'  , $key , $gid);
        // redis()->native('setTimeout' , $key , cache_duration());
    }

    // 群会话取消置顶
    public static function unsetTopGroup($uid , $gid)
    {
        $key = sprintf(self::$groupTopKey , $uid);
        $list = redis()->native('lRange' , $key , 0 , -1);
        if (!in_array($gid , $list)) {
            return ;
        }
        $index = array_search($gid , $list);
        array_splice($list , $index , 1);
        redis()->native('del' , $key);
        array_walk($list , function($v) use($key){
            redis()->native('rPush' , $key , $v);
        });
    }

    // 获取置顶群会话
    public static function getTopGroup($uid)
    {
        $key = sprintf(self::$groupTopKey , $uid);
        return redis()->native('lRange' , $key , 0 , -1);
    }

    // 是否置顶
    public static function isTop($uid , $gid)
    {
        $key = sprintf(self::$groupTopKey , $uid);
        $list = redis()->native('lRange' , $key , 0 , -1);
        if (in_array($gid , $list)) {
            return 1;
        }
        return 0;
    }
}