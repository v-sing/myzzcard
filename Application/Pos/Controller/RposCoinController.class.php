<?php
namespace Pos\Controller;
use Think\Controller;
use Think\Model;
header("content-type:text/html;charset=utf-8");
class RposCoinController extends CoinController{
    protected $keycode='JYO2O01';

    //劵列表查询接口
    public function ticketsQerry(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $page = trim($datami['page']);
        $countnum = trim($datami['countnum']);
        $sign=trim($datami['sign']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$page.$countnum.$sign);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $this->Nusershare($customid);
        if($sign==1){//君邻会劵
            $tickketList = $this->jlhTicketByCustomid($customid,$countnum,$page); //查找卡券的id/卡券的名称/卡券的数/过期时间
        }else{
            $tickketList = $this->ticketByCustomid($customid,$countnum,$page); //查找卡券的id/卡券的名称/卡券的数/过期时间
        }
        if($tickketList==false){
            $tickketList = array();
        }
        $totalCount=$tickketList['0']['count'];
        returnMsg(array('status'=>'1','ticketList'=>$tickketList,'totalCount'=>$totalCount,'pageNo'=>$page,'pageSize'=>$countnum,'time'=>time()));
    }

    //判断是否有分享未使用的卡卷
    public function Nusershare($customid){
        $sharesearch = M('share_wastebooks')->alias('sw')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where(array('cc.customid'=>$customid,'sw.flag'=>0))
            ->select();
        foreach($sharesearch as $key=>$vv){
            $exsharedata=strtotime($vv['placeddate']. $vv['placedtime'])+(trim($vv['exdate'])*24*60*60);
            if($exsharedata<time()){
                $this->backShareTicket($vv['shareid']);
            }
        }
    }

    //查询卡券列表中卡券信息（查找卡券id/卡券名称/卡券数/有效期）
    protected function jlhTicketByCustomid($customid,$countnum,$page){
        $where['cc.customid']=$customid;
        $where['c.panterid']='00000447';
        $where['c.status']='Y';
        $where['qa.amount']=array("neq","0");
        $quanaccount=M('quan_account');
        $count=$quanaccount->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,qa.amount,qa.quanid,qa.enddate,q.atype')
            ->where($where)->count();
        if(isset($page)){
            $newpage=intval($page);
        }
        if($newpage<=0){
            $newpage=1;
        }
        $offset=($newpage-1)*$countnum;
        $list=$quanaccount->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,qa.amount,qa.quanid,qa.enddate,q.atype,qa.accountid')
            ->where($where)->order("q.sort ASC,q.enddate ASC")->select();
        $pagedata=array_slice($list,$offset,$countnum);
        if($pagedata==false){
            return false;
        }else{
            foreach($pagedata as $key=>$val){
                $enddata=strtotime($val['enddate']);
                $ddd=date('Y-m-d',time());
                $nowdata=strtotime($ddd);
                if($enddata<$nowdata){
                    $ticketList[$key]['exflag']="1";
                    $ticketList[$key]['putuse']="已过期";
                }else{
                    $ticketList[$key]['exflag']="0";
                    $ticketList[$key]['putuse']="未过期";
                }
                $ticketList[$key]['quanid']=$val['quanid'];
                $ticketList[$key]['accountid']=$val['accountid'];
                $ticketList[$key]['quanname']=$val['quanname'];
                $ticketList[$key]['amount']=$val['amount'];
                $datetime=date("Y年m月d日",strtotime($val['enddate']));
                $ticketList[$key]['enddate']=$datetime;
                $ticketList[$key]['count']=$count;
            }
            return $ticketList;
        }
    }

    //查询卡券列表的中卡券的信息（查找卡券的id/卡券的名称/卡券的数/有效期的时间）
    protected function ticketByCustomid($customid,$countnum,$page){
        $where['cc.customid']=$customid;
        $where['a.type']='02';
        $where['a.amount']=array("neq","0");
        $count=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,a.quanid,q.enddate')
            ->where($where)->count();
        if(isset($page)){
            $newpage=intval($page);
        }
        if($newpage<=0){
            $newpage=1;
        }
        $offset=($newpage-1)*$countnum;
        $where1['cc.customid']=$customid;
        $where1['a.type']='02';
        $where1['a.amount']=array("neq","0");
        $where1['q.enddate']=array('egt',date('Ymd',time()));

        $list=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,a.quanid,q.enddate')
            ->where($where1)->order("enddate ASC,amount asc")->select();
        //查询过期的
        $where2['cc.customid']=$customid;
        $where2['a.type']='02';
        $where2['a.amount']=array("neq","0");
        $where2['q.enddate']=array('elt',date('Ymd',time()));
        $listexflag=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->field('q.quanname,a.amount,a.quanid,q.enddate')
            ->where($where2)->order("enddate DESC")->select();
        $listnew = array();
        foreach($list as $key=>$val){
            $listnew[] = $val;
        }
        foreach($listexflag as $key=>$val){
            $listnew[] = $val;
        }
        $pagedata=array_slice($listnew,$offset,$countnum);
        if($pagedata==false){
            return false;
        }else{
            foreach($pagedata as $key=>$val){
                if(isset($ticketList[$key]['quanid'])){
                    $ticketList[$key]['quanid']+=$val['amount'];
                }else{
                    $enddata=strtotime($val['enddate']);
                    $ddd=date('Y-m-d',time());
                    $nowdata=strtotime($ddd);
                    if($enddata<$nowdata){
                        $ticketList[$key]['exflag']="1";
                    }else{
                        $ticketList[$key]['exflag']="0";
                    }
                    $ticketList[$key]['quanid']=$val['quanid'];
                    $ticketList[$key]['quanname']=$val['quanname'];
                    $ticketList[$key]['amount']=$val['amount'];
                    $strtime=strtotime($val['enddate']);
                    if($strtime<time()){
                        $ticketList[$key]['putuse']="已过期";
                    }else{
                        $ticketList[$key]['putuse']="未过期";
                    }
                    $ticketList[$key]['enddate']=date("Y年m月d日",$strtime);
                    $ticketList[$key]['count']=$count;
                }
            }
            $item=array();
            foreach($ticketList as $k=>$v){
                if(!isset($item[$v['quanid']])){
                    $item[$v['quanid']]=$v;
                }else{
                    $item[$v['quanid']]['amount']+=$v['amount'];
                }
            }
            $zz=array();
            foreach($item as $vv){
                $zz[]=$vv;
            }
            return $zz;
        }
    }

    //消费的列表的接口
    public function expenseQerry(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $page = trim($datami['page']);
        $countnum = trim($datami['countnum']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$page.$countnum);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $expenseList = $this->getConsumeList($customid,$page,$countnum); //查找卡券的id/卡券的名称/卡券的
        if($expenseList==false){
            $expenseList = array();
        }
        $totalCount=$expenseList['0']['count'];
        returnMsg(array('status'=>'1','expenseList'=>$expenseList,'totalCount'=>$totalCount,'pageNo'=>$page,'pageSize'=>$countnum,'time'=>time()));

    }

    //获取卡券消费信息
    public function getConsumeList($customid,$page,$countnum){
        $where=array('cu.customid'=>$customid,'c.status'=>'Y');
        $cards=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($where)->select();
        $cards || die(json_encode(array('status'=>50,'codemsg'=>'卡号丢失。。。。')));
        $cardArr = $this->serializeArr($cards,'cardno');
        $cardArr = implode(',',$cardArr);
        $where1=" tw.tradetype='02' and tw.flag=0 and tw.cardno in({$cardArr})";
        $count=M("trade_wastebooks")->alias('tw')->join('quankind  q on tw.quanid=q.quanid')
            ->where($where1)
            ->field('tw.quanid as quanid,q.quanname as quanname,q.enddate as enddate,tw.placeddate as placeddate,tw.placedtime as placedtime,tw.tradeAmount as tradeAmount')
            ->count();
        if(isset($page)){
            $newpage=intval($page);
        }
        if($newpage<=0){
            $newpage=1;
        }
        $offset=($newpage-1)*$countnum;
        $quaninfo=M("trade_wastebooks")->alias('tw')->join('quankind  q on tw.quanid=q.quanid')
            ->join('panters p on p.panterid=tw.panterid')
            ->where($where1)
            ->field('p.namechinese as namechinese,tw.quanid as quanid,q.quanname as quanname,q.enddate as enddate,tw.placeddate as placeddate,tw.placedtime as placedtime,tw.tradeamount as tradeamount')
            ->limit($offset,$countnum)
            ->order("placeddate desc,placedtime desc")
            ->select();
        if($quaninfo==false){
            return '0';
        }else{
            foreach($quaninfo as $key=>$vv){
                $data[$key]['quanid']=$vv['quanid'];
                $data[$key]['quanname']=$vv['quanname'];
                $strtime=strtotime($vv['enddate']);
                $datetime=date("Y年m月d日",$strtime);
                $data[$key]['enddate']=$datetime;
                $placeddate=$vv['placeddate'];
                $placedtime=$vv['placedtime'];
                $place=$placeddate.$placedtime;
                $placestime=strtotime($place);
                $dateplaces=date("Y年m月d日H:i",$placestime);
                $data[$key]['placedalldate']=$dateplaces;
                $data[$key]['tradeamount']=$vv['tradeamount'];
                $data[$key]['count']=$count;
                $data[$key]['putuse']="已使用";
                $data[$key]['namechinese']=$vv['namechinese'];
            }
            return $data;
        }
    }

