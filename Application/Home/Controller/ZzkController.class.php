<?php
namespace Home\Controller;
use Think\Controller;
use Org\Util\DESedeCoder;
use Think\Model;
class ZzKController extends CoinController{
  private $users;
  private $panters;
  private $codeKey;
  private $editKey;
  private $pnameKey;
  private $pwdKey;
  private $consumeCode;
  private $trade_wastebooks;
  private $dayTradeCode;
  private $oneExcelCode;
  //一家商户同步秘钥
  private $zzkKey;
  protected $DESedeCoder;
  public function _initialize(){
    // $this->checkLogin();
    $this->users = M('users');
    $this->panters = M('panters');
    //官网后台登录 接口 数字签名
    $this->codeKey = 'zzadminlogin';
    //官网后台 商户编辑信息 接口 数字签名
    $this->editKey =md5('panteredit'.date('Ymd',time()));
    //官网后台 商户名查询 接口 数字签名
    $this->pnameKey=md5('panteridxyz'.date('Ymd',time()));
      //官网后台 商户名修改登录密码 接口 数字签名
    $this->pwdKey=md5('modifypwd'.date('Ymd',time()));
    $this->consumeCode=md5('todayconsume'.date('Ymd',time()));
    //官网导出某天卡系统商户每天交易总额 数字签名
    $this->dayTradeCode=md5('getDayTrade'.date('Ymd',time()));
    //官网导出某天卡系统商户交易excel 数字签名
    $this->oneExcelCode=md5('oneExcel'.date('Ymd',time()));
    $this->zzkKey=md5('zzkpanter'.date('Ymd',time()));
    $this->DESedeCoder=new  DESedeCoder();
    $this->trade_wastebooks=M('trade_wastebooks');
  }
  private function receviteData(){
    $data=getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if($datami == false){
        returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami,1);
    return $datami;
  }
public function login(){
     $data = getPostJson();
     $datami = trim($data['datami']);
     $datami = $this->DESedeCoder->decrypt($datami);
     if($datami == false){
         returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
     }
     $this->recordData($datami);
     $datami = json_decode($datami,1);
     $loginname = $datami['loginname'];
     $pwd = $datami['password'];
     $token = $datami['_token'];
     $key = $datami['key'];
     $checkKey = md5($loginname.$pwd.$token.$this->codeKey);
     if($checkKey!=$key){
        returnMsg(array('status'=>'01','msg'=>'非法秘钥！'));
     }
     $user  = $this->users->where(array('loginname'=>$loginname))->find();
     $user==true ||  returnMsg(array('status'=>'02','msg'=>'账号不存在'));
     if($user['wrpass']>=3) returnMsg(array('status'=>'03','msg'=>'账号被锁定!请联系管理员'));
     if($user['password1']!=$pwd){
       $error['wrpass']=$user['wrpass']+1;
       $this->users->where(array('loginname'=>$loginname))->save($error);
       returnMsg(array('status'=>'03','msg'=>'密码错误'));
     }else{
       $error['wrpass']=0;
       $this->users->where(array('loginname'=>$loginname))->save($error);
     }
       //
       $panter = $this->panters->where(array('panterid'=>$user['panterid']))->find();
       $panter==true || returnMsg(array('status'=>'05','msg'=>'商户不存在'));
       $panter['revorkflg']== N||returnMsg(array('status'=>'06','msg'=>'商户已经禁用'));
       $res = array(
           'name'=>$user['username'],
           'panterid'=>$panter['panterid'],
           'namechinese'=>$panter['namechinese'],
           'address'=>$panter['address'],
           'operatescope'=>$panter['operatescope'],
           'organizationcode'=>$panter['organizationcode'],
           'business'=>$panter['business'],
           'timevalue'=>$panter['timevalue'],
           'taxation'=>$panter['taxation'],
           'nameenglish'=>$panter['nameenglish'],
           //联系人信息
           'legalperson'=>$panter['legalperson'],
           'conperbtype'=>$panter['conperbtype'],
           'conperbpno'=>$panter['conperbpno'],
           'period'=>$panter['period'],
           'conpermobno'=>$panter['conpermobno'],
           'conpername'=>$panter['conpername'],
           'conperteleno'=>$panter['conperteleno'],
           //结算账户
           'settlementperiod'=>$panter['settlementperiod'],
           'settleaccountname'=>$panter['settleaccountname'],
           'settlebank'=>$panter['settlebank'],
           'settlebankname'=>$panter['settlebankname'],
           'settlebankid'=>$panter['settlebankid'],
           'conpername'=>$panter['conpername'],
           'conperteleno'=>$panter['conperteleno'],
       );
       returnMsg(array('status'=>'1','msg'=>'登录成功！','data'=>$res));
     }
     //商户信息修改
     public function panterEdit(){
       $data = getPostJson();
       $datami = trim($data['datami']);
       $datami = $this->DESedeCoder->decrypt($datami);
       if($datami == false){
           returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
       }
       $this->recordData($datami);
       $datami = json_decode($datami,1);
       $panterid = trim($datami['panterid']);
       $editDate = $datami['edit'];
       $_token = trim($datami['_token']);
       $key = trim($datami['key']);
       $str = $this->strArr($editDate);
       $checkKey = MD5($str.$panterid.$_token.$this->editKey);
       if($key!=$checkKey) returnMsg(array('status'=>'01','msg'=>'非法密钥'));
       $panter = $this->panters->where(array('panterid'=>$panterid))->find();
       $panter==true || returnMsg(array('status'=>'02','msg'=>'未找到该商户'));
       $panter['revorkflg']== N||returnMsg(array('status'=>'06','msg'=>'商户已经禁用'));
       $res = $this->panters->where(array('panterid'=>$panterid))->save($editDate);
       $res==true||returnMsg(array('status'=>'01','msg'=>'网路超时,商户修改失败'));
       returnMsg(array('status'=>'1','msg'=>'商户修成功！'));
     }
     protected  function  strArr($arr){
       $str="";
        foreach($arr as $key=>$val){
            $str.=$val;
        }
        return $str;
    }
    //商户名查询
    public function queryPname(){
      $data = getPostJson();
      $datami = trim($data['datami']);
      $datami = $this->DESedeCoder->decrypt($datami);
      if($datami == false){
          returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
      }
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $panterid = trim($datami['panterid']);
      $key = trim($datami['key']);
      $checkKey = md5($panterid.$this->pnameKey);
      if($key!=$checkKey) returnMsg(array('status'=>'01','msg'=>'非法密钥'));
      $panterid=decode($panterid);
      $panter =$this->panters->where(array('panterid'=>$panterid))->find();
      $panter==true || returnMsg(array('status'=>'02','msg'=>'非法商户号'));
      $panter['revorkflg']== N||returnMsg(array('status'=>'03','msg'=>'商户已经禁用'));
      $panter['namechinese']==true || returnMsg(array('status'=>'05','msg'=>'商户名为空！'));
      returnMsg(array('status'=>'1','pname'=>$panter['namechinese']));
    }
    //官网后台修改密码
    public function modifyPwd(){
      $data = getPostJson();
      $datami = trim($data['datami']);
      $datami = $this->DESedeCoder->decrypt($datami);
      if($datami == false){
          returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
      }
      $this->recordData($datami);
      $datami = json_decode($datami,1);
      $loginname = $datami['loginname'];
      $pwd = $datami['pwd'];
      $new = $datami['new'];
      $token = $datami['_token'];
      $key = $datami['key'];
      $checkKey = md5($loginname.$pwd.$new.$token.$this->pwdKey);
      if($checkKey!=$key){
         returnMsg(array('status'=>'01','msg'=>'非法秘钥！'));
      }
      $user  = $this->users->where(array('loginname'=>$loginname))->find();
      $user==true ||  returnMsg(array('status'=>'02','msg'=>'账号不存在'));
      if($user['wrpass']>=3) returnMsg(array('status'=>'03','msg'=>'账号被锁定!请联系管理员'));
      if($user['password1']!=$pwd){
        $error['wrpass']=$user['wrpass']+1;
        $this->users->where(array('loginname'=>$loginname))->save($error);
        returnMsg(array('status'=>'03','msg'=>'原密码错误'));
      }else{
        $res = $this->users->where(array('loginname'=>$loginname))->save(array('password1'=>$new));
        if($res){
            returnMsg(array('status'=>'1','msg'=>'密码修改成功'));
        }else{
            returnMsg(array('status'=>'04','msg'=>'数据库操作失败'));
        }
      }

    }
//    public function consume(){
//      $data=getPostJson();
//      $datami = trim($data['datami']);
//      $datami = $this->DESedeCoder->decrypt($datami);
//      if($datami == false){
//          returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
//      }
//      $this->recordData($datami);
//
//      $panterid = $datami['panterid'];
//      $_token = $datami['_token'];
//      $key = $datami['key'];
//      $checkKey=md5($panterid.$_token.$this->consumeCode);
//      if($checkKey!=$key){
//         returnMsg(array('status'=>'01','msg'=>'非法秘钥！'));
//      }
//      $panterid=decode($panterid);
//      $panterid='00000126';
//      $panter=$this->panters->where(array('panterid'=>$panterid))->find();
//      $panter==true || returnMsg(array('status'=>'03','msg'=>'商户号不存在'));
//      $panter['revorkflg']=='N' ||returnMsg(array('status'=>'02','msg'=>'非法商户号'));
//      $date=date('Ymd',time());
//      $date='20160405';
//      $where=array('placeddate'=>$date,'panterid'=>$panterid,'tradetype'=>array('in','00,13,21'),'flag'=>'0');
//      $lists=M('trade_wastebooks')->where($where)->field('sum(tradeamount) amount,panterid,termposno')->group('termposno,panterid')->select();
//      $lists==true ||returnMsg(array('status'=>'05','msg'=>'未查到交易数据'));
//      returnMsg(array('status'=>'1','data'=>json_encode($lists)));
//      }
      //定时由支付结算中心 查询每日卡商户系统交易金额
//      public function dayTrade(){
//        $data=getPostJson();
//        $datami = trim($data['datami']);
//        $datami = $this->DESedeCoder->decrypt($datami);
//        if($datami == false){
//            returnMsg(array('status'=>'04','msg'=>'非法数据传入'));
//        }
//        $this->recordData($datami);
//        $datami = json_decode($datami,1);
//        $date=$datami['date'];
//        $key = $datami['key'];
//        $checkKey=md5($date.$_token.$this->dayTradeCode);
//        if($checkKey!=$key){
//           returnMsg(array('status'=>'01','msg'=>'非法秘钥！'));
//        }
//        $where=['tr.placeddate'=>$date,'tr.tradetype'=>['in','00,13,21'],'tr.flag'=>0];
//        $res=$this->trade_wastebooks->alias('tr')
//                  ->join('left join __PANTERS__ p on p.panterid=tr.panterid')
//                  ->where($where)->field('sum(tr.tradeamount) amount,tr.panterid,p.namechinese')->group('tr.panterid,p.namechinese')->select();
//        if($res==true){
//          returnMsg(['status'=>'1','data'=>$res]);
//        }else{
//          returnMsg(['status'=>'03','msg'=>'未查询到订单,请管理核实信息']);
//        }
//      }
      //支付结算中心 查询商户交易具体每天的交易订单用于导出excel
//      public function queryOneDayTrade(){
//        $datami=$this->receviteData();
//        $panterid=$datami['panterid'];
//        $date=$datami['date'];
//        $_token=$datami['_token'];
//        $key = $datami['key'];
//        $checkKey=md5($panterid.$date.$_token.$this->oneExcelCode);
//        if($checkKey!=$key){
//           returnMsg(array('status'=>'01','msg'=>'非法秘钥！'));
//        }
//        $panterid=base64_decode($panterid);
//        $panter=$this->panters->where(['panterid'=>$panterid])->find();
//        $panter==true || returnMsg(['status'=>'03','msg'=>'未查询到该商户']);
//        $panter['revorkflg']=='N' || returnMsg(['status'=>'02','msg'=>'该商户已经被禁用']);
//        $lists = $this->trade_wastebooks
//                  ->where(['panterid'=>$panterid,'tradetype'=>['in','00,13,21'],'flag'=>0,'placeddate'=>$date])
//                  ->field('panterid,tradeamount,tradeid,placeddate,placedtime')
//                  ->select();
//        if($lists==true){
//          returnMsg(['status'=>'1','data'=>$lists,'namechinese'=>$panter['namechinese']]);
//        }else{
//        }
//      }
      //同步商户信息e+
      protected function getKey1($data,$index=[]){
       $str='';
       if(empty($index)){
          foreach ($data as $key => $val) {
            if($key!='key'){
               $str.=$data[$key];
            }
          }
       }else{
         foreach($index as $key=>$val){
             $str.=$data[$val];
         }
       }
       return md5($str.$this->zzkKey);
   }
      public function addPanter(){
        $datami=$this->receviteData();
        if(isset($datami['orzimg'])){
             $index=['pname','name','padress','scope','business','timevalue',
                 'licenseimg','orzimg','taximg','doorimg', 'legalperson','pid','idface','idcon','phone',
                 'conpername','conperteleno','accoutname','bank','bankname','bankno','settleperiod',
             ];
         }else{
             $index=['pname','name','padress','scope','business','timevalue',
                 'licenseimg','doorimg', 'legalperson','pid','idface','idcon','phone',
                 'conpername','conperteleno','accoutname','bank','bankname','bankno','settleperiod',
             ];
         }
        $checkKey=$this->getKey1($datami,$index);
        if($checkKey!=$datami['key']){
           returnMsg(array('status'=>'03','msg'=>'同步商户时,非法秘钥！'));
        }
        $namechinese=$datami['pname'];
        $nameenglish=$datami['name'];
        $panteraddress=$datami['padress'];
        $operatescope=$datami['scope'];
        $business=$datami['business'];
        $timevalue=$datami['timevalue'];
        $licenseimg=$datami['licenseimg'];
        $orzimg=$datami['orzimg']?:'';
        $taximg=$datami['taximg']?:'';
        $doorplateimg=$datami['doorimg'];
        $legalperson=$datami['legalperson'];
        $conperbpno=decode($datami['pid']);
        $idface=$datami['idface'];
        $idcon=$datami['idcon'];
        $conpermobno=decode($datami['phone']);
        $conpername=$datami['conpername'];
        $conperteleno=decode($datami['conperteleno']);
        $settleaccountname=$datami['accoutname'];
        $settlebank=$datami['bank'];
        $settlebankname=$datami['bankname'];
        $settlebankid=decode($datami['bankno']);
        $settlementperiod=$datami['settleperiod'];
		$panterid=$this->getFieldNextNumber("panterid");
        $flag='3';
        $revorkflg='N';
        $sql="INSERT INTO panters(panterid,namechinese,nameenglish,address,operatescope,business,timevalue,licenseimg,orzimg,taximg,doorplateimg,";
        $sql.="legalperson,conperbpno,idface,idcon,conpermobno,conpername,conperteleno,settleaccountname,settlebank,settlebankname,settlebankid,settlementperiod,flag,parent,revorkflg,status)VALUES('{$panterid}','{$namechinese}','{$nameenglish}','{$panteraddress}','{$operatescope}',";
        $sql.="'{$business}','{$timevalue}','{$licenseimg}','{$orzimg}','{$taximg}','{$doorplateimg}','{$legalperson}','{$conperbpno}','{$idface}','{$idcon}','{$conpermobno}',";
        $sql.="'{$conpername}','{$conperteleno}','{$settleaccountname}','{$settlebank}','{$settlebankname}','{$settlebankid}','{$settlementperiod}','{$flag}','00000243','N','1')";
        $panterif=$this->panters->execute($sql);
        if($panterif==true){
          returnMsg(['status'=>'1','panterid'=>$panterid]);
        }else{
          returnMsg(['status'=>'05','msg'=>'卡系统数据库写入失败']);
        }
      }
      public function editPanter(){
        $zzk=['pname'=>'namechinese','name'=>'nameenglish','padress'=>'address',
            'scope'=>'operatescope',
             'legalPerson'=>'legalperson','pid'=>'conperbpno','phone'=>'conpermobn','accoutname'=>'settleaccountname',
             'bank'=>'settlebank','bankname'=>'settlebankname','bankno'=>'settlebankid',
             'settleperiod'=>'settlementperiod'
         ];
        $datami=$this->receviteData();
        $checkKey=$this->getKey1($datami);
        if($checkKey!=$datami['key']){
           returnMsg(array('status'=>'01','msg'=>'修改商户时，非法秘钥！'));
        }else{
            unset($datami['key']);
        }
        $panterid=decode($datami['panterid']);
        $list=$this->panters->where(['panterid'=>$panterid])->find();
        $datami=$this->ifData($datami);
        if($list==true){
           unset($datami['panterid']);
           $datami=$this->zzkData($datami,$zzk);
           $this->logData($datami,'app');
           $res=$this->panters->where(['panterid'=>$panterid])->save($datami);
           if($res==true){
             returnMsg(array('status'=>'1','msg'=>'修改商户成功'));
           }else{
             returnMsg(array('status'=>'05','msg'=>'卡系统数据库修改商户失败！'));
           }
        }else{
          returnMsg(['status'=>'06','msg'=>'卡系统未找到该商户']);
        }
      }
      protected function ifData($data,$index=['phone','bankno','pid','conperteleno']){
        foreach ($index as $key=>$val) {
           if(array_key_exists($val,$data)){
             $data[$val]=decode($data[$val]);
           }
        }
        return $data;
      }
      protected function zzkData($data,$zzk){

        foreach ($zzk as $key => $val) {
          if(array_key_exists($key,$data)){
            $data[$val]=$data[$key];
            unset($data[$key]);
            }
        }
        return $data;
      }
      private function logData($data,$info){
        $str=date('Y-m-d H:i:s',time()).'|';
        unset($data['$key']);
        foreach($data as $key=>$val){
           $str.=$key.'=>'.$val.'|';
        }
        $str.="\n";
        $filename=PUBLIC_PATH.$info.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        file_put_contents($filename,$str,FILE_APPEND);
    }

    private function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
	protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $model=new Model();
        $list=$model->query($sql);
        $fieldsLength=C('FIELDS_LENGTH');
        $fieldLength=$fieldsLength[$field];
        $lastNumber=$list[0]['nextval'];
        return $this->getnumstr($lastNumber,$fieldLength);
    }
}
?>
