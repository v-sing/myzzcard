<?php
namespace Pos\Controller;
use Home\Model\CustomModel;

require  './ThinkPHP/Library/Jpush/autoload.php';
use JPush\Client as JPush;
class QifengController extends CoinController
{
    /*
   * var $userid 充值操作员id
   *
   * var $cardPanterid 卡所属商户ip
   *
   * var $parent 大食堂所属机构
   *
   * var $tradetype退卡时交易类型
   *
   * var $chongPanterid 充值退卡商户
   *
   * var $frozen卡费即卡中冻结金额 不能消费
   *
   * var $QemPanterid 前台紧急扣款
   */
    private $greenuserid;
    private $cardPanterid;
    private $parent;
    private $tradetype;
    private $reFoods;
    private $emPanterid;
    private $chongPanterid;
    private $frozen;
    protected $keycode;
    protected $gift;
    protected $paymenttype;

    protected $returnPanterid;
    protected $client;
    public function _initialize()
    {
        parent::_initialize();
        $this->userid       = C('qf_userid');
        $this->cardPanterid = C('qf_cardpanterid');
        $this->chongPanterid= C('qf_chongpanterid');
        $this->parent       = C('qf_parent');
        $this->tradetype    = '30';
        $this->reFoods      = '31';
        //紧急预案商户号
        $this->emPanterid   = C('qf_empanterid');
        $this->frozen= 10 ;
        //前台紧急扣款
        $this->QemPanterid  = C('qf_qempanterid');
        //数字签名
        $this->keycode       = 'kfc0002kfc';
        $this->returnPanterid = C('qf_returnpanterid');
        $this->gift          = C('qf_gift');
        $this->paymenttype = [
            '00'=>'现金','01'=>'银行卡','09'=>'微信','10'=>'支付宝'
        ];

        $this->client = new JPush('47c90447f1f8eca19b13da20','ccde9b0166a4a797a58877ec');;
    }
    //单卡充值
    public function cardRecharge(){
        $cardno      = trim(I('post.cardno',''));//卡号
        $paymenttype = trim(I('post.paymenttype',''));//现金银行支付的类型
        $totalmoney  = trim(I('post.totalmoney',''));//总金额
        $panterid    = trim(I('post.panterid',''));//商务号
        $termno      = trim(I('post.pos_id',''));
        $en          = trim(I('post.en',''));
        $key=$_POST['key'];
        $this->checkSign($key,md5($this->keycode.$cardno.$paymenttype.$totalmoney.$panterid.$termno));
        $this->writeLog(__FUNCTION__,$_POST);

        $panterid==$this->chongPanterid||returnMsg(array('status'=>'07','codemsg'=>'此商户没有充值权限'));
        $cardInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        if($customInfo['customlevel']=='启封故园食堂团购客户'){
            returnMsg(['status'=>'30','codemsg'=>'此卡是团购卡不能单独充值']);
        }

//	    $this->chargeLimit($totalmoney);

        $accountInfo=$this->accountInfo(__FUNCTION__,$cardInfo['customid']);

//        bcadd($accountInfo['amount'],$totalmoney,2)<=5000 || returnMsg(['status'=>'021','codemsg'=>'充值金额禁止大于5000']);

        $paytype = $paymenttype;
        switch($paymenttype){
            case "00":
                $paymenttype="现金";
                break;
            case "01":
                $paymenttype="银行卡";
                break;
            case "02":
                $paymenttype="支票";
                break;
            case "03":
                $paymenttype="汇款";
                break;
            case "04":
                $paymenttype="网上支付";
                break;
            case "05":
                $paymenttype="转账";
                break;
            case "06":
                $paymenttype="内部转账";
                break;
            case "07":
                $paymenttype="赠送";
                break;
            case "08":
                $paymenttype="其他";
                break;
            case "09":
                $paymenttype="微信";
                break;
            case "10":
                $paymenttype="支付宝";
                break;
        }
        if($cardInfo['cardfee']=='3'){
            $flag = '06';
        }else{
            $flag = '01';
        }
        //开启事务操作
        $this->model->startTrans();
        //新增 对不退卡的充值管理
        if($customInfo['cardflag']==='on'){
            $num = $customInfo['num'];
        }else{
            $num = $this->customsCardnum($customInfo);
        }
        $map = ['cardno'=>$cardno,'cutsomid'=>$customInfo['customid'],'amount'=>$totalmoney,
                'userid'=>$this->userid,'paymenttype'=>$paymenttype,'panterid'=>$panterid,
                'num' =>$num,'purchaseid'=>$this->getFieldNextNumber('purchaseid'),
                'auditid'=>$this->getFieldNextNumber('auditid'),'cardpurchaseid'=>$this->getFieldNextNumber('cardpurchaseid'),
                'paytype'=>$paytype,'termno'=>$termno
               ];
        $bool=$this-> exChong($map,$flag);
        if($bool['msg']===true){
            $this->model->commit();
            $balanceInfo = $this->model->table('account')->where(['type'=>'00','customid'=>$cardInfo['customid']])->field('amount')->find();
            $this->writeLog('single+success',['cardno'=>$cardno,'cardnum'=>$customInfo['num'],'purchaseId'=>$bool['purchaseid'],'balance'=>$balanceInfo['amount'],'org'=>$accountInfo['amount']]);

            if($en!=''){
                $msg = ['charge'=>$totalmoney,'paytype'=>$paymenttype,'time'=>date('Y-m-d H:i:s'),'cardno'=>$cardno,'payamount'=>$totalmoney,'name'=>'启封充值点','title'=>'本次充值'];
                $this->jpush($en,json_encode($msg));
            }
            $cardtype = $this->cardKind($cardno);
            returnMsg(['status'=>'1','codemsg'=>'充值成功','purchaseId'=>$bool['purchaseid'],
                'balance'=>floatval($balanceInfo['amount']),'org'=>floatval($accountInfo['amount']),
                'type'=>$cardtype.'充值'
            ]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'22','codemsg'=>'充值失败']);
        }
    }
    /*
   * execute charge single card
   * @param obj $obj 操作的表的实例化对象
   * @param array $map 充值需要的相关参数
   */
    protected function exChong( $map,$flag){
        $cardno       = $map['cardno'];
        $customid     = $map['cutsomid'];
        $chargeAmount = $map['amount'];
        $userid       = $map['userid'];
        $paymenttype  = $map['paymenttype'];
        $panterid     = $map['panterid'];
        $currentDate  = date('Ymd');
        $checkDate    = date('Ymd');
        $num          = $map['num'];
        //审核单号
        $auditid      = $map['auditid'];
        //充值单号
        $purchaseid   = $map['purchaseid'];
        $cardpurchaseid= $map['cardpurchaseid'];
        $paytype      = $map['paytype'];
        $termno       = $map['termno'];

        $customplSql="insert into custom_purchase_logs values('".$customid."','{$purchaseid}','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$chargeAmount."',NULL,'".$chargeAmount."',0,'".$chargeAmount."','".$chargeAmount;
        $customplSql.="',1,'','','1','".$panterid."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf = $this->model->execute($customplSql);

        //写入审核单
        $auditlogsSql="insert into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
        $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        $auditlogsIf = $this->model->execute($auditlogsSql);

        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$chargeAmount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        //更新账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$chargeAmount." where customid='".$customid."' and type='00'";
        $balanceIf = $this->model->execute($balanceSql);

        $time = date('H:i:s',time());
        $orderid = $this->getFieldPrimaryNumber('orderid',16);
        $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,placeddate,placedtime,price,num,payamount,customid,paytype,termno) VALUES ('{$orderid}',";
        $orderSql.="'{$purchaseid}','{$cardno}','{$num}','{$flag}','{$panterid}','{$currentDate}','{$time}','{$chargeAmount}','1','{$chargeAmount}','{$customid}','{$paytype}','{$termno}')";
        $orderif= $this->model->execute($orderSql);
        if($customplIf==true && $auditlogsIf==true && $cardplIf==true && $orderif==true ){
            return ['msg'=>true,'purchaseid'=>$purchaseid];
        }else{
            return false;
        }
    }
    //退卡前 返回卡信息
    public function getConsume(){
        $cardno = $_POST['cardno'];//卡号
        $key    = $_POST['key'];
        $this->checkSign($key,md5($this->keycode.$cardno));
        $this->writeLog(__FUNCTION__,$_POST);

        $cardnoInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardnoInfo['customid']);
        $accountInfo    = $this->accountInfo(__FUNCTION__,$cardnoInfo['customid']);
        if($customInfo['cardflag']=='off'){
            $error=['status'=>'021','codemsg'=>'此卡已退卡'];
            $this->errorHandle(__FUNCTION__,$error,'退卡前查询卡信息');
        }
        $this->cannotReturn($customInfo['num'],$cardno);
        if($customInfo['customlevel']=='启封故园食堂团购客户'){
            $isteamFLag ='2';
            $mapTeam = ['cardno'=>$cardno,'cardnum'=>$customInfo['num']];
            //获取团冲单号
            $teamid=$this->getTeamid($mapTeam);
            $this->writeLog('teamtui_get',['cardno'=>$cardno,'teamid'=>$teamid]);
            //充值总金额 $chongSum
            $map['teamid'] = $teamid;
            $map['flag']   = '01';
            $list     = $this->model->table('qf_order')->where($map)->field('customid')->select();
            $chongSum = $this->model->table('qf_order')->where($map)->field('nvl(sum(price),0) amount')->find()['amount'];

            //查询消费总金额 $conSum
            $map['flag'] = '02';
            $conSum   = $this->model->table('qf_order')->where($map)->field('nvl(sum(price*num),0) amount')->find()['amount'];

            $map['flag'] = '04';
            //退菜金额
            $refundSum = $this->model->table('qf_order')->where($map)->field('nvl(sum(price*num),0) amount')->find()['amount'];

            //accSum 账户余额合计
            $cuidlist = array_column($list,'customid');
            $customdWhere=['customid'=>['in',$cuidlist]];
            $accSum=$this->teamAcc($cuidlist);
            //合卡消费 出入账
            $combineSum=$this->teamCombine($cuidlist);
            $res['chong']=$chongSum;
            $res['consume']=$conSum;
            $res['account']=$accSum;
            $res['termid']=$teamid;
            $res['consume']=bcsub($res['consume'],$refundSum,2);
            $this->writeLog('teamtui_info',$res);
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$res,'flag'=>$isteamFLag]);
        }else{
            $isteamFlag='1';
            $this->writeLog('singletui_get',['cardno'=>$cardno]);
            $res['account'] = round($accountInfo['amount'],2);
            //查询充值
            //分为散卡与不卡退卡

            $map = ['cardno'=>$cardno,'cardnum'=>$customInfo['num']];
            $res['chong']   = $this->searchChong(__FUNCTION__,$map);
            // 查询交易记录
            $res['consume'] = $this->searchConsume($map);
            //查询 退菜金额
            $res['refund']=$this->getRefund($map);
            //查询 是否有合卡消费  合卡只针对团卡
//            $combine=$this->combineDetail(['cardno'=>$cardno,'num'=>$customInfo['num']]);
//            $res['in'] =(string)$combine['in'];
//            $res['out']=(string)$combine['out'];
            $res['in']   = '0';
            $res['out']  = '0';
            $this->writeLog('singletui_info',['cardno'=>$cardno,'info'=>json_encode($res)]);
            //交易详情
            $orderInfo = $this->orderInfo(['cardno'=>$cardno,'cardnum'=>$customInfo['num'],'flag'=>['in',['02','04']]]);
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$res,'flag'=>$isteamFlag,'order'=>$orderInfo]);
        }
    }
    //退卡
    public function returnCard(){
        $cardno    = trim(I('post.cardno',''));//卡号
        $totalmoney= trim(I('post.totalmoney',''));//总金额
        $panterid  = trim(I('post.panterid',''));//商务号
        $termno    = trim(I('post.pos_id',''));
        $tradetype = trim(I('post.tradetype',''));
        $paytype   = trim(I('post.paymenttype',''));
        $key       = trim(I('post.key',''));
        $en        = trim(I('post.en',''));

//        dump(md5($this->keycode.$cardno.$totalmoney.$panterid.$termno.$tradetype.$paytype));exit;
        $this->checkSign($key,md5($this->keycode.$cardno.$totalmoney.$panterid.$termno.$tradetype.$paytype));

        $tradetype==$this->tradetype||returnMsg(['status'=>'023','codemsg'=>'非退卡交易类型,请确认']);
        $this->valiPanterid(__FUNCTION__,$panterid,'退卡是校验商户号');
        $panterid==$this->returnPanterid||returnMsg(['status'=>'09','codemsg'=>'此商户没有退卡权限']);
        $cardInfo   = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        $accountInfo= $this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        $customInfo['cardflag']==='on'||returnMsg(array('status'=>'08','codemsg'=>'此卡'.$cardno.'未充值不能退卡'));
        $this->cannotReturn($customInfo['num'],$cardno);
        $this->teamNotReturn($customInfo['num'],$cardno);
        $accountInfo['amount']==$totalmoney||returnMsg(['status'=>'06','codemsg'=>'退卡金额与卡余额不一致']);

        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$totalmoney}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','{$this->tradetype}','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $customif=M('customs')->where(['customid'=>$cardInfo['customid']])->save(['cardflag'=>'off']);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>'0']);
        $orderid = $this->getFieldPrimaryNumber('orderid',16);
        $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,price,num,placeddate,placedtime,payamount,customid,paytype,termno) VALUES ('{$orderid}','{$tradeid}',";
        $orderSql.="'{$cardno}','{$customInfo['num']}','03','{$panterid}','{$totalmoney}','1','{$placeddate}','{$placedtime}','{$totalmoney}','{$cardInfo['customid']}','{$paytype}','{$termno}')";
        $orderif=$this->model->execute($orderSql);
        if($tradeif==true&&$customif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            $this->writeLog('singletui_success',['cardno'=>$cardno,'tradeid'=>$tradeid,'refund'=>$accountInfo['amount']]);
            $msg = ['charge'=>$totalmoney,'paytype'=>$this->paymenttype[$paytype],'time'=>date('Y-m-d H:i:s'),'cardno'=>$cardno,'payamount'=>$totalmoney,'name'=>'启封退卡点','title'=>'本次退卡'];
            if($en!=''){
                $this->jpush($en,json_encode($msg));
            }
            returnMsg(['status'=>'1','codemsg'=>'退卡成功','tradeid'=>$tradeid,'refund'=>round($accountInfo['amount'],2),'type'=>'游客退卡']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'07','codemsg'=>'退卡失败,请重试']);
        }
    }
    //获取商品类别
    public function goods(){
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        //01上架 02 所有的菜品
        $status=$_POST['stauts'];
        //flag 1正常菜品 2散装菜品
        $flag=$_POST['flag'];
        $this->checkSign($key,md5($this->keycode.$panterid.$status.$flag));
        $this->valiPanterid(__FUNCTION__,$panterid,'显示菜品接口验证商户');


        $where['panterid']=$panterid;
        if($status=='01'){
            $where['status']='1';
        }
        if($flag=='1'){
            $where['type']='1';
        }elseif($flag=='2'){
            $where['type']='2';
        }
        $field='type,goodsname,price,goodsid,status';
        $goods=M('qf_goods')
            ->where($where)->field($field)->order('price asc')->select();
        if($flag=='2'){
            foreach($goods as $key=>$val){
                $val['price']='';
                $val['name'] = '其他';
                $goods[$key]=$val;
            }
        }
        if($goods==true){
            if($flag!='2'){
                foreach($goods as $key=>$val){
                    $val['price']=floatval($val['price']);
                    $val['name'] = '主食';
                    $goods[$key]=$val;
                }
            }
            returnMsg(['status'=>'1','codemsg'=>'获取数据成功','data'=>$goods]);
        }else{
            returnMsg(['status'=>'03','codemsg'=>'未查询到数据','data'=>'']);
        }
    }
    //下单消费
    public function getOrder(){
        $cardno =$_POST['cardno'];//卡号
        $panterid=$_POST['panterid'];
        $price=$_POST['price'];
        $termno=$_POST['pos_id'];
        $amount=$_POST['amount'];
        $order=$_POST['data'];
        $key=$_POST['key'];
        $en = $_POST['en'];
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$price.$amount.$order.$termno));

        $cardInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        if($customInfo['cardflag']=='off'||$customInfo['cardflag']==null){
            $error=['status'=>'021','codemsg'=>'此卡已退卡'];
            $this->errorHandle(__FUNCTION__,$error,'下单消费是查询会员信息');
        }
        $frozen = $this->giftCardno($customInfo);
        $accountInfo = $this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        $panterInfo=$this->valiPanterid(__FUNCTION__,$panterid,'下单消费时检验商户有效性');
        $order=json_decode($order,true);
        $sum = $this->valiOrderAmount($order,$panterid);
        $sum==$price||returnMsg(['status'=>'06','codemsg'=>'订单总金额不对']);
        bcsub($accountInfo['amount'],$price,2)>=$frozen||returnMsg(['status'=>'09','codemsg'=>"卡余额不足"."\n"."余额：".floatval($accountInfo['amount']),'amount'=>$accountInfo['amount']]);
        $yue=bcsub($accountInfo['amount'],$price,2);

        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';

        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        if($customInfo['num']=='0'){
            $payWhere['customid'] = $customInfo['customid'];
            $payWhere['num']      = $customInfo['num'];
            $teamid = '';
        }else{
            $payWhere['cardno']   = $cardno;
            $payWhere['num']      = $customInfo['num'];
            $teamid = $this->getTeamidNull($payWhere);
        }
