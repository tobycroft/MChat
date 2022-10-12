<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace web\api\controller;


/**
 * Description of Game
 * 游戏
 * @author zmh
 */
class Game extends UserBase {

    public $config;
    public $url_addr = 'http://api.wg08.vip/api/';

    protected function _initialize(){
        $this->config = config('game');
        parent::_initialize();

    }


    /**
     * 平台名称
     */
    public function platform(){
        $data = [
            ['name'=>'开元棋牌','code'=>'KY','img'=>'/uploads/game/ky.png'],
            ['name'=>'JS金龙棋牌','code'=>'JS','img'=>'/uploads/game/js.png'],
            ['name'=>'761棋牌','code'=>'761','img'=>'/uploads/game/761.png'],
            ['name'=>'天豪棋牌','code'=>'TH','img'=>'/uploads/game/th.png'],
        ];
        return $this->successJSON($data);

    }

    /**
     * IG无自适应
     */
    public function commonCode(){
        $arr = ['KY','JS','SC','AB','761','TH','WG'];
        return $arr;
    }

    /**
     * 公共加密
    MD5(Token+GameCode+PlayerName+PlayerPassword+TimeSapn+md5密码) 注：PlayerName跟PlayerPassword 在不需要PlayerName的情况下不参与签名，即 MD5(Token+GameCode+TimeSapn+md5密码),GameCode在免转查询余额跟存款取款时设立为空
    Transfer: MD5(Token+GameCode+PlayerName+PlayerPassword+TimeSapn+Money+OrderNo+TranType+md5密码)
    TransferWallet: MD5(Token+PlayerName+PlayerPassword+TimeSapn+Money+OrderNo+TranType+md5密码)


     */
    public function commonSign($data){
        $str = '';
        foreach ($data as $key => $value) {
            $str .=$value;
        }
        return MD5($str.$this->config['apipwd']);
    }

    /**
     * 彩票跳转
     */
    public function lottteyJump(){
        $code = $this->_post('playcode');
        $userM = new \addons\member\model\MemberAccountModel();
        $user_info = $userM->getDetail($this->user_id, "phone,is_game");
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'PlayerName'=>$user_info['phone'],
            'PlayerPassword'=>$this->config['password'],
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $url = $this->url_addr.'GamePlayer';
        $ret =  http_json($url,$data);
        if($ret['success']&&$ret['data']['StatusCdoe']==1){
            return $this->successJSON($ret['data']['PayUrl']);
        }else{
            return $this->failJSON('游戏登录失败');
        }
    }

    /**
     * 游戏跳转
     */
    public function gameJump(){
        $code = $this->_post('code');
        $arr = $this->commonCode();
        if(!in_array($code, $arr)) return $this->failJSON('错误的号码');
        $userM = new \addons\member\model\MemberAccountModel();
        $user_info = $userM->getDetail($this->user_id, "phone,is_game");
        $this->gameRegister($user_info,$code);
        // if($user_info['is_game']){
        //     $this->gameRegister($user_info,$code);
        // }else{
        //     $this->gameRegister($user_info,$code);
        // }
    }

    /**
     * 注册
     */
    public function gameRegister($user_info,$code){
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'PlayerName'=>$user_info['phone'],
            'PlayerPassword'=>$this->config['password'],
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $url = $this->url_addr.'Register';
        $res = http_json($url,$data);
        if($res['success']&&$res['data']['StatusCdoe']==1){
            $this->gameLogin($user_info,$code);
        }else{
            return $this->failJSON('游戏注册用户失败');
        }
    }



