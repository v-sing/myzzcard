<?php
namespace Api\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
use Home\Controller\CoinController;

class QingLSController extends CoinController{

	/**
	 * 获取卡号
	 * @param string $data 字符串
	 * @return json 
	 */
	public function getCards()
	{
		$data 	= json_decode(file_get_contents('php://input'),true);
		$datami = json_decode($data,1);
		$cardno	= trim($data['cardno']);
		$key 	= trim($data['key']);
		
		$this->recordData($data);//记录日志
		$checkKey = md5($this->keycode.$cardno);
		if ( $checkKey != $key ){
			returnMsg(array('status'=>'01','data'=>$key,'data1'=>$cardno,'data2'=>$data,'codemsg'=>'无效秘钥，非法传入'));
		}
		
		$map = array('cardno'=>$cardno);
		$res = $this->cards->where($map)->find();
		if ( !$res ) {
			returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
		}
		
		if ( !preg_match("/^(6999)(\d{15})$/",$cardno) ){
			returnMsg(array('status'=>'04','codemsg'=>'卡号不支持该项目'));
		}
		
		$customid = $this->getCustomid($cardno);
		if( $customid == false || empty($customid) ){
			returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
		}
		
		$account = $this->customs_c->alias('cc')
		->join('account a on cc.cid=a.customid')
		->join('cards c on cc.cid=c.customid')
		->where($map)->sum('a.amount');
		
		returnMsg(array('status'=>'12','amount'=>$account,'codemsg'=>'该卡已开卡'));
	}
	
	/**
	 * 会员生成并售卡
	 * @param string $data 字符串
	 * @return json
	 */
	public function openCardss()
	{
		$data 	= json_decode(file_get_contents('php://input'),true);

		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
		$this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");
		
		$cardno = trim($data['cardno']);//获取的卡号
		$namechinese  = trim($data['namechinese']);//用户的姓名
		$residaddress = trim($data['residaddress']);//用户的地址
		$linktel  = trim($data['linktel']);//用户的联系方式
		$personid = trim($data['personid']);// 证件号
		$panterid = trim($data['panterid']);//商户ID
		$totalmoney = trim($data['totalmoney']);//金额
		$paymenttype = trim($data['paymenttype']);//现金银行支付的类型
		$key = trim($data['key']);
		
		$this->recordData($data);//记录日志
		$checkKey = md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$panterid.$totalmoney.$paymenttype);
		if($key!=$checkKey){
			returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
		}

		if( empty($cardno) ){
			returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
		}
		
		if ( !preg_match("/^(6999)(\d{15})$/",$cardno) ){
			returnMsg(array('status'=>'02','codemsg'=>'卡号不支持该项目'));
		}

		if( empty($namechinese) ){
			returnMsg(array('status'=>'03','codemsg'=>'用户名不能为空'));
		}

		if( empty($linktel) ){
			returnMsg(array('status'=>'04','codemsg'=>'手机号不能为空'));
		}

		if( !preg_match("/^1[34578]\d{9}$/",$linktel) ){
			returnMsg(array('status'=>'06','codemsg'=>'手机号不正确'));
		}

		if( empty($personid) ){
			returnMsg(array('status'=>'05','codemsg'=>'证件号不能为空'));
		}

		if( empty($residaddress) ) {
			returnMsg(array('status'=>'07','codemsg'=>'证件地址不能为空'));
		}

		$where['cardno'] = $cardno;
		$status=$this->cards->where($where)->field('status')->find();
		
