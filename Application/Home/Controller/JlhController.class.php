<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class JlhController extends Controller {
    public function search(){
        $data=trim($_REQUEST['data']);
        if(!empty($data)){
            $where['customlevel']='建业线上会员';
            //$where['countrycode']=array('in','e+会员,君邻会会员,football');
            $where['c.panterid']='00000447';
            $where['_string']="cu.personid='{$data}' OR cu.linktel='{$data}' OR cu.namechinese='{$data}'";
            $field='cu.namechinese,cu.personid,cu.linktel,cu.sex,cu.unitname,c.cardno';
            $customInfo=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on cc.cid=c.customid')
                ->where($where)->field($field)->find();
            if(!empty($customInfo['linktel'])){
                $customInfo['linktel']=$this->codeMobile($customInfo['linktel']);
            }
            if(!empty($customInfo['personid'])){
                $customInfo['personid']=$this->codePersonid($customInfo['personid']);
            }
            if(!empty($customInfo['namechinese'])){
                $customInfo['namechinese']=$this->codeName($customInfo['namechinese']);
            }
            if(!empty($customInfo['cardno'])){
                $customInfo['cardno']=$this->codeCardno($customInfo['cardno']);
            }
            $this->assign('list',$customInfo);
            $this->assign('data',$data);
        }
        $this->display();
    }
    public function codeMobile($mobile){
        $str1=substr($mobile,0,4);
        $str2=substr($mobile,-4,4);
        return $str1.'****'.$str2;
    }
    public function codePersonid($personid){
        $str1=substr($personid,0,6);
        $str2=substr($personid,-4,4);
        return $str1.'********'.$str2;
    }
    public function codeName($name){
        $str=mb_substr($name,1,mb_strlen($name,'utf-8')-1,'utf-8');
        return '*'.$str;
    }
    public function codeCardno($cardno){
        $str1=substr($cardno,0,6);
        $str2=substr($cardno,-4,4);
        return $str1.'********'.$str2;
    }
    public function changePwd(){
        header('content-type:text/html;charset=utf-8');
        if(IS_POST){
            $cardno=trim(I('cardno',''));
            $oldpwd=trim(I('oldpwd',''));
            $newpwd=trim(I('newpwd',''));
            $renewpwd=trim(I('renewpwd',''));
            $verify=trim(I('verify',''));
            $cards=M('cards');
            if(empty($cardno)){
                exit('<script type="text/javascript">alert("卡号必填！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            $map=array('cardno'=>$cardno);
            $card=$cards->where($map)->field('status,panterid,cardpassword')->find();
            if($card==false){
                exit('<script type="text/javascript">alert("查无此卡号！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if($card['status']!='Y'){
                exit('<script type="text/javascript">alert("非正常会员卡，不能修改密码！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if($card['panterid']!='00000447'){
                exit('<script type="text/javascript">alert("非君邻会卡号，不能修改密码！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if(empty($verify)){
                exit('<script type="text/javascript">alert("请输入验证码！");window.location="'.U('Jlh/changePwd').'";</script>');
            }else{
                $codeInfo=session('codeInfo');
                $currenttTime=time();
                $deadTime=$codeInfo['deadtime'];
                $code=$codeInfo['code'];
                if($code!=$verify){
                    exit('<script type="text/javascript">alert("验证码错误！");window.location="'.U('Jlh/changePwd').'";</script>');
                }
                if(($deadTime-$currenttTime)<0){
                    exit('<script type="text/javascript">alert("验证码过期！");window.location="'.U('Jlh/changePwd').'";</script>');
                }
            }
            if(empty($oldpwd)){
                exit('<script type="text/javascript">alert("原密码必填！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if(empty($newpwd)){
                exit('<script type="text/javascript">alert("新密码必填！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if(empty($renewpwd)){
                exit('<script type="text/javascript">alert("重复密码必填！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if(!preg_match('/^\d{6}$/',$newpwd)){
                exit('<script type="text/javascript">alert("新密码格式错误！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            if($renewpwd!=$newpwd){
                exit('<script type="text/javascript">alert("两次新密码输入不一致！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
            $oldpwd=$des->doEncrypt($oldpwd);
            if($oldpwd!=$card['cardpassword']){
                exit('<script type="text/javascript">alert("原密码错误！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
            $newpwd=$des->doEncrypt($newpwd);
            $cards->startTrans();
            $data=array('cardpassword'=>$newpwd);
            if($cards->where($map)->save($data)){
                $cards->commit();
                exit('<script type="text/javascript">alert("密码修改成功！");window.location="'.U('Jlh/changePwd').'";</script>');
            }else{
                $cards->rollback();
                exit('<script type="text/javascript">alert("密码修改失败！");window.location="'.U('Jlh/changePwd').'";</script>');
            }
        }else{
            $this->display();
        }
    }
    function tpl_send($tpl_id,$mobile,$tpl_value){
        $data = array (
            "apikey" =>'b9ede309143b6931de5d41abbd947abc',
            "tpl_id" =>$tpl_id,
            "mobile" =>$mobile,
            "tpl_value"=>$tpl_value
        );
        $ch = curl_init("https://sms.yunpian.com/v1/sms/tpl_send.json");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res=curl_exec($ch);
        return $res;
    }
    function sendVerify(){
        $mobile=trim($_REQUEST['mobile']);
        $cardno=trim($_REQUEST['cardno']);
        if(empty($mobile)){
            returnMsg(array('status'=>'01','msg'=>'手机号不能为空'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','msg'=>'会员卡号不能为空'));
        }
        $map=array('c.cardno'=>$cardno);
        $customInfo=M('cards')->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where($map)->field('cu.linktel,c.panterid')->find();
        if($customInfo['linktel']!=$mobile){
            returnMsg(array('status'=>'03','msg'=>'填写手机号与卡关联手机号不一致'));
        }
        if($customInfo['panterid']!='00000447'){
            returnMsg(array('status'=>'04','msg'=>'非君邻会卡号，不能修改密码'));
        }
        $code=mt_rand(1000,9999);
        $codeInfo=array('code'=>$code,'deadtime'=>time()+10*60);
        session('codeInfo',$codeInfo);
        //returnMsg(array('status'=>'1','msg'=>$mobile.'--'.$msgString));
        $tpl_value="#code#=".urlencode("{$code}");
        $result = $this->tpl_send("1400211",$mobile,$tpl_value);
        $result=json_decode($result,1);
        if($result['code']==0){
            returnMsg(array('status'=>'1','msg'=>'验证码发送成功！'));
        }else{
            returnMsg(array('status'=>'05','msg'=>'验证码发送失败，请重试'));
        }
    }
    public function searchHotel(){
        $data=trim($_REQUEST['data']);
        if(!empty($data)){
            $where['c.panterid']= '00000447';
            $where['_string']="cu.personid='{$data}' OR cu.linktel='{$data}' OR cu.namechinese='{$data}'";
            $field='cu.namechinese,cu.personid,cu.linktel,cu.sex,cu.unitname,c.cardno';
            $customInfo=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->field($field)->find();
            if(!empty($customInfo['linktel'])){
                $customInfo['linktel']=$this->codeMobile($customInfo['linktel']);
            }
            if(!empty($customInfo['personid'])){
                $customInfo['personid']=$this->codePersonid($customInfo['personid']);
            }
            if(!empty($customInfo['namechinese'])){
                $customInfo['namechinese']=$this->codeName($customInfo['namechinese']);
            }
            if(!empty($customInfo['cardno'])){
                $customInfo['cardno']=$this->codeCardno($customInfo['cardno']);
            }
            $this->assign('list',$customInfo);
            $this->assign('data',$data);
        }
        $this->display();
    }
}
