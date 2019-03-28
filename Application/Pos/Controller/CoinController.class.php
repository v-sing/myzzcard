<?php
namespace Pos\Controller;
use Org\Util\DESedeCoder;
use Think\Controller;
use Think\Model;
class CoinController extends Controller {
    protected $model;
    protected $customs;
    protected $account;
    protected $customs_c;
    protected $cards;
    protected $custompl;
    protected $cardpl;
    protected $userid;
    protected $keycode;
    protected $panterArr;
    protected $DESedeCoder;
    protected $hotelUserid;
    protected $hotelPanterid;
    public function _initialize(){
        $this->model=new model();
        $this->customs=M('customs');
        $this->account=M('account');
        $this->cards=M('cards');
        $this->customs_c=M('customs_c');

        $this->userid='0000000000000000';
        $this->keycode='JYO2O01';
        $this->DESedeCoder=new  DESedeCoder();
        $this->hotelUserid='0000000000000059';
        $this->hotelPanterid='00000013';
        $this->fieldsLength=C('FIELDS_LENGTH');
        $this->recoreIp();
    }

    //会员注册执行
    protected function createCustoms($customArr,$type=1){
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
        $currentDate=date('Ymd',time());
        if($type==1){
            $linktel=$customArr['mobile'];
            $panterid=$customArr['panterid'];
            $paswd=empty($customArr['pwd'])?'':md5($customArr['pwd']);
            $customlevel='建业线上会员';
            $countrycode=$customArr['pname'].'会员';
            $sql="INSERT INTO CUSTOMS(customid,linktel,placeddate,paswd,customlevel,countrycode) ";
            $sql.="values ('{$customid}','{$linktel}','{$currentDate}','{$paswd}','{$customlevel}','{$countrycode}')";
        }elseif($type==2){
            $linktel=$customArr['linktel'];
            $panterid=$customArr['panterid'];
            $customlevel='建业线上会员';
            $sql="INSERT INTO CUSTOMS(customid,linktel,placeddate,panterid,customlevel,countrycode) values ";
            $sql.="('{$customid}','{$linktel}','{$currentDate}','{$panterid}','{$customlevel}','收银台支付会员')";
        }

        $this->recordError(serialize($sql), "YjTbpost", "createAccount");

        $customIf=$this->model->execute($sql);
        if($customIf==true){
            return $customid;
        }else{
            return false;
        }
    }

    //开卡执行
    protected function openCard($cardArr,$customid,$amount=0,$panterid=null,$userid,$isBill=null,$paymenttype='现金'){
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

            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
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

            //更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+3 years"));
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
        if($rechargedAmount==$amount){
            return $purchaseArr;
        }else{
            return false;
        }
    }

    //充值执行
    protected function recharge($cardArr,$customid,$rechargeAmount,$panterid,$userid,$isBill=null,$paymenttype='现金'){
        if(empty($cardArr)) return false;
        $rechargedAmount=0;
        $purchaseArr=array();
        foreach($cardArr as $val){
            $waitAmount=$rechargeAmount-$rechargedAmount;
            $waitAmount=$rechargeAmount-$rechargedAmount;
            if($waitAmount<=0) break;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');

            $where['cardno']=$cardno;
            $card=$this->cards->alias('c')->join('account a on a.customid=c.customid')
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

    // //建业币充值
    // protected function coinRecharge($cardInfo,$coinArr,$panterid){
    //     $cardid=$cardInfo['cardid'];
    //     $cardno=$cardInfo['cardno'];
    //     $accountid=$cardInfo['accountid'];
    //     $orderid=$cardInfo['orderid'];
    //     $customid=$cardInfo['customid'];
    //     $errString="{$customid}充值执行记录\r\n";
    //     $nowdate=date('Ymd');
    //     $nowtime=date('H:i:s');
    //     $enddate=$nowdate+366;
    //     $userid = '0000000000000000';
    //     $cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
    //     $purchaseid=substr($cardpurchaseid,1,16);
    //     $userip='127.0.0.1';
    //     $coinAmount=$coinArr['totalAmount'];
    //     $coinAmount1=$coinArr['coinAmount1'];
    //     $coinAmount2=$coinArr['coinAmount2'];
    //     $type=$coinArr['type'];
    //     $errString.="至尊卡号：{$cardno},充值金额：{$coinAmount}，可用余额：{$coinAmount1}，锁定金额：{$coinAmount2},充值流程数据库操作记录：\r\n";
    //     $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
    //     $cardplSql.=trim($cardno)."',0,'{$coinAmount}','{$nowdate}','{$nowtime}','1','建业币接口充值:{$orderid}','";
    //     $cardplSql.=$userid."','{$panterid}','{$userip}','00000000')";
    //
    //     $accountSql="UPDATE account SET amount=nvl(amount,0)+".$coinAmount." where customid='".$cardid."' and type='01'";
    //
    //     $cardplif  =$this->model->execute($cardplSql);
    //     $accounteif = $this->model->execute($accountSql);
    //     $errString.="时间：".date('Y-m-d H:i:s').";建业通宝充值记录写入：{$cardplSql};执行结果：{$cardplif}\r\n";
    //     $errString.="时间：".date('Y-m-d H:i:s').";更新通宝账户信息：{$accountSql};执行结果：{$accounteif}\r\n";
    //
    //     /*建业币充值转台有激活状态（通用）、
    //     半激活状态（app上、房产本项目使用）、
    //     锁定状态（项目刷卡时发行本项目使用，其他项目或者商户均不能使用，购房后激活转化为激活装热爱）
    //     */
    //     if($type==2){
    //         $coinid=$this->getnextcode('coinid',8);
    //         $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','03','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
    //         $coinIf1=$this->model->execute($coinSql1);
    //         $coinIf2=true;
    //         $errString.="时间：".date('Y-m-d H:i:s').";通宝通用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
    //     }else{
    //         if($coinAmount2>0){
    //             $coinid=$this->getnextcode('coinid',8);
    //             $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
    //             $coinIf1=$this->model->execute($coinSql1);
    //             $errString.="时间：".date('Y-m-d H:i:s').";通宝可用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
    //             $coinid=$coinid=$this->getnextcode('coinid',8);
    //             $coinSql2="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount2}','{$nowdate}','{$nowtime}','02','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
    //             $coinIf2=$this->model->execute($coinSql2);
    //             $errString.="时间：".date('Y-m-d H:i:s').";通宝锁定账户明细写入：{$coinSql2};执行结果：{$coinIf2}\r\n";
    //         }else{
    //             $coinid=$this->getnextcode('coinid',8);
    //             $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
    //             //echo $coinSql1;exit;
    //             $coinIf1=$this->model->execute($coinSql1);
    //             $errString.="时间：".date('Y-m-d H:i:s').";通宝可用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
    //             $coinIf2=true;
    //         }
    //         $errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
    //     }
    //     $errString.="\r\n";
    //     $this->recordError($errString,'rechargeCoin','会员_'.$customid);
    //     if($cardplif==true&&$accounteif==true&&$coinIf1==true&&$coinIf2==true){
    //         return true;
    //     }else{
    //         return false;
    //     }
    // }

    //建业币充值(发行通宝为01状态，若出现退款更新为00)
    protected function coinRecharge($cardInfo,$coinAmount,$panterid){
        $cardid=$cardInfo['cardid'];
        $cardno=$cardInfo['cardno'];
        $accountid=$cardInfo['accountid'];
        $orderid=$cardInfo['orderid'];
        $customid=$cardInfo['customid'];
        $errString="{$customid}充值执行记录\r\n";
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        $enddate=$nowdate+366*2;
        $userid = '0000000000000000';
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $purchaseid=substr($cardpurchaseid,1,16);
        $userip='127.0.0.1';
        $errString.="至尊卡号：{$cardno},充值金额：{$coinAmount}充值流程数据库操作记录：\r\n";
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql.=trim($cardno)."',0,'{$coinAmount}','{$nowdate}','{$nowtime}','1','建业币接口充值:{$orderid}','";
        $cardplSql.=$userid."','{$panterid}','{$userip}','00000000')";

        $accountSql="UPDATE account SET amount=nvl(amount,0)+".$coinAmount." where customid='".$cardid."' and type='01'";

        $cardplif  =$this->model->execute($cardplSql);
        $accounteif = $this->model->execute($accountSql);
        $errString.="时间：".date('Y-m-d H:i:s').";建业通宝充值记录写入：{$cardplSql};执行结果：{$cardplif}\r\n";
        $errString.="时间：".date('Y-m-d H:i:s').";更新通宝账户信息：{$accountSql};执行结果：{$accounteif}\r\n";

        //$coinid=$this->getnextcode('coinid',8);
        $coinid=$this->getFieldNextNumber('coinid');
        $coinSql="INSERT INTO  coin_account  (ACCOUNTID,RECHARGEAMOUNT,REMINDAMOUNT,PLACEDDATE,PLACEDTIME,STATUS,PANTERID,CARDID,COINID,SOURCEORDER,CARDPURCHASEID,ENDDATE,PANTERCHECK,CHECKID,CHECKDATE)  values('{$accountid}','{$coinAmount}','{$coinAmount}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}',0,'','')";
        $coinIf=$this->model->execute($coinSql);
        $errString.="时间：".date('Y-m-d H:i:s').";通宝可用账户明细写入：{$coinSql};执行结果：{$coinIf}\r\n";
        $errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
        $errString.="\r\n";
        $this->recordError($errString,'rechargeCoin','会员_'.$customid);
        if($cardplif==true&&$accounteif==true&&$coinIf==true){
            return true;
        }else{
            return false;
        }
    }

    protected function pointRechargeExe($cardInfo,$pointAmount,$panterid,$userid){
        $cardid=$cardInfo['cardid'];
        $cardno=$cardInfo['cardno'];
        $accountid=$cardInfo['accountid'];
        $type=$cardInfo['type'];
        $validity=$cardInfo['validity'];
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        if(empty($validity)){
            $enddate=0;
        }else{
            $enddate=date('Ymd',strtotime('+'.$validity.' month',time()));
        }
        if($userid==null){
            $userid = $this->hotelUserid;
        }
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $purchaseid=substr($cardpurchaseid,1,16);
        $userip='';
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql.=trim($cardno)."',0,'{$pointAmount}','{$nowdate}','{$nowtime}','1','至尊积分充值','";
        $cardplSql.=$userid."','{$panterid}','{$userip}','00000000')";
        $accountSql="UPDATE account SET amount=nvl(amount,0)+".$pointAmount." where customid='".$cardid."' and type='04'";

        $cardplIf  =$this->model->execute($cardplSql);
        $accountIf = $this->model->execute($accountSql);

        //$pointid=$this->getnextcode('pointid',8);
        $pointid=$this->getFieldNextNumber('pointid');
        $pointSql="INSERT INTO point_account values('{$accountid}','{$pointAmount}','{$pointAmount}','{$nowdate}'";
        $pointSql.=",'{$nowtime}','{$panterid}','{$cardid}','{$pointid}','','{$cardpurchaseid}','{$enddate}','{$type}')";
        $pointIf=$this->model->execute($pointSql);
        if($cardplIf==true&&$accountIf==true&&$pointIf==true){
            return true;
        }else{
            return false;
        }
    }

    //获取账户下的至尊卡号
    protected function getOwnCards($customid){
        $where['cu.customid']=$customid;
        $list=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($where)->field('c.cardno')->order('c.customid asc')->select();
        $list=$this->serializeArr($list,'cardno');
        return $list;
    }

    //获取账户下余额账户不为0的至尊卡号
    protected function getConsumeCards($customid,$type,$brandid=null){
        $where=array('cu.customid'=>$customid,'a.type'=>$type,
            'a.amount'=>array('gt',0),'c.status'=>'Y',
            'c.exdate'=>array('gt',date('Ymd',time())));
        $where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        if(!empty($brandid)){
            $where['c.cardkind']=$brandid;
        }
        $list=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on a.customid=c.customid')
            ->where($where)->field('c.cardno,a.amount,c.customid cardid')->order('c.customid asc')->select();
        return $list;
    }

    //获取账户下建业币账户不为0的至尊卡号
    protected function getCoinCards($customid){
        $subQuery="(SELECT sum(remindamount) amount,cardid from coin_account where status in('01','03')  group by cardid)";
        $where=array('cu.customid'=>$customid,'a.amount'=>array('gt',0),'c.status'=>'Y');
        $list=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->join($subQuery.' a on a.cardid=c.customid')
            ->where($where)->field('c.cardno,a.amount,c.customid')->select();
        return $list;
    }

    //获取需开卡的数量
    protected function getOpenNum($amount){
        return ceil($amount/5000);
    }

    //获取新卡
    protected function getCard($num,$panterid,$cardno=null){
        $map=array('panterid'=>$panterid,'status'=>'N');
        $map['_string']="cardfee is null OR cardfee=0";
        if(!empty($cardno)){
            $cardkind=substr($cardno,0,4);
            if($cardkind!='6888'&&$cardkind!='2336'){
                $map['cardkind']=$cardkind;
            }
        }
        $c=$this->cards->where($map)->field('cardno')->count();
        $list=array();
        for($i=0;$i<$num;$i++){
            $rand=mt_rand(0,$c-$i);
            $cards=$this->cards->where($map)->field('cardno')->order('cardno asc')->select();
            $cardno=$cards[$rand]['cardno'];
            $this->cards->where(array('cardno'=>$cardno))->save(array('status'=>'L'));
            $list[]=$cardno;
            unset($cards);
        }
//        $cardList=$this->cards->where($map)->field('cardno')->limit(0,$num)->select();
//        $list=$this->serializeArr($cardList,'cardno');
        return $list;
    }

    //获取建业币账户信息
    protected function getCoinAccount($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'01');
        $account=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('c.customid cardid,a.accountid')->find();
        return $account;
    }

    //获取建业币账户信息
    protected function getPointAccount($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'04');
        $account=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('c.customid cardid,a.accountid')->find();
        return $account;
    }

