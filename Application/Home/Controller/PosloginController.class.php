<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
//use Home\Model\PosloginModel;

header("content-type:text/html;charset=utf-8");
class PosloginController extends Controller{
	/**
	 * 后台登录方法
	 */
       protected $keycode="JYO2O01";
	   protected $code;
	   protected $userid;
	   protected $panterid;
       public function index(){  
        $data['panterid']=$_POST['panterid'];//商户号
		$data['terminal_id']=$_POST['terminal_id'];//终端号
		$data['username']=$_POST['username'];//用户姓名
		$data['password']=$_POST['password'];//密码
		$key=$_POST['key'];//秘钥
		/*$data['panterid']='00000126';
		 $data['terminal_id']='12600418';
		 $data['username']='shimx';
		 $data['password']='123456';*/
		$checkKey=md5($this->keycode.$data['panterid'].$data['username'].$data['terminal_id'].$data['password']); 
		if(in_array('',$data)){
			returnMsg(array('status'=>'01','codemsg'=>'传入的数据不完整'));
		}
		if($key!=$checkKey){ //秘钥错误
			returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
		}
		//$data['username']="admin";
		//$data['password']="123456";
		$pwd=md5($data['password']);
		$m=M('users');
		$user=$m->getByLoginname($data['username']);
	    if(empty($user)){
           returnMsg(array('status'=>'03','codemsg'=>'管理员不存在'));
        }else{
			if($user['wrpass']>=3){
				returnMsg(array('status'=>'04','codemsg'=>'账号已被锁定，请联系管理员'));
			}
			if($pwd!=$user['password1']){
				$m->execute("update users set wrpass=wrpass+1 where loginname='{$data['username']}'");
				returnMsg(array('status'=>'05','codemsg'=>'密码错误'));
            }else{ //user用户信息正确,商户号未注销
			     $panters=M('panters');
				// $data['panterid']='00000127';
				 if($data['panterid']!=$user['panterid']){
					returnMsg(array('status'=>'06','codemsg'=>'商户号与用户没有对应'));
				 }
			     $map=array('panterid'=>$data['panterid']);
				 $panter=$panters->where($map)->field('panterid,namechinese,Revorkflg')->find(); 
				if(empty($panter)){
					returnMsg(array('status'=>'07','codemsg'=>'商户不存在'));
				 }
				 if($panter['revorkflg']!='N'){ //商户撤销
					 returnMsg(array('status'=>'08','codemsg'=>'商户已经撤销'));
				}
				// $data['terminal_id']='27000453';
				//终端号的信息
			     $model=M('panter_terminals');
				 $map1=array('Terminal_id'=>$data['terminal_id'],'panterid'=>$data['panterid']);
				 $list=$model->where($map1)->find();
				  if(empty($list)){
					returnMsg(array('status'=>'09','codemsg'=>'终端号不存在'));
				 }
				 if($list['flag']!='Y'){ 
					 returnMsg(array('status'=>'010','codemsg'=>'终端已经撤销'));
				} 
				//获取终端号信息结束
				$m->execute("update users set wrpass='0' where loginname='{$data['username']}'");
				returnMsg(array('status'=>'1','codemsg'=>'登录成功'));
            } 
		}  
    }
	
	//通过卡号获取用户的信息
	 public function cardsinfo(){
		  $cardno=$_POST['cardno'];
		//  $cardno="6889396888800153272";
		  $key=$_POST['key'];//秘钥
		  $checkKey=md5($this->keycode.$cardno);
		  if($key!=$checkKey){
			  returnMsg(array('status'=>'015','codemsg'=>'无效秘钥，非法传入'));
		  }
		  $cards = M('cards');
		  $cardsdata=$cards->where(array('cardno'=>$cardno))->find();

		  if(empty($cardsdata)){
			  returnMsg(array('status'=>'01','codemsg'=>'卡不存在'));
		  }
		  $status=$cardsdata['status'];
		  if($status!="Y"){
			  switch($status){
				  case "A":
				  returnMsg(array('status'=>'02','codemsg'=>'待激活'));
				  break;
				  case "D":
				  returnMsg(array('status'=>'03','codemsg'=>'销卡'));
				  break;
				  case "R":
				  returnMsg(array('status'=>'04','codemsg'=>'退卡'));
				  break;
				  case "S":
				  returnMsg(array('status'=>'05','codemsg'=>'过期'));
				  break;
				  case "N":
				  returnMsg(array('status'=>'06','codemsg'=>'新卡'));  
				  break;
				  case "L":
				  returnMsg(array('status'=>'07','codemsg'=>'锁定'));
				  break;
				  case "W":
				  returnMsg(array('status'=>'08','codemsg'=>'无卡'));
				  break;
				  case "C":
				  returnMsg(array('status'=>'09','codemsg'=>'已出库'));
				  break;
				  case "J":
				  returnMsg(array('status'=>'010','codemsg'=>'入库'));
				  break;
				  case "T":
				  returnMsg(array('status'=>'011','codemsg'=>'冻结'));
				  break;
				  case "G":
				  returnMsg(array('status'=>'012','codemsg'=>'异常锁定'));
				  break;
				  case "B":
				  returnMsg(array('status'=>'013','codemsg'=>'睡眠'));
				  break;
			  }
		  }
		  $customid=$cardsdata['customid'];//获取会员编号
		   //$customid="00000016";
		  $customdata=$cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
              ->join('customs cu on cu.customid=cc.customid')
              ->field('cu.linktel,cu.namechinese,cu.personid,c.cardkind')->where(array("c.cardno"=>$cardno))->find();
         if(empty($customdata)){
             returnMsg(array('status'=>'014','codemsg'=>'此会员不存在'));
         }
         if($customdata['cardkind']=='6688'){
             $flag=1;
         }else{
             $flag=0;
         }
		 returnMsg(array('status'=>'1','codemsg'=>'访问成功','linktel'=>$customdata['linktel'],'namechinese'=>$customdata['namechinese'],'personid'=>$customdata['personid'],'flag'=>$flag));
	}
	
