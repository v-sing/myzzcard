<?php
namespace Pos\Controller;
use Think\Controller;
use Org\Util\YjDes;
use Think\Model;
class JyHomeController extends CoinController {
    protected $panterid;
    protected $appid;
    protected $appkey;
    public function _initialize(){
        parent::_initialize();
        //$this->panterid='00000675';
        $this->panterid='00000952';
        $this->appid='SOON-EMPL-0001';
        //$this->appid  = 'SOON-ZZUN-0001';
        $this->appkey='3314884dbaf88cac6679ab9f039e3b7cbaf88cac6679ab9f';
    }

    public function pointRecharge(){
        $cardno='';
        $point='';
        $userid=$this->userid;
        $userstr= substr($userid,12,4);
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $checkDate=date('Ymd');
        $cardinfo=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where(array('c.cardno'=>$cardno,'a.type'=>'04'))
            ->field('c.panterid,a.accountid,c.customid')->find();
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $purchaseid=substr($cardpurchaseid,1,16);
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql.=trim($cardno)."',0,'{$point}','{$currentDate}','{$currentTime}','1','','";
        $cardplSql.=$userid."','{$cardinfo['panterid']}','','00000000')";

        $pointid=$this->getnextcode('pointid',8);
        $pointid=$this->getFieldNextNumber('pointid');
        $enddate=date('Ymd',strtotime('next year'));
        $pointSql="INSERT INTO point_account values('{$cardinfo['accountid']}','{$point}','{$point}','{$currentDate}'";
        $pointSql.=",'{$currentTime}','{$cardinfo['panterid']}','{$cardinfo['customid']}','{$pointid}','','{$cardpurchaseid}','{$enddate}','2')";
        $pointIf=$this->model->execute($pointSql);

        $accountSql="update account set amount={$point} where customid='{$cardinfo['customid']}' and type='04' ";
        $accountIf=$this->model->execute($accountSql);
    }