    //测试手机是否被注册
    protected function checkMobile($linktel){
        $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $customs=$this->customs->where($map)->find();
        //echo $this->customs->getLastSql();
        if($customs!=false){
            return true;
        }else{
            return false;
        }
    }

    //检查会员是否存在
    protected function checkCustom($customid){
        $customMap=array('customid'=>$customid);
        $customInfo=$this->customs->where($customMap)->find();
        if($customInfo==false){
            return false;
        }else{
            return $customInfo;
        }
    }
    //检查卡号是否存在
    protected function checkCard($cardno){
        $where=array('cardno'=>$cardno);
        $card=$this->cards->alias('c')->where($where)->field('status,customid,cardpassword')->find();
        return $card;
    }

    //检查商户是否存在
    protected function checkPanter($panterid){
        $where=array('panterid'=>$panterid);
        $panter=M('panters')->where($where)->find();
        return $panter;
    }

    //检查密码是否正确
    protected function checkPwd($customid,$pwd){
        $customMap=array('customid'=>$customid);
        $customInfo=$this->customs->where($customMap)->find();
        //echo $this->customs->getLastSql();
        if($customInfo['paswd']!=$pwd){
            return false;
        }else{
            return true;
        }
    }

    //检查支付密码是否正确
    protected function checkPayPwd($customid,$paypwd){
        $customMap=array('customid'=>$customid);
        $customInfo=$this->customs->where($customMap)->find();
        //echo $customInfo['paypwd'].'---'.$paypwd;exit;
        if($customInfo['paypwd']!=$paypwd){
            return false;
        }else{
            return true;
        }
    }

    //pos端传递卡密码通过用户卡列表进行逐一比对
    protected function checkCardPwd($customid,$cardpwd){
        $cardlist = $this->getOwnCards($customid);
        $cardlist = implode(',',$cardlist);
        $where = "cardno in ({$cardlist})";
        $getpwd = $this->cards->where($where)->field('cardpassword')->select();
        $errString=$this->cards->getLastSql().'\r\n';
        $this->recordError($errString,'consume','会员_'.$customid);
        $getpwd = array_column($getpwd,'cardpassword');
        if(in_array($cardpwd,$getpwd)){
            return true;
        }else{
            return false;
        }
    }



    //验证卡密码
    protected function checkPwdBycardno($cardno,$cardpwd){
        $where = array('cardno'=>$cardno);
        $cardInfo = $this->cards->where($where)->field('cardpassword')->find();
        $errString=$this->cards->getLastSql().'\r\n';
        $this->recordError($errString,'consume','卡号_'.$cardno);
        if($cardInfo['cardpassword']!=$cardpwd){
            return false;
        }else{
            return true;
        }
    }

    //查询会员账户余额
    protected function accountQuery($customid,$type,$cardno=null){
        $where=array('cc.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','a.amount'=>array('gt',0));
        $where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        if(!empty($cardno)){
            $cardkind=substr($cardno,0,4);
            $where['c.cardkind']=$cardkind;
        }
        $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->sum('a.amount');
        if(empty($account)) $account=0;
        return $account;
    }

    //查询会员账户余额
    protected function zzAccountQuery($customid,$type){
        $where=array('cc.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','c.cardkind'=>array('in',array('2336','6888','6886')));
        $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->sum('a.amount');
        if(empty($account)) $account=0;
        return $account;
    }

         #start
         protected function zzkaccount($customid)
         {
             return $money = M('zzk_account')
                 ->where(array('customid' => $customid, 'type' => '05'))
                 ->field('balance+cash_balance as money,balance,cash_balance,accountid')
                 ->find();
         }

         /**
          * 返回订单号
          *
          * @param $product_type 产品类型
          * @param $order_kind 订单类别
          * @param $accountid 账户id
          * @return string 订单号
          */
         function zzktradeid($product_type, $order_kind, $accountid)
         {
             return date('Ymd') . $product_type . $order_kind . rand(100000, 999999) . substr($accountid, -2) . rand(10, 99);
         }

         /**
          * 增加字符长度
          *
          * @param $table string 表名
          * @param $lennum int 字符长度
          * @return $str string 增加长度后的字符
          */
         function zzkgetnumstr($table, $lennum)
         {
             $id = $this->model->query("select $table.nextval from dual")[0]['nextval'];
             $snum = strlen($id);
             $x = '';
             for ($i = 1; $i <= $lennum - $snum; $i++) {
                 $x .= '0';
             }
             return $x . $id;
         }
         //自有资金
         public function equityfund($zzkamount, $zmoney)
         {
             $this->recordError(date("H:i:s") . '  自有资金要扣的金额：' . $zzkamount . '   数据:  ' . serialize($zmoney) . "\n\t", "Ctbxfdx", "info");
             try {
                 if ($zzkamount > $zmoney['balance']) {
                     $amount = $zzkamount - $zmoney['balance'];
                     $up = 'balance=0,cash_balance=cash_balance-' . $amount;
                 } else {
                     $up = 'balance=balance-' . $zzkamount;
                 }

                 $sql = "update zzk_account set $up where accountid='$zmoney[accountid]' and type='05'";
                 $zzk_accountInfo = $this->model->execute($sql);
                 $this->recordError($sql . "\n\t", "Ctbxfdx", "info");
                 $this->recordError($zzk_accountInfo . "\n\t", "Ctbxfdx", "info");

                 //zzk_account_detail 金额变动记录 主键id accountid before_amount变动前金额 charge_amount要变动的金额 balance变动后的金额 tradetype变动类型 order_sn订单号 source来源
                 $before_amount = $zmoney['money'] + $zmoney['freeze_balance'];
                 $charge_amount = $zzkamount;
                 $balance = $before_amount - $zzkamount;
                 $zzk_detailid = $this->zzkgetnumstr('zzk_detailid', 16);
                 $zzk_ordersql = "INSERT INTO zzk_account_detail(detailid,accountid,before_amount,charge_amount,balance,tradetype,source,placeddate,placedtime,order_sn) values ";
                 $zzk_ordersql .= "('{$zzk_detailid}','{$zmoney[accountid]}','{$before_amount}','{$charge_amount}','{$balance}','50','07','{$zmoney[placeddate]}','{$zmoney[placedtime]}','{$zmoney[orderid]}')";
                 $zzk_account_detail = $this->model->execute($zzk_ordersql);
                 $this->recordError($zzk_ordersql . "\n\t", "Ctbxfdx", "info");
                 $this->recordError($zzk_account_detail . "\n\t", "Ctbxfdx", "info");

                 //zzk_pay_detail 订单支付信息 主键id order_sn订单号 paytype支付类型 amount金额 descrption支付说明 panterid商户id storeid门店id
                 $zzk_payid = $this->zzkgetnumstr('zzk_payid', 16);
                 $zzk_paysql = "INSERT INTO zzk_pay_detail(payid,order_sn,paytype,amount,placeddate,placedtime,description,panterid,storeid) values ";
                 $zzk_paysql .= "('{$zzk_payid}','{$zmoney[inner_order]}','05','{$zzkamount}','{$zmoney[placeddate]}','{$zmoney[placedtime]}','{$zmoney[desc]}','{$zmoney[panterid]}','{$zmoney[panterid]}')";
                 $zzk_pay_detail = $this->model->execute($zzk_paysql);
                 $this->recordError($zzk_paysql . "\n\t", "Ctbxfdx", "info");
                 $this->recordError($zzk_pay_detail . "\n\t", "Ctbxfdx", "info");

                 return array('status' => 1, 'message' => '成功');
             } catch (\Exception $e) {
                //  var_dump($e);
                $this->recordError($e . "\n\t", "Ctbxfdx", "info");
                 return array('status' => 0, 'message' => '失败');
             }
         }
         #end

