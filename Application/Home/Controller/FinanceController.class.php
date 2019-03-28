<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Page;

class FinanceController extends CommonController {
    public function _initialize(){
        parent::_initialize();
    }

    public function index(){
		$this->display();
    }

    /**
     * 售卡报表
     */
	public function sellCard(){
        $where['a.tradeflag']=array('in','0,2');
        //$where['b.description']=array('in',array('后台充值','至尊币购卡','批量购卡'));
        $where['card.cardkind']=array('not in','6882,2081,6880,6666,6997');
        //$where['card.panterid']=array('not in',array('00000286','00000290','00000447'));
        //$where['card.panterid']=array('not in',array('00000447'));
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $panterid = trim(I('get.panterid',''));
        $pname	= trim(I('get.pname',''));
        $cardno	= trim(I('get.cardno',''));
        $username= trim(I('get.username',''));
        $cname=trim(I('get.cname',''));
        $panterid = $panterid=="机构编号"?"":$panterid;
        $pname = $pname=="机构名称"?"":$pname;
        $cardno = $cardno=="卡号"?"":$cardno;
        $username = $username=="操作员"?"":$username;
        $cname=$cname=='会员名称'?"":$cname;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['ca.activedate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['ca.activedate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['ca.activedate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['ca.activedate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($panterid!=''){
            $where['ca.panterid'] = $panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($cardno!=''){
            $where['b.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
            $map['username']=$username;
        }
        if(!empty($cname)){
            $where['c.namechinese']=array('like','%'.$cname.'%');
            $this->assign('cname',$cname);
            $map['cname']=$cname;
        }
		//金额区间
		$s_e_price = $_GET['s_e_price'];
		$this->assign('s_e_price',$s_e_price);
		switch($s_e_price){
			case 1 : $where['b.amount'][]=array('lt',1000);$where['b.amount'][]=array('gt',0);break;
			case 2 : $where['b.amount'][]=array('egt',1000);$where['b.amount'][]=array('lt',3000);break;
			case 3 : $where['b.amount'][]=array('egt',3000);$where['b.amount'][]=array('elt',5000);break;
			case 4 : $where['b.amount'][]=array('gt',5000);break;
			default :break;
		}
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $where['b.amount']=array('gt',0);
        $field='c.customid,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field.='b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,';
        $field.='p.namechinese pname,c.namechinese cname,card.Cardkind,u.username,b.description';

        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field);
        $count = $cardActiveLogs->getSellCount();
		$amount_sum = $cardActiveLogs->getSellAmountSum();

        $p = new \Think\Page($count, 10);

        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field,$p);
        $sell_list = $cardActiveLogs->getSellList();

		if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();

        session('sexcel', $where);			//将查询条件保存到session中，导出EXCEL时候取出来使用
        $this->assign('count',$count);
		$this->assign('amount_sum',$amount_sum['amount_sum']);
        $this->assign('list',$sell_list);
        $this->assign('page',$page);
        $this->display();
    }

    /**
     * 售卡报表导出Excel
     */
    public function sellCard_excel(){
    	$this->setTimeMemory();
        $field = 'c.customid,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username,b.description';
        $where = session('sexcel');

        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field);
        $amount_sum = $cardActiveLogs->getSellAmountSum();
        $sell_list = $cardActiveLogs->getSellList();

        $sellCard_list = array();
        $strlist = "发卡机构编号,发卡机构名称,会员编号,会员名称,卡号,卡号类型编号,激活时间,卡初始金额,操作员,充值类型,备注\n";
        $strlist = $this->changeCode($strlist);
        foreach ($sell_list as $key=>$val) {
        	foreach ($val as $k=>$v){
        		$val[$k] = $this->changeCode($v);
        	}
            $strlist .= $val['panterid'] . "\t" . ',' . $val['pname'] . ',';
            $strlist .= $val['customid'] . "\t" . ',' . $val['cname'] . ',';
            $strlist .= $val['cardno'] . "\t" . ',' . $val['cardkind'] . ',';
            $strlist .= date('Y-m-d H:i:s', strtotime($val['activedate'] . $val['activetime']));
            $strlist .= "\t" . ',' . floatval($val['amount']) . ',' . $val['username'];
            $strlist .= ',' . $val['paymenttype'] .',' . $val['description']."\n";
        }
        $strlist .= ',,,,,,,' . $amount_sum['amount_sum'] . "\t" . ',,' . "\n";
        $filename = '售卡报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist, $filename);
    }

    /**
     * 充值报表
     */
    public function recharge(){
        $field = 'c.customid,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
		$statype = array('R'=>'退卡','S'=>'过期','L'=>'锁定','Y'=>'正常卡');//卡状态
        //if($this->userid=='0000000000000056'){
               $where['a.tradeflag']=1;
        //}
        $where['a.flag']=1;
        $where['card.cardkind']=array('not in','6882,2081,6880,6666,6997,6884');
        //$where['card.panterid']=array('not in',array('00000286','00000290','00000447'));
        //$where['card.panterid']=array('not in',array('00000447'));
        $start = I('get.startdate','');//开始日期
        $end = I('get.enddate','');//结束日期
        $panterid = trim(I('get.panterid',''));//机构编号
        $pname	= trim(I('get.pname',''));//机构名称
        $cardno	= trim(I('get.cardno',''));//卡号
        $username= trim(I('get.username',''));//操作员
        $purchaseid= trim(I('get.purchaseid',''));//充值流水号
        $status = trim(I('get.status',''));//卡状态
        $customid = trim(I('get.customid',''));//会员编号
        $cname = trim(I('get.cname',''));//会员名称
        $cardkind = trim(I('get.cardkind',''));//卡段
        $pname = $pname=="机构名称"?"":$pname;
        $cardno = $cardno=="卡号"?"":$cardno;
        $purchaseid = $purchaseid=="充值流水号"?"":$purchaseid;
        $username = $username=="操作员"?"":$username;
        $customid = $customid=="会员编号"?"":$customid;
        $cname = $cname=="会员名称"?"":$cname;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['b.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['b.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['b.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['b.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($panterid!=''){
            $where['b.panterid'] = $panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($cardno!=''){
            $where['b.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
            $map['username']=$username;
        }
        if($purchaseid!=''){
            $where['b.Card_PurchaseId']=$purchaseid;
            $this->assign('purchaseid',$purchaseid);
            $map['purchaseid']=$purchaseid;
        }
        if($status!=''){
            $where['card.status']=$status;
            $this->assign('status',$status);
            $map['status']=$status;
        }
        if($customid!=''){
            $where['c.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cname!=''){
            $where['c.namechinese']=array('like','%'.$cname.'%');
            $this->assign('cname',$cname);
            $map['cname']=$cname;
        }
        if($cardkind!=''){
            $where['card.cardkind']=$cardkind;
            $this->assign('cardkind',$cardkind);
            $map['cardkind']= $cardkind;
        }
		//金额区间
		$s_e_price=$_GET['s_e_price'];
		$this->assign('s_e_price',$s_e_price);
		switch($s_e_price){
			case 1 : $where['b.amount'][]=array('lt',1000);break;
			case 2 : $where['b.amount'][]=array('egt',1000);$where['b.amount'][]=array('lt',3000);break;
			case 3 : $where['b.amount'][]=array('egt',3000);$where['b.amount'][]=array('elt',5000);break;
			case 4 : $where['b.amount'][]=array('gt',5000);break;
			default :break;
		}
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $where['b.amount']=array('gt',0);
        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field);
        $count = $cardActiveLogs->getRechargeCount();
		$amount_sum = $cardActiveLogs->getRechargeAmountSum();
        $p = new \Think\Page($count, 10);
        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field,$p);
        $recharge_list = $cardActiveLogs->getRechargeList();
		session('rexcel',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('count',$count);
		$this->assign('amount_sum',$amount_sum['amount_sum']);
        $this->assign('list',$recharge_list);
		$this->assign('statype',$statype);
        $this->assign('page',$page);
        $this->display();
    }

    /**
     * 充值报表导出Excel
     */
	public function recharge_excel(){
        $this->setTimeMemory();
        $field = 'c.customid,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
		$statypeArr = array('A'=>'待激活','D'=>'销卡','R'=>'退卡','S'=>'过期','N'=>'新卡','L'=>'锁定','Y'=>'正常卡','W'=>'无卡','C'=>'已出库');//卡状态
        $where = session('rexcel');
        $cardActiveLogs = new \Home\Model\CardActiveLogsModel($where,$field);
        $amount_sum = $cardActiveLogs->getRechargeAmountSum();
        $recharge_list = $cardActiveLogs->getRechargeList();

        $strlist = "发卡机构编号,发卡机构名称,会员编号,会员名称,卡号,卡号类型编号,卡状态,充值流水号,充值时间,充值单流水,充值金额,操作员,备注";
        $strlist = $this->changeCode($strlist);
        $this->changeCode($strlist);
        $strlist .= "\n";
        foreach($recharge_list as $key=>$val){
        	foreach ($val as $k=>$v){
        		if ($k == 'status')
        			$v = $statypeArr[$v];
        		$val[$k] = $this->changeCode($v);
        	}
            $strlist .= $val['panterid'] . "\t," . $val['pname'] . ',';
            $strlist .= $val['customid']."\t," . $val['cname'];
            $strlist .= ',' . $val['cardno'] . "\t," . $val['cardkind'];
            $strlist .= ',' . $val['status'] . ',' . $val['cpurchaseid'];
            $strlist .= "\t," . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
            $strlist .= "\t," . $val['purchaseid'] . "\t," . floatval($val['amount']);
            $strlist .= "," . $val['username'] . ',' . $val['description'] . "\n";
		}

		$filename = '充值报表' . date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        $strlist .= ',,,,,,,,,,' . $amount_sum['amount_sum'] . "\t" . ',,' . "\n";
        $this->load_csv($strlist, $filename);
	}

    /**
     * 余额报表
     */
    public function balance(){
        $model = new Model();
        $start = trim(I('get.startdate',''));//开始日期
        $end = trim(I('get.enddate',''));//结束日期
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//会员名称
        $pname= trim(I('get.pname',''));//商户名称
        $cardno = $cardno=="卡号"?"":$cardno;
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $pname=$pname=="商户名称"?"":$pname;
        if($cardno!=''){
            $where['a.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['b.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['a.cuname']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if(!empty($pname)){
            $where['a.pname']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $subWhere2=' and z.placeddate>='.$startdate;
            $subWhere3=' and e.placeddate>='.$startdate;
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $subWhere2=' and z.placeddate<='.$enddate;
            $subWhere3=' and e.placeddate<='.$enddate;
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $subWhere2=' and z.placeddate>='.$startdate.' and z.placeddate<='.$enddate;
            $subWhere3=' and e.placeddate>='.$startdate.' and e.placeddate<='.$enddate;
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($end!=''){
            $enddate = str_replace('-','',$end);
            $subWhere4=' and z.placeddate<='.$enddate;
            $subWhere5=' and e.placeddate<='.$enddate;
        }else{
            $enddate = date('Ymd',time());
            $subWhere4=' and z.placeddate<='.$enddate;
            $subWhere5=' and e.placeddate<='.$enddate;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['a.panterid']=$this->panterid;
            $where1['a.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
        }
        $where['a.cardkind']=array('not in',array('6882','2081','6880','6666','6997'));
        //$where['a.panterid']=array('not in',array('00000286','00000290','00000447'));
        $where['a.panterid']=array('not in',array('00000447'));
        $where['b.type']='01';
        $where['a.cardbalance'] = array('elt','5000');
        //$where['_string']='(nvl(r.amount,0)-nvl(s.tradeamount,0))>0';
        $field='a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount,';
        $field.='nvl(s.tradeamount,0) total_tradeamount,nvl(r.amount,0) total_amount';
        $subQuery1="(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1.="f.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1.="where h.customid=c.customid and h.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1.="and m.customid=b.customid and c.type='00')";

        $subQuery2="(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2.="where z.flag=1".$subWhere2."  group by z.cardno )";

        $subQuery3="(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3.="trade_wastebooks e where e.flag='0'".$subWhere3." and e.tradetype in ('00','17','21') group by e.cardno )";

        //期末充值总金额
        $subQuery4="(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery4.="where z.flag=1".$subWhere4."  group by z.cardno )";

        //期末消费总金额
        $subQuery5="(select nvl(sum(e.tradeamount),0) tradeamount,e.cardno from ";
        $subQuery5.="trade_wastebooks e where e.flag='0'".$subWhere5." and e.tradetype in ('00','17','21') group by e.cardno )";

        $count = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->join('left join '.$subQuery5.' s on s.cardno=a.cardno')
            ->field($field)->where($where)->count();

		$this->assign('count',$count);
		$amount_sum=$model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->join('left join '.$subQuery5.' s on s.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(cardbalance) a3,sum(nvl(r.amount,0)-nvl(s.tradeamount,0)) a4')
            ->where($where)->find();
        //print_r($amount_sum);
		$this->assign('amount_sum',$amount_sum);

        $p=new \Think\Page($count, 10);
        $card_balance_list=$model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->join('left join '.$subQuery5.' s on s.cardno=a.cardno')
            ->where($where)->field($field)->order('a.cuname asc')->limit($p->firstRow.','.$p->listRows)->select();
        //echo $model->getLastSql();

        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $dateCondition=array('con2'=>$subWhere2,'con3'=>$subWhere3,'con4'=>$subWhere4,'con5'=>$subWhere5);
        session('balexcel',$where);
        session('dateCondition',$dateCondition);
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$card_balance_list);
        $this->display();
    }

    /**
     * 卡余额报表导出Excel
     */
    public function balance_excel(){
        $this->setTimeMemory();
        $model=new Model();
        $balmap=session('balexcel');
        $dateCondition=session('dateCondition');
        $subWhere2=$dateCondition['con2'];
        $subWhere3=$dateCondition['con3'];
        $subWhere4=$dateCondition['con4'];
        $subWhere5=$dateCondition['con5'];
        foreach ($balmap as $key => $value) {
            $where[$key]=$value;
        }
        $field='a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,';
        $field.='nvl(q.amount,0) amount,nvl(s.tradeamount,0) total_tradeamount,nvl(r.amount,0) total_amount';

        $subQuery1="(select h.cardno,h.cardfee,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1.="h.panterid,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1.="where h.customid=c.customid and h.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1.="and m.customid=b.customid and c.type='00')";

        //期间充值统计
        $subQuery2="(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2.="where z.flag=1 ".$subWhere2." group by z.cardno )";

        //期间消费统计
        $subQuery3="(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3.="trade_wastebooks e where e.flag='0' ".$subWhere5."and e.tradetype in ('00','17','21') group by e.cardno )";

        //期末充值总金额
        $subQuery4="(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery4.="where z.flag=1".$subWhere4."  group by z.cardno )";

        //期末消费总金额
        $subQuery5="(select nvl(sum(e.tradeamount),0) tradeamount,e.cardno from ";
        $subQuery5.="trade_wastebooks e where e.flag='0'".$subWhere5." and e.tradetype in ('00','17','21') group by e.cardno )";

        $amount_sum=$model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->join('left join '.$subQuery5.' s on s.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(cardbalance) a3,sum(nvl(r.amount,0)-nvl(s.tradeamount,0)) a4')
            ->where($where)->find();
        //总消费统计
        $card_balance_list=$model->table($subQuery1)->alias('a')
            ->join('__ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->join('left join '.$subQuery5.' s on s.cardno=a.cardno')
            ->where($where)->field($field)->select();
        //echo $model->getLastSql();exit;

        $strlist = "卡号,会员编号,会员名称,会员所属机构,充值金额,交易金额,期末余额,卡余额,卡类型";
        $strlist = iconv('utf-8','gbk',$strlist);
        $strlist .= "\n";
        foreach($card_balance_list as $key=>$val){
        	switch ($val['cardfee']){
		        case '1' : $val['cardfee'] = '实体卡';break;
		        case '0' : $val['cardfee'] = '电子卡';break;
		        case '2' : $val['cardfee'] = '电子卡';break;
		        default  :$val['cardfee']= '实体卡'; break;
	        }
            $val['cuname']=iconv('utf-8','gbk',$val['cuname']);
            $val['pname']=iconv('utf-8','gbk',$val['pname']);
	        $val['cardfee']=iconv('utf-8','gbk',$val['cardfee']);
            $strlist.=$val['cardno']."\t,".$val['customid']."\t,".$val['cuname'];
            $strlist.=','.$val['pname'].','.$val['amount'].','.floatval($val['tradeamount']);
            $strlist.=','.floatval($val['total_amount']-$val['total_tradeamount']);
            $strlist.=",".floatval($val['cardbalance']);
	        $strlist.=",".$val['cardfee']."\n";
        }
        $filename='卡余额报表'.date('YmdHis');
        $strlist.=',,,,'.$amount_sum['a1']."\t,".$amount_sum['a2']."\t,".$amount_sum['a4']."\t,".$amount_sum['a3']."\t,\n";
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist,$filename);
    }

