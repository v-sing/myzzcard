<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 10:29
 */

namespace Home\Controller;



use Service\Account;
use Service\BaseService;
use Service\CoinAccount;
use Service\Panters;
use Service\PrintReceipt;
use Service\PrintReceiptLogs;
use Service\ReceiptClient;
use Service\ReceiptPanter;
use Service\TenantExtra;
use Think\Exception;
use Think\Model;
use Think\Page;

class PanterReceiptController extends CoinController
{
    public function  index()
    {
        $start     = I('get.start');
        $company = trim(I('get.company'));
        $panterName = trim(I('get.pantername'));
        $msg       = trim(I('get.msg'));
        if($start =='') {
            $start = date('Ym01',strtotime('-1 month'));
        }
        $startTime =  date('Ym01',strtotime($start));
        $endTime   = date('Ymd',strtotime('+1 month -1 day',strtotime($startTime)));
        $where = ['c.placeddate'=>[['egt',$startTime],['elt',$endTime]]];
        if($panterName != ''){
            $where['ps.namechinese'] = ['like',"%$panterName%"];
        }
        if($company != ''){
            $where['ps.nameenglish'] = ['like',"%$company%"];
        }

        #初始化赠送通宝提供商
        $coinAccountService = new CoinAccount();
        #获取所有商户赠送的通宝数量
        $coinAccountService->getAllCostByPanters($where,15);
        #初始化开票提供商
        $receiptPanterService = new ReceiptPanter();
        #初始化商户提供商
        $panterService      = new Panters();
        //匹配开票信息
        if(!is_null($coinAccountService->info) && !empty($coinAccountService->info)){
            #获取所需商户的具体信息
            $panterService->getPanterInfo($coinAccountService->panterid);
            #获取所需商户的开票的信息
            $receiptPanterService->getReceiptPanterInfo(['panterid'=>['in',$coinAccountService->panterid],['printtype'=>['eq',1]],['receiptdate'=>date("Y-m",strtotime($start))]]);
            foreach ($coinAccountService->info as $key=>&$val){
                foreach ($panterService->info as $panVal){
                    if($val['panterid'] == $panVal['panterid']){
                        $val['panterInfo'] = $panVal;
                    }
                }
                $paytab = $this->newProvisions($val['panterid'],$endTime,$startTime);
                $val['selltotalmoney'] = bcsub(sprintf("%.2f",$paytab['val']),sprintf("%.2f",$paytab['expire2']),2);
                if(!is_null($receiptPanterService->info) && !empty($receiptPanterService->info)){
                    foreach ($receiptPanterService->info as $receiptval){
                        if($val['panterid'] == $receiptval['panterid']){
                            $val['msg'] = $receiptval['openreceiptid'];
                        }
                    }
                }else{
                    $val['msg'] = "0";
                }
                if($msg == 1 && $val['msg'] == 0 || $msg == 2 && $val['msg'] != 0){
                       unset($coinAccountService->info[$key]);
                }
            }
        }
        $this->assign('data',$coinAccountService->info);
        if($msg == '' || $msg == 0){
            $this->assign('page',$coinAccountService->page->show2());
        }else{
            $Page = new Page(count($coinAccountService->info),15);
            $this->assign('page',$Page->show2());
        }

        #获取所有商户赠送的通宝数量
        $coinAccountService->getAllCostByPanters($where);
        $CoinPanterids = $coinAccountService->panterid;
        #获取所需商户的开票的信息
        if(!is_null($CoinPanterids) && !empty($CoinPanterids)){
            $receiptPanterService->getReceiptPanterInfo(['panterid'=>['in',$coinAccountService->panterid],['printtype'=>['eq',1]],['receiptdate'=>date("Y-m",strtotime($start))]]);
            $receiptPanterids = $receiptPanterService->panterid;
            //已开票金额
            $Panterids = array_intersect($CoinPanterids,$receiptPanterids);
            $beforMoney = $this->receiptMoney($startTime,$endTime,$Panterids);

            $this->assign("beforMoney",$beforMoney);

            //未开票
            $Panterids  = array_diff($CoinPanterids,$receiptPanterids);
            $afterMoney = $this->receiptMoney($startTime,$endTime,$Panterids);
            $this->assign("afterMoney",$afterMoney);
            //总开票
            $Panterids = $CoinPanterids;
            $totalissueMoney = $this->receiptMoney($startTime,$endTime,$Panterids);
            $this->assign("totalIsuue",$totalissueMoney);
        }else{
            $this->assign("totalIsuue",0);
            $this->assign("afterMoney",0);
            $this->assign("beforMoney",0);
        }

        $this->display();
    }

