<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 9:21
 */

namespace Service;


class CoinAccount extends BaseService
{
      public $coinAccountInfo;
      public $modelName = "CoinAccount";
      public $panters;
      public $info;
      public $page;
        public function getCoinAccountInfo($CardId)
      {
          if(is_array($CardId)){
              $CardId = implode(',',$CardId);
              $this->coinAccountInfo =  $this->model('CoinAccount')->getCardSell("cardid in $CardId");
          }else{
              $this->coinAccountInfo =   $this->model('CoinAccount')->getCardSell("cardid = $CardId");
          }
          return $this->coinAccountInfo;
      }

        /** 获取本张卡所有消耗得商户
         * @param $CardId
         * @return mixed
         */
        public function getCoinAccountTongbaoPanter($CardId)
      {
          if(is_array($CardId)){
              $CardId = implode(',',$CardId);
              $info =  M('account')->where("customid in ($CardId) and type='01'")->select();
              $cardids = implode(',',array_column($info,"customid"));
               $this->info =  M('coin_account')->field('panterid,max(sourceorder) sourceorder')->where("cardid in ($cardids)")->group("panterid")->select();
               return $this->info;

          }else{

              $info =  M('ACCOUNT')->where("customid = $CardId and type='01'")->select();
              $cardids = implode(',',array_column($info,"costomid"));
              $this->info =  M('COIN_ACCOUNT')->where("cardid in ($cardids)")->group("panterid")->select();
              return $this->info;
          }
      }

        /** 获取某个商户得消耗情况
         * @param null $where
         * @param null $page
         * @return mixed
         */
        public function getAllCostByPanters($where=null,$page=null)
        {
            if(is_null($page)){
                 $this->info = $this->model('CoinAccount')->getCostByPanter($where,$page);
            }else{
                list($this->info,$this->page) = $this->model('CoinAccount')->getCostByPanter($where,$page);
            }
            return $this->info;
        }

        /**取得合同号
         * @param $where
         */
        public function  getDistinctSourceOrder($where)
        {
            $this->info = $this->model('CoinAccount')->field("distinct sourceorder,panterid")->where($where)->select();
        }


        /**
         * 获取商户的总发行额
         */
        public function  getSumRechangeAmount($where)
        {
            $res =  $this->model('CoinAccount')->field("sum(c.rechargeamount) sellTotalMoney")->where($where)->find();
             return $res['selltotalmoney'];
        }






}