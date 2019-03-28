<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 9:41
 */

namespace Home\Model;


use Think\Model;

class Panters extends BaseModel
{
       public $tableName = "PANTERS";
       public function  getPantersInfo($where)
       {
           $sql =    $this->where($where)->fetchSql()->select();
          return   $this->where($where)->select();
       }
}