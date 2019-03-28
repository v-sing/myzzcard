<?php
/**
 * User: szj
 * Date: 2017/1/11
 * Time: 15:47
 * /user/mobile/sendmsg.json
 * http://ysco2o.yijiahn.com/jyo2o_web/user/mobile/firstorg.json
 * http://ysco2o.yijiahn.com/jyo2o_web/user/mobile/sendmsg.json
 */
namespace Home\Controller;
use Think\Controller;

header("content-type:text/html;charset=utf-8");
class SdmgController{
    protected $customs;
    protected $count;

    public function __construct(){
        set_time_limit(0);
        $this->count=1;
    }

    public function countPeople(){
        $sql = "select count(*) as people from (select cu.customid,cu.linktel,cu.namechinese,
                remindamount amount,co.enddate,co.cardid from customs cu join customs_c cu_c on cu.customid=cu_c.customid
                join coin_account co on co.cardid=cu_c.cid ) ss join cards ca on ss.cardid=ca.customid";
        $m = M();
        $count = $m->query($sql);
        echo $count[0]['people'];
        exit();

    }

    public function sendMsg(){
        $start = trim($_GET['start']);//开始条数
        $end = trim($_GET['end']);//结束条数

        $sql = "select * from (select insomnia.*,rownum as rn from  (select ss.*,ca.cardno from (select cu.customid,cu.linktel,cu.namechinese,
                remindamount amount,co.rechargeamount sendamount,co.enddate,co.cardid,co.placeddate senddate  from customs cu join customs_c cu_c on cu.customid=cu_c.customid
                join coin_account co on co.cardid=cu_c.cid ) ss join cards ca on ss.cardid=ca.customid
                ) insomnia) where (rn>={$start}) and (rn<={$end})";
        $m = M();
        $result = $m->query($sql);
        $url = "http://o2o.yijiahn.com/jyo2o_web/user/mobile/sendmsg.json";

        $year = date("Y");
        $month = date('m');//获取当前月
        $nowtime = strtotime(date("Ymd",time()));


        foreach($result as $key => $v){
            $sendtime = date("Ymd H:i:s",time());
            $endtime = strtotime($v['enddate']);
            $phone = $v['linktel'];
            $day = floor(($endtime-$nowtime)/60/60/24);
            $amount = number_format($v['amount'],'2','.','');

            $send_year = substr($v['senddate'],0,4);
            $send_month = substr($v['senddate'],4,2);
            $send_day = substr($v['senddate'],6,2);
            $content = "尊敬的建业通宝用户，您在{$send_year}年{$send_month}月{$send_day}日获赠建业通宝({$v['cardno']}){$v['sendamount']}，截止{$year}年{$month}月15日（不含当日）此次赠送的通宝余额为“{$amount}”，距离到期还有“{$day}”天，祝您工作愉快，生活顺心。";
            $data = array("mobile"=>"15638508585","content"=>$content);
            $log = "sendtime:$sendtime  sendphone:{$phone}  sendcontent:{$content}".$this->doCurl($data,$url)."\n\t";
            file_put_contents("./sendEhomeMsg.log",$log,FILE_APPEND);
           // echo $content."<hr/>";
        }

    }
    protected function doCurl($data,$url){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close();
        return $output;
    }

}

