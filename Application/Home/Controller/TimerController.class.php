<?php
namespace Home\Controller;
use Home\Model\QifengModel;
use Think\Controller;
use Think\Model;

class TimerController extends Controller {
    //每天0点结算
    public function panterTradeDaily(){
        set_time_limit(0);
        ini_set ('memory_limit', '128M');
        $model=new Model();
        $where['tw.dayflag']=array('EXP','IS NULL');
        $where['tw.panterid']=array('EXP','IS NOT NULL');
        $where['tw.placeddate']=date('Ymd',time()-86400);
        $where['tw.flag']=0;
        $where['p.b_flag']=0;
        $where['tw.tradetype']=array('in','00,21,07');
        //$where['p.hysx']=array('neq','酒店');
        $where['c.cardkind']=array('not in',array('6882','2081','6880','6997'));
        //$where['p.panterid']=array('not in',array('00000286','00000290'));
        $where['tw.tradepoint']=0;
//            $c=$model->table('trade_wastebooks')->alias('tw')
//                ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
//                ->where($where)->field('tw.*,p.rakerate')->count();
//            echo $model->getLastSql();exit;
        $unbalancedTrade=$model->table('trade_wastebooks')->alias('tw')
            ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CARDS__ c on c.cardno=tw.cardno')
            ->where($where)->field('tw.cardno,tw.panterid,tw.termposno,tw.placeddate,tw.tradeid,tw.tradeamount,tw.tradepoint,p.rakerate')
            ->limit(0,5000)->select();
        //print_r($unbalancedTrade);exit;
        if($unbalancedTrade!==false){
            foreach($unbalancedTrade as $key=>$val){
                $map=array('panterid'=>$val['panterid'],'termposno'=>$val['termposno'],'statdate'=>$val['placeddate']);
                $panterTrade=M('trade_panter_day_books')->field('tradeamount,tradepoint,tradequantity,retailamount,proxyamount')->where($map)->find();
                $settleLogs[$key]['datetime']=date('Y-m-d H:i:s');
                $settleLogs[$key]['tradeid']=$val['tradeid'];
                $settleLogs[$key]['tradeamount']=$val['tradeamount'];
//                if(substr($val['cardno'],0,4)!='6688'){
//                    if(!empty($val['rakerate'])&&$val['rakerate']!==0){
//                        $jsamount=round(floatval($val['tradeamount']*$val['rakerate']/100),2);
//                        $sxamount=floatval($val['tradeamount']-$jsamount);
//                    }else{
//                        $jsamount=$val['tradeamount'];
//                        $sxamount=0;
//                    }
//                }else{
//                    $jsamount= round(floatval($val['tradeamount']*98.4/100),2);
//                    $sxamount=floatval($val['tradeamount']-$jsamount);
//                }
                if(!empty($val['rakerate'])&&$val['rakerate']!==0){
                    $jsamount=round(floatval($val['tradeamount']*$val['rakerate']/100),2);
                    $sxamount=floatval($val['tradeamount']-$jsamount);
                }else{
                    $jsamount=$val['tradeamount'];
                    $sxamount=0;
                }
                $model->startTrans();
                if($panterTrade!=false){
                    $tradeamount=$panterTrade['tradeamount']+$val['tradeamount'];
                    $tradequantity=$panterTrade['tradequantity']+1;
                    $retailamount=$panterTrade['retailamount']+$jsamount;
                    $proxyamount=$panterTrade['proxyamount']+$sxamount;
                    $data=array('tradeamount'=>$tradeamount, 'tradequantity'=>$tradequantity,'retailamount'=>$retailamount,'proxyamount'=>$proxyamount);
                    $tradePdbIf=$model->table('trade_panter_day_books')->where($map)->save($data);
                    if($tradePdbIf==false){
                        $settleLogs[$key]['msg']='更新当日结算报表失败';
                        $settleLogs[$key]['status']=0;
                        continue;
                    }
                }else{
                    $sql='insert into trade_panter_day_books(panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount)';
                    $sql.=" values('{$val['panterid']}','{$val['placeddate']}','{$val['termposno']}',{$val['tradeamount']},";
                    $sql.="1,{$val['rakerate']},'{$jsamount}','{$sxamount}')";
                    $tradePdbIf=$model->execute($sql);
                    if($tradePdbIf==false){
                        $settleLogs[$key]['msg']='更新当日结算报表失败';
                        $settleLogs[$key]['status']=0;
                        continue;
                    }
                }
                $data1['dayflag']='Y';
                $map1['tradeid']=$val['tradeid'];
                if($model->table('trade_wastebooks')->where($map1)->save($data1)){
                    $settleLogs[$key]['status']=1;
                    $model->commit();
                }else{
                    $settleLogs[$key]['msg']='更新订单状态失败';
                    $settleLogs[$key]['status']=0;
                    $model->rollback();
                    continue;
                }
            }
            if(!empty($settleLogs)){
                $this->settleLogs($settleLogs);
            }
        }
    }
    // 每天4点执行结算商户
    public function panterTradeDailys(){
        set_time_limit(0);
        ini_set ('memory_limit', '128M');
        $model=new Model();
        $where['tw.dayflag']=array('EXP','IS NULL');
        $where['tw.panterid']=array('EXP','IS NOT NULL');
        $startdate=date('Ymd',time()-86400);
        $enddate =date('Ymd',time());
        $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['tw.flag']=0;
        $where['p.b_flag']=1;
        $where['tw.tradetype']=array('in','00,21,07');
        //$where['p.hysx']=array('neq','酒店');
        $where['c.cardkind']=array('not in',array('6882','2081','6880','6997'));
        //$where['p.panterid']=array('not in',array('00000286','00000290'));
        $where['tw.tradepoint']=0;
//            $c=$model->table('trade_wastebooks')->alias('tw')
//                ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
//                ->where($where)->field('tw.*,p.rakerate')->count();
//            echo $model->getLastSql();exit;
        $unbalancedTrade=$model->table('trade_wastebooks')->alias('tw')
            ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CARDS__ c on c.cardno=tw.cardno')
            ->where($where)->field('tw.cardno,tw.panterid,tw.termposno,tw.placeddate,tw.tradeid,tw.tradeamount,tw.tradepoint,p.rakerate')
            ->limit(0,5000)->select();
        //print_r($unbalancedTrade);exit;
        if($unbalancedTrade!==false){
            foreach($unbalancedTrade as $key=>$val){
                $map=array('panterid'=>$val['panterid'],'termposno'=>$val['termposno'],'statdate'=>$val['placeddate']);
                $panterTrade=M('trade_panter_day_books')->field('tradeamount,tradepoint,tradequantity,retailamount,proxyamount')->where($map)->find();
                $settleLogs[$key]['datetime']=date('Y-m-d H:i:s');
                $settleLogs[$key]['tradeid']=$val['tradeid'];
                $settleLogs[$key]['tradeamount']=$val['tradeamount'];
//                if(substr($val['cardno'],0,4)!='6688'){
//                    if(!empty($val['rakerate'])&&$val['rakerate']!==0){
//                        $jsamount=round(floatval($val['tradeamount']*$val['rakerate']/100),2);
//                        $sxamount=floatval($val['tradeamount']-$jsamount);
//                    }else{
//                        $jsamount=$val['tradeamount'];
//                        $sxamount=0;
//                    }
//                }else{
//                    $jsamount= round(floatval($val['tradeamount']*98.4/100),2);
//                    $sxamount=floatval($val['tradeamount']-$jsamount);
//                }
                if(!empty($val['rakerate'])&&$val['rakerate']!==0){
                    $jsamount=round(floatval($val['tradeamount']*$val['rakerate']/100),2);
                    $sxamount=floatval($val['tradeamount']-$jsamount);
                }else{
                    $jsamount=$val['tradeamount'];
                    $sxamount=0;
                }
                $model->startTrans();
                if($panterTrade!=false){
                    $tradeamount=$panterTrade['tradeamount']+$val['tradeamount'];
                    $tradequantity=$panterTrade['tradequantity']+1;
                    $retailamount=$panterTrade['retailamount']+$jsamount;
                    $proxyamount=$panterTrade['proxyamount']+$sxamount;
                    $data=array('tradeamount'=>$tradeamount, 'tradequantity'=>$tradequantity,'retailamount'=>$retailamount,'proxyamount'=>$proxyamount);
                    $tradePdbIf=$model->table('trade_panter_day_books')->where($map)->save($data);
                    if($tradePdbIf==false){
                        $settleLogs[$key]['msg']='更新当日结算报表失败';
                        $settleLogs[$key]['status']=0;
                        continue;
                    }
                }else{
                    $sql='insert into trade_panter_day_books(panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount)';
                    $sql.=" values('{$val['panterid']}','{$val['placeddate']}','{$val['termposno']}',{$val['tradeamount']},";
                    $sql.="1,{$val['rakerate']},'{$jsamount}','{$sxamount}')";
                    $tradePdbIf=$model->execute($sql);
                    if($tradePdbIf==false){
                        $settleLogs[$key]['msg']='更新当日结算报表失败';
                        $settleLogs[$key]['status']=0;
                        continue;
                    }
                }
                $data1['dayflag']='Y';
                $map1['tradeid']=$val['tradeid'];
                if($model->table('trade_wastebooks')->where($map1)->save($data1)){
                    $settleLogs[$key]['status']=1;
                    $model->commit();
                }else{
                    $settleLogs[$key]['msg']='更新订单状态失败';
                    $settleLogs[$key]['status']=0;
                    $model->rollback();
                    continue;
                }
            }
            if(!empty($settleLogs)){
                $this->settleLogs($settleLogs);
            }
        }
    }
    function settleLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='订单：'.$val['tradeid']."  ";
            $msgString.='消费金额：'.$val['amount']."  ";
            if($val['status']==0){
                $msgString.='状态：结算失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：就算成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->reserveLogs('settle',$msgString);
    }
    public function reserveLogs($type,$string){

        $logPath=PUBLIC_PATH.'logs/'.$type.'/'.date('Ym',time()).'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$string,FILE_APPEND);
    }

