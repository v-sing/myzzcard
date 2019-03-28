<?php
namespace Home\Controller;

/*可疑交易分析*/
class SusController extends CommonController{
    private $cash;//现金
    private $transfer;//转账
    private $cash_hz;//现金交易频次
    private $transfer_hz;//转账频次
    private $info;

    public function _initialize()
    {
        parent::_initialize();
        $this->cash = 50000;
        $this->transfer = 500000;
        $this->cash_hz = 20;
        $this->transfer_hz = 100;
        $this->info = array();
    }

    /*
     * @content 可疑交易初审表
     * */
    public function susIndex(){
        ini_set("max_execution_time",0);
        session("susIndex",null);
        $date = I("get.date",null);
        if(!$date){
            $date = date("Ymd",time());
        }
        if($date == "2"){
            $date = null;
        }
        $date = str_replace("-","",$date);


        $sus = D("sus");
        $charge_arr = $sus->getCharge($this->transfer,$this->transfer_hz,null,$date);

        $charge_queue = array();
        $trade_queue = array();
        if($charge_arr){
            foreach ($charge_arr as $k => $charge_v){
                $traceid = "";
                $traceid = "sus".$charge_v['customid'].$charge_v['placeddate'];//标识数据
                $trade_info = $sus->getTrade(0,0,$charge_v['customid'],$charge_v['placeddate']);
                $trade_nu = 0;
                $trade_amount = 0;
                if($trade_info){
                    $trade_nu = $trade_info[0]['nu'];
                    $trade_amount = $trade_info[0]['amount'];
                }
                $charge_queue[$traceid]['personidtype'] = $charge_v['personidtype'];
                $charge_queue[$traceid]['customid'] = $charge_v['customid'];
                $charge_queue[$traceid]['personid'] = $charge_v['personid'];
                $charge_queue[$traceid]['unitname'] = $charge_v['unitname'];
                $charge_queue[$traceid]['namechinese'] = $charge_v['namechinese'];
                $charge_queue[$traceid]['linktel'] = $charge_v['linktel'];
                $charge_queue[$traceid]['countyid'] = "中国";
                $charge_queue[$traceid]['chargeamount'] = $charge_v['amount'];
                $charge_queue[$traceid]['chargenu'] = $charge_v['nu'];
                $charge_queue[$traceid]['tradeamount'] = $trade_amount ? $trade_amount : 0;
                $charge_queue[$traceid]['tradenu'] = $trade_nu ? $trade_nu : 0;
                $charge_queue[$traceid]['placeddate'] = $charge_v['placeddate'];
                $charge_queue[$traceid]['traceid'] = $traceid;
                // $charge_queue[$traceid]['type'] = "转账充值";

            }

        }
        $trade_arr = $sus->getTrade($this->transfer,$this->transfer_hz,null,$date);
        if($trade_arr){

            foreach ($trade_arr as $k => $trade_v){
                $traceid = "";
                $traceid = "sus".$trade_v['customid'].$trade_v['placeddate'];//标识数据
                $charge_info = $sus->getCharge(0,0,$trade_v['customid'],$trade_v['placeddate']);
                $charge_nu = 0;
                $charge_amount = 0;
                if($charge_info){
                    $charge_nu = $charge_info[0]['nu'];
                    $charge_amount = $charge_info[0]['amount'];
                }
                $trade_queue[$traceid]['personidtype'] = $trade_v['personidtype'];
                $trade_queue[$traceid]['customid'] = $trade_v['customid'];
                $trade_queue[$traceid]['personid'] = $trade_v['personid'];
                $trade_queue[$traceid]['namechinese'] = $trade_v['namechinese'];
                $trade_queue[$traceid]['linktel'] = $trade_v['linktel'];
                $trade_queue[$traceid]['unitname'] = $trade_v['unitname'];
                $trade_queue[$traceid]['countyid'] = "中国";
                $trade_queue[$traceid]['chargeamount'] = $trade_v['amount'];
                $trade_queue[$traceid]['chargenu'] = $trade_v['nu'];
                $trade_queue[$traceid]['tradeamount'] = $charge_amount ? $charge_amount : 0;
                $trade_queue[$traceid]['tradenu'] = $charge_nu ? $charge_nu : 0;
                $trade_queue[$traceid]['placeddate'] = $trade_v['placeddate'];
                $trade_queue[$traceid]['traceid'] = $traceid;
                // $trade_queue[$traceid]['type'] = "消费";

            }

        }
        $data = array_merge($charge_queue,$trade_queue);
        session("susIndex",$data);
        $this->assign("data",$data);
        $this->assign("date",$date);

        $this->display();
    }

