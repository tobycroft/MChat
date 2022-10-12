<?php

/*
 * author grayVTouch 2016-10-12
 */

namespace Core\Lib;

class Router {

    /**
     * url 解析模式
     * QUERY_STRING  http://test.com/index.php?module=Pc&controller=Index&action=index&args=args
     * PATH_INFO     http://test.com/index.php/Pc/Index/index?args=args
     * REWRITE       http://test.com/Pc/Index/index?args=args
     */
	private static $_urlModeRange = ['QUERY_STRING' , 'PATH_INFO' , 'REWRITE'];

    // 模块范围
    private static $_moduleRange = [
        // 面向后台用户
        'Admin' => [
            'PcAdmin' => 'Admin' ,
            'MobileAdmin' => 'MobileAdmin'
        ] ,
        // 面向普通用户
        'User' => [
            'Pc' => 'Home' ,
            'Mobile' => 'Mobile'
        ]
    ];

    // 平台范围类型
    private static $_platformRange = ['Pc' , 'Mobile'];

    // 用户类型范围
    private static $_userTypeRange = ['Admin' , 'User'];

    // 用户类型：Admin/User
    public static $userType = '';

    // url 模式
    public static $urlMode = 'REWRITE';

    // // 模块
    public static $module       = '';
    // 控制器
    public static $controller   = '';
    // 动作
    public static $action       = '';

    // 获取可选的模块范围
    public static function modules($user_type){
        if (!in_array($user_type , self::$_userTypeRange)) {
             $user_type = 'User';
        }

        return self::$_moduleRange[$user_type];
    }

    // 获取所有支持的 module
    public static function allModules(){
        $res = [];

        foreach (self::$_userTypeRange as $v)
        {
            $res = array_merge($res , self::$_moduleRange[$v]);
        }

        return $res;
    }

	/*
	 * 根据用户类型 + 平台 获取对应加载的模块
	 */
	public static function getModule(){
	    $modules = self::allModules();

	    if (isset($_COOKIE['module']) && in_array($_COOKIE['module'] , $modules)) {
	        return $_COOKIE['module'];
        }

        if (!empty(self::$module)) {
	        return self::$module;
        }

        $range      = self::modules(self::$userType);
	    $platform   = get_platform();
	    $module     = $platform . (self::$userType === 'Admin' ? 'Admin' : '');

	    return $range[$module];
	}

    /**
     * 解析路由
     */
    public static function parseUrl($url_path){
        // PATH_INFO && REWRITE 解析
        $args = explode('/' , $url_path);
        // 过滤无效值
        $args = array_filter($args , function($val , $key){
            if (empty($val)) {
                return false;
            }

            return true;
        } , ARRAY_FILTER_USE_BOTH);

        self::$module = self::getModule();

        if (count($args) === 0) {
            self::$controller   = 'Index';
            self::$action       = 'index';
        }

        if (count($args) === 1) {
            self::$controller   = $args[0];
            self::$action       = 'index';
        }

        if (count($args) === 2) {
            self::$controller = $args[0];
            self::$action     = $args[1];
        }
    }

    /**
     * 解析模块 + 控制器 + 动作
     * $_SERVER['REQUEST_URI'] => http://wwww.test.com/Index/index => /Index/index
     */
	public static function parseMCA(){
	    if (!in_array(self::$urlMode , self::$_urlModeRange)) {
	        // 默认是重写模式
            self::$urlMode = 'REWRITE';
        }

        if (self::$urlMode === 'QUERY_STRING') {
	        // 模式1：查询字符串模式
            self::$module       = self::getModule();
            self::$controller   = isset($_GET['controller']) && !empty($_GET['controller'])     ? $_GET['controller']   : 'Index';
            self::$action       = isset($_GET['action'])     && !empty($_GET['action'])         ? $_GET['action']       : 'index';
        } else if (self::$urlMode === 'PATH_INFO') {
            // 模式2：简化路由模式:
            // 第一种模式：http://test.com/index.php/Pc/Index/index/name/chenxuelong
            // 第二种模式：http://test.com/index.php/Pc/Index/index?name=chenxuelong

            $url  = $_SERVER['REQUEST_URI'];
            $url  = ltrim($url , '\/');
            $qs   = strstr($url , '?') === false ? '' : strstr($url , '?');
            $url  = str_replace($qs , '' , $url);
            $url  = preg_replace('/index.php\/?/' , $url);

            self::parseUrl($url);
        } else {
            // 模式3：重写模式
            // 第一种模式：http://test.com/Pc/Index/index/name/chenxuelong
            // 第二种模式：http://test.com/Pc/Index/index?name=chenxuelong

            $url  = $_SERVER['REQUEST_URI'];
            $qs   = strstr($url , '?') === false ? '' : strstr($url , '?');
            $url  = str_replace($qs , '' , $url);
            $url  = ltrim($url , '\/');

            self::parseUrl($url);
        }
    }
}