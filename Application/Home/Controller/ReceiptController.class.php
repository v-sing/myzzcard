<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/29
 * Time: 14:01
 */

namespace Home\Controller;


use function PHPSTORM_META\type;
use Service\Account;
use Service\BaseService;
use Service\CoinAccount;
use Service\Customs;

use Service\CustomsC;
use Service\CustomsCard;
use Service\Panters;
use Service\ReceiptImg;
use Service\ReceiptPanter;
use Service\TenantExtra;
use Think\Page;


class ReceiptController extends CoinController
{


     #显示商户冲正补缴
     public function changePoint()
     {
            $model = M("panterchange");
            $start      = I('get.start');
            $end        = I('get.end');
            $panterName = I('get.pantername');
            $company    = I('get.company');
            $name       = I('get.name');
            $phone      = I('get.phone');
            if($start!='' && $end==''){
                      $start     = strtotime(str_replace('-','',$start));
                      $end       = time();
            }

            if($start=='' && $end!=''){
                      $start = $model->min('changedate');
                if(is_null($start)){
                    $start = strtotime(date('Y-m-d 00:00:00'));
                }
                      $end   = strtotime(str_replace('-','',$end));
            }
            if($start!='' && $end!=''){
                      $start = strtotime(str_replace('-','',$start))  ;
                      $end   = strtotime(str_replace('-','',$end));
             }
             if($start=='' && $end==''){
                      $start=  $model->min('changedate');
                      if(is_null($start)){
                          $start = strtotime(date('Y-m-d 00:00:00'));
                      }
                      $end  =  time();
             }
                  $where = "changedate>=$start and changedate<=$end";
           if($panterName!=''){
                $where.=" and pantername like '%$panterName%'";
           }
           if($company!=''){
                      $where.=" and company like '%$company%'";
           }
            if($name!=''){
                      $where.=" and customname like '%$name%'";
            }
           if($phone!=''){
                      $where.=" and phone = $phone";
           }

                  $count = $model->where($where)->count();
                  $page = new Page($count,15);
                  $data = $model->where($where)->limit($page->firstRow.','.$page->listRows)->order("changedate desc")->select();
                  session('PanterData',$data);
                  $this->assign('data',$data);
                  $this->assign('page',$page->show2());
                  $this->display();

        }

     #商户冲正补缴Excel
      public function  changePointExcel()
      {

          $strList="时间,客户姓名,手机号码,数量,法人单位,项目单位,触发行为";
          $strlist = iconv("utf-8","gbk",$strList);
          $strlist .= "\n";
          $list = session('PanterData');
          foreach ($list as $key => $val) {
              if($val['changetype'] == 1){
                   $val['type'] = "触发冲正";
              }else{
                  $val['type'] = "触发补缴";
              }
              $val['type']=iconv("utf-8","gbk",$val['type']);
              $val['customname']=iconv("utf-8","gbk",$val['customname']);
              $val['company']=iconv("utf-8","gbk",$val['company']);
              $val['pantername']=iconv("utf-8","gbk",$val['pantername']);
              $strlist .=date('Y-m-d',$val['changedate']).",{$val["customname"]},{$val["phone"]},{$val["pointnum"]},{$val['pantername']},{$val['company']},{$val['type']}\t\n";
          }

          $filename='建业通宝2.0发行金额调整报表'.date('YmdHis');
          $filename = iconv("utf-8","gbk",$filename);
          unset($list);
          $this->load_csv($strlist,$filename);
      }

    /**
     * 改变商户的属性
     */