    //分享券列表接口
    public function shareQerry(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $page = trim($datami['page']);
        $countnum = trim($datami['countnum']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$page.$countnum);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $this->Nusershare($customid);
        $shareList = $this->getshareList($customid,$page,$countnum); //查找卡券的id/卡券的名称/卡券的
        if($shareList==false){
            $shareList = array();
        }
        $totalCount=$shareList['0']['count'];
        returnMsg(array('status'=>'1','shareList'=>$shareList,'totalCount'=>$totalCount,'pageNo'=>$page,'pageSize'=>$countnum,'time'=>time()));
    }

    //获取卡券的分享的信息
    public function getshareList($customid,$page,$countnum){
        $where1=array('cc.customid'=>$customid);
        $count=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where($where1)
            ->count();
        if(isset($page)){
            $newpage=intval($page);
        }
        if($newpage<=0){
            $newpage=1;
        }
        $offset=($newpage-1)*$countnum;
        $where2=array('cc.customid'=>$customid);
        $where2['flag']=array("eq","0");
        $quaninfo=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where($where2)
            ->order('placeddate desc,placedtime desc')->select();

        $where3=array('cc.customid'=>$customid);
        $where3['flag']=array("neq","0");
        $quaninfoext=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where($where3)
            ->order('flag asc,placeddate asc,placedtime asc')->select();
        $listnew = array();
        foreach($quaninfo as $key=>$val){
            $listnew[] = $val;
        }
        foreach($quaninfoext as $key=>$val){
            $listnew[] = $val;
        }
        $pagedata=array_slice($listnew,$offset,$countnum);
        $ticketList=array();
        if($pagedata==false){
            return '0';
        }else{
            foreach($pagedata as $key=>$val){
                $sharedate=$val['placeddate']. $val['placedtime'];//分享的时间
                //过期的时间
                $exdate=trim($val['exdate']);
                $exsharedata=strtotime($sharedate)+($exdate*24*60*60);
                if($exsharedata<time()){
                    $ticketList[$key]['putuse']="已过期";
                }else{
                    if($val['flag']=="2"){
                        $ticketList[$key]['putuse']="已使用";
                    }elseif($val['flag']=="1"){
                        $ticketList[$key]['putuse']="已过期";
                    }else{
                        $ticketList[$key]['putuse']="未使用";
                    }
                }
                $exshare=date("Y年m月d日",$exsharedata);
                $ticketList[$key]['shareid']=trim($val['shareid']);
                $ticketList[$key]['quanid']=$val['quanid'];
                $placeddate=$val['placeddate'];
                $placedtime=$val['placedtime'];
                $place=$placeddate.$placedtime;
                $placestime=strtotime($place);
                $dateplaces=date("Y年m月d日H:i",$placestime);
                $ticketList[$key]['placedalldate']=$dateplaces;
                $ticketList[$key]['quanname']=$val['quanname'];
                $ticketList[$key]['tradeamount']=$val['tradeamount'];
                $ticketList[$key]['exdate']=$val['exdate'];
                $ticketList[$key]['exshare']=$exshare;
                $ticketList[$key]['count']=$count;
            }
            return $ticketList;
        }
    }

    public function getQuanList(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $quanid = trim($datami['quanid']);
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        empty($quanid) && returnMsg(array('status'=>'04','codemsg'=>'券的编号不能为空'));

        $where=array('cu.customid'=>$customid,'a.quanid'=>$quanid);
        $quanInfo=$this->model->table('quankind')->where(array('quanid'=>$quanid))->find();
        if($quanInfo['atype']==2){
            $list=$this->model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on cc.cid=c.customid')->join('quan_account a on a.customid=c.cusotmid')
                ->where($where)->field('a.quanid,a.enddate,a.amount,a.accountid')->select();
        }else{
            $list=$this->model->table('customs')->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on cc.cid=c.customid')->join('account a on a.customid=c.cusotmid')
                ->join('quankind q on q.quanid=a.quanid')
                ->where($where)->field('a.amount,a.accountid,q.enddate,q.quanid')->select();
        }
        if($list!=false){
            returnMsg(array('status'=>'1','list'=>$list,'time'=>time()));
        }else{
            returnMsg(array('status'=>'05','codemsg'=>'无信息'));
        }
    }

