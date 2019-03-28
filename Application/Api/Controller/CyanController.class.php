<?php
namespace Api\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
use Home\Controller\CoinController;

class CyanController extends CoinController{
// 	protected $keycode;
// 	public function _initialize(){
//
// 	}
    //查询账户信息接口(通过会员卡)
    public function getAccount(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        // $cardno=trim($datami['cardno']);
        $customid=trim($datami['customid']);
        $linktel = trim($datami['linktel']);
        $panterid='00000219';//商户号
        $key=trim($datami['key']);
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $checkKey=md5($this->keycode.$linktel);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'02','codemsg'=>'手机号不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号格式不正确'));
        }
        //  $where = array('c.status'=>'Y','c.cardsort'=>'1');
        $customids=$des->doDecrypt($customid);
        $custo=substr($customids,0,8);
        //echo $custo;exit;
        $where['cu.linktel']=$linktel;
        $where['cc.customid']=$custo;
        $where['c.status']='Y';
//        $where['c.cardkind']='6888';
        $where['c.cardkind'] = array('in',array('6888','2336','6886'));
        $field='cu.customid,c.cardno';
        //判断有无青蓝社至尊卡
        $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards c on c.customid=cc.cid')
            ->where($where)->field($field)->select();
        //	dump($coums);exit;
        //	echo M('customs')->getLastSql();exit;
        $arr=array();
        foreach ($coums as $k=>$v){
            $arr['customid']=$coums[$k]['customid'];
        }
        //dump($arr);exit;
        if(empty($coums)){
            returnMsg(array('status'=>'04','codemsg'=>'该手机号未申请青蓝社至尊卡会员'));
        }else{
            $type='00';
            $customid=$arr['customid'];
            //	dump($customid);exit;
//            $where1=array('cu.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','c.cardkind'=>'6888','c
//            .cardsort'=>'1');
            $where1=array('cu.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','c.cardkind'=>array('in',array('2336','6888','6886')));
            $account=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')->join('account a on cc.cid=a.customid')
                ->join('cards c on cc.cid=c.customid')
                ->where($where1)->sum('a.amount');
            //	echo M('customs')->getLastSql();exit;
            if(empty($account)){
                $account=0;
            }
            returnMsg(array('status'=>'1','balance'=>floatval($account),'customid'=>$customid));

        }
//
//
//
//
//
//
//
//
//
//
//
//
//        $map=array('cardno'=>$cardno,'cardsort'=>'1');
//        $card=$this->cards->where($map)->find();
//        if($card==false){
//            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
//        }
//        if($card['status']!='Y'){
//            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
//        }
//        if($card['cardkind']!='6888'&& $card['cardsort']!='1'){
//            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
//        }
//        $custom = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
//            ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno'=>$cardno))
//            ->field('cu.namechinese')->find();
////        dump($custom);exit;
//        $customid = $this->getCustomid($cardno);
//        if($custom == false || empty($customid)){
//            returnMsg(array('status'=>'05','codemsg'=>'此卡没有关联用户'));
//        }
////        $balance=$this->cardAccQuery($cardno);
////        //------获取账户下所有卡数量
////        //$tickketList=$this->getTicketByCustomid($customid);
////        //--------end
////        returnMsg(array('status'=>'1','balance'=>floatval($balance),
////            'exdate'=>$card['exdate'],'name'=>$custom['namechinese'],'time'=>time()));
//        if(in_array($card['cardkind'],array('6888'))) {
//            $balance = $this->zzAccountQuery($customid, '00');
//        }
////        }else{
////            $balance=$this->cardAccQuery($cardno);
////        }
////        $coinAmount=$this->getCoinByCardno($cardno);
//        //------获取账户下所有卡数量
//        //$tickketList=$this->getTicketByCustomid($customid);
////        $ticketList=$this->getTicketByCardno($cardno,$panterid);
//        if($balance == false || empty($balance)){
//            $balances = 0;
//        }else{
//            $balances = $balance;
////            echo $balances;exit;
//        }
//        //--------end
//        returnMsg(array('status'=>'1','balance'=>floatval($balances),'customid'=>$customid,'time'=>time()));
    }

    //获取劵列表
//    public function getTicket(){
//        $datami = trim($_POST['datami']);
//        $this->recordData($datami);
//        $datami = json_decode($datami,1);
//        $cardno=trim($datami['cardno']);
//        $key=trim($datami['key']);
//        $checkKey=md5($this->keycode.$cardno);
//        if($checkKey!=$key){
//            $this->recoreIp();
//            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
//        }
//        $map=array('cardno'=>$cardno);
//        $card=$this->cards->where($map)->find();
//        //校验卡状态封装
//        if($card==false){
//            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
//        }
//        if($card['status']!='Y'){
//            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
//        }
//        //此功能需要改造
//        if($card['cardkind']!='6999'){
//            returnMsg(array('status'=>'04','codemsg'=>'此卡非青蓝社卡'));
//        }
//        $ticketList=$this->getTicketByCardno($cardno,'00000126');
//        returnMsg(array('status'=>'1','list'=>$ticketList));
//    }

    //获取过期劵列表
