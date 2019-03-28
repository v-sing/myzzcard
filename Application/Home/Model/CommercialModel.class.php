<?php
namespace Home\Model;
use Think\Model;
class CommercialModel extends Model {
  //商户账户添加
   public function add($panterid,$warning=0,$type=0){
          $data = array($panterid,$warning,$type);
          $pa = M('panteraccount');
          $sql = "insert into panteraccount (panterid,waring,type)
                  values ('%s',%d,%d)";
          $re = $pa->execute($sql,$data);
          return $re;
   }

  //账户操作明细记录
  public function operate_record($info){
       $data = array($info['panterid']
                     ,$info['amount']
                     ,$info['cate']
                     ,$info['accountid']
                     ,$info['userid']
                     ,$info['cashdeposit']
                     ,$info['before_balance']
                     ,$info['after_balance']
                   );
       $sql = "insert into panter_account_operat (panterid,
                  amount,cate,accountid,userid,cashdeposit,before_balance,after_balance) values ('%s',%d,%d,%d,'%s',%d,%d,%d)";
                  // echo $sql;exit;
       $model = M()->execute($sql,$data);
       return $model;
  }
}
