<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Exception;
use Think\Model;
class JlhposController extends CoinController{
    public function getName(){
        header("content-type:text/html;charset=utf-8");
        $condition = array();        
        $customerInfo = $_POST['datami'];
        $customerInfo = json_decode($customerInfo,true);
        
        $key = 'JLH2016';
        $customerName = $customerInfo['customerName'];
        $sign = md5($customerName.$key);
        $sign == $customerInfo['sign'] || die(json_encode(array('code'=>'003','msg'=>"签名错误")));
        $customerName || die(json_encode(array('code'=>'001','msg'=>"用户名为空")));
       //$customerName = '李';
        $condition['namechinese'] = array('eq',"{$customerName}");
        $condition['customlevel']=array('eq','建业线上会员');
        $condition['countrycode']=array('eq','君邻会会员');
  
        $result = $this->customs
                       ->where($condition)
                       ->limit(10)
                       ->field('customid,namechinese')
                       ->select();
        
        if(empty($result) || !$result || count($result)<1){
            die(json_encode(array('code'=>'002','msg'=>"未查到用户信息")));
        }
        $resultData = array('code'=>'000','msg'=>$result);
        echo json_encode($resultData);
        
    }
    
    public function getCustomerDetail(){
        header("content-type:text/html;charset=utf-8");
        $condition = array();        
        $customerInfo = $_POST['datami'];        
        $customerInfo = json_decode($customerInfo,true);
        $key = 'JLH2016';
        $customerName = $customerInfo['customerName'];
        $sign = md5($customerName.$key);
        $sign == $customerInfo['sign'] || die(json_encode(array('code'=>'003','msg'=>"签名错误")));
        $customerName || die(json_encode(array('code'=>'001','msg'=>"用户姓名为空")));
        $condition['namechinese'] = array('eq',"{$customerName}");
        $condition['customlevel']=array('eq','建业线上会员');
        $condition['countrycode']=array('eq','君邻会会员');
        
        $result = $this->customs
                       ->where($condition)
                       ->field('customid,namechinese,personid,sex,linktel,residaddress,unitname')
                       ->select();
        
        if(empty($result) || !$result || count($result)<1){
            die(json_encode(array('code'=>'002','msg'=>"未查到用户信息")));
        }
        $resultData = array('code'=>'000','msg'=>$result);
        echo json_encode($resultData);
    }
    
    public function getCustomerByPhone(){
        header("content-type:text/html;charset=utf-8");
        $condition = array();
        $customerInfo = $_POST['datami'];
        $this->recordData($customerInfo);
        $customerInfo = json_decode($customerInfo,true);
        $key = 'JLH2016';
        $phone = $customerInfo['phone'];
        $personid=$customerInfo['personid'];
        $sign = md5($phone.$personid.$key);
        $sign == $customerInfo['sign'] || die(json_encode(array('code'=>'003','msg'=>"签名错误")));
        if(empty($phone)&&empty($personid)){
            die(json_encode(array('code'=>'001','msg'=>"检索条件不完善")));
        }
        //$phone = '13489064682';

        if(!empty($phone)){
            $condition['cu.linktel'] = array('eq',"{$phone}");
        }
        if(!empty($personid)){
            $condition['cu.personid'] = array('eq',"{$personid}");
        }
        $condition['customlevel']=array('eq','建业线上会员');
        //$condition['countrycode']=array('eq','君邻会会员');
        $condition['c.panterid']='00000447';


        $result = $this->customs->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($condition)
            ->field('cu.customid,cu.namechinese,cu.personid,cu.sex,cu.linktel,cu.residaddress,cu.unitname')
            ->find();
        //echo $this->customs->getLastSql();
        if($result==false){
            die(json_encode(array('code'=>'002','msg'=>"未查到用户信息")));
        }
        $resultData = array('code'=>'000','msg'=>array($result));
        echo json_encode($resultData);
    }
}
?>