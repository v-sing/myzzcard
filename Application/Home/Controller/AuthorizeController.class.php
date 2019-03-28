<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class AuthorizeController extends Controller{
  protected $model;
  protected $cards;
  protected $account;
  protected $trade;
  protected $keycode;
  protected $terminal;
  protected $panters;
  public function _initialize(){
      $this->account=M('account');
      $this->cards=M('cards');
      $this->trade=M('trade_wastebooks');
      $this->terminal=M('panter_terminals');
      $this->panters=M('panter_terminals');
      $this->model=new model();
      $this->keycode='JYO2O01';
    }
   public function start(){
       $cardno = trim(I('post.cardno'));
       $termposno = trim(I('post.termposno'));
       $panterid = trim(I('post.panterid'));
       $amount = trim(I('post.amount'));
       $tradetype = trim(I('post.tradetype'));
       $cardpwd = trim(I('post.cardpwd'));
       $sign = trim(I('post.sign'));
       $this->checkCardHotel($cardno);
      //  $str="\t,".$cardno ."\t,".$termposno."\t,".$panterid."\t,".$amount."\t,".$tradetype."\t,".$cardpwd."\t,".$sign."\n";
      //  file_put_contents('./chushi.txt',$str,FILE_APPEND);
       $signCode = md5($this->keycode.$cardno.$termposno.$panterid.$amount.$tradetype.$cardpwd);
       $signCode == $sign ||returnMsg(array('status'=>'01','codemsg'=>'无效秘钥,非法传入'));
       $tradetype=='17' || returnMsg(array('status'=>'02','codemsg'=>'非预授权交易类型'));
       //商户验证
        $amount>0 || returnMsg(array('status'=>'11','codemsg'=>'交易金额必填大于零'));
       $map_panter = array('panterid'=>$panterid,'revorkflg'=>'N');
       $panterif = $this->panters->where($map_panter)->find();
       $panterif==true|| returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
       //终端验证
       $map_terminal = array('panterid'=>$panterid,'terminal_id'=>$termposno,'flag'=>'Y');
       $terminalif = $this->terminal->where($terminalif)->find();
       $terminalif==true||returnMsg(array('status'=>'04','codemsg'=>'非法终端号'));
       //卡号验证
       $map_card = array('cardno'=>$cardno);
       $cardlist = $this->cards->where($map_card)->find();
       if($cardlist==false)
       {
         returnMsg(array('status'=>'05','codemsg'=>'卡号不存在'));
      }
      else
      {
        if($cardlist['status']!='Y')
        {
          returnMsg(array('status'=>'06','codemsg'=>'不是正常卡'));
        }
        if($cardlist['cardpassword']!=$cardpwd)
        {
          returnMsg(array('status'=>'07','codemsg'=>'卡密码错误'));
        }
      }
         $customid = $cardlist['customid'];
        //账户验证
        $map_account=array('customid'=>$cardlist['customid'],'type'=>'00');
        $accountlist = $this->account->where($map_account)->find();
        $accountlist==true || returnMsg(array('status'=>'08','codemsg'=>'账户不存在'));
        $accountlist['amount']>=$amount || returnMsg(array('status'=>'09','codemsg'=>'账户余额不足'));
        $time = time();
        $time1 = date('Y-m-d H:i:s',$time);
        $placeddate=date('Ymd',$time);
        $placedtime=date('H:i:s',$time);
        $tradeid = $termposno.date('YmdHis',$time);
        $flag='0';
          //开启事务
        $this->model->startTrans();
        $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,customid,tradetype,flag) VALUES('";
        $sql .=$termposno."','" .$termposno."','".$panterid."','".$tradeid."','".$placeddate."','".$placedtime."','".$amount."','".$cardno."','".$customid."','".$tradetype."','".$flag."')";
        $bool1 = $this->trade->execute($sql);
        if($bool1)
        {
               //暂时扣掉预售期的金额
             $start['amount'] = bcsub($accountlist['amount'],$amount,2);
             $bool2 = $this->account->where($map_account)->save($start);
             if($bool2==false)
             {
               returnMsg(array('status'=>'10','codemsg'=>'数据库异常'));
             }else
             {
                 $this->model->commit();
                 $str = "交易流水号：".$tradeid."\t,终端号：".$termposno."\t,商户号：".$panterid."\t,预授权金额：".floatval($amount)."\t,卡号：";
                 $str.=$cardno."\t,交易类型：".$tradetype."\t,时间:".$time1."\n";
                 $this->writeLogs('start',$str);
                 returnMsg(array('status'=>'1','time'=>$time1,'tradeid'=>$tradeid,'amount'=>floatval($amount)));
             }
        }else
        {
          returnMsg(array('status'=>'10','codemsg'=>'数据库异常'));
        }
   }
   //预授权撤销
   public function undo(){
     $cardno = trim(I('post.cardno'));
     $tradeid = trim(I('post.tradeid'));
     $panterid = trim(I('post.panterid'));
     $termposno = trim(I('post.termposno'));
     $tradetype = trim(I('post.tradetype'));
     $sign =  trim(I('post.sign'));
       $this->checkCardHotel($cardno);
      $str="undo测试"."\t,".$cardno ."\t,".$termposno."\t,".$panterid."\t,".$amount."\t,".$tradetype."\t,".$tradeid."\t,".$sign."\n";
      file_put_contents('./chushi.txt',$str,FILE_APPEND);
     $signCode = md5($this->keycode.$cardno.$termposno.$panterid.$tradetype.$tradeid);
     $signCode == $sign || returnMsg(array('status'=>'01','codemsg'=>'无效秘钥,非法传入'));
     $tradetype=='19' || returnMsg(array('status'=>'02','codemsg'=>'非预授权撤销交易类型'));
     //商户验证
     $map_panter = array('panterid'=>$panterid,'revorkflg'=>'N');
     $panterif = $this->panters->where($map_panter)->find();
     $panterif==true|| returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
     //终端号验证
     $map_terminal = array('panterid'=>$panterid,'terminal_id'=>$termposno,'flag'=>'Y');
     $terminalif = $this->terminal->where($map_terminal)->find();
     $terminalif==true||returnMsg(array('status'=>'04','codemsg'=>'非法终端号'));
     //交易流水号
     $map_trade = array('termposno'=>$termposno,'cardno'=>$cardno,'tradeid'=>$tradeid,'tradetype'=>'17','flag'=>'0');
     $tradelist = $this->trade->where($map_trade)->find();
     $tradelist ==true|| returnMsg(array('status'=>'05','codemsg'=>'非法流水号'));
     $tradelist['cardno']==$cardno|| returnMsg(array('status'=>'06','codemsg'=>'请核实卡号'));
     //卡号验证
     $map_card = array('cardno'=>$cardno);
     $cardlist = $this->cards->where($map_card)->find();
     $cardlist == true ||  returnMsg(array('status'=>'06','codemsg'=>'此卡号不存在'));
     $cardlist['status'] == 'Y' || returnMsg(array('status'=>'07','codemsg'=>'非正常卡'));
     //账户验证
     $customid = $cardlist['customid'];
     $map_account = array('customid'=>$cardlist['customid'],'type'=>'00');
     $accountlist = $this->account->where($map_account)->find();
     $accountlist ==true || returnMsg(array('status'=>'08','codemsg'=>'账户信息异常'));
     $amount = bcadd($accountlist['amount'],$tradelist['tradeamount'],2);
    $this->model->startTrans();
    // 撤销操作
    $time = time();
    $time1 = date('Y-m-d H:i:s',$time);
    $placeddate=date('Ymd',$time);
    $placedtime=date('H:i:s',$time);
    $undo_tradeid = $termposno.date('YmdHis',$time);
    $flag='0';
    $tradeamount1 = floatval($tradelist['tradeamount']);
    $this->model->startTrans();
    // returnMsg(array('status'=>'06','codemsg'=>'211212112'));
    $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,customid,tradetype,flag,pretradeid) VALUES('";
    $sql .=$termposno."','" .$termposno."','".$panterid."','".$undo_tradeid."','".$placeddate."','".$placedtime."','".$tradeamount1."','".$cardno."','".$customid."','".$tradetype."','".$flag."','".$tradeid."')";
    // file_put_contents('./chushi.txt',$sql,FILE_APPEND);
    $bool1 = $this->trade->execute($sql);
    // returnMsg(array('status'=>'06','codemsg'=>'211212112'));
    $bool2 = $this->trade->where($map_trade)->save(array('flag'=>'2'));
    $bool3 = $this->account->where($map_account)->save(array('amount'=>$amount));
    if($bool1==true && $bool2==true &&$bool3==true){
      $this->model->commit();
      $str = "预授权撤销流水号：".$undo_tradeid."\t,终端号：".$termposno."\t,商户号：".$panterid."\t,预授权流水号：".$tradeid."\t,撤销金额:".floatval($tradelist['tradeamount'])."\t,卡号：";
      $str.=$cardno."\t,交易类型：".$tradetype."\t,时间:".$time1."\n";
      $this->writeLogs('undo',$str);
      returnMsg(array('status'=>'1','time'=>$time1,'tradeid'=>$undo_tradeid,'amount'=>floatval($tradelist['tradeamount'])));
    }else{
      $this->model->rollback();
      returnMsg(array('status'=>'09','codemsg'=>'数据库或网路异常'));
    }
   }
   //预授权完成
   public function over(){
     $cardno = trim(I('post.cardno'));
     $termposno = trim(I('post.termposno'));
     $panterid = trim(I('post.panterid'));
     $amount = trim(I('post.amount'));
     $tradetype = trim(I('post.tradetype'));
     $tradeid = trim(I('post.tradeid'));
     $sign = trim(I('post.sign'));
     $signCode = md5($this->keycode.$cardno.$termposno.$panterid.$amount.$tradetype.$tradeid);
       $this->checkCardHotel($cardno);
//     $str="啊啊"."\t,".$cardno ."\t,".$termposno."\t,".$panterid."\t,".$amount."\t,".$tradetype."\t,".$cardpwd."\t,".$sign."\n";
//     file_put_contents('./chushi.txt',$str,FILE_APPEND);
     if($signCode!=$sign){
       returnMsg(array('status'=>'01','codemsg'=>'无效秘钥,非法传入'));
     }
     $tradetype=='21'||returnMsg(array('status'=>'02','codemsg'=>'非预授权完成交易'));
     //商户验证
     $map_panter = array('panterid'=>$panterid,'revorkflg'=>'N');
     $panterif = $this->panters->where($map_panter)->find();
     $panterif==true|| returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
     //终端号验证
     $map_terminal = array('panterid'=>$panterid,'terminal_id'=>$termposno,'flag'=>'Y');
     $terminalif = $this->terminal->where($map_terminal)->find();
     $terminalif==true||returnMsg(array('status'=>'04','codemsg'=>'非法终端号'));
     //预授权撤销 验证 订单
     $undo_map1 = array('cardno'=>$cardno,'termposno'=>$termposno,'tradeid'=>$tradeid,'tradetype'=>'17','flag'=>'0');
     $tradelist = $this->trade->where($undo_map1)->find();
     //$tradelist==true || returnMsg(array('status'=>'05','codemsg'=>'非法订单'));
	 $sql = $this->trade->getLastSql();
	  if($tradelist==false){
       $str='卡号：'.$cardno.'|终端号：'.$termposno.'|交易流水：'.$tradeid.'状态非法订单'.$sql;
       file_put_contents('over.log',$str,FILE_APPEND);
       returnMsg(array('status'=>'05','codemsg'=>'非法订单'));
     }
     //卡号验证
     $map_card = array('cardno'=>$cardno);
     $cardlist = $this->cards->where($map_card)->find();
     $cardlist == true || returnMsg(array('status'=>'06','codemsg'=>'卡号不存在'));
     $cardlist['status']=='Y' || returnMsg(array('status'=>'06','codemsg'=>'非正常卡'));
     //账户验证
     $customid = $cardlist['customid'];
      $amount>0 || returnMsg(array('status'=>'07','codemsg'=>'交易金额必填大于零'));
     $map_account = array('customid'=>$cardlist['customid'],'type'=>'00');
     $accountlist = $this->account->where($map_account)->find();
     $accountlist==true || returnMsg(array('status'=>'07','codemsg'=>'账户不存在'));
     $undo_amount = bcadd($accountlist['amount'],$tradelist['tradeamount'],2);
     $undo_amount>=$amount || returnMsg(array('status'=>'08','codemsg'=>'余额不足'));
      // returnMsg(array('status'=>'07','codemsg'=>'11111111111111'));
     $time = time();
     $time1 = date('Y-m-d H:i:s',$time);
     $placeddate=date('Ymd',$time);
     $placedtime=date('H:i:s',$time);
     $over_tradeid = $termposno.date('YmdHis',$time);
     $flag='0';
      // returnMsg(array('status'=>'07','codemsg'=>'11111111111111'));
     //插入交易表数据
     $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,customid,tradetype,flag,pretradeid) VALUES('";
     $sql .=$termposno."','" .$termposno."','".$panterid."','".$over_tradeid."','".$placeddate."','".$placedtime."','".$amount."','".$cardno."','".$customid."','".$tradetype."','".$flag."','".$tradeid."')";
     $this->model->startTrans();
     //预授权撤销操作
     $uddo_bool1 = $this->trade->where($undo_map1)->save(array('flag'=>'2'));
     $uddo_bool2 = $this->account->where($map_account)->save(array('amount'=>$undo_amount));
     $bool2 = $this->trade->execute($sql);
     //卡余额计算
     $over['amount'] = bcsub($undo_amount,$amount,2);

     $bool3 = $this->account->where($map_account)->save($over);
     if($bool2==true && $bool3==true && $uddo_bool1==true && $uddo_bool2==true){
       $this->model->commit();
       $str = "预授权完成流水号：".$over_tradeid."\t,交易金额:".floatval($amount)."\t,终端号：".$termposno."\t,商户号：".$panterid."\t,预授权流水号：".$tradeid."\t,卡号：";
       $str.=$cardno."\t,交易类型：".$tradetype."\t,时间:".$time1."\n";
       $this->writeLogs('over',$str);
       returnMsg(array('status'=>'1','time'=>$time1,'tradeid'=>$over_tradeid,'amount'=>floatval($amount)));
     }else{
       $this->model->rollback();
       returnMsg(array('status'=>'09','codemsg'=>'数据库网络异常'));
     }
   }
   //预售权完成撤销
   public function over_undo(){
     $cardno = trim(I('post.cardno'));
     $termposno = trim(I('post.termposno'));
     $panterid = trim(I('post.panterid'));
     $tradetype = trim(I('post.tradetype'));
     $tradeid = trim(I('post.tradeid'));
     $sign = trim(I('post.sign'));
       $this->checkCardHotel($cardno);
//     $str="啊啊哈哈"."\t,".$cardno ."\t,".$termposno."\t,".$panterid."\t,".$amount."\t,".$tradetype."\t,".$tradeid."\t,".$sign."\n";
//     file_put_contents('./chushi.txt',$str,FILE_APPEND);
     $signCode = md5($this->keycode.$cardno.$termposno.$panterid.$tradetype.$tradeid);
     if($signCode!=$sign){
       returnMsg(array('status'=>'01','codemsg'=>'无效秘钥,非法传入'));
     }
     $tradetype=='24'||returnMsg(array('status'=>'02','codemsg'=>'非预授权完成撤销交易'));
     //商户验证
     $map_panter = array('panterid'=>$panterid,'revorkflg'=>'N');
     $panterif = $this->panters->where($map_panter)->find();
     $panterif==true|| returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
     //终端号验证
     $map_terminal = array('panterid'=>$panterid,'terminal_id'=>$termposno,'flag'=>'Y');
     $terminalif = $this->terminal->where($map_terminal)->find();
     $terminalif==true||returnMsg(array('status'=>'04','codemsg'=>'非法终端号'));
     //流水号验证
     $map_tradeid = array('termposno'=>$termposno,'cardno'=>$cardno,'tradetype'=>'21','flag'=>'0','tradeid'=>$tradeid);
     $tradelist = $this->trade->where($map_tradeid)->find();
     $tradelist==true|| returnMsg(array('status'=>'05','codemsg'=>'非法预授权完成流水号'));
     $validdate = date('Ymd',time());
     
    // $validdate == $tradelist['placeddate'] || returnMsg(array('status'=>'06','codemsg'=>'非当日预授权完成交易'));
     $map_pretradeid = array('termposno'=>$termposno,'cardno'=>$cardno,'tradeid'=>$tradelist['pretradeid'],'tradetype'=>'17','flag'=>'2');
     $pretradeidlist = $this->trade->where($map_pretradeid)->find();
     $pretradeidlist==true || returnMsg(array('status'=>'07','codemsg'=>'非法预授权流水号'));
     ($validdate-$pretradeidlist['placeddate'])<30 || returnMsg(array('status'=>'08','codemsg'=>'预授权超过30天有效期'));
     //卡号验证
     $map_card = array('cardno'=>$cardno);
     $cardlist = $this->cards->where($map_card)->find();
     $cardlist == true || returnMsg(array('status'=>'09','codemsg'=>'卡号不存在'));
     $cardlist['status']=='Y' || returnMsg(array('status'=>'10','codemsg'=>'非正常卡'));
     //账户验证
     $customid = $cardlist['customid'];
     $map_account = array('customid'=>$cardlist['customid'],'type'=>'00');
     $accountlist = $this->account->where($map_account)->find();
     $accountlist==true || returnMsg(array('status'=>'11','codemsg'=>'账户不存在'));
     $this->model->startTrans();
     $account['amount'] = bcadd($accountlist['amount'],$tradelist['tradeamount'],2);
     $account['amount'] = bcsub($account['amount'],$pretradeidlist['tradeamount'],2);
     $bool1 = $this->trade->where($map_tradeid)->save(array('flag'=>'2'));
     $bool2 = $this->trade->where($map_pretradeid)->save(array('flag'=>'0'));
     $bool3 = $this->account->where($map_account)->save(array('amount'=> $account['amount']));
     //预授权完成撤销交易生成记录
     $time=time();
     $time1 = date('Y-m-d H:i:s',$time);
     $placeddate=date('Ymd',$time);
     $placedtime=date('H:i:s',$time);
     $over_undo_tradeid = $termposno.date('YmdHis',$time);
     $flagon='0';
     $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,customid,tradetype,flag,pretradeid) VALUES('";
     $sql .=$termposno."','" .$termposno."','".$panterid."','".$over_undo_tradeid."','".$placeddate."','".$placedtime."','".$amount."','".$cardno."','".$customid."','".$tradetype."','".$flagon."','".$tradeid."')";
     $bool4 = $this->trade->execute($sql);
     if($bool1=true && $bool2=true && $bool3=true){
        $this->model->commit();
         $str = "预授权完成撤销流水号：".$over_undo_tradeid."\t,终端号：".$termposno."\t,商户号：".$panterid."\t,预授权完成流水号：".$tradeid."\t,金额".floatval($tradelist['tradeamount']);
         $str.="\t,预授权流水号".$tradelist['pretradeid']."\t,金额".floatval($pretradeidlist['tradeamount'])."\t,卡号：";
         $str.=$cardno."\t,交易类型：".$tradetype."\t,时间:".$time1."\n";
         $this->writeLogs('over_undo',$str);
         returnMsg(array('status'=>'1','time'=>$time1,'tradeid'=>$over_undo_tradeid,'amount'=>floatval($tradelist['tradeamount'])));
     }else{
       $this->model->rollback();
       returnMsg(array('status'=>'12','codemsg'=>'数据库异常'));
     }
   }
   //定时清理预授权订单
   public function clear(){
     set_time_limit(0);
     ini_set('memory_limit', '-1');
     $map = array('tradetype'=>'17','flag'=>'0');
     $tradelists = $this->trade->where($map)->select();
     if($tradelists==true){
       $keysum=1;
       foreach ($tradelists as $key => $val) {
         if($keysum==200){
           $keysum=1;
           sleep(1);
         }
         $validdate = date('Ymd',time());
         $bool = ($validdate-$val['placeddate'])>=30;
         if($bool){
           $map_account = array('ca.cardno'=>$val['cardno'],'ac.type'=>'00');
           $list_account = $this->cards->alias('ca')
                                ->join('__ACCOUNT__ ac on ca.customid=ac.customid')
                                ->where($map_account)->field('ac.amount,ac.customid')->find();
          $account['amount'] = bcadd($list_account['amount'],$val['tradeamount'],2);
          $time=time();
          $placeddate = date('Ymd',$time);
          $placedtime = date('H:i:s',$time);
          $time1 = date('Y-m-d H:i:s',$time);
          $tradeid = $val['termposno'].date('YmdHis',$time);
          $tradetype='19';
          $flag = '0';
          $this->model->startTrans();
          $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,tradetype,flag,pretradeid) VALUES('";
          $sql .=$val['termno']."','" .$val['termposno']."','".$val['panterid']."','".$tradeid."','".$placeddate."','".$placedtime."','".$val['amount']."','";
          $sql.=$val['cardno']."','".$tradetype."','".$flag."','".trim($val['tradeid'])."')";
          $bool1 = $this->trade->execute($sql);
          $bool2 = $this->trade->where(array('tradeid'=>$val['tradeid'],'flag'=>'0'))->save(array('flag'=>'2'));
          $bool3 = $this->account->where(array('customid'=>$list_account['customid'],'type'=>'00'))->save(array('amount'=>$account['amount']));
          if($bool1==true && $bool2==true && $bool3==true){
            $this->model->commit();
            $str = '预授权流水号:'.trim($val['tradeid'])."\t,金额：".$val['tradeamount']."\t,预授权撤销流水号：".$tradeid."\t,时间:".$time1."\n";
            $this->writeLogs('success',$str);
          }else{
            $this->model->rollback();
              $str = '预授权流水号:'.trim($val['tradeid'])."\t,金额：".$val['tradeamount']."\n";
              $this->writeLogs('fail',$str);
          }
         }
         $keysum++;
        //  dump($list_account);
        //  exit;
       }
     }
   }
   public function writeLogs($module,$msgString){
       $month=date('Ym',time());
       switch($module){
           case 'start':$logPath=PUBLIC_PATH.'logs/Authorize/start/';break;
           case 'undo':$logPath=PUBLIC_PATH.'logs/Authorize/undo/';break;
           case 'over':$logPath=PUBLIC_PATH.'logs/Authorize/over/';break;
           case 'over_undo':$logPath=PUBLIC_PATH.'logs/Authorize/over_undo/';break;
           case 'success':$logPath=PUBLIC_PATH.'logs/Authorize/success/';break;
           case 'fail':$logPath=PUBLIC_PATH.'logs/Authorize/fail/';break;
           default :$logPath=PUBLIC_PATH.'logs/file/';
       }
       $logPath=$logPath.$month.'/';
       $filename=date('Ymd',time()).'.log';
       $filename=$logPath.$filename;
       if(!file_exists($logPath)){
           mkdir($logPath,0777,true);
       }
       file_put_contents($filename,$msgString,FILE_APPEND);
   }
   private function checkCardHotel($cardno){
       $list = $this->cards->where(['cardno'=>$cardno])->find();
       if($list){
           if(!in_array($list['cardkind'],['6882','2081'])){
               returnMsg(array('status'=>'09','codemsg'=>'此卡不是酒店卡'));
           }
       }else{
           returnMsg(array('status'=>'09','codemsg'=>'卡号不存在'));
       }
       return true;
   }
}
?>
