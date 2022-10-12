<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/5
 * Time: 15:49
 *
 * 分页处理，详细使用方法请在 doc/Page.md 中查看
 */

namespace app\v1\util;

class Page
{
    /**
     * @title 分页处理
     *
     * @param $count 总记录数
     *
     * @return null:object
     */
    public static function deal($count = 1){
        $client = config('page.client');
        $page = isset($_REQUEST[$client['page']]) ? intval($_REQUEST[$client['page']]) : 1;
        $min_page = 1;
        $limit = config('page.limit');
        $field = config('page.field');
        $max_page = ceil($count / $limit);
        $max_page = max($min_page , $max_page);
        $page   = max($min_page , min($max_page , $page));
        $offset = max(0 , ($page - 1) * $limit);

        return [
            $field['min_page'] => $min_page ,
            $field['max_page'] => $max_page ,
            $field['page'] => $page ,
            $field['limit'] => $limit ,
            $field['offset'] => $offset ,
        ];
    }

    /**
     * 生成返回给用户的数据
     */
    public static function data(array $page , $data){
        return array_merge($page , [
            'data' => $data
        ]);
    }
}