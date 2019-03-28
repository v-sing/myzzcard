<?php
/**
 * Created by PhpStorm.
 * User: huawei
 * Date: 2018/12/20
 * Time: 11:30
 */

namespace Service;


use Home\Controller\CoinController;
use Home\Model\TradeWastebooks;
use Think\Exception;
use Think\Model;

class Order extends BaseService
{
        public $TradeWastebooks;
        public $CoinConsume;

        public function __construct()
        {
            $this->TradeWastebooks = $this->model("TradeWastebooks");
            $this->CoinConsume      = $this->model("CoinConsume");
        }

        public $panterid =[
            '1001'=>['panterid'=>'00000286','pname'=>'e+','prefix'=>'e+_','userid'=>'0000000000000204'],
            '1002'=>['panterid'=>'00000284','pname'=>'南阳公司'],
            '1003'=>['pname'=>'fzg','prefix'=>'fzg_'],
            '1004'=>['panterid'=>'00000290','pname'=>'football','prefix'=>'ft_','userid'=>'0000000000000205'],
            '1005'=>['prefix'=>'pos'],
            '1006'=>['prefix'=>'soonpay_'],
            '1007'=>['prefix'=>'wt_'],
            'SOON-O2O-0001'=>['panterid'=>'00000286','pname'=>'e+','prefix'=>'e+_','userid'=>'0000000000000204'],
            'SOON-BALL-0001'=>['panterid'=>'00000290','pname'=>'football','prefix'=>'ft_','userid'=>'0000000000000205']
        ];

        /**
         *  one day is you teacher,day day is you father
         * @param null $souceCode  揭开订单标识
         * @return mixed
         */
        public function revealOrderPrefix($souceCode =null)
        {
                if(array_key_exists($souceCode,$this->panterid)){
                     return $this->panterid[$souceCode]['prefix'];
                }else{
                     false;
                }
        }

        /**
         * @param array $consumeInfo 消费记录
         * @param null $souceCode    订单标识
         */
        public function  backPay($tradeWasteBooksInfo=[],$souceCode=null)
        {
                #初始化订单记录表
                $TradeWasteBooksModel = new Model();//$this->model("TradeWastebooks");
                $TradeWasteBooksModel->startTrans();
                try{
                    //获取订单前缀
                    $prefix = $this->revealOrderPrefix($souceCode);
                   if(is_null($prefix) || empty($prefix) || !$prefix){
                         return [
                                'code'  => 10004,
                                'msg'   => '订单标识匹配失败'
                         ];
                   }
                //获取订单标识
                $tradeid = array_column($tradeWasteBooksInfo,"tradeid");
                #初始化通宝消耗提供商
                $CoinConsumeService = new CoinConsume();
                $CoinConsumeService->getInfo(["tradeid"=>['in',$tradeid]]);
                #获取主键ID
                $consumeid = (new CoinController())->getFieldNextNumber("a");


                }catch (Exception $e){
                    $TradeWasteBooksModel->rollback();
                }
        }
        //回退TW表消费记录
        public function  backTradeWasteBookOrder($consumeInfo,$TradeWasteBooksModel){

                array_map(function ($value) use ($TradeWasteBooksModel) {
                    $tradepoint = floatval($value['tradepoint']);
                    $value['tradepoint'] = "'$tradepoint'";
                    $value['tradetype'] = '09';
                    $value['termno'] = '00000002';
                    $value['placeddate'] = date("Ymd");
                    $value['placedtime'] = date("H:i:s");
                    $value['pretradeid'] = $value['tradeid'];
                    $tradeWastebooksSql = $this->getInsertSql($value, "trade_wastebooks");
                    $TradeWasteBooksModel->execute($tradeWastebooksSql);
                }, $consumeInfo);

        }
       //回退coin_consume 记录
        public function  backCoinConsumeOrder($consumeInfo,$CoinConsumeModel)
        {
                array_map(function ($value) use ($CoinConsumeModel) {
                    $tradepoint = floatval($value['tradepoint']);
                    $value['tradepoint'] = "'$tradepoint'";
                    $value['tradetype'] = '09';
                    $value['termno'] = '00000002';
                    $value['placeddate'] = date("Ymd");
                    $value['placedtime'] = date("H:i:s");
                    $value['pretradeid'] = $value['tradeid'];
                    $CoinConsumeSql = $this->getInsertSql($value, "coin_consume");
                    $CoinConsumeModel->execute($CoinConsumeSql);
                }, $consumeInfo);

        }


}