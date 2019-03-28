<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\CCdes;
class PanterController extends Controller
{
    protected $des;
    protected $key;
    protected $apiKey;
    protected $userName;
    //图片保存文件夹
    protected $saveDir;
    //customs 表
    protected $customs;
    public function _initialize(){
        $this->des     = new CCdes();
        $this->key     = 'Bdhk2FbU';
        $this->apiKey  = '400101';
        $this->userName= 'zz_jianyezzbusi';
        $this->saveDir = './Public/identityCard/';
        $this->customs  = M('customs');
    }
    public function index(){
        $name='刘瑞';
        $identityCard='411628197809168624';
//        $data = ['apiKey'  =>  $this->apiKey,
//                 'username'=>  $this->userName,
//                 'params'  =>  $this->encode($name,$identityCard)
//                ];
        $data="apiKey=".$this->apiKey."&username=".$this->userName."&params=".$this->encode($name,$identityCard);
        $url=C();
        $url='http://120.55.73.98:8080/ccqwsV2/api/rest/main';
        $result=$this->getUrl($url,$data);
        dump($result);exit;
        if($result['code']=='0'){
           $getData = $result['data'];
           $photo   = $this->photo($getData['photo'],$getData['identitycard']);
        }else{
           $this->writeLog($result,'identityCard','获取身份证信息失败');
        }
    }
    /*
     * 照片数据处理
     * @param  string $photo
     * @param  string $identitycard
     * @return stting
     */
    protected function photo($photo,$identitycard){
         $data     = base64_decode(base64_decode($photo));
         $filename = $identitycard.".jpg";
         $file = fopen($this->saveDir.$filename,"w");//打开文件准备写入
         fwrite($file,$data);//写入
         fclose($file);
        return $this->saveDir.$filename;
    }
    /*
     * 身份证信息加密
     * @param string $name 用户名
     * @param string $identityCard 身份证信息
     * @return string
     */
    protected function encode($name,$identityCard) {
        $str="name=".$name."&identityCard=".$identityCard;
        return $this->des->encrypt($str,$this->key);
    }
    /*
     *curl 发送请求返回数据
     *@param string $url 地址
     *@param sting  $data 数据
     */
    protected function  getUrl($url,$data){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }
    /*
  * 日志记录功能
  * @param  array $data 要记录的数据
  * @param  string $dir 文件名
  * @param  string $msg 数据描述信息
  * @return void
  */
    protected function writeLog($data,$dir,$msg){
        $str=date('Y-m-d H:i:s',time()).'******************'.'msg:'.$msg."\t";
        $str.=json_encode($data, JSON_UNESCAPED_UNICODE);
        $str.="\n";
        $filename=ELOG.$dir.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        file_put_contents($filename,$str,FILE_APPEND);
    }
    /*
    * 日志记录时 创建文件夹
    * @param  string $filename 文件夹名
    * @return void
    */
    protected function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path = str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
    /*
     * 获取的身份证信息保存数据库
     * @param string  $photo 身份证图片地址
     * @param string  $identityCard 身份证照信息
     */
    protected function saveInfo($photo,$identityCard){
        $where['personid'] = $identityCard;
        $data['FrontImg'] = $photo;
        $this->where($where)->save($data);
    }
}
?>