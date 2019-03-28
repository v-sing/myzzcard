<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class GreenController extends CommonController
{   private $greenParent;
    private $chongPanterid;
    private $tradeType;
    public function _initialize(){
        $this->greenParent='00000483';
        $this->chongPanterid='00000509';
        $this->tradeType=['charge'=>'充值','consume'=>'消费','refund'=>'退菜','return'=>'退卡'];
        //档口报表交易类型
        $this->type=['02'=>'消费','04'=>'退菜'];
        $this->model= new Model();
        parent::_initialize();
    }
    public function daliy(){
        //
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $map=['p1.panterid'=>$this->greenParent,'cap.placeddate'=>$date,'cap.flag'=>'1'];
        $chongField="cap.amount,p1.namechinese as pname";
        $chonglist=M('card_purchase_logs')->alias('cap')
                                          ->join("left join panters p on p.panterid=cap.panterid")
                                          ->join("left join panters p1 on p1.panterid=p.parent")
                                          ->where($map)->field($chongField)->select();
        $chong=$this->countAll($chonglist,'amount');
        //消费金额
        $consumeWhere=['tw.tradetype'=>'00','tw.flag'=>'0','p1.panterid'=>$this->greenParent,'tw.placeddate'=>$date];
        $consumeField="tw.tradeamount,p1.namechinese as pname";
        $consume=$this->getCount($consumeWhere,$consumeField,'tradeamount');
        //退卡金额
        $refundWhere=['tw.tradetype'=>'30','tw.flag'=>'0','p1.panterid'=>$this->greenParent,'tw.placeddate'=>$date];
        $refund=$this->getCount($refundWhere,$consumeField,'tradeamount');
        //退菜金额
        $backWhere=['tw.tradetype'=>'31','tw.flag'=>'0','p1.panterid'=>$this->greenParent,'tw.placeddate'=>$date];
        $back=$this->getCount($backWhere,$consumeField,'tradeamount');
        //卡余额总和
        $balanceWhere=['ca.cardkind'=>'6880','ca.status'=>'Y','cu.cardflag'=>'on','ac.type'=>'00','ca.cardno'=>['like',['6880371%','6880374%'],'OR']];
        $balancelist=M('cards')->alias('ca')
                               ->join('left join customs cu on cu.customid=ca.customid')
                               ->join('left join customs_c cc on cc.cid=ca.customid')
                               ->join('left join account ac on ac.customid=ca.customid')
                               ->where($balanceWhere)->field('ca.cardno,ac.amount')->select();
        $balance=$this->countAll($balancelist,'amount');
        $lists=['chong'=>$chong,'consume'=>$consume,'refund'=>$refund,'back'=>$back,'balance'=>$balance,'placeddate'=>$date];
        $this->assign('lists',$lists);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    public function every(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        $panterlists=$this->parent($this->greenParent);
        $key=array_search($this->chongPanterid,$panterlists);
        unset($panterlists[$key]);
        $res=$this->groupConsume($panterlists,'00',$date);
        $refund=$this->groupConsume($panterlists,'31',$date);
        $res=$this->arrayCombine($res,$refund);
        $this->assign('lists',$res);
        $this->assign('startdate',$startdate);
        $this->display();
    }
    /*
     *  大食堂 交易明细查询
     */
    public function tradeDetail(){
        $type=trim(I('get.type',''));
        $tradeid=trim(I('get.tradeid',''));
        //消费
        $res=$this->searchMethod($type,$tradeid);
        //充值
        $charge=$this->getCharge($type,$tradeid);
        $this->assign('type',$this->tradeType);
        $this->assign('get_type',$type);
        $this->assign('tradeid',$tradeid);
        $this->assign('charge',$charge);
        $this->assign('res',$res);
        $this->display();
    }
    /*大食堂 财务导出各个档口交易
     *
     *
     */
    public function getExcel(){
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-01');
        $enddate=trim(I('get.enddate',''));
        $pname=trim(I('get.pname',''));
        $type=trim(I('get.type',''));
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
        session('greenexcel',$map);
        $panterlists=$this->getGreen();
        $count = M('green_order')->alias('g')
                 ->join('left join panters p on p.panterid=g.panterid')->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10);
        $greens=M('green_order')->alias('g')->
                 join('left join panters p on p.panterid=g.panterid')->
                 where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')->limit($page->firstRow.','.$page->listRows)->select();
        $shows = $page->show();
        $this->assign('page',$shows);
        $this->assign('count',$count);
        $this->assign('greens',$greens);
        $this->assign('type',$this->type);
        $this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('panterlists',$panterlists);
        $this->display();
    }
    public function excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $type=$this->type;
        $map=session('greenexcel');
        $greens=M('green_order')->alias('g')->
        join('left join panters p on p.panterid=g.panterid')->
        where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='大食堂档口交易明细';
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
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','大食堂档口交易明细.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
   /*
    * var 查询大食堂 各个档口 商户号 以及商户名
    *
    */
    private function getGreen(){
        $parent=$this->greenParent;
        //收银端去除
        $panterid=$this->chongPanterid;
        $pantelists=M('panters')->where(['parent'=>$parent])->field('panterid,namechinese')->select();
        foreach($pantelists as $key=>$val){
            if(array_search($panterid,$val)){
                unset($pantelists[$key]);
                break;
            }
        }
        return $pantelists;
    }
  //---------------------------------------------------------------------------------------------------------------------------------------
    /*查询大食堂 消费 退卡 退菜金额
     * var
     */
   private function getTw($where,$field){
       return $consumelist=M('trade_wastebooks')->alias('tw')
                                         ->join("left join panters p on p.panterid=tw.panterid")
                                         ->join("left join panters p1 on p1.panterid=p.parent")
                                         ->where($where)->field($field)->select();


   }
   private function countAll($data,$field){
     $sum=0;
     if($data==true){
        $res=array_column($data,$field);
         foreach($res as $key=>$val){
             $sum=bcadd($sum,$val,2);
         }
     }
      return $sum;
   }
   private function getCount($where,$field,$getField){
       $data=$this->getTw($where,$field);
       return $this->countAll($data,$getField);
   }
   /*
    * 查询大食堂下面所属商户
    *
    */
    private function parent($panterid){
        $map['parent']=$panterid;
        $list=M('panters')->where($map)->field('panterid')->select();
        return array_column($list,'panterid');
    }
    /*分组查询商户 消费 ，退菜金额
     * var
     */
    private function groupConsume($panterlists,$type,$date){
        $str='';
        foreach($panterlists as $val){
            $str.="'".$val."'".",";
        }
        $panterlists=rtrim($str,",");
        $map=['panterid'=>$panterlists,'flag'=>'0','tradetype'=>$type,'placeddate'=>$date];
        $sql="SELECT sum(tw.tradeamount) tradeamount,p.namechinese,p.panterid,tw.placeddate FROM trade_wastebooks tw left join panters p on p.panterid=tw.panterid  WHERE tw.panterid IN ({$panterlists}) AND tw.flag ='{$map['flag']}' AND tw.tradetype = '{$map['tradetype']}' AND tw.placeddate ='{$map['placeddate']}' GROUP BY p.namechinese,p.panterid,tw.placeddate";
        return M('trade_wastebooks')->query($sql);
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
    /*
     *大食堂 根据交易类型与单号 查询卡号与cardno 结果
     */
     private  function getCard($type,$tradeid){
         if($type=='charge'){
             $map['flag']='01';
         }elseif($type=='consume'){
             $map['flag']='02';
         }elseif($type=='refund'){
             $map['flag']='04';
         }elseif($type=='return'){
             $map['flag']='03';
         }
         //获取 cardno  和 cardnum
         $map['tradeid']=$tradeid;
         return $list=M('green_order')->where($map)->find();
     }
    /*交易明细查询
     *
     * var $type 交易类型 charge 充值
     *
     * var $tradeid 交易流水号
     */
    private function searchMethod($type,$tradeid){
        $list=$this->getCard($type,$tradeid);
        //消费明细 退菜
        $consumlist=M('green_order')->alias('go')
                    ->join('left join panters p on p.panterid=go.panterid')
                    ->field('p.namechinese as pname,go.goodsname,go.price,go.num,go.placeddate,go.placedtime,go.flag')
                    ->where(['go.cardno'=>$list['cardno'],'go.cardnum'=>$list['cardnum'],'go.flag'=>['in',['02','04']]])
				    ->order('go.placeddate asc,go.placedtime asc')  	
                    ->select();		
        //合计消费金额
        $sum=0;
        foreach($consumlist as $val){
            if($val['flag']=='04'){
                $sum=bcsub($sum,bcmul($val['price'],$val['num'],2),2);
            }
            $sum=bcadd($sum,bcmul($val['price'],$val['num'],2),2);
        }
        $this->assign('sum',$sum);
        return $consumlist;
    }
    /* 查询充值记录
     *
     * var $type 交易类型 charge 充值
     *
     * var $tradeid 交易流水号
     */
    private function getCharge($type,$tradeid){
        $list=$this->getCard($type,$tradeid);
        $result=$this->model->table('green_order')->alias('go')
                            ->join('right join card_purchase_logs ca on ca.purchaseid=go.tradeid')
                            ->where(['go.flag'=>'01','go.cardno'=>$list['cardno'],'go.cardnum'=>$list['cardnum'],'ca.cardno'=>$list['cardno']])
                            ->field('sum(ca.amount) as amount')->select();
        return $result[0]['amount'];
    }
    //团购卡 明细
    public function teamDetail(){
		set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model=new Model();
        $startdate=trim(I('get.startdate',''));
        $startdate==true||$startdate=date('Y-m-d');
        $date=str_replace('-','',$startdate);
        //计算团购充值
        $map['placeddate']=$date;
        $map['teamid']=['like','team_id%'];
        $teamlist=M('green_team')->where($map)->field('distinct teamid')->select();
        $teamlist=array_column($teamlist,'teamid');
        foreach($teamlist as $val){
            $res[]=$this->everyTeam($val);
        }
        $this->assign('startdate',$startdate);
        $this->assign('res',$res);
        $this->display();
    }
    //数组 处理为 in方法的查询字符串
    /*
     * $arr 数组
     */
    private function inString($arr){
        $str='';
        foreach($arr as $val){
          $str.="'".$val."',";
        }
        return rtrim($str,',');
    }
    /*
     * 查询每笔团冲的详情
     * 充值 teamid 团冲订单号
     * 消费
     * 退菜
     * 以及退款
     */
    private function everyTeam($teamid){
        $slq="(SElECT purchaseid from green_team WHERE teamid='{$teamid}')";
        /* green_order
         *  基于purchaseid  与卡号 green_order 表获取cardno 与cardnum
         */
        $list=$this->model->table($slq)->alias('pu')
                           ->join('left join card_purchase_logs ca on ca.purchaseid=pu.purchaseid')
                           ->where(['ca.userid'=>'0000000000000000'])
                           ->field('ca.purchaseid,ca.cardno')->select();
        //充值 charge
        $chonglist=$this->model->query($slq);
        $chonglist=array_column($chonglist,'purchaseid');
        $charge=M('card_purchase_logs')->where(['purchaseid'=>['in',$chonglist],'userid'=>'0000000000000000'])
            ->field('sum(amount) as asum')->select();
        $charSum=$charge[0]['asum'];
        $consum=0;
        foreach($list as $v){
            //单卡消费
            $cardinfo=$this->model->table('green_order')
                                  ->where(['tradeid'=>$v['purchaseid'],'cardno'=>$v['cardno']])
                                  ->field('cardno,cardnum')->find();
            $consumlist=$this->model->table('green_order')
                ->where(['cardno'=>$cardinfo['cardno'],'cardnum'=>$cardinfo['cardnum'],'flag'=>'02'])
                ->group('cardno,cardnum,flag')
                ->field('nvl(sum(price*num),0) as amount,flag')->select();
            $refunlist=$this->model->table('green_order')
                ->where(['cardno'=>$cardinfo['cardno'],'cardnum'=>$cardinfo['cardnum'],'flag'=>'04'])
                ->group('cardno,cardnum,flag')
                ->field('nvl(sum(price*num),0) as amount,flag')->select();
            if($refunlist==true){
                $consum=bcadd($consum,bcsub($consumlist[0]['amount'],$refunlist[0]['amount'],2),2);
            }else{
                $consum=bcadd($consum,$consumlist[0]['amount'],2);
            }
        }
          $return=bcsub($charSum,$consum,2);
           return ['teamid'=>$teamid,'charge'=>$charSum,'consume'=>$consum,'return'=>$return];
    }
    public function consumeecel(){
        $teamid=trim(I('get.teamid',''));
        $slq="(SElECT purchaseid from green_team WHERE teamid='{$teamid}')";
        $list=$this->model->table($slq)->alias('pu')
            ->join('left join card_purchase_logs ca on ca.purchaseid=pu.purchaseid')
            ->where(['ca.userid'=>'0000000000000000'])
            ->field('ca.purchaseid,ca.cardno')->select();
        $res=[];
        foreach($list as $v){
            //单卡消费
            $cardinfo=$this->model->table('green_order')
                ->where(['tradeid'=>$v['purchaseid'],'cardno'=>$v['cardno']])
                ->field('cardno,cardnum')->find();
            $datalist=$this->model->table('green_order')->alias('go')
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
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $cellMerge="A1:J3";
        $titleName='大食堂档口交易明细';
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
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5',$teamid.'_大食堂团卡交易.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
}
?>
