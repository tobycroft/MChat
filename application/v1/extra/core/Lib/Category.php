<?php

namespace Core\Lib;

/*
 * 获取结构化数据
 */
use Exception;

class Category {
    // 默认的字段字典
    public static $field = [
        'id'    => 'id' ,
        'p_id'   => 'p_id'
    ];

    // 初始化
    protected static function _field($field = null){
        $field = empty($field) ? self::$field : $field;
        if (!isset($field['id']) || !isset($field['pid'])) {
            throw new Exception('传入的字段字典错误！');
        }
        return $field;
    }

    // 找到项
    public static function current($id , array $data = [] , $field = null){
        $field = self::_field($field);
        foreach ($data as $v)
        {
            if ($v[$field['id']] == $id)  {
                return $v;
            }
        }
        return false;
    }

    // 单个：获取父级
    public static function parent($id , array $data = [] , $field = null){
        $field  = self::_field($field);
        $cur    = self::current($id , $data , $field);
        foreach ($data as $v)
        {
            if ($v[$field['id']] == $cur[$field['pid']]) {
                return $v;
            }
        }
        return false;
    }

    // 所有：获取父级
    public static function parents($id , array $data = [] , $field = null , $save_self = true , $struct = true){
        $field      = self::_field($field);
        $cur        = self::current($id , $data  , $field);
        $save_self  = is_bool($save_self) ? $save_self : true;
        $struct     = is_bool($struct) ? $struct : true;

        $get = function($cur , array $res = []) use(&$get , $data , $field){
            $parent = self::parent($cur[$field['id']] , $data , $field);
            if (!$parent) {
                return $res;
            }
            $res[] = $parent;
            return $get($parent , $res);
        };
        $parents = $get($cur);
        if ($save_self) {
            // 保留自身
            array_unshift($parents , $cur);
        }
        $parents = array_reverse($parents);
        if ($struct) {
            $get_struct = function($list , array $res = []) use(&$get_struct){
                if (count($list) === 0) {
                    return $res;
                }
                $cur = array_shift($list);
                $res = $cur;
                $res['children'] = $get_struct($list);
                return $res;
            };
            $parents = $get_struct($parents);
        }
        return $parents;
    }

    // 获取直系子集
    public static function children($id , array $data = [] , $field = null){
        $field  = self::_field($field);
        $res    = [];
        foreach ($data as $v)
        {
            if ($v[$field['pid']] == $id) {
                $res[] = $v;
            }
        }
        return $res;
    }

    // 获取所有子集
    public static function childrens($id , array $data = [] , $field = null , $save_self = true , $struct = true){
        $field      = self::_field($field);
        $cur        = self::current($id , $data , $field);
        $save_self  = is_bool($save_self) ? $save_self : true;
        $struct     = is_bool($struct) ? $struct : true;

        $get = function($id) use(&$get , $data , $field){
            $children   = self::children($id , $data , $field);
            $res        = $children;
            foreach ($children as $v)
            {
                $other = $get($v[$field['id']]);
                $res = array_merge($res , $other);
            }
            return $res;
        };
        $res = $get($id);
        if ($save_self && $cur !== false) {
            // 保存自身
            array_unshift($res , $cur);
        }
        if ($struct) {
            // 保存结构
            $get_struct = function($id) use(&$get_struct , $res , $field){
                $children = self::children($id , $res , $field);
                foreach ($children as &$v)
                {
                    $v['children'] = $get_struct($v[$field['id']]);
                }
                return $children;
            };
            $res = $get_struct($id);
            if ($save_self && $cur !== false) {
                $cur['children'] = $res;
                $res = $cur;
            }
        }

        return $res;
    }
}