    /**开票金额
     * @param $startTime
     * @param $endTime
     * @param null $Panterids
     * @return string
     */
    public function receiptMoney($startTime,$endTime,$Panterids = null)
    {
        if(empty($Panterids)){
            return 0;
        }
        $paytab = $this->newProvisions($Panterids,$endTime,$startTime);
        $totalissueMoney = bcsub(sprintf("%.2f",$paytab['val']),sprintf("%.2f",$paytab['expire2']),2);
        return $totalissueMoney;
    }

    public function  openReceipt()
    {
        $panterid =  I('post.panterids/a');
        $preview   =  trim(I('get.preview'));
        #初始化
        $start     = I('post.start');
        if($start =='') {
            $start = date('Ymd',strtotime('-1 month'));
        }
        $startTime =  date('Ym01',strtotime($start));
        $endTime   = date('Ymd',strtotime('+1 month -1 day',strtotime($startTime)));
        #初始化开票提供商
        $receiptPanterService = new ReceiptPanter();
        $where = ['receiptdate'=>$start,'panterid'=>['in',$panterid],'printtype'=>1];
        $receiptPanterService->getReceiptPanterInfo($where);
        if(!is_null($receiptPanterService->info) && !empty($receiptPanterService->info)){
            $this->ajaxReturn([
                'msg'      => '开票失败,已开完票，请勿重复开票',
                'code'     => 10061 ,
                'panterid' => $panterid,
                'date'     => $start
            ]);
        }
        $where = ['c.placeddate'=>[['egt',$startTime],['elt',$endTime]],'c.panterid'=>['in',$panterid]];
        #初始化商户赠送通宝提供商
        $coinAccountService = new CoinAccount();
        $coinAccountService->getAllCostByPanters($where);
        #初始化所需的商户信息
        $panterService     = new Panters();
        $panterService->getPanterInfo($panterid);
        $insertData = [];
        foreach ($coinAccountService->info as &$val){
            foreach ($panterService->info as $panVal){
                if($val['panterid'] == $panVal['panterid']){
                    $val['panterInfo'] = $panVal;
                }
            }

            $paytab = $this->newProvisions($val['panterid'],$endTime,$startTime);
            $val['selltotalmoney'] = bcsub(sprintf("%.2f",$paytab['val']),sprintf("%.2f",$paytab['expire2']),2);
            //因为开票最多不能超过10万所以增加队列
            if($val['selltotalmoney']>100000){
                $code = 1;
               $money_list = $this->money_list($val['selltotalmoney']);
               foreach ($money_list as $m){
                   $insert = [
                       'OPENRECEIPTID'  =>$this->getFieldNextNumber('RECEIPTPANTER'),
                       'RECEIPTDATE'    => "'$start'",
                       'STATUS'         => 1,
                       "PANTERID"       => "'{$val['panterid']}'",
                       "ISRECEIPT"      => 1,
                       "ISOPEN"         => 1,
                       "RECEIPTMONEY"   => "'{$m}'",
                       "RECEIPTNUM"    => '0',
                       "PRINTTYPE"     => 1,
                       "NAMECHINESE"   => "'{$val['panterInfo']['namechinese']}'"
                   ];
                   $insertSql = $coinAccountService->getInsertSql($insert,"RECEIPTPANTER");
                   $this->model->execute($insertSql);
               }

            }else{
                $code = 0;
                $insert = [
                    'OPENRECEIPTID'  =>$this->getFieldNextNumber('RECEIPTPANTER'),
                    'RECEIPTDATE'    => "'$start'",
                    'STATUS'         => 1,
                    "PANTERID"       => "'{$val['panterid']}'",
                    "ISRECEIPT"      => 1,
                    "ISOPEN"         => 1,
                    "RECEIPTMONEY"   => "'{$val['selltotalmoney']}'",
                    "RECEIPTNUM"    => '0',
                    "PRINTTYPE"     => 1,
                    "NAMECHINESE"   => "'{$val['panterInfo']['namechinese']}'"
                ];
                $insertSql = $coinAccountService->getInsertSql($insert,"RECEIPTPANTER");
                $this->model->execute($insertSql);
            }

        }
        $this->ajaxReturn([
            'msg'      => '开票成功',
            'code'     => $code ,
            'panterid' => $panterid,
            'date'     => $start
        ]);

    }

