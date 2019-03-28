<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class CardsController extends CoinController{

//    public function test(){
//        $linktel="18697323783";
//        if(!empty($linktel)){
//            $tpl_value="#name#=".urlencode("宋振峰")."&#amount#=100&#point#=20";
//            $result = $this->tpl_send("1515042",$linktel,$tpl_value);
//            $result=json_decode($result,1);
//            print_r($result);
//        }
//    }

    //酒店批量开卡
    public function _initialize(){
        parent::_initialize();
    }

    //酒店卡批量充值
    public function batchRecharge(){
        if(IS_POST){
            if (!empty( $_FILES['file_stu']['name'])){
                $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
                $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
                $file_type = $file_types [count ( $file_types ) - 1];
                /*判别是不是.xls文件，判别是不是excel文件*/
                if (strtolower ( $file_type ) != "xls"&&strtolower ( $file_type ) != "xlsx")
                {
                    $this->error ( '不是Excel文件，重新上传' );
                }
                /*设置上传路径*/
                $savePath = './public/upfile/Excel/';
                /*以时间来命名上传的文件*/
                $str = date ( 'Ymdhis' );
                $file_name = $str . "." . $file_type;
                /*是否上传成功*/
                if (!copy($tmp_file,$savePath.$file_name ))
                {
                    $this->error('上传失败');
                }
                $exceldate=$this->import_excel($savePath.$file_name,1);
                $userid=$this->hotelUserid;
                $c=0;
                $batchbuyBatchLog=array();
                $panterid=$this->hotelPanterid;
                //print_r($exceldate);exit;
                foreach ($exceldate as $key => $value) {
                    $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                    $currentDate = date('Ymd', time());
                    $batchbuyBatchLog[$key]['name']=$namechinese=$value[0];
                    $linktel=strval($value[1]);
                    $personidtype=$value[2];
                    $personid=$value[3];
                    $residaddress=$value[4];
                    $sex=$value[5];
                    $batchbuyBatchLog[$key]['amount']=$amount=$value[6];
                    $batchbuyBatchLog[$key]['cardno']=$cardno=$value[7];
                    $isBill=$value[8]=="是"?1:0;
                    $card=$this->cards->where(array('cardno'=>$cardno))->find();
                    //print_r($card);exit;
                    if($card['status']!='N'&&$card['status']!='Y'){
                        $batchbuyBatchLog[$key]['status']='01';
                        $batchbuyBatchLog[$key]['msg']='至尊卡非正常卡';
                        continue;
                    }
                    $this->model->startTrans();
                    $map['linktel']=$linktel;
                    $map['customlevel']='建业线上会员';
                    $custom=M('customs')->where($map)->field('customid')->find();
                    if($custom==false){
                        //$customid=$this->getnextcode('customs','8');
                        $customid=$this->getFieldNextNumber("customid");
                        $sql="INSERT INTO CUSTOMS(CUSTOMID,NAMECHINESE,LINKTEL,PERSONIDTYPE,PERSONID,RESIDADDRESS,SEX,PANTERID,PLACEDDATE,CUSTOMLEVEL) ";
                        $sql.=" VALUES('{$customid}','{$namechinese}','{$linktel}','{$personidtype}',";
                        $sql.="'{$personid}','{$residaddress}','{$sex}','{$panterid}','{$currentDate}','建业线上会员')";
                    }else{
                        $customid=$custom['customid'];
                        $sql="UPDATE CUSTOMS SET namechinese='{$namechinese}',personidtype='{$personidtype}',";
                        $sql.="personid='{$personid}',residaddress='{$residaddress}',sex='{$sex}' where customid='{$customid}'";
                    }
                    if($this->model->execute($sql)){
                        $recBool=$this->rechargeExe($customid,$card,$amount,$panterid,$isBill,$userid);
                        if($recBool==false){
                            $this->model->rollback();
                            $batchbuyBatchLog[$key]['status']='03';
                            $batchbuyBatchLog[$key]['msg']='充值失败';
                            continue;
                        }
                        $map1=array('panterid'=>$panterid);
                        $point_config=M('point_config')->where($map1)->find();
                        $accountInfo=$this->getpointAccount($cardno);
                        $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
                            'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],'validity'=>$point_config['validity']);
                        $pointAmount=$point_config['zsrate']*$amount;
                        $pointBool=$this->pointRechargeExe($cardInfo,$pointAmount,$panterid);
                        if($pointBool==false){
                            $this->model->rollback();
                            $batchbuyBatchLog[$key]['status']='04';
                            $batchbuyBatchLog[$key]['msg']='积分充值失败';
                            continue;
                        }else{
                            $this->model->commit();
                            $batchbuyBatchLog[$key]['status']='1';
                            $batchbuyBatchLog[$key]['msg']='充值成功';
                            $c++;
                        }
                    }else{
                        $this->model->rollback();
                        $batchbuyBatchLog[$key]['status']='02';
                        $batchbuyBatchLog[$key]['msg']='会员资料添加/更新失败';
                        continue;
                    }
                }
                if(!empty($batchbuyBatchLog)){
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                if($c==0){
                    echo "<script type='text/javascript'>alert('充值完成');</script>";
                    $this->error('充值失败');
                }else{
                    echo "<script type='text/javascript'>alert('充值完成');</script>";
                    $this->success('充值成功'.$c.'张卡,失败'.(count($exceldate)-$c).'张卡');
                }
            }
        }else{
            $this->display();
        }
    }

    //酒店卡单张充值
    public function hotelRecharge(){
        if(IS_POST){
            $namechinese=$_POST['namechinese'];
            $linktel=$_POST['linktel'];
            $personidtype=$_POST['personidtype'];
            $personid=$_POST['personid'];
            $residaddress=$_POST['residaddress'];
            $residaddress=$_POST['residaddress'];
            $amount=$_POST['amount'];
            $isBill=empty($_POST['isBill'])?0:1;
            $cardno=$_POST['cardno'];
            $sex=$_POST['sex'];
            $currentDate=date('Ymd', time());
            $panterid=$this->hotelPanterid;
            $userid=$this->hotelUserid;
            if(!preg_match('/^1[34578]\d{9}$/',$linktel)){
                $this->error('手机号格式有误');
            }
            if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/',$amount)){
                $this->error('充值金额格式有误');
            }
            if($this->checkCardBrand($cardno)==false){
                $this->error('非酒店新卡');
            }
            $card=$this->cards->where(array('cardno'=>$cardno))->find();
            if($card['status']!='N'&&$card['status']!='Y'){
                $this->error('至尊卡非正常卡');
            }
            $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
            $custom=$this->customs->where($map)->find();
            $this->model->startTrans();
            if($custom==false){
                //$customid=$this->getnextcode('customs','8');
                $customid=$this->getFieldNextNumber("customid");
                $sql="INSERT INTO CUSTOMS(CUSTOMID,NAMECHINESE,LINKTEL,PERSONIDTYPE,PERSONID,RESIDADDRESS,SEX,PANTERID,PLACEDDATE,CUSTOMLEVEL) ";
                $sql.=" VALUES('{$customid}','{$namechinese}','{$linktel}','{$personidtype}',";
                $sql.="'{$personid}','{$residaddress}','{$sex}','{$panterid}','{$currentDate}','建业线上会员')";
            }else{
                $where=array('cu.customid'=>$custom['customid'],'c.cardkind'=>'6688','c.cardfee'=>1);
                $c=$this->model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                    ->join('cards c on c.customid=cc.cid')->where($where)->count();
                if($c>0&&$card['status']=='N'){
                    //$customid=$this->getnextcode('customs','8');
                    $customid=$this->getFieldNextNumber("customid");
                    $sql="INSERT INTO CUSTOMS(CUSTOMID,NAMECHINESE,LINKTEL,PERSONIDTYPE,PERSONID,RESIDADDRESS,SEX,PANTERID,PLACEDDATE,CUSTOMLEVEL) ";
                    $sql.=" VALUES('{$customid}','{$namechinese}','{$linktel}','{$personidtype}',";
                    $sql.="'{$personid}','{$residaddress}','{$sex}','{$panterid}','{$currentDate}','建业线上会员')";
                }else{
                    $customid=$custom['customid'];
                    $sql="UPDATE CUSTOMS SET namechinese='{$namechinese}',personidtype='{$personidtype}',";
                    $sql.="personid='{$personid}',residaddress='{$residaddress}',sex='{$sex}' where customid='{$customid}'";
                }
            }
            if($this->model->execute($sql)){
                $recBool=$this->rechargeExe($customid,$card,$amount,$panterid,$isBill,$userid);
                if($recBool==false){
                    $this->model->rollback();
                    $this->error('充值失败');
                }
                $map1=array('panterid'=>$panterid);
                $point_config=M('point_config')->where($map1)->find();
                $accountInfo=$this->getpointAccount($cardno);
                $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
                    'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],'validity'=>$point_config['validity']);
                $pointAmount=$point_config['zsrate']*$amount;
                $pointBool=$this->pointRechargeExe($cardInfo,$pointAmount,$panterid);
                if($pointBool==false){
                    $this->model->rollback();
                    $this->error('积分充值失败');
                }else{
                    $this->model->commit();
                    if(!empty($linktel)){
                        $tpl_value="#name#=".urlencode("{$namechinese}")."&#amount#={$amount}&#point#={$pointAmount}";
                        $result = $this->tpl_send("1515042",$linktel,$tpl_value);
                        $result=json_decode($result,1);
                    }
                    $this->success('充值成功');
                }
            }else{
                $this->error('会员资料添加/更新失败');
            }
        }else{
//            $map=array('hysx'=>'酒店');
//            $list=M('panters')->where($map)->field('namechinese pname,panterid')->select();
            //$this->assign('list',$list);
            $this->display();
        }
    }

    //后台充值执行
    public function rechargeExe($customid,$card,$amount,$panterid,$isBill,$userid){
        $cardno=$card['cardno'];
        if($card['status']=='N'){
            if($amount>5000){
                $openBool1=$this->opencard(array($cardno),$customid,5000,$panterid,$userid,$isBill,'转账');
                $waitAmount=$amount-5000;
                $openNum=$this->getOpenNum($waitAmount);
                $getCards=$this->getCard($openNum,$panterid,$cardno);
                $openBool2=$this->openCard($getCards,$customid,$waitAmount,$panterid,$userid,$isBill,'转账');
                if($openBool1==true&&$openBool2==true){
                    $bool=true;
                }
            }else{
                $bool=$this->opencard(array($cardno),$customid,$amount,$panterid,$userid,$isBill,'转账');
            }
            return $bool;
        }elseif($card['status']=='Y'){
            $cardAccount=$this->cardAccQuery($cardno);
            $recAmount=5000-$cardAccount;
            //echo $amount.'--'.$recAmount;exit;
            if($amount>=$recAmount){
                if($recAmount>0){
                    $recBool1=$this->recharge(array($cardno),$customid,$recAmount,$panterid,$userid,$isBill,'转账');
                }else{
                    $recBool1=true;
                }
                $waitAmount=$amount-$recAmount;
                $usableAccount=$this->getUsableAccount($customid,$cardno);
                $usableCards=$this->getUsableAccCards($customid,$cardno);
                if($waitAmount>$usableAccount){
                    if($usableAccount>0){
                        $recBool=$this->recharge($usableCards,$customid,$usableAccount,$panterid,$userid,$isBill,'转账');
                    }else{
                        $recBool=true;
                    }
                    $remindRecAmount=$waitAmount-$usableAccount;
                    $openNum=$this->getOpenNum($remindRecAmount);
                    $getCards=$this->getCard($openNum,$panterid,$cardno);
                    $openBool=$this->openCard($getCards,$customid,$remindRecAmount,$panterid,$userid,$isBill,'转账');
                    if($recBool==true&&$openBool==true){
                        $recBool2=true;
                        $purchaseArr2=array_merge($recBool,$openBool);
                    }
                }else{
                    $recBool2=$this->recharge($usableCards,$customid,$waitAmount,$panterid,$userid,$isBill,'转账');
                    $purchaseArr2=$recBool2;
                }

                if($recBool1==true&&$recBool2==true){
                    $bool=true;
                }
            }else{
                $bool=$this->recharge(array($cardno),$customid,$amount,$panterid,$userid,$isBill,'转账');
            }
            return $bool;
        }else{
            return false;
        }
    }

    //至尊卡充值接口
    public function cardRecharge(){
        $cardno =$_POST['cardno'];//卡号
        $paymenttype = $_POST['paymenttype'];//现金银行支付的类型
        $totalmoney=$_POST['totalmoney'];//总金额
        $panterid = $_POST['panterid'];//商务号
        $username=$_POST['username'];//操作员的用户名
        $isBill=$_POST['isBill'];
        $key=$_POST['key'];
//        $cardno='6688371800000000001';
//        $totalmoney=5000;
//        $paymenttype='01';
//        $panterid='00000270';
//        $username='kfsummer';
//        $isBill=1;
//        $key='ae174a94170cc77d0f9a663a1e20554f';
        $checkKey=md5($this->keycode.$cardno.$paymenttype.$totalmoney.$panterid.$username.$isBill);
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
        $hysx=$this->getHysx($panterid);
        if($hysx!='酒店'){
            if($totalmoney>5000){
                returnMsg(array('status'=>'04','codemsg'=>'充值金额不能超过5000'));
            }
            $users=M("Users");
            $usersdata=$users->where(array("username"=>$username))->find();
            $userid = $usersdata['userid'];
        }else{
            if($this->checkCardBrand($cardno)==false){
                returnMsg(array('status'=>'05','codemsg'=>'非酒店新卡不能充值'));
            }
            $userid=$this->hotelUserid;
            $panterid=$this->hotelPanterid;
        }
        switch($paymenttype){
            case "00":
                $paymenttype="现金";
                break;
            case "01":
                $paymenttype="银行卡";
                break;
            case "02":
                $paymenttype="支票";
                break;
            case "03":
                $paymenttype="汇款";
                break;
            case "04":
                $paymenttype="网上支付";
                break;
            case "05":
                $paymenttype="转账";
                break;
            case "06":
                $paymenttype="内部转账";
                break;
            case "07":
                $paymenttype="赠送";
                break;
            case "08":
                $paymenttype="其他";
                break;
        }
        $map1=array('panterid'=>$panterid);
        $point_config=M('point_config')->where($map1)->find();
        $accountInfo=$this->getpointAccount($cardno);
        $customid=$custom['customid'];
        $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
            'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],
            'validity'=>$point_config['validity']
        );
        $this->model->startTrans();
        $cardAccount=$this->cardAccQuery($cardno);
        $recAmount=5000-$cardAccount;
        if($totalmoney>=$recAmount){
            if($recAmount>0){
                $recBool1=$this->recharge(array($cardno),$customid,$recAmount,$panterid,$usersdata['userid'],$isBill,$paymenttype);
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
                    $recBool=$this->recharge($usableCards,$customid,$usableAccount,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                    $purchaseArr3=$recBool;
                }else{
                    $recBool=true;
                    $purchaseArr3=array();
                }
                $remindRecAmount=$waitAmount-$usableAccount;
                $openNum=$this->getOpenNum($remindRecAmount);
                $getCards=$this->getCard($openNum,$panterid,$cardno);
                $openBool=$this->openCard($getCards,$customid,$remindRecAmount,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                if($recBool==true&&$openBool==true){
                    $recBool2=true;
                    $purchaseArr2=array_merge($purchaseArr3,$openBool);
                }
            }else{
                $recBool2=$this->recharge($usableCards,$customid,$waitAmount,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                $purchaseArr2=$recBool2;
            }
            if($recBool1==true&&$recBool2==true){
                $bool=true;
                $purchaseArr=array_merge($purchaseArr1,$purchaseArr2);
            }
        }else{
            $bool=$this->recharge(array($cardno),$customid,$totalmoney,$panterid,$usersdata['userid'],$isBill,$paymenttype);
            $purchaseArr=$bool;
        }
        if($this->checkCardBrand($cardno)){
            $point_config=$this->checkPointSendtype($cardno,$panterid);
            $accountInfo=$this->getpointAccount($cardno);
            $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
                'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],'validity'=>$point_config['validity']);
            $pointAmount=floatval($point_config['zsrate'])*$totalmoney;
            $pointBool=$this->pointRechargeExe($cardInfo,$pointAmount,$panterid);
        }else{
            $pointBool=true;
        }
        $url=$this->createErweima($customid,$cardno,$_POST['panterid']);
        if($pointBool==true&&$bool==true){
            $this->model->rollback();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>$purchaseArr,'addpoint'=>$pointAmount,'url'=>$url));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'06','codemsg'=>'充值失败'));
        }
    }

    //获取用户信息之后提交,发卡
    public function customCards(){
        //获取用户的信息之后，会员与卡绑定提交
        $cardno=$_POST['cardno'];//获取的卡号
        $namechinese=$_POST['namechinese'];//用户的姓名
        $residaddress=$_POST['residaddress'];//用户的地址
        $personidexdate=$_POST['personidexdate'];//证件的期限
        //$frontimg=$_POST['frontimg'];//身份证的正面的图片
        //$reserveimg=$_POST['reserveimg'];//身份证的反面的图片
        $linktel=$_POST['linktel'];//用户的联系方式
        $personid=$_POST['personid'];// 证件号
        $order=$_POST['order'];// 订单号
        $panterid=$_POST['panterid'];//商务号
        $totalmoney=$_POST['totalmoney'];//金额
        $paymenttype = $_POST['paymenttype'];//现金银行支付的类型
        $username=$_POST['username'];//操作员的用户名
        $isBill=$_POST['isBill'];
        $key=$_POST['key'];
//        $cardno='6688371800000000001';$order='201607161448434396';$linktel='18613890001';
//        $namechinese='郑世威';$personid='41278198356690873';$residaddress='河南省';$paymenttype='01';
//        $personidexdate='';$panterid='00000270';$username='kfsummer';$totalmoney='1000';$isBill=1;
//        $key='4ee34bf76e3c72b1537d50db8bd0b95c';
        //账号+用户的姓名+用户的地址+证件的期限+联系方式+证件号+订单号+商务号+操作员的用户名+金额+现金银行支付的类型
        $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$order.$panterid.$username.$totalmoney.$paymenttype.$isBill);
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
        $hysx=$this->getHysx($panterid);
        if($hysx!='酒店'){
            if($totalmoney>5000){
                returnMsg(array('status'=>'07','codemsg'=>'充值金额不能超过5000'));
            }
            $users=M("Users");
            $usersdata=$users->where(array("username"=>$username))->find();
            $userid=$usersdata['userid'];
        }else{
            if($this->checkCardBrand($cardno)==false){
                returnMsg(array('status'=>'08','codemsg'=>'非酒店新卡不能充值'));
            }
            $userid=$this->hotelUserid;
            $panterid=$this->hotelPanterid;
        }
        $dept=M("DEPT");
        $strsql="select * from  DEPT where posorder='{$order}'";
        $data=$dept->query($strsql);
        if(!empty($data)){
            $frontimg=trim($data['0']['frontimg']);
            $reserveimg=trim($data['0']['reserveimg']);
        }
        $datapersonif=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $personidif =$this->customs->where($datapersonif)->find();
        $this->model->startTrans();
        if(empty($personidif)){
            $dd['frontimg']=$frontimg;
            $dd['reserveimg']=$reserveimg;
            $dd['namechinese']=$namechinese;
            $dd['residaddress']=$residaddress;
            $dd['personidexdate']=$personidexdate;
            $dd['linktel']=$linktel;
            $dd['panterid']=$panterid;
            $customid=$this->addcustoms($dd);
            if($customid){
                if($card['status']=='N'){
                    if($totalmoney>5000){
                        $openBool1=$this->opencard(array($cardno),$customid,5000,$panterid,$userid,$isBill,$paymenttype);
                        $waitAmount=$totalmoney-5000;
                        $openNum=$this->getOpenNum($waitAmount);
                        $getCards=$this->getCard($openNum,$panterid,$cardno);
                        $openBool2=$this->openCard($getCards,$customid,$waitAmount,$panterid,$userid,$isBill,$paymenttype);
                        if($openBool1==true&&$openBool2==true){
                            $bool=true;
                            $purchaseArr=array_merge($openBool1,$openBool2);
                        }
                    }else{
                        $bool=$this->opencard(array($cardno),$customid,5000,$panterid,$userid,$isBill,$paymenttype);
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
            $olddata['paymenttype']=$paymenttype;
            $olddata['panterid']=$panterid;
            $olddata['customid']=$customid;
            if($card['status']=='N'){
                if($totalmoney>5000){
                    $openBool1=$this->opencard(array($cardno),$customid,5000,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                    $waitAmount=$totalmoney-5000;
                    $openNum=$this->getOpenNum($waitAmount);
                    $getCards=$this->getCard($openNum,$panterid,$cardno);
                    $openBool2=$this->openCard($getCards,$customid,$waitAmount,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                    if($openBool1==true&&$openBool2==true){
                        $bool=true;
                        $purchaseArr=array_merge($openBool1,$openBool2);
                    }
                }else{
                    $bool=$this->opencard(array($cardno),$customid,5000,$panterid,$usersdata['userid'],$isBill,$paymenttype);
                    $purchaseArr=$bool;
                }
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'013','codemsg'=>'非新卡不能开卡，请去充值界面操作'));
            }
        }
        if($this->checkCardBrand($cardno)){
            $point_config=$this->checkPointSendtype($cardno,$panterid);
            $accountInfo=$this->getpointAccount($cardno);
            $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
                'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],'validity'=>$point_config['validity']);
            $pointAmount=floatval($point_config['zsrate'])*$totalmoney;
            $pointBool=$this->pointRechargeExe($cardInfo,$pointAmount,$panterid);
        }else{
            $pointBool=true;
        }
        $url=$this->createErweima($customid,$cardno,$_POST['panterid']);
        if($pointBool==true&&$bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>$purchaseArr,'addpoint'=>$pointAmount,'url'=>$url));
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
        $arr= array('00000270','00000118','00000127','00000125','00000126');
        $boolif = in_array($panterid,$arr);
        if(!empty($personid)||!empty($linktel)) {
            if(!$boolif){
                $datapersonif['personid'] = $personid;
                $personidif = $customs->where($datapersonif)->find();
                if($personidif) {
                    returnMsg(array('status'=>'09','codemsg'=>'身份证已经绑定，请更换'));
                }
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
        $ccu=$customs->execute($sql);
        if($ccu){
            return $customid;
        }else{
            return false;
        }
    }

    //验证卡是否为酒店特殊卡号
    public function examCard(){
        $cardno =$_POST['cardno'];//卡号
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if($this->checkCardBrand($cardno)){
            returnMsg(array('status'=>'1','codemsg'=>'特殊卡号段'));
        }else{
            returnMsg(array('status'=>'02','codemsg'=>'正常卡号段'));
        }
    }

    public function createErweima($customid,$cardno,$panterid){
        $custom=$this->customs->where(array('customid'=>$customid))->field('linktel')->find();
        $datadd=array(
            "mobile"=> $custom['linktel'], //手机号
            "cardno"=> $cardno, //卡号
            "phone_mob"=>$custom['linktel'],
            "panterid"=>$panterid,
            "customid"=>$customid //会员编号
        );
        $datadd['key']=md5($this->keycode.$custom['linktel'].$cardno.$custom['linktel'].$panterid.$customid);
        $url="http://kabao.9617777.com/kabao1/index.php?app=erweima&act=index";  //调用卡系统的接口地址8091测试端口   8080正式端口
        $result1= crul_post($url,$datadd);
        $redd=json_decode($result1,true);
        $url=$redd['url'];
        return $url;
    }

    function batchbuyLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='姓名：'.$val['name']."  ";
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='充值数量：'.$val['amount']."  ";
            if($val['status']!=1){
                $msgString.='状态：充值失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }else{
                $msgString.='状态：充值成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $logPath=PUBLIC_PATH.'logs/hotelrecharge/';
        $month=date('Ym',time());
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$msgString,FILE_APPEND);
    }

    public function import_excel($filename,$qshnum=1){
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel2007.php");
        $PHPExcel = new \PHPExcel();
        /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($filename)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filename)){
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filename);
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $currentDate=$currentSheet->toArray();
        $erp_orders_id=array();
        foreach($currentDate as $key=>$val){
            if($key>=$qshnum){
                $testString=implode('',$val);
                if(empty($testString)){
                    continue;
                }else{
                    $erp_orders_id[]=$val;
                }
            }
        }
        return $erp_orders_id;
    }

    function tpl_send($tpl_id,$mobile,$tpl_value){
        $data = array (
            "apikey" =>'b9ede309143b6931de5d41abbd947abc',
            "tpl_id" =>$tpl_id,
            "mobile" =>$mobile,
            "tpl_value"=>$tpl_value
        );
        $ch = curl_init("https://sms.yunpian.com/v1/sms/tpl_send.json");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res=curl_exec($ch);
        return $res;
    }
    //批量更新会员信息excel
    function eedd(){
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel2007.php");
        $PHPExcel = new \PHPExcel();
        /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        $filename=PUBLIC_PATH.'excel/jsjs.xls';
        if(!$PHPReader->canRead($filename)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filename)){
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filename);
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $currentDate=$currentSheet->toArray();
        $customs = M('customs');
        $sql = '';
//        dump($currentDate);exit;
        foreach ($currentDate as $key => $val) {
            $where['customid'] =  trim($val[0]);
            $map['namechinese'] = $val[1];
            $map['linktel'] = $val[2];
            $map['personid'] = $val[3];
            $map['pidt'] = $val[4];
            $map['customlevel'] = $val[5];
            $map['countrycode'] = $val[6];
            $map['birthday'] = $val[7];
            $map['sex'] = $val[8];
            $map['pedd'] = $val[9];
            $map['peded'] = $val[10];
            $map['readd'] = $val[11];
            $map['aplysrc'] = $val[12];
            $map['careertype'] = $val[13];
//            dump($map);exit;
            $custom=$customs->where($where)->save($map);
//            dump($customs->getLastSql());exit;
            if($custom){
                echo '执行成功！';
            }else{
                echo  '执行失败！';
            }
        }
    }
  }
