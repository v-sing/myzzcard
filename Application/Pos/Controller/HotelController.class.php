<?php
namespace Pos\Controller;
use Think\Model;
use Org\Util\DESedeCoder;
use Org\Util\Des;
require APP_PATH.'Pos/Controller/phpqrcode.php';
//酒店自主支付业务
class HotelController extends  CoinController
{
	protected $keycode;
	protected $panterArr;
	protected  $account;
	protected  $model;
	protected  $DESedeCoder;
	protected  $url;
	protected  $des;
	protected  $pName;
	/*
	 * error 错误代码：
	 * 09:账户余额不足
	 * 01商户 011 商户号错误
	 * 02卡号 021 非法卡号 022 不是酒店卡  023 会员卡号异常,已锁定  024 卡密码错误
	 * 03 订单号 031 空订单 032重复订单提交 033该订单已经扣款
	 * 04数据库  044数据库修改失败
	 *
	 */
	public function _initialize(){
		$this->keycode = 'jyzz_2018';
		$this->cache   = S(['expire'=>2]);
		$this->panterArr = [
			'00003696',
			'00005476'
		];
		$this->model   = new Model();
		$this->account = M('account');
		$this->DESedeCoder=new  DESedeCoder('GDgLwwdK27020180622lyTp');
		$this->url     = 'https://zz.9617777.com/';

		$this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');

		$this->pName = [
			'00003696'=>'鄢陵花满地温泉酒店',
			'00005476'=>'东风南路建业天筑国际公寓分公司',
		];


	}
	public function qrcode(){
		$json = $this->decodeDesData();
		$panterId = trim($json['panterId']);
		$termno   = trim($json['termno']);
		$amount   = trim($json['amount']);
        $orderSn  = trim($json['orderSn']);
        $key      = trim($json['key']);
		$checkKey=md5(md5($this->keycode.$panterId.$termno.$amount.$orderSn));
		$checkKey==$key ||  returnMsg(array('status'=>'06','codemsg'=>'无效秘钥，非法传入'));
		if(empty($orderSn)) returnMsg(array('status'=>'031','codemsg'=>'订单号不能为空'));

		$url = C('hotelQrcode');
		if(! in_array($panterId,$this->panterArr)) returnMsg(array('status'=>'011','codemsg'=>'非法商户id'));
		$time =time();
		$param = [
			'panterid'=>$panterId,
			'name'    =>$this->pName[$panterId],
			'termno'  =>$termno,
			'amount'  =>$amount,
			'ordersn' =>$orderSn,
			'time'    =>$time,
			'key'     =>md5('JYO2O01'.$panterId.$this->pName[$panterId].$termno.$amount.$orderSn.$time)
		];
		$param = encode(json_encode($param));
		$content= $url.'?'.'app=wxhousehold&act=hotelOutside&param='.$param."&_noconfirm_=1";
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 10;//生成图片大小
		// 	$openid=$user1['openid'];
		$filename=PUBLIC_PATH."img/".md5($orderSn).'.png';//原始二维码图 名称路径
		$path = pathinfo($filename);
		$path = $path['dirname']."/";
		file_exists($path) || mkdir($path,0777,true);

		if(file_exists($filename)){
			unlink($filename);
			\QRcode::png($content, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
			$filename = ltrim($filename,'./');
			$erweima=$filename;
			$url = $this->url.$erweima;
			returnMsg(array('status'=>'1',"codemsg"=>"生成二维码成功",'url'=>urlencode($url)));
		}else{
			\QRcode::png($content, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
			$filename = ltrim($filename,'./');
			$erweima=$filename;
			$url = $this->url.$erweima;
			returnMsg(array('status'=>'1',"codemsg"=>"生成二维码成功",'url'=>urlencode($url)));exit;
		}
	}

	public function cardConsume(){
		$json = $this->decodeDesData();

		$cardno   = trim($json['cardno']);
		$cardPwd  = trim($json['cardPwd']);
		$amount   = trim($json['amount']);
		$orderSn  = trim($json['orderSn']);
		$sourceId = trim($json['sourceId']);
		$panterId = trim($json['panterId']);
		$key      = trim($json['key']);

		$checkKey=md5(md5($this->keycode.$cardno.$cardPwd.$amount.$orderSn.$sourceId.$panterId));
		$checkKey==$key ||  returnMsg(array('status'=>'06','codemsg'=>'无效秘钥，非法传入'));
		if(empty($orderSn)) returnMsg(array('status'=>'031','codemsg'=>'订单号不能为空'));

		if($this->cache->$orderSn) returnMsg(array('status'=>'032','codemsg'=>'订单重复提交'));
		else $this->cache->$orderSn = true;

		if(M('trade_wastebooks')->where(['eorderid'=>$orderSn])->find()) returnMsg(array('status'=>'033','codemsg'=>'该订单已经扣款'));
		if(! in_array($panterId,$this->panterArr)) returnMsg(array('status'=>'08','codemsg'=>'非法商户id'));

		$cardInfo = M('cards')->where(['cardno'=>$cardno])->field('customid,cardkind,status,cardpassword')->find();
		if($cardInfo){
			$pwd  = $this->des->doEncrypt($cardPwd);
			$pwd === $cardInfo['cardpassword'] ||  returnMsg(array('status'=>'024','codemsg'=>'卡密码错误'));
			$cardInfo['cardkind']=='6882' || returnMsg(array('status'=>'022','codemsg'=>'不是酒店卡'));
			$cardInfo['status'] == 'Y'    || returnMsg(array('status'=>'023','codemsg'=>'会员卡号异常,已锁定'));

		}else{
			returnMsg(array('status'=>'021','codemsg'=>'非法卡号'));
		}

		$map['customid'] = $cardInfo['customid'];
		$map['type']     = '00';

		$account  = $this->account->where($map)->field('amount')->find();

		$account['amount']>=$amount || returnMsg(array('status'=>'09','codemsg'=>'卡余额不足'));

		$save['amount'] = bcsub($account['amount'],$amount,2);

		$termno = '0000003';
        $time   = time();
        $placeddate = date('Ymd',$time);
        $placedtime = date('H:i:s',$time);
		$tradeid = substr($cardno,15,4).date('YmdHis',$time);
		$tac='abcdefgh';
		$sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag,eorderid)VALUES(";
		$sql.="'{$termno}','{$termno}','{$panterId}','{$tradeid}','{$placeddate}','{$amount}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0','{$orderSn}')";

		$this->model->startTrans();
		try{
		   $accountIf = $this->account->where($map)->save($save);
		   $tradeIf	  = $this->model->execute($sql);
		   if($accountIf && $tradeIf){
		   	  $this->model->commit();
		   	  returnMsg(['status'=>'1','codemsg'=>'消费成功','consume'=>$amount]);
		   }else{
			   $this->model->rollback();
			   returnMsg(['status'=>'044','codemsg'=>'数据库修改失败']);
		   }
		}catch (\Exception $e){
			$this->model->rollback();
			returnMsg(['status'=>'044','codemsg'=>$e->getMessage()]);
		}
	}


	private function decodeDesData(){
		$post = getPostJson();
		$data = trim($post['datami']);
		$data=$this->DESedeCoder->decrypt($data);
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
}