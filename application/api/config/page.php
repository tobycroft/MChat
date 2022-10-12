<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/11/5
 * Time: 16:43
 *
 * 分页
 */
return [
    // 每页显示记录数
    'limit' => 12 ,
    // 返回数据的字段说明
    'field' => [
        // 当前页
        'page' => 'page' ,
        // 最小页数
        'min_page' => 'min_page' ,
        // 最大页数
        'max_page' => 'max_page' ,
        // 总记录数
        'count' => 'count' ,
        // 当前偏移量
        'offset' => 'offset' ,
        // 每页显示记录数
        'limit' => 'limit' ,
        // 结果集
        'data' => 'data'
    ] ,
    // 客户端上传的分页字段
    'client' => [
        // 当前页数
        'page' => 'page'
    ]
];