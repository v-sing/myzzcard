<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class MposCoinController extends CoinController{

  // public function test(){
  //     $arr=array('customid'=>encode('00000017'),'key'=>md5('JYO2O01'.encode('00000017')));
  //     $str=json_encode($arr);
  //   //  $str='{"customid":"MDAxMTYzOTcO0O0O","amount":"1","sourceCode":"1001","sourceRechargeId":"test","key":"608a8534bd47bee9b635b68e07b6df31"}';
  //
  //     $datami=$this->DESedeCoder->encrypt($str);
  //     echo $datami;exit;
  //     //echo encode('00117661');
  //    // echo "123";
  //    //echo decode('MDAxMzIxMjcO0O0O');
  // }


  //查询账户信息接口(通过用户编号)
   public function getAccount(){
      $data = getPostJson();
      $datami = trim($data['datami']);
      $datami = $this->DESedeCoder->decrypt($datami);
      if($datami == false){
          returnMsg(array('status'=>'04','codemsg'=>'非法数据传入'));
      }
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $key = trim($datami['key']);
      if(empty($customid)){
          $this->recoreIp();
          returnMsg(array('status'=>'01','codemsg'=>'用户缺失'));
      }
      $checkKey = md5($this->keycode.$customid);

      if($checkKey != $key){
          returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid = decode($customid);
      $customBool = $this->checkCustom($customid);
      if($customBool == false){
          returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
      }
      //账户信息
      $accountInfo = $this->accountInfo($customid);
      //------获取账户下所有卡数量
      $tickketList=$this->getTicketByCustomid($customid);
      if($tickketList == false || empty($tickketList)){
        $totaltickket = 0;
      }else{
        $totaltickket = array_sum(array_column($tickketList,'amount'));
      }
      //--------end
      returnMsg(array('status'=>'1','balance'=>floatval($accountInfo['balance']),
          'jycoin'=>floatval($accountInfo['jycoin']),'totaltickket'=>$totaltickket));
  }

  //劵查询接口
  public function ticketsQerry(){
      $data = getPostJson();
      $datami = trim($data['datami']);
      $datami = $this->DESedeCoder->decrypt($datami);
      if($datami == false){
        returnMsg(array('status'=>'05','codemsg'=>'非法数据'));
      }
      $datami = json_decode($datami,1);
      $customid = trim($datami['customid']);
      $key = trim($datami['key']);
      $checkKey = md5($this->keycode.$customid);
      if($checkKey != $key){
          returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
      }
      $customid=decode($customid);
      empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
      $customBool=$this->checkCustom($customid);
      if($customBool==false){
          returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
      }
      $tickketList=$this->getTicketByCustomid($customid);
      if($tickketList==false){
          returnMsg(array('status'=>'04','codemsg'=>'查无卡劵信息'));
      }else{
          returnMsg(array('status'=>'1','ticketList'=>json_encode($tickketList)));
      }
  }


}