//        $paytype = $this->getRelateInfo($payWhere,'paytpe');
        $this->model->startTrans();
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单
        foreach($order as $val){
            $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();

            $list==true|| returnMsg(['status'=>'033','codemsg'=>'传入商品异常未找到']);
            $orderid = $this->getFieldPrimaryNumber('orderid',16);

            if($val['type']=='1'){
                $payamount = bcmul($list['price'],$val['num'],2);
                $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid,payamount,paytype,customid,teamid,termno) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfo['num']}','{$val['goodsid']}','{$val['type']}','{$list['goodsname']}','{$list['price']}','{$val['num']}','02','{$placeddate}','{$placedtime}','{$panterid}','{$payamount}',";
                $orderSql.="'','{$customInfo['customid']}','{$teamid}','{$termno}')";
            }else{
                $payamount = bcmul($val['price'],$val['num'],2);
                $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid,payamount,paytype,customid,teamid,termno) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfo['num']}','{$val['goodsid']}','{$val['type']}','{$list['goodsname']}','{$val['price']}','{$val['num']}','02','{$placeddate}','{$placedtime}','{$panterid}','{$payamount}',";
                $orderSql.="'','{$customInfo['customid']}','{$teamid}','{$termno}')";
            }
            $orderif=$this->model->execute($orderSql);
            if(!$orderif){
                break;
            }
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            $cardkind = $this->cardKind($cardno);
            $msg = ['charge'=>$price,'paytype'=>$cardkind.'消费',
                'time'=>date('Y-m-d H:i:s'),'cardno'=>$cardno,'payamount'=>$payamount,
                'name'=>$panterInfo['namechinese'],'title'=>'客户消费','paytype'=>'一卡通刷卡'];
            if($en!=''){
                $this->jpush($en,json_encode($msg));
            }
            returnMsg(['status'=>'1','codemsg'=>'消费成功','tradeid'=>$tradeid,'balance'=>$yue,'type'=>$cardkind.'消费']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }
    //下单消费 是校验订单金额是否正确
    protected function valiOrderAmount($order,$panterid){
        $sum = 0;
        foreach($order as $val){
            if($val['type']=='1'){
                $list=M('qf_goods')->where(['goodsid'=>$val['goodsid']])->find();
                if($list==true){
                    $sum=bcadd($sum,bcmul($list['price'],$val['num'],2),2);
                }else{
                    returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }else{
                $list=M('qf_goods')->where(['goodsid'=>$val['goodsid']])->find();
                if($list==true){
                    $sum=bcadd($sum,$val['price'],2);
                }else{
                    returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }
        }
        return $sum;
    }
    //退菜获取订单
    public function returnFood(){
        $cardno = $_POST['cardno'];
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid));

        $cardInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $this->valiPanterid(__FUNCTION__,$panterid,'退菜获取订单检验商户');
        $customInfo =$this->customInfo(__FUNCTION__,$cardInfo['customid']);
        if($customInfo['cardflag']=='off'){
            returnMsg(['status'=>'09','codemsg'=>'此卡已经退卡']);
        }
        //flag 01充值 02消费 03 退卡 04退菜
        if($customInfo['num']=='0'){
            $normal=['type'=>'1','panterid'=>$panterid,'customid'=>$customInfo['customid'],'cardnum'=>$customInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];
            $other=['type'=>'2','panterid'=>$panterid,'customid'=>$customInfo['customid'],'cardnum'=>$customInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];

            $resNormal = $this->searchGoodsClassNoReturn($normal);
            $resNormal = $this->returnGoodsNoReturn($normal,$resNormal);

            $resOther=$this->searchGoodsClassNoReturn($other);
            if($resOther==true){
                $resOther=$this->returnGoodsNoReturn($other,$resOther);
            }
        }else{
            $normal=['type'=>'1','panterid'=>$panterid,'cardno'=>$cardno,'cardnum'=>$customInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];
            $other=['type'=>'2','panterid'=>$panterid,'cardno'=>$cardno,'cardnum'=>$customInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];


            $resNormal=$this->searchGoodsClass($normal);
            $resNormal=$this->returnGoods($normal,$resNormal);
            //散装菜品

            $resOther=$this->searchGoodsClass($other);
            if($resOther==true){
                $resOther=$this->returnGoods($other,$resOther);
            }
        }



        $data=$this->combine($resNormal,$resOther);
        foreach($data as $key=>$val){
            $val['price']=floatval($val['price']);
            $data[$key]=$val;
        }
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$data]);
    }
    //分类查询 获取消费菜品
    protected function searchGoodsClass($map){
        $sql="SELECT sum(num) as num,goodsname,price,goodsid,type from qf_order where type='{$map['type']}' AND panterid='{$map['panterid']}' AND cardno='{$map['cardno']}' AND cardnum='{$map['cardnum']}' ";
        $sql.="AND flag='{$map['flag']}' AND placeddate='{$map['placeddate']}' group by goodsname,price,goodsid,type ";
        return $this->model->query($sql);
    }
    //分类查询 获取消费菜品 针对不克退卡
    protected function searchGoodsClassNoReturn($map){
        $sql="SELECT sum(num) as num,goodsname,price,goodsid,type from qf_order where type='{$map['type']}' AND panterid='{$map['panterid']}' AND customid='{$map['customid']}' AND cardnum='{$map['cardnum']}' ";
        $sql.="AND flag='{$map['flag']}' AND placeddate='{$map['placeddate']}' group by goodsname,price,goodsid,type ";
        return $this->model->query($sql);
    }
    //把相同菜品消费 与退菜的数量合并
    private function returnGoods($map,$data){
        $map['flag']='04';
        if($map['type']=='1'){
            $lists=$this->searchGoodsClass($map);
            if($lists==true){
                foreach($lists as $k=>$v){
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }else{
            $lists=$this->searchGoodsClass($map);
            if($lists==true){
                foreach($lists  as $v){
                    //价格一样 商品id一样才删除
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }
    }
    //把相同菜品消费 与退菜的数量合并
    private function returnGoodsNoReturn($map,$data){
        $map['flag']='04';
        if($map['type']=='1'){
            $lists=$this->searchGoodsClassNoReturn($map);
            if($lists==true){
                foreach($lists as $k=>$v){
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }else{
            $lists=$this->searchGoodsClassNoReturn($map);
            if($lists==true){
                foreach($lists  as $v){
                    //价格一样 商品id一样才删除
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }
    }
    //散装菜品订单 与正常菜品 订单合并
    private function combine($resNormal,$resOther,$method){
        if($resNormal==true||$resOther==true){
            if($resNormal==true){
                foreach($resNormal as $val){
                    $data[]=$val;
                }
            }
            if($resOther==true){
                foreach($resOther as $val){
                    $data[]=$val;
                }
            }
            if($resNormal==true&&$resOther==true){
                $data=array_merge($resNormal,$resOther);
            }
        }
        if($resNormal==false && $resOther==false){
            returnMsg(['status'=>'03','codemsg'=>'当日没有交易数据']);
        }
        return $data;
    }
    //退菜 操作 执行
    public function returnHandle(){
        $cardno =$_POST['cardno'];//卡号
        $panterid=$_POST['panterid'];
        $price=$_POST['price'];
        $termno=$_POST['pos_id'];
        $order=$_POST['data'];
        $tradetype=$_POST['tradetype'];
        $key=$_POST['key'];
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$price.$order.$termno.$tradetype));

        $tradetype==$this->reFoods||returnMsg(array('status'=>'07','codemsg'=>'不是退菜交易，不能退菜'));
        $order=json_decode($order,true);
        $cardnInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardnInfo['customid']);
        $accounInfo = $this->accountInfo(__FUNCTION__,$cardnInfo['customid']);

        $sum=0;
        $customInfo['cardflag']=='on'||returnMsg(['status'=>'07','codemsg'=>'此卡已退卡,不能退菜']);

        //验证菜品修改
        if($customInfo['num']=='0'){
            foreach($order as $val){
                $totalNum=$this->refundNumCustomid($val,$panterid,$customInfo['customid'],$customInfo['num']);
                if($totalNum<$val['num']){
                    returnMsg(['status'=>'09','codemsg'=>'该商品:'.$val['goodsname'].'数量不足']);
                }
                if($val['type']=='1'){
                    $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();
                    if($list==true){
                        $sum=bcadd($sum,bcmul($list['price'],$val['num'],2),2);
                    }else{
                        returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                    }
                }else{
                    $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();
                    if($list==true){
                        if($val['num']=='1'){
                            $sum=bcadd($sum,$val['price'],2);
                        }else{
                            $sum=bcadd($sum,bcmul($val['price'],$val['num'],2),2);
                        }
                    }else{
                        returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                    }
                }
            }
            $teamid  = '';

        }else{
            foreach($order as $val){
                $totalNum=$this->refundNum($val,$panterid,$cardno,$customInfo['num']);
                if($totalNum<$val['num']){
                    returnMsg(['status'=>'09','codemsg'=>'该商品:'.$val['goodsname'].'数量不足']);
                }
                if($val['type']=='1'){
                    $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();
                    if($list==true){
                        $sum=bcadd($sum,bcmul($list['price'],$val['num'],2),2);
                    }else{
                        returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                    }
                }else{
                    $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();
                    if($list==true){
                        if($val['num']=='1'){
                            $sum=bcadd($sum,$val['price'],2);
                        }else{
                            $sum=bcadd($sum,bcmul($val['price'],$val['num'],2),2);
                        }
                    }else{
                        returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                    }
                }
            }
            $teamid = $this->getTeamidNull(['cardno'=>$cardno,'cardnum'=>$customInfo['num']]);
        }


        $sum==$price||returnMsg(['status'=>'06','codemsg'=>'订单总金额不对']);

        $yue=bcadd($accounInfo['amount'],$price,2);


        $this->model->startTrans();
        //生成消费记录
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardnInfo['customid']}','{$cardno}','{$placedtime}','{$tradetype}','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardnInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单
        foreach($order as $val){
           $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();

            $orderid = $this->getFieldPrimaryNumber('orderid',16);
            if($val['type']=='1'){
                $payamount = bcmul($list['price'],$val['num'],2);
                $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid,payamount,teamid,customid) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}'";
                $orderSql.=",'{$val['price']}','{$val['num']}','04','{$placeddate}','{$placedtime}','{$panterid}','{$payamount}','{$teamid}','{$customInfo['customid']}')";
            }else{
                $payamount = bcmul($val['price'],$val['num'],2);
                $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid,payamount,teamid,customid) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$val['price']}',";
                $orderSql.="'{$val['num']}','04','{$placeddate}','{$placedtime}','{$panterid}','{$payamount}','{$teamid}','{$customInfo['customid']}')";
            }
            $orderif=$this->model->execute($orderSql);
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'退菜成功','tradeid'=>$tradeid,'balance'=>$yue]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }
    /* 返回能可退菜数量
    * @param array $val 订单数据
    * @param string $panterid 商户号
    * $param string $cardno 卡号
    * $cardnum string $cardnum 卡使用次数
    * @return int $getNum 返回总共可以退掉菜品数量
    */
    private function refundNum($val,$panterid,$cardno,$cardnum){
        $getNum=0;
        $mapNum['placeddate']=date('Ymd');
        $mapNum['cardno']=$cardno;
        $mapNum['cardnum']=$cardnum;
        $mapNum['panterid']=$panterid;
        $mapNum['goodsid']=$val['goodsid'];
        $mapNum['type']=$val['type'];
        $mapNum['price']=$val['price'];
        $mapNum['flag']='02';
        $consumeSum=M('qf_order')->where($mapNum)->select();
        if($consumeSum==true){
            $getNum=bcadd($getNum,array_sum(array_column($consumeSum,'num')),0);
        }else{
            return $getNum;
        }
        //查询退菜数量
        $mapNum['flag']='04';
        $refundSum=M('qf_order')->where($mapNum)->select();
        if($refundSum==false){
            return $getNum;
        }else{
            $getNum=bcsub($getNum,array_sum(array_column($refundSum,'num')),0);
            if($getNum<0){
                returnMsg(['status'=>'09','codemsg'=>'该菜品:'.$val['goodsname']."数量为不足，不能退菜"]);
            }
            return $getNum;
        }
    }
    private function  refundNumCustomid($val,$panterid,$customid,$cardnum){
        $getNum=0;
        $mapNum['placeddate']=date('Ymd');
        $mapNum['customid']=$customid;
        $mapNum['cardnum']=$cardnum;
        $mapNum['panterid']=$panterid;
        $mapNum['goodsid']=$val['goodsid'];
        $mapNum['type']=$val['type'];
        $mapNum['price']=$val['price'];
        $mapNum['flag']='02';
        $consumeSum=M('qf_order')->where($mapNum)->select();
        if($consumeSum==true){
            $getNum=bcadd($getNum,array_sum(array_column($consumeSum,'num')),0);
        }else{
            return $getNum;
        }
        //查询退菜数量
        $mapNum['flag']='04';
        $refundSum=M('qf_order')->where($mapNum)->select();
        if($refundSum==false){
            return $getNum;
        }else{
            $getNum=bcsub($getNum,array_sum(array_column($refundSum,'num')),0);
            if($getNum<0){
                returnMsg(['status'=>'09','codemsg'=>'该菜品:'.$val['goodsname']."数量为不足，不能退菜"]);
            }
            return $getNum;
        }
    }
    //获取消费详情
    public function detail(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$cardno.$panterid));
