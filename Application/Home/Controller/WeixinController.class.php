<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class WeixinController extends CoinController {
    //劵查询接口
    public function ticketsQerry(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        //$customid='00000016';
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $tickketList=$this->getTicketByCustomid($customid);
        if($tickketList==false){
            returnMsg(array('status'=>'04','codemsg'=>'查无卡劵信息'));
        }else{
            returnMsg(array('status'=>'1','ticketList'=>json_encode($tickketList)));
        }
    }
    //注册接口
    public function register(){
        $logStr = '';
        $data=getPostJson();

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");
        
        $linktel=trim($data['linktel']);
        $cardno=trim($data['cardno']);
        $pwd=trim($data['pwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$linktel.$pwd.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(!preg_match("/1[34578]{1}\d{9}$/",$linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号为空或者格式不对'));
        }
        $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
        $custom=$this->customs->where($map)->find();//important bug !!!
        $map1=array('c.cardno'=>$cardno);
        $card=$this->cards->alias('c')->join('left join customs_c cc on cc.cid=c.customid')
            ->join('left join customs cu on cu.customid=cc.customid')->where($map1)
            ->field('c.status,c.cardno,cu.customid,cu.linktel')->find();

        isset($custom['customid']) && $logStr .= 'GetData: ' . serialize($data) . ' ReturnInfo ';
        if($custom==false){//会员不存在
            if($card['status']=='Y'){//至尊卡已激活

                if($card['linktel']==$linktel){//验证卡密码；卡已开卡判断卡属手机号和提价手机是否一致，一致返回会员编号，不一致返回卡已绑定
                    $pwdBool=$this->checkCardPwd($cardno,$pwd);
                    if($pwdBool==false){
                        $logStr .= '帮卡失败[密码错误]!  ErrorData:'.$cardno .'-'. $pwd;
                        $this->writeMsg($logStr);
                        returnMsg(array('status'=>'04','codemsg'=>'卡密码错误'));
                    }
                    $map['customid']=$card['customid'];
                    $arr=array('customlevel'=>'建业线上会员');
                    if($this->customs->where($map)->save($arr)){
                        $logStr .= '帮卡成功!';
                        $this->writeMsg($logStr);
                        returnMsg(array('status'=>'1','customid'=>encode($card['customid'])));
                    }else{
                        $logStr .= '帮卡失败[信息保存失败]! ErrorData:'.serialize($map);
                        $this->writeMsg($logStr);
                        returnMsg(array('status'=>'07','codemsg'=>'绑卡失败'));
                    }
                }else{
                    $logStr .= '帮卡失败[卡已绑定]!,ErrorData:'.serialize($card).'--'.$linktel;
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'09','codemsg'=>'卡已绑定，禁止重复绑定'));
                }
            }elseif($card['status']=='N'){//至尊卡未激活

                $this->model->startTrans();
                $customArr=array('mobile'=>$linktel,'panterid'=>'','pwd'=>'','pname'=>'微信会员');
                $customid=$this->createCustoms($customArr,1);
                if($customid==false){
                    $this->model->rollback();
                    $logStr .= '会员注册失败[操作回滚]! ErrorData:' . serialize($customArr) ."-" . serialize($card);
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'06','codemsg'=>'会员注册失败'));
                }
                $cardArr=array($cardno);
                $bool=$this->openCard($cardArr,$customid,0,'',1);
                if($bool==false){
                    $this->model->rollback();
                    $logStr .= '会员开卡失败[操作回滚]! ErrorData:' . serialize($customArr) . " CardInfo:" . serialize($cardArr) . "--会员id:" . $customid;
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'07','codemsg'=>'会员开卡失败'));
                }else{
                    $this->model->commit();
                    $logStr .= '会员开卡成功!';
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'1','customid'=>$customid));
                }
            }else{
                returnMsg(array('status'=>'05','codemsg'=>'非正常卡不能绑定'));
            }
        }else{//会员存在
            if($card['status'] == 'Y'){//至尊卡正常

                $pwdBool=$this->checkCardPwd($cardno,$pwd);//查看卡所属会员与手机号会员是否一致，一致返回会员编号，不一致返回错误

                if($pwdBool==false){
                    $logStr .= '帮卡失败[卡密码有误]! ErrorData:'.$cardno.'-'.$pwd;
                    $this->writeMsg($logStr);
                    //if ($cardno != '6688371800000000807')
                    returnMsg(array('status'=>'04','codemsg'=>'卡密码错误'));
                }
                if($card['customid']!=$custom['customid']){
                    $logStr .= '帮卡失败[卡属信息与提交信息不一致]! ErrorData:'. serialize($custom) .'-会员ID:'.$custom['customid'].'--查询信息:'.serialize($card);
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'08','codemsg'=>'卡属信息与提交信息不一致'));
                }
                $logStr .= '帮卡成功!';
                $this->writeMsg($logStr);
                returnMsg(array('status'=>'1','customid'=>encode($card['customid'])));

            }elseif($card['status']=='N'){//至尊卡未激活,开始售卡流程

                $cardArr=array($cardno);

                $this->model->startTrans();
                $bool = $this->openCard($cardArr,$custom['customid'],0,'',1);
                if($bool == false){
                    $this->model->rollback();
                    $logStr .= '卡激活失败!  ErrorData:' . serialize($cardArr) . "--会员id:" . $custom['customid'];
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'07','codemsg'=>'至尊卡激活失败!'));
                }else{
                    $this->model->rollback();
                    $logStr .= "卡激活成功!";
                    $this->writeMsg($logStr);
                    returnMsg(array('status'=>'1','customid'=>encode($custom['customid'])));
                }

            }else{
                $logStr .= '非正常卡,不能绑定!';
                $this->writeMsg($logStr);
                returnMsg(array('status'=>'05','codemsg'=>'非正常卡,不能绑定'));
            }
        }
    }

    public function writeMsg($str){
        $nowTime = date('Y-m-d H:i:s' , time());
        $day=date('Ymd',time());
        $path=PUBLIC_PATH.'logs/weixinlogs/';
        $path=$path.$day.'/';
        $filename=date('Y-m-d',time()).'.log';
        $filename=$path.$filename;
        $str = '['. $nowTime .']  ' . $str ."\r\n";
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($filename,$str,FILE_APPEND);
    }

    //新卡绑定接口
    public function newcardBind(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $customid=trim($data['customid']);
        $sourceCode=trim($data['sourceCode']);
        //$pwd=trim($data['pwd']);
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $pwd=trim($data['pwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$customid.$sourceCode.$pwd);
        //echo $checkKey.'---'.$key.'<br/>';
        //echo $this->keycode.$cardno.$customid.$pwd.$sourceCode;
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
//        $pwd=$this->decodePwd($pwd);
//        if($pwd==false){
//            returnMsg(array('status'=>'04','codemsg'=>'非法密码传入'));
//        }

//        $url='http://122.0.80.215:8086/Web/login.ashx';
//        $data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
//        $res=crul_post($url,$data);
//        $res=json_decode($res,1);
//        if($res['status']['code']!='1000'){
//            returnMsg(array('status'=>'05','codemsg'=>'密码错误'));
//        }

        $customid=decode($customid);
        $where['c.cardno']=$cardno;
        $card=$this->cards->alias('c')->join('left join customs_c cc on cc.cid=c.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->field('cu.customid,c.status,c.customid cid,c.panterid')->where($where)->find();
        //echo $this->cards->getLastSql();exit;
        if($card['customid']!=0||$card['status']!='N'){
            returnMsg(array('status'=>'02','codemsg'=>'非新卡，无法绑定'));
        }
        if($pwd!='888888'){
            returnMsg(array('status'=>'04','codemsg'=>'密码错误'));
        }
        $cardArr=array($cardno);
        $this->model->startTrans();
        $bool=$this->openCard($cardArr,$customid,0,$panterid);
        //echo $bool;exit;
        if($bool==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'绑定成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'03','codemsg'=>'绑定失败'));
        }
    }

    public function oldcardBind(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $customid=trim($data['customid']);
        //$linktel=trim($data['linktel']);
        $namechinese=trim($data['namechinese']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid,$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $where['c.cardno']=$cardno;
        $card=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')->field('cu.namechinese,cu.linktel,c.status,c.customid')->where($where)->find();
        if($card['namechinese']!=$namechinese){
            returnMsg(array('status'=>'02','codemsg'=>'老卡用户信息与新卡不一致，无法绑定'));
        }
        if($card['status']!='Y'||$card['customid']==0){
            returnMsg(array('status'=>'03','codemsg'=>'老卡非正常卡，无法绑定'));
        }
        $map['cid']=$card['customid'];
        $data['customid']=$customid;
        if($this->customs_c->where($map)->save($data)){
            returnMsg(array('status'=>'1','codemsg'=>'绑定成功'));
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'绑定失败'));
        }
    }

    //获取账户余额
    public function getBalance(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'用户编号缺失'));
        }
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'03','codemsg'=>'无效秘钥，非法传入'));
        }

        $customid=decode($customid);
        //returnMsg(array('status'=>'03','codemsg'=>$customid));
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
        }
        $balance=$this->accountInfo($customid);
        $coin=$this->coinQuery($customid);
        $amount=$this->getTicketCountByCustomid($customid);

        $where['cu.customid']=$customid;
        $cardList=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($where)->field('c.cardno')->order('c.customid asc')->select();
        $cardList=array_column($cardList,'cardno');
        returnMsg(array(
                'status'=>'1',
                'account'=>array('balance'=>$balance['balance'],'jycoin'=>floatval($coin),'ticketAmount'=>floatval($amount)),
                'cardList'=>$cardList)
        );
    }

    //获取消费信息
    public function getConsumeList(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        if(empty($customid)){
            returnMsg(array('status'=>'01','codemsg'=>'用户编号缺失'));
        }
        $customid=decode($customid);
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'02','codemsg'=>'用户不存在'));
        }
        $typeArr=array('00'=>'刷卡消费','02'=>'券消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成','07'=>'退款');
        //$customid='00000016';
        $where=array('cu.customid'=>$customid,'c.status'=>'Y');
        $cards=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($where)->select();
        $cardArr=$this->serializeArr($cards,'cardno');
        $where1=array('tw.cardno'=>array('in',$cardArr),'tw.tradetype'=>array('in','00,02,13,17,21,07'),'tw.flag'=>0);
        $consumeList=M('trade_wastebooks')->alias('tw')->join('panters p on tw.panterid=p.panterid')
            ->field('tw.tradeid,p.namechinese pname,tw.placeddate,tw.placedtime,tw.flag,tw.quanid,tw.tradeamount,tw.tradepoint,tw.tradetype')
            ->where($where1)->order('tw.placeddate desc,tw.placedtime desc')->select();
        $list=array();
        foreach($consumeList as $key=>$val){
            $list[$key]['pname']=$val['pname'];
            $list[$key]['tradeamount']=$val['tradeamount'];
            $list[$key]['placeddate']=$val['placeddate'];
            $list[$key]['placedtime']=$val['placedtime'];
            $list[$key]['tradepoint']=$val['tradepoint'];
            $list[$key]['tradeid']=trim($val['tradeid']);
            //echo $val['quanid'];
            if(!empty($val['quanid'])){
                //echo '123';exit;
                $map=array('quanid'=>$val['quanid']);
                $quankind=M('quankind')->where($map)->field('quanname')->find();
                $list[$key]['quanname']=$quankind['quanname'];
            }
            $list[$key]['tradetype']=$typeArr[$val['tradetype']];
        }
        if($consumeList==false){
            returnMsg(array('status'=>'03','codemsg'=>'暂无消费记录'));
        }else{
            returnMsg(array('status'=>'1','list'=>$list));
        }
    }

    //用户劵消费
    public function ticketConsume(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $quanid=trim($data['quanid']);
        $amount=trim($data['amount']);
        $sourceCode=trim($data['sourceCode']);
        $key=trim($data['key']);
        $string=$customid.'--'.$quanid.'--'.$amount.'--'.$sourceCode.'--'.$key."\r\n";
        file_put_contents('ticket.txt',$string,FILE_APPEND);
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
        }
        if(empty($quanid)){
            returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式错误'));
        }
        $customid=decode($customid);
        $map=array('customid'=>$customid);
        $custom=$this->customs->where($map)->find();
        if($custom==false){
            returnMsg(array('status'=>'05','codemsg'=>'会员不存在'));
        }
        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();
        if($quankind==false){
            returnMsg(array('status'=>'06','codemsg'=>'营销劵不存在'));
        }
        if($quankind['enddate']<date('Ymd',time())){
            returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
        }
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        $quanAmount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')->where($where)->sum('a.amount');
        if($quanAmount<$amount){
            returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
        }
        $this->model->startTrans();
        $consumeIf=$this->ticketExe($customid,$quanid,$amount,$panterid);
        if($consumeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'09','codemsg'=>'营销劵余额不足'));
        }
    }

    //更新用户信息
    public function updateConsumeInfo(){

    }

    //建业通宝消费
    public function consume(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $coinAmount=trim($data['coinAmount']);
        $sourceCode=trim($data['sourceCode']);
        $panterid=$this->panterArr[$sourceCode]['panterid'];
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid.$coinAmount.$sourceCode);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$coinAmount)){
            returnMsg(array('status'=>'02','codemsg'=>'消费金额格式错误'));
        }
        $customid=decode($customid);
        $map=array('customid'=>$customid);
        $custom=$this->customs->where($map)->find();
        if($custom==false){
            returnMsg(array('status'=>'03','codemsg'=>'会员不存在'));
        }
        $coinAccount=$this->coinQuery($customid,$panterid);
        if($coinAccount<$coinAmount){
            returnMsg(array('status'=>'04','codemsg'=>'建业币余额不足'));
        }
        $coinConsumeArr=array('customid'=>$customid,'orderid'=>'','panterid'=>$panterid,'type'=>'01','amount'=>$coinAmount);
        $coinConsumeIf=$this->coinExe($coinConsumeArr);
        if($coinConsumeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'消费失败'));
        }
    }

    //酒店微信获取劵信息
    public function getTickets(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        //$customid='00000016';
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效卡号'));
        }
        $tickketList=$this->getTicketByCardno($cardno);
        if($tickketList==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无卡劵信息'));
        }else{
            returnMsg(array('status'=>'1','ticketList'=>json_encode($tickketList)));
        }
    }

    //酒店微信劵消费
    public function ticketConsume1(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $quanid=trim($data['quanid']);
        $amount=trim($data['amount']);
        $panterid=trim($data['panterid']);
        $orderid=trim($data['orderid']);
        $pwd=trim($data['pwd']);
        $key=trim($data['key']);

        //$string=$cardno.'--'.$quanid.'--'.$amount.'--'.$key.'<br/>';
        //file_put_contents('ticket.txt',$string,FILE_APPEND);
        $checkKey=md5($this->keycode.$cardno.$quanid.$amount.$panterid.$orderid.$pwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号缺失缺失'));
        }
        if(empty($quanid)){
            returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
        }
        if(!preg_match('/^\d+$/',$amount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费金额格式错误'));
        }
        $card=$this->checkCard($cardno);
        if($card==false){
            returnMsg(array('status'=>'05','codemsg'=>'无效卡号'));
        }
        //$url='http://122.0.82.130:8087/Web/login.ashx';
        $url='http://192.168.10.50/Web/login.ashx';
        $data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
        $res=crul_post($url,$data);
        $res=json_decode($res,1);
        if($res['status']['code']!='1000'){
            returnMsg(array('status'=>'06','codemsg'=>'密码错误'));
        }

        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();
        if($quankind==false){
            returnMsg(array('status'=>'07','codemsg'=>'营销劵不存在'));
        }
        if($quankind['enddate']<date('Ymd',time())){
            returnMsg(array('status'=>'08','codemsg'=>'营销劵已过期'));
        }
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'c.cardno'=>$cardno,'a.amount'=>array('gt',0));
        $quanAmount=$this->account->alias('a')->join('cards c on c.customid=a.customid')->where($where)->sum('a.amount');
        if($quanAmount<$amount){
            returnMsg(array('status'=>'09','codemsg'=>'营销劵余额不足'));
        }
        $this->model->startTrans();
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $customid=$card['customid'];

        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
        $tradeSql.="values('00000000','00000000','{$panterid}','{$orderid}','{$placeddate}',";
        $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
        $tradeIf=$this->model->execute($tradeSql);

        $accountSql="UPDATE ACCOUNT set amount=amount-{$amount} where customid='{$customid}' and type='02' and quanid='{$quanid}'";
        $accountIf=$this->model->execute($accountSql);
        //echo $tradeIf.$accountIf;exit;
        if($tradeIf==true&&$accountIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'10','codemsg'=>'消费失败'));
        }
    }

    //getUserInfo
    public function getUserInfo(){
        $data=getPostJson();
        $customid=trim($data['customid']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
        }
        $customid = decode($customid);
        $map=array('customid'=>$customid);
        // 查询数据
        $list = $this->customs->where($map)->field('namechinese cuname,personid,linktel')->find();
        if($list==false){
            returnMsg(array('status'=>'03','codemsg'=>'未获取信息'));
        }else{
            returnMsg(array('status'=>'1','info'=>$list));
        }
    }
    public function changePwd(){
        $data=getPostJson();
        $cardno=trim($data['cardno']);
        $oldpwd=trim($data['oldpwd']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$oldpwd.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号缺失'));
        }
        if(empty($oldpwd)){
            returnMsg(array('status'=>'03','codemsg'=>'旧密码缺失'));
        }
        if(!preg_match('/^\d{6}$/',$newpwd)){
            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->field('status')->find();
        if($card['status']!='Y'){
            returnMsg(array('status'=>'05','codemsg'=>'非正常卡，不能修改密码'));
        }
        $pwdBool=$this->checkCardPwd($cardno,$oldpwd);
        if($pwdBool==false){
            returnMsg(array('status'=>'06','codemsg'=>'旧密码错误'));
        }
        //$url='http://122.0.82.130:8087/Web/CheckCardPassword.ashx';//正式环境
        $url='http://192.168.10.50/Web/CheckCardPassword.ashx';//正式环境
        $data=json_encode(array('cardno'=>$cardno,'cardpwd'=>$oldpwd,'newcardpwd'=>$newpwd));
        $res=crul_post($url,$data);
        $res=json_decode($res,1);
        if($res['status']['code']=='1000'){
            returnMsg(array('status'=>'1','codemsg'=>'密码修改成功'));
        }else{
            returnMsg(array('status'=>'07','codemsg'=>'密码修改失败'));
        }
    }
}