//    public function getOverDueTicket(){
//        $datami = trim($_POST['datami']);
//        $this->recordData($datami);
//        $datami = json_decode($datami,1);
//        $cardno=trim($datami['cardno']);
//        $key=trim($datami['key']);
//        $checkKey=md5($this->keycode.$cardno);
//        if($checkKey!=$key){
//            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
//        }
//        $map=array('cardno'=>$cardno);
//        $card=$this->cards->where($map)->find();
//        if($card==false){
//            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
//        }
//        if($card['status']!='Y'){
//            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
//        }
//        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
//            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
//        }
//        $ticketList=$this->getOverDueTicketByCardno($cardno,'00000126');
//        returnMsg(array('status'=>'1','list'=>$ticketList));
//    }

    //检验密码并绑卡
    public function checkPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        //$datami = json_decode('{"cardno":"6882371900000000002","key":"7c438ee62176d10fe9c8cf22c27f330e","pwd":"888888"}',1);
        $cardno=trim($datami['cardno']);
        $pwd=trim($datami['pwd']);
        $tel = trim($datami['tel']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno.$pwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6999371800000000011';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['cardkind']!='6888' && $card['cardsort']!='1'){
            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $pwd=$des->doEncrypt($pwd);
        if($pwd!=$card['cardpassword']){
            returnMsg(array('status'=>'05','codemsg'=>'密码错误'));
        }else{
            if(empty($tel)){
                returnMsg(array('status'=>'06','codemsg'=>'电话号码不能为空'));
            }
            if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$tel)){
                returnMsg(array('status'=>'03','codemsg'=>'手机号格式不正确'));
            }
            //查询卡是否已开卡
            //  $where['c.status']='Y';
            //	$where['c.cardfee']='1';
            $where['cardno']=$cardno;
            //	$where['c.cardkind']='6888';
            //	$where['c.cardsort']='1';
            $field='customid,cardno,namechinese pcname,linktel pclink,personid pcpid,address pcaddress';
            //	$cards=$this->cards->alias('c')
            //		->join('customs_c cc on cc.cid=c.customid')
            //       ->join('customs cu on cu.customid=cc.customid')
            //		->join('purcha_card pc on pc.customid=cu.customid')
            //       ->where($where)->field($field)->find();
            //dump($cards);exit;
            $cards=M('purcha_card')->where($where)->field($field)->find();
            //	echo M('cards')->getLastSql();exit;
            $coum=$cards['customid'];
            //echo $coum;exit;
            $bang=$this->customs->where(array('customid'=>$coum))->field('linktel')->find();
            $cc=$this->customs_c->where(array('cid'=>$coum))->field('customid')->find();
            //dump($cc);exit;
            if(isset($bang['linktel'])|| $cc['customid']!=$coum){
                returnMsg(array('status'=>'0900','codemsg'=>'该卡已绑卡'));
            }
            if($cards['cardno']!=$cardno){
                returnMsg(array('status'=>'090','codemsg'=>'该卡未开卡'));
            }elseif($tel!=$cards['pclink']){
                $son=M('trade_wastebooks')->where(array('cardno'=>$cardno))->select();
                if($son==true){
                    returnMsg(array('status'=>'013','codemsg'=>'该卡已消费，不能绑卡'));
                }
                $where1['c.status']='Y';
                $where1['cu.linktel']=$tel;
                $where1['c.cardkind'] = array('in',array('6888','2336','6886'));
                $field='c.cardkind,cu.customid,c.cardno';
                $links= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                    ->join('left join cards c on c.customid=cc.cid')
                    ->where($where1)->field($field)->select();
                if($links){
                    $type=true;
                }else{
                    $type=false;
                }
                returnMsg(array('status'=>'012','codemsg'=>'手机号不匹配','tel'=>$cards['pclink'],'type'=>$type));
            }else{
                // returnMsg(array('status'=>'1','codemsg'=>'检验成功'));
                //	$arr=array();
                //	foreach ($cards as $k=>$v){
                //		$arr['customid']=$cards[$k]['customid'];
                //		$arr['namechinese']=$cards[$k]['pcname'];
                //		$arr['linktel']=$cards[$k]['pclink'];
                //		$arr['personid']=$cards[$k]['pcpid'];
                //		$arr['address']=$cards[$k]['pcaddress'];
                //	}
                //查看卡系统有无此手机号的至尊卡会员
                $where1['c.status']='Y';
                $where1['cu.linktel']=$tel;
                $where1['c.cardkind'] = array('in',array('6888','2336','6886'));
                $field='c.cardkind,cu.customid,c.cardno';
                $links= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                    ->join('left join cards c on c.customid=cc.cid')
                    ->where($where1)->field($field)->select();
                //dump($links);exit;
                if($links){
                    $cardkinds = array_column($links,'cardkind');
                    //	dump($cardkinds);exit;
                    $cardkinds = array_unique($cardkinds);
                    $num=count($cardkinds);
                    $array=array();
                    $date=date('Ymd',time());
                    if($num>1){
                        //echo '1111';exit;
                        $this->think_send_mail('349409263@qq.com',$date,$tel,'手机号：'.$tel.'绑定青蓝社实体卡，手机号在卡系统存在重复的会员信息','');
                        foreach($links as $key=>$val){
                            $array['cardkind']= $links[$key]['cardkind'];
                            $array['customid'] = $links[$key]['customid'];
                            if(in_array('6886',$array)){
                                $customid=$array['customid'];break;
                            }elseif(in_array('6888',$array)){
                                $customid=$array['customid'];break;
                            }else{
                                $customid=$array['customid'];break;
                            }
                        }

                    }else{
                        foreach($links as $key=>$val){
                            $array['cardkind']= $links[$key]['cardkind'];
                            $array['customid'] = $links[$key]['customid'];
                        }
                        $customid=$array['customid'];
                        //echo $customid;exit;
                    }
                    $costomsTel="update customs_c set customid= '{$customid}' where customid='".$coum."'";
                    $customIf=$this->model->execute($costomsTel);
                }else{
                    $customid=$cards['customid'];
                    //echo $customid;exit;
                    $name=$cards['pcname'];
                    $link=$cards['pclink'];
                    $personid=$cards['pcpid'];
                    $address=$cards['pcaddress'];
                    //echo $address;exit;
                    $costomsTel = "update customs set namechinese = '{$name}',linktel= '{$link}',personid = '{$personid}',residaddress='{$address}' where customid='".$coum."'";
                    //echo $costomsTel;exit;
                    $customIf=$this->model->execute($costomsTel);
                }
                $customide=$des->doEncrypt($customid);
                //	echo $customide;exit;
                if($customIf==true){
                    returnMsg(array('status'=>'1','codemsg'=>'绑卡成功','customid'=>$customide));
                }else{
                    returnMsg(array('status'=>'001','codemsg'=>'绑卡失败'));
                }
            }
        }
    }
    //卡号密码正确绑定实体卡
    public function bingCard(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);//卡号
        $tel=trim($datami['tel']);//原来的手机号
        $linktel=trim($datami['linktel']);//新的电话号码
        //$panterid='00000219';//商户号
        $type=trim($datami['type']);
        $key=trim($datami['key']);
        //echo $key;exit;
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        //  $checkKey=md5($this->keycode.$linktel.$tel);
        $checkKey=md5($this->keycode.$cardno);
        //	echo $checkKey;exit;
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'020','codemsg'=>'卡号不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'02','codemsg'=>'新号码不能为空'));
        }
        if(empty($tel)){
            returnMsg(array('status'=>'2','codemsg'=>'原手机号码不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号格式不正确'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$tel)){
            returnMsg(array('status'=>'04','codemsg'=>'原手机号格式不正确'));
        }
        $where['linktel']=$tel;
        $where['cardno']=$cardno;
        $cards=M('purcha_card')->where($where)->field('customid')->find();
        //echo M('purcha_card')->getlastSql();exit;
        $coum=$cards['customid'];
        //dump($coum);exit;
        if($type==true){
            $where1['c.status']='Y';
            $where1['cu.linktel']=$linktel;
            $where1['c.cardkind'] = array('in',array('6888','2336','6886'));
            $field='c.cardkind,cu.customid,c.cardno';
            $links= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                ->join('left join cards c on c.customid=cc.cid')
                ->where($where1)->field($field)->select();
            //$customid=$cards['customid'];
            if($links){
                $cardkinds = array_column($links,'cardkind');
                //	dump($cardkinds);exit;
                $cardkinds = array_unique($cardkinds);
                $num=count($cardkinds);
                $array=array();
                $date=date('Ymd',time());
                if($num>1){
                    $this->think_send_mail('349409263@qq.com',$date,$linktel,'手机号：'.$linktel.'绑定青蓝社实体卡，手机号在卡系统存在重复的会员信息','');
                    foreach($links as $key=>$val){
                        $array['cardkind']= $links[$key]['cardkind'];
                        $array['customid'] = $links[$key]['customid'];
                        if(in_array('6886',$array)){
                            $customid=$array['customid'];break;
                        }elseif(in_array('6888',$array)){
                            $customid=$array['customid'];break;
                        }else{
                            $customid=$array['customid'];break;
                        }
                    }

                }else{
                    foreach($links as $key=>$val){
                        $array['cardkind']= $links[$key]['cardkind'];
                        $array['customid'] = $links[$key]['customid'];
                    }
                    $customid=$array['customid'];
                }

                $costomsTel="update customs_c set customid= '{$customid}' where customid='".$coum."'";
                $customIf=$this->model->execute($costomsTel);
            }
        }elseif($type==false){
            $namechinese=trim($datami['name']);//用户的姓名
            $personid=trim($datami['cardid']);// 证件号
            if(empty($namechinese)){
                //  $where['namechinese']=$namechinese;
                returnMsg(array('status'=>'040','codemsg'=>'用户名不能为空'));
            }
            if(empty($personid)){
                //  $where['cu.personid']=$personid;
                returnMsg(array('status'=>'05','codemsg'=>'身份证号码不能为空'));
            }
            if(!preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/",$personid)){
                returnMsg(array('status'=>'06','codemsg'=>'证件号格式不正确'));
            }
            $customid=$cards['customid'];
            $costomsTel = "update customs set namechinese = '{$namechinese}',linktel= '{$linktel}',personid = '{$personid}' where customid='".$coum."'";
            //echo $costomsTel;exit;
            $customIf=$this->model->execute($costomsTel);
        }else{
            returnMsg(array('status'=>'111','codemsg'=>'type值不能为空'));
        }
        $customide=$des->doEncrypt($customid);
        if($customIf==true){
            returnMsg(array('status'=>'1','codemsg'=>'绑卡成功','customid'=>$customide));
        }else{
            returnMsg(array('status'=>'001','codemsg'=>'绑卡失败'));
        }

    }
    //申请青蓝社电子卡
    public function eleCard(){
        $datami = trim($_POST['datami']);

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($datami) . "\n\t", "YjTbpost", "createAccount");

        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $namechinese = trim($datami['namechinese']);//用户的姓名
        $linktel=trim($datami['linktel']);//用户的电话号码
        $personid=trim($datami['personid']);//用户的身份证号码
        $panterid='00000219';//商户号
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$namechinese.$linktel.$personid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }

        if(empty($namechinese)){
            //   $where['cu.namechinese']=$namechinese;
            returnMsg(array('status'=>'02','codemsg'=>'用户名不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'电话号码不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'04','codemsg'=>'手机号格式不正确'));
        }
        if(empty($personid)){
            //  $where['cu.personid']=$personid;
            returnMsg(array('status'=>'05','codemsg'=>'身份证号码不能为空'));
        }
        if(!preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/",$personid)){
            returnMsg(array('status'=>'06','codemsg'=>'证件号格式不正确'));
        }
        //  $where = array('c.panterid'=>$panterid,'c.status'=>'Y');
        $where['_string']=" c.cardfee is null or c.cardfee='0'";
        //   $where['c.makecardid']='00000616';
        $where['cu.linktel']=$linktel;
        //    $where['c.cardkind']='6888';
        $where['c.cardkind']=array('in',array('6888','2336','6886'));
        $where['c.status']='Y';
        $field='cu.customid,c.cardno';
        //判断有无至尊电子卡
        $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
            ->join('left join cards c on c.customid=cc.cid')
            ->where($where)->field($field)->select();
        //	dump($coums);exit;
        //	echo M('customs')->getLastSql();exit;
        $array=array();
        foreach($coums as $k=>$v){
            $array['cardno']=$coums[$k]['cardno'];
            $array['customid']=$coums[$k]['customid'];
        }
        //	dump($array);exit;
        //echo M('customs')->getLastSql();exit;
        if($coums==false){
            //绑定会员信息
            $currentDate = date('Ymd',time());
            $cardArr = $this->getCardNo($panterid);
            //	dump($cardArr);exit;
            if (!$cardArr){
                returnMsg(array('status'=>'07','codemsg'=>'卡池数量不足,请联系管理'));
            }else{
                $this->model->startTrans();
                $customid = $this->getFieldNextNumber("customid");
                $bingSql = "insert into customs(customid,namechinese,personid,linktel,placeddate)";
                $bingSql .= "values('".$customid."','".$namechinese."','".$personid."','".$linktel."','".$currentDate."')";
                $this->recordError("注册SQL：" . serialize($bingSql) . "\n\t", "YjTbpost", "createAccount");
                //	echo $bingSql;exit;
                $custIf = $this->model->execute($bingSql);
                if($custIf==true){
                    //$this->model->commit();
                    $userid = $this->userid;
                    $n = $this->openCardno($cardArr,$customid,$totalmoney=0,$panterid,$userid,$isBill=null,$paymenttype='00');
                    if($n==true){
                        $cardList = M('card_purchase_logs')->where(array('card_purchaseid'=>$n))->field('cardno')
                            ->find();
                        //	dump($cardList);exit;
                        if($cardList){
                            $this->model->commit();
                            $this->recordData($datami);
                            $customide=$des->doEncrypt($customid);
                            returnMsg(array('status'=>'1','codemsg'=>'发卡成功','cardno'=>$cardList['cardno'],'customid'=>$customide));
                        }
                    }else{
                        $this->model->rollback();
                        $this->recordDerror($datami);
                        returnMsg(array('status'=>'0001','codemsg'=>'发卡失败'));
                    }
                }else{
                    $this->model->rollback();
                    $this->recordDerror($datami);
                    returnMsg(array('status'=>'001','codemsg'=>'创建会员失败'));
                }
            }
        }else{
            //echo $array['customid'];exit;
            $coua=$this->customs->where(array('customid'=>$array['customid']))->find();
            //echo M('customs')->getLastSql();exit;
            //dump($coua);exit;
            $coune=$coua['customid'];
            if(empty($coua['namechinese']) || empty($coua['personid'])){
                $upcustom="update customs set ";
                $costomsTel = "update customs set namechinese = '{$namechinese}',personid = '{$personid}',personidtype='身份证'  where customid='".$coune."'";
                //echo $costomsTel;exit;
                $customIf=$this->model->execute($costomsTel);
            }
            $customide=$des->doEncrypt($coune);
            returnMsg(array('status'=>'1','codemsg'=>'申请电子卡成功','cardno'=>$array['cardno'],'customid'=>$customide));
        }

    }
    // 在数据库获取电子卡号
    public function getCardNo($panterid,$cardkind='6888'){
        if($panterid==false){
            return false;
        }
        $where = array('panterid'=>$panterid,'status'=>'N');
        $where['_string']=" cardfee is null or cardfee='0'";
        $where['cardkind']=$cardkind;
        $where['makecardid']='00000617';//青蓝社电子卡制卡专用 工具2000张 制卡流水号 00000617
        $cardList = $this->cards->where($where)->field('cardno')->limit(0,1)->select();
        if(count($cardList)<=0){
            return false;
        }else{
            $list=$this->serializeArr($cardList,'cardno');
            return $list;
        }
    }
    //开卡执行
    protected function openCardno($cardArr,$customid,$amount=0,$panterid=null,$userid,$isBill=null,$paymenttype='现金'){
        if(empty($cardArr)) return false;
        $rechargedAmount=0;
        $purchaseArr=array();
        foreach($cardArr as $val){
            $waitMoney=$amount-$rechargedAmount;
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');
            $where['cardno']=$cardno;
            $cardinfo=M('cards')->where($where)->field('panterid')->find();
            if($amount==0){
                $rechargeMoney=0;
            }else{
                if($waitMoney>=5000){
                    $rechargeMoney=5000;
                }else{
                    $rechargeMoney=$waitMoney;
                }
            }
            //写入购卡单并审核
            $customplSql="insert into custom_purchase_logs(CUSTOMID,PURCHASEID,PLACEDDATE,PAYMENTTYPE,USERID,AMOUNT,CARD_NUM,TOTALMONEY,";
            $customplSql.="POINT,REALAMOUNT,WRITEAMOUNT,WRITENUMBER,CHECKNO,DESCRIPTION,FLAG,PANTERID,CHECKID,DESCRIPTION1,";
            $customplSql.="TRADEFLAG,CUSTOMFLAG,DAYFLAG,TERMPOSNO,PLACEDTIME,CHECKDATE,CHECKTIME,ADITID) ";
            $customplSql.="VALUES('".$customid."','".$purchaseid."','".$currentDate."','{$paymenttype}','";
            $customplSql.=$userid."','{$rechargeMoney}',NULL,'{$rechargeMoney}',0,'{$rechargeMoney}','{$rechargeMoney}'";
            $customplSql.=",1,'','购卡','1','{$cardinfo['panterid']}','".$userid."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
            $customplIf=$this->model->execute($customplSql);
            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs(auditid,purchaseid,TYPE,decription,placeddate,audituser,placedtime) values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

            $auditlogsIf=$this->model->execute($auditlogsSql);

            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            //写入购卡充值单
            $cardplSql="INSERT INTO  card_purchase_logs(CARD_PURCHASEID,PURCHASEID,CARDNO,AMOUNT,POINT,PLACEDDATE,PLACEDTIME,";
            $cardplSql.="FLAG,DESCRIPTION,USERID,PANTERID,IP,TERMINAL_ID) VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台开卡',";
            $cardplSql.="'{$userid}','{$cardinfo['panterid']}','','00000000')";
            $cardplIf=$this->model->execute($cardplSql);

            $where1['customid']=$customid;
            $card=$this->cards->where($where1)->find();
            if($card==false){
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                $cardId=$customid;
            }else{
                //若存在，则需另外生成卡编号
                //$cardId=$this->getnextcode('customs',8);
                $cardId=$this->getFieldNextNumber("customid");
                $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
                $customIf=$this->model->execute($customSql);
            }
            //echo $cardId;exit;
            //执行激活操作
            $cardAlSql="INSERT INTO card_active_logs(CARDNO,USERID,EXDATE,CARDBALANCE,STATUS,LINKTEL,ACTIVEDATE,ACTIVETIME,DESCRIPTION,CUSTOMID,PANTERID,TERMINAL_ID) ";
            $cardAlSql.=" VALUES('{$cardno}','{$userid}',".date('Ymd');
            $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
            $cardAlSql.=",'{$cardinfo['panterid']}','00000000')";
            $cardAlIf=$this->model->execute($cardAlSql);
            //echo $cardAlSql;exit;
            //关联会员卡号
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
            $customsIf=$this->model->execute($customcSql);

            //青蓝社至尊卡 更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+5 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}',cardsort='1' where cardno='".$cardno."'";
            $cardIf=$this->model->execute($cardSql);
            //echo $this->model->getLastSql();exit;

            //给卡片添加账户并给账户充值
            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
            $balanceIf=$this->model->execute($balanceSql);

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $coinSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $coinSql.=$acid."','".$cardId."','0','01',NULL)";
            $coinIf=$this->model->execute($coinSql);

            //$acid = $this->getnextcode('account',8);
            $acid = $this->getFieldNextNumber('accountid');
            $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $pointSql.=$acid."','".$cardId."','0','04',NULL)";
            $pointIf=$this->model->execute($pointSql);

            if($this->checkCardBrand($cardno)==true){
                if($isBill==1){
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',1,0,0)";
                }else{
                    $billingSql="INSERT INTO BILLING VALUES ('{$cardpurchaseid}','{$cardno}',0,0,0)";
                }
                $billingIf=$this->model->execute($billingSql);
            }else{
                $billingIf=true;
            }
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$pointIf==true&&$coinIf==true&&$billingIf==true){
                $rechargedAmount+=$rechargeMoney;
                $purchaseArr[]=$cardpurchaseid;
            }
        }
        if($rechargedAmount==$amount){
            return $cardpurchaseid;
        }else{
            return false;
        }
    }
    public function checkCardBrand($cardno){
        $brandid=substr($cardno,0,4);
        if($brandid!='6888'){
            return false;
        }else{
            return true;
        }
    }
    //消费列表
    public function getTreadelist(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6999371800000000011';
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
        }
        if($card['cardkind']!='6888' && $card['cardsort']!='1'){
            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
        }
        $where['c.cardno']=$cardno;
        $where['c.cardkind']='6888';
        $where['tw.flag']=0;
        $where['tw.tradetype']=array('in','00,13,17,21,07');
        $where['tw.tradeamount']=array('neq',0);
