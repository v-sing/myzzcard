<?php
namespace Home\Controller;

use Think\Controller;
use Think\Model;
use Think\Page;

class CoinAccountController extends CommonController
{
    //建业币发行报表
    public function issue()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $customid = trim(I('get.customid', ''));
        $cuname  = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $pname  = trim(I('get.pname', '')); //商户名称
        $coinAccount = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ca.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ca.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ca.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['ca.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        //echo $this->panterid;
        if ($this->panterid != 'FFFFFFFF') {
            $where1['ca.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        $field = 'ca.*,c.cardno,c.cardfee,cu.namechinese cuname,cu.linktel mobile,p.namechinese pname';
        $count = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();
        $p = new \Think\Page($count, 15);
        $list = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('ca.placeddate desc,ca.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        //--------计算2016年发行额---------------
        $where_16 = $where;
        $where_16['ca.placeddate'] = array(array('egt', "20160101"), array('elt', "20161231"));
        $sum16 =  $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------
        //--------截止目前发行额---------------
        $where_curr = $where;
        unset($where_curr['ca.placeddate']);
        $sum_curr =  $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where_curr)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------

        session('issueCon', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        //查询通宝符合赠送时间
        $url = "https://www.fangzg.cn/posindex.php/tbSend/getCreatTime";
        $postdata = array("key" => md5(md5('getCreatTime')));
        $ehome_getorg = C("ehome_getorg");
        foreach ($list as $k => $v) {
            $get_einfo = $this->getQuery($ehome_getorg, array("mobile" => $v['mobile']));
            $postdata['cardno'] = $v['cardno'];
            $returndata = $this->getQuery($url, $postdata);
            $list[$k]['createtime'] = date("Y-m-d H:i:s", $returndata['createtime']);
            $list[$k]['orgname'] = $get_einfo['data']['firstorgname'];
            $list[$k]['logintime'] = $get_einfo['data']['firstlogintime'];
        }

        $page = $p->show();
        $sum16 = number_format($sum16['amount'], '2', '.', '');
        $sum_curr = number_format($sum_curr['amount'], '2', '.', '');

        $this->assign("sum16", $sum16);
        $this->assign("sum_curr", $sum_curr);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    //发行报表导出
    public function issue_excel()
    {
        ini_set("memory_limit", "-1");
        if (isset($_SESSION['issueCon'])) {
            $issueCon = session('issueCon');
            foreach ($issueCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinAccount = M('coin_account');
        $field = 'ca.*,c.cardno,c.cardfee,cu.namechinese cuname,p.namechinese pname,cu.linktel';



        $list = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('ca.placeddate desc')->select();




        //print_r($list);exit;
        $strlist = "会员姓名,手机号,卡号,发行金额,发行编号,发行机构,发行时间,卡类型";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($list as $key => $val) {
            $val['cuname'] = iconv("utf-8", "gbk", $val['cuname']);
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $val['sourceorder'] = iconv("utf-8", "gbk", $val['sourceorder']);
            $cardfee = $val['cardfee'] == 2 ? iconv("utf-8", "gbk", '电子卡') : iconv("utf-8", "gbk", '实体卡');
            $strlist .= $val['cuname'] . "," . $val['linktel'] . "," . $val['cardno'] . "\t," . floatval($val['rechargeamount']) . "," . $val['sourceorder'];
            $strlist .= "," . $val['pname'] . "," . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
            $strlist .= "," . $cardfee . "\n";
        }
        $filename = '建业币发行报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }

    //通宝消费报表
    public function consume()
    {
        $coinConsume = M('coin_consume');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $customid = trim(I('get.customid', ''));
        $cuname  = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $issuepname  = trim(I('get.issuepname', '')); //发行商户名称
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称
        $status = trim(I('get.status', ''));
        $num = trim(I('get.num', ''));
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if ($status != '' && $status != '-1') {
            $where['cc.status'] = $status;
            $this->assign('status', $status);
            $map['status'] = $status;
        }
        $this->assign('status', $status);
        if ($this->panterid != 'FFFFFFFF') {
            $where1['cc.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        if (!empty($num)) {
            $map['num'] = $num;
            $this->assign('num', $num);
        }
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.status,cc.flag,c.cardno,c.cardfee,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,a.remindamount remindamount,tw.termposno,cc.pantercheck,tw.eorderid';

        $subQuery = "(select sum(remindamount) remindamount,cardid from coin_account group by cardid)";
        $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join($subQuery . ' a on a.cardid=c.customid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)->count();
        $sum = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->where($where)->sum('cc.amount');
        $num = !empty($num) ? $num : 30;
        $p = new \Think\Page($count, $num);
        //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join($subQuery . ' a on a.cardid=c.customid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
            ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();
        //-------计算2016年兑换数量---------
        //$where16 = $where;
        $where16['cc.placeddate'] = array(array('egt', "20160101"), array('elt', "20161231"));
        $sum16 = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->where($where16)->sum('cc.amount');
        $sum16 = number_format($sum16, '2', '.', '');
        //-------end--------------
        session('consumeCon', $where);
        $c = 0;
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) $c++;
        }
        if ($c == 0) $disabled = 1;
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign("sum16", $sum16);
        $this->assign('page', $page);
        $this->assign('disabled', $disabled);
        $this->assign('list', $list);
        $sum = number_format($sum, '2', '.', '');
        $this->assign('sum', $sum);
        $this->assign('count', $count);
        $this->display();
    }

    //通宝消费报表导出
    public function consume_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['consumeCon'])) {
            $consumeCon = session('consumeCon');
            foreach ($consumeCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinConsume = M('coin_consume');
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.flag,cc.status,cc.pantercheck,c.cardno,c.cardfee,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,a.remindamount remindamount,tw.termposno,tw.eorderid';

        $subQuery = "(select sum(remindamount) remindamount,cardid from coin_account group by cardid)";
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join($subQuery . ' a on a.cardid=c.customid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
            ->order('cc.placeddate desc')->where($where)->select();
        //$strlist="会员姓名,卡号,消费金额,余额,订单编号,受理机构,受理时间,发行机构,发行时间,订单状态,状态";
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        $cellMerge = "A1:N3";
        $titleName = '通宝兑换报表';
        $this->setTitle($cellMerge, $titleName);
        $startCell = 'A4';
        $endCell = 'N4';
        $headerArray = array(
            '会员姓名', '卡号', '消费金额', '余额', '订单编号', '一家订单号', '终端号', '受理机构',
            '受理时间', '发行机构', '发行时间', '订单状态', '项目审核', '状态', '卡类型'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L');
        $setWidth = array(12, 25, 12, 12, 25, 40, 25, 30, 25, 12, 12);
        $this->setWidth($setCells, $setWidth);
        $total = 0;
        $j = 5;
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) {
                $status = '未结算';
            } elseif ($val['status'] == 1) {
                $status = '已结算';
            }
            if ($val['flag'] == 1) {
                $flag = '通宝消费';
            } elseif ($val['flag'] == 2) {
                $flag = '消费撤销';
            } elseif ($val['flag'] == 3) {
                $flag = '退款';
            }
            if ($val['pantercheck'] == '1') {
                $pantercheck = '已审核';
            } else {
                $pantercheck = '未审核';
            }
            $total += $val['amount'];
            $cardfee = $val['cardfee'] == 2 ? '电子卡' : '实体卡';
            //$val['cuname'] = iconv("utf-8","gbk",$val['cuname']);
            //$val['consumepname'] = iconv("utf-8","gbk",$val['consumepname']);
            //$val['issuepname'] = iconv("utf-8","gbk",$val['issuepname']);
            //$flag=iconv("utf-8","gbk",$flag);
            //$status=iconv("utf-8","gbk",$status);
            //$objSheet->setCellValueExplicit("B".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cuname'])->setCellValue('B' . $j, "'" . $val['cardno'])
                ->setCellValue('C' . $j, $val['amount'])->setCellValue('D' . $j, $val['remindamount'])
                ->setCellValue('E' . $j, "'" . $val['tradeid'])->setCellValue('F' . $j, "'" . $val['eorderid'])
                ->setCellValue('G' . $j, "'" . $val['termposno'])
                ->setCellValue('H' . $j, "'" . $val['consumepname'])
                ->setCellValue('I' . $j, "'" . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime'])))
                ->setCellValue('J' . $j, "'" . $val['issuepname'])->setCellValue('K' . $j, "'" . date('Y-m-d H:i:s', strtotime($val['issuedate'] . $val['issuetime'])))
                ->setCellValue('L' . $j, "'" . $flag)->setCellValue('M' . $j, $pantercheck)->setCellValue('N' . $j, $status)->setCellValue('O' . $j, $cardfee);
            $j++;
        }
        $objSheet->getStyle('B' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('B' . $j, '合计金额:');
        $objSheet->setCellValue('C' . $j, $total);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '通宝兑换报表.xls'); //输出到浏览器
        $objWriter->save("php://output");
    }

    //通宝结算执行
    /* @add 结算同时减少商户账户备付金余额
     * @auth szj
     * @modifytime 2016年5月27日 */
    public function calculateDo()
    {
        $model = new Model();
        $consumeId = $_POST['consumeid'];
        $c = 0;
        $caculateAmount = 0;
        if (!empty($consumeId)) {
            foreach ($consumeId as $key => $val) {
                $consumeId = $val;
                $map = array('cc.coinconsumeid' => $val);
                $consumeInfo = M('coin_consume')->alias('cc')->join('coin_account ca on ca.coinid=cc.coinid')
                    ->where($map)->field('cc.*,ca.panterid issuepanterid')->find();
                if ($consumeInfo == false) continue;
                //查询商户账户备付金余额
                $provisionAmount = M('panteraccount')->alias('a')
                    ->join('panters b on a.panterid=b.panterid')
                    ->where("a.panterid={$consumeInfo['issuepanterid']} and a.type=1 and a.status=1")
                    ->field("a.amount,b.namechinese as pantername,a.accountid")
                    ->find();
                if (empty($provisionAmount)) {
                    $error = "结算失败,商户:{$consumeInfo['issuepanterid']}账户异常";
                    $this->recordErr($error);
                    continue;
                    //$this->error("结算失败,商户:{$consumeInfo['issuepanterid']}账户异常");
                }
                if (empty($provisionAmount['amount']) || $provisionAmount['amount'] < $consumeInfo['amount']) {
                    $error = "结算失败，商户:{$consumeInfo['issuepanterid']}余额不足";
                    $this->recordErr($error);
                    continue;
                    //$this->error("结算失败，商户:{$consumeInfo['issuepanterid']}余额不足");
                }
                //------end---------

                $model->startTrans();
                $currendDate = date('Ymd');
                //扣除商户账户备付金余额
                $panteraccountSql = "UPDATE panteraccount SET
                                 amount=amount-{$consumeInfo['amount']}
                                 WHERE panterid='{$consumeInfo['issuepanterid']}' AND type=1";
                $panteraccountIf = $model->execute($panteraccountSql);
                $placeddate = date('Ymd', time());
                $placedtime = date('His', time());

                $before_balance = $provisionAmount['amount'];
                $after_balance = bcsub($provisionAmount['amount'], $consumeInfo['amount'], 2);
                $panteroperatSql = "INSERT INTO panter_account_operat
                                   (panterid,amount,cate,accountid,placeddate,placedtime,userid,before_balance,after_balance)
                                    VALUES ('{$consumeInfo['issuepanterid']}',
                                           {$consumeInfo['amount']},3,{$provisionAmount['accountid']},
                                           '{$placeddate}','{$placedtime}','{$_SESSION['userid']}','{$before_balance}','{$after_balance}')";
                $panteroperatIf = $model->execute($panteroperatSql);
                //-------end----------
                $map1 = array('consumepanterid' => $consumeInfo['panterid'], 'issuepanterid' => $consumeInfo['issuepanterid'], 'placeddate' => $currendDate);
                $caculate = M('coin_calculate')->where($map1)->find();
                if ($caculate == false) {
                    $sql = "INSERT INTO coin_calculate values('{$consumeInfo['panterid']}','{$consumeInfo['issuepanterid']}','{$consumeInfo['amount']}','{$currendDate}')";
                } else {
                    $sql = "UPDATE coin_calculate SET amount=nvl(amount,0)+{$consumeInfo['amount']} WHERE ";
                    $sql .= "consumepanterid='{$consumeInfo['panterid']}' and issuepanterid='{$consumeInfo['issuepanterid']}' and placeddate='{$currendDate}'";
                }
                $calculateIf = $model->execute($sql);
                $map2 = array('coinconsumeid' => $val);
                $data2 = array('status' => 1);
                $consumeIf = M('coin_consume')->where($map2)->save($data2);
                if ($calculateIf == true && $consumeIf == true && $panteraccountIf == true && $panteroperatIf == true) {
                    $model->commit();
                    $c++;
                    $caculateAmount += $consumeInfo['amount'];
                } else {
                    $model->rollback();
                }
            }
            if ($c > 0) {
                $this->success('结算成功' . $c . '条,结算金额' . $caculateAmount);
            } else {
                $this->error('结算失败');
            }
        } else {
            $this->error('未选取结算记录');
        }
    }

    //通宝未结算报表
    public function uncalculate()
    {
        $issuepname  = trim(I('get.issuepname', '')); //发行商户名称
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称
        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = " p.panterid='{$this->panterid}' OR p.parent='{$this->panterid}' OR p1.panterid='{$this->panterid}' OR p1.parent='{$this->panterid}'";
        }
        $model = new Model();
        $subQuery = "(select sum(amount) amount,cc.panterid consumepanterid,ca.panterid issuepanterid from coin_consume cc ";
        $subQuery .= "inner join coin_account ca on cc.coinid=ca.coinid where cc.status=0 group by cc.panterid,ca.panterid)";
        $field = 'a.*,p.namechinese consumepname,p1.namechinese issuepname';
        $count = $model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)->where($where)->count();
        $p = new \Think\Page($count, 15);
        $list = $model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        //echo $model->getLastSql();
        session('uncalculateCon', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    //通宝未结算报表导出
    public function uncalculate_excel()
    {
        if (isset($_SESSION['uncalculateCon'])) {
            $uncalculateCon = session('uncalculateCon');
            foreach ($uncalculateCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinConsume = M('coin_consume');
        $model = new Model();
        $subQuery = "(select sum(amount) amount,cc.panterid consumepanterid,ca.panterid issuepanterid from coin_consume cc ";
        $subQuery .= "inner join coin_account ca on cc.coinid=ca.coinid where cc.status=0 group by cc.panterid,ca.panterid)";
        $field = 'a.*,p.namechinese consumepname,p1.namechinese issuepname';
        $list = $model->table($subQuery)->alias('a')->join('panters p on a.consumepanterid=p.panterid')
            ->join('panters p1 on p1.panterid=a.issuepanterid')->field($field)
            ->where($where)->select();
        $strlist = "受理机构,发行机构,受理金额";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($list as $key => $val) {
            $val['consumepname'] = iconv("utf-8", "gbk", $val['consumepname']);
            $val['issuepname'] = iconv("utf-8", "gbk", $val['issuepname']);
            $strlist .= $val['consumepname'] . "," . $val['issuepname'] . "\t," . floatval($val['amount']) . "\n";
        }
        $filename = '未受理报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }

    //通宝已结算报表
    public function calculated()
    {
        $coinCaculate = M('coin_calculate');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $issuepname  = trim(I('get.issuepname', '')); //发行商户名称
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称
        $status = trim(I('get.status', ''));
        $coinAccount = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = " p.panterid='{$this->panterid}' OR p.parent='{$this->panterid}' OR p1.panterid='{$this->panterid}' OR p1.parent='{$this->panterid}'";
        }
        $field = 'cc.*,p.namechinese consumepname,p1.namechinese issuepname';
        $count = $coinCaculate->alias('cc')->join('panters p on p.panterid=cc.consumepanterid')
            ->join('panters p1 on p1.panterid=cc.issuepanterid')->field($field)->where($where)->count();
        $p = new \Think\Page($count, 15);
        $list = $coinCaculate->alias('cc')->join('panters p on p.panterid=cc.consumepanterid')
            ->join('panters p1 on p1.panterid=cc.issuepanterid')
            ->field($field)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        session('calculatedCon', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    //通宝已结算报表导出
    public function calculated_excel()
    {
        if (isset($_SESSION['calculatedCon'])) {
            $calculatedCon = session('calculatedCon');
            foreach ($calculatedCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinCaculate = M('coin_calculate');
        $field = 'cc.*,p.namechinese consumepname,p1.namechinese issuepname';
        $list = $coinCaculate->alias('cc')->join('panters p on p.panterid=cc.consumepanterid')
            ->join('panters p1 on p1.panterid=cc.issuepanterid')
            ->field($field)->select();
        $strlist = "受理机构,发行机构,受理金额,结算时间";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($list as $key => $val) {
            $val['consumepname'] = iconv("utf-8", "gbk", $val['consumepname']);
            $val['issuepname'] = iconv("utf-8", "gbk", $val['issuepname']);
            $strlist .= $val['consumepname'] . "," . $val['issuepname'] . "\t," . floatval($val['amount']) . ",";
            $strlist .= date('Y-m-d', strtotime($val['placeddate'])) . "\n";
        }
        $filename = '已受理报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }

    //通宝赠送方核销
    public function issuePanters()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $issuepname  = trim(I('get.issuepname', '')); //发行商户名称
        $subfix = '';
        if ($start != '' && $end == '') {
            $subfix = ' where ';
            $startdate = str_replace('-', '', $start);
            $subCondition = "  ca.placeddate>='{$startdate}' ";
            $subCondition1 = " cc.placeddate>='{$startdate}' ";
            $dateString = $start . '后';
            $this->assign('dateString', $dateString);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $subfix = ' where ';
            $enddate = str_replace('-', '', $end);
            $subCondition = " ca.placeddate<='{$enddate}' ";
            $subCondition1 = "  cc.placeddate<='{$enddate}' ";
            $dateString = $end . '前';
            $this->assign('dateString', $dateString);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $subfix = ' where ';
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subCondition = "  ca.placeddate>='{$startdate}' and ca.placeddate<='{$enddate}' ";
            $subCondition1 = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $dateString = $start . '——' . $end;
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $this->assign('dateString', $dateString);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($issuepname != '') {
            $where['p.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $this->assign('is_admin', 0);
            $subfix = ' where ';
            $subCondition .= empty($subCondition) ? " (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')" : " and (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')";
        } else {
            $this->assign('is_admin', 1);
        }
        if (!empty($subfix)) {
            $subCondition = $subfix . $subCondition;
        }
        /*$subQuery="(select sum(rechargeamount) totalamount,panterid from coin_account";
        $subQuery.=$subCondition." group by panterid)";*/
        $subQuery = "(select sum(ca.rechargeamount) totalamount,ca.panterid,p.parent from coin_account ca inner join panters p on p.panterid=ca.panterid";
        $subQuery .= $subCondition . " group by ca.panterid,p.parent)";
        $model = new Model();
        $count = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->count();
        $p = new \Think\Page($count, 15);
        $issueList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        // echo M()->getLastSql();exit;
        session('issuePantersCon', array('subCondition' => $subCondition, 'where' => $where));
        foreach ($issueList as $key => $val) {
            $where1['ca.panterid'] = $val['panterid'];
            $field = 'sum(amount) consumeamount';
            $consume = $model->table('coin_account')->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
                ->where($where1)->field($field)->find();
            $issueList[$key]['consumeamount'] = !empty($consume['consumeamount']) ? $consume['consumeamount'] : 0;
            $issueList[$key]['remindamount'] = $val['totalamount'] - $issueList[$key]['consumeamount'];
        }
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $issueList);
        $this->display();
    }

    //发行方统计报表导出
    public function issuePanters_excel()
    {
        $issuePantersCon = $_SESSION['issuePantersCon'];
        if (isset($issuePantersCon['where'])) {
            $where = $issuePantersCon['where'];
        }
        $subCondition = $issuePantersCon['subCondition'];
        $subQuery = "(select sum(rechargeamount) totalamount,panterid from coin_account";
        $subQuery .= $subCondition . " group by panterid)";
        $model = new Model();
        $issueList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->select();
        $strlist = "发行机构,发行总金额,已受理金额";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($issueList as $key => $val) {
            $where1['ca.panterid'] = $val['panterid'];
            $field = 'sum(amount) consumeamount';
            $consume = $model->table('coin_account')->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
                ->where($where1)->field($field)->find();
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $consumeamount = !empty($consume['consumeamount']) ? $consume['consumeamount'] : 0;
            $strlist .= $val['pname'] . "," . floatval($val['totalamount']) . "\t," . floatval($consumeamount) . "\n";
        }
        $filename = '发行机构报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }
    //通宝受理方统计
    public function consumePanters()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $conpname  = trim(I('get.conpname', '')); //发行商户名称
        if ($start != '' && $end == '') {
            $subfix = " where ";
            $startdate = str_replace('-', '', $start);
            $subCondition = "  cc.placeddate>='{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $subfix = " where ";
            $enddate = str_replace('-', '', $end);
            $subCondition = "  cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $subfix = " where ";
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subCondition = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        //        if($start=='' && $end==''){
        //            $start=date('Y-m-01', strtotime('-1 month'));
        //            $end=date('Y-m-t', strtotime('-1 month'));
        //            $startdate = str_replace('-','',$start);
        //            $enddate = str_replace('-','',$end);
        //            $subCondition= " where placeddate>='{$startdate}' and placeddate<='{$enddate}' ";
        //            $this->assign('startdate',$start);
        //            $this->assign('enddate',$end);
        //            $map['startdate']=$start;
        //            $map['enddate']=$end;
        //        }
        if ($conpname != '') {
            $where['p.namechinese'] = array('like', '%' . $conpname . '%');
            $this->assign('conpname', $conpname);
            $map['conpname'] = $conpname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $this->assign('is_admin', 0);
            $subfix = ' where ';
            $subCondition .= empty($subCondition) ? " (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')" : " and (p.panterid='{$this->panterid}' or p.parent='{$this->panterid}')";
        } else {
            $this->assign('is_admin', 1);
        }
        if (!empty($subfix)) {
            $subCondition = $subfix . $subCondition;
        }
        $subQuery = "(select sum(cc.amount) totalamount,cc.panterid,p.parent from coin_consume cc inner join panters p on p.panterid=cc.panterid";
        $subQuery .= $subCondition . " group by cc.panterid,p.parent)";
        //echo $subQuery;
        $model = new Model();
        $count = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->count();
        $p = new \Think\Page($count, 15);
        $consumeList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        session('conPantersCon', array('subCondition' => $subCondition, 'where' => $where));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $consumeList);
        $this->display();
    }
    //通宝受理方统计
    public function consumePanters_excel()
    {
        $conPantersCon = $_SESSION['conPantersCon'];
        if (isset($conPantersCon['where'])) {
            $where = $conPantersCon['where'];
        }
        $subCondition = $conPantersCon['subCondition'];
        $subQuery = "(select sum(cc.amount) totalamount,cc.panterid,p.parent from coin_consume cc inner join panters p on p.panterid=cc.panterid";
        $subQuery .= $subCondition . " group by cc.panterid,p.parent)";
        $model = new Model();
        $issueList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->select();
        $strlist = "受理机构,受理总金额";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($issueList as $key => $val) {
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $strlist .= $val['pname'] . "," . floatval($val['totalamount']) . "\t," . "\n";
        }
        $filename = '受理机构报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }
    //通宝发行方受理机构统计
    public function issueConsume()
    {
        $isspanterid = $_REQUEST['isspanterid'];
        if (empty($isspanterid)) {
            $this->error('发行机构缺失');
        }
        $map['isspanterid'] = $isspanterid;
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称
        $coinAccount = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subCondition = " and ca.placeddate>='{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subCondition = " and ca.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subCondition = " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        //        if($start=='' && $end==''){
        //            $start=date('Y-m-01', strtotime('-1 month'));
        //            $end=date('Y-m-t', strtotime('-1 month'));
        //            $startdate = str_replace('-','',$start);
        //            $enddate = str_replace('-','',$end);
        //            $subCondition= " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
        //            $this->assign('startdate',$start);
        //            $this->assign('enddate',$end);
        //            $map['startdate']=$start;
        //            $map['enddate']=$end;
        //        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        $subQuery = "(select sum(cc.amount) consumeamount,cc.panterid pid from coin_account ca inner join coin_consume cc on cc.coinid=ca.coinid";
        $subQuery .= " where ca.panterid='{$isspanterid}' " . $subCondition . " group by cc.panterid)";
        $subQuery1 = "(select sum(cc.amount) calamount,cc.panterid from  coin_account ca inner join coin_consume cc on cc.coinid=ca.coinid  ";
        $subQuery1 .= "where ca.panterid='{$isspanterid}' and  cc.status=1 " . $subCondition . " group by cc.panterid)";
        $model = new Model();
        $field = "a.consumeamount,a.pid,p.namechinese pname,b.calamount,p.settlebankname,p.settlebankid";
        $count = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->count();
        $p = new \Think\Page($count, 15);
        $consumeList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)->select();
        $where1 = array('panterid' => $isspanterid);
        $panter = $model->table('panters')->field('namechinese pname')->where($where1)->find();
        session('issueconsumeCon', array('subCondition' => $subCondition, 'where' => $where, 'isspanterid' => $isspanterid, 'pname' => $panter['pname']));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $consumeList);
        $this->assign('panter', $panter);
        $this->assign('isspanterid', $isspanterid);
        $this->display();
    }

    //通宝发行方受理机构统计报表导出
    public function issueConsume_excel()
    {
        $issueconsumeCon = $_SESSION['issueconsumeCon'];
        if (isset($issueconsumeCon['where'])) {
            $where = $issueconsumeCon['where'];
        }
        $subCondition = $issueconsumeCon['subCondition'];
        $isspanterid = $issueconsumeCon['isspanterid'];
        $pname = $issueconsumeCon['pname'];
        $subQuery = "(select sum(cc.amount) consumeamount,cc.panterid pid from coin_account ca inner join coin_consume cc on cc.coinid=ca.coinid";
        $subQuery .= " where ca.panterid='{$isspanterid}' " . $subCondition . " group by cc.panterid)";
        $subQuery1 = "(select sum(cc.amount) calamount,cc.panterid from  coin_account ca inner join coin_consume cc on cc.coinid=ca.coinid  ";
        $subQuery1 .= "where ca.panterid='{$isspanterid}' and  cc.status=1 " . $subCondition . " group by cc.panterid)";
        $model = new Model();
        $field = "a.consumeamount,a.pid,p.namechinese pname,b.calamount,p.settlebankname,p.settlebankid";
        $consumeList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->select();
        $strlist = "受理机构,受理总金额,已结算金额,银行支行,银行卡号";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($consumeList as $key => $val) {
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $val['settlebankname'] = iconv("utf-8", "gbk", $val['settlebankname']);
            $strlist .= $val['pname'] . "," . floatval($val['consumeamount']) . "," . floatval($val['calamount']);
            $strlist .= "," . $val['settlebankname'] . "," . $val['settlebankid'] . "\t," . "\n";
        }
        $filename = $pname . '发行通宝的受理报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }
    //通宝受理方发行机构统计
    public function consumeIssue()
    {
        $conpanterid = $_REQUEST['conpanterid'];
        if (empty($conpanterid)) {
            $this->error('受理机构缺失');
        }
        $map['conpanterid'] = $conpanterid;
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $isspname  = trim(I('get.isspname', '')); //受理商户名称
        $coinAccount = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subCondition = " and cc.placeddate>='{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subCondition = " and cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subCondition = " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        //        if($start=='' && $end==''){
        //            $start=date('Y-m-01', strtotime('-1 month'));
        //            $end=date('Y-m-t', strtotime('-1 month'));
        //            $startdate = str_replace('-','',$start);
        //            $enddate = str_replace('-','',$end);
        //            $subCondition= " and (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
        //            $this->assign('startdate',$start);
        //            $this->assign('enddate',$end);
        //            $map['startdate']=$start;
        //            $map['enddate']=$end;
        //        }
        if ($isspname != '') {
            $where['p.namechinese'] = array('like', '%' . $isspname . '%');
            $this->assign('isspname', $isspname);
            $map['isspname'] = $isspname;
        }
        $subQuery = "(select sum(cc.amount) consumeamount,ca.panterid pid from coin_consume cc inner join coin_account ca on cc.coinid=ca.coinid";
        $subQuery .= " where cc.panterid='{$conpanterid}' " . $subCondition . " group by ca.panterid)";
        $subQuery1 = "(select sum(cc.amount) calamount,ca.panterid from  coin_consume cc inner join coin_account ca on cc.coinid=ca.coinid";
        $subQuery1 .= " where cc.panterid='{$conpanterid}' and  cc.status=1 " . $subCondition . " group by ca.panterid)";
        $model = new Model();
        $field = "a.consumeamount,a.pid,p.namechinese pname,b.calamount";
        $count = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->count();
        $p = new \Think\Page($count, 15);
        $consumeList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)->select();
        //echo $model->getLastSql();
        $where1 = array('panterid' => $conpanterid);
        $panter = $model->table('panters')->field('namechinese pname')->where($where1)->find();
        session('consumeissueCon', array('subCondition' => $subCondition, 'where' => $where, 'conpanterid' => $conpanterid, 'pname' => $panter['pname']));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $consumeList);
        $this->assign('panter', $panter);
        $this->assign('conpanterid', $conpanterid);
        $this->display();
    }
    public function getPanterInfo()
    {
        $panterid = $_REQUEST['panterid'];
        if (empty($panterid)) {
            $this->error('商户编号缺失');
        }
        $where = array('panterid' => $panterid);
        $panter = D('panters')->field('conpername,conperteleno,address,namechinese')->where($where)->find();
        $this->assign('panter', $panter);
        $this->display();
    }

    //通宝受理方发行机构统计
    public function consumeIssue_excel()
    {
        $consumeissueCon = $_SESSION['consumeissueCon'];
        if (isset($issueconsumeCon['where'])) {
            $where = $issueconsumeCon['where'];
        }
        $subCondition = $consumeissueCon['subCondition'];
        $conpanterid = $consumeissueCon['conpanterid'];
        $pname = $consumeissueCon['pname'];
        $subQuery = "(select sum(cc.amount) consumeamount,ca.panterid pid from coin_consume cc inner join coin_account ca on cc.coinid=ca.coinid";
        $subQuery .= " where cc.panterid='{$conpanterid}' " . $subCondition . " group by ca.panterid)";
        $subQuery1 = "(select sum(cc.amount) calamount,ca.panterid from  coin_consume cc inner join coin_account ca on cc.coinid=ca.coinid";
        $subQuery1 .= " where cc.panterid='{$conpanterid}' and  cc.status=1 " . $subCondition . " group by ca.panterid)";
        $model = new Model();
        $field = "a.consumeamount,a.pid,p.namechinese pname,b.calamount";
        $consumeList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.pid')
            ->join('left join' . $subQuery1 . ' b on b.panterid=a.pid')
            ->where($where)->field($field)->select();
        //echo $model->getLastSql();exit;
        $strlist = "发行机构,受理金额,已结算金额";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($consumeList as $key => $val) {
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $strlist .= $val['pname'] . "," . floatval($val['consumeamount']) . "," . floatval($val['calamount']) . "," . "\n";
        }
        $filename = $pname . '受理通宝的发行报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }

    //三级消费明细报表
    public function issueConsumeDetail()
    {
        $isspanterid = $_REQUEST['isspanterid'];
        $conpanterid = $_REQUEST['conpanterid'];
        if (empty($isspanterid)) {
            $this->error('发行机构缺失');
        }
        if (empty($conpanterid)) {
            $this->error('受理机构缺失');
        }
        $where['ca.panterid'] = $isspanterid;
        $where['cc.panterid'] = $conpanterid;
        $map['isspanterid'] = $isspanterid;
        $map['conpanterid'] = $conpanterid;
        $this->assign('isspanterid', $isspanterid);
        $this->assign('conpanterid', $conpanterid);

        $coinConsume = M('coin_consume');
        $panters = M('panters');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $customid = trim(I('get.customid', ''));
        $cuname  = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $status = trim(I('get.status', ''));
        $coinAccount = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        //        if($start==''&&$end==''){
        //            $start=date('Y-m-01', strtotime('-1 month'));
        //            $end=date('Y-m-t', strtotime('-1 month'));
        //            $where['cc.placeddate']=array(array('egt',str_replace('-','',$start)),array('elt',str_replace('-','',$end)));
        //            $this->assign('startdate',$start);
        //            $this->assign('enddate',$end);
        //            $map['startdate']=$start;
        //            $map['enddate']=$end;
        //        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($status != '' && $status != '-1') {
            $where['cc.status'] = $status;
            $map['status'] = $status;
            $this->assign('status', $status);
        } else {
            $this->assign('status', '-1');
        }
        if ($this->panterid != 'FFFFFFFF') {
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.status,c.cardno,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,tw.termposno';
        $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')
            ->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')
            ->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)->count();
        $p = new \Think\Page($count, 15);
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)
            ->order('cc.placeddate desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        //echo $coinConsume->getLastSql();
        $where1 = array('panterid' => $isspanterid);
        $where2 = array('panterid' => $conpanterid);
        $panter1 = $panters->field('namechinese pname')->where($where1)->find();
        $panter2 = $panters->field('namechinese pname')->where($where2)->find();
        session('consumeDetailCon', array('where' => $where, 'pname1' => $panter1['pname'], 'pname2' => $panter2['pname']));
        $c = 0;
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) $c++;
        }
        if ($c == 0) $disabled = 1;
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('panter1', $panter1);
        $this->assign('panter2', $panter2);
        $this->assign('disabled', $disabled);
        $this->assign('list', $list);
        $this->display();
    }

    //三级消费明细报表导出
    public function issueConsumeDetail_excel()
    {
        $consumeDetailCon = $_SESSION['consumeDetailCon'];
        if (isset($consumeDetailCon['where'])) {
            $where = $consumeDetailCon['where'];
        }
        $pname1 = $consumeDetailCon['pname1'];
        $pname2 = $consumeDetailCon['pname2'];
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.flag,cc.status,c.cardno,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,tw.tradeid,tw.termposno';
        $coinConsume = M('coin_consume');
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)
            ->order('cc.placeddate desc')->select();
        $strlist = "会员姓名,卡号,消费金额,订单编号,受理机构,受理时间,发行机构,发行时间,状态";
        $strlist = $this->changeCode($strlist);
        $strlist .= "\n";
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) {
                $status = '未结算';
            } elseif ($val['status'] == 1) {
                $status = '已结算';
            }
            if ($val['flag'] == 1) {
                $flag = '通宝消费';
            } elseif ($val['flag'] == 2) {
                $flag = '消费撤销';
            } elseif ($val['flag'] == 3) {
                $flag = '退款';
            }
            $val['cuname'] = iconv("utf-8", "gbk", $val['cuname']);
            $val['consumepname'] = iconv("utf-8", "gbk", $val['consumepname']);
            $val['issuepname'] = iconv("utf-8", "gbk", $val['issuepname']);
            $flag = iconv("utf-8", "gbk", $flag);
            $status = iconv("utf-8", "gbk", $status);
            $strlist .= $val['cuname'] . "," . $val['cardno'] . "\t," . floatval($val['amount']) . "," . $val['tradeid'];
            $strlist .= "\t," . $val['consumepname'] . "," . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
            $strlist .= "," . $val['issuepname'] . "," . date('Y-m-d H:i:s', strtotime($val['issuedate'] . $val['issuetime']));
            $strlist .= "," . $flag . "," . $status . "\n";
        }
        $filename = '建业通宝受理明细报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
    }

    //发票报表
    function invoice()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $pname = trim(I('get.pname', ''));
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['i.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['i.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['i.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if (!empty($pname)) {
            $where['p1.namechinese'] = array('like', '%' . $pname . '%');
            $map['pname'] = $pname;
        }
        if ($this->panterid = 'FFFFFFFF') {
            $this->assign('is_admin', 1);
        } else {
            $this->assign('is_admin', 0);
        }
        $field = "i.*,p1.namechinese pname1,p2.namechinese pname2";
        $m = M('invoice');
        $count = $m->alias('i')->join('panters p1 on p1.panterid=i.panterid1')
            ->join('panters p2 on p2.panterid=i.panterid2')->field($field)->where($where)->count();
        $p = new \Think\Page($count, 15);
        $list = $m->alias('i')->join('left join  panters p1 on p1.panterid=i.panterid1')
            ->join('left join panters p2 on p2.panterid=i.panterid2')->field($field)
            ->where($where)->order('i.placeddate desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }
    //发票添加数据
    function invoiceAdd()
    {
        if (IS_POST) {
            $m = M();
            $pingzheng = trim($_POST['pingzheng']);
            $ruzhang = trim($_POST['ruzhang']);
            $amount = trim($_POST['amount']);
            $panterid1 = trim($_POST['panterid1']);
            $panterid2 = $this->panterid;
            $is_get = trim($_POST['is_get']);
            $content = trim($_POST['content']);
            $currentDate = date('Ymd');
            $sql = "insert into invoice(id,pingzheng,ruzhang,amount,panterid1,panterid2,is_get,content ,placeddate) ";
            $sql .= "values(SEQ_INVOICE.nextval,'{$pingzheng}','{$ruzhang}','{$amount}','{$panterid1}','{$panterid2}','{$is_get}','{$content}','{$currentDate}')";
            $r = $m->execute($sql);
            if ($r) {
                $this->success("添加成功", U('CoinAccount/invoice'));
            } else {
                $this->error("操作失败");
            }
        } else {
            if ($this->panterid != 'FFFFFFFF') {
                $subCondition = " where consumepanterid='{$this->panterid}'";
            }
            $subQuery = "(SELECT issuepanterid FROM coin_calculate " . $subCondition . " GROUP BY issuepanterid)";
            $model = new Model();
            $panter = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.issuepanterid')
                ->field('p.panterid pid,p.namechinese pname')->select();
            //echo M('coin_calculate')->getLastSql();
            $this->assign('panter', $panter);
            $this->display();
        }
    }
    //----------byszj--------------
    //根据卡号查询卡下通宝发行，消费，余额
    public function carddetail()
    {
        $this->display();
    }

    public function carddetailshow()
    {
        $cardno = trim(I('get.cardno', ''));
        $type   = trim(I('get.type', ''));
        empty($cardno) && $this->error('请输入卡号');
        empty($type)   && $this->error('请选择类型');
        $cardinfo = M('cards')->field('customid')->where(array('cardno' => $cardno))->find();
        $cardinfo || $this->error('没有此卡号信息!');
        $rechargelist = array();
        $consumerlist = array();
        $condition['cardid'] = $cardinfo['customid'];
        if ($type == 1) {
            $rechargeinfo = M('coin_account')->field('rechargeamount,placeddate,placedtime,panterid')
                ->where($condition)
                ->order('placeddate')
                ->select();
            $rechargeinfo || $this->error('此卡没有通宝充值记录');
            foreach ($rechargeinfo as $k => $v) {
                $rechargelist[$k]   =   $v;
                $rechargelist[$k]['type']  =  "发行";
                $rechargelist[$k]['amount'] = $v['rechargeamount'];
                $rechargelist[$k]['placeddate']   =  date('Y-m-d', strtotime($v['placeddate']));
                $re = M('panters')->field('namechinese')->where(array('panterid' => $v['panterid']))->find();
                $rechargelist[$k]['pantername'] = $re['namechinese'];
            }
            $list = $rechargelist;
        } else {
            $count = M('coin_consume')->where($condition)->count();
            $p = new \Think\Page($count, 20);
            $consumerinfo = M('coin_consume')->field('amount,placeddate,placedtime,panterid')
                ->where($condition)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->order('placeddate')
                ->select();
            $consumerinfo || $this->error('没有消费记录');
            foreach ($consumerinfo as $k => $v) {
                $consumerlist[$k]   =   $v;
                $consumerlist[$k]['placeddate']   =  date('Y-m-d', strtotime($v['placeddate']));
                $consumerlist[$k]['type']  =  $v['amount'] > 0 ? "消费" : "退费";
                $re = M('panters')->field('namechinese')->where(array('panterid' => $v['panterid']))->find();
                $consumerlist[$k]['pantername'] = $re['namechinese'];
            }
            $page = $p->show();
            $this->assign('page', $page);
            $list = $consumerlist;
        }
        $this->assign('list', $list);
        $this->assign('cardno', $cardno);
        $this->display('carddetail');
    }

    public function carddetail_excel()
    {
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        $excel = new \PHPExcel();
        $cardno = trim(I('get.cardno', ''));
        empty($cardno) && $this->error('请输入卡号');
        $cardinfo = M('cards')->field('customid')->where(array('cardno' => $cardno))->find();
        $cardinfo || $this->error('没有此卡号信息!');
        $condition['cardid'] = $cardinfo['customid'];
        $rechargeinfo = M('coin_account')->field('rechargeamount,placeddate,placedtime,panterid')
            ->where($condition)
            ->order('placeddate')
            ->select();
        $rechargeinfo || $this->error('此卡没有通宝充值记录');
        $rechargelist = array();
        $consumerlist = array();
        $consumerinfo = M('coin_consume')->field('amount,placeddate,placedtime,panterid')
            ->where($condition)
            ->order('placeddate')
            ->select();
        foreach ($rechargeinfo as $k => $v) {
            $rechargelist[$k]   =   $v;
            $rechargelist[$k]['type']  =  "发行";
            $rechargelist[$k]['amount'] = $v['rechargeamount'];
            $rechargelist[$k]['placeddate']   =  date('Y-m-d', strtotime($v['placeddate']));
            $re = M('panters')->field('namechinese')->where(array('panterid' => $v['panterid']))->find();
            $rechargelist[$k]['pantername'] = $re['namechinese'];
        }
        $recharge_number = array_sum(array_column($rechargelist, "amount"));
        if (!empty($consumerinfo)) {
            foreach ($consumerinfo as $k => $v) {
                $consumerlist[$k]   =   $v;
                $consumerlist[$k]['placeddate']   =  date('Y-m-d', strtotime($v['placeddate']));
                $consumerlist[$k]['type']  =  $v['amount'] > 0 ? "消费" : "退费";
                $re = M('panters')->field('namechinese')->where(array('panterid' => $v['panterid']))->find();
                $consumerlist[$k]['pantername'] = $re['namechinese'];
            }
            $consumer_number = array_sum(array_column($consumerlist, "amount"));
        } else {
            $consumer_number = 0;
        }
        $result = array();
        $data = array_merge($rechargelist, $consumerlist);
        foreach ($data as $k => $v) {
            $result[$k]['cardno']  =  $cardno;
            $result[$k]['type']    = $v['type'];
            $result[$k]['pantername'] = $v['pantername'];
            $result[$k]['amount']  = $v['amount'];
            $result[$k]['placeddate'] = $v['placeddate'];
            $result[$k]['placedtime'] = $v['placedtime'];
        }
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $tableheader = array('卡号', '类型', '受理机构', '金额', '日期', '时间');
        for ($i = 0; $i < count($tableheader); $i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }
        for ($i = 2; $i <= count($result) + 1; $i++) {
            $j = 0;
            foreach ($result[$i - 2] as $key => $value) {
                if ($key == 0) {
                    $excel->getActiveSheet()->setCellValue("$letter[$j]$i", "$value" . "\t");
                    $j++;
                    continue;
                }
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i", "$value");
                $j++;
            }
        }

        $write = new \PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="carddetail.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }
    //-----------------end------------
    /* @modifytime 2016年6月27日10:36
     * @content 增加查询字段兑换方开户名
     * @author szj*/
    function qjs()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $issuepname  = trim(I('get.issuepname', '')); //受理商户名称
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称
        $proname = trim(I('get.proname', ''));
        $coinAccount = M('coin_account');
        //echo $proname;
        //通宝池过滤
        $tb_bool=M('tb_pool');
        $tbData =$tb_bool->select();
        $tbArray=[];

        foreach ($tbData as $k=>$v){
            $tbArray[]=$v['issue_company'];
        }
//      var_dump($tbArray);exit;
        //联盟商家替换
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames      = array_column($outPanters, 'namechinese');
        $outpanters    = array_column($outPanters, 'panterid');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subCondition = " where placeddate>='{$startdate}' ";
            $subCondition1 = " where cc.placeddate>='{$startdate}' ";
            $subCondition2 = "  cc.placeddate>='{$startdate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $sendDate = str_replace('-', '.', $start) . '-' . date('Y.m.d');
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subCondition = " where placeddate<='{$enddate}' ";
            $subCondition1 = " where cc.placeddate<='{$enddate}' ";
            $subCondition2 = "  cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $earlyDate = M('coin_account')->min('placeddate');
            $sendDate = date('Y-m-d', strtotime($earlyDate)) . str_replace('-', '.', $end);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subCondition = " where (placeddate>='{$startdate}' and placeddate<='{$enddate}' )";
            $subCondition1 = " where (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $subCondition2 = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $sendDate = str_replace('-', '.', $start) . '-' . str_replace('-', '.', $end);
        }
        if ($start == '' && $end == '') {
            $earlyDate = M('coin_account')->min('placeddate');
            $sendDate = date('Y.m.d', strtotime($earlyDate)) . '-' . date('Y.m.d');
        }

        // 判定开始时间
        if ($start !== '') {
            $sdate = str_replace('-', '', $start);
            session('qjs_sdate', $sdate);
        } else {
            $sdate = null;
        }

        // 判定最后时间
        if ($end !== '') {
            $date = str_replace('-', '', $end);
            session('qjs_date', $date);
        } else {
            $date = null;
        }
        $where['p.namechinese']=['not in',implode(',',$tbArray)];
        if ($issuepname != '') {
            $where['p.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
        }

        if ($consumepname != '') {
            if ($consumepname == '建业至尊（外拓商户简称）') {
                $where['p1.namechinese'] = ['in', $outnames];
            } else {
                $where['p1.namechinese'] = array('like', '%' . $consumepname . '%');
            }
            $this->assign('consumepname', $consumepname);

        }
        if (!empty($proname)) {
            $this->assign('proname', $proname);
            $data = array('pantername' => $proname, 'key' => md5('cxfang' . $proname));
            $url = C('fangzgIP') . '/posindex.php/search/getPanterid';
            $result = crul_post($url, $data);
            $result = json_decode($result, 1);
            if ($result['code'] == 5) {
                $panterArr = $result['msg'];
                foreach ($panterArr as $key => $val) {
                    if (!empty($val['parentid']) && $val['parentid'] != null) {
                        $list[] = $val['parentid'];
                    }
                }
                $where['p.panterid'] = array('in', $list);
            } else {
                $this->error('无该项目的发行记录');
            }
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = " p.panterid='{$this->panterid}' or p.parent='{$this->panterid}' or p1.panterid='{$this->panterid}' or p1.parent='{$this->panterid}'";
        }
        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} group by panterid) ";
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";

        $field = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";
        $model = new Model();

        if (empty($subCondition3)) {
            $count = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')->count();

            $p = new \Think\Page($count, 15);
            $list = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $p = isset($_GET['p']) ? $_GET['p'] : 1;
            $list0 = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->select();
            $list1 = array();
            $consumeArr = array();
            $issueArr = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[] = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1 = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid'] = array('not in', $issueArr);
                $list2 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('p.namechinese asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list3 = array_merge($list1, $list2);
            $s = ($p - 1) * 15;
            $e = $s + 15;
            $list = array();
            $count = count($list3);
            for ($i = $s; $i < $e; $i++) {
                if ($i > $count - 1) break;
                $list[] = $list3[$i];
            }
            $p = new \Think\Page($count, 15);
        }
        session('qjsCon', array(
            'subCondition' => $subCondition, 'subCondition2' => $subCondition2,
            'subCondition3' => $subCondition3, 'where' => $where, 'subCondition1' => $subCondition1, 'date' => $sendDate
        ));
        $fzgurl = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
            //            $payable =  $this->newProvisions($val['issuepid'],$date);
            $paydata =  $this->newProvisions($val['issuepid'], $date, $sdate);
            //var_dump($paydata);
            $payable = $paydata['val'];
            $str     = $paydata['msg'];
            $settleExpire  = $paydata['expire'];
            $settleExpire2  = $paydata['expire2'];
            $calculatedamount = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $list[$key]['payable'] = round($payable);
            $list[$key]['str']     = $str;
            $list[$key]['settleExpire'] = $settleExpire;
            $list[$key]['settleExpire2'] = $settleExpire2;
            // $list[$key]['settleExpire2'] =
            // if ($list[$key]['consumepid'] == '00004616') {
            //      $list[$key]['settleExpire2'] = $list[$key]['consumeamount'];
            //      var_dump($list[$key]);
            // }
            $k = $refer['key'];
            $list[$key]['issuepname'] = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan'] += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }

            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $list[$key]['pname1']            = '建业至尊（外拓商户简称）';
                $list[$key]['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $list[$key]['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $list[$key]['settlebankid'] = '76190154800003664';
            }
        }
        $page = $p->show();
        $this->assign('date', $sendDate);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    //计算赠送方所需补交备付金
    private function provisions($panterid)
    {
        $nowtime = date('Ymd', time()); //现在时间
        $subTime = $nowtime; //获取要查询的时间
        $endLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day')); //上个月最后一天
        $startLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 month')); //上个月第一天
        $model = new Model();
        $subCondition = "  where (placeddate<={$endLastMonth}) ";

        $subCondition .= " and panterid='{$panterid}'";


        $subQuery = "(SELECT sum(rechargeamount) totalamount,panterid from coin_account";
        $subQuery .= $subCondition . " group by panterid)";

        $issueList = $model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
            ->field('a.totalamount,p.namechinese pname,p.panterid')->find();

        $totalamount = !empty($issueList['totalamount']) ? $issueList['totalamount'] : 0; //截止上月末总计发行通宝
        $where1['ca.panterid'] = $issueList['panterid'];
        $where1['cc.placeddate'] = array('elt', $endLastMonth);
        $field = 'sum(amount) consumeamount';
        $consume = $model->table('coin_account')->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
            ->where($where1)->field($field)->find();
        $consumeamount = !empty($consume['consumeamount']) ? $consume['consumeamount'] : 0; //截止上月末总计消费通宝

        $lnconsumeWhere['ca.panterid']  = $issueList['panterid'];
        $lnconsumeWhere['cc.placeddate'] = [['egt', $startLastMonth], ['elt', $endLastMonth]];
        $lnconsume = $model->table('coin_account')
            ->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
            //            ->where("ca.panterid={$issueList['panterid']} and (cc.placeddate between {$startLastMonth} and {$endLastMonth})")
            ->where($lnconsumeWhere)
            ->field($field)->find();
        $lnconsumeamount = !empty($lnconsume['consumeamount']) ? $lnconsume['consumeamount'] : 0; //上月总计消费通宝


        //(panterid = {$issueList['panterid']} and type=1)
        $provisionsWhere['panterid'] = $issueList['panterid'];
        $provisionsWhere['type']     = '1';
        $provisions = $model->table('panteraccount')->where($provisionsWhere)->field('amount')->find(); //系统截止目前备付金余额
        $provisions = !empty($provisions['amount']) ? $provisions['amount'] : 0; //系统截止目前备付金余额

        //panterid = {$issueList['panterid']} and cate=0 and (placeddate={$nowtime})
        $inProvisionsWhere = ['panterid' => $issueList['panterid'], 'cate' => '0', 'placeddate' => $nowtime];
        $inProvisions = $model->table('panter_account_operat')
            ->where($inProvisionsWhere)
            ->field('sum(amount) amount')->find();
        $inProvisions = !empty($inProvisions['amount']) ? $inProvisions['amount'] : 0; //本月备付金入账

        //"panterid = {$issueList['panterid']} and cate=3 and (placeddate>={$nowtime})"
        $outProvisionsWhere = ['panterid' => $issueList['panterid'], 'cate' => '3', 'placeddate' => $nowtime];
        $outProvisions = $model->table('panter_account_operat')
            ->where($outProvisionsWhere)
            ->field('sum(amount) amount')->find();
        $outProvisions = !empty($outProvisions['amount']) ? $outProvisions['amount'] : 0; //本月备付金出账

        $lmProvisions = bcsub(bcadd($provisions, $outProvisions, 2), $inProvisions, 2); //截止上月末剩余备付金余额
        $payable = bcsub(bcadd(bcmul(bcsub($totalamount, $consumeamount, 2), 0.2, 2), $lnconsumeamount, 2), $lmProvisions, 2);
        return $payable;
    }

    function qjs_excel()
    {
        $qjsCon = $_SESSION['qjsCon'];
        if (isset($qjsCon['where'])) {
            $where = $qjsCon['where'];
        }
        $startTime = isset($_SESSION['qjs_sdate']) ? $_SESSION['qjs_sdate'] : null;
        $qjsdate = isset($_SESSION['qjs_date']) ? $_SESSION['qjs_date'] : null;
        $subCondition = $qjsCon['subCondition'];
        $subCondition1 = $qjsCon['subCondition1'];
        $subCondition2 = $qjsCon['subCondition2'];
        $subCondition3 = $qjsCon['subCondition3'];
        $date = $qjsCon['date'];
        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} group by panterid) ";
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";
        $model = new Model();
        $field = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";


        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames      = array_column($outPanters, 'namechinese');
        $outpanters    = array_column($outPanters, 'panterid');
        if (empty($subCondition3)) {
            $list = $model->table($subQuery)->alias('a')
                ->join(' full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')->select();
        } else {
            $list0 = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')
                ->select();
            $list1 = array();
            $consumeArr = array();
            $issueArr = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[] = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1 = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid'] = array('not in', $issueArr);
                $list2 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('a.panterid asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list = array_merge($list1, $list2);
        }
        $fzgurl = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
            $calculatedamount = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $k = $refer['key'];
            $list[$key]['issuepname'] = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan'] += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }
        }
        $strlist = "<tr><th>日期</th><th>发行机构</th><th>发行项目</th><th>需补缴备付金金额</th><th>发行金额</th><th>累计过期商户金额</th><th>当期过期金额</th><th>受理机构</th><th>受理金额</th>";
        $strlist .= "<th>手续费</th><th>已结算金额</th><th>开户行</th><th>开户名</th>银行卡号</th><th>备注</th></tr>";
        //        $strlist="<tr><th>日期</th><th>发行机构</th><th>发行项目</th><th>需补缴备付金金额</th><th>发行金额</th><th>过期商户金额</th><th>受理机构</th><th>受理金额</th>";
        //        $strlist.="<th>手续费</th><th>已结算金额</th></tr>";
        $strlist = $this->changeCode($strlist);
        $date = $this->changeCode($date);
        foreach ($list as $key => $val) {
            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $val['pname1']            = '建业至尊（外拓商户简称）';
                $val['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $val['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $val['settlebankid'] = '76190154800003664';
            }
            $paydata =  $this->newProvisions($val['issuepid'], $qjsdate, $startTime);
            $payable = round($paydata['val']);
            $settleExpire =  sprintf("%.2f", $paydata['expire']);
            $settleExpire2  = sprintf("%.2f", $paydata['expire2']);;
            $val['pname'] = iconv("utf-8", "gbk", $val['pname']);
            $val['pname1'] = iconv("utf-8", "gbk", $val['pname1']);
            $val['issuepname'] = iconv("utf-8", "gbk", $val['issuepname']);
            $val['settlebankname'] = iconv("utf-8", "gbk", $val['settlebankname']);
            $val['settleaccountname'] = iconv("utf-8", "gbk", $val['settleaccountname']);
            if ($val['rowspan'] != '-1') {
                if (!empty($val['rowspan'])) {
                    $rowspan = " rowspan='{$val['rowspan']}'";
                } else {
                    $rowspan = "";
                }
                $strlist .= "<tr><td {$rowspan}>{$date}</td><td {$rowspan}>{$val['pname']}</td><td {$rowspan}>{$val['issuepname']}</td><td {$rowspan}>{$payable}</td><td {$rowspan}>" . floatval($val['rechargeamount']) . "</td><td {$rowspan}>" . $settleExpire . "</td><td {$rowspan}>{$settleExpire2}</td>";
            }
            $strlist .= "<td>{$val['pname1']}</td><td>" . sprintf("%.2f", floatval($val['consumeamount'])) . "</td><td></td><td>" . sprintf("%.2f", floatval($val['calculatedamount'])) . "</td>";
            $strlist .= "<td>{$val['settlebankname']}</td><td>{$val['settleaccountname']}</td><td>{$val['settlebankid']}</td><td></td></tr>";
            //            $strlist.="<td></td></tr>";
        }
        $filename = '建业通宝发行受理清结算报表' . date('YmdHis') . '.xls';
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->export_xls($filename, $strlist);
    }

    //兑换明细审核
    function consumecheck()
    {
        $model = new Model();
        $consumeId = $_POST['consumeid'];
        $c = 0;
        $caculateAmount = 0;
        $coin_consume = M('coin_consume');
        if (!empty($consumeId)) {
            foreach ($consumeId as $key => $val) {
                $coin_consume->startTrans();
                $consumeId = $val;
                $map = array('coinconsumeid' => $consumeId);
                $data = array('pantercheck' => 1, 'checkid' => $this->userid, 'checkdate' => time());
                $consumeIf = $coin_consume->where($map)->save($data);
                //echo $coin_consume->getLastSql();exit;
                if ($consumeIf == true) {
                    $coin_consume->commit();
                    $c++;
                } else {
                    $coin_consume->rollback();
                }
            }
            if ($c > 0) {
                $this->success('审核成功' . $c . '条');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('未选取审核记录');
        }
    }

    //赠送明细审核
    function issuecheck()
    {
        $model = new Model();
        $consumeId = $_POST['accountid'];
        $c = 0;
        $caculateAmount = 0;
        $coin_account = M('coin_account');
        if (!empty($consumeId)) {
            foreach ($consumeId as $key => $val) {
                $coin_account->startTrans();
                $accountId = $val;
                $map = array('accountid' => $accountId);
                $data = array('pantercheck' => 1, 'checkid' => $this->userid, 'checkdate' => time());
                $accountIf = $coin_account->where($map)->save($data);
                //echo $coin_consume->getLastSql();exit;
                if ($accountIf == true) {
                    $coin_account->commit();
                    $c++;
                } else {
                    $coin_account->rollback();
                }
            }
            if ($c > 0) {
                $this->success('审核成功' . $c . '条');
            } else {
                $this->error('审核失败');
            }
        } else {
            $this->error('未选取审核记录');
        }
    }
    //-----2016年 获取房掌柜 项目信息
    public function tbcount()
    {
        $pan = M('panters');
        $coin_account = M('coin_account');
        $coin_consume = M('coin_consume');
        //默认显示所有法人公司
        $parent = trim(I('get.parent', ''));
        $start = trim(I('get.startdate'));
        $start = str_replace('-', '', $start);
        $end = trim(I('get.enddate'));
        $end = str_replace('-', '', $end);
        $panterid = trim(I('get.panterid', ''));
        if ($this->panterid == 'FFFFFFFF') {
            $parentlists = array(
                '00000284' => '南阳区域总公司', '00000227' => '郑州区域总公司', '00000323' => '洛阳区域总公司',
                '00000324' => '许昌区域总公司', '00000325' => '驻马店区域总公司', '00000326' => '开封区域总公司',
                '00000327' => '周口区域总公司', '00000328' => '新乡区域总公司', '00000329' => '安阳区域总公司',
                '00000935' => '信阳区域总公司',
                '00000937' => '商丘区域总公司'
            );
            $this->assign('is_admin', 1);
        } else {
            $parentlists = array($this->panterid);
            $parent = $this->panterid;
            $this->assign('is_admin', 0);
        }
        if ($start == '' && $end == '') {
            $time = array('start' => '', 'end' => '');
        }
        if ($start != '' && $end == '') {
            $time = array('start' => strtotime($start . ' 00:00:00'), 'end' => '');
            $where['placeddate'] = array('egt', $start);
            $this->assign('startdate', $start);
        }
        if ($start == '' && $end != '') {
            $time = array('start' => '', 'end' => strtotime($end . ' 23:59:59'));
            $where['placeddate'] = array('elt', $end);
            $this->assign('enddate', $end);
        }
        if ($start != '' && $end != '') {
            $time = array('start' => strtotime($start . ' 00:00:00'), 'end' => strtotime($end . ' 23:59:59'));
            $where['placeddate'] = array(array('egt', $start), array('elt', $end));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
        }
        // $parent = '00000115';
        if (!empty($parent)) {
            $panters = $pan->where(array('parent' => $parent))->select();
            if ($panters == true) {
                foreach ($panters as $key => $val) {
                    $panterlists[] = $val['panterid'];
                }
            }
            $panters_mo = $pan->where(array('parent' => $parent))
                ->field('panterid,namechinese')->select();
            $this->assign('panters_mo', $panters_mo);
            $this->assign('parent', $parent);
        } else {
            $panters_mo = $pan->where(array('parent' => array('in', array('00000284', '00000227', '00000323', '00000324', '00000325', '00000326', '00000327', '00000328', '00000329', '00000937'))))
                ->field('panterid,namechinese')->select();
            $this->assign('panters_mo', $panters_mo);
        }
        //法人公司条件控制
        if ($panterid != '') {
            $panter_xm = array(0 => $panterid);
            $panteradress = $pan->where(array('panterid' => $panterid))->field('panterid,nameenglish')->select();
            $this->assign('panteradress', $panteradress);
            $this->assign('panterid', $panterid);
        }
        //触发查询的条件
        if ($start != '' || $end != '' || $parent != '' || $panterid != '') {
            if ($panterid != '') {
                $fangzg_data = $this->getFangzg(array('time' => $time, 'panterid' => $panter_xm));
                $where_excel = array('time' => $time, 'panterid' => $panter_xm);
                session('tbcount_excel', $where_excel);
            } else {
                $fangzg_data = $this->getFangzg(array('time' => $time, 'panterid' => $panterlists));
                $where_excel = array('time' => $time, 'panterid' => $panterlists);
                session('tbcount_excel', $where_excel);
            }
        } else {
            $fangzg_data = $this->getFangzg(array('time' => $time, 'panterid' => ''));
            $where_excel = array('time' => $time, 'panterid' => '');
            session('tbcount_excel', $where_excel);
        }
        // dump($fangzg_data);
        $keys = array_keys($fangzg_data);
        $length = sizeof($keys);
        for ($i = 1; $i < $length; $i++) {
            //按时间统计兑换率 2016 ---04----19-- start
            $wheres['a.panterid'] = $keys[$i];
            $wheres['c.flag'] = '1';
            if (!empty($where['placeddate'])) {
                $wheres['c.placeddate'] = $where['placeddate'];
            }
            $cashlists = $coin_consume->alias('c')
                ->join('coin_account a on a.coinid=c.coinid')
                ->where($wheres)->field('c.amount')->select();
            if ($cashlists == false) {
                $cash_tb = 0.00;
            } else {
                $cash_tb = array_sum(array_column($cashlists, 'amount'));
            }
            $fangzg_data[$keys[$i]]['cash'] =  $cash_tb;
            //按时间统计总兑换
            $cash_tbs = bcadd($cash_tbs, $cash_tb, 2);
            //-------------2016 ----04 -----------19 end
            //统计兑换的通宝
            $lists = $coin_account->where(array('panterid' => $keys[$i]))->select();
            $rechargeamount = array_sum(array_column($lists, 'rechargeamount'));
            $remindamount = array_sum(array_column($lists, 'remindamount'));
            $cash_amount = bcsub($rechargeamount, $remindamount, 2);
            $cash_total = bcadd($cash_total, $cash_amount, 2);
            $total = bcadd($total, $rechargeamount, 2);
            // $fangzg_data[$keys[$i]]['cash'] =  $cash_amount;
            //累计兑换率
            if ($cash_amount == 0.00) {
                $cash_rate = 0.00;
            } else {
                $cash_rate = $cash_amount / ($rechargeamount / 24) * 100;
                $cash_rate = number_format($cash_rate, 2, '.', '');
            }
            $fangzg_data[$keys[$i]]['cash_rate'] = $cash_rate . '%';
            //查询所属商户对应的区域公司 和法定公司
            $parentlist = $pan->alias('p')
                ->join('panters p1 on p1.panterid=p.parent')
                ->where(array('p.panterid' => $keys[$i]))
                ->field('p1.namechinese,p.namechinese pname,p.nameenglish')->find();
            $arr_parent[] = $parentlist['namechinese'];
            $arr_pname[] = $parentlist['pname'];
            $arr_nameenligsh[] = $parentlist['nameenligsh'];
            $fangzg_data[$keys[$i]]['parentname'] =  $parentlist['namechinese'];
            $fangzg_data[$keys[$i]]['pname'] =  $parentlist['pname'];
            $fangzg_data[$keys[$i]]['nameenglish'] =  $parentlist['nameenglish'];
            //查询结算通宝数量
            $clearing_tblist = $coin_consume->alias('c')
                ->join('coin_account a on a.coinid = c.coinid')
                ->where(array('a.panterid' => $keys[$i], 'c.status' => '1', 'c.flag' => '1', 'c.placeddate' => $where['placeddate']))
                ->field('c.amount')->select();
            $clearing_tb = array_sum(array_column($clearing_tblist, 'amount'));
            if ($clearing_tblist == false) {
                $clearing_tb = 0.00;
            }
            $fangzg_data[$keys[$i]]['clear_tb'] = $clearing_tb;
            if ($fangzg_data[$keys[$i]]['threedaycardcale'] != null) {
                $fangzg_data[$keys[$i]]['threedaycardcale'] = ($fangzg_data[$keys[$i]]['threedaycardcale'] * 100) . '%';
            }
            $fangzg_data[$keys[$i]]['cardscale'] = ($fangzg_data[$keys[$i]]['cardscale'] * 100) . '%';
            $clearing_total = bcadd($clearing_tb, $clearing_total, 2);
        }
        // $fangzg_data['totaldata']['cash'] =  $cash_total;
        // $cash_total ==$cash_tbs || exit('统计异常');
        $fangzg_data['totaldata']['cash'] =  $cash_tbs;
        //总的兑换率
        if ($cash_total == 0.00) {
            $total_rate = 0.00;
        } else {
            $total_rate = $cash_total / ($total / 24) * 100;
            $total_rate = number_format($total_rate, 2, '.', '');
        }
        $fangzg_data['totaldata']['cash_rate'] = $total_rate . '%';
        //返回区域公司个数 和法定公司
        $parent_length = sizeof(array_unique($arr_parent));
        $pname_length = sizeof(array_unique($arr_pname));
        $nameenligsh_length = sizeof(array_unique($arr_nameenligsh));
        $fangzg_data['totaldata']['parentname'] = '区域公司统计：' . $parent_length;
        $fangzg_data['totaldata']['pname'] = '法人公司统计：' . $pname_length;
        $fangzg_data['totaldata']['nameenglish'] = '项目统计：' . $pname_length;
        $fangzg_data['totaldata']['clear_tb'] = $clearing_total;
        if ($fangzg_data['totaldata']['threedaycardcale'] != null) {
            $fangzg_data['totaldata']['threedaycardcale'] = ($fangzg_data['totaldata']['threedaycardcale'] * 100) . '%';
        }
        $fangzg_data['totaldata']['cardscale'] = ($fangzg_data['totaldata']['cardscale'] * 100) . '%';
        //数据排序处理
        ksort($fangzg_data);
        //-----计算2016年数据--------
        $data16['sendhouse'] = 16845;
        $data16['backmoney'] = 8184712530;
        $data16['cantb'] = 81847125.3;
        $data16['alsendhouse'] = 16175;
        $data16['alsendtb'] = 78653841.19;
        $data16['alusetb'] = 11173109.48;
        //$data16['alsettletb'] = 7782051.35;
        $data16['sevenrate'] = "58.69%";
        $data16['sendrate'] = "96.09%";
        $data16['userate'] = "406.74%";
        //------end---------

        foreach ($fangzg_data as $k => $v) {
            $fangzg_data_ha[$k]['totalhousenum'] = $v['totalhousenum'];
            $fangzg_data_ha[$k]['totalprice'] = $v['totalprice'];
            $fangzg_data_ha[$k]['totalsendhousenum'] = $v['totalsendhousenum'];
            $fangzg_data_ha[$k]['totaltb'] = number_format($v['totaltb'], '2', '.', '');
            $fangzg_data_ha[$k]['threedaycardcale'] = number_format(trim($v['threedaycardcale'], '%'), '2', '.', '') . "%";
            $fangzg_data_ha[$k]['cardscale'] = number_format(trim($v['cardscale'], '%'), '2', '.', '') . "%";
            $fangzg_data_ha[$k]['parentname'] = $v['parentname'];
            $fangzg_data_ha[$k]['cash'] = number_format($v['cash'], '2', '.', '');
            $fangzg_data_ha[$k]['cash_rate'] = number_format(trim($v['cash_rate'], '%'), '2', '.', '') . "%";
            $fangzg_data_ha[$k]['pname'] = $v['pname'];
            $fangzg_data_ha[$k]['nameenglish'] = $v['nameenglish'];
            $fangzg_data_ha[$k]['clear_tb'] = $v['clear_tb'];
        }
        session('tbcount_excle', $fangzg_data);
        $this->assign('fangzg_data', $fangzg_data_ha);
        $this->assign('parentlists', $parentlists);
        $this->assign("data16", $data16);
        $this->display();
    }
    protected function getFangzg($data)
    {

        $url = C('Tbcount_URL');

        $map['panterid'] = $data['panterid'];

        $map['time'] = $data['time'];
        $map['shuzi'] = rand(0, 9999);
        $keyCode = 'tbcount2016';
        $map['sign'] = md5(md5($keyCode . $map['shuzi']));
        // file_put_contents('./fangzhanggui.txt',$this->keyCode);
        $map = json_encode($map);
        $res = $this->getQuery($url, $map);
        return $res;
    }
    public function getQuery($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //禁用SSL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close();
        return json_decode($output, true);
    }
    //根据区域公司查询项目
    public function panterid_query()
    {
        $pan = M('panters');
        $parent = trim(I('post.parent', ''));
        if ($parent != '') {
            $panterlists = $pan->where(array('parent' => $parent))->field('panterid,namechinese,nameenglish')->select();
            $data = array(
                'status' => '1',
                'panterid' => $panterlists,
            );
        } else {
            $panterlists = $pan->where(array('parent' => array('in', array('00000284', '00000227', '00000323', '00000324', '00000325', '00000326', '00000327', '00000328', '00000329', '00000937'))))
                ->field('panterid,namechinese')->select();
            $data = array(
                'status' => '1',
                'panterid' => $panterlists,
            );
        }


        echo json_encode($data);
        exit;
    }
    //项目查询
    public function nameenglish_query()
    {
        $pan = M('panters');
        $panterid = trim(I('post.panterid', ''));
        if ($panterid != '') {
            $panterlists = $pan->where(array('panterid' => $panterid))->field('panterid,nameenglish')->select();
            $data = array(
                'status' => '1',
                'panterid' => $panterlists,
            );
        } else {
            $data = array(
                'status' => '1',
                'panterid' => array('panterid' => '', 'nameenglish' => ''),
            );
        }
        echo json_encode($data);
        exit;
    }
    //九大区域查询 ---2016 --04 ----19-----start
    public function area_query()
    {
        set_time_limit(0);
        $pan = M('panters');
        $coin_account = M('coin_account');
        $coin_consume = M('coin_consume');
        if ($this->panterid == 'FFFFFFFF') {
            $parentlists = array(
                '00000227', '00000323', '00000326',
                '00000325', '00000324', '00000329',
                '00000328', '00000284', '00000327',
                '00000935', '00000937'
            );
        } else {
            $parentlists = array($this->panterid);
        }
        $start = trim(I('get.startdate'));
        $start = str_replace('-', '', $start);
        $end = trim(I('get.enddate'));
        $end = str_replace('-', '', $end);
        $panterid = trim(I('get.panterid', ''));
        if ($start == '' && $end == '') {
            $time = array('start' => '', 'end' => '');
        }
        if ($start != '' && $end == '') {
            $time = array('start' => strtotime($start . ' 00:00:00'), 'end' => '');
            $where['placeddate'] = array('egt', $start);
            $this->assign('startdate', $start);
        }
        if ($start == '' && $end != '') {
            $time = array('start' => '', 'end' => strtotime($end . ' 23:59:59'));
            $where['placeddate'] = array('elt', $end);
            $this->assign('enddate', $end);
        }
        if ($start != '' && $end != '') {
            $time = array('start' => strtotime($start . ' 00:00:00'), 'end' => strtotime($end . ' 23:59:59'));
            $where['placeddate'] = array(array('egt', $start), array('elt', $end));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
        }
        session('area_queryTime', $time);
        session('area_queryExcel', $where);
        //各个区域查询
        foreach ($parentlists as $key => $val) {
            $parentlist[$val]  = $pan->where(array('panterid' => $val))->find();
            $panters = $pan->where(array('parent' => $val))->select();
            if ($panters == true) {
                foreach ($panters as $k => $v) {
                    $panterlists[] = $v['panterid'];
                }
                $fangzg = $this->getFangzg(array('time' => $time, 'panterid' => $panterlists));
                //计算兑换 通宝数量
                foreach ($panterlists as $k1 => $v1) {
                    //查询区域下公司的 某个时间段兑换通宝
                    $wheres[$v1]['a.panterid'] = $v1;
                    $wheres[$v1]['c.flag'] = '1';
                    if (!empty($where['placeddate'])) {
                        $wheres[$v1]['c.placeddate'] = $where['placeddate'];
                    }
                    $consume_time = $coin_consume->alias('c')
                        ->join('coin_account a on a.coinid=c.coinid')
                        ->where($wheres[$v1])->field('c.amount')->select();
                    // dump($val);dump($consume_time);
                    if ($consume_time == true) {
                        $consume_ts[$val] = array_sum(array_column($consume_time, 'amount'));
                    } else {
                        $consume_ts[$val] = 0.00;
                    }
                    $consume_tssum[$val] = bcadd($consume_tssum[$val], $consume_ts[$val], 2);
                    // //查询区域下公司发行累计发行的通宝
                    // $tb_where[$v1]['panterid'] = $v1;
                    // $tblist = $coin_account->where($tb_where[$v1])->select();
                    // if($tblist==true){
                    //     $tb = array_sum(array_column($tblist,'rechargeamount'));
                    //     $tb_remain = array_sum(array_column($tblist,'remindamount'));
                    //     $consume = bcsub($tb[$val],$tb_remain[$val],2);
                    // }
                    // $consume_sum[$val] = bcadd($consume_sum[$val],$consume,2);
                    // $tb_sum[$val] = bcadd($tb_sum[$val],$tb,2);
                    //查询已经结算通吧
                    $cash_where[$v1]['a.panterid'] = $v1;
                    $cash_where[$v1]['c.flag'] = '1';
                    $cash_where[$v1]['c.status'] = '1';
                    if (!empty($where['placeddate'])) {
                        $cash_where[$v1]['c.placeddate'] = $where['placeddate'];
                    }
                    $cashlist = $coin_consume->alias('c')
                        ->join('coin_account a on a.coinid=c.coinid')
                        ->where($cash_where[$v1])->field('c.amount')->select();
                    if ($cashlist == true) {
                        $cash[$val] = array_sum(array_column($cashlist, 'amount'));
                    } else {
                        $cash[$val] = 0.00;
                    }
                    $cash_sum[$val] = bcadd($cash_sum[$val], $cash[$val], 2);
                }
                unset($panterlists); //销毁区域下公司数组
                //2016----04--21重写累计发行通宝
                // dump($consume_tsum);
                $sql = "select rechargeamount,remindamount from coin_account where panterid in (SELECT panterid FROM panters where parent='{$val}')";
                $tblist = $coin_account->query($sql);
                if ($tblist == true) {
                    $tb[$val] = array_sum(array_column($tblist, 'rechargeamount'));
                    $tb_remain[$val] = array_sum(array_column($tblist, 'remindamount'));
                    $consume[$val] = bcsub($tb[$val], $tb_remain[$val], 2);
                    $tb_rate[$val] = $consume[$val] / ($tb[$val] / 24) * 100;
                    $tb_rate[$val] = number_format($tb_rate[$val], 2, '.', '') . '%';
                } else {
                    $tb[$val] = 0.00;
                    $consume[$val] = 0.00;
                    $tb_rate[$val] = '0.00%';
                }
                //计算通宝累计兑换率
                $area[$val] = $fangzg['totaldata'];
                $area[$val]['tb_rate'] = $tb_rate[$val];
                $area[$val]['tb'] = $consume_tssum[$val];
                $area[$val]['cash'] = $cash_sum[$val];
                $area[$val]['ftb'] = $tb[$val];
                $area[$val]['contb'] = $consume[$val];
                $area[$val]['parentname'] = $parentlist[$val]['namechinese'];
            }
        }
        // dump($area);
        //合计   结算总通宝
        $cash_total = array_sum(array_column($area, 'cash'));
        //兑换
        $consume_ttotal = array_sum(array_column($area, 'tb'));
        //累计兑换
        $consume_total = array_sum(array_column($area, 'contb'));
        //累计发行通宝
        $tb_total = array_sum(array_column($area, 'ftb'));
        //合计兑换率
        // dump($tb_total);
        $tb_rate_total = $consume_ttotal / ($tb_total / 24) * 100;
        $tb_rate_total = number_format($tb_rate_total, 2, '.', '') . '%';
        // $tb_rate_total.='%';
        foreach ($parentlists as $key => $val) {
            $panterss = $pan->where(array('parent' => $val))->select();
            if ($panterss == true) {
                foreach ($panterss as $k => $v) {
                    $lists[] = $v['panterid'];
                }
            }
        }
        $totaldata = $this->getFangzg(array('time' => $time, 'panterid' => $lists));
        //-----计算2016年数据--------
        $data16['sendhouse'] = 16845;
        $data16['backmoney'] = 8184712530;
        $data16['cantb'] = 81847125.3;
        $data16['alsendhouse'] = 16175;
        $data16['alsendtb'] = 78653841.19;
        $data16['alusetb'] = 11173109.48;
        $data16['alsettletb'] = 7782051.35;
        $data16['sevenrate'] = "58.69%";
        $data16['sendrate'] = "96.09%";
        $data16['userate'] = "406.74%";


        //------end---------
        $area['totaldata'] = $totaldata['totaldata'];
        $area['totaldata']['cash'] = $cash_total;
        $area['totaldata']['tb'] = $consume_ttotal;
        $area['totaldata']['tb_rate'] = $tb_rate_total;
        $area['totaldata']['parentname'] = '汇总';
        //数据处理
        foreach ($area as $key => $value) {
            $area_handle[$key]['totalhousenum'] = $value['totalhousenum'];
            $area_handle[$key]['totalprice'] = $value['totalprice'];
            $area_handle[$key]['totalsendhousenum'] = $value['totalsendhousenum'];
            $area_handle[$key]['totaltb'] = number_format($value['totaltb'], '2', '.', '');
            $area_handle[$key]['threedaycardcale'] = bcmul($value['threedaycardcale'], 100, 2) . "%";
            $area_handle[$key]['cardscale'] = bcmul($value['cardscale'], 100, 2) . "%";
            $area_handle[$key]['tb_rate'] = number_format(trim($value['tb_rate'], "%"), '2', '.', '') . "%";
            $area_handle[$key]['tb'] = number_format($value['tb'], '2', '.', '');
            $area_handle[$key]['cash'] = number_format($value['cash'], '2', '.', '');
            $area_handle[$key]['ftb'] = number_format($value['ftb'], '2', '.', '');
            $area_handle[$key]['contb'] = number_format($value['contb'], '2', '.', '');
            $area_handle[$key]['parentname'] = $value['parentname'];
        }
        $this->assign('area', $area_handle);
        $this->assign('data16', $data16);

        $this->display();
    }
    public  function tbcount_excel()
    {
        $pan = M('panters');
        $coin_account = M('coin_account');
        $coin_consume = M('coin_consume');
        $where = session('tbcount_excel');
        $fangzg_data = $this->getFangzg($where);
        $keys = array_keys($fangzg_data);
        $length = sizeof($keys);
        for ($i = 1; $i < $length; $i++) {
            //按时间统计兑换率 2016 ---04----19-- start
            $wheres['a.panterid'] = $keys[$i];
            $wheres['c.flag'] = '1';
            if (!empty($where['placeddate'])) {
                $wheres['c.placeddate'] = $where['placeddate'];
            }
            $cashlists = $coin_consume->alias('c')
                ->join('coin_account a on a.coinid=c.coinid')
                ->where($wheres)->field('c.amount')->select();
            if ($cashlists == false) {
                $cash_tb = 0.00;
            } else {
                $cash_tb = array_sum(array_column($cashlists, 'amount'));
            }
            $fangzg_data[$keys[$i]]['cash'] =  $cash_tb;
            //按时间统计总兑换
            $cash_tbs = bcadd($cash_tbs, $cash_tb, 2);
            //-------------2016 ----04 -----------19 end
            //统计兑换的通宝
            $lists = $coin_account->where(array('panterid' => $keys[$i]))->select();
            $rechargeamount = array_sum(array_column($lists, 'rechargeamount'));
            $remindamount = array_sum(array_column($lists, 'remindamount'));
            $cash_amount = bcsub($rechargeamount, $remindamount, 2);
            $cash_total = bcadd($cash_total, $cash_amount, 2);
            $total = bcadd($total, $rechargeamount, 2);
            // $fangzg_data[$keys[$i]]['cash'] =  $cash_amount;
            //累计兑换率
            if ($cash_amount == 0.00) {
                $cash_rate = 0.00;
            } else {
                $cash_rate = $cash_amount / ($rechargeamount / 24) * 100;
                $cash_rate = number_format($cash_rate, 2, '.', '');
            }
            $fangzg_data[$keys[$i]]['cash_rate'] = $cash_rate . '%';
            //查询所属商户对应的区域公司 和法定公司
            $parentlist = $pan->alias('p')
                ->join('panters p1 on p1.panterid=p.parent')
                ->where(array('p.panterid' => $keys[$i]))
                ->field('p1.namechinese,p.namechinese pname,p.nameenglish')->find();
            $arr_parent[] = $parentlist['namechinese'];
            $arr_pname[] = $parentlist['pname'];
            $arr_nameenligsh[] = $parentlist['nameenligsh'];
            $fangzg_data[$keys[$i]]['parentname'] =  $parentlist['namechinese'];
            $fangzg_data[$keys[$i]]['pname'] =  $parentlist['pname'];
            $fangzg_data[$keys[$i]]['nameenglish'] =  $parentlist['nameenglish'];
            //查询结算通宝数量
            $clearing_tblist = $coin_consume->alias('c')
                ->join('coin_account a on a.coinid = c.coinid')
                ->where(array('a.panterid' => $keys[$i], 'c.status' => '1', 'c.flag' => '1', 'c.placeddate' => $where['placeddate']))
                ->field('c.amount')->select();
            $clearing_tb = array_sum(array_column($clearing_tblist, 'amount'));
            if ($clearing_tblist == false) {
                $clearing_tb = 0.00;
            }
            $fangzg_data[$keys[$i]]['clear_tb'] = $clearing_tb;
            if ($fangzg_data[$keys[$i]]['threedaycardcale'] != null) {
                $fangzg_data[$keys[$i]]['threedaycardcale'] = ($fangzg_data[$keys[$i]]['threedaycardcale'] * 100) . '%';
            }
            $fangzg_data[$keys[$i]]['cardscale'] = ($fangzg_data[$keys[$i]]['cardscale'] * 100) . '%';
            $clearing_total = bcadd($clearing_tb, $clearing_total, 2);
        }
        // $fangzg_data['totaldata']['cash'] =  $cash_total;
        // $cash_total ==$cash_tbs || exit('统计异常');
        $fangzg_data['totaldata']['cash'] =  $cash_tbs;
        //总的兑换率
        if ($cash_total == 0.00) {
            $total_rate = 0.00;
        } else {
            $total_rate = $cash_total / ($total / 24) * 100;
            $total_rate = number_format($total_rate, 2, '.', '');
        }
        $fangzg_data['totaldata']['cash_rate'] = $total_rate . '%';
        //返回区域公司个数 和法定公司
        $parent_length = sizeof(array_unique($arr_parent));
        $pname_length = sizeof(array_unique($arr_pname));
        $nameenligsh_length = sizeof(array_unique($arr_nameenligsh));
        $fangzg_data['totaldata']['parentname'] = '区域公司统计：' . $parent_length;
        $fangzg_data['totaldata']['pname'] = '法人公司统计：' . $pname_length;
        $fangzg_data['totaldata']['nameenglish'] = '项目统计：' . $pname_length;
        $fangzg_data['totaldata']['clear_tb'] = $clearing_total;
        if ($fangzg_data['totaldata']['threedaycardcale'] != null) {
            $fangzg_data['totaldata']['threedaycardcale'] = ($fangzg_data['totaldata']['threedaycardcale'] * 100) . '%';
        }
        $fangzg_data['totaldata']['cardscale'] = ($fangzg_data['totaldata']['cardscale'] * 100) . '%';
        //数据排序处理
        ksort($fangzg_data);
        $string = '区域公司,法人公司,所在项目,符合赠送房源,回款金额,符合赠送通宝,已赠送房源,已赠送通宝,已兑换通宝,已结算通宝,发卡率,兑换率';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($fangzg_data as $key => $val) {
            $val['parentname'] = iconv('utf-8', 'gbk', $val['parentname']);
            $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
            $val['nameenglish'] = iconv('utf-8', 'gbk', $val['nameenglish']);
            $val['fhtb'] = $val['totalprice'] * 0.01;
            $string .= $val['parentname'] . "\t," . $val['pname'] . "\t," . $val['nameenglish'] . "\t,";
            $string .= $val['totalhousenum'] . "\t," . $val['totalprice'] . "\t," . $val['fhtb'] . "\t," . $val['totalsendhousenum'] . "\t,";
            $string .= $val['totaltb'] . "\t," . $val['cash'] . "\t," . $val['clear_tb'] . "\t,";
            $string .= $val['threedaycardcale'] . "\t," . $val['cash_rate'] . "\n";
        }
        $filename = '通宝统计报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }
    public function area_queryExcel()
    {
        $pan = M('panters');
        $coin_account = M('coin_account');
        $coin_consume = M('coin_consume');
        if ($this->panterid == 'FFFFFFFF') {
            $parentlists = array(
                '00000227', '00000323', '00000326',
                '00000325', '00000324', '00000329',
                '00000328', '00000284', '00000327',
                '00000937'
            );
        } else {
            $parentlists = array($this->panterid);
        }
        $where = session('area_queryExcel');
        $time = session('area_queryTime');
        //     dump($where);
        //各个区域查询
        foreach ($parentlists as $key => $val) {
            $parentlist[$val]  = $pan->where(array('panterid' => $val))->find();
            $panters = $pan->where(array('parent' => $val))->select();
            if ($panters == true) {
                foreach ($panters as $k => $v) {
                    $panterlists[] = $v['panterid'];
                }
                $fangzg = $this->getFangzg(array('time' => $time, 'panterid' => $panterlists));
                //计算兑换 通宝数量
                foreach ($panterlists as $k1 => $v1) {
                    //查询区域下公司的 某个时间段兑换通宝
                    $wheres[$v1]['a.panterid'] = $v1;
                    $wheres[$v1]['c.flag'] = '1';
                    if (!empty($where['placeddate'])) {
                        $wheres[$v1]['c.placeddate'] = $where['placeddate'];
                    }
                    $consume_time = $coin_consume->alias('c')
                        ->join('coin_account a on a.coinid=c.coinid')
                        ->where($wheres[$v1])->field('c.amount')->select();
                    // dump($val);dump($consume_time);
                    if ($consume_time == true) {
                        $consume_ts[$val] = array_sum(array_column($consume_time, 'amount'));
                    } else {
                        $consume_ts[$val] = 0.00;
                    }
                    $consume_tssum[$val] = bcadd($consume_tssum[$val], $consume_ts[$val], 2);
                    // //查询区域下公司发行累计发行的通宝
                    // $tb_where[$v1]['panterid'] = $v1;
                    // $tblist = $coin_account->where($tb_where[$v1])->select();
                    // if($tblist==true){
                    //     $tb = array_sum(array_column($tblist,'rechargeamount'));
                    //     $tb_remain = array_sum(array_column($tblist,'remindamount'));
                    //     $consume = bcsub($tb[$val],$tb_remain[$val],2);
                    // }
                    // $consume_sum[$val] = bcadd($consume_sum[$val],$consume,2);
                    // $tb_sum[$val] = bcadd($tb_sum[$val],$tb,2);
                    //查询已经结算通吧
                    $cash_where[$v1]['a.panterid'] = $v1;
                    $cash_where[$v1]['c.flag'] = '1';
                    $cash_where[$v1]['c.status'] = '1';
                    if (!empty($where['placeddate'])) {
                        $cash_where[$v1]['c.placeddate'] = $where['placeddate'];
                    }
                    $cashlist = $coin_consume->alias('c')
                        ->join('coin_account a on a.coinid=c.coinid')
                        ->where($cash_where[$v1])->field('c.amount')->select();
                    if ($cashlist == true) {
                        $cash[$val] = array_sum(array_column($cashlist, 'amount'));
                    } else {
                        $cash[$val] = 0.00;
                    }
                    $cash_sum[$val] = bcadd($cash_sum[$val], $cash[$val], 2);
                }
                unset($panterlists); //销毁区域下公司数组
                //2016----04--21重写累计发行通宝
                // dump($consume_tsum);
                $sql = "select rechargeamount,remindamount from coin_account where panterid in (SELECT panterid FROM panters where parent='{$val}')";
                $tblist = $coin_account->query($sql);
                if ($tblist == true) {
                    $tb[$val] = array_sum(array_column($tblist, 'rechargeamount'));
                    $tb_remain[$val] = array_sum(array_column($tblist, 'remindamount'));
                    $consume[$val] = bcsub($tb[$val], $tb_remain[$val], 2);
                    $tb_rate[$val] = $consume[$val] / ($tb[$val] / 24) * 100;
                    $tb_rate[$val] = number_format($tb_rate[$val], 2, '.', '') . '%';
                } else {
                    $tb[$val] = 0.00;
                    $consume[$val] = 0.00;
                    $tb_rate[$val] = '0.00%';
                }
                //计算通宝累计兑换率
                $area[$val] = $fangzg['totaldata'];
                $area[$val]['tb_rate'] = $tb_rate[$val];
                $area[$val]['tb'] = $consume_tssum[$val];
                $area[$val]['cash'] = $cash_sum[$val];
                $area[$val]['ftb'] = $tb[$val];
                $area[$val]['contb'] = $consume[$val];
                $area[$val]['parentname'] = $parentlist[$val]['namechinese'];
            }
        }
        // dump($area);
        //合计   结算总通宝
        $cash_total = array_sum(array_column($area, 'cash'));
        //兑换
        $consume_ttotal = array_sum(array_column($area, 'tb'));
        //累计兑换
        $consume_total = array_sum(array_column($area, 'contb'));
        //累计发行通宝
        $tb_total = array_sum(array_column($area, 'ftb'));
        //合计兑换率
        // dump($tb_total);
        $tb_rate_total = $consume_ttotal / ($tb_total / 24) * 100;
        $tb_rate_total = number_format($tb_rate_total, 2, '.', '') . '%';
        // $tb_rate_total.='%';
        foreach ($parentlists as $key => $val) {
            $panterss = $pan->where(array('parent' => $val))->select();
            if ($panterss == true) {
                foreach ($panterss as $k => $v) {
                    $lists[] = $v['panterid'];
                }
            }
        }
        $totaldata = $this->getFangzg(array('time' => $time, 'panterid' => $lists));
        // dump($totaldata);
        $area['totaldata'] = $totaldata['totaldata'];
        $area['totaldata']['cash'] = $cash_total;
        $area['totaldata']['tb'] = $consume_ttotal;
        $area['totaldata']['tb_rate'] = $tb_rate_total;
        $area['totaldata']['parentname'] = '汇总';
        // dump($area);
        //数据处理
        foreach ($area as $key => $value) {
            $value['threedaycardcale'] = ($value['threedaycardcale'] * 100) . '%';
            $value['cardscale'] = ($value['cardscale'] * 100) . '%';
            $area[$key] = $value;
        }
        $string = '区域公司,符合赠送房源,回款金额,符合赠送通宝,已赠送房源,已赠送通宝,已兑换通宝,已结算通宝,七天赠送率,赠送率,兑换率';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($area as $key => $val) {
            $val['parentname'] = iconv('utf-8', 'gbk', $val['parentname']);
            $val['fhtb'] = $val['totalprice'] * 0.01;
            $string .= $val['parentname'] . "\t,";
            $string .= $val['totalhousenum'] . "\t," . $val['totalprice'] . "\t," . $val['fhtb'] . "\t," . $val['totalsendhousenum'] . "\t,";
            $string .= $val['totaltb'] . "\t," . $val['tb'] . "\t," . $val['cash'] . "\t,";
            $string .= $val['threedaycardcale'] . "\t," . $val['cardscale'] . "\t," . $val['tb_rate'] . "\n";
        }
        $filename = '区域通宝统计报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }

    /**
     * @name 新增通宝兑换报表
     * @modify 2016-6-13 09:42:14  */
    public function conversionRate()
    {
        $starttime = $_POST['startdate'] ? $_POST['startdate'] : '';
        $endtime = $_POST['enddate'] ? $_POST['enddate'] : '';
        if ($starttime && $endtime) {
            $nowYear = str_replace('-', '', $starttime);
            $nextYear = str_replace('-', '', $endtime);
        } elseif (!$starttime && $endtime) {
            $nowYear = "20150101";
            $nextYear = str_replace('-', '', $endtime);
        } elseif ($starttime && !$endtime) {
            $nowYear = str_replace('-', '', $starttime);
            $nextYear = date('Y', time()) . '1231';
        } else {
            $nextYear = date('Y', time()) . '1231';
            $nowYear = date('Y', time()) . '0101';
        }
        $data = array();
        $areaInfo = array();
        //$nextYear = date('Y',strtotime("+1 year")).'0101';
        //$nowYear = date('Y',time()).'0101';
        //酒店总公司
        $hotelCondition['parent'] = array('eq', '00000013');
        $hotelPanterid = $this->searchPanterid($hotelCondition);
        $hotelAmount = $this->countConversion($hotelPanterid, $nowYear, $nextYear);

        //物业总公司
        $tenementCondition['parent'] = array('eq', '00000451');
        $tenementPanterid = $this->searchPanterid($tenementCondition);
        $tenementAmount = $this->countConversion($tenementPanterid, $nowYear, $nextYear);

        //教育公司
        $educationCondition['parent'] = array('eq', '00000130');
        $educationPanterid = $this->searchPanterid($educationCondition);
        $educationAmount = $this->countConversion($educationPanterid, $nowYear, $nextYear);

        //艾佳家居
        $ajCondition['parent'] = array('eq', '00000452');
        $ajPanterid = $this->searchPanterid($ajCondition);
        $ajAmount = $this->countConversion($ajPanterid, $nowYear, $nextYear);

        //绿色基地
        $greenCondition['parent'] = array('eq', '00000453');
        $greenPanterid = $this->searchPanterid($greenCondition);
        $greenAmount = $this->countConversion($greenPanterid, $nowYear, $nextYear);


        //足球俱乐部
        $footCondition['parent'] = array('eq', '00000014');
        $footPanterid = '00000307';
        $footAmount = $this->countConversion($footPanterid, $nowYear, $nextYear);

        //嵩云
        $soonCondition['parent'] = array('eq', '00000455');
        $soonPanterid = $this->searchPanterid($soonCondition);
        $soonAmount = $this->countConversion($soonPanterid, $nowYear, $nextYear);

        //商业公司
        $businessCondition['parent'] = array('eq', '00000456');
        $businessPanterid = $this->searchPanterid($businessCondition);
        $businessAmount = $this->countConversion($businessPanterid, $nowYear, $nextYear);

        //艾米
        $amCondition['parent'] = array('eq', '00000457');

        $amPanterid = $this->searchPanterid($amCondition);

        $amAmount = $this->countConversion($amPanterid, $nowYear, $nextYear);

        //旅游公司
        $tradAmount = $this->countConversion("00000466", $nowYear, $nextYear);

        $areaInfo = array(
            "物业总公司" => array(9000000, $tenementAmount),
            "酒店公司" => array(3500000, $hotelAmount),
            "教育公司" => array(850000, $educationAmount),
            "艾佳生活" => array(750000, $ajAmount),
            "绿色基地" => array(400000, $greenAmount),
            "足球俱乐部" => array(200000, $footAmount),
            "嵩云科技(一家)" => array(100000, $soonAmount),
            "商业公司" => array(100000, $businessAmount),
            "文旅公司(艾米)" => array(100000, $amAmount)

        );
        $key = 0;
        foreach ($areaInfo as $areaName => $areaGoal) {
            $data[$key]['areaname'] = $areaName;
            $data[$key]['areagoal'] = $areaGoal[0];
            $data[$key]['areaamount'] = $areaGoal[1];
            $rate = bcdiv($areaGoal[1], $areaGoal[0], 10);
            $rate = bcmul($rate, 100, 3);
            $data[$key]['rate'] = $rate . "%";
            $key++;
        }
        $data[] = array(
            'areaname' => "河南建业新生活旅游服务有限公司",
            'areagoal' => "",
            'areaamount' => $tradAmount,
            'rate' => ""
        );
        $memkey = "conversionRate" . session_id();
        session($memkey, $data);
        $this->assign('data', $data);
        $this->assign("startdate", $nowYear);
        $this->assign("enddate", $nextYear);

        $this->display();
    }
    /**
     * @name 导出行业兑换报表
     * @return excel
     * @modify 2016-6-14 16:27:50  */
    public function conversionExport()
    {
        $memkey = "conversionRate" . session_id();
        //       $memkey = md5($memkey);
        //       $mem = new \Memcache();
        //       $mem->addserver("127.0.0.1",11211);
        $data = session($memkey);
        session($memkey, null);
        //$mem->close();
        if (empty($data) || !$data) {
            $this->error("数据缺失，请刷新页面重试", "conversionRate");
        }
        $string = '各兑换业态,年度目标值(元),年度累计完成值(元),完成率(%)';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($data as $key => $val) {
            $val['areaname'] = iconv('utf-8', 'gbk', $val['areaname']);
            $string .= $val['areaname'] . "\t,";
            $string .= $val['areagoal'] . "\t," . $val['areaamount'] . "\t," . $val['rate'] . "\n";
        }
        $filename = '各业态兑换年度完成率报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }
    /**
     * @name 根据条件查找panterid
     * @param array $where
     * @return array
     * @modify 2016-6-13 14:02:48  */
    private function searchPanterid($where = array())
    {
        $panter = M('panters');
        $result = $panter->where($where)->field('panterid')->select();
        if (!empty($result) && $result) {
            return array_column($result, 'panterid');
        } else {
            return false;
        }
    }
    /**
     * @name 计算当年通宝兑换数量
     * @param (array or string) $panters
     * @param 20160101 $nowYear
     * @param 20170101 $nextYear
     * @return number or false
     * @modify 2016-6-13 09:38:06
     *   */
    private function countConversion($panters, $nowYear, $nextYear)
    {

        if (empty($panters) || !$panters) {
            return false;
        }

        $where['panterid'] = array('in', $panters);
        $where['_string'] = "(placeddate <= {$nextYear} and placeddate>= {$nowYear})";
        $coinConsume = M('coin_consume');

        $result = $coinConsume->where($where)->field('sum(amount) amount')->find();

        if ($result['amount']) {
            $number = $result['amount'];
        } else {
            $number = 0;
        }
        return $number;
    }
    /*
 * @content 特殊条件查询九大区域公司数据
 * @modifytime 2016年7月2日09:51:54
 * @auth szj */
    public function specialCount()
    {

        $parentlists = array(
            '00000227' => '郑州区域总公司', '00000323' => '洛阳区域总公司',
            '00000326' => '开封区域总公司', '00000325' => '驻马店区域总公司',
            '00000324' => '许昌区域总公司', '00000329' => '安阳区域总公司',
            '00000328' => '新乡区域总公司', '00000284' => '南阳区域总公司',
            '00000327' => '周口区域总公司', '00000935' => '信阳区域总公司',
            '00000937' => '商丘区域总公司'
        );


        $url = "https://www.fangzg.cn/posindex.php/tbSend/specialCount";
        $data = array("key" => md5(md5('specialCount')));
        $returnResult = $this->getQuery($url, $data);
        $info = $list = array(); //将parent放入数组
        $finalInfo = array(); //结果数据
        foreach ($returnResult as $k => $v) {
            $parent = $this->searchParent($v['parentid']);
            $list[$k] = $v;
            $list[$k]['parent'] = $parent;
        }
        //数据分入九大区域
        foreach ($parentlists as $kparent => $vparent) {
            foreach ($list as $kchild => $vchild) {
                if ($vchild['parent'] == $kparent) {
                    $info[$kparent][$kchild] = $vchild;
                    $info[$kparent][$kchild]['parentname'] = $vparent;
                }
            }
        }
        //计算总数据
        foreach ($info as $k => $v) {
            $finalInfo[$k]['totalhousenum'] = array_sum(array_column($v, "totalhousenum"));
            $finalInfo[$k]['agreementprice'] = array_sum(array_column($v, "agreementprice"));
            $finalInfo[$k]['parentname'] = $parentlists[$k];
            $finalInfo[$k]['tbcount'] = $finalInfo[$k]['agreementprice'] * 0.01;
        }
        $sessionSign = md5(session_id . 'specialCount');
        session($sessionSign, $finalInfo);
        $this->assign("data", $finalInfo);
        $this->display();
    }
    public function specialExport()
    {
        $sessionSign = md5(session_id . 'specialCount');

        $data =  session($sessionSign);
        session($sessionSign, null);

        if (empty($data) || !$data) {
            $this->error("数据缺失，请刷新页面重试", "specialCount");
        }
        $string = '区域公司,赠送房源数量,回款金额,赠送通宝数量';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($data as $key => $val) {
            $val['parentname'] = iconv('utf-8', 'gbk', $val['parentname']);
            $string .= $val['parentname'] . "\t,";
            $string .= $val['totalhousenum'] . "\t," . $val['agreementprice'] . "\t," . $val['tbcount'] . "\n";
        }
        $filename = '上月23号至月底各区域公司通宝数据' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }
    /*
   * @content 根据panterid 查询 panter
   * @param string panterid
   * @auth szj */
    private function searchParent($panterid)
    {
        $panters = M('panters');
        $parent = $panters->where("panterid = '{$panterid}'")->field("parent")->find();
        return $parent['parent'];
    }
    /*
   * 因每月需对各赠送方的累计“赠送率“进行考核，需查询指定时间段内的”赠送率“，
   * 公式为：截止上月底累计已赠送通宝数量/截止日7天前符合赠送条件通宝的数量。  */
    public function monthSend()
    {
        $parentlists = array(
            '00000227' => '郑州区域总公司', '00000323' => '洛阳区域总公司',
            '00000326' => '开封区域总公司', '00000325' => '驻马店区域总公司',
            '00000324' => '许昌区域总公司', '00000329' => '安阳区域总公司',
            '00000328' => '新乡区域总公司', '00000284' => '南阳区域总公司',
            '00000327' => '周口区域总公司', '00000935' => '信阳区域总公司',
            '00000937' => '商丘区域总公司'
        );

        $url = "https://www.fangzg.cn/posindex.php/tbSend/monthSend";
        $postdata = $_POST['startdate'] ? strtotime("{$_POST['startdate']}") : '';
        $data = array("key" => md5(md5('monthSend')), "date" => $postdata);
        $getData = $this->getQuery($url, $data);
        if ($getData['code'] != "000") {
            $this->display();
            exit;
        }
        $returnResult = $getData['data'];
        foreach ($returnResult as $k => $v) {
            $parent = $this->searchParent($k);
            $list[$k] = $v;
            $list[$k]['parent'] = $parent;
        }
        //数据分入九大区域
        foreach ($parentlists as $kparent => $vparent) {
            foreach ($list as $kchild => $vchild) {
                if ($vchild['parent'] == $kparent) {
                    $info[$kparent][$kchild] = $vchild;
                    $info[$kparent][$kchild]['parentname'] = $vparent;
                }
            }
        }
        //计算总数据
        foreach ($info as $k => $v) {
            $finalInfo[$k]['accordhouse'] = array_sum(array_column($v, "accordhouse"));
            $finalInfo[$k]['accordamount'] = array_sum(array_column($v, "accordamount"));
            $finalInfo[$k]['accordtb'] = array_sum(array_column($v, "accordtb"));
            $finalInfo[$k]['alreadyhouse'] = array_sum(array_column($v, "alreadyhouse"));
            $finalInfo[$k]['alreadyamount'] = array_sum(array_column($v, "alreadyamount"));
            $finalInfo[$k]['alreadytb'] = array_sum(array_column($v, "alreadytb"));
            $finalInfo[$k]['rate'] = bcdiv($finalInfo[$k]['alreadytb'], $finalInfo[$k]['accordtb'], 4);
            $finalInfo[$k]['rate'] = bcmul($finalInfo[$k]['rate'], 100, 2) . "%";
            $finalInfo[$k]['parentname'] = $parentlists[$k];
        }
        session("monthSend", $finalInfo);
        $this->assign("area", $finalInfo);
        $this->assign("startdate", date("Y-m", $postdata));
        $this->display();
    }
    public function monthSendEx()
    {
        $info = session("monthSend");
        empty($info) && $this->error("请刷新报表再导出");
        $string = '区域公司,符合赠送房源,回款金额,符合送通宝,已赠送房源,已赠送通宝,赠送率';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($info as $key => $val) {
            $val['parentname'] = iconv('utf-8', 'gbk', $val['parentname']);
            $string .= $val['parentname'] . "\t,";
            $string .= $val['accordhouse'] . "\t," . $val['accordamount'] . "\t," . $val['accordtb'] . "\t," . $val['alreadyhouse'] . "\t," . $val['alreadytb'] . "\t," . $val['rate'] . "\n";
        }
        $filename = '截止上月累计七天赠送率' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }
    /* 增加“月度七天赠送率”，
       * 公式为：月度内自符合赠送条件起小于等于7天完成赠送的建业通宝的数量（发行时间）/月度符合赠送条件建业通宝的数量（明源推送时间） */
    public function monthSend2()
    {
        $parentlists = array(
            '00000227' => '郑州区域总公司', '00000323' => '洛阳区域总公司',
            '00000326' => '开封区域总公司', '00000325' => '驻马店区域总公司',
            '00000324' => '许昌区域总公司', '00000329' => '安阳区域总公司',
            '00000328' => '新乡区域总公司', '00000284' => '南阳区域总公司',
            '00000327' => '周口区域总公司', '00000935' => '信阳区域总公司',
            '00000937' => '商丘区域总公司'

        );

        $url = "https://www.fangzg.cn/posindex.php/tbSend/monthSend2";
        $postdata = $_POST['startdate'] ? strtotime("{$_POST['startdate']}") : '';
        $data = array("key" => md5(md5('monthSend2')), "date" => $postdata);
        $getData = $this->getQuery($url, $data);
        if ($getData['code'] != "000") {
            $this->display();
            exit;
        }
        $returnResult = $getData['data'];

        foreach ($returnResult as $k => $v) {
            $parent = $this->searchParent($k);
            $list[$k] = $v;
            $list[$k]['parent'] = $parent;
        }
        //数据分入九大区域
        foreach ($parentlists as $kparent => $vparent) {
            foreach ($list as $kchild => $vchild) {
                if ($vchild['parent'] == $kparent) {
                    $info[$kparent][$kchild] = $vchild;
                    $info[$kparent][$kchild]['parentname'] = $vparent;
                }
            }
        }
        //计算总数据
        foreach ($info as $k => $v) {
            $finalInfo[$k]['accordhouse'] = array_sum(array_column($v, "accordhouse"));
            $finalInfo[$k]['accordamount'] = array_sum(array_column($v, "accordamount"));
            $finalInfo[$k]['accordtb'] = array_sum(array_column($v, "accordtb"));
            $finalInfo[$k]['alreadyhouse'] = array_sum(array_column($v, "alreadyhouse"));
            $finalInfo[$k]['alreadyamount'] = array_sum(array_column($v, "alreadyamount"));
            $finalInfo[$k]['alreadytb'] = array_sum(array_column($v, "alreadytb"));
            $finalInfo[$k]['rate'] = bcdiv($finalInfo[$k]['alreadytb'], $finalInfo[$k]['accordtb'], 4);
            $finalInfo[$k]['rate'] = bcmul($finalInfo[$k]['rate'], 100, 2) . "%";
            $finalInfo[$k]['parentname'] = $parentlists[$k];
        }
        $this->assign("area", $finalInfo);
        $this->assign("startdate", date("Y-m", $postdata));
        $this->display();
    }

    /* 增加“月度赠送率“，公式为：当月新增符合赠送条件建业通宝中实际赠送的数量/当月新增符合赠送条件建业通宝的数量 */
    public function monthSend3()
    {
        $parentlists = array(
            '00000227' => '郑州区域总公司', '00000323' => '洛阳区域总公司',
            '00000326' => '开封区域总公司', '00000325' => '驻马店区域总公司',
            '00000324' => '许昌区域总公司', '00000329' => '安阳区域总公司',
            '00000328' => '新乡区域总公司', '00000284' => '南阳区域总公司',
            '00000327' => '周口区域总公司', '00000935' => '信阳区域总公司',
            '00000937' => '商丘区域总公司'
        );

        $url = "https://www.fangzg.cn/posindex.php/tbSend/monthSend3";
        $postdata = $_POST['startdate'] ? strtotime("{$_POST['startdate']}") : '';
        $data = array("key" => md5(md5('monthSend2')), "date" => $postdata);
        $getData = $this->getQuery($url, $data);
        if ($getData['code'] != "000") {
            $this->display();
            exit;
        }
        $returnResult = $getData['data'];

        foreach ($returnResult as $k => $v) {
            $parent = $this->searchParent($k);
            $list[$k] = $v;
            $list[$k]['parent'] = $parent;
        }
        //数据分入九大区域
        foreach ($parentlists as $kparent => $vparent) {
            foreach ($list as $kchild => $vchild) {
                if ($vchild['parent'] == $kparent) {
                    $info[$kparent][$kchild] = $vchild;
                    $info[$kparent][$kchild]['parentname'] = $vparent;
                }
            }
        }
        //计算总数据
        foreach ($info as $k => $v) {
            $finalInfo[$k]['accordhouse'] = array_sum(array_column($v, "accordhouse"));
            $finalInfo[$k]['accordamount'] = array_sum(array_column($v, "accordamount"));
            $finalInfo[$k]['accordtb'] = array_sum(array_column($v, "accordtb"));
            $finalInfo[$k]['alreadyhouse'] = array_sum(array_column($v, "alreadyhouse"));
            $finalInfo[$k]['alreadyamount'] = array_sum(array_column($v, "alreadyamount"));
            $finalInfo[$k]['alreadytb'] = array_sum(array_column($v, "alreadytb"));
            $finalInfo[$k]['rate'] = bcdiv($finalInfo[$k]['alreadytb'], $finalInfo[$k]['accordtb'], 4);
            $finalInfo[$k]['rate'] = bcmul($finalInfo[$k]['rate'], 100, 2) . "%";
            $finalInfo[$k]['parentname'] = $parentlists[$k];
        }
        $this->assign("area", $finalInfo);
        $this->assign("startdate", date("Y-m-d", $postdata));
        $this->display();
    }

    /* @content 统计通宝账户余额低于50用户信息 */
    public function lowAccount()
    {
        $this->display();
    }
    public function getLowAccount()
    {
        $page = I('post.page', 1, 'intval');
        $pageSize = I('post.rows', 20, 'intval');
        $account = M('account');
        $count = $account->alias('a')
            ->join("customs_c b on a.customid=b.cid")
            ->join("customs c on b.customid=c.customid")
            ->join("cards d on b.cid=d.customid")
            ->join("RIGHT JOIN (SELECT distinct accountid from coin_account) e on a.accountid=e.accountid")
            ->field("c.namechinese as name,c.linktel as phone,c.sex,a.amount")
            ->where("a.type='01' and a.amount<=50 and (d.cardno not like '233%') and c.namechinese is not null")
            ->count();
        $info = $account->alias('a')
            ->join("customs_c b on a.customid=b.cid")
            ->join("customs c on b.customid=c.customid")
            ->join("cards d on b.cid=d.customid")
            ->join("RIGHT JOIN (SELECT distinct accountid from coin_account) e on a.accountid=e.accountid")
            ->field("c.namechinese as name,c.linktel as phone,c.sex,a.amount,d.cardno")
            ->page($page, $pageSize)
            ->where("a.type='01' and a.amount<=50 and (d.cardno not like '233%') and c.namechinese is not null")
            ->select();
        $returnInfo['total'] = !empty($count) ? $count : 0;
        $returnInfo['rows'] = !empty($info) ? array_values($info) : '';
        $this->ajaxReturn($returnInfo);
    }
    public function la_excel()
    {
        $account = M('account');
        $info = $account->alias('a')
            ->join("customs_c b on a.customid=b.cid")
            ->join("customs c on b.customid=c.customid")
            ->join("cards d on b.cid=d.customid")
            ->join("RIGHT JOIN (SELECT distinct accountid from coin_account) e on a.accountid=e.accountid")
            ->field("c.namechinese as name,c.linktel as phone,c.sex,a.amount,d.cardno")
            ->where("a.type='01' and a.amount<=50 and (d.cardno not like '233%') and c.namechinese is not null")
            ->select();
        $string = '卡号,会员名字,性别,联系电话,账户余额(元)';
        $string = iconv('utf-8', 'gbk', $string);
        $string .= "\n";
        foreach ($info as $key => $val) {
            $val['name'] = iconv('utf-8', 'gbk', $val['name']);
            $val['sex'] = iconv('utf-8', 'gbk', $val['sex']);
            $val['cardno'] = "'{$val['cardno']}'";
            $string .= $val['cardno'] . "\t,";
            $string .= $val['name'] . "\t," . $val['sex'] . "\t," . $val['phone'] . "\t," . $val['amount'] . "\n";
        }
        $filename = '低余额账户信息表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($string, $filename);
    }

    public function countPeploe()
    {
        $coinConsume = M('coin_consume');
        $start = I('post.startdate', '');
        $end = I('post.enddate', '');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
        }
        $field = 'cu.customid as customid';
        $count_repeat = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')
            ->field($field)
            ->where($where)
            ->group('cu.customid')
            ->having('count(cu.customid)>1')
            ->select();
        if (is_array($count_repeat)) {
            $count_repeat = count($count_repeat);
        } else {
            $count_repeat = 0;
        }

        $count_unique = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')
            ->field($field)
            ->where($where)
            ->group('cu.customid')
            ->having('count(cu.customid)=1')
            ->select();
        if (is_array($count_unique)) {
            $count_unique = count($count_unique);
        } else {
            $count_unique = 0;
        }

        $count_total = bcadd($count_repeat, $count_unique);
        $this->assign("count_total", $count_total);
        $this->assign("count_repeat", $count_repeat);
        $this->assign("count_unique", $count_unique);
        $this->display();
    }

    //-----各业态在一家app兑换情况
    public function eappConsumer()
    {

        $info = array();

        //酒店总公司
        $hotelCondition['parent'] = array('eq', '00000013');
        $hotelPanterid = $this->searchPanterid($hotelCondition);
        if ($hotelPanterid) {
            $hotelAmount = $this->csmOnapp($hotelPanterid);
        } else {
            $hotelAmount = 0;
        }
        $info[0]['name'] = "酒店总公司";
        $info[0]['amount'] = $hotelAmount;
        $info[0]['type'] = 1;
        $info[0]['panterid'] = "00000013";

        //物业总公司
        $tenementCondition['parent'] = array('eq', '00000451');
        $tenementPanterid = $this->searchPanterid($tenementCondition);
        if ($tenementPanterid) {
            $tenementAmount = $this->csmOnapp($tenementPanterid);
        } else {
            $tenementAmount = 0;
        }

        $info[1]['name'] = "物业总公司";
        $info[1]['amount'] = $tenementAmount;
        $info[1]['type'] = 1;
        $info[1]['panterid'] = "00000451";

        //教育公司
        $educationCondition['parent'] = array('eq', '00000130');
        $educationPanterid = $this->searchPanterid($educationCondition);
        if ($educationPanterid) {
            $educationAmount = $this->csmOnapp($educationPanterid);
        } else {
            $educationAmount = 0;
        }

        $info[2]['name'] = "教育公司";
        $info[2]['amount'] = $educationAmount;
        $info[2]['type'] = 1;
        $info[2]['panterid'] = "00000130";

        //艾佳家居
        $ajCondition['parent'] = array('eq', '00000452');
        $ajPanterid = $this->searchPanterid($ajCondition);
        if ($ajPanterid) {
            $ajAmount = $this->csmOnapp($ajPanterid);
        } else {
            $ajAmount = 0;
        }

        $info[3]['name'] = "艾佳家居";
        $info[3]['amount'] = $ajAmount;
        $info[3]['type'] = 1;
        $info[3]['panterid'] = "00000452";

        //绿色基地
        $greenCondition['parent'] = array('eq', '00000453');
        $greenPanterid = $this->searchPanterid($greenCondition);
        if ($greenPanterid) {
            $greenAmount = $this->csmOnapp($greenPanterid);
        } else {
            $greenAmount = 0;
        }

        $info[4]['name'] = "绿色基地";
        $info[4]['amount'] = $greenAmount;
        $info[4]['type'] = 1;
        $info[4]['panterid'] = "00000453";


        //足球俱乐部
        $footPanterid = '00000290';
        $footAmount = $this->csmOnapp($footPanterid);
        $info[5]['name'] = "足球俱乐部";
        $info[5]['amount'] = $footAmount;
        $info[5]['type'] = 0;
        $info[5]['panterid'] = "00000290";

        //一家app
        $soonAmount = $this->csmOnapp('00000286');
        $info[6]['name'] = "嵩云科技(一家)";
        $info[6]['amount'] = $soonAmount;
        $info[6]['type'] = 0;
        $info[6]['panterid'] = "00000286";

        //商业公司
        $businessCondition['parent'] = array('eq', '00000456');
        $businessPanterid = $this->searchPanterid($businessCondition);
        if ($businessPanterid) {
            $businessAmount = $this->csmOnapp($businessPanterid);
        } else {
            $businessAmount = 0;
        }

        $info[7]['name'] = "商业公司";
        $info[7]['amount'] = $soonAmount;
        $info[7]['type'] = 1;
        $info[7]['panterid'] = "00000456";


        //艾米
        $amCondition['parent'] = array('eq', '00000457');
        $amPanterid = $this->searchPanterid($amCondition);
        if ($amPanterid) {
            $amAmount = $this->csmOnapp($amPanterid);
        } else {
            $amAmount = 0;
        }

        $info[8]['name'] = "文旅公司(艾米)";
        $info[8]['amount'] = $amAmount;
        $info[8]['type'] = 1;
        $info[8]['panterid'] = "00000457";

        //旅游公司
        $tradAmount = $this->csmOnapp("00000466");
        $info[9]['name'] = "旅游公司";
        $info[9]['amount'] = $tradAmount;
        $info[9]['type'] = 0;
        $info[9]['panterid'] = "00000466";

        $this->assign("data", $info);
        $this->display();
    }

    //--------各业态于eapp平台兑换------
    public function eappDetail()
    {
        $area_name = I("get.name");
        $amount = I("get.amount");
        $type = I("get.type");
        $panterid_tmp = I("get.panterid");

        $map = array(
            "type" => $type,
            "panterid" => $panterid_tmp
        );

        if ($type) {
            $panterCondition['parent'] = array('eq', $panterid_tmp);
            $panterid = $this->searchPanterid($panterCondition);
        } else {
            $panterid = $panterid_tmp;
        }
        $where['a.termposno'] = "00000001";
        $where['a.panterid'] = array("in", $panterid);
        $where['a.tradetype'] = '00';
        $where['a.tradeamount'] = 0;
        $where['a.flag'] = '0';



        $wastebooks = M('trade_wastebooks');
        $count = $wastebooks->alias('a')
            ->join('cards b on a.cardno=b.cardno')
            ->join('customs_c c on b.customid=c.cid')
            ->join('customs d on c.customid=d.customid')
            ->field("d.namechinese name,a.cardno cardno,
                                    a.tradepoint tradepoint,a.placeddate,
                                    a.placedtime")
            ->where($where)
            ->count();

        $p = new \Think\Page($count, 50);
        $page = $p->show();


        $result = $wastebooks->alias('a')
            ->join('cards b on a.cardno=b.cardno')
            ->join('customs_c c on b.customid=c.cid')
            ->join('customs d on c.customid=d.customid')
            ->field("d.namechinese name,a.cardno cardno,
                                    a.tradepoint tradepoint,a.placeddate placeddate,
                                    a.placedtime placedtime")
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $this->assign('page', $page);
        $this->assign('list', $result);
        $this->display();
    }

    //-----计算在eapp上兑换通宝数量--------
    /**
     * @param $panters  */
    private function csmOnapp($panters)
    {
        $where['termposno'] = "00000001";
        $where['panterid'] = array("in", $panters);
        $where['tradetype'] = '00';
        $where['tradeamount'] = '0';
        $where['flag'] = '0';
        $wastebooks = M('trade_wastebooks');

        $result = $wastebooks->field("sum(tradepoint) amount")->where($where)->find();

        if ($result['amount']) {
            $number = $result['amount'];
        } else {
            $number = 0;
        }
        return $number;
    }

    /*
     * @content 通宝发行及兑换月度分析*/
    public function monthAnalyze()
    {

        $calYear = range(201604, 201612, 1);
        array_push($calYear, 201701);
        array_push($calYear, 201702);
        array_push($calYear, 201703);
        $coinAccount = M('coin_account');
        $coinConsume = M('coin_consume');

        $re_arr = array();
        foreach ($calYear as $year) {
            $getre = $coinAccount->field('sum(rechargeamount) amount')->where("placeddate like '{$year}%'")->find();
            $send_amount = $getre['amount'];

            foreach ($calYear as $year2) {
                $getre2 = $coinConsume->alias("a")
                    ->join("coin_account b on a.coinid=b.coinid")
                    ->field("sum(a.amount) amount")
                    ->where("a.placeddate like '{$year2}%' and b.placeddate like '{$year}%'")
                    ->find();
                $consume_amount = $getre2['amount'];
                $re_arr[$year]['consume'][$year2] = number_format($consume_amount, 2, '.', '');
            }
            $re_arr[$year]['send'] = number_format($send_amount, 2, '.', '');
            $re_arr[$year]['year'] = $year;
        }
        $this->assign("data", $re_arr);

        $this->display();
    }

    /*
     * @content 新增报表物业通宝消费动向*/
    public function coinTenement()
    {
        require_once "./Public/tenement.php";
        $coinConsume = M('coin_consume');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $cuname  = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $issuepname  = trim(I('get.issuepname', '')); //发行商户名称
        $consumepname  = trim(I('get.consumepname', '')); //受理商户名称

        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        switch ($consumepname) {
            case "河南建业物业管理有限公司":
                $where['p.namechinese'] = "河南建业物业管理有限公司";
                $map['consumepname'] = "河南建业物业管理有限公司";
                break;
            case "":
                $where['p.namechinese'] = array('like', '%物业%');
                $map['consumepname'] = "物业";
                break;
            default:
                $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
                $map['consumepname'] = $consumepname;
                break;
        }

        if ($this->panterid != 'FFFFFFFF') {
            $where1['cc.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,c.cardno,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.remindamount remindamount,tw.termposno';
        $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)->count();
        $sum = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->where($where)->sum('cc.amount');
        $p = new \Think\Page($count, 15);
        //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
            ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();

        session('coinTenement', $where);

        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        foreach ($list as $k => $v) {
            $list[$k]['tenement'] = $teneinfo[$v['termposno']];
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $sum = number_format($sum, '2', '.', '');
        $this->assign('sum', $sum);
        $this->assign('count', $count);
        $this->display();
    }
    public function coinTenement_excel()
    {
        require_once "./Public/tenement.php";
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['coinTenement'])) {
            $consumeCon = session('coinTenement');
            foreach ($consumeCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinConsume = M('coin_consume');
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,c.cardno,cu.namechinese cuname,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.remindamount remindamount,tw.termposno';
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
            ->order('cc.placeddate desc')->where($where)->select();
        //$strlist="会员姓名,卡号,消费金额,余额,订单编号,受理机构,受理时间,发行机构,发行时间,订单状态,状态";
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        $cellMerge = "A1:M3";
        $titleName = '通宝兑换报表';
        $this->setTitle($cellMerge, $titleName);
        $startCell = 'A4';
        $endCell = 'M4';
        $headerArray = array(
            '会员姓名', '卡号', '消费金额', '余额', '订单编号', '终端号', '受理机构',
            '受理时间', '发行机构', '缴费小区'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $setWidth = array(12, 25, 12, 12, 25, 40, 25, 30, 25, 12, 12);
        $this->setWidth($setCells, $setWidth);
        $total = 0;
        $j = 5;
        foreach ($list as $key => $val) {

            $total += $val['amount'];
            $objSheet->setCellValue('A' . $j, "'" . $val['cuname'])->setCellValue('B' . $j, "'" . $val['cardno'])
                ->setCellValue('C' . $j, $val['amount'])->setCellValue('D' . $j, $val['remindamount'])
                ->setCellValue('E' . $j, "'" . $val['tradeid'])->setCellValue('F' . $j, "'" . $val['termposno'])
                ->setCellValue('G' . $j, "'" . $val['consumepname'])
                ->setCellValue('H' . $j, "'" . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime'])))
                ->setCellValue('I' . $j, "'" . $val['issuepname'])->setCellValue('J' . $j, $teneinfo[$val['termposno']]);
            $j++;
        }
        $objSheet->getStyle('B' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('B' . $j, '合计金额:');
        $objSheet->setCellValue('C' . $j, $total);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '通宝物业费兑换报表.xls'); //输出到浏览器
        $objWriter->save("php://output");
    }

    protected function recordErr($data)
    {
        $a = $_SERVER['REMOTE_ADDR'];
        $month = date('Ym', time());
        $filename = date('Ymd', time()) . '.log';
        $time = date('Y-m-d H:i:s');
        $string = 'ip:' . $a . "\r\n时间：" . $time . "\r\n数据：" . $data . "\r\n\r\n";
        $path = PUBLIC_PATH . 'logs/coinCalculate/' . $month . '/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $filename, $string, FILE_APPEND);
    }

    //-----------------------------------------------------------------------通宝余额报表----------------------------------
    public function coinBalance()
    {
        //
        $map['linktel'] = trim(I('get.linktel', ''));
        $map['name']    = trim(I('get.name', ''));
        $map['cardno']  = trim(I('get.cardno', ''));
        $map['enddate'] = trim(I('get.enddate', ''));
        $map['balance'] = trim(I('get.balance', ''));
        $map['area_zone'] = trim(I('get.area_zone', ''));

        if ($map['linktel'] != '') $where['cu.linktel'] = $map['linktel'];
        if ($map['name'] != '')    $where['cu.namechinese']  = ['like', '%' . $map['name'] . '%'];
        if ($map['cardno'] != '')  $where['ca.cardno']       = $map['cardno'];
        if ($map['enddate'] != '') $where['coin.enddate']    = ['elt', str_replace('-', '', $map['enddate'])];
        if ($map['balance'] != '') {
            switch ($map['balance']) {
                case '1':
                    $where['bsub.total']    = [['egt', 0], ['elt', 1000]];
                    break;
                case '2':
                    $where['bsub.total']    = [['gt', 1000], ['elt', 5000]];
                    break;
                case '3':
                    $where['bsub.total']    = [['gt', 5000], ['elt', 10000]];
                    break;
                case '4':
                    $where['bsub.total']    = ['gt', 10000];
                    break;
            }
        }
        if ($map['area_zone'] != '') $where['p.cityid']  = $map['area_zone'];
        session('coin_balance', $where);
        $model = new Model();
        $mainSubQuery   = $model->table('coin_account')->field("distinct(cardid)")->buildSql();;

        $balanceSubQuery = $model->table('coin_account')->field('cardid,sum(remindamount) total')
            ->group('cardid')->buildSql();
        $field          = "bsub.total,ca.cardno,coin.rechargeamount,coin.remindamount,coin.enddate,cu.customid,cu.linktel,cu.namechinese,p.namechinese pname";
        $count          =  $model->table($mainSubQuery . " sub")
            ->join('left join ' . $balanceSubQuery . ' bsub on bsub.cardid=sub.cardid')
            ->join('left join coin_account coin on coin.cardid=sub.cardid')
            ->join('left join cards ca on ca.customid=sub.cardid')
            ->join('left join customs_c cc on cc.cid=ca.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->count();
        $page           = new Page($count, 10, $map);
        $list           = $model->table($mainSubQuery . " sub")
            ->join('left join ' . $balanceSubQuery . ' bsub on bsub.cardid=sub.cardid')
            ->join('left join coin_account coin on coin.cardid=sub.cardid')
            ->join('left join cards ca on ca.customid=sub.cardid')
            ->join('left join customs_c cc on cc.cid=ca.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->limit($page->firstRow, $page->listRows)->select();
        foreach ($list as $key => $val) {
            $val['total'] = bcadd($val['total'], 0, 2);
            $val['rechargeamount'] = bcadd($val['rechargeamount'], 0, 2);
            $val['remindamount'] = bcadd($val['remindamount'], 0, 2);
            $list[$key] = $val;
        }

        $area = M('city')->where(['provinceid' => '01'])->field('cityid,cityname')->order('cityid desc')->select();
        foreach ($area as $k => $v) {
            $v['cityid'] = (string)trim($v['cityid']);
            $area[$k] = $v;
        }
        $this->assign('list', $list);
        $this->assign('map', $map);
        $this->assign('page', $page->show());
        $this->assign('area', $area);
        $this->assign('balancelist', ['1' => '0----1000', '2' => '1000-5000', '3' => '5000-10000', '4' => '10000以上']);
        $this->display();
    }
    public function coinBlalanceExcel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $where  = session('coin_balance');
        $model = new Model();
        $mainSubQuery   = $model->table('coin_account')->field("distinct(cardid)")->buildSql();;

        $balanceSubQuery = $model->table('coin_account')->field('cardid,sum(remindamount) total')
            ->group('cardid')->buildSql();
        $field          = "bsub.total,ca.cardno,coin.rechargeamount,coin.remindamount,coin.enddate,cu.linktel,cu.namechinese,p.namechinese pname";
        $list           = $model->table($mainSubQuery . " sub")
            ->join('left join ' . $balanceSubQuery . ' bsub on bsub.cardid=sub.cardid')
            ->join('left join coin_account coin on coin.cardid=sub.cardid')
            ->join('left join cards ca on ca.customid=sub.cardid')
            ->join('left join customs_c cc on cc.cid=ca.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->join('left join panters p on p.panterid=coin.panterid')
            ->where($where)->field($field)->select();
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        $cellMerge = "A1:H3";
        $titleName = '通宝兑换报表';
        $this->setTitle($cellMerge, $titleName);
        $startCell = 'A4';
        $endCell = 'H4';
        $headerArray = array('会员姓名', '手机号', '卡号', '通宝发行金额', '通宝剩余额度', '通宝总额', '有效期', '项目名称');
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $setWidth = array(30, 20, 25, 12, 12, 12, 12, 30);
        $this->setWidth($setCells, $setWidth);
        $total = 0;
        $j = 5;
        foreach ($list as $key => $val) {

            $total += $val['amount'];
            $objSheet->setCellValue('A' . $j, $val['namechinese'])->setCellValue('B' . $j, $val['linktel'])
                ->setCellValue('C' . $j, "'" . $val['cardno'])
                ->setCellValue('D' . $j, $val['rechargeamount'])->setCellValue('E' . $j, $val['remindamount'])
                ->setCellValue('F' . $j, $val['total'])
                ->setCellValue('G' . $j, "'" . $val['enddate'])->setCellValue('H' . $j, $val['pname']);
            $j++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '通宝余额报表.xls'); //输出到浏览器
        $objWriter->save("php://output");
    }

    //通宝过期消费报表
    public function coinExpired()
    {
        $map['linktel'] = trim(I('get.linktel', ''));
        $map['name']    = trim(I('get.name', ''));
        $map['cardno']  = trim(I('get.cardno', ''));
        $map['startdate'] = trim(I('get.startdate', ''));
        $map['enddate'] = trim(I('get.enddate', ''));

        if ($map['startdate'] == '') $map['startdate'] = date('Y-m-d');

        if ($map['linktel'] != '') $where['cu.linktel'] = $map['linktel'];
        if ($map['name'] != '')    $where['cu.namechinese']  = ['like', '%' . $map['name'] . '%'];
        if ($map['cardno'] != '')  $where['tw.cardno']       = $map['cardno'];
        if ($map['enddate'] != '') {
            $where['coin.placeddate']    = [
                ['egt', str_replace('-', '', $map['startdate'])],
                ['elt', str_replace('-', '', $map['enddate'])]
            ];
        } else {
            $where['coin.placeddate']  = ['egt', str_replace('-', '', $map['startdate'])];
        }
        $where['tw.tradetype']   = '27';
        $field = "tw.placeddate,cu.namechinese,cu.linktel,tw.cardno,coin.amount,p.namechinese pname,p1.namechinese sname";
        $count = M('trade_wastebooks')->alias('tw')
            ->join('left join coin_consume coin on coin.tradeid=tw.tradeid')
            ->join('left join customs_c cc on cc.cid=tw.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->join('left join coin_account coa on coa.coinid=coin.coinid')
            ->join('left join panters p on p.panterid=coa.panterid')
            ->join('left join panters p1 on p1.panterid=tw.panterid')
            ->where($where)->count();
        $page  = new Page($count, 10, $map);
        $list = M('trade_wastebooks')->alias('tw')
            ->join('left join coin_consume coin on coin.tradeid=tw.tradeid')
            ->join('left join customs_c cc on cc.cid=tw.customid')
            ->join('left join customs cu on cu.customid=cc.customid')
            ->join('left join coin_account coa on coa.coinid=coin.coinid')
            ->join('left join panters p on p.panterid=coa.panterid')
            ->join('left join panters p1 on p1.panterid=tw.panterid')
            ->where($where)->limit($page->firstRow, $page->listRows)
            ->field($field)->select();
        $this->assign('list', $list);
        $this->assign('map', $map);
        $this->assign('page', $page->show());
        $this->display();
    }

    /*
	 * @author  wanqk
	 * @date    2018-07-28
	 * @content 商户应交备付金计算
	 * @formula (截止到当前发行通宝金额 - 截止当前已经兑换)*0.2 + 截止当前已经兑换 - 已收到备付金总额
	 * @param   $panterid issue panterid
	 */
    private function newProvisions($panterid, $date = null, $sdate = null)
    {
        if (is_null($date)) {
            $nowtime = date('Ymd', time()); //现在时间
            $subTime = $nowtime; //获取要查询的时间
        } else {
            $nowtime = $date;
            $subTime = $date;
        }
        if (is_null($date)) {
            $sdate = "20160407";
        }
        $endLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day')); //上个月最后一天
        $startLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 month')); //上个月第一天

        $model = new Model();

        $map['panterid'] = $panterid;
        $map['placeddate'] = ['elt', $nowtime];
        $issueTotalAmount = $model->table('coin_account')
            ->where($map)
            ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];


        if ($issueTotalAmount != 0) {

            $last['panterid']  = $panterid;
            $last['placeddate'] = ['elt', $endLastMonth];

            //截止上月
            $issueLastAmount  =   $model->table('coin_account')
                ->where($last)
                ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];
            $settle['ca.panterid'] = $panterid;
            $settle['cc.placeddate'] = ['elt', $nowtime];
            $settleTotalAmount = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settle)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount']; //累计已经兑换金额
            $settleLast['ca.panterid'] = $panterid;
            $settleLast['cc.placeddate']  = ['elt', $endLastMonth];
            $settleLastAmount = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settleLast)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount']; //累计已经兑换金额
        } else {
            $settleTotalAmount = 0;
            $issueLastAmount = 0;
        }
        //已收到备付金总额 = 截至上月通宝发行总金额-截止上月已经兑换）*0.2+截止上月已经兑换
        $provisionAmount   = bcadd(bcmul(bcsub($issueLastAmount, $settleLastAmount, 2), 0.2, 2), $settleLastAmount, 2);
        //(截止到当前发行通宝金额 - 截止当前已经兑换)*0.2 + 截止当前已经兑换
        $provisionNow     = bcadd(bcmul(bcsub($issueTotalAmount, $settleTotalAmount, 2), 0.2, 2), $settleTotalAmount, 2);
        $value   = bcsub($provisionNow, $provisionAmount, 2);
        $str     = "目前发行:$issueTotalAmount, 截止上月发行:$issueLastAmount,目前兑换:$settleTotalAmount,截止上月兑换:$settleLastAmount";
        //返回通宝过期消费商户 目前 已经兑换的金额
        $settle['cc.panterid'] = '00004616';
        $settle['ca.panterid'] = $panterid;
        $settle['cc.placeddate'] = ['elt', $nowtime];
        $expire = $model->table('coin_account')->alias('ca')
            ->join('left join coin_consume cc on cc.coinid=ca.coinid')
            ->where($settle)
            ->field('nvl(sum(cc.amount),0) amount')->find()['amount']; //累计已经兑换金额
        if (!is_null($sdate)) {
            $where = 'cc.panterid=00004616 and ca.panterid =' . $panterid . ' and cc.placeddate<=' . $nowtime . ' and cc.placeddate>=' . $sdate;
        }
        $expire2 = $model->table('coin_account')->alias('ca')
            ->join('left join coin_consume cc on cc.coinid=ca.coinid')
            ->where($where)
            ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];

        return ['val' => $value, 'msg' => $str, 'expire' => $expire, 'expire2' => $expire2];
    }

    public function jycoinService()
    {
        $year    = trim(I('get.year', ''));
        $quarter = trim(I('get.quarter', ''));
        $name    = trim(I('get.name', ''));
        $pname    = trim(I('get.pname', ''));
        $year != '' || $year = '2018';
        $quarter != '' || $quarter = '1';
        if ($name != '') {
            $where['p.nameenglish'] = ['like', '%' . $name . '%'];
            $condition['name'] = $name;
            $this->assign('name', $name);
        }
        if ($pname != '') {
            $where['p.namechinese'] = ['like', '%' . $pname . '%'];
            $condition['pname'] = $pname;
            $this->assign('pname', $pname);
        }

        $model = new Model();
        //项目名称

        $condition['quarter'] = $quarter;
        $condition['year']    = $year;
        switch ($quarter) {
            case '2':
                $map['placeddate'] = [
                    ['egt', date('Ymd', strtotime($year . '0401'))],
                    ['elt', date('Ymd', strtotime($year . '0630'))]
                ];
                break;
            case '3':
                $map['placeddate'] = [
                    ['egt', date('Ymd', strtotime($year . '0701'))],
                    ['elt', date('Ymd', strtotime($year . '0930'))]
                ];
                break;
            case '4':
                $map['placeddate'] = [
                    ['egt', date('Ymd', strtotime($year . '1001'))],
                    ['elt', date('Ymd', strtotime($year . '1231'))]
                ];
                break;

            default:
                $map['placeddate'] = [
                    ['egt', date('Ymd', strtotime($year . '0101'))],
                    ['elt', date('Ymd', strtotime($year . '0331'))]
                ];
                break;
        }
        session('jycoin_service_map', $map);
        $subQuery = $model->table('coin_account')->where($map)
            ->group('panterid')->field('sum(rechargeamount) issueamount,panterid')
            ->buildSql();
        $count   = $model->table($subQuery . " main")
            ->join('left join panters p on p.panterid=main.panterid')
            ->where($where)->count();
        $page = new Page($count, 10, $condition);
        session('jycoin_service_where', $where);
        $list   = $model->table($subQuery . " main")
            ->join('left join panters p on p.panterid=main.panterid')
            ->field('p.namechinese pname,p.nameenglish name,main.issueamount')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($list as $key => $val) {
            $val['service'] = bcmul($val['issueamount'], 0.05, 2);
            $list[$key]     = $val;
        }
        $this->assign('year', $year);
        $this->assign('quarter', $quarter);
        $this->assign('years', ['2018', '2017', '2016']);
        $this->assign('quarters', ['1' => '第一季度', '2' => '第二季度', '3' => '第三季度', '4' => '第四季度']);
        $this->assign('page', $page->show());
        $this->assign('list', $list);
        $this->display('service');
    }

    public function serviceExcel()
    {
        $model = new Model();
        $map   = session('jycoin_service_map');
        $where = session('jycoin_service_where');
        $subQuery = $model->table('coin_account')->where($map)
            ->group('panterid')->field('sum(rechargeamount) issueamount,panterid')
            ->buildSql();
        $list   = $model->table($subQuery . " main")
            ->join('left join panters p on p.panterid=main.panterid')
            ->field('p.namechinese pname,p.nameenglish name,main.issueamount')
            ->where($where)
            ->select();
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        $cellMerge = "A1:E3";
        $titleName = $map['placeddate'][0][1] . '---' . $map['placeddate'][1][1] . '通宝服务费';
        $this->setTitle($cellMerge, $titleName);
        $startCell = 'A4';
        $endCell = 'E4';
        $headerArray = array('商户名', '项目名', '已经发行通宝', '费率', '应交服务费');
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E');
        $setWidth = array(30, 20, 12, 10, 12);
        $this->setWidth($setCells, $setWidth);
        $total = 0;
        $j = 5;
        foreach ($list as $key => $val) {

            $val['service'] = bcmul($val['issueamount'], 0.05, 2);
            $objSheet->setCellValue('A' . $j, $val['pname'])->setCellValue('B' . $j, $val['name'])
                ->setCellValue('C' . $j, $val['issueamount'])
                ->setCellValue('D' . $j, '5%')->setCellValue('E' . $j, $val['service']);

            $j++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '通宝服务费.xls'); //输出到浏览器
        $objWriter->save("php://output");
    }
}
