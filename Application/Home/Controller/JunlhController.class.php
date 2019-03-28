<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class JunlhController extends CommonController {

    //酒店营销劵交易统计页面
    function ticketConsume(){
        $this->display();
    }
    //酒店营销劵交易统计数据
    function getTicketConsumeList(){
        $model=new model();
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $pname = trim(I('get.pname',''));
        $quanid = trim(I('get.quanid',''));
        $quanname = trim(I('get.quanname',''));
        $cardno =trim(I('get.cardno',''));
        $unitname =trim(I('get.unitname',''));
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
        }
        if($quanid!=''){
            $where['tw.quanid']=$quanid;
        }
        if($quanname!=''){
            $where['q.quanname']=array('like','%'.$quanname.'%');
        }
        if($unitname!=''){
            $where['custom.unitname']=array('like','%'.$unitname.'%');
        }
        $where['card.panterid']='00000447';
        if($cardno!='')$where['tw.cardno']=$cardno;
        session('ticketConsume_excel',$where);

        // $where['tw.tradetype']='02';
        // $where['p.hysx']='酒店';
        $field='tw.panterid,tw.cardno,p.namechinese pname,p.hysx,tw.placeddate,tw.placedtime,tw.flag,tw.quanid,tw.termposno,tw.tradepoint,tw.addpoint,';
        $field.='tw.tradetype,tw.tradeid,q.quanname,custom.namechinese cuname,custom.customid,p1.namechinese pname1,';
        $field.='count(tw.tradeid) count,sum(tw.tradeamount) as tradeamount,q.amount,custom.unitname';
        $group=' tw.panterid,tw.cardno,p.namechinese,p.hysx,tw.placeddate,tw.placedtime,tw.flag,tw.quanid,tw.termposno,tw.tradepoint,tw.addpoint,tw.tradetype,tw.tradeid,q.quanname,';
        $group.='custom.namechinese,custom.customid,p1.namechinese,tw.tradeamount,q.amount,custom.unitname';
        $subQuery = "(select sum(tradeamount) as tradeamount,cardno,panterid,quanid,placeddate,customid ";
        $subQuery.="from trade_wastebooks where tradetype='02' group by cardno,panterid,placeddate,quanid,customid)";

        $where1=$where;
        $total=$model->table($subQuery)->alias('tw')
            ->join('left join __PANTERS__ p on tw.panterid=p.panterid')
            ->join('left join __QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where1)->count();
        //正常交易表--营销劵表--商户表
        $where['tw.tradetype']='02';
        $results=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)->page($page,$pageSize)
            ->group($group)->order('tw.placeddate desc')->select();

        foreach($results as $k=>$v){
            switch($v['flag']){
                case 0:
                    $v['flag']='正常';
                    break;
                case 1:
                    $v['flag']='冲正';
                    break;
                case 2:
                    $v['flag']='退货';
                    break;
                case 3:
                    $v['flag']='撤销';
                    break;
            }
            switch ($v['tradetype']) {
                case '00':
                    $v['tradetype']='至尊卡消费';
                    break;
                case '02':
                    $v['tradetype']='劵消费';
                    break;
                case '04':
                    $v['tradetype']='消费撤销';
                    break;
                case '08':
                    $v['tradetype']='券消费冲正';
                    break;
                case '13':
                    $v['tradetype']='现金消费';
                    break;
                case '14':
                    $v['tradetype']='积分充值';
                    break;
                case '17':
                    $v['tradetype']='预授权';
                    break;
                case '18':
                    $v['tradetype']='预授权冲正';
                    break;
                case '19':
                    $v['tradetype']='预授权撤销';
                    break;
                case '21':
                    $v['tradetype']='预授权完成';
                    break;
                case '22':
                    $v['tradetype']='预授权完成冲正';
                    break;
                case '23':
                    $v['tradetype']='券消费撤销';
                    break;
                case '24':
                    $v['tradetype']='预授权完成撤销';
                    break;
            }
            $v['amount']=floatval($v['amount']);
            $v['totalamount']=$v['amount']*$v['tradeamount'];
            $rows[]=$v;
        }
        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($rows)?array_values($rows):'';
        $this->ajaxReturn($result);
    }
    /**
     * 当日营销劵交易统计导出EXCEL
     */
    function ticketConsume_excel(){
        //header('content-type:text/html;charset=gbk');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model=new Model();
        $ticketConsumeMap=session('ticketConsume_excel');
        foreach ($ticketConsumeMap as $key => $value) {
            $where[$key]=$value;
        }
        $field='tw.panterid,tw.cardno,p.namechinese pname,p.hysx,tw.placeddate,tw.placedtime,tw.flag,tw.quanid,tw.termposno,tw.tradepoint,tw.addpoint,';
        $field.='tw.tradetype,tw.tradeid,q.quanname,custom.namechinese cuname,custom.customid,p1.namechinese pname1,';
        $field.='count(tw.tradeid) count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group=' tw.panterid,tw.cardno,p.namechinese,p.hysx,tw.placeddate,tw.placedtime,tw.flag,tw.quanid,tw.termposno,tw.tradepoint,tw.addpoint,tw.tradetype,tw.tradeid,q.quanname,';
        $group.='custom.namechinese,custom.customid,p1.namechinese,tw.tradeamount,q.amount';
        $results=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)
            ->group($group)->order('tw.placeddate desc')->select();
        foreach($results as $k=>$v){
            switch($v['flag']){
                case 0:
                    $v['flag']='正常';
                    break;
                case 1:
                    $v['flag']='冲正';
                    break;
                case 2:
                    $v['flag']='退货';
                    break;
                case 3:
                    $v['flag']='撤销';
                    break;
            }
            switch ($v['tradetype']) {
                case '00':
                    $v['tradetype']='至尊卡消费';
                    break;
                case '02':
                    $v['tradetype']='劵消费';
                    break;
                case '04':
                    $v['tradetype']='消费撤销';
                    break;
                case '08':
                    $v['tradetype']='券消费冲正';
                    break;
                case 13:
                    $v['tradetype']='现金消费';
                    break;
                case '14':
                    $v['tradetype']='积分充值';
                    break;
                case '17':
                    $v['tradetype']='预授权';
                    break;
                case '18':
                    $v['tradetype']='预授权冲正';
                    break;
                case '19':
                    $v['tradetype']='预授权撤销';
                    break;
                case '21':
                    $v['tradetype']='预授权完成';
                    break;
                case '22':
                    $v['tradetype']='预授权完成冲正';
                    break;
                case '23':
                    $v['tradetype']='券消费撤销';
                    break;
                case '24':
                    $v['tradetype']='预授权完成撤销';
                    break;
            }
            $v['amount']=floatval($v['amount']);
            $v['totalamount']=$v['amount']*$v['tradeamount'];
            $rows[]=$v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge="A1:S3";
        $titleName='酒店营销劵交易统计报表';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='S4';
        $headerArray = array('交易卡号','会员编号','会员名称',
                           '会员所属机构','交易状态','商户编号',
                           '商户名称','交易日期','交易时间',
                           '终端号','交易数量','交易积分',
                           '交易类型','交易流水号号','交易产生积分',
                           '券编号','券名称','券单价','券总价' 
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','D','G','H','N','Q','O');
        $setWidth = array(21,40,40,10,25,30,15);
        $this->setWidth($setCells, $setWidth);

        $j=5;
      foreach ($rows as $key => $val){
            $objSheet->setCellValueExplicit("G".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("J".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A'.$j,"'".$val['cardno'])->setCellValue('B'.$j,"'".$val['customid'])->setCellValue('C'.$j,$val['cuname'])
                   ->setCellValue('D'.$j,$val['pname1'])->setCellValue('E'.$j,$val['flag'])->setCellValue('F'.$j,"'".$val['panterid'])
                   ->setCellValue('G'.$j,$val['pname'])->setCellValue('H'.$j,$val['placeddate'])->setCellValue('I'.$j,$val['placedtime'])
                   ->setCellValue('J'.$j,"'".$val['termposno'])->setCellValue('K'.$j,$val['tradeamount'])->setCellValue('L'.$j,$val['tradepoint'])
                   ->setCellValue('M'.$j,"'".$val['tradetype'])->setCellValue('N'.$j,"'".$val['tradeid'])->setCellValue('O'.$j,$val['addpoint'])
                   ->setCellValue('P'.$j,"'".$val['quanid'])->setCellValue('Q'.$j,$val['quanname'])->setCellValue('R'.$j,$val['amount'])
                   ->setCellValue('S'.$j,$val['totalamount']);
            $j++;
        }

        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','酒店营销劵交易统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    function index(){
        $customid=trim($_REQUEST['customid']);
        $cusname=trim($_REQUEST['cusname']);
        $linktel=trim($_REQUEST['linktel']);
        $personid=trim($_REQUEST['personid']);
        $unitname=trim($_REQUEST['unitname']);
        $cardno=trim($_REQUEST['cardno']);
        $customid = $customid=="会员编号"?"":$customid;
        $cusname = $cusname=="会员名称"?"":$cusname;
        $linktel = $linktel=="手机号"?"":$linktel;
        $personid = $personid=="身份证号"?"":$personid;
        $unitname=$unitname=='区域'?'':$unitname;
        $cardno=$cardno=='卡号'?'':$cardno;
        if(!empty($customid)){
            $where['cu.customid']=$customid;
            $map['customid']=$customid;
            $this->assign('customid',$customid);
        }

        if(!empty($cusname)){
            $where['cu.namechinese']=array('like','%'.$cusname.'%');
            $map['cusname']=$cusname;
            $this->assign('cusname',$cusname);
        }
        if(!empty($linktel)){
            $where['cu.linktel']=array('like','%'.$linktel.'%');
            $map['linktel']=$linktel;
            $this->assign('linktel',$linktel);
        }
        if(!empty($personid)){
            $where['cu.personid']=array('like','%'.$personid.'%');
            $map['personid']=$personid;
            $this->assign('personid',$personid);
        }
        if(!empty($unitname)){
            $where['cu.unitname']=array('like','%'.$unitname.'%');
            $map['unitname']=$unitname;
            $this->assign('unitname',$unitname);
        }
        if(!empty($cardno)){
            $where['c.cardno']=$cardno;
            $map['cardno']=$cardno;
            $this->assign('cardno',$cardno);
        }
        $where['c.panterid']='00000447';
        $where['c.status']='Y';

        $field="cu.customid,cu.linktel,cu.namechinese cuname,cu.unitname,cu.personid,cu.sex,c.cardno";
        $count=M('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 10);
        $custom_list=M('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)
            ->limit($p->firstRow.','.$p->listRows)->select();

        session('customs_con',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$custom_list);
        $this->assign('page',$page);
        $this->display();
    }

    function customs_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model=new Model();
        $where = session('customs_con');


        $title='会员编号,会员名称,性别,归属,手机号,证件类型,身份证号,卡号';
        $field='cu.customid,cu.linktel,cu.namechinese cuname,cu.unitname,cu.personid,cu.sex,c.cardno';
        $custom_list=$model->table('customs')->alias('cu')
            ->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')
            ->where($where)->field($field)->select();
        $strlist=$title;
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach($custom_list as $key=>$val){
            $cuname=iconv('utf-8','gbk',$val['cuname']);
            $sex=iconv('utf-8','gbk',$val['sex']);
            $personidtype=iconv('utf-8','gbk','身份证');
            $unitname=iconv('utf-8','gbk',$val['unitname']);
            $personid=iconv('utf-8','gbk',$val['personid']);
            $strlist.=$val['customid']."\t,".$cuname.",".$sex.",".$unitname;
            $strlist.=",".$val['linktel']."\t,".$personidtype.",".$personid."\t,";
            $strlist.=$val['cardno']."\t\n";
            $strlist.="";
        }
        $filename='会员报表'.date("YmdHis");
        $filename=iconv("utf-8","gbk",$filename);
        unset($custom_list);
        $this->load_csv($strlist,$filename);
    }

    function editCustom(){
        if(IS_POST){
            $customid=$_POST['customid'];
            $name=$_POST['name'];
            $sex=$_POST['sex'];
            $unitname=$_POST['unitname'];
            $linktel=$_POST['linktel'];
            $personid=$_POST['personid'];
            $sql="UPDATE CUSTOMS SET NAMECHINESE='{$name}',sex='{$sex}',unitname='{$unitname}',linktel='{$linktel}',personid='{$personid}' WHERE CUSTOMID='{$customid}'";
            if(M('customs')->execute($sql)){
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $customid=$_REQUEST['customid'];
            $map=array('customid'=>$customid);
            $list=M('customs')->where($map)->field('namechinese,linktel,personid,unitname,sex,customid')->find();
            $this->assign('list',$list);
            $this->display();
        }
    }

    function ticketDecute(){
        $cname= trim(I('post.cname',''));
        $linktel= trim(I('post.linktel',''));
        $cardno= trim(I('post.cardno',''));
        if(!empty($cname)){
            $where['cu.namechinese']=array('like','%'.$cname.'%');
            $this->assign('cname',$cname);
        }
        if(!empty($linktel)){
            $where['cu.linktel']=$linktel;
            $this->assign('linktel',$linktel);
        }
        if(!empty($cardno)){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
        }
        if(IS_POST){
            $field="cu.linktel,cu.namechinese cname,c.cardno,a.amount,a.quanid,q.quanname";
            $where['a.type']='02';
            $where['a.amount']=array('gt',0);
            $where['cu.customlevel']='建业线上会员';
            $where['c.cardkind']='6888';
            $customs=M('customs');
            $list=$customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')->join('account a on a.customid=c.customid')
                ->join('quankind q on q.quanid=a.quanid')->where($where)->field($field)->select();
            $this->assign('list',$list);
            $panterArr=array('00000125','00000126','00000127','00000118','00000270','00000678');
            $panters=M('panters')->where(array('panterid'=>array('in',$panterArr)))
                ->field('panterid,nameenglish pname')->select();
            $this->assign('panters',$panters);
            //print_r($list);
        }
        $this->display();
    }
    function ticketDecuteDo(){
        //echo json_encode(array("status"=>'1',"codemsg"=>'123'));exit;
        $cardno=trim($_REQUEST['cardno']);
        $quanid=trim($_REQUEST['quanid']);
        $decuteNum=trim($_REQUEST['decutenum']);
        $panterid=trim($_REQUEST['panterid']);
        $termposno = '00000000';
        //echo $cardno.'--'.$quanid.'--'.$decuteNum.'--'.$panterid;exit;
        if(empty($cardno)){
            returnMsg(array('status'=>'01','codemsg'=>'卡号缺失'));
        }
        if(empty($quanid)){
            returnMsg(array('status'=>'02','codemsg'=>'消费劵缺失'));
        }
        if(empty($decuteNum)){
            returnMsg(array('status'=>'03','codemsg'=>'扣劵数量缺失'));
        }
        if(empty($panterid)){
            returnMsg(array('status'=>'04','codemsg'=>'消费商户缺失'));
        }
        $map = array('cardno'=>$cardno);
        $cards=M('cards');
        $card = $cards->where($map)->field('status')->find();
        if($card['status']!='Y'){
            returnMsg(array('status'=>'05','codemsg'=>'非正常卡号'));
        }
        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();
        if($quankind==false){
            returnMsg(array('status'=>'06','codemsg'=>'营销劵不存在'));
        }
        if($quankind['enddate']<date('Ymd',time())){
            returnMsg(array('status'=>'07','codemsg'=>'营销劵已过期'));
        }
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'c.cardno'=>$cardno,'a.amount'=>array('gt',0));
        $quanAmount=$cards->alias('c')->join('account a on a.customid=c.customid')->where($where)->sum('a.amount');
        if($quanAmount<$decuteNum){
            returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
        }
        $model=new Model();
        $model->startTrans();
        $consumeIf=$this->ticketExeByCardno($cardno,$quanid,$decuteNum,$panterid,$termposno);
        //returnMsg(array('status'=>'09','codemsg'=>$consumeIf));
        if($consumeIf==true){
            $model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功'));
        }else{
            $model->rollback();
            returnMsg(array('status'=>'09','codemsg'=>'营销劵消费失败'));
        }
    }
    protected function ticketExeByCardno($cardno,$quanid,$amount,$panterid,$termposno=null){
        $map=array('cardno'=>$cardno);
        $cards=M('cards');
        $model=new Model();
        $cardInfo=$cards->where($map)->field('customid')->find();
        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $map=array('tradeid'=>$tradeid);
        $c=M('trade_wastebooks')->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }
        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $customid=$cardInfo['customid'];
        $termno = $termposno ? $termposno : '0000000';
        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
        $tradeIf=$model->execute($tradeSql);

        $userid=$this->getUserid();
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $tdbSql="insert into ticket_decute_books values ('{$cardno}','{$quanid}','{$amount}','{$userid}','{$currentDate}','{$currentTime}')";
        $tdbIf=$model->execute($tdbSql);

        $accountSql="UPDATE ACCOUNT set amount=amount-{$amount} where customid='{$customid}' and type='02' and quanid='{$quanid}'";
        $accountIf=$model->execute($accountSql);
        if($accountIf==true&&$tradeIf==true&&$tdbIf==true){
            return $tradeid;
        }else{
            return false;
        }
    }
}
