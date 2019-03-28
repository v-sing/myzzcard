<?php
namespace Home\Controller;
use Org\Util\Barcode\BarcodeGeneratorPNG;
use Think\Controller;
use Think\Model;

class AibianliController extends Controller
{
    public function index(){
        $cid = trim(I('get.cid',''));
        if($cid!=''){
            $customid  = decode($cid);
            $info      = $this->barEncode($customid);
            $barcode   = $info['barcode'];
            $placeddate= date('Ymd',$info['time']);
            $placedtime= date('H:i:s',$info['time']);
            $generator = new BarcodeGeneratorPNG();
            $img       = 'data:image/png;base64,' . base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128));
            $sql       = "INSERT INTO barcode (codeid,customid,placeddate,placedtime) VALUES ('{$barcode}','{$customid}','{$placeddate}','{$placedtime}')";
            $model     = new Model();
            try{
                $bool = $model->execute($sql);
            }catch (\Exception $e){
                exit($e->getMessage());
            }
            if($bool==false) exit('barcode 入库失败,请联系管理员');
            $this->assign('png',$img);
            $this->display();
        }else{
            exit('缺失customid');
        }
    }
    private function barEncode($customid){
        $arr      = str_split($customid);
        $zero     = array_keys($arr,'0');
        $customid = str_replace('0','',$customid);
        $not      = str_split($customid);
        $sum      = array_sum($not);
        $not[0]   = 10-$not[0];

        $first    = implode($not);
        $second   = rand(100,999);

        foreach ($zero as $val){
            $second.=$val+1;
        }
        $count    = count($not);
        $second   = $second.$count;
        //时间记录
        $time     = time();
        $third    = substr($time,-4).(100-$count-$sum);
        return ['barcode'=>$first.$second.$third,'time'=>$time];
    }
}