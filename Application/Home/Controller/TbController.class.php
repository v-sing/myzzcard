<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class TbController extends CoinController{
    /*
     * @content 根据合同编号删除通宝发行记录*/
    public function opTb(){
        $this->delLog("接收--->".json_encode($_POST));
        $key_code = "insomnia2017";
        $coinAccount = M('coin_account');
        $fzgorder = trim($_POST['soi']);
        $key = trim($_POST['ssmi']);
        if(!$fzgorder||!$key||empty($fzgorder)||empty($key)){
            die(json_encode(array("msg"=>"warning")));
        }
        $sign = md5(md5($key_code.$fzgorder));
        if($key != $sign){
            die(json_encode(array("msg"=>"getout")));
        }
        $where = array(
            "sourceorder"=>$fzgorder
        );

        $coin_info = $coinAccount->where($where)->find();

        if(is_array($coin_info)&&$coin_info){
            $where_tmp = array("a.sourceorder"=>$fzgorder);
            $find_info = $coinAccount->alias("a")
                        ->join("coin_consume b on a.coinid=b.coinid")
                        ->where($where_tmp)
                        ->find();
            if($find_info){
                die(json_encode(array("code"=>"7")));
            }else{
                $model = new Model();
                $amount = $coin_info['rechargeamount'];
                $accountid = $coin_info['accountid'];
                $update_sql = "update account set amount=amount-{$amount} where accountid='{$accountid}' and type='01'";

                $model->startTrans();

                $re2 = $model->execute($update_sql);
                $record_log = json_encode($coin_info);
                $this->delLog("删除---->".$record_log);
                $re = $coinAccount->where($where)->delete();
                if($re && $re2){
                    $model->commit();
                    die(json_encode(array("code"=>"1")));
                }else{
                    $model->rollback();
                    die(json_encode(array("code"=>"4")));
                }
            }

        }else{
            die(json_encode(array("code"=>"5")));
        }

    }

    protected function delLog($data){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        $path=PUBLIC_PATH.'logs/tbdel/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
}