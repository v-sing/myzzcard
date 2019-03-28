<?php
namespace Api\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
use Home\Controller\CoinController;

class SqlController extends CoinController{

 //检验密码
    public function checkPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $keycode="JYO2O01";//密钥
        $cardno=trim($datami['cardno']);
        $pwd=trim($datami['pwd']);
        $key=trim($datami['key']);
        $checkKey=md5($keycode.$cardno.$pwd);

        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入',"data"=>$checkKey));
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


//消费列表 datami={"cardno":'6882371888800002252',"key":"47afac6fb7b52c2f7188ce2c3fdd9e89"}
    public function getTreadelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $keycode="JYO2O01";//密钥
        $checkKey=md5($keycode.$cardno);
		
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6882371888800002858';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'){
            returnMsg(array('status'=>'04','codemsg'=>'非至尊酒店卡'));
        }

      $amountData=$this->account->where(array("customid"=>$card['customid']))->field("amount")->find();//获取总金额
   
      $quanData=M('quan_account')->alias("qa")->where(array('customid'=>$card['customid']))->field("quanid,amount,startdate,purchaseid,enddate")->order('enddate asc')->select();

      $list=array();

        if($quanData!=false){
    
            foreach ($quanData as $key => $value) {
				
		if(empty($value['amount'])||$value['amount']=='0')
				{
					
					continue;
				}
					
				$list[$key]['startdate']=$value['startdate'];
				$list[$key]['purchaseid']=$value['purchaseid'];
				$list[$key]['enddate']=$value['enddate'];
				$list[$key]['amount']=$value['amount'];
                 $temp=M('quankind')->where(array("quanid"=>$value['quanid']))->field("quanname,amount")->find();
                 $list[$key]['quanname']=$temp['quanname'];
                 $list[$key]['money']=number_format($temp['amount'],2);

            }
              $showamount=number_format($amountData['amount'],2);
             returnMsg(array('status'=>'1','list'=>$list,'money'=>$showamount));

        }else{

             returnMsg(array('status'=>'1','codemsg'=>'无记录','list'=>$list,'money'=>''));
        }

	
       

    }


}
