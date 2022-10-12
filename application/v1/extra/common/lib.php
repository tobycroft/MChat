<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2018/8/23
 * Time: 22:02
 */
namespace extra;

use Exception;

// 获取配置文件
function config($dir , $key , $args = []){
    if (empty($key)) {
        throw new Exception('未提供待查找的 key');
    }
    $keys   = explode('.' , $key);
    $len    = count($keys);
    $index  = 0;
    $res    = null;
    $dir = format_path($dir) . '/';
    static $data = [];
    $do = function($_dir , $v , &$config = []) use(&$do , $dir , $key , $keys , $len ,  &$index , $args){
        $index++;
        $file = format_path($_dir . $v);
        if (is_dir($file)) {
            if (!isset($config[$dir][$v])) {
                $config[$dir][$v] = null;
            }
            $file .= '/';
        } else {
            $tmp_file = $file . '.php';
            if ($len == 1) {
                // 表明仅提供类似 app / mail 这样的文件名称
                if (file_exists($tmp_file) && !is_dir($tmp_file) && !isset($config[$dir][$v])) {
                    $config[$dir][$v] = require_once $tmp_file;
                }
            } else {
                // 提供了类似 app.one 这样的多层级 key
                if ($index == 1) {
                    if ($index + 1 == $len && file_exists($tmp_file) && !is_dir($tmp_file) && !isset($config[$dir][$v])) {
                        $config[$dir][$v] = require_once $tmp_file;
                    }
                } else {
                    if ($index + 1 == $len && file_exists($tmp_file) && !is_dir($tmp_file) && !isset($config[$v])) {
                        $config[$v] = require_once $tmp_file;
                    }
                }
            }
        }
        if ($len == 1) {
            // 表明仅提供类似 app / mail 这样的文件名称
            if (!isset($config[$dir][$v])) {
                throw new Exception("未找到 {$key} 对应键值");
            }
            if (is_array($config[$dir][$v])) {
                return $config[$dir][$v];
            }
            return is_string($config[$dir][$v]) ? vsprintf($config[$dir][$v] , $args) : $config[$dir][$v];
        } else {
            // 提供了类似 app.one 这样的多层级 key
            if ($index === $len) {
                if (!isset($config[$v])) {
                    throw new Exception("未找到 {$key} 对应键值");
                }
                if (is_array($config[$v])) {
                    return $config[$v];
                }
                return is_string($config[$v]) ? vsprintf($config[$v] , $args) : $config[$v];
            } else {
                if ($index == 1) {
                    return $do($file , $keys[$index] , $config[$dir][$v]);
                } else {
                    return $do($file , $keys[$index] , $config[$v]);
                }
            }
        }
    };
    return $do($dir , $keys[$index] , $data);
}