    public function ChangePointAction()
    {

        $data = I('post.');
        $sourceorder = $data["SOURCEORDER"];
        unset($data["SOURCEORDER"]);
        $data['CHANGEDATE'] = time();
        $data['CHANGETYPE'] == 1?$data['POINTNUM']=$data['POINTNUM'] *(-1):$data['POINTNUM'] = $data['POINTNUM'];
        $customsService = new Customs();
        $coinAccountService = new CoinAccount();
        $accountService  = new Account();
        $customsService->getCustomInfo($data['PHONE']);
        if(empty($customsService->get('customid')) || is_null($customsService->get('customid'))){
            $this->ajaxReturn([
                'msg'  => '抱歉，会员信息有误',
                'code' => '10001'
            ]);
        }
        $coinAccountService->getInfo(["sourceorder"=>$sourceorder]);
        $accountService->getUserInfo($coinAccountService->get("cardid"));
        if($data['POINTNUM']<0){
            if(bccomp($accountService->get('amount'),abs($data['POINTNUM'])) <0){
                $this->ajaxReturn([
                    'msg'  => '抱歉，会员余额不足以扣款',
                    'code' => '10001'
                ]);
            }
        }
        $data['CHANGEID'] = $this->getFieldNextNumber('panterchange');
        //商户赠送记录
        $pointNum =  $data['POINTNUM'];
         $data["OBLIGATE2"] = "'{$accountService->get('amount')}'";
        $data = $this->filterInput(null,$data,['PANTERID','POINTNUM']);
        $sql = $this->getInsertSql($data,'PANTERCHANGE');
        $this->model->execute($sql);
        $coinAccountData = [
            'accountid'       =>$accountService->get('accountid'),
            'rechargeamount'  =>$data['POINTNUM'],
            'remindamount'    =>$data['POINTNUM'],
            'PLACEDDATE'      =>date('Ymd'),
            'placedtime'      =>date('H:i:s'),
            'panterid'        =>$data['PANTERID'],
            'cardid'          =>$this->checkCid($coinAccountService->get('cardid')),
            'sourceorder'     =>$sourceorder,
            'enddate'         =>date('Ymd',strtotime('+2 years')),
            'trigger_rules'   => $data['CHANGETYPE'] == 1?'触发冲正':'触发补缴'
        ];
        $coinAccountData = $this->filterInput(null,$coinAccountData,['accountid','cardid']);
        $sql =  $this->getInsertSql($coinAccountData,"COIN_ACCOUNT");
        $this->model->execute($sql);
        if(trim($data['CHANGETYPE'],"'") == 0){
            $myamount = floatval($accountService->get('amount'));
            $rechargeAount = abs($pointNum);
            $updateAmount = bcadd($myamount,$rechargeAount,2);
            $accountUpdateSql = "update account set amount = $updateAmount where accountid ={$accountService->get('accountid')} and type='01'";
            $this->model->execute($accountUpdateSql);
            $msg = "恭喜你，给{$customsService->get('namechinese')}补缴{$data['POINTNUM']}成功";
        }else{
            $myamount = floatval($accountService->get('amount'));
            $rechargeAount = abs($pointNum);
            $updateAmount = bcsub($myamount,$rechargeAount,2);
            $accountUpdateSql = "update account set amount = $updateAmount  where accountid ={$accountService->get('accountid')} and type='01'";
            $this->model->execute($accountUpdateSql);
            $msg = "恭喜你，给{$customsService->get('namechinese')}冲正{$pointNum}成功";
        }
        $this->ajaxReturn([
            'msg'  =>  $msg,
            'code'  => 0
        ]);
    }
     /**
      *   获取会员所属的所属的商户
      * */
    public function getCustomPanter(){
                $phone      = trim(I('post.phone',null));
                if(is_null($phone) || empty($phone) || !is_numeric($phone) || strlen($phone)>13){
                    $this->ajaxReturn([
                        'msg'    => '请输入正确的手机号',
                        'code'   => '1004'
                    ]);
                }
                #初始化会员提供商
                $customService = new Customs();
                #初始化会员卡号提供商
                $customcService = new CustomsCard();
                #初始化赠卡记录提供商
                $coinaccountService = new CoinAccount();
                #初始化商户提供商
                $pantersService     = new Panters();
                #获取会员信息
                 $customService->getCustomInfo($phone);
                if(is_null($customService->linktel)){
                    $this->ajaxReturn([
                        'msg'    => '获取客户信息失败，请检查信息',
                        'code'   => '1004'
                    ]);
                }
               #获取当前会员所有的卡号
               $customcService->getCustomsCInfo($customService->get("customid"));
               #获取会员所有卡号的所属商户
               $coinaccountService->getCoinAccountTongbaoPanter($customcService->cid);
               #获取商户的信息
               $PanterInfo = $pantersService->getPanterInfo($coinaccountService->panterid);

                if(!$PanterInfo){
                    $this->ajaxReturn([
                        'msg'    => '获取商户赠送记录失败',
                        'code'   => '1005'
                    ]);
            }
            $panters = array_column($PanterInfo,'namechinese','panterid');
            $company = array_column($PanterInfo,'nameenglish','panterid');
            #获取会员所有卡号的打印
            $coinaccountService->getDistinctSourceOrder(["cardid"=>['in',$customcService->cid]]);
            $this->ajaxReturn([
                        'data'  =>[
                              'name'    =>
                              !is_null($customService->get('namechinese'))?$customService->get('namechinese'):'姓名未填写',
                              'panters' => $panters,
                               'company' => $company,
                              "sourceorder" => $coinaccountService->info
                        ],
                        'msg'   => '恭喜你，获取成功',
                        'code'  => 0
                ]);
        }



