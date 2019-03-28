<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/12
 * Time: 17:32
 */

namespace Service;


class PrintReceiptLogs extends BaseService
{
        public $modelName="PrintReceiptLogs";
        public function saveData($data)
        {
            $this->model($this->modelName)->save($data);
        }
        public function  getDistinctInfo($where=null,$page=null)
        {

            if (is_null($page)) {
                $this->info = $this->model($this->modelName)->getInfo($where, $page);
                return $this->info;
            } else {
                list($this->info, $this->page) =    $this->model($this->modelName)->getDistinctInfo($where,$page);
                return $this->info;
            }
        }
}