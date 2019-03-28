<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class MistakeController extends CommonController {
	public function _initialize(){
		parent::_initialize();
	}
    /**
     * 充值后更改充值金额
     */
    public function recharge(){
        if ($_REQUEST) {

            $datemin = I('datemin', '2000-01-01', 'trim');
            $datemax = I('datemax', '2199-09-09', 'trim');
            $cardno = I('cardno', '', 'trim');
            $linktel = I('linktel', '', 'trim');
            $description= I('description', '', 'trim');
            if ($datemin == '') $datemin = '2000-01-01';
            if ($datemax == '') $datemax = '2199-09-09';
            $where['ca.makedate'] = array(array('egt', str_replace('-', '', $datemin)), array('elt', str_replace('-', '', $datemax)));
            if ($cardno != ''){
                $where['ca.cardno'] = $cardno;
                $map['cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
            if ($linktel != ''){
                $where['cu.linktel'] = $linktel;
                $map['cu.linktel']=$linktel;
                $this->assign('linktel',$linktel);
            }
            if ($description != ''){
                $where['c_p_l.description'] =array('like','%'.$description.'%');
                $map['c_p_l.description']=array('like','%'.$description.'%');
                $this->assign('description',$description);
            }
        }
        $model=new Model();
        $where['cu_p_l.tradeflag']='1';
        $where['a.type']='00';
        $where['ca.status'] = 'Y';
        $field='ca.cardno,cu.customid,cu.namechinese cuname,cu.linktel,p.namechinese pname,a.amount,cu_p_l.totalmoney cplmoney,cu_p_l.realamount reamount,c_p_l.purchaseid cppid,c_p_l.description cpd';
//        $field.='cu.namechinese as customname,p.namechinese as pantername,a.amount';
        $count = $model->table('card_purchase_logs')->alias('c_p_l')
            ->join('custom_purchase_logs cu_p_l on cu_p_l.purchaseid = c_p_l.purchaseid')
            ->join('cards ca on ca.cardno = c_p_l.cardno')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->join('account a on a.customid=ca.customid')
            ->field($field)->where($where)->count();
//        dump($count);exit;
        $p = new \Think\Page($count, 10);
        $data = $model->table('card_purchase_logs')->alias('c_p_l')
            ->join('custom_purchase_logs cu_p_l on cu_p_l.purchaseid = c_p_l.purchaseid')
            ->join('cards ca on ca.cardno = c_p_l.cardno')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->join('account a on a.customid=ca.customid')
            ->field($field)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('count', $count);
        $this->assign('data', $data);
        $this->assign('page', $page);
        $this->display();
    }
	/**
	 * 现金长短款处理列表
	 */
	public function correct(){
		if ($_REQUEST) {

			$datemin = I('datemin', '2000-01-01', 'trim');
			$datemax = I('datemax', '2199-09-09', 'trim');
			$cardno = I('cardno', '', 'trim');			
			if ($datemin == '') $datemin = '2000-01-01';
			if ($datemax == '') $datemax = '2199-09-09';
			$where['ca.makedate'] = array(array('egt', str_replace('-', '', $datemin)), array('elt', str_replace('-', '', $datemax)));
			if ($cardno != ''){
                $where['ca.cardno'] = $cardno;
                $map['cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
		}
        $where['ca.status'] = 'Y';$where['a.type'] = '00';

        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";;
        }
        //print_r($where);
        $model=new Model();
        $field='ca.cardno,ca.cardbalance,ca.customid as cusmid,cu.customid,';
        $field.='cu.namechinese as customname,p.namechinese as pantername,a.amount';
		$count = $model->table('cards')->alias('ca')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('account a on a.customid=ca.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->field($field)->where($where)->count();
		$p = new \Think\Page($count, 10);
		$data = $model->table('cards')->alias('ca')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('account a on a.customid=ca.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->field($field)->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
		$page = $p->show();
		$this->assign('count', $count);
		$this->assign('data', $data);
        $this->assign('page', $page);
		$this->display();
	}
	//充值更改金额处理
    public function rMore(){
        $customid = I('customid','','trim');
        $purchaseid = I('purchaseid','','trim');
        if (IS_POST) {
            $customid = I('customid');
            $purchaseid = I('cupid');
            $amount = I('reamount', 0, 'float');
            $amounts = I('amount', 0, 'float');
            $lower = I('lower', 0, 'float');
            $des = I('description');
            if ($lower > $amount)
                $this->error('更新金额已超出原充值的金额，请重新填写');
            if($lower>$amounts){
                $this->error('卡余额小于要更改的原充值的金额');
            }
            $sql = "update custom_purchase_logs set realamount = $lower where purchaseid=$purchaseid and customid=$customid";
            $sql1= M()->table('custom_purchase_logs')->execute($sql);
            if($sql1){
                $sql2 = "update card_purchase_logs set amount = '{$lower}',description = '{$des}' where purchaseid='{$purchaseid}'";
                $sql3= M()->table('card_purchase_logs')->execute($sql2);
            }
            if($sql3){
                $bSql = "UPDATE account SET amount=$amounts - ($amount-$lower)  where customid=$customid and type='00'";
                $sql5 = M()->table('account')->execute($bSql);
                if($sql5){
                    $this->success("更新成功");
                }else{
                    $this->error("更新失败");
                }
            }

        }
        $data = M()->table('CARD_PURCHASE_LOGS cpl,CUSTOM_PURCHASE_LOGS cupl,CARDS ca,CUSTOMS_C cc,CUSTOMS cu,PANTERS p,ACCOUNT A')
            ->field('ca.cardno,ca.customid,cu.namechinese as cuname,p.namechinese as pname,a.amount,cupl.totalmoney as cuamount,cupl.purchaseid as cupid,cupl.realamount as reamount,cpl.description as des')
            ->where('cupl.purchaseid=cpl.purchaseid AND ca.cardno=cpl.cardno AND ca.customid=cc.cid AND cu.customid=cc.customid AND p.panterid=ca.panterid AND a.customid=ca.customid')
            ->where(array('ca.status'=>'Y','cu.customid'=>$customid,'cpl.purchaseid'=>$purchaseid))
            ->find();
        $this->assign('data', $data);
        $this->display();
    }
	/**
	 * 现金长款处理
	 */
	public function more(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$lower = I('lower', 0, 'float');
			if ($lower > $amount)
				$this->error('您调低的金额已超出上限，请重新填写');
			$customid = I('customid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('ACCOUNT')->where(array('type'=>'00', 'customid'=>$customid))->find();
			$setamout = M()->table('ACCOUNT')->where(array('type'=>'00', 'customid'=>$customid))->setDec('amount',$lower);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(".$lower.",'".date('Ymd', time())."','".date('H:i:s', time())."',0,'".session('userid')."','".$cardno."',0,0,0)";
                if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql)) 
                	$this->redirect('Mistake/correct', array(), 1, '<h2>您调低的金额为'.$lower.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,ACCOUNT ac,PANTERS pa')
            ->field('ca.cardno,ca.customid,cu.namechinese as customname,ac.amount,pa.namechinese as pantername')
            ->where('ca.customid=cu.customid AND ca.customid=ac.customid AND ca.panterid=pa.panterid')
            ->where(array('ca.status'=>'Y', 'ac.type'=>'00', 'ac.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 现金短款处理
	 */
	public function less(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$upper = I('upper', 0, 'float');
			if (($upper + $amount) > 5000)
				$this->error('您调高的金额已超出上限，请重新填写');
			$customid = I('customid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('ACCOUNT')->where(array('type'=>'00', 'customid'=>$customid))->find();
			$setamout = M()->table('ACCOUNT')->where(array('type'=>'00', 'customid'=>$customid))->setInc('amount',$upper);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(".$upper.",'".date('Ymd', time())."','".date('H:i:s', time())."',1,'".session('userid')."','".$cardno."',0,0,0)";
				if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql))
					$this->redirect('Mistake/correct', array(), 1, '<h2>您调高的金额为'.$upper.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,ACCOUNT ac,PANTERS pa')
            ->field('ca.cardno,ca.customid,cu.namechinese as customname,ac.amount,pa.namechinese as pantername')
            ->where('ca.customid=cu.customid AND ca.customid=ac.customid AND ca.panterid=pa.panterid')
            ->where(array('ca.status'=>'Y', 'ac.type'=>'00', 'ac.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 积分长短款处理
	 */
	public function points(){
		if ($_REQUEST) {
			$datemin = I('datemin', '2000-01-01', 'trim');
			$datemax = I('datemax', '2199-09-09', 'trim');
			$cardno = I('cardno', '', 'trim');			
			if ($datemin == '') $datemin = '2000-01-01';
			if ($datemax == '') $datemax = '2199-09-09';
			$where['ca.makedate'] = array(array('egt', str_replace('-', '', $datemin)), array('elt', str_replace('-', '', $datemax)));
			if ($cardno != ''){
                $where['ca.cardno'] = $cardno;
                $map['cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
		}
        $where['ca.status'] = 'Y';$where['a.type'] = '01';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";;
        }
        $model=new Model();
        $field="ca.cardno,ca.cardbalance,ca.customid as cusmid,cu.customid,cu.namechinese as customname,";
        $field.="p.namechinese as pantername,a.amount";
		$count = $model->table('cards')->alias('ca')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('account a on a.customid=ca.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->field($field)->where($where)->count();
		$p = new \Think\Page($count, 10);
		$data = $model->table('cards')->alias('ca')
            ->join('customs_c cc on ca.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('account a on a.customid=ca.customid')
            ->join('panters p on p.panterid=ca.panterid')
            ->field($field)->where($where)
            ->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
		$this->assign('count', $count);
		$this->assign('data', $data);
		$this->assign('page', $page);
		$this->display();
	}
	
	/**
	 * 积分长款处理
	 */
	public function pMore(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$lower = I('lower', 0, 'float');
			if ($lower > $amount)
				$this->error('您调低的积分已超出上限，请重新填写');
			$customid = I('customid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('ACCOUNT')->where(array('type'=>'01', 'customid'=>$customid))->find();
			$setamout = M()->table('ACCOUNT')->where(array('type'=>'01', 'customid'=>$customid))->setDec('amount',$lower);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(0,'".date('Ymd', time())."','".date('H:i:s', time())."',0,'".session('userid')."','".$cardno."',".$lower.",0,0)";
                if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql)) 
                	$this->redirect('Mistake/points', array(), 1, '<h2>您调低的积分为'.$lower.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,ACCOUNT ac,PANTERS pa,CUSTOMS_C cc')->field('ca.cardno,ca.cardbalance,ca.customid as cusmid,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,ac.amount')->where('ca.customid=cc.cid AND cc.customid=cu.customid AND ca.customid=ac.customid AND ca.panterid=pa.panterid')->where(array('ca.status'=>'Y', 'ac.type'=>'01', 'ac.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 积分短款处理
	 */
	public function pLess(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$upper = I('upper', 0, 'float');
			if (($upper + $amount) > 5000)
				$this->error('您调高的积分已超出上限，请重新填写');
			$customid = I('customid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('ACCOUNT')->where(array('type'=>'01', 'customid'=>$customid))->find();
			$setamout = M()->table('ACCOUNT')->where(array('type'=>'01', 'customid'=>$customid))->setInc('amount',$upper);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(0,'".date('Ymd', time())."','".date('H:i:s', time())."',1,'".session('userid')."','".$cardno."',".$upper.",0,0)";
				if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql))
					$this->redirect('Mistake/points', array(), 1, '<h2>您调高的积分为'.$upper.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,ACCOUNT ac,PANTERS pa,CUSTOMS_C cc')->field('ca.cardno,ca.cardbalance,ca.customid as cusmid,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,ac.amount')->where('ca.customid=cc.cid AND cc.customid=cu.customid AND ca.customid=ac.customid AND ca.panterid=pa.panterid')->where(array('ca.status'=>'Y', 'ac.type'=>'01', 'ac.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 营销券长短款处理
	 */
	public function market(){
		if ($_REQUEST) {
			$datemin = I('datemin', '2000-01-01', 'trim');
			$datemax = I('datemax', '2199-09-09', 'trim');
			//$quanid = I('quanid', '', 'trim');
			$cardno = I('cardno', '', 'trim');
			if ($datemin == '') $datemin = '2000-01-01';
			if ($datemax == '') $datemax = '2199-09-09';
			$where['ca.makedate'] = array(array('egt', str_replace('-', '', $datemin)), array('elt', str_replace('-', '', $datemax)));
			if ($cardno != ''){
                $where['ca.cardno'] = $cardno;
                $map['cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
		}

        $where['ca.status'] = 'Y';

        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" pa.panterid='".$this->panterid."' OR pa.parent='".$this->panterid."'";;
        }
        $model=new Model();
		$count = M()->table('CARDS ca,CUSTOMS cu,PANTERS pa,QUANCZ qz,QUANKIND qk,QUANPANTER qp')
            ->field('ca.cardno,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,qz.quanid,qz.amount,qk.quanname')
            ->where('qz.customid=cu.customid AND qz.customid=ca.customid AND ca.panterid=pa.panterid AND qk.quanid=qz.quanid AND qp.quanid=qz.quanid AND qp.panterid=pa.panterid')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$data = M()->table('CARDS ca,CUSTOMS cu,PANTERS pa,QUANCZ qz,QUANKIND qk,QUANPANTER qp')
            ->field('ca.cardno,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,qz.quanid,qz.amount,qk.quanname')
            ->where('qz.customid=cu.customid AND qz.customid=ca.customid AND ca.panterid=pa.panterid AND qk.quanid=qz.quanid AND qp.quanid=qz.quanid AND qp.panterid=pa.panterid')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        //echo M()->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
		$this->assign('count', $count);
		$this->assign('data', $data);
        $this->assign('page', $page);
		$this->display();
	}
	
	/**
	 * 营销券长款处理
	 */
	public function mMore(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$lower = I('lower', 0, 'float');
			if ($lower > $amount)
				$this->error('您调低的数量已超出券数量，请重新填写');
			$customid = I('customid','','trim');
			$quanid = I('quanid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('QUANCZ')->where(array('quanid'=>$quanid))->find();
			$setamout = M()->table('QUANCZ')->where(array('customid'=>$customid, 'quanid'=>$quanid))->setDec('amount',$lower);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(0,'".date('Ymd', time())."','".date('H:i:s', time())."',0,'".session('userid')."','".$cardno."',0,".$lower.",'".$quanid."')";
                if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql)) 
                	$this->redirect('Mistake/market', array(), 1, '<h2>您调低的数量为'.$lower.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$quanid = I('quanid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,PANTERS pa,QUANCZ qz,QUANKIND qk,QUANPANTER qp')
            ->field('ca.cardno,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,qz.quanid,qz.amount,qk.quanname')
            ->where('qz.customid=cu.customid AND qz.customid=ca.customid AND ca.panterid=pa.panterid AND qk.quanid=qz.quanid AND qp.quanid=qz.quanid AND qp.panterid=pa.panterid')
            ->where(array('ca.status'=>'Y', 'qz.quanid'=>$quanid, 'qz.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 营销券短款处理
	 */
	public function mLess(){
		if (IS_POST) {
			$amount = I('amount', 0, 'float');
			$upper = I('upper', 0, 'float');
			if (($upper + $amount) > 5000)
				$this->error('您调高的数量已超出最大调整限额，请重新填写');
			$customid = I('customid','','trim');
			$quanid = I('quanid','','trim');
			$cardno = M()->table('CARDS')->where(array('customid'=>$customid))->getField('cardno');
			$account = M()->table('QUANCZ')->where(array('quanid'=>$quanid))->find();
			$setamout = M()->table('QUANCZ')->where(array('customid'=>$customid, 'quanid'=>$quanid))->setInc('amount',$upper);
			if ($setamout) {
				$sql = "insert into account_change_logs(account,placeddate,placedtime,flag,useid,cardno,account1,account2,quanid) values(0,'".date('Ymd', time())."','".date('H:i:s', time())."',1,'".session('userid')."','".$cardno."',0,".$upper.",'".$quanid."')";
				if (M()->table('ACCOUNT_CHANGE_LOGS')->execute($sql))
					$this->redirect('Mistake/market', array(), 1, '<h2>您调高的数量为'.$upper.'，保存成功！</h2>');
			}
		}
		$customid = I('customid','','trim');
		$quanid = I('quanid','','trim');
		$data = M()->table('CARDS ca,CUSTOMS cu,PANTERS pa,QUANCZ qz,QUANKIND qk,QUANPANTER qp')->field('ca.cardno,cu.customid,cu.namechinese as customname,pa.namechinese as pantername,qz.quanid,qz.amount,qk.quanname')->where('qz.customid=cu.customid AND qz.customid=ca.customid AND ca.panterid=pa.panterid AND qk.quanid=qz.quanid AND qp.quanid=qz.quanid AND qp.panterid=pa.panterid')->where(array('ca.status'=>'Y', 'qz.quanid'=>$quanid, 'qz.customid'=>$customid))->find();
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 长短款处理报表
	 */
	public function index(){
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";;
        }
		if ($_REQUEST) {
			$cardno = I('cardno', '', 'trim');
			if ($cardno != ''){
                $where['alog.cardno'] = $cardno;
                $map['alog.cardno']=$cardno;
                $this->assign('cardno',$cardno);
            }
            $field="alog.account,alog.placeddate,alog.placedtime,alog.cardno,";
            $field.="alog.useid,alog.flag,alog.account1 as points,alog.account2 as quannum,quanid";
            $account_count = M()->table('ACCOUNT_CHANGE_LOGS')->alias('alog')
                ->join('cards c on c.cardno=alog.cardno')
                ->join('panters p on p.panterid=c.panterid')
                ->field($field)
                ->where($where)->count();
            $p = new \Think\Page($account_count, 10);
			$account_changes = M()->table('ACCOUNT_CHANGE_LOGS')->alias('alog')
                ->join('cards c on c.cardno=alog.cardno')
                ->join('panters p on p.panterid=c.panterid')
                ->field($field)->where($where)->order('placeddate desc,placedtime desc')
                ->limit($p->firstRow.','.$p->listRows)->select();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $page = $p->show();
            session('indexCon',$where);
			$this->assign('account_count', $account_count);
			$this->assign('account_changes', $account_changes);
            $this->assign('page',$page);
			$this->display();
		} else {
            $field="alog.account,alog.placeddate,alog.placedtime,alog.cardno,";
            $field.="alog.useid,alog.flag,alog.account1 as points,alog.account2 as quannum,quanid";
			$account_count = M()->table('ACCOUNT_CHANGE_LOGS')->alias('alog')
                ->join('cards c on c.cardno=alog.cardno')
                ->join('panters p on p.panterid=c.panterid')
                ->field($field)->where($where)->count();
			$page = new \Think\Page($account_count, 10);
			$account_changes = M()->table('ACCOUNT_CHANGE_LOGS')->alias('alog')
                ->join('cards c on c.cardno=alog.cardno')
                ->join('panters p on p.panterid=c.panterid')
                ->field($field)->where($where)->order('placeddate desc,placedtime desc')
                ->limit($page->firstRow.','.$page->listRows)->select();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $page->parameter[$key]= $val;
                }
            }
			$pages = $page->show();
            session('indexCon',$where);
			$this->assign('account_count', $account_count);
			$this->assign('account_changes', $account_changes);
			$this->assign('page', $pages);
			$this->display();
		}
	}
	
	/**
	 * 长短款处理报表导出
	 */
	public function indexExcel(){
        $conmap=session('indexCon');
        foreach($conmap as $key=>$val){
            $where[$key]=$val;
        }
        $field="alog.account,alog.placeddate,alog.placedtime,alog.cardno,";
        $field.="alog.useid,alog.flag,alog.account1 as points,alog.account2 as quannum,quanid";
		$account_changes = M()->table('ACCOUNT_CHANGE_LOGS')->alias('alog')
            ->field($field)->where($where)->order('placeddate desc,placedtime desc')->select();
		
		$strlist = "序号,卡号,调节类型,调节日期,调节时间,金额,积分,券数量,券编号,操作者ID\n";
		$strlist = $this->changeCode($strlist);
		foreach ($account_changes as $key=>$val) {
			foreach ($val as $k=>$v){
				if ($k == 'flag') {
					if ($v == '1')
						$v = '上调';
					else 
						$v = '下调';
				}
				$val[$k] = $this->changeCode($v);
			}
			$strlist .= $val['numrow'] . ',' . $val['cardno'] . "\t" . ',';
			$strlist .= $val['flag'] . ',' . $val['placeddate'] . ',' . $val['placedtime'] . ',';
			$strlist .= $val['account'] . ',' . $val['points'] . ',' . $val['quannum'] . ',';
			$strlist .= $val['quanid'] . "\t" .  ',' . $val['useid'] . "\t\n";
		}
		$filename = '长短款处理报表' . date('YmdHis');
		$this->load_csv($strlist, $filename);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}