    #打印发票
    public function  service()
    {
        $year    = trim(I('get.year',''));
        $quarter = trim(I('get.quarter',''));
        $company = trim(I('get.company'));
        $panterName = trim(I('get.pantername'));
        $year!='' || $year='2018';
        $quarter!='' || $quarter='1';
        $company = trim(I('get.company'));
        $msg   = trim(I('get.msg'));
        $pantername = trim(I('get.pantername'));
        switch ($quarter){
            case '2':
                $startTime =  date('Ymd',strtotime($year.'0401'));
                $endTime =  date('Ymd',strtotime($year.'0630'));
                break;
            case '3':

                $startTime =  date('Ymd',strtotime($year.'0701'));
                $endTime   = date('Ymd',strtotime($year.'0930'));
                break;
            case '4':

                $startTime =  date('Ymd',strtotime($year.'1001'));
                $endTime   =    date('Ymd',strtotime($year.'1231'));
                break;

            default:

                $startTime =  date('Ymd',strtotime($year.'0101'));
                $endTime   =  date('Ymd',strtotime($year.'0331'));
                break;

        }
        $where['c.placeddate'] = [['egt',$startTime], ['elt',$endTime]];
        if($panterName != ''){
            $where['ps.namechinese'] = ['like',"%$panterName%"];
        }
        if($company != ''){
            $where['ps.nameenglish'] =  ['like',"%$company%"];
        }

        #初始化赠送通宝提供商
        $coinAccountService = new CoinAccount();
        #获取所有商户赠送的通宝数量
        $coinAccountService->getAllCostByPanters($where,15);
        #初始化开票提供商
        $receiptPanterService = new ReceiptPanter();
        #初始化商户提供商
        $panterService      = new Panters();
        if(!is_null($coinAccountService->info) && !empty($coinAccountService->info)){
            #获取所需商户的具体信息
            $panterService->getPanterInfo($coinAccountService->panterid);
            #获取所需商户的开票的信息
            $receiptPanterService->getReceiptPanterInfo(['panterid'=>['in',$coinAccountService->panterid],['printtype'=>['eq',2]],['receiptdate'=>$startTime."-".$endTime]]);
            foreach ($coinAccountService->info as $key=>&$val){
                $val['selltotalmoney'] = bcmul($val['selltotalmoney'],0.05,2) ;

                foreach ($panterService->info as $panVal){
                    if($val['panterid'] == $panVal['panterid']){
                        $val['panterInfo'] = $panVal;
                    }
                }
                if(!is_null($receiptPanterService->info) && !empty($receiptPanterService->info)){
                    foreach ($receiptPanterService->info as $receiptval){
                        if($val['panterid'] == $receiptval['panterid'] && $receiptval['receiptdate'] == $startTime."-".$endTime){
                            $val['msg'] = $receiptval['openreceiptid'];
                        }
                    }
                }else{
                    $val['msg'] = "0";
                }
                if($msg == 1 && $val['msg'] == 0 || $msg == 2 && $val['msg'] != 0){
                    unset($coinAccountService->info[$key]);
                }
            }
        }

        $this->assign('data',$coinAccountService->info);
        if($msg == '' || $msg == 0){
            $this->assign('page',$coinAccountService->page->show2());
        }else{
            $Page = new Page(count($coinAccountService->info),15);
            $this->assign('page',$Page->show2());
        }

        #获取所有商户赠送的通宝数量
        $coinAccountService->getAllCostByPanters($where);
        $CoinPanterids = $coinAccountService->panterid;
        if(!is_null($CoinPanterids) && !empty($CoinPanterids)){
            $CoinSumByPanterid = array_column($coinAccountService->info,"selltotalmoney","panterid");
            #获取所需商户的开票的信息
            $receiptPanterService->getReceiptPanterInfo(['panterid'=>['in',$coinAccountService->panterid],['printtype'=>['eq',2]],['receiptdate'=>$startTime."-".$endTime]]);        $receiptPanterids = $receiptPanterService->panterid;
            //已开票金额
            $Panterids = array_intersect($CoinPanterids,$receiptPanterids);
            $beforMoney = 0;
            foreach ($Panterids as $panterid){
                if(array_key_exists($panterid,$CoinSumByPanterid)){
                    $beforMoney +=bcmul($CoinSumByPanterid[$panterid],0.05,2);
                }
            }

            $this->assign("beforMoney",$beforMoney);

            //未开票
            $Panterids  = array_diff($CoinPanterids,$receiptPanterids);
            $afterMoney = 0;
            foreach ($Panterids as $panterid){
                if(array_key_exists($panterid,$CoinSumByPanterid)){
                    $afterMoney +=bcmul($CoinSumByPanterid[$panterid],0.05,2);
                }
            }
            $this->assign("afterMoney",$afterMoney);


            //总开票
            $totalissueMoney = bcmul(array_sum(array_values($CoinSumByPanterid)),0.05,2);

            $this->assign("totalIsuue",$totalissueMoney);
        }else{
            $this->assign("totalIsuue",0);
            $this->assign("afterMoney",0);
            $this->assign("beforMoney",0);
        }
        $this->display('index');
    }

