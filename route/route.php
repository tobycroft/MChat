<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


Route::any(':api/:controller/:function', ':api/:controller/:function');
Route::any(':controller/:function', 'api/:controller/:function');


//
//// 绑定模块
//Route::any('admin/:controller/:function', 'admin/:controller/:function');

return [
];
