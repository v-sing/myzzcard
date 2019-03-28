<?php
namespace  Pos\Controller;
use Think\Controller;
use Think\Model;

trait GreenPanterController
{
    /*
     *检查商户是否是大食堂商户
     */
    protected function isGreen($panterid ,$parent){
       $result = M('panters')->where(['panterid'=>$panterid,'parent'=>$parent,'flag'=>'3'])->field('panterid')->find();
       if($result){
           return true;
       }else{
           returnMsg(['status'=>'21','codemsg'=>'不是大食堂商户']);
       }
    }
    /*
     * 修改商户名 判定imei 与panterid匹配
     * @param string  $imei
     * @param sting   $panterid
     */
    protected function imeiVerify($imei,$panterid){
        $list = M('zz_config')->where(['imei'=>$imei,'panterid'=>'0000000'.$panterid])->find();
        if($list){
            return true;
        }else{
            returnMsg(['status'=>'45','codemsg'=>'未查询到该商户的pos配置']);
        }
    }
    /*
     * 大食堂 新增商户不允许超过200
     */
    protected function limitCount($parent){
        $count = M('panters')->where(['parent'=>$parent,'flag'=>'3'])->count();
        if($count>199){
            returnMsg(['status'=>'29','codemsg'=>'新增商户超过限额']);
        }else{
           return true;
        }
    }
    protected function sessionAddNum($key){
        if(is_null(session($key))){
            session($key,1);
        }
        session($key,session($key)+1);
    }
}