	//充值卡
	public function cardpays1($downdata=null){
		if(!empty($downdata)){
			$cardno =$downdata['cardno'];//卡号
			$paymenttype = $downdata['paymenttype'];//现金银行支付的类型
			$totalmoney=$downdata['totalmoney'];//总金额
			//明天要添加的字段
			$panterid = $downdata['panterid'];//商务号
			$username=$downdata['username'];      //操作员的姓名
			$key=$downdata['key'];
		}else{
			//生成单据
			//pos机上传过来的值
			$cardno =$_POST['cardno'];//卡号
			$paymenttype = $_POST['paymenttype'];//现金银行支付的类型
			$totalmoney=$_POST['totalmoney'];//总金额
			//明天要添加的字段
			$panterid = $_POST['panterid'];//商务号
			$username=$_POST['username'];      //操作员的姓名
			$key=$_POST['key'];
		}
		$checkno=""; //支票号
		$description="";//备注
		$checkKey=md5($this->keycode.$cardno.$paymenttype.$totalmoney.$panterid.$username);		
		if($key!=$checkKey){
			returnMsg(array('status'=>'011','codemsg'=>'无效秘钥，非法传入'));
		}
		$data1['cardno'] = $cardno;
		//$data1['cardno'] = "6889396888800153272";
	    //$data1['cardno'] = "6882371600000000453";
		//$data1['cardno'] = "2336370888801018116";
		//$paymenttype="01";  //银行卡的支付类型
		//$totalmoney="0.01";
		//$panterid ='00000125';
		//$username='admin';
		$cards=M("Cards");//卡表
		$cards_data=$cards->where($data1)->find();
		$customid1=$cards_data['customid'];	
		$status=$cards_data['status'];
		if($status!="Y"){  //不是正常卡不能充值
			returnMsg(array('status'=>'01','codemsg'=>'非正常卡，不能充值'));
		}
		$customs=M("customs");
		$customs_data=$customs->where(array("customid"=>$customid1))->find();	
		if(empty($customs_data)){
			 $where['cid']=$customid1;
			/* $cardif=$customs->alias('a')->join('left join customs_c c on c.cid=a.customid')
			       ->field('a.customid,a.namechinese,c.cid')->where($where)->find();*/
				$customs_c=M("Customs_c");  
                $dd=$customs_c->where($where)->find();
                $cardif=$customs->where(array("customid"=>$dd['customid']))->find();				
		    if(empty($cardif)){
				returnMsg(array('status'=>'02','codemsg'=>'会员不存在'));
			}
			$customid=$dd['cid'];
			$namechinese=$cardif['namechinese'];
			$customlevel=$cardif['customlevel'];  //用户的等级（一般用户）
		}else{
		    $customid=$customs_data['customid'];    //会员号
		    $namechinese=$customs_data['namechinese'];  //用户名
		    $customlevel=$customs_data['customlevel'];  //用户的等级（一般用户）
		}
        $tradeflag = "1";//充值单的类型为1，购卡为0
		$realamount =$totalmoney;  //实际金额
		$hysx=$this->getHysx($panterid);
		if($hysx!='酒店'){
            if($totalmoney>5000||$realamount>5000){
				returnMsg(array('status'=>'03','codemsg'=>'充值金额不能超过5000'));
            }
        }	
		$users=M("Users");
		$usersdata=$users->where(array("username"=>$username))->find();
		$userid = $usersdata['userid'];
        $userstr= substr($userid,12,4);
        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号PurchaseId充值单号
        $purchaseid=$this->getFieldNextNumber("purchaseid");
        $purchaseid=$userstr.$purchaseid;
        if($customlevel=='一般顾客'){
            $customflag=0;
        }else{
            $customflag=1;
        }
		$custompl=M('custom_purchase_logs');
		$customplif = $custompl->execute("insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".date('Ymd')."','".$paymenttype."','".$userid."','".$totalmoney."',NULL,'".$totalmoney."',0,'".$realamount."',0,0,'".$checkno."','".$description."','0','".$panterid."',NULL,NULL,'".$tradeflag."','".$customflag."',NULL,'00000000','".date('H:i:s')."',NULL,NULL,NULL)");
        ///////////////////////////////////////////////////////
		//$customplif=1 生成单据成功
		$dcustomp=$custompl->where(array('customid'=>$customid,'purchaseid'=>$purchaseid))->find();
		switch($dcustomp['flag']){
		case "0": 
			$data['flag']="未审核";
			break;
		case "1":
			$data['flag']="审核通过";
			break;
		case "2":
			$data['flag']="审核未通过";
			break;
		}
		$data['userid']=$userid;//操作员登录的用户id
		$this->userid=$data['userid'];
		$data['purchaseid']=$purchaseid;//订单号
		$data['customid']=$customid; //会员号
		switch($paymenttype){
		case "00":
			$data['paymenttype']="现金";
			break;
		case "01":
			$data['paymenttype']="银行卡";
			break;
		case "02":
			$data['paymenttype']="支票";
			break;
		case "03":
			$data['paymenttype']="汇款";
			break;
		case "04":
			$data['paymenttype']="网上支付";
			break;
		case "05":
			$data['paymenttype']="转账";
			break;
		case "06":
			$data['paymenttype']="内部转账";
			break;
		case "07":
			$data['paymenttype']="赠送";
			break;
        case "08":
			$data['paymenttype']="其他";
			break;			
		}
		$data['totalmoney']=$totalmoney; //全部的金额
		$data['realamount']=$realamount; //真实金额
		$data['description']=$description;//备注
		$data['cardno']=$data1['cardno']; //卡号
		$data['type']="0";//0表示审核通过的标志，1表示审核不通过的的标志
		$data['readStatus']="1";//1读卡成功，2表示读卡失败
		$data['description1']="";//备注
		$data['panterid']=$panterid;
		$this->panterid=$data['panterid'];
		$data['hysx']=$hysx; //是否是酒店
        $this->getcardpay1($data);		
	}
	//生成单据之后，充值审核
	public function getcardpay1($data){
		$model=new Model();
		$type=$data['type'];
		if($type!="0"){//审核已经通过，不能再审核
			returnMsg(array('status'=>'04','codemsg'=>'已经审核通过，无需重复审核！'));
		}
		if($type=="0"){//未审核的
			if(empty($data['cardno'])){
				returnMsg(array('status'=>'05','codemsg'=>'充值卡号不能为空'));
            }
			//$hysx=$this->getHysx($data['panterid']);
             if($data['hysx']!='酒店'){
                $accCondition=array('c.cardno'=>$data['cardno'],'a.type'=>'00');
                $account=$model->table('cards')->alias('c')
                    ->join('account a on c.customid=a.customid')
                    ->where($accCondition)->field('a.amount')->find();
                $totAccount=$data['totalmoney']+$account['amount'];
                if($totAccount>5000){
                    returnMsg(array('status'=>'03','codemsg'=>'充值金额不能超过5000'));
                }
            }  
			//$map['cardno']=$data['cardno']; //卡号，正常卡
           // $map['status']='Y'; 
			//if($data['panterid']!='FFFFFFFF'){
           //         $map['panterid']=$data['panterid'];
           // }
            $cardinfo=M('cards')->where($map)->find();
			$model->startTrans();//事务开启
			$customplSql="update custom_purchase_logs set flag='1',checkdate='".date('Ymd')."',checktime='".date('H:i:s');
            $customplSql.="',aditid='".$data['userid']."',description1='".$data['descriptions1']."' where purchaseId='".$data['purchaseid']."'";
			$customplif=$model->execute($customplSql);
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
			$auditlogsSql="insert into audit_logs values ('".$auditid."','".$data['purchaseid']."','审核通过','";
            $auditlogsSql.=$data['descriptions1']."','".date('Ymd')."','".$data['userid'] ."','".date('H:i:s')."')";
            $auditlogsif=$model->execute($auditlogsSql);
			$exeParam=array('cardno'=>$data['cardno'],'customid'=>$data['customid'],
                    'purchaseid'=>$data['purchaseid'],'totalmoney'=>$data['totalmoney']);
            $exeif=$this->payExe($exeParam);
		    $info = json_decode($exeif, true);
		    $cardpurchaseid=$info['cardpurchaseid'];
		    if($info['status']=="1"&&$customplif==true&&$exeif==true){
                $model->commit();
				$customs_c=M("Customs_c");
				$customs=M("Customs");
				$toms_c=$customs_c->where(array("cid"=>$data['customid']))->find();
				if(!empty($toms_c)){
					$customs_ttc=$customs->where(array("customid"=>$toms_c['customid']))->find();
					if(!empty($customs_ttc['customid'])){
						$datadd=array(
						   "mobile"=> $customs_ttc['linktel'], //手机号
						   "cardno"=> $data['cardno'], //卡号
						   "phone_mob"=>$customs_ttc['linktel'],
						   "panterid"=>$data['panterid'],
						   "customid"=>$customs_ttc['customid'] //会员编号
						);
						$datadd['key']=md5($this->keycode.$customs_ttc['linktel'].$data['cardno'].$customs_ttc['linktel'].$data['panterid'].$customs_ttc['customid']);
						$url="http://kabao.9617777.com/kabao1/index.php?app=erweima&act=index";  //调用卡系统的接口地址8091测试端口   8080正式端口	
						$result1= $this->CurlPost($url,$datadd); 
						$redd=json_decode($result1,true);
						$url=$redd['url'];
					}
				}
                returnMsg(array('status'=>'1','codemsg'=>'审核通过成功,卡已充值','cardpurchaseid'=>$cardpurchaseid,'url'=>$url));				
            }else{
                $model->rollback();
				returnMsg(array('status'=>'06','codemsg'=>'审核通过成功,充值失败','purchaseid'=>$data['purchaseid']));
            } 	  
		}
	}

