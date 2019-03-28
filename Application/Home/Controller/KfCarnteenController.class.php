<?php
namespace Home\Controller;
use Home\Model\KfModel;
use Think\Controller;
use Think\Model;
class KfCarnteenController extends  Controller
{
    protected $model;
    protected $parent;
    protected $panterid;
    protected $pantername;
    protected $frozen = 5;
    protected $printUrl='http://localhost/zzkpay/public/print/demo/interface/windows-usb.php?type=';
    public function _initialize(){
        if($_GET['panterid']){
            setcookie('panterid',$_GET['panterid']);
        }else{
            $this->panterid=$_COOKIE['panterid'];
        }
        $this->checkLogin();
        $this->parent =C("parent");

        $this->model=new model();
        $this->pantername=M('panters')->where(array('panterid'=>$this->panterid))->getField('namechinese');
    }

    //检测登录
    public function checkLogin(){
        $password=md5('123456');
        if(empty($_COOKIE['value'])) {
            if ($_POST) {
                $postPassword = $_POST['text'];
                if(empty($postPassword)){
                    $this->error('密码不能为空！');
                }

                if ($password == md5($postPassword)) {
                    setcookie('value', 1);
                    $this->success('登录成功','goods');
                } else {
                    $this->error('密码错误，请重新登录！');
                }
            } else {
                $this->redirect('Public/login2');
            }
        }
    }

    //菜品首页
    public function goods(){
        $where['panterid'] = $this->panterid;
        $where['type']     = '1';
        $where['status']   = '1';
        $goodslist = KfModel::getNoramlGoods($where);
        $num =0;
        $str = "";
        foreach($goodslist as $key=>$val){
            $val['price']=floatval($val['price']);
            if($num===0){
                $str.=" <ul style='padding-top: 0;'>
                       <li style=' margin-left:0px;'>
                          <div class='bg'>
                            <span style='padding-top:50px;'>{$val['goodsname']}</span>
                            <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥{$val['price']}</span>
                            <input type='hidden' value='{$val['goodsid']}'>
                          </div>
                         <div class='center'>
                              <div id='a{$key}' class='Spinner'></div>
                         </div>
                       </li>

                  ";
            }
            if($num===1){
                $str.="
                     <li>
                        <div class='bg'>
                          <span style='padding-top:50px;'>{$val['goodsname']}</span>
                          <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥{$val['price']}</span>
                          <input type='hidden' value='{$val['goodsid']}'>
                       </div>
                       <div class='center'>
                              <div id='a{$key}' class='Spinner'></div>
                         </div>
                       </li>

                  ";
            }
            if($num==2){
                $str.="
                   <li>
                        <div class='bg'>
                          <span style='padding-top:50px;'>{$val['goodsname']}</span>
                          <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥{$val['price']}</span>
                          <input type='hidden' value='{$val['goodsid']}'>
                       </div>
                       <div class='center'>
                              <div id='a{$key}' class='Spinner'></div>
                         </div>
                    </li>

                  ";
            }
            if($num==3){
                $str.="
                   <li>
                        <div class='bg'>
                          <span style='padding-top:50px;'>{$val['goodsname']}</span>
                          <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥{$val['price']}</span>
                          <input type='hidden' value='{$val['goodsid']}'>
                       </div>
                       <div class='center'>
                              <div id='a{$key}' class='Spinner'></div>
                       </div>
                    </li>

               </ul>
                  ";
            }
            $num++;
            $num=$num===4?0:$num;
        }
        if($num!==0){
            $str.=" </ul>";
        }
        $this->assign('count',count($goodslist));
        $this->assign('str',$str);
        $this->assign('pantername',$this->pantername);
        $this->display();
    }

    //验证卡余额是否充足
    public function VerifBalance(){
        if(IS_POST){
            $order  = trim(I('post.order',''));
            $cardno = trim(I('post.cardno',''));
            $total  = trim(I('post.total',''));
            $panterid = $this->panterid;
            if($order==''){
                $this->ajaxReturn(array('status'=>0,'msg'=>'订单信息不能为空'));
            }
            if($cardno==''){
                $this->ajaxReturn(array('status'=>0,'msg'=>'卡号不能为空'));
            }
            if(session('cardnos')){
                $arr=session('cardnos');
                if(in_array($cardno,$arr)){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'该卡已刷过卡,请更换卡'));
                }
                $arr[]=$cardno;
                session('cardnos',$arr);
            }else{
                $arr[]=$cardno;
                session('cardnos',$arr);
            }
            $orderInfo = $this->getOrderArr(explode(',',$order));

            $this->valiOrderAmount($orderInfo,$panterid,$total);//验证订单金额
            $this->valiPanterid($panterid);//验证商户信息

            $cardnoInfo= $this->cardAccountInfo($cardno);

