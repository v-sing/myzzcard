<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;
class CardController extends CommonController {
    public function _initialize(){
        parent::_initialize();
        $this->model=new model();
        $this->des=new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    }
    //售卡登记
    function index(){
        $custompl=M('custom_purchase_logs');
        $customs=M('customs');
        $safety = M('safety_monitoring');
        $gctype=array('0'=>'购卡单','1'=>'充值单');
        $paytype=array('00'=>'现金','01'=>'银行卡','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他','09'=>'批量充值');
        $this->assign('gctype',$gctype);
        $this->assign('paytype',$paytype);
        if(IS_POST){
            $date=date('Ymd',time());
            $customid=trim(I('post.customid',''));
            $cnames=trim(I('post.cnames',''));
            $tradeflag=trim(I('post.tradeflag',''));
            $customlevel=trim(I('post.customlevel',''));
            $paymenttype=trim(I('post.paymenttype',''));
            $totalmoney=trim(I('post.totalmoney',''));
            $realamount=trim(I('post.realamount',''));
            $description=trim(I('post.description',''));
            $checkno    =trim(I('post.checkno',0));
            $hysx=$this->getHysx();
            $ptime=date('H:i:s',time());
            $panterid=$this->panterid;
            $where['customid']=$customid;
            $customif=$customs->where($where)->find();
            if($customif==false){
                $customs->execute("insert into customs(customid,customlevel,panterid) values('".$customid."','一般客户','".$panterid."')");
            }else{
                if($hysx!='酒店'){
                    if(empty($customif['namechinese'])||empty($customif['personid'])){
                        if($totalmoney>1000||$realamount>1000){
                            $this->think_send_mail('349409263@qq.com',$date,$customid,'会员编号'.$customid
                                .'充值金额：'.$totalmoney.'非实名卡充值不能超过1000','');
                            $safety->execute("insert into safety_monitoring values('{$customid}','{$date}','{$ptime}','售卡登记','充值金额：".$totalmoney."非实名卡充值不能超过1000','01')");
                            $this->error('非实名卡充值不能超过1000');
                        }
                    }
                    if($totalmoney>5000||$realamount>5000){
                        $this->think_send_mail('349409263@qq.com',$date,$customid,'会员编号'.$customid
                            .'充值金额：'.$totalmoney.'充值金额不能超过5000','');
                        $safety->execute("insert into safety_monitoring values('{$customid}','{$date}','{$ptime}','售卡登记','充值金额：".$totalmoney."非实名卡充值不能超过5000','01')");
                        $this->error('充值金额不能超过5000');
                    }
               }

            }
            $userid =  $this->userid;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber("purchaseid");
            $purchaseid=$userstr.$purchaseid;
            if($customlevel=='一般顾客'){
                $customflag=0;
            }else{
                $customflag=1;
            }
            $custompl->execute("insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".date('Ymd')."','".$paymenttype."','".$userid."','".$totalmoney."',NULL,'".$totalmoney."',0,'".$realamount."',0,0,'".$checkno."','".$description."','0','".$panterid."',NULL,NULL,'".$tradeflag."','".$customflag."',NULL,'00000000','".date('H:i:s')."',NULL,NULL,NULL)");
            $this->success('生成单据成功!',U('Card/index'),5);
            /*$sostr['a.customid']=$customid;
            $sostr['a.tradeflag']=0;
            $customone=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                        ->join('left join panters c on c.panterid=a.panterid')
                        ->field('a.purchaseid,a.customid,b.namechinese,a.tradeflag,a.totalmoney,a.realamount')->where($sostr)->select();
            $this->assign('list',$customone);*/
        }else{
            $start = trim(I('get.startdate',''));
            $end = trim(I('get.enddate',''));
            $customsid = trim(I('get.customsid',''));
            $cuname  = trim(I('get.cuname',''));
            if($start!='' && $end==''){
                $startdate = str_replace('-','',$start);
                $where['a.placeddate']=array('egt',$startdate);
                $this->assign('startdate',$start);
                $map['startdate']=$start;
            }
            if($start=='' && $end!=''){
                $enddate = str_replace('-','',$end);
                $where['a.placeddate'] = array('elt',$enddate);
                $this->assign('enddate',$end);
                $map['enddate']=$end;
            }
            if($start!='' && $end!=''){
                $startdate = str_replace('-','',$start);
                $enddate = str_replace('-','',$end);
                $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
                $this->assign('startdate',$start);
                $this->assign('enddate',$end);
                $map['startdate']=$start;
                $map['enddate']=$end;
            }
            if($customsid!=''){
                $where['a.customid'] = $customsid;
                $this->assign('customsid',$customsid);
                $map['customsid']=$customsid;
            }
            if($cuname!=''){
                $where['b.namechinese']=array('like','%'.$cuname.'%');
                $this->assign('cuname',$cuname);
                $map['cuname']=$cuname;
            }
            if($this->panterid!='FFFFFFFF'){
                $where['_string']="c.panterid='".$this->panterid."' OR c.parent='".$this->panterid."'";
            }else{
                $where1['_string']="c.hysx <> '酒店' OR c.hysx IS NULL";
                $where['_complex']=$where1;
            }
            $field='a.purchaseid,a.customid,b.namechinese,a.tradeflag,a.totalmoney,a.realamount,a.placeddate,a.placedtime';
            $where['a.tradeflag']='0';
            $count=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                ->join('left join panters c on c.panterid=a.panterid')
                ->field($field)->where($where)->count();
            $p=new \Think\Page($count, 10);
            $custompl_list=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                            ->join('left join panters c on c.panterid=a.panterid')
                            ->field($field)->where($where)->limit($p->firstRow.','.$p->listRows)
                            ->order('a.placeddate desc,a.placedtime desc')->select();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $this->assign('panterid',$this->panterid);
            $hysx=$this->getHysx();
            if($hysx!='酒店'){
                $this->assign('permit',0);
            }else{
                $this->assign('permit',1);
            }
            $page = $p->show();
            $this->assign('page',$page);
            $this->assign('list',$custompl_list);
            $firstDate=date('Ymd',time());
            $secondDate=date('Ymd',(time()-7*24*3600));
            $map1['c.placeddate']=array('between',array($secondDate,$firstDate));
            if($this->panterid!='FFFFFFFF'){
                $map1['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
            }
            $recentCustoms=M('customs')->alias('c')
                ->join('panters p on c.panterid=p.panterid')
                ->field('c.customid,c.namechinese,c.customlevel,c.linktel')
                ->where($map1)->select();
            $this->assign('recentCustoms',$recentCustoms);
            $this->display();
        }
    }
    //获得新增会员编号
    function getcustomid(){
        //$customid=$this->getnextcode('customs',8);
        $customid=$this->getFieldNextNumber('customid');
        $panterid=$this->panterid;
        $sql="insert into customs(customid,panterid) values('{$customid}','{$panterid}')";
        $model=new model();
        if($model->execute($sql)){
            $res['customid']=$customid;
            $res['customlevel']='一般顾客';
            $res['success']=1;
            echo json_encode($res);
        }else{
            $res['msg']='网络故障，添加失败！';
        }
    }
    //获得卡片信息
    function getcards(){
        $cards = M('cards');
        $sex    = trim(I('post.sex',''));
        $cardno  = trim(I('post.cardno',''));
        $cname  = trim(I('post.cname',''));
        $customsid = trim(I('post.customsid',''));
        if($sex!=''){
            $where['b.sex']=$sex;
        }
        if($cardno!=''){
            $where['a.cardno']=$cardno;
        }
        if($cname!=''){
            $where['b.namechinese']=array('like','%'.$cname.'%');
        }
        if($customsid!=''){
            $where['a.customid']=$customsid;
        }
        $where['d.type']='01';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $card_list=$cards->alias('a')
                ->join('left join customs_c c on a.customid=c.cid')
                ->join('left join customs b on c.customid=b.customid')
                ->join('left join account d on a.customid=d.customid')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field('a.cardno,a.status,a.customid as customid1,b.customid,b.namechinese,b.customlevel,b.linktel,d.amount,a.exdate')
                ->order('a.cardno desc')->select();
        if($card_list){
            $res['data']=$card_list;
            $res['success']=1;
        }else{
            $res['msg']='查无记录';
        }
        echo json_encode($res);
    }
    //查询返回会员信息ajax
    function getcustoms(){
        $customs=M('customs');
        $customid=trim(I('post.customsid',''));
        $linktel =trim(I('post.linktel',''));
        $cname   =trim(I('post.cname',''));
        if($customid!=''){
            $where['c.customid']=$customid;
        }
        if($linktel!=''){
            $where['c.linktel']=$linktel;
        }
        if($cname!=''){
            $where['c.namechinese']=array('like','%'.$cname.'%');
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $field='c.customid,c.namechinese,c.customlevel,c.linktel';
        $custom_list=$customs->alias('c')->join('left join __PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->order('c.customid asc')->select();
        // echo $customs->getLastSql();exit;
        if($custom_list){
            $res['data'] =$custom_list;
            $res['success'] = 1;
        }else{
            $res['msg']='查无会员记录!';
        }
        echo json_encode($res);
    }
    //查询返回售卡充值单记录
    function getcustompl(){
        $custompl= M('custom_purchase_logs');
        $start = trim(I('post.startdate',''));
        $end = trim(I('post.enddate',''));
        $customsid = trim(I('post.customsid',''));
        $cname  = trim(I('post.cname',''));
        $purchaseid = trim(I('post.purchaseid',''));
        $tradeflag = trim(I('post.tradeflag',''));
        $flag   = trim(I('post.flag',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['a.placeddate']=array('egt',$startdate);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['a.placeddate'] = array('elt',$enddate);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($customsid!=''){
            $where['a.customid'] = $customsid;
        }
        if($purchaseid!=''){
            $where['a.purchaseid'] = $purchaseid;
        }
        if($cname!=''){
            $where['b.namechinese']=array('like','%'.$cname.'%');
        }
        if($tradeflag!=''){
            $where['a.tradeflag']=$tradeflag;
        }
        if($flag!=''){
            $where['a.flag']=$flag;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        if($tradeflag==0){
            $where['a.writenumber']=0;
            $where['_string'].=" a.totalmoney-a.WriteAmount>=0";
        }else{
            $where['_string'].="a.totalmoney-a.WriteAmount>0";
        }
        $field='a.purchaseid,a.customid,a.writenumber,a.writeamount,b.namechinese,';
        $field.='a.tradeflag,a.totalmoney,a.realamount,a.placeddate,a.placedtime';
        $custompl_list=$custompl->alias('a')
            ->join('left join customs b on b.customid=a.customid')
            ->join('join __PANTERS__ p on p.panterid=a.panterid')
            ->field($field)->where($where)->order('a.placeddate desc,a.placedtime desc')->select();
        //echo $custompl->getLastSql();exit;
        if($custompl_list){
            $res['data'] =$custompl_list;
            $res['success'] = 1;
        }else{
            $res['msg']='没有查询到信息!';
        }
        echo json_encode($res);
    }
    //查询返回充值卡号信息
    function getcardpay(){
        $statype=array('A'=>'待激活','D'=>'销卡','R'=>'退卡','S'=>'过期','N'=>'新卡','L'=>'锁定','Y'=>'正常卡','W'=>'无卡','C'=>'已出库');//卡状态
        $cardno = trim(I('post.cardno',''));
        $customid = trim(I('post.customid',''));
        $status = trim(I('post.status'),'');
        $type = trim(I('post.type'),'');
        $panterid=$this->panterid;
        $cards = M('cards');
        $safety = M('safety_monitoring');
        $where['a.cardno']=$cardno;
        $where['a.status']=$status;
        $where['c.type']=$type;
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        $cardif=$cards->alias('a')->join('left join customs_c d on d.cid=a.customid')
                ->join('left join customs b on b.customid=d.customid')
                ->join('left join account c on a.customid=c.customid')
                ->field('a.cardno,a.cardbalance,b.customid,a.panterid,a.status,b.namechinese,a.cardinitamount,c.amount')->where($where)->find();
        if($cardif){
            $cardif['status']=$statype[$cardif['status']];
            if($cardif['customid']!=$customid){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员编号'.$customid.'此卡片卡片所属会员与充值单会员不符','');
                $safety->execute("insert into safety_monitoring values('{$customid}','{$date}','{$ptime}','查询卡号信息','卡号:".$cardno."此卡片卡片所属会员与充值单会员不符','01')");
                $res['msg']='此卡片卡片所属会员与充值单会员不符';
            }else if($panterid!='FFFFFFFF'){
                if($cardif['panterid']!=$panterid){
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'商员编号'.$panterid.'此卡非本部门卡，无法充值!','');
                    $safety->execute("insert into safety_monitoring values('{$panterid}','{$date}','{$ptime}','查询卡号信息','卡号:".$cardno."此卡非本部门卡，无法充值!','02')");
                    $res['msg']='此卡非本部门卡，无法充值!';
                }else{
                    $res['data']=$cardif;
                    $res['success']=1;
                }
            }else{
               $res['data']=$cardif;
                $res['success']=1;
            }
        }else{
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'卡状态'.$status.'此卡不是正常卡，或者为非法卡，不能充值！','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','查询卡号信息','卡状态:".$status."此卡不是正常卡，或者为非法卡，不能充值！','04')");
            $res['msg']='此卡不是正常卡，或者为非法卡，不能充值！';
        }
        echo json_encode($res);
    }
    //查询返回购卡执行卡号信息
    function getsellcard(){
        $statype=array('A'=>'待激活','D'=>'销卡','R'=>'退卡','S'=>'过期','N'=>'新卡','L'=>'锁定','Y'=>'正常卡','W'=>'无卡','C'=>'已出库');//卡状态
        $cardno = trim(I('post.cardno',''));
        $customid = trim(I('post.customid',''));
        $status = trim(I('post.status'),'');
        $panterid=$this->panterid;
        $date=date('Ymd',time());
        $cards = M('cards');
        $safety = M('safety_monitoring');
        $ptime=date('H:i:s',time());
        $date=date('Ymd',time());
        $where['cardno']=$cardno;
        $where['status']=$status;
        $cardif=$cards->where($where)->field('customid,cardno,cardbalance,panterid')->find();
        if($cardif==false){
            $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'卡状态'.$status.'此卡不是新卡，或者为非法卡，不能充值！','');
            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','查询卡号信息','卡状态:".$status."此卡不是正常卡，或者为非法卡，不能充值！','04')");
            $res['msg']='此卡不是新卡，或者为非法卡，不能充值！';
        }else{
            if($panterid!='FFFFFFFF'){
                if($cardif['panterid']!=$panterid){
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'商员编号'.$panterid.'此卡非本部门卡，无法充值!','');
                    $safety->execute("insert into safety_monitoring values('{$panterid}','{$date}','{$ptime}','查询卡号信息','卡号:".$cardno."此卡非本部门卡，无法充值!','02')");
                    $res['msg']='此卡非本部门卡，无法充值!';
                }else{
                    $res['data']=$cardif;
                    $res['success']=1;
                }
            }else if(trim($cardif['customid'])!='0'){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'商员编号'.$panterid.'此卡非第一次充值','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','查询卡号信息','卡号:".$cardno."此卡非第一次充值','04')");
                $res['msg']='此卡非第一次充值!';
            }else{
                $res['data']=$cardif;
                $res['success']=1;
            }
        }
        echo json_encode($res);
    }
    //售卡审核
    function cardcheck(){
        $userid =  $this->userid;
        $panterid=$this->panterid;
        $userip=$_SERVER['REMOTE_ADDR'];
        $acetype=trim($_REQUEST['acetype']);
        $gctype=array('0'=>'购卡单','1'=>'充值单');
        $chetype=array('0'=>'未审核','1'=>'审核通过','2'=>'审核未通过');//审核状态
        $paytype=array('00'=>'现金','01'=>'银行卡','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他','09'=>'批量充值');
        $this->assign('paytype',$paytype);
        $this->assign('gctype',$gctype);
        $this->assign('chetype',$chetype);
        $custompl=M('custom_purchase_logs');
        $cardpl = M('card_purchase_logs');
        $customs=M('customs');
        $safety = M('safety_monitoring');
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        if(IS_POST){
            $auditlogs=M('audit_logs');
            $type = trim(I('post.type',0));
            $purchaseid = trim(I('post.xpurchaseid',''));
            $descriptions = trim(I('post.description1',''));
            $totalmoney=trim(I('post.totalmoney',''));
            $cardno=trim(I('post.cardno',''));
            $customid=trim(I('post.xcustomid',''));
            if($purchaseid==''){
                 $this->error('充值单号不能为空!');
            }
            $map['purchaseid']=$purchaseid;
            $info=M('custom_purchase_logs')->where($map)->find();
            if($info['flag']!=0){
                $this->error('已经审核通过，无需重复审核！');
            }
            if($type==0){
                if(empty($cardno)){
                    $this->error('请输入充值卡号');
                }
                $hysx=$this->getHysx();
                if($hysx!='酒店'){
                    $where1['customid']=$info['customid'];
                    $customif=$customs->where($where1)->find();
                    if(empty($customif['namechinese'])||empty($customif['personid'])){
                        if($totalmoney>1000){
                            $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$customif['namechinese'].'充值金额'.$totalmoney.'非实名卡账户金额不能超过1000!','');
                            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','售卡审核','会员名称:".$customif['namechinese']."金额".$totalmoney."非实名卡账户金额不能超过1000!','04')");
                            $this->error('非实名卡账户金额不能超过1000');
                        }
                    }
                    if($totalmoney>5000){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$customif['namechinese'].'充值金额'.$totalmoney.'充值金额不能超过5000','');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','售卡审核','会员名称:".$customif['namechinese']."金额".$totalmoney."充值金额不能超过5000!','04')");
                        $this->error('充值金额不能超过5000');
                    }
                }

                $where['cardno']=$cardno;
                $where['status']='N';
                $cardinfo=M('cards')->where($where)->field('status')->find();
                if($cardinfo['status']!='N'){
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$customif['namechinese'].'充值金额'.$totalmoney.'卡状态：'.$cardinfo['status'].'充值卡号非新卡，不能售卡！','');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','售卡审核','卡状态:".$cardinfo['status']."金额".$totalmoney."充值卡号非新卡，不能售卡！','04')");
                    $this->error('充值卡号非新卡，不能售卡！');
                }
                $model=new Model();
                $model->startTrans();
                $customplSql="update custom_purchase_logs set flag='1',checkdate='".date('Ymd')."',checktime='".date('H:i:s')."'";
                $customplSql.=",aditid='".$userid."',description1='".$descriptions."' where purchaseId='".$purchaseid."'";
                $customplif=$model->execute($customplSql);
                //$auditid=$this->getnextcode('audit_logs',16);
                $auditid=$this->getFieldNextNumber('auditid');
                $auditlogsSql="insert into audit_logs values ('".$auditid."','".$purchaseid."','审核通过','";
                $auditlogsSql.=$descriptions."','".date('Ymd')."','".$userid ."','".date('H:i:s')."')";
                $auditlogsif=$model->execute($auditlogsSql);
                $exePram=array('purchaseid'=>$purchaseid,'cardno'=>$cardno,'totalmoney'=>$totalmoney,'customid'=>$customid);
                $exeBool=$this->sellExecute($exePram);
                if($exeBool==true&&$auditlogsif==true&&$customplif==true){
                    $model->commit();
                    $this->success('售卡成功，卡已充值激活');
                }else{
                    $model->rollback();
                    $this->error('审核失败');
                }
            }else{
                if($descriptions==''){
                    $this->error('审核备注不能为空!');
                }
                $custompl->execute("update custom_purchase_logs set flag='2',checkdate='".date('Ymd')."',checktime='".date('H:i:s')."',aditid='".$userid."',description1='".$descriptions."' where purchaseId='".$purchaseid."'");
                //$auditid=$this->getnextcode('audit_logs',16);
                $auditid=$this->getFieldNextNumber('auditid');
                $auditlogsif=$auditlogs->execute("insert into audit_logs values ('".$auditid."','".$purchaseid."','审核不通过','".$descriptions."','".date('Ymd')."','".$userid ."','".date('H:i:s')."')");
                $this->success('审核不通过成功');
            }
        }else{
            $start = trim(I('get.startdate',''));
            $end = trim(I('get.enddate',''));
            $purchaseid = trim(I('get.purchaseid',''));
            $flag   = trim(I('get.flag',''));
            $customid = trim(I('get.customid',''));
            $cname  = trim(I('get.cname',''));
            //$where='';
            if($start!='' && $end==''){
                $startdate = str_replace('-','',$start);
                $where['a.placeddate']=array('egt',$startdate);
                $this->assign('startdate',$start);
                $map['startdate']=$start;
            }
            if($start=='' && $end!=''){
                $enddate = str_replace('-','',$end);
                $where['a.placeddate'] = array('elt',$enddate);
                $this->assign('enddate',$end);
                $map['enddate']=$end;
            }
            if($start!='' && $end!=''){
                $startdate = str_replace('-','',$start);
                $enddate = str_replace('-','',$end);
                $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
                $this->assign('startdate',$start);
                $this->assign('enddate',$end);
                $map['startdate']=$start;
                $map['enddate']=$end;
            }
            if($start==''&&$end==''){
                $datestr=date('Y-m-d');
                $startdate = str_replace('-','',$datestr);
                $enddate = str_replace('-','',$datestr);
                $this->assign('startdate',$datestr);
                $this->assign('enddate',$datestr);
                $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
                $map['startdate']=$start;
                $map['enddate']=$end;
            }
            if($purchaseid!=''){
                $where['a.purchaseid']=$purchaseid;
                $this->assign('purchaseid',$purchaseid);
                $map['purchaseid']=$purchaseid;
            }
            if($flag!=''){
                $where['a.flag']=$flag;
                $this->assign('flag',$flag);
                $map['flag']=$flag;
            }
            if($customid!=''){
                $where['a.customid']=$customid;
                $this->assign('customid',$customid);
                $map['customid']=$customid;
            }
            if($cname!=''){
                $where['b.namechinese']=$cname;
                $this->assign('cname',$cname);
                $map['cname']=$cname;
            }
            $where['a.tradeflag']='0';
            if($this->panterid!='FFFFFFFF'){
                $where['_string']="d.panterid='".$this->panterid."' OR d.parent='".$this->panterid."'";
            }else{
                $where1['_string']="d.hysx <> '酒店' OR d.hysx IS NULL";
                $where['_complex']=$where1;
            }
            $count=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                ->join('left join users c on c.userid=a.userid')
                ->join('left join panters d on d.panterid=a.panterid')
                ->where($where)->field('a.*,b.namechinese,c.username')->count();
            $p=new \Think\Page($count,10);
            $customp_list=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                ->join('left join users c on c.userid=a.userid')
                ->join('left join panters d on d.panterid=a.panterid')
                ->where($where)->field('a.*,b.namechinese,c.username')
                ->limit($p->firstRow.','.$p->listRows)->order('a.placeddate desc')->select();
            //echo $custompl->getLastSql();
            if(!empty($map)){
                foreach($map as $key=>$val){
                    $p->parameter[$key]=$val;
                }
            }
            $page= $p->show();
            $this->assign('list',$customp_list);
            $this->assign('page',$page);
        //    $this->display();
        }
        $this->display();
    }
    //售卡执行
    function sellExecute($exePram){
        $custompl=M('custom_purchase_logs');
        $cardpl = M('card_purchase_logs');
        $cards=M('cards');
        $customsc=M('customs_c');
        $customs=M('customs');
        $cardactive=M('card_active_logs');
        $model=new model();
        $batchbuyBatchLog=array();
        $userid =  $this->userid;
        $panterid=$this->panterid;
        $nowdate=date('Ymd');
        $nowtime=date('H:i:s');
        //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
        $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
        $userip=$_SERVER['REMOTE_ADDR'];
        $cardno=$exePram['cardno'];
        $purchaseid=$exePram['purchaseid'];
        $totalmoney=$exePram['totalmoney'];
        $customid=$exePram['customid'];
        $sql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}',";
        $sql.="'{$cardno}','{$totalmoney}','0','{$nowdate}','{$nowtime}','1','后台充值',";
        $sql.="'{$userid}','{$panterid}','{$userip}','00000000')";
        $cardplif  =$cardpl->execute($sql);
        if($cardplif==false){
            $batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行充值表增加有异常,请联系系统管理员!';
        }
        $customplSql="update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+";
        $customplSql.=$totalmoney." where purchaseid='".$purchaseid."'";
        $customplif=$custompl->execute($customplSql);
        $cardw['customid']=$customid;
        $cardone=$cards->where($cardw)->find();

        if($cardone!=false){
            //$customno  =$this->getnextcode('customs',8);
            $customno=$this->getFieldNextNumber('customid');
            $map['cardno']=$cardno;
            $cardinfo=$cards->where($map)->find();
            $exd=date('Ymd',strtotime("+3 years"));
            $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$customno}'";
            $sql.=",'{$cardinfo['panterid']}','00000000')";
            $customcSql="INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')";
            $customcif =$customsc->execute($customcSql);

            $cardSql="UPDATE cards SET status='Y',customid='{$customno}',cardbalance='{$totalmoney}',exdate='{$exd}' where cardno='".$cardno."'";
            $cardsif  =$cards->execute($cardSql);
            $customif  =$customs->execute("UPDATE customs SET cardno='teshu' where customid='".$customid."'");
            $cardactiveif=$model->table('card_active_logs')->execute($sql);
        }else{
            $map['cardno']=$cardno;
            $customno=$customid;
            $cardinfo=$cards->where($map)->find();
            $exd=date('Ymd',strtotime("+3 years"));
            $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
            $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','".$customid;
            $sql.=   "','{$cardinfo['panterid']}','00000000')";
            $customcif =$customsc->execute("INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')");
            $cardsif  =$cards->execute("UPDATE cards SET status='Y',customid='".$customid."',exdate='".$exd."' where cardno='".$cardno."'");
            $cardactiveif=$model->table('card_active_logs')->execute($sql);
        }

        $account=M('account');
        $cardw1['type']='00';
        $cardw1['customid']=$customno;
        $accountone = $account->where($cardw1)->find();
        if($accountone==false){
            //$acid = $this->getnextcode('account',8);
            $acid=$this->getFieldNextNumber('accountid');
            $accountSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('";
            $accountSql.=$acid."','".$customno."','".$totalmoney."','00',NULL)";
            $acountif = $account->execute($accountSql);
        }else{
            $accountSql="UPDATE account SET amount=nvl(amount,0)+".$totalmoney." where customid='".$customno."' and type='00'";
            $acountif = $account->execute($accountSql);
        }
        $cardw1['type']='01';
        $cardw1['customid']=$customno;
        $accountones = $account->where($cardw1)->find();
        if($accountones==false){
            //$acid = $this->getnextcode('account',8);
            $acid=$this->getFieldNextNumber('accountid');
            $accountSql1="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acid."','".$customno."','0','01',NULL)";
            $acounteif = $account->execute($accountSql1);
        }
        $cardw1['type']='04';
        $cardw1['customid']=$customno;
        $accountones = $account->where($cardw1)->find();
        if($accountones==false){
            //$acid = $this->getnextcode('account',8);
            $acid=$this->getFieldNextNumber('accountid');
            $accountSql2="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acid."','".$customno."','0','04',NULL)";
            $acountsif=$account->execute($accountSql2);
        }
        if($cardplif==false || $customplif==false || $customcif==false || $cardsif==false || $acountif==false || $cardactiveif==false){
            $res=false;
            $batchbuyBatchLog[0]['status']=0;
            $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行有异常,请联系系统管理员!';
        }else{
            $res=true;
            $batchbuyBatchLog[0]['status']=1;
            $batchbuyBatchLog[0]['msg']='购卡执行成功!';
        }
        if(!empty($batchbuyBatchLog)){
            $this->batchbuyLogs($batchbuyBatchLog);
        }
        return $res;
    }
    function cardexecute(){
        $custompl=M('custom_purchase_logs');
        $customs=M('customs');
        $safety = M('safety_monitoring');
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        if(IS_POST){
            $customid = trim(I('post.customid',''));
            $this->assign('customid',$customid);
            $cuname = trim(I('post.cuname',''));
            $this->assign('cuname',$cuname);
            $purchaseid = trim(I('post.cpurchaseid',''));
            $this->assign('purchaseid',$purchaseid);
            $cardno = trim(I('post.cardno',''));
            $this->assign('cardno',$cardno);
            $totalmoney = trim(I('post.totalmoney',''));
            $this->assign('totalmoney',$totalmoney);
            $realamount = trim(I('post.realamount',''));
            $this->assign('realamount',$realamount);
            $cardnumber = trim(I('post.cardnumber',''));
            $this->assign('cardnumber',$cardnumber);
            $hidecardno = trim(I('post.hidecardno',''));
            $date=date('Ymd',time());
            $userid =  $this->userid;
            $panterid=$this->panterid;
            $cards = M('cards');
            $cardactive=M('card_active_logs');
            if($cardno==''){
                $this->error('读取的卡号不能为空!');
            }
            if($hidecardno==''){
                $this->error('没有选择的数据，无法充值执行！');
            }
            if($realamount==''){
                $this->error('卡片初始充值金额为空，无法充值执行！');
            }
            $hysx=$this->getHysx();
//            if($hysx!='酒店'){
//                if($totalmoney>5000||$realamount>5000){
//                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$cuname.'充值金额'.$realamount.'充值金额不能超过5000','');
//                    $this->error('充值金额不能超过5000');
//                }
//            }
            if($hysx!='酒店'){
                $where1['customid']=$customid;
                $customif=$customs->where($where1)->find();
                if(empty($customif['namechinese'])||empty($customif['personid'])){
                    if($totalmoney>1000){
                        $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$customif['namechinese'].'充值金额'.$totalmoney.'非实名卡账户金额不能超过1000!','');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','会员名称:".$customif['namechinese']."金额".$totalmoney."非实名卡账户金额不能超过1000!','04')");
                        $this->error('非实名卡账户充值金额不能超过1000');
                    }
                }
                if($totalmoney>5000){
                    $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$customif['namechinese'].'充值金额'.$totalmoney.'充值金额不能超过5000','');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','会员名称:".$customif['namechinese']."金额".$totalmoney."充值金额不能超过5000!','04')");
                    $this->error('充值金额不能超过5000');
                }
            }

            $custompl=M('custom_purchase_logs');
            $cardpl = M('card_purchase_logs');
            if($totalmoney < $realamount){
                $this->think_send_mail('349409263@qq.com',$date,$cardno,'时间：'.$date.'卡号:'.$cardno.'会员名称：'.$cuname.'充值金额'.$realamount.'充值金额已超过充值单余额，请修改!','');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','会员名称:".$customif['namechinese']."金额".$totalmoney."充值金额已超过充值单余额，请修改!','04')");
                $this->error('充值金额已超过充值单余额，请修改!');
            }
            $customsc=M('customs_c');
            $batchbuyBatchLog=array();
            $cardpl->startTrans();
            $nowdate=date('Ymd');
            $nowtime=date('H:i:s');
            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid=$this->getFieldNextNumber('cardpurchaseid');
            $userip=$_SERVER['REMOTE_ADDR'];
            $sql="INSERT INTO card_purchase_logs VALUES('{$cardpurchaseid}','{$purchaseid}','{$cardno}','{$realamount}','0','{$nowdate}','{$nowtime}','1','后台充值','{$userid}','{$panterid}','{$userip}','00000000')";
            $cardplif  =$cardpl->execute($sql);
            if($cardplif==false){
                $cardpl->rollback();
                $batchbuyBatchLog[0]['status']=0;
                $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行充值表增加有异常,请联系系统管理员!';
            }
            $customplif=$custompl->execute("update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+".$realamount." where purchaseid='".$purchaseid."'");
            if($customplif==false){
                $cardpl->rollback();
                $batchbuyBatchLog[0]['status']=0;
                $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行购卡充值单表更新有异常,请联系系统管理员!';
            }
            $cardw['customid']=$customid;
            $cardone=$cards->where($cardw)->find();
            if($cardone!=false){
                //$customno  =$this->getnextcode('customs',8);
                $customno=$this->getFieldNextNumber('customid');
                $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
                $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','{$customno}','{$cardone['panterid']}','00000000')";

                $customcif =$customsc->execute("INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')");
                $cardsif  =$cards->execute("UPDATE cards SET status='Y',customid='{$customno}',cardbalance='{$realamount}' where cardno='".$cardno."'");
                $customif  =$customs->execute("UPDATE customs SET cardno='teshu' where customid='".$customno."'");
                $cardactiveif=$cardactive->execute($sql);
            }else{
                $map['cardno']=$cardno;
                $customno=$customid;
                $cardinfo=$cards->where($map)->find();
                $sql="INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}',".date('Ymd');
                $sql.=",'0','Y','00',".date('Ymd').",'".date('H:i:s')."','售卡激活','".$customid."','{$cardinfo['panterid']}','00000000')";

                $customcif =$customsc->execute("INSERT INTO customs_c(customid,cid) VALUES('".$customid."','".$customno."')");
                $cardsif  =$cards->execute("UPDATE cards SET status='Y',customid='".$customid."' where cardno='".$cardno."'");

                $cardactiveif=$cardactive->execute($sql);
            }
            $account=M('account');
            $cardw1['type']='00';
            $cardw1['customid']=$customno;
            $accountone = $account->where($cardw1)->find();
            //echo $account->getLastSql();exit;
            if($accountone==false){
                //$acid = $this->getnextcode('account',8);
                $acid=$this->getFieldNextNumber('accountid');
                $acountif = $account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acid."','".$customno."','".$realamount."','00',NULL)");
            }else{
                $acountif = $account->execute("UPDATE account SET amount=nvl(amount,0)+".$realamount." where customid='".$customno."' and type='00'");
            }
            $cardw1['type']='01';
            $cardw1['customid']=$customno;
            $accountones = $account->where($cardw1)->find();
            if($accountones==false){
                //$acid = $this->getnextcode('account',8);
                $acid=$this->getFieldNextNumber('accountid');
                $acounteif = $account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acid."','".$customno."','0','01',NULL)");
            }
            $cardw1['type']='04';
            $cardw1['customid']=$customno;
            $accountones = $account->where($cardw1)->find();
            if($accountones==false){
                //$acid = $this->getnextcode('account',8);
                $acid=$this->getFieldNextNumber('accountid');
                $acountsif=$account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acid."','".$customno."','0','04',NULL)");
            }
            if($cardplif==false || $customplif==false || $customcif==false || $cardsif==false || $acountif==false || $cardactiveif==false){
                $cardpl->rollback();
                $batchbuyBatchLog[0]['status']=0;
                $batchbuyBatchLog[0]['msg']='卡号'.$cardno.'购卡执行有异常,请联系系统管理员!';
            }else{
                $cardpl->commit();
                $batchbuyBatchLog[0]['status']=1;
                $batchbuyBatchLog[0]['msg']='购卡执行成功!';
            }
            if(!empty($batchbuyBatchLog)){
                $this->batchbuyLogs($batchbuyBatchLog);
            }
            $this->success('售卡充值成功，卡已激活',U('Card/cardexecute'));
        }else{
            $hysx=$this->getHysx();
            if($hysx!='酒店'){
                $this->assign('permit',0);
            }else{
                $this->assign('permit',1);
            }
            $this->display();
        }
    }
    //卡充值登记
    function cardpay(){
        $custompl=M('custom_purchase_logs');
        $customs=M('customs');
        $gctype=array('0'=>'购卡单','1'=>'充值单');
        $paytype=array('00'=>'现金','01'=>'银行卡','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他','09'=>'批量充值');
        $this->assign('gctype',$gctype);
        $this->assign('paytype',$paytype);
        $safety = M('safety_monitoring');
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        if(IS_POST){
            $customid = trim(I('post.customid',''));
            $cnames = trim(I('post.cnames',''));
            $tradeflag = trim(I('post.tradeflag',''));
            $customlevel = trim(I('post.customlevel',''));
            $paymenttype = trim(I('post.paymenttype',''));
            $totalmoney = trim(I('post.totalmoney',''));
            $realamount = trim(I('post.realamount',''));
            $description = trim(I('post.description',''));
            $checkno    = trim(I('post.checkno',0));
            $hysx=$this->getHysx();
            $panterid=$this->panterid;
            if($totalmoney==0){
                $this->error('充值金额不能为0');
            }
            $date=date('Ymd',time());
            $where['customid']=$customid;
            $customif=$customs->where($where)->find();
            if($customif==false){
                $customs->execute("insert into customs(customid,customlevel,panterid) values('".$customid."','一般客户','".$panterid."')");
            }else{
                if($hysx!='酒店'){
                    if(empty($customif['namechinese'])||empty($customif['personid'])){
                        if($totalmoney>1000||$realamount>1000){
                            $this->think_send_mail('349409263@qq.com',$date,$customid,'会员编号'.$customid
                                .'充值金额：'.$realamount.'非实名卡充值不能超过1000','');
                            $safety->execute("insert into safety_monitoring values('{$customid}','{$date}','{$ptime}','卡充值登记','充值金额：:".$totalmoney."金额".$realamount."非实名卡充值不能超过1000','01')");
                            $this->error('非实名卡充值不能超过1000');
                        }
                    }
                    if($totalmoney>5000||$realamount>5000){
                        $this->think_send_mail('349409263@qq.com',$date,$customid,'会员编号'.$customid
                            .'充值金额：'.$realamount.'充值金额不能超过5000','');
                        $safety->execute("insert into safety_monitoring values('{$customid}','{$date}','{$ptime}','卡充值登记','充值金额：:".$totalmoney."金额".$realamount."充值不能超过5000','01')");
                        $this->error('充值金额不能超过5000');
                    }
                }

            }
            $userid =  $this->userid;
            $userstr= substr($userid,12,4);
            //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
            $purchaseid=$this->getFieldNextNumber('purchaseid');
            $purchaseid=$userstr.$purchaseid;
            if($customlevel=='一般顾客'){
                $customflag=0;
            }else{
                $customflag=1;
            }
            $customplif = $custompl->execute("insert into custom_purchase_logs values('".$customid."','".$purchaseid."','".date('Ymd')."','".$paymenttype."','".$userid."','".$totalmoney."',NULL,'".$totalmoney."',0,'".$realamount."',0,0,'".$checkno."','".$description."','0','".$panterid."',NULL,NULL,'".$tradeflag."','".$customflag."',NULL,'00000000','".date('H:i:s')."',NULL,NULL,NULL)");
            $this->success('生成单据成功！',U('Card/cardpay'));
            /*$sostr['a.customid']=$customid;
            $sostr['a.tradeflag']=0;
            $customone=$custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                            ->join('left join panters c on c.panterid=a.panterid')->field('a.purchaseid,a.customid,b.namechinese,a.tradeflag,a.totalmoney,a.realamount')->where($sostr)->select();
            $this->assign('list',$customone);*/
        }else{
            $start = trim(I('get.startdate',''));
            $end = trim(I('get.enddate',''));
            $customsid = trim(I('get.customsid',''));
            $cuname  = trim(I('get.cuname',''));
            if($start!='' && $end==''){
                $startdate = str_replace('-','',$start);
                $where['a.placeddate']=array('egt',$startdate);
                $this->assign('startdate',$start);
                $map['startdate']=$start;
            }
            if($start=='' && $end!=''){
                $enddate = str_replace('-','',$end);
                $where['a.placeddate'] = array('elt',$enddate);
                $this->assign('enddate',$end);
                $map['enddate']=$end;
            }
            if($start!='' && $end!=''){
                $startdate = str_replace('-','',$start);
                $enddate = str_replace('-','',$end);
                $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
                $this->assign('startdate',$start);
                $this->assign('enddate',$end);
                $map['startdate']=$start;
                $map['enddate']=$end;
            }
            if($customsid!=''){
                $where['a.customid'] = $customsid;
                $this->assign('customsid',$customsid);
                $map['customsid']=$customsid;
            }
            if($cuname!=''){
                $where['b.namechinese']=array('like','%'.$cuname.'%');
                $this->assign('cuname',$cuname);
                $map['cuname']=$cuname;
            }
            $where['a.tradeflag']='1';
            if($this->panterid!='FFFFFFFF'){
                $where['_string']="c.panterid='".$this->panterid."' OR c.parent='".$this->panterid."'";
            }else{
                $where1['_string']="c.hysx <> '酒店' OR c.hysx IS NULL";
                $where['_complex']=$where1;
            }
            $count=$custompl->alias('a')
                            ->join('left join customs b on b.customid=a.customid')
                           ->join('left join panters c on c.panterid=a.panterid')
                           ->field('a.purchaseid,a.customid,b.namechinese,a.tradeflag,a.totalmoney,a.realamount,a.placeddate,a.placedtime')
                           ->where($where)->count();
            $p=new \Think\Page($count,15);
            $custompl_list=$custompl->alias('a')
                           ->join('left join customs b on b.customid=a.customid')
                           ->join('left join panters c on c.panterid=a.panterid')
                           ->field('a.purchaseid,a.customid,b.namechinese,a.tradeflag,a.totalmoney,a.realamount,a.placeddate,a.placedtime')
                           ->where($where)->order('a.placeddate desc,a.placedtime desc')
                           ->limit($p->firstRow.','.$p->listRows)->select();
            $page=$p->show();
            $hysx=$this->getHysx();
            if($hysx!='酒店'){
                $this->assign('permit',0);
            }else{
                $this->assign('permit',1);
            }
            $this->assign('panterid',$this->panterid);
            $this->assign('page',$page);
            $this->assign('list',$custompl_list);
            $firstDate=date('Ymd',time());
            $secondDate=date('Ymd',(time()-7*24*3600));
            if($this->panterid!='FFFFFFFF'){
                $map1['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
            }
            $map1['c.placeddate']=array('between',array($secondDate,$firstDate));
            $recentCustoms=M('customs')->alias('c')
                ->join('panters p on c.panterid=p.panterid')
                ->field('c.customid,c.namechinese,c.customlevel,c.linktel')
                ->where($map1)->select();
            $this->assign('recentCustoms',$recentCustoms);
            $this->display();
        }
    }
    //卡充值审核
    function paycheck()
    {
        $userid = $this->userid;
        $panterid = $this->panterid;
        $userip = $_SERVER['REMOTE_ADDR'];
        $acetype = trim($_REQUEST['acetype']);
        $gctype = array('0' => '购卡单', '1' => '充值单');
        $chetype = array('0' => '未审核', '1' => '审核通过', '2' => '审核未通过');//审核状态
        $paytype = array('00' => '现金', '01' => '银行卡', '02' => '支票', '03' => '汇款', '04' => '网上支付', '05' => '转账', '06' => '内部转账', '07' => '赠送', '08' => '其他', '09' => '批量充值');
        $this->assign('paytype', $paytype);
        $this->assign('gctype', $gctype);
        $this->assign('chetype', $chetype);
        $custompl = M('custom_purchase_logs');
        $cardpl = M('card_purchase_logs');
        $customs = M('customs');
        $safety = M('safety_monitoring');
        $date=date('Ymd',time());
        $ptime=date('H:i:s',time());
        if (IS_POST) {
            $auditlogs = M('audit_logs');
            $type = trim(I('post.type', 0));
            $purchaseid = trim(I('post.xpurchaseid', ''));
            $descriptions = trim(I('post.description1', ''));
            $cardno = trim(I('post.cardno', ''));
            $customid = trim(I('post.xcustomid', ''));
            $totalmoney = trim(I('post.totalmoney', ''));
            $model = new Model();
            if ($purchaseid == '') {
                $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                    . '充值单号：' . $purchaseid . '充值单号不能为空!', '');
                $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','充值单号:".$purchaseid."充值单号不能为空!','04')");
                $this->error('充值单号不能为空!');
            }
            $map['purchaseid'] = $purchaseid;
            $info = M('custom_purchase_logs')->field('flag')->where($map)->find();

            if ($info['flag'] != 0) {
                $this->error('已经审核通过，无需重复审核！');
            }
            if ($type == 0) {
                if (empty($cardno)) {
                    $this->error('请输入充值卡号');
                }
//                $hysx = $this->getHysx();
//                if ($hysx != '酒店') {
                $ma['cardno'] = $cardno;
                $cardkind=M('cards')->field('cardkind')->where($ma)->find();
                $cardk=$cardkind['cardkind'];
                $accCondition = array('c.cardno' => $cardno, 'a.type' => '00');
                $account = $model->table('cards')->alias('c')
                    ->join('account a on c.customid=a.customid')
                    ->where($accCondition)->field('a.amount')->find();
                $totAccount = $totalmoney + $account['amount'];
                if(!in_array($cardk,array('2081','6882'))){
//                    echo '111';exit;
                    $where['customid']=$customid;
                    $customif=$customs->where($where)->find();
                    // dump($customif);exit;
                    if (empty($customif['namechinese']) || empty($customif['personid'])){
                        if ($totAccount > 1000) {
                            $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                                . '账户金额' . $totAccount . '非实名卡账户金额不能超过1000', '');
                            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','卡号:".$cardno."非实名卡账户金额不能超过1000!','04')");
                            $this->error('非实名卡账户金额不能超过1000');
                        }
                    }
                    if ($totAccount > 5000) {
                        $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                            . '账户总金额：' . $totAccount . '账户总金额不能超过5000', '');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','卡号:".$cardno."总金额:".$totAccount."账户总金额不能超过5000!','04')");
                        $this->error('账户总金额不能超过5000');
                    }
                }

                    $map['cardno'] = $cardno;
                    $map['status'] = 'Y';
                    if ($this->panterid != 'FFFFFFFF') {
                        $map['panterid'] = $this->panterid;
                    }
                    $cardinfo = M('cards')->where($map)->find();
                    if ($cardinfo == false) {
                        $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                            . '非正常卡或者非部门卡，无法充值', '');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','卡号:".$cardno."非正常卡或者非部门卡，无法充值!','04')");
                        $this->error('非正常卡或者非部门卡，无法充值');
                    }
                    $customs_cMap = array('customid' => $customid, 'cid' => $cardinfo['customid']);
                    $customs_cInfo = M('customs_c')->where($customs_cMap)->find();
                    if ($customs_cInfo == false) {
                        $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno . '充值卡号和会员不一致', '');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','卡号:".$cardno."充值卡号和会员不一致!','04')");
                        $this->error('充值卡号和会员不一致');
                    }
                    $model->startTrans();
                    $customplSql = "update custom_purchase_logs set flag='1',checkdate='" . date('Ymd') . "',checktime='" . date('H:i:s');
                    $customplSql .= "',aditid='" . $userid . "',description1='" . $descriptions . "' where purchaseId='" . $purchaseid . "'";
                    $customplif = $model->execute($customplSql);
                    //$auditid=$this->getnextcode('audit_logs',16);
                    $auditid = $this->getFieldNextNumber('auditid');
                    $auditlogsSql = "insert into audit_logs values ('" . $auditid . "','" . $purchaseid . "','审核通过','";
                    $auditlogsSql .= $descriptions . "','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s') . "')";
                    $auditlogsif = $model->execute($auditlogsSql);
                    $exeParam = array('cardno' => $cardno, 'customid' => $cardinfo['customid'],
                        'purchaseid' => $purchaseid, 'totalmoney' => $totalmoney);
                    $exeif = $this->payExe($exeParam);
                    if ($auditlogsif == true && $customplif == true && $exeif == true) {
                        $model->commit();
                        $this->success('审核通过成功,卡已充值');
                    } else {
                        $model->rollback();
                        $this->error('审核通过失败');
                    }
                }else{
                    if ($descriptions == '') {
                        $this->error('审核备注不能为空!');
                    }

                    $customplSql = "update custom_purchase_logs set flag='2',checkdate='" . date('Ymd') . "',checktime='";
                    $customplSql .= date('H:i:s') . "',aditid='" . $userid . "',description1='" . $descriptions;
                    $customplSql .= "' where purchaseId='" . $purchaseid . "'";
                    $custompl->execute($customplSql);
                    //$auditid=$this->getnextcode('audit_logs',16);
                    $auditid = $this->getFieldNextNumber('auditid');
                    $auditlogsSql = "insert into audit_logs values ('" . $auditid . "','" . $purchaseid . "','审核不通过','";
                    $auditlogsSql .= $descriptions . "','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s') . "')";
                    $auditlogsif = $auditlogs->execute($auditlogsSql);
                    $this->success('审核不通过成功');
                }
            } else {
                $start = trim(I('get.startdate', ''));
                $end = trim(I('get.enddate', ''));
                $purchaseid = trim(I('get.purchaseid', ''));
                $flag = trim(I('get.flag', ''));
                $customid = trim(I('get.customid', ''));
                $cname = trim(I('get.cname', ''));
                $where = '';
                $where['a.flag'] = array('in', array('0', '1', '2'));
                if ($start != '' && $end == '') {
                    $startdate = str_replace('-', '', $start);
                    $where['a.placeddate'] = array('egt', $startdate);
                    $this->assign('startdate', $start);
                    $map['startdate'] = $start;
                }
                if ($start == '' && $end != '') {
                    $enddate = str_replace('-', '', $end);
                    $where['a.placeddate'] = array('elt', $enddate);
                    $this->assign('enddate', $end);
                    $map['enddate'] = $end;
                }
                if ($start != '' && $end != '') {
                    $startdate = str_replace('-', '', $start);
                    $enddate = str_replace('-', '', $end);
                    $where['a.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                    $this->assign('startdate', $start);
                    $this->assign('enddate', $end);
                    $map['startdate'] = $start;
                    $map['enddate'] = $end;
                }
                if ($start == '' && $end == '') {
                    $datestr = date('Y-m-d');
                    $startdate = str_replace('-', '', $datestr);
                    $enddate = str_replace('-', '', $datestr);
                    $this->assign('startdate', $datestr);
                    $this->assign('enddate', $datestr);
                    $where['a.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                    $map['startdate'] = $start;
                    $map['enddate'] = $end;
                }
                if ($purchaseid != '') {
                    $where['a.purchaseid'] = $purchaseid;
                    $this->assign('purchaseid', $purchaseid);
                    $map['purchaseid'] = $purchaseid;
                }
                if ($flag != '') {
                    $where['a.flag'] = $flag;
                    $this->assign('flag', $flag);
                    $map['flag'] = $flag;
                }
                if ($customid != '') {
                    $where['a.customid'] = $customid;
                    $this->assign('customid', $customid);
                    $map['customid'] = $customid;
                }
                if ($cname != '') {
                    $where['b.namechinese'] = $cname;
                    $this->assign('cname', $cname);
                    $map['cname'] = $cname;
                }
                $where['a.tradeflag'] = '1';
                if ($this->panterid != 'FFFFFFFF') {
                    $where['_string'] = "d.panterid='" . $this->panterid . "' OR d.parent='" . $this->panterid . "'";
                } else {
                    $where1['_string'] = "d.hysx <> '酒店' OR d.hysx IS NULL";
                    $where['_complex'] = $where1;
                }
                $count = $custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                    ->join('left join users c on c.userid=a.userid')
                    ->join('left join panters d on d.panterid=a.panterid')
                    ->where($where)->field('a.*,b.namechinese,c.username')->count();
                $p = new \Think\Page($count, 10);
                $customp_list = $custompl->alias('a')->join('left join customs b on b.customid=a.customid')
                    ->join('left join users c on c.userid=a.userid')
                    ->join('left join panters d on d.panterid=a.panterid')
                    ->where($where)->field('a.*,b.namechinese,c.username')
                    ->limit($p->firstRow . ',' . $p->listRows)->order('a.placeddate desc')->select();
                if (!empty($map)) {
                    foreach ($map as $key => $val) {
                        $p->parameter[$key] = $val;
                    }
                }
                $page = $p->show();
                $this->assign('list', $customp_list);
                $this->assign('page', $page);
               // $this->display();
            }
        $this->display();
    }
        function payExe($exeParam)
        {
            $customsc = M('customs_c');
            $cards = M('cards');
            $cardpl = M('card_purchase_logs');
            $custompl = M('custom_purchase_logs');
            $account = M('account');
            $batchbuyBatchLog = array();
            $userid = $this->userid;
            $panterid = $this->panterid;
            $nowdate = date('Ymd');
            $nowtime = date('H:i:s');
            $totalmoney = $exeParam['totalmoney'];
            $cardno = $exeParam['cardno'];
            $purchaseid = $exeParam['purchaseid'];
            //$zszzb=$exeParam['zszzb'];
            $customid = $exeParam['customid'];
            $cardSql = "UPDATE cards SET cardbalance=nvl(cardbalance,0)+" . $totalmoney . " where cardno='" . $cardno . "'";
            $cardsif = $cards->execute($cardSql);
            if ($cardsif == false) {
                $batchbuyBatchLog[0]['status'] = 0;
                $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行卡表列新有异常,请联系系统管理员!';
            }
            //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
            $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
            $userip = $_SERVER['REMOTE_ADDR'];

            $cardplSql = "INSERT INTO card_purchase_logs VALUES('" . $cardpurchaseid . "','" . $purchaseid . "','" . $cardno . "','";
            $cardplSql .= $totalmoney . "','0','" . $nowdate . "','" . $nowtime . "','1','后台充值','" . $userid . "','";
            $cardplSql .= $panterid . "','" . $userip . "','00000000')";

            $cardplif = $cardpl->execute($cardplSql);
            if ($cardplif == false) {
                $batchbuyBatchLog[0]['status'] = 0;
                $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行充值表增加有异常,请联系系统管理员!';
            }
            $customplSql = "update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+";
            $customplSql .= $totalmoney . " where purchaseid='" . $purchaseid . "'";

            $customplif = $custompl->execute($customplSql);
            if ($customplif == false) {
                $batchbuyBatchLog[0]['status'] = 0;
                $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行购卡充值单表更新有异常,请联系系统管理员!';
            }
            $acountSql = "UPDATE account SET amount=nvl(amount,0)+" . $totalmoney . " where customid='" . $customid . "' and type='00'";
            $acountif = $account->execute($acountSql);
//        $acounteSql="UPDATE account SET amount=nvl(amount,0)+".$zszzb." where customid='".$customid."' and type='01'";
//        $acounteif = $account->execute($acounteSql);


            if ($acountif == false || $acountif == false || $customplif == false || $cardplif == false || $cardsif == false) {
                $res = false;
                $batchbuyBatchLog[0]['status'] = 0;
                $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行有异常,请联系系统管理员!';
            } else {
                $res = true;
                $batchbuyBatchLog[0]['status'] = 1;
                $batchbuyBatchLog[0]['msg'] = '购卡执行成功!';
            }
            if (!empty($batchbuyBatchLog)) {
                $this->batchbuyLogs($batchbuyBatchLog);
            }
            return $res;
        }

        //卡充值执行
        function payexecute()
        {
            $custompl = M('custom_purchase_logs');
            $customs = M('customs');
            $safety = M('safety_monitoring');
            $date=date('Ymd',time());
            $ptime=date('H:i:s',time());
            if (IS_POST) {
                $date = date('Ymd', time());
                $customid = trim(I('post.customid', ''));
                $this->assign('customid', $customid);
                $cuname = trim(I('post.cuname', ''));
                $this->assign('cuname', $cuname);
                $purchaseid = trim(I('post.cpurchaseid', ''));
                $this->assign('purchaseid', $purchaseid);
                $cardno = trim(I('post.cardno', ''));
                $this->assign('cardno', $cardno);
                $totalmoney = trim(I('post.totalmoney', ''));
                $this->assign('totalmoney', $totalmoney);
                $realamount = trim(I('post.realamount', ''));
                $this->assign('realamount', $realamount);
                $cardnumber = trim(I('post.cardnumber', ''));
                $this->assign('cardnumber', $cardnumber);
                $zszzb = trim(I('post.zszzb', 0));
                $this->assign('zszzb', $zszzb);
                $type = trim(I('post.type', 0));
                $hidecardno = trim(I('post.hidecardno', ''));
                $model = new Model();
              //  $hysx = $this->getHysx();
//                if ($hysx != '酒店') {
//                    $where['customid'] = $customid;
//                    $customif = $customs->where($where)->find();
//                    if (empty($customif['namechinese']) || empty($customif['personid'])) {
//                        if ($totalmoney > 1000 || $realamount > 1000) {
//                            $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
//                                . '充值金额' . $totalmoney . '非实名卡充值金额不能超过1000', '');
//                            $this->error('非实名卡充值金额不能超过1000');
//                        }
//                    }
//                }
                $ma['cardno'] = $cardno;
                $cardkind=M('cards')->field('cardkind')->where($ma)->find();
                $cardk=$cardkind['cardkind'];
                $accCondition = array('c.cardno' => $cardno, 'a.type' => '00');
                $account = $model->table('cards')->alias('c')
                    ->join('account a on c.customid=a.customid')
                    ->where($accCondition)->field('a.amount')->find();
                $totAccount = $totalmoney + $account['amount'];
                if(!in_array($cardk,array('2081','6882'))){
//                    echo '111';exit;
                    $where['customid']=$customid;
                    $customif=$customs->where($where)->find();
                    if (empty($customif['namechinese']) || empty($customif['personid'])){
                        if ($totAccount > 1000) {
                            $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                                . '账户金额' . $totAccount . '非实名卡账户金额不能超过1000', '');
                            $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','充值金额:".$totalmoney."非实名卡充值金额不能超过1000!','04')");
                            $this->error('非实名卡账户金额不能超过1000');
                        }
                    }
                    if ($totAccount > 5000) {
                        $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                            . '账户总金额：' . $totAccount . '账户总金额不能超过5000', '');
                        $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','充值金额:".$totalmoney."充值金额不能超过5000!','04')");
                        $this->error('账户总金额不能超过5000');
                    }
                    if ($totalmoney > 5000 || $realamount > 5000) {
                        $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                            . '充值金额' . $totalmoney . '充值金额不能超过5000', '');
                        $this->error('充值金额不能超过5000');
                    }
                }

                $userid = $this->userid;
                $panterid = $this->panterid;
                $cards = M('cards');
                if ($cardno == '') {
                    $this->error('读取的卡号不能为空!');
                }
                if ($hidecardno == '') {
                    $this->error('没有选择的数据，无法充值执行！');
                }
                if ($realamount == '') {
                    $this->error('卡片初始充值金额为空，无法充值执行！');
                }
                if ($zszzb == '') {
                    $zszzb = 0;
                }
                $map['cardno'] = $cardno;
                $map['status'] = 'Y';
                if ($this->panterid != 'FFFFFFFF') {
                    $map['panterid'] = $this->panterid;
                }
                $cardinfo = $cards->where($map)->find();
                if ($cardinfo == false) {
                    $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                        . '非正常卡或者非部门卡，无法充值', '');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','卡号:".$cardno."非正常卡或者非部门卡，无法充值!','04')");
                    $this->error('非正常卡或者非部门卡，无法充值');
                }
                $custompl = M('custom_purchase_logs');
                $cardpl = M('card_purchase_logs');
                if ($totalmoney < $realamount) {
                    $this->think_send_mail('349409263@qq.com', $date, $cardno, '卡号' . $cardno
                        . '充值金额：' . $realamount . '充值单余额:' . $totalmoney . '充值金额已超过充值单余额，请修改!', '');
                    $safety->execute("insert into safety_monitoring values('{$cardno}','{$date}','{$ptime}','卡充值','充值金额:".$realamount."充值单余额:".$totalmoney."充值金额已超过充值单余额','04')");
                    $this->error('充值金额已超过充值单余额，请修改!');
                }
                $customsc = M('customs_c');
                $batchbuyBatchLog = array();
                $cardpl->startTrans();
                $nowdate = date('Ymd');
                $nowtime = date('H:i:s');
                $cardsif = $cards->execute("UPDATE cards SET cardbalance=cardbalance+" . $realamount . " where cardno='" . $cardno . "'");
                //echo $cards->getLastSql();exit;
                if ($cardsif == false) {
                    $cardpl->rollback();
                    $batchbuyBatchLog[0]['status'] = 0;
                    $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行卡表列新有异常,请联系系统管理员!';
                }
                //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
                $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
                $userip = $_SERVER['REMOTE_ADDR'];
                $cardplif = $cardpl->execute("INSERT INTO card_purchase_logs VALUES('" . $cardpurchaseid . "','" . $purchaseid . "','" . trim($cardno) . "','" . $realamount . "','0','" . $nowdate . "','" . $nowtime . "','1','后台充值','" . $userid . "','" . $panterid . "','" . $userip . "','00000000')");
                if ($cardplif == false) {
                    $cardpl->rollback();
                    $batchbuyBatchLog[0]['status'] = 0;
                    $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行充值表增加有异常,请联系系统管理员!';
                }
                $customplif = $custompl->execute("update custom_purchase_logs set writenumber=writenumber+1, writeamount=writeamount+" . $realamount . " where purchaseid='" . $purchaseid . "'");
                if ($customplif == false) {
                    $cardpl->rollback();
                    $batchbuyBatchLog[0]['status'] = 0;
                    $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行购卡充值单表更新有异常,请联系系统管理员!';
                }
                $account = M('account');
                $acountif = $account->execute("UPDATE account SET amount=nvl(amount,0)+" . $realamount . " where customid='" . $cardinfo['customid'] . "' and type='00'");
                $acounteif = $account->execute("UPDATE account SET amount=nvl(amount,0)+" . $zszzb . " where customid='" . $cardinfo['customid'] . "' and type='01'");

                if ($acountif == false || $acounteif == false) {
                    $cardpl->rollback();
                    $batchbuyBatchLog[0]['status'] = 0;
                    $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行有异常,请联系系统管理员!';
                } else {
                    $cardpl->commit();
                    $batchbuyBatchLog[0]['status'] = 1;
                    $batchbuyBatchLog[0]['msg'] = '购卡执行成功!';
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $this->success('充值执行成功', U('Card/payexecute'), 5);
            } else {
                $hysx = $this->getHysx();
                if ($hysx != '酒店') {
                    $this->assign('permit', 0);
                } else {
                    $this->assign('permit', 1);
                }
          //      $this->display();
            }
            $this->display();
        }

        //积分充值
        function pointpay()
        {
            $cardpl = M('card_purchase_logs');
            $account = M('account');
            if (IS_POST) {
                $cuname = trim(I('post.cuname', ''));
                $linktel = trim(I('post.linktel', ''));
                $customlevel = trim(I('post.customlevel', ''));
                $cardno = trim(I('post.cardno', ''));
                $amount = trim(I('post.amount', 0));
                $camount = trim(I('post.camount', 0));
                $cstatus = trim(I('post.cstatus', ''));
                $cexdate = trim(I('post.cexdate', ''));
                $customid = trim(I('post.customid', ''));
                $is_read = trim(I('post.is_read', ''));
                if ($is_read == 1) {
                    $this->error('已经提交不能重复提交!');
                }
                if ($camount == 0) {
                    $this->error('充值积分不能为空');
                }
                if ($cstatus != 'Y') {
                    $this->error('此卡不是正常卡，不能积分充值！');
                }
                $batchbuyBatchLog = array();
                $cardpl->startTrans();
                $nowdate = date('Ymd');
                $nowtime = date('H:i:s');
                $userid = $this->userid;
                $panterid = $this->panterid;
                //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
                $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
                $userip = $_SERVER['REMOTE_ADDR'];
                $purchaseid = 'F' . substr($cardpurchaseid, 3, 15);
                $cardplif = $cardpl->execute("INSERT INTO card_purchase_logs VALUES('" . $cardpurchaseid . "','" . $purchaseid . "','" . trim($cardno) . "','" . $camount . "','0','" . $nowdate . "','" . $nowtime . "','1','后台积分充值','" . $userid . "','" . $panterid . "','" . $userip . "','00000000')");
                $acounteif = $account->execute("UPDATE account SET amount=nvl(amount,0)+" . $camount . " where customid='" . $customid . "' and type='01'");
                if ($cardplif == false || $acounteif == false) {
                    $cardpl->rollback();
                    $batchbuyBatchLog[0]['status'] = 0;
                    $batchbuyBatchLog[0]['msg'] = '卡号' . $cardno . '卡充值执行有异常,请联系系统管理员!';
                } else {
                    $cardpl->commit();
                    $batchbuyBatchLog[0]['status'] = 1;
                    $batchbuyBatchLog[0]['msg'] = '购卡执行成功!';
                    $this->success('积分充值成功', U('Card/pointpay'), 5);
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
            } else {
                $this->display();
            }
        }

        //批量购卡
        function cardbatchbuy()
        {
            set_time_limit(0);
            $panterid = $this->panterid;
            $userid = $this->userid;
            $hysx = $this->getHysx();
            // $hysx = '酒店'; //获取行业
            if (!empty($_FILES['file_stu']['name'])) {
                $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
                $file_types = explode(".", $_FILES ['file_stu'] ['name']);
                $file_type = $file_types [count($file_types) - 1];
                /*判别是不是.xls文件，判别是不是excel文件*/
                if (strtolower($file_type) != "xls") {
                    $this->error('不是Excel文件，重新上传');
                }
                /*设置上传路径*/
                $savePath = './Public/upfile/Excel/';
                /*以时间来命名上传的文件*/
                $str = date('Ymdhis');
                $file_name = $str . "." . $file_type;
                /*是否上传成功*/
                if (!copy($tmp_file, $savePath . $file_name)) {
                    $this->error('上传失败');
                }
                $exceldate = $this->import_excel($savePath . $file_name);
                // dump($exceldate);exit;
                $cards = M('cards');
                $tempo = M('tempo');
                $gkid = $this->getnextcode('GKID', 8);//获得GKID 8位编号
                $counts = 0;
                $batchbuyBatchLog = array();
                $err = false;
                $ksnum = 1;
                foreach ($exceldate as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchbuyBatchLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    if (array_key_exists("A", $value)) {
                        $batchRechargeLog[$key]['cardno'] = $cardno = trim($value['A']);
                        $batchRechargeLog[$key]['amount'] = $amount = trim($value['B']);
                    } else {
                        $batchRechargeLog[$key]['cardno'] = $cardno = trim($value[0]);
                        $batchRechargeLog[$key]['amount'] = $amount = trim($value[1]);
                    }
                    $where = array();
                    if ($panterid != 'FFFFFFFF') {
                        $where['panterid'] = $panterid;
                    } else {
                      //  if ($hysx != '酒店') {
                            if ($amount > 5000) {
                                $batchbuyBatchLog[$key]['status'] = 0;
                                $batchbuyBatchLog[$key]['msg'] = '充值金额不能大于5000元，请核实!';
                                continue;
                            }
                      //  }
                    }
                    if ($cardno != '') {
                        $where['cardno'] = $cardno;
                    } else {
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号不能为空，请核实!';
                        continue;
                    }
                    $where['status'] = 'N';
                    $card = $cards->where($where)->find();
                    if ($card == false) {
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '有异常,请联系系统管理员! sql:' . $cards->getLastSql();
                        $err = true;
                        continue;
                    }
                    $ppanterid = $card['panterid'];
                    $wtempo = array();
                    $wtempo['cardno'] = $cardno;
                    $tempos = $tempo->where($wtempo)->find();
                    if ($tempos == false) {
                        $tempoif = $tempo->execute("INSERT INTO tempo (gkid,cardno,amount,panterid,flag) VALUES ('" . $gkid . "','" . $cardno . "','" . $amount . "','" . $ppanterid . "','0')");
                        if ($tempoif == false) {
                            $batchbuyBatchLog[$key]['status'] = 0;
                            $batchbuyBatchLog[$key]['msg'] = '新增卡号' . $cardno . '购卡记录表失败,请联系系统管理员! 原因：添加批量购卡表（tempo）错误,sql:' . $tempo->getLastSql();
                            $err = true;
                            continue;
                        } else {
                            $batchbuyBatchLog[$key]['status'] = 1;
                            $batchbuyBatchLog[$key]['msg'] = '批量购卡成功';
                        }
                    } else {
                        if ($tempos['flag'] == 2) {
                            $tempoif = $tempo->execute("UPDATE tempo SET gkid='" . $gkid . "',amount=" . $amount . ",flag='0' where cardno='" . $cardno . "'");
                            if ($tempoif == false) {
                                $batchbuyBatchLog[$key]['status'] = 0;
                                $batchbuyBatchLog[$key]['msg'] = '更新卡号' . $cardno . '购卡记录表失败,请联系系统管理员! sql:' . $tempo->getLastSql();
                                $err = true;
                                continue;
                            } else {
                                $batchbuyBatchLog[$key]['status'] = 1;
                                $batchbuyBatchLog[$key]['msg'] = '批量购卡成功';
                            }
                        } else {
                            $batchbuyBatchLog[$key]['status'] = 0;
                            $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '在tempo表已存在,请联系系统管理员! sql:' . $tempo->getLastSql();
                            $err = true;
                            continue;
                        }
                    }
                    $batchbuyBatchLog[$key]['status'] = 1;
                    $counts++;
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $nun = count($exceldate) - $counts;
                if ($err) {
                    $this->success('成功批量购卡' . $counts . '张,<br /><font color=red>其中<b>' . $nun . '</b>张异常</font>', U('Card/cardbatchbuy'), 5);
                } else {
                    $this->success('成功批量购卡' . $counts . '张', U('Card/cardbatchbuy'), 5);
                }
            }
            $this->display();
        }
    //批量插入礼品卡
    function cardinexcel(){
        set_time_limit(0);
        $panterid=$this->panterid;
        $userid =  $this->userid;
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls")
            {
                $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
//            $savePath = './public/upfile/Excel/';
            $savePath = PUBLIC_PATH.'upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name);
            // dump($exceldate);exit;
            $cards=M('cards');
            $counts=0;
            $batchbuyBatchLog=array();
            $err = false;
            $ksnum=1;
            foreach ($exceldate as $key => $value) {
                if($ksnum==300){
                    $ksnum=1;
                    sleep(1);
                }
                $ksnum++;
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                if(array_key_exists("A",$value)){
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value['A']);
                }else{
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value[0]);
                }
                $where=array();
                if($cardno!=''){
                    $where['cardno']=$cardno;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    continue;
                }
                $card=$cards->where($where)->find();
                if($card==true){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'已存在,请联系系统管理员! sql:' .$cards->getLastSql();
                    $err = true;
                    continue;
                }else{
                    $cardins = $cards->execute("INSERT INTO cards(cardno,cardkind,customid,cardpassword,exdate,status,cardfee,cardpoint,cardbalance,panterid,brandid)VALUES('".$cardno."','2336',0,'BFD0C613A11BF88E','20300101','N',1,0,0,'00000219','2336')");
                    if($cardins==false){
                        $batchbuyBatchLog[$key]['status']=0;
                        $batchbuyBatchLog[$key]['cardno']=$cardno;
                        $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'插入卡号失败,请联系系统管理员! 原因：添加批量礼品卡（cardno）错误,sql:' .$cards->getLastSql();
                        $err = true;
                        continue;
                    }else{
                        $batchbuyBatchLog[$key]['status']=1;
                        $batchbuyBatchLog[$key]['cardno']=$cardno;
                        $batchbuyBatchLog[$key]['msg']='批量插入礼品卡成功';
                    }
                }
                $batchbuyBatchLog[$key]['status']=1;
                $counts++;
            }
            if(!empty($batchbuyBatchLog)){
                $this->cardinexcelLogs($batchbuyBatchLog);
            }
            $nun = count($exceldate) - $counts;
            if ($err){
                $this->success('成功批量插入卡'.$counts.'张,<br /><font color=red>其中<b>'.$nun.'</b>张异常</font>',U('Card/cardbatchbuy'), 5);
            }else{
                $this->success('成功批量插入卡'.$counts.'张',U('Card/cardinexcel'),5);
            }
        }
        $this->display();
    }
    //批量更改卡密码
    function carduppwdexcel(){
        set_time_limit(0);
        $panterid=$this->panterid;
        $userid =  $this->userid;
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls")
            {
                $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
//            $savePath = './public/upfile/Excel/';
            $savePath = PUBLIC_PATH.'upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name);
            // dump($exceldate);exit;
            $cards=M('cards');
            $counts=0;
            $batchbuyBatchLog=array();
            $err = false;
            $ksnum=1;
            foreach ($exceldate as $key => $value) {
                if($ksnum==300){
                    $ksnum=1;
                    sleep(1);
                }
                $ksnum++;
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                if(array_key_exists("A",$value)){
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value['A']);
                    $batchRechargeLog[$key]['cardpassword']=$password=trim($value['B']);
                }else{
                    $batchRechargeLog[$key]['cardno']=$cardno=trim($value[0]);
                    $batchRechargeLog[$key]['cardpassword']=$password=trim($value[1]);
                }
                $where=array();
                if($cardno!=''){
                    $where['cardno']=$cardno;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    continue;
                }
                $card=$cards->where($where)->find();
                if($card==true){
//                    $passwords = md5($password);
                    $passwords = $this->des->doEncrypt($password);
                    $cardins = $cards->execute("update cards set cardpassword= '{$passwords}' where cardno= $cardno");
                    if($cardins == true){
                        $batchbuyBatchLog[$key]['status']=1;
                        $batchbuyBatchLog[$key]['cardno']=$cardno;
                        $batchbuyBatchLog[$key]['msg']='更新卡密码成功';
                    }else{
                        $batchbuyBatchLog[$key]['status']=0;
                        $batchbuyBatchLog[$key]['cardno']=$cardno;
                        $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'更新密码失败,请联系系统管理员!sql:' .$cards->getLastSql();
                    }
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['cardno']=$cardno;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'更新密码失败,请联系系统管理员!sql:' .$cards->getLastSql();
                }
                $batchbuyBatchLog[$key]['status']=1;
                $counts++;
            }
            if(!empty($batchbuyBatchLog)){
                $this->carduppwdLogs($batchbuyBatchLog);
            }
            $nun = count($exceldate) - $counts;
            if ($err){
                $this->success('成功更改卡密码'.$counts.'张,<br /><font color=red>其中<b>'.$nun.'</b>张异常</font>',U('Card/carduppwdexcel'), 5);
            }else{
                $this->success('成功更改卡密码'.$counts.'张',U('Card/carduppwdexcel'),5);
            }
        }
        $this->display();
    }
    //写入批量插入礼品卡日志
    function cardinexcelLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            if($val['status']==0){
                $msgString.='状态：批量插入卡号失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量插入卡号成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('cardinexcel',$msgString);
    }
    //写入批量更改卡密码日志
    function carduppwdLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            if($val['status']==0){
                $msgString.='状态：批量更新卡号密码失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：批量更新卡号密码成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('carduppwd',$msgString);
    }
        //批量购卡审核
        function batchcheck()
        {
            set_time_limit(0);
            $tempo = M('tempo');
            $panterid = $this->panterid;
            // $panterid='00000125';
            $userid = $this->userid;
            $userstr = substr($userid, 12, 4);
            $acetype = trim($_REQUEST['acetype']);
            $model = new Model();
            if ($acetype == 'check') {
                $gkid = $_POST['gkid'];
                $cards = M('cards');
                $customs = M('customs');
                $customsc = M('customs_c');
                $custompl = M('custom_purchase_logs');
                $cardpl = M('card_purchase_logs');
                $account = M('account');
                $chackstr = session('chackwhere');
                $tempo_list = $tempo->alias('t')->join('left join __PANTERS__ p on t.panterid=p.panterid')
                    ->where($chackstr)->field('t.gkid,t.panterid,t.cardno,t.amount,p.namechinese')
                    ->order('t.cardno asc')->select();
                $counts = 0;
                $ksnum = 1;
                $cardsactive = M('card_active_logs');
                foreach ($tempo_list as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchbuyBatchLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    $batchbuyBatchLog[$key]['cardno'] = $cardno = $value['cardno'];
                    $batchbuyBatchLog[$key]['amount'] = $amount = $value['amount'];
                    $ppanterid = $value['panterid'];
                    $model->startTrans();
                    //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
                    $purchaseid = $this->getFieldNextNumber('purchaseid');
                    $purchaseid = $userstr . $purchaseid;
                    //$customid  =$this->getnextcode('customs',8);
                    $customid = $this->getFieldNextNumber('customid');
                    $customif = $customs->execute("INSERT INTO customs(customid,customlevel,panterid) VALUES('" . $customid . "','一般顾客','" . $ppanterid . "')");
                    if ($customif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '购卡审核有异常,请联系系统管理员!';
                        continue;
                    }
                    $customcif = $customsc->execute("INSERT INTO customs_c(customid,cid) VALUES('" . $customid . "','" . $customid . "')");
                    if ($customif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '执行会员-卡号关联表失败!';
                        continue;
                    }

                    $nowdate = date('Ymd');
                    $nowtime = date('H:i:s');
                    $customplif = $custompl->execute("INSERT INTO custom_purchase_logs VALUES('" . $customid . "','" . $purchaseid . "','" . $nowdate . "','现金','" . $userid . "','" . $amount . "',NULL,'" . $amount . "','0','" . $amount . "','" . $amount . "',1,'0','后台充值','1','" . $ppanterid . "',NULL,NULL,'0','0',NULL,'00000000','" . $nowtime . "','" . $nowdate . "','" . $nowtime . "','" . $userid . "')");
                    if ($customplif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '执行购卡单审核失败!';
                        continue;
                    }

                    //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
                    $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
                    $userip = $_SERVER['REMOTE_ADDR'];
                    $cardplif = $cardpl->execute("INSERT INTO card_purchase_logs VALUES('" . $cardpurchaseid . "','" . $purchaseid . "','" . $cardno . "','" . $amount . "','0','" . $nowdate . "','" . $nowtime . "','1','后台充值','" . $userid . "','" . $ppanterid . "','" . $userip . "','00000000')");
                    if ($cardplif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '执行购卡充值审核失败!';
                        continue;
                    }


                    $cardsif = $cards->execute("UPDATE cards SET status='Y',customid='" . $customid . "' where cardno='" . $cardno . "'");
                    $sql = "INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}'," . date('Ymd');
                    $sql .= ",'0','Y','00'," . date('Ymd') . ",'" . date('H:i:s') . "','批量售卡激活','{$customid}','{$ppanterid}','00000000')";
                    $activeif = $cardsactive->execute($sql);
                    if ($activeif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '卡激活执行失败!';
                        continue;
                    }


                    //$acid = $this->getnextcode('account',8);
                    $acid = $this->getFieldNextNumber('accountid');
                    $acountif = $account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('" . $acid . "','" . $customid . "','" . $amount . "','00',NULL)");
                    if ($acountif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '账户充值执行失败!';
                        continue;
                    }

                    //$acoid = $this->getnextcode('account',8);
                    $acid = $this->getFieldNextNumber('accountid');
                    $acountsif = $account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('" . $acid . "','" . $customid . "','0','01',NULL)");
                    if ($acountif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '积分充值执行失败!';
                        continue;
                    }

                    $temposif = $tempo->execute("UPDATE tempo SET flag='1' where cardno='" . $cardno . "'");
                    if ($temposif == false) {
                        $model->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '批量充值审核失败!';
                        continue;
                    } else {
                        $model->commit();
                        $batchbuyBatchLog[$key]['status'] = 1;
                        $batchbuyBatchLog[$key]['msg'] = '购卡审核成功!';
                        $counts++;
                    }
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                if ($counts > 0) {
                    $this->success('成功批量审核' . $counts . '条', U('Card/batchcheck'), 5);
                } else {
                    $this->error('批量审核失败', U('Card/batchcheck'), 5);
                }
            } else if ($acetype == 'nocheck') {
                $chackstr = session('chackwhere');
                $tempo_list = $tempo->alias('t')->join('left join __PANTERS__ p on t.panterid=p.panterid')->where($chackstr)->field('t.gkid,t.panterid,t.cardno,t.amount,p.namechinese')->order('t.cardno asc')->select();
                $counts = 0;
                $batchbuyBatchLog = array();
                $ksnum = 1;
                foreach ($tempo_list as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchbuyBatchLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    $batchbuyBatchLog[$key]['cardno'] = $cardno = $value['cardno'];
                    $batchbuyBatchLog[$key]['amount'] = $amount = $value['amount'];
                    $ppanterid = $value['panterid'];
                    $tempo->startTrans();
                    $temposif = $tempo->execute("UPDATE tempo SET flag='2' where cardno='" . $cardno . "'");
                    if ($temposif == false) {
                        $tempo->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '购卡审核不通有异常,请联系系统管理员!';
                        continue;
                    } else {
                        $tempo->commit();
                        $batchbuyBatchLog[$key]['status'] = 1;
                        $batchbuyBatchLog[$key]['msg'] = '购卡审核不通过操作成功!';
                    }
                    $counts++;
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $this->success('成功批量审核不通过' . $counts . '条', U('Card/batchcheck'), 5);
            } else {
                $gkid = trim(I('get.gkid', ''));
                $cardno = trim(I('get.cardno', ''));
                $pantersid = trim(I('get.panterid', ''));
                $namechinese = trim(I('get.namechinese', ''));
                $where['t.flag'] = 0;
                if ($gkid != '') {
                    $where['t.gkid'] = $gkid;
                    $this->assign('gkid', $gkid);
                    $map['gkid'] = $gkid;
                }
                if ($pantersid != '') {
                    $where['t.panterid'] = $pantersid;
                    $this->assign('panterid', $pantersid);
                    $map['panterid'] = $pantersid;
                }
                if ($cardno != '') {
                    $where['t.cardno'] = $cardno;
                    $this->assign('cardno', $cardno);
                    $map['cardno'] = $cardno;
                }
                if ($namechinese != '') {
                    $where['p.namechinese'] = array('like', '%' . $namechinese . '%');
                    $this->assign('namechinese', $namechinese);
                    $map['namechinese'] = $namechinese;
                }
                if ($panterid != 'FFFFFFFF') {
                    $wmap['parent'] = $panterid;
                    $wmap['panterid'] = $panterid;
                    $wmap['_logic'] = 'OR';
                    $panterin = M('panters')->where($wmap)->field('panterid')->select();
                    $onearray = $this->getonearray($panterin, 'panterid');
                    $where['t.panterid'] = array('in', $onearray);
                } else {
                    $where1['_string'] = "p.hysx <> '酒店' OR p.hysx IS NULL";
                    $where['_complex'] = $where1;
                }
                $count = $tempo->alias('t')
                    ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                    ->where($where)->field('t.gkid,t.panterid,t.cardno,t.amount,p.namechinese')->count();
                $this->assign('count', $count);
                $amount_sum = $tempo->alias('t')
                    ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                    ->where($where)->field('sum(t.amount) amount_sum')->find();
                $this->assign('amount_sum', $amount_sum['amount_sum']);
                $p = new \Think\Page($count, 10);
                $tempo_list = $tempo->alias('t')
                    ->join('left join __PANTERS__ p on t.panterid=p.panterid')
                    ->where($where)->field('t.gkid,t.panterid,t.cardno,t.amount,p.namechinese')
                    ->limit($p->firstRow . ',' . $p->listRows)->order('t.cardno asc')->select();
                //echo $tempo->getLastSql();
                session('chackwhere', $where);
                if (!empty($map)) {
                    foreach ($map as $key => $val) {
                        $p->parameter[$key] = $val;
                    }
                }
                $page = $p->show();
                $this->assign('list', $tempo_list);
                $this->assign('page', $page);
                $this->display();
            }
        }

        //批量激活
        function batchactivate()
        {
            set_time_limit(0);
            $tempo = M('tempo');
            $cards = M('cards');
            $brand = M('brand');
            $userid = $this->userid;
            $panterid = $this->panterid;
            $brand_list = $brand->field('brandid,brandname')->select();
            $this->assign('brand', $brand_list);
            $statype = array('A' => '待激活', 'D' => '销卡', 'R' => '退卡', 'S' => '过期',
                'N' => '新卡', 'L' => '锁定', 'Y' => '正常卡', 'W' => '无卡', 'C' => '已出库');//卡状态
            $this->assign('statype', $statype);
            $acetype = trim($_REQUEST['acetype']);
            if ($acetype == 'active') {
                $activestr = session('activewhere');
                $field = 'a.cardno,a.panterid,a.customid as customid1,b.customid,a.status,b.namechinese,d.brandname,t.gkid';
                $cart_list = $cards->alias('a')->join('left join __BRAND__ d on d.brandid=a.brandid')
                    ->join('left join __CUSTOMS_C__ c on c.cid=a.customid')
                    ->join('left join __CUSTOMS__ b on b.customid=c.customid')
                    ->join('left join __TEMPO__ t on t.cardno=a.cardno')
                    ->join('__PANTERS__ p on p.panterid=a.panterid')
                    ->where($activestr)->field($field)->order('a.cardno asc')->select();
                $cardactive = M('card_active_logs');
                $counts = 0;
                $batchbuyBatchLog = array();
                $ksnum = 1;
                foreach ($cart_list as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchbuyBatchLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    $batchbuyBatchLog[$key]['cardno'] = $cardno = $value['cardno'];
                    $ppanterid = $value['panterid'];
                    $cards->startTrans();
                    $exd = strtotime("+3 years");
                    $cardsif = $cards->execute("UPDATE cards SET status='Y',exdate='" . date('Ymd', $exd) . "' where cardno='" . $value['cardno'] . "'");
                    $str = $cards->getLastSql();
                    $cardplif = $cardactive->execute("INSERT INTO card_active_logs VALUES('" . $value['cardno'] . "','" . $userid . "','" . date('Ymd') . "','0','Y','00','" . date('Ymd') . "','" . date('H:i:s') . "','批量激活','" . $value['customid1'] . "','" . $ppanterid . "','00000000')");
                    $str .= $cardactive->getLastSql();
                    if ($cardsif != false && $cardplif != false) {
                        $cards->commit();
                        $batchbuyBatchLog[$key]['status'] = 1;
                        $batchbuyBatchLog[$key]['msg'] = '购卡批量激活成功!';
                    } else {
                        $cards->rollback();
                        $batchbuyBatchLog[$key]['status'] = 0;
                        $batchbuyBatchLog[$key]['msg'] = '卡号' . $cardno . '购卡批量激活有异常,请联系系统管理员! sql:' . $str;;
                    }
                    $counts++;
                }
                if (!empty($batchbuyBatchLog)) {
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $this->success('成功批量激活' . $counts . '条', U('Card/batchactivate'), 5);
            }

            $startcard = trim(I('get.startcard', ''));
            $endcard = trim(I('get.endcard', ''));
            $brandid = trim(I('get.brandid', ''));
            $brandname = trim(I('get.brandname', ''));
            $cardno = trim(I('get.cardno', ''));
            $gkid = trim(I('get.gkid', ''));
            if ($gkid != '') {
                $where['t.gkid'] = $gkid;
                $this->assign('gkid', $gkid);
                $map['gkid'] = $gkid;
            }
            if ($startcard != '' && $endcard == '') {
                $where['a.cardno'] = array('EGT', $startcard);
                $this->assign('startcard', $startcard);
                $map['startcard'] = $startcard;
            }
            if ($endcard != '' && $startcard == '') {
                $where['a.cardno'] = array('ELT', $endcard);
                $this->assign('endcard', $endcard);
                $map['endcard'] = $endcard;
            }
            if ($startcard != '' && $endcard != '') {
                $where['a.cardno'] = array(array('egt', $startcard), array('elt', $endcard));
                $this->assign('startcard', $startcard);
                $this->assign('endcard', $endcard);
                $map['startcard'] = $startcard;
                $map['endcard'] = $endcard;
            }
            if ($brandid != '') {
                $where['a.brandid'] = $brandid;
                $this->assign('brandid', $brandid);
                $map['brandid'] = $brandid;
            }
            if ($brandname != '') {
                $where['d.brandname'] = array('like', '%' . $brandname . '%');
                $this->assign('brandname', $brandname);
                $map['brandname'] = $brandname;
            }
            if ($cardno != '') {
                $where['a.cardno'] = $cardno;
                $this->assign('cardno', $cardno);
                $map['cardno'] = $cardno;
            }
            $where['a.status'] = 'A';
            if ($this->panterid != 'FFFFFFFF') {
                $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
                $this->assign('is_admin', 0);
            } else {
                $where1['_string'] = "p.hysx <> '酒店' OR p.hysx IS NULL";
                $where['_complex'] = $where1;
                $this->assign('is_admin', 1);
            }
            $count = $cards->alias('a')->join('left join __BRAND__ d on d.brandid=a.brandid')
                ->join('left join __CUSTOMS_C__ c on c.cid=a.customid')
                ->join('left join __CUSTOMS__ b on b.customid=c.customid')
                ->join('left join __TEMPO__ t on t.cardno=a.cardno')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field('a.cardno,a.customid as customid1,b.customid,a.status,b.namechinese,d.brandname,t.gkid')->count();
            $p = new \Think\Page($count, 15);
            $cart_list = $cards->alias('a')->join('left join __BRAND__ d on d.brandid=a.brandid')
                ->join('left join __CUSTOMS_C__ c on c.cid=a.customid')
                ->join('left join __CUSTOMS__ b on b.customid=c.customid')
                ->join('left join __TEMPO__ t on t.cardno=a.cardno')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field('a.cardno,a.customid as customid1,b.customid,a.status,b.namechinese,d.brandname,t.gkid')
                ->limit($p->firstRow . ',' . $p->listRows)->order('a.cardno asc')->select();
            session('activewhere', $where);
            if (!empty($map)) {
                foreach ($map as $key => $val) {
                    $p->parameter[$key] = $val;
                }
            }
            $page = $p->show();
            $this->assign('list', $cart_list);
            $this->assign('page', $page);
            $this->display();
        }

        //通宝卡激活
        function tbcardactivate()
        {
            set_time_limit(0);
            $tempo = M('tempo');
            $cards = M('cards');
            $brand = M('brand');
            $userid = $this->userid;
            $panterid = $this->panterid;
            $brand_list = $brand->field('brandid,brandname')->select();
            $this->assign('brand', $brand_list);
            $statype = array('A' => '待激活', 'D' => '销卡', 'R' => '退卡', 'S' => '过期',
                'N' => '新卡', 'L' => '锁定', 'Y' => '正常卡', 'W' => '无卡', 'C' => '已出库');//卡状态
            $this->assign('statype', $statype);
            $acetype = trim($_REQUEST['acetype']);
            $cardno = trim(I('get.cardno', ''));
            $cname = trim(I('get.cname', ''));
            $idcard = trim(I('get.idcard', ''));
            $telphone = trim(I('get.telphone', ''));

            if ($cname != '') {
                $where['b.namechinese'] = $cname;
                $this->assign('cname', $cname);
                $map['cname'] = $cname;
            }
            if ($idcard != '') {
                $where['b.personid'] = $idcard;
                $this->assign('idcard', $idcard);
                $map['idcard'] = $idcard;
            }
            if ($telphone != '') {
                $where['b.linktel'] = $telphone;
                $this->assign('telphone', $telphone);
                $map['telphone'] = $telphone;
            }
            if ($cardno != '') {
                $where['a.cardno'] = $cardno;
                $this->assign('cardno', $cardno);
                $map['cardno'] = $cardno;
            }
            $where['a.status'] = 'A';
            $where['a.brandid'] = '6889';
            $where['a.cardfee'] = 1;
            if ($this->panterid != 'FFFFFFFF') {
                $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
                $this->assign('is_admin', 0);
            } else {
                $this->assign('is_admin', 1);
            }
            $count = $cards->alias('a')->join('left join __BRAND__ d on d.brandid=a.brandid')
                ->join('left join __CUSTOMS_C__ c on c.cid=a.customid')
                ->join('left join __CUSTOMS__ b on b.customid=c.customid')
                ->join('left join __TEMPO__ t on t.cardno=a.cardno')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field('a.cardno,a.customid as customid1,b.customid,a.status,b.namechinese,d.brandname,t.gkid')->count();
            $p = new \Think\Page($count, 15);
            $cart_list = $cards->alias('a')->join('left join __BRAND__ d on d.brandid=a.brandid')
                ->join('left join __CUSTOMS_C__ c on c.cid=a.customid')
                ->join('left join __CUSTOMS__ b on b.customid=c.customid')
                ->join('left join __TEMPO__ t on t.cardno=a.cardno')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field('a.cardno,a.customid as customid1,b.customid,b.linktel,b.personid,p.namechinese as pname,a.status,b.namechinese,d.brandname,t.gkid')
                ->limit($p->firstRow . ',' . $p->listRows)->order('a.cardno asc')->select();
            session('activewhere', $where);
            if (!empty($map)) {
                foreach ($map as $key => $val) {
                    $p->parameter[$key] = $val;
                }
            }
            $page = $p->show();
            $this->assign('list', $cart_list);
            $this->assign('page', $page);
            $this->display();
        }

        function tbcardact()
        {
            $cards = M('cards');
            $cardno = trim($_REQUEST['cardno']);
            $userid = $this->userid;
            $cardInfo = $cards->where(array('cardno' => $cardno))->find();
            if ($cardInfo == false) {
                $this->error('至尊卡不存在', U('Card/tbcardactivate'));
            }
            if ($cardInfo['status'] != 'A') {
                $this->error('该卡非待激活卡', U('Card/tbcardactivate'));
            }
            $cards->startTrans();
            $cardAlSql = "INSERT INTO card_active_logs VALUES('{$cardno}','{$userid}'," . date('Ymd');
            $cardAlSql .= ",'0','Y','00'," . date('Ymd') . ",'" . date('H:i:s') . "','客服激活','{$cardInfo['customid']}'";
            $cardAlSql .= ",'{$cardInfo['panterid']}','00000000')";
            $cardAlIf = $cards->execute($cardAlSql);

            $cardSql = "UPDATE cards SET status='Y' where cardno='" . $cardno . "'";
            $cardIf = $cards->execute($cardSql);
            if ($cardAlIf == true && $cardIf == true) {
                $cards->commit();
                $this->success('激活成功', U('Card/tbcardactivate'));
            } else {
                $cards->rollback();
                $this->error('激活失败', U('Card/tbcardactivate'));
            }
        }

        //批量充值登记
        function batchRecharge()
        {
            set_time_limit(0);
            $panterid = $this->panterid;
            $userid = $this->userid;
            $userstr = substr($userid, 12, 4);
            $model = new Model();
            if (IS_POST) {
                if (empty($_FILES['file_stu']['name'])) {
                    $this->error('请上传卡号文件', U('Card/batchRecharge'), 5);
                }
                $upload = $this->_upload('excel');
                if (is_string($upload)) {
                    $this->error($upload, U('Card/batchRecharge'), 5);
                } else {
                    $filename = PUBLIC_PATH . $upload['file_stu']['savepath'] . $upload['file_stu']['savename'];
                    $exceldate = $this->import_excel($filename);
                    $cards = M('cards');
                    $batchRechargeLog = array();
                    $custompl = M('custom_purchase_logs');
                    $count = 0;
                    $ksnum = 1;
                    foreach ($exceldate as $key => $value) {
                        if ($ksnum == 300) {
                            $ksnum = 1;
                            sleep(1);
                        }
                        $ksnum++;
                        $batchRechargeLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                        if (array_key_exists("A", $value)) {
                            $batchRechargeLog[$key]['cardno'] = $cardno = $value['A'];
                            $batchRechargeLog[$key]['amount'] = $amount = $value['B'];
                        } else {
                            $batchRechargeLog[$key]['cardno'] = $cardno = $value[0];
                            $batchRechargeLog[$key]['amount'] = $amount = $value[1];
                        }
                        if ($panterid != 'FFFFFFFF') {
                            $where['panterid'] = $panterid;
                        } else {
                            if ($amount > 5000) {
                                $batchbuyBatchLog[$key]['status'] = 0;
                                $batchbuyBatchLog[$key]['msg'] = '充值金额不能大于5000元，请核实!';
                                continue;
                            }
                        }
                        if ($cardno != '') {
                            $where['cardno'] = $cardno;
                        } else {
                            $batchRechargeLog[$key]['status'] = 0;
                            $batchRechargeLog[$key]['msg'] = '卡号不能为空，请核实!';
                            continue;
                        }
                        $where['status'] = 'Y';
                        $card = $cards->where($where)->find();
                        if ($card == false) {
                            $batchRechargeLog[$key]['status'] = 0;
                            $batchRechargeLog[$key]['msg'] = '非新卡或者无效卡，请核实!';
                            continue;
                        }
                        $customid = $card['customid'];
                        $ppanterid = $card['panterid'];
                        //$purchaseid=$this->getnextcode('PurchaseId ',12);//获得PurchaseId 12位编号
                        $purchaseid = $this->getFieldNextNumber('purchaseid');
                        $purchaseid = $userstr . $purchaseid;
                        $nowdate = date('Ymd');
                        $nowtime = date('H:i:s');
                        $model->startTrans();
                        $customplif = $custompl->execute("INSERT INTO custom_purchase_logs VALUES('" . $customid . "','" . $purchaseid . "','" . $nowdate . "','现金','" . $userid . "','" . $amount . "',NULL,'" . $amount . "','0','" . $amount . "','" . $amount . "',1,'0','批量充值_" . $cardno . "','0','" . $ppanterid . "',NULL,NULL,'1','0',NULL,'00000000','" . $nowtime . "','" . $nowdate . "','" . $nowtime . "','" . $userid . "')");
                        if ($customplif == false) {
                            $model->rollback();
                            $batchRechargeLog[$key]['status'] = 1;
                            $batchRechargeLog[$key]['msg'] = '批量充值申请成功';
                            continue;
                        } else {
                            $model->commit();
                            $batchRechargeLog[$key]['status'] = 1;
                            $batchRechargeLog[$key]['msg'] = '批量充值申请成功';
                            $count++;
                        }
                    }
                    if (!empty($batchRechargeLog)) {
                        $this->batchRechargeLogs($batchRechargeLog);
                    }
                    $this->success('成功批量充值登记' . $count . '条', U('Card/batchRecharge'), 5);
                }
            }
            $this->display();
        }

        //批量充值审核
        function batchRechargeCheck()
        {
            set_time_limit(0);
            $userid = $this->userid;
            $panterid = $this->panterid;
            $userip = $_SERVER['REMOTE_ADDR'];
            $acetype = trim($_REQUEST['acetype']);
            $gctype = array('0' => '购卡单', '1' => '充值单');
            $this->assign('gctype', $gctype);
            $chetype = array('0' => '未审核', '1' => '审核通过', '2' => '审核未通过');//审核状态
            $this->assign('chetype', $chetype);
            $custompl = M('custom_purchase_logs');
            $cardpl = M('card_purchase_logs');
            $model = new Model();
            if ($acetype == 'check') {
                $checkstr = session('checkwhere');
                $customplist = $custompl->alias('a')->join('left join customs c on c.customid=a.customid')
                    ->join('__PANTERS__ p on a.panterid=p.panterid')
                    ->where($checkstr)->field('a.*,c.namechinese')->order('a.placeddate asc')->select();
                $auditlogs = M('audit_logs');
                $cards = M('cards');
                $account = M('account');
                $counts = 0;
                $ksnum = 1;
                foreach ($customplist as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchRechargeLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    $cardno = $this->subposstr($value['description'], '_');
                    $batchRechargeLog[$key]['cardno'] = $cardno;
                    $batchRechargeLog[$key]['amount'] = $amount = $value['amount'];
                    if ($panterid != 'FFFFFFFF') {
                        $where['panterid'] = $panterid;
                    }
                    if ($cardno != '') {
                        $where['cardno'] = $cardno;
                    } else {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '卡号不能为空';
                        continue;
                    }
                    $where['status'] = 'Y';
                    $card = $cards->where($where)->find();
                    if ($card == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '非法卡或者非部门卡，请核实';
                        continue;
                    }
                    $model->startTrans();
                    $czamount = $card['cardbalance'] + $value['amount'];
                    $ppanterid = $card['panterid'];
                    //$auditid=$this->getnextcode('audit_logs',16);
                    $auditid = $this->getFieldNextNumber('auditid');
                    $auditlogsif = $auditlogs->execute("insert into audit_logs values ('" . $auditid . "','" . $value['purchaseid'] . "','审核通过','批量充值审核','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s') . "')");
                    if ($auditlogsif == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '执行审核失败';
                        $model->rollback();
                        continue;
                    }
                    $customplSql = "update custom_purchase_logs set flag='1',checkdate='" . date('Ymd');
                    $customplSql .= "',checktime='" . date('H:i:s') . "',aditid='" . $userid;
                    $customplSql .= "',Writenumber=Writenumber+1, writeamount=writeamount+'" . $value['amount'];
                    $customplSql .= "' where purchaseId='" . $value['purchaseid'] . "'";
                    $customplIf = $custompl->execute($customplSql);
                    if ($customplIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '更新购卡单失败';
                        $model->rollback();
                        continue;
                    }

                    //$cardpurchaseid=$this->getnextcode('card_purchase_logs',18);
                    $cardpurchaseid = $this->getFieldNextNumber('cardpurchaseid');
                    $cardIf = $cards->execute("update cards set CardBalance='" . $czamount . "' where cardno='" . $cardno . "'");
                    if ($cardIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '卡片信息更新失败';
                        $model->rollback();
                        continue;
                    }
                    //echo $cardno;exit;
                    $cardplIf = $cardpl->execute("insert into card_purchase_logs values('" . $cardpurchaseid . "','" . $value['purchaseid'] . "','" . $cardno . "','" . $amount . "',0,'" . date('Ymd') . "','" . date('H:i:s') . "','1','批量充值审核','" . $userid . "','" . $ppanterid . "','" . $userip . "','00000000')");
                    if ($cardplIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '购卡记录执行失败';
                        $model->rollback();
                        continue;
                    }

                    $balanceIf = $account->execute("update account set amount=nvl(amount,0)+'" . $value['amount'] . "' where customid='" . $value['customid'] . "' and type='00'");
                    if ($balanceIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '资金账户更新失败';
                        $model->rollback();
                        continue;
                    }

                    $pointIf = $account->execute("update account set amount=nvl(amount,0)+'" . $value['card_num'] . "' where customid='" . $value['customid'] . "' and type='01'");

                    if ($pointIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '积分账户更新失败';
                        $model->rollback();
                        continue;
                    } else {
                        $batchRechargeLog[$key]['status'] = 1;
                        $batchRechargeLog[$key]['msg'] = '审核成功';
                        $model->commit();
                        $counts++;
                    }
                }
                if (!empty($batchRechargeLog)) {
                    $this->batchRechargeLogs($batchRechargeLog);
                }
                $this->success('成功批量充值审核' . $counts . '条', U('Card/batchRechargecheck'), 5);
            } else if ($acetype == 'nocheck') {
                $checkstr = session('checkwhere');
                $customplist = $custompl->alias('a')->join('left join customs c on c.customid=a.customid')
                    ->join('__PANTERS__ p on a.panterid=p.panterid')->where($checkstr)
                    ->field('a.*,c.namechinese')->order('a.placeddate asc')->select();
                $auditlogs = M('audit_logs');
                $cards = M('cards');
                $account = M('account');
                $counts = 0;
                $ksnum = 1;
                foreach ($customplist as $key => $value) {
                    if ($ksnum == 300) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $ksnum++;
                    $batchRechargeLog[$key]['datetime'] = date('Y-m-d H:i:s', time());
                    $cardno = $this->subposstr($value['description'], '_');
                    $batchRechargeLog[$key]['cardno'] = $cardno;
                    $batchRechargeLog[$key]['amount'] = $amount = $value['amount'];
                    $model->startTrans();
                    if ($value['flag'] != '0') {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = $cardno . '卡号登记单号已经审核通过不允许再审核!' . $value['description'];
                        continue;
                    }
                    if ($panterid != 'FFFFFFFF') {
                        $where['panterid'] = $panterid;
                    }
                    if ($cardno != '') {
                        $where['cardno'] = $cardno;
                    } else {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = $cardno . '卡号不能为空，请核实!' . $value['description'];
                        continue;
                    }
                    $where['status'] = 'Y';
                    $card = $cards->where($where)->find();
                    if ($card == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '非法卡或者非部门卡';
                        continue;
                    }
                    $czamount = $card['cardbalance'] + $value['amount'];
                    $ppanterid = $card['panterid'];
                    //$auditid=$this->getnextcode('audit_logs',16);
                    $auditid = $this->getFieldNextNumber('auditid');
                    $auditlogsif = $auditlogs->execute("insert into audit_logs values ('" . $auditid . "','" . $value['purchaseid'] . "','审核不通过','批量充值审核','" . date('Ymd') . "','" . $userid . "','" . date('H:i:s') . "')");
                    if ($auditlogsif == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '审核执行失败';
                        $model->rollback();
                        continue;
                    }
                    $customplIf = $custompl->execute("update custom_purchase_logs set flag='2',checkdate='" . date('Ymd') . "',checktime='" . date('H:i:s') . "',aditid='" . $userid . "' where purchaseId='" . $value['purchaseid'] . "'");
                    if ($customplIf == false) {
                        $batchRechargeLog[$key]['status'] = 0;
                        $batchRechargeLog[$key]['msg'] = '更新购卡单失败';
                        $model->rollback();
                        continue;
                    }
                    $model->commit();
                    $counts++;
                }
                if (!empty($batchRechargeLog)) {
                    $this->batchRechargeLogs($batchRechargeLog);
                }
                $this->success('成功批量审核不通过' . $counts . '条', U('Card/batchRechargecheck'), 5);
            } else {
                $start = trim(I('get.startdate', ''));
                $end = trim(I('get.enddate', ''));
                $purchaseid = trim(I('get.purchaseid', ''));
                $flag = trim(I('get.flag', ''));
                $customid = trim(I('get.customid', ''));
                $cname = trim(I('get.cname', ''));
                $description = trim(I('get.description', ''));
                $where = '';
                if (empty($flag)) $flag = 0;
                if ($start != '' && $end == '') {
                    $startdate = str_replace('-', '', $start);
                    $where['a.placeddate'] = array('egt', $startdate);
                    $this->assign('startdate', $start);
                    $map['startdate'] = $start;
                }
                if ($start == '' && $end != '') {
                    $enddate = str_replace('-', '', $end);
                    $where['a.placeddate'] = array('elt', $enddate);
                    $this->assign('enddate', $end);
                    $map['enddate'] = $end;
                }
                if ($start != '' && $end != '') {
                    $startdate = str_replace('-', '', $start);
                    $enddate = str_replace('-', '', $end);
                    $where['a.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                    $this->assign('startdate', $start);
                    $this->assign('enddate', $end);
                    $map['startdate'] = $start;
                    $map['enddate'] = $end;
                }
                if ($start == '' && $end == '') {
                    $start = date('Y-m-d');
                    $end = date('Y-m-d');
                    $startdate = str_replace('-', '', $start);
                    $enddate = str_replace('-', '', $end);
                    $where['a.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
                    $this->assign('startdate', $start);
                    $this->assign('enddate', $end);
                    $map['startdate'] = $start;
                    $map['enddate'] = $end;
                }
                if ($purchaseid != '') {
                    $where['a.purchaseid'] = $purchaseid;
                    $this->assign('purchaseid', $purchaseid);
                    $map['purchaseid'] = $purchaseid;
                }
                if ($flag !== false) {
                    $where['a.flag'] = $flag;
                    $this->assign('flag', $flag);
                    $map['flag'] = $flag;
                }
                if ($customid != '') {
                    $where['a.customid'] = $customid;
                    $this->assign('customid', $customid);
                    $map['customid'] = $customid;
                }
                if ($cname != '') {
                    $where['c.namechinese'] = $cname;
                    $this->assign('cname', $cname);
                    $map['cname'] = $cname;
                }
                if ($description != '') {
                    $where['a.description'] = array('like', '%' . $description . '%');
                    $this->assign('description', $description);
                    $map['description'] = $description;
                }
                $where['a.tradeflag'] = '1';
                if ($this->panterid != 'FFFFFFFF') {
                    $where['_string'] = "p.panterid='" . $this->panterid . "' OR p.parent='" . $this->panterid . "'";
                } else {
                    $where1['_string'] = "p.hysx <> '酒店' OR p.hysx IS NULL";
                    $where['_complex'] = $where1;
                }
                $count = $custompl->alias('a')->join('left join customs c on c.customid=a.customid')
                    ->join('__PANTERS__ p on a.panterid=p.panterid')
                    ->where($where)->field('a.*,c.namechinese')->count();
                $this->assign('count', $count);
                $amount_sum = $custompl->alias('a')->join('left join customs c on c.customid=a.customid')
                    ->join('__PANTERS__ p on a.panterid=p.panterid')
                    ->where($where)->field("sum(a.amount) amount_sum,sum(a.totalmoney) totalmoney_sum,sum(a.realamount) realamount_sum")->find();

                $this->assign('amount_sum', $amount_sum['amount_sum']);
                $this->assign('totalmoney_sum', $amount_sum['totalmoney_sum']);
                $this->assign('realamount_sum', $amount_sum['realamount_sum']);
                $p = new \Think\Page($count, 10);
                $customp_list = $custompl->alias('a')->join('left join customs c on c.customid=a.customid')
                    ->join('__PANTERS__ p on a.panterid=p.panterid')
                    ->where($where)->field('a.*,c.namechinese')
                    ->limit($p->firstRow . ',' . $p->listRows)->order('a.placeddate asc')->select();
                //echo $custompl->getLastSql();

                session('checkwhere', $where);
                if (!empty($map)) {
                    foreach ($map as $key => $val) {
                        $p->parameter[$key] = $val;
                    }
                }
                $page = $p->show();
                $this->assign('list', $customp_list);
                $this->assign('page', $page);
                $this->display();
            }
        }

        //写入批量申请购卡日志
        function batchbuyLogs($array)
        {
            $msgString = '';
            foreach ($array as $key => $val) {
                $msgString .= '卡号：' . $val['cardno'] . "  ";
                $msgString .= '充值金额：' . $val['amount'] . "  ";
                if ($val['status'] == 0) {
                    $msgString .= '状态：批量购卡申请失败' . "  ";
                    $msgString .= '原因：' . $val['msg'] . "  ";
                } elseif ($val['status'] == 1) {
                    $msgString .= '状态：批量购卡申请成功' . "  ";
                }
                $msgString .= '时间：' . $val['datetime'] . "  ";
                $msgString .= "\r\n\r\n";
            }
            $this->writeLogs('cardbatchbuy', $msgString);
        }

        //写入批量充值申请扣款日志
        function batchRechargeLogs($array)
        {
            $msgString = '';
            foreach ($array as $key => $val) {
                $msgString .= '卡号：' . $val['cardno'] . "  ";
                $msgString .= '充值金额：' . $val['amount'] . "  ";
                if ($val['status'] == 0) {
                    $msgString .= '状态：批量充值申请失败' . "  ";
                    $msgString .= '原因：' . $val['msg'] . "  ";
                } elseif ($val['status'] == 1) {
                    $msgString .= '状态：批量充值申请成功' . "  ";
                }
                $msgString .= '时间：' . $val['datetime'] . "  ";
                $msgString .= "\r\n\r\n";
            }
            $this->writeLogs('batchRecharge', $msgString);
        }
    //销售礼品卡
    public function giftCards(){
        $panterid='00000219';
        $paytype=array('00'=>'现金','01'=>'银行卡','02'=>'支票','03'=>'汇款','04'=>'网上支付','05'=>'转账','06'=>'内部转账','07'=>'赠送','08'=>'其他','09'=>'批量充值');
        $this->assign('paytype',$paytype);
        if(IS_POST){
            $cardno = trim($_POST['cardno']);
            $namechinese = trim($_POST['namechinese']);
            $residaddress= trim($_POST['residaddress']);
            $totalmoney = trim($_POST['totalmoney']);
            $paymenttype=trim($_POST['paymenttype']);
            if(empty($cardno)){
                $this->error('卡号不能为空');
            }
            if(empty($namechinese)){
                $this->error('用户名不能为空');
            }
            if(empty($residaddress)){
                $this->error('住址不能为空');
            }
            if(empty($totalmoney) || $totalmoney<=0){
                $this->error('充值金额不能少于0');
            }
            $cards = M('cards');
            $card = $cards->where(array('cardno'=>$cardno))->find();
            if($card==false){
                $this->error('此卡不存在');
            }
            if(!strstr($cardno,"2336")){
                $this->error('此卡不能用于礼品卡');
            }
            if($card['status']="N"){
                $currentDate = date('Ymd',time());
                if($card['exdate'] < $currentDate){
                    $this->error('此卡已过期');
                }
                if($card['cardfee']!='1'){
                    $this->error('此卡非实体卡');
                }
                //绑定会员信息
                $cardNum  =  $this->getCardNum($totalmoney);
                if ($cardNum >1){
                    $cardArr  = $this->getCardNo( $cardNum - 1 , $panterid);
                    if (!$cardArr)
//                        returnMsg(array('status'=>'05','codemsg'=>'卡池数量不足'));
                        $this->error('卡池数量不足');
                }
                $pkind='412111';
                $adate=date('Ymdhi',time());
                $personid=$pkind.$adate;
                $link='1818';
                $bdate=date('md',time());
                for($i=0;$i<=998;$i++){
                    $x=$i+1;
                    $snum=strlen($x);
                    if($snum==1){
                        $num='00'.$x;
                        $linkt=$link.$bdate.$num;
                    }elseif($snum==2){
                        $num='0'.$x;
                        $linkt=$link.$bdate.$num;
                    }else{
                        $linkt=$link.$bdate.$x;
                    }
                    $custom = M('customs');
                    $tel = $custom->where(array('linktel'=>$linkt))->field('customid')->find();
                    if($tel){
                        continue;
                    }else{
                        $telq[]=$linkt;
                    }
                }
                $linktel=$telq[0];
                $cardArr[]= $cardno;
                $this->model->startTrans();
                $customid = $this->getFieldNextNumber("customid");
                $customs=M('customs');
                $bingSql = "insert into customs(customid,namechinese,personid,linktel,residaddress,placeddate,aplysrc)";
                $bingSql .= "values('".$customid."','".$namechinese."','".$personid."','".$linktel."','".$residaddress."','".$currentDate."','J')";
                $customsif  =$customs->execute($bingSql);
                if($customsif== true){
                    $userid = $this->userid;
                    $n = $this->openCard($cardArr,$customid,$totalmoney,$panterid,$userid,$isBill=null,$paymenttype);
                    if($n==true){
                        $this->model->commit();
                        $this->success('发卡成功');
                    }else{
                        $this->model->rollback();
                        $this->error('发卡失败');
                    }
                }else{
                    $this->model->rollback();
                    $this->error('创建会员失败');

                }

            }else{
                $this->error('此卡非正常卡');
            }

        }
        $this->display();
    }
    //读取卡数量
    public function getCardNum($amount){
        return ceil($amount/5000);
    }
    //从卡池里去卡 如果取卡高峰期，则可能导致重复取卡，在通用场合不合适 by eslamu
    public function getCardNo($num,$panterid,$cardkind='2336'){
        if($panterid==false){
            return false;
        }
        $where = array('panterid'=>$panterid,'status'=>'N');
        $where['_string']=" cardfee is null or cardfee='0'";
        $where['cardkind']=$cardkind;
        $where['makecardid']='00000580';//老卡制卡专用 工具1000张 00000557 00000580
        $cards = M('cards');
        $cardList = $cards->where($where)->field('cardno')->limit(0,$num)->select();
//        echo $cards->getLastSql();exit;
//        dump($cardList);exit;
        if(count($cardList)<$num){
            return false;
        }else{
            $list=$this->serializeArr($cardList,'cardno');
//            dump($list);exit;
            return $list;
        }
    }
    //将二维数组转化成一维
    protected function serializeArr($array,$key){
        $list=array();
        foreach($array as $k=>$v){
            $list[]=$v[$key];
        }
        return $list;
    }
    //开卡执行
    protected function openCard($cardArr,$customid,$amount=0,$panterid=null,$userid,$isBill=null,$paymenttype='现金'){
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
            $cards = M('cards');
            $card=$cards->where($where1)->find();
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

            //更新卡状态为正常卡，更新卡有效期；
            $exd=date('Ymd',strtotime("+3 years"));
            $cardSql="UPDATE cards SET status='Y',customid='{$cardId}',cardbalance='{$rechargeMoney}',exdate='{$exd}' where cardno='".$cardno."'";
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
            return $purchaseArr;
        }else{
            return false;
        }
    }
    //判断是否是2336卡段
    public function checkCardBrand($cardno){
        $brandid=substr($cardno,0,4);
        if($brandid!='2336'){
            return false;
        }else{
            return true;
        }
    }

    
}
