<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/1
 * Time: 17:09
 */

namespace Home\Model;


use Think\Model;

class Account extends Model
{
        public $tableName = "ACCOUNT";
        public function getAccountInfo($where)
        {
            $sql =  $this->where($where)->fetchSql()->select();
              return  $this->where($where)->select();
        }
}