    public function firstCheck(){
        if(IS_POST){
            $remark = I("post.comment",null);
            $traceid = I("post.traceid");
            $getInfo = session("susIndex");
            $userinfo = $getInfo[$traceid];

            if(!$remark){
                die(json_encode(array("code"=>0,"msg"=>"未填写意见")));
            }
            $sus = D("sus");
            $data['traceid'] = $traceid;
            $data['customid'] = $userinfo['customid'];
            $data['chargeamount'] = $userinfo['chargeamount'];
            $data['chargenu'] = $userinfo['chargenu'];
            $data['tradeamount'] = $userinfo['tradeamount'];
            $data['tradenu'] = $userinfo['tradenu'];
            $data['datadate'] = $userinfo['placeddate'];
            $data['remark'] = $remark;
            $bool = $sus->search($traceid);
            if($bool){
                die(json_encode(array("code"=>0,"msg"=>"已进行初步审核")));
            }
            $insert = $sus->inLarge($data);
            if($insert){
                die(json_encode(array("code"=>1,"msg"=>"初步审核成功")));
            }else{
                die(json_encode(array("code"=>0,"msg"=>"初步审核失败")));
            }
        }
    }

    public function rCheck(){
        $sus = D('sus');
        $data = $sus->search();

        foreach ($data as $k => $v){
            $customer = $sus->getCustomer($v['customid']);
            $this->packData($k,$v,$customer);
        }

        $this->assign("data",$this->info);
        $this->display();
    }
    public function doCheck(){
        $status = I("post.status");
        $traceid = I("post.traceid");
        $comment = I("post.comment",null);
        $bool = D("sus")->ud($status,$traceid,$comment);
        if($bool){
            die(json_encode(array("code"=>1)));

        }else{
            die(json_encode(array("code"=>0)));
        }

    }

