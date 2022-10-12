<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/9/2
 * Time: 15:24
 */

namespace app\v1\model;


class GameUserModel extends Model
{
    protected $table = 'cq_game_user';

    public static function findByUserIdAndType($user_id , $type)
    {
        $res = self::where([
            ['user_id' , '=' , $user_id] ,
            ['type' , '=' , $type] ,
        ])
            ->find();
        self::single($res);
        return $res;
    }
}