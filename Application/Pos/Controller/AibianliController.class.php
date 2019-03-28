<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class AibianliController extends CoinController{

	public function _initialize(){
		$this->model=new model();
		$this->customs=M('customs');
		$this->account=M('account');
		$this->cards=M('cards');
		$this->customs_c=M('customs_c');
		$this->prefix = 'abl_';
		$this->keycode='jyzzabl2017';
		$this->barcodeLifeTime = '90';
        $this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $this->panterid = '00003814';
        $this->barcodeLimitAmount = 1000;
	}

//查询账户信息接口(通过会员卡)
   public function getAccount(){
     $datami = trim($_POST['datami']);
     $this->writeLog(__FUNCTION__,'get',$datami);
     //$datami='{"key":"acd82f9ebbfa50d7f9d3edfdbc7971e0","cardno":"6889371888800160527"}';
     $datami = json_decode($datami,1);
     $cardno= trim($datami['cardno']);
     $key= trim($datami['key']);
     $checkKey=md5($this->keycode.$cardno);
     if($checkKey!=$key){
         $this->recoreIp();
         returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
     }
     $cardno = $this->decodeCard($cardno);
     $map=array('cardno'=>$cardno);
     $card=$this->cards->where($map)->find();
     if($card==false){
         $this->recoreIp();
         returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
     }
       if($card['status']!='Y'){
           if($card['status']=='A'){
               returnMsg(array('status'=>'04','codemsg'=>'待激活卡，请先激活'));
           }else{
               returnMsg(array('status'=>'05','codemsg'=>'非正常卡号'));
           }
       }
      $customid = $this->getCustomid($cardno);
      if($customid == false || empty($customid)){
          returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
      }
      //账户信息
      //$balance= $this->accountQuery($customid,'00');
      //$accountInfo = $this->getCoinInfo($customid);

       if(in_array($card['cardkind'],array('2336','6888'))){
           $balance=$this->zzAccountQuery($customid,'00');
       }else{
           $balance=$this->cardAccQuery($cardno);
       }
       $coinAmount=$this->getCoinByCardno($cardno);
      //------获取账户下所有卡数量
      //$tickketList=$this->getTicketByCustomid($customid);
//       $ticketList=$this->getTicketByCardno($cardno,$panterid);
//       if($ticketList == false || empty($ticketList)){
//         $totaltickket = 0;
//       }else{
//         $totaltickket = array_sum(array_column($ticketList,'amount'));
//       }
      //--------end
      returnMsg(array('status'=>'1','balance'=>floatval($balance),
          'jycoin'=>floatval($coinAmount),'customid'=>$customid,'time'=>time()));
  }

  

  

  //通宝和卡余额消费扣款接口
//  public function consume(){
//      //$returnUrl='http://kabao.9617777.com/poscreditapi.php?act=sendTemp';
//      $datami = trim($_POST['datami']);
//      $this->recordData($datami);
//      $datami = json_decode($datami,1);
//      //$datami=json_decode('{"tradetype":"1","panterid":"00000219","totalbalance":"0","totalpoint":"1","termno":"21900671","cardno":"6889371888800160527","cardpwd":"BFD0C613A11BF88E","sourcecode":"ABL","outorderid":"abl2017122610101122123","key":"9327fe9af70ed034d748da0b6e655f8c"}',1);
////      $tradetype=trim($datami['tradetype']);
//      $cardno = trim($datami['cardno']);
//      $balanceAmount = trim($datami['totalbalance']);
//      $coinAmount = trim($datami['totalpoint']);
//      $cardpwd = trim($datami['pwd']);
//      $panterid = trim($datami['panterid']);//爱便利商户号
//      $termposno = trim($datami['termno']);//爱便利终端号
//      $outorderid=trim($datami['outorderid']);
//      $sourcecode=trim($datami['sourcecode']);
//      $key = trim($datami['key']);
//
////      $cardno = '6688371800000000001';
////      $balanceAmount = 12.00;
////      $coinAmount = 0.0;
////      $cardpwd = 'BFD0C613A11BF88E';
////      $key = '9f7f33875102bbc504c7bb77c6ae3d90';
////      $panterid = '00000299';//商户号
////      $termposno = '29900640';//终端号
////      $posFlagId='3b0eba4f-52c0-4a59-b93a-77b5eb58366a';
//      //$string=$cardno.'--'.$balanceAmount.'--'.$coinAmount.'--'.$cardpwd.'--'.$key.'--'.$panterid.'--'.$termposno.'--'.$posFlagId;
//      //$this->recordData($string);
//		//16889371888800160527018888880000021921900671 741ec43984f449d5627c544d519a85bb
//		//echo $this->keycode.$tradetype.$cardno.$balanceAmount.$coinAmount.$cardpwd.$panterid.$termposno.$outorderid.$sourcecode;exit;
//      $checkKey=md5($this->keycode.$cardno.$balanceAmount.$coinAmount.$cardpwd.$panterid.$termposno.$outorderid.$sourcecode);
//      $panter=$this->checkPanter($panterid);
//      if($panter==false){
//          returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
//      }
//      if($checkKey!=$key){
//          returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
//      }
//      empty($cardpwd) && returnMSg(array('status'=>'12','codemsg'=>'卡密码为空'));
//      if(empty($cardno)){
//          returnMsg(array('status'=>'03','codemsg'=>'卡号缺失'));
//      }
//      $b=$this->checkCardValidate($cardno);
//      if($b==false){
//          returnMsg(array('status'=>'07','codemsg'=>'卡已过期'));
//      }
//      if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
//          returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
//      }
//      if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
//          returnMsg(array('status'=>'05','codemsg'=>'通宝格式有误'));
//      }
//      if($balanceAmount==0&&$coinAmount==0){
//          returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
//      }
////       if($panterid=='00000299'){
////           if($balanceAmount>0) {
////               $limitBool=$this->checkTradeLimited($cardno,$balanceAmount,$panterid);
////               if($limitBool==false){
////                   returnMsg(array('status'=>'011','codemsg'=>'消费受限'));
////               }
////           }
////       }
//      $map = array('cardno'=>$cardno);
//      $card = $this->cards->where($map)->field('customid,cardpassword,status,dcflag,panterid,cardkind')->find();
//      if($panter['parent']=='00002313'){
//          $card_panter=M('panters')->where(array('panterid'=>$card['panterid']))->find();
//          if($card_panter['parent']!='00002313'){
//              returnMsg(array('status'=>'017','codemsg'=>'非生态新城卡不能再此消费'));
//          }
//      }else{
//          $card_panter=M('panters')->where(array('panterid'=>$card['panterid']))->find();
//          if($card_panter['parent']=='00002313'){
//              returnMsg(array('status'=>'018','codemsg'=>'生态新城卡不能再此消费'));
//          }
//      }
//      if($card == false){
//          returnMsg(array('status'=>'01','codemsg'=>'非法卡号'));
//      }
//      if($card['status']!='Y'){
//          if($card['status']=='J'){
//              returnMsg(array('status'=>'015','codemsg'=>'卡已被锁定'));
//          }elseif($card['status']=='A'){
//              returnMsg(array('status'=>'016','codemsg'=>'待激活卡，请先激活后才能消费'));
//          }else{
//              returnMsg(array('status'=>'012','codemsg'=>'非正常卡号'));
//          }
//      }
//      $card['cardpassword'] = substr($this->des->doDecrypt($card['cardpassword']),0,6);
//      $card['cardpassword'] = md5(md5($card['cardpassword']));
//      if($card['cardpassword']!==$cardpwd){
//          if($card['cardflag']<2){
//              $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1 WHERE CARDNO='{$cardno}'";
//          }else{
//              $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1,status='G' WHERE CARDNO='{$cardno}'";
//          }
//          $this->cards->execute($sql);
//          returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
//      }
//      if(in_array($card['cardkind'],array('2336','6888'))){
//          $customid = $this->getCustomid($cardno);
//          $balance=$this->zzAccountQuery($customid,'00');
//      }else{
//          $balance=$this->cardAccQuery($cardno);
//      }
//      if($balance<$balanceAmount){
//          returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
//      }
//      // $coinAccount=$this->accountQuery($customid,'01');
//      $coinAccount=$this->getCoinByCardno($cardno);
//      if($coinAccount<$coinAmount){
//          returnMsg(array('status'=>'010','codemsg'=>'通宝余额不足'));
//      }
//
//      $this->model->startTrans();
//      $balanceConsumeIf=$coinConsumeIf=false;
//
//      //余额消费，金额为0不执行
//      if($balanceAmount>0){
//          $limitIf=$this->checkPanterLimit($card['panterid'],$panterid);
//          if($limitIf==='02'){
//              returnMsg(array('status'=>'017','codemsg'=>'酒店卡不能再在非酒店商户消费'));
//          }
//          $consumeLimit=M('panter_con_account')->where(array('panterid'=>$panterid))->find();
////          if($consumeLimit!=false){
////              //消费金额超过商户单笔消费限制
////              if($balanceAmount>$consumeLimit['d_one_account']){
////                  returnMsg(array('status'=>'018','codemsg'=>'单笔消费超限'));
////              }
////              $consumeCon=array('panterid'=>$panterid,'placeddate'=>data('Ymd'),'tradeamount'=>array('gt',0));
////              $c=M('trade_wastebooks')->where($consumeCon)->count();
////              $sum=M('trade_wastebooks')->where($consumeCon)->sum('tradeamount');
////              //商户交易次数超限
////              if($c>=$consumeLimit['d_sum_number']){
////                  returnMsg(array('status'=>'019','codemsg'=>'商户日交易次数超限'));
////              }
////              //商户交易次数超限
////              if($sum>=$consumeLimit['d_sum_account']){
////                  returnMsg(array('status'=>'020','codemsg'=>'商户日交易金额超限'));
////              }
////          }
//
//
//          if(in_array($card['cardkind'],array('2336','6888'))){
//              $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'outid_'.$posFlagId,
//                  'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'tradetype'=>'00',
//                  'termno'=>$termposno);
//              $balanceConsumeIf=$this->consumeExeByzzCardno($balanceConsumeArr);
//          }else{
//              $balanceConsumeArr=array('cardno'=>$cardno,'orderid'=>'outid_'.$posFlagId,
//                  'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'tradetype'=>'00',
//                  'termno'=>$termposno);
//              //现金订单列表
//              $balanceConsumeIf=$this->consumeExeByCardno($balanceConsumeArr);
//          }
//      }else{
//          $balanceConsumeIf=true;
//      }
//
//      //建业币消费，金额为0不执行
//      if($coinAmount>0){
//          $coinConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,
//              'panterid'=>$panterid,'amount'=>$coinAmount,
//              'termno'=>$termposno);
//          //通宝消费订单列表，二维数组
//          $coinConsumeIf=$this->coinExeByCardno($coinConsumeArr);
//          dump($coinConsumeIf);
//          //$coinConsumeIf = array_one($coinConsumeIf);
//      }else{
//          $coinConsumeIf=true;
//      }
//      if($balanceConsumeIf==true&&$coinConsumeIf==true){
//          $this->model->commit();
//          $map=array('panterid'=>$panterid);
//          $panter=M('panters')->where($map)->find();
//          $returnData=array('cardno'=>encode($cardno),'balanceAmount'=>$balanceAmount,
//              'coinAmount'=>$coinAmount,'cardno'=>$cardno,'pname'=>$panter['namechinese']);
//          //crul_post($returnUrl,json_encode($returnData));
//		  //--------20160401-------
//          // if(floatval($coinAmount)>0){
//          //   $customInfo=M('customs')->where("customid='{$customid}'")->find();
//          //   $tpl_value="#name#=".urlencode("{$customInfo['namechinese']}")."&#tel#=".urlencode("{$customInfo['linktel']}")."&#content#=".urlencode("{$panter['namechinese']}")."消费";
//          //   $nowmobile = "18538295100,18697323783";
//          //   $re = $this->tpl_send("1307781",$nowmobile,$tpl_value);
//          //   $this->recordError(date("H:i:s").'-'.$tpl_value.'-'.$re."\n\t","Ctbxfdx","log");
//          // }
//           $leftBalance = (string)($this->cardAccQuery($cardno));
//           $leftCoin = (string)($this->getCoinByCardno($cardno));
//          //---------end--------
//
//          $cardkind=substr($cardno,0,4);
//          $kindArr=array('6882','2081');
//          $isBill=in_array($cardkind,$kindArr)?0:1;
//          if(is_bool($balanceConsumeIf)){
//              $cashtradidlist=$balanceConsumeIf;
//          }else{
//              $cashtradidlist=array($balanceConsumeIf);
//          }
//          echo json_encode(
//              array(
//                  'status'=>'1',
//                  'info'=>array(
//                      'totalbalance'=>floatval($balanceAmount),
//                      'totalpoint'=>floatval($coinAmount),
//                  		'orderid' => $coinConsumeIf[0],//数组
//                  		'outorderid'=> $sourcecode.'_'.$outorderid
//                  )
//              )
//          );
//      }else{
//          $this->model->rollback();
//          returnMsg(array('status'=>'011','codemsg'=>'消费扣款失败'));
//      }
//  }

  //余额通宝消费订单撤销
//  public function cancelOrder(){
//      $datami = trim($_POST['datami']);
//      $this->recordData('消费订单撤销：'.$datami);
//      $datami=json_decode($datami,1);
//      $tradeid=trim($datami['tradeid']);
//      $key=trim($datami['key']);
//      $checkKey=md5($this->keycode.$tradeid);
//      if($checkKey!=$key){
//          returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
//      }
//      if(empty($tradeid)){
//          returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
//      }
//      $trdeWastebooks=M('trade_wastebooks');
//      $map['tradeid']=$tradeid;
//      $map['flag']='0';
//      $tradeinfo=$trdeWastebooks->where($map)
//          ->field("tradeid,tradeamount,tradepoint,cardno,eorderid")->find();
//      if($tradeinfo==false){
//          returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
//      }
//      $cardkind=substr($tradeinfo['cardno'],0,4);
//      if($cardkind=='6688'){
//          //酒店新卡订单撤销
//          $map1=array('flag'=>0,'eorderid'=>$tradeinfo['eorderid'],'tradetype'=>array('in','00,01'));
//          $list=$trdeWastebooks->where($map1)->select();
//          $this->model->startTrans();
//          $count=0;
//          foreach($list as $key=>$val){
//              $map['cardno']=$tradeinfo['cardno'];
//              $cardInfo=$this->cards->where($map)->find();
//              $customid=$cardInfo['customid'];
//              if($val['tradetype']=='00'){
//                  $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='00'";
//                  $balanceIf=$this->account->execute($balanceSql);
//                  $tradeMap=array('tradeid'=>$val['tradeid']);
//                  $tradeData=array('tradetype'=>'04');
//                  $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
//
//                  $map2=array('tradeid'=>$val['tradeid']);
//                  $tradeBillList=M('tradebilling')->where($map2)->find();
//                  if($tradeBillList!=false){
//                      $tradeBillSql="DELETE FROM  TRADEBILL WHERE tradeid='{$val['tradeid']}'";
//                      //$tradeBillSql="UPDATE TRADEBILL SET AMOUNT=NVL(AMOUNT,0)-{$val['tradeamount']},status=0 WHERE tradeid='{$val['tradeid']}'";
//                      $billDetailList=M('billingdetail')->where($map2)->select();
//                      $c=0;
//                      foreach($billDetailList as  $k=>$v){
//                          $billingDetailSql="DELETE FROM BILLINGDETAIL WHERE card_purchaseid='{$v['card_purchaseid']}' and tradeid='{$val['tradeid']}'";
//                          $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)-{$v['amount']},FLAG=0 WHERE card_purchaseid='{$v['card_purchaseid']}'";
//                          $billingDetailIf=$this->model->execute($billingDetailSql);
//                          $billingIf=$this->model->execute($billingDetailIf);
//                          if($billingDetailIf==true&&$billingIf==true){
//                              $c++;
//                          }
//                      }
//                      if($c=count($billDetailList)){
//                          $billIf=true;
//                      }
//                  }else{
//                      $billIf=true;
//                  }
//                  if($tradeIf==true&&$balanceIf==true&&$billIf==true){
//                      $count++;
//                  }
//              }elseif($val['tradetype']=='01'){
//                  $pointSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='04'";
//                  $pointIf=$this->model->execute($pointSql);
//                  $tradeMap=array('tradeid'=>$val['tradeid']);
//                  $tradeData=array('tradetype'=>'11');
//                  $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
//
//                  $map2=array('tradeid'=>$val['tradeid']);
//                  $pointConsumeList=M('point_consume')->where($map2)->select();
//                  $c=0;
//                  foreach($pointConsumeList as $k=>$v){
//                      $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
//                      $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
//                      $pointConsumeIf=$this->model->execute($pointConsumeSql);
//                      $pointAccountIf=$this->model->execute($pointAccountSql);
//                      if($pointConsumeIf==true&&$pointAccountIf==true){
//                          $c++;
//                      }
//                  }
//                  if($c=count($pointConsumeList)){
//                      $pointConsumeIf=true;
//                  }
//                  if($tradeIf==true&&$pointIf==true&&$pointConsumeIf==true){
//                      $count++;
//                  }
//              }
//          }
//          if($count=count($list)){
//              $this->model->commit();
//              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
//          }else{
//              $this->model->rollback();
//              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
//          }
//      }elseif($cardkind=='2336'||$cardkind=='6888'){
//          if(!preg_match('/^6882/',$tradeinfo['cardno'])){
//              $balance=$this->cardAccQuery($tradeinfo['cardno']);
//              if($balance+$tradeinfo['tradeamount']>5000){
//                  returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限，禁止退款'));
//              }
//          }
//          $tradeList=$trdeWastebooks->where(array('eorderid'=>$tradeinfo['eorderid']))
//              ->field("tradeid,tradeamount,cardno,eorderid")->select();
//          $this->model->startTrans();
//          $c=0;
//          //echo $trdeWastebooks->getLastSql();exit;
//          //print_r($tradeList);exit;
//          $string=$trdeWastebooks->getLastSql().'<br/>';
//          foreach($tradeList as $key=>$val){
//              $map['cardno']=$val['cardno'];
//              $cardInfo=$this->cards->where($map)->find();
//              $customid=$cardInfo['customid'];
//              $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$val['tradeamount']." WHERE customid='{$customid}' and type='00'";
//              $balanceIf=$this->account->execute($balanceSql);
//              $tradeMap=array('tradeid'=>$val['tradeid']);
//              $tradeData=array('tradetype'=>'04');
//              $string.="{$balanceSql}<br/>";
//              $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
//              if($tradeIf==true&&$balanceSql==true){
//                  $c++;
//              }
//          }
//          $this->recordData('消费订单撤销sql：'.$string.count($tradeList));
//          if($c==count($tradeList)){
//              $this->model->commit();
//              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
//          }else{
//              $this->model->rollback();
//              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
//          }
//      }else{
//          //正常至尊卡消费撤销
//          if($tradeinfo['tradepoint']>0){
//              returnMsg(array('status'=>'06','codemsg'=>'通宝消费不允许撤销'));
//          }
//          if(!preg_match('/^6882/',$tradeinfo['cardno'])){
//              $balance=$this->cardAccQuery($tradeinfo['cardno']);
//              if($balance+$tradeinfo['tradeamount']>5000){
//                  returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限，禁止退款'));
//              }
//          }
//          $this->model->startTrans();
//          $map['cardno']=$tradeinfo['cardno'];
//          $cardInfo=$this->cards->where($map)->find();
//          $customid=$cardInfo['customid'];
//          if($tradeinfo['tradeamount']!=0){
//              $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='00'";
//              $balanceIf=$this->account->execute($balanceSql);
//          }else{
//              $balanceIf=true;
//          }
//          if($tradeinfo['tradepoint']!=0){
//              $coinSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradepoint']." WHERE customid='{$customid}' and type='01'";
//              $coinIf=$this->account->execute($coinSql);
//              $coinReturnIf=$this->returnCoin($tradeinfo['tradeid']);
//          }else{
//              $coinIf=true;
//              $coinReturnIf=true;
//          }
//          $tradeMap=array('tradeid'=>$tradeinfo['tradeid']);
//          $tradeData=array('tradetype'=>'04');
//          $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
//          if($balanceIf==true&&$coinIf==true&&$tradeIf==true&&$coinReturnIf==true){
//              $this->model->commit();
//              $data = $balanceSql."///<br/>".$coinSql."***********<br/>";
//              $this->recordError($data,'cancelOrder','卡编号_'.$tradeinfo['customid']);
//              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
//          }else{
//              $this->model->rollback();
//              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
//          }
//      }
//  }

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
    //获得单卡片的劵消费信息
    protected function getCardConsumeList($cardno){
        $where1=array('tw.tradetype'=>'02','tw.flag'=>0,'tw.cardno'=>$cardno);
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
  public function getConsumeRecord(){
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami,1);
        $panterid = trim($datami['panterid']);
        $termposno = trim($datami['termposno']);
        $key = trim($datami['key']);
		$panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMSg(array('status'=>'02','codemsg'=>'商户不存在！'));
        }
		//echo $panterid.'---'.$termposno;exit;
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        $currentDate=date('Ymd');
        $deadDate=date('Ymd',strtotime('-7days'));
        $map=array('tw.panterid'=>$panterid,'tw.termposno'=>$termposno);
        $map['tw.tradetype']=array('in','00,01,02,03,06,13,17,21');
        $map['tw.flag']=0;
        $map['placeddate']=array(array('egt',$deadDate),array('elt',$currentDate));
        $tradeWasteBooks=M('trade_wastebooks');
        $field='tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradepoint,tw.cardno,tw.tradetype,tw.quanid,q.quanname,tw.eorderid';
        $tradeList=$tradeWasteBooks->alias('tw')->join('left join quankind q on tw.quanid=q.quanid')
            ->where($map)->field($field)->order('placeddate desc,placedtime desc')->select();
		//echo M('trade_wastebooks')->getLastSql();exit;
        if($tradeList==false){
            returnMSg(array('status'=>'03','codemsg'=>'查无消费记录！'));
        }else{
            $list=array();
            foreach($tradeList as $key=>$val){
                $array=array();
                if(substr($val['cardno'],0,4)=='6688'){
                    $tradetypes=array('01','03','06');
                    if(in_array($val['tradetype'],$tradetypes)){
                        $panter=$this->model->table('cards')->alias('c')->join('panters p on c.panterid=p.panterid')
                            ->where(array('c.cardno'=>$val['cardno']))->field('p.namechinese pname')->find();
                        $array['pname']=$panter['pname'];
                        $array['tradeid']=trim($val['tradeid']);
                        $array['cardno']=$val['cardno'];
                        $array['date']=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'];
                        $where=array('eorderid'=>$val['eorderid'],'tradetype'=>array('in','00,17,21'),'flag'=>0);
                        $amount=$tradeWasteBooks->where($where)->sum('tradeamount');
                        $array['point']=$val['tradeamount'];
                        $array['amount']=$amount;
                        if($val['tradetype']=='01'){
                            $array['tradetype']='至尊卡消费';
                        }elseif($val['tradetype']=='03'){
                            $array['tradetype']='预授权';
                        }elseif($val['tradetype']=='06'){
                            $array['tradetype']='预授权完成';
                        }
                        $array['spec']=1;
                    }else{
                        continue;
                    }
                }else{
                    $array['tradeid']=trim($val['tradeid']);
                    $array['cardno']=$val['cardno'];
                    $array['date']=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'];
                    $array['spec']=0;
                    if($val['tradetype']=='00'){
                        if($val['tradeamount']==0&&$val['tradepoint']>0){
                            $array['tradetype']='通宝兑换';
                            $array['amount']=floatval($val['tradepoint']);
                        }
                        if($val['tradeamount']>0&&$val['tradepoint']==0){
                            $array['tradetype']='卡余额消费';
                            $array['amount']=floatval($val['tradeamount']);
                        }
                        if($val['tradeamount']>0&&$val['tradepoint']==0){
                            $array['tradetype']='至尊卡消费';
                            $array['amount']=floatval($val['tradeamount']);
                        }
                    }else{
                        $array['tradetype']=$jytype[$val['tradetype']];
                        $array['amount']=floatval($val['tradeamount']);
                        if($val['tradetype']=='02'){
                            $array['quanid']=$val['quanid'];
                            $array['quanname']=$val['quanname'];
                        }
                    }
                }
                $list[]=$array;
            }
            returnMSg(array('status'=>'1','list'=>$list));
        }
    }
	//云片网发送短信20160401
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
    public function coinIssue(){
        $data=getPostJson();

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        $this->recordData($data);
        $cardno=trim($data['cardno']);
        $coinAmount=trim($data['coinAmount']);
        $linktel=trim($data['mobile']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $key=trim($data['key']);
        $prefix='soonpay_';
        $checkkey=md5($this->keycode.$linktel.$cardno.$panterid.$coinAmount.$orderid);
        $panter=$this->checkPanter($panterid);
        if(empty($cardno)||empty($linktel)||empty($orderid)){
            returnMsg(array('status'=>'010','codemsg'=>'传入数据不完整'));
        }
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }
        $card=$this->checkCard($cardno);
        $customMap=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $custom=$this->customs->where($customMap)->field('customid')->find();
        if($card==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
//        $issueBool=$this->checkIssue($panterid);
//        if($issueBool==false){
//            returnMsg(array('status'=>'011','codemsg'=>'商户发行金额超限'));
//        }
        $this->model->startTrans();
        if($card['status']=='N'){
            if($custom==false){
                $customArr=array('linktel'=>$linktel,'panterid'=>$panterid);
                $customid=$this->createCustoms($customArr,2);
            }else{
                $customid=$custom['customid'];
            }
            //echo $customid;exit;
            if($customid==false){
                $this->model->rollback();
                returnMsg(array('status'=>'04','codemsg'=>'会员创建失败'));
            }
            $cardArr=array($cardno);
            $bool=$this->openCard($cardArr,$customid,0);
            if($bool==false){
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'新卡开卡失败'));
            }
        }else{
            if($card['status']=='Y'){
                $where=array('c.cardno'=>$cardno);
                $custom=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                    ->join('cards c on cc.cid=c.customid')
                    ->field('cu.namechinese cuname,cu.personid,cu.customid')->where($where)->find();
                $customid=$custom['customid'];
            }else{
                returnMsg(array('status'=>'08','codemsg'=>'非正常卡不能充值'));
            }
        }
        $accountMap=array('sourceorder'=>$prefix.$orderid);
        $issueCounter=M('coin_account')->where($accountMap)->count();
        if($issueCounter>0){
            returnMsg(array('status'=>'09','codemsg'=>'该订单已充值'));
        }
        $accountInfo=$this->getCoinAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡号账户异常'));
        }
        $cardInfo=array('customid'=>$customid,'cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],'accountid'=>$accountInfo['accountid'],'orderid'=>$prefix.$orderid);
        $coinBool=$this->coinRecharge($cardInfo,$coinAmount,$panterid);
        if($coinBool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','customid'=>$customid));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'建业通宝充值失败'));
        }
    }
