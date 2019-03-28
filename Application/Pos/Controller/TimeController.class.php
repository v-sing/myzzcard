<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
class TimeController extends Controller
{
    private $keyCode;
    private $greenParent;
    private $model;
    public function _initialize()
    {
        $this->keyCode = 'timetask';
        $this->greenParent = '00000483';
        $this->model=new model();
    }

    public function index()
    {
        $data = getPostJson();
        $method = $data['method'];
        $key = $data['key'];
        $this->checkKey($method, $key);
        $this->$method();
    }

    private function checkKey($method, $key)
    {
        $check = md5(md5(md5($this->keyCode.$method)));
        if ($check != $key) {
            returnMsg(['status' => '01', 'codemsg' => '非法秘钥']);
        }

    }

    /*
     * var $method 方法名
     *
     * var $error 返回的错误信息和提示
     *
     * var $errorData 错误数据对比
     */
    private function errorLog($method, $error, $errorData)
    {
        $filename = GREEN . 'daliy' . '/' . date('Ymd', time()) . '.txt';
        $this->getDir($filename);
        $str = date('Y m d H:i:s', time()) . '  ' . $method . '------------';
        foreach ($error as $ke => $val) {
            $str .= $ke . ':' . $val . "\t  ";
        }
        foreach ($errorData as $k => $v) {
            $str .= $k . ':' . $v . "\t  ";
        }
        $str .= "\n";
        file_put_contents($filename, $str, FILE_APPEND);
    }

    private function getDir($filename)
    {
        $path = pathinfo($filename);
        $path = $path['dirname'] . "/";
        $path = str_replace("\\", "/", $path);
        file_exists($path) || mkdir($path, 0777, true);
    }

    /*
     *  错误 处理 写入日志返回错误信息
     */
    private function handdle($method, $error, $errorData)
    {
        $this->errorLog($method, $error, $errorData);
        returnMsg($error);
    }
    protected function writeLog($data,$dir,$msg){
        $str=date('Y-m-d H:i:s',time()).'******************'.'msg:'.$msg."\t";
        if(is_array($data)){
            $str.=json_encode($data, JSON_UNESCAPED_UNICODE);
        }else{
            $str.=$data;
        }
        $str.="\n";
        $filename=PUBLIC_PATH.$dir.'/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        file_put_contents($filename,$str,FILE_APPEND);
    }
    /*
     * 酒店预授权30天后 自动撤销
     */
    private function undoHotelAuthorization(){
        $map['ca.cardkind']  = ['in',['6882','2081']];
        $map['tw.tradetype'] = '17';
        $map['tw.flag']      = '0';
        $map['tw.placeddate']= ['elt',date('Ymd',time()-30*86400)];
        $field = "tw.cardno,tw.tradeamount,tw.customid,tw.termno,tw.tradeid,tw.panterid,tw.termposno";
        $list = M('trade_wastebooks')
                       ->alias('tw')
                       ->join('left join cards ca on ca.cardno=tw.cardno')
                       ->where($map)->field($field)->select();
        if(!$list){
            exit(json_encode(['info'=>'无预授权需要批量处理'],JSON_UNESCAPED_UNICODE));
        }else{
            $num = 0;
            $time = time();
            foreach($list as $key=>$val){
                $account = M('account')->where(['customid'=>$val['customid'],'type'=>'00'])->find();
                if($account){
                    $time1 = date('Y-m-d H:i:s',$time);
                    $tradetype ='19';
                    $flag      = '0';
                    $tradeid = $val['termposno'].date('YmdHis',$time);
                    $placeddate = date('Ymd',$time);
                    $placedtime = date('H:i:s',$time);
                    $amount = bcadd($val['tradeamount'],$account['amount'],2);
                    $this->model->startTrans();
                    $sql="INSERT into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,placedtime,tradeamount,cardno,tradetype,flag,pretradeid,customid) VALUES('";
                    $sql .=trim($val['termno'])."','" .$val['termposno']."','".$val['panterid']."','".$tradeid."','".$placeddate."','".$placedtime."','".$val['tradeamount']."','";
                    $sql.=$val['cardno']."','".$tradetype."','".$flag."','".trim($val['tradeid'])."','".$val['customid']."')";
                    $bool1 = $this->model->execute($sql);
                    $bool2 = $this->model->table('trade_wastebooks')->where(array('tradeid'=>$val['tradeid']))->save(array('flag'=>'2'));
                    $bool3 = $this->model->table('account')->where(array('customid'=>$val['customid'],'type'=>'00'))->save(array('amount'=>$amount));
                    if($bool1==true && $bool2==true && $bool3==true){
                        $this->model->commit();
                        $str = '预授权流水号:'.trim($val['tradeid'])."\t,金额：".$val['tradeamount']."\t,预授权撤销流水号：".$tradeid."\t,时间:".$time1."\n";
                        $this->writeLog($str,'logs/'.__FUNCTION__.'/success','30天预授权自动撤销成功');
                    }else{
                        $this->model->rollback();
                        $str = '预授权流水号:'.trim($val['tradeid'])."\t,金额：".$val['tradeamount']."\n";
                        $this->writeLog($str,'logs/'.__FUNCTION__.'/sqlerror','30天预授权自动撤销数据库操作失败');
                    }

                }else{
                    $this->writeLog(['customid'=>$val['customid'],'logs/'.__FUNCTION__.'/false','预授权撤销时缺失用户信息']);
                }
                $time++;
            }
            exit(json_encode(['success'=>'执行了预授权30天后取消操作'],JSON_UNESCAPED_UNICODE));
        }
    }
    /*
     * 客户身份证三个月到期的给客户进行到期提醒及时更新身份证照片
     */
    public function checkPersonidexdate(){
        $switch = M('config')->where(['name'=>'personExpire'])->field('value')->find();
        if($switch['value']==='on'){
            $date = date('Ymd',time()-90*86400);
            $map['personidexdate'] = ['elt',$date];
            $map['_string'] = '(linktel is not null)';
            $map['_string'] ='(linktel is not null) and (frontimg is not null) and (thirdzip is null)';
            $tpl_id = '1734786';
            $list = $this->model->table('customs')->where($map)
                ->field('linktel，customid')->select();
            if($list){
                $list = [['linktel'=>'18538508988','customid'=>'0000001']];
                foreach($list as $val){
                    if($val['linktel']){
                       $res=$this->sendMsg($tpl_id,$val['linktel']);
                       $arr =json_decode($res,1);
                       if($arr['code']!==0){
                           $arr['linktel'] = $val['linktel'];
                           $arr['customid']= $val['customid'];
                           $this->writeLog($arr,'Time/checkPersonidexdate/yunpianfalse','云片返回错误信息');
                       }
                    }else{
                        $this->writeLog(['customid'=>$val['customid']],'Time/checkPersonidexdate/false','该会员缺失电话号');
                    }
                }
            }
        }else{
             dump(false);
        }
    }
    //云片网发送短信20160401
    private function sendMsg($tpl_id,$mobile){
        $data = array (
            "apikey" =>'b9ede309143b6931de5d41abbd947abc',
            "tpl_id" =>$tpl_id,
            "mobile" =>$mobile
        );
        $ch = curl_init("https://sms.yunpian.com/v1/sms/tpl_send.json");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res=curl_exec($ch);
        return $res;
    }
}
?>
