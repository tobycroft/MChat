<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/26
 * Time: 11:39
 */

namespace app\v1\model;

use app\v1\redis\BaseRedis;
use Exception;
use think\Db;
use DateTime;
use DateTimeZone;
use DateInterval;

class FriendCircleModel
{
    public static $table = 'cq_friend_circle';

    public static function insertGetId(array $data = [])
    {
        return Db::table(self::$table)
            ->insertGetId(array_unit($data , [
                'user_id' ,
                'open_level' ,
                'content' ,
            ]));
    }

    public static function find($id)
    {
        $res = Db::table(self::$table)
            ->where('id' , $id)
            ->find();
        return $res;
    }

    public static function exists($id)
    {
        return Db::table(self::$table)
                ->where('id' , $id)
                ->count() > 0;
    }

    public static function findWithLock($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->lock(true)
            ->find();
    }

    public static function changeCommendation($id , $type)
    {
        $range = ['increment' , 'decrement'];
        if (!in_array($type , $range)) {
            throw new Exception('不支持的操作类型');
        }
        $ins = Db::table(self::$table)
            ->where('id' , $id);
        if ($type == 'increment') {
            return $ins->setInc('commendation');
        }
        return $ins->setDec('commendation');
    }

    public static function delete($id)
    {
        return Db::table(self::$table)
            ->where('id' , $id)
            ->delete();
    }

    // 获取指定用户的朋友圈列表
    public static function getForAll($user_id , $offset = 0 , $limit = 10)
    {
        // 获取该用户 + 自身的所有朋友圈列表
        $friend = SingleFriendModel::api_select_byUid($user_id);
        $id_list = [$user_id];
        foreach ($friend as $v)
        {
            $id_list[] = $v['fid'];
        }
        $id_str = implode(',' , $id_list);
        $sql = <<<EOT
                select fc.* from cq_friend_circle as fc 
                where
                    fc.user_id in ({$id_str})
                    and
                    (
                            fc.user_id = :user_id0
                            or
                            (
                                    (
                                            fc.open_level = 'public' 
                                            or
                                            (
                                                    fc.open_level = 'self' 
                                                    and 
                                                    fc.user_id = :user_id1
                                            ) 
                                            or 
                                            (
                                                    fc.open_level = 'assign' 
                                                    and 
                                                    (
                                                            (
                                                                    select count(id) from cq_friend_circle_group_member where 
                                                                            user_id = :user_id2
                                                                            and 
                                                                            friend_circle_group_id in (select friend_circle_group_id from cq_friend_circle_visible where friend_circle_id = fc.id)
                                                            ) > 0
                                                    )
                                            )
                                    )
                                    and 
                                    (
                                            (
                                                    (select count(id) from cq_friend_privilege where user_id = :user_id3 and friend_id = fc.user_id) = 0
                                                    and
                                                    (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id4) = 0
                                            )
                                            or
                                            (
                                                    (select count(id) from cq_friend_privilege where user_id = :user_id5 and friend_id = fc.user_id) > 0
                                                    and
                                                    (select shield from cq_friend_privilege where user_id = :user_id6 and friend_id = fc.user_id) != 1
                                                    and
                                                    (
                                                            (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id7) = 0
                                                            or
                                                            (
                                                                    (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id8) > 0
                                                                    and
                                                                    (select hidden from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id9) != 1
                                                            )
                                                    )
                                            )
                                    )
                            )
                    )
                    order by
                    fc.create_time desc ,
                    fc.id desc
                    limit :offset , :limit;
EOT;
        $res = Db::query($sql , [
            'user_id0' => $user_id ,
            'user_id1' => $user_id ,
            'user_id2' => $user_id ,
            'user_id3' => $user_id ,
            'user_id4' => $user_id ,
            'user_id5' => $user_id ,
            'user_id6' => $user_id ,
            'user_id7' => $user_id ,
            'user_id8' => $user_id ,
            'user_id9' => $user_id ,
            'offset'    => $offset ,
            'limit'     => $limit
        ]);
        return $res;
    }