    /**
     * 消费明细报表
     */
    public function consume(){
        $model=new Model();
        $field='t.cardno,t.termposno,t.tradeid,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,t.pretradeid,';
        $field.='t.flag,t.addpoint,t.quanid,p.namechinese pname,p.hysx,custom.namechinese cuname,p1.namechinese pname1,t.eorderid';
        $jytype=array('00'=>'至尊卡消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成','07'=>'退款');
        $hytype=array('餐饮'=>'餐饮','酒店'=>'酒店','服装'=>'服装');
        $where['t.flag']=0;
        $where['t.tradetype']=array('in','00,13,17,21,07,25');
        $where['t.tradeamount']=array('neq',0);
        //$where['card.panterid']=array('not in',array('00000286','00000290','00000447'));
        //$where['card.panterid']=array('not in',array('00000447'));
        $where['card.cardkind']=array('not in','6882,2081,6880,6666,6997,6884');
        $start = I('get.startdate','');//开始日期
        $end = I('get.enddate','');//结束日期
        $panterid = trim(I('get.panterid',''));//商户编号
        $pname  = trim(I('get.pname',''));//商户名称
        $cardno = trim(I('get.cardno',''));//卡号
        $jystatus = trim(I('get.jystatus',''));//交易类型
        $hystatus = trim(I('get.hystatus',''));//所属行业
        //$customid = I('get.customid','');//会员编号
        $cuname = trim(I('get.cuname','')); //会员名称
        $cardkind = trim(I('get.cardkind',''));//卡段
        $cardno = $cardno=="卡号"?"":$cardno;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $panterid = $panterid=="商户编号"?"":$panterid;
        $pname = $pname=="商户名称"?"":$pname;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['t.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['t.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['t.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['t.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }

        if($panterid!=''){
            $where['t.panterid'] = $panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($cardno!=''){
            $where['t.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($jystatus!=''){
            $where['t.tradetype']=$jystatus;
            $this->assign('jystatus',$jystatus);
            $map['jystatus']=$jystatus;
        }else{
            $where['t.tradetype']=array('in','00,13,17,21,07');
        }
        if($hystatus!=''){
            $where['p.hysx']=$hystatus;
            $this->assign('hystatus',$hystatus);
            $map['hystatus']=$hystatus;
        }
        if($cuname!=''){
            $where['custom.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cardkind!=''){
            $where['card.cardkind']=$cardkind;
            $this->assign('cardkind',$cardkind);
            $map['cardkind']= $cardkind;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $count=$model->table('trade_wastebooks')->alias('t')
                ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                ->join('left join __CARDS__ card on card.cardno=t.cardno')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                ->where($where)->field($field)->count();
		$this->assign('count',$count);
		$amount_sum=$model->table('trade_wastebooks')->alias('t')
                ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                ->join('left join __CARDS__ card on card.cardno=t.cardno')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                ->where($where)->field("sum(t.tradeamount) amount_sum")->find();
		$this->assign('amount_sum',$amount_sum['amount_sum']);
        $p=new \Think\Page($count, 10);
        $consume_list=$model->table('trade_wastebooks')->alias('t')
                      ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                      ->join('left join __CARDS__ card on card.cardno=t.cardno')
                      ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                      ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                      ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                      ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
                      ->order("t.placeddate||' '||t.placedtime desc")->select();
        //echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        session('conexcel',$where);
        $this->assign('jytype',$jytype);
        $this->assign('hytype',$hytype);
        $this->assign('page',$page);
        $this->assign('list',$consume_list);
        $this->display();
    }

    /**
     * 消费明细报表导出Excel
     */
    public function consume_excel(){
    	$this->setTimeMemory();
        $field='t.cardno,t.termposno,t.tradeid,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
        $field.='t.flag,t.addpoint,t.quanid,p.namechinese pname,p.hysx,custom.namechinese cuname,custom.customid,p1.namechinese pname1,t.eorderid';
        $jytypeArr=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成','07'=>'退款');
        $conmap=session('conexcel');
        foreach($conmap as $key=>$val){
            $where[$key]=$val;
        }
        $model= new model();
        $amount_sum=$model->table('trade_wastebooks')->alias('t')
                ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                ->join('left join __CARDS__ card on card.cardno=t.cardno')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                ->where($where)->field("sum(t.tradeamount) amount_sum")->find();
        $consume_list=$model->table('trade_wastebooks')->alias('t')
                      ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                      ->join('left join __CARDS__ card on card.cardno=t.cardno')
                      ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                      ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                      ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                      ->where($where)->field($field)->order("t.placeddate||' '||t.placedtime desc")->select();
        $strlist="卡号,会员编号,会员名称,状态,商户编号,商户名称,所属行业,卡属机构,交易时间,终端号,";
        $strlist.="交易金额,交易积分,交易类型,流水号,产生积分,营销劵编号,外部订单号";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach($consume_list as $key=>$val_info){
            $val_info['pname']=iconv('utf-8','gbk',$val_info['pname']);
            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
            $val_info['hysx']=iconv('utf-8','gbk',$val_info['hysx']);
            $val_info['pname1']=iconv('utf-8','gbk',$val_info['pname1']);
            $jytypeArr[$val_info["tradetype"]]=iconv('utf-8','gbk',$jytypeArr[$val_info["tradetype"]]);
            $jytype=iconv('utf-8','gbk',$jytypeArr[$val_info["tradetype"]]);
            $strlist.=$val_info['cardno']."\t,".$val_info['customid'];
            $strlist.="\t,".$val_info['cuname'].','.iconv('utf-8','gbk','交易成功').','.$val_info['panterid'];
            $strlist.="\t,".$val_info['pname'].','.$val_info['hysx'].','.$val_info['pname1'];
            $strlist.=','.date('Y-m-d H:i:s',strtotime($val_info['placeddate'].$val_info['placedtime']))."\t,".$val_info['termposno'];
            $strlist.="\t,".floatval($val_info['tradeamount']).",".$val_info['tradepoint'];
            $strlist.=','.$jytype.','.$val_info['tradeid']."\t,";
            $strlist.=floatval($val_info['addpoint']).','.$val_info['quanid'].','.$val_info['eorderid']."\t\n";
        }
        $filename='消费明细报表'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        $strlist.=',,,,,,,,,,'.$amount_sum['amount_sum']."\t,,,,,\n";
        $this->load_csv($strlist,$filename);
    }

    /**
     * 通宝+消费明细报表
     */
    public function tbconsume(){
        // $rs = $this->https_request('http://test.com/admin/donate/zzcard_consume');
        // print_r($rs);
        $model=new Model();
        // foreach ($rs as $k=>$v) {
        //     $tradeid=substr($v['ACCOUNTID'],1,4).date('YmdHis',time());
        //     $placeddate = date('Ymd',$v['DATETIME']);
        //     $sql='insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,';
        //     $sql.='tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)';
        //     $sql.=" values('00000000','00000000','{$v['PANTERID']}','{$tradeid}','{$placeddate}',";
        //     $sql.="'{$v['']}','0','{$card_info['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0')";
        //     $model->execute($sql);
        // }
        
        $host = '192.168.10.64';
        // $host = '192.168.2.34';
        $dbname = 'jycard';
        $tns = "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=$dbname)));charset=utf8";
        $db_username = "tongbao";
        $db_password = "tongbao_2018";
        // $db_password = "tongbao";
        try{
            $conn = new \PDO("oci:dbname=".$tns,$db_username,$db_password);
        }catch(PDOException $e){
            echo ($e->getMessage());
        }
        
        $jytype=array('1'=>'通宝+消费','5'=>'通宝+退款','6'=>'通宝+交易关闭');
        $hytype=array('餐饮'=>'餐饮','酒店'=>'酒店','服装'=>'服装');
        $where['tc.status'] = array('in','1,5,6');

        $start = I('get.startdate','');//开始日期
        $end = I('get.enddate','');//结束日期
        $panterid = trim(I('get.panterid',''));//商户编号
        $pname  = trim(I('get.pname',''));//商户名称
        $jystatus = trim(I('get.jystatus',''));//交易类型
        $hystatus = trim(I('get.hystatus',''));//所属行业
        $customid = I('get.customid','');//会员编号

        $panterid = $panterid=="商户编号"?"":$panterid;
        $pname = $pname=="商户名称"?"":$pname;
        
        if($start!='' && $end==''){
            $startdate = strtotime(str_replace('-','',$start));
            $where['tc.DATETIME']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = strtotime(str_replace('-','',$end));
            $where['tc.DATETIME'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['tc.DATETIME']=array(array('egt',strtotime($startdate)),array('elt',strtotime($enddate)));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tc.DATETIME']=array(array('egt',strtotime($startdate)),array('elt',strtotime($enddate)));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }

        if($customid!=''){
            $where['tc.customid'] = $customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($panterid!=''){
            $where['tc.panterid'] = $panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($jystatus!=''){
            $where['tc.status']=$jystatus;
            $this->assign('jystatus',$jystatus);
            $map['jystatus']=$jystatus;
        }
        if($hystatus!=''){
            $where['p.hysx']=$hystatus;
            $this->assign('hystatus',$hystatus);
            $map['hystatus']=$hystatus;
        }
        if($cuname!=''){
            $where['p.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        
        // $sql = 'SELECT count(NUM) num,sum(TOTALAMOUNT) amount_sum from t_consume where DATETIME<1545898772';
        $sql = $model->table('t_consume')->alias('tc')
            ->join('left join JYCARD.PANTERS p on tc.panterid=p.panterid')
            ->field('count(NUM) num,sum(TOTALAMOUNT) amount_sum')
            ->where($where)->select(false);
        // echo $sql;
        $sth = $conn->prepare($sql);
        $sth->execute();
        $count = $sth->fetch(\PDO::FETCH_ASSOC);
        // var_dump($count);
        // $field='t.cardno,t.termposno,t.tradeid,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,t.pretradeid,';
        // $field.='t.flag,t.addpoint,t.quanid,p.namechinese pname,p.hysx,custom.namechinese cuname,p1.namechinese pname1,t.eorderid';
        $this->assign('count',$count['NUM']);
		$this->assign('amount_sum',$count['AMOUNT_SUM']);
        $p=new \Think\Page($count['NUM'], 10);
        $sql = $model->table('t_consume')->alias('tc')
            ->join('left join JYCARD.PANTERS p on tc.panterid=p.panterid')
            ->field('tc.*,p.NAMECHINESE pname,p.HYSX')
            ->where($where)->limit($p->firstRow . ',' . $p->listRows)
            ->order("DATETIME desc")->select(false);
            // echo $sql;
        // $sql = 'SELECT * from t_consume where DATETIME<1545898772';
        $sth = $conn->prepare($sql);
        $sth->execute();
        $consume_list = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        // session('conexcel',$where);
        $this->assign('jytype',$jytype);
        $this->assign('hytype',$hytype);
        $this->assign('page',$page);
        $this->assign('list',$consume_list);
        $this->display();
    }

    /**
     * 补卡统计
     */
    public function mendCard(){
        $model=new Model();
        $field='ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $where['ccl.flag']=1;
        $start = I('get.startdate','');//开始时间
        $end = I('get.enddate','');//结束时间
        $customid = trim(I('get.customid',''));//会员编号
        $cuname  = trim(I('get.cuname',''));//会员名称
        $precardno=trim(I('get.precardno',''));//原卡号
        $cardno = trim(I('get.cardno',''));//现卡号
        $username= trim(I('get.username',''));//操作员
        $cardno = $cardno=="现卡号"?"":$cardno;
        $precardno = $precardno=="原卡号"?"":$precardno;
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $username = $username=="操作员"?"":$username;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['ccl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['ccl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['ccl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
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
        if($precardno!=''){
            $where['ccl.precardno']=$precardno;
            $this->assign('precardno',$precardno);
            $map['precardno']=$precardno;
        }
        if($cardno!=''){
            $where['ccl.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
            $map['username']=$username;
        }
        //$where['c.panterid']=array('not in',array('00000286','00000290'));
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where['c.cardkind']=array('not in','6882,2081,6880,6666');
        }

        $count=$model->table('card_change_logs')->alias('ccl')
            ->join('left join __CARDS__ c on ccl.cardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 10);
        $mendCardList=$model->table('card_change_logs')->alias('ccl')
                      ->join('__CARDS__ c on ccl.precardno=c.cardno')
                      ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                      ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                      ->join('__PANTERS__ p on p.panterid=c.panterid')
                      ->join('left join __USERS__ u on u.userid=ccl.userid')
                      ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
                      ->order('ccl.placedDate desc')->select();
        session('menexcel',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$mendCardList);
        $this->display();
    }

    /**
     * 补卡报表导出Excel
     */
    public function mendCard_excel(){
    	$this->setTimeMemory();
        $model=new Model();
        $field='ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $menmap=session('menexcel');
        foreach($menmap as $key=>$val){
            $where[$key]=$val;
        }
        $mendCardList=$model->table('card_change_logs')->alias('ccl')
                      ->join('__CARDS__ c on ccl.precardno=c.cardno')
                      ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                      ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                      ->join('left join __USERS__ u on u.userid=ccl.userid')
                      ->join('__PANTERS__ p on p.panterid=c.panterid')
                      ->where($where)->field($field)->order('ccl.placedDate desc')->select();
        $strlist="卡号,会员编号,会员名称,原卡号,补卡时间,操作员,备注";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach ($mendCardList as $key => $val_info) {
            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
            $val_info['username']=iconv('utf-8','gbk',$val_info['username']);
            $val_info['memo']=iconv('utf-8','gbk',$val_info['memo']);
            $strlist.=$val_info['cardno']."\t,".$val_info['customid']."\t,";
            $strlist.=$val_info['cuname'].','.$val_info['precardno']."\t,";
            $strlist.=date('Y-m-d',strtotime($val_info['placeddate']));
            $strlist.="\t,".$val_info['username'].','.$val_info['memo']."\n";
        }
        $filename='补卡报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($mendCardList);
        $this->load_csv($strlist,$filename);
    }

    /**
     * 商户日结算报表
     */
    public function dailyReport(){
        $model=new model;
        $start = I('get.startdate','');
    	$end = I('get.enddate','');
    	$panterid = trim(I('get.panterid',''));
        $shname = trim(I('get.shname',''));
        $jsname = trim(I('get.jsname',''));
        $panterid = $panterid=="商户编号"?"":$panterid;
        $shname = $shname=="结算商户"?"":$shname;
        $jsname = $jsname=="结算户名"?"":$jsname;
    	if (!empty($start) && empty($end)) {
    		$startdate = str_replace('-','',$start);
    		$where['tp.statdate']=array('egt',$startdate);
    		$this->assign('startdate',$start);
            $map['startdate']=$start;
    	}
    	if(empty($start)&&!empty($end)){
    		$enddate = str_replace('-','',$end);
            $where['tp.statdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
    	}
    	if(!empty($end) && !empty($start)){
    		$startdate = str_replace('-','',$start);
    		$enddate = str_replace('-','',$end);
    		$where['tp.statdate'] = array(array('egt',$startdate),array('elt',$enddate));
    		$this->assign('startdate',$start);
    		$this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
    	}
        if(empty($end) && empty($start)){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tp.statdate'] = array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($panterid)){
            $where['tp.panterid']=$panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
    	if (!empty($shname)){
    		$where['p.namechinese'] = array('like','%'.$shname.'%');
    		$this->assign('shname',$shname);
            $map['shname']=$shname;
    	}
        if(!empty($jsname)){
            $where['p.settleaccountname'] = array('like','%'.$jsname.'%');
            $this->assign('jsname',$jsname);
            $map['jsname']=$jsname;
        }
        $where['_string']="tp.type = '00' or tp.type is null";
        //$where['_string']="p.hysx <> '酒店' or p.hysx is null";
        //$where['p.panterid']=array('not in',array('00000286','00000290'));
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $field='tp.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate';
        $count=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->count();
		$this->assign('count',$count);
		$amount_sum=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
		$this->assign('amount_sum',$amount_sum['amount_sum']);
        $p=new \Think\Page($count, 10);
        $panter_trade_daily_list=$model->table('trade_panter_day_books')->alias('tp')
                                 ->join('__PANTERS__ p on p.panterid=tp.panterid')
                                 ->where($where)->field($field)
                                 ->limit($p->firstRow.','.$p->listRows)
                                 ->order('tp.statdate desc')->select();
        foreach($panter_trade_daily_list as $key=>$val){
            if($val['statdate']<'20160923'){
                $tradeamount=floatval($val['tradeamount']);
                $rate=floatval($val['rate']);
                $fwrate=floatval($val['servicerate']);
                $jsamount=round(floatval($tradeamount*$rate/100),2);
                if($fwrate!=''){
                    $fuamount=round(floatval($tradeamount*$fwrate/100),2);
                }else{
                    $fuamount=0;
                }
                $sxf=round(floatval($tradeamount-$jsamount),2);
                $panter_trade_daily_list[$key]['tradeamount']=$tradeamount;
                $panter_trade_daily_list[$key]['rate']=$rate;
                $panter_trade_daily_list[$key]['jsamount']=$jsamount-$fuamount;
                $panter_trade_daily_list[$key]['fuamount']=$fuamount;
                $panter_trade_daily_list[$key]['sxf']=$sxf;
            }else{
                $tradeamount=bcadd($val['tradeamount'],0,2);
				$fwrate=bcadd(floatval($val['servicerate']),0,2);
                if($fwrate!=''){
                    $fuamount=bcadd(floatval($tradeamount*$fwrate/100),0,2);
                }else{
                    $fuamount=0;
                }
                $tradeamount=bcadd($val['tradeamount'],0,2);
                $rate=bcadd($val['rate'],0,2);
                $jsamount=bcadd($val['retailamount'],0,2);
                $sxf=bcadd($val['proxyamount'],0,2);
                $panter_trade_daily_list[$key]['tradeamount']=$tradeamount;
                $panter_trade_daily_list[$key]['rate']=$rate;
                $panter_trade_daily_list[$key]['jsamount']=bcsub($jsamount,$fuamount,2);
                $panter_trade_daily_list[$key]['fuamount']=$fuamount;
                $panter_trade_daily_list[$key]['sxf']=$sxf;
            }
        }
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        session('dailyReportxcel',$where);
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$panter_trade_daily_list);
        $this->display();
    }

    /**
     * 商户日结算报表导出Excel
     */
    public function dailyReport_excel(){
    	$this->setTimeMemory();
    	$model=new Model();
        if(isset($_SESSION['dailyReportxcel'])){
            $recmap=session('dailyReportxcel');
            foreach($recmap as $key=>$val){
                $where[$key]=$val;
            }
        }
        $amount_sum=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
    	$field='tp.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate';
    	$panterList=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->order('tp.statdate desc')->select();
        $strlist="序号,商户编号,商户名称,结算日期,交易笔数,交易金额,结算积分,";
        $strlist.="结算比率,手续费,服务费,结算金额,结算户名,结算账号,结算银行,结算开户行\n";
        $strlist=iconv('utf-8','gbk',$strlist);
        $fwrate=0;
        $sxfs=0;
        $setamount=0;
        $fuamounts=0;
    	foreach($panterList as $key=>$val){
            $val['pname']=iconv('utf-8','gbk',$val['pname']);
            $val['settleaccountname']=iconv('utf-8','gbk',$val['settleaccountname']);
            $val['settlebank']=iconv('utf-8','gbk',$val['settlebank']);
            $val['settlebankname']=iconv('utf-8','gbk',$val['settlebankname']);
            $tradeamount=floatval($val['tradeamount']);
            $fwrate=round(floatval($val['servicerate']),2);
            $rate=round(floatval($val['rate']),2);
            $jsamount=round(floatval($tradeamount*$rate/100),2);
            //判定结算金额与数据库是否一致若不一致取数据库数据
            $retailamount = floatval($val['retailamount']);
            if($retailamount>0){
                if(bccomp($jsamount,$retailamount,2)!==0){
                    $jsamount = $retailamount;
                }
            }
            //---end
            $sxf=round(floatval($tradeamount-$jsamount),2);
            if($fwrate!=''){
                $fuamount=round(floatval($tradeamount*$fwrate/100),2);
            }else{
                $fuamount=0;
            }
            $fuamounts+=$fuamount;
            $sxfs+=$sxf;
            $setamount+=floatval($jsamount-$fuamount);
            $keys=$key+1;
            $strlist.=$keys.','.$val['panterid']."\t,".$val['pname'].','.date('Y-m-d',strtotime($val['statdate']));
            $strlist.="\t,".$val['tradequantity'].','.$tradeamount.','.$val['tradepoint'].','.$rate."\t,".$sxf;
            $strlist.=",".$fuamount.",".($jsamount-$fuamount).",".$val['settleaccountname'].',';
            $strlist.=$val['settlebankid']."\t,".$val['settlebank'].','.$val['settlebankname']."\n";
    	}
    	$filename='商户日报表'.date("YmdHis");
      $filename = iconv("utf-8","gbk",$filename);
        unset($panterList);
        $strlist.=',,,,,'.$amount_sum['amount_sum'].",,,".$sxfs.",".$fuamounts.",".$setamount.",,,,\n";
        $this->load_csv($strlist,$filename);
    }

    /**
     * 网联商户日结算报表
     */
    public function WdailyReport(){
        $model = new model;
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $panterid = trim(I('get.panterid', ''));
        $shname = trim(I('get.shname', ''));
        $jsname = trim(I('get.jsname', ''));
        $status = trim(I('get.status', ''));
        $id = trim(I('get.id', ''));
        $batchno = trim(I('get.batchno', ''));
        $order_sn = trim(I('get.order_sn', ''));

        if (isset($id) && is_numeric($id)) {
            $where['tp.id'] = $id;
            $this->assign('id', $id);
            $map['id'] = $id;
        }
        if (!empty($batchno)) {
            $where['tp.batchno'] = $batchno;
            $this->assign('batchno', $batchno);
            $map['batchno'] = $batchno;
        }
        if (isset($status) && is_numeric($status)) {
            if ($status == 10) {//已同步
                $where['tp.status'] = array('in', array('2', '3'));
            } else {
                $where['tp.status'] = $status;
            }
            $this->assign('status', $status);
            $map['status'] = $status;
        } else {
            $where['tp.status'] = array('in', array('0', '2', '3'));
        }

    	if (!empty($start) && empty($end)) {
    		$startdate = str_replace('-','',$start);
    		$where['tp.statdate']=array('egt',$startdate);
    		$this->assign('startdate',$start);
            $map['startdate']=$start;
    	}
    	if(empty($start)&&!empty($end)){
    		$enddate = str_replace('-','',$end);
            $where['tp.statdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
    	}
    	if(!empty($end) && !empty($start)){
    		$startdate = str_replace('-','',$start);
    		$enddate = str_replace('-','',$end);
    		$where['tp.statdate'] = array(array('egt',$startdate),array('elt',$enddate));
    		$this->assign('startdate',$start);
    		$this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
    	}
        if(empty($end) && empty($start)){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tp.statdate'] = array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($panterid)){
            $where['tp.panterid']=$panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
    	if (!empty($shname)){
    		$where['p.namechinese'] = array('like','%'.$shname.'%');
    		$this->assign('shname',$shname);
            $map['shname']=$shname;
    	}
        if(!empty($jsname)){
            $where['p.settleaccountname'] = array('like','%'.$jsname.'%');
            $this->assign('jsname',$jsname);
            $map['jsname']=$jsname;
        }
        
        if (!empty($order_sn)) {
            $o_sn = explode('.', $order_sn);
            $where = ['tp.panterid' => $o_sn[0], 'tp.statdate' => $o_sn[1], 'tp.termposno' => $o_sn[2], 'tp.id' => $o_sn[3]];
            $this->assign('order_sn', $order_sn);
            $map['order_sn'] = $order_sn;
        }
        //$where['_string']="p.hysx <> '酒店' or p.hysx is null";
        //$where['p.panterid']=array('not in',array('00000286','00000290'));
        
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $field='tp.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate';
        $count=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->count();
		$this->assign('count',$count);
		$amount_sum=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
		$this->assign('amount_sum',$amount_sum['amount_sum']);
        $p=new \Think\Page($count, 10);
        $panter_trade_daily_list=$model->table('trade_panter_day_books')->alias('tp')
                                 ->join('__PANTERS__ p on p.panterid=tp.panterid')
                                 ->where($where)->field($field)
                                 ->limit($p->firstRow.','.$p->listRows)
                                 ->order('tp.statdate desc')->select();
        foreach($panter_trade_daily_list as $key=>$val){
            if($val['statdate']<'20160923'){
                $tradeamount=floatval($val['tradeamount']);
                $rate=floatval($val['rate']);
                $fwrate=floatval($val['servicerate']);
                $jsamount=round(floatval($tradeamount*$rate/100),2);
                if($fwrate!=''){
                    $fuamount=round(floatval($tradeamount*$fwrate/100),2);
                }else{
                    $fuamount=0;
                }
                $sxf=round(floatval($tradeamount-$jsamount),2);
                $panter_trade_daily_list[$key]['tradeamount']=$tradeamount;
                $panter_trade_daily_list[$key]['rate']=$rate;
                $panter_trade_daily_list[$key]['jsamount']=$jsamount-$fuamount;
                $panter_trade_daily_list[$key]['fuamount']=$fuamount;
                $panter_trade_daily_list[$key]['sxf']=$sxf;
            }else{
                $tradeamount=bcadd($val['tradeamount'],0,2);
				$fwrate=bcadd(floatval($val['servicerate']),0,2);
                if($fwrate!=''){
                    $fuamount=bcadd(floatval($tradeamount*$fwrate/100),0,2);
                }else{
                    $fuamount=0;
                }
                $tradeamount=bcadd($val['tradeamount'],0,2);
                $rate=bcadd($val['rate'],0,2);
                $jsamount=bcadd($val['retailamount'],0,2);
                $sxf=bcadd($val['proxyamount'],0,2);
                $panter_trade_daily_list[$key]['tradeamount']=$tradeamount;
                $panter_trade_daily_list[$key]['rate']=$rate;
                $panter_trade_daily_list[$key]['jsamount']=bcsub($jsamount,$fuamount,2);
                $panter_trade_daily_list[$key]['fuamount']=$fuamount;
                $panter_trade_daily_list[$key]['sxf']=$sxf;
            }
        }
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        session('dailyReportxcel',$where);
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$panter_trade_daily_list);
        $this->display();
    }

     /*
     * 查询结算信息 封装后同步银企直联
     * 前台页面post panterid.placeddate
     * @return array
     */
    public function getDaliyInfo1()
    {
        $field = 'p.ConPerMobNo,p.ConPerTeleNo,p.GoingTeleNo,tp.retailamount,p.namechinese,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,tp.status,tp.tradeamount,tp.rakerate';
        $order = trim(I('post.o_id', ''));
        $batchno = trim(I('post.batchno', ''));
        $status = trim(I('post.status', ''));
        if ($order == '') {
            exit(json_encode(['code' => '3', 'msg' => '缺失订单号']));
        }
        if ($batchno == '') {
            if ($status == 2) {
                $batchno = 'kxt_wl_'.date('Ymd').'_'.session('username');
            } else if ($status == 3) {
                $batchno = 'kxt_yl_'.date('Ymd').'_'.session('username');
            }
        } else {
            $batchno = $batchno.'_'.session('username');
        }
        if ($status == 2) {
            $trxsmmry = '卡系统网联结算';
            $type = 1;
        } else if ($status == 3) {
            $trxsmmry = '卡系统银联结算';
            $type = 2;
        }
        $this->writeLogs("WdailyReport",date("Y-m-d H:i:s") . "-记录起始--{$trxsmmry}\n\t订单号：" . serialize($order).' 同步到哪：'. serialize($status).' 批次号：'. serialize($batchno) . "\n\t");

        $where = explode('.', $order);
        $map = ['tp.panterid' => $where[0], 'tp.statdate' => $where[1], 'tp.termposno' => $where[2], 'tp.id' => $where[3]];
        $list = M('trade_panter_day_books')->alias('tp')
            ->join("left join __PANTERS__ p on p.panterid=tp.panterid")
            ->where($map)->field($field)->find();

        $this->writeLogs("WdailyReport","查询的数据：\n\t" . serialize($list) . "\n\t");

        if ($list == false) {
            exit(json_encode(['code' => '5', 'msg' => '订单查询失败']));
        }
        if ($list['status'] == '1') {
            exit(json_encode(['code' => '4', 'msg' => '订单已经同步']));
        }
        if ($list['status'] == '2') {
            exit(json_encode(['code' => '4', 'msg' => '订单已经同步']));
        }
        if ($list['status'] == '3') {
            exit(json_encode(['code' => '4', 'msg' => '订单已经同步']));
        }
        if (is_null($list['retailamount'])) {
            if ($list['rakerate'] == 100) {
                $list['retailamount'] = $list['tradeamount'];
            } else {
                $rate = bcdiv($list['rakerate'], 100, 2);
                $list['retailamount'] = bcmul($list['tradeamount'], $rate, 2);
            }
        }

        $rsa = md5(rand(1000000, 999999999));//得到aes对称秘钥  可以是随机的
        $aes = new \Home\Controller\AesController($rsa);
        //加密数据
        $data = [
            'batchno' => $batchno,
            'trxsmmry' => $trxsmmry,
            'trxustrd' => $order,
            'user_phone' => $list['conpermobno'],
            'bankname' => $list['settlebank'],
            'pyeebknm' => $list['settleaccountname'],
            'pyeebkno' => $list['settlebankid'],
            'biztp' => '140001',
            'trxprps' => '0004',
            'cause' => '1',
            'posttime' => time(),
            'affiliation' => $list['namechinese'],
            'trxamt' => bcsub((float)$list['retailamount'],0,2),
            'type' => $type,
            'settleMethod' => '00'
        ];
        $arr = [];
        foreach($data as $k=>$v) {
            $arr[$k] = $aes->encrypt($v);
        }
        $this->writeLogs("WdailyReport","请求的数据：\n\t" . serialize($data) . "\n\t");
        $this->writeLogs("WdailyReport","请求的数据：\n\t" . serialize($arr) . "\n\t");
        $arr['signature'] = $this->pubyRas('1205472369' . $rsa);//使用银联公钥进行加密
        $arr['rsa'] = $this->pubwRas($rsa);//用网联公钥 对aes对称秘钥进行加密
        // $data = json_encode($arr);
        //使用post 进行数据传输
        //注:接口中新添加两个字段 A:type(1:网联 2:银联)  B:rsa
        $rs = $this->https_request('https://123.161.209.66:8443/admin/api/transfer', $arr);
        // $rs = $this->https_request('https://192.168.2.4/admin/api/transfer', $arr);
        $rs = json_decode($rs, true);
        if($rs['status'] == 2) {
            $map = ['panterid' => $where[0], 'statdate' => $where[1], 'termposno' => $where[2], 'id' => $where[3]];
            $save_data = ['status' => $status, 'batchno' => $batchno, 'code' => $rs['code']];
            $list = M('trade_panter_day_books')->where($map)->save($save_data);
            $this->writeLogs("WdailyReport","返回的信息：" . serialize($rs) . "\n\t");
            $this->writeLogs("WdailyReport","修改结算表的状态：" . serialize($list).'-修改的数据：-'. serialize($save_data) . "\n\t\n\t");
            if ($list == true) {
                exit(json_encode(['code' => '1', 'msg' => '结算状态修改成功']));
            } else {
                exit(json_encode(['code' => '0', 'msg' => '结算状态修改失败']));
            }
            exit(json_encode(['code' => '1', 'msg' => $rs['msg']]));
        }
        $rs['msg'] = empty($rs['msg']) ? '银网联无返回值' : $rs['msg'];
        $this->writeLogs("WdailyReport","返回的信息：" . serialize($rs) . "\n\t\n\t");
        exit(json_encode(['code' => '0', 'msg' => $rs['msg']]));
    }

    /**
     * 退卡统计
     */
    public function returnCard(){
        if($_REQUEST['back']==1){
            $this->assign('back',1);
        }
        $model=new Model();
        $field='rcl.*,cu.customid,cu.namechinese cuname,u.username';
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $customid = trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $cardno = trim(I('get.cardno',''));
        $username= trim(I('get.username',''));
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $cardno = $cardno=="卡号"?"":$cardno;
        $username = $username=="操作员"?"":$username;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['rcl.rtdate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['rcl.rtdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['rcl.rtdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
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
        if($cardno!=''){
            $where['rcl.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
            $map['username']=$username;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $count=$model->table('return_cards_log')->alias('rcl')
               ->join('__CARDS__ c on rcl.cardno=c.cardno')
               ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
               ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
               ->join('__PANTERS__ p on p.panterid=c.panterid')
               ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
               ->field($field)->count();
        $p=new \Think\Page($count, 10);
        $destory_list=$model->table('return_cards_log')->alias('rcl')
                      ->join('__CARDS__ c on rcl.cardno=c.cardno')
                      ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
                      ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
                      ->join('__USERS__ u on u.userid=rcl.operid')
                      ->join('__PANTERS__ p on p.panterid=c.panterid')
                      ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
                      ->order('rcl.rtdate desc')->select();
        session('recardexcel',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$destory_list);
        $this->display();
    }

    /**
     * 退卡统计导出Excel
     */
    public function returnCard_excel(){
    	$this->setTimeMemory();
        $model=new Model();
        $field='rcl.*,c.customid,cu.namechinese cuname,u.username';
        if(isset($_SESSION['recardexcel'])){
            $recmap=session('recardexcel');
            foreach($recmap as $key=>$val){
                $where[$key]=$val;
            }
        }
        $amount_sum=$model->table('return_cards_log')->alias('rcl')
               ->join('__CARDS__ c on rcl.cardno=c.cardno')
               ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
               ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
               ->join('__PANTERS__ p on p.panterid=c.panterid')
               ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
               ->field('sum(rcl.cardbalance) balance_sum,sum(rcl.cardbalance1) balance1_sum')->find();
        $ralist=$model->table('return_cards_log')->alias('rcl')
               ->join('__CARDS__ c on rcl.cardno=c.cardno')
               ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
               ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
               ->join('__PANTERS__ p on p.panterid=c.panterid')
               ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
               ->field($field)->order('rcl.rtdate desc')->select();
        $strlist="卡号,会员编号,会员名称,退卡时间,卡余额,通用积分,实退金额,操作员,备注";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach ($ralist as $key => $val_info) {
            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
            $val_info['username']=iconv('utf-8','gbk',$val_info['username']);
            $strlist.=$val_info['cardno']."\t,".$val_info['customid']."\t,".$val_info['cuname'];
            $strlist.="\t,".date('Y-m-d H:i:s',strtotime($val_info['rtdate'].$val_info['rttime']));
            $strlist.="\t,".floatval($val_info['cardbalance']).",".floatval($val_info['cardbalance1']).",";
            $strlist.=floatval($val_info['amount']).','.$val_info['username'].',';
            $strlist.=$val_info['description']."\n";
        }
        $filename='退卡统计'.date('YmdHis');
        unset($ralist);
        $strlist.=',,,,'.$amount_sum['balance_sum']."\t,".$amount_sum['balance1_sum']."\t,,,\n";
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist,$filename);
    }

    /**
     * 卡激活报表
     */
    public function activeCard(){
        $model=new Model();
        $field='cal.*,cu.customid cuid,cu.namechinese cuname,u.username';
        //cal.userid,cal.activedate,cal.cardno,cal.panterid,cal.activetime
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $customid = trim(I('get.customid',''));
        $cuname  = trim(I('get.cuname',''));
        $cardno = trim(I('get.cardno',''));
        $username= trim(I('get.username',''));
        $customid = $customid=="会员编号"?"":$customid;
        $cardno = $cardno=="卡号"?"":$cardno;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $username = $username=="操作员"?"":$username;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cal.activedate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cal.activedate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cal.activedate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($customid!=''){
            $where['cu.customid'] = $customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }else{
            $where['cu.customid'] = array('neq','null');
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cardno!=''){
            $where['cal.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }else{
            $where['cal.cardno']=array('neq','null');
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
            $this->assign('username',$username);
            $map['username']=$username;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $where['c.cardkind']=array('not in','6882,6880,2081,6666');
        $count=$model->table('Card_Active_Logs')->alias('cal')
                ->join('__CARDS__ c on c.cardno=cal.cardno')
                ->join('__PANTERS__ p on p.panterid=c.panterid')
                ->join('left join __USERS__ u on cal.userid=u.userid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 10);
        $activeCard_list=$model->table('Card_Active_Logs')->alias('cal')
                         ->join('left join __CARDS__ c on c.cardno=cal.cardno')
                         ->join('__PANTERS__ p on p.panterid=c.panterid')
                         ->join('left join __USERS__ u on cal.userid=u.userid')
                         ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                         ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')->where($where)
                         ->field($field)->limit($p->firstRow.','.$p->listRows)
                         ->order("cal.activedate||' '||cal.activetime asc")->select();
        session('activeexcel',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$activeCard_list);
        $this->display();
    }

    /**
     * 卡激活报表导出Excel
     */
    public function activeCard_excel(){
    	$this->setTimeMemory();
        $model=new Model();
        $field='cal.*,cu.customid cuid,cu.namechinese cuname,u.username';
        $actmap=session('activeexcel');
        foreach ($actmap as $key => $val) {
            $where[$key]=$val;
        }
        $activeCard_list=$model->table('Card_Active_Logs')->alias('cal')
                         ->join('left join __CARDS__ c on c.cardno=cal.cardno')
                         ->join('left join __USERS__ u on cal.userid=u.userid')
                         ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                         ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                         ->join('__PANTERS__ p on p.panterid=c.panterid')
                         ->where($where)->field($field)->order('cal.activedate')->select();
        $strlist="卡号,会员编号,会员名称,激活时间,操作员";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach ($activeCard_list as $key => $val_info) {
            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
            $val_info['username']=iconv('utf-8','gbk',$val_info['username']);
            $strlist.=$val_info['cardno']."\t,".$val_info['cuid']."\t,".$val_info['cuname'];
            $strlist.=','.date('Y-m-d H:i:s',strtotime($val_info['activedate'].$val_info['activetime']));
            $strlist.="\t,".$val_info['username']."\n";
        }
        $filename = iconv("utf-8","gbk",$filename);
        $filename='卡激活报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist,$filename);
    }

    public function edit(){
        $this->display();
    }

    public function add(){
        $this->display();
    }

    /**
     * 异地消费报表
     */
    public function interConsume(){
        $model=new model();
        if($this->panterid!='FFFFFFFF'){
            $where1['p1.panterid']=$this->panterid;
            $where1['p1.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        $where['_string']="c.panterid!=tw.panterid and c.panterid!=p.parent and (p.hysx <> '酒店' or p.hysx is null) and p.parent!='00000483'";
        $where['tw.flag']=0;
        $where['tw.tradetype']=array('in','00,02,13,17,21');
        $start = I('get.startdate','');//开始日期
        $end = I('get.enddate','');//结束日期
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//会员名称
        $panterid=trim(I('get.panterid',''));//商户编号
        $pantername=trim(I('get.pantername',''));//商户名称
        $tradeid=trim(I('get.tradeid',''));//交易编号
        $hysx=trim(I('get.hysx',''));//行业属性
        $cardno = $cardno=="卡号"?"":$cardno;
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $panterid = $panterid=="商户编号"?"":$panterid;
        $pantername = $pantername=="商户名称"?"":$pantername;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['tw.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($cardno!=''){
            $where['tw.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($panterid!=''){
            $where['tw.panterid']=$panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        if($pantername!=''){
            $where['p.namechinese']=array("like","%".$pantername."%");
            $this->assign('pantername',$pantername);
            $map['pantername']=$pantername;
        }
        if($tradeid!=''){
            $where['tw.tradeid']=$tradeid;
            $this->assign('tradeid',$tradeid);
            $map['tradeid']=$tradeid;
        }
        if($hysx!=''){
            $where['p.hysx']=$hysx;
            $this->assign('hysx',$hysx);
            $map['hysx']=$hysx;
        }
        $field='tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.customid cuid';
        $count=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)
            ->where($where)->count();

		$this->assign('count',$count);
		$amount_sum=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field('sum(tradeamount) amount_sum')
            ->where($where)->find();
		$this->assign('amount_sum',$amount_sum['amount_sum']);

        $p=new \Think\Page($count, 10);
        $interConsume=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->limit($p->firstRow.','.$p->listRows)->order('tw.placeddate')->select();
        //echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        session('intConexcel',$where);
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$interConsume);
        $this->display();
    }

    /**
     * 异地消费报表导出
     */
    public function iterConsume_excel(){
    	$this->setTimeMemory();
        $model=new Model();
        $intConMap=session('intConexcel');
        foreach ($intConMap as $key => $value) {
            $where[$key]=$value;
        }
        $field='tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.customid cuid';
        $interConsume=$model->table('trade_wastebooks')->alias('tw')
            ->join('left join __CARDS__ c on c.cardno=tw.cardno')
            ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->order('tw.placeddate')->select();
        $strlist="卡号,会员编号,会员名称,交易号,交易时间,交易金额,交易积分,交易劵,产生积分,卡归属商户,消费商户";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach($interConsume as $key=>$val){
            $val['cuname']=iconv('utf-8','gbk',$val['cuname']);
            $val['pname1']=iconv('utf-8','gbk',$val['pname1']);
            $val['pname']=iconv('utf-8','gbk',$val['pname']);
            $strlist.=$val['cardno']."\t,".$val['cuid']."\t,".$val['cuname'];
            $strlist.=','.$val['tradeid']."\t,".date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $strlist.="\t,".floatval($val['tradeamount']).",".$val['tradepoint'].','.$val['quanid'];
            $strlist.="\t,".$val['addpoint'].','.$val['pname1'].','.$val['pname']."\n";
        }
        $filename='异地消费报表'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist,$filename);
    }
    /*
     * 查询结算信息 封装后同步银企直联
     * 前台页面post panterid.placeddate
     * @return array
     */
    public function getDaliyInfo(){
      $field='tp.retailamount,p.namechinese,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,tp.status,tp.tradeamount,tp.rakerate';
      $order=trim(I('post.o_id',''));
      $batchno=trim(I('post.batchno',''));
      $account_type=trim(I('post.account_type',''));
      if($order==''){
            echo json_encode(['code'=>'3','msg'=>'缺失订单号']);exit;
      }
      if($batchno==''){
         echo json_encode(['code'=>'2','msg'=>'缺失订单号']);exit;
      }
      if($account_type==''){
            echo json_encode(['code'=>'6','msg'=>'缺失打款账户']);exit;
      }
      $arr=explode('.',$order);


      $map=['tp.panterid'=>$arr[0],'tp.statdate'=>$arr[1],'tp.termposno'=>$arr[2]];

      $list=M('trade_panter_day_books')->alias('tp')
                                       ->join("left join __PANTERS__ p on p.panterid=tp.panterid")
                                       ->where($map)->field($field)->find();

      if($list==false){
          exit(json_encode(['code'=>'5','msg'=>'订单查询失败']));
      }
      if($list['status']=='1'){
          exit(json_encode(['code'=>'4','msg'=>'订单已经同步']));
      }
      if($list['status']=='2'){
        exit(json_encode(['code'=>'4','msg'=>'订单已经同步']));
      }
      if($list['status']=='3'){
        exit(json_encode(['code'=>'4','msg'=>'订单已经同步']));
      }

      if(is_null($list['retailamount'])){
        if($list['rakerate']==100){
            $list['retailamount'] = $list['tradeamount'];
        }else{
            $rate = bcdiv($list['rakerate'], 100,2);
            $list['retailamount'] = bcmul($list['tradeamount'], $rate,2);
        }
      }


      $list['batchno'] = $batchno;
      $list['time'] = time();
      $list['account_type'] = $account_type;
      exit(json_encode($list));
    }
    /*
     * 同步银企直联 成功后修改 结算stauts 为零
     */
   public function confirm(){
       $order=trim(I('post.o_id',''));
       $status=trim(I('post.status',''));
       if(empty($status)){
            $status = 1;
       } else {
            $status = 2;
       }
       if(empty($order)){
           echo json_encode(['code'=>'2','msg'=>'缺失订单号']);exit;
       }
       $arr=explode('.',$order);
       $map=['panterid'=>$arr[0],'statdate'=>$arr[1],'termposno'=>$arr[2]];
       $list=M('trade_panter_day_books')->where($map)->save(['status'=>$status]);
       if($list==true){
           exit(json_encode(['code'=>'1','msg'=>'结算状态修改成功']));
       }else{
           exit(json_encode(['code'=>'3','msg'=>'结算状态修失败']));
       }
   }
   //--------------------------------------------------------华联外拓通宝结算以及对账模块--------------------------
    /*
     *author wanqk
     *@  outDaliyTrade  商户每日交易合计
     *@  outDaliyEexcel 下载商户每日交易详情(包含通宝)
     *@  outDaliyJycoin  商户每日交易统计(同步银企直联用)
     *@  outJycoinSucceess
     */
    public function outDaliyTrade(){
        $start    = I('get.startdate','');
        $end      = I('get.enddate','');
        $panter = $this->panterid;
        if($start==''){
            $start = date('Y-m-d',time()-86400);
        }
        $map['tl.placeddate'] =['egt',str_replace('-','',$start)];
        if($end!=''){
            $map['tl.placeddate'] =[['egt',str_replace('-','',$start)],['elt',str_replace('-','',$end)]];
        }
        if($panter!='FFFFFFFF'){
            $map['tl.panterid'] = $panter;
        }else{
            $panterid = trim(I('get.panterid',''));
            $pname    = trim(I('get.pname',''));
            if($panterid!=''){
                $map['tl.panterid'] = $panterid;
                $this->assign('panterid',$panterid);
            }
            if($pname!=''){
                $map['p.namechinese'] = ['like','%'.$pname.'%'];
                $this->assign('pname',$pname);
            }
            $this->assign('is_admin','1');
        }
        //
        $count  = M('out_daliy_total')->alias('tl')
                                     ->join('left join panters p on p.panterid=tl.panterid')
                                     ->where($map)->count();
        $sum  = M('out_daliy_total')->alias('tl')
                                    ->join('left join panters p on p.panterid=tl.panterid')
                                    ->where($map)->field('nvl(sum(tradeamount),0) sum')->find();
        $p      = new Page($count, 10);
        $field  = 'tl.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate';
        $jycoin = M('out_daliy_total')->alias('tl')
                                      ->join('left join panters p on p.panterid=tl.panterid')
                                      ->where($map)->field($field)->limit($p->firstRow,$p->listRows)->select();

        foreach ($jycoin  as $key=>$val){
        	$val['tradeamount']  =  bcadd($val['tradeamount'],0.00,2);
	        $val['settleamount'] =  bcadd($val['settleamount'],0.00,2);
	        $val['poundage']     =  bcadd($val['poundage'],0.00,2);

	        $jycoin[$key] = $val;

        }
        $this->assign('show',$p->show());
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('jycoin',$jycoin);
        $this->assign('sum',$sum);
        $this->display('outTotal');
    }
    public function outDaliyEexcel(){
        $placeddate = trim(I('get.placeddate',''));
        $panterid   = trim(I('get.panterid',''));
        if($this->panterid=='FFFFFFFF'||$this->panterid==$panterid){
            $field = 'tw.panterid,tw.placeddate,tw.placedtime,tw.tradepoint,tw.tradeamount,p.namechinese,tw.tradeid';
            $map['tw.placeddate'] = $placeddate;
            $map['tw.panterid']   = $panterid;
            $map['tw.flag']       = '0';
            $excel = M('trade_wastebooks')->alias('tw')
                                   ->join('left join panters p on p.panterid=tw.panterid')
                                   ->field($field)->where($map)->order('tw.placedtime asc')->select();
            if($excel){
                $headArray = ['商户号','交易流水号','商户名','至尊余额','建业通宝','交易时间'];
                $setCells  = ['A','B','C','D','E','F'];
                $setWidth  = [20,30,30,15,15,30];
                $titleName = $excel[0]['namechinese'].'_'.$placeddate.'交易';
                $format=[
                    'title'=>['cellMerge'=>'A1:F3','titleName'=>$titleName],
                    'head'=>['startCell'=>'A4','endCell'=>'F4','headerArray'=>$headArray],
                    'width'=>['setCells'=>$setCells,'setWidth'=>$setWidth]
                ];
                $this->normalFormat($format);
                $j=5;
                $objPHPExcel=$this->objPHPExcel;
                $objSheet=$objPHPExcel->getActiveSheet();
                foreach ($excel as $val){
                    $objSheet->setCellValue('A'.$j, "'" .$val['panterid'])
                              ->setCellValue('B'.$j,"'" .$val['tradeid'])
                              ->setCellValue('C'.$j, $val['namechinese'])
                              ->setCellValue('D'.$j, $val['tradeamount'])
                              ->setCellValue('E'.$j, $val['tradepoint'])
                              ->setCellValue('F'.$j, $val['placeddate']." ".$val['placedtime'])
                              ;
                    $j++;
                }
                $objSheet->setCellValue('D'.$j,array_sum(array_column($excel,'tradeamount')))
                         ->setCellValue('E'.$j,array_sum(array_column($excel,'tradepoint')));
                ob_end_clean();//清除缓冲区,避免乱码
                $filename = $excel[0]['namechinese'].'_'.$placeddate.'.xls';
                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                $this->browser_export('Excel5', $filename);//输出到浏览器
                $objWriter->save("php://output");
            }else{
                exit('无数据下载');
            }
        }else{
            exit('您无权下载该商户报表');
        }
    }
    public function outDaliyJycoin(){
        $panter = $this->panterid;
        $today  = date('Ymd');
        $start    = I('get.startdate','');
        $end      = I('get.enddate','');
        $sync      = I('get.sync','');
        if($start==''){
            $start = date('Y-m-d',time()-86400);
        }
        $map['jy.placeddate'] =['egt',str_replace('-','',$start)];
        if($end!=''){
            $map['jy.placeddate'] =[['egt',str_replace('-','',$start)],
                                    ['elt',str_replace('-','',$end)]
            ];
        }
        if($panter!='FFFFFFFF'){
            $map['jy.panterid'] = $panter;
        }else{
            $panterid = trim(I('get.panterid',''));
            $pname    = trim(I('get.pname',''));
            if($panterid!=''){
                $map['jy.panterid'] = $panterid;
                $this->assign('panterid',$panterid);
            }
            if($pname!=''){
                $map['p.namechinese'] = ['like','%'.$pname.'%'];
                $this->assign('pname',$pname);
            }
            if ($sync!='') {
                $map['jy.sync'] = $sync;
            }
            $this->assign('is_admin','1');
        }
        session('out_jycoin',$map);
        $count  = M('out_daliy_jycoin')->alias('jy')
                        ->join('left join panters p on p.panterid=jy.panterid')
                        ->where($map)->count();
        $sum    = M('out_daliy_jycoin')->alias('jy')
                            ->join('left join panters p on p.panterid=jy.panterid')
                            ->where($map)->field('nvl(sum(settleamount),0) sum')->find();
        $p      = new Page($count, 10);
        $field  = 'jy.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate';
        $jycoin = M('out_daliy_jycoin')->alias('jy')
                                       ->join('left join panters p on p.panterid=jy.panterid')
                                       ->where($map)->field($field)->limit($p->firstRow,$p->listRows)->select();
        $this->assign('batchno',$today);
        $this->assign('show',$p->show());
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('jycoin',$jycoin);
        $this->assign('sum',$sum);
        $this->assign('iadmin', $this->panterid);
        $this->display('outJycoin');
    }

    /**
     * 通宝外拓 结转备付金
     */
    public function outDaliyJycoin_bfj()
	{
		$data = I('post.');
        if($data['batchno']==''){
            exit(json_encode(['code'=>'2','msg'=>'缺失批次号']));
        }
            
		$this->writeLogs("WdailyReport", date("Y-m-d H:i:s") . "-记录起始--通宝外拓日结赎回结转备付金\n\tID：" . serialize($data['id']) . ' 批次号：' . serialize($data['batchno']) . "\n\t");

        $ids=explode(',',rtrim($data['id'],','));
        $this->writeLogs("WdailyReport", "\n\t" . json_encode($ids) . "\n\t");

        $msg = '';
        foreach ($ids as $k=>$v){
            $arr = explode('.',$v);     
            $this->writeLogs("WdailyReport", "\n\t" . json_encode($arr) . "\n\t");

            $map=['panterid'=>$arr[0],'placeddate'=>$arr[1]];
            $list=M('out_daliy_jycoin')->where($map)->find();
            $this->writeLogs("WdailyReport", "out_daliy_jycoin：\n\t" . json_encode($list) . "\n\t");

            if($list==false){
                echo json_encode(['code'=>'5','msg'=>'订单查询失败']);
            }
            if($list['sync']=='1'){
                echo json_encode(['code'=>'4','msg'=>'订单已经同步']);
            }
            if($list['sync']=='2'){
                echo json_encode(['code'=>'4','msg'=>'订单已经同步']);
            }

            $trade_wastebooks = M('trade_wastebooks')->where($map)->select();
            $this->writeLogs("WdailyReport", "trade_wastebooks：\n\t" . json_encode($trade_wastebooks) . "\n\t");

            $rs = [];
            $i = 0;
            foreach($trade_wastebooks as $val) {
                $customs_c = M('customs_c')->where(['cid'=>$val['customid']])->find();
                $this->writeLogs("WdailyReport", "customs_c：\n\t" . json_encode($customs_c) . "\n\t");
                $rs[$i]['name'] = '';
                $rs[$i]['phone'] = '';
                $rs[$i]['bank_no'] = '';
                $rs[$i]['bank_name'] = '';

                $rs[$i]['user_id'] = $customs_c['customid'];
                $rs[$i]['amount'] = bcsub($val['tradepoint'],0,2);
                $rs[$i]['source'] = 5;
                if ($val['flag'] == 0) {
                    $rs[$i]['type'] = 1;
                } else if ($val['flag'] == 4) {
                    $rs[$i]['type'] = 2;
                }
                $rs[$i]['shop_id'] = $val['panterid'];
                $rs[$i]['order_no'] = $val['tradeid'];
                $rs[$i]['batch_no'] = $data['batchno'];
                $rs[$i]['key'] = md5('JYO2O01' . $rs[$i]['user_id'] . $rs[$i]['source'] . $rs[$i]['type'] . $rs[$i]['amount'] . $rs[$i]['shop_id'] . $rs[$i]['order_no'] . $rs[$i]['batch_no']);
                $i++;
            }

            $this->writeLogs("WdailyReport", "请求的数据：\n\t" . json_encode($rs) . "\n\t");
            // $res = $this->https_request('http://10.1.1.33:8080/index.php/index/index/carried', json_encode($data));//正式
            $res = $this->https_request('http://106.3.45.147:9010/index.php/index/index/carried', json_encode($rs));//测试 预生产
            $this->writeLogs("WdailyReport", "返回的信息：" . serialize($res) . "\n\t");
            $res = json_decode($res, true);

            $dingding = ['title' => '通宝外拓商家结算结转备付金' . $v, 'data' => $res, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
            $dingding = ['title' => '通宝外拓商家结算结转备付金' . $v, 'data' => $res, 'type' => '1'];//测试群聊
            $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网

            if ($res['status'] == 1) {
                $this->writeLogs("WdailyReport", "返回的信息：" . serialize($res) . "\n\t");

                $save['sync'] = '2';
                $save['syncdate'] = date('Y-m-d H:i:s');
                $list=M('out_daliy_jycoin')->where($map)->save($save);

                $this->writeLogs("WdailyReport", "修改结算表的状态：" . serialize($list) . '-修改的数据：-' . M('out_daliy_jycoin')->getLastSql() . "\n\t\n\t");
                if ($list == true) {
                    $msg .= $v.'结转备付金成功。';
                    
                } else {
                    $msg .= $v.'结转备付金失败。';
                }
            }
            $this->writeLogs("WdailyReport", "返回的信息：" . serialize($res) . "\n\t\n\t");
            
        }
        exit(json_encode(['code' => '0', 'msg' => $msg]));
        
	}

    public function outDaliyInfo(){
        $order=trim(I('post.o_id',''));
        $batchno=trim(I('post.batchno',''));
        if($order==''){
            echo json_encode(['code'=>'3','msg'=>'缺失订单号']);
        }
        if($batchno==''){
            echo json_encode(['code'=>'2','msg'=>'缺失订单号']);
        }
        $arr=explode('.',$order);
        $map=['oj.panterid'=>$arr[0],'oj.placeddate'=>$arr[1]];
        $field='p.namechinese,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,oj.*';
        $list=M('out_daliy_jycoin')->alias('oj')
            ->join("left join __PANTERS__ p on p.panterid=oj.panterid")
            ->where($map)->field($field)->find();

        if($list==false){
            exit(json_encode(['code'=>'5','msg'=>'订单查询失败']));
        }
        if($list['sync']=='1'){
            exit(json_encode(['code'=>'4','msg'=>'订单已经同步']));
        }
        if($list['sync']=='2'){
            exit(json_encode(['code'=>'4','msg'=>'订单已经同步']));
        }
        $list['account_type'] = '1';//1 自有资金 2 备付金
        $list['batchno'] = $batchno;
        exit(json_encode($list));
    }
    public function confirmJycoinSync(){
        $order=trim(I('post.o_id',''));
        if(empty($order)){
            echo json_encode(['code'=>'2','msg'=>'缺失订单号']);exit;
        }
        $arr=explode('.',$order);
        $map=['panterid'=>$arr[0],'placeddate'=>$arr[1]];
        $save['sync'] = '1';
        $save['syncdate'] = date('Y-m-d H:i:s');
        $list=M('out_daliy_jycoin')->where($map)->save($save);
        if($list==true){
            exit(json_encode(['code'=>'1','msg'=>'结算状态修改成功']));
        }else{
            exit(json_encode(['code'=>'3','msg'=>'结算状态修失败']));
        }
    }

    public function  outJycoinExcel(){
	    $map= session('out_jycoin');
	    $field  = 'jy.*,p.namechinese,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate';
	    $excel = M('out_daliy_jycoin')->alias('jy')
	                                  ->join('left join panters p on p.panterid=jy.panterid')
	                                  ->where($map)->field($field)->select();
	    if($excel){
		    $headArray = ['商户号','商户名','交易日期','交易金额','结算手续费','结算金额','结算比例','结算户名',
			    '结算银行','结算账户','开户行','同步银企'];
		    $setCells  = ['A','B','C','D','E','F','G','H','I','J','K','L'];
		    $setWidth  = [20,35,15,10,10,10,10,35,30,30,30,15];
		    $titleName = '外拓通宝'.'交易';
		    $format=[
			    'title'=>['cellMerge'=>'A1:L3','titleName'=>$titleName],
			    'head'=>['startCell'=>'A4','endCell'=>'L4','headerArray'=>$headArray],
			    'width'=>['setCells'=>$setCells,'setWidth'=>$setWidth]
		    ];
		    $this->normalFormat($format);
		    $j=5;
		    $sync = ['0'=>'未同步','1'=>'已同步'];
		    $objPHPExcel=$this->objPHPExcel;
		    $objSheet=$objPHPExcel->getActiveSheet();
		    foreach ($excel as $val){
			    $objSheet->setCellValue('A'.$j, "'" .$val['panterid'])
			             ->setCellValue('B'.$j,$val['namechinese'])
			             ->setCellValue('C'.$j, $val['placeddate'])
			             ->setCellValue('D'.$j, $val['tradeamount'])
			             ->setCellValue('E'.$j, $val['poundage'])
			             ->setCellValue('F'.$j, $val['settleamount'])
			             ->setCellValue('G'.$j, $val['rate'])
					     ->setCellValue('H'.$j, $val['settleaccountname'])
					     ->setCellValue('I'.$j, $val['settlebank'])
					     ->setCellValue('J'.$j, $val['settlebankname'])
					     ->setCellValue('K'.$j, "'".$val['settlebankid'])
				         ->setCellValue('L'.$j, $sync[$val['sync']])
			    ;
			    $j++;
		    }
		    $objSheet->setCellValue('D'.$j,array_sum(array_column($excel,'tradeamount')))
		             ->setCellValue('E'.$j,array_sum(array_column($excel,'poundage')))
			         ->setCellValue('F'.$j, array_sum(array_column($excel,'settleamount')))
		    ;
		    ob_end_clean();//清除缓冲区,避免乱码
		    $filename = '外拓通宝交易'.'.xls';
		    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		    $this->browser_export('Excel5', $filename);//输出到浏览器
		    $objWriter->save("php://output");
	    }else{
		    exit('无数据下载');
	    }
    }

    private function normalFormat($format){
    //设置标题
    $this->setTitle($format['title']['cellMerge'],$format['title']['titleName']);
    //设置表头
    $this->setHeader($format['head']['startCell'],$format['head']['endCell'],$format['head']['headerArray']);
    //设置列的宽窄
    $this->setWidth($format['width']['setCells'], $format['width']['setWidth']);

    }
}
