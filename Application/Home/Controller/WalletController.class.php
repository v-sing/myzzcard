<?php
namespace Home\Controller;
use Think\Controller;
use Org\Util\YjDes;
use Think\Model;
class WalletController extends CoinController{

    public function test1()
    {
        $key = md5($this->keycode . '3770.01' . encode('00002476') . 'e+_0001-0921faa417d44c1b8f94dc97985104d5步静远');
        $sendData = array('storeid' => '377', 'amount' => '0.01', 'customid' => encode('00002476'), 'uniqid' => 'e+_0001-0921faa417d44c1b8f94dc97985104d5',
            'name' => '步静远','key'=>$key);
        $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
        $datami = json_encode(array('datami' => $datami));
        //echo $datami;exit;
        $url = 'http://120.27.7.216:8088/admin/consume';
        $res = crul_post($url, $datami);
        print_r($res);
    }

    public function test()
    {
        $amount = '10.00';
        $uniqid = md5(uniqid());
        $accessToken = 'efad2ac6e3f7429693812f27ec7cdf54';
//        $sendData1=array('type'=>'08','amount'=>$amount,'state'=>'01','accounttype'=>'1',
//            'ordercode'=>$uniqid,'remark'=>'','key'=>md5($this->keycode.'08'.$amount.'011'.$uniqid.''));


        $sendData1 = array('accounttype' => '01', 'type' => '', 'pageNo' => '1', 'pageSize' => '10',
            'key' => md5($this->keycode . '01110'));
        $datami = $this->DESedeCoder->encrypt(json_encode($sendData1));
        $datami = array('datami' => $datami);

        $url = C('walletIp').'/jyo2o_web/app/zzcard/getrecordToZz.json?accessToken=' . $accessToken;
        $res = crul_post($url, $datami);
    }

    //获取用户账户信息
    public function getAccount()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        //$customid=trim('MDAyODAwNDQO0O0O ');
        $key = trim($data['key']);
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        //账户信息
        $accountInfo = $this->accountInfo($customid);

        $map = array('customid' => $customid);
        $walletInfo = M('wallet')->where($map)->find();

        $zzk_account = M('zzk_account')->where(array('customid' => $customid, 'type' => '05'))->field('balance,freeze_balance,cash_balance')->find();
        $freeze_balance = 0;
        $cash_balance = 0;
        $balance = 0;
        if (!empty($zzk_account)) {
            $freeze_balance = floatval($zzk_account['freeze_balance']);
            $cash_balance = floatval($zzk_account['cash_balance']);
            $balance = floatval($zzk_account['balance']);
        }

