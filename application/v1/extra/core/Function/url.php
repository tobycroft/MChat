<?php



/*
 * 获取当前打开网址的 URL （协议 + 域名 + 端口）
 * @param	Boolean	$is_add_port	是否添加端口号
 * @return  String
 */
function get_url($type = '' , $is_add_port = false){
    $type_range = ['url' , 'protocol' , 'domain' , 'port'];
    $type	  = array_search($type , $type_range) === false ? 'url' : $type;
    $protocol = $_SERVER['SERVER_PROTOCOL'];
    $protocol = explode('/' , $protocol);
    $protocol = strtolower($protocol[0]);
    $domain   = $_SERVER['SERVER_NAME'];
    $port     = $_SERVER['SERVER_PORT'];

    if ($type === 'url') {
        if ($is_add_port) {
            return $protocol . '://' . $domain . ':' . $port . '/';
        }

        return $protocol . '://' . $domain . '/';
    }

    if ($type === 'protocol') {
        return $protocol;
    }

    if ($type === 'domain') {
        return $domain;
    }

    if ($type === 'port') {
        return $port;
    }
}

/*
 * 根据当前文件路径自动生成 url 地址
 * @param	String	  $file			当前文件路径
 * @param   Boolean	  $is_relative  是否生成绝对路径
 * @param   String    $root_dir     根路径
 * @return  String
 */
function generate_url($file = '' , $root_dir = '' , $url = '' , $is_position = false){
    $file		 = realpath($file);

    if ($file === false) {
        return false;
    }

    $file		 = format_path($file);
    $file		 = gbk($file);
    $is_position = is_bool($is_position) ? $is_position : false;
    $root_dir    = empty($root_dir) ? $_SERVER['DOCUMENT_ROOT'] : $root_dir;
    $file	     = mb_substr($file , mb_strlen($root_dir) , mb_strlen($file));
    $file        = utf8($file);

    if ($is_position) {
        if (empty($url)) {
            $url = get_url();
        }

        $file  = $url . $file;
    }

    return $file;
}

/*
 * 获取完整的 URL 路径(web 环境下有效)
 */
function get_full_url($type = 'domain'){
    $type_range = ['domain' , 'ip'];

    $type = in_array($type , $type_range) ? $type : 'domain';

    return $_SERVER['REQUEST_SCHEME'] . '://' . ($type === 'domain' ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR']) . $_SERVER['REQUEST_URI'];
}

// 将数组转换成查询字符串
function to_query_string(array $params = []){
    if (empty($params)) {
        return '';
    }

    $query_string = '?';

    array_walk($params , function($val , $key) use(&$query_string){
        $query_string .= $key . '=' . $val . '&';
    });

    return rtrim($query_string , '&');
}

