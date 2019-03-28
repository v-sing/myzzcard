<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 14:51
 */

namespace Home\Model;



use Think\Model;
use Think\Page;

class ReceiptPanter extends BaseModel
{
          public $tableName = "RECEIPTPANTER";
         public function  getReceiptPanterInfo($where=null,$page=null)
         {
             if(is_null($page)){
                 $sql = $this->where($where)->fetchSql()->select();
                 $data = $this->where($where)->select();
                 return    $data;
             }else{
                 $count = $this->where($where)->count();
                 $page = new  Page($count,$page);
                 $data = $this->where($where)->limit($page->firstRow.','.$page->listRows)->select();
                 return [$data,$page];
             }
         }

}