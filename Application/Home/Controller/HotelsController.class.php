<?php
namespace Home\Controller;

use Think\Controller;
use Think\Model;

class HotelsController extends CommonController
{
    //异地消费报表
    public function yuaninterConsume()
    {
        $model = new model();
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p1.panterid'] = $this->panterid;
            $where1['p1.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        $where['_string'] = "c.panterid!=tw.panterid and c.panterid!=p.parent";
        $where['tw.flag'] = 0;
        $where['tw.tradetype'] = array('in', '00,02,13,17,21');
        $start = trim(I('get.startdate', ''));        //开始日期
        $end = trim(I('get.enddate', ''));            //结束日期
        $cardno = trim(I('get.cardno', ''));            //卡号
        $customid = trim(I('get.customid', ''));        //会员编号
        $cuname = trim(I('get.cuname', ''));            //会员名称
        $panterid = trim(I('get.panterid', ''));        //商户编号
        $pantername = trim(I('get.pantername', ''));    //商户名称
        $tradeid = trim(I('get.tradeid', ''));            //交易编号
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pantername = $pantername == "商户名称" ? "" : $pantername;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['tw.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($cardno != '') {
            $where['tw.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
            $this->assign('customid', $customid);
            $map['customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pantername != '') {
            $where['p1.namechinese'] = array('like', '%' . $pantername . '%');
            $this->assign('pantername', $pantername);
            $map['pantername'] = $pantername;
        }
        if ($tradeid != '') {
            $where['tw.tradeid'] = $tradeid;
            $this->assign('tradeid', $tradeid);
            $map['tradeid'] = $tradeid;
        }
        $field = 'tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid';
        $count = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field('sum(tradeamount) amount_sum')
            ->where($where)->find();
        $this->assign('amount_sum', $amount_sum['amount_sum']);

        $p = new \Think\Page($count, 10);
        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('tw.placeddate')->select();
        // echo $model->getLastSql();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        session('intConexcel', $where);
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $interConsume);
        $this->display();
    }

    //酒店异地消费统计页面
    public function interConsume()
    {
        $this->display();
    }

    //酒店异地消费统计报表数据
    public function getInterConsumeList()
    {
        $model = new model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $where['_string'] = "c.panterid!=tw.panterid and c.panterid!=p.parent";
        $where['tw.flag'] = 0;
        $where['tw.tradetype'] = array('in', '00,02,13,17,21');
        $start = trim(I('get.startdate', ''));       //开始日期
        $end = trim(I('get.enddate', ''));           //结束日期
        $cardno = trim(I('get.cardno', ''));         //卡号
        $customid = trim(I('get.customid', ''));     //会员编号
        $cuname = trim(I('get.cuname', ''));         //会员名称
        $panterid = trim(I('get.panterid', ''));     //商户编号
        $pantername = trim(I('get.pantername', '')); //商户名称
        $tradeid = trim(I('get.tradeid', ''));           //交易编号
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pantername = $pantername == "商户名称" ? "" : $pantername;
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['tw.cardno'] = array('in', $cardno);
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
        }
        if ($pantername != '') {
            $where['p1.namechinese'] = array('like', '%' . $pantername . '%');
        }
        if ($tradeid != '') {
            $where['tw.tradeid'] = $tradeid;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p1.panterid'] = $this->panterid;
            $where1['p1.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        session('intConexcel', $where);
        $field = 'tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid';
        $total = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->count();

        $amount_sum = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->where($where)->getField('sum(tradeamount) amount_sum');
        //正常交易表--卡号表--商户表--会员编号对应表--会员信息表--商户表
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->page($page, $pageSize)->order('tw.placeddate')->select();
        foreach ($results as $k => $v) {
            $v['placedatime'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['tradeamount'] = floatval($v['tradeamount']);
            $v['tradepoint'] = floatval($v['tradepoint']);
            $v['addpoint'] = floatval($v['addpoint']);
            $rows[] = $v;
        }
        $amount_sum = array('cardno' => '合计金额：', 'cuid' => $amount_sum);
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店异地消费统计导出EXCEL
     */
    function iterConsume_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $intConMap = session('intConexcel');
        foreach ($intConMap as $key => $value) {
            $where[$key] = $value;
        }

        $field = 'tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid';
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('left join __CARDS__ c on c.cardno=tw.cardno')
            ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->order('tw.placeddate')->select();
        foreach ($results as $k => $v) {
            $v['placedatime'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['tradeamount'] = floatval($v['tradeamount']);
            $v['tradepoint'] = floatval($v['tradepoint']);
            $v['addpoint'] = floatval($v['addpoint']);
            $rows[] = $v;
        }
        $tradeamounts = array_column($results, 'tradeamount');
        foreach ($tradeamounts as $key => $val) {
            //bcadd() 2个任意精度数字相加
            $sum = bcadd($sum, $val, 2);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:L3";
        $titleName = '酒店异地消费统计报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'L4';
        $headerArray = array('卡号', '会员编号', '会员名称',
            '交易号', '交易时间', '交易金额',
            '交易积分', '交易劵', '产生积分',
            '商户编号', '卡归属商户', '消费商户'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
        $setWidth = array(25, 15, 25, 25, 25, 12, 12, 15, 12, 15, 50, 50);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['cuid'])->setCellValue('C' . $j, "'" . $val['cuname'])
                ->setCellValue('D' . $j, $val['tradeid'])->setCellValue('E' . $j, "'" . $val['placedatime'])->setCellValue('F' . $j, "'" . $val['tradeamount'])
                ->setCellValue('G' . $j, "'" . $val['tradepoint'])->setCellValue('H' . $j, "'" . $val['customid'])->setCellValue('I' . $j, "'" . $val['addpoint'])
                ->setCellValue('J' . $j, "'" . $val['panterid'])->setCellValue('K' . $j, $val['pname1'])->setCellValue('L' . $j, $val['pname']);
            $j++;
        }
        $objSheet->getStyle('E' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('E' . $j, '合计金额:');
        $objSheet->setCellValue('F' . $j, "'" . $sum);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店异地消费统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    /**
     * 酒店异地消费统计导出EXCEL
     */
    function yuaniterConsume_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $intConMap = session('intConexcel');
        foreach ($intConMap as $key => $value) {
            $where[$key] = $value;
        }
        $amount_sum = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field('sum(tradeamount) amount_sum')
            ->where($where)->find();
        $field = 'tw.*,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid';
        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('left join __CARDS__ c on c.cardno=tw.cardno')
            ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->order('tw.placeddate')->select();
        //$strlist="卡号,会员编号,会员名称,交易号,交易时间,交易金额,交易积分,交易劵,产生积分,卡归属商户,消费商户";
        $strlist = "卡号,会员编号,会员名称,联系方式,交易号,交易时间,交易金额,交易积分,交易劵,产生积分,卡归属商户,消费商户";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($interConsume as $key => $val) {
            $val['cuname'] = iconv('utf-8', 'gbk', $val['cuname']);
            $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
            $val['pname1'] = iconv('utf-8', 'gbk', $val['pname1']);
            //$strlist.=$val['cardno']."\t,".$val['cuid']."\t,".$val['cuname'];
            $strlist .= $val['cardno'] . "\t," . $val['cuid'] . "\t," . $val['cuname'] . "\t," . $val['linktel'];
            $strlist .= ',' . $val['tradeid'] . "\t," . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
            $strlist .= "\t," . floatval($val['tradeamount']) . "," . $val['tradepoint'] . ',' . $val['quanid'] . "\t,";
            $strlist .= $val['addpoint'] . ',' . $val['pname1'] . ',' . $val['pname'] . "\n";
        }
        //array_unshift($interConsume_list,array('卡号','会员编号','会员名称','交易号','交易时间','交易金额','交易积分','交易劵','产生积分','卡归属商户','消费商户'));
        $filename = '酒店异地消费统计' . date("YmdHis");
        $filename = iconv("utf-8", "gbk", $filename);
        unset($interConsume);
        $strlist .= ',,,,,' . $amount_sum['amount_sum'] . "\t,,,,,\n";
        $this->load_csv($strlist, $filename);
    }

    //酒店劵消费报表
    function yuanticketConsume()
    {
        $model = new model();
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $panterid = trim(I('get.panterid', ''));
        $pname = trim(I('get.pname', ''));
        $quanid = trim(I('get.quanid', ''));
        $quanname = trim(I('get.quanname', ''));
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pname = $pname == "商户名称" ? "" : $pname;
        $quanid = $quanid == "营销劵编号" ? "" : $quanid;
        $quanname = $quanname == "营销劵名称" ? "" : $quanname;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['tw.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        if ($quanid != '') {
            $where['tw.quanid'] = $quanid;
            $this->assign('quanid', $quanid);
            $map['quanid'] = $quanid;
        }
        if ($quanname != '') {
            $where['q.quanname'] = array('like', '%' . $quanname . '%');
            $this->assign('quanname', $quanname);
            $map['quanname'] = $quanname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['panterid'] = $this->panterid;
            $where1['parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $panters = D('panters')->where($where1)->field('panterid')->select();
            $panterId = array();
            foreach ($panters as $key => $val) {
                $panterId[] = $val['panterid'];
            }
            $where['tw.panterid'] = array('in', $panterId);
        } else {
            $this->assign('is_admin', 1);
        }
        $wheres = $where;
        $wheres['tw.tradetype'] = '02';
        // $where['tw.tradetype']='02';
        // $where['p.hysx']='酒店';
        $field = 'tw.panterid,tw.cardno,p.namechinese pname,tw.placeddate,tw.quanid,q.quanname,';
        $field .= 'count(tw.tradeid) count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.panterid,tw.cardno,p.namechinese,tw.placeddate,tw.quanid,q.quanname,q.amount';
        $subQuery = "(select sum(tradeamount) as tradeamount,cardno,panterid,quanid,placeddate,customid ";
        $subQuery .= "from trade_wastebooks where tradetype='02' group by cardno,panterid,placeddate,quanid,customid)";
        $count = $model->table($subQuery)->alias('tw')
            ->join('left join __PANTERS__ p on tw.panterid=p.panterid')
            ->join('left join __QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->count();
        $p = new \Think\Page($count, 10);
        $ticketConsume_list = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->group($group)->order('tw.placeddate desc')->select();
        // dump($ticketConsume_list);
        //echo $model->getLastSql();
        session('ticketConsume_excel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('count', $count);
        $this->assign('list', $ticketConsume_list);
        $this->assign('page', $page);
        $this->display();
    }

    //酒店营销劵交易统计页面
    function ticketConsume()
    {
        $jytype=C('jytype');
        $this->assign('jytype',$jytype);
        $this->display();
    }

    //酒店营销劵交易统计数据
    function getTicketConsumeList()
    {
        $model = new model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $panterid = trim(I('get.panterid', ''));
        $pname = trim(I('get.pname', ''));
        $quanid = trim(I('get.quanid', ''));
        $quanname = trim(I('get.quanname', ''));
        $cardno = trim(I('get.cardno', ''));
        $Oname = trim(I('get.Oname', ''));
        $tradetype = trim(I('get.tradetype', ''));
        $vipcard = trim(I('get.vipcard', ''));
        $tradeid = trim(I('get.tradeid', ''));
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['tw.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['tw.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($quanid != '') {
            $where['tw.quanid'] = $quanid;
        }
        if ($quanname != '') {
            $where['q.quanname'] = array('like', '%' . $quanname . '%');
        }
        if ($cardno != '') $where['tw.cardno'] = $cardno;
        if ($Oname != '') $where['card.panterid'] = $Oname;
        if ($tradeid != '') $where['tw.tradeid'] = $tradeid;
        if ($tradetype != ''&&$tradetype!='-1'){
            $where['tw.tradetype'] = $tradetype;
        }else{
            $where['tw.tradetype']=array('in','02,23');
        }
        if ($vipcard ==1) $where['tw.cardno'] = array('like','68823710888%');
        // $where['p.hysx']='酒店';
        $where['tw.flag'] = array('in','0,3');
        session('ticketConsume_excel', $where);
        $field = 'tw.cardno,tw.panterid,tw.placeddate,tw.placedtime,tw.quanid,tw.customid,tw.flag,p.namechinese pname,p.hysx,tw.termposno,tw.tradepoint,tw.addpoint,';
        $field .= 'tw.tradetype,tw.tradeid,q.quanname,custom.namechinese cuname,p1.namechinese pname1,';
        $field .= 'count(tw.tradeid) count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.cardno,tw.panterid,tw.placeddate,tw.placedtime,tw.quanid,tw.customid,tw.flag,p.namechinese,p.hysx,tw.termposno,tw.tradepoint,tw.addpoint,tw.tradetype,tw.tradeid,q.quanname,';
        $group .= 'custom.namechinese,p1.namechinese,tw.tradeamount,q.amount';
        $total = $model->table('trade_wastebooks')->alias('tw')
            ->join('left join __PANTERS__ p on tw.panterid=p.panterid')
            ->join('left join __QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->count();

        //正常交易表--营销劵表--商户表
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)->page($page, $pageSize)
            ->group($group)->order('tw.placeddate desc,tw.placedtime desc')->select();
        foreach ($results as $k => $v) {
            switch ($v['flag']) {
                case 0:
                    $v['flag'] = '正常';
                    break;
                case 1:
                    $v['flag'] = '冲正';
                    break;
                case 2:
                    $v['flag'] = '退货';
                    break;
                case 3:
                    $v['flag'] = '撤销';
                    break;
            }
            switch ($v['tradetype']) {
                case 00:
                    $v['tradetype'] = '至尊卡消费';
                    break;
                case 02:
                    $v['tradetype'] = '劵消费';
                    break;
                case 04:
                    $v['tradetype'] = '消费撤销';
                    break;
                case 08:
                    $v['tradetype'] = '券消费冲正';
                    break;
                case 13:
                    $v['tradetype'] = '现金消费';
                    break;
                case 14:
                    $v['tradetype'] = '积分充值';
                    break;
                case 17:
                    $v['tradetype'] = '预授权';
                    break;
                case 18:
                    $v['tradetype'] = '预授权冲正';
                    break;
                case 19:
                    $v['tradetype'] = '预授权撤销';
                    break;
                case 21:
                    $v['tradetype'] = '预授权完成';
                    break;
                case 22:
                    $v['tradetype'] = '预授权完成冲正';
                    break;
                case 23:
                    $v['tradetype'] = '券消费撤销';
                    break;
                case 24:
                    $v['tradetype'] = '预授权完成撤销';
                    break;
            }
            $v['amount'] = floatval($v['amount']);
            $v['totalamount'] = $v['amount'] * $v['tradeamount'];
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 当日营销劵交易统计导出EXCEL
     */
    function ticketConsume_excel()
    {
        //header('content-type:text/html;charset=gbk');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $ticketConsumeMap = session('ticketConsume_excel');
        foreach ($ticketConsumeMap as $key => $value) {
            $where[$key] = $value;
        }
        $field = 'tw.cardno,tw.panterid,tw.placeddate,tw.placedtime,tw.quanid,tw.customid,tw.flag,p.namechinese pname,p.hysx,tw.termposno,tw.tradepoint,tw.addpoint,';
        $field .= 'tw.tradetype,tw.tradeid,q.quanname,custom.namechinese cuname,p1.namechinese pname1,';
        $field .= 'count(tw.tradeid) count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.cardno,tw.panterid,tw.placeddate,tw.placedtime,tw.quanid,tw.customid,tw.flag,p.namechinese,p.hysx,tw.termposno,tw.tradepoint,tw.addpoint,tw.tradetype,tw.tradeid,q.quanname,';
        $group .= 'custom.namechinese,p1.namechinese,tw.tradeamount,q.amount';
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)
            ->group($group)->order('tw.placeddate desc')->select();
        foreach ($results as $k => $v) {
            switch ($v['flag']) {
                case 0:
                    $v['flag'] = '正常';
                    break;
                case 1:
                    $v['flag'] = '冲正';
                    break;
                case 2:
                    $v['flag'] = '退货';
                    break;
                case 3:
                    $v['flag'] = '撤销';
                    break;
            }
            switch ($v['tradetype']) {
                case 00:
                    $v['tradetype'] = '至尊卡消费';
                    break;
                case 02:
                    $v['tradetype'] = '券消费';
                    break;
                case 04:
                    $v['tradetype'] = '消费撤销';
                    break;
                case 08:
                    $v['tradetype'] = '券消费冲正';
                    break;
                case 13:
                    $v['tradetype'] = '现金消费';
                    break;
                case 14:
                    $v['tradetype'] = '积分充值';
                    break;
                case 17:
                    $v['tradetype'] = '预授权';
                    break;
                case 18:
                    $v['tradetype'] = '预授权冲正';
                    break;
                case 19:
                    $v['tradetype'] = '预授权撤销';
                    break;
                case 21:
                    $v['tradetype'] = '预授权完成';
                    break;
                case 22:
                    $v['tradetype'] = '预授权完成冲正';
                    break;
                case 23:
                    $v['tradetype'] = '券消费撤销';
                    break;
                case 24:
                    $v['tradetype'] = '预授权完成撤销';
                    break;
            }
            $v['amount'] = floatval($v['amount']);
            $v['totalamount'] = $v['amount'] * $v['tradeamount'];
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:S3";
        $titleName = '酒店营销劵交易统计报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'S4';
        $headerArray = array('交易卡号', '会员编号', '会员名称',
            '会员所属机构', '交易状态', '商户编号',
            '商户名称', '交易日期', '交易时间',
            '终端号', '交易数量', '交易积分',
            '交易类型', '交易流水号号', '交易产生积分',
            '券编号', '券名称', '券单价', '券总价'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'D', 'G', 'H', 'N', 'Q', 'O');
        $setWidth = array(21, 40, 40, 10, 25, 30, 15);
        $this->setWidth($setCells, $setWidth);
        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("J" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['customid'])->setCellValue('C' . $j, $val['cuname'])
                ->setCellValue('D' . $j, $val['pname1'])->setCellValue('E' . $j, $val['flag'])->setCellValue('F' . $j, "'" . $val['panterid'])
                ->setCellValue('G' . $j, $val['pname'])->setCellValue('H' . $j, $val['placeddate'])->setCellValue('I' . $j, $val['placedtime'])
                ->setCellValue('J' . $j, "'" . $val['termposno'])->setCellValue('K' . $j, $val['tradeamount'])->setCellValue('L' . $j, $val['tradepoint'])
                ->setCellValue('M' . $j, "'" . $val['tradetype'])->setCellValue('N' . $j, "'" . $val['tradeid'])->setCellValue('O' . $j, $val['addpoint'])
                ->setCellValue('P' . $j, "'" . $val['quanid'])->setCellValue('Q' . $j, $val['quanname'])->setCellValue('R' . $j, $val['amount'])
                ->setCellValue('S' . $j, $val['totalamount']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店营销劵交易统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    /**
     * 当日营销劵交易统计导出EXCEL
     */
    function yuanticketConsume_excel()
    {
        //header('content-type:text/html;charset=gbk');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $ticketConsumeMap = session('ticketConsume_excel');
        foreach ($ticketConsumeMap as $key => $value) {
            $where[$key] = $value;
        }
        $field = 'tw.panterid,tw.cardno,p.namechinese pname,tw.placeddate,tw.quanid,q.quanname,';
        $field .= 'count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.panterid,tw.cardno,p.namechinese,tw.placeddate,tw.quanid,q.quanname,q.amount';
        $ticketConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->field($field)->group($group)->order('tw.placeddate desc')->select();
        $strlist = "商户编号,商户名称,交易卡号,交易日期,营销劵编号,营销劵名称,交易笔数,交易张数,营销劵单价,价值总额";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        $jzzmounts = 0;
        foreach ($ticketConsume as $key => $val) {
            $jzzmount = $val['amount'] * $val['tradeamount'];
            $jzzmounts += $jzzmount;
            $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
            $val['quanname'] = iconv('utf-8', 'gbk', $val['quanname']);
            $strlist .= $val['panterid'] . "\t," . $val['pname'] . "\t," . $val['cardno'] . "\t," . date('Y-m-d', strtotime($val['placeddate']));
            $strlist .= "\t," . $val['quanid'] . "\t," . $val['quanname'] . ',' . $val['count'] . ',' . floatval($val['tradeamount']);
            $strlist .= ',' . floatval($val['amount']) . "," . floatval($jzzmount) . "\n";
        }
        $filename = '当日营销劵交易统计' . date("YmdHis");
        $filename = iconv("utf-8", "gbk", $filename);
        //$this->load_excel($ticketConsume_list,$filename,array('A','D'),array('F','G'));
        unset($ticketConsume);
        $this->load_csv($strlist, $filename);
    }

    //售卡报表
    function yuansellCard()
    {
        $model = new Model();
        $where['a.tradeflag'] = array('in', '0,2');
        $where['b.description'] = array('in', array('后台充值', '至尊币购卡', '批量购卡'));
        //$where['a.flag']=1;
        $where['card.cardkind'] = '6882';
        $start = trim(I('get.startdate', ''));
        $end = trim(I('get.enddate', ''));
        $panterid = trim(I('get.panterid', ''));
        $pname = trim(I('get.pname', ''));
        $cardno = trim(I('get.cardno', ''));
        $username = trim(I('get.username', ''));
        $panterid = $panterid == "机构编号" ? "" : $panterid;
        $pname = $pname == "机构名称" ? "" : $pname;
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $username = $username == "操作员" ? "" : $username;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ca.activedate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ca.activedate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ca.activedate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['ca.activedate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($panterid != '') {
            $where['ca.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        if ($cardno != '') {
            $where['b.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
            $this->assign('username', $username);
            $map['username'] = $username;
        }
        //金额区间
        $s_e_price = $_GET['s_e_price'];
        $this->assign('s_e_price', $s_e_price);
        switch ($s_e_price) {
            case 1 :
                $where['b.amount'][] = array('lt', 1000);
                break;
            case 2 :
                $where['b.amount'][] = array('egt', 1000);
                $where['b.amount'][] = array('lt', 3000);
                break;
            case 3 :
                $where['b.amount'][] = array('egt', 3000);
                $where['b.amount'][] = array('elt', 5000);
                break;
            case 4 :
                $where['b.amount'][] = array('gt', 5000);
                break;
            default :
                break;
        }

        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        $field = 'c.customid,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        $count = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->count();

        $this->assign('count', $count);
        //卡激活表--卡表--操作员表--会员编号对应表--会员信息表--商户表--充值表--购卡/充值单
        $amount_sum = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field("sum(b.amount) amount_sum")->find();
        $this->assign('amount_sum', $amount_sum['amount_sum']);

        $p = new \Think\Page($count, 10);
        $recharge_list = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('__CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('b.card_purchaseid desc')->select();
        // echo $model->getLastSql();
        session('sexcel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('list', $recharge_list);
        $this->assign('page', $page);
        $this->display();
    }

    //售卡报表页面
    function sellCard()
    {
        $this->display();
    }

    //售卡报表数据
    function getSellCardList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');

        $start = I('get.startdate', '', 'strip_tags');
        $end = I('get.enddate', '', 'strip_tags');
        $cardno = trim(I('get.cardno', '', 'strip_tags'));
        $panterid = trim(I('get.panterid', '', 'strip_tags'));
        $pname = trim(I('get.pname', '', 'strip_tags'));
        $linktel = trim(I('get.linktel', '', 'strip_tags'));
        $username = trim(I('get.username', '', 'strip_tags'));
        $where['a.tradeflag'] = array('in', '0,2');
        $where['b.description'] = array('in', array('后台充值', '至尊币购卡', '批量购卡'));
        $where['card.cardkind'] = array('in', '6882,2081');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ca.activedate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ca.activedate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ca.activedate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['ca.activedate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($panterid != '') {
            $where['ca.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['b.cardno'] = array('in', $cardno);
        }
        if ($username != '') {
            $where['u.username'] = $username;
        }
        if ($linktel != '') {
            $where['c.linktel'] = $linktel;
        }

        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        session('sexcel', $where);
        $model = new Model();
        $field = 'c.customid,c.linktel,a.purchaseid,a.tradeflag,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        //卡激活表--卡表--操作员表--会员编号对应表--会员信息表--商户表--充值表--购卡/充值单
        $results = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('__CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->order('b.card_purchaseid desc')->page($page, $pageSize)->field($field)->select();
//echo $model->getLastSql()
        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['activedate'] . $v['activetime']));
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }

        $total = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)
            ->count();
        $amount_sum = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->getField("sum(b.amount) amount_sum");
        $amount_sum = array('panterid' => '合计金额：', 'pname' => $amount_sum);
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店售卡报表导出Excel
     */
    public function sellCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'c.customid,c.linktel,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        $smap = session('sexcel');
        foreach ($smap as $key => $val) {
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

        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['activedate'] . $v['activetime']));
            switch ($v['paymenttype']) {
                case 00:
                    $v['paymenttypes'] = '现金';
                    break;
                case 01:
                    $v['paymenttypes'] = '银行卡';
                    break;
                case 02:
                    $v['paymenttypes'] = '支票';
                    break;
                case 03:
                    $v['paymenttypes'] = '汇款';
                    break;
                case 04:
                    $v['paymenttypes'] = '网上支付';
                    break;
                case 05:
                    $v['paymenttypes'] = '转账';
                    break;
                case 06:
                    $v['paymenttypes'] = '内部转账';
                    break;
                case 07:
                    $v['paymenttypes'] = '赠送';
                    break;
                case 08:
                    $v['paymenttypes'] = '其他';
                    break;
            }
            $rows[] = $v;
        }

        $tradeamounts = array_column($results, 'amount');
        foreach ($tradeamounts as $key => $val) {
            //bcadd() 2个任意精度数字相加
            $sum = bcadd($sum, $val, 2);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '酒店售卡报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'K4';
        $headerArray = array('发卡机构编号', '发卡机构名称', '会员编号',
            '会员名称', '会员手机号', '卡号',
            '卡号类型编号', '激活时间', '卡初始金额',
            '操作员', '充值类型'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
        $setWidth = array(15, 48, 13, 13, 15, 25, 15, 25, 15, 15, 12, 18);

        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['panterid'])->setCellValue('B' . $j, $val['pname'])->setCellValue('C' . $j, "'" . $val['customid'])
                ->setCellValue('D' . $j, $val['cname'])->setCellValue('E' . $j, "'" . $val['linktel'])->setCellValue('F' . $j, "'" . $val['cardno'])
                ->setCellValue('G' . $j, "'" . $val['cardkind'])->setCellValue('H' . $j, $val['activation'])->setCellValue('I' . $j, $val['amount'])
                ->setCellValue('J' . $j, $val['username'])->setCellValue('K' . $j, $val['paymenttypes']);
            $j++;
        }
        $objSheet->getStyle('H' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('H' . $j, '合计金额:');
        $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $objSheet->setCellValue('I' . $j, $sum);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店售卡报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    /**
     * 酒店售卡报表导出Excel
     */
    public function yuansellCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'c.customid,a.purchaseid,a.tradeflag,a.paymenttype,b.Card_PurchaseId,b.cardno,';
        $field .= 'b.amount amount,ca.activedate,ca.activetime,ca.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,u.username';
        $smap = session('sexcel');
        foreach ($smap as $key => $val) {
            $where[$key] = $val;
        }
        $amount_sum = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('__CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('__CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field("sum(b.amount) amount_sum")->find();
        $slist = $model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
            ->join('left join __CARDS__ card on card.cardno=ca.cardno')
            ->join('left join __USERS__ u on u.userid=ca.userid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('left join __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __PANTERS__ p on p.panterid=ca.panterid')
            ->join('left join __CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();
        $strlist = "商户编号,商户名称,会员编号,会员名称,卡号,卡号类型编号,激活时间,卡初始金额,操作员";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($slist as $key => $sell_info) {
            $sell_info['pname'] = iconv('utf-8', 'gbk', $sell_info['pname']);
            $sell_info['cname'] = iconv('utf-8', 'gbk', $sell_info['cname']);
            $sell_info['username'] = iconv('utf-8', 'gbk', $sell_info['username']);
            $sell_info['paymenttype'] = iconv('utf-8', 'gbk', $sell_info['paymenttype']);
            $strlist .= $sell_info['panterid'] . "\t" . ',' . $sell_info['pname'];
            $strlist .= ',' . $sell_info['customid'] . "\t" . ',' . $sell_info['cname'] . ',';
            $strlist .= $sell_info['cardno'] . "\t" . ',' . $sell_info['cardkind'] . ',';
            $strlist .= date('Y-m-d H:i:s', strtotime($sell_info['activedate'] . $sell_info['activetime']));
            $strlist .= "\t" . ',' . floatval($sell_info['amount']) . ',' . $sell_info['username'] . ',' . "\n";
        }
        unset($slist);
        $strlist .= ',,,,,,,' . $amount_sum['amount_sum'] . "\t" . ',,' . "\n";
        $filename = '酒店售卡报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($strlist, $filename);
    }

    //充值报表
    function yuanrecharge()
    {
        $model = new Model();
        $field = 'c.customid,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        $statype = array('A' => '待激活', 'D' => '销卡', 'R' => '退卡', 'S' => '过期', 'N' => '新卡',
            'L' => '锁定', 'Y' => '正常卡', 'W' => '无卡', 'C' => '已出库', 'G' => '异常锁定');//卡状态
        //$where['a.tradeflag']=1;
        //$where['a.flag']=1;
        // $where['card.cardkind']=array('in','6882,2081');
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $panterid = trim(I('get.panterid', ''));//机构编号
        $pname = trim(I('get.pname', ''));//机构名称
        $cardno = trim(I('get.cardno', ''));//卡号
        $username = trim(I('get.username', ''));//操作员
        $purchaseid = trim(I('get.purchaseid', ''));//充值流水号
        $status = trim(I('get.status', ''));//卡状态
        $customid = trim(I('get.customid', ''));//会员编号
        $cname = trim(I('get.cname', ''));//会员名称
        $pname = $pname == "机构名称" ? "" : $pname;
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $purchaseid = $purchaseid == "充值流水号" ? "" : $purchaseid;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cname = $cname == "会员名称" ? "" : $cname;
        $username = $username == "操作员" ? "" : $username;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['b.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($panterid != '') {
            $where['b.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        if ($cardno != '') {
            $where['b.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
            $this->assign('username', $username);
            $map['username'] = $username;
        }
        if ($purchaseid != '') {
            $where['b.Card_PurchaseId'] = $purchaseid;
            $this->assign('purchaseid', $purchaseid);
            $map['purchaseid'] = $purchaseid;
        }
        if ($status != '') {
            $where['card.status'] = $status;
            $this->assign('status', $status);
            $map['status'] = $status;
        }
        if ($customid != '') {
            $where['c.customid'] = $customid;
            $this->assign('customid', $customid);
            $map['customid'] = $customid;
        }
        if ($cname != '') {
            $where['c.namechinese'] = array('like', '%' . $cname . '%');
            $this->assign('cname', $cname);
            $map['cname'] = $cname;
        }
        //金额区间
        $s_e_price = $_GET['s_e_price'];
        $this->assign('s_e_price', $s_e_price);
        switch ($s_e_price) {
            case 1 :
                $where['b.amount'][] = array('lt', 1000);
                break;
            case 2 :
                $where['b.amount'][] = array('egt', 1000);
                $where['b.amount'][] = array('lt', 3000);
                break;
            case 3 :
                $where['b.amount'][] = array('egt', 3000);
                $where['b.amount'][] = array('elt', 5000);
                break;
            case 4 :
                $where['b.amount'][] = array('gt', 5000);
                break;
            default :
                break;
        }

        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        $count = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            //->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            //->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field("sum(b.amount) amount_sum")->find();
        $this->assign('amount_sum', $amount_sum['amount_sum']);

        $p = new \Think\Page($count, 10);
        $recharge_list = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            //->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('b.card_purchaseid desc')->select();
        //echo $model->getLastSql();
        session('rexcel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('statype', $statype);
        $this->assign('list', $recharge_list);
        $this->assign('page', $page);
        $this->display();
    }

    //充值报表页面
    function recharge()
    {
        $this->display();
    }

    //充值报表数据
    public function getRechargeList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $where['b.flag'] = array('neq', 3);
        $where['card.cardkind'] = array('in', '6882,2081');
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $panterid = trim(I('get.panterid', ''));//机构编号
        $pname = trim(I('get.pname', ''));//机构名称
        $cardno = trim(I('get.cardno', ''));//卡号
        $username = trim(I('get.username', ''));//操作员
        $purchaseid = trim(I('get.cpurchaseid', ''));//充值流水号
        $status = trim(I('get.status', ''));//卡状态
        $customid = trim(I('get.customid', ''));//会员编号
        $cname = trim(I('get.cname', ''));//会员名称
        $linktel = trim(I('get.linktel', ''));//会员手机号
        $pname = $pname == "机构名称" ? "" : $pname;
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $purchaseid = $purchaseid == "充值流水号" ? "" : $purchaseid;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cname = $cname == "会员名称" ? "" : $cname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        $username = $username == "操作员" ? "" : $username;
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array('elt', $enddate);
        }
        if ($panterid != '') {
            $where['b.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['b.cardno'] = array('in', $cardno);
        }
        if ($linktel != '') {
            $where['c.linktel'] = $linktel;
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
        }
        if ($purchaseid != '') {
            $where['b.Card_PurchaseId'] = $purchaseid;
        }
        if ($status != '') {
            $where['card.status'] = $status;
        }
        if ($customid != '') {
            $where['c.customid'] = $customid;
        }
        if ($cname != '') {
            $where['c.namechinese'] = array('like', '%' . $cname . '%');
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        session('rexcel', $where);
        $model = new Model();
        $field = 'c.customid,c.linktel,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        $accounttype = array('00' => '金额', '01' => '至尊币', '02' => '劵', '03' => '理财产品', '04' => '积分');
        //充值表--卡表--商户表--会员编号对应表--会员信息表--操作员表
        $results = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->order('b.card_purchaseid desc')->page($page, $pageSize)->field($field)->select();

        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['amount'] = floatval($v['amount']);
            switch ($v['status']) {
                case A:
                    $v['status'] = '待激活';
                    break;
                case D:
                    $v['status'] = '销卡';
                    break;
                case R:
                    $v['status'] = '退卡';
                    break;
                case S:
                    $v['status'] = '过期';
                    break;
                case N:
                    $v['status'] = '新卡';
                    break;
                case L:
                    $v['status'] = '锁定';
                    break;
                case Y:
                    $v['status'] = '正常卡';
                    break;
                case W:
                    $v['status'] = '无卡';
                    break;
                case C:
                    $v['status'] = '已出库';
                    break;
                case G:
                    $v['status'] = '异常锁定';
                    break;
            }
            $rows[] = $v;
        }

        $total = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)
            ->count();
        $amount_sum = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            //->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->getField("sum(b.amount) amount_sum");
        $amount_sum = array('panterid' => '合计金额：', 'pname' => $amount_sum);
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店充值报表导出Excel
     */
    public function recharge_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'c.customid,c.linktel,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        $rmap = session('rexcel');
        foreach ($rmap as $key => $val) {
            $where[$key] = $val;
        }

        $results = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();

        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['amount'] = floatval($v['amount']);
            switch ($v['status']) {
                case A:
                    $v['status'] = '待激活';
                    break;
                case D:
                    $v['status'] = '销卡';
                    break;
                case R:
                    $v['status'] = '退卡';
                    break;
                case S:
                    $v['status'] = '过期';
                    break;
                case N:
                    $v['status'] = '新卡';
                    break;
                case L:
                    $v['status'] = '锁定';
                    break;
                case Y:
                    $v['status'] = '正常卡';
                    break;
                case W:
                    $v['status'] = '无卡';
                    break;
                case C:
                    $v['status'] = '已出库';
                    break;
                case G:
                    $v['status'] = '异常锁定';
                    break;
            }
            $rows[] = $v;
        }

        $tradeamounts = array_column($results, 'amount');
        foreach ($tradeamounts as $key => $val) {
            //bcadd() 2个任意精度数字相加
            $sum = bcadd($sum, $val, 2);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:N3";
        $titleName = '酒店充值报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'N4';
        $headerArray = array('发卡机构编号', '发卡机构名称', '会员编号',
            '会员名称', '会员手机号', '卡号',
            '卡号类型编号', '卡状态', '充值流水号',
            '充值时间', '充值单流水', '充值金额', '操作员', '备注'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'I', 'J', 'K', 'L', 'N');
        $setWidth = array(15, 48, 13, 13, 15, 25, 15, 20, 20, 20, 12, 50);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("L" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['panterid'])->setCellValue('B' . $j, $val['pname'])->setCellValue('C' . $j, "'" . $val['customid'])
                ->setCellValue('D' . $j, $val['cname'])->setCellValue('E' . $j, "'" . $val['linktel'])->setCellValue('F' . $j, "'" . $val['cardno'])
                ->setCellValue('G' . $j, "'" . $val['cardkind'])->setCellValue('H' . $j, $val['status'])->setCellValue('I' . $j, "'" . $val['cpurchaseid'])
                ->setCellValue('J' . $j, $val['activation'])->setCellValue('K' . $j, "'" . $val['purchaseid'])->setCellValue('L' . $j, $val['amount'])
                ->setCellValue('M' . $j, $val['username'])->setCellValue('N' . $j, $val['description']);
            $j++;
        }
        $objSheet->getStyle('K' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('K' . $j, '合计总额:');
        $objSheet->setCellValueExplicit("L" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $objSheet->setCellValue('L' . $j, $sum);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店充值报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    /**
     * 酒店充值报表导出Excel
     */
    public function yuanrecharge_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'c.customid,c.linktel,b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,c.namechinese cname,card.Cardkind,card.status,u.username';
        $statype = array('A' => '待激活', 'D' => '销卡', 'R' => '退卡', 'S' => '过期', 'N' => '新卡', 'L' => '锁定', 'Y' => '正常卡', 'W' => '无卡', 'C' => '已出库');//卡状态
        $rmap = session('rexcel');
        foreach ($rmap as $key => $val) {
            $where[$key] = $val;
        }
        $amount_sum = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->field("sum(b.amount) amount_sum")->find();
        $rlist = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();
        $strlist = "发卡机构编号,发卡机构名称,会员编号,会员名称,卡号,";
        $strlist .= "卡号类型编号,卡状态,充值流水号,充值时间,充值单流水,充值金额,操作员,备注";

        //$encode = mb_detect_encoding($strlist, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";

        foreach ($rlist as $key => $list_info) {
            $list_info['pname'] = iconv('utf-8', 'gbk', $list_info['pname']);
            $list_info['cname'] = iconv('utf-8', 'gbk', $list_info['cname']);
            $list_info['username'] = iconv('utf-8', 'gbk', $list_info['username']);
            $list_info['description'] = iconv('utf-8', 'gbk', $list_info['description']);
            $statype[$list_info['status']] = iconv('utf-8', 'gbk', $statype[$list_info['status']]);
            $strlist .= $list_info['panterid'] . "\t," . $list_info['pname'] . "," . $list_info['customid'];
            $strlist .= "\t," . $list_info['cname'] . "," . $list_info['cardno'] . "\t," . $list_info['cardkind'];
            $strlist .= ',' . $statype[$list_info['status']] . "," . $list_info['cpurchaseid'];
            $strlist .= "\t," . date('Y-m-d H:i:s', strtotime($list_info['placeddate'] . $list_info['placedtime']));
            $strlist .= "\t," . $list_info['purchaseid'] . "\t," . floatval($list_info['amount']) . "," . $list_info['username'];
            $strlist .= "," . $list_info['description'] . "\n";
        }
        $filename = '酒店充值报表' . date("YmdHis");
        $filename = iconv("utf-8", "gbk", $filename);
        unset($rlist);
        $strlist .= ',,,,,,,,,,' . $amount_sum['amount_sum'] . "\t" . ',,' . "\n";
        $this->load_csv($strlist, $filename);
    }

    //消费明细报表
    public function consume()
    {
        $model = new Model();
        $field = 't.cardno,t.termposno,t.tradeid,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
        $field .= 't.flag,t.addpoint,t.quanid,p.namechinese pname,p.hysx,custom.namechinese cuname,p1.namechinese pname1';
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
        $where['t.flag'] = 0;
        $where['t.tradetype'] = array('in', '00,02,13,17,21');
        $where['_string'] = "p.hysx = '酒店'";
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $panterid = trim(I('get.panterid', ''));//商户编号
        $pname = trim(I('get.pname', ''));//商户名称
        $cardno = trim(I('get.cardno', ''));//卡号
        $jystatus = trim(I('get.jystatus', ''));//交易类型
        //$customid = I('get.customid','');//会员编号
        $cuname = trim(I('get.cuname', ''));//会员名称
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pname = $pname == "商户名称" ? "" : $pname;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['t.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['t.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($panterid != '') {
            $where['t.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.nameenglish'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        if ($cardno != '') {
            $where['t.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($jystatus != '') {
            $where['t.tradetype'] = $jystatus;
            $this->assign('jystatus', $jystatus);
            $map['jystatus'] = $jystatus;
        } else {
            $where['t.tradetype'] = array('in', '00,02,13,17,21');
        }
        if ($cuname != '') {
            $where['custom.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        $count = $model->table('trade_wastebooks')->alias('t')
            ->join('join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field("sum(t.tradeamount) amount_sum")->find();
        $this->assign('amount_sum', $amount_sum['amount_sum']);

        $p = new \Think\Page($count, 10);
        $consume_list = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order("t.placeddate||' '||t.placedtime desc")->select();
        //echo $model->getLastSql();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        session('conexcel', $where);
        $this->assign('jytype', $jytype);
        $this->assign('page', $page);
        $this->assign('list', $consume_list);
        $this->display();
    }

    /**
     * 酒店消费明细报表导出Excel
     */
    function consume_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $field = 't.cardno,t.termposno,t.tradeid,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
        $field .= 't.flag,t.addpoint,t.quanid,p.namechinese pname,p.hysx,custom.namechinese cuname,custom.customid,p1.namechinese pname1';
        $conmap = session('conexcel');
        foreach ($conmap as $key => $val) {
            $where[$key] = $val;
        }
        $model = new model();
        $amount_sum = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field("sum(t.tradeamount) amount_sum")->find();
        $consume_list = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->field($field)->order("t.placeddate||' '||t.placedtime desc")->select();
        $strlist = "卡号,会员编号,会员名称,状态,商户编号,商户名称,所属行业,卡属机构,交易时间,";
        $strlist .= "终端号,交易金额,交易积分,交易类型,流水号,产生积分,营销劵编号";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($consume_list as $key => $val_info) {
            $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
            $val_info['cuname'] = iconv('utf-8', 'gbk', $val_info['cuname']);
            $val_info['pname'] = iconv('utf-8', 'gbk', $val_info['pname']);
            $val_info['hysx'] = iconv('utf-8', 'gbk', $val_info['hysx']);
            $val_info['pname1'] = iconv('utf-8', 'gbk', $val_info['pname1']);
            $jytype[$val_info['tradetype']] = iconv('utf-8', 'gbk', $jytype[$val_info['tradetype']]);
            $strlist .= $val_info['cardno'] . "\t," . $val_info['customid'] . "\t," . $val_info['cuname'];
            $strlist .= ',' . iconv('utf-8', 'gbk', '交易成功') . ',' . $val_info['panterid'] . "\t," . $val_info['pname'] . ',' . $val_info['hysx'];
            $strlist .= ',' . $val_info['pname1'] . ',' . date('Y-m-d H:i:s', strtotime($val_info['placeddate'] . $val_info['placedtime']));
            $strlist .= "\t," . $val_info['termposno'] . "\t,";
            $strlist .= floatval($val_info['tradeamount']) . "," . $val_info['tradepoint'];
            $strlist .= "\t," . $jytype[$val_info['tradetype']] . ',' . $val_info['tradeid'] . "\t,";
            $strlist .= floatval($val_info['addpoint']) . ',' . $val_info['quanid'] . "\t\n";
        }
        $filename = '酒店消费明细报表' . date("YmdHis");
        $filename = iconv("utf-8", "gbk", $filename);
        unset($consume_list);
        $strlist .= '' . $amount_sum['amount_sum'] . "\t\n";
        $this->load_csv($strlist, $filename);
    }

    //酒店余额报表
    function yuanbalance()
    {
        $model = new Model();
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $cardno = trim(I('get.cardno', ''));//卡号
        $customid = trim(I('get.customid', ''));//会员编号
        $cuname = trim(I('get.cuname', ''));//会员名称
        $cardno = $cardno == "卡号" ? "" : $cardno;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        if ($cardno != '') {
            $where['a.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($customid != '') {
            $where['a.customid'] = $customid;
            $this->assign('customid', $customid);
            $map['customid'] = $customid;
        }
        if ($cuname != '') {
            $where['a.cuname'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subWhere2 = ' and z.placeddate>=' . $startdate;
            $subWhere3 = ' and e.placeddate>=' . $startdate;
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subWhere2 = ' and z.placeddate<=' . $enddate;
            $subWhere3 = ' and e.placeddate<=' . $enddate;
            $subchongWhere = ' and chong.placeddate<=' . $enddate;
            $subtradeWhere = ' and trade.placeddate<=' . $enddate;
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subWhere2 = ' and z.placeddate>=' . $startdate . ' and z.placeddate<=' . $enddate;
            $subWhere3 = ' and e.placeddate>=' . $startdate . ' and e.placeddate<=' . $enddate;
            //某个时间段结束到现在充值，交易金额时间条件
            $subchongWhere = ' and chong.placeddate<=' . $enddate;
            $subtradeWhere = ' and trade.placeddate<=' . $enddate;
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['a.panterid'] = $this->panterid;
            $where1['a.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
        }

        $where['b.type'] = '01';
        $where['a.cardkind'] = array('in', '6882,2081');
        $field = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount';
        $field1 = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount,nvl(t.tradepoint1,0) tradepoint1,nvl(t.tradeamount1,0) tradeamount1,nvl(c.amount1,0) amount1';
        $subQuery1 = "(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1 .= "h.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1 .= "where h.customid=c.customid and b.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1 .= "and m.customid=b.customid and c.type='00')";
        $subQuery2 = "(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2 .= "where z.description in ('后台充值','至尊币购卡')  " . $subWhere2 . " group by z.cardno )";
        $subQuery3 = "(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3 .= "trade_wastebooks e where e.flag='0' " . $subWhere3 . " and e.tradetype in ('00','17','21') group by e.cardno )";
        //某个时间段结束到现在充值，交易金额查询
        $subchong = "(select nvl(sum(chong.amount),0) as amount1,chong.cardno from card_purchase_logs chong ";
        $subchong .= "where chong.description in ('后台充值','至尊币购卡') " . $subchongWhere . " group by chong.cardno )";
        $subtrade = "(select nvl(sum(trade.tradeamount),0) tradeamount1,nvl(sum(trade.tradepoint),0)  tradepoint1,trade.cardno from ";
        $subtrade .= "trade_wastebooks trade where trade.flag='0' " . $subtradeWhere . " and trade.tradetype in ('00','17','21') group by trade.cardno )";
        $count = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
            ->field($field)->where($where)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($where)->find();
        $this->assign('amount_sum', $amount_sum);
        //echo $model->getLastSql();

        $p = new \Think\Page($count, 10);
        if ($end != '') {
            $card_balance_list = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->field($field1)->limit($p->firstRow . ',' . $p->listRows)->select();
            //查询时时时间点截止 到目前 交易总额a4 充值总额 a5
            $amount_sum1 = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3,sum(c.amount1) a4,sum(t.tradeamount1) a5')->where($where)->find();
            //查询总余额
            $selectamount = $amount_sum1['a3'] + $amount_sum1['a5'] - $amount_sum1['a4'];
            $this->assign('selectamount', $selectamount);

        } else {
            $card_balance_list = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        //print_r($card_balance_list);
        //echo $model->getLastSql();

        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        session('balexcel', $where);
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $card_balance_list);
        $this->display();
    }

    //酒店余额报表页面
    function balance()
    {
        $this->display();
    }

    //酒店余额报表数据
    function getBalanceList()
    {
        $this->setTimeMemory;
        $model = new Model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $cardno = trim(I('get.cardno', ''));//卡号
        $customid = trim(I('get.customid', ''));//会员编号
        $cuname = trim(I('get.cname', ''));//会员名称
        $cardno = $cardno == "卡号" ? "" : $cardno;
        // $cardno='6882371899900013570,6882371899900013565';
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['a.cardno'] = array(array('in', $cardno),array('notlike','68823710888%'));
        }else{
            $where['a.cardno'] = array('notlike','68823710888%');//排除贵宾卡消费信息
        }
        if ($customid != '') {
            $where['a.customid'] = $customid;
        }
        if ($cuname != '') {
            $where['a.cuname'] = array('like', '%' . $cuname . '%');
        }
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subWhere2 = ' and z.placeddate>=' . $startdate;
            $subWhere3 = ' and e.placeddate>=' . $startdate;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subWhere2 = ' and z.placeddate<=' . $enddate;
            $subWhere3 = ' and e.placeddate<=' . $enddate;
            $subchongWhere = ' and chong.placeddate<=' . $enddate;
            $subtradeWhere = ' and trade.placeddate<=' . $enddate;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subWhere2 = ' and z.placeddate>=' . $startdate . ' and z.placeddate<=' . $enddate;
            $subWhere3 = ' and e.placeddate>=' . $startdate . ' and e.placeddate<=' . $enddate;
            //某个时间段结束到现在充值，交易金额时间条件
            $subchongWhere = ' and chong.placeddate<=' . $enddate;
            $subtradeWhere = ' and trade.placeddate<=' . $enddate;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['a.panterid'] = $this->panterid;
            $where1['a.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
        }

        $where['b.type'] = '01';
        $where['a.cardkind'] = array('in', '6882,2081');
        session('cardno',$cardno);
        session('balexcel', $where);
        session('subWhere2', $subWhere2);
        session('subWhere3', $subWhere3);
        session('subchongWhere', $subchongWhere);
        session('subtradeWhere', $subtradeWhere);
        session('enddate', $end);

        $field = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount';
        $field1 = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount,nvl(t.tradepoint1,0) tradepoint1,nvl(t.tradeamount1,0) tradeamount1,nvl(c.amount1,0) amount1';
        $subQuery1 = "(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1 .= "h.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1 .= "where h.customid=c.customid and b.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1 .= "and m.customid=b.customid and c.type='00' and h.status!='R')";
        $subQuery2 = "(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2 .= "where z.description in ('后台充值','至尊币购卡','后台至尊币充值') and z.flag <> 3 " . $subWhere2 . " group by z.cardno )";
        $subQuery3 = "(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3 .= "trade_wastebooks e where e.flag='0' " . $subWhere3 . " and e.tradetype in ('00','17','21') group by e.cardno )";
        //某个时间段结束到现在充值，交易金额查询  $subchong--充值信息  $subtrade--交易信息
        $subchong = "(select nvl(sum(chong.amount),0) as amount1,chong.cardno from card_purchase_logs chong ";
        $subchong .= "where chong.description in ('后台充值','至尊币购卡','后台至尊币充值') " . $subchongWhere . " group by chong.cardno )";
        $subtrade = "(select nvl(sum(trade.tradeamount),0) tradeamount1,nvl(sum(trade.tradepoint),0)  tradepoint1,trade.cardno from ";
        $subtrade .= "trade_wastebooks trade where trade.flag='0' " . $subtradeWhere . " and trade.tradetype in ('00','17','21') group by trade.cardno )";


        $amount_sum = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($where)->find();

        if ($end != '') {
            $total = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->count();
            $results = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->field($field1)->page($page, $pageSize)->select();
            //by wan  ----2016 5 25 -----start
            foreach ($results as $key => $val) {
                $val['timebalance'] = bcsub($val['amount1'], $val['tradeamount1'], 2);//查询余额=充值金额—交易金额
                unset($results[$key]);//销毁变量
                $results[$val['cardno']] = $val;
                //退卡问题
                $boo2 = M('return_cards_log')->where(array('cardno' => $val['cardno'], flag => '2'))->find();
                if (true == $boo2) {
                    unset($results[$val['cardno']]);
                }
            }
            //统计所有查询余额
            $lists = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->field($field1)->select();
            foreach ($lists as $key => $val) {
                $val['timebalance'] = bcsub($val['amount1'], $val['tradeamount1'], 2);//查询余额==充值金额—交易金额
                $lists[$key] = $val;
            }
            $selectamount = array_sum(array_column($lists, 'timebalance'));//array_column从记录集中取出 last_name 列
            ///----end
            //查询时时时间点截止 到目前 交易总额a4 充值总额 a5
//             $amount_sum1=$model->table($subQuery1)->alias('a')
//                 ->join('join __ACCOUNT__ b on a.customid1=b.customid')
//                 ->join('left join '.$subQuery2.' q on q.cardno=a.cardno')
//                 ->join('left join '.$subQuery3.' p on p.cardno=a.cardno')
//                 ->join('left join '.$subchong.' c on c.cardno=a.cardno')
//                 ->join('left join '.$subtrade.' t on t.cardno=a.cardno')
//                 ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3,sum(c.amount1) a4,sum(t.tradeamount1) a5')->where($where)->find();
            //查询总余额
//             $selectamount=$amount_sum1['a3']+$amount_sum1['a5']-$amount_sum1['a4'];
        } else {
            $total = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->where($where)->count();
            $results = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->where($where)->field($field)->page($page, $pageSize)->select();
            foreach ($results as $k => $v) {
                $v['tradeamount'] = floatval($v['tradeamount']);//交易金额
                $v['cardbalance'] = floatval($v['cardbalance']);//实际卡余额
                // $v['amounts']=floatval($v['amount1']-$v['tradeamount1']);
                // $rows[]=$v;
                unset($results[$k]);//销毁变量
                $results[$v['cardno']] = $v;
                //退卡问题
                $boo2 = M('return_cards_log')->where(array('cardno' => $v['cardno'], 'flag' => '2'))->find();
                if (true == $boo2) {
                    unset($results[$v['cardno']]);
                }
            }
        }
        //补卡处理
        $panterid = $this->panterid;
        if ($panterid != 'FFFFFFFF' && $panterid != '00000013') {
            $whereHotel['c.panterid'] = $panterid;
        }

        $whereHotel['ca.flag'] = '1';
        $whereHotel['c.cardkind'] = array(in, '6882,2081');
        $amount_sum = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($where)->find();
        if(empty($cardno)){
            foreach($where as $mm=>$nn){
                if($mm=='a.cardno'){
                    unset($where[$mm]);
                }
            }
        }
        $oldcards = M('card_change_logs')->alias('ca')
            ->join('cards c on ca.cardno=c.cardno')
            ->where($whereHotel)->field('ca.cardno,ca.precardno')
            ->order('placeddate desc')->select();
        foreach ($oldcards as $key => $val) {
            if (array_key_exists($val['precardno'], $results)) {
                $wheres = $where;
                $wheres['a.cardno'] = $val['cardno'];//补卡卡号
                if ($end != '') {
                    $newCardlist = $model->table($subQuery1)->alias('a')
                        ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                        ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                        ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                        ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                        ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                        ->where($wheres)->field($field1)->find();
                    $results[$val['precardno']]['amount'] = bcadd($results[$val['precardno']]['amount'], $newCardlist['amount'], 2);
                    $results[$val['precardno']]['tradeamount'] = bcadd($results[$val['precardno']]['tradeamount'], $newCardlist['tradeamount'], 2);
                    $results[$val['precardno']]['cardbalance'] = bcadd($results[$val['precardno']]['cardbalance'], $newCardlist['cardbalance'], 2);
                    $results[$val['precardno']]['newCard'] = $val['cardno'];
                    $results[$val['precardno']]['timebalance'] = bcadd($results[$val['precardno']]['timebalance'], bcsub($newCardlist['amount1'], $newCardlist['tradeamount1'], 2), 2);
                    //合计交易金额 充值金额关于补卡修改
                    if (is_array($cardno)) {
                        if (in_array($val['precardno'], $cardno)) {
                            $wheres['a.cardno'] = $val['cardno'];
                            $sum_new = $model->table($subQuery1)->alias('a')
                                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                                ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($wheres)->find();
                        }
                        $amount_sum['a1'] = bcadd($amount_sum['a1'], $sum_new['a1'], 2);
                        $amount_sum['a2'] = bcadd($amount_sum['a2'], $sum_new['a2'], 2);
                        $amount_sum['a3'] = bcadd($amount_sum['a3'], $sum_new['a3'], 2);
                        //查询卡余额重新统计：
                        $list_new = $model->table($subQuery1)->alias('a')
                            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                            ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                            ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                            ->where($wheres)->field($field1)->find();
                        $list_new['timebalance'] = bcsub($list_new['amount1'], $list_new['tradeamount1'], 2);
                        $selectamount = bcadd($selectamount, $list_new['timebalance'], 2);
                    }
                } else {
                    $newCardlist = $model->table($subQuery1)->alias('a')
                        ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                        ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                        ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                        ->where($wheres)->field($field)->find();
                    $results[$val['precardno']]['amount'] = bcadd($results[$val['precardno']]['amount'], $newCardlist['amount'], 2);//充值金额,bcadd2个任意精度数字的加法
                    $results[$val['precardno']]['tradeamount'] = bcadd($results[$val['precardno']]['tradeamount'], $newCardlist['tradeamount'], 2);//旧卡交易金额=旧卡交易金额--新卡交易金额
                    $results[$val['precardno']]['cardbalance'] = bcadd($results[$val['precardno']]['cardbalance'], $newCardlist['cardbalance'], 2);//实际卡余额
                    $results[$val['precardno']]['newCard'] = $val['cardno'];
                    //合计交易金额 充值金额关于补卡修改
                    if (is_array($cardno)) {
                        if (in_array($val['precardno'], $cardno)) {
                            $wheres['a.cardno'] = $val['cardno'];
                            $sum_new = $model->table($subQuery1)->alias('a')
                                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                                ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($wheres)->find();
                        }
                        $amount_sum['a1'] = bcadd($amount_sum['a1'], $sum_new['a1'], 2);
                        $amount_sum['a2'] = bcadd($amount_sum['a2'], $sum_new['a2'], 2);
                        $amount_sum['a3'] = bcadd($amount_sum['a3'], $sum_new['a3'], 2);
                    }
                }
            }
            if (array_key_exists($val['cardno'], $results)) {
                unset($results[$val['cardno']]);
            }
        }
        foreach ($results as $k => $v) {
            $rows[] = $v;
        }
        $amount_sum = array(
            'cardno' => '充值合计：', 'customid1' => $amount_sum['a1'] . '元',
            'cuname' => '交易合计：', 'pname' => $amount_sum['a2'] . '元',
            'amount' => '查询余额统计：', 'tradeamount' => $selectamount . '元',
            'tradepoint' => '余额合计：', 'cardbalance' => $amount_sum['a3'] . '元'
        );
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店卡余额报表导出Excel
     */
    function balance_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $end = session('enddate');
        $model = new Model();
        $cardno = session('cardno');
        $balmap = session('balexcel');
        $subWhere2 = session('subWhere2');
        $subWhere3 = session('subWhere3');
        $subchongWhere = session('subchongWhere');
        $subtradeWhere = session('subtradeWhere');

        foreach ($balmap as $key => $value) {
            $where[$key] = $value;
        }
        $field = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount';
        $field1 = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount,nvl(t.tradepoint1,0) tradepoint1,nvl(t.tradeamount1,0) tradeamount1,nvl(c.amount1,0) amount1';
        $subQuery1 = "(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1 .= "h.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1 .= "where h.customid=c.customid and b.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1 .= "and m.customid=b.customid and c.type='00')";
        $subQuery2 = "(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z ";
        $subQuery2 .= "where z.description in ('后台充值','至尊币购卡') and z.flag <> 3  " . $subWhere2 . " group by z.cardno )";
        $subQuery3 = "(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3 .= "trade_wastebooks e where e.flag='0' " . $subWhere3 . " and e.tradetype in ('00','17','21') group by e.cardno )";
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        $panterid = $this->panterid;
//         $panterid='00000126';
        if ($panterid != 'FFFFFFFF' && $panterid != '00000013') {
            $whereHotel['c.panterid'] = $panterid;
        }
        $whereHotel['ca.flag'] = '1';
        $whereHotel['c.cardkind'] = array(in, '6882,2081');
        $amount_sum = $model->table($subQuery1)->alias('a')
            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
            ->field('sum(q.amount) a1,sum(p.tradeamount) a2,sum(a.cardbalance) a3')->where($where)->find();
        $oldcards = M('card_change_logs')->alias('ca')
            ->join('cards c on ca.cardno=c.cardno')
            ->where($whereHotel)->field('ca.cardno,ca.precardno')
            ->order('placeddate desc')->select();
        if ($end != '' && $end != null) {
            //某个时间段结束到现在充值，交易金额查询
            $subchong = "(select nvl(sum(chong.amount),0) as amount1,chong.cardno from card_purchase_logs chong ";
            $subchong .= "where chong.description in ('后台充值','至尊币购卡') " . $subchongWhere . " group by chong.cardno )";
            $subtrade = "(select nvl(sum(trade.tradeamount),0) tradeamount1,nvl(sum(trade.tradepoint),0)  tradepoint1,trade.cardno from ";
            $subtrade .= "trade_wastebooks trade where trade.flag='0' " . $subtradeWhere . " and trade.tradetype in ('00','17','21') group by trade.cardno )";
            $balancelist = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->field($field1)->select();

            foreach ($balancelist as $k => $v) {
                $v['tradeamount'] = floatval($v['tradeamount']);
                $v['cardbalance'] = floatval($v['cardbalance']);
                $v['amounts'] = bcsub($v['amount1'], $v['tradeamount1'], 2);
//                 //补卡
//                 $bool = M('card_change_logs')->where(array('cardno'=>$v['cardno'],flag=>'1'))->find();
//                 if(true==$bool){
//                     $v['cardno']=$v['cardno']."(补卡)";
//                 }
//                 $boo1= M('card_change_logs')->where(array('precardno'=>$v['cardno'],flag=>'1'))->find();
//                 if(true==$boo1){
//                     $v['cardno']=$v['cardno']."(补卡的旧卡)";
//                 }

                $balancelist[$v['cardno']] = $v;
                unset($balancelist[$k]);
                //退卡问题
                $boo2 = M('return_cards_log')->where(array('cardno' => $v['cardno'], flag => '2'))->find();
                if (true == $boo2) {
                    unset($balancelist[$v['cardno']]);
                }
//                 $results[]=$v;
            }
            if(empty($cardno)){
                foreach($where as $mm=>$nn){
                    if($mm=='a.cardno'){
                        unset($where[$mm]);
                    }
                }
            }
            if (is_array($where['a.cardno'])) {
                foreach ($oldcards as $key => $val) {
                    if (array_key_exists($val['precardno'], $balancelist)) {
                        $wheres = $where;
                        $wheres['a.cardno'] = $val['cardno'];
                        $newCardlist = $model->table($subQuery1)->alias('a')
                            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                            ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                            ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                            ->where($wheres)->field($field1)->find();
                        $balancelist[$val['precardno']]['amount'] = bcadd($balancelist[$val['precardno']]['amount'], $newCardlist['amount'], 2);
                        $balancelist[$val['precardno']]['tradeamount'] = bcadd($balancelist[$val['precardno']]['tradeamount'], $newCardlist['tradeamount'], 2);
                        $balancelist[$val['precardno']]['cardbalance'] = bcadd($balancelist[$val['precardno']]['cardbalance'], $newCardlist['cardbalance'], 2);
                        $balancelist[$val['precardno']]['newCard'] = $val['cardno'];
                        $balancelist[$val['precardno']]['amounts'] = bcadd($balancelist[$val['precardno']]['amounts'], bcsub($newCardlist['amount1'], $newCardlist['tradeamount1'], 2), 2);
                    }
                }
            } else {
                foreach ($oldcards as $key => $val) {
                    $balancelist[$val['precardno']]['amount'] = bcadd($balancelist[$val['precardno']]['amount'], $balancelist[$val['cardno']]['amount'], 2);
                    $balancelist[$val['precardno']]['tradeamount'] = bcadd($balancelist[$val['precardno']]['tradeamount'], $balancelist[$val['cardno']]['tradeamount'], 2);
                    $balancelist[$val['precardno']]['cardbalance'] = bcadd($balancelist[$val['precardno']]['cardbalance'], $balancelist[$val['cardno']]['cardbalance'], 2);
                    $balancelist[$val['precardno']]['newCard'] = $val['cardno'];
                    $balancelist[$val['precardno']]['amounts'] = bcadd($balancelist[$val['precardno']]['amounts'], $balancelist[$val['cardno']]['amounts'], 2);
                    unset($balancelist[$val['cardno']]);
                }
            }
            $selectamount = array_sum(array_column($balancelist, 'amounts'));
            //设置title
            $cellMerge = "A1:J3";
            $titleName = '酒店卡余额报表';
            $this->setTitle($cellMerge, $titleName);
            //setheader
            $startCell = 'A4';
            $endCell = 'K4';
            $headerArray = array('卡号', '补卡卡号', '会员编号', '会员名称',
                '会员所属机构', '充值金额', '交易金额',
                '交易积分', '查询余额', '实际卡余额', '积分'
            );
            $this->setHeader($startCell, $endCell, $headerArray);
            //setWidth
            $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
            $setWidth = array(25, 25, 15, 15, 50, 15, 15, 15, 15, 15, 15);
            $this->setWidth($setCells, $setWidth);

            $j = 5;
            foreach ($balancelist as $key => $val) {
                $objSheet->setCellValueExplicit("E" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("F" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("H" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("J" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("K" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['newCard'])->setCellValue('C' . $j, "'" . $val['customid1'])->setCellValue('D' . $j, $val['cuname'])
                    ->setCellValue('E' . $j, $val['pname'])->setCellValue('F' . $j, $val['amount'])->setCellValue('G' . $j, $val['tradeamount'])
                    ->setCellValue('H' . $j, $val['tradepoint'])->setCellValue('I' . $j, $val['amounts'])->setCellValue('J' . $j, $val['cardbalance'])
                    ->setCellValue('K' . $j, $val['pointbalance']);
                $j++;
            }
        } else {
            $balancelist = $model->table($subQuery1)->alias('a')
                ->join('left join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->where($where)->field($field)->select();
            foreach ($balancelist as $k => $v) {
                unset($balancelist[$k]);
                $balancelist[$v['cardno']] = $v;
                //退卡问题
                $boo2 = M('return_cards_log')->where(array('cardno' => $v['cardno'], flag => '2'))->find();
                if (true == $boo2) {
                    unset($balancelist[$v['cardno']]);
                }
            }
            if(empty($cardno)){
                foreach($where as $mm=>$nn){
                    if($mm=='a.cardno'){
                        unset($where[$mm]);
                    }
                }
            }
            if (is_array($where['a.cardno'])) {
                foreach ($oldcards as $key => $val) {
                    if (array_key_exists($val['precardno'], $balancelist)) {
                        $wheres = $where;
                        $wheres['a.cardno'] = $val['cardno'];
                        $newCardlist = $model->table($subQuery1)->alias('a')
                            ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                            ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                            ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                            ->where($wheres)->field($field)->find();
                        $balancelist[$val['precardno']]['amount'] = bcadd($balancelist[$val['precardno']]['amount'], $newCardlist['amount'], 2);
                        $balancelist[$val['precardno']]['tradeamount'] = bcadd($balancelist[$val['precardno']]['tradeamount'], $newCardlist['tradeamount'], 2);
                        $balancelist[$val['precardno']]['cardbalance'] = bcadd($balancelist[$val['precardno']]['cardbalance'], $newCardlist['cardbalance'], 2);
                        $balancelist[$val['precardno']]['newCard'] = $val['cardno'];
                        $balancelist[$val['precardno']]['amounts'] = bcadd($balancelist[$val['precardno']]['amounts'], bcsub($newCardlist['amount1'], $newCardlist['tradeamount1'], 2), 2);
                        unset($balancelist[$val['cardno']]);
                    }
                }
            } else {
                foreach ($oldcards as $key => $val) {
                    $balancelist[$val['precardno']]['amount'] = bcadd($balancelist[$val['precardno']]['amount'], $balancelist[$val['cardno']]['amount'], 2);
                    $balancelist[$val['precardno']]['tradeamount'] = bcadd($balancelist[$val['precardno']]['tradeamount'], $balancelist[$val['cardno']]['tradeamount'], 2);
                    $balancelist[$val['precardno']]['cardbalance'] = bcadd($balancelist[$val['precardno']]['cardbalance'], $balancelist[$val['cardno']]['cardbalance'], 2);
                    $balancelist[$val['precardno']]['newCard'] = $val['cardno'];
                    unset($balancelist[$val['cardno']]);
                }
            }
            //设置title
            $cellMerge = "A1:J3";
            $titleName = '酒店卡余额报表';
            $this->setTitle($cellMerge, $titleName);
            //setheader
            $startCell = 'A4';
            $endCell = 'J4';
            $headerArray = array('卡号', '补卡卡号', '会员编号', '会员名称',
                '会员所属机构', '充值金额', '交易金额',
                '交易积分', '查询余额', '实际卡余额', '积分'
            );
            $this->setHeader($startCell, $endCell, $headerArray);
            //setWidth
            $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
            $setWidth = array(25, 25, 15, 15, 50, 15, 15, 15, 15, 15);
            $this->setWidth($setCells, $setWidth);
            $j = 5;
            foreach ($balancelist as $key => $val) {
                $objSheet->setCellValueExplicit("E" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("F" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("H" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['newCard'])->setCellValue('C' . $j, "'" . $val['customid1'])->setCellValue('D' . $j, $val['cuname'])
                    ->setCellValue('E' . $j, $val['pname'])->setCellValue('F' . $j, $val['amount'])->setCellValue('G' . $j, $val['tradeamount'])
                    ->setCellValue('H' . $j, $val['tradepoint'])->setCellValue('I' . $j, '')->setCellValue('J' . $j, $val['cardbalance'])->setCellValue('K' . $j, $val['pointbalance']);
                $j++;
            }
        }
        $objSheet->getStyle('A' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->getStyle('C' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->getStyle('E' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->getStyle('H' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $amount_sum['a1'] = array_sum(array_column($balancelist, 'amount'));
        $amount_sum['a2'] = array_sum(array_column($balancelist, 'tradeamount'));
        $amount_sum['a3'] = array_sum(array_column($balancelist, 'cardbalance'));
        $selectamount = array_sum(array_column($balancelist, 'amounts'));
        $objSheet->setCellValue('A' . $j, '充值合计:')
            ->setCellValue('B' . $j, $amount_sum['a1'])
            ->setCellValue('C' . $j, '交易合计:')
            ->setCellValue('D' . $j, $amount_sum['a2'])
            ->setCellValue('E' . $j, '查询余额统计:')
            ->setCellValue('F' . $j, $selectamount)
            ->setCellValue('H' . $j, '余额合计:')
            ->setCellValue('I' . $j, $amount_sum['a3']);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店卡余额报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    /**
     * 酒店卡余额报表导出Excel
     */
    function yuanbalance_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $end = trim(I('get.enddate', ''));
        $model = new Model();
        $balmap = session('balexcel');
        foreach ($balmap as $key => $value) {
            $where[$key] = $value;
        }
        $field = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount';
        $field1 = 'a.*,b.amount as pointbalance,nvl(p.tradeamount,0) tradeamount,nvl(p.tradepoint,0),tradepoint,nvl(q.amount,0) amount,nvl(t.tradepoint1,0) tradepoint1,nvl(t.tradeamount1,0) tradeamount1,nvl(c.amount1,0) amount1';
        $subQuery1 = "(select h.cardno,h.customid as customid1,h.cardkind,b.customid, b.namechinese cuname,  c.amount as cardbalance,";
        $subQuery1 .= "h.panterid,f.parent,f.namechinese as pname from  customs b,account c,customs_c m,cards h,panters f ";
        $subQuery1 .= "where h.customid=c.customid and b.panterid=f.panterid  and h.customid=m.cid ";
        $subQuery1 .= "and m.customid=b.customid and c.type='00')";
        $subQuery2 = "(select nvl(sum(z.amount),0) as amount,z.cardno from card_purchase_logs z where z.description='后台充值'  group by z.cardno )";
        $subQuery3 = "(select nvl(sum(e.tradeamount),0) tradeamount,nvl(sum(e.tradepoint),0)  tradepoint,e.cardno from ";
        $subQuery3 .= "trade_wastebooks e where e.flag='0' and e.tradetype in ('00','17','21') group by e.cardno )";
        if ($end != '') {
            $end = str_replace('-', '', $end);
            $subchongWhere = ' and chong.placeddate<=' . $end;
            $subtradeWhere = ' and trade.placeddate<=' . $end;
            //某个时间段结束到现在充值，交易金额查询
            $subchong = "(select nvl(sum(chong.amount),0) as amount1,chong.cardno from card_purchase_logs chong ";
            $subchong .= "where chong.description='后台充值' " . $subchongWhere . " group by chong.cardno )";
            $subtrade = "(select nvl(sum(trade.tradeamount),0) tradeamount1,nvl(sum(trade.tradepoint),0)  tradepoint1,trade.cardno from ";
            $subtrade .= "trade_wastebooks trade where trade.flag='0' " . $subtradeWhere . " and trade.tradetype in ('00','17','21') group by trade.cardno )";
            $card_balance_list = $model->table($subQuery1)->alias('a')
                ->join('join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->join('left join ' . $subchong . ' c on c.cardno=a.cardno')
                ->join('left join ' . $subtrade . ' t on t.cardno=a.cardno')
                ->where($where)->field($field1)->limit($p->firstRow . ',' . $p->listRows)->select();
            $strlist = "卡号,会员编号,会员名称,会员所属机构,充值金额,交易金额,交易积分,查询余额,实际卡余额,积分";
            $strlist = iconv('utf-8', 'gbk', $strlist);
            $strlist .= "\n";
            $amounts = 0;
            $tradeamounts = 0;
            //$qmamounts=0;
            $cardbalances = 0;

            foreach ($card_balance_list as $key => $val) {
                $val['cuname'] = iconv('utf-8', 'gbk', $val['cuname']);
                $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
                $selectamount = floatval($val['amount1'] - $val['tradeamount1']);
                $strlist .= $val['cardno'] . "\t," . $val['customid'] . "\t," . $val['cuname'];
                $strlist .= ',' . $val['pname'] . ',' . $val['amount'] . ',' . floatval($val['tradeamount']);
                $strlist .= "," . $val['tradepoint'] . ',' . $selectamount . "," . floatval($val['cardbalance']) . "," . floatval($val['pointbalance']) . "\n";
                $amounts += $val['amount'];
                $selectamounts += $selectamount;
                $tradeamounts += floatval($val['tradeamount']);
                $cardbalances += floatval($val['cardbalance']);
            }
            $strlist .= ',,,,' . $amounts . "\t," . $tradeamounts . "\t,," . $selectamounts . "\t," . $cardbalances . "\t,\n";
        } else {
            $card_balance_list = $model->table($subQuery1)->alias('a')
                ->join('left join __ACCOUNT__ b on a.customid1=b.customid')
                ->join('left join ' . $subQuery2 . ' q on q.cardno=a.cardno')
                ->join('left join ' . $subQuery3 . ' p on p.cardno=a.cardno')
                ->where($where)->field($field)->select();
            $strlist = "卡号,会员编号,会员名称,会员所属机构,充值金额,交易金额,交易积分,卡余额,积分";
            $strlist = iconv('utf-8', 'gbk', $strlist);
            $strlist .= "\n";
            $amounts = 0;
            $tradeamounts = 0;
            //$qmamounts=0;
            $cardbalances = 0;

            foreach ($card_balance_list as $key => $val) {
                $val['cuname'] = iconv('utf-8', 'gbk', $val['cuname']);
                $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
                $strlist .= $val['cardno'] . "\t," . $val['customid'] . "\t," . $val['cuname'];
                $strlist .= ',' . $val['pname'] . ',' . $val['amount'] . ',' . floatval($val['tradeamount']);
                $strlist .= "," . $val['tradepoint'] . ',' . floatval($val['cardbalance']) . "," . floatval($val['pointbalance']) . "\n";
                $amounts += $val['amount'];
                $tradeamounts += floatval($val['tradeamount']);
                $cardbalances += floatval($val['cardbalance']);
            }
            $strlist .= ',,,,' . $amounts . "\t," . $tradeamounts . "\t,," . $cardbalances . "\t,\n";
        }
        $filename = '酒店卡余额报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($strlist, $filename);
    }

    //酒店日结算报表
    function dailyReport()
    {
        $model = new model;
        $start = trim(I('get.startdate', ''));
        $end = trim(I('get.enddate', ''));
        $panterid = trim(I('get.panterid', ''));
        $shname = trim(I('get.shname', ''));
        $jsname = trim(I('get.jsname', ''));
        if (!empty($start) && empty($start)) {
            $startdate = str_replace('-', '', $start);
            $where['tp.statdate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if (!empty($end) && empty($start)) {
            $enddate = str_replace('-', '', $end);
            $where['tp.statdate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if (!empty($end) && !empty($start)) {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['tp.statdate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if (empty($end) && empty($start)) {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['tp.statdate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if (!empty($panterid)) {
            $where['tp.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if (!empty($shname)) {
            $where['p.namechinese'] = array('like', '%' . $shname . '%');
            $this->assign('shname', $shname);
            $map['shname'] = $shname;
        }
        if (!empty($jsname)) {
            $where['p.settleaccountname'] = array('like', '%' . $jsname . '%');
            $this->assign('jsname', $jsname);
            $map['jsname'] = $jsname;
        }
        $where['p.hysx'] = '酒店';
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'or';
            $where['_complex'] = $where1;
            if ($this->panterid == '00000013') {
                $this->assign('is_admin', 1);
            } else {
                $this->assign('is_admin', 0);
            }
        } else {
            $this->assign('is_admin', 1);
        }
        $field = 'tp.*,p.namechinese pname';
        $count = $model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
        $this->assign('amount_sum', $amount_sum['amount_sum']);

        $p = new \Think\Page($count, 10);
        $panter_trade_daily_list = $model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)
            ->limit($p->firstRow . ',' . $p->listRows)->order('tp.statdate desc')->select();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        session('dailyReportxcel', $where);
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $panter_trade_daily_list);
        $this->display();
    }

    /**
     * 商户日结算报表导出Excel
     */
    function dailyReport_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        if (isset($_SESSION['dailyReportxcel'])) {
            $recmap = session('dailyReportxcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $amount_sum = $model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field('sum(tradeamount) amount_sum')->find();
        $field = 'tp.*,p.namechinese pname';
        $panterList = $model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->order('tp.statdate desc')->select();
        $strlist = "序号,商户编码,结算商户,结算日期,交易笔数,交易金额,交易积分";//序号,商户编号,商户名称,结算日期,交易笔数,交易金额,结算积分,结算比率,服务费,结算金额,手续费,结算户名,结算账号,结算银行,结算开户行
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($panterList as $key => $val) {
            $tradeamount = floatval($val['tradeamount']);
            $keys = $key + 1;
            $val['pname'] = iconv('utf-8', 'gbk', $val['pname']);
            $strlist .= $keys . ',' . $val['panterid'] . "\t," . $val['pname'] . ',' . date('Y-m-d', strtotime($val['statdate']));
            $strlist .= "\t," . $val['tradequantity'] . ',' . floatval($tradeamount) . "," . $val['tradepoint'] . "\n";
        }
        $filename = '当日交易结算报表' . date("YmdHis");
        unset($panterList);
        $strlist .= ',,,,,' . $amount_sum['amount_sum'] . "\t,\n";
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($strlist, $filename);
    }

    //补卡统计
    function yuanmendCard()
    {
        $model = new Model();
        $field = 'ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $where['ccl.flag'] = 1;
        $start = I('get.startdate', '');//开始时间
        $end = I('get.enddate', '');//结束时间
        $customid = trim(I('get.customid', ''));//会员编号
        $cuname = trim(I('get.cuname', ''));//会员名称
        $precardno = trim(I('get.precardno', ''));//原卡号
        $cardno = trim(I('get.cardno', ''));//现卡号
        $username = trim(I('get.username', ''));//操作员
        $precardno = $precardno == "原卡号" ? "" : $precardno;
        $cardno = $cardno == "现卡号" ? "" : $cardno;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $username = $username == "操作员" ? "" : $username;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ccl.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ccl.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ccl.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
            $this->assign('customid', $customid);
            $map['customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($precardno != '') {
            $where['ccl.precardno'] = $precardno;
            $this->assign('precardno', $precardno);
            $map['precardno'] = $precardno;
        }
        if ($cardno != '') {
            $where['ccl.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
            $this->assign('username', $username);
            $map['username'] = $username;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
        } else {
            $where1['_string'] = "p.hysx ='酒店'";
            $where['_complex'] = $where1;
        }
        $count = $model->table('card_change_logs')->alias('ccl')
            ->join('left join __CARDS__ c on ccl.cardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->where($where)->field($field)->count();
        $p = new \Think\Page($count, 10);
        $mendCardList = $model->table('card_change_logs')->alias('ccl')
            ->join('__CARDS__ c on ccl.precardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('ccl.placedDate desc')->select();
        session('menexcel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $mendCardList);
        $this->display();
    }

    //补卡统计页面
    function mendCard()
    {
        $this->display();
    }

    //补卡统计数据
    function getMendCardList()
    {
        $model = new Model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $field = 'ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $where['ccl.flag'] = 1;
        $start = I('get.startdate', '');//开始时间
        $end = I('get.enddate', '');//结束时间
        $customid = trim(I('get.customid', ''));//会员编号
        $cuname = trim(I('get.cuname', ''));//会员名称
        $precardno = trim(I('get.precardno', ''));//原卡号
        $cardno = trim(I('get.cardno', ''));//现卡号
        $username = trim(I('get.username', ''));//操作员
        $precardno = $precardno == "原卡号" ? "" : $precardno;
        $cardno = $cardno == "现卡号" ? "" : $cardno;
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $username = $username == "操作员" ? "" : $username;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ccl.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ccl.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ccl.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($precardno != '') {
            $precardno = explode(',', $precardno);
            $where['ccl.precardno'] = array('in', $precardno);
        }
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['ccl.cardno'] = array('in', $cardno);
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
        } else {
            $where1['_string'] = "p.hysx ='酒店'";
            $where['_complex'] = $where1;
        }
        session('menexcel', $where);

        $total = $model->table('card_change_logs')->alias('ccl')
            ->join('left join __CARDS__ c on ccl.cardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->where($where)->field($field)->count();
        //补卡日志表--会员编号对应表--会员信息表--商户表--操作员表
        $results = $model->table('card_change_logs')->alias('ccl')
            ->join('__CARDS__ c on ccl.precardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->where($where)->field($field)->page($page, $pageSize)
            ->order('ccl.placedDate desc')->select();
        foreach ($results as $k => $v) {
            $v['placedtime'] = date('Y-m-d', strtotime($v['placeddate']));
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);

    }

    //酒店补卡报表导出Excel
    function mendCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $menmap = session('menexcel');
        foreach ($menmap as $key => $val) {
            $where[$key] = $val;
        }
        $results = $model->table('card_change_logs')->alias('ccl')
            ->join('__CARDS__ c on ccl.precardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('ccl.placedDate desc')->select();
        foreach ($results as $k => $v) {
            $v['placedtime'] = date('Y-m-d', strtotime($v['placeddate']));
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:G3";
        $titleName = '酒店补卡统计报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'G4';
        $headerArray = array('卡号', '会员编号', '会员名称',
            '原卡号', '补卡时间', '操作员',
            '备注'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['customid'])->setCellValue('C' . $j, $val['cuname'])
                ->setCellValue('D' . $j, "'" . $val['precardno'])->setCellValue('E' . $j, $val['placedtime'])->setCellValue('F' . $j, $val['username'])
                ->setCellValue('G' . $j, $val['memo']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店补卡统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //补卡报表导出Excel
    function yuanmendCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'ccl.*,cc.cid,cu.customid,cu.namechinese cuname,u.username';
        $menmap = session('menexcel');
        foreach ($menmap as $key => $val) {
            $where[$key] = $val;
        }
        $mendCardList = $model->table('card_change_logs')->alias('ccl')
            ->join('__CARDS__ c on ccl.precardno=c.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __USERS__ u on u.userid=ccl.userid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('ccl.placedDate desc')->select();
        $strlist = "卡号,会员编号,会员名称,原卡号,补卡时间,操作员,备注";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($mendCardList as $key => $val_info) {
            $val_info['cuname'] = iconv('utf-8', 'gbk', $val_info['cuname']);
            $val_info['username'] = iconv('utf-8', 'gbk', $val_info['username']);
            $val_info['memo'] = iconv('utf-8', 'gbk', $val_info['memo']);
            $strlist .= $val_info['cardno'] . "\t," . $val_info['customid'] . "\t,";
            $strlist .= $val_info['cuname'] . ',' . $val_info['precardno'] . "\t,";
            $strlist .= date('Y-m-d', strtotime($val_info['placeddate']));
            $strlist .= "\t," . $val_info['username'] . ',' . $val_info['memo'] . "\n";
        }
        $filename = '补卡报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($mendCardList);
        $this->load_csv($strlist, $filename);
    }

    //退卡统计
    function yuanreturnCard()
    {
        if ($_REQUEST['back'] == 1) {
            $this->assign('back', 1);
        }
        $model = new Model();
        $field = 'rcl.*,cu.customid,cu.namechinese cuname,u.username';
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $customid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $username = trim(I('get.username', ''));
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $username = $username == "操作员" ? "" : $username;
        $cardno = $cardno == "卡号" ? "" : $cardno;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['rcl.rtdate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['rcl.rtdate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['rcl.rtdate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
            $this->assign('customid', $customid);
            $map['customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cardno != '') {
            $where['rcl.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
            $this->assign('username', $username);
            $map['username'] = $username;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
        } else {
            $where1['_string'] = "p.hysx = '酒店'";
            $where['_complex'] = $where1;
        }
        $count = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
            ->field($field)->count();
        $p = new \Think\Page($count, 10);
        $destory_list = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=rcl.operid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('rcl.rtdate desc')->select();
        session('recardexcel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('list', $destory_list);
        $this->display();
    }

    function returnCard()
    {
        $this->display();
    }

    //退卡统计数据
    function getReturnCardList()
    {
        if ($_REQUEST['back'] == 1) {
            $this->assign('back', 1);
        }
        $model = new Model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $field = 'rcl.*,cu.customid,cu.namechinese cuname,u.username';
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $customid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cardno = trim(I('get.cardno', ''));
        $username = trim(I('get.username', ''));
        $customid = $customid == "会员编号" ? "" : $customid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $username = $username == "操作员" ? "" : $username;
        $cardno = $cardno == "卡号" ? "" : $cardno;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['rcl.rtdate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['rcl.rtdate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['rcl.rtdate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($customid != '') {
            $where['cu.customid'] = $customid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($cardno != '') {
            $cardno = explode(',', $cardno);
            $where['rcl.cardno'] = array('in', $cardno);
        }
        if ($username != '') {
            $where['u.username'] = array('like', '%' . $username . '%');
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
            $where['rcl.Flag'] = array('neq', 9);
        } else {
            $where1['_string'] = "p.hysx = '酒店'";
            $where['_complex'] = $where1;
        }
        session('recardexcel', $where);

        $total = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
            ->field($field)->count();
        $amount_sum = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
            ->field("sum(rcl.cardbalance) cardbalances,sum(rcl.amount) amounts")->select();
        //销卡记录表--卡表--会员编号对应表--会员信息表--操作员表--商户表
        $results = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=rcl.operid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->page($page, $pageSize)
            ->order('rcl.rtdate desc')->select();
        //echo $model->getLastSql();exit;
        foreach ($results as $k => $v) {
            $v['rtdatetime'] = date('Y-m-d H:i:s', strtotime($v['rtdate'] . $v['rttime']));
            $v['cardbalance'] = floatval($v['cardbalance']);
            $v['cardbalance1'] = floatval($v['cardbalance1']);
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }
        $result = array();
        $footer[] = array('rtdatetime' => '卡余额总计：', 'cardbalance' => $amount_sum[0]['cardbalances'], 'cardbalance1' => '实退金额总计：', 'amount' => $amount_sum[0]['amounts']);
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    //酒店退卡报表导出Excel
    function returnCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'rcl.*,c.customid,cu.namechinese cuname,u.username';
        if (isset($_SESSION['recardexcel'])) {
            $recmap = session('recardexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=rcl.operid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('rcl.rtdate desc')->select();
        foreach ($results as $k => $v) {
            $v['rtdatetime'] = date('Y-m-d H:i:s', strtotime($v['rtdate'] . $v['rttime']));
            $v['cardbalance'] = floatval($v['cardbalance']);
            $v['cardbalance1'] = floatval($v['cardbalance1']);
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:I3";
        $titleName = '酒店退卡统计报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'I4';
        $headerArray = array('卡号', '会员编号', '会员名称',
            '退卡时间', '卡余额', '通用积分', '实退金额', '操作员',
            '备注'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'I');
        $setWidth = array(25, 15, 25, 25, 25, 15, 20, 50);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("E" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("F" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['customid'])->setCellValue('C' . $j, $val['cuname'])
                ->setCellValue('D' . $j, "'" . $val['rtdatetime'])->setCellValue('E' . $j, $val['cardbalance'])->setCellValue('F' . $j, $val['cardbalance1'])
                ->setCellValue('G' . $j, $val['amount'])->setCellValue('H' . $j, $val['username'])->setCellValue('I' . $j, $val['description']);
            $j++;
        }
        $sum_czamount = array_sum(array_column($rows, cardbalance));
        $sum_consumeamount = array_sum(array_column($rows, amount));
        $objSheet->setCellValue('D' . $j, '卡余额合计:');
        $objSheet->setCellValue('E' . $j, $sum_czamount);
        $objSheet->setCellValue('F' . $j, '实退金额合计:');
        $objSheet->setCellValue('G' . $j, $sum_consumeamount);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店退卡统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //退卡统计导出Excel
    function yuanreturnCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'rcl.*,c.customid,cu.namechinese cuname,u.username';
        if (isset($_SESSION['recardexcel'])) {
            $recmap = session('recardexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $amount_sum = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=rcl.operid')->where($where)
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->field('sum(rcl.cardbalance) balance_sum,sum(rcl.cardbalance1) balance1_sum')->find();
        $ralist = $model->table('return_cards_log')->alias('rcl')
            ->join('__CARDS__ c on rcl.cardno=c.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__USERS__ u on u.userid=rcl.operid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('rcl.rtdate desc')->select();
        $strlist = "卡号,会员编号,会员名称,退卡时间,卡余额,通用积分,实退金额,操作员,备注";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        foreach ($ralist as $key => $val_info) {
            $val_info['cuname'] = iconv('utf-8', 'gbk', $val_info['cuname']);
            $val_info['username'] = iconv('utf-8', 'gbk', $val_info['username']);
            $val_info['description'] = iconv('utf-8', 'gbk', $val_info['description']);
            $strlist .= $val_info['cardno'] . "\t," . $val_info['customid'] . "\t," . $val_info['cuname'];
            $strlist .= "\t," . date('Y-m-d H:i:s', strtotime($val_info['rtdate'] . $val_info['rttime']));
            $strlist .= "\t," . floatval($val_info['cardbalance']) . "," . floatval($val_info['cardbalance1']) . ",";
            $strlist .= floatval($val_info['amount']) . ',' . $val_info['username'] . ',';
            $strlist .= $val_info['description'] . "\n";
        }
        $filename = '退卡统计' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($ralist);
        $strlist .= ',,,,' . $amount_sum['balance_sum'] . "\t," . $amount_sum['balance1_sum'] . "\t,,,\n";
        $this->load_csv($strlist, $filename);
    }

    //营销产品充值查询
    function yuanproductquery()
    {
        $cardnoc = trim(I('get.cardnoc', ''));
        $customsid = trim(I('get.customsid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cquanid = trim(I('get.cquanid', ''));
        $cquanname = trim(I('get.cquanname', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $cquanid = $cquanid == "营销产品编号" ? "" : $cquanid;
        $cquanname = $cquanname == "营销产品名称" ? "" : $cquanname;
        if ($cardnoc != '') {
            $where['d.cardno'] = $cardnoc;
            $this->assign('cardnoc', $cardnoc);
            $map['cardnoc'] = $cardnoc;
        }
        if ($customsid != '') {
            $where['b.customid'] = $customsid;
            $this->assign('customsid', $customsid);
            $map['customsid'] = $customsid;
        }
        if ($cuname != '') {
            $where['b.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cquanid != '') {
            $where['a.quanid'] = $cquanid;
            $this->assign('cquanid', $cquanid);
            $map['cquanid'] = $cquanid;
        }
        if ($cquanname != '') {
            $where['c.quanname'] = array('like', '%' . $cquanname . '%');
            $this->assign('cquanname', $cquanname);
            $map['cquanname'] = $cquanname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $where['_complex'] = $where1;
        }
        $account = M('account');
        $where['a.type'] = '02';
        $field = 'd.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname';
        //$subQuery=' select sum(amount) zcamount,customid,quanid from quancz group by customid,quanid';
        $count = $account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)
            ->order('d.cardno desc')->count();
        $p = new \Think\Page($count, 15);
        $account_list = $account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->order('d.cardno desc')->select();
        //echo $account->getLastSql();
        session('produceexcel', $where);
        $page = $p->show();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $quancz = M('quancz');
        $tradeWB = M('trade_wastebooks');
        foreach ($account_list as $key => $val) {
            $map1 = array('customid' => $val['customid1'], 'quanid' => $val['quanid']);
            $account_list[$key]['czamount'] = intval($quancz->where($map1)->sum('amount'));

            $map2 = array('cardno' => $val['cardno'], 'tradetype' => '02', 'quanid' => $val['quanid']);
            $account_list[$key]['consumeamount'] = intval($tradeWB->where($map2)->sum('tradeamount'));
        }
        $this->assign('page', $page);
        $this->assign('list', $account_list);
        $this->display();
    }

    //营销产品余额报表列表
    function productquery()
    {
        $this->display();
    }

    //营销产品余额报表列表数据
    function getProductQueryList()
    {
        $model = new Model();
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cquanid = trim(I('get.quanid', ''));
        $cquanname = trim(I('get.quanname', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $cquanid = $cquanid == "营销产品编号" ? "" : $cquanid;
        $cquanname = $cquanname == "营销产品名称" ? "" : $cquanname;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subWhere = ' and placeddate>=' . $startdate;
            $map['qa.placeddate'] = array('egt', $startdate);
            $wheres['placeddate'] = array('egt', $startdate);
            $oldqwhere['qa.placeddate'] = array('egt', $startdate);
            $diffwhere['qa.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subWhere = " and placeddate<='" . $enddate . "'";
            $subtradeWhere = " and placeddate<='" . $enddate . "'";
            $map['qa.placeddate'] = array('elt', $enddate);
            $wheres['placeddate'] = array('elt', $enddate);
            $oldqwhere['qa.placeddate'] = array('elt', $enddate);
            $diffwhere['qa.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subWhere = " and placeddate>='" . $startdate . "' and placeddate<='" . $enddate . "'";
            $subtradeWhere = " and placeddate<='" . $enddate . "'";
            $map['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $wheres['placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $oldqwhere['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $diffwhere['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cardnoc != '') {
            $cardnoc = explode(',', $cardnoc);
            $where['h.cardno'] = array('in', $cardnoc);
        }
        if ($customsid != '') {
            $where['h.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['h.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($cquanid != '') {
            $where['h.quanid'] = $cquanid;
            $wheres['quanid'] = $cquanid;
            $map['quanid'] = $cquanid;
        }
        if ($cquanname != '') {
            $where['h.quanname'] = $cquanname;
            $wheres['quanname'] = $cquanname;
            $map['quanname'] = $cquanname;
        }
        $where['h.cardkind'] = array('neq', '6883');
        if ($this->panterid != 'FFFFFFFF') {
            $where['h.panterid'] = $this->panterid;
            $map['panterid'] = $this->panterid;
        }
        session('enddate', $enddate);
        session('produceexcel', $where);
        session('subWhere', $subWhere);
        session('subtradeWhere', $subtradeWhere);
        session('map', $map);
        session('wheres', $wheres);
        session('oldqwhere', $oldqwhere);
        session('diffwhere', $diffwhere);

        $field = "h.*,f.rechargeamount,i.consumeamount";
        $field1 = "h.*,f.rechargeamount,i.consumeamount,m.rechargeamount1,n.consumeamount1";
        $subQuery1 = "(select d.cardno,d.cardkind,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.amount quanprice,p.panterid";
        $subQuery1 .= " from cards d,account a,quankind c,customs_c m,customs b,panters p";
        $subQuery1 .= " where d.customid=a.customid and a.quanid=c.quanid and  m.cid=d.customid and b.customid=m.customid and p.panterid=c.panterid and d.status='Y' and a.type='02')";
        //充值数量
        $subQuery2 = "(select customid,quanid,nvl(sum(amount),0) rechargeamount from quancz  where 1=1 " . $subWhere . " group by quanid,customid)";
        //交易数量
        $subQuery3 = "(select cardno,nvl(sum(tradeamount),0) consumeamount,quanid from trade_wastebooks where  tradetype='02' and flag=0" . $subWhere . " group by cardno,quanid)";
        //某个时间段结束到现在充值，交易金额查询  $subchong--充值信息  $subtrade--交易信息
        $subchong = "(select customid,quanid,nvl(sum(amount),0) rechargeamount1 from quancz ";
        $subchong .= "where 1=1 " . $subtradeWhere . " group by quanid,customid)";
        $subtrade = "(select cardno,nvl(sum(tradeamount),0) consumeamount1,quanid from trade_wastebooks ";
        $subtrade .= "where  tradetype='02' and flag=0" . $subtradeWhere . " group by cardno,quanid)";
        //结束日期存在时候,根据充值数量和消费数量求出查询数量
        if ($end != '') {
            $total = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->count();
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->field($field1)->page($page, $pageSize)->select();
        } else {
            $total = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->count();
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->field($field)->page($page, $pageSize)->select();
        }
        foreach ($results as $k => $v) {
            if ($v['rechargeamount'] != '') {
                $v['fnrecharamount'] = $v['rechargeamount'];//充值数量
            } else {
                $v['fnrecharamount'] = 0;//充值数量
            }
            $v['fnrecharmoney'] = $v['recharamount'] * floatval($v['quanprice']);//充值金额
            if ($v['consumeamount'] != '') {
                $v['fntradeamount'] = $v['consumeamount'];//消费数量
            } else {
                $v['fntradeamount'] = 0;//消费数量
            }
            $v['fntrademoney'] = $v['tradeamount'] * floatval($v['quanprice']);//消费金额
            $v['quanprice'] = floatval($v['quanprice']);//劵单价
            if ($end == '') {
                $v['fnsearamount'] = '';//查询数量
                $v['fnsearmoney'] = '';//查询金额
            } else {
                $v['fnsearamount'] = bcsub($v['rechargeamount1'], $v['consumeamount1']);//查询数量
                $v['fnsearmoney'] = bcsub($v['rechargeamount1'], $v['consumeamount1']) * floatval($v['quanprice']);//查询金额
            }
            $v['fnamount'] = floatval($v['amount']);//剩余数量
            $v['fnsuramount'] = floatval($v['amount'] * $v['quanprice']);//剩余金额
            //补卡处理
            $whereHotel['flag'] = '1';
            $whereHotel['cardno'] = $v['cardno'];
            //根据新卡查询相关旧卡信息
            $oldcards = M('card_change_logs')->where($whereHotel)->order('placeddate desc')->find();
            if (count($oldcards) > 0) {
                //旧卡新卡共同有的券消费查询条件
                $wheres['cardno'] = $oldcards['precardno'];
                $wheres['tradetype'] = '02';
                $wheres['flag'] = 0;
                $wheres['quanid'] = $v['quanid'];
                //旧卡新卡共同有的券消费查询条件
                $map['ca.cardno'] = $oldcards['precardno'];
                $map['qa.quanid'] = $v['quanid'];
                if ($end != '') {
                    $wheres['placeddate'] = array('elt', $enddate);
                    $map['qa.placeddate'] = array('elt', $enddate);
                    $eoldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                    if ($eoldrecharge) {
                        //消费总额
                        $eoldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                        $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $eoldrecharge['oldamount']), bcadd($v['consumeamount1'], $eoldconsume));//查询数量
                        $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                    }
                }
                //旧卡充值
                $oldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                if ($oldrecharge) {
                    //旧卡消费
                    $oldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                    //总充值、消费
                    $v['fnrecharamount'] = bcadd($v['rechargeamount'], $oldrecharge['oldamount']);//充值数量
                    $v['fnrecharmoney'] = $v['fnrecharamount'] * floatval($v['quanprice']);//充值金额
                    $v['fntradeamount'] = bcadd($v['consumeamount'], $oldconsume);//消费数量
                    $v['fntrademoney'] = $v['fntradeamount'] * floatval($v['quanprice']);//消费金额
                }
                $v['newcard'] = $oldcards['precardno'];//旧卡卡号
                if (empty($cquanid) || empty($cquanname)) {
                    //卡号第一次出现时统计新卡及旧卡所有券ID
                    if ($results[$k]['cardno'] != $results[$k + 1]['cardno']) {
                        //统计旧卡所有券ID
                        $oldqwhere['ca.cardno'] = $oldcards['precardno'];
                        $oldqrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($oldqwhere)->field('qa.quanid')->group('qa.quanid')->select();
                        if ($oldqrecharge) {
                            $oldAllQuanid = array_column($oldqrecharge, 'quanid');
                            //统计新卡所有券ID
                            $newqwhere['ca.cardno'] = array('eq', $v['cardno']);
                            $newqwhere['ac.quanid'] = array('neq', 'null');
                            $newAllQuanid = M('cards')->alias('ca')->join('account ac on ca.customid=ac.customid')->where($newqwhere)->field('quanid')->select();
                            $newAllQuanid = array_column($newAllQuanid, 'quanid');
                            //求出旧卡有新卡没有的券
                            if ($oldAllQuanid) {
                                foreach ($oldAllQuanid as $kk => $vv) {
                                    if (!isset($vv, $newAllQuanid)) {
                                        //旧卡有新卡没有的券充值查询条件
                                        $diffwhere['ca.cardno'] = $oldcards['precardno'];
                                        $diffwhere['qu.quanid'] = $vv;
                                        //旧卡有新卡没有的券查询条件
                                        $diffcwhere['cardno'] = $oldcards['precardno'];
                                        $diffcwhere['quanid'] = $vv;
                                        $diffcwhere['flag'] = 0;
                                        //不同券查询数量
                                        if ($end != '') {
                                            //查询充值
                                            $diffwhere['qu.placeddate'] = array('elt', $enddate);
                                            $ediffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ac.customid')->where($diffwhere)->sum('qu.amount');
                                            //查询消费
                                            $diffcwhere['placeddate'] = array('elt', $enddate);
                                            $ediffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                            $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $ediffresult), bcadd($v['consumeamount1'], $ediffconsume));//查询数量
                                            $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                                        }
                                        //不同券充值、券信息
                                        $diffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ca.customid')
                                            ->join('quankind qk on qk.quanid=qu.quanid')
                                            ->where($diffwhere)->field('sum(qu.amount) diffamount,qk.quanname,qk.amount')->group('qk.quanname,qk.amount')->find();
                                        //不同券消费信息
                                        $diffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                        $v['quanid'] = $vv;
                                        $v['quanname'] = $diffresult['quanname'];
                                        $v['accountid'] = $diffresult['accountid'];
                                        $v['quanprice'] = $diffresult['amount'];
                                        $v['fnrecharamount'] = $diffresult['diffamount'];//充值数量
                                        $v['fnrecharmoney'] = $diffresult['diffamount'] * $diffresult['amount'];//充值金额
                                        $v['fntradeamount'] = $diffconsume;//消费数量
                                        $v['fntrademoney'] = $diffconsume * $diffresult['amount'];//消费金额
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //营销产品余额查询导出
    function productquery_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $end = session('enddate');
        $where = session('produceexcel');
        $subWhere = session('subWhere');
        $subtradeWhere = session('subtradeWhere');


        $field = "h.*,f.rechargeamount,i.consumeamount";
        $field1 = "h.*,f.rechargeamount,i.consumeamount,m.rechargeamount1,n.consumeamount1";
        $subQuery1 = "(select d.cardno,d.cardkind,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.amount quanprice,p.panterid";
        $subQuery1 .= " from cards d,account a,quankind c,customs_c m,customs b,panters p";
        $subQuery1 .= " where d.customid=a.customid and a.quanid=c.quanid and  m.cid=d.customid and b.customid=m.customid and p.panterid=c.panterid and a.type='02')";
        //充值数量
        $subQuery2 = "(select customid,quanid,nvl(sum(amount),0) rechargeamount from quancz  where 1=1 " . $subWhere . " group by quanid,customid)";
        //交易数量
        $subQuery3 = "(select cardno,nvl(sum(tradeamount),0) consumeamount,quanid from trade_wastebooks where  tradetype='02' and flag=0" . $subWhere . " group by cardno,quanid)";
        //某个时间段结束到现在充值，交易金额查询  $subchong--充值信息  $subtrade--交易信息
        $subchong = "(select customid,quanid,nvl(sum(amount),0) rechargeamount1 from quancz ";
        $subchong .= "where 1=1 " . $subtradeWhere . " group by quanid,customid)";
        $subtrade = "(select cardno,nvl(sum(tradeamount),0) consumeamount1,quanid from trade_wastebooks ";
        $subtrade .= "where  tradetype='02' and flag=0" . $subtradeWhere . " group by cardno,quanid)";
        //结束日期存在时候,根据充值数量和消费数量求出查询数量
        if ($end != '') {
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->field($field1)->select();
        } else {
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->field($field)->select();
        }
        foreach ($results as $k => $v) {
            $map = session('map');
            $wheres = session('wheres');
            $oldqwhere = session('oldqwhere');
            $diffwhere = session('diffwhere');
            if ($v['rechargeamount'] != '') {
                $v['fnrecharamount'] = $v['rechargeamount'];//充值数量
            } else {
                $v['fnrecharamount'] = 0;//充值数量
            }
            $v['fnrecharmoney'] = $v['recharamount'] * floatval($v['quanprice']);//充值金额
            if ($v['consumeamount'] != '') {
                $v['fntradeamount'] = $v['consumeamount'];//消费数量
            } else {
                $v['fntradeamount'] = 0;//消费数量
            }
            $v['fntrademoney'] = $v['tradeamount'] * floatval($v['quanprice']);//消费金额
            $v['quanprice'] = floatval($v['quanprice']);//劵单价
            if ($end == '') {
                $v['fnsearamount'] = '';//查询数量
                $v['fnsearmoney'] = '';//查询金额
            } else {
                $v['fnsearamount'] = bcsub($v['rechargeamount1'], $v['consumeamount1']);//查询数量
                $v['fnsearmoney'] = bcsub($v['rechargeamount1'], $v['consumeamount1']) * floatval($v['quanprice']);//查询金额
            }
            $v['fnamount'] = floatval($v['amount']);//剩余数量
            $v['fnsuramount'] = floatval($v['amount'] * $v['quanprice']);//剩余金额
            //补卡处理
            $whereHotel['flag'] = '1';
            $whereHotel['cardno'] = $v['cardno'];
            //根据新卡查询相关旧卡信息
            $oldcards = M('card_change_logs')->where($whereHotel)->order('placeddate desc')->find();
            if (count($oldcards) > 0) {
                //旧卡新卡共同有的券消费查询条件
                $wheres['cardno'] = $oldcards['precardno'];
                $wheres['tradetype'] = '02';
                $wheres['flag'] = 0;
                $wheres['quanid'] = $v['quanid'];
                //旧卡新卡共同有的券消费查询条件
                $map['ca.cardno'] = $oldcards['precardno'];
                $map['qa.quanid'] = $v['quanid'];
                if ($end != '') {
                    $wheres['placeddate'] = array('elt', $end);
                    $map['qa.placeddate'] = array('elt', $end);
                    $eoldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                    if ($eoldrecharge) {
                        //消费总额
                        $eoldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                        $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $eoldrecharge['oldamount']), bcadd($v['consumeamount1'], $eoldconsume));//查询数量
                        $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                    }
                }
                //旧卡充值
                $oldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                if ($oldrecharge) {
                    //旧卡消费
                    $oldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                    //总充值、消费
                    $v['fnrecharamount'] = bcadd($v['rechargeamount'], $oldrecharge['oldamount']);//充值数量
                    $v['fnrecharmoney'] = $v['fnrecharamount'] * floatval($v['quanprice']);//充值金额
                    $v['fntradeamount'] = bcadd($v['consumeamount'], $oldconsume);//消费数量
                    $v['fntrademoney'] = $v['fntradeamount'] * floatval($v['quanprice']);//消费金额
                }
                $v['newcard'] = $oldcards['precardno'];//旧卡卡号
                if (empty($cquanid) || empty($cquanname)) {
                    //卡号第一次出现时统计新卡及旧卡所有券ID
                    if ($results[$k]['cardno'] != $results[$k + 1]['cardno']) {
                        //统计旧卡所有券ID
                        $oldqwhere['ca.cardno'] = $oldcards['precardno'];
                        $oldqrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($oldqwhere)->field('qa.quanid')->group('qa.quanid')->select();
                        if ($oldqrecharge) {
                            $oldAllQuanid = array_column($oldqrecharge, 'quanid');
                            //统计新卡所有券ID
                            $newqwhere['ca.cardno'] = array('eq', $v['cardno']);
                            $newqwhere['ac.quanid'] = array('neq', 'null');
                            $newAllQuanid = M('cards')->alias('ca')->join('account ac on ca.customid=ac.customid')->where($newqwhere)->field('quanid')->select();
                            $newAllQuanid = array_column($newAllQuanid, 'quanid');
                            //求出旧卡有新卡没有的券
                            if ($oldAllQuanid) {
                                foreach ($oldAllQuanid as $kk => $vv) {
                                    if (!isset($vv, $newAllQuanid)) {
                                        //旧卡有新卡没有的券充值查询条件
                                        $diffwhere['ca.cardno'] = $oldcards['precardno'];
                                        $diffwhere['qu.quanid'] = $vv;
                                        //旧卡有新卡没有的券查询条件
                                        $diffcwhere['cardno'] = $oldcards['precardno'];
                                        $diffcwhere['quanid'] = $vv;
                                        $diffcwhere['flag'] = 0;
                                        //不同券查询数量
                                        if ($end != '') {
                                            //查询充值
                                            $diffwhere['qu.placeddate'] = array('elt', $end);
                                            $ediffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ac.customid')->where($diffwhere)->sum('qu.amount');
                                            //查询消费
                                            $diffcwhere['placeddate'] = array('elt', $end);
                                            $ediffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                            $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $ediffresult), bcadd($v['consumeamount1'], $ediffconsume));//查询数量
                                            $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                                        }
                                        //不同券充值、券信息
                                        $diffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ca.customid')
                                            ->join('quankind qk on qk.quanid=qu.quanid')
                                            ->where($diffwhere)->field('sum(qu.amount) diffamount,qk.quanname,qk.amount')->group('qk.quanname,qk.amount')->find();
                                        //不同券消费信息
                                        $diffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                        $v['quanid'] = $vv;
                                        $v['quanname'] = $diffresult['quanname'];
                                        $v['accountid'] = $diffresult['accountid'];
                                        $v['quanprice'] = $diffresult['amount'];
                                        $v['fnrecharamount'] = $diffresult['diffamount'];//充值数量
                                        $v['fnrecharmoney'] = $diffresult['diffamount'] * $diffresult['amount'];//充值金额
                                        $v['fntradeamount'] = $diffconsume;//消费数量
                                        $v['fntrademoney'] = $diffconsume * $diffresult['amount'];//消费金额
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:O3";
        $titleName = '酒店营销劵余额报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'P4';
        $headerArray = array('卡号', '补卡卡号', '会员编号', '会员名称',
            '营销产品编号', '营销产品名称', '账户编号', '券单价',
            '充值数量', '充值金额', '消费数量', '消费金额', '查询剩余数量', '查询剩余金额', '实际剩余数量', '实际剩余金额'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
        $setWidth = array(25, 25, 15, 20, 15, 80, 15, 15, 15, 15, 15, 15, 15, 15, 15, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("H" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("J" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("K" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("L" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("M" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("N" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("O" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['newcard'])->setCellValue('C' . $j, "'" . $val['customid'])
                ->setCellValue('D' . $j, "'" . $val['namechinese'])->setCellValue('E' . $j, "'" . $val['quanid'])->setCellValue('F' . $j, "'" . $val['quanname'])
                ->setCellValue('G' . $j, "'" . $val['accountid'])->setCellValue('H' . $j, $val['quanprice'])->setCellValue('I' . $j, $val['fnrecharamount'])
                ->setCellValue('J' . $j, $val['fnrecharmoney'])->setCellValue('K' . $j, $val['fntradeamount'])->setCellValue('L' . $j, $val['fntrademoney'])
                ->setCellValue('M' . $j, $val['fnsearamount'])->setCellValue('N' . $j, $val['fnsearmoney'])->setCellValue('O' . $j, $val['fnamount'])->setCellValue('P' . $j, $val['fnsuramount']);
            $j++;
        }
        ob_end_clean();
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店营销劵余额报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //新券营销产品余额报表列表
    function newProductQuery()
    {
        $this->display();
    }

    //营销产品余额报表列表数据
    function newGetProductQueryList()
    {
        $model = new Model();
        $start = trim(I('get.startdate', ''));//开始日期
        $end = trim(I('get.enddate', ''));//结束日期
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cquanid = trim(I('get.quanid', ''));
        $cquanname = trim(I('get.quanname', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $cquanid = $cquanid == "营销产品编号" ? "" : $cquanid;
        $cquanname = $cquanname == "营销产品名称" ? "" : $cquanname;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $subWhere = ' and placeddate>=' . $startdate;
            $map['qa.placeddate'] = array('egt', $startdate);
            $wheres['placeddate'] = array('egt', $startdate);
            $oldqwhere['qa.placeddate'] = array('egt', $startdate);
            $diffwhere['qa.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $subWhere = " and placeddate<='" . $enddate . "'";
            $subtradeWhere = " and placeddate<='" . $enddate . "'";
            $map['qa.placeddate'] = array('elt', $enddate);
            $wheres['placeddate'] = array('elt', $enddate);
            $oldqwhere['qa.placeddate'] = array('elt', $enddate);
            $diffwhere['qa.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $subWhere = " and placeddate>='" . $startdate . "' and placeddate<='" . $enddate . "'";
            $subtradeWhere = " and placeddate<='" . $enddate . "'";
            $map['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $wheres['placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $oldqwhere['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $diffwhere['qa.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cardnoc != '') {
            $cardnoc = explode(',', $cardnoc);
            $where['h.cardno'] = array('in', $cardnoc);
        }
        if ($customsid != '') {
            $where['h.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['h.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($cquanid != '') {
            $where['h.quanid'] = $cquanid;
            $wheres['quanid'] = $cquanid;
            $map['quanid'] = $cquanid;
        }
        if ($cquanname != '') {
            $where['h.quanname'] = $cquanname;
            $wheres['quanname'] = $cquanname;
            $map['quanname'] = $cquanname;
        }
        $where['h.cardkind'] = array('neq', '6883');
        if ($this->panterid != 'FFFFFFFF') {
            $where['h.panterid'] = $this->panterid;
            $map['panterid'] = $this->panterid;
        }
        session('enddate', $enddate);
        session('produceexcel', $where);
        session('subWhere', $subWhere);
        session('subtradeWhere', $subtradeWhere);
        session('map', $map);
        session('wheres', $wheres);
        session('oldqwhere', $oldqwhere);
        session('diffwhere', $diffwhere);
        $field = "h.*,f.rechargeamount,i.consumeamount";
        $field1 = "h.*,f.rechargeamount,i.consumeamount,m.rechargeamount1,n.consumeamount1";
        $subQuery1 = "(select d.cardno,d.cardkind,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.amount quanprice,p.panterid";
        $subQuery1 .= " from cards d,quan_account a,quankind c,customs_c m,customs b,panters p";
        $subQuery1 .= " where d.customid=a.customid and a.quanid=c.quanid and  m.cid=d.customid and b.customid=m.customid and p.panterid=c.panterid and d.status='Y')";
        //充值数量
        $subQuery2 = "(select customid,quanid,nvl(sum(amount),0) rechargeamount from quancz  where 1=1 " . $subWhere . " group by quanid,customid)";
        //交易数量
        $subQuery3 = "(select cardno,nvl(sum(tradeamount),0) consumeamount,quanid from trade_wastebooks where  tradetype='02' and flag=0" . $subWhere . " group by cardno,quanid)";
        //某个时间段结束到现在充值，交易金额查询  $subchong--充值信息  $subtrade--交易信息
        $subchong = "(select customid,quanid,nvl(sum(amount),0) rechargeamount1 from quancz ";
        $subchong .= "where 1=1 " . $subtradeWhere . " group by quanid,customid)";
        $subtrade = "(select cardno,nvl(sum(tradeamount),0) consumeamount1,quanid from trade_wastebooks ";
        $subtrade .= "where  tradetype='02' and flag=0" . $subtradeWhere . " group by cardno,quanid)";
        //结束日期存在时候,根据充值数量和消费数量求出查询数量
        if ($end != '') {
            $total = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->count();
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->field($field1)->page($page, $pageSize)->select();
        } else {
            $total = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->count();
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->field($field)->page($page, $pageSize)->select();
        }
        foreach ($results as $k => $v) {
            if ($v['rechargeamount'] != '') {
                $v['fnrecharamount'] = $v['rechargeamount'];//充值数量
            } else {
                $v['fnrecharamount'] = 0;//充值数量
            }
            $v['fnrecharmoney'] = $v['recharamount'] * floatval($v['quanprice']);//充值金额
            if ($v['consumeamount'] != '') {
                $v['fntradeamount'] = $v['consumeamount'];//消费数量
            } else {
                $v['fntradeamount'] = 0;//消费数量
            }
            $v['fntrademoney'] = $v['tradeamount'] * floatval($v['quanprice']);//消费金额
            $v['quanprice'] = floatval($v['quanprice']);//劵单价
            if ($end == '') {
                $v['fnsearamount'] = '';//查询数量
                $v['fnsearmoney'] = '';//查询金额
            } else {
                $v['fnsearamount'] = bcsub($v['rechargeamount1'], $v['consumeamount1']);//查询数量
                $v['fnsearmoney'] = bcsub($v['rechargeamount1'], $v['consumeamount1']) * floatval($v['quanprice']);//查询金额
            }
            $v['fnamount'] = floatval($v['amount']);//剩余数量
            $v['fnsuramount'] = floatval($v['amount'] * $v['quanprice']);//剩余金额
            //补卡处理
            $whereHotel['flag'] = '1';
            $whereHotel['cardno'] = $v['cardno'];
            //根据新卡查询相关旧卡信息
            $oldcards = M('card_change_logs')->where($whereHotel)->order('placeddate desc')->find();
            if (count($oldcards) > 0) {
                //旧卡新卡共同有的券消费查询条件
                $wheres['cardno'] = $oldcards['precardno'];
                $wheres['tradetype'] = '02';
                $wheres['flag'] = 0;
                $wheres['quanid'] = $v['quanid'];
                //旧卡新卡共同有的券消费查询条件
                $map['ca.cardno'] = $oldcards['precardno'];
                $map['qa.quanid'] = $v['quanid'];
                $map['qa.placeddate'] = array('gt', '20170610');
                if ($end != '') {
                    $wheres['placeddate'] = array('elt', $enddate);
                    $map['qa.placeddate'] = array('elt', $enddate);
                    $eoldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                    if ($eoldrecharge) {
                        //消费总额
                        $eoldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                        $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $eoldrecharge['oldamount']), bcadd($v['consumeamount1'], $eoldconsume));//查询数量
                        $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                    }
                }
                //旧卡充值
                $oldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                if ($oldrecharge) {
                    //旧卡消费
                    $oldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                    //总充值、消费
                    $v['fnrecharamount'] = bcadd($v['rechargeamount'], $oldrecharge['oldamount']);//充值数量
                    $v['fnrecharmoney'] = $v['fnrecharamount'] * floatval($v['quanprice']);//充值金额
                    $v['fntradeamount'] = bcadd($v['consumeamount'], $oldconsume);//消费数量
                    $v['fntrademoney'] = $v['fntradeamount'] * floatval($v['quanprice']);//消费金额
                }
                $v['newcard'] = $oldcards['precardno'];//旧卡卡号
                if (empty($cquanid) || empty($cquanname)) {
                    //卡号第一次出现时统计新卡及旧卡所有券ID
                    if ($results[$k]['cardno'] != $results[$k + 1]['cardno']) {
                        //统计旧卡所有券ID
                        $oldqwhere['ca.cardno'] = $oldcards['precardno'];
                        $oldqrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($oldqwhere)->field('qa.quanid')->group('qa.quanid')->select();
                        if ($oldqrecharge) {
                            $oldAllQuanid = array_column($oldqrecharge, 'quanid');
                            //统计新卡所有券ID
                            $newqwhere['ca.cardno'] = array('eq', $v['cardno']);
                            $newqwhere['ac.quanid'] = array('neq', 'null');
                            $newAllQuanid = M('cards')->alias('ca')->join('account ac on ca.customid=ac.customid')->where($newqwhere)->field('quanid')->select();
                            $newAllQuanid = array_column($newAllQuanid, 'quanid');
                            //求出旧卡有新卡没有的券
                            if ($oldAllQuanid) {
                                foreach ($oldAllQuanid as $kk => $vv) {
                                    if (!isset($vv, $newAllQuanid)) {
                                        //旧卡有新卡没有的券充值查询条件
                                        $diffwhere['ca.cardno'] = $oldcards['precardno'];
                                        $diffwhere['qu.quanid'] = $vv;
                                        //旧卡有新卡没有的券查询条件
                                        $diffcwhere['cardno'] = $oldcards['precardno'];
                                        $diffcwhere['quanid'] = $vv;
                                        $diffcwhere['flag'] = 0;
                                        //不同券查询数量
                                        if ($end != '') {
                                            //查询充值
                                            $diffwhere['qu.placeddate'] = array('elt', $enddate);
                                            $ediffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ac.customid')->where($diffwhere)->sum('qu.amount');
                                            //查询消费
                                            $diffcwhere['placeddate'] = array('elt', $enddate);
                                            $ediffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                            $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $ediffresult), bcadd($v['consumeamount1'], $ediffconsume));//查询数量
                                            $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                                        }
                                        //不同券充值、券信息
                                        $diffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ca.customid')
                                            ->join('quankind qk on qk.quanid=qu.quanid')
                                            ->where($diffwhere)->field('sum(qu.amount) diffamount,qk.quanname,qk.amount')->group('qk.quanname,qk.amount')->find();
                                        //不同券消费信息
                                        $diffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                        $v['quanid'] = $vv;
                                        $v['quanname'] = $diffresult['quanname'];
                                        $v['accountid'] = $diffresult['accountid'];
                                        $v['quanprice'] = $diffresult['amount'];
                                        $v['fnrecharamount'] = $diffresult['diffamount'];//充值数量
                                        $v['fnrecharmoney'] = $diffresult['diffamount'] * $diffresult['amount'];//充值金额
                                        $v['fntradeamount'] = $diffconsume;//消费数量
                                        $v['fntrademoney'] = $diffconsume * $diffresult['amount'];//消费金额
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //新营销产品余额查询导出
    function newproductquery_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $end = session('enddate');
        $where = session('produceexcel');
        $subWhere = session('subWhere');
        $subtradeWhere = session('subtradeWhere');


        $field = "h.*,f.rechargeamount,i.consumeamount";
        $field1 = "h.*,f.rechargeamount,i.consumeamount,m.rechargeamount1,n.consumeamount1";
        $subQuery1 = "(select d.cardno,d.cardkind,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.amount quanprice,p.panterid";
        $subQuery1 .= " from cards d,quan_account a,quankind c,customs_c m,customs b,panters p";
        $subQuery1 .= " where d.customid=a.customid and a.quanid=c.quanid and  m.cid=d.customid and b.customid=m.customid and p.panterid=c.panterid)";
        //充值数量
        $subQuery2 = "(select customid,quanid,nvl(sum(amount),0) rechargeamount from quancz  where 1=1 " . $subWhere . " group by quanid,customid)";
        //交易数量
        $subQuery3 = "(select cardno,nvl(sum(tradeamount),0) consumeamount,quanid from trade_wastebooks where  tradetype='02' and flag=0" . $subWhere . " group by cardno,quanid)";
        //某个时间段结束到现在充值，交易金额查询  $subchong--充值信息  $subtrade--交易信息
        $subchong = "(select customid,quanid,nvl(sum(amount),0) rechargeamount1 from quancz ";
        $subchong .= "where 1=1 " . $subtradeWhere . " group by quanid,customid)";
        $subtrade = "(select cardno,nvl(sum(tradeamount),0) consumeamount1,quanid from trade_wastebooks ";
        $subtrade .= "where  tradetype='02' and flag=0" . $subtradeWhere . " group by cardno,quanid)";
        //结束日期存在时候,根据充值数量和消费数量求出查询数量
        if ($end != '') {
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->join('left join ' . $subchong . '  m  on h.customid1=m.customid and h.quanid=m.quanid')
                ->join('left join ' . $subtrade . '  n  on n.cardno=h.cardno and h.quanid=n.quanid')
                ->where($where)->field($field1)->select();
        } else {
            //账户表--卡号表--会员编号对应表--会员信息表--商户表
            $results = $model->table($subQuery1)->alias('h')
                ->join('left join ' . $subQuery2 . ' f  on h.customid1=f.customid and h.quanid=f.quanid')
                ->join('left join ' . $subQuery3 . ' i  on i.cardno=h.cardno and i.quanid=h.quanid')
                ->where($where)->field($field)->select();
        }
        foreach ($results as $k => $v) {
            $map = session('map');
            $wheres = session('wheres');
            $oldqwhere = session('oldqwhere');
            $diffwhere = session('diffwhere');
            if ($v['rechargeamount'] != '') {
                $v['fnrecharamount'] = $v['rechargeamount'];//充值数量
            } else {
                $v['fnrecharamount'] = 0;//充值数量
            }
            $v['fnrecharmoney'] = $v['recharamount'] * floatval($v['quanprice']);//充值金额
            if ($v['consumeamount'] != '') {
                $v['fntradeamount'] = $v['consumeamount'];//消费数量
            } else {
                $v['fntradeamount'] = 0;//消费数量
            }
            $v['fntrademoney'] = $v['tradeamount'] * floatval($v['quanprice']);//消费金额
            $v['quanprice'] = floatval($v['quanprice']);//劵单价
            if ($end == '') {
                $v['fnsearamount'] = '';//查询数量
                $v['fnsearmoney'] = '';//查询金额
            } else {
                $v['fnsearamount'] = bcsub($v['rechargeamount1'], $v['consumeamount1']);//查询数量
                $v['fnsearmoney'] = bcsub($v['rechargeamount1'], $v['consumeamount1']) * floatval($v['quanprice']);//查询金额
            }
            $v['fnamount'] = floatval($v['amount']);//剩余数量
            $v['fnsuramount'] = floatval($v['amount'] * $v['quanprice']);//剩余金额
            //补卡处理
            $whereHotel['flag'] = '1';
            $whereHotel['cardno'] = $v['cardno'];
            //根据新卡查询相关旧卡信息
            $oldcards = M('card_change_logs')->where($whereHotel)->order('placeddate desc')->find();
            if (count($oldcards) > 0) {
                //旧卡新卡共同有的券消费查询条件
                $wheres['cardno'] = $oldcards['precardno'];
                $wheres['tradetype'] = '02';
                $wheres['flag'] = 0;
                $wheres['quanid'] = $v['quanid'];
                //旧卡新卡共同有的券消费查询条件
                $map['ca.cardno'] = $oldcards['precardno'];
                $map['qa.quanid'] = $v['quanid'];
                $map['qa.placeddate'] = array('gt', '20170610');
                if ($end != '') {
                    $wheres['placeddate'] = array('elt', $end);
                    $map['qa.placeddate'] = array('elt', $end);
                    $eoldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                    if ($eoldrecharge) {
                        //消费总额
                        $eoldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                        $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $eoldrecharge['oldamount']), bcadd($v['consumeamount1'], $eoldconsume));//查询数量
                        $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                    }
                }
                //旧卡充值
                $oldrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($map)->field('sum(qa.amount) oldamount')->find();
                if ($oldrecharge) {
                    //旧卡消费
                    $oldconsume = M('trade_wastebooks')->where($wheres)->sum('tradeamount');
                    //总充值、消费
                    $v['fnrecharamount'] = bcadd($v['rechargeamount'], $oldrecharge['oldamount']);//充值数量
                    $v['fnrecharmoney'] = $v['fnrecharamount'] * floatval($v['quanprice']);//充值金额
                    $v['fntradeamount'] = bcadd($v['consumeamount'], $oldconsume);//消费数量
                    $v['fntrademoney'] = $v['fntradeamount'] * floatval($v['quanprice']);//消费金额
                }
                $v['newcard'] = $oldcards['precardno'];//旧卡卡号
                if (empty($cquanid) || empty($cquanname)) {
                    //卡号第一次出现时统计新卡及旧卡所有券ID
                    if ($results[$k]['cardno'] != $results[$k + 1]['cardno']) {
                        //统计旧卡所有券ID
                        $oldqwhere['ca.cardno'] = $oldcards['precardno'];
                        $oldqrecharge = M('cards')->alias('ca')->join('quancz qa on ca.customid=qa.customid')->where($oldqwhere)->field('qa.quanid')->group('qa.quanid')->select();
                        if ($oldqrecharge) {
                            $oldAllQuanid = array_column($oldqrecharge, 'quanid');
                            //统计新卡所有券ID
                            $newqwhere['ca.cardno'] = array('eq', $v['cardno']);
                            $newqwhere['ac.quanid'] = array('neq', 'null');
                            $newAllQuanid = M('cards')->alias('ca')->join('account ac on ca.customid=ac.customid')->where($newqwhere)->field('quanid')->select();
                            $newAllQuanid = array_column($newAllQuanid, 'quanid');
                            //求出旧卡有新卡没有的券
                            if ($oldAllQuanid) {
                                foreach ($oldAllQuanid as $kk => $vv) {
                                    if (!isset($vv, $newAllQuanid)) {
                                        //旧卡有新卡没有的券充值查询条件
                                        $diffwhere['ca.cardno'] = $oldcards['precardno'];
                                        $diffwhere['qu.quanid'] = $vv;
                                        //旧卡有新卡没有的券查询条件
                                        $diffcwhere['cardno'] = $oldcards['precardno'];
                                        $diffcwhere['quanid'] = $vv;
                                        $diffcwhere['flag'] = 0;
                                        //不同券查询数量
                                        if ($end != '') {
                                            //查询充值
                                            $diffwhere['qu.placeddate'] = array('elt', $end);
                                            $ediffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ac.customid')->where($diffwhere)->sum('qu.amount');
                                            //查询消费
                                            $diffcwhere['placeddate'] = array('elt', $end);
                                            $ediffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                            $v['fnsearamount'] = bcsub(bcadd($v['rechargeamount1'], $ediffresult), bcadd($v['consumeamount1'], $ediffconsume));//查询数量
                                            $v['fnsearmoney'] = $v['fnsearamount'] * floatval($v['quanprice']);//查询金额
                                        }
                                        //不同券充值、券信息
                                        $diffresult = M('cards')->alias('ca')->join('quancz qu on qu.customid=ca.customid')
                                            ->join('quankind qk on qk.quanid=qu.quanid')
                                            ->where($diffwhere)->field('sum(qu.amount) diffamount,qk.quanname,qk.amount')->group('qk.quanname,qk.amount')->find();
                                        //不同券消费信息
                                        $diffconsume = M('trade_wastebooks')->where($diffcwhere)->sum('tradeamount');
                                        $v['quanid'] = $vv;
                                        $v['quanname'] = $diffresult['quanname'];
                                        $v['accountid'] = $diffresult['accountid'];
                                        $v['quanprice'] = $diffresult['amount'];
                                        $v['fnrecharamount'] = $diffresult['diffamount'];//充值数量
                                        $v['fnrecharmoney'] = $diffresult['diffamount'] * $diffresult['amount'];//充值金额
                                        $v['fntradeamount'] = $diffconsume;//消费数量
                                        $v['fntrademoney'] = $diffconsume * $diffresult['amount'];//消费金额
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:O3";
        $titleName = '酒店新营销劵余额报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'P4';
        $headerArray = array('卡号', '补卡卡号', '会员编号', '会员名称',
            '营销产品编号', '营销产品名称', '账户编号', '券单价',
            '充值数量', '充值金额', '消费数量', '消费金额', '查询剩余数量', '查询剩余金额', '实际剩余数量', '实际剩余金额'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
        $setWidth = array(25, 25, 15, 20, 15, 80, 15, 15, 15, 15, 15, 15, 15, 15, 15, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("H" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("J" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("K" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("L" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("M" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("N" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("O" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['newcard'])->setCellValue('C' . $j, "'" . $val['customid'])
                ->setCellValue('D' . $j, "'" . $val['namechinese'])->setCellValue('E' . $j, "'" . $val['quanid'])->setCellValue('F' . $j, "'" . $val['quanname'])
                ->setCellValue('G' . $j, "'" . $val['accountid'])->setCellValue('H' . $j, $val['quanprice'])->setCellValue('I' . $j, $val['fnrecharamount'])
                ->setCellValue('J' . $j, $val['fnrecharmoney'])->setCellValue('K' . $j, $val['fntradeamount'])->setCellValue('L' . $j, $val['fntrademoney'])
                ->setCellValue('M' . $j, $val['fnsearamount'])->setCellValue('N' . $j, $val['fnsearmoney'])->setCellValue('O' . $j, $val['fnamount'])->setCellValue('P' . $j, $val['fnsuramount']);
            $j++;
        }
        ob_end_clean();
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店新营销劵余额报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //营销产品余额查询导出
    function yuanproductquery_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $field = 'd.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname';
        if (isset($_SESSION['produceexcel'])) {
            $recmap = session('produceexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $field = 'd.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname';
        $account_list = M('account')->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('d.cardno desc')->select();
        $quancz = M('quancz');
        $tradeWB = M('trade_wastebooks');
        $strlist = "卡号,会员名称,营销劵编号,劵名称,充值数量,消费数量,余额";
        //$strlist=iconv('utf-8','gbk',$strlist);
        $strlist .= "\n";

        foreach ($account_list as $key => $val) {
            $map1 = array('customid' => $val['customid1'], 'quanid' => $val['quanid']);
            $czamount = intval($quancz->where($map1)->sum('amount'));
            $map2 = array('cardno' => $val['cardno'], 'tradetype' => '02', 'quanid' => $val['quanid']);
            $consumeamount = intval($tradeWB->where($map2)->sum('tradeamount'));
            $val['namechinese'] = iconv('utf-8', 'gbk', $val['namechinese']);

            $strlist .= $val['cardno'] . "\t," . $val['namechinese'] . "," . $val['quanid'];
            $strlist .= "\t," . $val['quanname'] . "," . $czamount . ',' . $consumeamount . ',';
            $strlist .= $val['amount'] . "\n";
        }
        $filename = '劵余额报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($account_list);
        $this->load_csv($strlist, $filename);
    }

    public function initcard()
    {
        $cards = M('cards');
        $tempo = M('tempo');
        if (IS_POST) {
            $cardno = trim(I('post.cardno', ''));
            $flag = '1';
            $tempowhere['cardno'] = $cardno;
            $tempowhere['flag'] = $flag;
            $cuname = trim(I('post.cuname', ''));
            $customid = trim(I('post.customid', ''));
            $status = trim(I('post.card_status', ''));
            $cardbalance = trim(I('post.cardbalance', ''));
            //判断该卡是否来自批量售卡
            $bool = $tempo->where($tempowhere)->find();
            if ($bool) {
                $cards->startTrans();
                //1初始化卡
                $carddata['status'] = 'N';
                $carddata['customid'] = '0';
                $cardif = $cards->where("cardno=$cardno")->save($carddata);
                $msg['1'] = $cards->getlastSql();
                //2处理生成的customid
                $customs_c = M('customs_c');
                $customs = M('customs');
                $customsif = $customs->where("customid=$customid")->delete();
                $customs_cif = $customs_c->where("cid=$customid")->delete();
                $arr = array();
                $arr['1'] = $customs->getlastSql();
                $arr['2'] = $customs_c->getlastSql();
                $msg['2'] = $arr;
                if ($customsif == true && $customs_cif == true) {
                    $customif = true;
                } else {
                    $this->error('会员表信息初始化失败!');
                    $cards->roll();
                }
                //3删除购卡单记录card_purchase_logs
                $cards_logs = M('card_purchase_logs');
                $listinfo = $cards_logs->where("cardno=$cardno")->find();
                if ($listinfo) {
                    $purchaseid = $listinfo['purchaseid'];
                    $cards_logsif = $cards_logs->where("purchaseid=$purchaseid")->delete();
                    $msg['3'] = $cards_logs->getlastSql();
                } else {
                    $cards_logsif = false;
                    $this->error('该卡查询不到购卡单号!');
                }
                //4custom_purchase_logs信息删除
                $custom_purchase_logs = M('custom_purchase_logs');
                $custom_purchase_logsif = $custom_purchase_logs->where("purchaseid=" . "'" . $purchaseid . "'")->delete();
                $msg['4'] = $custom_purchase_logs->getlastSql();
                //5删除卡激活记录的card_active_logs
                $active = M('card_active_logs');
                $activeif = $active->where("cardno=$cardno")->delete();
                $msg['5'] = $active->getLastSql();
                //6删除账户中信息
                $account = M('account');
                $accountif = $account->where("customid=" . "'" . $customid . "'")->delete();
                $msg['6'] = $account->getlastSql();
                //7删除tempo 批量充值信息
                $tempoif = $tempo->where($tempowhere)->delete();
                $msg['7'] = $tempo->getlastSql();
                //8确认提交事务
                if ($cardif == true && $customif == true && $custom_purchase_logsif == true && $cards_logsif == true && $activeif == true && $accountif == true && $tempoif == true) {
                    $cards->commit();
                    $msg['userid'] = $this->userid;
                    file_put_contents('./hotelinit.log', $msg, FILE_APPEND);
                    $this->success('卡初始化成功', U('Hotels/initcard'));

                } else {
                    $cards->rollback();
                    $this->error('初始化失败!');
                    exit;
                }
            } else {
                $cards->startTrans();
                //1初始化卡
                $carddata['status'] = 'N';
                $carddata['customid'] = '0';
                $cardif = $cards->where("cardno=$cardno")->save($carddata);
                $msg['1'] = $cards->getlastSql();
                //2处理生成的customsid
                $customs_c = M('customs_c');
                $customs = M('customs');
                $custom_list = $customs->where("customid=$customid")->find();
                if ($custom_list) {
                    //customds删除信息
                    $customsif = $customs->where("customid=$customid")->delete();
                    $customs_cif = $customs_c->where("cid=$customid")->delete();
                    $arr = array();
                    $arr['1'] = $customs->getlastSql();
                    $arr['2'] = $customs_c->getlastSql();
                    $msg['2'] = $arr;
                    if ($customsif == true && $customs_cif == true) {
                        $customif = true;
                    } else {
                        $this->error('会员表信息初始化失败!');
                        $cards->roll();
                    }
                } else {
                    $customs_c_list = $customs_c->where("cid=$customid")->find();
                    if ($customs_c_list) {
                        $customif = $customs_c->where("cid=$customid")->delete();
                        $msg['2'] = $customs_c->getlastSql();
                    } else {
                        $customif = false;
                        $cards->roll();
                    }

                }
                //3删除购卡单记录card_purchase_logs
                $cards_logs = M('card_purchase_logs');
                $listinfo = $cards_logs->where("cardno=$cardno")->find();
                if ($listinfo) {
                    $purchaseid = $listinfo['purchaseid'];
                    $cards_logsif = $cards_logs->where("purchaseid=$purchaseid")->delete();
                    $msg['3'] = $cards_logs->getlastSql();
                } else {
                    $cards_logsif = false;
                    $this->error('该卡查询不到购卡单号!');
                }
                //4custom_purchase_logs信息删除
                $custom_purchase_logs = M('custom_purchase_logs');
                $custom_purchase_logsif = $custom_purchase_logs->where("purchaseid=" . "'" . $purchaseid . "'")->delete();
                $msg['4'] = $custom_purchase_logs->getlastSql();
                //5删除审核audit_logs审核通过记录
                $audit = M('audit_logs');
                $auditif = $audit->where("purchaseid=$purchaseid")->delete();
                $msg['5'] = $audit->getlastSql();
                //6删除卡激活记录的card_active_logs
                $active = M('card_active_logs');
                $activeif = $active->where("cardno=$cardno")->delete();
                $msg['6'] = $active->getLastSql();
                //7删除账户中信息
                $account = M('account');
                $accountif = $account->where("customid=" . "'" . $customid . "'")->delete();
                $msg['6'] = $account->getLastSql();
                //8确认提交
                if ($cardif == true && $customif == true && $custom_purchase_logsif == true && $auditif == true && $cards_logsif == true && $activeif == true && $accountif == true) {
                    $cards->commit();
                    $msg['userid'] = $this->userid;
                    file_put_contents('./hotelinit.log', $msg, FILE_APPEND);
                    $this->success('卡初始化成功', U('Hotels/initcard'));

                } else {
                    $cards->rollback();
                    $this->error('初始化失败!');
                    exit;
                }
            }

        } else {
            $this->display();
        }
    }

    //卡初始化ajax查询卡状态
    public function card_query()
    {
        $cards = M('cards');
        $trade = M('trade_wastebooks');
        if (IS_POST) {
            $cardno = $_POST['cardno'];
            if ($res = $cards->where("cardno=$cardno")->find()) {
                if ($res['cardkind'] != '6882') {
                    $arr['msg'] = '此卡不是酒店的卡!';
                    $arr['success'] = '0';
                    echo json_encode($arr);
                    exit;
                }
                if ($res['status'] != 'Y') {
                    $arr['msg'] = '此卡不是正常卡!';
                    $arr['success'] = '0';
                    echo json_encode($arr);
                    exit;
                }
                if ($trade->where("cardno=$cardno")->find()) {
                    $arr['msg'] = '此卡有交易记录!';
                    $arr['success'] = '0';
                    echo json_encode($arr);
                    exit;
                }
                $where['c.cardno'] = $cardno;
                $where['a.type'] = '00';
                $where['a1.type'] = '01';
                $field = "c.cardno,c.status,c.exdate,cu.namechinese cuname,c.customid,cc.cid,a.amount card_money,a1.amount card_points";
                $card_list = $cards->alias('c')->JOIN('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->JOIN('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->JOIN('left join __ACCOUNT__ a on a.customid=c.customid')
                    ->JOIN('left join __ACCOUNT__ a1 on a1.customid=c.customid')
                    ->where($where)->field($field)->find();
                $card_list['card_money'] = !empty($card_list['card_money']) ? $card_list['card_money'] : 0;
                $card_balance['card_points'] = !empty($card_balance['card_points']) ? $card_balance['card_points'] : 0;
                $arr['data'] = $card_list;
                $arr['success'] = '1';
                echo json_encode($arr);

            } else {
                $arr['msg'] = '未查询到该卡号,请核对!';
                $arr['success'] = '0';
                echo json_encode($arr);
            }
        } else {
            $arr['msg'] = '非法操作!';
            $arr['success'] = '0';
            echo json_encode($arr);
        }
    }

    //消费/充值排行榜 by 陈星
    function chart()
    {
        $model = new Model();
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $type = I('get.chanxun', '');
        $type = $type == "" ? "1" : $type;
        if ($type == '1') {
            if ($start != '' && $end == '') {
                $startdate = str_replace('-', '', $start);
                $where['b.placeddate'] = array('egt', $startdate);
                $this->assign('startdate', $start);
                $map['startdate'] = $start;
            }
            if ($start == '' && $end != '') {
                $enddate = str_replace('-', '', $end);
                $where['b.placeddate'] = array('elt', $enddate);
                $this->assign('enddate', $end);
                $map['enddate'] = $end;
            }
            if ($start != '' && $end != '') {
                $startdate = str_replace('-', '', $start);
                $enddate = str_replace('-', '', $end);
                $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                $this->assign('startdate', $start);
                $this->assign('enddate', $end);
                $map['startdate'] = $start;
                $map['enddate'] = $end;
            }
            if ($start == '' && $end == '') {
                $startdate = date('Ym01', strtotime(date('Ymd')));
                $enddate = date('Ymd', time());
                $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
                $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
            }
            $where['card.cardkind'] = '6882';
            $field = 'b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,b.flag,';
            $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,
				p.hysx,c.customid,c.namechinese cname,card.Cardkind,card.status,u.username,p1.namechinese pname1';

            $recharge_list = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
                ->join('left join __CARDS__ card on card.cardno=b.cardno')
                ->join('left join __PANTERS__ p on p.panterid=b.panterid')
                ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
                ->join(' __CUSTOMS__ c on c.customid=f.customid')
                ->join('left join __USERS__ u on u.userid=b.userid')
                ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                ->field($field)->where($where)->order('amount desc')->limit(10)->select();
            $this->assign('chanxun', $type);
            $this->assign('list', $recharge_list);
        } else {
            if ($start != '' && $end == '') {
                $startdate = str_replace('-', '', $start);
                $where['t.placeddate'] = array('egt', $startdate);
                $this->assign('startdate', $start);
                $map['startdate'] = $start;
            }
            if ($start == '' && $end != '') {
                $enddate = str_replace('-', '', $end);
                $where['t.placeddate'] = array('elt', $enddate);
                $this->assign('enddate', $end);
                $map['enddate'] = $end;
            }
            if ($start != '' && $end != '') {
                $startdate = str_replace('-', '', $start);
                $enddate = str_replace('-', '', $end);
                $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                $this->assign('startdate', $start);
                $this->assign('enddate', $end);
                $map['startdate'] = $start;
                $map['enddate'] = $end;
            }
            if ($start == '' && $end == '') {
                $startdate = date('Ym01', strtotime(date('Ymd')));
                $enddate = date('Ymd', time());
                $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
                $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
            }
            //$where['p.hysx']='酒店';
            $where['card.cardkind'] = '6882';
            $field1 = 't.cardno,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
            $field1 .= 't.flag,t.addpoint,p.namechinese pname,p.hysx,custom.namechinese cuname,custom.customid,p1.namechinese pname1';
            $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
            $consume_list = $model->table('trade_wastebooks')->alias('t')
                ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                ->join('left join __CARDS__ card on card.cardno=t.cardno')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
                ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
                ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
                ->field($field1)->where($where)->limit(10)
                ->order("t.tradeamount desc")->select();
            //echo   $model->getLastSql();
            $this->assign('chanxun', $type);
            $this->assign('jytype', $jytype);
            $this->assign('clist', $consume_list);
        }

        session('type', $where);
        $this->display();
    }

    //充值排行页面
    public function rechargtion()
    {
        $this->display();
    }

    //充值排行数据
    public function getRechargtionList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $model = new Model();
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['b.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['b.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        $where['card.cardkind'] = array('in', '6882,2081');
        session('rechargtionexcel', $where);
        $field = 'b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,b.flag,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,
        p.hysx,c.customid,c.namechinese cname,card.Cardkind,card.status,u.username,p1.namechinese pname1';

        $results = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->field($field)->where($where)->order('amount desc')->page($page, $pageSize)->select();

        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }
        $amount_sum = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->getField('sum(b.amount) amounts');
        $total = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->count();
        $result = array();
        $footer[] = array('activation' => '充值金额', 'amount' => $amount_sum);
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店充值排行报表导出Excel
     */
    public function rechargtion_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $smap = session('rechargtionexcel');
        foreach ($smap as $key => $val) {
            $where[$key] = $val;
        }
        $field = 'b.purchaseid,b.Card_PurchaseId cpurchaseid,b.cardno,b.description,b.flag,';
        $field .= 'b.amount amount,b.placeddate,b.placedtime,b.panterid,b.userid,p.namechinese pname,
        p.hysx,c.customid,c.namechinese cname,card.Cardkind,card.status,u.username,p1.namechinese pname1';

        $results = $model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join(' __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->field($field)->where($where)->order('amount desc')->select();

        foreach ($results as $k => $v) {
            $v['activation'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:I3";
        $titleName = '酒店充值排行报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'I4';
        $headerArray = array('会员编号', '会员名称', '卡号',
            '发卡机构编号', '发卡机构名称', '充值时间',
            '充值金额', '操作员', '充值类型'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        $setWidth = array(15, 15, 25, 15, 50, 25, 15, 15, 15, 50);

        $this->setWidth($setCells, $setWidth);
        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, $val['cname'])->setCellValue('C' . $j, "'" . $val['cardno'])
                ->setCellValue('D' . $j, "'" . $val['panterid'])->setCellValue('E' . $j, $val['pname'])->setCellValue('F' . $j, $val['activation'])
                ->setCellValue('G' . $j, $val['amount'])->setCellValue('H' . $j, $val['username'])->setCellValue('I' . $j, $val['description']);
            $j++;
        }
        $sum_czamount = array_sum(array_column($rows, amount));
        $objSheet->setCellValue('F' . $j, '充值金额合计:');
        $objSheet->setCellValue('G' . $j, $sum_czamount);
        $objSheet->getStyle('H' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店充值排行报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //消费排行页面
    public function consumption()
    {
        $this->display();
    }

    //消费排行数据
    public function getConsumptionList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $model = new Model();
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['t.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['t.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['t.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        $where['t.flag'] = 0;
        $where['t.tradetype'] = array('in', '00,02,13,17,21');
        //$where['p.hysx']='酒店';
        $where['card.cardkind'] = array('in', '6882,2081');
        session('consumptionexcel', $where);
        $field = 't.cardno,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
        $field .= 't.flag,t.addpoint,p.namechinese pname,p.hysx,custom.namechinese cuname,custom.customid,p1.namechinese pname1';
        $tradetype = array('00' => '至尊卡消费', '02' => '消费撤销', '04' => '劵消费', '08' => '券消费冲正', '13' => '现金消费', '14' => '积分充值', '17' => '预授权',
            '18' => '预授权冲正', '19' => '预授权撤销', '21' => '预授权完成', '22' => '预授权完成冲正', '24' => '预授权完成撤销');
        // $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        $results = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->field($field)->where($where)->page($page, $pageSize)
            ->order("t.tradeamount desc")->select();
        foreach ($results as $k => $v) {
            $v['tradetype'] = $tradetype[$v['tradetype']];
            $rows[] = $v;
        }
        //正常交易表--商户表--卡号表--会员编号对应表--会员信息表--商户表
        $total = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->count();
        $amount_sum = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->where($where)->getField('sum(t.tradeamount) tradeamounts');
        $footer[] = array('pname' => '消费金额总计', 'tradeamount' => $amount_sum);
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }

    /**
     * 酒店消费排行报表导出Excel
     */
    public function consumption_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $smap = session('consumptionexcel');
        foreach ($smap as $key => $val) {
            $where[$key] = $val;
        }
        $tradetype = array('00' => '至尊卡消费', '02' => '消费撤销', '04' => '劵消费', '08' => '券消费冲正', '13' => '现金消费', '14' => '积分充值', '17' => '预授权',
            '18' => '预授权冲正', '19' => '预授权撤销', '21' => '预授权完成', '22' => '预授权完成冲正', '24' => '预授权完成撤销');
        $field = 't.cardno,t.panterid,t.placeddate,t.placedtime,t.tradetype,t.tradeamount,t.tradepoint,';
        $field .= 't.flag,t.addpoint,p.namechinese pname,p.hysx,custom.namechinese cuname,custom.customid,p1.namechinese pname1';
        $results = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno=t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ custom on custom.customid=cc.customid')
            ->join('left join __PANTERS__ p1 on card.panterid=p1.panterid')
            ->field($field)->where($where)
            ->order("t.tradeamount desc")->select();
        foreach ($results as $k => $v) {
            $v['tradetype'] = $tradetype[$v['tradetype']];
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:H3";
        $titleName = '酒店消费排行报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'H4';
        $headerArray = array('会员编号', '会员名称', '卡号',
            '商户编号', '商户名称', '消费金额',
            '交易积分', '交易类型'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $setWidth = array(15, 15, 25, 15, 50, 15, 15, 15, 15);

        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("F" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("G" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, $val['cname'])->setCellValue('C' . $j, "'" . $val['cardno'])
                ->setCellValue('D' . $j, "'" . $val['panterid'])->setCellValue('E' . $j, $val['pname'])->setCellValue('F' . $j, $val['tradeamount'])
                ->setCellValue('G' . $j, $val['tradepoint'])->setCellValue('H' . $j, $val['tradetype']);
            $j++;
        }
        $sum_czamount = array_sum(array_column($rows, tradeamount));
        $objSheet->setCellValue('E' . $j, '消费金额合计:');
        $objSheet->setCellValue('F' . $j, $sum_czamount);
        $objSheet->getStyle('H' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店消费排行报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    public function yuandailyticket()
    {
        $model = new model();
        $start = I('get.startdate', '');
        $panterid = trim(I('get.panterid', ''));
        $pname = trim(I('get.pname', ''));
        $quanid = trim(I('get.quanid', ''));
        $quanname = trim(I('get.quanname', ''));
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pname = $pname == "商户名称" ? "" : $pname;
        $quanid = $quanid == "营销劵编号" ? "" : $quanid;
        $quanname = $quanname == "营销劵名称" ? "" : $quanname;
        if ($start != '') {
            $startdate = str_replace('-', '', $start);
            $where['tw.placeddate'] = array('eq', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '') {
            $startdate = date("Ymd", time());
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $where['tw.placeddate'] = array('eq' . $startdate);
            $map['startdate'] = $start;
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }
        if ($quanid != '') {
            $where['tw.quanid'] = $quanid;
            $this->assign('quanid', $quanid);
            $map['quanid'] = $quanid;
        }
        if ($quanname != '') {
            $where['q.quanname'] = array('like', '%' . $quanname . '%');
            $this->assign('quanname', $quanname);
            $map['quanname'] = $quanname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['panterid'] = $this->panterid;
            $where1['parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $panters = D('panters')->where($where1)->field('panterid')->select();
            $panterId = array();
            foreach ($panters as $key => $val) {
                $panterId[] = $val['panterid'];
            }
            $where['tw.panterid'] = array('in', $panterId);
        } else {
            $this->assign('is_admin', 1);
        }
        $where['tw.tradetype'] = '02';
        //$where['p.hysx']='酒店';
        $field = 'tw.panterid,tw.cardno,p.namechinese pname,tw.placeddate,tw.quanid,q.quanname,';
        $field .= 'count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.panterid,tw.cardno,p.namechinese,tw.placeddate,tw.quanid,q.quanname,q.amount';
        $count = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->count();
        $p = new \Think\Page($count, 10);
        $ticketConsume_list = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->field($field)->limit($p->firstRow . ',' . $p->listRows)
            ->group($group)->order('tw.placeddate desc')->select();
        // echo $model->getLastSql();
        session('ticketConsume_excel', $where);
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('count', $count);
        $this->assign('list', $ticketConsume_list);
        $this->assign('page', $page);
        $this->display();
    }

    //营销劵当日交易统计统计页面
    public function dailyticket()
    {
        $this->display();
    }

    //营销劵当日交易统计数据
    public function getDailyticketList()
    {
        $model = new model();
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $start = I('get.startdate', '');
        $panterid = trim(I('get.panterid', ''));
        $pname = trim(I('get.pname', ''));
        $quanid = trim(I('get.quanid', ''));
        $quanname = trim(I('get.quanname', ''));
        $panterid = $panterid == "商户编号" ? "" : $panterid;
        $pname = $pname == "商户名称" ? "" : $pname;
        $quanid = $quanid == "营销劵编号" ? "" : $quanid;
        $quanname = $quanname == "营销劵名称" ? "" : $quanname;
        if ($start != '') {
            $startdate = str_replace('-', '', $start);
            $where['tw.placeddate'] = array('eq', $startdate);
        }
        if ($start == '') {
            $startdate = date("Ymd", time());
            $where['tw.placeddate'] = array('eq', $startdate);
        }
        if ($panterid != '') {
            $where['tw.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($quanid != '') {
            $where['tw.quanid'] = $quanid;
        }
        if ($quanname != '') {
            $where['q.quanname'] = array('like', '%' . $quanname . '%');
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['panterid'] = $this->panterid;
            $where1['parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $panters = D('panters')->where($where1)->field('panterid')->select();
            $panterId = array();
            foreach ($panters as $key => $val) {
                $panterId[] = $val['panterid'];
            }
            $where['tw.panterid'] = array('in', $panterId);
        } else {
            $this->assign('is_admin', 1);
        }
        $where['tw.tradetype'] = '02';
        session('ticketConsume_excel', $where);
        //$where['p.hysx']='酒店';
        $field = 'tw.panterid,tw.cardno,p.namechinese pname,tw.placeddate,tw.placedtime,tw.quanid,q.quanname,';
        $field .= 'count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.panterid,tw.cardno,p.namechinese,tw.placeddate,tw.placedtime,tw.quanid,tw.tradeamount,q.quanname,q.amount';
        $total = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->count();
        //正常交易表--商户表--营销劵表
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->field($field)->page($page, $pageSize)
            ->group($group)->order('tw.placeddate desc')->select();
        foreach ($results as $k => $v) {
            $v['placeddatime'] = date('Y-m-d', strtotime($v['placeddate'])) . ' ' . $v['placedtime'];
            $v['totalamount'] = $v['amount'] * $v['tradeamount'];
            $v['amount'] = floatval($v['amount']);
            $rows[] = $v;
        }
        $sum_czamount = array_sum(array_column($results, count));
        $sum_consumeamount = array_sum(array_column($results, tradeamount));
        $sum_amount = array_sum(array_column($results, totalamount));
        $footer[] = array('panterid' => '交易笔数合计', 'pname' => $sum_czamount, 'customid' => '交易张数合计', 'placeddatime' => $sum_consumeamount, 'quanid' => '价值总额合计', 'quanname' => $sum_amount);
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);

    }

    /**
     * 营销劵当日交易统计导出EXCEL
     */
    function dailyticket_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        $ticketConsumeMap = session('ticketConsume_excel');
        foreach ($ticketConsumeMap as $key => $value) {
            $where[$key] = $value;
        }
        $field = 'tw.panterid,tw.cardno,p.namechinese pname,tw.placeddate,tw.quanid,q.quanname,';
        $field .= 'count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount,q.amount';
        $group = ' tw.panterid,tw.cardno,p.namechinese,tw.placeddate,tw.quanid,q.quanname,q.amount';
        $results = $model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__QUANKIND__ q on q.quanid=tw.quanid')
            ->where($where)->field($field)->group($group)->order('tw.placeddate desc')->select();
        foreach ($results as $k => $v) {
            $v['placeddatime'] = date('Y-m-d', strtotime($v['placeddate']));
            $v['totalamount'] = $v['amount'] * $v['tradeamount'];
            $rows[] = $v;
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:J3";
        $titleName = '营销劵当日交易统计报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'J4';
        $headerArray = array('商户编号', '商户名称', '交易卡号',
            '交易日期', '营销劵编号', '营销劵名称',
            '交易笔数', '交易张数', '营销劵单价',
            '价值总额'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $setWidth = array(15, 48, 20, 13, 15, 50, 15, 15, 15, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['panterid'])->setCellValue('B' . $j, $val['pname'])->setCellValue('C' . $j, "'" . $val['customid'])
                ->setCellValue('D' . $j, $val['placeddatime'])->setCellValue('E' . $j, "'" . $val['quanid'])->setCellValue('F' . $j, "'" . $val['quanname'])
                ->setCellValue('G' . $j, "'" . $val['count'])->setCellValue('H' . $j, "'" . $val['tradeamount'])->setCellValue('I' . $j, "'" . $val['amount'])
                ->setCellValue('J' . $j, "'" . $val['totalamount']);
            $j++;
        }
        $sum_czamount = array_sum(array_column($results, count));
        $sum_consumeamount = array_sum(array_column($results, tradeamount));
        $sum_amount = array_sum(array_column($results, totalamount));
        $objSheet->setCellValue('D' . $j, '交易笔数合计:');
        $objSheet->setCellValue('E' . $j, $sum_czamount);
        $objSheet->setCellValue('F' . $j, '交易张数合计:');
        $objSheet->setCellValue('G' . $j, $sum_consumeamount);
        $objSheet->setCellValue('H' . $j, '价值总额合计:');
        $objSheet->setCellValue('I' . $j, $sum_amount);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '营销劵当日交易统计报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    // //营销劵过期报表
    // public function returnCoupon(){
    // 	$start = I('get.startdate','');
    //   $end = I('get.enddate','');
    // 	$cardnoc = trim(I('get.cardnoc',''));
    //   $customsid=trim(I('get.customsid',''));
    //   $cuname  = trim(I('get.cuname',''));
    //   $cquanname=trim(I('get.cquanname',''));
    //   $cardnoc = $cardnoc=="卡号"?"":$cardnoc;
    //   $customsid = $customsid=="会员编号"?"":$customsid;
    //   $cuname = $cuname=="会员名称"?"":$cuname;
    // 	$cquanid = $cquanid=="营销产品编号"?"":$cquanid;
    //   $cquanname = $cquanname=="营销产品名称"?"":$cquanname;
    // 	if($start!='' && $end==''){
    // 			$startdate = str_replace('-','',$start);
    // 			$enddate=date("Ymd",time());
    // 			//$where['c.enddate']=array('egt',$startdate);
    // 			$where['c.enddate']=array(array('egt',$startdate),array('elt',$enddate));
    // 			$this->assign('startdate',$start);
    // 			$map['startdate']=$start;
    // 	}
    // 	if($start=='' && $end!=''){
    // 			$enddate = str_replace('-','',$end);
    // 			$where['c.enddate'] = array('elt',$enddate);
    // 			$this->assign('enddate',$end);
    // 			$map['enddate']=$end;
    // 	}
    // 	if($start!='' && $end!=''){
    // 			$startdate = str_replace('-','',$start);
    // 			$enddate = str_replace('-','',$end);
    // 			$where['c.enddate']=array(array('egt',$startdate),array('elt',$enddate));
    // 			$this->assign('startdate',$start);
    // 			$this->assign('enddate',$end);
    // 			$map['startdate']=$start;
    // 			$map['enddate']=$end;
    // 	}
    // 	if($start=='' && $end==''){
    // 			$startdate=date('Ym01',strtotime(date('Ymd')));
    // 			$enddate=date('Ymd',time());
    // 			$where['c.enddate']=array(array('egt',$startdate),array('elt',$enddate));
    // 			$this->assign('startdate',date('Y-m-d',strtotime($startdate)));
    // 			$this->assign('enddate',date('Y-m-d',strtotime($enddate)));
    // 	}
    // 	if($cquanid!=''){
    //       $where['a.quanid']=$cquanid;
    //       $this->assign('cquanid',$cquanid);
    //       $map['cquanid']=$cquanid;
    //   }
    //   if($cardnoc!=''){
    //       $where['d.cardno']=$cardnoc;
    //       $this->assign('cardnoc',$cardnoc);
    //       $map['cardnoc']=$cardnoc;
    //   }
    //   if($customsid!=''){
    //       $where['b.customid']=$customsid;
    //       $this->assign('customsid',$customsid);
    //       $map['customsid']=$customsid;
    //   }
    //   if($cuname!=''){
    //       $where['b.namechinese']=array('like','%'.$cuname.'%');
    //       $this->assign('cuname',$cuname);
    //       $map['cuname']=$cuname;
    //   }
    //   if($cquanname!=''){
    //       $where['c.quanname']=array('like','%'.$cquanname.'%');
    //       $this->assign('cquanname',$cquanname);
    //       $map['cquanname']=$cquanname;
    //   }
    //   if($this->panterid!='FFFFFFFF'){
    //       $where1['p.panterid']=$this->panterid;
    //       $where1['p.parent']=$this->panterid;
    //       $where1['_logic']='OR';
    //       $where['_complex']=$where1;
    //   }
    //   $account=M('account');
    //   $where['a.type']='02';
    //   $where['a.amount']=array('neq',0);
    //   $field='d.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.enddate';
    //   $count=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
    //         ->join('cards d on d.customid=a.customid')
    //         ->join('customs_c m on m.cid=d.customid')
    //         ->join('customs b on b.customid=m.customid')
    //         ->join('__PANTERS__ p on p.panterid=c.panterid')
    //         ->where($where)->field($field)
    //         ->order('c.enddate desc')->count();
    //   $p=new \Think\Page($count, 15);
    //   $account_list=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
    //         ->join('cards d on d.customid=a.customid')
    //         ->join('customs_c m on m.cid=d.customid')
    //         ->join('customs b on b.customid=m.customid')
    //         ->join('__PANTERS__ p on p.panterid=c.panterid')
    //         ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
    //         ->order('c.enddate desc')->select();
    //      // echo $account->getLastSql();
    //   session('returnCouponexcel',$where);
    //   $page = $p->show();
    //   if(!empty($map)){
    //       foreach($map as $key=>$val) {
    //           $p->parameter[$key]= $val;
    //       }
    //   }
    //   $quancz=M('quancz');
    //   $tradeWB=M('trade_wastebooks');
    //   foreach($account_list as $key=>$val){
    //       $map1=array('customid'=>$val['customid1'],'quanid'=>$val['quanid']);
    //       $account_list[$key]['czamount']=intval($quancz->where($map1)->sum('amount'));
    //       $map2=array('cardno'=>$val['cardno'],'tradetype'=>'02','quanid'=>$val['quanid']);
    //       $account_list[$key]['consumeamount']=intval($tradeWB->where($map2)->sum('tradeamount'));
    // 		  $account_list[$key]['enddate']=$this->insertToStr($this->insertToStr($account_list[$key]['enddate'],4,'-'),7,'-');
    //   }
    //   $this->assign('page',$page);
    //   $this->assign('list',$account_list);
    // 	$this->display();
    // }
    //   //营销劵过期报表excle
    //   function returnCoupon_excel(){
    //       set_time_limit(0);
    //       ini_set('memory_limit', '-1');
    //       $model=new Model();
    //       $field='d.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname';
    //       if(isset($_SESSION['returnCouponexcel'])){
    //           $recmap=session('returnCouponexcel');
    //           foreach($recmap as $key=>$val){
    //               $where[$key]=$val;
    //           }
    //       }
    //       $field='d.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname,c.enddate';
    //       $account_list=M('account')->alias('a')->join('quankind c on a.quanid=c.quanid')
    //           ->join('cards d on d.customid=a.customid')
    //           ->join('customs_c m on m.cid=d.customid')
    //           ->join('customs b on b.customid=m.customid')
    //           ->join('__PANTERS__ p on p.panterid=c.panterid')
    //           ->where($where)->field($field)->order('c.enddate desc')->select();
    //       $quancz=M('quancz');
    //       $strlist="卡号,会员名称,营销劵编号,劵名称,过期数量,过期时间";
    //       $strlist=iconv('utf-8','gbk',$strlist);
    //       $strlist.="\n";
    //       foreach ($account_list as $key=>$val) {
    //           $val['namechinese']=iconv('utf-8','gbk',$val['namechinese']);
    // 		      $val['quanname']=iconv('utf-8','gbk',$val['quanname']);
    // 		      $strlist.=$val['cardno']."\t,".$val['namechinese'].",".$val['quanid'];
    //           $strlist.="\t,".$val['quanname'].',';
    //           $strlist.=$val['amount'].",".$val['enddate']."\n";
    //
    //       }
    //       $filename='劵过期报表'.date('YmdHis');
    //       $filename = iconv("utf-8","gbk",$filename);
    //       unset($account_list);
    //       $this->load_csv($strlist,$filename);
    //   }
//营销劵过期报表
    public function yuanreturnCoupon()
    {
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $cardnoc = trim(I('get.cardnoc', ''));
        $customsid = trim(I('get.customsid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cquanname = trim(I('get.cquanname', ''));
        $cquanid = trim(I('get.cquanid', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $cquanid = $cquanid == "营销产品编号" ? "" : $cquanid;
        $cquanname = $cquanname == "营销产品名称" ? "" : $cquanname;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $enddate = date("Ymd", time());
            $where['a.enddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['a.enddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['a.enddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate'] = $end;
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['a.enddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
        }
        if ($cquanid != '') {
            $where['a.quanid'] = $cquanid;
            $this->assign('cquanid', $cquanid);
            $map['cquanid'] = $cquanid;
        }
        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
            $this->assign('cardnoc', $cardnoc);
            $map['cardnoc'] = $cardnoc;
        }
        if ($customsid != '') {
            $where['a.customid'] = $customsid;
            $this->assign('customsid', $customsid);
            $map['customsid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cs.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($cquanname != '') {
            $where['a.quanname'] = array('like', '%' . $cquanname . '%');
            $this->assign('cquanname', $cquanname);
            $map['cquanname'] = $cquanname;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $where['_complex'] = $where1;
        }
        $model = new Model();
        $sql = "(select a.*,b.quanname,b.enddate,b.panterid,b.amount m_money from account a ,quankind b where a.quanid=b.quanid and a.type='02' and a.amount<>0)";
        $filed = "a.*,c.cardno,cs.namechinese";
        $count = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate desc')
            ->count();
        $p = new \Think\Page($count, 10);
        $account_list = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate desc')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        session('returnCouponexcel', $where);
        $page = $p->show();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        foreach ($account_list as $key => $val) {
            $account_list[$key]['enddate'] = $this->insertToStr($this->insertToStr($account_list[$key]['enddate'], 4, '-'), 7, '-');
        }
        $this->assign('page', $page);
        $this->assign('list', $account_list);
        $this->display();
    }

    //营销劵过期报表数据
    public function returnCoupon()
    {
        $this->display();
    }

    //营销劵过期报表数据
    public function getReturnCouponList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $cquanname = trim(I('get.quanname', ''));
        $cquanid = trim(I('get.quanid', ''));
        $linktel = trim(I('get.linktel', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $cquanid = $cquanid == "营销产品编号" ? "" : $cquanid;
        $cquanname = $cquanname == "营销产品名称" ? "" : $cquanname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['a.enddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['a.enddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cquanid != '') {
            $where['a.quanid'] = $cquanid;
        }
        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
        }
        if ($customsid != '') {
            $where['a.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cs.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($cquanname != '') {
            $where['a.quanname'] = array('like', '%' . $cquanname . '%');
        }
        if ($linktel != '') {
            $where['cs.linktel'] = $linktel;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $where['_complex'] = $where1;
        }
        session('returnCouponexcel', $where);

        $model = new Model();
        $sql = "(select a.*,b.quanname,b.enddate,b.panterid,b.amount m_money from account a ,quankind b where a.quanid=b.quanid  and a.type='02' and a.amount<>0)";
        $filed = "a.*,c.cardno,cs.namechinese,cs.linktel";
        $total = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate asc')
            ->count();
        //营销劵--账户表--卡号表--会员编号对应表--会员信息表--商户表
        $results = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate asc')
            ->page($page, $pageSize)
            ->select();
        foreach ($results as $k => $v) {
            $v['enddatime'] = $this->insertToStr($this->insertToStr($v['enddate'], 4, '-'), 7, '-');
            $v['m_money'] = floatval($v['m_money']);
            $v['totalamount'] = $v['amount'] * $v['m_money'];
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //消费过期营销劵
    public function consumeVoucher()
    {
        $model = new Model();
        $model->startTrans();
        $cardno = trim(I('post.cardno', ''));
        $customid = trim(I('post.customid', ''));//会员编号
        $quanid = trim(I('post.quanid', ''));//营销券编号
        $amount = trim(I('post.amount', ''));//营销券过期数量
        //更新account表中金额(或数量)
        $accountid = trim(I('post.accountid', ''));//账户编号
        $Sql = "UPDATE account set amount=0 where accountid='{$accountid}' and type='02'";
        $result = $model->execute($Sql);

        //往正常交易表插入一条记录
        $placeddate = date("Ymd");
        $placedtime = date("H:i:s");
        $panterid = $_SESSION['panterid'];
        $tradeid = substr($cardno, 15, 4) . date('YmdHis', time());
        $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
        $tradeSql .= "values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql .= "'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
        $tradeIf = $model->execute($tradeSql);
        if ($result == true && $tradeIf == true) {
            $model->commit();
            $this->ajaxReturn(array("status" => 1, "msg" => "过期营销券消费成功！"));
        } else {
            $model->rollback();
            $this->ajaxReturn(array("status" => 0, "msg" => "过期营销券消费失败！"));
        }
    }

    //保存延期营销券信息
    public function saveDeferInfo()
    {
        $productid = I('post.productid');
        $defertime = I('post.defertime');
        $enddate = str_replace('-', '', $defertime);
        $where['quanid'] = $productid;
        $data['enddate'] = $enddate;
        $quankind = M("quankind")->where($where)->save($data);
        if ($quankind) {
            $this->ajaxReturn(array("status" => 1, "msg" => "营销券延期成功！"));
        } else {
            $this->ajaxReturn(array("status" => 0, "msg" => "营销券延期失败！"));
        }
    }

    //酒店营销劵过期报表导出
    function returnCoupon_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        if (isset($_SESSION['returnCouponexcel'])) {
            $recmap = session('returnCouponexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $sql = "(select a.*,b.quanname,b.enddate,b.panterid,b.amount m_money from account a ,quankind b where a.quanid=b.quanid and a.type='02' and a.amount<>0)";
        $filed = "a.*,c.cardno,cs.namechinese,cs.linktel";
        $results = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate desc')
            ->select();
        foreach ($results as $k => $v) {
            $v['enddatime'] = $this->insertToStr($this->insertToStr($v['enddate'], 4, '-'), 7, '-');
            $v['totalamount'] = $v['amount'] * $v['m_money'];
            $rows[] = $v;
        }
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '酒店营销劵过期报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'K4';
        $headerArray = array('卡号', '会员编号', '会员名称', '手机号', '营销产品编号',
            '营销产品名称', '账户编号', '过期数量', '营销劵单价', '营销劵过期金额', '过期时间'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
        $setWidth = array(25, 15, 25, 15, 15, 50, 15, 15, 15, 20, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValueExplicit("H" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("I" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValueExplicit("J" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . "'" . $val['customid'])->setCellValue('C' . $j, $val['namechinese'])
                ->setCellValue('D' . $j, "'" . $val['linktel'])->setCellValue('E' . $j, "'" . $val['quanid'])->setCellValue('F' . $j, $val['quanname'])
                ->setCellValue('G' . $j, "'" . $val['accountid'])->setCellValue('H' . $j, $val['amount'])->setCellValue('I' . $j, $val['m_money'])
                ->setCellValue('J' . $j, $val['totalamount'])->setCellValue('K' . $j, $val['enddatime']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店营销劵过期报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //酒店礼品卡过期报表
    public function giftCard()
    {
        $this->display();
    }

    //酒店礼品卡过期报表数据
    public function getGiftCardList()
    {
        $status = array("Y" => "正常卡", "S" => "过期卡");
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $type = I('get.type', '');
        $type = empty($type) ? 1 : $type;
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $linktel = trim(I('get.linktel', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['ca.exdate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['ca.exdate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['ca.exdate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['ca.exdate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cardnoc != '') {
            $where['ca.cardno'] = $cardnoc;
        }
        if ($customsid != '') {
            $where['ca.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cs.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cs.linktel'] = $linktel;
        }
        if ($this->panterid != 'FFFFFFFF') {
            $where1['p.panterid'] = $this->panterid;
            $where1['p.parent'] = $this->panterid;
            $where1['_logic'] = 'OR';
            $where['_complex'] = $where1;
        }
        $where['ca.cardkind'] = array('eq', '2081');
        if ($type == 1) {
            $where['ca.status'] = array('eq', 'S');
        } else {
            $where['ca.status'] = array('eq', 'Y');
        }
        $where['ac.type'] = array('eq', '00');
        session('giftCardexcel', $where);

        $model = new Model();
        $filed = "ca.cardno,ca.customid,ca.exdate,ca.status,ac.accountid,ac.amount,cs.namechinese,cs.linktel,p.panterid";
        $total = $model->table('cards')->alias('ca')
            ->join("account ac on ca.customid=ac.customid")
            ->join('customs_c cs_c on cs_c.cid=ca.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join("panters p on ca.panterid=p.panterid")
            ->field($filed)->where($where)
            ->count();
        //营销劵--账户表--卡号表--会员编号对应表--会员信息表
        $results = $model->table('cards')->alias('ca')
            ->join("account ac on ca.customid=ac.customid")
            ->join('customs_c cs_c on cs_c.cid=ca.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join("panters p on ca.panterid=p.panterid")
            ->field($filed)->where($where)
            ->page($page, $pageSize)
            ->select();
        foreach ($results as $k => $v) {
            $v['enddatime'] = $this->insertToStr($this->insertToStr($v['exdate'], 4, '-'), 7, '-');
            $v['cardstatus'] = $status[$v['status']];
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //消费过期礼品卡
    public function giftConsumeVoucher()
    {
        $str = 0;
        $data = $_POST['cardno'];
        foreach ($data as $k => $v) {
            $model = new Model();
            $model->startTrans();
            $where['ca.cardno'] = $v;
            $where['ac.type'] = '00';
            //根据卡号查询会员编号、过期金额、账户编号
            $filed = "ca.customid,ac.accountid,ac.amount";
            $info = $model->table('cards')->alias('ca')
                ->join("account ac on ca.customid=ac.customid")
                ->field($filed)
                ->where($where)
                ->find();
            $customid = $info['customid'];//会员编号
            $amount = $info['amount'];//礼品卡过期金额
            //更新account表中金额(或数量)
            $accountid = $info['accountid'];//账户编号
            $Sql = "UPDATE account set amount=0 where accountid='{$accountid}'  and type='00'";
            $result = $model->execute($Sql);

            //更新cards表中卡状态
            $cardsql = "UPDATE cards set status='S' where cardno='{$v}'";
            $cardresult = $model->execute($cardsql);

            //往过期消费交易表插入一条记录
            $placeddate = date("Ymd");
            $placedtime = date("H:i:s");
            $panterid = $_SESSION['panterid'];
            $tradeid = substr($v, 15, 4) . date('YmdHis', time());
            $tradeSql = "insert into giftcardtrade (termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
            $tradeSql .= "values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql .= "'{$amount}','0','{$customid}','{$v}','{$placedtime}','00','abcdefgh','0')";
            $tradeIf = $model->execute($tradeSql);

            //往正常交易表插入一条记录
            $tradewSql = "insert into trade_wastebooks (termno,termposno,panterid,tradeid,placeddate,";
            $tradewSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
            $tradewSql .= "values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradewSql .= "'{$amount}','0','{$customid}','{$v}','{$placedtime}','00','abcdefgh','0')";
            $tradewIf = $model->execute($tradewSql);
            if ($result == true && $cardresult == true && $tradeIf == true && $tradewIf == true) {
                $model->commit();
                $str = 1;
            } else {
                $str = 2;
                $model->rollback();
            }
        }
        if ($str == 1) {
            $this->ajaxReturn(array("status" => 1, "msg" => "过期礼品卡消费成功！"));
        } else {
            $this->ajaxReturn(array("status" => 0, "msg" => "过期礼品卡消费失败！"));
        }
    }

    //保存延期礼品卡信息
    public function saveGiftInfo()
    {
        $cardno = I('post.cardno');
        $defertime = I('post.defertime');
        $enddate = str_replace('-', '', $defertime);
        $where['cardno'] = $cardno;
        $data['exdate'] = $enddate;
        $result = M("cards")->where($where)->save($data);
        if ($result) {
            $this->ajaxReturn(array("status" => 1, "msg" => "礼品卡延期成功！"));
        } else {
            $this->ajaxReturn(array("status" => 0, "msg" => "礼品卡延期失败！"));
        }
    }

    //酒店礼品卡过期报表导出
    function giftCard_excel()
    {
        $status = array("Y" => "正常卡", "S" => "过期卡");
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        if (isset($_SESSION['giftCardexcel'])) {
            $recmap = session('giftCardexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $filed = "ca.*,ac.accountid,ac.amount,cs.namechinese,cs.linktel";
        //营销劵--账户表--卡号表--会员编号对应表--会员信息表
        $results = $model->table('cards')->alias('ca')
            ->join("account ac on ca.customid=ac.customid")
            ->join('customs_c cs_c on cs_c.cid=ca.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join("panters p on ca.panterid=p.panterid")
            ->field($filed)->where($where)->order('ca.exdate desc')
            ->select();
        foreach ($results as $k => $v) {
            $v['enddatime'] = $this->insertToStr($this->insertToStr($v['exdate'], 4, '-'), 7, '-');
            $v['cardstatus'] = $status[$v['status']];
            $rows[] = $v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '酒店礼品卡过期报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'G4';
        $headerArray = array('卡号', '卡类型', '会员编号', '会员名称', '手机号', '账户编号', '账户金额', '过期时间'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
//            $objSheet->setCellValueExplicit("H".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
//            $objSheet->setCellValueExplicit("I".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
//            $objSheet->setCellValueExplicit("J".$j, "0123456789.",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . "'" . $val['cardstatus'])->setCellValue('C' . $j, "'" . "'" . $val['customid'])->setCellValue('D' . $j, $val['namechinese'])
                ->setCellValue('E' . $j, "'" . $val['linktel'])->setCellValue('F' . $j, "'" . $val['accountid'])->setCellValue('G' . $j, $val['amount'])
                ->setCellValue('H' . $j, "'" . $val['enddatime']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店礼品卡过期报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //酒店过期消费报表
    public function consumeGiftCard()
    {
        $this->display();
    }

    //酒店过期消费报表数据
    public function getConsumeGiftCardList()
    {
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $start = I('get.startdate', '');
        $end = I('get.enddate', '');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $linktel = trim(I('get.linktel', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        if ($start != '' && $end == '') {
            $startdate = str_replace('-', '', $start);
            $where['gc.placeddate'] = array('egt', $startdate);
        }
        if ($start == '' && $end != '') {
            $enddate = str_replace('-', '', $end);
            $where['gc.placeddate'] = array('elt', $enddate);
        }
        if ($start != '' && $end != '') {
            $startdate = str_replace('-', '', $start);
            $enddate = str_replace('-', '', $end);
            $where['gc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($start == '' && $end == '') {
            $startdate = date('Ym01', strtotime(date('Ymd')));
            $enddate = date('Ymd', time());
            $where['gc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
        }
        if ($cardnoc != '') {
            $where['gc.cardno'] = $cardnoc;
        }
        if ($customsid != '') {
            $where['gc.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['gc.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['gc.linktel'] = $linktel;
        }

        session('consumeGiftCardexcel', $where);

        $model = new Model();
        $filed = "gc.cardno,gc.customid,gc.tradeamount,gc.placeddate,gc.placedtime,cs.namechinese,cs.linktel";
        $total = $model->table('giftcardtrade')->alias('gc')
            ->join("customs cs on cs.customid=gc.customid")
            ->field($filed)->where($where)
            ->count();
        $results = $model->table('giftcardtrade')->alias('gc')
            ->join("customs cs on cs.customid=gc.customid")
            ->field($filed)->where($where)
            ->page($page, $pageSize)
            ->select();
        foreach ($results as $k => $v) {
            $v['time'] = ($this->insertToStr($this->insertToStr($v['placeddate'], 4, '-'), 7, '-')) . ' ' . $v['placedtime'];
            $rows[] = $v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //酒店礼品卡过期报表导出
//    function consumeGiftCard_excel(){
//        set_time_limit(0);
//        ini_set('memory_limit', '-1');
//        $model=new Model();
//        if(isset($_SESSION['consumeGiftCardexcel'])){
//            $recmap=session('consumeGiftCardexcel');
//            foreach($recmap as $key=>$val){
//                $where[$key]=$val;
//            }
//        }
//        $filed="gc.cardno,gc.customid,gc.tradeamount,gc.placeddate,gc.placedtime,cs.namechinese,cs.linktel";
//        //营销劵--账户表--卡号表--会员编号对应表--会员信息表
//        $results=$model->table('giftcardtrade')->alias('gc')
//            ->join("customs cs on cs.customid=gc.customid")
//            ->field($filed)->where($where)
//            ->select();
//        foreach($results as $k=>$v){
//            $v['time']=($this->insertToStr($this->insertToStr($v['placeddate'],4,'-'),7,'-')).' '.$v['placedtime'];
//            $rows[]=$v;
//        }
//        //设置标题
//        $PHPExcel=$this->objPHPExcel;
//        $getActiveSheet=$PHPExcel->getActiveSheet();
//        $PHPExcel->setActiveSheetIndex(0);
//        $getActiveSheet->setCellValue('A1','酒店过期消费报表');
//        //合并单元格
//        $getActiveSheet->mergeCells('A1:F3');
//        //设置单元格字体
//        $getActiveSheet->getStyle('A1')->getFont()->setName('宋体') //字体
//        ->setSize(20) //字体大小
//        ->setBold(true); //字体加粗
//        // 设置垂直居中
//        $getActiveSheet->getStyle('A1')->getFont()->setName('宋体') //字体
//        ->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
//        $getActiveSheet->getStyle('A1')->getFont()->setName('宋体') //字体
//        ->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//        // 设置行高度
//        $getActiveSheet->getDefaultRowDimension()->setRowHeight(20); //设置默认行高
//        $getActiveSheet->getRowDimension('1')->setRowHeight(20);    //第一行行高
//        $getActiveSheet->getRowDimension('2')->setRowHeight(20);    //第二行行高
//        // 字体和样式
//        $getActiveSheet->getDefaultStyle()->getFont()->setSize(12);   //字体大小
//        //设置表头
//        $PHPExcel->setActiveSheetIndex(0)
//            ->setCellValue('A4', '卡号')
//            ->setCellValue('B4', '会员编号')
//            ->setCellValue('C4', '会员名称')
//            ->setCellValue('D4', '手机号')
//            ->setCellValue('E4', '交易金额')
//            ->setCellValue('F4', '消费时间');
//        // 设置宽度
//        $getActiveSheet->getColumnDimension('A')->setWidth(30);
//        $getActiveSheet->getColumnDimension('B')->setWidth(20);
//        $getActiveSheet->getColumnDimension('C')->setWidth(30);
//        $getActiveSheet->getColumnDimension('D')->setWidth(10);
//        $getActiveSheet->getColumnDimension('E')->setWidth(10);
//        $getActiveSheet->getColumnDimension('F')->setWidth(20);
//
//         //内容
//         for ($i = 0, $len = count($rows); $i < $len; $i++) {
//             $getActiveSheet->setCellValueExplicit('A' . ($i + 5), ' '.$rows[$i]['cardno'],\PHPExcel_Cell_DataType::TYPE_NUMERIC);
//             $getActiveSheet->setCellValue('B' . ($i + 5), $rows[$i]['customid']);
//             $getActiveSheet->setCellValue('C' . ($i + 5), $rows[$i]['namechinese']);
//             $getActiveSheet->setCellValue('D' . ($i + 5), $rows[$i]['linktel']);
//             $getActiveSheet->setCellValue('E' . ($i + 5), $rows[$i]['tradeamount']);
//             $getActiveSheet->setCellValue('F' . ($i + 5), $rows[$i]['time']);
//             $getActiveSheet->getStyle('A' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//             $getActiveSheet->getStyle('B' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//             $getActiveSheet->getStyle('C' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//             $getActiveSheet->getStyle('D' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//             $getActiveSheet->getStyle('E' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//             $getActiveSheet->getStyle('F' . ($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
//         }
//
//        ob_end_clean();//清除缓冲区,避免乱码
//        header('Content-Type: application/vnd.ms-excel');
//        header('Content-Disposition: attachment;filename="过期消费报表(' . date('YmdHis') . ').xls"');
//        header('Cache-Control: max-age=0');
//
//        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
//        $objWriter->save('php://output');
//    }
    function consumeGiftCard_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        if (isset($_SESSION['consumeGiftCardexcel'])) {
            $recmap = session('consumeGiftCardexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $filed = "gc.cardno,gc.customid,gc.tradeamount,gc.placeddate,gc.placedtime,cs.namechinese,cs.linktel";
        //营销劵--账户表--卡号表--会员编号对应表--会员信息表
        $results = $model->table('giftcardtrade')->alias('gc')
            ->join("customs cs on cs.customid=gc.customid")
            ->field($filed)->where($where)
            ->select();
        foreach ($results as $k => $v) {
            $v['time'] = ($this->insertToStr($this->insertToStr($v['placeddate'], 4, '-'), 7, '-')) . ' ' . $v['placedtime'];
            $rows[] = $v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '酒店过期消费报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'F4';
        $headerArray = array('卡号', '会员编号', '会员名称', '手机号', '交易金额', '消费时间'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F');
        $setWidth = array(25, 15, 15, 25, 15, 25);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . "'" . $val['customid'])->setCellValue('C' . $j, "'" . "'" . $val['namechinese'])->setCellValue('D' . $j, $val['linktel'])
                ->setCellValue('E' . $j, "'" . $val['tradeamount'])->setCellValue('F' . $j, "'" . $val['time']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店过期消费报表.xls');//输出到浏览器
        $objWriter->save("php://output");

    }

    //营销产品过期查询excle 陈星
    function yuanreturnCoupon_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model = new Model();
        if (isset($_SESSION['returnCouponexcel'])) {
            $recmap = session('returnCouponexcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $sql = "(select a.*,b.quanname,b.enddate,b.panterid,b.amount m_money from account a ,quankind b where a.quanid=b.quanid and a.type='02' and a.amount<>0)";
        $filed = "a.*,c.cardno,cs.namechinese";
        $account_list = $model->table($sql)->alias('a')
            ->join("left join cards c on a.customid=c.customid")
            ->join('customs_c cs_c on cs_c.cid=a.customid')
            ->join("customs cs on cs.customid=cs_c.customid")
            ->join('__PANTERS__ p on p.panterid=a.panterid')
            ->field($filed)->where($where)->order('a.enddate desc')
            ->select();
        $strlist = "卡号,会员名称,营销劵编号,劵名称,过期数量,过期时间";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        $strlist .= "\n";

        foreach ($account_list as $key => $val) {
            $val['namechinese'] = iconv('utf-8', 'gbk', $val['namechinese']);
            $val['quanname'] = iconv('utf-8', 'gbk', $val['quanname']);
            $strlist .= $val['cardno'] . "\t," . $val['namechinese'] . "," . $val['quanid'];
            $strlist .= "\t," . $val['quanname'] . ',';
            $strlist .= $val['amount'] . "," . $val['enddate'] . "\n";
        }
        $filename = '劵过期报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($account_list);
        $this->load_csv($strlist, $filename);
    }

    /**
     * 指定位置插入字符串
     * @param $str  原字符串
     * @param $i    插入位置
     * @param $substr 插入字符串
     * @return string 处理后的字符串
     */
    function insertToStr($str, $i, $substr)
    {
        //指定插入位置前的字符串
        $startstr = "";
        for ($j = 0; $j < $i; $j++) {
            $startstr .= $str[$j];
        }
        //指定插入位置后的字符串
        $laststr = "";
        for ($j = $i; $j < strlen($str); $j++) {
            $laststr .= $str[$j];
        }
        //将插入位置前，要插入的，插入位置后三个字符串拼接起来
        $str = $startstr . $substr . $laststr;
        //返回结果
        return $str;
    }

    //酒店消费明细报表首页
    public function newConsume()
    {
        $parents_lists = array('00000125' => '雅乐轩酒店', '00000126' => '艾美酒店', '00000127' => '福朋酒店', '00000118' => '森林半岛酒店', '00000270' => '铂尔曼酒店');
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '17' => '预授权', '21' => '预授权完成');
        $panterid = $_SESSION['panterid'];
        if ($this->panterid != 'FFFFFFFF' && $this->panterid != '00000013') {
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        $this->assign('tradetype', $jytype);
        $this->assign('parents', $parents_lists);
        $this->assign('panterid', $panterid);
        $this->display();
    }

//酒店消费明细报表列表数据
    public function searchConsume()
    {
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
        $auditstatus = array('0' => '未审核', '1' => '审核通过', '2' => '审核驳回');
        $page = $_POST['page'];
        $pageSize = $_POST['rows'];
        $cardno = trim(I('post.cardno', ''));
        $start = trim(I('post.start', ''));
        if ($start == '') {
            $start = date('Y-m-01', time());
        }
        $end = trim(I('post.end', ''));
        $cuname = trim(I('post.cuname', ''));
        $panterid = trim(I('post.panterid', ''));
        $parents = trim(I('post.parents', ''));
        $pname = trim(I('post.pname', ''));
        $linktel = trim(I('post.linktel', ''));
        $tradetype = trim(I('post.tradetype', ''));
        $start = str_replace('-', '', $start);
        $end = str_replace('-', '', $end);
        $yidi = trim(I('post.yidi', ''));
        $dw = trim(I('post.dw', ''));//酒店外卡消费
        if ($cardno != '') {
            $where['tw.cardno'] = $cardno;
        }
        if ($panterid != '') {
            $where['p1.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p1.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = $cuname;
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($start != '' && $end == '') {
            $where['tw.placeddate'] = array('egt', $start);
        }
        if ($start == '' && $end != '') {
            $where['tw.placeddate'] = array('elt', $end);
        }
        if ($start != '' && $end != '') {
            $where['tw.placeddate'] = array(array('egt', $start), array('elt', $end));
        }
        $first = $pageSize * ($page - 1);
        $model = new model();

        if ($this->panterid != 'FFFFFFFF' && $this->panterid != '') {
            if ($yidi == 1) {
                $where['p1.panterid'] = $this->panterid;
                $where['_string'] = "c.panterid!=tw.panterid and c.panterid!=p.parent";
            } else {
                if ($dw == 1) {
                    $where['c.cardkind'] = array('not in', array('6882', '2081', '6688'));
                    $where['c.panterid'] = array('neq', '00000447');
                    $where['tw.tradepoint'] = 0;
                } else {
                    $where['c.cardkind'] = array('in', array('6882', '2081'));
                }
                $where1['p.panterid'] = $this->panterid;
                $where1['p.parent'] = $this->panterid;
                $where1['_logic'] = 'OR';
                $where['_complex'] = $where1;
            }
        } else {
            if ($yidi == 1) {
                $where['_string'] = "c.panterid!=tw.panterid and c.panterid!=p.parent";
                $where['c.cardkind'] = array('in', array('6882', '2081'));
            } else {
                if ($dw == 1) {
                    $where['c.cardkind'] = array('not in', array('6882', '2081', '6688'));
                    $where['c.panterid'] = array('neq', '00000447');
                    $where['tw.tradepoint'] = 0;
                } else {
                    $where['c.cardkind'] = array('in', array('6882', '2081'));
                }
                if ($parents != '') {
                    $where['p.panterid'] = $parents;
                } else {
                    $where['p.parent'] = '00000013';
                }
            }
        }
//    $where['c.cardkind']=array('in',array('6882','2081'));
        $where['tw.flag'] = 0;
        $where['c.cardno'] = array('notlike','68823710888%');//排除贵宾卡消费信息
        $where['tw.tradetype'] = array('in', array('00', '02', '13', '17', '21'));
        if ($tradetype != '') {
            $where['tw.tradetype'] = $tradetype;
        }
        $field = 'tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,q.amount qprice,';
        $field .= 'tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,q.quanname,ta.auditstatus,ta.remark';
        $count = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
            ->field($field)->where($where)->count();

        $this->assign('count', $count);
        $amount_sum = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
            ->field('sum(tw.tradeamount) amount_sum')
            ->where($where)->find();
        $p = new \Think\Page($count, 10);

        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
            ->field($field)->where($where)->limit($first, $pageSize)->order('tw.placeddate desc,tw.placedtime desc')->select();
        //计算交易金额变更：交易金额由券交易金额 和其他交易金额构成  其中交易记录表中券的交易是数量
        //对余筛选条件判断
        if (is_array($where['tw.tradetype'])) {
            //1券的交易总额:
            $where_quan = $where;
            $where_quan['tw.tradetype'] = '02';
            $consumeQuan = $model->table('trade_wastebooks')->alias('tw')
                ->join('__CARDS__ c on c.cardno=tw.cardno')
                ->join('__PANTERS__ p on p.panterid=tw.panterid')
                ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                ->field('tw.tradeamount,q.amount qprice')->where($where_quan)->select();
            if (true == $consumeQuan) {
                foreach ($consumeQuan as $key => $val) {
                    $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                    $sum_quan = bcadd($sum_quan, $val['zprice'], 2);
                }
            } else {
                $sum_quan = 0;
            }
            //2其他交易金额
            $where_qi = $where;
            $where_qi['tw.tradetype'] = array('in', '00,13,17,21');
            $consumeQi = $model->table('trade_wastebooks')->alias('tw')
                ->join('__CARDS__ c on c.cardno=tw.cardno')
                ->join('__PANTERS__ p on p.panterid=tw.panterid')
                ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                ->field('tw.tradeamount')->where($where_qi)->select();
            if (true == $consumeQi) {
                foreach ($consumeQi as $key => $val) {
                    $sum_qi = bcadd($sum_qi, $val['tradeamount'], 2);
                }
            } else {
                $sum_qi = 0;
            }

            //合计
            $sum = bcadd($sum_qi, $sum_quan, 2);
        } else {
            //单个条件查询
            if ($where['tw.tradetype'] == '02') {
                $consumeQuan = $model->table('trade_wastebooks')->alias('tw')
                    ->join('__CARDS__ c on c.cardno=tw.cardno')
                    ->join('__PANTERS__ p on p.panterid=tw.panterid')
                    ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                    ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                    ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                    ->field('tw.tradeamount,q.amount qprice')->where($where)->select();
                if (true == $consumeQuan) {
                    foreach ($consumeQuan as $key => $val) {
                        $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                        $sum_quan = bcadd($sum_quan, $val['zprice'], 2);
                    }
                } else {
                    $sum_quan = 0;
                }
                $sum = $sum_quan;
            } else {
                $consumeQi = $model->table('trade_wastebooks')->alias('tw')
                    ->join('__CARDS__ c on c.cardno=tw.cardno')
                    ->join('__PANTERS__ p on p.panterid=tw.panterid')
                    ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                    ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                    ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                    ->field('tw.tradeamount')->where($where)->select();
                if (true == $consumeQi) {
                    foreach ($consumeQi as $key => $val) {
                        $sum_qi = bcadd($sum_qi, $val['tradeamount'], 2);
                    }
                } else {
                    $sum_qi = 0;
                }
                $sum = $sum_qi;
            }
        }
        session('consumeSum', $sum);
        if (isset($sum_quan)) {
            session('consumeQuan', $sum_quan);
        } else {
            session('consumeQuan', null);
        }
        session('newConsume', $where);
        if (!empty($interConsume)) {
            foreach ($interConsume as $key => $val) {
                $val['tradetype'] = $jytype[$val['tradetype']];
                $val['tradeamount'] = floatval($val['tradeamount']);
                $val['auditstatus'] = $auditstatus[$val['auditstatus']];
                if (isset($val['qprice'])) {
                    $val['qprice'] = floatval($val['qprice']);
                    $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                }
                $val['tradetime'] = date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
                $val['flag'] = $val['flag'] == '0' ? '交易成功' : $val['flag'];
                $json .= json_encode($val) . ',';
            }
        }
        $sum = "合计消费金额： " . $sum;
        $sum = json_encode($sum);
        $json = substr($json, 0, -1);
        echo '{"total" : ' . $count . ', "rows" : [' . $json . '], "footer" : [{"tradeamount" : ' . $sum . ' }]}';
    }

    //保存修改金额或者劵数量信息
    public function saveDetails()
    {
        $jytype = array('至尊卡消费' => '00', '劵消费' => '02', '现金消费' => '13', '预授权' => '17', '预授权完成' => '21');
        $cardno = I('post.cardno');
        $username = I('post.username');
        $phone = I('post.linktel');
        $namechinese = I('post.namechinese');
        $tradetype = I('post.tradetype');
        if ($tradetype == '劵消费') {
            $quanname = I("post.quanname");
            $quankinds = M("quankind");
            $quanid = $quankinds->where(array("quanname" => $quanname))->getField("quanid");
        } else {
            $quanid = '';
        }
        $placeddate = I('post.placeddate');
        $placedtime = I('post.placedtime');
        $tradeid = I('post.tradeid');
        $ortradeaccount = I('post.originaltradeamount');
        $tradeaccount = I('post.tradeamount');
        $auditid = $this->getFieldNextNumber('auditid');
        $operateperson = session('username');
        $operatedate = date("Y-m-d H:i:s", time());
        $model = new model();
        $sql = "insert into trade_auditing values('{$cardno}','{$username}','{$phone}','{$namechinese}','{$jytype[$tradetype]}','{$placeddate}','{$tradeid}','{$ortradeaccount}','{$tradeaccount}','{$operateperson}','{$operatedate}','0','','','','0','{$auditid}','{$placedtime}','{$quanid}')";

        if ($model->execute($sql)) {
            $this->ajaxReturn(array("status" => 1, "msg" => "修改信息成功!"));
        } else {
            $this->ajaxReturn(array("status" => 0, "msg" => "修改信息失败!"));
        }
    }

    //至尊卡消费撤销
    public function cancelRecords()
    {
        $tradeids = I("post.tradeid");
        $model = new model();
        foreach ($tradeids as $k => $v) {
            $where['tw.tradeid'] = $v;
            $field = 'tw.cardno,tw.tradetype,tw.tradeid,tw.tradeamount,tw.placedtime,tw.placeddate,p.namechinese pname,cu.namechinese cuname,cu.linktel';
            $interConsume = $model->table('trade_wastebooks')->alias('tw')
                ->join('__CARDS__ c on c.cardno=tw.cardno')
                ->join('__PANTERS__ p on p.panterid=tw.panterid')
                ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->field($field)->where($where)->order('tw.placeddate desc')->select();
            foreach ($interConsume as $m => $n) {
                $cardno = $n['cardno'];
                $username = $n['cuname'];
                $phone = $n['linktel'];
                $namechinese = $n['pname'];
                $tradetype = $n['tradetype'];
                $placeddate = $n['placeddate'];
                $placedtime = $n['placedtime'];
                $tradeid = $n['tradeid'];
                $ortradeaccount = $n['tradeamount'];
                $operateperson = session('username');
                $operatedate = date("Y-m-d H:i:s", time());
                $auditid = $this->getFieldNextNumber('auditid');
                $sql = "insert into trade_auditing values('{$cardno}','{$username}','{$phone}','{$namechinese}','{$tradetype}','{$placeddate}','{$tradeid}','{$ortradeaccount}','','{$operateperson}','{$operatedate}','0','','','','1','{$auditid}','{$placedtime}','')";
                if ($model->execute($sql)) {
                    $this->ajaxReturn(array("status" => 1, "msg" => "撤销成功!"));
                } else {
                    $this->ajaxReturn(array("status" => 1, "msg" => "撤销失败!"));
                }
            }
        }
    }

    //通过卡号获取券信息
    function getQuanByCardno()
    {
        $cardno = $_REQUEST['cardno'];
        $map = array('cardno' => $cardno);
        $cards = M('cards');
        $card = $cards->where($map)->field('cardno,status')->select();
        if ($card == false) {
            exit(json_encode(array('status' => '01', 'msg' => '无此卡号')));
        }
        if ($card['status'] == 'N') {
            exit(json_encode(array('status' => '02', 'msg' => '非正常卡号，无法扣款')));
        }
        $map1 = array('c.cardno' => $cardno, 'a.type' => '02');
        $list = $cards->alias('c')->join('account a on a.customid=c.customid')
            ->join('quankind q on q.quanid=a.quanid')->where($map1)
            ->field('a.amount,q.quanid,q.quanname')->select();
        if ($list == false) {
            exit(json_encode(array('status' => '03', 'msg' => '该卡下无劵信息')));
        } else {
            $html = '<option value="-1">请选择扣劵类型</option>';
            foreach ($list as $key => $val) {
                $html .= '<option value="' . $val['quanid'] . '">' . $val['quanname'] . '</option>';
            }
            exit(json_encode(array('status' => '1', 'html' => $html)));
        }
    }

    //通过卡号判断该卡是否是该受理酒店的卡
    public function getCards()
    {
        $panterid = $_SESSION['panterid'];
        $cardno = I("post.cardno");
        $cards = M("cards");
        $panterids = $cards->where(array("cardno" => $cardno))->getField("panterid");
        if ($panterid != $panterids) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '此卡非该酒店的卡！'));
        } else {
            $this->ajaxReturn(array('status' => 1, 'msg' => '正常！'));
        }
    }

    //保存扣款信息
    public function saveCutDetails()
    {
        $parents_lists = array('00000125' => '雅乐轩酒店', '00000126' => '艾美酒店', '00000127' => '福朋酒店', '00000118' => '森林半岛酒店', '00000270' => '铂尔曼酒店');
        $panterid = I('post.panterid');
        $namechinese = $parents_lists[$panterid];
        $tradetype = I('post.tradetype');
        if ($tradetype == '00') {
            $quanid = '';
        } else {
            $quanid = trim(I('post.quanid'));
        }
        $tradeaccount = I("post.amount");
        $cardno = trim(I('post.cardno'));
        $tradetime = I('post.tradedate');
        $arr = explode(' ', $tradetime);
        $placeddate = str_replace("-", "", $arr[0]);
        $placedtime = $arr[1];
        $map = array('cardno' => $cardno);
        $cards = M('cards');
        $account = M('account');
        $model = new model();
        $card = $cards->where($map)->find();
        if ($card == false) {
            $this->ajaxReturn(array('status' => '01', 'msg' => '无此卡号!'));
        }
        if ($card['status'] == 'N') {
            $this->ajaxReturn(array('status' => '02', 'msg' => '非正常卡号，无法扣款!'));
        }
        //$tradetype=1余额消费,$tradetype=2劵消费
        if ($tradetype == 1) {
            $maps = array('customid' => $card['customid'], 'type' => '00');
            $balance = $account->where($maps)->find();
            if ($balance['amount'] < $tradeaccount) {
                $this->ajaxReturn(array('status' => '04', 'msg' => '余额不足!'));
            }
        } elseif ($tradetype == 2) {
            $map1 = array('c.cardno' => $cardno, 'a.type' => '02');
            $list = $cards->alias('c')->join('account a on a.customid=c.customid')
                ->join('quankind q on q.quanid=a.quanid')->where($map1)
                ->field('a.amount,q.quanid,q.quanname')->select();
            if ($list == false) {
                $this->ajaxReturn(array('status' => '03', 'msg' => '该卡下无劵信息!'));
            }
            $mapss = array('customid' => $card['customid'], 'type' => '02');
            $balance = $account->where($mapss)->find();
            if ($balance['amount'] < $tradeaccount) {
                $this->ajaxReturn(array('status' => '06', 'msg' => '余额不足!'));
            }
        }
        //根据卡号查询该卡对应会员信息
        $customs = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->where(array('c.cardno' => $cardno))->field("cu.namechinese,cu.linktel")->find();
        if ($customs) {
            $username = $customs['namechinese'];
            $phone = $customs['linktel'];
        }

        $operateperson = session('username');
        $operatedate = date("Y-m-d H:i:s", time());
        $auditid = $this->getFieldNextNumber('auditid');
        $sql = "insert into trade_auditing values('{$cardno}','{$username}','{$phone}','{$namechinese}','{$tradetype}','{$placeddate}','','{$tradeaccount}','','{$operateperson}','{$operatedate}','0','','','','2','{$auditid}','{$placedtime}','{$quanid}')";
        if ($model->execute($sql)) {
            $this->ajaxReturn(array('status' => '1', 'msg' => '已扣款,待审核!'));
        } else {
            $this->ajaxReturn(array('status' => '0', 'msg' => '扣款失败!'));
        }
    }

    //交易审核首页
    public function transactionAudit()
    {
        $this->display();
    }

    //交易审核列表数据
    public function getTransactionAudit()
    {
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
        $auditstatus = array('0' => '未审核', '1' => '审核通过', '2' => '审核驳回');
        $source = array('0' => '修改金额', '1' => '交易撤销', '2' => '后台扣款');
        $tradeauditing = M("trade_auditing");
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');

        $tradetype = trim(I('get.audittype', '', 'strip_tags'));
        $status = trim(I('get.status', '', 'strip_tags'));

        if ($tradetype != '') {
            $where['tradetype'] = $tradetype;
        }
        if ($status != '') {
            $where['auditstatus'] = $status;
        }
        $data = $tradeauditing->where($where)->order('operatedate desc')->page($page, $pageSize)->select();
        foreach ($data as $k => $v) {
            $v['type'] = $jytype[$v['tradetype']];
            $v['status'] = $auditstatus[$v['auditstatus']];
            $v['tradesource'] = $source[trim($v['source'])];
            $v['originaltradeamounts'] = floatval($v['originaltradeamount']);
            $v['tradeamounts'] = floatval($v['tradeamount']);
            $v["time"] = date("Y-m-d", strtotime($v['tradedate'])) . ' ' . $v['tradetime'];
            $rows[] = $v;
        }
        $total = $tradeauditing->count();
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }

    //交易审核操作
    public function financeAduit()
    {
        $auditid = I("post.auditid");
        $source = I("post.source");
        $model = new Model();
        $model->startTrans();
        $where['auditid'] = array("in", $auditid);
        //查询交易审核表中所有符合条件的数据
        $traderesult = $model->table('trade_auditing')->where($where)->field("cardno,tradeid,tradetype,originaltradeamount,tradeamount,namechinese,quanid,tradedate,tradetime")->select();
        //更新交易审核表(trade_auditing)表审核状态
        $arr = "(";
        foreach ($auditid as $m => $n) {
            $arr .= "'" . $n . "',";
        }
        $arrr = rtrim($arr, ',') . ")";
        $auditperson = session("username");
        $auditdate = date("Y-m-d H:i:s", time());
        $Sql = "UPDATE trade_auditing set auditperson='{$auditperson}',auditdate='{$auditdate}',auditstatus=1 where auditid in ({$arrr})";
        $editresult = $model->execute($Sql);
        //$source包含0,说明是修改交易金额;$source包含1,说明是交易撤销;$source包含2,说明是后台扣款;
        if (in_array('0', $source)) {
            //修改交易金额
            foreach ($traderesult as $k => $v) {
                //判断卡号是否为正常卡
                $card = $model->table('cards')->where(array('cardno' => $v['cardno']))->field('cardno,status,customid')->find();
                //交易类型不同时,更新不同的数据,00--至尊卡消费,02--券消费
                if ($v['tradetype'] == '00') {
                    $map1 = array('customid' => $card['customid'], 'type' => '00');
                    $account = $model->table('account')->where($map1)->find();
                    if ($v['tradeamount'] > ($account['amount'] + $v['originaltradeamount'])) {
                        $this->ajaxReturn(array('status' => '0', 'msg' => '卡余额不足!'));
                    }
                    //更新正常交易表(trade_wastebooks)金额
                    $updaeSql = "UPDATE trade_wastebooks set tradeamount={$v["tradeamount"]} where tradeid='{$v['tradeid']}'";
                    $wastebooks = $model->execute($updaeSql);
                    //更新账户表(account)金额
                    $money = $account['amount'] + $v['originaltradeamount'] - $v['tradeamount'];
                    $updaeaccountSql = "UPDATE account set amount={$money} where accountid='{$account['accountid']}'";
                    $updateaccount = $model->execute($updaeaccountSql);
                } elseif ($v['tradetype'] == '02') {
                    $map1 = array('customid' => $card['customid'], 'type' => '02');
                    $account = $model->table('account')->where($map1)->find();
                    if ($account['amount'] < $v['originaltradeamount']) {
                        $this->ajaxReturn(array('status' => '0', 'msg' => '消费劵数量不足!'));
                    }
                    //更新正常交易表(trade_wastebooks)金额
                    $updaeSql = "UPDATE trade_wastebooks set tradeamount={$v["tradeamount"]} where tradeid='{$v['tradeid']}'";
                    $wastebooks = $model->execute($updaeSql);
                    //更新账户表(account)金额
                    $money = $account['amount'] + $v['originaltradeamount'] - $v['tradeamount'];
                    $updaeaccountSql = "UPDATE account set amount={$money} where accountid='{$account['accountid']}'";
                    $updateaccount = $model->execute($updaeaccountSql);
                }
            }
            if ($editresult == true && $wastebooks == true && $updateaccount == true) {
                $model->commit();
                $this->ajaxReturn(array('status' => '1', 'msg' => '审核通过!'));
            } else {
                $model->rollback();
                $this->ajaxReturn(array('status' => '0', 'msg' => '审核失败!'));
            }
        } elseif (in_array('1', $source)) {
            //交易撤销
            foreach ($traderesult as $k => $v) {
                //查询账户表(account)账户余额
                $serachaccount = $model->table('trade_wastebooks')->alias('tw')->join('cards c on tw.cardno=c.cardno')
                    ->join('account ac on ac.customid=c.customid')->where(array("tw.tradeid" => $v['tradeid'], "ac.type" => "00"))
                    ->field('ac.amount,ac.accountid')->find();
                //更新正常交易表(trade_wastebooks)金额
                $updaeSql = "UPDATE trade_wastebooks set tradeamount=0,flag=3 where tradeid='{$v['tradeid']}'";
                $wastebooks = $model->execute($updaeSql);
                //更新账户表(account)金额
                $money = $serachaccount['amount'] + $v['originaltradeamount'];
                $updaeaccountSql = "UPDATE account set amount={$money} where accountid='{$serachaccount['accountid']}'";
                $updateaccount = $model->execute($updaeaccountSql);
            }
            if ($editresult == true && $wastebooks == true && $updateaccount == true) {
                $model->commit();
                $this->ajaxReturn(array('status' => '1', 'msg' => '审核通过!'));
            } else {
                $model->rollback();
                $this->ajaxReturn(array('status' => '0', 'msg' => '审核失败!'));
            }
        } elseif (in_array('2', $source)) {
            //后台扣款
            foreach ($traderesult as $k => $v) {
                //判断卡号是否为正常卡
                $card = $model->table('cards')->where(array('cardno' => $v['cardno']))->field('cardno,status,customid')->find();
                if ($card['status'] != 'Y') {
                    $this->ajaxReturn(array('status' => '0', 'msg' => '扣款卡号非正常卡!'));
                }
                $panterid = $_SESSION['panterid'];
                $tradeid = substr($v['cardno'], 15, 4) . date('YmdHis', time());
                $auditperson = session("username");
                $auditdate = date("Y-m-d H:i:s", time());
                $Sql = "UPDATE trade_auditing set auditperson='{$auditperson}',auditdate='{$auditdate}',auditstatus=1,tradeid='{$tradeid}' where auditid in ({$arrr})";
                $editresult = $model->execute($Sql);
                //交易类型不同时,更新不同的数据,00--至尊卡消费,02--券消费
                if ($v['tradetype'] == '00') {
                    $map1 = array('customid' => $card['customid'], 'type' => '00');
                    $account = $model->table('account')->where($map1)->find();
                    if ($account['amount'] < $v['originaltradeamount']) {
                        $this->ajaxReturn(array('status' => '0', 'msg' => '至尊卡余额不足!'));
                    }
                    //更新正常交易表(trade_wastebooks)相关数据
                    $placeddate = $v['tradedate'];
                    $placedtime = $v['tradetime'];
                    $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
                    $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
                    $tradeSql .= "values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
                    $tradeSql .= "'{$v["originaltradeamount"]}','0','{$card["customid"]}','{$v["cardno"]}','{$placedtime}','00','abcdefgh','0')";
                    $tradeIf = $model->execute($tradeSql);

                    //更新账户表(account)相关数据
                    $accountSql = "UPDATE ACCOUNT set amount=amount-{$v["originaltradeamount"]} where customid='{$card['customid']}' and type='00'";
                    $accountIf = $model->execute($accountSql);
                } elseif ($v['tradetype'] == '02') {
                    $map1 = array('customid' => $card['customid'], 'type' => '02');
                    $account = $model->table('account')->where($map1)->find();
                    if ($account['amount'] < $v['originaltradeamount']) {
                        $this->ajaxReturn(array('status' => '0', 'msg' => '消费劵数量不足!'));
                    }
                    $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
                    $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
                    $tradeSql .= "values('000001','000001','{$panterid}','{$tradeid}','{$placeddate}',";
                    $tradeSql .= "'{$v["originaltradeamount"]}','0','{$card['customid']}','{$v['cardno']}','{$placedtime}','02','abcdefgh','0','{$v["quanid"]}')";
                    $tradeIf = $model->execute($tradeSql);

                    $accountSql = "UPDATE ACCOUNT set amount=amount-{$v["originaltradeamount"]} where customid='{$card['customid']}' and type='02'and quanid='{$v["quanid"]}'";
                    $accountIf = $model->execute($accountSql);
                } else {
                    $this->ajaxReturn(array('status' => '0', 'msg' => '非法扣款类型!'));
                }
            }
            if ($editresult == true && $tradeIf == true && $accountIf == true) {
                $model->commit();
                $this->ajaxReturn(array('status' => '1', 'msg' => '审核通过!'));
            } else {
                $model->rollback();
                $this->ajaxReturn(array('status' => '0', 'msg' => '审核失败!'));
            }
        }
    }

    //审核驳回,保存审核备注信息
    public function saveRemark()
    {
        $auditid = I("post.auditid");
        $remark = I("post.remark");
        $trade_auditing = M("trade_auditing");
        $data['remark'] = $remark;
        $data['auditperson'] = session('username');
        $data['auditdate'] = date("Y-m-d H:i:s", time());
        $data['auditstatus'] = 2;
        $result = $trade_auditing->where(array("auditid" => $auditid))->save($data);
        if ($result) {
            $this->ajaxReturn(array("stauts" => 1, "msg" => "驳回成功!"));
        } else {
            $this->ajaxReturn(array("stauts" => 0, "msg" => "驳回失败!"));
        }
    }

    public function newConsume_excel()
    {
        $model = new model();
        $this->setTimeMemory();
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '17' => '预授权', '21' => '预授权完成');
        $where = session('newConsume');
        $field = 'tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,q.amount qprice,';
        $field .= 'tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,q.quanname';
        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->order('tw.placeddate desc')->select();
//       $tradeamounts = array_column($interConsume,'tradeamount');
//       foreach ($tradeamounts as $key=>$val){
//           $sum=bcadd($sum,$val,2);
//       }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:Q3";
        $titleName = '酒店消费报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'Q4';
        $headerArray = array('卡号', '会员编号', '会员名称',
            '会员电话', '会员所属机构编号', '会员所属机构名称',
            '交易状态', '交易日期', '交易时间',
            '终端号', '交易金额(券数量)', '交易类型',
            '券名称', '券单价', '券总价', '消费商户名', '交易流水号'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'D', 'E', 'F', 'L', 'J', 'K', 'M', 'N', 'P', 'Q');
        $setWidth = array(21, 13, 19, 39, 12, 22, 12, 24, 20, 39, 24);
        $this->setWidth($setCells, $setWidth);

//       //合并单元格
//       $objSheet->getRowDimension(1)->setRowHeight(10);//设置第二行行高
//       $objSheet->getRowDimension(2)->setRowHeight(10);//设置第二行行高
//       $objSheet->getRowDimension(3)->setRowHeight(10);//设置第三行行高
//       $objPHPExcel->getActiveSheet()->mergeCells('A1:O3');
//       $objSheet->setCellValue('A1','消费报表');
//       $styleArray1 = array(
//           'font' => array(
//               'bold' => true,
//               'size'=>20,
//               'color'=>array(
//                   'rgb' => '7CCD7C',
//               ),
//           ),
//           'alignment' => array(
//               'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
//           ),
//       );
//       $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
//       $objSheet->setCellValue('A1','酒店消费报表');
//       $objSheet->getStyle("A4:O4")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');//
//       $objPHPExcel->getActiveSheet()->getStyle('A4:O4')->getBorders()->getTop()->getColor()->setARGB('E8E8E8');
//       $objPHPExcel->getActiveSheet()->getStyle('O4')->getBorders()->getTop()->getColor()->setARGB('E8E8E8');
//       $objSheet->setCellValue('A4','卡号')->setCellValue('B4','会员编号')->setCellValue('C4','会员名称')
//                ->setCellValue('D4','会员电话')->setCellValue('E4','会员所属机构编号')->setCellValue('F4','会员所属机构名称')
//                ->setCellValue('G4','交易状态')->setCellValue('H4','交易日期')->setCellValue('I4','交易时间')
//                ->setCellValue('J4','终端号')->setCellValue('K4','交易金额')->setCellValue('L4','交易类型')
//                ->setCellValue('M4','券名称')->setCellValue('N4','消费商户名')->setCellValue('O4','交易流水号');
//       $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(21);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(13);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(19);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(39);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(12);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(24);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(39);
//       $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(24);
//       setWidth($setCells,$setWidth);
        $j = 5;
        foreach ($interConsume as $key => $val) {
            $val['flag'] = $val['flag'] == '0' ? '交易成功' : $val['flag'];
            $val['tradeamount'] = floatval($val['tradeamount']);
            if (isset($val['qprice'])) {
                $val['qprice'] = floatval($val['qprice']);
                $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
            }
            $objSheet->setCellValueExplicit("O" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['cuid'])->setCellValue('C' . $j, $val['cuname'])
                ->setCellValue('D' . $j, "'" . $val['linktel'])->setCellValue('E' . $j, "'" . $val['panterid1'])->setCellValue('F' . $j, $val['pname1'])
                ->setCellValue('G' . $j, $val['flag'])->setCellValue('H' . $j, $val['placeddate'])->setCellValue('I' . $j, $val['placedtime'])
                ->setCellValue('J' . $j, $val['termposno'])->setCellValue('K' . $j, $val['tradeamount'])->setCellValue('L' . $j, $jytype[$val['tradetype']])
                ->setCellValue('M' . $j, $val['quanname'])->setCellValue('N' . $j, $val['qprice'])->setCellValue('O' . $j, $val['zprice'])
                ->setCellValue('P' . $j, $val['pname'])->setCellValue('Q' . $j, $val['tradeid']);
            $j++;
        }
        $sum = session('consumeSum');
        $sum_quan = session('consumeQuan');
//       $objSheet->setCellValueExplicit("L".$j, "",\PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $objSheet->setCellValue('J' . $j, '合计消费金额(含券)：')->setCellValue('K' . $j, $sum)->setCellValue('N' . $j, '合计券消费金额：')->setCellValue('O' . $j, $sum_quan);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店消费报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //酒店消费明细报表首页
    public function extremeConsume()
    {
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '17' => '预授权', '21' => '预授权完成');
        $panterid = $_SESSION['panterid'];
        if ($this->panterid != 'FFFFFFFF' && $this->panterid != '00000013') {
            $this->assign('is_admin', 0);
        } else {
            $this->assign('is_admin', 1);
        }
        $this->assign('tradetype', $jytype);
        $this->assign('panterid', $panterid);
        $this->display();
    }

    //酒店至尊卡消费明细报表列表数据
    public function getExtremeConsumeList()
    {
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成');
        $auditstatus = array('0' => '未审核', '1' => '审核通过', '2' => '审核驳回');
        $page = $_POST['page'];
        $pageSize = $_POST['rows'];
        $cardno = trim(I('post.cardno', ''));
        $start = trim(I('post.start', ''));
        if ($start == '') {
            $start = date('Y-m-01', time());
        }
        $end = trim(I('post.end', ''));
        $cuname = trim(I('post.cuname', ''));
        $panterid = trim(I('post.panterid', ''));
        $pname = trim(I('post.pname', ''));
        $linktel = trim(I('post.linktel', ''));
        $tradetype = trim(I('post.tradetype', ''));
        $start = str_replace('-', '', $start);
        $end = str_replace('-', '', $end);
        if ($cardno != '') {
            $where['tw.cardno'] = $cardno;
        }
        if ($panterid != '') {
            $where['p1.panterid'] = $panterid;
        }
        if ($pname != '') {
            $where['p1.namechinese'] = array('like', '%' . $pname . '%');
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = $cuname;
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($start != '' && $end == '') {
            $where['tw.placeddate'] = array('egt', $start);
        }
        if ($start == '' && $end != '') {
            $where['tw.placeddate'] = array('elt', $end);
        }
        if ($start != '' && $end != '') {
            $where['tw.placeddate'] = array(array('egt', $start), array('elt', $end));
        }
        $first = $pageSize * ($page - 1);
        $model = new model();

        $where['c.cardkind'] = array('in', array('6888', '2336'));
        $where['c.panterid'] = array('not in', array('00000447', 'FFFFFFFF'));
        $where['tw.tradepoint'] = 0;
        $where['p.parent'] = array('eq', '00000013');

        $where['tw.flag'] = 0;
        $where['tw.tradetype'] = array('in', array('00', '02', '13', '17', '21'));
        if ($tradetype != '') {
            $where['tw.tradetype'] = $tradetype;
        }
        $field = 'tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,q.amount qprice,';
        $field .= 'tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,q.quanname,ta.auditstatus,ta.remark';
        $count = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
            ->field($field)->where($where)->count();

        $this->assign('count', $count);

        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
            ->field($field)->where($where)->limit($first, $pageSize)->order('tw.placeddate desc,tw.placedtime desc')->select();
        //计算交易金额变更：交易金额由券交易金额 和其他交易金额构成  其中交易记录表中券的交易是数量
        //对余筛选条件判断
        if (is_array($where['tw.tradetype'])) {
            //1券的交易总额:
            $where_quan = $where;
            $where_quan['tw.tradetype'] = '02';
            $consumeQuan = $model->table('trade_wastebooks')->alias('tw')
                ->join('__CARDS__ c on c.cardno=tw.cardno')
                ->join('__PANTERS__ p on p.panterid=tw.panterid')
                ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                ->field('tw.tradeamount,q.amount qprice')->where($where_quan)->select();
            if (true == $consumeQuan) {
                foreach ($consumeQuan as $key => $val) {
                    $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                    $sum_quan = bcadd($sum_quan, $val['zprice'], 2);
                }
            } else {
                $sum_quan = 0;
            }
            //2其他交易金额
            $where_qi = $where;
            $where_qi['tw.tradetype'] = array('in', '00,13,17,21');
            $consumeQi = $model->table('trade_wastebooks')->alias('tw')
                ->join('__CARDS__ c on c.cardno=tw.cardno')
                ->join('__PANTERS__ p on p.panterid=tw.panterid')
                ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                ->field('tw.tradeamount')->where($where_qi)->select();
            if (true == $consumeQi) {
                foreach ($consumeQi as $key => $val) {
                    $sum_qi = bcadd($sum_qi, $val['tradeamount'], 2);
                }
            } else {
                $sum_qi = 0;
            }

            //合计
            $sum = bcadd($sum_qi, $sum_quan, 2);
        } else {
            //单个条件查询
            if ($where['tw.tradetype'] == '02') {
                $consumeQuan = $model->table('trade_wastebooks')->alias('tw')
                    ->join('__CARDS__ c on c.cardno=tw.cardno')
                    ->join('__PANTERS__ p on p.panterid=tw.panterid')
                    ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                    ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                    ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                    ->field('tw.tradeamount,q.amount qprice')->where($where)->select();
                if (true == $consumeQuan) {
                    foreach ($consumeQuan as $key => $val) {
                        $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                        $sum_quan = bcadd($sum_quan, $val['zprice'], 2);
                    }
                } else {
                    $sum_quan = 0;
                }
                $sum = $sum_quan;
            } else {
                $consumeQi = $model->table('trade_wastebooks')->alias('tw')
                    ->join('__CARDS__ c on c.cardno=tw.cardno')
                    ->join('__PANTERS__ p on p.panterid=tw.panterid')
                    ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
                    ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
                    ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
                    ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
                    ->join('left join __TRADE_AUDITING__ ta on tw.tradeid=ta.tradeid')
                    ->field('tw.tradeamount')->where($where)->select();
                if (true == $consumeQi) {
                    foreach ($consumeQi as $key => $val) {
                        $sum_qi = bcadd($sum_qi, $val['tradeamount'], 2);
                    }
                } else {
                    $sum_qi = 0;
                }
                $sum = $sum_qi;
            }
        }
        session('consumeSum', $sum);
        if (isset($sum_quan)) {
            session('consumeQuan', $sum_quan);
        } else {
            session('consumeQuan', null);
        }
        session('newConsume', $where);
        if (!empty($interConsume)) {
            foreach ($interConsume as $key => $val) {
                $val['tradetype'] = $jytype[$val['tradetype']];
                $val['tradeamount'] = floatval($val['tradeamount']);
                $val['auditstatus'] = $auditstatus[$val['auditstatus']];
                if (isset($val['qprice'])) {
                    $val['qprice'] = floatval($val['qprice']);
                    $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
                }
                $val['tradetime'] = date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
                $val['flag'] = $val['flag'] == '0' ? '交易成功' : $val['flag'];
                $json .= json_encode($val) . ',';
            }
        }
        $sum = "合计消费金额： " . $sum;
        $sum = json_encode($sum);
        $json = substr($json, 0, -1);
        echo '{"total" : ' . $count . ', "rows" : [' . $json . '], "footer" : [{"tradeamount" : ' . $sum . ' }]}';
    }

    public function extremeConsume_excel()
    {
        $model = new model();
        $this->setTimeMemory();
        $jytype = array('00' => '至尊卡消费', '02' => '劵消费', '17' => '预授权', '21' => '预授权完成');
        $where = session('newConsume');
        $field = 'tw.cardno,tw.tradetype,tw.flag,tw.tradeid,tw.termposno,tw.tradepoint,tw.addpoint,q.amount qprice,';
        $field .= 'tw.panterid panterid,tw.tradeamount,tw.placedtime,tw.placeddate,c.panterid panterid1,p.namechinese pname,p1.namechinese pname1,cu.namechinese cuname,cu.linktel,cu.customid cuid,q.quanname';
        $interConsume = $model->table('trade_wastebooks')->alias('tw')
            ->join('__CARDS__ c on c.cardno=tw.cardno')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->join('left join __QUANKIND__ q on tw.quanid=q.quanid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p1 on p1.panterid=c.panterid')
            ->field($field)->where($where)->order('tw.placeddate desc')->select();

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:Q3";
        $titleName = '酒店至尊卡消费报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'Q4';
        $headerArray = array('卡号', '会员编号', '会员名称',
            '会员电话', '会员所属机构编号', '会员所属机构名称',
            '交易状态', '交易日期', '交易时间',
            '终端号', '交易金额(券数量)', '交易类型',
            '券名称', '券单价', '券总价', '消费商户名', '交易流水号'
        );
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'D', 'E', 'F', 'L', 'J', 'K', 'M', 'N', 'P', 'Q');
        $setWidth = array(21, 13, 19, 39, 12, 22, 12, 24, 20, 39, 24);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($interConsume as $key => $val) {
            $val['flag'] = $val['flag'] == '0' ? '交易成功' : $val['flag'];
            $val['tradeamount'] = floatval($val['tradeamount']);
            if (isset($val['qprice'])) {
                $val['qprice'] = floatval($val['qprice']);
                $val['zprice'] = bcmul($val['qprice'], $val['tradeamount'], 2);
            }
            $objSheet->setCellValueExplicit("O" . $j, "0123456789.", \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objSheet->setCellValue('A' . $j, "'" . $val['cardno'])->setCellValue('B' . $j, "'" . $val['cuid'])->setCellValue('C' . $j, $val['cuname'])
                ->setCellValue('D' . $j, "'" . $val['linktel'])->setCellValue('E' . $j, "'" . $val['panterid1'])->setCellValue('F' . $j, $val['pname1'])
                ->setCellValue('G' . $j, $val['flag'])->setCellValue('H' . $j, $val['placeddate'])->setCellValue('I' . $j, $val['placedtime'])
                ->setCellValue('J' . $j, $val['termposno'])->setCellValue('K' . $j, $val['tradeamount'])->setCellValue('L' . $j, $jytype[$val['tradetype']])
                ->setCellValue('M' . $j, $val['quanname'])->setCellValue('N' . $j, $val['qprice'])->setCellValue('O' . $j, $val['zprice'])
                ->setCellValue('P' . $j, $val['pname'])->setCellValue('Q' . $j, $val['tradeid']);
            $j++;
        }
        $sum = session('consumeSum');
        $sum_quan = session('consumeQuan');
        $objSheet->setCellValue('J' . $j, '合计消费金额(含券)：')->setCellValue('K' . $j, $sum)->setCellValue('N' . $j, '合计券消费金额：')->setCellValue('O' . $j, $sum_quan);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '酒店至尊卡消费报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }

    //从营销劵表中取出所有营销劵名称
    public function getALLquankinds()
    {
        if ($this->panterid != 'FFFFFFFF') {
            $where['panterid'] = $this->panterid;
        }
        $quankindnames = M('quankind')->field('quanid,quanname')->where($where)->select();
        echo $this->ajaxReturn($quankindnames);
    }
    //贵宾卡折扣明细报表
    public function vipDiscount(){
        $this->display();
    }
    //贵宾卡折扣明细报表列表数据
    public function getVipDiscountList(){
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $cardnoc = trim(I('get.cardno', ''));
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.cuname', ''));
        $linktel = trim(I('get.linktel', ''));
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;

        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
        }else{
            $where['c.cardno']=array('like','68823710888%');
        }
        if ($customsid != '') {
            $where['c.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        $where['c.status']='Y';
        session('vipDiscountExcel', $where);
        $total=M('cards')->alias('c')
            ->join('customs cu on c.customid=cu.customid')
            ->join('left join discount_renewal dr on c.cardno=dr.cardno')
            ->where($where)->count();
        $results=M('cards')->alias('c')
            ->join('customs cu on c.customid=cu.customid')
            ->join('custom_purchase_logs cl on c.customid=cl.customid')
            ->join('left join discount_renewal dr on c.cardno=dr.cardno')
            ->where($where)->field('c.customid,c.vipdate,c.cardno,c.exdate,cu.namechinese,cu.linktel,cl.placeddate,dr.renewaldate')
            ->order('cl.placeddate desc')->page($page, $pageSize)->select();
        foreach($results as $k=>$v){
            $v['exdate']=date('Y-m-d',strtotime($v['exdate']));
            $v['activatedate']=date('Y-m-d',strtotime($v['placeddate']));
            if($v['renewaldate']){
                $v['renewaldate']=date('Y-m-d',strtotime($v['renewaldate']));
            }else{
                $v['renewaldate']=$v['vipdate'];
            }
            $rows[]=$v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }
    //贵宾卡折扣明细报表导出
    public function vipDiscount_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['vipDiscountExcel'])) {
            $recmap = session('vipDiscountExcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results=M('cards')->alias('c')
            ->join('customs cu on c.customid=cu.customid')
            ->join('custom_purchase_logs cl on c.customid=cl.customid')
            ->join('left join discount_renewal dr on c.cardno=dr.cardno')
            ->where($where)->field('c.customid,c.vipdate,c.cardno,c.exdate,cu.namechinese,cu.linktel,cl.placeddate,dr.renewaldate')
            ->order('cl.placeddate desc')->select();
        foreach($results as $k=>$v){
            $v['exdate']=date('Y-m-d',strtotime($v['exdate']));
            $v['activatedate']=date('Y-m-d',strtotime($v['placeddate']));
            if($v['renewaldate']){
                $v['renewaldate']=date('Y-m-d',strtotime($v['renewaldate']));
            }else{
                $v['renewaldate']=$v['vipdate'];
            }
            $rows[]=$v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '贵宾卡折扣明细报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'G4';
        $headerArray = array('会员编号', '会员名称', '会员手机号', '卡号', '卡到期时间', '折扣激活时间', '折扣到期时间');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, "'" . $val['namechinese'])->setCellValue('C' . $j, "'" . $val['linktel'])->setCellValue('D' . $j, "'" .$val['cardno'])
                ->setCellValue('E' . $j, "'" . $val['exdate'])->setCellValue('F' . $j, "'" . $val['activatedate'])->setCellValue('G' . $j, $val['renewaldate']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡折扣明细报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //贵宾卡消费明细报表
    public function vipTrade(){
        $jytype=C('jytype');
        $flag=C('flag');
        $this->assign('jytype',$jytype);
        $this->assign('flag',$flag);
        $this->display();
    }
    //贵宾卡消费明细报表列表数据
    public function getVipTradeList(){
        $jytype=C('jytype');
        $jyflag=C('flag');
        $paytype=array('1'=>'银行卡','2'=>'微信','3'=>'支付宝','4'=>'通宝','5'=>'现金');
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.username', ''));
        $linktel = trim(I('get.linktel', ''));
        $tradeid = trim(I('get.tradeid', ''));
        $cardnoc = trim(I('get.cardno', ''));

        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        $tradeid = $tradeid == "交易流水号" ? "" : $tradeid;
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        if ($customsid != '') {
            $where['cu.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($tradeid != '') {
            $where['v.tradeid'] = $tradeid;
        }
        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
        }
        session('vipTradeExcel', $where);
        $total=M('viptrade')->alias('v')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->count();
        $results=M('viptrade')->alias('v')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('v.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('v.paydate desc,v.paytime desc')->page($page, $pageSize)->select();
        foreach($results as $k=>$v){
            if($v['quanid']){
                $quanname=M('quankind')->where(array('quanid'=>$v['quanid']))->getField('quanname');
                $v['quanname']=$quanname;
            }
            $v['point']=floor($v['finalaccount']/25);
            $v['paydate']=date('Y-m-d',strtotime($v['paydate']));
            $v['discount']=floatval($v['discount']);
            $v['amount']=floatval($v['amount']);
            $v['account']=floatval($v['account']);
            $v['acccount']=floatval($v['acccount']);
            $v['finalaccount']=floatval($v['finalaccount']);
            $v['paytype']=$paytype[$v['paytype']];
            $rows[]=$v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }
    //贵宾卡消费明细报表导出
    public function vipTrade_excel(){
        $jytype=C('jytype');
        $jyflag=C('flag');
        $paytype=array('1'=>'银行卡','2'=>'微信','3'=>'支付宝','4'=>'通宝','5'=>'现金');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['vipTradeExcel'])) {
            $recmap = session('vipTradeExcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results=M('viptrade')->alias('v')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('v.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('v.paydate desc,v.paytime desc')->select();
        foreach($results as $k=>$v){
            if($v['quanid']){
                $quanname=M('quankind')->where(array('quanid'=>$v['quanid']))->getField('quanname');
                $v['quanname']=$quanname;
            }
            $v['point']=floor($v['finalaccount']/25);
            $v['paydate']=date('Y-m-d',strtotime($v['paydate']));
            $v['discount']=floatval($v['discount']);
            $v['amount']=floatval($v['amount']);
            $v['account']=floatval($v['account']);
            $v['acccount']=floatval($v['acccount']);
            $v['finalaccount']=floatval($v['finalaccount']);
            $v['paytype']=$paytype[$v['paytype']];
            $rows[]=$v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '贵宾卡消费明细报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'U4';
        $headerArray = array('终端号','会员编号', '会员名称', '会员手机号', '卡号', '交易流水号', '券ID', '券名称', '消费日期', '消费时间', '总金额', '折扣比例', '折扣金额', '非折扣金额', '券数量', '实际支付金额', '产生积分', '支付方式');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G','H', 'I', 'J', 'K', 'L', 'M', 'N', 'O','P', 'Q', 'R', 'S');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['termposno'])->setCellValue('B' . $j, "'" . $val['customid'])->setCellValue('C' . $j, "'" . $val['namechinese'])->setCellValue('D' . $j, "'" .$val['linktel'])
                ->setCellValue('E' . $j, "'" . $val['cardno'])->setCellValue('F' . $j, "'" . $val['tradeid'])->setCellValue('G' . $j, "'" .$val['quanid'])->setCellValue('H' . $j, $val['quanname'])->setCellValue('I' . $j, $val['paydate'])
                ->setCellValue('J' . $j, "'" . $val['paytime'])->setCellValue('K' . $j, "'" . $val['totalamount'])->setCellValue('L' . $j, "'" .$val['discount'])->setCellValue('M' . $j, "'" .$val['amount'])->setCellValue('N' . $j, "'" .$val['account'])->setCellValue('O' . $j, "'" .$val['acccount'])
                ->setCellValue('P' . $j, "'" .$val['finalaccount'])->setCellValue('Q' . $j, "'" .$val['point'])->setCellValue('R' . $j, $val['paytype']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡消费明细报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //贵宾卡积分充值报表
    public function pointRecharge(){
        $jytype=C('jytype');
        $flag=C('flag');
        $this->assign('jytype',$jytype);
        $this->assign('flag',$flag);
        $this->display();
    }
    //贵宾卡积分充值报表列表数据
    public function getPointRechargeList(){
        $jytype=C('jytype');
        $jyflag=C('flag');
        $paytype=array('1'=>'银行卡','2'=>'微信','3'=>'支付宝','4'=>'通宝','5'=>'现金');
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.username', ''));
        $linktel = trim(I('get.linktel', ''));
        $tradeid = trim(I('get.tradeid', ''));
        $cardnoc = trim(I('get.cardno', ''));
        $tradetype = trim(I('get.tradetype', ''));
        $flag = trim(I('get.flag', ''));

        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        $tradeid = $tradeid == "交易流水号" ? "" : $tradeid;
        $tradetype = $tradetype == "交易类型" ? "" : $tradetype;
        $flag = $flag == "状态" ? "" : $flag;
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        if ($customsid != '') {
            $where['cu.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($tradeid != '') {
            $where['v.tradeid'] = $tradeid;
        }
        if ($tradetype != ''&&$tradetype!='-1') {
            $where['tw.tradetype'] = $tradetype;
        }
        if ($flag != ''&&$flag!='-1') {
            $where['tw.flag'] = $flag;
        }
        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
        }
        $where['v.finalaccount']=array('gt',0);
        session('pointRechargeExcel', $where);
        $total=M('viptrade')->alias('v')
            ->join('trade_wastebooks tw on tw.tradeid=v.tradeid')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->count();
        $amount_sum = M('viptrade')->alias('v')
            ->join('trade_wastebooks tw on tw.tradeid=v.tradeid')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->getField('sum(v.finalaccount) amount_sum');
        $results=M('viptrade')->alias('v')
            ->join('trade_wastebooks tw on tw.tradeid=v.tradeid')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('v.*,tw.tradetype,tw.flag,cu.customid,cu.namechinese,cu.linktel')
            ->order('v.paydate desc,v.paytime desc')->page($page, $pageSize)->select();
        foreach($results as $k=>$v){
            if($v['quanid']){
                $quanname=M('quankind')->where(array('quanid'=>$v['quanid']))->getField('quanname');
                $v['quanname']=$quanname;
            }
            $v['point']=floor($v['finalaccount']/25);
            $v['paydate']=date('Y-m-d',strtotime($v['paydate']));
            $v['discount']=floatval($v['discount']);
            $v['amount']=floatval($v['amount']);
            $v['account']=floatval($v['account']);
            $v['acccount']=floatval($v['acccount']);
            $v['finalaccount']=floatval($v['finalaccount']);
            $v['paytype']=$paytype[$v['paytype']];
            $v['tradetype']=$jytype[$v['tradetype']];
            $v['flag']=$jyflag[$v['flag']];
            $rows[]=$v;
        }
        $amount_sum=floor($amount_sum/25);
        $amount_sum = array('cardno' => '合计充值积分：', 'orderid' => $amount_sum);
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }
    //贵宾卡积分充值报表导出
    public function pointRecharge_excel(){
        $jytype=C('jytype');
        $jyflag=C('flag');
        $paytype=array('1'=>'银行卡','2'=>'微信','3'=>'支付宝','4'=>'通宝','5'=>'现金');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['pointRechargeExcel'])) {
            $recmap = session('pointRechargeExcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results=M('viptrade')->alias('v')
            ->join('trade_wastebooks tw on tw.tradeid=v.tradeid')
            ->join('cards c on c.cardno=v.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('v.*,tw.tradetype,tw.flag,cu.customid,cu.namechinese,cu.linktel')
            ->order('v.paydate desc,v.paytime desc')->select();
        foreach($results as $k=>$v){
            if($v['quanid']){
                $quanname=M('quankind')->where(array('quanid'=>$v['quanid']))->getField('quanname');
                $v['quanname']=$quanname;
            }
            $v['point']=floor($v['finalaccount']/25);
            $v['paydate']=date('Y-m-d',strtotime($v['paydate']));
            $v['discount']=floatval($v['discount']);
            $v['amount']=floatval($v['amount']);
            $v['account']=floatval($v['account']);
            $v['acccount']=floatval($v['acccount']);
            $v['finalaccount']=floatval($v['finalaccount']);
            $v['paytype']=$paytype[$v['paytype']];
            $v['tradetype']=$jytype[$v['tradetype']];
            $v['flag']=$jyflag[$v['flag']];
            $rows[]=$v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '贵宾卡积分充值报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'U4';
        $headerArray = array('终端号','会员编号', '会员名称', '会员手机号', '卡号', '交易流水号', '券ID', '券名称', '消费日期', '消费时间', '总金额', '折扣比例', '折扣金额', '非折扣金额', '券数量', '实际支付金额', '产生积分', '支付方式', '支付类型', '状态');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G','H', 'I', 'J', 'K', 'L', 'M', 'N', 'O','P', 'Q', 'R', 'S', 'T', 'U');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25, 15, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['termposno'])->setCellValue('B' . $j, "'" . $val['customid'])->setCellValue('C' . $j, "'" . $val['namechinese'])->setCellValue('D' . $j, "'" .$val['linktel'])
                ->setCellValue('E' . $j, "'" . $val['cardno'])->setCellValue('F' . $j, "'" . $val['tradeid'])->setCellValue('G' . $j,  "'" .$val['quanid'])->setCellValue('H' . $j, $val['quanname'])->setCellValue('I' . $j, $val['paydate'])
                ->setCellValue('J' . $j, "'" . $val['paytime'])->setCellValue('K' . $j, "'" . $val['totalamount'])->setCellValue('L' . $j, "'" .$val['discount'])->setCellValue('M' . $j, "'" .$val['amount'])->setCellValue('N' . $j, "'" .$val['account'])->setCellValue('O' . $j, "'" .$val['acccount'])
                ->setCellValue('P' . $j, "'" .$val['finalaccount'])->setCellValue('Q' . $j, "'" .$val['point'])->setCellValue('R' . $j, $val['paytype'])->setCellValue('S' . $j, $val['tradetype'])->setCellValue('T' . $j, $val['flag']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡积分充值报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //贵宾卡积分消费报表
    public function pointConsume(){
        $this->display();
    }
    //贵宾卡积分消费报表列表数据
    public function getPointConsumeList(){
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.username', ''));
        $linktel = trim(I('get.linktel', ''));
        $tradeid = trim(I('get.tradeid', ''));
        $cardnoc = trim(I('get.cardno', ''));

        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        $tradeid = $tradeid == "交易流水号" ? "" : $tradeid;
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        if ($customsid != '') {
            $where['cu.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($tradeid != '') {
            $where['p.tradeid'] = $tradeid;
        }
        if ($cardnoc != '') {
            $where['c.cardno'] = $cardnoc;
        }

        session('pointConsumeExcel', $where);
        $total=M('point_exchange')->alias('p')
            ->join('cards c on c.cardno=p.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->count();
        $amount_sum=M('point_exchange')->alias('p')
            ->join('cards c on c.cardno=p.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->getField('sum(p.totalprice) amount_sum');
        $results=M('point_exchange')->alias('p')
            ->join('cards c on c.cardno=p.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('p.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('p.placeddate desc,p.placedtime desc')->page($page, $pageSize)->select();
        foreach($results as $k=>$v){
            $v['price']=floatval($v['price']);
            $v['num']=floatval($v['num']);
            $v['totalprice']=floatval($v['totalprice']);
            $rows[]=$v;
        }
        $amount_sum = array('cardno' => '合计消费积分：', 'tradeid' => $amount_sum);
        $footer[] = $amount_sum;
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $result['footer'] = !empty($footer) ? array_values($footer) : '';
        $this->ajaxReturn($result);
    }
    //贵宾卡积分消费报表导出
    public function pointConsume_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['pointConsumeExcel'])) {
            $recmap = session('pointConsumeExcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results=M('point_exchange')->alias('p')
            ->join('cards c on c.cardno=p.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('p.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('p.placeddate desc,p.placedtime desc')->select();
        foreach($results as $k=>$v){
            $v['price']=floatval($v['price']);
            $v['num']=floatval($v['num']);
            $v['totalprice']=floatval($v['totalprice']);
            $rows[]=$v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '贵宾卡积分消费报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'L4';
        $headerArray = array('会员编号', '会员名称', '会员手机号', '卡号', '交易流水号', '券ID', '券名称', '兑换日期', '兑换时间', '单价', '数量', '总价');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G','H', 'I', 'J', 'K', 'L');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, "'" . $val['namechinese'])->setCellValue('C' . $j, "'" .$val['linktel'])
                ->setCellValue('D' . $j, "'" . $val['cardno'])->setCellValue('E' . $j, "'" . $val['tradeid'])->setCellValue('F' . $j, "'" .$val['quanid'])->setCellValue('G' . $j, "'" .$val['quanname'])->setCellValue('H' . $j,"'" . $val['placeddate'])
                ->setCellValue('I' . $j, "'" . $val['placedtime'])->setCellValue('J' . $j, "'" . $val['price'])->setCellValue('K' . $j, "'" .$val['num'])->setCellValue('L' . $j, "'" .$val['totalprice']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡积分消费报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
    //贵宾卡券转赠报表
    public function quanShare(){
        $usestatus=array('1'=>'未使用','2'=>'已使用');
        $this->assign('usestatus',$usestatus);
        $this->display();
    }
    //贵宾卡券转赠报表列表数据
    public function getQuanShareList(){
        $usestatus=array('1'=>'未使用','2'=>'已使用');
        $page = I('get.page', 1, 'intval');
        $pageSize = I('get.rows', 20, 'intval');
        $customsid = trim(I('get.customid', ''));
        $cuname = trim(I('get.username', ''));
        $linktel = trim(I('get.linktel', ''));
        $tradeid = trim(I('get.tradeid', ''));
        $cardnoc = trim(I('get.cardno', ''));
        $quanid = trim(I('get.quanid', ''));
        $quanname = trim(I('get.quanname', ''));
        $shareid = trim(I('get.shareid', ''));
        $status = trim(I('get.status', ''));

        $customsid = $customsid == "会员编号" ? "" : $customsid;
        $cuname = $cuname == "会员名称" ? "" : $cuname;
        $linktel = $linktel == "手机号" ? "" : $linktel;
        $tradeid = $tradeid == "交易流水号" ? "" : $tradeid;
        $cardnoc = $cardnoc == "卡号" ? "" : $cardnoc;
        $quanid = $quanid == "券ID" ? "" : $quanid;
        $quanname = $quanname == "券名称" ? "" : $quanname;
        $shareid = $shareid == "分享ID" ? "" : $shareid;
        $status = $status == "-1" ? "" : $status;
        if ($customsid != '') {
            $where['cu.customid'] = $customsid;
        }
        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
        }
        if ($linktel != '') {
            $where['cu.linktel'] = $linktel;
        }
        if ($tradeid != '') {
            $where['q.tradeid'] = $tradeid;
        }
        if ($cardnoc != '') {
            $where['q.cardno'] = $cardnoc;
        }
        if ($quanid != '') {
            $where['q.quanid'] = $quanid;
        }
        if ($quanname != '') {
            $where['q.quanname'] = array('like',$quanname);
        }
        if ($shareid != '') {
            $where['q.shareid'] = $shareid;
        }
        if ($status != '') {
            $where['q.status'] = $status;
        }

        session('quanShareExcel', $where);
        $total=M('quanshare')->alias('q')
            ->join('cards c on c.cardno=q.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->count();
        $results=M('quanshare')->alias('q')
            ->join('cards c on c.cardno=q.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('q.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('q.sharedate desc,q.sharetime desc')->page($page, $pageSize)->select();
        foreach($results as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['status']=$usestatus[$v['status']];
            $rows[]=$v;
        }
        $result = array();
        $result['total'] = !empty($total) ? $total : 0;
        $result['rows'] = !empty($rows) ? array_values($rows) : '';
        $this->ajaxReturn($result);
    }
    //贵宾卡券转赠报表导出
    public function quanShare_excel(){
        $usestatus=array('1'=>'未使用','2'=>'已使用');
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['quanShareExcel'])) {
            $recmap = session('quanShareExcel');
            foreach ($recmap as $key => $val) {
                $where[$key] = $val;
            }
        }
        $results=M('quanshare')->alias('q')
            ->join('cards c on c.cardno=q.cardno')
            ->join('customs cu on cu.customid=c.customid')
            ->where($where)->field('q.*,cu.customid,cu.namechinese,cu.linktel')
            ->order('q.sharedate desc,q.sharetime desc')->select();
        foreach($results as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['status']=$usestatus[$v['status']];
            $rows[]=$v;
        }

        $objPHPExcel = $this->objPHPExcel;
        $objSheet = $objPHPExcel->getActiveSheet();
        //设置title
        $cellMerge = "A1:K3";
        $titleName = '贵宾卡券转赠报表';
        $this->setTitle($cellMerge, $titleName);
        //setheader
        $startCell = 'A4';
        $endCell = 'L4';
        $headerArray = array('会员编号', '会员名称', '会员手机号', '卡号', '交易流水号', '分享ID', '券ID', '券名称', '分享日期', '分享时间', '分享数量', '状态');
        $this->setHeader($startCell, $endCell, $headerArray);
        //setWidth
        $setCells = array('A', 'B', 'C', 'D', 'E', 'F', 'G','H', 'I', 'J', 'K', 'L');
        $setWidth = array(25, 15, 15, 25, 15, 15, 50,25, 15, 15, 25, 15);
        $this->setWidth($setCells, $setWidth);

        $j = 5;
        foreach ($rows as $key => $val) {
            $objSheet->setCellValue('A' . $j, "'" . $val['customid'])->setCellValue('B' . $j, "'" . $val['namechinese'])->setCellValue('C' . $j, "'" .$val['linktel'])
                ->setCellValue('D' . $j, "'" . $val['cardno'])->setCellValue('E' . $j, "'" . $val['tradeid'])->setCellValue('F' . $j, "'" .$val['shareid'])
                ->setCellValue('G' . $j, "'" .$val['quanid'])->setCellValue('H' . $j,"'" . $val['quananme'])
                ->setCellValue('I' . $j, "'" . $val['sharedate'])->setCellValue('J' . $j, "'" . $val['sharetime'])->setCellValue('K' . $j, "'" .$val['amount'])->setCellValue('L' . $j, "'" .$val['status']);
            $j++;
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '贵宾卡券转赠报表.xls');//输出到浏览器
        $objWriter->save("php://output");
    }
}
