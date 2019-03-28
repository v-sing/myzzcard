<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class CposPointController extends CoinController{

    //查询账户信息接口(通过会员卡)
    public function getAccount(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $panterid=trim($data['panterid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$panterid);
        $string=$cardno.'--'.$key.'--'.$panterid;
        $this->recordData($string);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }
		
		if (strlen($cardno) > 19){
			$cardno = explode('=',$cardno);
			$cardno = $cardno[0];
		}
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法卡号'));
        }
        $customid = $this->getCustomid($cardno);
        if($customid == false || empty($customid)){
            returnMsg(array('status'=>'04','codemsg'=>'此卡没有关联用户'));
        }
        $balance=$this->accountQuery($customid,'00',$cardno);
        $pointAmount=$this->getPointByCustomid($customid,$panterid,$cardno);
        //$pointAmount=$this->getPointByCardno($cardno,$panterid);
        //--------end
        $list=$this->checkPointSendtype($cardno,$panterid);
        if($list==false){
            $flag=0;
            $czrate='';
        }else{
            $flag=1;
            $czrate=$list['zsrate'];
        }
        returnMsg(
            array(
                'status'=>'1','balance'=>floatval($balance),
                'point'=>floatval($pointAmount), 'customid'=>$customid,
                'time'=>time(),'flag'=>$flag,'czrate'=>floatval($czrate)
            )
        );
    }

    //至尊卡至尊积分充值
    public function pointRecharge(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $pointAmount=trim($data['amount']);
        $panterid=trim($data['panterid']);
        $key=trim($data['key']);
        $checkkey=md5($this->keycode.$cardno.$pointAmount.$panterid);
        if($checkkey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
//        $cardno='6883379931341060611';
//        $panterid='00000012';
//        $pointAmount=10;
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }
        $map1=array('panterid'=>$panterid);
        $point_config=M('point_config')->where($map1)->find();
        if($point_config==false){
            returnMsg(array('status'=>'03','codemsg'=>'商户未配置积分'));
        }
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'04','codemsg'=>'无效卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'05','codemsg'=>'非正常卡'));
        }
        $accountInfo=$this->getpointAccount($cardno);
        if($accountInfo['accountid']==false||$accountInfo['cardid']==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡号账户异常'));
        }
        $cardInfo=array('cardno'=>$cardno,'cardid'=>$accountInfo['cardid'],
            'accountid'=>$accountInfo['accountid'],'type'=>$point_config['type'],'validity'=>$point_config['validity']);
        $pointBool=$this->pointRechargeExe($cardInfo,$pointAmount,$panterid);
        if($pointBool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'积分充值成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'积分充值失败'));
        }
    }
	
	
	
	
    public function mys_consume(){
		$string = '111';
		$this->recordData($string);
		echo 11;exit;
		
        $data=getPostJson();
        $cardno = trim($data['cardno']);
        $balanceAmount = trim($data['balanceAmount']);
        $pointAmount = trim($data['pointAmount']);
        $cardpwd = trim($data['cardpwd']);
        $key = trim($data['key']);
        $panterid = trim($data['panterid']);//商户号
        $termposno = trim($data['termposno']);//终端号
        $posFlagId=trim($data['posFlagId']);
        $isLast=trim($data['isLast']);

        $string=$cardno.'--'.$balanceAmount.'--'.$pointAmount.'--'.$cardpwd.'--'.$key.'--'.$panterid.'--'.$termposno.'--'.$posFlagId.$isLast;
//        $cardno='6688371800000000001';
//        $panterid='00000270';
//        $balanceAmount=10.00;
//        $pointAmount=2.0;
//        $cardpwd='BFD0C613A11BF88E';
//        $termposno='27000348';
//        $posFlagId='de7019df-0a44-4f45-90ff-0ce34ce50b2f';
//        $key='ab7d68dae6039d6b0ecaa995fab317d5';

        $this->recordData($string);

        $checkKey=md5($this->keycode.$cardno.$balanceAmount.$pointAmount.$cardpwd.$panterid.$termposno.$posFlagId.$isLast);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->checkPanter($panterid) || returnMSg(array('status'=>'02','codemsg'=>'商户不存在！'));

        empty($cardpwd) && returnMSg(array('status'=>'03','codemsg'=>'卡密码为空'));
        if(empty($cardno)){
            returnMsg(array('status'=>'04','codemsg'=>'卡号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            returnMsg(array('status'=>'05','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$pointAmount)){
            returnMsg(array('status'=>'06','codemsg'=>'积分格式有误'));
        }
        if($balanceAmount==0&&$pointAmount==0){
            returnMsg(array('status'=>'07','codemsg'=>'消费数据有误'));
        }
        if($balanceAmount>0){
//            $limitBool=$this->checkTradeLimited($cardno,$balanceAmount,$panterid);
//            if($limitBool==false){
//                returnMsg(array('status'=>'08','codemsg'=>'消费受限'));
//            }
            if(empty($posFlagId)){
                returnMsg(array('status'=>'09','codemsg'=>'pos单号缺失'));
            }
        }

        $map = array('c.cardno'=>$cardno);
        $card = $this->cards->alias('c')->join('left join panters p on p.panterid=c.panterid')
            ->where($map)->field('c.customid,c.cardpassword,c.status,c.dcflag,p.namechinese pname')->find();
        if($card == false){
            returnMsg(array('status'=>'010','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            if($card['status']=='G'){
                returnMsg(array('status'=>'011','codemsg'=>'卡已被锁定'));
            }else{
                returnMsg(array('status'=>'012','codemsg'=>'非正常卡号'));
            }
        }
        if($card['cardpassword']!=$cardpwd){
            if($card['cardflag']<2){
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1 WHERE CARDNO='{$cardno}'";
            }else{
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1,status='G' WHERE CARDNO='{$cardno}'";
            }
            $this->cards->execute($sql);
            returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
        }
        $customid=$this->getCustomid($cardno);
        if($customid==false){
            returnMsg(array('status'=>'08','codemsg'=>'此卡未关联会员'));
        }
        //$balance=$this->cardAccQuery($cardno);
        $balanceAccount=$this->accountQuery($customid,'00',$cardno);
        $pointAccount=$this->getPointByCustomid($customid,$panterid,$cardno);

        if($isLast==1){
            $balanceAmount=$balanceAccount;
            $pointAmount=$pointAccount;
        }else{
            if($balanceAccount<$balanceAmount){
                returnMsg(array('status'=>'014','codemsg'=>'账户金额不足'));
            }
            if($pointAccount<$pointAmount){
                returnMsg(array('status'=>'015','codemsg'=>'积分余额不足'));
            }
        }
        // $coinAccount=$this->accountQuery($customid,'01');
        //$pointAccount=$this->getPointByCardno($cardno,$panterid);

        $this->model->startTrans();
        $balanceConsumeIf=$pointConsumeIf=false;
        //var_dump($balanceConsumeIf);exit;
        //余额消费，金额为0不执行
        if($balanceAmount>0){
//            $limitIf=$this->checkPanterLimit($card['panterid'],$panterid);
//            if($limitIf=='01'){
//                returnMsg(array('status'=>'017','codemsg'=>'非酒店卡不能在酒店消费'));
//            }elseif($limitIf=='02'){
//                returnMsg(array('status'=>'018','codemsg'=>'酒店卡不能再在非酒店商户消费'));
//            }
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,
                'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,
                'termno'=>$termposno,'cardno'=>$cardno,'tradetype'=>'00');
            //现金订单列表
            //$balanceConsumeIf=$this->consumeExeByCardno($balanceConsumeArr);
            $balanceConsumeIf=$this->consumeExe1($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }
        //积分消费，金额为0不执行
        if($pointAmount>0){
            $pointConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,'tradetype'=>'01',
                'panterid'=>$panterid,'amount'=>$pointAmount, 'termno'=>$termposno,'customid'=>$customid);
            //积分消费订单列表，二维数组
            //$pointConsumeIf=$this->pointExeByCardno($pointConsumeArr);
            $pointConsumeIf=$this->pointExeByCustomid($pointConsumeArr);
            //$coinConsumeIf = array_one($coinConsumeIf);
        }else{
            $pointConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$pointConsumeIf==true){
            $this->model->commit();
            $leftBalance=(string)$this->accountQuery($customid,'00',$cardno);
            //$leftPoint = (string)($this->getPointByCardno($cardno,$panterid));
            $leftPoint=$this->getPointByCustomid($customid,$panterid,$cardno);
            $info=array(
                'reduceBalance'=>floatval($balanceAmount),
                'reducePoint'=>floatval($pointAmount),
                'pointtradidlist'=>$pointConsumeIf,
                'leftBalance'=>$leftBalance,
                'leftPoint'=>$leftPoint,
                'time'=>time(),
                'pname'=>$card['pname']
            );
            if($this->checkCardBrand($cardno)==true){
                $info['cashtradidlist']=$balanceConsumeIf['tradeArr'];
                if(!empty($balanceConsumeIf['billedAmount'])){
                    $info['billedAmount']=$balanceConsumeIf['billedAmount'];
                    $info['unBillAmount']=floatval($balanceAmount-$balanceConsumeIf['billedAmount']);
                }
                $customInfo=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                    ->join('customs cu on cu.customid=cc.customid')
                    ->join('panters p on c.panterid=p.panterid')
                    ->field('cu.namechinese,cu.linktel,p.namechinese pname')
                    ->where(array('c.cardno'=>$cardno))->find();
                //echo $customInfo['linktel'];exit;
                $info['pname']=$customInfo['pname'];
                if(!empty($customInfo['linktel'])){
                    $panter=M('panters')->where(array('panterid'=>$panterid))->field('namechinese pname')->find();
                    $amount=floatval($balanceAmount)+floatval($pointAmount);
                    $tpl_value="#name#=".urlencode("{$customInfo['namechinese']}")."&#content#=".urlencode("{$panter['pname']}")."&#amount#={$amount}";
                    $tpl_value.="&#balanceAmount#=".floatval($balanceAmount)."&#pointAmount#=".floatval($pointAmount);
                    $tpl_value.="&#remindAmount#=".floatval($leftBalance)."&#remindPoint#=".floatval($leftPoint);
                    $result = $this->tpl_send("1484467",$customInfo['linktel'],$tpl_value);
                    $result=json_decode($result,1);
                }
            }else{
                $info['cashtradidlist']=$balanceConsumeIf;
            }
            returnMsg(array('status'=>'1', 'info'=>$info));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'016','codemsg'=>'消费扣款失败'));
        }
    }
	
	
	//至尊卡积分消费
    public function consume(){
        $data=getPostJson();
        $cardno = trim($data['cardno']);
        $balanceAmount = trim($data['balanceAmount']);
        $pointAmount = trim($data['pointAmount']);
        $cardpwd = trim($data['cardpwd']);
        $key = trim($data['key']);
        $panterid = trim($data['panterid']);//商户号
        $termposno = trim($data['termposno']);//终端号
        $posFlagId=trim($data['posFlagId']);
        $isLast=trim($data['isLast']);

        $string=$cardno.'--'.$balanceAmount.'--'.$pointAmount.'--'.$cardpwd.'--'.$key.'--'.$panterid.'--'.$termposno.'--'.$posFlagId.$isLast;
//        $cardno='6688371800000000001';
//        $panterid='00000270';
//        $balanceAmount=10.00;
//        $pointAmount=2.0;
//        $cardpwd='BFD0C613A11BF88E';
//        $termposno='27000348';
//        $posFlagId='de7019df-0a44-4f45-90ff-0ce34ce50b2f';
//        $key='ab7d68dae6039d6b0ecaa995fab317d5';

        $this->recordData($string);

        $checkKey=md5($this->keycode.$cardno.$balanceAmount.$pointAmount.$cardpwd.$panterid.$termposno.$posFlagId.$isLast);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->checkPanter($panterid) || returnMSg(array('status'=>'02','codemsg'=>'商户不存在！'));

        empty($cardpwd) && returnMSg(array('status'=>'03','codemsg'=>'卡密码为空'));
        if(empty($cardno)){
            returnMsg(array('status'=>'04','codemsg'=>'卡号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$balanceAmount)){
            returnMsg(array('status'=>'05','codemsg'=>'消费金额格式有误'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$pointAmount)){
            returnMsg(array('status'=>'06','codemsg'=>'积分格式有误'));
        }
        if($balanceAmount==0&&$pointAmount==0){
            returnMsg(array('status'=>'07','codemsg'=>'消费数据有误'));
        }
        if($balanceAmount>0){
//            $limitBool=$this->checkTradeLimited($cardno,$balanceAmount,$panterid);
//            if($limitBool==false){
//                returnMsg(array('status'=>'08','codemsg'=>'消费受限'));
//            }
            if(empty($posFlagId)){
                returnMsg(array('status'=>'09','codemsg'=>'pos单号缺失'));
            }
        }
		if (strlen($cardno) > 19){
			$cardno = explode('=',$cardno);
			$cardno = $cardno[0];
		}
        $map = array('c.cardno'=>$cardno);
        $card = $this->cards->alias('c')->join('left join panters p on p.panterid=c.panterid')
            ->where($map)->field('c.customid,c.cardpassword,c.status,c.dcflag,p.namechinese pname')->find();
        if($card == false){
            returnMsg(array('status'=>'010','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            if($card['status']=='G'){
                returnMsg(array('status'=>'011','codemsg'=>'卡已被锁定'));
            }else{
                returnMsg(array('status'=>'012','codemsg'=>'非正常卡号'));
            }
        }
        if($card['cardpassword']!=$cardpwd){
            if($card['cardflag']<2){
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1 WHERE CARDNO='{$cardno}'";
            }else{
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1,status='G' WHERE CARDNO='{$cardno}'";
            }
            $this->cards->execute($sql);
            returnMsg(array('status'=>'013','codemsg'=>'卡号密码错误'));
        }
        $customid=$this->getCustomid($cardno);
        if($customid==false){
            returnMsg(array('status'=>'08','codemsg'=>'此卡未关联会员'));
        }
        //$balance=$this->cardAccQuery($cardno);
        $balanceAccount=$this->accountQuery($customid,'00',$cardno);
        $pointAccount=$this->getPointByCustomid($customid,$panterid,$cardno);

        if($isLast==1){
            $balanceAmount=$balanceAccount;
            $pointAmount=$pointAccount;
        }else{
            if($balanceAccount<$balanceAmount){
                returnMsg(array('status'=>'014','codemsg'=>'账户金额不足'));
            }
            if($pointAccount<$pointAmount){
                returnMsg(array('status'=>'015','codemsg'=>'积分余额不足'));
            }
        }
        // $coinAccount=$this->accountQuery($customid,'01');
        //$pointAccount=$this->getPointByCardno($cardno,$panterid);

        $this->model->startTrans();
        $balanceConsumeIf=$pointConsumeIf=false;
        //var_dump($balanceConsumeIf);exit;
        //余额消费，金额为0不执行
        if($balanceAmount>0){
//            $limitIf=$this->checkPanterLimit($card['panterid'],$panterid);
//            if($limitIf=='01'){
//                returnMsg(array('status'=>'017','codemsg'=>'非酒店卡不能在酒店消费'));
//            }elseif($limitIf=='02'){
//                returnMsg(array('status'=>'018','codemsg'=>'酒店卡不能再在非酒店商户消费'));
//            }
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,
                'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,
                'termno'=>$termposno,'cardno'=>$cardno,'tradetype'=>'00');
            //现金订单列表
            //$balanceConsumeIf=$this->consumeExeByCardno($balanceConsumeArr);
            $balanceConsumeIf=$this->consumeExe1($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }
        //积分消费，金额为0不执行
        if($pointAmount>0){
            $pointConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,'tradetype'=>'01',
                'panterid'=>$panterid,'amount'=>$pointAmount, 'termno'=>$termposno,'customid'=>$customid);
            //积分消费订单列表，二维数组
            //$pointConsumeIf=$this->pointExeByCardno($pointConsumeArr);
            $pointConsumeIf=$this->pointExeByCustomid($pointConsumeArr);
            //$coinConsumeIf = array_one($coinConsumeIf);
        }else{
            $pointConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$pointConsumeIf==true){
            $this->model->commit();
            $leftBalance=(string)$this->accountQuery($customid,'00',$cardno);
            //$leftPoint = (string)($this->getPointByCardno($cardno,$panterid));
            $leftPoint=$this->getPointByCustomid($customid,$panterid,$cardno);
            $info=array(
                'reduceBalance'=>floatval($balanceAmount),
                'reducePoint'=>floatval($pointAmount),
                'pointtradidlist'=>$pointConsumeIf,
                'leftBalance'=>$leftBalance,
                'leftPoint'=>$leftPoint,
                'time'=>time(),
                'pname'=>$card['pname']
            );
            if($this->checkCardBrand($cardno)==true){
                $info['cashtradidlist']=$balanceConsumeIf['tradeArr'];
                if(!empty($balanceConsumeIf['billedAmount'])){
                    $info['billedAmount']=$balanceConsumeIf['billedAmount'];
                    $info['unBillAmount']=floatval($balanceAmount-$balanceConsumeIf['billedAmount']);
                }
                $customInfo=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                    ->join('customs cu on cu.customid=cc.customid')
                    ->join('panters p on c.panterid=p.panterid')
                    ->field('cu.namechinese,cu.linktel,p.namechinese pname')
                    ->where(array('c.cardno'=>$cardno))->find();
                //echo $customInfo['linktel'];exit;
                $info['pname']=$customInfo['pname'];
                if(!empty($customInfo['linktel'])){
                    $panter=M('panters')->where(array('panterid'=>$panterid))->field('namechinese pname')->find();
                    $amount=floatval($balanceAmount)+floatval($pointAmount);
                    $tpl_value="#name#=".urlencode("{$customInfo['namechinese']}")."&#content#=".urlencode("{$panter['pname']}")."&#amount#={$amount}";
                    $tpl_value.="&#balanceAmount#=".floatval($balanceAmount)."&#pointAmount#=".floatval($pointAmount);
                    $tpl_value.="&#remindAmount#=".floatval($leftBalance)."&#remindPoint#=".floatval($leftPoint);
                    $result = $this->tpl_send("1484467",$customInfo['linktel'],$tpl_value);
                    $result=json_decode($result,1);
                }
            }else{
                $info['cashtradidlist']=$balanceConsumeIf;
            }
            returnMsg(array('status'=>'1', 'info'=>$info));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'016','codemsg'=>'消费扣款失败'));
        }
    }

    //获取商户积分配置
    public function getPointRate(){
        $data=getPostJson();
        $panterid=trim($data['panterid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$panterid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$panterid='00000012';
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效商户号'));
        }
        $map=array('panterid'=>$panterid);
        $point_config=M('point_config')->where($map)->field('zsrate')->find();
        if($point_config==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无信息'));
        }else{
            returnMsg(array('status'=>'1','zsrate'=>floatval($point_config['zsrate']),'sendtype'=>$point_config['sendtype']));
        }
    }

    //获取消费记录,未使用
    public function getConsumeRecord(){
        $data=getPostJson();
        $panterid = trim($data['panterid']);
        $termposno = trim($data['termposno']);
        $key = trim($data['key']);
        $checkKey=md5($this->keycode.$panterid.$termposno);
        if($checkKey!=$key){
            returnMSg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMSg(array('status'=>'02','codemsg'=>'商户不存在！'));
        }
        $jytype=array('00'=>'至尊卡消费','01'=>'至尊积分消费');
        $currentDate=date('Ymd');
        $deadDate=date('Ymd',strtotime('-7days'));
        $map=array('panterid'=>$panterid,'termposno'=>$termposno);
        $map['tradetype']=array('in','00,01');
        $map['flag']=0;
        $map['placeddate']=array(array('egt',$deadDate),array('elt',$currentDate));
        $field='tradeid,placeddate,placedtime,tradeamount,tradepoint,cardno,tradetype,quanid';
        $tradeList=M('trade_wastebooks')->where($map)->field($field)->select();
        if($tradeList==false){
            returnMSg(array('status'=>'03','codemsg'=>'查无消费记录！'));
        }else{
            $list=array();
            foreach($tradeList as $key=>$val){
                $list[$key]['tradeid']=$val['tradeid'];
                $list[$key]['cardno']=$val['cardno'];
                $list[$key]['date']=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'];
                $list[$key]['tradetype']=$jytype[$val['tradetype']];
                $list[$key]['amount']=floatval($val['tradeamount']);
            }
            returnMSg(array('status'=>'1','list'=>$list));
        }
    }

    //开发票
    public function invoice(){
        $data=getPostJson();
        //$data='{"panterid":"00000270","tradeids":"[\"000620160719181016\"]","key":"e9b767daf0b53374a8127866b39a105b","cardno":"6688371800000000001"}';
        //$data=json_decode($data,1);
        $tradeids = trim($data['tradeids']);
        $cardno = trim($data['cardno']);
        $panterid=trim($data['panterid']);
        if(empty($tradeids)){
            returnMsg(array('status'=>'01','msg'=>'交易流水号不能没空'));
        }
        $tradeArr=json_decode($tradeids);
        $trade_wastebooks=M('trade_wastebooks');
        $tradeBill=M('tradebilling');
        $billingDetail=M('billingdetail');
        $res=array();
        foreach($tradeArr as $key=>$val){
            $tradeid=$val;
            $map=array('tradeid'=>$tradeid);
            $list1=$tradeBill->where($map)->find();
            if($list1['status']==1){
                $res[]=array('tradeid'=>$tradeid,'status'=>'05','msg'=>'该订单流水发票已开');
                continue;
            }
            $list=$trade_wastebooks->where($map)->find();
            $billedAccount=$billingDetail->where($map)->sum('amount');
            if(empty($billedAccount)) $billedAccount=0;
            if($list['tradetype']!='00') continue;
            $cardno=$list['cardno'];
            $amount=$list['tradeamount']-$billedAccount;
            if($this->checkCardBrand($cardno)==false) continue;
            if($amount==0) continue;
            if($panterid!=$list['panterid']){
                $res[$tradeid]=array('tradeid'=>$tradeid,'status'=>'02','msg'=>'订单商户与pos商户不一致');
                continue;
            }
            $usableAmount=$this->checkBillAccount($cardno,$panterid);
            if($amount>$usableAmount){
                $res[]=array('tradeid'=>$tradeid,'status'=>'03','msg'=>'发票金额超出可用发票金额');
            }
            $usableList=$this->getUsableBillList($cardno,$panterid);
            $this->model->startTrans();
            $bool=$this->billingExe($usableList,$amount,$tradeid);
            if($bool==true){
                $this->model->commit();
                $res[]=array('tradeid'=>$tradeid,'status'=>'1','msg'=>'开发票成功');
            }else{
                $this->model->rollback();
                $res[]=array('tradeid'=>$tradeid,'status'=>'04','msg'=>'开发票失败');
            }
        }
        returnMSg($res);
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

    //预授权接口
    public function preAuthorization(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $termposno=trim($data['termposno']);
        $panterid=trim($data['panterid']);
        $posFlagId=trim($data['posFlagId']);
        $tradetype = trim($data['tradetype']);
        $amount = trim($data['amount']);
        $cardpwd = trim($data['cardpwd']);
        $key = trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$termposno.$panterid.$posFlagId.$tradetype.$amount.$cardpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }

//        $cardno='6688371800000000001';
//        $termposno='27000349';
//        $panterid='00000270';
//        $posFlagId='3267c0dc-73ca-4eb4-aa3b-126f935eb5e5';
//        $tradetype = '17';
//        $amount = '12';
//        $cardpwd = 'BFD0C613A11BF88E';

        if($tradetype!='17'){
            returnMsg(array('status'=>'02','codemsg'=>'非预授权交易类型'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'消费金额格式有误'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'04','codemsg'=>'卡号缺失'));
        }
        if($this->checkCardBrand($cardno)==false){
            returnMsg(array('status'=>'05','codemsg'=>'非酒店新卡不能再次做预授权'));
        }
        $map = array('c.cardno'=>$cardno);
        $card = $this->cards->alias('c')->join('left join panters p on p.panterid=c.panterid')
            ->where($map)->field('c.customid,c.cardpassword,c.status,c.dcflag,p.namechinese pname')->find();
        if($card == false){
            returnMsg(array('status'=>'06','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            if($card['status']=='G'){
                returnMsg(array('status'=>'07','codemsg'=>'卡已被锁定'));
            }else{
                returnMsg(array('status'=>'08','codemsg'=>'非正常卡号'));
            }
        }
        if($card['cardpassword']!=$cardpwd){
            if($card['cardflag']<2){
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1 WHERE CARDNO='{$cardno}'";
            }else{
                $sql="UPDATE CARDS SET CARDFLAG=CARDFLAG+1,status='G' WHERE CARDNO='{$cardno}'";
            }
            $this->cards->execute($sql);
            returnMsg(array('status'=>'09','codemsg'=>'卡号密码错误'));
        }
        $customid=$this->getCustomid($cardno);
        if($customid==false){
            returnMsg(array('status'=>'010','codemsg'=>'此卡未关联会员'));
        }
        $list=$this->checkPointSendtype($cardno,$panterid);
        if($list==false){
            returnMsg(array('status'=>'011','codemsg'=>'商户未设置积分比例'));
        }
        $balanceAmount=floatval(round(1/(1+$list['zsrate'])*$amount,2));
        $pointAmount=floatval($amount-$balanceAmount);
        $balanceAccount=$this->accountQuery($customid,'00');
        if($balanceAccount<$balanceAmount){
            returnMsg(array('status'=>'012','codemsg'=>'账户金额不足'));
        }
        $pointAccount=$this->getPointByCustomid($customid,$panterid,$cardno);
        if($pointAccount<$pointAmount){
            returnMsg(array('status'=>'013','codemsg'=>'积分余额不足'));
        }
        $this->model->startTrans();
        $balanceConsumeIf=$pointConsumeIf=false;
        if($balanceAmount>0){
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>'pos_'.$posFlagId,
                'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,
                'termno'=>$termposno,'cardno'=>$cardno,'tradetype'=>'17');
            $balanceConsumeIf=$this->consumeExe1($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }
        if($pointAmount>0){
            $pointConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,'tradetype'=>'03',
                'panterid'=>$panterid,'amount'=>$pointAmount, 'termno'=>$termposno,'customid'=>$customid);
            $pointConsumeIf=$this->pointExeByCustomid($pointConsumeArr);
        }else{
            $pointConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$pointConsumeIf==true){
            $this->model->commit();
            $leftBalance = (string)($this->accountQuery($customid,'00',$cardno));
            $leftPoint = (string)($this->getPointByCardno($cardno,$panterid));
            $info=array(
                'reduceBalance'=>floatval($balanceAmount),
                'reducePoint'=>floatval($pointAmount),
                'tradeid'=>$pointConsumeIf[0],
                'leftBalance'=>$leftBalance,
                'leftPoint'=>$leftPoint,
                'time'=>time(),
                'pname'=>$card['pname']
            );
            returnMsg(array('status'=>'1', 'info'=>$info,'codemsg'=>'预授权成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'014','codemsg'=>'预授权失败'));
        }
    }

    //预授权撤销接口
    public function undoPreAuthorization(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $tradeid=trim($data['tradeid']);
        $tradetype = trim($data['tradetype']);
        $panterid = trim($data['panterid']);
        $termposno = trim($data['termposno']);
        $key = trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$tradeid.$tradetype.$panterid.$termposno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
//        $cardno='6688371800000000001';
//        $tradeid='000120160801163214';
//        $tradetype = '19';
//        $panterid = '00000270';
//        $termposno = '27000349';
        if($tradetype!='19'){
            returnMsg(array('status'=>'02','codemsg'=>'非预授权撤销交易类型'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
        }
        $map=array('tradeid'=>$tradeid,'panterid'=>$panterid,'flag'=>0);
        $list=$this->model->table('trade_wastebooks')->field('eorderid,panterid')->where($map)->find();
        if($list==false){
            returnMsg(array('status'=>'04','codemsg'=>'未查询到该预授权消费'));
        }
        if($list['panterid']!=$panterid){
            returnMsg(array('status'=>'05','codemsg'=>'预授权撤销商户与预授权商户不一致'));
        }
        $map1=array('eorderid'=>$list['eorderid'],'panterid'=>$panterid,'typetype'=>array('in','17,03'),'flag'=>0);
        $tradeLists=$this->model->table('trade_wastebooks')->where($map1)->select();
        if($tradeLists==false){
            returnMsg(array('status'=>'06','codemsg'=>'异常预授权消费'));
        }
        $this->model->startTrans();
        $info=array('point'=>0,'balance'=>0);
        $c=0;
        foreach($tradeLists as $key=>$val){
            if($val['tradetype']=='17'){
                $bool=$this->undoPreBalance($val,$panterid,$termposno);
                if($bool==true){
                    $info['balance']= bcadd($val['tradeamount'],$info['balance'],2);
                    $c++;
                }
            }elseif($val['tradetype']=='03'){
                $bool=$this->undoPrePoint($val,$panterid,$termposno);
                if($bool==true){
                    $info['point']= bcadd($val['tradeamount'],$info['point'],2);
                    $c++;
                }
            }
        }
        //echo $c.'--'.count($tradeLists);exit;
        if($c==count($tradeLists)){
            $this->model->commit();
            $map = array('c.cardno'=>$cardno);
            $card = $this->cards->alias('c')->join('left join panters p on p.panterid=c.panterid')
                ->where($map)->field('c.customid,c.cardpassword,c.status,c.dcflag,p.namechinese pname')->find();
            $info['pname']=$card['pname'];
            $info['time']=time();
            returnMsg(array('status'=>'1','codemsg'=>'预授权撤销成功','info'=>$info));

        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'07','codemsg'=>'预授权撤销失败'));
        }
    }
    //余额预授权撤销执行
    function undoPreBalance($tradeArr,$panterid,$termposno){
        if(empty($tradeArr)) return false;
        $undo_tradeid=$termposno.date('YmdHis');
        $placeddate=date('Ymd');
        $placedtime=date('H:i:s');
        $pretradeid=trim($tradeArr['tradeid']);

        $map=array('tradeid'=>$undo_tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $undo_tradeid=$termposno.date('YmdHis');
        }
        $sql1="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,";
        $sql1.="cardno,customid,tradetype,flag,pretradeid,eorderid) VALUES('{$termposno}','{$termposno}','{$panterid}',";
        $sql1.="'{$undo_tradeid}','{$placeddate}','{$placedtime}','{$tradeArr['tradeamount']}','{$tradeArr['cardno']}'";
        $sql1.=",'{$tradeArr['customid']}','19','0','{$pretradeid}','{$tradeArr['eorderid']}')";
        $bool1=$this->model->execute($sql1);
        //echo $sql1;exit;

        $sql2="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$tradeArr['tradeid']}'";
        $bool2=$this->model->execute($sql2);

        $card = $this->cards->where(array('cardno'=>$tradeArr['cardno']))->field('customid')->find();
        $sql3="UPDATE ACCOUNT SET AMOUNT=NVL(AMOUNT,0)+{$tradeArr['tradeamount']} WHERE CUSTOMID='{$card['customid']}' AND TYPE='00'";
        $bool3=$this->model->execute($sql3);
        if($bool1==true&&$bool2==true&&$bool3==true){
            return true;
        }else{
            return false;
        }
    }
    //积分预授权撤销执行
    function undoPrePoint($tradeArr,$panterid,$termposno){
        if(empty($tradeArr)) return false;
        $undo_tradeid=$termposno.date('YmdHis');
        $placeddate=date('Ymd');
        $placedtime=date('H:i:s');
        $pretradeid=trim($tradeArr['tradeid']);

        $map=array('tradeid'=>$undo_tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $undo_tradeid=$termposno.date('YmdHis');
        }
        $sql1="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,";
        $sql1.="cardno,customid,tradetype,flag,pretradeid,eorderid) VALUES('{$termposno}','{$termposno}','{$panterid}',";
        $sql1.="'{$undo_tradeid}','{$placeddate}','{$placedtime}','{$tradeArr['tradeamount']}','{$tradeArr['cardno']}'";
        $sql1.=",'{$tradeArr['customid']}','05','0','{$pretradeid}','{$tradeArr['eorderid']}')";
        $bool1=$this->model->execute($sql1);


        $sql2="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$tradeArr['tradeid']}'";
        $bool2=$this->model->execute($sql2);

        $card = $this->cards->where(array('cardno'=>$tradeArr['cardno']))->field('customid')->find();
        $sql3="UPDATE ACCOUNT SET AMOUNT=NVL(AMOUNT,0)+{$tradeArr['tradeamount']} WHERE CUSTOMID='{$card['customid']}' AND TYPE='04'";
        $bool3=$this->model->execute($sql3);

        $map1=array('tradeid'=>$tradeArr['tradeid']);
        $pointConsumeList=M('point_consume')->where($map1)->select();
        $c=0;
        foreach($pointConsumeList as $k=>$v){
            $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
            $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
            $pointConsumeIf=$this->model->execute($pointConsumeSql);
            $pointAccountIf=$this->model->execute($pointAccountSql);
            if($pointConsumeIf==true&&$pointAccountIf==true){
                $c++;
            }
        }
        if($c==count($pointConsumeList)){
            $bool4=true;
        }else{
            $bool4=false;
        }
        if($bool1==true&&$bool2==true&&$bool3==true&&$bool4==true){
            return true;
        }else{
            return false;
        }
    }

    //预授权完成
    public function overPreAuthorization(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $termposno = trim($data['termposno']);
        $panterid = trim($data['panterid']);
        $amount=trim($data['amount']);
        $tradeid=trim($data['tradeid']);
        $tradetype = trim($data['tradetype']);
        $key = trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$termposno.$panterid.$amount.$tradeid.$tradetype);

//        $cardno='6688371800000000001';
//        $termposno = '27000349';
//        $panterid = '00000270';
//        $amount='12';
//        $tradeid='000120160801165859';
//        $tradetype = '21';
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if($tradetype!='21'){
            returnMsg(array('status'=>'02','codemsg'=>'非预授权完成交易类型'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'03','codemsg'=>'预授权完成金额有误'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'04','codemsg'=>'非法商户号'));
        }
        $customid=$this->getCustomid($cardno);
        if($customid==false){
            returnMsg(array('status'=>'05','codemsg'=>'此卡未关联会员'));
        }
        $list=$this->checkPointSendtype($cardno,$panterid);
        if($list==false){
            returnMsg(array('status'=>'06','codemsg'=>'商户未设置积分比例'));
        }
        $map=array('tradeid'=>$tradeid,'panterid'=>$panterid,'flag'=>0);
        $tradeList=$this->model->table('trade_wastebooks')->field('eorderid,panterid')->where($map)->find();
        if($tradeList==false){
            returnMsg(array('status'=>'07','codemsg'=>'未查询到该预授权消费'));
        }
        if($tradeList['panterid']!=$panterid){
            returnMsg(array('status'=>'08','codemsg'=>'预授权完成商户与预授权商户不一致'));
        }
        $map1=array('eorderid'=>$tradeList['eorderid'],'panterid'=>$panterid,'typetype'=>array('in','17,03'),'flag'=>0);
        $tradeLists=$this->model->table('trade_wastebooks')->where($map1)->select();
        $tradeAmount=$this->model->table('trade_wastebooks')->where($map1)->sum('tradeamount');
        if($tradeLists==false){
            returnMsg(array('status'=>'09','codemsg'=>'异常预授权消费'));
        }
        $map = array('c.cardno'=>$cardno);
        $card = $this->cards->alias('c')->join('left join panters p on p.panterid=c.panterid')
            ->where($map)->field('c.customid,c.cardpassword,c.status,c.dcflag,p.namechinese pname')->find();
        $this->model->startTrans();
        $res=array('point'=>0,'balance'=>0);
        $c=0;
        //撤销原订单
        foreach($tradeLists as $key=>$val){
            if($val['tradetype']=='17'){
                $bool=$this->overPreBalance($val,$panterid,$termposno);
                if($bool==true){
                    $c++;
                }
            }elseif($val['tradetype']=='03'){
                $bool=$this->overPrePoint($val,$panterid,$termposno);
                if($bool==true){
                    $c++;
                }
            }
        }
        $bool1=$c==count($tradeLists)?true:false;
        if($bool1==false){
            $this->model->rollback();
            returnMsg(array('status'=>'010','codemsg'=>'原订单撤销失败'));
        }

        //预授权完成金额消费
        $balanceAmount=floatval(round(1/(1+$list['zsrate'])*$amount,2));
        $pointAmount=floatval($amount-$balanceAmount);
        $balanceAccount=$this->accountQuery($customid,'00');
        if($balanceAccount<$balanceAmount){
            returnMsg(array('status'=>'011','codemsg'=>'账户金额不足'));
        }
        $pointAccount=$this->getPointByCustomid($customid,$panterid,$cardno);
        if($pointAccount<$pointAmount){
            returnMsg(array('status'=>'012','codemsg'=>'积分余额不足'));
        }
        if($balanceAmount>0){
            $balanceConsumeArr=array('customid'=>$customid,'orderid'=>$tradeList['eorderid'],
                'panterid'=>$panterid,'type'=>'00','amount'=>$balanceAmount,'tradetype'=>'21',
                'termno'=>$termposno,'cardno'=>$cardno,'pertradeid'=>$tradeid);
            $balanceConsumeIf=$this->consumeExe1($balanceConsumeArr);
        }else{
            $balanceConsumeIf=true;
        }
        if($pointAmount>0){
            $pointConsumeArr=array('cardno'=>$cardno,'orderid'=>$tradeList['eorderid'],'tradetype'=>'06',
                'panterid'=>$panterid,'amount'=>$pointAmount, 'termno'=>$termposno,'customid'=>$customid,'pertradeid'=>$tradeid);
            $pointConsumeIf=$this->pointExeByCustomid($pointConsumeArr);
        }else{
            $pointConsumeIf=true;
        }
        if($balanceConsumeIf==true&&$pointConsumeIf==true){
            $this->model->commit();
            $leftBalance = (string)($this->cardAccQuery($cardno));
            $leftPoint = (string)($this->getPointByCardno($cardno,$panterid));
            $info=array(
                'reduceBalance'=>floatval($balanceAmount),
                'reducePoint'=>floatval($pointAmount),
                'pointtradidlist'=>$pointConsumeIf,
                'leftBalance'=>$leftBalance,
                'leftPoint'=>$leftPoint,
                'time'=>time(),
                'pname'=>$card['pname']
            );
            if($this->checkCardBrand($cardno)==true){
                $info['cashtradidlist']=$balanceConsumeIf['tradeArr'];
                if(!empty($balanceConsumeIf['billedAmount'])){
                    $info['billedAmount']=$balanceConsumeIf['billedAmount'];
                    $info['unBillAmount']=floatval($balanceAmount-$balanceConsumeIf['billedAmount']);
                }
            }else{
                $info['cashtradidlist']=$balanceConsumeIf;
            }
            returnMsg(array('status'=>'1','info'=>$info));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'013','codemsg'=>'预授权完成成功'));
        }
    }

    //余额预授权完成
    function overPreBalance($tradeArr){
        if(empty($tradeArr)) return false;
        $sql1="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$tradeArr['tradeid']}'";
        $bool1=$this->model->execute($sql1);

        $card = $this->cards->where(array('cardno'=>$tradeArr['cardno']))->field('customid')->find();
        $sql2="UPDATE ACCOUNT SET AMOUNT=NVL(AMOUNT,0)+{$tradeArr['tradeamount']} WHERE CUSTOMID='{$card['customid']}' AND TYPE='00'";
        $bool2=$this->model->execute($sql2);
        if($bool1==true&&$bool2==true){
            return true;
        }else{
            return false;
        }
    }

    //积分预授权完成
    function overPrePoint($tradeArr){
        if(empty($tradeArr)) return false;
        $sql1="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$tradeArr['tradeid']}'";
        $bool1=$this->model->execute($sql1);
        $card = $this->cards->where(array('cardno'=>$tradeArr['cardno']))->field('customid')->find();
        $sql2="UPDATE ACCOUNT SET AMOUNT=NVL(AMOUNT,0)+{$tradeArr['tradeamount']} WHERE CUSTOMID='{$card['customid']}' AND TYPE='04'";
        $bool2=$this->model->execute($sql2);

        $map1=array('tradeid'=>$tradeArr['tradeid']);
        $pointConsumeList=M('point_consume')->where($map1)->select();
        $c=0;
        foreach($pointConsumeList as $k=>$v){
            $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
            $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
            $pointConsumeIf=$this->model->execute($pointConsumeSql);
            $pointAccountIf=$this->model->execute($pointAccountSql);
            if($pointConsumeIf==true&&$pointAccountIf==true){
                $c++;
            }
        }
        if($c==count($pointConsumeList)){
            $bool4=true;
        }else{
            $bool4=false;
        }
        if($bool1==true&&$bool2==true){
            return true;
        }else{
            return false;
        }
    }

    //消费撤销
    function cancleConsume(){
        $data=getPostJson();
        $tradeid=trim($data['tradeid']);
        $cardno=trim($data['cardno']);
        $panterid=trim($data['panterid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$panterid.$tradeid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($tradeid)){
            returnMsg(array('status'=>'02','codemsg'=>'订单编号缺失'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法商户号'));
        }

        $trdeWastebooks=M('trade_wastebooks');
        $map=array('tradeid'=>$tradeid,'flag'=>0,'cardno'=>$cardno,$panterid=>$panterid);
        $tradeinfo=$trdeWastebooks->where($map)
            ->field("tradeid,tradeamount,tradepoint,cardno")->find();
        if($tradeinfo==false){
            returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
        }
        if(substr($tradeinfo['cardno'],0,4)!='6688'){
            returnMsg(array('status'=>'05','codemsg'=>'非酒店新卡消费交易不能再次撤销'));
        }
    }

    function overPreAuthCancle(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $tradeid=trim($data['tradeid']);
        $panterid=trim($data['panterid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$panterid.$tradeid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥,非法传入'));
        }
        $panter=$this->checkPanter($panterid);
        if($panter==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法商户号'));
        }
        $map=array('tradeid'=>$tradeid,'panterid'=>$panterid,'flag'=>0,'cardno'=>$cardno,'tradetype'=>'06');
        $tradeList=$this->model->table('trade_wastebooks')->field('eorderid,panterid,pretradeid')->where($map)->find();

        if($tradeList==false){
            returnMsg(array('status'=>'03','codemsg'=>'未查询到该预授权完成记录'));
        }
        $validdate = date('Ymd',time());
        if($validdate-$tradeList['placeddate']<30){
            returnMsg(array('status'=>'06','codemsg'=>'预授权超过30天有效期'));
        }
        $map1=array('eorderid'=>$tradeList['eorderid'],'panterid'=>$panterid,'tradetype'=>array('in','06,21'),'flag'=>0);
        $tradeLists=$this->model->table('trade_wastebooks')->where($map1)->select();
        $this->model->startTrans();
        if($tradeLists==false){
            returnMsg(array('status'=>'04','codemsg'=>'异常预授权完成记录'));
        }
        $c1=0;
        $balacneAmount=0;
        foreach($tradeLists as $key=>$val){
            $card = $this->cards->where(array('cardno'=>$val['cardno']))->field('customid')->find();
            if($val['tradetype']=='06'){
                //撤销积分预授权完成订单，更新对应账户金额，更新积分明细赠送兑换记录
                $tradeSql="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$val['tradeid']}'";
                $accountSql="UPDATE ACCOUNT SET AMOUNT=AMOUNT+{$val['tradeamount']} WHERE CUSTOMID='{$card['customid']}' and type='04'";
                $tradeIf=$this->model->execute($tradeSql);
                $accountIf=$this->model->execute($accountSql);

                $condition=array('tradeid'=>$val['tradeid'],'flag'=>1);
                $pointConsumeList=M('point_consume')->where($condition)->select();
                $count=0;
                foreach($pointConsumeList as $k=>$v){
                    $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=2 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
                    $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)+{$v['amount']} WHERE POINTID='{$v['pointid']}'";
                    $pointConsumeIf=$this->model->execute($pointConsumeSql);
                    $pointAccountIf=$this->model->execute($pointAccountSql);
                    if($pointConsumeIf==true&&$pointAccountIf==true){
                        $count++;
                    }
                }
                $pointAmount=$val['tradeamount'];
                $pointTradeid=$val['tradeid'];
                if($count==count($pointConsumeList)&&$tradeIf==true&&$accountIf==true){
                    $c1++;
                }
            }else{
                //撤销余额预授权完成订单，更新对应账户金额，删除对应开发票记录
                $tradeSql="UPDATE TRADE_WASTEBOOKS SET FLAG=2 WHERE TRADEID='{$val['tradeid']}'";
                $accountSql="UPDATE ACCOUNT SET AMOUNT=AMOUNT+{$val['tradeamount']} WHERE CUSTOMID='{$card['customid']}' and type='00'";
                $tradeIf=$this->model->execute($tradeSql);
                $accountIf=$this->model->execute($accountSql);

                $condition=array('tradeid'=>$val['tradeid']);
                $tradeBillList=M('tradebilling')->where($condition)->find();
                if($tradeBillList!=false){
                    $tradeBillSql="DELETE FROM  TRADEBILL WHERE tradeid='{$val['tradeid']}'";
                    //$tradeBillSql="UPDATE TRADEBILL SET AMOUNT=NVL(AMOUNT,0)-{$val['tradeamount']},status=0 WHERE tradeid='{$val['tradeid']}'";
                    $billDetailList=M('billingdetail')->where($condition)->select();
                    $count=0;
                    foreach($billDetailList as  $k=>$v){
                        $billingDetailSql="DELETE FROM BILLINGDETAIL WHERE card_purchaseid='{$v['card_purchaseid']}' and tradeid='{$val['tradeid']}'";
                        $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)-{$v['amount']},FLAG=0 WHERE card_purchaseid='{$v['card_purchaseid']}'";
                        $billingDetailIf=$this->model->execute($billingDetailSql);
                        $billingIf=$this->model->execute($billingDetailIf);
                        if($billingDetailIf==true&&$billingIf==true){
                            $count++;
                        }
                    }
                }
                $balacneAmount+=$val['tradeamount'];
                if($count==count($billDetailList)&&$tradeIf==true&&$accountIf==true){
                    $c1++;
                }
            }
        }
        $bool1=$c1==count($tradeLists)?true:false;

        $map2=array('eorderid'=>$tradeList['eorderid'],'panterid'=>$panterid,'tradetype'=>array('in','03,17'),'flag'=>2);
        $preLists=$this->model->table('trade_wastebooks')->where($map2)->select();
        $c2=0;
        //print_r($preLists);exit;
        foreach($preLists as $key=>$val){
            $tradeSql="UPDATE TRADE_WASTEBOOKS SET FLAG=0 WHERE TRADEID='{$val['tradeid']}'";
            $card = $this->cards->where(array('cardno'=>$val['cardno']))->field('customid')->find();
            if($val['tradetype']=='03'){
                //恢复积分预授权完成订单，更新对应账户金额，更新积分明细赠送兑换记录
                $tradeSql="UPDATE TRADE_WASTEBOOKS SET FLAG=0 WHERE TRADEID='{$val['tradeid']}'";
                $accountSql="UPDATE ACCOUNT SET AMOUNT=AMOUNT-{$val['tradeamount']} WHERE CUSTOMID='{$card['customid']}' and type='04'";
                $tradeIf=$this->model->execute($tradeSql);
                $accountIf=$this->model->execute($accountSql);

                $condition=array('tradeid'=>$val['tradeid'],'flag'=>2);
                $pointConsumeList=M('point_consume')->where($condition)->select();
                $count=0;
                foreach($pointConsumeList as $k=>$v){
                    $pointConsumeSql="UPDATE POINT_CONSUME SET FLAG=1 WHERE POINTCONSUMEID='{$v['pointconsumeid']}'";
                    $pointAccountSql="UPDATE POINT_ACCOUNT SET REMINDAMOUNT=NVL(REMINDAMOUNT,0)-{$v['amount']} WHERE POINTID='{$v['pointid']}'";
                    $pointConsumeIf=$this->model->execute($pointConsumeSql);
                    $pointAccountIf=$this->model->execute($pointAccountSql);
                    if($pointConsumeIf==true&&$pointAccountIf==true){
                        $count++;
                    }
                }
                if($count==count($pointConsumeList)&&$tradeIf==true&&$accountIf==true){
                    $c2++;
                }
            }else{
                //恢复余额预授权完成订单，更新对应账户金额
                $tradeSql="UPDATE TRADE_WASTEBOOKS SET FLAG=0 WHERE TRADEID='{$val['tradeid']}'";
                $accountSql="UPDATE ACCOUNT SET AMOUNT=AMOUNT-{$val['tradeamount']} WHERE CUSTOMID='{$card['customid']}' and type='00'";
                $tradeIf=$this->model->execute($tradeSql);
                $accountIf=$this->model->execute($accountSql);
                if($tradeIf==true&&$accountIf==true){
                    $c2++;
                }
            }
        }
        $bool2=$c2==count($preLists)?true:false;
        if($bool1==true&&$bool2==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'预授权完成撤销成功',
                'time'=>time(),'pointtradeid'=>trim($pointTradeid),'balanceAmount'=>$balacneAmount,'pointAmount'=>$pointAmount));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'预授权完成撤销失败'));
        }
    }
  }
