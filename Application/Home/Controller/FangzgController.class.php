<?php
namespace Home\Controller;
use Org\Util\idCard;
use Org\Util\rndChinaName;
use Think\Controller;
use Think\Model;
class FangzgController extends CommonController {
    protected $model;
    protected $customs;
    protected $account;
    protected $customs_c;
    protected $cards;
    protected $custompl;
    protected $cardpl;
    //protected $userid;
    protected $fangzg;

    public function _initialize(){
        parent::_initialize();
        $this->model=new model();
        $this->customs=M('customs');
        $this->account=M('account');
        $this->cards=M('cards');
        $this->custompl=M('custom_purchase_logs');
        $this->cardpl=M('card_purchase_logs');
        //$this->userid='0000000000000000';
        $this->fangzg=M('fangzg');
    }

    public function index(){
        if(IS_POST){
            $start = trim(I('post.start',''));
            $end = trim(I('post.end',''));
            $panterid=trim(I('post.panterid',''));
            if(empty($panterid)){
                $this->error('项目必选');
            }
            $url=C("fangzgIP").'/posindex.php/Index/jycard';
            if($panterid==0) $panterid='';
            if(empty($panterid)){
                $this->error('请选择同步的项目');
            }
            if(!empty($start)){
                if(str_replace('-','',$start)<'20151101'){
                    $this->error('其实日期不能早于11月');
                }
            }
            $pantersMap=array('panterid'=>$panterid);
            $postData=array('start'=>$start.' 00:00:00','end'=>$end.' 23:59:59','panterid'=>$panterid);
            $sycData=$this->crul_post($url,$postData);
            $orderArr=json_decode($sycData,true);
            if($sycData!==false){
                $orderArr=json_decode($sycData,true);
                if($orderArr['status']==0){
                    $this->error($orderArr['codeMsg']);
                }else{
                    set_time_limit(1800);
                    $res=$this->restore($orderArr['list']);
                    if($res==true){
                        $this->success('同步成功');
                    }else{
                        $this->error('同步失败');
                    }
                }
            }else{
                $this->error('网络问题同步失败，请重试！');
            }
        }else{
            $start=date('Y-m-d');
            $end=date('Y-m-d');
        }
        $map['hysx']='房产/物业';
        $fcList=M('panters')->where($map)->field('panterid,namechinese')->select();
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('fcList',$fcList);
        $this->display();
    }