	//通过手机查找用户的信息
	public function Lintelinfo(){
		$host=$_SERVER['HTTP_HOST'];
		$linktel=$_POST['linktel'];//获取手机号
		//$linktel='13592389639';
		//$linktel='13592389639';
		//15890620404
		$key=$_POST['key'];
		$checkKey=md5($this->keycode.$linktel);
		if($key!=$checkKey){
			returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
		}
		$customs=M("Customs");  
		$customsdata=$customs->where(array("linktel"=>$linktel))->find();
		if(empty($customsdata)){
			returnMsg(array('status'=>'02','codemsg'=>'用户的信息不存在'));
		}
		if(empty($customsdata['namechinese'])){
			$customsdata['namechinese']="";
		}
		if(empty($customsdata['personid'])){
			$customsdata['personid']="";
		}
		if(empty($customsdata['residaddress'])){
			$customsdata['residaddress']="";
		}
		if(empty($customsdata['personidexdate'])){
			$customsdata['personidexdate']="";
		}
		if(empty($customsdata['frontimg'])){
			$customsdata['frontimg']="";
		}else{
			 
			$customsdata['frontimg']= "https://".$host."/public".$customsdata['frontimg'];
		}
		if(empty($customsdata['reserveimg'])){
			$customsdata['reserveimg']="";
		}else{
			 
			$customsdata['reserveimg']= "https://".$host."/public".$customsdata['reserveimg'];
		}
		//查找卡号
		$customid=$customsdata['customid'];
		$customs_c=M("Customs_c");
		$cards=M("Cards");
	    $cdata=$customs_c->where(array("customid"=>$customid))->select();	
		$cardata=array();
		foreach($cdata as $vv){
			$cid=$vv['cid'];
			$cardsdata=$cards->where(array("customid"=>$cid))->find();
            if(!empty($cardsdata)){
				$cardata[]=$cardsdata['cardno'];
			}	
		}
	 returnMsg(array('status'=>'1','codemsg'=>'获取用户信息成功','namechinese'=>$customsdata['namechinese'],'personid'=>$customsdata['personid'],'residaddress'=>$customsdata['residaddress'],'personidexdate'=>$customsdata['personidexdate'],'frontimg'=>$customsdata['frontimg'],'reserveimg'=>$customsdata['reserveimg'],'cardno'=>$cardata));
}
		
		
	//获取用户信息之后提交,发卡
  public function customCards(){

	$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
	$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
	$uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
	$this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($_POST) . "\n\t", "YjTbpost", "createAccount");