//        $panterid==$this->chongPanterid||returnMsg(array('status'=>'08','codemsg'=>'该商户没有查询订单详情权限'));
        $cardInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        $accountInfo= $this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        $customInfo['cardflag']=='on'||returnMsg(array('status'=>'07','codemsg'=>'该卡已经退卡,无法查询明细'));

        $day = date('Ymd');
        if($customInfo['num']!='0'){
            $map=['cardno'=>$cardno,'flag'=>'02','cardnum'=>$customInfo['num']];
            $mapWhere=['o.cardno'=>$cardno,'o.cardnum'=>$customInfo['num'],'o.flag'=>'02'];
            $rewhere=['cardno'=>$cardno,'cardnum'=>$customInfo['num'],'flag'=>'04'];
            $reMap=['o.cardno'=>$cardno,'o.cardnum'=>$customInfo['num'],'o.flag'=>'04'];

            $combine=$this->combineDetail(['cardno'=>$cardno,'cardnum'=>$customInfo['num']]);

            $sql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,cardno,cardnum from qf_order where cardno='{$map['cardno']}' AND cardnum='{$map['cardnum']}' ";
            $sql.="AND flag='{$map['flag']}' and placeddate='{$day}' group by goodsname,price,goodsid,type,panterid,flag,cardno,cardnum)";

            $reSql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,cardnum,cardno from qf_order where cardno='{$rewhere['cardno']}' AND cardnum='{$rewhere['cardnum']}' ";
            $reSql.="AND flag='{$rewhere['flag']}' and placeddate='{$day}' group by goodsname,price,goodsid,type,panterid,flag,cardnum,cardno)";

        }else{
            $customid = $customInfo['customid'];
            $map=['customid'=>$customid,'flag'=>'02','cardnum'=>$customInfo['num']];
            $mapWhere=['o.customid'=>$customid,'o.cardnum'=>$customInfo['num'],'o.flag'=>'02'];
            $rewhere=['customid'=>$customid,'cardnum'=>$customInfo['num'],'flag'=>'04'];
            $reMap=['o.customid'=>$customid,'o.cardnum'=>$customInfo['num'],'o.flag'=>'04'];
            $combine = ['in'=>'0','out'=>'0'];


            $sql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,customid,cardnum from qf_order where customid='{$customid}' AND cardnum='{$map['cardnum']}' ";
            $sql.="AND flag='{$map['flag']}' and placeddate='{$day}' group by goodsname,price,goodsid,type,panterid,flag,customid,cardnum)";

            $reSql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,cardnum,customid from qf_order where customid='{$customid}' AND cardnum='{$rewhere['cardnum']}' ";
            $reSql.="AND flag='{$rewhere['flag']}' and placeddate='{$day}' group by goodsname,price,goodsid,type,panterid,flag,cardnum,customid)";
        }
        //消费菜品
        $orderlists=$this->model->table($sql)->alias('o')
            ->join('left join panters p on p.panterid=o.panterid')
            ->field('p.namechinese,o.goodsname,o.type,o.price,o.num,o.flag,o.goodsid')
            ->where($mapWhere)->select();


        //t退菜




        $refundlist=$this->model->table($reSql)->alias('o')
            ->join('left join panters p on p.panterid=o.panterid')
            ->field('p.namechinese,o.goodsname,o.type,o.price,o.num,o.flag,o.goodsid')
            ->where($reMap)->select();
//        $orderlists==true||returnMsg(array('status'=>'07','codemsg'=>'无消费信息'));
//        if($refundlist==false){
//            $refundlist='';
//        }else{
//            //除去 退款订单的
//            $orderlists=$this->subRe($orderlists,$refundlist);
//            $refundlist=$this->getFloat($refundlist,'price');
//        }
//        $orderlists=$this->getFloat($orderlists,'price');
        if($orderlists){
            if($refundlist){
                //除去 退款订单的
            $orderlists=$this->subRe($orderlists,$refundlist);
            $orderlists=$this->getFloat($orderlists,'price');
            }

        }else{
           $orderlists = [];
        }
        ///=======查询合卡出入账  ---2016  09 30---

        //=------- end
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$orderlists,'in'=>(string)floatval($combine['in']),'out'=>(string)floatval($combine['out']),'balance'=>round($accountInfo['amount'],2)]);

    }
    //获取消费详情时  除去退款订单
    private  function subRe($orderlists,$refundlist){
        foreach($refundlist as $v){
            foreach($orderlists as $key=>$val){
                if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']&&$v['namechinese']==$val['namechinese']){
                    $val['num']-=$v['num'];
                    if($val['num']==0){
                        unset($orderlists[$key]);
                        break;
                    }
                    $orderlists[$key]=$val;
                    break;
                }
            }

        }
        $res=[];
        foreach($orderlists as $val){
            $res[]=$val;
        }
        return $res;
    }
    //float 类型转换
    private function getFloat($data,$field){
        foreach($data as $key=>$val){
            $val[$field]=floatval($val[$field]);
            $data[$key]=$val;
        }
        return $data;
    }
    //查询单卡充值合计金额
    protected function searchChong($method,$map){
        $map['_string']='flag=01';
        $orders=M('qf_order')->where($map)->select();
        if($orders==false){
            $error=['status'=>'07','codemsg'=>'此卡:'.$map['cardno'].'无充值记录,不需要退卡','num'=>$map['cardnum']];
            $this->errorHandle($method,$error,'查询单卡充值合计时无充值记录');
        }
        $tradeWhere=array_column($orders,'tradeid');
        $where['purchaseid']=['in',$tradeWhere];
        $res=M('card_purchase_logs')->where($where)->field('sum(amount) as amount')->select();
        return $res[0]['amount']?:0;
    }
    /*
     * 查询 单卡大食堂卡消费记录
     * var $map 查询条件
     */
    private function searchConsume($map){
        $map['flag']='02';
        $orders=M('qf_order')->where($map)->select();
        if($orders==false){
            return 0;
        }
        $tradeWhere=array_column($orders,'tradeid');
        $where['tradeid']=['in',$tradeWhere];
        $where['cardno']=$map['cardno'];
        $res=M('trade_wastebooks')->where($where)->field('sum(tradeamount) as tradeamount')->select();
        return $res[0]['tradeamount']?:0;
    }
    /*
     * 单卡 退卡市退菜的金额
     */
    private function getRefund($map){
        $map['flag']='04';
        $orders=M('qf_order')->where($map)->field('tradeid')->select();
        if($orders==false){
            return 0;
        }
        $tradeWhere=array_column($orders,'tradeid');
        $where['tradeid']=['in',$tradeWhere];
        $res=M('trade_wastebooks')->where($where)->field('sum(tradeamount) as tradeamount')->select();
        return $res[0]['tradeamount']?:0;

    }
    //合卡明细查询
    private function combineDetail($map){
        $whereIn=['cardno'=>$map['cardno'],'innum'=>$map['num'],'status'=>'1'];
        $res=$this->model->table('qf_combine')->where($whereIn)->field('sum(amount) as amount')->select();
        $in = $res[0]['amount']?:0;
        $whereOut=['outcardno'=>$map['cardno'],'outnum'=>$map['num'],'status'=>'1'];
        $resOut=$this->model->table('qf_combine')->where($whereOut)->field('sum(amount) as amount')->select();
        $out = $resOut[0]['amount']?:0;
        return ['in'=>$in,'out'=>$out];
    }
    // 查询单卡的交易详情
    private function orderInfo($map){
        $orderlist=M('qf_order')->where($map)
            ->field('goodsname,sum(price*num) as allpirce ,sum(num) as num,flag,panterid,price')
            ->group('goodsname,price,flag,panterid')
            ->select();
        $odertype=['02'=>'消费','04'=>'退菜'];
        if($orderlist){
            foreach($orderlist as $key=>$val){
                $val['flag']=$odertype[$val['flag']];
                $orderlist[$key]=$val;
            }
        }
      return $orderlist?:'';
    }
    //对会员表 记录的cardnum 在充值进行处理
    protected function customsCardnum($customInfo){
        if($customInfo['cardflag']==='off' || $customInfo['cardflag']===null){
            if($customInfo['num']===null){
                $customInfo['num']=1;
            }
            else{
                $customInfo['num']+=1;
            }
            $bool = $this->model->table('customs')->where(['customid'=>$customInfo['customid']])->save(['num'=>$customInfo['num'],'cardflag'=>'on']);
            if(!$bool){
                $this->model->rollback();
                returnMsg(['status'=>'05','codemsg'=>'修改卡使用次数失败！']);
            }
        }
        return $customInfo['num'];
    }
