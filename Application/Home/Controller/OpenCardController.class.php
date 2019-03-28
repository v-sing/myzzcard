<?php
namespace Home\Controller;

class OpenCardController extends CoinController
{
    //大食堂 开卡
    function _initialize()
    {
        parent::_initialize();
        $this->keycode = 'kfc0002kfc';
    }
    public function allocateCards(){
        $data=getPostJson();
        
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

        $panterid=trim($data['panterid']);
        $termno=trim($data['termno']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$panterid.$termno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        // $panterid = '00001294';
        $cateringId = ['00000922'=>'574','00000923'=>'392','00000924'=>'393'];
        $name       = ['00000922'=>'神垕','00000923'=>'鹤壁','00000924'=>'濮阳'];
        //返回商户对应的机构
        $parent = M('panters')->where(['panterid'=>$panterid])->field('parent')->find();
        if(!$parent||!isset($cateringId[$parent['parent']])){
            returnMsg(['status'=>'05','codemsg'=>'非法商户']);
        }
        $parent = $parent['parent'];
        $userid='0000000000000197';
        $cardkind='6880';
        $cardStart=$cardkind.$cateringId[$parent];
        $customid=$this->getFieldNextNumber("customid");
        $namechinese=$name[$parent].'食堂'.intval($customid);
        $currentDate=date('Ymd',time());
        $this->model->startTrans();
        $sql="INSERT INTO CUSTOMS(customid,namechinese,placeddate,panterid) values ";
        $sql.="('{$customid}','{$namechinese}','{$currentDate}','{$parent}')";
        $this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
        $customIf=$this->model->execute($sql);
        if($customIf==true){
            $cardNo=$this->getLvCards($parent,$cardStart);
//            $cardNo=$this->checkCardUsable($cardNo,$parent,$cardkind);
            if(!empty($cardNo)){
                $bool=$this->opencard(array($cardNo),$customid,0,$parent,1,$userid);
                if($bool==true){
                    $this->model->commit();
                    returnMsg(array('status'=>'1','cardno'=>$cardNo,'termno'=>$termno));
                }else{
                    $this->model->rollback();
                    returnMsg(array('status'=>'04','codemsg'=>'配卡失败'));
                }
            }else{
                $this->model->rollback();
                returnMsg(array('status'=>'02','codemsg'=>'卡池数量不足'));
            }
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'03','codemsg'=>'会员创建失败'));
        }
    }
    protected function getLvCards($panterid,$cardStart){
        $map=array('panterid'=>$panterid,'status'=>'N','cardno'=>array('like',$cardStart.'%'),'cardfee'=>'0');
        $count  = $this->cards->where($map)->count();
        $rownum = rand(1,intval($count));
        // 取卡
        $card   = $this->cards->where($map)->limit($rownum,1)->field('cardno')->select();
        $map1['cardno']=$card[0]['cardno'];
        $data['status']='L';
        $bool = $this->cards->where($map1)->save($data);
        if($bool){
            return $card[0]['cardno'];
        }else{
            return false;
        }
    }

}