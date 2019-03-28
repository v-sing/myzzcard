<?php
namespace Home\Controller;

use Home\Model\FootballTownModel;
use Think\Model;

class FootballTownController extends CommonController
{
    //查询余额
    public function balance(){
        $cardno = trim(I('get.cardno',''));
        $phone  = trim(I('get.phone',''));
        if($cardno){
            $map['ca.cardno'] = $cardno;
            $this->assign('cardno',$cardno);
        }
        if($phone){
            $map['ac.phone'] = $phone;
            $this->assign('phone',$phone);
        }
        if($map){
            $info = M('cards')->alias('ca')
                ->join('zzk_account ac on ac.accountid=ca.accountid')
                ->where($map)
                ->field('ca.cardno,ac.balance')
                ->find();
        }
        $this->assign('info',$info);
        $this->display();
    }
    //充值报表
    public function charge(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);

        $map['placeddate'] = $date;

        //充值金额
        $map['flag']  = '01';
        $statistics['charge'] = FootballTownModel::sumAmount($map,'price');

        $map['flag']  = '02';
        $statistics['consume'] = FootballTownModel::sumAmount($map,'payamount');

        $map['flag'] = '04';
        $statistics['refund'] = FootballTownModel::sumAmount($map,'payamount');

        $map['flag'] = '03';
        $statistics['return'] = FootballTownModel::sumAmount($map,'price');

