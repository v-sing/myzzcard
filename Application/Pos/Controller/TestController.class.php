<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\NumEncode;
class TestController extends Controller
{
    public function test(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
//        $map['customid'] = ['lt','00145526'];
        $count = M('encustoms')->where("personidexdate is null and personid is not null")
            ->order('customid desc')
            ->count();
			//echo M('encustoms')->getLastSql();
			dump($count);exit;
        $j=0;
        do{
            $lists = M('encustoms')->where("personidexdate is null and personid is not null")
                     ->field('personid,customid,personidexdate')
                     ->limit($j,300)
                     ->order('customid desc')
                     ->select();
            foreach($lists as $val){
                if($val['personid'] && !$val['personidexdate']){
                    $year = substr($val['personid'],6,4);
                    if($year<=1977){
                        $save['personidexdate'] = '永久';
                    }elseif($year>1977){
                        $save['personidexdate'] = substr($val['personid'],6,4)+20;
                        if($save['personidexdate']<2017){
                            $save['personidexdate']+=20;
                        }
                        $save['personidexdate'].= substr($val['personid'],10,4);
                    }
                   $bool = M('encustoms')->where(['customid'=>$val['customid']])->save($save);
                   if(!$bool){
                       $str = "customid=>".$val['customid']." ---失败----"."\t\n";
                       file_put_contents('personid.txt',$str,FILE_APPEND);
                   }else{
                       $str = "customid=>".$val['customid']. "*****成功******"."personidexdate=> ".$save['personidexdate']."\t\n";
                       file_put_contents('csuccess.txt',$str,FILE_APPEND);
                   }
                }
            }
            if($j==($count-1)){
                break;
            }
            $j = $j+300;
            if($j>($count-1)){
                $j=$count-1;
            }
        }while($j<$count);
    }
    /*
     * 对商户信息 进行加密处理
     *
     */
    public function encodeCustoms(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $numEncode = new NumEncode();
        $map['customid'] = ['lt','00311256'];
        $count = M('encustoms')
            ->where('linktel is not null or personid is not null')
            ->where($map)
            ->count();
        $j=0;
        do{
            $lists = M('encustoms')
                ->where('linktel is not null or personid is not null')
                ->field('linktel,personid,customid')
                ->where($map)
                ->limit($j,300)
                ->order('customid desc')
                ->select();
            foreach($lists as $val){
                $save = [];
                if($val['linktel'] || $val['personid']){
                    if($val['linktel']){
                        $save['linktel'] = $numEncode->encode( $val['linktel'],1);
                    }
                    if($val['personid']){
                        $save['personid'] = $numEncode->encode( $val['personid']);
                    }
                    $bool = M('encustoms')->where(['customid'=>$val['customid']])->save($save);
                    if(!$bool){
                        $str = "customid=>".$val['customid']." ---失败----"."\t\n";
                        file_put_contents('encodecustomidfalse.txt',$str,FILE_APPEND);
                    }else{
                        $str = "customid=>".$val['customid']. "*****成功******"."\t\n";
                        file_put_contents('encodecustomidsuccess.txt',$str,FILE_APPEND);
                    }
                }
            }
            if($j==($count-1)){
                break;
            }
            $j = $j+300;
            if($j>($count-1)){
                $j=$count-1;
            }
        }while($j<$count);
    }

	public function hotelCardsExpire(){
        $map['cardkind'] = '2081';
        $map['exdate']   = ['elt',date('Ymd')];
        $map['status']   = 'Y';
        $lists = M('cards')->where($map)->field('cardno,status,exdate')->select();
		//var_dump($lists);exit;
        if($lists){
            foreach($lists as $val){
            $bool = M('cards')->where(['cardno'=>$val['cardno']])->save(['status'=>'L']);
            $val['msg'] = boolval($bool);
            $this->writeLog($val,__FUNCTION__,'酒店礼品卡过期处理');
           }
        }
    }
	
	protected function writeLog($data,$dir,$msg){
        $str=date('Y-m-d H:i:s',time()).'******************'.'msg:'.$msg."\t";
        $str.=json_encode($data, JSON_UNESCAPED_UNICODE);
        $str.="\n";
        $filename=LOGSTIME.$dir.'/'.date('Ymd',time()).'.txt';
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
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }
}