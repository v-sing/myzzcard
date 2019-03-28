<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/5 0005
 * Time: 下午 4:40
 */
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class BlueController extends CoinController{
    // 获取卡号
    public function getCards(){
        header('Content-type: text/json');
        $data=json_decode(file_get_contents('php://input'),true);

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        //  $datami='{"cardno":"6889396888800153272","key":"9d21d339d4c240d968702fe78dce062b"}';
        $this->recordData($data);
//        $datami = json_decode($data,1);
        $namechinese=trim($data['namechinese']);//用户的姓名
        $residaddress=trim($data['residaddress']);//用户的地址
        $linktel=trim($data['linktel']);//用户的联系方式
        $personid=trim($data['personid']);// 证件号
//      returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
        //  $panterid=trim($data['panterid']);//商务号
        $panterid='00000219';
        $totalmoney=trim($data['totalmoney']);//金额
        //     $paymenttype = trim($data['paymenttype']);//现金银行支付的类型
        $cardno=trim($data['cardno']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno);
//        $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$panterid.$totalmoney.$paymenttype);
        if(preg_match("/^(6888)(\d{15})$/",$cardno)){
            $customid = $this->getCustomid($cardno);
            if($totalmoney>0){

                //	returnMsg(array('status'=>'03','codemsg'=>'11'));
                if($customid == false || empty($customid)){
                    //echo 'yy';exit;
                    $this->recordData($cardno);
                    if(!empty($namechinese)){
                        //echo $namechinese;exit;
                        $this->openCards($cardno,$namechinese,$residaddress,$linktel,$personid,$panterid,$totalmoney,
                            $paymenttype='00');
                    }

                }else{
                    //echo 'qq';exit;
                    $this->cardRecharge($cardno,$panterid,$totalmoney,$paymenttype='00');
                }

            }elseif($totalmoney=0 && !empty($namechinese)){
                //	echo 'rr';exit;
                //	returnMsg(array('status'=>'04','codemsg'=>''));
                if($customid == false || empty($customid)){
                    $this->recordData($cardno);
                    returnMsg(array('status'=>'00211','codemsg'=>'开卡充值金额不能为0'));
                    //        $this->openCards($cardno,$namechinese,$residaddress,$linktel,$personid,$panterid,$totalmoney,
                    //           $paymenttype='00');
                }
            }elseif(empty($totalmoney) && empty($namechinese)){
                //echo 'pp';exit;
                //returnMsg(array('status'=>'05','codemsg'=>'33'));
                if($checkKey!=$key){
                    $this->recoreIp();
                    returnMsg(array('status'=>'01','data'=>$key,'data1'=>$cardno,'data2'=>$data,'codemsg'=>'无效秘钥，非法传入'));
                    // returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
                }
                $map=array('cardno'=>$cardno);
                $card=$this->cards->where($map)->find();
                if($card==false){
                    $this->recoreIp();
//            echo '1212';exit;
                    returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
                }
                if($customid == false || empty($customid)){
                    $this->recordData($cardno);
                    returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
                }else{
                    $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
                        ->join('cards c on cc.cid=c.customid')
                        ->where($map)->sum('a.amount');
                    returnMsg(array('status'=>'12','amount'=>$account,'codemsg'=>'该卡已开卡'));
                }
//                $this->openCards($cardno,$namechinese,$residaddress,$linktel,$personid,$panterid,$totalmoney,$paymenttype);
            }

        }else{
            returnMsg(array('status'=>'04','codemsg'=>'卡号不支持该项目'));
        }
    }
    // 查看卡号并开卡
    protected function openCards($cardno,$namechinese,$residaddress,$linktel,$personid,$panterid,$totalmoney,
                                 $paymenttype){
        if(empty($cardno)){
            $where['cardno']=$cardno;
            returnMsg(array('status'=>'05','codemsg'=>'卡号不能为空'));
        }
        if(empty($namechinese)){
            $where['namechinese']=$namechinese;
            returnMsg(array('status'=>'06','codemsg'=>'购卡人不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'07','codemsg'=>'购卡人手机号不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'08','codemsg'=>'购卡人手机号不正确'));
        }

//        if(empty($personid)){
//            returnMsg(array('status'=>'09','codemsg'=>'证件号不能为空'));
//        }
//        if(!preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/",$personid)){
//            returnMsg(array('status'=>'001','codemsg'=>'证件号不正确'));
//        }
//        if(empty($residaddress)) {
//            $where['residaddress']=$residaddress;
//            returnMsg(array('status'=>'002','codemsg'=>'证件地址不能为空'));
//        }
        $where['cardno']=$cardno;
        $status=$this->cards->where($where)->field('status')->find();
        //dump($status);exit;
        if($status['status']=="N"){
//            $map=array('linktel'=>$linktel);
//            $mob=$this->customs->where($map)->find();
//            if($mob==true){
//                returnMsg(array('status'=>'003','codemsg'=>'手机号已绑定，请更换'));
//            }
//            $map1=array('personid'=>$personid);
//            $person=$this->customs->where($map1)->find();
//	//		echo M('customs')->getLastSql();exit;
//		   // var_dump($person);exit;
//            if($person){
//                returnMsg(array('status'=>'004','codemsg'=>'证件号已绑定，请更换'));
//            }
            //       插入购卡人信息 绑定会员id
            $currentDate = date('Ymd',time());
            $this->model->startTrans();
            $customid = $this->getFieldNextNumber('customid');
            //	echo $customid;exit;
            $gCard="insert into purcha_card(cardno,customid,namechinese,linktel,personid,address,placeddate,placedtime)values('".$cardno."','".$customid."','".$namechinese."','".$linktel."','".$personid."','".$residaddress."','".$currentDate."','".date('H:i:s')."')";
            $gcardIf=$this->model->execute($gCard);
            $bingSql = "insert into customs(customid,customlevel,countrycode,panterid,placeddate) values('".$customid ."','建业线上会员','青蓝社会员',
'".$panterid."','".$currentDate."')";
            $this->recordError("注册SQL：" . serialize($bingSql) . "\n\t", "YjTbpost", "createAccount");
            $custIf=$this->model->execute($bingSql);

            if($custIf==true && $gcardIf==true){
                //	echo '111';exit;
                //  $this->model->commit();
                $userid = $this->userid;
                //	echo $userid;exit;
                $cardNum  =  $this->getCardNum($totalmoney);
                //echo $cardNum;exit;
                if ($cardNum >1){
                    $cardArr  = $this->getCardNo( $cardNum - 1 ,$panterid);
                    //   dump($cardArr);exit;
                    if (!$cardArr){
                        returnMsg(array('status'=>'05','codemsg'=>'卡池数量不足'));
                    }

                }
                $cardArr[]= $cardno;
                //dump($cardArr);exit;
                $n=$this->openCardno($cardArr,$customid,$totalmoney,$panterid,$userid,$isBill=null,$paymenttype);
                //dump($n);exit;
                if($n==true){
                    $this->model->commit();
                    returnMsg(array('status'=>'005','codemsg'=>'绑定开卡充值成功','purchaseId'=>$n));

                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'006','codemsg'=>'绑定开卡充值失败'));

                }
                returnMsg(array('status'=>'007','codemsg'=>'绑定会员成功'));

            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'008','codemsg'=>'绑定会员失败'));

            }

        }else{
            returnMsg(array('status'=>'009','codemsg'=>'该卡不是新卡'));
        }
    }
    //开卡
    public function openCardss(){
        $data=json_decode(file_get_contents('php://input'),true);
        $cardno=trim($data['cardno']);//获取的卡号
        //获取用户的信息之后，会员与卡绑定提交
        $this->recordData($data);
        $namechinese=trim($data['namechinese']);//用户的姓名
        $residaddress=trim($data['residaddress']);//用户的地址
        $linktel=trim($data['linktel']);//用户的联系方式
        $personid=trim($data['personid']);// 证件号
        // $panterid=trim($data['panterid']);//商务号
        $panterid='00000219';
        $totalmoney=trim($data['totalmoney']);//金额
        $paymenttype = trim($data['paymenttype']);//现金银行支付的类型
        $key=trim($data['key']);

//        $key='4a615d9f8679688df31437913f7ed02b';
        $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$totalmoney.$paymenttype);
        //    echo $checkKey;exit;
        if($key!=$checkKey){
            returnMsg(array('status'=>'010','codemsg'=>'无效秘钥，非法传入'));

        }
        if(empty($cardno)){
            $where['cardno']=$cardno;
            returnMsg(array('status'=>'020','codemsg'=>'卡号不能为空'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            $this->recoreIp();
            returnMsg(array('status'=>'030','codemsg'=>'非法卡号'));
        }
        if(empty($namechinese)){
            $where['namechinese']=$namechinese;
            returnMsg(array('status'=>'040','codemsg'=>'用户名不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'050','codemsg'=>'手机号不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'060','codemsg'=>'手机号不正确'));
        }

//        if(empty($personid)){
//            returnMsg(array('status'=>'070','codemsg'=>'证件号不能为空'));
//        }
//        if(!preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/",$personid)){
//            returnMsg(array('status'=>'080','codemsg'=>'证件号不正确'));
//        }
//        if(empty($residaddress)) {
//            $where['residaddress']=$residaddress;
//            returnMsg(array('status'=>'090','codemsg'=>'证件地址不能为空'));
//        }
        $where['cardno']=$cardno;
        $status=$this->cards->where($where)->field('status')->find();
        if($status['status']=="N"){
//            $map=array('linktel'=>$linktel);
//            $mob=$this->customs->where($map)->find();
//            if($mob==true){
//                returnMsg(array('status'=>'011','codemsg'=>'手机号已绑定，请更换'));
//            }
//            $map1=array('personid'=>$personid);
//            $person=$this->customs->where($map1)->find();
//            if($person){
//                returnMsg(array('status'=>'012','codemsg'=>'证件号已绑定，请更换'));
//            }
            //        绑定会员信息
            $currentDate = date('Ymd',time());
            $this->model->startTrans();
            $customid = $this->getFieldNextNumber('customid');
            $gCard="insert into purcha_card(cardno,customid,namechinese,linktel,personid,address,placeddate,placedtime)values('".$cardno."','".$customid."','".$namechinese."','".$linktel."','".$personid."','".$residaddress."','".$currentDate."','".date('H:i:s')."')";
            $gcardIf=$this->model->execute($gCard);
            $bingSql = "insert into customs(customid,customlevel,countrycode,panterid,placeddate) values('".$customid ."','建业线上会员','青蓝社会员',
'".$panterid."','".$currentDate."')";
            $custIf=$this->model->execute($bingSql);

            if($custIf==true && $gcardIf==true){
                // $this->model->commit();
                $userid = $this->userid;
                $cardNum  =  $this->getCardNum($totalmoney);
                //echo $cardNum;exit;
                if ($cardNum >1){
                    $cardArr  = $this->getCardNo( $cardNum - 1 ,$panterid);
                    //   dump($cardArr);exit;
                    if (!$cardArr){
                        returnMsg(array('status'=>'05','codemsg'=>'卡池数量不足'));
                    }

                }
                $cardArr[]= $cardno;
                $n=$this->openCardno($cardArr,$customid,$totalmoney,$panterid,$userid,$isBill=null,$paymenttype);
                if($n==true){
                    $this->model->commit();
                    returnMsg(array('status'=>'013','codemsg'=>'绑定开卡充值成功','purchaseId'=>$n));

                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'014','codemsg'=>'绑定开卡充值失败'));

                }
                returnMsg(array('status'=>'015','codemsg'=>'绑定会员成功'));

            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'016','codemsg'=>'绑定会员失败'));

            }

        }else{
            returnMsg(array('status'=>'017','codemsg'=>'该卡不是新卡'));
        }
    }
    //至尊卡充值接口
    public function cardRecharge($cardno,$panterid,$totalmoney, $paymenttype){
        $data1['cardno'] = $cardno;
        $cards=M("Cards");//卡表
        $cards_data=$cards->where($data1)->find();
        $customid1=$cards_data['customid'];
        $status=$cards_data['status'];
        $userid = '0000000000000000';
        if($status!="Y"){  //不是正常卡不能充值
            returnMsg(array('status'=>'018','codemsg'=>'非正常卡，不能充值'));
        }
        if($cards_data['exdate']<date('Ymd',time())){
            returnMsg(array('status'=>'019','codemsg'=>'卡号已过期'));
        }
        $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array("c.customid"=>$customid1))
            ->field('c.cardno,c.customid cardid,cu.customid')->find();
        if(empty($custom['customid'])){
            returnMsg(array('status'=>'021','codemsg'=>'会员不存在'));
        }
