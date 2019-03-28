<?php
namespace Home\Model;
use Think\Model;

class KfModel extends Model
{
    public static function getNoramlGoods($map){
        return M('kf_goods')->where($map)->order('sort asc')->select();
    }
    public static function getNoramlGoodsDesc($map){
        return M('kf_goods')->where($map)->order('goodsid desc')->select();
    }
    //新增菜品
    public static function addGoods($map){
        $sql="INSERT INTO kf_goods (goodsid,panterid,type,goodsname,price,status,sort) VALUES ('{$map['goodsid']}','{$map['panterid']}','{$map['type']}','{$map['goodsname']}','{$map['price']}','1','{$map['sort']}')";
        return M('kf_goods')->execute($sql);
    }
    //新增菜品时  添加菜品对应的列别
    public static function addType($map,$flag){
        $typelist=M('kf_type')->where(['panterid'=>$map['panterid'],'type'=>$flag])->find();
        if($typelist==false){
            $sql="INSERT INTO kf_type (panterid,type,name,status) VALUES ('{$map['panterid']}','{$flag}','{$map['typename']}','1')";
            return M('kf_type')->execute($sql);
        }else{
            return true;
        }
    }
    //返回kf_goods goodsid
    public static function goodsid($map){
        $goodslist=M('kf_goods')->where($map)->select();
        if($goodslist==false){
            return 1;
        }else{
            $arr=array_column($goodslist,'goodsid');
            rsort($arr);
            return $arr[0]+1;
        }
    }
    //退菜列表获取
    public static function refundlist($map){
        $normal=['type'=>1,'panterid'=>$map['panterid'],'cardno'=>$map['cardno'],'cardnum'=>$map['num'],'flag'=>'02','placeddate'=>date('Ymd')];
        $resNormal=self::searchGoodsClass($normal);
        $resNormal=self::returnGoods($normal,$resNormal);
        $other=['type'=>'2','panterid'=>$map['panterid'],'cardno'=>$map['cardno'],'cardnum'=>$map['num'],'flag'=>'02','placeddate'=>date('Ymd')];
        $resOther=self::searchGoodsClass($other);
        if($resOther==true){
            $resOther=self::returnGoods($other,$resOther);
        }
        $data=self::combine($resNormal,$resOther);
        foreach($data as $key=>$val){
            $val['price']=floatval($val['price']);
            $data[$key]=$val;
        }
        return ['status'=>'1','data'=>$data,'codemsg'=>'查询成功'];
    }

