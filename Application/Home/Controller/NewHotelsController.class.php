<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class NewHotelsController extends CommonController{
    //售卡报表页面
    function sellCard(){
        $this->display();
    }
    //售卡报表数据
    function getSellCardList(){
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');

        $start=I('get.startdate','','strip_tags');
        $end=I('get.enddate','','strip_tags');
        $cardno=trim(I('get.cardno','','strip_tags'));
        $panterid=trim(I('get.panterid','','strip_tags'));
        $pname=trim(I('get.pname','','strip_tags'));
        $linktel=trim(I('get.linktel','','strip_tags'));
        $username=trim(I('get.username','','strip_tags'));
        $cname=trim(I('get.cname','','strip_tags'));
        $where['a.tradeflag']=array('in','0,2');
        //$where['b.description']=array('in',array('后台充值','至尊币购卡','批量购卡'));
        $where['card.cardkind']='6688';
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['ca.activedate']=array('egt',$startdate);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['ca.activedate'] = array('elt',$enddate);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['ca.activedate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['ca.activedate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($panterid!=''){
            $where['ca.panterid'] = $panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
        }
        if($cardno!=''){
            $cardno=explode(',',$cardno);
            $where['b.cardno']=array('in',$cardno);
        }
        if($username!=''){
            $where['u.username'] = $username;
        }
        if($linktel!=''){
            $where['c.linktel'] = $linktel;
        }
        if(!empty($cname)){
            $where['c.namechinese'] = array('like','%'.$cname.'%');
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
        }
        session('sexcel',$where);
        $model=new Model();
        $field='c.customid,c.linktel,card.cardfee,a.purchaseid,a.tradeflag,b.Card_PurchaseId,b.cardno,';
        $field.='b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        //卡激活表--卡表--操作员表--会员编号对应表--会员信息表--商户表--充值表--购卡/充值单
        $results=$model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('__CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->order('b.card_purchaseid desc')->page($page,$pageSize)->field($field)->select();

        foreach($results as $k=>$v){
            $v['activation']=date('Y-m-d H:i:s',strtotime($v['activedate'].$v['activetime']));
            $v['amount']=floatval($v['amount']);
            $v['cardf']=$v['cardfee']=='1'?'实体卡':"虚拟卡";
            $rows[]=$v;
        }

        $total=$model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)
            ->count();
        $amount_sum=$model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->getField("sum(b.amount) amount_sum");
        $amount_sum=array('panterid'=>'合计金额：','pname'=>$amount_sum);
        $footer[]=$amount_sum;
        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($rows)?array_values($rows):'';
        $result['footer']=!empty($footer)?array_values($footer):'';
        $this->ajaxReturn($result);
    }
    /**
     * 酒店售卡报表导出Excel
     */
    public function sellCard_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'c.customid,c.linktel,card.cardfee,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        $smap = session('sexcel');
        foreach($smap as $key=>$val) {
            $where[$key] = $val;
        }
        $results = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('left join __CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('left join __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();

        foreach($results as $k=>$v){
            $v['activation']=date('Y-m-d H:i:s',strtotime($v['activedate'].$v['activetime']));
            $v['cardf']=$v['cardfee']==1?'实体卡':'虚拟卡';
            $rows[]=$v;
        }

        $tradeamounts = array_column($results,'amount');
        $sum=0;
        foreach ($tradeamounts as $key=>$val){
            //bcadd() 2个任意精度数字相加
            $sum=bcadd($sum,$val,2);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge="A1:K3";
        $titleName='酒店售卡报表';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='K4';
        $headerArray = array('发卡机构编号','发卡机构名称','会员编号',
                           '会员名称','会员手机号','卡号',
                           '卡号类型编号','卡种','激活时间','卡初始金额',
                           '操作员'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K');
        $setWidth = array(15,48,13,13,15,25,15,25,15,15,12,18);

        $this->setWidth($setCells, $setWidth);

        $j=5;
        foreach ($rows as $key => $val){
            $objSheet->setCellValueExplicit("I".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A'.$j,"'".$val['panterid'])->setCellValue('B'.$j,$val['pname'])->setCellValue('C'.$j,"'".$val['customid'])
                   ->setCellValue('D'.$j,$val['cname'])->setCellValue('E'.$j,"'".$val['linktel'])->setCellValue('F'.$j,"'".$val['cardno'])
                   ->setCellValue('G'.$j,"'".$val['cardkind'])->setCellValue('H'.$j,$val['cardf'])->setCellValue('I'.$j,$val['activation'])
                   ->setCellValue('J'.$j,$val['amount'])->setCellValue('K'.$j,$j,$val['username']);
            $j++;
        }
        $objSheet->getStyle('H'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('H'.$j,'合计金额:');
        $objSheet->setCellValueExplicit("I".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $objSheet->setCellValue('I'.$j,$sum);
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','酒店售卡报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //充值报表页面
    function recharge(){
        $this->display();
    }
    //充值报表数据
    public function getRechargeList(){
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');

        $where['card.cardkind']='6688';
        $start = trim(I('get.startdate',''));//开始日期
        $end = trim(I('get.enddate',''));//结束日期
        $panterid = trim(I('get.panterid',''));//机构编号
        $pname  = trim(I('get.pname',''));//机构名称
        $cardno = trim(I('get.cardno',''));//卡号
        $username= trim(I('get.username',''));//操作员
        $purchaseid= trim(I('get.cpurchaseid',''));//充值流水号
        $status = trim(I('get.status',''));//卡状态
        $customid = trim(I('get.customid',''));//会员编号
        $cname = trim(I('get.cname',''));//会员名称
        $linktel = trim(I('get.linktel',''));//会员手机号
        $pname = $pname=="机构名称"?"":$pname;
        $cardno = $cardno=="卡号"?"":$cardno;
        $purchaseid = $purchaseid=="充值流水号"?"":$purchaseid;
        $customid = $customid=="会员编号"?"":$customid;
        $cname = $cname=="会员名称"?"":$cname;
        $linktel=$linktel=="手机号"?"":$linktel;
        $username = $username=="操作员"?"":$username;
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['b.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['b.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($panterid!=''){
            $where['b.panterid'] = $panterid;
        }
        if($pname!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
        }
        if($cardno!=''){
            $cardno=explode(',',$cardno);
            $where['b.cardno']=array('in',$cardno);
        }
        if($linktel!=''){
            $where['c.linktel']=$linktel;
        }
        if($username!=''){
            $where['u.username']=array('like','%'.$username.'%');
        }
        if($purchaseid!=''){
            $where['b.Card_PurchaseId']=$purchaseid;
        }
        if($status!=''){
            $where['card.status']=$status;
        }
        if($customid!=''){
            $where['c.customid']=$customid;
        }
        if($cname!=''){
            $where['c.namechinese']=array('like','%'.$cname.'%');
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
            if($this->panterid=='00000013'){
                $this->assign('is_admin',1);
            }else{
                $this->assign('is_admin',0);
            }
        }else{
            $this->assign('is_admin',1);
        }
        session('rexcel',$where);
        $model=new Model();
        $field='c.customid,c.linktel,card.cardfee,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field.='b.amount amount,b.point,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        //充值表--卡表--商户表--会员编号对应表--会员信息表--操作员表
        $results=$model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->order('b.card_purchaseid desc')->page($page,$pageSize)->field($field)->select();
        //echo $model->getLastSql();

        foreach($results as $k=>$v){
            $v['activation']=date('Y-m-d H:i:s',strtotime($v['placeddate'].$v['placedtime']));
            $v['amount']=floatval($v['amount']);
            $v['cardf']=$v['cardfee']=='1'?'实体卡':"虚拟卡";
            switch($v['status']){
                case A:
                    $v['status']='待激活';
                    break;
                case D:
                    $v['status']='销卡';
                    break;
                case R:
                    $v['status']='退卡';
                    break;
                case S:
                    $v['status']='过期';
                    break;
                case N:
                    $v['status']='新卡';
                    break;
                case L:
                    $v['status']='锁定';
                    break;
                case Y:
                    $v['status']='正常卡';
                    break;
                case W:
                    $v['status']='无卡';
                    break;
                case C:
                    $v['status']='已出库';
                    break;
                case G:
                    $v['status']='异常锁定';
                    break;
            }
            $rows[]=$v;
        }

        $total=$model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)
            ->count();
        $amount_sum=$model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            //->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->getField("sum(b.amount) amount_sum");
        $amount_sum=array('panterid'=>'合计金额：','pname'=>$amount_sum);
        $footer[]=$amount_sum;
        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($rows)?array_values($rows):'';
        $result['footer']=!empty($footer)?array_values($footer):'';
        $this->ajaxReturn($result);
    }
    /**
     * 酒店充值报表导出Excel
     */
    public function recharge_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model=new Model();
        $field='c.customid,c.linktel,card.cardfee,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field.='b.amount amount,b.point,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        $rmap=session('rexcel');
        foreach($rmap as $key=>$val){
            $where[$key]=$val;
        }

        $results=$model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();

        foreach($results as $k=>$v){
            $v['activation']=date('Y-m-d H:i:s',strtotime($v['placeddate'].$v['placedtime']));
            $v['amount']=floatval($v['amount']);
            switch($v['status']){
                case A:
                    $v['status']='待激活';
                    break;
                case D:
                    $v['status']='销卡';
                    break;
                case R:
                    $v['status']='退卡';
                    break;
                case S:
                    $v['status']='过期';
                    break;
                case N:
                    $v['status']='新卡';
                    break;
                case L:
                    $v['status']='锁定';
                    break;
                case Y:
                    $v['status']='正常卡';
                    break;
                case W:
                    $v['status']='无卡';
                    break;
                case C:
                    $v['status']='已出库';
                    break;
                case G:
                    $v['status']='异常锁定';
                    break;
            }
            $rows[]=$v;
        }

        $tradeamounts = array_column($results,'amount');
        $sum=0;
        foreach ($tradeamounts as $key=>$val){
            //bcadd() 2个任意精度数字相加
            $sum=bcadd($sum,$val,2);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge="A1:N3";
        $titleName='酒店充值报表';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='N4';
        $headerArray = array('发卡机构编号','发卡机构名称','会员编号',
            '会员名称','会员手机号','卡号',
            '卡号类型编号','卡状态','充值流水号',
            '充值时间','充值单流水','充值金额','操作员','备注'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','I','J','K','L','N');
        $setWidth = array(15,48,13,13,15,25,15,20,20,20,12,50);
        $this->setWidth($setCells, $setWidth);

        $j=5;
        foreach ($rows as $key => $val){
            $objSheet->setCellValueExplicit("L".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A'.$j,"'".$val['panterid'])->setCellValue('B'.$j,$val['pname'])->setCellValue('C'.$j,"'".$val['customid'])
                ->setCellValue('D'.$j,$val['cname'])->setCellValue('E'.$j,"'".$val['linktel'])->setCellValue('F'.$j,"'".$val['cardno'])
                ->setCellValue('G'.$j,"'".$val['cardkind'])->setCellValue('H'.$j,$val['status'])->setCellValue('I'.$j,"'".$val['cpurchaseid'])
                ->setCellValue('J'.$j,$val['activation'])->setCellValue('K'.$j,"'".$val['purchaseid'])->setCellValue('L'.$j,$val['amount'])
                ->setCellValue('M'.$j,$val['username'])->setCellValue('N'.$j,$val['description']);
            $j++;
        }
        $objSheet->getStyle('K'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('K'.$j,'合计总额:');
        $objSheet->setCellValueExplicit("L".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $objSheet->setCellValue('L'.$j,$sum);
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','酒店充值报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    public function newConsume(){
        $parents_lists = array('00000125'=>'雅乐轩酒店','00000126'=>'艾美酒店','00000127'=>'福朋酒店','00000118'=>'南阳假日酒店','00000270'=>'铂尔曼酒店');
        $jytype=array('00'=>'至尊卡消费','01'=>'积分消费','17'=>'预授权','21'=>'预授权完成');
        $this->assign('tradetype',$jytype);
        $this->assign('parents',$parents_lists);
        $this->display();
    }

//酒店消费明细报表列表数据
    public function searchConsume() {
        $jytype=array(
            '00'=>'至尊卡消费',
            '01'=>'积分消费',
            '13'=>'现金消费',
            '17'=>'预授权',
            '19'=>'预授权撤销',
            '21'=>'预授权完成'
            );
        $page = $_GET['page'];
        $pageSize = $_GET['rows'];
        $cardno = trim(I('get.cardno',''));
        $start = trim(I('get.startdate',''));
        if($start==''){
            $start=date('Y-m-01',time());
        }
        $end = trim(I('get.enddate',''));
        $cuname = trim(I('get.cuname',''));
        $panterid = trim(I('get.panterid',''));
        //$parents = trim(I('get.parents',''));
        $pname = trim(I('get.pname',''));
        $linktel = trim(I('get.linktel',''));
        $tradetype = trim(I('get.tradetype',''));
        $start = str_replace('-','',$start);
        $end = str_replace('-','',$end);
        if($cardno!=''){
            $where['tw.cardno']=$cardno;
        }
        if($panterid!=''){
            $where['p1.panterid']=$panterid;
        }
        if($pname!=''){
            $where['p1.namechinese'] =array('like','%'.$pname.'%');
        }
        if($cuname!=''){
            $where['cu.namechinese'] = $cuname;
        }
        if($linktel!=''){
            $where['cu.linktel'] = $linktel;
        }
        if($start!=''&& $end==''){
            $where['tw.placeddate']=array('egt',$start);
        }
        if($start=='' && $end!=''){
            $where['tw.placeddate'] = array('elt',$end);
        }
        if($start!='' && $end!=''){
            $where['tw.placeddate'] = array(array('egt',$start),array('elt',$end));
        }
        $first = $pageSize * ($page - 1);
        $model = new model();
//     $this->panterid = '00000013';
        if($this->panterid != 'FFFFFFFF'){
            $where['_string']=" p.panterid='{$this->panterid}' OR p.parent='{$this->panterid}'";
        }
        $where['c.cardkind']='6688';
        $where['tw.flag'] = 0;
        $where1=$where;
        if($tradetype!=''){
            $where['tw.tradetype'] = $tradetype;
        }else{
            $where['tw.tradetype'] = array('in',array('00','17','21'));
        }
        $where['tw1.tradetype']=array('in',array('01','03','06'));
        $where['tw1.flag']=0;
        $field='tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,tw.eorderid,';
        $field.='tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,';
        $field.='p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,';
        $field.='bd.placeddate bdate,bd.placedtime btime,bd.amount billamount,bd.card_purchaseid,';
        $field.='cpl.amount rechargeamount,tb.status tbstatus,tb.amount billedamount,cpl.placeddate cdate,';
        $field.='cpl.placedtime ctime,tw1.tradeid pointid,tw1.tradeamount point';
        $count=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join tradebilling tb on tb.tradeid=tw.tradeid')
            ->join('left join billingdetail bd on tw.tradeid=bd.tradeid')
            ->join('left join card_purchase_logs cpl on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw1 on tw.eorderid=tw1.eorderid')
            ->field($field)->where($where)->count();
        $amount_sum=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join trade_wastebooks tw1 on tw.eorderid=tw1.eorderid')
            ->field('sum(tw.tradeamount) amount_sum')
            ->where($where)->find();
        //echo $count;exit;
        $interConsume=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join tradebilling tb on tb.tradeid=tw.tradeid')
            ->join('left join billingdetail bd on tw.tradeid=bd.tradeid')
            ->join('left join card_purchase_logs cpl on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw1 on tw.eorderid=tw1.eorderid')
            ->field($field)->where($where)->page($page,$pageSize)
            ->order('tw1.tradeid asc,tw.tradeid asc,tw.placeddate desc,tw.placedtime asc')->select();
        //echo $model->getLastSql();exit;
        //print_r($interConsume);
        session('newConsume',$where);
        session('consumeSum',$amount_sum['amount_sum']);
        $referId='';
        $referPointId='';
        $array=array();
        $pointArray=array();
        if(!empty($interConsume)){
            foreach ($interConsume as $key=>$val){
                $tradeid=trim($val['tradeid']);
                $pointid=trim($val['pointid']);
                if($val['tradetype']=='17'||$val['tradetype']=='19'){
                    if($val['tradetype']=='17'||$val['tradetype']=='19'){
                        $val['status']='预授权订单不开具发票';
                    }
                    $val['btstring']='';
                }else{
                    if(empty($val['tbstatus'])){
                        $val['status']='未开发票';
                        $val['btstring']='<button  class="easyui-linkbutton l-btn l-btn-small" style="width:50px;" onclick="javascript:bill(\''.trim($val['tradeid']).'\',\''.$val['tradeamount'].'\');" >开发票</button>';
                    }else{
                        if($val['tbstatus']==1){
                            $val['status']='已开发票';
                            $val['btstring']='';
                        }else{
                            if($val['billedamount']>0){
                                $waitAmount=$val['tradeamount']-$val['billedamount'];
                                $val['status']='已开发票:'.$val['billedamount'].',未开发票:'.$waitAmount;
                                $val['btstring']='<button class="easyui-linkbutton l-btn l-btn-small" style="width:50px;" onclick="javascript:bill(\''.trim($val['tradeid']).'\',\''.$waitAmount.'\');">开发票</button>';
                            }else{
                                $val['status']='未开发票';
                                $val['btstring']='<button class="easyui-linkbutton l-btn l-btn-small" style="width:50px;" onclick="javascript:bill(\''.trim($val['tradeid']).'\',\''.$val['tradeamount'].'\');">开发票</button>';
                            }
                        }
                    }
                }
//                if($key==1){
//                    $tradeid=$referId;
//                    $pointid=$referPointId;
//                }
                if($referId==$tradeid){
                    if(!isset( $array[$tradeid]['index'])){
                        $array[$tradeid]['index']=$key;
                    }
                    $array[$tradeid]['rowspan']+=1;
                }else{
                    $array[$tradeid]['index']=$key;
                    $array[$tradeid]['rowspan']=1;
                }
                if($referPointId==$pointid){
                    if(!isset( $pointArray[$pointid]['index'])){
                        $pointArray[$pointid]['index']=$key;
                    }
                    $pointArray[$pointid]['rowspan']+=1;
                }else{
                    $pointArray[$pointid]['index']=$key;
                    $pointArray[$pointid]['rowspan']=1;
                }
                $val['tradeid']=$tradeid;
                $val['tradetype']=$jytype[$val['tradetype']];
                $val['tradeamount'] =  floatval($val['tradeamount']);
                $val['tradetime'] = date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
                $val['bdate']=!empty($val['bdate'])?date('Y-m-d',strtotime($val['bdate'])).' '.$val['btime']:'-';
                $val['cdate']=!empty($val['cdate'])?date('Y-m-d',strtotime($val['cdate'])).' '.$val['ctime']:'-';
                $val['flag']=$val['flag']=='0'?'交易成功':$val['flag'];
                $val['billedamount'] =  floatval($val['billedamount']);
                $val['billamount'] =  floatval($val['billamount']);
                $val['rechargeamount'] =  floatval($val['rechargeamount']);
                $referId=$tradeid;
                $referPointId=$pointid;
                $json.=json_encode($val).',';
            }
        }
        //exit;
        //print_r($array);exit;
        $merges=array();
        $pointMerges=array();
        if(!empty($array)){
            foreach($array as $key=>$val){
                if($val['rowspan']>1){
                    $merges[]=$val;
                }
            }
            foreach($pointArray as $key=>$val){
                if($val['rowspan']>1){
                    $pointMerges[]=$val;
                }
            }
        }
        $merges=json_encode($merges);
        $pointMerges=json_encode($pointMerges);
        $sum="合计消费金额： ".$amount_sum['amount_sum'].'(撤销金额不计入)';
        $sum =json_encode($sum);
        $json = substr($json, 0, -1);
        echo '{"total" : '.$count.', "rows" : ['.$json.'], "footer" : [{"tradeamount" : '.$sum.' }],"merges":'.$merges.',"pointMerges":'.$pointMerges.'}';
    }
    public  function newConsume_excel(){
        $model = new model();
        $this->setTimeMemory();
        $where=session('newConsume');
        $jytype=array(
            '00'=>'至尊卡消费',
            '01'=>'积分消费',
            '13'=>'现金消费',
            '17'=>'预授权',
            '19'=>'预授权撤销',
            '21'=>'预授权完成');
        $field='tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,tw.eorderid,';
        $field.='tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,';
        $field.='p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,';
        $field.='bd.placeddate bdate,bd.placedtime btime,bd.amount billamount,bd.card_purchaseid,';
        $field.='cpl.amount rechargeamount,tb.status tbstatus,tb.amount billedamount,cpl.placeddate cdate,';
        $field.='cpl.placedtime ctime,tw1.tradeid pointid,tw1.tradeamount point';
        $list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join tradebilling tb on tb.tradeid=tw.tradeid')
            ->join('left join billingdetail bd on tw.tradeid=bd.tradeid')
            ->join('left join card_purchase_logs cpl on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw1 on tw.eorderid=tw1.eorderid')
            ->field($field)->where($where)->select();
        $consumeSum=session('consumeSum');

        $refer=array();
        $refer1=array();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $k=$refer['key'];
                $k1=$refer1['key'];
                //if($key==1) $val['tradeid']=$referId;
                if($val['tradeid']==$refer['tradeid']){
                    $list[$k]['rowspan']+=empty($list[$k]['rowspan'])?2:1;
                    $list[$key]['rowspan']+=-1;
                }else{
                    $refer=array('tradeid'=>$val['tradeid'],'key'=>$key);
                }
                if($val['point']==$refer1['pointid']){
                    $list[$k1]['rowspan1']+=empty($list[$k1]['rowspan1'])?2:1;
                    $list[$key]['rowspan1']+=-1;
                }else{
                    $refer1=array('pointid'=>$val['pointid'],'key'=>$key);
                }
            }
        }
        $strlist="<tr><th>会员名字</th><th>联系电话</th><th>商户名称</th><th>消费机构</th><th>终端编号</th><th>卡号</th>";
        $strlist.="<th>交易状态</th><th>交易类型</th><th>交易时间</th><th>交易流水号</th><th>交易金额</th><th>交易积分</th>";
        $strlist.="<th>开发票情况</th><th>已开发票总金额</th><th>发票充值单号</th><th>开发票金额</th><th>开发票时间</th>";
        $strlist.="<th>发票充值单号金额</th><th>充值时间</th></tr>";
        $strlist=$this->changeCode($strlist);
        foreach($list as $key=>$val){
            if($val['tradetype']=='17'||$val['tradetype']=='19'){
                $val['status']=iconv("utf-8","gbk",'预授权订单不开具发票');
            }else{
                if(empty($val['tbstatus'])){
                    $val['status']=iconv("utf-8","gbk",'未开发票');
                }else{
                    if($val['tbstatus']==1){
                        $interConsume[$key]['status']=iconv("utf-8","gbk",'已开发票');
                    }else{
                        if($val['billedamount']>0){
                            $val['status']=iconv("utf-8","gbk",'未全部开发票');
                        }else{
                            $val['status']=iconv("utf-8","gbk",'未开发票');
                        }
                    }
                }
            }
            $val['cuname']=iconv("utf-8","gbk",$val['cuname']);
            $val['pname1']=iconv("utf-8","gbk",$val['pname1']);
            $val['pname']=iconv("utf-8","gbk",$val['pname']);
            $val['tradetype']=iconv("utf-8","gbk",$jytype[$val['tradetype']]);
            $val['tradeamount'] =  floatval($val['tradeamount']);
            $val['tradetime'] = date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $val['bdate']=!empty($v['bdate'])?date('Y-m-d',strtotime($v['bdate'])).' '.$v['btime']:'-';
            $val['cdate']=!empty($v['cdate'])?date('Y-m-d',strtotime($v['cdate'])).' '.$v['ctime']:'-';
            $val['flag']=$val['flag']=='0'?iconv("utf-8","gbk",'交易成功'):$val['flag'];
            $val['billedamount'] =  floatval($val['billedamount']);
            $val['billamount'] =  floatval($val['billamount']);
            $val['rechargeamount'] =  floatval($val['rechargeamount']);
            if($val['rowspan']!='-1'){
                if(!empty($val['rowspan'])){
                    $rowspan=" rowspan='{$val['rowspan']}'";
                }else{
                    $rowspan="";
                }
                if(!empty($val['rowspan1'])){
                    $rowspan1=" rowspan1='{$val['rowspan1']}'";
                }else{
                    $rowspan1="";
                }
                $strlist.="<tr><td {$rowspan1}>{$val['cuname']}</td><td {$rowspan1}>{$val['linktel']}</td><td {$rowspan1}>{$val['pname1']}</td>";
                $strlist.="<td {$rowspan1}>{$val['pname']}</td><td {$rowspan1}>{$val['termposno']}</td><td {$rowspan}>{$val['cardno']}</td>";
                $strlist.="<td {$rowspan}>{$val['flag']}</td><td {$rowspan}>{$val['tradetype']}</td><td {$rowspan}>{$val['tradetime']}</td>";
                $strlist.="<td {$rowspan}>{$val['tradeid']}</td><td {$rowspan}>{$val['tradeamount']}</td><td {$rowspan1}>{$val['point']}</td>";
                $strlist.="<td {$rowspan}>{$val['status']}</td><td {$rowspan}>{$val['billedamount']}</td>";
            }
            $strlist.="<td>{$val['card_purchaseid']}</td><td>{$val['billamount']}</td><td>{$val['bdate']}</td><td>{$val['rechargeamount']}</td><td>{$val['cdate']}</td></tr>";
        }
        $strlist.="<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
        $strlist.="<td colspan='2' align='right'>{$consumeSum}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
        $filename='酒店新卡消费报表报表'.date('YmdHis').'.xls';
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->export_xls($filename,$strlist);
    }

    public function invoice(){
        $this->display();
    }
    public function getInvoiceList(){
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');
        $start = trim(I('get.startdate',''));//开始日期
        $end = trim(I('get.enddate',''));//结束日期
        $cuname=trim(I('get.cuname',''));//结束日期
        $linktel = trim(I('get.linktel',''));//手机号码
        $status=trim(I('get.status',''));//卡号
        $cardno = trim(I('get.cardno',''));//卡号
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cpl.placeddate']=array('egt',$startdate);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate'] = array('elt',$enddate);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if(!empty($cuname)){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
        }
        if(!empty($linktel)){
            $where['cu.linktel']=$linktel;
        }
        if(!empty($cardno)){
            $where['cpl.cardno']=$cardno;
        }
        if($status!==''&&$status!='-1'){
            $where['b.status']=$status;
        }

        $where['c.cardkind']='6688';
        $where['cpl.amount']=array('gt',0);
        $model = new model();
        $field="cpl.card_purchaseid,cpl.cardno,cpl.placeddate,cpl.placedtime,cpl.amount,b.usedamount,b.status,b.flag,";
        $field.="cu.linktel,cu.namechinese cuname,bd.placeddate bdate,bd.placedtime btime,bd.amount billamount,";
        $field.="bd.tradeid,tw.tradeamount,tw.placeddate twdate,tw.placedtime twtime";
        $total=$model->table('card_purchase_logs')->alias('cpl')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('billing b on cpl.card_purchaseid=b.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('left join billingdetail bd on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw on tw.tradeid=bd.tradeid')
            ->where($where)->field($field)->count();

        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('billing b on cpl.card_purchaseid=b.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('left join billingdetail bd on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw on tw.tradeid=bd.tradeid')
            ->where($where)->field($field)->page($page,$pageSize)
            ->order('cpl.placeddate desc,cpl.placedtime asc,cpl.card_purchaseid asc')->select();
        //echo $model->getLastSql();
        session('invoiceCon',$where);
        $referId='';
        $array=array();
        foreach($list as $k=>$v){
            //if($k==1) $v['card_purchaseid']=$referId;
            if($referId==$v['card_purchaseid']){
                if(!isset( $array[$v['card_purchaseid']]['index'])){
                    $array[$v['card_purchaseid']]['index']=$k;
                }
                $array[$v['card_purchaseid']]['rowspan']+=1;
            }else{
                $array[$v['card_purchaseid']]['index']=$k;
                $array[$v['card_purchaseid']]['rowspan']=1;
            }
            $v['date']=date('Y-m-d',strtotime($v['placeddate'])).' '.$v['placedtime'];
            $v['bdate']=!empty($v['bdate'])?date('Y-m-d',strtotime($v['bdate'])).' '.$v['btime']:'-';
            $v['twdate']=!empty($v['twdate'])?date('Y-m-d',strtotime($v['twdate'])).' '.$v['twtime']:'-';
            $v['amount']=floatval($v['amount']);
            $v['usedamount']=floatval($v['usedamount']);
            $v['billamount']=floatval($v['billamount']);
            $v['tradeamount']=floatval($v['tradeamount']);
            $v['status']=$v['status']=='1'?'充值开发票':"充值未开发票";
            $v['tradeid']=!empty($v['tradeid'])?$v['tradeid']:'无消费开发票记录';
            $rows[]=$v;
            $referId=$v['card_purchaseid'];
        }
        $merges=array();
        if(!empty($array)){
            foreach($array as $key=>$val){
                if($val['rowspan']>1){
                    $merges[]=$val;
                }
            }
        }
        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($rows)?array_values($rows):'';
        $result['footer']=!empty($footer)?array_values($footer):'';
        $result['merges']=!empty($merges)?$merges:'';
        $this->ajaxReturn($result);
    }

    public function invoice_excel(){
        $where=session('invoiceCon');
        $field="cpl.card_purchaseid,cpl.cardno,cpl.placeddate,cpl.placedtime,cpl.amount,b.usedamount,b.status,b.flag,";
        $field.="cu.linktel,cu.namechinese cuname,bd.placeddate bdate,bd.placedtime btime,bd.amount billamount,";
        $field.="bd.tradeid,tw.tradeamount,tw.placeddate twdate,tw.placedtime twtime";
        $model = new model();
        $list=$model->table('card_purchase_logs')->alias('cpl')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('billing b on cpl.card_purchaseid=b.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('left join billingdetail bd on cpl.card_purchaseid=bd.card_purchaseid')
            ->join('left join trade_wastebooks tw on tw.tradeid=bd.tradeid')
            ->where($where)->field($field)->order('cpl.placeddate desc,cpl.placedtime asc,cpl.card_purchaseid asc')->select();
        $refer=array();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $k=$refer['key'];
                //if($key==1) $val['tradeid']=$referId;
                if($val['card_purchaseid']==$refer['card_purchaseid']){
                    $list[$k]['rowspan']+=empty($list[$k]['rowspan'])?2:1;
                    $list[$key]['rowspan']+=-1;
                }else{
                    $refer=array('card_purchaseid'=>$val['card_purchaseid'],'key'=>$key);
                }
            }
        }
        $strlist="<tr><th>会员名字</th><th>联系电话</th><th>至尊卡号</th><th>充值日期</th><th>充值金额</th>";
        $strlist.="<th>充值编号</th><th>开发票类型</th><th>已开发票金额</th><th>消费编号</th><th>开发票时间</th><th>开发票金额</th>";
        $strlist.="<th>消费时间</th><th>消费金额</th></tr>";
        $strlist=$this->changeCode($strlist);
        foreach($list as $k=>$v){
            $v['date']=date('Y-m-d',strtotime($v['placeddate'])).' '.$v['placedtime'];
            $v['bdate']=!empty($v['bdate'])?date('Y-m-d',strtotime($v['bdate'])).' '.$v['btime']:'-';
            $v['twdate']=!empty($v['twdate'])?date('Y-m-d',strtotime($v['twdate'])).' '.$v['twtime']:'-';
            $v['amount']=floatval($v['amount']);
            $v['usedamount']=floatval($v['usedamount']);
            $v['billamount']=floatval($v['billamount']);
            $v['tradeamount']=floatval($v['tradeamount']);
            $v['status']=$v['status']=='1'?iconv("utf-8","gbk",'充值开发票'):iconv("utf-8","gbk","充值未开发票");
            $v['tradeid']=!empty($v['tradeid'])?$v['tradeid']:iconv("utf-8","gbk",'无消费开发票记录');
            $v['cuname']=iconv("utf-8","gbk",$v['cuname']);
            if($val['rowspan']!='-1'){
                if(!empty($val['rowspan'])){
                    $rowspan=" rowspan='{$val['rowspan']}'";
                }else{
                    $rowspan="";
                }
                $strlist.="<tr><td {$rowspan}>{$v['cuname']}</td><td {$rowspan}>{$v['linktel']}</td>";
                $strlist.="<td {$rowspan}>{$v['cardno']}</td><td {$rowspan}>{$v['date']}</td><td {$rowspan}>{$v['amount']}</td>";
                $strlist.="<td {$rowspan}>{$v['card_purchaseid']}</td><td {$rowspan}>{$v['status']}</td><td {$rowspan}>{$v['usedamount']}</td>";
            }
            $strlist.="<td>{$v['tradeid']}</td><td>{$v['bdate']}</td><td>{$v['billamount']}</td><td>{$v['twdate']}</td><td>{$v['tradeamount']}</td></tr>";
        }
        $filename='酒店新卡发票报表'.date('YmdHis').'.xls';
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->export_xls($filename,$strlist);
    }

    //商户日结报表
    public function dailyReport(){
        $panters= array('00000125'=>'雅乐轩酒店','00000126'=>'艾美酒店','00000127'=>'福朋酒店','00000118'=>'南阳假日酒店','00000270'=>'铂尔曼酒店');
        $this->assign('panters',$panters);
        $this->display();
    }

    //ajax获取商户日结数据
    public function getDailyReportList(){
        $model=new model;
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');
        $start = I('get.startdate','');
        $end = I('get.enddate','');
        $panterid = trim(I('get.panterid',''));
        $jsname = trim(I('get.jsname',''));
        if (!empty($start) && empty($start)) {
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
        if(!empty($jsname)){
            $where['p.settleaccountname'] = array('like','%'.$jsname.'%');
            $this->assign('jsname',$jsname);
            $map['jsname']=$jsname;
        }
        $first = $pageSize * ($page - 1);
        //$where['_string']="p.hysx <> '酒店' or p.hysx is null";
        //$where['p.panterid']=array('not in',array('00000286','00000290'));
        $where['_string']="p.hysx='酒店'";
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
        }
        $field='tp.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate';
        $amount_sum=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
        $list=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')->where($where)->field($field)
            ->limit($first,$pageSize)->order('tp.statdate desc')->select();
        //echo $model->getLastSql();
        session('dailyReportxcel',$where);

        foreach($list as $key=>$val){
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
            $list[$key]['tradeamount']=$tradeamount;
            $list[$key]['rate']=$rate;
            $list[$key]['jsamount']=floatval($jsamount-$fuamount);
            $list[$key]['fuamount']=floatval($fuamount);
            $list[$key]['sxf']=$sxf;
        }
        $amount_sum=array('panterid'=>'合计金额：','pname'=>$amount_sum['amount_sum']);
        $footer[]=$amount_sum;

        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($list)?array_values($list):'';
        $result['footer']=!empty($footer)?array_values($footer):'';
        $this->ajaxReturn($result);
    }

    //商户日结报表导出
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
        $strlist="序号,商户编号,商户名称,结算日期,交易笔数,交易金额,";
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
            $fwrate=floatval($val['servicerate']);
            $rate=floatval($val['rate']);
            $jsamount=round(floatval($tradeamount*$rate/100),2);
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
            $strlist.="\t,".$val['tradequantity'].','.$tradeamount.','.$rate."\t,".$sxf;
            $strlist.=",".$fuamount.",".($jsamount-$fuamount).",".$val['settleaccountname'].',';
            $strlist.=$val['settlebankid']."\t,".$val['settlebank'].','.$val['settlebankname']."\n";
        }
        $filename='商户日报表'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        unset($panterList);
        $strlist.=',,,,,'.$amount_sum['amount_sum'].",,,".$sxfs.",".$fuamounts.",".$setamount.",,,,\n";
        $this->load_csv($strlist,$filename);
    }

    //余额报表
    public function balance(){
        $this->display();
    }
    //ajax获取余额数据
    public function getBalanceList(){
        $model = new Model();
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//会员名称
        $cardno = $cardno=="卡号"?"":$cardno;
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        if($cardno!=''){
            $where['a.cardno']=$cardno;
            $this->assign('cardno',$cardno);
        }
        if($customid!=''){
            $where['a.customid']=$customid;
        }
        if($cuname!=''){
            $where['a.cuname']=array('like','%'.$cuname.'%');
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['a.panterid']=$this->panterid;
            $where1['a.parent']=$this->panterid;
            $where1['_logic']='or';
            $where['_complex']=$where1;
        }
        $first = $pageSize * ($page - 1);
        $where['a.cardkind']='6688';
        $where['b.type']='04';
        $field='a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(r.tradeamount,0) tradepoint,nvl(q.amount,0) amount';
        $subQuery1="(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,h.cardfee,c.amount as cardbalance,";
        $subQuery1.="f.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1.="where h.customid=c.customid and h.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1.="and m.customid=b.customid and c.type='00')";

        $subQuery2="(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2.="where z.flag=1  group by z.cardno )";

        $subQuery3="(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3.="trade_wastebooks e where e.flag='0' and e.tradetype in ('00','17','21') group by e.cardno )";

        //期末消费总金额
        $subQuery4="(select nvl(sum(e.tradeamount),0) tradeamount,e.cardno from ";
        $subQuery4.="trade_wastebooks e where e.flag='0' and e.tradetype in ('01','03','06') group by e.cardno )";

        $total=$model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->where($where)->field($field)->count();

        $list=$model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
            ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
            ->join('left join '.$subQuery4.' r on r.cardno=a.cardno')
            ->where($where)->field($field)
            ->order('a.cuname asc')->limit($first,$pageSize)->select();
        foreach($list as $key=>$val){
            $list[$key]['cardfee']=$val['cardfee']==1?'实体卡':'虚拟卡';
        }

        $result=array();
        $result['total']=!empty($total)?$total:0 ;
        $result['rows']=!empty($list)?array_values($list):'';
        $this->ajaxReturn($result);
    }

    public function bill(){
        $tradeid=trim($_REQUEST['tradeid']);
        $amount=trim($_REQUEST['amount']);
        $model=new Model();
        if(empty($tradeid)){
            $this->error('订单号缺失');
        }
        if(empty($amount)){
            $this->error('开发票金额缺失');
        }
        $map=array('tradeid'=>$tradeid);
        $list=M('trade_wastebooks')->where($map)->find();
        if($list==false){
            $this->error('无此消费记录');
        }
        if($list['tradeamount']<$amount){
            $this->error('发票金额大于订单易金额');
        }
        $tradeBillingList=$model->table('tradebilling')->where($map)->find();
        $model->startTrans();
        if($tradeBillingList==false){
            if($amount!=$list['tradeamount']){
                $this->error('发票金额与交易金额不符');
            }
            //$tradeBillingSql="INSERT INTO TRADEBILLING VALUES ('{$tradeid}',1,'{$amount}')";
        }else{
            if($tradeBillingList['status']==1){
                $this->error('该交易已经开过发票');
            }
            if($tradeBillingList['amount']+$amount>$list['tradeamount']){
                $this->error('交易订单总开发票金额大于交易金额');
            }
        }
        //$tradeBillingIf=$model->execute($tradeBillingSql);
        $usableAmount=$this->checkBillAccount($list['cardno'],'00000013');
        if($list['amount']+$amount>$usableAmount){
            $this->error('发票金额超出可用发票金额');
        }
        $usableList=$this->getUsableBillList($list['cardno'],'00000013');
        $bool=$this->billingExe($usableList,$amount,$tradeid);
        if($bool==true){
            $model->commit();
            $this->success('开发票成功');
        }else{
            $model->rollback();
            $this->error('开发票失败');
        }
    }

    //获取可开发票最大金额（未开发票的充值）
    public function checkBillAccount($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>0,'b.flag'=>0);
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field('sum(cpl.amount-b.usedamount) usableamount')->find();
        return $list['usableamount'];
    }

    //获取可开发票的充值列表(充值时未开发票的充值)
    public function getUsableBillList($cardno,$panterid){
        $map=array('panterid'=>$panterid);
        $panter=M('panters')->where($map)->field('parent')->find();
        $where=array('cpl.cardno'=>$cardno,'b.status'=>0,'b.flag'=>0);
        $field="cpl.*,b.status,b.usedamount";
        $where['_string']="cpl.panterid='{$panterid}' OR cpl.panterid='{$panter['parent']}'";
        $list=M('card_purchase_logs')->alias('cpl')->join('billing b on b.card_purchaseid=cpl.card_purchaseid')
            ->where($where)->field($field)->order('cpl.placeddate')->select();
        return $list;
    }

    //开发票操作执行
    public function billingExe($billList,$amount,$tradeid){
        if(empty($billList)) return false;
        $waitAmount=$amount;
        $exedAmount=0;
        $tradeBill=M('tradebilling');
        $billingDetail=M('billingdetail');
        $trade_wastebooks=M('trade_wastebooks');
        $model=new Model();
        foreach($billList as $key=>$val){
            if($waitAmount<=0) break;
            $cardPurchaseid=$val['card_purchaseid'];
            $cardno=$val['cardno'];
            $currentDte=date('Ymd');
            $currentTime=date('H:i:s');
            $usableAmount=$val['amount']-$val['usedamount'];
            if($usableAmount<=$waitAmount){
                $exeAmount=$usableAmount;
                $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)+'{$exeAmount}',FLAG=1 WHERE CARD_PURCHASEID='{$cardPurchaseid}'";
            }else{
                $exeAmount=$waitAmount;
                $billingSql="UPDATE BILLING SET USEDAMOUNT=NVL(USEDAMOUNT,0)+'{$exeAmount}' WHERE CARD_PURCHASEID='{$cardPurchaseid}'";
            }
            $billingDeataiSql="INSERT INTO BILLINGDETAIL VALUES('{$cardPurchaseid}','{$cardno}','{$currentDte}','{$currentTime}','{$exeAmount}','{$tradeid}')";
            $billIf=$model->execute($billingSql);
            $billingDeataiIf=$model->execute($billingDeataiSql);
            $map=array('tradeid'=>$tradeid);
            $list=$tradeBill->where($map)->find();
            $list1=$trade_wastebooks->where($map)->find();
            if($list==false){
                if($exeAmount<$list1['tradeamount']){
                    $tradeBillingSql="INSERT INTO TRADEBILLING VALUES ('{$tradeid}','0','{$exeAmount}')";
                }else{
                    $tradeBillingSql="INSERT INTO TRADEBILLING VALUES ('{$tradeid}','1','{$exeAmount}')";
                }
            }else{
                $billedAccount=$billingDetail->where($map)->sum('amount');
                if($billedAccount+$exeAmount>=$list1['tradeamount']){
                    $tradeBillingSql="UPDATE TRADEBILLING SET STATUS=1,AMOUNT=AMOUNT+{$exeAmount} WHERE tradeid='{$tradeid}'";
                }else{
                    $tradeBillingSql="UPDATE TRADEBILLING SET AMOUNT=AMOUNT+{$exeAmount} WHERE tradeid='{$tradeid}'";
                }
            }
            $tradeBillingIf=$model->execute($tradeBillingSql);
            if($billIf==true&&$billingDeataiIf==true&&$tradeBillingIf==true){
                $waitAmount=$waitAmount-$exeAmount;
                $exedAmount=$exedAmount+$exeAmount;
            }
        }
        if($exedAmount==$amount){
            return floatval($amount);
        }else{
            return false;
        }
    }

    //民享酒店会员报表
    public function customs(){
        $this->display();
    }
    //ajax获取民享酒店会员列表
    public function getCustomsList(){
        $page=I('get.page',1,'intval');
        $pageSize=I('get.rows',20,'intval');
        $customid  = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//卡号
        $linktel= trim(I('get.linktel',''));//手机号码
        $personid= trim(I('get.personid',''));//身份证号
        if(!empty($customid)){
            $where['cu.customid']=$customid;
        }
        if(!empty($cuname)){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
        }
        if(!empty($linktel)){
            $where['cu.linktel']=$linktel;
        }
        if(!empty($personid)){
            $where['cu.personid']=$personid;
        }
        $where['c.cardkind']='6688';
        $where['c.cardfee']=1;
        $field='distinct cu.customid,cu.linktel,cu.namechinese,cu.personid,p.namechinese pname';
        $model=new Model();
        $total=$model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('panters p on p.panterid=cu.panterid')
            ->where($where)->field('count(distinct cu.customid) c')->find();
        $list=$model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('panters p on p.panterid=cu.panterid')
            ->where($where)->field($field)->page($page,$pageSize)->select();
        session('customCondition',$where);
        //echo count($list);
        $result=array();
        $result['total']=!empty($total['c'])?$total['c']:0 ;
        $result['rows']=!empty($list)?$list:'';
        $this->ajaxReturn($result);
    }

    public function customs_excel(){
        $where=session('customCondition');
        $field='distinct cu.customid,cu.linktel,cu.namechinese,cu.personid,p.namechinese pname';
        $model=new Model();
        $total=$model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('panters p on p.panterid=cu.panterid')
            ->where($where)->field('count(distinct cu.customid) c')->find();
        $list=$model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on cc.cid=c.customid')->join('panters p on p.panterid=cu.panterid')
            ->where($where)->field($field)->select();
        $strlist="<tr><th>会员编号</th><th>会员名称</th><th>手机号</th><th>身份证号</th><th>会员所属机构</th></tr>";
        $strlist=$this->changeCode($strlist);
        foreach($list as $k=>$v){
            $v['namechinese']=$this->changeCode($v['namechinese']);
            $v['pname']=$this->changeCode($v['pname']);
            $strlist.="<tr><td>{$v['customid']}</td><td>{$v['namechinese']}</td>";
            $strlist.="<td>{$v['linktel']}</td><td>{$v['personid']}</td><td>{$v['pname']}</td></tr>";
        }
        $filename='会员报表'.date('YmdHis').'.xls';
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->export_xls($filename,$strlist);
    }
}