    //卡券详情页面的信息
    public function  codeInterface(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $quanid = trim($datami['quanid']);
        $accountid=trim($datami['accountid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$quanid.$accountid);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        empty($quanid) && returnMsg(array('status'=>'04','codemsg'=>'券的编号不能为空'));
        $Ccustoms=M("customs_c")->where(array('cid'=>$customid))->find();
        if(empty($Ccustoms)){
            $newcustomid=$customid;//查询获取新的主会员的id
        }else{
            $newcustomid=$Ccustoms['customid'];//查询获取新的主会员的id
        }
        $customBool=$this->checkCustom($newcustomid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $ticketCodeList = $this->getTicketInfo($customid,$quanid); //查找卡券的一些基本的信息

        if($ticketCodeList==false){
            $ticketCodeList = array();
            returnMsg(array('status'=>'1','ticketCodeList'=>$ticketCodeList,'time'=>time()));
        }

        $customsall=M("customs_c")->where(array('customid'=>$customid))->select();

        foreach($customsall as $key=>$vv){
            $cid=$vv['cid'];
            $where1=array('customid'=>$cid,"quanid"=>$quanid,'flag'=>0);
            $zzwastecount=M("share_wastebooks")->where($where1)->count();
            $count+=$zzwastecount;
        }
        $ticketCodeList['sharenum']= $count;

        $acccustomid=$ticketCodeList['customid'];

        $url=$this->curPageURL();
        $contenturl=$url."/zzkp.php/Pos/RposCoin/posapi?customid={$acccustomid}&quanid={$quanid}&accountid={$accountid}";

        $customQuan="d".$acccustomid."_".$quanid.'_'.$accountid;
        $logPath='./Public/erweima/coin/';
        $filename=$customQuan.'.png';//原始二维码图 名称路径
        if(file_exists($filename)){
            if(time()<$_SESSION['qrcode_time']+600){
                $erweima=$filename;
            }else{
                unlink($filename);
                $erweima=$this->rwmcode($contenturl,$customQuan,$logPath);
            }
        }else{
            $erweima=$this->rwmcode($contenturl,$customQuan,$logPath);
        }
        $erweimaurl=$url;
        $ticketCodeList['erweima']=$erweimaurl.$logPath.$filename;
        $quandata=C("quanall");

        foreach($quandata as $key=>$vv){
            if($vv['quanid']==$quanid&&$vv['panterid']==$ticketCodeList['panterid']){
                $ticketCodeList['annotation']=$vv['annotation'];
            }
        }
        if(empty($ticketCodeList['annotation'])){
            $ticketCodeList['annotation']="";
        }
        returnMsg(array('status'=>'1','ticketCodeList'=>$ticketCodeList,'time'=>time()));
    }

    //查找卡券的id/卡券的名称/卡券的数
    public function getTicketInfo($customid,$quanid){
        $quanInfo=M('quankind')->where(array('quanid'=>$quanid))->find();
        if($quanInfo['atype']==1){
            $where['cc.customid']=$customid;
            $where['a.type']='02';
            $where['a.quanid']=$quanid;
            $list=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
                ->join('cards c on c.customid=a.customid')
                ->join('customs_c cc on cc.cid=c.customid')
                ->join('panters p on p.panterid=q.panterid')
                ->field('q.quanname,a.amount,q.enddate,q.panterid,a.customid,a.quanid,p.namechinese,q.atype')
                ->where($where)->select();
        }else{
            $where['cc.customid']=$customid;
            $where['qa.quanid']=$quanid;
            $list=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
                ->join('cards c on c.customid=qa.customid')
                ->join('customs_c cc on cc.cid=c.customid')
                ->join('panters p on p.panterid=q.panterid')
                ->field('q.quanname,qa.amount,qa.enddate,q.panterid,qa.customid,qa.quanid,p.namechinese,q.atype')
                ->where($where)->select();
        }

        if($list==false){
            return false;
        }else{
            $item=array();
            foreach($list as $k=>$v){
                if($v['atype']==1){
                    if(!isset($item[$v['quanid']])){
                        $item[$v['quanid']]['namechinese']=$v['namechinese'];
                        $item[$v['quanid']]['quanname']=$v['quanname'];
                        $item[$v['quanid']]['amount']=$v['amount'];
                        $item[$v['quanid']]['panterid']=$v['panterid'];
                        $strtime=strtotime($v['enddate']);
                        $datetime=date("Y年m月d日",$strtime);
                        $item[$v['quanid']]['enddate']=$datetime;
                        $item[$v['quanid']]['customid']=$v['customid'];
                    }else{
                        $item[$v['quanid']]['amount']+=$v['amount'];
                    }
                }else{
                    $item[$v['quanid']]['namechinese']=$v['namechinese'];
                    $item[$v['quanid']]['quanname']=$v['quanname'];
                    $item[$v['quanid']]['amount']=$v['amount'];
                    $item[$v['quanid']]['panterid']=$v['panterid'];
                    $strtime=strtotime($v['enddate']);
                    $datetime=date("Y年m月d日",$strtime);
                    $item[$v['quanid']]['enddate']=$datetime;
                    $item[$v['quanid']]['customid']=$v['customid'];
                }
            }
            $zz=array();
            foreach($item as $vv){
                $zz=$vv;
            }
            return $zz;
        }
    }

    //通过用户查找会员的id
    public function allTicketInfo($customid,$quanid){
        $where['cc.customid']=$customid;
        $where['a.type']='02';
        $where['a.quanid']=$quanid;
        $where['q.enddate']=array('gt',date('Ymd',time()));
        $list=$this->account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->where($where)->select();
        if($list==false){
            return false;
        }else{
            return $list;
        }
    }

    //获取卡券的分享数量
    public function getsharenum($customid,$quanid){
        $where=array('cu.customid'=>$customid,'c.status'=>'Y');
        $cards=$this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->field('c.cardno')->where($where)->select();

        $reslogs.=$customid.'1'."\r\n"."\r\n";
        $this->writeLogs("getsharenum",$reslogs);

        $cards || die(json_encode(array('code'=>50,'message'=>'卡号丢失。。。。')));

        $cardArr = $this->serializeArr($cards,'cardno');
        $cardArr = implode(',',$cardArr);

        $where1=array('customid'=>$customid,"quanid"=>$quanid,'flag'=>0);
        // $where1['cardno']=array("in",$cardArr);

        $reslogs.=json_encode($where1).'2'."\r\n"."\r\n";
        $this->writeLogs("getsharenum",$reslogs);

//       $quaninfo=M("share_wastebooks")->alias('sw')->join('quankind  q on sw.quanid=q.quanid')
//               ->where($where1)
//               ->sum('tradeAmount');

        $quaninfo=M("share_wastebooks")->where($where1)->select();

        $quaninfo1=count($quaninfo);

        $reslogs.=json_encode($quaninfo).'3'."\r\n"."\r\n";
        $this->writeLogs("getsharenum",$reslogs);

        if($quaninfo1==false){
            return '0';
        }else{
            return $quaninfo1;
        }
    }

    //生成二维码
    public function rwmcode($content,$customQuan,$logPath){
        include 'phpqrcode.php';
        $value =$content."&rand=".time(); //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 10;//生成图片大小
        //生成二维码图片
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        $filename=$logPath.$customQuan.'.png';//原始二维码图 名称路径
        $object = new \QRcode();
        // QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        //  $QR = $filename;//已经生成的原始二维码图
        $object->png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $_SESSION['qrcode_time']=time();
        $erweima=$filename;
        return $erweima;
    }

    //扫码之后获取的数据
    public function posapi(){
        echo "非法访问";
        exit();

        $customid=$_REQUEST['customid'];
        $quanid=$_REQUEST['quanid'];
        $quanname=$_REQUEST['quanname'];
        $shareid=$_REQUEST['shareid'];
        $rand=$_REQUEST['rand']+600;
        if(!empty($shareid)){
            $share=M("share_wastebooks");
            $where1['quanid']=$quanid;
            $where1['customid']=$customid;
            $where1['shareid']=$shareid;
            $sharedata=$share->where($where1)->find();
            $placeddate=$sharedata['placeddate'];
            $placedtime=$sharedata['placedtime'];
            $exdate=trim($sharedata['exdate']);
            $sharedate=$placeddate.$placedtime;//分享的时间
            //过期的时间
            //  $exdate="0.05";
            $exsharedata=strtotime($sharedate)+($exdate*24*60*60);
            if($exsharedata<time()){
                $msg=array(
                    "status"=>'1003',
                    "codemsg"=>"二维码已过期",
                );
                echo json_encode($msg);
                exit();
            }
            $customBool=$this->checkCustom($customid);
            if(!$customBool){
                $msg=array(
                    "status"=>'1001',
                    "codemsg"=>"用户不存在",
                );
                echo json_encode($msg);
                exit();
            }
            $sharedata=$this->getshareinfo($customid,$quanid,$shareid);
            if(!$sharedata){
                $msg=array(
                    "status"=>'1004',
                    "codemsg"=>"未获取分享的卡券的信息",
                );
                echo json_encode($msg);
                exit();
            }
            $shareall['quanid']=$quanid;
            $shareall['customid']=$customid;
            $shareall['amount']="1";
            $shareall['shareid']=$shareid;
            echo json_encode($shareall);
            exit();
        }else{
            if($rand<time()){
                $msg=array(
                    "status"=>'1003',
                    "codemsg"=>"二维码已过期",
                );
                echo json_encode($msg);
                exit();
            }
            $customBool=$this->checkCustom($customid);
            if(!$customBool){
                $msg=array(
                    "status"=>'1001',
                    "codemsg"=>"用户不存在",
                );
                echo json_encode($msg);
                exit();
            }
            $ticketinfo=$this->getTicketInfo($customid,$quanid);
            if(!$ticketinfo){
                $msg=array(
                    "status"=>'1003',
                    "codemsg"=>"卡券的信息没有获取成功",
                );
                echo json_encode($msg);
                exit();
            }
            $shareall['quanid']=$quanid;
            $shareall['customid']=$customid;
            $shareall['amount']="1";
            echo json_encode($shareall);
            exit();
        }
    }

    //分享信息的添加
    public function shareCoin(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $quanid = trim($datami['quanid']);
        $amount = trim($datami['amount']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$quanid.$amount);

        $zz=json_encode($datami);
        $reslogs.=$zz."\r\n"."\r\n";

        if($checkKey != $key){
            $this->writeLogs("shareCoin",$reslogs);
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        $reslogs.=$customid."\r\n"."\r\n";
        $this->writeLogs("shareCoin",$reslogs);

        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        empty($quanid) && returnMsg(array('status'=>'04','codemsg'=>'券的编号不能为空'));

//      $customid='00000356';
//      $quanid='00000015';
//      $amount="1";

        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }

        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();

        if($quankind==false){
            returnMsg(array('status'=>'05','codemsg'=>'营销劵不存在'));
        }

        if($quankind['enddate']<date('Ymd',time())){
            returnMsg(array('status'=>'06','codemsg'=>'营销劵已过期'));
        }

        if($amount!=1){
            returnMsg(array('status'=>'010','codemsg'=>'只能分享一张'));
        }

        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        $quanAmount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')->where($where)->sum('a.amount');

        if($quanAmount<=0||$quanAmount<$amount){
            returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
        }
        $quanname=$quankind['quanname'];

        $this->model->startTrans();

        $consumeIf=$this->ticketShare($customid,$quanid,$amount);

        if($consumeIf==true){
            $this->model->commit();
            // $shareid=$consumeIf['0'];
            returnMsg(array('status'=>'1','codemsg'=>'分享成功','shareInfo'=>$consumeIf,'time'=>time()));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'09','codemsg'=>'分享失败'));
        }
    }

    //分享执行的过程
    public function ticketShare($customid,$quanid,$amount){
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        $quanAccount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')
            //->join('cards c on c.customid=cc.cid')
            ->join('quankind q on q.quanid=a.quanid')
            ->where($where)->field('a.accountid,a.amount,cc.cid,q.quanname,q.startdate,q.enddate,q.panterid')->select();

        $reslogs.=json_encode($quanAccount)."\r\n"."\r\n";
        $this->writeLogs("shareCoin2",$reslogs);
        if(empty($quanAccount)){
            $rescards.=json_encode($quanAccount)."\r\n"."\r\n";
            $this->writeLogs("sharecards",$rescards);
        }
        if(!empty($quanAccount)){
            foreach($quanAccount as $key=>&$v){
                $cid=$v['cid'];
                $cardsdata=M("Cards")->where(array('customid'=>$cid))->find();
                if(!empty($cardsdata)){
                    $v['cardno']=$cardsdata['cardno'];
                }else{
                    $rescards.=json_encode($cardsdata)."\r\n"."\r\n";
                    $this->writeLogs("sharecards",$rescards);
                }
            }
        }
        $tradIdList = array();
        $consumedAmount=0;
        foreach($quanAccount as $key=>$val){
            $waitAmount=$amount-$consumedAmount;
            if($waitAmount<=0) break;
            if($waitAmount>=$val['amount']){
                $consumeAmount=$val['amount'];
            }else{
                $consumeAmount=$waitAmount;
            }
            $cardno=$val['cardno'];
            $panterid=$val['panterid'];
            $quanname=$val['quanname'];
            $startdate=$val['startdate'];
            $enddate=$val['enddate'];
            $accountid=$val['accountid'];
            $shareid=substr($cardno,15,4).date('YmdHis',time());
            $map=array('shareid'=>$shareid);
            $c=M('share_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $shareid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $customid=$val['cid'];
            $exdate="1";
            $shareid=trim($shareid);
            $tradeSql="insert into share_wastebooks(shareid,customid,quanid,quanname,startdate,";
            $tradeSql.="enddate,panterid,placeddate,placedtime,exdate,tradeamount,retailamount,flag,accountid,cardno)";
            $tradeSql.="values('{$shareid}','{$customid}','{$quanid}','{$quanname}','{$startdate}',";
            $tradeSql.="'{$enddate}','{$panterid}','{$placeddate}','{$placedtime}','{$exdate}','{$consumeAmount}','0','0','{$accountid}','{$cardno}')";
            $tradeIf=$this->model->execute($tradeSql);

            $errString12.="时间：".date('Y-m-d H:i:s').";分享更新记录：{$tradeSql};执行结果：{$tradeIf}\r\n";

            $this->writeLogs("ticketShare",$errString12);

            $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$val['cid']}' and type='02' and quanid='{$quanid}'";
            $accountIf=$this->model->execute($accountSql);

            $errString12.="时间：".date('Y-m-d H:i:s').";分享更新记录：{$accountSql};执行结果：{$accountIf}\r\n";

            $this->writeLogs("ticketShare",$errString12);

            if($accountIf==true&&$tradeIf==true){
                $consumedAmount+=$consumeAmount;
                $tradIdList[$key]['shareid'] = $shareid;
                $tradIdList[$key]['quanname']=$quanname;
                $sharetime=time()+1*24*60*60;
                $sharedate=date("Y-m-d",$sharetime);
                $tradIdList[$key]['shareEnddate']=$sharedate;
            }
        }
        if($consumedAmount==$amount){
            return $tradIdList;
        }else{
            return false;
        }
    }

    //分享卡券记录中的详情页面的显示
    public function  shareInterface(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'07','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $quanid = trim($datami['quanid']);
        $shareid = trim($datami['shareid']); //分享的id
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$quanid.$shareid);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'02','codemsg'=>'用户编号缺失'));
        empty($quanid) && returnMsg(array('status'=>'04','codemsg'=>'券的编号不能为空'));
        empty($shareid) && returnMsg(array('status'=>'05','codemsg'=>'分享的编号不能为空'));
        $Ccustoms=M("customs_c")->where(array('cid'=>$customid))->find();
        if(empty($Ccustoms)){
            $newcustomid=$customid;//查询获取新的主会员的id
        }else{
            $newcustomid=$Ccustoms['customid'];//查询获取新的主会员的id
        }
        $customBool=$this->checkCustom($newcustomid);
        if($customBool==false){
            returnMsg(array('status'=>'03','codemsg'=>'用户不存在'));
        }
        $share_wastebooks=M("share_wastebooks")->where(array("shareid"=>$shareid))->find();
        $sharecustomid=$share_wastebooks['customid'];
        if($sharecustomid!=$customid){
            $customid=$sharecustomid;
        }
        $ticketshareList = $this->getshareinfo($customid,$quanid,$shareid); //查找卡券的一些基本的信息
        if($ticketshareList==false){
            $ticketshareList = array();
            returnMsg(array('status'=>'1','ticketshareList'=>$ticketshareList,'time'=>time()));
        }
        $url=$this->curPageURL();
        $contenturl=$url."/zzkp.php/Pos/RposCoin/posapi?customid={$newcustomid}&quanid={$quanid}&shareid={$shareid}";
        $customQuan="share".$customid."_".$shareid;
        $logPath='./Public/erweima/share/';
        $filename=$customQuan.'.png';//原始二维码图 名称路径
        if(file_exists($filename)){
            $placeddate=$ticketshareList['placeddate'];
            $placedtime=$ticketshareList['placedtime'];
            $datetime=$placeddate.$placedtime;
            $alldate=strtotime($datetime);
            $futertime=$alldate+604800;
            if(time()<$futertime){
                $erweima=$filename;
            }else{
                returnMsg(array('status'=>'06','codemsg'=>'二维码已经过期了'));
            }
        }else{
            $erweima=$this->rwmcode($contenturl,$customQuan,$logPath);
        }
        $erweimaurl=$url;
        $ticketshareList['erweima']=$erweimaurl.$logPath.$filename;
        $quandata=C('quanall');
        foreach($quandata as $key=>$vv){
            if($vv['quanid']==$ticketshareList['quanid']&&$vv['panterid']==$ticketshareList['panterid']){
                $ticketshareList['annotation']=$vv['annotation'];
            }
        }
        if(empty($ticketCodeList['annotation'])){
            $ticketCodeList['annotation']="";
        }
        returnMsg(array('status'=>'1','ticketshareList'=>$ticketshareList,'time'=>time()));
    }

    //卡券直接消费
    public function ticketConsume(){
        $jsonpost=json_encode($_POST);
        $reslogs.="----------------------------\r\n".$jsonpost."\r\n"."\r\n";
        $this->writeLogs("ticketConsumetemp",$reslogs);

        $customid = trim($_POST['customid']);
        $quanid = trim($_POST['quanid']);
        $amount = "1";
        $panterid = trim($_POST['panterid']);
        $termposno = trim($_POST['termposno']);//终端号
        $accountid=trim($_POST['accountid']);
        $key = trim($_POST['key']);

        if($key != md5($this->keycode.$customid.$quanid.$panterid.$termposno)){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥'));
        }
        $map_c=array('cid'=>$customid);
        $custom_c=$this->customs_c->where($map_c)->find();
        if(empty($custom_c)){
            returnMsg(array('status'=>'05','codemsg'=>'会员不存在'));
        }
        $customid=$custom_c['customid'];

        $this->checkPanter($panterid) || returnMSg(array('status'=>'50','codemsg'=>'商户不存在！'));

        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
        }
        if(empty($quanid)){
            returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
        }

        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            returnMsg(array('status'=>'04','codemsg'=>'消费格式错误'));
        }
        $map=array('customid'=>$customid);
        $custom=$this->customs->where($map)->find();