	  //获取用户的信息之后，会员与卡绑定提交
	  $cardno=$_POST['cardno'];//获取的卡号
	  $namechinese=$_POST['namechinese'];//用户的姓名
	  $residaddress=$_POST['residaddress'];//用户的地址
	  $personidexdate=$_POST['personidexdate'];//证件的期限
	  //$frontimg=$_POST['frontimg'];//身份证的正面的图片
	  //$reserveimg=$_POST['reserveimg'];//身份证的反面的图片
	  $linktel=$_POST['linktel'];//用户的联系方式
	  $personid=$_POST['personid'];// 证件号
	  $order=$_POST['order'];// 订单号
	  $panterid=$_POST['panterid'];//商务号
	  $username=$_POST['username'];//操作员的用户名
	  $totalmoney=$_POST['totalmoney'];//金额
	  $paymenttype = $_POST['paymenttype'];//现金银行支付的类型
	  $key=$_POST['key'];
	  //账号+用户的姓名+用户的地址+证件的期限+联系方式+证件号+订单号+商务号+操作员的用户名+金额+现金银行支付的类型
	  $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$order.$panterid.$username.$totalmoney.$paymenttype);
	  if($key!=$checkKey){
		  returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
	  }
	  //通过订单查找信息
	 /*$order="201606220013458281";
	 $personid="411527198704060038";
	 $linktel='18639210858';
	 $residaddress="河南省郑州市";//用户的地址
	 $cardno='6882371799900013132';//卡为绑定
	 $namechinese="www";
	 $username="admin";
	 $panterid="00000270";
	 $totalmoney="200";
	 $paymenttype="01";*/
	 if(empty($cardno)){
		 returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空')); 
	}
	 if(empty($namechinese)){
		 returnMsg(array('status'=>'03','codemsg'=>'用户名不能为空')); 
	 }
	if(empty($linktel)){
		 returnMsg(array('status'=>'04','codemsg'=>'手机号不能为空')); 
	}
	if(empty($personid)){
		 returnMsg(array('status'=>'05','codemsg'=>'证件号不能为空')); 
	}
	  $dept=M("DEPT");
	  $strsql="select * from  DEPT where posorder='{$order}'";
	  $data=$dept->query($strsql);
	  if(!empty($data)){
		 $frontimg=trim($data['0']['frontimg']);
		 $reserveimg=trim($data['0']['reserveimg']);
	  }
	 //查找订单，查看会员是否存在
    // $panterid_if = $panterid; //商务号
    // $arr  = array('00000270','00000118','00000127','00000125','00000126');
   //  $boolif = in_array($panterid_if,$arr);
	    $customs = M('customs');
		$datapersonif=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
		$personidif =$customs->where($datapersonif)->find();
		if(empty($personidif)){//会员不存在
			 $dd['frontimg']=$frontimg;
			 $dd['reserveimg']=$reserveimg;
			 $dd['cardno']=$cardno;
			 $dd['namechinese']=$namechinese;
			 $dd['residaddress']=$residaddress;
			 $dd['personidexdate']=$personidexdate;
			 $dd['linktel']=$linktel;
			 $dd['panterid']=$panterid;
			 $dd['username']=$username;
			 $result=$this->addcustoms($dd);
			 if($result){
			    $olddata['cardno']=$cardno;
				$olddata['totalmoney']=$totalmoney;
				$olddata['paymenttype']=$paymenttype;		
				$olddata['username']=$username;	
				$olddata['panterid']=$panterid;			
				$linktel=$dd['linktel'];
				$newcustom =$customs->where(array("linktel"=>$linktel))->find();
				$olddata['customid']=$newcustom['customid'];
                $this->Ctuckcomb1($olddata);		
			 }
		 }else{//会员存在,更改会员信息成功
				$customid=$personidif['customid'];
				if(empty($personidif['namechinese'])){
					 $dataall['namechinese']=$namechinese;
				}
				if(empty($personidif['residaddress'])){
					$dataall['residaddress']=$residaddress;
				}
				if(empty($personidif['personid'])){
					$dataall['personid']=$personid;
				}
				if(!empty($frontimg)){
					 $dataall['frontimg']=$frontimg;
				}
				if(!empty($reserveimg)){
					$dataall['reserveimg']=$reserveimg;
				}
				if(!empty($dataall)){
				    $dd=$customs->where(array("customid"=>$customid))->save($dataall);
					if($dd){
						$olddata['cardno']=$cardno;
						$olddata['totalmoney']=$totalmoney;
						$olddata['paymenttype']=$paymenttype;		
						$olddata['username']=$username;	
						$olddata['panterid']=$panterid;			
						$oldcustom =$customs->where(array("linktel"=>$linktel))->find();
						$olddata['customid']=$oldcustom['customid'];
						$this->tuckcomb($olddata);
					}else{
						returnMsg(array('status'=>'06','codemsg'=>'会员更改失败'));
					}
			    }else{
					$olddata['cardno']=$cardno;
					$olddata['totalmoney']=$totalmoney;
					$olddata['paymenttype']=$paymenttype;		
					$olddata['username']=$username;	
					$olddata['panterid']=$panterid;			
					$oldcustom =$customs->where(array("linktel"=>$linktel))->find();
					$olddata['customid']=$oldcustom['customid'];
					$this->tuckcomb($olddata);
				}	
	    }	
   }


//发卡时，卡已经存在
  public function tuckcomb($newdata){
	//秘钥
	// $key=md5($this->keycode.$newdata['cardno'].$newdata['paymenttype'].$newdata['totalmoney'].$newdata['panterid'].$newdata['username']);  
	$cards=M("Cards");
	$cardno=$newdata['cardno'];
	$carddata=$cards->where(array('cardno'=>$cardno))->find();
	if($carddata['status']=='N'){
	    $this->Ctuckcomb1($newdata);
	}elseif($carddata['status']=='Y'){
		if($carddata['customid']==$newdata['customid']){
		$newdata['key']=md5($this->keycode.$newdata['cardno'].$newdata['paymenttype'].$newdata['totalmoney'].$newdata['panterid'].$newdata['username']);	
		$this->cardpays1($newdata);
		}else{ 
			 $customid=$newdata['customid'];
			 $custom_c=M("Customs_c"); //
			 $C_data=$custom_c->where(array("customid"=>$customid,"cid"=>$carddata['customid']))->find();
			 if(empty($C_data)){
				returnMsg(array('status'=>'07','codemsg'=>'会员号与卡号不对应'));
			 }else{
				 //会员卡号都存在，现在直接充值
				 $newdata['key']=md5($this->keycode.$newdata['cardno'].$newdata['paymenttype'].$newdata['totalmoney'].$newdata['panterid'].$newdata['username']);	
				 $this->cardpays1($newdata);
				// echo "数据存在";
			 } 
		}
	}else{
		returnMsg(array('status'=>'016','codemsg'=>'非正常卡'));
	}
  }