    public function test(){
        $model=new Model();
        $data=array('orgid'=>'181','building'=>'建业','unint'=>'建业新生活-物业',
            'housenum'=>'周口分公司','mobilephone'=>'15238695918','password'=>md5('123456'),
            'uname'=>'王娜','idcardno'=>'412726198708074141','appid'=>$this->appid);
        $linktel=$data['linktel'];
        $name=$data['name'];
        $personid=$data['personid'];

        $model->startTrans();
        $customid=$this->getFieldNextNumber('customid');
        $sql="insert into customs(customid,namechinese,linktel,personid,career) values ";
        $sql.=" ('{$customid}','{$name}','{$linktel}','{$personid}','')";
        $customIf=$model->execute($sql);

        $data['customid']=encode($customid);
        $de = new YjDes($this->appkey);
        $sign = $de->encrypt($data);
        //echo $sign;exit;
        $data = json_encode($data,JSON_FORCE_OBJECT);
        $url = "http://ysco2o.yijiahn.com/jyo2o_web/app/user/staffregister.user";
        $result = $this->curlPost($url,$data,$sign);
        $result=json_decode($result,1);
        print_r($result);
    }
    //会员初始化
    public function customImport(){
        $array=array(
//            array("orderid"=>5337,"building"=>"5337","unint"=>"建业新生活_建业物业","housenum"=>"郑州物业管理分公司","linktel"=>"13333880799","name"=>"吴旭东","personid"=>"410105197811263310","point"=>"5345"),
            array("orderid"=>7961,"building"=>"7961","unint"=>"建业中国_许昌区域总公司","housenum"=>"宝丰森林半岛项目","linktel"=>"13781845656","name"=>"何少歌","personid"=>"410421198502255529","point"=>"7969"),
//            array("orderid"=>914,"building"=>"914","unint"=>"建业新生活_建业物业","housenum"=>"鹤壁物业管理分公司","linktel"=>"15503927366","name"=>"汤自成","personid"=>"41060319861217201X","point"=>"922"),
            array("orderid"=>8754,"building"=>"8754","unint"=>"建业中国_驻马店区域总公司","housenum"=>"西平森林半岛项目","linktel"=>"15516888857","name"=>"杨扬","personid"=>"412824198909102244","point"=>"8762"),
            array("orderid"=>7047,"building"=>"7047","unint"=>"建业中国_计划财务中心","housenum"=>"计划管理部","linktel"=>"18336337521","name"=>"王博阳","personid"=>"410105198810290338","point"=>"7055"),
        );
//        $model=new Model();
//        $sql="";
//        foreach($array as $key=>$val){
//            $map=array('linktel'=>$val['linktel'],'countrycode'=>'建业之家会员');
//            $list=$model->table('customs')->where($map)->find();
//            $customsList=$model->table('customs_c')->where(array('customid'=>$list['customid']))->find();
//            if($customsList==false){
//                $sql.="delete from customs where customid='{$list['customid']}';<br/>";
//            }
//        }
//        echo $sql;
//        exit;
        $de = new YjDes($this->appkey);
        $appid=$this->appid;
        $appkey=$this->appkey;
        //$url = "http://121.42.47.72:8080/jyo2o_web/app/user/staffregister.user";
        $url="http://o2o.yijiahn.com/jyo2o_web/app/user/staffregister.user";
        $orgid='186';
        //$url = "http://ysco2o.yijiahn.com/jyo2o_web/app/user/staffregister.user";
        //$orgid='181';
        $model=new Model();
        foreach($array as $key=>$val){
            $orderid=$val['orderid'];
            $linktel=$val['linktel'];
            $name=$val['name'];
            $personid=$val['personid'];
            $staffid=$val['staffid'];
            $cardno=$val['cardno'];
            $point=$val['point'];

            $model->startTrans();
            $customid1=$this->getFieldNextNumber('customid');
            $sql="insert into customs(customid,namechinese,linktel,personid,career,customlevel,countrycode) values ";
            $sql.=" ('{$customid1}','{$name}','{$linktel}','{$personid}','{$staffid}','建业线上会员','建业之家会员')";
            $customIf=$model->execute($sql);

            if($customIf==true){
                $data=array('orgid'=>$orgid,'building'=>$val['building'],'unint'=>$val['unint'],
                    'housenum'=>$val['housenum'],'mobilephone'=>$linktel,'password'=>md5(substr($linktel,-6)),
                    'uname'=>$name,'idcardno'=>$personid,'customid'=>encode($customid1),'appid'=>$appid);

                //print_r($data);
                $sign = $de->encrypt($data);
                $data = json_encode($data,JSON_FORCE_OBJECT);
                //$url = "http://ysco2o.yijiahn.com/jyo2o_web/app/user/staffregister.user";
                $result = $this->curlPost($url,$data,$sign);
                $result = json_decode($result,1);

                if($result['code']!='500'&&$result['code']!='100'){
                    $model->rollback();
                    $errorMsg=$orderid.':反向注册失败'."\r\n";
                    $this->exitMsg($errorMsg);
                    continue;
                }
                print_r($result);
                if($result['code']=='500'){
                    $customid=decode($result['msg']);
                    if($customid==false){
                        $model->rollback();
                        $errorMsg=$orderid.':反向注会员已存在，但会员信息返回有误'."\r\n";
                        $this->exitMsg($errorMsg);
                        continue;
                    }
                    //$sql="update customs set customid='{$customid}' where customid='{$customid1}'";
                    $sql="update customs set namechinese='{$name}',personid='{$personid}',career='{$staffid}' where customid='{$customid}'";

                    $model->execute($sql);

                    $sql1="delete from customs where customid='{$customid1}'";
                    $model->execute($sql1);
                    $staffCustomid=$customid;
                }else{
                    $staffCustomid=$customid1;
                }
                $cardno=$this->getCard();

                if($cardno==false){
                    $model->rollback();
                    $errorMsg=$orderid.':配卡失败'."\r\n";
                    $this->exitMsg($errorMsg);
                    continue;
                }

                //echo $cardno.'--'.$staffCustomid.'--'.$point.'<br/>';
                $bool=$this->openCard($cardno,$staffCustomid,0);
                if($bool==true){
                    $model->commit();
                    $errorMsg=$orderid.':初始化成功,会与编号：'.$staffCustomid.',卡号：'.$cardno."\r\n";
                    $this->exitMsg($errorMsg);
                    continue;
                }else{
                    $model->rollback();
                    $errorMsg=$orderid.':开卡失败'."\r\n";
                    $this->exitMsg($errorMsg);
                    continue;
                }

            }else{
                $errorMsg=$orderid.':会员录入失败'."\r\n";
                $this->exitMsg($errorMsg);
                continue;
            }
        }
    }

    //建业之家会员导入
    public function openCard($cardno,$customid,$point){
        $userid=$this->userid;
        $userstr= substr($userid,12,4);
        $purchaseid=$this->getFieldNextNumber("purchaseid");
        $purchaseid=$userstr.$purchaseid;
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $checkDate=date('Ymd');
        $where['cardno']=$cardno;
        $cardinfo=$this->cards->where($where)->field('panterid')->find();
        $rechargeMoney=0;
        //写入购卡单并审核
        $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','现金','";
        $customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
        $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

        //写入审核单
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
        $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);

        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        //写入购卡充值单
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
        $cardplIf=$this->model->execute($cardplSql);

