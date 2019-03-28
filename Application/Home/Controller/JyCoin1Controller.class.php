<?php
namespace Home\Controller;
use Think\Controller;
use Org\Util\Des;
use Org\Util\YjDes;
use Think\Model;

class JyCoin1Controller extends CoinController {

    public function test(){
        echo encode('00368886');exit;
    }
    //创建账户接口
    public function createAccount(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $mobile=trim($datami['mobile']);
        $pwd=trim($datami['pwd']);
        $key=trim($datami['key']);
        $sourceCode=trim($datami['sourceCode']);
        $checkKey=md5($this->keycode.$mobile.$pwd.$sourceCode);
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $pname=$this->panterArr[$sourceCode]['pname'];
        $userid=$this->panterArr[$sourceCode]['userid'];
        //$str=$mobile.'--'.$pwd.'--'.date('Y-m-d H:i:s')."\r\n";
        //file_put_contents('test.txt',$str,FILE_APPEND);exit;
        if(!preg_match("/1[345789]{1}\d{9}$/",$mobile)){
            returnMsg(array('status'=>'01','codemsg'=>'手机号为空或者格式不对'));
        }
        $pwd=$this->decodePwd($pwd);
        if($pwd==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法密码传入'));
        }
        if($checkKey!=$key){
            returnMsg(array('status'=>'06','codemsg'=>'无效秘钥，非法传入'));
        }
        $bool=$this->checkMobile($mobile);
        //手机号没被注册时用手机号注册会员；若存在，已绑卡的直接返回会员编号，若没绑卡进行绑卡操作
        if($bool==true){
            $customid=$bool;
            $map1=array('cu.customid'=>$customid);
            $cardCount=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                ->join('cards c on cc.cid=c.customid')->where($map1)->field('c.cardno')->count();
            if($cardCount>0){
                returnMsg(array('status'=>'1','codemsg'=>'账户创建成功','customid'=>encode($customid)));
            }
            //returnMsg(array('status'=>'03','codemsg'=>'此号码已被注册'));
        }else{
            $customArr=array('mobile'=>$mobile,'pwd'=>$pwd,'panterid'=>$panterid,'pname'=>$pname);
            $this->model->startTrans();
            $customid=$this->createCustoms($customArr);
            if($customid==false){
                $this->model->rollback();
                returnMsg(array('status'=>'04','codemsg'=>'信息录入失败'));
            }
        }
        $getCard=$this->getCard(1,$panterid);
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
        //$data=json_decode('{"datami":"ratmHaBNwSP17Jjcb1P37ee32rgWgjqUUQKhKjOjODFMR1tqV6eYToSunCrhHbugEX\/I7z75jckdUUPDCZLfJwPjsp7bs24EODJQ7oLX5V1Ytxih\/BwToEmfFPEphXwbxuXk\/5ABwj4LupQh5lCiEH+1raJ1ZoSdPIpAkNNHu17RPPGgK1jJ390rkDNt1U7bnVKPX\/RRRJ0="}',1);
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $amount=trim($datami['amount']);
        $key=trim($datami['key']);
        $sourceCode=trim($datami['sourceCode']);
        $sourceRechargeId=trim($datami['sourceRechargeId']);
        //$sourceCode='1001';$amount='9000';$customid='00002479';
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $userid=$this->panterArr[$sourceCode]['userid'];
        $checkKey=md5($this->keycode.$customid.$amount.$sourceRechargeId.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        if(empty($sourceRechargeId)){
            returnMsg(array('status'=>'10','codemsg'=>'缺失充值编号'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'充值金额格式错误'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法会员编号'));
        }
        $map=array('description'=>array('like','%'.$prefix.$sourceRechargeId.'%'));
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
            $rechargeBool=$this->recharge($ownCards,$customid,$amount,$prefix.$sourceRechargeId,$userid);
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
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $balanceAmount=trim($datami['balanceAmount']);
        $coinAmount=trim($datami['coinAmount']);
        $payPwd=trim($datami['payPwd']);
        $key=trim($datami['key']);
        $orderid=trim($datami['orderId']);
        $backUrl=trim($datami['backUrl']);
        $sourceCode=trim($datami['sourceCode']);
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $orderPrefix=$this->panterArr[$sourceCode]['prefix'];

        $checkKey=md5($this->keycode.$customid.$balanceAmount.$coinAmount.$orderid.$payPwd.$backUrl.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'03','codemsg'=>'用户缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'05','codemsg'=>'建业通宝格式有误'));
        }
        if($balanceAmount==0&&$coinAmount==0){
            returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
        }
        if(empty($orderid)){
            returnMsg(array('status'=>'07','codemsg'=>'订单编号缺失'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'08','codemsg'=>'非法会员编号'));
        }
        $paypwd=$this->decodePwd($payPwd);
        if($paypwd==false){
            returnMsg(array('status'=>'012','codemsg'=>'非法密码传入'));
        }
        $pwdBool=$this->checkPayPwd($customid,$paypwd);
        if($pwdBool==='01'){
            returnMsg(array('status'=>'013','codemsg'=>'支付密码错误'));
        }elseif($pwdBool==='02'){
            returnMsg(array('status'=>'016','codemsg'=>'当天密码错误次数超过三次'));
        }
        $balanceAccount=$this->accountQuery($customid,'00');

        if($balanceAccount<$balanceAmount){
            $walletAccount=$this->getWalletAccount($customid);
            if($walletAccount!==false){
                if(($balanceAccount+$walletAccount)<$balanceAmount){
                    returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
                }
            }else{
                returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
            }
        }
        //$coinAccount=$this->accountQuery($customid,'01');
        $coinAccount=$this->coinQuery($customid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'010','codemsg'=>'建业通宝余额不足'));
        }
        $this->model->startTrans();
        $balanceConsumeIf=$coinConsumeIf=false;

        //余额消费，金额为0不执行
        if($balanceAmount>0){
            $map=array('eorderid'=>$orderPrefix.$orderid,'tradetype'=>'00');
            $balanceConsume=M('trade_wastebooks')->where($map)->sum('tradeamount');
            if($balanceConsume>0){
                returnMsg(array('status'=>'014','codemsg'=>'此订单已进行余额支付，请勿重复提交'));
            }
            if($balanceAmount>$balanceAccount){
                $walletConsumeAmount=$balanceAmount-$balanceAccount;
                $balanceConsumeAmount=$balanceAmount-$walletConsumeAmount;
            }else{
                $walletConsumeAmount=0;
                $balanceConsumeAmount=$balanceAmount;
            }
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>$orderPrefix.$orderid,
                'panterid'=>$panterid,'type'=>'00','amount'=>$balanceConsumeAmount,
                'walletConsumeAmount'=>$walletConsumeAmount);
            $balanceConsumeIf=$this->consumeExe($balanceConsumeArr);

            //var_dump($balanceConsumeIf);exit;
            if($walletConsumeAmount>0&&$balanceConsumeIf==true){
                $wallet=$this->model->table('wallet')->where(array('customid'=>$customid))->find();
                $storeid = $wallet['storeid'];
                $custom=$this->model->table('customs')->where(array('customid'=>$customid))->field('namechinese')->find();
                $key = md5($this->keycode . $storeid . $walletConsumeAmount . encode($customid) . $orderid . $custom['namechinese']);
                $sendData = array('storeid' => $storeid, 'amount' => $walletConsumeAmount, 'customid' => encode($customid), 'uniqid' => $orderid,
                    'name' => $custom['namechinese'],  'key' => $key);
                $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
                $datami = json_encode(array('datami' => $datami));
                $url =C('zfjsIp').'/admin/consume';
                $res = crul_post($url, $datami);
            }
        }else{
            $balanceConsumeIf=true;
        }
        //建业币消费，金额为0不执行
        if($coinAmount>0){
            $map=array('eorderid'=>$orderPrefix.$orderid,'tradetype'=>'00');
            $balanceConsume=M('trade_wastebooks')->where($map)->sum('tradepoint');
            //echo M('trade_wastebooks')->getLastSql();exit;
            if($balanceConsume>0){
                returnMsg(array('status'=>'015','codemsg'=>'此订单已进行建业通宝支付，请勿重复提交'));
            }

            $coinConsumeArr=array('customid'=>$customid,'orderid'=>$orderPrefix.$orderid,
                'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount);
            //$coinConsumeIf=$this->consumeExe($coinConsumeArr);
            $coinConsumeIf=$this->coinExe($coinConsumeArr);
            if($coinConsumeIf){
                //---------向一家传输用户通宝信息--17:21----
                $de = new YjDes();
                $tb_info = D("Ehome")->getTbinfo($customid);
                if($tb_info){
                    $appid  = 'SOON-ZZUN-0001';
                    $tb_info['customid'] = encode($tb_info['customid']);
                    $tb_info['activetype'] = 2;
                    $tb_info['appid'] = $appid;
                    $tb_sign = $de->encrypt($tb_info);
                    $tb_data = json_encode($tb_info,JSON_FORCE_OBJECT);
                    $return_yj = $this->curlPost(C('ehome_potb'),$tb_data,$tb_sign);
                    $return_arr = json_decode($return_yj,1);

                    if($return_arr['code'] == '100'){
                        $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","success");
                    }else{
                        $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","failed");
                    }

                }

                //---------end-----------------
            }
        }else{
            $coinConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$coinConsumeIf==true){
            $this->model->commit();
            ob_clean();
            echo json_encode(array('status'=>'1','info'=>array('reduceBalance'=>floatval($balanceAmount),'reduceCoin'=>floatval($coinAmount))));
            if(!empty($backUrl)){
                $backUrl=urldecode($backUrl);
                $backData=array('orderid'=>$orderid,'consumeAmount'=>$balanceAmount,'coinAmount'=>$coinAmount,'payRes'=>1);
                crul_post($backUrl,json_encode($backData));
                $this->recordMsg($orderid,$balanceAmount,$coinAmount,$backUrl);
            }
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'011','codemsg'=>'消费扣款失败'));
        }
    }

    //获取订单支付状态
    public function getPayInfo(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $orderid=trim($datami['orderId']);
        $balanceAmount=trim($datami['balanceAmount']);
        $coinAmount=trim($datami['coinAmount']);
        $sourceCode=trim($datami['sourceCode']);
        $key=trim($datami['key']);
        $orderPrefix=$this->panterArr[$sourceCode]['prefix'];
        $checkKey=md5($this->keycode.$orderid.$balanceAmount.$coinAmount.$sourceCode);

        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        //$orderid='201512281513';
        //$amount='6000';
        if(empty($orderid)){
            returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
        }
        $map['eorderid']=$orderPrefix.$orderid;
        $map['flag']=0;
        $map['tradetype']='00';
        $payInfo=M('trade_wastebooks')->where($map)
            ->field("sum(tradeamount) consumebalance,sum(tradepoint) consumecoin")->find();
        if($payInfo==false){
            returnMsg(array('status'=>'04','codemsg'=>'无扣款信息'));
        }
        //echo floatval($payInfo['consumebalance']).'--'.$balanceAmount;exit;
        if(floatval($payInfo['consumebalance'])!=$balanceAmount){
            returnMsg(array('status'=>'05','codemsg'=>'账户消费金额与订单金额不一致'));
        }
//        if(floatval($payInfo['consumecoin'])!=$coinAmount){
//            returnMsg(array('status'=>'06','codemsg'=>'建业币消费金额与订单金额不一致'));
//        }
        returnMsg(array('status'=>'1','payInfo'=>array('consumebalance'=>floatval($payInfo['consumebalance']),'consumecoin'=>floatval($payInfo['consumecoin']))));
    }

    //查询账户余额接口
    public function getBalance(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $key=trim($datami['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'用户编号缺失'));
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
        $balance=$this->accountQuery($customid,'00');
        file_put_contents('./aa12.txt',$balance);
        $map=array('customid'=>$customid);
        $list=M('wallet')->where($map)->find();
        if($list!=false){
            $balance=floatval($balance)+floatval($list['amount']);
        }
        returnMsg(array('status'=>'1','balance'=>floatval($balance)));
    }

    //查询账户信息接口(通过用户编号)
    public function getAccount(){
        //        $data=getPostJson();
//        $datami=trim($data['datami']);
//        $datami=$this->DESedeCoder->decrypt($datami);
//        if($datami==false){
//            returnMsg(array('status'=>'04','codemsg'=>'非法数据传入'));
//        }
//        $this->recordData($datami);
        $datami='{"customid":"MDAzNjkyNjkO0O0O","key":"cc5d59bb33971835e88e61800b3be56c"}';
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        //$customid=trim('MDAwMDAzNTYO0O0O ');
        $key=trim($datami['key']);
        if(empty($customid)){
			$this->recoreIp1();
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
        $map1=array('cu.customid'=>$customid,'c.cardkind'=>'6889','c.cardfee'=>'1');
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
    //查询账户信息接口(通过手机号)
    public function getAccountByMobile(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'05','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $linktel=trim($datami['mobile']);
        $key=trim($datami['key']);
        if(!preg_match("/1[34578]{1}\d{9}$/",$linktel)){
            returnMsg(array('status'=>'01','codemsg'=>'手机号为空或者格式不对'));
        }
        $checkKey=md5($this->keycode.$linktel);
        if($checkKey!=$key){
            returnMsg(array('status'=>'04','codemsg'=>'无效秘钥，非法传入'));
        }
        $map['cu.linktel']=$linktel;

        $custom=$this->customs->alias('cu')->join('cards c on cu.customid=c.customid')
            ->field('cu.customid,c.cardno')->where($map)->find();
        if($custom==false){
            returnMsg(array('status'=>'02','codemsg'=>'该手机尚未绑定账户'));
        }
        if($custom['customlevel']=='e+会员'){
            returnMsg(array('status'=>'03','codemsg'=>'该手机已绑定，无需重复绑定'));
        }
        $customid=$custom['customid'];
        returnMsg(array('status'=>'1','cardno'=>$custom['cardno'],'customid'=>encode($custom['customid'])));
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
//        if($pwdBool==false){
//            returnMsg(array('status'=>'03','codemsg'=>'支付密码错误'));
//        }else{
//            returnMsg(array('status'=>'1','codemsg'=>'校验通过'));
//        }
    }

    //校验是否支付密码
    public function hasPayPwd(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'05','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $key=trim($datami['key']);
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

    //绑定老卡
    public function cardBind(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'09','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        //$datami='{"cardno":"6889379600000219671","pwd":"anljYXJkMjg3Mzg5anljb2lu","key":"8f31946b9f0894d522a682a82ba62d0a","customid":"MDA0NjUyNDAO0O0O","sourceCode":"1001"}';
        $datami=json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $pwd=trim($datami['pwd']);
        $customid=trim($datami['customid']);
        $key=trim($datami['key']);
        $sourceCode=trim($datami['sourceCode']);

        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $checkKey=md5($this->keycode.$customid.$cardno.$pwd.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        //returnMsg(array('test'=>$this->cards->getLastSql()));exit;
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        $pwd=$this->decodePwd($pwd);
        if($pwd==false){
            returnMsg(array('status'=>'08','codemsg'=>'非法密码传入'));
        }
		//$url = C('netApiUrl');
        //$url='http://122.0.82.130:8087/Web/login.ashx';//正式环境
        //$url='http://192.168.10.50/Web/login.ashx';//老正式环境
        //$data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
        //$res=crul_post($url,$data);
        //$res=json_decode($res,1);
        //print_r($res);exit;

        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=$des->doEncrypt($pwd);
        if($pwd!=$card['cardpassword']){
            returnMsg(array('status'=>'03','codemsg'=>'密码错误'));
        }
        $field='c.customid cid,cu.customlevel,c.brandid,a.amount,cu.namechinese,cu.personidtype,cu.personid,';
        $field.='cu.nameenglish,cu.linkman,cu.sex,cu.birthday,c.status,c.cardkind,a1.amount coin';
        //查询老卡关联会员信息
        $where['c.cardno']=$cardno;
        $where['a.type']='00';
        $where['a1.type']='01';
        $card=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('account a on c.customid=a.customid')
            ->join('account a1 on c.customid=a1.customid')
            ->where($where)->field($field)->find();

        $map1=array('cardno'=>$cardno,'status'=>1);
        $bindList=M('ecard_bind')->where($map1)->select();
        //echo $this->cards->getLastSql();exit;
        if($card['status']!='Y'&&$card['status']!='A'){
            returnMsg(array('status'=>'04','codemsg'=>'非正常卡'));
        }
        if($card['cardkind']=='6882'||$card['cardkind']=='2081'){
            returnMsg(array('status'=>'05','codemsg'=>'酒店卡，不允许绑定'));
        }
        if($bindList!=false){
            returnMsg(array('status'=>'06','codemsg'=>'此卡已绑定'));
        }

        $this->model->startTrans();
        //将绑定卡关联至注册会员账户上；
        $customid=decode($customid);
        $customCData=array('customid'=>$customid);
        $where2=array('cid'=>$card['cid']);
        $customCIf=$this->customs_c->where($where2)->save($customCData);

        //绑定卡的会员信息一直到注册的会员信息中
        $where3['customid']=$customid;
        $custom=$this->customs->where($where3)->find();
        if(empty($custom['namechinese'])&&$custom['personid']){
            $data3=array('namechinese'=>$card['namechinese'],
                'nameenglish'=>$card['nameenglish'],
                'personidtype'=>$card['personidtype'],
                'personid'=>$card['personid'],
                'linkman'=>$card['linkman'],
                'sex'=>$card['sex'],
                'birthday'=>$card['birthday']
            );
            $customIf=$this->customs->where($where3)->save($data3);
        }else{
			$customIf=true;
		}

        $sql="INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}')";
        $ecardBindIf=$this->model->execute($sql);

        if($customCIf==true&&$customIf==true&&$ecardBindIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'绑定成功','addamount'=>floatval($card['amount']),'addcoin'=>floatval($card['coin'])));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'绑定失败'));
        }
    }

    //检查老卡
    public function checkCard(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'05','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->field('cardkind,cardno,status')->find();
        $map1=array('cardno'=>$cardno,'status'=>1);
        $bindList=M('ecard_bind')->where($map1)->select();
        if($card['status']!='Y'&&$card['status']!='A'){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号或者非正常卡号'));
        }
        if($card['cardkind']=='6882'){
            returnMsg(array('status'=>'03','codemsg'=>'酒店卡，不允许绑定'));
        }
        if($bindList!=false){
            returnMsg(array('status'=>'04','codemsg'=>'此卡已经绑定'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'校验通过'));
        }
    }

    //营销劵查询
    public function ticketsQuery(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        //$cardno='2336370888800422152';
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        $tickketList=$this->getTicketByCardno($cardno);
        //print_r($tickketList);exit;
        if($tickketList==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无卡劵信息'));
        }else{
            returnMsg(array('status'=>'1','ticketList'=>json_encode($tickketList)));
        }
    }

    //撤销订单
    public function cancelOrder(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $orderid=trim($datami['orderId']);
        $sourceCode=trim($datami['sourceCode']);
        $key=trim($datami['key']);
        $orderPrefix=$this->panterArr[$sourceCode]['prefix'];
        $checkKey=md5($this->keycode.$orderid.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        //$orderid='201512281850';
        if(empty($orderid)){
            returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
        }
        $map['eorderid']=$orderPrefix.$orderid;
        $map['flag']=0;
        $map['tradetype']='00';
        $tradeList=M('trade_wastebooks')->where($map)
            ->field("tradeid,tradeamount,tradepoint,cardno")->select();
        if($tradeList==false){
            returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
        }
        $this->model->startTrans();
        $c=0;
        foreach($tradeList as $ke=>$val){
            $map['cardno']=$val['cardno'];
            $cardInfo=$this->cards->where($map)->find();
            $customid=$cardInfo['customid'];
            if($val['tradeamount']!=0){
                $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradeamount']." WHERE customid='{$customid}' and type='00'";
                $balanceIf=$this->account->execute($balanceSql);
            }else{
                $balanceIf=true;
            }
            if($val['tradepoint']!=0){
                $coinSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradepoint']." WHERE customid='{$customid}' and type='01'";
                $coinIf=$this->account->execute($coinSql);
                $coinReturnIf=$this->returnCoin($val['tradeid']);
            }else{
                $coinIf=true;
                $coinReturnIf=true;
            }
            $tradeMap=array('tradeid'=>$val['tradeid']);
            $tradeData=array('tradetype'=>'04');
            $tradeIf=M('trade_wastebooks')->where($tradeMap)->save($tradeData);
            if($balanceIf==true&&$coinIf==true&&$tradeIf==true&&$coinReturnIf==true){
                $c++;
            }
        }
        if($c==count($tradeList)){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败'));
        }
    }

    //订单退款
    public function returnGoods(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $orderid=trim($datami['orderId']);
        $returnAmount=trim($datami['returnAmount']);
        $returnCoin=trim($datami['returnCoin']);
        $sourceCode=trim($datami['sourceCode']);
        $key=trim($datami['key']);

        $orderPrefix=$this->panterArr[$sourceCode]['prefix'];
        $checkKey=md5($this->keycode.$orderid.$returnAmount.$returnCoin.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($orderid)){
            returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$returnAmount)){
            returnMsg(array('status'=>'04','codemsg'=>'退款金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$returnCoin)){
            returnMsg(array('status'=>'05','codemsg'=>'退款建业币格式有误'));
        }
        if($returnAmount==0&&$returnCoin==0){
            returnMsg(array('status'=>'06','codemsg'=>'退款数据有误'));
        }
        $orderid=$orderPrefix.$orderid;
        $map=array('eorderid'=>$orderid,'flag'=>0,'tradetype'=>'00');
        $tradeC=M('trade_wastebooks')->where($map)
            ->field("tradeid,tradeamount,tradepoint,cardno")->count();

        $tradeC1=M('income_books')->where(array('active_id'=>$orderid))->count();
        if($tradeC==0&&$tradeC==0){
            returnMsg(array('status'=>'07','codemsg'=>'无此订单支付信息'));
        }
        $tradeInfo=M('trade_wastebooks')->where($map)
            ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

        $map1=array('eorderid'=>$orderid,'flag'=>array('in',array('2','3')),'tradetype'=>'00');
        $cancleTrade=M('trade_wastebooks')->where($map1)
            ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();
        if($tradeInfo['orderamount']-$cancleTrade['orderamount']<$returnAmount){
            returnMsg(array('status'=>'08','codemsg'=>'退款金额大于订单金额'));
        }
        if($tradeInfo['ordercoin']-$cancleTrade['ordercoin']<$returnCoin){
            returnMsg(array('status'=>'09','codemsg'=>'退款建业币金额大于订单建业币金额'));
        }
        //echo $orderid.'--'.$returnAmount.'--'.$returnCoin;exit;
        $this->model->startTrans();
        if($returnAmount>0){
            $balanceIf=true;
            $balanceIf=$this->refund($orderid,$returnAmount,1);
        }else{
            $balanceIf=true;
        }
        if($returnCoin>0){
            returnMsg(array('status'=>'011','codemsg'=>'通宝消费不能退款'));
            //$coinIf=$this->refund($orderid,$returnCoin,2);
        }else{
            $coinIf=true;
        }
        if($balanceIf==true&&$coinIf==true){
            //$this->model->rollback();
            $this->model->commit();
            returnMsg(array('status'=>'1','info'=>array('addBalance'=>floatval($returnAmount),'addCoin'=>floatval($returnCoin))));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'010','codemsg'=>'退款失败'));
        }
    }

    //建业币充值
    public function rechargeCoin(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid=trim($datami['customid']);
        $amount=trim($datami['amount']);
        $key=trim($datami['key']);
        $sourceCode=trim($datami['sourceCode']);
        $sourceRechargeId=trim($datami['sourceRechargeId']);
        returnMsg(array('status'=>'010','codemsg'=>'非项目方禁止进行通宝充值'));
        //$sourceCode='1001';$amount='9000';$customid='00002479';
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $checkKey=md5($this->keycode.$customid.$amount.$sourceRechargeId.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'充值金额格式错误'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法会员编号'));
        }
        $cardno=$this->getMainCard($customid);
        if($cardno==false){
            returnMsg(array('status'=>'05','codemsg'=>'会员缺失至尊卡号'));
        }
        $accountInfo=$this->getCoinAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'07','codemsg'=>'会员账户异常'));
        }
        $map=array('description'=>array('like','%'.$prefix.$sourceRechargeId.'%'));
        $cpl=M('card_purchase_logs')->where($map)->find();
        if($cpl!=false){
            returnMsg(array('status'=>'09','codemsg'=>'此充值号已经充值，请勿重复提交'));
        }
        $this->model->startTrans();
        $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],'accountid'=>$accountInfo['accountid'],
            'orderid'=>$prefix.$sourceRechargeId,'customid'=>$customid);
        $coinBool=$this->coinRecharge($cardInfo,$amount,$panterid);
        if($coinBool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','addcoin'=>floatval($amount)));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'08','codemsg'=>'建业币充值失败'));
        }
    }

    //通过会员编号获得会员下的主卡（卡编号与会员编号一样的卡）；若没有，获取编号最小的卡
    protected function getMainCard($customid){
        $map=array('customid'=>$customid);
        $mainCard=$this->cards->where($map)->find();
        if($mainCard==false){
            $map1=array('cu.customid'=>$customid);
            $card=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on cc.cid=c.customid')->where($map1)->order('c.customid asc')->find();
            if($card==false){
                return false;
            }else{
                return $card['cardno'];
            }
        }else{
            return $mainCard['cardno'];
        }
    }

    //一家用户注册接口对接测试
    public function registYjia(){

//        $data=json_decode('{
//            "appid": "SOON-ZZUN-0001","orgid": "12","building": "5号楼","unint": "1",
//            "housenum": "501","mobilephone": "13982654763","password": "094564",
//            "uname": "康瑾瑾","idcardno": "411424199110094564","customid": "MDAxODUxMTMO0O0O"
//            }',1);
//        $de = new YjDes();
//        $sign = $de->encrypt($data);
//        $data = json_encode($data,JSON_FORCE_OBJECT);
//        $url = "http://testo2o.yijiahn.com/jyo2o_web/app/user/register.user";
//        $result = $this->curlPost($url,$data,$sign);
//        print_r($result);exit;
        //$resultinfo = json_decode($result,true);
       $datainfo = $_POST['info'];
       $datainfo = json_decode($datainfo,true);
        $cardno=$datainfo['zzkno'];
       if($datainfo['key'] == md5(md5(md5($datainfo['uname'].$datainfo['idcardno'].'yjzc')))){
       $appid  = 'SOON-ZZUN-0001';
       $homeinfo = $datainfo['homeinfo'];//项目名称-分期-楼栋号-单元号(可能没有)-房号
       $homearray = explode('-',$homeinfo);
       $orgid = $datainfo['orgid'];//小区id
       if(count($homearray) == 6){
       $building = $homearray[3];//楼栋号
       $unint = $homearray[4];//单元号
       $housenum = $homearray[5];//房间号
       }elseif(count($homearray) == 5){
       $building = $homearray[2];//楼栋号
       $unint = $homearray[3];//单元号
       $housenum = $homearray[4];//房间号
     }elseif(count($homearray) == 4){
       $building = $homearray[2];//楼栋号
       $unint = '';//单元号
       $housenum = $homearray[3];//房间号
     }elseif(count($homearray) == 3){
         $building = $homearray[1];
         $unint = '';
         $housenum = $homearray[2];
     }else{
       $building = '';//楼栋号
       $unint = '';//单元号
       $housenum = '';//房间号
     }
       $idcardno = $datainfo['idcardno'];//用户身份证号
       $password = substr($idcardno,-6);//用户密码
       $mobilephone = $datainfo['mobilephone'];//用户手机号
       $uname = $datainfo['uname'];//用户名字
       $customid = $this->getCustomid($datainfo['zzkno']);//用户会员编号
       $data = array('appid'=>$appid,
                     'orgid'=>$orgid,
                     'building'=>$building,
                     'unint'=>$unint,
                     'housenum'=>$housenum,
                     'mobilephone'=>$mobilephone,
                     'password'=>$password,
                     'uname'=>$uname,
                     'idcardno'=>$idcardno,
                     'customid'=>encode($customid)
                   );
       $de = new YjDes();
       $sign = $de->encrypt($data);
       $data = json_encode($data,JSON_FORCE_OBJECT);
       $url = C("ehome_regist");

           //---------向一家传输用户通宝信息--17:21----
           $tb_info = D("Ehome")->getTbinfo($customid);
           if($tb_info){
               $tb_info['customid'] = encode($tb_info['customid']);
               $tb_info['activetype'] = 2;
               $tb_info['appid'] = $appid;
               $tb_sign = $de->encrypt($tb_info);
               $tb_data = json_encode($tb_info,JSON_FORCE_OBJECT);
               $return_yj = $this->curlPost(C('ehome_potb'),$tb_data,$tb_sign);
               $return_arr = json_decode($return_yj,1);

               if($return_arr['code'] == '100'){
                   $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","success");
               }else{
                   $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","failed");
               }

           }

           //---------end-----------------


       $result = $this->curlPost($url,$data,$sign);
       $resultinfo = json_decode($result,true);
           $map=array('cardno'=>$cardno,'customid'=>$customid,'panterid'=>'00000286');
           $list=$this->model->table('ecard_bind')->where($map)->find();
           if($resultinfo['code']=='100'){
               /*if($list==false){
                   $sql="INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'00000286')";
                   $this->model->execute($sql);
               }*/
               $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","success");
           }elseif($resultinfo['code']=='500'){
               $customid=$resultinfo['msg'];
               $customid=decode($customid);
               /*if($list==false&&$customid!=false){
                   $sql="INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'00000286')";
                   $this->model->execute($sql);

                   $card=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                       ->where(array('c.cardno'=>$cardno))
                       ->field('c.customid cid,cu.namechinese,cu.nameenglish,cu.personid,cu.personidtype,cu.linkman,cu.sex,cu.birthday')
                       ->find();
                   $customCData=array('customid'=>$customid);
                   $where2=array('cid'=>$card['cid']);
                   $customCIf=$this->customs_c->where($where2)->save($customCData);

                   //绑定卡的会员信息一直到注册的会员信息中
                   $where3['customid']=$customid;
                   $data3=array('namechinese'=>$card['namechinese'],
                       'nameenglish'=>$card['nameenglish'],
                       'personidtype'=>$card['personidtype'],
                       'personid'=>$card['personid'],
                       'linkman'=>$card['linkman'],
                       'sex'=>$card['sex'],
                       'birthday'=>$card['birthday']
                   );
                   $customIf=$this->customs->where($where3)->save($data3);

               }*/
               $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","registed");
           }elseif($resultinfo['code']=='501'){
               $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","registed");
           }else{
               $this->recordError(date("H:i:s").'-'.$data.'-'.$result.'-'.json_encode($datainfo)."\n\t","YjRegist","error");
           }
           echo 1;
//       switch ($resultinfo['code']) {
//            case 100:
//            $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","success");
//                break;
//            case 500:
//            $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","registed");
//                break;
//            default:
//            $this->recordError(date("H:i:s").'-'.$data.'-'.$result.'-'.json_encode($datainfo)."\n\t","YjRegist","error");
//        }
         echo 1;
     }else{
       echo 0;
     }
    }

    public function mytest(){
    		$password = '888888';//用户密码
    		$data = array('password'=>$password,);
    		$de = new YjDes();
    		$sign = $de->encrypt($data['password']);
    		var_dump($sign);
    		$data = json_encode($data,JSON_FORCE_OBJECT);

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

    //测试库清理e+会员数据
    public function test2(){
        $map=array('customlevel'=>'建业线上会员');
        $list=M('customs')->where($map)->field('customid')->limit(0,100)->select();
        //print_r($list);exit;
        foreach($list as $key=>$val){
            $this->model->startTrans();
            $map1=array('customid'=>$val['customid']);
            $list1=M('customs_c')->where($map1)->select();
            foreach($list1 as $k=>$v){
                $map2=array('customid'=>$v['cid']);
                $card=M('cards')->where($map2)->field('cardno')->find();
                if($card!=false){
                    $map3=array('cardno'=>$card['cardno']);
                    M('card_purchase_logs')->where($map3)->delete();
                    M('card_active_logs')->where($map3)->delete();
                    $data=array('status'=>'N','customid'=>0);
                    if($card['card']!='2336'){
                        M('cards')->where($map2)->save($data);
                    }
                    M('ecard_bind')->where($map3)->delete();
                }
                M('account')->where($map2)->delete();
                M('custom_purchase_logs')->where($map2)->delete();
            }
            M('customs_c')->where($map1)->delete();
            M('customs')->where($map1)->delete();
            $this->model->commit();
        }
    }

    public function test3(){
//        $map=array('cu.countrycode'=>'football会员','c.cardno'=>array('EXP','IS NULL'));
//        $customs=M('customs')->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
//            ->join('left join cards c on c.customid=cc.cid')
//            ->where($map)->field('cu.customid,cc.cid,cu.linktel,cu.countrycode,c.cardno,cu.placeddate')
//            ->order('cu.placeddate asc')->limit(0,200)->select();
        $array=array();
        //print_r($customs);exit;
        foreach($array as $key=>$val){
            $map1=array('customid'=>$val['customid']);
            $a=M('customs_c')->where($map1)->select();
            if($a==false){
                $getCard=$this->getCard(1,'00000290');
                $bool=$this->opencard($getCard,$val['customid'],0,'00000290');
            }
        }
    }

    //清楚正式环境下的测试数据
    public function test4(){
        $arr=array();
//        foreach($arr as $key=>$val){
//            $map=array('linktel'=>$val['linktel'],'customlevel'=>'建业线上会员');
//            $custom=$this->customs->where($map)->find();
//            $map1=array('customid'=>$custom['customid']);
//            $customs_c=M('customs_c')->where($map1)->select();
//            echo count($customs_c).'<br/>';
//        }
//        exit;
        foreach($arr as $key=>$val){
            $map=array('linktel'=>$val['linktel'],'customlevel'=>'建业线上会员');
            $custom=$this->customs->where($map)->find();
            if($custom!=false){
                $map1=array('cc.cid'=>$custom['customid']);
                $card=$this->customs_c->alias('cc')->join('cards c on cc.cid=c.customid')
                    ->where($map1)->field('c.cardno,c.customid')->find();
                if($card!=false){
                    $map2=array('cardno'=>$card['cardno'],'amount'=>array('gt',0));
                    $card_purchase_logs=M('card_purchase_logs')->where($map2)->field('purchaseid')->select();
                    foreach($card_purchase_logs as $k=>$v){
                        $map3=array('purchaseid'=>$v['purchaseid']);
                        M('custom_purchase_logs')->where($map3)->delete();
                    }
                    $map7=array('cardno'=>$card['cardno'],'point'=>array('gt',0));
                    M('card_purchase_logs')->where($map7)->delete();

                    M('card_purchase_logs')->where($map2)->delete();
                    $map4=array('cardno'=>$card['cardno']);
                    M('trade_wastebooks')->where($map4)->delete();
                    $map5=array('cardid'=>$card['customid']);
                    M('coin_account')->where($map5)->delete();
                    $map6=array('customid'=>$card['customid']);
                    $data6=array('amount'=>0);
                    M('account')->where($map6)->save($data6);
                }
            }
        }
    }
    public function query_balance(){
		$cid = trim(I('get.cid'));
        if(isset($cid)){
          $customid = $cid;
          $sign = md5($this->keycode.$customid);
          $data =array(
            'customid'=>$customid,
            'key'=>$sign
          );
		  session('access_token',"accessToken={$_GET['access_token']}");
          $data = json_encode($data);
          $data = $this->DESedeCoder->encrypt($data);
          $data = json_encode(array('datami'=>$data));
          $url = C('e+_balance');
          $query=$this->getQuery($url,$data);
          $balance = $query['balance'];
          $this->assign('pid',$customid);

          $this->assign('balance',$balance);
      }
          $this->display();
    }
    public function getQuery($url,$data){
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
      curl_setopt($ch,CURLOPT_POST,1);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
      $output = curl_exec($ch);
      curl_close();
      return json_decode($output,true);
    }
    public function create_order(){
      if(IS_POST){
        // $pid = trim(I('post.customid '));
        $chargeMoney = trim(I('post.chargeMoney'));
        $data =array(
          'from'=>'wallet',
          'goodstype'=>'wallet',
          'name'=>'余额充值',
          'price'=>floatval($chargeMoney),
          'channel'=>'nosupervip,nojycoin,noqpay',
		  'refid'=>'zhizun_yue',
          'storeid'=>'32947',
          'callbackurl'=>C('callbackurl'),
        );
//        if($chargeMoney>1000){
//            exit(json_encode(['msg'=>'充值金额大于1000','code'=>'502','success'=>'false']));
//        }
        $url = C('e+create_order').'?'.session('access_token');
        $query = $this->getQuery($url,$data);
        echo json_encode($query);
      }
    }
    public function receiveE(){
      $data=file_get_contents('php://input');
      file_put_contents('./orderid',$data."\r\n",FILE_APPEND);
      $data = explode('&',$data);
      foreach ($data as $key => $val){
        $val = explode('=',$val);
        $arr[$val[0]]= $val[1];
      }
      $customid = $arr['customid'];
      $amount = $arr['amount']/100;
      $orderno = $arr['orderno'];
      $source = $arr['source'];
      // $customid = 'MDAwMDM3NDkO0O0O';
      // $amount = 1;
      // $orderno = 'SW20160425034121004433350011111ssssss';
      // $source ='jyo2o';
      // $amount = $amount/100;
      $amount =number_format($amount, 2, '.', '');
      $str = 'customid:'.$customid."\t,amount:".$amount."\t,orderno:".$orderno."\t,source:".$source;
      $str1=date('Y-m-d H:i:s',time())."\t,";
      if(empty($customid)){
        $str1.= '缺失用户id'."\t,";
      }
      if(empty($amount)){
        $str1.= '充值金额为空'."\t,";
      }
      if($amount<=0){
        $str1.= '充值金额大于零'."\t,";
      }
      if(empty($orderno)){
          $str1.= '缺失订单号'."\t,";
      }
      if($source!='jyo2o'){
        $str1.= '缺失来源地址'."\t,";
      }else{
        $source = '1001';
      }
      $datas = array(
        'customid'=>$customid,
        'amount'=>$amount,
        'sourceCode'=>$source,
        'sourceRechargeId'=>$orderno,
        'key'=>md5($this->keycode.$customid.$amount.$orderno.$source),
      );
//      if($amount>1000){
//          $str1.='amount=>金额大于1000'."\t\n";
//      }else{
          $datas = json_encode($datas);
          $datas = $this->DESedeCoder->encrypt($datas);
          $datas = json_encode(array('datami'=>$datas));
          $url = C('chargeMoney');
          //$url = "http://192.168.10.45/zzkp.php/JyCoin/customRecharge";
          $query=$this->getQuery($url,$datas);
          $str1.=$str.$query['codemsg']."\t\n";
//      }
        $this->writeLogs('chargeMoney',$str1);
      exit;
    }
    private function writeLogs($module,$msgString){
        $month=date('Ym',time());
        switch($module){
            case 'chargeMoney':$logPath=PUBLIC_PATH.'logs/chargeMoney/false/';break;
            case 'undo':$logPath=PUBLIC_PATH.'logs/Authorize/undo/';break;
            case 'over':$logPath=PUBLIC_PATH.'logs/Authorize/over/';break;
            case 'over_undo':$logPath=PUBLIC_PATH.'logs/Authorize/over_undo/';break;
            case 'success':$logPath=PUBLIC_PATH.'logs/Authorize/success/';break;
            case 'fail':$logPath=PUBLIC_PATH.'logs/Authorize/fail/';break;
            default :$logPath=PUBLIC_PATH.'logs/file/';
        }
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$msgString,FILE_APPEND);
    }

    public function batchRefund(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $orderString=trim($datami['orderString']);
        $sourceCode=trim($datami['sourceCode']);
        $key=trim($datami['key']);

        $orderPrefix=$this->panterArr[$sourceCode]['prefix'];
        $checkKey=md5($this->keycode.$orderString.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        $orderArr=json_decode($orderString);
        if($orderArr==false){
            returnMsg(array('status'=>'03','codemsg'=>'订单传入有误'));
        }
        $errorMsg=array();
        foreach($orderArr as $key=>$val){
            $orderId=$val['orderId'];
            $returnAmount=$val['returnAmount'];
            if(empty($orderId)){
                $errorMsg[$key]['msg']='订单缺失';
                $errorMsg[$key]['stat']=0;
                continue;
            }
            if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$returnAmount)){
                $errorMsg[$key]['msg']='退款金额缺失';
                $errorMsg[$key]['stat']=0;
                continue;
            }
            $orderId=$orderPrefix.$orderId;
            $map=array('eorderid'=>$orderId,'flag'=>0,'tradetype'=>'00');
            $tradeC=M('trade_wastebooks')->where($map)
                ->field("tradeid,tradeamount,tradepoint,cardno")->count();
            if($tradeC==0){
                $errorMsg[$key]['msg']='无此订单信息';
                $errorMsg[$key]['stat']=0;
                continue;
            }
            $tradeInfo=M('trade_wastebooks')->where($map)
                ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

            if($tradeInfo['orderamount']!=$returnAmount){
                $errorMsg[$key]['msg']='退款金额与订单金额不一致';
                $errorMsg[$key]['stat']=0;
                continue;
            }
            $this->model->startTrans();
            $balanceIf=$this->refund($orderId,$returnAmount,1);
            if($balanceIf==true){
                $this->model->commit();
                $errorMsg[$key]['msg']='订单'.$orderId.'退款成功';
                $errorMsg[$key]['info']=array('addBalance'=>floatval($returnAmount));
                $errorMsg[$key]['stat']=1;
                continue;
            }else{
                $this->model->rollback();
                $errorMsg[$key]['msg']='订单'.$orderId.'退款失败';
                $errorMsg[$key]['stat']=0;
                continue;
            }
        }
        returnMsg(array('status'=>1,'res'=>$errorMsg));
    }

    //绿色基地配卡
    public function allocateCards(){
        $data=getPostJson();
        $panterid=trim($data['panterid']);
        $termno=trim($data['termno']);
//        $panterid='00000241';
//        $termno='54700696';
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$panterid.$termno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $userid='0000000000000197';
        $cardbrand='6880';
        $termArr=array('50900756'=>1,'50900802'=>2,'50900807'=>3,'50900806'=>4);
        $termflag=empty($termArr[$termno])?8:$termArr[$termno];
        $cardStart=$cardbrand.'374'.$termflag;
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
        $namechinese='大食堂'.intval($customid);
        $currentDate=date('Ymd',time());
        $this->model->startTrans();
        $sql="INSERT INTO CUSTOMS(customid,namechinese,placeddate,panterid) values ";
        $sql.="('{$customid}','{$namechinese}','{$currentDate}','{$panterid}')";
        $customIf=$this->model->execute($sql);
        if($customIf==true){
            $cardNo=$this->getLvCards($panterid,$cardStart);
            $cardNo=$this->checkCardUsable($cardNo,$panterid,$cardbrand);
            if(!empty($cardNo)){
                $bool=$this->opencard(array($cardNo),$customid,0,$panterid,1,$userid);
                if($bool==true){
                    $this->model->commit();
                    returnMsg(array('status'=>'1','cardno'=>$cardNo,'termno'=>$termno));
                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'04','codemsg'=>'配卡失败'));
                }
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'02','codemsg'=>'卡池数量不足'));
            }
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'03','codemsg'=>'会员创建失败'));
        }
    }

    //一家通宝订单穿透接口
    public function coinOrderAssyn(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        //$datami='WpLyprjGzj1LL/a90TaeDa2rZh2gTcEjKfazCWbNPSkw/hkKcFdwLVECoSoz ozgx45MmgLnFjwLatvEGtwtQ5nmijRfCz9LxcEm2zXG8JkBtxmun0biDaqoJ kIRWKI25o87YMdnYl8QguCaj1LyZeKrDdACgSitLWcataNnLJd6SYTficLI6 VH8bFG/GHYC+iNi22GtGJNjFPlBK/8axQtfGKYY/tXyWUqZ9G+74swxCAp0Q 6pk94a5lwx6kvoX4nAi7lCXVaQWQXpg4LRJV9PRSK+0QCaJMZbPwhLmVabJ4 NebFODDrBQ==';
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $datami=json_decode($datami,1);
        $orderListStr=$datami['orderListStr'];
        $customid=$orderListStr['customId'];
        //$customid=encode('00185514');
        $payorder=$orderListStr['payorder'];
        //$payorder='0001-6d4e06730c414a27931d872101177537';
        $orderlist=$orderListStr['orderList'];

        $key=trim($datami['key']);
        $orderPrefix=$this->panterArr['1001']['prefix'];
        $checkKey=md5($this->keycode.json_encode($orderListStr));

        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->recordData($datami);
        if(empty($customid)){
            returnMsg(array('status'=>'03','codemsg'=>'用户缺失'));
        }
        if(empty($orderlist)){
            returnMsg(array('status'=>'04','codemsg'=>'子订单不能为空'));
        }
        $list=M('eouttrade')->where(array('orderid'=>$payorder))->select();
        if($list!=false){
            returnMsg(array('status'=>'05','codemsg'=>'该订单已同步'));
        }
        $amount=0;
        foreach($orderlist as $key=>$val){
            if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$val['coinAmount'])||$val['coinAmount']==0){
                returnMsg(array('status'=>'06','codemsg'=>'子订单通宝数据格式有误'));
            }
            if(empty($val['storeId'])||empty($this->storeArr[$val['storeId']])){
                //returnMsg(array('status'=>'07','codemsg'=>'子订单店铺id缺失'));
                $orderlist[$key]['panterid']='00000286';
            }else{
                $orderlist[$key]['panterid']=$this->storeArr[$val['storeId']];
            }