            if(session('num')){//存在叠卡消费情况
                if((session('num')==$cardnoInfo['num'])||(session('num')!=0&&$cardnoInfo['num']!=0)){
                    if(session('num')!=0){
                        $amount=session('amount')+$cardnoInfo['amount'];//叠卡总金额
                        if(bcsub($amount,$this->frozen,2)<$total){//叠卡总额不足
                            $amount=$amount-($this->frozen);
                            session('amount',$amount);
                            $cardnoInfo['cut']=$cardnoInfo['amount']-($this->frozen);//叠卡需要减去的金额
                            if(session('cardnoInfos')){//存在两次以上叠卡
                                $arrInfo=session('cardnoInfos');
                                $arrInfo[]=$cardnoInfo;
                                session('cardnoInfos',$arrInfo);
                            }else{
                                $arrInfo[]=$cardnoInfo;
                                session('cardnoInfos',$arrInfo);
                            }
                            $this->ajaxReturn(array('status'=>0,'msg'=>'叠卡总额不足,还差'.($total-$amount)));
                        }else{//叠卡总额充足
                            $cardnoInfo['cut']=$total-session('amount');//最后一张卡需要减去的金额
                            if(session('cardnoInfos')){//存在两次以上叠卡
                                $arrInfo=session('cardnoInfos');
                                $arrInfo[]=$cardnoInfo;
                                session('cardnoInfos',$arrInfo);
                            }else{
                                $arrInfo[]=$cardnoInfo;
                                session('cardnoInfos',$arrInfo);
                            }
                        }
                    }else{//特惠卡
                        $this->ajaxReturn(array('status'=>0,'msg'=>'特惠卡不能参与叠卡消费'));
                    }

                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'非同类卡，不能叠卡消费'));
                }
            }else{
                if($cardnoInfo['num']==0){
                    $amount=bcsub($cardnoInfo['amount'],0,2);
                    if($amount<$total){
                        session('num',$cardnoInfo['num']);
                        session('amount',($cardnoInfo['amount']-$this->frozen));
                        session('cardnoInfo',$cardnoInfo);
                        $this->ajaxReturn(array('status'=>0,'msg'=>'卡内可用余额不足,还差'.($total-$amount)));
                    }
                }else{
                    $amount=bcsub($cardnoInfo['amount'],$this->frozen,2);
                    if($amount<$total){
                        session('num',$cardnoInfo['num']);
                        session('amount',($cardnoInfo['amount']-$this->frozen));
                        session('cardnoInfo',$cardnoInfo);
                        $this->ajaxReturn(array('status'=>0,'msg'=>'卡内可用余额不足,还差'.($total-$amount)));
                    }
                }
                session('cardnoInfo',$cardnoInfo);
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>'卡内余额充足,可正常消费'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'提交订单信息不正常'));
        }
    }

    //清除session
    public function clearSession(){
        $type=$_POST['type'];
        if($type){
            session('num',null);
            session('cardnos',null);
            session('amount',null);
            session('cardnoInfo',null);
            session('cardnoInfos',null);
        }
    }

    //提交订单信息
    public function order(){
        if(IS_POST){
            $order  = trim(I('post.order',''));
            $total  = trim(I('post.total',''));
            $cardno = trim(I('post.cardno',''));
            $panterid = $this->panterid;
            $pantername = $this->pantername;
            $orderInfo = $this->getOrderArr(explode(',',$order));
            if(session('num')){//叠卡消费
                $cardnoInfo= session('cardnoInfo');//主卡账号信息
                $cardnoInfos= session('cardnoInfos');//副卡账号信息
                    $msg = $this->combineConsume($orderInfo,$cardnoInfo,$cardnoInfos,$panterid,$total);
            }else{
                $cardnoInfo= $this->cardAccountInfo($cardno);
                $msg = $this->consume($orderInfo,$cardnoInfo,$panterid,$total);
            }

            $msg['total']=$total;

            $this->success('消费成功');
            echo '<script type="text/javascript"> window.open("';
            echo $this->printUrl.'2&panterid=';
            echo $panterid.'&pantername=';
            echo encode(json_encode($pantername)).'&msg=';
            echo encode(json_encode($msg)).'&order='.encode(json_encode($orderInfo)).'","_blank");</script>';
        }else{
            $this->error('非法参数出入');
        }
    }

    //卡金额合并 以及账户扣钱
    private function getCombine($map,$cardlist){
        foreach($cardlist as $key=>$val){
            $yue=bcsub($val['amount'],$val['cut'],2);
            $accountif=$this->model->table('account')->where(['customid'=>$val['customid'],'type'=>'00'])->save(['amount'=>$yue]);
            $date=time();$placeddate=date('Ymd',$date);$placedtime=date('H:i:s',$date);
            $sql="INSERT INTO kf_combine (cardno,innum,outcardno,outnum,placeddate,placedtime,status,tradeid,amount) VALUES (";
            $sql.="'{$map['cardno']}','{$map['num']}','{$val['cardno']}','{$val['num']}','{$placeddate}','{$placedtime}','1','{$map['tradeid']}','{$val['cut']}')";
            $combineif=$this->model->execute($sql);
            if($accountif==true&&$combineif==true){
                session('num',null);
                session('cardnos',null);
                session('amount',null);
                session('cardnoInfo',null);
                session('cardnoInfos',null);
            }else{
                $this->ajaxReturn(array('status'=>'09','codemsg'=>'数据库操写入失败'));
            }
        }
    }

    //设置菜品
    public function setDishes(){
        $where['panterid'] = $this->panterid;
        $goodslist = KfModel::getNoramlGoodsDesc($where);
        $str = "";
        $num = 0;
        foreach($goodslist as $key=>$val){
            $val['price']=floatval($val['price']);
            $style = "style='margin-left:0px;'";
            if($num===0){
                $str.="<ul> " .$this->setHmtl($val,$style);
            }
            if($num===1||$num===2){
                $str.=$this->setHmtl($val);
            }
            if($num===3){
                $str.=$this->setHmtl($val)."
                 </ul>";
            }
            $num++;
            $num=$num===4?0:$num;
        }
        if($num!==0){
            $str.="</ul>";
        }
        $this->assign('str',$str);
        $this->display();
    }

    //设置菜品是 后台输出页面样式
    private function setHmtl($val,$style=''){
        if($val['status']=='1'){
            return "<li {$style}>
                	<div class='bg'>
                        <span style=\" padding-top:50px;\">{$val['goodsname']}</span>
                        <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥ {$val['price']}</span>
                    </div>

                    <div class='dishes_set'><a href='http://zz.9617777.com/zzkp.php/KfCarnteen/editDish?goodsid={$val["goodsid"]}'><span style='color:#999;' >编辑</span></a><span style='color:#f88b0f;  margin-left:110px; display: inline-block;' value='{$val['goodsid']}' class='under'>下架</span></div>

                </li>";
        }elseif($val['status']=='2'){
            return "<li {$style}>
                	<div class='bg'>
                        <span style=\" padding-top:50px;\">{$val['goodsname']}</span>
                        <span style='color:#f88b0f; font-weight:600; margin-top:10px; font-size:38px;'>¥ {$val['price']}</span>
                    </div>

                    <div class='dishes_set'><a href='http://zz.9617777.com/zzkp.php/KfCarnteen/editDish?goodsid={$val["goodsid"]}'><span style='color:#999;' >编辑</span></a><span style='color:green;  margin-left:110px; display: inline-block;' value='{$val['goodsid']}' class='ondish'>上架</span></div>

                </li>";
        }
    }

    //下架菜品上架
    public function onDish(){
        if(IS_POST){
            $goodsid = trim(I('post.goodsid',''));
            $panterid= $this->panterid;
            $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
            $goodsInfo = $this->goodsInfo($map);
            $goodsInfo['status']=='2' || returnMsg(['status'=>'15','codemsg'=>'该商品不是下架商品']);
            if($this->updateGoodsStatus($map,['status'=>'1'])){
                returnMsg(array('status'=>'1','codemsg'=>'商品上架成功'));
            }else{
                returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
            }
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'参数传入错误'));
        }
    }

    //下架菜品
    public function removeDish(){
        if(IS_POST){
            $goodsid = trim(I('post.goodsid',''));
            $panterid= $this->panterid;
            $map=['goodsid'=>$goodsid,'panterid'=>$panterid];
            $goodsInfo = $this->goodsInfo($map);
            $goodsInfo['status']=='1'||returnMsg(['status'=>'15','codemsg'=>'该商品不是在线商品']);
            if($this->updateGoodsStatus($map,['status'=>'2'])){
                returnMsg(array('status'=>'1','codemsg'=>'商品下架成功'));
            }else{
                returnMsg(array('status'=>'03','codemsg'=>'数据库操作失败'));
            }
        }else{
            returnMsg(array('status'=>'03','codemsg'=>'参数传入错误'));
        }
    }

    //基于菜品号 返回菜品信息
    private function goodsInfo($map){
        if($gooodsInfo = M('kf_goods')->where($map)->find()){
            return $gooodsInfo;
        }else{
            returnMsg(['status'=>'14','codemsg'=>'未查到该商品,请核实']);
        }
    }

    //编辑菜品页面
    public function editDish(){
        $panterid= $this->panterid;
        if($_POST){
            $goodsid=$_POST['goodsid'];
            $goodsname=$_POST['goodsname'];
            $price=$_POST['price'];
            $other=$_POST['other'];
            $normal=$_POST['normal'];
            $sort=$_POST['sort'];
            if($goodsname==''||$goodsname=='请输入菜品名称'){
                $this->ajaxReturn(array('status'=>0,'msg'=>'菜品名称不能为空！'));
            }else{
                if($goodsname=='特殊消费'){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'特殊消费是敏感字段,请换名字！'));
                }
            }

            if($other=='true'&&$normal=='false'){
                $type=2;
            }else{
                $preg ='/^(([1-9]\d{0,9})|0)(\.\d{1,2})?$/';
                $bool = preg_match($preg,$price);
                if(!$bool&&$price<=0) $this->ajaxReturn(array('status'=>0,'msg'=>'请填写有效价格！'));
                $type=1;
            }
            if(!is_numeric($sort)){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写正确的菜品顺序！'));
            }else{

            }
            $result=M('kf_goods')->where(array('goodsid'=>$goodsid,'panterid'=>$panterid))->save(array('goodsname'=>$goodsname,'price'=>$price,'type'=>$type,'sort'=>$sort));
            if($result){
                $this->ajaxReturn(array('status'=>1,'msg'=>'更新成功！'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'更新失败！'));
            }
        }else{
            $goodsid=$_GET['goodsid'];
            $result=M('kf_goods')->where(array('goodsid'=>$goodsid,'panterid'=>$panterid))->find();
            $result['price']=floatval($result['price']);
            $this->assign('goodsid',$goodsid);
            $this->assign('data',$result);
        }
        $this->display();
    }

    //菜品上下架
    private function updateGoodsStatus($where,$map){
        return M('kf_goods')->where($where)->save($map);
    }

    //新增菜品
    public function addDish(){
        $panterid = $this->panterid;
        if(IS_POST){
            $goodsname = trim(I('post.goodsname',''));
            $price     = trim(I('post.price',''));
            $other     = trim(I('post.other',''));
            $normal    = trim(I('post.normal',''));
            $sort    = trim(I('post.sort',''));
            if($goodsname==''||$goodsname=='请输入菜品名称'){
                $this->ajaxReturn(array('status'=>0,'msg'=>'菜品名称不能为空！'));
            }else{
                $map['goodsname'] = $goodsname;
                if($goodsname=='特殊消费'){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'特殊消费是敏感字段,请换名字!'));
                }
            }
            $map['goodsname'] = $goodsname;
            if($normal==='true' && $other==='false'){
                $flag = '1';
                $map['type'] = '1';
                $preg ='/^(([1-9]\d{0,9})|0)(\.\d{1,2})?$/';
                $bool = preg_match($preg,$price);
                if(!$bool&&$price<=0) $this->ajaxReturn(array('status'=>0,'msg'=>'请填写有效价格！'));
                $map['price']   = $price;
                $map['typename']= '主食';
            }elseif($normal==='false' && $other==='true'){
                $map['goodsname'] = $goodsname;
                $map['type'] = '2';
                $map['typename'] ='其他';
                $flag = '2';
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'异常数据传入！'));
            }
            if(!is_numeric($sort)){$this->ajaxReturn(array('status'=>0,'msg'=>'请填写正确的菜品顺序！'));}
            $map['goodsid'] = KfModel::goodsid(['panterid'=>$panterid]);
            $map['panterid']= $panterid;
            $map['sort']= $sort;
            M('kf_goods')->startTrans();
            $goodsif = KfModel::addGoods($map);
            $typeif  = KfModel::addType($map,$flag);
            if($goodsif==true && $typeif==true){
                M('kf_goods')->commit();
                $this->ajaxReturn(array('status'=>1,'msg'=>'新增菜品成功！'));
            }else{
                M('kf_goods')->rollback();
                $this->ajaxReturn(array('status'=>0,'msg'=>'新增失败！'));
            }
        }else{
            $this->display();
        }
    }

    //散装菜品
    public function noprice(){
        $where['panterid'] = $this->panterid;
        $where['type']     = '2';
        $where['status']   = '1';
        $goodslist = KfModel::getNoramlGoods($where);
        $num = 0;
        $str = "";
        foreach($goodslist as $key=>$val){
            if($num===0){
                $str.=" <ul>
                           <li style=' margin-left:0px;'>
                              <div class='bg_02'>
                                <span>{$val['goodsname']}</span>
                                <input type='hidden' name='goodsid' value='{$val['goodsid']}'>
                              </div>
                           </li>

                      ";
            }
            if($num===1){
                $str.="
                         <li>
                            <div class='bg_02'>
                              <span>{$val['goodsname']}</span>
                              <input type='hidden' name='goodsid' value='{$val['goodsid']}'>
                           </div>
                           </li>

                      ";
            }
            if($num==2){
                $str.="
                       <li>
                            <div class='bg_02'>
                              <span>{$val['goodsname']}</span>
                              <input type='hidden' name='goodsid' value='{$val['goodsid']}'>
                           </div>
                        </li>

                      ";
            }
            if($num==3){
                $str.="
                       <li>
                            <div class='bg_02'>
                              <span>{$val['goodsname']}</span>
                              <input type='hidden' name='goodsid' value='{$val['goodsid']}'>
                           </div>
                        </li>

                   </ul>
                      ";
            }
            $num++;
            $num=$num===4?0:$num;
        }
        $str.=" </ul>";
        $this->assign('str',$str);
        $this->display();

    }

    //散装菜品结账
    public function saveNoprice(){
        $panterid = $this->panterid;
        $pantername = $this->pantername;
        $cardno =$_POST['cardno'];
        $order=$_POST['order'];
        $total  = trim(I('post.total',''));
        $orderInfo = $this->getOrderArr(explode(',',$order));
        $cardnoInfo = $this->cardAccountInfo($cardno);
        if(empty($_POST['total'])){
            $this->error('结账金额不能为空');
        }
        if($cardnoInfo['num']==0){
            bcsub($cardnoInfo['amount'],0,2)>=$total || $this->error('卡内可用余额不足');
        }else{
            bcsub($cardnoInfo['amount'],$this->frozen,2)>=$total || $this->error('卡内可用余额不足');
        }
        $goodsInfo=array('goodsid'=>$orderInfo[0][0],'goodsname'=>$orderInfo[0][1]);
        $msg=$this->bulkConsume($goodsInfo,$_POST['total'],$cardnoInfo,$panterid);
        $msg['total']=$total;
        $this->success('消费成功');
        echo '<script type="text/javascript"> window.open("';
        echo $this->printUrl.'3&panterid=';
        echo $panterid.'&pantername=';
        echo encode(json_encode($pantername)).'&msg=';
        echo encode(json_encode($msg)).'&order='.encode(json_encode($orderInfo)).'","_blank");</script>';
    }

    //退菜页面
    public function refund(){
        $cardno = $_GET['cardno'];
        $cardnInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardnInfo['msg']['customid']);
        $customInfo['cardflag']=='on'||$this->error('此卡已退卡,无退菜信息');
        $cardnum =M('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->where(['ca.cardno'=>$cardno])
            ->getField('cu.num');
        $map['cardno']  = $cardno;
        $map['num']     = $cardnum;
        $map['panterid']= $this->panterid;
        $data = KfModel::refundlist($map);
        if($data['status']==='1'){
            $refundlist = $data['data'];
        }else{
            $this->error("{$data['codemsg']}");
        }
        if($refundlist>0){
            $str='';
            $m=0;
            $n=0;
            foreach($refundlist as $k=>$v){
                $n+=$m+1;
                $str.="<tr>
                            <td>{$v['goodsname']}</td>
                            <td>{$v['price']}</td>
                            <td>{$v['num']}</td>
                            <td style=' width:30%; padding:0 3%;'>
                                <div class='center'>
                                    <div id='id{$n}' class='Spinner'></div>
                                </div>
                            </td>
                            <input type='hidden' name='goodsid' value='{$v["goodsid"]}'>
                            <input type='hidden' name='type' value='{$v["type"]}'>
                        </tr>";
            }
        }
        $this->assign('cardno',$cardno);
        $this->assign('str',$str);
        $this->display();
    }

    //退菜操作
    public function refundGoodsOrder(){
        $cardno =$_POST['cardno'];//卡号
        $panterid = $this->panterid;
        $pantername = $this->pantername;
        $total=$_POST['total'];
        $order=$_POST['order'];
        $orderInfo = $this->getOrderArr(explode(',',$order));
        $termno ='00000000';
        $tradetype='31';
        $cardnInfo = $this->cardInfo(__FUNCTION__,$cardno);
        $customInfo = $this->customInfo(__FUNCTION__,$cardnInfo['msg']['customid']);
        $accounInfo = $this->accountInfo(__FUNCTION__,$cardnInfo['msg']['customid']);
        $sum=0;
        $amount=count($orderInfo);
        if(count($orderInfo)<=0){
            $this->error('请选择退菜数量');
        }else{
            foreach($orderInfo as $val){

                $totalNum=$this->refundNum($val,$panterid,$cardno,$customInfo['num']);
                if($totalNum<$val[3]){
                    $this->error('该商品:'.$val[1].'数量不足');
                }
                $list=M('kf_goods')->where(['goodsid'=>$val[0],'type'=>$val[2],'panterid'=>$panterid])->find();
                if($list==true){
                    $sum=bcadd($sum,bcmul($val[3],$val[4],2),2);
                }else{
                    $this->error('未找到商品:'.$val[1]);
                }
            }

            $sum==$total||$this->error('订单总金额不对');
            $customInfo['cardflag']=='on'||$this->error('此卡已退卡,不能退菜');

            $yue=bcadd($accounInfo['msg']['amount'],$total,2);
            $this->model->startTrans();
            //生成消费记录
            $date=time();
            $placeddate=date('Ymd',$date);
            $placedtime=date('H:i:s',$date);
            $tradeid=$termno.date('YmdHis',$date);
            $tac='abcdefgh';
            $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
            $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$total}','0','{$cardnInfo['msg']['customid']}','{$cardno}','{$placedtime}','{$tradetype}','{$tac}','0')";
            $tradeif=$this->model->execute($sql);
            $accountif=M('account')->where(['customid'=>$cardnInfo['msg']['customid'],'type'=>'00'])->save(['amount'=>$yue]);
            //写入订单
            foreach($orderInfo as $val){
                $orderSql="INSERT INTO kf_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
                $orderSql.="'{$cardno}','{$customInfo['num']}','{$val[0]}','{$val[2]}','{$val[1]}','{$val[4]}','{$val[3]}','04','{$placeddate}','{$placedtime}','{$panterid}')";
                $orderif=$this->model->execute($orderSql);
            }
            if($tradeif==true&&$accountif==true&&$orderif==true){
                $this->model->commit();
                $msg=array('tradeid'=>$tradeid,'balance'=>$yue,'cardno'=>$cardno,'total'=>$total,'amount'=>$amount);
                echo '<script type="text/javascript"> window.open("';
                echo $this->printUrl.'4&panterid=';
                echo $panterid.'&pantername=';
                echo encode(json_encode($pantername)).'&msg=';
                echo encode(json_encode($msg)).'&order='.encode(json_encode($orderInfo)).'","_blank");</script>';
                $this->success('退菜成功','elseFunction');
            }else{
                $this->model->rollback();
                $this->error('数据库操作失败');
            }
        }
    }

    /* 返回能可退菜数量
    * @param array $val 订单数据
    * @param string $panterid 商户号
    * $param string $cardno 卡号
    * $cardnum string $cardnum 卡使用次数
    * @return int $getNum 返回总共可以退掉菜品数量
    */
    private function refundNum($val,$panterid,$cardno,$cardnum){
        $getNum=0;
        $mapNum['placeddate']=date('Ymd');
        $mapNum['cardno']=$cardno;
        $mapNum['cardnum']=$cardnum;
        $mapNum['panterid']=$panterid;
        $mapNum['goodsid']=$val[0];
        $mapNum['type']=$val[2];
        $mapNum['price']=$val[4];
        $mapNum['flag']='02';
        $consumeSum=M('kf_order')->where($mapNum)->select();
        if($consumeSum==true){
            $getNum=bcadd($getNum,array_sum(array_column($consumeSum,'num')),0);
        }else{
            return $getNum;
        }
        //查询退菜数量
        $mapNum['flag']='04';
        $refundSum=M('kf_order')->where($mapNum)->select();
        if($refundSum==false){
            return $getNum;
        }else{
            $getNum=bcsub($getNum,array_sum(array_column($refundSum,'num')),0);
            if($getNum<0){
                returnMsg(['status'=>'09','codemsg'=>'该菜品:'.$val['goodsname']."数量为不足，不能退菜"]);
            }
            return $getNum;
        }
    }

    //卡账户信息
    private function cardAccountInfo($cardno){
        $cardInfo = M('cards')->alias('ca')
            ->join('left join customs cu on cu.customid=ca.customid')
            ->join('left join customs_c cc on cc.cid = ca.customid')
            ->join('left join account ac on ac.customid = ca.customid')
            ->where(['ca.cardno'=>$cardno,'ac.type'=>'00'])
            ->field('ca.cardno,ca.status,ca.cardkind,ca.customid,cu.cardflag,cu.num,ac.amount')
            ->select();
        if($cardInfo){
            if($cardInfo[0]['status']!='Y'){
                $this->error('不是正常卡');
            }
            if($cardInfo[0]['cardflag']!='on'){
                $this->error('已经退卡');
            }
            if($cardInfo[0]['cardkind']!='6880'){
                $this->error('卡宾不对');
            }
            return $cardInfo[0];

        }else{
            $this->error('账户信息异常');
        }
    }

    //验证订单金额
    protected function valiOrderAmount($order,$panterid,$realSum){
        $sum = 0;
        foreach($order as $val){
            $list=M('kf_goods')->where(['goodsid'=>$val['0'],'type'=>'1','panterid'=>$panterid])->find();
            if($list==true){
                $sum=bcadd($sum,bcmul($list['price'],$val['3'],2),2);
            }else{
                returnMsg(['status'=>'05','codemsg'=>'未找到该商品','name'=>$val['goodsname']]);
            }
        }
        if($sum==$realSum){
            return true;
        }else{
            $this->error('下单金额与实际金额不符');
        }
    }

    //正常菜品消费
    private function consume($orderInfo,$cardnoInfo,$panterid,$sum){
        $this->model =new Model();
        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $termno ='00000000';
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $type = '1';
        $yue = bcsub($cardnoInfo['amount'],$sum,2);
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$sum}','0','{$cardnoInfo['customid']}','{$cardnoInfo['cardno']}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardnoInfo['customid'],'type'=>'00'])->save(['amount'=>bcsub($cardnoInfo['amount'],$sum,2)]);
        //写入订单
        foreach($orderInfo as $val){
            $orderSql="INSERT INTO kf_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
            $orderSql.="'{$cardnoInfo['cardno']}','{$cardnoInfo['num']}','{$val['0']}','{$type}','{$val['1']}','{$val['2']}','{$val['3']}','02','{$placeddate}','{$placedtime}','{$panterid}')";
            $orderif=$this->model->execute($orderSql);
            if(!$orderif){
                break;
            }
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            session('cardnos',null);session('num',null);session('cardnoInfo',null);
            $this->model->commit();
            return ['tradeid'=>$tradeid,'balance'=>$yue,'cardno'=>$cardnoInfo['cardno']];
        }else{
            $this->model->rollback();
            $this->error('消费时数据库操作失败');
        }
    }

    //正常菜品和卡消费
    public function combineConsume($orderInfo,$cardnoInfo,$cardnoInfos,$panterid,$sum){
        $this->model =new Model();
        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $termno ='00000000';
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $type = '1';
        //主卡信息
        $cardnoInfo['tradeid']=$tradeid;
        $this->getCombine($cardnoInfo,$cardnoInfos);
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$sum}','0','{$cardnoInfo['customid']}','{$cardnoInfo['cardno']}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        //更新主卡账号金额
        $accountif=M('account')->where(['customid'=>$cardnoInfo['customid'],'type'=>'00'])->save(['amount'=>$this->frozen]);
        //写入订单
        foreach($orderInfo as $val){
            $orderSql="INSERT INTO kf_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
            $orderSql.="'{$cardnoInfo['cardno']}','{$cardnoInfo['num']}','{$val['0']}','{$type}','{$val['1']}','{$val['2']}','{$val['3']}','02','{$placeddate}','{$placedtime}','{$panterid}')";
            $orderif=$this->model->execute($orderSql);
            if(!$orderif){
                break;
            }
        }
        if($tradeif==true&&$accountif==true&&$orderif==true){
            session('cardnos',null);session('num',null);session('cardnoInfo',null);
            $this->model->commit();
            return ['tradeid'=>$tradeid,'balance'=>$this->frozen,'cardno'=>$cardnoInfo['cardno']];
        }else{
            $this->model->rollback();
            $this->error('消费时数据库操作失败');
        }
    }

    //散装消费
    private function bulkConsume($goodsInfo,$price,$cardnoInfo,$panterid){
        $this->model =new Model();
        $this->model->startTrans();
        $date=time();
        $placeddate=date('Ymd',$date);
        $placedtime=date('H:i:s',$date);
        $termno ='00000000';
        $tradeid=$termno.date('YmdHis',$date);
        $tac='abcdefgh';
        $flag='0';
        $yue = bcsub($cardnoInfo['amount'],$price,2);
        $sql="INSERT INTO trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,tradeamount,tradepoint,customid,cardno,placedtime,tradetype,tac,flag)VALUES(";
        $sql.="'{$termno}','{$termno}','{$panterid}','{$tradeid}','{$placeddate}','{$price}','0','{$cardnoInfo['customid']}','{$cardnoInfo['cardno']}','{$placedtime}','00','{$tac}','0')";
        $tradeif=$this->model->execute($sql);
        $accountif=M('account')->where(['customid'=>$cardnoInfo['customid'],'type'=>'00'])->save(['amount'=>bcsub($cardnoInfo['amount'],$price,2)]);
        //写入订单
        $orderSql="INSERT INTO kf_order(tradeid,cardno,cardnum,goodsid,type,goodsname,price,num,flag,placeddate,placedtime,panterid) VALUES ('{$tradeid}',";
        $orderSql.="'{$cardnoInfo['cardno']}','{$cardnoInfo['num']}','{$goodsInfo['goodsid']}','2','{$goodsInfo['goodsname']}','{$price}','1','02','{$placeddate}','{$placedtime}','{$panterid}')";
        $orderif=$this->model->execute($orderSql);
        //更新kf_goods订单金额
        $updatesql="UPDATE kf_goods set price={$price} where goodsid='{$goodsInfo['goodsid']}' and type='2' and panterid='{$panterid}'";
        $updateif=$this->model->execute($updatesql);
        if($tradeif==true&&$accountif==true&&$orderif==true&&$updateif==true){
            $this->model->commit();
            return ['tradeid'=>$tradeid,'balance'=>$yue,'cardno'=>$cardnoInfo['cardno']];
        }else{
            $this->model->rollback();
            $this->error('消费时数据库操作失败');
        }
    }

    //显示订单是 对接到的数组进行处理
    protected function getOrderArr($info){
        $count=count($info);
        $count%5==0||$this->error('订单信息异常');
        for($i=0;$i<($count/5);$i++){
            $offset = $i*5;
            $arr[] = array_slice($info,$offset,5);
        }
        return $arr;
    }

    //商户日结算页面
    public function daySettlement(){
        $data=$this->daliyBalance();
        $this->assign('panterid',$this->panterid);
        $this->assign('pantername',$this->pantername);
        $this->assign('printUrl',$this->printUrl);
        $this->assign('data',$data);
        $this->display();
    }

    //商户日结算数据
    public function daliyBalance(){
        $panterid=$this->panterid;
        $this->valiPanterid(__FUNCTION__,$panterid,'商户日结算校验商户');
        $map['panterid']=array('eq',$panterid);
        $map['tradetype']=array('eq','00');
        $datetime=array(date('Ymd',time()),date('Ymd',time()-86400),date('Ymd',time()-86400*2),date('Ymd',time()-86400*3),date('Ymd',time()-86400*4),date('Ymd',time()-86400*5),date('Ymd',time()-86400*6));
        foreach($datetime as $k=>$v){
            $map = ['panterid'=>$panterid,'tradetype'=>'00','flag'=>'0','placeddate'=>$v];
            $consume = $this->panterTradeInfo($map);
            //退菜金额
            $map['tradetype'] = '31';
            $refund = $this->panterTradeInfo($map);
            $data[]=array('datetime'=>substr($v,0,4).'.'.substr($v,4,2).'.'.substr($v,6,2),'consume'=>floatval($consume),'refund'=>floatval($refund));
        }
        return $data;
    }

    //日结算统计 商户的各种交易金额 比如正常消费，退菜，退卡
    private function panterTradeInfo($map){
        $info =  $this->model->table('trade_wastebooks')->where($map)->field('sum(tradeamount) as tradeamount')->select();
        return $info[0]['tradeamount']?:0.00;
    }

    //其他功能
    public function elseFunction(){
        $this->display();
    }

    //验证密码
    public function setPassword(){
        $password='37869652';
        $postPassword=$_POST['password'];
        if($postPassword==''){
            $this->ajaxReturn(array('status'=>0,'msg'=>'密码不能为空！'));
        }
        if($password===$postPassword){
            $this->ajaxReturn(array('status'=>1,'msg'=>'密码正确！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'密码不正确！'));
        }
    }

    //验证商户信息
    private function valiPanterid($method,$panterid,$description){
        $list=M('panters')->where(['panterid'=>$panterid])->find();
        if($list==false){
            $error=['status'=>'6','codemsg'=>'未查询到该商户信息,'.$panterid.'请核实'];
            $this->errorHandle($method,$error,$description);
        }
        if($list['revorkflg']!='N'){
            $error=['status'=>'12','codemsg'=>'该商户禁用中'];
            $this->errorHandle($method,$error,$description);
        }
        if($list['parent']!=$this->parent){
            $error=['status'=>'13','codemsg'=>'不是开封大食堂商户'];
            $this->errorHandle($method,$error,$description);
        }
        return $list;
    }

    //解密卡号
    public function decryptCardno(){
        $str=$_POST['cardno'];
        $arr1=str_split(substr($str,"2","10"));
        $arr2=str_split(substr($str,"12","9"));
        $size = count($arr1) > count($arr2) ? count($arr1) : count($arr2); //取出元素最多的数组循环
        $arr = array();
        for($i=0;$i < $size; $i++){
            array_push($arr,$arr1[$i]); //将数组压入新的变量
            array_push($arr,$arr2[$i]);//将数组压入新的变量
        }
        array_pop($arr);
        $result=array_reverse($arr);
        $startnum=array_shift($result);
        $endnum=array_pop($result);
        array_push($result,$startnum);
        array_unshift($result,$endnum);
        $cardno=implode("",$result);
        echo json_encode($this->getYue($cardno));
    }

    //获取卡余额
    public function getYue($cardno){
        $cardInfo=$this->cardInfo(__FUNCTION__,$cardno);
        if($cardInfo['status']!='01'){
            return $cardInfo;
        }else{
            $cardInfo=$cardInfo['msg'];
            $account=$this->accountInfo(__FUNCTION__,$cardInfo['customid']);
            if($account['status']!='01'){
                return $account;
            }else{
                $account['msg']['cardno']=$cardno;
                $account['msg']['amount']=floatval($account['msg']['amount']);
                return array('status'=>'01','msg'=>$account['msg']);
            }
        }
    }

    //查询卡信息
    private function cardInfo($method,$cardno){
        if($cardInfo = $this->model->table('cards')->where(['cardno'=>$cardno])->find()){
            if($cardInfo['status']!='Y'){
                $error= ['status'=>'02','msg'=>'卡号:'.$cardno.'不是正常卡'];
                $this->errorHandle($method,$error,'查询卡号信息操作');
                $cardno=substr($cardno,-6);
                return array('status'=>'02','msg'=>'卡号:'.$cardno.'不是正常卡');
            }else{
                return array('status'=>'01','msg'=>$cardInfo);
            }
        }else{
            $error= ['status'=>'02','msg'=>'卡号:'.$cardno.'不存在'];
            $this->errorHandle($method,$error,'查询卡号信息操作');
            $cardno=substr($cardno,-6);
            return array('status'=>'02','msg'=>'卡号:'.$cardno.'不存在');
        }
    }

    //查询会员信息
    private function customInfo($method,$customid){
        if($customInfo = $this->model->table('customs')->where(['customid'=>$customid])->find()){
            return $customInfo;
        }else{
            $error=['status'=>'03','codemsg'=>'此会员:'.$customid.'不存在'];
            $this->errorHandle($method,$error,'查询会员信息操作');
        }
    }

    //查询账户信息
    protected function accountInfo($method,$customid){
        if($accountInfo = $this->model->table('account')->where(['customid'=>$customid,'type'=>'00'])->find()){
            return  array('status'=>'01','msg'=>$accountInfo);
        }else{
            $error=['status'=>'04','msg'=>'卡会员:'.$customid.'账户信息不存在'];
            $this->errorHandle($method,$error,'查询卡对应会员的账户信息');
            return array('status'=>'04','msg'=>'卡会员:'.$customid.'账户信息不存在');
        }
    }

    /*
     * 查出非法信息处理
     * @param string $method 那个方法调用
     * @param array  要返回的错误信息
     * @param string $descrition 信息描述
     */
    private function errorHandle($method,$error,$description){
        $this->errorLog($method,$error,$description);
    }

    //错误信息日志记录错误
    private function errorLog($method,$error,$description){
        $filename = PUBLIC_PATH.'logs/kfcarteen_error/'.date('Ymd',time()).'.txt';
        $this->getDir($filename);
        $str=date('Y m d H:i:s',time()).'  '.$method.'------------';
        $str.=json_encode($error,JSON_UNESCAPED_UNICODE)."\t ";
        $str.=$description."\t\n";
        file_put_contents($filename,$str,FILE_APPEND);
    }

    //获取文件目录
    private function getDir($filename){
        $path = pathinfo($filename);
        $path = $path['dirname']."/";
        $path=str_replace("\\","/",$path);
        file_exists($path)||mkdir($path,0777,true);
    }

    //更新kf_goods表sort数据
    public function  updateSort(){
        $model = new model();
        $restult=M('kf_goods')->select();
        $m=0;
        foreach($restult as $k=>$v){
            $Sql="UPDATE kf_goods set sort={$m} where goodsid={$v['goodsid']} and panterid={$v['panterid']} and type={$v['type']} and goodsname='"."{$v['goodsname']}'";
            $model->execute($Sql);
            $m++;
        }
    }
}