        $where1['customid']=$customid;
        $card=$this->cards->where($where1)->find();
        if($card==false){
            //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
            $cardId=$customid;
        }else{
            //若存在，则需另外生成卡编号
            $cardId=$this->getFieldNextNumber("customid");
            $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
            $customIf=$this->model->execute($customSql);
        }
        $cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
        $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
        $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
        $cardAlIf=$this->model->execute($cardAlSql);

        //关联会员卡号
        $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
        $customsIf=$this->model->execute($customcSql);

        //更新卡状态为正常卡，更新卡有效期；通宝卡不激活
        $exd=date('Ymd',strtotime("+3 years"));
        $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
        $cardIf=$this->model->execute($cardSql);

        //给卡片添加账户并给账户充值
        $acid = $this->getFieldNextNumber('accountid');
        $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
        $balanceIf=$this->model->execute($balanceSql);

        $acid = $this->getFieldNextNumber('accountid');
        $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $coinSql.=$acid."','".$cardId."','0','01',NULL)";
        $coinIf=$this->model->execute($coinSql);

        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $pointAccountSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $pointAccountSql.=$acid."','".$cardId."','{$point}','04',NULL)";
        $pointAccountIf=$this->model->execute($pointAccountSql);

//        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
//        $purchaseid=substr($cardpurchaseid,1,16);
//        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
//        $cardplSql.=trim($cardno)."',0,'{$point}','{$currentDate}','{$currentTime}','1','','";
//        $cardplSql.=$userid."','{$cardinfo['panterid']}','','00000000')";

        //$pointid=$this->getnextcode('pointid',8);
//        $pointid=$this->getFieldNextNumber('pointid');
//        $enddate=date('Ymd',strtotime('next year'));
//        $pointSql="INSERT INTO point_account values('{$acid}','{$point}','{$point}','{$currentDate}'";
//        $pointSql.=",'{$currentTime}','{$cardinfo['panterid']}','{$cardId}','{$pointid}','','{$cardpurchaseid}','{$enddate}','2')";
//        $pointIf=$this->model->execute($pointSql);