//            if(empty($this->storeArr[$val['storeId']])){
//                returnMsg(array('status'=>'08','codemsg'=>'子订单无对应店铺商户'));
//            }
            $amount+=$val['coinAmount'];
        }
        //$amount=0.01;
        if(empty($payorder)){
            returnMsg(array('status'=>'09','codemsg'=>'订单编号缺失'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'010','codemsg'=>'非法会员编号'));
        }
        $trade=M('trade_wastebooks');
        $eorderid=$orderPrefix.$payorder;
        $map1=array('tw.eorderid'=>$eorderid);
        $list=$trade->alias('tw')->where($map1)->select();
        if($list==false){
            returnMsg(array('status'=>'011','codemsg'=>'查无订单'));
        }
        $customList=$trade->alias('tw')->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($map1)->field('cu.customid')->group('cu.customid')->select();
        if(count($customList)!=1){
            returnMsg(array('status'=>'012','codemsg'=>'异常订单'));
        }
        if($customid!=$customList[0]['customid']){
            returnMsg(array('status'=>'013','codemsg'=>'非本人订单编号'));
        }
        $map2=array('eorderid'=>$eorderid,'tradepoint'=>array('gt',0),'tradeamount'=>0);
        $consumeAmount=$trade->where($map2)->sum('tradepoint');
        if($consumeAmount!=$amount){
            returnMsg(array('status'=>'014','codemsg'=>'消费金额与传入订单金额不符'));
        }

        $this->model->startTrans();
        $tradeSql="update trade_wastebooks set flag='3' where eorderid='{$eorderid}'";
        //echo $tradeSql;exit;
        $tradeIf=$this->model->execute($tradeSql);
        //echo $this->model->getLastSql().'<br/>';
        $c=0;
        foreach($list as $key=>$val){
            $tradeid=$val['tradeid'];
            $cardno=$val['cardno'];
            $card=$this->cards->where(array('cardno'=>$cardno))->field('customid')->find();

            $consumeList=M('coin_consume')->where(array('tradeid'=>$tradeid,'cardid'=>$card['customid']))->select();
            $c1=0;
            foreach($consumeList as $k=>$v){
                $coinAccountSql="update coin_account set remindamount=remindamount+{$v['amount']} where coinid='{$v['coinid']}' and cardid='{$v['cardid']}'";
                $coinAccountIf=$this->model->execute($coinAccountSql);

                //echo $this->model->getLastSql().'<br/>';
                $accountSql="update account set amount=amount+{$v['amount']} where customid='{$v['cardid']}' and type='01'";
                $accountIf=$this->model->execute($accountSql);
                //echo $this->model->getLastSql().'<br/>';

                $coinConsumecoinSql="delete from coin_consume  where coinconsumeid='{$v['coinconsumeid']}' and cardid='{$v['cardid']}'";
                $coinConsumeIf=$this->model->execute($coinConsumecoinSql);
                //echo $this->model->getLastSql().'<br/>';

                if($coinAccountSql==true&&$accountSql==true&&$coinConsumeIf==true){
                    $c1++;
                }
            }
            $c1=1;
            if($c1==count($list)){
                $c++;
            }
        }

        $c2=0;
        //print_r($orderlist);exit;
        foreach($orderlist as $key=>$val){
            $orderid=trim($val['orderId']);
            $coinAmount=trim($val['coinAmount']);
            $storeId=trim($val['storeId']);
            $panterid=empty($this->storeArr[$storeId])?'00000286':$this->storeArr[$storeId];
            $sql="insert into eouttrade values ('{$storeId}','{$panterid}','{$payorder}','{$coinAmount}','{$orderid}')";
            $eoutIf=$this->model->execute($sql);
            //echo $this->model->getLastSql().'<br/>';

            $coinConsumeArr=array('customid'=>$customid,'orderid'=>$orderPrefix.$orderid,
                'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount,'termno'=>'00000001');
            $coinConsumeIf=$this->coinExe($coinConsumeArr);
            //echo $this->model->getLastSql().'<br/>';
            if($eoutIf==true&&$coinConsumeIf==true){
                $c2++;
            }
        }
        //exit;
        if($c==count($list)&&$c2==count($orderlist)&&$tradeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'同步成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'015','codemsg'=>'同步失败'));
        }
    }

    //物业通宝消费订单穿透接口
    public function wyOrderAssyn(){
        $data=getPostJson();
        //$data=json_decode('{"orderid":"s259960160bda68fd01616877cc7a766","amount":"0.06","areaname":"\u5f00\u5c01\u7199\u548c\u5e9c(\u6d4b\u8bd5)","customid":"MDAxNTAyMzkO0O0O","key":"34a053a1e8467ffb02a92bf0e98d5a92"}',1);
        $orderid=$data['orderid'];
        $amount=$data['amount'];
        $areaname=$data['areaname'];
        $customid=$data['customid'];
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$orderid.$amount.$areaname.$customid);

        $this->recordData(json_encode($data).'--'.$this->keycode.'--'.$orderid.'--'.$amount.'--'.$areaname.'--'.$customid.'--'.$checkKey);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