    //查询会员建业币余额
    protected function coinQuery($customid){
        $where=array('cc.customid'=>$customid,'a.type'=>'01','a.amount'=>array('gt',0),'c.status'=>'Y');
        $cards=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field('c.cardno')->select();
        $totalAmount=0;
        foreach($cards as $key=>$val){
            $account=$this->getCoinDetail($val['cardno']);
            $totalAmount+=$account;
        }
        return $totalAmount;
    }

    //查询会员信息
    protected function getCustomByLinktel($linktel){
        $where=array('linktel'=>$linktel,'customlevel'=>'e+会员');
        $custom=$this->customs->field('customid')->where($where)->find();
        return $custom;
    }

    //查询会员账户信息（余额，建业币）
    protected function accountInfo($customid){
        $where=array('cc.customid'=>$customid,'a1.type'=>'00','a2.type'=>'01');
        $accountInfo=$this->customs_c->alias('cc')->join('account a1 on cc.cid=a1.customid')
            ->join('account a2 on cc.cid=a2.customid')
            ->where($where)->field("sum(a1.amount) balance,sum(a2.amount) jycoin")->find();
        return $accountInfo;
    }

    //查询会员建业通宝信息
    protected function getCoinInfo($customid){
        $where=array('cc.customid'=>$customid,'a2.type'=>'01');
        $accountInfo=$this->customs_c->alias('cc')->join('account a2 on cc.cid=a2.customid')
            ->where($where)->field("sum(a2.amount) jycoin")->find();
        $where1=array('customid'=>$customid);
        $subCards=$this->customs_c->where($where1)->field('cid')->select();
        $cardids=array();
        foreach($subCards as $key=>$val){
            $cardids[]=$val['cid'];
        }
        $where2=array('cardid'=>array('in',$cardids),'enddate'=>array('egt',date('Ymd')));
        $avilableAmount=M('coin_account')->where($where2)->sum('remindamount');
        $coinInfo=array('amount'=>$accountInfo['jycoin'],'avilableAmount'=>$avilableAmount);
        //var_dump($coinInfo);
        return $coinInfo;
    }

    //查询账户下的卡数量
    protected function customCardNum($customid){
        $where['cc.customid']=$customid;
        $c=$this->customs_c->alias('cc')->join('cards c on cc.id=c.customid')->where($where)->count();
        return $c;
    }

