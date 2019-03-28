<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/1
 * Time: 19:12
 */

namespace Home\Model;


use Think\Model;

class Customs extends BaseModel
{
         protected $tableName = "CUSTOMS";
         public function getCustomInfo($where)
         {

             return $this->where($where)->select();

         }
}