    public function test(){
        header('content-type:text/html;charset=utf-8');
        $str="你好\r\n";

        $path=dirname(__FILE__);
        $path=str_replace("\\","/",$path);
        file_put_contents($path.'/text.txt',$str,FILE_APPEND);
    }
    function sendCoupon(){
  		$model=new Model();
  		$time=date("Ymd",strtotime("+1 month"));
  		$where['q.enddate']=array(array("lt",$time),array("gt",date('Ymd',time())),'and');

  		$field="a.quanid,a.customid a_cid,q.quanname,q.startdate,q.enddate,q.panterid,c.namechinese,c.sex,c.linktel";
  		$seed_list=$model->table('__ACCOUNT__ ')->alias('a')
              ->join('left join __QUANKIND__ q on q.quanid=a.quanid')
              ->join(' __PANTERS__ p on p.panterid=q.panterid')
              ->join('left join __CUSTOMS_C__ f on f.cid=a.customid')
              ->join(' __CUSTOMS__ c on c.customid=f.customid')
              ->field($field)
  			->where($where)
  			->select();
  		foreach($seed_list as $key=>$val){
  			$linktel=$val['linktel'];
  			$msg="您的".$val['quanname']."将在一月内(".$val['enddate'].")过期，请及时使用!";
  			$xml=$this->send_message($linktel,$msg);
  			$state=$xml->FailPhone->State;
  			$data=array('namechinese'=>$val['namechinese'],'linktel'=>$val['linktel'],'enddate'=>$val['enddate'],'quanid'=>$val['quanid'],'customid'=>$val['customid']);
  			if($state == '1'  || $state == 1){
  				$data['state']='发送成功';
  			}else{
  				$data['state']='发送失败';
  			}
  			$this->send1($data);

  			/* $timer=date("YmdHis",time());
  			$date=substr($timer,0,8);
  			$t=substr($timer,8);
  			if( $state=='1'){
  				$sql='insert into send(custno,custname,custtelo,consume,date,time)';
                  $sql.=" values('000000000','{$val['namechinese']}','{$val['linktel']}','发送成功','{$date}','{$t}')";
  				$model->execute($sql);
  			}else{
  				$sql='insert into sendfail(custno,custname,custtelo,consume,date,time)';
                  $sql.=" values('000000000','{$val['namechinese']}','{$val['linktel']}','发送失败','{$date}','{$t}')";
  				$model->execute($sql);
  			} */
  		}
	}
	function send1($arr){
		$msgString='';
		$msgString.='券编号：'.$arr['quanid']."   所属会员：".$arr['namechinese']."  联系方式：".$arr['linktel']."  过期时间：".$arr['enddate']."  状态：".$arr['state'];
        $msgString.="\r\n\r\n";
		$this->reserveLogs('sendCoupon',$msgString);
	}
	function send_message($phone,$msg) {
		if(empty($phone)||empty($msg)){
			return false;
		}
        $jgid = '413';
        //短信接口用户名 $loginname
        $loginname = 'jyzz1';
        //短信接口密码 $passwd
        $passwd = 'jyzz';
        $msg =  urlencode($msg);
        $gateway = "http://116.255.233.131:8180/service.asmx/SendMessage?Id={$jgid}&Name={$loginname}&Psw={$passwd}&Message={$msg}&Phone={$phone}&Timestamp=0";
        $result = simplexml_load_file($gateway);
        return $result;
    }

