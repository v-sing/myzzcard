<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 10:27
 */

namespace Home\Model;


use Think\Model;

class CustomsCard extends Model
{
    public $tableName="CUSTOMS_C";
    public function  getCustomsCInfo($where)
    {
        return     $this->where($where)->select();
    }
}