<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class OfficialCoinController extends CoinController {
    //卡售后管理
    public function _initialize(){
        parent::_initialize();
        $this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    }
    //登陆验证
    public function checkLogin(){
        $data1 = $_POST['datami'];
        $datami = trim($data1);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $name = trim($datami['name']);
        $phone = trim($datami['phone']);
        $password = $datami['password'];
        $newpassword=$this->des->doEncrypt($password);
        $where="c.status='Y'";
        if($newpassword!=''){
            $where.=" AND c.cardpassword='".$newpassword."'";
        }
        if($name!=''){
            $where.=" AND c.cardno='".$name."'";
        }
        if($phone!=''){
            $where.=" AND cu.linktel='".$phone."'";
        }
        //卡号登录
        if($name){
            //用户输入卡号后，通过卡号查询会员手机号
            $linktel=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')->where($where)->getField('cu.linktel');
        }
        //手机号登录
        if($phone){
            $linktel=$phone;
        }
        //如果手机号存在，通过手机号查询所有会员编号
        if($linktel){
            $map['cu.linktel']=array('eq',$linktel);
            $map['cu.customlevel']=array('eq','建业线上会员');
            $map['c.cardno']=array('neq','null');
            $map['c.cardkind']= array('in','6889,6688,6888,2336');
            $result=$this->customs->alias('cu')
                ->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')
                ->field('c.customid,cu.linktel,cu.namechinese,c.cardkind,c.cardfee')->where($map)->select();
            $sql=M()->getLastSql();
        }else{
            $result=$this->customs->alias('cu')
                ->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where($where)->field('cu.customid,cu.linktel,cu.namechinese,c.cardkind,c.cardfee')->select();
        }
        if(!empty($result)){
            foreach($result as $k=>$v){
                if((($v['cardkind']==6889||$v['cardkind']==6688)&&$v['cardfee']==1)||$v['cardkind']==6888||$v['cardkind']==2336){
                    $data[$k]=$v;
                }else{
                    returnMsg(array('status'=>'0','msg'=>'此会员卡号段不在规定范围内！'));
                }
            }
        }else{
            if($linktel){
                returnMsg(array('status'=>'0','msg'=>'不存在相关数据!'));
            }else{
                returnMsg(array('status'=>'0','msg'=>'请检查卡号 或卡必须是正常卡！'));
            }

        }
        if($data){
            returnMsg(array('status'=>'1','msg'=>'登陆成功！','data'=>$data,'linktel'=>$linktel));
        }else{
            returnMsg(array('status'=>'0','msg'=>'卡号或密码错误！'));
        }
    }
    //根据会员编号查询会员交易记录相关信息
    public function getTradeMessages(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $daterange=trim($datami['daterange']);
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
        $enddate=date("Ymd",time());
        if($daterange=='week'){
            $startdate = date("Ymd",strtotime('-1 week'));
        }elseif($daterange=='month'){
            $startdate = date('Ymd',strtotime('-1 month'));
        }elseif($daterange=='halfyear'){
            $startdate = date('Ymd',strtotime('-6 month'));
        }elseif($daterange=='year'){
            $startdate = date('Ymd',strtotime('-1 year'));
        }
        $where['tra.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['tra.customid']=$customid;
        $tradetype=array('00'=>'至尊卡消费','02'=>'消费撤销','04'=>'劵消费','08'=>'券消费冲正','13'=>'现金消费','14'=>'积分充值','17'=>'预授权',
            '18'=>'预授权冲正','19'=>'预授权撤销','21'=>'预授权完成','22'=>'预授权完成冲正','24'=>'预授权完成撤销');
        $flag=array('0'=>'正常','1'=>'冲正','2'=>'退货','3'=>'撤销');
        $trademessages=M('trade_wastebooks')->alias('tra')
            ->join('panters pan on tra.panterid=pan.panterid')
            ->where($where)->field('tra.placeddate,tra.placedtime,tra.tradeamount,tra.flag,tra.tradetype,pan.namechinese')->limit(5)->order('tra.placeddate desc')->select();
        if($trademessages){
            foreach($trademessages as $k=>$v){
                $result=array();
                $result['datetime']=date('Y-m-d H:i:s',strtotime($v['placeddate'].$v['placedtime']));
                $result['tradeamount']=$v['tradeamount'];
                $result['tradetype']=$tradetype[$v['tradetype']];
                $result['type']=$flag[$v['flag']];
                $result['namechinese']=$v['namechinese'];
                $datas[]=$result;
            }
            returnMsg(array('status'=>'1','jycoin'=>$datas,'type'=>$daterange));
        }else{
            returnMsg(array('status'=>'0','codemsg'=>'暂无交易记录','type'=>$daterange));
        }
    }
    //获取联盟商家地区、分类以及联盟商家详细信息
    public function getBusinessMessages(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $cityname = trim($datami['cityname']);
        $category = trim($datami['category']);
        $search = trim($datami['search']);
        $where="pa.flag=3 AND pa.revorkflg='N' ";
        //如果城市名称存在，根据城市名称获取城市ID
        if($cityname){
            $cityid=M('city')->where("cityname='{$cityname}'")->getField('cityid');
            $where.=' AND pa.cityid='.$cityid;
        }
        //如果分类存在，分类作为where查询条件
        if($category){
            $where.=" AND pa.hysx='".$category."'";
        }
        //如果搜索条件存在，搜索条件作为where查询条件
        if($search){
            $where.=" AND (ci.cityname like'%".$search."%' OR pa.hysx like'%".$search."%')";
        }
        //获取城市表中河南省的所有城市名称
        $citys=M('city')->where('provinceid=01')->select();
        //所有分类
        $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
        //获取联盟商家详细信息
        $data=M('panters')->alias('pa')
            ->join('city ci on pa.cityid=ci.cityid')
            ->where($where)->field('pa.panterid,pa.address,pa.namechinese,ci.cityname,pa.doorplateimg')->select();
        $total=M('panters')->alias('pa')
            ->join('city ci on pa.cityid=ci.cityid')
            ->where($where)->count();
        if($data){
            returnMsg(array('status'=>'1','city'=>$citys,'hysxs'=>$hysxs,'data'=>$data,'total'=>$total));
        }else{
            returnMsg(array('status'=>'0','city'=>$citys,'hysxs'=>$hysxs,'codemsg'=>'暂无商家','total'=>0));
        }
    }
    //获取所有省份信息
    public function getAllProvince(){
        $provinces=M('province')->select();
        returnMsg($provinces);
    }
    //根据省份ID获取该省份下的城市信息
    public function getAllCity(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $provinceid = trim($datami['provinceid']);
        $city=M('city')->where('provinceid='.$provinceid)->select();
        returnMsg($city);
    }
    //根据城市ID获取该城市下的县区信息
    public function getAllCounty(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $cityid = trim($datami['cityid']);
        $county=M('county')->where('cityid='.$cityid)->select();
        returnMsg($county);
    }
    //保存商家加盟信息
    public function saveBusinessInfo(){
        $model=new model();
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $namechinese = trim($datami['namechinese']);
        $conpername = trim($datami['conpername']);
        $conpermobno = trim($datami['conpermobno']);
        $hysxs= trim($datami['hysxs']);
        $provinceid = trim($datami['provinceid']);
        $cityid = trim($datami['cityid']);
        $address = trim($datami['address']);
        $business = trim($datami['business']);
        $operatescope = trim($datami['operatescope']);
        $dataif['namechinese'] = $namechinese;
        if($hysxs==''){
            returnMsg(array('status'=>'0','msg'=>'行业类型不能为空!'));
        }
        if($namechinese=='')
        {
            returnMsg(array('status'=>'0','msg'=>'商户名称不能为空!'));
        }
        $bool = M('panters')->where($dataif)->find();
        if($bool)
        {
            returnMsg(array('status'=>'0','msg'=>'商户名称不能重复!'));
        }
        if($conpername=='')
        {
            returnMsg(array('status'=>'0','msg'=>'联系人姓名不能为空!'));
        }
        if($conpermobno=='')
        {
            returnMsg(array('status'=>'0','msg'=>'联系人手机不能为空!'));
        }
        $preg = '/^[1][34578][0-9]{9}$/';
        if(!preg_match($preg,$conpermobno))
        {
            returnMsg(array('status'=>'0','msg'=>'联系人手机格式不对!'));
        }
        if($provinceid=='')
        {
            returnMsg(array('status'=>'0','msg'=>'所在省份不能为空!'));
        }
        if($cityid=='')
        {
            returnMsg(array('status'=>'0','msg'=>'所在市不能为空!'));
        }
        if($address=='')
        {
            returnMsg(array('status'=>'0','msg'=>'地址不能为空!'));
        }
        if($business=='')
        {
            returnMsg(array('status'=>'0','msg'=>'营业执照号码不能为空!'));
        }
        if($operatescope=='')
        {
            returnMsg(array('status'=>'0','msg'=>'经营范围不能为空!'));
        }
        $panterid=$this->getnextcode('panters',8);
        $psql="INSERT INTO panters (panterid,namechinese,conpername,conpermobno,hysx,cityid,address,business,stoppayflg,RakeRate,SettlementPeriod,flag,operatescope) VALUES ";
        $psql.="('".$panterid."','".$namechinese."','".$conpername."','".$conpermobno."','".$hysxs."','".$cityid."','".$address."','".$business."','N',100,30,3,'".$operatescope."')";
        if($model->execute($psql)){
            returnMsg(array('status'=>'1','msg'=>'新增商户成功!'));
        }else{
            returnMsg(array('status'=>'0','msg'=>'新增商户失败!'));
        }
    }
    //根据商户ID查询商户信息
    public function getPanteridInfo(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $panterid = trim($datami['panterid']);
        $where['panterid']=$panterid;
        $data=M('panters')->alias('pa')
            ->join('city ci on pa.cityid=ci.cityid')
            ->where($where)->field('pa.*,ci.cityname')->find();
        if($data){
            returnMsg($data);
        }
    }
    //根据会员编号查询会员姓名、账户余额、卡券数量信息
    public function getCustomsInfo(){
        $data = $_POST['datami'];
        $datami = trim($data);
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
        //账户余额信息
        $balance= $this->accountQuery($customid,'00');
        $accountInfo = $this->getCoinInfo($customid);
        //------获取账户下所有卡券数量
        $tickketList=$this->getTicketByCustomid($customid);
        if($tickketList == false || empty($tickketList)){
            $totaltickket = 0;
        }else{
            $totaltickket = array_sum(array_column($tickketList,'amount'));
        }
        //--------end
        returnMsg(array('status'=>'1','balance'=>floatval($balance),
            'jycoin'=>floatval($accountInfo['avilableAmount']),'totaltickket'=>$totaltickket,'customid'=>$customid,'time'=>time()));
    }
    //判断卡系统中是此会员否存在该手机号
    public function checkPhone(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $phone = trim($datami['phone']);
        $customid = trim($datami['customid']);
        $where['cu.linktel']=array('eq',$phone);
        if($customid!=''){
            $where['cc.cid']=array('eq',$customid);
            $customs=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')->where($where)->find();
        }else{
            $customs=$this->customs->alias('cu')->where($where)->find();
        }

        if($customs){
            returnMsg(array('status'=>'1','msg'=>'存在此手机号!'));
        }else{
            returnMsg(array('status'=>'0','msg'=>'不存在此手机号!'));
        }
    }
    //修改此会员手机号
    public function editPhone(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $checkphone = trim($datami['checkphone']);
        $customid = trim($datami['customid']);
        $map['linktel']=$checkphone;
        $where=array('customid'=>$customid);
        $customs=$this->customs->where($where)->save($map);
        if($customs){
            returnMsg(array('status'=>'1','codemsg'=>'修改用户手机号成功!'));
        }else{
            returnMsg(array('status'=>'0','codemsg'=>'修改用户手机号失败!'));
        }
    }
    //根据会员编号查询此会员下所有卡段在6889、6688、6888、2336，其中6889、6688卡段的cardfee值为1的正常卡
    public function getAllCards(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $linktel = trim($datami['linktel']);
        $where=array('cu.linktel'=>$linktel);
        $cardnos=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($where)->field('cc.cid,c.cardno,c.cardkind,c.cardfee')->order('c.customid asc')->select();
        if($cardnos){
            foreach($cardnos as $k=>$v){
                if((($v['cardkind']==6889||$v['cardkind']==6688)&&$v['cardfee']==1)||$v['cardkind']==6888||$v['cardkind']==2336){
                    $result[$k]['cardno']=$v['cardno'];
                    $result[$k]['cid']=$v['cid'];
                }
            }
            returnMsg($result);
        }
    }
    //根据会员编号查询此会员下所有卡段在6889、6688、6888、2336，其中6889、6688卡段的cardfee值为1
    public function getAllCards2(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $where['cc.cid']=$customid;
        $where['c.cardkind']=array('in','6889,6688,6888,2336');
        $cardnos=$this->customs_c->alias('cc')
            ->join('cards c on c.customid=cc.cid')->where($where)->field('c.cardno,c.cardkind,c.cardfee')->order('c.customid asc')->select();
        if($cardnos){
            foreach($cardnos as $k=>$v){
                if((($v['cardkind']==6889||$v['cardkind']==6688)&&$v['cardfee']==1)||$v['cardkind']==6888||$v['cardkind']==2336){
                    $cardno[]=$v['cardno'];
                }
            }
            returnMsg($cardno);
        }
    }
    //根据会员编号获取该会员下的所有有效卡号
    public function getAllCard(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid);

        if($checkKey != $key){
            returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $where['ca.customid']=$customid;
        $where['ca.status']='Y';
        $result=M('cards')->alias('ca')->join('brand b on b.brandid=ca.brandid')
            ->field('ca.cardno,b.brandname')
            ->where($where)
            ->select();
        if($result){
            returnMsg($result);
        }
    }
    //根据手机号查询该手机号下面的所有账户
    public function getAllAccount(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $linktel = trim($datami['linktel']);
        $where['cu.linktel']=$linktel;
        $where['c.cardkind']=array('in','6889,6688,6888,2336');
        $data=$this->customs->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field('cc.cid')->select();
        if($data){
            returnMsg($data);
        }
    }
    //根据手机号查询该手机号下面的所有账户交易信息
    public function getAllTrade(){
        $tradetype=array('00'=>'消费','09'=>'消费冲正','04'=>'消费撤销','13'=>'现金消费','14'=>'积分充值','02'=>'券消费','08'=>'券消费冲正'
        ,'23'=>'券消费撤销','17'=>'预授权','18'=>'预授权冲正','19'=>'预授权撤销','21'=>'预授权完成','22'=>'预授权完成冲正');
        $flag=array('0'=>'正常','1'=>'冲正','2'=>'退货','3'=>'撤销','4'=>'退款');
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $linktel = $datami['linktel'];
        if($datami['starttime']!=''){
            $start=date('Ymd',strtotime($datami['starttime']));
        }
        if($datami['endtime']!=''){
            $end=date('Ymd',strtotime($datami['endtime']));
        }
        if($start!=''&& $end==''){
            $where['tr.placeddate']=array('egt',$start);
        }
        if($start=='' && $end!=''){
            $where['tr.placeddate'] = array('elt',$end);
        }
        if($start!='' && $end!=''){
            $where['tr.placeddate'] = array(array('egt',$start),array('elt',$end));
        }
        if($datami['cardno']!=''){
            $where['c.cardno']=array('eq',$datami['cardno']);
        }
        if($datami['namechinese']!=''){
            $where['p.namechinese']=array('like',"%{$datami['namechinese']}%");
        }
        if(count($datami['cid'])>0){
            $where['cc.cid']=array('in',$datami['cid']);
        }
        $where['cu.linktel']=$linktel;
        $where['c.cardkind']=array('in','6889,6688,6888,2336');
        $data=$this->customs->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('panters p on p.panterid=cu.panterid')
            ->join('cards c on c.customid=cc.cid')
            ->join('trade_wastebooks tr on c.cardno=tr.cardno')
            ->where($where)->field('cc.cid,c.cardno,tr.placeddate,tr.tradetype,tr.flag,tr.tradeamount,p.namechinese')->order('tr.placeddate desc,tr.placedtime desc')->select();
        if($data){
            foreach($data as $k=>$v){
                $v['datetime']=date('Y-m-d',strtotime($v['placeddate']));
                $v['tradetypes']=$tradetype[$v['tradetype']];
                $v['flags']=$flag[$v['flag']];
                $v['tradeamounts']=round($v['tradeamount'],2);
                $result[]=$v;
            }
            returnMsg($result);
        }
    }
    //验证认领时填写的卡号、密码是否正确
    public function verifyInfo(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $cardno = trim($datami['cardno']);
        $password=$this->des->doEncrypt(trim($datami['password']));
        $where['cardno']=$cardno;
        $where['cardpassword']=$password;
        $result=$this->cards->where($where)->find();
        returnMsg($result);
    }
    //根据会员卡号查询此卡号的密码
    public function validateCardno(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $cardno = trim($datami['cardno']);
        $where=array('cardno'=>$cardno);
        $cardpassword=$this->cards->where($where)->getField('cardpassword');
        if($cardpassword){
            $newpassword=$this->des->doDecrypt($cardpassword);
            $newpassword=substr($newpassword,0,6);
            returnMsg($newpassword);
        }
    }
    //根据会员卡号修改此卡号的密码
    public function editCardno(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $password = trim($datami['password']);
        $cardno = trim($datami['cardno']);
        $where=array('cardno'=>$cardno);
        $newpassword=$this->des->doEncrypt($password);
        $map['cardpassword']=$newpassword;
        $result=$this->cards->where($where)->save($map);
        if($result){
            returnMsg($result);
        }
    }
    //根据会员编号查询会员基本信息
    public function getCustoms(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $where=array('customid'=>$customid);
        $result=$this->customs->where($where)->find();
        if($result){
            returnMsg($result);
        }
    }

    //根据会员编号对应表中Cid查询会员基本信息
    public function getCustomsByCid(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $linktel = trim($datami['linktel']);
        $where['cc.cid']=array('eq',$datami['cid']);
        $where['cu.linktel']=array('eq',$linktel);
        $result=$this->customs->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->where($where)
            ->field("cu.*")
            ->find();
        if($result){
            returnMsg($result);
        }
    }
    //根据会员编号验证输入的账户密码是否正确;如正确，修改账户密码
    public function checkAccount(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $password = trim($datami['password']);
        $newpassword = trim($datami['newpassword']);
        $where=array('customid'=>$customid,'paswd'=>$password);
        $result=$this->customs->where($where)->find();
        if($result){
            $map=array('customid'=>$customid);
            $data=array('paswd'=>$newpassword);
            $return=$this->customs->where($map)->save($data);
            if($return){
                returnMsg(array('status'=>'1','msg'=>'账户密码修改成功！'));
            }else{
                returnMsg(array('status'=>'0','msg'=>'账户密码修改失败！'));
            }
        }else{
            returnMsg(array('status'=>'0','msg'=>'原账户密码错误，请重新输入！'));
        }
    }
    //根据会员编号获取该会员下的所有营销券
    public function getAllQuan(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $quanstatus = trim($datami['quanstatus']);
        $map['cu.customid']=$customid;
        $customids=M('customs')->alias('cu')->join('customs_c cc on cu.customid=cc.customid')->where($map)->field('cc.cid')->select();
        $arr=array();
        foreach($customids as $k=>$v){
            $arr[$k]=$v['cid'];
        }
        $enddate=date('Ymd',time());
        if($quanstatus=='notused'){
            $where['q.enddate']=array('gt',$enddate);
        }elseif($quanstatus=='expired'){
            $where['q.enddate']=array('lt',$enddate);
        }
        $where['a.customid']=array('in',$arr);
        $where['a.type']='02';
        $result=M('account')->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('panters p on q.panterid=p.panterid')
            ->field('q.quanid,q.quanname,q.startdate,q.enddate,sum(a.amount) count,p.namechinese')
            ->group('q.quanid,q.quanname,q.startdate,q.enddate,a.amount,p.namechinese')
            ->where($where)
            ->select();
        if($result){
            foreach($result as $k=>$v){
                if($v['count']>0){
                    $data=array();
                    $data['quanname']=$v['quanname'];
                    $data['count']=$v['count'];
                    $data['namechinese']=$v['namechinese'];
                    $data['date']=date('Y.m.d',strtotime($v['startdate'])).'-'.date('Y.m.d',strtotime($v['enddate']));
                    $return[]=$data;
                }
            }
            returnMsg($return);
        }
    }

    //实名验证用户信息时验证身份证号是否已填写过
    public function verifiedPersonid(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $personid = trim($datami['personid']);
        $where['personid']=$personid;
        $result=M('customs')->where($where)->find();
        if($result){
            returnMsg(array('status'=>1,'msg'=>'证件号已经绑定,请更换'));
        }
    }
    //更新实名认证用户信息
    public function editCustomsVerified(){
        $data = $_POST['datami'];
        $datami = trim($data);
        $datami = json_decode($datami,1);
        $linktel = trim($datami['linktel']);
        $personid = trim($datami['personid']);
        $frontimg = trim($datami['frontimg']);
        $reserveimg = trim($datami['reserveimg']);
        $cid = trim($datami['cid']);
        if(trim($datami['sex'])){
            $sex=trim($datami['sex']);
        }else{
            $sex='';
        }
        if(trim($datami['careertype'])){
            $careertype=trim($datami['careertype']);
        }else{
            $careertype='';
        }
        if(trim($datami['residaddress'])){
            $residaddress=trim($datami['residaddress']);
        }else{
            $residaddress='';
        }
        $where['cu.linktel']=$linktel;
        $where['cc.cid']=$cid;
        $data=array('personid' => $personid,'frontimg' => $frontimg,'reserveimg' => $reserveimg,'isverified' => 1,'careertype' => $careertype,'sex' =>$sex,'residaddress' => $residaddress);
        $result=$this->customs->alias('cu')
                ->join('customs_c cc on cc.customid=cu.customid')
                ->where($where)->save($data);
        if($result){
            returnMsg(array('status'=>1,'msg'=>'信息保存成功！'));
        }else{
            returnMsg(array('status'=>0,'msg'=>'信息保存失败！'));
        }
    }
}