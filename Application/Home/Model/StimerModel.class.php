<?php
namespace Home\Model;
use Think\Model;
class StimerModel extends Model
{
    protected $tableName = 'customs';
    private $status;
    private $cardkind;
    private $cardk;
    private $point;

    public function _initialize()
    {
        $this->status = "'Y'";
        $this->cardkind = "('6882','2081','6880')";
        $this->point = 0;
        $this->cardk = "('6882','2081','6880','6666','6999','6668')";
    }
    /*
* @content 获取用户单日充值11111*/
    public function getCharger($amount = 0, $nu = 0, $customid = null, $date = null)
    {

        $where = "ca.status={$this->status} and ca.cardkind not in{$this->cardk} and ca_p_l.point={$this->point}";

        $where_ex = " where 1=1";
        if ($date) {
            $where_ex .= " and placeddate='{$date}'";
        }
        if ($customid) {
            $where_ex .= " and customid='{$customid}'";
        }

        $sql = "select * from(select my.*,rownum as rn from(
                select customid,personidtype,personid,namechinese,linktel,residaddress,email,career,ip,cardno,unitname,countyid,sum(amount) amount,count(customid) nu,placeddate,placedtime,paymenttype,pname,po,ps,psb,purchaseid from (select cu.customid,cu.personidtype,cu.personid,cu.namechinese,cu.linktel,cu.residaddress,cu.email,cu.career,ca_p_l.cardno,ca_p_l.ip,cu.unitname,cu_c.cid,cu.countyid,ca_p_l.amount,ca_p_l.placeddate,ca_p_l.placedtime,cp_p_l.paymenttype,p.namechinese pname,p.organizationcode po,p.settlebankid ps,p.settlebankname psb,ca_p_l.purchaseid from customs cu 
                join customs_c cu_c on cu.customid=cu_c.customid
                join cards ca on cu_c.cid=ca.customid
                join card_purchase_logs ca_p_l on ca_p_l.cardno=ca.cardno
                join custom_purchase_logs cp_p_l on cp_p_l.purchaseid=ca_p_l.purchaseid
                join panters p on p.panterid = ca.panterid
                where {$where} ) group by
                customid,personidtype,personid,namechinese,linktel,unitname,countyid,placeddate,placedtime,residaddress,email,career ,cardno,ip,paymenttype,pname,po,ps,psb,purchaseid having sum(amount)>={$amount} or count(customid)>={$nu})my)
                {$where_ex}  order by placeddate desc";
//        echo $sql;exit;
        $result = $this->query($sql);

        if ($result) {
//            dump($result);exit;
            return $result;
        } else {
            return 0;
        }

    }
    /*
* @content 获取用户单日消费111*/
    public function getTrader($amount = 0, $nu = 0, $customid = null, $date = null)
    {
        $where = "ca.status={$this->status} and ca.cardkind not in{$this->cardk} and tr.tradepoint={$this->point}";

        $where_ex = " where 1=1";
        if ($date) {
            $where_ex .= " and placeddate='{$date}'";
        }
        if ($customid) {
            $where_ex .= " and customid='{$customid}'";
        }

        $sql = "select * from(select my.*,rownum as rn from(
                select tradeid,eorderid,ps,psb,pname,po,cardno,customid,personidtype,personid,namechinese,linktel,residaddress,email,career,unitname,
                countyid,sum(amount) amount,count(customid) nu,placeddate,placedtime from (select cu.customid,cu
                .personidtype,cu.personid,cu.namechinese,cu.linktel,cu.residaddress,cu.email,cu.career,cu.unitname,
                cu_c.cid,cu.countyid,tr.tradeamount amount,tr.placeddate,tr.placedtime,tr.eorderid,tr.tradeid,ca.cardno,p.namechinese pname,p.organizationcode po,p.settlebankid ps,p.settlebankname psb from customs cu join customs_c cu_c on cu.customid=cu_c.customid
                join cards ca on cu_c.cid=ca.customid
                join trade_wastebooks tr on ca.customid=tr.customid
                join panters p on p.panterid = tr.panterid
                where {$where} ) group by  tradeid,eorderid,ps,psb,pname,po,cardno,customid,personidtype,personid,namechinese,linktel,residaddress,email,career,unitname,countyid,placeddate,placedtime having sum(amount)>={$amount} or count(customid)>={$nu}) my)
                {$where_ex}";

//       echo $sql;exit;
        $result = $this->query($sql);

        if ($result) {
            return $result;
        } else {
            return 0;
        }

    }
    }