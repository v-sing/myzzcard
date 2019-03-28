<?php
namespace Home\Model;
use Think\Model;
class EhomeModel extends Model{
    protected $tableName = 'customs';

    public function _initialize(){


    }
    public function getTbinfo($customid){
        $where = array("cu.customid"=>$customid);
        $data = array();
        $re = $this->alias("cu")
                   ->join(" customs_c cu_c on cu_c.customid=cu.customid")
                   ->join("cards ca on cu_c.cid=ca.customid")
                   ->join("coin_account co_a on ca.customid=co_a.cardid")
                   ->join("panters pa on co_a.panterid=pa.panterid")
                   ->field("cu.customid,cu.namechinese name,cu.linktel mobilephone,ca.cardno cardnum, 
                            ca.status,co_a.rechargeamount,co_a.remindamount,
                            co_a.placeddate,co_a.placedtime,co_a.enddate cutofftime
                            ,pa.namechinese pantername")
                   ->where($where)
                   ->select();
        if($re){
            $data['customid'] = $re[0]['customid'];
            $data['name'] = $re[0]['name'];
            $data['mobilephone'] = $re[0]['mobilephone'];
            $data['cardnum'] = $re[0]['cardnum'];
            $placeddate = $re[0]['placeddate'];
            $placeddate = date("Y-m-d",strtotime($placeddate));
            $data['givetime'] = $placeddate." ".$re[0]['placedtime'];

            $data['cutofftime'] = date("Y-m-d",strtotime($re[0]['cutofftime']));
            $data['givepost'] = $re[0]['pantername'];

            $recharge_arr = array_column($re,"rechargeamount");
            $remind_arr = array_column($re,"remindamount");
            $card_arr = array_column($re,"cardnum");

            $status_arr = array_unique(array_column($re,"status"));
            if(in_array('Y',$status_arr)){
                $data['isactive'] = "1";
            }else{
                $data['isactive'] = "0";
            }

            $data['total'] = round(array_sum($recharge_arr),2,PHP_ROUND_HALF_DOWN);
            $data['balance'] = round(array_sum($remind_arr),2,PHP_ROUND_HALF_DOWN);
            $data['cardnum'] = implode(",",array_unique($card_arr));

        }

        return $data;
    }

}
if(!function_exists("array_column")){
    function array_column($arr,$key){
        $newarr=array();
        foreach ($arr as  $v){
            if($v[$key]){
            $newarr[] = $v[$key];
            }
        }
        return $newarr;

    }
}