<?php
namespace Home\Controller;
use Home\Model\ConfigModel;
use Think\Controller;
use Think\Model;

class ConfigController extends CommonController{
    /*
     * config manage
     */
    public function _initialize(){
        parent::_initialize();
        $this->config = new ConfigModel();
    }
    public function index(){
        $list = $this->config->getConfigList();
        $this->assign('list',$list);
        $this->display();
    }
    public function add(){
        if(IS_POST){
            $msg = ['name'=>'配置名','description'=>'配置描述','value'=>'配置值'];
            $config = $this->getPostData($_POST,$msg);
            $config['conid'] = $this->getnextcode('config',8);
            if($this->config->addConfig($config)){
                $this->success('新增成功','index');
            }else{
                $this->error('新增失败，请重试!');
            }
        }else{
            $this->display();
        }
    }
    public function switchs(){
        $conid  = trim(I('post.conid',''));
        $switch = trim(I('post.switch',''));
        $arr    = ['1'=>'off','2'=>'on'];
        if($switch==''){
            returnMsg(['status'=>'3','msg'=>'缺失开关参数']);
        }
        if($conid!=''){
            $bool = $this->config->save(['conid'=>$conid],['value'=>$arr[$switch]]);
            if($bool){
                returnMsg(['status'=>'1','msg'=>'修改成功']);
            }else{
                returnMsg(['status'=>'4','msg'=>'修改失败,请重试']);
            }
        }else{
            returnMsg(['status'=>'2','msg'=>'缺失配置id']);
        }
    }
    //handle post data
    private function getPostData($data,$msg){
        $arr =[];
        foreach($msg as $key=>$val){
            $arr[$key] = trim(I("post.$key",''));
            if($arr[$key]==''){
                $this->error($val.'不能为空!');
            }
        }
        return $arr;
    }
}
?>
