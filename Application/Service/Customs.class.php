<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/1
 * Time: 18:42
 */

namespace Service;


class Customs extends BaseService
{
    public $customid;
    public $phone;
    public $info;

    public function  getCustomInfo($phone=null)
    {
        is_null($phone) && is_null($this->phone) && die;
        is_null($phone) && $phone = $this->phone;
        $this->info = $this->model('Customs')->getCustomInfo("linktel = '$phone'");
        return $this->info;
    }

    public function  get($name)
    {
         return    $this->__get($name)[0];
    }

}