    public function recharge(){
        //状态：0未录入，1：已录入
        $arrs=array(0=>"未录入",1=>"已录入");
        $status =trim(I('get.status',''));
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
//        $starthour=trim(I('get.starthour',''));
//        $startminite=trim(I('get.startminite',''));
//        $endhour=trim(I('get.endhour',''));
//        $endminite=trim(I('get.endminite',''));
        $panterid=trim(I('get.panterid',''));
        if($start!='' && $end==''){
            $startdate=strtotime(date($start.' 00:00:00'));
            $where['f.acccheckdate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate=strtotime(date($end.' 23:59:59'));
            $where['f.acccheckdate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate=strtotime(date($start.' 00:00:00'));
            $enddate=strtotime(date($end.' 23:59:59'));
            $where['f.acccheckdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=strtotime(date('Y-m-d 00:00:00', time()));
            $enddate=strtotime(date('Y-m-d 23:59:59', time()));
            $where['f.acccheckdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',time()));
            $this->assign('enddate',date('Y-m-d',time()));
        }
//        if($starthour!=false){
//            $starthours=$this->getHours($starthour);
//            $map['starthour']=$starthour;
//        }else{
//            $starthours=$this->getHours('00');
//            $map['starthour']='00';
//        }
//        if($startminite!=false){
//            $startminites=$this->getMinites($startminite);
//            $map['startminite']=$startminite;
//        }else{
//            $startminites=$this->getMinites('00');
//            $map['startminite']='00';
//        }
//        if($endhour!=false){
//            $endhours=$this->getHours($endhour);
//            $map['endhour']=$endhour;
//        }else{
//            $endhours=$this->getHours('23');
//            $map['endhour']='23';
//        }
//        if($endminite!=false){
//            $endminites=$this->getMinites($endminite);
//            $map['endminite']=$endminite;
//        }else{
//            $endminites=$this->getMinites('59');
//            $map['endminite']='59';
//        }
        if($this->panterid!='FFFFFFFF'){
            $where['f.panterid']=$this->panterid;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        if(!empty($panterid)){
            $where['p.panterid']=$panterid;
            $map['panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        $where['f.acccheckstatus']=1;
        $where['f.status']=$status==""?0:$status;
        $map['status']=$where['f.status'];
        $field='f.*,p.namechinese pname';
        $count=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->order('amount asc')->count();
        //echo $this->fangzg->getLastSql();
        $p=new \Think\Page($count, 20);
        $list=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->field($field)->order('amount asc')->limit($p->firstRow.','.$p->listRows)->select();
        //echo $this->fangzg->getLastSql();
        $amount_sum=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->sum('amount');
        $cardNum=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->sum('ceil(amount/5000)');
        session('fzgMap',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('amount_sum',$amount_sum);
        $this->assign('cardNum',$cardNum);
        $map['hysx']='房产/物业';
        $fcList=M('panters')->where($map)->field('panterid,namechinese')->select();
//echo $this->fangzg->getLastSql();
        $this->assign('fcList',$fcList);
//        $this->assign('starthours',$starthours);
//        $this->assign('startminites',$startminites);
//        $this->assign('endhours',$endhours);
//        $this->assign('endminites',$endminites);
        $this->assign("arrs",$arrs);
        $this->assign("sts",$where['f.status']);
        $this->display();
    }

    public function consume(){
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $panterid=trim(I('get.panterid',''));
        $status=trim(I('get.status',''));
        if($status=='') $status=0;

        $map['status']=$status;
        $this->assign('status',$status);
        if($status!==false){
            $where['fc.status']=$status;
        }
        if($status==0){
            $where['f.status']=1;
            $where['fc.status']=array('in',array(0,2));
        }elseif($status==1){
            $where['f.status']=array('in',array(1,2));
            $where['fc.status']=1;
        }
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['cpl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ymd',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['cpl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($panterid)){
            $where['f.panterid']=$panterid;
            $map['panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        if($this->panterid!='FFFFFFFF'){
            $where['f.panterid']=$this->panterid;
            $this->assign('is_admin',0);
        }else{
            $this->assign('is_admin',1);
        }
        //$where['f.tradeid']='20151102102852_5876';
        //6889395600000001798,6889395600000001799,6889395600000001800
        $field='f.tradeid,f.status,c.cardno,cpl.card_purchaseid,fc.amount,cpl.placeddate placeddate,';
        $field.='cu.namechinese cuname,p.namechinese pname,fc.status status1,fc.consumedamount consumedamount,a.amount aamount';
        $where['a.type']='00';
        $count=$this->fangzg->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
            ->where($where)->count();
        $p=new \Think\Page($count, 20 );
        $list=$this->fangzg->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
//        echo $this->fangzg->getLastSql();

        $consume_amount=$this->fangzg->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
//            ->where($where)->sum('a.amount');

            ->where($where)->field('sum(a.amount) amount_sum,sum(consumedamount) consumedamount_sum')->find();

        session('consumeMap',$where);
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('consume_amount',$consume_amount);
        $map1['hysx']='房产/物业';
        $fcList=M('panters')->where($map1)->field('panterid,namechinese')->order("nlssort(namechinese,'NLS_SORT=SCHINESE_PINYIN_M')")->select();
        $this->assign('fcList',$fcList);
        $this->display();
    }

    /**
     *  批量扣款导表Excel
     */
    public function consume_excel(){
        $this->setTimeMemory();
        $field='f.tradeid,f.status,c.cardno,cpl.card_purchaseid,fc.amount,cpl.placeddate placeddate,';
        $field.='cu.namechinese cuname,p.namechinese pname,fc.status status1,fc.consumedamount consumedamount,a.amount aamount';
        $where['a.type']='00';
        $consumemap=session('consumeMap');
        foreach($consumemap as $key=>$val){
            $where[$key]=$val;
        }
        $model = new model();
//        $count=$this->fangzg->alias('f')
//            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
//            ->join('cards c on c.cardno=fc.cardno')
//            ->join('account a on a.customid=c.customid')
//            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
//            ->join('customs_c cc on cc.cid=c.customid')
//            ->join('customs cu on cu.customid=cc.customid')
//            ->join('panters p on p.panterid=f.panterid')
//            ->where($where)->count();
        $consume_amount=$model->table('fangzg')->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
//            ->where($where)->sum('a.amount aamount')->find();
            ->where($where)->field('sum(a.amount) amount_sum,sum(consumedamount) consumedamount_sum')->find();
//        var_dump($consume_amount);exit;
        $list=$model->table('fangzg')->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
            ->where($where)->field($field)->order("f.tradeid desc")->select();
        $strlist = "订单编号,卡号,充值单号,充值金额,已扣金额,卡余额,充值日期,账户名字,项目名称,状态";
        $strlist = iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach($list as $key=>$val_info){

//            $val_info['card_purchaseid'] =iconv('utf-8','gbk',$val_info['card_purchaseid']);
//            $val_info['pname']=iconv('utf-8','gbk',$val_info['pname']);
//            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
//            $val_info['amount']=iconv('utf-8','gbk',$val_info['amount']);
//            $strlist.=$val_info['cardno']."\t,".$val_info['customid'];
//            $strlist.=$val_info['tradeid']."\t,".$val_info['tradeid'];


            $val_info['tradeid'] = iconv('utf-8','gbk',$val_info['tradeid']);
            $val_info['pname'] = iconv('utf-8','gbk',$val_info['pname']);
            $val_info['cuname']=iconv('utf-8','gbk',$val_info['cuname']);
            $val_info['cardno']=iconv('utf-8','gbk',$val_info['cardno']);
            $val_info['consumedamount']=iconv('utf-8','gbk',floatval($val_info['consumedamount']));
            $strlist.=$val_info['tradeid']."\t,".$val_info['cardno']."\t,";
            $strlist.=$val_info['card_purchaseid']."\t,".floatval($val_info['amount'])."\t,";
            $strlist.=floatval($val_info['consumedamount'])."\t,";
//            $strlist.="\t,".$val_info['card_purchaseid']."\t,".floatval($val_info['amount']);
            $strlist.=floatval($val_info['aamount'])."\t,";
            $strlist.=date('Y-m-d',strtotime($val_info['placeddate']))."\t,";
            $strlist.=$val_info['cuname']."\t,".$val_info['pname']."\t,";
            $strlist.=$val_info['status']."\n";
        }
        $filename='批量扣款表'.date('YmdHis');
        $filename= iconv("utf-8","gbk",$filename);
        $strlist.=',,,,'.$consume_amount['amount_sum']."\t,".$consume_amount['consumedamount_sum']."\t,,,,,\n";
        $this->load_csv($strlist,$filename);

    }

    public function sycData(){
        $url= C("fangzgIp").'/posindex.php/Index/jycard';
        $start=trim($_POST['start']);
        $end=trim($_POST['end']);
        $panterid=trim($_POST['panterid']);
        if($panterid==0) $panterid='';
        //echo str_replace('-','',$start);exit;
        if(!empty($start)){
            if(str_replace('-','',$start)<'20151101'){
                $this->error('其实日期不能早于11月');
            }
        }
        $postData=array('start'=>$start,'end'=>$end,'panterid'=>$panterid);
        $sycData=$this->crul_post($url,$postData);
        if(!empty($sycData)){
            $orderArr=json_decode($sycData,true);
            if($orderArr['status']==0){
                $this->error('无订单');
            }else{
                $res=$this->restore($orderArr['list']);
                if($res==true){
                    $this->success('同步成功');
                }else{
                    $this->error('同步失败');
                }
            }
        }else{
            $this->error('网络故障，请求失败');
        }
    }

    public function restore($array){
        if(empty($array)){
            return false;
        }
        $sql="INSERT INTO FANGZG";
        $trades=array();
        foreach($array as $key=>$val){
            if(!empty($val['photoReverse'])){
                $photoReverse=$this->catchImg($val['photoReverse']);
                if($photoReverse!=false){
                    $reverseImg=$this->saveImg($photoReverse);
                }else{
                    $reverseImg='';
                }
            }else{
                $reverseImg='';
            }
            if(!empty($val['photoJust'])){
                $photoJust=$this->catchImg($val['photoJust']);
                if($photoJust!=false){
                    $justImg=$this->saveImg($photoJust);
                }else{
                    $justImg='';
                }
            }else{
                $justImg='';
            }
            //echo $reverseImg.'--'.$justImg;exit;
           // $name=trim($val['name']);
            $name=trim(str_replace("'","_",$val['name']));
            $personid = trim(str_replace("'","",$val['personid']));
            $address = trim(str_replace("'","",$val['personid']));
            $pantersMap=array('panterid'=>$val['panterid']);
            $panter=M('panters')->where($pantersMap)->field('parent')->find();
            if($key!=0) $sql.=" UNION ALL ";
//            $sql.=" SELECT '{$val['tradeid']}','{$name}','{$val['personid']}','{$val['linktel']}'";
//            $sql.=",'{$val['amount']}','{$panter['parent']}','".time()."',0,0,0,'{$val['panterid']}',0,0,'','','','',0";
//            $sql.=",'{$val['ordertime']}','{$val['address']}',0,0,'{$reverseImg}','{$justImg}' FROM DUAL";
//            $trades[]="'".$val['tradeid']."'";
            $sql.=" SELECT '{$val['tradeid']}','{$name}','{$personid}','{$val['linktel']}'";
            $sql.=",'{$val['amount']}','{$panter['parent']}','".time()."',0,0,0,'{$val['panterid']}',0,0,'','','','',0";
            $sql.=",'{$val['ordertime']}','{$address}',0,0,'{$reverseImg}','{$justImg}' FROM DUAL";
            $trades[]="'".$val['tradeid']."'";
        }
        //$this->fangzgLogs($sql,'2222');
        $tradeArr['tradeid']=implode(',',$trades);
        if($this->model->execute($sql)){
            $url=C('fangzgIp').'/posindex.php/Index/jycard2';
            $res=$this->crul_post($url,$tradeArr);
            return true;
        }else{
            return false;
        }
    }

    public function batchRecharge(){
        set_time_limit(0);
        $tradeidArr=$_POST['tradeid'];

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($tradeidArr) . "\n\t", "YjTbpost", "createAccount");

        if(!empty($tradeidArr)){
            $where['tradeid']=array('in',$tradeidArr);
        }else{
            $this->error('请选择要充值的订单',U('Fangzg/recharge'));
        }
        $bool=$this->checkProcedure();
        if($bool==false){
            $this->error('上一批程序尚未进行结束，请等待',U('Fangzg/recharge'));
        }
        //$this->closeProcedureDoor();
        $logStr = "##############################################################################\r\n";
        $logStr .= '获取订单号:'.serialize($tradeidArr)."\r\n";
        $recordList=$this->fangzg->where($where)->select();
        //$logStr .= 'sql:'.$this->fangzg->getLastSql()."\r\n";

        $successCount=0;
        $totalRechargedMoney=0;
        foreach($recordList as $key=>$val){
            $logStr .= "开始处理订单:".$val['tradeid']."\r\n";
            $flag=$this->checkOrder($val['tradeid']);
            if($flag==true){
                $logStr .= "订单异常，结束操作!!!\r\n";
                continue;
            }
            $this->lockOrder($val['tradeid']);
            $unRechargeAmount=$val['amount']-$val['rechargedamount'];
            $logStr .= '订单详情:'.$val['tradeid'].'--'.$val['name'].'--'.$unRechargeAmount.'--'.date('Y-m-d H:i:s',time())."\r\n";
            $panterid=$val['belongpanterid'];
            $customMap=array('namechinese'=>$val['name'],'personid'=>$val['personid'],'customlevel'=>'建业线上会员');
            $custom=$this->customs->where($customMap)->field('customid')->find();//如果该用户有重复数据会有异常问题
            if($custom==false){
                $logStr .= "会员不存在,新增会员~~!\r\n";
                $customInfo=array(
                    'namechinese'=>$val['name'],
                    'personid'=>$val['personid'],
                    'linktel'=>$val['linktel'],
                    'panterid'=>$panterid,
                    'residaddress'=>$val['address'],
                    'frontimg'=>$val['fontimg'],
                    'reverseimg'=>$val['reverseimg']
                );
                $customid=$this->createCustoms($customInfo);
                $logStr .= '新增会员:'.$customInfo['namechinese'].'--'.$customid."\r\n";
                $cardNum = $this->getCardNum($unRechargeAmount);
                $logStr .= '需新发卡/充值数量:'.$cardNum."\r\n";
                $getCards=$this->getCards($cardNum,$panterid);
                $logStr.="取卡时间：".date('Y-m-d H:i:s')."取卡卡号：".implode(',',$getCards)."\r\n";
                $logStr.=$val['tradeid'].'售卡充值情况:'. $val['name'].'--'.$unRechargeAmount."\r\n";
                //$this->fangzgLogs($logStr,$val['tradeid']);
                if($getCards==false){
                    //写入卡池不足的日志
                    $logStr .= "卡池卡数量不足,制卡结束!!!\r\n";
                    $logStr .= "------------------------------------------------------------------------------\r\n";
                    $this->fangzgLogs($logStr,$val['tradeid']);
                    $this->unlockOrder($val['tradeid']);
                    continue;
                }
                //将卡池中取出的卡开卡并充值
                $openCard=$this->openCards($getCards,$customid,$panterid,$unRechargeAmount,$val['tradeid']);
                if($openCard==false){
                    //写入开卡失败日志
                    $logStr .= "新会员卡开，新卡失败!!!\r\n";
                    $logStr .= "####################\r\n";
                    //$this->fangzgLogs($logStr,$val['tradeid']);
                    $this->unlockOrder($val['tradeid']);
                    continue;
                }
                $rechargedMoney=$openCard['rechargedMoney'];
                $rechargedArr=$openCard['rechargedArr'];
                $totalRechargedMoney+=$rechargedMoney;

                $logStr .= "完成充值金额:{$rechargedMoney},待充值金额:{$unRechargeAmount}\r\n";
                //$this->fangzgLogs($logStr,$val['tradeid']);
                if($unRechargeAmount==$rechargedMoney){
                    $fangzgMap=array('tradeid'=>$val['tradeid']);
                    $data=array('customid'=>$customid,'status'=>1);
                    if($this->fangzg->where($fangzgMap)->save($data)){
                        $successCount++;
                        $logStr .= "订单号:(".$val['tradeid'].")充值成功\r\n";
                        $logStr .= serialize($getCards)."\r\n";
                        $logStr .= "####################\r\n";
                    }else{
                        $logStr .= "充值成功，但订单更新失败:".$val['tradeid'].'--'.$data['customid']."\r\n";
                        $logStr .= "####################\r\n";
                        $this->unlockOrder($val['tradeid']);
                        continue;
                    }
                }else{
                    $logStr .= "订单号:(".$val['tradeid'].")充值未完全\r\n";
                    $logStr .= "充值未完全成功,充值完成金额：{$rechargedMoney},充值完成卡号:".serialize($rechargedArr)."\r\n";
                    $logStr .= "####################\r\n";
                }
            }else{
                $logStr .= "会员已存在:".$custom['customid']."\r\n";
                $ownCards=$this->getOwnCards($custom['customid']);//确认查询的都是余额为零的卡号
                $needNum=$this->getCardNum($unRechargeAmount);
                $ownCardsNum=count($ownCards);
                $logStr .= "卡余额为零卡数量:".$ownCardsNum.",需新发卡/充值数量:".$needNum."\n";
                $logStr .=$val['tradeid'].'充值情况:'. $val['name'].'--'.$unRechargeAmount."\n";
                //保存用户发卡信息
                //$this->fangzgLogs($logStr,$val['tradeid']);
                if($ownCardsNum>=$needNum){
                    for($i=0;$i<$needNum;$i++){
                        $regCards[$i]=$ownCards[$i];
                    }
                    $regRes=$this->cardRecharge($regCards,$custom['customid'],$unRechargeAmount,$val['tradeid']);//充值
                    $logStr .= "老卡充值卡号:".serialize($regCards)."\r\n";
                    $logStr .= "老卡充值金额:".$val['amount'].'--会员ID:'.$custom['customid'].'--订单号:'.$val['tradeid']."\r\n";
                    //$this->fangzgLogs($logStr,$val['tradeid']);
                    if($regRes==false){
                        //写入充值失败日志
                        $logStr .= "老卡充值失败,充值结束!!!\r\n";
                        $logStr .= "####################\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                        $this->unlockOrder($val['tradeid']);
                        continue;
                    }
                    $rechargedMoney=$regRes['rechargedMoney'];
                    $rechargedArr=$regRes['rechargedArr'];
                    $totalRechargedMoney+=$rechargedMoney;

                    $logStr .= "完成充值金额:{$rechargedMoney},待充值金额:{$unRechargeAmount}\r\n";
                    //$this->fangzgLogs($logStr,$val['tradeid']);
                    if($unRechargeAmount==$rechargedMoney){
                        $data=array('customid'=>$custom['customid'],'status'=>1);
                        $fangzgMap=array('tradeid'=>$val['tradeid']);
                        if($this->fangzg->where($fangzgMap)->save($data)){
                            $successCount++;
                            $logStr .= "订单号(".$val['tradeid'].")充值成功\r\n";
                            $logStr .= serialize($getCards)."\r\n";
                            $logStr .= "----------------------\r\n";
                            //$this->fangzgLogs($logStr,$val['tradeid']);
                        }else{
                            $logStr .= "订单号充值成功，更新订单失败,充值结束!!!\r\n";
                            $logStr .= "####################\r\n";
                            $this->fangzgLogs($logStr,$val['tradeid']);
                            $this->unlockOrder($val['tradeid']);
                            continue;
                        }
                    }else{
                        $logStr = "订单号:(".$val['tradeid'].")充值未完全\r\n";
                        $logStr .= "充值未完全成功,充值完成金额：{$rechargedMoney},充值完成卡号:".serialize($rechargedArr)."\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                        $logStr .= "----------------------\r\n";
                    }
                }else{
                    $cardNum=$needNum-$ownCardsNum;
                    $getCards=$this->getCards($cardNum,$panterid);//卡池取卡
                    //var_dump($getCards);exit;
                    $logStr .= "需新增发卡数量:".$cardNum."\r\n";
                    $logStr .= "卡池取卡:".serialize($getCards)."\r\n";
                    //$this->fangzgLogs($logStr,$val['tradeid']);
                    if($getCards==false){
                        //写入卡池卡不足日志
                        $logStr .= "开卡失败：卡池卡号数量不足\r\n";
                        $logStr .= "####################\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                        $this->unlockOrder($val['tradeid']);
                        continue;
                    }
                    $openFee=$unRechargeAmount-$ownCardsNum*5000;
                    $openCard=$this->openCards($getCards,$custom['customid'],$panterid,$openFee,$val['tradeid']);//卡开并充值
                    if($openCard==false){
                        //添加新卡开卡开失败日志
                        $logStr .= "开卡失败:".serialize($getCards)."\r\n";
                        $logStr .= "####################\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                        $rechargedMoney1=0;
                    }else{
                        $rechargedMoney1=$openCard['rechargedMoney'];
                        $rechargedArr1=$openCard['rechargedArr'];
                        $logStr .= "新卡充值成功:".serialize($rechargedArr1)."\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                    }
                    if($ownCardsNum>0){
                        $rechargeFee=$unRechargeAmount-$openFee;
                        $regRes=$this->cardRecharge($ownCards,$custom['customid'],$rechargeFee,$val['tradeid']);
                        if($regRes==false){
                            //给旧卡充值失败日志
                            $logStr .= "老卡充值失败:".serialize($ownCards)."\r\n";
                            $logStr .= "####################\r\n";
                            //$this->fangzgLogs($logStr,$val['tradeid']);
                            $rechargedMoney2=0;
                            $rechargedArr2=array();
                        }else{
                            $rechargedMoney2=$regRes['rechargedMoney'];
                            $rechargedArr2=$regRes['rechargedArr'];
                            $logStr .= "老卡充值成功:".serialize($rechargedArr2)."\r\n";
                            //$this->fangzgLogs($logStr,$val['tradeid']);
                        }
                    }else{
                        $rechargedMoney2=0;
                        $rechargedArr2=array();
                    }
                    $rechargedMoney=$rechargedMoney1+$rechargedMoney2;
                    $rechargedArr=array_merge($rechargedArr1,$rechargedArr2);
                    $totalRechargedMoney+=$rechargedMoney;
                    $logStr .= "完成充值金额:{$rechargedMoney},待充值金额:{$unRechargeAmount}\r\n";
                    //$this->fangzgLogs($logStr,$val['tradeid']);
                    if($unRechargeAmount==$rechargedMoney){
                        $data=array('customid'=>$custom['customid'],'status'=>1);
                        $fangzgMap=array('tradeid'=>$val['tradeid']);
                        if($this->fangzg->where($fangzgMap)->save($data)){
                            $logStr .= "订单号(".$val['tradeid'].")充值成功\r\n";
                            $logStr .= "----------------------\r\n";
                            //$this->fangzgLogs($logStr,$val['tradeid']);
                            $successCount++;
                        }else{
                            $logStr .= "订单号{$val['tradeid']}充值成功,但更新订单失败\r\n";
                            //$this->fangzgLogs($logStr,$val['tradeid']);
                            $logStr .= "####################\r\n";
                            $this->unlockOrder($val['tradeid']);
                            continue;
                        }
                    }else{
                        $logStr .= "订单号:(".$val['tradeid'].")充值未完全成功\r\n";
                        $logStr .= "充值未完全成功,充值完成金额：{$rechargedMoney},充值完成卡号:".serialize($rechargedArr)."\r\n";
                        $logStr .= "####################\r\n";
                        //$this->fangzgLogs($logStr,$val['tradeid']);
                    }
                }
            }
            $this->unlockOrder($val['tradeid']);
        }
        if($successCount>0){
            $this->openProcedureDoor();
            if($successCount==count($recordList)){
                $logStr .= '执行成功'.$successCount.'条记录,总计'.count($recordList).'条记录\r\n';
                $logStr .= "\r\n\r\n";
                $this->fangzgLogs($logStr,date('Y-m-d', time()));
                $this->success('执行成功'.$successCount.'条记录,充值金额'.$totalRechargedMoney,U('Fangzg/recharge'));
            }else{
                $logStr .= '执行未完全失败，成功'.$successCount.'条记录,总计'.count($recordList).'条记录\r\n';
                $logStr .= "\r\n\r\n";
                $this->fangzgLogs($logStr,date('Y-m-d', time()));
                $this->error('执行未完全失败，成功'.$successCount.'条记录,成功充值金额'.$totalRechargedMoney,U('Fangzg/recharge'));
            }
        }else{
            $this->openProcedureDoor();
            $logStr .= '执行失败'.$successCount.'条记录,总计'.count($recordList).'条记录\r\n';
            $logStr .= "####################\r\n";
            $this->fangzgLogs($logStr,date('Y-m-d', time()));
            $this->error('执行失败',U('Fangzg/recharge'));
        }
    }

    public function checkProcedure(){
        $map=array('name'=>'door');
        $list=D('fangzg_door')->where($map)->find();
        if($list['status']==1){
            return true;
        }else{
            return false;
        }
    }

    public function closeProcedureDoor(){
        $map=array('name'=>'door');
        $data=array('status'=>0);
        D('fangzg_door')->where($map)->save($data);
    }
    public function openProcedureDoor(){
        $map=array('name'=>'door');
        $data=array('status'=>1);
        D('fangzg_door')->where($map)->save($data);
    }
    //创建会员
    protected function createCustoms($customArr){
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber("customid");
        $nameChinese=$customArr['namechinese'];
        $personId=$customArr['personid'];
        $linkTel=$customArr['linktel'];
        $currentDate=date('Ymd',time());
        $panterid=$customArr['panterid'];
        $residaddress=$customArr['residaddress'];
        $fontimg=$customArr['fontimg'];
        $reverseimg=$customArr['reverseimg'];
        $nameEnglish='';
        if(empty($nameChinese)){
            $rndChinese=new rndChinaName();
            $nameChinese=$rndChinese->getName();
            $nameEnglish='特殊';
        }
        if(empty($personId)){
            $idCard=new idCard();
            $personId=$idCard->getIdCard();
            $nameEnglish='特殊';
        }
        $sql="INSERT INTO CUSTOMS(customid,namechinese,nameenglish,personid,linktel,placeddate,paswd,personidtype,customlevel,frontimg,reserveimg) values";
        $sql.="('{$customid}','{$nameChinese}','{$nameEnglish}','{$personId}','{$linkTel}','{$currentDate}','888888','身份证','建业线上会员','{$fontimg}','{$reverseimg}')";
        $this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
        if($this->model->execute($sql)){
            return $customid;
        }
    }
    //读取卡数量
    public function getCardNum($amount){
        return ceil($amount/5000);
    }
    //从卡池里去卡
    public function getCards($num,$panterid){
        if($panterid==false){
            return false;
        }
        //echo $num.'-'.$panterid;
        $where=array('panterid'=>$panterid,'status'=>'N');
        $where['_string']=" cardfee is null or cardfee='0'";
        $where['cardkind']='6889';
        $cardList=$this->cards->where($where)->field('cardno')->limit(0,$num)->select();
        //var_dump($cardList);exit;
        //echo $this->cards->getLastSql();exit;
        if(count($cardList)<$num){
            return false;
        }else{
            $list=$this->serializeArr($cardList,'cardno');
            return $list;
        }
    }
    //开卡执行
    public function openCards($cardArr,$customid,$panterid,$amount,$tradeid){
        $c=0;
        $userid =  $this->userid;
        //$userid='0000000000000221';
        $string='';
        $string1='';
        $rechargedMoney=0;
        $rechargedArr=array();
        foreach($cardArr as $val){
            $this->model->startTrans();
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');
            if($c!=(count($cardArr)-1)){
                $rechargeMoney=5000;
            }else{
                $rechargeMoney=$amount-$c*5000;
            }
            $string.="订单：{$tradeid},卡号：{$cardno},充值金额：{$rechargeMoney}\n";
            //写入购卡单并审核
            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','转账','";
            $customplSql.=$this->userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
            $customplSql.="',1,'','房掌柜购卡','1','".$panterid."','".$userid."',NULL,'0',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";
            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'房掌柜购卡审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

            $customplIf=$this->model->execute($customplSql);
            $auditlogsIf=$this->model->execute($auditlogsSql);

            $userip=$_SERVER['REMOTE_ADDR'];
            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');

            //写入购卡充值单
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
            $cardplSql.="'{$userid}','{$panterid}','{$userip}','00000000')";
            $cardplIf=$this->model->execute($cardplSql);

            $where['customid']=$customid;
            $card=$this->cards->where($where)->find();
            if($card==false){
                //看会员编号一致的卡是否存在，不存在，以会员编号作为卡的编号
                $cardId=$customid;
            }else{
                //若存在，则需另外生成卡编号
                //$cardId=$this->getnextcode('customs',8,$tradeid);
                $cardId=$this->getFieldNextNumber('customid');
                $customSql="UPDATE customs SET cardno='teshu' where customid='".$customid."'";
                $this->model->execute($customSql);
            }
            //执行激活操作
            $cardAlSql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $cardAlSql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$cardId}'";
            $cardAlSql.=",'{$panterid}','00000000')";
            $cardAlIf=$this->model->execute($cardAlSql);

            //关联会员卡号
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$cardId."')";
            $customsIf=$this->model->execute($customcSql);

            //更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+3 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
            $cardIf=$this->model->execute($cardSql);

            //给卡片添加账户并给账户充值
            $balanceMap=array('type'=>'00','customid'=>$cardId);
            $balanceAccount=$this->account->where($balanceMap)->find();
            if($balanceAccount==false){
                //$acid = $this->getnextcode('account',8);
                $acid = $this->getFieldNextNumber('accountid');
                $balanceSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
                $balanceSql.=$acid."','".$cardId."','".$rechargeMoney."','00',NULL)";
                $balanceIf=$this->model->execute($balanceSql);
            }else{
                $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$cardId."' and type='00'";
                $balanceIf=$this->model->execute($balanceSql);
            }
            $fangzgCSql="INSERT INTO FANGZG_C VALUES('{$tradeid}','{$cardno}','{$rechargeMoney}',0,'{$cardpurchaseid}',0)";
            $fangzgCIf=$this->model->execute($fangzgCSql);

            //添加积分账户
            $pointMap=array('type'=>'01','customid'=>$cardId);
            $pointAccount=$this->account->where($pointMap)->find();
            if($pointAccount==false){
                //$acid = $this->getnextcode('account',8);
                $acid = $this->getFieldNextNumber('accountid');
                $pointSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
                $pointSql.=$acid."','".$cardId."','0','01',NULL)";
                $this->model->execute($pointSql);
            }
            $fangzSql="UPDATE FANGZG SET rechargedamount=nvl(rechargedamount,0)+".$rechargeMoney." where tradeid='{$tradeid}'";
            $fangzgIf=$this->model->execute($fangzSql);
            $string1.=$cardno.'执行sql：'."\r\n".$customplSql."\r\n".$auditlogsSql."\r\n".$cardplSql."\r\n".$customcSql."\r\n";
            $string1.=$cardSql."\r\n".$balanceSql."\r\n".$fangzgCSql."\r\n".$pointSql."\r\n".$fangzSql."\r\n\r\n";
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$cardAlIf==true&&$customsIf==true&&$cardIf==true&&$balanceIf==true&&$fangzgCIf==true&&$fangzgIf==true){
                $string1.="充值成功卡号：{$cardno}\r\n";
                $rechargedMoney+=$rechargeMoney;
                $rechargedArr[]=$cardno;
                $c++;
                $this->model->commit();
            }else{
                $string1.="充值失败卡号：{$cardno}\r\n";
                $this->model->rollback();
            }
        }
        if(!empty($string)){
            $string.=$string1."\n";
            $this->fangzgLogs($string,$tradeid);
        }
        if($c>0){
            $retArr=array('c'=>$c,'rechargedMoney'=>$rechargedMoney,'rechargedArr'=>$rechargedArr);
            return $retArr;
        }else{
            return false;
        }
    }
    //消费执行
    public function batchConsume(){
        //$this->model->startTrans();
        $consumeAmount=trim($_POST['consumeAmount']);
        if(empty($consumeAmount)){
            $this->error('请输入扣款金额');
        }
        $where=session('consumeMap');
        $totalAmount=$this->fangzg->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
            ->where($where)->sum('a.amount');
        //echo $consumeAmount.'------'.$consume_amount;exit;
        if($consumeAmount>$totalAmount){
            $this->error('扣款金额大于剩余总金额');
        }

        $field='f.tradeid,c.cardno,c.customid,fc.amount,a.amount cardbalance,p.panterid panterid';
        $list=$this->fangzg->alias('f')
            ->join('fangzg_c fc on f.tradeid=fc.tradeid')
            ->join('cards c on c.cardno=fc.cardno')
            ->join('account a on a.customid=c.customid')
            ->join('card_purchase_logs cpl on cpl.card_purchaseid=fc.card_purchaseid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('panters p on p.panterid=f.panterid')
            ->where($where)->field($field)->select();
        $c=0;
        $c1=0;
        $waitAmount=$consumeAmount;
        $consumeLogs=array();
        foreach($list as $key=>$val){
            if($waitAmount<=0) break;
            $cardno=$val['cardno'];
            //$amount=$val['amount'];
            $consumeLogs[$key]['cardno']=$cardno;
            $consumeLogs[$key]['amount']=$val['amount'];
            $consumeLogs[$key]['datetime']=date('Y-m-d H:i:s');
            $cardbalance=$val['cardbalance'];
            $this->model->startTrans();
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
            $placeddate=date('Ymd',time());
            $placedtime=date('H:i:s',time());

            //比较应扣金额和卡账户金额
//            if($val['amount']!=$val['cardbalance']){
//                $consumeLogs[$key]['status']=0;
//                $consumeLogs[$key]['msg']='应扣资金和卡号账户资金不一致';
//                continue;
//            }

            if($waitAmount>=$val['cardbalance']){
                $amount=$val['cardbalance'];
            }else{
                $amount=$waitAmount;
            }

            //写入扣款记录
            $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag)";
            $tradeSql.="values('00000000','00000000','{$val['panterid']}','{$tradeid}','{$placeddate}',";
            $tradeSql.="'{$amount}','0','{$val['customid']}','{$cardno}','{$placedtime}','00','abcdefgh','0')";
            $tradeIf=$this->model->execute($tradeSql);
            if($tradeIf==false){
                $consumeLogs[$key]['status']=0;
                $consumeLogs[$key]['msg']='执行扣款记录失败';
                $this->model->rollback();
                continue;
            }
            $balanceData=array('amount'=>$cardbalance-$amount);
            $balanceMap=array('customid'=>$val['customid'],'type'=>'00');
            //扣除账户余额
            $balanceIf=$this->account->where($balanceMap)->save($balanceData);
            if($balanceIf==false){
                $consumeLogs[$key]['status']=0;
                $consumeLogs[$key]['msg']='执行扣款失败';
                $this->model->rollback();
                continue;
            }

            //更新订单--金额表，该卡已经扣款
            $fangCMap=array('tradeid'=>$val['tradeid'],'cardno'=>$cardno);
            if($amount==$val['cardbalance']){
                $sql="UPDATE FANGZG_C SET status=1,consumedamount=nvl(consumedamount,0)+".$amount;
                $sql.=" where tradeid='{$val['tradeid']}' and cardno='{$cardno}'";
            }else{
                $sql="UPDATE FANGZG_C SET status=2,consumedamount=nvl(consumedamount,0)+".$amount;
                $sql.=" where tradeid='{$val['tradeid']}' and cardno='{$cardno}'";
            }
            $fangCIf=$this->model->execute($sql);
            if($fangCIf==false){
                $consumeLogs[$key]['status']=0;
                $consumeLogs[$key]['msg']='更新订单--金额状态失败';
                $this->model->rollback();
                continue;
            }
            $statusIf=$this->checkStatus($val['tradeid'],$amount);
            if($statusIf==false){
                $consumeLogs[$key]['status']=0;
                $consumeLogs[$key]['msg']='更新入库订单状态失败';
                $this->model->rollback();
                continue;
            } else{
                $consumeLogs[$key]['status']=1;
                $consumeLogs[$key]['msg']='扣款成功';
                $this->model->commit();
                $c++;
                $c1+=$amount;
                $waitAmount-=$amount;
            }
        }
        if(!empty($consumeLogs)){
            $this->consumeLogs($consumeLogs);
        }
        if($c1==$consumeAmount){
            $this->success('扣款'.$c.'张卡,金额:'.$c1);
        }else{
            $this->error('扣款成功'.$c.'张卡,金额:'.$c1);
        }
    }

    public function checkStatus($tradeid,$amount){
        $where['tradeid']=$tradeid;
        $order=$this->fangzg->where($where)->find();
        $consumedmoney=$order['consumedmoney']+$amount;
        if($consumedmoney>=$order['amount']){
            $data=array('consumedmoney'=>$consumedmoney,'status'=>2);
        }else{
            $data=array('consumedmoney'=>$consumedmoney);
        }
        return $this->fangzg->where($where)->save($data);
    }

    //给已有卡号充值
    public function cardRecharge($cardArr,$customid,$amount,$tradeid){
        $userid =  $this->userid;
        $c=0;
        $string='';
        $string1='';
        $rechargedMoney=0;
        $rechargedArr=array();
        foreach($cardArr as $val){
            $this->model->startTrans();
            $cardno=$val;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            $currentDate=date('Ymd');
            $checkDate=date('Ymd');
            $where['cardno']=$cardno;
            $card=$this->cards->where($where)->field('customid,panterid')->find();
            if($c!=(count($cardArr)-1)){
                $rechargeMoney=5000;
            }else{
                $rechargeMoney=$amount-$c*5000;
            }
            $string.="订单：{$tradeid},卡号：{$cardno},充值金额：{$rechargeMoney}\n";
            //echo $card['customid'].'--'.$customid.'--'.$rechargeMoney.'--'.$amount.'<br/>';

            $customplSql="insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".$currentDate."','转账','";
            $customplSql.=$this->userid."','".$rechargeMoney."',NULL,'".$rechargeMoney."',0,'".$rechargeMoney."','".$rechargeMoney;
            $customplSql.="',1,'','房掌柜订单充值','1','".$card['panterid']."','".$userid."',NULL,'1',";
            $customplSql.="'0',NULL,'00000000','".date('H:i:s')."','".$checkDate."','".date('H:i:s',time()+300)."',NULL)";

            //写入审核单
            //$auditid=$this->getnextcode('audit_logs',16);
            $auditid=$this->getFieldNextNumber('auditid');
            $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过',";
            $auditlogsSql.="'房掌柜充值审核通过','".date('Ymd')."','".$userid ."','".date('H:i:s',time()+300)."')";

            $customplIf=$this->model->execute($customplSql);
            $auditlogsIf=$this->model->execute($auditlogsSql);

            $userip=$_SERVER['REMOTE_ADDR'];
            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            //写入充值单
            $cardplSql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
            $cardplSql.="'{$cardno}','{$rechargeMoney}','0','".$currentDate."','".date('H:i:s',time()+300)."','1','后台充值',";
            $cardplSql.="'{$userid}','{$card['panterid']}','{$userip}','00000000')";
            $cardplIf=$this->model->execute($cardplSql);

            //更新卡片账户
            $balanceSql="UPDATE account SET amount=nvl(amount,0)+".$rechargeMoney." where customid='".$card['customid']."' and type='00'";
            $balanceIf=$this->model->execute($balanceSql);

            $fangzgCSql="INSERT INTO FANGZG_C VALUES('{$tradeid}','{$cardno}','{$rechargeMoney}',0,'{$cardpurchaseid}',0)";
            $fangzgCIf=$this->model->execute($fangzgCSql);

            $fangzSql="UPDATE FANGZG SET rechargedamount=nvl(rechargedamount,0)+".$rechargeMoney." where tradeid='{$tradeid}'";
            $fangzgIf=$this->model->execute($fangzSql);

            //echo $cardno.'--'.$balanceSql.'<br/>';
            //echo $customplIf.'--'.$auditlogsIf.'--'.$cardplIf.'--'.$balanceIf.'<br/>';
            $string1.=$cardno.'执行sql：'."\r\n".$customplSql."\r\n".$auditlogsSql."\r\n".$cardplSql."\r\n";
            $string1.=$balanceSql."\r\n".$fangzgCSql."\r\n"."$fangzgIf"."\r\n\r\n";
            if($customplIf==true&&$auditlogsIf==true&&$cardplIf==true&&$balanceIf==true&&$fangzgCIf==true&&$fangzgIf==true){
                $string1.="充值成功卡号：{$cardno}\r\n";
                $rechargedMoney+=$rechargeMoney;
                $rechargedArr[]=$cardno;
                $c++;
                $this->model->commit();
            }else{
                $this->model->rollback();
            }
        }
        if(!empty($string)){
            $string.=$string1."\n";
            $this->fangzgLogs($string,$tradeid);
        }
        //echo $c;exit;
        if($c>0){
            $retArr=array('c'=>$c,'rechargedMoney'=>$rechargedMoney,'rechargedArr'=>$rechargedArr);
            return $retArr;
        }else{
            return false;
        }
    }

    //获取老会员已开的卡号
    public function getOwnCards($customid){
        $map['cc.customid']=$customid;
        $map['a.amount']=0;
        $map['a.type']='00';
        $map['c.cardfee']='0';
        $map['c.cardkind']='6889';
        $cards=$this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('account a on a.customid=c.customid')
            ->where($map)->field('c.cardno')->select();
        $cardsList=$this->serializeArr($cards,'cardno');
        return $cardsList;
    }

    //将二维数组转化成一维
    public function serializeArr($array,$key){
        $list=array();
        foreach($array as $k=>$v){
            $list[]=$v[$key];
        }
        return $list;
    }

    //获取下一个序号  tablename 表名 主键自增一
    public function getnextcode($tablename,$lennum=0,$parm=null){
        $code = D('code_generators');
        if($tablename!=''){
            $dmap['keyname']=$tablename;
            $datatable=$code->where($dmap)->getfield('current_seq');
            if($datatable==null){
                $mapp=array(
                    'keyname'=>$tablename,
                    'current_seq'=>1,
                );
                $strsql="INSERT INTO code_generators (keyname,current_seq) VALUES ('".$tablename."', 1)";
                $codeid=$code->execute($strsql);
                if ($codeid==false) { //判断是否成功
                    $this->error('失败！');
                    exit;
                }
            }
            $map['current_seq']=$datatable+1;
            if($parm!=null){
                $string="取值:".($datatable+1)."执行sql:";
                $this->fangzgLogs($string,'1');
                $this->fangzgLogs($string,$parm);
            }
            if($code->where($dmap)->save($map)){

                if($lennum!=0){
                    $datatable=$this->getnumstr($datatable,$lennum);
                }
                if($parm!=null){
                    $this->fangzgLogs($code->getLastSql()."\n",$parm);
                }
                return $datatable;
            }else{
                $this->error('表主键更新失败');
                exit;
            }
        }else{
            $this->error('表名不能为空');
            exit;
        }
    }

    //获得增加长度的字符 $numstr编号    $lennum字符长度
    public function getnumstr($numstr,$lennum){
        $snum=strlen($numstr);
        for($i=1;$i<=$lennum-$snum;$i++){
            $x.='0';
        }
        return $x.$numstr;
    }

    //写入录单日志
    function recordLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='订单：'.$val['tradeid']."  ";
            $msgString.='姓名：'.$val['name']."  ";
            $msgString.='充值金额：'.$val['amount']."  ";
            if($val['status']==0){
                $msgString.='状态：录单操作失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：录单操作成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\n\n";
        }
        $this->reserveLogs('batchRecharge',$msgString);
    }

    //写入录单日志
    function consumeLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='订单：'.$val['tradeid']."  ";
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='消费金额：'.$val['amount']."  ";
            if($val['status']==0){
                $msgString.='状态：扣款失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：扣款成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\n\n";
        }
        $this->reserveLogs('batchConsume',$msgString);
    }

    function  crul_post($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res=curl_exec ($ch);
        curl_close ($ch);
        return $res;
    }

    public function reserveLogs($type,$string){

        $logPath=PUBLIC_PATH.'logs/fangzg/'.$type.'/'.date('Ym',time()).'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$string,FILE_APPEND);
    }

    public function fangzgLogs($string,$indentifyName){

        $logPath=PUBLIC_PATH.'logs/fangzg/batchRecharge/'.date('Ym',time()).'/'.date('d',time()).'/';
        $filename=iconv("utf-8","gb2312",$indentifyName).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$string,FILE_APPEND);
    }

    public function getHours($defaultHour){
        $hour= array(
            '00','01','02','03','04','05','06','07','08','09',
            '10','11','12','13','14','15','16','17','18','19',
            '20','21','22','23'
        );
        $hourString='';
        foreach($hour as $val){
            if($val==$defaultHour){
                $selected=' selected="selected"';
            }else{
                $selected='';
            }
            $hourString.='<option name="'.$val.'" '.$selected.'>'.$val.'</option>';
        }
        return $hourString;
    }

    public function getMinites($defaultMinite){
        $minites=array(
            '00','01','02','03','04','05','06','07','08','09',
            '10','11','12','13','14','15','16','17','18','19',
            '20','21','22','23','24','25','26','27','28','29',
            '30','31','32','33','34','35','36','37','38','39',
            '40','41','42','43','44','45','46','47','48','49',
            '50','51','52','53','54','55','56','57','58','59'
        );
        $minitesString='';
        foreach($minites as $val){
            if($val==$defaultMinite){
                $selected=' selected="selected"';
            }else{
                $selected='';
            }
            $minitesString.='<option name="'.$val.'" '.$selected.' >'.$val.'</option>';
        }
        return $minitesString;
    }

    //清结算审核
    public function check(){
        $arrs=array(0=>"未审核",1=>"已审核");
        $checkstatus =trim(I('get.checkstatus',''));
        $tradeid=trim(I('get.tradeid',''));
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $namechinese=trim(I('get.namechinese',''));
        if($start!='' && $end==''){
            $where['f.ordertime']=array('egt',strtotime(date($start.' 00:00:00')));
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $where['f.ordertime'] = array('elt',strtotime(date($end.' 23:59:59')));
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate=strtotime(date($start.' 00:00:00'));
            $enddate=strtotime(date($end.' 23:59:59'));
            $where['f.ordertime']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=strtotime(date('Y-m-d 00:00:00', time()));
            $enddate=strtotime(date('Y-m-d 23:59:59', time()));
            $where['f.ordertime']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',time()));
            $this->assign('enddate',date('Y-m-d',time()));
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if(!empty($namechinese)){
            $map1['hysx']='房产/物业';
            $map1['namechinese'] = $namechinese;
            $fcList=M('panters')->where($map1)->field('PANTERID')->find();
            $panterid = $fcList['panterid'];
            $where['p.panterid']=$panterid;
            $map['panterid']=$panterid;
            $this->assign('namechinese',$namechinese);
        }
        if(empty($checkstatus)){
            $checkstatus=0;
        }
        if($checkstatus!='-1'){
            $where['f.checkstatus']=$checkstatus;
            $map['checkstatus']=$checkstatus;
        }
        if(!empty($tradeid)){
            $where['f.tradeid']=$tradeid;
            $map['tradeid']=$tradeid;
            $this->assign('tradeid',$tradeid);
        }
        $field='f.*,p.namechinese pname';
        $count=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->count();
        $p=new \Think\Page($count, 30);
        $list=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $amount_sum=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->sum('amount');
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('amount_sum',$amount_sum);
        // $map1['hysx']='房产/物业';
        // $fcList=M('panters')->where($map1)->field('panterid,namechinese')->select();
		//print_r($fcList);
        // $this->assign('fcList',$fcList);
        $this->assign("arrs",$arrs);
        $this->assign("sts",$checkstatus);
        $this->display();
    }

    public function checkDo(){
        $tradeidArr=$_POST['tradeid'];
        if(!empty($tradeidArr)){
            $where['tradeid']=array('in',$tradeidArr);
        }else{
            $this->error('请选择要充值的订单');
        }
        $data=array('checkstatus'=>1,'checkdate'=>time(),'checkid'=>$this->userid);
        $saveBool=$this->fangzg->where($where)->save($data);
        if($saveBool==true){
            $this->success('审核成功');
        }else{
            $this->error('审核失败');
        }
    }
    //财务审核
    public function accountCheck(){
        $arrs=array(0=>"未审核",1=>"已审核");
        $acccheckstatus =trim(I('get.acccheckstatus',''));
        $start = trim(I('get.startdate',''));
        $end = trim(I('get.enddate',''));
        $namechinese=trim(I('get.namechinese',''));
        if($start!='' && $end==''){
            $where['f.checkdate']=array('egt',strtotime(date($start.' 00:00:00')));
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $where['f.checkdate'] = array('elt',strtotime(date($end.' 23:59:59')));
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate=strtotime(date($start.' 00:00:00'));
            $enddate=strtotime(date($end.' 23:59:59'));
            $where['f.checkdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=strtotime(date('Y-m-d 00:00:00', time()));
            $enddate=strtotime(date('Y-m-d 23:59:59', time()));
            $where['f.checkdate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',time()));
            $this->assign('enddate',date('Y-m-d',time()));
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if(!empty($namechinese)){
            $map1['hysx']='房产/物业';
            $map1['namechinese'] = $namechinese;
            $fcList=M('panters')->where($map1)->field('PANTERID')->find();
            $panterid = $fcList['panterid'];
            $where['p.panterid']=$panterid;
            $map['panterid']=$panterid;
            $this->assign('namechinese',$namechinese);
        }
        if(empty($acccheckstatus)){
            $acccheckstatus=0;
        }
        if($acccheckstatus!='-1'){
            $where['f.acccheckstatus']=$acccheckstatus;
            $map['acccheckstatus']=$acccheckstatus;
        }
        $where['f.checkstatus']=1;
        $field='f.*,p.namechinese pname';
        $count=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->count();
        $p=new \Think\Page($count, 30);
        $list=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $amount_sum=$this->fangzg->alias('f')->join('left join panters p on f.panterid=p.panterid')
            ->where($where)->sum('amount');
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('amount_sum',$amount_sum);
        // $map['hysx']='房产/物业';
        // $fcList=M('panters')->where($map)->field('panterid,namechinese')->select();
        // $this->assign('fcList',$fcList);
        $this->assign("arrs",$arrs);
        $this->assign("sts",$acccheckstatus);
        $this->display();
    }

    public function accountCheckDo(){
        $tradeidArr=$_POST['tradeid'];
        if(!empty($tradeidArr)){
            $where['tradeid']=array('in',$tradeidArr);
        }else{
            $this->error('请选择要充值的订单');
        }
        $data=array('acccheckstatus'=>1,'acccheckdate'=>time(),'acccheckid'=>$this->userid);
        $saveBool=$this->fangzg->where($where)->save($data);
        if($saveBool==true){
            $this->success('审核成功');
        }else{
            $this->error('审核失败');
        }
    }

    public function markMistake(){
        $tradeid=trim($_REQUEST['tradeid']);
        if(empty($tradeid)){
            exit(json_encode(array('status'=>0,'msg'=>'订单不存在')));
        }
        $map=array('tradeid'=>$tradeid);
        $data=array('mark'=>1);
        if($this->fangzg->where($map)->save($data)){
            exit(json_encode(array('status'=>1,'msg'=>'标记成功')));
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'标记失败')));
        }
    }

    public function cancelMark(){
        $tradeid=trim($_REQUEST['tradeid']);
        if(empty($tradeid)){
            exit(json_encode(array('status'=>0,'msg'=>'订单不存在')));
        }
        $map=array('tradeid'=>$tradeid);
        $data=array('mark'=>0);
        if($this->fangzg->where($map)->save($data)){
            exit(json_encode(array('status'=>1,'msg'=>'取消标记成功')));
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'取消标记失败')));
        }
    }

    protected function recordRechargeError($data,$indentifyName){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $day=date('d');
        $filename=iconv("utf-8","gb2312",$indentifyName).'.log';
        $time=date('Y-m-d H:i:s');
        $string=$data;
        $path=PUBLIC_PATH.'logs/batchRecharge/'.$month.'/'.$day.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }

    //检测充值订单是否在执行充值4498300
    public function checkOrder($tradeid){
        $map=array('tradeid'=>$tradeid);
        $list=$this->fangzg->where($map)->field('lockstatus')->find();
        if($list['lockstatus']==1){
            return true;
        }else{
            return false;
        }
    }
    //对充值的订单锁定
    public function lockOrder($tradeid){
        if(empty($tradeid)) return false;
        $map=array('tradeid'=>$tradeid);
        $data=array('lockstatus'=>1);
        $this->fangzg->where($map)->save($data);
    }
    //对充值的订单接触锁定
    public function unlockOrder($tradeid){
        if(empty($tradeid)) return false;
        $map=array('tradeid'=>$tradeid);
        $data=array('lockstatus'=>0);
        $this->fangzg->where($map)->save($data);
    }

    public function catchImg($imgUrl){
        $sourcheUrl=C('fangzgIp').'/';
        $curl = curl_init($imgUrl); //初始化
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  //将结果输出到一个字符串中，而不是直接输出到浏览器
        curl_setopt($curl, CURLOPT_REFERER, $sourcheUrl); //最重要的一步，手动指定Referer
        $res = curl_exec($curl); //执行
        if (curl_errno($curl)) {
            curl_close($curl);
            return NULL;
        }
        curl_close($curl);
        return $res;
    }

    public function saveImg($file){
        $path=IMAGE_PATH.'photoes/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        $filename=$path.uniqid('id_').'.jpg';
        file_put_contents($filename,$file);
        return substr($filename,1);
    }

	public function examine($val)
	{
		$data = array(4=>'姓名空！',5=>'电话空！',8=>'退款金额空！',16=>'身份证空！',17=>'支付时间空！',18=>'项目ID空！');
		foreach($val as $k=>$v) {
			if($k==4 || $k==5 || $k==8 || $k==16 || $k==17 || $k==18) {
				if(empty(trim($val[$k]))){
					return $val[3].'-'.$data[$k];
				}
			}				
		}
	}
	public function upload()
    {
        //判断文件类型
		//print_r($_FILES);
        $file_type = substr(strrchr($_FILES['myfile']['name'], '.'), 1);
        //echo $file_type;
        if ($file_type != 'xlsx') {
            exit('文件后缀只能是xlsx！');
        }
        //var_dump(file_exists("./Public/uploadexcel/"));
        $res = move_uploaded_file($_FILES["myfile"]["tmp_name"], "./Public/uploadexcel/" . $_FILES["myfile"]["name"]);
        if ($res == true) {
            echo '文件上传成功！';
        } else {
            exit('文件上传失败！');
        }

        //读取数据
        $arr = $this->excelToArray($_FILES["myfile"]["name"]);
        $this->model->startTrans();
        $sql = 'insert into fangzg ';
		$i = 0;
        foreach ($arr as $key => $val) {
			$exclude = M('fangzg')->where(array('tradeid' => $val[3]))->field('tradeid')->find();
            if (!$exclude && !empty($val[3])) {
				$mes = $this->examine($val);
				if(!empty($mes)) {
					echo '<br>'.$mes;
					continue;//跳出当次循环
				}
				/*$shopname = $val[0];
				if(mb_strrchr($val[0],'(',true)){
					$shopname = trim(mb_strrchr($val[0],'(',true));
				}
				echo $shopname;
                $panter = M('panters')->where(array('namechinese' => $shopname ))->field('parent,panterid')->find();
				echo M()->getLastSql();
				print_r($panter);
				exit;
                $panterid = '';
                if ($panter != null) {
                    $parentid = $panter['parent'];
                }*/
				
				$pantersMap = array('panterid' => $val[18]);
				$panter = M('panters')->where($pantersMap)->field('parent')->find();
                if ($i != 0) $sql .= " UNION ALL ";
                $sql .= " SELECT '{$val[3]}','{$val[4]}','{$val[16]}','{$val[5]}'";
				$sql .= ",'{$val[8]}','{$panter['parent']}','" . time() . "',0,0,0,'{$val[18]}',0,0,'','','','',0";
				$sql .= ",'{$val[17]}','',0,0,'','' FROM DUAL";
                $trades[] = "'" . $val['3'] . "'";
				$data .= "'".$val[3].'\',<br>';
				$i++;
            } else if(!empty($val[3])){
                $excludedata .= "'".$val[3].'\',<br>';
            }
            // print_r($val[0]);
            /*$panter = M('panters')->where(array('namechinese' => $val[0]))->field('parent,panterid')->find();

            // var_dump($panter);
            $panterid = '';
            if ($panter != null) {
                $parentid = $panter['parent'];
            }
            //{$val[13]}
            if ($key != 0) $sql .= " UNION ALL ";
            $sql .= " SELECT '{$val[3]}','{$val[4]}','{$val[16]}','{$val[5]}'";
            $sql .= ",'{$val[8]}','{$parentid}','" . time() . "',0,0,0,'{$panter['panterid']}',0,0,'','','','',0";
            $sql .= ",'{$val[17]}','',0,0,'','' FROM DUAL";
            $trades[] = "'" . $val['3'] . "'";*/
        }
		echo '<br>';
		$excludedata = empty($excludedata) ? '无！' : $excludedata;
		echo '数据已存在未导入的单号：'.$excludedata;
        echo '<br>';
        echo $sql;
        echo '<br>';
        print_r($trades);
	    if($i!=0) {
			$tradeArr['tradeid'] = implode(',', $trades);
			if ($this->model->execute($sql)) {
				$url = C('fangzgIp') . '/posindex.php/Index/jycard2';
				$res = $this->crul_post($url, $tradeArr);
				$rs = $this->model->commit();
				if ($rs) {
					echo '<br>导入成功的数据：'.$data;
				} else {
					echo '导入失败！';
				}
				var_dump($rs);
				return true;
			} else {
				$rs = $this->model->rollback();
				if ($rs) {
					echo '回退成功！';
				}
				var_dump($rs);
				return false;
			}
		}

    }


    public function test1(){
        //echo '123';exit;
        $array=array(
            //array('cardno'=>'6889371800000210668','amount'=>5000,'panterid'=>'00000227','customid'=>'00237602','tradeid'=>'20160723103110_8661'),
        );
        foreach($array as $key=>$val){
            $this->model->startTrans();
            $getCards=array($val['cardno']);
            $customid=$val['customid'];
            $panterid=$val['panterid'];
            $amount=$val['amount'];
            $openCard=$this->openCards($getCards,$customid,$panterid,$val['amount'],$val['tradeid']);
            if($openCard==true){
                $this->model->commit();
            }else{
                $this->model->rollback();
            }
        }
        exit;
        $panterid='00000227';
        $arr=array();
        foreach($arr as $key=>$val) {
            $this->model->startTrans();
            $customid=$val['customid'];
            $amount=$val['amount'];
            $ownCards = $this->getOwnCards($customid);
            $needNum = $this->getCardNum($amount);
            $ownCardsNum = count($ownCards);
            if ($ownCardsNum >= $needNum) {
                for ($i = 0; $i < $needNum; $i++) {
                    $regCards[$i] = $ownCards[$i];
                }
                $regRes = $this->cardRecharge($regCards, $customid, $amount, $val['tradeid']);//充值
                if ($regRes == false) {
                    //写入充值失败日志
                    $recordLogs[$key]['msg'] = '老会员旧卡充值失败1';
                    $recordLogs[$key]['status'] = 0;
                    $this->model->rollback();
                    continue;
                }
            } else {
                $cardNum = $needNum - $ownCardsNum;
                $getCards = $this->getCards($cardNum, $panterid);
                if ($getCards == false) {
                    //写入卡池卡不足日志
                    $recordLogs[$key]['msg'] = '老会员开新卡，卡池卡数量不足';
                    $recordLogs[$key]['status'] = 0;
                    $this->model->rollback();
                    continue;
                }
                $openFee = $val['amount'] - $ownCardsNum * 5000;
                $openCard = $this->openCards($getCards, $customid, $panterid, $openFee, $val['tradeid']);//卡开并充值
                if ($openCard == false) {
                    //添加新卡开卡开失败日志
                    $recordLogs[$key]['msg'] = '老会员开新卡失败';
                    $recordLogs[$key]['status'] = 0;
                    $this->model->rollback();
                    continue;
                }
                if ($ownCardsNum > 0) {
                    $rechargeFee = $val['amount'] - $openFee;
                    $regRes = $this->cardRecharge($ownCards, $customid, $rechargeFee, $val['tradeid']);
                    if ($regRes == false) {
                        //给旧卡充值失败日志
                        $recordLogs[$key]['msg'] = '老会员旧卡充值失败2';
                        $recordLogs[$key]['status'] = 0;
                        $this->model->rollback();
                        continue;
                    }
                }
                $this->model->commit();
            }
        }
    }

    public function test(){
        $array=array();
        $trade_wastebooks=M('trade_wastebooks');
        $model=new model();
        $fangzgc=M('fangzg_c');
        $account=M('account');
        $fangzg=M('fangzg');
        foreach($array as $key=>$val){

            $model->startTrans();
            $tradeid=$val['tradeid'];
            $trade=$trade_wastebooks->where(array('tradeid'=>$tradeid))->find();
//            $accountInfo=$account->alias('a')->join('cards c on c.customid=a.customid')
//                ->where(array('a.type'=>'00','c.cardno'=>$trade['cardno']))->field('a.customid')->find();
            $fangzcInfo=$fangzgc->where(array('cardno'=>$trade['cardno']))->find();
            //$sql0="update trade_wastebooks set tradetype='04' where tradeid='{$val['tradeid']}'";
            //$sql1="update account set amount=amount+{$trade['tradeamount']} where customid='{$accountInfo['customid']}' and type='00'";
            //echo $sql0.'<br/>';
            //echo $sql1.'<br/>';
            //$model->execute($sql0);
            //$model->execute($sql1);
            //echo $fangzcInfo['consumedamount']-$trade['tradeamount'];exit;
//            if(($fangzcInfo['consumedamount']-$trade['tradeamount'])<=0){
//                $sql2="update fangzg_c set consumedamount=consumedamount-{$trade['tradeamount']},status=0 where cardno='{$trade['cardno']}' and tradeid='{$val['orderid']}'";
//            }else{
//                $sql2="update fangzg_c set consumedamount=consumedamount-{$trade['tradeamount']},status=2 where cardno='{$trade['cardno']}' and tradeid='{$val['orderid']}'";
//            }
            //echo $sql2.'<br/>';
//            $model->execute($sql2);
            $fangzgInfo=$fangzg->where(array('tradeid'=>$fangzcInfo['tradeid']))->find();
//            if($fangzgInfo['consumedamount']-$trade['tradeamount']<=0){
//                $sql3="update fangzg set consumedmoney=consumedmoney-{$trade['tradeamount']} where tradeid='{$fangzgInfo['tradeid']}'";
//            }else{
//                $sql3="update fangzg set consumedmoney=consumedmoney-{$trade['tradeamount']},status=0 where tradeid='{$fangzgInfo['tradeid']}'";
//            }
            $sql3="update fangzg set status=1 where tradeid='{$fangzgInfo['tradeid']}'";
            //echo $sql3;exit;
            $model->execute($sql3);
            //echo $sql3;exit;
            $model->commit();
        }
    }

}