    // 获取朋友圈记录数
    public static function countForAll($user_id)
    {
        // 获取该用户 + 自身的所有朋友圈列表
        $friend = SingleFriendModel::api_select_byUid($user_id);
        $id_list = [$user_id];
        foreach ($friend as $v)
        {
            $id_list[] = $v['fid'];
        }
        $id_str = implode(',' , $id_list);
        // 带注释版本，青岛 mysql 中查看保存的查询
        $sql = <<<EOT
                select count(fc.id) as `count` from cq_friend_circle as fc 
                where
                    fc.user_id in ({$id_str})
                    and
                    (
                            fc.user_id = :user_id0
                            or
                            (
                                    (
                                            fc.open_level = 'public' 
                                            or
                                            (
                                                    fc.open_level = 'self' 
                                                    and 
                                                    fc.user_id = :user_id1
                                            ) 
                                            or 
                                            (
                                                    fc.open_level = 'assign' 
                                                    and 
                                                    (
                                                            (
                                                                    select count(id) from cq_friend_circle_group_member where 
                                                                            user_id = :user_id2
                                                                            and 
                                                                            friend_circle_group_id in (select friend_circle_group_id from cq_friend_circle_visible where friend_circle_id = fc.id)
                                                            ) > 0
                                                    )
                                            )
                                    )
                                    and 
                                    (
                                            (
                                                    (select count(id) from cq_friend_privilege where user_id = :user_id3 and friend_id = fc.user_id) = 0
                                                    and
                                                    (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id4) = 0
                                            )
                                            or
                                            (
                                                    (select count(id) from cq_friend_privilege where user_id = :user_id5 and friend_id = fc.user_id) > 0
                                                    and
                                                    (select shield from cq_friend_privilege where user_id = :user_id6 and friend_id = fc.user_id) != 1
                                                    and
                                                    (
                                                            (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id7) = 0
                                                            or
                                                            (
                                                                    (select count(id) from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id8) > 0
                                                                    and
                                                                    (select hidden from cq_friend_privilege where user_id = fc.user_id and friend_id = :user_id9) != 1
                                                            )
                                                    )
                                            )
                                    )
                            )
                    );
EOT;
        $res = Db::query($sql , [
            'user_id0' => $user_id ,
            'user_id1' => $user_id ,
            'user_id2' => $user_id ,
            'user_id3' => $user_id ,
            'user_id4' => $user_id ,
            'user_id5' => $user_id ,
            'user_id6' => $user_id ,
            'user_id7' => $user_id ,
            'user_id8' => $user_id ,
            'user_id9' => $user_id ,
        ]);
        $res = $res[0]['count'];
        return intval($res);

    }

    // 获取给定好友的朋友列表
    public static function get($user_id , $offset = 0 , $limit = 10)
    {
        return Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->order('create_time' , 'desc')
            ->order('id' , 'desc')
            ->field('*,date_format(create_time , "%Y") as year')
            ->limit($offset , $limit)
            ->select();
    }

    // 指定用户朋友圈记录数
    public static function count($user_id)
    {
        return Db::table(self::$table)
            ->where('user_id' , $user_id)
            ->count();
    }

    // 检查是否应该更新朋友圈未读消息
    public static function can_inc_unread_count($friend_circle_id , $uid , $fid)
    {
        // 检查朋友圈权限
        $friend_circle = self::find($friend_circle_id);
        if ($friend_circle['open_level'] == 'public') {
            // 检查你对朋友设置的权限
            $user_priv = FriendPrivilegeModel::findByUserId($uid , $fid);
            if (!empty($user_priv) && $user_priv['hidden'] == 1) {
                // 不让朋友看我
                return false;
            }
            // 检查朋友对你设置的权限
            $friend_priv = FriendPrivilegeModel::findByUserId($fid , $uid);
            if (empty($friend_priv) || $friend_priv['shield'] == 0) {
                // 朋友没有对你设置权限
                // 朋友没有设置不看你的朋友圈
                // 更新
                return true;
            }
        }
        return false;
    }

    public static function clearCache($id)
    {
        $key = BaseRedis::key('friend_circle' , $id);
        cache($key , null);
    }
}