    function consumePushMsg(){
        $nowTime=time();
        $ymd=date('Ymd',$nowTime);
//        $content=file_get_contents("./Public/pushMessage/".$ymd.".txt");
        $content=file_get_contents(PUBLIC_PATH."pushMessage/".$ymd.".txt");
        $a=explode("\r\n",$content);
        foreach($a as $k=>$v){
            $b=explode("|",$v);
            if($b[4]<=$nowTime&&$b[4]>$nowTime-60){
                $arr[]=$b;
            }
        }
        foreach($arr as $key=>$val){
            $orderid=$val[0];
            $consumeAmount=$val[1];
            $coinAmount=$val[2];
            $backUrl=$val[3];
            $backData=array('orderid'=>$orderid,'consumeAmount'=>$consumeAmount,'coinAmount'=>$coinAmount,'payRes'=>1);
            crul_post(urldecode($backUrl),json_encode($backData));
        }
        //dump($arr);
        $content2=implode("\r\n",$arr);
//        file_put_contents("./Public/pushMessage/".$ymd."success.txt",$content2,FILE_APPEND);
        file_put_contents(PUBLIC_PATH."pushMessage/".$ymd."success.txt",$content2,FILE_APPEND);
    }
    public function outDaliyJycoin(){
        $model          = new Model();
	    $panters        = $model->table('panters')->where(['parent'=>'00000927'])->field('panterid')->select();
	    $outpanter      = array_column($panters,'panterid');
//        $outpanter      = [
//            '00002974','00003619','00003634','00003756',
//            '00003755','00003754','00003720','00004096','00004135','00004134','00004154',
//            '00004155','00004118','00004119','00004080','00004095','00004079',
//            '00004094','00004041','00004040','00004037','00004074','00004039','00004036',
//            '00004075','00004076','00004035','00004054','00003967','00003966',
//	        //-2018 -04-16
//	        '00004834','00004835',
//	        //-2018-04-17
//	        '00004874','00003618','00005003','00005002','00005034','00004934','00005148',
//	        //2018 05 09
//	        "00005035","00005056","00005057","00005011","00005013","00005054","00005017",
//	        "00004999","00005009","00005036","00005039","00005040","00005010","00005042",
//	        "00005043","00005044","00005045","00005004","00005005","00005046","00005047",
//	        "00005018","00005000","00005006","00005008","00004998","00005007","00005037",
//	        "00005038","00005041","00005012","00005048"
//        ];
        $placeddate     = date('Ymd',time()-86400);
//        $placeddate     = '20171113';
        $map['panterid']= ['in',$outpanter];
        $map['placeddate']=$placeddate;
        $map['tradetype'] = '00';
        $field          = 'panterid,nvl(sum(tradepoint),0) jycoin, placeddate';
        $subQuery       = M('trade_wastebooks')->where($map)->field($field)
                                                     ->group('panterid,placeddate')
                                                     ->buildSql();
        $dField ='tw.panterid,tw.jycoin,tw.placeddate,p.rakerate rate';
        $daliy  = $model->table($subQuery." tw") //$subSql." sub"
                        ->join('left join panters p on p.panterid=tw.panterid')
                        ->field($dField)->select();
        $head = "INSERT ALL ";
        $body = "";
        foreach ($daliy as $val){
            if($val['jycoin']>0){
                $settle  = bcdiv(bcmul($val['jycoin'],$val['rate'],2),100,2);
                $poundage= bcsub($val['jycoin'],$settle,2);
                $body.= " into out_daliy_jycoin(panterid,tradeamount,settleamount,poundage,placeddate,sync) VALUES ";
                $body.= "('{$val['panterid']}','{$val['jycoin']}','{$settle}','{$poundage}','{$placeddate}','0') ";
            }
        }
        if($body!=''){
            $sql = $head.$body."SELECT 1 FROM DUAL";
            $model->execute($sql);
        }
    }
    public function outDaliyTotal(){
        $model          = new Model();
	    $panters        = $model->table('panters')->where(['parent'=>'00000927'])->field('panterid')->select();
	    $outpanter      = array_column($panters,'panterid');
//        $outpanter      = [
//            '00002974','00003619','00003634','00003756',
//            '00003755','00003754','00003720','00004096','00004135','00004134','00004154',
//            '00004155','00004118','00004119','00004080','00004095','00004079',
//            '00004094','00004041','00004040','00004037','00004074','00004039','00004036',
//            '00004075','00004076','00004035','00004054','00003967','00003966',
//	        //-2018 -04-16
//	        '00004834','00004835',
//	        //-2018-04-17
//	        '00004874','00003618','00005003','00005002','00005034','00004934','00005148',
//	        //2018 05 09
//	        "00005035","00005056","00005057","00005011","00005013","00005054","00005017",
//	        "00004999","00005009","00005036","00005039","00005040","00005010","00005042",
//	        "00005043","00005044","00005045","00005004","00005005","00005046","00005047",
//	        "00005018","00005000","00005006","00005008","00004998","00005007","00005037",
//	        "00005038","00005041","00005012","00005048"
//        ];
        $placeddate     = date('Ymd',time()-86400);
//        $placeddate     = '20171113';
        $map['panterid']= ['in',$outpanter];
        $map['placeddate']=$placeddate;
        $map['tradetype'] = '00';
        $field          = 'panterid,nvl(sum(tradepoint),0)+nvl(sum(tradeamount),0) total, placeddate';
        $subQuery       = M('trade_wastebooks')->where($map)->field($field)
                                                ->group('panterid,placeddate')
                                                ->buildSql();
        $dField ='tw.panterid,tw.total,tw.placeddate,p.rakerate rate';
        $daliy  = $model->table($subQuery." tw") //$subSql." sub"
        ->join('left join panters p on p.panterid=tw.panterid')
            ->field($dField)->select();
        $head = "INSERT ALL ";
        $body = "";
        foreach ($daliy as $val){
            if($val['total']>0){
                $settle  = bcdiv(bcmul($val['total'],$val['rate'],2),100,2);
                $poundage= bcsub($val['total'],$settle,2);
                $body.= " into out_daliy_total(panterid,tradeamount,settleamount,poundage,placeddate) VALUES ";
                $body.= "('{$val['panterid']}','{$val['total']}','{$settle}','{$poundage}','{$placeddate}') ";
            }
        }
        if($body!=''){
            $sql = $head.$body."SELECT 1 FROM DUAL";
            $model->execute($sql);
        }
    }
    //定时任务每天晚上0点更新密码次数
    public function Timed_task()
    {
        $ca = M('cards');
        $ca->startTrans();
//        $sql="update cards set cardflag='0' where cardflag!='0'";
        $sql = $ca->where('cardflag' != '0')->save(array('cardflag' => '0'));
        if (!$sql) {
           $ca->rollback();
        } else {
            $ca->commit();
        }

    }
	//通宝过期消费
	public function jycoinExpiredConsume() {
        
		$panterid                  = '00004616';
        $now                       = date( 'Ymd' );
        $enddate                   = I('get.enddate') == ''?date('Ymd', time() - 86400 ):I('get.enddate');
		$time                      = strtotime($now);
		$termno                    = '00000002';
		$tradetype                 = '27';
		$userid                    = '0000000000000000';
		$map['coin.remindamount'] = [ 'gt', '0' ];
		$map['coin.enddate']       = $enddate;
		$field                     = "coin.remindamount,coin.cardid,coin.coinid,coin.accountid,ca.cardno";
		$model                     = new Model();
		$info                      = $model->table( 'coin_account' )->alias( 'coin' )
		                                   ->join( "left join cards ca on coin.cardid=ca.customid " )
		                                   ->where($map)->field($field)->select();
		$tradesql  = '';
		$consumeSql= '';
		$accountSql= '';
		if ($info) {
			foreach ( $info as $coin ) {
				$tradeid      = substr( $coin['cardno'], 15, 4 ) . rand( 1, 999 ) . $now . '000000';
				$tradesql    .= " INTO trade_wastebooks (termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,flag)";
				$tradesql    .= " VALUES('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$now}','0','{$coin['remindamount']}','{$coin['cardid']}','{$coin['cardno']}','00:00:00','{$tradetype}','0')";

				$conconsumeid = $this->getFieldNextNumber('coinconsumeid');
				$consumeSql  .= " INTO coin_consume VALUES ('{$conconsumeid}','{$tradeid}','{$coin['cardid']}','{$coin['coinid']}',";
				$consumeSql  .= "{$coin['remindamount']},'{$now}','00:00:00','{$panterid}','1','1','1','{$time}','{$userid}')";

//				$accountSql  .= " WHEN '{$coin['accountid']}' THEN {$coin['remindamount']} ";
				$coinid[]     = $coin['coinid'];
//				$account[]    = $coin['accountid'];

                $deduct[] = ['cardid'=>$coin['cardid'],'amount'=>$coin['remindamount']];
			}
			$head = "INSERT ALL ";
			$end  = " select 1 from dual";

//			$accountSql = "UPDATE ACCOUNT SET amount = amount - CASE accountid ".$accountSql." END WHERE accountid in (".$this->inString($account).")";
            $accountSql = $this->getAccountSql($deduct);


			$model->startTrans();
			try{
				$tradeIf   = $model->execute($head.$tradesql.$end);
				$consumeIf = $model->execute($head.$consumeSql.$end);
				$coinIf    = $model->table('coin_account')->where(['coinid'=>['in',$coinid]])->save(['remindamount'=>'0']);
				$accountIf = $model->execute($accountSql);
				if($tradeIf && $consumeIf && $coinIf && $accountIf){
					$model->commit();
					echo '成功';
//				return true;
				}else{
					$model->rollback();
					file_put_contents('jycoinExpiredConsume.txt',$now.":消费过期通宝失败"."\t\n",8);
				}
			}catch (\Exception $e){
				$model->rollback();
				file_put_contents('jycoinExpiredConsume.txt',$now.":消费过期通宝失败"."\t\n",8);
			}
		}else{
			echo '无数据';
		}

	}

