<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class MposCoinController extends CoinController{

  //查询账户信息接口(通过用户编号)
   public function getAccount(){
      $data = $_POST['datami'];
      $datami = trim($data);
      $this->recordData($datami);
       //$datami='{"panterid":"00000126","key":"56899d1e57b99035b31d2de86602816e","customid":"MDAxMTYzNDQO0O0O"}';
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
       $panterid=trim($datami['panterid']);
      $key = trim($datami['key']);
      if(empty($customid)){
          $this->recoreIp();
          returnMsg(array('status'=>'01','codemsg'=>'用户缺失'));
      }
      $checkKey = md5($this->keycode.$customid.$panterid);

      if($checkKey != $key){
          returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid = decode($customid);
       //$customid='00000356';
      $customBool = $this->checkCustom($customid);
      if($customBool == false){
          returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
      }
      //账户信息
      $bmoney= $this->accountQuery($customid,'00');
      #start 
      $zmoney = $this->zzkaccount($customid);//自有资金
      $balance = $bmoney + $zmoney['money'];
      //$this->recordError('备付金：'.$bmoney.'  自有资金：'.serialize($zmoney)."\n\t","Ctbxfdx","mcs");
      #end
      $accountInfo = $this->getCoinInfo($customid);
      //------获取账户下所有卡券数量
      // echo '111';exit;
      $tickketList=$this->getTicketByCustomid($customid,$panterid);
      if($tickketList == false || empty($tickketList)){
        $totaltickket = 0;
      }else{
        $totaltickket = array_sum(array_column($tickketList,'amount'));
      }

       $jyPoint=$this->getJyPoint($customid);
      //--------end
      returnMsg(array('status'=>'1','balance'=>floatval($balance),
          'jycoin'=>floatval($accountInfo['avilableAmount']),'totaltickket'=>$totaltickket,
          'customid'=>$customid,'jyPoint'=>$jyPoint,'time'=>time()));
  }

  //劵查询接口
  public function ticketsQerry(){
      $datami = trim($_POST['datami']);
      //$datami='{"panterid":"00000126","key":"ba239405d8255df0e178e021bf0f12b7","customid":"MDAwMDAzNTYO0O0O"}';
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $panterid=trim($datami['panterid']);
      $key = trim($datami['key']);
      $checkKey = md5($this->keycode.$customid.$panterid);
      if($checkKey != $key){
          returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid=decode($customid);
      empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
      $customBool=$this->checkCustom($customid);
      if($customBool==false){
          returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
      }

      $tickketList = $this->getTicketByCustomid($customid,$panterid);
      $newticklist = array();
      // dump($ticketList);
      $oldtickketList = $this->getConsumeList($customid);
      if($tickketList==false){
         $tickketList = '0';
      }
      foreach ($tickketList as $k => $v) {
         if($v['amount']==0){
           continue;
         }
         $newticklist[] = $v;
      }
          returnMsg(array('status'=>'1','ticketList'=>$newticklist,'oldticketList'=>$oldtickketList,'time'=>time()));
  }

  //券消费通过会员编号
  public function ticketConsume(){
      $datami = trim($_POST['datami']);
      $this->recordData($datami);
      //$datami='{"amount":"1","panterid":"00000126","quanid":"00000023","customid":"MDAwMDAzNTYO0O0O","termposno":"24100704","cardpwd":"BFD0C613A11BF88E","accountid":"0000000006","key":"05685d6dfda5b3361a2472ce21e8d594"}';
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $quanid = trim($datami['quanid']);
      $amount = trim($datami['amount']);
      $panterid = trim($datami['panterid']);
      $termposno = trim($datami['termposno']);//终端号
      $accountid=trim($datami['accountid']);
      $key = trim($datami['key']);
      $cardpwd = trim($datami['cardpwd']);

      $panter=$this->checkPanter($panterid);
      if($panter==false){
          returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
      }
      if(empty($customid)){
          returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
      }
      if(empty($quanid)){
          returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
      }
      if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
          returnMsg(array('status'=>'04','codemsg'=>'消费金额格式错误'));
      }
      if($key != md5($this->keycode.$customid)){
         returnMsg(array('status'=>'10','codemsg'=>'无效秘钥'));
      }

      $customid=decode($customid);
      $map=array('customid'=>$customid);
      $custom=$this->customs->where($map)->find();
      if($custom==false){
          returnMsg(array('status'=>'05','codemsg'=>'会员不存在'));
      }
      $pwdBool=$this->checkCardPwd($customid,$cardpwd);
      if($pwdBool==false){
          returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
      }
      $map1=array('quanid'=>$quanid);
      $quankind=M('quankind')->where($map1)->find();
      if($quankind==false){
          returnMsg(array('status'=>'06','codemsg'=>'营销劵不存在'));
      }
      if($quankind['panterid']!=$panterid&&$panter['parent']!=$quankind['panterid']){
          returnMsg(array('status'=>'010','codemsg'=>'营销劵不能再该商户下消费'));
      }
      if($quankind['atype']==1){
          if($quankind['enddate']<date('Ymd',time())){
              returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
          }

          $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0),'q.enddate'=>array('egt',date('Ymd')));
          $quanAmount=$this->account->alias('a')
              ->join('cards c on a.customid=c.customid')
              ->join('customs_c cc on cc.cid=c.customid')
              ->join('quankind q on q.quanid=a.quanid')
              ->where($where)->sum('a.amount');
          if($quanAmount<=0||$quanAmount<$amount){
              returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
          }
          $this->model->startTrans();
          $consumeIf=$this->ticketExe($customid,$quanid,$amount,$panterid,$termposno);
      }elseif($quankind['atype']==2){
          if(empty($accountid)){
              returnMsg(array('status'=>'010','codemsg'=>'劵消费账户缺失'));
          }
          $where=array('qa.quanid'=>$quanid,'cu.customid'=>$customid,'qa.accountid'=>$accountid);
          $quanList=M('quan_account')->alias('qa')
              ->join('cards c on qa.customid=c.customid')
              ->join('customs_c cc on cc.cid=c.customid')
              ->join('customs cu on cu.customid=cc.customid')
              ->where($where)->field('qa.*,c.cardno')->find();
          if($quanList['enddate']<date('Ymd')){
              returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
          }
          if($quanList['amount']<$amount){
              returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
          }
          $this->model->startTrans();
          $consumeIf=$this->newTicketExe($customid,$quanList,$amount,$panterid,$termposno);
      }
      if($consumeIf==true){
          $this->model->commit();
          returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradidlist'=>$consumeIf,'time'=>time()));
      }else{
          $this->model->rollback();
          returnMsg(array('status'=>'09','codemsg'=>'消费失败'));
      }
  }
  //券退回
  public function backTicket(){
       $datami = trim($_POST['datami']);
       $this->recordData("券消费退回:".$datami);
       $datami = json_decode($datami,1);
       $tradeid = $datami['tradeid'];
       $key = $datami['key'];
       empty($tradeid) && returnMSg(array('status'=>'02','codemsg'=>'订单号缺失'));
       empty($key) && returnMSg(array('status'=>'03','codemsg'=>'秘钥缺失'));
       $key == md5($this->keycode.$tradeid) || returnMSg(array('status'=>'05','codemsg'=>'秘钥验证失败'));
       $map = array('tradeid'=>$tradeid);
       $trade = M('trade_wastebooks')->where($map)->find();
       if($trade == false){
           returnMsg(array('status'=>'06','codemsg'=>'券订单未查到'));
       }
        $this->model->startTrans();
        $bool = $this->backQuanExc($tradeid);
        if($bool == true){
        $this->model->commit();
        $quaninfo = M('quankind')->where(array('quanid'=>$trade['quanid']))->field('quanname')->find();
        returnMSg(array('status'=>'1','codemsg'=>'退回成功！','cardno'=>$trade['cardno'],'amount'=>$trade['tradeamount'],'quanname'=>$quaninfo['quanname'],'time'=>time()));
      }else{
        $this->model->rollback();
        returnMSg(array('status'=>'11','codemsg'=>'退回失败'));
        }
  }

  //通宝和卡余额消费扣款接口
  public function consume(){
      $datami = trim($_POST['datami']);
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $balanceAmount = trim($datami['balanceAmount']);
      $coinAmount = trim($datami['coinAmount']);
      $cardpwd = trim($datami['cardpwd']);//卡密码
      $key = trim($datami['key']);
      $panterid = trim($datami['panterid']);//商户号
      $termposno = trim($datami['termposno']);//终端号
      $this->checkPanter($panterid) || returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
      $checkKey=md5($this->keycode.$customid.$panterid.$termposno);
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
          returnMsg(array('status'=>'05','codemsg'=>'通宝格式有误'));
      }
      if($balanceAmount==0&&$coinAmount==0){
          returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
      }
      $customid=decode($customid);
      $bool=$this->checkCustom($customid);
      if($bool==false){
          returnMsg(array('status'=>'07','codemsg'=>'非法会员编号'));
      }
      if(trim($bool['paypwd'])==''){
          returnMsg(array('status'=>'08','codemsg'=>'未设置支付密码，请在app上设置支付密码后在进行扫码支付'));
      }
      $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
      $pwd=substr($des->doDecrypt($cardpwd),0,6);
