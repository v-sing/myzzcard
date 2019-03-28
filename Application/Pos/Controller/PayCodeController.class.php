<?php
namespace Pos\Controller;
use Think\Model;

class PayCodeController extends CoinController
{
    public function _initialize(){
        parent::_initialize();
        $this->keycode = 'YJDFSHXI';
        $this->model = new Model();
    }
    public function checkPanterCode(){
        $data  = $this->receviteData();
        $pname = $data['nameshop'];
        $status= $data['status'];
        $sign  = $data['sign'];
        $checkCode = md5($this->keycode.$pname.$status);
        if($sign!=$checkCode){
            returnMsg(['statsu'=>'03','msg'=>'非法秘钥']);
        }
        if($status!='1'){
            returnMsg(['statsu'=>'06','msg'=>'状态码错误']);
        }
        $searchData = $this->checkPname($pname);
        $panterid = encode($searchData['panterid']);
        returnMsg(['status'=>'1','pantername'=>$searchData['namechinese'],'panterid'=>$panterid,'codestatue'=>$searchData['codestatue']]);
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
    /*
     * 检验商户名
     */
    private function checkPname($pname){
        $map['namechinese'] = $pname;
        $map['flag']        = '3';
        $list = $this->model->table('panters')->where($map)->find();
        if($list){
            if($list['revorkflg']!=='N'){
                returnMsg(['status'=>'21','msg'=>'该商户已经禁用']);
            }
            return $list;
        }else{
            returnMsg(['status'=>'41','msg'=>'未查询到该商户']);
        }

    }
}
?>