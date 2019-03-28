<?php
namespace Home\Controller;
use Think\Controller;
use Think\Page;

//require THINK_PATH.'Library/Org/Util/phpqrcode.php';
class JyHomeController extends Controller
{
    protected $keycode;
    protected $customs;
    protected $cards;
    public function _initialize(){
        $this->keycode = 'JYO2O01';
        $this->customs = M('customs');
        $this->cards   = M('cards');
        $this->customs_c = M('customs_c');
    }
    /*
     * 查询积分功能
     */
    public function balance(){
    $cid  = trim(I('get.cid'));
    if(isset($cid)){
        $customid = $cid;
        $sign = md5($this->keycode.$customid);
        $data =array(
            'customid'=>$customid,
            'key'=>$sign
        );
        session('access_token_jyhome',"accessToken={$_GET['access_token']}");
        $data = json_encode($data);
        $sendData = ['datami'=>$data];
        $url = C('jyhomebalance');
        $query=$this->getQuery($url,$sendData);
        if($query['status']==='1'){
            $balance = $query['amount']?:0;
            $this->getTrade($customid);
            $this->assign('balance',$balance);
        }else{
            exit(json_encode($query));
        }
    }
    $this->assign('cid',$cid);
    $this->display();
}
    public function balance1(){
        $cid  = trim(I('get.cid'));
        $cid  = encode('00133142');
        if(isset($cid)){
            $customid = $cid;
            $sign = md5($this->keycode.$customid);
            $data =array(
                'customid'=>$customid,
                'key'=>$sign
            );
            session('access_token_jyhome',"accessToken={$_GET['access_token']}");
            $data = json_encode($data);
            $sendData = ['datami'=>$data];
            $url = C('jyhomebalance');
            $query=$this->getQuery($url,$sendData);
            if($query['status']==='1'){
                $balance = $query['amount']?:0;
                $this->assign('balance',$balance);
            }else{
                exit(json_encode($query));
            }
        }
        $this->assign('cid',$cid);
        $this->display();
    }
    public function info(){
        $this->display();
    }
    /*
     * 查询消费记录
     * @param  array $customid
     */
    public function getTradeInfo(){
        $page = trim(I('get.page'));
        $size = trim(I('get.size'));
        $start= 0+($page-1)*$size;
        $cid  = trim(I('get.cid'));
        $customid = decode($cid);
        $cardno   = $this->customs_c->alias('cc')
                                ->join("left join cards ca on ca.customid=cc.cid")
                                ->where(['cc.customid'=>$customid,'ca.cardkind'=>'6680'])
                                ->field('ca.cardno')->find();
        $map      = ['cardkind'=>'6680','cardno'=>trim($cardno['cardno'])];
        $list = M('trade_wastebooks')
                            ->where($map)->field('tradeamount,tradeid,placeddate,placedtime')
                            ->limit($start,$size)->select();
        $list = $this->setFloat($list,'tradeamount');
        echo json_encode($list);
    }
    public function getTrade($customid){
        $customid = decode($customid);
        $cardno   = $this->cards->where(['customid'=>$customid])
            ->field('cardno')->find();
        $map      = ['cardkind'=>'6680','cardno'=>trim($cardno['cardno'])];
        $count    = M('trade_wastebooks')->where($map)
            ->field('tradepoint,tradeid,placeddate,placedtime')
            ->count();
        $p = new Page($count,2);
        $list = M('trade_wastebooks')
            ->where($map)->field('tradepoint,tradeid,placeddate,placedtime')
                            ->limit($p->firstRow.','.$p->listRows)->select();
        $list = $this->setFloat($list,'tradepoint');
        $show = $p->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
    }
    protected function getQuery($url,$data){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
        curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_TIMEOUT,60);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        $info  = curl_getinfo( $ch );
        $errno = curl_errno( $ch );
        $info['errno'] = $errno;
        curl_close();
        return json_decode($output,true);
    }
//    function getImg(){
//        $customid1=encode('00190956');
//        dump($customid1);
//        $customid=decode($customid1);
//        $customlist = M('customs')->where(array('customid'=>$customid))->find();
//        $customlist==true || returnMsg(array('status'=>'05','codemsg'=>'会员编号不存在'));
//        $customlist['linktel']==true || returnMsg(array('status'=>'04','codemsg'=>'用户联系电话为空'));
//        $content="/zzkp.php/Img/getImg&customid={$customid1}&linktel={$customlist['linktel']}&flag=jyhome&rand=".time();
//        $errorCorrectionLevel = 'L';//容错级别
//        $matrixPointSize = 10;//生成图片大小
//        $filename=PUBLIC_PATH."img/".md5($customid).'.png';//原始二维码图 名称路径
//        $path = pathinfo($filename);
//        $path = $path['dirname']."/";
//        file_exists($path) || mkdir($path,0777,true);
//        if(file_exists($filename)){
//            unlink($filename);
//            \QRcode::png($content, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
//            $filename = ltrim($filename,'.');
//            $erweima=$filename;
//            returnMsg(array('status'=>'1','img'=>$erweima));
//        }else{
//            \QRcode::png($content, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
//            $filename = ltrim($filename,'.');
//            $erweima=$filename;
//            returnMsg(array('status'=>'1','img'=>$erweima));
//        }
//    }
    /*
     * 数据转为float 类型处理
     * @param  array  $arr 数据数组
     * @param  string $column 对应列
     * @return array
     */
    protected function setFloat($arr,$column){
        if($arr){
            foreach($arr as $key=>$val){
                $val[$column] = floatval($val[$column]);
                $arr[$key]    = $val;
            }
        }
        return $arr;
    }
}