//-----20160510---修改---
    public function getCustomInfo(){
        $data = trim($_POST['datami']);
		$data = json_decode($data,1);
        $cardno=trim($data['cardno']);
        if(empty($cardno)){
            returnMsg(array('code'=>'01','msg'=>'卡号非法传入'));
        }
        $card=$this->checkCard($cardno);
        if($card['status']=='N'||$card['customid']==0){
            returnMsg(array('code'=>'02','msg'=>'非正常卡号'));
        }
        $map=array('c.cardno'=>$cardno);//customid,namechinese,personid,sex,linktel,residaddress,unitname
        $field='cu.personid,cu.customid,cu.namechinese,cu.sex,cu.linktel,cu.residaddress,cu.unitname';
        $customInfo=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->field($field)->where($map)->select();
        if($customInfo==false){
            returnMsg(array('code'=>'03','msg'=>'查无此卡关联的会员'));
        }else{
            returnMsg(array('code'=>'000','msg'=>$customInfo));
        }
    }
//-------------end---------
    public function getCoinRate(){
        $data = trim($_POST['datami']);
        $data = json_decode($data,1);
        $panterid=trim($data['panterid']);
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'01','codemsg'=>'无效商户号'));
        }
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('tongbao_rate')->find();
        if($panter['tongbao_rate']==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无信息'));
        }else{
            returnMsg(array('status'=>'1','rate'=>floatval($panter['tongbao_rate'])));
        }
    }

    public function checkOrderStatus(){
        $data = trim($_POST['datami']);
        $data = json_decode($data,1);
        $posFlagId=trim($data['posFlagId']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$posFlagId);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('eorderid'=>'pos_'.$posFlagId);
        $list=M('trade_wastebooks')->where($map)->field('tradeid,tradeamount')->find();
        if($list==false){
            returnMsg(array('status'=>'02','msg'=>'查无订单'));
        }else{
            returnMsg(array('status'=>'1','tradeInfo'=>array('tradeid'=>$list['tradeid'],'tradeamount'=>$list['tradeamount'])));
        }
    }

    public function tradeWash(){
        //returnMsg(array('status'=>'05','msg'=>'不能冲正'));
        $data = trim($_POST['datami']);
        $string=$data.'--cz';
        $this->recordData($string);
        $data = json_decode($data,1);
        $posFlagId=trim($data['posFlagId']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$posFlagId);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('eorderid'=>'pos_'.$posFlagId,'tradetype'=>'00');
        $list=M('trade_wastebooks')->where($map)->field('cardno,tradeid,tradeamount')->find();
        if($list==false){
            returnMsg(array('status'=>'02','msg'=>'查无订单'));
        }
        $this->model->startTrans();
        $data=array('tradetype'=>'09');
        $map=array('tradeid'=>$list['tradeid']);
        $this->model->startTrans();
        $twIf=M('trade_wastebooks')->where($map)->save($data);
        $cardno=$list['cardno'];
        $map1=array('c.cardno'=>$cardno,'a.type'=>'00');
        $accountInfo=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->field('a.customid,a.accountid')->where($map1)->find();
        if($accountInfo==false){
            returnMsg(array('status'=>'03','msg'=>'卡号账户异常'));
        }
        $sql="UPDATE ACCOUNT SET AMOUNT=AMOUNT+'{$list['tradeamount']}' WHERE customid='{$accountInfo['customid']}' and type='00'";
        $accountIf=$this->model->execute($sql);
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $wash_logs_sql="innsert into wash_logs values ('{$list['tradeid']}','{$currentTime}','{$cardno}') ";
        $this->model->execute($wash_logs_sql);

        if($accountIf==true&&$twIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','msg'=>'冲正成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'04','msg'=>'冲正失败'));
        }
    }
    //-----------------------------------------------------------------------------------------------------
    public function cancleOrder(){
        $datami  = trim($_POST['datami']);
        $this->writeLog(__FUNCTION__,'get',$datami);
        $post    = json_decode($datami,1);
        $tradeid = trim($post['tradeid']);
        $key     = trim($post['key']);
        $checkKey= md5($this->keycode.$tradeid);

        $this->checkSign($checkKey,$key);
        if(empty($tradeid)){
            returnMsg(array('status'=>'02','codemsg'=>'流水号不能为空'));
        }
        $map['eorderid'] = $this->prefix.$tradeid;
        $map['flag']     = '0';
        $field           = 'tradeid,tradeamount,tradepoint,cardno,customid,tradetype,eorderid,placeddate';
        $orderInfo = $this->valiOrder($map,$field);
        //判定退什么 jycoin or balance
        $method    = $this->cancleMethod(array_column($orderInfo,'tradeamount'),array_column($orderInfo,'tradepoint'));
        if($method==='jycoin'||$method==='balance'){
            if($method==='jycoin'){
                $this->cancleJyCoin($orderInfo);
            }else{
                $this->cancleBalance($orderInfo);
            }

        }else{
            returnMsg(array('status'=>'08','codemsg'=>'退款订单支付方式异常'));
        }


    }
    //验证退款订单
    private function valiOrder($map,$field){
        $info  = M('trade_wastebooks')->where($map)->field($field)->select();
        if($info){
            $tradetype  = array_unique(array_column($info,'tradetype'));
            $date       = array_unique(array_column($info,'placeddate'))[0];
            $date == date('Ymd') ||returnMsg(array('status'=>'07','codemsg'=>'只能退当日订单'));
            if(count($tradetype)==1){
                $type = $tradetype[0];
                if($type=='00'||$type=='04'){
                    if($type=='00') return $info;
                    if($type=='04') returnMsg(array('status'=>'04','codemsg'=>'此单已经退款'));
                }else{
                    returnMsg(array('status'=>'06','codemsg'=>'此单信息异常，请联系至尊'));
                }

            }else{
                returnMsg(array('status'=>'06','codemsg'=>'此单信息异常，请联系至尊'));
            }
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'无此订单支付信息'));
        }
    }
    //
    public function cancleMethod($balance,$jycoin){
        $balance = array_unique($balance);
        $jycoin  = array_unique($jycoin);
        if(array_search('0',$balance)===false){
            return 'balance';
        }
        if(array_search('0',$jycoin)===false){
            return 'jycoin';
        }
    }
    private function cancleJyCoin($orderInfo){
        $count = count($orderInfo);
        $field = 'tw.tradepoint,cc.*,ca.accountid';
        if($count===1){
            $trade['tradeid']   = $orderInfo[0]['tradeid'];
            $coinInfo   = $this->coinConsumeInfo($trade,$field);

            $amount    = $coinInfo[0]['amount'];
            $accountid = $coinInfo[0]['accountid'];
            $coinid    = $coinInfo[0]['coinid'];
            $coin_accountUpdate = "UPDATE coin_account set remindamount=remindamount + {$amount} WHERE coinid='{$coinid}'";
            $accountUpdate      = "UPDATE account set amount=amount + {$amount} where accountid='{$accountid}'";

        }else{
            $trade['tradeid'] = ['in',array_column($orderInfo,'tradeid')];
            $coin_accountUpdate = "UPDATE coin_account set remindamount=remindamount + CASE coinid";
            $accountUpdate      = "UPDATE account set amount=amount + CASE accountid";
            $coinInfo   = $this->coinConsumeInfo($trade,$field);
            foreach ($coinInfo as $val){
                $coin_accountUpdate.= " WHEN '{$val['coinid']}' THEN {$val['amount']}";
                $accountUpdate.= " WHEN '{$val['accountid']}' THEN {$val['amount']}";
            }
            $coinInWhere    = $this->inString(array_column($coinInfo,'coinid'));
            $accountInWhere = $this->inString(array_column($coinInfo,'accountid'));
            $coin_accountUpdate.=" END where coinid in ({$coinInWhere})";
            $accountUpdate.=" END where accountid in ({$accountInWhere})";
        }
        $record = ['accountSql'=>$accountUpdate,'coinAccountSql'=>$coin_accountUpdate,'tradeid'=>$trade];
        $this->writeLog(__FUNCTION__,'jycoinSql',json_encode($record));
        $this->model->startTrans();
        try{
            $tradeIf   = M('trade_wastebooks')->where($trade)->save(['tradetype'=>'04']);
            $consumeIf = M('coin_consume')->where($trade)->delete();
            $coin_accountIf= $this->model->execute($coin_accountUpdate);
            $accountIf = $this->model->execute($accountUpdate);
            if($tradeIf==true&&$consumeIf==true&&$coin_accountIf==true&&$accountIf==true){
                $this->model->commit();
                $this->writeLog(__FUNCTION__,'jycoinSucc',json_encode($record));
                returnMsg(array('status'=>'1','codemsg'=>'退款成功'));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'07','codemsg'=>'数据库写入异常'));
            }

        }catch (\Exception $e){
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>$e->getMessage()));
        }
    }

    private function coinConsumeInfo($map,$field){
       $where['tw.tradeid'] = $map['tradeid'];
       return  M('trade_wastebooks')->alias('tw')
                                ->join('left join coin_consume cc on tw.tradeid=cc.tradeid')
                                ->join('left join coin_account ca on cc.coinid=ca.coinid')
                                ->where($where)->field($field)->select();
    }

    private function cancleBalance($orderInfo){
        $count = count($orderInfo);
        if($count===1){
            $trade['tradeid']   = $orderInfo[0]['tradeid'];
            $accountSql         = "UPDATE account set amount=amount + '{$orderInfo[0]['tradeamount']}' where customid='{$orderInfo[0]['customid']}' and type='00'";
        }else{
            $trade['tradeid']   = ['in',array_column($orderInfo,'tradeid')];
            $accountSql         = "UPDATE account set amount=amount + CASE customid";
            foreach ($orderInfo as $val){
                $accountSql.= " WHEN '{$val['customid']}' THEN {$val['amount']}";
            }
            $accountInWhere = $this->inString(array_column($orderInfo,'customid'));
            $accountSql.=" END where customid in ({$accountInWhere}) and type='00' ";
        }
        $record = ['accountSql'=>$accountSql,'tradeid'=>$trade];
        $this->writeLog(__FUNCTION__,'balanceSql',json_encode($record));
        try{
            $tradeIf   = M('trade_wastebooks')->where($trade)->save(['tradetype'=>'04']);

            $accountIf = $this->model->execute($accountSql);
            if($tradeIf==true&&$accountIf==true){
                $this->model->commit();
                $this->writeLog(__FUNCTION__,'balanceSucc',json_encode($record));
                returnMsg(array('status'=>'1','codemsg'=>'退款成功'));
            }else{
                returnMsg(array('status'=>'07','codemsg'=>'数据库写入异常'));
            }

        }catch (\Exception $e){
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>$e->getMessage()));
        }
    }
    //-----------------------------------------------------------------------------------------------------
    public function getOrderInfo(){
        $datami  = trim($_POST['datami']);
        $this->writeLog(__FUNCTION__,'get',$datami);
        $post    = json_decode($datami,1);

        $orderId = trim($post['orderid']);
        $key     = trim($post['key']);
        $checkKey= md5($this->keycode.$orderId);
        $this->checkSign($checkKey,$key);

        $map['eorderid'] = $this->prefix.$orderId;
        $map['flag']     = '0';
        $info    = M('trade_wastebooks')->where($map)->field('tradetype')->find();
        $flag    = $info['tradetype'];
        if($info){
            if($flag==='00'){
                returnMsg(array('status'=>'1','codemsg'=>'该订单:'.$orderId.'交易成功'));
            }elseif ($flag==='04'){
                returnMsg(array('status'=>'2','codemsg'=>'该订单:'.$orderId.'已经撤销'));
            }else{
                returnMsg(array('status'=>'03','codemsg'=>'该订单:'.$orderId.'错误交易信息'));
            }
        }else{
            returnMsg(array('status'=>'02','codemsg'=>'该订单:'.$orderId.'不存在'));
        }
    }
    public function barcodeConsume(){
        $datami    = trim($_POST['datami']);
        $this->writeLog(__FUNCTION__,'get',$datami);
        $post      = json_decode($datami,1);

        $barcode   = trim($post['barcode']);
        $amount    = trim($post['amount']);
        $panterid  = trim($post['panterid']);//爱便利商户号
        $termno    = trim($post['termno']);//爱便利终端号
        $outorderid= trim($post['outorderid']);
        $sourcecode= trim($post['sourcecode']);
        $key       = trim($post['key']);

        $checkKey  = md5($this->keycode.$barcode.$amount.$panterid.$termno.$outorderid.$sourcecode);
        $this->checkSign($checkKey,$key);
        $panterid == $this->panterid || returnMSg(array('status'=>'02','codemsg'=>'非爱便利商户'));
        $this->valiBarcode($barcode);
        $customid  = $this->barDecode($barcode);
        $this->valiBarcodeCustoms($customid,$amount,$barcode);
        //
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount) || $amount=='0'){
            returnMsg(array('status'=>'06','codemsg'=>'消费金额格式有误'));
        }
        if($amount>300){
            returnMsg(array('status'=>'07','codemsg'=>'条形码支付金额需小于300'));
        }
        $eorderid = $this->prefix.$outorderid;
        $this->vaiaOutorderid($eorderid);
        $termno  = $this->bindTermno($termno);
        //获取可用余额
        $balaceMap = [
            'cc.customid'=>$customid,'ca.cardkind'=>['in',['2336','6886']],
            'ac.type'=>'00','ac.amount'=>['gt','0'],'ca.status'=>'Y'
        ];
        $field     = 'cc.cid as customid,ca.cardno,ac.amount,ac.accountid';
        $balaceInfo= $this->getAccountInfo($balaceMap,$field);
        $time      = time();
        $placeddate= date('Ymd',$time );
        $placedtime= date('H:i:s',$time);
        $tradetime = date('YmdHis',$time);
        // some parameter
        $param['panterid']  = $panterid ;
        $param['tradetime'] = $tradetime;
        $param['placeddate']= $placeddate;
        $param['termno']    = $termno;
        $param['eorderid']  = $eorderid;
        $param['placedtime']= $placedtime;
        //余额够扣款
        if($balaceInfo){
            $sumBalance = array_column($balaceInfo,'amount');
            if($sumBalance>=$amount){
               $balanceSql = $this->balanceConsume($amount,$balaceInfo,$param);
               $tradeSql  = 'INSERT ALL' .$balanceSql['tradeSql']. ' SELECT 1 FROM DUAL';
               $accountSql= 'UPDATE ACCOUNT set amount=amount- CASE accountid '.$balanceSql['accountSql']." END where accountid in ({$this->inString($balanceSql['accountid'])})";

               $record   = ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql];
               $this->writeLog(__FUNCTION__,'balanceSql',json_encode($record));
               $this->model->startTrans();
               try{
                   $tradeIF   = $this->model->execute($tradeSql);
                   $accountSql= $this->model->execute($accountSql);
                   if($tradeIF && $accountSql){
                       $this->changeBarcode($barcode,$amount);
                       $this->writeLog(__FUNCTION__,'balanceSucc',json_encode($record));
                       $this->model->commit();
                       returnMsg(array('status'=>'1','codemsg'=>'消费成功','info'=>array('totalbalance'=>$amount,'totalpoint'=>'0.00','orderid'=>$balanceSql['tradeid'])));
                   }else{
                       returnMsg(array('status'=>'08','codemsg'=>'数据库修改失败'));
                   }
               }catch (\Exception $e){
                   $this->model->rollback();
                   returnMsg(array('status'=>'08','codemsg'=>$e->getMessage()));
               }
            }
        }
        //获取通宝
        $jycoinMap =[
            'cc.customid'=>$customid,'ca.cardkind'=>'6889',
            'ca.cardfee'=>['in',['1',2]],
            'ac.type'=>'01','ac.amount'=>['gt','0'],
            'ca.status'=>'Y'
        ];

        $jycoinInfo=$this->getAccountInfo($jycoinMap,$field);
       //只扣款通宝
        if($jycoinInfo){
            $sumJycoin = array_column($jycoinInfo,'amount');
            if($sumJycoin>=$amount){
                $balanceSql    = $this->jycoinConsume($amount,$jycoinInfo,$param);
                $tradeSql      = 'INSERT ALL' .$balanceSql['tradeSql']. ' SELECT 1 FROM DUAL';
                $accountSql    = 'UPDATE ACCOUNT set amount=amount - CASE accountid '.$balanceSql['accountSql']." END where accountid in ({$this->inString($balanceSql['accountid'])})";
                $coinConsumeSql= "INSERT ALL ".$balanceSql['coinConsumeSql'] .' SELECT 1 FROM DUAL';
                $coinAccountSql= 'UPDATE COIN_ACCOUNT set remindamount=remindamount - CASE coinid ' .$balanceSql['coinAccountSql']." END where coinid in ({$this->inString($balanceSql['coinid'])})";
                $record   = ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql,'coinConsumeSql'=>$coinConsumeSql,'coinAccountSql'=>$coinAccountSql];
                $this->writeLog(__FUNCTION__,'jycoinSql',json_encode($record));
                $this->model->startTrans();
                try{
                    $tradeIF      = $this->model->execute($tradeSql);
                    $accountIf    = $this->model->execute($accountSql);
                    $coinConsumeIf= $this->model->execute($coinConsumeSql);
                    $coinAccountIf= $this->model->execute($coinAccountSql);
                    if($tradeIF && $accountIf && $coinConsumeIf && $coinAccountIf){
                        $this->changeBarcode($barcode,$amount);
                        $this->writeLog(__FUNCTION__,'jycoinSucc',json_encode($record));
                        $this->model->commit();
                        returnMsg(array('status'=>'1','codemsg'=>'消费成功','info'=>array('totalbalance'=>'0.00','totalpoint'=>$amount,'orderid'=>$balanceSql['tradeid'])));
                    }else{
                        returnMsg(array('status'=>'08','codemsg'=>'数据库修改失败'));
                    }
                }catch (\Exception $e){
                    $this->model->rollback();
                    returnMsg(array('status'=>'08','codemsg'=>$e->getMessage()));
                }
            }
        }
        returnMsg(array('status'=>'09','codemsg'=>'账户余额不足'));

    }

    private function checkSign($checkKey,$key){
        if($checkKey===$key){
            return true;
        }else{
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
    }
    private function barDecode($barcode){
        $length       = substr($barcode, 11,1);
        //不含0部分
        $not          = substr($barcode,0,$length);
        $not          = str_split($not);
        $not[0]       = 10-$not[0];

        $customid  = implode($not);
        //零部分
        $zero         = substr($barcode,$length+3,8-$length);
        $zero     = str_split($zero);

        foreach($zero as $v){
            if($v==1){
                $customid='0'.$customid;
            }else{
                $len     =count($customid);
                $customid=substr($customid,0,$v-1).'0'.substr($customid,$v-1);
            }
        }
        return $customid;
    }
    private function valiBarcode($barcode){
      $result = M('barcode')->where(['codeid'=>$barcode])->find();
      if($result){
          $now = substr(time(),-4);
          $it  = substr($barcode,12,4);
          ($now-$it)<=$this->barcodeLifeTime || returnMsg(array('status'=>'03','codemsg'=>'barcode 已过期'));
          if($result['status']=='1') returnMsg(array('status'=>'04','codemsg'=>'barcode 已使用'));
          return true;
      }else{
          returnMsg(array('status'=>'02','codemsg'=>'非法 barcode'));
      }
    }
    private function valiBarcodeCustoms($customid,$amount,$barcode){
       $result = $this->customs->where(['customid'=>$customid])->find();
       if($result){
           //增加消费限额问题 1000
           $map['barcode'] = $barcode;
           $map['customid']= $customid;
           $map['placeddate']= date('Ymd');
           $barcode = M('barcode')->where($map)->find();
           if($barcode){
               $sum['customid']= $customid;
               $sum['placeddate']= date('Ymd');
               $consumed =M('barcode')->where($map)->field('sum(amount) amount')->find()['amount'];
               $total    = bcadd($consumed,$amount,2);
               if($total>$this->barcodeLimitAmount){
                   returnMsg(array('status'=>'021','codemsg'=>'barcode 当天消费总金额不能超过1000'));
               }else{
                   return true;
               }

           }else{
               returnMsg(array('status'=>'020','codemsg'=>'barcode 绑定会员异常'));
           }
           return true;
       }else{
           returnMsg(array('status'=>'05','codemsg'=>'无效会员id'));
       }
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
    private function getAccountInfo($map,$field){
         return M('customs_c')->alias('cc')
             ->join('left join cards ca on ca.customid=cc.cid')
             ->join('left join account ac on ac.customid=cc.cid')
             ->where($map)->field($field)->select();
    }
    //余额消费
    protected function balanceConsume($amount,$balaceInfo,$map){
        $tradeSql  = '';
        $accountSql= '';
        $consume   = $amount;
        $panterid  = $map['panterid'];
        $tradetime = $map['tradetime'];
        $placeddate= $map['placeddate'];
        $termno    = $map['termno'];
        $eorderid  = $map['eorderid'];
        $placedtime= $map['placedtime'];
        foreach ($balaceInfo as $val){
            $val['accountid'] = trim($val['accountid']);
            if($consume>0){
                if($consume>=$val['amount']){
                    $consume = bcsub($consume,$val['amount'],2);
                    $tradeid = substr($termno,-4).substr($val['cardno'],-5).$tradetime;
                    $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                    $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                    $accountSql.=" WHEN '{$val['accountid']}' THEN {$val['amount']}";
                    $accountid[] = $val['accountid'];

                }else{
                    if($consume==0) break;
                    $tradeid = substr($termno,-4).substr($val['cardno'],-5).$tradetime;
                    $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                    $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$consume}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";
                    $accountSql.=" WHEN '{$val['accountid']}' THEN {$consume}";
                    $consume = 0;
                    $accountid[] = $val['accountid'];
                }

            }
        }
        return ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql,'accountid'=>$accountid,'tradeid'=>$tradeid];
    }
    //通宝消费
    private function jycoinConsume($amount,$jycoinInfo,$map){
        $tradeSql  = '';
        $accountSql= '';
        $coinAccountSql = "";
        $coinConsumeSql = "";
        $consume   = $amount;
        $panterid  = $map['panterid'];
        $tradetime = $map['tradetime'];
        $placeddate= $map['placeddate'];
        $termno    = $map['termno'];
        $eorderid  = $map['eorderid'];
        $placedtime= $map['placedtime'];
        foreach ($jycoinInfo as $val){
            if($consume>0){
                $val['accountid'] = trim($val['accountid']);
                if($consume>=$val['amount']){
                    //通宝可能多账户
                    $coinInfo= M('coin_account')->where(['accountid'=>trim($val['accountid']),'remindamount'=>['gt','0']])->select();
                    foreach ($coinInfo as  $v){
                        $tradeid = substr($termno,-4).substr($v['coinid'],-5).$tradetime;
                        $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                        $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$v['remindamount']}','{$val['customid']}','{$val['cardno']}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                        $coinAccountSql.= " WHEN {$v['coinid']} THEN {$v['remindamount']}";

                        $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                        $coinConsumeSql.=" into coin_consume values('{$coinconsumeid}','{$tradeid}','{$v['cardid']}',";
                        $coinConsumeSql.="'{$v['coinid']}','{$v['remindamount']}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";

                        $coinid[] = $v['coinid'];
                    }
                    $accountSql.=" WHEN '{$val['accountid']}' THEN {$val['amount']}";
                    $accountid[] = trim($val['accountid']);
                    $consume = bcsub($consume,$val['amount'],2);

                }else{
                    if($consume==0) break;
                    $accountSql.=" WHEN '{$val['accountid']}' THEN {$consume}";
                    $accountid[] = trim($val['accountid']);
                    $coinInfo= M('coin_account')->where(['accountid'=>trim($val['accountid']),'remindamount'=>['gt','0']])->select();
                    foreach ($coinInfo as $v){
                        if($v['remindamount']<=$consume){
                            $tradeid = substr($termno,-4).substr($v['coinid'],-5).$tradetime;
                            $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                            $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$v['remindamount']}','{$val['customid']}','{$val['cardno']}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                            $coinAccountSql.= " WHEN '{$v['coinid']}' THEN {$v['remindamount']}";

                            $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                            $coinConsumeSql.=" into coin_consume values('{$coinconsumeid}','{$tradeid}','{$v['cardid']}',";
                            $coinConsumeSql.="'{$v['coinid']}','{$v['remindamount']}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";

                            $coinid[] = $v['coinid'];
                            $consume  =bcsub($consume,$v['remindamount'],2);
                        }else{
                            if($consume==0) break;
                            $tradeid = substr($termno,-4).substr($v['coinid'],-5).$tradetime;
                            $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                            $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$consume}','{$val['customid']}','{$val['cardno']}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                            $coinAccountSql.= "  WHEN '{$v['coinid']}' THEN {$consume}";

                            $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                            $coinConsumeSql.=" into coin_consume values('{$coinconsumeid}','{$tradeid}','{$v['cardid']}',";
                            $coinConsumeSql.="'{$v['coinid']}','{$consume}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";

                            $coinid[] = $v['coinid'];
                            $consume = 0;
                        }

                    }
                }

            }
        }
        return ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql,'accountid'=>$accountid,
                'coinAccountSql'=>$coinAccountSql,'coinConsumeSql'=>$coinConsumeSql,
                'coinid'=>$coinid,'tradeid'=>$tradeid
        ];
    }
    //消费成功 更改barcode 状态
    private function changeBarcode($barcode,$amount){
        $result = M('barcode')->where(['codeid'=>$barcode])->save(['status'=>'1','amount'=>$amount]);
        if($result){
            return true;
        }else{
            returnMsg(array('status'=>'07','codemsg'=>' change barcode  status false'));
        }
    }

    //----------------------------------------------------------------------------------卡消费---------------------------------
    public function consume(){
        //$returnUrl='http://kabao.9617777.com/poscreditapi.php?act=sendTemp';
        $post          = trim($_POST['datami']);
        $this->writeLog(__FUNCTION__,'get',$post);
        $post          = json_decode($post,1);
        $cardno        = trim($post['cardno']);
        $balanceAmount = trim($post['totalbalance']);
        $coinAmount    = trim($post['totalpoint']);
        $cardpwd       = trim($post['pwd']);
        $panterid      = trim($post['panterid']);//爱便利商户号
        $termno        = trim($post['termno']);//爱便利终端号
        $outorderid    = trim($post['outorderid']);
        $sourcecode    = trim($post['sourcecode']);
        $key           = trim($post['key']);

        $checkKey=md5($this->keycode.$cardno.$balanceAmount.$coinAmount.$cardpwd.$panterid.$termno.$outorderid.$sourcecode);
//        var_dump($checkKey);exit;

        $this->checkSign($checkKey,$key);

        $panterid == $this->panterid || returnMSg(array('status'=>'02','codemsg'=>'非爱便利商户'));
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'05','codemsg'=>'通宝格式有误'));
        }
        if($balanceAmount==0&&$coinAmount==0){
            returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
        }
        if($balanceAmount!=0 && $coinAmount!=0){
            returnMsg(array('status'=>'07','codemsg'=>'暂不支持组合支付'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'03','codemsg'=>'卡号缺失'));
        }
        $eorderid   = $this->prefix.$outorderid;
        $this->vaiaOutorderid($eorderid);
        $termno  = $this->bindTermno($termno);
        $cardno  = $this->decodeCard($cardno);
        empty($cardpwd) && returnMSg(array('status'=>'012','codemsg'=>'卡密码为空'));
        $time       = time();
        $placeddate = date('Ymd',$time);
        $placedtime = date('H:i:s',$time);
        $tradetime  = date('YmdHis');
        $tradeSql   = "";

        if($coinAmount>0){
            $coinAccountSql = '';
            $coinConsumeSql = '';
            $cardInfo = $this->valiCard('jycoin',$cardno);
            $this->valiCardpwd($cardpwd,$cardInfo['cardpassword'],$cardno,$cardInfo['cardflag']);
            $jycoin   = $this->cardAccount('01',$cardInfo['customid']);
            $jycoin>=$coinAmount || returnMsg(array('status'=>'14','codemsg'=>'此卡通宝不足'));
            $accountSql="update account set amount=amount-{$coinAmount} where customid='{$cardInfo['customid']}' and type='01'";


            $coinInfo= M('coin_account')->where(['cardid'=>$cardInfo['customid'],'remindamount'=>['gt','0']])->select();
            if(!$coinInfo) returnMsg(array('status'=>'15','codemsg'=>'通宝账户异常'));
            $consume = $coinAmount;
            foreach ($coinInfo as $v){
                if($v['remindamount']<=$consume){
                    $tradeid = substr($termno,-4).substr($v['coinid'],-5).$tradetime;
                    $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                    $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$v['remindamount']}','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                    $coinAccountSql.= " WHEN '{$v['coinid']}' THEN {$v['remindamount']}";

                    $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                    $coinConsumeSql.=" into coin_consume values('{$coinconsumeid}','{$tradeid}','{$v['cardid']}',";
                    $coinConsumeSql.="'{$v['coinid']}','{$v['remindamount']}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";

                    $coinid[] = $v['coinid'];
                    $consume  =bcsub($consume,$v['remindamount'],2);
                }else{
                    if($consume==0) break;
                    $tradeid = substr($termno,-4).substr($v['coinid'],-5).$tradetime;
                    $tradeSql.= " into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
                    $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$consume}','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

                    $coinAccountSql.= " WHEN '{$v['coinid']}' THEN {$consume}";

                    $coinconsumeid=$this->getFieldNextNumber('coinconsumeid');
                    $coinConsumeSql.=" into coin_consume values('{$coinconsumeid}','{$tradeid}','{$v['cardid']}',";
                    $coinConsumeSql.="'{$v['coinid']}','{$consume}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'".time()."','0000000000000000')";

                    $coinid[] = $v['coinid'];
                    $consume = 0;
                }
            }
            $tradeSql      = 'INSERT ALL' .$tradeSql. ' SELECT 1 FROM DUAL';
            $coinConsumeSql= "INSERT ALL ".$coinConsumeSql .' SELECT 1 FROM DUAL';
            $coinAccountSql= 'UPDATE COIN_ACCOUNT set remindamount=remindamount- CASE coinid ' .$coinAccountSql." END where coinid in ({$this->inString($coinid)})";
            $record   = ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql,'coinConsumeSql'=>$coinConsumeSql,'coinAccountSql'=>$coinAccountSql];
            $this->writeLog(__FUNCTION__,'jycoinSql',json_encode($record));
            $this->model->startTrans();
            try{
                $tradeIF      = $this->model->execute($tradeSql);
                $accountIf    = $this->model->execute($accountSql);
                $coinConsumeIf= $this->model->execute($coinConsumeSql);
                $coinAccountIf= $this->model->execute($coinAccountSql);
                if($tradeIF && $accountIf && $coinConsumeIf && $coinAccountIf){
                    $this->model->commit();
                    $this->writeLog(__FUNCTION__,'jycoinSucc',json_encode($record));
                    returnMsg(array('status'=>'1','codemsg'=>'消费成功','info'=>array('totalbalance'=>'0.00','totalpoint'=>$coinAmount,'orderid'=>$tradeid)));
                }else{
                    returnMsg(array('status'=>'016','codemsg'=>'数据库修改失败'));
                }
            }catch (\Exception $e){
                $this->model->rollback();
                returnMsg(array('status'=>'016','codemsg'=>$e->getMessage()));
            }


        }

        if($balanceAmount>0){
            $cardInfo = $this->valiCard('balance',$cardno);
            $this->valiCardpwd($cardpwd,$cardInfo['cardpassword'],$cardno,$cardInfo['cardflag']);
            $balance  = $this->cardAccount('00',$cardInfo['customid']);
            $balance>=$balanceAmount ||  returnMsg(array('status'=>'014','codemsg'=>'此卡余额不足'));
            $tradeid = substr($termno,-4).substr($cardno,-5).$tradetime;

            $accountSql="update account set amount=amount-{$balanceAmount} where customid='{$cardInfo['customid']}' and type='00'";

            $tradeSql.= " insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid) ";
            $tradeSql.= " values ('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','0','{$coinAmount}','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0','{$eorderid}')";

            $record   = ['tradeSql'=>$tradeSql,'accountSql'=>$accountSql];
            $this->writeLog(__FUNCTION__,'balanceSql',json_encode($record));
            $this->model->startTrans();
            try{
                $tradeIF   = $this->model->execute($tradeSql);
                $accountSql= $this->model->execute($accountSql);
                if($tradeIF && $accountSql){
                    $this->model->commit();
                    $this->writeLog(__FUNCTION__,'balanceSucc',json_encode($record));
                    returnMsg(array('status'=>'1','codemsg'=>'消费成功','info'=>array('totalbalance'=>$balanceAmount,'totalpoint'=>'0.00','orderid'=>$tradeid)));
                }else{
                    returnMsg(array('status'=>'016','codemsg'=>'数据库修改失败'));
                }
            }catch (\Exception $e){
                $this->model->rollback();
                returnMsg(array('status'=>'016','codemsg'=>$e->getMessage()));
            }
        }

        $this->model->startTrans();

    }
    //卡号解密
    private function decodeCard($cardno){
        $even    = str_split(substr($cardno,-9));
        $odd     = str_split(substr($cardno,0,10));
        $str     = '';
        for ($i=0; $i <9 ; $i++) {
            $str  .= $odd[$i].$even[$i];
        }
        $str    .= $odd[$i];
        $num  = str_split(strrev($str));

        $end     = $num[0];
        $num[0]  = $num[18];
        $num[18] = $end;
        $cardno  = implode($num);
        return $cardno;

    }

    private function valiCard($type,$cardno){
        $map['cardno'] = $cardno;
        $field         = 'cardno,customid,status,cardkind,cardfee,cardpassword,cardflag,exdate';
        $result        = M('cards')->where($map)->field($field)->find();
        if($result){
           $result['status']=='Y'|| returnMsg(array('status'=>'09','codemsg'=>'不是正常卡'));
           if($type==='jycoin'){
               if($result['cardkind']=='6889' && $result['cardfee']=='1'){
                   return $result;
               }else{
                   returnMsg(array('status'=>'010','codemsg'=>'不是通宝实体卡'));
               }
           }else{
               if(!in_array($result['cardkind'],['2336','6886'])) {
                   returnMsg(array('status' => '011', 'codemsg' => '不是至尊余额卡,不能在爱便利消费'));
               }else{
                   $result['exdate']>=date('Ymd')||returnMsg(array('status' => '012', 'codemsg' => '此卡已过期'));
                   return $result;
               }
           }
        }else{
            returnMsg(array('status'=>'08','codemsg'=>'查无此卡'));
        }
    }
    private function valiCardpwd($pwd,$cardPwd,$cardno,$cardflag){
        $cardPwd = substr($this->des->doDecrypt($cardPwd),0,6);
        $cardPwd = md5(md5($cardPwd));
        if($pwd!==$cardPwd){
            if($cardflag<2){
                $sql="UPDATE CARDS SET cardflag=nvl(cardflag,0)+1 WHERE CARDNO='{$cardno}'";
            }else{
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG + 1,status='G' WHERE CARDNO='{$cardno}'";
            }
            $this->cards->execute($sql);
            returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
        }else{
            if($cardflag>0){
                $sql="UPDATE CARDS SET cardflag = 0 WHERE CARDNO='{$cardno}'";
                $this->cards->execute($sql);
                return true;
            }
        }
    }
    //查询单卡余额,通宝
    private function cardAccount($type,$customid){
        $map['type']     = $type;
        $map['customid'] = $customid;
        return M('account')->where($map)->field('nvl(amount,0) amount')->find()['amount'];
    }
    //
    private function vaiaOutorderid($eorderid){
        $result = M('trade_wastebooks')->where(['eorderid'=>$eorderid])->find();
        if(!$result){
            return true;
        }else{
            returnMsg(array('status'=>'017','codemsg'=>'此订单已经付款'));
        }
    }
    private function writeLog($method,$type,$data){
        $path    = PUBLIC_PATH.'Pos/interface/abianli/'.date('Ymd').'/';
        $filename= $path.$method.'.log';
        $this->getDir($filename);
        $str     = date('Y-m-d H:i:s').'*******'.$type.'-----'.$data."\n\t";
        file_put_contents($filename,$str,FILE_APPEND);
    }
    private function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
    //abl 终端绑定
    public function bindTermno($termno){
        $result = M('abl_termno')->where(['abltermno'=>$termno])->find();
        if($result){
            return $result['termno'];
        }else{
            $id=$this->getnextcode('terminals',8);
            $zzktermno=substr($this->panterid,5,3).substr($id,3,5);
            $sql   = "insert into abl_termno values ('{$termno}','{$zzktermno}') ";
            try{
                $insert  = $this->model->execute($sql);
                if($insert){
                    return $zzktermno;
                }else{
                    returnMsg(array('status'=>'019','codemsg'=>'新增终端失败'));
                }
            }catch (\Exception $e){
                returnMsg(array('status'=>'019','codemsg'=>$e->getMessage()));
            }
        }
    }
  }