        if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true){
            return true;
        }else{
            return false;
        }
    }

    //消费接口
    public function consume(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        //$datami='{"amount":"0.01","termposno":"00000547","posFlagId":"54700705","key":"9be542ce0e01779a86efd29db8a96298","customid":"MDAxOTA5NTYO0O0O"}';
        //$datami='{"amount":"501.00","termposno":"00000299","posFlagId":"29900497","key":"aeb6df5c2e3205dd65636cefcfe6a10f","customid":"MDAxMzMxNDIO0O0O"}';
        $datami = json_decode($datami,1);
        //print_r($datami);exit;
        $customid = trim($datami['customid']);
        $amount = trim($datami['amount']);
        //$paypwd = trim($datami['paypwd']);
        $termposno = trim($datami['termposno']);//终端号
        $posFlagId=trim($datami['posFlagId']);
        $key = trim($datami['key']);
        $checkKey=md5($this->keycode.$customid.$amount.$paypwd.$termposno.$posFlagId);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        if($customid==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效会员'));
        }
        $custom=$this->customs->where(array('customid'=>$customid))->field('paypwd')->find();
        if($custom==false){
            returnMsg(array('status'=>'03','codemsg'=>'查无此会员'));
        }
//        if(empty($cardpwd)){
//            returnMSg(array('status'=>'04','codemsg'=>'密码为空'));
//        }
//        if(empty($custom['paypwd'])){
//            returnMSg(array('status'=>'05','codemsg'=>'未设置支付密码，请在App上设置支付密码'));
//        }
//        if($cardpwd!=$custom['paypwd']){
//            returnMSg(array('status'=>'06','codemsg'=>'支付密码错误'));
//        }
        $cardno=$this->getJhCard($customid);
        if($cardno==false){
            returnMsg(array('status'=>'07','codemsg'=>'会员名下无建业之家卡号'));
        }
        $map = array('cardno'=>$cardno);
        $card = $this->cards->where($map)->find();
        if($card['status']!='Y'){
            returnMsg(array('status'=>'08','codemsg'=>'建业之家卡未激活或已挂失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'09','codemsg'=>'消费金额格式有误'));
        }
        $pointAmount=$this->getPointByCardno($cardno);
        if($pointAmount<$amount){
            returnMsg(array('status'=>'010','codemsg'=>'积分余额不足'));
        }
        $pointConsumeArr=array('cardno'=>$cardno,'orderid'=>'pos_'.$posFlagId,
            'panterid'=>$this->panterid,'amount'=>$amount, 'termno'=>$termposno);
        //积分消费订单列表，二维数组
        $pointConsumeIf=$this->pointExeByCardno($pointConsumeArr);
        if($pointConsumeIf==true){
            returnMsg(array('status'=>'1','info'=>$pointConsumeIf));
        }else{
            returnMsg(array('status'=>'011','codemsg'=>'消费失败'));
        }
    }

    public function cancleOrder(){
        $datami = trim($_POST['datami']);
        $datami=json_decode($datami,1);
        $tradeid=trim($datami['tradeid']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$tradeid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($tradeid)){
            returnMsg(array('status'=>'03','codemsg'=>'订单编号缺失'));
        }
        //$tradeid='078520160806103219';
        $trdeWastebooks=M('trade_wastebooks');
        $map['tradeid']=$tradeid;
        $map['flag']=0;
        $map['tradetype']=array('in','00,01');
        $tradeinfo=$trdeWastebooks->where($map)
            ->field("tradeid,tradeamount,tradepoint,cardno,eorderid")->find();
        if($tradeinfo==false){
            returnMsg(array('status'=>'04','codemsg'=>'无此订单支付信息'));
        }
        $this->model->startTrans();
        $pointSql="UPDATE account SET amount=nvl(amount,0)+".$tradeinfo['tradeamount']." WHERE customid='{$tradeinfo['customid']}' and type='04'";
        $pointIf=$this->model->execute($pointSql);
        $tradeMap=array('tradeid'=>$tradeinfo['tradeid']);
        $tradeData=array('tradetype'=>'11');
        $tradeIf=$trdeWastebooks->where($tradeMap)->save($tradeData);
        $map2=array('tradeid'=>$tradeinfo['tradeid']);
        $pointConsumeList=M('point_consume')->where($map2)->select();
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
        if($c=count($pointConsumeList)){
            $pointConsumeIf=true;
        }
        if($pointConsumeIf==true&&$tradeIf==true&&$pointIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'账户支付撤销成功',
                'balance'=>floatval($tradeinfo['tradeamount']),
                'coin'=>floatval($tradeinfo['tradeamount']),'time'=>time()));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'05','codemsg'=>'账户支付撤销失败','time'=>time()));
        }
    }

    //获得积分账户
    public function getPointAccount(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $customid = trim($datami['customid']);
        $key = trim($datami['key']);

        $checkKey=md5($this->keycode.$customid);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$customid='MDAxOTA3MDcO0O0O';
        $customid=decode($customid);
        if($customid==false){
            returnMsg(array('status'=>'02','codemsg'=>'无效会员'));
        }
        $cardno=$this->getJhCard($customid);
        if($cardno==false){
            returnMsg(array('status'=>'03','codemsg'=>'会员名下无建业之家卡号'));
        }
        $pointAmount=$this->getPointByCardno($cardno);
        returnMsg(array('status'=>'1','amount'=>$pointAmount));
    }

    //通过会员编号获得建业之家卡号
    protected function getJhCard($customid){
        if(empty($customid)) return false;
        $map=array('cu.customid'=>$customid,'c.cardkind'=>'6680');
        $cardInfo=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($map)->find();
        if($cardInfo==false){
            return false;
        }else{
            return $cardInfo['cardno'];
        }
    }


    protected function getPointByCardno($cardno){
        $panterid=$this->panterid;
        $map=array('c.cardno'=>$cardno,'pa.panterid'=>$panterid);
        $map['_string']='pa.enddate=0 or pa.enddate>='.date('Ymd');
        $amount=$this->cards->alias('c')->join('point_account pa on c.customid=pa.cardid')
            ->where($map)->sum('remindamount');
        //echo $this->cards->getLastSql();
        return $amount;
    }

    //获取建业之家卡号
    protected function getCard(){
        //$map=array('panterid'=>$this->patnerid,'status'=>'N');
        $map=array('panterid'=>$this->panterid,'status'=>'N','cardkind'=>'6680');
        $card=$this->cards->where($map)->field('cardno')->find();
        if($card==false) return false;
        return $card['cardno'];
    }

    protected function getTradeList(){
        $customid=$_REQUEST['customid'];
        $customid=decode($customid);
        $map=array('c.cardkind'=>'6680','cu.customid'=>$customid,'tw.tradetype'=>'00');
        $list=M('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->join('trade_wastebooks tw on tw.cardno=c.cardno')
            ->where($map)->field('tw.tradepoint,tw.tradeid,tw.placeddate,tw.placedtime')
            ->select();
    }

    protected function exitMsg($msg){
        $filename='error.log';
        $path=PUBLIC_PATH.'jyhome/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$msg,FILE_APPEND);
        //echo $msg;
        //exit($msg);
    }

    private function curlPost($url,$data,$sign){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type:application/json',"sign:{$sign}"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if($output == false){
            return curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }
}