    //天筑会积分过期处理 定时任务
    public function QtzPoint(){
        $model  = new Model();
        $panterid = '00000792';
        $where['pa.panterid'] = $panterid;
//        $where['pa.enddate'] < date('Ymd');
//        $enddate = '20180302';
//        $where['pa.enddate'] =array('lt',$enddate);
        $where['pa.enddate'] =array('lt',date('Ymd'));
        $where['pa.remindamount'] = array('gt', 0);
        $list = M('cards')->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where)->field('c.cardno,pa.cardid,pa.pointid,pa.remindamount,pa.enddate')->select();
//         echo M('cards')->getLastSql();
//            dump($list);exit;
//        $tradesql  = '';
//        $consumeSql= '';
//        $accountSql= '';
        $now  = date( 'Ymd' );
        if($list){
            $pante = '00007434';
            foreach ($list as $point){
                $tradeid      = substr( $point['cardno'], 15, 4 ) . rand( 1, 999 ) . $now . '000000';
                $currentDate = date('Ymd');
                $currentTime = date('H:i:s');
                $data['cardno'] = $point['cardno'];
                $data['customid'] = $point['cardid'];
                $data['pointid'] = $point['pointid'];
                $data['remindamount'] = $point['remindamount'];
                $data['enddate'] = $point['enddate'];
                $sql = "insert into tz_wastebooks values('{$tradeid}','00000000','{$point['cardno']}','0','{$point['tradepoint']}',";
                $sql .= "'{$currentDate}','{$currentTime}','{$pante}','00','0','1','{$point['pointid']}','','{$point['remindamount']}',1)";
                $tradeIf = $model->execute($sql);
                $pointSql = "UPDATE point_account set remindamount=remindamount-{$point['remindamount']} where pointid='{$point['pointid']}'";
                $pointIf = $model->execute($pointSql);
                $accountSql = "UPDATE account set amount=amount-{$point['remindamount']} where customid='{$point['cardid']}' and type='04'";
                $accountIf = $model->execute($accountSql);
                $pointconsumeid = $this->getFieldNextNumber('pointconsumeid');
                $pointConsumeSql = "INSERT INTO point_consume values('{$pointconsumeid}','{$tradeid}','{$point['cardid']}',";
                $pointConsumeSql .= "'{$point['pointid']}','{$point['remindamount']}','{$currentDate}','{$currentTime}','{$pante}',0,1)";
                $pointConsumeIf = $model->execute($pointConsumeSql);

                if ($tradeIf == true && $pointIf == true && $accountIf == true && $pointConsumeIf == true) {
                    $data['msg'] ='过期消费成功';
                    $this->recordData(json_encode($data), '');
                    echo '过期消费成功';
                }else{
                    $data['msg'] ='过期消费失败';
                    $this->recordData(json_encode($data), '');
                    echo '过期消费失败';
                }

            }


        }else{
            echo '无数据';
        }


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
	protected function getFieldNextNumber($field){
		$fieldLength = [
			'conconsumeid'    => '10',
			'cardpurchaseid'=> '18',
			'coinconsumeid'=> '10',
            'pointconsumeid'=>'10'
		];
		$model  = new Model();
		$seq_field='seq_'.$field.'.nextval';
		$sql="select {$seq_field} from dual";
		$list=$model->query($sql);
		$fieldLength=$fieldLength[$field];
		$lastNumber=$list[0]['nextval'];
		return str_pad($lastNumber,$fieldLength,'0',STR_PAD_LEFT);
	}
	protected function inString($arr){
		$str='';
		foreach($arr as $val){
			$str.="'".$val."',";
		}
		return rtrim($str,',');
	}
    protected function recordData($data,$flag){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Pos/tianzhu/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Pos/tianzhu/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }


