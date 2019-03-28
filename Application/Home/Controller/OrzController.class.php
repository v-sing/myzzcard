<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class OrzController extends CommonController{
  protected $panter;
  public function _initialize()
  {
        parent::_initialize();
        $this->panter=M('panters');
  }
  function index(){
    $model = M('panters');
    $revork1=array('N'=>'正常机构','Y'=>'禁用机构');
    $revork=array('Y'=>'是','N'=>'否');
    $where['flag'] = '1';
    $pname=trim(I('get.pname',''));
    $revorkflg=trim(I('get.revorkflg',''));
    $pname = $pname=="商户名称"?"":$pname;
    $nameenglish = trim(I('get.nameenglish',''));
    $nameenglish = $nameenglish=="商户简称"?"":$nameenglish;
    if($pname!=''){
        $where['namechinese']=array('like','%'.$pname.'%');
        $this->assign('pname',$pname);
        $map['pname']=$pname;
    }
    if($nameenglish!=''){
      $where['nameenglish']=array('like','%'.$nameenglish.'%');
      $this->assign('nameenglish',$nameenglish);
      $map['nameenglish']=$nameenglish;
    }
    if($nameenglish!=''){
      $where['nameenglish']=array('like','%'.$nameenglish.'%');
      $this->assign('nameenglish',$nameenglish);
      $map['nameenglish']=$nameenglish;
    }
    if($revorkflg!=''){
      $where['revorkflg']=$revorkflg;
      $this->assign('revorkflg',$revorkflg);
    }
    else $where['revorkflg']='N';
    $count=$model->where($where)->count();
    $p=new \Think\Page($count, 10 );
    $panters_list=$model->where($where)->limit($p->firstRow.','.$p->listRows)
          ->order('panterid desc')->select();
    if(!empty($map)){
        foreach($map as $key=>$val) {
            $p->parameter[$key]= $val;
        }
    }
    $page = $p->show();
    $this->assign('revork',$revork);
    $this->assign('revork1',$revork1);
    $this->assign('list',$panters_list);
    $this->assign('page',$page);
    $this->display();
  }
  function add(){
    $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
    $panter = M('panters');
    if(IS_POST){
      $namechinese = trim(I('post.namechinese',''));
      $nameenglish = trim(I('post.nameenglish',''));
      $address = trim(I('post.address',''));
      $hysx = trim(I('post.hysx',''));
      $revorkflg = trim(I('post.revorkflg',''));
      $settlementperiod = trim(I('post.settlementperiod',''));
      $rakerate = trim(I('post.rakerate',''));
      $panterid=$this->getnextcode('panters',8);
      $flag='1';
      $placeddate = date('Ymd',time());
      $sql  = "INSERT INTO panters (panterid,namechinese,nameenglish,address,hysx,revorkflg,settlementperiod,rakerate,flag,placeddate) VALUES";
      $sql .= "('".$panterid."','".$namechinese."','".$nameenglish."','".$address."','".$hysx."','".$revorkflg."','".$settlementperiod."','".$rakerate."','".$flag."','".$placeddate."')";
      if($panter->execute($sql)){
        $this->success('新增机构成功!');
      }else $this->error('新增失败,请重试！');
    }
    $this->assign('hysxs',$hysxs);
    $this->display();
  }
  function edit(){
    $panterid=trim(I('get.panterid',''));
    if(IS_POST){
      $data['namechinese'] = trim(I('post.namechinese',''));
      $$data['nameenglish'] = trim(I('post.nameenglish',''));
      $data['address'] = trim(I('post.address',''));
      $data['hysx'] = trim(I('post.hysx',''));
      $data['revorkflg'] = trim(I('post.revorkflg',''));
      $data['settlementperiod'] = trim(I('post.settlementperiod',''));
      $rakerate = trim(I('post.rakerate',''));
      $panterid=trim(I('post.panterid',''));
      if($panterid!='')$where['panterid']=$panterid;
      else $this->error('非法操作');
        if($this->panter->where($where)->save($data))$this->success('机构修改成功!',U('Orz/index'));
        else $this->error('修改失败，请重试!');
      }
    if($panterid=='')$this->error('非法操作！');
    $where['panterid']=$panterid;
    $where['flag'] = '1';
    $list  = $this->panter->where($where)->find();
    $list==true ||$this-error('未查询到该机构');
    $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
    $this->assign('hysxs',$hysxs);
    $this->assign('list',$list);
    $this->display();
  }
}
?>
