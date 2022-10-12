<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/3/24
 * Time: 14:30
 *
 * 此为处理 错误 / 异常
 */

namespace Core\Lib;

use Exception;
use Throwable as ThrowableNative;

class Throwable
{
    // 错误说明
    private static  $errorLevelExplanation = [
        '1'    => 'E_ERROR：致命的运行错误。错误无法恢复，终止执行脚本' ,
        '2'    => 'E_WARNING：运行时警告（非致命错误）。脚本继续运行' ,
        '4'    => 'E_PARSE：编译时解析错误。解析错误只由分析器产生' ,
        '8'    => 'E_NOTICE：运行时提醒(这些经常是你代码中的bug引起的，也可能是有意的行为造成的。)' ,
        '16'   => 'E_CORE_ERROR' ,
        '32'   => 'E_CORE_WARNING PHP启动时初始化过程中的警告(非致命性错)。' ,
        '64'   => 'E_COMPILE_ERROR 编译时致命性错。这就像由Zend脚本引擎生成了一个E_ERROR。' ,
        '128'  => 'E_COMPILE_WARNING 编译时警告(非致命性错)。这就像由Zend脚本引擎生成了一个E_WARNING警告。' ,
        '256'  => 'E_USER_ERROR 用户自定义的错误消息。这就像由使用PHP函数trigger_error（程序员设置E_ERROR）' ,
        '512'  => 'E_USER_WARNING 用户自定义的警告消息。这就像由使用PHP函数trigger_error（程序员设定的一个E_WARNING警告）' ,
        '1024' => 'E_USER_NOTICE 用户自定义的提醒消息。这就像一个由使用PHP函数trigger_error（程序员一个E_NOTICE集）' ,
        '2048' => 'E_STRICT 编码标准化警告。允许PHP建议如何修改代码以确保最佳的互操作性向前兼容性。' ,
        '4096' => 'E_RECOVERABLE_ERROR 开捕致命错误。这就像一个E_ERROR，但可以通过用户定义的处理捕获（又见set_error_handler（））' ,
        '8191' => 'E_ALL'
    ];

    // 预定义请求头
    private $header = [];

    function __construct($header = [])
    {
        $this->header = $header;
    }

    // 设置请求头
    private function setResponseHeader(...$args)
    {
        if (count($args) == 1) {
            $header = $args[0];
            if (is_string($header)) {
                header($header);
                return ;
            }
            if (is_array($header)) {
                foreach ($header as $k => $v)
                {
                    header(sprintf('%s: %s' , $k , $v));
                }
                return ;
            }
            exit(sprintf("file: %s\nline：%d\nmessage: 参数 1 类型错误\n" , __FILE__ , __LINE__));
        }
        if (count($args) == 2) {
            header(sprintf('%s: %s' , $args[0] , $args[1]));
            return ;
        }
        exit(sprintf("file: %s\nline：%d\nmessage: 不支持的调用方式\n" , __FILE__ , __LINE__));
    }

    // 设置状态码
    private function setStatusCode($code = 500)
    {
        header('HTTP/1.1 500 Internal Server Error ');
    }

    // 获取错误等级
    public static function level($value = 0)
    {
        switch ($value)
        {
            case E_ERROR:
                $level = 'E_ERROR';
                break;
            case E_WARNING:
                $level = 'E_WARNING';
                break;
            case E_NOTICE:
                $level = 'E_NOTICE';
                break;
            case E_USER_ERROR:
                $level = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $level = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $level = 'E_USER_NOTICE';
                break;
            case E_CORE_ERROR:
                $level = 'E_CORE_ERROR';
                break;
            case E_CORE_WARNING:
                $level = 'E_CORE_WARNING';
                break;
            case E_PARSE:
                $level = 'E_PARSE';
                break;
            case E_COMPILE_ERROR:
                $level = 'E_COMPILE_ERROR';
                break;
            case E_COMPILE_WARNING:
                $level = 'E_COMPILE_WARNING';
                break;
            default:
                $level = 'unknow level: ' . $value;
        }
        return $level;
    }

    // 错误中文说明
    public static function levelExplanation($level = 0)
    {
        if (array_key_exists($level , self::$errorLevelExplanation)) {
            return self::$errorLevelExplanation[$level];
        }
        return '未知的错误等级：' . $level;
    }

    // 异常：记录日志用
    public static function exceptionReport(ThrowableNative $throwable)
    {
        $trace = $throwable->getTrace();
        $file  = $throwable->getFile();
        $line  = $throwable->getLine();
        $message   = $throwable->getMessage();
        $i	   = 0;
        $msg  = '----- Exception Start -----';
        $msg .= "\r\n";
        $msg .= "Exception: Time:" . date('Y-m-d H:i:s' , time()) . "  File:$file  Line:$line  Message:$message";
        $msg .= "\r\n";
        foreach ($trace as $v)
        {
            $file = isset($v['file']) ? $v['file'] : 'unknow';
            $line = isset($v['line']) ? $v['line'] : 'unknow';
            $msg .= '#' . $i . ' ' . $file . ' ';
            if (isset($v['class'])) {
                $msg .=  $v['class'] . $v['type'] . $v['function'] . '(' . $line . ')';
            } else {
                $msg .= $v['function'] . '(' . $line . ')';
            }
            $msg .= "\r\n";
            $i++;
        }
        $msg .= "----- Exception End ------";
        $msg .= "\r\n";
        $msg .= "\r\n";
        return $msg;
    }