    /** 过滤输入
     * @param string $type
     * @return mixed
     */
    public function  filterInput($type = 'get',$data=null,$filterField=[])
        {
               if(is_null($data)){
                   $data = I("$type.");
               }
                foreach ($data as $key=>&$val){
                    if(!is_numeric($val) || in_array($key,$filterField) ){
                        $val =  trim($val,"'");
                        $val =  trim($val);
                        $val =  "'$val'";
                    }
                }
                return $data;
        }

    /**
     * 拼接Sql
     */

    public function getInsertSql($data,$table)
    {
        $sql = "insert into $table(".implode(',',array_reverse(array_keys($data))).")values(".implode(',',array_reverse(array_values($data))).")";
        return $sql;
    }


    public function checkCid($cid)
    {
         if(strlen($cid) == 8){
               return $cid;
         }else{
                $offset =8-strlen($cid);
                for ($i=0;$i<$offset;$i++){
                     $cid = "0".$cid;
                }
                return $cid;
         }
    }

    public function load_csv($arrList,$tableName){
        header("Content-type: text/html; charset=gbk");
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$tableName.".csv");
        header('RedisCache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $arrList;
    }

    #兑换方收票
    public function  receives()
    {

        $start     = I('get.start');
        $panterName = I('get.panterName');
        $company    = I('get.company');
        if($start =='') {
            $start = strtotime(date('Ym01'));
        }
        $startTime = date("Ym01",strtotime($start));
        $endTime   = date("Ymd",strtotime("+1 month -1 day",strtotime($startTime)));
        $where     = ['CE.PLACEDDATE'=>[['egt',$startTime],['elt',$endTime]]];
        if($panterName != ''){
            $where['p.namechinese'] = ["like","%$panterName%"];
        }
        if($company != '')
        {
            $where['p.nameenglish'] = ["like","%$company%"];
        }
        $count = count($this->model->table("panters p")->field("sum(ce.amount) tmoney,max(p.namechinese) namechinese,max(p.nameenglish) nameenglish")
            ->join("coin_consume ce on p.panterid = ce.panterid","LEFT")
            ->where($where)
            ->group("p.namechinese")->select());
  //郑州建业至尊商务服务有限公司
        $Page = new Page($count,15);
        $data = $this->model->table("panters p")->field("sum(ce.amount) tmoney,max(p.namechinese) namechinese,max(p.nameenglish) nameenglish")
            ->join("coin_consume ce on p.panterid = ce.panterid","LEFT")->group("p.namechinese")
            ->where($where)->limit($Page->firstRow.",".$Page->listRows)->select();

        if(!is_null($data) && !empty($data)){
            $Imgwhere = ["operator"=>['in',array_column($data,"namechinese")]];
            $imgstart  = date('Y-m-d',strtotime("+1 days",strtotime($endTime)));
            $imgend    = date('Y-m-d',strtotime("+1 month -1 day",strtotime($endTime)));
            $Imgwhere  ["receiptdate"] = [['egt',$imgstart],['elt',$imgend]];
            $Imgwhere  ["del"] = 0;
            $ImgData = $this->model->table("receiptimg g")->where($Imgwhere)
                ->select();
            if(!is_null($ImgData) && !empty($ImgData)){
                        foreach ($data as &$val){
                                 foreach ($ImgData as $v){
                                       if($val['namechinese'] == $v['operator']){
                                           $v['operator']  == $v['receiptcompany']  && $val['imgpanterName'] = 1;
                                           $val['tmoney']  == $v['money'] &&  $val['imgmoney'] = 1;
                                           date("Y-m",$v['uploaddate']) == date("Y-m",strtotime($v['receiptdate'])) && $val['imgdate'] = 1;
                                       }
                                 }
                        }
            }
        }

        $this->assign('data',$data);
        $this->assign('page',$Page->show2());
        $this->display();
    }


