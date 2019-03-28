<?php
namespace Home\Controller;

use Home\Model\FootballTownModel;
use Home\Model\QifengModel;
use Think\Model;
use Think\Page;


class QifengController extends CommonController
{
    protected  $error;
    protected  $msg;

    protected $termno = '00000006';//后台处理统一终端号

    protected $returnPanterid = '00009196';

    protected $parent  = '00000934';
    //查询余额
    public function balance(){
        $tradeType=['01'=>'充值','02'=>'消费','04'=>'退菜','03'=>'退卡'];
        $cardno = trim(I('get.cardno',''));

        $flag     =trim(I('get.type',''));
        $tradeid  =trim(I('get.tradeid',''));
//        if($tradeid=='' || $flag==='')$this->error('交易单号,交易类型不能为空');

        $map['flag'] = $flag;
        $map['tradeid'] = $tradeid;
        $urlMap = ['type'=>$map['flag'],'tradeid'=>$map['tradeid']];
        $cardInfo = QifengModel::getCardInfo($map);
        if($cardInfo){
            $trade['q.cardno'] = $cardInfo['cardno'];
            $trade['q.cardnum']= $cardInfo['cardnum'];
            $tradeInfo = QifengModel::CardTradeInfo($trade);
            unset($cardInfo['numrow']);

            //计算 充值金额
            $cardInfo['flag'] = '01';
            $sum['charge']    = QifengModel::sumAmount($cardInfo,'payamount');

            $cardInfo['flag'] = '02';
            //消费
            $sum['consume']    =  QifengModel::sumAmount($cardInfo,'payamount');

            //退菜
            $cardInfo['flag'] = '04';
            $sum['refund']    =  QifengModel::sumAmount($cardInfo,'payamount');

            //退卡
            $cardInfo['flag'] = '03';
            $sum['return']    =  QifengModel::sumAmount($cardInfo,'payamount');



            $combine['cardno'] = $cardInfo['cardno'];
            $combine['outnum'] = $cardInfo['cardnum'];
            $togther['out']    =  QifengModel::combineCardSum($combine);

            unset($combine['outnum']);
            $combine['innum']  = $cardInfo['cardnum'];
            $togther['in']     = QifengModel::combineCardSum($combine);

            $this->assign('info',$tradeInfo);
            $this->assign('sum',$sum);
            $this->assign('combine',$togther);


        }

        $this->assign('flag',$flag);
        $this->assign('tradeid',$tradeid);
        $this->assign('type',$tradeType);
        $this->display();
    }
    //充值报表
    public function charge(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);

        $map['placeddate'] = $date;

        //充值金额
        $map['flag']  = ['in',['01','05','06']];
        $statistics['charge'] = QifengModel::sumAmount($map,'price');

        $map['flag']  = '02';
        $statistics['consume'] = QifengModel::sumAmount($map,'payamount');

        $map['flag'] = '04';
        $statistics['refund'] = QifengModel::sumAmount($map,'payamount');

        $map['flag'] = '03';
        $statistics['return'] = QifengModel::sumAmount($map,'payamount');

        //结算当日卡余额

//        $balanceWhere=['ca.cardkind'=>'6997','ca.status'=>'Y','cu.cardflag'=>'on','ac.type'=>'00'];
        $balanceWhere=['ca.cardkind'=>'6997','ac.type'=>'00'];
        $statistics['balance'] = QifengModel::balanceInfo($balanceWhere);
//        $where['type'] = '06';
//        $statistics['balance'] = M('zzk_account')->where($where)->field('nvl(sum(balance),0) amount')->find()['amount'];

