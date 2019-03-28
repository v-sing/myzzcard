<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class SearchsController extends CommonController {
    public function _initialize(){
        parent::_initialize();
    }
    function index(){
        $cusname=trim($_REQUEST['cusname']);
        $personid=trim($_REQUEST['personid']);
        $cusname = $cusname=="会员名称"?"":$cusname;
        $personid = $personid=="证件号码"?"":$personid;
        if(!empty($cusname)){
            $where['namechinese']=array('like','%'.$cusname.'%');
            $this->assign('cusname',$cusname);
        }
        if(!empty($personid)){
            $where['personid']=$personid;
            $this->assign('personid',$personid);
        }
        $model=new model();
        $count=$model->table('searchs')->where($where)->count();
        $p=new \Think\Page($count, 12);
        $custom_list=$model->table('searchs')->where($where)->limit($p->firstRow.','.$p->listRows)
                     ->order('searchid')->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$custom_list);
        $this->assign('page',$page);
		$this->display();
    }

    function add(){
        if(IS_POST){
            $searchs=M('searchs');
            $searchs->startTrans();
            $namechinese=trim($_POST['namechinese']);
            $personid=trim($_POST['personid']);
                if(empty($namechinese)){
                    $this->error('名字必填');
                }
//                if(empty($personid)){
//                    $this->error('身份证必填');
//                }
            $searchid=$this->getnextcode('searchs',8);
            $sql="insert into searchs values('{$searchid}','{$personid}','{$namechinese}')";
            if($searchs->execute($sql)){
                $searchs->commit();
                $this->success('黑名单信息添加成功');
            }else{
                $searchs->rollback();
                $this->error('信息添加失败');
            }
        }
        else{
            $this->display();
        }
    }
    function edit(){
        if(IS_POST){
            $custom=M('searchs');
            $data['namechinese']=trim($_POST['cuname']);
            $data['personid']=trim($_POST['personid']);
            $searchid=trim($_POST['searchid']);
                if(empty($data['namechinese'])){
                    $this->error('姓名必填');
                }
                if(empty($data['personid'])){
                    $this->error('身份证必填');
                }
            if($custom->create()){
                if($custom->where('searchid='.$searchid)->save($data)){
                    $this->success('修改信息资料成功',U('searchs/index'));
                }else{
                    $this->error('修改失败');
                }
            }
        }else{
            $searchid=trim($_REQUEST['searchid']);
            if(empty($searchid)){
                $this->error('信息ID缺失');
            }
            $where['searchid']=$searchid;
            $custom_info=M('searchs')->where($where)->find();
            $this->assign('info',$custom_info);
            $this->display();
        }
    }

    function redlist(){
        if(IS_POST){
            $name=$_POST['name'];
            $where['name']=array('like','%'.$name.'%');
            session('redCon',$where);
        }
        $list=M('redlist')->where($where)->select();
        $this->assign('list',$list);
        $this->display();
    }

    function redlist_excel(){
        $where=session('redCon');
        $sell_list = M('redlist')->where($where)->select();

        $sellCard_list = array();
        $strlist = "姓名,性别,职务,证件号码,护照号码,出逃时间,出逃国家/地区,立案单位,罪名,立案时间,立案编号\n";
        $strlist = $this->changeCode($strlist);
        foreach ($sell_list as $key=>$val) {
            foreach ($val as $k=>$v){
                $val[$k] = $this->changeCode($v);
            }
            $strlist .= $val['name']  . ',' . $val['sex'] . ',';
            $strlist .= $val['career']  . ',' . $val['personid']. "\t" . ',';
            $strlist .= $val['passportid'] . "\t" . ',' . $val['runtime'] . ',';
            $strlist .= $val['runcountry']  . ',' . $val['unit'] . ','. $val['crime'] . ',';
            $strlist .= $val['registertime']  . ',' . $val['registerid'] . "\t". "\n";
        }
        $filename = '红通名单报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist, $filename);
    }
}
