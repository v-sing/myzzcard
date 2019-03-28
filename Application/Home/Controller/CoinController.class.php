<?php
namespace Home\Controller;
use Org\Util\DESedeCoder;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
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
    public function _initialize(){
        $this->model=new model();
        $this->customs=M('customs');
        $this->account=M('account');
        $this->cards=M('cards');
        $this->customs_c=M('customs_c');

        $this->userid='0000000000000000';
        $this->keycode='JYO2O01';
//        $this->panterArr=array('1001'=>array('panterid'=>'00000180','pname'=>'e+','prefix'=>'e+_'),
//            '1002'=>array('panterid'=>'00000134','pname'=>'南阳公司'),
//            '1003'=>array('pname'=>'fzg','prefix'=>'fzg_'),
//            '1004'=>array('panterid'=>'00000182','pname'=>'football','prefix'=>'ft_'),
//            'SOON-O2O-0001'=>array('panterid'=>'00000180','pname'=>'e+','prefix'=>'e+_'),
//            'SOON-BALL-0001'=>array('panterid'=>'00000182','pname'=>'football','prefix'=>'ft_')
//        );
        $this->panterArr=array(
           '1001'=>array('panterid'=>'00000286','pname'=>'e+','prefix'=>'e+_','userid'=>'0000000000000204'),
           '1002'=>array('panterid'=>'00000284','pname'=>'南阳公司'),
           '1003'=>array('pname'=>'fzg','prefix'=>'fzg_'),
           '1004'=>array('panterid'=>'00000290','pname'=>'football','prefix'=>'ft_','userid'=>'0000000000000205'),
           '1005'=>array('prefix'=>'pos'),
           '1006'=>array('prefix'=>'soonpay_'),
           #start
           '1007'=>array('prefix'=>'wt_'),
           #end
           'SOON-O2O-0001'=>array('panterid'=>'00000286','pname'=>'e+','prefix'=>'e+_','userid'=>'0000000000000204'),
           'SOON-BALL-0001'=>array('panterid'=>'00000290','pname'=>'football','prefix'=>'ft_','userid'=>'0000000000000205')
       );
        $this->storeArr=array(
            '33111'=>'00000125','33073'=>'00000118','32892'=>'00000013','33104'=>'00000270',
            '33072'=>'00000127','33071'=>'00000126','33102'=>'00000295','32880'=>'00000241',
            '32996'=>'00000466','33102'=>'00000294','33396'=>'00000862'
        );
        $this->fieldsLength=C('FIELDS_LENGTH');
        $this->DESedeCoder=new  DESedeCoder();
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
            $errString="{$linktel}充值执行记录\r\n";
            $sql="INSERT INTO CUSTOMS(customid,linktel,placeddate,paswd,customlevel,countrycode) ";
            $sql.="values ('{$customid}','{$linktel}','{$currentDate}','{$paswd}','{$customlevel}','{$countrycode}')";
        }elseif($type==2){
            $linktel=$customArr['linktel'];
            $panterid=$customArr['panterid'];
            $namechinese=$customArr['namechinese'];
            $personid=$customArr['personid'];
            $customlevel='建业线上会员';
            $sql="INSERT INTO CUSTOMS(customid,namechinese,linktel,placeddate,personid,panterid,customlevel,countrycode) values ";
            $sql.="('{$customid}','{$namechinese}','{$linktel}','{$currentDate}','{$personid}','{$panterid}','{$customlevel}','房掌柜会员')";
            //echo $sql;exit;
        }
        $customIf=$this->model->execute($sql);
        $errString.="时间：".date('Y-m-d H:i:s').";账户信息写入：{$sql};执行结果：{$customIf}\r\n";
        $this->recordError($errString,'createAccount','会员_'.$customid);
        
        $this->recordError($errString, "YjTbpost", "createAccount");
        if($customIf==true){
            return $customid;
        }else{
            return false;
        }
    }

    //开卡执行
    protected function openCard($cardArr,$customid,$amount=0,$panterid=null,$type,$sourceRechargeId=null,$userid=null,$isActive=1){
        if(empty($cardArr)) return false;
        if($type==1){
            $childPath='createAccount';
            $errString="{$customid}会员注册开卡执行记录\r\n";
        }elseif($type==2){
            $childPath='customRecharge';
            $errString="{$customid}充值开卡执行记录\r\n";
        }
        $description=empty($userid)?'后台充值':"后台接口充值:".$sourceRechargeId;
        $userid=empty($userid)?$this->userid:$userid;
        $rechargedAmount=0;
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
            $errString.="至尊卡号：{$cardno},充值金额：{$rechargeMoney},开卡流程数据库操作记录：\r\n";
            //写入购卡单并审核
            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','转账','";
            $customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
            $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time())."',NULL)";

            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time())."')";

            $customplIf=$this->model->execute($customplSql);
            $auditlogsIf=$this->model->execute($auditlogsSql);
            $errString.="时间：".date('Y-m-d H:i:s').";售卡单入：{$customplSql};执行结果：{$customplIf}\r\n";
            $errString.="时间：".date('Y-m-d H:i:s').";售卡单审核：{$auditlogsSql};执行结果：{$auditlogsIf}\r\n";

            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            //写入购卡充值单
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time())."','1','{$description}',";
            $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);
            $errString.="时间：".date('Y-m-d H:i:s')."售卡充值单写入：{$cardplSql};执行结果：{$cardplIf}\r\n";

            $where1['customid']=$customid;
            $card=$this->cards->where($where1)->find();
            $customs_c=$this->customs_c->where(array('cid'=>$customid))->find();
            if($card==false&&$customs_c==false){
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                $cardId=$customid;
            }else{
                //若存在，则需另外生成卡编号
                //$cardId=$this->getnextcode('customs',8);
                $cardId=$this->getFieldNextNumber("customid");
                $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
                $customIf=$this->model->execute($customSql);
                $errString.="时间：".date('Y-m-d H:i:s').";更新会员：{$customSql};执行结果：{$customIf}\r\n";
            }
            //echo $cardId;exit;
            //执行激活操作

            if($isActive==0){
                $cardAlIf=true;
            }else{
                $cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
                $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
                $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
                $cardAlIf=$this->model->execute($cardAlSql);
                $errString.="时间：".date('Y-m-d H:i:s').";激活记录：{$customSql};执行结果：{$cardAlIf}\r\n";
            }

            //关联会员卡号
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
            $customsIf=$this->model->execute($customcSql);
            $errString.="时间：".date('Y-m-d H:i:s').";关联会员和卡：{$customcSql};执行结果：{$customsIf}\r\n";
            //echo $this->model->getLastSql();exit;

            //更新卡状态为正常卡，更新卡有效期；通宝卡不激活
            if($isActive==0){
                $exd=date('Ymd',strtotime("+3 years"));
                $cardSql="UPDATE cards SET status='A',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
                $cardIf=$this->model->execute($cardSql);
                $errString.="时间：".date('Y-m-d H:i:s').";更新卡状态：{$cardSql};执行结果：{$cardIf}\r\n";
            }else{
                $exd=date('Ymd',strtotime("+3 years"));
                $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
                $cardIf=$this->model->execute($cardSql);
                $errString.="时间：".date('Y-m-d H:i:s').";更新卡状态：{$cardSql};执行结果：{$cardIf}\r\n";
            }

            //给卡片添加账户并给账户充值
            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
            $balanceIf=$this->model->execute($balanceSql);
            $errString.="时间：".date('Y-m-d H:i:s').";创建余额账户：{$balanceSql};执行结果：{$balanceIf}\r\n";

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $coinSql.=$acid."','".$cardId."','0','01',NULL)";
            $coinIf=$this->model->execute($coinSql);
            $errString.="时间：".date('Y-m-d H:i:s').";创建建业通宝账户：{$coinSql};执行结果：{$coinIf}\r\n";

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $pointSql.=$acid."','".$cardId."','0','04',NULL)";
            $pointIf=$this->model->execute($pointSql);
            $errString.="时间：".date('Y-m-d H:i:s').";创建至尊积分账户：{$pointSql};执行结果：{$pointIf}\r\n";

            //在账户绑定记录记录下此卡和用户（注册用户状态为0，绑定卡状态为1）
            if(!empty($panterid)){
                $ecardBindSql="INSERT INTO ecard_bind values('{$cardno}','{$customid}',0,'{$panterid}','','')";
                $ecardBindIf=$this->model->execute($ecardBindSql);
                $errString.="时间：".date('Y-m-d H:i:s').";写入绑卡信息：{$ecardBindSql};执行结果：{$ecardBindIf}\r\n";
            }else{
                $ecardBindIf=true;
            }
            $errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true&&$ecardBindIf==true){
                $rechargedAmount+=$rechargeMoney;
            }
        }
        $errString.="\r\n";
        $this->recordError($errString,$childPath,'会员_'.$customid);
        if(!bccomp($rechargedAmount,$amount,2)){
            return true;
        }else{
            return false;
        }
    }

    //充值执行
    protected function recharge($cardArr,$customid,$rechargeAmount,$sourceRechargeId,$userid){
        if(empty($cardArr)) return false;
        $errString="{$customid}充值执行记录\r\n";
        $rechargedAmount=0;
        $userid=empty($userid)?$this->userid:$userid;
        foreach($cardArr as $val){
            $waitAmount=$rechargeAmount-$rechargedAmount;
            if($waitAmount<=0) break;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');

            $where['c.cardno']=$cardno;
            $where['a.type']='00';
            $card=$this->cards->alias('c')->join('account a on a.customid=c.customid')
                ->where($where)->field('c.customid,a.amount,c.panterid')->find();
            $rechargableAmount=5000-$card['amount'];
            if($rechargableAmount<=$waitAmount){
                $rechargeMoney=$rechargableAmount;
            }else{
                $rechargeMoney=$waitAmount;
            }
            $errString.="至尊卡号：{$cardno},充值金额：{$rechargeMoney},充值流程数据库操作记录：\r\n";
            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','转账','";
            $customplSql.=$userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
            $customplSql.="',1,'','后台接口充值:{$sourceRechargeId}','1','".$card['panterid']."','".$userid."',NULL,'1',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time())."',NULL)";

            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'一家充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time())."')";

            $customplIf=$this->model->execute($customplSql);
            $auditlogsIf=$this->model->execute($auditlogsSql);
            $errString.="时间：".date('Y-m-d H:i:s').";充值申请单写入：{$customplSql};执行结果：{$customplIf}\r\n";
            $errString.="时间：".date('Y-m-d H:i:s').";充值申请审核单写入：{$auditlogsSql};执行结果：{$auditlogsIf}\r\n";

            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

            //写入充值单
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time())."','1','后台接口充值:{$sourceRechargeId}',";
            $cardplSql.="'{$userid}','{$card['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);
            $errString.="时间：".date('Y-m-d H:i:s').";充值记录写入：{$cardplSql};执行结果：{$cardplIf}\r\n";

            //更新卡片账户
            $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$card['customid']."' and type='00'";
            $balanceIf=$this->model->execute($balanceSql);
            $errString.="时间：".date('Y-m-d H:i:s').";更新账户余额信息：{$balanceSql};执行结果：{$balanceIf}\r\n";
            $errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";

            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true){
                $rechargedAmount+=$rechargeMoney;
            }
        }
        $errString.="\r\n";
        $this->recordError($errString,'customRecharge','会员_'.$customid);
        if(!bccomp($rechargedAmount,$rechargeAmount,2)){
                return true;
            }else{
                return false;
        }
    }

    /*建业币充值
    protected function coinRecharge($cardInfo,$coinArr,$panterid){
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
        $cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $purchaseid=substr($cardpurchaseid,1,16);
        $userip='127.0.0.1';
        $coinAmount=$coinArr['totalAmount'];
        $coinAmount1=$coinArr['coinAmount1'];
        $coinAmount2=$coinArr['coinAmount2'];
        $type=$coinArr['type'];
        $errString.="至尊卡号：{$cardno},充值金额：{$coinAmount}，可用余额：{$coinAmount1}，锁定金额：{$coinAmount2},充值流程数据库操作记录：\r\n";
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql.=trim($cardno)."',0,'{$coinAmount}','{$nowdate}','{$nowtime}','1','建业币接口充值:{$orderid}','";
        $cardplSql.=$userid."','{$panterid}','{$userip}','00000000')";

        $accountSql="UPDATE account SET amount=nvl(amount,0)+".$coinAmount." where customid='".$cardid."' and type='01'";

        $cardplif  =$this->model->execute($cardplSql);
        $accounteif = $this->model->execute($accountSql);
        $errString.="时间：".date('Y-m-d H:i:s').";建业通宝充值记录写入：{$cardplSql};执行结果：{$cardplif}\r\n";
        $errString.="时间：".date('Y-m-d H:i:s').";更新通宝账户信息：{$accountSql};执行结果：{$accounteif}\r\n";

        *老建业通宝发行
         * 建业币充值转台有激活状态（通用）、
        半激活状态（app上、房产本项目使用）、
        锁定状态（项目刷卡时发行本项目使用，其他项目或者商户均不能使用，购房后激活转化为激活装热爱）
        *
        if($type==2){
            $coinid=$this->getnextcode('coinid',8);
            $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','03','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
            $coinIf1=$this->model->execute($coinSql1);
            $coinIf2=true;
            $errString.="时间：".date('Y-m-d H:i:s').";通宝通用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
        }else{
            if($coinAmount2>0){
                $coinid=$this->getnextcode('coinid',8);
                $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
                $coinIf1=$this->model->execute($coinSql1);
                $errString.="时间：".date('Y-m-d H:i:s').";通宝可用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
                $coinid=$coinid=$this->getnextcode('coinid',8);
                $coinSql2="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount2}','{$nowdate}','{$nowtime}','02','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
                $coinIf2=$this->model->execute($coinSql2);
                $errString.="时间：".date('Y-m-d H:i:s').";通宝锁定账户明细写入：{$coinSql2};执行结果：{$coinIf2}\r\n";
            }else{
                $coinid=$this->getnextcode('coinid',8);
                $coinSql1="INSERT INTO coin_account values('{$accountid}','{$coinAmount}','{$coinAmount1}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}')";
                //echo $coinSql1;exit;
                $coinIf1=$this->model->execute($coinSql1);
                $errString.="时间：".date('Y-m-d H:i:s').";通宝可用账户明细写入：{$coinSql1};执行结果：{$coinIf1}\r\n";
                $coinIf2=true;
            }
            $errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
        }
        $errString.="\r\n";
        $this->recordError($errString,'rechargeCoin','会员_'.$customid);
        if($cardplif==true&&$accounteif==true&&$coinIf1==true&&$coinIf2==true){
            return true;
        }else{
            return false;
        }
    }*/

    //建业币充值(发行通宝为01状态，若出现退款更新为00)
    protected function coinRecharge($cardInfo,$coinAmount,$panterid){
        $cardid=$cardInfo['cardid'];
        $cardno=$cardInfo['cardno'];
//        $accountid=$cardInfo['accountid'];
        $accountid=substr($cardInfo['accountid'], 0, 8);//因数据库字段长度为8，此处获取的字符串长度16位，所以截取处理 by muyushan 2017-09-27
        $orderid=$cardInfo['orderid'];
        $customid=$cardInfo['customid'];
        $errString="{$customid}充值执行记录\r\n";
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        $enddate=date('Ymd',strtotime('+2 years'));
        $userid = '0000000000000000';
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

        $purchaseid='F'.substr($cardpurchaseid,3,15);
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
        $coinSql="INSERT INTO coin_account (accountid,rechargeamount,remindamount,placeddate,placedtime,status,panterid,cardid,coinid,sourceorder,cardpurchaseid,enddate,pantercheck,checkid,checkdate) values('{$accountid}','{$coinAmount}','{$coinAmount}','{$nowdate}','{$nowtime}','01','{$panterid}','{$cardid}','{$coinid}','{$orderid}','{$cardpurchaseid}','{$enddate}',0,'','')";
        //echo $coinSql;exit;
        $this->recordError($coinSql,'rechargeCoin',$customid);
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

    //获取账户下的至尊卡号
    protected function getOwnCards($customid){
        $where['cu.customid']=$customid;
		$where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        $list=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($where)->field('c.cardno')->order('c.customid asc')->select();
        $list=$this->serializeArr($list,'cardno');
        return $list;
    }

    //获取账户下余额账户不为0的至尊卡号
    protected function getConsumeCards($customid,$type){
        $where=array('cu.customid'=>$customid,'a.type'=>$type,'a.amount'=>array('gt',0));
		$where['_string']=" c.cardno not in (select cardno from cards  where (cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) or cardkind='6688') and c.status='Y'";
        $list=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->join('account a on a.customid=c.customid')
            ->where($where)->field('c.cardno,a.amount,c.customid cardid')->select();
        return $list;
    }

    //获取账户下建业币账户不为0的至尊卡号
    protected function getCoinCards($customid){
        $subQuery="(SELECT sum(remindamount) amount,cardid from coin_account where status in('01','03')  group by cardid)";
        $where=array('cu.customid'=>$customid,'a.amount'=>array('gt',0));
		$where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
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
    protected function getCard($num,$panterid,$cardkind=null,$isLock=null){
        $map=array('panterid'=>$panterid,'status'=>'N');
        if(!empty($cardkind)) $map['cardkind']=$cardkind;
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

        return $list;
    }

    //获取建业币账户信息
    protected function getCoinAccount($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'01');
        $account=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('c.customid cardid,a.accountid')->find();
        return $account;
    }

    //测试手机是否被注册
    protected function checkMobile($linktel){
        $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $customs=$this->customs->where($map)->find();
        //echo $this->customs->getLastSql();
        //print_r($customs);exit;
        if($customs!=false){
            return $customs['customid'];
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
            return true;
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
        $currentDate=date('Ymd');
        $map=array('customid'=>$customid,'placeddate'=>$currentDate);
        $list=M('paypwd_login_logs')->where($map)->find();
        $customMap=array('customid'=>$customid);
        $customInfo=$this->customs->where($customMap)->find();
        if($list==false){
            if($customInfo['paypwd']!=$paypwd){
                $sql="INSERT INTO PAYPWD_LOGIN_LOGS VALUES('{$customid}',$currentDate,1)";
                $this->model->execute($sql);
                return '01';
            }else{
                return 1;
            }
        }else{
            if($list['degree'>=3]){
                return '02';
            }else{
                if($customInfo['paypwd']!=$paypwd){
                    $sql="UPDATE PAYPWD_LOGIN_LOGS SET degree=degree+1 WHERE customid='{$list['customid']}' and placeddate='{$list['placeddate']}'";
                    $this->model->execute($sql);
                    return '01';
                }else{
                    $sql="UPDATE PAYPWD_LOGIN_LOGS SET degree=0 WHERE customid='{$list['customid']}' and placeddate='{$list['placeddate']}'";
                    $this->model->execute($sql);
                    return 1;
                }
            }
        }
    }

    //查询会员账户余额
    protected function accountQuery($customid,$type){
        $where=array('cc.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y');
		$where['_string']=" c.cardno not in (select cardno from cards  where (cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) or cardkind='6688') and c.status='Y'";
        $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')->join('cards c on c.customid=cc.cid')
            ->where($where)->sum('a.amount');
        return $account;
    }

        #start
        protected function zzkaccount($customid)
        {
            return $money = M('zzk_account')->where(array('customid'=>$customid,'type'=>'05'))->field('balance+cash_balance as money,balance,cash_balance,accountid')->find();
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
            $this->recordError(date("H:i:s") . '  自有资金要扣的金额：' . $zzkamount . '   数据:  ' . serialize($zmoney) . "\n\t", "YjTbpost", "info");
            try {
                $cash_balance = 0;
                if ($zzkamount > $zmoney['balance']) {
                    $amount = $zzkamount - $zmoney['balance'];
                    $up = 'balance=0,cash_balance=cash_balance-' . $amount;
                    $consume_balance = $zmoney['balance'];
                    $cash_balance = $amount;
                } else {
                    $up = 'balance=balance-' . $zzkamount;
                    $consume_balance = $zzkamount;
                }

                $sql = "update zzk_account set $up where accountid='$zmoney[accountid]' and type='05'";
                $this->model->execute($sql);

                //zzk_account_detail 金额变动记录 主键id accountid before_amount变动前金额 charge_amount要变动的金额 balance变动后的金额 tradetype变动类型 order_sn订单号 source来源
                $before_amount = $zmoney['money'] + $zmoney['freeze_balance'];
                $balance = $before_amount - $zzkamount;
                $zzk_detailid = $this->zzkgetnumstr('zzk_detailid', 16);
                $zzk_ordersql = "INSERT INTO zzk_account_detail(detailid,accountid,before_amount,charge_amount,balance,tradetype,source,placeddate,placedtime,order_sn,cash_balance,consume_balance) values ";
                $zzk_ordersql .= "('{$zzk_detailid}','{$zmoney[accountid]}','{$before_amount}','{$zzkamount}','{$balance}','50','06','{$zmoney[placeddate]}','{$zmoney[placedtime]}','{$zmoney[inner_order]}','{$cash_balance}','{$consume_balance}')";
                $zzk_account_detail = $this->model->execute($zzk_ordersql);
                $this->recordError($zzk_ordersql . "\n\t", "YjTbpost", "info");
                $this->recordError($zzk_account_detail . "\n\t", "YjTbpost", "info");
                //zzk_pay_detail 订单支付信息 主键id order_sn订单号 paytype支付类型 amount金额 descrption支付说明 panterid商户id storeid门店id
                $zzk_payid = $this->zzkgetnumstr('zzk_payid', 16);
                $zzk_paysql = "INSERT INTO zzk_pay_detail(payid,order_sn,paytype,amount,placeddate,placedtime,description,panterid,storeid) values ";
                $zzk_paysql .= "('{$zzk_payid}','{$zmoney[inner_order]}','05','{$zzkamount}','{$zmoney[placeddate]}','{$zmoney[placedtime]}','{$zmoney[desc]}','{$zmoney[panterid]}','{$zmoney[panterid]}')";
                $zzk_pay_detail = $this->model->execute($zzk_paysql);
                $this->recordError($zzk_paysql . "\n\t", "YjTbpost", "info");
                $this->recordError($zzk_pay_detail . "\n\t", "YjTbpost", "info");

                return array('status' => 1, 'message' => '成功');
            } catch (\Exception $e) {
                $this->recordError(date("H:i:s") . '-' . $e . "\n\t", "YjTbpost", "zyzjyichang");
                return array('status' => 0, 'message' => '失败');
            }
        }
        #end

    //查询会员建业币余额
    protected function coinQuery($customid){
        $where=array('cc.customid'=>$customid,'a.type'=>'01','a.amount'=>array('gt',0),'c.status'=>'Y');
        $where['_string']=" c.cardno not in (select cardno from cards  where cardkind='6889' and (cardfee=0 or cardfee is null)) and c.status='Y'";
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
        $where=array('cc.customid'=>$customid,'a1.type'=>'00','a2.type'=>'01','c.status'=>'Y');
		$where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6882','6889','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        $accountInfo=$this->customs_c->alias('cc')->join('account a1 on cc.cid=a1.customid')->join('cards c on c.customid=cc.cid')
            ->join('account a2 on cc.cid=a2.customid')
            ->where($where)->field("sum(a1.amount) balance,sum(a2.amount) jycoin")->find();
        //echo $this->customs_c->getLastSql();exit;
        return $accountInfo;
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
        return $cardAccount['amount'];
    }
    //查询卡片的账户积分
    protected function cardPointQuery($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'04');
        $cardAccount=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('a.amount')->find();
        return $cardAccount['amount'];
    }
    //查询卡片的各类券总数
    protected function getAllQuans($cardno){
        $quancate=C('QuanCate');
        $where=array('c.cardno'=>$cardno);
        $cardAccount=$this->cards->alias('c')->join('quan_account qa on qa.customid=c.customid')->join('quankind q on q.quanid=qa.quanid')
            ->where($where)->field('sum(qa.amount) amounts,q.cate,qa.enddate')->order('purchaseid asc')->group('q.cate,qa.enddate,qa.purchaseid')->select();
        $result=$data=array();
        if($cardAccount){
            foreach($cardAccount as $k=>$v){
                if(time()<strtotime($v['enddate'])){
                    $v['catename']=$quancate[$v['cate']];
                    if(!isset($result[$v['cate']])){
                        $result[$v['cate']]=$v;
                    }else{
                        $result[$v['cate']]['amounts']+=$v['amounts'];
                    }
                }
            }
            return $result;
        }
    }
    //已分享券总数及列表接口
    public function alreadyShare($cardno){
        $arr=array();
        $total=$this->cards->alias('c')->join('quanshare qs on qs.cardno=c.cardno')->where(array('c.cardno'=>$cardno))->count();
        if($total>0){
            $result=$this->cards->alias('c')->join('quanshare qs on qs.cardno=c.cardno')->where(array('c.cardno'=>$cardno))->field('qs.quanname,qs.sharedate,qs.sharetime')->order('sharedate desc,sharetime desc')->select();
            $arr=array('total'=>$total,'list'=>$result);
        }
        return $arr;
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
        $path=PUBLIC_PATH.'/logs/interface/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recordData($data,$flag=null){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if($flag==1){
            $path=PUBLIC_PATH.'logs/interface/eCardIssue/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'logs/interface/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }

    /**
     * curl请求数据
     *
     * @param string $url 请求地址
     * @param array $param 请求参数
     * @param string $contentType 请求参数格式(json)
     * @return boolean|mixed
     */
    function https_request($url = '', $param = [], $contentType = '')
    {
        $ch = curl_init();

        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $url);

        // 请求参数类型
        $param = $contentType == 'json' ? json_encode($param) : $param;

        // 关闭https验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // post提交
        if ($param) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }

        // 返回的数据是否自动显示
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行并接收响应结果
        $output = curl_exec($ch);
        // echo 4321;
        // var_dump($param);exit;
        // var_dump($output);exit;
        // 关闭curl
        curl_close($ch);

        return $output !== false ? $output : false;
    }

	protected function recordError($data,$childPath,$indentifyName){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $day=date('d');
        $filename=iconv("utf-8","gb2312",$indentifyName).'.log';
        $time=date('Y-m-d H:i:s');
        $string=$data;
        $path=PUBLIC_PATH.'logs/interface/'.$childPath.'/'.$month.'/'.$day.'/';
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

    protected function getTicketByCustomid($customid){
        $where['cc.customid']=$customid;
        $where['a.type']='02';
        $where['q.enddate']=array('gt',date('Ymd',time()));
        $list=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,a.quanid')
            ->where($where)->select();
        if($list==false){
            return false;
        }else{
            $ticketList=array();
            foreach($list as $key=>$val){
                if(isset($ticketList[$key]['quanid'])){
                    $ticketList[$key]['quanid']+=$val['amount'];
                }else{
                    $ticketList[$key]['quanid']=$val['quanid'];
                    $ticketList[$key]['quanname']=$val['quanname'];
                    $ticketList[$key]['amount']=$val['amount'];
                }
            }
            return $ticketList;
        }
    }

    protected function getTicketCountByCustomid($customid){
        $where['cc.customid']=$customid;
        $where['a.type']='02';
        $where['q.enddate']=array('gt',date('Ymd',time()));
        $amount=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,a.quanid')
            ->where($where)->sum('a.amount');
        $amount=$amount==false?0:$amount;
        return $amount;
    }

    protected function getTicketByCardno($cardno,$panterid=null,$cate=null){
        $where1=array(
            'c.cardno'=>$cardno,
            'a.type'=>'02',
            'a.amount'=>array('gt',0),
            'q.enddate'=>array('egt',date('Ymd')),
            'q.atype'=>1,
        );
        $where2=array(
            'c.cardno'=>$cardno,
            'qa.amount'=>array('gt',0),
            'qa.enddate'=>array('egt',date('Ymd')),
            'q.atype'=>2,
        );
        if($cate!='null'&&!empty($cate)){
            $where1['q.cate']=$cate;
            $where2['q.cate']=$cate;
        }
        if($panterid!=null){
            $panter=M('panters')->where(array('panterid'=>$panterid))->find();
            $where1['_string']=$where2['_string']="(q.panterid='{$panterid}' and q.utype=2) or (q.utype=1 and q.panterid='{$panter['parent']}')";
        }
        $list1=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->field('q.quanname,a.amount,q.quanid,q.transfer,q.memo,q.enddate,c.cardno')
            ->where($where1)->order('q.enddate asc')->select();

        $list2=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->field('q.quanname,qa.amount,q.quanid,q.transfer,q.memo,qa.enddate,qa.accountid,c.cardno')
            ->where($where2)->order('qa.enddate asc')->select();
        $list=array_merge($list1,$list2);
        //print_r($list);exit;
        if($list==false){
            return false;
        }else{
            $ticketList=array();
            foreach($list as $key=>$val){
                $ticketList[$key]['quanname']=$val['quanname'];
                $ticketList[$key]['amount']=$val['amount'];
                $ticketList[$key]['quanid']=$val['quanid'];
                $ticketList[$key]['accountid']=!empty($val['accountid'])?$val['accountid']:'';
                $ticketList[$key]['enddate']=date('Y-m-d',strtotime($val['enddate']));
                $ticketList[$key]['cardno']=$val['cardno'];
                $ticketList[$key]['transfer']=$val['transfer'];
                $ticketList[$key]['memo']=$val['memo'];
            }
            return $ticketList;
        }
    }

    protected function getOverDueTicketByCardno($cardno,$panterid=null,$cate=null){
        $where1=array(
            'c.cardno'=>$cardno,
            'a.type'=>'02',
            'a.amount'=>array('gt',0),
            'q.enddate'=>array('lt',date('Ymd')),
            'q.atype'=>1
        );
        $where2=array(
            'c.cardno'=>$cardno,
            'qa.amount'=>array('gt',0),
            'qa.enddate'=>array('lt',date('Ymd')),
            'q.atype'=>2
        );
        if($cate!='null'&&!empty($cate)){
            $where1['q.cate']=$cate;
            $where2['q.cate']=$cate;
        }
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
                $ticketList[$key]['enddate']=$val['enddate'];
            }
            return $ticketList;
        }
    }
    //分享券
    protected function shareQuanByCard($cardno,$quanid,$quanname,$sharedate,$sharetime,$shareid){
        $result=M('cards')->alias('c')->join('quan_account qa on qa.customid=c.customid')->where(array('cardno'=>$cardno,'quanid'=>$quanid))->field('c.customid,qa.accountid')->find();
        $tradeSql="insert into quanshare(cardno,quanid,quanname,amount,sharedate,sharetime,shareid)";
        $tradeSql.="values('{$cardno}','{$quanid}','{$quanname}','1','{$sharedate}','{$sharetime}','{$shareid}')";
        $tradeIf=$this->model->execute($tradeSql);
        $accountSql="UPDATE QUAN_ACCOUNT set amount=amount-1 where customid='{$result["customid"]}'  and quanid='{$quanid}' and accountid='{$result["accountid"]}'";
        $accountIf=$this->model->execute($accountSql);
        if($accountIf==true&&$tradeIf==true){
            return true;
        }else{
            return false;
        }
    }
    //执行至尊卡扣款记录，
    protected function tradeExecute($consumeInfo){
        $panterid=$consumeInfo['panterid'];
        $cardno=$consumeInfo['cardno'];
        $customid=$consumeInfo['customid'];
        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $termno=!empty($consumeInfo['termno'])?$consumeInfo['termno']:'00000000';
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
        $type=$consumeInfo['type'];
        $is_tradeid=$consumeInfo['is_tradeid'];
        if($type=='00'){
            $balanceAmount=$amount;
            $coinAmount=0;
        }elseif($type='01'){
            $balanceAmount=0;
            $coinAmount=$amount;
        }
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'{$balanceAmount}','{$coinAmount}','{$customid}','{$cardno}','{$placedtime}','00','abcdefgh','0','{$orderid}')";

        $tradeIf=$this->model->execute($tradeSql);
        $errString="时间：".date('Y-m-d H:i:s').";订单写入记录：{$tradeSql};执行结果：{$tradeIf}\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        if($type=='01'){
            if($tradeIf==true){
                return $tradeid;
            }else{
                return false;
            }
        }else{
            return $tradeIf;
        }
    }

    //更新账户余额,0:余额；1：建业币
    protected function cutAccount($cutInfo){
        $amount=$cutInfo['amount'];
        $cardid=$cutInfo['cardid'];
        $customid=$cutInfo['customid'];
        $accountAmount=$cutInfo['accountAmount'];
        $type=$cutInfo['type'];
        $accountSql="UPDATE ACCOUNT set amount={$cutInfo['amount']} where customid='{$cardid}' and type='{$type}'";
        $accountIf=$this->model->execute($accountSql);
        $errString="时间：".date('Y-m-d H:i:s').";更新账户记录：{$accountSql};账户余额：{$accountAmount};执行结果：{$accountIf}\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        return $accountIf;
    }

    //劵消费执行
    protected function ticketExe($customid,$quanid,$amount,$panterid){
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        $quanAccount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field('a.accountid,a.amount,cc.cid,c.cardno')->select();
        $consumedAmount=0;
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
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
            $tradeSql.="values('00000000','00000000','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$consumeAmount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
            $tradeIf=$this->model->execute($tradeSql);

            $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$val['cid']}' and type='02' and quanid='{$quanid}'";
            $accountIf=$this->model->execute($accountSql);
            if($accountIf==true&&$tradeIf==true){
                $consumedAmount+=$consumeAmount;
            }
        }
        //echo $consumedAmount;exit;
        if($consumedAmount==$amount){
            return true;
        }else{
            return false;
        }
    }

    /*获取可用建业币数量
    public function getCoinDetail($cardno,$consumePanterid=null){
        if(!empty($consumePanterid)){
            $panter=$this->checkPanter($consumePanterid);
            if($panter==false) return false;
        }

        if($panter['hysx']=='房产'){
            $where=array('ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno);
            $where1['ca.status']='03';
            $where1['ca.panterid']=$consumePanterid;
            $where1['_login']='OR';
            $where['_complex']=$where1;
        }else{
            $where=array('ca.status'=>array('in','01,03'),'ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno);
        }
        $account=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->sum('remindamount');
        return $account;
    }*/

    //获取可用建业币数量
    public function getCoinDetail($cardno){
        $where=array('ca.status'=>'01','ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'ca.enddate'=>array('egt',date('Ymd')));
        $account=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->sum('remindamount');
        return $account;
    }

    /*获取建业通宝账户列表
    public function getCoinAccountList($cardno,$consumePanterid=null){
        if(!empty($consumePanterid)){
            $panter=$this->checkPanter($consumePanterid);
            if($panter==false) return false;
        }
        if($panter['hysx']=='房产'){
            $where=array('ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno);
            $where1['ca.status']='03';
            $where1['ca.panterid']=$consumePanterid;
            $where1['_login']='OR';
            $where['_complex']=$where1;
        }else{
            $where=array('ca.status'=>array('in','01,03'),'ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno);
        }
        $coinList=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->field('ca.*,c.cardno')->order('ca.placeddate asc')->select();
        return $coinList;
    }*/

    //获取建业通宝账户列表
    public function getCoinAccountList($cardno){
        $where=array('ca.status'=>'01','ca.remindamount'=>array('gt',0),'c.cardno'=>$cardno,'ca.enddate'=>array('egt',date('Ymd')));
        $coinList=$this->cards->alias('c')->join('coin_account ca on ca.cardid=c.customid')
            ->where($where)->field('ca.*,c.cardno')->order('ca.placeddate asc')->select();
        return $coinList;
    }

    //卡号建业币消费执行
    public function coinConsumeExe($coinList,$coinAmount,$panterid,$orderid,$signCode,$termno){
        if($coinList==false) return false;
        $consumedAmount=0;
        foreach($coinList as $key=>$val){
            $waitAmount=$coinAmount-$consumedAmount;
            if($waitAmount<=0) break;
            if($waitAmount>=$val['remindamount']){
                $consumeAmount=$val['remindamount'];
            }else{
                $consumeAmount=$waitAmount;
            }
            $consumeAmount = round($consumeAmount,2);
            $errString1="至尊卡号：{$val['cardno']},通宝明细账户：{{$val['coinid']}},消费通宝金额：{$consumeAmount}\r\n";
            $this->recordError($errString1,'consume','会员_'.$signCode);
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,'type'=>2,'termno'=>$termno,
                'customid'=>$val['cardid'],'orderid'=>$orderid,'type'=>'01','cardno'=>$val['cardno']);
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
                $coinConsumeSql="INSERT INTO coin_consume values('{$coinconsumeid}','{$tradeIf}','{$val['cardid']}',";
                //$coinConsumeSql.="'{$val['coinid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1,0,'','')";
                $coinConsumeSql.="'{$val['coinid']}','{$consumeAmount}','{$currentDate}','{$currentTime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";
                $coinConsemeIf=$this->model->execute($coinConsumeSql);
                $errString2.="时间：".date('Y-m-d H:i:s').";通宝消费写入记录：{$coinSql};执行结果：{$coinIf}\r\n";
            }else{
                $coinConsemeIf=false;
            }
            $errString2.="**********************************************************************\r\n";
            $this->recordError($errString2,'consume','会员_'.$signCode);
            if($tradeIf==true&&$coinIf==true&&$accountIf==true&&$coinConsemeIf==true){
                //$consumedAmount+=$consumeAmount;
                $consumedAmount=bcadd($consumedAmount,$consumeAmount,2);
            }
        }
        if($consumedAmount==$coinAmount){
            return true;
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

            $coinList=$this->getCoinAccountList($cardno);
            $consumeIf=$this->coinConsumeExe($coinList,$consumeAmount,$panterid,$orderid,$customid,$termno);
            if($consumeIf==true){
                $consumedAmount=bcadd($consumedAmount,$consumeAmount,2);
                $waitAmount=bcsub($amount,$consumedAmount,2);
            }
            $errString="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
            $this->recordError($errString,'consume','会员_'.$customid);
        }
        $errString="\r\n";
        $this->recordError($errString,'consume','会员_'.$customid);
        if($consumedAmount==$amount){
            return true;
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
        $walletConsumeAmount=$consumeArr['walletConsumeAmount'];
        $cardsList=$this->getConsumeCards($customid,$type);
        $waitAmount=$amount;
        $consumedAmount=0;
        $errString1=count($cardsList)."{$customid}余额消费执行记录,时间：".date('Y-m-d H:i:s')."\r\n";
        $this->recordError($errString1,'consume','custom_'.$customid);
        $errString5='';
        $i = 0;
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
            $this->recordError($errString1,'consume','custom_'.$customid);
            //写入扣款记录
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,'type'=>1,
                'customid'=>$customid,'orderid'=>$orderid,'type'=>$type,'cardno'=>$cardno,'termno'=>$termno);
            $tradeIf=$this->tradeExecute($consumeArr);
            if($tradeIf==false) continue;

            //扣除账户余额
            $cutArr=array('amount'=>$cardbalance-$consumeAmount,'cardid'=>$val['cardid'],'type'=>$type,'customid'=>$customid,'accountAmount'=>$cardbalance);
            $cutInfo=$this->cutAccount($cutArr);
            if($cutInfo==true&&$tradeIf==true){
            	$errString5.='ying fu1:'.$consumedAmount."\r\n";
                $consumedAmount=bcadd($consumedAmount,$consumeAmount,2);
                $errString5.='ying fu2:'.$consumedAmount."\r\n";
                $waitAmount=bcsub($amount,$consumedAmount,2);
            }else{
                continue;
            }
            $i++;
            $errString5.='ying fu3:'.$consumedAmount.'--'.$i."\r\n";
           
            $this->recordError($errString5,'consume','custom__'.$customid);
            $errString1="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
            $this->recordError($errString1,'consume','custom_'.$customid);
            
        }
        if($walletConsumeAmount>0){
            $consumeArr=array('panterid'=>$panterid,'amount'=>$consumeAmount,'type'=>1,
                'customid'=>$customid,'orderid'=>$orderid,'type'=>$type,'cardno'=>$cardno);

            $walletArr=array('customid'=>$customid,'panterid'=>$panterid,'orderid'=>$orderid,'amount'=>$walletConsumeAmount);
            $walletIf=$this->cutWallet($walletArr);
        }else{
            $walletIf=true;
        }
        $errString1='扣除金额：'.$consumedAmount.',应付金额:'.$amount."\r\n";
        $this->recordError($errString1,'consume','custom__'.$customid);
        if($consumedAmount==$amount&&$walletIf==true){
            return true;
        }else{
            return false;
        }
    }

    protected function cutWallet($walletArr){
        $customid=$walletArr['customid'];
        $panterid=$walletArr['panterid'];
        $orderid=$walletArr['orderid'];
        $amount=$walletArr['amount'];
        $wallet=$this->model->table('wallet')->where(array('customid'=>$customid))->find();
        if($wallet==false||$wallet['amount']<$amount) return false;
        $walletSql="UPDATE WALLET SET AMOUNT=AMOUNT-{$amount} WHERE CUSTOMID='{$customid}'";
        $walletIf=$this->model->execute($walletSql);

        $incomeBooksSql="INSERT INTO INCOME_BOOKS VALUES('{$customid}','{$wallet['storeid']}','{$orderid}','{$amount}','3','".date('Ymd')."','".date('H:i:s')."','线上接口消费','','0','{$panterid}')";
        $incomeBooksIf=$this->model->execute($incomeBooksSql);

        if($walletIf==true&&$incomeBooksIf==true){
            return true;
        }else{
            return false;
        }
    }

    public function getCustomid($cardno){
        $field='cc.customid';
        $map=array('c.cardno'=>$cardno);
        $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->where($map)->field($field)->find();
        return $custom['customid'];
    }

    protected function zrefund($orderid, $zzkamount, $zzk_order)
    {
        try {
            $desc = '一家退款';
            $placeddate = date('Ymd', time());
            $placedtime = date('H:i:s', time());
            $inner_order = $this->zzktradeid('05', '05', $zzk_account['accountid']);
            $cash_balance = 0;
            $consume_balance = 0;

            if ($zzkamount > $zzk_order['zdconsume']) {
                $amount = $zzkamount - $zzk_order['zdconsume'];
                $cash_balance = $amount;
                $consume_balance = $zzk_order['zdconsume'];
                $up = "balance=balance+$zzk_order[zdconsume],cash_balance=cash_balance+" . $amount;
            } else {
                $up = 'balance=balance+' . $zzk_order['zdconsume'];
                $consume_balance = $zzk_order['zdconsume'];
            }
            $sql = "update zzk_account set $up where accountid='$zzk_order[accountid]' and type='05'";
            $this->recordError($sql . "\n\t", "YjTbpost", "returnGoods");
            $this->model->execute($sql);

            //zzk_account_detail 金额变动记录 主键id accountid before_amount变动前金额 charge_amount要变动的金额 balance变动后的金额 tradetype变动类型 order_sn订单号 source来源
            $before_amount = $zzk_order['cash_balance'] + $zzk_order['balance'] + $zzk_order['freeze_balance'];
            $balance = $before_amount + $zzkamount;
            $zzk_detailid = $this->zzkgetnumstr('zzk_detailid', 16);
            $zzk_ordersql = "INSERT INTO zzk_account_detail(detailid,accountid,before_amount,charge_amount,balance,tradetype,source,placeddate,placedtime,order_sn,cash_balance,consume_balance) values ";
            $zzk_ordersql .= "('{$zzk_detailid}','{$zzk_order[accountid]}','{$before_amount}','{$zzkamount}','{$balance}','57','06','{$placeddate}','{$placedtime}','{$inner_order}','{$cash_balance}','{$consume_balance}')";
            $this->recordError($zzk_ordersql . "\n\t", "YjTbpost", "returnGoods");
            $zzk_account_detail = $this->model->execute($zzk_ordersql);

            $zzk_orderid = $this->zzkgetnumstr('zzk_orderid', 16);
            $ordersql = "INSERT INTO zzk_order(phone,orderid,tradetype,order_sn,placeddate,placedtime,panterid,storeid,accountid,inner_order,source,amount,paytype,description) values ";
            $ordersql .= "('{$zzk_order[phone]}','{$zzk_orderid}','57','{$orderid}','{$placeddate}','{$placedtime}','{$zzk_order[panterid]}','{$zzk_order[panterid]}','{$zzk_order[accountid]}','{$inner_order}','06','{$zzkamount}','05','{$desc}')";
            $this->recordError($ordersql . "\n\t", "YjTbpost", "returnGoods");
            $orderInfo = $this->model->execute($ordersql);

            $rechargeid = $this->zzkgetnumstr('rechargeid', 16);
            $rechargesql = "INSERT INTO zzk_recharge(rechargeid,inner_order,paytype,amount,placeddate,placedtime,description,panterid,storeid) values ";
            $rechargesql .= "('{$rechargeid}','{$inner_order}','06','{$zzkamount}','{$placeddate}','{$placedtime}','{$desc}','{$zzk_order[panterid]}','{$zzk_order[panterid]}')";
            $this->recordError($rechargesql . "\n\t", "YjTbpost", "returnGoods");
            $rechargeInfo = $this->model->execute($rechargesql);

            return array('status' => 1, 'message' => '成功');
        } catch (\Exception $e) {
            $this->recordError(date("H:i:s") . '-' . $e . "\n\t", "YjTbpost", "returnGoods");
            return array('status' => 0, 'message' => '失败');
        }

    }

    //退款执行
    protected function refund($orderid, $amount, $type)
    {
        if ($type == 1) {
            $map = array('tw.tradetype' => '00', 'tw.flag' => 0, 'tw.eorderid' => $orderid, 'tw.tradeamount' => array('gt', 0));
            $searString = 'tradeamount';
            $accountType = '00';
        } elseif ($type == 2) {
            $map = array('tw.tradetype' => '00', 'tw.flag' => 0, 'tw.eorderid' => $orderid, 'tw.tradepoint' => array('gt', 0));
            $searString = 'tradepoint';
            $accountType = '01';
        }
        $orderList = M('trade_wastebooks')->alias('tw')->join('cards c on tw.cardno=c.cardno')
            ->field('tw.*,c.customid cardid')->where($map)->select();
        //echo M('trade_wastebooks')->getLastSql().'<br/>';
        $returnedAmount = 0;
        foreach ($orderList as $key => $val) {
            $waitAmount = $amount - $returnedAmount;
            if ($waitAmount <= 0) {
                break;
            }
            $cancelMap = array('preorderid' => $val['orderid'], 'eorder' => $val['eorderid'], 'tradetype' => '00', 'flag' => '02');
            $cancelAmount = M('trade_wastebooks')->where($cancelMap)->sum($searString);
            if ($val[$searString] - $cancelAmount <= 0) continue;

            if ($val[$searString] - $cancelAmount <= $waitAmount) {
                if ($type == 1) {
                    $balanceAmount = $val[$searString] - $cancelAmount;
                    $coinAmount = 0;
                } elseif ($type == 2) {
                    $balanceAmount = 0;
                    $coinAmount = $val[$searString] - $cancelAmount;
                }
                $backAmount = $val[$searString] - $cancelAmount;
            } else {
                if ($type == 1) {
                    $balanceAmount = $waitAmount;
                    $coinAmount = 0;
                } elseif ($type == 2) {
                    $balanceAmount = 0;
                    $coinAmount = $waitAmount;
                }
                $backAmount = $waitAmount;
            }
            $tradeAmount = $balanceAmount > 0 ? -$balanceAmount : 0;
            $tradeCoin = $coinAmount > 0 ? -$coinAmount : 0;
            $tradeid = substr($val['cardno'], 15, 4) . date('YmdHis', time());
            $map = array('tradeid' => $tradeid);
            $c = M('trade_wastebooks')->where($map)->count();
            if ($c > 0) {
                sleep(1);
                $tradeid = substr($val['cardno'], 15, 4) . date('YmdHis', time());
            }

            $placeddate = date('Ymd', time());
            $placedtime = date('H:i:s', time());
            $pretradeid = trim($val['tradeid']);
            $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,pretradeid,";
            $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
            $tradeSql .= "values('00000000','00000000','{$val['panterid']}','{$tradeid}','{$placeddate}','{$pretradeid}',";
            $tradeSql .= "'{$tradeAmount}','{$tradeCoin}','{$val['cardid']}','{$val['cardno']}','{$placedtime}',";
            $tradeSql .= "'07','abcdefgh','0','{$orderid}')";

            //echo $tradeSql.'<br/>';
            $tradeIf = $this->model->execute($tradeSql);

            $balanceSql = "UPDATE account SET amount=nvl(amount,0)+" . $backAmount . " WHERE customid='{$val['cardid']}' and type='{$accountType}'";
            $balanceIf = $this->account->execute($balanceSql);

            if ($type == 2) {
                $coinReturnIf = $this->returnCoin($pretradeid);
            } else {
                $coinReturnIf = true;
            }
            if ($balanceIf == true && $tradeIf == true && $coinReturnIf == true) {
                $returnedAmount += $backAmount;
            }
        }
        if ($returnedAmount == $amount) {
            return true;
        } else {
            return false;
        }
    }

    //通宝退款执行
    public function returnCoin($tradeid){
        if(empty($tradeid)) return false;
        $map=array('tradeid'=>trim($tradeid));
        $coinConsumeList=M('coin_consume')->where($map)->select();
        $c=0;
        if(empty($coinConsumeList)) return false;
        foreach($coinConsumeList as $key=>$val){
            //$coinconsumeid=$this->getnextcode('coinconsumeid',10);
            $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $coinConsumeSql="INSERT INTO coin_consume values('{$coinconsumeid}','{$tradeid}','{$val['cardid']}',";
            $coinConsumeSql.="'{$val['coinid']}','-{$val['amount']}','{$currentDate}','{$currentTime}','{$val['panterid']}',0,2,0,'','')";
            $coinAccountSql="UPDATE COIN_ACCOUNT SET remindamount=remindamount+{$val['amount']} WHERE coinid='{$val['coinid']}'";
            $coinAccountIf=$this->model->execute($coinAccountSql);
            $coinConsumeIf=$this->model->execute($coinConsumeSql);
            if($coinAccountIf==true&&$coinConsumeIf==true){
                $c++;
            }
        }
        if($c==count($coinConsumeList)){
            return true;
        }else{
            return false;
        }
    }

    //存储推送消息
    public function recordMsg($orderid,$consumeAmount,$coinAmount,$backUrl){
        $nowTime=time();
        $string=$orderid."|".$consumeAmount."|".$coinAmount."|".$backUrl."|";
        $date1=$nowTime+60;
        $date2=$nowTime+600;
        $date3=$nowTime+7200;
        $content1=$string.$date1."|".date('Y-m-d H:i:s',$nowTime+60)."\r\n";
        $content2=$string.$date2."|".date('Y-m-d H:i:s',$nowTime+600)."\r\n";
        $content3=$string.$date3."|".date('Y-m-d H:i:s',$nowTime+7200)."\r\n";
        $path="./Public/pushMessage/";
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        for($i=1;$i<=3;$i++){
            $a='date'.$i;
            $b='content'.$i;
            $d=date('Ymd',$$a);
            file_put_contents($path."$d.txt",$$b,FILE_APPEND);
        }
    }

    //接口返回信息加密
    public function returnEncMsg($msgArr){
        $string=$this->keycode;
        foreach($msgArr as $value){
            $string.=$value;
        }
        $msgArr['key']=md5($string);
        $encString=$this->DESedeCoder->encrypt(json_encode($msgArr));
        exit(json_encode(array('datami'=>$encString)));
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

    public function checkCardPwd($cardno,$pwd){

        //$url = C('netApiUrl');
        //$url='http://122.0.82.130:8086/Web/login.ashx';//正式环境
        //$url='http://192.168.10.50/Web/login.ashx';//正式环境
        //$data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
        //$res=crul_post($url,$data);
        //$res=json_decode($res,1);
        //print_r($res);exit;
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $passWord = $des->doEncrypt($pwd);
        $cards = M('cards');
        $card_where = array('cardno'=>$cardno,'status'=>'Y');
        $cardif = $cards->where($card_where)->find();
        if(false==$cardif){
            return false;
        }
        if ($cardif['cardpassword']==$passWord) {
            return true;
        }else{
            return false;
        }
    }

    protected function getWalletAccount($customid){
        if(empty($customid)) return false;
        $map=array('customid'=>$customid);
        $wallet=$this->model->table('wallet')->where($map)->find();
        if($wallet==false){
            return false;
        }
        return $wallet['amount'];
    }

    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $list=$this->model->query($sql);
        $fieldLength=$this->fieldsLength[$field];
        $lastNumber=$list[0]['nextval'];
        return $this->getnumstr($lastNumber,$fieldLength);
    }

    protected function checkCardUsable($cardno,$panterid,$cardbrand){
        $sql="select * from cards where cardno='{$cardno}' for update";
        $a=$this->model->query($sql);
        while($a==false){
            $getCard=$this->getCard(1,$panterid,$cardbrand,1);
            $cardno=$getCard[0];
            $this->checkCardUsable($cardno,$panterid,$cardbrand);
        }
        return $cardno;
    }

    protected function getLvCards($panterid,$cardStart){
        $map=array('panterid'=>$panterid,'status'=>'N','cardno'=>array('like',$cardStart.'%'),'cardfee'=>0);
        if(!empty($cardkind)) $map['cardkind']=$cardkind;
        $card=$this->cards->where($map)->field('cardno')->find();
        $map1['cardno']=$card['cardno'];
        $data['status']='L';
        if($this->cards->where($map1)->save($data)){
            return $card['cardno'];
        }else{
            return false;
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
                returnMsg(['status'=>'035','codemsg'=>'此商户通宝受理受限,老通宝余额不足']);
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
				}
			}
			//扣款 相关发行方信息
			$cutPulish = $this->returnPublish($publish);
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
			    return $cutPulish;
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
//		$SQL  = "SELECT coin.remindamount,coin.cardid,coin.panterid,coin.coinid,p.namechinese FROM COIN_ACCOUNT coin left join panters p on coin.panterid=p.panterid WHERE coin.CARDID IN(SELECT cid from customs_c where customid='{$customid}') AND coin.ENDDATE>='{$day}' AND coin.remindamount>0 ORDER  BY  ENDDATE ASC ";
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

	protected function  returnPublish($publish){
		$keys = array_unique(array_column($publish,'name'));
		if(count($publish)==count($keys)){
			return $publish;
		}else{
			$arr = [];
			foreach ($publish as $key=>$val){
				if(isset($arr[$val['name']])){
					$arr[$val['name']]['cut'] = bcadd($arr[$val['name']]['cut'],$val['cut'],2);
				}else{
					$arr[$val['name']]  = $val;
				}
			}
			$return = [];
			foreach ($arr as $val){
				$return[] = $val;
			}
			return $return;
		}
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
        $day = date('Ymd');
        $where['cu.customid'] = $customid;
        $where['ca.cardkind'] = '6889';
        $where['ca.cardfee']  = ['in',['1','2']];
        $where['ca.status']   = 'Y';
        $where['coin.enddate']= ['egt',$day];
        $where['coin.remindamount'] = ['gt','0'];
        $where['coin.placeddate']  =  ['lt','20190101'];
        $field  = 'coin.remindamount,coin.cardid,coin.coinid,p.namechinese,coin.enddate';
        $coinList  = $this->model->table('customs')->alias('cu')
            ->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards ca on ca.customid=cc.cid')
            ->join('left join coin_account coin on coin.cardid=ca.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->order('coin.enddate asc')->select();
        return $coinList;
    }

    /**
     * 导出
     * @param $arrList
     * @param $tableName
     * @param $arrString
     * @param $arrSUM
     * @param $titleTime
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function load_excel($arrList,$tableName,$arrString,$arrSUM,$titleTime){
        header("Content-type: text/html; charset=utf-8");
        if(!is_array($arrList)){exit("<script type='text/javascript'>alert('传入参数有误');window.close();</script>");}
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        $tableName?'':$tableName=date("Y-m-d",time());
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("yyh");
        $objPHPExcel->getProperties()->setLastModifiedBy("yyh");
        $objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
        $objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
        $objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
        // Add some data
        $objPHPExcel->setActiveSheetIndex(0);
        //$arrList=array(0=>array('姓名','年龄','性别'),1=>array('name'=>'张三','age'=>'10','sex'=>'男'),2=>array('name'=>'李四','age'=>'15','sex'=>'女'));
        //设置宽度为自动适应
        $zm='A';
        for($n=0;$n<count($arrList[0]);$n++){
            $objPHPExcel->getActiveSheet()->getColumnDimension($zm)->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getStyle($zm)->getAlignment()->setHorizontal('left');
            $zm++;
        }
        $objPHPExcel->getActiveSheet()->SetCellValue('A1',$tableName);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);

        $i=2;
        foreach($arrList as $k=>$v){
            $i++;
            $z='A';
            foreach($v as $kk=>$vv){
                if(in_array($z,$arrString)){
                    $objPHPExcel->getActiveSheet()->SetCellValueExplicit($z.$i,$vv,'s');
                }
                else{
                    $objPHPExcel->getActiveSheet()->SetCellValue($z.$i,$vv);
                }
                $z++;
            }
            if($k!=0){
                if($titleTime){
                    $titleTime_arr[]=strtotime($v[$titleTime]);
                }
            }
        }
        if($titleTime){
            $titleTime_start=date("Y.m.d",min($titleTime_arr));
            $titleTime_end=date("Y.m.d",max($titleTime_arr));
            $objPHPExcel->getActiveSheet()->SetCellValue('A2',$titleTime_start.'-'.$titleTime_end);
        }
        else{
            $objPHPExcel->getActiveSheet()->SetCellValue('A2',date("Y-m-d"),time());
        }
        $x=chr(ord($z)-1);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$x.'1');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:'.$x.'2');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('center');
        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('right');
        //合计
        foreach($arrSUM as $va){
            $objPHPExcel->getActiveSheet()->SetCellValue($va.($i+1),"=SUM({$va}2:{$va}{$i})");
        }
        $objPHPExcel->getActiveSheet()->setTitle("报表");
        //$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save(str_replace('.php', '.xls', __FILE__));
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=".$tableName.".xls");
        //header("Content-Disposition:attachment;filename=".$tableName.".csv");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save("php://output");
    }

    /**
     * $filename带路径文件名   $qshnum 读取文件起始行
     * @param $filename
     * @param int $qshnum
     * @return array|void
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
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
}