//创建一个新的会员的之后，然后发卡并充值
         public function Ctuckcomb1($tuckcomb){
			 $cards=M("Cards");
			 $custompl=M('custom_purchase_logs');
			 $customs=M('customs');
	         $cardno=$tuckcomb['cardno'];
	         $carddata=$cards->where(array('cardno'=>$cardno))->find();
			 if(!empty($carddata['customid'])){
				returnMsg(array('status'=>'011','codemsg'=>'这个卡已经被绑定了'));
			 }
			 if($carddata['status']!='N'){
				returnMsg(array('status'=>'012','codemsg'=>'充值卡号非新卡，不能售卡！'));
			 }
			 $totalmoney=$tuckcomb['totalmoney'];
			 $realamount= $totalmoney;
			 $checkno='';
			 $description='';
			$tradeflag = "0";//充值单的类型为1，购卡为0
			$panterid = $tuckcomb['panterid'];
			$hysx=$this->getHysx($panterid);
            if($hysx!='酒店'){
                if($totalmoney>5000||$realamount>5000){
					returnMsg(array('status'=>'013','codemsg'=>'充值金额不能超过5000'));
                }
            }
			$username=$tuckcomb['username'];
			$users=M("Users");
		    $usersdata=$users->where(array("username"=>$username))->find();
		    $userid = $usersdata['userid'];
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
			$purchaseid=$userstr.$purchaseid;
            if($customlevel=='一般顾客'){
                $customflag=0;
            }else{
                $customflag=1;
            }
			$customid=$tuckcomb['customid'];
			$paymenttype=$tuckcomb['paymenttype'];
			$ff=$custompl->execute("insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".date('Ymd')."','".$paymenttype."','".$userid."','".$totalmoney."',NULL,'".$totalmoney."',0,'".$realamount."',0,0,'".$checkno."','".$description."','0','".$panterid."',NULL,NULL,'".$tradeflag."','".$customflag."',NULL,'00000000','".date('H:i:s')."',NULL,NULL,NULL)");
			$datass['purchaseid']=$purchaseid;//订单号
			$datass['totalmoney']=$totalmoney;
			$datass['cardno']=$cardno;
			$datass['customid']=$customid;
			$datass['getHysx']=$hysx;
			$datass['userid']=$userid;
			$datass['panterid']=$panterid;
			$this->tumbcheck($datass);
		 }
     //售卡审核与添加发卡
    public function tumbcheck($datass){
		 $auditlogs=M('audit_logs');
		 $type = "0";
		 $purchaseid = $datass['purchaseid'];
		 $userid=$datass['userid'];
		 $cardno=$datass['cardno'];
		 $customid=$datass['customid'];
		 $descriptions = "";
		 $totalmoney=$datass['totalmoney'];
		 $map['purchaseid']=$purchaseid;
		 $info=M('custom_purchase_logs')->where($map)->find();
         if($info['flag']!=0){
			    returnMsg(array('status'=>'014','codemsg'=>'已经审核通过，无需重复审核！'));
           } 
		$where['cardno']=$cardno;
        //$where['status']='N';
        $cardinfo=M('cards')->where($where)->field('status')->find();
		if($cardinfo['status']!='N'){
			returnMsg(array('status'=>'012','codemsg'=>'充值卡号非新卡，不能售卡！'));
        }
		$model=new Model();
        $model->startTrans(); 
		$customplSql="update custom_purchase_logs set flag='1',checkdate='".date('Ymd')."',checktime='".date('H:i:s')."'";
		$customplSql.=",aditid='".$userid."',description1='".$descriptions."' where purchaseId='".$purchaseid."'";
		$customplif=$model->execute($customplSql);
		//$auditid=$this->getnextcode('audit_logs',16);
        $auditid=$this->getFieldNextNumber('auditid');
		$auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过','";
        $auditlogsSql.=$descriptions."','".date('Ymd')."','".$userid ."','".date('H:i:s')."')";
        $auditlogsif=$model->execute($auditlogsSql);
		$exePram=array('purchaseid'=>$purchaseid,'cardno'=>$cardno,'totalmoney'=>$totalmoney,'customid'=>$customid);
		$zz['userid']=$userid;
		$zz['panterid']=$datass['panterid'];
		$exeBool=$this->sellExecute($exePram,$zz);
		$jsoninfo = json_decode($exeBool, true);
		$cardpurchaseid=$jsoninfo['cardpurchaseid'];
		if($jsoninfo['status']=="1"&&$auditlogsif==true&&$auditlogsif==true){
            $model->commit();
			$customs_c=M("Customs_c");
			$customs=M("Customs");
			$toms_c=$customs_c->where(array("cid"=>$customid))->find();
			if(!empty($toms_c)){
				$customs_ttc=$customs->where(array("customid"=>$toms_c['customid']))->find();
				if(!empty($customs_ttc['customid'])){
					$datadd=array(
					   "mobile"=> $customs_ttc['linktel'], //手机号
					   "cardno"=> $datass['cardno'], //卡号
					   "phone_mob"=>$customs_ttc['linktel'],
					   "panterid"=>$datass['panterid'],
					   "customid"=>$customs_ttc['customid'] //会员编号
					);
					$datadd['key']=md5($this->keycode.$customs_ttc['linktel'].$datass['cardno'].$customs_ttc['linktel'].$datass['panterid'].$customs_ttc['customid']);
					$url="http://kabao.9617777.com/kabao1/index.php?app=erweima&act=index";  //调用卡系统的接口地址8091测试端口   8080正式端口	
					$result1= $this->CurlPost($url,$datadd); 
					$redd=json_decode($result1,true);
					$url=$redd['url'];
				}
			} 
			returnMsg(array('status'=>'1','codemsg'=>'售卡成功，卡已充值激活','cardpurchaseid'=>$cardpurchaseid,'url'=>$url));
           // $this->success('售卡成功，卡已充值激活');
        }else{
             $model->rollback();
			 returnMsg(array('status'=>'015','codemsg'=>'审核失败'));
        }
	}
 //售卡执行
    function sellExecute($exePram,$zz=null){
		$custompl=M('custom_purchase_logs');
        $cardpl = M('card_purchase_logs');
        $cards=M('cards');
        $customsc=M('customs_c');
        $customs=M('customs');
        $cardactive=M('card_active_logs');
        $model=new model();
        $batchbuyBatchLog=array();
		$userid =  $zz['userid'];
        $panterid=$zz['panterid'];
		$nowdate=date('Ymd');
        $nowtime=date('H:i:s');
		
	    //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
		
		$userip=$_SERVER['REMOTE_ADDR'];
        $cardno=$exePram['cardno'];
        $purchaseid=$exePram['purchaseid'];
        $totalmoney=$exePram['totalmoney'];
        $customid=$exePram['customid'];
		$sql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
		$sql.="'{$cardno}','{$totalmoney}','0','{$nowdate}','{$nowtime}','1','pos充值',";
        $sql.="'{$userid}','{$panterid}','{$userip}','00000000')";
		
		$cardplif  =$cardpl->execute($sql);
		if($cardplif==false){
            $batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行充值表增加有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'016','codemsg'=>'购卡执行充值表增加有异常'));
        }
		$customplSql="update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+";
        $customplSql.=$totalmoney." where purchaseid='".$purchaseid."'";
        $customplif=$custompl->execute($customplSql);
        $cardw['customid']=$customid;
		$cardone=$cards->where($cardw)->find();
		 if($cardone!=false){
            //$customno  =$this->getnextcode('customs',8);
            $customno=$this->getFieldNextNumber("customid");
            $map['cardno']=$cardno;
            $cardinfo=$cards->where($map)->find();
            $exd=date('Ymd',strtotime("+3 years"));
            $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$customno}'";
            $sql.=",'{$cardinfo['panterid']}','00000000')";
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')";
            $customcif =$customsc->execute($customcSql);

            $cardSql="UPDATE cards SET status='Y',customid='{$customno}',cardbalance='{$totalmoney}',exdate='{$exd}' where cardno='".$cardno."'";
            $cardsif  =$cards->execute($cardSql);
            $customif  =$customs->execute("UPDATE customs SET cardno='teshu' where customid='".$customid."'");
            $cardactiveif=$model->table('card_active_logs')->execute($sql);
        }else{
            $map['cardno']=$cardno;
            $customno=$customid;
            $cardinfo=$cards->where($map)->find();
            $exd=date('Ymd',strtotime("+3 years"));
            $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','".$customid;
            $sql.=   "','{$cardinfo['panterid']}','00000000')";
            $customcif =$customsc->execute("INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')");
            $cardsif  =$cards->execute("UPDATE cards SET status='Y',customid='".$customid."',exdate='".$exd."' where cardno='".$cardno."'");
            $cardactiveif=$model->table('card_active_logs')->execute($sql);
        }
        $account=M('account');
        $cardw1['type']='00';
        $cardw1['customid']=$customno;
        $accountone = $account->where($cardw1)->find();
        if($accountone==false){
            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $accountSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $accountSql.=$acid."','".$customno."','".$totalmoney."','00',NULL)";
            $acountif = $account->execute($accountSql);
        }else{
            $accountSql="UPDATE account SET amount=nvl(amount,0)+".$totalmoney." where customid='".$customno."' and type='00'";
            $acountif = $account->execute($accountSql);
        }
        if($customcif==false || $cardsif==false || $acountif==false || $cardactiveif==false){
			$res['status']="0";
			$res['cardpurchaseid']=$cardpurchaseid;
			$result=json_encode($res);//传过去的json的数据
			
            $batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'012','codemsg'=>'购卡执行有异常'));
        }else{
            $res['status']="1";
			$res['cardpurchaseid']=$cardpurchaseid;
			$result=json_encode($res);//传过去的json的数据
            $batchbuyBatchLog[0]['status']=1;
            $batchbuyBatchLog[0]['msg']='购卡执行成功!';
        }
        if(!empty($batchbuyBatchLog)){
            $this->batchbuyLogs($batchbuyBatchLog);
        }
        return $result;
	}


