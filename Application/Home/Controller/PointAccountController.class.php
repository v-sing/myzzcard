<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class PointAccountController extends CommonController{
    //至尊积分发行报表
    public function issue(){
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $customid = trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $cardno = trim(I('get.cardno',''));
        $pname  = trim(I('get.pname',''));//商户名称
        $pointAccount=M('point_account');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['pa.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['pa.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['ca.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        //echo $this->panterid;
        if($this->panterid!='FFFFFFFF'){
            $where1['pa.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $where['c.cardkind']=array('neq','6688');
        $field='pa.*,c.cardno,cu.namechinese cuname,p.namechinese pname';
        $count=$pointAccount->alias('pa')->join('panters p on p.panterid=pa.panterid')
            ->join('cards c on c.customid=pa.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 15);
        $list=$pointAccount->alias('pa')->join('panters p on p.panterid=pa.panterid')
            ->join('cards c on c.customid=pa.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('pa.placeddate desc,pa.placedtime desc')->limit($p->firstRow.','.$p->listRows)->select();
        session('issueCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display();
    }

    //发行报表导出
    public function issue_excel(){
        if(isset($_SESSION['issueCon'])){
            $issueCon=session('issueCon');
            foreach($issueCon as $key=>$val){
                $where[$key]=$val;
            }
        }
        $pointAccount=M('point_account');
        $field='pa.*,c.cardno,cu.namechinese cuname,p.namechinese pname';
        $list=$pointAccount->alias('pa')->join('panters p on p.panterid=pa.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('pa.placeddate desc')->select();
        //print_r($list);exit;
        $strlist="会员姓名,卡号,发行积分金额,发行编号,发行商户,发行时间";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($list as $key=>$val) {
            $val['cuname']=iconv("utf-8","gbk",$val['cuname']);
            $val['pname']=iconv("utf-8","gbk",$val['pname']);
            $val['sourceorder']=iconv("utf-8","gbk",$val['sourceorder']);
            $strlist.=$val['cuname'].",".$val['cardno']."\t,".floatval($val['rechargeamount']).",".$val['sourceorder'];
            $strlist.=",".$val['pname'].",".date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']))."\n";
        }
        $filename='至尊积分发行报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //至尊积分消费报表
    public function consume(){
        $pointConsume=M('point_consume');
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $customid = trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $cardno = trim(I('get.cardno',''));
        $issuepname  = trim(I('get.issuepname',''));//发行商户名称
        $consumepname  = trim(I('get.consumepname',''));//受理商户名称
        $status=trim(I('get.status',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['pc.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($issuepname!=''){
            $where['p1.namechinese']=array('like','%'.$issuepname.'%');
            $this->assign('issuepname',$issuepname);
            $map['issuepname']=$issuepname;
        }
        if($consumepname!=''){
            $where['p.namechinese']=array('like','%'.$consumepname.'%');
            $this->assign('consumepname',$consumepname);
            $map['consumepname']=$consumepname;
        }
        if($status!=''&&$status!='-1'){
            $where['pc.status']=$status;
            $this->assign('status',$status);
            $map['status']=$status;
        }
        $this->assign('status',$status);
        if($this->panterid!='FFFFFFFF'){
            $where1['pc.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $where['c.cardkind']=array('neq','6688');
        $field='pc.tradeid,pc.amount,pc.placeddate,pc.placedtime,pc.status,c.cardno,cu.namechinese cuname,';
        $field.='pc.pointconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field.='pa.placeddate issuedate,pa.placedtime issuetime,tw.termposno';
        $count=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on pc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=pc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)->where($where)->count();
        $p=new \Think\Page($count, 15);
        $list=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on pc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=pc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)
            ->order('pc.placeddate desc,pc.placedtime desc')
            ->limit($p->firstRow.','.$p->listRows)->where($where)->select();
        //echo $pointConsume->getLastSql();
        session('consumeCon',$where);
        $c=0;
        foreach($list as $key=>$val){
            if($val['status']==0) $c++;
        }
        if($c==0) $disabled=1;
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('disabled',$disabled);
        $this->assign('list',$list);
        $this->display();
    }

    //积分报表导出
    public function consume_excel(){
        if(isset($_SESSION['consumeCon'])){
            $consumeCon=session('consumeCon');
            foreach($consumeCon as $key=>$val){
                $where[$key]=$val;
            }
        }
        $pointConsume=M('point_consume');
        $field='pc.tradeid,pc.amount,pc.placeddate,pc.placedtime,pc.status,c.cardno,cu.namechinese cuname,';
        $field.='pc.pointconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field.='pa.placeddate issuedate,pa.placedtime issuetime,tw.termposno';
        $list=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)
            ->order('pc.placeddate desc')->where($where)->select();
        $strlist="会员姓名,卡号,消费积分金额,订单编号,消费商户,消费时间,积分发行机构,发行时间,状态";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($list as $key=>$val) {
            if($val['status']==0){
                $status='未结算';
            }elseif($val['status']==1){
                $status='已结算';
            }
            $val['cuname'] = iconv("utf-8","gbk",$val['cuname']);
            $val['consumepname'] = iconv("utf-8","gbk",$val['consumepname']);
            $val['issuepname'] = iconv("utf-8","gbk",$val['issuepname']);
            $status=iconv("utf-8","gbk",$status);
            $strlist.=$val['cuname'].",".$val['cardno']."\t,".floatval($val['amount']).",".$val['tradeid'];
            $strlist.="\t,".$val['consumepname'].",".date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $strlist.=",".$val['issuepname'].",".date('Y-m-d H:i:s',strtotime($val['issuedate'].$val['issuetime']));
            $strlist.=",".$status."\n";
        }
        $filename='至尊积分消费报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //积分结算执行
    public function calculateDo(){
        $model=new Model();
        $consumeId=$_POST['consumeid'];
        $c=0;$caculateAmount=0;
        if(!empty($consumeId)){
            foreach($consumeId as $key=>$val){
                $consumeId=$val;
                $map=array('pc.pointconsumeid'=>$val);
                $consumeInfo=M('point_consume')->alias('pc')->join('point_account pa on pa.pointid=pc.pointid')
                    ->where($map)->field('pc.*,pa.panterid issuepanterid')->find();
                if($consumeInfo==false) continue;

                $model->startTrans();
                $currendDate=date('Ymd');
                $placeddate = date('Ymd',time());
                $placedtime = date('His',time());
                //-------end----------
                $map1=array('consumepanterid'=>$consumeInfo['panterid'],'issuepanterid'=>$consumeInfo['issuepanterid'],'placeddate'=>$currendDate);
                $caculate=M('point_calculate')->where($map1)->find();
                if($caculate==false){
                    $sql="INSERT INTO point_calculate values('{$consumeInfo['panterid']}','{$consumeInfo['issuepanterid']}','{$consumeInfo['amount']}','{$currendDate}')";
                }else{
                    $sql="UPDATE point_calculate SET amount=nvl(amount,0)+{$consumeInfo['amount']} WHERE ";
                    $sql.="consumepanterid='{$consumeInfo['panterid']}' and issuepanterid='{$consumeInfo['issuepanterid']}' and placeddate='{$currendDate}'";
                }
                $calculateIf=$model->execute($sql);
                $map2=array('pointconsumeid'=>$val);
                $data2=array('status'=>1);
                $consumeIf=M('point_consume')->where($map2)->save($data2);
                if($calculateIf==true&&$consumeIf==true){
                    $model->commit();
                    $c++;
                    $caculateAmount+=$consumeInfo['amount'];
                }else{
                    $model->rollback();
                }
            }
            if($c>0){
                $this->success('结算成功'.$c.'条,结算金额'.$caculateAmount);
            }else{
                $this->error('结算失败');
            }
        }else{
            $this->error('未选取结算记录');
        }
    }

    //至尊积分赠送方核销
    public function issuePanters(){
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $issuepname  = trim(I('get.issuepname',''));//发行商户名称
        $subfix='';
        if($start!='' && $end==''){
            $subfix=' where ';
            $startdate = str_replace('-','',$start);
            $subCondition= "  pa.placeddate>='{$startdate}' ";
            $subCondition1= " pc.placeddate>='{$startdate}' ";
            $dateString=$start.'后';
            $this->assign('dateString',$dateString);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $subfix=' where ';
            $enddate = str_replace('-','',$end);
            $subCondition= " pa.placeddate<='{$enddate}' ";
            $subCondition1= "  pc.placeddate<='{$enddate}' ";
            $dateString=$end.'前';
            $this->assign('dateString',$dateString);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $subfix=' where ';
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $subCondition= "  pa.placeddate>='{$startdate}' and pa.placeddate<='{$enddate}' ";
            $subCondition1= " pc.placeddate>='{$startdate}' and pc.placeddate<='{$enddate}' ";
            $dateString=$start.'——'.$end;
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $this->assign('dateString',$dateString);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($issuepname!=''){
            $where['p.namechinese']=array('like','%'.$issuepname.'%');
            $this->assign('issuepname',$issuepname);
            $map['issuepname']=$issuepname;
        }
        if($this->panterid!='FFFFFFFF'){
            $subfix=' where ';
            $subCondition.=empty($subCondition)?" (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')":$subCondition." and (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')";
        }
        if(!empty($subfix)){
            $subCondition=$subfix.$subCondition;
        }
        /*$subQuery="(select sum(rechargeamount) totalamount,panterid from coin_account";
        $subQuery.=$subCondition." group by panterid)";*/
        $subQuery="(select sum(pa.rechargeamount) totalamount,pa.panterid,p.parent from point_account pa inner join panters p on p.panterid=pa.panterid";
        $subQuery.=$subCondition." and p.panterid='00000013' group by pa.panterid,p.parent)";
        $model=new Model();
        $count=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->count();
        $p=new \Think\Page($count, 15);
        $issueList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
            ->limit($p->firstRow.','.$p->listRows)->select();
        // echo M()->getLastSql();exit;
        session('issuePantersCon',array('subCondition'=>$subCondition,'where'=>$where));
        foreach($issueList as $key=>$val){
            $where1['pa.panterid']=$val['panterid'];
            $field='sum(amount) consumeamount';
            $consume=$model->table('point_account')->alias('pa')->join('point_consume pc on pc.pointid=pa.pointid')
                ->where($where1)->field($field)->find();
            $issueList[$key]['consumeamount']=!empty($consume['consumeamount'])?$consume['consumeamount']:0;
            $issueList[$key]['remindamount']=$val['totalamount']-$issueList[$key]['consumeamount'];
        }
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$issueList);
        $this->display();
    }

    //至尊积分赠送方核销报表导出
    public function issuePanters_excel(){
        $issuePantersCon=$_SESSION['issuePantersCon'];
        if(isset($issuePantersCon['where'])){
            $where=$issuePantersCon['where'];
        }
        $subCondition=$issuePantersCon['subCondition'];
        $subQuery="(select sum(rechargeamount) totalamount,panterid from point_account";
        $subQuery.=$subCondition." group by panterid)";
        $model=new Model();
        $issueList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->select();
        $strlist="赠送商户,赠送总金额,已消费金额";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($issueList as $key=>$val) {
            $where1['ca.panterid']=$val['panterid'];
            $field='sum(amount) consumeamount';
            $consume=$model->table('point_account')->alias('pa')->join('point_consume pc on pc.pointid=pa.pointid')
                ->where($where1)->field($field)->find();
            $val['pname'] = iconv("utf-8","gbk",$val['pname']);
            $consumeamount=!empty($consume['consumeamount'])?$consume['consumeamount']:0;
            $strlist.=$val['pname'].",".floatval($val['totalamount'])."\t,".floatval($consumeamount)."\n";
        }
        $filename='赠送机构报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //积分受理方统计
    public function consumePanters(){
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $conpname  = trim(I('get.conpname',''));//发行商户名称
        if($start!='' && $end==''){
            $subfix=" where ";
            $startdate = str_replace('-','',$start);
            $subCondition= "  placeddate>='{$startdate}' ";
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $subfix=" where ";
            $enddate = str_replace('-','',$end);
            $subCondition= "  placeddate<='{$enddate}' ";
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $subfix=" where ";
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $subCondition= " placeddate>='{$startdate}' and placeddate<='{$enddate}' ";
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
//        if($start=='' && $end==''){
//            $start=date('Y-m-01', strtotime('-1 month'));
//            $end=date('Y-m-t', strtotime('-1 month'));
//            $startdate = str_replace('-','',$start);
//            $enddate = str_replace('-','',$end);
//            $subCondition= " where placeddate>='{$startdate}' and placeddate<='{$enddate}' ";
//            $this->assign('startdate',$start);
//            $this->assign('enddate',$end);
//            $map['startdate']=$start;
//            $map['enddate']=$end;
//        }
        if($conpname!=''){
            $where['p.namechinese']=array('like','%'.$conpname.'%');
            $this->assign('conpname',$conpname);
            $map['conpname']=$conpname;
        }
        if($this->panterid!='FFFFFFFF'){
            $subfix=' where ';
            $subCondition.=empty($subCondition)?" (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')":$subCondition." and (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')";
        }
        if(!empty($subfix)){
            $subCondition=$subfix.$subCondition;
        }
        $subQuery="(select sum(pc.amount) totalamount,pc.panterid,p.parent from point_consume pc inner join panters p on p.panterid=pc.panterid";
        $subQuery.=$subCondition." and p.hysx <> '酒店' group by pc.panterid,p.parent)";
        $model=new Model();
        $count=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->count();
        $p=new \Think\Page($count, 15);
        $consumeList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
            ->limit($p->firstRow.','.$p->listRows)->select();
        session('conPantersCon',array('subCondition'=>$subCondition,'where'=>$where));
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$consumeList);
        $this->display();
    }
    //积分受理方统计
    public function consumePanters_excel(){
        $conPantersCon=$_SESSION['conPantersCon'];
        if(isset($conPantersCon['where'])){
            $where=$conPantersCon['where'];
        }
        $subCondition=$conPantersCon['subCondition'];
        $subQuery="(select sum(amount) totalamount,panterid from point_consume";
        $subQuery.=$subCondition." group by panterid)";
        $model=new Model();
        $issueList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->select();
        $strlist="消费商户,消费总金额";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($issueList as $key=>$val) {
            $val['pname'] = iconv("utf-8","gbk",$val['pname']);
            $strlist.=$val['pname'].",".floatval($val['totalamount'])."\t,"."\n";
        }
        $filename='消费报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }
    //积分发行方受理机构统计
    public function issueConsume(){
        $isspanterid=$_REQUEST['isspanterid'];
        if(empty($isspanterid)){
            $this->error('发行机构缺失');
        }
        $map['isspanterid']=$isspanterid;
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $consumepname  = trim(I('get.consumepname',''));//受理商户名称
        $pointAccount=M('point_account');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $subCondition= " and ca.placeddate>='{$startdate}' ";
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $subCondition= " and ca.placeddate<='{$enddate}' ";
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $subCondition= " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
//        if($start=='' && $end==''){
//            $start=date('Y-m-01', strtotime('-1 month'));
//            $end=date('Y-m-t', strtotime('-1 month'));
//            $startdate = str_replace('-','',$start);
//            $enddate = str_replace('-','',$end);
//            $subCondition= " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
//            $this->assign('startdate',$start);
//            $this->assign('enddate',$end);
//            $map['startdate']=$start;
//            $map['enddate']=$end;
//        }
        if($consumepname!=''){
            $where['p.namechinese']=array('like','%'.$consumepname.'%');
            $this->assign('consumepname',$consumepname);
            $map['consumepname']=$consumepname;
        }
        $subQuery="(select sum(pc.amount) consumeamount,pc.panterid pid from point_account pa inner join point_consume pc on pc.pointid=pa.pointid";
        $subQuery.=" where pa.panterid='{$isspanterid}' ".$subCondition." group by pc.panterid)";
        $subQuery1="(select sum(pc.amount) calamount,pc.panterid from  point_account pa inner join point_consume pc on pc.pointid=pa.pointid  ";
        $subQuery1.="where pa.panterid='{$isspanterid}' and  pc.status=1 ".$subCondition." group by pc.panterid)";
        $model=new Model();
        $field="a.consumeamount,a.pid,p.namechinese pname,b.calamount,p.settlebankname,p.settlebankid";
        $count=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 15);
        $consumeList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $where1=array('panterid'=>$isspanterid);
        $panter=$model->table('panters')->field('namechinese pname')->where($where1)->find();
        session('issueconsumeCon',array('subCondition'=>$subCondition,'where'=>$where,'isspanterid'=>$isspanterid,'pname'=>$panter['pname']));
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$consumeList);
        $this->assign('panter',$panter);
        $this->assign('isspanterid',$isspanterid);
        $this->display();
    }

    //积分发行方受理机构统计报表导出
    public function issueConsume_excel(){
        $issueconsumeCon=$_SESSION['issueconsumeCon'];
        if(isset($issueconsumeCon['where'])){
            $where=$issueconsumeCon['where'];
        }
        $subCondition=$issueconsumeCon['subCondition'];
        $isspanterid=$issueconsumeCon['isspanterid'];
        $pname=$issueconsumeCon['pname'];
        $subQuery="(select sum(cc.amount) consumeamount,cc.panterid pid from point_account pa inner join point_consume pc on pc.pointid=pa.pointid";
        $subQuery.=" where pa.panterid='{$isspanterid}' ".$subCondition." group by pc.panterid)";
        $subQuery1="(select sum(cc.amount) calamount,cc.panterid from  point_account pa inner join point_consume pc on pc.pointid=pa.pointid  ";
        $subQuery1.="where pa.panterid='{$isspanterid}' and  pc.status=1 ".$subCondition." group by pc.panterid)";
        $model=new Model();
        $field="a.consumeamount,a.pid,p.namechinese pname,b.calamount,p.settlebankname,p.settlebankid";
        $consumeList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->select();
        $strlist="消费商户,消费总金额,已结算金额,银行支行,银行卡号";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($consumeList as $key=>$val) {
            $val['pname'] = iconv("utf-8","gbk",$val['pname']);
            $val['settlebankname']=iconv("utf-8","gbk",$val['settlebankname']);
            $strlist.=$val['pname'].",".floatval($val['consumeamount']).",".floatval($val['calamount']);
            $strlist.=",".$val['settlebankname'].",".$val['settlebankid']."\t,"."\n";
        }
        $filename=$pname.'赠送积分的消费报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }
    //积分受理方发行机构统计
    public function consumeIssue(){
        $conpanterid=$_REQUEST['conpanterid'];
        if(empty($conpanterid)){
            $this->error('受理机构缺失');
        }
        $map['conpanterid']=$conpanterid;
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $isspname  = trim(I('get.isspname',''));//受理商户名称
        $pointAccount=M('point_account');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $subCondition= " and pc.placeddate>='{$startdate}' ";
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $subCondition= " and pc.placeddate<='{$enddate}' ";
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $subCondition= " and (pc.placeddate>='{$startdate}' and pc.placeddate<='{$enddate}' )";
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
//        if($start=='' && $end==''){
//            $start=date('Y-m-01', strtotime('-1 month'));
//            $end=date('Y-m-t', strtotime('-1 month'));
//            $startdate = str_replace('-','',$start);
//            $enddate = str_replace('-','',$end);
//            $subCondition= " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
//            $this->assign('startdate',$start);
//            $this->assign('enddate',$end);
//            $map['startdate']=$start;
//            $map['enddate']=$end;
//        }
        if($isspname!=''){
            $where['p.namechinese']=array('like','%'.$isspname.'%');
            $this->assign('isspname',$isspname);
            $map['isspname']=$isspname;
        }
        $subQuery="(select sum(pc.amount) consumeamount,pa.panterid pid from point_consume pc inner join point_account pa on pc.pointid=pa.pointid";
        $subQuery.=" where pc.panterid='{$conpanterid}' ".$subCondition." group by pa.panterid)";
        $subQuery1="(select sum(pc.amount) calamount,pa.panterid from  point_consume pc inner join point_account pa on pc.pointid=pa.pointid";
        $subQuery1.=" where pc.panterid='{$conpanterid}' and  pc.status=1 ".$subCondition." group by pa.panterid)";
        $model=new Model();
        $field="a.consumeamount,a.pid,p.namechinese pname,b.calamount";
        $count=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 15);
        $consumeList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        //echo $model->getLastSql();
        $where1=array('panterid'=>$conpanterid);
        $panter=$model->table('panters')->field('namechinese pname')->where($where1)->find();
        session('consumeissueCon',array('subCondition'=>$subCondition,'where'=>$where,'conpanterid'=>$conpanterid,'pname'=>$panter['pname']));
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$consumeList);
        $this->assign('panter',$panter);
        $this->assign('conpanterid',$conpanterid);
        $this->display();
    }

    //积分受理方发行机构统计
    public function consumeIssue_excel(){
        $consumeissueCon=$_SESSION['consumeissueCon'];
        if(isset($issueconsumeCon['where'])){
            $where=$issueconsumeCon['where'];
        }
        $subCondition=$consumeissueCon['subCondition'];
        $conpanterid=$consumeissueCon['conpanterid'];
        $pname=$consumeissueCon['pname'];
        $subQuery="(select sum(pc.amount) consumeamount,pa.panterid pid from point_consume pc inner join point_account pa on pc.pointid=pa.pointid";
        $subQuery.=" where pc.panterid='{$conpanterid}' ".$subCondition." group by pa.panterid)";
        $subQuery1="(select sum(pc.amount) calamount,pa.panterid from  point_consume pc inner join point_account pa on pc.pointid=pa.pointid";
        $subQuery1.=" where pc.panterid='{$conpanterid}' and  pc.status=1 ".$subCondition." group by pa.panterid)";
        $model=new Model();
        $field="a.consumeamount,a.pid,p.namechinese pname,b.calamount";
        $consumeList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join'.$subQuery1.' b on b.panterid=a.pid')
            ->where($where)->field($field)->select();
        //echo $model->getLastSql();exit;
        $strlist="赠送商户,消费金额,已结算金额";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($consumeList as $key=>$val) {
            $val['pname'] = iconv("utf-8","gbk",$val['pname']);
            $strlist.=$val['pname'].",".floatval($val['consumeamount']).",".floatval($val['calamount']).","."\n";
        }
        $filename=$pname.'消费积分的赠送报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //三级消费明细报表
    public function issueConsumeDetail(){
        $isspanterid=$_REQUEST['isspanterid'];
        $conpanterid=$_REQUEST['conpanterid'];
        if(empty($isspanterid)){
            $this->error('发行机构缺失');
        }
        if(empty($conpanterid)){
            $this->error('受理机构缺失');
        }
        $where['pa.panterid']=$isspanterid;
        $where['pc.panterid']=$conpanterid;
        $map['isspanterid']=$isspanterid;
        $map['conpanterid']=$conpanterid;
        $this->assign('isspanterid',$isspanterid);
        $this->assign('conpanterid',$conpanterid);

        $pointConsume=M('point_consume');
        $panters=M('panters');
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $customid = trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $cardno = trim(I('get.cardno',''));
        $status=trim(I('get.status',''));
        $pointAccount=M('point_account');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['pc.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($status!=''&&$status!='-1'){
            $where['pc.status']=$status;
            $map['status']=$status;
            $this->assign('status',$status);
        }else{
            $this->assign('status','-1');
        }
        if($this->panterid!='FFFFFFFF'){
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $field='pc.tradeid,pc.amount,pc.placeddate,pc.placedtime,pc.status,c.cardno,cu.namechinese cuname,';
        $field.='pc.pointconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field.='pa.placeddate issuedate,pa.placedtime issuetime,tw.termposno';
        $count=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on pc.cardid=c.customid')
            ->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')
            ->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=pc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)->where($where)->count();
        $p=new \Think\Page($count, 15);
        $list=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on pc.cardid=c.customid')
            ->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')
            ->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=pc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)->where($where)
            ->order('pc.placeddate desc')->limit($p->firstRow.','.$p->listRows)->select();
        //echo $coinConsume->getLastSql();
        $where1=array('panterid'=>$isspanterid);
        $where2=array('panterid'=>$conpanterid);
        $panter1=$panters->field('namechinese pname')->where($where1)->find();
        $panter2=$panters->field('namechinese pname')->where($where2)->find();
        session('consumeDetailCon',array('where'=>$where,'pname1'=>$panter1['pname'],'pname2'=>$panter2['pname']));
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('panter1',$panter1);
        $this->assign('panter2',$panter2);
        $this->assign('list',$list);
        $this->display();
    }

    //三级消费明细报表导出
    public function issueConsumeDetail_excel(){
        $consumeDetailCon=$_SESSION['consumeDetailCon'];
        if(isset($consumeDetailCon['where'])){
            $where=$consumeDetailCon['where'];
        }
        $pname1=$consumeDetailCon['pname1'];
        $pname2=$consumeDetailCon['pname2'];
        $field='pc.tradeid,pc.amount,pc.placeddate,pc.placedtime,pc.status,c.cardno,cu.namechinese cuname,';
        $field.='pc.pointconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field.='pa.placeddate issuedate,pa.placedtime issuetime,tw.tradeid,tw.termposno';
        $pointConsume=M('point_consume');
        $list=$pointConsume->alias('pc')->join('point_account pa on pc.pointid=pa.pointid')
            ->join('cards c on pc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=pc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=pc.tradeid')
            ->join('panters p1 on p1.panterid=pa.panterid')->field($field)->where($where)
            ->order('pc.placeddate desc')->select();
        $strlist="会员姓名,卡号,消费金额,订单编号,消费,消费时间,赠送商户,赠送时间,状态";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($list as $key=>$val) {
            if($val['status']==0){
                $status='未结算';
            }elseif($val['status']==1){
                $status='已结算';
            }
            $val['cuname'] = iconv("utf-8","gbk",$val['cuname']);
            $val['consumepname'] = iconv("utf-8","gbk",$val['consumepname']);
            $val['issuepname'] = iconv("utf-8","gbk",$val['issuepname']);
            $status = iconv("utf-8","gbk",$status);
            $strlist.=$val['cuname'].",".$val['cardno']."\t,".floatval($val['amount']).",".$val['tradeid'] ;
            $strlist.="\t,".$val['consumepname'].",".date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $strlist.=",".$val['issuepname'].",".date('Y-m-d H:i:s',strtotime($val['issuedate'].$val['issuetime']));
            $strlist.=",".$status."\n";
        }
        $filename='建业通宝受理明细报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //通宝未结算报表
    public function uncalculate(){
        $issuepname  = trim(I('get.issuepname',''));//发行商户名称
        $consumepname  = trim(I('get.consumepname',''));//受理商户名称
        if($issuepname!=''){
            $where['p1.namechinese']=array('like','%'.$issuepname.'%');
            $this->assign('issuepname',$issuepname);
            $map['issuepname']=$issuepname;
        }
        if($consumepname!=''){
            $where['p.namechinese']=array('like','%'.$consumepname.'%');
            $this->assign('consumepname',$consumepname);
            $map['consumepname']=$consumepname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='{$this->panterid}' OR p.parent='{$this->panterid}' OR p1.panterid='{$this->panterid}' OR p1.parent='{$this->panterid}'";
        }
        $where['pa.panterid']=array('neq','00000013');
        $model=new Model();
        $subQuery="(select sum(amount) amount,pc.panterid consumepanterid,pa.panterid issuepanterid from point_consume pc ";
        $subQuery.="inner join point_account pa on pc.pointid=pa.pointid where pc.status=0 group by pc.panterid,pa.panterid)";
        $field='a.*,p.namechinese consumepname,p1.namechinese issuepname';
        $count=$model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)->where($where)->count();
        $p=new \Think\Page($count, 15);
        $list=$model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)->where($where)
            ->limit($p->firstRow.','.$p->listRows)->select();
        session('uncalculateCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display();
    }

    //通宝未结算报表导出
    public function uncalculate_excel(){
        if(isset($_SESSION['uncalculateCon'])){
            $uncalculateCon=session('uncalculateCon');
            foreach($uncalculateCon as $key=>$val){
                $where[$key]=$val;
            }
        }
        $coinConsume=M('point_consume');
        $model=new Model();
        $subQuery="(select sum(amount) amount,pc.panterid consumepanterid,pa.panterid issuepanterid from point_consume pc ";
        $subQuery.="inner join point_account pa on pc.pointid=pa.pointid where pc.status=0 group by pc.panterid,pa.panterid)";
        $field='a.*,p.namechinese consumepname,p1.namechinese issuepname';
        $list=$model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)
            ->where($where)->select();
        $strlist="受理机构,发行机构,受理金额";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($list as $key=>$val) {
            $val['consumepname'] = iconv("utf-8","gbk",$val['consumepname']);
            $val['issuepname'] = iconv("utf-8","gbk",$val['issuepname']);
            $strlist.=$val['consumepname'].",".$val['issuepname']."\t,".floatval($val['amount'])."\n";
        }
        $filename='未受理报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    //通宝已结算报表
    public function calculated(){
        $pointCaculate=M('point_calculate');
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $issuepname  = trim(I('get.issuepname',''));//发行商户名称
        $consumepname  = trim(I('get.consumepname',''));//受理商户名称
        $status=trim(I('get.status',''));
        $pointAccount=M('point_account');
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['pc.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['pc.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($issuepname!=''){
            $where['p1.namechinese']=array('like','%'.$issuepname.'%');
            $this->assign('issuepname',$issuepname);
            $map['issuepname']=$issuepname;
        }
        if($consumepname!=''){
            $where['p.namechinese']=array('like','%'.$consumepname.'%');
            $this->assign('consumepname',$consumepname);
            $map['consumepname']=$consumepname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='{$this->panterid}' OR p.parent='{$this->panterid}' OR p1.panterid='{$this->panterid}' OR p1.parent='{$this->panterid}'";
        }
        $field='pc.*,p.namechinese consumepname,p1.namechinese issuepname';
        $count=$pointCaculate->alias('pc')->join('panters p on p.panterid=pc.consumepanterid')
            ->join('panters p1 on p1.panterid=pc.issuepanterid')->field($field)->where($where)->count();
        $p=new \Think\Page($count, 15);
        $list=$pointCaculate->alias('pc')->join('panters p on p.panterid=pc.consumepanterid')
            ->join('panters p1 on p1.panterid=pc.issuepanterid')
            ->field($field)->where($where)->limit($p->firstRow.','.$p->listRows)->select();
        session('calculatedCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display();
    }

    //通宝已结算报表导出
    public function calculated_excel(){
        if(isset($_SESSION['calculatedCon'])){
            $calculatedCon=session('calculatedCon');
            foreach($calculatedCon as $key=>$val){
                $where[$key]=$val;
            }
        }
        $pointCaculate=M('point_calculate');
        $field='pc.*,p.namechinese consumepname,p1.namechinese issuepname';
        $list=$pointCaculate->alias('pc')->join('panters p on p.panterid=pc.consumepanterid')
            ->join('panters p1 on p1.panterid=pc.issuepanterid')
            ->field($field)->select();
        $strlist="受理机构,发行机构,受理金额,结算时间";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($list as $key=>$val) {
            $val['consumepname'] = iconv("utf-8","gbk",$val['consumepname']);
            $val['issuepname'] = iconv("utf-8","gbk",$val['issuepname']);
            $strlist.=$val['consumepname'].",".$val['issuepname']."\t,".floatval($val['amount']).",";
            $strlist.=date('Y-m-d',strtotime($val['placeddate']))."\n";
        }
        $filename='已受理报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }
}
