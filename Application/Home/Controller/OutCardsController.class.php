<?php
namespace Home\Controller;
use Think\Controller;
use Org\Util\Des;
use Org\Util\YjDes;
use Think\Model;

class OutCardsController extends CoinController {
    //创建账户接口
    public function createAccount(){
        $data=getPostJson();
        
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        $this->recordData(json_encode($data));
        //$data=json_decode('{"mobile":"18613890807","key":"cff3052d8ca716edaeeb58d4c71c29ea"}',1);
        $mobile=trim($data['mobile']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$mobile);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(!preg_match("/1[345789]{1}\d{9}$/",$mobile)){
            returnMsg(array('status'=>'01','codemsg'=>'手机号为空或者格式不对'));
        }
        //$mobile='15890620404';
        $bool=$this->checkMobile($mobile);
        //手机号没被注册时用手机号注册会员；若存在，已绑卡的直接返回会员编号，若没绑卡进行绑卡操作
      
        //$panterid='00000853';//测试环境
        $panterid='00002233';//正式环境
        if($bool==true){
            $customid=$bool;
            $map1=array('cu.customid'=>$customid,'cardkind'=>'6886');
            $card=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                ->join('cards c on cc.cid=c.customid')->where($map1)->field('c.cardno')->select();
            if(count($card)>0){
                returnMsg(array('status'=>'1','codemsg'=>'账户创建成功','customid'=>encode($customid),'cardno'=>$card[0]['cardno']));
            }
            //returnMsg(array('status'=>'03','codemsg'=>'此号码已被注册'));
        }else{
            $customArr=array('mobile'=>$mobile,'panterid'=>$panterid,'pname'=>'');
            $customid=$this->createCustoms($customArr);
            if($customid==false){
                returnMsg(array('status'=>'04','codemsg'=>'信息录入失败'));
            }
            $is_push=1;
        }
        $userid='0000000000000000';
        $getCard=$this->getCard(1,$panterid);
        $this->model->startTrans();
        /*if($getCard==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡池数量不足，请联系至尊'));
        }*/
        $bool=$this->openCard($getCard,$customid,0,$panterid,1,'',$userid,1);
        if($bool==true){
            $this->recordData(json_encode(array('is_push'=>$is_push)));
            if($is_push==1){
                $data1 = array(
                    'appid'=>'SOON-ZZUN-0001',
                    'mobilephone'=>$mobile,
                    'password'=>substr($mobile,-6),
                    'customid'=>encode($customid)
                );
                $de = new YjDes();
                $sign = $de->encrypt($data1);
                $data1 = json_encode($data1,JSON_FORCE_OBJECT);
                $this->recordData($data1.$sign);
                $url = C('ecardIssue').'/app/user/registerex.user';
                //$url = 'http://ysco2o.yijiahn.com/jyo2o_web/app/user/registerex.user';
                $result = $this->curlPost($url,$data1,$sign);
                $resultinfo = json_decode($result,true);
                $this->recordData($data1.$result);

                if($resultinfo['code']==100){
                    $sql="update customs set idflag=1 where customid='{$customid}'";
                    $this->model->execute($sql);
                }
            }
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'账户创建成功','customid'=>encode($customid),'cardno'=>$getCard[0]));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'开卡失败'));
        }
    }

    //充值接口
    public function customRecharge(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
       
        $customid=trim($data['customid']);
        $amount=trim($data['amount']);
        $key=trim($data['key']);
        $sourceRechargeId=trim($data['sourceRechargeId']);
        //$sourceCode='1001';$amount='9000';$customid='00002479';
        $checkKey=md5($this->keycode.$customid.$amount.$sourceRechargeId);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        if(empty($sourceRechargeId)){
            returnMsg(array('status'=>'06','codemsg'=>'缺失充值编号'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'充值金额格式错误'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        //$panterid='00000853';
        $panterid='00002233';//正式环境
        $userid='0000000000000000';
        if($bool==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法会员编号'));
        }
        $map=array('description'=>array('like','%'.'out_'.$sourceRechargeId.'%'));
        $cpl=M('card_purchase_logs')->where($map)->find();
        if($cpl!=false){
            returnMsg(array('status'=>'09','codemsg'=>'此充值号已经充值，请勿重复提交'));
        }
        $ownCards=$this->getOwnCards($customid);
        $ownCardsNum=count($ownCards);
        //$ownCardsNum=$this->customCardNum($customid);
        $customAccount=$this->accountQuery($customid,'00');
        $usableRecharge=5000*$ownCardsNum-$customAccount;
        $this->model->startTrans();
        if($usableRecharge<$amount){
            if($usableRecharge>0){
                $rechargeBool=$this->recharge($ownCards,$customid,$usableRecharge,'out_'.$sourceRechargeId,$userid);
            }else{
                $rechargeBool=true;
            }
            $openRechargeAmount=$amount-$usableRecharge;
            $openNum=$this->getOpenNum($openRechargeAmount);
            $getCards=$this->getCard($openNum,$panterid);
            //echo $openNum.'<br/>';
            //print_r($getCards);
            //echo $openRechargeAmount;exit;
            if(empty($getCards)){
                returnMsg(array('status'=>'08','codemsg'=>'卡池数量不足'));
            }
            $openBool=$this->openCard($getCards,$customid,$openRechargeAmount,$panterid,2,'out_'.$sourceRechargeId,$userid);
            if($rechargeBool==true&&$openBool==true){
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功','addamount'=>floatval($amount)));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'充值失败'));
            }
        }else{
            if(empty($ownCards)){
                returnMsg(array('status'=>'07','codemsg'=>'用户无关联至尊卡'));
            }
            $rechargeBool=$this->recharge($ownCards,$customid,$amount,'out_'.$sourceRechargeId,$userid);
            if($rechargeBool==true){
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功','addamount'=>floatval($amount)));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'充值失败'));
            }
        }
    }

    //消费扣款接口
    public function consume(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $amount=trim($data['amount']);
        $payPwd=trim($data['payPwd']);
        $key=trim($data['key']);
        $orderid=trim($data['orderId']);
        $panterid=trim($data['panterid']);
        $termno=trim($data['termno']);
        $markcode=trim($data['markcode']);
        $coinAmount=trim($data['coinAmount']);
        $checkKey=md5($this->keycode.$customid.$amount.$orderid.$payPwd.$coinAmount.$termno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'014','codemsg'=>'建业通宝格式有误'));
        }
        if($amount==0&&$coinAmount==0){
            returnMsg(array('status'=>'015','codemsg'=>'消费数据有误'));
        }
        if(empty($orderid)){
            returnMsg(array('status'=>'05','codemsg'=>'订单编号缺失'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法会员编号'));
        }
        $paypwd=$this->decodePwd($payPwd);
        if($paypwd==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法密码传入'));
        }
        if(empty($markcode)){
            $pwdBool=$this->checkPayPwd($customid,$paypwd);
            if($pwdBool==='01'){
                returnMsg(array('status'=>'08','codemsg'=>'支付密码错误'));
            }elseif($pwdBool==='02'){
                returnMsg(array('status'=>'09','codemsg'=>'当天密码错误次数超过三次'));
            }
        }else{
            $checkMarkcode=md5($checkKey.'zzconsue001');
            if($checkMarkcode!=$markcode){
                returnMsg(array('status'=>'013','codemsg'=>'标识码错误'));
            }
        }

        $tradeLogs=M('trade_logs');
        $where=array('customid'=>$customid,'placeddate'=>date('Ymd'));
        $trade_logs_list=$tradeLogs->where($where)->order('datetimes desc')->select();
        $date=time();
        if($trade_logs_list!=false){
            if(intval($date-$trade_logs_list[0]['datetimes'])<=10){
                returnMsg(array('status'=>'016','codemsg'=>'重复支付订单'));
            }
        }
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $sql="insert into trade_logs (customid,CODECUSTOMID,placeddate,placedtime,datetimes,orderid) values('{$customid}','".encode($customid)."','{$currentDate}','{$currentTime}','{$date}','{$orderid}')";
        $tradeLogs->execute($sql);
        unset($sql);
        $balanceAccount=$this->accountQuery($customid,'00');

        if($balanceAccount<$amount){
            returnMsg(array('status'=>'010','codemsg'=>'账户金额不足'));
        }
        //$coinAccount=$this->accountQuery($customid,'01');
        $coinAccount=$this->coinQuery($customid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'010','codemsg'=>'建业通宝余额不足'));
        }

        $this->model->startTrans();
        $balanceConsumeIf=$coinConsumeIf=false;

        //余额消费，金额为0不执行
        if($amount>0){
            $map=array('eorderid'=>'out_'.$orderid,'tradetype'=>'00');
            $balanceConsume=M('trade_wastebooks')->where($map)->sum('tradeamount');
            if($balanceConsume>0){
                returnMsg(array('status'=>'011','codemsg'=>'此订单已进行余额支付，请勿重复提交'));
            }
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'out_'.$orderid,
                'panterid'=>$panterid,'type'=>'00','amount'=>$amount,'termno'=>$termno,
                'walletConsumeAmount'=>0);
            $balanceConsumeIf=$this->consumeExe($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }

        if($coinAmount>0){
            $map=array('eorderid'=>'out_'.$orderid,'tradetype'=>'00');
            $balanceConsume=M('trade_wastebooks')->where($map)->sum('tradepoint');
            //echo M('trade_wastebooks')->getLastSql();exit;
            if($balanceConsume>0){
                returnMsg(array('status'=>'017','codemsg'=>'此订单已进行建业通宝支付，请勿重复提交'));
            }
            $coinConsumeArr=array('customid'=>$customid,'orderid'=>'out_'.$orderid,
                'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount,'termno'=>$termno);
//            $coinConsumeIf=$this->coinExe($coinConsumeArr);
	        $coinConsumeIf=$this->consumeCoin($coinConsumeArr);
        }else{
            $coinConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$coinConsumeIf==true){
            $this->model->commit();
            $hbinfo=$this->getHbGiftAmount($amount,$panterid,$orderid,$customid);
            returnMsg(array('status'=>'1','codemsg'=>'消费成功','hginfo'=>$hbinfo));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'012','codemsg'=>'消费扣款失败'));
        }
    }

    //查询账户信息接口(通过用户编号)
    public function getAccount(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        //$customid=trim('MDAwMDAzNTYO0O0O ');
        $key=trim($data['key']);

        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        //$customid='00194507';
        //账户信息
        $accountInfo=$this->accountInfo($customid);

        $map['customid']=$customid;
        $custom=$this->customs->where($map)->find();
        $hasPaypwd=empty($custom['paypwd'])?0:1;
        //账户绑卡信息
        $bindCards=$this->getBindCards($customid);
        if($bindCards==false){
            $bindList='';
        }else{
            $bindList=$bindCards;
        }
        returnMsg(array('status'=>'1','balance'=>floatval($accountInfo['balance']),
                'jycoin'=>floatval($accountInfo['jycoin'])
                )
        );
    }

    //设置支付密码
    public function setPayPwd(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $paypwd=trim($data['paypwd']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        if($paypwd==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法密码传入'));
        }
        $checkKey=md5($this->keycode.$customid.$paypwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'05','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效会员'));
        }
        $paypwd=$this->decodePwd($paypwd);
        $map=array('customid'=>$customid);
        //$data=array('paypwd'=>md5($paypwd));
        $data=array('paypwd'=>$paypwd);
        $customIf=$this->customs->where($map)->save($data);
        if($customIf==false){
            returnMsg(array('status'=>'04','codemsg'=>'设置失败'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'设置成功'));
        }
    }

    //校验支付密码
    public function examPayPwd(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $paypwd=trim($data['paypwd']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        $checkKey=md5($this->keycode.$customid.$paypwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'04','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效会员'));
        }
        $paypwd=$this->decodePwd($paypwd);
        if($paypwd==false){
            returnMsg(array('status'=>'05','codemsg'=>'非法密码传入'));
        }
        $pwdBool=$this->checkPayPwd($customid,$paypwd);
        if($pwdBool==='01'){
            returnMsg(array('status'=>'03','codemsg'=>'支付密码错误'));
        }elseif($pwdBool==='02'){
            returnMsg(array('status'=>'07','codemsg'=>'当天密码错误次数超过三次'));
        }elseif($pwdBool===1){
            returnMsg(array('status'=>'1','codemsg'=>'校验通过'));
        }
    }

    //校验是否支付密码
    public function hasPayPwd(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效会员'));
        }
        $map['customid']=$customid;
        $custom=$this->customs->where($map)->find();
        if($custom['paypwd']==''){
            returnMsg(array('status'=>'04','codemsg'=>'未设置支付密码'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'支付密码已设置'));
        }
    }

    //修改支付密码
    public function editPayPwd(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $oldpwd=trim($data['oldpwd']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        $checkKey=md5($this->keycode.$customid.$oldpwd.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'06','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效会员'));
        }
        $oldpwd=$this->decodePwd($oldpwd);
        $newpwd=$this->decodePwd($newpwd);
        if($oldpwd==false||$newpwd==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法密码传入'));
        }
        $pwdBool=$this->checkPayPwd($customid,$oldpwd);
        if($pwdBool==='01'){
            returnMsg(array('status'=>'03','codemsg'=>'旧支付密码错误'));
        }elseif($pwdBool==='02'){
            returnMsg(array('status'=>'08','codemsg'=>'旧密码错误次数超过三次'));
        }
