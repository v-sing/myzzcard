<?php
namespace Home\Controller;
use Think\Controller;
class RbacController extends CommonController {
	//角色//
    function roleList(){
		$m=M('think_role');
        $name=$_REQUEST['name'];
        if(!empty($name)){
            $where['name']=array('like','%'.$name.'%');
            $this->assign('name',$name);
        }
        $c=$m->where($where)->count();
        $this->assign('count',$c);
        $p=new \Think\Page($c, 20);
		$list=$m->where($where)->limit($p->firstRow.','.$p->listRows)->order('id asc')->select();
        if(!empty($where)){
            foreach($where as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
    }	
	function addRole(){
		if(IS_POST){
			$m=M();
			$name=$_POST['name'];
			$status=$_POST['status'];
			$remark=$_POST['remark'];
			$sql="insert into think_role(id,name,status,remark) values(seq_think_role.nextval,'{$name}',$status,'{$remark}')";
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/roleList"); 
				$this->success("添加成功");
			}
			else{
				$this->error("操作失败");
			}
		}else{
			$this->display();
		}
		
	}	
	function editRole(){
		$id=intval($_REQUEST['id']);
		if(empty($id)||$id<=0){
			$this->error("参数错误");
		}
		$m=M('think_role');
		if(IS_POST){
			$name=$_POST['name'];
			$status=$_POST['status'];
			$remark=$_POST['remark'];
			$sql="update think_role set name='{$name}',status=$status,remark='{$remark}' where id=$id";
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/roleList"); 
				$this->success("修改成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{
			$list=$m->query("select * from think_role where id=$id");			
			$this->assign('list',$list[0]);
			$this->display("addRole");
		}
	}
	
	//用户//
	function userList(){
		$m=M('users');
        $panterid=$_REQUEST['panterid'];
        $username=$_REQUEST['username'];
		$loginname=$_REQUEST['loginname'];
        $teleno=$_REQUEST['teleno'];
        $name=$_REQUEST['name'];
        if(!empty($panterid)){
            $where['panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
		if(!empty($loginname)){
            $where['loginname']=array('like','%'.$loginname.'%');
            $this->assign('loginname',$loginname);
        }
        if(!empty($username)){
            $where['username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
        }
        if(!empty($teleno)){
            $where['teleno']=$teleno;
            $this->assign('teleno',$teleno);
        }
        if(!empty($name)){
            $where['c.name']=array('like','%'.$name.'%');
            $this->assign('name',$name);
        }
		$count=$m->join("left join think_role_user b on users.userid=b.user_id left join think_role c on b.role_id=c.id")->where($where)->count();
//        echo $count;exit;
        $this->assign('count',$count);
        $p=new \Think\Page($count, 20 );
        $list=$m->join("left join think_role_user b on users.userid=b.user_id left join think_role c on b.role_id=c.id")->where($where)->limit($p->firstRow.','.$p->listRows)->order('userid')->select();
		//"select * from users a left join think_role_user b on a.userid=b.user_id left join think_role c on b.role_id=c.id";
        $page = $p->show();
        $this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
	}
	function addUser(){
		if(IS_POST){
			$userid=$this->getnextcode('users',16);
			$loginname=$_POST['loginname'];
			$password1=md5($_POST['password1']);
			$description=$_POST['description'];
			$username=$_POST['username'];
			$teleno=$_POST['teleno'];
			$panterid=$_POST['panterid'];
			$active=$_POST['active'];
			$sql="insert into users(userid,loginname,password1,description,username,teleno,panterid,active,wrpass) values('{$userid}','{$loginname}','{$password1}','{$description}','{$username}','{$teleno}','{$panterid}','{$active}',0)";
			$m=M();
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/userList"); 
				$this->success("添加成功");
			} else {
				$this->error("操作失败");
			}
		} else {
			$this->display();
		}
	}
	function editUser(){
		$m=M('users');
		if(IS_POST){
			$userid=$_POST['userid'];
			$loginname=$_POST['loginname'];
			$password1=$_POST['password1'];
			$description=$_POST['description'];
			$username=$_POST['username'];
			$teleno=$_POST['teleno'];
			$panterid=$_POST['panterid'];
			$active=$_POST['active'];
			if($password1=='******'){
				$sql="update users set loginname='{$loginname}',description='{$description}',username='{$username}',teleno='{$teleno}',panterid='{$panterid}',active='{$active}',wrpass=0 where userid='{$userid}'";
			}
			else{
				$password1=md5($password1);
				$sql="update users set loginname='{$loginname}',password1='{$password1}',description='{$description}',username='{$username}',teleno='{$teleno}',panterid='{$panterid}',active='{$active}',wrpass=0 where userid='{$userid}'";
			}
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/userList"); 
				$this->success("修改成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{
			$user_id=$_GET['user_id'];
			$list=$m->query("select * from users where userid='{$user_id}'");
			$this->assign('list',$list[0]);
			$this->display();
		}
		
		
	}
	function userSet(){
		$m=M('think_role');
		if(IS_POST){
			$user_id=$_POST['user_id'];
			$role_id=$_POST['role_id'];
			$sql="delete think_role_user where user_id='{$user_id}'";
			$r=$m->execute($sql);
			if($r||$r==0){
				$sql2="insert into think_role_user values($role_id,'{$user_id}')";
				$r2=$m->execute($sql2);
				if($r2){
					$this->assign("jumpUrl",__APP__."/Rbac/userList"); 
					$this->success("设置成功");
				}
				else{
					$this->error("操作失败2");
				}
			}
			else{
				$this->error("操作失败1");
			}
		}
		else{
			$user_id=$_REQUEST['user_id'];
			$name=$_REQUEST['name'];
			$this->assign('user_id',$user_id);
			$list=$m->query("select * from think_role order by id");
			$this->assign('list',$list);
            $this->assign('name',$name);



			$this->display();
		}	
	}
	//节点//
	function nodeList(){	
		$m=M("think_node");
		$list=$m->query("select id,name,title,pid from think_node order by sort,id");
		$a=node_merge($list);
		$this->assign('a',$a);
		$this->display();		
	}
	function addNode(){
		$pid=intval($_REQUEST['pid']);
		if(empty($pid)||$pid<0){
			$this->error("参数错误");
		}
		$lev=intval($_REQUEST['lev']);
		if($lev==2){
			$this->typeName='控制器';
		}
		else if($lev==3){
			$this->typeName='方法';
		}
		else{
			$this->error("参数错误");
		}
		if(IS_POST){
			$m=M();
			$name=$_POST['name'];
			$title=$_POST['title'];
			$status=$_POST['status'];
			$sort=$_POST['sort']?$_POST['sort']:100;
			$sql="insert into think_node(id,name,title,status,sort,pid,lev) values(seq_think_node.nextval,'{$name}','{$title}',$status,$sort,$pid,$lev)";
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/nodeList"); 
				$this->success("添加成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{
			$this->assign('pid',$pid);
			$this->assign('lev',$lev);
			$this->display();
		}	
	}
	function editNode(){
		$id=intval($_REQUEST['id']);
		if(empty($id)||$id<=0){
			$this->error("参数错误");
		}
		$m=M('think_node');
		if(IS_POST){
			$name=$_POST['name'];
			$title=$_POST['title'];
			$status=$_POST['status'];
			$sort=$_POST['sort'];
			$sql="update think_node set name='{$name}',title='{$title}',status=$status,sort=$sort where id=$id";
			$r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Rbac/nodeList"); 
				$this->success("修改成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{	
			$list=$m->query("select * from think_node where id=$id");
			$this->assign('list',$list[0]);
			if($list[0]['lev']==2){
			$this->typeName='控制器';
			}
			else if($list[0]['lev']==3){
				$this->typeName='方法';
			}
			$this->display('addNode');
		}
	}
	function delNode(){
		$id=intval($_REQUEST['id']);
		if(empty($id)||$id<=0){
			$this->error("参数错误");
		}
		$m=M();
		$sql="delete think_node where id=$id";
		$r=$m->execute($sql);
		if($r){
			$this->assign("jumpUrl",__APP__."/Rbac/nodeList"); 
			$this->success("删除成功");
		}
		else{
			$this->error("操作失败");
		}
	}
	//Access
	function accessList(){
		$id=intval($_GET['id']);
		if(empty($id)||$id<=0){
			$this->error('参数错误');
		}
		$this->assign('id',$id);
		$m=M();
		$list=$m->query("select id,name,title,pid from think_node order by sort,id");
		$list2=$m->query("select node_id from think_access where role_id=$id");
		foreach($list2 as $v){
			$arr[]=$v['node_id'];
		}
		$a=node_merge($list,$arr);
		$this->assign('a',$a);
		$this->display();
	}
	function setAccess(){
		$role_id=$_POST['role_id'];
		$m=M();
		//先清空
		$sql="delete think_access where role_id=$role_id";
		$m->execute($sql);
		foreach($_POST['access'] as $k=>$v){
			$tmp=explode('_',$v);
			$sql="insert into think_access(role_id,node_id,lev) values($role_id,$tmp[0],$tmp[1])";	
			$r=$m->execute($sql);
		}
		if($r){
			$this->assign("jumpUrl",__APP__."/Rbac/accessList/id/$role_id"); 
			$this->success("设置成功");
		}
		else{
			$this->error("操作失败");
		}
	}



	//---------------------------外拓商户批量分账户功能

	public function batchOpenOutAccount(){
		$stringData = "";
		if(IS_POST){
			$upload = $this->_upload("xls");
			$filename=PUBLIC_PATH.$upload['file_stu']['savepath'].$upload['file_stu']['savename'];
			$exceldate=$this->import($filename);
			foreach ($exceldate as $k=>$v){
				if($k>=1){
					$panterid = trim($v[0]);
					$name     = trim($v[1]);
					$phone    = trim($v[7])?$v[7]:'1';
					$password = md5('123456');
					$loginname='out_'.$panterid;
					if(M('users')->where(['loginname'=>$loginname])->find()){
						$stringData .= "$name-----{$panterid}------账户:".$loginname."------已经开户"."<br/>";
						continue;
					}
					$active   = 'Y';
					$userid=$this->getnextcode('users',16);
					$sql="insert into users(userid,loginname,password1,description,username,teleno,panterid,active,wrpass) values('{$userid}','{$loginname}','{$password}','{$name}','{$name}','{$phone}','{$panterid}','{$active}',0)";

					$role_id = '522';
					$roleSql = "insert into think_role_user values($role_id,'{$userid}')";

					$useif = M('users')->execute($sql);
					$roleif= M('think_role_user')->execute($roleSql);
					if($useif && $roleif){
						$stringData .= "$name-----{$panterid}------账户:".$loginname."------开户成功"."<br/>";
						$vp[] = $panterid;
					}

				}
			}
		}
		if(!empty($stringData)){
			$this->assign("data",$stringData);
		}
		if(isset($vp) && $vp){
			file_put_contents('out_panter.txt',json_encode($vp),8);
		}
		$this->display();
	}
	protected  function import($filename){
		/**默认用excel2005读取excel，若格式不对，则用之前的版本进行读取*/
		$PHPReader = new \PHPExcel_Reader_Excel5();
		try{
			if(!$PHPReader->canRead($filename)){
				$PHPReader = new \PHPExcel_Reader_Excel2007();
				if(!$PHPReader->canRead($filename)){
					throw new \Think\Exception('只能读取03,05版excel');
				}
			}
		}
		catch(\Exception $e){
			echo $e->getMessage();
		}
		$PHPReader->setReadDataOnly(true); //设置只读取数据
//         $PHPReader->setOutputEncoding('gbk');//设置输出格式
		$PHPExcel = $PHPReader->load($filename);
		$currentSheet = $PHPExcel->getSheet();  //读取excel文件中的第一个工作表
		$allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
		$currentDate=$currentSheet->toArray();
		return $currentDate;
	}
}