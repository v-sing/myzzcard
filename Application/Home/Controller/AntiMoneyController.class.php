<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class AntiMoneyController extends CommonController{

 public function _initialize() {
     header("content-type:text/html;charset=utf-8");
     ini_set('memory_limit','1280M');
 }

    public function index(){

       $this->display();
    }

    public function  countTrade(){
          $times = 20;//单日交易次数
          $tradeamount = 200000;//单日交易金额
          $page = I('post.page',1,'intval');
          $pageSize = I('post.rows',20,'intval');
          $sort = I('post.sort','customid');
          $order = I('post.order','desc');
          $gtime = trim($_REQUEST['date']);
          $timeCondition = "";
          if($gtime){
              $time = str_replace('-','', $gtime);
              $timeCondition = " and (d.placeddate={$time})";
          }


   //       $panterid="'00000509','00000923','00000922','00000924','00000915'";
          $where = "1=1 ";
          $where .=" and (c.status='Y') and (d.tradetype='00') and (ac.type='00')";
//          $where .=" and a.panterid  in($panterid)";
            $where .=" and a.namechinese not like '%食堂%'";

            $where .=" and a.namechinese not like '%九道%'";

          $customs = M('customs');
          $result = $customs->alias('a')
                  ->join('account ac on a.customid=ac.customid')
                  ->join('customs_c b on a.customid=b.customid')
                  ->join('cards c on b.cid=c.customid')
                  ->join('trade_wastebooks d on c.cardno=d.cardno')
                  ->field("a.customid,count(a.customid) as times,
                           sum(d.tradeamount) as tradeamount,
                           ac.amount,a.namechinese,a.linktel,d.placeddate")
                  ->where($where.$timeCondition)
                  ->group("a.customid,ac.amount,a.namechinese,a.linktel,d.placeddate")
                  ->having("count(a.customid)>{$times} or sum(d.tradeamount)>{$tradeamount}")
                  ->order("{$sort} {$order}")
                  ->page($page,$pageSize)
                  ->select();

           $count = $customs->table("(SELECT  a.customid,d.placeddate
                           FROM customs a INNER JOIN account ac on a.customid=ac.customid
                           INNER JOIN customs_c b on a.customid=b.customid
                           INNER JOIN cards c on b.cid=c.customid
                           INNER JOIN trade_wastebooks d on c.cardno=d.cardno
                           WHERE ({$where}{$timeCondition})
                           GROUP BY a.customid,d.placeddate
                           HAVING count(a.customid)>{$times} or sum(d.tradeamount)>{$tradeamount})")->count();

//           dump($customs->getLastSql());exit;

           $handle_result = $returnInfo = array();
           foreach ($result as $k => $v){
                if($v['tradeamount']==0){
                 continue;
                }
                $handle_result[$k] = $v;
                $handle_result[$k]['riskgrade'] = self::analyzeRisk($v['customid']);
                $handle_result[$k]['date'] = date("Y-m-d",strtotime($v['placeddate']));

           }
//           dump($handle_result);exit;
           $returnInfo['total']=!empty($count)?$count:0 ;
           $returnInfo['rows']=!empty($handle_result)?array_values($handle_result):'';
           $this->ajaxReturn($returnInfo);
    }

    public function saveRisk(){
          $getInfo = I('post.info');

          $data = array();
          $anti = M('antimoney');
          $count = 0;
          foreach ($getInfo as $k => $v){
              $data[$k]['customid'] = $v['customid'];
              $data[$k]['amount']   = $v['amount'];
              $data[$k]['times']    = $v['times'];
              $data[$k]['tradeamount'] = $v['tradeamount'];
              $data[$k]['riskgrade'] = $v['riskgrade'];
              $data[$k]['tradetime'] = $v['date'];
              $data[$k]['commitdate'] = date('Y-m-d H:i:s',time());
          }
          foreach ($data as $k => $v){
             $find = $anti->where(array('customid'=>$v['customid'],'tradetime'=>$v['tradetime']))->find();
             if($find){
                 continue;
             }
              $field = $value = array();
              $sql = $fields = $values = '';
              foreach ($v as $k1=>$v1){
              $field[] = $k1;
              $value[] = "'".$v1."'";
              }
              $fields = implode(',',$field);
              $values = implode(',',$value);
              $sql = "insert into antimoney ($fields) values ($values)";
              $bool = $anti->execute($sql);
              if($bool){
                  $count++;
              }
          }
          if($count>0){
             die(json_encode(array('msg'=>"{$count}位用户进入反洗钱名单")));
          }else{
              die(json_encode(array('msg'=>"名单已存在")));
          }
    }
   //反洗钱信息
    public function listRisk(){

         $this->display();
    }
    public function getListData(){
        $page = I('post.page',1,'intval');
        $pageSize = I('post.rows',20,'intval');
        $sort = I('post.sort','customid');
        $order = I('post.order','desc');
        $startdate = trim($_REQUEST['startdate']) ? trim($_REQUEST['startdate']) : date("Y-m-d",time());
        $enddate = trim($_REQUEST['enddate']) ? trim($_REQUEST['enddate']) : date("Y-m-d",time());
        $listRisk = M('antimoney');
        $where = "a.isdelet=0 ";
        $where .= " and (a.tradetime>='{$startdate}') and (a.tradetime<='{$enddate}')";
        $result = $listRisk->alias('a')
                           ->join("customs b on a.customid=b.customid")
                           ->where($where)
                           ->order("a.{$sort} {$order}")
                           ->page($page,$pageSize)
                           ->select();
        $count = $listRisk->alias('a')
                           ->join("customs b on a.customid=b.customid")
                           ->where($where)
                           ->count();
        foreach ($result as $k => $v){
            $data[$k]['namechinese'] = $v['namechinese'];
            $data[$k]['linktel'] = $v['linktel'];
            $data[$k]['customid'] = $v['customid'];
            $data[$k]['amount']   = $v['amount'] ? $v['amount'] : 0;
            $data[$k]['times']    = $v['times'];
            $data[$k]['tradeamount'] = $v['tradeamount'];
            $data[$k]['riskgrade'] = $v['riskgrade'];
            $data[$k]['date'] = $v['tradetime'];
        }
        $returnInfo['total']=!empty($count)?$count:0 ;
        $returnInfo['rows']=!empty($data)?array_values($data):'';
        $this->ajaxReturn($returnInfo);
    }

    public function delet(){
        $getInfo = I('post.info');
        $data = array();
        $anti = M('antimoney');
        $count = 0;
        foreach ($getInfo as $k => $v){
            $data[$k]['customid'] = $v['customid'];
            $data[$k]['tradetime'] = $v['date'];
        }
        foreach ($data as $k => $v){
            $bool = $anti->where(array("customid"=>$v['customid'],"tradetime"=>$v['tradetime']))->save(array("isdelet"=>1));
            if($bool){
                $count++;
            }
        }
        if($count>0){
            die(json_encode(array('msg'=>"{$count}位用户删除")));
        }else{
            die(json_encode(array('msg'=>"失败")));
        }
    }

    /*@content 计算客户风险  */
    private function analyzeRisk($customid){
        $customid = $customid;
        //第一项客户信息公开程度
        $customerGrade = 0;
        $pCustomer = 1;//权重
        $storeInfo = array();
        $customer = M('customs');
        $customerInfo = $customer->where("customid='{$customid}'")->find();
        $storeInfo['name'] = $customerInfo['namechinese'] ? $customerInfo['namechinese'] : "inexistence";
        $storeInfo['persionId'] = $customerInfo['personid'] ? $customerInfo['personid'] : "inexistence";
        $storeInfo['sex'] = $customerInfo['sex'] ? $customerInfo['sex'] : "inexistence";
        $storeInfo['phone'] = $customerInfo['linkman'] ? $customerInfo['linkman'] : "inexistence";
        $storeInfo['persionIdDate'] = $customerInfo['persionidexdate'] ? $customerInfo['persionidexdate'] : "inexistence";
        $storeInfo['address'] = $customerInfo['residaddress'] ? $customerInfo['residaddress'] : "inexistence";
        $storeInfo['unitName'] = $customerInfo['unitname'] ? $customerInfo['unitname'] : "inexistence";
        $storeInfo['cityId'] = $customerInfo['cityid'] ? $customerInfo['cityid'] : "inexistence";
        $storeInfo['frontImg'] = $customerInfo['frontimg'] ? $customerInfo['frontimg'] : "inexistence";
        $count = array_count_values(array_values($storeInfo));
        $count = $count['inexistence'] ? $count['inexistence'] : 0;
        if($count < 5){
           $customerGrade = 1;
        }elseif($count == 5){
            $customerGrade = 2;
        }elseif($count == 6){
            $customerGrade = 3;
        }elseif($count == 7){
            $customerGrade = 4;
        }elseif($count == 8){
            $customerGrade = 5;
        }else{
            $customerGrade = 5;
        }
        $calculate[] = array($customerGrade,$pCustomer);
        //第二项
        $secondGrade = 1;
        $pSecond = 2;
        $calculate[] = array($secondGrade,$pSecond);
        //第三项
        if($storeInfo['frontImg'] == 'inexistence'){
            $threeGrade = 2;
        }else{
            $threeGrade = 1;
        }
        $pThree = 6;
        $calculate[] = array($threeGrade,$pThree);
        //第四项
        $fourGrade = 1;
        $pFour = 5;
        $calculate[] = array($fourGrade,$pFour);
        //第五项
        $fiveGrade = 1;
        $pFive = 8;
        $calculate[] = array($fiveGrade,$pFive);
        //第六项
        $age = $customerInfo['age'] ? $customerInfo['age'] : 0;
        if($age>=31 && $age<=40){
            $sixGrade = 5;
        }elseif($age>=21 && $age<=30){
            $sixGrade = 4;
        }elseif($age>=41 && $age<=50){
            $sixGrade = 3;
        }elseif($age>=51 && $age<=60){
            $sixGrade = 2;
        }elseif($age>60){
            $sixGrade = 1;
        }else{
            $sixGrade = 5;
        }
        $pSix = 2;
        $calculate[] = array($sixGrade,$pSix);
        //第七项
        $registerDate = $customerInfo['placeddate'] ? $customerInfo['placeddate'] : date('Ymd',time());
        $existenceTime = time() - strtotime($registerDate);
        $existence_day = intval($existenceTime/86400);
        if($existence_day < 365){
            $sevenGrade = 5;
        }elseif($existence_day>=365 && $existence_day<365*2){
            $sevenGrade = 4;
        }elseif($existence_day>=365*2 && $existence_day<365*5){
            $sevenGrade = 3;
        }elseif($existence_day>=365*5 && $existence_day<365*10){
            $sevenGrade = 2;
        }else{
            $sevenGrade = 1;
        }
         $pSeven = 1;
         $calculate[] = array($sevenGrade,$pSeven);

         //第八项
         $eightGrade = 1;
         $pEight = 4;
         $calculate[] = array($eightGrade,$pEight);
         //第九项
         $nineGrade = 1;
         $pNine = 4;
         $calculate[] = array($nineGrade,$pNine);
         //第十项
         $tenGrade = 1;
         $pTen = 3;
         $calculate[] = array($tenGrade,$pTen);
         //第十一项？？？根据交易频次定义
         $elevenGrade = 1;
         $pEleven = 4;
         $calculate[] = array($elevenGrade,$pEleven);

         //第十二项
         $twelveGrade = 1;
         $pTwelve = 7;
         $calculate[] = array($twelveGrade,$pTwelve);

         //计算分数
         $member = 0;
         $deno = 0;
         foreach ($calculate as $v){
           $member += $v[0]*$v[1];
           $deno += $v[1];
         }
        return bcmul(bcdiv($member,$deno,5),20);
    }
    /*
     * @content 大额交易报告*/
    public function baReport(){
        $this->display();
    }

    /*
     * @content 通用可疑交易报告*/
    public function susReport(){
        $this->display();
    }
}
