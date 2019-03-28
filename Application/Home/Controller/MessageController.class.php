<?php
namespace Home\Controller;
use Think\Controller;
class MessageController extends CommonController {
    function send(){
		
		$shi=$_GET['shi'];
		if(!empty($shi)){
			$condition['cityname']=array('eq',$shi);
			$this->assign('shi',$shi);
		}
		$sex=$_GET['sex'];
		if(!empty($sex)){
			$condition['sex']=array('eq',$sex);
			$this->assign('sex',$sex);
		}
		$sMemid=$_GET['sMemid'];
		if(!empty($sMemid)){
			$condition['customid'][]=array('egt',$sMemid);
			$this->assign('sMemid',$sMemid);
		}
		$eMemid=$_GET['eMemid'];
		if(!empty($eMemid)){
			$condition['customid'][]=array('elt',$eMemid);
			$this->assign('eMemid',$eMemid);
		}
		$condition['linktel']=array('neq','null');
		$m=M("customs");
		/*$custom_list=$m->table('customs')->alias('c')
                     ->join('join __PANTERS__ p on c.panterid=p.panterid')
                     ->join('join __CITY__ ct on ct.cityid=c.cityid')
                     ->where($where)->field('c.*,p.namechinese pname,ct.cityname,p.hysx')
                     //->limit($p->firstRow.','.$p->listRows)
                     ->order('c.customid asc')->select();
		dump($custom_list);*/
		$count=$m->join("left join city on customs.cityid=city.cityid")->where($condition)->count();
		$p=new \Think\Page($count, 15);
		$list=$m->join("left join city on customs.cityid=city.cityid")->where($condition)->limit($p->firstRow.','.$p->listRows)->select();
		$page=$p->show();
		$this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
    }

	function send_message() {	
		$msg=$_POST['msg'];
        $jgid = '413';
        //短信接口用户名 $loginname
        $loginname = 'jyzz1';
        //短信接口密码 $passwd
        $passwd = 'jyzz';
        //发送到的目标手机号码 $telphone，多个号码用半角分号分隔
		$tel=I('post.tel','18911822210');
        //短信内容 $message
        $msg =  urlencode($msg);
        $gateway = "http://116.255.233.131:8180/service.asmx/SendMessage?Id={$jgid}&Name={$loginname}&Psw={$passwd}&Message={$msg}&Phone={$tel}&Timestamp=0";
        $result = simplexml_load_file($gateway);
        return $result;
    }
	
}