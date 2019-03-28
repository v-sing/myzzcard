<?php
namespace Home\Model;

use Think\Model;

class SusModel extends Model
{
    protected $tableName = 'customs';
    private $status;
    private $cardkind;
    private $point;

    public function _initialize()
    {
        $this->status = "'Y'";
        $this->cardkind = "('6882','2081','6880')";
        $this->point = 0;

    }

    /*
     * @content 获取用户单日充值*/
    public function getCharge($amount = 0, $nu = 0, $customid = null, $date = null)
    {

        $where = "ca.status={$this->status} and ca.cardkind not in{$this->cardkind} and ca_p_l.point={$this->point}";

        $where_ex = " where 1=1";
        if ($date) {
            $where_ex .= " and placeddate='{$date}'";
        }
        if ($customid) {
            $where_ex .= " and customid='{$customid}'";
        }

        $sql = "select * from(select my.*,rownum as rn from(
                select customid,personidtype,personid,namechinese,linktel,unitname,countyid,sum(amount) amount,count(customid) nu, placeddate 
                from (select cu.customid,cu.personidtype,cu.personid
                      ,cu.namechinese,cu.linktel,cu.unitname,cu_c.cid,cu.countyid,ca_p_l.amount,ca_p_l.placeddate
                from customs cu join customs_c cu_c on cu.customid=cu_c.customid
                join cards ca on cu_c.cid=ca.customid
                join card_purchase_logs ca_p_l on ca_p_l.cardno=ca.cardno
                where {$where} ) group by 
                customid,personidtype,personid,namechinese,linktel,unitname,countyid,placeddate having sum(amount)>{$amount} or count(customid)>{$nu}) my)
                {$where_ex}  order by placeddate desc";


        $result = $this->query($sql);

        if ($result) {
            return $result;
        } else {
            return 0;
        }

    }

    /*
     * @content 获取用户单日消费*/
    public function getTrade($amount = 0, $nu = 0, $customid = null, $date = null)
    {
        $where = "ca.status={$this->status} and ca.cardkind not in{$this->cardkind} and tr.tradepoint={$this->point}";

        $where_ex = " where 1=1";
        if ($date) {
            $where_ex .= " and placeddate='{$date}'";
        }
        if ($customid) {
            $where_ex .= " and customid='{$customid}'";
        }

        $sql = "select * from(select my.*,rownum as rn from(
                select customid,personidtype,personid,namechinese,linktel,unitname,countyid,sum(amount) amount,count(customid) nu, placeddate 
                from (select cu.customid,cu.personidtype,cu.personid
                       ,cu.namechinese,cu.linktel,cu.unitname,cu_c.cid,cu.countyid,tr.tradeamount amount,tr.placeddate
                from customs cu join customs_c cu_c on cu.customid=cu_c.customid
                join cards ca on cu_c.cid=ca.customid
                join trade_wastebooks tr on ca.customid=tr.customid
                where {$where} ) group by 
                customid,personidtype,personid,namechinese,linktel,unitname,countyid,placeddate having sum(amount)>{$amount} or count(customid)>{$nu}) my)
                {$where_ex}";


        $result = $this->query($sql);

        if ($result) {
            return $result;
        } else {
            return 0;
        }

    }
    /*
     * @大额交易数据表*/
    public function inLarge($data){
        $traceid = $data['traceid'];
        $customid = $data['customid'];
        $chargeamount = $data['chargeamount'];
        $chargenu = $data['chargenu'];
        $tradeamount = $data['tradeamount'];
        $tradenu = $data['tradenu'];
        $datadate = $data['datadate'];
        $remarkf = $data['remark'];

        $insert_sql = "insert into sus_deal (traceid,customid,chargeamount,chargenu,
                tradeamount,tradenu,datadate,remarkf,remarks,comm,status,isdel)
                values ('{$traceid}','{$customid}',
                        '{$chargeamount}','{$chargenu}',{$tradeamount},'{$tradenu}',
                         '{$datadate}','{$remarkf}','','',0,0)";

        $bool = $this->execute($insert_sql);
        if($bool){
            return true;
        }else{
            return false;
        }

    }
    /*
     * @content 查询大额交易表*/
    public function search($traceid=null,$status=null){
        $where = "where 1=1 and isdel=0";
        if($traceid){
            $where .= " and traceid='{$traceid}'";
        }
        if($status){
            $where .= " and status={$status}";
        }
        $sql = "select * from sus_deal {$where}";
        $data = $this->query($sql);
        if($data){
            return $data;
        }else{
            return false;
        }
    }
    /*
     * @content 更新大额交易表*/
    public function ud($status,$traceid,$comment=null){
        $msg = "";
        if(!$status || !$traceid){
            return false;
        }
        if($comment){
            $msg .= " ,remarks ='{$comment}'";
        }
        if($status == 1){
            $checker = session("username");
            $placeddate = date("Ymd",time());
            $placedtime = date("H:i:s",time());
            $msg .= " ,checker='{$checker}',placeddate='{$placeddate}',placedtime='{$placedtime}'";
        }
        $sql = "update sus_deal set status={$status}{$msg} 
                where traceid='{$traceid}' and isdel=0";
        $bool = $this->execute($sql);
        if($bool){
            return true;
        }else{
            return false;
        }
    }

    /*
     * @content 查询用户信息*/
    public function getCustomer($customid){
        if(!$customid){
            return false;
        }
        $customer = $this->field("personidtype,personid,namechinese,linktel,
                                  unitname")
            ->where(array("customid"=>$customid))->find();
        return $customer;
    }

    /*
     * @content 删除数据*/
    public function del($traceid){
        if(!$traceid){
            return false;
        }
        $sql = "update sus_deal set isdel=1 
                where traceid='{$traceid}'";
        $bool = $this->execute($sql);
        if($bool){
            return true;
        }else{
            return false;
        }

    }

    /*
     * @content 更新数据*/
    public function up($data,$where){
        if(!$data || !$where){
            return false;
        }
        $updata = "";
        foreach ($data as $k => $v){
            $updata .= "$k='{$v}',";
        }
        $updata = rtrim($updata,",");
        $sql = "update sus_deal set $updata  where $where";
        $bool = $this->execute($sql);
        if($bool){
            return true;
        }else{
            return false;
        }
    }
    /*
     * @content 新增数据*/
    public function inData($data){
        if(!$data){
            return false;
        }
        $values = "";
        foreach ($data as $v){
            $values .= "'{$v}',";
        }
        $values = rtrim($values,",");
        $sql = "insert into sus_deal (traceid,customid,chargeamount,";
        $sql .= "chargenu,tradeamount,tradenu,datadate,remarkf,remarks,";
        $sql .= "status,isdel,placeddate,placedtime,checker,agentname,";
        $sql .= "agentcountry,agidtype,agid,tradeaddress,cubankno,rivalname,rivalbankno)";
        $sql .= " values ($values)";
        $bool = $this->execute($sql);
        if($bool){
            return true;
        }else{
            return false;
        }
    }

}

if (!function_exists("array_column")) {
    function array_column($arr, $key)
    {
        $newarr = array();
        foreach ($arr as $v) {
            if ($v[$key]) {
                $newarr[] = $v[$key];
            }
        }
        return $newarr;

    }
}