//---------------------------------------------分割线---------------------------------------------------
    //商户日结算
    public function daliyBalance(){
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];

        $this->checkSign($key,md5(md5($this->keycode.$panterid)));
        $this->valiPanterid(__FUNCTION__,$panterid,'商户日结算校验商户');
        $map = ['panterid'=>$panterid,'tradetype'=>'00','flag'=>'0','placeddate'=>date('Ymd')];
        $consume = $this->panterTradeInfo($map);
        //退菜金额
        $map['tradetype'] = '31';
        $refund = $this->panterTradeInfo($map);
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>['consume'=>$consume,'refund'=>$refund]]);

    }
    //日结算统计 商户的各种交易金额 比如正常消费，退菜，退卡
    private function panterTradeInfo($map){
        $info =  $this->model->table('trade_wastebooks')->where($map)->field('sum(tradeamount) as tradeamount')->select();
        return $info[0]['tradeamount']?:0.00;
    }

    //======充值端统计充值钱
    public function chongDatail(){
        $panterid = trim(I('post.panterid',''));
        $termno   = trim(I('post.pos_id',''));
        $key      = trim(I('post.key',''));

//        dump($key);dump(md5($this->keycode.$panterid.$termno));exit;
        $this->checkSign($key,md5($this->keycode.$panterid.$termno));
        $panterid==$this->chongPanterid||returnMsg(array('status'=>'02','codemsg'=>'此商户无权查询前台充值详情'));
        //普通充值
        $map['panterid'] = $panterid;
        $map['termno']   = $termno;
        $map['placeddate'] = date('Ymd');
        //特惠充值
        $map['flag'] = '05';
        $gift = $this->model->table('qf_order')->where($map)
            ->group('paytype')
            ->field('paytype,sum(price) amount')
            ->select();
        if($gift){
            $gift = $this->formdatePaymentArr($gift);

        }else{
            $gift = [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }
        //员工卡
        $map['flag'] = '06';
        $customs = $this->model->table('qf_order')->where($map)
                ->group('paytype')
                ->field('paytype,sum(payamount) amount')
                ->select();
        if($customs){
            $customs = $this->formdatePaymentArr($customs);

        }else{
            $customs =  [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }
        //普通飞团团卡
        $map['flag']     = '01';
        $map['_string']  = 'teamid is null';

        $map['placeddate'] = date('Ymd');
        $noraml = $this->model->table('qf_order')->where($map)
                                                ->group('paytype')
                                                ->field('paytype,sum(payamount) amount')
                                                ->select();
        if($noraml){
            $noraml = $this->formdatePaymentArr($noraml);

        }else{
            $noraml =  [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }

        //团卡对卡
        $map['_string']  = 'teamid is not null';
        $team = $this->model->table('qf_order')->where($map)
            ->group('paytype')
            ->field('paytype,sum(payamount) amount')
            ->select();
        if($team){
            $team = $this->formdatePaymentArr($team);

        }else{
            $team =  [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }


        returnMsg(['status'=>'1','codemsg'=>'查询成功',
            'data'=>['noraml'=>$noraml,'gift'=>$gift,'customs'=>$customs,'team'=>$team]]
        );
    }
    //统一格式化四种支付类型统计数据
    protected function formdatePaymentArr($arr){
        // '00'=>'现金','01'=>'银行卡','09'=>'微信','10'=>'支付宝'
        $sort = ['00','01','09','10'];
        foreach ($arr as $key=>$val){
//            $val['paytype'] = $this->paymenttype[$val['paytype']];
            $val['amount']  = round($val['amount'],2);
            unset($val['numrow']);
            $arr[$key]     = $val;
        }
        $vals = array_column($arr,'paytype');
        if(count($vals)==count($sort)){
             array_multisort($vals,SORT_ASC,$arr);
            return $arr;
        }else{
            $diff = array_diff($sort,$vals);
            foreach ($diff as $v){
                $arr[] = ['paytype'=>$v,'amount'=>'0'];
            }
            array_multisort(array_column($arr,'paytype'),SORT_ASC,$arr);
            return $arr;
        }
    }
    //退卡端统计
    public function returnCardSatistics(){
        $panterid = trim(I('post.panterid',''));
        $termno   = trim(I('post.pos_id',''));
        $key      = trim(I('post.key',''));
        $this->checkSign($key,md5($this->keycode.$panterid.$termno));
        $panterid==$this->returnPanterid||returnMsg(array('status'=>'02','codemsg'=>'此商户无权查询退卡详情'));
        $map['panterid'] = $panterid;
        $map['termno']   = $termno;
        $map['flag']     = '03';
        $map['placeddate'] = date('Ymd');

        //普通飞团团卡
        $map['_string']  = 'teamid is null';

        $map['placeddate'] = date('Ymd');
        $noraml = $this->model->table('qf_order')->where($map)
            ->group('paytype')
            ->field('paytype,sum(payamount) amount')
            ->select();
        if($noraml){
            $noraml = $this->formdatePaymentArr($noraml);

        }else{
            $noraml =  [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }
        //团卡对卡
        $map['_string']  = 'teamid is not null';
        $team = $this->model->table('qf_order')->where($map)
            ->group('paytype')
            ->field('paytype,sum(payamount) amount')
            ->select();
        if($team){
            $team = $this->formdatePaymentArr($team);

        }else{
            $team =  [
                ['paytype'=>'00','amount'=>'0'],
                ['paytype'=>'01','amount'=>'0'],
                ['paytype'=>'09','amount'=>'0'],
                ['paytype'=>'10','amount'=>'0'],
            ];
        }
        $customs =  [
            ['paytype'=>'00','amount'=>'0'],
            ['paytype'=>'01','amount'=>'0'],
            ['paytype'=>'09','amount'=>'0'],
            ['paytype'=>'10','amount'=>'0'],
        ];
        $gift =  [
            ['paytype'=>'00','amount'=>'0'],
            ['paytype'=>'01','amount'=>'0'],
            ['paytype'=>'09','amount'=>'0'],
            ['paytype'=>'10','amount'=>'0'],
        ];
        returnMsg(['status'=>'1','codemsg'=>'查询成功',
                'data'=>['noraml'=>$noraml,'gift'=>$gift,'customs'=>$customs,'team'=>$team]]
        );





    }
    //结算充值 合计总金额
    private function chargeSum($map){
        $chargeInfo = $this->model->table('custom_purchase_logs')->alias('cup')
                                  ->join('left join qf_order kf on kf.tradeid=cup.purchaseid')
                                  ->where($map)->field('nvl(sum(amount),0) amount,nvl(sum(realamount),0) realamount')
                                  ->select();
        return $chargeInfo[0];
    }
    //查询卡 可用余额
    public function yue(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$termno));
        $cardInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $this->valiPanterid(__FUNCTION__,$panterid,'查询卡卡用余额校验商户');
        $customInfo = $this->customsInfo($cardInfo['customid']);
        $frozen = $this->giftCardno($customInfo);
        $accountInfo = $this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        $accountInfo['amount']>$frozen||returnMsg(['status'=>'03','codemsg'=>'无可以消费余额']);
        $yue=bcsub($accountInfo['amount'],$frozen,2);
        returnMsg(['status'=>'1','codemsg'=>'查询成功','yue'=>$yue]);
    }
 //------------------------------------------------------分割线------------------------------------
    public function emergency(){
        $cardno   = trim(I('post.cardno',''));
        $panterid = trim(I('post.panterid',''));
        $termno   = trim(I('post.pos_id',''));
        $price    = trim(I('post.price',''));
        $key      = trim(I('post.key',''));;

        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$price.$termno));
        $this->writeLog('emergency_get',['cardno'=>$cardno,'panterid'=>$panterid,'termno'=>$termno,'price'=>$price]);
        if(!in_array($panterid,$this->emPanterid)){
            returnMsg(array('status'=>'07','codemsg'=>'该商户不能进行紧急预案消费'));
        }
        $price>0||returnMsg(array('status'=>'08','codemsg'=>'消费金额应大于0'));
        $cardInfo   = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        $customInfo['cardflag']=='on'||returnMsg(['status'=>'03','codemsg'=>'此卡已退卡或未充值']);
        $accountInfo= $this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        if($customInfo['num']!='0'){
            $ableAccount = bcsub($accountInfo['amount'],$this->frozen,2);
            $teamid      = $this->getTeamidNull(['cardno'=>$cardno,'cardnum'=>$customInfo['num']]);
        }else{
            $ableAccount = $accountInfo['amount'];
            $teamid  = '';
        }


        $ableAccount>=$price||returnMsg(array('status'=>'09','codemsg'=>'卡余额不足,可用余额为:'.floatval($ableAccount)));
        $yue=bcsub($accountInfo['amount'],$price,2);


        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";

        $this->model->startTrans();
        $tradeif=$this->model->execute($sql);
        //查询是否有特殊消费信息
        $map=['panterid'=>$panterid,'goodsname'=>'特殊消费'];
        $bool=M('qf_goods')->where($map)->find();
        if($bool==false){
            $goodsid = $this->getFieldPrimaryNumber('goodsid','8');
            $goodsSql="INSERT INTO qf_goods(goodsid,panterid,type,goodsname,status) VALUES ('1','{$panterid}','2','特殊消费','1')";
//            $typeSql="INSERT INTO qf_type(panterid,type,name,status) VALUES ('{$panterid}','2','特殊','1')";
            $goodif=$this->model->execute($goodsSql);

            if($goodif==true){
            }else{
                $this->model->rollback();
                returnMsg(['status'=>'07','codemsg'=>'数据库写入失败']);
            }
        }
        $orderid = $this->getFieldPrimaryNumber('orderid',16);
        $ordeSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,price,num,flag,placeddate,placedtime,panterid,goodsname,payamount,customid,teamid) VALUES ";
        $ordeSql.="('{$tradeid}','{$cardno}','{$customInfo['num']}','1','2','{$price}' ,'1','02','{$placeddate}','{$placedtime}',";
        $ordeSql.="'{$panterid}','特殊消费','{$price}','{$customInfo['customid']}','{$teamid}')";
        $orderif=$this->model->execute($ordeSql);
        $accountif=M('account')->where(['customid'=>$customInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        if($tradeif==true&&$orderif==true&&$accountif==true){
            $this->model->commit();
            $this->writeLog('emergency_success',['cardno'=>$cardno,'tradeid'=>$tradeid,'price'=>$price,'balance'=>$yue]);
            returnMsg(['status'=>'1','codemsg'=>'消费成功','yue'=>$yue,'tradeid'=>$tradeid]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'09','codemsg'=>'数据库操作失败']);
        }
    }
    /* 合卡消费
     * $cardno 主卡卡号
     * $panterid 消费的商户号
     * $termno 终端号
     * $cardlist 合卡的卡号 与金额
     * $amount 菜的总金额
     * $order 订单
     * $accountZ主账户
     * $accountS附属卡账户
     * $realCut 真正的扣款金额
     */
    public function combineCard(){
        $cardno=trim($_POST['cardno']);
        $panterid=trim($_POST['panterid']);
        $termno=trim($_POST['pos_id']);
        $carddata=trim($_POST['cardlist']);
        $amount=trim($_POST['amount']);
        $order=trim($_POST['data']);
        $key=$_POST['key'];


        file_put_contents('combine.log',json_encode($_POST),8);
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$termno.$carddata.$amount.$order));
        $this->valiPanterid(__FUNCTION__,$panterid,'合卡消费是校验商户');
        $amount>0||returnMsg(array('status'=>'06','codemsg'=>'消费金额必须大于零'));

        $cardnoInfoZ = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfoZ = $this->customInfo(__FUNCTION__,$cardnoInfoZ['customid']);
        $customInfoZ['cardflag']=='on'||returnMsg(array('status'=>'05','codemsg'=>'此卡已退卡'));
        $accountInfoZ= $this->accountInfo(__FUNCTION__,$cardnoInfoZ['customid']);
        $this->cannotCombine($customInfoZ);
        $teamid = $this->getTeamidNull(['cardno'=>$cardno,'cardnum'=>$customInfoZ['num']]);
        $teamid==true ||  returnMsg(array('status'=>'033','codemsg'=>'不是团卡，无法合卡消费'));

        $carddata=json_decode($carddata,1);
        $order=json_decode($order,1);
        if(array_key_exists($cardno,$carddata)){
            unset($carddata[$cardno]);
        }
        $sum=0;
        foreach($carddata as $key=>$val){
            $cardAccount = $this->cardAccountInfo(__FUNCTION__,$key);
            $this->cannotCombine($cardAccount);
            $fTeamid = $this->getTeamidNull(['cardno'=>$cardAccount['cardno'],'cardnum'=>$cardAccount['num']]);
            if(empty($fTeamid) || $teamid!=$fTeamid){
                returnMsg(array('status'=>'033','codemsg'=>'不是团卡或不是同一个团卡,不能合卡消费'));
            }
            $cut=bcadd($this->frozen,$val,2);
            if($cardAccount['amount']<$cut){
                returnMsg(array('status'=>'15','codemsg'=>'此卡:'.$key.'金额不够合卡消费'));
            }
            $cardAccount['cut'] = $val;
            $sum=bcadd($sum,$val,2);
            //所有子卡的账户信息
            $sonInfo[] = $cardAccount;
        }
        $realAccount=bcsub($accountInfoZ['amount'],$this->frozen,2);
        $realCut=bcadd($realAccount,$sum,2);
        $realCut==$amount||returnMsg(array('status'=>'16','codemsg'=>'要扣款金额与消费金额不一致'));

        //订单判定
        $consumeSum=0;
        foreach($order as $key=>$val){
            if($val['type']=='1'){
                $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
                if($list==true){
                    $consumeSum=bcadd($consumeSum,bcmul($list['price'],$val['num'],2),2);
                }else{
                    returnMsg(['status'=>'16','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }else{
                returnMsg(array('status'=>'17','codemsg'=>'散卖商品不能参与合卡消费'));
            }
        }
        $consumeSum==$amount||returnMsg(array('status'=>'18','codemsg'=>'订单总金额与实际的不一致'));
        //生成消费记录 1 卡金额合并 并生成记录
        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        //原卡号信息
        $map=['cardno'=>$cardno,'num'=>$customInfoZ['num'],'tradeid'=>$tradeid];
        $this->getCombine($map,$sonInfo);
        //主卡账户变更
        $accountZif=$this->model->table('account')->where(['customid'=>$accountInfoZ['customid'],'type'=>'00'])->save(['amount'=>$this->frozen]);
        //生成消费记录
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$amount}','0','{$accountInfoZ['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        //写入消费记录
        foreach($order as $val){
            $list=M('qf_goods')->where(['goodsid'=>$val['goodsid'],'panterid'=>$panterid])->find();
            $orderid = $this->getFieldPrimaryNumber('orderid',16);
            $payamount = bcmul($list['price'],$val['num'],2);
            if($val['type']=='1'){
                $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid,payamount,customid,teamid) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfoZ['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$list['price']}','{$val['num']}','02',";
                $orderSql.="'{$placeddate}','{$placedtime}','{$panterid}','{$list['price']}','{$customInfoZ['customid']}','{$teamid}')";
            }else {
                returnMsg(array('status'=>'17','codemsg'=>'散卖商品不能参与合卡消费'));
            }
            $orderif=$this->model->execute($orderSql);
            if($orderif==false){
                returnMsg(array('status'=>'09','codemsg'=>'数据写入大食堂订单失败'));
            }
        }
        if($accountZif==true&&$tradeif){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'合卡消费成功','tradeid'=>$tradeid,'balance'=>$this->frozen]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'09','codemsg'=>'主账户或者消费记录写入失败']);
        }
    }
    //基于卡号获取账户的相关信息
    private function cardAccountInfo($method,$cardno){
        $cardInfo = $this->model->table('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join customs_c cc on cc.cid = ca.customid')
            ->join('left join account ac on ac.customid = ca.customid')
            ->where(['ca.cardno'=>$cardno,'ac.type'=>'00'])
            ->field('ca.cardno,ca.status,ca.customid,cu.cardflag,cu.num,ac.amount')
            ->select();
        if($cardInfo){
            if($cardInfo[0]['status']!='Y'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'不是正常卡'];
                $this->errorHandle($method,$error,'合卡消费时非正常卡');
            }
            if($cardInfo[0]['cardflag']!='on'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'已经退卡'];
                $this->errorHandle($method,$error,'合卡消费时卡已经退卡');
            }
            return $cardInfo[0];

        }else{
            $error =['status'=>'02','codemsg'=>'此卡：'.$cardno.' 账户信息异常'];
            $this->errorHandle($error,$method,'合卡消费是查询子卡信息失败');
        }

    }
    //卡金额合并 以及账户扣钱
    private function getCombine($map,$cardlist){
        foreach($cardlist as $key=>$val){
            $yue=bcsub($val['amount'],$val['cut'],2);
            $accountif=$this->model->table('account')->where(['customid'=>$val['customid'],'type'=>'00'])->save(['amount'=>$yue]);
            $date=time();$placeddate=date('Ymd',$date);$placedtime=date('H:i:s',$date);
            $sql="INSERT INTO qf_combine (cardno,innum,outcardno,outnum,placeddate,placedtime,status,tradeid,amount) VALUES (";
            $sql.="'{$map['cardno']}','{$map['num']}','{$val['cardno']}','{$val['num']}','{$placeddate}','{$placedtime}','1','{$map['tradeid']}','{$val['cut']}')";
            $combineif=$this->model->execute($sql);
            if($accountif==true&&$combineif==true){
            }else{
                returnMsg(array('status'=>'09','codemsg'=>'数据库操写入失败'));
            }
        }
    }
