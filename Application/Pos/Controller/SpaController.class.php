<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class SpaController extends CoinController{

    public function _initialize(){
        parent::_initialize();
        $this->tzpanterid='00002012';
    }

    //至尊卡充值接口
    public function cardRecharge(){
        $data=getPostJson();
        
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");
        
        //$data=json_decode('{"amount":"100","type":"2","key":"5c7623ec698a6c4c37cb418ba5588d89","cardno":"6668371800000000001"}',1);
        $cardno =$data['cardno'];//卡号
        $linktel = $data['linktel'];//电话（开卡时传入）
        $personid=$data['personid'];//身份证号（开卡时传入）
        $name = $data['name'];//名字（开卡时传入）
        $amount=$data['amount'];//冲值金额
        $type=$data['type'];//充值类型（1开卡并充值，2充值）
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$linktel.$personid.$name.$amount.$type);
        $this->recordData(json_encode($data),'');
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
        }
        if(empty($type)||!in_array($type,array(1,2))){
            returnMsg(array('status'=>'04','codemsg'=>'无效充值类型'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'05','codemsg'=>'无效充值金额'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            returnMsg(array('status'=>'06','codemsg'=>'查无此卡号'));
        }
        if($card['cardkind']!='6668'){
            returnMsg(array('status'=>'07','codemsg'=>'非SPA卡，不能再次开卡充值'));
        }
        $paymenttype='SPA普通充值';
        if($amount>=3000&&$amount<5000){
            $point=300;
        }elseif($amount>=5000&&$amount<10000){
            $point=750;
        }elseif($amount>=10000){
            $point=2000;
        }else{
            $point=0;
        }
        $description="充值金额:{$amount}";
        $panterid=$this->tzpanterid;
        $userid='0000000000000000';
        $this->model->startTrans();
        if($type==1){
            if(empty($linktel)||empty($name)){
                returnMsg(array('status'=>'011','codemsg'=>'开卡用户信息缺失'));
            }
            $data=array('linktel'=>$linktel,'personid'=>$personid,'name'=>$name,
                'personidexdate'=>'','personidissuedate'=>'',
                'residaddress'=>'');
            $customid=$this->addcustoms($data);
            $rechargeBool=$this->spaOpenCard($cardno,$customid,$amount,$panterid,$userid,$paymenttype,$description);

            $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cc.customid=cu.customid')
                ->join('account a on a.customid=c.customid')
                ->field('cu.customid,a.amount,a.accountid,c.customid cardid')
                ->where(array('c.cardno'=>$cardno,'a.type'=>'04'))->find();
        }else{
            $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cc.customid=cu.customid')
                ->join('account a on a.customid=c.customid')
                ->field('cu.customid,a.amount,a.accountid,c.customid cardid')
                ->where(array('c.cardno'=>$cardno,'a.type'=>'04'))->find();
            $rechargeBool=$this->spaRecharge($cardno,$custom['customid'],$amount,$panterid,$userid,$paymenttype,$description);
        }
        if($rechargeBool==true){
            $cardInfo=array('cardid'=>$custom['customid'],'cardno'=>$cardno,
                'accountid'=>$custom['accountid'],'validity'=>13,'type'=>2);
            if($point>0){
                $pointBool=$this->spaPointRechargeExe($cardInfo,$point,$panterid,$userid,$rechargeBool);
            }else{
                $pointBool=true;
            }
            if($pointBool==true){
                $this->model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'充值成功','purchaseid'=>$rechargeBool,'point'=>$point));
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'08','codemsg'=>'积分充值失败'));
            }
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'08','codemsg'=>'余额充值失败'));
        }
    }

    //获取卡激活情况
    public function getCardInfo(){
        $data=getPostJson();
        $cardno =$data['cardno'];//卡号
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno);
        $this->recordData(json_encode($data),'');
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6666371800000000002';
        $card=$this->cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['cardkind']!='6668'){
            returnMsg(array('status'=>'05','codemsg'=>'非SPA卡'));
        }
        if($card['status']=='N'){
            returnMsg(array('status'=>'03','codemsg'=>'待激活卡'));
        }elseif($card['status']=='Y'){
            $custom=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cc.customid=cu.customid')
                ->field('cu.namechinese name,cu.sex,cu.linktel,cu.personid,cu.birthday,cu.personidissuedate,cu.personidexdate,cu.residaddress')
                ->where(array('c.cardno'=>$cardno))->find();
            returnMsg(array('status'=>'1','info'=>$custom));
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'锁定卡'));
        }
    }

    //添加会员的信息
    public function addcustoms($data){
        $customs = M('customs');
        $linktel=$data['linktel'];
        $personid=$data['personid'];
        $namechinese=$data['name'];
        $panterid=$data['panterid'];
        $personidissuedate=$data['personidissuedate'];
        $personidexdate=$data['personidexdate'];
        $residaddress=$data['residaddress'];
        $birth=substr($personid,6,8);
        $sex=substr($personid,-2,1)%2==0?'女':'男';
        $customlevel='SPA会员';
        $countrycode='SPA会员';
        $currentDate=date('Ymd',time());
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
        $sql='insert into customs(customid,namechinese,linktel,personid,panterid,placeddate,personidtype,customlevel,';
        $sql.="countrycode) values('{$customid}','{$namechinese}','{$linktel}','{$personid}','{$panterid}'";
        $sql.=",'{$currentDate}','身份证','{$customlevel}','{$countrycode}')";
        $this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
        $ccu=$customs->execute($sql);
        if($ccu){
            return $customid;
        }else{
            return false;
        }
    }

    //获取卡账户信息
    public function getAccount(){
        $data=getPostJson();
        $cardno =$data['cardno'];//卡号
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        if($card['cardkind']!='6668'){
            returnMsg(array('status'=>'04','codemsg'=>'非SPA卡'));
        }
        $balance=$this->cardAccQuery($cardno);
        $point=$this->getTzPoint($cardno);
        $info=array('balance'=>floatval($balance),'point'=>floatval($point));
        returnMsg(array('status'=>'1','info'=>$info));
    }

    //获取一级菜单
    public function getProduct(){
        $where=array('catelevel'=>2,'panterid'=>$this->tzpanterid);
        $list=M('tz_cate')->where($where)->field('cateid,catename')->order('cateid asc')->select();
        $product=M('tz_product');

        if(!empty($list)){
            foreach($list as $key=>$val){
                unset($list[$key]['numrow']);
                $productList=$product->where(array('cateid'=>$val['cateid']))->field('price,pid,pname')->select();
                $list[$key]['product']=$productList;
            }
            returnMsg(array('status'=>1,'info'=>$list));
        }else{
            returnMsg(array('status'=>0,'codemsg'=>'无信息'));
        }
    }

    //消费接口
    public function consume(){
        $data=getPostJson();
        //$data=json_decode('{"totalpoint":"2000","totalbalance":"0","pwd":"BFD0C613A11BF88E","cardno":"6880374868800000237","orderid":"201611241911577326","key":"e061a95c5cb885cb22f129f4f31ef94f","orderlist":"[{\"num\":1,\"pid\":\"1\"},{\"num\":1,\"pid\":\"3\"}]"}',1);
        //$data=json_decode('{"pwd":"D87B738229B01D13","cardno":"6668371800000000001","orderid":"201707031107571479","key":"7348849b0d887a66b93c283247630a5f","orderlist":"[{\"num\":1,\"pid\":\"98\"}]","discount":"0.8","totalamount":"258"}',1);
        //$data=json_decode('{"pwd":"6D0ED630730633F1","cardno":"6668371800000000001","orderid":"201707041007142337","key":"2ef7509a66f91ae382baf0d66333fb35","orderlist":"[{\"num\":1,\"pid\":\"91\"}]","discount":"7.2","totalamount":"568"}',1);;
        $this->recordData(json_encode($data),'');
        $cardno =$data['cardno'];//卡号
        $orderId=$data['orderid'];//总订单id
        $orderList=$data['orderlist'];//子订单集合
        $totalAmount=$data['totalamount'];//消费总余额
        $discount=$data['discount'];
        //$termno=$data['termno'];//pos终端号
        $pwd=$data['pwd'];//至尊卡密码
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$orderId.$orderList.$totalAmount.$pwd.$discount);
        //echo $checkKey.'--'.$key;exit;
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        if($card['cardkind']!='6668'){
            returnMsg(array('status'=>'012','codemsg'=>'非SPA卡，不能消费'));
        }
        if(empty($orderId)){
            returnMsg(array('status'=>'04','codemsg'=>'总订单编号缺失'));
        }
        $orderList=json_decode($orderList,1);
        if($orderList==false){
            returnMsg(array('status'=>'05','codemsg'=>'子订单信息缺失'));
        }
        $checkPwd=$this->checkPwdBycardno($cardno,$pwd);
        if($checkPwd==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡密码错误'));
        }
        $balance=$this->cardAccQuery($cardno);
        $point=$this->getTzPoint($cardno);
        $discount=empty($discount)?1:$discount;
        $amount=$totalAmount*$discount;
        //echo $balance.'--'.$point.'--'.$amount;exit;
        if($amount>($balance+$point)){
            returnMsg(array('status'=>'07','codemsg'=>'余额不足'));
        }
        $validateAmount=0;
        foreach($orderList as $key=>$val){
            $price=$this->getPrice($val['pid']);
            $orderList[$key]['price']=$price;
            $num=$val['num'];
            $pamount=$num*$price*$discount;
            $validateAmount=$validateAmount+$pamount;
        }
        if($validateAmount!=$amount){
            returnMsg(array('status'=>'09','codemsg'=>'订单总金额与实际消费金额不符'));
        }
        if($balance>=$amount){
            $balance1=$amount;
            $point1=0;
        }else{
            $balance1=$balance;//待扣款余额
            $point1=$amount-$balance1;//待扣款积分
        }
        $undecuteBalance=$balance1;
        $undecutePoint=$point1;
        $totalConsumedFee=0;//总订单扣款总金额
        $tradeIds=array();
        $this->model->startTrans();
        foreach($orderList as $key=>$val){
            $consumeAmount=$val['price']*$val['num']*$discount;
            //echo $undecutePoint.'--';
            if($undecutePoint>0){
                if($undecutePoint>=$consumeAmount){
                    $consumePoint=$consumeAmount;//首先进行积分消费，需要扣款的积分大于子订单消费金额，只给本子订单进行积分扣除
                    $consumeBalance=0;
                }else{
                    $consumePoint=$undecutePoint;
                    $consumeBalance=$consumeAmount-$undecutePoint;
                }
                $undecutePoint=$undecutePoint-$consumePoint;//本次子订单执行结束，代扣款积分减去本次消费积分
            }else{
                $consumeBalance=$consumeAmount;
                $consumePoint=0;
            }
            //echo $consumeBalance.'--'.$consumePoint.'--';
            $tradeid=substr($cardno,-5).date('YmdHis').$val['pid'];
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $sql="insert into tz_wastebooks values('{$tradeid}','00000000','{$cardno}','{$consumeBalance}','{$consumePoint}',";
            $sql.="'{$currentDate}','{$currentTime}','{$this->tzpanterid}','00','{$val['pid']}','{$val['num']}','{$orderId}','','{$val['price']}','{$discount}')";
            $tradeIf=$this->model->execute($sql);

            if($consumeBalance>0){
                $balanceSql="update account set amount=amount-{$consumeBalance} where customid='{$card['customid']}' and type='00'";
                $balanceIf=$this->model->execute($balanceSql);
            }else{
                $balanceIf=true;
            }
            if($consumePoint>0){
                $pointIf=$this->tzPointConsume($cardno,$consumePoint,$tradeid);
            }else{
                $pointIf=true;
            }
            //echo $balanceIf.'--'.$pointIf.'--'.$tradeIf.'<br/>';
            if($balanceIf==true&&$pointIf==true&&$tradeIf==true){
                $totalConsumedFee+=$consumeBalance+$consumePoint;
                $tradeIds[]=$tradeid;
            }
        }
        //echo $totalConsumedFee.'---'.$totalAmount;exit;
        if($totalConsumedFee==$amount){
            $this->model->commit();
            $balance=$this->cardAccQuery($cardno);
            $point=$this->getTzPoint($cardno);
            returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradeids'=>$tradeIds,
                'info'=>array('balance'=>$balance1,'point'=>$point1,'discount'=>$discount),
                'cardinfo'=>array('balance'=>$balance,'point'=>$point)
            ));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'010','codemsg'=>'消费失败'));
        }
    }

    //修改卡密码接口
    public function editPwd(){
        $data=getPostJson();
        $cardno =$data['cardno'];//卡号
        $oldPwd=$data['oldpwd'];//原密码
        $newPwd=trim($data['newpwd']);//新密码
        $newPwd1=trim($data['newpwd1']);//重复新密码
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$oldPwd.$newPwd.$newPwd1);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->field('cardno,customid,cardpassword,status')->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $oldPwd=$des->doEncrypt($oldPwd);
        if($oldPwd!=$card['cardpassword']){
            returnMsg(array('status'=>'04','codemsg'=>'原密码输入错误'));
        }
        if(!preg_match('/^\d{6}?$/',$newPwd)){
            returnMsg(array('status'=>'05','codemsg'=>'新密码格式错误'));
        }
        if($newPwd!=$newPwd1){
            returnMsg(array('status'=>'06','codemsg'=>'两次新密码输入不一致'));
        }
        $newPwd=$oldPwd=$des->doEncrypt($newPwd);
        $sql="update cards set cardpassword='{$newPwd}' where cardno='{$cardno}'";
        if($this->model->execute($sql)){
            returnMsg(array('status'=>'1','codemsg'=>'密码修改成功,请妥善保管'));
        }else{
            returnMsg(array('status'=>'07','codemsg'=>'密码修改失败'));
        }
    }

    //获取订单列表
    public function getTradeList(){
        $data=getPostJson();
        //$data=json_decode('{"key":"428c535e85f3ee8ee8435ad6157a35ef","pwd":"BFD0C613A11BF88E","cardno":"6880374868800000237"}',1);
        $this->recordData(json_encode($data),'');
        $cardno =$data['cardno'];//卡号
        $pwd=$data['pwd'];//至尊卡密码
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$pwd);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->field('cardno,customid,status')->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        $checkPwd=$this->checkPwdBycardno($cardno,$pwd);
        if($checkPwd==false){
            returnMsg(array('status'=>'05','codemsg'=>'卡密码错误'));
        }
        $tz_wastebooks=M('tz_wastebooks');
        $where=array('cardno'=>$cardno,'placeddate'=>array('gt',date("Y-m-d",strtotime("-1 month - day"))));
        $orderlist=$tz_wastebooks->where($where)->field('orderid')->group('orderid')->select();
        $list=array();
        foreach($orderlist as $key=>$val){
            $map=array('tw.orderid'=>$val['orderid']);
            $field='tw.tradeid,tw.tradeamount,tw.tradepoint,tw.placeddate,tw.placedtime,tw.tradetype,p.pname,tw.tradenum';
            $childList=$tz_wastebooks->alias('tw')->join('tz_product p on p.pid=tw.tradepid')
                ->where($map)->field($field)->select();
            $consumePoint=0;
            $consumeBalance=0;
            foreach($childList as $k=>$v){
                $type=$v['tradetype']=='00'?'交易成功':'退款';
                $list[$val['orderid']]['child'][]=array('tradeid'=>$v['tradeid'], 'tradeamount'=>floatval($v['tradeamount']),
                    'tradepoint'=>floatval($v['tradepoint']),'tradetime'=>date('Y-m-d',strtotime($v['placeddate'])).' '.$v['placedtime'],
                    'type'=>$type,'pname'=>$v['pname'],'tradenum'=>$v['tradenum']
                );
                $consumePoint+=$v['tradepoint'];
                $consumeBalance+=$v['tradeamount'];
            }
            $list[$val['orderid']]['totalbalance']=$consumeBalance;
            $list[$val['orderid']]['totalpoint']=$consumePoint;
            $list[$val['orderid']]['orderid']=$val['orderid'];
        }
        $c=0;
        foreach($list as $key=>$val){
            $list[$c]=$val;
            unset($list[$key]);
            $c++;
        }
        if(!empty($list)){
            returnMsg(array('status'=>'1','info'=>$list));
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'无记录'));
        }
    }

    //获取订单详细信息
    public function getTradeDetail(){
        $data=getPostJson();
        $this->recordData(json_encode($data),'');
        $cardno =$data['cardno'];//卡号
        $orderid=$data['orderid'];
        $key=$data['key'];
        $checkKey=md5($this->keycode.$cardno.$orderid);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->field('cardno,customid,status')->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        $tz_wastebooks=M('tz_wastebooks');
        $where=array('cardno'=>$cardno,'orderid'=>$orderid);
        $field='tw.tradeid,tw.tradeamount,tw.tradepoint,tw.placeddate,tw.placedtime,tw.tradetype,p.pname,tw.tradenum';
        $tradeList=$tz_wastebooks->where($where)->field('orderid')->group('orderid')->select();
        if($tradeList==false){
            returnMsg(array('status'=>'04','codemsg'=>'无订单记录'));
        }else{
            $list=array();
            $consumePoint=0;
            $consumeBalance=0;
            foreach($tradeList as $k=>$v){
                $type=$v['tradetype']=='00'?'交易成功':'退款';
                $list['child'][]=array('tradeid'=>$v['tradeid'], 'tradeamount'=>floatval($v['tradeamount']),
                    'tradepoint'=>floatval($v['tradepoint']),'tradetime'=>date('Y-m-d',$v['placeddate']).' '.$v['placedtime'],
                    'type'=>$type,'pname'=>$v['pname'],'tradenum'=>$v['tradenum']
                );
                $consumePoint+=$v['tradepoint'];
                $consumeBalance+=$v['tradeamount'];
            }
            $list['totalbalance']=$consumeBalance;
            $list['totalpoint']=$consumePoint;
            $list['orderid']=$orderid;
            returnMsg(array('status'=>'1','info'=>$list));
        }
    }

    //子订单退款
    public function cancelTrade(){
        $data=getPostJson();
        //$data=json_decode('{"key":"44539ce7c5d7580a176bef7e0e33f02f","tradeid":"000013989320161205140133","cardno":"6666371800000000001"}',1);
        $this->recordData(json_encode($data),'');
        $cardno =$data['cardno'];//卡号
        $tradeid=$data['tradeid'];
        $key=$data['key'];
        //$this->recordData(json_encode($data));
        $checkKey=md5($this->keycode.$cardno.$tradeid);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->cards->where(array('cardno'=>$cardno))->field('cardno,customid,status')->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'查无此卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'无效卡号'));
        }
        $map=array('cardno'=>$cardno,'tradeid'=>$tradeid,'tradetype'=>'00');
        $tz_wastebooks=M('tz_wastebooks');
        $list=$tz_wastebooks->where($map)->find();
        if($list==false){
            returnMsg(array('status'=>'04','codemsg'=>'查无此订单'));
        }
        $map1=array('cardno'=>$cardno,'pretradeid'=>$tradeid,'tradetype'=>'01');
        $list1=$tz_wastebooks->where($map1)->find();
        if($list1==true){
            returnMsg(array('status'=>'05','codemsg'=>'该订单已退款'));
        }
        $tradeid=substr($cardno,-5).$list['pid'].mt_rand(1000,9999).date('YmdHis');
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $returnBalance=$list['tradeamount']>0?-$list['tradeamount']:0;
        $returnPoint=$list['tradepoint']>0?-$list['tradepoint']:0;
        $sql="insert into tz_wastebooks values('{$tradeid}','{$list['termno']}','{$list['cardno']}','{$returnBalance}','{$returnPoint}',";
        $sql.="'{$currentDate}','{$currentTime}','{$this->tzpanterid}','00','{$list['pid']}','{$list['num']}','{$list['orderid']}','{$list['tradeid']}','{$list['tradeprice']}',0)";
        $tradeIf=$this->model->execute($sql);

        if($list['tradeamount']>0){
            $balanceSql="update account set amount=amount+{$list['tradeamount']} where customid='{$card['customid']}' and type='00'";
            $balanceIf=$tradeIf=$this->model->execute($balanceSql);
        }else{
            $balanceIf=true;
        }

        if($list['tradepoint']>0){
            $pointIf=$this->tzPointReturn($cardno,$list['tradepoint'],$list['tradeid']);
        }else{
            $pointIf=true;
        }

        if($balanceIf==true&&$tradeIf==true&&$pointIf==true){
            $this->model->commit();
            $productInfo=M('tz_product')->where(array('pid'=>$list['tradepid']))->find();
            returnMsg(array('status'=>'1','codemsg'=>'订单退款成功',
                    'info'=>array('pname'=>$productInfo['pname'],'point'=>$list['tradepoint'],
                        'amount'=>$list['tradeamount'],'tradeid'=>$tradeid,'cardno'=>$cardno
                    ))
            );
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'06','codemsg'=>'订单退款失败'));
        }
    }

    //积分消费执行
    protected function tzPointConsume($cardno,$point,$tradeid){
        $panterid=$this->tzpanterid;
        $pointList=$this->getTzPointList($cardno);
        //echo $consumePoint;
        $consumedPoint=0;
        foreach($pointList as $key=>$val){
            $waitPoint=$point-$consumedPoint;
            if($waitPoint>=$val['remindamount']){
                $consumePoint=$val['remindamount'];
            }else{
                $consumePoint=$waitPoint;
            }
            $pointSql="UPDATE point_account set remindamount=remindamount-{$consumePoint} where pointid='{$val['pointid']}'";
            $pointIf=$this->model->execute($pointSql);

            $accountSql="UPDATE account set amount=amount-{$consumePoint} where customid='{$val['cardid']}' and type='04'";
            $accountIf=$this->model->execute($accountSql);

            $pointconsumeid=$this->getFieldNextNumber('pointconsumeid');
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            //-----------增加字段
            $pointConsumeSql="INSERT INTO point_consume values('{$pointconsumeid}','{$tradeid}','{$val['cardid']}',";
            $pointConsumeSql.="'{$val['pointid']}','{$consumePoint}','{$currentDate}','{$currentTime}','{$panterid}',0,1)";
            $pointConsumeIf=$this->model->execute($pointConsumeSql);


            if($pointSql==true&&$accountIf==true&&$pointConsumeIf==true){
                $consumedPoint+=$consumePoint;
            }
        }
        if($consumedPoint==$point){
            return true;
        }else{
            return false;
        }
    }

    //开卡执行
    protected function spaOpenCard($cardno,$customid,$amount,$panterid=null,$userid,$paymenttype='转账',$description=null){
        if(empty($cardno)) return false;
        $userstr= substr($userid,12,4);
        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
        $purchaseid=$this->getFieldNextNumber("purchaseid");
        $purchaseid=$userstr.$purchaseid;
        $currentDate=date('Ymd');
        $checkDate=date('Ymd');
        $where['cardno']=$cardno;
        $cardinfo=M('cards')->where($where)->field('panterid')->find();
        //写入购卡单并审核
        $customplSql="insert into custom_purchase_logs(CUSTOMID,PURCHASEID,PLACEDDATE,PAYMENTTYPE,USERID,AMOUNT,CARD_NUM,TOTALMONEY,";
        $customplSql.="POINT,REALAMOUNT,WRITEAMOUNT,WRITENUMBER,CHECKNO,DESCRIPTION,FLAG,PANTERID,CHECKID,DESCRIPTION1,";
        $customplSql.="TRADEFLAG,CUSTOMFLAG,DAYFLAG,TERMPOSNO,PLACEDTIME,CHECKDATE,CHECKTIME,ADITID) ";
        $customplSql.="VALUES('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','{$amount}',NULL,'{$amount}',0,'{$amount}','{$amount}'";
        $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf=$this->model->execute($customplSql);
        //写入审核单
        //$auditid=$this->getnextcode('audit_logs',16);
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs(auditid,purchaseid,TYPE,decription,placeddate,audituser,placedtime) values ('".$auditid."','".$purchaseid."','审核通过',";
        $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

        $auditlogsIf=$this->model->execute($auditlogsSql);

        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        //写入购卡充值单

        $cardplSql="INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
        $cardplSql.="FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$amount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','{$description}',";
        $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
        $cardplIf=$this->model->execute($cardplSql);

        $where1['customid']=$customid;
        $card=$this->cards->where($where1)->find();
        if($card==false){
            //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
            $cardId=$customid;
        }else{
            //若存在，则需另外生成卡编号
            //$cardId=$this->getnextcode('customs',8);
            $cardId=$this->getFieldNextNumber("customid");
            $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
            $customIf=$this->model->execute($customSql);
        }
        //echo $cardId;exit;
        //执行激活操作
        $cardAlSql="INSERT INTO card_active_logs(CARDNO,USERID,EXDATE,CARDBALANCE,STATUS,LINKTEL,ACTIVEDATE,ACTIVETIME,DESCRIPTION,CUSTOMID,PANTERID,TERMINAL_ID) ";
        $cardAlSql.=" VALUES('{$cardno}','{$userid}',".date('Ymd');
        $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
        $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
        $cardAlIf=$this->model->execute($cardAlSql);
        //echo $cardAlSql;exit;
        //关联会员卡号
        $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
        $customsIf=$this->model->execute($customcSql);

        //更新卡状态为正常卡，更新卡有效期；
        $exd=date('Ymd',strtotime("+3 years"));
        $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$amount}',exdate='{$exd}' where cardno='".$cardno."'";
        $cardIf=$this->model->execute($cardSql);
        //echo $this->model->getLastSql();exit;

        //给卡片添加账户并给账户充值
        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $balanceSql.=$acid."','".$cardId."','".$amount."','00',NULL)";
        $balanceIf=$this->model->execute($balanceSql);

        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $coinSql.=$acid."','".$cardId."','0','01',NULL)";
        $coinIf=$this->model->execute($coinSql);

        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $pointSql.=$acid."','".$cardId."','0','04',NULL)";
        $pointIf=$this->model->execute($pointSql);

        if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true&&$coinIf==true){
            return $cardpurchaseid;
        }else{
            return false;
        }
    }

    //充值执行
    protected function spaRecharge($cardno,$customid,$rechargeAmount,$panterid,$userid,$paymenttype='现金',$description=null){
        if(empty($cardno)) return false;
        $userstr= substr($userid,12,4);
        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
        $purchaseid=$this->getFieldNextNumber("purchaseid");
        $purchaseid=$userstr.$purchaseid;
        $currentDate=date('Ymd');
        $checkDate=date('Ymd');

        $where['cardno']=$cardno;
        $card=$this->cards->where($where)->field('customid')->find();
        $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$rechargeAmount."',NULL,'".$rechargeAmount."',0,'".$rechargeAmount."','".$rechargeAmount;
        $customplSql.="',1,'','','1','".$card['panterid']."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

        //写入审核单
        //$auditid=$this->getnextcode('audit_logs',16);
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
        $auditlogsSql.="'充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);

        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

        //写入充值单
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$rechargeAmount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','{$description}',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf=$this->model->execute($cardplSql);

        //更新卡片账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeAmount." where customid='".$card['customid']."' and type='00'";
        $balanceIf=$this->model->execute($balanceSql);


        if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true){
            return $cardpurchaseid;
        }else{
            return false;
        }
    }

    //获取品类商品价格
    protected function getPrice($pid){
        if(empty($pid)) return false;
        $map=array('pid'=>$pid);
        $list=M('tz_product')->where($map)->find();
        if($list==false) return false;
        return $list['price'];
    }

    //获取天筑积分
    protected function getTzPoint($cardno){
        $panterid=$this->tzpanterid;
        $where['pa.panterid']=$panterid;
        $where['pa.enddate']>=date('Ymd');
        $where['c.cardno']=$cardno;
        $where['pa.remindamount']=array('gt',0);
        $sum=M('cards')->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where)->sum('remindamount');
        $sum=$sum==''?0:$sum;
        return $sum;
    }

    //获取天筑可消费积分列表
    protected function getTzPointList($cardno){
        $panterid=$this->tzpanterid;
        $where['pa.panterid']=$panterid;
        $where['pa.enddate']>=date('Ymd');
        $where['c.cardno']=$cardno;
        $where['pa.remindamount']=array('gt',0);
        $list=M('cards')->alias('c')->join('point_account pa on pa.cardid=c.customid')
            ->where($where)->field('pa.cardid,pa.pointid,pa.remindamount')->select();
        return $list;
    }

    protected function spaPointReturn($cardno,$point,$tradeid){
        $panterid=$this->tzpanterid;
        $map=array('tradeid'=>$tradeid,'panterid'=>$panterid);
        $consumeList=$this->model->table('point_consume')->where($map)->select();
        $c=0;
        foreach($consumeList as $key=>$val){
            $pointSql="UPDATE point_account set remindamount=remindamount+{$val['amount']} where pointid='{$val['pointid']}'";
            $pointIf=$this->model->execute($pointSql);

            $accountSql="UPDATE account set amount=amount+{$val['amount']} where customid='{$val['cardid']}' and type='04'";
            $accountIf=$this->model->execute($accountSql);

            $pointconsumeid=$this->getFieldNextNumber('pointconsumeid');
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            //-----------增加字段
            $pointConsumeSql="INSERT INTO point_consume values('{$pointconsumeid}','{$tradeid}','{$val['cardid']}',";
            $pointConsumeSql.="'{$val['pointid']}','-{$val['amount']}','{$currentDate}','{$currentTime}','{$panterid}',0,2)";
            $pointConsumeIf=$this->model->execute($pointConsumeSql);

            if($pointSql==true&&$accountIf==true&&$pointConsumeIf==true){
                $c++;
            }
        }
        if($c==count($consumeList)){
            return true;
        }else{
            return false;
        }
    }

    protected function spaPointRechargeExe($cardInfo,$pointAmount,$panterid,$userid,$balancePurchaseid){
        $cardid=$cardInfo['cardid'];
        $cardno=$cardInfo['cardno'];
        $accountid=$cardInfo['accountid'];
        $type=$cardInfo['type'];
        $validity=$cardInfo['validity'];
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        if(empty($validity)){
            $enddate=0;
        }else{
            $enddate=date('Ymd',strtotime('+'.$validity.' month',time()));
        }
        if($userid==null){
            $userid = $this->hotelUserid;
        }
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $purchaseid=substr($cardpurchaseid,1,16);
        $userip='';
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql.=trim($cardno)."',0,'{$pointAmount}','{$nowdate}','{$nowtime}','1','充值单号:{$balancePurchaseid}','";
        $cardplSql.=$userid."','{$panterid}','{$userip}','00000000')";
        $accountSql="UPDATE account SET amount=nvl(amount,0)+".$pointAmount." where customid='".$cardid."' and type='04'";

        $cardplIf  =$this->model->execute($cardplSql);
        $accountIf = $this->model->execute($accountSql);

        //$pointid=$this->getnextcode('pointid',8);
        $pointid=$this->getFieldNextNumber('pointid');
        $pointSql="INSERT INTO point_account values('{$accountid}','{$pointAmount}','{$pointAmount}','{$nowdate}'";
        $pointSql.=",'{$nowtime}','{$panterid}','{$cardid}','{$pointid}','','{$cardpurchaseid}','{$enddate}','{$type}')";
        $pointIf=$this->model->execute($pointSql);
        if($cardplIf==true&&$accountIf==true&&$pointIf==true){
            return true;
        }else{
            return false;
        }
    }


}