    #服务票
    public function  openReceiptTwo()
    {
        $panterid =  I('post.panterids/a');
        #初始化
        $year    = trim(I('get.year',''));
        $quarter = trim(I('get.quarter',''));
        $year!='' || $year='2018';
        $quarter!='' || $quarter='1';
        switch ($quarter){
            case '2':
                $startTime =  date('Ymd',strtotime($year.'0401'));
                $endTime =  date('Ymd',strtotime($year.'0630'));
                break;
            case '3':

                $startTime =  date('Ymd',strtotime($year.'0701'));
                $endTime   = date('Ymd',strtotime($year.'0930'));
                break;
            case '4':

                $startTime =  date('Ymd',strtotime($year.'1001'));
                $endTime   =    date('Ymd',strtotime($year.'1231'));
                break;

            default:

                $startTime =  date('Ymd',strtotime($year.'0101'));
                $endTime   =  date('Ymd',strtotime($year.'0331'));
                break;

        }
        #初始化开票提供商
        $receiptPanterService = new ReceiptPanter();
        $rwhere = ['receiptdate'=>$startTime."-".$endTime,'panterid'=>['in',$panterid],'printtype'=>2];
        $receiptPanterService->getReceiptPanterInfo($rwhere);
        if(!is_null($receiptPanterService->info) && !empty($receiptPanterService->info)){
            $this->ajaxReturn([
                'msg'      => '开票失败,已开完票，请勿重复开票',
                'code'     => 10061 ,
                'panterid' => $panterid,
                'date'     => $startTime
            ]);
        }
        $where = ['c.placeddate'=>[['egt',$startTime],['elt',$endTime]],'c.panterid'=>['in',$panterid]];
        #初始化商户赠送通宝提供商
        $coinAccountService = new CoinAccount();
        $coinAccountService->getAllCostByPanters($where);
        #初始化所需的商户信息
        $panterService     = new Panters();
        $panterService->getPanterInfo($panterid);
       // $insertData = [];
        foreach ($coinAccountService->info as &$val){
            foreach ($panterService->info as $panVal){
                if($val['panterid'] == $panVal['panterid']){
                    $val['panterInfo'] = $panVal;
                }
            }
            $val['selltotalmoney'] = bcmul($val['selltotalmoney'],0.05,2);
            if($val['selltotalmoney'] >100000){
                $code = 1;
                $money_list = $this->money_list($val['selltotalmoney']);
                foreach ($money_list as $m){
                    $insert = [
                        'OPENRECEIPTID'  =>$this->getFieldNextNumber('RECEIPTPANTER'),
                        'RECEIPTDATE'    => "'{$startTime}-{$endTime}'",
                        'STATUS'         => 1,
                        "PANTERID"       => "'{$val['panterid']}'",
                        "ISRECEIPT"      => 1,
                        "ISOPEN"         => 1,
                        "RECEIPTMONEY"   => "'{$m}'",
                        "RECEIPTNUM"    => '0',
                        "PRINTTYPE"     => 2,
                        "NAMECHINESE"   => "'{$val['panterInfo']['namechinese']}'"
                    ];
                    $insertSql = $coinAccountService->getInsertSql($insert,"RECEIPTPANTER");
                    $this->model->execute($insertSql);
                }
            }else{
                $code = 0;
                $insert = [
                    'OPENRECEIPTID'  =>$this->getFieldNextNumber('RECEIPTPANTER'),
                    'RECEIPTDATE'    => "'{$startTime}-{$endTime}'",
                    'STATUS'         => 1,
                    "PANTERID"       => "'{$val['panterid']}'",
                    "ISRECEIPT"      => 1,
                    "ISOPEN"         => 1,
                    "RECEIPTMONEY"   => "'{$val['selltotalmoney']}'",
                    "RECEIPTNUM"    => '0',
                    "PRINTTYPE"     => 2,
                    "NAMECHINESE"   => "'{$val['panterInfo']['namechinese']}'"
                ];

                $insertSql = $coinAccountService->getInsertSql($insert,"RECEIPTPANTER");
                $this->model->execute($insertSql);
            }



        }
        $this->ajaxReturn([
            'msg'      => '开票成功',
            'code'     => $code ,
            'panterid' => $panterid,
            'date'     => $startTime
        ]);

    }


