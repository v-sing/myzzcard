<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class QuanController extends CommonController
{
  public function _initialize()
  {
    parent::_initialize();
  }
 //营销产品设置
  public function product(){
    $quanid = trim(I('get.quanid',''));
    $quanname= trim(I('get.quanname',''));
    $pname=trim(I('get.pname',''));
    $quanid = $quanid=="营销产品编号"?"":$quanid;
    $quanname = $quanname=="营销产品名称"?"":$quanname;
    $pname = $pname=="机构/商户名称"?"":$pname;
    if($quanid!=''){
        $where['q.quanid']= $quanid;
        $this->assign('quanid',$quanid);
        $map['quanid']=$quanid;
    }
    if($quanname!=''){
        $where['q.quanname']= array('like','%'.$quanname.'%');
        $this->assign('quanname',$quanname);
        $map['quanname']=$quanname;
    }
    if($pname!=''){
        $where['p.namechinese']= array('like','%'.$pname.'%');
        $this->assign('pname',$pname);
        $map['pname']=$pname;
    }
    if($this->panterid!='FFFFFFFF'){
        $where['_string']="q.panterid='".$this->panterid."' or p.parent='".$this->panterid."'";
    }
    //实例化model层
    $m = D('Home/Quan');
    $data=$m->search($where,$map);
    $this->assign('lists',$data['lists']);
    $this->assign('page',$data['page']);
    $this->display();
  }
  //新增营销产品
  public function addProduct()
  {
    $m = D('Home/Quan');
    if(IS_POST){
      $data['quanname']=trim(I('post.quanname',''));
      $startdate=$_POST['startdate'];
      $data['startdate']=str_replace("-","",$startdate);
      $enddate=$_POST['enddate'];
      $data['enddate']=str_replace("-","",$enddate);
      $data['panterid']=trim($_POST['panterid']);
      $data['amount']=trim($_POST['amount']);
      $data['description']=trim($_POST['description']);
      $data['status'] = trim(I('post.status',''));
      $data['userid'] = $this->userid;
      if(empty($data['quanname'])){
          $this->error('营销产品不能为空',U('Quan/addProduct'));
      }
       if(empty($data['panterid'])){
          if(empty($pname)){
              $this->error('商户必选',U('Quan/addProduct'));
          }else{
              $map=array('namechinese'=>$pname);
              $panter=D('panters')->field('panterid')->where($map)->find();
              if($panter==false){
                  $this->error('查无此商户记录,请确定商户名称无误');
              }else{
                  $data['panterid']=$panter['panterid'];
              }
          }
      }
      if(empty($data['amount'])){
          $this->error('产品价格不能为空',U('Quan/addProduct'));
      }
      $data['quanid']=$this->getnextcode('quan_publish',8);
      //返回数据给Model层操作
      $bool=$m->addProduct($data);
      if($bool){
        $this->success('新增营销产品成功');
      }else
      {
        $this->error('新增营销产品失败!');
      }
    }else
    {
      $hysx=$m->hysx();
      $this->assign('hysx',$hysx);
      $this->display();
    }
  }
  //营销产品编辑
  public function editProduct()
  {
    $m = D('Home/Quan');
    if(IS_POST){
     $quanid=trim(I('post.quanid',''));
     $data['quanname']=trim(I('post.quanname',''));
     $data['startdate']=trim(I('post.startdate',''));
     $data['enddate']=trim(I('post.enddate',''));
     $data['panterid']=trim(I('post.panterid',''));
     $data['amount']=trim(I('post.amount',''));
     $data['description']=trim(I('post.description',''));
     $res=$m->editsave($quanid,$data);
     if($res){
       $this->success('修改成功!',U('Quan/product'));
     }else{
       $this->error('修改失败!');
     }
    }else{
      $quanid = trim(I('get.quanid',''));
      if($quanid==''){
        $this->error('非法操作');
      }
      $hysx = $m->hysx();
      $data = $m->editProduct($quanid);
      $this->assign('hysx',$hysx);
      $this->assign('list',$data);
      $this->display();
    }
  }
  //营销产品充值
  public function productpay()
  {
    $m = D('Home/Quan');
    $now = date('Ymd',time());
    $whereq['startdate'] = array('elt',$now);
    $whereq['enddate'] = array('egt',$now);
    if($this->panterid!='FFFFFFFF'){
        $whereq['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
    }
    $quans=$m->quansearch($whereq);
    $this->assign('quankinds',$quans);
    if(IS_POST){
      $data['customid'] = trim(I('post.customid',''));
      $data['puramount'] = trim(I('post.paynumber',''));
      $data['cardno'] = trim(I('post.cardno',''));
      $data['quanid'] = trim(I('post.quanid',''));
      $userid = $this->userid;
      $data['purchaseid'] = $this->getnextcode('quan_purchase',16);
      if($data['cardno']==''){
        $this->error('卡号不能为空',U('Quan/productpay'));
      }
      if($data['quanid']==''){
          $this->error('券类必选',U('Quan/productpay'));
      }
      if($data['puramount']==''){
          $this->error('充值数量必须填写',U('Quan/productpay'));
      }
      $pur = $m->purchase($data,$userid);
      if($pur){
        $this->success('购买成功',U('Quan/productpay'));
      }else{
        $this->error('购买失败');
      }
    }
    else{
      $start = I('get.startdate','');
      $end = I('get.enddate','');
      $customidc = trim(I('get.customidc',''));
      $cnamec	= trim(I('get.cnamec',''));
      $cardnoc	= trim(I('get.cardnoc',''));
      $customid = $customidc=="会员编号"?"":$customidc;
      $cname = $cnamec=="会员名称"?"":$cnamec;
      $cardno = $cardnoc=="卡号"?"":$cardnoc;
      if($start!='' && $end==''){
          $startdate = str_replace('-','',$start);
          $where['a.purdate']=array('egt',$startdate);
          $this->assign('startdate',$start);
          $map['startdate']=$start;
      }
      if($start=='' && $end!=''){
          $enddate = str_replace('-','',$end);
          $where['a.purdate'] = array('elt',$enddate);
          $this->assign('enddate',$end);
          $map['enddate']=$end;
      }
      if($start!='' && $end!=''){
          $startdate = str_replace('-','',$start);
          $enddate = str_replace('-','',$end);
          $where['a.purdate']=array(array('egt',$startdate),array('elt',$enddate));
          $this->assign('startdate',$start);
          $this->assign('enddate',$end);
          $map['startdate']=$start;
          $map['enddate']=$end;
      }
      if($customid!=''){
          $where['b.customid'] = $customid;
          $this->assign('customidc',$customid);
          $map['customidc']=$customid;
      }
      if($cname!=''){
          $where['b.namechinese']=array('like','%'.$cname.'%');
          $this->assign('cnamec',$cname);
          $map['cnamec']=$cname;
      }
      if($cardno!=''){
          $where['f.cardno']=$cardno;
          $this->assign('cardnoc',$cardno);
          $map['cardnoc']=$cardno;
      }
      if($this->panterid!='FFFFFFFF'){
          $where1['c.panterid']=$this->panterid;
          $where1['c.parent']=$this->panterid;
          $where1['_logic']='OR';
          $where['_complex']=$where1;
      }
      $data=$m->pursearch($where,$map);
      session('quanexcel',$where);
      $this->assign('list',$data['list']);
      $this->assign('page',$data['page']);
      $this->display();
    }
  }
  //营销产品充值excel导出
  public function purquery_excel()
  {
    $m = D('Home/Quan');
    $map=session('quanexcel');
    if(!empty($map)){
      foreach ($map as $key => $val){
        $where[$key] = $val;
      }
    }
    $lists=$m->purquery($where);
    $strlists="会员编号,会员名称,卡号,充值日期,充值时间,充值券编号,充值券名称,充值数量,到期时间,充值机构,操作员\n";
    $strlists =iconv('utf-8','gbk',$strlists);
    foreach ($lists as $key => $val) {
      $val['namechinese']=iconv('utf-8','gbk',$val['namechinese']);
      $val['purdate'] = date('Y-m-d',strtotime($val['purdate']));
      $val['overdate'] = date('Y-m-d',strtotime($val['overdate']));
      $val['quanname']=iconv('utf-8','gbk',$val['quanname']);
      $val['pantername']=iconv('utf-8','gbk',$val['pantername']);
      $val['username']=iconv('utf-8','gbk',$val['username']);
      $strlists.= $val['customid']."\t,".$val['namechinese'].',';
      $strlists.= $val['cardno']."\t,".$val['purdate']."\t,";
      $strlists.=$val['purtime']."\t,".$val['quanid']."\t  ,";
      $strlists.=$val['quanname'].','.$val['puramount'].',';
      $strlists.=$val['overdate']."\t,".$val['pantername'].',';
      $strlists.=$val['username']."\n";
    }
    $filename='营销产品充值报表'.date('YmdHis');
    $filename=iconv("utf-8","gbk",$filename);
    $this->load_csv($strlists, $filename);
  }
}
?>