    // 错误：记录日志用
    public static function errorReport($trace , $file , $line , $message)
    {
        $i	  = 0;
        $msg  = '----- Error Start -----';
        $msg .= "\r\n";
        $msg .= "Error: Time:" . date('Y-m-d H:i:s' , time()) . "  File:$file  Line:$line  Message:$message";
        $msg .= "\r\n";
        foreach ($trace as $v)
        {
            $file = isset($v['file']) ? $v['file'] : 'unknow';
            $line = isset($v['line']) ? $v['line'] : 'unknow';
            $msg .= '#' . $i . ' ' . $file . ' ';
            if (isset($v['class'])) {
                $msg .= $v['class'] . $v['type'] . $v['function'] . '(' . $line . ')';
            } else {
                $msg .= $v['function'] . '(' . $line . ')';
            }
            $msg .= "\r\n";
            $i++;
        }
        $msg .= "----- Error End ------";
        $msg .= "\r\n";
        $msg .= "\r\n";
        return $msg;
    }


    // 致命错误：记录日志用
    public static function fetalErrorReport($file , $line , $message)
    {
        $i	  = 0;
        $msg  = '----- FetalError Start -----';
        $msg .= "\r\n";
        $msg .= "Error: Time:" . date('Y-m-d H:i:s' , time()) . "  File:$file  Line:$line  Message:$message";
        $msg .= "\r\n";
        $msg .= "----- FetalError End ------";
        $msg .= "\r\n";
        $msg .= "\r\n";
        return $msg;
    }

    // 开发环境-json：异常
    public function exceptionJsonHandlerInDev(ThrowableNative $throwable)
    {
        $file   = $throwable->getFile();
        $line   = $throwable->getLine();
        $msg    = $throwable->getMessage();
        $trace  = $throwable->getTrace();
        $log = [
            'type' => 'Exception' ,
            // 回溯跟踪
            'trace' => [
                [
                    'file' => $file ,
                    'line' => $line ,
                    'message' => $msg ,
                ] ,
            ]
        ];
        foreach ($trace as $v)
        {
            $log['trace'][] = $this->line($v['file'] ?? 'unknow' , $v['line'] ?? 'unknow' , $v['function'] ?? '' , $v['class'] ?? '' , $v['type'] ?? '');
        }
        // 输出错误信息
        $this->response($log);
    }

    // 开发环境-json：错误
    public function errorJsonHandlerInDev($level , $message , $file , $line , $context = null)
    {
        $trace = debug_backtrace();
        array_shift($trace);
        $log = [
            'type' => 'Error' ,
            'level' => self::level($level) ,
            'trace' => [
                [
                    'file' => $file ,
                    'line' => $line ,
                    'message' => $message ,
                ]
            ]
        ];
        foreach ($trace as $v)
        {
            $log['trace'][] = $this->line($v['file'] ?? 'unknow' , $v['line'] ?? 'unknow' , $v['function'] ?? '', $v['class'] ?? '' , $v['type'] ?? '');
        }
        $this->response($log);
    }

    // 开发环境-json：致命错误
    public function fetalErrorJsonHandlerInDev()
    {
        if (!is_null($last = error_get_last())) {
            // todo 如果碰到 php 抛出致命错时，请及时处理！
            var_dump("php 抛出了致命错误，请扩展改方法！！\nline: " . __LINE__ . "\nfile: " . __FILE__);
        }
    }

    // 生成单条信息
    private function line($file , $line , $function , $class = '' , $type = '')
    {
        return [
            'file' => $file ,
            'line' => $line ,
            'operation' => empty($class) ? sprintf("%s" , $function) : sprintf("%s%s%s" , $class , $type , $function)
        ];
    }

    // 响应
    private function response($response = '')
    {
        // 设置状态码
        $this->setResponseHeader('HTTP/1.1 500 Internal Server Error');
        // 设置用户自定义响应头
        $this->setResponseHeader($this->header);
        if (is_scalar($response)) {
            exit($response);
        }
        // 设置该头部是便于调试工具显示数据
        $this->setResponseHeader('Content-Type' , 'application/json');
        exit(json_encode($response));
    }

    // todo 开发环境-string：异常
    // todo 开发环境-string：错误
    // todo 开发环境-string：致命错误

    // todo 生产环境-json：异常
    // todo 生产环境-json：错误
    // todo 生产环境-json：致命错误

    // todo 生产环境-string：异常
    // todo 生产环境-string：错误
    // todo 生产环境-string：致命错误

}