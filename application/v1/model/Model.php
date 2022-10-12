<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/8/21
 * Time: 9:32
 */

namespace app\v1\model;

use Exception;
use think\Model as BaseModel;

class Model extends BaseModel
{
    public static function multiple($list)
    {
        foreach ($list as $v)
        {
            static::single($v);
        }
    }

    public static function single($m = null)
    {
        if (empty($m)) {
            return ;
        }
        if (!is_object($m)) {
            throw new Exception('参数 1 类型错误');
        }
    }

    // 更新
    public static function updateById(int $id , array $param = [])
    {
        return static::where('id' , $id)
            ->update($param);
    }

    public static function updateByIds(array $id_list = [] , array $param = [])
    {
        return static::whereIn('id' , $id_list)
            ->update($param);
    }

    public static function getAll()
    {

        $res = static::order('id' , 'desc')
            ->select();
        static::multiple($res);
        return $res;
    }

    public static function findById(int $id)
    {
        $res = static::find($id);
        if (empty($res)) {
            return null;
        }
        static::single($res);
        return $res;
    }

    public static function getByIds(array $id_list = [])
    {
        $res = static::whereIn('id' , $id_list)->select();
        static::multiple($res);
        return $res;
    }

    // 检查是否全部存在
    public static function allExist(array $id_list = [])
    {
        $count = static::whereIn('id' , $id_list)->count();
        return count($id_list) == $count;
    }

    public static function delByIds(array $id_list = [])
    {
        return self::whereIn('id' , $id_list)
            ->delete();
    }

    public static function delById(int $id)
    {
        return self::where('id' , $id)
            ->delete();
    }
}