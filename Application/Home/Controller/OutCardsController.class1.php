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
        
        $this->recordData($data);
        $mobile=trim($data['mobile']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$mobile);
        
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(!preg_match("/1[345789]{1}\d{9}$/",$mobile)){
            returnMsg(array('status'=>'01','codemsg'=>'手机号为空或者格式不对'));
        }
        $bool=$this->checkMobile($mobile);
        //手机号没被注册时用手机号注册会员；若存在，已绑卡的直接返回会员编号，若没绑卡进行绑卡操作
      
        $panterid='00000853';
        if($bool==true){
            $customid=$bool;
            $map1=array('cu.customid'=>$customid,'cardkind'=>'6886');
            $cardCount=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                ->join('cards c on cc.cid=c.customid')->where($map1)->field('c.cardno')->count();
            if($cardCount>0){
                returnMsg(array('status'=>'1','codemsg'=>'账户创建成功','customid'=>encode($customid)));
            }
            //returnMsg(array('status'=>'03','codemsg'=>'此号码已被注册'));
        }else{
            $customArr=array('mobile'=>$mobile,'panterid'=>$panterid,'pname'=>'');
            $customid=$this->createCustoms($customArr);
            if($customid==false){
                returnMsg(array('status'=>'04','codemsg'=>'信息录入失败'));
            }
        }
        $userid='0000000000000000';
        $getCard=$this->getCard(1,$panterid);
        $this->model->startTrans();
        /*if($getCard==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡池数量不足，请联系至尊'));
        }*/
        $bool=$this->openCard($getCard,$customid,0,$panterid,1,'',$userid,1);
        if($bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'账户创建成功','customid'=>encode($customid)));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'开卡失败'));
        }
    }

    //充值接口
    public function customRecharge(){
        $data=getPostJson();
        $this->recordData($data);
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
        $panterid='00000853';
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
                $rechargeBool=$this->recharge($ownCards,$customid,$usableRecharge,$prefix.$sourceRechargeId,$userid);
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
            $openBool=$this->openCard($getCards,$customid,$openRechargeAmount,$panterid,2,$prefix.$sourceRechargeId,$userid);
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
        $this->recordData($data);
        $customid=trim($data['customid']);
        $amount=trim($data['amount']);
        $payPwd=trim($data['payPwd']);
        $key=trim($data['key']);
        $orderid=trim($data['orderId']);
        $sourceCode=trim($data['sourceCode']);
        $markcode=trim($data['markcode']);
        $panterid=trim($data['panterid']);
        $checkKey=md5($this->keycode.$customid.$amount.$orderid.$payPwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)||$amount==0){
            returnMsg(array('status'=>'03','codemsg'=>'消费金额格式有误'));
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
        $balanceAccount=$this->accountQuery($customid,'00');

        if($balanceAccount<$amount){
            returnMsg(array('status'=>'010','codemsg'=>'账户金额不足'));
        }
        //$coinAccount=$this->accountQuery($customid,'01');
        $coinAccount=$this->coinQuery($customid);
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
                'panterid'=>$panterid,'type'=>'00','amount'=>$amount,
                'walletConsumeAmount'=>0);
            $balanceConsumeIf=$this->consumeExe($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }
        if($balanceConsumeIf==true){
            $this->model->commit();
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'012','codemsg'=>'消费扣款失败'));
        }
    }

    //查询账户信息接口(通过用户编号)
    public function getAccount(){
        $data=getPostJson();
        $this->recordData($data);
        $customid=trim($data['customid']);
        //$customid=trim('MDAwMDAzNTYO0O0O ');
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'用户缺失'));
        }
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
        }
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
        $map=array('customid'=>$customid);
        $list=M('wallet')->where($map)->find();
        if($list!=false){
            $accountInfo['balance']=floatval($accountInfo['balance'])+floatval($list['amount']);
        }
        $map1=array('cu.customid'=>$customid,'c.cardkind'=>'6889','c.cardfee'=>array('in',array(1,2)));
        $cardList=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($map1)->field('c.cardno,c.status')->select();
        if($cardList!=false){
            $isActive=1;
            $c=0;
            foreach($cardList as $key=>$val){
                if($val['status']=='A'){
                    $c++;
                }
            }
            if($c==count($cardList)) $isActive=0;
        }else{
            $isActive=2;
        }
        returnMsg(array('status'=>'1','balance'=>floatval($accountInfo['balance']),
            'jycoin'=>floatval($accountInfo['jycoin']),'hasPaypwd'=>$hasPaypwd,
            'bindList'=>$bindList,'isActive'=>$isActive)
        );
    }

    //设置支付密码
    public function setPayPwd(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $paypwd=trim($datami['paypwd']);
        $key=trim($datami['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'会员编号缺失'));
        }
        if($paypwd==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法密码传入'));
        }
//        if(!preg_match("/\d{6,}$/",$paypwd)){
//            returnMsg(array('status'=>'02','codemsg'=>'密码格式错误'));
//        }
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
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $paypwd=trim($datami['paypwd']);
        $key=trim($datami['key']);
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
        $this->recordData($data);
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
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $oldpwd=trim($datami['oldpwd']);
        $newpwd=trim($datami['newpwd']);
        $key=trim($datami['key']);
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
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $newpwd=trim($datami['newpwd']);
        $key=trim($datami['key']);
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

    public function giftHb($customid,$panterid,$amount){
        $hbConig=M('hbrules')->where(array('panterid'=>$panterid,'gtype'=>2,'is_on'=>1))->find();
        if($hbConig==false) return;
        $giftConsume=$amount*$hbConig['rate']/100;
        $ownCards=$this->getOwnCards($customid);
        //$rechargeBool=$this->recharge($ownCards,$customid,$amount,'hb_'.$sourceRechargeId,$userid);
    }
}
?>
