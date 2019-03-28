<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class ScanCodeController extends CommonController {
     
    /**
    * 函数用途描述
    * @date: 2017年2月6日 上午11:11:04
    * @author: Administrator
    * @param: variable
    * @return:生成二维码
    */
    function createcode(){
        $model = M('panters');
        Vendor("phpqrcode");
        $panterid=I('panterid');//获取商户的id
        $where['panterid']=$panterid;
        $pantersfind = $model->where($where)->find();
        $revorkflg=$pantersfind['revorkflg'];//是不是正常的商户
         
        $msglog="生成二维码传来的数据：".$panterid."\r\n";
        
        $this->WriterLog('createcode',$msglog);
        
        if($revorkflg=="N"){ 
            //生成二维码
            $pantername=$pantersfind['namechinese'];
            $mdname=encode($pantername);
            $object=new \QRcode();
            $url=C("ScanCode")."?nameShop=".$mdname;
            $level="3";
            $size="9";  
            $month=date('Ymd',time()); 
            $path=PUBLIC_PATH."Scan/".$panterid.'/'.$month.'/';//PUBLIC_PATH."Scan/".$month.'/'.$dd.'/';
            $file=$path.$panterid.".png";//图片输出路径和文件名
            if(!file_exists($path)){
               mkdir($path,0777,true);
            } 
            if(file_exists){
                 unlink($file);
             }
            $msgpath="生成二维码的地址：".$file."\r\n"; 
            $msgpath.="\r\n"."\r\n".'---------结束------------'."\r\n"."\r\n";
            $this->WriterLog('createcode',$msgpath);
            
            $errorCorrectionLevel=intval($level);//容错级别
            $matrixPointSize =intval($size);// 生成图片大小
            $object->png($url,$file,QR_ECLEVEL_L,$matrixPointSize,2,false); 
            $save['codeadd']=$file;
            $save['codestatue']='1';
            $model->where($where)->save($save);
            $this->redirect('panters/index');
        }
    }
    /**
    * 函数用途描述
    * @date: 2017年2月6日 下午1:56:34
    * @author: Administrator
    * @param: variable
    * @return:下载二维码
    */
    public function downimg(){
        $model = M('panters');  
        $panterid=I('panterid');//获取商户的id
        $where['panterid']=$panterid;
        $pantersfind = $model->where($where)->find();
        $codestatue=$pantersfind['codestatue'];
        if($codestatue!='1'){
            $this->error('二维码不存在或已经过去');
            exit;
        } 
        $imgfile=$pantersfind['codeadd'];
        $fileinfo = pathinfo($imgfile);
        $filename=$pantersfind['namechinese']; 
        
        header('Content-type: application/x-'.$fileinfo['extension']);
        header('Content-Disposition: attachment; filename='.$filename."_".$fileinfo['basename']);
        header('Content-Length: '.filesize($imgfile));
        readfile($imgfile);
        
        $msglog="下载二维码传来的数据：".$panterid.','."\t\n";
        $msglog.="二维码的地址：".$filename."_".$fileinfo['basename']."\r\n";
        $msglog.="\r\n"."\r\n".'---------结束------------'."\r\n"."\r\n";
        $this->WriterLog('downimg',$msglog);
        
        exit();
    }
    /**
    * 函数用途描述
    * @date: 2017年2月6日 下午2:08:18
    * @author: Administrator
    * @param: variable
    * @return:一码多付的禁用
    */
    public function forbidden(){
        $model = M('panters');
        $panterid=I('panterid');//获取商户的id
        $where['panterid']=$panterid;
        $pantersfind = $model->where($where)->find();
        $codestatue=$pantersfind['codestatue'];
        
        $msglog="禁用：".$panterid.','."\t\n";
        $this->WriterLog('forbidden',$msglog);
        
        if($codestatue==1){  
            $file=$pantersfind['codeadd']; 
            $save['codestatue']='2';
            $save['codeadd']=""; 
            $saveid=$model->where($where)->save($save);
             
            $msglog.=$saveid."禁止的更改的数据：".json_encode($save)."\r\n";
            $msglog.="\r\n"."\r\n".'---------结束------------'."\r\n"."\r\n";
            $this->WriterLog('forbidden',$msglog);
            if($saveid>0){  
                $this->redirect('panters/index');
            }
        }else{
            $this->error("二维码的状态有误");
            exit;
        } 
    }
  public function WriterLog($pathname,$data){
       $month=date('Ym',time());
       $dd=date('d',time());
       if(!in_array($data)){
           $strdata=$data; //数据
       }else{
           $strdata=json_encode($data);
       }  
       $path=PUBLIC_PATH.'Scanlogs/'.$pathname.'/'.$month.'/'.$dd.'/';
       $pathlog=date('Ymd',time()).'.log';
       $filename=$path.$pathlog;
     
       if(!file_exists($filename)){
           mkdir($path,0777,true);
       }
       $strdata='时间'.date('Y-m-d H:i:s',time()).'是：'.$strdata;
       file_put_contents($filename,$strdata,FILE_APPEND);
   } 
   
}