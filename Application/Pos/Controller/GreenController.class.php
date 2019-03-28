<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class GreenController extends CoinController
{
    private $greenuserid; //
    private $cardPanterid;
    private $parent;
    private $tradetype;
    private $reFoods;
    private $emPanterid;
    private $chongPanterid;
    private $frozen;
    private $paymenttype;
    public function _initialize(){
        parent::_initialize();
        $this->greenuserid=C('greenuserid');
        $this->cardPanterid=C('cardPanterid');
        $this->chongPanterid=C('chongPanterid');
        $this->parent=C('parent');
        $this->tradetype='30';
        //退菜订单
        $this->reFoods='31';
        //紧急预案商户号
        $this->emPanterid=C('emPanterid');
        $this->frozen=5;
        //前台紧急扣款
        $this->QemPanterid=C('QemPanterid');
        //支付方式
        $this->paymenttype = [
            '00'=>'现金','01'=>'银行卡','09'=>'微信','10'=>'支付宝'
        ];
    }

    //单卡充值
    public function cardRecharge(){
        $cardno =$_POST['cardno'];//卡号
        $paymenttype = $_POST['paymenttype'];//现金银行支付的类型
        $totalmoney=$_POST['totalmoney'];//总金额
        $panterid = $_POST['panterid'];//商务号
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$paymenttype.$totalmoney.$panterid);
        $this->checkSign($key,$checkKey);
        $get = ['cardno'=>$cardno,'paymenttype'=>$paymenttype,'totalmoney'=>$totalmoney,'panterid'=>$panterid];
        $this->writeLog('single_get',json_encode($get));

        $method = __FUNCTION__;
        $panterid==$this->chongPanterid||returnMsg(array('status'=>'07','codemsg'=>'此商户没有充值权限'));
        $cardInfo    = $this->valiCard($cardno, $method);

//	    bcadd($cardInfo['amount'],$totalmoney,2)<=5000 || returnMsg(['status'=>'021','codemsg'=>'充值金额禁止大于5000']);

        //充值限额
	    $this->chargeLimit($totalmoney);

        if($cardInfo['customlevel']=='大食堂团购客户'){
            returnMsg(['status'=>'30','codemsg'=>'此卡是团购卡不能单独充值']);
        }
        $cardnum = $this->getCardNum($cardInfo);
        $paymenttype = $this->paymenttype[$paymenttype]?:'其他';
        $unpurchaseid=$this->getFieldNextNumber('purchaseid');
        $map = ['card'=>$cardno,'cutsomid'=>$cardInfo['customid'],
                'amount'=>$totalmoney,'panterid'=>$panterid,'num'=>$cardnum,
                'paymenttype'=>$paymenttype,'Purchaseid'=>$unpurchaseid,'cardflag'=>$cardInfo['cardflag']
        ];

        $this->model->startTrans();
        $bool=$this->exechong($map);
        if($bool['msg']===true){
            $this->model->commit();
            //卡中余额
            $accountNow=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->find();
            //若无账户信息 报错
            $accountNow==true||returnMsg(['status'=>'05','codemsg'=>'该卡：'.$cardno.'账户异常,请换卡']);
            $success = ['cardno'=>$cardno,'cardnum'=>$cardInfo['num'],'purchaseId'=>$bool['purchaseid'],
                         'balance'=>$accountNow['amount'],'org'=>$cardInfo['amount'],'cardfee'=>$cardInfo['cardfee']
            ];
            $this->writeLog('single+success',json_encode($success));
            //----2016 10 14充值 返回初始金额
            returnMsg(['status'=>'1','codemsg'=>'充值成功','purchaseId'=>$bool['purchaseid'],
                       'balance'=>floatval($accountNow['amount']),'org'=>floatval($cardInfo['amount'])
                      ]
                     );
            //----end
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'05','codemsg'=>'充值失败']);
        }
    }
    // 退卡前获取卡信息
    public function getConsume(){
        $cardno =$_POST['cardno'];//卡号
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno);
        $this->checkSign($key, $checkKey);

        $cardInfo = $this->valiCard($cardno);
        if($cardInfo['cardflag']=='off'){
            returnMsg(['status'=>'06','codemsg'=>'此卡已退卡']);
        }
        $method=__FUNCTION__;
        if($cardInfo['customlevel']=='大食堂团购客户'){
            $this->teamInfo($cardInfo);
        }else{
            $this->sigleCardInfo($cardInfo);
        }

    }
    //单卡退卡
    public function returnCard(){
        $cardno    = $_POST['cardno'];//卡号
        $totalmoney= $_POST['totalmoney'];//总金额
        $panterid  = $_POST['panterid'];//商务号
        $termno    = $_POST['pos_id'];
        $tradetype = $_POST['tradetype'];
        $key       = $_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$totalmoney.$panterid.$termno.$tradetype);
        $this->checkSign($key, $checkKey);

        $method=__FUNCTION__;
        $tradetype==$this->tradetype||returnMsg(['status'=>'02','codemsg'=>'非退卡交易类型,请确认']);
        $panterid==$this->chongPanterid||returnMsg(['status'=>'09','codemsg'=>'此商户没有退卡权限']);
        $cardInfo = $this->valiCard($cardno, $method);
        //赠送卡 不能退卡
        if($cardInfo['cardfee']=='3')returnMsg(['status'=>'10','codemsg'=>'赠送卡无法退卡']);
        $cardInfo['cardflag']=='on'||returnMsg(array('status'=>'08','codemsg'=>'此卡未充值不能退卡'));
        $cardInfo['amount']==$totalmoney||returnMsg(['status'=>'06','codemsg'=>'退卡金额与卡余额不一致']);
        //生成消费记录 清除customid状态
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
        $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,flag,panterid) VALUES ('{$tradeid}',";
        $orderSql.="'{$cardno}','{$cardInfo['num']}','03','{$panterid}')";
        $orderif=$this->model->execute($orderSql);
        if($tradeif==true&&$customif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            $this->writeLog('singletui_success',json_encode(['cardno'=>$cardno,'tradeid'=>$tradeid,'fefund'=>$cardInfo['amount']]));
            returnMsg(['status'=>'1','codemsg'=>'退卡成功','tradeid'=>$tradeid,'fefund'=>$cardInfo['amount']]);
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
        $checkKey=md5($this->keycode.$panterid.$status.$flag);
        $this->checkSign($key, $checkKey);

        $panter=M('panters');
        $map['panterid']=$panterid;
        $where['g.panterid']=$panterid;
        $where['t.panterid']=$panterid;
        $where['t.status']='1';
        if($status=='01'){
            $where['g.status']='1';
        }
        if($flag=='1'){
            $where['g.type']='1';
        }elseif($flag=='2'){
            $where['g.type']='2';
        }
        $list = $panter->where($map)->find();
        isset($list)==true||returnMsg(['status'=>'02','codemsg'=>'该商户不存在']);
        $list['parent']==$this->parent||returnMsg(['status'=>'03','codemsg'=>'不是大食堂商户']);
        $field='t.type,t.name,g.goodsname,g.price,g.goodsid,g.status';
        $goods=M('green_type')->alias('t')
            ->join('left join green_goods g on g.type=t.type')
            ->where($where)->field($field)->order('price asc')->select();
        if($flag=='2'){
            foreach($goods as $key=>$val){
                $val['price']='';
                $goods[$key]=$val;
            }

        }
        if($goods==true){
            if($flag!='2'){
                foreach($goods as $key=>$val){
                    $val['price']=floatval($val['price']);
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
        $checkKey=md5($this->keycode.$cardno.$panterid.$price.$amount.$order.$termno);
        $this->checkSign($key, $checkKey);
//        $cardno='6882371899900013664';
//        $panterid='00000270';
//        $price=31.30;
//        $amount=4;
//        $order='[{"goodsname":"开封灌汤包","goodsid":"1","type":"1","num":"3"},{"goodsname":"大白米","goodsid":"4","type":"4","num":"1"}]';
        //检测卡
        $method=__FUNCTION__;
        $cardInfo=$this->valiCard($cardno,$method);
        $this->valiPanterid($panterid,$method);
        //
        $order=json_decode($order,true);
        $sum=0;
        foreach($order as $val){
            if($val['type']=='1'){
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
                if($list==true){
                    $sum=bcadd($sum,bcmul($list['price'],$val['num'],2),2);
                }else{
                    returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }else{
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
                if($list==true){
                    $sum=bcadd($sum,$val['price'],2);
                }else{
                    returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }
        }
        $sum==$price||returnMsg(['status'=>'06','codemsg'=>'订单总金额不对']);
//        获取用户信息以及账户
        $map=['ca.cardno'=>$cardno,'ac.type'=>'00'];
        //赠送卡无卡底费
        if($cardInfo['cardfee']=='3'){
            $this->frozen = 0;
        }
        bcsub($cardInfo['amount'],$price,2)>=$this->frozen||returnMsg(['status'=>'09','codemsg'=>"卡余额不足"."\n"."余额：".floatval($cardInfo['amount']),'amount'=>$cardInfo['amount']]);
        $yue=bcsub($cardInfo['amount'],$price,2);
        $this->model->startTrans();
        //生成消费记录
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单
        foreach($order as $val){
            $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
            if($val['type']=='1'){
                $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$cardInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$list['price']}','{$val['num']}','02','{$placeddate}','{$placedtime}','{$panterid}')";
            }else{
                $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$cardInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$val['price']}','{$val['num']}','02','{$placeddate}','{$placedtime}','{$panterid}')";
            }
            $orderif=$this->model->execute($orderSql);
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'消费成功','tradeid'=>$tradeid,'balance'=>$yue]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }
    }

    //退菜前获取订单
    public function returnFood(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid);
        $this->checkSign($key,$checkKey);

        $method=__FUNCTION__;
        $cardInfo  = $this->valiCard($cardno,$method);
        $panterlist= $this->valiPanterid($panterid,$method);
        if($cardInfo['cardflag']=='off'){
            returnMsg(['status'=>'09','codemsg'=>'此卡已经退卡']);
        }
        //flag 01充值 02消费 03 退卡 04退菜
        //分类查询  获取消费菜品
        $normal=['type'=>1,'panterid'=>$panterid,'cardno'=>$cardno,'cardnum'=>$cardInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];
        $resNormal=$this->searchClass($normal);
        $resNormal=$this->returnGoods($normal,$resNormal);
        $other=['type'=>'2','panterid'=>$panterid,'cardno'=>$cardno,'cardnum'=>$cardInfo['num'],'flag'=>'02','placeddate'=>date('Ymd')];
        $resOther=$this->searchClass($other);
        if($resOther==true){
            $resOther=$this->returnGoods($other,$resOther);
        }
        //合并返回
        $data=$this->combine($resNormal,$resOther,$method);
        foreach($data as $key=>$val){
            $val['price']=floatval($val['price']);
            $data[$key]=$val;
        }
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$data]);
    }

    //退单操作
    public function returnHandle(){
        $cardno =$_POST['cardno'];//卡号
        $panterid=$_POST['panterid'];
        $price=$_POST['price'];
        $termno=$_POST['pos_id'];
        $order=$_POST['data'];
        $tradetype=$_POST['tradetype'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid.$price.$order.$termno.$tradetype);
        $this->checkSign($key,$checkKey);

        $tradetype==$this->reFoods||returnMsg(array('status'=>'07','codemsg'=>'不是退菜交易，不能退菜'));;
        $order=json_decode($order,true);
        $method = __FUNCTION__;
        $cardInfo  =$this->valiCard($cardno,$method);
        $cardInfo['cardflag']=='on'||returnMsg(['status'=>'07','codemsg'=>'此卡已退卡,不能退菜']);
        //
        $sum=0;
        foreach($order as $val){
            $totalNum=$this->refundNum($val,$panterid,$cardno,$cardInfo['num']);
            if($totalNum<$val['num']){
                returnMsg(['status'=>'09','codemsg'=>'该商品:'.$val['goodsname'].'数量不足']);
            }
            if($val['type']=='1'){
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
                if($list==true){
                    $sum=bcadd($sum,bcmul($list['price'],$val['num'],2),2);
                }else{
                    returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
                }
            }else{
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
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
        $sum==$price||returnMsg(['status'=>'06','codemsg'=>'订单总金额不对']);
        $yue=bcadd($cardInfo['amount'],$price,2);
        $this->model->startTrans();
        //生成消费记录
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','{$tradetype}','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        //写入订单
        foreach($order as $val){
            if($val['type']=='1'){
                $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$cardInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$val['price']}','{$val['num']}','04','{$placeddate}','{$placedtime}','{$panterid}')";
            }else{
                $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$cardInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$val['price']}','{$val['num']}','04','{$placeddate}','{$placedtime}','{$panterid}')";
            }
            $orderif=$this->model->execute($orderSql);
            if($orderif==false){
                $this->model->rollback();
                returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
            }
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'退菜成功','tradeid'=>$tradeid,'balance'=>$yue]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库操作失败']);
        }

    }
    //消费详情
    public function detail(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        $method=__FUNCTION__;
        $checkKey=md5($this->keycode.$cardno.$panterid);
        $this->checkSign($key, $checkKey);
        $cardInfo=$this->valiCard($cardno,$method);
        //收银台商户验证
        $panterid==$this->chongPanterid||returnMsg(array('status'=>'08','codemsg'=>'该商户没有查询订单详情权限'));
        $cardInfo['cardflag']=='on'||returnMsg(array('status'=>'07','codemsg'=>'该卡已经退卡,无法查询明细'));
        //查询订单详情
        $map=['cardno'=>$cardno,'flag'=>'02','cardnum'=>$cardInfo['num']];
        $sql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,cardno,cardnum from green_order where cardno='{$map['cardno']}' AND cardnum='{$map['cardnum']}' ";
        $sql.="AND flag='{$map['flag']}' group by goodsname,price,goodsid,type,panterid,flag,cardno,cardnum)";
        $mapWhere=['o.cardno'=>$cardno,'o.cardnum'=>$cardInfo['num'],'o.flag'=>'02'];
        $orderlists=$this->model->table($sql)->alias('o')
            ->join('left join panters p on p.panterid=o.panterid')
            ->field('p.namechinese,o.goodsname,o.type,o.price,o.num,o.flag,o.goodsid')
            ->where($mapWhere)->select();
        //t退菜
        $rewhere=['cardno'=>$cardno,'cardnum'=>$cardInfo['num'],'flag'=>'04'];
        $reMap=['o.cardno'=>$cardno,'o.cardnum'=>$cardInfo['num'],'o.flag'=>'04'];
        $reSql="(SELECT sum(num) as num,goodsname,price,goodsid,type,panterid,flag,cardnum,cardno from green_order where cardno='{$rewhere['cardno']}' AND cardnum='{$rewhere['cardnum']}' ";
        $reSql.="AND flag='{$rewhere['flag']}' group by goodsname,price,goodsid,type,panterid,flag,cardnum,cardno)";
        $refundlist=$this->model->table($reSql)->alias('o')
            ->join('left join panters p on p.panterid=o.panterid')
            ->field('p.namechinese,o.goodsname,o.type,o.price,o.num,o.flag,o.goodsid')
            ->where($reMap)->select();
        $orderlists==true||returnMsg(array('status'=>'07','codemsg'=>'无消费信息'));
        if($refundlist==false){
            $refundlist='';
        }else{
            //除去 退款订单的
            $orderlists=$this->subRe($orderlists,$refundlist);
            $refundlist=$this->getFloat($refundlist,'price');
        }
        $orderlists=$this->getFloat($orderlists,'price');
        ///=======查询合卡出入账  ---2016  09 30---
        $combine=$this->combineDetail(['cardno'=>$cardno,'num'=>$cardInfo['num']]);
        //=------- end
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$orderlists,'refund'=>$refundlist,'in'=>(string)floatval($combine['in']),'out'=>(string)floatval($combine['out'])]);
    }

    //紧急扣款
    public function emergency(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $price=$_POST['price'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid.$price.$termno);
        $this->checkSign($key, $checkKey);
        $this->writeLog('emergency_get',json_encode(['cardno'=>$cardno,'panterid'=>$panterid,'termno'=>$termno,'price'=>$price]));
        $method=__FUNCTION__;
        if(!in_array($panterid,$this->emPanterid)){
            returnMsg(array('status'=>'07','codemsg'=>'该商户不能进行紧急预案消费'));
        }
        $cardInfo=$this->valiCard($cardno,$method);
        $this->valiPanterid($panterid,$method);
        $price>0||returnMsg(array('status'=>'08','codemsg'=>'消费金额应大于0'));
        $cardInfo['cardflag']=='on'||returnMsg(['status'=>'03','codemsg'=>'此卡已退卡或未充值']);
        //计算 账户可以消费 的余额 $ableAccount
        //赠送卡 无卡底费
        if($cardInfo['cardfee']=='3'){
            $this->frozen = 0;
        }
        $ableAccount=bcsub($cardInfo['amount'],$this->frozen,2);
        if($panterid==$this->QemPanterid){
            $ableAccount=$cardInfo['amount'];
        }
        $ableAccount>=$price||returnMsg(array('status'=>'09','codemsg'=>'卡余额不足,可用余额为:'.floatval($ableAccount)));
        $yue=bcsub($cardInfo['amount'],$price,2);
        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        //查询是否有特殊消费信息
        $map=['panterid'=>$panterid,'goodsid'=>'1'];
        $bool=M('green_goods')->where($map)->find();
        if($bool==false){
            $goodsSql="INSERT INTO green_goods(goodsid,panterid,type,goodsname,status) VALUES ('1','{$panterid}','2','特殊消费','1')";
            $typeSql="INSERT INTO green_type(panterid,type,name,status) VALUES ('{$panterid}','2','特殊','1')";
            $goodif=$this->model->execute($goodsSql);
            $typeif=$this->model->execute($typeSql);
            if($goodif==true&&$typeif==true){
            }else{
                returnMsg(['status'=>'07','codemsg'=>'数据库写入失败']);
            }
        }
        $ordeSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,price,num,flag,placeddate,placedtime,panterid,goodsname) VALUES ";
        $ordeSql.="('{$tradeid}','{$cardno}','{$cardInfo['num']}','1','2','{$price}' ,'1','02','{$placeddate}','{$placedtime}','{$panterid}','特殊消费')";
        $orderif=$this->model->execute($ordeSql);
        $accountif=M('account')->where(['customid'=>$cardInfo['customid'],'type'=>'00'])->save(['amount'=>$yue]);
        if($tradeif==true&&$orderif==true&&$accountif==true){
            $this->model->commit();
            $this->writeLog('emergency_success',['cardno'=>$cardno,'tradeid'=>$tradeid,'price'=>$price,'balance'=>$yue]);
            returnMsg(['status'=>'1','codemsg'=>'消费成功','yue'=>$yue,'tradeid'=>$tradeid]);
        }else{
            returnMsg(['status'=>'09','codemsg'=>'数据库操作失败']);
        }
    }

    //======充值端统计充值钱
    public function chongDatail(){
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid);
        $this->checkSign($key, $checkKey);
        $map=['panterid'=>$panterid,'placeddate'=>date('Ymd'),'flag'=>'1'];
        $panterid==$this->cardPanterid||returnMsg(array('status'=>'02','codemsg'=>'此商户无权查询前台充值详情'));
        $chonglist=M('card_purchase_logs')->where($map)->field('nvl(sum(amount),0) amount')->select();
        $chong    = $chonglist[0]['amount'];
        $where=['panterid'=>$panterid,'placeddate'=>date('Ymd'),'flag'=>'0','tradetype'=>'30'];
        //退卡金额
        $refundlist=M('trade_wastebooks')->where($where)->field('nvl(sum(tradeamount),0) amount')->select();
        $refund    = $refundlist[0]['amount'];
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>['chong'=>$chong,'refund'=>$refund]]);
    }
    //-----------合卡消费
    //1查询卡用卡余
    public function yue(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid.$termno);
        $this->checkSign($key, $checkKey);
        $method=__FUNCTION__;
        //
        $cardInfo = $this->valiCard($cardno,$method);
        $this->valiPanterid($panterid,$method);
        $this->frozen = $this->giftCardFrozen($cardInfo['cardfee']);
        if($cardInfo['amount']<=$this->frozen){
            returnMsg(['status'=>'03','codemsg'=>'无可以消费余额']);
        }
        $yue=bcsub($cardInfo['amount'],$this->frozen,2);
        returnMsg(['status'=>'1','codemsg'=>'查询成功','yue'=>$yue]);
    }
    //合卡消费
    /*
     * @param $cardno    主卡卡号
     * @param $panterid  消费的商户号
     * @param $termno    终端号
     * @param $cardlist  合卡的卡号与金额
     * @param $amount    菜的总金额
     * @param $order     订单
     * @param $accountZ  主账户
     * @param $accountS  附属卡账户
     * @param $realCut   真正的扣款金额
     */
    public function combineCard(){
        $cardno   = trim($_POST['cardno']);
        $panterid = trim($_POST['panterid']);
        $termno   = trim($_POST['pos_id']);
        $carddata = trim($_POST['cardlist']);
        $amount   = trim($_POST['amount']);
        $order    = trim($_POST['data']);
        $key      = $_POST['key'];
        $checkKey = md5($this->keycode.$cardno.$panterid.$termno.$carddata.$amount.$order);
        $this->checkSign($key, $checkKey);
        $method   = __FUNCTION__;
//        $cardno='6885371868800000052';
//        $panterid='00000547';
//        $termno='54700696';
//        $carddata='{"6885371868800000050":"2.0","6885371868800000038":"6.0"}';
//        $amount=8.00;
//        $order= '[{"goodsname":"建业至尊传统糕点","goodsid":"6","type":"1","num":"1"}]';
        $carddata = json_decode($carddata,1);
        $order    = json_decode($order,1);
        if(array_key_exists($cardno,$carddata)){
            unset($carddata[$cardno]);
        }
        $amount>0||returnMsg(array('status'=>'06','codemsg'=>'消费金额必须大于零'));
        //1查询卡余额 合并
        $this->valiPanterid($panterid,$method);
        $cardZInfo=$this->valiCard($cardno,$method);
        $cardZInfo['cardflag']=='on'||returnMsg(array('status'=>'05','codemsg'=>'此卡已退卡'));
        if($cardZInfo['cardfee']=='3')returnMsg(array('status'=>'05','codemsg'=>'赠送卡无法参加合卡消费'));
        $sum=0;
        foreach($carddata as $key=>$val){
            $accountS = $this->valiCard($key,$method);
            if($accountS['cardfee']=='3')returnMsg(array('status'=>'05','codemsg'=>'赠送卡无法参加合卡消费'));
            $accountS['cardflag']=='on'||returnMsg(array('status'=>'05','codemsg'=>'合卡的已经退卡'));
            $cut=bcadd($this->frozen,$val,2);
            if($accountS['amount']<$cut){
                returnMsg(array('status'=>'15','codemsg'=>'此卡:'.$key.'金额不够合卡消费'));
            }
            $sum=bcadd($sum,$val,2);
        }
        $realAccount=bcsub($cardZInfo['amount'],$this->frozen,2);
        $realCut=bcadd($realAccount,$sum,2);
        $realCut==$amount||returnMsg(array('status'=>'16','codemsg'=>'要扣款金额与消费金额不一致'));
        //订单判定
        $consumeSum=0;
        foreach($order as $key=>$val){
            if($val['type']=='1'){
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
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
        $map=['cardno'=>$cardno,'num'=>$cardZInfo['num'],'tradeid'=>$tradeid];
        $this->getCombine($map,$carddata,$method);
        //主卡账户变更
        $accountZif=$this->model->table('account')->where(['customid'=>$cardZInfo['customid'],'type'=>'00'])->save(['amount'=>$this->frozen]);
        //生成消费记录
        $tac='abcdefgh';
        $flag='0';
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$amount}','0','{$cardZInfo['customid']}','{$cardno}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        //写入消费记录
        foreach($order as $val){
            if($val['type']=='1'){
                $list=M('green_goods')->where(['goodsid'=>$val['goodsid'],'type'=>$val['type'],'panterid'=>$panterid])->find();
                $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$cardZInfo['num']}','{$val['goodsid']}','{$val['type']}','{$val['goodsname']}','{$list['price']}','{$val['num']}','02','{$placeddate}','{$placedtime}','{$panterid}')";
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
    /* 商户查询 卡余额 包含卡费
    *
    */
    public function getYue(){
        $cardno=$_POST['cardno'];
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid.$termno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $method=__FUNCTION__;
        $this->valiPanterid($panterid,$method);
        $cardInfo = $this->valiCard($cardno,$method);
        returnMsg(['status'=>'1','amount'=>$cardInfo['amount']]);
    }
    /*
     * 各个档口 一周的交易金额
     */
    public function weekData(){
        $panterid=$_POST['panterid'];
        $termno=$_POST['pos_id'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$termno);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $method=__FUNCTION__;
        $inDate=[date('Ymd'),date('Ymd',time()-86400),date('Ymd',time()-2*86400),
            date('Ymd',time()-3*86400),date('Ymd',time()-4*86400),date('Ymd',time()-5*86400),
            date('Ymd',time()-6*86400)
        ];
        $this->valiPanterid($panterid,$method);
        if($panterid==C('getDrink')){
            $this->getDrink($panterid);
        }else{
            //消费的
            $map['flag']='02';
            $map['panterid']=$panterid;
            $map['placeddate']=['in',$inDate];
            $list=M('green_order')->where($map)->field('nvl(sum(price*num),0) esum,placeddate,flag')
                ->group('placeddate,flag')->order('placeddate desc')->select();
            //退菜的
            $map['flag']='04';
            $refundlist=M('green_order')->where($map)->field('nvl(sum(price*num),0) esum,placeddate,flag')
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
                        $res[]=['placeddate'=>$sv['placeddate'],'consume'=>$sv['esum'],'refund'=>$sv['refund']];
                    }
                }
                if($sbool==false){
                    $res[]=['placeddate'=>strval($inv),'consume'=>'0','refund'=>'0'];
                }
            }
            returnMsg(array('status'=>'1','data'=>$res,'flag'=>'01'));
        }
    }
    /*
     * 大食堂 烟酒商户 统计
     */
    private function getDrink($panterid){
        $map['placeddate']=date('Ymd');
        $map['panterid']=$panterid;
        $map['flag']='02';
        $getlist=M('green_order')->where($map)->field('sum(price*num) as csum,sum(num) as tnum,goodsname,goodsid,type,placeddate')
            ->group('goodsname,goodsid,type,placeddate')->select();
        //退菜
        $map['flag']='04';
        $refulist=M('green_order')->where($map)->field('sum(price*num) as csum,sum(num) as tnum,goodsname,goodsid,type,placeddate')
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
    //---------------------------------------------------------大食堂团卡模块-----------------------------------
    public function teamCharge(){
        $cardlist = $_POST['cardlist'];
        $amount   = $_POST['amount'];
        $num      = $_POST['num'];
        $price    = $_POST['price'];
        $panterid = $_POST['panterid'];
        $termno   = trim($_POST['pos_id']);
        $key      = $_POST['key'];
        $checkKey=md5($this->keycode.$cardlist.$panterid.$termno.$amount.$num.$price);
        $this->checkSign($key, $checkKey);
        $get = ['cardlist'=>$cardlist,'panterid'=>$panterid,'termno'=>$termno,'amount'=>$amount,'num'=>$num,'price'=>$price];
        $this->writeLog('teamcharge_get',json_encode($get));
        $cardlist=json_decode($cardlist,1);
        //检测商户
        $panterid==$this->chongPanterid||returnMsg(['status'=>'02','codemsg'=>'不是大食堂充值端']);
        //结算金额 是否正确

	    //充值限额
	    $this->chargeLimit($amount);

        $count=count($cardlist);
        $count==$num||returnMsg(['status'=>'03','codemsg'=>'充值卡总数量不对']);
        $countSum=bcmul($count,$amount,2);
        $countSum==$price||returnMsg(['status'=>'04','codemsg'=>'充值总金额不对']);
        $method  = __FUNCTION__;
        $teamid='team_id'.date('YmdHis').substr($cardlist[0],-4);
        $cardinfo  = $this->cardsValiStatus($cardlist, $count, $method);
        //生成团卡充值序列
        $batch    = $this->teamChargeBatchNumber($num);
        $map=['paymenttype'=>'现金','amount'=>$amount,
              'panterid'=>$panterid,'userid'=>$this->userid,
              'teamid'=>$teamid,'batch'=>$batch

        ];
        $this->model->startTrans();
        $bool=$this->exTeamCharge($map,$cardinfo);
        if($bool==true){
            $this->model->commit();
            $this->writeLog('teamcharge_susccess',json_encode(['teamid'=>$teamid,'cardlist'=>$cardlist]));
            returnMsg(['status'=>'1','codemsg'=>'团购成功','teamid'=>$teamid]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'30','codemsg'=>'操作数据库异常']);
        }

    }
    private function cardsValiStatus($cardlist, $count, $method){
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
            $this->handdle($method,$error,$decription);
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
    private function teamChargeBatchNumber($num){
        //'customid'=>8,'purchaseid'=>12,'auditid'=>16,
        $cardpurchases = $this->getFieldBatchNumber('cardpurchaseid', $num);
        $purchases     = $this->getFieldBatchNumber('purchaseid', $num);
        $audits        = $this->getFieldBatchNumber('auditid', $num);
        $batch         = [];
        for ($i=0;$i<$num;$i++){
            $customid  = str_pad($cardpurchases[$i]['nextval'],18,'0',STR_PAD_LEFT);
            $purchaseid= str_pad($purchases[$i]['nextval'],12,'0',STR_PAD_LEFT);
            $auditid   = str_pad($audits[$i]['nextval'],16,'0',STR_PAD_LEFT);
            $batch[]= ['cardpurchaseid'=>$customid,'purchaseid'=>$purchaseid,'auditid'=>$auditid];
        }
        return $batch;
    }
     //批量获取表 字段 序列值
    public function getFieldBatchNumber($field, $num){
        $sql    = "SELECT seq_{$field}.nextval FROM (SELECT 1 FROM all_objects where rownum <= $num)";
        $batch  = $this->model->query($sql);
        return $batch;
    }
    private function exTeamCharge($map,$info){
        $currentDate  = date('Ymd');
        $checkDate    = date('Ymd');
        $placddate    = date('Ymd');
        $placdtime    = date('H:i:s');
        $customplSql  = '';
        $auditlogsSql = '';
        $cardplSql    = '';
        $teamSql      = '';
        $orderSql     = '';
        $batch        = $map['batch'];
        foreach($info as $key =>$val){
            $purchaseid    =$batch[$key]['purchaseid'];
            $auditid       =$batch[$key]['auditid'];
            $cardpurchaseid=$batch[$key]['cardpurchaseid'];
            $val['num']+=1;
            if($key==0){
                //购卡单
                $customplSql.="INSERT ALL ";
                //审核
                $auditlogsSql.="INSERT ALL ";
                //充值单记录
                $cardplSql.="INSERT ALL ";
                //订单表
                $orderSql.="INSERT ALL ";
                // 团冲记录写入
                $teamSql.="INSERT ALL ";
            }
            $customplSql.=" into custom_purchase_logs values('".$val['customid']."','{$purchaseid}','".$currentDate."','{$map['paymenttype']}','";
            $customplSql.=$map['userid']."','".$map['amount']."',NULL,'".$map['amount']."',0,'".$map['amount']."','".$map['amount'];
            $customplSql.="',1,'','','1','".$map['panterid']."','".$map['userid']."',NULL,'1',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

            $auditlogsSql.=" into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
            $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$map['userid'] ."','".date('H:i:s',time()+300)."')";

            $cardplSql.=" INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$val['cardno']}','{$map['amount']}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
            $cardplSql.="'{$map['userid']}','{$map['panterid']}','','00000000')";

            $orderSql.=" INTO green_order (tradeid,cardno,cardnum,flag,panterid) VALUES ('{$purchaseid}',";
            $orderSql.="{$val['cardno']},'{$val['num']}','01','{$map['panterid']}')";

            $teamSql.=" INTO green_team(teamid,purchaseid,placeddate,placedtime) VALUES (";
            $teamSql.="'{$map['teamid']}','{$purchaseid}','{$placddate}','{$placdtime}')";
            // 修改账户金额
            $customsInfo[] = $val['customid'];
        }
        $customplSql.=" SELECT 1 FROM DUAL";
        $auditlogsSql.=" SELECT 1 FROM DUAL";
        $cardplSql.=" SELECT 1 FROM DUAL";
        $orderSql.=" SELECT 1 FROM DUAL";
        $teamSql.=" SELECT 1 FROM DUAL";

        //修改卡使用次数-----2017--10--25 by wan
        $inCustomid = $this->inString($customsInfo);
        $sql        = "UPDATE customs set num=nvl(num,0)+1,cardflag='on',customlevel='大食堂团购客户' where customid in ($inCustomid)";
        $customNumIf= $this->model->execute($sql);
        //--end

        $customplIf = $this->model->execute($customplSql);
        $auditlogsIf= $this->model->execute($auditlogsSql);
        $cardplIf   = $this->model->execute($cardplSql);
        $orderif    = $this->model->execute($orderSql);
        $teamif     = $this->model->execute($teamSql);
        $accountif  = $this->model->table('account')
            ->where(['customid'=>['in',$customsInfo],'type'=>'00'])
            ->save(['amount'=>$map['amount']]);
        if($customNumIf==true && $customplIf==true && $auditlogsIf==true && $cardplIf==true && $orderif==true && $teamif==true && $accountif==true ){
            return true;
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'07','codemsg'=>'数据库写入失败不能充卡']);
        }
    }
    //团卡 退卡
    public function returnTeam(){
        $cardno   = $_POST['cardno'];
        $panterid = $_POST['panterid'];
        $termno   = $_POST['pos_id'];
        $getTeamid= $_POST['getTeamid'];
        $key      = $_POST['key'];
        $checkKey = md5($this->keycode.$cardno.$panterid.$termno.$getTeamid);
        $this->checkSign($key, $checkKey);
        $get      = ['cardno'=>$cardno,'panterid'=>$panterid,'termno'=>$termno,'getTeamid'=>$getTeamid];
        $this->writeLog('returnTeam_info',json_encode($get));

        $method=__FUNCTION__;
        $panterid==$this->chongPanterid||returnMsg(array('status'=>'02','codemsg'=>'此商户没有退卡权限'));
        $cardInfo = $this->valiCard($cardno,$method);
        if($cardInfo['customlevel']!=='大食堂团购客户'){
            returnMsg(array('status'=>'07','codemsg'=>'此卡号不是团购卡:'.$cardno.'不能退卡'));
        }
        //查询充值单号
        $mapPurcahse=['cardno'=>$cardno,'cardnum'=>$cardInfo['num']];
        //var $teamid 团购单号
        $teamid=$this->getTeamid($mapPurcahse);
        md5($teamid)==$getTeamid||returnMsg(array('status'=>'40','codemsg'=>'非法单号不能退卡'));

        //返回团卡相关信息
        $teamcardinfo = $this->cardTeamInfo(__FUNCTION__,$teamid);
        $customlists  = array_column($teamcardinfo,'customid');;
        $customdWhere=['customid'=>['in',$customlists]];

        $this->model->startTrans();
        $customIf=M('customs')->where($customdWhere)->save(['cardflag'=>'off','customlevel'=>'']);
        $customdWhere['type']='00';
        $account=M('account')->where($customdWhere)->save(['amount'=>'0']);
        //退卡记录写入
        $returnTeamid = $this->exTeamCards($teamcardinfo,['panterid'=>$panterid,'termno'=>$termno],$cardno);
        if($customIf==true &&$account==true){
            $this->model->commit();
            $this->writeLog('termtui_success',['teamid'=>$returnTeamid]);
            returnMsg(['status'=>'1','codemsg'=>'团退卡成功','teamid'=>$returnTeamid]);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'23','codemsg'=>'数据库操作失败,请重试']);
        }
    }
    private function isPurcahse($map){
        $map['flag']='01';
        $res=$this->model->table('green_order')->where($map)->field('tradeid')->find();
        if($res==true){
            $teamlist=$this->model->table('green_team')->where(['purchaseid'=>$res['tradeid']])->field('teamid')->find();
            if($teamlist==true){
                return $teamlist['teamid'];
            }else{
                returnMsg(array('status'=>'20','codemsg'=>'未查询到此卡团购单号:'.$map['cardno'].'num:'.$map['cardnum']));
            }
        }else{
            returnMsg(array('status'=>'21','codemsg'=>'未查询到此卡chon充值单号:'.$map['cardno'].'num:'.$map['cardnum']));
        }
    }
    //返回团卡 相关信息 卡号，customid，以及cardflag，num
    private function cardTeamInfo($method,$teamid){
        $lists = M('green_team')->where(['teamid'=>$teamid])->select();
        $count = count($lists);
        $list  = array_column($lists,'purchaseid');
        $where['cp.purchaseid']=['in',$list];
        $where['ac.type'] = '00';
        $info = $this->model->table('card_purchase_logs')->alias('cp')
            ->join('left join cards ca on ca.cardno=cp.cardno')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join account ac on ac.customid=cu.customid')
            ->field('ca.cardno,cu.customid,cu.num,ac.amount')
            ->where($where)->select();
        if($count!=count($info)){
            $error              = ['status'=>'05','codemsg'=>'团退卡查询账户信息数量异常'];
            $description        = $error;
            $description['msg'] = '团退卡的基于充值单号查询所有团卡信息';
            $this->handdle($method,$error,$description);
        }
        return $info;
    }
    /*
   * 团购卡 退卡各种记录写入
   * var $customlist 用户id序列
   * var $map['panterid'] 商户号 $map['termno'] 终端号
   * var $cardno 卡号
   */
    private function exTeamCards($info,$map,$cardno){
        $termno    = $map['termno'];
        $panterid  = $map['panterid'];
        $tradetype = 30;
        $date=time();
        $teamDate=$date;
        $teamid='team_tui'.date('YmdHis',$teamDate).substr($cardno,-4);
        $sql = '';
        $orderSql = '';
        $teamSql  = '';
        foreach($info as $key => $val){
            //生成消费记
            $placeddate=date('Ymd',$date);
            $placedtime=date('H:i:s',$date);
            $tradeid=$termno.date('YmdHis',$date);
            $tac='abcdefgh';
            $flag='0';
            if($key>=1){
                $sql.=" INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0')";

                $orderSql.=" INTO green_order(tradeid,cardno,cardnum,flag,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','03','{$panterid}')";
                //green_team 表
                $teamSql.=" INTO green_team (teamid,purchaseid,placeddate,placedtime) VALUES (";
                $teamSql.="'{$teamid}','{$tradeid}','{$placeddate}','{$placedtime}')";
            }else{
                $sql.="INSERT ALL INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','{$tradetype}','{$tac}','0')";
                // green_order 表
                $orderSql.="INSERT ALL INTO green_order(tradeid,cardno,cardnum,flag,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$val['cardno']}','{$val['num']}','03','{$panterid}')";
                //green_team 表
                $teamSql.="INSERT ALL INTO green_team (teamid,purchaseid,placeddate,placedtime) VALUES (";
                $teamSql.="'{$teamid}','{$tradeid}','{$placeddate}','{$placedtime}')";
            }
            $date++;
            //tw 表
        }
        $sql.= " SELECT 1 FROM DUAL";
        $orderSql.= " SELECT 1 FROM DUAL";
        $teamSql.= " SELECT 1 FROM DUAL";
        $tradeif=$this->model->execute($sql);
        $orderif=$this->model->execute($orderSql);
        $teamif=$this->model->execute($teamSql);
        if($tradeif==true&&$orderif==true&&$teamif==true){
            return $teamid;
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'36','codemsg'=>'退卡交易记录生成失败'));
        }
    }
    //----------------------------------------------------------赠送卡处理---------------------------------
    public function griftCard(){
        $panterid    = $_POST['panterid'];
        $termno      = $_POST['pos_id'];
        $cardno      = $_POST['cardno'];
        $namechinese = trim($_POST['namechinese']);
        $linktel     = trim($_POST['linktel']);
//        $personid    = trim($_POST['personid'])?:'';
        $personid    = trim(I('post.personid',''));
        $key         = $_POST['key'];
        $checkKey=md5($this->keycode.$cardno.$panterid.$termno.$namechinese.$linktel.$personid);
        $this->checkSign($key, $checkKey);
        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'该商户无权补卡']);

        $map['cardno']= $cardno;
        $map['cardfee']= '3';
        $map['status']= 'N';
        if(empty($namechinese)||empty($linktel)) returnMsg(['status'=>'04','codemsg'=>'用户名或手机号不能为空']);
        $cardInfo    = $this->model->table('cards')->where($map)->find();
        $cardInfo==true || returnMsg(['status'=>'03','codemsg'=>'该卡无法作为赠送卡:'.$cardno]);
        $customid  = $this->getFieldNextNumber('customid');
        $purchaseid=$this->getFieldNextNumber('purchaseid');
        $open      = ['customid'=>$customid,'cardno'=>$cardno,'panterid'=>$panterid,'purchaseid'=>$purchaseid];
        $this->model->startTrans();
        $bool = $this->giftopenCard($open);
        $customsSql = "insert into customs (customid,namechinese,linktel,personid) values('{$customid}','{$namechinese}','{$linktel}','{$personid}')";
        $customsIf  = $this->model->execute($customsSql);
        if($bool===true  && $customsIf==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'开卡成功']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'10','codemsg'=>'数据库写入失败']);
        }


    }
    private function giftopenCard($open){
        $customid   = $open['customid'];
        $cardno     = $open['cardno'];
        $panterid   = $open['panterid'];
        $userid     = $this->userid;
        $purchaseid = $open['purchaseid'];
        $currentDate= date('Ymd');
        $checkDate  = date('Ymd');
        $rechargeMoney= '0';
        $paymenttype= '现金';
        //写入购卡单并审核
        $customplSql="insert into custom_purchase_logs(CUSTOMID,PURCHASEID,PLACEDDATE,PAYMENTTYPE,USERID,AMOUNT,CARD_NUM,TOTALMONEY,";
        $customplSql.="POINT,REALAMOUNT,WRITEAMOUNT,WRITENUMBER,CHECKNO,DESCRIPTION,FLAG,PANTERID,CHECKID,DESCRIPTION1,";
        $customplSql.="TRADEFLAG,CUSTOMFLAG,DAYFLAG,TERMPOSNO,PLACEDTIME,CHECKDATE,CHECKTIME,ADITID) ";
        $customplSql.="VALUES('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
        $customplSql.=",1,'','购卡','1','{$panterid}','".$userid."',NULL,'0',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
        $customplIf=$this->model->execute($customplSql);

        //写入审核单
        //$auditid=$this->getnextcode('audit_logs',16);
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs(auditid,purchaseid,TYPE,decription,placeddate,audituser,placedtime) values ('".$auditid."','".$purchaseid."','审核通过',";
        $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
        $auditlogsIf=$this->model->execute($auditlogsSql);

        //写入购卡充值单
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $cardplSql="INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
        $cardplSql.="FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台开卡',";
        $cardplSql.="'{$userid}','{$panterid}','','00000000')";
        $cardplIf=$this->model->execute($cardplSql);

        //执行激活操作
        $cardAlSql="INSERT INTO card_active_logs(CARDNO,USERID,EXDATE,CARDBALANCE,STATUS,LINKTEL,ACTIVEDATE,ACTIVETIME,DESCRIPTION,CUSTOMID,PANTERID,TERMINAL_ID) ";
        $cardAlSql.=" VALUES('{$cardno}','{$userid}',".date('Ymd');
        $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$customid}'";
        $cardAlSql.=",'{$panterid}','00000000')";
        $cardAlIf=$this->model->execute($cardAlSql);

        //关联会员卡号
        $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customid."')";
        $customsIf=$this->model->execute($customcSql);

        //更新卡状态为正常卡，更新卡有效期；
        $exd=date('Ymd',strtotime("+3 years"));
        $cardSql="UPDATE cards SET status='Y',customid='{$customid}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
        $cardIf=$this->model->execute($cardSql);

        //给卡片添加账户并给账户充值
        $acid = $this->getFieldNextNumber('accountid');
        $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $balanceSql.=$acid."','".$customid."','".$rechargeMoney."','00',NULL)";
        $balanceIf=$this->model->execute($balanceSql);

        $acid = $this->getFieldNextNumber('accountid');
        $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $coinSql.=$acid."','".$customid."','0','01',NULL)";
        $coinIf=$this->model->execute($coinSql);

        $acid = $this->getFieldNextNumber('accountid');
        $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $pointSql.=$acid."','".$customid."','0','04',NULL)";
        $pointIf=$this->model->execute($pointSql);

        if($customplIf && $cardpurchaseid && $cardplIf && $cardAlIf && $customsIf && $cardIf && $balanceIf && $coinIf && $pointIf){
            return true;
        }else{
            return false;
        }
    }
    //补卡钱获取可用的补卡
    public function getCardsLock(){
        $panterid = $_POST['panterid'];
        $termno   = $_POST['pos_id'];
        $key      = $_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$termno);
        $this->checkSign($key, $checkKey);

        $panterid==$this->chongPanterid || returnMsg(['status'=>'03','codemsg'=>'该商户无权补卡']);
        $map['cl.active']  = '0';
        $map['ca.panterid']= $panterid;
        $map['ca.cardfee'] = '3';
        $list   = M('card_locks_log')->alias('cl')
                                     ->join('cards ca on cl.cardno=ca.cardno')
                                      ->where($map)->field('ca.cardno')->select();
        if($list==false){
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>'']);
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
        $newMap       = ['panterid'=>$panterid,'cardfee'=>'3','status'=>'N'];
        $newInfo      = $this->model->table('cards')->where($newMap)->find();
        $newInfo==true || returnMsg(['status'=>'04','codemsg'=>'此卡新卡号不存在:'.$cardno]);
        $this->valireissueCard($oldcard);
        $oldInfo  = $this->cardAccount($oldcard);
        $map['cardno'] = $oldcard;
        $save['cardno']= $cardno;
        $this->model->startTrans();
        if($this->model->table('green_order')->where($map)->select()){
            $orderIf     = $this->model->table('green_order')->where($map)->save($save);
            if($this->model->table('trade_wastebooks')->where($map)->select()){
                $tradeIf = $this->model->table('trade_wastebooks')->where($map)->save($save);
            }else{
                $tradeIf = true;
            }

        }else{
            $orderIf     = true;
            $tradeIf     = true;
        }
        $purchaseIf  = $this->model->table('card_purchase_logs')->where($map)->save($save);
        $oldcardIf   = $this->model->table('cards')->where($map)->save(['customid'=>'0']);
        $cardIf      = $this->model->table('cards')->where($save)->save(['customid'=>$oldInfo['customid'],'status'=>'Y']);
        $lockIf      = $this->model->table('card_locks_log')->where($map)->save(['active'=>'2']);
        if($orderIf==true && $tradeIf==true && $purchaseIf==true && $oldcardIf==true && $cardIf==true && $lockIf==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'补卡成功']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'06','codemsg'=>'修改数据库失败,补卡卡失败']);
        }
    }
    private function valireissueCard($oldcard){
        $map['cl.active']  = '0';
        $map['ca.panterid']= $this->chongPanterid;
        $map['ca.cardfee'] = '3';
        $map['ca.cardno']  = $oldcard;
        $list   = M('card_locks_log')->alias('cl')
                                    ->join('cards ca on cl.cardno=ca.cardno')
                                    ->where($map)->field('ca.cardno')->select();
        $list==true || returnMsg(['status'=>'04','codemsg'=>'此卡补卡记录不存在:'.$oldcard]);
    }
    //---------------------------------------------------------菜品管理模块------------------------------
    public function addDish(){
        $panterid=$_POST['panterid'];
        $goodsname=$_POST['goodsname'];
        $typename=$_POST['typename'];
        $price=$_POST['price'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$goodsname.$typename.$price);
        $this->checkSign($key, $checkKey);
        $list=$this->valiPanterid($panterid);
        $this->model->startTrans();
        if($typename=='主食'){
            $map=['panterid'=>$panterid,'goodsname'=>$goodsname,'price'=>$price,'type'=>'1','typename'=>$typename];;
            $goodsif=$this->addGoods($map);
            //初始化类别
            $flag='1';
            $typeif=$this->addType($map,$flag);
        }elseif($typename=='其他'){
            $map=['panterid'=>$panterid,'goodsname'=>$goodsname,'typename'=>'其他','type'=>'2'];
            if($goodsname=='特殊消费'){
                returnMsg(['status'=>'05','codemsg'=>'特殊消费是敏感字段,请换名字']);
            }
            $goodsif=$this->addGoods($map);
            //初始化类别
            $flag='2';
            $typeif=$this->addType($map,$flag);
        }
        if($goodsif==true&&$typeif==true){
            $this->model->commit();
            returnMsg(['status'=>'1','codemsg'=>'新增商品成功']);
        }else{
            $this->model->rollback();
            returnMsg(['status'=>'02','codemsg'=>'数据库写入失败']);
        }
    }
    public function removeDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$goodsid);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->valiPanterid($panterid);
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $list=$this->searchGoods($map);
        if($list['status']!='1'){
            returnMsg(['status'=>'15','codemsg'=>'该商品不是在线商品']);
        }
        $editif=M('green_goods')->where($map)->save(['status'=>'2']);
        if($editif==true){
            returnMsg(array('status'=>'1','codemsg'=>'商品下架成功'));
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
        }
    }
    public function onDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$goodsid);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->valiPanterid($panterid);
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $list=$this->searchGoods($map);
        if($list['status']!='2'){
            returnMsg(['status'=>'15','codemsg'=>'该商品不是下架商品']);
        }
        $editif=M('green_goods')->where($map)->save(['status'=>'1']);
        if($editif==true){
            returnMsg(array('status'=>'1','codemsg'=>'商品上架成功'));
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
        }
    }
    public function editDish(){
        $panterid=$_POST['panterid'];
        $goodsid=$_POST['goodsid'];
        $data=$_POST['data'];
        $key=$_POST['key'];
        $checkKey=md5($this->keycode.$panterid.$goodsid.$data);
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $data=json_decode($data,1);
        $this->valiPanterid($panterid);
        $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
        $list=$this->searchGoods($map);
        if($list['status']!='1'){
            returnMsg(['status'=>'15','codemsg'=>'该商品不是在线商品']);
        }
        //校验字段是否与数据库相同
        foreach($data as $key=>$val){
            if($data[$key]==$list[$key]){
                $bool=true;
            }else{
                $bool=false;
                break;
            }
        }
        if($bool==true){
            returnMsg(['status'=>'04','codemsg'=>'无有效信息需要编辑']);
        }
        $editif=M('green_goods')->where($map)->save($data);
        if($editif==true){
            returnMsg(['status'=>'1','codemsg'=>'商品编辑成功']);
        }else{
            returnMsg(['status'=>'03','codemsg'=>'数据库操作失败']);
        }
    }
    //日结算
    public function daliyBalance(){
        $panterid=$_POST['panterid'];
        $key=$_POST['key'];
        $checkKey=md5(md5($this->keycode.$panterid));
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $this->valiPanterid($panterid);
        //结算该商户消费金额
        $consume=M('trade_wastebooks')->where(['panterid'=>$panterid,'tradetype'=>'00','flag'=>'0','placeddate'=>date('Ymd')])->field('tradeamount')->select();
        if($consume==false){
            $consume=0.00;
        }else{
            $consume=array_column($consume,'tradeamount');
            $consume=$this->countAll($consume);
        }
        //退卡金额
        $refund=M('trade_wastebooks')->where(['panterid'=>$panterid,'flag'=>'0','tradetype'=>'31','placeddate'=>date('Ymd')])->field('tradeamount')->select();
        if($refund==false){
            $refund=0.00;
        }else{
            $refund=array_column($refund,'tradeamount');
            $refund=$this->countAll($refund);
        }
        $data=['consume'=>$consume,'refund'=>$refund];
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$data]);
    }
    private function addGoods($map){
        $goodslist=M('green_goods')->where(['panterid'=>$map['panterid']])->select();
        if($goodslist==false){
            $goodsid=1;
        }else{
            $namelist=M('green_goods')->where(['goodsname'=>$map['goodsname'],'panterid'=>$map['panterid'],'type'=>$map['type']])->find();
            if($namelist==true){
                returnMsg(['status'=>'03','codemsg'=>'商品名字重复']);
            }
            $goodsid=$this->getMax(array_column($goodslist,'goodsid'));
            $goodsid+=1;
        }
        $sql="INSERT INTO green_goods (goodsid,panterid,type,goodsname,price,status) VALUES ('{$goodsid}','{$map['panterid']}','{$map['type']}','{$map['goodsname']}','{$map['price']}','1')";
        $goodsif=$this->model->execute($sql);
        return $goodsif;
    }
    private function addType($map,$flag){
        if($flag=='1'){
            $typelist=M('green_type')->where(['panterid'=>$map['panterid'],'type'=>'1'])->find();
            if($typelist==false){
                $type=1;
                $sql="INSERT INTO green_type (panterid,type,name,status) VALUES ('{$map['panterid']}','{$type}','{$map['typename']}','1')";
                $typeif=$this->model->execute($sql);
            }else{
                $typeif=true;
            }
        }elseif($flag=='2'){
            $typelist=M('green_type')->where(['panterid'=>$map['panterid'],'type'=>'2'])->find();
            if($typelist==false){
                $type=2;
                $sql="INSERT INTO green_type (panterid,type,name,status) VALUES ('{$map['panterid']}','{$type}','{$map['typename']}','1')";
                $typeif=$this->model->execute($sql);
            }else{
                $typeif=true;
            }
        }
        return $typeif;
    }
    //查询goods 商品是否存在
    private function searchGoods($map){
        $list=M('green_goods')->where($map)->find();
        if($list==false){
            returnMsg(['status'=>'14','codemsg'=>'未查到该商品,请核实']);
        }
        return $list;
    }
    private function getMax($arr){
        rsort($arr);
        return $arr[0];
    }
    //-------------------------------------------------------- 子方法------------------------------
    private function getCardNum($info){
        if($info['cardflag']=='on'){
            return $info['num'];
        }else{
           if($info['num']==null&&$info['cardfee']=='3'){
               return 0;
           }
           return ($info['num']+1);
        }
    }
    private function exechong($map){
        $cardno           = $map['card'];
        $customid         = $map['cutsomid'];
        $rechargeMoney    = $map['amount'];
        $userid           = $this->greenuserid;
        $card['panterid'] = $map['panterid'];
        $paymenttype      = $map['paymenttype'];
        $currentDate      = date('Ymd');
        $checkDate        = date('Ymd');
        $purchaseid       = $map['Purchaseid'];

        $customplSql="insert into custom_purchase_logs values('".$customid."','{$purchaseid}','".$currentDate."','{$paymenttype}','";
        $customplSql.=$userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
        $customplSql.="',1,'','','1','".$card['panterid']."','".$userid."',NULL,'1',";
        $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

        //写入审核单
        $auditid=$this->getFieldNextNumber('auditid');
        $auditlogsSql="insert into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
        $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

        $customplIf=$this->model->execute($customplSql);
        $auditlogsIf=$this->model->execute($auditlogsSql);

        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        //写入充值单
        $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
        $cardplSql.="'{$userid}','{$card['panterid']}','','00000000')";
        $cardplIf=$this->model->execute($cardplSql);

        //更新卡片账户
        $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$customid."' and type='00'";
        $balanceIf=$this->model->execute($balanceSql);
        //判定会员情况
        $cardWhere['customid']=$customid;
        $customslist=M('customs')->where($cardWhere)->find();
        if($customslist['cardflag']=='off'||$customslist['cardflag']==null){
            $customif1=M('customs')->where($cardWhere)->save(['num'=>$map['num'],'cardflag'=>'on']);
            //充值单记录
            $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,flag,panterid) VALUES ('{$purchaseid}',";
            $orderSql.="{$cardno},'{$map['num']}','01','{$map['panterid']}')";
            $orderif=$this->model->execute($orderSql);
            if($orderif==true&&$customif1==true){
                $orderif=true;
            }else{
                $orderif=false;
            }

        }else{
            $orderSql="INSERT INTO green_order(tradeid,cardno,cardnum,flag) VALUES ('{$purchaseid}',";
            $orderSql.="{$cardno},'{$customslist['num']}','01')";
            $orderif=$this->model->execute($orderSql);
        }
        if($customplIf==true && $auditlogsIf==true && $cardplIf==true && $orderif==true){
            return ['msg'=>true,'purchaseid'=>$purchaseid];
        }else{
            return ['msg'=>false];
        }
    }

    //退卡前 返回团卡的信息
    private function teamInfo($cardInfo){
        $isteamFLag = '2';
        $cardno     = $cardInfo['cardno'];
        $mapTeam    = ['cardno'=>$cardno,'cardnum'=>$cardInfo['num']];
        //获取团冲单号
        $teamid     = $this->getTeamid($mapTeam);
        $this->writeLog('teamtui_get',json_encode(['cardno'=>$cardno,'teamid'=>$teamid]));
        // 查询 团冲订单 关联的卡号 与 会员id  ---2017--10--27 by wan
        $list       = M('green_team')->alias('t')
            ->join('left join card_purchase_logs cp on t.purchaseid=cp.purchaseid')
            ->join('left join custom_purchase_logs cu on cu.purchaseid=t.purchaseid')
            ->field('cp.purchaseid,cp.cardno,cu.customid')
            ->where(['t.teamid'=>$teamid])->select();
        // var $list 所有的充值单号
        $purlist   = array_column($list,'purchaseid');
        $where     = ['purchaseid'=>['in',$purlist]];
        //充值总金额 $chongSum
        $chong     = M('card_purchase_logs')->where($where)->field('nvl(sum(amount),0) amount')->select();
        $chongSum  = $chong[0]['amount'];

        //查询消费总金额 $conSum
        $subSql   = M('green_team')->alias('team')
            ->join('left join green_order k_o on team.purchaseid=k_o.tradeid')
            ->field('k_o.cardno,k_o.cardnum')
            ->where(['team.teamid'=>$teamid])->buildSql();
        //消费合计
        $consume = $this->model->table($subSql." sub")
            ->join("left join green_order kf on sub.cardno=kf.cardno and sub.cardnum=kf.cardnum")
            ->field('nvl(sum(kf.price*kf.num),0) consume')
            ->where(['kf.flag'=>'02'])->select();
        //退菜合计
        $refund =  $this->model->table($subSql." sub")
            ->join('left join green_order kf on sub.cardno=kf.cardno and sub.cardnum=kf.cardnum')
            ->field('nvl(sum(kf.price*kf.num),0) refund')
            ->where(['kf.flag'=>'04'])->select();
        $conSum = bcsub($consume[0]['consume'],$refund[0]['refund'],2);

        //accSum 账户余额合计
        $culist  = array_column($list,'customid');
        $cuWhere = ['customid'=>['in',$culist],'type'=>'00'];
        $account = M('account')->where($cuWhere)->field('nvl(sum(amount),0) amount')->select();
        $accSum  = $account[0]['amount'];
        $res['chong']=$chongSum;
        $res['consume']=$conSum;
        $res['account']=$accSum;
        $res['termid']=$teamid;
        if($chongSum!=bcadd($conSum,$accSum,2)){
            //合卡消费 出入账
            $combineSum=$this->newteamCombine($subSql);
            $res['consume']=bcsub(bcadd($res['consume'],$combineSum['out'],2),$combineSum['in'],2);
        }
        $this->writeLog('teamtui_info',json_encode($res));
        returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$res,'flag'=>$isteamFLag]);
    }
    /*
    * 优化合卡出入账 统计
    * $subSql 子查询 获取对应卡的
    */
    private function newteamCombine($subSql){
        $in  = $this->model->table($subSql." sub")
            ->join('left join green_combine kf on sub.cardno=kf.cardno and sub.cardnum=kf.innum')
            ->field('nvl(sum(amount),0) incard')
            ->select();
        $out = $this->model->table($subSql." sub")
            ->join('left join green_combine kf on sub.cardno=kf.outcardno and sub.cardnum=kf.outnum')
            ->field('nvl(sum(amount),0) outcard')
            ->select();
        return ['in'=>$in[0]['incard'],'out'=>$out[0]['outcard']];
    }
    //返回团卡 相关信息 卡号，customid，以及cardflag，num
    private function newcardTeamInfo($method,$teamid){
        $count = M('green_team')->where(['teamid'=>$teamid])->count();
        $info = $this->model->table('green_team')->alias('kf')
            ->join('green_order kd on kf.purchaseid=kd.tradeid')
            ->join('cards ca on ca.cardno=kd.cardno')
            ->field('ca.customid,kd.cardnum num,kd.cardno')
            ->where(['kf.teamid'=>$teamid])->select();
        if($count!=count($info)){
            $description = ['msg'=>'团退卡的基于充值单号查询所有团卡信息'];
            $error = ['status'=>'05','codemsg'=>'团退卡查询账户信息数量异常'];
            $this->errorHandle($method,$error,$description);
        }
        return $info;
    }
    //获取团冲单号
    private function getTeamid($map){
        $map['flag']='01';
        $res=$this->model->table('green_order')->where($map)->field('tradeid')->find();
        if($res==true){
            $teamlist=$this->model->table('green_team')->where(['purchaseid'=>trim($res['tradeid'])])->field('teamid')->find();
            if($teamlist==true){
                return $teamlist['teamid'];
            }else{
                returnMsg(array('status'=>'20','codemsg'=>'未查询到此卡团购单号:'.$map['cardno'].'num:'.$map['cardnum']));
            }
        }else{
            returnMsg(array('status'=>'21','codemsg'=>'未查询到此卡chon充值单号:'.$map['cardno'].'num:'.$map['cardnum']));
        }
    }
    //退卡前返回 单卡信息
    private function sigleCardInfo($cardInfo, $method){
        $isteamFlag='1';
        $cardno    = $cardInfo['cardno'];
        $res['account']=$cardInfo['amount'];
        //查询充值订单
        $map=['cardno'=>$cardno,'cardnum'=>$cardInfo['num']];
        $res['chong']=$this->searchChong($map,$method);
        //交易记录查询
        $res['consume']=$this->searchConsume($map);
        //退菜金额
        //---查询 是否有合卡消费
        $res['refund']=$this->getRefund($map);
        $combine=$this->combineDetail(['cardno'=>$cardno,'num'=>$cardInfo['num']]);
        $res['in']=(string)floatval($combine['in']);
        $res['out']=(string)floatval($combine['out']);
        //====== 20160929  16:00 flag1 正常退卡  2团购卡
        $orderMap = ['cardno'=>$cardno,'cardnum'=>$cardInfo['num'],'flag'=>['in',['02','04']]];
        $orderlist=M('green_order')->where($orderMap)
            ->field('goodsname,sum(price*num) as allpirce ,sum(num) as num,flag,panterid,price')
            ->group('goodsname,price,flag,panterid')
            ->select();
        $odertype=['02'=>'消费','04'=>'退菜'];
        $this->writeLog('singletui_info',json_encode(['cardno'=>$cardno,'msg'=>$res]));
        if($orderlist==true){
            foreach($orderlist as $key=>$val){
                $val['flag']=$odertype[$val['flag']];
                $orderlist[$key]=$val;
            }
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$res,'flag'=>$isteamFlag,'order'=>$orderlist]);
        }else{
            returnMsg(['status'=>'1','codemsg'=>'查询成功','data'=>$res,'flag'=>$isteamFlag,'order'=>'']);
        }
    }
    /* 查询大食堂卡充值记录
    * var $map 查询条件
    *
    */
    private function searchChong($map,$method){
        //
        $map['flag']='01';
        $orders=M('green_order')->where($map)->select();
        if($orders==false){
            $error=['status'=>'07','codemsg'=>'此卡无充值记录,不需要退卡'];
            $this->handdle($method,$error,$map);
        }
        $tradeWhere=array_column($orders,'tradeid');
        $where['purchaseid']=['in',$tradeWhere];
//        $where['cardno']=$map['cardno'];
        $res=M('card_purchase_logs')->where($where)->field('sum(amount) amount')->select();
        return $res[0]['amount'];
    }
    /*
     * 查询 大食堂卡消费记录
     * var $map 查询条件
     */
    private function searchConsume($map){
        $map['flag']='02';
        $consume=M('green_order')->where($map)->field('sum(price*num) amount')->select();
        return $consume[0]['amount']?:0;
    }
    //返回单卡退菜的金额
    private function getRefund($map){
        $map['flag']='04';
        $orders=M('green_order')->where($map)->field('tradeid')->select();
        if($orders==false){
            return 0;
        }
        $tradeWhere=array_column($orders,'tradeid');
        $where['tradeid']=['in',$tradeWhere];
        $res=M('trade_wastebooks')->where($where)->field('sum(tradeamount) amount')->select();
        return $res[0]['amount'];

    }
    //合卡明细查询
    private function combineDetail($map){
        $whereIn=['cardno'=>$map['cardno'],'innum'=>$map['num'],'status'=>'1'];
        $res=$this->model->table('green_combine')->where($whereIn)->select();
        if($res==true){
            $in=$this->countAll(array_column($res,'amount'));
        }else $in=0;
        $whereOut=['outcardno'=>$map['cardno'],'outnum'=>$map['num'],'status'=>'1'];
        $resOut=$this->model->table('green_combine')->where($whereOut)->select();
        if($resOut==true){
            $out=$this->countAll(array_column($resOut,'amount'));
        }else $out=0;
        return ['in'=>$in,'out'=>$out];
    }
    //合计金额
    private function countAll($data){
        $sum=0;
        foreach($data as $val){
            $sum=bcadd($sum,$val,2);
        }
        return $sum;
    }

    //退菜获取菜品 分类查询
    private  function searchClass($map,$method=null){
        $sql="SELECT sum(num) as num,goodsname,price,goodsid,type from green_order where type='{$map['type']}' AND panterid='{$map['panterid']}' AND cardno='{$map['cardno']}' AND cardnum='{$map['cardnum']}' ";
        $sql.="AND flag='{$map['flag']}' AND placeddate='{$map['placeddate']}' group by goodsname,price,goodsid,type ";
        $res=$this->model->query($sql);
        return $res;
    }
    private function returnGoods($map,$data){
        $map['flag']='04';
        if($map['type']=='1'){
            $lists=$this->searchClass($map);
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
            $lists=$this->searchClass($map);
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
        $consumeSum=M('green_order')->where($mapNum)->select();
        if($consumeSum==true){
            $getNum=bcadd($getNum,array_sum(array_column($consumeSum,'num')),0);
        }else{
            return $getNum;
        }
        //查询退菜数量
        $mapNum['flag']='04';
        $refundSum=M('green_order')->where($mapNum)->select();
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

    //除去退款订单
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
    //卡金额合并 以及账户扣钱
    public function getCombine($map,$cardlist,$method){
        foreach($cardlist as $key=>$val){
            $cardS=$this->cardAccount($key);
            $yue=bcsub($cardS['amount'],$val,2);
            $accountif=$this->model->table('account')->where(['customid'=>$cardS['customid'],'type'=>'00'])->save(['amount'=>$yue]);
            $date=time();$placeddate=date('Ymd',$date);$placedtime=date('H:i:s',$date);
            $sql="INSERT INTO green_combine (cardno,innum,outcardno,outnum,placeddate,placedtime,status,tradeid,amount) VALUES (";
            $sql.="'{$map['cardno']}','{$map['num']}','{$key}','{$cardS['num']}','{$placeddate}','{$placedtime}','1','{$map['tradeid']}','{$val}')";
            $combineif=$this->model->execute($sql);
            if($accountif==true&&$combineif==true){
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'09','codemsg'=>'数据库写入失败'));
            }
        }
        return true;
    }
    //------------------------------------------common method--------------------------------------------------------------
    //查询卡账户
    private function cardAccount($cardno){
        return $this->model->table('cards')->alias('ca')
                           ->join('left join customs cu on cu.customid=ca.customid')
                           ->join('left join account ac on cu.customid=ac.customid')
                           ->where(['ca.cardno'=>$cardno,'ac.type'=>'00'])
                           ->field('ca.cardno,ca.customid,ac.amount,cu.num')->find();
    }
    private function giftCardFrozen($cardfee){
        if($cardfee=='3'){
            return 0;
        }else{
            return $this->frozen;
        }
    }
    private function checkSign($key,$checkKey){
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        return true;
    }
    private function valiCard($cardno, $method){
        $map['ca.cardno'] = $cardno;
        $map['ac.type']   = '00';
        $field            = 'ca.cardno,ca.panterid,ca.cardfee,ca.status,cu.customid,cu.customlevel,cu.cardflag,cu.num,ac.amount';
        $info = $this->model->table('cards')->alias('ca')
                            ->join('left join customs cu on cu.customid=ca.customid')
                            ->join('left join account ac on ac.customid=cu.customid')
                            ->where($map)->field($field)->select();
        if($info==false){
            $error=['status'=>'02','codemsg'=>'此卡号不存'];
            $errorLog=['cardno'=>$cardno,'error'=>'此卡号不存'];
            $this->handdle($method, $error, $errorLog);
        }
        $cardInfo = $info[0];
        if($cardInfo['status']!='Y'){
            $error=['status'=>'03','codemsg'=>'此卡不是正常卡:'.$cardno];
            $errorLog=['cardno'=>$cardno,'cardStatus'=>$cardInfo['status']];
            $this->handdle($method,$error,$errorLog);
        }
        if($cardInfo['panterid']!=$this->cardPanterid){
            $error=['status'=>'04','codemsg'=>'此卡不是大食堂的卡'];
            $errorLog=['cardno'=>$cardno,'panterid'=>$cardInfo['panterid'],'greenPanterid'=>$this->cardPanterid];
            $this->handdle($method,$error,$errorLog);
        }
        if($cardInfo['amount']===null||$cardInfo['customid']===null){
            $error=['status'=>'05','codemsg'=>'未查询到该卡账户,请确认该卡是否初始化'];
            $errorLog=['cardno'=>$cardno,'error'=>'此卡账户异常'];
            $this->handdle($method,$error,$errorLog);
        }
        return $cardInfo;
    }
    /*校验商户是否是大食堂商户
    * var panterid
    * var $method
    */
    private function valiPanterid($panterid,$method){
        $map['panterid']=$panterid;
        $list=M('panters')->where($map)->find();
        if($list==false){
            $error=['status'=>'11','codemsg'=>'未查询到该商户信息,请核实'];
            $errorData=['panterid'=>$panterid];
            $this->handdle($method,$error,$errorData);
        }
        if($list['revorkflg']!='N'){
            $error=['status'=>'12','codemsg'=>'该商户禁用中'];
            $errorData=['panterid'=>$panterid,'revorkflg'=>$list['revorkflg']];
            $this->handdle($method,$error,$errorData);
        }
        if($list['parent']!=$this->parent){
            $error=['status'=>'13','codemsg'=>'不是大食堂商户'];
            $errorData=['panterid'=>$panterid,'parent'=>$list['parent']];
            $this->handdle($method,$error,$errorData);
        }
        return $list;
    }
    /*获数据记录日志
    *$this->getDir()文件是否建立 若无建立
    * $dir 文件夹名
    * $data 数据信息
    */
    private function writeLog($dir,$data){
        $str=date('Y-m-d H:i:s',time()).'******************';
        $str.=$data;
        $str.="\n";
        $filename=GREEN.'/'.$dir.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        file_put_contents($filename,$str,FILE_APPEND);
    }
    private function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
    //错误 处理 写入日志返回错误信息
    private function handdle($method,$error,$errorData){
        $this->errorLog($method,$error,$errorData);
        returnMsg($error);
    }
    /*
    * param $method 方法名
    * param $error 返回的错误信息和提示
    * param $errorData 错误数据对比
    */
    private function errorLog($method,$error,$errorData){
        $filename=GREEN.'error'.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        $str=date('Y m d H:i:s',time()).'  '.$method.'------------';
        $str.=json_encode($errorData);
        $str.="\n";
        file_put_contents($filename,$str,FILE_APPEND);
    }



    //大食堂 禁止充值大于5000


	protected function chargeLimit($amount){
//    	if($amount>5000){
//    		returnMsg(['status'=>'021','codemsg'=>'充值金额禁止大于5000']);
//	    }else{
//    		return true;
//	    }
        return true;
	}

}