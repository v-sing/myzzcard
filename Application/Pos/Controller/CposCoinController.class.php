<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Think\Common;
class CposCoinController extends CoinController{

  //查询账户信息接口(通过会员卡)
   public function getAccount(){
     $datami = trim($_POST['datami']);
     $this->recordData($datami);
       $datami = json_decode($datami,1);
     $cardno=trim($datami['cardno']);
       $panterid=trim($datami['panterid']);

     $key=trim($datami['key']);
     $checkKey=md5($this->keycode.$cardno.$panterid);
     if($checkKey!=$key){
         $this->recoreIp();
         returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
     }
       if(preg_match('/^\d+=\d+$/',$cardno)){
           $info=explode('=',$cardno);
           $cardno=$info[0];
       }
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

       if(in_array($card['cardkind'],array('2336','6888','6886'))){
           $bmoney=$this->zzAccountQuery($customid,'00');
           #start 
           $zmoney = $this->zzkaccount($customid);//自有资金
           $balance = $bmoney + $zmoney['money'];
           $this->recordError('备付金：'.$bmoney.'  自有资金：'.serialize($zmoney)."\n\t","Ctbxfdx","cs");
           #end
       }else{
           $balance=$this->cardAccQuery($cardno);
       }
       $coinAmount=$this->getCoinByCardno($cardno);
      //------获取账户下所有卡数量
      //$tickketList=$this->getTicketByCustomid($customid);
       $ticketList=$this->getTicketByCardno($cardno,$panterid);
      if($ticketList == false || empty($ticketList)){
        $totaltickket = 0;
      }else{
        $totaltickket = array_sum(array_column($ticketList,'amount'));
      }
      //--------end
      returnMsg(array('status'=>'1','balance'=>floatval($balance),
          'jycoin'=>floatval($coinAmount),'totaltickket'=>$totaltickket,'customid'=>$customid,'time'=>time()));
  }

