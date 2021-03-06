<?php
namespace Home\Controller;
use Think\Controller;
use Think\Cache\Driver\Redis;
use Think\Common;
class PublicController extends Controller{
	/**
	 * 后台登录方法
	 */
    public function login(){

        //$sessionid=session_id();
        //$redis = new Redis();
        //$redis->connect('192.168.10.35',6379);
        //$redis->set('sessionid',$sessionid);
        //echo $redis->get("test");
        //$this->checkIp();
    	$this->display();
    }

	/**
	 * 后台登录方法(次方法只针对开封九道湾项目)
	 */
	public function login2(){
		$this->display();
	}

    /**
     * 生成验证码方法
     */
    public function verify(){
		ob_clean();
        $config=array('fontSize'=>14,'imageW'=>110,'imageH'=>40,'useNoise'=>false,'fontttf'=>'5.ttf','length'=>4);
        $Verify=new \Think\Verify($config);
        $Verify->entry();
    }
    
    /**
     * 后台登录验证方法
     */
    public function checkLogin(){
        $username=trim($_POST['username']);
        $pwd=trim($_POST['password']);
        $code=trim($_POST['code']);
        $verify = new \Think\Verify();
        if(empty($code)){
            $this->error('请输入验证码');
        }else{
            if($verify->check($code)==false){
                $this->error('验证码错误');
            }
        }
		$m=M('users');
        $safety =M('safety_monitoring');
        $user=$m->getByLoginname($username);
        $userid=$user['userid'];
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        if(empty($user)){
            $this->think_send_mail('349409263@qq.com',$date,$username,'管理员不存在','');
            $safety->execute("insert into safety_monitoring values('{$username}','{$date}','{$ptime}','后台登录','{$username}管理员不存在','03')");
            $this->error('管理员不存在');
        } else{
            if($user['active'] =='N'){
                $this->error('账号已禁用，请联系管理员');
            }
			if($user['wrpass']>=3){
                $this->think_send_mail('349409263@qq.com',$date,$username,'账号已被锁定，请联系管理员','');
                $safety->execute("insert into safety_monitoring values('{$userid}','{$date}','{$ptime}','后台登录','{$username}密码输入错误3次，账号已被锁定','03')");
                $this->error('账号已被锁定，请联系管理员');
			}
            if(md5($pwd)!=$user['password1']){
				$m->execute("update users set wrpass=wrpass+1 where loginname='{$username}'");
                $this->think_send_mail('349409263@qq.com',$date,$username,'密码错误');
                $safety->execute("insert into safety_monitoring values('{$userid}','{$date}','{$ptime}','后台登录','{$username}密码错误','03')");
				$this->error('密码错误');
            }else{
				if($user['password1']== ('888888')){
					session('userid_first',$user['userid']);
					session('username_first',$user['username']);
					$this->redirect("Public/firstPassword");
				}
                session('lastTime',time());
                session('userid',$user['userid']);
                session('username',$user['username']);
				session('panterid',$user['panterid']);
                $session_id = session_id();
				$m->execute("update users set wrpass='0',login_flag='1',session_id='{$session_id}' where loginname='{$username}'");
				if($user['username']=='admin'){
					session(C('ADMIN_AUTH_KEY'),true);
				}
				\Org\Util\Rbac::saveAccessList();
				if(in_array($username,C('KfAccount'))){
                    $d = date('Ymd');
                    if ($user['enddate'] <= $d) {
                        $this->think_send_mail('349409263@qq.com',$date,$username,'密码已过期，请尽快修改密码','');
                        $safety->execute("insert into safety_monitoring values('{$userid}','{$date}','{$ptime}','后台登录','{$username}密码已过期，请尽快修改密码','03')");
                        $this->error('密码已过期，请尽快修改密码', U('System/upPassword'));
                        exit;
                    } else {
                        $this->redirect('KfCarnteen/goods');
                    }
				}else{
                    $d = date('Ymd');
                    if ($user['enddate'] <= $d) {
                        $this->think_send_mail('349409263@qq.com',$date,$username,'密码已过期，请尽快修改密码','');
                        $safety->execute("insert into safety_monitoring values('{$userid}','{$date}','{$ptime}','后台登录','{$username}密码已过期，请尽快修改密码','03')");
                        $this->error('密码已过期，请尽快修改密码', U('System/upPassword'));
                        exit;
                    } else {
                        $this->redirect('Index/index');
                    }
				}
            }
        }
    }
    
    /**
     * 后台退出登录方法
     */
    public function logout(){
        if(session('userid')!==false){
            $userid = session('userid');
            $m=M('users');
            $m->execute("update users set login_flag='0' where userid='{$userid}'");
            session('[destroy]');
            $this->redirect('/Public/login');
        }
    }

    public function checkIp(){
        $ip = get_client_ip();
        $allow_ips = C('ALLOW_IPS');
        $deny_ips = C('DENY_IPS');
        if(!in_array($ip, $allow_ips)){
            if (in_array($ip, $deny_ips)) {
                exit('Deny Access!');
            } else {
                exit('Error Website!');
            }
        }
    }
    
    /**
     * 首次登录修改密码
     */
    function firstPassword(){
        $userid=$_SESSION['userid_first'];
        if(empty($userid)){
            $this->error('页面错误');
        }
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
                $this->error("密码长度不低于6位");
            }
            if($new_password!=$re_password){
                $this->error("两次密码不一致");
            }
            $m=M();
            $list=$m->query("select * from users where userid='{$userid}'");
            if(md5($old_password)!=$list[0]['password1']){

                $this->error("原密码错误");
            }
            else{
                $n=md5($new_password);
//                $d=date('Ymd');
                $da=date('Ymd',strtotime('+1 month'));
                $r=$m->execute("update users set password1='{$n}',enddate='{$da}' where userid='{$userid}'");
                if($r){
                    unset($_SESSION['userid_first']);
                    unset($_SESSION['username_first']);
                    $this->assign("jumpUrl",__APP__."/Public/login");
                    $this->success("更改成功，请登录");
                }
                else{
                    $this->success("操作失败");
                }
            }
        }else{
            $this->display();
        }
    }
	
	protected function writelog(){
		$url = 'http://192.168.10.42';
		$a=$_SERVER['REMOTE_ADDR'];
		$month=date('Ym',time());
		$filename=date('Ymd',time()).'.log';
		$time=date('Y-m-d H:i:s');
		$string='ip:'.$a."\r\n登录时间：".$time."\r\n状态：111\r\n\r\n";
		$path= 'http://192.168.10.42/loginlogs/';
		if(!file_exists($path)){
			mkdir($path,0777,true);
		}
		file_put_contents($path.$filename,$string,FILE_APPEND);
	}
	
}