        $accountAmount = floatval($accountInfo['balance']);
        $walletAmount = floatval($walletInfo['amount']);
        $amount = $accountAmount + $walletAmount + $balance + $freeze_balance + $cash_balance;
        returnMsg(
            array(
                'status' => 1, 'accountAmount' => $accountAmount,
                'walletAmount' => $walletAmount,
                'freeze_balance' => $freeze_balance,
                'cash_balance' => $cash_balance,
                'amount' => $amount,
                'consumable' => $amount - $freeze_balance
            )
        );
    }

    //获取用户基本信息
    public function getCustomInfo()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        //$customid=trim('MDAwMDAzNTYO0O0O ');
        $key = trim($data['key']);
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        //账户信息
        $map = array('customid' => $customid);
        $walletInfo = $this->customs->where($map)->field('namechinese name,personid,residaddress address')->find();
        $walletInfo['status'] = 1;
        returnMsg($walletInfo);
    }

    //完善用户基本信息
    public function completeCustom()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        $name = trim($data['name']);
        $personid = trim($data['personid']);
        $address = trim($data['address']);
        $key = trim($data['key']);

        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $name . $personid . $address);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        if (empty($name) && empty($peronid) && empty($address)) {
            returnMsg(array('status' => '04', 'codemsg' => '传入信息不完整'));
        }
        if (!empty($name)) {
            $data1['namechinese'] = $name;
        }
        if (!empty($personid)) {
            $data1['personid'] = $personid;
        }
        if (!empty($address)) {
            $data1['residaddress'] = $address;
        }
        $map = array('customid' => $customid);
        if (M('customs')->where($map)->save($data1)) {
            returnMsg(array('status' => '1', 'codemsg' => '信息完善成功'));
        } else {
            returnMsg(array('status' => '05', 'codemsg' => '信息完善失败'));
        }
    }

    //完善用户银行卡信息
    public function completeBankInfo()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        $bankname = trim($data['bankname']);
        $bankno = trim($data['bankno']);
        $bankbranch = trim($data['bankbranch']);
        $key = trim($data['key']);
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $bankname . $bankno . $bankbranch);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        if (empty($bankname) || empty($bankno) || empty($bankbranch)) {
            returnMsg(array('status' => '04', 'codemsg' => '银行资料信息不完整'));
        }
        $map = array('customid' => $customid);
        $list = M('custombank')->where($map)->find();
        if ($list != false) {
            returnMsg(array('status' => '05', 'codemsg' => '银行信息已录入'));
        }
        $sql = "INSERT INTO CUSTOMBANK VALUES('{$customid}','{$bankname}','{$bankno}','{$bankbranch}')";
        if ($this->model->execute($sql)) {
            returnMsg(array('status' => '1', 'codemsg' => '银行信息完善成功'));
        } else {
            returnMsg(array('status' => '05', 'codemsg' => '银行信息完善失败'));
        }
    }

    //活动结束入账
    public function income()
    {
//        $arr=array(
//            //array("customid"=>'MDAyNzcxOTEO0O0O',"storeid"=>"33009","activeid"=>"2481","amount"=>"35"),
//            //array("customid"=>'MDAyODA2NDgO0O0O',"storeid"=>"33008","activeid"=>"2517","amount"=>"1120"),
//        );
//        foreach($arr as $key=>$val){
//            $customid=$val['customid'];
//            $storeId=$val['storeid'];
//            $activeId=$val['activeid'];
//            $amount=$val['amount'];
//            $type='01';
//            $customid=$val['customid'];
//            $storeId =$val['storeid'];
//            $endTime='';
//            $amount=$val['amount'];
//            $key=md5($this->keycode . $customid . $storeId . $activeId . $amount . $endTime.$type);
//            $array=array('customid'=>$customid,'storeid'=>$storeId,'activeid'=>$activeId,'amount'=>$amount,'endtime'=>$endTime,'type'=>$type,'key'=>$key);
//            $brr=array('datami'=>$this->DESedeCoder->encrypt(json_encode($array)));
//            $senData = json_encode($brr);
//            //echo $senData.'<br/>';
//            $customid = decode($customid);
//            $url = "http://www.9617777.com/admin/getcash";
//            $res = crul_post($url, $senData);
//            $res=json_decode($res,1);
//            //var_dump($res);
//            if ($res['status'] == 1) {
//                $sql2 = "UPDATE INCOME_BOOKS SET ISSYNC=1 WHERE CUSTOMID='{$customid}' AND STOREID='{$storeId}' AND ACTIVEID='{$activeId}'";
//            } else {
//                $sql2 = "UPDATE INCOME_BOOKS SET ISSYNC=0 WHERE CUSTOMID='{$customid}' AND STOREID='{$storeId}' AND ACTIVEID='{$activeId}'";
//            }
//            echo $sql2.'<br/>';
//            $incomeIf2 = $this->model->execute($sql2);
//        }
//        exit;
        $data = getPostJson();
        $datami = trim($data['datami']);
        $datami = $this->DESedeCoder->decrypt($datami);
        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
        $datami = json_decode($datami, 1);
        $customid = trim($datami['customid']);
        $storeId = trim($datami['storeid']);
        $activeId = trim($datami['activeid']);
        $amount = trim($datami['amount']);
        $endTime = trim($datami['endtime']);
        $type=trim($datami['type']);
        $accessToken=trim($datami['accessToken']);
        $key = trim($datami['key']);
        if (empty($customid)) {
            returnMsg(array('status' => '02', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $storeId . $activeId . $amount . $endTime.$type.$accessToken);
        if ($checkKey != $key) {
            returnMsg(array('status' => '03', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '04', 'codemsg' => '用户不存在'));
        }
        $map = array('customid' => $customid, 'activeid' => $activeId);
        $list = M('income_books')->where($map)->find();
        if ($list != false) {
            returnMsg(array('status' => '05', 'codemsg' => '该活动已入账'));
        }
        if($type=='01'){
            $description='活动结束入账';
        }elseif($type=='02'){
            $description='君答入账';
        }elseif($type=='12'){
            $description='一帮二货入账';
        }
        $this->model->startTrans();

//        $sql = "INSERT INTO INCOME_BOOKS VALUES ('{$customid}','{$storeId}','{$activeId}','{$amount}',1,";
//        $sql .= date('Ymd') . ",'" . date('H:i:s') . "','{$description}','{$endTime}','','')";
        //-edit by wan 防止活动订单重复提交
        $placeddate = date('Ymd');$placedtime = date('H:i:s');
        $sql = "insert when(not exists(select 1 from income_books  where storeid='{$storeId}' and activeid='{$activeId}')) then ";
        $sql.=" into income_books(customid,storeid,activeid,amount,status,placeddate,placedtime,description,endtime) ";
        $sql.=" select '{$customid}','{$storeId}','{$activeId}','{$amount}','1','{$placeddate}','{$placedtime}','{$description}','{$endTime}' from dual";
        $incomeIf = $this->model->execute($sql);
        if($incomeIf==false){
            $this->model->rollback();
            returnMsg(array('status' => '10', 'codemsg' => '此活动已经入账'));
        }

        $map1 = array('customid' => $customid);
        $wallet = M('wallet')->where($map1)->find();
        if ($wallet == false) {
            if($type=='02'){
                $sql1 = "INSERT INTO WALLET VALUES('{$customid}','$amount','')";
            }else{
                $sql1 = "INSERT INTO WALLET VALUES('{$customid}','$amount','{$storeId}')";
            }
        } else {
            if($type=='01'&&empty($wallet['storeid'])){
                $sql1 = "UPDATE WALLET SET AMOUNT=AMOUNT+{$amount},storeid='{$storeId}' WHERE CUSTOMID='{$customid}'";
            }else{
                $sql1 = "UPDATE WALLET SET AMOUNT=AMOUNT+{$amount} WHERE CUSTOMID='{$customid}'";
            }
        }
        $walletIf = $this->model->execute($sql1);
        if ($incomeIf == true && $walletIf == true) {
            $this->model->commit();
            $senData = json_encode($data);
            $url = C('zfjsIp')."/admin/getcash";

            $res = crul_post($url, $senData);
            $res=json_decode($res,1);
            if ($res['status'] == 1) {
                $sql2 = "UPDATE INCOME_BOOKS SET ISSYNC=1 WHERE CUSTOMID='{$customid}' AND STOREID='{$storeId}' AND ACTIVEID='{$activeId}'";
            } else {
                $sql2 = "UPDATE INCOME_BOOKS SET ISSYNC=0 WHERE CUSTOMID='{$customid}' AND STOREID='{$storeId}' AND ACTIVEID='{$activeId}'";
            }
            $incomeIf2 = $this->model->execute($sql2);
            if($type=='01'){
                $sendType='12';
                $recordtitle='活动收入';

            }else{
                $sendType='11';
                $recordtitle='君答收入';
            }

            $sendData1 = array('type' => $sendType, 'amount' => $amount, 'state' => '01', 'accounttype' => '1',
                'ordercode' => $activeId, 'remark' => '', 'recordtitle'=>$recordtitle,'payflag'=>1,
                'key' => md5($this->keycode . $sendType . $amount . '011' . $activeId.$recordtitle.'1'));
            $datami1 = $this->DESedeCoder->encrypt(json_encode($sendData1));
            $sendData2 = array('datami' => $datami1, 'accessToken' => $accessToken);
            //$url1 = C('walletIp').'/jyo2o_web/app/zzcard/saveRecordToZz.json?accessToken=' . $accessToken;
            $url1 = '10.1.0.151:8080/jyo2o_web/app/zzcard/saveRecordToZz.json?accessToken=' . $accessToken;
            $res1 = crul_post($url1, $sendData2);
            $res1=json_decode($res1,1);
            if($res1['code']=='100'){
                $sql3="update INCOME_BOOKS set remark='1' where customid='{$customid}' and activeid='{$activeId}'";
            }else{
                $sql3="update INCOME_BOOKS set remark='0' where customid='{$customid}' and activeid='{$activeId}'";
            }
            $this->model->execute($sql3);
            returnMsg(array('status' => '1', 'codemsg' => '入账成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '06', 'codemsg' => '入账失败'));
        }
    }

    //获取账户收支信息接口
    public function getIncomeList()
    {
		$data = getPostJson();
		$customid = trim($data['customid']);
        $accessToken = trim($data['accessToken']);
		$key = trim($data['key']);
       //$customid = 'MDAxMjI1MDk=';//trim($data['customid']);
        //$accessToken = '8b82b29d3cbe49cbbeb4a9920e779891';//trim($data['accessToken']);
        //$key = '8e6c7ae6a29efc1954b8f2a8f2850d44';//trim($data['key']);
		
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $accessToken);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        if (empty($accessToken)) {
            returnMsg(array('status' => '05', 'codemsg' => 'accessToken缺失'));
        }
		
        $map = array('customid' => $customid);
        $detail = $this->accountDetail($customid);
        if(empty($detail)){
	        returnMsg(array('status' => '04', 'codemsg' => '无记录'));
        }else{
	        returnMsg(array('status' => '1', 'list' => $detail));
        }
//        $list1 = M('income_books')->where($map)->field('customid,amount,status,placeddate,placedtime,description')->order('placeddate desc,placedtime desc')->select();
//		$list2 = $this->getAccountList($accessToken);
//        if (empty($list1) && empty($list2)) {
//            returnMsg(array('status' => '04', 'codemsg' => '无记录'));
//        } else {
//            $resList1 = array();
//            $resList2 = array();
//            if (!empty($list1)) {
//                foreach ($list1 as $key => $val) {
//                    $arr = array();
//                    if ($val['status'] == 1) {
//                        $arr['amount'] = '+' . floatval($val['amount']);
//                    } else {
//                        $arr['amount'] = '-' . floatval($val['amount']);
//                    }
//                    $arr['date'] = date('Y-m-d', strtotime($val['placeddate'])) . ' ' . $val['placedtime'];
//                    $arr['description'] = $val['description'];
//                    $arr['flag'] = $val['status'];
//                    $resList1[] = $arr;
//                }
//            }
//            if (!empty($list2)) {
//                $resList2 = $list2;
//            }
//            $list = array_merge($resList1, $resList2);
//            returnMsg(array('status' => '1', 'list' => $list));
//        }
    }

    //提现接口
    public function withdraw()
    {
//        $customid = encode('00185630');
//        $amount = 0.01;
//        $bankname = 2;
//        $bankno = 3;
//        $bankbranch = 4;

        $data = getPostJson();
        $customid = trim($data['customid']);
        $amount = trim($data['amount']);
        $bankname = trim($data['bankname']);
        $bankno = trim($data['bankno']);
        $bankbranch = trim($data['bankbranch']);
        $accessToken = trim($data['accessToken']);
        $key = trim($data['key']);
        $this->recordData(json_encode($data));
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $amount . $accessToken . $bankname . $bankno . $bankbranch);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customInfo = $this->customs->where(array('customid' => $customid))->find();
        if ($customInfo == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
            returnMsg(array('status' => '04', 'codemsg' => '提现金额格式有误'));
        }
        $this->model->startTrans();
        $walletInfo = $this->model->table('wallet')->where(array('customid' => $customid))->find();
        if ($walletInfo['amount'] < $amount) {
            returnMsg(array('status' => '05', 'codemsg' => '账户余额不足'));
        }
//        $bankInfo=$this->model->table('custombank')->where(array('customid'=>$customid))->find();
//        if($bankInfo==false){
//            returnMsg(array('status'=>'06','codemsg'=>'银行信息未完善'));
//        }
        if (empty($bankname) && empty($bankno) && empty($bankbranch)) {
            returnMsg(array('status' => '06', 'codemsg' => '银行信息不完整'));
        }
        $currentDate = date('Ymd');
        $currentTime = date('H:i:s');
        $sql = "UPDATE WALLET SET AMOUNT=AMOUNT-{$amount} WHERE CUSTOMID='{$customid}'";
        $walletIf = $this->model->execute($sql);
        $uniqid = uniqid();
        $sql1 = "INSERT INTO INCOME_BOOKS VALUES('{$customid}','{$walletInfo['storeid']}','{$uniqid}','{$amount}','2','{$currentDate}','{$currentTime}','用户线上提现','','0','')";
        $incomeIf = $this->model->execute($sql1);

        if ($walletIf == true && $incomeIf == true) {
            $storeid = $walletInfo['storeid'];
            $key = md5($this->keycode . $storeid . $amount . encode($customid) . $uniqid . $customInfo['namechinese'] . $bankname . $bankno . $bankbranch);
            $sendData = array('storeid' => $storeid, 'amount' => $amount, 'customid' => encode($customid), 'uniqid' => $uniqid,
                'name' => $customInfo['namechinese'], 'bankname' => $bankname,
                'bankno' => $bankno, 'bankbranch' => $bankbranch, 'key' => $key);
            $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
            $datami = json_encode(array('datami' => $datami));
            $url =C('zfjsIp'). '/admin/cash';
            $res = crul_post($url, $datami);
            $res=json_decode($res,1);
            if($res['status']==1){
                $sql2="update INCOME_BOOKS set remark='1' where customid='{$customid}' and activeid='{$uniqid}'";
            }else{
                $sql2="update INCOME_BOOKS set remark='0' where customid='{$customid}' and activeid='{$uniqid}'";
            }
            $this->model->execute($sql2);
            $sendData1 = array('type' => '08', 'amount' => $amount, 'state' => '01', 'accounttype' => '1',
                'ordercode' => $uniqid, 'remark' => '', 'recordtitle'=>'个人提现','payflag'=>2,
                'key' => md5($this->keycode . '08' . $amount . '011' . $uniqid.'个人提现2'));
            $datami1 = $this->DESedeCoder->encrypt(json_encode($sendData1));
            $sendData2 = array('datami' => $datami1, 'accessToken' => $accessToken);
            $url1 = C('walletIp').'/jyo2o_web/app/zzcard/saveRecordToZz.json?accessToken=' . $accessToken;
            $res1 = crul_post($url1, $sendData2);
            $res1=json_decode($res1,1);
            if($res1['code']=='100'){
                $sql3="update INCOME_BOOKS set remark=remark||'1' where customid='{$customid}' and activeid='{$uniqid}'";
            }else{
                $sql3="update INCOME_BOOKS set remark=remark||'0' where customid='{$customid}' and activeid='{$uniqid}'";
            }
            $this->model->execute($sql3);
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '发起提现成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '07', 'codemsg' => '发起提现失败'));
        }
    }

    //批量处理未推送成功的提现记录
    public function withdrawBatch(){
        $map=array('ib.descrition'=>'用户线上提现','ib.remark'=>array('like','0%'));
        $list=M('income_books')->alias('ib')->join('custombank cb on cb.cusotmid=ib.customid')
            ->join('customs cu on cu.customid=ib.customid')->where($map)->field('ib.*,cb.*,cu.namechinese')->select();
        if(!empty($list)){
            foreach($list as $key=>$val){
                $storeid = $val['storeid'];
                $key = md5($this->keycode . $storeid . $val['amount'] . encode($val['customid']) . $val['activeid'] .
                    $val['namechinese'] . $val['bankname'] . $val['bankno'] . $val['bankbranch']);
                $sendData = array('storeid' => $storeid, 'amount' => $val['amount'], 'customid' => encode($val['customid']),
                    'uniqid' => $val['activeid'], 'name' => $val['namechinese'], 'bankname' => $val['bankname'],
                    'bankno' => $val['bankno'], 'bankbranch' => $val['bankbranch'], 'key' => $key);
                $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
                $datami = json_encode(array('datami' => $datami));
                $url =C('zfjsIp'). '/admin/cash';
                $res = crul_post($url, $datami);
                if($res['status']==1){
                    $remark='1';
                }else{
                    $remark='0';
                }
                $sql="update INCOME_BOOKS set remark='{$remark}' where customid='{$val['customid']}' and activeid='{$val['activeid']}'";
                $this->model->execute($sql);
            }
        }
    }

    //提现记录回调
    public function withdrawCallback()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        $uniqid = trim($data['uniqid']);
        $key = trim($data['key']);

//        $customid='MDAxMTY0MDcO0O0O';
//        $uniqid='57e49480153a3';

        $this->recordData(json_encode($data));
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $uniqid);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customInfo = $this->customs->where(array('customid' => $customid))->find();
        if ($customInfo == false) {
            returnMsg(array('status' => '03', 'codemsg' => '用户不存在'));
        }
        $map = array('customid' => $customid, 'activeid' => $uniqid);
        $list = M('income_books')->where($map)->find();
        if ($list == false) {
            returnMsg(array('status' => '04', 'codemsg' => '无此提现记录'));
        }
        if ($list['issync'] == 1) {
            returnMsg(array('status' => '05', 'codemsg' => '此提现已回调'));
        }
        $sql = "update income_books set issync=1 where customid='{$customid}' and activeid='{$uniqid}'";
        if ($this->model->execute($sql)) {
            returnMsg(array('status' => '1', 'codemsg' => '回调成功'));
        } else {
            returnMsg(array('status' => '06', 'codemsg' => '回调失败'));
        }
    }

    //获取账户信息
    public function getAccountList($accessToken)
    {	
		//$accessToken = 'f08f62a60529451f8d3b4b81a834e238';
        $sendData1 = array('accounttype' => '1', 'type' => '', 'pageNo' => '1', 'pageSize' => '500',
            'key' => md5($this->keycode . '11500'));
        $datami = $this->DESedeCoder->encrypt(json_encode($sendData1));
        //$datami = 'HM6EyOXFsXkAbDowRSqtixpt99KmUPiCrNUn5xsLAIHn1tas76oBUZodgVO8la2wmY158ArkI/pERIFl0QPFV4DHd3WvHCfhOXwZ+5a3BPnfzQYGN0zvu7qRE6uGEDTHsHdlQBvKjiE=';
		$datami = array('datami' => $datami);
		$url =C('walletIp'). '/jyo2o_web/app/zzcard/getrecordToZz.json?accessToken=' . $accessToken;
        $res = crul_post($url, $datami);
		if (!empty($res)){
			$res = json_decode($res,true);
		}
        if ($res['code'] == '100') {
            $list = array();
            $data = $res['data'];
            $typeArr = array(
                '01' => '支付宝充值', '02' => '微信充值',
                '03' => '银联充值', '04' => '至尊卡关联', '05' => '订单交易消费',
                '06' => '支付宝提现', '07' => '微信提现', '08' => '银联提现',
                '09' => '退款', '10' => '红包充值','11'=>'分答奖励充值'
            );
            if (!empty($data)) {
                foreach ($data as $key => $val) {
					$val['createTime'] = substr($val['createTime'] , 0 , 10);
                    $list[$key]['date'] = date('Y-m-d H:i:s', $val['createTime']);
                    $list[$key]['description'] = $typeArr[$val['type']];

//                    if ($val['type'] == '01' || $val['type'] == '02' || $val['type'] == '03' || $val['type'] == '04' || $val['type'] == '09' || $val['type'] == '10'|| $val['type'] == '11') {
//                        $list[$key]['amount'] = '+'.$val['amount'];
//                        $list[$key]['flag'] = 1;
//                    } else {
//                        $list[$key]['amount'] = '-'.$val['amount'];
//                        $list[$key]['flag'] = 2;
//                    }
	                //收入支出判定
	                if($val['payflag']=="1"){
		                $list[$key]['amount'] = '+'.$val['amount'];
                        $list[$key]['flag'] = 1;
	                }else{
		                $list[$key]['amount'] = '-'.$val['amount'];
                        $list[$key]['flag'] = 2;
	                }
                }
				return $list;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //校验支付密码
    public function examPayPwd()
    {
        $data = getPostJson();
        $customid = trim($data['customid']);
        $paypwd = trim($data['paypwd']);
        $key = trim($data['key']);
        if (empty($customid)) {
            returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
        }
        $checkKey = md5($this->keycode . $customid . $paypwd);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '无效会员'));
        }
        $paypwd = $this->decodePwd($paypwd);
        if ($paypwd == false) {
            returnMsg(array('status' => '04', 'codemsg' => '非法密码传入'));
        }
        $pwdBool = $this->checkPayPwd($customid, $paypwd);
        if ($pwdBool === '01') {
            returnMsg(array('status' => '05', 'codemsg' => '支付密码错误'));
        } elseif ($pwdBool === '02') {
            returnMsg(array('status' => '06', 'codemsg' => '当天密码错误次数超过三次'));
        } elseif ($pwdBool === 1) {
            returnMsg(array('status' => '1', 'codemsg' => '校验通过'));
        }
    }

    //店铺更新账户信息
    public function updateCustom(){
        $data = getPostJson();
        //print_r($data);exit;
        $datami = trim($data['datami']);
        $datami = $this->DESedeCoder->decrypt($datami);
        if ($datami == false) {
            returnMsg(array('status' => '09', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
//        $datami='{"customid":"MDAxMTY0MDcO0O0O","name":"玉山","personid":"411522198804070091","address":"商务内环","sex":"0","personidexdate":"2016-09-30","career":"职业","frontimg":"http://dfs.jianyezuqiu.cn:8090/group2/M01/2B/28/CqwC8FflLDGANIxaAAVcwDkbMJc864.jpg","reserveimg":"http://dfs.jianyezuqiu.cn:8090/group2/M01/2B/28/CqwC8FflLDGAQO-IAAVTc_TTNU8895.jpg","bankname":"中国银行","bankno":"6217858000001525258","bankbranch":"中国银行","storeid":"33047","key":"12843c0fe1d9f8c85353bcce5c2577e0"}';
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $name = trim($datami['name']);
        $personid = trim($datami['personid']);
        $address = trim($datami['address']);
        $sex = trim($datami['sex']);
        $personidexdate = trim($datami['personidexdate']);
        $career = trim($datami['career']);
        $frontimg = trim($datami['frontimg']);
        $reserveimg = trim($datami['reserveimg']);
        $bankname = trim($datami['bankname']);
        $bankno = trim($datami['bankno']);
        $bankbranch = trim($datami['bankbranch']);
        $storeid = trim($datami['storeid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode . $customid . $name . $personid . $address . $sex . $personidexdate . $career . $frontimg . $reserveimg . $bankname . $bankno . $bankbranch . $storeid);
        if ($checkKey != $key) {
            returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
        }
        if (empty($customid)) {
            returnMsg(array('status' => '02', 'codemsg' => '会员编号缺失'));
        }
        $customid = decode($customid);
        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '03', 'codemsg' => '无效会员'));
        }
        //echo $name.'--'.$personid.'--'.$sex.'--'.$personidexdate.'--'.$address;exit;
        if (empty($name) || empty($personid)  || empty($personidexdate) || empty($address)) {
            returnMsg(array('status' => '04', 'codemsg' => '个人信息不完整'));
        }
        if (empty($bankname) || empty($bankno) || empty($bankbranch)) {
            returnMsg(array('status' => '05', 'codemsg' => '银行信息不完整'));
        }
        if (empty($storeid)) {
            returnMsg(array('status' => '06', 'codemsg' => '店铺id缺失'));
        }
        if (!empty($frontimg)) {
            $photoJust = $this->catchImg($frontimg);
            if ($photoJust != false) {
                $justImg = $this->saveImg($photoJust);
                $data1['frontimg'] = $justImg;
            }
        }
        if (!empty($reserveimg)) {
            $photoReverse = $this->catchImg($reserveimg);
            if ($photoReverse != false) {
                $reverseImg = $this->saveImg($photoReverse);
                $data1['reserveimg'] = $reverseImg;
            }
        }
        if (!empty($name)) {
            $data1['namechinese'] = $name;
        }
        if (!empty($personid)) {
            $data1['personid'] = $personid;
        }
        if (!empty($address)) {
            $data1['residaddress'] = $address;
        }
        if (!empty($sex)) {
            $data1['sex'] = $sex;
        }
        if (!empty($personidexdate)) {
            $data1['personidexdate'] = $personidexdate;
        }
        if (!empty($career)) {
            $data1['career'] = $career;
        }
        $this->model->startTrans();
        if(!empty($data1)){
            $map = array('customid' => $customid);
            $customIf = $this->model->table('customs')->where($map)->save($data1);
        }else{
            $customIf=true;
        }
        $map1 = array('customid' => $customid);
        $wallet = M('wallet')->where($map1)->find();
        if ($wallet == false) {
            $sql1 = "INSERT INTO WALLET VALUES('{$customid}','0','{$storeid}')";
            $walletIf = $this->model->execute($sql1);
        }else{
            $walletIf=true;
        }
        if(!empty($bankname) || !empty($bankno) || !empty($bankbranch)){
            $map2 = array('customid' => $customid);
            $list = M('custombank')->where($map2)->find();
            if ($list != false) {
                if (!empty($bankname)) {
                    $data2['bankname'] = $bankname;
                }
                if (!empty($bankno)) {
                    $data2['bankno'] = $bankno;
                }
                if (!empty($bankbranch)) {
                    $data2['bankbranch'] = $bankbranch;
                }
                $map2['customid'] = $customid;
                $bankIf = $this->model->table('custombank')->where($map2)->save($data2);
            } else {
                $sql2 = "INSERT INTO CUSTOMBANK VALUES('{$customid}','{$bankname}','{$bankno}','{$bankbranch}')";
                $bankIf = $this->model->execute($sql2);
            }
        }else{
            $bankIf=true;
        }
        if ($customIf == true && $bankIf == true && $bankIf == true) {
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '数据完善成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '08', 'codemsg' => '数据完善失败'));
        }
    }

    public function catchImg($imgUrl){
        $curl = curl_init($imgUrl); //初始化
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  //将结果输出到一个字符串中，而不是直接输出到浏览器
        curl_setopt($curl, CURLOPT_REFERER, $imgUrl); //最重要的一步，手动指定Referer
        $res = curl_exec($curl); //执行
        if (curl_errno($curl)) {
            curl_close($curl);
            return NULL;
        }
        curl_close($curl);
        return $res;
    }
    public function saveImg($file){
        $path=IMAGE_PATH.'photoes/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        $filename=$path.uniqid('id_').'.jpg';
        file_put_contents($filename,$file);
        return $filename;
    }

    //周边商户钱包入账
    public function income1(){
        $data = getPostJson();
        //$data=json_decode('{"datami":"UbQpXFo0vj\/sTbDGne4wxtq7oEfYro1x2sUMW230Dq04UR4LesVhyBTIh38mC5ra8CvGcfs2AiaGaac+cKeFGE7Uyx4ata6dkPPw1CuT74kbTGUMsAwSAIxsZXl3OwiM0oEW1E8uzMs3vAx+rZF95qHARqTpK\/dNnVKPX\/RRRJ0="}',1);
        $datami = trim($data['datami']);
        $datami = $this->DESedeCoder->decrypt($datami);
        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
        $datami = json_decode($datami, 1);
        $storeId = trim($datami['storeid']);
        $orderId = trim($datami['orderid']);
        $amount = trim($datami['amount']);
        $account=trim($datami['account']);
        $type=trim($datami['type']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode  . $storeId . $orderId . $amount . $type.$account);
        $returnData=array('storeid'=>$storeId,'orderid'=>$orderId,'amount'=>$amount);
        if ($checkKey != $key) {
            $returnData['status']='02';
            $this->returnData($returnData);
            //returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        if(empty($type)){
            $returnData['status']='03';
            $this->returnData($returnData);
            //returnMsg(array('status' => '03', 'codemsg' => '入账类型缺失'));
        }
        if(empty($storeId)){
            $returnData['status']='04';
            $this->returnData($returnData);
            //returnMsg(array('status' => '04', 'codemsg' => '商铺id缺失'));
        }
        $map=array('storeid'=>$storeId);
        $wallet = M('wallet')->where($map)->find();
        if($wallet==false){
            $returnData['status']='05';
            $this->returnData($returnData);
            //returnMsg(array('status' => '05', 'codemsg' => '钱包无关联用户'));
        }
        if($wallet['amount']!=$account){
            $returnData['status']='06';
            $this->returnData($returnData);
            //returnMsg(array('status' => '06', 'codemsg' => '账户金额异常'));
        }
        $customid=$wallet['customid'];
        $map = array('customid' => $customid, 'activeid' => $orderId);
        $list = M('income_books')->where($map)->find();
        if ($list != false) {
            $returnData['status']='07';
            $this->returnData($returnData);
            //returnMsg(array('status' => '07', 'codemsg' => '该订单已入账'));
        }
        $description='周边商户消费入账';
        $this->model->startTrans();
        $sql = "INSERT INTO INCOME_BOOKS VALUES ('{$customid}','{$storeId}','{$orderId}','{$amount}',1,";
        $sql .= date('Ymd') . ",'" . date('H:i:s') . "','{$description}','','','')";
        $incomeIf = $this->model->execute($sql);
        $map1 = array('customid' => $customid);
        $wallet = M('wallet')->where($map1)->find();
        if ($wallet == false) {
            $sql1 = "INSERT INTO WALLET VALUES('{$customid}','$amount','{$storeId}')";
        } else {
            $sql1 = "UPDATE WALLET SET AMOUNT=AMOUNT+{$amount} WHERE CUSTOMID='{$customid}'";
        }
        $walletIf = $this->model->execute($sql1);
        if ($incomeIf == true && $walletIf == true) {
            $this->model->commit();
            $returnData['status']='1';
            $this->returnData($returnData);
            //returnMsg(array('status' => '1', 'codemsg' => '入账成功'));
        } else {
            $this->model->rollback();
            $returnData['status']='07';
            $this->returnData($returnData);
            //returnMsg(array('status' => '07', 'codemsg' => '入账失败'));
        }
    }

    public function returnData($returnData){
        //$returnData=array('storeid'=>'33115','orderid'=>'SW201609241503352921653537557','amount'=>'0.02','status'=>1);
        $sendString=$this->DESedeCoder->encrypt(json_encode($returnData));
        $sendData=json_encode(array('datami'=>$sendString));
        $url = C('zfjsIp')."/admin/merchant";
        if($returnData['status']==1){
            sleep(5);
        }
        $res = crul_post($url, $sendData);
        $res=json_decode($res,1);
        if($res['staus']=='1'){
            $map=array('activeid'=>$returnData['orderid']);
            $data['issync']=1;
        }else{
            $map=array('activeid'=>$returnData['orderid']);
            $data['issync']=0;
        }
        $this->model->table('income_books')->where($map)->save($data);
        exit;
    }

    public function test111(){
//        $sendType='12';
//        $recordtitle='君答收入';
//        $amount='0.1';
//        $activeId='233030';
//        $accessToken='767e178388e64beda1ec7b91d1e0927c';
//        $sendData1 = array('type' => $sendType, 'amount' => $amount, 'state' => '01', 'accounttype' => '1',
//            'ordercode' => $activeId, 'remark' => '', 'recordtitle'=>$recordtitle,'payflag'=>1,
//            'key' => md5($this->keycode . $sendType . $amount . '011' . $activeId.$recordtitle.'1'));
//        $datami1 = $this->DESedeCoder->encrypt(json_encode($sendData1));
//        $sendData2 = array('datami' => $datami1, 'accessToken' => $accessToken);
//        //$url1 = C('walletIp').'/jyo2o_web/app/zzcard/saveRecordToZz.json?accessToken=' . $accessToken;
//        $url1 = '10.1.0.151:8080/jyo2o_web/app/zzcard/saveRecordToZz.json?accessToken=' . $accessToken;
//        $res1 = crul_post($url1, $sendData2);
//        var_dump($res1);
//        $res1=json_decode($res1,1);
//        //var_dump($res1);
//        exit;
        $array=array(
//            array('customid'=>'00277287','storeid'=>'33033','uniqid'=>'58184ee27495d','name'=>'李心和','amount'=>'598.5',
//                'bankname'=>'建设银行','bankno'=>'6217002520000201946','bankbranch'=>'中国建设银行鹤壁九洲路中段支行'),
//            array('customid'=>'00282860','storeid'=>'33024','uniqid'=>'581862dbdc0c2','name'=>'刘璐','amount'=>'718.2',
//                'bankname'=>'中国建设银行','bankno'=>'6236682540009402177','bankbranch'=>'三门峡大岭路支行'),
//            array('customid'=>'00286458','storeid'=>'33037','uniqid'=>'58186570bad5b','name'=>'梁娟','amount'=>'200',
//                'bankname'=>'中国建设银行','bankno'=>'6217002590005244498','bankbranch'=>'南阳建设银行卧龙区支行'),
//            array('customid'=>'00279354','storeid'=>'33019','uniqid'=>'581af996cd7e8','name'=>'董冉','amount'=>'3950.1',
//                'bankname'=>'中国工商银行','bankno'=>'6212261702016226375','bankbranch'=>'济源济水大街支行'),
//            array('customid'=>'00277294','storeid'=>'33023','uniqid'=>'581866372c707','name'=>'李辉','amount'=>'1037.4',
//                'bankname'=>'中国建设银行','bankno'=>'6217002480004389397','bankbranch'=>'建设银行焦作市龙源湖支行'),
//            array('customid'=>'00282690','storeid'=>'33027','uniqid'=>'581ad5c54203f','name'=>'王文凯','amount'=>'210',
//                'bankname'=>'建设银行','bankno'=>'6210812470001620634','bankbranch'=>'商丘市民主路支行'),
//            array('customid'=>'00277448','storeid'=>'33250','uniqid'=>'581afaacedf01','name'=>'陈茜茜','amount'=>'39',
//                'bankname'=>'中国建设银行','bankno'=>'6210812480006345863','bankbranch'=>'沁园路分理处')
//            array('customid'=>'00282860','storeid'=>'33024','uniqid'=>'581473be1b3df','name'=>'刘璐','amount'=>'2010',
//            'bankname'=>'中国建设银行','bankno'=>'6236682540009402177','bankbranch'=>'三门峡大岭路支行'),
//            array('customid'=>'00277230','storeid'=>'33007','uniqid'=>'585609f77c2ee','name'=>'王喜燕','amount'=>'220',
//            'bankname'=>'中国建设银行','bankno'=>'6217002450008024373','bankbranch'=>'中国建设银行洛阳分行牡丹支行'),
        );
        foreach($array as $key=>$val){
            $storeid = $val['storeid'];
            $customid=$val['customid'];
            $amount=$val['amount'];
            $name=$val['name'];
            $bankname=$val['bankname'];
            $uniqid=$val['uniqid'];
            $bankno=$val['bankno'];
            $bankbranch=$val['bankbranch'];
            $key = md5($this->keycode . $storeid . $amount . encode($customid) . $uniqid . $name . $bankname . $bankno . $bankbranch);
            $sendData = array('storeid' => $storeid, 'amount' => $amount, 'customid' => encode($customid),
                'uniqid' => $uniqid, 'name' => $name, 'bankname' => $bankname,
                'bankno' => $bankno, 'bankbranch' => $bankbranch, 'key' => $key);
            $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
            $datami = json_encode(array('datami' => $datami));
            $url ='http://www.9617777.com/admin/cash';
            $res = crul_post($url, $datami);
            $res=json_decode($res,1);
            //var_dump($res);
            if($res['status']==1){
                $sql2="update INCOME_BOOKS set remark='1' where customid='{$customid}' and activeid='{$uniqid}'";
            }else{
                $sql2="update INCOME_BOOKS set remark='0' where customid='{$customid}' and activeid='{$uniqid}'";
            }
            echo $sql2.'<br/>';
            $this->model->execute($sql2);
        }
    }

	public function accountDetail($customid='')
    {
        $model = new Model();
        $arr = [];
        $detail = [];
        #start
        $zzk_account_detail = $model->table('zzk_account')->alias('za')
            ->join('left join zzk_account_detail zad on za.accountid=zad.accountid')
            ->field('za.customid,zad.charge_amount,zad.tradetype,zad.placeddate,zad.placedtime,zad.source')
            ->order('zad.placeddate desc,zad.placedtime desc')
            ->where(['za.customid' => $customid, 'za.type' => '05', 'zad.tradetype' => array('not in', '53,54')])//53 54 冻结 解冻, 'source' => array('not in', '06')
            ->select();

        if (!empty($zzk_account_detail)) {
            foreach ($zzk_account_detail as $key => $val) {
                $arr = array();
                if (in_array($val['tradetype'], array('51', '56', '57'))) {
                    $arr['amount'] = '+' . floatval($val['charge_amount']);
                    $arr['flag'] = 1;
                } else if (in_array($val['tradetype'], array('50', '52', '55'))) {
                    $arr['amount'] = '-' . floatval($val['charge_amount']);
                    $arr['flag'] = 2;
                }
                $arr['date'] = date('Y-m-d', strtotime($val['placeddate'])) . ' ' . $val['placedtime'];
                switch ($val['tradetype']) {
                    case "51":
                        $arr['description'] = '充值';
                        break;
                    case "56":
                        $arr['description'] = '转账充值';
                        break;
                    case "50":
                        $arr['description'] = '消费';
                        break;
                    case "52":
                        $arr['description'] = '提现';
                        break;
                    case "55":
                        $arr['description'] = '转账扣款';
                        break;
                    case "57":
                        $arr['description'] = '退款';
                        break;
                    default:
                        $arr['description'] = '';
                }
                $detail[] = $arr;
            }
        }
        #end
        $map = array('customid' => $customid);
        $chargeMap['cu.customid'] = $customid;
        $chargeMap['cpl.flag'] = '1';
        $chargeMap['cpl.amount'] = ['gt', 0];
        $field = 'cu.customid customid,cpl.amount amount,cpl.flag as status,cpl.placeddate placeddate,cpl.placedtime placedtime,cpl.description';

        $charge = M('customs')->alias('cu')
            ->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards ca on ca.customid=cc.cid')
            ->join('left join card_purchase_logs cpl on cpl.cardno=ca.cardno')
            ->field($field)->where($chargeMap)->select();
        if ($charge) {
            foreach ($charge as $val) {
                if (strpos($val['description'], 'LM') === false) {
                    $arr['description'] = '余额充值';
                } else {
                    $arr['description'] = '红包充值';
                }
                $arr['date'] = date('Y-m-d', strtotime($val['placeddate'])) . ' ' . $val['placedtime'];
                $arr['amount'] = '+' . floatval($val['amount']);
                $arr['flag'] = $val['status'];
                $detail[] = $arr;
            }
        }
        $consumeMap['cu.customid'] = $customid;
        $consumeMap['tw.tradetype'] = ['in', ['00', '07', '25']];#start 新增 25 #end
        $consumeMap['tw.tradeamount'] = ['neq', 0];


        $field = "cu.customid customid,tw.tradeamount amount,2 as status,tw.placeddate placeddate,tw.placedtime placedtime,tw.termno,tw.tradetype";
        $consume = M('customs')->alias('cu')
            ->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards ca on ca.customid=cc.cid')
            ->join('left join trade_wastebooks tw on tw.cardno=ca.cardno')
            ->where($consumeMap)
            ->order('tw.placeddate desc,tw.placedtime desc')
            ->field($field)->select();
        if ($consume) {
            foreach ($consume as $cv) {
                if ($cv['termno'] == '00000000') {
                    $arr['description'] = '线上消费';
                } else {
                    $arr['description'] = '线下消费';
                }
                if ($cv['tradetype'] == '00') {
                    $arr['amount'] = '-' . floatval($cv['amount']);
                } elseif ($cv['tradetype'] == '07') {
                    $arr['amount'] = '+' . floatval(-$cv['amount']);
                    $arr['description'] = $arr['description'] . '-退款';
                }
                #start
                elseif ($cv['tradetype'] == '25') {
                    $arr['amount'] = '-' . floatval($cv['amount']);
                    $arr['description'] = '转账扣款';
                }
                #end

                $arr['date'] = date('Y-m-d', strtotime($cv['placeddate'])) . ' ' . $cv['placedtime'];

                $arr['flag'] = $cv['status'];
                #start
                if ($cv['tradetype'] == '07') {
                    $arr['flag'] = 1;
                }
                #end
                $detail[] = $arr;
            }
        }


        $list = $model->table('income_books')
            ->field('customid,amount,status,placeddate,placedtime,description')
            ->order('placeddate desc,placedtime desc')
            ->where(['customid' => $customid])
            ->select();
        if ($list) {
            foreach ($list as $key => $val) {
                $arr = array();
                if ($val['status'] == 1) {
                    $arr['amount'] = '+' . floatval($val['amount']);
                } else {
                    $arr['amount'] = '-' . floatval($val['amount']);
                }
                $arr['date'] = date('Y-m-d', strtotime($val['placeddate'])) . ' ' . $val['placedtime'];
                $arr['description'] = $val['description'];
                $arr['flag'] = $val['status'];
                $detail[] = $arr;
            }
        }
        if ($detail) {
            $date = array_column($detail, 'date');
            array_multisort($date, SORT_DESC, $detail);
        }
        return $detail;
    }

}


