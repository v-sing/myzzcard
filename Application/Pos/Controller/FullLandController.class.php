<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;

class FullLandController extends CoinController
{
    public function getMenus()
    {
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami,1);
        $panterid = trim($datami['panterid']);
        $termposno = trim($datami['termposno']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$panterid.$termposno);
        if ($checkKey != $key) {
            $this->recoreIp();
            returnMsg(array('status' => '01', 'key' => '无效秘钥，非法传入'));
        }
        if($panterid!='00003696'){
            returnMsg(array('status' => '02', 'codemsg' => '商户信息不正确'));
        }
        $where['panterid']=array('eq',$panterid);
        $field ="id,name,quans,relation";
        $menus=M('menus')->where($where)->field($field)->order('sort asc')->select();
        $arr='';
        if($menus){
            foreach($menus as $k=>$v){
                $quan=$quanrs='';
                $quans=explode(',',$v['quans']);
                foreach($quans as $m=>$n){
                    $quanids=explode(':',$n);
                    $quan['quanid']=$quanids[0];
                    $quan['quanname']=$this->getQuan($quanids[0]);
                    $quan['num']=$quanids[1];
                    $quanrs[]=$quan;
                }
                $v['quan']=$quanrs;
                unset($v['quans']);
                $arr[]=$v;
            }
            returnMsg(array('status' => '1', 'codemsg' => $arr));
        }else{
            returnMsg(array('status' => '03', 'codemsg' => '暂无信息'));
        }
    }

    public function cardRecharge()
    {
        $datami = trim($_POST['datami']);
        $this->recordData($datami, 1);
        $datami = json_decode($datami, 1);
        $cardno = trim($datami['cardno']);
        $quan = trim($datami['quan']);
        $amount = trim($datami['amount']);
        $panterid = trim($datami['panterid']);
        $termposno = trim($datami['termposno']);//终端号
        $paymenttype = trim($datami['paymenttype']);
        $userdefined = trim($datami['userdefined']);//是否自定义套餐 true/false
        $key = trim($datami['key']);
        if($key != md5($this->keycode.$cardno.$quan.$amount.$panterid.$termposno.$paymenttype)){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥'));
        }
        if (empty($cardno)) {
            returnMsg(array('status' => '02', 'codemsg' => '会员卡号缺失'));
        }
        $map = array('cardno' => $cardno);
        $cards = $this->cards->where($map)->field('customid,cardpassword,status,panterid')->find();
        if ($cards == false) {
            returnMsg(array('status' => '03', 'codemsg' => '非法卡号'));
        }
        if ($cards['status'] != 'Y') {
            returnMsg(array('status' => '04', 'codemsg' => '非正常卡号'));
        }
        $b = $this->checkCardValidate($cardno);
        if ($b == false) {
            returnMsg(array('status' => '05', 'codemsg' => '卡已过期'));
        }
        if($cards['panterid']!='00003696'){
            returnMSg(array('status' => '06', 'codemsg' => '该卡不能在花满地充值！'));
        }
        $panter = $this->checkPanter($panterid);
        if ($panter == false) {
            returnMSg(array('status' => '07', 'codemsg' => '商户不存在！'));
        }
        if($panterid!='00003696'){
            returnMsg(array('status' => '08', 'codemsg' => '商户信息不正确'));
        }
        $customid = $this->getCustomid($cardno);
        if ($customid == false || empty($customid)) {
            returnMsg(array('status' => '09', 'codemsg' => '此卡没有关联用户'));
        }
        if($paymenttype==1){
            $paymenttype='01';
        }elseif($paymenttype==2){
            $paymenttype='04';
        }elseif($paymenttype==3){
            $paymenttype='04';
        }else{
            $paymenttype='00';
        }
        $userid=$this->userid;
        $userstr= substr($userid,12,4);
        $purchaseid=$this->getFieldNextNumber("purchaseid");
        $purchaseid=$userstr.$purchaseid;
        $currentDate=date('Ymd');
        $checkDate=date('Ymd');
        $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$amount."',NULL,'".$amount."',0,'".$amount."','".$amount;
        $customplSql.="',1,'','','1','".$cards['panterid']."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        //写入审核单
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
        $auditlogsSql.="'充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        //写入充值单
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$amount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$cards['panterid']}','','00000000')";
        //更新卡片账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$amount." where customid='".$cards['customid']."' and type='00'";
        $this->model->startTrans();
        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);
        $cardplIf=$this->model->execute($cardplSql);
        $balanceIf=$this->model->execute($balanceSql);

        if($userdefined===true){
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true){
                $this->model->commit();
            }else{
                $this->model->rollback();
                returnMsg(array('status' => '13', 'codemsg' => '充值失败'));
            }
            returnMsg(array('status' => '1', 'codemsg' => '充值成功','tradeid'=>$purchaseid));

        }else{
            $account=M('quan_account');
            $quancz=M('quancz');
            $userid=$_SESSION['userid'];
            $counts=0;
            $quan=json_decode($quan,true);
            foreach($quan as $k=>$v){
                if(empty($v['quanid'])){
                    returnMsg(array('status' => '10', 'codemsg' => '营销劵编号缺失'));
                }else{
                    $map1 = array('quanid' => $v['quanid']);
                    $quankind = M('quankind')->where($map1)->find();
                    if ($quankind == false) {
                        returnMsg(array('status' => '11', 'codemsg' => '营销劵不存在'));
                    }
                    if ($quankind['panterid'] != $panterid) {
                        returnMsg(array('status' => '12', 'codemsg' => '营销劵不能再该商户下充值'));
                    }
                    $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);
                    $condition=array('quanid'=>$v['quanid'],'customid'=>$cards['customid']);
                    $accounts=$account->where($condition)->find();
                    if($accounts==false){
                        $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
                        $enddate='80800101';
                        $acountSql="INSERT INTO quan_account(quanid,customid,amount,purchaseid,accountid,enddate) VALUES('{$v['quanid']}','{$cards['customid']}','{$v['num']}','{$quanpurchaseid}','{$quanaccountid}','{$enddate}')";
                    }else{
                        $acountSql="update quan_account set amount=amount+{$v['num']} where customid='{$cards['customid']}' and accountid='{$accounts['accountid']}' and quanid='{$v['quanid']}'";
                    }
                    $quanSql="insert into quancz values('".$v['quanid']."','".$v['num']."','".date('Ymd')."','".date('H:i:s')."','".$cards['panterid']."','".$userid."','".$cards['customid']."','".$quanpurchaseid."',0)";
                    $quancz->startTrans();
                    $this->recordData('监听quan_account券账户表变动sql：'.$acountSql);
                    $this->recordData('监听quancz券充值表变动sql：'.$quanSql);
                    $acountIf= $account->execute($acountSql);
                    $quanczs=$quancz->execute($quanSql);
                    if($acountIf==false || $quanczs==false){
                        $quancz->rollback();
                        continue;
                    }else{
                        $quancz->commit();
                    }
                    $counts++;
                }
            }
            if(count($quan)==$counts){
                if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true){
                    $this->model->commit();
                }else{
                    $this->model->rollback();
                    returnMsg(array('status' => '13', 'codemsg' => '充值失败'));
                }
                returnMsg(array('status' => '1', 'codemsg' => '充值成功','tradeid'=>$purchaseid));
            }else{
                returnMsg(array('status' => '13', 'codemsg' => '充值失败'));
            }
        }

    }

    public function getQuan($quanid){
        $result=M("quankind")->where(array('quanid'=>$quanid))->getField('quanname');
        return $result;
    }
}