//        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
//            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','13','17','21')),'tw.flag'=>0))
//            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
        $tradelist=M('trade_wastebooks')->alias('tw')
            ->join('left join panters p on p.panterid=tw.panterid')
            ->join('left join cards c on c.cardno=tw.cardno')
            ->join('left join customs cc on cc.cid=c.customid')
            ->join('left join customs cu on  cu.customid=cc.customid')
            ->where($where)
            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
        $list=array();
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        if($tradelist!=false){
            foreach($tradelist as $key=>$val){
                $list[$key]['tradeid']=trim($val['tradeid']);
                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
                $list[$key]['tradetype']=$jytype[$val['tradetype']];
                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
                $list[$key]['pname']=$val['pname'];
            }
            returnMsg(array('status'=>'1','list'=>$list));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
        }
    }

//    public function getAccountlist(){
//    	$datami = trim($_POST['datami']);
//    	$this->recordData($datami);
//    	$datami = json_decode($datami,1);
//    	$cardno=trim($datami['cardno']);
//    	$key=trim($datami['key']);
//    	$checkKey=md5($this->keycode.$cardno);
//    	if($checkKey!=$key){
//    		returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
//    	}
////    	$cardno='6999371800000000011';
//    	$map=array('cardno'=>$cardno);
//    	$card=$this->cards->where($map)->find();
//    	if($card==false){
//    		returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
//    	}
//    	if($card['status']!='Y'){
//    		returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
//    	}
//        if($card['cardkind']!='6888' && $card['cardsort']!='1'){
//            returnMsg(array('status'=>'04','codemsg'=>'非青蓝社卡'));
//        }
//    	$tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
//    	->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in',array('00','13','17','21')),'tw.flag'=>0))
//    	->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradetype,p.namechinese pname')->select();
//    	$list=array();
//    	$jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
//    	if($tradelist!=false){
//    		foreach($tradelist as $key=>$val){
//    			$list[$key]['tradeid']=trim($val['tradeid']);
//    			$list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
//    			$list[$key]['tradetype']=$jytype[$val['tradetype']];
//    			$list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
//    			$list[$key]['pname']=$val['pname'];
//    		}
//    		returnMsg(array('status'=>'1','list'=>$list));
//    	}else{
//    		returnMsg(array('status'=>'05','codemsg'=>'无记录'));
//    	}
//    }

    //获取劵消费列表