  //营销劵查询(通过卡号查询)
  public function ticketsQuery(){
      $datami = trim($_POST['datami']);
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $cardno = trim($datami['cardno']);
      $panterid=trim($datami['panterid']);
      $key = trim($datami['key']);
      $checkKey = md5($this->keycode.$cardno.$panterid);
      if($checkKey != $key){
          returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
      }
      if(preg_match('/^\d+=\d+$/',$cardno)){
          $info=explode('=',$cardno);
          $cardno=$info[0];
      }
      //$cardno = decode($cardno);
      $map = array('cardno'=>$cardno);
      $card = $this->cards->where($map)->find();
      if($card == false){
          returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
      }
      $customid = $this->getCustomid($cardno);
      if($customid == false || empty($customid)){
          $this->recordData($cardno);
          returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
      }
      $tickketList=$this->getTicketByCardno($cardno,$panterid);
      $oldtickketList = $this->getCardConsumeList($cardno);
        $newticklist = array();
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

  //券消费通过会员卡号
  public function ticketConsume(){
      $datami = trim($_POST['datami']);
      $this->recordData($datami,1);
      $datami = json_decode($datami,1);
      $cardno = trim($datami['cardno']);
      $quanid = trim($datami['quanid']);
      $amount = trim($datami['amount']);
      $panterid = trim($datami['panterid']);
      $termposno = trim($datami['termposno']);//终端号
      $accountid=trim($datami['accountid']);
      $key = trim($datami['key']);
      $cardpwd = trim($datami['cardpwd']);
      $nocard = trim($datami['nocard']);
      $type=trim($datami['type']);


//      $cardno = '6888371900000028850';
//      $quanid = '00000014';
//      $amount = 1;
//      $panterid = '00000678';
//      $termposno = '00000000';
//      $accountid='0000003791';
      if(empty($cardno)){
          returnMsg(array('status'=>'02','codemsg'=>'会员卡号缺失'));
      }
      $b=$this->checkCardValidate($cardno);
      if($b==false){
          returnMsg(array('status'=>'010','codemsg'=>'卡已过期'));
      }
      if(empty($quanid)){
          returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
      }
      if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
          returnMsg(array('status'=>'04','codemsg'=>'消费金额格式错误'));
      }
      if($key != md5($this->keycode.$cardno)){
         returnMsg(array('status'=>'10','codemsg'=>'无效秘钥'));
      }
      $panter=$this->checkPanter($panterid);
      if($panter==false){
          returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
      }

      $map = array('cardno'=>$cardno);
      $card = $this->cards->where($map)->field('customid,cardpassword,status,panterid')->find();
      if($card == false){
          returnMsg(array('status'=>'01','codemsg'=>'非法卡号'));
      }
      if($card['status']!='Y'){
          returnMsg(array('status'=>'012','codemsg'=>'非正常卡号'));
      }
      if($type!=1){
          if($card['cardpassword']!=$cardpwd&&$nocard==''){
              returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
          }
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
          $where=array('a.type'=>'02','a.quanid'=>$quanid,'c.cardno'=>$cardno,'a.amount'=>array('gt',0));
          $quanAmount=$this->cards->alias('c')->join('account a on a.customid=c.customid')->where($where)->sum('a.amount');
          if($quanAmount<$amount){
              returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
          }
          $this->model->startTrans();
          $consumeIf=$this->ticketExeByCardno($cardno,$quanid,$amount,$panterid,$termposno);
          if($consumeIf==true){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradidlist'=>array($consumeIf),'time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'09','codemsg'=>'营销劵消费失败'));
          }
      }elseif($quankind['atype']==2){
          if(empty($accountid)){
              returnMsg(array('status'=>'010','codemsg'=>'劵消费账户缺失'));
          }
          $where=array('qa.quanid'=>$quanid,'c.cardno'=>$cardno,'qa.accountid'=>$accountid);
          $quanList=$this->cards->alias('c')->join('quan_account qa on qa.customid=c.customid')
              ->where($where)->field('qa.*')->find();
          if($quanList['enddate']<date('Ymd')){
              returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
          }
          if($quanList['amount']<$amount){
              returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
          }
          $this->model->startTrans();
          $consumeIf=$this->newTicketExeByCardno($cardno,$quanList,$amount,$panterid,$termposno);
          if($consumeIf==true){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradidlist'=>array($consumeIf),'time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'09','codemsg'=>'营销劵消费失败'));
          }
      }
  }
    //通宝和卡余额消费扣款接口
    public function consume(){
        //$returnUrl='http://kabao.9617777.com/poscreditapi.php?act=sendTemp';
        $datami = trim($_POST['datami']);
        // $datami = '{"cardno":"6888371600000015356","balanceAmount":"0.01","coinAmount":"0","panterid":"00000126","cardpwd":"BFD0C613A11BF88E","posFlagId":"2e7f9f47-0aee-4e73-8ace-b6cef55330c6","termposno":"12601256","key":"15cb7c7e16b3c9fecd4887022f6136c1"}';
        $this->recordError(date("H:i:s") . "-POS刷卡支付记录起始\n\t" . $datami."\n\t","Ctbxfdx","info");
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        // print_r($datami);exit;
        //$datami=json_decode('{"posFlagId":"0497e74f-4f90-4fb8-b133-65388a888056","panterid":"00000126","balanceAmount":"120.00","coinAmount":"0","termposno":"12600671","cardno":"2336379900822631340","cardpwd":"BFD0C613A11BF88E","key":"05531c4ceec103c26f26c5d6cc9fc160"}',1);
        $cardno = trim($datami['cardno']);
        $balanceAmount = trim($datami['balanceAmount']);
        $coinAmount = trim($datami['coinAmount']);
        $cardpwd = trim($datami['cardpwd']);
        $key = trim($datami['key']);
        $panterid = trim($datami['panterid']);//商户号
        $termposno = trim($datami['termposno']);//终端号
        $posFlagId=trim($datami['posFlagId']);
        $date=date('Ymd',time());
        $checkKey=md5($this->keycode.$cardno.$panterid.$termposno.$posFlagId);
        $panter=$this->checkPanter($panterid);
        $safety = M('safety_monitoring');
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        #start
        if(empty($cardno)) {
            returnMSg(array('status'=>'0','codemsg'=>'卡号必传！'));
        }
        #end
        if($panter==false){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'商户：'.$panter.'商户不存在！','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','商户:".$panter."商户不存在!','04')");
            returnMSg(array('status'=>'01','codemsg'=>'商户不存在！'));
        }
        if($checkKey!=$key){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'秘钥：'.$key.'无效秘钥，非法传入！','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','秘钥:".$key."无效秘钥，非法传入!','04')");
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        empty($cardpwd) && returnMSg(array('status'=>'12','codemsg'=>'卡密码为空'));
        if(preg_match('/^\d+=\d+$/',$cardno)){
            $info=explode('=',$cardno);
            $cardno=$info[0];
        }
        if(empty($cardno)){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡号缺失','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."卡号缺失!','04')");
            returnMsg(array('status'=>'03','codemsg'=>'卡号缺失'));
        }
        $b=$this->checkCardValidate($cardno);

        if($b==false){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡已过期','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."卡已过期!','04')");
            returnMsg(array('status'=>'07','codemsg'=>'卡已过期'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费金额:'.$balanceAmount.'消费金额格式有误','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额::".$balanceAmount."消费金额格式有误!','04'))");
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费金额:'.$coinAmount.'通宝格式有误','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额::".$coinAmount."通宝格式有误!','04')");
            returnMsg(array('status'=>'05','codemsg'=>'通宝格式有误'));
        }
        if($balanceAmount==0&&$coinAmount==0){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费金额:'.$balanceAmount.'消费通宝:'.$coinAmount.'消费数据有误','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额:".$balanceAmount."消费通宝:".$coinAmount."消费数据有误!','04')");
            returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
        }
        if($panterid=='00000299'){
            if($balanceAmount>0) {

                $limitBool=$this->checkTradeLimited($cardno,$balanceAmount,$panterid);
                if($limitBool==false){
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费受限','');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额:".$balanceAmount."消费受限!','04')");
                    returnMsg(array('status'=>'011','codemsg'=>'消费受限'));
                }
                if (empty($posFlagId)) {
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'pos单号：'.$posFlagId.'pos单号缺失','');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','pos单号:".$posFlagId."消费数据有误!','04')");
                    returnMsg(array('status' => '014', 'codemsg' => 'pos单号缺失'));
                }
            }
        }
        $map = array('cardno'=>$cardno);
        $card = $this->cards->where($map)->field('customid,cardpassword,status,dcflag,panterid,cardkind,cardflag')->find();
        if($panter['parent']=='00002313'){
            $card_panter=M('panters')->where(array('panterid'=>$card['panterid']))->find();
            if($card_panter['parent']!='00002313'){
                returnMsg(array('status'=>'017','codemsg'=>'非生态新城卡不能再此消费'));
            }
        }else{
            $card_panter=M('panters')->where(array('panterid'=>$card['panterid']))->find();
            if($card_panter['parent']=='00002313'){
                returnMsg(array('status'=>'018','codemsg'=>'生态新城卡不能再此消费'));
            }
        }
        if($card == false){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'非法卡号','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."非法卡号!','04')");
            returnMsg(array('status'=>'01','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            if($card['status']=='J'){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡状态：'.$card['status'].'卡已被锁定','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡状态:".$card['status']."卡已被锁定!','04')");
                returnMsg(array('status'=>'015','codemsg'=>'卡已被锁定'));
            }elseif($card['status']=='A'){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡状态：'.$card['status'].'待激活卡，请先激活后才能消费','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡状态:".$card['status']."待激活卡，请先激活后才能消费!','04')");
                returnMsg(array('status'=>'016','codemsg'=>'待激活卡，请先激活后才能消费'));
            }else{
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'非正常卡号','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."非正常卡号!','04')");
                returnMsg(array('status'=>'012','codemsg'=>'非正常卡号'));
            }
        }
        if($card['cardpassword']!=$cardpwd){
            if($card['cardflag']<2){
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1 WHERE CARDNO='{$cardno}'";
            }else{
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1,status='G' WHERE CARDNO='{$cardno}'";
            }
            $this->cards->execute($sql);
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡号密码错误','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."卡号密码错误!','04')");
            returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
        }
        if(in_array($card['cardkind'],array('2336','6888'))){
            $customid = $this->getCustomid($cardno);
            $bmoney=$this->zzAccountQuery($customid,'00');
            #start
            $zmoney = $this->zzkaccount($customid);//自有资金
            $balance = $bmoney + $zmoney['money'];
            $this->recordError('备付金：'.$bmoney.'  自有资金：'.serialize($zmoney)."\n\t","Ctbxfdx","info");
            #end
        }else{
            $balance=$this->cardAccQuery($cardno);
        }
        if($balance<$balanceAmount){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费金额：'.$balanceAmount.'账户金额'.$balance.'账户金额不足','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额:".$balanceAmount."账户金额".$balance."账户金额不足!','04')");
            returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));
        }
        // $coinAccount=$this->accountQuery($customid,'01');
        if(!in_array($card['cardkind'],array('6882','2081'))){
            $customid = $this->getCustomid($cardno);
            $customif=$this->customs->where(array('customid'=>$customid))->find();
            if(empty($customif['namechinese'])||empty($customif['personid'])){
                if($balanceAmount > 1000){
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额：".$balanceAmount."非实名用户消费金额不得大于1000元','04')");
                    returnMsg(array('status'=>'0222','codemsg'=>'非实名用户消费金额不得大于1000元'));
                }
            }
        }
        #start 允许过5000
        // if($balanceAmount>5000){
        //     $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费金额：".$balanceAmount."消费金额不得大于5000元','04')");
        //     returnMsg(array('status'=>'0111','codemsg'=>'消费金额不得大于5000元'));
        // }
        #end
        $coinAccount=$this->getCoinByCardno($cardno);
        if($coinAccount<$coinAmount){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费通宝金额：'.$coinAmount.'通宝金额'.$coinAccount.'通宝金额不足','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','消费通宝金额:".$coinAmount."通宝金额".$coinAccount."通宝金额不足!','04')");
            returnMsg(array('status'=>'010','codemsg'=>'通宝余额不足'));
        }

        $this->model->startTrans();
        $balanceConsumeIf=$coinConsumeIf=false;

        //余额消费，金额为0不执行
        if($balanceAmount>0){
            $limitIf=$this->checkPanterLimit($card['panterid'],$panterid);
            if($limitIf==='02'){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'酒店卡不能再非酒店商户消费','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡号:".$cardno."酒店卡不能再非酒店商户消费!','04')");
                returnMsg(array('status'=>'017','codemsg'=>'酒店卡不能再在非酒店商户消费'));
            }
            $consumeLimit=M('panter_con_account')->where(array('panterid'=>$panterid))->find();
            if($consumeLimit!=false){
                //消费金额超过商户单笔消费限制
                if($consumeLimit['d_one_account']!=0){
                    if($balanceAmount>=$consumeLimit['d_one_account']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费金额：'.$balanceAmount.'商户单笔消费限制：'.$consumeLimit['d_one_account'].'单笔消费超限','');
                        $safety->execute("insert into safety_monitoring values('{$consumeLimit['panterid']}','{$date}','{$ptime}','卡消费','卡号:".$cardno."消费金额:".$balanceAmount."商户单笔消费限制：".$consumeLimit['d_one_account']."单笔消费超限!','02')");
                        returnMsg(array('status'=>'018','codemsg'=>'单笔消费超限'));
                    }
                }
                $consumeCon=array('panterid'=>$panterid,'placeddate'=>date('Ymd'),'tradeamount'=>array('gt',0),'tradetype'=>'00');
                $c=M('trade_wastebooks')->where($consumeCon)->count();
                $sum=M('trade_wastebooks')->where($consumeCon)->sum('tradeamount');
                //商户交易次数超限
                if($consumeLimit['d_sum_number']!=0){
                    if(($c+1)>=$consumeLimit['d_sum_number']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'商户日交易次数：'.$c.'商户日交易次数限制：'.$consumeLimit['d_sum_number'].'商户日交易次数超限','');
                        $safety->execute("insert into safety_monitoring values('{$consumeLimit['panterid']}','{$date}','{$ptime}','卡消费','卡号:".$cardno."商户日交易次数:".$c."商户日交易次数限制".$consumeLimit['d_sum_number']."商户日交易次数超限!','02')");
                        returnMsg(array('status'=>'019','codemsg'=>'商户日交易次数超限'));
                    }
                }

                //商户交易金额超限
                if($consumeLimit['d_sum_account']!=0){
                    if(bcadd($sum,$balanceAmount,2)>=$consumeLimit['d_sum_account']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'商户日交易金额：'.$sum.'商户日交易金额限制：'.$consumeLimit['d_sum_account'].'商户日交易金额超限','');
                        $safety->execute("insert into safety_monitoring values('{$consumeLimit['panterid']}','{$date}','{$ptime}','卡消费','卡号:".$cardno."商户日交易金额:".$sum."商户日交易金额限制：".$consumeLimit['d_sum_account']."商户日交易金额超限!','02')");
                        returnMsg(array('status'=>'020','codemsg'=>'商户日交易金额超限'));
                    }
                }
            }
            $customids =$card['customid'] ;
            //客户限额消费
            $conLimit=M('custom_con_account')->where(array('customid'=>$customids))->find();
            if($conLimit!=false){
                //卡单笔消费限制
                if($conLimit['oneaccount']!=0){
                    if($balanceAmount>=$conLimit['oneaccount']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡单笔交易金额：'.$balanceAmount.'卡单笔消费限制：'.$conLimit['oneaccount'].'单笔卡消费超限','');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡消费','卡单笔交易金额:".$balanceAmount."卡单笔消费限制:".$conLimit['oneaccount']."单笔卡消费超限!','04')");
                        returnMsg(array('status'=>'021','codemsg'=>'单笔卡消费超限'));
                    }
                }
                $consumeCon=array('customid'=>$customids,'placeddate'=>date('Ymd'),'tradeamount'=>array('gt',0),'tradetype'=>'00');
                $a=M('trade_wastebooks')->where($consumeCon)->count();
                $sumer=M('trade_wastebooks')->where($consumeCon)->sum('tradeamount');
                //卡交易次数超限
                if($conLimit['sumnumber']!=0){
                    if(($a+1)>=$conLimit['sumnumber']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡交易次数：'.$a.'卡交易次数超限限制：'.$conLimit['sumnumber'].'卡日交易次数超限','');
                        $safety->execute("insert into safety_monitoring values('".$cardno."','{$date}','{$ptime}','卡消费','卡交易次数:".$a."卡交易次数超限限制:".$conLimit['sumnumber']."卡日交易次数超限!','04')");
                        returnMsg(array('status'=>'022','codemsg'=>'卡日交易次数超限'));
                    }
                }
                //卡交易金额超限
                if($conLimit['account']!=0){
                    if(bcadd($sumer,$balanceAmount,2)>=$conLimit['account']){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'卡交易金额：'.$sumer.'卡交易金额限制：'.$conLimit['account'].'卡日交易金额超限','');
                        $safety->execute("insert into safety_monitoring values('".$cardno."','{$date}','{$ptime}','卡消费','卡交易金额：".$sumer."卡交易金额限制:".$conLimit['account']."卡日交易金额超限!','04')");
                        returnMsg(array('status'=>'023','codemsg'=>'卡日交易金额超限'));
                    }
                }
            }
            if(in_array($card['cardkind'],array('2336','6888'))){

                #start  $balanceAmount 要扣除的金额
                //zzk_order 订单信息 主键ID order_sn订单号 tradetype交易方式 panterid商户id storeid门店id source来源 amount金额
                $zzk_orderid = $this->zzkgetnumstr('zzk_orderid', 16);
                $inner_order = $this->zzktradeid('05', '05', $zmoney['accountid']);
                $placeddate = date('Ymd');
                $placedtime = date('H:i:s');
                $combined = 0;
                $desc = 'POS刷卡消费';
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
                    $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,
                    'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'tradetype'=>'00',
                    'termno'=>$termposno);
                    $this->recordError('consumeExeByzzCardno：' . serialize($balanceConsumeArr) . "\n\t", "Ctbxfdx", "info");  
                    $balanceConsumeIf=$this->consumeExeByzzCardno($balanceConsumeArr);
                    $this->recordError('consumeExeByzzCardno：' . serialize($balanceConsumeIf) . "\n\t", "Ctbxfdx", "info");  
                } else {
                    $balanceConsumeIf = 'zy_'.$inner_order;
                }
                
            }else{
                $balanceConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,
                    'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'tradetype'=>'00',
                    'termno'=>$termposno);
                //现金订单列表
                $this->recordError('consumeExeByCardno：' . serialize($balanceConsumeArr) . "\n\t", "Ctbxfdx", "info");  
                $balanceConsumeIf=$this->consumeExeByCardno($balanceConsumeArr);
                $this->recordError('consumeExeByzzCardno：' . $balanceConsumeIf . "\n\t", "Ctbxfdx", "info");  
            }
        }else{
            $balanceConsumeIf=true;
        }
        //建业币消费，金额为0不执行
        if($coinAmount>0){
            $coinConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,
                'panterid'=>$panterid,'amount'=>$coinAmount,
                'termno'=>$termposno);
            //通宝消费订单列表，二维数组
            $this->recordError('coinExeByCardno：' . serialize($coinConsumeArr) . "\n\t", "Ctbxfdx", "info");  
            $coinConsumeIf=$this->coinExeByCardno($coinConsumeArr);
            //$coinConsumeIf = array_one($coinConsumeIf);
        }else{
            $coinConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$coinConsumeIf==true){
            $this->model->commit();
            $map=array('panterid'=>$panterid);
            $panter=M('panters')->where($map)->find();
            $returnData=array('cardno'=>encode($cardno),'balanceAmount'=>$balanceAmount,
                'coinAmount'=>$coinAmount,'cardno'=>$cardno,'pname'=>$panter['namechinese']);
            //线下实体卡消费通宝同步一家app
            if($coinAmount>0){
                //获取账户信息
                $customInfo = M('customs')->alias('cu')
                    ->join(' right join customs_c cc on cu.customid=cc.customid')
                    ->where(['cc.cid'=>$card['customid']])->field('cu.linktel')->select();
                if (strlen($posFlagId) == 0) {
                    $posFlagId = '00000';
                }
                $messageArr=array('tradeid'=>$posFlagId,'linktel'=>$customInfo[0]['linktel'],
                    'amount'=>$coinAmount,'pname'=>$panter['namechinese'],'date'=>date('Y-m-d H:i:s'));
                $url=C('ecardConsume').'/api/order/tongbao/sync.json';
                //$url= 'http://yscmall.yijiahn.com/mall/api/order/tongbao/sync.json';
                crul_post($url,$messageArr);
            }
            // 线下实体卡消费 end
            //crul_post($returnUrl,json_encode($returnData));
            //--------20160401-------
            // if(floatval($coinAmount)>0){
            //   $customInfo=M('customs')->where("customid='{$customid}'")->find();
            //   $tpl_value="#name#=".urlencode("{$customInfo['namechinese']}")."&#tel#=".urlencode("{$customInfo['linktel']}")."&#content#=".urlencode("{$panter['namechinese']}")."消费";
            //   $nowmobile = "18538295100,18697323783";
            //   $re = $this->tpl_send("1307781",$nowmobile,$tpl_value);
            //   $this->recordError(date("H:i:s").'-'.$tpl_value.'-'.$re."\n\t","Ctbxfdx","log");
            // }
            $leftBalance = (string)($this->cardAccQuery($cardno));
            $leftCoin = (string)($this->getCoinByCardno($cardno));
            //---------end--------

            $cardkind=substr($cardno,0,4);
            $kindArr=array('6882','2081');
            $isBill=in_array($cardkind,$kindArr)?0:1;
            if(is_bool($balanceConsumeIf)){
                $cashtradidlist=$balanceConsumeIf;
            }else{
                $cashtradidlist=array($balanceConsumeIf);
            }
            echo $str = json_encode(
                array(
                    'status'=>'1',
                    'info'=>array(
                        'reduceBalance'=>floatval($datami['balanceAmount']),
                        'reduceCoin'=>floatval($coinAmount)
                    ),
                    'cointradidlist'=>$coinConsumeIf,
                    'cashtradidlist'=>$cashtradidlist,
                    'leftBalance'=>$leftBalance,
                    'leftCoin'=>$leftCoin,
                    'time'=>time(),
                    'isBill'=>$isBill
                )
            );
            $this->recordError('返回信息：' . $str . "\n\t\r\r", "Ctbxfdx", "info");  
        }else{
            $this->model->rollback();
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'卡号：'.$cardno.'消费扣款失败','');
            $safety->execute("insert into safety_monitoring values('".$cardno."','{$date}','{$ptime}','卡消费','卡号：".$cardno."消费扣款失败!','04')");
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
      $trdeWastebooks=M('trade_wastebooks');
      $map['tradeid']=$tradeid;
      $map['flag']='0';
      $tradeinfo=$trdeWastebooks->where($map)
          ->field("tradeid,tradeamount,tradepoint,cardno,eorderid")->find();
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
          foreach($list as $key=>$val){
              $map['cardno']=$tradeinfo['cardno'];
              $cardInfo=$this->cards->where($map)->find();
              $customid=$cardInfo['customid'];
              if($val['tradetype']=='00'){
                  $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='00'";
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
                  if($tradeIf==true&&$balanceIf==true&&$billIf==true){
                      $count++;
                  }
              }elseif($val['tradetype']=='01'){
                  $pointSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$customid}' and type='04'";
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
              }
          }
          if($count=count($list)){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
          }
      }elseif($cardkind=='2336'||$cardkind=='6888'){
          if(!preg_match('/^6882/',$tradeinfo['cardno'])){
              $balance=$this->cardAccQuery($tradeinfo['cardno']);
              if($balance+$tradeinfo['tradeamount']>5000){
                  returnMsg(array('status'=>'08','codemsg'=>'退款后余额超限，禁止退款'));
              }
          }
          $tradeList=$trdeWastebooks->where(array('eorderid'=>$tradeinfo['eorderid']))
              ->field("tradeid,tradeamount,cardno,eorderid")->select();
          $this->model->startTrans();
          $c=0;
          //echo $trdeWastebooks->getLastSql();exit;
          //print_r($tradeList);exit;
          $string=$trdeWastebooks->getLastSql().'<br/>';
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
              }
          }
          $this->recordData('消费订单撤销sql：'.$string.count($tradeList));
          if($c==count($tradeList)){
              $this->model->commit();
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
          }
      }else{
          //正常至尊卡消费撤销
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
          $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
          if($balanceIf==true&&$coinIf==true&&$tradeIf==true&&$coinReturnIf==true){
              $this->model->commit();
              $data = $balanceSql."///<br/>".$coinSql."***********<br/>";
              $this->recordError($data,'cancelOrder','卡编号_'.$tradeinfo['customid']);
              returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功','time'=>time()));
          }else{
              $this->model->rollback();
              returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
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
    // 退货功能
	public function refundGoods(){
        returnMsg(array('status'=>'01','codemsg'=>'此功能暂停使用,带来不便请谅解！'));
		$datami  =  trim($_POST['datami']);
		$datami  =  json_decode($datami,1);
		$tradeid =  trim($datami['tradeid']);
		$key     = trim($datami['key']);
		$checkKey=md5($this->keycode.$tradeid);
		if($checkKey!=$key){
			returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
		}
		if(empty($tradeid)){
			returnMsg(array('status'=>'03','codemsg'=>'订单号不能为空'));
		}
		$map['tw.tradeid'] = $tradeid;
		$map['ac.type']    = '00';
		$field = 'ca.cardno,ca.status,ca.exdate,cu.namechinese,tw.tradeamount tamount,cu.personid,ac.amount,tw.placeddate,tw.tradetype,tw.panterid,p.parent';
		$info = $this->model->table('trade_wastebooks')->alias('tw')
		                    ->join('left join panters p on p.panterid=tw.panterid')
		                    ->join('left join cards ca on ca.cardno=tw.cardno')
		                    ->join('left join account ac on ac.customid=ca.customid')
		                    ->join('left join customs_c cc on cc.cid=ca.customid')
		                    ->join('left join customs cu on cu.customid=cc.customid')
		                    ->where($map)->field($field)->select();
		$real = $info[0];
		if($real){
			$real['status']=='Y'|| returnMsg(array('status'=>'05','codemsg'=>'非正常卡'));
			$real['exdate']>=date('Ymd') || returnMsg(array('status'=>'06','codemsg'=>'此卡已经过期'));
			$real['tamount']>0 || returnMsg(array('status'=>'07','codemsg'=>'退货金额应大于0'));
			$total = bcadd($real['amount'],$real['tamount'],2);
			if($real['namechinese'] && $real['personid']){
				$total<=5000 || returnMsg(array('status'=>'06','codemsg'=>'记名卡单卡金额不能超过5000'));
			}else{
				$total<=1000 || returnMsg(array('status'=>'06','codemsg'=>'不记名卡单卡金额不能超过1000'));
			}
			$real['tradetype'] == '00' || returnMsg(array('status'=>'09','codemsg'=>'异常交易订单'));
		}else{
			returnMsg(array('status'=>'04','codemsg'=>'账户信息异常'));
		}
		$placeddate = date('Ymd');
		$placedtime = date('H:i:s');
		$refundid = substr($real['cardno'], -4).mt_rand(0,999).date('YmdHis');
		$this->model->startTrans();
		$sql  = "insert into refund_goods values('{$refundid}','{$tradeid}','0','{$placeddate}','{$placedtime}','{$real['panterid']}','{$real['parent']}')";
		try{
			$result = $this->model->execute($sql);
			$tradeif= $this->model->table('trade_wastebooks')->where(['tradeid'=>$tradeid])->save(['tradetype'=>'44']);
		}catch (\Exception $e){
			$this->model->rollback();
			returnMsg(array('status'=>'07','codemsg'=>'退货失败'.$e->getMessage()));
		}
		if($result && $tradeif){
			$this->model->commit();
			returnMsg(array('status'=>'1','codemsg'=>'退货成功'));
		}else{
			$this->model->rollback();
			returnMsg(array('status'=>'07','codemsg'=>'退货失败'));
		}
	}
  }
