<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class HotelVipCardController extends CoinController{
    //查询账户信息接口(通过会员卡)
    public function getAccount(){
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
            ->field('cu.namechinese')->find();
        if($custom == false){
            returnMsg(array('status'=>'05','codemsg'=>'此卡没有关联用户'));
        }
        $vipdate=substr($card['vipdate'],'0','4').'年'.substr($card['vipdate'],'4','2').'月';
        //获取卡下所有积分
        $point=$this->cardPointQuery($cardno);
        returnMsg(array('status'=>'1','point'=>floatval($point),
            'exdate'=>$vipdate,'name'=>$custom['namechinese'],'time'=>time()));
    }
    //查询账户所有券接口(通过会员卡)
    public function getAllQuan(){
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
            ->field('cu.namechinese')->find();
        if($custom == false){
            returnMsg(array('status'=>'06','codemsg'=>'此卡没有关联用户'));
        }
        //获取卡下各种券总量
        $result=$this->getAllQuans($cardno);
        //已分享券总数及列表接口
        $shareresult=$this->alreadyShare($cardno);
        if($result||$shareresult){
            returnMsg(array('status'=>'1','list'=>$result,'share'=>$shareresult));
        }else{
            returnMsg(array('status'=>'07','codemsg'=>'无记录'));
        }
    }
    //获取劵列表
    public function getTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $cate=trim($datami['cate']);
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $ticketList=$this->getTicketByCardno($cardno,'00000126',$cate);
        returnMsg(array('status'=>'1','list'=>$ticketList));
    }
    //获取过期劵列表
    public function getOverDueTicket(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $cate=trim($datami['cate']);
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $ticketList=$this->getOverDueTicketByCardno($cardno,'00000126',$cate);
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
        $cate=trim($datami['cate']);
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
        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
            ->join('quankind q on q.quanid=tw.quanid')
            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','02','13','17','21')),'tw.flag'=>0,'q.cate'=>$cate))
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pnamee,q.quanname')->select();
        $list=array();
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        if($tradelist!=false){
            foreach($tradelist as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['tradetype']=$jytype[$val['tradetype']];
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
                $list[$key]['pname']=$val['pname'];
                $list[$key]['quanname']=$val['quanname'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }
    //分享券
    public function shareQuan(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $quanid=trim($datami['quanid']);
        $quanname=trim($datami['quanname']);
        $sharedate=date('Ymd',time());
        $sharetime=date('H:i:s',time());
        $shareid=trim($datami['shareid']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno.$quanid.$quanname.$shareid);
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $this->model->startTrans();
        $consumeIf = $this->shareQuanByCard($cardno,$quanid,$quanname,$sharedate,$sharetime,$shareid);
        if ($consumeIf == true) {
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '分享成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '09', 'codemsg' => '营销劵分享失败'));
        }
    }
    //更改密码
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
        $pwd=$des->doEncrypt($newpwd);
        $cardMap=array('cardno'=>$cardno);
        $pwdData=array('cardpasswordd'=>$newpwd);
        $sql="update cards set cardpassword='{$newpwd}' where cardno='{$cardno}'";
        if($this->cards->execute($sql)){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'06','codemsg'=>'修改失败'));
        }
    }
    //积分兑换
    public function pointExchange(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $tradeid=trim($datami['tradeid']);
        $cardno=trim($datami['cardno']);
        $quanid=trim($datami['quanid']);
        $quanname=trim($datami['quanname']);
        $price=trim($datami['price']);
        $totalprice=trim($datami['totalprice']);
        $num=trim($datami['num']);
        $placeddate=trim($datami['placeddate']);
        $placedtime=trim($datami['placedtime']);
        $key=trim($datami['key']);
        $time=date('Y-m-d',time());
        $checkKey=md5($this->keycode.$tradeid.$cardno.$quanid.$quanname.$price.$totalprice.$num.$placeddate.$placedtime);
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        //获取账户积分
        $point=$this->cardPointQuery($cardno);
        if($totalprice>$point){
            returnMsg(array('status' => '06', 'codemsg' => '账户积分余额不足'));
        }
        $this->model->startTrans();
        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
            ->field('cu.namechinese,cu.customid')->find();
        if($custom == false){
            returnMsg(array('status'=>'07','codemsg'=>'此卡没有关联用户'));
        }
        $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);
        $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
        $userid=session('userid');
        $quankind=M('quankind')->where(array('quanid'=>$quanid))->field('startdate,enddate,validaty')->find();
        if(!empty($quankind['startdate'])){//活动券
            $qstartdate=$quankind['startdate'];
            $qenddate=$quankind['enddate'];
        }else{//普通券
            $qstartdate=$placeddate;
            $qenddate=date('Ymd',strtotime("{$time} +{$quankind['validaty']} month"));
        }
        $quan_account=M('quan_account')->where(array('startdate'=>$qstartdate,'enddate'=>$qenddate,'customid'=>$custom["customid"],'quanid'=>$quanid))->find();
        $this->model->startTrans();
        $pointSql="insert into point_exchange(tradeid,cardno,quanid,quanname,price,totalprice,num,placeddate,placedtime)";
        $pointSql.="values('{$tradeid}','{$cardno}','{$quanid}','{$quanname}','{$price}','{$totalprice}','{$num}','{$placeddate}','{$placedtime}')";
        $pointIf=$this->model->execute($pointSql);
        if(count($quan_account)>0){
            $quanAccountSql="update quan_account set amount=nvl(amount,0)+".$num." where customid='{$custom["customid"]}' and accountid='{$quan_account["accountid"]}' and quanid='{$quanid}'";
        }else{
            $quanAccountSql="insert into quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$quanid}','{$custom["customid"]}','{$num}','{$qstartdate}','{$quanpurchaseid}','{$quanaccountid}','{$qenddate}')";
        }
        $quanAccountIf=$this->model->execute($quanAccountSql);
        $quanczSql= "insert into quancz values('".$quanid."','".$num."','".date('Ymd')."','".date('H:i:s')."','00000126','".$userid."','".$custom['customid']."','".$quanpurchaseid."')";
        $quanczIf=$this->model->execute($quanczSql);
        if(empty($quankind['startdate'])){
            $quanSql="update quankind set startdate='".$qstartdate."',enddate='".$qenddate."' where quanid='".$quanid."'";
            $quanIf=$this->model->execute($quanSql);
            if ($pointIf==true||$quanAccountIf==true||$quanczIf==true||$quanIf==true) {
                $this->model->commit();
                returnMsg(array('status' => '1', 'codemsg' => '兑换成功'));
            } else {
                $this->model->rollback();
                returnMsg(array('status' => '08', 'codemsg' => '兑换失败'));
            }
        }else{
            if ($pointIf==true||$quanAccountIf==true||$quanczIf==true) {
                $this->model->commit();
                returnMsg(array('status' => '1', 'codemsg' => '兑换成功'));
            } else {
                $this->model->rollback();
                returnMsg(array('status' => '08', 'codemsg' => '兑换失败'));
            }
        }
    }
    //积分明细
    public function pointDetail(){
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
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        $recharge=$consume=array();
        $where['cardno']=$cardno;
        $where['finalaccount']=array('gt',0);
        $result=M('viptrade')->where($where)->order('paydate desc,paytime desc')->field('paydate as placeddate,paytime as placedtime,finalaccount')->select();
        if($result){
            foreach($result as $k=>$v){
                $v['point']=floor($v['finalaccount']/25);
                $v['finalaccount']=floatval($v['finalaccount']);
                $recharge[]=$v;
            }
        }
        $data=M('point_exchange')->where(array('cardno'=>$cardno))->order('placeddate desc,placedtime desc')->field('placeddate,placedtime,num,totalprice')->select();
        if($data){
            foreach($data as $m=>$n){
                $n['totalprice']=floatval($n['totalprice']);
                $consume[]=$n;
            }
        }
        returnMsg(array('status'=>'1','recharge'=>$recharge,'consume'=>$consume));
    }
}
