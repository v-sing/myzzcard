<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class CardWithholdController extends CommonController {
    public function _initialize(){
        parent::_initialize();
    }
    //扣款/扣劵首页
    function index(){
        $hotels = array(
            array('panterid'=>'00000125','name'=>'雅乐轩酒店'),
            array('panterid'=>'00000126','name'=>'艾美酒店'),
            array('panterid'=>'00000127','name'=>'福朋酒店'),
            array('panterid'=>'00000118','name'=>'南阳假日酒店'),
            array('panterid'=>'00000270','name'=>'铂尔曼酒店')
        );
        $this->assign('hotels',$hotels);
        $this->display();
    }

    //扣款/扣劵执行
    function withholdDo(){
        $panterid=$_REQUEST['panterid'];
        $cate=$_REQUEST['cate'];
        $cardno=$_REQUEST['cardno'];
        $quanid=$_REQUEST['quanid'];
        $consumedate=str_replace('-','',$_REQUEST['consumedate']);
        $cards=M('cards');
        $account=M('account');
        $card=$cards->where(array('cardno'=>$cardno))->find();
        if($card==false){
            $this->error('无此卡号');
        }
        if($card['status']!='Y'){
            $this->error('非正常卡不能消费');
        }
        if($cate==1){
            $amount=$_REQUEST['amount1'];
            $map=array('customid'=>$card['customid'],'type'=>'00');
            $balance=$account->where($map)->find();
            if($balance['amount']<$amount){
                $this->error('余额不足');
            }
        }elseif($cate==2){
            $amount=$_REQUEST['amount'];
            if(empty($quanid)){
                $this->error('扣款信息缺失');
            }
            $map=array('customid'=>$card['customid'],'type'=>'02','quanid'=>$quanid);
            $balance=$account->where($map)->find();
            if($balance['amount']<$amount){
                $this->error('余额不足');
            }
        }
        $userid=$this->getUserid();
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $model=new model();
        $withholdid=$this->getFieldNextNumber('withholdid');
        if($cate==1){
            $sql="insert into card_withhold values('{$withholdid}','{$cardno}','','{$amount}','{$cate}','{$panterid}','{$consumedate}','23:59:59','{$userid}','','','','','','{$currentDate}','{$currentTime}','')";
        }else{
            $sql="insert into card_withhold values('{$withholdid}','{$cardno}','{$quanid}','{$amount}','{$cate}','{$panterid}','{$consumedate}','23:59:59','{$userid}','','','','','','{$currentDate}','{$currentTime}','')";
        }
        //echo $sql;exit;
        if($model->execute($sql)){
            $this->success('已执行扣款，待相关工作人员审核');
        }else{
            $this->error('执行扣款失败');
        }
    }

    //ajax获取劵信息
    function getQuanByCardno(){
        $cardno=$_REQUEST['cardno'];
        $map=array('cardno'=>$cardno);
        $cards=M('cards');
        $card=$cards->where($map)->field('cardno,status')->select();
        if($card==false){
            exit(json_encode(array('status'=>'01','msg'=>'无此卡号')));
        }
        if($card['status']=='N'){
            exit(json_encode(array('status'=>'02','msg'=>'非正常卡号，无法扣款')));
        }
        $map1=array('c.cardno'=>$cardno,'a.type'=>'02');
        $list=$cards->alias('c')->join('account a on a.customid=c.customid')
            ->join('quankind q on q.quanid=a.quanid')->where($map1)
            ->field('a.amount,q.quanid,q.quanname')->select();
        if($list==false){
            exit(json_encode(array('status'=>'03','msg'=>'该卡下无劵信息')));
        }else{
            $html='<option value="-1">请选择扣劵类型</option>';
            foreach($list as $key=>$val){
                $html.='<option value="'.$val['quanid'].'">'.$val['quanname'].'</option>';
            }
            exit(json_encode(array('status'=>'1','html'=>$html)));
        }
    }

    function audit(){
        $cardno = trim(I('get.cardno',''));
        $placeddate	= trim(I('get.placeddate',''));
        $cate	= trim(I('get.cate',''));
        $checkstatus=trim(I('get.checkstatus',''));
        if($placeddate!=''){
            $placeddate=str_replace('-','',$placeddate);
            $where['cw.placeddate']=$placeddate;
            $this->assign('placeddate',$placeddate);
            $map['placeddate']=$placeddate;
        }
        if($cardno!=''){
            $where['cw.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cate!=''){
            $where['cw.cate']=$cate;
            $this->assign('cate',$cate);
            $map['cate']=$cate;
        }
        if($checkstatus!=''){
            $where['cw.checkstatus']=$checkstatus;
            $this->assign('checkstatus',$checkstatus);
            $map['checkstatus']=$checkstatus;
        }else{
            $where['cw.checkstatus']=array('EXP','IS NULL');
        }
        if($this->panterid!='FFFFFFFF'){
            $where['cw.panterid']=$this->panterid;
        }
        $field='cw.cate,cw.cardno,cw.withholdid,cw.amount,p.namechinese pname,q.quanname,';
        $field.='u.username,cw.placeddate,cw.placedtime,cw.consumedate,cw.consumetime,cw.checkstatus';
        $card_withhold=M('card_withhold');
        $list=$card_withhold->alias('cw')->join('panters p on p.panterid=cw.panterid')
            ->join('left join quankind q on cw.quanid=q.quanid')
            ->join('users u on cw.applyid=u.userid')
            ->where($where)->field($field)->select();
        $this->assign('list',$list);
        $this->display();
    }

    //扣款审核
    function auditDo(){
        $card_withhold=M('card_withhold');
        if(IS_POST){
            $checkstatus=$_POST['checkstatus'];
            $meno=trim($_POST['meno']);
            $withholdid=$_POST['withholdid'];
            $userid=$this->getUserid();
            if(empty($checkstatus)){
                $this->error('非法传入');
            }
            $model=new Model();
            $model->startTrans();
            $map=array('withholdid'=>$withholdid);
            $withholdList=$card_withhold->where($map)->find();
            $consumeAmount=$withholdList['amount'];
            $cardno=$withholdList['cardno'];
            $panterid=$withholdList['panterid'];
            $card=$model->table('cards')->where(array('cardno'=>$cardno))->field('cardno,status,customid')->find();
            if($card['status']!='Y'){
                $this->error('扣款卡号非正常卡，请驳回申请');
            }
            if($withholdList['cate']==1){
                $placeddate=$withholdList['consumeplaceddate'];
                $placedtime=$withholdList['placedtime'];
                $map1=array('customid'=>$card['customid'],'type'=>'00');
                $account=$model->table('account')->where($map1)->find();
                if($account['amount']<$consumeAmount){
                    $this->error('至尊卡余额不足，请驳回申请');
                }

                $tradeid=substr($cardno,15,4).date('YmdHis',time());
                $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
                $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
                $tradeSql.="values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
                $tradeSql.="'{$consumeAmount}','0','{$card['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0')";
                $tradeIf=$model->execute($tradeSql);

                $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$card['customid']}' and type='00'";
                $accountIf=$model->execute($accountSql);
            }elseif($withholdList['cate']==2){
                $placeddate=$withholdList['consumeplaceddate'];
                $placedtime=$withholdList['placedtime'];
                $quanid=$withholdList['quanid'];
                $map1=array('customid'=>$card['customid'],'type'=>'02','quanid'=>$quanid);
                $account=$model->table('account')->where($map1)->find();
                if($account['amount']<$consumeAmount){
                    $this->error('消费劵数量不足，请驳回申请');
                }
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
                $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
                $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
                $tradeSql.="values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
                $tradeSql.="'{$consumeAmount}','0','{$card['customid']}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
                $tradeIf=$model->execute($tradeSql);

                $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$card['customid']}' and type='02' and quanid='{$quanid}'";
                $accountIf=$model->execute($accountSql);
            }else{
                $this->error('非法扣款类型，请驳回申请');
            }
            $data=array('checkstatus'=>$checkstatus,'meno'=>$meno,'checkid'=>$userid,
                'checkdate'=>date('Ymd'),'checktime'=>date('H:i:s'),'tradeid'=>$tradeid);
            $withholdIf=$card_withhold->where($map)->save($data);


            if($tradeIf==true&&$accountIf==true&&$withholdIf==true){
                $model->commit();
                $this->success('审核成功，已执行扣款');
            }else{
                $model->rollback();
                $this->error('审核失败');
            }
        }else{
            $withholdid=$_REQUEST['withholdid'];
            $where=array('cw.withholdid'=>$withholdid);
            $field='cw.cate,cw.cardno,cw.withholdid,cw.amount,p.namechinese pname,q.quanname,';
            $field.='u.username,cw.placeddate,cw.placedtime,cw.consumedate,cw.consumetime';
            $list=$card_withhold->alias('cw')->join('panters p on p.panterid=cw.panterid')
                ->join('left join quankind q on cw.quanid=q.quanid')
                ->join('users u on cw.applyid=u.userid')
                ->where($where)->field($field)->find();
            //print_r($list);
            $this->assign('list',$list);
            $this->display();
        }
    }

    public function withholdList(){
        $cardno = trim(I('get.cardno',''));
        $checkdate	= trim(I('get.checkdate',''));
        $consumedate	= trim(I('get.consumedate',''));
        $cate	= trim(I('get.cate',''));
        $checkstatus=trim(I('get.checkstatus',''));
        if($checkdate!=''){
            $checkdate=str_replace('-','',$checkdate);
            $where['cw.checkdate']=$checkdate;
            $this->assign('checkdate',$checkdate);
            $map['checkdate']=$checkdate;
        }
        if($consumedate!=''){
            $consumedate=str_replace('-','',$consumedate);
            $where['cw.consumedate']=$consumedate;
            $this->assign('consumedate',$consumedate);
            $map['consumedate']=$consumedate;
        }
        if($cardno!=''){
            $where['cw.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cate!=''){
            $where['cw.cate']=$cate;
            $this->assign('cate',$cate);
            $map['cate']=$cate;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['cw.panterid']=$this->panterid;
        }
        $where['cw.checkstatus']=1;
        $field='cw.cate,cw.cardno,cw.withholdid,cw.amount,p.namechinese pname,q.quanname,u1.username checkname,cw.tradeid,';
        $field.='u.username,cw.placeddate,cw.placedtime,cw.consumedate,cw.consumetime,cw.checkstatus,cw.checkdate';
        $field.=',cw.checktime';
        $card_withhold=M('card_withhold');
        $list=$card_withhold->alias('cw')->join('panters p on p.panterid=cw.panterid')
            ->join('left join quankind q on cw.quanid=q.quanid')
            ->join('users u on cw.applyid=u.userid')
            ->join('users u1 on cw.checkid=u1.userid')
            ->where($where)->field($field)->select();
        $this->assign('list',$list);
        $this->display();
    }


}
