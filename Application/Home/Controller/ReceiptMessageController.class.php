<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 9:47
 */

namespace Home\Controller;


use Service\BaseService;
use Service\Panters;
use Service\ReceiptImg;
use Service\TenantExtra;
use Service\UploadHandler;
use Think\Exception;

class ReceiptMessageController extends CoinController
{
      public function  index()
      {
            $pantername = I('get.pantername');
            $company    =  I('get.company');
            $where = null;
            if($pantername != ""){
                  $where['pantername'] = ['like',"%$pantername%"];
            }
            if($company != ''){
                 $where['company'] =  ['like',"%$company%"];
            }
             #初始化 商户额外参数提供商
             $PanterExtraService = new TenantExtra();
             $PanterExtraService->getInfo($where,20);
             $this->assign('data',$PanterExtraService->info);
             $this->assign("page",$PanterExtraService->page->show2());
             $this->display();
      }

      public function  addAction()
      {

            $data = I('post.');
            if(empty($data)){
                 $this->ajaxReturn([
                       'msg'   => '非法参数，失败',
                       'code'  => 10061
                 ]);
            }
          $tenantModel = new \Home\Model\TenantExtra();
           if(isset($data['id']) && $data['id'] != '' && !empty($data['id'])){

                     $tenantModel->where("tenantid = {$data['id']}")->save($data);
                       $this->ajaxReturn([
                           'code'  => 0,
                           "msg"   => '修改信息成功'
                       ]);

           }else{
          #初始化商户提供商
               unset($data['id']);
               $count = $tenantModel->where(["pantername"=>$data['pantername']])->count();
               if($count >0){
                   $this->ajaxReturn([
                       'code'  => 10061,
                       "msg"   => '抱歉,信息重复'
                   ]);
               }
           $panterService = new Panters();
            $data = $panterService->filterInput(null,$data);
            $data['tenantid'] = $this->getFieldNextNumber("tenantextra");
            $SQL           = $panterService->getInsertSql($data,'TENANTEXTRA');
            $res = $this->model->execute($SQL);
            $this->ajaxReturn([
                    'code'  => 0,
                    "msg"   => '添加信息成功'
              ]);
           }

      }

      #发票的图片信息
       public function  receiptImgMessage()
       {

           $start = I('get.start');
           if($start == ''){
                $start = date('Y-m-01');
           }else{
                $start = date('Y-m-01',strtotime($start));
           }
           $startTime = strtotime($start);
           $endTime   = strtotime('+1 month -1 days',$startTime);
           #初始化发票图片提供商

           if(session("username") == '' || is_null(session("username"))){
                    $this->redirect("/zzkp.php");
           }
           $ReceiptImgService = new ReceiptImg();
           $ReceiptImgService->getInfo(['del'=>0,'operation'=>session("username"),"uploaddate"=>[["egt",$startTime],['elt',$endTime]]],15);
           $this->assign("page",$ReceiptImgService->page->show2());
           $this->assign("data",$ReceiptImgService->info);
            $this->display();
       }