//--------------------------------------------------------------------------团购卡处理------------------
  /*
   * var $cardlist 团购卡号
   * var $amount 单卡充金额
   * var $num 充值卡数量
   * var $price 总价格
   * var $panterid
   * var $termno 终端号
   */
    public function teamCharge(){
        $cardlist = trim($_POST['cardlist']);
        $amount   = trim(I('post.amount',''));
        $num      = trim(I('post.num',''));
        $price    = trim(I('post.price',''));
        $panterid = trim(I('post.panterid',''));
        $termno   = trim(I('post.pos_id',''));
//        $value    = trim(I('post.value',''));
        $paymenttype = trim(I('post.paymenttype',''));
        $itemid   = trim(I('post.itemid',''));
        $key      = trim(I('post.key',''));
        $en       = trim(I('post.en',''));


        $this->checkSign($key,md5($this->keycode.$cardlist.$panterid.$termno.$amount.$num.$price.$paymenttype.$itemid));
        $this->writeLog('teamcharge_get',['cardlist'=>$cardlist,'panterid'=>$panterid,'termno'=>$termno,'amount'=>$amount,'num'=>$num,'price'=>$price]);
        $panterid==$this->chongPanterid||returnMsg(['status'=>'02','codemsg'=>'不是大食堂充值端']);

	    $this->chargeLimit($amount);

        //返点信息获取
        $cardlist=json_decode($cardlist,1);
//        $cardlist = ['6880374800000000020',
//            '6880374800000000021',
//            '6880374800000000022',
//            '6880374800000000023',
//            '6880374800000000024',
//            '6880374800000000025',
//            '6880374800000000026',
//            '6880374800000000027',
//            '6880374800000000029',
//            '6880374800000000030'
//        ];
//        $num = 10;
//        $price=1000;
        $count=count($cardlist);
        $count==$num||returnMsg(['status'=>'03','codemsg'=>'充值卡总数量不对']);
        $countSum=bcmul($count,$amount,2);
        $countSum==$price||returnMsg(['status'=>'04','codemsg'=>'充值总金额不对']);
//        $teamid='team_id'.date('YmdHis').substr($cardlist[0],-4);
        $teamid = $this->getFieldPrimaryNumber('teamid',10);
        $cardinfo = $this->cardsValiStatus($cardlist,$count);
        $paytype     = $paymenttype;
        $paymenttype = $this->paymenttype[$paymenttype];
        $itemInfo     = $this->valiItem($itemid);
        $map=['paymenttype'=>$paymenttype,'amount'=>$amount,
            'panterid'=>$panterid,'userid'=>$this->userid,
            'teamid'=>$teamid,'sum'=>$price,'termno'=>$termno,
            'paytype'=>$paytype,'itemid'=>$itemid,'item'=>$itemInfo
        ];
        $this->model->startTrans();
        $bool=$this->exTeamCharge($map,$cardinfo);
        if($bool==true){
            $this->model->commit();
            $this->writeLog('teamcharge_susccess',['teamid'=>$teamid,'cardlist'=>json_encode($cardlist)]);
            if($en!=''){
                $msg = ['charge'=>$price,'paytype'=>$paymenttype,'time'=>date('Y-m-d H:i:s'),'cardno'=>$teamid,'payamount'=>$price,'name'=>'启封充值点','title'=>'本次充值'];
                $this->jpush($en,json_encode($msg));
            }
            returnMsg(['status'=>'1','codemsg'=>'团购成功','teamid'=>$teamid]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'30','codemsg'=>'操作数据库异常']);
        }
    }
    private function cardsValiStatus($cardlist,$count){
        $instring = $this->inString($cardlist);
        $map = "ca.cardno in ({$instring}) and ca.status='Y' and (cu.cardflag = 'off' or cu.cardflag is null)";
        $info=$this->model->table('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->where($map)->field('ca.cardno,cu.customid,cu.num')->select();
        if(count($info)!=$count){
            $diff = array_diff($cardlist,array_column($info,'cardno'));
            $cards=implode(',',$diff);
            $error = ['status'=>'05','codemsg'=>'有卡号:'.$cards.'账户异常'];
            $decription = $cards."/t".'团冲卡检验时有卡不是退卡状态';
            $this->errorHandle(__FUNCTION__,$error,$decription);
        }else{
            return $info;
        }
    }
    /*
   * 子查询的时 数组转为 in 查询时所需的字符串
   * @param array $arr 一维数组信息
   * @return sting
   */
    protected function inString($arr){
        $str='';
        foreach($arr as $val){
            $str.="'".$val."',";
        }
        return rtrim($str,',');
    }
    //团卡冲卡执行
    private function exTeamCharge($map,$info){
        $currentDate=date('Ymd');
        $checkDate=date('Ymd');
        $placddate=date('Ymd');
        $placedtime=date('H:i:s');
        $customplSql  = '';
        $auditlogsSql = '';
        $cardplSql    = '';
        $orderSql     = '';
        $teamid       = $map['teamid'];
        $amount       = $map['amount'];
        $paymenttype  = $map['paymenttype'];
        $termno       = $map['termno'];
        $paytype      = $map['paytype'];

        $itemInfo     = $map['item'];
        $area         = $itemInfo['area'];
        $linkname     = $itemInfo['linkname'];
        $phone        = $itemInfo['phone'];
        $point        = $itemInfo['point'];
        $name         = $itemInfo['name'];
        foreach($info as $key =>$val){
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $auditid=$this->getFieldNextNumber('auditid');
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            $orderid = $this->getFieldPrimaryNumber('orderid',16);

            if($val['num']===null){
                $val['num']=1;
            }else{
                $val['num']+=1;
            }
            //修改卡使用次数
            $customNumIf=M('customs')->where(['customid'=>$val['customid']])->save(['num'=>$val['num'],'cardflag'=>'on','customlevel'=>'启封故园食堂团购客户']);
            if(!$customNumIf){
                $this->model->rollback();
                returnMsg(['status'=>'07','codemsg'=>'修改会员团购记录失败']);
            }
            if($key>=1){
                $customplSql.=" into custom_purchase_logs values('".$map['customid']."','{$purchaseid}','".$currentDate."','{$map['paymenttype']}','";
                $customplSql.=$map['userid']."','".$map['amount']."',NULL,'".$map['amount']."',0,'".$map['amount']."','".$map['amount'];
                $customplSql.="',1,'','','1','".$map['panterid']."','".$map['userid']."',NULL,'1',";
                $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

                $auditlogsSql.=" into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
                $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$map['userid'] ."','".date('H:i:s',time()+300)."')";

                $cardplSql.=" INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
                $cardplSql.="'{$val['cardno']}','{$map['amount']}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
                $cardplSql.="'{$map['userid']}','{$map['panterid']}','','00000000')";

                $orderSql.=" INTO qf_order (orderid,tradeid,cardno,cardnum,price,num,flag,panterid,teamid,payamount,paytype,teamid,customid,termno,placeddate,placedtime) VALUES ('{$orderid}','{$purchaseid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','{$amount}','1','01','{$map['panterid']}','{$teamid}','{$amount}','{$paytype}','{$teamid}','{$val['customid']}','{$termno}','{$placddate}','{$placedtime}')";
            }else{
                $customplSql.="INSERT ALL INTO custom_purchase_logs values('".$val['customid']."','{$purchaseid}','".$currentDate."','{$map['paymenttype']}','";
                $customplSql.=$map['userid']."','".$map['amount']."',NULL,'".$map['amount']."',0,'".$map['amount']."','".$map['amount'];
                $customplSql.="',1,'','','1','".$map['panterid']."','".$map['userid']."',NULL,'1',";
                $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

                $auditlogsSql.="INSERT ALL INTO audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
                $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$map['userid'] ."','".date('H:i:s',time()+300)."')";

                $cardplSql.="INSERT ALL INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
                $cardplSql.="'{$val['cardno']}','{$map['amount']}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
                $cardplSql.="'{$map['userid']}','{$map['panterid']}','','00000000')";

                //充值单记录
                $orderSql.="INSERT ALL INTO qf_order (orderid,tradeid,cardno,cardnum,price,num,flag,panterid,payamount,paytype,teamid,customid,termno,placeddate,placedtime) VALUES ('{$orderid}','{$purchaseid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','{$amount}','1','01','{$map['panterid']}','{$amount}','{$paytype}','{$teamid}','{$val['customid']}','{$termno}','{$placddate}','{$placedtime}')";
            }
            // 修改账户金额
            $customsInfo = $val['customid'];
        }
        $teamSql = "insert into qf_team_logs (teamid,charge,placeddate,placedtime,itemid,name,ctype,area,linkname,point,phone) VALUES ";

        $teamSql.= "('{$teamid}','{$map['sum']}','{$placddate}','{$placedtime}','{$map['itemid']}','{$name}','{$paytype}',";
        $teamSql.="'{$area}','{$linkname}','{$point}','{$phone}')";

        $customplSql.=" SELECT 1 FROM DUAL";
        $auditlogsSql.=" SELECT 1 FROM DUAL";
        $cardplSql.=" SELECT 1 FROM DUAL";
        $orderSql.=" SELECT 1 FROM DUAL";

        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);
        $cardplIf=$this->model->execute($cardplSql);
        $orderif=$this->model->execute($orderSql);
        $teamif=$this->model->execute($teamSql);
        $accountif = $this->model->table('account')
                            ->where(['customid'=>['in',array_column($info,'customid')],'type'=>'00'])
                            ->save(['amount'=>$map['amount']]);
        if($customplIf==true && $auditlogsSql==true && $cardplIf==true && $orderif==true && $teamif==true && $accountif==true){
            return true;
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'07','codemsg'=>'数据库写入失败不能充卡']);
        }
    }
    //获取团冲单号
    private function getTeamid($map){
        $map['flag']='01';
        $res=$this->model->table('qf_order')->where($map)->field('teamid')->find();
        if($res==true){
            return $res['teamid'];
        }else{
            returnMsg(array('status'=>'21','codemsg'=>'未查询到此卡chon充值单号:'.$map['cardno'].'num:'.$map['cardnum']));
        }
    }
    //查询 所有团购卡消费记录 之和
    private function teamConsume($customlist){
        $conSum=0;
        foreach($customlist as $val){
            $condition=$this->model->table('customs')->alias('cu')
                ->join('left join cards ca on ca.customid=cu.customid')
                ->where(['cu.customid'=>$val])->field('ca.cardno,cu.num')->find();
            if($condition==false){
                returnMsg(array('status'=>'22','codemsg'=>'未查询到此用户号:'.$val.'卡num异常:'));
            }
            $con=0;$tui=0;
            $xiaoWhere=['flag'=>'02','cardno'=>$condition['cardno'],'cardnum'=>$condition['num']];
            $xiao=$this->model->table('qf_order')->where($xiaoWhere)->field('price,num')->select();
            if($xiao){
                foreach($xiao as $v){
                    $con=bcadd($con,bcmul($v['price'],$v['num'],2),2);
                }
            }
            $tuiWhere=['flag'=>'04','cardno'=>$condition['cardno'],'cardnum'=>$condition['num']];
            $tuicai=$this->model->table('qf_order')->where($tuiWhere)->field('price,num')->select();
            if($tuicai==true){
                foreach($tuicai as $tv){
                    $tui=bcadd($tui,bcmul($tv['price'],$tv['num'],2),2);
                }
            }
            $conSum=bcadd($conSum,bcsub($con,$tui,2),2);
        }
        return $conSum;
    }
    private function teamAcc($customlist){
        $accSum=0;
        foreach($customlist as $val){
            $account=$this->model->table('account')->where(['customid'=>$val,'type'=>'00'])->field('amount')->find();
            if($account==false){
                returnMsg(array('status'=>'23','codemsg'=>'未查询到此用户号:'.$val.'账户异常:'));
            }
            $accSum=bcadd($accSum,$account['amount'],2);
        }
        return $accSum;
    }
    /*  //团购卡合卡 出入账
    * var $customlist 用户customid
    */
    private function teamCombine($customlist){
        $inSum=0;$outSum=0;
        foreach($customlist as $val){
            $condition=$this->model->table('customs')->alias('cu')
                ->join('left join cards ca on ca.customid=cu.customid')
                ->where(['cu.customid'=>$val])->field('ca.cardno,cu.num')->find();
            if($condition==false){
                returnMsg(array('status'=>'22','codemsg'=>'未查询到此用户号:'.$val.'卡num异常:'));
            }
            $inWhere=['status'=>'1','cardno'=>$condition['cardno'],'innum'=>$condition['num']];
            $inlist=$this->model->table('qf_combine')->where($inWhere)->field('amount')->select();
            $in=0;
            if($inlist==true){
                $in=$this->countAll(array_column($inlist,'amount'));
            }
            $inSum=bcadd($inSum,$in,2);
            $outWhere=['status'=>'1','outcardno'=>$condition['cardno'],'outnum'=>$condition['num']];
            $outlist=$this->model->table('qf_combine')->where($outWhere)->field('amount')->select();
            $out=0;
            if($outlist==true){
                $out=$this->countAll(array_column($outlist,'amount'));
            }
            $outSum=bcadd($outSum,$out,2);
        }
        return ['in'=>$inSum,'out'=>$outSum];
    }

    //大食堂 团卡 退卡
    public function returnTeam(){
        $cardno     = trim(I('post.cardno',''));
        $panterid   = trim(I('post.panterid',''));
        $termno     = trim(I('post.pos_id',''));
        $getTeamid  = trim(I('post.getTeamid',''));
        $paymenttype= trim(I('post.paymenttype',''));
        $key=$_POST['key'];
        $en         = trim(I('post.en',''));
        $this->writeLog('returnTeam_info',['cardno'=>$cardno,'panterid'=>$panterid,'termno'=>$termno,'getTeamid'=>$getTeamid,'paymenttype'=>$paymenttype]);
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$termno.$getTeamid.$paymenttype));
        $panterid==$this->returnPanterid||returnMsg(array('status'=>'02','codemsg'=>'此商户没有退卡权限'));
        $cardInfo   = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardInfo['customid']);
        $customInfo['customlevel']=='启封故园食堂团购客户'||returnMsg(array('status'=>'07','codemsg'=>'此卡号不是团购卡:'.$cardno.'不能退卡'));
        $mapTeam = ['cardno'=>$cardno,'cardnum'=>$customInfo['num']];
        $teamid = $this->getTeamid($mapTeam);
        if(md5($teamid)!==$getTeamid){
            returnMsg(array('status'=>'40','codemsg'=>'非法单号不能退卡'));
        }
        //返回团卡相关信息
        $teamcardinfo = $this->cardTeamInfo(__FUNCTION__,$teamid);
        $customlists  = array_column($teamcardinfo,'customid');
        $num          = count($customlists);


        //获取团怼 扣点
        $point = M('qf_team_logs')->where(['teamid'=>$teamid])->find()['point'];
        $chong = M('qf_order')->where(['teamid'=>$teamid,'flag'=>'01'])
                              ->field('nvl(sum(price),0) amount')->find()['amount'];
        $pointJ = bcdiv($point,100,2);
        $this->model->startTrans();
        $customWhere['customid']=['in',$customlists];
        $teamInfo = $this->exTeamCards($teamcardinfo,
            ['panterid'=>$panterid,'termno'=>$termno,'teamid'=>$teamid,'paytype'=>$paymenttype],
            $cardno,$point);
        $reTeamid = $teamInfo['teamid'];
        $sum      = $teamInfo['sum'];
        $pointAmount = bcmul(bcsub($chong,$sum,2),$pointJ,2);
        $customIf=M('customs')->where($customWhere)->save(['cardflag'=>'off','customlevel'=>'']);
        $customWhere['type']='00';
        $accountIf=M('account')->where($customWhere)->save(['amount'=>0]);
        if($customIf==true && $accountIf==true){
            $this->model->commit();
            $this->writeLog('termtui_success',['teamid'=>$reTeamid]);
            $msg = ['charge'=>$sum,'paytype'=>$this->paymenttype[$paymenttype],'time'=>date('Y-m-d H:i:s'),'cardno'=>$reTeamid,'payamount'=>$sum,'name'=>'启封退卡点','title'=>'本次退卡'];
            if($en!=''){
                $this->jpush($en,json_encode($msg));
            }
            returnMsg(['status'=>'1','codemsg'=>'团退卡成功','teamid'=>$reTeamid,'point'=>$point,'pointAmount'=>$pointAmount,'num'=>$num]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'23','codemsg'=>'数据库操作失败,请重试']);
        }
    }
    //返回团卡 相关信息 卡号，customid，以及cardflag，num
