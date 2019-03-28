<?php
namespace Pos\Controller;

use Org\Util\DESedeCoder;
use Think\Controller;
use Think\Model;

class FootballTownController extends Controller
{
	protected $des;
	protected $model;
	protected $keysign;
	protected $prefix;
	public function _initialize(){
		$this->des =  new DESedeCoder('GDgLwwdK27020180622lyTp');

		$this->model = new Model();

		$this->keysign = 'zzk_jyzz_2018';

		$this->prefix  = 'fbt_';
	}
	public function getAccountByPhone() {
		$post  = $this->decodeDesData();
		$phone = $post['phone'];
		$key   = $post['key'];
		$this->checkSign($key,md5($this->keysign.$phone));

		$count = intval($this->model->table('customs')->where(['linktel'=>$phone])->count());

		if($count===1){
			$info = $this->model->table('customs')->where(['linktel'=>$phone])->field('customid,paypwd')->find();
			$customid= $info['customid'];
			$return['balance']  = $this->getAccountAmount('00',$customid);
			$return['jycoin']   = $this->getAccountAmount('01',$customid);
			$return['customid'] = encode($customid);
//			$bool = $this->model->table('zzk_account')->where(['phone'=>$phone])->field('paypwd')->find();
//			if($bool){
//				if(is_null($info['paypwd']) && is_null($bool['paypwd'])){
//					$return['paypwd']= null;
//				}elseif($info['paypwd'] && $bool['paypwd']){
//					if($info['paypwd']==$bool['paypwd']){
//						$return['paypwd'] = $info['paypwd'];
//					}else{
//						returnMsg(['status'=>'010','codemsg'=>'账户密码异常']);
//					}
//				}else{
//					$return['paypwd'] = is_null($info['paypwd'])?$bool['paypwd']:$info['paypwd'];
//					$this->editPwd($info['paypwd'],$bool['paypwd'],$phone);
//				}
//			}else{
//				$return['paypwd'] = $info['paypwd'];
//			}
//			if(!is_null($return['paypwd']))$return['paypwd']=$this->encodePwd($return['paypwd']);
			returnMsg(['status'=>'1','codemsg'=>'ok','data'=>$return]);
		}else{
			if($count===0){
				returnMsg(array('status'=>'04','codemsg'=>'该手机未绑定会员'));
			}else{
				returnMsg(array('status'=>'03','codemsg'=>'会员信息异常'));
			}
		}
	}
	public function isPayPwd(){
        $post  = $this->decodeDesData();
        $customid = $post['customid'];
        $key   = $post['key'];
        $this->checkSign($key,md5($this->keysign.$customid));

        $customid = decode($customid);
        $info     = $this->model->table('customs')->where(['customid'=>$customid])->find();
        if(! $info) returnMsg(array('status'=>'03','codemsg'=>'查无此会员'));
        if(is_null($info['paypwd'])) returnMsg(['status'=>'2','codemsg'=>'未设置密码']);
        else  returnMsg(['status'=>'1','codemsg'=>'已设置密码']);
    }
    public function pos_en_post(){
        $post  = $this->decodeDesData();
        $outid = $post['outid'];
        $key   = $post['key'];
        $this->checkSign($key,md5($this->keysign.$outid));
        $outids = decode($outid);
        $where['z.outid'] = $outids;
        $where['p.forbid'] = 0;
        $where['t.auth_status'] = 1;
        $field= "t.pos_en";
        $info     = $this->model->table('zzk_store')->alias('z')
            ->join('left join zzk_pos_config p  on z.outid= p.outid')
            ->join('left join zzk_tpos t on t.imei = p.imei')
            ->field($field)
            ->where($where)->find();
        // dump($info);exit;
        if(!$info){
            returnMsg(array('status'=>'01','codemsg'=>'查无此pos'));
        }else{
            returnMsg(['status'=>'1','codemsg'=>'查询成功','pos_en'=>$info['pos_en']]);
        }

    }
    public function setPayPwd(){
        $post  = $this->decodeDesData();
        $customid = $post['customid'];
        $paypwd   = $post['paypwd'];
        $key   = $post['key'];
        $this->checkSign($key,md5($this->keysign.$paypwd.$customid));
        $customid = decode($customid);
        $info     = $this->model->table('customs')->where(['customid'=>$customid])->find();
        if(! $info) returnMsg(array('status'=>'03','codemsg'=>'查无此会员'));
        $paypwd = $this->decodePwd($paypwd);

        $bool = $this->model->table('customs')->where(['customid'=>$customid])->save(['paypwd'=>$paypwd]);
        if($bool) returnMsg(['status'=>'1','codemsg'=>'设置密码成功']);
        else returnMsg(['status'=>'07','codemsg'=>'修改数据库失败']);
    }