    #开始打印
    public function  sendPrint()
    {
        $panterid = I('post.openreceiptid');
        $printType = I('post.printtype');
        $type     = I('post.type');
        $money    = I('post.money');
        $start    = I('post.receiptdate');
        $year    = trim(I('get.year',''));
        $quarter = trim(I('get.quarter',''));
        $preview = trim(I('post.preview'));
        $address = trim(I('post.address'));
        $bank = trim(I('post.bank'));
        $bankaccount = trim(I('post.bankaccount'));
        $linktel = trim(I('post.linktel'));
        $pantername = trim(I('post.pantername'));
        $year!='' || $year='2018';
        $quarter!='' || $quarter='1';
        switch ($quarter){
            case '2':
                $startTime =  date('Ymd',strtotime($year.'0401'));
                $endTime =  date('Ymd',strtotime($year.'0630'));
                break;
            case '3':

                $startTime =  date('Ymd',strtotime($year.'0701'));
                $endTime   = date('Ymd',strtotime($year.'0930'));
                break;
            case '4':

                $startTime =  date('Ymd',strtotime($year.'1001'));
                $endTime   =    date('Ymd',strtotime($year.'1231'));
                break;

            default:

                $startTime =  date('Ymd',strtotime($year.'0101'));
                $endTime   =  date('Ymd',strtotime($year.'0331'));
                break;

        }
        if($start =='' && $year == '' && $quarter == ''){
            $this->ajaxReturn([
                'msg'  => '参数错误，请重试',
                'code' =>  10061
            ]);
        }
        if($start == ''){
            $start = $startTime."-".$endTime;
        }
        if(!is_numeric($panterid) || empty($panterid) || is_null($panterid)){
            $this->ajaxReturn([
                'msg'  => '参数错误，请重试',
                'code' =>  10061
            ]);
        }
        #实例化开票记录提供商
        $ReceiptPanterService = new ReceiptPanter();
        $ReceiptPanterService->getInfo(['panterid' => $panterid,'printtype'=>$type,'receiptdate'=>$start,'receiptmoney'=>$money]);
        if(is_null($ReceiptPanterService->info) || empty($ReceiptPanterService->info)){
            $this->ajaxReturn([
                'msg'  => '抱歉无此记录，请查明再打印',
                'code' =>  10061
            ]);
        }
        #实例化商户提供商
        $PanterService       = new Panters();
        $PanterService->getInfo(["panterid"=>$ReceiptPanterService->get('panterid')]);
        $TenantExtraService = new TenantExtra();
        $TenantExtraService->getInfo(["pantername"=>$PanterService->get('namechinese')]);
        if(is_null($TenantExtraService->info) || empty($TenantExtraService->info)){
            $this->ajaxReturn([
                'msg'  => '抱歉，商户信息不符，请移步项目信息管理，补全信息再进行操作',
                'code'  => 10061
            ]);
        }
        $ReceiptPanterService->info[0]['panterinfo'] = $TenantExtraService->info[0];
        if($preview != ''){
            $this->ajaxReturn([
                'data'  => $ReceiptPanterService->info[0]['panterinfo'],
                'code'  => 10063
            ]);
        }

        $ReceiptPanterService->info[0]['panterinfo']['address'] = $address;
        $ReceiptPanterService->info[0]['panterinfo']['bank'] = $bank;
        $ReceiptPanterService->info[0]['panterinfo']['bankaccount'] = $bankaccount;
        $ReceiptPanterService->info[0]['panterinfo']['linktel'] = $linktel;
        $ReceiptPanterService->info[0]['panterinfo']['pantername'] = $pantername;
        $ReceiptClientService = new ReceiptClient();
        if($ReceiptPanterService->info[0]['printtype'] == 2){
            $order =  $ReceiptClientService->getKpXml($ReceiptPanterService->info[0],$printType,1);
        }else{
            $order =  $ReceiptClientService->getKpXml($ReceiptPanterService->info[0],$printType);
        }
        try{
            $ReceiptClientService->getResultXml();
        }catch (Exception $e){
            $this->ajaxReturn([
                'msg'  => "任务超时，请重新拔插税盘，如有问题请联系管理员",
                'code'  => 10061
            ]);
        }

        if(is_null($ReceiptClientService->parseArr) || empty($ReceiptClientService->parseArr) || !isset($ReceiptClientService->parseArr['returncode']) || $ReceiptClientService->parseArr['returncode'][0] !=0 ){             $this->ajaxReturn([
            'msg'  => isset($ReceiptClientService->parseArr['returnmsg'])?$ReceiptClientService->parseArr['returnmsg']:"请打印",
            'code'  => 10061
        ]);
        }
        #初始化打印记录提供商
        $PrintReceiptLogsSercive = new  PrintReceiptLogs();
        $data = [
            'openreceiptid'=> $ReceiptPanterService->get('openreceiptid'),
            'lsorder'       => $order,
            'printDate'     => time(),
            "invoicetype"   => $printType
        ];
        $data =   $PrintReceiptLogsSercive->filterInput(null,$data);
        $SQL =  $PrintReceiptLogsSercive->getInsertSql($data,"PRINTRECEIPTLOGS");
        $this->model->execute($SQL);
        #更新打印次数
        $ReceiptPanterService->changePrintNum(['openreceiptid'=>$ReceiptPanterService->get('openreceiptid')]);
        $ReceiptPanterService->changePrintDate(['openreceiptid'=>$ReceiptPanterService->get('openreceiptid')]);
        $this->ajaxReturn([
            'msg'  => '调用成功，请预览并打印',
            'code'  => 0
        ]);

    }

