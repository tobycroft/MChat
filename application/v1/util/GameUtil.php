<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/9/2
 * Time: 15:29
 */

namespace app\v1\util;

class GameUtil
{
    public static $token = '4f5aad7059f94b67884c4a8330c510d6';

    public static $password = '4fabaca5';

    public static $api = 'http://api.wg08.vip/api/';

    /**
     * 真人-code  AG
    棋牌-code  NW
    捕鱼-code  AG&game=type=6
    电竞-code  AVIA
    电子-code  api_name=AG
    彩票-code  VR
     */
    /**
     *  * 7-捕鱼来了3D -code=FG ganmename= fish_3D
     * 8-魔法王者- code=FG gamename= fish_mfwz
     * 9-西游-code=FG gamename= xy
     * 10-扫雷红包 -code=FG gamename= slhb
     * 10-扫雷红包 -code=FG gamename= hlhb
     * 11-雷霆战警-code=FG gamename= fish_zj
     * 12-LUCKY5-code=FG gamegame= lucky
     */
    public static $gameCodeRange = [
        // AG视讯
        1 => 'AG' ,
        // EBET 视讯
        2 => 'EBET' ,
        // 开元棋牌
        3 => 'KY' ,
        // 双赢棋牌
        4 => 'SW' ,
        // 皇冠体育
        5 => 'GJ' ,
    ];

    // 注册
    public static function register($code_key , $username , $password)
    {
        $timestamp = date('YmdHis' , time());
        $sign = self::generateSign($code_key , $username , $password , $timestamp);
        $url = self::$api . 'Register';
        $data = [
            'Token'         => self::$token ,
            'GameCode'      => self::$gameCodeRange[$code_key] ,
            'PlayerName'    => $username ,
            'PlayerPassword' => $password ,
            'TimeSapn'      => $timestamp ,
            'Sign'          =>  $sign ,
        ];
        $res = self::post($url , $data);
        if (empty($res)) {
            return [
                'code' => 500 ,
                'data' => 'curl 发送请求失败，请检查本地网络是否通畅' ,
            ];
        }
        $res = json_decode($res , true);
        if ($res['StatusCdoe'] != 1) {
            return [
                'code' => 500 ,
                'data' => '远程接口返回错误信息：' . $res['Message'] ,
            ];
        }
        return [
            'code' => 0 ,
            'data' => $res['Message']
        ];
    }

    // 生成签名
    public static function generateSign($code_key , $username , $password , $timestamp)
    {
        $sign_str = self::$token;
        $sign_str .= self::$gameCodeRange[$code_key] ?? '';
        $sign_str .= $username;
        $sign_str .= $password;
        $sign_str .= $timestamp;
        $sign_str .= self::$password;
        return md5($sign_str);
    }

    // 生成签名
    public static function generateSignForTransferWallet($username , $password , $timestamp , $money , $order_no , $trans_type)
    {
        $sign_str = self::$token;
        $sign_str .= $username;
        $sign_str .= $password;
        $sign_str .= $timestamp;
        $sign_str .= $money;
        $sign_str .= $order_no;
        $sign_str .= $trans_type;
        $sign_str .= self::$password;
        return md5($sign_str);
    }

    public static function login($code_key , $username , $password)
    {
        $timestamp = date('YmdHis' , time());
        $sign = self::generateSign($code_key , $username , $password , $timestamp);
        $url = self::$api . 'Login';
        switch ($code_key)
        {
            case 1:
                $game_name = '';
                break;
            case 2:
                $game_name = self::$gameCodeRange[$code_key];
                break;
            case 3:
                $game_name = 6;
                break;
            case 4:
                $game_name = self::$gameCodeRange[$code_key];
                break;
            case 5:
                $game_name = '8';
                break;
            case 6:
                $game_name = '';
                break;
            case 7:
                $game_name = 'fish_3D';
                break;
            case 8:
                $game_name = 'fish_mfwz';
                break;
            case 9:
                $game_name = 'xy';
                break;
            case 10:
//                $game_name = 'slhb';
                $game_name = 'hlhb';
                break;
            case 11:
                $game_name = 'fish_zj';
                break;
            case 12:
                $game_name = 'lucky';
                break;
            default:
                $game_name = self::$gameCodeRange[$code_key];
        }
        $data = [
            'Token'         => self::$token ,
            'GameCode'      => self::$gameCodeRange[$code_key] ,
            'PlayerName'    => $username ,
            'PlayerPassword' => $password ,
            'TimeSapn'      => $timestamp ,
            'Sign'          =>  $sign ,
            'UserIP'        =>  request()->ip() ,
            'DeviceType'        =>  1 ,
            'GameName'      => $game_name
        ];
//        print_r($data);
//        var_dump(json_encode($data));
        $res = self::post($url , $data);
        if (empty($res)) {
            return self::response('curl 发送请求失败，请检查本地网络是否通畅' , 500);
        }
        $res = json_decode($res , true);
        if ($res['StatusCdoe'] != 1) {
            return self::response('远程接口返回错误信息：' . $res['Message'] , 500);
        }
        return self::response($res['PayUrl'] , 0);
    }