//        if(!preg_match("/\d{6,}$/",$newpwd)){
//            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
//        }
        $customMap=array('customid'=>$customid);
        //$pwdData=array('paypwd'=>md5($newpwd));
        $pwdData=array('paypwd'=>$newpwd);
        if($this->customs->where($customMap)->save($pwdData)){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'修改失败'));
        }
    }
    //重置支付密码
    public function resetPayPwd(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        $checkKey=md5($this->keycode.$customid.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'05','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效会员'));
        }
        $newpwd=$this->decodePwd($newpwd);
        if($newpwd==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法密码传入'));
        }
//        if(!preg_match("/\d{6,}$/",$newpwd)){
//            returnMsg(array('status'=>'03','codemsg'=>'新密码格式错误'));
//        }
        $customMap=array('customid'=>$customid);
        $pwdData=array('paypwd'=>$newpwd);
        if($this->customs->where($customMap)->save($pwdData)){
            returnMsg(array('status'=>'1','codemsg'=>'重置成功'));
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'重置失败'));
        }
    }

    public function getHbGiftAmount($amount,$panterid,$orderid,$customid){
        if($amount<10) return 0;
        $hbConig=M('hbrules')->where(array('panterid'=>$panterid,'gtype'=>2,'is_on'=>1,'enddate'=>array('egt',date('Ymd'))))->find();
        if($hbConig==false) return false;
        $giftConsumeAmount=round($amount*$hbConig['rate']/100,2);
        $hgid=$this->getFieldNextNumber('hbid');
        $sql="insert into hb_logs values('{$hgid}','{$customid}','{$giftConsumeAmount}','{$panterid}','{$orderid}',1,0)";
        $this->model->execute($sql);
        return array('hbid'=>$hgid,'amount'=>floatval($giftConsumeAmount));
    }


    public function giftHb(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        //$data=json_decode('{"customid":"MDAxOTQ1MDcO0O0O","hbid":"10","key":"b6f993a973124473d86ea0c3bba67376"}',1);
        $customid=trim($data['customid']);
        $hbid=trim($data['hbid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid.$hbid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        $hbinfo=M('hb_logs')->where(array('hgid'=>$hbid))->find();
        if($hbinfo==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效红包'));
        }
        if($hbinfo['status']==1){
            returnMsg(array('status'=>'05','codemsg'=>'红包已赠送'));
        }
        $amount=$hbinfo['amount'];
        $userid='0000000000000000';
        $ownCards=$this->getOwnCards(decode($customid));
        $rechargeBool=$this->recharge($ownCards,decode($customid),$amount,'zzhb_'.$hbid,$userid);
        if($rechargeBool==true){
            $sql="update hb_logs set status=1 where hgid='{$hbid}'";
            if($this->model->execute($sql)){
                returnMsg(array('status'=>'1','codemsg'=>'赠送成功','amount'=>floatval($amount)));
            }else{
                returnMsg(array('status'=>'04','codemsg'=>'赠送失败'));
            }
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'赠送失败'));
        }
    }

    public function returnGoods(){
        $data=getPostJson();
        $this->recordData(json_encode($data));

        //$data=json_decode('{"amount":"0.01","key":"4a910d1bef74c346a5f411691268f3be","tradeid":"802520170719222436"}',1);
        $tradeid=trim($data['tradeid']);
        $amount=trim($data['amount']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$tradeid.$amount);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $trade=M('trade_wastebooks');
        $tradeInfo=$trade->where(array('tradeid'=>$tradeid))->find();
        if($tradeInfo==false){
            returnMsg(array('status'=>'02','codemsg'=>'交易不存在'));
        }
        if($tradeInfo['tradeamount']<$amount){
            returnMsg(array('status'=>'03','codemsg'=>'订单金额小于退款金额'));
        }
        $tradeInfo1=$trade->where(array('pretradeid'=>$tradeid))->find();
        if($tradeInfo1!=false){
            returnMsg(array('status'=>'05','codemsg'=>'订单已退款'));
        }
        $tradeid=substr($tradeInfo['cardno'],15,4).date('YmdHis',time());
        $pretradeid=trim($tradeInfo['tradeid']);
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,pretradeid,tradememo)";
        $tradeSql.="values('00000000','00000000','{$tradeInfo['panterid']}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'-{$amount}',0,'{$tradeInfo['customid']}','{$tradeInfo['cardno']}','{$placedtime}','07'";
        $tradeSql.=",'abcdefgh','0','{$pretradeid}','订单退款')";

        $this->model->startTrans();

        //echo $tradeSql;exit;
        $tradeIf=$this->model->execute($tradeSql);


        $cardInfo=$this->model->table('cards')->where(array('cardno'=>$tradeInfo['cardno']))->find();

        $accountSql="update account set amount=amount+{$amount} where customid='{$cardInfo['customid']}' and type='00'";


        $accountIf=$this->model->execute($accountSql);
        if($accountIf==true&&$tradeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'退款成功','amount'=>$amount));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'04','codemsg'=>'退货失败'));
        }

    }

    private function curlPost($url,$data,$sign){
        if(!$url){
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type:application/json',"sign:{$sign}"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);
        $output = curl_exec($ch);
        if($output == false){
            return curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }

    public function getRechargeList(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'用户编号缺失'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
        }
        //$customid='00000016';
        $where=array('cu.customid'=>$customid);
        $where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        $cards=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($where)->select();
        $cardArr=$this->serializeArr($cards,'cardno');
        $where1=array('cpl.cardno'=>array('in',$cardArr),'cpl.flag'=>1,'cpl.amount'=>array('gt',0));
        $rechargeList=M('card_purchase_logs')->alias('cpl')->field('cpl.placeddate,cpl.amount,cpl.cardno,cpl.placedtime,cpl.card_purchaseid')
            ->where($where1)->order('cpl.placeddate desc,cpl.placedtime desc')->select();
        $list=array();
        foreach($rechargeList as $key=>$val){
            $list[$key]['cardno']=$val['cardno'];
            $list[$key]['amount']=floatval($val['amount']);
            $list[$key]['placeddate']=$val['placeddate'];
            $list[$key]['placedtime']=$val['placedtime'];
            $list[$key]['purchaseid']=$val['card_purchaseid'];
        }
        if($list==false){
            returnMsg(array('status'=>'03','codemsg'=>'暂无充值记录'));
        }else{
            returnMsg(array('status'=>'1','list'=>$list));
        }
    }
}
?>
