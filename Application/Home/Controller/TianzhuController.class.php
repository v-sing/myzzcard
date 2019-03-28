<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class TianzhuController extends CommonController{

    public function _initialize(){
        parent::_initialize();
    }
    //通宝充值
    public function tbrecharge(){
        $model=new Model();
        $where['cupl.paymenttype']='通宝充值';
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $cardno	= trim(I('get.cardno',''));
        $cuname	= trim(I('get.cuname',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cpl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.point point,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno';
        $count=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cpl.card_purchaseid desc')->select();
//        echo $model->getLastSql();
        session('tbrechargeCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
//        foreach($list as $key=>$val){
//            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
//            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
//            $list[$key]['point']=$pointPurchase['point'];
//        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }
    //现金充值报表
    public function recharge(){
        $model=new Model();
        $where['cupl.paymenttype']='普通充值';
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $cardno	= trim(I('get.cardno',''));
        $cuname	= trim(I('get.cuname',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cpl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno';
        $count=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cpl.card_purchaseid desc')->select();
//        echo $model->getLastSql();
        session('rechargeCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        foreach($list as $key=>$val){
            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $list[$key]['point']=$pointPurchase['point'];
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //现金充值报表导出
    public function recharge_excel(){
        $where=session('rechargeCon');
        $model=new Model();
        $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno';
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('cpl.card_purchaseid desc')->select();

        //echo $model->getLastSql();
        $sellCard_list = array();
        $strlist = "会员名称,手机号,卡号,充值流水号,充值时间,充值金额,赠送积分\n";
        $strlist = $this->changeCode($strlist);
        $totalAmount=0;$totalPoint=0;
        foreach($list as $key=>$val){
            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $point=$pointPurchase['point'];

            $strlist.=$val['cuname']. ','.$val['linktel'].','.$val['cardno']."\t".',';
            $strlist.=$val['card_purchaseid'].  "\t" .','.date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'];
            $strlist.=floatval($val['amount']).','.floatval($point).  "\n";
            $totalAmount+=floatval($val['amount']);
            $totalPoint+=floatval($point);
        }
        $filename = '现金充值报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $strlist .= ',,,,'.$this->changeCode('总计:').',' . $totalAmount . "\t" . ','.$totalPoint . "\n";
        $this->load_csv($strlist, $filename);
    }

    //物业充值报表
    public function propertyRecharge(){
        $model=new Model();
        $where['cupl.paymenttype']='物业充值';
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $cardno	= trim(I('get.cardno',''));
        $cuname	= trim(I('get.cuname',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cpl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
//        if($start=='' && $end==''){
//            $startdate=date('Ym01',strtotime(date('Ymd')));
//            $enddate=date('Ymd',time());
//            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
//            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
//            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
//        }
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        $where['c.cardkind']='6666';
        $field='cupl.paymenttype,cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno,cpl.description';
        $count=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cpl.card_purchaseid desc')->select();
//        echo $model->getLastSql();exit;
        session('propertyRechargeCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        foreach($list as $key=>$val){
            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $list[$key]['point']=$pointPurchase['point'];
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //现金充值报表导出
    public function propertyRecharge_excel(){
        $where=session('propertyRechargeCon');
        $model=new Model();
        $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno,cpl.description';
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('cpl.card_purchaseid desc')->select();
        //echo $model->getLastSql();
        $strlist = "会员名称,手机号,卡号,缴费信息,流水号,缴费时间,赠送积分\n";
        $strlist = $this->changeCode($strlist);
        $totalPoint=0;
        foreach($list as $key=>$val){
            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $point=$pointPurchase['point'];

            $val['description']=$this->changeCode(str_replace(',',' ',$val['description']));
            $strlist.=$this->changeCode($val['cuname']). ','.$val['linktel'].','.$val['cardno']."\t".',';
            $strlist.=$val['description']."\t".','.$val['card_purchaseid']."\t".',';
            $strlist.=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'].','.floatval($point).  "\n";
            $totalPoint+=floatval($point);
        }
        $filename = '物业缴费报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $strlist .= ',,,,,'.$this->changeCode('总计:').',' .$totalPoint . "\n";
        $this->load_csv($strlist, $filename);
    }
    //编辑积分信息
    public function editPro(){
        $model=new Model();
        if(IS_POST){
            $cardno= trim($_POST['cardno']);
            $card_purchaseid = trim($_POST['card_purchaseid']);
            $description = trim($_POST['description']);
            $point = trim($_POST['point']);
            if($description=="")
            {
                $this->error("缴费信息不能为空!");
            }
            if($point=="")
            {
                $this->error("赠送积分不能为空");
            }
            $where['card_purchaseid']=$card_purchaseid;
            $where['cardno']=$cardno;
            $search=$model->table('card_purchase_logs')->where($where)->find();
            if($search==false){
                $this->error('缴费流水号信息有误，请核实');
            }else{
                $map=array('description'=>'天筑充值单号:'.$search['card_purchaseid']);
                $pointPurchase=$model->table('card_purchase_logs')->where($map)->find();
                $model->startTrans();
                if($pointPurchase==true){
                    $data['description']= $description;
                    $dsave = $model->table('card_purchase_logs')->where(array('card_purchaseid'=>$card_purchaseid))->save($data);
                    if($dsave==true){
                        $apoint=$pointPurchase['point'];
                        // echo $apoint;exit;
                        $data1['point']= $point;//新
                        //  echo $data1['point'];exit;
                        $desave = $model->table('card_purchase_logs')->where(array('card_purchaseid'=>$pointPurchase['card_purchaseid']))->save($data1);
                        if($desave==true){
                            $acpoint=$data1['point']-$apoint;
                            // echo $acpoint;exit;
                            $where1['c.cardno']= $cardno;
                            $where1['a.type'] = '04';
                            $field ='a.customid,a.amount';
                            $cust=$model->table('account')->alias('a')->join('left join cards c on  c.customid = a.customid')->where($where1)->field($field)->find();
                            //     dump($cust);exit;
                            //     echo $model->getlastSql();exit;
                            if($cust==true){
                                $Model = M();
                                $sql="update account set amount=amount+'{$acpoint}' where customid='{$cust['customid']}' and type='04'";
                                $a= $Model->execute($sql);
                                if($a==true){
                                    //echo '11';exit;
                                    $tpoint=$model->table('point_account')->where(array('cardpurchaseid'=>$pointPurchase['card_purchaseid'],'cardid'=>$cust['customid']))->find();
                                    //   echo $model->getlastSql();exit;
                                    //  dump($tpoint);exit;
                                    if($tpoint==true){
                                        //    echo '11p';exit;
                                        $Model = M();
                                        $c= $tpoint['cardpurchaseid'];
                                        $sql2="update point_account set rechargeAmount=rechargeAmount+'{$acpoint}',remindAmount=remindAmount+'{$acpoint}' where cardpurchaseid='{$c}'";
                                        // echo $sql2;exit;
//                                            $accountsql=$this->model->execute($sql2);
                                        $b= $Model->execute($sql2);
                                        if($b == true){
                                            $model->commit();
                                            $this->success('修改成功',U('Tianzhu/propertyRecharge'));
                                        }else{
                                            $model->rollback();
                                            $this->error('修改失败');
                                        }
                                    }else{
                                        $model->rollback();
                                        $this->error('账户积分更新失败');
                                    }
                                }else{
                                    $model->rollback();
                                    $this->error('账户总积分更新失败');
                                }
                            }else{
                                $model->rollback();
                                $this->error('账户信息不正确');
                            }

                        }else{
                            $model->rollback();
                            $this->error('赠送积分更新失败');
                        }
                    }else{
                        $model->rollback();
                        $this->error('缴费信息更新失败');
                    }


                }else{
                    $model->rollback();
                    $this->error('缴费流水号信息有误');
                }

            }
        }else{
            $card_purchaseid=trim($_REQUEST['card_purchaseid']);
            if(empty($card_purchaseid)){
                $this->error('缴费流水号缺失');
            }
            $where['cpl.card_purchaseid']=$card_purchaseid;
            $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,';
            $field.='c.cardno,cpl.description';
            $list=$model->table('card_purchase_logs')->alias('cpl')
                ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
                ->join('cards c on c.cardno=cpl.cardno')
                ->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cu.customid=cc.customid')
                ->where($where)->field($field)->find();
            $map=array('description'=>'天筑充值单号:'.$list['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $list['point']=$pointPurchase['point'];

            $this->assign('info',$list);
            $this->display();
        }
    }
    public function cardRecharge(){
        $paytype=array('00'=>'现金','01'=>'银行卡','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他');
        $this->assign('paytype',$paytype);
        $model=new Model();
        if(IS_POST){
            $cardno=trim(I('post.cardno',''));//卡号
            $linktel = trim(I('post.linktel',''));//联系电话
            $name = trim(I('post.namechinese',''));//客户名称
            $amount = trim(I('post.amount',''));//冲值金额
            $personid = trim(I('post.personid',''));//身份证号
            $personidissuedate = trim(I('post.personidissuedate',''));//身份证签发日期
            $personidexdate = trim(I('post.personidexdate',''));//身份证到期日期
            $residaddress = trim(I('post.residaddress',''));//住址
            $sizecode = trim(I('post.sizecode',''));//户型（物业缴费时传入，值为：1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19......）
            $months = trim(I('post.months',''));//物业缴费月数（物业缴费时传入）
            $scene = trim(I('post.scene',''));//场景编号（1普通充值，2物业缴费充值）
            $payttype=trim(I('post.paymenttype',''));//支付类型
            // echo $months;exit;
            if (empty($cardno)) {
                $this->error('卡号不能为空');
            }
            if (empty($linktel)) {
                $this->error('联系电话不能为空');
            }
            if (empty($name)) {
                $this->error('客户名称不能为空');
            }
            if (empty($personid)) {
                $this->error('身份证号不能为空');
            }
            if (empty($amount)) {
                $this->error('充值金额不能为空');
            }
            if (empty($residaddress)) {
                $this->error('住址不能为空');
            }
            if (empty($personidissuedate)) {
                $this->error('身份证签发日期不能为空');
            }
            if (empty($personidexdate)) {
                $this->error('身份证有效日期不能为空');
            }
            if (empty($scene) || !in_array($scene, array(1, 2))) {
                $this->error('无效场景类型');
            }
            if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
                $this->error('无效充值金额');
            }
            $card = M('cards')->where(array('cardno' => $cardno))->find();
            //  dump($card);exit;
            if ($card == false) {
                $this->error('查无此卡号');
            }else{
                if(trim($card['status'])=='N'){
                    $type =1;
                }elseif(trim($card['status'])=='Y'){
                    $type =2;
                }else{
                    $this->error('该卡非正常卡或非新卡，不能充值!');
                }
            }
            if ($scene == 1) {
                $paymenttype = $payttype.'--普通充值';
                if ($amount > 5000) {
                    $this->error('充值金额不得大于5000!');
                }else{
                    $point = 0;
                }
                $description = "充值金额:{$amount}";

            }elseif($scene == 2){
                $paymenttype = $payttype.'--物业充值';
                if (empty($sizecode) || empty($months)) {
                    $this->error('户型或缴费月份缺失!');
                }
                if (!preg_match('/^[0-9]\d*$/', $sizecode)) {
                    $this->error('户型格式有误!');
                }
                if (!preg_match('/^[1-9]\d*$/', $months)) {
                    $this->error('物业月份格式有误!');
                }
                switch ($sizecode) {
                    case 1:
                        $point = 144 * $months;
                        break;
                    case 2:
                        $point = 162 * $months;
                        break;
                    case 3:
                        $point = 270 * $months;
                        break;
                    case 4:
                        $point = 288 * $months;
                        break;
                    case 5:
                        $point = 414 * $months;
                        break;
                    case 6:
                        $point = 432 * $months;
                        break;
                    case 7:
                        $point = 504 * $months;
                        break;
                    case 8:
                        $point = 522 * $months;
                        break;
                    case 9:
                        $point = 540 * $months;
                        break;
                    case 10:
                        $point = 576 * $months;
                        break;
                    case 11:
                        $point= 594 * $months;
                        break;
                    case 12:
                        $point= 738 * $months;
                        break;
                    case 13:
                        $point= 756 * $months;
                        break;
                    case 14:
                        $point= 810 * $months;
                        break;
                    case 15:
                        $point= 828 * $months;
                        break;
                    case 16:
                        $point= 900 * $months;
                        break;
                    case 17:
                        $point= 918 * $months;
                        break;
                    case 18:
                        $point= 1080 * $months;
                        break;
                    case 19:
                        $point= 1098 * $months;
                        break;
                    case 20:
                        $point= 1188 * $months;
                        break;
                    default:
                        $point = 144 * $months;
                }
                $description = "房型:" . $this->room[$sizecode] . ",缴费月数:{$months},缴费金额:{$amount}";
                $amount = 0;
            }
            $panterid = $this->tzpanterid;
            $userid =  $this->userid;
            $model->startTrans();
            if($type==1){
                if ((empty($linktel) || empty($name) || empty($personid))) {
                    $this->error('开卡用户信息缺失');
                }
                $data = array('linktel' => $linktel, 'personid' => $personid, 'name' => $name,
                    'personidexdate' => $personidexdate, 'personidissuedate' => $personidissuedate,
                    'residaddress' => $residaddress,'panterid' =>$panterid);
                $customid = $this->addcustoms($data);
                $rechargeBool = $this->tzOpenCard($cardno, $customid, $amount, $panterid, $userid, $paymenttype, $description);
                $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                    ->join('customs cu on cc.customid=cu.customid')
                    ->join('account a on a.customid=c.customid')
                    ->field('cu.customid,a.amount,a.accountid,c.customid cardid')
                    ->where(array('c.cardno' => $cardno, 'a.type' => '04'))->find();
            }elseif($type==2){
                //    echo '232';exit;
                $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
                    ->join('customs cu on cc.customid=cu.customid')
                    ->join('account a on a.customid=c.customid')
                    ->field('cu.customid,cu.namechinese,cu.linktel,cu.personid,a.amount,a.accountid,c.customid cardid')
                    ->where(array('c.cardno' => $cardno, 'a.type' => '04'))->find();
                if($custom['namechinese'] !=$name){
                    $this->error('用户名不匹配');
                }
                if($custom['linktel'] !=$linktel){
                    $this->error('用户联系电话不匹配');
                }
                if($custom['personid'] !=$personid){
                    $this->error('用户身份证不匹配');
                }
                $rechargeBool = $this->tzRecharge($cardno, $custom['cardid'], $amount, $panterid, $userid, $paymenttype, $description);
            }
            if ($rechargeBool == true) {
                $cardInfo = array('cardid' => $custom['cardid'], 'cardno' => $cardno,
                    'accountid' => rtrim($custom['accountid']), 'validity' => 12, 'type' => 2);
                $pointBool = $this->tzPointRechargeExe($cardInfo, $point, $panterid, $userid, $rechargeBool);
                if ($pointBool == true) {
                    $model->commit();
                    $this->success('充值成功');
                    //  returnMsg(array('status' => '1', 'codemsg' => '充值成功', 'purchaseid' => $rechargeBool, 'point' =>
                    //     $point));
                } else {
                    $model->rollback();
                    $this->error('充值失败');
                    //  returnMsg(array('status' => '012', 'codemsg' => '积分充值失败'));
                }
            } else {
                $model->rollback();
                $this->error('余额充值失败');
                //  returnMsg(array('status' => '012', 'codemsg' => '余额充值失败'));
            }
        }
        $this->display();
    }
    //添加会员的信息
    public function addcustoms($data)
    {
        $customs = M('customs');
        $linktel = $data['linktel'];
        $personid = $data['personid'];
        $namechinese = $data['name'];
        $panterid = $data['panterid'];
        $personidissuedate = $data['personidissuedate'];
        $personidexdate = $data['personidexdate'];
        $residaddress = $data['residaddress'];
        $birth = substr($personid, 6, 8);
        $sex = substr($personid, -2, 1) % 2 == 0 ? '女' : '男';
        $customlevel = '天筑会员';
        $countrycode = '天筑会员';
        $currentDate = date('Ymd', time());
        //$customid=$this->getnextcode('customs',8);
        $customid = $this->getFieldNextNumber("customid");
        $sql = 'insert into customs(customid,namechinese,linktel,personid,panterid,placeddate,personidtype,customlevel,';
        $sql .= "countrycode,birthday,sex,personidissuedate,personidexdate,residaddress) values('{$customid}','{$namechinese}','{$linktel}','{$personid}','{$panterid}'";
        $sql .= ",'{$currentDate}','身份证','{$customlevel}','{$countrycode}','{$birth}','{$sex}','{$personidissuedate}','{$personidexdate}','{$residaddress}')";
        $ccu = $customs->execute($sql);
        if ($ccu) {
            return $customid;
        } else {
            return false;
        }
    }
    //开卡执行
    protected function tzOpenCard($cardno, $customid, $amount, $panterid = null, $userid, $paymenttype = '转账', $description = null)
    {
        if (empty($cardno)) return false;
        $userstr = substr($userid, 12, 4);
        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
        $purchaseid = $this->getFieldNextNumber("purchaseid");
        $purchaseid = $userstr . $purchaseid;
        $currentDate = date('Ymd');
        $checkDate = date('Ymd');
        $where['cardno'] = $cardno;
        $cardinfo = M('cards')->where($where)->field('panterid')->find();
        //写入购卡单并审核
        $customplSql = "insert into custom_purchase_logs(CUSTOMID,PURCHASEID,PLACEDDATE,PAYMENTTYPE,USERID,AMOUNT,CARD_NUM,TOTALMONEY,";
        $customplSql .= "POINT,REALAMOUNT,WRITEAMOUNT,WRITENUMBER,CHECKNO,DESCRIPTION,FLAG,PANTERID,CHECKID,DESCRIPTION1,";
        $customplSql .= "TRADEFLAG,CUSTOMFLAG,DAYFLAG,TERMPOSNO,PLACEDTIME,CHECKDATE,CHECKTIME,ADITID) ";
        $customplSql .= "VALUES('" . $customid . "','" . $purchaseid . "','" . $currentDate . "','{$paymenttype}','";
        $customplSql .= $userid . "','{$amount}',NULL,'{$amount}',0,'{$amount}','{$amount}'";
        $customplSql .= ",1,'','购卡','1','{$cardinfo['panterid']}','" . $userid . "',NULL,'0',";
        $customplSql .= "'0',NULL,'00000000','" . date('H:i:s') . "','" . $checkDate . "','" . date('H:i:s', time() + 300) . "',NULL)";
        $customplIf = $this->model->execute($customplSql);
        //写入审核单
        //$auditid=$this->getnextcode('audit_logs',16);
        $auditid = $this->getFieldNextNumber('auditid');
        $auditlogsSql = "insert into audit_logs(auditid,purchaseid,TYPE,decription,placeddate,audituser,placedtime) values ('" . $auditid . "','" . $purchaseid . "','审核通过',";
        $auditlogsSql .= "'购卡审核通过','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s', time() + 300) . "')";

        $auditlogsIf = $this->model->execute($auditlogsSql);

        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
        //写入购卡充值单

        $cardplSql = "INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
        $cardplSql .= "FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql .= "'{$cardno}','{$amount}','0','" . $currentDate . "','" . date('H:i:s', time() + 300) . "','1','{$description}',";
        $cardplSql .= "'{$userid}','{$cardinfo['panterid']}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        $where1['customid'] = $customid;
        $card = $this->cards->where($where1)->find();
        if ($card == false) {
            //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
            $cardId = $customid;
        } else {
            //若存在，则需另外生成卡编号
            //$cardId=$this->getnextcode('customs',8);
            $cardId = $this->getFieldNextNumber("customid");
            $customSql = "UPDATE customs SET cardno='teshu' where customid='" . $customid . "'";
            $customIf = $this->model->execute($customSql);
        }
        //echo $cardId;exit;
        //执行激活操作
        $cardAlSql = "INSERT INTO card_active_logs(CARDNO,USERID,EXDATE,CARDBALANCE,STATUS,LINKTEL,ACTIVEDATE,ACTIVETIME,DESCRIPTION,CUSTOMID,PANTERID,TERMINAL_ID) ";
        $cardAlSql .= " VALUES('{$cardno}','{$userid}'," . date('Ymd');
        $cardAlSql .= ",'0','Y','00'," . date('Ymd') . ",'" . date('H:i:s') . "','售卡激活','{$cardId}'";
        $cardAlSql .= ",'{$cardinfo['panterid']}','00000000')";
        $cardAlIf = $this->model->execute($cardAlSql);
        //echo $cardAlSql;exit;
        //关联会员卡号
        $customcSql = "INSERT INTO customs_c(customid,cid) VALUES('" . $customid . "','" . $cardId . "')";
        $customsIf = $this->model->execute($customcSql);

        //更新卡状态为正常卡，更新卡有效期；
        $exd = date('Ymd', strtotime("+3 years"));
        $cardSql = "UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$amount}',exdate='{$exd}' where cardno='" . $cardno . "'";
        $cardIf = $this->model->execute($cardSql);
        //echo $this->model->getLastSql();exit;

        //给卡片添加账户并给账户充值
        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $balanceSql = "INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $balanceSql .= $acid . "','" . $cardId . "','" . $amount . "','00',NULL)";
        $balanceIf = $this->model->execute($balanceSql);

        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $coinSql = "INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $coinSql .= $acid . "','" . $cardId . "','0','01',NULL)";
        $coinIf = $this->model->execute($coinSql);

        //$acid = $this->getnextcode('account',8);
        $acid = $this->getFieldNextNumber('accountid');
        $pointSql = "INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
        $pointSql .= $acid . "','" . $cardId . "','0','04',NULL)";
        $pointIf = $this->model->execute($pointSql);

        if ($customplIf == true && $auditlogsIf == true && $cardplIf == true && $cardAlIf == true && $customsIf == true && $cardIf == true && $balanceIf == true && $pointIf == true && $coinIf == true) {
            return $cardpurchaseid;
        } else {
            return false;
        }
    }

    //充值执行
    protected function tzRecharge($cardno, $customid, $rechargeAmount, $panterid, $userid, $paymenttype = '现金', $description = null)
    {
        if (empty($cardno)) return false;
        $userstr = substr($userid, 12, 4);
        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
        $purchaseid = $this->getFieldNextNumber("purchaseid");
        $purchaseid = $userstr . $purchaseid;
        $currentDate = date('Ymd');
        $checkDate = date('Ymd');
        $where['cardno'] = $cardno;
        $card = $this->cards->where($where)->field('customid')->find();
        $customplSql = "insert into custom_purchase_logs values('" . $customid . "','" . $purchaseid . "','" . $currentDate . "','{$paymenttype}','";
        $customplSql .= $userid . "','" . $rechargeAmount . "',NULL,'" . $rechargeAmount . "',0,'" . $rechargeAmount . "','" . $rechargeAmount;
        $customplSql .= "',1,'','','1','" . $card['panterid'] . "','" . $userid . "',NULL,'1',";
        $customplSql .= "'0',NULL,'00000000','" . date('H:i:s') . "','" . $checkDate . "','" . date('H:i:s', time() + 300) . "',NULL)";

        //写入审核单
        //$auditid=$this->getnextcode('audit_logs',16);
        $auditid = $this->getFieldNextNumber('auditid');
        $auditlogsSql = "insert into audit_logs values ('" . $auditid . "','" . $purchaseid . "','审核通过',";
        $auditlogsSql .= "'充值审核通过','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s', time() + 300) . "')";

        $customplIf = $this->model->execute($customplSql);
        $auditlogsIf = $this->model->execute($auditlogsSql);

        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');

        //写入充值单
        $cardplSql = "INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $cardplSql .= "'{$cardno}','{$rechargeAmount}','0','" . $currentDate . "','" . date('H:i:s', time() + 300) . "','1','{$description}',";
        $cardplSql .= "'{$userid}','{$panterid}','','00000000')";
        $cardplIf = $this->model->execute($cardplSql);

        //更新卡片账户
        $balanceSql = "UPDATE account SET amount=nvl(amount,0)+" . $rechargeAmount . " where customid='" . $card['customid'] . "' and type='00'";
        $balanceIf = $this->model->execute($balanceSql);


        if ($customplIf == true && $auditlogsIf == true && $cardplIf == true && $balanceIf == true) {
            return $cardpurchaseid;
        } else {
            return false;
        }
    }
    protected function tzPointRechargeExe($cardInfo, $pointAmount, $panterid, $userid, $balancePurchaseid)
    {
        $cardid = $cardInfo['cardid'];
        $cardno = $cardInfo['cardno'];
        $accountid = $cardInfo['accountid'];
        $type = $cardInfo['type'];
        $validity = $cardInfo['validity'];
        $nowdate = date('Ymd');
        $nowtime = date('H:i:s');
        if (empty($validity)) {
            $enddate = 0;
        } else {
            $enddate = date('Ymd', strtotime('+' . $validity . ' month', time()));
        }
        if ($userid == null) {
            $userid = $this->hotelUserid;
        }
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
        $purchaseid = substr($cardpurchaseid, 1, 16);
        $userip = '';
        $cardplSql = "INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','";
        $cardplSql .= trim($cardno) . "',0,'{$pointAmount}','{$nowdate}','{$nowtime}','1','天筑充值单号:{$balancePurchaseid}','";
        $cardplSql .= $userid . "','{$panterid}','{$userip}','00000000')";
        $accountSql = "UPDATE account SET amount=nvl(amount,0)+'" . $pointAmount . "' where customid='" . $cardid . "' and type='04'";

        $cardplIf = $this->model->execute($cardplSql);
        $accountIf = $this->model->execute($accountSql);

        //$pointid=$this->getnextcode('pointid',8);
        $pointid = $this->getFieldNextNumber('pointid');
        $pointSql = "INSERT INTO point_account values('{$accountid}','{$pointAmount}','{$pointAmount}','{$nowdate}'";
        $pointSql .= ",'{$nowtime}','{$panterid}','{$cardid}','{$pointid}','','{$cardpurchaseid}','{$enddate}','{$type}')";
        $pointIf = $this->model->execute($pointSql);
        if ($cardplIf == true && $accountIf == true && $pointIf == true) {
            return true;
        } else {
            return false;
        }
    }

    //消费明细报表
    public function consume(){
        $model=new Model();
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $cardno	= trim(I('get.cardno',''));
        $cuname	= trim(I('get.cuname',''));
        $pname	= trim(I('get.pname',''));
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
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($pname!=''){
            $where['tp.pname']=$pname;
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        $field='tw.*,cu.namechinese cuname,tp.pname';
        $count=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('tw.placeddate desc')->select();
        session('consumeCon',$where);
        // echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //消费明细报表导出
    public function consume_excel(){
        $where=session('consumeCon');
        $model=new Model();
        $field='tw.*,cu.namechinese cuname,tp.pname';

        $list=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)
            ->order('tw.placeddate desc')->select();

        $consumeInfo=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field('sum(tradeamount) amount,sum(tradepoint) point')
            ->order('tw.placeddate desc')->find();

        $strlist = "卡号,会员名,商品名称,消费数量,消费金额,消费积分,消费时间,交易流水号\n";
        $strlist = $this->changeCode($strlist);
        foreach($list as $key=>$val){
            $cuname=$this->changeCode($val['cuname']);
            $pname=$this->changeCode($val['pname']);

            $strlist.=$val['cardno']."\t".','.$cuname. ','.$pname.','.$val['tradenum'].',';
            $strlist.=floatval($val['tradeamount'])."\t".','.floatval($val['tradepoint']).',';
            $strlist.=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'].','.$val['tradeid']."\t\n";
        }
        $filename = '消费报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $strlist .= ',,'.$this->changeCode('总计:').',,' .$consumeInfo['amount'] ."\t".','.$consumeInfo['point']."\t".',,'."\n";
        $this->load_csv($strlist, $filename);
    }

    //商品统计报表
    public function productStat(){
        $model=new Model();
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $pname=trim(I('get.pname'));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $condition="where placeddate>='{$startdate}'";
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $condition="where placeddate<='{$enddate}'";
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $condition="where placeddate>='{$startdate}' and placeddate<='{$enddate}'";
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $condition="where placeddate>='{$startdate}' and placeddate<='{$enddate}'";
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($pname)){
            $where['tp.pname']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        $subQuery="(select tradepid,sum(tradeamount) amount,sum(tradepoint) point from tz_wastebooks {$condition} and cardno like '6666%' group by tradepid)";
        //echo $subQuery;
        $where['tc.panterid']='00000792';
        $field='a.amount,a.tradepid,a.point,tp.pname,tc.catename';
        $list=$model->table($subQuery)->alias('a')
            ->join('tz_product tp on tp.pid=a.tradepid')
            ->join('tz_cate tc on tp.cateid=tc.cateid')
            ->where($where)->field($field)->select();
        $this->assign('list',$list);
        $this->display();
    }

    //类别交易统计报表
    public function cateStat(){
        $model=new Model();
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $cateid=trim(I('get.cateid'));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $condition="where placdddate>='{$startdate}'";
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $condition="where placdddate<='{$enddate}'";
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $condition="where placdddate>='{$startdate}' and placeddate<='{$enddate}'";
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $condition="where placdddate>='{$startdate}' and placeddate<='{$enddate}'";
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($cateid)){
            if($cateid!=6){
                $where['a.cateid']=$cateid;
            }else{
                $where['a.cateid']=array('in','7,8,9');
            }
            $this->assign('cateid',$cateid);
            $map['cateid']=$cateid;
        }
        $subQuery="(select tc.cateid,tc.catename,sum(tw.tradeamount) amount,sum(tw.tradepoint) point from tz_wastebooks";
        $subQuery.=" tw inner join tz_product tp on tp.pid=tw.tradepid inner join tz_cate tc on tc.cateid=tp.cateid  ";
        $subQuery.="group by tc.cateid,tc.catename)";
        $field='a.cateid,a.catename,a.amount,a.point';
        $list=$model->table($subQuery)->alias('a')
            ->where($where)->field($field)->select();
        foreach($list as $key=>$val){
            if(in_array($val['cateid'],array(7,8,9))){
                $amount+=$val['amount'];
                $point+=$val['point'];
                unset($list[$key]);
            }
        }
        if($amount>0||$point>0){
            $list[]=array('cate'=>'6','catename'=>'下沉式花园','amount'=>$amount,'point'=>$point);
        }
        $map=array('catelevel'=>1);
        $cate_list=M('tz_cate')->where($map)->field('cateid,catename')->order('cateid asc')->select();
        $this->assign('list',$list);
        $this->assign('catelist',$cate_list);
        $this->display();
    }

    public function customs(){
        $model=new Model();
        $cuname	= trim(I('get.cuname',''));
        $linktel	= trim(I('get.linktel',''));
        $personid	= trim(I('get.personid',''));
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($linktel!=''){
            $where['cu.linktel']=$linktel;
            $this->assign('linktel',$linktel);
            $map['linktel']=$personid;
        }
        if($personid!=''){
            $where['cu.personid']=$personid;
            $this->assign('personid',$personid);
            $map['personid']=$personid;
        }
        $where['customlevel']='天筑会员';
        $where['c.status']='Y';
        $field='cu.namechinese cuname,cu.linktel,cu.personid,cu.sex,cu.birthday,';
        $field.='cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno';
        $count=$model->table('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cu.customid asc')->select();
        session('customsCon',$where);
        // echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    public function customs_excel(){
        $where=session('customsCon');
        $model=new Model();
        $field='cu.namechinese cuname,cu.linktel,cu.personid,cu.sex,cu.birthday,';
        $field.='cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno';

        $list=$model->table('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)
            ->order('cu.customid asc')->select();

        $strlist = "会员名称,手机号,身份证号,身份证有效期,生日,性别,地址,天筑卡卡号\n";
        $strlist = $this->changeCode($strlist);
        foreach($list as $key=>$val){
            $cuname=$this->changeCode($val['cuname']);
            $sex=$this->changeCode($val['sex']);
            $residaddress=$this->changeCode($val['residaddress']);
            $personidissuedate=$this->changeCode($val['personidissuedate']);
            $personidexdate=$this->changeCode($val['personidexdate']);

            $strlist.=$cuname.','.$val['linktel']. ','.$val['personid']."\t".',';
            $strlist.=$personidissuedate.'———'.$personidexdate.',';
            $strlist.=$val['birthday'].','.$sex.','.$residaddress.','.$val['cardno']."\t\n";
        }
        $filename = '会员报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist, $filename);
    }

    //商品列表
    public function cateManage(){
        $cateid = trim(I('get.cateid',''));
        $pname = trim(I('get.pname',''));
        if(!empty($cateid)){
            $where['c.cateid']=$cateid;
            $this->assign('cateid',$cateid);
            $map['cateid']=$cateid;
        }
        if(!empty($pname)){
            $where['p.pname']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        $where['c.panterid']='00000792';
        $pro=M('tz_product');
        $cateList=$this->getCateList();
        $proList=$pro->alias('p')->join('tz_cate c on c.cateid=p.cateid')->where($where)
            ->field('p.pname,p.pid,p.price,p.stroenum,c.catename,p.status')->select();
        $this->assign('cate',$cateList);
        $this->assign('list',$proList);
        $this->display();
    }

    //添加商品
    public function addProduct(){
        if(IS_POST){
            $cateid=$_POST['cateid'];
            $price=$_POST['price'];
            $stroenum=$_POST['stroenum'];
            $pname=$_POST['pname'];
            $status=$_POST['status'];
            if(empty($cateid)){
                $this->error('品类id缺失');
            }
            if(empty($pname)){
                $this->error('商品名称缺失');
            }
            if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$price)){
                $this->error('价格格式错误');
            }
            if($stroenum==''||$stroenum==-1){
                $stroenum='-1';
            }else{
                if(!preg_match('/^[0-9]+$/',$stroenum)){
                    $this->error('库存格式错误');
                }
            }
            $pid=$this->getFieldNextNumber('tzpid');
            $sql="insert into tz_product values ('{$pid}','{$pname}','{$cateid}','{$price}','{$stroenum}','{$status}')";
            $model=new Model();
            if($model->execute($sql)){
                $this->success('商品添加成功',U('Tianzhu/cateManage'));
            }else{
                $this->success('商品添加失败');
            }
        }else{
            $cateList=$this->getCateList();
            $this->assign('cate',$cateList);
            $this->display();
        }
    }

    //编辑商品
    public function editProduct(){
        if(IS_POST){
            $pid=$_REQUEST['pid'];
            $pname=$_REQUEST['pname'];
            $price=$_REQUEST['price'];
            if(empty($pid)){
                $this->error('商品id缺失');
            }
            $model=new Model();
            $product=$model->table('tz_product')->where(array('pid'=>$pid))->find();
            if($product==false){
                $this->error('查无此商品信息');
            }
            if($product['price']!=$price){
                $currentDate=date('Ymd');
                $currentTime=date('H:i:s');
                $userid=$this->getUserid();
                $sql1="insert into tz_price_logs values('{$pid}','{$price}','{$currentDate}','{$currentTime}','{$userid}')";
                $model->execute($sql1);
            }
            $sql="update tz_product set pname='{$pname}',price='{$price}' where pid='{$pid}'";
            if($model->execute($sql)){
                $this->success('商品编辑成功',U('Tianzhu/cateManage'));
            }else{
                $this->success('商品编辑失败');
            }
        }else{
            $pid=trim($_REQUEST['pid']);
            if(empty($pid)){
                $this->error('商品id缺失');
            }
            $product=M('tz_product')->alias('p')->join('tz_cate c on c.cateid=p.cateid')
                ->where(array('p.pid'=>$pid))->field('p.*,c.catename')->find();
            if($product==false){
                $this->error('查无此商品信息');
            }
            $this->assign('product',$product);
            $this->display();
        }
    }

    //获取品类列表
    public function getCateList(){
        $cate=M('tz_cate');
        $cateList=$cate->where(array('catelevel'=>1,'panterid'=>'00000792'))->select();
        //$cateList=$cate->where(array('catelevel'=>1))->select();
        foreach($cateList as $key=>$val){
            $children=$cate->where(array('parentid'=>$val['cateid']))->field('cateid,catename')->select();
            if($children!=false){
                $cateList[$key]['children']=$children;
            }
        }
        return $cateList;
    }

    //商品上下架管理
    public function productManage(){
        $pid=$_REQUEST['pid'];
        $status=$_REQUEST['status'];
        $model=new Model();
        $product=$model->table('tz_product')->where(array('pid'=>$pid))->find();
        if($product==false){
            json_encode(array('status'=>'01','msg'=>'查无此信息'));
        }
        $sql="update tz_product set status='{$status}' where pid='{$pid}'";
        if($status==1){
            if($model->execute($sql)){
                echo json_encode(array('status'=>'1','msg'=>'上架成功'));
            }else{
                echo json_encode(array('status'=>'02','msg'=>'上架失败'));
            }
        }else{
            if($model->execute($sql)){
                echo json_encode(array('status'=>'1','msg'=>'下架成功'));
            }else{
                echo json_encode(array('status'=>'02','msg'=>'下架失败'));
            }
        }
    }

    public function addStore(){
        if(IS_POST){
            $pid=$_REQUEST['pid'];
            $pname=$_REQUEST['pname'];
            $stroenum=$_REQUEST['stroenum'];
            if(empty($pid)){
                $this->error('商品id缺失');
            }
            $model=new Model();
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $userid=$this->getUserid();
            $product=$model->table('tz_product')->where(array('pid'=>$pid))->find();
            if($product['stroenum']=='-1'){
                $sql1="update tz_product set stroenum=$stroenum where pid='{$pid}'";
            }else{
                $sql1="update tz_product set stroenum=stroenum+$stroenum where pid='{$pid}'";
            }
            $sql2="insert into tz_store_logs values('{$pid}','{$stroenum}','{$currentDate}','{$currentTime}','{$userid}','1')";
            if($model->execute($sql1)&&$model->execute($sql2)){
                $this->success('库存添加成功');
            }else{
                $this->error('库存添加失败');
            }
        }else{
            $pid=trim($_REQUEST['pid']);
            if(empty($pid)){
                $this->error('商品id缺失');
            }
            $product=M('tz_product')->alias('p')->join('tz_cate c on c.cateid=p.cateid')
                ->where(array('p.pid'=>$pid))->field('p.*,c.catename')->find();
            if($product==false){
                $this->error('查无此商品信息');
            }
            $this->assign('product',$product);
            $this->display();
        }
    }

    //综合统计数据
    public function pointStat(){
        $cardPl=M('card_purchase_logs');
        $map1=array('cl.paymenttype'=>'普通充值','c.cardkind'=>'6666');
        $rechargeAmount=$cardPl->alias('cpl')->join('custom_purchase_logs cl on cl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')->where($map1)->sum('cpl.amount');
        $rechargeAmount=empty($rechargeAmount)?0:$rechargeAmount;
        $map2=array('c.cardkind'=>'6666','cpl.point'=>array('gt',0));
        $pointAmount=$cardPl->alias('cpl')->join('cards c on c.cardno=cpl.cardno')->where($map2)->sum('cpl.point');

        $consumeAmount=M('tz_wastebooks')->sum('tradeamount');
        $consumePoint=M('tz_wastebooks')->sum('tradepoint');

        $this->assign('rechargeAmount',$rechargeAmount);
        $this->assign('pointAmount',$pointAmount);
        $this->assign('consumeAmount',$consumeAmount);
        $this->assign('consumePoint',$consumePoint);
        $this->display();
    }
    //充值明细
    public function rechargeDetail(){
        $model=new Model();
        $where['c.cardkind']='6666';
        $where['cupl.paymenttype']='普通充值';
        $where['cpl.amount']=array('gt',0);
        $field='cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno,cpl.description';
        $count=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();
        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cpl.card_purchaseid desc')->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //积分赠送明细
    public function pointDetail(){
        $model=new Model();
        $where['c.cardkind']='6666';
        $field='cupl.paymenttype,cu.namechinese cuname,cu.linktel,cpl.card_purchaseid,cpl.amount,cpl.placeddate,cpl.placedtime,';
        $field.='c.cardno,cpl.description';
        $count=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cpl.card_purchaseid desc')->select();
        session('propertyRechargeCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        foreach($list as $key=>$val){
            $map=array('description'=>'天筑充值单号:'.$val['card_purchaseid']);
            $pointPurchase=$model->table('card_purchase_logs')->where($map)->field('point')->find();
            $list[$key]['point']=$pointPurchase['point'];
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //积分消费明细
    public function pointConsumeDetail(){
        $model=new Model();
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $where['tw.tradepoint']=array('gt',0);
        $field='tw.*,cu.namechinese cuname,tp.pname';
        $count=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('tw.placeddate desc')->select();
        session('consumeCon',$where);
        // echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    //余额消费明细
    public function consumeDetail(){
        $model=new Model();
        //$where['c.cardno']='6880374868800000237';
        $where['c.cardkind']='6666';
        $where['tw.tradeamount']=array('gt',0);
        $field='tw.*,cu.namechinese cuname,tp.pname';
        $count=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('tz_wastebooks')->alias('tw')
            ->join('tz_product tp on tp.pid=tw.tradepid')
            ->join('cards c on c.cardno=tw.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('tw.placeddate desc')->select();
        session('consumeCon',$where);
        // echo $model->getLastSql();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
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
                $this->error('请输入查询条件',U('Tianzhu/lossCard'));
            }
            $where['c.cardkind']='6666';
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

            $coinList=$model->table('point_account')->where(array('cardid'=>$old_card['customid']))->select();
            if($coinList!=false){
                $sql6="update point_account set cardid='{$new_customid}' where cardid='{$old_card['customid']}'";
                $sql7="update point_account set cardid='{$new_customid}' where cardid='{$old_card['customid']}'";
                $model->execute($sql6);
                $model->execute($sql7);
            }

            $text="补卡卡号：{$cardno},新卡卡号：{$newcard},补卡时间".date('Y-m-d H:i:s')."\r\n";
            $text.="操作sql日志：{$sql1}"."\r\n"."{$sql2}"."\r\n"."{$sql3}"."\r\n"."{$sql4}"."\r\n"."{$sql5}"."\r\n";
            $text.="{$sql6}"."\r\n"."{$sql7}";
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
            $where['c.status']='L';
            $where['a.type']='00';
            $where['a1.type']='01';
            $where['cll.active']=0;
            $where['c.cardkind']='6666';
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
            $this->display();
        }
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
            if($card2['cardkind']!='6666'){
                $res['msg']='所填新卡号不是新卡或是非法卡';
                echo json_encode($res);
                exit;
            }
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
        $where['a1.type']='04';
        $where['cll.active']=2;
        $where['c.cardkind']='6666';
        $field='c.cardno,cu.namechinese cuname,cu.customid,c.status,a.amount balanceamount,a1.amount pointamount,ccl.cardno newcard,cu.personid,cu.linktel';
        $count=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            //->join('__ACCOUNT__ a1 on a1.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
            ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
            ->join('left join __CARD_CHANGE_LOGS__ ccl on ccl.lockid=cl.lockid')
            ->join('cards c1 on c1.cardno=ccl.cardno')
            ->join('__ACCOUNT__ a1 on a1.customid=c1.customid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 10);
        $reissueCard=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            //->join('__ACCOUNT__ a1 on a1.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__CARD_LOCKS__ cl on c.cardno=cl.cardno')
            ->join('__CARD_LOCKS_LOG__ cll on cl.lockid=cll.lockid')
            ->join('left join __CARD_CHANGE_LOGS__ ccl on ccl.lockid=cl.lockid')
            ->join('cards c1 on c1.cardno=ccl.cardno')
            ->join('__ACCOUNT__ a1 on a1.customid=c1.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('list',$reissueCard);
        $this->display();
    }

    function balance(){
        $model=new Model();
        $cuname	= trim(I('get.cuname',''));
        $linktel	= trim(I('get.linktel',''));
        $personid	= trim(I('get.personid',''));
        $cardno=trim(I('get.cardno',''));
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($linktel!=''){
            $where['cu.linktel']=$linktel;
            $this->assign('linktel',$linktel);
            $map['linktel']=$personid;
        }
        if($personid!=''){
            $where['cu.personid']=$personid;
            $this->assign('personid',$personid);
            $map['personid']=$personid;
        }
        $where['cu.customlevel']='天筑会员';
        $where['c.cardkind']='6666';
        $where['c.status']='Y';
        $where['a.type']='04';
        $where['a1.type']='00';
        $field='cu.namechinese cuname,cu.linktel,cu.personid,cu.sex,cu.birthday,';
        $field.='cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno,a.amount point,a1.amount balance';
        $count=$model->table('cards')->alias('c')
            ->join('account a1 on a1.customid=c.customid')
            ->join('account a on a.customid=c.customid')
            ->join('customs_c cc on c.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();

        $this->assign('count',$count);

        $p=new \Think\Page($count,10);
        $list=$model->table('cards')->alias('c')
            ->join('account a1 on a1.customid=c.customid')
            ->join('account a on a.customid=c.customid')
            ->join('customs_c cc on c.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('cu.customid asc')->select();
        session('balanceCon',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        foreach($list as $K=>$v){
            $rechagetPoint=$model->table('card_purchase_logs')->alias('cpl')
                ->where(array('cardno'=>$v['cardno'],'point'=>array('gt',0)))->sum('point');
            $consumePoint=$model->table('tz_wastebooks')
                ->where(array('cardno'=>$v['cardno'],'tradepoint'=>array('gt',0)))
                ->sum('tradepoint');
            $list[$K]['rechagetpoint']=$rechagetPoint==null?0:$rechagetPoint;
            $list[$K]['consumepoint']=$consumePoint==null?0:$consumePoint;
        }
        //print_r($list);
        $page = $p->show ();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }

    function balance_excel(){
        $where=session('balanceCon');
        $model=new Model();
        $field='cu.namechinese cuname,cu.linktel,cu.personid,cu.sex,cu.birthday,';
        $field.='cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno,';
        $field.='a.amount point,a1.amount balance,s1.rechargepoint,s2.consumepoint';

        $subQuery1="(select cardno,sum(point) rechargepoint from card_purchase_logs where point>0 group by cardno)";
        $subQuery2="(select cardno,sum(tradepoint) consumepoint from tz_wastebooks where tradepoint>0 group by cardno)";

        $list=$model->table('cards')->alias('c')
            ->join('account a1 on a1.customid=c.customid')
            ->join('account a on a.customid=c.customid')
            ->join('customs_c cc on c.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('left join '.$subQuery1 .' s1 on s1.cardno=c.cardno')
            ->join('left join '.$subQuery2 .' s2 on s2.cardno=c.cardno')
            ->where($where)->field($field)
            ->order('cu.customid asc')
            ->select();

        $strlist = "卡号,会员名称,电话,身份证号,卡余额,天筑积分,积分充值金额,积分消费金额\n";
        $strlist = $this->changeCode($strlist);
        foreach($list as $key=>$val){
            $cuname=$this->changeCode($val['cuname']);
            $consumepoint=$val['consumepoint']==null?0:$val['consumepoint'];

            $strlist.=$val['cardno']."\t".','.$cuname. ','.$val['linktel']."\t".',';
            $strlist.=$val['personid']."\t".','.floatval($val['balance']).',';
            $strlist.=floatval($val['point']).','.floatval($val['rechargepoint']).','.floatval($val['consumepoint'])."\n";
        }
        $filename = '天筑荟卡余额报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist, $filename);
    }
    //卡积分过期表
    function penddate()
    {
        $model = new Model();
        $cuname = trim(I('get.cuname', ''));
        $linktel = trim(I('get.linktel', ''));
        $personid = trim(I('get.personid', ''));
        $cardno = trim(I('get.cardno', ''));
        $pacid = trim(I('get.pacid', ''));
//        $enddate=trim(I('get.enddate', ''));
        $validate = trim(I('get.validate',''));
        $date = date('Ymd');
        if($cardno==''){
            if ($validate == 'all') {
                $where['pa.enddate'] =array('egt',$date);
            }
            if ($validate == 'effective') {
                $where['pa.enddate'] = array('egt',$date);
            } elseif ($validate == "overdue") {
//                $where['pa.enddate'] = "(pa.enddate<=$date)";
                $where['pa.enddate']=array('lt',$date);
            }
            $this->assign('validate', $validate);
            $map['validate'] = $validate;
        }else{
            $where['c.cardno'] =$cardno;
            $map['cardno'] = $cardno;
        }
//        dump ($where);exit;
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
            $this->assign('linktel', $linktel);
            $map['linktel'] = $personid;
        }
        if ($personid != '') {
            $where['cu.personid'] = $personid;
            $this->assign('personid', $personid);
            $map['personid'] = $personid;
        }
        if ($pacid != '') {
            $where['pa.cardpurchaseid'] = $pacid;
            $this->assign('pacid', $pacid);
            $map['pacid'] = $pacid;
        }
        $where['cu.customlevel'] = '天筑会员';
        $where['c.cardkind'] = '6666';
        $where['c.status'] = 'Y';
        $where['a.type'] = '04';
//            $date=date('Ymd');
//            $where['pa.enddate'] < $date;
        $where['pa.panterid'] = '00000792';
            $field = 'cu.namechinese cuname,cu.linktel,cu.personid,cu.birthday,';
            $field .= 'cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno,a.amount point, pa.rechargeAmount pamount, pa.remindAmount paramount,pa.placeddate placeddate,pa.placedtime placedtime,pa.enddate enddate,pa.cardpurchaseid  pacid';
            $count = $model->table('cards')->alias('c')
                ->join('account a on a.customid=c.customid')
                ->join('point_account pa on pa.cardid=a.customid')
                ->join('customs_c cc on c.customid=cc.cid')
                ->join('customs cu on cu.customid=cc.customid')
                ->where($where)->field($field)->count();
//          echo $model->getLastSql();exit;
            $this->assign('count', $count);

            $p = new \Think\Page($count, 10);
            $list = $model->table('cards')->alias('c')
                ->join('account a on a.customid=c.customid')
                ->join('point_account pa on pa.cardid=a.customid')
                ->join('customs_c cc on c.customid=cc.cid')
                ->join('customs cu on cu.customid=cc.customid')
                ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
                ->order('cu.customid asc')->select();
//        echo $model->getLastSql();exit;
            session('penddate', $where);
            if (!empty($map)) {
                foreach ($map as $key => $val) {
                    $p->parameter[$key] = $val;
                }
            }
//            foreach($list as $K=>$v){
//                $rechagetPoint=$model->table('card_purchase_logs')->alias('cpl')
//                    ->where(array('cardno'=>$v['cardno'],'point'=>array('gt',0)))->sum('point');
//                $consumePoint=$model->table('tz_wastebooks')
//                    ->where(array('cardno'=>$v['cardno'],'tradepoint'=>array('gt',0)))
//                    ->sum('tradepoint');
//                $list[$K]['rechagetpoint']=$rechagetPoint==null?0:$rechagetPoint;
//                $list[$K]['consumepoint']=$consumePoint==null?0:$consumePoint;
//            }
            //print_r($list);
            $page = $p->show();
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->display();

        }
    function penddate_excel(){
        $where=session('penddate');
        $model=new Model();
        $field = 'cu.namechinese cuname,cu.linktel,cu.personid,cu.birthday,';
        $field .= 'cu.personidissuedate,cu.personidexdate,cu.residaddress,c.cardno,a.amount point, pa.rechargeAmount pamount, pa.remindAmount paramount,pa.placeddate placeddate,pa.placedtime placedtime,pa.enddate enddate,pa.cardpurchaseid  pacid';

        $list = $model->table('cards')->alias('c')
            ->join('account a on a.customid=c.customid')
            ->join('point_account pa on pa.cardid=a.customid')
            ->join('customs_c cc on c.customid=cc.cid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('cu.customid asc')->select();

        $strlist = "卡号,会员名称,电话,身份证号,赠送积分,剩余积分,赠送时间,过期时间,积分充值单号,总积分\n";
        $strlist = $this->changeCode($strlist);
        foreach($list as $key=>$val){
            $cuname=$this->changeCode($val['cuname']);
            $strlist.=$val['cardno']."\t".','.$cuname. ','.$val['linktel']."\t".',';
            $strlist.=$val['personid']."\t".','.floatval($val['pamount']).',';
            $strlist.=floatval($val['paramount']).',';
            $strlist.=date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime'])).', '.$val['enddate'].','
                .$val['pacid']."\t".','
                .$val['point']."\t\n";
        }
        $filename = '天筑会赠送积分明细报表' . date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        $this->load_csv($strlist, $filename);
    }
    //根据积分充值单号批量更改积分有效期
    public function tzpoint_excel(){
        set_time_limit(0);
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls")
            {
                $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
//            $savePath = './public/upfile/Excel/';
            $savePath = PUBLIC_PATH.'upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name);
            // dump($exceldate);exit;
            $point_account=M('point_account');
            $counts=0;
            $batchbuyBatchLog=array();
            $err = false;
            $ksnum=1;
            foreach ($exceldate as $key => $value) {
                if($ksnum==300){
                    $ksnum=1;
                    sleep(1);
                }
                $ksnum++;
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                if(array_key_exists("A",$value)){
                    $batchRechargeLog[$key]['cardpurchaseid']=$cardpurchaseid=trim($value['A']);
                    $batchRechargeLog[$key]['enddate']=$enddate=trim($value['B']);
                }else{
                    $batchRechargeLog[$key]['cardpurchaseid']=$cardpurchaseid=trim($value[0]);
                    $batchRechargeLog[$key]['enddate']=$enddate=trim($value[1]);
                }
                // $where=array();
                if($cardpurchaseid!=''){
                    $where['cardpurchaseid']=$cardpurchaseid;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='积分充值单号不能为空，请核实!';
                    continue;
                }
                $point=$point_account->where($where)->find();
                // dump($point);exit;
                if($point==true){
                    $point_enddate = $point_account->execute("update point_account set enddate= '{$enddate}' where cardpurchaseid= '{$cardpurchaseid}'");
                    if($point_enddate == true){
                        $batchbuyBatchLog[$key]['status']=1;
                        $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                        $batchbuyBatchLog[$key]['msg']='更新积分有效期成功';
                    }else{
                        $batchbuyBatchLog[$key]['status']=0;
                        $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                        $batchbuyBatchLog[$key]['msg']='积分充值单号'.$cardpurchaseid.'更新有效期失败,请联系系统管理员!sql:' .$point->getLastSql();
                    }
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                    $batchbuyBatchLog[$key]['msg']='积分充值单号'.$cardpurchaseid.'不存在,请联系系统管理员!sql:' .$point->getLastSql();
                }
                $batchbuyBatchLog[$key]['status']=1;
                $counts++;
            }
            if(!empty($batchbuyBatchLog)){
                $this->tzpointLogs($batchbuyBatchLog);
            }
            $nun = count($exceldate) - $counts;
            if ($err){
                $this->success('成功更改积分有效期'.$counts.'户,<br /><font color=red>其中<b>'.$nun.'</b>户异常</font>',U('Tianzhu/tzpoint_excel'), 5);
            }else{
                $this->success('成功更改积分有效期'.$counts.'户',U('Tianzhu/tzpoint_excel'),5);
            }
        }
        $this->display();
    }
    //写入批量更改积分有效期日志
    public function tzpointLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='积分充值单号：'.$val['cardpurchaseid']."  ";
            if($val['status']==0){
                $msgString.='状态：批量更新积分有效期失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量更新积分有效期成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('tzpointLogs',$msgString);
    }
    //返还 天筑积分
    public function tzcxpoint_excel(){
        set_time_limit(0);
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls")
            {
                $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
//            $savePath = './public/upfile/Excel/';
            $savePath = PUBLIC_PATH.'upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name);
            $point_account=M('point_account');
            $cards  = M('cards');
            $tz_wastebooks  = M('tz_wastebooks');
            $account = M('account');
            $point_consume =M('point_consume');
            $counts=0;
            $batchbuyBatchLog=array();
            $err = false;
            $ksnum=1;
            foreach ($exceldate as $key => $value) {
                if($ksnum==300){
                    $ksnum=1;
                    sleep(1);
                }
                $ksnum++;
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                if(array_key_exists("A",$value)){
                    $batchRechargeLog[$key]['cardpurchaseid']=$cardpurchaseid=trim($value['A']);
                    $batchRechargeLog[$key]['enddate']=$enddate=trim($value['B']);
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value['C']);
                }else{
                    $batchRechargeLog[$key]['cardpurchaseid']=$cardpurchaseid=trim($value[0]);
                    $batchRechargeLog[$key]['enddate']=$enddate=trim($value[1]);
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value[2]);
                }
                // $where=array();
                if($cardpurchaseid!=''){
                    $where['cardpurchaseid']=$cardpurchaseid;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='积分充值单号不能为空，请核实!';
                    continue;
                }
                if($cardno=''){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    continue;
                }
                if($enddate=''){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='有效期不能为空，请核实!';
                    continue;
                }
                $point=$point_account->where($where)->find();
                // dump($point);exit;
                if($point==true){
                    $where2['pointid'] = $point['pointid'];
                    $where2['cardid'] = $point['cardid'];
                    $where2['panterid'] = '00007434';
                    $point_consumesql = $point_consume->where($where2)->find();
                    if($point_consumesql ==true){
                        $where3['customid'] = $point['cardid'];
                        $cards_sql = $cards->where($where3)->find();
                        if($cards_sql==true && trim($cards_sql['cardno']) == $cardno){
                            $where5['cardno'] = $cardno;
                            $where5['panterid'] = $panterid = '00007434' ;
                            $where5['placeddate'] = $placeddates = $point_consumesql['placeddate'] ;
                            $tz_wastebooks_sql = $tz_wastebooks->where($where5)->find();
                            if($tz_wastebooks_sql == true){
                                $tz_w_del = $tz_wastebooks->execute("delete from tz_wastebooks where cardno='{$cardno}' and placeddate ='{$placeddates}' and panterid ='{$panterid}'");
                                $up_point_account = $point_account->execute("update point_account set enddate='{$enddate}',remindamount=remindamount+'{$point_consumesql['amount']}' where  pointid ='{$point['pointid']}' and panterid = '{$panterid}' and cardid = '{$point['cardid']}'");
                                $up_account  = $account->execute("update account set amount=amount+'{$point_consumesql['amount']}' where customid='{$point['cardid']}' and type='04'");
                                $del_point_consume = $point_consume->execute("delete from point_consume  where pointid='{$point['pointid']}' and panterid = '{$panterid}'");
                                if($tz_w_del==true && $up_point_account==true && $up_account==true && $del_point_consume==true){
                                    $batchbuyBatchLog[$key]['status']=1;
                                    $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                                    $batchbuyBatchLog[$key]['msg']='返还积分成功';
                                }else{
                                    $batchbuyBatchLog[$key]['status']=0;
                                    $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                                    $batchbuyBatchLog[$key]['msg']='积分充值单号'.$cardpurchaseid.'返还积分失败!sql:' .$point->getLastSql();
                                }

                            }else{
                                $batchbuyBatchLog[$key]['status']=0;
                                $batchbuyBatchLog[$key]['cardno']=$cardno;
                                $batchbuyBatchLog[$key]['msg']='交易记录表信息查询有误' .$tz_wastebooks->getLastSql();
                            }
                        }else{
                            $batchbuyBatchLog[$key]['status']=0;
                            $batchbuyBatchLog[$key]['cardno']=$cardno;
                            $batchbuyBatchLog[$key]['msg']='卡表 cards 信息有误' .$cards->getLastSql();
                        }
                    }else{
                        $batchbuyBatchLog[$key]['status']=0;
                        $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                        $batchbuyBatchLog[$key]['msg']='积分消费明细表 POINT_CONSUME 未查到信息' .$point_consume->getLastSql();
                    }
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['cardpurchaseid']=$cardpurchaseid;
                    $batchbuyBatchLog[$key]['msg']='积分充值单号'.$cardpurchaseid.'不存在,请联系系统管理员!sql:' .$point->getLastSql();
                }
                $batchbuyBatchLog[$key]['status']=1;
                $counts++;
            }
            if(!empty($batchbuyBatchLog)){
                $this->tzpointLogs($batchbuyBatchLog);
            }
            $nun = count($exceldate) - $counts;
            if ($err){
                $this->success('积分成功返还'.$counts.'户,<br /><font color=red>其中<b>'.$nun.'</b>户异常</font>',U('Tianzhu/tzcxpoint_excel'), 5);
            }else{
                $this->success('积分成功返还'.$counts.'户',U('Tianzhu/tzcxpoint_excel'),5);
            }
        }
        $this->display();
    }
    //写入批量返还 天筑积分 日志
    public function tzcxpointLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='积分充值单号：'.$val['cardpurchaseid']."  ";
            if($val['status']==0){
                $msgString.='状态：返还积分失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量返还积分成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('tzcxpointLogs',$msgString);
    }


}
