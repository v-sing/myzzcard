<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class SystemController extends CommonController{
	public function province(){
		$model=M('province');
        $provincename=trim(I("get.provincename"));
        $cityname=trim(I("get.cityname"));
				$provincename = $provincename=="省份名称"?"":$provincename;
				$cityname = $cityname=="城市名称"?"":$cityname;
        if(IS_POST){//城市修改
            $cityid=trim(I('post.cityid',''));
            $cityname=trim(I('post.cname',''));
            $provincename=trim(I('post.provincename',''));
						$cityid=$cityid=="城市编码"?"":$cityid;
						$cityname = $cityname=="城市名称"?"":$cityname;
            if($provincename==''){
                $this->error('省份名称不能为空');
            }
            $where['provincename']=$provincename;
						//
						if($cityid=='')
						{
							$this->error("城市编号为空，请选择下面编号");
						}
            $pid=$model->where($where)->find();
            $re=M('city')->execute("update city set cityname='".$cityname."', provinceid='".$pid['provinceid']."' where cityid=".$cityid);
            if($re){
                $this->success('城市名称修改成功');
            }else{
                $this->error('城市名称修改失败');
            }
        }
        if(!empty($cityname)){
            $where['c.cityname']=array('like','%'.$cityname.'%');
            $this->assign('cityname',$cityname);
            $map['cityname']=$cityname;
        }
        if(!empty($provincename)){
            $where['p.provincename']=array('like','%'.$provincename.'%');
            $this->assign('provincename',$provincename);
            $map['provincename']=$provincename;
        }
        $field.="p.provincename,p.provinceid,";
        $field.="c.cityname,c.cityid";
        $count=$model->table('__PROVINCE__')->alias('p')
             ->join("__CITY__ c on p.provinceid=c.provinceid")
             ->where($where)
             ->count();
        $p=new \Think\Page($count, 15 );
        $list=$model->table('__PROVINCE__')->alias('p')
             ->join("__CITY__ c on p.provinceid=c.provinceid")
             ->where($where)
             ->field($field)
             ->order('p.provinceid asc')
             ->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page=$p->show();
        $listprovince=$model->order("provinceid asc")->select();
        $this->assign('listprovince',$listprovince);
        $this->assign('list',$list);
        $this->assign('page',$page);
		$this->display();
	}
	//省份添加
	function addprovince(){
		$model=M('province');
		$city = M('city');
	    if(IS_POST){
	        $provinceid=I('post.provinceid','');
            $cityname=I('post.cityname','');
	        if($cityname==''){
	        	$this->error("城市名称不能为空");
	        }
            if($provinceid==''){
                $this->error("省份名称不能为空");
            }
						//城市不允许重名
						$cityname =rtrim($cityname,"市")."市";//地级市统一格式如：郑州市
						$cityif['cityname'] = $cityname;
						if($city->where($cityif)->find())
						{
							$this->error("城市名已经存在");exit;
						}
            $id=$this->getnextcode('city');
	        $sql="insert into city(cityid,cityname,provinceid) values('".$id."','".$cityname."','".$provinceid."')";
	        if($model->execute($sql)){
	          $model->commit();
	          $this->success('城市添加成功',U('System/province'));
	        }else{
	          $model->rollback();
	          $this->error('城市添加失败');
	        }
	    }
        $list=$model->select();
        $this->assign('list',$list);
        $this->display();
	}

    //县区管理
	public function city(){
		$model=M('city');
        $countyname=trim(I("get.countyname"));
        $cityname=trim(I("get.cityname"));
				$countyname = $countyname=="县区名称"?"":$countyname;
				$cityname = $cityname=="城市名称"?"":$cityname;
        if(IS_POST){//更新县区
            $countyid=I("post.countyid",'');
            $cityname=I("post.cityname",'');
            $countyname=I("post.countyname",'');
            $where['cityname']=$cityname;
						$countyid=$countyid=="县区编码"?"":$countyid;
						//
						if($countyid=='')
						{
							$this->error("县区编号为空，请选择下面编号");
						}
            $cid=$model->where($where)->find();
            $re=M('county')->execute("update county set countyname='".$countyname."',cityid='".trim($cid['cityid'])."' where countyid=".$countyid);
            if($re){
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }
        if(!empty($cityname)){
            $where['c.cityname']=array('like','%'.$cityname.'%');
            $this->assign('cityname',$cityname);
            $map['cityname']=$cityname;
        }
        if(!empty($countyname)){
            $where['p.countyname']=array('like','%'.$countyname.'%');
            $this->assign('countyname',$countyname);
            $map['countyname']=$countyname;
        }
        $field.="p.countyname,p.countyid,";
        $field.="c.cityname,c.cityid";
        $count=$model->table('__CITY__')->alias('c')
             ->join("__COUNTY__ p on p.cityid=c.cityid")
             ->where($where)
             ->count();
        $p=new \Think\Page($count, 15 );
        $list=$model->table('__CITY__')->alias('c')
             ->join("__COUNTY__ p on p.cityid=c.cityid")
             ->where($where)
             ->field($field)
             ->order('c.cityid asc')
             ->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page=$p->show();
        $listcity=$model->order("cityid asc")->select();
        $this->assign('listcity',$listcity);
        $this->assign('list',$list);
        $this->assign('page',$page);
		$this->display();
	}
    function addcity(){
        $model=M('city');
				$county = M('county');
        if(IS_POST){
            $countyname=trim(I("post.countyname"));
            $cityid=trim(I("post.cityid"));
            if($countyname==''){
                $this->error("县区名称不能为空");
            }
            if($cityid==''){
                $this->error("城市名称不能为空");
            }
						//县区不能重复
						$countyif['countyname'] = rtrim($countyname,"");
						if($county->where($countyif)->find())
						{
							 $this->error("县区已经存在");exit;
						}
            $id=$this->getnextcode('county');
            $sql="insert into county(countyid,cityid,countyname) values(".trim($id).",'".$cityid."','".$countyname."')";
            if($model->execute($sql)){
              $model->commit();
              $this->success('县区添加成功',U('System/city'));
            }else{
              $model->rollback();
              $this->error('县区添加失败');
            }
        }
        $list=$model->select();
        $this->assign('list',$list);
        $this->display();
    }

    //修改密码
    function upPassword(){
        if(IS_POST){
            $r1='/[A-Z]/';  //uppercase
            $r2='/[a-z]/';  //lowercase
            $r3='/[0-9]/';  //numbers
            $r4='/[~!@#$%^&*()\-_=+{};:<,.>?]/';  // special char
            $old_password=$_POST['old_password'];
            $new_password=$_POST['new_password'];
            $re_password=$_POST['re_password'];
            if(preg_match_all($r1,$new_password, $o)<1) {
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("密码必须包含至少一个大写字母，请返回修改！");
            }
            if(preg_match_all($r2,$new_password, $o)<1) {
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("密码必须包含至少一个小写字母，请返回修改！");
            }
            if(preg_match_all($r3,$new_password, $o)<1) {
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("密码必须包含至少一个数字，请返回修改！");
            }
            if(preg_match_all($r4,$new_password, $o)<1) {
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("密码必须包含至少一个特殊符号：[~!@#$%^&*()\-_=+{};:<,.>?]，请返回修改！");
            }
            if(strlen($new_password)<6){
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("密码长度不低于6位");
            }
            if($new_password!=$re_password){
                $this->assign("jumpUrl",__APP__."/System/upPassword");
                $this->error("两次密码不一致");
            }
            $m=M();
            $userid=$_SESSION['userid'];
            $list=$m->query("select * from users where userid='{$userid}'");
            if(md5($old_password)!=$list[0]['password1']){
                $this->error("原密码错误");
            }
            else{
                $n=md5($new_password);
//				$d=date('Ymd');
//				echo $d;exit;
                $da=date('Ymd',strtotime('+3 month'));
                $r=$m->execute("update users set password1='{$n}',enddate='{$da}' where userid='{$userid}'");
                if($r){
                    unset($_SESSION['userid']);
                    unset($_SESSION['username']);
                    $this->assign("jumpUrl",__APP__."/Public/login");
                    $this->success("更改成功，请重新登录");
                }
                else{
                    $this->success("操作失败");
                }
            }
        }
        else{
            $this->display();
        }
    }

}
?>
