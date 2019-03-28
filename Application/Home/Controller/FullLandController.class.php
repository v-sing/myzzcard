<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class FullLandController extends CommonController {
    protected $keycode;
    protected $url;
    public function _initialize(){
        parent::_initialize();
        $this->keycode='JYO2O01';
        $this->url='http://192.168.10.22:8081/activate.php?app=amunbind';
    }
    public function menuList(){
        $panterid=$this->panterid;
        $panterid='00003696';
        $where['m.panterid']=array('eq',$panterid);
        $field ="m.*,p.namechinese";
        $menus=M('menus')->alias('m')
            ->join('panters p on m.panterid=p.panterid')
            ->where($where)->field($field)->order('sort asc')->select();
        $arr='';
        if($menus){
//            foreach($menus as $k=>$v){
//                $quan=$quanrs='';
//                $quans=explode(',',$v['quans']);
//                foreach($quans as $m=>$n){
//                    $quanids=explode(':',$n);
//                    $quan['quanid']=$quanids[0];
//                    $quan['quanname']=$this->getQuan($quanids[0]);
//                    $quan['num']=$quanids[1];
//                    $quanrs[]=$quan;
//                }
//                $v['quan']=$quanrs;
//                unset($v['quans']);
//                $arr[]=$v;
//            }
            foreach($menus as $k=>$v){
                $quanname='';
                $quans=explode(',',$v['quans']);
                foreach($quans as $m=>$n){
                    $quanids=explode(':',$n);
                    if($v['relation']==1){
                        if($m==0){
                            $quanname.=$quanids[1].'张'.$this->getQuan($quanids[0]);
                        }else{
                            $quanname.='或'.$quanids[1].'张'.$this->getQuan($quanids[0]);
                        }
                    }else{
                        if($m==0){
                            $quanname.=$quanids[1].'张'.$this->getQuan($quanids[0]);
                        }else{
                            $quanname.='、'.$quanids[1].'张'.$this->getQuan($quanids[0]);
                        }
                    }
                }
                $v['quanname']=$quanname;
                $arr[]=$v;
            }
        }
//        $this->ajaxReturn($arr);
        $count=M('menus')->alias('m')
            ->join('panters p on m.panterid=p.panterid')
            ->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $page = $p->show();
        $this->assign('menus',$arr);
        $this->assign('page',$page);
        $this->assign('count',$count);
        $this->display();
    }

    public function addMenu(){
        $panterid=$this->panterid;
        $panterid='00003696';
        $quankind=M('quankind')->where(array('panterid'=>$panterid))->select();
        if($_POST){
            $name=trim($_POST['name']);
            $quans=trim($_POST['quans']);
            $panterid=trim($_POST['panterid']);
            $amount=trim($_POST['amount']);
            $relation=trim($_POST['relation']);
            $sort=trim($_POST['sort']);
            $menuid=trim($this->getFieldNextNumber('menusid'));
            $nowtime=intval(time());
            $menus=M('menus')->where(array('name'=>$name,'panterid'=>$panterid))->find();
            if($menus){
                $this->error('套餐已存在');
            }
            $menuSql="INSERT INTO menus (id,name,panterid,quans,amount,addtime,relation,sort) VALUES ('{$menuid}','{$name}','{$panterid}','{$quans}','{$amount}','{$nowtime}','{$relation}','{$sort}')";
            $menuIf=M()->execute($menuSql);
            if($menuIf==true){
                $this->error('套餐添加成功',U('FullLand/menuList'));
            }else{
                $this->error('套餐添加失败',U('FullLand/addMenu'));
            }
        }
        $this->assign('quankind',$quankind);
        $this->assign('panterid',$panterid);
        $this->display();
    }

    public function editMenu(){
        if($_POST){
            $id=trim($_POST['id']);
            $name=trim($_POST['name']);
            $quans=trim($_POST['quans']);
            $panterid=trim($_POST['panterid']);
            $amount=trim($_POST['amount']);
            $relation=trim($_POST['relation']);
            $sort=trim($_POST['sort']);
            $menuSql="UPDATE menus set name='{$name}',quans='{$quans}',panterid='{$panterid}',amount='{$amount}',relation='{$relation}',sort='{$sort}' WHERE id='{$id}'";
            $menuIf=M()->execute($menuSql);
            if($menuIf==true){
                $this->error('套餐更新成功',U('FullLand/menuList'));
            }else{
                $this->error('套餐更新失败',U('FullLand/editMenu'));
            }
        }else{
            $panterid=$this->panterid;
            $panterid='00003696';
            $id = trim(I('get.id',''));
            $quankind=M('quankind')->where(array('panterid'=>$panterid))->select();
            $menus=M('menus')->where(array('id'=>$id))->find();
            if(empty($menus)){
                $this->error('信息有误',U('FullLand/menuList'));
            }else{
                $quan=$quanrs='';
                $quans=explode(',',$menus['quans']);
                foreach($quans as $m=>$n){
                    $quanids=explode(':',$n);
                    $quan['quanid']=$quanids[0];
                    $quan['num']=$quanids[1];
                    $quanrs[]=$quan;
                }
                $menus['quan']=$quanrs;
            }
        }
        $this->assign('id',$id);
        $this->assign('panterid',$panterid);
        $this->assign('menus',$menus);
        $this->assign('quankind',$quankind);
        $this->display();
    }

    public function delMenu(){
        $id = trim(I('get.id',''));
        if($id==''){
            $this->error('非法操作');
        }
        $menuIf=M('menus')->where(array('id'=>$id))->delete();
        if($menuIf==true){
            $this->error('套餐删除成功',U('FullLand/menuList'));
        }else{
            $this->error('套餐删除失败',U('FullLand/menuList'));
        }
    }

    public function getQuan($quanid){
        $result=M("quankind")->where(array('quanid'=>$quanid))->getField('quanname');
        return $result;
    }
}