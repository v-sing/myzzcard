<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/12
 * Time: 17:31
 */

namespace Home\Model;


use Think\Page;

class PrintReceiptLogs extends BaseModel
{
      public $tableName = "PRINTRECEIPTLOGS";

      public function  getDistinctInfo($where,$page)
      {
          if(is_null($page)){
              $sql = $this->where($where)->fetchSql()->select();
              $data = $this->where($where)->select();
              return    $data;
          }else{
              $count = count($this->table("(SELECT   TO_CHAR(PRINTDATE / (86400) +  
       TO_DATE('1970-01-01', 'YYYY-MM-DD'), 'YYYY-MM-DD') AS PRINTDATE,OPENRECEIPTID,LSORDER  from  PRINTRECEIPTLOGS)")->field("printdate,openreceiptid,max(lsorder) lsorder")->where($where)->group("printdate,openreceiptid")->order("printdate desc")->select());
              $page = new  Page($count,$page);
              $sql = $this->table("(SELECT   TO_CHAR(PRINTDATE / (86400) +  
       TO_DATE('1970-01-01', 'YYYY-MM-DD'), 'YYYY-MM-DD') AS PRINTDATE,OPENRECEIPTID,LSORDER  from  PRINTRECEIPTLOGS)")->field("printdate,openreceiptid,max(lsorder) lsorder")->where($where)->limit($page->firstRow.','.$page->listRows)->group("printdate,openreceiptid")->order("printdate desc")->select();
              $data = $this->table("(SELECT   TO_CHAR(PRINTDATE / (86400) +  
       TO_DATE('1970-01-01', 'YYYY-MM-DD'), 'YYYY-MM-DD') AS PRINTDATE,OPENRECEIPTID,LSORDER  from  PRINTRECEIPTLOGS)")->field("printdate,openreceiptid,max(lsorder) lsorder")->where($where)->limit($page->firstRow.','.$page->listRows)->group("printdate,openreceiptid")->order("printdate desc")->select();
              return [$data,$page];
          }
      }
}