        $this->assign('statistics',$statistics);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    //每天卡交易报表
    public function cardTrade(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $startdate=trim(I('get.date',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date = str_replace('-','',$startdate);
        $map['g.placeddate'] = $date;
//        $map['g.flag']       = ['in',['01','02','03','04']];
        $field = 'g.tradeid,g.panterid,p.namechinese pname,g.goodsname,g.price,g.num,g.cardno,g.cardnum,g.placeddate,g.placedtime,g.flag';
        $field.=',g.payamount';
        $list=M('qf_order')->alias('g')
            ->join('left join panters p on p.panterid=g.panterid')
            ->field($field)
            ->order('g.placeddate asc,g.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:M3";

        $titleName=$date .'卡交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='M4';
        $headerArray = array('日期','时间','商户名',
            '商户号','类型','卡号','卡次',
            '菜品名','单价','交易数量',
            '总价','支付金额','交易单号'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L');
        $setWidth = array(12,12,25,12,12,25,12,10,8,8,8,8);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        $type=['01'=>'充值','02'=>'消费','03'=>'退卡','04'=>'退菜','05'=>'充值','06'=>'充值'];
        if(!$list)exit('无交易数据');
        foreach ($list as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
                $val['payamount'] = -$val['payamount'];
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['pname'])
                ->setCellValue('D'.$j,"'".$val['panterid'])
                ->setCellValue('E'.$j,$type[$val['flag']])->setCellValue('F'.$j,"'".$val['cardno'])->setCellValue('G'.$j,$val['cardnum'])
                ->setCellValue('H'.$j,$val['goodsname'])->setCellValue('I'.$j,$val['price'])->setCellValue('J'.$j,$val['num'])
                ->setCellValue('K'.$j,$sumP)->setCellValue('L'.$j,$val['payamount'])
                ->setCellValue('M'.$j,$val['tradeid']);;
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('L'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('J'.$j,'合计金额:');
        $objSheet->setCellValue('K'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$date .'卡交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");


    }

    /*
     * 档口交易详情
     */
    public function stall(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate  = trim(I('get.enddate',''));
        $pname    = trim(I('get.pname',''));
        $type=trim(I('get.type',''));
        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate,'pname'=>$pname,'type'=>$type];
        $map['q.placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['q.placeddate']=[['egt',str_replace('-','',$realStart)],['elt',str_replace('-','',$enddate)]];
        }
        if($pname!=''){
            $list=M('panters')->where(['namechinese'=>['like','%'.$pname.'%']])->field('panterid')->find();
            $list==true||$this->error('该档口名不存在');
            $map['q.panterid'] = $list['panterid'];
            $this->assign('pname',$pname);
        }
        if($type!=''){
            $map['q.flag']=$type;
            $this->assign('atype',$type);
        }else{
            $map['q.flag']=['in',['02','04']];
        }
        session('qf_stall_excel',$map);
        $info = QifengModel::stall($map,$urlMap);
        $this->assign('page',$info['show']);
        $this->assign('list',$info['data']);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('type',['02'=>'消费','04'=>'退菜']);
        $this->display();
    }
    public function stallExcel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $type=['02'=>'消费','04'=>'退菜'];
        $map=session('qf_stall_excel');
        $field = 'q.tradeid,q.panterid,p.namechinese pname,q.goodsname,q.price,q.num,q.cardno,q.cardnum,q.placeddate,q.placedtime,q.flag';
        $field.= ',q.payamount';
        $list=M('qf_order')->alias('q')
            ->join('left join panters p on p.panterid=q.panterid')
            ->field($field)
            ->order('q.placeddate asc,q.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:L3";

        $titleName='启封档口交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='L4';
        $headerArray = array('日期','时间','商户名',
            '商户号','类型','卡号','卡次',
            '菜品名','单价','交易数量',
            '总价','支付金额'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K');
        $setWidth = array(12,12,25,12,12,25,8,12,10,8,8,8);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
                $val['payamount'] = -$val['payamount'];
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['pname'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,$type[$val['flag']])
                ->setCellValue('F'.$j,"'".$val['cardno'])->setCellValue('G'.$j,$val['cardnum'])
                ->setCellValue('H'.$j,$val['goodsname'])->setCellValue('I'.$j,$val['price'])
                ->setCellValue('J'.$j,$val['num'])
                ->setCellValue('K'.$j,$sumP)->setCellValue('L'.$j,$val['payamount']);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('K'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('J'.$j,'合计金额:');
        $objSheet->setCellValue('K'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','启封档口交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");

    }
    /*
     * 档口交易合计统计
     */
    public function stallStatistics(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $enddate=trim(I('get.enddate',''));
        $start  = str_replace('-','',$startdate);
        $map['placeddate'] = ['egt',$start];
        if($enddate!=''){
            $end    = str_replace('-','',$enddate);
            $map['placeddate']=[['egt',$start],['elt',$end]];
            $this->assign('enddate',$enddate);
        }
        session('qf_stallStatistics_map',$map);
        $map['flag'] = '02';
        $consume =  QifengModel::groupConsumeRefund($map);
        $map['flag'] = '04';
        $refund  =  QifengModel::groupConsumeRefund($map);
        $list=$this->arrayCombine($consume,$refund);
        $this->assign('list',$list);
        $this->assign('startdate',$startdate);

        $this->display();
    }
    public function stallStatisticsExcel(){
        ini_set('memory_limit', '-1');
        $map = session('qf_stallStatistics_map');
        $map['flag'] = '02';
        $consume =  QifengModel::groupConsumeRefund($map);
        $map['flag'] = '04';
        $refund  =  QifengModel::groupConsumeRefund($map);
        $list=$this->arrayCombine($consume,$refund);
        if(! ($map['placeddate'][1][1])){
            $start = $map['placeddate'][1];
            $end   = '';
        }else{
            $start = $map['placeddate'][0][1];
            $end   = $map['placeddate'][1][1];
        }
        if(! $list) exit('查无数据');
        $title = $start.'---'.$end;
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:G3";
        $titleName=$title.'档口交易合计';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='F4';
        $headerArray = array('日期','商户名',
            '商户号','消费金额','退菜金额','实际流水',
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F');
        $setWidth = array(25,25,12,12,12,12);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            $real = bcsub($val['tradeamount'],$val['refund'],2);
            $objSheet->setCellValue('A'.$j,$title)->setCellValue('B'.$j,$val['pname'])->setCellValue('C'.$j,"'".$val['panterid'])
                ->setCellValue('D'.$j,$val['tradeamount'])->setCellValue('E'.$j,$val['refund'])
                ->setCellValue('F'.$j,$real);
            $j++;
        }
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$title.'_'.'档口交易合计.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    private function arrayCombine($consume,$refund){
        foreach($refund as $key=>$val){
            foreach($consume as $k=> $v){
                if($v['panterid']==$val['panterid']){
                    $v['refund'] = $val['tradeamount'];
                    $v['tradeamount'] = bcadd($v['tradeamount'],0,2);
                    $v['refund'] = bcadd($v['refund'],0,2);
                    $consume[$k]=$v;
                    break;
                }
            }
        }
        return $consume;
    }

    public function team(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate   = trim(I('get.enddate',''));
        $map['placeddate']=['egt',$realStart];

        if($enddate!=''){
            $map['placeddate'] = [
                ['egt',str_replace('-','',$realStart)],
                ['elt',str_replace('-','',$enddate)]
            ];
        }
        $teamlist = QifengModel::getTeamids($map);
        if($teamlist){
            foreach ($teamlist as  $key=>$val){
                $v['teamid'] = $val['teamid'];
                $info  = QifengModel::getConsumeRufundAmount($v);
                $val['consume']= $info[0]['amount'];
                $val['refund'] = $info[1]['amount'];
                $teamlist[$key]= $val;
            }
            $this->assign('teaminfo',$teamlist);
        }
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->display();
    }
    public function returnExcel(){
        ini_set('memory_limit', '-1');
        $teamid=trim(I('get.teamid',''));
        $map['q.teamid'] = $teamid;
        $map['q.flag']   = '03';

        $field = 'q.teamid,q.orderid,q.panterid,p.namechinese name,q.price,q.cardno,q.cardnum,q.placeddate,q.placedtime,q.termno';
        $list=M('qf_order')->alias('q')
            ->join('left join panters p on p.panterid=q.panterid')
            ->field($field)
            ->order('q.placeddate asc,q.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:L3";
        $titleName=$teamid.'团卡退卡明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='L4';
        $headerArray = array('日期','时间','商户名',
            '商户号','终端号','类型','卡号','卡次',
            '金额',
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I');
        $setWidth = array(12,12,25,12,12,12,25,5,12);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])
                ->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])
                ->setCellValue('E'.$j,"'".$val['termno'])->setCellValue('F'.$j,'退卡')
                ->setCellValue('G'.$j,"'".$val['cardno'])->setCellValue('H'.$j,"'".$val['cardnum'])
                ->setCellValue('I'.$j,$val['price']);
            $j++;

        }
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$teamid.'_'.'团卡退卡明细.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    public function consumeExcel(){
        ini_set('memory_limit', '-1');
        $teamid=trim(I('get.teamid',''));
        $map['q.teamid'] = $teamid;
        $map['q.flag']   = ['in',['02','04']];
        $field = 'q.teamid,q.orderid,q.panterid,p.namechinese name,q.price,q.num,q.cardno,q.cardnum,q.placeddate,q.placedtime,q.termno';
        $list=M('qf_order')->alias('q')
            ->join('left join panters p on p.panterid=q.panterid')
            ->field($field)
            ->order('q.placeddate asc,q.placedtime asc')
            ->where($map)
            ->select();
        if(!$field) exit('无消费数据');
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName=$teamid.'团卡消费明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='L4';
        $headerArray = array('日期','时间','商户名',
            '商户号','类型','卡号','卡次',
            '菜品名','单价','交易数量',
            '总价','支付金额'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L');
        $setWidth = array(12,12,25,12,12,25,8,15,8,8,8,8);
        $this->setWidth($setCells, $setWidth);
        $tradetype=['02'=>'消费','04'=>'退菜'];
        $type = ['1'=>'正常','2'=>'散装' ];
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            if($val['flag']=='02'){
                $sumP =  bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP = -bcmul($val['price'],$val['num'],2);
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])
                ->setCellValue('C'.$j,$val['name'])->setCellValue('D'.$j,"'".$val['panterid'])
                ->setCellValue('E'.$j,$tradetype[$val['flag']])->setCellValue('F'.$j,$val['cardno'])
                ->setCellValue('G'.$j,$val['cardnum'])->setCellValue('H'.$j,$val['goodsname'])
                ->setCellValue('I'.$j,$val['price'])->setCellValue('J'.$j,$val['num'])->setCellValue('K'.$j,$sumP)
                ->setCellValue('L'.$j,$val['payamount']);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('K'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('K'.$j,'合计金额:');
        $objSheet->setCellValue('K'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$teamid.'_'.'团卡消费明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //特惠充值报表
    public function gift(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate   = trim(I('get.enddate',''));
        $map['q.placeddate']=['egt',$realStart];

        $sum['placeddate'] = ['egt',$realStart];
        if($enddate!=''){
            $map['q.placeddate'] = [
                ['egt',$realStart],
                ['elt',str_replace('-','',$enddate)]
            ];
            $sum['placeddate'] = [
                ['egt',$realStart],
                ['elt',str_replace('-','',$enddate)]
            ];
        }
        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate];
        $map['q.flag'] = '05';
        session('qf_gift',$map);
        $sum['flag']  = '05';
        $info = QifengModel::gift($map,$urlMap);
        $gift = QifengModel::sum($sum);
        $this->assign('page',$info['show']);
        $this->assign('list',$info['data']);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('gift',$gift);
        $this->display();
    }
    public function giftCardExcel(){
        set_time_limit(0);
        ini_set('memory_limit','-1');
        $cardno =  trim(I('get.cardno',''));
        if($cardno=='') exit('缺失卡号');
        $map['q.cardno'] = $cardno;
        $field = 'q.teamid,q.orderid,q.panterid,q.flag,p.namechinese name,q.price,q.num,q.cardno,q.cardnum,q.placeddate,q.placedtime,q.termno,q.payamount,q.paytype';
        $list = M('qf_order')->alias('q')
            ->join('left join panters p on p.panterid = q.panterid')
            ->where($map)->order('q.placeddate asc,q.placedtime asc')
            ->field($field)
            ->select();
        if(!$list) exit('暂无数据');
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName=$cardno.'特惠详情';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='J4';
        $headerArray = array('日期','时间','商户名','商户号',
            '终端号','卡号','交易类型','交易金额','支付金额',
            '支付类型'

        );
        $type = ['01'=>'充值','02'=>'消费','04'=>'退菜','05'=>'充值'];
        $paymenttype =  [
            '00'=>'现金','01'=>'银行卡'
        ];
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I',J);
        $setWidth = array(12,12,25,12,20,5,12,12,12,12);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,$val['termno'])->setCellValue('F'.$j,"'".$val['cardno'])
                ->setCellValue('G'.$j,$type[$val['flag']])->setCellValue('H'.$j,bcmul($val['price'],$val['num'],1))
                ->setCellValue('I'.$j,$val['payamount'])->setCellValue('J'.$j,$paymenttype[$val['paytype']]);
            $j++;

        }
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$cardno.'特惠详情.xls');//输出到浏览器
        $objWriter->save("php://output");

    }
    public function giftExcel(){
        set_time_limit(0);
        ini_set('memory_limit','-1');
        $map  = session('qf_gift');
        $list = M('qf_order')->alias('q')
                            ->join('left join panters p on p.panterid = q.panterid')
                            ->where($map)->order('q.placeddate desc,q.placedtime desc')
                            ->field('q.*,p.namechinese pname')
                           ->select();
        if(!$list) exit('暂无数据');
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='特惠充值详情';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='I4';
        $headerArray = array('日期','时间','商户名',
            '终端号','卡号','卡次','充值金额','支付金额',
            '支付类型'

        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I');
        $setWidth = array(12,12,25,12,20,5,12,12,12);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,"'".$val['storeid'])->setCellValue('F'.$j,'退卡')
                ->setCellValue('G'.$j,"'".$val['cardno'])->setCellValue('H'.$j,"'".$val['cardnum'])
                ->setCellValue('I'.$j,$val['price']);
            $j++;

        }
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','特惠充值详情.xls');//输出到浏览器
        $objWriter->save("php://output");

    }
    public function staffCharge(){
        set_time_limit(0);
        $panterid = $this->panterid;
        $userid = $this->userid;
        $model = new Model();
        if (IS_POST) {
            if (empty($_FILES['excel']['name'])) {
                $this->error('请上传卡号文件', U('Qifeng/staffCharge'), 5);
            }
            $upload = $this->_upload('excel');
            if (is_string($upload)) {
                $this->error($upload, U('Card/batchRecharge'), 5);
            } else {
                $filename = PUBLIC_PATH . $upload['excel']['savepath'] . $upload['excel']['savename'];
                $exceldata = $this->import_excel($filename);
                foreach ($exceldata as $v){
                    $data[$v['3']] = $v;
                }
                $cards = M('cards');
                $data=$this->inputCustomInfo($data);
                //
                $panterid =  '00008654';
                $flag     = '06';
                $paytype  = '00';
                $orderSql = "";
                $customplSql  = "";
                $auditlogsSql = "";
                $cardplSql    = "";
                $logSql       = "";
                $paymenttype  = '现金';
                $accountSql   = '';
                $placeddate   = date('Ymd');
                $placedtime   = date('H:i:s');



                foreach ($data as $key => $val) {
                    $purchaseid=$this->getFieldNextNumber("purchaseid");
                    $auditid=$this->getFieldNextNumber('auditid');
                    $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
                    $orderid = $this->getFieldPrimaryNumber('orderid',16);
                    $cardno  = $val[3];
                    $amount  = $val[4];
                    $customid= $val['customid'];
                    $month   = $val[5];

                    $customplSql.=" into custom_purchase_logs values('".$customid."','{$purchaseid}','".$placeddate."','{$paymenttype}','";
                    $customplSql.=$userid."','".$amount."',NULL,'".$amount."',0,'".$amount."','".$amount;
                    $customplSql.="',1,'','','1','".$panterid."','".$userid."',NULL,'1',";
                    $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$placeddate."','".date('H:i:s',time()+300)."',NULL)";

                    $auditlogsSql.=" into audit_logs values ('{$auditid}','".$purchaseid."','审核通过',";
                    $auditlogsSql.="'大食堂充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

                    $cardplSql.=" INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
                    $cardplSql.="'{$cardno}','{$amount}','0','".$placeddate."','".date('H:i:s',time()+300)."','1','后台充值',";
                    $cardplSql.="'{$userid}','{$panterid}','','00000000')";

                    $orderSql.=" INTO qf_order (orderid,tradeid,cardno,cardnum,price,num,flag,panterid,teamid,payamount,paytype,teamid,customid,termno,placeddate,placedtime) VALUES ('{$orderid}','{$purchaseid}',";
                    $orderSql.="'{$cardno}','0','{$amount}','1','{$flag}','{$panterid}','','{$amount}','{$paytype}','','{$customid}','00000006','{$placeddate}','{$placedtime}')";

                    $logSql  .= " into qf_staff_logs (orderid,cardno,month,userid,amount,placeddate,placedtime) values ('{$orderid}',";
                    $logSql  .= "'{$amount}','{$month}','{$userid}','{$amount}','{$placeddate}','{$placedtime}')";

                    $accountSql.=" WHEN '{$val['customid']}' THEN '{$val[4]}' ";

                }
                $head = "insert all ";
                $end  = " SELECT 1 FROM DUAL";
                $customplSql  = $head.$customplSql.$end;
                $auditlogsSql = $head.$auditlogsSql.$end;
                $cardplSql    = $head.$cardplSql.$end;
                $orderSql     = $head.$orderSql.$end;
                $logSql       = $head.$logSql.$end;
                $customString = $this->inString(array_column($data,'customid'));
                $accountSql   = "update account set amount= amount + case customid " .$accountSql. "END where customid in ({$customString}) and type='00'";

                $model->startTrans();
                try{
                    if($model->execute($customplSql) && $model->execute($auditlogsSql)
                        && $model->execute($cardplSql) && $model->execute($orderSql)
                        && $model->execute($logSql)  && $model->execute($accountSql)
                    ){
                        $model->commit();
                        $this->success('充值成功');

                    }
                }catch (\Exception $e){
                    $this->error .=$e->getMessage();
                    $model->rollback();
                    dump('充值失败');
                    dump($this->error);
                }
            }
        }
        $this->display();
    }
    /*
  * 子查询的时 数组转为 in 查询时所需的字符串
  * @param array $arr 一维数组信息
  * @return sting
  */
    protected function inString($arr){
        $str='';
        foreach($arr as $val){
            $str.="'".$val."',";
        }
        return rtrim($str,',');
    }
    //启封录入会员信息
    protected function inputCustomInfo($arr){
        $model = new Model();
        $cards = array_column($arr,3);
        $map['cardno']  = ['in',$cards];
        $map['cardfee'] = '3';
        $bool = M('cards')->where($map)->field('cardno,customid,status')->select();
        $res = $model->table('cards')->where(['cardno'=>['in',$cards]])->field('cardno,customid,status')->select();
        foreach ($res as $rk=>$rv){
            $arr[$rv['cardno']]['customid'] = $rv['customid'];
        }
        foreach ($res as $k=>$v){
            $relate[$v['cardno']] = $v;
        }
        if($bool){
            if(count($cards)==count($bool)){
                return $arr;
            }else{
                $diff = array_diff($cards,array_column($bool,'cardno'));

                foreach ($diff as $bv){
                    if($relate[$bv]['status']=='Y'){
                        $data = $arr[$bv];
                        $save['namechinese'] = $data[1];
                        $save['linktel']     = $data[2];
                        $save['num']         = '0';
                        $save['cardflag']        = 'on';
                        $if = $model->table('customs')->where(['customid'=>$relate[$bv]['customid']])->find();
                        if($if){
                            if(!is_null($if['cardflag'])){
                                exit($bv.'已经使用不能当员工卡');
                            }
                        }else{
                            exit($bv.'未开户');
                        }
                        $model->startTrans();
                        if($model->table('customs')->where(['customid'=>$relate[$bv]['customid']])->save($save)
                          && $model->table('cards')->where(['cardno'=>$bv])->save(['cardfee'=>'3'])
                        ){
                            $model->commit();
                        }else{
                            $model->rollback();
                            exit($bv.'开户失败');
                        }
                    }else{
                        exit($bv.'不是正常卡');
                    }
                }
                return $arr;
            }
        }else{
            foreach ($res as $bv){
                if($bv['status']=='Y'){
                    $data = $arr[$bv['cardno']];
                    $save['namechinese'] = $data[1];
                    $save['linktel']     = $data[2];
                    $save['num']         = '0';
                    $save['cardflag']        = 'on';
                    $if = $model->table('customs')->where(['customid'=>$bv['customid']])->find();
                    if($if){
                        if(!is_null($if['cardflag'])){
                            exit($bv['cardno'].'已经使用不能当员工卡');
                        }
                    }else{
                        exit($bv['cardno'].'未开户');
                    }
                    $model->startTrans();
                    if($model->table('customs')->where(['customid'=>$bv['customid']])->save($save)
                        && $model->table('cards')->where(['cardno'=>$bv['cardno']])->save(['cardfee'=>'3'])
                    ){
                       $model->commit();
                    }else{
                        $model->rollback();
                        exit($bv['cardno'].'开户失败');
                    }
                }else{
                    exit('开卡中:'.$bv['cardno']."---".'此卡不是正常卡');
                }
            }
            return $arr;

        }
    }

    protected function getFieldPrimaryNumber($field, $length){
        $model = new Model();
        $seq_field='seq_qf_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $list=$model->query($sql);
        return  str_pad($list[0]['nextval'],$length,'0',STR_PAD_LEFT);
    }

    //启封冲退报表统计
    public function chargeReturn(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate  = trim(I('get.enddate',''));
        $map['placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['placeddate']=[['egt',$realStart],['elt',str_replace('-','',$enddate)]];
        }

        //特惠
        $map['flag'] = '05';
        $gift = QifengModel::sum($map);

        //员工
        $map['flag'] = '06';
        $staff = QifengModel::sum($map);

        $map['flag'] = '03';
        $return = QifengModel::sumAmount($map,'payamount');

        //普通充值
        $map['flag']   = '01';
        $map['_string']= 'teamid is null';
        $normal = QifengModel::sum($map);
        U();

        //团卡
        $map['flag']   = '01';
        $map['_string']= 'teamid is not null';
        $team = QifengModel::sum($map);


        $this->assign('normal',$normal);
        $this->assign('staff',$staff);
        $this->assign('gift',$gift);
        $this->assign('team',$team);
        $this->assign('return',$return);

        $this->assign('map',['startdate'=>$startdate,'enddate'=>$enddate]);
        $this->display();
    }
    public function chargeRerunExcel(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate   = trim(I('get.enddate',''));
        $flag      = trim(I('get.flag',''));
        $map['g.placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['g.placeddate']=[['egt',str_replace('-','',$realStart)],['elt',str_replace('-','',$enddate)]];
        }
        $map['g.flag'] = ['in',['01','03','05','06']];


        $field = 'g.panterid,p.namechinese pname,g.price,g.num,g.cardno,g.cardnum,g.placeddate,g.placedtime,g.flag';
        $field.=',g.payamount,g.teamid,g.paytype,g.termno';
        $list=M('qf_order')->alias('g')
            ->join('left join panters p on p.panterid=g.panterid')
            ->field($field)
            ->order('g.placeddate asc,g.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:M3";

        $titleName=$startdate.'----'.$enddate .'卡交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='M4';
        $headerArray = array('日期','时间','商户名',
            '商户号','终端号','卡号','卡次','卡类型',
            '交易类型', '金额','实收金额','支付方式'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L');
        $setWidth = array(12,12,25,12,12,25,10,10,8,8,8,8);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        $paytype=['00'=>'现金','01'=>'银行卡'];
        $cardtype = ['05'=>'特惠卡','06'=>'员工卡'];
        if(!$list)exit('无交易数据');
        foreach ($list as $key => $val){
            if($val['flag']=='03'){
                $val['payamount'] = -$val['payamount'];
                $tradetype= '退卡';
            }else{
                $val['payamount'] = $val['payamount'];
                $tradetype= '充值';
            }
            if($val['flag']=='01'){
                if(is_null($val['teamid'])){
                    $type='普通卡';
                }else{
                    $type='团队卡';
                }
            }elseif($val['flag']=='03'){
                if(is_null($val['teamid'])){
                    $type='普通卡';
                }else{
                    $type='团队卡';
                }
            }else{
                $type = $cardtype[$val['flag']];
            }

            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['pname'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,$val['termno'])
                ->setCellValue('F'.$j,"'".$val['cardno'])->setCellValue('G'.$j,$val['cardnum'])
                ->setCellValue('H'.$j,$type)->setCellValue('I'.$j,$tradetype)
                ->setCellValue('J'.$j,$val['price'])
                ->setCellValue('K'.$j,$val['payamount'])
                ->setCellValue('L'.$j,$paytype[$val['paytype']]);;
            $j++;
        }
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$startdate.'----'.$enddate .'卡冲退明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //员工卡查询
    public function staff(){
        $cardno      = trim(I('get.cardno',''));
        $namechinese = trim(I('get.namechinese',''));
        $linktel     = trim(I('get.linktel',''));

        if($cardno){
            $map['q.cardno'] = $cardno;
            $this->assign('cardno',$cardno);
        }
        if($namechinese){
            $map['cu.namechinese'] = $namechinese;
            $this->assign('namechinese',$namechinese);
        }
        if($linktel){
            $map['cu.linktel']     = $linktel;
            $this->assign('linktel',$linktel);
        }
        $field = 'q.cardno,q.placeddate,q.placedtime,p.namechinese name,q.goodsname,q.price,q.num,q.payamount,q.flag';
        if($map){
            $map['ca.cardkind'] = '6997';
            $count = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('left join qf_order q on ca.customid=q.customid')
                ->join('left join panters p on p.panterid=q.panterid')
                ->field($field)->where($map)->count();
            $page = new Page($count,10);
            $list = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('left join qf_order q on ca.customid=q.customid')
                ->join('left join panters p on p.panterid=q.panterid')
                ->where($map)->field($field)->order('q.placeddate desc,q.placedtime desc')
                ->limit($page->firstRow,$page->listRows)->select();
            $this->assign('list',$list);
            $this->assign('page',$page->show());
        }
        $this->display();
    }

    //员工卡充值详情
    public function staffDetail(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate  = trim(I('get.enddate',''));
        $map['s.placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['s.placeddate']=[['egt',$realStart],['elt',str_replace('-','',$enddate)]];
        }

        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate];

        $count  = M('qf_staff_logs')->alias('s')
                                    ->join('left join qf_order q on s.orderid=q.orderid')
                                    ->join('left join customs cu on cu.customid=q.customid')
                                    ->where($map)->count();
        $page = new Page($count,10,$urlMap);
        $field = 'cu.namechinese,cu.linktel,q.price amount,q.placeddate,q.placedtime,s.month,q.cardno';
        $list = M('qf_staff_logs')->alias('s')
                     ->join('left join qf_order q on s.orderid=q.orderid')
                     ->join('left join customs cu on cu.customid=q.customid')
                     ->field($field)->where($map)->limit($page->firstRow,$page->listRows)
                     ->select();
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('page',$page->show());
        $this->assign('list',$list);
        $this->display();
    }
    //启封日余额相关统计

    public function daliyBalance(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate  = trim(I('get.enddate',''));
        $map['placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['placeddate']=[['egt',$realStart],['elt',str_replace('-','',$enddate)]];
        }

        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate];

        $count  = M('qf_daily_detail')->where($map)->count();
        $page = new Page($count,10,$urlMap);
        $list =  M('qf_daily_detail')->where($map)
                                    ->limit($page->firstRow,$page->listRows)
                                    ->order('placeddate desc')
                                    ->select();
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('list',$list);
        $this->assign('page',$page->show());
        $this->display();
    }

    public function staffOff(){
        $cardno = trim(I('get.cardno',''));
        $cuname = trim(I('get.cuname',''));
        $linktel= trim(I('get.linktel',''));


        if($cardno || $cuname || $linktel){
            $map['ca.cardfee'] = '3';
            $map['ac.type']    = '00';
            if($cardno)  $map['ca.cardno'] = $cardno;
            if($cuname)  $map['cu.namechinese'] = $cuname;
            if($linktel) $map['cu.linktel'] = $linktel;
            $map['ca.cardkind'] = '6997';

            $field = 'cu.linktel,cu.namechinese cuname,ca.cardno,ac.amount,ca.status';
            $info = M('cards')->alias('ca')
                              ->join('customs cu on cu.customid=ca.customid')
                              ->join('account ac on ac.customid=ca.customid')
                              ->field($field)->where($map)->select();

            $this->assign('info',$info);
            $this->assign('cardno',$cardno);
            $this->assign('cuname',$cuname);
            $this->assign('linktel',$linktel);
        }
        $this->display();
    }

    public function card_query(){
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $map['ca.cardfee'] = '3';
            $map['ca.cardno'] = $cardno;
            $field = 'cu.linktel,cu.namechinese cuname,ca.cardno,ac.amount,ca.status';
            $info = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('account ac on ac.customid=ca.customid')
                ->field($field)->where($map)->find();
            if($info){
                echo json_encode(['success'=>'1','data'=>$info]);
            }else{
                echo json_encode(['success'=>'2','msg'=>'此卡信息错误']);
            }
        }else{
            echo json_encode(['success'=>'3','msg'=>'传输方式错误']);
        }
    }

    public function off(){
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $amount = trim(I('post.amount',''));
            $map['ca.cardfee'] = '3';
            $map['ca.cardno'] = $cardno;
            $map['ca.cardkind'] = '6997';
            $field = 'cu.linktel,cu.namechinese cuname,ca.cardno,ac.amount,ca.status,cu.customid';
            $model = new Model();
            $info = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('account ac on ac.customid=ca.customid')
                ->field($field)->where($map)->find();
            if($info){
               if($info['amount']!=$amount){
                   echo json_encode(['success'=>'6','msg'=>'金额不对']);
                   exit;
               }
                $termno = $this->termno;
                $tradeid = $this->termno.date('YmdHis');
                $placeddate = date('Ymd');
                $placedtime = date('H:i:s');
                $panterid   = $this->returnPanterid;
                $paytype    = '00';
                $customid   = $info['customid'];
                $linktel    = $info['linktel'];
                $tac='abcdefgh';
                $orderid = $this->getFieldPrimaryNumber('orderid',16);
                $offid     = $this->getFieldPrimaryNumber('offid',8);
                //trade
                $trade  ="INSERT  INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $trade  .="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$amount}','0','{$customid}','{$cardno}','{$placedtime}','30','{$tac}','0')";
                // qf_order 表
                $orderSql="INSERT  INTO qf_order(orderid,tradeid,cardno,cardnum,flag,panterid,price,num,placeddate,placedtime,customid,teamid,paytype,payamount,termno) VALUES ('{$orderid}','{$tradeid}',";
                $orderSql.="'{$cardno}','0','03','{$panterid}','{$amount}','1','{$placeddate}','{$placedtime}','{$customid}',";
                $orderSql.="'','{$paytype}','{$amount}','{$termno}')";


                //qf_staff_off
                $off = "insert into qf_staff_off(offid,name,customid,cardno,linktel,placeddate,placedtime,amount) VALUES (";
                $off.= "'{$offid}','{$info['cuname']}','{$customid}','{$cardno}','{$linktel}','{$placeddate}','{$placedtime}','{$amount}')";


                $cid     =  $this->getFieldBatchNumber(1,'customid')[0];
                $accountids =  $this->getFieldBatchNumber(3,'accountid');

                $accSql = " into account(accountid,amount,type,customid) values ('{$accountids[0]}','0','00','{$cid}') ";
                $accSql .= " into account(accountid,amount,type,customid) values ('{$accountids[1]}','0','01','{$cid}') ";
                $accSql .= " into account(accountid,amount,type,customid) values ('{$accountids[2]}','0','04','{$cid}') ";

                $ccSql  = "insert into customs_c values('{$cid}','{$cid}') ";
                $cuSql  = "insert into customs (customid,namechinese,placeddate,panterid) values ('{$cid}','$启封故园{$cid}','{$placeddate}','{$this->parent}') ";

                $accSql = "insert all " .$accSql. "select 1 from dual";

                $save['cardfee'] = '1';
                $save['customid']= $cid;
                $model->startTrans();
                try{
                    if($model->table('cards')->where(['cardno'=>$cardno])->save($save) && $model->execute($trade)
                        && $model->execute($orderSql) && $model->execute($off)  && $model->execute($accSql)
                        && $model->execute($ccSql) && $model->execute($cuSql)
                    ){
                        $model->commit();
                        echo json_encode(['success'=>'1','msg'=>'ok']);
                    }
                }catch (\Exception $e){
                    $model->rollback();
                   dump($e->getTraceAsString());
                   dump($e->getMessage());
                }
            }else{
                echo json_encode(['success'=>'2','msg'=>'此卡信息错误']);
            }
        }else{
            echo json_encode(['success'=>'3','msg'=>'传输方式错误']);
        }
    }
    //批量获取表 字段 序列值
    public function getFieldBatchNumber($num, $field){
        $model = new Model();
        $sql    = "SELECT seq_{$field}.nextval FROM (SELECT 1 FROM all_objects where rownum <= $num)";
        $batch  = $model->query($sql);
        $return = [];
        foreach ($batch as $bv){
            $return[] = str_pad($bv['nextval'],8,'0',0);
        }
        return $return;
    }




}