    /*
     * @content 大额交易报告*/
    public function reportList(){
        $sus = D("sus");
        $data = $sus->search(null,1);
        foreach ($data as $k => $v){
            $customer = $sus->getCustomer($v['customid']);
            $this->packData($k,$v,$customer);
        }
        $this->assign("data",$this->info);
        $this->display();
    }
    /*
    * @content 导出可疑报告*/
    public function expReport(){
        $traceid = I("get.traceid");
        $sus = D("sus");
        $data = $sus->search($traceid,1);
        foreach ($data as $k => $v){
            $customer = $sus->getCustomer($v['customid']);
            $this->packData($k,$v,$customer);
        }
        $info = $this->info[0];
        $this->objPHPExcel->setActiveSheetIndex(0);
        $this->objPHPExcel->getActiveSheet()->setTitle("可疑交易报告");
        $this->objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $this->objPHPExcel->getActiveSheet()->getStyle("A1")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B2")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D2")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B3")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D3")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B4")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D4")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B5")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D5")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B6")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B7")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D7")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B8")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D8")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B9")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D9")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B10")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B11")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D11")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B12")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D12")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B13")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D13")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B14")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("D15")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B15")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("B16")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $this->objPHPExcel->getActiveSheet()->getStyle("D3")->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $this->objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(1);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(1);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("C")->setAutoSize(1);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("D")->setAutoSize(1);

        //---------设置内容-----------------------------------
        $this->objPHPExcel->getActiveSheet()->setCellValue('A1',"可疑交易报告");


        $this->objPHPExcel->getActiveSheet()->setCellValue('A2',"姓名：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B2',$info['namechinese']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C2',"手机：");
        $this->objPHPExcel->getActiveSheet()->setCellValueExplicit('D2',$info['linktel'],\PHPExcel_Cell_DataType::TYPE_STRING);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A3',"证件类型：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B3',$info['personidtype']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C3',"证件号：");
        $this->objPHPExcel->getActiveSheet()->setCellValueExplicit('D3',$info['personid'],\PHPExcel_Cell_DataType::TYPE_STRING);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A4',"客户职业：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B4',$info['unitname']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C4',"客户国籍：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D4',"中国");
        $this->objPHPExcel->getActiveSheet()->setCellValue('A5',"代办人姓名：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B5',$info['agentname']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C5',"代办人国籍：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D5',$info['agentcountry']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A6',"代办人证件类型：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B6',$info['agentidtype']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C6',"代办人证件号：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D6',$info['agentid']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A7',"交易金额(充值)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B7',$info['chargeamount']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C7',"交易频次(充值)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D7',$info['chargenu']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A8',"交易金额(消费)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B8',$info['tradeamount']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C8',"交易频次(消费)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D8',$info['tradenu']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A9',"交易时间：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B9',$info['date']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C9',"交易发生地：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D9',$info['tradeaddress']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A10',"客户银行卡号：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B10',$info['cusbankno']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A11',"交易对手名称：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B11',$info['rivalname']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C11',"交易对手银行账号：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D11',$info['rivalbankno']);

        $this->objPHPExcel->getActiveSheet()->setCellValue('A12',"可疑交易报告触发点：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B12',$info['susaddress']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C12',"报告紧急程度：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D12',$info['suslevel']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A13',"可疑交易事件起始日期：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B13',$info['susbegindate']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C13',"可疑交易事件结束日期：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D13',$info['susenddate']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A14',"报送次数：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B14',$info['reportnu']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('C14',"疑似涉罪类型：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('D14',$info['sustype']);

        $this->objPHPExcel->getActiveSheet()->setCellValue('A15',"交易信息备注1(初审审定意见)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B15',$info['remarkf']);
        $this->objPHPExcel->getActiveSheet()->setCellValue('A16',"交易信息备注2(复审审定意见)：");
        $this->objPHPExcel->getActiveSheet()->setCellValue('B16',$info['remarks']);


        //-------------end-----------------------------------
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel,'Excel5');
		ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="可疑交易报告.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');

    }

    /*
     * @content 展示大额交易报告*/
    public function seeReport(){
        $traceid = I("get.traceid");
        $sus = D("sus");
        $data = $sus->search($traceid,1);
        foreach ($data as $k => $v){
            $customer = $sus->getCustomer($v['customid']);
            $this->packData($k,$v,$customer);
        }
        $info = $this->info[0];
        $this->assign("data",$info);

        $this->display();
    }
    /*
     * @content 删除可疑交易报告*/
    public function del(){
        $traceid = I("post.traceid");
        $bool = D("sus")->del($traceid);
        if($bool){
            die(json_encode(array("code"=>1)));
        }else{
            die(json_encode(array("code"=>0)));
        }
    }

    /*
     * @content 修改可疑交易报告*/
    public function edReport(){
        $traceid = I("get.traceid");
        $sus = D("sus");
        $data = $sus->search($traceid,1);
        foreach ($data as $k => $v){
            $customer = $sus->getCustomer($v['customid']);
            $this->packData($k,$v,$customer);
        }
        $info = $this->info[0];
        $this->assign("data",$info);

        $this->display();
    }
    public function doEd(){
        if(IS_POST){
            /*太臃肿*/
            $remarkf = I("post.remarkf",null);
            $remarks = I("post.remarks",null);
            $traceid = I("post.traceid");

            $agentname = I("post.agentname",null);
            $agentcountry =  I("post.agentcountry",null);
            $agidtype = I("post.agidtype",null);
            $agid = I("post.agid",null);
            $tradeaddress = I("post.tradeaddress",null);
            $cubankno = I("post.cubankno",null);
            $rivalname = I("post.rivalname",null);
            $rivalbankno = I("post.rivalbankno",null);
            $susaddress = I("post.susaddress",null);
            $suslevel = I("post.suslevel",null);
            $susbegindate = I("post.susbegindate",null);
            $susenddate = I("post.susenddate",null);
            $reportnu = I("post.reportnu",null);
            $sustype = I("post.sustype",null);

            !$remarkf && $this->error("初审意见不能为空");
            !$remarks && $this->error("复审意见不能为空");
            $data = array("remarkf"=>$remarkf,"remarks"=>$remarks);
            $agentname ? $data['agentname'] = $agentname : "";
            $agentcountry ? $data['agentcountry'] = $agentcountry : "";
            $agidtype ? $data['agidtype'] = $agidtype : "";
            $agid ? $data['agid'] = $agid : "";
            $tradeaddress ? $data['tradeaddress'] = $tradeaddress : "";
            $cubankno ? $data['cubankno'] = $cubankno : "";
            $rivalname ? $data['rivalname'] = $rivalname : "";
            $rivalbankno ? $data["rivalbankno"] = $rivalbankno : "";
            $susaddress ? $data['susaddress'] = $susaddress : "";
            $suslevel ? $data['suslevel'] = $suslevel : "";
            $susbegindate ? $data['susbegindate'] = $susbegindate : "";
            $susenddate ? $data['susenddate'] = $susenddate : "";
            $reportnu ? $data['reportnu'] = $reportnu : "";
            $sustype ? $data['sustype'] = $sustype : "";
            $bool = D("sus")->up($data,"traceid='{$traceid}'");
            if($bool){
                $this->success("修改成功");
            }else{
                $this->error("修改失败");
            }

        }
    }

    public function addReport(){
        $this->display();
    }
    public function doAdd(){
        $info = I("post.");
        $date = date("Ymd",time());
        $data['traceid'] = "sus".$info['customid'].$date;
        $data['customid'] = $info['customid'];
        $data['chargeamount'] = $info['chargeamount'];
        $data['chargenu'] = $info['chargenu'];
        $data['tradeamount'] = $info['tradeamount'];
        $data['tradenu'] = $info['tradenu'];
        $data['datadate'] = $info['tradetime'];
        $data['remarkf'] = $info['remarkf'];
        $data['remarks'] = $info['remarks'];
        $data['status'] = 1;
        $data['isdel'] = 0;
        $data['placeddate'] = $date;
        $data['placedtime'] = date("H:i:s",time());
        $data['checker'] = session("username");
        $data['agentname'] = $info['agentname'];
        $data['agentcountry'] = $info['agentcountry'];
        $data['agidtype'] = $info['agentidtype'];
        $data['agid'] = $info['agentid'];
        $data['tradeaddress'] = $info['tradeaddress'];
        $data['cubankno'] = $info['cusbankno'];
        $data['rivalname'] = $info['rivalname'];
        $data['rivalbankno'] = $info['rivalbankno'];
        $data['susaddress'] = $info['susaddress'];
        $data['suslevel'] = $info['suslevel'];
        $data['susbegindate'] = $info['susbegindate'];
        $data['susenddate'] = $info['susenddate'];
        $data['reportnu'] = $info['reportnu'];
        $data['sustype'] = $info['sustype'];
        $check = D("sus")->search($data['traceid']);
        if($check){
            $this->error("已存在,勿重复添加");
        }
        $bool = D("sus")->inData($data);
        if($bool){
            $this->success("新增成功");
        }else{
            $this->error("新增失败");
        }

    }

    public function searchCustomer(){
        $name = I("post.name");
        $where['namechinese'] = $name;
        $info = D("sus")->field("customid,personidtype,
                                   personid,namechinese,
                                   linktel,unitname")->where($where)->find();
        if($info){
            $info['code'] = 1;
            die(json_encode($info));
        }else{
            die(json_encode(array("code"=>0)));
        }
    }

    private function packData($k,$v,$customer){
        $this->info[$k]['namechinese'] = $customer['namechinese'];
        $this->info[$k]['personidtype'] = $customer['personidtype'];
        $this->info[$k]['personid'] = $customer['personid'];
        $this->info[$k]['linktel'] = $customer['linktel'];
        $this->info[$k]['unitname'] = $customer['unitname'];
        $this->info[$k]['countyid'] = "中国";
        $this->info[$k]['chargeamount'] = $v['chargeamount'];
        $this->info[$k]['chargenu'] = $v['chargenu'];
        $this->info[$k]['tradeamount'] = $v['tradeamount'];
        $this->info[$k]['tradenu'] = $v['tradenu'];
        $this->info[$k]['datadate'] = $v['datadate'];
        $this->info[$k]['remarkf'] = $v['remarkf'];
        $this->info[$k]['remarks'] = $v['remarks'];
        $this->info[$k]['traceid'] = $v['traceid'];
        $this->info[$k]['checker'] = $v['checker'];
        $this->info[$k]['date'] = $v['placeddate']." ".$v['placedtime'];
        $this->info[$k]['agentname'] = $v['agentname'];
        $this->info[$k]['agentcountry'] = $v['agentcountry'];
        $this->info[$k]['agentidtype'] = $v['agidtype'];
        $this->info[$k]['agentid'] = $v['agid'];
        $this->info[$k]['tradeaddress'] = $v['tradeaddress'];
        $this->info[$k]['cusbankno'] = $v['cubankno'];
        $this->info[$k]['rivalname'] = $v['rivalname'];
        $this->info[$k]['rivalbankno'] = $v['rivalbankno'];
        $this->info[$k]['susaddress'] = $v['susaddress'];
        $this->info[$k]['suslevel'] = $v['suslevel'];
        $this->info[$k]['susbegindate'] = $v['susbegindate'];
        $this->info[$k]['susenddate'] = $v['susenddate'];
        $this->info[$k]['reportnu'] = $v['reportnu'];
        $this->info[$k]['sustype'] = $v['sustype'];
        switch ($v['status']){
            case 0:
                $this->info[$k]['status'] = "未复审";
                break;
            case 1:
                $this->info[$k]['status'] = "符合";
                break;
            case 2:
                $this->info[$k]['status'] = "不符合";
                break;
            default:
                $this->info[$k]['status'] = "未复审";
        }

    }

}


