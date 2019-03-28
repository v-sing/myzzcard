<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class HotelWeixinController extends CoinController{

    //查询账户信息接口(通过会员卡)
    public function getAccount(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
//        $datami = json_decode('{"cardno":"6882371900000000002","key":"acef586f9b7a34122ba1eff9cfff0bd9"}',1);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);

        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
            ->field('cu.namechinese')->find();
        if($custom == false){
            returnMsg(array('status'=>'05','codemsg'=>'此卡没有关联用户'));
        }
        $balance=$this->cardAccQuery($cardno);
        //------获取账户下所有卡数量
        //$tickketList=$this->getTicketByCustomid($customid);
        //--------end
        returnMsg(array('status'=>'1','balance'=>floatval($balance),
            'exdate'=>$card['exdate'],'name'=>$custom['namechinese'],'time'=>time()));
    }

    //获取劵列表
    public function getTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);

        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6882371888800002858';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $ticketList=$this->getTicketByCardno($cardno,'00000126');
        returnMsg(array('status'=>'1','list'=>$ticketList));
    }

    //获取过期劵列表
    public function getOverDueTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $ticketList=$this->getOverDueTicketByCardno($cardno,'00000126');
        returnMsg(array('status'=>'1','list'=>$ticketList));
    }

    //检验密码
    public function checkPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        //$datami = json_decode('{"cardno":"6882371900000000002","key":"7c438ee62176d10fe9c8cf22c27f330e","pwd":"888888"}',1);
        $cardno=trim($datami['cardno']);
        $pwd=trim($datami['pwd']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno.$pwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=$des->doEncrypt($pwd);
        if($pwd!=$card['cardpassword']){
            returnMsg(array('status'=>'04','codemsg'=>'密码错误'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'检验成功'));
        }
    }

    //消费列表
    public function getTreadelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6882371888800002858';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','13','17','21')),'tw.flag'=>0))
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
        $list=array();
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        if($tradelist!=false){
            foreach($tradelist as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['tradetype']=$jytype[$val['tradetype']];
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
                $list[$key]['pname']=$val['pname'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }

    //获取劵消费列表
    public function getQuanConsumelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6882371888800002858';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
            ->join('quankind q on q.quanid=tw.quanid')
            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in','02'),'tw.flag'=>0))
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,p.namechinese pname,q.quanname')->select();
        $list=array();
        if($tradelist!=false){
            foreach($tradelist as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
                $list[$key]['pname']=$val['pname'];
                $list[$key]['quanname']=$val['quannames'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }

    public function editPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        //$data=json_decode('{"cardno":"6882371900000000002","oldpwd":"YW1ob3RlbDg4ODg4OHpoaXp1bg==","newpwd":"YW1ob3RlbDEyMzQ1NnpoaXp1bg==","key":"0839ab6c739c391fe5fdc8f9e33663c2"}',1);
        $data = json_decode($datami,1);
        $cardno=trim($data['cardno']);
        $oldpwd=trim($data['oldpwd']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$oldpwd.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        $oldpwd=$this->decodePwd($oldpwd,'amhotel','zhizun');
        $newpwd=$this->decodePwd($newpwd,'amhotel','zhizun');
        if($oldpwd==false||$newpwd==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法密码传入'));
        }
        if(!preg_match("/\d{6}$/",$newpwd)){
            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $oldpwd=$des->doEncrypt($oldpwd);
        if($oldpwd!=$card['cardpassword']){
            returnMsg(array('status'=>'05','codemsg'=>'旧密码校验错误'));
        }
        $newpwd=$des->doEncrypt($newpwd);
        $cardMap=array('cardno'=>$cardno);
        $pwdData=array('cardpasswordd'=>$newpwd);
        $sql="update cards set cardpassword='{$newpwd}' where cardno='{$cardno}'";
        if($this->cards->execute($sql)){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'06','codemsg'=>'修改失败'));
        }
    }

    public function getRechargeList(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6882371888800002858';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
        }
        $where=array('cpl.cardno'=>$cardno,'cpl.amount'=>array('gt',0));
        $rechargelist=M('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->where($where)->field('cpl.amount,cpl.cardno,cpl.placeddate,cpl.placedtime')->select();
        $list=array();
        if($rechargelist!=false){
            foreach($rechargelist as $key=>$val){
                $list[$key]['amount']=trim(floatval($val['amount']));
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }
}
