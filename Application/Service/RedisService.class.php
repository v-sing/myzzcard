<?php
/**
 * Created by PhpStorm.
 * User: HWDvip
 * Date: 2018/12/21
 * Time: 9:13
 */

namespace Service;


use Think\Model;

class RedisService  extends Model
{
        public $client;
        public $prefix;
        public function  __construct()
        {
             $this->client = new \Redis();
             $this->client->connect("127.0.0.1");
            // $this->client->auth("Redisjyzz123");
             $this->prefix = "jycard";
             parent::__construct();
        }

        /**
         * @param $tableName 键值
         * @param $date   时间
         * @param $data  json数据
         */
        public function setZAddByScore($tableName,$date,$data)
        {
            $res = $this->client->zAdd($this->prefix.$tableName,$date,$data);
            if($res){
                return true;
            }
            return false;
        }
        /**
         * @param $tableName 键值
         * @param $start  开始时间
         * @param $end     结束时间
         * @param $offset  偏移量
         * @param $limit   分页数
         */
        public function  getZInfoByScore($tableName,$start,$end,$offset,$limit)
        {
            return  $this->client->zRangeByScore($this->prefix.$tableName,$start,$end,["limit"=>[$offset,$limit]]);
        }

        /**
         * @param $tableName
         * @param $start
         * @param $end
         * @return countPage  分页总数
         */
        public function getZCountByScore($tableName,$start,$end)
        {
            $count  = $this->client->zCount($this->prefix.$tableName,$start,$end);
            if($count){
                return $count;
            }
            return false;
        }

    /** 查看Score 是否存在
     * @param $tableName
     * @param $key
     * @return bool
     */
        public function existsScore($tableName,$key)
        {
                $res = $this->client->zCount($this->prefix.$tableName,$key,$key);
                if($res){
                    return true;
                }
                return false;
        }


        public function delZScore($tableName,$key)
        {
               $this->client->zRemRangeByScore($this->prefix.$tableName,$key,$key);
               return $this;
        }

}