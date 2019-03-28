<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 15:17
 */

namespace Home\Model;


class ReceiptImg extends  BaseModel
{
     public $tableName = "RECEIPTIMG";
     public function  getSumByOperator($where)
     {
                   $sql = $this->field("SUM(money) tmoney,SUM(actualmoney) tactualmoney,max(receiptcompany) receiptcompany,max(operator) operator")
                               ->group("operator")->where($where)
                                ->fetchSql()->select();
         return   $this->field("SUM(money) tmoney,SUM(actualmoney) tactualmoney,max(receiptcompany) receiptcompany,max(operator) operator")
             ->group("operator")->where($where)
             ->select();
     }
     public function  getCountInvoiceNum($invoice){
          $count =     $this->where("receiptnum = $invoice")->count();
            return $count;
     }

}