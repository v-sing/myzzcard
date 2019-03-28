<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\DESedeCoder;
use Org\Util\DES;
use Home\Controller\CoinController;
use  Home\Controller\CommonController;

class TbrecordGoodsController extends CoinController{
    protected  $unActivateDay=10;//用户没有激活(实名) 十天有效期
    protected  $ActivateDay=730;//用户激活(实名)之后 两年有效期
    protected  $reimbursedDay=7;//退款，通宝过期，补偿七天时间
    protected  $model;
    protected  $customs;
    protected  $cards;
    protected  $account;
    protected  $panters;
    protected  $tb_pool;
    protected  $tb_pool_details;
    protected  $coin_account;
    protected  $coin_consume;
    protected  $customs_c;
    protected  $keycode="JYO2O01";//密钥
    protected  $payPWdKeyCode='JYfghj6789436';//免密秘钥
  public function _initialize(){
  	 date_default_timezone_set("Asia/Shanghai");

      $this->account=M('account');
      $this->customs=M('customs');
      $this->panters=M("panters");
      $this->account=M("account");
      $this->cards=M('cards');
      $this->trade_wastebooks=M("trade_wastebooks");
      $this->model=new model();
      $this->tb_pool=M("tb_pool");
      $this->tb_pool_details=M("tb_pool_details");
      $this->coin_account=M("coin_account");
      $this->coin_consume=M("coin_consume");
      $this->customs_c=M("customs_c");
    }
 public function ejiaRefund($refund){

       
        if($refund==false){
            return ['status'=>'011','codemsg'=>'非法数据传入'];
        }
 
        $orderid=trim($refund['orderId']);
        $refundCoin=trim($refund['returnCoin']);
        $sourceCode=trim($refund['sourceCode']);
  
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$refundCoin)) {
            return ['status'=>'013','codemsg'=>'退款建业币格式有误',"data"=>$refund];
        }
         $refundCoin=sprintf("%.2f",floatval($refundCoin));
         $coin=new CoinController();
         $payId = $orderid;
        if(!isset($coin->panterArr[$sourceCode])) return ['status'=>'014','codemsg'=>'非法商户'];

        if($this->isRepeat($coin->panterArr[$sourceCode]['prefix'].$payId)) 
          return ['status'=>'015','codemsg'=>'已经退款'];
  
        $map['tw.eorderid'] = $coin->panterArr[$sourceCode]['prefix'].$payId;
        $field = 'tw.tradepoint,coin_c.*,coin_a.accountid,coin_a.enddate,coin_a.coinid ccoinid,p.namechinese name';
        $consumeInfo = $this->consumeInfo($map, $field);
        $consumePositiveInfo = $this->ConsumePositiveInfo($map, $field);
       
        // 通宝过期，进行七天补偿，如果二次过期，最后期限+1天
        $this->ifExpired($consumeInfo);
     
        $comp  = $this->valiEjiaRefundAmount($consumeInfo,$refundCoin,$consumePositiveInfo);

  
        if($comp===0){ //全部退款.
          $this->model->startTrans();

          try{
            if($this->exeEjiaRefund($consumeInfo,$refundCoin,$comp)){
                // 冲正消费
                // 冲正消费
                foreach($consumeInfo as $value){
                    $positiveFlag=$this->PositiveConsumptionRecord($orderid,$value['tradepoint'],$value["cardid"],$value["panterid"],$value["coinid"],$sourceCode);
                }
                if($positiveFlag==false){
                     return ['status'=>'14','codemsg'=>'消费冲正记录失败！'];
                }

                $this->model->commit();

              $pulish  = $this->returnPublish($consumeInfo ,true);
              return ['status'=>'1','codemsg'=>'ok','info'=>['addCoin'=>sprintf("%.2f",floatval($refundCoin))],'publish'=>$pulish];
            }else{
              $this->model->rollback();
              $refund["error"]="回滚失败";
              $coin->recordData("退款失败记录:".implode("#", $refund));
              return ['status'=>'20','codemsg'=>'退款失败，请联系管理员'];
            }

          }catch (\Exception $e){
            $this->model->rollback();
              $refund["error"]=$e;
              $coin->recordData("退款失败记录:".implode("#", $refund));
            return ['status'=>'21','codemsg'=>'退款失败，请联系管理员'];
          }

        }elseif ($comp===1){//部分退款
            $mainCustomid = $this->mainCustomid(array_unique(array_column($consumeInfo,'cardid')));
            if($mainCustomid==false){
                $coin->recordData("退款失败记录:".implode("#", $refund));
              return ['status'=>'15','codemsg'=>'账户变更导致异常'];
            }
          $consume    =  array_sum(array_column($consumeInfo,'amount'));
          $consumeCoin = bcsub($consume,$refundCoin,2);
            $consumeArr = [
              'customid'=> $mainCustomid,
              'orderid' => $map['tw.eorderid'],
              'amount'  => $consumeCoin,
              'panterid'=>'00000286',
              'type'    =>'00',
              'placeddate'=>$consumeInfo[0]['placeddate'],
              'placedtime'=>$consumeInfo[0]['placedtime']

            ];
        $this->model->startTrans();
        try{
           
             $boolif =$this->exeEjiaRefund($consumeInfo,$refundCoin,$comp);
	           
                if($boolif){
					// 冲正消费
	         $positiveFlag=$this->PositiveConsumptionRecord($orderid,$refundCoin,$consumeInfo[0]["cardid"],$consumeInfo[0]["panterid"],$consumeInfo[0]["coinid"],$sourceCode);

              if($positiveFlag==false){
                  $coin->recordData("退款失败记录:".implode("#", $refund));
                   return ['status'=>'27','codemsg'=>'消费冲正记录失败！'];
              }

	            	$this->model->commit();
	              
	              	$pulish = $this->returnPublish($consumeInfo,false);
	              	foreach ($coinPublish as  $vs){
	                	if(isset($pulish[$vs['name']])){
	                  		$pulish[$vs['name']]['cut'] = bcsub($pulish[$vs['name']]['cut'],$vs['amount'],2);
	                	}
	              	}
	              	$back = [];
	              	foreach ($pulish as $vss){
	                	$back[] = $vss;
	              	}

	              	return ['status'=>'1','codemsg'=>'ok','info'=>['addCoin'=>sprintf("%.2f",floatval($refundCoin))],'publish'=>$back];
	            }else{
	            	$this->model->rollback();
                  $refund["error"]="回滚失败";
	              	$coin->recordData("退款失败记录:".implode("#", $refund));
	              	return ['status'=>'22','codemsg'=>'退款失败，请联系管理员'];
	            }

        	}catch (\Exception $e){
          		$this->model->rollback();
              $refund["error"]=$e;
            	$coin->recordData("退款失败记录:".implode("#", $refund));
            	return ['status'=>'23','codemsg'=>'退款失败，请联系管理员',"e"=>$e];
        	}
        }
        else{
            return $comp;
        }

    }
