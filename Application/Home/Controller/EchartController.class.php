<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class EchartController extends CommonController{
//系统总计通宝赠送与兑换
 public function totalinfo(){
   $panterid = $this->panterid;
   $zsamount = array();
   $dhamount = array();
   $currentyear = date("Y",time());
   $md = array('0101','0201','0301','0401',
               '0501','0601','0701','0801',
               '0901','1001','1101','1201');
   foreach ($md as $k => $v) {
     if($panterid == 'FFFFFFFF'){
       $where = '';
     }else{
       $where = "panterid={$panterid} and";
     }
     if($k == 11){
       $where .= " placeddate>={$currentyear}{$v}";
     }else{
       $where .= " placeddate>={$currentyear}{$v} and placeddate<{$currentyear}{$md[$k+1]}";
    }
      $model = new Model();
      $issueList = $model->table("coin_account")
          ->field('sum(rechargeamount) as zsamount,sum(remindamount) as syamount')->where($where)
          ->find();
      $zsamount[$k] = floatval($issueList['zsamount']) ? floatval($issueList['zsamount']) : 0;
      //$datainfo[$k]['syamount'] = floatval($issueList['syamount']) ? floatval($issueList['syamount']) : 0;
      $dhamount[$k] = floatval($issueList['zsamount'] - $issueList['syamount']);
   }
     $datainfo = array('zsamount'=>$zsamount,'dhamount'=>$dhamount);
    die(json_encode($datainfo));

 }

 //项目通宝赠送排行
  public function zsRanklist(){
    $panterid = $this->panterid;
    $startdate = date("Y",time())."01"."01";
    $enddate = date("Y",time())."12"."31";
    $zsamount_px = array();
    $zsamount = array();
    $pname = array();
    if($panterid == 'FFFFFFFF'){
      $where = '';
    }else{
      $where = "p.panterid={$panterid}";
    }
     $model = new Model();
     $issueList = $model->table("(select sum(rechargeamount) zsamount,
                                  panterid from coin_account
                                  where (placeddate between {$startdate} and {$enddate})
                                  group by panterid)")
                        ->alias('a')
                        ->join('panters p on p.panterid=a.panterid')
                        ->field('a.zsamount as zsamount,p.namechinese as pname')
                        ->where($where)
                        ->select();
     foreach ($issueList as $k => $v) {
        $zsamount_px[$k] = $v['zsamount'];
     }
     array_multisort($zsamount_px,SORT_ASC,$issueList);
     foreach ($issueList as $k => $v) {
       $zsamount[$k] = $v['zsamount'];
       $pname[$k]    = $v['pname'];
     }
     die(json_encode(array('zsamount'=>$zsamount,'pname'=>$pname)));
  }

//项目通宝兑换排行榜
  public function dhRanklist(){
    $panterid = $this->panterid;
    $startdate = date("Y",time())."01"."01";
    $enddate = date("Y",time())."12"."31";
    $dhamount_px = array();
    $dhamount = array();
    $pname = array();
    if($panterid == 'FFFFFFFF'){
      $where = '';
    }else{
      $where = "p.panterid={$panterid}";
    }
    $subQuery="(select sum(amount) totalamount,panterid from coin_consume where (placeddate between {$startdate} and {$enddate})";
    $subQuery.=$subCondition." group by panterid)";
    $model=new Model();
    $consumeList=$model->table($subQuery)
                       ->alias('a')
                       ->join('panters p on p.panterid=a.panterid')
                       ->field('a.totalamount dhamount,p.namechinese pname')
                       ->where($where)
                       ->select();
     foreach ($consumeList as $k => $v) {
        $dhamount_px[$k] = $v['dhamount'];
     }
     array_multisort($dhamount_px,SORT_ASC,$consumeList);
     foreach ($consumeList as $k => $v) {
       $dhamount[$k] = $v['dhamount'];
       $pname[$k]    = $v['pname'];
     }
     die(json_encode(array('dhamount'=>$dhamount,'pname'=>$pname)));

  }


}
