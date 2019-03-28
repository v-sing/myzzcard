<?php
namespace Home\Model;
use Think\Model;

class QifengModel extends  Model
{

    public static function getCardInfo($map){
        return M('qf_order')->where($map)->field('cardno,cardnum')->find();
    }
    public static function CardTradeInfo($map){
        $field = 'q.tradeid,q.cardno,q.cardnum,q.price,q.num,q.goodsname,q.placeddate,q.placedtime,q.payamount,q.flag,p.namechinese pname';
        return M('qf_order')->alias('q')
                            ->join('left join panters p on p.panterid=q.panterid')
                            ->where($map)
                            ->order('q.orderid asc')
                            ->field($field)->select();
    }
    public static function balanceInfo($map){
        $balancelist=M('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join customs_c cc on cc.cid=ca.customid')
            ->join('left join account ac on ac.customid=ca.customid')
            ->where($map)->field('nvl(sum(ac.amount),0) amount')->select();
        return $balancelist[0]['amount'];
    }
    //返回大食堂各个档口交易详情s
    public static function stall($map,$urlMap){
        $count = M('qf_order')->alias('q')
            ->join('left join panters p on q.panterid = p.panterid')
            ->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10,$urlMap);

        $detail= M('qf_order')->alias('q')
                    ->join('left join panters p on p.panterid = q.panterid')
                    ->where($map)->order('q.placeddate desc,q.placedtime desc')
                    ->field('q.*,p.namechinese pname')
                    ->limit($page->firstRow.','.$page->listRows)->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }
    //分组查询商户 消费 ，退菜金额
    public static function groupConsumeRefund($map){
        $subSql = M('qf_order')
                  ->group('panterid')
                  ->where($map)
                  ->field('panterid, nvl(sum(payamount),0) tradeamount')->buildSql();
        $model = new Model();
        $info  = $model->table($subSql. " sub")
                       ->join('left join panters p on p.panterid=sub.panterid')
                       ->field('sub.panterid,sub.tradeamount,p.namechinese pname')
                       ->order('sub.panterid asc')
                       ->select();
        return $info;

    }

    //计算合计金额
    public static function sumAmount($map,$field){
        return M('qf_order')->where($map)
                            ->field("nvl(sum({$field}),0) amount")
                            ->find()['amount'];
    }
    //统计充值金额 与支付金额
    public static function sum($map){
        return M('qf_order')->where($map)
                        ->field("nvl(sum(price),0) price,nvl(sum(payamount),0) pay")
                        ->find();
    }

    // 合卡消费 出入 金额和合计
    public static function combineCardSum($map){
        return M('qf_combine')->where($map)->field('nvl(sum(amount),0) amount')->find()['amount'];
    }

    //特惠统计
    public static function gift($map,$urlMap){
        $count = M('qf_order')->alias('q')
            ->join('left join panters p on q.panterid = p.panterid')
            ->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10,$urlMap);
        $detail= M('qf_order')->alias('q')
                    ->join('left join panters p on p.panterid = q.panterid')
                    ->where($map)->order('q.placeddate desc,q.placedtime desc')
                    ->field('q.*,p.namechinese pname')
                    ->limit($page->firstRow.','.$page->listRows)->select();

        return ['data'=>$detail,'show'=>$page->show()];
    }

    public function customCharge(){

    }
    //获取团卡所有列表
    public static function getTeamids($map){
        $filed = 'teamid,charge,ctype,replaceddate,replacedtime,return,retype,placeddate,placedtime,name,area,linkname,phone,point';
        return M('qf_team_logs')->where($map)->field($filed)->order('teamid asc')->select();
    }

    public static function getConsumeRufundAmount($map){
        $map['flag'] = ['in',['02','04']];
        $info = M('qf_order')->where($map)
                             ->group('flag')
                             ->field('flag,nvl(sum(payamount),0) amount')
                             ->order('flag asc')
                             ->select();
        return $info;
    }


}