<?php
namespace Home\Controller;
use Think\Controller;
use Org\Util\Des;
use Org\Util\YjDes;
use Think\Model;

class EappController extends Controller {
    protected $keycode;
    public function _initialize(){
        $this->keycode='JYO2O01';
    }

    public function modifyLinktel(){
        $this->recordData('11111');
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'06','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami);
        $customid=trim($datami['customid']);
        $newlinktel=trim($datami['newlinktel']);
        $oldlinktel=trim($datami['oldlinktel']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$customid.$newlinktel.$oldlinktel);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'用户缺失'));
        }
        $model=new model();
        $customid=decode($customid);
        $customInfo=$model->table('customs')->where(array('customid'=>$customid))->field('customid,linktel')->find();
        if($customInfo==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法会员编号'));
        }
        if($oldlinktel!=$customInfo['linktel']){
            returnMsg(array('status'=>'06','codemsg'=>'旧手机号与系统手机号不符'));
        }
        $list=$model->table('customs')->where(array('linktel'=>$newlinktel,'customlevel'=>'建业线上会员'))->find();
        if($list!=false){
            $card=$model->table('customs')->alias('cu')
                ->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where(array('cu.customid'=>$list['customid'],'c.cardkind'=>'6886','c.panterid'=>'00000286'))
                ->select();
            if(count($card)>0){
                returnMsg(array('status'=>'04','codemsg'=>'手机号已注册过一家app'));
            }
        }
        $currentDate=date('Ymd');
        $currentTime=date('H:i:s');
        $changeSql="insert into linktel_change_logs values('{$customid}','{$newlinktel}','{$customInfo['linktel']}','{$currentTime}')";
        $customSql="update  customs set linktel='{$newlinktel}' where customid='{$customid}'";
        $customIf=$model->execute($customSql);
        $changeIf=$model->execute($changeSql);
        if($customIf==true&&$changeIf==true){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'修改失败'));
        }
    }

    protected function recordData($data){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";

        $path=PUBLIC_PATH.'logs/interface/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
}
?>
