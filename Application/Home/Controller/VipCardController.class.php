<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class VipCardController extends CommonController {
    protected $keycode;
    protected $url;
    public function _initialize(){
        parent::_initialize();
        $this->keycode='JYO2O01';
        $this->url='http://192.168.10.22:8081/activate.php?app=amunbind';
    }
    //营销产品充值
    function cardPay(){
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
            $where['f.cardno']=$cardno;
            $this->assign('cardnoc',$cardno);
            $map['cardnoc']=$cardno;
        }
        if($linktel!=''){
            $where['b.linktel']=$linktel;
            $this->assign('linktel',$linktel);
            $map['linktel']=$linktel;
        }
        $where['d.atype']=2;
        $where['c.panterid']='00000126';
        //   $where['f.cardno']=array('like','%68823710888%');
        session('quxcel',$where);
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
        foreach($quancz_list as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['quanidprice']=floatval($v['quanidprice']);
            $v['totalamount']=$v['quanidprice']*$v['amount'];
            $result[]=$v;
        }
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
    //营销产品续期充值
    function cardPay1(){
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
            $where['f.cardno']=$cardno;
            $this->assign('cardnoc',$cardno);
            $map['cardnoc']=$cardno;
        }
        if($linktel!=''){
            $where['b.linktel']=$linktel;
            $this->assign('linktel',$linktel);
            $map['linktel']=$linktel;
        }
        $where['d.atype']=2;
        $where['c.panterid']='00000126';
        //    $where['f.cardno']=array('like','%68823710888%');
        $where['a.type']=1;
        session('quxcel',$where);
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
        foreach($quancz_list as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['quanidprice']=floatval($v['quanidprice']);
            $v['totalamount']=$v['quanidprice']*$v['amount'];
            $result[]=$v;
        }
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
    //贵宾卡充值列表导出
    function cardPay_excel(){
        $quancz=D('quancz');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $menmap = session('quxcel');
        foreach ($menmap as $key => $val) {
            $where[$key] = $val;
        }
        $field='f.cardno,a.customid,a.placeddate,a.placedtime,a.quanid,a.userid,a.amount,a.quanpurchaseid,qc.startdate,qc.enddate,';
        $field.='b.customid as customid1,b.namechinese,b.linktel,d.quanname,d.amount quanidprice,c.namechinese as pantername';
        $quancz_list=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')
            ->join('left join customs_c m on a.customid=m.cid')
            ->join('left join customs b on m.customid=b.customid')
            ->join('left join panters c on a.panterid=c.panterid')
            ->join('left join quankind d on a.quanid=d.quanid')
            ->join('quan_account qc on qc.purchaseid=a.quanpurchaseid')
            ->where($where)->field($field)
            ->order('a.placeddate desc,a.placedtime desc')->select();
        foreach($quancz_list as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['quanidprice']=floatval($v['quanidprice']);
            $v['totalamount']=$v['quanidprice']*$v['amount'];
            $v['placeddates']=date('Y-m-d',strtotime($v['placeddate']));
            $v['placedtimes']=date('H:i:s',strtotime($v['placedtime']));
            $v['startdates']=date('Y-m-d',strtotime($v['startdate']));
            $v['enddates']=date('Y-m-d',strtotime($v['enddate']));
            $rows[]=$v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:G3";
        $titleName = '贵宾卡充值报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'M4';
        $headerArray = array('会员编号', '会员名称', '手机号', '卡号', '充值日期', '充值时间', '充值券编号', '充值券名称', '充值数量', '券起始日期', '券终止日期', '充值机构', '操作员');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');
        $setWidth = array(15, 15, 15, 25, 15, 15, 15, 25, 15, 15, 15, 50, 25);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, "'" . $val['namechinese'])->setCellValue('C' . $j, $val['linktel'])->setCellValue('D' . $j, "'" . $val['cardno'])
                ->setCellValue('E' . $j, $val['placeddates'])->setCellValue('F' . $j,"'" . $val['placedtimes'])->setCellValue('G' . $j,"'" . $val['quanid'])->setCellValue('H' . $j, $val['quanname'])
                ->setCellValue('I' . $j, "'" . $val['amount'])->setCellValue('J' . $j, $val['startdates'])->setCellValue('K' . $j, $val['enddates'])->setCellValue('L' . $j, $val['pantername'])->setCellValue('M' . $j,"'" . $val['userid']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡充值报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //获得卡片信息
    function getcards(){
        $cards = D('cards');
        $sex    = trim(I('post.sex',''));
        $cardno  = trim(I('post.cardno',''));
        $cname  = trim(I('post.cname',''));
        $customsid = trim(I('post.customsid',''));
        $cardno = $cardno=="卡号"?"":$cardno;
        $cname = $cname=="会员名称"?"":$cname;
        $customsid = $customsid=="会员编号"?"":$customsid;
        if($sex!=''){
            $where['b.sex']=$sex;
        }
        if($cardno!=''){
            $where['a.cardno']=$cardno;
        }
        if($cname!=''){
            $where['b.namechinese']=array('like','%'.$cname.'%');
        }
        if($customsid!=''){
            $where['a.customid']=$customsid;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        if(strstr($cardno,"68823710888")){
            $result=$cards->alias('a')
                ->join('inner join quancz q on q.customid=a.customid')
                ->where(array('a.cardno'=>$cardno))
                ->order('a.cardno desc')->select();
            if($result){
                $res['msg']='此卡已充值过券';
            }else{
                $where['a.status']='Y';
                $field='a.cardno,a.status,a.customid as customid1,b.customid,';
                $field.='b.namechinese cuname,b.customlevel,b.linktel,a.exdate';
                $card_list=$cards->alias('a')
                    ->join('left join customs_c c on a.customid=c.cid')
                    ->join('left join customs b on c.customid=b.customid')
                    ->join('__PANTERS__ p on p.panterid=a.panterid')
                    ->where($where)->field($field)
                    ->order('a.cardno desc')->select();
                if($card_list){
                    $res['data']=$card_list;
                    $res['success']=1;
                }else{
                    $res['msg']='没有此记录';
                }
            }
        }else{
            $res['msg']='此卡号非艾美贵宾卡';
        }
        echo json_encode($res);
    }
    //获得卡片信息
    function getcards1(){
        $cards = D('cards');
        $sex    = trim(I('post.sex',''));
        $cardno  = trim(I('post.cardno',''));
        $cname  = trim(I('post.cname',''));
        $customsid = trim(I('post.customsid',''));
        $cardno = $cardno=="卡号"?"":$cardno;
        $cname = $cname=="会员名称"?"":$cname;
        $customsid = $customsid=="会员编号"?"":$customsid;
        if($sex!=''){
            $where['b.sex']=$sex;
        }
        if($cardno!=''){
            $where['a.cardno']=$cardno;
        }
        if($cname!=''){
            $where['b.namechinese']=array('like','%'.$cname.'%');
        }
        if($customsid!=''){
            $where['a.customid']=$customsid;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $operatedate=date('Ymd',time());
        $card=M('discount_renewal')->where(array('cardno'=>$cardno,'operatedate'=>$operatedate))->find();
        if(!$card){
            $res['msg']='请续期后再充值';
        }
        if(strstr($cardno,"68823710888")){
            $placeddate=date('Ymd',time());
            $result=$cards->alias('a')
                ->join('inner join quancz q on q.customid=a.customid')
                ->where(array('a.cardno'=>$cardno,'q.type'=>1,'q.placeddate'=>$placeddate))
                ->order('a.cardno desc')->select();
            if($result){
                $res['msg']='此卡已续期充值过券了';
            }else{
                $where['a.status']='Y';
                $field='a.cardno,a.status,a.customid as customid1,b.customid,';
                $field.='b.namechinese cuname,b.customlevel,b.linktel,a.exdate';
                $card_list=$cards->alias('a')
                    ->join('left join customs_c c on a.customid=c.cid')
                    ->join('left join customs b on c.customid=b.customid')
                    ->join('__PANTERS__ p on p.panterid=a.panterid')
                    ->where($where)->field($field)
                    ->order('a.cardno desc')->select();
                if($card_list){
                    $res['data']=$card_list;
                    $res['success']=1;
                }else{
                    $res['msg']='没有此记录';
                }
            }
        }else{
            $res['msg']='此卡号非艾美贵宾卡';
        }
        echo json_encode($res);
    }
    //根据卡号显示选中信息
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
    //给卡号充值31张券
    public function  cardRecharge(){
        $cards = D('cards');
        $quancz = D('quancz');
        $account  = D('quan_account');
        $cardno = I('post.cardno','');
        $userid=$_SESSION['userid'];
        $cardsInfo=$cards->where(array('cardno'=>$cardno))->field('customid,panterid')->find();
        $cardsif= $cards->execute("update cards set vipdate='".date('Ymd',strtotime("+1 year"))."' where cardno='{$cardno}'");
        $quan=C('VipQuan');
        $counts=0;
        foreach($quan as $k=>$v){
            $cards->startTrans();
            $startdate=date('Ymd',time());
            if($v['month']==6){
                $enddate=date("Ymd", strtotime("+6 months", time()));
            }else{
                $enddate=date("Ymd", strtotime("+12 months", time()));
            }
            $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);
            $condition=array('startdate'=>$startdate,'enddate'=>$enddate,'quanid'=>$v['quanid'],'customid'=>$cardsInfo['customid']);
            $accounts=$account->where($condition)->find();
            if($accounts==false){
                $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
                $acountsif= $account->execute("INSERT INTO quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$v['quanid']}','{$cardsInfo['customid']}','{$v['amount']}','{$startdate}','{$quanpurchaseid}','{$quanaccountid}','{$enddate}')");
            }else{
                $acountsif= $account->execute("update quan_account set amount=amount+{$v['amount']} where customid='{$cardsInfo['customid']}' and accountid='{$accounts['accountid']}' and quanid='{$v['quanid']}'");
            }
            $quanczs=$quancz->execute("insert into quancz values('".$v['quanid']."','".$v['amount']."','".date('Ymd')."','".date('H:i:s')."','".$cardsInfo['panterid']."','".$userid."','".$cardsInfo['customid']."','".$quanpurchaseid."',0)");
            $msgString='卡号：'.$cardno."  ";
            $msgString.='充值数量：'.$v['amount']."  ";
            $msgString.='券名称：'.$v['quanname']."  ";
            if($cardsif==false||$acountsif==false || $quanczs==false){
                $cards->rollback();
                continue;
                $msgString.='状态：营销产品批量充值失败'."  ";
                $msgString.='原因：卡号'.$cardno.'营销产品充值有异常,请联系系统管理员!'."  ";

            }else{
                $cards->commit();
                $msgString.='状态：营销产品批量充值成功'."  ";
            }
            $msgString.='时间：'.date('Y-m-d H:i:s',time())."  ";
            $msgString.="\r\n\r\n";
            $this->writeLogs('vipCard',$msgString);
            $counts++;
        }
        $this->success('营销产品充值成功'.$counts.'条',U('VipCard/cardPay'));
    }
    //给卡号充值32张券
    public function  cardRecharge1(){
        $cards = D('cards');
        $quancz = D('quancz');
        $account  = D('quan_account');
        $cardno = I('post.cardno','');
        $userid=$_SESSION['userid'];
        $cardsInfo=$cards->where(array('cardno'=>$cardno))->field('customid,panterid')->find();
//        $cardsif= $cards->execute("update cards set vipdate='".date('Ymd',strtotime("+1 year"))."' where cardno='{$cardno}'");
        $quan=C('VipQuan1');
        $counts=0;
        foreach($quan as $k=>$v){
            $cards->startTrans();
            $startdate=date('Ymd',time());
            if($v['month']==6){
                $enddate=date("Ymd", strtotime("+6 months", time()));
            }else{
                $enddate=date("Ymd", strtotime("+12 months", time()));
            }
            $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);
            $condition=array('startdate'=>$startdate,'enddate'=>$enddate,'quanid'=>$v['quanid'],'customid'=>$cardsInfo['customid']);
            //     $condition=array('quanid'=>$v['quanid'],'customid'=>$cardsInfo['customid']);
            $accounts=$account->where($condition)->find();
            if($accounts==false){
                $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
                $acountsif= $account->execute("INSERT INTO quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$v['quanid']}','{$cardsInfo['customid']}','{$v['amount']}','{$startdate}','{$quanpurchaseid}','{$quanaccountid}','{$enddate}')");
            }else{
                $acountsif= $account->execute("update quan_account set amount=amount+{$v['amount']},startdate={$startdate},enddate={$enddate} where customid='{$cardsInfo['customid']}' and accountid='{$accounts['accountid']}' and quanid='{$v['quanid']}'");
            }
            $quanczs=$quancz->execute("insert into quancz values('".$v['quanid']."','".$v['amount']."','".date('Ymd')."','".date('H:i:s')."','".$cardsInfo['panterid']."','".$userid."','".$cardsInfo['customid']."','".$quanpurchaseid."',1)");
            $msgString='卡号：'.$cardno."  ";
            $msgString.='充值数量：'.$v['amount']."  ";
            $msgString.='券名称：'.$v['quanname']."  ";
            if($acountsif==false || $quanczs==false){
                $cards->rollback();
                continue;
                $msgString.='状态：营销产品批量充值失败'."  ";
                $msgString.='原因：卡号'.$cardno.'营销产品充值有异常,请联系系统管理员!'."  ";

            }else{
                $cards->commit();
                $msgString.='状态：营销产品批量充值成功'."  ";
            }
            $msgString.='时间：'.date('Y-m-d H:i:s',time())."  ";
            $msgString.="\r\n\r\n";
            $this->writeLogs('vipCard',$msgString);
            $counts++;
        }
        $this->success('营销产品充值成功'.$counts.'条',U('VipCard/cardPay1'));
    }
    //贵宾卡折扣续期列表
    public function discountRenewal(){
        $paytype=array(1=>'支付宝',2=>'微信',3=>'银行卡',4=>'现金');
        if($_POST){
            $where['cardno']=trim($_POST['cardnos']);
            $where['startdate']=$_POST['startdate'];
            $where['enddate']=$_POST['enddate'];
            $result=M('discount_renewal')->where($where)->order('renewaldate desc')->select();
            if(empty($result)){
                $data=array();
            }
            $this->assign('cardnos',$_POST['cardnos']);
            $this->assign('startdate',$_POST['startdate']);
            $this->assign('enddate',$_POST['enddate']);
        }else{
            $result=M('discount_renewal')->order('renewaldate desc')->select();
        }
        if(!empty($result)){
            foreach($result as $k=>$v){
                $v['originaldate']=date('Y-m-d',strtotime($v['originaldate']));
                $v['renewaldate']=date('Y-m-d',strtotime($v['renewaldate']));
                $v['operatedate']=date('Y-m-d',strtotime($v['operatedate']));
                $v['paytype']=$paytype[$v['paytype']];
                $data[]=$v;
            }
        }
        $this->assign('paytype',$paytype);
        $this->assign('data',$data);
        $this->display();
    }
//    //贵宾卡折扣续期操作
//    public function renewal(){
//        $cardno=trim($_POST['cardno']);
//        $originaldate=date('Ymd',strtotime(trim($_POST['originaldate'])));
//        $renewaldate=date('Ymd',strtotime(trim($_POST['renewaldate'])));
//        $paytype=trim($_POST['paytype']);
//        $payamount=trim($_POST['payamount']);
//        $operatedate=date('Ymd',time());
//        $operatetime=date('H:i:s',time());
//        $operater=session('username');
//        $discount  = M('discount_renewal');
//        $cards = M('cards');
//        if(strtotime($renewaldate)<strtotime($originaldate)){
//            $this->error('新过期日期必须大于原过期日期');
//        }
//        $result=$discount->execute("INSERT INTO discount_renewal VALUES ('{$cardno}','{$payamount}','{$paytype}','{$originaldate}','{$operatedate}','{$renewaldate}','{$operater}','{$operatetime}')");
//        if($result==true){
//            $cardsif= $cards->execute("update cards set vipdate='{$renewaldate}' where cardno='{$cardno}'");
//            $quan_recharge =$this->cardRecharge1();
////            dump($quan_recharge) ;exit;
//            if($cardsif==true && $quan_recharge==true ){
////                $quan_recharge =$this->cardRecharge1();
//                $this->success('贵宾卡折扣续期，营销产品批量充值成功',U('VipCard/discountRenewal'));
//            }else{
//                $this->error('贵宾卡折扣续期，营销产品批量充值失败',U('VipCard/discountRenewal'));
//            }
//
//        }
//    }
    //贵宾卡折扣续期操作
    public function renewal(){
        $cardno=trim($_POST['cardno']);
        $originaldate=date('Ymd',strtotime(trim($_POST['originaldate'])));
        $renewaldate=date('Ymd',strtotime(trim($_POST['renewaldate'])));
        $paytype=trim($_POST['paytype']);
        $payamount=trim($_POST['payamount']);
        $operatedate=date('Ymd',time());
        $operatetime=date('H:i:s',time());
        $operater=session('username');
        $discount  = M('discount_renewal');
        $cards = M('cards');
        if(strtotime($renewaldate)<strtotime($originaldate)){
            $this->error('新过期日期必须大于原过期日期');
        }
        $result=$discount->execute("INSERT INTO discount_renewal VALUES ('{$cardno}','{$payamount}','{$paytype}','{$originaldate}','{$operatedate}','{$renewaldate}','{$operater}','{$operatetime}')");
        if($result==true){
            $cardsif= $cards->execute("update cards set vipdate='{$renewaldate}' where cardno='{$cardno}'");
            if($cardsif==true){
                $this->success('贵宾卡折扣续期成功',U('VipCard/discountRenewal'));
            }
        }
    }
    //根据卡号同步获取卡折扣原过期时间
    public function getOriginalDate(){
        $cardno=trim($_POST['cardno']);
        $map=array('cardno'=>$cardno);
        $card=M('cards')->alias('c')->join('custom_purchase_logs cu on c.customid=cu.customid')->where($map)->field('c.status,cu.placeddate,c.vipdate')->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
//        $originaldate=date('Y-m-d',strtotime($card['placeddate'])+365*24*60*60);
        $originaldate=date('Y-m-d',strtotime($card['vipdate']));
        returnMsg(array('status'=>'1','originaldate'=>$originaldate));
    }
    //贵宾卡解绑列表
    public function unbundingList(){
        $status=array(1=>'已绑定',2=>'已解绑');
        if($_GET){
            $cardno=$_GET['cardno'];
            $url=$this->url.'&act=checkCard';
            $data=array('cardno'=>$cardno,'key'=>md5($this->keycode.$cardno));
            $datamidata['datami']=json_encode($data);
            $result=$this->getQuery($datamidata,$url);
            if($result['status']==1){
                $result['data']['status']=$status[ $result['data']['statue']];
                $rows[]=$result['data'];
            }else{
                $this->error($result['codemsg']);
            }
            $this->assign('cardno',$cardno);
            $this->assign('data',$rows);
        }
        $this->display();
    }
    //贵宾卡解绑
    public function unbunding(){
        $cardno  = trim($_GET['cardno']);
        $url=$this->url.'&act=cardUnbind';
        $data=array('cardno'=>$cardno,'key'=>md5($this->keycode.$cardno));
        $datamidata['datami']=json_encode($data);
        $result=$this->getQuery($datamidata,$url);
        if($result['status']==1){
            $this->success($result['codemsg'],U('VipCard/unbundingList'));
        }else{
            $this->error($result['codemsg'],U('VipCard/unbundingList'));
        }
    }
}