		if( $status['status'] == "N" ){
			
			$map = array('linktel'=>$linktel);
			$mob = $this->customs->where($map)->find();
			if($mob==true){
				returnMsg(array('status'=>'17','codemsg'=>'手机号已绑定，请更换'));
			}
			
			$map1 = array('personid'=>$personid);
			$person=$this->customs->where($map1)->find();
			if($person){
				returnMsg(array('status'=>'20','codemsg'=>'证件号已绑定，请更换'));
			}
			
			$currentDate = date('Ymd',time());
			
			$this->model->startTrans();
			$customid = $this->getnextcode('customs',8);//有无必要放到事务外围
			$bingSql = "insert into customs(customid,namechinese,personid,linktel,residaddress,placeddate) values('".$customid."','".$namechinese."','".$personid."','".$linktel."','".$residaddress."','".$currentDate."')";
			$this->recordError("注册SQL：" . serialize($bingSql) . "\n\t", "YjTbpost", "createAccount");
			$custIf=$this->model->execute($bingSql);
			if($custIf==true){
				$userid = $this->userid;
				$cardArr=array('cardno'=>$cardno);
				$res = $this->openCard($cardArr,$customid,$totalmoney,$panterid=null,$userid,$isBill=null,$paymenttype);
				if($res == true){
					$this->model->commit();
					returnMsg(array('status'=>'10','codemsg'=>'绑定开卡充值成功'));
				}else{
					$this->model->rollback();
					returnMsg(array('status'=>'11','codemsg'=>'绑定开卡充值失败'));
				}
			}else{
				$this->model->rollback();
				returnMsg(array('status'=>'09','codemsg'=>'绑定会员失败'));
			}
		}
	}
	
	/**
	 * 青蓝社充值
	 * @param string $data 字符串
	 * @return json
	 */
	public function CardRecharge(){
		$data   = json_decode(file_get_contents('php://input'),true);
		$cardno = trim($data['cardno']);//卡号
		$paymenttype =  trim($data['paymenttype']);//现金银行支付的类型
		$totalmoney  =  trim($data['totalmoney']);//总金额
		$panterid    =  trim($data['panterid']);//商务号
		$key 	= trim($data['key']);
		
		$this->recordData($data);//记录日志
		$checkKey = md5($this->keycode.$cardno.$paymenttype.$totalmoney.$panterid);
		if($key!=$checkKey){
			returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
		}
		
		$typeArr = array('00'=>'现金','01'=>'银行','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他');
		$paymenttype = $typeArr[$paymenttype];
		
		$data1['cardno'] = $cardno;
		$cards = M("Cards");//卡表
		$cards_data = $cards->where($data1)->find();
		$customid1  = $cards_data['customid'];
		$status     = $cards_data['status'];
		if($status!="Y"){  //不是正常卡不能充值
			returnMsg(array('status'=>'02','codemsg'=>'非正常卡，不能充值'));
		}
		
		$custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
		->join('customs cu on cu.customid=cc.customid')->where(array("c.customid"=>$customid1))
		->field('c.cardno,c.customid cardid,cu.customid')->find();
		if(empty($custom['customid'])){
			returnMsg(array('status'=>'03','codemsg'=>'会员不存在'));
		}
		
		//需优化
		$users=M("Users");
		$username = '';
		$usersdata=$users->where(array("username"=>$username))->find();
		$userid = isset($usersdata['userid']) ? $usersdata['userid']: '0000000000000000';
		 
		$map1=array('panterid'=>$panterid);
		$point_config=M('point_config')->where($map1)->find();
		$accountInfo=$this->getCardAccount($cardno);
		$customid=$custom['customid'];
		$cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
				'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],
				'validity'=>$point_config['validity']
		);
		
		$this->model->startTrans();
		$cardAccount=$this->cardAccQuery($cardno);
		$bool=$this->Recharge(array($cardno),$customid,$totalmoney,$panterid,$userid,$paymenttype);
		$purchaseArr=$bool;
		$pointAmount = 0;
		if($bool){
			$this->model->commit();
			returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseId'=>$purchaseArr,'addpoint'=>$pointAmount,'url'=>$url));
		}else{
			$this->model->rollback();
			returnMsg(array('status'=>'06','codemsg'=>'充值失败'));
		}
	}
	
    //查询账户信息接口(通过会员卡)
    public function getAccount(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);

        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6999'){
            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
        }
        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
            ->field('cu.namechinese')->find();
        if($custom == false){
            returnMsg(array('status'=>'05','codemsg'=>'此卡没有关联用户'));
        }
        $balance=$this->cardAccQuery($cardno);
        //------获取账户下所有卡数量
        //$tickketList=$this->getTicketByCustomid($customid);
        //--------end
        returnMsg(array('status'=>'1','balance'=>floatval($balance),
            'exdate'=>$card['exdate'],'name'=>$custom['namechinese'],'time'=>time()));
    }

    //获取劵列表
    public function getTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        //校验卡状态封装
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        //此功能需要改造
        if($card['cardkind']!='6999'){
            returnMsg(array('status'=>'04','codemsg'=>'此卡非青蓝社卡'));
        }
        $ticketList=$this->getTicketByCardno($cardno,'00003313');
        returnMsg(array('status'=>'1','list'=>$ticketList));
    }

    //获取过期劵列表
    public function getOverDueTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $ticketList=$this->getOverDueTicketByCardno($cardno,'00003313');
        returnMsg(array('status'=>'1','list'=>$ticketList));
    }

    //检验密码
    public function checkPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $pwd=trim($datami['pwd']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno.$pwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=$des->doEncrypt($pwd);
        if($pwd!=$card['cardpassword']){
            returnMsg(array('status'=>'04','codemsg'=>'密码错误'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'检验成功'));
        }
    }

    //消费列表
    public function getTreadelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6999371800000000011';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6999'){
            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
        }
        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','13','17','21')),'tw.flag'=>0))
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
        $list=array();
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        if($tradelist!=false){
            foreach($tradelist as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['tradetype']=$jytype[$val['tradetype']];
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime'])).' '.$val['placedtime'];
                $list[$key]['pname']=$val['pname'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }

    public function getAccountlist(){
    	$datami = trim($_POST['datami']);
    	$this->recordData($datami);
    	$datami = json_decode($datami,1);
    	$cardno=trim($datami['cardno']);
    	$key=trim($datami['key']);
    	$checkKey=md5($this->keycode.$cardno);
    	if($checkKey!=$key){
    		returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
    	}
    	$map=array('cardno'=>$cardno);
    	$card=$this->cards->where($map)->find();
    	if($card==false){
    		returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
    	}
    	if($card['status']!='Y'){
    		returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
    	}
    	if($card['cardkind']!='6999'){
    		returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
    	}
    	$tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
    	->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','13','17','21')),'tw.flag'=>0))
    	->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
    	$list=array();
    	$jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
    	if($tradelist!=false){
    		foreach($tradelist as $key=>$val){
    			$list[$key]['tradeid']=trim($val['tradeid']);
    			$list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
    			$list[$key]['tradetype']=$jytype[$val['tradetype']];
    			$list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
    			$list[$key]['pname']=$val['pname'];
    		}
    		returnMsg(array('status'=>'1','list'=>$list));
    	}else{
    		returnMsg(array('status'=>'05','codemsg'=>'无记录'));
    	}
    }
    
    //获取劵消费列表
    public function getQuanConsumelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
            ->join('quankind q on q.quanid=tw.quanid')
            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in','02'),'tw.flag'=>0))
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,p.namechinese pname,q.quanname')->select();
        $list=array();
        if($tradelist!=false){
            foreach($list as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
                $list[$key]['pname']=$val['pname'];
                $list[$key]['quanname']=$val['quannames'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }

    public function editPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        //$data=json_decode('{"cardno":"6882371900000000002","oldpwd":"YW1ob3RlbDg4ODg4OHpoaXp1bg==","newpwd":"YW1ob3RlbDEyMzQ1NnpoaXp1bg==","key":"0839ab6c739c391fe5fdc8f9e33663c2"}',1);
        $data = json_decode($datami,1);
        $cardno=trim($data['cardno']);
        $oldpwd=trim($data['oldpwd']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$oldpwd.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        $oldpwd=$this->decodePwd($oldpwd,'amhotel','zhizun');
        $newpwd=$this->decodePwd($newpwd,'amhotel','zhizun');
        if($oldpwd==false||$newpwd==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法密码传入'));
        }
        if(!preg_match("/\d{6}$/",$newpwd)){
            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $oldpwd=$des->doEncrypt($oldpwd);
        if($oldpwd!=$card['cardpassword']){
            returnMsg(array('status'=>'05','codemsg'=>'旧密码校验错误'));
        }
        $newpwd=$des->doEncrypt($newpwd);
        $cardMap=array('cardno'=>$cardno);
        $pwdData=array('cardpasswordd'=>$newpwd);
        $sql="update cards set cardpassword='{$newpwd}' where cardno='{$cardno}'";
        if($this->cards->execute($sql)){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'06','codemsg'=>'修改失败'));
        }
    }
    
    //充值记录
    public function getRechargeList(){
    	//参数校验
    	$datami = trim($_POST['datami']);
    	$this->recordData($datami);
    	//$data=json_decode('{"cardno":"6882371900000000002","oldpwd":"YW1ob3RlbDg4ODg4OHpoaXp1bg==","newpwd":"YW1ob3RlbDEyMzQ1NnpoaXp1bg==","key":"0839ab6c739c391fe5fdc8f9e33663c2"}',1);
    	$data = json_decode($datami,1);
    	$cardno=trim($data['cardno']);
    	$startdate=trim($data['startdate']);
    	$enddate=trim($data['enddate']);
    	$key=trim($data['key']);
    	$checkKey=md5($this->keycode.$cardno.$startdate.$enddate);
    	if($checkKey!=$key){
    		returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
    	}
    	//$cardno ='6999371800000000011';
    	//限制条件 需要封装
    	$field = 'c.customid,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
    	$field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
    	$statype = array('D'=>'销卡','R'=>'退卡','S'=>'过期','L'=>'锁定','Y'=>'正常卡');//卡状态
    
    	$where['card.cardkind']= '6999';
    
    	if($start!='' && $end==''){
    		$startdate = str_replace('-','',$start);
    		$where['b.placeddate']=array('egt',$startdate);
    		$this->assign('startdate',$start);
    		$map['startdate']=$start;
    	}
    	if($start=='' && $end!=''){
    		$enddate = str_replace('-','',$end);
    		$where['b.placeddate'] = array('elt',$enddate);
    		$this->assign('enddate',$end);
    		$map['enddate']=$end;
    	}
    	if($start!='' && $end!=''){
    		$startdate = str_replace('-','',$start);
    		$enddate = str_replace('-','',$end);
    		$where['b.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
    		$this->assign('startdate',$start);
    		$this->assign('enddate',$end);
    		$map['startdate']=$start;
    		$map['enddate']=$end;
    	}
    	if($start=='' && $end==''){
    	
    	}
    	if($cardno!=''){
    		$where['b.cardno']=$cardno;
    		$this->assign('cardno',$cardno);
    		$map['cardno']=$cardno;
    	}
    	if($customid!=''){
    		$where['c.customid']=$customid;
    		$this->assign('customid',$customid);
    		$map['customid']=$customid;
    	}
    	$model=new Model();
    	$cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field);
    	$count=$model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
    	->join('left join __CARDS__ card on card.cardno=b.cardno')
    	->join('left join __PANTERS__ p on p.panterid=b.panterid')
    	->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
    	->join(' __CUSTOMS__ c on c.customid=f.customid')
    	->join('left join __USERS__ u on u.userid=b.userid')
    	->where($where)->field($field)->count();
    	$amount_sum = $cardActiveLogs->getRechargeAmountSum();
    	$p = new \Think\Page($count, 10);
    	$cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field,$p);
    	$recharge_list = $cardActiveLogs->getRechargeList();
    	session('rexcel',$where);
    	if(!empty($map)){
    		foreach($map as $key=>$val) {
    			$p->parameter[$key] = $val;
    		}
    	}
    	$page = $p->show();
    	if($recharge_list){
    		returnMsg(array('status'=>'1','list'=>$recharge_list,'count'=>$count,'amount_sum'=>$amount_sum['amount_sum'],'statype'=>$statype,'page'=>$page));
    	}else{
    		returnMsg(array('status'=>'03','codemsg'=>'获取失败'));
    	}
    }
    
    //获取建业币账户信息
    protected function getCardAccount($cardno){
    	$where=array('c.cardno'=>$cardno,'a.type'=>'00');
    	$account=$this->cards->alias('c')->join('account a on a.customid=c.customid')
    	->where($where)->field('c.customid cardid,a.accountid')->find();
    	return $account;
    }
    
    /**
     * 充值操作
     * @param string $data 字符串
     * @return json
     */
    protected function Recharge($cardArr,$customid,$rechargeAmount,$panterid,$userid,$paymenttype='现金'){
    	if(empty($cardArr)) return false;
    	$rechargedAmount=0;
    	$purchaseArr=array();
    	foreach($cardArr as $val){
    		$waitAmount=$rechargeAmount-$rechargedAmount;
    		if($waitAmount<=0) break;
    		$cardno=$val;
    		$userstr= substr($userid,12,4);
    		//$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
    		$purchaseid=$this->getFieldNextNumber("purchaseid");
    		$purchaseid=$userstr.$purchaseid;
    		$currentDate=date('Ymd');
    		$checkDate=date('Ymd');
    		$where['cardno']=$cardno;
    		$card=$this->cards->alias('c')->join('account a on a.customid=c.customid')
    		->where($where)->field('c.customid,a.amount,c.panterid')->find();
    		$rechargeMoney=$waitAmount;
    		$customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
    		$customplSql.=$userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
    		$customplSql.="',1,'','','1','".$card['panterid']."','".$userid."',NULL,'1',";
    		$customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
    
    		//写入审核单
    		//$auditid=$this->getnextcode('audit_logs',16);
    		$auditid=$this->getFieldNextNumber('auditid');
    		$auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
    		$auditlogsSql.="'充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
    
    		$customplIf=$this->model->execute($customplSql);
    		$auditlogsIf=$this->model->execute($auditlogsSql);
    
    		//$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
    		$cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
    
    		//写入充值单
    		$cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
    		$cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
    		$cardplSql.="'{$userid}','{$card['panterid']}','','00000000')";
    		$cardplIf=$this->model->execute($cardplSql);
    
    		//更新卡片账户
    		$balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$card['customid']."' and type='00'";
    		$balanceIf=$this->model->execute($balanceSql);
    
    		if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true){
    			$rechargedAmount+=$rechargeMoney;
    			$purchaseArr[]=$cardpurchaseid;
    		}
    	}
    	if($rechargedAmount==$rechargeAmount){
    		return $purchaseArr;
    	}else{
    		return false;
    	}
    }
    
    /**
     * 开卡操作
     * @param string $data 字符串
     * @return json
     */
    protected function openCard($cardArr,$customid,$amount=0,$panterid=null,$type,$sourceRechargeId=null,$userid=null,$isActive=1){
    	if(empty($cardArr)) return false;
    	if($type==1){
    		$childPath='createAccount';
    		$errString="{$customid}会员注册开卡执行记录\r\n";
    	}elseif($type==2){
    		$childPath='customRecharge';
    		$errString="{$customid}充值开卡执行记录\r\n";
    	}
    	$description=empty($userid)?'后台充值':"后台接口充值:".$sourceRechargeId;
    	$userid=empty($userid)?$this->userid:$userid;
    	$rechargedAmount=0;
    	foreach($cardArr as $val){
    		$waitMoney=$amount-$rechargedAmount;
    		$cardno=$val;
    		$userstr= substr($userid,12,4);
    		//$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
    		$purchaseid=$this->getFieldNextNumber("purchaseid");
    		$purchaseid=$userstr.$purchaseid;
    		$currentDate=date('Ymd');
    		$checkDate=date('Ymd');
    		$where['cardno']=$cardno;
    		$cardinfo=M('cards')->where($where)->field('panterid')->find();
    		if($amount==0){
    			$rechargeMoney=0;
    		}else{
    			$rechargeMoney=$waitMoney;
    		}
    		$errString.="至尊卡号：{$cardno},充值金额：{$rechargeMoney},开卡流程数据库操作记录：\r\n";
    		//写入购卡单并审核
    		$customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','转账','";
    		$customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
    		$customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
    		$customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
    
    		//写入审核单
    		//$auditid=$this->getnextcode('audit_logs',16);
    		$auditid=$this->getFieldNextNumber('auditid');
    		$auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
    		$auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
    
    		$customplIf=$this->model->execute($customplSql);
    		$auditlogsIf=$this->model->execute($auditlogsSql);
    		$errString.="时间：".date('Y-m-d H:i:s').";售卡单入：{$customplSql};执行结果：{$customplIf}\r\n";
    		$errString.="时间：".date('Y-m-d H:i:s').";售卡单审核：{$auditlogsSql};执行结果：{$auditlogsIf}\r\n";
    
    		//$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
    		$cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
    		//写入购卡充值单
    		$cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
    		$cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','{$description}',";
    		$cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
    		$cardplIf=$this->model->execute($cardplSql);
    		$errString.="时间：".date('Y-m-d H:i:s')."售卡充值单写入：{$cardplSql};执行结果：{$cardplIf}\r\n";
    
    		$where1['customid']=$customid;
    		$card=$this->cards->where($where1)->find();
    		$customs_c=$this->customs_c->where(array('cid'=>$customid))->find();
    		if($card==false&&$customs_c==false){
    			//看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
    			$cardId=$customid;
    		}else{
    			//若存在，则需另外生成卡编号
    			//$cardId=$this->getnextcode('customs',8);
    			$cardId=$this->getFieldNextNumber("customid");
    			$customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
    			$customIf=$this->model->execute($customSql);
    			$errString.="时间：".date('Y-m-d H:i:s').";更新会员：{$customSql};执行结果：{$customIf}\r\n";
    		}
    		//echo $cardId;exit;
    		//执行激活操作
    
    		if($isActive==0){
    			$cardAlIf=true;
    		}else{
    			$cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
    			$cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
    			$cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
    			$cardAlIf=$this->model->execute($cardAlSql);
    			$errString.="时间：".date('Y-m-d H:i:s').";激活记录：{$customSql};执行结果：{$cardAlIf}\r\n";
    		}
    
    		//关联会员卡号
    		$customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
    		$customsIf=$this->model->execute($customcSql);
    		$errString.="时间：".date('Y-m-d H:i:s').";关联会员和卡：{$customcSql};执行结果：{$customsIf}\r\n";
    		//echo $this->model->getLastSql();exit;
    
    		//更新卡状态为正常卡，更新卡有效期；通宝卡不激活
    		if($isActive==0){
    			$exd=date('Ymd',strtotime("+3 years"));
    			$cardSql="UPDATE cards SET status='A',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
    			$cardIf=$this->model->execute($cardSql);
    			$errString.="时间：".date('Y-m-d H:i:s').";更新卡状态：{$cardSql};执行结果：{$cardIf}\r\n";
    		}else{
    			$exd=date('Ymd',strtotime("+3 years"));
    			$cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
    			$cardIf=$this->model->execute($cardSql);
    			$errString.="时间：".date('Y-m-d H:i:s').";更新卡状态：{$cardSql};执行结果：{$cardIf}\r\n";
    		}
    
    		//给卡片添加账户并给账户充值
    		//$acid = $this->getnextcode('account',8);
    		$acid = $this->getFieldNextNumber('accountid');
    		$balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
    		$balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
    		$balanceIf=$this->model->execute($balanceSql);
    		$errString.="时间：".date('Y-m-d H:i:s').";创建余额账户：{$balanceSql};执行结果：{$balanceIf}\r\n";
    
    		//$acid = $this->getnextcode('account',8);
    		$acid = $this->getFieldNextNumber('accountid');
    		$coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
    		$coinSql.=$acid."','".$cardId."','0','01',NULL)";
    		$coinIf=$this->model->execute($coinSql);
    		$errString.="时间：".date('Y-m-d H:i:s').";创建建业通宝账户：{$coinSql};执行结果：{$coinIf}\r\n";
    
    		//$acid = $this->getnextcode('account',8);
    		$acid = $this->getFieldNextNumber('accountid');
    		$pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
    		$pointSql.=$acid."','".$cardId."','0','04',NULL)";
    		$pointIf=$this->model->execute($pointSql);
    		$errString.="时间：".date('Y-m-d H:i:s').";创建至尊积分账户：{$pointSql};执行结果：{$pointIf}\r\n";
    
    		//在账户绑定记录记录下此卡和用户（注册用户状态为0，绑定卡状态为1）
    		if(!empty($panterid)){
    			$ecardBindSql="INSERT INTO ecard_bind values('{$cardno}','{$customid}',0,'{$panterid}')";
    			$ecardBindIf=$this->model->execute($ecardBindSql);
    			$errString.="时间：".date('Y-m-d H:i:s').";写入绑卡信息：{$ecardBindSql};执行结果：{$ecardBindIf}\r\n";
    		}else{
    			$ecardBindIf=true;
    		}
    		$errString.="<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\r\n";
    		if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true&&$ecardBindIf==true){
    			$rechargedAmount+=$rechargeMoney;
    		}
    	}
    	$errString.="\r\n";
    	$this->recordError($errString,$childPath,'会员_'.$customid);
    	if(!bccomp($rechargedAmount,$amount,2)){
    		return true;
    	}else{
    		return false;
    	}
    }
    
}