//    public function getQuanConsumelist(){
//        $datami = trim($_POST['datami']);
//        $this->recordData($datami);
//        $datami = json_decode($datami,1);
//        $cardno=trim($datami['cardno']);
//        $key=trim($datami['key']);
//        $checkKey=md5($this->keycode.$cardno);
//        if($checkKey!=$key){
//            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
//        }
//        $map=array('cardno'=>$cardno);
//        $card=$this->cards->where($map)->find();
//        if($card==false){
//            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
//        }
//        if($card['status']!='Y'){
//            returnMsg(array('status'=>'03','codemsg'=>'非正常卡号'));
//        }
//        if($card['cardkind']!='6882'&&$card['cardkind']!='2081'){
//            returnMsg(array('status'=>'04','codemsg'=>'非酒店卡'));
//        }
//        $tradelist=M('trade_wastebooks')->alias('tw')->join('panters p on p.panterid=tw.panterid')
//            ->join('quankind q on q.quanid=tw.quanid')
//            ->where(array('tw.cardno'=>$cardno,'tw.tradetype'=>array('in','02'),'tw.flag'=>0))
//            ->field('tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,p.namechinese pname,q.quanname')->select();
//        $list=array();
//        if($tradelist!=false){
//            foreach($list as $key=>$val){
//                $list[$key]['tradeid']=trim($val['tradeid']);
//                $list[$key]['tradeamount']=trim(floatval($val['tradeamount']));
//                $list[$key]['datetime']=date('Y-m-d',strtotime($val['placeddate'].' '.$val['placedtime']));
//                $list[$key]['pname']=$val['pname'];
//                $list[$key]['quanname']=$val['quannames'];
//            }
//            returnMsg(array('status'=>'1','list'=>$list));
//        }else{
//            returnMsg(array('status'=>'05','codemsg'=>'无记录'));
//        }
//    }
// 修改密码
    public function editPwd(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        //$data=json_decode('{"cardno":"6882371900000000002","oldpwd":"YW1ob3RlbDg4ODg4OHpoaXp1bg==","newpwd":"YW1ob3RlbDEyMzQ1NnpoaXp1bg==","key":"0839ab6c739c391fe5fdc8f9e33663c2"}',1);
        $data = json_decode($datami,1);
        $cardno=trim($data['cardno']);
        $oldpwd=trim($data['oldpwd']);
        $newpwd=trim($data['newpwd']);
        $key=trim($data['key']);
        $checkKey=md5($this->keycode.$cardno.$oldpwd.$newpwd);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map=array('cardno'=>$cardno);
        $card=$this->cards->where($map)->find();
        if($card==false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        $oldpwd=$this->decodePwd($oldpwd,'amhotel','zhizun');
        $newpwd=$this->decodePwd($newpwd,'amhotel','zhizun');
        if($oldpwd==false||$newpwd==false){
            returnMsg(array('status'=>'03','codemsg'=>'非法密码传入'));
        }
        if(!preg_match("/\d{6}$/",$newpwd)){
            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
        }
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $oldpwd=$des->doEncrypt($oldpwd);
        if($oldpwd!=$card['cardpassword']){
            returnMsg(array('status'=>'05','codemsg'=>'旧密码校验错误'));
        }
        $newpwd=$des->doEncrypt($newpwd);
        $sql="update cards set cardpassword='{$newpwd}' where cardno='{$cardno}'";
        if($this->cards->execute($sql)){
            returnMsg(array('status'=>'1','codemsg'=>'修改成功'));
        }else{
            returnMsg(array('status'=>'06','codemsg'=>'修改失败'));
        }
    }

    //充值记录和消费记录
    public function getRechargeList(){
        //参数校验
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $linktel=trim($datami['linktel']);//用户的电话号码
        $customid=trim($datami['customid']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$linktel);
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $customids=$des->doDecrypt($customid);
        $custo=substr($customids,0,8);
//    	dump($checkKey);exit;
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($linktel)){
            $where['c.linktel']=$linktel;
            returnMsg(array('status'=>'02','codemsg'=>'手机号不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'03','codemsg'=>'手机号格式不正确'));
        }
        $field = 'b.purchaseid purchaseid,a.placeddate placeddate,a.placedtime placedtime,p.namechinese pname,b.amount amount';
        $where['card.cardkind']= array('in',array('6888','2336','6886'));
        //   $where['card.cardsort']= '1';
        $where['f.customid']=$custo;
        // $where['a.paymenttype']=array('in',array('现金','00'));
        $where['a.amount']=array('gt',0);
        //dump($where);exit;
        $recharge_list = $this->model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
            ->join('left join __CARDS__ card on card.cardno=b.cardno')
            ->join('left join __PANTERS__ p on p.panterid=b.panterid')
            ->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
            ->join('left join __CUSTOMS__ c on c.customid=f.customid')
            ->join('left join __USERS__ u on u.userid=b.userid')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.placeddate desc')->select();
        //	echo $recharge_list;exit;
        $array = array('type' => '1');
        $fields = 'tw.tradeid purchaseid,tw.placeddate placeddate,tw.placedtime placedtime,p.namechinese pname,tw.tradeamount amount';
        //  $where1['c.cardsort']= '1';
        $where1['c.cardkind']= array('in',array('6888','2336','6886'));
        $where1['cc.customid']=$custo;
        $where1['tw.flag']=0;
        $where1['tw.tradetype']=array('in','00,13,17,21,07');
        $where1['tw.tradeamount']=array('neq',0);
        $treade_list=M('trade_wastebooks')->alias('tw')
            ->join('left join panters p on p.panterid=tw.panterid')
            ->join('left join cards c on c.cardno=tw.cardno')
            ->join('left join customs_c cc on cc.cid=c.customid')
            ->join('left join customs cu on  cu.customid=cc.customid')
            ->where($where1)->field($fields)->order('tw.placeddate desc')->select();
        $arrays = array('type' => '2');
        foreach ($recharge_list as $key=>$val){
            $recharge_list[$key]['type']=$array['type'];
            unset($recharge_list[$key]['numrow']);
        }
        foreach ($treade_list as $k=>$v){
            $treade_list[$k]['type']=$arrays['type'];
            unset($treade_list[$k]['numrow']);
        }
        if($recharge_list || $treade_list){
            $total_list=array_merge($recharge_list,$treade_list);
//            dump($total_list);exit;
            returnMsg(array('status'=>'1','list'=>$total_list));
        }else{
            returnMsg(array('status'=>'06','codemsg'=>'获取信息失败'));
        }
    }
    //账户的信息查询
    public function accountInfo(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $linktel=trim($datami['tel']);//用户的电话号码
        $namechinese =trim($datami['name']);
        $type=trim($datami['type']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$type);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $field='cu.customid,sum(ac.amount) amount,cu.namechinese,cu.personid,cu.linktel,cu.placeddate';
        if($type=1 ||$type=''){

            $where1=array('ac.type'=>'00','ca.status'=>'Y','ca.cardkind'=>'6888','ca.cardsort'=>'1');
            //    $account=M('customs')->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
            //        ->join('left join cards c on c.customid=cc.cid')
            //        ->join('left join account a on cc.cid=a.customid')
            //       ->where($where1)->field($field)->group('cu.customid')->select();
            $account= M('cards')->alias('ca')->join('left join account ac on ac.customid=ca.customid')
                ->join('left join customs_c cc on cc.cid=ca.customid')
                ->join('left join customs cu on cu.customid=cc.customid')
                ->where($where1)->field($field)->group('cu.customid,cu.namechinese,cu.personid,cu.linktel,cu.placeddate')->select();

            //	dump($account);exit;
        }
        if($type=2){
            //判断有无青蓝社至尊卡
            $where=array('ca.status'=>'Y','ca.cardkind'=>'6888','ca.cardsort'=>'1');
            if($linktel !=''){
                $where['cu.linktel']=$linktel;
                $field1='cu.customid,ca.cardno';
                $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                    ->join('left join cards ca on ca.customid=cc.cid')
                    ->where($where)->field($field1)->select();
                //echo M('customs')->getLastSql();exit;
                if(empty($coums)){
                    returnMsg(array('status'=>'03','codemsg'=>'该手机号未申请青蓝社会员'));
                }
            }
            //判断有无用户名
            if($namechinese !=''){
                $where['cu.namechinese']=$namechinese;
                $field1='cu.customid,ca.cardno';
                $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                    ->join('left join cards ca on ca.customid=cc.cid')
                    ->where($where)->field($field1)->select();
                //echo M('customs')->getLastSql();exit;
                if(empty($coums)){
                    returnMsg(array('status'=>'04','codemsg'=>'该用户未申请青蓝社会员'));
                }
            }
            $where['ac.type']='00';
            $account= M('cards')->alias('ca')->join('left join account ac on ac.customid=ca.customid')
                ->join('left join customs_c cc on cc.cid=ca.customid')
                ->join('left join customs cu on cu.customid=cc.customid')
                ->where($where)->field($field)->group('cu.customid,cu.namechinese,cu.personid,cu.linktel,cu.placeddate')->select();
            // echo M('cards')->getLastSql();exit;
        }
        if($account==false){
            returnMsg(array('status'=>'02','codemsg'=>'暂无数据'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'获取数据成功','list'=>$account));
        }

    }
    //卡号的信息查询
    public function cardInfo(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $linktel=trim($datami['tel']);//用户的电话号码
        $namechinese =trim($datami['name']);
        $cardno=trim($datami['cardno']);
        $type=trim($datami['type']);
        //dump($type);exit;
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$linktel.$namechinese.$cardno);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $field='puc.linktel,c.cardno,puc.namechinese,puc.personid,cp.placeddate';
        if($type=1|| $type=''){
            $where=array('c.status'=>'Y','c.cardkind'=>'6888','c.cardsort'=>'1','c.cardfee'=>'1','cp.tradeflag'=>0);
            // $custom=M('customs')->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
            //     ->join('left join cards c on c.customid=cc.cid')
            //     ->join('left join card_purchase_logs cpl on cpl.cardno=c.cardno')
            //     ->join('left join custom_purchase_logs cp on cp.purchaseid=cpl.purchaseid')
            //     ->where($where)->field($field)->select();

            $custom=M('purcha_card')->alias('puc')
                ->join('left join cards c on c.cardno=puc.cardno')
                ->join('left join card_purchase_logs cpl on cpl.cardno=c.cardno')
                ->join('left join custom_purchase_logs cp on cp.purchaseid=cpl.purchaseid')
                ->where($where)->field($field)->select();
        }
        if($type=2){
            $where=array('c.status'=>'Y','c.cardkind'=>'6888','c.cardsort'=>'1','c.cardfee'=>'1');
            if($linktel !=''){
                $where['puc.linktel']=$linktel;
                //    $field1='cu.customid,c.cardno';
                //判断有无青蓝社至尊卡
                // $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                //     ->join('left join cards c on c.customid=cc.cid')
                //     ->where($where)->field($field1)->select();
                //echo M('customs')->getLastSql();exit;
                //	$coums = M('purcha_card')->where($where1)->find();
                //   if(empty($coums)){
                //        returnMsg(array('status'=>'03','codemsg'=>'该手机号没有购青蓝社会员卡'));
                //   }

            }
            if($cardno !=''){
                $where['c.cardno']=$cardno;
                //  $field1='c.cardno';
                //判断有无青蓝社至尊卡
                //  $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                //     ->join('left join cards c on c.customid=cc.cid')
                //      ->where($where)->field($field1)->select();
                //echo M('customs')->getLastSql();exit;
                //  if(empty($coums)){
                //     returnMsg(array('status'=>'05','codemsg'=>'此卡非青蓝社卡或无此卡号'));
                //  }
            }
            //判断有无用户名
            if($namechinese !=''){
                $where['puc.namechinese']=$namechinese;
                //  $field1='cu.customid,c.cardno';
                //   $coums= $this->customs->alias('cu')->join('left join customs_c cc on cc.customid=cu.customid')
                //       ->join('left join cards c on c.customid=cc.cid')
                //      ->where($where)->field($field1)->select();
                //echo M('customs')->getLastSql();exit;
                //  if(empty($coums)){
                //       returnMsg(array('status'=>'04','codemsg'=>'该用户未申请青蓝社会员'));
                //   }
            }
            $where['cp.tradeflag']=0;
            //dump($where);exit;
            $custom=M('purcha_card')->alias('puc')
                ->join('left join cards c on c.cardno=puc.cardno')
                ->join('left join card_purchase_logs cpl on cpl.cardno=c.cardno')
                ->join('left join custom_purchase_logs cp on cp.purchaseid=cpl.purchaseid')
                ->where($where)->field($field)->select();
            //dump($custom);exit;
            //	echo M('customs')->getLastSql();exit;
        }
        if($custom==false){
            returnMsg(array('status'=>'02','codemsg'=>'暂无数据'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'获取数据成功','list'=>$custom));
        }


    }
    //查询账户下的卡数量
    protected function customCardNum($customid){
        $where['cc.customid']=$customid;
        $c=$this->customs_c->alias('cc')->join('cards c on cc.id=c.customid')->where($where)->count();
        return $c;
    }
    //查询单张卡片的账户余额
    protected function cardAccQuery($cardno){
        $where=array('c.cardno'=>$cardno,'a.type'=>'00','c.cardsort'=>'1');
        $cardAccount=$this->cards->alias('c')->join('account a on a.customid=c.customid')
            ->where($where)->field('a.amount')->find();
        $amount=empty($cardAccount['amount'])?0:$cardAccount['amount'];
        return $amount;
    }
    //查询会员账户余额
    protected function zzAccountQuery($customid,$type){
        $where=array('cc.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','c.cardkind'=>'6888','c.cardsort'=>'1');
        $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->sum('a.amount');
        if(empty($account)) $account=0;
        return $account;
    }
    //查询会员账户余额
    protected function accountQuery($customid,$type,$cardno=null){
        $where=array('cc.customid'=>$customid,'a.type'=>$type,'c.status'=>'Y','a.amount'=>array('gt',0));
        $where['_string']=" c.cardno not in (select cardno from cards  where cardkind in ('6889','6882','2081') and (cardfee=0 or cardfee is null)) and c.status='Y'";
        if(!empty($cardno)){
            $cardkind=substr($cardno,0,4);
            $where['c.cardkind']=$cardkind;
        }
        $account=$this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')
            ->join('cards c on cc.cid=c.customid')
            ->where($where)->sum('a.amount');
        if(empty($account)) $account=0;
        return $account;
    }
    protected function recordData($data,$flag){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Api/interface/blue/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Api/interface/bcard/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    protected function recordDerror($data,$flag){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        if(!empty($flag)){
            $path=PUBLIC_PATH.'Api/interface/blueer/'.$month.'/';
        }else{
            $path=PUBLIC_PATH.'Api/interface/bcarder/'.$month.'/';
        }
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }
    public function editTel(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $cardno=trim($datami['cardno']);
        $tel=trim($datami['tel']);
        $linktel= trim($datami['linktel']);
        $key=trim($datami['key']);
        $checkKey=md5($this->keycode.$linktel);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($cardno)){
            returnMsg(array('status'=>'02','codemsg'=>'卡号不能为空'));
        }
        if(empty($tel)){
            returnMsg(array('status'=>'03','codemsg'=>'原手机不能为空'));
        }
        if(empty($linktel)){
            returnMsg(array('status'=>'04','codemsg'=>'新手机不能为空'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$linktel)){
            returnMsg(array('status'=>'05','codemsg'=>'新手机号格式不正确'));
        }
        if(!preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/",$tel)){
            returnMsg(array('status'=>'06','codemsg'=>'原手机号格式不正确'));
        }
        $linkt=M('purcha_card')->where(array('cardno'=>$cardno))->find();
        if($tel!=$linkt['linktel']){
            returnMsg(array('status'=>'07','codemsg'=>'原手机号不对'));
        }
        //	echo M('cards')->getLastSql();exit;
        $coum=$linkt['customid'];
        //	echo $coum;exit;
        $bang=$this->customs->where(array('customid'=>$coum))->field('linktel')->find();
        //dump($bang);exit;
        $cc=$this->customs_c->where(array('cid'=>$coum))->field('customid')->find();
        //dump($cc);exit;
        if(isset($bang['linktel'])|| $cc['customid']!=$coum){
            //	echo '111';exit;
            returnMsg(array('status'=>'08','codemsg'=>'该卡已绑卡'));
        }
        //dump($cc);exit;
        //echo $cardno;exit;
        $up="update purcha_card set linktel='{$linktel}' where cardno='".$cardno."'";
        $uplink=$this->model->execute($up);
        if($uplink==true){
            returnMsg(array('status'=>'1','codemsg'=>'更改手机号成功'));
        }else{
            returnMsg(array('status'=>'2','codemsg'=>'更改手机号失败'));
        }



    }
    public function ddel(){
        //$customid='4767141131F5B893';
        //$des=new Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        //  $customids=$des->doDecrypt($customid);
        //  $custom=substr($customids,0,8);
        //	echo $custom;exit;
        $customid='00632260';
        $des=new Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $customids=$des->doEncrypt($customid);
        echo $customids;exit;
    }
    public function payCustom(){
        $datami = trim($_POST['datami']);
        $this->recordData($datami);
        $datami = json_decode($datami,1);
        $customid=trim($datami['customid']);
        $key=trim($datami['key']);
        //echo $key;exit;
        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $checkKey=md5($this->keycode.$customid);
        if($checkKey!=$key){
            $this->recoreIp();
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号不能为空'));
        }
        $customids=$des->doDecrypt($customid);
        $custo=substr($customids,0,8);
        $customs=$this->customs->where(array('customid'=>$custo))->field('paypwd')->find();
        if($customs==false){
            returnMsg(array('status'=>'03','codemsg'=>'获取失败'));
        }
        if(empty($customs['paypwd'])){
            returnMsg(array('status'=>'04','codemsg'=>'支付密码为空'));
        }else{
            returnMsg(array('status'=>'1','codemsg'=>'已有支付密码'));
        }
    }
}
