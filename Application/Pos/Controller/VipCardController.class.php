<?php
namespace Pos\Controller;

use Think\Controller;
use Think\Model;

class VipCardController extends CoinController
{
    //查询账户信息接口(通过会员卡)
    public function getAccount()
    {
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami, 1);
        $cardno = trim($datami['cardno']);
        $panterid = trim($datami['panterid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode . $cardno . $panterid);
        if ($checkKey != $key) {
            $this->recoreIp();
            returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
        }
        if($panterid!='00000126'){
            returnMsg(array('status' => '09', 'codemsg' => '贵宾卡只能在艾美酒店消费'));
        }
        $map = array('cardno' => $cardno);
        $card = $this->cards->where($map)->find();
        if ($card == false) {
            $this->recoreIp();
            returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
        }
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        if ($card['status'] != 'Y') {
            if ($card['status'] == 'A') {
                returnMsg(array('status' => '03', 'codemsg' => '待激活卡，请先激活'));
            } else {
                returnMsg(array('status' => '04', 'codemsg' => '非正常卡号'));
            }
        }
        $customid = $this->getCustomid($cardno);
        if ($customid == false || empty($customid)) {
            returnMsg(array('status' => '06', 'codemsg' => '此卡没有关联用户'));
        }
        //------获取账户下券总量及每种券数量等信息
        $quanresult = $this->getQuanByCardno($cardno, $panterid);
        if($quanresult){
            returnMsg(array('status' => '07', 'codemsg' => '查询成功','data'=>$quanresult));
        }else{
            returnMsg(array('status' => '08', 'codemsg' => '此卡名下暂无券'));
        }
        returnMsg($quanresult);
    }

    //券消费通过会员卡号
    public function ticketConsume()
    {
        $datami = trim($_POST['datami']);
        $this->recordData($datami, 1);
        $datami = json_decode($datami, 1);
        $cardno = trim($datami['cardno']);
        $quanid = trim($datami['quanid']);
        $amount = trim($datami['amount']);
        $panterid = trim($datami['panterid']);
        $termposno = trim($datami['termposno']);//终端号
        $accountid = trim($datami['accountid']);
        $key = trim($datami['key']);
        $shareid = trim($datami['shareid']);
        if (empty($cardno)) {
            returnMsg(array('status' => '01', 'codemsg' => '会员卡号缺失'));
        }
        $b = $this->checkCardValidate($cardno);
        if ($b == false) {
            returnMsg(array('status' => '02', 'codemsg' => '卡已过期'));
        }
        if (empty($quanid)) {
            returnMsg(array('status' => '03', 'codemsg' => '营销劵编号缺失'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
            returnMsg(array('status' => '04', 'codemsg' => '消费金额格式错误'));
        }
        if($key != md5($this->keycode.$cardno)){
            returnMsg(array('status'=>'05','codemsg'=>'无效秘钥'));
        }
        $panter = $this->checkPanter($panterid);
        if ($panter == false) {
            returnMSg(array('status' => '06', 'codemsg' => '商户不存在！'));
        }
        if($panterid!='00000126'){
            returnMsg(array('status' => '07', 'codemsg' => '贵宾卡只能在艾美酒店消费'));
        }
        $map = array('cardno' => $cardno);
        $card = $this->cards->where($map)->field('customid,cardpassword,status,panterid,vipdate')->find();
        if ($card == false) {
            returnMsg(array('status' => '08', 'codemsg' => '非法卡号'));
        }
        if ($card['status'] != 'Y') {
            returnMsg(array('status' => '09', 'codemsg' => '非正常卡号'));
        }
        $map1 = array('quanid' => $quanid);
        $quankind = M('quankind')->where($map1)->find();
        if ($quankind == false) {
            returnMsg(array('status' => '010', 'codemsg' => '营销劵不存在'));
        }
        if ($quankind['panterid'] != $panterid && $panter['parent'] != $quankind['panterid']) {
            returnMsg(array('status' => '010', 'codemsg' => '营销劵不能再该商户下消费'));
        }
        $customid = $this->getCustomid($cardno);
        if ($customid == false || empty($customid)) {
            returnMsg(array('status' => '17', 'codemsg' => '此卡没有关联用户'));
        }
        $quanshare=M('quanshare')->alias('qu')->join('quan_account qa on qa.quanid=qu.quanid')->where(array('qu.shareid'=>$shareid,'qa.customid'=>$customid))->find();
        if($quanshare['status']==2){
            returnMsg(array('status' => '011', 'codemsg' => '此营销券已消费,不能再次消费'));
        }
        if($quanshare['enddate']<date('Ymd')){
            returnMsg(array('status' => '012', 'codemsg' => '分享营销劵已过期'));
        }
        if ($quankind['atype'] == 1) {
            if ($quankind['enddate'] < date('Ymd', time())) {
                returnMsg(array('status' => '013', 'codemsg' => '营销劵已过期'));
            }
            $where = array('a.type' => '02', 'a.quanid' => $quanid, 'c.cardno' => $cardno, 'a.amount' => array('gt', 0));
            $quanAmount = $this->cards->alias('c')->join('account a on a.customid=c.customid')->where($where)->sum('a.amount');
            if ($quanAmount < $amount) {
                returnMsg(array('status' => '014', 'codemsg' => '营销劵余额不足'));
            }
            $this->model->startTrans();
            $consumeIf = $this->ticketExeByCardno($cardno, $quanid, $amount, $panterid, $termposno);
            if ($consumeIf == true) {
                $this->model->commit();
                returnMsg(array('status' => '1', 'codemsg' => '消费成功', 'tradidlist' => array($consumeIf), 'time' => time()));
            } else {
                $this->model->rollback();
                returnMsg(array('status' => '015', 'codemsg' => '营销劵消费失败'));
            }
        } elseif ($quankind['atype'] == 2) {
            if (empty($accountid)) {
                returnMsg(array('status' => '013', 'codemsg' => '劵消费账户缺失'));
            }
            $where = array('qa.quanid' => $quanid, 'c.cardno' => $cardno, 'qa.accountid' => $accountid);
            $quanList = $this->cards->alias('c')->join('quan_account qa on qa.customid=c.customid')
                ->where($where)->field('qa.*')->find();
            if ($quanList['enddate'] < date('Ymd')) {
                returnMsg(array('status' => '014', 'codemsg' => '营销劵已过期'));
            }
            if(empty($shareid)){
                if ($quanList['amount'] < $amount) {
                    returnMsg(array('status' => '015', 'codemsg' => '营销劵余额不足'));
                }
            }
            $this->model->startTrans();
            $consumeIf = $this->newTicketExeByCardno($cardno, $quanList, $amount, $panterid, $termposno,$shareid);
            if ($consumeIf == true) {
                $this->model->commit();
                returnMsg(array('status' => '1', 'codemsg' => '消费成功', 'tradidlist' => array($consumeIf), 'time' => time()));
            } else {
                $this->model->rollback();
                returnMsg(array('status' => '016', 'codemsg' => '营销劵消费失败'));
            }
        }
    }
    
    //艾美贵宾卡支付
    public function VipPay(){
        $myfile = fopen("testfile.txt", "w");
        $datetime=date('Y-m-d H:i:s');
        fwrite($myfile, $datetime);
        fclose($myfile);
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami, 1);
        $termposno = trim($datami['termposno']);
        $cardno = trim($datami['cardno']);
        $panterid = trim($datami['panterid']);
        $orderid = trim($datami['orderid']);
        $quanid = trim($datami['quanid']);
        $totalamount = trim($datami['totalamount']);
        $amount = trim($datami['amount']);
        $account = trim($datami['account']);
        $acccount = trim($datami['acccount']);
        $finalaccount = trim($datami['finalaccount']);
        $discount = trim($datami['discount']);
        $paytype = trim($datami['paytype']);
        $paydate = trim($datami['paydate']);
        $paytime = trim($datami['paytime']);
        $tradeid = trim($datami['tradeid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode .$termposno. $cardno .$panterid. $orderid.$quanid.$totalamount.$amount.$account.$acccount.$finalaccount.$discount.$paytype.$paydate.$paytime.$tradeid);
        if ($checkKey != $key) {
            $this->recoreIp();
            returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
        }
        if($panterid!='00000126'){
            returnMsg(array('status' => '07', 'codemsg' => '贵宾卡只能在艾美酒店消费'));
        }
        $map = array('cardno' => $cardno);
        $card = $this->cards->where($map)->find();
        if ($card == false) {
            $this->recoreIp();
            returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
        }
        if(!strstr($cardno,"68823710888")){
            returnMsg(array('status' => '05', 'codemsg' => '此卡非贵宾卡'));
        }
        if ($card['status'] != 'Y') {
            if ($card['status'] == 'A') {
                returnMsg(array('status' => '03', 'codemsg' => '待激活卡，请先激活'));
            } else {
                returnMsg(array('status' => '04', 'codemsg' => '非正常卡号'));
            }
        }
        $customid = $this->getCustomid($cardno);
        if ($customid == false || empty($customid)) {
            returnMsg(array('status' => '06', 'codemsg' => '此卡没有关联用户'));
        }
        $tradeSql="insert into viptrade(termposno,cardno,panterid,orderid,quanid,totalamount,";
        $tradeSql.="amount,account,acccount,finalaccount,discount,paytype,paydate,paytime,key,tradeid)";
        $tradeSql.="values('{$termposno}','{$cardno}','{$panterid}','{$orderid}','{$quanid}','{$totalamount}',";
        $tradeSql.="'{$amount}','{$account}','{$acccount}','{$finalaccount}','{$discount}','{$paytype}','{$paydate}','{$paytime}','{$key}','{$tradeid}')";
        $vtIf=$this->model->execute($tradeSql);
        $point=floor($finalaccount/25);
        $this->model->startTrans();
        $PointSql="update account set amount=amount+{$point} where customid='{$customid}' and type='04'";
        $pointIf=$this->model->execute($PointSql);
        if ($vtIf==true||$pointIf==true) {
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '支付成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '09', 'codemsg' => '支付失败'));
        }
    }
}