        //计算所有卡余额
        $where['type'] = '06';
        $statistics['balance'] = M('zzk_account')->where($where)->field('nvl(sum(balance),0) amount')->find()['amount'];

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
        $map['g.flag']       = ['in',['02','04']];
        $field = 'g.order_sn,g.panterid,g.storeid,s.name,g.goodsname,g.price,g.num,g.cardno,g.cardnum,g.placeddate,g.placedtime,g.flag';
        $field.=',g.discount,g.payamount';
        $list=M('zzk_catering_trade')->alias('g')
            ->join('left join zzk_store s on s.outid=g.storeid')
            ->field($field)
            ->order('g.placeddate asc,g.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";

        $titleName=$date .'卡交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='N4';
        $headerArray = array('日期','时间','店铺名',
            '商户号','店铺号','类型','卡号','卡次',
            '菜品名','单价','交易数量',
            '总价','折扣','支付金额'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N');
        $setWidth = array(12,12,25,12,12,12,25,12,10,8,8,8,8,8);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        $type=['02'=>'消费','04'=>'退菜'];
        if(!$list)exit('无交易数据');
        foreach ($list as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
                $val['payamount'] = -$val['payamount'];
            }
            if($val['num']==='0') $val['num'] = '会员卡';
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,"'".$val['storeid'])
                ->setCellValue('F'.$j,$type[$val['flag']])->setCellValue('G'.$j,"'".$val['cardno'])->setCellValue('H'.$j,$val['num'])
                ->setCellValue('I'.$j,$val['goodsname'])->setCellValue('J'.$j,$val['price'])->setCellValue('K'.$j,$val['num'])
                ->setCellValue('L'.$j,$sumP)->setCellValue('M'.$j,$val['discount'])->setCellValue('N'.$j,$val['payamount']);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('L'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('K'.$j,'合计金额:');
        $objSheet->setCellValue('L'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$date .'卡交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");


    }
    public function tradeDetail(){
        $order_sn = trim(I('get.order_sn',''));
        if($order_sn){
            $bool = M('zzk_catering_trade')->where(['order_sn'=>$order_sn])->find();
            if(! $bool) $this->error('查无此单号');
            if($bool['teamid'] && ($bool['flag']=='01' || $bool['flag']=='03')) $this->error('团卡请传消费单号');

            $map['c.accountid'] = $bool['accountid'];
            $map['c.cardnum']   = $bool['cardnum'];


            $field = 'c.order_sn,c.cardno,c.cardnum,c.placeddate,c.placedtime,c.price,c.num,c.goodsname,s.name,c.flag,c.discount,c.payamount';
            $list = M('zzk_catering_trade')->alias('c')
                    ->join('left join zzk_store s on s.outid=c.storeid')
                    ->where($map)->order('c.tradeid asc')
                    ->field($field)->select();

            $consume['accountid'] = $bool['accountid'];
            $consume['cardnum']   = $bool['cardnum'];
            $consume['flag'] = '01';
            $charge['amount'] = M('zzk_catering_trade')->where($consume)->field('nvl(sum(price),0) amount')->find()['amount'];
            $consume['flag'] = '02';
            $trade['consume'] = M('zzk_catering_trade')->where($consume)->field('nvl(sum(payamount),0) amount')->find()['amount'];

            $consume['flag'] = '04';
            $trade['refund'] = M('zzk_catering_trade')->where($consume)->field('nvl(sum(payamount),0) amount')->find()['amount'];

            $this->assign('charge',$charge);
            $this->assign('trade',$trade);
            $this->assign('list',$list);
        }
        $this->assign('order_sn',$order_sn);
        $this->display();


    }
    /*
     * 档口交易详情
     */
    public function stall(){
        $startdate = trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate = trim(I('get.enddate',''));
        $pname=trim(I('get.pname',''));
        $type=trim(I('get.type',''));
        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate,'pname'=>$pname,'type'=>$type];
        $map['g.placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['g.placeddate']=[['egt',str_replace('-','',$realStart)],['elt',str_replace('-','',$enddate)]];
        }
        if($pname!=''){
            $list=M('zzk_store')->where(['name'=>['like','%'.$pname.'%']])->find();
            $list==true||$this->error('该档口名不存在');
            $map['g.storeid'] = $list['outid'];
            $this->assign('pname',$pname);
        }
        if($type!=''){
            $map['g.flag']=$type;
            $this->assign('atype',$type);
        }else{
            $map['g.flag']=['in',['02','04']];
        }
        session('footballTown_stall_excel',$map);
        $info = FootballTownModel::stall($map,$urlMap);
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
        $map=session('footballTown_stall_excel');
        $field = 'g.order_sn,g.panterid,g.storeid,s.name,g.goodsname,g.price,g.num,g.cardno,g.cardnum,g.placeddate,g.placedtime,g.flag';
        $field.=',g.discount,g.payamount';
        $list=M('zzk_catering_trade')->alias('g')
            ->join('left join zzk_store s on s.outid=g.storeid')
            ->field($field)
            ->order('g.placeddate asc,g.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";

        $titleName='大食堂档口交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='J4';
        $headerArray = array('日期','时间','店铺名',
            '商户号','店铺号','类型','卡号',
            '菜品名','单价','交易数量',
            '总价','折扣','支付金额'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L','M');
        $setWidth = array(12,12,25,12,12,12,25,12,10,8,8,8,8);
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
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,"'".$val['storeid'])
                ->setCellValue('F'.$j,$type[$val['flag']])->setCellValue('G'.$j,"'".$val['cardno'])
                ->setCellValue('H'.$j,$val['goodsname'])->setCellValue('I'.$j,$val['price'])->setCellValue('J'.$j,$val['num'])
                ->setCellValue('K'.$j,$sumP)->setCellValue('L'.$j,$val['discount'])->setCellValue('M'.$j,$val['payamount']);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('K'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('J'.$j,'合计金额:');
        $objSheet->setCellValue('K'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','档口交易明细.xls');//输出到浏览器
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
        session('stallStatistics_map',$map);
        $map['flag'] = '02';
        $consume =  FootballTownModel::groupConsumeRefund($map);
        $map['flag'] = '04';
        $refund  =  FootballTownModel::groupConsumeRefund($map);
        $list=$this->arrayCombine($consume,$refund);
        $this->assign('list',$list);
        $this->assign('startdate',$startdate);

        $this->display();
    }
    public function stallStatisticsExcel(){
        ini_set('memory_limit', '-1');
        $map = session('stallStatistics_map');
        $map['flag'] = '02';
        $consume =  FootballTownModel::groupConsumeRefund($map);
        $map['flag'] = '04';
        $refund  =  FootballTownModel::groupConsumeRefund($map);
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
        $headerArray = array('日期','店铺名',
            '店铺号','消费金额','退菜金额','实际流水',
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
            $objSheet->setCellValue('A'.$j,$title)->setCellValue('B'.$j,$val['name'])->setCellValue('C'.$j,$val['storeid'])
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
                if($v['storeid']==$val['storeid']){
                    $v['refund']=$val['tradeamount'];
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
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);

        $map['placeddate'] = $date;

        $teamlist = FootballTownModel::teamChargeList($map);
        if($teamlist){
            foreach ($teamlist as $key=>$val){
                $where = [];
                $where['teamid'] = $val['teamid'];
                $where['flag']   = '02';
                $val['consume']  = M('zzk_catering_trade')->where($where)->field('nvl(sum(price*num),0) amount')->find()['amount'];
                $where['flag']   = '04';
                $val['refund']  = M('zzk_catering_trade')->where($where)->field('nvl(sum(price*num),0) amount')->find()['amount'];

                //返回团退金额
                $chargeinfo = M('zzk_catering_trade')->where($where)->field('cardno,cardnum')->find();
                $charge = [];
                $charge['cardno'] = $chargeinfo['cardno'];
                $charge['cardnum']= $chargeinfo['cardnum'];
                $charge['flag']   = '03';
                $refund['teamid'] = M('zzk_catering_trade')->where($charge)->field('teamid')->find()['teamid'];

                $val['return']  = M('zzk_catering_trade')->where($refund)->field('nvl(sum(price),0) amount')->find()['amount'];



                $teamlist[$key] = $val;
            }
        }else{
            $teamlist = [];
        }
        $this->assign('startdate',$startdate);
        $this->assign('lists',$teamlist);
        $this->display();

    }
    public function returnExcel(){
        ini_set('memory_limit', '-1');
        $teamid=trim(I('get.teamid',''));
        $map['teamid'] = $teamid;
        $chargeinfo = M('zzk_catering_trade')->where(['teamid'=>$teamid])->field('cardno,cardnum')->find();
        $where['cardno'] = $chargeinfo['cardno'];
        $where['cardnum']= $chargeinfo['cardnum'];
        $where['flag']   = '03';
        $refundTemaid = M('zzk_catering_trade')->where($where)->field('teamid')->find()['teamid'];
        $map['teamid'] = $refundTemaid;
        $map['flag']   = '03';

        $field = 'c.order_sn,c.panterid,c.storeid,s.name,c.price,c.cardno,c.cardnum,c.placeddate,c.placedtime';
        $list=M('zzk_catering_trade')->alias('c')
            ->join('left join zzk_store s on s.outid=c.storeid')
            ->field($field)
            ->order('c.placeddate asc,c.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='团卡明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='L4';
        $headerArray = array('日期','时间','店铺名',
            '商户号','店铺号','类型','卡号','卡次',
            '金额',
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I');
        $setWidth = array(12,12,25,12,12,12,25,5,15);
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
        $this->browser_export('Excel5',$teamid.'_'.'团卡退卡明细.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    public function consumeExcel(){
        ini_set('memory_limit', '-1');
        $teamid=trim(I('get.teamid',''));
        $map['teamid'] = $teamid;
        $map['flag']   = ['in',['02','04']];
        $field = 'c.order_sn,c.panterid,c.storeid,s.name,c.goodsname,c.price,c.num,c.cardno,c.cardnum,c.placeddate,c.placedtime,c.flag';
        $list=M('zzk_catering_trade')->alias('c')
            ->join('left join zzk_store s on s.outid=c.storeid')
            ->field($field)
            ->order('c.placeddate asc,c.placedtime asc')
            ->where($map)
            ->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='团卡明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='L4';
        $headerArray = array('日期','时间','店铺名',
            '商户号','店铺号','类型','卡号','卡次',
            '菜品名','单价','交易数量',
            '总价'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L');
        $setWidth = array(12,12,25,12,12,12,25,5,15,8,5,8);
        $this->setWidth($setCells, $setWidth);
        $tradetype=['02'=>'消费','04'=>'退菜'];
        $type = ['1'=>'正常','2'=>'散装' ];
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['name'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,"'".$val['storeid'])->setCellValue('F'.$j,$tradetype[$val['flag']])
                ->setCellValue('G'.$j,"'".$val['cardno'])->setCellValue('H'.$j,"'".$val['cardnum'])
                ->setCellValue('I'.$j,$val['goodsname'])->setCellValue('J'.$j,$val['price'])->setCellValue('K'.$j,$val['num'])
                ->setCellValue('L'.$j,$sumP);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('L'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('L'.$j,'合计金额:');
        $objSheet->setCellValue('L'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$teamid.'_'.'团卡明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    public function card_query(){
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $map['ca.cardno'] = $cardno;
            $info = M('cards')->alias('ca')->where($map)->field('accountid,status')->find();
            if($info){
                if($info['status']!='Y')   exit( json_encode(['success'=>'4','msg'=>'不是正常卡']));
                $account = M('zzk_account')->where(['accountid'=>$info['accountid']])->field('phone,balance')->find();
                if(!$account) exit(json_encode(['success'=>'5','msg'=>'此卡没有开账户'])) ;
                if($account['phone']=='0') exit(json_encode(['success'=>'5','msg'=>'不是会员卡'])) ;
                echo json_encode(['success'=>'1','msg'=>['cardno'=>$cardno,'phone'=>$account['phone'],'amount'=>$account['balance']]]);
            }else{
                echo json_encode(['success'=>'2','msg'=>'此卡信息错误']);
            }
        }else{
            echo json_encode(['success'=>'3','msg'=>'传输方式错误']);
        }
    }

    public function loss(){
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $amount = trim(I('post.amount',''));
            $map['cardno'] = $cardno;
            $info = M('cards')->alias('ca')->where($map)->field('accountid,status')->find();
            $model = new Model();
            if(! $info) exit(json_encode(['success'=>'2','msg'=>'此卡信息错误']));

            if($info['status']!='Y')   exit(json_encode(['success'=>'4','msg'=>'不是正常卡'])) ;
            $account = M('zzk_account')->where(['accountid'=>$info['accountid']])->field('phone,balance')->find();
            if(!$account) exit(json_encode(['success'=>'5','msg'=>'此卡没有开账户'])) ;
            if($account['phone']=='0') exit(json_encode(['success'=>'5','msg'=>'不是会员卡'])) ;
            $userid = $this->userid;
            $lockid=$this->getnextcode('card_locks',19);
            $currenttime=date('Ymd',time());
            $userid=$this->userid;
            $sql='insert into card_locks(cardno,LockedDate,lockid,updtetime,userid,description,cardno1)';
            $sql.=" values('{$cardno}','{$currenttime}','{$lockid}','{$currenttime}','{$userid}','卡挂失','00')";

            $sql1='insert into card_locks_log(CardNo,LockedDate,lockid,userid,description,active)';
            $sql1.=" values('{$cardno}','{$currenttime}','{$lockid}','{$userid}','卡挂失',0)";
            $save['status']    = 'L';
            $model->startTrans();
            try{
                if($model->table('cards')->where(['cardno'=>$cardno])->save($save)
                    && $model->execute($sql) && $model->execute($sql1)
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
            echo json_encode(['success'=>'3','msg'=>'传输方式错误']);
        }
    }
}