<?php
namespace Pos\Controller;
use Org\Util\DESedeCoder;
use Org\Util\YjDes;
use Think\Controller;
use Think\Model;
class CustomsController extends Controller{

	public function _initialize(){

	    $this->sign =  'JYO2O01';

		$this->model = new model();

		$this->des   = new DESedeCoder();

		$this->Yj   =  new  YjDes();
	}
	//支付即会员

    public function payCustom(){
	    $post = $this->decodeDesData();
	    $phone  = $post['phone'];
	    $amount = $post['amount'];
	    $source = $post['source'];
	    $orderid= $post['orderid'];
	    $key    = $post['key'];
	    $this->checkSign($key,md5($this->sign.$phone.$amount.$source.$orderid));

	    //查询是否是一家会员

        $map['linktel'] = $phone;

        $count = $this->model->table('customs')->where($map)->count();
        if($count==1){
            $customid     = $this->model->table('customs')->where($map)->field('customid')->find()['customid'];
            $baseCustomid = encode($customid);
        }elseif ($count==0){
            $baseCustomid = '';
        }else{
            returnMsg(array('status'=>'01','codemsg'=>'校验错误次会员信息异常'));
        }
        $ejia['appid']       = 'SOON-ZZUN-0001';
        $ejia['mobilephone'] = $phone;
        $ejia['srcfrom']     = $source;
        $ejia['customid']    = $baseCustomid;

        $bool = $this->registerGetJyCoin($ejia);
        if($bool){
            $registerGetJyCoinInfo = date('Y-m-d H:i:s').'---'. json_encode($ejia).'结果'.$bool."\n";
            $this->recordError($registerGetJyCoinInfo,__FUNCTION__,'ejia');
        }
        if($baseCustomid==''){
            $customInfo     = $this->model->table('customs')->where($map)->field('customid')->find();
            if(! $customInfo){
                returnMsg(['status'=>'04','codemsg'=>'反向注册失败']);
            }
            $baseCustomid = encode($customInfo['customid']);
        }
        $consume['member_id']    = $baseCustomid;
        $consume['source_order'] = $orderid;
        $consume['source_code']  = '1001';
        $consume['trigger_rules']= '至尊消费';
        $consume['recharge_amount'] = $amount;
        $consume['key']          = md5($this->sign.implode($consume));
        $res = $this->consumeGetJycoin($consume);
        if($res==false){
            returnMsg(['status'=>'05','codemsg'=>'消费赠送通宝接口异常']);
        }else{
            if($res['status']=='01'){
                returnMsg(['status'=>'1','codemsg'=>'赠送通宝成功']);
            }else{
                returnMsg(['status'=>'06','codemsg'=>'赠送通宝接口返回'.$res['codemsg']]);
            }
        }
    }
    public function giftSwitch(){
        $post    = $this->decodeDesData();
        $appName = $post['appName'];
        $key     = $post['key'];
        $this->checkSign($key,md5($this->sign.$appName));

        $switch = C('giftJycoinSwitch');
        if(in_array($appName,$switch)){
            $status = 'on';

        }else{
            $status = 'off';
        }
        returnMsg(['status'=>'1','codemsg'=>'查询成功','swtich'=>$status]);
    }


    //消费送通宝
    protected function consumeGetJycoin($consume){
//	    dump($consume);exit;
	    $json  = json_encode($consume);
	    $encode= $this->des->encrypt($json);
        $url   = C('consumeGetJycoin');
        return  $this->curlPost(json_encode(['datami'=>$encode]),$url,'consumeGetJycoin');

    }

    protected function registerGetJyCoin($ejia){
	    $url   = C('registerGetJyCoin');
        $de = new YjDes();
        $sign = $de->encrypt($ejia);
        $data = json_encode($ejia, JSON_FORCE_OBJECT);

	   return  $this->curlYPost($url,$data,$sign);
    }
    private function checkSign($postKey, $key){
        $postKey==$key || returnMsg(array('status'=>'06','codemsg'=>'秘钥错误'));
        return true;
    }
    private function curlYPost($url, $data, $sign)
    {
        if (!$url) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json', "sign:{$sign}"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $output = curl_exec($ch);
        if ($output == false) {
            $info = curl_getinfo($ch);
            $info['errorcode']= curl_error($ch);
            $this->recordError(json_encode($info),'registerGetJyCoin','ejia');
            return false;
        }
        curl_close($ch);
        return $output;
    }

    private function decodeDesData(){
        $post = getPostJson();
        $data = trim($post['datami']);
        $data=$this->des->decrypt($data);
        if($data==false){
            returnMsg(array('status'=>'01','codemsg'=>'加密信息传入错误'));
        }
        $json = json_decode($data,1);
        if($json){
            return $json;
        }else{
            returnMsg(array('status'=>'08','codemsg'=>'数据格式错误'));
        }
    }

    protected function curlPost($str,$url,$dir){
        $result = $this->curlPostJson($url,$str);
        $time   = date('Y-m-d H:i:s');
        if($result['curl'] ===true){
            $json = json_decode($result['json'],1);
            $str = $time."-".$result['json']."\n";
            $this->recordError($str,$dir,'zzk');
            return $json;
        }else{
            $str = $time."-".json_encode($result['json'])."\n";
            $this->recordError($str,$dir,'zzk');
            return false;
        }


    }
    protected function curlPostJson($url,$data){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,[
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);
        $result = curl_exec($ch);
        if($result==false){
            $info = curl_getinfo($ch);
            $info['errorcode']= curl_error($ch);
            curl_close($ch);
            return ['curl'=>false,'json'=>$info];
        }else{
            curl_close($ch);
            return ['curl'=>true,'json'=>$result];
        }

    }
    protected function recordError($data,$childPath,$indentifyName){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $day=date('d');
        $filename=date('Ymd').iconv("utf-8","gb2312",$indentifyName).'.log';
        $time=date('Y-m-d H:i:s');
        $string=$data;
        $path=PUBLIC_PATH.'logs/interface/'.$childPath.'/'.$month.'/'.$day.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);

    }

    /**
     * 房掌柜反向注册一家
     * 
     * @author dylucas
     */
    public function fzgE()
    {
        $post = getPostJson();
        $this->recordError(date('Y-m-d H:i:s') . '---' . serialize($post) . "\r", __FUNCTION__, 'ejia');
        $phone = $post['phone'];
        $customid = $post['customid'];
        $key = $post['key'];
        if ($key != md5($this->sign . $phone . $customid)) {
            returnMsg(array('status' => '0', 'codemsg' => '秘钥错误'));
        }

        $baseCustomid = encode($customid);
        $ejia['appid'] = 'SOON-ZZUN-0001';
        $ejia['mobilephone'] = $phone;
        $ejia['srcfrom'] = '001';
        $ejia['customid'] = $baseCustomid;

        $bool = $this->registerGetJyCoin($ejia);
        if ($bool) {
            $registerGetJyCoinInfo = json_encode($ejia) . '结果' . $bool . "\n\r";
            $this->recordError($registerGetJyCoinInfo, __FUNCTION__, 'ejia');
        }
        exit($bool);
    }

  }
