<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 9:39
 */

namespace Service;


class Panters extends BaseService
{
    public $panterId;
    public $info;
    public $modelName="Panters";
    public function  __construct($panterId =null)
    {
           !is_null($panterId) && $this->panterId = $panterId;
    }

    public function  getPanterInfo($PanterId=null)
    {
           is_null($PanterId) && is_null($this->panterId) && die;
           is_null($PanterId) && $PanterId = $this->panterId;
           if(is_array($PanterId)){
                   $where['panterid'] = ['in',$PanterId];
                   $this->info =   $this->model('Panters')->getPantersInfo($where);
           }else{
               $where['panterid'] = $PanterId;
               $this->info =   $this->model('Panters')->getPantersInfo($where);
           }
           return $this->info;
    }

}