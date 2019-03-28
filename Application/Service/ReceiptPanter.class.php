<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 14:47
 */

namespace Service;


class ReceiptPanter extends BaseService
{
            public $info;
            public $modelName="ReceiptPanter";
            public function  getReceiptPanterInfo($where=null,$page=null)
            {
                 $this->info = $this->model('ReceiptPanter')->getReceiptPanterInfo($where,$page);
                 return $this->info;
            }
            #更新打印次数
           public function  changePrintNum($where)
           {
               $this->info = $this->model('ReceiptPanter')->where($where)->setInc('receiptnum');
           }

            #更新打印时间
            public function  changePrintDate($where)
            {
                $this->info = $this->model('ReceiptPanter')->where($where)->save(['printdate'=>time()]);
            }

}