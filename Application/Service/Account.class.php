<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/1
 * Time: 17:07
 */

namespace Service;


class Account extends BaseService
{
            public $info;
            public $cid =null;
            public function __construct($param=null)
            {
                      isset($param['cid'])?$this->cid=$param['cid']:"";
                      isset($param['type'])?$this->type=$param['type']:"";
            }

            public function  getUserInfo($cid=null,$type='01')
            {
                      is_null($cid) && is_null($this->cid) && die;
                      is_null($cid) && $cid = $this->cid;
                      $this->info = $this->model('Account')->getAccountInfo(['customid'=>$cid,'type'=>$type]);
                      return $this->info;
            }




}