//    private function cardTeamInfo($method,$teamid){
//        $lists = M('qf_team')->where(['teamid'=>$teamid])->select();
//        $count = count($lists);
//        $list  = array_column($lists,'purchaseid');
//        $where['cp.purchaseid']=['in',$list];
//        $where['ac.type'] = '00';
//        $info = $this->model->table('card_purchase_logs')->alias('cp')
//            ->join('left join cards ca on ca.cardno=cp.cardno')
//            ->join('left join customs cu on cu.customid=ca.customid')
//            ->join('left join account ac on ac.customid=cu.customid')
//            ->field('ca.cardno,cu.customid,cu.num,ac.amount')
//            ->where($where)->select();
//        if($count!=count($info)){
//            $description = '团退卡的基于充值单号查询所有团卡信息';
//            $error = ['status'=>'05','codemsg'=>'团退卡查询账户信息数量异常'];
//            $this->errorHandle($method,$error,$description);
//        }
//        return $info;
//    }
    private function cardTeamInfo($method,$teamid){
        $map['qf.teamid'] = $teamid;
        $map['qf.flag']   = '01';
        $map['ac.type']   = '00';

        $info = $this->model->table('qf_order')->alias('qf')
            ->join('left join customs cu on cu.customid=qf.customid')
            ->join('left join account ac on ac.customid=cu.customid')
            ->field('qf.cardno,cu.customid,cu.num,ac.amount')
            ->where($map)->select();
        if($info){
            return $info;
        }else{
            $description = 'cardTeamInfo查询所有团卡信息异常';
            $error = ['status'=>'05','codemsg'=>'团退卡查询账户信息数量异常'];
            $this->errorHandle($method,$error,$description);
        }
    }
    /*
    * 团购卡 退卡各种记录写入
    * var $customlist 用户id序列
    * var $map['panterid'] 商户号 $map['termno'] 终端号
    * var $cardno 卡号
    */
    private function exTeamCards($info,$map,$cardno,$point){
        $termno=$map['termno'];
        $panterid=$map['panterid'];
        $tradetype=30;
        $date=time();
        $teamDate=$date;
        $teamid=$map['teamid'];
        $paytype = $map['paytype'];
        $sql = '';
        $orderSql = '';
        $sum = 0;
        foreach($info as $key => $val){
            //生成消费记
            $date++;
            $placeddate=date('Ymd',$date);
            $placedtime=date('H:i:s',$date);
            $tradeid=$termno.date('YmdHis',$date);
            $tac='abcdefgh';
            $orderid = $this->getFieldPrimaryNumber('orderid','16');
            $flag='0';
            $sum=bcadd($sum,$val['amount'],2);
            if($key>=1){
                $sql.=" INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0')";

                $orderSql.=" INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,price,num,placeddate,placedtime,customid,teamid,paytype,payamount,termno) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','03','{$panterid}','{$val['amount']}','1','{$placeddate}','{$placedtime}','{$val['customid']}',";
                $orderSql.="'{$teamid}','{$paytype}','{$val['amount']}','{$termno}')";

            }else{
                $sql.="INSERT ALL INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0')";
                // qf_order 表
                $orderSql.="INSERT ALL INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,price,num,placeddate,placedtime,customid,teamid,paytype,payamount,termno) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','03','{$panterid}','{$val['amount']}','1','{$placeddate}','{$placedtime}','{$val['customid']}',";
                $orderSql.="'{$teamid}','{$paytype}','{$val['amount']}','{$termno}')";
                //qf_team 表
            }
            //tw 表
        }

        $sql.= " SELECT 1 FROM DUAL";
        $orderSql.= " SELECT 1 FROM DUAL";
        $tradeif=$this->model->execute($sql);
        $orderif=$this->model->execute($orderSql);
        $save['retype'] = $paytype;
        $save['replaceddate']  = $placeddate;
        $save['replacedtime']  = $placedtime;
        $save['return']        = $sum;
        $teamif=$this->model->table('qf_team_logs')->where(['teamid'=>$teamid])->save($save);
        if($tradeif==true&&$orderif==true&&$teamif==true){
            return ['teamid'=>$teamid,'sum'=>$sum];
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'36','codemsg'=>'退卡交易记录生成失败'));
        }
    }

//------------------------------------------------------菜品处理----------------------------
    // 新增菜品
    public function addDish(){
        $panterid  =$_POST['panterid'];
        $goodsname =$_POST['goodsname'];
        $typename  =$_POST['typename'];
        $price     =$_POST['price'];
        $key       =$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$panterid.$goodsname.$typename.$price));
        $this->valiPanterid(__FUNCTION__,$panterid,'新增菜品是校验商户');

        if($typename=='主食'){
            $map=['panterid'=>$panterid,'goodsname'=>$goodsname,'price'=>$price,'type'=>'1','typename'=>$typename];
            $flag='1';;
        }elseif($typename=='其他'){
            $map=['panterid'=>$panterid,'goodsname'=>$goodsname,'typename'=>'其他','type'=>'2'];
            if($goodsname=='特殊消费'){
                returnMsg(['status'=>'05','codemsg'=>'特殊消费是敏感字段,请换名字']);
            }
            $flag='2';
        }
        $goodsif=$this->addGoods($map);
//        $typeif=$this->addType($map,$flag);
        if($goodsif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'新增商品成功']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'02','codemsg'=>'数据库写入失败']);
        }
    }
    //下架菜品
    public function removeDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $key=$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$panterid.$goodsid));
        $this->valiPanterid(__FUNCTION__,$panterid,'移除菜品检验商户');
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $goodsInfo = $this->goodsInfo($map);
        $goodsInfo['status']=='1'||returnMsg(['status'=>'15','codemsg'=>'该商品不是在线商品']);
        if($this->updateGoodsStatus($map,['status'=>'2'])){
            returnMsg(array('status'=>'1','codemsg'=>'商品下架成功'));
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
        }
    }
    //下架菜品上架
    public function onDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $key=$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$panterid.$goodsid));
        $this->valiPanterid(__FUNCTION__,$panterid,'上架菜品检验商户');
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $goodsInfo = $this->goodsInfo($map);
        $goodsInfo['status']=='2' || returnMsg(['status'=>'15','codemsg'=>'该商品不是下架商品']);
        if($this->updateGoodsStatus($map,['status'=>'1'])){
            returnMsg(array('status'=>'1','codemsg'=>'商品上架成功'));
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
        }
    }
    //编辑菜品
    public function editDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $data=$_POST['data'];
        $key=$_POST['key'];

        $this->checkSign($key,md5($this->keycode.$panterid.$goodsid.$data));
        $this->valiPanterid(__FUNCTION__,$panterid,'编辑菜品是检验商户');
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $goodsInfo = $this->goodsInfo($map);
        $goodsInfo['status']=='1'||returnMsg(['status'=>'15','codemsg'=>'该商品不是在线商品']);
        $data = json_decode($data,1);
        //校验字段是否与数据库相同
        foreach($data as $key=>$val){
            if($data[$key]==$goodsInfo[$key]){
                $bool=true;
            }else{
                $bool=false;
                break;
            }
        }
        if($bool==true){
            returnMsg(['status'=>'04','codemsg'=>'无有效信息需要编辑']);
        }
        unset($data['typename']);
        if($this->updateGoodsStatus($map,$data)){
            returnMsg(['status'=>'1','codemsg'=>'商品编辑成功']);
        }else{
            returnMsg(['status'=>'03','codemsg'=>'数据库操作失败']);
        }
    }
    //新增菜品 到菜品表
    private function addGoods($map){
//        $goodslist=M('qf_goods')->where(['panterid'=>$map['panterid']])->select();
//        if($goodslist==false){
////            $goodsid=1;
//        }else{
        $namelist=M('qf_goods')->where(['goodsname'=>$map['goodsname'],'panterid'=>$map['panterid'],'type'=>$map['type']])->find();
        if($namelist==true){
            returnMsg(['status'=>'03','codemsg'=>'商品名字重复']);
        }
//            $goodsid=$this->getMax(array_column($goodslist,'goodsid'));
//            $goodsid+=1;
//        }
        $goodsid = $this->getFieldPrimaryNumber('goodsid',8);
        $sql="INSERT INTO qf_goods (goodsid,panterid,type,goodsname,price,status) VALUES ('{$goodsid}','{$map['panterid']}','{$map['type']}','{$map['goodsname']}','{$map['price']}','1')";
        return $this->model->execute($sql);
    }
    //新增菜品时  添加菜品对应的列别
    private function addType($map,$flag){
        $typelist=M('qf_type')->where(['panterid'=>$map['panterid'],'type'=>$flag])->find();
        if($typelist==false){
            $sql="INSERT INTO qf_type (panterid,type,name,status) VALUES ('{$map['panterid']}','{$flag}','{$map['typename']}','1')";
            return $this->model->execute($sql);
        }else{
            return true;
        }
    }
    //基于菜品号 返回菜品信息
    private function goodsInfo($map){
        if($gooodsInfo = M('qf_goods')->where($map)->find()){
            return $gooodsInfo;
        }else{
            returnMsg(['status'=>'14','codemsg'=>'未查到该商品,请核实']);
        }
    }
    //编辑菜品信息
    private function updateGoodsStatus($where,$map){
        return M('qf_goods')->where($where)->save($map);
    }
    private function getMax($arr){
        rsort($arr);
        return $arr[0];
    }
 //------------------------------大食堂其他方法
    //获取卡余额
    public function getYue(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];
        $this->checkSign($key,md5($this->keycode.$cardno.$panterid.$termno));
        $this->valiPanterid(__FUNCTION__,$panterid,'查询余额时验证商户信息');
        $cardInfo=$this->cardInfo(__FUNCTION__,$cardno);
        $account=$this->accountInfo(__FUNCTION__,$cardInfo['customid']);
        returnMsg(['status'=>'1','amount'=>$account['amount']]);
    }
    //商户获取一周的交易详情
    public function weekData(){
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$termno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $inDate=[date('Ymd'),date('Ymd',time()-86400),date('Ymd',time()-2*86400),
            date('Ymd',time()-3*86400),date('Ymd',time()-4*86400),date('Ymd',time()-5*86400),
            date('Ymd',time()-6*86400)
        ];
        $this->valiPanterid(__FUNCTION__,$panterid,'周交易详情时检验商户');
        if($panterid==C('qf_getDrink')){
            $this->getDrink($panterid);
        }else{
            //消费的
            $map['flag']='02';
            $map['panterid']=$panterid;
            $map['placeddate']=['in',$inDate];
            $list=M('qf_order')->where($map)->field('nvl(sum(price*num),0) esum,placeddate,flag')
                ->group('placeddate,flag')->order('placeddate desc')->select();
            //退菜的
            $map['flag']='04';
            $refundlist=M('qf_order')->where($map)->field('nvl(sum(price*num),0) esum,placeddate,flag')
                ->group('placeddate,flag')->order('placeddate desc')->select();
            //第一步 有退菜日期的 获取  与正常合并
            foreach($list as $key=>$val){
                $bool=false;
                foreach($refundlist as $k=>$v){
                    if($val['placeddate']==$v['placeddate']){
                        $val['refund']=$v['esum'];
                        $bool=true;
                        unset($refundlist[$k]);break;
                    }
                }
                if($bool==false){
                    $val['refund']='0';
                }
                $list[$key]=$val;
            }
            foreach($inDate as $inv){
                $sbool=false;
                foreach($list as $sk=>$sv){
                    if($sv['placeddate']==$inv){
                        $sbool=true;
                        $res[]=['placeddate'=>$sv['placeddate'],'consume'=>round($sv['esum'], 2),
                               'refund'=>round($sv['refund'],2),
                                'real'=> bcsub($sv['esum'],$sv['refund'],2)
                        ];
                    }
                }
                if($sbool==false){
                    $res[]=['placeddate'=>strval($inv),'consume'=>'0','refund'=>'0','real'=>'0'];
                }
            }
            returnMsg(array('status'=>'1','data'=>$res,'flag'=>'01'));
        }
    }
    private function getDrink($panterid){
        $map['placeddate']=date('Ymd');
        $map['panterid']=$panterid;
        $map['flag']='02';
        $getlist=M('qf_order')->where($map)->field('sum(price*num) as csum,sum(num) as tnum,goodsname,goodsid,type,placeddate')
            ->group('goodsname,goodsid,type,placeddate')->select();
        //退菜
        $map['flag']='04';
        $refulist=M('qf_order')->where($map)->field('sum(price*num) as csum,sum(num) as tnum,goodsname,goodsid,type,placeddate')
            ->group('goodsname,goodsid,type,placeddate')->select();
        if($refulist==true){
            foreach($getlist as $key=>$val){
                $bool=false;
                foreach($refulist as $k=>$v){
                    if($val['goodsname']==$v['goodsname']&&$val['goodsid']==$v['goodsid']&&$val['type']==$v['type']){
                        $val['refund']=$v['csum'];
                        $val['rnum']=$v['tnum'];
                        $bool=true;
                        break;
                    }
                }
                if($bool==false){
                    $val['refund']='0';
                    $val['rnum']='0';
                }
                $getlist[$key]=$val;
            }
        }else{
            foreach($getlist as $key=>$val){
                $val['refund']='0';
                $val['rnum']='0';
                $getlist[$key]=$val;
            }
        }
        returnMsg(array('status'=>'1','data'=>$getlist,'flag'=>'02'));
    }
