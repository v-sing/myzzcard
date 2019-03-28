<?php
namespace Home\Controller;
use Org\Util\Des;
use Think\Controller;
use Think\Model;
use Org\Util\YjDes;
class JyCurrencyController extends CoinController{

    /*房产建业币发行
    public function coinIssue(){
        $data=getPostJson();
        //$this->recordData($data);
        $cardno=trim($data['cardno']);
        $coinAmount=trim($data['coinAmount']);
        $namechinese=trim($data['name']);
        $linktel=trim($data['mobile']);
        $personid=trim($data['personid']);
        $orderAmount=trim($data['orderAmount']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode];
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }

        $card=$this->checkCard($cardno);

        //$customMap=array('namechinese'=>$namechinese,'personid'=>$personid);
        $customMap=array('linktel'=>$namechinese,'customlevel'=>'建业线上会员');
        $custom=$this->customs->where($customMap)->field('customid')->find();
        //echo $this->customs->getLastSql();exit;
        if($card==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        $this->model->startTrans();
        if($card['status']=='N'){
            if($custom==false){
                $customArr=array('namechinese'=>$namechinese,'linktel'=>$linktel,'personid'=>$personid,'panterid'=>$panterid);
                $customid=$this->createCustoms($customArr,2);
            }else{
                $map=array('customid'=>$custom['customid']);
                $data=array('namechinese'=>$namechinese,'personid'=>$personid);
                $this->customs->where($map)->save($data);
                $customid=$custom['customid'];
            }
            //echo $customid;exit;
            if($customid==false){
                $this->model->rollback();
                returnMsg(array('status'=>'04','codemsg'=>'会员创建失败'));
            }
            $cardArr=array($cardno);
            $bool=$this->openCard($cardArr,$customid,0);
            if($bool==false){
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'新卡开卡失败'));
            }
        }
        $accountInfo=$this->getCoinAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡号账户异常'));
        }
        if($orderAmount<$coinAmount){
            $coinAmount1=$orderAmount;
            $coinAmount2=$coinAmount-$orderAmount;
        }else{
            $coinAmount1=$coinAmount;
            $coinAmount2=0;
        }
        $coinArr=array('coinAmount1'=>$coinAmount1,'coinAmount2'=>$coinAmount2,'totalAmount'=>$coinAmount,'type'=>1);
        $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],'accountid'=>$accountInfo['accountid'],'orderid'=>$prefix.$orderid);
        $coinBool=$this->coinRecharge($cardInfo,$coinArr,$panterid);
        if($coinBool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'建业币充值失败'));
        }
    }*/
    //房产通宝发行
    public function coinIssue(){
        $data=getPostJson();
        $this->recordData(json_encode($data));

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        $cardno=trim($data['cardno']);
        $coinAmount=trim($data['coinAmount']);
        $namechinese=trim($data['name']);
        $linktel=trim($data['mobile']);
        $personid=trim($data['personid']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $panter=$this->checkPanter($panterid);


        if(empty($cardno)||empty($namechinese)||empty($personid)||empty($linktel)||empty($orderid)){
            returnMsg(array('status'=>'010','codemsg'=>'传入数据不完整'));
        }
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }
        $card=$this->checkCard($cardno);
        //$customMap=array('namechinese'=>$namechinese,'personid'=>$personid);
        $customMap=array('namechinese'=>$namechinese,'personid'=>$personid,'customlevel'=>'建业线上会员');
        //$customMap=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $custom=$this->customs->where($customMap)->field('customid')->find();
        //echo $this->customs->getLastSql();exit;
        if($card==false){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        /*$issueBool=$this->checkIssue($panterid);
        if($issueBool==false){
            returnMsg(array('status'=>'011','codemsg'=>'商户发行金额超限'));
        }*/
        $this->model->startTrans();
        if($card['status']=='N'){
            if($custom==false){
                $customArr=array('namechinese'=>$namechinese,'linktel'=>$linktel,'personid'=>$personid,'panterid'=>$panterid);
                $customid=$this->createCustoms($customArr,2);
            }else{
                $map=array('customid'=>$custom['customid']);
                $data=array('namechinese'=>$namechinese,'personid'=>$personid);
                $this->customs->where($map)->save($data);
                $customid=$custom['customid'];
            }
            //echo $customid;exit;
            if($customid==false){
                $this->model->rollback();
                returnMsg(array('status'=>'04','codemsg'=>'会员创建失败'));
            }
            $cardArr=array($cardno);
            $bool=$this->openCard($cardArr,$customid,0,'',1,'','',0);
            if($bool==false){
                $this->model->rollback();
                returnMsg(array('status'=>'05','codemsg'=>'新卡开卡失败'));
            }
        }else{
            $where=array('c.cardno'=>$cardno);
            $custom=$this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                ->join('cards c on cc.cid=c.customid')->field('cu.namechinese cuname,cu.personid,cu.customid')->where($where)->find();
            $customid=$custom['customid'];
            if($custom['cuname']!=$namechinese){
                returnMsg(array('status'=>'08','codemsg'=>'充值至尊卡号与充值名字不一致'));
            }
        }
        $accountMap=array('sourceorder'=>$prefix.$orderid);
        $issueCounter=M('coin_account')->where($accountMap)->count();
        if($issueCounter>0){
            returnMsg(array('status'=>'09','codemsg'=>'该订单已充值'));
        }
        $accountInfo=$this->getCoinAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡号账户异常'));
        }
        $cardInfo=array('customid'=>$customid,'cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],'accountid'=>$accountInfo['accountid'],'orderid'=>$prefix.$orderid);
        $coinBool=$this->coinRecharge($cardInfo,$coinAmount,$panterid);
        if($coinBool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','customid'=>$customid));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'建业币充值失败'));
        }
    }

    //房掌柜建业币消费接口
    public function consume(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $coinAmount=trim($data['coinAmount']);
        $panterid=trim($data['panterid']);
        //$preOrderid=trim($data['preOrderid']);
        $orderid=trim($data['orderid']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $orderid=$prefix.$orderid;
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'03','codemsg'=>'消费金额格式错误'));
        }
        $coinAccount=$this->getCoinDetail($cardno,$panterid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'04','codemsg'=>'建业币余额不足'));
        }
        $coinList=$this->getCoinAccountList($cardno);
        $this->model->startTrans();
        $consumeIf=$this->coinConsumeExe($coinList,$coinAmount,$panterid,$orderid,$cardno);
        if($consumeIf==true){
            $this->model->commit();
            //---------向一家传输用户通宝信息--17:21----
            $de = new YjDes();
            $customid = $this->getCustomid($cardno);//用户会员编号
            $tb_info = D("Ehome")->getTbinfo($customid);
            if($tb_info){
                $appid  = 'SOON-ZZUN-0001';
                $tb_info['customid'] = encode($tb_info['customid']);
                $tb_info['activetype'] = 2;
                $tb_info['appid'] = $appid;
                $tb_sign = $de->encrypt($tb_info);
                $tb_data = json_encode($tb_info,JSON_FORCE_OBJECT);
                $return_yj = $this->curlPost(C('ehome_potb'),$tb_data,$tb_sign);
                $return_arr = json_decode($return_yj,1);

                if($return_arr['code'] == '100'){
                    $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","success");
                }else{
                    $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","failed");
                }

            }

            //---------end-----------------
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'消费失败'));
        }
    }

    //建业币(刷卡消费账户）消费接口
    public function consume1(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $coinAmount=trim($data['coinAmount']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $sourceCode=trim($data['sourceCode']);
        $pwd=trim($data['pwd']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        if(!empty($orderid)){
            $orderid=$prefix.$orderid;
        }else{
            $orderid='';
        }
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        if($card['cardpassword']!=$pwd){
            returnMsg(array('status'=>'03','codemsg'=>'密码错误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式错误'));
        }
        $customid=$this->getCustomid($cardno);
        $coinAccount=$this->coinQuery($customid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'05','codemsg'=>'建业币余额不足'));
        }
        $this->model->startTrans();
        $coinConsumeArr=array('customid'=>$customid,'orderid'=>$orderid,
            'panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount);
        $coinConsumeIf=$this->coinExe($coinConsumeArr);
        if($coinConsumeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'06','codemsg'=>'消费失败'));
        }
    }

    //建业币查询
    public function coinQueryByCardno(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $card=$this->checkCard($cardno);
        $panterid=empty($data['panterid'])?trim($data['panterid']):'';
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        $account=$this->getCoinDetail($cardno,$panterid);
        returnMsg(array('status'=>'1','coinAmount'=>$account));
    }

    //查询某一购房订单下建业币消费
    public function consumeList(){
        $data=getPostJson();
        $orderid=trim($data['orderid']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $cardno=trim($data['cardno']);
        $orderid=$prefix.$orderid;
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        $map=array('cardid'=>$card['customid'],'sourceorder'=>$orderid);
        $coinAccount=M('coin_account')->where($map)->find();
        $map1=array('cc.coinid'=>$coinAccount['coinid'],'cc.flag'=>1);
        $consumeList=M('coin_consume')->alias('cc')->join('panters p on p.panterid=cc.panterid')
            ->where($map1)->field('cc.amount,cc.placeddate,cc.placedtime,p.namechinese pname')->select();
        $consumeAmount=M('coin_consume')->alias('cc')->where($map1)->sum('cc.amount');
        if($consumeList!=false){
            returnMsg(array('status'=>'1','res'=>array('list'=>$consumeList,'consumeAmount'=>$consumeAmount)));
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'查无消费信息'));
        }
    }
    public function getConsumeAmount($coinid,$cardid){
        $map1=array('cc.coinid'=>$coinid,'cc.flag'=>1);
        $consumeAmount=M('coin_consume')->alias('cc')->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')->where($map1)->sum('cc.amount');
        return $consumeAmount;
    }
    //房产退款时建业币锁定
    public function orderRefund(){
        $data=getPostJson();
        $orderid=trim($data['orderid']);
        $cardno=trim($data['cardno']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $orderid=$prefix.$orderid;
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        $cardId=$card['customid'];
        $map=array('sourceorder'=>$orderid,'cardid'=>$cardId,'status'=>'01');
        $coinAccount=M('coin_account')->where($map)->select();
        if($coinAccount==false){
            returnMsg(array('status'=>'03','codemsg'=>'未查到该订单或该卡下发行的通宝记录'));
        }
        $consumeAmount=$this->getConsumeAmount($coinAccount['coinid'],$cardId);
        $this->model->startTrans();
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $coinAccountSql="UPDATE COIN_ACCOUNT SET status='00' WHERE coinid='{$coinAccount['coinid']}'";
        $coinRefundSql="INSERT INTO COIN_REFUND VALUES('{$coinAccount['coinid']}','{$coinAccount['rechargeamount']}'";
        $coinRefundSql.=",'{$coinAccount['reminamount']}','{$consumeAmount}','{$cardId}','{$currentDate}','{$currentTime}')";
        $coinAccountIf=$this->model->execute($coinAccountSql);
        $coinRefundIf=$this->model->execute($coinRefundSql);
        if($coinAccountIf==true&&$coinRefundIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'操作成功，赠送建业通宝已锁定'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'04','codemsg'=>'操作失败'));
        }
    }

    public function getCustomInfo(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $customid=decode($customid);
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户编号有误'));
        }
        $map=array('cu.customid'=>$customid);
        $customInfo=$this->customs->alias('cu')->join('left join panters p on cu.panterid=p.panterid')
            ->where($map)->field('cu.namechinese cuname,cu.linktel,cu.personid,p.namechinese pname')->find();
        $cardList=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->where($map)->field('c.cardno')->select();
        $list=array();
        foreach($cardList as $key=>$val){
            $list[]=$val['cardno'];
        }
        if($customInfo==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无此会员'));
        }else{
            returnMsg(array('status'=>'1','info'=>$customInfo,'list'=>$list));
        }
    }

    //通宝卡一家激活
    public function cardActive(){
//        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
//        $pwd=$des->doDecrypt('0A1A98728322AEAF');
//        echo $pwd;exit;
        $data=getPostJson();
        $this->recordData($data);
        $cardno=trim($data['cardno']);
        $pwd=trim($data['pwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$pwd);

//        $cardno='6889372888800156918';
//        $pwd='872499';
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'非法秘钥'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号为空'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=$des->doEncrypt($pwd);
        $userid=$this->userid;
        $cardInfo=$this->cards->where(array('cardno'=>$cardno))->find();
        if($cardInfo==false){
            returnMsg(array('status'=>'03','codemsg'=>'至尊卡不存在'));
        }
        if($pwd!=$cardInfo['cardpassword']){
            returnMsg(array('status'=>'04','codemsg'=>'密码错误'));
        }
        if($cardInfo['status']!='A'){
            returnMsg(array('status'=>'05','codemsg'=>'该卡非待激活卡'));
        }
        $this->model->startTrans();
        $cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
        $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','APP激活','{$cardInfo['customid']}'";
        $cardAlSql.=",'{$cardInfo['panterid']}','00000000')";
        $cardAlIf=$this->model->execute($cardAlSql);

        $cardSql="UPDATE cards SET status='Y' where cardno='".$cardno."'";
        $cardIf=$this->model->execute($cardSql);
        if($cardAlIf==true&&$cardIf==true){
            $this->model->commit();

            //---------向一家传输用户通宝信息--17:21----
            $de = new YjDes();
            $customid = $this->getCustomid($cardno);//用户会员编号
            $tb_info = D("Ehome")->getTbinfo($customid);
            if($tb_info){
                $appid  = 'SOON-ZZUN-0001';
                $tb_info['customid'] = encode($tb_info['customid']);
                $tb_info['activetype'] = 2;
                $tb_info['appid'] = $appid;
                $tb_sign = $de->encrypt($tb_info);
                $tb_data = json_encode($tb_info,JSON_FORCE_OBJECT);
                $return_yj = $this->curlPost(C('ehome_potb'),$tb_data,$tb_sign);
                $return_arr = json_decode($return_yj,1);

                if($return_arr['code'] == '100'){
                    $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","success");
                }else{
                    $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","failed");
                }

            }

            //---------end-----------------

            returnMsg(array('status'=>'1','codemsg'=>'激活成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'激活失败'));
        }
    }

    public function getTbCardList(){
        $data=getPostJson();
        $this->recordData($data);
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'非法秘钥'));
        }
        $customid=decode($customid);
        if($customid==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法会员编号'));
        }
        $map=array('customid'=>$customid);
        $custom=$this->customs->where($map)->field('customid')->find();
        if($custom==false){
            returnMsg(array('status'=>'02','codemsg'=>'会员不存在'));
        }
        $map1=array('cu.customid'=>$customid,'c.cardkind'=>'6889','c.cardfee'=>1);
        $cardList=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->field('c.cardno,c.status')->where($map1)->select();
        if($cardList==false){
            returnMsg(array('status'=>'03','codemsg'=>'无通宝卡'));
        }else{
            returnMsg(array('status'=>'1','list'=>$cardList));
        }
    }

    public function editCoinData(){
        $data=getPostJson();
        //$this->recordData($data);
        //$data=json_decode('{"name":"\u8d75\u6625\u548c","personid":"412823195503040012","linktel":"13503961096","oldcontractid":"SP-SLBD-2-D-27-134","newcontractid":"SP-SLBD-2-D-27-134"}',1);
        $name=trim($data['name']);
        $personid=trim($data['personid']);
        $linktel=trim($data['linktel']);
        $oldcontractid=trim($data['oldcontractid']);
        $newcontractid=trim($data['newcontractid']);
        if(empty($oldcontractid)){
            returnMsg(array('status'=>'01','codemsg'=>'请输入旧合同编号'));
        }
        if(empty($name)&&empty($personid)&&empty($linktel)&&empty($newcontractid)){
            returnMsg(array('status'=>'02','codemsg'=>'传入数据有误'));
        }
        $where['ca.sourceorder']='fzg_'.$oldcontractid;
        $field="cu.customid,ca.coinid,ca.cardid";
        $model=new model();
        $model->startTrans();
        $list=$model->table('coin_account')->alias('ca')->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->find();
        if($list==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无此通宝发行记录'));
        }
        if(!empty($name)){
            $nameSql="update customs set namechinese='{$name}' where customid='{$list['customid']}'";
            $nameIf=$model->execute($nameSql);
        }else{
            $nameIf=true;
        }
        if(!empty($personid)){
            $personidSql="update customs set personid='{$personid}' where customid='{$list['customid']}'";
            $personidIf=$model->execute($personidSql);
        }else{
            $personidIf=true;
        }
        if(!empty($linktel)){
            $linktelSql="update customs set linktel='{$linktel}' where customid='{$list['customid']}'";
            $linktelIf=$model->execute($linktelSql);
        }else{
            $linktelIf=true;
        }
        if(!empty($newcontractid)){
            $contractSql="update coin_account set sourceorder='{$newcontractid}' where coinid='{$list['customid']}' and cardid='{$list['cardid']}'";
            $contractIf=$model->execute($contractSql);
        }else{
            $contractIf=true;
        }
        if($nameIf=true&&$personidIf=true&&$linktelIf=true&&$contractIf=true){
            $model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            $model->rollback();
            returnMsg(array('status'=>'04','codemsg'=>'修改失败'));
        }
    }

    public function editFangzgData(){
        $data=getPostJson();
        //$data=json_decode('{"status":1,"name":"\u6c6a\u5728\u946b2","personid":"411503199603295034","linktel":"18511018945","tradeid":"20160427184700_2076","amount":"20000.00"}',1);
        $name=trim($data['name']);
        $personid=trim($data['personid']);
        $linktel=trim($data['linktel']);
        $amount=trim($data['amount']);
        $tradeid=trim($data['tradeid']);
        $status=trim($data['status']);
        $statusArr=array(1,2);
        if(empty($tradeid)){
            returnMsg(array('status'=>'01','codemsg'=>'订单编号必填'));
        }
        if(empty($status)||!in_array($status,$statusArr)){
            returnMsg(array('status'=>'02','codemsg'=>'非法操作方式'));
        }
        if($status==1){
            if(empty($name)&&empty($personid)&&empty($linktel)&&empty($amount)){
                returnMsg(array('status'=>'03','codemsg'=>'传入数据有误'));
            }
        }
        $where['tradeid']=$tradeid;
        $model=new model();
        $model->startTrans();
        $list=$model->table('fangzg')->where($where)->find();
        if($list==false){
            returnMsg(array('status'=>'04','codemsg'=>'订单未同步'));
        }
        if($status==1){
            if(!empty($name)){
                $data['name']=$name;
                $data1['namechinese']=$name;
            }
            if(!empty($personid)){
                $data['personid']=$personid;
                $data1['personid']=$personid;
            }
            if(!empty($linktel)){
                $data['linktel']=$linktel;
                $data1['linktel']=$linktel;
            }
            if(!empty($amount)){
                $data['amount']=$amount;
            }
            if($list['status']!=0&&!empty($data1)){
                $custom=$model->table('fangzg')->alias('f')
                    ->join('fangzg_c fc on fc.tradeid=f.tradeid')
                    ->join('cards c on c.cardno=fc.tradeid')
                    ->join('customs_c cc on cc.cid=c.customid')
                    ->field('cc.customid')->where(array('f.tradeid'=>$tradeid))
                    ->select();
                $map1=array('customid'=>$custom[0]['customid']);
                $this->model->where($map1)->save($data1);
            }
            $map=array('tradeid'=>$tradeid);
            if($model->table('fangzg')->where($map)->save($data)){
                returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
            }else{
                returnMsg(array('status'=>'06','codemsg'=>'修改失败'));
            }
        }elseif($status==2){
            if($list['status']!=0){
                returnMsg(array('status'=>'05','codemsg'=>'订单已充值不能删除'));
            }
            $sql="delete from fangzg where tradeid='{$tradeid}'";
            if($model->execute($sql)){
                returnMsg(array('status'=>'01','codemsg'=>'删除成功'));
            }else{
                returnMsg(array('status'=>'06','codemsg'=>'删除失败'));
            }
        }
    }
    private function curlPost($url,$data,$sign){
        if(!$url){
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type:application/json',"sign:{$sign}"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);
        $output = curl_exec($ch);
        if($output == false){
            return curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }

    public function eCardIssue(){
        $data=getPostJson();
        $this->recordData(json_encode($data));
        //$data=json_decode('{"customid":"MDAxMjc0NTIO0O0O","coinAmount":"162.69","name":"\u9ad8\u79cb\u5e73","personid":"41272819891105427X","orderAmount":"16269.00","mobile":"15890620404","panterid":"00000360","orderid":"Sa-SLBD-2-D-27-137","sourceCode":"1003","info":{"homeinfo":"\u9042\u5e73\u68ee\u6797\u534a\u5c9b-\u4e8c\u671f-27\u53f7\u697c\u5730\u4e0b\u50a8\u85cf\u5ba4-137","idcardno":"41272819891105427X","orgid":"178","mobilephone":"15890620404","uname":"\u9ad8\u79cb\u5e73","customid":"MDAxMjc0NTIO0O0O"}}',1);
        $customid=trim($data['customid']);
        $coinAmount=trim($data['coinAmount']);
        $namechinese=trim($data['name']);
        $linktel=trim($data['mobile']);
        $personid=trim($data['personid']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $info=trim($data['info']);
        $sourceCode=trim($data['sourceCode']);
        $prefix=$this->panterArr[$sourceCode]['prefix'];
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'01','codemsg'=>'无效商户号'));
        }
        if(trim($customid)==''){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
        }
        if(!preg_match('/^\d+$/',$customid)){
            $customid=decode($customid);
            if(empty($customid)){
                returnMsg(array('status'=>'03','codemsg'=>'无效会员编号'));
            }
        }
        $custom=$this->customs->where(array('customid'=>$customid))->find();
        if($custom==false){
            returnMsg(array('status'=>'04','codemsg'=>'查无此会员'));
        }

        $accountMap=array('sourceorder'=>$prefix.$orderid);
        $issueCounter=M('coin_account')->where($accountMap)->count();
        if($issueCounter>0){
            returnMsg(array('status'=>'05','codemsg'=>'该订单已充值'));
        }
        $map=array('cu.customid'=>$customid,'c.cardkind'=>'6889','c.cardfee'=>2);
        $ecard=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($map)->find();
        $model=new Model();
        if($ecard==false){
            $map1=array('cardkind'=>'6889','cardfee'=>2,'status'=>'N');
            $cards=$this->cards->where($map1)->field('cardno')->select();
            $c=$this->cards->where($map1)->count();
            if($c==0){
                returnMsg(array('status'=>'06','codemsg'=>'卡池数量不足'));
            }
            $rand=mt_rand(0,$c-1);
            $cardno=$cards[$rand]['cardno'];
            $sql="update cards set status='D' where cardno='{$cardno}'";
            $model->execute($sql);
            $model->startTrans();
            $openCardArr=array($cardno);
            $this->recordData(json_encode(array('customid'=>$customid,'cardno'=>$cardno)),1);
            $bool=$this->openCard($openCardArr,$customid,0,'',1,'','',1);
            if($bool==false){
                $model->rollback();
                returnMsg(array('status'=>'07','codemsg'=>'新卡开卡失败'));
            }
            $ecardTime = date('Y-m-d H:i:s');
            $sql="insert into ecard_bind values ('{$cardno}','{$customid}',1,'00000286','{$ecardTime}','{$customid}')";
            $model->execute($sql);

            if(empty($custom['personid'])&&empty($custom['namechinese'])){
                $customsSql="update customs set personid='{$personid}',namechinese='{$namechinese}' where customid='{$customid}'";
                $model->execute($customsSql);
            }
        }else{
            $model->startTrans();
            $cardno=$ecard['cardno'];
        }
        $accountInfo=$this->getCoinAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'08','codemsg'=>'卡号账户异常'));
        }
        $cardInfo=array('customid'=>$customid,'cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],'accountid'=>$accountInfo['accountid'],'orderid'=>$prefix.$orderid);
        $coinBool=$this->coinRecharge($cardInfo,$coinAmount,$panterid);
        if($coinBool==true){
//            foreach($cardArr as $k=>$v){
//                if($v==$cardno){
//                    unset($cardArr[$k]);
//                }
//            }
//            S('cardArr',$cardArr);
            $model->commit();
            if($custom['linktel']==$linktel){
                $this->pushFangInfo($data['info']);
            }else{
                $data['info']['mobilephone']=$custom['linktel'];
                $data['info']['tongbaomobile']=$linktel;
                $this->pushFangInfo($data['info']);
            }
            returnMsg(array('status'=>'1','codemsg'=>'充值成功','customid'=>$customid,'cardno'=>$cardno));
        }else{
            $model->rollback();
            returnMsg(array('status'=>'09','codemsg'=>'建业通宝充值失败'));
        }
    }

    public function pushFangInfo($datainfo){
        //$datainfo = json_decode($info,true);
        //print_r($datainfo);exit;
        $appid  = 'SOON-ZZUN-0001';
        $homeinfo = $datainfo['homeinfo'];//项目名称-分期-楼栋号-单元号(可能没有)-房号
        $homearray = explode('-',$homeinfo);
        $orgid = $datainfo['orgid'];//小区id
        if(count($homearray) == 6){
            $building = $homearray[3];//楼栋号
            $unint = $homearray[4];//单元号
            $housenum = $homearray[5];//房间号
        }elseif(count($homearray) == 5){
            $building = $homearray[2];//楼栋号
            $unint = $homearray[3];//单元号
            $housenum = $homearray[4];//房间号
        }elseif(count($homearray) == 4){
            $building = $homearray[2];//楼栋号
            $unint = '';//单元号
            $housenum = $homearray[3];//房间号
        }elseif(count($homearray) == 3){
            $building = $homearray[1];
            $unint = '';
            $housenum = $homearray[2];
        }else{
            $building = '';//楼栋号
            $unint = '';//单元号
            $housenum = '';//房间号
        }
        $idcardno = $datainfo['idcardno'];//用户身份证号
        $mobilephone = $datainfo['mobilephone'];//用户手机号
        $uname = $datainfo['uname'];//用户名字
        if(preg_match('/^\d+$/',$datainfo['customid'])){
            $customid=encode($datainfo['customid']);
            $customid1=$datainfo['customid'];
        }else{
            $customid=$datainfo['customid'];
            $customid1=decode($datainfo['customid']);
        }
        $data = array('appid'=>$appid,
            'orgid'=>$orgid,
            'building'=>$building,
            'unint'=>$unint,
            'housenum'=>$housenum,
            'mobilephone'=>$mobilephone,
            'password'=>'123456',
            'uname'=>$uname,
            'idcardno'=>$idcardno,
            'customid'=>$customid,
            'tongbaomobile'=>$datainfo['tongbaomobile']
        );
        $de = new YjDes();
        $sign = $de->encrypt($data);
        $data = json_encode($data,JSON_FORCE_OBJECT);
        $this->recordData($data);


        $url=C('ecardIssue').'/app/user/tongbaoactive.user';
        //$url='http://ysco2o.yijiahn.com/jyo2o_web/app/user/tongbaoactive.user';
        $url2=C('ecardIssue').'/app/user/tongbao/syncinfo.user';

        $de = new YjDes();
        $tb_info = D("Ehome")->getTbinfo($customid1);
        if($tb_info){
            $appid  = 'SOON-ZZUN-0001';
            $tb_info['customid'] = encode($tb_info['customid']);
            $tb_info['activetype'] = 2;
            $tb_info['appid'] = $appid;
            $tb_sign = $de->encrypt($tb_info);
            $tb_data = json_encode($tb_info,JSON_FORCE_OBJECT);

            $this->recordData($tb_data);
            $return_yj = $this->curlPost($url2,$tb_data,$tb_sign);
            $return_arr = json_decode($return_yj,1);

            if($return_arr['code'] == '100'){
                $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","success");
            }else{
                $this->recordError(date("H:i:s").'-'.$tb_data.'-'.$return_yj."\n\t","YjTbpost","failed");
            }
        }
        $result = $this->curlPost($url,$data,$sign);
        $resultinfo = json_decode($result,true);
        //print_r($resultinfo);
        if($resultinfo['code']=='100'){
            $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","success");
        }elseif($resultinfo['code']=='500'){
            $customid=$resultinfo['msg'];
            $customid=decode($customid);
            $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","registed");
        }elseif($resultinfo['code']=='501'){
            $this->recordError(date("H:i:s").'-'.$data.'-'.$result."\n\t","YjRegist","registed");
        }else{
            $this->recordError(date("H:i:s").'-'.$data.'-'.$result.'-'.json_encode($datainfo)."\n\t","YjRegist","error");
        }
    }
}