    /**
     * 登录
     */
    public function gameLogin($user_info,$code){
        // $this->userBalance($user_info,$code);
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'PlayerName'=>$user_info['phone'],
            'PlayerPassword'=>$this->config['password'],
            'TimeSapn'=>date('YmdHis'),
        ];
        $gamename = $this->_post('gamename');
        $data['Sign'] = $this->commonSign($data);
        $data['GameName'] = $gamename;
        $data['UserIP'] = getRealIpAddr();
        $data['DeviceType'] = 1;
        $data['Additional'] = null;
        $url = $this->url_addr.'Login';
        // return $this->successJSON($data);
        $ret =  http_json($url,$data);
        if($ret['success']&&$ret['data']['StatusCdoe']==1){
            $url = $ret['data']['PayUrl'];
            // if($code=='KY') {
            //     $pos = strripos($url,'&gameId=');
            //     $url = $pos? substr($url,0,$pos).'&gameId='.$gamename : $ret['data']['PayUrl'];
            // }elseif($code=='SC') {
            //     $pos = strripos($url,'&lotteryId=');
            //     $url = $pos? substr($url,0,$pos).'&lotteryId='.$gamename : $ret['data']['PayUrl'].'&lotteryId='.$gamename;
            //     if(!$gamename) $url = $ret['data']['PayUrl'];
            // }else{

            // }
            return $this->successJSON($url);
        }else{
            return $this->failJSON($ret['data']['Message']);
        }
    }

    /**
     * 获取会员余额
     */
    public function userBalance(){
        $code = $this->_post('code');
        $arr = $this->commonCode();
        if(!in_array($code, $arr)) return $this->failJSON('错误的号码');
        $userM = new \addons\member\model\MemberAccountModel();
        $user_info = $userM->getDetail($this->user_id, "phone,is_game");
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'PlayerName'=>$user_info['phone'],
            'PlayerPassword'=>$this->config['password'],
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $url = $this->url_addr.'QueryBalance';
        $ret = http_json($url,$data);
        if($ret['success']&&$ret['data']['StatusCdoe']==1){
            $balanceM = new \addons\member\model\Balance();
            $BalanceConf = new \addons\config\model\BalanceConf();
            $type = $BalanceConf->where('code',$code)->value('type');
            $map['type'] = $type;
            $map['user_id'] = $this->user_id;
            $_data['amount'] = $ret['data']['Balance'];
            $res  = $balanceM->where($map)->update($_data);
            return $this->successJSON($ret['data']);
        }else{
            return $this->failJSON($ret['data']['Message']);
        }
    }

    /**
     * 获取游戏列表
     */
    public function QueryGameList(){
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>'AG',
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $url = $this->url_addr.'QueryGameList';
        $res = http_json($url,$data);
    }

    /**
     * 金额转入
     */
    public function AmountTrans(){
        // return $this->failData('不支持该类型转账');
        $user_id = $this->user_id;
        $out_code = $this->_post('out_code');
        $in_code = $this->_post('in_code');
        if($out_code==$in_code){
            return $this->failJSON('不支持该类型转账');
        }
        if(!($out_code==0||$in_code==0)){
            return $this->failJSON('不支持该类型转账');
        }
        if($out_code!='0'){
            $code = $out_code;
            $changetype = 0;
            $TranType = 'OUT';
        }else{
            $code = $in_code;
            $changetype = 1;
            $TranType = 'IN';
        }
        $amount = $this->_post('amount');
        $arr = $this->commonCode();
        // if(!in_array($code, $arr)) return $this->failJSON('错误的号码');
        $balanceM = new \addons\member\model\Balance();
        $recordM = new \addons\member\model\TradingRecord();
        $coin_type = 1;
        if(!$changetype){
            $MerchantBlance = $this->MerchantBlance($code);
            if($amount>$MerchantBlance){
                return $this->failJSON('转出账户余额不足');
            }
        }else{
            $member_amount = $balanceM->getBalanceByType($user_id,$coin_type);
            if($member_amount['amount']<$amount){
                return $this->failJSON('当前余额不足，无法转换');
            }
        }
        $balanceM->startTrans();
        if($TranType=='IN'){
            $res = $balanceM->updateBalance($user_id,$coin_type,$amount);
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('转换金额失败');
            }
            $bet_type = 200;//余额转换到游戏币
            $res = $recordM->addRecord($user_id, $amount, $res['before_amount'], $res['amount'], $coin_type,$bet_type,0, $user_id,  '余额转换');
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('添加转换记录失败');
            }
            if($code =='KY'){
                $coin_type = 3;
            }elseif($code =='SC'){
                $coin_type = 2;
            }
            $res = $balanceM->updateBalance($user_id,$coin_type,$amount,1);
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('更新金额失败');
            }
            $bet_type = 200;//余额转换到游戏币
            $res = $recordM->addRecord($user_id, $amount, $res['before_amount'], $res['amount'], $coin_type,$bet_type, 1, $user_id,  '余额转换');
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('添加转换记录失败');
            }
        }else{
            $res = $balanceM->updateBalance($user_id,$coin_type,$amount,1);
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('转换金额失败');
            }
            $bet_type = 200;//余额转换到游戏币
            $res = $recordM->addRecord($user_id, $amount, $res['before_amount'], $res['amount'], $coin_type,$bet_type,1, $user_id,  '余额转换');
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('添加转换记录失败');
            }
            if($code =='KY'){
                $coin_type = 3;
            }elseif($code =='SC'){
                $coin_type = 2;
            }
            $res = $balanceM->updateBalance($user_id,$coin_type,$amount);
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('更新金额失败');
            }
            $bet_type = 200;//余额转换到游戏币
            $res = $recordM->addRecord($user_id, $amount, $res['before_amount'], $res['amount'], $coin_type,$bet_type, 0, $user_id,  '余额转换');
            if(!$res){
                $balanceM->rollback();
                return $this->failJSON('添加转换记录失败');
            }
        }

        $userM = new \addons\member\model\MemberAccountModel();
        $user_info = $userM->getDetail($user_id, "phone,is_game");
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'PlayerName'=>$user_info['phone'],
            'PlayerPassword'=>$this->config['password'],
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $data['TranType'] = $TranType;
        $data['Money'] = $amount;
        $data['OrderNo'] = create_uuid();
        $url = $this->url_addr.'Transfer';
        $ret = http_json($url,$data);
        if($ret['success']&&$ret['data']['StatusCdoe']==1){
            $balanceM->commit();
            return $this->successJSON('转账成功');
        }else{
            $balanceM->rollback();
            return $this->failJSON($ret['data']['Message']);
        }
    }

    protected function MerchantBlance($code){
        $data = [
            'Token'=>$this->config['apitoken'],
            'GameCode'=>$code,
            'TimeSapn'=>date('YmdHis'),
        ];
        $data['Sign'] = $this->commonSign($data);
        $url = $this->url_addr.'MerchantBlance';
        $ret = http_json($url,$data);
        if($ret['success']&&$ret['data']['StatusCdoe']==1){
            return $ret['data']['Balance'];
        }else{
            return $this->failJSON($ret['data']['Message']);
        }
    }

}