    #打印记录
    public  function  printLogs()
    {
        $start  = I('get.start');
        $end    = I('get.end');
        $lsorder = I('get.lsorder');
        if($start == '' || $end == ''){
            $start = date('Y-m-d');
            $end   = date('Y-m-d',strtotime("+1 month -1 day",strtotime(date("Y-m-01 23:59:59"))));
        }
        $where = ["printdate"=>[['egt',$start],['elt',$end]]];
        if($lsorder != ''){
            $where['lsorder'] = ['like',"%$lsorder%"];
        }
        #初始化打印记录提供商
        $PrintReceiptLogsService = new PrintReceiptLogs();
        $PrintReceiptLogsService->getDistinctInfo($where,15);
        #初始化订单记录提供商
        $ReceiptPanterService  = new ReceiptPanter();
        if(!is_null($PrintReceiptLogsService->info) && !empty($PrintReceiptLogsService->info)){
            $ReceiptPanterService->getInfo(['openreceiptid'=>['in',$PrintReceiptLogsService->openreceiptid]]);
            #初始化商户额外信息表
            $TenantExtraService = new TenantExtra();
            $TenantExtraService->getInfo(['pantername'=>['in',$ReceiptPanterService->namechinese]]);
            foreach ($ReceiptPanterService->info as &$val){
                foreach ($TenantExtraService->info as $v){
                    if($val['namechinese'] == $v['pantername']){
                        $val['extra'] = $v;
                    }
                }

            }
            foreach ($PrintReceiptLogsService->info as &$val){
                foreach ($ReceiptPanterService->info as $v){
                    if($val['openreceiptid'] == $v['openreceiptid']){
                        $val['panterReceipt'] = $v;
                    }
                }
            }
            $this->assign('page',$PrintReceiptLogsService->page->show2());
        }
        $this->assign('data',$PrintReceiptLogsService->info);
        $this->display();
    }


