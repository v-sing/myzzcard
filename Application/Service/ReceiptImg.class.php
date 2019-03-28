<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 15:16
 */

namespace Service;


class ReceiptImg extends  BaseService
{
        public $modelName = "ReceiptImg";


        public function  del($where)
        {
             $this->model($this->modelName)->where($where)->save(["del"=>1]);
        }
}