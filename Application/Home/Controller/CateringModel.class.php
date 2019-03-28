<?php
namespace Home\Model;
use Think\Model;

class CateringModel extends  Model
{
    //计算普通充值金额
    public static function ChargeSum($prefix,$where){
        $chonglist=M($prefix.'order')->alias('kf')
            ->join("left join custom_purchase_logs cup on cup.purchaseid=kf.tradeid")
            ->where($where)->field('nvl(sum(cup.amount),0) amount,nvl(sum(realamount),0) realamount')->select();
        return $chonglist[0];
    }
    //结算交易金额 基于商户号
    public static function tradeSum($map){
        $consumelist=M('trade_wastebooks')->alias('tw')
            ->join("left join panters p on p.panterid=tw.panterid")
            ->where($map)->field('nvl(sum(tw.tradeamount),0) tradeamount')->select();
        return $consumelist[0]['tradeamount'];
    }
    //结算卡余额
    public static function balanceInfo($map){
        $balancelist=M('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join customs_c cc on cc.cid=ca.customid')
            ->join('left join account ac on ac.customid=ca.customid')
            ->where($map)->field('nvl(sum(ac.amount),0) amount')->select();
        return $balancelist[0]['amount'];
    }
    //返回大食堂机构下的所有商户
    public static function getKfPanterids($panterid){
        $map['parent']=$panterid;
        return array_column(M('panters')->where($map)->field('panterid')->select(),'panterid');
    }
    //获取大食堂 机构下的商户
    public static function getKfPanters($parent,$chongPanterid){
        //收银端去除
        $pantelists=M('panters')->where(['parent'=>$parent])->field('panterid,namechinese')->select();
//        $pantelists=array_column($pantelists,'panterid');
//        if($key=array_search($chongPanterid,$pantelists)){
//            unset($pantelists[$key]);
//        }
        foreach($pantelists as $key=>$val){
            if(array_search($chongPanterid,$val)){
                unset($pantelists[$key]);
                break;
            }
        }
        return $pantelists;
    }
    //分组查询商户 消费 ，退菜金额
    public static function groupConsumeRefund($panterlists,$type,$start,$end){
        $map=['panterid'=>$panterlists,'flag'=>'0','tradetype'=>$type,'placeddate'=>$start];
        $sql="SELECT sum(tw.tradeamount) tradeamount,p.namechinese,p.panterid FROM trade_wastebooks tw left join panters p on p.panterid=tw.panterid  WHERE tw.panterid IN ({$panterlists}) AND tw.flag ='{$map['flag']}' AND tw.tradetype = '{$map['tradetype']}' AND tw.placeddate >='{$map['placeddate']}' AND tw.placeddate<='{$end}' GROUP BY p.namechinese,p.panterid";
        return M('trade_wastebooks')->query($sql);
    }
    //返回大食堂各个档口交易详情
    public static function everyPanterOrderInfo($prefix,$map,$urlMap){
        $count = M($prefix.'order')->alias('g')
            ->join('left join panters p on p.panterid=g.panterid')
            ->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10,$urlMap);
        $detail=M($prefix.'order')->alias('g')
            ->join('left join panters p on p.panterid=g.panterid')
            ->where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')
            ->limit($page->firstRow.','.$page->listRows)->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }


    //返回团冲定点列表
    public static function teamlist($prefix,$date){
        return M($prefix.'team')->where(['placeddate'=>$date,'flag'=>'1'])->field('distinct teamid')->select();
    }

    //
    //交易详情查询 根据 交易号与交易类型锁定
    public static function orderCardInfo($map,$prefix){
        return M($prefix.'order')->where($map)->field('cardno,cardnum')->find();
    }
    //查询卡的 消费 退菜详情
    public static function orderTradeInfo($map,$urlMap,$prefix){
        $count = M($prefix.'order')->where($map)->count();
        $page  = new \Think\Page($count,10,$urlMap);
        $where =[ 'kf.cardno'=>$map['cardno'],
            'kf.cardnum'=>$map['cardnum'],
            'kf.flag' => $map['flag']

        ];
        $detail= M($prefix.'order')->alias('kf')
            ->join('left join panters p on  kf.panterid=p.panterid')
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->order('kf.placeddate desc,kf.placedtime desc')
            ->field('kf.*,p.namechinese as pname')
            ->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }
    //结算大食堂 消费的合计
    public static function orderConsumeSum($map,$prefix){
        $info= M($prefix.'order')->where($map)->field('nvl(sum(price*num),0) tradeamount')->select();
        return $info[0];
    }
    //计算 退卡 以及 退菜 在trade_wastebooks中统计
    public static function tradeCardSum($map,$prefix){
        $info= M($prefix.'order')->alias('kf')
            ->join("left join trade_wastebooks tw on tw.tradeid=kf.tradeid")
            ->field('nvl(sum(tradeamount),0) tradeamount')
            ->where($map)
            ->select();
        return $info[0];
    }
    // 合卡消费 出入 金额和合计
    public static function combineCardSum($map,$prefix){
        $info = M($prefix.'combine')->where($map)->field('nvl(sum(amount),0) amount')->select();
        return $info[0];
    }

}