    // 查询商户余额
    public static function balance($username , $password)
    {
        $timestamp = date('YmdHis' , time());
        $sign = self::generateSign(0 , $username , $password , $timestamp);
        $url = self::$api . 'QueryWalletBalance';
        $data = [
            'Token'         => self::$token ,
            'PlayerName'    => $username ,
            'PlayerPassword' => $password ,
            'TimeSapn'      => $timestamp ,
            'Sign'          =>  $sign ,
        ];
        $res = self::post($url , $data);
        if (empty($res)) {
            return self::response('curl 发送请求失败，请检查本地网络是否通畅' , 500);
        }
        $res = json_decode($res , true);
        if ($res['StatusCdoe'] != 1) {
            return self::response('远程接口返回错误信息：' . $res['Message'] , 500);
        }
        return self::response($res['Balance'] , 0);
    }

    // 转账
    public static function transfer($username , $password , $trans_type , $money , $order_no)
    {
        $timestamp = date('YmdHis' , time());
        $sign = self::generateSignForTransferWallet($username , $password , $timestamp , $money , $order_no , $trans_type);
        $url = self::$api . 'TransferWallet';
        $data = [
            'Token'         => self::$token ,
            'PlayerName'    => $username ,
            'PlayerPassword' => $password ,
            'TimeSapn'      => $timestamp ,
            'Sign'          =>  $sign ,
            'TranType'          =>  $trans_type ,
            'Money'          =>  $money ,
            'OrderNo'          =>  $order_no ,
            'JoinGame'          =>  false ,
        ];
//        print_r($data);
//        var_dump(json_encode($data));
        $res = self::post($url , $data);
        if (empty($res)) {
            return self::response('curl 发送请求失败，请检查本地网络是否通畅' , 500);
        }
        $res = json_decode($res , true);
        if ($res['StatusCdoe'] != 1) {
            return self::response('远程接口返回错误信息：' . $res['Message'] , 500);
        }
        return self::response($res['Message'] , 0);
    }

    // 响应
    public static function response($data , $code = 0)
    {
        return [
            'code' => $code ,
            'data' => $data ,
        ];
    }

    public static function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = 'B'.substr($str,0,10) . 'AA';
        $uuid .= substr($str,10,3) . 'F';
        $uuid .= substr($str,13,5) . 'FF';
        $uuid .= substr($str,18,2) . 'F';
        $uuid .= substr($str,20,5);
        return $prefix . $uuid;
    }


    public static function create_guid(){
        $microTime = microtime();
        list($a_dec, $a_sec) = explode(" ", $microTime);
        $dec_hex = dechex($a_dec* 1000000);
        $sec_hex = dechex($a_sec);
        self::ensure_length($dec_hex, 5);
        self::ensure_length($sec_hex, 6);
        $guid = "";
        $guid .= $dec_hex;
        $guid .= self::create_guid_section(3);
        $guid .= '-';
        $guid .= self::create_guid_section(4);
        $guid .= '-';
        $guid .= self::create_guid_section(4);
        $guid .= '-';
        $guid .= self::create_guid_section(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= self::create_guid_section(6);
        return $guid;
    }

    public static function ensure_length(&$string, $length){
        $strlen = strlen($string);
        if($strlen < $length) {
            $string = str_pad($string,$length,"0");
        } else if($strlen > $length)
        {
            $string = substr($string, 0, $length);
        }
    }

    public static function create_guid_section($characters){
        $return = "";
        for($i=0; $i<$characters; $i++)
        {
            $return .= dechex(mt_rand(0,15));
        }
        return $return;
    }

    public static function post($url , $data)
    {
        $res = curl_init();
        curl_setopt_array($res , [
            CURLOPT_RETURNTRANSFER => true ,
            CURLOPT_HEADER => false ,
            CURLOPT_URL => $url ,
            // 要发送的请求头
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ] ,
            CURLOPT_POST => true ,
            CURLOPT_POSTFIELDS => json_encode($data) ,
            // user-agent 必须携带！
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36' ,
            // 要携带的 cookie，不知道能够坚持多久？？
//            CURLOPT_COOKIE => $option['cookie'] ,
            CURLOPT_SSL_VERIFYPEER => false ,
            CURLOPT_FOLLOWLOCATION  => true ,
            CURLOPT_MAXREDIRS  => 3 ,
            /*
             * todo 支持代理
            // 启用 http 代理隧道
            CURLOPT_HTTPPROXYTUNNEL => true ,
            CURLOPT_PROXYTYPE   => $cur['type'] ,
            CURLOPT_PROXY       => $cur['ip'] ,
            CURLOPT_PROXYPORT   => $cur['port'] ,
            */
        ]);
        $str = curl_exec($res);
        curl_close($res);
        return $str;
    }
}