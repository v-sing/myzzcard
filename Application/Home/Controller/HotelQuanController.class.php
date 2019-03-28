<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;

class HotelQuanController extends Controller {

    public function _initialize(){
        $this->keycode="hotelquan01";
        $this->cateArr=array('1'=>'住宿','2'=>'餐饮','3'=>'饼屋','4'=>'烧烤','5'=>'电影','6'=>'活动','7'=>'其他');
        $this->hotel=array('q_am'=>'00000126','q_bem'=>'00000270','q_ylx'=>'00000125','q_nyjr'=>'00000118','q_fp'=>'00000127');
    }

    //全部劵查询接口
    public function getQuanAccount(){
        $data=file_get_contents('php://input');
        $this->recordData($data);
        //$data='{"datami":{"cardno":"6889396888800153272","key":"6baca384c7b941124c22e24d40a682d3"}}';
        $data = json_decode($data,1);
        $datami = $data['datami'];
        $cardno = trim($datami['cardno']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$cardno);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6889396888800153272';
        //$cardno = decode($cardno);
        $map = array('cardno'=>$cardno);
        $card = M('cards')->where($map)->find();
        if($card == false){
            returnMsg(array('status'=>'02','codemsg'=>'传入卡号为空'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'卡已锁定'));
        }
        if($card['exdate']<date('Ymd')){
            returnMsg(array('status'=>'04','codemsg'=>'卡已过期'));
        }
        $account=M('account');
        $panters=M('panters');
        $qaccount=M('quan_account');
        $where1['c.cardno']=$cardno;
        $where1['a.type']='02';
        $where1['q.atype']=1;
        $where1['a.amount']=array('gt',0);
        $where1['_string']=" p.panterid in ('00000118','00000270','00000127','00000126','00000125','00000013')";
        $list1=$account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join('panters p on p.panterid=q.panterid')
            ->field('q.quanname,a.amount,q.quanid,q.enddate,q.cate,q.utype,q.amount price,p.namechinese pname,p.panterid pid')
            ->where($where1)->select();

        $where2=array('c.cardno'=>$cardno,'q.atype'=>2,'qa.amount'=>array('gt',0));
        $where2['_string']=" p.panterid in ('00000118','00000270','00000127','00000126','00000125','00000013')";
        $list2=$qaccount->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->join('panters p on p.panterid=q.panterid')
            ->field('q.quanname,qa.amount,qa.accountid,q.quanid,qa.enddate,q.cate,q.utype,q.amount price,p.namechinese pname,p.panterid pid')
            ->where($where2)->select();

        $list=array_merge($list1,$list2);
        if(empty($list)){
            returnMsg(array('status'=>'05','codemsg'=>'该卡号无优惠劵信息'));
        }
        $quanList=array();
        foreach($list as $key=>$val){
            $quaninfo=array(
                'quanname'=>$val['quanname'],
                'quanid'=>$val['quanid'],
                'enddate'=>$val['enddate'],
                'pname'=>$val['pname'],
                'amount'=>$val['amount'],
                'pid'=>$val['pid'],
                'price'=>$val['price'],
                'cate'=>$this->cateArr[$val['cate']],
                'accountid'=>$val['accountid']
            );
            $quaninfo['accountid']=!empty($val['accountid'])?encode($val['accountid']):'';
            if($val['pid']=='00000013'){
                $quaninfo['pid']='';
                $quaninfo['pname']='';
            }else{
                $quaninfo['pid']=$val['pid'];
                $quaninfo['pname']=$val['pname'];
            }
            if($val['enddate']<date('Ymd')){
                $quaninfo['overdue']=1;
            }else{
                $quaninfo['overdue']=0;
            }
            if($val['utype']==1){
                $quaninfo['utype']='通用劵';
            }else{
                $quaninfo['utype']='专用劵';
            }
            $quanList[]=$quaninfo;
        }
        returnMsg(array('status'=>'1','quanlist'=>$quanList));
    }

