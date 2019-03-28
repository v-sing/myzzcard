<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class  OldcardController extends CommonController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    //售卡报表
    public function sellcard()
    {
      $cards=M('Old_purchase_logs');
      $start = trim(I('start',''));
      $end = trim(I('end',''));
      $cstart = trim(I('cstart',''));
      $cend = trim(I('cend',''));
      $cleardatestart = str_replace('-','',$cstart);
      $cleardateend = str_replace('-','',$cend);
      $tradedatestart =  str_replace('-','',$start);
      $tradedateend = str_replace('-','',$end);
      $cleardate = trim(I('ttradedateend',''));
      $tradetype = trim(I('tradetype',''));
      // dump($tradetype);
      if($tradedatestart!='' && $tradedateend=='')
      {
        $where['tradedate'] = array('egt',$tradedatestart);
        $this->assign("start",$start);
        $map['start'] = $start;
      }
      if($tradedateend!='' && $tradedatestart=='')
      {
        $where['tradedate'] = array('elt',$tradedateend);
        $this->assign("end",$end);
        $map['end']= $end;
      }
      if($tradedatestart!='' && $tradedateend!='')
      {
        $where['tradedate'] = array(array('egt',$tradedatestart),array('elt', $tradedateend));
        $this->assign("end",$end);
        $this->assign("start",$start);
        $map['end'] =$end;
        $map['start'] = $start;
      }
      if($tradetype!='')
      {
        $where['tradetype'] = $tradetype;
        $map['tradetype'] = $tradetype;
        $this->assign('tradetype',$tradetype);
      }
      if($cleardatestart!='' && $cleardateend=='')
      {
        $where['cleardate'] = array('egt',$cleardatestart);
        $this->assign("cstart",$cstart);
        $map['cstart'] = $cstart;
      }
      if($cleardateend!='' && $cleardatestart=='')
      {
        $where['cleardate'] = array('elt',$cleardatecend);
        $this->assign("cend",$cend);
        $map['cend']= $cend;
      }
      if($cleardatestart!='' && $cleardateend!='')
      {
        $where['cleardate'] = array(array('egt',$cleardatestart),array('elt', $cleardateend));
        $this->assign("cend",$cend);
        $this->assign("cstart",$cstart);
        $map['cend'] =$cend;
        $map['cstart'] = $cstart;
      }
      // dump($where);
      $count = $cards->where($where)->count();
      $p = new \Think\Page($count, 10);
      if(!empty($map)){
              foreach($map as $key=>$val) {
                  $p->parameter[$key]= $val;
              }
          }
      $lists = $cards->where($where)->limit($p->firstRow.','.$p->listRows)->select();
      $show = $p->show();
      $this->assign('lists',$lists);
      $this->assign('count',$count);
      $this->assign('show',$show);
      $this->display();
    }
    public function cardbatchbuy()
    {
      set_time_limit(0);
      ini_set('memory_limit', '-1');
      if (!empty( $_FILES['file_stu']['name']))
      {
          $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
          $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
          $file_type = $file_types [count ( $file_types ) - 1];
          /*判别是不是.xls文件，判别是不是excel文件*/
          if (strtolower ( $file_type ) != "xls")
          {
             $this->error ( '不是Excel文件，重新上传' );
          }
          /*设置上传路径*/
          $savePath = './Public/upfile/Excel/';
          /*以时间来命名上传的文件*/
          $str = date ( 'Ymdhis' );
          $file_name = $str . "." . $file_type;
          /*是否上传成功*/
          if (!copy($tmp_file,$savePath.$file_name ))
          {
              $this->error('上传失败');
          }
          $exceldate=$this->import_excel($savePath.$file_name);
          $cards=M('Old_purchase_logs');
          // $gkid=$this->getnextcode('GKID',8);//获得GKID 8位编号
          $counts=0;
          $batchbuyBatchLog=array();
          $err = false;
          $ksnum=1;
          $cards->startTrans();
          foreach ($exceldate as $key => $value)
          {
              if($ksnum==300){
                  $ksnum=1;
                  sleep(1);
              }
              $ksnum++;
             $batchbuyBatchLog[$key]['purchaseid']= $purchaseid=$value[0];
             $batchbuyBatchLog[$key]['cardissuer']= $cardissuer=$value[1];
             $batchbuyBatchLog[$key]['integralclub']= $integralclub=$value[2];
             $batchbuyBatchLog[$key]['panterid']= $panterid=$value[3];
             $batchbuyBatchLog[$key]['tradeid']= $tradeid=$value[4];
             $batchbuyBatchLog[$key]['tradetype']= $tradetype=$value[5];
             $batchbuyBatchLog[$key]['cardno']= $cardno=$value[6];
             $batchbuyBatchLog[$key]['endcardno'] =$endcardno=$value[7];
             $batchbuyBatchLog[$key]['count']= $count=$value[8];
             $batchbuyBatchLog[$key]['amount']= $amount=$value[9];
             $batchbuyBatchLog[$key]['cleardate']= $cleardate=$value[10];
             $batchbuyBatchLog[$key]['tradedate']= $tradedate=$value[11];
             $batchbuyBatchLog[$key]['tradetime']= $tradetime=$value[12];
             $batchbuyBatchLog[$key]['operator']= $operator=$value[13];
             $batchbuyBatchLog[$key]['operatetime']= $operatetime=$value[14];
             $batchbuyBatchLog[$key]['checker']= $checker=$value[15];
             $batchbuyBatchLog[$key]['checktime']= $checktime=$value[16];
              $sql = "INSERT INTO old_purchase_logs (purchaseid,cardissuer,integralclub,panterid,tradeid,";
              $sql.= "tradetype,cardno,endcardno,count,amount,cleardate,tradedate,tradetime,operator,operatetime,checker,checktime) VALUES ('";
              $sql.= $purchaseid."','".$cardissuer."','".$integralclub."','".$panterid."','".$tradeid."','".$tradetype."','".$cardno."','";
              $sql.= $endcardno."','".$count."','".$amount."','".$cleardate."','".$tradedate."','".$tradetime."','".$operator."','".$operatetime."','".$checker."','".$checktime."')";
              $tempos=$cards->execute($sql);
              if($tempos==false)
              {
                $cards->rollback();
              }

          }
          $cards->commit();
      }
    $this->display();
    }
    public function cardconsum()
    {
      set_time_limit(0);
      ini_set('memory_limit', '-1');
      if (!empty( $_FILES['file_stu']['name']))
      {
          $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
          $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
          $file_type = $file_types [count ( $file_types ) - 1];
          /*判别是不是.xls文件，判别是不是excel文件*/
          if (strtolower ( $file_type ) != "xls")
          {
             $this->error ( '不是Excel文件，重新上传' );
          }
          /*设置上传路径*/
          $savePath = './Public/upfile/Excel/';
          /*以时间来命名上传的文件*/
          $str = date ( 'Ymdhis' );
          $file_name = $str . "." . $file_type;
          /*是否上传成功*/
          if (!copy($tmp_file,$savePath.$file_name ))
          {
              $this->error('上传失败');
          }

          $exceldate=$this->import_excel($savePath.$file_name);
          $cards=M('Old_consum_logs');
          // $gkid=$this->getnextcode('GKID',8);//获得GKID 8位编号
          $counts=0;
          $batchbuyBatchLog=array();
          $err = false;
          $ksnum=1;
          $cards->startTrans();
          foreach ($exceldate as $key => $value)
          {
              if($ksnum==300){
                  $ksnum=1;
                  sleep(1);
              }
              $ksnum++;
             $batchbuyBatchLog[$key]['consumid']= $consumid=$value[0];
             $batchbuyBatchLog[$key]['integralclub']= $integralclub=$value[1];
             $batchbuyBatchLog[$key]['tradeid']= $tradeid=$value[2];
             $batchbuyBatchLog[$key]['cardno'] =$cardno=$value[3];
             $batchbuyBatchLog[$key]['tradetype']= $tradetype=$value[4];
             $batchbuyBatchLog[$key]['comid']= $comid=$value[5];
             $batchbuyBatchLog[$key]['panterid']= $panterid=$value[6];
             $batchbuyBatchLog[$key]['terminalid']= $terminalid=$value[7];
             $batchbuyBatchLog[$key]['tradeno']= $tradeno=$value[8];
             $batchbuyBatchLog[$key]['tradedate']= $tradedate=$value[9];
             $batchbuyBatchLog[$key]['tradetime']= $tradetime=$value[10];
             $batchbuyBatchLog[$key]['cleardate']= $cleardate=$value[11];
             $batchbuyBatchLog[$key]['operator']= $operator=$value[12];
             $batchbuyBatchLog[$key]['checker']= $checker=$value[13];
             $batchbuyBatchLog[$key]['amount']= $amount=$value[14];

              $sql = "INSERT INTO old_consum_logs (consumid,integralclub,tradeid,cardno,tradetype,";
              $sql.= "comid,panterid,terminalid,tradeno,tradedate,tradetime,cleardate,operator,checker,amount) VALUES ('";
              $sql.= $consumid."','".$integralclub."','".$tradeid."','".$cardno."','".$tradetype."','".$comid."','".$panterid."','";
              $sql.= $terminalid."','".$tradeno."','".$tradedate."','".$tradetime."','".$cleardate."','".$operator."','".$checker."','".$amount."')";
              $tempos=$cards->execute($sql);
              $arr=array();
              if($tempos==false)
              {
                $cards->rollback();
                $arr[$key]['msg'] = '卡号'.$cardno.'有异常,请联系系统管理员! sql:' .$cards->getLastSql();
                continue;
              }


          }

       $cards->commit();
      }
    $this->display();
    }
    //消费报表
    public function consum()
    {
      $cards=M('Old_consum_logs');
      $start = trim(I('start',''));
      $end = trim(I('end',''));
      $cstart = trim(I('cstart',''));
      $cend = trim(I('cend',''));
      $cardno = trim(I('cardno',''));
      $cleardatestart = str_replace('-','',$cstart);
      $cleardateend = str_replace('-','',$cend);
      $tradedatestart =  str_replace('-','',$start);
      $tradedateend = str_replace('-','',$end);
      $cleardate = trim(I('ttradedateend',''));
      $tradetype = trim(I('tradetype',''));
      // dump($tradetype);
      if($cardno!='')
      {
        $where['cardno'] = $cardno;
        $this->assign("cardno",$cardno);
        $map['cardno'] = $cardno;
      }
      if($tradedatestart!='' && $tradedateend=='')
      {
        $where['tradedate'] = array('egt',$tradedatestart);
        $this->assign("start",$start);
        $map['start'] = $start;
      }
      if($tradedateend!='' && $tradedatestart=='')
      {
        $where['tradedate'] = array('elt',$tradedateend);
        $this->assign("end",$end);
        $map['end']= $end;
      }
      if($tradedatestart!='' && $tradedateend!='')
      {
        $where['tradedate'] = array(array('egt',$tradedatestart),array('elt', $tradedateend));
        $this->assign("end",$end);
        $this->assign("start",$start);
        $map['end'] =$end;
        $map['start'] = $start;
      }
      if($tradetype!='')
      {
        $where['tradetype'] = $tradetype;
        $map['tradetype'] = $tradetype;
        $this->assign('tradetype',$tradetype);
      }
      if($cleardatestart!='' && $cleardateend=='')
      {
        $where['cleardate'] = array('egt',$cleardatestart);
        $this->assign("cstart",$cstart);
        $map['cstart'] = $cstart;
      }
      if($cleardateend!='' && $cleardatestart=='')
      {
        $where['cleardate'] = array('elt',$cleardatecend);
        $this->assign("cend",$cend);
        $map['cend']= $cend;
      }
      if($cleardatestart!='' && $cleardateend!='')
      {
        $where['cleardate'] = array(array('egt',$cleardatestart),array('elt', $cleardateend));
        $this->assign("cend",$cend);
        $this->assign("cstart",$cstart);
        $map['cend'] =$cend;
        $map['cstart'] = $cstart;
      }
      // dump($where);
      $count = $cards->where($where)->count();
      $p = new \Think\Page($count, 10);
      if(!empty($map)){
              foreach($map as $key=>$val) {
                  $p->parameter[$key]= $val;
              }
          }
      $lists = $cards->where($where)->limit($p->firstRow.','.$p->listRows)->select();
      $show = $p->show();
      $this->assign('lists',$lists);
      $this->assign('count',$count);
      $this->assign('show',$show);
      $this->display();
    }
  //导入卡余额excel
    public function importremain()
    {
      set_time_limit(0);
      ini_set('memory_limit', '-1');
      if (!empty( $_FILES['file_stu']['name']))
      {
          $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
          $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
          $file_type = $file_types [count ( $file_types ) - 1];
          /*判别是不是.xls文件，判别是不是excel文件*/
          if (strtolower ( $file_type ) != "xls")
          {
             $this->error ( '不是Excel文件，重新上传' );
          }
          /*设置上传路径*/
          $savePath = './Public/upfile/Excel/';
          /*以时间来命名上传的文件*/
          $str = date ( 'Ymdhis' );
          $file_name = $str . "." . $file_type;
          /*是否上传成功*/
          if (!copy($tmp_file,$savePath.$file_name ))
          {
              $this->error('上传失败');
          }

          $exceldate=$this->import_excel($savePath.$file_name);
          // dump($exceldate);exit;
          $cards=M('Old_card_remain');
          $batchbuyBatchLog=array();
          $ksnum=1;
          $cards->startTrans();
          foreach ($exceldate as $key => $value)
          {
              if($ksnum==300){
                  $ksnum=1;
                  sleep(1);
              }
              $ksnum++;
             $batchbuyBatchLog[$key]['cardno']= $cardno=trim($value[0]);
             $batchbuyBatchLog[$key]['remain_amount']= $remain_amount=$value[1];
              $sql = "INSERT INTO old_card_remain (cardno,remain_amount) VALUES ('";
              $sql.= $cardno."','".$remain_amount."')";
              $tempos=$cards->execute($sql);
              $arr=array();
              if($tempos==false)
              {
                $cards->rollback();

              }


          }
       $cards->commit();
      }
    $this->display();
    }
