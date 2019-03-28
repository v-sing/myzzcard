<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class MposCoin1Controller extends CoinController{

  //查询账户信息接口(通过用户编号)
   public function getAccount(){
      $data = $_POST['datami'];
      $datami = trim($data);
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $key = trim($datami['key']);
      if(empty($customid)){
          $this->recoreIp();
          returnMsg(array('status'=>'01','codemsg'=>'用户缺失'));
      }
      $checkKey = md5($this->keycode.$customid);

      if($checkKey != $key){
          returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid = decode($customid);
      $customBool = $this->checkCustom($customid);
      if($customBool == false){
          returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
      }
      //账户信息
      $balance= $this->accountQuery($customid,'00');
      $accountInfo = $this->getCoinInfo($customid);
      //------获取账户下所有卡券数量
      $tickketList=$this->getTicketByCustomid($customid);
      if($tickketList == false || empty($tickketList)){
        $totaltickket = 0;
      }else{
        $totaltickket = array_sum(array_column($tickketList,'amount'));
      }
      //--------end
      returnMsg(array('status'=>'1','balance'=>floatval($balance),
          'jycoin'=>floatval($accountInfo['avilableAmount']),'totaltickket'=>$totaltickket,'customid'=>$customid,'time'=>time()));
  }

  //劵查询接口
  public function ticketsQerry(){
      $datami = trim($_POST['datami']);
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $key = trim($datami['key']);
      $checkKey = md5($this->keycode.$customid);
      if($checkKey != $key){
          returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid=decode($customid);
      empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
      $customBool=$this->checkCustom($customid);
      if($customBool==false){
          returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
      }

      $tickketList = $this->getTicketByCustomid($customid);
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
     $datami = json_decode($datami,1);
     $customid = trim($datami['customid']);
     $quanid = trim($datami['quanid']);
     $amount = trim($datami['amount']);
     $panterid = trim($datami['panterid']);
     $termposno = trim($datami['termposno']);//终端号
     $key = trim($datami['key']);
     $cardpwd = trim($datami['cardpwd']);

      $this->checkPanter($panterid) || returnMSg(array('status'=>'50','codemsg'=>'商户不存在！'));
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
      if($quankind['enddate']<date('Ymd',time())){
          returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
      }
      $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
      $quanAmount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')->where($where)->sum('a.amount');
      if($quanAmount<$amount){
          returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
      }
      $this->model->startTrans();
      $consumeIf=$this->ticketExe($customid,$quanid,$amount,$panterid,$termposno);
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
      $this->checkPanter($panterid) || returnMSg(array('status'=>'50','codemsg'=>'商户不存在！'));
      $checkKey=md5($this->keycode.$customid.$panterid.$termposno);
      if($checkKey!=$key){
          returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
      }
      empty($cardpwd) && returnMSg(array('status'=>'12','codemsg'=>'卡密码为空'));
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
          returnMsg(array('status'=>'08','codemsg'=>'非法会员编号'));
      }
      $pwdBool=$this->checkCardPwd($customid,$cardpwd);
      if($pwdBool==false){
          returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
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
          'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'termno'=>$termposno);
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
                                  'time'=>time()));
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
      $map['tradeid']=$tradeid;
      $map['flag']=0;
      $map['tradetype']='00';
      $tradeinfo=M('trade_wastebooks')->where($map)
          ->field("tradeid,tradeamount,tradepoint,cardno")->find();
      if($tradeinfo==false){
          returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
      }
      if($tradeinfo['tradepoint']>0){
          returnMsg(array('status'=>'06','codemsg'=>'通宝消费不允许撤销'));
      }
      if(!preg_match('/^6882/',$tradeinfo['cardno'])){
          $balance=$this->cardAccQuery($tradeinfo['cardno']);
          if($balance+$tradeinfo['tradeamount']>5000){
              returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限，禁止退款'));
          }
      }
      $this->model->startTrans();
      $map['cardno']=$tradeinfo['cardno'];
      $cardInfo=$this->cards->where($map)->find();
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


}
