<?php
namespace Pos\Controller;

use Think\Controller;

class CateringController extends Controller
{
    protected $config;
    public function _initialize(){
        $this->config = [
            'hb'=>['key'=>'kfc0002kfc','pwd'=>'258369'],

        ];
    }
    public function goodspwd(){
        $post = $this->getPostJson();
        if(is_null($post)){
            dump("error address");
        }else{
            $item = $post['item'];
            $key  = $post['key'];
            if(!isset($this->config[$item])){
                returnMsg(['status'=>'02','codemsg'=>'这是个测试项目']);
            }
            $checkKey= md5($this->config[$item]['key'].$item);
            $checkKey===$key||returnMsg(['status'=>'01','codemsg'=>'非法秘钥']);
            returnMsg(['status'=>'1','pwd'=>$this->config[$item]['pwd']]);
        }

    }
    protected function getPostJson(){
        $data=file_get_contents('php://input');
        $data=json_decode($data,1);
        return $data;
    }

}