	public function panter(){
		$post  = $this->decodeDesData();
//        $panterid = $post['panterid'];
    //    dump($post);exit;
        $name = $post['namechinese'];
        $pantertype = $post['pantertype'];
        $tel = $post['goingteleno'];
        $card_name = $post['conpername'];
        $linktel = $post['conperteleno'];
        $linktels = $post['conperteleno'];
        $card_id = $post['conperbpno'];
        $parent = $post['parent'];
        $address = $post['address'];
        $hysx = $post['hysx'];
        $settname = $post['settleaccountname'];
        $bank =  $post['settlebank'];
        $zhibank = $post['settlebankname'];
        $bankcard = $post['settlebankid'];
        $licenseimg = $post['licenseimg'];
        $doorplateimg = $post['doorplateimg'];
        $business = $post['business'];
        $legalperson = $post['legalperson'];
        $placeddate =   $post['placeddate'];
        $idface = $post['idface'];
        $idcon = $post['idcon'];
//        $status = $post['status'];
        $accounttype = $post['accounttype'];
        $key = $post['key'];
        $stoppayflag = 'N';
        $revorkflg = 'N';
        $settletime = '30';
        $rakerate = '100';
        $flag = '3';
        $conpertype = "身份证";
        $cityid = '371';
        $this->checkSign($key,md5($this->keysign.$name.$tel.$card_name.$card_id));
        $model = new Model();
        $panterid=$this->getFieldNextNumber("panterid");
        $userid =  $this->userid;
        $pant =$model->table('panters')->where(['panterid'=>$panterid])->field('panterid')->find();
        $panter_name = $model->table('panters')->where(['namechinese'=>$name])->field('namechinese')->find();
        if($panter_name){
            returnMsg(array('status'=>'04','codemsg'=>'商户名称已存在'));
        }
        if($pant){
            returnMsg(array('status'=>'01','codemsg'=>'商户已存在'));
        }else{
            $psql="INSERT INTO panters(panterid,conpername,namechinese,nameenglish,cityid,conpermobno,conperteleno,address,operatescope,organizationcode,business,taxation,conperbtype,legalperson,";
            $psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,placeddate,idface,idcon,timevalue,orzimg,taximg,status,accounttype,pantertype,settleaccountname,settlebank,settlebankname,settlebankid) VALUES ";
            $psql.="('".$panterid."','".$card_name."','".$name."','".$name."','".$cityid."','".$linktel."','";
            $psql.=$linktels."','".$address."','','".$business."','".$business."','','".$conpertype."','".$legalperson."','".$card_id."','".$revorkflg."','".$parent."','FFFFFFFF','".$hysx."','".$tel."','".$stoppayflag."','".$rakerate."','".$settletime."','".$flag."','".$licenseimg."','".$doorplateimg;
            $psql.="','','','".$placeddate."','".$idface."','".$idcon."','','','',".'0'.",'".$accounttype."','".$pantertype."','".$settname."','".$bank."','".$zhibank."','".$bankcard."')";
            $fxsql="INSERT INTO panter_con_account values ('".$panterid."','0','0','0','".$userid."')";
            $model->startTrans();
            if($model->execute($psql)){
                if($model->execute($fxsql)){
                    $model->commit();
                    returnMsg(array('status'=>'1','panterid'=>$panterid,'codemsg'=>'添加商户成功'));
                }else{
                    $model->rollback();
                    returnMsg(array('status'=>'02','codemsg'=>'添加商户失败')) ;
                }
            }else{
                $model->rollback();
                returnMsg(array('status'=>'03','codemsg'=>'添加商户失败')) ;
            }
        }
	}

	public function store(){
		$post  = $this->decodeDesData();
		//dump($post);exit;
		$panterid = $post['panterid'];
		$outid  = $post['storeid'];
		$name     =  $post['name'];
		$key      = $post['key'];
		$this->checkSign($key,md5($this->keysign.$panterid.$outid.$name));
		$model = new Model();
		if($model->table('panters')->where(['panterid'=>$panterid])->field('panterid')->find()){
			!($model->table('zzk_store')->where(['outid'=>$outid])->field('outid')->find())
			|| returnMsg(array('status'=>'06','codemsg'=>'改门店已经存在'));
			$placeddate = date('Ymd');
			$placedtime = date('H:i:s');
			$storeid    = $this->getPrimarykey('zzk_storeid','8');
			$sql = "insert into zzk_store(storeid,panterid,outid,name,placeddate,placedtime) VALUES ";
			$sql.= " ('{$storeid}','{$panterid}','{$outid}','{$name}','{$placeddate}','{$placedtime}')";
			$bool = $model->execute($sql);
			if($bool){
				returnMsg(array('status'=>'1','codemsg'=>'新增门店成功'));
			}else{
				returnMsg(array('status'=>'05','codemsg'=>'新增门店失败'));
			}
		}else{
			returnMsg(array('status'=>'04','codemsg'=>'查无此商户'));
		}
	}
	//通宝余额消费
	public function consume(){
        $post = $this->decodeDesData();
		$this->recordData($post);
		$customid      = trim($post['customid']);
		$balanceAmount = trim($post['balanceAmount']);
		$coinAmount    = trim($post['coinAmount']);
		$payPwd        = trim($post['payPwd']);
		$key           = trim($post['key']);
		$orderid       = trim($post['orderId']);
		$panterid      = trim($post['panterid']);
		$storeid       = trim($post['storeid']);
		$source    = trim($post['source']);


		$checkKey=md5($this->keysign.$customid.$balanceAmount.$coinAmount.$orderid.$payPwd.$panterid.$storeid.$source);
		$this->checkSign($key,$checkKey);
		if(empty($customid)){
			returnMsg(array('status'=>'03','codemsg'=>'用户缺失'));
		}
		if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
			returnMsg(array('status'=>'04','codemsg'=>'消费金额格式有误'));
		}
		if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
			returnMsg(array('status'=>'05','codemsg'=>'建业通宝格式有误'));
		}
		if($balanceAmount==0&&$coinAmount==0){
			returnMsg(array('status'=>'06','codemsg'=>'消费数据有误'));
		}
		if(empty($orderid)){
			returnMsg(array('status'=>'07','codemsg'=>'订单编号缺失'));
		}
		$this->checkStore($panterid,$storeid);

		$customid=decode($customid);
		$bool=$this->checkCustom($customid);
		if($bool==false){
			returnMsg(array('status'=>'08','codemsg'=>'非法会员编号'));
		}
		$paypwd=$this->decodePwd($payPwd);
		if($paypwd==false){
			returnMsg(array('status'=>'012','codemsg'=>'非法密码传入'));
		}
		$pwdBool=$this->checkPayPwd($customid,$paypwd);
		if($pwdBool==='01'){
			returnMsg(array('status'=>'013','codemsg'=>'支付密码错误'));
		}elseif($pwdBool==='02'){
			returnMsg(array('status'=>'016','codemsg'=>'当天密码错误次数超过三次'));
		}
		$tradeLogs=M('trade_logs');
		$where=array('customid'=>$customid,'placeddate'=>date('Ymd'));
		$trade_logs_list=$tradeLogs->where($where)->order('datetimes desc')->select();
		$date=time();
		if($trade_logs_list!=false){
			if(intval($date-$trade_logs_list[0]['datetimes'])<=10){
				returnMsg(array('status'=>'017','codemsg'=>'重复支付订单'));
			}
		}
		$currentDate=date('Ymd');
		$currentTime=date('H:i:s');
        $sql="insert into trade_logs values('{$customid}','".encode($customid)."','{$currentDate}','{$currentTime}','{$date}','{$orderid}','')";
