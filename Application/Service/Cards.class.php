<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/30
 * Time: 9:39
 */

namespace Service;


class Cards extends BaseService
{

        private  $customId;
        private  $cardIds;
        private  $panterIds;

    /**
     * Cards constructor.
     * @param null $param  Cards 参数
     */
        public function __construct($param=null)
        {
            if(!is_null($param)){
                    $this->cardid = isset($param['cardIds']);
            }
            $this->model = M('cards');
        }

        /**
         * 获取当前卡的所有发行商户ID
         */
        public function getPanters($cardIds = null)
        {
                      if(is_null($cardIds)){
                          if(is_null($this->cardIds)){
                              return false;
                          }
                         $cardIds = $this->cardIds;
                      }
                      $checkStatus = $this->checkIntOrArray($cardIds);
                      if($checkStatus){
                               if($checkStatus == 1){
                                    return $this->model->field('panterid')->where(['customid'=>['in',$cardIds]])->select();
                               }else{
                                    return $this->model->field('panterid')->where(['customid'=>$cardIds])->select();
                               }
                         }
        }


}