//卡余额
   public function remain()
   {
     $cards=M('Old_card_remain');
     $cardno = trim(I('cardno',''));
     $small = trim(I('small',''));
     $big = trim(I('big',''));
     if($small!='' && $big=='')
     {
       $where['remain_amount'] = array('egt',$small);
       $map['small'] = $small;
       $this->assign('small',$small);
     }
     if($small=='' && $big!='')
     {
       $where['remain_amount'] = array('elt',$big);
       $map['big'] = $big;
       $this->assign('big',$big);
     }
     if($small!='' && $big!='')
     {
       $where['remain_amount'] = array(array('egt',$small),array('elt',$big));
       $map['big'] = $big;
       $map['small'] = $small;
       $this->assign('big',$big);
       $this->assign('small',$small);
     }
     // dump($tradetype);
     if($cardno!='')
     {
       $where['cardno'] = $cardno;
       $this->assign("cardno",$cardno);
       $map['cardno'] = $cardno;
     }
     // dump($where);
     $count = $cards->where($where)->count();
     $p = new \Think\Page($count, 10);
     if(!empty($map)){
             foreach($map as $key=>$val) {
                 $p->parameter[$key]= $val;
             }
         }
     $lists = $cards->where($where)->limit($p->firstRow.','.$p->listRows)->select();
     $show = $p->show();
     $this->assign('lists',$lists);
     $this->assign('count',$count);
     $this->assign('show',$show);
     $this->display();
   }
   //会员excel导入
   public function importcustom()
   {
     set_time_limit(0);
     ini_set('memory_limit', '-1');
     if (!empty( $_FILES['file_stu']['name']))
     {
         $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
         $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
         $file_type = $file_types [count ( $file_types ) - 1];
         /*判别是不是.xls文件，判别是不是excel文件*/
         if (strtolower ( $file_type ) != "xls")
         {
            $this->error ( '不是Excel文件，重新上传' );
         }
         /*设置上传路径*/
         $savePath = './Public/upfile/Excel/';
         /*以时间来命名上传的文件*/
         $str = date ( 'Ymdhis' );
         $file_name = $str . "." . $file_type;
         /*是否上传成功*/
         if (!copy($tmp_file,$savePath.$file_name ))
         {
             $this->error('上传失败');
         }

         $exceldate=$this->import_excel($savePath.$file_name);
         // dump($exceldate);exit;
         $cards=M('Old_customer');
         $batchbuyBatchLog=array();
         $ksnum=1;
         $cards->startTrans();
         foreach ($exceldate as $key => $value)
         {
             if($ksnum==300){
                 $ksnum=1;
                 sleep(1);
             }
             $ksnum++;
            $batchbuyBatchLog[$key]['unitid']= $unitid=trim($value[0]);
            $batchbuyBatchLog[$key]['unitname']= $unitname=trim($value[1]);
            $batchbuyBatchLog[$key]['cardno']= $cardno=trim($value[2]);
            $batchbuyBatchLog[$key]['status']= $status=trim($value[3]);
            $batchbuyBatchLog[$key]['customname']= $customname=trim($value[4]);
            $batchbuyBatchLog[$key]['personid']= $personid=trim($value[5]);
            $batchbuyBatchLog[$key]['sex']= $sex=trim($value[6]);
            $batchbuyBatchLog[$key]['birthday']= $birthday=trim($value[7]);
            $batchbuyBatchLog[$key]['homeid']= $homeid=trim($value[8]);
            $batchbuyBatchLog[$key]['homephone']= $homephone=trim($value[9]);
            $batchbuyBatchLog[$key]['workid']= $workid=trim($value[10]);
            $batchbuyBatchLog[$key]['workphone']= $workphone=trim($value[11]);
            $batchbuyBatchLog[$key]['faxid']= $faxid=trim($value[12]);
            $batchbuyBatchLog[$key]['faxphone']= $faxphone=trim($value[13]);
            $batchbuyBatchLog[$key]['phone']= $phone=trim($value[14]);
            $batchbuyBatchLog[$key]['email']= $email=trim($value[15]);
            $batchbuyBatchLog[$key]['homepost']= $homepost=trim($value[16]);
            $batchbuyBatchLog[$key]['homeaddress']= $homeaddress=trim($value[17]);
            $batchbuyBatchLog[$key]['workpost']= $workpost=trim($value[18]);
            $batchbuyBatchLog[$key]['wordaddress']= $wordaddress=trim($value[19]);
            $batchbuyBatchLog[$key]['description']= $description=trim($value[20]);
            $sql = "INSERT INTO Old_customer (unitid,unitname,cardno,status,customname,";
            $sql.= "personid,sex,birthday,homeid,homephone,faxid,faxphone,phone,email,homepost,homeaddress,workpost,wordaddress,description) VALUES ('";
            $sql.= $unitid."','".$unitname."','".$cardno."','".$status."','".$customname."','".$personid."','".$sex."','";
            $sql.= $birthday."','".$homeid."','".$homephone."','".$faxid."','".$faxphone."','".$phone."','".$email."','".$homepost."','".$homeaddress."','".$workpost."','".$wordaddress."','".$description."')";
            $tempos=$cards->execute($sql);
             if($tempos==false)
             {
               $cards->rollback();

             }


         }
      $cards->commit();
     }
   $this->display();
   }
   //会员报表
   public function custom()
   {
     $cards=M('Old_customer');
     $cardno = trim(I('cardno',''));
     $phone = trim(I('phone',''));
     $customname = trim(I('customname',''));
     $personid = trim(I('personid',''));
     $sex = trim(I('sex',''));
     if($cardno!='')
     {
       $where['ca.cardno'] = $cardno;
       $this->assign("cardno",$cardno);
       $map['cardno'] = $cardno;
     }
     if($phone!='')
     {
       $where['cu.phone'] = $phone;
       $map['phone'] = $phone;
       $this->assign('phone',$phone);
     }
     if($customname!='')
     {
       $where['cu.customname'] = $customname;
       $map['customname'] = $customname;
       $this->assign('customname',$customname);
     }
     if($personid!='')
     {
       $where['cu.personid'] = $personid;
       $map['personid'] = $personid;
       $this->assign('personid',$personid);
     }
     if($sex!='')
     {
       $where['cu.sex'] = $sex;
       $map['sex'] = $sex;
       $this->assign('sex',$sex);
     }
     // dump($tradetype);
     // dump($where);
     $field ='cu.customname,cu.cardno,cu.phone,cu.personid,cu.birthday,cu.sex,cu.homeaddress,cu.description,ca.remain_amount';
    //  $count = $cards->where($where)->count();
     $count = $cards->alias('cu')->JOIN('left join __OLD_CARD_REMAIN__ ca on ca.cardno = cu.cardno')
                                  ->where($where)->count();
     $p = new \Think\Page($count, 10);
     if(!empty($map)){
             foreach($map as $key=>$val) {
                 $p->parameter[$key]= $val;
             }
         }
    //  $lists = $cards->where($where)->limit($p->firstRow.','.$p->listRows)->select();
     $lists = $cards->alias('cu')->JOIN('left join __OLD_CARD_REMAIN__ ca on ca.cardno = cu.cardno')
                                  ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
     $show = $p->show();
     $this->assign('lists',$lists);
     $this->assign('count',$count);
     $this->assign('show',$show);
     $this->display();
   }
}
?>