//        $orderid='0001-561b08e4e3d340b0a2c2f86450859e27';
//        $customid=encode('00185514');
//        $amount='10';
//        $areaname='建业小区';
        if(empty($orderid)){
            returnMsg(array('status'=>'02','codemsg'=>'订单编号不能为空'));
        }
        $list=M('eouttrade')->where(array('orderid'=>$orderid))->select();
        if($list!=false){
            returnMsg(array('status'=>'03','codemsg'=>'该订单已同步'));
        }
        if(empty($areaname)){
            returnMsg(array('status'=>'04','codemsg'=>'小区名字为空'));
        }
        $map=array('areaname'=>$areaname);
        $paterInfo=M('wy_area')->where($map)->find();
        if(empty($paterInfo)){
            returnMsg(array('status'=>'05','codemsg'=>'未匹配到对应物业公司'));
        }
        $panterid=$paterInfo['panterid'];
        if(empty($panterid)){
            returnMsg(array('status'=>'011','codemsg'=>'该小区尚未分配物业'));
        }
//        $panterid='00000280';
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'05','codemsg'=>'非法会员编号'));
        }
        $trade=M('trade_wastebooks');
        $eorderid='e+_0001-'.$orderid;
        $map1=array('tw.eorderid'=>$eorderid);
        $list=$trade->alias('tw')->where($map1)->find();
        if($list==false){
            returnMsg(array('status'=>'06','codemsg'=>'查无订单'));
        }
        $customList=$trade->alias('tw')->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($map1)->field('cu.customid')->group('cu.customid')->select();

        if(count($customList)!=1){
            returnMsg(array('status'=>'07','codemsg'=>'异常订单'));
        }
        if($customid!=$customList[0]['customid']){
            returnMsg(array('status'=>'08','codemsg'=>'非本人订单编号'));
        }
        $map2=array('eorderid'=>$eorderid,'tradepoint'=>array('gt',0),'tradeamount'=>0);
        $consumeAmount=$trade->where($map2)->sum('tradepoint');
        //echo $trade->getLastSql();exit;
        if($consumeAmount!=$amount){
            returnMsg(array('status'=>'09','codemsg'=>'消费金额与传入订单金额不符'));
        }

        $this->model->startTrans();
        $tradeSql="update trade_wastebooks set panterid='{$panterid}' where eorderid='{$eorderid}'";
        //echo $tradeSql;exit;
        $tradeIf=$this->model->execute($tradeSql);
        //echo $this->model->getLastSql().'<br/>';
        $tradeid=$list['tradeid'];
        $cardno=$list['cardno'];
        $card=$this->cards->where(array('cardno'=>$cardno))->field('customid')->find();
        $consumeList=M('coin_consume')->where(array('tradeid'=>$tradeid,'cardid'=>$card['customid']))->select();

        $c=0;
        foreach($consumeList as $k=>$v){

            $coinConsumecoinSql="update coin_consume set panterid='{$panterid}'  where coinconsumeid='{$v['coinconsumeid']}' and cardid='{$v['cardid']}'";
            $coinConsumeIf=$this->model->execute($coinConsumecoinSql);
            //echo $this->model->getLastSql().'<br/>';

            if($coinConsumeIf==true){
                $c++;
            }
        }
        $sql="insert into eouttrade values ('','{$panterid}','{$eorderid}','{$list['tradepoint']}','')";
        $eoutIf=$this->model->execute($sql);
        if($c==count($consumeList)&&$tradeIf==true&&$eoutIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'同步成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'010','codemsg'=>'同步失败'));
        }
    }

    public function newtest(){
        set_time_limit(0);
        $array=array();
        $panterid='00000509';
        $userid='0000000000000197';
        //print_r($array);exit;
        foreach($array as $key=>$val){
            $customid=$this->getFieldNextNumber("customid");
            $namechinese='大食堂'.intval($customid);
            $currentDate=date('Ymd',time());
            $this->model->startTrans();
            $sql="INSERT INTO CUSTOMS(customid,namechinese,placeddate,panterid) values ";
            $sql.="('{$customid}','{$namechinese}','{$currentDate}','{$panterid}')";
            $customIf=$this->model->execute($sql);
            if($customIf==true){
                $cardno=$val['cardno'];
                $bool=$this->opencard(array($cardno),$customid,0,$panterid,1,$userid);
                if($bool==true){
                    $this->model->commit();
                    echo $cardno.'开卡成功<br/>';
                }else{
                    $this->model->rollback();
                    echo $cardno.'开卡失败<br/>';
                }
            }else{
                $this->model->rollback();
                echo $cardno.'创建会员失败<br/>';
            }
        }
    }
    public function testtb(){
        $tb_info = D("Ehome")->getTbinfo("00116397");
        dump($tb_info);
    }

    public function test222(){
        set_time_limit(0);
        $array=array(
            array("cardno"=>"6880374800000008007"),
        );
        $cardpl=M('card_purchase_logs');
        $cards=M('cards');
        $sql='';
        foreach($array as $key=>$val){
            $list=$cardpl->where(array('cardno'=>$val['cardno']))->find();
            $card=$cards->alias('c')->join('customs_c cc on cc.cid=c.customid')->field('cc.customid,c.panterid')
                ->where(array('c.cardno'=>$val['cardno']))->find();
            $customplSql="insert into custom_purchase_logs values('".$card['customid']."','".$list['purchaseid']."','".$list['placeddate']."','现金','";
            $customplSql.=$list['userid']."','{$list['amount']}',NULL,'{$list['amount']}',0,'{$list['amount']}','{$list['amount']}'";
            $customplSql.=",1,'','购卡','1','{$card['panterid']}','".$list['userid']."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$list['placeddate']."','".$list['placedtime']."',NULL);";
            $sql.=$customplSql.'<br/>';
        }
        echo $sql;
    }

    public function getJlhInfo(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $linktel=trim($datami['linktel']);
        //$customid=trim('MDAwMDAzNTYO0O0O ');
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$linktel);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
//        $linktel='13425715172';
        if(empty($linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号缺失'));
        }
        $map=array('linktel'=>$linktel,'countrycode'=>'君邻会会员');
        $custom=M('customs')->where($map)->find();
        if($custom==false){
            returnMsg(array('status'=>'04','codemsg'=>'查无此君邻会会员'));
        }
        $card=M('cards')->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where(array('cu.customid'=>$custom['customid'],'c.panterid'=>'00000447'))
            ->field('c.cardno')
            ->find();

        //echo M('cards')->getLastSql();exit;
        returnMsg(array('status'=>'1','customid'=>encode($custom['customid']),'cardno'=>$card['cardno']));
    }
}
?>
