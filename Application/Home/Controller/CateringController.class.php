<?php
namespace  Home\Controller;
use Home\Model\CateringModel;
use Home\Model\KfModel;
use Think\Controller;
use Think\Model;
class CateringController extends CommonController
{
    private $kfParent;//机构商户id
    private $chongPanterid;
    private $tradeType;
    public function _initialize(){
       parent::_initialize();
        $this->tradeType=['01'=>'充值','02'=>'消费','04'=>'退菜','03'=>'退卡','05'=>'特惠充值'];
        $this->kfParent = $this->panterid;
        $this->chongPanterid = [
            '00000922'=>'00003093','00000923'=>'00003094','00000924'=>'00003095'
        ];
        $this->prefix = [
            '00000922'=>'sh_','00000923'=>'hb_','00000924'=>'py_'
        ];
        $this->type=['02'=>'消费','04'=>'退菜'];
        $this->excelName = ['00000922'=>'神垕','00000923'=>'鹤壁','00000924'=>'濮阳'];
        $this->cache = S(array('type'=>'file','prefix'=>'catering','expire'=>300));
        $this->model = new  Model();
    }
    public function daliy(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000922';
            }
            $this->assign('zzk','1');
            $this->assign('panterlist',$this->excelName);
        }else{
            $parent = $this->kfParent;
        }
        $prefix = $this->prefix[$parent];
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $map = ['cup.placeddate'=>$date,'cup.flag'=>'1'];
        $map['_string'] = 'kf.flag=01 or kf.flag=06';
        $normal = CateringModel::ChargeSum($prefix,$map);
        //结算交易 金额
        //消费金额
        $where=['tw.tradetype'=>'00','tw.flag'=>'0','p.parent'=>$parent,'tw.placeddate'=>$date];
        $consume= KfModel::tradeSum($where);
        //退卡交易
        $where['tw.tradetype'] = '30';
        $refund = CateringModel::tradeSum($where);
        //退菜金额
        $where['tw.tradetype'] = '31';
        $back  = CateringModel::tradeSum($where);
        //卡余额总和
        $balanceWhere=['ca.cardkind'=>'6880','ca.panterid'=>$parent,'ca.status'=>'Y','cu.cardflag'=>'on','ac.type'=>'00'];
        $balance = CateringModel::balanceInfo($balanceWhere);

        $lists=['normal'=>$normal,'consume'=>$consume,'refund'=>$refund,'back'=>$back,'balance'=>$balance,'placeddate'=>$date];
        $this->assign('lists',$lists);
        $this->assign('startdate',$startdate);
        $this->display();

    }
    //各个档口交易合计
    public function everySum(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000922';
            }
            session('catering_everySum_parent',$parent);
            $this->assign('zzk','1');
            $this->assign('panterlist',$this->excelName);
        }else{
            $parent = $this->kfParent;
        }
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $enddate=trim(I('get.enddate',''));
        $enddate==true||$enddate=date('Y-m-d');
        $start  = str_replace('-','',$startdate);
        $end    = str_replace('-','',$enddate);
        $panterlists = CateringModel::getKfPanterids($parent);
        $key=array_search($this->chongPanterid[$parent],$panterlists);
        unset($panterlists[$key]);
        $str='';
        foreach($panterlists as $val){
            $str.="'".$val."'".",";
        }
        $panterstr=rtrim($str,",");
        $cosume  =  CateringModel::groupConsumeRefund($panterstr,'00',$start,$end);
        $refund  =  CateringModel::groupConsumeRefund($panterstr,'31',$start,$end);
        $list=$this->arrayCombine($cosume,$refund);

        ini_set('precision',14);
        foreach ($list as $lk=>$lv){
            $lv['tradeamount'] = round( $lv['tradeamount'],2);
            $lv['refund'] = round( $lv['refund'] ,2);
            $list[$lk] = $lv;
        }
        $date=['start'=>$start,'end'=>$end];
        $prefix   = $this->prefix[$parent];
        $everySum = $prefix.'everySum';
        $cachedate     = $prefix.'date';
        $this->cache->$everySum =$list;
        $this->cache->$cachedate=$date ;
        $this->assign('lists',$list);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('datetime',$date);
        $this->display();
    }
    //各个档口交易明细
    public function every(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000922';
            }
            session('catering_excel_parent',$parent);
            $this->assign('zzk','1');
            $this->assign('panterlist',$this->excelName);
        }else{
            $parent = $this->kfParent;
        }
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $realStart = str_replace('-','',$startdate);
        $enddate=trim(I('get.enddate',''));
        $pname=trim(I('get.pname',''));
        $type=trim(I('get.type',''));
        $urlMap = ['startdate'=>$startdate,'enddate'=>$enddate,
            'pname'=>$pname        ,'type'=>$type
        ];
        $prefix = $this->prefix[$parent];
        $map['g.placeddate']=['egt',$realStart];
        if($enddate!=''){
            $map['g.placeddate']=[['egt',str_replace('-','',$realStart)],['elt',str_replace('-','',$enddate)]];
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
        $map['p.parent'] = $parent;
        session($prefix.'cateringexcel',$map);
        //返回各个档口交易详情
        $info = CateringModel::everyPanterOrderInfo($prefix,$map,$urlMap);
        $panterlists=CateringModel::getKfPanters($parent,$this->chongPanterid[$parent]);
        $this->assign('page',$info['show']);
        $this->assign('list',$info['data']);
        $this->assign('type',$this->type);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('panterlists',$panterlists);
        $this->display();
    }
    //团冲报表
    public function team(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000922';
            }
            session('catering_team_parent',$parent);
            $this->assign('zzk','1');
            $this->assign('panterlist',$this->excelName);
        }else{
            $parent = $this->kfParent;
        }
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $prefix = $this->prefix[$parent];
        $teamlist = CateringModel::teamlist($prefix,$date);
        if($teamlist==true){
            $list = array_column($teamlist,'teamid');
            foreach($list as $val){
                $res[]=$this->everyTeam($val,$parent);
            }
        }
        $this->assign('startdate',$startdate);
        $this->assign('res',$res);
        $this->display();
    }
    // 大食堂 卡 交易明细查询
    public function tradeDetail(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000922';
            }
            $this->assign('zzk','1');
            $this->assign('panterlist',$this->excelName);
        }else{
            $parent = $this->kfParent;
        }
        $map['flag']     =trim(I('get.type',''));
        $map['tradeid']  =trim(I('get.tradeid',''));
        $prefix      =   $this->prefix[$parent];
        //分页上面 传送查询条件
        $urlMap = ['type'=>$map['flag'],'tradeid'=>$map['tradeid'],'zzkparent'=>$zzkparent];
        //消费
        $cardInfo = CateringModel::orderCardInfo($map,$prefix);
        if($cardInfo){
            //查询充值 退菜详情
            $cardInfo['flag'] = ['in',['02','04']];
            $info  = CateringModel::orderTradeInfo($cardInfo,$urlMap,$prefix);
            $this->assign('list',$info['data']);
            $this->assign('show',$info['show']);
            //充值 总额
            $chargeWhere['kf.cardno'] = $cardInfo['cardno'];
            $chargeWhere['kf.cardnum']= $cardInfo['cardnum'];

            $chargeWhere['kf.flag']   = ['in',['01','05']];
            $chargeInfo  = CateringModel::chargeSum($prefix,$chargeWhere);
            $this->assign('chargeInfo',$chargeInfo);
            //消费 合计
            $con['cardno'] = $cardInfo['cardno'];
            $con['cardnum']= $cardInfo['cardnum'];
            $con['flag']   = '02';
            $consume = CateringModel::orderConsumeSum($con,$prefix);
            //退菜
            $con['flag']   = '04';
            $refund = CateringModel::orderConsumeSum($con,$prefix);
            //退卡
            $trade['kf.flag']   = '03';
            $trade['kf.cardno'] = $cardInfo['cardno'];
            $trade['kf.cardnum']= $cardInfo['cardnum'];
            $back = CateringModel::tradeCardSum($trade,$prefix);
            $this->assign('trade',['consume'=>$consume,'refund'=>$refund,'back'=>$back]);
            //合卡 统计 有出入金额
            $combine['cardno'] = $cardInfo['cardno'];
            $combine['outnum'] = $cardInfo['cardnum'];

            $out = CateringModel::combineCardSum($combine,$prefix);
            unset($combine['outnum']);
            $combine['innum']  = $cardInfo['cardnum'];
            $in  = CateringModel::combineCardSum($combine,$prefix);
            $this->assign('combine',['in'=>$in['amount'],'out'=>$out['amount']]);
        }
        $this->assign('type',$this->tradeType);
        $this->assign('flag',$map['flag']);
        $this->assign('tradeid',$map['tradeid']);
        $this->display();
    }
    //导出各个档口交易明细

    //------------------------------------------------------------------------------------------excel---------------------------
    public function excel(){
        if($this->kfParent=='FFFFFFFF'){
            $parent = session('catering_excel_parent');
        }else{
            $parent = $this->kfParent;
        }
        $prefix = $this->prefix[$parent];
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $type=$this->type;
        $map=session($prefix.'cateringexcel');
        $greens=M($prefix.'order')->alias('g')->
        join('left join panters p on p.panterid=g.panterid')->
        where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $flagName =$this->excelName[$parent];
        $titleName=$flagName.'大食堂档口交易明细';
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
        $this->browser_export('Excel5',$flagName.'档口交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");
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
        if($this->kfParent=='FFFFFFFF'){
            $parent = session('catering_everySum_parent');
        }else{
            $parent = $this->kfParent;
        }
        $prefix   = $this->prefix[$parent];
        $everySum = $prefix.'everySum';
        $cahcedate     = $prefix.'date';
        $list = $this->cache->$everySum;
        $date = $this->cache->$cahcedate;
        $str  = $date['start'].'---'.$date['end'];
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:F3";
        $flagName =$this->excelName[$parent];
        $titleName=$flagName.'各个档口交易统计';
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
        $this->browser_export('Excel5',$flagName.'各个档口交易统计.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    public function consumeecel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if($this->kfParent=='FFFFFFFF'){
            $parent = session('catering_team_parent');
        }else{
            $parent = $this->kfParent;
        }
        $prefix = $this->prefix[$parent];
        $teamid=trim(I('get.teamid',''));
        $slq="(SElECT purchaseid from {$prefix}team WHERE teamid='{$teamid}')";
        $list=$this->model->table($slq)->alias('pu')
            ->join('left join card_purchase_logs ca on ca.purchaseid=pu.purchaseid')
            ->field('ca.purchaseid,ca.cardno')->select();
        $res=[];
        foreach($list as $v){
            //单卡消费
            $cardinfo=$this->model->table($prefix.'order')
                ->where(['tradeid'=>$v['purchaseid'],'cardno'=>$v['cardno']])
                ->field('cardno,cardnum')->find();
            $datalist=$this->model->table($prefix.'order')->alias('go')
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
        $flagName =$this->excelName[$parent];
        $titleName=$flagName.'团卡明细';
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
        $this->browser_export('Excel5',$teamid.'_'.$flagName.'团卡明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //对基于卡号的 数组重新排列
    public function panterArrDsc($arr){
        $panter = array_column($arr,'panterid');
        array_multisort($panter,SORT_ASC,$arr);
        return $arr;
    }
    //-----------------------------------------------------------------------------------------
    private function everyTeam($teamid,$parent){
        $prefix = $this->prefix[$parent];
        $purlist = array_column($this->model->table($prefix.'team')->where(['teamid'=>$teamid])->select(),'purchaseid');
        $map['purchaseid'] = ['in',$purlist];
        //充值
        $chong   = $this->model->table('card_purchase_logs')
            ->where($map)->field('nvl(sum(amount),0)  amount')->select();
        //消费 ,退菜
        $consum=0;
        $refsum=0;
        $cardlist = $this->model->table($prefix.'order')->where(['tradeid'=>['in',$purlist]])
            ->field('cardno,cardnum')->select();
        foreach($cardlist as $k=>$v){
            $consumlist=$this->model->table($prefix.'order')
                ->where(['cardno'=>$v['cardno'],'cardnum'=>$v['cardnum'],'flag'=>'02'])
                ->field('nvl(sum(price*num),0)  amount')->select();
            $refunlist=$this->model->table($prefix.'order')
                ->where(['cardno'=>$v['cardno'],'cardnum'=>$v['cardnum'],'flag'=>'04'])
                ->field('nvl(sum(price*num),0) as amount')->select();
            $consum=bcadd($consum,$consumlist[0]['amount'],2);
            $refsum=bcadd($refsum,$refunlist[0]['amount'],2);
        }
        //退卡金额合计
        $querySql = "SELECT teamid FROM {$prefix}team where purchaseid=(select tradeid from kf_order where cardno='{$cardlist[0]['cardno']}' and cardnum='{$cardlist[0]['cardnum']}' and flag='03')";
        $teamtu   = $this->model->query($querySql);
        if($teamtu){
            $return = $this->model->table($prefix.'team')->alias('kf')
                ->join("left join trade_wastebooks tw on tw.tradeid=kf.purchaseid")
                ->where(['kf.teamid'=>$teamtu[0]['teamid']])
                ->field('sum(tradeamount) tradeamount')->select();
        }
        $returnsum = $return[0]['tradeamount']?:'尚未退卡';
        return ['teamid'=>$teamid,'charge'=>$chong[0]['amount'],'consume'=>$consum,'refund'=>$refsum,'return'=>$returnsum];
    }
    //特惠充值模块统计
    public function chargeTotal(){
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000924';
            }
            $this->assign('zzk','1');
            $pantelist  = $this->excelName;
            //目前只有濮阳有特惠所以重写
            $pantelist  = ['00000924'=>'濮阳','00000923'=>'鹤壁','00000922'=>'神垕'];
            $this->assign('panterlist',$pantelist);
        }else{
            $parent = $this->kfParent;
        }
        $prefix = $this->prefix[$parent];
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $map = ['cup.placeddate'=>$date,'cup.flag'=>'1'];
        $map['kf.flag'] = '01';
        $normal = CateringModel::ChargeSum($prefix,$map);

        //特惠充值金额
        $map['kf.flag'] = '05';
        $gift  = CateringModel::ChargeSum($prefix,$map);
        //结算交易 金额
        //消费金额
        $where=['tw.tradetype'=>'00','tw.flag'=>'0','p.parent'=>$parent,'tw.placeddate'=>$date];
        $consume= KfModel::tradeSum($where);
        //退卡交易
        $where['tw.tradetype'] = '30';
        $refund = CateringModel::tradeSum($where);
        //退菜金额
        $where['tw.tradetype'] = '31';
        $back  = CateringModel::tradeSum($where);
        //卡余额总和
        $balanceWhere=['ca.cardkind'=>'6880','ca.panterid'=>$parent,'ca.status'=>'Y','cu.cardflag'=>'on','ac.type'=>'00'];
        $balance = CateringModel::balanceInfo($balanceWhere);

        $lists=['normal'=>$normal,'consume'=>$consume,'gift'=>$gift,'refund'=>$refund,'back'=>$back,'balance'=>$balance,'placeddate'=>$date];
        $this->assign('lists',$lists);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    //特惠充值详情
    public function giftInfo(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        if($this->kfParent=='FFFFFFFF'){
            $zzkparent = trim(I('get.zzkparent',''));
            if($zzkparent!=''){
                $this->assign('searchparent',$zzkparent);
                $parent = $zzkparent;
            }else{
                $parent = '00000924';
            }
            $this->assign('zzk','1');
            $pantelist  = $this->excelName;
            //目前只有濮阳有特惠所以重写
            $pantelist  = ['00000924'=>'濮阳','00000923'=>'鹤壁','00000922'=>'神垕'];
            $this->assign('panterlist',$pantelist);
        }else{
            $parent = $this->kfParent;
        }
        $prefix = $this->prefix[$parent];
        $map    = ['cup.placeddate'=>$date,'cup.flag'=>'1','kf.flag'=>'05'];
        $field  = 'cup.amount,cup.realamount,cup.placeddate,cup.placedtime,kf.cardno';
        $gift   = CateringModel::ChargeSum($prefix,$map);
        $detail = CateringModel::giftDetail($prefix,$map,$field);
        $this->assign('lists',$detail);
        $this->assign('gift',$gift);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    //濮阳团队充值详情
	public function teamChargeInfo(){
		$startdate= trim(I('get.startdate',''));
		$enddate  = trim(I('get.enddate',''));
		$startdate==true||$startdate=date('Y-m-d');
		$begin    = str_replace('-','',$startdate);
		if($this->kfParent=='FFFFFFFF' || $this->kfParent=='00000924'){
			if($enddate!=''){
				$end  = str_replace('-','',$enddate);
				$map['cup.placeddate'] =[['egt',$begin],['elt',$end]];
				$this->assign('enddate',$enddate);
			}else{
				$map['cup.placeddate'] = ['egt',$begin];
			}
			$map['cup.flag'] = '1';
			$map['kf.flag']  = '06';
			$prefix = 'py_';
			$field  = 'cup.amount,cup.realamount,cup.placeddate,cup.placedtime,kf.cardno';
			$gift   = CateringModel::ChargeSum($prefix,$map);
			$detail = CateringModel::giftDetail($prefix,$map,$field);
			$this->assign('lists',$detail);
			$this->assign('gift',$gift);
			$this->assign('startdate',$startdate);
			$this->display();
		}
	}

	//濮阳团队卡 消费详情
	public function teamCardConsume(){
		$startdate= trim(I('get.startdate',''));
		$startdate==true||$startdate=date('Y-m-d');
		$map['placeddate'] = str_replace('-','',$startdate);
		$map['flag']       = '06';
		if($this->kfParent=='FFFFFFFF' || $this->kfParent=='00000924'){

			$mainSql = M('py_order')->where($map)->field('cardno,cardnum')->buildSql();
			$model   = new Model();
			$where['py.flag'] = ['in',['02','04']];
			$field = 'py.cardno,py.cardnum,py.goodsname,py.price,py.num,py.placeddate,py.placedtime,py.flag,p.namechinese';
			$lists   = $model->table($mainSql. " main")
				             ->join('left join py_order py on main.cardno=py.cardno and main.cardnum=py.cardnum')
				             ->join('left join panters p on p.panterid=py.panterid')
				             ->where($where)->field($field)->order('placeddate desc,placedtime desc')->select();
			$where['py.flag'] = '02';
			$consume  = $model->table($mainSql. " main")
							->join('left join py_order py on main.cardno=py.cardno and main.cardnum=py.cardnum')
							->where($where)->field('nvl(sum(price*num),0) consume')->find();
			$where['py.flag'] = '04';
			$refund  = $model->table($mainSql. " main")
			                 ->join('left join py_order py on main.cardno=py.cardno and main.cardnum=py.cardnum')
			                 ->where($where)->field('nvl(sum(price*num),0) refund')->find();

			$this->assign('lists',$lists);
			$this->assign('consume',$consume);
			$this->assign('refund',$refund);
			$this->assign('real',bcsub($consume['consume'],$refund['refund'],2));
			$this->assign('type',['02'=>'消费','04'=>'退菜']);
			$this->assign('startdate',$startdate);
			$this->display();
		}
	}

	public function  giftCardQuery(){
        $cardno = trim(I('get.cardno',''));
        $linktel= trim(I('get.linktel',''));


        if($cardno   || $linktel){
            if($cardno)  {
                $map['ca.cardno'] = $cardno;
                $cardkind = substr($cardno,0,7);
                if($cardkind!=='6880392'){
                    $this->error('不是鹤壁大食堂卡');
                }
            }
            if($linktel) $map['cu.linktel'] = $linktel;
            if($linktel && empty($cardno))  $map['ca.cardno'] = ['like','6880392%'];
            $map['cu.num'] = '0';
            $map['cu.cardflag'] = 'on';
            $map['ac.type']     = '00';


            $field = 'cu.linktel,cu.namechinese cuname,ca.cardno,ac.amount,ca.status';
            $info = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('account ac on ac.customid=ca.customid')
                ->field($field)->where($map)->select();
            $this->assign('info',$info);
            $this->assign('cardno',$cardno);
            $this->assign('linktel',$linktel);
        }
        $this->display();
    }

    public function card_query(){
        if(IS_POST){
            $cardno = trim(I('post.cardno',''));
            $map['ca.cardno'] = $cardno;
            if(empty($cardno))  {
                echo json_encode(['success'=>'4','msg'=>'缺失卡号']);exit;
            }
            $cardkind = substr($cardno,0,7);
            if($cardkind!=='6880392'){
                echo json_encode(['success'=>'4','msg'=>'不是鹤壁大食堂卡']);exit;
            }
            $map['cu.num'] = '0';
            $map['cu.cardflag'] = 'on';
            $map['ac.type'] = '00';
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

    public function consume(){
        if(IS_POST){
            $cardno =  trim(I('post.cardno',''));
            $consume = trim(I('post.consume',''));

            if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $consume)) {
                returnMsg(array('success' => '4', 'msg' => '消费金额格式有误'));
            }
            $cardkind = substr($cardno,0,7);

            if($cardkind!=='6880392'){
                echo json_encode(['success'=>'4','msg'=>'不是鹤壁大食堂卡']);exit;
            }
            $map['cu.num'] = '0';
            $map['cu.cardflag'] = 'on';
            $map['ca.status']    = 'Y';
            $map['ac.type'] = '00';
            $map['ca.cardno']  = $cardno;
            $field = 'cu.linktel,ca.cardno,ac.amount,ca.status,cu.customid';
            $model = new Model();
            $info = M('cards')->alias('ca')
                ->join('customs cu on cu.customid=ca.customid')
                ->join('account ac on ac.customid=ca.customid')
                ->field($field)->where($map)->find();
            if($info){
                if($info['amount']<$consume){
                    echo json_encode(['success'=>'6','msg'=>'消费金额大于余额']);
                    exit;
                }

                $goods = $this->getGoods(['panterid'=>'00003564','goodsname'=>'后台扣款']);
                $termno = '00000001';
                $tradeid = $this->termno.date('YmdHis');
                $placeddate = date('Ymd');
                $placedtime = date('H:i:s');
                $panterid   = '00003564';
                $paytype    = '00';
                $customid   = $info['customid'];
                $linktel    = $info['linktel'];
                $tac='abcdefgh';
                //trade
                $trade  ="INSERT  INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
                $trade  .="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$consume}','0','{$customid}','{$cardno}','{$placedtime}','00','{$tac}','0')";
                // qf_order 表
                $orderSql="INSERT INTO hb_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','0','{$goods['goodsid']}','2','后台扣款','{$consume}','1','02','{$placeddate}','{$placedtime}','{$panterid}')";


                $accountSql = "update account set amount = amount - {$consume} where customid = '{$info['customid']}' and type='00' ";
                $model->startTrans();
                try{
                    if( $model->execute($orderSql) && $model->execute($trade)
                        && $model->execute($accountSql)
                    ){
                        $model->commit();
                        echo json_encode(['success'=>'1','msg'=>'ok']);exit;
                    }
                }catch (\Exception $e){
                    echo json_encode(['success'=>'6','msg'=>$e->getMessage()]);exit;
                }
            }else{
                echo json_encode(['success'=>'2','msg'=>'此卡信息错误']);
            }
        }else{
            echo json_encode(['success'=>'3','msg'=>'传输方式错误']);
        }
    }

    //新增菜品 到菜品表
    private function getGoods($map){
        $model = new Model();
        $result = M('hb_goods')->where($map)->find();
        if($result){
            return $result;
        }else{
            $this->addType($map['panterid'],2);
            $goodslist=M('hb_goods')->where(['panterid'=>$map['panterid']])->select();
            if($goodslist==false){
                $goodsid=1;
            }else{
                $goodsid=$this->getMax(array_column($goodslist,'goodsid'));
                $goodsid+=1;
            }
            $sql="INSERT INTO hb_goods (goodsid,panterid,type,goodsname,price,status) VALUES ('{$goodsid}','{$map['panterid']}','2','{$map['goodsname']}','','1')";

            $model->execute($sql);
            return ['goodsid'=>$goodsid,'gooodsname'=>$map['goodsname']];
        }

    }
    //新增菜品时  添加菜品对应的列别
    private function addType($map,$flag){
        $model = new Model();
        $typelist=M('hb_type')->where(['panterid'=>$map['panterid'],'type'=>$flag])->find();
        if($typelist==false){
            $sql="INSERT INTO hb_type (panterid,type,name,status) VALUES ('{$map['panterid']}','{$flag}','{$map['typename']}','1')";
            return $model->execute($sql);
        }else{
            return true;
        }
    }
    private function getMax($arr){
        rsort($arr);
        return $arr[0];
    }

}