//添加会员的信息
public function addcustoms($data){
	
	$customs = M('customs');
	 $customs->startTrans();
	$personid=$data['personid'];
	$linktel=$data['linktel'];
	$panterid_if = $data['panterid'];
    $arr= array('00000270','00000118','00000127','00000125','00000126');
    $boolif = in_array($panterid_if,$arr);
	if(!empty($personid)||!empty($linktel))
	{  
		if(!$boolif){
			$datapersonif['personid'] = $personid;
			$personidif = $customs->where($datapersonif)->find();
			if($personidif)
			{
		     returnMsg(array('status'=>'08','codemsg'=>'身份证已经绑定，请更换'));
			}
		}
    }
	//最后判断身份证的数据有没有填写，并上传服务器
	    $personidexdate=trim($data['personidexdate']);
		$namechinese=$data['namechinese'];
		$where=array();
		$where['namechinese']=array('like','%'.$namechinese.'%');
		$where['personid'] = $personid;
		$where['_logic'] = 'or';
		$seach_con['_string']="namechinese ='{$namechinese}' OR personid='{$personid}'";
		$searchs=M('searchs');
		$search=$searchs->where($seach_con)->select();
		if($search!=false){
			returnMsg(array('status'=>'09','codemsg'=>'该用户疑似恐怖分子，请速与警方联系'));
		}
		$currentDate=date('Ymd',time());
		$frontimg=$data['frontimg'];
		$reserveimg=$data['reserveimg'];
		$residaddress=$data['residaddress'];
		$linktel=$data['linktel'];
		$panterid=$data['panterid'];
		//$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
		$sql='insert into customs(customid,namechinese,linktel,personid,';
		$sql.="residaddress,panterid,placeddate,frontimg,reserveimg,personidexdate,customlevel)values('{$customid}','{$namechinese}','{$linktel}',";
		$sql.="'{$personid}','{$residaddress}','{$panterid}','{$currentDate}','{$frontimg}','{$reserveimg}','{$personidexdate}','建业线上会员')";
		$this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
		$ccu=$customs->execute($sql);
		if($ccu){
			  $customs->commit();
			  return $ccu;
			//returnMsg(array('status'=>'1','codemsg'=>'会员添加成功'));
		}else{
			  $customs->commit();
			returnMsg(array('status'=>'010','codemsg'=>'会员添加失败'));
		}
}



	// posorder 订单生成字段 正面
	public function savePic(){
		$order=$_POST['order']; //获取的订单号
		$key=$_POST['key'];
		$checkKey=md5($this->keycode.$order);
		 if($key!=$checkKey){
		  returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
	    }
    	if (!empty($_FILES)) {
    		$upload = new \Think\Upload();
    		$upload->maxSize   	=     2000000 ;//2M
    		$upload->exts      	=     array('jpg', 'gif', 'png', 'jpeg');
    		$upload->rootPath  	=     './Public/'; // 设置附件上传根目录
    		$upload->savePath  	=      '/Uploads/';// 设置附件上传（子）目录
			$upload->saveName     = '';
			$upload->replace    =     true;
    		$info  =  $upload->upload();
			if($info){
				$frontpath=$info['file']['savepath'].$info['file']['savename'];
				$dept=M("DEPT");
				$strsql="select * from  DEPT where posorder='".$order."'";
				
		        $datacustoms=$dept->query($strsql);
				if(empty($datacustoms)){
					$strsql1="INSERT INTO DEPT (posorder,frontimg) VALUES ('".$order."', '".$frontpath."')";
		            $dd1=$dept->execute($strsql1);
					if($dd1==true){
						  returnMsg(array('status'=>'1','codemsg'=>'上传成功1'));
					}
				}else{
					$datacustoms['frontimg']=trim($datacustoms['0']['frontimg']);
					$reservepath=trim($frontpath);
					if($datacustoms['frontimg']==$frontpath){	
						returnMsg(array('status'=>'1','codemsg'=>'上传成功2'));
					}else{
						 $customsSql="UPDATE DEPT SET frontimg='{$frontpath}' where posorder='{$order}'";
                         $cardsif  =$dept->execute($customsSql);
						 if($cardsif==true){
						  returnMsg(array('status'=>'1','codemsg'=>'上传成功3'));
					    }
					}
				}	 
			}
    	}else{
			 returnMsg(array('status'=>'02','codemsg'=>'图片未上传'));  
		}
    }
	//反面的地址
    public function reservePic(){
		$order=$_POST['order']; //获取的订单号
		$key=$_POST['key'];
		$checkKey=md5($this->keycode.$order);
		 if($key!=$checkKey){
		  returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
	    }
    	if (!empty($_FILES)) {
    		$upload = new \Think\Upload();
    		$upload->maxSize   	=     2000000 ;//2M
    		$upload->exts      	=     array('jpg', 'gif', 'png', 'jpeg');
    		$upload->rootPath  	=     './Public/'; // 设置附件上传根目录
    		$upload->savePath  	=      '/Uploads/';// 设置附件上传（子）目录
			$upload->saveName     = '';
			$upload->replace    =     true;
    		$info  =  $upload->upload();
			if($info){
				$reservepath=$info['reserveimg']['savepath'].$info['reserveimg']['savename'];
				$dept=M("DEPT");
				$strsql1="select * from  DEPT where posorder='".$order."'";
				//$strsql1="select * from  DEPT where posorder='201606212135427754'";
		        $datacustoms=$dept->query($strsql1);
			    if(empty($datacustoms)){
					$strsql="INSERT INTO DEPT (posorder,reserveimg) VALUES ('".$order."','".$reservepath."')";
		            $dd1=$dept->execute($strsql);
					if($dd1==true){
						returnMsg(array('status'=>'1','codemsg'=>'上传成功'));
					}
				}else{
					$datacustoms['reserveimg']=trim($datacustoms['0']['reserveimg']);
					$reservepath=trim($reservepath);
					if($datacustoms['reserveimg']==$reservepath){
						returnMsg(array('status'=>'1','codemsg'=>'上传成功'));
					}else{
						 $customsSql="UPDATE DEPT SET reserveimg='{$reservepath}' where posorder='{$order}'";
                         $cardsif  =$dept->execute($customsSql);
						 if($cardsif==true){
						  returnMsg(array('status'=>'1','codemsg'=>'上传成功'));
					    }
					}
				}	 
			} 
    	}else{
			 returnMsg(array('status'=>'02','codemsg'=>'图片未上传'));  
		}
    }
	
	
    //通过会员id查看会员的所有的信息
	/*public function customChild(){
		$customid12=$_POST['customid'];
		$key=$_POST['key'];
		$checkKey=md5($this->keycode.$customid12);
		if($key!=$checkKey){
		  returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
	    }
		//$customid12='MTAwMDE4NzIO0O0O';
		//$customid='00000018';
		//$customid_string=base64_encode($customid12);
		//$customid_mm=str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), $customid_string);
		$customid_mm12=str_replace(array('O0O0O', 'o000o', 'oo00o'),array('=', '+', '/'),$customid12);
		$cid=base64_decode($customid_mm12); 
		$cards = M('cards');
		$cardsdata=$cards->where(array("customid"=>$cid))->find();
		if(empty($cardsdata)){
			returnMsg(array('status'=>'02','codemsg'=>'卡号为空'));
		}
		//$cardno=$cardsdata['cardno'];
		$customs_c=M("Customs_c");
		$cus_cdata=$customs_c->where(array("cid"=>$cid))->find();
		if(empty($cus_cdata)){
			returnMsg(array('status'=>'03','codemsg'=>'未找到主会员编号'));
		}
		$allcustomid=$cus_cdata['customid'];
		$customs=M("Customs");
		$customdata=$customs->where(array("customid"=>$allcustomid))->find();
		if(empty($customdata)){
			returnMsg(array('status'=>'04','codemsg'=>'用户信息为空'));
		}
		$linktel=$customdata['linktel'];
		$namechinese=$customdata['namechinese'];
		$personid=$customdata['personid'];
		returnMsg(array('status'=>'1','codemsg'=>'获取信息成功','linktel'=>$linktel,'namechinese'=>$namechinese,'personid'=>$personid,'cardno'=>$cardno)); 
	}
	*/
	
	  //通过主会员id查看会员的所有的信息
	public function customChild(){
		$customid12=$_POST['customid'];
		$key=$_POST['key'];
		$checkKey=md5($this->keycode.$customid12);
		if($key!=$checkKey){
		  returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));  
	    }
		$customid_mm12=str_replace(array('O0O0O', 'o000o', 'oo00o'),array('=', '+', '/'),$customid12);
		$customid=base64_decode($customid_mm12); 
		//$customid="00000016";
		$customs_c=M("Customs_c");
		$arr=array();
		$cus_cdata=$customs_c->where(array("customid"=>$customid))->select();
		if(empty($cus_cdata)){
			returnMsg(array('status'=>'03','codemsg'=>'未找到主会员编号'));
		}
	    foreach($cus_cdata as $vv){
		   $cards = M('cards');
		   $cardsdata=$cards->where(array("customid"=>$vv['cid']))->find();
		   if(!empty($cardsdata)){
			   $arr[]=$cardsdata['cardno'];
		   }
	    }
		if(empty($arr)){
			returnMsg(array('status'=>'02','codemsg'=>'卡号为空'));
		}
		//$cardno=$cardsdata['cardno'];
		$customs=M("Customs");
		$customdata=$customs->where(array("customid"=>$customid))->find();
		if(empty($customdata)){
			returnMsg(array('status'=>'04','codemsg'=>'用户信息为空'));
		}
		$linktel=$customdata['linktel'];
		$namechinese=$customdata['namechinese'];
		$personid=$customdata['personid'];
		if(empty($linktel)){
			$linktel="";
		}
		if(empty($namechinese)){
			$namechinese="";
		}
		if(empty($personid)){
			$personid="";
		}
		returnMsg(array('status'=>'1','codemsg'=>'获取信息成功','linktel'=>$linktel,'namechinese'=>$namechinese,'personid'=>$personid,'cardno'=>$arr)); 
	}
	//通过商务号判断是否是酒店
	public function getHysx($panterid){
        $where['panterid']=$panterid;
        $panterInfo=D('panters')->where($where)->find();
        return $panterInfo['hysx'];
    }
	//获取下一个序号  tablename 表名 主键自增一
	public function getnextcode($tablename,$lennum=0){
		if($tablename!=''){
			$dmap['keyname']=$tablename;
			$this->code=M('code_generators');
			$datatable=$this->code->where($dmap)->getfield('current_seq');
			if($datatable==null){
				$mapp=array(
					'keyname'=>$tablename,
					'current_seq'=>1,
				);
				$strsql="INSERT INTO code_generators (keyname,current_seq) VALUES ('".$tablename."', 1)";
				$codeid=$this->code->execute($strsql);
				if ($codeid==false) { //判断是否成功
			        $this->error('失败！');
			        exit;
			    }
			}
			$map['current_seq']=$datatable+1;
			if($this->code->where($dmap)->save($map)){
				if($lennum!=0){
					$datatable=$this->getnumstr($datatable,$lennum);
				}
				return $datatable;
			}else{
				$this->error('表主键更新失败');
				exit;
			}
		}else{
			$this->error('表名不能为空');
			exit;
		}
	}
	//获得增加长度的字符 $numstr编号    $lennum字符长度
	public function getnumstr($numstr,$lennum){
		$snum=strlen($numstr);
		for($i=1;$i<=$lennum-$snum;$i++){
			$x.='0';
		}
		return $x.$numstr;
	}
    function payExe($exeParam){
        $customsc=M('customs_c');
        $cards=M('cards');
        $cardpl=M('card_purchase_logs');
        $custompl=M('custom_purchase_logs');
        $account=M('account');
        $batchbuyBatchLog=array();
        $userid =  $this->userid;
        $panterid=$this->panterid;
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        $totalmoney=$exeParam['totalmoney'];
        $cardno=$exeParam['cardno'];
        $purchaseid=$exeParam['purchaseid'];
        //$zszzb=$exeParam['zszzb'];
        $customid=$exeParam['customid'];
        $cardSql="UPDATE cards SET cardbalance=nvl(cardbalance,0)+".$totalmoney." where cardno='".$cardno."'";
        $cardsif  =$cards->execute($cardSql);
        if($cardsif==false){
			$batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'卡充值执行卡表列新有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'06','codemsg'=>'卡充值执行卡表列新有异常'));
        }
		
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $userip=$_SERVER['REMOTE_ADDR'];
        $cardplSql="INSERT INTO card_purchase_logs VALUES('".$cardpurchaseid."','".$purchaseid."','".$cardno."','";
        $cardplSql.=$totalmoney."','0','".$nowdate."','".$nowtime."','1','pos机充值','".$userid."','";
        $cardplSql.=$panterid."','".$userip."','00000000')";
        $cardplif  =$cardpl->execute($cardplSql);
        if($cardplif==false){
			$batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'卡充值执行充值表增加有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'07','codemsg'=>'卡充值执行充值表增加有异常'));
        }
        $customplSql="update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+";
        $customplSql.=$totalmoney." where purchaseid='".$purchaseid."'";
        $customplif=$custompl->execute($customplSql);
        if($customplif==false){
			$batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'卡充值执行购卡充值单表更新有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'08','codemsg'=>'卡充值执行购卡充值单表更新有异常'));
        }
        $acountSql="UPDATE account SET amount=nvl(amount,0)+".$totalmoney." where customid='".$customid."' and type='00'";
        $acountif = $account->execute($acountSql);
        if($acountif==false || $customplif==false||$cardplif==false||$cardsif==false){
			$res['status']="0";
			$res['cardpurchaseid']=$cardpurchaseid;
			$result=json_encode($res);//传过去的json的数据
			$batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'卡充值执行有异常,请联系系统管理员!';
			//returnMsg(array('status'=>'09','codemsg'=>'卡充值执行有异常'));
        }
		else{
			$res['status']="1";
			$res['cardpurchaseid']=$cardpurchaseid;
			$result=json_encode($res);//传过去的json的数据
            $batchbuyBatchLog[0]['status']=1;
            $batchbuyBatchLog[0]['msg']='卡可以充值，无异常!';
        }
        if(!empty($batchbuyBatchLog)){
            $this->batchbuyLogs($batchbuyBatchLog);
        }
        return $result;
    }
	 //写入批量申请购卡日志
    function batchbuyLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='充值金额：'.$val['amount']."  ";
            if($val['status']==0){
                $msgString.='状态：批量购卡申请失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量购卡申请成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('cardbatchbuy',$msgString);
    }
	 public function writeLogs($module,$msgString){
        $month=date('Ym',time());
        switch($module){
            case 'batchConsume':$logPath=PUBLIC_PATH.'logs/batchConsume/';break;
            case 'batchRecharge':$logPath=PUBLIC_PATH.'logs/batchRecharge/';break;
            case 'cardbatchbuy':$logPath=PUBLIC_PATH.'logs/cardbatchbuy/';break;
            case 'createCards':$logPath=PUBLIC_PATH.'logs/createCards/';break;
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
 //post提交数据
      function CurlPost($url,$data = null){
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
         curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
         curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
         if (!empty($data)){
             curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
         }
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $info = curl_exec($ch);
         if (curl_errno($ch)) {
             echo 'Errno'.curl_error($ch);
         }
       //file_put_contents("./poscredits/erweima.txt",$info."\r\n",FILE_APPEND);//二维码的文件
         curl_close($ch);
         return $info;
     }  
	//通过微信获取的主会员id，然后再获取用户的cid
	public function wxcustom(){
    	$customid=$_POST['customid'];
        $key=$_POST['key'];//秘钥
		$checkKey=md5($this->keycode.$customid); 
		if($key!=$checkKey){ //秘钥错误
			returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
		}
	//	$customid='00000016';
		$arrcar=array();
		$customs_c=M("Customs_c");
		$customs_c=$customs_c->where(array("customid"=>$customid))->select();
		foreach($customs_c as $vv){
			 $customid=$vv['cid'];
			 $cards=M("Cards");
			 $ded=$cards->where(array("customid"=>$customid))->field('cardno')->find();
			 if(!empty($ded['cardno'])){
				 $arrcar[]=$ded['cardno'];
			 }
		}
		if(empty($arrcar)) {
			returnMsg(array('status'=>'02','codemsg'=>'数据为空'));
		}
		//$arrdata=json_encode($arrcar);
		returnMsg(array('status'=>'1','codemsg'=>'获取信息成功','arrcar'=>$arrcar));
		//echo $arrdata;
		//return;
	}	 
}