<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/1 0001
 * Time: 上午 10:03
 */
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class OpenCardsController extends CoinController{
// 获取卡号
    public function getCards(){
	    header('Content-type: text/json');
		$data=json_decode(file_get_contents('php://input'),true);
        //  $datami='{"cardno":"6889396888800153272","key":"9d21d339d4c240d968702fe78dce062b"}';
        $this->recordData($data);
        $datami = json_decode($data,1);
        $cardno=trim($data['cardno']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','data'=>$key,'data1'=>$cardno,'data2'=>$data,'codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            $this->recoreIp();
//            echo '1212';exit;
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if(preg_match("/^(6888)(\d{15})$/",$cardno)){
            $customid = $this->getCustomid($cardno);
            if($customid == false || empty($customid)){
                $this->recordData($cardno);
                returnMsg(array('status'=>'03','codemsg'=>'此卡没有关联用户'));
                //$this->openCards();
            }else{
                 $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
                    ->join('cards c on cc.cid=c.customid')
                    ->where($map)->sum('a.amount');
                returnMsg(array('status'=>'12','amount'=>$account,'codemsg'=>'该卡已开卡'));
                //$this->debitCards();
            }
        }else{
            returnMsg(array('status'=>'04','codemsg'=>'卡号不支持该项目'));
        }
    }
	
//    获取卡状态绑定开卡充值 同卡进出功能屏蔽加了一个d
    public function openCardss(){
            $data=json_decode(file_get_contents('php://input'),true);
        returnMsg(array('status'=>'001','codemsg'=>'此功能暂停使用'));
            $cardno=trim($data['cardno']);//获取的卡号
        //获取用户的信息之后，会员与卡绑定提交
            $this->recordData($data);
            $namechinese=trim($data['namechinese']);//用户的姓名
            $residaddress=trim($data['residaddress']);//用户的地址
            $linktel=trim($data['linktel']);//用户的联系方式
            $personid=trim($data['personid']);// 证件号
            $panterid=trim($data['panterid']);//商务号
            $totalmoney=trim($data['totalmoney']);//金额
            $paymenttype = trim($data['paymenttype']);//现金银行支付的类型
            $key=trim($data['key']);

//        $key='4a615d9f8679688df31437913f7ed02b';
        $checkKey=md5($this->keycode.$cardno.$namechinese.$residaddress.$linktel.$personid.$panterid.$totalmoney.$paymenttype);
//        var_dump($checkKey);exit;
        if($key!=$checkKey){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));

        }
        if(empty($cardno)){
            $where['cardno']=$cardno;
            returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
        }
        if(empty($namechinese)){
            $where['namechinese']=$namechinese;
            returnMsg(array('status'=>'03','codemsg'=>'用户名不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'04','codemsg'=>'手机号不能为空'));
        }
        if(!preg_match("/^1[34578]\d{9}$/",$linktel)){
            returnMsg(array('status'=>'06','codemsg'=>'手机号不正确'));
        }

        if(empty($personid)){
            returnMsg(array('status'=>'05','codemsg'=>'证件号不能为空'));
        }
        if(empty($residaddress)) {
            $where['residaddress']=$residaddress;
            returnMsg(array('status'=>'07','codemsg'=>'证件地址不能为空'));
        }
        $where['cardno']=$cardno;
        $status=$this->cards->where($where)->field('status')->find();
        if($status['status']=="N"){
            $map=array('linktel'=>$linktel);
            $mob=$this->customs->where($map)->find();
            if($mob==true){
                returnMsg(array('status'=>'17','codemsg'=>'手机号已绑定，请更换'));
            }
            $map1=array('personid'=>$personid);
            $person=$this->customs->where($map1)->find();
            if($person){
                returnMsg(array('status'=>'20','codemsg'=>'证件号已绑定，请更换'));
            }
            //        绑定会员信息
            $currentDate = date('Ymd',time());
            $this->model->startTrans();
            $customid = $this->getFieldNextNumber('customid');
            $bingSql = "insert into customs(customid,namechinese,personid,linktel,residaddress,placeddate) values('".$customid."','".$namechinese."','".$personid."','".$linktel."','".$residaddress."','".$currentDate."')";
            $custIf=$this->model->execute($bingSql);

            if($custIf==true){
                $this->model->commit();
                $userid = $this->userid;
                $cardArr=array('cardno'=>$cardno);
                $n=$this->openCard($cardArr,$customid,$totalmoney,$panterid=null,$userid,$isBill=null,$paymenttype);
                if($n==true){
                    $this->model->commit();
                    returnMsg(array('status'=>'10','codemsg'=>'绑定开卡充值成功'));

                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'11','codemsg'=>'绑定开卡充值失败'));

                }
                returnMsg(array('status'=>'08','codemsg'=>'绑定会员成功'));

            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'09','codemsg'=>'绑定会员失败'));

            }

        }
        if($status['status']=="Y"){
            $totalmoney=trim($data['totalmoney']);
            $panterid=trim($data['panterid']);
            $customid = $this->getCustomid($cardno);
            $account = $this->accountQuery($customid,00,$cardno);
            if($totalmoney>$account){
                returnMsg(array('status'=>'13','codemsg'=>'扣除金额大于余额'));
            }
            $this->model->startTrans();
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
            $tradeSql.="values('00000000','00000000','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$totalmoney}','0','{$customid}','{$cardno}','{$placedtime}','00','abcdefgh','0')";
            $tradeIf=$this->model->execute($tradeSql);
            if($tradeIf==false){
                returnMsg(array('status'=>'14','codemsg'=>'执行扣款记录失败'));
                $this->model->rollback();
            }
            $where['type']='00';
            $where['cardno']=$cardno;
            $where['status']='Y';
            $where['customid']=$customid;
            $debitamount=array('amount'=>$account-$totalmoney);
            $debitIf=$this->account->where($where)->save($debitamount);
            if($debitIf==false){
                $this->model->rollback();
                returnMsg(array('status'=>'15','codemsg'=>'执行扣款失败'));
            }else{
                $this->model->commit();
                $cardSql= "update cards set status='T' where cardno='".$cardno ."'";
                $cardif = $this->model->execute($cardSql);
                if($cardif){
                    $this->model->commit();
                    returnMsg(array('status'=>'18','codemsg'=>'执行扣款更新卡状态成功'));
                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'19','codemsg'=>'执行扣款更新卡状态失败'));
                }
                returnMsg(array('status'=>'16','codemsg'=>'执行扣款成功'));

            }
        }
    }
    protected function recordData($data,$flag){

        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Pos/interface/ticket/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Pos/interface/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recoreIp0(){
        $a=$_SERVER['REMOTE_ADDR'];
        $time=date('Y-m-d H:i:s');
        $string=$a.'--'.$time."\r\n\r\n";
        $path='test0.txt';
        file_put_contents($path,$string,FILE_APPEND);
    }

}