//---------------------------------------------公用方法
    /*
     * 校验数据来源
     * @param string $key 传入秘钥
     * @param string $checkKey 本地秘钥
     */
    private function checkSign($key,$checkKey){
        if($key!==$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
    }
    //查询卡信息
    private function cardInfo($method,$cardno){
        if($cardInfo = $this->model->table('cards')->where(['cardno'=>$cardno])->find()){
            if($cardInfo['status']!='Y'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'不是正常卡'];
                $this->errorHandle($method,$error,'查询卡号信息操作');
            }
            if($cardInfo['cardkind']!='6997'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'卡宾不对'];
                $this->errorHandle($method,$error,'查询卡号信息操作');
            }
//            if(substr($cardno,3,4)!='0393'){
//                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'不属于启封故园地区'];
//                $this->errorHandle($method,$error,'查询卡号信息操作');
//            }
            return $cardInfo;
        }else{
            $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'不存'];
            $this->errorHandle($method,$error,'查询卡号信息操作');
        }
    }
    //查询会员信息
    private function customInfo($method,$customid){
        if($customInfo = $this->model->table('customs')->where(['customid'=>$customid])->find()){
            return $customInfo;
        }else{
            $error=['status'=>'03','codemsg'=>'此会员:'.$customid.'不存'];
            $this->errorHandle($method,$error,'查询会员信息操作');
        }
    }
    //查询账户信息
    protected function accountInfo($method,$customid){

        if($accountInfo = $this->model->table('account')->where(['customid'=>$customid,'type'=>'00'])->find()){
            return $accountInfo;
        }else{
            $error=['status'=>'04','codemsg'=>'此卡会员:'.$customid.'账户信息不存'];
            $this->errorHandle($method,$error,'查询卡对应会员的账户信息');
        }
    }
    //查询商户信息
    private function valiPanterid($method,$panterid,$description){
        $list=M('panters')->where(['panterid'=>$panterid])->find();
        if($list==false){
            $error=['status'=>'6','codemsg'=>'未查询到该商户信息,'.$panterid.'请核实'];
            $this->errorHandle($method,$error,$description);
        }
        if($list['revorkflg']!='N'){
            $error=['status'=>'12','codemsg'=>'该商户禁用中'];
            $this->errorHandle($method,$error,$description);
        }
        if($list['parent']!=$this->parent){
            $error=['status'=>'13','codemsg'=>'不是启封故园商户'];
            $this->errorHandle($method,$error,$description);
        }
        return $list;
    }
    /*
     * 查出非法信息处理
     * @param string $method 那个方法调用
     * @param array  要返回的错误信息
     * @param string $descrition 信息描述
     */
    private function errorHandle($method,$error,$description){
        $this->errorLog($method,$error,$description);
        returnMsg($error);
    }
    /*
     * 错误信息日志记录错误
     */
    private function errorLog($method,$error,$description){
        $filename = PUBLIC_PATH.'logs/qf_catering_error/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        $str=date('Y m d H:i:s',time()).'  '.$method.'------------';
        $str.=json_encode($error,JSON_UNESCAPED_UNICODE)."\t ";
        $str.=$description."\t\n";
        file_put_contents($filename,$str,FILE_APPEND);

    }
    private function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
    /*获数据记录日志
     *$this->getDir()文件是否建立 若无建立
     * $dir 文件夹名
     * $data 数据信息
     */
    private function writeLog($dir,$data){
        $str=date('Y-m-d H:i:s',time()).'******************';
        if(is_array($data)){
            foreach($data as $key=>$val){
                $str.=$key.':'.$val."\t  ";
            }
        }else{
            $str.=$data;
        }
        $str.="\n";
        $filename=PUBLIC_PATH.'logs/qf_catering/'.$dir.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        file_put_contents($filename,$str,FILE_APPEND);
    }
    //合计金额
    private function countAll($data){
        $sum=0;
        foreach($data as $val){
            $sum=bcadd($sum,$val,2);
        }
        return $sum;
    }
    //-----------------------------------------------------------------------特惠充值----------------------------------------------------------
    //获取优惠比例
    public function giftRate()
    {
        $termno  = I('post.pos_id');
        $panterid= I('post.panterid');
        $key     = I('post.key');
        $this->checkSign($key,md5($this->keycode.$termno.$panterid));
        $panterid===$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'此商户没有特殊充值的优惠权限']);
        returnMsg(['status'=>'1','gift'=>C('qf_gift_api')]);
    }

    //特惠充值
    public function rechargeGift(){
        $cardno      = $_POST['cardno'];//卡号
        $paymenttype = $_POST['paymenttype'];//现金银行支付的类型
        $realmoney   = $_POST['realmoney']; //实付金额
        $giftmoney   = $_POST['giftmoney'];  //赠送金额
        $totalmoney  = $_POST['totalmoney']; //总金额
        $panterid    = $_POST['panterid'];  //商户号
        $termno      = trim(I('post.pos_id',''));
        $key         = $_POST['key'];
        $en          = trim(I('post.en',''));
        $this->checkSign($key,md5($this->keycode.$cardno.$paymenttype.$realmoney.$giftmoney.$totalmoney.$panterid.$termno));
        $this->writeLog(__FUNCTION__,$_POST);
        //检验优惠金额 是否正确
        $this->valiChargeAmount($realmoney,$giftmoney,$totalmoney);
        $panterid==$this->chongPanterid||returnMsg(array('status'=>'07','codemsg'=>'此商户没有特惠充值权限'));
        $cardInfo = $this->giftCardVali(__FUNCTION__,$cardno);
	    $this->chargeLimit($totalmoney);
        if($cardInfo['num']==='0'){
        	bcadd($cardInfo['amount'],$totalmoney,2)<=5000 ||  returnMsg(['status'=>'021','codemsg'=>'充值金额禁止大于5000']);
        }

        $paytype    =  $paymenttype;
        $paymenttype=  $this->paymenttype[$paymenttype];
        //开启事务操作
        $this->model->startTrans();
        //判定是否更新账户customs表
        if($cardInfo['flag']=='2'){
            $customIf = $this->model->table('customs')->where(['customid'=>$cardInfo['customid']])->save(['num'=>'0','cardflag'=>'on']);
        }else{
            $customIf = true;
        }
        $map = ['cardno'=>$cardno,'cutsomid'=>$cardInfo['customid'],'amount'=>$totalmoney,
            'realmoney'=>$realmoney,
            'userid'=>$this->userid,'paymenttype'=>$paymenttype,'panterid'=>$panterid,
            'num' =>'0','purchaseid'=>$this->getFieldNextNumber('purchaseid'),
            'auditid'=>$this->getFieldNextNumber('auditid'),'cardpurchaseid'=>$this->getFieldNextNumber('cardpurchaseid'),
            'paytype'=>$paytype,'termno'=>$termno
        ];
        $bool=$this->exGiftChong($map);
        if($bool['msg']===true && $customIf==true){
            $this->model->commit();
            $balanceInfo = $this->model->table('account')->where(['type'=>'00','customid'=>$cardInfo['customid']])->field('amount')->find();
            $this->writeLog('gift_success',['cardno'=>$cardno,'cardnum'=>'0','purchaseId'=>$bool['purchaseid'],'balance'=>$balanceInfo['amount'],'org'=>$cardInfo['amount']]);
            if($en!=''){
                $msg = ['charge'=>$totalmoney,'paytype'=>$paymenttype,'time'=>date('Y-m-d H:i:s'),'cardno'=>$cardno,'payamount'=>$realmoney,'name'=>'启封充值点','title'=>'本次充值'];
                $this->jpush($en,json_encode($msg));
            }
            returnMsg(['status'=>'1','codemsg'=>'充值成功','purchaseId'=>$bool['purchaseid'],'balance'=>floatval($balanceInfo['amount']),'org'=>floatval($cardInfo['amount'])]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'22','codemsg'=>'充值失败']);
        }
    }
    protected function exGiftChong( $map){
        $cardno       = $map['cardno'];
        $customid     = $map['cutsomid'];
        $chargeAmount = $map['amount'];
        $userid       = $map['userid'];
        $paymenttype  = $map['paymenttype'];
        $panterid     = $map['panterid'];
        $currentDate  = date('Ymd');
        $checkDate    = date('Ymd');
        $num          = $map['num'];
        $realmoney    = $map['realmoney'];
        //审核单号
        $auditid      = $map['auditid'];
        //充值单号
        $purchaseid   = $map['purchaseid'];
        $cardpurchaseid=$map['cardpurchaseid'];
        $paytype      = $map['paytype'];
        $termno       = $map['termno'];
        $placeddate   = date('Ymd');
        $placedtime   = date('H:i:s');

        $customplSql="insert into custom_purchase_logs values('".$customid."','{$purchaseid}','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$chargeAmount."',NULL,'".$chargeAmount."',0,'".$realmoney."','".$chargeAmount;
        $customplSql.="',1,'','','1','".$panterid."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf = $this->model->execute($customplSql);

        //写入审核单
        $auditlogsSql="insert into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
        $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        $auditlogsIf = $this->model->execute($auditlogsSql);

        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$chargeAmount}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        //更新账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$chargeAmount." where customid='".$customid."' and type='00'";
        $balanceIf = $this->model->execute($balanceSql);
        $orderid = $this->getFieldPrimaryNumber('orderid',16);
        $orderSql="INSERT INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,price,num,payamount,customid,paytype,termno,placeddate,placedtime) VALUES ('{$orderid}','{$purchaseid}',";
        $orderSql.="'{$cardno}','{$num}','05','{$panterid}','{$chargeAmount}','1','{$realmoney}','{$customid}','{$paytype}','{$termno}','{$placeddate}','{$placedtime}')";
        $orderif= $this->model->execute($orderSql);
        if($customplIf==true && $auditlogsIf==true && $cardplIf==true && $orderif==true ){
            return ['msg'=>true,'purchaseid'=>$purchaseid];
        }else{
            return false;
        }
    }
    /*
    *@param realmoney 真实金额
    *@param giftmoney 赠送金额
    *@param totalmoney 充值金额
    *@return void
    */
    private function valiChargeAmount($realmoney, $giftmoney, $totalmoney){
        $totalmoney==bcadd($realmoney,$giftmoney,2)||returnMsg(['status'=>'07','codemsg'=>'充值金额异常']);
        $gift = $this->gift;
        if($key=array_search($giftmoney,$gift)){
            $realmoney>=$key || returnMsg(['status'=>'07','codemsg'=>'真实金额:'.$realmoney."无法享有这个优惠:".$giftmoney]);
        }else{
            returnMsg( ['status'=>'07','codemsg'=>"无这个优惠:".$giftmoney]);
        }
        return true;
    }
    private function giftCardVali($method,$cardno){
        $description = '检验该卡是否可以参与优惠充值';
        $cardInfo = $this->model->table('cards')->alias('ca')
            ->join('left join customs cu on ca.customid=cu.customid')
            ->join('left join account ac on ca.customid=ac.customid')
            ->where(['ca.cardno'=>$cardno,'ac.type'=>'00'])
            ->field('ca.cardno,ca.status,ca.customid,ca.cardkind,cu.num,cu.cardflag,ac.amount')
            ->select();
        if($cardInfo){
            if($cardInfo[0]['status']!='Y'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'不是正常卡'];
                $this->errorHandle($method,$error,$description);
            }
            if($cardInfo[0]['cardkind']!='6997'){
                $error= ['status'=>'02','codemsg'=>'此卡号:'.$cardno.'卡宾不对'];
                $this->errorHandle($method,$error,'查询卡号信息操作');
            }
            if($cardInfo[0]['num']===null || $cardInfo[0]['num']==='0'){
                if($cardInfo[0]['num']==='0' && $cardInfo[0]['num']==='on') $cardInfo[0]['flag']='1';
                else $cardInfo[0]['flag']='2';
                return $cardInfo[0];
            }else{
                $error=['status'=>'06','codemsg'=>'此卡：'.$cardno.'不能参加优惠充值'];
            }

        }else{
            $error=['status'=>'06','codemsg'=>'此卡：'.$cardno.'账户信息异常'];
        }
        $this->errorHandle($method,$error,$description);
    }
    //退卡 时判定那些卡 不能退卡
    private function cannotReturn($num,$cardno){
        if($num==='0'){
            returnMsg(['status'=>'07','codemsg'=>'此卡不能退卡'.$cardno]);
        }else{
            return true;
        }
    }