//        if($totalmoney>5000){
//                returnMsg(array('status'=>'022','codemsg'=>'充值金额不能超过5000'));
//            }

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
        $customid=$custom['customid'];
        $this->model->startTrans();
        $cardAccount=$this->cardAccQuery($cardno);
        $recAmount=5000-$cardAccount;
        if($totalmoney>=$recAmount){
            if($recAmount>0){
                $recBool1=$this->recharge(array($cardno),$customid,$recAmount,$panterid,$userid,'',$paymenttype);
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
                    $recBool=$this->recharge($usableCards,$customid,$usableAccount,$panterid,$userid,'',$paymenttype);
                    $purchaseArr3=$recBool;
                }else{
                    $recBool=true;
                    $purchaseArr3=array();
                }
                $remindRecAmount=$waitAmount-$usableAccount;
                $openNum=$this->getCardNum($remindRecAmount);
                $getCards=$this->getCardNo($openNum,$panterid,'6888');
                $openBool=$this->openCardno($getCards,$customid,$remindRecAmount,$panterid,$userid,'',$paymenttype);
                if($recBool==true&&$openBool==true){
                    $recBool2=true;
                    $purchaseArr2=array_merge($purchaseArr3,$openBool);
                }
            }else{
                $recBool2=$this->recharge($usableCards,$customid,$waitAmount,$panterid,$userid,'',$paymenttype);
                $purchaseArr2=$recBool2;
            }
            if($recBool1==true&&$recBool2==true){
                $bool=true;
                $purchaseArr=array_merge($purchaseArr1,$purchaseArr2);
            }
        }else{
            $bool=$this->recharge(array($cardno),$customid,$totalmoney,$panterid,$userid,'',$paymenttype);
            $purchaseArr=$bool;
        }
        if($bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'023','codemsg'=>'充值成功','purchaseId'=>$purchaseArr));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'024','codemsg'=>'充值失败'));
        }
    }
    //开卡执行
    protected function openCardno($cardArr,$customid,$amount=0,$panterid=null,$userid,$isBill=null,$paymenttype='现金'){
        if(empty($cardArr)) return false;
        $rechargedAmount=0;
        $purchaseArr=array();
        foreach($cardArr as $val){
            $waitMoney=$amount-$rechargedAmount;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');
            $where['cardno']=$cardno;
            //dump($cardno);exit;
            $cardinfo=M('cards')->where($where)->field('panterid')->find();
            //	dump($cardinfo);exit;
            if($amount==0){
                $rechargeMoney=0;
            }else{
                if($waitMoney>=5000){
                    $rechargeMoney=5000;
                }else{
                    $rechargeMoney=$waitMoney;
                }
            }
            //写入购卡单并审核
            $customplSql="insert into custom_purchase_logs(CUSTOMID,PURCHASEID,PLACEDDATE,PAYMENTTYPE,USERID,AMOUNT,CARD_NUM,TOTALMONEY,";
            $customplSql.="POINT,REALAMOUNT,WRITEAMOUNT,WRITENUMBER,CHECKNO,DESCRIPTION,FLAG,PANTERID,CHECKID,DESCRIPTION1,";
            $customplSql.="TRADEFLAG,CUSTOMFLAG,DAYFLAG,TERMPOSNO,PLACEDTIME,CHECKDATE,CHECKTIME,ADITID) ";
            $customplSql.="VALUES('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
            $customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
            $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
            $customplIf=$this->model->execute($customplSql);
            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs(auditid,purchaseid,TYPE,decription,placeddate,audituser,placedtime) values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

            $auditlogsIf=$this->model->execute($auditlogsSql);
            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            //写入购卡充值单
            $cardplSql="INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
            $cardplSql.="FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台开卡',";
            $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);
            //dump($cardplIf);exit;
            $where1['customid']=$customid;
            $card=$this->cards->where($where1)->find();
            //dump($card);exit;
            if($card==false){
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                //	echo '222';exit;
                $cardId=$customid;
            }else{
                //若存在，则需另外生成卡编号
                //$cardId=$this->getnextcode('customs',8);
                //	echo '333';exit;
                $cardId=$this->getFieldNextNumber("customid");
                $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
                $customIf=$this->model->execute($customSql);
            }
            //echo $cardId;exit;
            //执行激活操作
            $cardAlSql="INSERT INTO card_active_logs(CARDNO,USERID,EXDATE,CARDBALANCE,STATUS,LINKTEL,ACTIVEDATE,ACTIVETIME,DESCRIPTION,CUSTOMID,PANTERID,TERMINAL_ID) ";
            $cardAlSql.=" VALUES('{$cardno}','{$userid}',".date('Ymd');
            $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
            $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
            $cardAlIf=$this->model->execute($cardAlSql);
            //echo $cardAlSql;exit;
            //关联会员卡号
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
            $customsIf=$this->model->execute($customcSql);
            //dump($customsIf);exit;

            //青蓝社至尊卡 更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+5 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}',cardsort='1' where 
cardno='".$cardno."'";
            $cardIf=$this->model->execute($cardSql);
            //echo $this->model->getLastSql();exit;

            //给卡片添加账户并给账户充值
            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
            $balanceIf=$this->model->execute($balanceSql);

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $coinSql.=$acid."','".$cardId."','0','01',NULL)";
            $coinIf=$this->model->execute($coinSql);

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $pointSql.=$acid."','".$cardId."','0','04',NULL)";
            $pointIf=$this->model->execute($pointSql);

            if($this->checkCardBrand($cardno)==true){
                //echo 'ss' ;exit;
                if($isBill==1){
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',1,0,0)";
                }else{
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',0,0,0)";
                }
                $billingIf=$this->model->execute($billingSql);
            }else{
                $billingIf=true;
            }
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true&&$coinIf==true&&$billingIf==true){
                $rechargedAmount+=$rechargeMoney;
                $purchaseArr[]=$cardpurchaseid;
            }
        }
        if($rechargedAmount==$amount){
            //dump($purchaseArr) ;exit;
            return $purchaseArr;
        }else{
            //	echo '5555';exit;
            return false;
        }
    }
    public function tesst(){
        $cardno = '6888377888800000055';

        $where['c.cardno']=$cardno;

        $where['a.type'] = '00';

        $card=$this->cards->alias('c')->join('left join account a on a.customid=c.customid')
            ->where($where)->field('c.customid,a.amount,c.panterid')->find();
//        dump($card);exit;
    }
    //充值执行
    protected function recharge($cardArr,$customid,$rechargeAmount,$panterid,$userid,$isBill=null,$paymenttype='现金'){
        if(empty($cardArr)) return false;
        $rechargedAmount=0;
        $purchaseArr=array();
        foreach($cardArr as $val){
            $waitAmount=$rechargeAmount-$rechargedAmount;

            // if($waitAmount<=0) break;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');

//            $where['cardno']=$cardno;
//
//            $card=$this->cards->alias('c')->join('left join account a on a.customid=c.customid')
//                ->where($where)->field('c.customid,a.amount,c.panterid')->find();
            $where['c.cardno']=$cardno;
            $where['a.type'] = '00';
            $card=$this->cards->alias('c')->join('left join account a on a.customid=c.customid')
                ->where($where)->field('c.customid,a.amount,c.panterid')->find();
            $rechargableAmount=5000-$card['amount'];
            if($rechargableAmount<=$waitAmount){
                $rechargeMoney=$rechargableAmount;
            }else{
                $rechargeMoney=$waitAmount;
            }
            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
            $customplSql.=$userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
            $customplSql.="',1,'','','1','".$card['panterid']."','".$userid."',NULL,'1',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

            $customplIf=$this->model->execute($customplSql);
            $auditlogsIf=$this->model->execute($auditlogsSql);

            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

            //写入充值单
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
            $cardplSql.="'{$userid}','{$card['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);

            //更新卡片账户
            $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$card['customid']."' and type='00'";
            $balanceIf=$this->model->execute($balanceSql);


            if($this->checkCardBrand($cardno)==true){
                if($isBill==1){
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',1,0,0)";
                }else{
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',0,0,0)";
                }
                $billingIf=$this->model->execute($billingSql);
            }else{
                $billingIf=true;
            }

            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true&&$billingIf==true){
                $rechargedAmount+=$rechargeMoney;
                $purchaseArr[]=$cardpurchaseid;
            }
        }
        if($rechargedAmount==$rechargeAmount){
            return $purchaseArr;
        }else{
            return false;
        }
    }
    //获取账户下可充值的余额
    public function getUsableAccount($customid,$cardno){
        $cardBrand=substr($cardno,0,4);
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardno'=>array('neq',$cardno),'c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000),'c.cardsort'=>'1');
        $rechargedAccount=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->sum('a.amount');
        $rechargedC=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->count();
        return 5000*$rechargedC-$rechargedAccount;
    }
    //获取账户下可充值的卡号
    public function getUsableAccCards($customid,$cardno){
        $cardBrand=substr($cardno,0,4);
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardno'=>array('neq',$cardno),'c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000),'c.cardsort'=>'1');
        $usableCards=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')
            ->where($map)->field('c.cardno')->select();
        $cardArr=$this->serializeArr($usableCards,'cardno');
        return $cardArr;
    }
    protected function recordData($data,$flag){

        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Pos/interface/blue/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Pos/interface/bcard/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recoreIp0(){
        $a=$_SERVER['REMOTE_ADDR'];
        $time=date('Y-m-d H:i:s');
        $string=$a.'--'.$time."\r\n\r\n";
        $path='blue.txt';
        file_put_contents($path,$string,FILE_APPEND);
    }
    //读取卡数量
    public function getCardNum($amount){
        return ceil($amount/5000);
    }
    // 在数据库获取电子卡号
    public function getCardNo($num,$panterid,$cardkind='6888'){
        if($panterid==false){
            return false;
        }
        $where = array('panterid'=>$panterid,'status'=>'N');
        $where['_string']=" cardfee is null or cardfee='0'";
        $where['cardkind']=$cardkind;
        $where['makecardid']='00000617';//青蓝社电子卡制卡专用 工具2000张 制卡流水号 00000617
        $cardList = $this->cards->where($where)->field('cardno')->limit(0,$num)->select();
        if(count($cardList)<=0){
            return false;
        }else{
            $list=$this->serializeArr($cardList,'cardno');
            return $list;
        }
    }
    //将二维数组转化成一维
    protected function serializeArr($array,$key){
        $list=array();
        foreach($array as $k=>$v){
            $list[]=$v[$key];
        }
        return $list;
    }
    public function checkCardBrand($cardno){
        $brandid=substr($cardno,0,4);
        if($brandid!='6888'){
            return false;
        }else{
            return true;
        }
    }
    //二维码申请实体卡
    public function erweiCard(){
        $data=json_decode(file_get_contents('php://input'),true);
        $this->recordData($data);
        $customid=trim($data['customid']);//用户的会员主编号
        $cardno=trim($data['cardno']);//获取的卡号
        $panterid='00000219';
        $totalmoney='0';//金额
        $paymenttype = '现金';//现金银行支付的类型
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            $where['cardno']=$cardno;
            returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            $this->recoreIp();
            returnMsg(array('status'=>'03','codemsg'=>'非法卡号'));
        }
        $des=new Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $customids=$des->doDecrypt($customid);
        $custom=substr($customids,0,8);
        $field='customid,namechinese,linktel,residaddress,personid';
        $cu=$this->customs->where(array('customid'=>$custom))->field($field)->find();
        if($cu['customid']!=$custom){
            $this->recoreIp();
            returnMsg(array('status'=>'04','codemsg'=>'会员不存在'));
        }else{
            $cum=$cu['customid'];
            $namechinese=$cu['namechinese'];
            $linktel  = $cu['linktel'];
            $personid = $cu['personid'];
            $residaddress = $cu['residaddress'];
        }

        if($card['status']=="N"){
            //        绑定会员信息
            $currentDate = date('Ymd',time());
            $this->model->startTrans();
            $customid = $this->getFieldNextNumber('customid');
            $gCard="insert into purcha_card(cardno,customid,namechinese,linktel,personid,address,placeddate,placedtime)values('".$cardno."','".$customid."','".$namechinese."','".$linktel."','".$personid."','".$residaddress."','".$currentDate."','".date('H:i:s')."')";
            $gcardIf=$this->model->execute($gCard);
//            $bingSql = "insert into customs(customid,namechinese,personid,linktel,residaddress,placeddate) values('".$customid."','".$namechinese."','".$personid."','".$linktel."','".$residaddress."','".$currentDate."')";
            $bingSql="insert into  customs_c(customid,cid) values('".$cum."','".$customid."')";
            $custIf=$this->model->execute($bingSql);

            if($custIf==true && $gcardIf==true){
                //	echo '111';exit;
                // $this->model->commit();
                $userid = $this->userid;
                $cardArr=array('cardno'=>$cardno);
                $n=$this->openCardno($cardArr,$customid,$totalmoney,$panterid,$userid,$isBill=null,$paymenttype);
                if($n==true){
                    //	echo '455';exit;
                    $des=new Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
                    $customids=$des->doEncrypt($cum);
                    $key=md5($this->keycode.$customids.$cardno);
                    $datami=array('customid'=>$customids,'cardno'=>$cardno,'key'=>$key);
                    //	dump($datami);exit;
                    //	$ch = curl_init("http://192.168.2.63:9090/cyanine/Api/GetCusID");
                    //	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    //	curl_setopt($ch, CURLOPT_POSTFIELDS,$datami);
                    //	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                    //	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    //		'Content-Type: application/json','Content-Length: ' . strlen($datami)
                    //	));
                    //	$result = curl_exec($ch);
                    //	if (curl_errno($ch)) {
                    //		print curl_error($ch);
                    //	}
                    //	curl_close($ch);
                    //	dump($result);
                    $curl=curl_init();
                  //  curl_setopt($curl,CURLOPT_URL,'http://m.qinglanclub.com/cyanine/Api/GetCusID');
                    curl_setopt($curl,CURLOPT_URL,'http://10.1.1.38:81/cyanine/Api/GetCusID');
                    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($curl,CURLOPT_POST,1);
                    curl_setopt($curl,CURLOPT_POSTFIELDS,$datami);
                    $result = curl_exec($curl);
                    curl_close($curl);
                    //var_dump($ban);exit;
                    if($result==false){
                        $this->model->rollback();
                        returnMsg(array('status'=>'16','codemsg'=>'实体卡开卡失败'));
                    }
                    $ban=json_decode($result,true);
                    if($ban['status']==2){
                        //dump($ban);exit;
                        $this->model->commit();
                        returnMsg(array('status'=>'05','codemsg'=>'实体卡开卡成功','purchaseId'=>$n));
                    }else{
                        $this->model->rollback();
                        returnMsg(array('status'=>'06','codemsg'=>'实体卡开卡失败'));
                    }

                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'26','codemsg'=>'实体卡开卡失败'));

                }
                returnMsg(array('status'=>'1','codemsg'=>'绑定会员成功'));

            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'07','codemsg'=>'绑定会员失败'));

            }

        }else{
            returnMsg(array('status'=>'08','codemsg'=>'该卡不是新卡'));
        }

    }
    public function ddel(){
        $customid='4767141131F5B893';
        $des=new Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $customids=$des->doDecrypt($customid);
        $custom=substr($customids,0,8);
        echo $custom;exit;
    }
}