    //qf 统计余额任务

    public function qfDailyBalance(){
        $placeddate = date('Ymd',(time()-86400));
        $balanceWhere=['ca.cardkind'=>'6997','ac.type'=>'00'];
        $balance = QifengModel::balanceInfo($balanceWhere);

        $map['placeddate'] = $placeddate;

        //充值金额
        $map['flag']  = ['in',['01','05','06']];
        $charge       = QifengModel::sumAmount($map,'price');

        $map['flag']  = '02';
        $consume      = QifengModel::sumAmount($map,'payamount');

        $map['flag'] = '04';
        $refund      = QifengModel::sumAmount($map,'payamount');

        $map['flag'] = '03';
        $return      = QifengModel::sumAmount($map,'payamount');

        $sql         = "insert into qf_daily_detail(placeddate,balance,charge,consume,refund,return) VALUES(";
        $sql        .= "'{$placeddate}','{$balance}','{$charge}','{$consume}','{$refund}','{$return}')";

        $model      =  new Model();
        $model->execute($sql);
    }

    /**
     * 自有资金消费结算归档结算
     */
    public function zy_archive()
    {
        $ymd = I('get.statdate');
        if (empty($ymd)) {
            $ymd = date('Ymd', time() - 86400); //凌晨二点执行！！！
        }
        $data = M('zzk_settlement')->where(array('statdate' => $ymd))->field('statdate')->find();
        if ($data) {
            return false;
        }

        $where = array('zd.placeddate' => $ymd, 'zd.tradetype' => '50');
        $this->writeLogs("zy_archive",date("Y-m-d H:i:s") . "-记录起始\n\t订单号：" . serialize($where). "\n\t");

        $list = M('zzk_account_detail')->alias('zd')
            ->join('zzk_order zo on zd.order_sn=zo.inner_order')
            ->join('panters p on zo.panterid=p.panterid')
            ->where($where)
            ->field('zo.panterid,sum(zd.charge_amount) as money,p.rakerate,zd.placeddate,count(*) as num')
            ->group('zo.panterid,p.rakerate,zd.placeddate')->select();
        
        $this->writeLogs("zy_archive","查询的数据：\n\t" . serialize($list) . "\n\t");
        // print_r($list);exit;
        $model = new model();
        foreach ($list as $key => $val) {
            $money = (float)$val['money'];
            $jsamount = $money * ($val['rakerate'] / 100);
            $sxamount = $money - $jsamount;
            $settlementid = $this->zzkgetnumstr('ZZK_SETTLEMENTID', 8);
                                                             //商户id 开始日期 终端号 实际交易金额 交易积分 交易卷数量 折扣率 与商户结算金额 原金额 
            $sql = 'insert into ZZK_SETTLEMENT(settlementid,panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount,num)';
            $sql .= " values('{$settlementid}','{$val['panterid']}','{$val['placeddate']}','00000000',{$money},";
            $sql .= "1,{$val['rakerate']},'{$jsamount}','{$sxamount}','{$val['num']}')";
            $this->writeLogs("zy_archive","SQL语句：" . serialize($sql) . "\n\t");
            // echo $sql;
            $model->execute($sql);
        }

    }

