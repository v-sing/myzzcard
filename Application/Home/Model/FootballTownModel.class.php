<?php
namespace Home\Model;
use Think\Model;

class FootballTownModel extends  Model
{
    //返回大食堂各个档口交易详情s
    public static function stall($map,$urlMap){
        $count = M('zzk_catering_trade')->alias('g')
            ->join('left join zzk_store s on s.outid=g.storeid')
            ->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10,$urlMap);

        $detail= M('zzk_catering_trade')->alias('g')
                    ->join('left join zzk_store s on s.outid=g.storeid')
                    ->where($map)->order('g.placeddate desc,g.placedtime desc')
                    ->field('g.*,s.name')
                    ->limit($page->firstRow.','.$page->listRows)->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }
    //分组查询商户 消费 ，退菜金额
    public static function groupConsumeRefund($map){
        $subSql = M('zzk_catering_trade')
                  ->group('panterid,storeid')
                  ->where($map)
                  ->field('panterid,storeid, nvl(sum(payamount),0) tradeamount')->buildSql();
        $model = new Model();
        $info  = $model->table($subSql. " sub")
                       ->join('left join zzk_store s on s.outid=sub.storeid')
                       ->field('sub.panterid,sub.storeid,sub.tradeamount,s.name')

                       ->order('sub.panterid asc,sub.storeid asc')
                       ->select();
        return $info;

    }

    //
    public static function teamChargeList($map){
        $map['flag'] = '01';
        $map['_string'] = 'teamid is not null';
        return M('zzk_catering_trade')->where($map)
                                      ->field('order_sn,sum(price) amount,teamid')
                                      ->group('order_sn,teamid')
                                      ->order('teamid asc')
                                      ->select();
    }

    //计算合计金额
    public static function sumAmount($map,$field){
        return M('zzk_catering_trade')
                              ->where($map)
                              ->field("nvl(sum({$field}),0) amount")
                              ->find()['amount'];
    }

}