    #兑换方收票改
    public function  invoiceReceive()
    {
        $start     = trim(I('get.start'));
        $panterName = trim(I('get.panterName'));
        $company    = trim(I('get.company'));
        $invoicenum =trim(I('get.invoicenum'));
        $wheres = null;
        if($start =='') {
            $start = date('Ym01');
        }
        if($invoicenum !=''){
            $where['receiptnum'] = ['like',"%$invoicenum%"];
        }
        if($panterName != ''){
            $wheres["namechinese"] = ['like',"%$panterName%"];
        }
        if($company !=''){
            $wheres["nameenglish"] = ['like',"%$company%"];
        }
        $startTime = strtotime($start);
        $endTime   = strtotime("+1 month -1 day",$startTime);
        $where['uploaddate'] = [['egt',$startTime],['elt',$endTime]];
        $where['del']     = 0;
        $count = $this->model->table("receiptimg")
                                            ->where($where)->count();
        $Page = new Page($count,15);
        $Imgdata = $this->model->table("receiptimg")
            ->where($where)->limit($Page->firstRow.",".$Page->listRows)->select();
        if($wheres==null && !is_null($Imgdata) && !empty($Imgdata)){
             $wheres['namechinese'] = ['in',array_column($Imgdata,"operator")];
        }
        $PanterData = $this->model->table("panters")
                     ->where($wheres)->select();
        if(!is_null($Imgdata) || !empty($Imgdata)){
            foreach ($Imgdata as $key=>&$val){
                $g = 0;
                $val['data'] = stream_get_contents($val['data']);
                foreach ($PanterData as $v){
                    if($val['operator'] == $v['namechinese']){
                        $val['companys'] = $v['nameenglish'];
                        $g =1;
                    }
                }
                if(!$g && $wheres !=null){
                    unset($Imgdata[$key]);
                }
            }
        }

        $this->assign("data",$Imgdata);
        $this->assign("page",$Page->show2());
        $this->display();
    }







    public function  Excel()
    {
        $BaseService = new BaseService();
        $BaseService->Excel();
    }


    #累计收票金额
    public function  ReceiptTotalReceive()
    {
           $panterName = trim(I('get.panterName'));
           #获取每个单位开票记录
           $ReceiptImgModel         = new \Home\Model\ReceiptImg();
           #获取每个
            $where = null;
            if($panterName != ''){
                  $where = ['operator'=>['like',"%$panterName%"]];
            }
           $data =  $ReceiptImgModel->getSumByOperator($where);
           $this->assign("data",$data);

           $this->display();
    }



}