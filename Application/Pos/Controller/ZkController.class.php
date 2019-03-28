<?php
namespace Pos\Controller;

use Org\Util\DESedeCoder;
use Think\Controller;
use Think\Exception;
use Think\Model;
class ZkController extends Controller
{
    protected $frozen = 20;

    protected $key    = 'JYO2O01';

    protected $paymenttype;

    protected $panterid = '00010774';

    protected $model;

    function _initialize(){
        $this->model = new Model();
        $this->prefix = 'zk_';
        $this->paymenttype = [
            '00'=>'现金','01'=>'银行卡','09'=>'微信','10'=>'支付宝'
        ];
    }

    public function config(){
        $request = json_decode($this->getRequestData(),1);
        $imei    = $request['imei'];
        $this->checkSign($request['key'],md5($this->key.$imei)) ;
        if(empty($imei)) $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,imei为空']);

        $info = M('zzk_pos_config')->where(['imei'=>$imei])->find();
        if(!$info) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,未获取到对应配置信息']);
        $storeid = $info['outid'] ;
        $store = M('zzk_store')->where(['outid'=>$storeid])->field('name')->find();
        if(!$store) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,获取店铺信息异常']);
        $msg['imei'] = $imei;
        $msg['storeid']   = $storeid;
        $msg['panterid']  = $info['panterid'];
        $msg['storename'] = $store['name'];
        $msg['pantername']= $info['pantername'];
        $msg['termno']    = $info['termno'];
        $msg['acceptance']= $info['acceptance'];
        $this->returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$msg]);

    }
    public function balance(){
        $request = json_decode($this->getRequestData(),1);
        $cardno  = $request['cardno'];
        $this->checkSign($request['key'],md5($this->key.$cardno)) ;
        $cardInfo = $this->valiCard($cardno,$request['panterid']);
        $cardInfo['flag']=='on' || $this->returnMsg(['status'=>'04','codemsg'=>'校验信息错误,此卡未充值']);
        $customid = $cardInfo['customid'];
        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);
        $balance = $account['amount'];
        $cardnum = $cardInfo['cardnum'];
        $msg['cardno']   = $cardno;
        $msg['customid'] = $customid;
        $msg['balance']  = $balance;
        $msg['avaliable']= $this->avaliableBalance($balance,$cardnum);
        $msg['team']     = $cardInfo['team'];
        $msg['cardnum']  = $cardnum;
        $this->returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$msg]);
    }

    public function cardRecharge(){
        $request     = json_decode($this->getRequestData(),1);
        $cardno      = $request['cardno'];
        $paymenttype = $request['paymenttype'];
        $totalmoney  = $request['totalmoney'];
        $panterid    = $request['panterid'];
        $storeid     = $request['storeid'];
        $order_sn    = $request['order_sn'];
        $key         = $request['key'];
        $this->checkSign($key,md5($this->key.$cardno.$paymenttype.$totalmoney.$panterid.$storeid.$order_sn));

        $this->valiOrder($order_sn,'01');
        //$this->valiStore($panterid,$storeid);
        $cardInfo = $this->valiCard($cardno,$panterid);
        $customid = $cardInfo['customid'];
        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!empty($cardInfo['team'])) $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,团卡不能参与单卡充值']);
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);


        $paymenttype = $this->paymenttype[$paymenttype];
        //开启事务操作

        //新增 对不退卡的充值管理
        $flag=$cardInfo['flag'];
        if($flag==='on'){
            $num = $cardInfo['cardnum'];
        }else{
            $num = $this->customsCardnum($cardno,$cardInfo['cardnum'],$flag);
        }
        $map = ['cardno'=>$cardno,'cutsomid'=>$customid,'amount'=>$totalmoney,
            'userid'=>$this->userid,'paymenttype'=>$paymenttype,'panterid'=>$panterid,
            'num' =>$num,'purchaseid'=>$this->getFieldNextNumber('purchaseid'),
            'auditid'=>$this->getFieldNextNumber('auditid'),'cardpurchaseid'=>$this->getFieldNextNumber('cardpurchaseid'),
            'order_sn'=>$order_sn
        ];
         $this->model->startTrans();
        $bool=$this->exChong($map);
        if($bool['msg']===true){
            $this->model->commit();
            $balanceInfo = M('account')->where(['type'=>'00','customid'=>$customid])
                                       ->field('amount')->find();
            returnMsg(['status'=>'1','codemsg'=>'充值成功','purchaseId'=>$bool['purchaseid'],'balance'=>floatval($balanceInfo['amount']),'org'=>floatval($account['amount'])]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'05','codemsg'=>'修改数据库失败,充值失败']);
        }
    }

    protected function exChong( $map){
        $cardno       = $map['cardno'];
        $customid     = $map['cutsomid'];
        $chargeAmount = $map['amount'];
        $userid       = $map['userid'];
        $paymenttype  = $map['paymenttype'];
        $panterid     = $map['panterid'];
        $currentDate  = date('Ymd');
        $checkDate    = date('Ymd');
        $num          = $map['num'];
        //审核单号
        $auditid      = $map['auditid'];
        $order_sn     = $map['order_sn'];
        //充值单号
        $purchaseid   = $map['purchaseid'];
        $cardpurchaseid=$map['cardpurchaseid'];

        $customplSql="insert into custom_purchase_logs values('".$customid."','{$purchaseid}','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$chargeAmount."',NULL,'".$chargeAmount."',0,'".$chargeAmount."','".$chargeAmount;
        $customplSql.="',1,'','{$order_sn}','1','".$panterid."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf = $this->model->execute($customplSql);

        //写入审核单
        $auditlogsSql="insert into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
        $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        $auditlogsIf = $this->model->execute($auditlogsSql);

        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$chargeAmount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        //更新账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$chargeAmount." where customid='".$customid."' and type='00'";
        $balanceIf = $this->model->execute($balanceSql);

        if($customplIf==true && $auditlogsIf==true && $cardplIf==true){
            return ['msg'=>true,'purchaseid'=>$purchaseid];
        }else{
            return false;
        }
    }


    //下单消费
    public function consume(){
        $request = json_decode($this->getRequestData(),1);
        $cardno  = $request['cardno'];
        $panterid= $request['panterid'];
        $storeid = $request['storeid'];
        $amount  = $request['amount'];
        $termno  = $request['termno'];
        $order_sn= $request['order_sn'];

        $key     = $request['key'];

        $this->checkSign($key,md5($this->key.$cardno.$panterid.$storeid.$amount.$termno.$order_sn));

        $this->valiOrder($order_sn,'02');
        //$this->valiStore($panterid,$storeid);
        $cardInfo = $this->valiCard($cardno,$panterid);
        $customid = $cardInfo['customid'];
        $flag = $cardInfo['flag'];
        if($flag!='on') $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,此卡已退卡']);

        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);

        $balance = $account['amount'];
        $cardnum = $cardInfo['cardnum'];
        $avaliable = $this->avaliableBalance($balance,$cardnum);
        $avaliable>=$amount||returnMsg(['status'=>'03','codemsg'=>"校验信息错误,可用卡余额不足",'amount'=>round($balance,2)]);
        $yue=bcsub($balance,$amount,2);


        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $order_sn = $this->prefix.$order_sn;
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$amount}','0','{$customid}','{$cardno}','{$placedtime}','00','{$tac}','0','{$order_sn}')";
        $this->model->startTrans();
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单

        if($tradeif==true && $accountif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'消费成功','tradeid'=>$tradeid,'balance'=>$yue]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }

    public function returnFood(){
        $request = json_decode($this->getRequestData(),1);
        $cardno  = $request['cardno'];
        $panterid= $request['panterid'];
        $storeid = $request['storeid'];
        $amount  = $request['amount'];
        $termno  = $request['termno'];
        $order_sn= $request['order_sn'];

        $key     = $request['key'];
        $this->checkSign($key,md5($this->key.$cardno.$panterid.$storeid.$amount.$order_sn.$termno));

        $this->valiOrder($order_sn,'04');
       // $this->valiStore($panterid,$storeid);
        $cardInfo = $this->valiCard($cardno,$panterid);
        $customid = $cardInfo['customid'];
        $flag = $cardInfo['flag'];
        if($flag!='on') $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,此卡已退卡']);


        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);


        $balance = $account['amount'];
        $cardnum = $cardInfo['cardnum'];
        $yue     = bcadd($balance,$amount,2);
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $order_sn = $this->prefix.$order_sn;
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$amount}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','31','{$tac}','0','{$order_sn}')";
        $this->model->startTrans();
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单

        if($tradeif==true && $accountif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'退菜成功','tradeid'=>$tradeid,'balance'=>$yue]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }

    public function giftRecharge(){
        $request      = json_decode($this->getRequestData(),1);
        $cardno       = $request['cardno'];
        $panterid     = $request['panterid'];
        $storeid      = $request['storeid'];
        $termno       = $request['termno'];
        $paymenttype  = $request['paymenttype'];
        $order_amount = $request['order_amount'];
        $pay_amount   = $request['pay_amount'];
        $order_sn     = $request['order_sn'];
        $key          = $request['key'];
        $this->checkSign($key,md5($this->key.$cardno.$panterid.$storeid.$termno.$order_amount.$pay_amount.$order_sn));

        $this->valiOrder($order_sn,'01');
      //  $this->valiStore($panterid,$storeid);
        $cardInfo = $this->valiCard($cardno,$panterid);
        $customid = $cardInfo['customid'];
        $flag = $cardInfo['flag'];
        $cardnum = $cardInfo['cardnum'];
        if(!empty($flag) && $cardnum!='0')  $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,普通卡无法参与特惠充值']);
        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);

        $paymenttype = $this->paymenttype[$paymenttype];

        $map = ['cardno'=>$cardno,'cutsomid'=>$customid,'amount'=>$order_amount,
            'realmoney'=>$pay_amount,
            'userid'=>$this->userid,'paymenttype'=>$paymenttype,'panterid'=>$panterid,
            'num' =>'0','purchaseid'=>$this->getFieldNextNumber('purchaseid'),
            'auditid'=>$this->getFieldNextNumber('auditid'),
            'cardpurchaseid'=>$this->getFieldNextNumber('cardpurchaseid'),
            'termno'=>$termno,
            'order_sn'=>$order_sn
        ];
        $this->model->startTrans();
        $bool=$this->exGiftChong($map);
        if($flag!='on'){
            $cardif = M('cards')->where(['cardno'=>$cardno])->save(['cardnum'=>'0','flag'=>'on']);
        }else{
            $cardif = true;
        }
        if($bool['msg']===true && $cardif==true){
            $this->model->commit();
            $balanceInfo = $this->model->table('account')->where(['type'=>'00','customid'=>$cardInfo['customid']])->field('amount')->find();

            returnMsg(['status'=>'1','codemsg'=>'充值成功','purchaseId'=>$bool['purchaseid'],'balance'=>floatval($balanceInfo['amount']),'org'=>floatval($account['amount'])]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'22','codemsg'=>'充值失败']);
        }


    }
    protected function exGiftChong( $map){
        $cardno       = $map['cardno'];
        $customid     = $map['cutsomid'];
        $chargeAmount = $map['amount'];
        $userid       = $map['userid'];
        $paymenttype  = $map['paymenttype'];
        $panterid     = $map['panterid'];
        $currentDate  = date('Ymd');
        $checkDate    = date('Ymd');
        $num          = $map['num'];
        $realmoney    = $map['realmoney'];
        //审核单号
        $auditid      = $map['auditid'];
        //充值单号
        $purchaseid   = $map['purchaseid'];
        $cardpurchaseid=$map['cardpurchaseid'];
        $termno       = $map['termno'];
        $placeddate   = date('Ymd');
        $placedtime   = date('H:i:s');
        $order_sn     = $map['order_sn'];

        $customplSql="insert into custom_purchase_logs values('".$customid."','{$purchaseid}','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$chargeAmount."',NULL,'".$chargeAmount."',0,'".$realmoney."','".$chargeAmount;
        $customplSql.="',1,'','{$order_sn}','1','".$panterid."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf = $this->model->execute($customplSql);

        //写入审核单
        $auditlogsSql="insert into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
        $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        $auditlogsIf = $this->model->execute($auditlogsSql);

        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$chargeAmount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        //更新账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$chargeAmount." where customid='".$customid."' and type='00'";
        $balanceIf = $this->model->execute($balanceSql);

        if($customplIf==true && $auditlogsIf==true && $cardplIf==true ){
            return ['msg'=>true,'purchaseid'=>$purchaseid];
        }else{
            return false;
        }
    }

    public function returnCard(){
        $request = json_decode($this->getRequestData(),1);
        $cardno  = $request['cardno'];
        $panterid= $request['panterid'];
        $storeid = $request['storeid'];
        $totalmoney  = $request['totalmoney'];
        $termno  = $request['termno'];
        $order_sn= $request['order_sn'];

        $key     = $request['key'];
        $this->checkSign($key,md5($this->key.$cardno.$totalmoney.$panterid.$storeid.$termno.$order_sn));

        $this->valiOrder($order_sn,'03');
      //  $this->valiStore($panterid,$storeid);
        $cardInfo = $this->valiCard($cardno,$panterid);
        $customid = $cardInfo['customid'];
        $flag = $cardInfo['flag'];
        if($flag!='on') $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,此卡已退卡']);


        $account  = M('account')->where(['customid'=>$customid,'type'=>'00'])->field('amount')->find();
        if(!$account) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,此卡账户异常']);
        $account['amount']==$totalmoney || $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,退卡金额有误']);


        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $order_sn = $this->prefix.$order_sn;
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$totalmoney}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','30','{$tac}','0','{$order_sn}')";
        $this->model->startTrans();
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$customid,'type'=>'00'])->save(['amount'=>'0']);
        //写入订单
        $cardIf = M('cards')->where(['cardno'=>$cardno])->save(['flag'=>'off']);

        if($tradeif==true && $accountif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'退卡成功','tradeid'=>$tradeid,'balance'=>0]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }

    public function teamRecharge(){
        $request = json_decode($this->getRequestData(),1);
        $cardlist  = $request['cardlist'];
        $panterid  = $request['panterid'];
        $paymenttype = $request['paymenttype'];
        $storeid   = $request['storeid'];
        $totalmoney= $request['totalmoney'];
        $price     = $request['price'];
        $termno    = $request['termno'];
        $order_sn  = $request['order_sn'];

        $key     = $request['key'];
        $this->checkSign($key,md5($this->key.$cardlist.$paymenttype.$totalmoney.$price.$panterid.$storeid.$termno.$order_sn));
        $this->valiOrder($order_sn,'01');
        //$this->valiStore($panterid,$storeid);
        $cardlist=json_decode($cardlist,1);
        $count   =count($cardlist);
        bcmul($price,$count,2)==$totalmoney || $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,总金额不对']);

        $cardinfo = $this->valiTeamCards($cardlist,$count);
        $map=['paymenttype'=>'现金','amount'=>$price,
            'panterid'=>$panterid,'userid'=>$this->userid,
            'teamid'=>$order_sn,
        ];
        $bool=$this->exTeamCharge($map,$cardinfo);
        if($bool==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'团购成功','teamid'=>$order_sn]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'30','codemsg'=>'操作数据库异常']);
        }
    }
    //团卡冲卡执行
    private function exTeamCharge($map,$info){
        $currentDate=date('Ymd');
        $checkDate=date('Ymd');
        $placddate=date('Ymd');
        $placdtime=date('H:i:s');
        $customplSql  = '';
        $auditlogsSql = '';
        $cardplSql    = '';
        $teamSql      = '';
        $orderSql     = '';
        $order_sn     = $map['teamid'];
        foreach($info as $key =>$val){
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $auditid=$this->getFieldNextNumber('auditid');
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

            if($val['num']===null){
                $val['num']=1;
            }else{
                $val['num']+=1;
            }
            //修改卡使用次数
            $customNumIf=M('cards')->where(['cardno'=>$val['cardno']])->save(['cardnum'=>$val['num'],'flag'=>'on','team'=>'1']);
            if(!$customNumIf){
                $this->model->rollback();
                returnMsg(['status'=>'07','codemsg'=>'修改会员团购记录失败']);
            }
            if($key>=1){
                $customplSql.=" into custom_purchase_logs values('".$map['customid']."','{$purchaseid}','".$currentDate."','{$map['paymenttype']}','";
                $customplSql.=$map['userid']."','".$map['amount']."',NULL,'".$map['amount']."',0,'".$map['amount']."','".$map['amount'];
                $customplSql.="',1,'','{$order_sn}','1','".$map['panterid']."','".$map['userid']."',NULL,'1',";
                $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

                $auditlogsSql.=" into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
                $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$map['userid'] ."','".date('H:i:s',time()+300)."')";

                $cardplSql.=" INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
                $cardplSql.="'{$val['cardno']}','{$map['amount']}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
                $cardplSql.="'{$map['userid']}','{$map['panterid']}','','00000000')";

            }else{
                $customplSql.="INSERT ALL INTO custom_purchase_logs values('".$val['customid']."','{$purchaseid}','".$currentDate."','{$map['paymenttype']}','";
                $customplSql.=$map['userid']."','".$map['amount']."',NULL,'".$map['amount']."',0,'".$map['amount']."','".$map['amount'];
                $customplSql.="',1,'','{$order_sn}','1','".$map['panterid']."','".$map['userid']."',NULL,'1',";
                $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

                $auditlogsSql.="INSERT ALL INTO audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
                $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$map['userid'] ."','".date('H:i:s',time()+300)."')";

                $cardplSql.="INSERT ALL INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
                $cardplSql.="'{$val['cardno']}','{$map['amount']}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
                $cardplSql.="'{$map['userid']}','{$map['panterid']}','','00000000')";

            }
            // 修改账户金额
            $customsInfo = $val['customid'];
        }
        $customplSql.=" SELECT 1 FROM DUAL";
        $auditlogsSql.=" SELECT 1 FROM DUAL";
        $cardplSql.=" SELECT 1 FROM DUAL";
        $orderSql.=" SELECT 1 FROM DUAL";
        $teamSql.=" SELECT 1 FROM DUAL";

        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);
        $cardplIf=$this->model->execute($cardplSql);
        $accountif = $this->model->table('account')
            ->where(['customid'=>['in',array_column($info,'customid')],'type'=>'00'])
            ->save(['amount'=>$map['amount']]);
        if($customplIf==true && $auditlogsSql==true && $cardplIf==true && $accountif==true){
            return true;
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'07','codemsg'=>'数据库写入失败不能充卡']);
        }
    }

    public function returnTeam(){
        $request   = json_decode($this->getRequestData(),1);
        $cardno    = $request['cardno'];
        $panterid  = $request['panterid'];
        $paymenttype = $request['paymenttype'];
        $storeid   = $request['storeid'];
        $totalmoney= $request['totalmoney'];
        $termno    = $request['termno'];
        $teamid    = $request['teamid'];
        $order_sn  = $request['order_sn'];
        $key       = $request['key'];

        $this->checkSign($key,md5($this->key.$cardno.$totalmoney.$panterid.$storeid.$termno.$teamid.$order_sn));
        $this->valiOrder($order_sn,'03');
       // $this->valiStore($panterid,$storeid);
        $cardInfo   = $this->valiCard($cardno,$panterid);
        $flag       = $cardInfo['flag'];
        $team       = $cardInfo['team'];
        if($flag!='on') $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,此卡已退卡']);
        if($team=='0')  $this->returnMsg(['status'=>'03','codemsg'=>'校验信息错误,此卡不是团卡']);

        //
        $info = M('custom_purchase_logs')->alias('cp')
                                         ->join('left join card_purchase_logs cpl on cp.purchaseid=cpl.purchaseid')
                                         ->where(['cp.description'=>$teamid])
                                         ->field('cpl.cardno')->select();
        if(!$info) $this->returnMsg(['status'=>'04','codemsg'=>'查询信息错误,未查到该团队号得记录']);
        $cardlist = array_column($info,'cardno');

        $teaminfo = $this->valiTeamStatus($cardlist,count($cardlist));
        $sum = array_sum(array_column($teaminfo,'amount'));


        $customlist = array_column($teaminfo,'customid');
        $map['cardno']=['in',$cardlist];
        $customWhere['customid']=['in',$customlist];
        $customWhere['type']='00';
        $this->model->startTrans();

        $reTeamid = $this->exTeamCards($teaminfo,['panterid'=>$panterid,'termno'=>$termno,'order_sn'=>$this->prefix.$order_sn],$cardno);
        $customIf=M('cards')->where($map)->save(['flag'=>'off','team'=>'0']);

        $accountIf=M('account')->where($customWhere)->save(['amount'=>'0']);
        if($customIf==true && $accountIf==true){
            $this->model->commit();
            $this->returnMsg(['status'=>'1','codemsg'=>'团退卡成功','totalmoney'=>$sum]);
        }else{
            $this->model->rollback();
            $this->returnMsg(['status'=>'05','codemsg'=>'数据库操作失败,请重试']);
        }
    }
    private function exTeamCards($info,$map,$cardno){
        $termno=$map['termno'];
        $panterid=$map['panterid'];
        $tradetype=30;
        $date=time();
        $teamDate=$date;
        $sql = '';
        $order_sn = $map['order_sn'];
        foreach($info as $key => $val){
            //生成消费记
            $date++;
            $placeddate=date('Ymd',$date);
            $placedtime=date('H:i:s',$date);
            $tradeid=$termno.date('YmdHis',$date);
            $tac='abcdefgh';
            $flag='0';
            if($key>=1){
                $sql.=" INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0','{$order_sn}')";


            }else{
                $sql.="INSERT ALL INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0','{$order_sn}')";
            }
            //tw 表
        }
        $sql.= " SELECT 1 FROM DUAL";

        $tradeif=$this->model->execute($sql);
        if($tradeif==true){
            return true;
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'36','codemsg'=>'退卡交易记录生成失败'));
        }
    }
    private function valiTeamCards($cardlist,$count){
        $instring = $this->inString($cardlist);
        $map = "ca.cardno in ({$instring}) and ca.status='Y' and (ca.flag = 'off' or ca.flag is null)";
        $info=$this->model->table('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->where($map)->field('ca.cardno,cu.customid,ca.cardnum num')->select();
        if(count($info)!=$count){
            $diff = array_diff($cardlist,array_column($info,'cardno'));
            $cards=implode(',',$diff);
            $error = ['status'=>'03','codemsg'=>'有卡号:'.$cards.'账户异常'];
            $this->returnMsg($error);
        }else{
            return $info;
        }
    }
    private function valiTeamStatus($cardlist,$count){
        $instring = $this->inString($cardlist);
        $map = "ca.cardno in ({$instring}) and ca.status='Y' and ca.team = '1' and ac.type='00'";
        $info=$this->model->table('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join account ac on ac.customid=ca.customid')
            ->where($map)->field('ca.cardno,cu.customid,ca.cardnum num,ac.amount')->select();
        if(count($info)!=$count){
            $diff = array_diff($cardlist,array_column($info,'cardno'));
            $cards=implode(',',$diff);
            $error = ['status'=>'03','codemsg'=>'有卡号:'.$cards.'账户异常'];
            $this->returnMsg($error);
        }else{
            return $info;
        }
    }
    protected function inString($arr){
        $str='';
        foreach($arr as $val){
            $str.="'".$val."',";
        }
        return rtrim($str,',');
    }

    protected function getRequestData(){
        $data = getPostJson()['data'];
        $des  = new DESedeCoder();
        $decode = $des->decrypt($data);
        if(false==$decode){
           $this->returnMsg(['status'=>'01','codemsg'=>'加密信息错误']);
        }else{
            return $decode;
        }
    }
    protected function checkSign($key,$check){
        if($key!==$check){
            $this->returnMsg(['status'=>'02','codemsg'=>'签名错误']);
        }else{
            return true;

        }
    }

    protected function returnMsg($msgArr){
        ob_clean();
        exit(json_encode($msgArr,JSON_UNESCAPED_UNICODE));
    }

    private function valiStore($panterid,$storeid){
//        $zkPanter = C('ck_panter');
        if($panterid===$this->panterid){
            $bool = M('zzk_store')->where(['outid'=>$storeid])->field('1')->find();
            if($bool){
                return true;
            }else{
                $this->returnMsg(['status'=>'03','校验信息错误'=>'校验信息错误,查无此店铺']);
            }
        }else{
            $this->returnMsg(['status'=>'03','校验信息错误'=>'校验信息错误,不是周口大食堂商户']);
        }
    }

    private function valiCard($cardno,$panterid){
        if($cardInfo = M('cards')->where(['cardno'=>$cardno])->find()){
            if($cardInfo['status']!='Y'){
                $error= ['status'=>'03','codemsg'=>'校验信息错误,'.$cardno.'不是正常卡'];
            }
            if($cardInfo['cardkind']!='6880'){
                $error= ['status'=>'02','codemsg'=>'校验信息错误:'.$cardno.'不是大食堂卡'];
            }
             if($cardInfo['panterid'] != $panterid){
                 $error= ['status'=>'02','codemsg'=>'校验信息错误:'.$cardno.'不是此大食堂卡'];
             }
            return $cardInfo;
        }else{
            $error= ['status'=>'02','codemsg'=>'校验信息错误:'.$cardno.'不存在'];
        }
        $this->returnMsg($error);
    }

    private function avaliableBalance($balace,$cardnum){
        if($cardnum=='0'){
            return $balace;
        }else{
            if($balace<=$this->frozen){
                return 0;
            }else{
                return bcsub($balace,$this->frozen,2);
            }
        }
    }
    //对会员表 记录的cardnum 在充值进行处理
    protected function customsCardnum($cardno,$cardnum,$flag){
        if($flag==='off' || $flag===null){
            if($cardnum===null){
                $cardnum=1;
            }
            else{
                $cardnum+=1;
            }
            $bool = $this->model->table('cards')->where(['cardno'=>$cardno])->save(['cardnum'=>$cardnum,'flag'=>'on']);
            if(!$bool){
                returnMsg(['status'=>'05','codemsg'=>'修改卡使用次数失败！']);
            }
        }
        return $cardnum;
    }
    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $list=$this->model->query($sql);
        $fieldLength= C('FIELDS_LENGTH')[$field];
        $lastNumber=$list[0]['nextval'];
        return  str_pad($lastNumber,$fieldLength,'0',STR_PAD_LEFT);
    }
    //
    protected function valiOrder($order_sn,$type){
        //01 充值
        if($type==='01'){
            $map['description'] = $order_sn;
            if(M('custom_purchase_logs')->where($map)->find()){
                $this->returnMsg(['status'=>'03','codemsg'=>'检验信息错误,此订单已经充值']);
            }else{
                return true;
            }
        }else{
            $map['eorderid'] = $this->prefix.$order_sn;
            if(M('trade_wastebooks')->where($map)->find()){
                $this->returnMsg(['status'=>'03','codemsg'=>'检验信息错误,此订单已经存在']);
            }else{
                return true;
            }
        }
    }


}