//    public function test(){
//        $cardno = '6997378000000000026';
//        $num    = '1';
//        $this->teamNotReturn($num,$cardno);
//    }
    //单卡退卡验证退卡不能
    protected function teamNotReturn($num,$cardno){
        $map['cardno'] = $cardno;
        $map['cardnum']= $num;
        $bool = $this->model->table('qf_order')->where($map)->field('teamid')->find();
        if(!empty($bool['teamid'])){
            returnMsg((['status'=>'07','codemsg'=>'此卡是团卡'.$cardno]));
        }else{
            return true;
        }

    }
    //不可退卡 不能参与合卡
    protected function cannotCombine($info){
        if($info['num']==='0'){
            returnMsg(['status'=>'09','codemsg'=>'特惠卡与不可退卡不能参与合卡消费']);
        }
    }
    //------------------------------------------------------------------特惠卡无卡底费 书写
    private function giftCardno($customInfo){
        if($customInfo['num']==='0'){
            return 0;
        }else{
            return $this->frozen;
        }
    }


    //---------------------------------------------------------------特惠充值卡补卡问题----------------------------------------------
    //补卡钱获取可用的补卡
    public function getCardsLock(){
        $panterid = $_POST['panterid'];
        $termno   = $_POST['pos_id'];
        $key      = $_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$termno);
        $this->checkSign($key, $checkKey);

        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'该商户无权补卡']);
        $map['cl.active'] = '0';
        $map['cu.num']    = '0';
        $map['ca.cardkind'] = '6997';
        $list   = M('card_locks_log')->alias('cl')
            ->join('cards ca on cl.cardno=ca.cardno')
            ->join('customs cu on ca.customid=cu.customid')
            ->where($map)->field('ca.cardno')->select();
        if($list==false){
            returnMsg(['status'=>'2','codemsg'=>'无挂失的卡']);
        }else{
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>array_column($list,'cardno')]);
        }
    }
    //赠送卡补卡
    public function reissueCard(){
        $panterid     = $_POST['panterid'];
        $termno       = $_POST['pos_id'];
        $cardno       = $_POST['cardno'];
        $oldcard      = $_POST['oldCard'];
        $key          = $_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$termno.$cardno.$oldcard);
        $this->checkSign($key, $checkKey);
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'此商户无权补卡']);
        $newMap       = ['status'=>'Y','cardno'=>$cardno];
        $newInfo      = $this->model->table('cards')->where($newMap)->find();
        $newInfo==true || returnMsg(['status'=>'04','codemsg'=>'此卡新卡号不存在:'.$cardno]);
        $newInfo['cardkind']=='6997'|| returnMsg(['status'=>'05','codemsg'=>'非启封故园地区卡']);
        $pyInfo       =$this->model->table('qf_order')->where(['cardno'=>$cardno])->find();
        $pyInfo==false ||returnMsg(['status'=>'04','codemsg'=>'此卡不是新卡:'.$cardno]);
        $this->valireissueCard($oldcard);
        $oldInfo  = $this->cardAccount($oldcard);
        $map['cardno'] = $oldcard;
        $save['cardno']= $cardno;

        $placeddate = date('Ymd');
        $placeddtime= date('H:i:s');
        $sql = "insert into qf_reissue_card(cardno,oldcard,customid,replaceid,placeddate,placedtime)values";
        $sql.= "('{$cardno}','{$oldcard}','{$oldInfo['customid']}','{$newInfo['customid']}','{$placeddate}','{$placeddtime}')";
        $this->model->startTrans();
        //新不卡流程 账户关联卡号替换  qf_reissue_card
        try{
            $oldIf = $this->model->table('cards')->where(['cardno'=>$oldcard])->save(['status'=>'L','customid'=>'']);
            $newIf = $this->model->table('cards')->where(['cardno'=>$cardno])->save(['customid'=>$oldInfo['customid']]);
            $lockIf= $this->model->table('card_locks_log')->where($map)->save(['active'=>'2']);
            $logsIf= $this->model->execute($sql);
            if($oldIf && $newIf && $lockIf && $logsIf){
                $this->model->commit();
                returnMsg(['status'=>'1','codemsg'=>'补卡成功']);
            }else{
                $this->model->rollback();
                returnMsg(['status'=>'06','codemsg'=>'修改数据库失败,补卡卡失败']);
            }
        }catch (\Exception $e){
            $this->model->rollback();
            returnMsg(['status'=>'07','codemsg'=>$e->getMessage()]);

        }

    }
    private function valireissueCard($oldcard){
        substr($oldcard,0,4)=='6997'||returnMsg(['status'=>'05','codemsg'=>'挂失卡,非启封故园地区卡']);
        $map['cl.active']  = '0';
        $map['ca.cardno']  = $oldcard;
        $map['cu.num']     = '0';
        $list   = M('card_locks_log')->alias('cl')
            ->join('cards ca on cl.cardno=ca.cardno')
            ->join('customs cu on ca.customid=cu.customid')
            ->where($map)->field('ca.cardno')->select();
        $list==true || returnMsg(['status'=>'04','codemsg'=>'此卡补卡记录不存在:'.$oldcard]);
    }
    //查询卡账户
    private function cardAccount($cardno){
        return $this->model->table('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join account ac on cu.customid=ac.customid')
            ->where(['ca.cardno'=>$cardno,'ac.type'=>'00'])
            ->field('ca.cardno,ca.customid,ac.amount,cu.num')->find();
    }

	//大食堂 禁止充值大于5000
	protected function chargeLimit($amount){
//		if($amount>5000){
//			returnMsg(['status'=>'021','codemsg'=>'充值金额禁止大于5000']);
//		}else{
//			return true;
//		}
        return true;
	}



	//-----------------------------
    protected function getFieldPrimaryNumber($field, $length){
        $seq_field='seq_qf_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $list=$this->model->query($sql);
        return  str_pad($list[0]['nextval'],$length,'0',STR_PAD_LEFT);
    }


    protected function getRelateInfo($map,$field){
        $map['flag'] = '01';
	    $bool = $this->model->table('qf_order')->where($map)->find()[$field];
	    if($bool){
	        return $bool;
        }else{
	        returnMsg(['status'=>'33','codemsg'=>"获取相关类型参数{$field}失败"]);
        }
    }
    protected function getTeamidNull($map){
        $map['flag'] = '01';
        return  $this->model->table('qf_order')->where($map)->find()['teamid'];
    }



    //----------------------------------------------------------------------团卡项目相关-------------------------------

    public function addItem(){
	    $name     = trim(I('post.name',''));
	    $panterid = trim(I('post.panterid',''));
	    $point    = trim(I('post.point',''));
	    $area     = trim(I('post.area',''));
	    $linkname = trim(I('post.linkname',''));
	    $phone    = trim(I('post.phone',''));
	    $key      = trim(I('post.key',''));
        $this->checkSign($key,md5($this->keycode.$name.$panterid.$point.$area.$linkname.$phone));
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'此商户权限不足']);
        $name==true || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'项目名不能为空']);


        $exist = $this->model->table('qf_item')->where(['name'=>$name,'status'=>'1'])->find();
        if($exist) returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'项目名字已经存在']);

        $itemid = $this->getFieldPrimaryNumber('itemid','8');
        $placeddate = date('Ymd');
        $placedtime = date('H:i:s');
        $sql = "insert into qf_item (itemid,name,status,point,area,linkname,phone,placeddate,placedtime) VALUES (";
        $sql.= "'{$itemid}','{$name}','1','{$point}','{$area}','{$linkname}','{$phone}','{$placeddate}','{$placedtime}')";

        try{
            if($this->model->execute($sql)){
                returnMsg(['status'=>'1','codemsg'=>'新增项目成功']);
            }else{
                returnMsg(['status'=>'022','codemsg'=>'新增项目失败']);
            }
        }catch (\Exception $e){
            returnMsg(['status'=>'022','codemsg'=>$e->getMessage()]);
        }
    }

    public function getItem(){
//        $flag     = trim(I('post.flag',''));
        $panterid = trim(I('post.panterid',''));
        $key      = trim(I('post.key',''));

        $this->checkSign($key,md5($this->keycode.$panterid));
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'此商户权限不足']);

        $result = $this->model->table('qf_item')->where(['status'=>'1'])->select();
//        if($flag=='01'){//全部项目
//
//        }elseif($flag=='02'){
//            $result = $this->model->table('qf_item')->where(['status'=>'1'])->field('itemid,name,status')->select();
//        }else{
//            returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'参数错误']);
//        }  if($flag=='01'){//全部项目
//
//        }elseif($flag=='02'){
//            $result = $this->model->table('qf_item')->where(['status'=>'1'])->field('itemid,name,status')->select();
//        }else{
//            returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'参数错误']);
//        }
        if(!$result) returnMsg(['status'=>'05','codemsg'=>'无可用得项目']);
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$result]);
    }

    public function onItem(){
        $itemid     = trim(I('post.itemid',''));
        $panterid   = trim(I('post.panterid',''));
        $key        = trim(I('post.key',''));

        $this->checkSign($key,md5($this->keycode.$itemid.$panterid));
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'此商户权限不足']);
        $bool = $this->model->table('qf_item')->where(['itemid'=>$itemid])->find();
        if(!$bool) returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'无此项目']);
        if($bool['status']=='1') returnMsg(['status'=>'04','codemsg'=>'该项目已经上架']);
        if($this->model->table('qf_item')->where(['itemid'=>$itemid])->save(['status'=>'1'])){
            returnMsg(['status'=>'1','codemsg'=>'上架成功']);
        }else{
            returnMsg(['status'=>'022','codemsg'=>'上架失败']);
        }

    }
    public function offItem(){
        $itemid     = trim(I('post.itemid',''));
        $panterid   = trim(I('post.panterid',''));
        $key        = trim(I('post.key',''));
        $this->checkSign($key,md5($this->keycode.$itemid.$panterid));
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'此商户权限不足']);
        $bool = $this->model->table('qf_item')->where(['itemid'=>$itemid])->find();
        if(!$bool) returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'无此项目']);
        if($bool['status']=='2') returnMsg(['status'=>'04','codemsg'=>'该项目已经下架']);
        if($this->model->table('qf_item')->where(['itemid'=>$itemid])->save(['status'=>'2'])){
            returnMsg(['status'=>'1','codemsg'=>'下架成功']);
        }else{
            returnMsg(['status'=>'022','codemsg'=>'下架失败']);
        }
    }
    public function editItem(){
        $itemid     = trim(I('post.itemid',''));
        $data       = trim($_POST['data']);
        $panterid   = trim(I('post.panterid',''));
        $key        = trim(I('post.key',''));

        $this->checkSign($key,md5($this->keycode.$itemid.$data.$panterid));
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'此商户权限不足']);
        $bool = $this->model->table('qf_item')->where(['itemid'=>$itemid])->find();
        if(!$bool) returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'无此项目']);

        $save = json_decode($data,1);
       try{
           if($this->model->table('qf_item')->where(['itemid'=>$itemid])->save($save)){
               returnMsg(['status'=>'1','codemsg'=>'修改项目成功']);
           }else{
               returnMsg(['status'=>'022','codemsg'=>'修改项目失败']);
           }
       }catch (\Exception $e){
           returnMsg(['status'=>'022','codemsg'=>'修改项目失败']);
       }

    }
    protected function valiItem($itemid){
        $bool = $this->model->table('qf_item')->where(['itemid'=>$itemid])->find();
        if(!$bool) returnMsg(['status'=>'03','codemsg'=>'校验信息错误'.'无此项目']);
        return $bool;
    }


    //jpush


    protected function jpush($en,$msg){
        $push   = $this->client->push()->setPlatform('android');
        $this->valiJpushResponse($en);
        $push->addAlias($en);
        $push->message($msg);
        try {
            $push->send();
        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
//            print $e;
            file_put_contents('jpush.txt',$en.":".$e."\t\n",FILE_APPEND);
        }
        return true;

    }

    protected function valiJpushResponse($alias){
        $response = $this->client->device()->getAliasDevices($alias);
        if($response['http_code']=='200'){
            return true;
        }else{
            $error = "该设备无法连接jpush:".$alias;
            file_put_contents('valijpusp.txt',$error."\t\n",FILE_APPEND);
            exit('该设备无法连接jpush:'.$alias);
        }
    }


    //获取商户最近消费的订单
    public function getConsumeList(){
        $panterid = trim(I('post.panterid',''));
        $termno   = trim(I('post.pos_id',''));
        $key      = trim(I('post.key',''));
        $this->checkSign($key,md5($this->keycode.$panterid.$termno));

        $list     = $this->getPanterOrderInfo(['panterid'=>$panterid,'termno'=>$termno]);
        if(! $list) $list = [];
        foreach ($list as $key=>$val){
            $val['placeddate'] = date('Y-m-d',strtotime($val['placeddate']));
            $list[$key]  = $val;
        }
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$list]);

    }
    protected function getPanterOrderInfo($map){
       return  M('qf_order')->where($map)
                            ->limit(0,5)->order('placeddate desc,placedtime desc')
                            ->field('goodsname,price,num,cardno,tradeid,placeddate,placedtime')->select();
    }

    //获取卡类别
    public function cardKind($cardno){
        $cardInfo   = M('cards')->where(['cardno'=>$cardno])->field('customid,cardfee')->find();
        $customInfo = M('customs')->where(['customid'=>$cardInfo['customid']])->field('num')->find();
        if($customInfo['num']=='0'){
            if($cardInfo['cardfee']=='3'){
                return '员工';
            }else{
                return '会员';
            }

        }else{
            $map['cardno'] = $cardno;
            $map['cardnum'] = $customInfo['num'];
            $map['flag']    = '01';
            $charge =  M('qf_order')->where($map)->field('teamid')->find();

            if(is_null($charge['teamid'])){
                return '游客';
            }else{
                return '团队';
            }
        }
    }

}