    public function  Excel()
    {
        $Service = new BaseService();
        $Service->Excel();
    }




    public function  filterPanterNameCompany($data)
    {
        $company = trim(I('get.company'));
        $panterName = trim(I('get.pantername'));
        if($company ==  '' && $panterName == ''){
            return $data;
        }

        foreach ($data as $key=>&$val){
            if(!isset($val['panterInfo'])){
                unset($val);
                continue;
            }
            if($panterName !=''){
                if($val['panterInfo']['namechinese'] != $panterName){
                    unset($data[$key]);
                }
            }
            if($company !=''){
                if($val['panterInfo']['nameenglish'] != $company){
                    unset($data[$key]);
                }
            }

        }
        return $data;
    }


    private function newProvisions($panterid,$date =null,$sdate=null){
        if(is_null($date)){
            $nowtime = date('Ymd',time());//现在时间
            $subTime = $nowtime;//获取要查询的时间
        }else{
            $nowtime = $date;
            $subTime = $date;
        }
        if(is_null($date)){
            $sdate = "20160407";
        }
        $endLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day'));//上个月最后一天
        $model=new Model();
        if(is_array($panterid)){
            $map['panterid'] = ['in',$panterid];
        }else{
            $map['panterid'] = $panterid;
        }

        $map['placeddate'] = ['elt',$nowtime];

        //截至本日期所发行的金额
        $issueTotalAmount = $model->table('coin_account')
            ->where($map)
            ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];