//8.15下午到10.19 冲正消费 补偿

    public function buchangPostive(){
      $eorderid=$_GET["id"];
      $amount=$_GET["amount"];
      $sourceCode=$_GET["code"];
         $coin=new CoinController();
       $map['tw.eorderid'] = $coin->panterArr[$sourceCode]['prefix'].$eorderid;
        $field = 'tw.tradepoint,coin_c.*,coin_a.accountid,coin_a.enddate,coin_a.coinid ccoinid,p.namechinese name';
        $consumeInfo = $this->consumeInfo($map, $field);

        var_dump( $consumeInfo);

         $positiveFlag=$this->PositiveConsumptionRecord( $eorderid,$amount,$consumeInfo[0]["cardid"],$consumeInfo[0]["panterid"],$consumeInfo[0]["coinid"], $sourceCode);

         if($positiveFlag)
          echo "成功";
        else echo "失败";




    }

    //校验一家通宝退款 金额 0 全款  1 部分退款
    private function valiEjiaRefundAmount(array $consumeInfo,$postRefundCoin,$consumePositiveInfo){
        if($postRefundCoin<=0) returnMsg(['status'=>'09','codemsg'=>'建业币退款金额需大于0']);
        $sqlTradeCoin   = array_sum(array_column($consumeInfo,'tradepoint'));//tw 消费额度
        $sqlConusmeCoin = array_sum(array_column($consumeInfo,'amount'));//cc 消费额度
        $sqlTradeCoin=sprintf("%.2f",floatval($sqlTradeCoin)); 
        $sqlConusmeCoin=sprintf("%.2f",floatval($sqlConusmeCoin));
        $sqlTradePositiveCoin   = array_sum(array_column($consumePositiveInfo,'tradepoint'));//tw 消费额度
        $sqlConusmePositiveCoin = -array_sum(array_column($consumePositiveInfo,'amount'));//cc 消费额度
        $sqlTradePositiveCoin=sprintf("%.2f",floatval($sqlTradePositiveCoin)); 
        $sqlConusmePositiveCoin=sprintf("%.2f",floatval($sqlConusmePositiveCoin));
        if(bccomp($sqlTradePositiveCoin,$sqlConusmePositiveCoin,2)!==0) return ['status'=>'11','codemsg'=>'建业币冲正交易金额异常，请联系管理'];
        if(bccomp($sqlTradeCoin,$sqlConusmeCoin,2)!==0) return ['status'=>'11','codemsg'=>'建业币交易金额异常，请联系管理'];
        
        $remainTradeCoin=bcsub($sqlTradeCoin,$sqlTradePositiveCoin,2);

         $remainTradeCoin=bcsub($sqlTradeCoin,$sqlTradePositiveCoin,2);
        if($remainTradeCoin<=0)
          return ['status'=>'31','codemsg'=>'此消费订单已经退款,请勿重复退款!'];

        $comp = bccomp($remainTradeCoin,$postRefundCoin,2);
        if($comp===0){
            return $comp;
        }elseif($comp===1){
          return $comp; //开启部分退款

        }
        else{
            return ['status'=>'12','codemsg'=>'退款建业币金额大于订单建业币金额',"comp"=> $comp,"sqlTradeCoin"=>$sqlTradeCoin];
        }
    }
    //检验一家通宝退款 时交易日期(上月订单是，本月退款日期不应在4号之后)
    private function valiEjiaRefundTradeDate(string $tradeDate,string $tradeTime){
        $month = date('m');
        $day   = date('d');
        $trade = substr($tradeDate,'4','2');
        if($month===$trade){
            return true;
        }
        if(strtotime($tradeDate.$tradeTime)>=strtotime(date('Ym01',strtotime('last month')))&& $day<='04'){
            return true;
        }
        return false;
    }

    //执行一家 全额 通宝退款
    protected function exeEjiaRefund(array $consumeInfo,$postRefundCoin,$comp){
        $count = count($consumeInfo);
       
     
        if($count===1){
            $trade['tradeid']   = $consumeInfo[0]['tradeid'];
            $coinid    = $consumeInfo[0]['coinid'];
            $accountid = $consumeInfo[0]['accountid'];
            $amount    = $consumeInfo[0]['amount'];
            // $coin_accountUpdate = "UPDATE coin_account set remindamount=remindamount + {$amount} WHERE coinid={$coinid}";
            // $accountUpdate      = "UPDATE account set amount=amount + {$amount} where accountid={$accountid}";
            $coin_accountUpdate = "UPDATE coin_account set remindamount=remindamount + {$postRefundCoin} WHERE coinid={$coinid}";
            $accountUpdate      = "UPDATE account set amount=amount + {$postRefundCoin} where accountid={$accountid}";

        }else{
            $trade['tradeid'] = ['in',array_column($consumeInfo,'tradeid')];
            $coin_accountUpdate = "UPDATE coin_account set remindamount=remindamount + CASE coinid";
            $accountUpdate      = "UPDATE account set amount=amount + CASE accountid ";
            $remainAmount=sprintf("%.2f",floatval($postRefundCoin));
            foreach ($consumeInfo as $val){


               if($val['amount']>=$remainAmount){
                 $coin_accountUpdate.= " WHEN '{$val['coinid']}' THEN {$remainAmount}";
                 break;
               }

               if($val['amount']<$remainAmount){
                $remainAmount=bcsub($remainAmount, $val['amount'],2);
                $coin_accountUpdate.= " WHEN '{$val['coinid']}' THEN {$val['amount']}";
                if($remainAmount<=0)
                  break;

               }

                // $coin_accountUpdate.= " WHEN '{$val['coinid']}' THEN {$val['amount']}";
               // $accountUpdate.= " WHEN '{$val['accountid']}' THEN {$val['amount']}";
            }

            $coinInWhere    = $this->inString(array_column($consumeInfo,'coinid'));
           $accountInWhere = $this->inString(array_column($consumeInfo,'accountid'));
            $coin_accountUpdate.=" END where coinid in ({$coinInWhere})";
           // $accountUpdate.=" END where accountid in ({$accountInWhere})";
          $accountSql     = $this->getAccountSql($consumeInfo);
          $accountUpdate .=  $accountSql['sql']." END where accountid in ({$accountSql['keys']})";

           
        }  

        // $tradeIf   = M('trade_wastebooks')->where($trade)->save(array("tradetype"=>"04"));
        // $consumeIf = M('coin_consume')->where($trade)->delete();
        $coin_accountIf= $this->model->execute($coin_accountUpdate);
        $accountIf = $this->model->execute($accountUpdate);

        if($coin_accountIf==true&&$accountIf==true){
            return true;
        }else{
            return false;
        }
    }
  protected function getAccountSql($customArr)
  {

    $keys = array_unique(array_column($customArr,'accountid'));

    if(count($customArr)===count($keys)){
      $foreach = $customArr;
    }else{
      foreach($customArr as $val){
        if(isset($foreach[$val['accountid']])){
          $foreach[$val['accountid']]['amount'] = bcadd($foreach[$val['accountid']]['amount'],$val['amount'],2);
        }else{
          $foreach[$val['accountid']] = $val;
        }
      }
    }
    $accounSql = '';
    foreach ($foreach as $info){
      $accounSql .= " WHEN '{$info['accountid']}' THEN {$info['amount']} ";
    }
    $inKeys = $this->inString($keys);
    // var_dump($keys);exit;
    return ['sql'=>$accounSql,'keys'=>$inKeys];
  }

  public function BuchangRecords(){
      // $positiveSql="select cc.coinconsumeid, cc.tradeid,cc.cardid,cc.coinid,cc.amount,cc.placeddate,cc.placedtime,tw.eorderid FROM coin_consume cc join trade_wastebooks tw on tw.tradeid=cc.tradeid where cc.amount<0 and cc.placeddate>='20180918'  and cc.flag='2'";
     $coinClass=new CoinController;
     // $positive=$this->model->execute($positiveSql);
     $positive=M("coin_consume")->alias("cc")->join("trade_wastebooks tw on tw.tradeid=cc.tradeid")->where(array("cc.amount"=>array("lt",0),"cc.placeddate"=>array("egt",'20180918'),"cc.flag"=>array("eq",'2')))->field("cc.coinconsumeid, cc.tradeid,cc.cardid,cc.coinid,cc.amount,cc.placeddate,cc.placedtime,tw.eorderid")->select();
    // var_dump($positive);
     foreach ($positive as $key => $value) {

      
     $coinconsumeid=$coinClass->getFieldNextNumber('coinconsumeid');
     $card = M('cards')->where(['customid'=>$cardid])->field('cardno')->find();
     $amount=-sprintf("%.2f",floatval($value['amount']));
     $twDate=M("trade_wastebooks")->where(array("eorderid"=>"e+_".$value["eorderid"],"tradetype"=>"04","tradepoint"=>$amount))->field("tradeid")->find();
     if(empty($twDate["tradeid"]))
     {
      echo $key."没有对应的tw失败记录:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."..."."<br/>";


        $twDate1=M("trade_wastebooks")->where(array("eorderid"=>"e+_".$value["eorderid"],"tradetype"=>"04","tradepoint"=>array("egt",$amount)))->select();

        if(count($twDate1)>=2){
          echo $key."没有对应的tw失败记录:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."...需要人工排查"."<br/>";
          continue;
        }
        // 多个消费记录
        if(count($twDate1)==0){

          $twDate3=M("trade_wastebooks")->where(array("eorderid"=>"e+_".$value["eorderid"],"tradetype"=>"04","tradepoint"=>array("elt",$amount)))->select();

          $twDateAmount=array_sum(array_column($twDate3, "tradepoint"));

          if($twDateAmount==$amount)
          {
             foreach ($twDate3 as $key1 => $value1) {
           $coinconsumeid=$coinClass->getFieldNextNumber('coinconsumeid');
           $tradeid= $value1['tradeid'];
           $placeddate=$value1["placeddate"];
           $placedtime=$value1["placedtime"];
           $status=1;
           $flag=1;
           $pantercheck=1;
           // $checkDate=date("Ymd",time());
           $checkdate=time();
           $cardid=$value1["customid"]; 
           $coinid=$value["coinid"];
           $panterid="00000286";
           $amount=$value1["tradepoint"];
           $coinConsumeSql="INSERT INTO coin_consume(coinconsumeid,tradeid,cardid,coinid,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate)VALUES";
           $coinConsumeSql.="('".$coinconsumeid."','".$tradeid."','".$cardid."','".$coinid."','".$amount."','".$placeddate."','".$placedtime."','".$panterid."','".$status."','".$flag."','".$pantercheck."','".$checkdate."')";

           echo  $key."需要执行：".$coinConsumeSql."<br/>";


             }



           // 获取delete

           $twDate4=M("trade_wastebooks")->where(array("eorderid"=>"e+_".$value["eorderid"],"tradetype"=>"00","tradepoint"=>array("elt",$amount)))->select();
           if(count($twDate4)==1){
            if($twDate4[0]["tradepoint"]==$amount){
          echo $key."需要执行：delete * FROM trade_wastebooks where tradeid='".$twDate4[0]["tradeid"]."'"."<br/>";
           echo $key."需要执行：delete * FROM coin_consume where tradeid='".$twDate4[0]["tradeid"]."'"."<br/>";
            }
             else {
              echo $key."没有对应的tw失败记录出现异常:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."...需要人工排查"."<br/>";
              continue;
             }
           }

           if(count($twDate4)==0){
            echo "多个消费记录，没有部分消费记录"."</br>";
             continue;
           }

           if(count($twDate4)>=2)
            {
            echo $key."没有对应的tw失败记录出现异常:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."...需要人工排查"."<br/>";
            continue;
           }

          }
          else echo $key."没有对应的tw失败记录:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."...需要人工排查"."<br/>";

          continue;

        }

        if(count($twDate1)==1)
       
           $remainAmount=bcsub($twDate1[0]["tradepoint"], $amount,2);

           $twDate2=M("trade_wastebooks")->where(array("eorderid"=>"e+_".$value["eorderid"],"tradetype"=>"00","tradepoint"=>$remainAmount))->find();

           echo $key."需要执行：delete * FROM trade_wastebooks where tradeid='".$twDate2["tradeid"]."'"."<br/>";
           echo $key."需要执行：delete * FROM coin_consume where tradeid='".$twDate2["tradeid"]."'"."<br/>";

            $tradeid= $twDate1[0]['tradeid'];
     $placeddate=$twDate1[0]["placeddate"];
     $placedtime=$twDate1[0]["placedtime"];
     $status=1;
     $flag=1;
     $pantercheck=1;
     // $checkDate=date("Ymd",time());
     $checkdate=time();
     $cardid=$twDate1[0]["customid"]; 
     $coinid=$value["coinid"];
     $panterid="00000286";
     $coinConsumeSql="INSERT INTO coin_consume(coinconsumeid,tradeid,cardid,coinid,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate)VALUES";
     $coinConsumeSql.="('".$coinconsumeid."','".$tradeid."','".$cardid."','".$coinid."','".$amount."','".$placeddate."','".$placedtime."','".$panterid."','".$status."','".$flag."','".$pantercheck."','".$checkdate."')";

     echo  $key."需要执行：".$coinConsumeSql."<br/>";


      continue;
     }

     $twDate1=M("coin_consume")->where(array("tradeid"=>$twDate["tradeid"]))->find();

     if(!empty($twDate1["coinconsumeid"])){
      echo $key."异常数据:coinconsumeid-".$value["coinconsumeid"]."-amount:".$value["amount"]."...存在消费记录coinconsumeid:".$twDate1["coinconsumeid"]."<br/>";
      continue;
     }

     $tradeid= $twDate['tradeid'];
     $placeddate=$value["placeddate"];
     $placedtime=$value["placedtime"];
     $status=1;
     $flag=1;
     $pantercheck=1;
     // $checkDate=date("Ymd",time());
     $checkdate=time();
     $cardid=$value["cardid"]; 
     $coinid=$value["coinid"];
     $panterid="00000286";
     $coinConsumeSql="INSERT INTO coin_consume(coinconsumeid,tradeid,cardid,coinid,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate)VALUES";
     $coinConsumeSql.="('".$coinconsumeid."','".$tradeid."','".$cardid."','".$coinid."','".$amount."','".$placeddate."','".$placedtime."','".$panterid."','".$status."','".$flag."','".$pantercheck."','".$checkdate."')";

  echo  $coinConsumeSql."<br/>";
     }
  }

 //冲正消费
  // $orderid 退款订单号 $amount 退款通宝数量 $cardid 卡id $panterid 消费商户id

  private  function  PositiveConsumptionRecord($orderid,$amount,$cardid,$panterid,$coinid,$sourceCode){

    if(empty($orderid)||empty($amount)||empty($cardid)||empty($panterid)||empty($sourceCode))
    {
      return false;
    }
     $amount=sprintf("%.2f",floatval($amount));
     $coinid=$coinid;//关联coin_account 的 冲正消费没有发行项目信息,为了在兑换消费中显示，需要随机绑定一个发行商户，这个是无效的
     $coinClass=new CoinController;
     $coinconsumeid=$coinClass->getFieldNextNumber('coinconsumeid');
     $card = M('cards')->where(['customid'=>$cardid])->field('cardno')->find();
         
     $tradeid=substr($card['cardno'],15,4).date('YmdHis',time()).rand(1000,9999);
     $placeddate=date("Ymd",time());
     $placedtime=date("H:i:s",time());;
     $status=1;
     $flag=2;
     $pantercheck=1;
     $checkDate=date("Ymd",time());
     $panterid=$panterid;
     $checkdate=time();
     $amount=-$amount;//冲正处理

     $payId = $orderid;
     // if(!isset($coinClass->panterArr[$sourceCode])) return ['status'=>'014','codemsg'=>'非法商户信息'];

     $eorderidFlag = $coinClass->panterArr[$sourceCode]['prefix'].$payId;

     $pretradeidFlag = M('trade_wastebooks')->where(array("eorderid"=>$eorderidFlag,"tradetype"=>"00"))->field('tradeid')->find();
 
     $coinConsumeSql="INSERT INTO coin_consume(coinconsumeid,tradeid,cardid,coinid,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate)VALUES";
     $coinConsumeSql.="('".$coinconsumeid."','".$tradeid."','".$cardid."','".$coinid."','".$amount."','".$placeddate."','".$placedtime."','".$panterid."','".$status."','".$flag."','".$pantercheck."','".$checkdate."')";

     $termposno='00000000';
     $tradetype="09";//通宝冲正消费标志
     $tradepoint=-$amount;
     $flag=0;
     $eorderid=$coinClass->panterArr[$sourceCode]['prefix'].$orderid;
     $tac='abcdefgh';
     $termno="00000002";
     $tradeamount=0;
     $pretradeid= trim($pretradeidFlag["tradeid"]);
     $tradeWasteBookSql="INSERT INTO trade_wastebooks(panterid,termposno,cardno,customid,tradeid,placeddate,placedtime,tradetype,tradepoint,flag,tac,eorderid,termno,tradeamount,pretradeid)VALUES";

     $tradeWasteBookSql.="('".$panterid."','".$termposno."','".$card["cardno"]."','".$cardid."','".$tradeid."','".$placeddate."','".$placedtime."','".$tradetype."','".$tradepoint."','".$flag."','".$tac."','".$eorderid."','".$termno."','".$tradeamount."','".$pretradeid."')";

       $coinSqlIf=$this->model->execute($coinConsumeSql);
     
      $tradeSqlIf=$this->model->execute($tradeWasteBookSql);


      if($coinSqlIf==true&&$tradeSqlIf==true)
        return  true;
      else  return false;



}
  // 部分退款 执行在消费  ----2018--04--16
  public function partConsume(array $consumeArr){
    $orderid  = $consumeArr['orderid'];
    $customid = $consumeArr['customid'];
    $amount   = $consumeArr['amount'];
    $panterid = $consumeArr['panterid'];
    $type     = $consumeArr['type'];
    $termno   = !empty($consumeArr['termno'])?$consumeArr['termno']:'00000000';
     $coinClass=new CoinController;
    $coinLists = $this->coinAccountList($customid);
    $coinAccountSql = '';
    $coinConsumeSql = '';
    $tradeSql       = '';
    $placeddate     = $consumeArr['placeddate'];
    $placedtime     = $consumeArr['placedtime'];
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
          $card =  M('cards')->where(['customid'=>$coin['cardid']])->field('cardno')->find();
          $coinAccountSql.=" WHEN '{$coin['coinid']}' THEN {$reduce}";

          $tradeid=substr($card['cardno'],15,4).date('YmdHis',time()).rand(1000,9999);
    
          $coinconsumeid = $coinClass->getFieldNextNumber('coinconsumeid');
 
          $coinConsumeSql .=" INTO coin_consume values('{$coinconsumeid}','{$tradeid}','{$coin['cardid']}','{$coin['coinid']}','{$reduce}','{$placeddate}','{$placedtime}','{$panterid}',0,1,1,'{$time}','0000000000000000')";

          $tradeSql.=" into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
          $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,eorderid)";
          $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
          $tradeSql.="'0','{$reduce}','{$coin['cardid']}','{$card['cardno']}','{$placedtime}','00','abcdefgh','0','{$orderid}') ";

          $deduct[] = ['cardid'=>$coin['cardid'],'amount'=>$reduce];
          $time++;
          $coinid[] = $coin['coinid'];

          $cutPublish[] = ['name'=>$coin['namechinese'],'amount'=>$reduce];
        }
      }
      if($leave>0) returnMsg(['status'=>'17','codemsg'=>'异常账户变更,扣款失败']);
      $coinidIn = $this->inString($coinid);
      $coinAccountSql = "UPDATE coin_account set remindamount = remindamount - CASE coinid ".$coinAccountSql. " END WHERE coinid in ($coinidIn)";
      $coinConsumeSql = "INSERT ALL ".$coinConsumeSql. " select 1 from dual ";
      $tradeSql       =  "INSERT ALL ".$tradeSql. " select 1 from dual ";
      $accountSql = $this->getPartAccountSql($deduct);
      try{
        $coinAccountIf   = $this->model->execute($coinAccountSql);
        $coinConsumeIf   = $this->model->execute($coinConsumeSql);
        $tradeIf         = $this->model->execute($tradeSql);
        $accountIf       = $this->model->execute($accountSql);

      }catch (\Exception $e){

        return false;
      }
      if($leave==0 && $coinAccountIf && $coinConsumeIf && $tradeIf && $accountIf){
        return $cutPublish;
      }else{
        return false;
      }
    }
    else{
      return false;
    }
  }
  //通宝卡按发行记录扣款 返回所有查询记录
  // protected function coinAccountList($customid){
  //   $info = M('customs_c')->where(['cid'=>$customid])->field('customid')->find();
  //   $customid = $info['customid'];
  //   $day = date('Ymd');
  //   $SQL  = "SELECT coin.remindamount,coin.cardid,coin.coinid,p.namechinese FROM COIN_ACCOUNT coin left join panters p on coin.panterid=p.panterid WHERE coin.CARDID IN(SELECT cid from customs_c where customid='{$customid}') AND coin.ENDDATE>='{$day}' AND coin.remindamount>0 ORDER  BY  ENDDATE ASC ";
  //   $coinList = $this->model->query($SQL);
  //   return $coinList;
  // }
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

  protected function getPartAccountSql($customArr)
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

  protected function getFieldNextNumber($field){
    $fieldLength = [
      'coinconsumeid'    => '10',
    ];
    $seq_field='seq_'.$field.'.nextval';
    $sql="select {$seq_field} from dual";
    $list=$this->model->query($sql);
    $fieldLength=$fieldLength[$field];
    $lastNumber=$list[0]['NEXTVAL'];
    return str_pad($lastNumber,$fieldLength,'0',STR_PAD_LEFT);
  }




  //--------------------过期通宝
  //true 过期通宝已经成为有效通宝，false 更新失败  当天时间+补偿7天
  protected function  ifExpired(array  $consumeInfo){
 
    $date = date('Ymd');

    foreach ($consumeInfo as $value){
    	
      if($value['enddate']<$date){
        $reimbursedTimeStamp=3600*24*$this->reimbursedDay;
        // $reimbursedTime=strtotime($value['enddate'])+$reimbursedTimeStamp;
          $reimbursedTime=time()+$reimbursedTimeStamp;
          $enddate=date("Ymd",$reimbursedTime);

        // if($enddate<$date) {
        // $reimbursedTimeStamp=3600*24;
        // $reimbursedTime=strtotime($date)+$reimbursedTimeStamp;
        // $enddate=date("Ymd",$reimbursedTime);
        // }

        $this->coin_account->where(array("coinid"=>$value["ccoinid"]))->save(array("enddate"=>$enddate));
       
      }
    }
    return true;
  }
  //返回发行信息
  protected function  returnPublish(array $publish , $bool = false){
    $keys = array_unique(array_column($publish,'name'));
    $return = [];
    if($bool){
      if(count($publish)==count($keys)){
        foreach ($publish as $v){
          $return[] = ['name'=>$v['name'],'cut'=>$v['amount']];
        }
        return $return;
      }else{
        $arr = [];
        foreach ($publish as $key=>$val){
          if(isset($arr[$val['name']])){
            $arr[$val['name']]['cut'] = bcadd($arr[$val['name']]['cut'],$val['amount'],2);
          }else{
            $arr[$val['name']]  = ['name'=>$val['name'],'cut'=>$val['amount']];
          }
        }
        foreach ($arr as $val){
          $return[] = $val;
        }
        return $return;
      }
    }else{
      if(count($publish)==count($keys)){
        foreach ($publish as $v){
          $return[$v['name']] = ['name'=>$v['name'],'cut'=>$v['amount']];
        }
        return $return;
      }else{
        $arr = [];
        foreach ($publish as $key=>$val){
          if(isset($arr[$val['name']])){
            $arr[$val['name']]['cut'] = bcadd($arr[$val['name']]['cut'],$val['amount'],2);
          }else{
            $arr[$val['name']]  = ['name'=>$val['name'],'cut'=>$val['amount']];
          }
        }
        return $arr;
      }
    }

  }
    /**************************************************退款代码start********************************************/
