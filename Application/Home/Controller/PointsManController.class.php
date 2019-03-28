<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class PointsManController extends CommonController {

    //卡售后管理
    public function _initialize(){
        parent::_initialize();
    }
    //积分规则设置
    public function activeRules(){
        $model=new Model();
        $id = trim(I('get.id',''));
        $activename	= trim(I('get.activename',''));
        if(trim($id)!=''){
            $where['id']=$id;
            $this->assign('id',$id);
        }
        if(trim($activename)!=''){
            $where['activename']=array('like','%'.$activename.'%');
            $this->assign('activename',$activename);
        }
        if($this->panterid!='FFFFFFFF'){
            $map['panterid']=$this->panterid;
            $panter=M('panters')->field('pantergroup')->where($map)->find();
            $where['pg.groupid']=$panter['pantergroup'];
        }
        $field='ap.*,pg.groupname';
        $activeRules=$model->table('active_pointrole')->alias('ap')
            ->join('left join __PANTERGROUP__ pg on pg.groupid=ap.groupid')
            ->where($where)->field($field)->order('ap.id desc')->select();
        $this->assign('list',$activeRules);
        $this->display();
    }
    //积分规则添加
    public function activeRulesAdd(){
        if(IS_POST){
            $model=new Model();
            $activename=trim($_POST['activename']);
            $pointbs=trim($_POST['pointbs']);
            $startdate=trim($_POST['startdate']);
            $enddate=trim($_POST['enddate']);
            $groupid=trim($_POST['groupid']);
            $flag=trim($_POST['flag']);
            if(empty($activename)){
                $this->error('活动名称必填');
            }
            if(empty($pointbs)){
                $this->error('积分倍数必填');
            }
            if(empty($startdate)){
                $this->error('开始日期必填');
            }
            if(empty($enddate)){
                $this->error('结束日期必填');
            }
            if(strtotime($startdate)>strtotime($enddate)){
                $this->error('结束日期应不小于开始日期');
            }
            if(empty($groupid)){
                $this->error('商圈必选');
            }
            $activeid=$this->getnextcode('ACTIVE_POINTROLE',8);
            //echo $activeid;exit;
            $start=date('Ymd',strtotime($startdate));
            $end=date('Ymd',strtotime($enddate));
            $sql="insert into active_pointrole values('{$activeid}','{$activename}','{$pointbs}','{$start}','{$end}','{$flag}','{$groupid}')";
            if($model->execute($sql)){
                $this->success('添加成功！',U('PointsMan/activeRules'));
            }else{
                $this->error('添加失败！',U('PointsMan/activeRules'));
            }
        }else{
            if($this->panterid!='FFFFFFFF'){
                $where['panterid']=$this->panterid;
                $panter=M('panters')->where($where)->find();
                $where1=array('groupid'=>$panter['pantergroup']);
            }
			if ($where1['groupid'] != null){
				$group=M('pantergroup')->where($where1)->select();
			}
			else
				$group=M('pantergroup')->select();
            $this->assign('group',$group);
            $this->display();
        }
    }
    //积分规则修改
    public function activeRulesEdit(){
        if(IS_POST){
            $model=new Model();
            $id=$_POST['id'];
            $activename=trim($_POST['activename']);
            $pointbs=trim($_POST['pointbs']);
            $startdate=trim($_POST['startdate']);
            $enddate=trim($_POST['enddate']);
            $groupid=trim($_POST['groupid']);
            $flag=trim($_POST['flag']);
            if(empty($activename)){
                $this->error('活动名称必填');
            }
            if(empty($pointbs)){
                $this->error('积分倍数必填');
            }
            if(empty($activename)){
                $this->error('开始日期必填');
            }
            if(empty($activename)){
                $this->error('结束日期必填');
            }
            if(strtotime($startdate)>strtotime($enddate)){
                $this->error('结束日期应不小于开始日期');
            }
            if(empty($groupid)){
                $this->error('商圈必选');
            }
            $start=date('Ymd',strtotime($startdate));
            $end=date('Ymd',strtotime($enddate));
            $data=array('activename'=>$activename,'pointbs'=>$pointbs,
                'startdate'=>$start,'enddate'=>$end,'groupid'=>$groupid,'flag'=>$flag);
            if(M('active_pointrole')->where('id='.$id)->save($data)){
                $this->success('修改成功',U('PointsMan/activeRules'));
            }else{
                $this->error('修改失败');
            }
        }else{
            $id=intval($_REQUEST['id']);
            if(empty($id)){
                $this->error('活动Id缺失');
            }
            $active_info=M('active_pointrole')->where('id='.$id)->find();
            if($active_info==false){
                $this->error('无此活动记录');
            }
            $this->assign('info',$active_info);
            $group=M('pantergroup')->select();
            $this->assign('group',$group);
            $this->display();
        }
    }
    //积分产生规则设置
    public function rules(){
        $model=new Model();
        $panterid = trim(I('get.panterid',''));
        $pname	= trim(I('get.pname',''));
        if(trim($panterid)!=''){
            $where['pr.panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        if(trim($pname)!=''){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $field='pr.*,p.namechinese pname';
        $rules_list=$model->table('panters_roles')->alias('pr')
            ->join('__PANTERS__ p on p.panterid=pr.panterid')
            ->where($where)->field($field)->select();
        $this->assign('list',$rules_list);
        $this->display();
    }
    //积分产生规则添加
    public function rulesAdd(){
        if(IS_POST){
            $model=new Model();
            $pname=trim($_POST['pname']);
            $panterid=trim($_POST['panterid']);
            $startdate=trim($_POST['startdate']);
            $enddate=trim($_POST['enddate']);
            $roles=trim($_POST['roles']);
            $roles1=trim($_POST['roles1']);
            if(empty($panterid)){
                if(empty($pname)){
                    $this->error('商户必选');
                }else{
                    $map=array('namechinese'=>$pname);
                    $panter=M('panters')->field('panterid')->where($map)->find();
                    //print_r($panter);exit;
                    if($panter==false){
                        $this->error('查无此商户记录,请确定商户名称无误');
                    }else{
                        $panterid=$panter['panterid'];
                    }
                }
            }
            if(empty($startdate)){
                $this->error('开始日期必填');
            }
            if(empty($enddate)){
                $this->error('结束日期必填');
            }
            $map['panterid']=$panterid;
            $panters_roles=M('panters_roles')->where($map)->find();
            if($panters_roles!=false){
                $this->error('该商户已设置');
            }
            if(strtotime($startdate)>strtotime($enddate)){
                $this->error('结束日期应不小于开始日期');
            }
            if(empty($roles)){
                $this->error('卡消费通用积分比率必填');
            }
            if(empty($roles1)){
                $this->error('现金消费通用积分比率必填');
            }
            $start=date('Ymd',strtotime($startdate));
            $end=date('Ymd',strtotime($enddate));
            $sql="insert into panters_roles values('{$panterid}','{$start}','{$end}','{$roles}','{$roles1}')";
            if($model->execute($sql)){
                $this->success('添加成功！',U('PointsMan/rules'));
            }else{
                $this->error('添加失败！',U('PointsMan/rules'));
            }
        }else{
            if($this->panterid!='FFFFFFFF'){
                $where1['parent']=$this->panterid;
                $where1['panterid']=$this->panterid;
                $where1['_logic']='or';
                $where['_complex']=$where1;
            }else{
                $where['panterid']=array('not in',array('FFFFFFFF','EEEEEEEE'));
            }
            $where['revorkflg']='N';

            $panters=M('panters')->field('panterid,namechinese pname')->where($where)->order('namechinese asc')->select();
            $this->assign('panters',$panters);
            $this->display();
        }
    }
     function rulesEdit(){
        $model=new Model();
        if(IS_POST){
            $panterid=trim($_POST['panterid']);
            $data['bdate']=date('Ymd',strtotime(trim($_POST['bdate'])));
            $data['edate']=date('Ymd',strtotime(trim($_POST['edate'])));
            $data['roles1']=trim($_POST['roles1']);
            $data['roles']=trim($_POST['roles']);
            if(M('panters_roles')->create()){
                if(M('panters_roles')->where('panterid='.$panterid)->save($data)){
                    $this->success('积分产生规则资料成功');
                }else{
                    $this->error('积分产生规则修改失败');
                }
            }
        }else{
            $panterid=trim($_REQUEST['panterid']);
            if(empty($panterid)){
                $this->error('商户ID缺失');
            }
            $where['pr.panterid']=$panterid;

            $field='pr.*,p.namechinese pname';
            $list=$model->table('panters_roles')->alias('pr')
                     ->join('__PANTERS__ p on p.panterid=pr.panterid')
                     ->where($where)->field($field)->find();
            $this->assign('list',$list);
            $this->display();
        }
    }
    //会员积分报表
    public function pointsRep(){
        $model=new Model();
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//会员名称
        if($cardno!=''){
            $where['c.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        $where['a.type']='01';
        $where['c.status']='Y';
        //$where['_string']='cu.namechinese is not null';
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $field='c.cardno,cu.customid cuid,cu.namechinese cuname,a.amount cardpoint';

        $count=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on c.customid=cc.cid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $card_list=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on c.customid=cc.cid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$card_list);
        if($this->panterid!='FFFFFFFF'){
            $where2['panterid']=$this->panterid;
        }
        $where2['flag']='启用';
        $goods=M('goods')->where($where2)->select();
        $this->assign('goods',$goods);
        $this->display();
    }
    //积分查询
    public function pointQuery(){
        $cardno=trim($_POST['cardno']);
        if(empty($cardno)){
            $res['success']=0;
            $res['msg']='卡号缺失';
            echo json_encode($res);
            exit;
        }
        $where['c.cardno']=$cardno;
        $where['a.type']='01';
        $field='c.cardno,cu.customid cuid,cu.namechinese cuname,a.amount cardpoint';
        $model=new Model();
        $point_info=$model->table('cards')->alias('c')
            ->join('__CUSTOMS_C__ cc on c.customid=cc.cid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->where($where)->field($field)->find();
        if($point_info!=false){
            if(trim($point_info['cardpoint'])==''){
                $point_info['cardpoint']=0;
            }
            $res['data']=$point_info;
            $res['success']=1;
        }else{
            $res['msg']='无此卡信息';
            $res['success']=0;
        }
        echo json_encode($res);
    }
    //积分礼品兑换
    public function pointExchange(){
        $cardno=$_REQUEST['cardno'];
        $goodsid=$_REQUEST['goodsid'];
        $amount=$_REQUEST['amount'];
        if(empty($cardno)){
            $res['msg']='卡号缺失';
            $res['success']=0;
            echo json_ecode($res);
            exit;
        }
        if(empty($goodsid)){
            $res['msg']='礼品Id缺失';
            $res['success']=0;
            echo json_ecode($res);
            exit;
        }
        if(empty($amount)){
            $res['msg']='数量缺失';
            $res['success']=0;
            echo json_ecode($res);
            exit;
        }
        $goodsArr=explode('_',$goodsid);
        $goodsId=$goodsArr[0];
        $price=$goodsArr[1];
        $where=array('cardno'=>$cardno,'placeddate'=>date('Ymd',time()));
        $card_purchase_logs=M('card_change_logs')->where($where)->find();
        if($card_purchase_logs!=false){
            $res['success']=0;
            $res['msg']='此卡今日补卡，隔日才能兑换积分！';
            echo json_ecode($res);
            exit;
        }
        $model=new Model();
        $model->startTrans();
        $total_price=$amount*$price;
        $where1=array('c.cardno'=>$cardno,'a.type'=>'01');
        $field='c.cardno,a.amount,c.customid,cu.customid cuid,cu.namechinese cuname,cu.linktel';
        $card=$model->table('cards')->alias('c')
            ->join('__ACCOUNT__ a on a.customid=c.customid')
            ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->where($where1)->field($field)->find();
        if($total_price>$card['amount']){
            $res['success']=0;
            $res['msg']='积分余额不足，不能兑换';
            echo json_ecode($res);
            exit;
        }
        $rempoint=$card['amount']-$total_price;
        $where2=array('customid'=>$card['customid'],'type'=>'01');
        $data2=array('amount'=>$rempoint);
        M('account')->where($where2)->save($data2);


        $tradeid=$this->getnextcode('goodstrade',8);
        $currentdate=date('Ymd',time());
        $currenttime=date('H:i:s',time());
        $panterid=$this->panterid;
        $userid=$this->userid;
        $sql="insert into goodstrade values('{$tradeid}','{$currentdate}',";
        $sql.="'{$currenttime}','{$total_price}','{$goodsId}','{$amount}','{$card['customid']}',";
        $sql.="'{$cardno}','{$card['cuname']}','{$panterid}','{$userid}')";
        if($model->execute($sql)){
            $model->commit();
            $res['success']=1;
        }else{
            $model->rollback();
            $res['success']=0;
            $res['msg']='网路故障请重试';
        }
        echo json_encode($res);
        /*if(!empty($card['linktel'])){
            $this->sendMessage();
        }*/
    }

    //积分商品
    public function pointsGoods(){
        $model=new Model();
        $panterid = trim(I('get.panterid',''));
        $goodsname	= trim(I('get.goodsname',''));
        
        if(!empty($panterid)){
            $where['p.panterid']=$panterid;
            $this->assign('panterid',$panterid);
        }
        if(!empty($goodsname)){
            $where['g.goodsname']=array('like','%'.$goodsname.'%');
            $this->assign('goodsname',$goodsname);
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        //$where['g.flag']='启用';
        $field='g.*,p.namechinese pname';
        $count=$model->table('goods')->alias('g')
            ->join('left join __PANTERS__ p on p.panterid=g.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $goods=$model->table('goods')->alias('g')
            ->join('left join __PANTERS__ p on p.panterid=g.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$goods);
        $this->display();
    }

    //积分商品添加
    public function goodsAdd(){
        if(IS_POST){          
            $goodsname=trim($_REQUEST['goodsname']);
            $price=trim($_REQUEST['price']);
            $panterid=trim($_REQUEST['panterid']);
            $pname=trim($_REQUEST['pname']);
            $flag=$_REQUEST['flag'];
            if(empty($goodsname)){
                $this->error('商品名字必填');
            }
            if(empty($price)){
                $this->error('价格必填');
            }
            if(empty($panterid)){
                if(empty($pname)){
                    $this->error('所属商户必填');
                }else{
                    $map=array('namechinese'=>$pname);
                    $panter=M('panters')->field('panterid')->where($map)->find();

                    if($panter==false){
                       $this->error('查无此商户记录,请确定商户名称无误',U('PointsMan/goodsAdd'));
                    }else{
                        $panterid=$panter['panterid'];
                    }
                }    
            }
            $goodsid=$this->getnextcode('goods',3);
            $model=new Model();
            $sql="insert into goods values('{$goodsid}','{$goodsname}','{$price}','{$panterid}','{$flag}')";
            if($model->execute($sql)){
                $this->success('执行成功',U('PointsMan/pointsGoods'));
            }else{
                $this->error('执行失败',U('PointsMan/pointsGoods'));
            }
        }else{
            if($this->panterid!='FFFFFFFF'){
                $where1['parent']=$this->panterid;
                $where1['panterid']=$this->panterid;
                $where1['_logic']='or';
                $where['_complex']=$where1;
            }else{
                $where['panterid']=array('not in',array('FFFFFFFF','EEEEEEEE'));
            }
            $where['revorkflg']='N';

            $panters=M('panters')->field('panterid,namechinese pname')->where($where)->order('namechinese asc')->select();
            $this->assign('panters',$panters);
            $this->display();
        }
    }

    //积分商品修改
    public function goodsEdit(){
        if(IS_POST){
            $goodsid=trim($_REQUEST['goodsid']);
            $goodsname=trim($_REQUEST['goodsname']);
            $price=trim($_REQUEST['price']);
            $panterid=trim($_REQUEST['panterid']);
            $flag=$_REQUEST['flag'];
            if(empty($goodsid)){
                $this->error('商品Id缺失');
            }
            if(empty($goodsname)){
                $this->error('商品名字必填');
            }
            if(empty($price)){
                $this->error('价格必填');
            }
            if(empty($panterid)){
                if(empty($pname)){
                    $this->error('所属商户必填');
                }else{
                    $map=array('namechinese'=>$pname);
                    $panter=M('panters')->field('panterid')->where($map)->find();

                    if($panter==false){
                       $this->error('查无此商户记录,请确定商户名称无误',U('PointsMan/goodsAdd'));
                    }else{
                        $panterid=$panter['panterid'];
                    }
                }    
            }
            $data=array('goodsname'=>$goodsname, 'price'=>$price,'panterid'=>$panterid,'flag'=>$flag);
            if(M('goods')->where('goodsid='.$goodsid)->save($data)){
                $this->success('修改成功',U('PointsMan/pointsGoods'));
            }else{
                $this->error('修改失败',U('PointsMan/pointsGoods'));
            }
        }else{
            $goodsid=$_REQUEST['goodsid'];
            if(empty($goodsid)){
                $this->error('商品Id缺失');
            }
            $field='g.goodsname,g.goodsid,g.price,g.flag,p.panterid,p.namechinese';
            $goods=M('goods')->alias('g')->field($field)->join("left join panters p on p.panterid=g.panterid")
                ->where('goodsid='.$goodsid)->find();
            if($goods==false){
                $this->error('无记录');
            }
            $this->assign('goods',$goods);
            if($this->panterid!='FFFFFFFF'){
                $where1['parent']=$this->panterid;
                $where1['panterid']=$this->panterid;
                $where1['_logic']='or';
                $where['_complex']=$where1;
            }else{
                $where['panterid']=array('not in',array('FFFFFFFF','EEEEEEEE'));
            }
            $where['revorkflg']='N';
            $panters=M('panters')->field('panterid,namechinese pname')->where($where)->order('namechinese asc')->select();
            //print_r($panters);
            $this->assign('panters',$panters);
            $this->display();
        }
    }

    //退还礼品
    public function returnGift(){
        $model=new model();
        $start = trim(I('get.startdate',''));//开始日期
        $end = trim(I('get.enddate',''));//结束日期
        $cardno = trim(I('get.cardno',''));//卡号
        $customid = trim(I('get.customid',''));//会员编号
        $cuname = trim(I('get.cuname',''));//会员名称
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['rgl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['rgl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['rgl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['rgl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($cardno!=''){
            $where['rgl.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['cu.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['cu.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        $where['a.type']='01';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $field='rgl.tradeid,cu.customid cuid,cu.namechinese cuname,rgl.cardno,';
        $field.='rgl.placeddate,gt.goodamount,g.goodsname,g.price,rgl.account,rgl.userid,g.goodsid';
        $count=$model->table('returngift_logs')->alias('rgl')
            ->join('__CARDS__ c on c.cardno=rgl.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=rgl.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=rgl.customid')
            ->join('__GOODSTRADE__ gt on gt.tradeid=rgl.tradeid')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__PANTERS__ p on c.panterid=p.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $list=$model->table('returngift_logs')->alias('rgl')
            ->join('__CARDS__ c on c.cardno=rgl.cardno')
            ->join('__CUSTOMS_C__ cc on cc.cid=rgl.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=rgl.customid')
            ->join('__GOODSTRADE__ gt on gt.tradeid=rgl.tradeid')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__PANTERS__ p on c.panterid=p.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display();
    }

    //礼品交易查询
    public function getGifttrade(){
        //$tradeid='00000006';
        $tradeid=trim($_REQUEST['tradeid']);
        if(empty($tradeid)){
            $res['msg']='交易编号缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $model=new Model();
        $where['gt.tradeid']=$tradeid;
        $where['a.type']='01';
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $field='gt.*,g.goodsname,g.price,cu.namechinese cuname,a.amount cardpoint,cu.customid cuid,p.namechinese pname';
        $goodstrade=$model->table('goodstrade')->alias('gt')
            ->join('__GOODS__ g on g.goodsid=gt.goodid')
            ->join('__CUSTOMS_C__ cc on cc.cid=gt.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=gt.customid')
            ->join('__PANTERS__ p on p.panterid=gt.panterid')
            ->where($where)->field($field)->find();
        if($goodstrade==false){
            $res['msg']='无此记录';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }else{
            $goodstrade['returnpoint']=$goodstrade['tradeamount']*$goodstrade['price'];
            $res['data']=$goodstrade;
            $res['success']=1;
            echo json_encode($res);
        }
    }

    //礼品退还执行
    public function returnGiftDo(){
        $tradeid=trim($_REQUEST['tradeid']);
        //$tradeid='00000006';
        if(empty($tradeid)){
            $res['msg']='交易编号缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $goodstrade=M('goodstrade')->where('tradeid='.$tradeid)->find();
        $returngift_logs=M('returngift_logs')->where('tradeid='.$tradeid)->find();
        if($goodstrade==false){
            $res['msg']='查无此记录';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        if($returngift_logs!=false){
            $res['msg']='礼物已退还';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $model=new Model();
        $where['gt.tradeid']=$tradeid;
        $where['a.type']='01';
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $field='gt.*,g.goodsname,g.price,g.goodsid,cu.namechinese cuname,a.amount cardpoint,cu.customid cuid,p.namechinese pname';
        $trade_info=$model->table('goodstrade')->alias('gt')
            ->join('left join __GOODS__ g on g.goodsid=gt.goodid')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=gt.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('left join __ACCOUNT__ a on a.customid=gt.customid')
            ->join('left join __PANTERS__ p on p.panterid=gt.panterid')
            ->where($where)->field($field)->find();
        $currenddate=date('Ymd',time());
        $currendtime=date('H:i:s',time());
        $user=$this->userid;

        $model->startTrans();
        //更改交易流水表
        $data1=array('customname'=>1);
        $where1=array('tradeid'=>$tradeid);
        M('goodstrade')->where($where1)->save($data1);

        //更改积分账户
        $where2=array('customid'=>$trade_info['customid'],'type'=>'01');
        $data2=array('amount'=>$trade_info['cardpoint']+$trade_info['tradeamount']);
        M('account')->where($where2)->save($data2);


        //写入礼品退还日志表
        $sql="insert into returngift_logs VALUES ('{$tradeid}','{$trade_info['customid']}','{$trade_info['cardno']}',";
        $sql.="'{$currenddate}','{$currendtime}','{$trade_info['goodsid']}','{$trade_info['goodamount']}','{$user}','{$trade_info['tradeamount']}')";
        if($model->execute($sql)){
            $model->commit();
            $res['success']=1;
        }else{
            $model->rollback();
            $res['success']=0;
            $res['msg']='网路故障请重试';
        }

        echo json_encode($res);
    }

    //退货管理
    public function returnGoods(){
        $model=new model();
        $start = trim(I('get.startdate',''));//开始日期
        $end = trim(I('get.enddate',''));//结束日期
        $cardno = I('get.cardno','');//卡号
        $customid = I('get.customid','');//会员编号
        $cuname = I('get.cuname','');//会员名称
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['rl.placeddate']=array('egt',$startdate);
            $this->assign('startdate',$start);
            $map['startdate']=$start;
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['rl.placeddate'] = array('elt',$enddate);
            $this->assign('enddate',$end);
            $map['enddate']=$end;
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['rl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',$start);
            $this->assign('enddate',$end);
            $map['startdate']=$start;
            $map['enddate']=$end;
        }
        if($start=='' && $end==''){
            $startdate=date('Ym01',strtotime(date('Ymd')));
            $enddate=date('Ymd',time());
            $where['rl.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            $this->assign('startdate',date('Y-m-d',strtotime($startdate)));
            $this->assign('enddate',date('Y-m-d',strtotime($enddate)));
        }
        if($cardno!=''){
            $where['rl.cardno']=$cardno;
            $this->assign('cardno',$cardno);
            $map['cardno']=$cardno;
        }
        if($customid!=''){
            $where['c.customid']=$customid;
            $this->assign('customid',$customid);
            $map['customid']=$customid;
        }
        if($cuname!=''){
            $where['c.cuname']=$cuname;
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $where['a.type']='01';
        $where['tw.tradetype']='13';
        $where['tw.flag']='3';
        $field='rl.tradeid,tw.panterid,p.namechinese pname,c.customid cuid,c.namechinese cuname,rl.cardno,';
        $field.='rl.placeddate,rl.tradeamount,rl.userid,tw.addpoint';
        $count=$model->table('return_logs')->alias('rl')
            ->join('__CUSTOMS_C__ cc on cc.cid=rl.customid')
            ->join('__CUSTOMS__ c on c.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=rl.customid')
            ->join('__TRADE_WASTEBOOKS__ tw on tw.tradeid=rl.tradeid')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $list=$model->table('return_logs')->alias('rl')
            ->join('__CUSTOMS_C__ cc on cc.cid=rl.customid')
            ->join('__CUSTOMS__ c on c.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=rl.customid')
            ->join('__TRADE_WASTEBOOKS__ tw on tw.tradeid=rl.tradeid')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->where($where)->field($field)->where($where)
            ->field($field)->limit($p->firstRow.','.$p->listRows)->select();
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display();
    }

    //交易订单查询
    public function getTrade(){
        //$tradeid='600030141014144723';
        $tradeid=trim($_REQUEST['tradeid']);
        if(empty($tradeid)){
            $res['msg']='交易编号缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $model=new Model();
        $where['tw.tradetype']='13';
        $where['tw.flag']='0';
        $where['a.type']='01';
        $where['tw.tradeid']=$tradeid;
        if($this->panterid!='FFFFFFFF'){
            $where['_string']='p.panterid='.$this->panterid.' or p.parent='.$this->panterid;
        }
        $field='tw.tradeid,cu.customid cuid,cu.namechinese cuname,tw.cardno,tw.tradeamount,tw.addpoint,tw.placeddate,';
        $field.='a.amount cardpoint,p.namechinese pname';
        $trade_info=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=tw.customid')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->where($where)->field($field)->find();
        if($trade_info==false){
            $res['msg']='无此记录';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }else{
            $trade_info['tradeamount']=floatval($trade_info['tradeamount']);
            $trade_info['cardpoint']=floatval($trade_info['cardpoint']);
            $trade_info['addpoint']=floatval($trade_info['addpoint']);
            $res['data']=$trade_info;
            $res['success']=1;
            echo json_encode($res);
        }
    }

    //退货执行
    public function returnGoodsDo(){
        $tradeid=trim($_REQUEST['tradeid']);
        //$tradeid='600030141014144723';
        if(empty($tradeid)){
            $res['msg']='交易编号缺失';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $where['tradeid']=$tradeid;
        $goodstrade=M('trade_wastebooks')->where($where)->find();
        $returngift_logs=M('return_logs')->where('tradeid='.$tradeid)->find();
        if($goodstrade==false){
            $res['msg']='查无此记录';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        if($returngift_logs!=false){
            $res['msg']='已退货,无需重复操作';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $model=new Model();
        $where['tw.tradetype']='13';
        $where['tw.flag']='0';
        $where['a.type']='01';
        if($this->panterid!='FFFFFFFF'){
            $where['_string']='p.panterid='.$this->panterid.' or p.parent='.$this->panterid;
        }
        $field='tw.tradeid,cu.namechinese cuname,tw.cardno,tw.tradeamount,tw.addpoint,tw.placeddate,';
        $field.='a.amount cardpoint,p.namechinese pname,cc.cid cardid';
        $trade_info=$model->table('trade_wastebooks')->alias('tw')
            ->join('__CUSTOMS_C__ cc on cc.cid=tw.customid')
            ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
            ->join('__ACCOUNT__ a on a.customid=tw.customid')
            ->join('__PANTERS__ p on p.panterid=tw.panterid')
            ->where($where)->field($field)->find();
        if($trade_info['cardpoint']<$trade_info['addpoint']){
            $res['msg']='积分不足，请补足积分或到管理中心退回礼品';
            $res['success']=0;
            echo json_encode($res);
            exit;
        }
        $currenddate=date('Ymd',time());
        $currendtime=date('H:i:s',time());
        $user=$this->userid;

        $model->startTrans();
        //更改交易流水表
        $data1=array('flag'=>3);
        $where1=array('tradeid'=>$tradeid,'tradetype'=>'13');
        M('trade_wastebooks')->where($where1)->save($data1);

        //更改积分账户
        $where2=array('customid'=>$trade_info['cardid'],'type'=>'01');
        $data2=array('amount'=>$trade_info['cardpoint']-$trade_info['addpoint']);
        M('account')->where($where2)->save($data2);

        //写入退货日志表
        $sql="insert into return_logs VALUES ('{$tradeid}','{$trade_info['cardid']}','{$trade_info['cardno']}',";
        $sql.="'{$currenddate}','{$currendtime}','{$trade_info['tradeamount']}','{$trade_info['addpoint']}','{$user}')";
        if($model->execute($sql)){
            $model->commit();
            $res['success']=1;
        }else{
            $model->rollback();
            $res['success']=0;
            $res['msg']='网路故障请重试';
        }

        echo json_encode($res);
    }

    //积分兑换报表
    public function pointExchangeRep(){
        $model=new Model();
        $start = trim(I('get.startdate',''));//开始时间
        $end = trim(I('get.enddate',''));//结束时间
        $customid=trim(I('get.customid',''));//会员名称
        $cuname = trim(I('get.cuname',''));//会员名称
        $cardno = trim(I('get.cardno',''));//卡号
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
    public function pointExchangeRep_excel(){
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
        $exchangeList=array();
        foreach($exchange_list as $key=>$val){
            $exchangeList[$key]['tradeid']=$val['tradeid'];
            $exchangeList[$key]['time']=date('Y-m-d H:i:s',strtotime($val['placeddate'].$val['placedtime']));
            $exchangeList[$key]['pname']=$val['pname'];
            $exchangeList[$key]['cardno']=$val['cardno'];
            $exchangeList[$key]['customid']=$val['customid'];
            $exchangeList[$key]['cuname']=$val['cuname'];
            $exchangeList[$key]['goodsname']=$val['goodsname'];
            $exchangeList[$key]['price']=$val['price'];
            $exchangeList[$key]['goodamount']=$val['goodamount'];
            $exchangeList[$key]['tradeamount']=$val['tradeamount'];
        }
        array_unshift($exchangeList,array('兑换流水号','兑换时间',
            '兑换机构','卡号','会员编号','会员名称'
        ,'兑换商品','单价','兑换数量','兑换总积分'));
        $filename='积分兑换报表'.date("YmdHis");
        $this->load_excel($exchangeList,$filename,array('A','D','E'),array('I','J'));
    }
}