        if($issueTotalAmount!=0){
            if(is_array($panterid)) {
                $last['panterid'] = ['in',$panterid];
            }else{
                $last['panterid'] = $panterid;
            }
            $last['placeddate']= ['elt',$endLastMonth];

            //截止上月
            $issueLastAmount  =   $model->table('coin_account')
                ->where($last)
                ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];
            if(is_array($panterid)) {
                $settle['ca.panterid'] = ['in',$panterid];
            }else{
                $settle['ca.panterid'] = $panterid;
            }
            $settle['cc.placeddate'] = ['elt',$nowtime];
            $settleTotalAmount = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settle)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额
            if(is_array($panterid)) {
                $settleLast['ca.panterid'] = ['in',$panterid];
            }else{
                $settleLast['ca.panterid'] = $panterid;
            }

            $settleLast['cc.placeddate']  = ['elt',$endLastMonth];
            $settleLastAmount = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settleLast)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额


        }else{
            $settleTotalAmount = 0;
            $issueLastAmount= 0;
        }
        //已收到备付金总额 = 截至上月通宝发行总金额-截止上月已经兑换）*0.2+截止上月已经兑换
        $provisionAmount   = bcadd(bcmul(bcsub($issueLastAmount,$settleLastAmount,2),0.2,2),$settleLastAmount,2);
        //(截止到当前发行通宝金额 - 截止当前已经兑换)*0.2 + 截止当前已经兑换
        $provisionNow     = bcadd(bcmul(bcsub($issueTotalAmount,$settleTotalAmount,2),0.2,2),$settleTotalAmount,2);
        $value   = bcsub($provisionNow,$provisionAmount,2);
        $str     = "目前发行:$issueTotalAmount, 截止上月发行:$issueLastAmount,目前兑换:$settleTotalAmount,截止上月兑换:$settleLastAmount";
        //返回通宝过期消费商户 目前 已经兑换的金额
        $settle['cc.panterid'] = '00004616';
        if(is_array($panterid)) {
            $settle['ca.panterid'] = ['in',$panterid];
        }else{
            $settle['ca.panterid'] = $panterid;
        }
        $settle['cc.placeddate'] = ['elt',$nowtime];
        $expire = $model->table('coin_account')->alias('ca')
            ->join('left join coin_consume cc on cc.coinid=ca.coinid')
            ->where($settle)
            ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额
        if (!is_null($sdate)){
            $where['cc.panterid'] = "00004616";
            $where['cc.placeddate'] = [['elt',$nowtime],['egt',$sdate]];

        }
        if(is_array($panterid)){
            $where['ca.panterid'] = ['in',$panterid];
        }else{
            $where['ca.panterid'] = $panterid;
        }
        $expire2 = $model->table('coin_account')->alias('ca')
            ->join('left join coin_consume cc on cc.coinid=ca.coinid')
            ->where($where)
            ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];

        return ['val'=>$value,'msg'=>$str,'expire'=>$expire,'expire2'=>$expire2];
    }



    /**
     * money list
     */
    public function  money_list($money)
    {
        static $moneyArr = [];
        if($money>=90000){
            $money = $money - 90000;
            array_push($moneyArr,90000);
           $this->money_list($money);
        }elseif($money>0){
            array_push($moneyArr,$money);
        }
        return $moneyArr;
    }

    public function  PreviewReceiptByPanterid()
    {
        $panterid = I('post.openreceiptid');
        $type     = I('post.type');
        $money    = I('post.money');
        $start    = I('post.receiptdate');
        $year    = trim(I('get.year',''));
        $quarter = trim(I('get.quarter',''));

        $year!='' || $year='2018';
        $quarter!='' || $quarter='1';
        switch ($quarter){
            case '2':
                $startTime =  date('Ymd',strtotime($year.'0401'));
                $endTime =  date('Ymd',strtotime($year.'0630'));
                break;
            case '3':

                $startTime =  date('Ymd',strtotime($year.'0701'));
                $endTime   = date('Ymd',strtotime($year.'0930'));
                break;
            case '4':

                $startTime =  date('Ymd',strtotime($year.'1001'));
                $endTime   =    date('Ymd',strtotime($year.'1231'));
                break;

            default:

                $startTime =  date('Ymd',strtotime($year.'0101'));
                $endTime   =  date('Ymd',strtotime($year.'0331'));
                break;

        }
        if($start =='' && $year == '' && $quarter == ''){
            $this->ajaxReturn([
                'msg'  => '参数错误，请重试',
                'code' =>  10061
            ]);
        }
        if($start == ''){
            $start = $startTime."-".$endTime;
        }
        if(!is_numeric($panterid) || empty($panterid) || is_null($panterid)){
            $this->ajaxReturn([
                'msg'  => '参数错误，请重试',
                'code' =>  10061
            ]);
        }
        #实例化开票记录提供商
        $ReceiptPanterService = new ReceiptPanter();
        $ReceiptPanterService->getInfo(['panterid' => $panterid,'printtype'=>$type,'receiptdate'=>$start]);
        if(is_null($ReceiptPanterService->info) || empty($ReceiptPanterService->info)){
            $this->ajaxReturn([
                'msg'  => '抱歉无此记录，请查明再打印',
                'code' =>  10061
            ]);
        }
        $this->ajaxReturn([
            'msg'  => '抱歉无此记录，请查明再打印',
            'code' =>  10061,
            'data' => $ReceiptPanterService->receiptmoney
        ]);

    }

}