//		$sql="insert into trade_logs values('{$customid}','".encode($customid)."','{$currentDate}','{$currentTime}','{$date}','{$orderid}')";
		$tradeLogs->execute($sql);
		unset($sql);


		if($balanceAmount>0){
			$balance=$this->getAccountAmount('00',$customid);
			$balance>=$balanceAmount || returnMsg(array('status'=>'09','codemsg'=>'账户金额不足'));

			$returnBalance = bcsub($balance,$balanceAmount,2);
		}
		if($coinAmount>0){
			$jycoin = $this->getAccountAmount('01',$customid);
			$jycoin>=$coinAmount||returnMsg(array('status'=>'010','codemsg'=>'建业通宝余额不足'));

            $returnBalance = bcsub($jycoin,$coinAmount,2);
		}
		$this->model->startTrans();
		$balanceConsumeIf=$coinConsumeIf=false;

		//余额消费，金额为0不执行
		if($balanceAmount>0){
		    $synctype = '00';
		    $syncamount = $balanceAmount;
			$map=array('eorderid'=>$this->prefix.$orderid,'tradetype'=>'00');
			$balanceConsume=M('trade_wastebooks')->where($map)->sum('tradeamount');
			if($balanceConsume>0){
				returnMsg(array('status'=>'014','codemsg'=>'此订单已进行余额支付，请勿重复提交'));
			}
			$balanceConsumeArr=array('customid'=>$customid,'orderid'=>$this->prefix.$orderid,
			                         'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,
			                        );
			$balanceConsumeIf=$this->consumeExe($balanceConsumeArr);
		}else{
			$balanceConsumeIf=true;
		}
		//建业币消费，金额为0不执行
		if($coinAmount>0){
		    $synctype = '01';
            $syncamount = $coinAmount;
			$map=array('eorderid'=>$this->prefix.$orderid,'tradetype'=>'00');
			$balanceConsume=M('trade_wastebooks')->where($map)->sum('tradepoint');
			if($balanceConsume>0){
				returnMsg(array('status'=>'015','codemsg'=>'此订单已进行建业通宝支付，请勿重复提交'));
			}
			$coinConsumeArr=array('customid'=>$customid,'orderid'=>$this->prefix.$orderid,
			                      'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount);
			$coinConsumeIf=$this->consumeCoin($coinConsumeArr);

		}else{
			$coinConsumeIf=true;
		}
//		if($source!=='001'){
//            $recordSql =  "insert into zzk_sync_order(panterid,outid,order_sn,placeddate,placedtime,balance,jycoin,flag) VALUES ";
//            $recordSql.=  " ('{$panterid}','{$storeid}','{$orderid}','{$currentDate}','{$currentTime}','{$balanceAmount}','{$coinAmount}','1')";
//        }else{
//            $recordSql =  "insert into zzk_sync_order(panterid,outid,order_sn,placeddate,placedtime,balance,jycoin) VALUES ";
//            $recordSql.=  " ('{$panterid}','{$storeid}','{$orderid}','{$currentDate}','{$currentTime}','{$balanceAmount}','{$coinAmount}')";
//        }


//		$recordIf = $this->model->execute($recordSql);
		if($balanceConsumeIf==true&&$coinConsumeIf==true ){
			$this->model->commit();
			ob_clean();
			echo json_encode(array('status'=>'1','data'=>array('balance'=>$returnBalance),'info'=>array('reduceBalance'=>floatval($balanceAmount),'reduceCoin'=>floatval($coinAmount))));
			//回调支付通道
//            if($source!=='001'){
//                $json['panterid'] = $panterid;
//                $json['storeid']  = $storeid;
//                $json['service']  = $synctype;
//                $json['amount']   = $syncamount;
//                $json['createtime'] = time();
//                $json['endtime']    = time();
//                $json['out_oradeno'] = $orderid;
//                $json['key']         = md5($this->keysign.implode($json));
//                $this->pushAggregatePayment(C('AggregatePayment'),$json);
//            }

		}else{
			$this->model->rollback();
			returnMsg(array('status'=>'011','codemsg'=>'消费扣款失败'));
		}
	}
	protected function checkStore($panterid,$storeid){
		$where['outid'] = $storeid;
		$where['panterid'] = $panterid;
		if($this->model->table('zzk_store')->where($where)->field('outid')->find()){
			return true;
		}else{
			returnMsg(['status'=>'010','codemsg'=>'商户店铺信息不正确']);
		}
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

	//检查会员是否存在
	protected function checkCustom($customid){
		$customMap=array('customid'=>$customid);
		$customInfo=M('customs')->where($customMap)->find();
		if($customInfo==false){
			return false;
		}else{
			return true;
		}
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
	//检查支付密码是否正确
	protected function checkPayPwd($customid,$paypwd){
		$currentDate=date('Ymd');
		$map=array('customid'=>$customid,'placeddate'=>$currentDate);
		$list=M('paypwd_login_logs')->where($map)->find();
		$customMap=array('customid'=>$customid);
		$customInfo=M('customs')->where($customMap)->find();
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

	private function decodeDesData(){
		$post = getPostJson();
		//dump($post);exit;
		$data = trim($post['datami']);
		//dump($data);exit;
		$data=$this->des->decrypt($data);
	//	dump($data);exit;
		if($data==false){
			returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
		}
		$json = json_decode($data,1);
		if($json){
			return $json;
		}else{
			returnMsg(array('status'=>'08','codemsg'=>'数据格式错误'));
		}
	}

	private function checkSign($postKey, $key){
		$postKey==$key || returnMsg(array('status'=>'06','codemsg'=>'秘钥错误'));
		return true;
	}

	private function getAccountAmount($type,$customid){
		//
		$map['cc.customid'] = $customid;
		$map['ac.type']     = $type;
		$map['ca.status']   = 'Y';
		if($type==='00'){
			$map['_string']     = "ca.cardkind in ('6888','2336','6886') or(ca.cardkind= '6889' and ca.cardfee between '1' and '2')";
		}elseif($type==='01'){
			$map['ca.cardkind'] = '6889';
			$map['ca.cardfee']  =  ['between',['1','2']];
		}else{
			return false;
		}
		return $this->model->table('customs')->alias('cu')
		                   ->join('left join customs_c cc on cc.customid=cu.customid')
		                   ->join('left join cards ca on ca.customid=cc.cid')
		                   ->join('left join account ac on ac.customid=cc.cid')
		                   ->field('nvl(sum(ac.amount),0) amount')
		                   ->where($map)->find()['amount'];
	}
	//账户余额消费执行
	public function consumeExe($consumeArr){
		$orderid=$consumeArr['orderid'];
		$customid=$consumeArr['customid'];
		$amount=$consumeArr['amount'];
		$panterid=$consumeArr['panterid'];
		$termno=$consumeArr['termno'];
		$type=$consumeArr['type'];
		$cardsList=$this->getConsumeCards($customid);
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
		$errString1='扣除金额：'.$consumedAmount.',应付金额:'.$amount."\r\n";
		$this->recordError($errString1,'consume','custom__'.$customid);
		if($consumedAmount==$amount){
			return true;
		}else{
			return false;
		}
	}
	protected function getConsumeCards($customid){
		$where=array('cu.customid'=>$customid,'a.type'=>'00','a.amount'=>array('gt',0));
		$where['_string'] = "ca.cardkind in ('6888','2336','6886') or(ca.cardkind= '6889' and ca.cardfee between '1' and '2')";
		$list=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
		                    ->join('cards ca on ca.customid=cc.cid')
		                    ->join('account a on a.customid=ca.customid')
		                    ->where($where)->field('ca.cardno,a.amount,ca.customid cardid')->select();
		return $list;
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
        $coinLists = $this->coinAccountList($customid);
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
//            $cutPulish = $this->returnPublish($publish);
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
                return true;
            }else{
                return false;
            }
        }
        else{
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
	//通宝卡按发行记录扣款 返回所有查询记录
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
    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $model=new Model();
        $list=$model->query($sql);
        $fieldsLength=C('FIELDS_LENGTH');
        $fieldLength=$fieldsLength[$field];
        $lastNumber=$list[0]['nextval'];
        return  $this->getnumstr($lastNumber,$fieldLength);
    }
    //获得增加长度的字符 $numstr编号    $lennum字符长度
    public function getnumstr($numstr,$lennum){
        $snum=strlen($numstr);
        for($i=1;$i<=$lennum-$snum;$i++){
            $x.='0';
        }
        return $x.$numstr;
    }

    protected function getPrimarykey($field, $length){
    	$sql   = "select $field.nextval from dual";
	    $model = new Model();
	    $result= $model->query($sql);
	    return str_pad($result[0]['nextval'],$length,'0',STR_PAD_LEFT);
    }

    protected function encodePwd($pwd,$begin='jycard',$end='jycoin'){
    	return base64_encode($begin.$pwd.$end);
    }

    protected function editPwd($cp,$ap,$phone){
    	if(is_null($cp)){
    		return M('customs')->where(['linktel'=>$phone])->save(['paypwd'=>$ap]);
	    }else{
    		$map['phone'] = $phone;
    		$map['type']  = '06';
		    return M('zzk_account')->where($map)->save(['paypwd'=>$cp]);
	    }
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
    protected function pushAggregatePayment($url,$data){
	    $curldata['datami'] =  $this->des->encrypt(json_encode($data));

	    $result = $this->curlPostJson($url,json_encode($curldata));
	    $order_sn = $data['out_oradeno'];
	    if($result['curl'] ===true){
	        $json = json_decode($result['json'],1);
	        if($json['status']=='1'){
	            $save['splaceddate'] = date('Ymd');
	            $save['splacedtime'] = date('H:i:s');
	            $save['sync']        = '1';
	            $this->model->table('zzk_sync_order')->where(['order_sn'=>$order_sn])->save($save);

            }else{
	            $str = $order_sn."-".json_encode($result['info'])."\n";
	            $this->recordError($str,'pushAggregatePayment','return');
            }
        }else{
            $str = $order_sn."-".$result['json']."\n";
            $this->recordError($str,'pushAggregatePayment','false');
        }
    }
    protected function curlPostJson($url,$data){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,[
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);
        $result = curl_exec($ch);
        if($result==false){
            $info = curl_getinfo($ch);
            $info['errorcode']= curl_error($ch);
            return ['curl'=>false,'info'=>$info];
        }else{
            return ['curl'=>true,'json'=>$result];
        }

    }
}