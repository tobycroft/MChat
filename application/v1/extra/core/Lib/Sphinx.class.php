<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2017/7/26
 * Time: 16:06
 */

namespace Lib;

class Sphinx {
    protected static $_instance = null;
    protected static $_connect = null;

    /**
     * @param string $host 主机名 或 ip 名
     * @param int $port 端口
     * @return Sphinx|null
     */
    function __construct($host = '127.0.0.1' , $port = 9312){
        if (!is_null(self::$_instance)) {
            throw new \Exception('已经存在一个 Sphinx 实例，不允许再重复实例化');
        }

        self::$_connect = new \SphinxClient();
        self::$_connect->setServer($host , $port);
        // 如果采用了中文分词技术的时候，应该设置为 SPH_MATCH_ANY
        // 否则应该设置 SPH_MATCH_ALL
        self::$_connect->setMatchMode(SPH_MATCH_ALL);
        // 结果集处理（返回数组）
        self::$_connect->setArrayResult(true);

    }

    /**
     * @param string $host 主机名 或 ip 名
     * @param int $port 端口
     * @return Sphinx|null
     */
    public static function getInstance($host = '127.0.0.1' , $port = 9312){
        if (is_null(self::$_instance)) {
            self::$_instance = new self($host , $port);
        }

        return self::$_instance;
    }

    /**
     * @param string $query_string
     * @param string $index
     * @param string $comment
     * @return mixed
     */
    public static function query($query_string = '' , $index = '*' , $comment = ''){
        return self::$_connect->query($query_string , $index , $comment);
    }

    /**
     * @param string $attr
     * @param array $values
     * @param bool $exclude
     * @return mixed
     */
    public static function setFilter($attr = '' , Array $values , $exclude = false){
        return self::$_connect->setFilter($attr , $values , $exclude);
    }

    /**
     * @param $osffet
     * @param $limit
     * @param int $max_matches
     * @param int $cutoff
     * @return mixed
     */
    public static function setLimits($offset , $limit , $max_matches = 0 , $cutoff = 0 ){
        return self::$_connect->setLimits($offset , $limit , $max_matches , $cutoff);
    }

    /**
     * @param $mode
     * @return mixed
     */
    public static function setMatchMode($mode){
        return self::$_connect->setMatchMode($mode);
    }

    /**
     * @param $mode
     * @param $sort
     */
    public static function setSortMode($mode , $sort){
        return self::$_connect->setSortMode($mode , $sort);
    }

    /**
     * @return mixed
     */
    public static function getStatus(){
        return self::$_connect->status;
    }

    /**
     * @param bool $array_result
     * @return mixed
     */
    public static function setArrayResult($array_result = false){
        return self::$_connect->setArrayResult($array_result);
    }

}