       #上传发票图片
      public function  upload()
      {
          error_reporting(E_ALL | E_STRICT);
          ini_set("post_max_size","20M");
          ini_set("upload_max_filesize ","15M");
          $operator = trim(I('post.operator'));
          if($operator == '' || is_numeric($operator)){
              $this->ajaxReturn([
                  "code"   => 10061,
                  "msg"    => "抱歉，上传失败，请填写正确的法人单位"
              ]);
          }
          $upload_handler = new UploadHandler();
          $str            = json_decode($upload_handler->str,true);
          $uploadID = $this->getFieldNextNumber("RECEIPTIMG");
          #识别图片内容
          $ImgPath = dirname(__DIR__).'/../../Public/'."receiptImg/thumbnail/".$str['files'][0]['name'];
          if(!is_file($ImgPath)){
              $this->ajaxReturn([
                  "code"   => 10061,
                  "msg"    => "抱歉，图片上传异常，请重试"
              ]);
          }
          $imgData = $this->revealReceipt($ImgPath);
          if(isset($imgData['error_code'])){
              $this->ajaxReturn([
                  "code"   => 10061,
                  "msg"    => "抱歉，发票识别失败！，请传入JPG | PNG 格式的发票，请重试"
              ]);
          }
          $imgData['data']['开票日期'] =  str_replace(["年","月","日"],["-","-",""],$imgData['data']['开票日期']);
          $imgData['data']['开票日期']  = date("Y-m-d",strtotime($imgData['data']['开票日期']));
          $time      = strtotime(date('Y-m-01',strtotime($imgData['data']['开票日期'])));
          $startTime = date('Ym01',strtotime('-1 month +1day',$time));
          $endTime   = date('Ymd',strtotime('-1 day',$time));
          try{

              $data       = $this->model->table("panters p")->field("sum(ce.amount) tmoney,max(p.namechinese) namechinese")
                  ->join("coin_consume ce  on p.panterid = ce.panterid","LEFT")
                  ->where(["p.namechinese"=>$operator,"CE.PLACEDDATE"=>[['egt',$startTime],['elt',$endTime]]])
                  ->select();
                if(is_null($data[0]['tmoney']) || empty($data[0]['tmoney'])){
                    $this->ajaxReturn([
                        "code"   => 10061,
                        "msg"    => "抱歉，发票识别失败！，请传入正确发票"
                    ]);
                }
               $actualMoney = $data[0]['tmoney'];
          }catch (Exception $e){
              $this->ajaxReturn([
                  "code"   => 10061,
                  "msg"    => "抱歉，发票识别失败！，请传入正确发票"
              ]);
          }
          $count = (new \Home\Model\ReceiptImg())->getCountInvoiceNum($imgData['data']['发票号码']);
          if($count >0){
              $this->ajaxReturn([
                  "code"   => 10061,
                  "msg"    => "抱歉，发票重复！，请传入正确发票"
              ]);
          }

          $data = [
                 "UPLOADDATE" => time(),
                 "IMG"        =>  "receiptImg/".$str['files'][0]['name'],
                 "status"     => 1,
                 "OPERATOR"   => $operator,
                 "OPERATION"  => session("username"),
                 "UPLOADID"   => $uploadID,
                 "DATA"    => json_encode($imgData['data']),
                 "MONEY"   =>  $imgData['data']['发票金额'],
                 "RECEIPTDATE" => $imgData['data']['开票日期'],
                 "RECEIPTCOMPANY" =>$imgData['data']['销售方名称'],
                 "ACTUALMONEY"=>$actualMoney,
                 "RECEIPTNUM"  => $imgData['data']['发票号码']
          ];
          $BaseService =  new BaseService();
          $data = $BaseService->filterInput(null,$data);
          $sql  = $BaseService->getInsertSql($data,"RECEIPTIMG");
          try{
              if($this->model->execute($sql)){
                  $this->ajaxReturn([
                      "code"   => 0,
                      "msg"    => "上传成功",
                      "data"   => [
                            "time"  => date('Y-m-d'),
                            "img"  =>  "receiptImg/".$str['files'][0]['name'],
                            "status" =>1,
                            "operator" => $operator,
                            "uploadid" => $uploadID,
                            "receiptdate" => $imgData['data']['开票日期'],
                            "receiptmoney"  =>$imgData['data']['发票金额']
                      ]
                  ]);
              }else{
                  $this->ajaxReturn([
                      "code"   => 10061,
                      "msg"    => "抱歉，上传失败，请刷新页面重试"
                  ]);
              }
          }catch (Exception $e){
               $this->ajaxReturn([
                     "code"   => 10061,
                     "msg"    => "抱歉，上传失败，请重试"
               ]);
          }
      }

      #删除发票内容
      public function  del()
      {
          $uploadid = I("post.uploadid");
             if($uploadid == '' || !is_numeric($uploadid) || is_null($uploadid)){
                 $this->ajaxReturn([
                     "code"   => 10061,
                     "msg"    => "抱歉，参数有误"
                 ]);
             };
             #初始化发票图片提供商
             $ReceiptImgService = new ReceiptImg();
             $ReceiptImgService->del(["uploadid" => $uploadid]);
          $this->ajaxReturn([
              "code"   => 0,
              "msg"    => "删除成功"
          ]);
      }


      #识别发票文字
      public function  revealReceipt($file)
      {
             $BaseService = new BaseService();
              $url = "https://ocrapi-invoice.taobao.com/ocrservice/invoice";
              $appcode = "7a7092cc2a384917abbae7ceb08c6cb7";
              $headers = array();
              array_push($headers, "Authorization:APPCODE " . $appcode);
              //根据API的要求，定义相对应的Content-Type
              array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
              $data = json_encode(['img'=>$BaseService->base64EncodeImage($file)]);
              $data = $BaseService->curlRequest($url,$headers,$data);
              return $data;

      }
    #导出Ex'ce'l
    public function  Excel()
    {
      $BaseService = new BaseService();
      $BaseService->Excel();
    }
    #导入Ex'ce'l
    public function  UploadExcel()
    {
        #初始化基础提供商
        $BaseService = new BaseService();
        $that=$this;
        $BaseService->excelExport("TenantExtra",function (&$v,$data) use ($BaseService,$that){
              $tenantid = $that->getFieldNextNumber("tenantextra");
              $v['tenantid'] = $tenantid;
              return $v;
        });
        $this->ajaxReturn([
              "msg"  => "成功"
        ]);
    }


}