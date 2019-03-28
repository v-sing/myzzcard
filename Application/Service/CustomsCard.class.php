<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 9:54
 */

namespace Service;


class CustomsCard extends BaseService
{
         public $info;
         public function  getCustomsCInfo($CustomId=null)
         {
               $this->info =   $this->model('CustomsCard')->getCustomsCInfo("customid = $CustomId");
               return $this->info;
         }
}