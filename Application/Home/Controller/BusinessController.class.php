<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class BusinessController extends CommonController {
    //商户日消费报表
    public function consumeRep(){
       $model=new Model();
       $start = trim(I('get.startdate',''));//开始时间
       $end = trim(I('get.enddate',''));//结束时间
       $customid=trim(I('get.customid',''));//会员名称
       $cuname = trim(I('get.cuname',''));//会员名称
       $customid = $customid=="会员编号"?"":$customid;
       $cuname = $cuname=="会员名称"?"":$cuname;
       if($start!='' && $end==''){
           $startdate = str_replace('-','',$start);
           $where['tw.placeddate']=array('egt',$startdate);
           $this->assign('startdate',$start);
           $map['startdate']=$start;
       }
       if($start=='' && $end!=''){
           $enddate = str_replace('-','',$end);
           $where['tw.placeddate'] = array('elt',$enddate);
           $this->assign('enddate',$end);
           $map['enddate']=$end;
       }
       if($start!='' && $end!=''){
           $startdate = str_replace('-','',$start);
           $enddate = str_replace('-','',$end);
           $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
           $this->assign('startdate',$start);
           $this->assign('enddate',$end);
           $map['startdate']=$start;
           $map['enddate']=$end;
       }
       if($start=='' && $end==''){
           $startdate=date('Ym01',strtotime(date('Ymd')));
           $enddate=date('Ymd',time());
           $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
           $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
           $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
       }
       if(!empty($customid)){
           $where['cu.customid']=$customid;
           $this->assign('customid',$customid);
           $map['customid']=$customid;
       }
       if(!empty($cuname)){
           $where['cu.namechinese']=array('like','%'.$cuname.'%');
           $this->assign('cuname',$cuname);
           $map['cuname']=$cuname;
       }
       $where['tw.tradeamount']=array('neq',0);
       $where['tw.tradetype']='13';
       $where['tw.flag']='0';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
       $field='cu.namechinese cuname,cu.customid,';
       $field.="p.panterid pid,p.namechinese pname,";
       $field.="nvl(count(tw.tradeid),0) total_count,nvl(sum(tw.tradeamount),0) tradeamount";
       $count=$model->table('trade_wastebooks')->alias('tw')
           ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
           ->join('__CUSTOMS__ cu on cc.customid=cu.customid')
           ->join('__PANTERS__ p on p.panterid=tw.panterid')
           ->group('cu.customid,cu.namechinese,p.panterid,p.namechinese')
           ->where($where)->field($field)->select();
       $count=count($count);
       $p=new \Think\Page($count, 12 );
       $consume_list=$model->table('trade_wastebooks')->alias('tw')
           ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
           ->join('__CUSTOMS__ cu on cc.customid=cu.customid')
           ->join('__PANTERS__ p on p.panterid=tw.panterid')
           ->where($where)->field($field)->group('cu.customid,cu.namechinese,p.panterid,p.namechinese')
           ->limit($p->firstRow.','.$p->listRows)->select();
       if(!empty($map)){
           foreach($map as $key=>$val) {
               $p->parameter[$key]= $val;
           }
       }
       session('consumeRep_excel',$where);
       $page = $p->show ();
       $this->assign('list',$consume_list);
       $this->assign('page',$page);
       $this->display();
   }
    //商户日消费报表导出
    public function consumeRep_excel(){
        $model=new Model();
        $field='cu.namechinese cuname,cu.customid,';
        $field.="p.panterid pid,p.namechinese pname,";
        $field.="nvl(count(tw.tradeid),0) total_count,nvl(sum(tw.tradeamount),0) tradeamount";
        $consumeRep_excel=session('consumeRep_excel');
        foreach ($consumeRep_excel as $key => $value) {
            $where[$key]=$value;
        }
        $consume_list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cc.customid=cu.customid')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->where($where)->field($field)->group('cu.customid,cu.namechinese,p.panterid,p.namechinese')
            ->select();
        $strlist="会员编号,会员名称,商户编号,商户名称,消费次数,消费总额,客单价\n";
        $tradeamounts=0;
        $total_counts=0;
        if($consume_list!=false){
            foreach($consume_list as $key=>$val){
                $strlist.=$val['customid']."\t,".$val['cuname'].','.$val['pid']."\t,".$val['pname'].','.$val['total_count'].','.floatval($val['tradeamount'])."\t,".round(floatval($val['tradeamount'])/$val['total_count'],2)."\t\n";
                $tradeamounts+=$val['tradeamount'];
                $total_counts+=$val['total_count'];
                /*$consumelist[$key]['customid']=$val['customid'];
                $consumelist[$key]['cuname']=$val['cuname'];
                $consumelist[$key]['pid']=$val['pid'];
                $consumelist[$key]['pname']=$val['pname'];
                $consumelist[$key]['total_count']=$val['total_count'];
                $consumelist[$key]['tradeamount']=floatval($val['tradeamount']);
                $consumelist[$key]['price']=round(floatval($val['tradeamount'])/$val['total_count'],2);*/
            }
            //array_unshift($consumelist,array('会员编号','会员名称','商户编号','商户名称','消费次数','消费总额','客单价'));
            $filename='日商户消费报表'.date("YmdHis");
            $filename = iconv("utf-8","gbk",$filename);
            //$this->load_excel($consumelist,$filename,array('A','C'),array('E','F'));
            unset($consume_list);
            $strlist.=',,,,'.$total_counts."\t,".$tradeamounts."\t,\n";
            $this->load_csv($strlist,$filename);
        }else{
            header("Content-type: text/html; charset=utf-8");
            echo '<script type="text/javascript">alert("无记录");window.close();</script>';
        }
    }
    //积分兑换报表
    public function pointExchange(){
        $model=new Model();
        $start = trim(I('get.startdate',''));//开始时间
        $end = trim(I('get.enddate',''));//结束时间
        $customid=trim(I('get.customid',''));//会员名称
        $cuname = trim(I('get.cuname',''));//会员名称
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = $customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $cardno = $cardno=="卡号"?"":$cardno;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['gt.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['gt.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['gt.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['gt.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($customid)){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if(!empty($cuname)){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if(!empty($cardno)){
            $where['gt.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }


        $where['gt.customname']=array('neq','1');
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $field='gt.tradeid,gt.placeddate,gt.placedtime,gt.cardno,gt.goodamount,cu.namechinese cuname,';
        $field.='gt.tradeamount,g.goodsname,g.price,p.namechinese pname,cc.customid';

        $count=$model->table('goodstrade')->alias('gt')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__CUSTOMS_C__ cc on cc.cid=gt.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__CARDS__ c on gt.cardno=c.cardno')
            ->join('__PANTERS__ p on c.panterid=p.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12 );

        $exchange_list=$model->table('goodstrade')->alias('gt')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__CUSTOMS_C__ cc on cc.cid=gt.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__CARDS__ c on gt.cardno=c.cardno')
            ->join('__PANTERS__ p on c.panterid=p.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
            //分页跳转的时候保证查询条件
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        session('pointExchange_excel',$where);
        $page = $p->show ();
        $this->assign('page',$page);
        $this->assign('list',$exchange_list);
        $this->display();
    }
    //积分兑换报表导出
    public function pointExchange_excel(){
        $model=new Model();
        $pointExchange_excel=session('pointExchange_excel');
        foreach ($pointExchange_excel as $key => $value) {
            $where[$key]=$value;
        }
        $field='gt.tradeid,gt.placeddate,gt.placedtime,gt.cardno,gt.goodamount,cu.namechinese cuname,';
        $field.='gt.tradeamount,g.goodsname,g.price,p.namechinese pname,cc.customid';
        $exchange_list=$model->table('goodstrade')->alias('gt')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__CUSTOMS_C__ cc on cc.cid=gt.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__CARDS__ c on gt.cardno=c.cardno')
            ->join('__PANTERS__ p on c.panterid=p.panterid')
            ->where($where)->field($field)->select();
        $strlist="兑换流水号,兑换时间,兑换机构,卡号,会员编号,会员名称,兑换商品,单价,兑换数量,兑换总积分\n";
        $goodamounts=0;
        $tradeamounts=0;
        foreach($exchange_list as $key=>$val){
            $strlist.=$val['tradeid']."\t,".date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']))."\t,".$val['pname'].','.$val['cardno']."\t,".$val['customid']."\t,".$val['cuname'].','.$val['goodsname'].','.$val['price'].','.$val['goodamount']."\t,".$val['tradeamount']."\t\n";
            $tradeamounts+=$val['tradeamount'];
            $goodamounts+=$val['goodamount'];
            /*$exchangeList[$key]['tradeid']=$val['tradeid'];
            $exchangeList[$key]['time']=date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $exchangeList[$key]['pname']=$val['pname'];
            $exchangeList[$key]['cardno']=$val['cardno'];
            $exchangeList[$key]['customid']=$val['customid'];
            $exchangeList[$key]['cuname']=$val['cuname'];
            $exchangeList[$key]['goodsname']=$val['goodsname'];
            $exchangeList[$key]['price']=$val['price'];
            $exchangeList[$key]['goodamount']=$val['goodamount'];
            $exchangeList[$key]['tradeamount']=$val['tradeamount'];*/
        }
        //array_unshift($exchangeList,array('兑换流水号','兑换时间','兑换机构','卡号','会员编号','会员名称','兑换商品','单价','兑换数量','兑换总积分'));
        $filename='积分兑换报表'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        //$this->load_excel($exchangeList,$filename,array('A','D','E'),array('I','J'));
        unset($exchange_list);
        $strlist.=',,,,,,,,'.$goodamounts."\t,".$tradeamounts."\t\n";
        $this->load_csv($strlist,$filename);
    }
    //积分/交易异常报表
    public function excardRep(){
        $model=new Model();
        $start = trim(I('get.startdate',''));//开始时间
        $end = trim(I('get.enddate',''));//结束时间
        $customid=trim(I('get.customid',''));//会员名称
        $cuname = trim(I('get.cuname',''));//会员名称
        $cardno = trim(I('get.cardno',''));//卡号
        $cardno = $cardno=="卡号"?"":$cardno;
        $customid =$customid=="会员编号"?"":$customid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['ex.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['ex.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['ex.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['ex.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($customid)){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if(!empty($cuname)){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if(!empty($cardno)){
            $where['ex.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }

        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }else{
            $where1['_string']="p.hysx <> '酒店' OR p.hysx IS NULL";
            $where['_complex']=$where1;
        }
        $field='ex.cardno,ex.placeddate,ex.placedtime,ex.reason,ex.panterid,';
        $field.='cu.customid,cu.namechinese cuname,p.namechinese pname';

        $count=$model->table('excardrep')->alias('ex')
            ->join('__CARDS__ c on c.cardno=ex.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=ex.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12 );
        $excard_list=$model->table('excardrep')->alias('ex')
            ->join('__CARDS__ c on c.cardno=ex.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=ex.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        session('excardRep_excel',$where);
        $page = $p->show ();
        $this->assign('page',$page);
        $this->assign('list',$excard_list);
        $this->display();
    }
    //积分/交易异常报表导出
    public function excardRep_excel(){
        $model=new Model();
        $excardRep_excel=session('excardRep_excel');
        foreach ($excardRep_excel as $key => $value) {
            $where[$key]=$value;
        }
        $field='ex.cardno,ex.placeddate,ex.placedtime,ex.reason,ex.panterid,';
        $field.='cu.customid,cu.namechinese cuname,p.namechinese pname';
        $excard_list=$model->table('excardrep')->alias('ex')
            ->join('__CARDS__ c on c.cardno=ex.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__PANTERS__ p on p.panterid=ex.panterid')
            ->where($where)->field($field)->select();
        $strlist="卡号,会员编号,会员名称,异常时间,异常原因,商户编号,商户名称\n";
        foreach($excard_list as $key=>$val){
            $strlist.=$val['cardno']."\t,".$val['customid']."\t,".$val['cuname'].','.date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']))."\t,".$val['reason'].','.$val['panterid']."\t,".$val['pname']."\n";
            /*$excardList[$key]['cardno']=$val['cardno'];
            $excardList[$key]['customid']=$val['customid'];
            $excardList[$key]['cuname']=$val['cuname'];
            $excardList[$key]['time']=date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $excardList[$key]['reason']=$val['reason'];
            $excardList[$key]['panterid']=$val['panterid'];
            $excardList[$key]['pname']=$val['pname'];*/
        }
        //array_unshift($excardList,array('卡号','会员编号','会员名称','异常时间','异常原因','商户编号','商户名称'));
        $filename='积分/交易异常报表'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        //$this->load_excel($excardList,$filename,array('A','B','F'));
        unset($excard_list);
        $this->load_csv($strlist,$filename);
    }
    //消费统计（行业分类）
    public function consumeStat1(){
        $model=new Model();
        $start = I('get.startdate','');//开始时间
        $end = I('get.enddate','');//结束时间
        $sex=I('get.sex','');//性别
        $hysx=I('get.hysx','');//行业属性
        $age=I('get.age','');//年龄
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['tw.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($sex)&&$sex!='all'){
            $where['cu.sex']=$sex;
            $this->assign('sex',$sex);
        }
        if(!empty($hysx)&&$hysx!='all'){
            $where['p.hysx']=$hysx;
            $this->assign('hysx',$hysx);
        }
        if(!empty($age)&&$age!='all'){
            $currentyear=date('Y');
            if($age=='18'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<18";
                $this->assign('age',$age);
            }elseif($age=='18-25'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=18 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=25";
                $this->assign('age',$age);
            }elseif($age=='26-30'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=26 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=30 ";
                $this->assign('age',$age);
            }elseif($age=='31-40'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=31 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=40 ";
                $this->assign('age',$age);
            }elseif($age=='41-50'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=41 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=50 ";
                $this->assign('age',$age);
            }elseif($age=='51-60'){
                $where['_string']="cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=51 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=60 ";
                $this->assign('age',$age);
            }elseif($age=='60'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>60";
                $this->assign('age',$age);
            }
        }
        $where['tw.tradetype']='13';
        $where['tw.flag']='0';
        $where['tw.tradeamount']=array('neq',0);
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }

        $field='p.hysx, count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount ';
        $consumeStat_list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->where($where)->field($field)->group('p.hysx')->select();
        session('consumeStat1_excel',$where);
        $this->assign('list',$consumeStat_list);
        $this->display();
    }
    //消费统计（商户分类）
    public function consumeStat2(){
        $model=new Model();
        $start = I('get.startdate','');//开始时间
        $end = I('get.enddate','');//结束时间
        $sex=I('get.sex','');//性别
        $hysx=I('get.hysx','');//行业属性
        $age=I('get.age','');//年龄
        $tradeamount=I('get.tradeamount','');//交易金额


        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['tw.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if(!empty($sex)&&$sex!='all'){
            $where['cu.sex']=$sex;
            $this->assign('sex',$sex);
        }
        if(!empty($hysx)&&$hysx!='all'){
            $where['p.hysx']=$hysx;
            $this->assign('hysx',$hysx);
        }
        if(!empty($age)&&$age!='all'){
            $currentyear=date('Y');
            if($age=='18'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<18";
                $this->assign('age',$age);
            }elseif($age=='18-25'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=18 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=25";
                $this->assign('age',$age);
            }elseif($age=='26-30'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=26 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=30 ";
                $this->assign('age',$age);
            }elseif($age=='31-40'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=31 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=40 ";
                $this->assign('age',$age);
            }elseif($age=='41-50'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=41 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=50 ";
                $this->assign('age',$age);
            }elseif($age=='51-60'){
                $where['_string']="cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>=51 and cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))<=60 ";
                $this->assign('age',$age);
            }elseif($age=='60'){
                $where['_string']=" cast('{$currentyear}' as NUMERIC(18,0))-cast(substr(cu.birthday,1,4) as NUMERIC(18,0))>60";
                $this->assign('age',$age);
            }
        }
        if(!empty($tradeamount)&&$tradeamount!='all'){
            if($tradeamount=='0-1000'){
                $where['tw.tradeamount']=array(array('gt',0),array('elt',1000));
                $this->assign('tradeamount',$tradeamount);
            }elseif($tradeamount=='1000-3000'){
                $where['tw.tradeamount']=array(array('gt',1000),array('elt',3000));
                $this->assign('tradeamount',$tradeamount);
            }elseif($tradeamount=='3000-5000'){
                $where['tw.tradeamount']=array(array('gt',3000),array('elt',5000));
                $this->assign('tradeamount',$tradeamount);
            }elseif($tradeamount=='5000-10000'){
                $where['tw.tradeamount']=array(array('gt',5000),array('elt',10000));
                $this->assign('tradeamount',$tradeamount);
            }elseif($tradeamount=='10000'){
                $where['tw.tradeamount']=array('gt',10000);
                $this->assign('tradeamount',$tradeamount);
            }
        }else{
            $where['tw.tradeamount']=array('neq',0);
        }

        $where['tw.tradetype']='13';
        $where['tw.flag']='0';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $field='tw.panterid,p.namechinese pname,count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount';
        $consumeStat_list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->where($where)->field($field)->group('tw.panterid,p.namechinese')->select();
        //echo $model->getLastSql();
        session('consumeStat2_excel',$where);
        $this->assign('list',$consumeStat_list);
        $this->display();
    }
    //消费统计（行业分类）报表导出
    public function consumeStat1_excel(){
        $model=new Model();
        $consumeStat1_excel=session('consumeStat1_excel');
        foreach ($consumeStat1_excel as $key => $value) {
            $where[$key]=$value;
        }
        $field='p.hysx, count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount ';
        $consumeStat1_list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->where($where)->field($field)->group('p.hysx')->select();
        $strlist="行业属性,消费次数,消费金额,客单价\n";
        $tradeamounts=0;
        $prices=0;
        foreach($consumeStat1_list as $key=>$val){
            $strlist.=$val['hysx'].','.$val['count'].','.$val['tradeamount']."\t,".round($val['tradeamount']/$val['count'],2)."\t\n";
            $tradeamounts+=$val['tradeamount'];
            $prices+=round($val['tradeamount']/$val['count'],2);
            /*$consumeStatList1[$key]['hysx']=$val['hysx'];
            $consumeStatList1[$key]['count']=$val['count'];
            $consumeStatList1[$key]['tradeamount']=$val['tradeamount'];
            $consumeStatList1[$key]['price']=round($val['tradeamount']/$val['count'],2);*/
        }
        //print_r($consumeStatList1);exit;
        //array_unshift($consumeStatList1,array('行业属性','消费次数','消费金额', '客单价'));
        $filename='消费统计（行业分类）'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        //$this->load_excel($consumeStatList1,$filename);
        //$this->load_excel();
        unset($consumeStat1_list);
        $strlist.=',,'.$tradeamounts."\t,".$prices."\t\n";
        $this->load_csv($strlist,$filename);
    }
    //消费统计（商户分类）报表导出
    public function consumeStat2_excel(){
        $model=new Model();
        $consumeStat2_excel=session('consumeStat2_excel');
        foreach ($consumeStat2_excel as $key => $value) {
            $where[$key]=$value;
        }
        $field='tw.panterid,p.namechinese pname,count(tw.tradeid) as count,sum(tw.tradeamount) as tradeamount';
        $consumeStat2_list=$model->table('trade_wastebooks')->alias('tw')
            ->join('__PANTERS__ p on tw.panterid=p.panterid')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->where($where)->field($field)->group('tw.panterid,p.namechinese')->select();
        $strlist="商户编号,商户名称,消费次数,消费金额,客单价\n";
        $tradeamounts=0;
        $prices=0;
        foreach($consumeStat2_list as $key=>$val){
            $strlist.=$val['panterid']."\t,".$val['pname'].','.$val['count'].','.$val['tradeamount']."\t,".round($val['tradeamount']/$val['count'],2)."\t\n";
            $tradeamounts+=$val['tradeamount'];
            $prices+=round($val['tradeamount']/$val['count'],2);
            /*$consumeStatList2[$key]['panterid']=$val['panterid'];
            $consumeStatList2[$key]['pname']=$val['pname'];
            $consumeStatList2[$key]['count']=$val['count'];
            $consumeStatList2[$key]['tradeamount']=$val['tradeamount'];
            $consumeStatList2[$key]['tradeamount']=$val['tradeamount'];*/
        }
        //array_unshift($consumeStatList2,array('行业属性','消费次数','消费金额', '客单价'));
        $filename='消费统计（行业分类）'.date("YmdHis");
        $filename = iconv("utf-8","gbk",$filename);
        //$this->load_excel($consumeStatList2,$filename,array('A'));
        unset($consumeStat2_list);
        $strlist.=',,,'.$tradeamounts."\t,".$prices."\t\n";
        $this->load_csv($strlist,$filename);
    }
    //商业卡休眠
    public function cardSleep(){
        $map['cardno']=array('like','6883379%');
        $map['status']='Y';
        $list=D('cards')->field('cardno')->where($map)->select();
        $currentDate=date('Ymd',time());
        $deadLine=date('Ymd',time()-180*24*3600);
        $trades=D('trade_wastebooks');
        $cards=D('cards');
        $model=new Model();
        foreach($list as $key=>$val){
            $where['placeddate']=array('egt',$deadLine);
            $where['cardno']=$val['cardno'];
            $c=$trades->where($where)->count();
            if($c==0){
                $sleepid=$this->getnextcode('card_sleep',19);
                $sql="INSERT INTO CARD_SLEEP VALUES('{$sleepid}','{$val['cardno']}','半年无消费休眠','{$currentDate}','1','0000000000000000','')";
                if($model->execute($sql)){
                    $data['status']='B';
                    $map1['cardno']=$val['cardno'];
                    $cards->where($map1)->save($data);
                }
            }
        }
    }

    //卡休眠激活处理
    public function cardWeak(){
        $model=new Model();
        if($this->panterid!='FFFFFFFF'){
            $map['_string']=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $field='c.cardno,cu.namechinese cuname,cs.sleepdate';
        $map['c.status']='B';
        $count=$model->table('cards')->alias('c')
            ->join('left join panters p on c.panterid=p.panterid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('card_sleep cs on cs.cardno=c.cardno')
            ->where($map)->field($field)->count();
        $p=new \Think\Page($count, 10 );
        $list=$model->table('cards')->alias('c')
            ->join('left join panters p on c.panterid=p.panterid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('card_sleep cs on cs.cardno=c.cardno')
            ->where($map)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('list',$list);
        $page = $p->show ();
        $this->assign('page',$page);
        $this->display();
    }

    //卡休眠激活执行
    public function cardWeakDo(){
        $cardno=$_REQUEST['cardno'];
        if(empty($cardno)){
            $this->error('未选择激活卡号');
        }
        if(is_string($cardno)){
            $cardArr=array($cardno);
        }else{
            $cardArr=$cardno;
        }
        $currentDate=date('Ymd',time());
        $model=new Model();
        $cards=M('cards');
        $card_sleep=M('card_sleep');
        $c=0;
        $resStr='';
        foreach($cardArr as $val){
            $cardno=$val;
            $map1['cardno']=$cardno;
            $cardInfo=$cards->where($map1)->field('status')->find();
            if($cardInfo['status']=='Y'){
                $resStr.=$val.'已是正常卡';
                continue;
            }
            $map=array('cardno'=>$cardno,'active'=>1);
            $sleepInfo=M('card_sleep')->where($map)->find();
            $sleepid=$this->getnextcode('card_sleep',19);
            $sql="INSERT INTO CARD_SLEEP VALUES('{$sleepid}','{$cardno}',";
            $sql.="'卡号休眠激活','{$currentDate}','2','0000000000000000','{$sleepInfo['sleepid']}')";
            $model->startTrans();
            if($model->execute($sql)){
                $data=array('active'=>3);
                $sleepif=$card_sleep->where($map)->save($data);

                $data1['status']='Y';
                $cardif=$cards->where($map1)->save($data1);
                if($sleepif==true&&$cardif==true){
                    $model->commit();
                    $resStr.=$val.'激活成功';
                    $c++;
                }else{
                    $model->rollback();
                    $resStr.=$val.'激活失败';
                }
            }
        }
        if($c>0){
            $this->success('激活成功'.$c.'条');
        }else{
            $this->error('全部激活失败');
        }
    }
}