    //查询单张卡片的账户余额
    protected function cardAccQuery($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'00');
        $cardAccount=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('a.amount')->find();
        $amount=empty($cardAccount['amount'])?0:$cardAccount['amount'];
        return $amount;
    }
    //获取建业通宝
    protected function getCoinByCardno($cardno){
        $where=array('c.cardno'=>$cardno,'ca.enddate'=>array('egt',date('Ymd')));
        $sum=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->sum('remindamount');
        $sum=$sum==''?0:$sum;
        return $sum;
    }

    //获取至尊积分的金额
    protected function getPointByCardno($cardno,$panterid){
        $where1=array('c.cardno'=>$cardno,'type'=>'1');
        $where1['_string']="pa.enddate>='".date('Ymd')."' OR pa.enddate=0";
        $sum1=$this->cards->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where1)->sum('remindamount');
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->find();
        $where2=array('c.cardno'=>$cardno,'type'=>'2');
        $where2['_string']=" (pa.panterid='{$panterid}' OR pa.panterid='{$panter['parent']}') and ( pa.enddate>='".date('Ymd')."' OR pa.enddate=0)";
        $sum2=$this->cards->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where2)->sum('remindamount');
        $sum1=$sum1==''?0:$sum1;
        $sum2=$sum2==''?0:$sum2;
        $sum=$sum1+$sum2;
        return $sum;
    }

    //获取至尊积分的金额
    protected function getPointByCustomid($customid,$panterid,$cardno){
        $cardbrind=substr($cardno,0,4);
        $where1=array('cu.customid'=>$customid,'pa.type'=>'1','c.cardkind'=>$cardbrind);
        $where1['_string']="pa.enddate>='".date('Ymd')."' OR pa.enddate=0";
        $sum1=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('point_account pa on pa.cardid=c.customid')
            ->where($where1)->sum('remindamount');
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->find();
        $where2=array('cu.customid'=>$customid,'pa.type'=>'2','c.cardkind'=>$cardbrind);
        $where2['_string']=" (pa.panterid='".$panterid."' OR pa.panterid='".$panter['parent']."') AND (pa.enddate>='".date('Ymd')."' OR pa.enddate=0)";
        $sum2=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('point_account pa on pa.cardid=c.customid')
            ->where($where2)->sum('remindamount');
        $sum1=$sum1==''?0:$sum1;
        $sum2=$sum2==''?0:$sum2;
        $sum=$sum1+$sum2;
        return $sum;
    }

    //获取下一个序号  tablename 表名 主键自增一
    protected function getnextcode($tablename,$lennum=0){
        $code = D('code_generators');
        if($tablename!=''){
            $dmap['keyname']=$tablename;
            $datatable=$code->where($dmap)->getfield('current_seq');
            if($datatable==null){
                $mapp=array(
                    'keyname'=>$tablename,
                    'current_seq'=>1,
                );
                $strsql="INSERT INTO code_generators (keyname,current_seq) VALUES ('".$tablename."', 1)";
                $codeid=$code->execute($strsql);
                if ($codeid==false) { //判断是否成功
                    $this->error('失败！');
                    exit;
                }
            }
            $map['current_seq']=$datatable+1;
            if($code->where($dmap)->save($map)){
                if($lennum!=0){
                    $datatable=$this->getnumstr($datatable,$lennum);
                }
                return $datatable;
            }else{
                $this->error('表主键更新失败');
                exit;
            }
        }else{
            $this->error('表名不能为空');
            exit;
        }
    }

    //获得增加长度的字符 $numstr编号    $lennum字符长度
    protected function getnumstr($numstr,$lennum){
        $snum=strlen($numstr);
        for($i=1;$i<=$lennum-$snum;$i++){
            $x.='0';
        }
        return $x.$numstr;
    }

    //将二维数组转化成一维
    protected function serializeArr($array,$key){
        $list=array();
        foreach($array as $k=>$v){
            $list[]=$v[$key];
        }
        return $list;
    }

    protected function getBindCards($customid){
        $map=array('customid'=>$customid,'status'=>1);
        $bindList=M('ecard_bind')->where($map)->field('cardno')->select();
        if($bindList==false){
            return false;
        }else{
            return $this->serializeArr($bindList,'cardno');
        }
    }

    protected function recoreIp(){

        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename='ip'.date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string=$a.'--'.$time."\r\n\r\n";
        $path=PUBLIC_PATH.'/Pos/interface/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recordData($data,$flag){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Pos/interface/ticket/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Pos/interface/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recordError($data,$childPath,$indentifyName){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $day=date('d');
        $filename=iconv("utf-8","gb2312",$indentifyName).'.log';
        $time=date('Y-m-d H:i:s');
        $string=$data;
        $path=PUBLIC_PATH.'Pos/interface/'.$childPath.'/'.$month.'/'.$day.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }

    protected function recoreIp0(){
        $a=$_SERVER['REMOTE_ADDR'];
        $time=date('Y-m-d H:i:s');
        $string=$a.'--'.$time."\r\n\r\n";
        $path='test0.txt';
        file_put_contents($path,$string,FILE_APPEND);
    }

    protected function decodePwd($pwd,$begin='jycard',$end='jycoin'){
        $pwd=base64_decode($pwd);
        $reg='/^'.$begin.'(.+)'.$end.'$/';
        if(preg_match($reg,$pwd,$arr)){
            return $arr[1];
        }else{
            return false;
        }
    }

    protected function getTicketByCustomid($customid,$panterid=null){
        $panter=M('panters')->where(array('panterid'=>$panterid))->find();
        $where1=array(
            'cc.customid'=>$customid,
            'a.type'=>'02',
            'a.amount'=>array('gt',0),
            'q.enddate'=>array('egt',date('Ymd')),
            'c.status'=>'Y',
            'c.exdate'=>array('egt',date('Ymd'))
        );
        $where2=array(
            'cc.customid'=>$customid,
            'qa.amount'=>array('gt',0),
            'qa.enddate'=>array('egt',date('Ymd')),
            'c.status'=>'Y',
            'c.exdate'=>array('egt',date('Ymd'))
        );
        if($panterid!=null){
            $panter=M('panters')->where(array('panterid'=>$panterid))->find();
            $where1['_string']=$where2['_string']="(q.panterid='{$panterid}' and q.utype=2) or (q.utype=1 and q.panterid='{$panter['parent']}')";
        }
        $where1['_string']=$where2['_string']="(q.panterid='{$panterid}' and q.utype=2) or (q.utype=1 and q.panterid='{$panter['parent']}')";
        $list1=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,q.quanid,q.atype')
            ->where($where1)->select();

        $list2=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,qa.amount,q.quanid,qa.accountid,q.atype')
            ->where($where2)->select();
        $list=array_merge($list1,$list2);
        if($list==false){
            return false;
        }else{
            $ticketList=array();
            foreach($list as $key=>$val){
                if($val['atype']==1){
                    if(isset($ticketList[$key]['quanid'])){
                        $ticketList[$key]['quanid']+=$val['amount'];
                    }else{
                        $ticketList[$key]['quanid']=$val['quanid'];
                        $ticketList[$key]['quanname']=$val['quanname'];
                        $ticketList[$key]['amount']=$val['amount'];
                    }
                    $ticketList[$key]['accountid']='';
                }else{
                    $ticketList[$key]['quanid']=$val['quanid'];
                    $ticketList[$key]['quanname']=$val['quanname'];
                    $ticketList[$key]['amount']=$val['amount'];
                    $ticketList[$key]['accountid']=$val['accountid'];
                }
            }
            return $ticketList;
        }
    }

    protected function getTicketByCardno($cardno,$panterid=null){
        $where1=array(
            'c.cardno'=>$cardno,
            'a.type'=>'02',
            'a.amount'=>array('gt',0),
            'q.enddate'=>array('egt',date('Ymd')),
            'q.atype'=>1
        );
        $where2=array(
            'c.cardno'=>$cardno,
            'qa.amount'=>array('gt',0),
            'qa.enddate'=>array('egt',date('Ymd')),
            'q.atype'=>2
        );
        if($panterid!=null){
            $panter=M('panters')->where(array('panterid'=>$panterid))->find();
            $where1['_string']=$where2['_string']="(q.panterid='{$panterid}' and q.utype=2) or (q.utype=1 and q.panterid='{$panter['parent']}')";
        }
        $list1=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->field('q.quanname,a.amount,q.quanid,q.enddate')
            ->where($where1)->select();

        $list2=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->field('q.quanname,qa.amount,q.quanid,qa.enddate,qa.accountid')
            ->where($where2)->select();

        $list=array_merge($list1,$list2);
        if($list==false){
            return false;
        }else{
            $ticketList=array();
            foreach($list as $key=>$val){
                $ticketList[$key]['quanname']=$val['quanname'];
                $ticketList[$key]['amount']=$val['amount'];
                $ticketList[$key]['quanid']=$val['quanid'];
                $ticketList[$key]['accountid']=!empty($val['accountid'])?$val['accountid']:'';
            }
            return $ticketList;
        }
    }

    //执行至尊卡扣款记录，
    protected function tradeExecute($consumeInfo){
        $panterid=$consumeInfo['panterid'];
        $cardno=$consumeInfo['cardno'];
        $customid=$consumeInfo['customid'];
        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $termno=!empty($consumeInfo['termno'])?$consumeInfo['termno']:'00000000';
        $pretradeid=empty($consumeInfo['pretradeid'])?'':$consumeInfo['pretradeid'];
        $signType=$consumeInfo['signType'];
        if(!empty($signType)){
            $flag=1;
        }
        $flag=empty($signType)?0:1;
        $map=array('tradeid'=>$tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $amount=$consumeInfo['amount'];
        $orderid=$consumeInfo['orderid'];
        $tradetype=$consumeInfo['tradetype'];
        if($tradetype=='00'){
            $balanceAmount=$amount;
            $coinAmount=0;
            $tradetype='00';
        }elseif($tradetype=='01'){
            $balanceAmount=0;
            $coinAmount=$amount;
            $tradetype='00';
        }else{
            $balanceAmount=$amount;
            $coinAmount=0;
        }
        //echo $tradetype;exit;

        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,pretradeid,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$pretradeid}',";
        $tradeSql.="'{$balanceAmount}','{$coinAmount}','{$customid}','{$cardno}','{$placedtime}','{$tradetype}','abcdefgh','0','{$orderid}')";

        $tradeIf=$this->model->execute($tradeSql);
        $errString="时间：".date('Y-m-d H:i:s').";订单写入记录：{$tradeSql};执行结果：{$tradeIf}\r\n";
        if($flag==1){
            $this->recordError($errString,'consume','卡号_'.$cardno);
        }else{
            $this->recordError($errString,'consume','会员_'.$customid);
        }
        if($tradeIf==true){
            return $tradeid;
        }else{
            return false;
        }
    }

    //更新账户余额,0:余额；1：建业币
    protected function cutAccount($cutInfo){
        $amount=$cutInfo['amount'];
        $cardid=$cutInfo['cardid'];
        $customid=$cutInfo['customid'];
        $type=$cutInfo['type'];
        $accountSql="UPDATE ACCOUNT set amount=amount-{$cutInfo['amount']} where customid='{$cardid}' and type='{$type}'";
        $accountIf=$this->model->execute($accountSql);
        $errString="时间：".date('Y-m-d H:i:s').";更新账户记录：{$accountSql};执行结果：{$accountIf}\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        return $accountIf;
    }

    //劵消费执行
    protected function ticketExe($customid,$quanid,$amount,$panterid,$termposno=null){
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        $quanAccount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field('a.accountid,a.amount,cc.cid,c.cardno')->select();
        $consumedAmount=0;
        $tradIdList = array();
        //print_r($quanAccount);exit;
        foreach($quanAccount as $key=>$val){
            $waitAmount=$amount-$consumedAmount;
            if($waitAmount<=0) break;
            if($waitAmount>=$val['amount']){
                $consumeAmount=$val['amount'];
            }else{
                $consumeAmount=$waitAmount;
            }
            $cardno=$val['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $customid=$val['cid'];
            $termno = $termposno ? $termposno : '0000000';
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$consumeAmount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
            $tradeIf=$this->model->execute($tradeSql);

            $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$val['cid']}' and type='02' and quanid='{$quanid}'";
            $accountIf=$this->model->execute($accountSql);
            if($accountIf==true&&$tradeIf==true){
                $consumedAmount+=$consumeAmount;
                $tradIdList[$key] = $tradeid;
            }
        }
        //echo $consumedAmount;exit;
        if($consumedAmount==$amount){
            return $tradIdList;
        }else{
            return false;
        }
    }

    //新劵消费执行
    protected function newTicketExe($customid,$quanList,$amount,$panterid,$termposno=null){
        if(empty($quanList)) return false;
        $quanid=$quanList['quanid'];
        $accountid=$quanList['accountid'];
        $cardid=$quanList['customid'];
        $cardno=$quanList['cardno'];

        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $map=array('tradeid'=>$tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $termno = $termposno ? $termposno : '0000000';
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'{$amount}','0','{$cardid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','营销劵账户:{$accountid}')";
        $tradeIf=$this->model->execute($tradeSql);

        $accountSql="UPDATE QUAN_ACCOUNT set amount=amount-{$amount} where customid='{$cardid}'  and quanid='{$quanid}' and accountid='{$accountid}'";
        $accountIf=$this->model->execute($accountSql);
        if($accountIf==true&&$tradeIf==true){
            return array($tradeid);
        }else{
            return false;
        }
    }

    protected function ticketExeByCardno($cardno,$quanid,$amount,$panterid,$termposno=null){
        $map=array('cardno'=>$cardno);
        $cardInfo=$this->cards->where($map)->field('customid')->find();
        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $map=array('tradeid'=>$tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $customid=$cardInfo['customid'];
        $termno = $termposno ? $termposno : '0000000';
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
        $tradeIf=$this->model->execute($tradeSql);

        $accountSql="UPDATE ACCOUNT set amount=amount-{$amount} where customid='{$customid}' and type='02' and quanid='{$quanid}'";
        $accountIf=$this->model->execute($accountSql);
        if($accountIf==true&&$tradeIf==true){
            return $tradeid;
        }else{
            return false;
        }
    }

    protected function newTicketExeByCardno($cardno,$quanList,$amount,$panterid,$termposno=null,$shareid=null){
        if(empty($quanList)) return false;
        $quanid=$quanList['quanid'];
        $accountid=$quanList['accountid'];
        $customid=$quanList['customid'];

        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $map=array('tradeid'=>$tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }
        $this->model->startTrans();
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $termno = $termposno ? $termposno : '0000000';
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        if($shareid){
            $tradeSql.="'1','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','营销劵账户:{$accountid}')";
            $shareTradeIf=$this->model->execute($tradeSql);
            $sharesql="UPDATE QUANSHARE set status=2,tradeid='{$tradeid}' where shareid='{$shareid}'";
            $shareIf=$this->model->execute($sharesql);
            if($shareTradeIf==true&&$shareIf==true){
                $this->model->commit();
                return $tradeid;
            }else{
                $this->model->rollback();
                return false;
            }
        }else{
            $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','营销劵账户:{$accountid}')";
            $tradeIf=$this->model->execute($tradeSql);
            if($quanid){
                $accountSql="UPDATE QUAN_ACCOUNT set amount=amount-{$amount} where customid='{$customid}'  and quanid='{$quanid}' and accountid='{$accountid}'";
                $accountIf=$this->model->execute($accountSql);
                if($accountIf==true&&$tradeIf==true){
                    $this->model->commit();
                    return $tradeid;
                }else{
                    $this->model->rollback();
                    return false;
                }
            }else{
                if($tradeIf==true){
                    $this->model->commit();
                    return $tradeid;
                }else{
                    $this->model->rollback();
                    return false;
                }
            }
        }
    }
    //获取可用建业币数量
    public function getCoinDetail($cardno){
        $where=array('ca.status'=>['in',['01','1']],'ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'ca.enddate'=>array('egt',date('Ymd')));
        $account=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->sum('remindamount');
        $account=empty($account)?0:$account;
        return $account;
    }
    //获取建业通宝账户列表
    public function getCoinAccountList($cardno){
        $where=array('ca.status'=>'01','ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'ca.enddate'=>array('egt',date('Ymd')));
        $coinList=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->field('ca.*,c.cardno')->order('ca.placeddate asc')->select();
        return $coinList;
    }

    //获取至尊别账户列表
    public function getPointAccountList($cardno,$panterid){
        $where1=array('pa.type'=>'1','pa.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'pa.enddate'=>array('egt',date('Ymd')));
        $where2=array('pa.type'=>'2','pa.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'pa.enddate'=>array('egt',date('Ymd')));
        $pointList1=$this->cards->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where1)->field('pa.*,c.cardno')->order('pa.placeddate asc')->select();
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->find();
        if(!empty($panter['parent'])){
            $where2['_string']=' pa.panterid='.$panterid.' OR pa.panterid='.$panter['parent'];
        }else{
            $where2['pa.panterid']=$panterid;
        }
        $pointList2=$this->cards->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where2)->field('pa.*,c.cardno')->order('pa.placeddate asc')->select();
        $pointList=array_merge($pointList1,$pointList2);
        return $pointList;
    }

    public function getPointAccountListByCustomid($customid,$panterid,$cardno){
        $cardbrind=substr($cardno,0,4);
        $where1=array('cu.customid'=>$customid,'pa.type'=>'1','c.cardkind'=>$cardbrind,'pa.remindamount'=>array('gt',0));
        $where1['_string']="pa.enddate>='".date('Ymd')."' OR pa.enddate=0";
        $pointList1=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('point_account pa on pa.cardid=c.customid')
            ->where($where1)->field('pa.*,c.cardno')->order('pa.placeddate asc')->select();
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->find();
        $where2=array('cu.customid'=>$customid,'pa.type'=>'2','c.cardkind'=>$cardbrind);
        $where2['_string']=" (pa.panterid=".$panterid." OR pa.panterid=".$panter['parent'].") AND (pa.enddate>='".date('Ymd')."' OR pa.enddate=0)";
        $pointList2=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('point_account pa on pa.cardid=c.customid')
            ->where($where2)->field('pa.*,c.cardno')->order('pa.placeddate asc')->select();
        $pointList=array_merge($pointList1,$pointList2);
        return $pointList;
    }
    //卡号建业币消费执行
    public function coinConsumeExe($coinList,$coinAmount,$panterid,$orderid,$signCode,$termno=null,$signType=null){
        if($coinList==false) return false;
        $consumedAmount=0;
        $cointradidlist = array();//通宝消费订单号列表
        foreach($coinList as $key=>$val){
            $waitAmount=bcsub($coinAmount,$consumedAmount,2);
            //$waitAmount=$coinAmount-$consumedAmount;
            if($waitAmount<=0) break;
            if($waitAmount>=$val['remindamount']){
                $consumeAmount=$val['remindamount'];
            }else{
                $consumeAmount=$waitAmount;
            }
            $errString1="至尊卡号：{$val['cardno']},通宝明细账户：{{$val['coinid']}},消费通宝金额：{$consumeAmount}\r\n";
            if(!empty($signType)){
                $this->recordError($errString1,'consume','卡号_'.$signCode);
            }else{
                $this->recordError($errString1,'consume','卡号_'.$signCode);
            }
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,'tradetype'=>'01',
                'customid'=>$val['cardid'],'orderid'=>$orderid,'cardno'=>$val['cardno'],'termno'=>$termno);
            if(!empty($signType)) $consumeArr['signType']=$signType;
            $tradeIf=$this->tradeExecute($consumeArr);

            $coinSql="UPDATE coin_account set remindamount=remindamount-{$consumeAmount} where coinid='{$val['coinid']}'";
            $coinIf=$this->model->execute($coinSql);


            $accountSql="UPDATE account set amount=amount-{$consumeAmount} where customid='{$val['cardid']}' and type='01'";
            $accountIf=$this->model->execute($accountSql);
            $errString2="时间：".date('Y-m-d H:i:s').";通宝明细账户更新记录：{$coinSql};执行结果：{$coinIf}\r\n";
            $errString2.="时间：".date('Y-m-d H:i:s').";账户更新记录：{$coinSql};执行结果：{$coinIf}\r\n";
            if($tradeIf==true){
                //$coinconsumeid=$this->getnextcode('coinconsumeid',10);
                $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                $currentDate=date('Ymd');
                $currentTime=date('H:i:s');
                //-----------增加字段
                $coinConsumeSql="INSERT INTO coin_consume values('{$coinconsumeid}','{$tradeIf}','{$val['cardid']}',";
                //$coinConsumeSql.="'{$val['coinid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1,0,'','')";
                $coinConsumeSql.="'{$val['coinid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";
                //echo $coinConsumeSql;exit;
                $coinConsemeIf=$this->model->execute($coinConsumeSql);
                // echo  $this->model->getLastSql();exit;
                $errString2.="时间：".date('Y-m-d H:i:s').";通宝消费写入记录：{$coinSql};执行结果：{$coinIf}\r\n";
            }else{
                $coinConsemeIf=false;
            }
            $errString2.="**********************************************************************\r\n";
            if(!empty($signType)){
                $this->recordError($errString2,'consume','卡号_'.$signCode);
            }else{
                $this->recordError($errString2,'consume','会员_'.$signCode);
            }
            if($tradeIf==true&&$coinIf==true&&$accountIf==true&&$coinConsemeIf==true){
                $consumedAmount+=$consumeAmount;
                $cointradidlist[$key] = $tradeIf;//存储订单号
                //$consumedAmount=bcadd($consumedAmount,$consumeAmount);
            }
        }
        if(floatval($consumedAmount,2)==floatval($coinAmount,2)){
            return $cointradidlist;
        }else{
            return false;
        }
    }

    //卡号建业币消费执行
    public function pointConsumeExe($pointList,$coinAmount,$panterid,$orderid,$termno=null){
        if($pointList==false) return false;
        $consumedAmount=0;
        $cointradidlist = array();//通宝消费订单号列表
        foreach($pointList as $key=>$val){
            $waitAmount=$coinAmount-$consumedAmount;
            if($waitAmount<=0) break;
            if($waitAmount>=$val['remindamount']){
                $consumeAmount=$val['remindamount'];
            }else{
                $consumeAmount=$waitAmount;
            }
            $cardno=$val['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $termno=!empty($termno)?$termno:'00000000';
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());

            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$consumeAmount}',0,'{$val['cardid']}','{$cardno}','{$placedtime}','01','abcdefgh','0','{$orderid}')";

            $tradeIf=$this->model->execute($tradeSql);


            $pointSql="UPDATE point_account set remindamount=remindamount-{$consumeAmount} where pointid='{$val['pointid']}'";
            $pointIf=$this->model->execute($pointSql);


            $accountSql="UPDATE account set amount=amount-{$consumeAmount} where customid='{$val['cardid']}' and type='04'";
            $accountIf=$this->model->execute($accountSql);
            if($tradeIf==true){
                //$pointconsumeid=$this->getnextcode('pointconsumeid',10);
                $pointconsumeid=$this->getFieldNextNumber('pointconsumeid');
                $currentDate=date('Ymd');
                $currentTime=date('H:i:s');
                //-----------增加字段
                $pointConsumeSql="INSERT INTO point_consume values('{$pointconsumeid}','{$tradeid}','{$val['cardid']}',";
                $pointConsumeSql.="'{$val['pointid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1)";
                $pointConsemeIf=$this->model->execute($pointConsumeSql);
            }else{
                $pointConsemeIf=false;
            }
            if($tradeIf==true&&$pointIf==true&&$accountIf==true&&$pointConsemeIf==true){
                $consumedAmount+=$consumeAmount;
                $pointtradidlist[$key] = $tradeid;//存储订单号
                //$consumedAmount=bcadd($consumedAmount,$consumeAmount);
            }
        }
        if($consumedAmount==$coinAmount){
            return $pointtradidlist;
        }else{
            return false;
        }
    }

    //账户建业币消费执行
    public function coinExe($consumeArr){
        $orderid=$consumeArr['orderid'];
        $customid=$consumeArr['customid'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $type=$consumeArr['type'];
        $termno=$consumeArr['termno'];
        $cardsList=$this->getCoinCards($customid);
        $waitAmount=$amount;
        $consumedAmount=0;
        $errString="{$customid}通宝消费执行记录\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        foreach($cardsList as $key=>$val){
            if($waitAmount<=0) break;
            $cardbalance=$val['amount'];
            $cardno=$val['cardno'];
            if($waitAmount>=$cardbalance){
                $consumeAmount=$cardbalance;
            }else{
                $consumeAmount=$waitAmount;
            }
            $errString="至尊卡号：{$cardno},消费通宝金额：{$consumeAmount},数据库操作记录：\r\n";
            $this->recordError($errString,'consume','会员_'.$customid);

            $coinList=$this->getCoinAccountList($cardno,$panterid);
            $consumeIf=$this->coinConsumeExe($coinList,$consumeAmount,$panterid,$orderid,$customid,$termno);
            if($consumeIf==true){
                $consumedAmount+=$consumeAmount;
                $waitAmount=$amount-$consumedAmount;
                $cointradidlist[$key] = $consumeIf;
            }
            $errString="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
            $this->recordError($errString,'consume','会员_'.$customid);
        }
        $errString="\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        if($consumedAmount==$amount){
            return $cointradidlist;
        }else{
            return false;
        }
    }

    //账户建业币消费执行
    public function coinExeByCardno($consumeArr){
        $orderid=$consumeArr['orderid'];
        $cardno=$consumeArr['cardno'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $errString="{$cardno}通宝消费执行记录\r\n";
        $errString.="至尊卡号：{$cardno},消费通宝金额：{$amount},数据库操作记录：\r\n";
        $this->recordError($errString,'consume','卡号_'.$cardno);
        $coinList=$this->getCoinAccountList($cardno,$panterid);
        $consumeIf=$this->coinConsumeExe($coinList,$amount,$panterid,$orderid,$cardno,$termno,'cardno');
        if($consumeIf==true){
            return $consumeIf;
        }else{
            return false;
        }
        $errStrin.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
        $errString="\r\n";
        $this->recordError($errString,'consume','卡号_'.$cardno);
    }

    //账户至尊积分消费执行
    public function pointExeByCardno($consumeArr){
        $orderid=$consumeArr['orderid'];
        $cardno=$consumeArr['cardno'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $pointList=$this->getPointAccountList($cardno,$panterid);
        $consumeIf=$this->pointConsumeExe($pointList,$amount,$panterid,$orderid,$termno);
        if($consumeIf==true){
            return $consumeIf;
        }else{
            return false;
        }
    }

    //账户至尊积分消费执行
    public function pointExeByCustomid($consumeArr){
        $customid=$consumeArr['customid'];
        $orderid=$consumeArr['orderid'];
        $cardno=$consumeArr['cardno'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $tradetype=$consumeArr['tradetype'];
        $pretradeid=empty($consumeArr['pretradeid'])?'':$consumeArr['pretradeid'];
        $list=$this->getPointAccountListByCustomid($customid,$panterid,$cardno);
        $consumedAmount=0;
        $waitAmount=$amount;
        $pointtradidlist=array();
        foreach($list as $key=>$val){
            if($waitAmount<=0) break;
            $remindAmount=$val['remindamount'];
            if($remindAmount<=$waitAmount){
                $consumeAmount=$remindAmount;
            }else{
                $consumeAmount=$waitAmount;
            }
            $cardno=$val['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $termno=!empty($termno)?$termno:'00000000';
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());

            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,pretradeid,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$pretradeid}',";
            $tradeSql.="'{$consumeAmount}',0,'{$val['cardid']}','{$cardno}','{$placedtime}','{$tradetype}','abcdefgh','0','{$orderid}')";

            $tradeIf=$this->model->execute($tradeSql);
            $pointSql="UPDATE point_account set remindamount=remindamount-{$consumeAmount} where pointid='{$val['pointid']}'";
            $pointIf=$this->model->execute($pointSql);


            $accountSql="UPDATE account set amount=amount-{$consumeAmount} where customid='{$val['cardid']}' and type='04'";
            $accountIf=$this->model->execute($accountSql);
            if($tradeIf==true){
                //$pointconsumeid=$this->getnextcode('pointconsumeid',10);
                $pointconsumeid=$this->getFieldNextNumber('pointconsumeid');
                $currentDate=date('Ymd');
                $currentTime=date('H:i:s');
                //-----------增加字段
                $pointConsumeSql="INSERT INTO point_consume values('{$pointconsumeid}','{$tradeid}','{$val['cardid']}',";
                $pointConsumeSql.="'{$val['pointid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1)";
                $pointConsumeIf=$this->model->execute($pointConsumeSql);
            }else{
                $pointConsumeIf=false;
            }
            if($tradeIf==true&&$pointIf==true&&$accountIf==true&&$pointConsumeIf==true){
                $consumedAmount+=$consumeAmount;
                $waitAmount-=$consumeAmount;
                $pointtradidlist[$key] = $tradeid;//存储订单号
                //$consumedAmount=bcadd($consumedAmount,$consumeAmount);
            }
        }
        if($consumedAmount==$amount){
            return $pointtradidlist;
        }else{
            return false;
        }
    }

    //账户余额消费执行
    public function consumeExe($consumeArr){
        $orderid=$consumeArr['orderid'];
        $customid=$consumeArr['customid'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $type=$consumeArr['type'];
        $tradetype=$consumeArr['tradetype'];
        $cardsList=$this->getConsumeCards($customid,$type);
        $waitAmount=$amount;
        $consumedAmount=0;
        $errString1="{$customid}余额消费执行记录,时间：".date('Y-m-d H:i:s')."\r\n";
        $this->recordError($errString1,'consume','会员_'.$customid);
        $cashtradidlist = array();
        foreach($cardsList as $key=>$val){
            if($waitAmount<=0) break;
            $cardbalance=$val['amount'];
            $cardno=$val['cardno'];
            if($waitAmount>=$cardbalance){
                $consumeAmount=$cardbalance;
            }else{
                $consumeAmount=$waitAmount;
            }
            $errString1="至尊卡号：{$cardno},消费账户金额：{$consumeAmount},订单执行数据库操作记录：\r\n";
            $this->recordError($errString1,'consume','会员_'.$customid);
            //写入扣款记录
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,
                'customid'=>$val['cardid'],'orderid'=>$orderid,
                'tradetype'=>$tradetype,'cardno'=>$cardno,'termno'=>$termno);
            $tradeIf=$this->tradeExecute($consumeArr);
            if($tradeIf==false) continue;
            //扣除账户余额
            $cutArr=array('amount'=>$consumeAmount,'cardid'=>$val['cardid'],'type'=>$type,'customid'=>$customid);
            $cutInfo=$this->cutAccount($cutArr);
            if($cutInfo==true&&$tradeIf==true){
                //$consumedAmount+=$consumeAmount;
                $consumedAmount=bcadd($consumedAmount,$consumeAmount,2);
                $waitAmount=bcsub($amount,$consumedAmount,2);
                //存储订单号
                $cashtradidlist[$key] = $tradeIf;
            }else{
                continue;
            }
            $errString1="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
            $this->recordError($errString1,'consume','会员_'.$customid);
        }
        $errString1="\r\n";
        $this->recordError($errString1,'consume','会员_'.$customid);
        if($consumedAmount==$amount){
            return $cashtradidlist;
        }else{
            return false;
        }
    }

    //账户余额消费执行
    public function consumeExe1($consumeArr){
        $orderid=$consumeArr['orderid'];
        $customid=$consumeArr['customid'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $type=$consumeArr['type'];
        $cardno=$consumeArr['cardno'];
        $brandid=substr($cardno,0,4);
        $tradetype=$consumeArr['tradetype'];
        $pretradeid=empty($consumeArr['pretradeid'])?'':$consumeArr['pretradeid'];
        $cardsList=$this->getConsumeCards($customid,$type,$brandid);
//        $list=M('account')->where(array('customid'=>'00185159','type'=>'00'))->find();
//        $cardsList=array(array('cardno'=>'6688371800000000006','amount'=>$list['amount'],'cardid'=>'00185159'));
        $waitAmount=$amount;
        $consumedAmount=0;
        $cashtradidlist = array();
        foreach($cardsList as $key=>$val){
            if($waitAmount<=0) break;
            $cardbalance=$val['amount'];
            $cardno=$val['cardno'];
            if($waitAmount>=$cardbalance){
                $consumeAmount=$cardbalance;
            }else{
                $consumeAmount=$waitAmount;
            }
            //写入扣款记录
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,
                'customid'=>$val['cardid'],'orderid'=>$orderid,
                'tradetype'=>$tradetype,'cardno'=>$cardno,'termno'=>$termno,'pretradeid'=>$pretradeid);
            $tradeIf=$this->tradeExecute($consumeArr);
            if($tradeIf==false) continue;

            $typeArr=array('00','21');
            if($this->checkCardBrand($cardno)==true&&in_array($type,$typeArr)){
                $billAccount=$this->checkBillAccount1($cardno,$panterid);
                if($billAccount>0){
                    if($billAccount<=$consumeAmount){
                        $billAmount=$billAccount;
                    }else{
                        $billAmount=$consumeAmount;
                    }
                    $billList=$this->getUsableBillList1($cardno,$panterid);
                    $bool=$this->billingExe($billList,$billAmount,$tradeIf);
                }else{
                    $bool=true;
                }
            }
            //扣除账户余额
            $cutArr=array('amount'=>$consumeAmount,'cardid'=>$val['cardid'],'type'=>$type,'customid'=>$customid);
            //echo $cardbalance-$consumeAmount;exit;
            $cutIf=$this->cutAccount($cutArr);
            if($this->checkCardBrand($cardno)==true){
                if($cutIf==true&&$tradeIf==true&&$bool==true){
                    if(!is_bool($bool)){
                        $cashtradidlist['tradeArr'][]=$tradeIf;
                        $cashtradidlist['billedAmount']+=$bool;
                    }else{
                        $cashtradidlist['tradeArr'][]=$tradeIf;
                    }
                    $consumedAmount+=$consumeAmount;
                    $waitAmount=$amount-$consumedAmount;
                }else{
                    continue;
                }
            }else{
                if($cutIf==true&&$tradeIf==true){
                    $consumedAmount+=$consumeAmount;
                    $waitAmount=$amount-$consumedAmount;
                    $cashtradidlist[] = $tradeIf;
                }else{
                    continue;
                }
            }
        }
        if($consumedAmount==$amount){
            return $cashtradidlist;
        }else{
            return false;
        }
    }

    //单卡余额消费执行
    public function consumeExeByCardno($consumeArr){
        $orderid=$consumeArr['orderid'];
        $cardno=$consumeArr['cardno'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $type=$consumeArr['type'];
        $tradetype=$consumeArr['tradetype'];
        $errString1="{$cardno}余额消费执行记录,时间：".date('Y-m-d H:i:s')."\r\n";
        $this->recordError($errString1,'consume','卡号_'.$cardno);
        $errString1="至尊卡号：{$cardno},消费账户金额：{$amount},订单执行数据库操作记录：\r\n";
        $this->recordError($errString1,'consume','卡号_'.$cardno);
        //写入扣款记录
        $map=array('cardno'=>$cardno);
        $cardInfo=$this->cards->where($map)->field('customid')->find();
        $consumeArr=array('panterid'=>$panterid,'amount'=>$amount,
            'customid'=>$cardInfo['customid'],'orderid'=>$orderid,
            'tradetype'=>$tradetype,'cardno'=>$cardno,'termno'=>$termno,'signType'=>'cardno');
        $tradeIf=$this->tradeExecute($consumeArr);
        if($this->checkCardBrand($cardno)==true){
            $billAccount=$this->checkBillAccount1($cardno,$panterid);
            if($billAccount>0){
                if($billAccount<=$amount){
                    $billAmount=$billAccount;
                }else{
                    $billAmount=$amount;
                }
                $billList=$this->getUsableBillList1($cardno,$panterid);
                $bool=$this->billingExe($billList,$billAmount,$tradeIf);
            }else{
                $bool=true;
            }
        }
        //扣除账户余额
        $cutArr=array('amount'=>$amount,'cardid'=>$cardInfo['customid'],'type'=>$type,'customid'=>$cardInfo['customid']);
        $cutInfo=$this->cutAccount($cutArr);
        $errString1="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
        $this->recordError($errString1,'consume','卡号_'.$cardno);
        $errString1="\r\n";
        $this->recordError($errString1,'consume','卡号_'.$cardno);
        if($this->checkCardBrand($cardno)==true){
            if($cutInfo==true&&$tradeIf==true&&$bool==true){
                if(is_bool($bool)){
                    return array('tradeArr'=>$tradeIf,'billedAmount'=>$bool);
                }else{
                    return array('tradeArr'=>$tradeIf);
                }
            }else{
                return false;
            }
        }else{
            if($cutInfo==true&&$tradeIf==true){
                return $tradeIf;
            }else{
                return false;
            }
        }
    }

    //至尊卡余额消费执行
    public function consumeExeByzzCardno($consumeArr){
        $orderid=$consumeArr['orderid'];
        $customid=$consumeArr['customid'];
        $amount=$consumeArr['amount'];
        $panterid=$consumeArr['panterid'];
        $termno=$consumeArr['termno'];
        $type=$consumeArr['type'];
        $tradetype=$consumeArr['tradetype'];

        $where=array('cc.customid'=>$customid,'a.type'=>'00','c.status'=>'Y','a.amount'=>array('gt',0),
            'c.cardkind'=>array('in',array('2336','6888')));
        $cardList=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->field('c.cardno,c.customid,a.amount')->select();
        $consumedAmount=0;
        $waitAmount=$amount;
        $cashtradidlist=array();
        //写入扣款记录
        foreach($cardList as $key=>$val){
            if($waitAmount<=0) break;
            if($waitAmount<=$val['amount']){
                $consumeAmount=$waitAmount;
            }else{
                $consumeAmount=$val['amount'];
            }
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,
                'customid'=>$val['customid'],'orderid'=>$orderid,
                'tradetype'=>$tradetype,'cardno'=>$val['cardno'],'termno'=>$termno,'signType'=>'cardno');
            $tradeIf=$this->tradeExecute($consumeArr);
            //扣除账户余额
            $cutArr=array('amount'=>$consumeAmount,'cardid'=>$val['customid'],'type'=>$type,'customid'=>$val['customid']);
            $cutInfo=$this->cutAccount($cutArr);
            if($cutInfo==true&&$tradeIf==true){
                $consumedAmount=bcadd($consumedAmount,$consumeAmount,2);
                $waitAmount=bcsub($amount,$consumedAmount,2);
                $cashtradidlist[$key] = $tradeIf;
            }else {
                continue;
            }
        }
        if($consumedAmount==$amount){
            return $cashtradidlist[0];
        }else{
            return false;
        }
    }

    //通过卡号查找会员编号
    public function getCustomid($cardno){
        $field='cc.customid';
        $map=array('c.cardno'=>$cardno);
        $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->where($map)->field($field)->find();
        return $custom['customid'];
    }

    //执行券退回
    public function backQuanExc($tradeid){
        $tradsearch = M('trade_wastebooks')->where(array('tradeid'=>$tradeid,'tradetype'=>'02'))->find();
        if($tradsearch == false){
            return false;
        }
        $this->model->startTrans();
        $tradesql = "update trade_wastebooks set tradetype=23 ,flag=3 where tradeid='{$tradeid}'";
        $trade = M()->execute($tradesql);
        $errString2="时间：".date('Y-m-d H:i:s').";消费明细更新记录：{$tradesql};执行结果：{$trade}\r\n";
        $this->recordError($errString2,'backquan','会员卡编号_'.$tradsearch['customid']);
        $quanpurresult=M('quancz')->where(array('quanid'=>$tradsearch['quanid'],'customid'=>$tradsearch['customid']))->field('quanpurchaseid')->find();
        if($quanpurresult){
            $quanshare=M('quanshare')->where(array('tradeid'=>$tradeid))->find();
            if(empty($quanshare)){
                $tradeamount=$tradsearch['tradeamount'];
            }else{
                $tradeamount=0;
            }
            $accountsql = "update quan_account set amount=amount+{$tradeamount} where quanid='{$tradsearch["quanid"]}'
                and customid='{$tradsearch["customid"]}' and purchaseid='{$quanpurresult["quanpurchaseid"]}'";
            //积分撤销
            $vipresult=M('viptrade')->where(array('orderid'=>$tradeid))->field('finalaccount')->find();
            if($vipresult['finalaccount']>0){
                $finalaccount=floor($vipresult['finalaccount']/20);
                $pointsql="update account set amount=amount-{$finalaccount} where quanid='{$tradsearch["quanid"]}'
                and customid='{$tradsearch["customid"]}' and type='04'";
                $pointresult = M()->execute($pointsql);
            }else{
                $pointresult=true;
            }
        }else{
            $accountsql = "update account set amount=amount+{$tradsearch['tradeamount']} where quanid='{$tradsearch["quanid"]}'
                and customid='{$tradsearch["customid"]}' and type='02'";
        }
        $account = M()->execute($accountsql);
        $errString1="时间：".date('Y-m-d H:i:s').";账户更新记录：{$accountsql};执行结果：{$account}\r\n";
        $this->recordError($errString1,'backquan','会员卡编号_'.$tradsearch['customid']);
        //分享券消费撤销
        if($quanshare) {
            $sharesql = "update quanshare set status=1 where shareid='{$quanshare["shareid"]}'";
            $share = M()->execute($sharesql);
            $errString3 = "时间：" . date('Y-m-d H:i:s') . ";券分享更新记录：{$sharesql};执行结果：{$share}\r\n";
            $this->recordError($errString3, 'backquan', '会员卡编号_' . $tradsearch['customid']);
            if ($trade != true || $share != true||$account!=true||$pointresult!=true) {
                $this->model->rollback();
                return false;
            }
        }else{
            if($trade != true || $account != true||$pointresult!=true){
                $this->model->rollback();
                return false;
            }
        }
        $this->model->commit();
        return true;
    }

    //通宝退款执行
    public function returnCoin($tradeid){
        if(empty($tradeid)) return false;
        $map=array('tradeid'=>trim($tradeid));
        $coinConsumeList=M('coin_consume')->where($map)->select();
        // echo $this->model->getLastSql();exit;
        $c=0;
        foreach($coinConsumeList as $key=>$val){
            //$coinconsumeid=$this->getnextcode('coinconsumeid',10);
            $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $coinConsumeSql="INSERT INTO coin_consume values('{$coinconsumeid}','{$tradeid}','{$val['cardid']}','{$val['coinid']}'";
            $coinConsumeSql.=",'{$val['amount']}','{$currentDate}','{$currentTime}','{$val['panterid']}',0,2,0,'','')";
            $coinAccountSql="UPDATE COIN_ACCOUNT SET remindamount=remindamount+{$val['amount']} WHERE coinid='{$val['coinid']}'";
            $coinAccountIf=$this->model->execute($coinAccountSql);
            $coinConsumeIf=$this->model->execute($coinConsumeSql);
            if($coinAccountIf==true&&$coinConsumeIf==true){
                $c++;
            }
        }
        if($c==count($coinConsumeList)&&!empty($coinConsumeList)){
            return true;
        }else{
            return false;
        }
    }

    //查询卡片是否交易超限
    public function checkTradeLimited($cardno,$amount,$panterid){
        $map=array('panterid'=>$panterid);
        $panterAccount=M('panter_con_account')->where($map)->find();
        if($panterAccount==false){
            return true;
        }
        if($panterAccount['d_sum_account']==0&&$panterAccount['d_sum_number']==0&&$panterAccount['d_one_account']==0){
            return true;
        }
        if($amount>$panterAccount['d_one_account']){
            return false;
        }
        $map1=array('panterid'=>$panterid,'cardno'=>$cardno,'placeddate'=>date('Ymd'));
        $field="count(*) c,sum(tradeamount) s";
        $list=M('trade_wastebooks')->where($map1)->field($field)->find();
        if(($list['c']+1)>$panterAccount['d_sum_number']){
            return false;
        }
        if($list['s']+$amount>$panterAccount['d_sum_account']){
            return false;
        }
        return true;
    }

    public function checkIssue($panterid){
        $where=array('panterid'=>$panterid,'type'=>1);
        $where['panterid']=$panterid;
        $panterAccount=M('panteraccount')->where($where)->find();
        if($panterAccount['status']==1){
            return true;
        }else{
            return false;
        }
    }
    //获取商户积分赠送方式
    public function checkPointSendtype($cardno,$panterid){
        //--------end
        $brandid=substr($cardno,0,4);
        $map=array('brandid'=>$brandid,'panterid'=>$panterid);
        $list=M('point_config')->where($map)->find();
        if($list==false){
            $panter=M('panters')->where(array('panterid'=>$panterid))->field('panterid,parent')->find();

            $map1=array('brandid'=>$brandid,'panterid'=>$panter['parent']);
            $list1=M('point_config')->where($map1)->find();
            if($list1==false){
                return false;
            }else{
                return $list1;
            }
        }else{
            return $list;
        }
    }

    public function checkCardBrand($cardno){
        $brandid=substr($cardno,0,4);
        if($brandid!='6688'){
            return false;
        }else{
            return true;
        }
    }

    //获取可开发票最大金额（未开发票的充值）
    public function checkBillAccount($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>0,'b.flag'=>0);
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field('sum(cpl.amount-b.usedamount) usableamount')->find();
        return $list['usableamount'];
    }

    //获取可开发票的充值列表(充值时未开发票的充值)
    public function getUsableBillList($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>0,'b.flag'=>0);
        $field="cpl.*,b.status,b.usedamount";
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field($field)->order('cpl.placeddate')->select();
        return $list;
    }

    //获取可开发票最大金额（已开发票的充值）
    public function checkBillAccount1($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>1,'b.flag'=>0);
        $field="cpl.*,b.status,b.usedamount";
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field('sum(cpl.amount-b.usedamount) usableamount')->find();
        return $list['usableamount'];
    }

    //获取可开发票的充值列表(充值时已开发票的充值)
    public function getUsableBillList1($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>1,'b.flag'=>0);
        $field="cpl.*,b.status,b.usedamount";
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field($field)->order('cpl.placeddate')->select();
        return $list;
    }
    //开发票操作执行
    public function billingExe($billList,$amount,$tradeid){
        if(empty($billList)) return false;
        $waitAmount=$amount;
        $exedAmount=0;
        $tradeBill=M('tradebilling');
        $billingDetail=M('billingdetail');
        $trade_wastebooks=M('trade_wastebooks');
        foreach($billList as $key=>$val){
            if($waitAmount<=0) break;
            $cardPurchaseid=$val['card_purchaseid'];
            $cardno=$val['cardno'];
            $currentDte=date('Ymd');
            $currentTime=date('H:i:s');
            $usableAmount=$val['amount']-$val['usedamount'];
            if($usableAmount<=$waitAmount){
                $exeAmount=$usableAmount;
                $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)+'{$exeAmount}',FLAG=1 WHERE CARD_PURCHASEID='{$cardPurchaseid}'";
            }else{
                $exeAmount=$waitAmount;
                $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)+'{$exeAmount}' WHERE CARD_PURCHASEID='{$cardPurchaseid}'";
            }
            $billingDeataiSql="INSERT INTO BILLINGDETAIL VALUES('{$cardPurchaseid}','{$cardno}','{$currentDte}','{$currentTime}','{$exeAmount}','{$tradeid}')";
            $billIf=$this->model->execute($billingSql);
            $billingDeataiIf=$this->model->execute($billingDeataiSql);
            $map=array('tradeid'=>$tradeid);
            $list=$tradeBill->where($map)->find();
            $list1=$trade_wastebooks->where($map)->find();
            if($list==false){
                if($exeAmount<$list1['tradeamount']){
                    $tradeBillingSql="INSERT INTO TRADEBILLING VALUES ('{$tradeid}','0','{$exeAmount}')";
                }else{
                    $tradeBillingSql="INSERT INTO TRADEBILLING VALUES ('{$tradeid}','1','{$exeAmount}')";
                }
            }else{
                $billedAccount=$billingDetail->where($map)->sum('usedamount');
                if($billedAccount>=$list1['tradeamount']){
                    $tradeBillingSql="UPDATE TRADEBILLING SET STATUS=1,AMOUNT=AMOUNT+{$exeAmount} WHERE tradeid='{$tradeid}'";
                }else{
                    $tradeBillingSql="UPDATE TRADEBILLING SET AMOUNT=AMOUNT+{$exeAmount} WHERE tradeid='{$tradeid}'";
                }
            }
            $tradeBillingIf=$this->model->execute($tradeBillingSql);
            if($billIf==true&&$billingDeataiIf==true&&$tradeBillingIf==true){
                $waitAmount=$waitAmount-$exeAmount;
                $exedAmount=$exedAmount+$exeAmount;
            }
        }
        if($exedAmount==$amount){
            return floatval($amount);
        }else{
            return false;
        }
    }
    //查询会员信息
    protected function customsInfo($customid){
        $namechinese=M('customs')->where("customid='".$customid."'")->find();
        return $namechinese;
    }
    //获取账户下可充值的余额
    public function getUsableAccount($customid,$cardno){
        $cardBrand=substr($cardno,0,4);
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardno'=>array('neq',$cardno),'c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000));
        $rechargedAccount=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->sum('a.amount');
        $rechargedC=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')->where($map)->count();
        return 5000*$rechargedC-$rechargedAccount;
    }

    //获取账户下可充值的卡号
    public function getUsableAccCards($customid,$cardno){
        $cardBrand=substr($cardno,0,4);
        $map=array('cu.customid'=>$customid,'a.type'=>'00','c.cardno'=>array('neq',$cardno),'c.cardkind'=>$cardBrand,'a.amount'=>array('lt',5000));
        $usableCards=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on c.customid=a.customid')
            ->where($map)->field('c.cardno')->select();
        $cardArr=$this->serializeArr($usableCards,'cardno');
        return $cardArr;
    }

    //获取商户的行业属性
    public function getHysx($panterid){
        $where['panterid']=$panterid;
        $panterInfo=D('panters')->where($where)->find();
        return $panterInfo['hysx'];
    }

    public function checkPanterLimit($cardPanterid,$panterid){
        $panters=M('panters');
        $panter1=$panters->where(array('panterid'=>$cardPanterid))->find();
        $panter2=$panters->where(array('panterid'=>$panterid))->find();
        if($panter2['hysx']=='酒店'){
            if($panter1['hysx']!='酒店'){
                return '01';
            }else{
                return true;
            }
        }else{
            if($panter1['hysx']=='酒店'){
                return '02';
            }else{
                return true;
            }
        }
    }

    //检测卡有效期
    public function checkCardValidate($cardno){
        if(empty($cardno)) return false;
        $map=array('cardno'=>$cardno);
        $currentDate=date('Ymd');
        $card=$this->cards->where($map)->field('exdate')->find();
        if($card['exdate']<$currentDate){
            return false;
        }else{
            return true;
        }
    }

    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $list=$this->model->query($sql);
        $fieldLength=$this->fieldsLength[$field];
        $lastNumber=$list[0]['nextval'];
        return $this->getnumstr($lastNumber,$fieldLength);
    }
    //获取账户下券总量及每种券数量等信息
    public function getQuanByCardno($cardno,$panterid){
        //判断此卡是否有折扣权限
        $discounttype=1;
        $discount=M('discount_renewal')->where(array('cardno'=>$cardno))->order('operatedate desc')->find();
        if($discount){
            if(time()>strtotime($discount['renewaldate'])){
                $discounttype=2;
            }
        }else{
            $cards=M('cards')->where(array('cardno'=>$cardno))->find();
            if(time()>strtotime($cards['vipdate'])){
                $discounttype=2;
            }
        }
        $where['c.cardno']=$cardno;
        $where['c.panterid']=$panterid;
        $where['qu.amount']=array('gt','0');
        //未使用,已使用--过期、消费、分享
        $data=$this->cards->alias('c')->join('quan_account qu on qu.customid=c.customid')
            ->join('quankind q on q.quanid=qu.quanid')
            ->where(array('c.cardno'=>$cardno,'c.panterid'=>$panterid))
            ->field('c.cardno,c.customid,qu.amount,qu.accountid,qu.enddate,qu.quanid,q.quanname')->select();
        foreach($data as $k=>$v){
            //区分券未使用及过期
            if(time()>strtotime($v['enddate'])){
                $overdue[]=$v;
            }else{
                $notoverdue[]=$v;
            }
        }
        //券已使用(消费)
        $trade=$this->quanTrade($cardno,$panterid);
        //券已使用(分享)
        $share=$this->quanShare($cardno);
        if($overdue||$notoverdue||$trade||$share){
            $result=array('overdue'=>$overdue,'notoverdue'=>$notoverdue,'trade'=>$trade,'share'=>$share,'discounttype'=>$discounttype);
        }else{
            $result=array('discounttype'=>$discounttype);
        }
        return $result;
    }
    public function quanTrade($cardno,$panterid){
        //券已使用(消费)
        $trade=M('trade_wastebooks')->alias('tw')->join('quankind qu on tw.quanid=qu.quanid')
            ->where(array('tw.cardno'=>$cardno,'tw.panterid'=>$panterid))->field('tw.tradeamount,tw.placeddate,tw.placedtime,qu.quanname')->select();
        if($trade){
            return $trade;
        }
    }
    public function quanShare($cardno){
        //券已使用(消费)
        $share=M('quanshare')->alias('q')->join('quankind qu on q.quanid=qu.quanid')
            ->where(array('q.cardno'=>$cardno))->field('q.amount,q.sharedate,q.sharetime,qu.quanname')->select();
        if($share){
            return $share;
        }
    }

	//-------通宝消费 按有效期进行-------
	protected function consumeCoin($consumeArr){
		$orderid  = $consumeArr['orderid'];
		$customid = $consumeArr['customid'];
		$amount   = $consumeArr['amount'];
		$panterid = $consumeArr['panterid'];
		$type     = $consumeArr['type'];
		$termno   = !empty($consumeArr['termno'])?$consumeArr['termno']:'00000000';
		$errString="{$customid}通宝消费执行记录\r\n";
		$this->recordError($errString,'consume','会员_'.$customid);

        $limit    = $this->jyAcceptanceLimit($panterid);
        if($limit===1){
            $coinLists = $this->coinAccountList($customid);
        }else{
            $coinLists = $this->coinAccount2List($customid);
            $oldSum    = array_sum(array_column($coinLists,'remindamount'));
            if($oldSum<$amount){
                returnMsg(['status'=>'035','codemsg'=>'2019年之后发行的通宝不能用于物业缴费，详情咨询各物业公司。或致电9617777']);
            }
        }
		$coinAccountSql = '';
		$coinConsumeSql = '';
		$tradeSql       = '';
		$placeddate     =date('Ymd');
		$placedtime     =date('H:i:s');
		$time           = time();
		if($coinLists){
			$leave =  $amount;
			foreach ($coinLists as $coin){
				if($leave>0){
					if($coin['remindamount']<=$leave){
						$leave = bcsub($leave,$coin['remindamount'],2);
						$reduce= $coin['remindamount'];
					}else{
						$reduce= $leave;
						$leave = 0;
					}
					$card = M('cards')->where(['customid'=>$coin['cardid']])->field('cardno')->find();
					$coinAccountSql.=" WHEN '{$coin['coinid']}' THEN {$reduce}";

					$tradeid       =  $tradeid=substr($card['cardno'],15,4).date('YmdHis',$time);
					$coinconsumeid = $this->getFieldNextNumber('coinconsumeid');

					$coinConsumeSql .=" INTO coin_consume values('{$coinconsumeid}','{$tradeid}','{$coin['cardid']}','{$coin['coinid']}','{$reduce}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'{$time}','0000000000000000')";

					$tradeSql.=" into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
					$tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
					$tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
					$tradeSql.="'0','{$reduce}','{$coin['cardid']}','{$card['cardno']}','{$placedtime}','00','abcdefgh','0','{$orderid}') ";

					$deduct[] = ['cardid'=>$coin['cardid'],'amount'=>$reduce];
					$time++;
					$coinid[] = $coin['coinid'];

					$publish[] = ['name'=>$coin['namechinese'],'cut'=>$reduce];
					$tradeList[] = $tradeid;
				}
			}
			//扣款 相关发行方信息
//			$cutPulish = $this->returnPublish($publish);
			$coinidIn = $this->inString($coinid);
			$coinAccountSql = "UPDATE coin_account set remindamount = remindamount - CASE coinid ".$coinAccountSql. " END WHERE coinid in ($coinidIn)";
			$coinConsumeSql = "INSERT ALL ".$coinConsumeSql. " select 1 from dual ";
			$tradeSql       =  "INSERT ALL ".$tradeSql. " select 1 from dual ";
			$accountSql = $this->getAccountSql($deduct);
			$updateString = $placeddate." ".$placedtime.":\n\t".$coinAccountSql."\n\t".$coinConsumeSql."\n\t".$tradeSql."\n\t".$accountSql;
			$this->recordError($updateString,'consume','会员_'.$customid);
			try{
				$coinAccountIf	= $this->model->execute($coinAccountSql);
				$coinConsumeIf   = $this->model->execute($coinConsumeSql);
				$tradeIf         = $this->model->execute($tradeSql);
				$accountIf       = $this->model->execute($accountSql);
			}catch (\Exception $e){
				return false;
			}
			if($leave==0 && $coinAccountIf && $coinConsumeIf && $tradeIf && $accountIf){
				return $tradeList;
			}else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	//通宝卡按发行记录扣款 返回所有查询记录
//	protected function coinAccountList($customid){
//		$day = date('Ymd');
//		$SQL  = "SELECT coin.remindamount,coin.cardid,coin.coinid,p.namechinese FROM COIN_ACCOUNT coin left join panters p on coin.panterid=p.panterid WHERE coin.CARDID IN(SELECT cid from customs_c where customid='{$customid}') AND coin.ENDDATE>='{$day}' AND coin.remindamount>0 ORDER  BY  ENDDATE ASC ";
//		$coinList = $this->model->query($SQL);
//		return $coinList;
//	}
    protected function coinAccountList($customid){
        $day = date('Ymd');
        $where['cu.customid'] = $customid;
        $where['ca.cardkind'] = '6889';
        $where['ca.cardfee']  = ['in',['1','2']];
        $where['ca.status']   = 'Y';
        $where['coin.enddate']= ['egt',$day];
        $where['coin.remindamount'] = ['gt','0'];
        $field  = 'coin.remindamount,coin.cardid,coin.coinid,p.namechinese,coin.enddate';
        $coinList  = $this->model->table('customs')->alias('cu')
            ->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards ca on ca.customid=cc.cid')
            ->join('left join coin_account coin on coin.cardid=ca.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->order('coin.enddate asc')->select();
        return $coinList;
    }
	protected function getAccountSql($customArr)
	{
		$keys = array_unique(array_column($customArr,'cardid'));
		if(count($customArr)===count($keys)){
			$foreach = $customArr;
		}else{
			foreach($customArr as $val){
				if(isset($foreach[$val['cardid']])){
					$foreach[$val['cardid']]['amount'] = bcadd($foreach[$val['cardid']]['amount'],$val['amount'],2);
				}else{
					$foreach[$val['cardid']] = $val;
				}
			}
		}

		$accounSql = '';
		foreach ($foreach as $info){
			$accounSql .= "WHEN '{$info['cardid']}' THEN {$info['amount']} ";
		}
		$inKeys = $this->inString($keys);
		$accounSql = "UPDATE account set amount= amount - case customid " .$accounSql. "END where customid in ({$inKeys}) AND type='01' ";
		return $accounSql;
	}
	/*
    * 子查询的时 数组转为 in 查询时所需的字符串
    * @param array $arr 一维数组信息
    * @return sting
    */
	protected function inString($arr){
		$str='';
		foreach($arr as $val){
			$str.="'".$val."',";
		}
		return rtrim($str,',');
	}
	//---------------------通宝按有效期扣款 end
    protected function jyAcceptanceLimit($panterid){
        $map['panterid'] = $panterid;
        $acceptance = M('panters')->where($map)->field('jyacceptance')->find();
        if($acceptance){
            if($acceptance['jyacceptance']==='0'){//受理所有痛吧
                return 1;
            }elseif ($acceptance['jyacceptance']==='1'){
                return 2;
            }else{
                returnMsg(['status'=>'034','codemsg'=>'校验信息错误,此商户不受理通宝业务']);
            }
        }else{
            returnMsg(['status'=>'033','codemsg'=>'查询错误,未能获取通宝受理范围']);
        }
    }
    protected function coinAccount2List($customid){
	    $result=M('whitelist')->where(['status'=>1,'customid'=>$customid])->field('sourceorder')->select();
       if($result){
           $array=[];
           foreach ($result as $v){
               $array[]=$v['sourceorder'];
           }
           $map["coin.sourceorder"]=['in',implode(',',$array)];
           $map['coin.placeddate']= ['lt','20190101'];
           $map['_logic']='or';
           $where['_complex']=$map;
       }else{
           $where['coin.placeddate']  =  ['lt','20190101'];
       }

        $day = date('Ymd');
        $where['cu.customid'] = $customid;
        $where['ca.cardkind'] = '6889';
        $where['ca.cardfee']  = ['in',['1','2']];
        $where['ca.status']   = 'Y';
        $where['coin.enddate']= ['egt',$day];
        $where['coin.remindamount'] = ['gt','0'];

        $field  = 'coin.remindamount,coin.cardid,coin.coinid,p.namechinese,coin.enddate';
        $coinList  = $this->model->table('customs')->alias('cu')
            ->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards ca on ca.customid=cc.cid')
            ->join('left join coin_account coin on coin.cardid=ca.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->order('coin.enddate asc')->select();
        return $coinList;
    }
    public function test(){
	    var_dump($this->coinAccount2List('00166349'));exit;
    }
}
