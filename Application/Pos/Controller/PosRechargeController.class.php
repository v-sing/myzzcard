<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class PosRechargeController extends CoinController{

    public function _initialize(){
        parent::_initialize();
    }


    //至尊卡充值接口
    public function cardRecharge(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        //$data=json_decode('{"cardno":"6668371800000000002","totalmoney":"5001","panterid":"00000546","key":"f7979029e419fc154ac261ef8dfafaa5"}',1);
        //$data=json_decode('{"panterid":"00000734","totalmoney":"0.01","key":"44f8340f885d12a2ac183a19d072e639","cardno":"6888374999900031361"}',1);
        $cardno =$data['cardno'];//卡号
        $totalmoney=$data['totalmoney'];//总金额
        $panterid = $data['panterid'];//商户编号
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$totalmoney.$panterid);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $data1['cardno'] = $cardno;
        $cards=M("Cards");//卡表
        $cards_data=$cards->where($data1)->find();
        $customid1=$cards_data['customid'];
        $status=$cards_data['status'];
        if($status!="Y"){  //不是正常卡不能充值
            returnMsg(array('status'=>'02','codemsg'=>'非正常卡，不能充值'));
        }
        $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array("c.customid"=>$customid1))
            ->field('c.cardno,c.customid cardid,cu.customid')->find();
        if(empty($custom['customid'])){
            returnMsg(array('status'=>'03','codemsg'=>'会员不存在'));
        }
        $customid=$custom['customid'];
        $userid='0000000000';
        $this->model->startTrans();
        $cardAccount=$this->cardAccQuery($cardno);
        $recAmount=5000-$cardAccount;
        if($totalmoney>$recAmount){
            if($recAmount>0){
                $recBool1=$this->recharge(array($cardno),$customid,$recAmount,$panterid,$userid,'');
                $purchaseArr1=$recBool1;
            }else{
                $recBool1=true;
                $purchaseArr1=array();
            }
            $waitAmount=$totalmoney-$recAmount;
            $usableAccount=$this->getUsableAccount($customid,$cardno);
            $usableCards=$this->getUsableAccCards($customid,$cardno);
            if($waitAmount>$usableAccount){
                if($usableAccount>0){
                    $recBool=$this->recharge($usableCards,$customid,$usableAccount,$panterid,$userid,'');
                    $purchaseArr3=$recBool;
                }else{
                    $recBool=true;
                    $purchaseArr3=array();
                }
                $remindRecAmount=$waitAmount-$usableAccount;
                $openNum=$this->getOpenNum($remindRecAmount);
                $getCards=$this->getCard($openNum,$panterid,$cardno);
                //print_r($getCards);exit;
                $openBool=$this->openCard($getCards,$customid,$remindRecAmount,$panterid,$userid,'');
                if($recBool==true&&$openBool==true){
                    $recBool2=true;
                    $purchaseArr2=array_merge($purchaseArr3,$openBool);
                }
            }else{
                $recBool2=$this->recharge($usableCards,$customid,$waitAmount,$panterid,$userid,'');
                $purchaseArr2=$recBool2;
            }
            if($recBool1==true&&$recBool2==true){
                $bool=true;
                $purchaseArr=array_merge($purchaseArr1,$purchaseArr2);
            }
        }else{
            $bool=$this->recharge(array($cardno),$customid,$totalmoney,$panterid,$userid,'');
            $purchaseArr=$bool;
        }
        if($bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>''));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'06','codemsg'=>'充值失败'));
        }
    }

    //获取用户信息之后提交,发卡
    public function customCards(){
        $data=getPostJson();

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        $this->recordData(json_encode($data));
        //获取用户的信息之后，会员与卡绑定提交
        //$data=json_decode('{"totalmoney":"5001.00","residaddress":"郑州市","panterid":"00002233","order":"201707161307184101","personid":"410102199410030217","cardno":"6886371888800184274","linktel":"15981907194","key":"f62c3897a434ab384290c6fb4b448d0a","personidexdate":"2012","namechinese":"彭程"}',1);
        $cardno=$data['cardno'];//获取的卡号
        $namechinese=$data['namechinese'];//用户的姓名
        $residaddress=$data['residaddress'];//用户的地址
        $personidexdate=$data['personidexdate'];//证件的期限
        $linktel=$data['linktel'];//用户的联系方式
        $personid=$data['personid'];// 证件号
        $order=$data['order'];// 订单号
        $panterid=$data['panterid'];//商户号
        $totalmoney=$data['totalmoney'];//开卡金额
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$order.$panterid.$totalmoney);
        //echo $checkKey.'--'.$key;exit;
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
        }
        if(empty($namechinese)){
            returnMsg(array('status'=>'03','codemsg'=>'用户名不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'04','codemsg'=>'手机号不能为空'));
        }
        if(empty($personid)){
            returnMsg(array('status'=>'05','codemsg'=>'证件号不能为空'));
        }
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'06','codemsg'=>'无效卡号'));
        }
        $userid='000000000000000';
        $datapersonif=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $personidif =$this->customs->where($datapersonif)->find();
        $this->model->startTrans();
        if(empty($personidif)){
            $dd['namechinese']=$namechinese;
            $dd['residaddress']=$residaddress;
            $dd['personidexdate']=$personidexdate;
            $dd['linktel']=$linktel;
            $dd['panterid']=$panterid;
            $customid=$this->addcustoms($dd);
            if($customid){
                if($card['status']=='N'){
                    if($totalmoney>5000){
                        $openBool1=$this->opencard(array($cardno),$customid,5000,$panterid,$userid,'');
                        $waitAmount=$totalmoney-5000;
                        $openNum=$this->getOpenNum($waitAmount);
                        $getCards=$this->getCard($openNum,$panterid,$cardno);
                        $openBool2=$this->openCard($getCards,$customid,$waitAmount,$panterid,$userid,'');
                        if($openBool1==true&&$openBool2==true){
                            $bool=true;
                            $purchaseArr=array_merge($openBool1,$openBool2);
                        }
                    }else{
                        $bool=$this->opencard(array($cardno),$customid,$totalmoney,$panterid,$userid,'');
                        $purchaseArr=$bool;
                    }
                }else{
                    returnMsg(array('status'=>'013','codemsg'=>'非新卡不能开卡，请去充值界面操作'));
                }
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'011','codemsg'=>'会员添加失败'));
            }
        }else{
            $customid=$personidif['customid'];
            if(empty($personidif['namechinese'])){
                $dataall['namechinese']=$namechinese;
            }
            if(empty($personidif['residaddress'])){
                $dataall['residaddress']=$residaddress;
            }
            if(empty($personidif['personid'])){
                $dataall['personid']=$personid;
            }
            if(!empty($frontimg)){
                $dataall['frontimg']=$frontimg;
            }
            if(!empty($reserveimg)){
                $dataall['reserveimg']=$reserveimg;
            }
            if(!empty($dataall)){
                $dd=$this->customs->where(array("customid"=>$customid))->save($dataall);
                if(!$dd){
                    $this->model->rollback();
                    returnMsg(array('status'=>'012','codemsg'=>'会员更改失败'));
                }
            }
            $olddata['cardno']=$cardno;
            $olddata['totalmoney']=$totalmoney;
            $olddata['panterid']=$panterid;
            $olddata['customid']=$customid;
            if($card['status']=='N'){
                if($totalmoney>5000){
                    $openBool1=$this->opencard(array($cardno),$customid,5000,$panterid,$userid,'');
                    $waitAmount=$totalmoney-5000;
                    $openNum=$this->getOpenNum($waitAmount);
                    $getCards=$this->getCard($openNum,$panterid,$cardno);
                    $openBool2=$this->openCard($getCards,$customid,$waitAmount,$panterid,$userid,'');
                    if($openBool1==true&&$openBool2==true){
                        $bool=true;
                        $purchaseArr=array_merge($openBool1,$openBool2);
                    }
                }else{
                    $bool=$this->opencard(array($cardno),$customid,$totalmoney,$panterid,$userid,'');
                    $purchaseArr=$bool;
                }
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'013','codemsg'=>'非新卡不能开卡，请去充值界面操作'));
            }
        }
        if($bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>''));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'014','codemsg'=>'充值失败'));
        }
    }

    //添加会员的信息
    public function addcustoms($data){
        $customs = M('customs');
        $linktel=$data['linktel'];
        $personid=$data['personid'];
        $personidexdate=trim($data['personidexdate']);
        $namechinese=$data['namechinese'];
        $frontimg=$data['frontimg'];
        $reserveimg=$data['reserveimg'];
        $residaddress=$data['residaddress'];
        $panterid=$data['panterid'];
        if(!empty($personid)||!empty($linktel)) {
            $datapersonif['personid'] = $personid;
            $personidif = $customs->where($datapersonif)->find();
            if($personidif) {
                returnMsg(array('status'=>'09','codemsg'=>'身份证已经绑定，请更换'));
            }
        }
        //最后判断身份证的数据有没有填写，并上传服务器
        $where=array();
        $where['namechinese']=array('like','%'.$namechinese.'%');
        $where['personid'] = $personid;
        $where['_logic'] = 'or';
        $seach_con['_string']="namechinese ='{$namechinese}' OR personid='{$personid}'";
        $searchs=M('searchs');
        $search=$searchs->where($seach_con)->select();
        if($search!=false){
            returnMsg(array('status'=>'010','codemsg'=>'该用户疑似恐怖分子，请速与警方联系'));
        }
        $currentDate=date('Ymd',time());
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
        $sql='insert into customs(customid,namechinese,linktel,personid,';
        $sql.="residaddress,panterid,placeddate,frontimg,reserveimg,personidexdate,customlevel)values('{$customid}','{$namechinese}','{$linktel}',";
        $sql.="'{$personid}','{$residaddress}','{$panterid}','{$currentDate}','{$frontimg}','{$reserveimg}','{$personidexdate}','建业线上会员')";
        $this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
        $ccu=$customs->execute($sql);
        if($ccu){
            return $customid;
        }else{
            return false;
        }
    }

    //获取卡激活情况
    public function getCardInfo(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        //$data=json_decode('{"key":"70b11f0ffd92f6478b52d067dbd4721d","cardno":"6888371800000027769"}',1);
        $cardno =$data['cardno'];//卡号
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno);
        $this->recordData(json_encode($data),'');
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6666371800000000002';
        $card=$this->cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']=='N'){
            returnMsg(array('status'=>'03','codemsg'=>'待激活卡'));
        }elseif($card['status']=='Y'){
            $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cc.customid=cu.customid')
                ->field('cu.namechinese name,cu.sex,cu.linktel,cu.personid,cu.birthday,cu.personidissuedate,cu.personidexdate,cu.residaddress')
                ->where(array('c.cardno'=>$cardno))->find();
            returnMsg(array('status'=>'1','info'=>$custom));
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'锁定卡'));
        }
    }

    //充值接口
    public function customRecharge(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        $customid=trim($data['customid']);//会员编号
        $amount=trim($data['amount']);//充值金额
        $panterid=trim($data['panterid']);//商户编号

        $type=trim($data['type']);//红包类型
        $key=trim($data['key']);
        $sourceRechargeId=trim($data['sourceRechargeId']);
        $checkKey=md5($this->keycode.$customid.$amount.$sourceRechargeId.$panterid.$type);
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
        if($type==1) $paymenttype='现金红包';
        $this->model->startTrans();
        if($usableRecharge<$amount){
            if($usableRecharge>0){
                $rechargeBool=$this->recharge($ownCards,$customid,$usableRecharge,$sourceRechargeId,$userid,'',$paymenttype);
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
            $openBool=$this->openCard($getCards,$customid,$openRechargeAmount,$panterid,2,$sourceRechargeId,$userid,'',$paymenttype);
            if($rechargeBool==true&&$openBool==true){
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功'));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'充值失败'));
            }
        }else{
            if(empty($ownCards)){
                returnMsg(array('status'=>'07','codemsg'=>'用户无关联至尊卡'));
            }
            $rechargeBool=$this->recharge($ownCards,$customid,$amount,'out_'.$sourceRechargeId,$userid,'',$paymenttype);
            if($rechargeBool==true){
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>''));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'充值失败'));
            }
        }
    }

  }