//      $pwdBool=$this->checkCardPwd($customid,$cardpwd);
//      if($pwdBool==false){
//          returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
//      }
      if($bool['paypwd']!=md5($pwd)){
          returnMsg(array('status'=>'013','codemsg'=>'支付密码错误'));
      }
      $balanceAccount=$this->accountQuery($customid,'00');
      if($balanceAccount<$balanceAmount){
          returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
      }
      //$coinAccount=$this->accountQuery($customid,'01');
      $coinAccount=$this->coinQuery($customid);
      if($coinAccount<$coinAmount){
          returnMsg(array('status'=>'010','codemsg'=>'通宝余额不足'));
      }

      $this->model->startTrans();
      $balanceConsumeIf=$coinConsumeIf=false;

      //余额消费，金额为0不执行
      if($balanceAmount>0){
          $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'',
          'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'termno'=>$termposno,'tradetype'=>'00');
          //现金订单列表
          $balanceConsumeIf=$this->consumeExe($balanceConsumeArr);
      }else{
          $balanceConsumeIf=true;
      }

      //建业币消费，金额为0不执行
      if($coinAmount>0){
          $coinConsumeArr=array('customid'=>$customid,'orderid'=>'',
              'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount,'termno'=>$termposno);
          //通宝消费订单列表，二维数组
          $coinConsumeIf=$this->coinExe($coinConsumeArr);
          $coinConsumeIf = array_one($coinConsumeIf);

      }else{
          $coinConsumeIf=true;
      }
      if($balanceConsumeIf==true&&$coinConsumeIf==true){
          $this->model->commit();
          //2016-04-27 返回余额 与通宝余额
          $leftBalance = (string)($this->accountQuery($customid,'00'));
          $leftCoin = (string)($this->coinQuery($customid));
          echo json_encode(array('status'=>'1','info'=>array('reduceBalance'=>floatval($balanceAmount),'reduceCoin'=>floatval($coinAmount)),
                                  'cointradidlist'=>$coinConsumeIf,'cashtradidlist'=>$balanceConsumeIf,'leftBalance'=>$leftBalance,
                                  'leftCoin'=>$leftCoin,
                                  'time'=>time(),'isBill'=>''));
          //end
      }else{
          $this->model->rollback();
          returnMsg(array('status'=>'011','codemsg'=>'消费扣款失败'));
      }
  }
  //余额通宝消费订单撤销
  public function cancelOrder(){
      $datami = trim($_POST['datami']);
      $this->recordData('消费订单撤销：'.$datami);
      $datami=json_decode($datami,1);
      $tradeid=trim($datami['tradeid']);
      $key=trim($datami['key']);
      $checkKey=md5($this->keycode.$tradeid);
      if($checkKey!=$key){
          returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
      }
      if(empty($tradeid)){
          returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
      }
      //$tradeid='024620161228170445';
      $trdeWastebooks=M('trade_wastebooks');
      $map['tradeid']=$tradeid;
      $map['flag']=0;
      $map['tradetype']=array('in','00,01');
      $tradeinfo=$trdeWastebooks->where($map)
          ->field("tradeid,tradeamount,tradepoint,cardno,eorderid,customid")->find();
      if($tradeinfo==false){
          returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
      }
      $cardkind=substr($tradeinfo['cardno'],0,4);
      if($cardkind=='6688'){
          //酒店新卡订单撤销
          $map1=array('flag'=>0,'eorderid'=>$tradeinfo['eorderid'],'tradetype'=>array('in','00,01'));
          $list=$trdeWastebooks->where($map1)->select();
          $this->model->startTrans();
          $count=0;
          $balaceAmount=0;
          //print_r($list);exit;
          foreach($list as $key=>$val){
              $map['cardno']=$val['cardno'];
              $cardInfo=$this->cards->where($map)->find();
              $customid=$cardInfo['customid'];
              if($val['tradetype']=='00'){
                  $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradeamount']." WHERE customid='{$customid}' and type='00'";
                  $balanceIf=$this->account->execute($balanceSql);
                  $tradeMap=array('tradeid'=>$val['tradeid']);
                  $tradeData=array('tradetype'=>'04');
                  $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
                  $map2=array('tradeid'=>$val['tradeid']);
                  $tradeBillList=M('tradebilling')->where($map2)->find();
                  if($tradeBillList!=false){
                      $tradeBillSql="DELETE FROM  TRADEBILL WHERE tradeid='{$val['tradeid']}'";
                      //$tradeBillSql="UPDATE TRADEBILL SET AMOUNT=NVL(AMOUNT,0)-{$val['tradeamount']},status=0 WHERE tradeid='{$val['tradeid']}'";
                      $billDetailList=M('billingdetail')->where($map2)->select();
                      $c=0;
                      foreach($billDetailList as  $k=>$v){
                          $billingDetailSql="DELETE FROM BILLINGDETAIL WHERE card_purchaseid='{$v['card_purchaseid']}' and tradeid='{$val['tradeid']}'";
                          $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)-{$v['amount']},FLAG=0 WHERE card_purchaseid='{$v['card_purchaseid']}'";
                          $billingDetailIf=$this->model->execute($billingDetailSql);
                          $billingIf=$this->model->execute($billingDetailIf);
                          if($billingDetailIf==true&&$billingIf==true){
                              $c++;
                          }
                      }
                      if($c=count($billDetailList)){
                          $billIf=true;
                      }
                  }else{
                      $billIf=true;
                  }
                  $balaceAmount+=$val['tradeamount'];
                  if($tradeIf==true&&$balanceIf==true&&$billIf==true){
                      $count++;
                  }
              }elseif($val['tradetype']=='01'){
                  $pointSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradeamount']." WHERE customid='{$customid}' and type='04'";
                  $pointIf=$this->model->execute($pointSql);
                  $tradeMap=array('tradeid'=>$val['tradeid']);
                  $tradeData=array('tradetype'=>'11');
                  $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
                  $map2=array('tradeid'=>$val['tradeid']);
                  $pointConsumeList=M('point_consume')->where($map2)->select();
                  $c=0;
                  foreach($pointConsumeList as $k=>$v){
                      $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
                      $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
                      $pointConsumeIf=$this->model->execute($pointConsumeSql);
                      $pointAccountIf=$this->model->execute($pointAccountSql);
                      if($pointConsumeIf==true&&$pointAccountIf==true){
                          $c++;
                      }
                  }
                  if($c=count($pointConsumeList)){
                      $pointConsumeIf=true;
                  }
                  if($tradeIf==true&&$pointIf==true&&$pointConsumeIf==true){
                      $count++;
                  }
                  $pointAmount=$val['tradeamount'];
              }
          }
          if($count=count($list)){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功',
                  'balance'=>floatval($balaceAmount),
                  'coin'=>floatval($pointAmount),'time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
          }
      }elseif($cardkind=='6880'){
          //建业之家
          $card=$this->model->table('cards')->where(array('cardno'=>$tradeinfo['cardno']))->find();
          $this->model->startTrans();
          $pointSql="UPDATE account SET amount=nvl(amount,0)+".floatval($tradeinfo['tradeamount'])." WHERE customid='{$card['customid']}' and type='04'";
          $pointIf=$this->model->execute($pointSql);
          $tradeMap=array('tradeid'=>$tradeinfo['tradeid']);
          $tradeData=array('tradetype'=>'11');
          $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
          $map2=array('tradeid'=>$tradeinfo['tradeid']);
          $pointConsumeList=M('point_consume')->where($map2)->select();
          $c=0;
          foreach($pointConsumeList as $k=>$v){
              $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
              $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
              $pointConsumeIf=$this->model->execute($pointConsumeSql);
              $pointAccountIf=$this->model->execute($pointAccountSql);
              if($pointConsumeIf==true&&$pointAccountIf==true){
                  $c++;
              }
          }
          if($c=count($pointConsumeList)){
              $pointConsumeIf=true;
          }
          if($pointConsumeIf==true&&$tradeIf==true&&$pointIf==true){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功',
                  'balance'=>floatval($tradeinfo['tradeamount']),'coin'=>'','time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
          }
      }elseif($cardkind=='2336'||$cardkind=='6888'){
          if(!preg_match('/^6882/',$tradeinfo['cardno'])){
              $balance=$this->cardAccQuery($tradeinfo['cardno']);
              $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                 ->join('customs cu on cu.customid=cc.customid')
                 ->where(array('c.cardno'=>$tradeinfo['cardno']))
                 ->field('cu.namechinese,cu.personid')->find();
             if(empty($custom['namechinese']) || empty($custom['personid'])){
                 if($balance+$tradeinfo['tradeamount']>1000){
                     returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限1000，禁止退款'));
                 }
             }
              if($balance+$tradeinfo['tradeamount']>5000){
                  returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限，禁止退款'));
              }
          }
          if($tradeinfo['eorderid']=='pos_' || $tradeinfo['eorderid']==''){
              $tradeList=$trdeWastebooks->where(array('tradeid'=>$tradeinfo['tradeid']))
                  ->field("tradeid,tradeamount,cardno,eorderid")->select();
          }else{

              $tradeList=$trdeWastebooks->where(array('eorderid'=>$tradeinfo['eorderid']))
                  ->field("tradeid,tradeamount,cardno,eorderid")->select();
          }

          $this->model->startTrans();
          $c=0;
          //echo $trdeWastebooks->getLastSql();exit;
          //print_r($tradeList);exit;
          $string=$trdeWastebooks->getLastSql().'<br/>';
          $amount=0;
          foreach($tradeList as $key=>$val){
              $map['cardno']=$val['cardno'];
              $cardInfo=$this->cards->where($map)->find();
              $customid=$cardInfo['customid'];
              $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradeamount']." WHERE customid='{$customid}' and type='00'";
              $balanceIf=$this->account->execute($balanceSql);
              $tradeMap=array('tradeid'=>$val['tradeid']);
              $tradeData=array('tradetype'=>'04');
              $string.="{$balanceSql}<br/>";
              $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
              if($tradeIf==true&&$balanceSql==true){
                  $c++;
                  $amount+=$val['tradeamount'];
              }
          }
          $this->recordData('消费订单撤销sql：'.$string.count($tradeList));
          if($c==count($tradeList)){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','balance'=>floatval($amount),
                  'coin'=>floatval($tradeinfo['tradepoint']),'time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
          }
      }else{
          if($tradeinfo['tradepoint']>0){
              returnMsg(array('status'=>'06','codemsg'=>'通宝消费不允许撤销'));
          }
          if(!preg_match('/^6882/',$tradeinfo['cardno'])){
              $balance=$this->cardAccQuery($tradeinfo['cardno']);

             // $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
             //     ->join('customs cu on cu.customid=cc.customid')
             //     ->where(array('c.cardno'=>$tradeinfo['cardno']))
             //     ->field('cu.namechinese,cu.personid')->find();
             // if(empty($custom['namechinese']) || empty($custom['personid'])){
             //     if($balance+$tradeinfo['tradeamount']>1000){
             //         returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限1000，禁止退款'));
             //     }
             // }

              if($balance+$tradeinfo['tradeamount']>5000){
                  returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限 5000，禁止退款'));
              }
          }
          $this->model->startTrans();
          $map2['cardno']=$tradeinfo['cardno'];
          $cardInfo=$this->cards->where($map2)->find();
          $customid=$cardInfo['customid'];
          if($tradeinfo['tradeamount']!=0){
              $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='00'";
              $balanceIf=$this->account->execute($balanceSql);
          }else{
              $balanceIf=true;
          }
          if($tradeinfo['tradepoint']!=0){
              $coinSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradepoint']." WHERE customid='{$customid}' and type='01'";
              $coinIf=$this->account->execute($coinSql);
              $coinReturnIf=$this->returnCoin($tradeinfo['tradeid']);
          }else{
              $coinIf=true;
              $coinReturnIf=true;
          }
          $tradeMap=array('tradeid'=>$tradeinfo['tradeid']);
          $tradeData=array('tradetype'=>'04');
          $tradeIf=M('trade_wastebooks')->where($tradeMap)->save($tradeData);

          if($balanceIf==true&&$coinIf==true&&$tradeIf==true&&$coinReturnIf==true){
              $this->model->commit();
              $data = $balanceSql."\r\n".$coinSql."***********\r\n";
              $this->recordError($data,'cancelOrder','卡编号_'.$tradeinfo['cardno']);
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功',
                  'balance'=>floatval($tradeinfo['tradeamount']),
                  'coin'=>floatval($tradeinfo['tradepoint']),'time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败'));
          }
      }
  }

  //获取卡券消费信息
  protected function getConsumeList($customid){

      $where=array('cu.customid'=>$customid,'c.status'=>'Y');
      $cards=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
          ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($where)->select();
      $cards || die(json_encode(array('code'=>50,'message'=>'卡号丢失。。。。')));
      $cardArr = $this->serializeArr($cards,'cardno');
      $cardArr = implode(',',$cardArr);
      $where1=" tw.tradetype='02' and tw.flag=0 and tw.cardno in({$cardArr})";
      $quaninfo = M('trade_wastebooks')->alias('tw')->join('quankind q on tw.quanid=q.quanid')
          ->field('tw.quanid as quanid,q.quanname as quanname, sum(tw.tradeamount) as amount')
          ->where($where1)
          ->group('tw.quanid,q.quanname')
          ->select();
      if($quaninfo==false){
        return '0';
      }else{
        return $quaninfo;
      }
  }

    protected function getJyPoint($customid){
        $map=array('cu.customid'=>$customid,'c.cardkind'=>'6680');
        $cardInfo=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($map)->find();

        if($cardInfo==false){
            return false;
        }

        $panterid='00000952';
        $map=array('c.cardno'=>$cardInfo['cardno'],'pa.panterid'=>$panterid);
        $map['_string']='pa.enddate=0 or pa.enddate>='.date('Ymd');
        $amount=$this->cards->alias('c')->join('point_account pa on c.customid=pa.cardid')
            ->where($map)->sum('remindamount');
        $amount=empty($amount)?0:$amount;
        return $amount;
    }

    //通宝和卡余额消费扣款接口
    public function consume1(){
        $datami = trim($_POST['datami']);
        $this->recordError(date("H:i:s") . "-POS扫码支付记录起始\n\t" . $datami."\n\t","Ctbxfdx","info");
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $balanceAmount = trim($datami['balanceAmount']);
        $coinAmount = trim($datami['coinAmount']);
        $cardpwd = trim($datami['cardpwd']);//卡密码
        $key = trim($datami['key']);
        $panterid = trim($datami['panterid']);//商户号
        $posFlagId=trim($datami['posFlagId']);
        $termposno = trim($datami['termposno']);//终端号
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
        }
        $checkKey=md5($this->keycode.$customid.$panterid.$termposno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'03','codemsg'=>'用户缺失'));
        }
        if($coinAmount>0&&empty($posFlagId)){
            returnMsg(array('status'=>'012','codemsg'=>'pos单号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'05','codemsg'=>'通宝格式有误'));
        }
        if($balanceAmount==0&&$coinAmount==0){
            returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
        }
        $customid=decode($customid);
        $bool=$this->checkCustom($customid);
        if($bool==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法会员编号'));
        }
        if(trim($bool['paypwd'])==''){
            returnMsg(array('status'=>'08','codemsg'=>'未设置支付密码，请在app上设置支付密码后在进行扫码支付'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=substr($des->doDecrypt($cardpwd),0,6);
        if($bool['paypwd']!=md5($pwd)){
            returnMsg(array('status'=>'013','codemsg'=>'支付密码错误'));
        }
        $bmoney=$this->accountQuery($customid,'00');
        #start
        $zmoney = $this->zzkaccount($customid);//自有资金
        $balanceAccount = $bmoney + $zmoney['money'];
        $this->recordError('备付金：'.$bmoney.'  自有资金：'.serialize($zmoney)."\n\t","Ctbxfdx","info");
        #end
        if($balanceAccount<$balanceAmount){
            returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
        }
        //$coinAccount=$this->accountQuery($customid,'01');
        $coinAccount=$this->coinQuery($customid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'010','codemsg'=>'通宝余额不足'));
        }

        $this->model->startTrans();
        $balanceConsumeIf=$coinConsumeIf=false;

        //余额消费，金额为0不执行
        if($balanceAmount>0){
            #start  $balanceAmount 要扣除的金额
            //zzk_order 订单信息 主键ID order_sn订单号 tradetype交易方式 panterid商户id storeid门店id source来源 amount金额
            $zzk_orderid = $this->zzkgetnumstr('zzk_orderid', 16);
            $inner_order = $this->zzktradeid('05', '05', $zmoney['accountid']);
            $placeddate = date('Ymd');
            $placedtime = date('H:i:s');
            $combined = 0;
            $desc = 'POS扫码消费';
            if ($bmoney < $balanceAmount) {
                $zzkamount = $balanceAmount - $bmoney;
                $balanceAmount = $bmoney;
                $zmoney['inner_order'] = $inner_order;
                $zmoney['orderid'] = 'pos_'.$posFlagId;
                $zmoney['placeddate'] = $placeddate;
                $zmoney['placedtime'] = $placedtime;
                $zmoney['panterid'] = $panterid;
                $zmoney['desc'] = $desc;
                $rs = $this->equityfund($zzkamount, $zmoney);
                $this->recordError('自有资金扣款返回信息：'.serialize($rs) . "\n\t", "Ctbxfdx", "info");
                if($rs['status'] != 1) {
                    returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
                }
                if($bmoney > 0) {
                    $combined = 1;
                }
            }
            $sql = "INSERT INTO zzk_order(combined,orderid,tradetype,order_sn,placeddate,placedtime,panterid,storeid,accountid,inner_order,source,amount,paytype,description) values ";
            $sql .= "('{$combined}','{$zzk_orderid}','50','pos_{$posFlagId}','{$placeddate}','{$placedtime}','{$panterid}','{$panterid}','{$zmoney[accountid]}','{$inner_order}','07','{$datami[balanceAmount]}','05','{$desc}')";
            $orderInfo = $this->model->execute($sql);
            $this->recordError($sql."\n\t","Ctbxfdx","info");
            #end

            
            if($balanceAmount > 0) {
                $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'termno'=>$termposno,'tradetype'=>'00');
                //现金订单列表
                $this->recordError('consumeExe：' . serialize($balanceConsumeArr) . "\n\t", "Ctbxfdx", "info"); 
                $balanceConsumeIf=$this->consumeExe($balanceConsumeArr);
                $this->recordError('consumeExe：' . serialize($balanceConsumeIf) . "\n\t", "Ctbxfdx", "info");  
            } else {
                $balanceConsumeIf = ['zy_'.$inner_order];
            }

        }else{
            $balanceConsumeIf=true;
        }

        //建业币消费，金额为0不执行
        if($coinAmount>0){
            $coinConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,
                'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount,'termno'=>$termposno);
            //通宝消费订单列表，二维数组
//            $coinConsumeIf=$this->coinExe($coinConsumeArr);
//            $coinConsumeIf = array_one($coinConsumeIf);
            $this->recordError('consumeCoin：' . serialize($coinConsumeArr) . "\n\t", "Ctbxfdx", "info");
	        $coinConsumeIf  = $this->consumeCoin($coinConsumeArr);

        }else{
            $coinConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$coinConsumeIf==true){
            $this->model->commit();
            //2016-04-27 返回余额 与通宝余额
            if($coinAmount>0){
                $messageArr=array('tradeid'=>$posFlagId,'linktel'=>$bool['linktel'],
                'amount'=>$coinAmount,'pname'=>$panter['namechinese'],'date'=>date('Y-m-d H:i:s'));
                //        订单同步的接口：
                //开发环境：http://testmall.yijiahn.com/mall
                //测试环境：http://cso2o.yijiahn.com:8088/mall
                //预生产环境：http://yscmall.yijiahn.com/mall
                //生产环境：http://mall.yijiahn.com/mall
                $this->recordData(json_encode($messageArr));
                $url=C('ecardConsume').'/api/order/tongbao/sync.json';
                crul_post($url,$messageArr);
        }
            $leftBalance = (string)($this->accountQuery($customid,'00'));
            $leftCoin = (string)($this->coinQuery($customid));
            echo $str = json_encode(array('status'=>'1','info'=>array('reduceBalance'=>floatval($datami['balanceAmount']),'reduceCoin'=>floatval($coinAmount)),
                'cointradidlist'=>$coinConsumeIf,'cashtradidlist'=>$balanceConsumeIf,'leftBalance'=>$leftBalance,
                'leftCoin'=>$leftCoin,
                'time'=>time(),'isBill'=>''));
            $this->recordError('返回信息：' . $str . "\n\t\r\r", "Ctbxfdx", "info"); 
            //end
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'011','codemsg'=>'消费扣款失败'));
        }
    }
}