//      if($custom==false){
//          returnMsg(array('status'=>'05','codemsg'=>'会员不存在'));
//      }
        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();
        if($quankind==false){
            returnMsg(array('status'=>'011','codemsg'=>'营销劵不存在'));
        }
        $quanSql=M('quankind')->getLastSql();
        if($quankind['atype']==1){
            if($quankind['enddate']<date('Ymd',time())){
                returnMsg(array('status'=>'08','codemsg'=>'营销劵已过期'));
            }
            $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
            $quanAmount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')->where($where)->sum('a.amount');

            $getsalx.=$this->account->getLastSql().json_encode($quanAmount)."\r\n"."\r\n";
            $this->writeLogs("ticketConsumetemp",$getsalx);

            if($quanAmount<=0||$quanAmount<$amount){
                returnMsg(array('status'=>'09','codemsg'=>'营销劵余额不足'));
            }
        }else{
            $quanInfo=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
                ->where(array("qa.accountid"=>$accountid))->field('qa.*,q.utype,q.panterid')->find();
            if($quanInfo['enddate'] < date('Ymd',time())){
                returnMsg(array('status'=>'08','codemsg'=>"营销劵已过期"));
            }
            if($quanInfo['utype']==1){
                $panter=M('panters')->where(array('panterid'=>$panterid))->field('parent,panterid')->find();
                if($panter['parent']!=$quanInfo['panterid']&&$panterid!=$quanInfo['panterid']){
                    returnMsg(array('status'=>'011','codemsg'=>'不能再此商户下消费'));
                }
            }elseif($quanInfo['utype']==2){
                if($panterid!=$quanInfo['panterid']){
                    returnMsg(array('status'=>'012','codemsg'=>'专用劵不能再此商户下消费'));
                }
            }
//           $zz=$accountid.json_encode($quanInfo);

//           returnMsg(array('status'=>'09','codemsg'=>$zz));


            if($quanInfo['amount']<$amount){
                returnMsg(array('status'=>'09','codemsg'=>'营销劵余额不足'));
            }

        }
        $getsal.=$quanSql.json_encode($quankind)."\r\n"."\r\n";
        $this->writeLogs("ticketConsumetemp",$getsal);

        $this->model->startTrans();

        $consumeIf=$this->ticketExenew($customid,$quanid,$amount,$panterid,$termposno,$accountid,$quanInfo['atype']);

        $getzz.=json_encode($consumeIf)."\r\n"."\r\n";
        $this->writeLogs("ticketConsumetemp",$getzz);

        if($consumeIf==true){
            $getzz.= "------------------------------消费成功--\r\n"."\r\n";
            $this->writeLogs("ticketConsumetemp",$getzz);
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradidlist'=>$consumeIf,'quanname'=>$quankind['quanname'],'time'=>time()));
        }else{
            $getzz.= "------------------------------消费失败--\r\n"."\r\n";
            $this->writeLogs("ticketConsumetemp",$getzz);
            $this->model->rollback();
            returnMsg(array('status'=>'010','codemsg'=>'消费失败'));
        }
    }

    //劵消费执行
    protected function ticketExenew($customid,$quanid,$amount,$panterid,$termposno=null,$accountid,$type){
        $where=array('a.type'=>'02','a.quanid'=>$quanid,'cc.customid'=>$customid,'a.amount'=>array('gt',0));
        if($type==1){
            $quanAccount=$this->account->alias('a')->join('customs_c cc on cc.cid=a.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where($where)->field('a.accountid,a.amount,cc.cid,c.cardno')->select();
            $consumedAmount=0;
            $tradIdList = array();
            foreach($quanAccount as $key=>$val){
                $waitAmount=$amount-$consumedAmount;
                if($waitAmount<=0) break;
                if($waitAmount>=$val['amount']){
                    $consumeAmount=$val['amount'];
                }else{
                    $consumeAmount=$waitAmount;
                }

                $cardno=$val['cardno'];
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
                $map=array('tradeid'=>$tradeid);
                $c=M('trade_wastebooks')->where($map)->count();
                if($c>0){
                    sleep(1);
                    $tradeid=substr($cardno,15,4).date('YmdHis',time());
                }
                $placeddate=date('Ymd',time());
                $placedtime=date('H:i:s',time());
                $customid=$val['cid'];
                $termno = $termposno ? $termposno : '0000000';
                $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
                $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
                $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
                $tradeSql.="'{$consumeAmount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";
                $tradeIf=$this->model->execute($tradeSql);


                $accountSql="UPDATE ACCOUNT set amount=amount-{$consumeAmount} where customid='{$val['cid']}' and type='02' and quanid='{$quanid}'";
                $accountIf=$this->model->execute($accountSql);

                $sql.=$tradeSql.'执行的结果'.$tradeIf."\r\n"."\r\n";
                $sql.=$accountSql.'执行的结果'.$accountIf."\r\n"."\r\n";
                $this->writeLogs("ticketExe1",$sql);

                if($accountIf==true&&$tradeIf==true){
                    $consumedAmount+=$consumeAmount;
                    $tradIdList[$key] = $tradeid;
                }
            }
            if($consumedAmount==$amount){
                return $tradIdList;
            }else{
                return false;
            }
        }else{
            $quanAccount=M('quan_account')->alias('qa')
                ->join('cards c on c.customid=qa.customid')
                ->where(array('qa.accountid'=>$accountid))
                ->field('qa.*,c.cardno')
                ->find();
            $cardno=$quanAccount['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $customid=$quanAccount['customid'];
            $termno = $termposno ? $termposno : '0000000';
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','营销劵账户:{$accountid}')";
            $tradeIf=$this->model->execute($tradeSql);

            $quanaccountSql="update quan_account set amount=amount-{$amount} where accountid='{$accountid}'  and quanid='{$quanid}'";
            $quanaccountIf=$this->model->execute($quanaccountSql);

            $sql.=$tradeSql.'执行的结果'.$tradeIf."\r\n"."\r\n";
            $sql.=$quanaccountSql.'执行的结果'.$quanaccountIf."\r\n"."\r\n";
            $this->writeLogs("ticketExe1",$sql);

            if($tradeIf==true&&$quanaccountIf==true){
                $tradIdList[] = $tradeid;
                return $tradIdList;
            }else{
                return false;
            }
        }
    }

    //分享的卡券的消费
    public function ticketShareCoin(){

        $jsonpost=json_encode($_POST);
        $reslogs=$jsonpost."\r\n"."\r\n";
        $this->writeLogs("ticketSharetemp",$reslogs);

        $customid = trim($_POST['customid']);
        $quanid = trim($_POST['quanid']);
        $shareid = trim($_POST['shareid']);
        $amount = "1";
        $panterid = trim($_POST['panterid']);//商务号
        $termposno = trim($_POST['termposno']);//终端号
        $key = trim($_POST['key']);
        $accountid=trim($_POST['accountid']);   //-----------2018-06-02--分享卡券-账号--------------------

        $this->checkPanter($panterid) || returnMSg(array('status'=>'50','codemsg'=>'商户不存在！'));
        if(empty($customid)){
            returnMsg(array('status'=>'02','codemsg'=>'会员编号缺失'));
        }
        if(empty($quanid)){
            returnMsg(array('status'=>'03','codemsg'=>'营销劵编号缺失'));
        }
        if(empty($shareid)){
            returnMsg(array('status'=>'05','codemsg'=>'分享卡券的编号缺失'));
        }
        if(!preg_match('/^[0-9]+(\.[0-9]+)?$/',$amount)){
            $this->writeLogs("ticketShareCoin",$reslogs);
            returnMsg(array('status'=>'04','codemsg'=>'消费格式错误'));
        }
        if($key != md5($this->keycode.$customid.$quanid.$shareid.$panterid.$termposno)){
            $this->writeLogs("ticketShareCoin",$reslogs);
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥'));
        }
        $map=array("shareid"=>$shareid);
        $wastebooks=M('share_wastebooks')->where($map)->find();
        $cid=$wastebooks['customid'];
        if($cid==$customid){
            $map2=array("cid"=>$customid);
            $customC=M('customs_c')->where($map2)->find();
            $customid=$customC['customid'];
        }
        $mapz=array('customid'=>$customid);
        $custom=$this->customs->where($mapz)->find();
        if($custom==false){
            returnMsg(array('status'=>'06','codemsg'=>'会员不存在'));
        }
        $customid=$cid;

        $map1=array('quanid'=>$quanid);
        $quankind=M('quankind')->where($map1)->find();

        if($quankind==false){
            returnMsg(array('status'=>'08','codemsg'=>'营销劵不存在'));
        }

        if($quankind['atype']==1){
            if($quankind['enddate']<date('Ymd',time())){
                returnMsg(array('status'=>'09','codemsg'=>'营销劵已过期'));
            }
        }else{
            $quanInfo=M('quan_account')->alias('qa')->join('quankind q on q.quanid=qa.quanid')
                ->where(array("qa.accountid"=>$accountid))->field('qa.*,q.utype,q.panterid')->find();

            if(empty($quanInfo)){
                returnMsg(array('status'=>'081','codemsg'=>"未查到此券的信息"));
            }
            if($quanInfo['enddate'] < date('Ymd',time())){
                returnMsg(array('status'=>'08','codemsg'=>"营销劵已过期"));
            }
            if($quanInfo['utype']==1){
                $panter=M('panters')->where(array('panterid'=>$panterid))->field('parent,panterid')->find();
                if($panter['parent']!=$quanInfo['panterid']&&$panterid!=$quanInfo['panterid']){
                    returnMsg(array('status'=>'011','codemsg'=>'不能再此商户下消费'));
                }
            }elseif($quanInfo['utype']==2){
                if($panterid!=$quanInfo['panterid']){
                    returnMsg(array('status'=>'012','codemsg'=>'专用劵不能再此商户下消费'));
                }
            }
        }
        $map2=array('shareid'=>$shareid);
        $quanShare=M('share_wastebooks')->where($map2)->find();
        $placeddate=$quanShare['placeddate'];
        $placedtime=$quanShare['placedtime'];
        $exdate=trim($quanShare['exdate']);
        $sharedate=$placeddate.$placedtime;//分享的时间

        //过期的时间
        $exsharedata=strtotime($sharedate)+($exdate*24*60*60);
        if($quanShare==false){
            returnMsg(array('status'=>'010','codemsg'=>'分享的卡卷不存在'));
        }
        if($exsharedata<time()){
            returnMsg(array('status'=>'011','codemsg'=>'分享的卡卷已经失效了'));
        }
        if($quanShare['flag']==2){
            returnMsg(array('status'=>'019','codemsg'=>'分享的卡卷已经消费'));
        }
        $share_wastebooks=M('share_wastebooks');
        $where=array('a.flag'=>'0','a.quanid'=>$quanid,'a.shareid'=>$shareid,'cc.cid'=>$customid,'a.tradeamount'=>array('gt',0));
        $shareAmount=$share_wastebooks->alias('a')->join('customs_c cc on cc.cid=a.customid')->where($where)->sum('a.tradeamount');

        if($shareAmount<=0||$shareAmount<$amount){
            returnMsg(array('status'=>'012','codemsg'=>'营销劵余额不足'));
        }
        $quan=M("quankind");
        $quandata=$quan->where(array("quanid"=>$quanid))->find();
        $quanname=$quandata['quanname'];
        $this->model->startTrans();
        $consumeIf=$this->shareExe($customid,$quanid,$shareid,$amount,$panterid,$termposno,$accountid,$quanInfo['atype']);
        if($consumeIf==true){
            $this->model->commit();
            returnMsg(array('status'=>'1','codemsg'=>'消费成功','tradidlist'=>$consumeIf,'quanname'=>$quanname,'time'=>time()));
        }else{
            $this->model->rollback();
            returnMsg(array('status'=>'013','codemsg'=>'消费失败'));
        }
    }

    //劵消费执行
    protected function shareExe($customid,$quanid,$shareid,$amount,$panterid,$termposno=null,$accountid,$type){
        $share_wastebooks=M('share_wastebooks');
        $where=array('a.flag'=>'0','a.quanid'=>$quanid,'a.shareid'=>$shareid,'cc.cid'=>$customid,'a.tradeamount'=>array('gt',0));
        if($type==1){
            $quanAccount=$share_wastebooks->alias('a')->join('customs_c cc on cc.cid=a.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where($where)->field('a.accountid,a.tradeamount,cc.cid,c.cardno')->find();
            $cardno=$quanAccount['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $termno = $termposno ? $termposno : '0000000';
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}')";

            $tradeIf=$this->model->execute($tradeSql);
            $accountSql="UPDATE share_wastebooks set tradeamount=tradeamount-{$amount},flag='2' where customid='{$customid}' and flag='0'and shareid={$shareid} and quanid='{$quanid}'";
            $accountIf=$this->model->execute($accountSql);

            $sql=$tradeSql.'执行的结果'.$tradeIf."\r\n"."\r\n";
            $sql.=$accountSql.'执行的结果'.$accountIf."\r\n"."\r\n";
            $this->writeLogs("shareExe",$sql);

            $consumedata['0']=$tradeid;
            if($accountIf && $tradeIf){
                return $consumedata;
            }else{
                return false;
            }
        }else{
            $quanAccount=$share_wastebooks->alias('a')->join('customs_c cc on cc.cid=a.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where($where)->field('a.accountid,a.tradeamount,cc.cid,c.cardno')->find();
            $cardno=$quanAccount['cardno'];
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $map=array('tradeid'=>$tradeid);
            $c=M('trade_wastebooks')->where($map)->count();
            if($c>0){
                sleep(1);
                $tradeid=substr($cardno,15,4).date('YmdHis',time());
            }
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());
            $termno = $termposno ? $termposno : '0000000';
            $acountquan=M('quan_account')->where(array('accountid'=>$accountid))->find();
            $customid=$acountquan['customid'];

            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
            $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','营销劵账户:{$accountid}')";
            $tradeIf=$this->model->execute($tradeSql);
            $accountSql="UPDATE share_wastebooks set tradeamount=tradeamount-{$amount},flag='2' where accountid='{$accountid}' and flag='0'and shareid={$shareid} and quanid='{$quanid}'";
            $accountIf=$this->model->execute($accountSql);
            $sql=$tradeSql.'执行的结果'.$tradeIf."\r\n"."\r\n";
            $sql.=$accountSql.'执行的结果'.$accountIf."\r\n"."\r\n";
            $this->writeLogs("shareExe",$sql);

            $consumedata['0']=$tradeid;
            if($accountIf && $tradeIf){
                return $consumedata;
            }else{
                return false;
            }
        }
    }

    //获取卡券已经分享的卡券
    public function getshareinfo($quanid,$shareid){
        $where1=array('sw.quanid'=>$quanid,'sw.shareid'=>$shareid);
        $quaninfo=M("share_wastebooks")->alias('sw')->join('quankind  q on sw.quanid=q.quanid')
            ->where($where1)
            ->field('sw.flag as flag,sw.shareid,sw.quanid as quanid,sw.panterid as panterid,q.quanname as quanname,q.enddate as enddate,sw.placeddate as placeddate,sw.placedtime as placedtime,sw.tradeAmount as tradeAmount,sw.exdate as exdate')
            ->find();
        if($quaninfo==false){
            return '0';
        }else{
            $panterdata=M("Panters")->where(array("panterid"=>$quaninfo['panterid']))->field("namechinese")->find();
            $quaninfo['namechinese']=$panterdata['namechinese'];
            if($quaninfo['flag']==0){//正常的卡卷
                $exsharedata=strtotime($quaninfo['placeddate']. $quaninfo['placedtime'])+(trim($quaninfo['exdate'])*24*60*60);
                if($exsharedata>time()){
                    $quaninfo['exshare']=date("Y-m-d",$exsharedata);
                }else{
                    $back=$this->backShareTicket($shareid);
                    if($back==true){
                        returnMSg(array('status'=>'012','codemsg'=>'卡券过期'));
                    }else{
                        returnMSg(array('status'=>'011','codemsg'=>'卡券过期，更改失败'));
                    }
                }
            }else{//已经消费的卡卷或者是过期的卡卷
                $exsharedata=strtotime($quaninfo['placeddate']. $quaninfo['placedtime'])+(trim($quaninfo['exdate'])*24*60*60);
                $quaninfo['exshare']=date("Y-m-d",$exsharedata);
            }
        }
        $quaninfo['enddate']=date('Y年m月d日',strtotime($quaninfo['enddate']));
        $quaninfo['placeddate']=date('Y年m月d日',strtotime($quaninfo['placeddate']));
        return $quaninfo;
    }

    //券退回
    public function backShareTicket($shareid){
        $map = array('shareid'=>$shareid);
        $share = M('share_wastebooks')->where($map)->find();

        if($share == false){
            returnMsg(array('status'=>'06','codemsg'=>'券分享编号未查到'));
        }
        if($share['flag']=="2"){
            returnMsg(array('status'=>'015','codemsg'=>'卡卷已经分享过了'));
        }
        $this->model->startTrans();
        $bool = $this->backQuanShare($shareid);
        if($bool == true){
            $this->model->commit();
            return true;
        }else{
            $this->model->rollback();
            return false;
        }
    }

    //执行券过期退回
    public function backQuanShare($shareid){
        $data['shareid']=$shareid;
        $data['flag']=0;
        $sharesearch = M('share_wastebooks')->where($data)->find();
        if($sharesearch == false){
            return false;
        }
        $accountsql = "update account set amount=amount+{$sharesearch['tradeamount']} where quanid='{$sharesearch['quanid']}'
        and customid='{$sharesearch['customid']}' and type='02'";
        $account = M()->execute($accountsql);
        $errString1.="时间：".date('Y-m-d H:i:s').";分享更新记录：{$accountsql};执行结果：{$account}\r\n";
        $this->recordError($errString1,'backsharequan','会员卡编号_'.$sharesearch['customid']);
        $tradsql = "update share_wastebooks set flag=1 where shareid='{$shareid}'";
        $trade = M()->execute($tradsql);
        $errString1.="时间：".date('Y-m-d H:i:s').";分享明细更新记录：{$tradsql};执行结果：{$trade}\r\n";
        $this->recordError($errString1,'backquan','会员卡编号_'.$sharesearch['customid']);
        if($trade != true || $account != true){
            return false;
        }
        return true;
    }

    function curPageURL()
    {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on")
        {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80"  && $_SERVER["SERVER_PORT"] != "443")
        {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] ;
        }
        else
        {
            $pageURL .= $_SERVER["SERVER_NAME"];
        }
        return $pageURL;
    }

    //日志
    public function writeLogs($module,$msgString){
        $month=date('Ym',time());
        switch($module){
            case 'shareCoin':$logPath=PUBLIC_PATH.'logs/shareCoin/';break;
            case 'shareCoin2':$logPath=PUBLIC_PATH.'logs/shareCoin2/';break;
            case 'shareExe':$logPath=PUBLIC_PATH.'logs/shareExe/';break;
            case 'ticketSharetemp':$logPath=PUBLIC_PATH.'logs/ticketSharetemp/';break;
            case 'ticketConsumetemp':$logPath=PUBLIC_PATH.'logs/ticketConsumetemp/';break;
            case 'ticketExe1':$logPath=PUBLIC_PATH.'logs/ticketExe1/';break;
            case 'ticketShare':$logPath=PUBLIC_PATH.'logs/ticketShare/';break;
            case 'sharecards':$logPath=PUBLIC_PATH.'logs/sharecards/';break;
            case 'getsharenum':$logPath=PUBLIC_PATH.'logs/getsharenum/';break;
            case 'shareQuanRecover':$logPath=PUBLIC_PATH.'logs/shareQuanRecover/';break;
            case 'jlhTicketShare':$logPath=PUBLIC_PATH.'logs/jlhTicketShare/';break;
            default :$logPath=PUBLIC_PATH.'logs/file/';
        }
        $msgString = date('Y-m-d H:i:s',time()).$msgString;
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents( $filename,$msgString,FILE_APPEND);
    }

    //分享券
    public function jlhTicketShare()
    {
        $data = getPostJson();
        $datami = trim($data['datami']);
        $datami = $this->DESedeCoder->decrypt($datami);
        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
        $datami = json_decode($datami, 1);
        $customid = trim($datami['customid']);
        $accountid = trim($datami['accountid']);
        $quanid = trim($datami['quanid']);
        $quanname = trim($datami['quanname']);
        $shareid = trim($datami['shareid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode . $customid .$accountid. $quanid . $quanname . $shareid);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥'));
        }
        $customid = decode($customid);
        empty($customid) && returnMsg(array('status' => '03', 'codemsg' => '用户编号缺失'));

        $customBool = $this->checkCustom($customid);
        if ($customBool == false) {
            returnMsg(array('status' => '04', 'codemsg' => '用户不存在'));
        }
        //------------2018-06-01---主账号下的子账户的信息---------
        $customsdata = M('customs_c')->where(array('customid'=>$customid))->select();
        $newcustoms=array();
        foreach($customsdata as $v){
            $newcustoms[$v['cid']]=$v['cid'];
        }
        $where = array('customid' =>array('in',$newcustoms), 'accountid'=>$accountid,'quanid' => $quanid);
        //  $where = array('customid' => $customid, 'accountid'=>$accountid,'quanid' => $quanid);
        //------------2018-06-01---主账号下的子账户的信息---------

        $quanAccount = M('quan_account')->where($where)->find();
        if ($quanAccount['enddate'] < date('Ymd', time())) {
            returnMsg(array('status' => '05', 'codemsg' => '券已过期'));
        }
        if($quanAccount['amount']<1){
            returnMsg(array('status' => '06', 'codemsg' => '券数量不足'));
        }
        $share=M('share_wastebooks')->where(array('shareid'=>$shareid))->find();
        if($share){
            returnMsg(array('status' => '07', 'codemsg' => '券编号已存在'));
        }
        if($share['flag']==2){
            returnMsg(array('status' => '08', 'codemsg' => '券已使用'));
        }
        $placeddate = date('Ymd', time());
        $placedtime = date('H:i:s', time());
        $exdate = "7";
        $shareid = trim($shareid);
        $tradeSql = "insert into share_wastebooks(shareid,customid,quanid,quanname,startdate,";
        $tradeSql .= "enddate,placeddate,placedtime,exdate,tradeamount,retailamount,flag,accountid,panterid)";
        $tradeSql .= "values('{$shareid}','{$customid}','{$quanid}','{$quanname}','{$quanAccount['startdate']}',";
        $tradeSql .= "'{$quanAccount['enddate']}','{$placeddate}','{$placedtime}','{$exdate}','1','0','0','{$accountid}','00000447')";

        //------------2018-06-01---查询条件会员信息id---------
        //$accountSql = "UPDATE QUAN_ACCOUNT set amount=amount-1 where customid='{$customid}' and quanid='{$quanid}' and accountid='{$accountid}'";
        $accountSql = "UPDATE QUAN_ACCOUNT set amount=amount-1 where  quanid='{$quanid}' and accountid='{$accountid}'";
        //------------2018-06-01---查询条件会员信息id---------

        $this->model->startTrans();
        $tradeIf = $this->model->execute($tradeSql);
        $accountIf = $this->model->execute($accountSql);
        if ($tradeIf == true && $accountIf == true) {
            $reslogs='quan_account表数据变动:'.$accountSql."\r\n"."\r\n";
            $reslogs.='share_wastebooks表数据变动:'.$tradeSql."\r\n"."\r\n";
            $this->writeLogs('jlhTicketShare',$reslogs);
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '分享成功'));
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '09', 'codemsg' => '分享失败'));
        }
    }

    //分享列表
    public function jlhShareQerry(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $page = trim($datami['page']);
        $countnum = trim($datami['countnum']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$page.$countnum);
        if($checkKey != $key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'03','codemsg'=>'用户编号缺失'));

        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'04','codemsg'=>'用户不存在'));
        }
        $shareList = $this->shareList($customid,$page,$countnum); //查找卡券的id/卡券的名称/卡券的
        if(empty($shareList)){
            returnMsg(array());
        }
        if($shareList==false){
            $shareList = array();
        }
        $totalCount=$shareList['0']['count'];
        returnMsg(array('status'=>'1','shareList'=>$shareList,'totalCount'=>$totalCount,'pageNo'=>$page,'pageSize'=>$countnum,'time'=>time()));
    }

    //分享列表
    public function shareList($customid,$page,$countnum){
        if(isset($page)){
            $newpage=intval($page);
        }
        if($newpage<=0){
            $newpage=1;
        }
        $offset=($newpage-1)*$countnum;
        $count=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where(array('cc.customid'=>$customid))
            ->count();
        if(empty($count)){
            return '0';
        }
        $where2=array('cc.customid'=>$customid);
        $where2['flag']=array("eq","0");
        $quaninfo=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where($where2)
            ->order('placeddate desc,placedtime desc')->select();
        $where3=array('cc.customid'=>$customid);
        $where3['flag']=array("neq","0");
        $quaninfoext=M("quankind")->alias('q')
            ->join('share_wastebooks  sw on q.quanid=sw.quanid')
            ->join('customs_c cc on cc.cid=sw.customid')
            ->where($where3)
            ->order('placeddate desc,placedtime desc')->select();
        $listnew = array();
        foreach($quaninfo as $key=>$val){
            $listnew[] = $val;
        }
        foreach($quaninfoext as $key=>$val){
            $listnew[] = $val;
        }
        $pagedata=array_slice($listnew,$offset,$countnum);

        //----------------2018-06-01-券过期时间的判断------开始---------
        $newarray=array();
        foreach($pagedata as $vv){
            $newarray[trim($vv['accountid'])]=trim($vv['accountid']);
        }
        $countwhere=array('accountid'=>array('in',$newarray));
        $quanAccount = M('quan_account')->where($countwhere)->select();
        $newquan=array();
        foreach($quanAccount as $v){
            $newquan[$v['accountid']]=$v['enddate'];
        }
        //----------------2018-06-01-券过期时间的判断-----结束----------

        $ticketList=array();
        if($pagedata==false){
            return '0';
        }else{
            foreach($pagedata as $key=>$val){
                $sharedate=$val['placeddate']. $val['placedtime'];//分享的时间
                //过期的时间
                $exdate=trim($val['exdate']);
                $exsharedata=strtotime($sharedate)+($exdate*86400);
                if($exsharedata<time()){
                    $ticketList[$key]['putuse']="已过期";
                }else{
                    if($val['flag']=="2"){
                        $ticketList[$key]['putuse']="已使用";
                    }elseif($val['flag']=="1"){
                        $ticketList[$key]['putuse']="已过期";
                    }else{
                        $ticketList[$key]['putuse']="未使用";
                    }
                }
                //-----2018-06-01-券的过期时间-----开始--
                $accountid=trim($val['accountid']);
                if(!empty($newquan[$accountid])) {
                    $quanenddate = strtotime($newquan[$accountid]);
                    if ($quanenddate < $exsharedata) {
                        $exsharedata = $quanenddate;
                    }
                }
                //-----2018-06-01-券的过期时间-----结束---

                $exshare=date("Y年m月d日",$exsharedata);
                $ticketList[$key]['shareid']=trim($val['shareid']);
                $ticketList[$key]['quanid']=$val['quanid'];
                $placeddate=$val['placeddate'];
                $placedtime=$val['placedtime'];
                $place=$placeddate.$placedtime;
                $placestime=strtotime($place);
                $dateplaces=date("Y年m月d日H:i",$placestime);
                $ticketList[$key]['placedalldate']=$dateplaces;
                $ticketList[$key]['quanname']=$val['quanname'];
                $ticketList[$key]['tradeamount']=$val['tradeamount'];
                $ticketList[$key]['exdate']=$val['exdate'];
                $ticketList[$key]['exshare']=$exshare;
                $ticketList[$key]['count']=$count;
                $ticketList[$key]['flag']=$val['flag'];
            }
            return $ticketList;
        }
    }

    //分享券详情
    public function shareQuanDetail(){
        $data=getPostJson();
        $datami=trim($data['datami']);
        $datami=$this->DESedeCoder->decrypt($datami);
        if($datami==false){
            returnMsg(array('status'=>'01','codemsg'=>'非法数据传入'));
        }
        $this->recordData($datami);
        $datami=json_decode($datami,1);
        $customid = trim($datami['customid']);
        $shareid = trim($datami['shareid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$customid.$shareid);
        if($checkKey != $key){
            returnMsg(array('status'=>'02','codemsg'=>'无效秘钥'));
        }
        $customid=decode($customid);
        empty($customid) && returnMsg(array('status'=>'03','codemsg'=>'用户编号缺失'));

        $customBool=$this->checkCustom($customid);
        if($customBool==false){
            returnMsg(array('status'=>'04','codemsg'=>'用户不存在'));
        }
        $shareResult=M('share_wastebooks')->where(array('customid'=>$customid,'shareid'=>$shareid))->find();
        if($shareResult==false){
            returnMsg(array('status'=>'05','codemsg'=>'分享券不存在'));
        }

        //--------------2018-06-01---增加判断子商户--------开始------
        $customsdata = M('customs_c')->where(array('customid'=>$customid))->select();
        $newcustoms=array();
        foreach($customsdata as $v){
            $newcustoms[$v['cid']]=$v['cid'];
        }
        $countwhere=array('customid'=>array('in',$newcustoms),'accountid'=>trim($shareResult['accountid']),'quanid'=>$shareResult['quanid']);
        $quanAccount = M('quan_account')->where($countwhere)->find();
        //--------------2018-06-01---增加判断子商户--------结束------
        //  $quanAccount = M('quan_account')->where(array('customid'=>$customid,'accountid'=>trim($shareResult['accountid']),'quanid'=>$shareResult['quanid']))->find();

        if($shareResult['flag']==0){
            if ($quanAccount['enddate'] < date('Ymd', time())) {
                returnMsg(array('status' => '06', 'codemsg' => '券已过期'));
            }
            $shareTime=strtotime($shareResult['placeddate'].$shareResult['placedtime'])+86400*$shareResult['exdate'];
            if($shareTime<time()){
                returnMsg(array('status'=>'07','codemsg'=>'券已过期'));
            }
        }
        $quankind=M('quankind')->where(array('quanid'=>$shareResult['quanid']))->find();
        $panters=M('panters')->where(array('panterid'=>$quankind['panterid']))->find();
        $shareResult['pantername']=$panters['namechinese'];
        $shareResult['beizu']=$quankind['memo'];
        if($quanAccount['enddate']<$shareResult['placeddate']){
            $shareResult['overduedate']=$quanAccount['enddate'];
        }else{
            $shareResult['overduedate']=date('Ymd',strtotime($shareResult['placeddate'].$shareResult['placedtime'])+86400*$shareResult['exdate']);
            //-----2018-06-01---添加券的分享券过期的时间----------
            if($quanAccount['enddate']<$shareResult['overduedate']){
                $shareResult['overduedate']=$quanAccount['enddate'];
            }
            //---------2018-06-01---------
        }
        if($shareResult['flag']==0){
            $shareResult['putuse']='未使用';
        }elseif($shareResult['flag']==1){
            $shareResult['putuse']='已过期';
        }else{
            $shareResult['putuse']='已使用';
        }
        returnMsg(array('status' => '1', 'list' => $shareResult));
    }

    //分享券回收
    public function shareQuanRecover(){
        $shareResult=M('share_wastebooks')->where(array('flag'=>0))->select();
        foreach($shareResult as $k=>$v){
            if($v['enddate']>date('Ymd',time())){
                $overduedate=date('Ymd',strtotime($v['placeddate'].$v['placedtime'])+86400*$v['exdate']);
                if($overduedate==date('Ymd',time())){
                    $quanid=trim($v['quanid']);
                    $accountid=trim($v['accountid']);
                    $shareid=trim($v['shareid']);
                    $accountSql = "UPDATE QUAN_ACCOUNT SET amount=amount+1 WHERE quanid='{$quanid}' AND accountid='{$accountid}'";
                    $tradeSql="UPDATE SHARE_WASTEBOOKS SET tradeamount=tradeamount-1,flag=1 WHERE shareid='{$shareid}'";
                    $this->model->startTrans();
                    $tradeIf = $this->model->execute($tradeSql);
                    $accountIf = $this->model->execute($accountSql);
                    if ($tradeIf == true && $accountIf == true) {
                        $reslogs='quan_account表数据变动:'.$accountSql."\r\n"."\r\n";
                        $reslogs.='share_wastebooks表数据变动:'.$tradeSql."\r\n"."\r\n";
                        $this->writeLogs('shareQuanRecover',$reslogs);
                        $this->model->commit();
                    } else {
                        $this->model->rollback();
                    }
                }
            }
        }
    }
}
?>
