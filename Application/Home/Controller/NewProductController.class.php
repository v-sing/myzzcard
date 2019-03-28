<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class NewProductController extends CommonController {
    public function _initialize(){
        parent::_initialize();
    }

    //营销产品充值
    function productpay(){
        $quankind = D('quankind');
        $quancz	= D('quancz');
        $quan_account=D('quan_account');
        $nowdate=date('Ymd');
        $wquank['_string']=" (q.startdate<='".$nowdate."' and q.enddate>='".$nowdate."' and q.vtype=1) or q.vtype=2 ";
        if($this->panterid!='FFFFFFFF'){
            $wquank['_string'].=" and (p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."' and q.utype=2) or q.utype=1";
        }
        $wquank['q.atype']=2;
        $quankinds= $quankind->alias('q')->join(' left join __PANTERS__ p on p.panterid=q.panterid')
            ->where($wquank)->field('q.quanid,q.quanname')->order('q.quanid desc')->select();
        $this->assign('quankinds',$quankinds);
        if(IS_POST){
            $status = trim(I('post.status',''));
            $customid = trim(I('post.customid',''));
            $paynumber = trim(I('post.paynumber',''));
            if($paynumber==''){
                $this->error('充值数量必须填写',U('NewProduct/productpay'));
            }
            $quanid = I('post.quanid','');
            if($quanid==''){
                $this->error('券类必选',U('NewProduct/productpay'));
            }
            $cardno = trim(I('post.cardno',''));
            //卡号不能为空
            if($cardno=="")
            {
                $this->error('卡号不能为空',U('NewProduct/productpay'));
            }
            $quan_info=$quankind->where(array('quanid'=>$quanid))->find();
            if($quan_info['vtype']==1){
                $startdate=$quan_info['startdate'];
                $enddate=$quan_info['enddate'];
            }elseif($quan_info['vtype']==2){
                $validaty=empty($quan_info['validaty'])?12:$quan_info['validaty'];
                $startdate=date('Ymd');
                $enddate=date('Ymd',strtotime('+ '.$validaty.' months'));
            }
            $waccount['customid']=$customid;
            $waccount['type']='02';
            $waccount['quanid']=$quanid;
            $userid =  $this->userid;
            $panterid=$this->panterid;
            $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);


            $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
            $acountsif= $quan_account->execute("INSERT INTO quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$quanid}','{$customid}','{$paynumber}','{$startdate}','{$quanpurchaseid}','{$quanaccountid}','{$enddate}')");

            $quanczs=$quancz->execute("insert into quancz values('".$quanid."','".$paynumber."','".date('Ymd')."','".date('H:i:s')."','".$panterid."','".$userid."','".$customid."','".$quanpurchaseid."',0)");
            if($acountsif==false || $quanczs==false){
                $this->error('充值失败',U('NewProduct/productpay'));
            }else{
                $this->success('充值成功',U('NewProduct/productpay'));
            }
        }else{
            $quancz=D('quancz');
            $start = I('get.startdate','');
            $end = I('get.enddate','');
            $customidc = trim(I('get.customidc',''));
            $cnamec	= trim(I('get.cnamec',''));
            $cardnoc    = trim(I('get.cardnoc',''));
            $linktel	= trim(I('get.linktel',''));
            $customid = $customidc=="会员编号"?"":$customidc;
            $cname = $cnamec=="会员名称"?"":$cnamec;
            $cardno = $cardnoc=="卡号"?"":$cardnoc;
            $linktel = $linktel=="手机号"?"":$linktel;
            if($start!='' && $end==''){
                $startdate = str_replace('-','',$start);
                $where['a.placeddate']=array('egt',$startdate);
                $this->assign('startdate',$start);
                $map['startdate']=$start;
            }
            if($start=='' && $end!=''){
                $enddate = str_replace('-','',$end);
                $where['a.placeddate'] = array('elt',$enddate);
                $this->assign('enddate',$end);
                $map['enddate']=$end;
            }
            if($start!='' && $end!=''){
                $startdate = str_replace('-','',$start);
                $enddate = str_replace('-','',$end);
                $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
                $this->assign('startdate',$start);
                $this->assign('enddate',$end);
                $map['startdate']=$start;
                $map['enddate']=$end;
            }
            if($customid!=''){
                $where['b.customid'] = $customid;
                $this->assign('customidc',$customid);
                $map['customidc']=$customid;
            }
            if($cname!=''){
                $where['b.namechinese']=array('like','%'.$cname.'%');
                $this->assign('cnamec',$cname);
                $map['cnamec']=$cname;
            }
            if($cardno!=''){
                $cardresult=M('card_change_logs')->where(array('cardno'=>$cardno))->find();
                if($cardresult){
                    $cardno=$cardresult['precardno'];
                }
                $where['f.cardno']=$cardno;
                $this->assign('cardnoc',$cardno);
                $map['cardnoc']=$cardno;
            }
            if($linktel!=''){
                $where['b.linktel']=$linktel;
                $this->assign('linktel',$linktel);
                $map['linktel']=$linktel;
            }
            if($this->panterid!='FFFFFFFF'){
                $where1['c.panterid']=$this->panterid;
                $where1['c.parent']=$this->panterid;
                $where1['_logic']='OR';
                $where['_complex']=$where1;
            }
            $where['d.atype']=2;
            $field='f.cardno,a.customid,a.placeddate,a.placedtime,a.quanid,a.userid,a.amount,a.quanpurchaseid,qc.startdate,qc.enddate,';
            $field.='b.customid as customid1,b.namechinese,b.linktel,d.quanname,d.amount quanidprice,c.namechinese as pantername';
            $count=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')
                ->join('left join customs_c m on a.customid=m.cid')
                ->join('left join customs b on m.customid=b.customid')
                ->join('left join panters c on a.panterid=c.panterid')
                ->join('left join quankind d on a.quanid=d.quanid')
                ->join('quan_account qc on qc.purchaseid=a.quanpurchaseid')
                ->where($where)->field($field)->count();
            $p=new \Think\Page($count, 12);
            $quancz_list=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')
                ->join('left join customs_c m on a.customid=m.cid')
                ->join('left join customs b on m.customid=b.customid')
                ->join('left join panters c on a.panterid=c.panterid')
                ->join('left join quankind d on a.quanid=d.quanid')
                ->join('quan_account qc on qc.purchaseid=a.quanpurchaseid')
                ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
                ->order('a.placeddate desc,a.placedtime desc')->select();
            //echo $quancz->getLastSql();
            foreach($quancz_list as $k=>$v){
                $v['amount']=floatval($v['amount']);
                $v['quanidprice']=floatval($v['quanidprice']);
                $v['totalamount']=$v['quanidprice']*$v['amount'];
                $result[]=$v;
            }
            session('quxcel',$where);
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $page = $p->show();
            $this->assign('list',$result);
            $this->assign('page',$page);
            $this->display();
        }
    }
    function getquank(){
        $customs = D('customs');
        $cardno = I('post.cardno','');
        if($cardno!=''){
            $where['d.cardno']=$cardno;
        }
        $account_list=$customs->alias('b')->join('left join customs_c m on b.customid=m.customid')
            ->join('left join cards d on m.cid=d.customid')
            ->where($where)->field('d.cardno,d.customid,b.namechinese,b.personidtype,b.personid,b.linktel,d.status')
            ->order('d.cardno desc')->find();
        if($account_list){
            $res['data']=$account_list;
            $res['success']=1;
        }else{
            $res['msg']='信息不能为空';
        }
        echo json_encode($res);
    }

    //检测手机号是否注册
    public function jlhCheckCustom(){
        if (!empty( $_FILES['file_stu']['name'])) {
            $m=$n=0;
            set_time_limit(0);
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['file_stu'] ['name']);
            $file_type = $file_types [count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xls" && strtolower($file_type) != "xlsx") {
                $this->error('不是Excel文件，重新上传');
            }
            /*设置上传路径*/
            $savePath = './Public/upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date('Ymdhis');
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file, $savePath . $file_name)) {
                $this->error('上传失败');
            }
            $exceldate = $this->import_excel($savePath . $file_name, 1);
            $arr=$array=array();
            foreach($exceldate as $k=>$v){
                $arr['cardno']=$v[1];
                $arr['namechinese']=$v[2];
                $arr['sex']=$v[3];
                $arr['personid']=$v[5];
                $arr['linktel']=$v[4];
                $arr['unitname']=$v[6];
                $array[]=$arr;
            }

            foreach($array as $key=>$val){
                $linktel=trim($val['linktel']);
                $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
                $custom=M('customs')->where($map)->find();
                if($custom==false){
                    $jlh1[]=$val;//未注册过手机号
                }else{
                    $jlh[]=$val;//注册过手机号
                }
            }
            if(count($jlh1)>0){
                $m=$this->jlh($jlh1,1);
            }
            if(count($jlh)>0){
                $n=$this->jlh($jlh,2);
            }
            $counts=$m+$n;
            $this->success('会员批量开卡成功'.$counts.'条',U('App/jlhCheckCustom'));
        }
        $this->display();
    }

    //君邻会新会员导入（注册过手机号）
    public function jlh($array,$type){
        $sql='';
        $m=0;
        foreach($array as $key=>$val){
            $linktel=trim($val['linktel']);
            $namechinese=$val['namechinese'];
            $sex=$val['sex'];
            $personid=$val['personid'];
            $unitname=$val['unitname'];
            M()->startTrans();
            if($type==1){
                $customid=$this->getFieldNextNumber('customid');
                $currentDate=date('Ymd');
//                if($key==0){
//                    $sql.="INSERT ALL INTO CUSTOMS (CUSTOMID,LINKTEL,NAMECHINESE,SEX,PERSONID,UNITNAME,CUSTOMLEVEL,PLACEDDATE,COUNTRYCODE) VALUES";
//                    $sql.=" ('{$customid}','{$linktel}','{$namechinese}','{$sex}','{$personid}','{$unitname}','建业线上会员','{$currentDate}','君邻会会员')";
//                }else{
//                    $sql.=" INTO CUSTOMS (CUSTOMID,LINKTEL,NAMECHINESE,SEX,PERSONID,UNITNAME,CUSTOMLEVEL,PLACEDDATE,COUNTRYCODE) VALUES";
//                    $sql.=" ('{$customid}','{$linktel}','{$namechinese}','{$sex}','{$personid}','{$unitname}','建业线上会员','{$currentDate}','君邻会会员')";
//                }
//                $cardArr[]=$val['cardno'];
                $sql="INSERT INTO CUSTOMS (CUSTOMID,LINKTEL,NAMECHINESE,SEX,PERSONID,UNITNAME,CUSTOMLEVEL,PLACEDDATE,COUNTRYCODE) VALUES";
                $sql.=" ('{$customid}','{$linktel}','{$namechinese}','{$sex}','{$personid}','{$unitname}','建业线上会员','{$currentDate}','君邻会会员')";
                $customIf=M()->execute($sql);
                if($customIf == true){
                    $cardArr=array($val['cardno']);
                    $bool=$this->openCard($cardArr,$customid);
                    if($bool==true){
                        ++$m;
                        M()->commit();
                        if($key==count($array)-1){
                            return  $m;
                        }
                    }else{
                        M()->rollback();
                    }
                }else{
                    M()->rollback();
                }
            }else{
                $map=array('linktel'=>$linktel,'customlevel'=>'建业线上会员');
                $customInfo=M('customs')->where($map)->find();
                $customid=$customInfo['customid'];
                $sql="UPDATE  CUSTOMS SET NAMECHINESE='{$namechinese}',SEX='{$sex}',PERSONID='{$personid}',UNITNAME='{$unitname}' where customid='{$customid}'";
                $customIf=M()->execute($sql);
                if($customIf==true){
                    $cardArr=array($val['cardno']);
                    $bool=$this->openCard($cardArr,$customid);
                    if($bool==true){
                        ++$m;
                        M()->commit();
                        if($key==count($array)-1){
                            return  $m;
                        }
                    }else{
                        M()->rollback();
                    }
                }else{
                    M()->rollback();
                }
            }
        }
//        if($type==1){
//            $sql.=" SELECT 1 FROM DUAL";
//            $customIf=M()->execute($sql);
//            if($customIf==true){
//                $bool=$this->openCard($cardArr,$customid);
//                if($bool==true){
//                    M()->commit();
//                    return  count($array);
//                }else{
//                    M()->rollback();
//                }
//            }else{
//                M()->rollback();
//            }
//        }
    }

    protected function openCard($cardArr,$customid,$amount=0){
        if(empty($cardArr)) return false;
        $userid=$this->userid;
        $rechargedAmount=0;
        foreach($cardArr as $key=>$val){
            $waitMoney=$amount-$rechargedAmount;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            $purchaseid=$this->getFieldNextNumber('purchaseid');
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');
            $where['cardno']=$cardno;
            $cardinfo=M('cards')->where($where)->field('panterid')->find();
            if($amount==0){
                $rechargeMoney=0;
            }else{
                if($waitMoney>=5000){
                    $rechargeMoney=5000;
                }else{
                    $rechargeMoney=$waitMoney;
                }
            }
            //写入购卡单并审核
            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','现金','";
            $customplSql.=$this->userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
            $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
            $customplIf=M()->execute($customplSql);
//            if($customplIf == true){
//                echo '1#';
//            }

            //写入审核单
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";
            $auditlogsIf=M()->execute($auditlogsSql);
//            if($auditlogsIf == true){
//                echo '2#';
//            }

            //写入购卡充值单
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
            $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
            $cardplIf=M()->execute($cardplSql);
//            if($auditlogsIf == true){
//                echo '3#';
//            }
            $where1['customid']=$customid;
            $card=M()->table('cards')->where($where1)->find();
            if($card==false){
            //    echo '4#';
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                $cardId=$customid;
            }else{
              //  echo '5#';
                //若存在，则需另外生成卡编号
                $cardId=$this->getFieldNextNumber('customid');
                $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
                $customIf=M()->execute($customSql);
            }

            //执行激活操作
            $cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
            $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
            $cardAlIf=M()->execute($cardAlSql);
//            if($cardAlIf == true){
//                echo '6#'.$cardId;
//            }
            //关联会员卡号
//            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
//            $customsIf=M()->execute($customcSql);
            $cut= M()->table('customs_c')->where(array('cid'=>$cardId))->find();
            if($cut == true){
                $customsIf =true;
            }else{
                //            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
                $customcSql="INSERT INTO customs_c(customid,cid) VALUES('{$customid}','{$cardId}')";
                $customsIf=M()->execute($customcSql);
//                if($customsIf == true){
//                    echo '7#';
//                }else{
//                    echo '4%5#'.$customid.'*'.$cardId;
//                }
            }
            //更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+3 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
            $cardIf=M()->execute($cardSql);
//            if($cardIf == true){
//                echo '8#';
//            }
            //给卡片添加账户并给账户充值
            $acid = $this->getFieldNextNumber('accountid');
            $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
            $balanceIf=M()->execute($balanceSql);
//            if($balanceIf == true){
////                echo '9#';
//            }

            $acid = $this->getFieldNextNumber('accountid');
            $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $pointSql.=$acid."','".$cardId."','0','01',NULL)";
            $pointIf=M()->execute($pointSql);
//            if($pointIf == true){
////                echo '10#';
//            }
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true){
                $rechargedAmount+=$rechargeMoney;
            }
        }
        if(!bccomp($rechargedAmount,$amount,2)){
//            echo '11#';
            return true;
        }else{
           // echo '12#';
            return false;
        }
    }

    //营销产品批量充值
    function batchproductpay(){
        $panterid=$this->panterid;
        $userid =  $this->userid;
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls"&&strtolower ( $file_type ) != "xlsx")
            {
                $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
            $savePath = './Public/upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name,1);
            $cards  = D('cards');
            $quankind = D('quankind');
            $panters  = D('panters');
            $account  = D('quan_account');
            $quancz   = D('quancz');
            $counts=0;
            set_time_limit(0);
            foreach ($exceldate as $key => $value) {
                $batchbuyBatchLog=array();
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                $batchbuyBatchLog[$key]['cardno']=$cardno=$value[0];
                $batchbuyBatchLog[$key]['tradeamount']=$tradeamount=$value[1];
                $batchbuyBatchLog[$key]['quanid']=$quanid=$value[2];
                $cards->startTrans();
                if($panterid!='FFFFFFFF'){
                    $where['a.panterid']=$panterid;
                    $wquank['a.panterid']=$panterid;
                    // $wquank['b.parent'] = $panterid;
                }
                if($cardno!=''){
                    $where['a.cardno']=$cardno;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                if($tradeamount=='' || floatval($tradeamount)<=0){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='充值数量错误!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $where['a.status']='Y';
                $card=$cards->alias('a')->join('left join customs_c c on a.customid=c.cid')
                    ->join('left join customs b on c.customid=b.customid')->where($where)->field('a.*,b.customid as customid1')->find();
                if($card==false){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'错误，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $customid = $card['customid'];
                $customid1= $card['customid1'];
                $nowdate=date('Ymd');
//				$wquank['a.startdate']=array('elt',$nowdate);
//				$wquank['a.enddate']=array('egt',$nowdate);
                $wquank['a.quanid'] =$quanid;
                $wquank['_string']=" (a.startdate <='{$nowdate}' and a.enddate>='{$nowdate}' and a.vtype=1) or a.vtype=2";
                $quankinds= $quankind->alias('a')->join('left join panters b on a.panterid=b.panterid')
                    ->where($wquank)->field('a.quanid,a.quanname,a.panterid,a.vtype,a.startdate,a.enddate')->find();
                //echo $quankind->getLastSql();exit;
                if($quankinds==false){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='营销券'.$quanid.'错误，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $quanname=$quankinds['quanname'];
                $waccount['customid']=$customid;
                $waccount['quanid']=$quanid;
                $waccount['startdate']=$nowdate;
                $accounts=$account->where($waccount)->field('accountid')->find();

                $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);

                if($quankinds['vtype']==1){
                    $startdate=$quankinds['startdate'];
                    $enddate=$quankinds['enddate'];
                }elseif($quankinds['vtype']==2){
                    $validaty=empty($quankinds['validaty'])?24:$quankinds['validaty'];
                    $startdate=date('Ymd');
                    $enddate=date('Ymd',strtotime('+ '.$validaty.' months'));
                }
                //echo $startdate.'--'.$enddate;exit;
                $condition=array('startdate'=>$startdate,'enddate'=>$enddate,'quanid'=>$quanid,'customid'=>$customid);
                $accountList=$account->where($condition)->find();
                if(empty($accountList)){
                    $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
                    $acountsif= $account->execute("INSERT INTO quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$quanid}','{$customid}','{$tradeamount}','{$startdate}','{$quanpurchaseid}','{$quanaccountid}','{$enddate}')");
                }else{
                    $sql="update quan_account set amount=amount+{$tradeamount} where customid='{$accountList['customid']}' and accountid='{$accountList['accountid']}' and quanid='{$accountList['quanid']}'";
                    $acountsif= $account->execute($sql);
                }

                $quanczs=$quancz->execute("insert into quancz values('".$quanid."','".$tradeamount."','".date('Ymd')."','".date('H:i:s')."','".$panterid."','".$userid."','".$customid."','{$quanpurchaseid}','0')");
                if($acountsif==false || $quanczs==false){
                    $cards->rollback();
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'营销产品批量充值有异常,请联系系统管理员!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }else{
                    $cards->commit();
                    $batchbuyBatchLog[$key]['status']=1;
                    $batchbuyBatchLog[$key]['msg']='营销产品批量充值成功!';
                }
                if(!empty($batchbuyBatchLog)){
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $counts++;
            }
            $this->success('营销产品批量充值成功'.$counts.'条',U('NewProduct/batchproductpay'));
        }
        $this->display();
    }
    //营销产品充值查询
    function productquery(){
        $cardnoc = trim(I('get.cardnoc',''));
        $customsid=trim(I('get.customsid',''));
        $cuname  = trim(I('get.cuname',''));
        $cquanid = trim(I('get.cquanid',''));
        $cquanname=trim(I('get.cquanname',''));
        $cardnoc = $cardnoc=="卡号"?"":$cardnoc;
        $customsid = $customsid=="会员编号"?"":$customsid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $cquanid = $cquanid=="营销产品编号"?"":$cquanid;
        $cquanname = $cquanname=="营销产品名称"?"":$cquanname;
        if($cardnoc!=''){
            $where['d.cardno']=$cardnoc;
            $this->assign('cardnoc',$cardnoc);
            $map['cardnoc']=$cardnoc;
        }
        if($customsid!=''){
            $where['b.customid']=$customsid;
            $this->assign('customsid',$customsid);
            $map['customsid']=$customsid;
        }
        if($cuname!=''){
            $where['b.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cquanid!=''){
            $where['a.quanid']=$cquanid;
            $this->assign('cquanid',$cquanid);
            $map['cquanid']=$cquanid;
        }
        if($cquanname!=''){
            $where['c.quanname']=array('like','%'.$cquanname.'%');
            $this->assign('cquanname',$cquanname);
            $map['cquanname']=$cquanname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $account=M('quan_account');
        $where['c.atype']='2';
        $field='d.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,a.enddate';
        $field.=',c.utype,c.vtype';
        $count=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('left join __PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)
            ->order('d.cardno desc')->count();
        $p=new \Think\Page($count, 15);
        $account_list=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join(' left join __PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('d.cardno desc')->select();
        //echo $account->getLastSql();
        $page = $p->show();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $quancz=M('quancz');
        $tradeWB=M('trade_wastebooks');
        foreach($account_list as $key=>$val){
            $map1=array('customid'=>$val['customid1'],'quanid'=>$val['quanid']);
            $account_list[$key]['czamount']=intval($quancz->where($map1)->sum('amount'));

            $map2=array('cardno'=>$val['cardno'],'tradetype'=>'02','quanid'=>$val['quanid']);
            $account_list[$key]['consumeamount']=intval($tradeWB->where($map2)->sum('tradeamount'));
        }
        $this->assign('page',$page);
        $this->assign('list',$account_list);
        $this->display();
    }

    //营销产品充值报表
    function payquery_excel(){
        $quancz=D('quancz');
        $smap=session('quxcel');
        foreach($smap as $key=>$val) {
            $where[$key]=$val;
        }
        $quancz_list=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')->join('left join customs_c m on a.customid=m.cid')
            ->join('left join customs b on m.customid=b.customid')->join('left join panters c on a.panterid=c.panterid')
            ->join('left join quankind d on a.quanid=d.quanid')->where($where)
            ->field('f.cardno,a.customid,a.placeddate,a.placedtime,a.quanid,a.userid,a.amount,b.customid as customid1,b.namechinese,b.linktel,d.quanname,d.startdate,d.enddate,d.amount quanidprice,c.namechinese as pantername')
            ->order('a.placeddate asc,a.placedtime asc')->select();
        foreach($quancz_list as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['quanidprice']=floatval($v['quanidprice']);
            $v['totalamount']=$v['quanidprice']*$v['amount'];
            $slist[]=$v;
        }
        $strlist="会员编号,会员名称,手机号,卡号,充值日期,充值时间,充值券编号,充值券名称,充值数量,劵单价,劵总价,券起始日期,券终止日期,充值机构";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        $sellCard_list=array();
        foreach ($slist as $key => $sell_info) {
            $sell_info['namechinese']=iconv('utf-8','gbk',$sell_info['namechinese']);
            $sell_info['quanname']=iconv('utf-8','gbk',$sell_info['quanname']);
            $sell_info['amount']=floatval($sell_info['amount']);
            $sell_info['quanidprice']=floatval($sell_info['quanidprice']);
            $sell_info['totalamount']=floatval($sell_info['totalamount']);
            $sell_info['pantername']=iconv('utf-8','gbk',$sell_info['pantername']);
            $sellCard_list[$key]['userid']=$sell_info['userid'];
            $strlist.=$sell_info['customid']."\t,".$sell_info['namechinese'].",";
            $strlist.=$sell_info['linktel'].",".$sell_info['cardno']."\t,".$sell_info['placeddate'].",";
            $strlist.=$sell_info['placedtime'].",".$sell_info['quanid']."\t,";
            $strlist.=$sell_info['quanname'].",".$sell_info['amount'].','.$sell_info['quanidprice'].',';
            $strlist.=$sell_info['totalamount'].','.$sell_info['startdate'].','.$sell_info['enddate'].',';
            $strlist.=$sell_info['pantername']."\n";
        }
        $filename='劵充值报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($slist);
        $this->load_csv($strlist,$filename);
    }
    //写入批量申请购卡日志
    function batchbuyLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='充值数量：'.$val['tradeamount']."  ";
            if($val['status']==0){
                $msgString.='状态：营销产品批量充值失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：营销产品批量充值成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('cardbatchbuy',$msgString);
    }

    function HbRules(){
        $rulename= trim(I('get.rulename',''));//结束日期
        $pname=trim(I('get.pname',''));
        $rulename = $rulename=="营销产品名称"?"":$rulename;
        $pname = $pname=="机构/商户名称"?"":$pname;
        if($rulename!=''){
            $where['h.rulename']= array('like','%'.$rulename.'%');
            $this->assign('h.rulename',$rulename);
            $map['rulename']=$rulename;
        }
        if($pname!=''){
            $where['p.namechinese']= array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        $count=M('hbrules')->alias('h')->join('panters p on p.panterid=h.panterid')
            ->where($where)->field('h.*,p.namechinese pname')->count();
        $p=new \Think\Page($count, 12);
        $list=M('hbrules')->alias('h')->join('panters p on p.panterid=h.panterid')
            ->where($where)->field('h.*,p.namechinese pname')->limit($p->firstRow.','.$p->listRows)->select();
        foreach($list as $key=>$val){
            if($val['enddate']<date('Ymd')){
                $list[$key]['overdue']=1;
            }else{
                $list[$key]['overdue']=0;
            }
        }
        $this->assign('list',$list);
        $this->display();
    }
    function addHbRules(){
        if(IS_POST){
            $m=M();
            $rulename=trim($_POST['rulename']);
            $startdate=$_POST['startdate'];
            $startdate=str_replace("-","",$startdate);
            $enddate=$_POST['enddate'];
            $enddate=str_replace("-","",$enddate);
            $panterid=trim($_POST['panterid']);
            $amount=trim($_POST['amount']);
            $memo=trim($_POST['memo']);
            $rate=trim($_POST['rate']);
            $ptype=trim(trim($_POST['ptype']));
            $gtype=trim(trim($_POST['gtype']));

            $maxmoney=trim(trim($_POST['maxmoney']));

            //print_r($_POST);exit;
            if(empty($rulename)){
                $this->error('规则名称不能为空',U('NewProduct/addHbRules'));
            }

            if(empty($ptype)){
                $this->error('送红包场景必选',U('NewProduct/addHbRules'));
            }
            if(empty($gtype)){
                $this->error('赠送方式必填',U('NewProduct/addHbRules'));
            }
            if(empty($startdate)||empty($enddate)){
                $this->error('活动有效期必填',U('NewProduct/addHbRules'));
            }
            if(empty($panterid)){
                $this->error('适用商户必填',U('NewProduct/addHbRules'));
            }

            if($maxmoney!=0&&$maxmoney!=''){
                $maxmoney=0;
            }
            /*if(!empty($amount)){
                $preg = "/(^0\.\d{1,2}$)|(^[1-9]\d*\.\d{1,2}$)|(^[1-9]\d*$)/";
                $boo =preg_match($preg, $amount);
                if(!$boo){
                    $this->error('金额最多精确到分');
                }
            }*/
            $preg = "/(^0\.\d{1,2}$)|(^[1-9]\d*\.\d{1,2}$)|(^[1-9]\d*$)/";
            $boo =preg_match($preg, $rate);
            if(!$boo){
                $this->error('赠送比例格式错误');
            }
            $ruleid=$this->getFieldNextNumber('ruleid');
            $currentDate=date('Ymd');
            $currentTime=date('H:i:s');
            $userid=$this->getUserid();
            if($ptype==1){
                $sql0="update hbrules set is_on=0 where ptype=1 and is_on=1 and ruleid <> '{$ruleid}' and panterid='{$panterid}'";
            }elseif($ptype==2){
                $sql0="update hbrules set is_on=0 where ptype=2 and is_on=1 and ruleid <> '{$ruleid}' and panterid='{$panterid}'";
            }
            $sql="insert into hbrules values('{$ruleid}','{$rulename}','{$startdate}','{$enddate}','{$panterid}','{$ptype}','{$gtype}','{$memo}','{$amount}','{$rate}','{$userid}','{$currentDate}','{$currentTime}',1,'{$maxmoney}') ";
            //echo $sql;exit;
            $r=$m->execute($sql);
            if($r){
                //echo $sql0;exit;
                $m->execute($sql0);
                $this->assign("jumpUrl",__APP__."/NewProduct/HbRules");
                $this->success("红包规则添加成功");
            }
            else{
                $this->error("操作失败");
            }
        }else{
            $this->display();
        }
    }
    //酒店券有效期列表
    public function quanenddate(){
        $customid = trim($_REQUEST['customid']);
        $cardno = trim($_REQUEST['cardno']);
        $quanid = trim($_REQUEST['quanid']);
        $qname = trim($_REQUEST['qname']);
        $customid = $customid == "会员编号" ? "" : $customid;
        $cardno = $cardno == "会员卡号" ? "" : $cardno;
        $quanid = $quanid == "营销产品编号" ? "" : $quanid;
        $qname = $qname == "营销产品名称" ? "" : $qname;
        $model = new model();
        if (!empty($customid)) {
            $where['q.customid'] = $customid;
            $map['customid']=$customid;
            $this->assign('customid',$customid);
        }
        if (!empty($cardno)) {
            $where['c.cardno'] = $cardno;
            $map['cardno']=$cardno;
            $this->assign('cardno',$cardno);
        }
        if (!empty($quanid)) {
            $where['q.quanid'] = $quanid;
            $map['quanid']=$quanid;
            $this->assign('quanid',$quanid);
        }
        if (!empty($qname)) {
            $where['qk.quanname'] = $qname;
            $map['quananme']=$qname;
            $this->assign('quananme',$qname);
        }
        $where['q.amount'] = array('neq','0');
        $where['c.cardkind']='6882';
        $where['c.status']='Y';
        $where['p.panterid'] = array('in','00000125,00000127,00000118,00000270,00000126,00003696');
        $field ='c.cardno cardno,q.customid customid,cu.namechinese cuname,cu.linktel linktel,q.quanid quanid,qk.quanname qname,q.amount amount,q.startdate qsdate,q.enddate qddate,q.accountid accountid,p.namechinese pname';
        $count = $model->table('quan_account')->alias('q')
            ->join('left join quankind qk on qk.quanid = q.quanid')
            ->join('left join panters p on p.panterid = qk.panterid')
            ->join('left join customs cu on cu.customid = q.customid')
            ->join('left join customs_c cc on cc.customid =cu.customid')
            ->join('left join cards c on c.customid=cc.cid')
            //  ->join('left join panters p on p.panterid = c.panterid')
            ->where($where)->field($field)->count();
        //  echo $count;
        //  dump($count);exit;
//        $quan_list = $model->table('quan_account')->alias('q')
//            ->join('left join quankind qk on qk.quanid = q.quanid')
//            ->join('left join panters p on p.panterid = qk.panterid')
//            ->join('left join customs cu on cu.customid = q.customid')
//            ->join('left join customs_c cc on cc.customid =cu.customid')
//            ->join('left join cards c on c.customid=cc.cid')
//            ->where($where)->field($field)->fetchSql('true')
//            ->order('q.accountid desc')->select();
//        echo $quan_list;exit;
//        dump($quan_list);exit;
        $p = new \Think\Page($count, 10);
        $quan_list = $model->table('quan_account')->alias('q')
            ->join('left join quankind qk on qk.quanid = q.quanid')
            ->join('left join panters p on p.panterid = qk.panterid')
            ->join('left join customs cu on cu.customid = q.customid')
            ->join('left join customs_c cc on cc.customid =cu.customid')
            ->join('left join cards c on c.customid=cc.cid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('q.accountid desc')->select();
//        dump($quan_list);exit;
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$quan_list);
        $this->assign('page',$page);
        $this->display();
    }
    //修改酒店券有效期
    public function edit_quandate(){
        if(IS_POST){
            $quan_account = M('quan_account');
//            dump($_POST);exit;
            $accountid = trim($_POST['accountid']);
            $data['enddate'] = trim($_POST['enddate']);
            $date = date('Ymd');
            if($data['enddate'] == ""){
                $this->error("券截止时间不能为空");
            }else{
                if($data['enddate'] <= $date){
                    $this->error("券截止时间不能小于等于今天");
                }
            }
            $where['accountid'] = $accountid;
            $account_quan = $quan_account->where($where)->save($data);
            if($account_quan){
                $this->success('修改券有效期成功', U('NewProduct/quanenddate'));
            }else{
                $this->error('修改券有效期失败');
            }

        }else{
            $accountid = trim($_REQUEST['accountid']);
            if (empty($accountid)) {
                $this->error('券账户ID缺失');
            }
            $where['accountid'] = $accountid;
            $quan_data = M('quan_account')->where($where)->find();
            $this->assign('info',$quan_data);
        }
        $this->display();

    }
    //君临会批量更改会员开卡绑定信息（临时）
    function customs_opencard(){
        set_time_limit(0);
//        $panterid=$this->panterid;
//        $userid =  $this->userid;
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
            $cards=M('cards');
            $custom =M('customs');
            $custom_p =M('custom_purchase_logs');
            $cu_a = M('card_active_logs');
            $cu_c =M('customs_c');
            $cu_account =M('account');
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
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value['A']);
                    $batchRechargeLog[$key]['linktel']=$linktel=trim($value['B']);
                }else{
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value[0]);
                    $batchRechargeLog[$key]['linktel']=$linktel=trim($value[1]);
                }
                $where=array();
                if($cardno!=''){
                    $where['cardno']=$cardno;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    continue;
                }
                if($linktel!=''){
                    $where1['linktel']=$linktel;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='手机号不能为空，请核实!';
                    continue;
                }
                $card=$cards->where($where)->find();
                $customs = $custom->where($where1)->find();
                //  dump($customs);exit;
                if($card==true && $customs ==true){
                    $old_cid =  $card['customid'];
                    $xin_customid = $customs['customid'];
                    //  echo $old_cid.'##';echo $xin_customid;exit;04561647##04561647
                    $purchaseid = M('card_purchase_logs')->where(array('cardno'=>$cardno))->find();
                    //     dump($purchaseid);exit;0625000001069878
                    if($purchaseid == true){
                        $xin_purchaseid = $purchaseid['purchaseid'];//0625000001069878
                        //  echo $xin_purchaseid;exit;0625000001069878
                        $cu_purchaseid = $custom_p->execute("update custom_purchase_logs set customid= '{$xin_customid}' where purchaseid=$xin_purchaseid");
                        $xin_cards = $cards->execute("update cards set customid='{$xin_customid}' where cardno=$cardno");
                        if($cu_purchaseid== true && $xin_cards == true){
//                            echo '123';exit;
                            $active = M('card_active_logs')->where(array('cardno'=>$cardno))->find();
//                            dump($active);exit;
                            $account = M('account')->where(array('customid'=>$old_cid))->find();
//                            dump($account);exit;
                            if($active == true && $account ==true){
                                $account_customid = $account['customid'];
                                $xin_active = $cu_a->execute("update card_active_logs set customid='".$xin_customid."' where cardno='".$cardno."'");
                                $cus_old = M('customs_c')->where(array('customid'=>$xin_customid))->find();
                                if($cus_old == true){
                                    $customs_into = true;
//                                    $batchbuyBatchLog[$key]['status']=0;
//                                    $batchbuyBatchLog[$key]['msg']='关联会员编号已存在，请核实!';
//                                    continue;
                                }else{
                                    $customs_into =$cu_c->execute("insert into customs_c(customid,cid)values('".$xin_customid."','".$xin_customid."')");
                                }
                                $xin_account = $cu_account->execute("update account set customid='".$xin_customid."'  where customid='".$account_customid."'");
                            }else{
                                $batchbuyBatchLog[$key]['status']=0;
                                $batchbuyBatchLog[$key]['msg']='卡号没有激活或老会员编号不存在，请核实!';
                                continue;
                            }
                        }else{
                            $batchbuyBatchLog[$key]['status']=0;
                            $batchbuyBatchLog[$key]['msg']='更新会员信息审核失败，请核实!';
                            continue;
                        }
                    }
//                    if($xin_active == true){
//                        echo 'q1#';
//                    }
//                    if($customs_into == true){
//                        echo 'q2*';
//                    }
//                    if($xin_account == true){
//                        echo 'q3!';
//                    }
                    if($xin_active == true && $customs_into==true && $xin_account ==true){
//                        echo '66';
                        $batchbuyBatchLog[$key]['status']=1;
                        $batchbuyBatchLog[$key]['cardno']=$cardno;
                        $batchbuyBatchLog[$key]['linktel']=$linktel;
                        $batchbuyBatchLog[$key]['msg']='更新开卡信息成功';
                    }else{
//                        echo '33123';exit;
                        $batchbuyBatchLog[$key]['status']=0;
                        $batchbuyBatchLog[$key]['linktel']=$linktel;
                        $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'手机号'.$linktel.'更新开卡信息失败,请联系系统管理员!';
                    }
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['cardno']=$cardno;
                    $batchbuyBatchLog[$key]['linktel']=$linktel;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'或手机号'.$linktel.'不存在,请联系系统管理员!sql:'
                        .$cards->getLastSql().'sql2'.$custom->getLastSql();
                }
                $batchbuyBatchLog[$key]['status']=1;
                $counts++;
            }
            if(!empty($batchbuyBatchLog)){
                $this->open_card_Logs($batchbuyBatchLog);
            }
            $nun = count($exceldate) - $counts;
            if ($err){
                $this->success('成功更新开卡信息'.$counts.'张,<br /><font color=red>其中<b>'.$nun.'</b>张异常</font>',U('NewProduct/customs_opencard'), 5);
            }else{
                $this->success('成功更新开卡信息'.$counts.'张',U('NewProduct/customs_opencard'),5);
            }
        }
        $this->display();
    }
    //写入批量更改开卡日志
    function open_card_Logs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            if($val['status']==0){
                $msgString.='状态：批量更新开卡信息'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量更新开卡信息成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('opencard',$msgString);
    }

}
