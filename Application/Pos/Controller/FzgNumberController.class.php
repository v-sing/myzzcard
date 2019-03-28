<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/13 0013
 * Time: 下午 1:45
 */
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class FzgNumberController extends CoinController{
 //扫码开卡充值
    public function openCards(){
        $data=json_decode(file_get_contents('php://input'),true);
        $this->recordData($data);
        $customids=trim($data['customid']);//会员主编号
        $linktel=trim($data['linktel']);//用户的联系方式
        $namechinese=trim($data['namechinese']);//用户的姓名
        $residaddress=trim($data['residaddress']);//用户的地址
        $personid=trim($data['personid']);// 证件号
        $panterid=trim($data['panterid']);//商务号
        $startpids=trim($data['startpid']);//证件初始日期
		$startpid=str_replace('.',"",$startpids);
        $endpids = trim($data['endpid']);//证件有效截止日期
		$endpid=str_replace('.',"",$endpids);
        $totalmoney=trim($data['totalmoney']);//金额
        $panter='00000219';//制卡商户号
     //   $paymenttype = trim($data['paymenttype']);//现金银行支付的类型
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customids.$linktel.$namechinese.$residaddress.$personid.$panterid.$totalmoney);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','data'=>$data,'codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($namechinese)){
            returnMsg(array('status'=>'02','codemsg'=>'用户名不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号不能为空'));
        }
        if(!preg_match("/^1[34578]\d{9}$/",$linktel)){
            returnMsg(array('status'=>'04','codemsg'=>'手机号不正确'));
        }

        if(empty($personid)){
            returnMsg(array('status'=>'05','codemsg'=>'证件号不能为空'));
        }
        if(empty($residaddress)) {
            returnMsg(array('status'=>'06','codemsg'=>'证件地址不能为空'));
        }
        if($totalmoney=='0'){
            returnMsg(array('status'=>'07','codemsg'=>'充值金额不能为0'));
        }
		$customid=trim(decode($customids));
		//echo $customid;exit;
        $where['customid']=$customid;
        $where['linktel'] = $linktel;
        $custom= $this->customs->where($where)->field('namechinese,personid')->find();
       // echo M('customs')->getLastSql();exit;
     //   dump($custom);exit;
        $this->model->startTrans();
        if(!empty($custom['namechinese']) && !empty($custom['personid'])){
           // echo '11';exit;
            if($custom['namechinese']!=$namechinese && $custom['personid']!=$personid) {
                returnMsg(array('status' => '08', 'codemsg' => '会员信息不正确','data'=>$custom));
            }elseif($custom['namechinese']==$namechinese || $custom['personid']==$personid ){
               // echo '121';exit;
                $customSql="UPDATE customs SET personidtype='身份证',personid='{$personid}',namechinese='{$namechinese}',residaddress='{$residaddress}',personidissuedate='{$startpid}',personidexdate='{$endpid}' where 
customid='".$customid."'";
                $customIf=$this->model->execute($customSql);
                $currentDate = date('Ymd',time());
                if($customIf == true){
                    $memberSql= "insert into cus_member(customid,ycusname,ypersonid,xcusname,xpersonid,placeddate) values('".$customid."','".$custom['namechinese']."','".$custom['personid']."','".$namechinese."',
'".$personid."','".$currentDate."')";
                    $memberIf=$this->model->execute($memberSql);
                }
            }
        }elseif(empty($custom['namechinese'])||empty($custom['personid'])) {
          //  echo '222';exit;
            $customSql = "UPDATE customs SET personidtype='身份证',personid='{$personid}',namechinese='{$namechinese}',residaddress='{$residaddress}',personidissuedate='{$startpid}',personidexdate='{$endpid}' where 
customid='" . $customid . "'";
            $customIf = $this->model->execute($customSql);
            $currentDate = date('Ymd', time());
            if ($customIf == true) {
                $memberSql = "insert into cus_member(customid,ycusname,ypersonid,xcusname,xpersonid,placeddate) values('" . $customid . "','" . $custom['namechinese'] . "','" . $custom['personid'] . "','" . $namechinese . "',
'" . $personid . "','" . $currentDate . "')";
                $memberIf = $this->model->execute($memberSql);
            }
        }
        if($memberIf == true){
            //查询该会员下有没有6888的虚拟卡
            $userid=M('users')->where(array('panterid'=>$panterid))->field('userid,username')->find();
            $useid=$userid['userid'];
			$u = $userid['username'];
		//	echo $u;
		//	echo $useid;exit;
            $cardsnum=$this->getsCards($customid,'6888');
		//	dump($cardsnum);exit;
            $cardNum  =  $this->getCardNum($totalmoney);
            if($cardsnum==true){
				//echo '111';exit;
                $account=$this->getUsableAccount($customid,'6888');
                if($account>0){
                    if($account>=$totalmoney){
						//echo '23';exit;
                        $recBool=$this->recharge($cardsnum,$customid,$totalmoney,$panterid,$useid,'',$paymenttype='00');
                        $purchaseArr1=$recBool;
                    }else{
						//echo '33';exit;
                        $recBool1=$this->recharge($cardsnum,$customid,$account,$panterid,$useid,'',$paymenttype='00');
                        $purchaseArr3=$recBool1;
                        $caccount=$totalmoney-$account;
                        $openNum=$this->getOpenNum($caccount);
                        $getCarde=$this->getCardNo($openNum,$panter,'6888');
                        if (!$getCarde){
                            returnMsg(array('status'=>'09','codemsg'=>'卡池数量不足'));
                        }else {
                            $openBool = $this->openCardno($getCarde, $customid, $caccount, $panterid, $useid, $isBill = null, $paymenttype = '00');
                        }
                        if($recBool1==true&&$openBool==true){
                            $recBool=true;
                            $purchaseArr1=array_merge($purchaseArr3,$openBool);
                        }
					//	dump($purchaseArr1);exit;
                 }
                 if($recBool==true){
                        $n= true;
                        $n=$purchaseArr1;
                 }
				//	dump($purchaseArr1);exit;
                }else{
				//	echo '33w';exit;
                        $cardArr  = $this->getCardNo( $cardNum,$panter,'6888');
                        if (!$cardArr){
                            returnMsg(array('status'=>'12','codemsg'=>'卡池卡数量不足'));
                        }else{
                            $n = $this->openCardno($cardArr,$customid,$totalmoney,$panterid,$useid,$isBill=null,$paymenttype='00');
                        }
                }
					
            }else{
			//	echo '2228';exit;
                    $cardArr  = $this->getCardNo( $cardNum,$panter,'6888');
					//dump($cardArr);exit;
                    if (!$cardArr){
                        returnMsg(array('status'=>'13','codemsg'=>'卡池卡的数量不足'));
                    }else{
                        $n = $this->openCardno($cardArr,$customid,$totalmoney,$panterid,$useid,$isBill=null,$paymenttype='00');
                    }
            }
			//dump($n);exit;

            if($n==true){
				//echo '777';exit;
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>$n));
            }else{
				//echo '7774';exit;
                $this->model->rollback();
                returnMsg(array('status'=>'11','codemsg'=>'充值失败'));
            }
        }
 }
    // 在数据库获取电子卡号
    public function getCardNo($cardNum,$panter,$cardkind='6888'){
        if($panter==false){
            return false;
        }

        $where = array('panterid'=>$panter,'status'=>'N','cardsort'=>array('exp','is null'));
        $where['_string']=" cardfee is null or cardfee='0'";
        $where['cardkind']=$cardkind;
        $cardList = $this->cards->where($where)->field('cardno')->limit(0,$cardNum)->select();
      //  echo M('cards')->getLastSql();exit;
        if(count($cardList)<=0){
            return false;
        }else{
            $list=$this->serializeArr($cardList,'cardno');
            return $list;
        }
    }
    //读取卡数量
    public function getCardNum($amount){
        return ceil($amount/5000);
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
            $cardinfo=M('cards')->where($where)->field('panterid')->find();
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
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            //写入购卡充值单
            $cardplSql="INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
            $cardplSql.="FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台开卡',";
            $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);
            $where1['customid']=$customid;
            $card=$this->cards->where($where1)->find();
            if($card==false){
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                $cardId=$customid;
            }else{
                //若存在，则需另外生成卡编号
                //$cardId=$this->getnextcode('customs',8);
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

            //至尊卡 更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+ 3 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
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
		//dump($purchaseArr);exit;
        if($rechargedAmount==$amount){
            return $purchaseArr;
        }else{
            return false;
        }
    }
    //获取账户下可充值的余额
    public function getUsableAccount($customid,$cardBrand){
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000),'c.cardsort'=>array('exp','is null'));
        $rechargedAccount=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->sum('a.amount');
        $rechargedC=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->count();
        return 5000*$rechargedC-$rechargedAccount;
    }
    // 获取账户下6888的卡
    public function getsCards($customid,$cardBrand){
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000),'c.cardsort'=>array('exp','is null'));
        $usableCards=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')
            ->where($map)->field('c.cardno')->select();
        $cardArr=$this->serializeArr($usableCards,'cardno');
        return $cardArr;
    }
    //查询账户下的卡数量
    protected function customCardNum($customid){
        $where['cc.customid']=$customid;
        $c=$this->customs_c->alias('cc')->join('cards c on cc.id=c.customid')->where($where)->count();
        return $c;
    }
}