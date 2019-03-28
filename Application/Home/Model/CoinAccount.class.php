<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 9:22
 */

namespace Home\Model;


use Think\Model;
use Think\Page;

class CoinAccount extends BaseModel
{
         public $tableName = "COIN_ACCOUNT";
         public function getCardSell($where){
             return $this->where($where)->select();
         }

         public function  getCostByPanter($where=null,$page=null)
         {
                 if(is_null($page)){
                     $data = $this->alias("c")->field("sum(c.rechargeamount) sellTotalMoney,c.panterid panterId")->join("panters ps on c.panterid = ps.panterid")->where($where)->group('c.panterid')->select();
                     return    $data;
                 }else{
                     $sql = $this->alias("c")->field("sum(c.rechargeamount) sellTotalMoney,c.panterid panterId")->join("panters ps on c.panterid = ps.panterid")->where($where)->group('c.panterid')->fetchSql()->select();
                     $count = count($this->alias("c")->field("sum(c.rechargeamount) sellTotalMoney,c.panterid panterId")->join("panters ps on c.panterid = ps.panterid")->where($where)->group('c.panterid')->select());
                     $page = new  Page($count,$page);
                     $data = $this->alias("c")->field("sum(c.rechargeamount) sellTotalMoney,c.panterid panterId")->join("panters ps on c.panterid = ps.panterid")->where($where)->group('c.panterid')->limit($page->firstRow.','.$page->listRows)->select();
                     return [$data,$page];
                 }
         }
}

//$sql = "select * FROM (SELECT Pmoney.*,ps.NAMECHINESE,ROWNUM as rcount FROM (SELECT   sum(rechargeamount) sellTotalMoney,panterid panterId FROM coin_account WHERE placeddate >= '20160501' AND placeddate <= '20160531'  GROUP BY panterid) Pmoney
//left join PANTERS ps on Pmoney.PANTERID = ps.PANTERID) WHERE rcount >={$page->firstRow} and rcount <={$page->listRows}";