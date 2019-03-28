<?php
namespace Home\Controller;
use Org\Util\Des;
use Think\Controller;
use Think\Model;
class CardStockController extends CommonController {
    public function _initialize(){
        parent::_initialize();
        $this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    }
    //生成制卡文件控制器
    public function cardFile(){
        $makecardid=trim($_REQUEST['makecardid']);
        $start = I('post.startdate','');
        $end = I('post.enddate','');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['makedate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['makedate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['makedate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['makedate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($makecardid)){
            $where['makecardid']=$makecardid;
            $this->assign('makecardid',$makecardid);
        }
        $where['status']='W';
        $where['dcflag']=0;
        $list=M('cards')->where($where)->field('cardno,makedate,maketime,status,makecardid')->select();
        session('createCondition',$where);
        $this->assign('list',$list);

        $map1=array('flag'=>0);
        $brandInfo=M('brand')->where($map1)->field('brandid,brandname')->select();
        $cityInfo=M('city')->field('cityid,cityname')->order('cityid asc')->select();
        $levelInfo=M('cardlevel')->field('id,name')->select();
        $map2=array('flag'=>'启用');
        $ylInfo=M('yl_kind')->where($map2)->field('ylid,ylname')->select();
        $this->assign('brandInfo',$brandInfo);
        $this->assign('cityInfo',$cityInfo);
        $this->assign('levelInfo',$levelInfo);
        $this->assign('ylInfo',$ylInfo);
        $this->display();
    }
    //生成卡号操作
    public function createCards(){
        ini_set("max_execution_time", 2400);
        $brandid=trim($_REQUEST['brandid']);
        $cityid=trim($_REQUEST['cityid']);
        $levelid=trim($_REQUEST['levelid']);
        $ylid=trim($_REQUEST['ylid']);
        $amount=trim($_REQUEST['amount']);
        $pwdtype=trim($_REQUEST['pwdtype']);
        $ctype=trim($_REQUEST['ctype']);
        $cardfee=trim($_REQUEST['cardfee']);
        if(empty($brandid)||$brandid=='-1'){
            $this->error('卡类型编号不能为空');
        }
        if(empty($cityid)||$cityid=='-1'){
            $this->error('城市编号不能为空');
        }
        if($levelid==''||$levelid=='-1'){
            $this->error('卡级别不能为空');
        }
        if(empty($ylid)||$ylid=='-1'){
            $this->error('预留位不能为空');
        }
        if(empty($amount)||!preg_match('/^[1-9]\d*$/',$amount)){
            $this->error('制卡数量格式有误');
        }
        if(empty($pwdtype)){
            $this->error('密码方式不能为空');
        }
        $model=new Model();
        $model->startTrans();
        $cards=D('cards');
        $cardkindid=$this->getnextcode('makecardid',8);
        $cardid1=$brandid.$cityid.$levelid.$ylid;
        $str = 'INSERT ALL';
        $data='';
        for($i=1;$i<=$amount;$i++){
            if($ctype==1){
                $cardid2=$this->getNextcardno($brandid,8);
            }else{
                $cardid2=$this->getnextcode($brandid,8);
            }
            $cardid=$cardid1.$cardid2;
            if($pwdtype==1){
                $pwd='888888';
            }else{
               $pwd=$this->creatRandNum(6);
            }
            $cardpassword=$this->des->doEncrypt($pwd);
            $cardDes=$this->des->doEncrypt($cardid);
            $serial_no=substr($cardDes,0,6);
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $str .=  " INTO CARDS(CardNo,CustomId,ExDate,Status,cardpassword,dcflag,MakeDate,MakeTime,makecardid,panterid,CardBalance,CardInitAmount,cardkind,brandid,serial_no,cardfee) VALUES ('{$cardid}',0,'{$currentDate}','W','{$cardpassword}',0,'{$currentDate}','{$currentTime}','{$cardkindid}','',0,0,'{$brandid}','{$brandid}','{$serial_no}','{$cardfee}') ";

            if($i%200==0||$i==$amount){
                    $data .= $str. " SELECT 1 FROM DUAL";
                    $sql=$model->execute($data);//循环执行
                    $data='';
                    $str ='INSERT ALL';
                    if($sql){
                        $res=true;
                    }else{
                        $res=false;
                        break;
                    }
                  }
            }
               if($res){
                   $model->commit();
                   $this->success('制卡成功，制卡'.$amount.'张卡');
               }else{
                   $model->rollback();
               }
    }

    //生成制卡文件
    public function createFile(){
        $this->setTimeMemory();
        $where=session('createCondition');
        $list=M('cards')->where($where)->field('cardno,makedate,maketime,status,makecardid,cardpassword')
            ->order('makecardid asc,cardno asc')->select();
//        $logPath='./Public/logs/createCards/';
        $logPath=PUBLIC_PATH.'logs/createCards/';
        $filename=date('Ymd').'_'.date('His').'_'.$list[0]['makecardid'].'.txt';
        $saveName=$logPath.$filename;
        $model=new Model();
        $model->startTrans();
        $data=array('dcflag'=>1);
        $i=0;
        $cardnoStr='';
        foreach($list as $key=>$val){
            $i++;
            $cardnoStr = $cardnoStr . $val['cardno'].',';
            if($i%200==0 || $i==count($list)){
                $map['cardno']=array('in', rtrim($cardnoStr, ","));
                if($model->table('cards')->where($map)->save($data)) {
                    $cardnoStr = '';
                    $res=true;
                }else{
                    $res=false;
                    break;
                }
            }
            $pwd=$this->des->doDecrypt($val['cardpassword']);
            $str.=$val['cardno']."    ".substr($pwd,0,6)."\r\n";
        }
        if($res){
            $str1="本批次共有卡号{$i}个。\r\n卡号\r\n===================\r\n";
            $str=$str1.$str;
            if(!file_exists($logPath)){
                mkdir($logPath,0777,true);
            }
            file_put_contents($saveName,$str,FILE_APPEND);
            $model->commit();
            session('createCondition',null);
            echo "<script type='text/javascript'>alert('文件导出成功');</script>";
            $this->success('<a href="'.U('CardStock/download','file='.$filename).'">请点击下载制卡文件</a>',U('CardStock/cardFile'),15);
        }else{
            $model->rollback();
            $this->error('文件导出失败');
        }
    }


    //下载制卡文件
    public function download(){
        $filename=$_REQUEST['file'];
        if(empty($filename)){
            $this->error('文件名缺失');
        }
        $filename=PUBLIC_PATH.'logs/createCards/'.$filename.'.txt';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($filename);
    }

    //卡片入库控制器
    public function cardRestore(){
        $makecardid=trim($_REQUEST['makecardid']);
        $startcardno = trim($_REQUEST['startcardno']);
        $endcardno = trim($_REQUEST['endcardno']);
        $brandname=trim($_REQUEST['brandname']);
        if(!empty($makecardid)){
            $where['c.makecardid']=$makecardid;
            $this->assign('makecardid',$makecardid);
        }
        if($startcardno!='' && $endcardno==''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            $where['c.cardno']=array('egt',$startcardno);
            $this->assign('startcardno',$startcardno);
            $map['startcardno']=$startcardno;
        }
        if($startcardno=='' && $endcardno!=''){
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where['c.cardno'] = array('elt',$endcardno);
            $this->assign('endcardno',$endcardno);
            $map['endcardno']=$endcardno;
        }
        if($startcardno!='' && $endcardno!=''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where['c.cardno']=array(array('egt',$startcardno),array('elt',$endcardno));
            $this->assign('startcardno',$startcardno);
            $this->assign('endcardno',$endcardno);
            $map['startcardno']=$startcardno;
            $map['endcardno']=$endcardno;
        }
        if(!empty($brandname)){
            $where['b.brandname']=array('like','%'.$brandname.'%');
        }
        $where['c.status']='W';
        $where['c.dcflag']=1;
        $field="c.cardno,c.makedate,c.maketime,c.brandid,c.status,c.makecardid,b.brandname";
        $list=M('cards')->alias('c')->join('brand b on b.brandid=c.brandid')
            ->where($where)->field($field)->order('c.makecardid desc')->select();
        session('restoreCondition',$where);
        $this->assign('list',$list);
        $this->display();
    }

    //卡片入库
    public function restore(){
        $this->setTimeMemory();
        $where=session('restoreCondition');
        $model=new Model();
        $list=$model->table('cards')->alias('c')->where($where)->field('cardno,brandid')->select();
        $model->startTrans();
        $c=0;
        $cardnoStr='';
//        foreach($list as $key=>$val){
//            $c++;
//            $data=array('status'=>'J');
//            $cardnoStr = $cardnoStr . $val['cardno'].',';
//            if($c%200==0 || $c==count($list)){
//                $map['cardno']=array('in', rtrim($cardnoStr, ","));
//                if($model->table('cards')->where($map)->save($data)){
//                    $sql="UPDATE inventory SET cardsw = cardsw + $c where brandid='{$val['brandid']}'";
//                    $sql1= $model->execute($sql);
//                    $c='';
//                    $cardnoStr='';
//                    }
//                    if($sql1){
//                        $res = true;
//                    }else{
//                        $res = false;
//                        break;
//                    }
//                }
//            }
//        if($res){
//            $model->commit();
//            session('restoreCondition',null);
//            $this->success('入库成功');
//        }else{
//            $model->rollback();
//            $this->error('入库失败');
//        }
//    }
        foreach($list as $key=>$val){
            $c++;
            $data=array('status'=>'J');
            $cardnoStr = $cardnoStr . $val['cardno'].',';
            if($c%200==0||$c==count($list)){
                $map['cardno']=array('in', rtrim($cardnoStr, ","));
                if($model->table('cards')->where($map)->save($data)){
                    $cardnoStr='';
                    $res = true;
                }else{
                    $res = false;
                    break;
                }
            }
        }
        if($res){
            $sql="UPDATE inventory SET cardsw= cardsw + $c where brandid='{$val['brandid']}'";
            if($model->execute($sql)){
                $model->commit();
                session('restoreCondition',null);
                $this->success('入库成功');
            }else{
                $model->rollback();
                $this->error('入库失败');
            }
        }else{
            $model->rollback();
            $this->error('入库失败');
        }
    }
    //卡片派送
    public function cardSend(){
        $id=trim($_REQUEST['id']);
        $panterid = trim($_REQUEST['panterid']);
        $pname = trim($_REQUEST['pname']);
        $start = trim($_REQUEST['startdate']);
        $end = trim($_REQUEST['enddate']);
        if(!empty($id)){
            $where['a.id']=$id;
            $this->assign('id',$id);
        }
        if(!empty($panterid)){
            $where['p.panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        if(!empty($pname)){
            $where['p.pname']=$pname;
            $this->assign('pname',$pname);
        }
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['a.appdate']=array('egt',$startdate);
            $this->assign('startdate',$start);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['a.appdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['a.appdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['a.appdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-01',strtotime(date('Ymd'))));
            $this->assign('enddate',date('Y-m-d',time()));
        }
        $where['librarysw']=0;
        $field="a.*,p.namechinese pname";
        $list=D('application')->alias('a')->join('panters p on p.panterid=a.applicant')
            ->where($where)->field($field)->select();
        $this->assign('list',$list);
        $this->display();
    }

    //卡片派送添加
    public function sendAdd(){
        if(IS_POST){
            $brandid=trim($_REQUEST['brandid']);
            $panterid=trim($_REQUEST['panterid']);
            $amount=trim($_REQUEST['amount']);
            $memo=trim($_REQUEST['memo']);
            if(empty($panterid)){
                $this->error('申请机构必选');
            }
            if(empty($brandid)){
                $this->error('卡类型必选');
            }
            if(empty($amount)){
                $this->error('申请数量必填');
            }
            $maxId=M('application')->max('id');
            $id=$maxId+1;
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $model=new Model();
            $sql="INSERT INTO application(id,count,brandid,appdate,apptime,applicant,reviewer,result,librarysw,memo) values ";
            $sql.=" ('{$id}','{$amount}','{$brandid}','{$currentDate}','{$currentTime}','{$panterid}','{$this->userid}',1,0,'{$memo}')";
            if($model->execute($sql)){
                $this->success('保存成功');
            }else{
                $this->success('保存失败');
            }
        }else{
            $this->display();
        }
    }

    //卡片派送编辑
    public function sendEdit(){
        if(IS_POST){
            $id=trim($_REQUEST['id']);
            $brandid=trim($_REQUEST['brandid']);
            $panterid=trim($_REQUEST['panterid']);
            $amount=trim($_REQUEST['amount']);
            $memo=trim($_REQUEST['memo']);
            if(empty($id)){
                $this->error('id缺失');
            }
            if(empty($panterid)){
                $this->error('申请机构必选');
            }
            if(empty($brandid)){
                $this->error('卡类型必选');
            }
            if(empty($amount)){
                $this->error('申请数量必填');
            }
            $data=array('brandid'=>$brandid,'applicant'=>$panterid,'count'=>$amount,'memo'=>$memo);
            $map=array('id'=>$id);
            if(M('application')->where($map)->save($data)){
                $this->success('修改成功');
            }else{
                $this->success('修改失败');
            }
        }else{
            $id=trim($_REQUEST['id']);
            if(empty($id)){
                $this->error('申请流水号缺失');
            }
            $where['id']=$id;
            $appInfo=M('application')->where($where)->find();
            $map=array('panterid'=>$appInfo['applicant']);
            $map1=array('pb.panterid'=>$appInfo['applicant']);
            $panter=M('panters')->where($map)->field('panterid,namechinese pname')->find();
            $brand=M('brand')->alias('b')->join('panter_brands pb on pb.brandid=b.brandid')
                ->where($map1)->field('b.brandid,b.brandname')->select();
            $this->assign('appInfo',$appInfo);
            $this->assign('panter',$panter);
            $this->assign('brand',$brand);
            $this->display();
        }
    }
//接收卡
    public function cardReceive(){
        $id=trim($_REQUEST['id']);
        $panterid=trim($_REQUEST['panterid']);
        $pname=trim($_REQUEST['pname']);
        $start = trim($_REQUEST['startdate']);
        $end = trim($_REQUEST['enddate']);
        if(!empty($id)){
            $where['a.id']=$id;
            $this->assign('id',$id);
        }
        if(!empty($pname)){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
        }
        if(!empty($panterid)){
            $where['p.panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['a.appdate']=array('egt',$startdate);
            $this->assign('startdate',$start);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['a.appdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['a.appdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['a.appdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-01',strtotime(date('Ymd'))));
            $this->assign('enddate',date('Y-m-d',time()));
        }
        $field='a.*,p.namechinese pname';
        $where['a.librarysw']=0;
        $where['a.result']=1;
        $list=D('application')->alias('a')->join('panters p on p.panterid=a.applicant')
            ->where($where)->field($field)->order('id desc')->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function receive(){
        $id=trim($_REQUEST['id']);
        if(empty($id)){
            $this->error('id缺失');
        }
        $where['a.id']=$id;
        $field='a.*,p.namechinese pname,b.brandname';
        $appInfo=M('application')->alias('a')->join('brand b on a.brandid=b.brandid')
            ->join('panters p on p.panterid=a.applicant')->where($where)->field($field)->find();
        if($appInfo==false){
            $this->error('查无对应派送申请记录');
        }
        $this->assign('appInfo',$appInfo);
        $makecardid=trim($_REQUEST['makecardid']);
        $startcardno = trim($_REQUEST['startcardno']);
        $endcardno = trim($_REQUEST['endcardno']);
        if(!empty($makecardid)){
            $where1['makecardid']=$makecardid;
            $this->assign('makecardid',$makecardid);
        }
        if($startcardno!='' && $endcardno==''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            $where1['cardno']=array('egt',$startcardno);
            $this->assign('startcardno',$startcardno);
        }
        if($startcardno=='' && $endcardno!=''){
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where1['cardno'] = array('elt',$endcardno);
            $this->assign('endcardno',$endcardno);
        }
        if($startcardno!='' && $endcardno!=''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where1['cardno']=array(array('egt',$startcardno),array('elt',$endcardno));
            $this->assign('startcardno',$startcardno);
            $this->assign('endcardno',$endcardno);
        }
        if(!empty($where1)){
            $where1['brandid']=$appInfo['brandid'];
            $where1['status']='J';
            $list=D('cards')->where($where1)->field('cardno,makedate,makecardid,status')->select();
            $this->assign('list',$list);
            session('recieveCondition',$where1);
        }
        $this->display();
    }

    public function receiveDo(){
        //2336371953499739279
        $this->setTimeMemory();
        $amount=trim($_REQUEST['amount']);
        $id=trim($_REQUEST['id']);
        if(empty($id)){
            $this->error('申请单编号缺失');
        }
        $where=session('recieveCondition');

        if(!empty($where)){
            $where=session('recieveCondition');
            $list=D('cards')->where($where)->field('cardno,makedate,makecardid,status')->select();
        }
        if(count($list)!=$amount){
            $this->error('卡数量与申请单数量不符');
        }
        $panterid=trim($_REQUEST['panterid']);
        $brandid=trim($_REQUEST['brandid']);
        $model=new Model();
        $model->startTrans();
        $c=0;
        $cardnoStr='';
        foreach($list as $key=>$val){
            $c++;
            $cardnoStr = $cardnoStr . $val['cardno'].',';
            if($c%200==0||$c==count($list)){
                $map['cardno']=array('in', rtrim($cardnoStr, ","));
                $data=array('app_id'=>$id,'panterid'=>$panterid,'status'=>'C');
                if($model->table('cards')->where($map)->save($data)){
                    $cardnoStr='';
                    $res = true;
                }else{
                    $res = false;
                    break;
                }
            }
        }
        if($res){
            $sql="UPDATE application SET librarysw=1 WHERE ID='{$id}'";
            $sql1="update inventory set outcount=outcount+{$amount},balancecount=balancecount-{$amount} where brandid='{$brandid}'";
            if($model->execute($sql)&&$model->execute($sql1)){
                $model->commit();
                session('recieveCondition',null);
                $this->success('第'.$id.'号单接收成功',U('CardStock/cardReceive'),5);
            }else{
                $model->rollback();
                $this->error('第'.$id.'号单接收失败');
            }
        }else{
            $model->rollback();
            $this->error('第'.$id.'号单接收失败');
        }
    }
//商圈发卡管理
    public function cardGroup(){
        $makecardid=trim($_REQUEST['makecardid']);
        $startcardno = trim($_REQUEST['startcardno']);
        $endcardno = trim($_REQUEST['endcardno']);
        if(!empty($makecardid)){
            $where['makecardid']=$makecardid;
            $this->assign('makecardid',$makecardid);
        }
        if($startcardno!='' && $endcardno==''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            $where['cardno']=array('egt',$startcardno);
            $this->assign('startcardno',$startcardno);
        }
        if($startcardno=='' && $endcardno!=''){
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where['cardno'] = array('elt',$endcardno);
            $this->assign('endcardno',$endcardno);
        }
        if($startcardno!='' && $endcardno!=''){
            if(strlen($startcardno)<19){
                $this->error('起始卡号位数不够');
            }
            if(strlen($endcardno)<19){
                $this->error('终止卡号位数不够');
            }
            $where['cardno']=array(array('egt',$startcardno),array('elt',$endcardno));
            $this->assign('startcardno',$startcardno);
            $this->assign('endcardno',$endcardno);
        }
        $where['status']='C';
        $field="c.cardno,c.status,c.makedate,c.maketime,b.brandname,c.brandid,c.makecardid";
        $list=D('cards')->alias('c')->join('brand b on b.brandid=c.brandid')
            ->where($where)->field($field)->select();
        $this->assign('list',$list);
        session('groupCondition',$where);
        $pantergroup=M('pantergroup')->select();
        $this->assign('pantergroup',$pantergroup);
        $this->display();
    }

    public function setGroup(){
        $this->setTimeMemory();
        $groupid=trim($_REQUEST['groupid']);
        if(empty($groupid)||$groupid=='-1'){
            $this->error('请选择商圈');
        }
        $where=session('groupCondition');
        if(empty($where['makecardid'])&&empty($where['cardno'])){
            $this->error('请选择搜索条件查询后在执行');
        }
        $field="c.cardno,c.status,c.makedate,c.maketime,b.brandname,c.brandid,c.makecardid";
        $list=D('cards')->alias('c')->join('brand b on b.brandid=c.brandid')
            ->where($where)->field($field)->select();
        $count=D('cards')->alias('c')->join('brand b on b.brandid=c.brandid')
            ->where($where)->field($field)->count();
        if(empty($list)){
        }
        $model=new Model();
        $c=0;
        $cardnoStr='';
        foreach($list as $key=>$val){
            $c++;
            $cardnoStr=$cardnoStr . $val['cardno'].',';
            if($c%200==0||$c==count($list)){
                $map['cardno']=array('in', rtrim($cardnoStr, ","));
                $data=array('status'=>'N','userflag'=>'N','groupid'=>$groupid,'cardpoint'=>0,'cardinitamount'=>0,'CardBalance'=>0);
                if($model->table('cards')->where($map)->save($data)){
                    $cardnoStr='';
                    $sql="UPDATE inventory SET incount=incount+{$c},cardsw=cardsw-{$c},balancecount=balancecount+{$c}
                    where brandid='{$val['brandid']}'";
                    $sql1=$model->execute($sql);
                    $c='';
                }
                if($sql1){
                    $res = true;
                }else{
                    $res = false;
                    break;
                }
            }
        }
            if($res){
                $model->commit();
                $this->success('发卡成功，成功发卡'.$count.'张');
            }else{
                $model->rollback();
            }
        }
    //卡种管理
    public function cardBrand(){
        $brandid=trim(I('post.brandid'),'');
        $brandname=trim(I('post.brandname'),'');
        $flag=trim(I('post.flag'),'');
        if(!empty($brandid)){
            $where['brandid']=$brandid;
            $this->assign('brandid',$brandid);
        }
        if(!empty($brandname)){
            $where['brandname']=$brandname;
            $this->assign('brandname',$brandname);
        }
        if(!empty($flag)){
            $where['flag']=$flag;
            $this->assign('flag',$flag);
        }
        $brand=M('brand');
        $list=$brand->where($where)->select();
        $this->assign('list',$list);
        $this->display();
    }
    //添加卡种
    public function addBrand(){
        if(IS_POST){
            $brandid=trim(I('post.brandid'),'');
            $brandname=trim(I('post.brandname'),'');
            $discount=trim(I('post.discount',''));
            $rate=trim(I('post.rate',''));
            $flag=trim(I('post.flag',''));
            $date=date('Ymd');
            if(!preg_match('/^\d{4}$/',$brandid)){
                $this->error('卡段编号需为4位数字');
            }
            if(empty($brandname)){
                $this->error('卡类型名称必填');
            }
            $brand=M('brand');
            $map=array('brandid'=>$brandid);
            $list=$brand->where($map)->find();
            if($list!=false){
                $this->error('该卡段已存在');
            }

            //添加新卡种
            $brandSql="INSERT INTO BRAND VALUES('{$brandid}','{$brandname}','{$flag}','{$date}','{$discount}','{$rate}')";
            $brandIf=M('brand')->execute($brandSql);

            //记录卡种库存
            $inventorySql="INSERT INTO INVENTORY VALUES(0,0,0,'{$brandid}',0,0,0,0)";
            $inventoryIf=M('inventory')->execute($inventorySql);

            $map=array('keyname'=>$brandid);
            $c=M('code_generators')->where($map)->find();
            if($c==false){
                $codeGeneratorsSql="INSERT INTO CODE_GENERATORS VALUES('{$brandid}','',1)";
                $codeGeneratorIf=M('code_generators')->execute($codeGeneratorsSql);
            }else{
                $codeGeneratorIf=true;
            }
            //记录卡的编号递增数
            if($brandIf==true&&$codeGeneratorIf==true&&$inventoryIf==true){
                $this->success('卡段添加成功');
            }else{
                $this->error('卡段添加失败');
            }
        }else{
            $this->display();
        }
    }
    //卡种分配
    public function allotBrand(){
        $panterid=trim(I('post.panterid',''));
        $pname=trim(I('post.pname',''));
        $brandname=trim(I('post.brandname',''));
        if(!empty($panterid)){
            $where['p.panterid']=$panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if(!empty($pname)){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if(!empty($brandname)){
            $where['b.brandname']=array('like','%'.$brandname.'%');
            $this->assign('brandname',$brandname);
            $map['brandname']=$brandname;
        }
        $where['p.flag']=array('in','1,3');
        $where['b.flag']=0;
        $field="pb.*,p.namechinese pname,b.brandname";
        $count=M('panter_brands')->alias('pb')->join('panters p on p.panterid=pb.panterid')
            ->join('brand b on b.brandid=pb.brandid')->field($field)->where($where)->count();
        $p=new \Think\Page($count,10);
        $list=M('panter_brands')->alias('pb')->join('panters p on p.panterid=pb.panterid')
            ->join('brand b on b.brandid=pb.brandid')->field($field)->where($where)
            ->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val){
                $p->parameter[$key]=$val;
            }
        }
        $page= $p->show();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }
    //为商户分配卡种
    public function addAllot(){
        if(IS_POST){
            $panterid=trim(I('post.panterid',''));
            $brandid=trim(I('post.brandid',''));
            if(empty($panterid)){
                $this->error('商户必选');
            }
            if(empty($brandid)||$brandid=='-1'){
                $this->error('卡种必选');
            }
            $panter_brands=M('panter_brands');
            $map=array('panterid'=>$panterid,'brandid'=>$brandid);
            $list=M('panter_brands')->where($map)->select();
            if($list!=false){
                $this->error('该商户已分配该卡种');
            }
            $sql="INSERT INTO PANTER_BRANDS VALUES('{$panterid}','{$brandid}','')";
            if($panter_brands->execute($sql)){
                $this->success('分配成功');
            }else{
                $this->success('分配失败');
            }
        }else{
            $where=array('flag'=>0);
            $brand=M('brand')->where($where)->select();
            $this->assign('brand',$brand);
            $this->display();
        }
    }

    public function cardInventory(){
        $conditionArr=array(
            array('panterid'=>'00000227','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000284','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000323','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000324','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000325','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000326','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000327','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000328','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000329','cardkind'=>'6889','cardfee'=>0,'desc'=>'项目排号卡'),
            array('panterid'=>'00000227','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000284','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000323','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000324','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000325','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000326','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000327','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000328','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000329','cardkind'=>'6889','cardfee'=>1,'desc'=>'通宝卡'),
            array('panterid'=>'00000013','cardkind'=>'6688','cardfee'=>1,'desc'=>'酒店民享卡'),
            array('panterid'=>'00000286','cardkind'=>'6886','cardfee'=>0,'desc'=>'一家APP账户卡'),
            array('panterid'=>'00000290','cardkind'=>'6886','cardfee'=>0,'desc'=>'足球APP账户卡'),
            array('panterid'=>'00000509','cardkind'=>'6880','cardfee'=>0,'desc'=>'绿色基地大食堂充值卡'),
            array('panterid'=>'00001252','cardkind'=>'6889','cardfee'=>2,'desc'=>'通宝电子卡'),
        );
        $cards=M('cards');
        $panters=M('panters');
        $list=array();
        foreach($conditionArr as $key=>$val){
            $map=array('status'=>'N','cardkind'=>$val['cardkind'],'panterid'=>$val['panterid']);
            if($val['cardfee']==0){
                $map['_string']=' cardfee is null or cardfee=0';
            }else{
                $map['cardfee']=$val['cardfee'];
            }
            $c=$cards->where($map)->count();
//            echo M('cards')->getLastSql();exit;
            $panter=$panters->where(array('panterid'=>$val['panterid']))->field('namechinese pname')->find();
//            echo M('panters')->getLastSql();exit;
            $d=$val['cardfee']==1?'实体卡':'虚拟卡';
            $l=array('panterid'=>$val['panterid'],'pname'=>$panter['pname'],'c'=>$c,'cardkind'=>$val['cardkind'],'cardfee'=>$d,'desc'=>$val['desc']);
            $list[]=$l;
        }
        $this->assign('list',$list);
        $this->display();
    }
}
