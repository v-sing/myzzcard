<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class AfterSalesController extends CommonController {
    //卡售后管理
    public function _initialize(){
        parent::_initialize();
        $this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    }
    //批量扣款
    function batchConsume(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if(IS_POST){
            //print_r($_FILES);exit;
            if(empty( $_FILES['file_stu']['name'])){
                $this->error('请上传卡号文件',U('AfterSales/batchConsume'));
            }
            $upload=$this->_upload('excel');
            if(is_string($upload)){
                $this->error($upload,U('AfterSales/batchConsume'));
            }else{
                $filename=PUBLIC_PATH.$upload['file_stu']['savepath'].$upload['file_stu']['savename'];
                $exceldate=$this->import_excel($filename);
                $panters=D('panters');
                $cards=D('cards');
                $account=D('account');
                $model=new Model();
                $trade_wastebooks=('trade_wastebooks');
                $batchConsumeLogArr=array();
                $str='';
                $c=0;
                foreach($exceldate as $key=>$val){
                    //var_dump($val[0]);continue;
                    $batchConsumeLogArr[$key]['datetime']=date('Y-m-d H:i:s',time());
                    $batchConsumeLogArr[$key]['cardno']=$cardno=trim($val[0]);
                    $batchConsumeLogArr[$key]['amount']=$amount=trim($val[1]);
                    $panterid=$val[2];
                    $where1=array('cardno'=>$cardno,'status'=>'Y');
                    /*if($this->panterid!='FFFFFFFF'){
                        $where1['panterid']=$this->panterid;
                    }*/
                    //卡号不存在跳过；
                    $card_info=$cards->where($where1)->find();
                    if($card_info==false){
                        $batchConsumeLogArr[$key]['status']=0;
                        $batchConsumeLogArr[$key]['msg']='卡号不存在';
                        continue;
                    }
                    //消费的商户不存在跳过
                    $where2=array('panterid'=>$panterid);
                    $panter_info=$panters->where($where2)->find();

                    if($panter_info==false){
                        $batchConsumeLogArr[$key]['status']=0;
                        $batchConsumeLogArr[$key]['msg']='扣款商户不存在';
                        continue;
                    }
                    //账户余额不足时跳过
                    $where3=array('type'=>'00','customid'=>$card_info['customid']);
                    $account_info=$account->where($where3)->find();
                    if($account_info['amount']<$amount){
                        $batchConsumeLogArr[$key]['status']=0;
                        $batchConsumeLogArr[$key]['msg']='卡号余额不足';
                        continue;
                    }
                    $tradeid=substr($cardno,15,4).date('YmdHis',time());
                    $placeddate=date('Ymd',time());
                    $placedtime=date('H:i:s',time());
                    $sql='insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,';
                    $sql.='tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)';
                    $sql.=" values('00000000','00000000','{$panterid}','{$tradeid}','{$placeddate}',";
                    $sql.="'{$amount}','0','{$card_info['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0')";
                    $model->execute($sql);
                    $remind_account=$account_info['amount']-$amount;
                    $data3=array('amount'=>$remind_account);
                    if($account->where($where3)->save($data3)){
                        $batchConsumeLogArr[$key]['status']=1;
                        $batchConsumeLogArr[$key]['msg']='扣款成功';
                        $c++;
                    }else{
                        $batchConsumeLogArr[$key]['status']=0;
                        $batchConsumeLogArr[$key]['msg']='扣款失败';
                    }
                }
                //print_r($batchConsumeLogArr);
                if(!empty($batchConsumeLogArr)){
                    $this->batchConsumeLogs($batchConsumeLogArr);
                }
                if($c>0){
                    $this->success('批量扣款完成，扣款执行'.$c.'条',U('AfterSales/batchConsume'),5);
                }else{
                    $this->error('扣款失败，扣款执行0条',U('AfterSales/batchConsume'),5);
                }
            }
        }else{
            $this->display();
        }
    }
    //写入批量扣款日志
    function batchConsumeLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='充值金额：'.$val['amount']."  ";
            if($val['status']==0){
                $msgString.='状态：扣款失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：扣款成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('batchConsume',$msgString);
    }
    //余额查询
    function balance_query(){
        $this->display();
    }
    //退卡管理
    function returnCard(){
        if(IS_POST){
            $model=new Model();
            $cardno=trim($_REQUEST['cardno']);
            $cardbalance=trim($_REQUEST['cardbalance']);
            $cardbalance1=trim($_REQUEST['cardbalance1']);
            $description=trim($_REQUEST['description']);
            $amount=trim($_REQUEST['amount']);
            $currentdate=date('Ymd',time());
            $currenttime=date('H:i:s',time());
            if(empty($cardno)){
                $this->error('请输入至尊卡号');
            }
            $card_info=M('cards')->where('cardno='.$cardno)->find();
            if($card_info==false){
                $this->error('无此卡信息');
            }

            //开启事务--2016--05--19 by wan
            $model->startTrans();
            $return_card_info=M('returncards')->where('cardno='.$cardno)->find();//退卡次数表
            if($return_card_info==false){
                $sql="insert into returncards values('{$cardno}','{$currentdate}',1)";
                $boolif1=$model->execute($sql);
            }else{
                $usecount=$return_card_info['usecount']+1;
                $data=array('returndatetime'=>$currentdate,'usecount'=>$usecount);
                M('returncards')->where('cardno='.$cardno)->save($data);
            }
            $panterid=$card_info['panterid'];
            $operid=$this->userid;
            $sql1="insert into Return_cards_log VALUES ('{$cardno}','{$currentdate}','{$currenttime}','{$card_info['panterid']}',2,";//退卡记录表
            $sql1.="'{$cardbalance}','{$amount}','{$description}','{$operid}','{$cardbalance1}')";
            $boolif2=$model->execute($sql1);

            $data1=array('status'=>'R');
            $data2=array('amount'=>0);
            $boolif3=M('cards')->where('cardno='.$cardno)->save($data1);
            $boolif4=M('account')->where('customid='.$card_info['customid'])->save($data2);
            if($boolif1==true && $boolif2==true && $boolif3==true &&$boolif4==true){
                $model->commit();
                $this->success('退卡成功，卡将被回收！');
            }else{
                $model->rollback();
                $this->error('退卡失败,请重试！');
            }
            //-----end--------
        }else{
            $hysx=$this->getHysx();
            if($hysx=='酒店'){
                $this->assign('is_hotel',1);
            }else{
                $this->assign('is_hotel',0);
            }
            $this->display();
        }
    }
    //查询卡片信息
    function card_query(){
        $cardno=$_POST['cardno'];
        $style=$_POST['style'];
        $model=new Model();
        if($style==1||$style==3){
            $map=array('cardno'=>$cardno);
        }elseif($style==2){
            $map=array('cardno'=>$cardno,'status'=>'Y');
        }
        $card_check=M('Cards')->where($map)->find();
        if($card_check==false){
            if($style==1){
                $res['msg']='非正常卡';
                $res['success']=0;
                echo json_encode($res);exit;

            }elseif($style==2){
                $res['msg']='非正常卡，无法退卡！';
                $res['success']=0;
                echo json_encode($res);exit;
            }elseif($style==3){
                $res['msg']='非法卡号';
                $res['success']=0;
                echo json_encode($res);exit;
            }
        }else{
            if($this->panterid!='FFFFFFFF'){
                $map1['panterid']=$card_check['panterid'];
                $panter_info=M('panters')->field('panterid,parent')->where($map1)->find();
                if($this->panterid!=$panter_info['panterid']&&$this->panterid!=$panter_info['parent']){
                    $res['msg']='非本部门卡，无法查询';
                    $res['success']=0;
                    echo json_encode($res);exit;
                }
            }
            $where['c.cardno']=$cardno;
            $where['a.type']='00';
            // $where['a1.type']='01';
            $field='c.cardno,c.status,c.exdate,cu.namechinese cuname,cu.customid,cc.cid,a.amount card_money';
            $card_balance=$model->table('cards')->alias('c')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.Customid')
                ->join('left join __CUSTOMS__ cu on cu.Customid=cc.Customid')
                ->join('left join __ACCOUNT__ a on a.customid=c.customid')
                // ->join('left join __ACCOUNT__ a1 on a1.customid=c.customid')
                ->field($field)->where($where)->find();
            $card_balance['card_money']=!empty($card_balance['card_money'])?$card_balance['card_money']:0;
            // $card_balance['card_points']=!empty($card_balance['card_points'])?$card_balance['card_points']:0;
            if($style==1){
                $card_balance['exdate']=date('Y-m-d H:i:s',strtotime($card_balance['exdate']));
                switch($card_balance['status']){
                    case 'A':$card_balance['status']='待激活';break;
                    case 'D':$card_balance['status']='已销卡';break;
                    case 'R':$card_balance['status']='已退卡';break;
                    case 'S':$card_balance['status']='已过期';break;
                    case 'N':$card_balance['status']='新卡';break;
                    case 'L':$card_balance['status']='锁定';break;
                    default:$card_balance['status']='正常卡';break;
                }
            }
            $res['data']=$card_balance;
            $res['success']=1;
            echo json_encode($res);
        }
    }
    //补卡管理
    function reissueCard(){
        if(IS_POST){
            $model=new Model();
            $cardno=$_POST['cardno'];
            $newcard=$_POST['newcard'];
            $cardbalance=$_POST['cardbalance'];
            $cardpoint=$_POST['cardbalance1'];
            $currentdate=date('Ymd',time());
            $meno=trim($_POST['meno']);
            if(empty($cardno)){
                $this->error('请选择挂失卡号');
            }
            if(empty($newcard)){
                $this->error('请填写新卡号');
            }
            $map1['cardno']=$cardno;
            $card_lock=M('card_locks')->where($map1)->find();
            if($card_lock==false){
                $this->error('此卡未挂失');
            }
            $map1['cardno']=$cardno;
            $map1['flag']=0;
            $map1['tradetype']=17;
            $trade_type=M('trade_wastebooks')->where($map1)->select();
            if($trade_type){
                $this->error('预授权未完成！请完成预授权再进行');
            }
            $model->startTrans();
            $map2['lockid']=$card_lock['lockid'];
            $card_locks_log=M('card_locks_log')->where($map2)->find();
            if($card_locks_log['active']==2){
                $this->error('此卡已补卡！无需重复补卡');
            }
            $data3=array('active'=>2,'description'=>'此挂卡已经补卡');
            $map3['lockid']=$card_lock['lockid'];
            M('card_locks_log')->where($map3)->save($data3);
            $sql="insert into card_change_logs values('{$newcard}','{$cardno}','{$cardbalance}','{$cardpoint}',";
            $sql.="'{$card_lock["lockid"]}','{$this->userid}','{$currentdate}',1,'{$meno}')";
            $model->execute($sql);
            $map4['c.cardno']=$cardno;
            $old_card=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->where($map4)->field('c.customid,cu.customid cuid,c.panterid')->find();
            //$new_customid=$this->getnextcode('customs',8);
            //$accountid=$this->getnextcode('account',8);
//            var_dump($old_card);exit;
            $coinList=$model->table('coin_account')->where(array('cardid'=>$old_card['customid']))->select();
            $new_customid=$this->getFieldNextNumber('customid');
            $accountid=$this->getFieldNextNumber('accountid');
            $data5=array('customid'=>$new_customid);
            $map5['customid']=$old_card['customid'];
            M('account')->where($map5)->save($data5);
            $sql1="insert into customs_c values('{$old_card['cuid']}','{$new_customid}')";
            $sql2="insert into account (accountid,customid,amount,type,quanid) values('{$accountid}','{$old_card['customid']}','0','00',NULL)";
            $sql3="insert into account (accountid,customid,amount,type,quanid) values('{$accountid}','{$old_card['customid']}','0','01',NULL)";
            $sql4="insert into account (accountid,customid,amount,type,quanid) values('{$accountid}','{$old_card['customid']}','0','02',NULL)";

            $userid=$this->userid;
            $sql5="INSERT INTO card_active_logs VALUES('{$newcard}','{$userid}',".date('Ymd');
            $sql5.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$new_customid}'";
            $sql5.=",'{$old_card['panterid']}','00000000')";

            $model->execute($sql1);
            $model->execute($sql2);
            $model->execute($sql3);
            $model->execute($sql4);
            $model->execute($sql5);

            $text="补卡卡号：{$cardno},新卡卡号：{$newcard},补卡时间".date('Y-m-d H:i:s')."\r\n";
            $text.="操作sql日志：{$sql1}"."\r\n"."{$sql2}"."\r\n"."{$sql3}"."\r\n"."{$sql4}"."\r\n"."{$sql5}"."\r\n";
            if($coinList!=false){
                $sql6="update coin_account set cardid='{$new_customid}' where cardid='{$old_card['customid']}'";
                $sql7="update coin_consume set cardid='{$new_customid}' where cardid='{$old_card['customid']}'";
                $model->execute($sql6);
                $model->execute($sql7);
                $text.="{$sql6}"."\r\n"."{$sql7}";
            }
            $text.="\r\n\r\n\r\n";
            //$model->rollback();
            //exit;
            $data6=array('status'=>'Y','customid'=>$new_customid,'exdate'=>date('Ymd',strtotime("+3 years")));
            $map6['cardno']=$newcard;

            if(M('cards')->where($map6)->save($data6)){
                $model->commit();
                $this->recordData($text,'reissueCard');
                $this->success('补卡成功，卡已激活');
            }else{
                $model->rollback();
                $this->success('补卡失败');
            }
        }else{
            $model=new model();
            $cardno=trim($_GET['cardno']);
            $cuname=trim($_GET['cuname']);
            $customid=trim($_GET['customid']);
            if(!empty($cardno)){
                $where['c.cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
            if(!empty($customid)){
                $where['cu.customid']=$customid;
                $this->assign('customid',$customid);
            }
            if(!empty($cuname)){
                $where['cu.namechinese']=$cuname;
                $this->assign('cuname',$cuname);
            }
            $where['c.status']='L';
            $where['a.type']='00';
            $where['a1.type']='01';
            $where['cll.active']=0;
            $field='c.cardno,cu.namechinese cuname,cu.customid,c.status,a.amount balanceamount,a1.amount pointamount';
            if($this->panterid!='FFFFFFFF'){
                $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
            }else{
//                $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
//                $where['_complex']=$where1;
            }
            $count=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__ACCOUNT__ a on a.customid=c.customid')
                ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
                ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
                ->where($where)->field($field)->count();
            $p=new \Think\Page($count, 10);
            $lockedCard=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__ACCOUNT__ a on a.customid=c.customid')
                ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
                ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
                ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
            $page = $p->show();
            $this->assign('list',$lockedCard);
            $this->assign('page',$page);
            $this->display();
        }
    }
    //成功补卡记录
    function reissueList(){
        $model=new model();
        $oldcard=trim($_REQUEST['oldcard']);
        $newcard=trim($_REQUEST['newcard']);
        $cuname=trim($_REQUEST['cuname']);
        $customid=trim($_REQUEST['customid']);
        $personid = isset($_REQUEST['personid'])?$_REQUEST['personid']:'';//证件号
        $linktel = isset($_REQUEST['linktel'])?$_REQUEST['linktel']:'';//联系方式
        if(!empty($oldcard)){
            $where['c.cardno']=$oldcard;
            $this->assign('oldcard',$oldcard);
        }
        if(!empty($newcard)){
            $where['ccl.cardno']=$newcard;
            $this->assign('newcard',$newcard);
        }
        if(!empty($customid)){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
        }
        if(!empty($cuname)){
            $where['cu.namechinese']=$cuname;
            $this->assign('cuname',$cuname);
        }
        if(!empty($personid)){
            $where['cu.personid']=array('like','%'.$personid.'%');
            $this->assign('personid',$personid);
            $map['personid']=$personid;
        }
        if(!empty($linktel)){
            $where['cu.linktel']=array('like','%'.$linktel.'%');
            $this->assign('linktel',$linktel);
            $map['linktel']=$linktel;
        }
        $where['c.status']='L';
        $where['a.type']='00';
        $where['a1.type']='01';
        $where['cll.active']=2;
        $field='c.cardno,cu.namechinese cuname,cu.customid,c.status,a.amount balanceamount,a1.amount pointamount,ccl.cardno newcard,cu.personid,cu.linktel';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $count=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
            ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
            ->join('left join __CARD_CHANGE_LOGS__ ccl on ccl.lockid=cl.lockid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 10);
        $reissueCard=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
            ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
            ->join('left join __CARD_CHANGE_LOGS__ ccl on ccl.lockid=cl.lockid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('list',$reissueCard);
        $this->assign('page',$page);
        $this->display();
    }
    //检查补卡信息
    function check_reissue(){
        $cardno=$_POST['cardno'];
        $newcard=$_POST['newcard'];
        if(empty($cardno)||empty($newcard)){
            $this->error('非法操作');
        }
        $map1=array('cardno'=>$cardno);
        $map2=array('cardno'=>$newcard,'status'=>'N');
        $card1=M('cards')->where($map1)->find();
        $card2=M('cards')->where($map2)->find();
        $res['success']=0;

        if($card2==false){
            //$res['msg']=M('cards')->getLastSql();
            $res['msg']='所填新卡号不是新卡或是非法卡';
        }else{
            if($this->panterid!='FFFFFFFF'){
                if($card2['panterid']!=$this->panterid){
                    $res['msg']='新卡非本部门卡，不能在本部门补卡';
                }else{
                    if($card2['panterid']!=$card1['panterid']){
                        $res['msg']='新卡与挂失卡不是同一部门！';
                    }else{
                        $res['success']=1;
                    }
                }
            }else{
                if($card2['panterid']!=$card1['panterid']){
                    $res['msg']='新卡与挂失卡不是同一部门！';
                }else{
                    $res['success']=1;
                }
            }

        }
        echo json_encode($res);
    }
    //销卡管理
    function destroyCard(){
        if($_POST){
            $cardno=$_POST['cardno'];
            $model=new Model();
            $where['c.cardno']=$cardno;
            $where['a.type']='00';
            $where['a1.type']='01';
            $where['_string']=" c.status='Y' OR c.status='A'";
            if($this->panterid!='FFFFFFFF'){
                $where1['p.panterid']=$this->panterid;
                $where1['p.parent']=$this->panterid;
                $where1['_logic']='OR';
                $where['_complex']=$where1;
            }
            $field='c.customid cardid,c.cardno,cu.namechinese cuname,a.amount cardbalance,a1.amount cardpoint,cu.customid cuid';
            $card_info=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on c.customid=cc.cid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__ACCOUNT__ a on a.customid=c.customid')
                ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->where($where)->field($field)->find();
            if($card_info==false){
                $res['success']=0;
                $res['msg']='此卡不是正常卡或者非本部门卡，不能销卡！';
            }else{
                $res['success']=1;
                $res['info']=$card_info;
            }
            echo json_encode($res);
        }else{
            $this->display();
        }
    }
    //销卡执行
    function cardDes(){
        $cardno=trim($_POST['cardno']);
        $cardbalance=trim($_POST['cardbalance']);
        $description=trim($_POST['description']);
        $customid=trim($_POST['customid']);
        $cardid=trim($_POST['cardid']);
        if(empty($cardno)){
            $this->error('卡号缺失');
        }
        $model=new Model();
        $now=date('Ymd',time());
        $userid=$this->getUserid();
        $sql="insert into Card_Destroy values('{$cardno}','{$customid}','{$now}','{$cardbalance}','{$userid}','{$description}')";
        $model->execute($sql);

        $data=array('cardbalance'=>0,'status'=>'D');
        $data1=array('amount'=>0);
        $model->table('cards')->where('cardno='.$cardno)->save($data);

        $model->table('account')->where('customid='.$cardid)->save($data1);
        $this->success('销卡成功！');
    }
    //销卡记录
    function cardDesList(){
        $model=new Model();
        $start = I('get.startdate','');//开始日期
        $end = I('get.enddate','');//结束日期
        $description= I('get.description','');//备注
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cd.destroydatetime']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cd.destroydatetime'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cd.destroydatetime']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if(!empty($description)){
            $where['cd.description']=array('like','%'.$description.'%');
            $this->assign('description',$description);
            $map['description']=$description;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $field='cd.*,cu.namechinese cuname,u.username';
        $count=$model->table('card_destroy')->alias('cd')
            ->join('__CARDS__ c on c.cardno=cd.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__  cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=cd.userid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $cardDesList=$model->table('card_destroy')->alias('cd')
            ->join('__CARDS__ c on c.cardno=cd.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__  cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=cd.userid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$cardDesList);
        $this->display();
    }
    //卡信息查询
    function cardinfo_query(){
        $model=new Model();
        $ctypes=array('A'=>'待激活','D'=>'销卡', 'R'=>'退卡',
            'S'=>'过期','N'=>'新卡','L'=>'锁定','Y'=>'正常卡',
            'W'=>'无卡','C'=>'出库', 'J'=>'入库','T'=>'冻结','G'=>'异常锁定'
        );
        $this->assign('ctypes',$ctypes);
        $cardno=trim(I('get.cardno',''));
        $customid =trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $status= I('get.status','');
        $pname=trim(I('get.pname',''));
        $cardno = $cardno=="卡号"?"":$cardno;
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $pname = $pname=="商户名称"?"":$pname;
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['cu.customid'] = $customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($status!=''){
            $where['c.status']=$status;
            $this->assign('status',$status);
            $map['status']=$status;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($this->panterid!='FFFFFFFF'){
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        if(!empty($where)){
            $where['a.type']='00';
            $where['a1.type']='01';
            if($this->panterid!='FFFFFFFF'){
                $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
            }
            $field='c.cardno,c.status,c.exdate,c.makedate,c.maketime,c.cardfee,';
            $field.='cu.namechinese cuname,cu.personid,cu.linktel,cu.customid cuid,p.namechinese pname,a.amount cardbalance,a1.amount cardpoint';
            $count=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('left join __PANTERS__ p on p.panterid=c.panterid')
                ->join('__ACCOUNT__ a on a.customid=c.customid')
                ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
                ->where($where)->field($field)->count();
            $p=new \Think\Page($count, 12);
            $card_list=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('left join __PANTERS__ p on p.panterid=c.panterid')
                ->join('__ACCOUNT__ a on a.customid=c.customid')
                ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
                ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            foreach ($card_list as $key=>$val){
	            switch ($val['cardfee']){
		            case '1' : $val['cardfee'] = '实体卡';break;
		            case '0' : $val['cardfee'] = '电子卡';break;
		            case '2' : $val['cardfee'] = '电子卡';break;
		            default  :$val['cardfee']= '实体卡'; break;
	            }
	            $card_list[$key] = $val;
            }
            $page = $p->show();
            $this->assign('page',$page);
            $this->assign('list',$card_list);
        }
        $this->display();
    }
    //挂失/解挂管理
    function lossCard(){
        if($_GET||$_POST){
            $cardno = isset($_REQUEST['cardno'])?$_REQUEST['cardno']:'';//卡号
            $cuname = isset($_REQUEST['cuname'])?$_REQUEST['cuname']:'';//会员名
            $customid = isset($_REQUEST['customid'])?$_REQUEST['customid']:'';//会员编号
            $personid = isset($_REQUEST['personid'])?$_REQUEST['personid']:'';//证件号
            $linktel = isset($_REQUEST['linktel'])?$_REQUEST['linktel']:'';//联系方式
            $cuname = isset($_REQUEST['cuname'])?$_REQUEST['cuname']:'';//会员名
            $status=isset($_REQUEST['status'])?$_REQUEST['status']:'';
            $cardno = $cardno=="卡号"?"":$cardno;
            $cuname = $cuname=="会员名称"?"":$cuname;
            $customid = $customid=="会员编号"?"":$customid;
            $personid = $personid=="证件号"?"":$personid;
            $linktel = $linktel=="联系电话"?"":$linktel;
            $model=new Model();
            if(!empty($cardno)){
                $where['c.cardno']=$cardno;
                $this->assign('cardno',$cardno);
                $map['cardno']=$cardno;
            }
            if(!empty($cuname)){
                $where['cu.namechinese']=array('like','%'.$cuname.'%');
                $this->assign('cuname',$cuname);
                $map['cuname']=$cuname;
            }
            if(!empty($customid)){
                $where['cu.customid']=$customid;
                $this->assign('customid',$customid);
                $map['customid']=$customid;
            }
            if(!empty($personid)){
                $where['cu.personid']=array('like','%'.$personid.'%');
                $this->assign('personid',$personid);
                $map['personid']=$personid;
            }
            if(!empty($linktel)){
                $where['cu.linktel']=array('like','%'.$linktel.'%');
                $this->assign('linktel',$linktel);
                $map['linktel']=$linktel;
            }
            if(!empty($status)&&$status!='all'){
                $where['c.status']=$status;
                $this->assign('status',$status);
                $map['status']=$status;
            }
            if(empty($where)){
                $this->error('请输入查询条件',U('AfterSales/lossCard'));
            }
            if($this->panterid!='FFFFFFFF'){
                $where1['p.panterid']=$this->panterid;
                $where1['p.parent']=$this->panterid;
                $where1['_logic']='OR';
                $where['_complex']=$where1;
            }else{
                $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
                $where['_complex']=$where1;
            }
            $where['_string']=" c.status <> 'R'";
            $field='c.cardno,cu.customid,cu.namechinese cuname,cu.personid,cu.linktel,c.status';
            $count=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->where($where)->field($field)->count();
            $p=new \Think\Page($count, 12);
            $card_list=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
            //echo $model->getLastSql();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $page = $p->show();
            $this->assign('page',$page);
            $this->assign('list',$card_list);
            if($card_list!=false){
                $this->assign('is_read',1);
            }
        }
        $this->display();
    }
    //会员信息查询
    function checkCustom(){
        $cardno=$_REQUEST['cardno'];
        if(empty($cardno)){
            $res['msg']='卡号缺失';
            $res['success']=0;
        }else{
            $field='c.cardno,cu.customid,cu.namechinese cuname,cu.personidtype,cu.personid,cu.linktel,c.status';
            $where=array('c.cardno'=>$cardno);
            $model=new Model();
            $cardinfo=$model->table('cards')->alias('c')
                ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                ->where($where)->field($field)->find();
            if($cardinfo==false){
                $res['msg']='卡号缺失';
                $res['success']=0;
            }else{
                if($cardinfo['status']=='Y'){
                    $cardinfo['statusString']='正常卡';
                }elseif($cardinfo['status']=='N'){
                    $cardinfo['statusString']='新卡';
                }elseif($cardinfo['status']=='C'){
                    $cardinfo['statusString']='出库';
                }elseif($cardinfo['status']=='L'){
                    $cardinfo['statusString']='挂失';
                }elseif($cardinfo['status']=='D'){
                    $cardinfo['statusString']='销卡';
                }elseif($cardinfo['status']=='A'){
                    $cardinfo['statusString']='待激活';
                }elseif($cardinfo['status']=='W'){
                    $cardinfo['statusString']='无卡';
                }elseif($cardinfo['status']=='J'){
                    $cardinfo['statusString']='入库';
                }elseif($cardinfo['status']=='T'){
                    $cardinfo['statusString']='冻结';
                }elseif($cardinfo['status']=='G'){
                    $cardinfo['statusString']='锁定';
                }
                $res['data']=$cardinfo;
                $res['success']=1;
            }
        }
        echo json_encode($res);
    }
    //挂失/解挂执行
    function lossCardDo(){
        $cardno=trim($_POST['cardno']);
        $type=trim($_POST['type']);
        $model=new Model();
        if(empty($cardno)){
            $res['msg']='卡号缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        if(empty($type)){
            $res['msg']='业务类型缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        if($type==1){
            $data=array('status'=>'L');
            //更新卡状态为锁定
            $model->table('cards')->where('cardno='.$cardno)->save($data);

            $lockid=$this->getnextcode('card_locks',19);
            $currenttime=date('Ymd',time());
            $userid=$this->userid;

            $sql='insert into card_locks(CardNo,LockedDate,lockid,updtetime,userid,description,cardno1)';
            $sql.=" values('{$cardno}','{$currenttime}','{$lockid}','{$currenttime}','{$userid}','卡挂失','00')";

            //echo $sql;exit;

            if($model->execute($sql)){
                $sql1='insert into card_locks_log(CardNo,LockedDate,lockid,userid,description,active)';
                $sql1.=" values('{$cardno}','{$currenttime}','{$lockid}','{$userid}','卡挂失',0)";

                if($model->execute($sql1)){
                    $res['success']=1;
                    echo json_encode($res);
                    exit;
                }
            }

        }elseif($type==2){
            $card_lock=$model->table('card_locks')->where('cardno='.$cardno)->find();
            $lockid=$card_lock['lockid'];

            //查看是否已经补卡
            $card_locks_log=$model->table('card_locks_log')->where('lockid='.$lockid)->find();
            if($card_locks_log['active']==2){
                $res['success']=0;
                $res['msg']='此卡已经补卡，无需解挂';
                echo json_encode($res);
                exit;
            }
            //更新卡状态由挂失转为正常
            $data1=array('status'=>'Y');
            $model->table('cards')->where('cardno='.$cardno)->save($data1);

            //更新挂失记录中状态为解挂
            $currenttime=date('Ymd',time());
            $data2=array('active'=>'1','description'=>'已经解挂','unlockeddate'=>$currenttime);
            $model->table('card_locks_log')->where('lockid='.$lockid)->save($data2);

            //将卡从黑名单中删掉
            $model->table('card_locks')->where('cardno='.$cardno)->delete();

            //添加解挂记录
            $userid=$this->userid;
            $cardjgId=$this->getnextcode('card_jg_logs',16);
            $sql="insert into card_jg_logs values('{$cardjgId}','{$cardno}','{$currenttime}','{$currenttime}',1,'卡解挂','{$userid}')";
            $model->execute($sql);

            $res['success']=1;
            echo json_encode($res);
            exit;
        }
    }

    //批量删除
    function batchDelete(){
        if(IS_POST){
            if(empty( $_FILES['file_stu']['name'])){
                $this->error('请上传卡号文件',U('Card/batchRecharge'));
            }
            $upload=$this->_upload('excel');
            if(is_string($upload)){
                $this->error($upload,U('Card/batchRecharge'));
            }else{
                $filename=PUBLIC_PATH.$upload['file_stu']['savepath'].$upload['file_stu']['savename'];
                $exceldate=$this->import_excel($filename);
                $model=new model();
                foreach($exceldate as $key=>$val){
                    $map['cardno']=trim($val[0]);
                    $cardLogs=M('card_purchase_logs')->where($map)->select();
                    $cardinfo=M('cards')->where($map)->find();
                    foreach($cardLogs as $k=>$v){
                        $map1['purchaseid']=$v['purchaseid'];
                        M('custom_purchase_logs')->where($map1)->delete();
                    }
                    M('card_purchase_logs')->where($map)->delete();
                    $map2['customid']=$cardinfo['customid'];
                    $map2['type']='00';
                    $data=array('amount'=>0);
                    M('account')->where($map2)->save($data);
                    M('trade_wastebooks')->where($map)->delete();
                }
            }
        }else{
            $this->display();
        }
    }

    //卡初始化ajax查询卡状态
    public function card_queryjiu()
    {
        $cards = M('cards');
        $trade = M('trade_wastebooks');
        if(IS_POST)
        {
            $cardno = $_POST['cardno'];
            if($res=$cards->where("cardno='{$cardno}'")->find())
            {
//           if($res['cardkind']!='6882' && $res['cardkind']!='2081')
//           {
//             $arr['msg']='此卡不是酒店的卡!';
//             $arr['success']='0';
//             echo json_encode($arr);
//             exit;
//           }
                if($res['status']!='Y')
                {
                    $arr['msg']='此卡不是正常卡!';
                    $arr['success']='0';
                    echo json_encode($arr);
                    exit;
                }
                if($trade->where("cardno=$cardno")->find())
                {
                    $arr['msg']='此卡有交易记录!';
                    $arr['success']='0';
                    echo json_encode($arr);
                    exit;
                }
                $where['c.cardno']=$cardno;
                $where['a.type']='00';
                $where['a1.type']='01';
                $field="c.cardno,c.status,c.exdate,cu.namechinese cuname,c.customid,cc.cid,a.amount card_money,a1.amount card_points";
                $card_list = $cards->alias('c')->JOIN('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->JOIN('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->JOIN('left join __ACCOUNT__ a on a.customid=c.customid')
                    ->JOIN('left join __ACCOUNT__ a1 on a1.customid=c.customid')
                    ->where($where)->field($field)->find();
                $card_list['card_money'] =!empty($card_list['card_money'])?$card_list['card_money']:0;
                $card_balance['card_points']=!empty($card_balance['card_points'])?$card_balance['card_points']:0;
                $arr['data'] = $card_list;
                $arr['success']='1';
                echo json_encode($arr);

            }
            else
            {
                $arr['msg']='未查询到该卡号,请核对!';
                $arr['success']='0';
                echo json_encode($arr);
            }
        }
        else
        {
            $arr['msg']='非法操作!';
            $arr['success']='0';
            echo json_encode($arr);
        }
    }
    public function initioncard()
    {
        $cards = M('cards');
        $tempo = M('tempo');
        if(IS_POST)
        {
            $cardno = trim(I('post.cardno',''));
            $flag='1';
            $tempowhere['cardno'] =$cardno;
            $tempowhere['flag'] = $flag;
            $cuname = trim(I('post.cuname',''));
            $customid = trim(I('post.customid',''));
            $status = trim(I('post.card_status',''));
            $cardbalance = trim(I('post.cardbalance',''));
            //判断该卡是否来自批量售卡
            $bool = $tempo->where($tempowhere)->find();
            if($bool)
            {
                $cards->startTrans();
                //1初始化卡
                $carddata['status']='N';
                $carddata['customid']='0';
                $cardif = $cards->where("cardno=$cardno")->save($carddata);
                $msg['1']=$cards->getlastSql();
                //2处理生成的customid
                //-----2016/04/08 不删除主会员信息
                $customs_c = M('customs_c');
                $customs = M('customs');
                $customsif=$customs_c->where("cid='{$customid}'")->delete();
                $arr = array();
                // $arr['1']=$customs_c->getlastSql();
                // // $arr['2']=$customs_c->getlastSql();
                // $msg['2']=$arr;
                $msg['2'] = $customs_c->getlastSql();
                if($customsif==true)
                {
                    $customif = true;
                }
                else
                {
                    $this->error('会员表信息初始化失败!');
                    $cards->rollback();
                }
                //3删除购卡单记录card_purchase_logs
                $cards_logs = M('card_purchase_logs');
                $listinfo = $cards_logs->where("cardno=$cardno")->find();
                if($listinfo)
                {
                    $purchaseid=$listinfo['purchaseid'];
                    $cards_logsif = $cards_logs->where("purchaseid='{$purchaseid}'")->delete();
                    $msg['3'] = $cards_logs->getlastSql();
                }
                else
                {
                    $cards_logsif=false;
                    $this->error('该卡查询不到购卡单号!');
                }
                //4custom_purchase_logs信息删除
                $custom_purchase_logs=M('custom_purchase_logs');
                $custom_purchase_logsif = $custom_purchase_logs->where("purchaseid="."'".$purchaseid."'")->delete();
                $msg['4'] = $custom_purchase_logs->getlastSql();
                //5删除卡激活记录的card_active_logs
                $active = M('card_active_logs');
                $activeif = $active->where("cardno=$cardno")->delete();
                $msg['5']=$active->getLastSql();
                //6删除账户中信息
                $account = M('account');
                $accountif = $account->where("customid="."'".$customid."'")->delete();
                $msg['6'] =$account->getlastSql();
                //7删除tempo 批量充值信息
                $tempoif = $tempo->where($tempowhere)->delete();
                $msg['7'] =$tempo->getlastSql();

                $quancz = M('quancz');
                $quanczlist = $quancz->where("customid='{$customid}'")->find();
                if($quanczlist){
                    $quanczif = $quancz->where("customid='{$customid}'")->delete();
                    $msg['8']=$quancz->getLastSql();
                }
                //8确认提交事务
                if($cardif==true&&$customif==true&&$custom_purchase_logsif==true&&$cards_logsif==true&&$activeif==true&&$accountif==true&&$tempoif==true)
                {
                    $cards->commit();
                    $msg['userid']=$this->userid;
                    $str ='';
                    foreach($msg as $key => $val){
                        $str .= $val."\t,";
                    }
                    $str.="\n";
                    file_put_contents('./hotelinit.log',$str,FILE_APPEND);
                    $this->success('卡初始化成功',U('AfterSales/initioncard'));

                }
                else
                {
                    $cards->rollback();
                    $this->error('初始化失败!');
                    exit;
                }
            }
            else
            {
                $cards->startTrans();
                //1初始化卡
                $carddata['status']='N';
                $carddata['customid']='0';
                $cardif = $cards->where("cardno=$cardno")->save($carddata);
                $msg['1']=$cards->getlastSql();
                //2处理生成的customsid
                $customs_c = M('customs_c');
                $customs = M('customs');
                $custom_list = $customs->where("customid='{$customid}'")->find();
                //-----2016/04/08 不删除主会员信息
                if($custom_list)
                {
                    //customds删除信息
                    $customsif=$customs_c->where("cid='{$customid}'")->delete();
                    $arr = array();
                    $msg['2'] = $customs_c->getlastSql();
                    if($customsif==true)
                    {
                        $customif = true;
                    }
                    else
                    {
                        $this->error('会员表信息初始化失败!');
                        $cards->rollback();
                    }
                }
                else
                {
                    $customs_c_list = $customs_c->where("cid='{$customid}'")->find();
                    if($customs_c_list)
                    {
                        $customif = $customs_c->where("cid='{$customid}'")->delete();
                        $msg['2'] = $customs_c->getlastSql();
                    }
                    else
                    {
                        $customif = false;
                        $cards->rollback();
                    }

                }
                //3删除购卡单记录card_purchase_logs
                $cards_logs = M('card_purchase_logs');
                $listinfo = $cards_logs->where("cardno=$cardno")->find();
                if($listinfo)
                {
                    $purchaseid=$listinfo['purchaseid'];
                    $cards_logsif = $cards_logs->where("purchaseid='{$purchaseid}'")->delete();
                    $msg['3'] = $cards_logs->getlastSql();
                }
                else
                {
                    $cards_logsif=false;
                    $this->error('该卡查询不到购卡单号!');
                }
                //4custom_purchase_logs信息删除
                $custom_purchase_logs=M('custom_purchase_logs');
                $custom_purchase_logsif = $custom_purchase_logs->where("purchaseid="."'".$purchaseid."'")->delete();
                $msg['4'] = $custom_purchase_logs->getlastSql();
                //5删除审核audit_logs审核通过记录
                $audit=M('audit_logs');
                $auditif = $audit->where("purchaseid='{$purchaseid}'")->delete();
                $msg['5'] = $audit->getlastSql();
                //6删除卡激活记录的card_active_logs
                $active = M('card_active_logs');
                $activeif = $active->where("cardno=$cardno")->delete();
                $msg['6']=$active->getLastSql();
                //7删除账户中信息
                $account = M('account');
                $accountif = $account->where("customid="."'".$customid."'")->delete();
                $msg['6']=$account->getLastSql();
                //删除卡券充值记录
                $quancz = M('quancz');
                $quanczlist = $quancz->where("customid='{$customid}'")->find();
                if($quanczlist){
                    $quanczif = $quancz->where("customid='{$customid}'")->delete();
                    $msg['7']=$quancz->getLastSql();
                }
                //8确认提交
                if($cardif==true&&$customif==true&&$custom_purchase_logsif==true&&$auditif==true&&$cards_logsif==true&&$activeif==true&&$accountif==true)
                {
                    $cards->commit();
                    $msg['userid']=$this->userid;
                    $str ='';
                    foreach($msg as $key => $val){
                        $str .= $val."\t,";
                    }
                    $str.="\n";
                    file_put_contents('./hotelinit.log',$str,FILE_APPEND);
                    $this->success('卡初始化成功',U('AfterSales/initioncard'));exit;

                }
                else
                {
                    $cards->rollback();
                    $this->error('初始化失败!');
                    exit;
                }
            }

        }
        else
        {
            $this->display();
        }
    }
    public function  freeze(){
        $ca = M('cards');
        $cardlists = $_POST;
        $cards = $cardlists['select'];
        if($cards=='' ||empty($cards)){
            $this->error('请选择冻结卡号');
        }
        $ca->startTrans();
        foreach ($cards as $key => $val){
            $bool = $ca->where(array('status'=>'Y','cardno'=>$val))->find();
            if($bool==true){
                $mod=$ca->where(array('cardno'=>$val))->save(array('status'=>'T'));
                if(!$mod){
                    $this->error('冻结失败');
                    exit;
                }
            }
            else {
                $this->error('冻结卡必须为正常卡');exit;
            }
        }
        $ca->commit();
        $this->success('冻结成功',U('AfterSales/cardinfo_query'));
    }
    public function  thaw(){
        $ca = M('cards');
        $cardlists = $_POST;
        $cards = $cardlists['select'];
        if($cards=='' ||empty($cards)){
            $this->error('请选择冻结卡号');
        }
        $ca->startTrans();
        foreach ($cards as $key => $val){
            $bool = $ca->where(array('status'=>'T','cardno'=>$val))->find();
            if($bool==true){
                $mod=$ca->where(array('cardno'=>$val))->save(array('status'=>'Y'));
                if(!$mod){
                    $this->error('解冻失败');
                    exit;
                }
            }else{
                $this->error('解冻卡必须为冻结卡');exit;
            }
        }
        $ca->commit();
        $this->success('解冻成功',U('AfterSales/cardinfo_query'));
    }
    public function  deblockingstatus(){
        if(IS_POST){
            $ca = M('cards');
            $cardlists = $_POST;
            $cards = $cardlists['select'];
            if($cards=='' ||empty($cards)){
                $this->error('请选择冻结卡号');
            }
            $ca->startTrans();
            foreach ($cards as $key => $val){
                $bool = $ca->where(array('status'=>'G','cardno'=>$val))->find();
                if($bool==true){
                    $mod=$ca->where(array('cardno'=>$val))->save(array('status'=>'Y','cardflag'=>'0'));
                    if(!$mod){
                        $this->error('解锁失败');
                        exit;
                    }
                }else{
                    $this->error('解锁卡必须为异常锁定卡');exit;
                }
            }
            $ca->commit();
            $this->success('解锁成功',U('AfterSales/cardinfo_query'));
        }
    }
    public function rechargeCancle(){
        $this->display();
    }
    public function cancleCondition(){
        if(IS_POST){
            $page =$_POST['page'];
            $pageSize = $_POST['rows'];
            $cardno =trim(I('post.cardno',''));
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
        }
        $first = $pageSize * ($page - 1);
        $userid = $this->userid;
        $card_purchase_logs = M('card_purchase_logs');
//        $where['cu.userid'] = $userid;
//        $where['c.placeddate'] = date('Ymd',time());
        $where['c.flag'] = '1';
        $where['cu.flag'] = '1';
//         $where['c.placeddate'] = '20160512';
        $field = 'c.purchaseid,c.cardno,cu.customid,c.amount,c.placeddate cplaceddate,c.placedtime cplacedtime,cu.placeddate,cu.placedtime,cus.namechinese,cus.linktel,cu.purchaseid pur';
        $lists = $card_purchase_logs->alias('c')
            ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
            ->join('customs  cus on cus.customid=cu.customid')
//            ->join('users u on u.userid = cu.userid')
            ->where($where)->field($field)->limit($first,$pageSize)->select();
        //  dump($lists);exit;
        $count = $card_purchase_logs->alias('c')
            ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
            ->join('customs  cus on cus.customid=cu.customid')
//            ->join('users u on u.userid = cu.userid')
            ->where($where)->field($field)->count();
        if(!empty($lists)){
            foreach ($lists as $key => $val){
                $json.=json_encode($val).',';
            }
        }
        $json = substr($json, 0, -1);
        echo '{"total" : '.$count.', "rows" : ['.$json.']}';
    }
    //充值撤销发起
    public function cancle(){
        $userid = $this->userid;
        if(IS_POST){
            // dump($_POST);exit;
            $pur = trim(I('post.pur',''));
            if($pur!=''){
                $card_purchase_logs = M('card_purchase_logs');
                $where['c.purchaseid']=$pur;
                $where['c.flag'] = '1';
                $where['cu.flag'] = '1';//审核通过状态
                $field = 'cu.purchaseid,cu.userid,c.cardno,c.amount';
                $cardif = $card_purchase_logs->alias('c')
                    ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
                    ->where($where)->field($field)->find();
//                 $res = $card_purchase_logs->getLastSql();
                if($cardif){
                    //开启撤销操作
                    $custom_purchase_logs = M('custom_purchase_logs');
                    $custom_purchase_logs->startTrans();
                    $whereCancel['purchaseid']= $pur;
                    $whereCancel['flag'] ='1';
                    $cancel['flag'] = '3';
                    $nowdate = date('Ymd',time());
                    $nowtime = date('H:i:s',time());
                    $sql = "INSERT INTO charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid)VALUES";
                    $sql .="('{$pur}','{$cardif['cardno']}','{$cardif['amount']}','1','{$nowdate}','{$nowtime}','{$cardif['userid']}')";
                    $logsif = $custom_purchase_logs->execute($sql);
                    $cancleif = $custom_purchase_logs->where($whereCancel)->save($cancel);
                    if($cancleif && $logsif){
                        $custom_purchase_logs->commit();
                        exit(json_encode(array('status'=>'1','msg'=>'撤销已经申请,请审核!')));
                    }else{
                        $custom_purchase_logs->rollback();
                        exit(json_encode(array('status'=>'0','msg'=>'撤销失败,请重试!')));
                    }
                }else{
                    exit(json_encode(array('status'=>'2','msg'=>'未查到该订单充值记录')));
                }
            }else{
                exit(json_encode(array('status'=>'3','msg'=>'订单号为空')));
            }
        }else{
            exit(json_encode(array('status'=>'4','msg'=>'非法访问')));
        }
    }
    public function passHtml(){
        $this->display();
    }
    //充值审核返回数据
    public function passJson(){
        if(IS_POST){
            $page =$_POST['page'];
            $pageSize = $_POST['rows'];
//             $cardno = trim(I('post.cardno',''));
        }
        $first = $pageSize * ($page - 1);
        $userid = $this->userid;
        $card_purchase_logs = M('card_purchase_logs');
        // $where['c.userid'] = $userid;
        //   $where['c.placeddate'] = date('Ymd',time());
        $where['c.flag'] = '1';
        $where['cu.flag'] = '3';
//         $where['c.placeddate'] = '20160512';
        $field = 'c.purchaseid,c.cardno,cu.customid,c.amount,c.placeddate cplaceddate,c.placedtime cplacedtime,cu.placeddate,cu.placedtime,cus.namechinese,cus.linktel,cu.purchaseid pur';
        $lists = $card_purchase_logs->alias('c')
            ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
            ->join('customs  cus on cus.customid=cu.customid')
            //     ->join('users u on u.userid = cu.userid')
            ->where($where)->field($field)->limit($first,$pageSize)->select();
        $count = $card_purchase_logs->alias('c')
            ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
            ->join('customs  cus on cus.customid=cu.customid')
            //    ->join('users u on u.userid = cu.userid')
            ->where($where)->field($field)->count();
        if(!empty($lists)){
            foreach ($lists as $key => $val){
                $json.=json_encode($val).',';
            }
        }
        $json = substr($json, 0, -1);
        echo '{"total" : '.$count.', "rows" : ['.$json.']}';
    }
    //充值审核操作
    public function passHandler(){
        $userid = $this->userid;
        if(IS_POST){
            $pur = trim(I('post.pur',''));
            if($pur!=''){
                $card_purchase_logs = M('card_purchase_logs');
                $where['c.purchaseid']=$pur;
                $where['c.flag'] = '1';
                $where['cu.flag'] = '3';//发起充值撤销状态
                $field = 'cu.purchaseid,cu.userid,c.cardno,c.amount';
                $cardif = $card_purchase_logs->alias('c')
                    ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
                    ->where($where)->field($field)->find();
//                  $res = $card_purchase_logs->getLastSql();
//                  exit(json_encode(array('status'=>'5','msg'=>$res)));
                if($cardif){
                    //开启撤销操作
                    $account = M('account');
                    $cards = M('cards');
                    $whereCancel['purchaseid']= $pur;
                    $whereCancel['flag'] ='1';
                    $cancel['flag'] = '3';
                    //开启事务
                    $account->startTrans();
                    $cancleif = $card_purchase_logs->where($whereCancel)->save($cancel);
                    //账号金额退回
                    $cardno = $cards->where(array('cardno'=>$cardif['cardno']))->field('customid')->find();
                    if($cardno['customid']==''||$cardno['customid']==null)
                        exit(json_encode(array('status'=>'5','msg'=>'用户编号异常!')));
                    $customAccount = $account->where(array('customid'=>$cardno['customid'],'type'=>'00'))->find();
                    //对比卡余是否够撤销金额
                    $boo1 = bccomp($customAccount['amount'],$cardif['amount'],2);
                    if($boo1==-1){
                        $str = '账户该卡'.$cardif['cardno'].'余额为：'.$customAccount['amount']."\t,撤销充值金额为：".$cardif['amount'];
                        file_put_contents('./bbb.txt',$str,FILE_APPEND);
                        exit(json_encode(array('status'=>'6','msg'=>'充值审核失败,请联系管理员!')));
                    }
                    $amount['amount'] = bcsub($customAccount['amount'],$cardif['amount'],2);
                    $accountif = $account->where(array('customid'=>$cardno['customid'],'type'=>'00'))->save($amount);
                    //记录操作
                    $nowdate = date('Ymd',time());
                    $nowtime = date('H:i:s',time());
                    $sql = "INSERT INTO charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid)VALUES";
                    $sql.="('{$pur}','{$cardif['cardno']}','{$cardif['amount']}','2','{$nowdate}','{$nowtime}','{$cardif['userid']}')";
                    $logsif = $account->execute($sql);
                    if($accountif && $cancleif && $logsif){
                        $account->commit();
                        exit(json_encode(array('status'=>'1','msg'=>'充值撤销成功!')));
                    }else{
                        $account->rollback();
                        exit(json_encode(array('status'=>'0','msg'=>'撤销失败,请重试!')));
                    }
                }else{
                    exit(json_encode(array('status'=>'2','msg'=>'未查到该订单充值记录')));
                }
            }else{
                exit(json_encode(array('status'=>'3','msg'=>'订单号为空')));
            }
        }else{
            exit(json_encode(array('status'=>'4','msg'=>'非法访问')));
        }
    }
    //充值审核拒绝
    public function undo(){
        if(IS_POST){
            $pur = trim(I('post.pur',''));
            $description = trim(I('post.description',''));
            if($description==''){
                exit(json_encode(array('status'=>'5','msg'=>'请填写拒绝理由!')));
            }
            if($pur!=''){
                $card_purchase_logs = M('card_purchase_logs');
                $where['c.purchaseid']=$pur;
                $where['c.flag'] = '1';
                $where['cu.flag'] = '3';//审核通过状态
                $field = 'cu.purchaseid,cu.userid,c.cardno,c.amount';
                $cardif = $card_purchase_logs->alias('c')
                    ->join('custom_purchase_logs cu on cu.purchaseid=c.purchaseid')
                    ->where($where)->field($field)->find();
                //                 $res = $card_purchase_logs->getLastSql();
                if($cardif){
                    $custom_purchase_logs = M('custom_purchase_logs');
                    $custom_purchase_logs->startTrans();
                    $whereCancel['purchaseid']= $pur;
                    $whereCancel['flag'] ='3';
                    $cancel['flag'] = '1';
                    $cancleif = $custom_purchase_logs->where($whereCancel)->save($cancel);
                    //记录操作
                    $nowdate = date('Ymd',time());
                    $nowtime = date('H:i:s',time());
                    $sql = "INSERT INTO charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description)VALUES";
                    $sql.="('{$pur}','{$cardif['cardno']}','{$cardif['amount']}','3','{$nowdate}','{$nowtime}','{$cardif['userid']}','{$description}')";
                    $logsif=$custom_purchase_logs->execute($sql);
                    if($cancleif && $logsif){
                        $custom_purchase_logs->commit();
                        exit(json_encode(array('status'=>'1','msg'=>'审核拒绝操作成功!')));
                    }else{
                        $custom_purchase_logs->rollback();
                        exit(json_encode(array('status'=>'0','msg'=>'拒绝操作失败,请重试!')));
                    }
                }else{
                    exit(json_encode(array('status'=>'2','msg'=>'未查到该订单充值记录')));
                }
            }else{
                exit(json_encode(array('status'=>'3','msg'=>'订单号为空')));
            }
        }else{
            exit(json_encode(array('status'=>'4','msg'=>'非法访问')));
        }
    }
    //修改卡密码：
    public function editCode(){
        $panterid = $this->panterid;
        $cards = M('cards');
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $old_password = trim(I('post.old_password',''));
            $new_password = trim(I('post.new_password',''));
            $re_password = trim(I('post.re_password',''));
            $card_where = array('cardno'=>$cardno,'status'=>'Y');
            $cardif = $cards->where($card_where)->find();
            if(false==$cardif){
                $this->error('卡号不存在，请核对！');
            }
            if($panterid!='FFFFFFFF'){
                $cardif['panterid']==$panterid || $this->error('非本部门的卡！');
            }
            $passWord = $this->des->doEncrypt($old_password);
//            $passWord = $this->des->doDecrypt($cardif['cardpassword']);
//            $passWord = substr($passWord,0,6);
            $cardif['cardpassword']==$passWord || $this->error('原密码不正确！');
            $new_password!='' ||$this->error('新密码不能为空！');
            $new_password==$re_password ||$this->error('两次密码不一致！');
            $new_password = $this->des->doEncrypt($new_password);
            $bool = $cards->where($card_where)->save(array('cardpassword'=>$new_password));
            if($bool){
                $this->success('修改成功！');
            }else{
                $this->error('密码修改失败！');
            }
        }
        $this->display();
    }
    function resetcode(){
        if(IS_POST){
            $panterid = $this->panterid;
            $cardno = trim(I('post.cardno',''));
            if($cardno=='')
                $this->error('卡号不能为空！');
            $map=array('cardno'=>$cardno,'status'=>'Y');
            if($panterid!='FFFFFFFF'){
                $cardif = M('cards')->where($map)->find();
                if(true==$cardif){
                    $cardif['panterid']==$panterid ||$this->error('不是本部门的卡');
                    $cardpass = '888888';
                    $cardpass = $this->des->doEncrypt($cardpass);
                    $bool = M('cards')->where($map)->save(array('cardpassword'=>$cardpass));
                    if(true==$bool){
                        $this->success('卡密码重置成功！');
                    }else
                        $this->error('请联系管理员！');
                }else{
                    $this->error('请检查卡号 或卡必须是正常卡！');
                }
            }else{
                $cardif = M('cards')->where($map)->find();
                if(true==$cardif){
                    $cardpass = '888888';
                    $cardpass = $this->des->doEncrypt($cardpass);
                    $bool = M('cards')->where($map)->save(array('cardpassword'=>$cardpass));
                    if(true==$bool){
                        $this->success('卡密码重置成功！');
                    }else
                        $this->error('请联系管理员！');
                }else{
                    $this->error('请检查卡号 或卡必须是正常卡！');
                }
            }
        }else{
            $this->display();
        }

    }

    function cardExdate(){
        if($_POST){
            $cardno=trim($_POST['cardno']);
            $exdate=trim($_POST['exdate']);
            if(empty($cardno)){
                $this->error('卡号不能为空');
            }
            if(empty($exdate)){
                $this->error('新有效期不能为空');
            }
            $currentDate=date('Ymd');
            if($exdate<=$currentDate){
                $this->error('卡号新有效期不能早于当前日期');
            }
            $map=array('cardno'=>$cardno);
            $cards=M('cards');
            $card=$cards->where($map)->field('cardno,status,exdate,panterid')->find();
            if($card==false){
                $this->error('无效卡号');
            }
            if($this->panterid!='FFFFFFFF'){
                $map1=array('panterid'=>$card['panterid']);
                $panter=M('panters')->where($map1)->field('panterid,parent')->find();
                if($card['panterid']!=$this->panterid&&$panter['parent']!=$this->panterid){
                    $this->error('非同部门卡，不能修改卡号有效期');
                }
            }
            $data['exdate']=$exdate;
            if($cards->where($map)->save($data)){
                $this->success($cardno.'有效期修改成功');
            }else{
                $this->error($cardno.'有效期修改失败');
            }
        }else{
            $this->display();
        }
    }
    function checkCardExdate(){
        $cardno=trim($_POST['cardno']);
        if(empty($cardno)){
            exit(json_encode(array('status'=>'01','msg'=>'卡号不能为空')));
        }
        $map=array('cardno'=>$cardno);
        $card=M('cards')->where($map)->field('cardno,status,exdate,panterid')->find();
        if($card==false){
            exit(json_encode(array('status'=>'02','msg'=>' 无效卡号')));
        }
        if($card['status']!='Y'){
            exit(json_encode(array('status'=>'03','msg'=>' 非正常卡')));
        }
        if($this->panterid!='FFFFFFFF'){
            $map1=array('panterid'=>$card['panterid']);
            $panter=M('panters')->where($map1)->field('panterid,parent')->find();
            if($card['panterid']!=$this->panterid&&$panter['parent']!=$this->panterid){
                exit(json_encode(array('status'=>'04','msg'=>'非同部门卡，不能读取/修改卡号有效期')));
            }
        }
        exit(json_encode(array('status'=>'1','info'=>array('exdate'=>$card['exdate']))));
    }

    protected function recordData($data,$childPath){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $path=PUBLIC_PATH.'logs/AfterSales/'.$childPath.'/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$data,FILE_APPEND);
    }
    //添加卡限额
    public function add(){
        $model=M('custom_con_account');
        if(IS_POST){
            $customid=trim($_POST['customid']);
            $account=trim($_POST['account']);
            $sumnumber=trim($_POST['sumnumber']);
            $oneaccount=trim($_POST['oneaccount']);
            $userid=$this->userid;
            if(empty($customid)){
                $this->error("卡编号不能为空");
            }
            if(empty($account)){
                $this->error("日消费限制累计不能为空");
            }
            if(empty($sumnumber)){
                $this->error("每日消费次数不能为空");
            }
            if(empty($oneaccount)){
                $this->error("每笔刷卡限额不能为空");
            }
            $sql ="insert into custom_con_account(customid,account,sumnumber,oneaccount,userid) values ('".$customid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')";
            if($model->execute($sql)){
                $model->commit();
                $this->success('添加成功',U('AfterSales/cardinfo_qa'));
            }else{
                $model->rollback();
                $this->error('添加失败');
            }
        }else{

            $this->display();
        }

    }
    //编辑卡限额
    public function edit(){
        $model=M('custom_con_account');
        if(IS_POST){
            $customid=I('post.customid');
            if($customid!=''){
                $wheres['customid']=$customid;
            }else{
                $this->error('卡编号不能为空！');
            }
            if($model->create()){
                $_POST['customid']=trim($_POST['customid']);
                $_POST['account']=trim($_POST['account']);
                $_POST['sumnumber']=trim($_POST['sumnumber']);
                $_POST['oneaccount']=trim($_POST['oneaccount']);
                if($model->where($wheres)->save($_POST)){
                    $this->success('修改成功！',U('AfterSales/cardinfo_qa'));
                }else{
                    $this->error('修改失败！',U('AfterSales/edit'));
                }
            }
        }else {
            $customid = I('get.customid');
            if ($customid != '') {
                $where['customid'] = $customid;
            }
            $list=$model->where($where)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }
    //卡限额处理
    public function cardinfo_qa(){
        $customid=trim($_REQUEST['customid']);
        $customid = $customid=="卡编号"?"":$customid;
        if(!empty($customid)){
            $where['cc.customid']=$customid;
            $this->assign('customid',$customid);
        }else{
            $where['cc.customid']=array('like','0%');
        }
        $model=new model();
        $count=$model->table('custom_con_account')->alias('cc')
            ->join('left join __CARDS__ ca on cc.customid=ca.customid')
            ->join('left join __CUSTOMS_C__ cu on cu.cid=cc.customid')
            ->join('left join __CUSTOMS__ c on c.customid=cu.customid')
            ->where($where)->field('cc.*,c.customid cname,ca.cardno cardno')->count();
        $p=new \Think\Page($count, 10);
        $custom_list=$model->table('custom_con_account')->alias('cc')
            ->join('left join __CARDS__ ca on cc.customid=ca.customid')
            ->join('left join __CUSTOMS_C__ cu on cu.cid=cc.customid')
            ->join('left join __CUSTOMS__ c on c.customid=cu.customid')
            ->where($where)->field('cc.*,c.namechinese cname,ca.cardno cardno')
            ->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('list',$custom_list);
        $this->assign('page',$page);
        $this->display();
    }
	//商品退货审核
	public function refundVerify(){
		$map['startdate'] = trim(I('get.startdate',''));
		$map['enddate']   = trim(I('get.enddate',''));
		$map['tradeid']   = trim(I('get.tradeid',''));
		$map['status']    = trim(I('get.status',''));
		$map['status']!=''||$map['status']='0';
		if($map['startdate']=='') $map['startdate'] = date('Y-m-d');
		if($map['enddate'] !='') {
			$where['re.placeddate']    = [
				['egt',str_replace('-','',$map['startdate'])],
				['elt',str_replace('-','',$map['enddate'])]
			];
		}else{
			$where['re.placeddate']  = ['egt',str_replace('-','',$map['startdate'])];
		}
		if($map['tradeid']) $where['re.tradeid'] = ['like','%'.$map['tradeid'].'%'];
		$where['re.status'] = $map['status'];
		if($this->panterid!='FFFFFFFF'){
			$where['_string']="re.panterid='".$this->panterid."' OR re.parent='".$this->panterid."'";
		}
		$count = M('refund_goods')->alias('re')
		                          ->join('left join trade_wastebooks tw on re.tradeid=tw.tradeid')
		                          ->where($where)->count();
		$page  =  new \Think\Page($count,10,$map);
		$field = 'tw.cardno,tw.tradeamount,re.*,tw.placeddate tplaceddate,tw.placedtime tplacedtime';
		$list  = M('refund_goods')->alias('re')
		                          ->join('left join trade_wastebooks tw on re.tradeid=tw.tradeid')
		                          ->where($where)
		                          ->limit($page->firstRow,$page->listRows)->field($field)->select();
		$this->assign('list',$list);
		$this->assign('map',$map);
		$this->assign('verifyStatus',['0'=>'未审核','1'=>'已审核']);
		$this->assign('page',$page->show());
		$this->display();
	}

	//商品退货
	public function refundOrderHandle(){
		$refundid = trim(I('post.refund_id',''));
		if(empty($refundid)) returnMsg(array('status'=>'03','codemsg'=>'订单号不能为空'));
		$map['refundid'] = $refundid;
		$map['status']   = '0';
		$refundInfo = M('refund_goods')->where($map)->field('tradeid')->find();
		if($refundInfo){
			$where['tw.tradeid']  = $refundInfo['tradeid'];
			$where['tw.tradetype']= '44';
			$where['ac.type']    = '00';
			$field = 'ca.cardno,ca.status,ca.exdate,ca.customid,tw.tradeamount tamount,cu.personid,ac.amount,tw.placeddate,tw.tradetype';
			$info = M('trade_wastebooks')->alias('tw')
			                             ->join('left join cards ca on ca.cardno=tw.cardno')
			                             ->join('left join account ac on ac.customid=ca.customid')
			                             ->join('left join customs_c cc on cc.cid=ca.customid')
			                             ->join('left join customs cu on cu.customid=cc.customid')
			                             ->where($where)->field($field)->select();
			$real = $info[0];
			if($real){
				$real['status']=='Y'|| returnMsg(array('status'=>'05','codemsg'=>'非正常卡'));
				$real['exdate']>=date('Ymd') || returnMsg(array('status'=>'06','codemsg'=>'此卡已经过期'));
				$real['tamount']>0 || returnMsg(array('status'=>'07','codemsg'=>'退货金额应大于0'));
				$total = bcadd($real['amount'],$real['tamount'],2);
				if($real['namechinese'] && $real['personid']){
					$total<=5000 || returnMsg(array('status'=>'06','codemsg'=>'记名卡单卡金额不能超过5000'));
				}else{
					$total<=1000 || returnMsg(array('status'=>'06','codemsg'=>'不记名卡单卡金额不能超过1000'));
				}

				//开启事务
				$model = new Model();
				$model->startTrans();
				try{
					$acc['customid'] = $real['customid'];
					$acc['type']     = '00';
					$refundIf = M('refund_goods')->where($map)->save(['status'=>'1']);
					$tradeIf  = M('trade_wastebooks')->where(['tradeid'=>$refundInfo['tradeid']])->save(['tradetype'=>'04']);
					$account  = M('account')->where($acc)->save(['amount'=>$total]);
				}catch(\Exception $e){
					$model->rollback();
					returnMsg(array('status'=>'05','codemsg'=>$e->getMessage()));
				}
				if ($refundIf && $tradeIf && $account){
					$model->commit();
					returnMsg(array('status'=>'1','codemsg'=>'退货审核成功'));
				}else{
					$model->rollback();
					returnMsg(array('status'=>'06','codemsg'=>'退货审核失败'));
				}
			}else{
				returnMsg(array('status'=>'04','codemsg'=>'账户信息异常'));
			}
		}else{
			returnMsg(array('status'=>'04','codemsg'=>'无次退款订单信息'));
		}
	}
}
