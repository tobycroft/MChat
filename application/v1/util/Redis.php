<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/12/12
 * Time: 10:19
 */
namespace app\v1\util;

use Redis as ORedis;
use Exception;

class Redis
{
    // 连接
    protected $conn = null;
    // 安全前缀
    public $prefix = '';

    public function __construct($host = '' , $port , $password = '' , $timeout = 0)
    {
        $this->conn = new ORedis();
        $res = $this->conn->connect($host , $port , $timeout);
        if (!$res) {
            throw new Exception("创建 Redis 连接发生错误：host：{$host}；port：{$port}；timeout：{$timeout}");
        }
        $this->conn->auth($password);
    }
    // 调用原生方法
    public function native($method , ...$args)
    {
        return call_user_func_array([$this->conn , $method] , $args);
    }
    // string
    // hash
    // list
    // set
    // sorted set
    // 添加字符串数据
    public function key($name)
    {
        return sprintf('%s%s' , $this->prefix , $name);
    }
    // 获取/设置字符串
    public function string($name , $value = '')
    {
        $key = $this->key($name);
        if (empty($value)) {
            return $this->conn->get($key);
        }
        $this->native('set' , $key , $value);
    }
    // 删除 key
    public function del($name)
    {
        return $this->native('del' , $this->key($name));
    }
    public function parse($str = '')
    {
        return json_decode($str , true);
    }
    public function json($obj = null)
    {
        return json_encode($obj);
    }
    // 清空 key
    public function  flushAll()
    {
        $key = sprintf('%s*' , $this->prefix);
        $keys = $this->native('keys' , $key);
        return $this->native('del' , $keys);
    }
}