<?php
namespace  Home\Controller;
use Home\Model\KfModel;
use Think\Controller;
use Think\Model;
class KfFinanceController extends CommonController
{
    private $kfParent;
    private $chongPanterid;
    private $tradeType;
    public function _initialize(){
        parent::_initialize();
        $this->tradeType=['01'=>'充值','02'=>'消费','04'=>'退菜','03'=>'退卡','05'=>'特惠充值'];
        $this->kfParent = '00000915';
        $this->chongPanterid ='00001294';
        $this->type=['02'=>'消费','04'=>'退菜'];
        $this->cache = S(array('type'=>'file','prefix'=>'kf','expire'=>300));
        $this->model = new  Model();
    }
    public function daliy(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $map = ['cup.placeddate'=>$date,'cup.flag'=>'1'];
        $map['kf.flag'] = '01';
        $normal = KfModel::ChargeSum($map);
        $map['kf.flag'] = '05';
        $gift  = KfModel::ChargeSum($map);
        //结算交易 金额
        //消费金额
        $where=['tw.tradetype'=>'00','tw.flag'=>'0','p.parent'=>$this->kfParent,'tw.placeddate'=>$date];
        $consume= KfModel::tradeSum($where);
        //退卡交易
        $where['tw.tradetype'] = '30';
        $refund = KfModel::tradeSum($where);
        //退菜金额
        $where['tw.tradetype'] = '31';
        $back  = KfModel::tradeSum($where);
        //卡余额总和
        $balanceWhere=['ca.cardkind'=>'6880','ca.cardno'=>['like','68803788%'],'ca.status'=>'Y','cu.cardflag'=>'on','ac.type'=>'00'];
        $balance = KfModel::balanceInfo($balanceWhere);
        $lists=['normal'=>$normal,'gift'=>$gift,'consume'=>$consume,'refund'=>$refund,'back'=>$back,'balance'=>$balance,'placeddate'=>$date];
        $this->assign('lists',$lists);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    //特惠充值详情
    public function gift(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $map = ['cup.placeddate'=>$date,'cup.flag'=>'1','kf.flag'=>'05'];
        $field = 'cup.amount,cup.realamount,cup.placeddate,cup.placedtime,kf.cardno';
        $list = KfModel::giftDetail($map,$field);
        $gift  = KfModel::ChargeSum($map);
        $this->assign('lists',$list['data']);
        $this->assign('show',$list['show']);
        $this->assign('gift',$gift);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    /*
     *  大食堂 交易明细查询
     */
    public function tradeDetail(){
        $map['flag']     =trim(I('get.type',''));
        $map['tradeid'] =trim(I('get.tradeid',''));
        //分页上面 传送查询条件
        $urlMap = ['type'=>$map['flag'],'tradeid'=>$map['tradeid']];
        //消费
        $cardInfo = KfModel::orderCardInfo($map);
        if($cardInfo){
            //查询充值 退菜详情
            $cardInfo['flag'] = ['in',['02','04']];
            $info  = KfModel::orderTradeInfo($cardInfo,$urlMap);
            $this->assign('list',$info['data']);
            $this->assign('show',$info['show']);
            //充值 总额
            $chargeWhere['kf.cardno'] = $cardInfo['cardno'];
            $chargeWhere['kf.cardnum']= $cardInfo['cardnum'];
                if($cardInfo['cardnum']==='0'){
                    $chargeWhere['kf.flag']   = '05';
                    $chargeInfo  = KfModel::chargeSum($chargeWhere);
                }else{
                    $chargeWhere['kf.flag']   = '01';
                    $chargeInfo  = KfModel::chargeSum($chargeWhere);
                }
            $this->assign('chargeInfo',$chargeInfo);
            //消费 合计
            $con['cardno'] = $cardInfo['cardno'];
            $con['cardnum']= $cardInfo['cardnum'];
            $con['flag']   = '02';
            $consume = KfModel::orderConsumeSum($con);
            //退菜
            $con['flag']   = '04';
            $refund = KfModel::orderConsumeSum($con);
            //退卡
            $trade['kf.flag']   = '03';
            $trade['kf.cardno'] = $cardInfo['cardno'];
            $trade['kf.cardnum']= $cardInfo['cardnum'];
            $back = KfModel::tradeCardSum($trade);
            $this->assign('trade',['consume'=>$consume,'refund'=>$refund,'back'=>$back]);
            //合卡 统计 有出入金额
            $combine['cardno'] = $cardInfo['cardno'];
            $combine['outnum'] = $cardInfo['cardnum'];

            $out = KfModel::combineCardSum($combine);
            unset($combine['outnum']);
            $combine['innum']  = $cardInfo['cardnum'];
            $in  = KfModel::combineCardSum($combine);
            $this->assign('combine',['in'=>$in['amount'],'out'=>$out['amount']]);
        }
        $this->assign('type',$this->tradeType);
        $this->assign('flag',$map['flag']);
        $this->assign('tradeid',$map['tradeid']);
        $this->display();
    }
    /*
     * 各个档口交易明显
     */
    public function every(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $enddate=trim(I('get.enddate',''));
        $pname=trim(I('get.pname',''));
        $type=trim(I('get.type',''));
        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate,
                   'pname'=>$pname        ,'type'=>$type
                  ];
        $map['g.placeddate']=['egt',str_replace('-','',$startdate)];
        if($enddate!=''){
            $map['g.placeddate']=[['egt',str_replace('-','',$startdate)],['elt',str_replace('-','',$enddate)]];
        }
        if($pname!=''){
            $list=M('panters')->where(['namechinese'=>$pname])->find();
            $list==true||$this->error('该档口名不存在');
            $map['g.panterid']=$list['panterid'];
            $this->assign('pname',$pname);
        }
        if($type!=''){
            $map['g.flag']=$type;
            $this->assign('atype',$type);
        }else{
            $map['g.flag']=['in',['02','04']];
        }
        session('kfcarnteenexcel',$map);
        //返回各个档口交易详情
        $info = KfModel::everyPanterOrderInfo($map,$urlMap);
        $panterlists=KfModel::getKfPanters($this->kfParent,$this->chongPanterid);
        $this->assign('page',$info['show']);
        $this->assign('list',$info['data']);
        $this->assign('type',$this->type);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('panterlists',$panterlists);
        $this->display();
    }
    //导出各个档口交易明细
    public function excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $type=$this->type;
        $map=session('kfcarnteenexcel');
        $greens=M('kf_order')->alias('g')->
        join('left join panters p on p.panterid=g.panterid')->
        where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='开封九道弯档口交易明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='J4';
        $headerArray = array('日期','时间','商户名',
            '商户号','类型','卡号',
            '菜品名','单价','交易数量',
            '总价'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J');
        $setWidth = array(12,12,25,12,12,25,25,8,10,8);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($greens as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['pname'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,$type[$val['flag']])->setCellValue('F'.$j,"'".$val['cardno'])
                ->setCellValue('G'.$j,$val['goodsname'])->setCellValue('H'.$j,$val['price'])->setCellValue('I'.$j,$val['num'])
                ->setCellValue('J'.$j,$sumP);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('J'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('I'.$j,'合计金额:');
        $objSheet->setCellValue('J'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','开封九道弯档口交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //各个档口交易合计
    public function everySum(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $enddate=trim(I('get.enddate',''));
        $enddate==true||$enddate=date('Y-m-d');
        $start  = str_replace('-','',$startdate);
        $end    = str_replace('-','',$enddate);
        $panterlists = KfModel::getKfPanterids($this->kfParent);
        $key=array_search($this->chongPanterid,$panterlists);
        unset($panterlists[$key]);
        $str='';
        foreach($panterlists as $val){
            $str.="'".$val."'".",";
        }
        $panterstr=rtrim($str,",");
        $cosume  =  KfModel::groupConsumeRefund($panterstr,'00',$start,$end);
        $refund  =  KfModel::groupConsumeRefund($panterstr,'31',$start,$end);
        $list=$this->arrayCombine($cosume,$refund);
        $date=['start'=>$start,'end'=>$end];
        $this->cache->everysum=$list;
        $this->cache->date    =$date ;
        $this->assign('lists',$list);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('datetime',$date);
        $this->display();
    }
    /*
    * 退菜金额 和消费金额 合并
    */
    private function arrayCombine($consume,$refund){
        foreach($refund as $key=>$val){
            foreach($consume as $k=> $v){
                if($v['panterid']==$val['panterid']){
                    $v['refund']=$val['tradeamount'];
                    $consume[$k]=$v;
                    break;
                }
            }
        }
        return $consume;
    }
    //各个档口交易统计
    public function everySumExcel(){
        $list = $this->cache->everysum;
        $date = $this->cache->date;
        $str  = $date['start'].'---'.$date['end'];
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:F3";
        $titleName='开封九道弯各个档口交易统计';
        $this->setTitle($cellMerge,$titleName);
        $startCell = 'A4';
        $endCell ='F4';
        $headerArray = array('日期','商户名','商户号',
            '消费金额','退菜金额','实际流水'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F');
        $setWidth = array(25,25,12,12,12,12);
        $this->setWidth($setCells, $setWidth);
        $j=5;
        $sum=0;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$str)->setCellValue('B'.$j,$val['namechinese'])->setCellValue('C'.$j,"'".$val['panterid'])
                ->setCellValue('D'.$j,$val['tradeamount'])->setCellValue('E'.$j,$val['refund'])
                ->setCellValue('F'.$j,bcsub($val['tradeamount'],$val['refund'],2));
            $j++;
        }
        $objSheet->getStyle('J'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','开封九道弯各个档口交易统计.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //团冲报表
    public function team(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $teamlist = KfModel::teamlist($date);
        if($teamlist==true){
            $list = array_column($teamlist,'teamid');
            foreach($list as $val){
                $res[]=$this->everyTeam($val);
            }
        }
        $this->assign('startdate',$startdate);
        $this->assign('res',$res);
        $this->display();
    }
    private function everyTeam($teamid){
        $purlist = array_column($this->model->table('kf_team')->where(['teamid'=>$teamid])->select(),'purchaseid');
        $map['purchaseid'] = ['in',$purlist];
        //充值
        $chong   = $this->model->table('card_purchase_logs')
                        ->where($map)->field('nvl(sum(amount),0)  amount')->select();
        //消费 ,退菜
        $consum=0;
        $refsum=0;
        $cardlist = $this->model->table('kf_order')->where(['tradeid'=>['in',$purlist]])
                                ->field('cardno,cardnum')->select();
        foreach($cardlist as $k=>$v){
            $consumlist=$this->model->table('kf_order')
                ->where(['cardno'=>$v['cardno'],'cardnum'=>$v['cardnum'],'flag'=>'02'])
                ->field('nvl(sum(price*num),0)  amount')->select();
            $refunlist=$this->model->table('kf_order')
                ->where(['cardno'=>$v['cardno'],'cardnum'=>$v['cardnum'],'flag'=>'04'])
                ->field('nvl(sum(price*num),0) as amount')->select();
            $consum=bcadd($consum,$consumlist[0]['amount'],2);
            $refsum=bcadd($refsum,$refunlist[0]['amount'],2);
        }
        //退卡金额合计
        $querySql = "SELECT teamid FROM kf_team where purchaseid=(select tradeid from kf_order where cardno='{$cardlist[0]['cardno']}' and cardnum='{$cardlist[0]['cardnum']}' and flag='03')";
        $teamtu   = $this->model->query($querySql);
        if($teamtu){
            $return = $this->model->table('kf_team')->alias('kf')
                            ->join("left join trade_wastebooks tw on tw.tradeid=kf.purchaseid")
                            ->where(['kf.teamid'=>$teamtu[0]['teamid']])
                            ->field('sum(tradeamount) tradeamount')->select();
        }
        $returnsum = $return[0]['tradeamount']?:'尚未退卡';
        $rate      = $this->model->table('kf_team_rate')->where(['teamid'=>$teamid])->find()['rate'];
        return ['teamid'=>$teamid,'charge'=>$chong[0]['amount'],'consume'=>$consum,'refund'=>$refsum,'return'=>$returnsum,'rate'=>$rate];
    }
    public function consumeecel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $teamid=trim(I('get.teamid',''));
        $slq="(SElECT purchaseid from kf_team WHERE teamid='{$teamid}')";
        $list=$this->model->table($slq)->alias('pu')
            ->join('left join card_purchase_logs ca on ca.purchaseid=pu.purchaseid')
            ->field('ca.purchaseid,ca.cardno')->select();
        $res=[];
        foreach($list as $v){
            //单卡消费
            $cardinfo=$this->model->table('kf_order')
                ->where(['tradeid'=>$v['purchaseid'],'cardno'=>$v['cardno']])
                ->field('cardno,cardnum')->find();
            $datalist=$this->model->table('kf_order')->alias('go')
                ->join('left join panters p on p.panterid=go.panterid')
                ->where(['go.cardno'=>$cardinfo['cardno'],'go.cardnum'=>$cardinfo['cardnum'],'go.flag'=>['in',['02','04']]])
                ->field('p.namechinese as pname,go.goodsname,go.price,go.num,go.placeddate,go.placedtime,go.flag,go.cardno,go.panterid')
                ->select();
            if($datalist==true){
                foreach($datalist as $val){
                    $res[]=$val;
                }

            }
        }
        $res=$this->panterArrDsc($res);
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='开封九道弯团卡明细';
        $this->setTitle($cellMerge,$titleName);
        //setheader
        $startCell = 'A4';
        $endCell ='J4';
        $headerArray = array('日期','时间','商户名',
            '商户号','类型','卡号',
            '菜品名','单价','交易数量',
            '总价'
        );
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J');
        $setWidth = array(12,12,25,12,12,25,25,8,10,8);
        $this->setWidth($setCells, $setWidth);
        $type=$this->type;
        $j=5;
        $sum=0;
        foreach ($res as $key => $val){
            if($val['flag']=='02'){
                $sumP=bcmul($val['price'],$val['num'],2);
            }elseif($val['flag']=='04'){
                $sumP=-bcmul($val['price'],$val['num'],2);
            }
            $objSheet->setCellValue('A'.$j,$val['placeddate'])->setCellValue('B'.$j,$val['placedtime'])->setCellValue('C'.$j,$val['pname'])
                ->setCellValue('D'.$j,"'".$val['panterid'])->setCellValue('E'.$j,$type[$val['flag']])->setCellValue('F'.$j,"'".$val['cardno'])
                ->setCellValue('G'.$j,$val['goodsname'])->setCellValue('H'.$j,$val['price'])->setCellValue('I'.$j,$val['num'])
                ->setCellValue('J'.$j,$sumP);
            $j++;
            $sum=bcadd($sum,$sumP,2);
        }
        $objSheet->getStyle('J'.$j)->applyFromArray(array('font'=>array('bold'=>true)));
        $objSheet->setCellValue('I'.$j,'合计金额:');
        $objSheet->setCellValue('J'.$j,$sum);
        ob_end_clean();
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$teamid.'_开封九道弯团卡明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //对基于卡号的 数组重新排列
    public function panterArrDsc($arr){
        $panter = array_column($arr,'panterid');
        array_multisort($panter,SORT_ASC,$arr);
        return $arr;
    }
}