public function test(){

	
}

/*
     * @查询通宝消费信息
     */
 private  function ConsumeInfo(array $map,string $field){

        $map['tw.tradetype']='00';
        $map['tw.flag']     = '0';
       return M('trade_wastebooks')->alias('tw')->join("left join coin_consume coin_c on tw.tradeid=coin_c.tradeid")->join("left join coin_account coin_a on coin_c.coinid=coin_a.coinid")->join("left join panters p on p.panterid=coin_a.panterid")->where($map)->field($field)->select();
                      
              
    }
    /*
     * @查询通宝冲正消费
     */
 private  function ConsumePositiveInfo(array $map,string $field){

        $map['tw.tradetype']='09';
        $map['tw.flag']     = '0';
       return M('trade_wastebooks')->alias('tw')->join("left join coin_consume coin_c on tw.tradeid=coin_c.tradeid")->join("left join coin_account coin_a on coin_c.coinid=coin_a.coinid")->join("left join panters p on p.panterid=coin_a.panterid")->where($map)->field($field)->select();
                      
              
    }

    //返回账户信息 即 主会员id 防止绑定账户信息 导致多个主会员id
 private function mainCustomid(array $cidArray){
      if(count($cidArray)==1){
        return M('customs_c')->where(['cid'=>$cidArray[0]])->field('customid')->find()['customid'];
      }else{
        $where['cid']  = ['in',$cidArray];
        $info   = M('customs_c')->where($where)->field('customid')->select();
        if($info){
          $unique = array_unique(array_column($info, 'customid'));
          if(count($unique)==1){
            return $unique['0'];
          }else{
            return false;
          }
        }else{
          return false;
        }
      }
  }
  //
 private function isRepeat($eorderid){
    $map['eorderid']  = $eorderid;
    $map['tradetype'] = '04';
    $info = M('trade_wastebooks')->where($map)->find();
    return $info==true;
  
  }
    /*
     * @验证签名
     */
    protected function valiSign($postKey,$localKey){
        if($postKey===$localKey)  return true;
        else return false;
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

   /**************************************************退款代码end**********************************************/


}