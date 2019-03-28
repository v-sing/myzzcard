<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Home\Model\CommercialModel;

class CommercialAccountController extends CommonController{
      private $commercial;

      public function _initialize(){
          parent::_initialize();
          $this->commercial = new CommercialModel;
      }
     //商户账户列表
      public function index(){
          $model = M('panteraccount');
        	$panterid = trim(I('get.panterid',''));
        	$pantername = trim(I('get.pantername',''));
          $where = '1=1';
          if(!empty($pantername)){
              $where1['namechinese'] = $pantername;
              $result = M('panters')->field('panterid')->where($where1)->find();
              $panterid = $result['panterid'];
          }
          empty($panterid) || $where .= " and a.panterid='".$panterid."'";
          $count = $model->alias('a')->where($where)->count();
          $this->assign('count',$count);
          $p = new \Think\Page($count, 10 );
          $panters_list = $model->alias('a')->join('panters p on p.panterid=a.panterid')->where($where)
                                ->limit($p->firstRow.','.$p->listRows)
                                ->order('p.namechinese desc')
                                ->field('a.panterid as panterid ,p.namechinese as pantername,a.type as type,
                                         a.amount as amount,a.waring as waring,a.accountid as accountid,a.status as status')
                                ->select();
          $page = $p->show();
          $this->assign('list',$panters_list);
          $this->assign('page',$page);
  	     	$this->display();
      }
      //导出商户账户信息
      public function exportIn(){
          $model = M('panteraccount');
          $list = $model->alias('a')->join('panters p on p.panterid=a.panterid')
                                ->order('p.namechinese desc')
                                ->field('a.panterid as panterid ,p.namechinese as pantername,a.type as type,
                                         a.amount as amount,a.accountid as accountid,a.status as status')
                                ->select();
          $strlist="商户编号,商户名称,余额,账户类型,账户状态";
          $strlist = iconv("utf-8","gbk",$strlist);
          $strlist.="\n";
          foreach ($list as $key=>$val) {
              $val['pname'] = iconv("utf-8","gbk",$val['pantername']);
              switch($val['type']){
                  case 0:
                      $type = iconv("utf-8","gbk","现金");
                      break;
                  case 1:
                      $type = iconv("utf-8","gbk","备付金");
                      break;
              }
              $status = $val['status'] == 1 ? "启用" : "禁用";
              $status = iconv("utf-8","gbk",$status);
              $strlist.= "'".$val['panterid']."',".$val['pname'].",".floatval($val['amount']);
              $strlist.=",".$type.",".$status.","."\n";
          }
          $filename=$pname.'商户账户备付金'.date('YmdHis');
          $filename = iconv("utf-8","gbk",$filename);
          unset($list);
          $this->load_csv($strlist,$filename);

      }
      //增加商户账户
      public function addaccount(){
        if(IS_POST){
          $panterid = I('post.panterid');
          $accounttype = I('post.accounttype');
          $warning = I('post.warning');
          empty($panterid) && $this->error('商户不能为空或没有此商户！');
          $warning < 0 && $this->error('预警金额不能为负数！');
          $model = M('panteraccount')->where(array('panterid'=>$panterid,'type'=>$accounttype))->find();
          $model && $this->error('已有此账户！');
          $this->commercial->add($panterid,$warning,$accounttype) || $this->error('账户创建失败，请重试！');
          $this->success('添加成功！');
          $this->display();
        }else{
          $this->display();
        }
      }
      //修改商户账户
      public function editaccount(){
        $accountid = trim(I('get.accountid'));
        $model = M('panteraccount')->where(array('accountid'=>$accountid))->find();
        $model['status'] || $this->error('此账户已禁用');
        $pantername = trim(I('get.pantername'));
        $type = trim(I('get.type'));
        $waring = trim(I('get.waring'));
        $this->assign('pantername',$pantername);
        $this->assign('type',$type);
        $this->assign('waring',$waring);
        $this->assign('accountid',$accountid);
        $this->display();
      }
      //执行修改账户预警
      public function doedit(){
        if(IS_POST){
          $accountid = trim(I('post.accountid'));
          $waring = trim(I('post.waring'));
          empty($waring) && $waring=0;
          $sql = "update panteraccount set waring=%d where accountid='{$accountid}'";
          $data = array($waring);
          $re = M('panteraccount')->execute($sql,$data);
          if($re){
            $this->success('修改成功！','index');
          }else{
            $this->error('修改失败！');
          }
        }else{
          $this->error('信息有误!');
        }
      }
    //商户账户充值
     public function recharge(){
       $accountid = I("get.accountid");
       $pantername = I("get.pantername");
       $panterid = I("get.panterid");
       $type = I("get.type");
       $bool =  M('panteraccount')->where(array('panterid'=>$panterid,'type'=>$type,'status'=>1))->find();
       $bool || $this->error('账户已禁用！');
       $this->assign('accountid',$accountid);
       $this->assign('pantername',$pantername);
       $this->assign('panterid',$panterid);
       $this->assign('type',$type);
        $this->display();
     }
     //账户充值执行
     public function dorecharge(){
       if(IS_POST){
         $panterid = I('post.panterid');
         $type = I('post.type');
         $money = I('post.money');
         empty($panterid) && $this->error('请选择商户，或无此商户账户！');
         empty($money) && $this->error('充值金额有误');
         $money < 0    && $this->error('充值金额不能为负');
         $cashdeposit = I('post.cashdeposit','0');
         $warning = I('post.warning','0');

         $bool =  M('panteraccount')->where(array('panterid'=>$panterid,'type'=>$type,'status'=>1))->find();
         $bool || $this->error('没有此账户或账户已禁用！');
         $model = M();
         $model->startTrans();

         if($type == 1){
         $account_sql = "update panteraccount set amount=amount+%d where status=1 and panterid='{$panterid}' and type=1";
         $re1 = $model->execute($account_sql,array($money));
         $data = array('panterid'=>$panterid
                       ,'amount'=>$money
                       ,'cate'=>0
                       ,'accountid'=>$bool['accountid']
                       ,'cashdeposit'=>$cashdeposit
                       ,'userid'=>$this->userid
	                   ,'before_balance'=>$bool['amount']
	                   ,'after_balance' =>bcadd($bool['amount'],$money,2)
         );
         $re2 =  $this->commercial->operate_record($data);
         if($re1==true&&$re2==true){
           $model->commit();
           $this->success('充值成功！',U("index"));
         }else{
           $model->rollback();
           $this->error('充值失败！');
         }
       }else{
         $this->error('未知错误！');
       }
       }
     }
   //商户账户操作明细
    public function operatedetail(){
       $model = M('panter_account_operat');
       $panterid = trim(I('get.panterid',''));
       $pantername = trim(I('get.pantername',''));
       $startdate = trim(I('get.startdate',''));
       $enddate   = trim(I('get.enddate',''));
       $cate     =  trim(I('get.cate',''));
       $where = '1=1';
       if($cate != ''){
         $where .= " and a.cate={$cate} ";
       }
       if(!empty($startdate) && !empty($enddate)){
         $startdate = str_replace('-','',$startdate);
         $enddate   = str_replace('-','',$enddate);
         $where .= " and (a.placeddate between {$startdate} and {$enddate})";
       }
       $list = array();
       if(!empty($pantername)){
           $where1['namechinese'] = $pantername;
           $result = M('panters')->field('panterid')->where($where1)->find();
           $panterid = $result['panterid'];
       }
       empty($panterid) || $where .= " and a.panterid='".$panterid."'";
       $count = $model->alias('a')->where($where)->count();
       $this->assign('count',$count);
       $p = new \Think\Page($count, 10 );
       $data = $model->alias('a')
                     ->join('panteraccount b on a.accountid=b.accountid')
                     ->join('panters c on c.panterid=b.panterid')
                     ->join('users u on a.userid=u.userid')
                     ->field('c.namechinese as pantername,b.panterid as panterid,
                              b.type as type,a.amount as amount,a.cate as cate,
                              a.before_balance,a.after_balance,
                              u.username as userid,b.status as status,a.cashdeposit as cashdeposit,
                              a.placeddate as placeddate,a.placedtime as placedtime')
                     ->order('a.panterid')
                     ->limit($p->firstRow.','.$p->listRows)
                     ->where($where)
                     ->select();
       foreach ($data as $k => $v) {
             $list[$k]  =  $v;
             $list[$k]['placeddate'] = date('Y-m-d H:s:i',strtotime($v['placeddate'].$v['placedtime']));

       }
        $page = $p->show();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }
    //商户账户操作明细表导出
    public function exportOp(){
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        $excel = new \PHPExcel();
        $model = M('panter_account_operat');
        $data = array();
        $list = $model->alias('a')
                      ->join('panteraccount b on a.accountid=b.accountid')
                      ->join('panters c on c.panterid=b.panterid')
                      ->field('c.namechinese as pantername,b.panterid as panterid,
                               b.type as type,a.amount as amount,a.cate as cate,
                               a.userid as userid,b.status as status,
                               a.placeddate as placeddate,a.placedtime as placedtime')
                      ->order('a.panterid')
                      ->select();
        $letter = array('A','B','C','D','E','F','G','H','I','J');
        $tableheader = array('商户名','商户编号','账户类别','账户变动金额','用途','操作员','账户状态','操作日期');
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
                           }
          foreach ($list as $k => $v) {
                $data[$k]['pantername'] = $v['pantername'];
                $data[$k]['panterid']   = $v['panterid'];
                $data[$k]['type'] = $v['type'] == 0?'现金' : '备付金';
                $data[$k]['amount']     = $v['amount'];
                switch ($v['cate']) {
                  case 0:
                      $data[$k]['cate'] = '充值';
                    break;
                  case 1:
                      $data[$k]['cate'] = '提现';
                  case 2:
                      $data[$k]['cate'] = '红包';
                  case 3:
                      $data[$k]['cate'] = '结算';
                }
                $data[$k]['userid']     = $v['userid'];
                $data[$k]['status']     = $v['status'] ? '启用' : '禁用';
                $data[$k]['placeddate'] = date('Y-m-d H:s:i',strtotime($v['placeddate'].$v['placedtime']));

          }
          for ($i = 2;$i <= count($data) + 1;$i++) {
                  $j = 0;
                  foreach ($data[$i - 2] as $key=>$value) {
                  $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
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
              header('Content-Disposition:attachment;filename="operatedetail.xls"');
              header("Content-Transfer-Encoding:binary");
              $write->save('php://output');
        }
    //调取商户名称
    public function getpanter(){
        $keys=$_GET['keys'];
        $panterid = M('panteraccount')->field('panterid')->select();
        $panterid = array_column($panterid,'panterid');
        $where['panterid'] = array('in',$panterid);
        if(!empty($keys)){
            $where['namechinese']=array('like','%'.$keys.'%');
        }
        $panters=M('panters')->where($where)
        ->field('panterid,namechinese pname')->select();
        echo  json_encode($panters);
    }
    //商户状态
    public function statuschange(){
       $accountid = I('post.accountid');
       $model = M('panteraccount')->where(array('accountid'=>$accountid))->find();
       $status = $model['status'] ? 0 : 1;
       M()->execute("update panteraccount set status={$status} where accountid={$accountid}");
       die(json_encode(array('status'=>$status)));
    }
    /* @modifytime:2016年6月4日
     * @content:备付金计算
     * @parameters:string time(Ymd),string issuepname
     * @author:szj*/
    public function provisions(){
      $issuepname  = trim(I('get.issuepname',''));//发行商户名称
      $nowtime = date('Ymd',time());//现在时间
      $subTime = I('get.time',$nowtime);//获取要查询的时间
      $endLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day'));//上个月最后一天
      $startLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 month'));//上个月第一天
      $model=new Model();
      $subCondition = "  where (placeddate<={$endLastMonth}) ";
      if($issuepname!=''){
          $where['p.namechinese']=array('like','%'.$issuepname.'%');
          $this->assign('issuepname',$issuepname);
          $map['issuepname']=$issuepname;
      }
      if($this->panterid!='FFFFFFFF'){
          $subCondition.=empty($subCondition)?" and panterid='{$this->panterid}'":$subCondition." and panterid='{$this->panterid}'";
      }

      $subQuery="(SELECT sum(rechargeamount) totalamount,panterid from coin_account";
      $subQuery.=$subCondition." group by panterid)";
      $count=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
          ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)->count();
      $p=new \Think\Page($count, 15);
      $issueList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
          ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
          ->limit($p->firstRow.','.$p->listRows)->select();
      session('provisions',array('subCondition'=>$subCondition,'where'=>$where,'subTime'=>$subTime));
      foreach($issueList as $key=>$val){
          $totalamount = !empty($val['totalamount']) ? $val['totalamount'] : 0;//截止上月末总计发行通宝
          $where1['ca.panterid']=$val['panterid'];
          $where1['cc.placeddate']=array('elt',$endLastMonth);
          $field='sum(amount) consumeamount';
          $consume=$model->table('coin_account')->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
              ->where($where1)->field($field)->find();
          $consumeamount = !empty($consume['consumeamount']) ? $consume['consumeamount'] : 0;//截止上月末总计消费通宝

          $lnconsume=$model->table('coin_account')
                         ->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
                         ->where("ca.panterid={$val['panterid']} and (cc.placeddate between {$startLastMonth} and {$endLastMonth})")
                         ->field($field)->find();
          $lnconsumeamount = !empty($lnconsume['consumeamount']) ? $lnconsume['consumeamount'] : 0;//上月总计消费通宝

          /* $consumeCheck = $model->table('coin_calculate')
                              ->where("issuepanterid={$val['panterid']} and (placeddate between {$startLastMonth} and {$endLastMonth})")
                              ->field("sum(amount) amount")->find();
          $consumeCheck = !empty($consumeCheck['amount']) ? $consumeCheck['amount'] : 0;//上个月结算通宝数量 */

          $provisions = $model->table('panteraccount')->where("(panterid = {$val['panterid']} and type=1)")->field('amount')->find();//系统截止目前备付金余额
          $provisions = !empty($provisions['amount']) ? $provisions['amount'] : 0;//系统截止目前备付金余额

          $inProvisions = $model->table('panter_account_operat')
                                ->where("panterid = {$val['panterid']} and cate=0 and (placeddate={$nowtime})")
                                ->field('sum(amount) amount')->find();
          $inProvisions = !empty($inProvisions['amount']) ? $inProvisions['amount'] : 0;//本月备付金入账

          $outProvisions = $model->table('panter_account_operat')
                                 ->where("panterid = {$val['panterid']} and cate=3 and (placeddate>={$nowtime})")
                                 ->field('sum(amount) amount')->find();
          $outProvisions = !empty($outProvisions['amount']) ? $outProvisions['amount'] : 0;//本月备付金出账

          $lmProvisions = bcsub(bcadd($provisions,$outProvisions,2),$inProvisions,2);//截止上月末剩余备付金余额
          $payable = bcsub(bcadd(bcmul(bcsub($totalamount,$consumeamount,2),0.2,2),$lnconsumeamount,2),$lmProvisions,2);
          $issueList[$key]['unconsume'] = bcsub($totalamount,$consumeamount,2);//截止上月末累计未兑换通宝
          $issueList[$key]['lmprovisions'] = floatval($lmProvisions);//截止上月末剩余备付金余额
          $issueList[$key]['lnconsume'] = floatval($lnconsumeamount);//上个月兑换通宝数量
          if(empty($payable) || $payable < 0){
              $payable =0;
          }
          $issueList[$key]['payable']    = round($payable);
      }
      if(!empty($map)){
          foreach($map as $key=>$val) {
              $p->parameter[$key]= $val;
          }
      }
      $page = $p->show();
      $this->assign('page',$page);
      $this->assign('list',$issueList);
      $this->display();
    }
    //商户账户备付金导出报表
    public function provisions_excel(){
        $issueconsumeCon=$_SESSION['provisions'];

        $nowtime = date('Ymd',time());//现在时间
        $subTime = !empty($_SESSION['provisions']['subTime']) ? $_SESSION['provisions']['subTime'] : $nowtime;//获取要查询的时间
        $endLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day'));//上个月最后一天
        $startLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 month'));//上个月第一天

        if(isset($issueconsumeCon['where'])){
            $where=$issueconsumeCon['where'];
        }
        $subCondition=$issueconsumeCon['subCondition'];
        $subQuery="(SELECT sum(rechargeamount) totalamount,panterid from coin_account";
        $subQuery.=$subCondition." group by panterid)";
        $model=new Model();
        $issueList=$model->table($subQuery)->alias('a')->join('panters p on p.panterid=a.panterid')
                         ->field('a.totalamount,p.namechinese pname,p.panterid')->where($where)
                         ->select();
        foreach($issueList as $key=>$val){
          $totalamount = !empty($val['totalamount']) ? $val['totalamount'] : 0;//截止上月末总计发行通宝
          $where1['ca.panterid']=$val['panterid'];
          $where1['cc.placeddate']=array('elt',$endLastMonth);
          $field='sum(amount) consumeamount';
          $consume=$model->table('coin_account')->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
              ->where($where1)->field($field)->find();
          $consumeamount = !empty($consume['consumeamount']) ? $consume['consumeamount'] : 0;//截止上月末总计消费通宝
          $lnconsume=$model->table('coin_account')
                         ->alias('ca')->join('coin_consume cc on cc.coinid=ca.coinid')
                         ->where("ca.panterid={$val['panterid']} and (cc.placeddate between {$startLastMonth} and {$endLastMonth})")
                         ->field($field)->find();
          $lnconsumeamount = !empty($lnconsume['consumeamount']) ? $lnconsume['consumeamount'] : 0;//上月总计消费通宝

          /* $consumeCheck = $model->table('coin_calculate')
                              ->where("issuepanterid={$val['panterid']} and (placeddate between {$startLastMonth} and {$endLastMonth})")
                              ->field("sum(amount) amount")->find();
          $consumeCheck = !empty($consumeCheck['amount']) ? $consumeCheck['amount'] : 0;//上个月结算通宝数量 */

          $provisions = $model->table('panteraccount')->where("(panterid = {$val['panterid']} and type=1)")->field('amount')->find();//系统截止目前备付金余额
          $provisions = !empty($provisions['amount']) ? $provisions['amount'] : 0;//系统截止目前备付金余额

          $inProvisions = $model->table('panter_account_operat')
                                ->where("panterid = {$val['panterid']} and cate=0 and (placeddate={$nowtime})")
                                ->field('sum(amount) amount')->find();
          $inProvisions = !empty($inProvisions['amount']) ? $inProvisions['amount'] : 0;//本月备付金入账

          $outProvisions = $model->table('panter_account_operat')
                                 ->where("panterid = {$val['panterid']} and cate=3 and (placeddate>={$nowtime})")
                                 ->field('sum(amount) amount')->find();
          $outProvisions = !empty($outProvisions['amount']) ? $outProvisions['amount'] : 0;//本月备付金出账

          $lmProvisions = bcsub(bcadd($provisions,$outProvisions,2),$inProvisions,2);//截止上月末剩余备付金余额
          $payable = bcsub(bcadd(bcmul(bcsub($totalamount,$consumeamount,2),0.2,2),$lnconsumeamount,2),$lmProvisions,2);
          $issueList[$key]['unconsume'] = bcsub($totalamount,$consumeamount,2);//截止上月末累计未兑换通宝
          $issueList[$key]['lmprovisions'] = $lmProvisions;//截止上月末剩余备付金余额
          $issueList[$key]['lnconsume'] = $lnconsumeamount;//上个月兑换通宝数量
          if(empty($payable) || $payable < 0){
              $payable =0;
          }
          $issueList[$key]['payable']    = round($payable);
        }
        $strlist="赠送机构,未兑换通宝总额(截止上月累计),已兑换金额(上月),备付金余额(截止上月末),需补交备付金";
        $strlist=$this->changeCode($strlist);
        $strlist.="\n";
        foreach ($issueList as $key=>$val) {
            $val['pname'] = iconv("utf-8","gbk",$val['pname']);
            $strlist.=$val['pname'].",".floatval($val['unconsume']).",".floatval($val['lnconsume']);
            $strlist.=",".floatval($val['lmprovisions']).",".floatval($val['payable']).","."\n";
        }
        $filename=$pname.'商户账户备付金'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($list);
        $this->load_csv($strlist,$filename);
    }

    public function batchRecharge(){
        $stringData = "";
        if(IS_POST){
            $panteraccount = M("panteraccount");
            $model = M();
           $upload = $this->_upload("xls");
           $filename=PUBLIC_PATH.$upload['file_stu']['savepath'].$upload['file_stu']['savename'];
           $exceldate=$this->import_excel($filename);
           foreach ($exceldate as $v){

              $panterid = $v[0];
              $pantername = $v[1];
              $amount = $v[2] ? floatval($v[2]) : "";
              if(empty($amount) || $amount<=0){
                  $stringData .="商户号:{$panterid};商户名称:{$pantername}----------状态:充值失败,未获取金额<hr/>";
                  continue;
              }

              $findbool = $panteraccount->where(array("panterid"=>$panterid,"status"=>1,"type"=>1))->find();
               if(!$findbool){
                   $stringData .="商户号:{$panterid};商户名称:{$pantername}----------状态:未查询到此商户相关账户<hr/>";
                   continue;
               }
               $account_sql = "update panteraccount set amount=amount+%d where status=1 and panterid='{$panterid}' and type=1";
               $re1 = $model->execute($account_sql,array($amount));
               $data = array('panterid'=>$panterid
                   ,'amount'=>$amount
                   ,'cate'=>0
                   ,'accountid'=>$findbool['accountid']
                   ,'cashdeposit'=>0
                   ,'userid'=>$this->userid
	               ,'before_balance'=>$findbool['amount']
	               ,'after_balance' =>bcadd($findbool['amount'],$amount,2)
               );
               $re2 =  $this->commercial->operate_record($data);
               if($re1==true&&$re2==true){
                   $stringData .="商户号:{$panterid};商户名称:{$pantername}----------状态:充值成功------金额:{$amount}<hr/>";
               }else{
                   $stringData .="商户号:{$panterid};商户名称:{$pantername}----------状态:充值失败<hr/>";
               }

           }
        }
        if(!empty($stringData)){
            $this->assign("data",$stringData);
        }
         $this->display();
    }

    public function demo(){
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
        $excel = new \PHPExcel();
        $model = M('panteraccount');
        $data = array();
        $list = $model->alias('a')
                    ->join('panters c on c.panterid=a.panterid')
                    ->field('c.namechinese as pantername,a.panterid as panterid')
                    ->order('a.panterid')
                    ->select();
        $letter = array('A','B','C');
        $tableheader = array('商户编号','商户名称','充值金额');
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->getColumnDimension("$letter[$i]")->setAutoSize(true);
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
        }
        foreach ($list as $k => $v) {
            $data[$k]['panterid']   = $v['panterid'];
            $data[$k]['pantername'] = $v['pantername'];
        }
        for ($i = 2;$i <= count($data) + 1;$i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key=>$value) {
                $excel->getActiveSheet()->setCellValueExplicit("$letter[$j]$i","$value");
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
        header('Content-Disposition:attachment;filename="batch.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');

    }
}