    //分类查询 获取消费菜品
    private function searchGoodsClass($map){
        $sql="SELECT sum(num) as num,goodsname,price,goodsid,type from kf_order where type='{$map['type']}' AND panterid='{$map['panterid']}' AND cardno='{$map['cardno']}' AND cardnum='{$map['cardnum']}' ";
        $sql.="AND flag='{$map['flag']}' AND placeddate='{$map['placeddate']}' group by goodsname,price,goodsid,type ";
        return M('kf_order')->query($sql);
    }
    //把相同菜品消费 与退菜的数量合并
    private function returnGoods($map,$data){
        $map['flag']='04';
        if($map['type']=='1'){
            $lists=self::searchGoodsClass($map);
            if($lists==true){
                foreach($lists as $k=>$v){
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }else{
            $lists=self::searchGoodsClass($map);
            if($lists==true){
                foreach($lists  as $v){
                    //价格一样 商品id一样才删除
                    foreach($data as $key=>$val){
                        if($v['goodsid']==$val['goodsid']&&$v['price']==$val['price']){
                            $val['num']-=$v['num'];
                            if($val['num']==0){
                                unset($data[$key]);
                                break;
                            }
                            $data[$key]=$val;
                            break;
                        }
                    }
                }
            }
            return $data;
        }

    }
    //散装菜品订单 与正常菜品 订单合并
    private function combine($resNormal,$resOther){
        if($resNormal==true||$resOther==true){
            if($resNormal==true){
                foreach($resNormal as $val){
                    $data[]=$val;
                }
            }
            if($resOther==true){
                foreach($resOther as $val){
                    $data[]=$val;
                }
            }
            if($resNormal==true&&$resOther==true){
                $data=array_merge($resNormal,$resOther);
            }
        }
        if($resNormal==false && $resOther==false){
            return ['status'=>'03','codemsg'=>'当日没有交易数据'];
        }
        return $data;
    }
    //-----------------------------------------------------------------开封九道湾统计报表------------------------------
    //计算普通充值金额
    public static function ChargeSum($where){
        $chonglist=M('kf_order')->alias('kf')
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
    //特惠充值详情
    public static function giftDetail($map,$field){
        $count = M('kf_order')->alias('kf')
                ->join("left join custom_purchase_logs cup on cup.purchaseid=kf.tradeid")
                ->field($field)->where($map)
                ->count();
        $page = new \Think\Page($count,10);
        $giftDetail = M('kf_order')->alias('kf')
                              ->join("left join custom_purchase_logs cup on cup.purchaseid=kf.tradeid")
                              ->field($field)->where($map)
                              ->order('cup.placeddate desc,cup.placedtime desc')
                              ->limit($page->firstRow.','.$page->listRows)
                              ->select();
        return ['data'=>$giftDetail,'show'=>$page->show()];

    }
    //交易详情查询 根据 交易号与交易类型锁定
    public static function orderCardInfo($map){
        return M('kf_order')->where($map)->field('cardno,cardnum')->find();
    }
    //查询卡的 消费 退菜详情
    public static function orderTradeInfo($map,$urlMap){
        $count = M('kf_order')->where($map)->count();
        $page  = new \Think\Page($count,10,$urlMap);
        $where =[ 'kf.cardno'=>$map['cardno'],
                  'kf.cardnum'=>$map['cardnum'],
                  'kf.flag' => $map['flag']

        ];
        $detail= M('kf_order')->alias('kf')
                              ->join('left join panters p on  kf.panterid=p.panterid')
                              ->where($where)
                              ->limit($page->firstRow.','.$page->listRows)
                              ->order('kf.placeddate desc,kf.placedtime desc')
                              ->field('kf.*,p.namechinese as pname')
                              ->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }
    //结算大食堂 消费的合计
    public static function orderConsumeSum($map){
        $info= M('kf_order')->where($map)->field('nvl(sum(price*num),0) tradeamount')->select();
        return $info[0];
    }
    //计算 退卡 以及 退菜 在trade_wastebooks中统计
    public static function tradeCardSum($map){
       $info= M('kf_order')->alias('kf')
                        ->join("left join trade_wastebooks tw on tw.tradeid=kf.tradeid")
                        ->field('nvl(sum(tradeamount),0) tradeamount')
                        ->where($map)
                        ->select();
        return $info[0];
    }
    // 合卡消费 出入 金额和合计
    public static function combineCardSum($map){
        $info = M('kf_combine')->where($map)->field('nvl(sum(amount),0) amount')->select();
        return $info[0];
    }
    //获取 开封大食堂商户
    public static function getKfPanters($parent,$chongPanterid){
        //收银端去除
        $pantelists=M('panters')->where(['parent'=>$parent])->field('panterid')->select();
        foreach($pantelists as $key=>$val){
            if(array_search($chongPanterid,$val)){
                unset($pantelists[$key]);
                break;
            }
        }
        return $pantelists;
    }
    //返回大食堂各个档口交易详情
    public static function everyPanterOrderInfo($map,$urlMap){
        $count = M('kf_order')->alias('g')
                              ->join('left join panters p on p.panterid=g.panterid')
                              ->where($map)->count();// 查询满足要求的总记录数
        $page = new \Think\Page($count,10,$urlMap);
        $detail=M('kf_order')->alias('g')
            ->join('left join panters p on p.panterid=g.panterid')
            ->where($map)->order('g.placeddate desc')->field('g.*,p.namechinese as pname')
            ->limit($page->firstRow.','.$page->listRows)->select();
        return ['data'=>$detail,'show'=>$page->show()];
    }
    //返回开封九道弯下的所有商户
    public static function getKfPanterids($panterid){
        $map['parent']=$panterid;
        return array_column(M('panters')->where($map)->field('panterid')->select(),'panterid');
    }
    /*分组查询商户 消费 ，退菜金额
    * var
    */
    public static function groupConsumeRefund($panterlists,$type,$start,$end){
        $map=['panterid'=>$panterlists,'flag'=>'0','tradetype'=>$type,'placeddate'=>$start];
        $sql="SELECT sum(tw.tradeamount) tradeamount,p.namechinese,p.panterid FROM trade_wastebooks tw left join panters p on p.panterid=tw.panterid  WHERE tw.panterid IN ({$panterlists}) AND tw.flag ='{$map['flag']}' AND tw.tradetype = '{$map['tradetype']}' AND tw.placeddate >='{$map['placeddate']}' AND tw.placeddate<='{$end}' GROUP BY p.namechinese,p.panterid";
        return M('trade_wastebooks')->query($sql);
    }
    //返回团冲定点列表
    public static function teamlist($date){
        return M('kf_team')->where(['placeddate'=>$date,'flag'=>'1'])->field('distinct teamid')->select();
    }
}