    //不同消费场景下有效劵查询接口
    public function getUsableAccount(){
        $data=file_get_contents('php://input');
        $this->recordData($data);
        $data = json_decode($data,1);
        $datami = $data['datami'];
        $cardno = trim($datami['cardno']);
        $hotelcode=trim($datami['hotelcode']);
        $cate=trim($datami['cate']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$cardno.$cate.$hotelcode);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
//        $cardno='6889396888800153272';
//        $hotelcode='q_am';
//        $cate='1';
        //$cardno = decode($cardno);
        $map = array('cardno'=>$cardno);
        $card = M('cards')->where($map)->find();
        if($card == false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'卡已锁定'));
        }
        if($card['exdate']<date('Ymd')){
            returnMsg(array('status'=>'04','codemsg'=>'卡已过期'));
        }
        $panterid=$this->hotel[$hotelcode];
        $account=M('account');
        $qaccount=M('quan_account');
        $panters=M('panters');
        $where1=array(
            'c.cardno'=>$cardno,
            'a.type'=>'02',
            'q.atype'=>1,
            'a.amount'=>array('gt',0),
            'q.enddate'=>array('egt',date('Ymd'))
        );
        $where2=array(
            'c.cardno'=>$cardno,
            'q.atype'=>2,
            'qa.amount'=>array('gt',0),
            'qa.enddate'=>array('egt',date('Ymd'))
        );
        if(!empty($cate)){
            $where1['q.cate']=$where2['q.cate']=$cate;
        }
        if(!empty($hotelcode)){
            $panter=$panters->where(array('panterid'=>$panterid))->find();
            $where1['_string']=$where2['_string']=" (q.panterid='{$panterid}' and q.utype=2) or (q.utype=1 and q.panterid='{$panter['parent']}')";
        }
        $list1=$account->alias('a')->join('quankind q on q.quanid=a.quanid')
            ->join('cards c on c.customid=a.customid')
            ->join(' left join panters p on p.panterid=q.panterid')
            ->field('q.quanname,a.amount,q.quanid,q.enddate,q.cate,q.utype,q.amount price,p.namechinese pname,p.panterid pid')
            ->where($where1)->select();

        $list2=$qaccount->alias('qa')->join('quankind q on q.quanid=qa.quanid')
            ->join('cards c on c.customid=qa.customid')
            ->join(' left join panters p on p.panterid=q.panterid')
            ->field('q.quanname,qa.amount,q.quanid,qa.accountid,qa.enddate,q.cate,q.utype,q.amount price,p.namechinese pname,p.panterid pid')
            ->where($where2)->select();
        //echo $qaccount->getLastSql();exit;
        $list=array_merge($list1,$list2);
        if(empty($list)){
            returnMsg(array('status'=>'05','codemsg'=>'该卡号无优惠劵信息'));
        }
        $quanList=array();
        foreach($list as $key=>$val){
            $quaninfo=array(
                'quanname'=>$val['quanname'],
                'quanid'=>$val['quanid'],
                'enddate'=>$val['enddate'],
                'pname'=>$val['pname'],
                'pid'=>$val['pid'],
                'price'=>$val['price'],
                'amount'=>$val['amount'],
                'cate'=>$this->cateArr[$val['cate']]
            );
            $quaninfo['accountid']=!empty($val['accountid'])?encode($val['accountid']):'';
            if($val['pid']=='00000013'){
                $quaninfo['pid']='';
                $quaninfo['pname']='';
            }else{
                $quaninfo['pid']=$val['pid'];
                $quaninfo['pname']=$val['pname'];
            }
            if($val['utype']==1){
                $quaninfo['utype']='通用劵';
            }else{
                $quaninfo['utype']='专用劵';
            }
            $quanList[]=$quaninfo;
        }
        returnMsg(array('status'=>'1','quanlist'=>$quanList));
    }

    //劵消费接口
    public function consume(){
        $data=file_get_contents('php://input');
        $this->recordData($data);
        //$data='{"datami":{"cardno":"6889396888800153272","accountid":"","quanid":"00000014","hotelcode":"q_am","amount":1,"pwd":"21218cca77804d2ba1922c33e0151105","key":"8ae4e7944bfacbbd5692000cd98eccf3"}}';
        $data = json_decode($data,1);
        $datami = $data['datami'];
        $cardno = trim($datami['cardno']);
        $hotelcode=trim($datami['hotelcode']);
        $quanid=trim($datami['quanid']);
        $amount=trim($datami['amount']);
        $pwd=trim($datami['pwd']);
        $accountid=trim($datami['accountid']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$cardno.$hotelcode.$quanid.$amount.$pwd.$accountid);
        //echo $this->keycode.$cardno.$hotelcode.$quanid.$amount.$pwd.$accountid.'<br/>';
        //echo $checkKey.'--'.$key;exit;
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $map = array('cardno'=>$cardno);
        $card = M('cards')->where($map)->find();
        if($card == false){
            returnMsg(array('status'=>'02','codemsg'=>'非法卡号'));
        }
        if($card['status']!='Y'){
            returnMsg(array('status'=>'03','codemsg'=>'卡已锁定'));
        }
        if($card['exdate']<date('Ymd')){
            returnMsg(array('status'=>'04','codemsg'=>'卡已过期'));
        }
        if(!preg_match('/\d+/',$amount)){
            returnMsg(array('status'=>'05','codemsg'=>'消费金额格式有误'));
        }

        $des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
        $card['cardpassword']=$des->doDecrypt($card['cardpassword']);
        if($pwd!=md5(substr($card['cardpassword'],0,6))){
            returnMsg(array('status'=>'010','codemsg'=>'密码错误'));
        }
        $model=new model();
        $quaninfo=$model->table('quankind')->where(array('quanid'=>$quanid))->find();
        if(empty($quaninfo)){
            returnMsg(array('status'=>'06','codemsg'=>'查无此劵信息'));
        }
        $panterid=$this->hotel[$hotelcode];
        $panter=$model->table('panters')->where(array('panterid'=>$panterid))->find();
        if($panter['panterid']!=$quaninfo['panterid']&&$panter['parent']!=$quaninfo['panterid']){
            returnMsg(array('status'=>'07','codemsg'=>'此优惠券不能再该商户下消费'));
        }
        if($quaninfo['atype']==1){
            $where=array('c.cardno'=>$cardno,
                'a.type'=>'02',
                'q.quanid'=>$quanid,
                'q.enddate'=>array('egt',date('Ymd'))
            );
            $field='q.quanname,a.amount,q.quanid,q.enddate,q.panterid,q.utype,c.customid';
            $list=$model->table('account')->alias('a')->join('cards c on a.customid=c.customid')
                ->join('quankind q on q.quanid=a.quanid')
                ->where($where)->field($field)->find();
            if($list['amount']<$amount){
                returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
            }
            $model->startTrans();
            $sql="update account set amount=amount-{$amount} where customid='{$list['customid']}' and type='02'";
            $consumeInfo=array('quanid'=>$quanid,
                'cardno'=>$cardno,'amount'=>$amount,
                'customid'=>$list['customid'],
                'panterid'=>$panterid
            );
            $tradeIf=$this->recodeQuanConsume($consumeInfo);
            if($model->execute($sql)&&$tradeIf==true){
                $model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'劵消费成功'));
            }else{
                $model->rollback();
                returnMsg(array('status'=>'09','codemsg'=>'劵消费失败'));
            }
        }elseif($quaninfo['atype']==2){
            if(empty($accountid)){
                returnMsg(array('status'=>'010','codemsg'=>'劵账户缺失'));
            }
            $accountid=decode($accountid);
            $account=M('quan_account');
            $where=array('c.cardno'=>$cardno,
                'q.quanid'=>$quanid,
                'qa.enddate'=>array('egt',date('Ymd')),
                'qa.accountid'=>$accountid
            );
            //$where['_string']=" (q.panterid='{$panterid}' and q.utype=2) or  (q.utype=1 and q.panterid='{$panter['parent']}')";
            $field='q.quanname,qa.amount,q.quanid,qa.enddate,q.utype,qa.accountid,c.customid';
            $c=$model->table('quan_account')->alias('qa')->join('cards c on qa.customid=c.customid')
                ->join('quankind q on q.quanid=qa.quanid')
                ->where($where)->field($field)->sum('qa.amount');
            $list=$model->table('quan_account')->alias('qa')->join('cards c on qa.customid=c.customid')
                ->join('quankind q on q.quanid=qa.quanid')
                ->where($where)->field($field)->find();
            if($c<$amount){
                returnMsg(array('status'=>'08','codemsg'=>'营销劵余额不足'));
            }
            $model->startTrans();
            $sql="update quan_account set amount=amount-{$amount} where customid='{$list['customid']}' and accountid='{$list['accountid']}'";
            $consumeInfo=array(
                'quanid'=>$quanid,
                'cardno'=>$cardno,
                'amount'=>$amount,
                'customid'=>$list['customid'],
                'quanaccountid'=>$list['accountid'],
                'panterid'=>$panterid
            );
            $tradeIf=$this->recodeQuanConsume($consumeInfo);
            /*$consumedAmount=0;
            foreach($list as $key=>$val){
                $waitAmount=$amount-$consumedAmount;
                if($waitAmount<=0) break;
                if($waitAmount>$val['amount']){
                    $consumeAmount=$val['amount'];
                }else{
                    $consumeAmount=$waitAmount;
                }
                $sql="update quan_account set amount=amount-{$consumeAmount} where customid='{$val['customid']}' and accountid='{$val['accountid']}'";
                $consumeInfo=array(
                    'quanid'=>$quanid,
                    'cardno'=>$cardno,
                    'amount'=>$amount,
                    'customid'=>$list['customid'],
                    'quanaccountid'=>$val['accountid'],
                    'panterid'=>$panterid
                );
                $tradeIf=$this->recodeQuanConsume($consumeInfo);
                //echo $sql;exit;
                if($model->execute($sql)&&$tradeIf==true){
                    $consumedAmount+=$consumeAmount;
                }
            }*/
            if($model->execute($sql)&&$tradeIf==true){
                $model->commit();
                returnMsg(array('status'=>'1','codemsg'=>'劵消费成功'));
            }else{
                $model->rollback();
                returnMsg(array('status'=>'09','codemsg'=>'劵消费失败'));
            }
        }
    }

    public function recodeQuanConsume($consumeInfo){
        $quanid=$consumeInfo['quanid'];
        $amount=$consumeInfo['amount'];
        $customid=$consumeInfo['customid'];
        $cardno=$consumeInfo['cardno'];
        $panterid=$consumeInfo['panterid'];
        $quanaccountid=empty($consumeInfo['quanaccountid'])?'':'营销劵账户:'.$consumeInfo['quanaccountid'];
        $termno='0000000';


        $placeddate=date('Ymd',time());
        $placedtime=date('H:i:s',time());
        $tradeid=substr($cardno,15,4).date('YmdHis',time());
        $trade=M('trade_wastebooks');
        $map=array('tradeid'=>$tradeid);
        $c=$trade->where($map)->count();
        if($c>0){
            sleep(1);
            $tradeid=substr($cardno,15,4).date('YmdHis',time());
        }

        $tradeSql="insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
        $tradeSql.="tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
        $tradeSql.="values('{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}',";
        $tradeSql.="'{$amount}','0','{$customid}','{$cardno}','{$placedtime}','02','abcdefgh','0','{$quanid}','{$quanaccountid}')";
        $tradeIf=$trade->execute($tradeSql);

        //echo $tradeSql;exit;

        return $tradeIf;
    }

    protected function recordData($data){
        $a=$_SERVER['REMOTE_ADDR'];
        $month=date('Ym',time());
        $filename=date('Ymd',time()).'.log';
        $time=date('Y-m-d H:i:s');
        $string='ip:'.$a."\r\n时间：".$time."\r\n数据：".$data."\r\n\r\n";
        $path=PUBLIC_PATH.'logs/interface/newQuan/'.$month.'/';
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        file_put_contents($path.$filename,$string,FILE_APPEND);
    }

    public function consumeList(){
        $data=file_get_contents('php://input');
        $this->recordData($data);
        $data = json_decode($data,1);
        $datami = $data['datami'];
        $cardno = trim($datami['cardno']);
        $date=trim($datami['date']);
        $key = trim($datami['key']);
        $checkKey = md5($this->keycode.$cardno.$date);
        if($checkKey != $key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        //$cardno='6889396888800153272';
        //$cardno = decode($cardno);
        $map = array('cardno'=>$cardno);
        $card = M('cards')->where($map)->find();
        if($card == false){
            returnMsg(array('status'=>'02','codemsg'=>'传入卡号为空'));
        }
        if(!empty($date)){
            $where['tw.placeddate']=array('egt',$date);
        }
        $trade=M('trade_wastebooks');
        $field="tw.quanid,q.quanname,tw.tradeamount  amount,p.panterid pid,p.namechinese pname,q.cate,q.utype,tw.placeddate,tw.placedtime";
        $where=array('tw.tradetype'=>'02','tw.flag'=>0,'tw.cardno'=>$cardno);
        $list = $trade->alias('tw')->join('quankind q on tw.quanid=q.quanid')
            ->join('panters p on p.panterid=tw.panterid')
            ->field($field)->where($where)
            ->order('tw.placeddate desc,tw.placedtime desc')
            ->select();

        if(empty($list)){
            returnMsg(array('status'=>'03','codemsg'=>'无消费信息'));
        }
        $consumeList=array();
        foreach($list as $key=>$val){
            $quaninfo=array(
                'quanname'=>$val['quanname'],
                'quanid'=>$val['quanid'],
                'placeddate'=>$val['placeddate'],
                'placedtime'=>$val['placedtime'],
                'pname'=>$val['pname'],
                'amount'=>$val['amount'],
                'pid'=>$val['pid'],
                'cate'=>$this->cateArr[$val['cate']]
            );
            if($val['utype']==1){
                $quaninfo['utype']='通用劵';
            }else{
                $quaninfo['utype']='专用劵';
            }
            $consumeList[]=$quaninfo;
        }
        returnMsg(array('status'=>'1','list'=>$consumeList));
    }

    public function getConsumeRecord(){
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami,1);
        $key = trim($datami['key']);
        //echo $panterid.'---'.$termposno;exit;
        $jytype=array('00'=>'至尊卡消费','02'=>'劵消费','13'=>'现金消费','17'=>'预授权','21'=>'预授权完成');
        $map['tw.tradetype']=array('in','00,01,02,17,21');
        $map['tw.flag']=0;
        $map['tw.tradeamount']=array('gt',0);
        $tradeWasteBooks=M('trade_wastebooks');
        $field='tw.tradeid,tw.placeddate,tw.placedtime,tw.tradeamount,tw.tradepoint,tw.cardno,tw.tradetype,tw.quanid,q.quanname,tw.eorderid';
        $tradeList=$tradeWasteBooks->alias('tw')->join('left join quankind q on tw.quanid=q.quanid')
            ->where($map)->field($field)->order('placeddate desc,placedtime desc')->select();
        //echo M('trade_wastebooks')->getLastSql();exit;
        if($tradeList==false){
            returnMSg(array('status'=>'03','codemsg'=>'查无消费记录！'));
        }else{
            $list=array();
            foreach($tradeList as $key=>$val){
                $array=array();
                $array['tradeid']=trim($val['tradeid']);
                $array['cardno']=$val['cardno'];
                $array['date']=date('Y-m-d',strtotime($val['placeddate'])).' '.$val['placedtime'];
                $array['tradetype']=$jytype[$val['tradetype']];
                $array['amount']=floatval($val['tradeamount']);
                if($val['tradetype']=='02'){
                    $array['quanid']=$val['quanid'];
                    $array['quanname']=$val['quanname'];
                }
            }
                $list[]=$array;
        }
        returnMSg(array('status'=>'1','list'=>$list));
    }
}