    /** #
     * 序列号
     * 
     * @param $table string 表名
     * @param $lennum int 字符长度
     * @return $str string 增加长度后的字符
     */
    function zzkgetnumstr($table, $lennum)
    {
        $model = new Model();
        $id = $model->query("select $table.nextval from dual")[0]['nextval'];
        $snum = strlen($id);
        $x = '';
        for ($i = 1; $i <= $lennum - $snum; $i++) {
            $x .= '0';
        }
        return $x . $id;
    }
     
    /**
     * 日志记录
     */
    public function writeLogs($module,$msgString){
        $month=date('Ym',time());
        switch($module){
            case 'batchConsume':$logPath=PUBLIC_PATH.'logs/batchConsume/';break;
            case 'batchRecharge':$logPath=PUBLIC_PATH.'logs/batchRecharge/';break;
            case 'cardbatchbuy':$logPath=PUBLIC_PATH.'logs/cardbatchbuy/';break;
            case 'createCards':$logPath=PUBLIC_PATH.'logs/createCards/';break;
            case 'vipCard':$logPath=PUBLIC_PATH.'logs/vipCard/';break;
            case 'cardinexcel':$logPath=PUBLIC_PATH.'logs/cardinexcel/';break;
            case 'carduppwd':$logPath=PUBLIC_PATH.'logs/carduppwd/';break;
            case 'opencard':$logPath=PUBLIC_PATH.'logs/opencard/';break;
            default :$logPath=PUBLIC_PATH."logs/$module/";
        }
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$msgString,FILE_APPEND);
    }

    public function writeLogs_test()
    {
        $module = 'WdailyReport';
        $msgString = 'fdasfdsafdsafsda测试';
        $month=date('Ym',time());
        switch($module){
            case 'batchConsume':$logPath=PUBLIC_PATH.'logs/batchConsume/';break;
            case 'batchRecharge':$logPath=PUBLIC_PATH.'logs/batchRecharge/';break;
            case 'cardbatchbuy':$logPath=PUBLIC_PATH.'logs/cardbatchbuy/';break;
            case 'createCards':$logPath=PUBLIC_PATH.'logs/createCards/';break;
            case 'vipCard':$logPath=PUBLIC_PATH.'logs/vipCard/';break;
            case 'cardinexcel':$logPath=PUBLIC_PATH.'logs/cardinexcel/';break;
            case 'carduppwd':$logPath=PUBLIC_PATH.'logs/carduppwd/';break;
            case 'opencard':$logPath=PUBLIC_PATH.'logs/opencard/';break;
            default :$logPath=PUBLIC_PATH."logs/$module/";
        }
        echo $logPath=$logPath.$month.'/';
        echo '<br>';
        echo $filename=date('Ymd',time()).'.log';
        echo '<br>';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            $rs = mkdir($logPath,0777,true);
            var_dump($rs);
            echo '<br>';
        }
        $rs = file_put_contents($filename,$msgString,FILE_APPEND);
        var_dump($rs);
    }

    /**
     * 商家结算回调
     */
    public function dailyreport_callback()
    {
        $this->writeLogs("WdailyReport", date("Y-m-d H:i:s") . "-回调记录起始：\n\t");
        $list = file_get_contents('php://input');

        //结转备付金回调
        $this->https_request('http://106.3.45.147:9010/index.php/index/index/carried_callback', $list);//测试
        // $this->https_request('http://106.3.45.156:1987/index.php/index/index/carried_callback', $list);//正式

        $this->writeLogs("WdailyReport", "请求数据：\n\t" . $list . "\n\t");
        $v = json_decode($list, true);
        $this->writeLogs("WdailyReport", "请求数据：\n\t" . serialize($list) . "\n\t");
        $error_type = ['01', '02', '06', '10', '11', '12', '13', '14', '31', '32', '34', '35', '36', '37', '38', '39', '41', '43', '60', '61', '63', '66', '68', '73', '78', '90', '92', '91', '93'];
        try {
            $field = '*';
            $where = explode('.', $v['o_sn']);

            $map = ['panterid' => $where[0], 'statdate' => $where[1], 'termposno' => $where[2], 'id' => $where[3], 'code' => $v['cid']];
            // $this->writeLogs("WdailyReport", "查询数据：\n\t" . serialize($map) . "\n\t");
            $res = M('trade_panter_day_books')->where($map)->field($field)->find();
            $this->writeLogs("WdailyReport", "查询数据：\n\t" . serialize($res) . "\n\t");
            if ($res) {
                if ($res['type'] == $v['transstatus']) {
                    $this->writeLogs("WdailyReport", "返回的信息：状态码一样" . "\n\t\n\t");
                    exit("状态码一样");
                }
                if (in_array($res['type'], $error_type)) {
                    //禁止同一订单失败1次以上
                    $this->writeLogs("WdailyReport", "返回的信息：禁止同一订单失败1次以上" . "\n\t\n\t");
                    exit("禁止同一订单失败1次以上");
                }
                $save_data = ['type' => $v['transstatus'], 'type_msg' => $v['code']];
                $rs = M('trade_panter_day_books')->where($map)->save($save_data);
                $this->writeLogs("WdailyReport", "修改结算表的状态：" . serialize($rs) . '-修改的数据：-' . serialize($save_data) . "\n\t");
                if (in_array($v['transstatus'], $error_type)) {
                    $num_map = ['panterid' => $where[0], 'statdate' => $where[1], 'termposno' => $where[2]];
                    $num = M('trade_panter_day_books')->where($num_map)->field('panterid,statdate,termposno,id')->select();
                    $this->writeLogs("WdailyReport", "查询该订单次数数据：\n\t" . count($num) . '--' . serialize($num) . "\n\t");
                    if (count($num) > 2) {
                        $this->writeLogs("WdailyReport", "查询数据：\n\t" . serialize($num) . "\n\t");
                        $this->writeLogs("WdailyReport", "返回的信息：该天商户已执行3次，请联系管理员！！！" . "\n\t\n\t");
                        exit("该天商户已执行3次，请联系管理员");
                    }

                    unset($res['numrow']);
                    $model = new Model();
                    $res['id']++;
                    $res['batchno'] = null;
                    $res['code'] = null;
                    $res['status'] = 0;
                    $sql = $this->addSql('trade_panter_day_books', $res);
                    $rs = $model->execute($sql);
                    $this->writeLogs("WdailyReport", "添加结算表的状态：" . serialize($rs) . '-SQL语句：-' . serialize($sql) . "\n\t");
                    $this->writeLogs("WdailyReport", "返回的信息：订单失败，修改订单状态和生成新结算数据成功" . "\n\t\n\t");
                    exit("订单失败，修改订单状态和生成新结算数据成功");
                }
                $this->writeLogs("WdailyReport", "返回的信息：订单状态修改成功" . "\n\t\n\t");
                exit("订单状态修改成功");
            }

        } catch (\Exception $e) {
            $this->writeLogs("WdailyReport", "返回的信息：修改失败，请查看异常信息" . "\n\t");
            $this->writeLogs("WdailyReport", "异常信息：" . $e . "\n\t\n\t");
            exit("修改失败，请查看异常信息");
        }
    }

    /**
     * 获取oracle install 语句
     * 
     * @param $table string 表名
     * @param $data array 要添加的数据
     * @param $field array 要去除或者保留的字段
     * @param $type bool false去除 true保留 字段
     * @return $sql string 返回sql语句
     */
    function addSql($table, $data, $field = [], $type = true)
    {
        $list = $data;
        if (!empty($field)) {
            $list = $type == true ? [] : $data;
            foreach ($field as $k => $v) {
                if ($type == true) {
                    $list[$v] = $data[$v];
                } else {
                    unset($list[$v]);
                }
            }
        }
        $sql = "INSERT INTO {$table} (" . implode(',', array_keys($list)) . ')';
        $sql .= ' SELECT \'' . implode('\',\'', array_values($list)) . '\' FROM DUAL';
        return $sql;
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

}
