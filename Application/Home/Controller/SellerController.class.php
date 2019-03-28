<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class SellerController extends CommonController {
	public function _initialize(){
        parent::_initialize();
    }
    //营销产品设置
    function product(){
        $model=new Model();
        $quanid = trim(I('get.quanid',''));//开始日期
        $quanname= trim(I('get.quanname',''));//结束日期
        $pname=trim(I('get.pname',''));
				$quanid = $quanid=="营销产品编号"?"":$quanid;
				$quanname = $quanname=="营销产品名称"?"":$quanname;
				$pname = $pname=="机构/商户名称"?"":$pname;
        $cateArr=array('','住宿','餐饮','饼屋','烧烤','电影','活动','花卉','教育','健身房','洗衣房','其他');
        if($quanid!=''){
            $where['q.quanid']= $quanid;
            $this->assign('quanid',$quanid);
            $map['quanid']=$quanid;
        }
        if($quanname!=''){
            $where['q.quanname']= array('like','%'.$quanname.'%');
            $this->assign('quanname',$quanname);
            $map['quanname']=$quanname;
        }
        if($pname!=''){
            $where['p.namechinese']= array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where['_string']="(q.panterid='".$this->panterid."' or p.parent='".$this->panterid."') or q.utype=1";
        }else{
            $this->assign('is_admin',1);
        }
        $field='q.*,p.namechinese,p.panterid pid';
        $count=$model->table('quankind')->alias('q')
            ->join('left join __PANTERS__ p on p.panterid=q.panterid')
            ->where($where)->field($field)->count();
        $p=new \Think\Page($count, 12);
        $list=$model->table('quankind')->alias('q')
            ->join('left join __PANTERS__ p on p.panterid=q.panterid')
            ->where($where)->field($field)->order('q.quanid')->limit($p->firstRow.','.$p->listRows)->select();
        //echo $model->getLastSql();
        foreach ($list as $key => $val) {
            $val['amount']=floatval($val['amount']);
            $val['cate']=$cateArr[$val['cate']];
            $list[$key]=$val;
        }
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
    }
	//营销产品添加
	function addProduct(){
		if(IS_POST){
			$m=M();
			$quanname=trim($_POST['quanname']);
			$startdate=$_POST['startdate'];
			$startdate=str_replace("-","",$startdate);
			$enddate=$_POST['enddate'];
			$enddate=str_replace("-","",$enddate);
			$panterid=trim($_POST['panterid']);
            $pname=trim($_POST['pname']);
			$amount=trim($_POST['amount']);
			$transfer=trim($_POST['transfer']);
			$memo=trim($_POST['memo']);
			$sort=trim($_POST['sort']);
            $validaty=trim($_POST['validaty']);
            $vtype=trim(trim($_POST['vtype']));
            $utype=trim(trim($_POST['utype']));
            $cate=trim(trim($_POST['cate']));
            if(empty($quanname)){
                $this->error('营销产品不能为空',U('Seller/addProduct'));
            }
            if(empty($amount)){
                $this->error('产品价格不能为空');
            }
            $preg = "/(^0\.\d{1,2}$)|(^[1-9]\d*\.\d{1,2}$)|(^[1-9]\d*$)/";
            $boo =preg_match($preg, $amount);
            if(!$boo){
                $this->error('金额最多精确到分');
            }
            if(empty($vtype)){
                $this->error('劵类型必选');
            }else{
                if($vtype==1){
                    $validaty=0;
                    if(empty($startdate)||empty($enddate)){
                        $this->error('活动起始日期或结束日期缺失');
                    }
                }else{
                    $startdate='';
                    $enddate='';
                    if(empty($validaty)){
                        $this->error('劵有效期缺失');
                    }
                    if(!preg_match('/^[1-9]\d*$/',$validaty)){
                        $this->error('有效期格式有误');
                    }
                }
            }
            if(empty($utype)){
                $this->error('劵适用类型必选');
            }else{
                if($utype==1){
                    $panterid=$_REQUEST['parent'];
                    if($this->panterid!=='FFFFFFFF'&&$this->panterid!=='00000013'){
                        $this->error('非管理员无权添加通用劵');
                    }
                }else{
                    if(empty($panterid)){
                        if(empty($pname)){
                            $this->error('商户必选',U('Seller/addProduct'));
                        }else{
                            $map=array('namechinese'=>$pname);
                            $panter=D('panters')->field('panterid')->where($map)->find();
                            if($panter==false){
                                $this->error('查无此商户记录,请确定商户名称无误');
                            }else{
                                $data['panterid']=$panter['panterid'];
                            }
                        }
                    }
                }
            }
            if(empty($cate)||$cate=='-1'){
                $this->error('请选择劵所属品类');
            }
            $quanid=$this->getnextcode('quankind',8);
            $sql="insert into quankind values('{$quanid}','{$quanname}','{$startdate}','{$enddate}','{$memo}','{$panterid}','{$amount}','{$memo}',{$validaty},{$vtype},{$utype},{$cate},2,'{$transfer}','{$sort}') ";
            //echo $sql;exit;
			$r=$m->execute($sql);
			if($r){
                $sql="insert into quanpanter values('{$quanid}','{$panterid}')";
                $m->execute($sql);
				$this->assign("jumpUrl",__APP__."/Seller/product");
				$this->success("添加成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{
			if($this->panterid=='FFFFFFFF'||$this->panterid=='00000013'){
                $this->assign('is_admin',1);
            }else{
                $panter=M('panters')->where(array('panterid'=>$this->panterid))->field('namechinese pname')->find();
                $this->assign('panterid',$this->panterid);
                $this->assign('pname',$panter['pname']);
                $this->assign('is_admin',0);
            }
			$this->display();
		}
	}
    //营销产品修改
	function editProduct(){
		if(IS_POST){
			$m=M();
			//$quanid_old=$_POST['quanid_old'];
			$quanid=$_POST['quanid'];
			$quanname=$_POST['quanname'];
			$startdate=$_POST['startdate'];
			$startdate=str_replace("-","",$startdate);
			$enddate=$_POST['enddate'];
			$enddate=str_replace("-","",$enddate);
            $validaty=$_POST['validaty'];
			$amount=$_POST['amount'];
			$transfer=$_POST['transfer'];
			$memo=$_POST['memo'];
			$sort=$_POST['sort'];
            $cate=$_POST['cate'];
            if($quanname==''){
                $this->error('请填写营销产品名称',U('Seller/editProduct',array('quanid'=>$quanid)));
            }
//            if($panterid==''){
//                $this->error('请选择发卡机构/商户',U('Seller/editProduct',array('quanid'=>$quanid)));
//            }
            if($amount==''){
                $this->error('请填写产品价格',U('Seller/editProduct',array('quanid'=>$quanid)));
            }
            $preg = "/(^0\.\d{1,2}$)|(^[1-9]\d*\.\d{1,2}$)|(^[1-9]\d*$)/";
            $boo =preg_match($preg, $amount);
            if(!$boo){
                $this->error('金额最多精确到分');
            }
            $c=M('quancz')->where(array('quanid'=>$quanid))->count();
            $quaninfo=M('quankind')->where(array('quanid'=>$quanid))->find();
            if($c>0){
                if($quaninfo['vtype']==1){
                    if($quaninfo['startdate']!==$startdate||$quaninfo['enddate']!==$enddate){
                        $this->error('该劵已有充值记录，不能修改活动起止日期');
                    }else{
                        $string="startdate='{$startdate}',enddate='{$enddate}'";
                    }
                }else{
                    if($quaninfo['validaty']!=$validaty){
                        $this->error('该劵已有充值记录，不能修改劵有效期');
                    }else{
                        $string="validaty='{$validaty}'";
                    }
                }
            }else{
                if($quaninfo['vtype']==1){
                    if(empty($startdate)||empty($enddate)){
                        $this->error('请选择活动劵起止日期');
                    }
                    $string="startdate='{$startdate}',enddate='{$enddate}'";
                }else{
                    if(empty($validaty)){
                        $this->error('请填写劵有效期');
                    }
                    $string="validaty={$validaty}";
                }
            }
            if(empty($cate)||$cate=='-1'){
                $this->error('请填写劵类型');
            }
            $sql="update quankind set quanname='{$quanname}',".$string.",amount={$amount},transfer='{$transfer}',memo='{$memo}',sort='{$sort}',cate={$cate} where quanid='{$quanid}'";
            $r=$m->execute($sql);
			if($r){
				$this->assign("jumpUrl",__APP__."/Seller/product");
				$this->success("修改成功");
			}
			else{
				$this->error("操作失败");
			}
		}
		else{
			$m=M();
			$quanid=$_GET['quanid'];
            $list=M('quankind')->alias('q')->join(" left join panters p on p.panterid=q.panterid")
                ->where('quanid='.$quanid)->field('q.*,p.namechinese pname,p.panterid pid')->find();
			//$list=$m->query("select * from quankind where quanid='{$quanid}'");
			if(empty($list)){
				$this->error("未查到数据");
			}
			$list['amount']=floatval($list['amount']);
			$this->assign('list',$list);
			$pList=$m->query("select panterid,namechinese from panters");
			$this->assign('pList',$pList);
            if($this->panterid!='FFFFFFFF'){
                $this->assign('is_admin',0);
            }else{
                $this->assign('is_admin',1);
            }
			$this->display();
		}
	}

	//营销商户设置
	function setProduct(){
		$m=M('quankind');
		if(IS_POST)
	 {
		$startdate = trim(I("post.startdate",""));
		$sellname = trim(I("post.sellname",""));
		$startdate = $startdate=="活动开始时间"?"":$startdate;
		$sellname = $sellname=="营销名称"?"":$sellname;
		if($startdate!="")
		{
			$start = str_replace("-","",$startdate);
			$where['startdate'] = array("egt",$start);
			$this->assign("startdate",$startdate);
		}
		if($sellname!="")
		{
			$where['quanname'] = array("like","%".$sellname."%");
			$this->assign("sellname",$sellname);
		}
	}
        $count=$m->alias('q')->where($where)->count();
        $p=new \Think\Page($count, 12);
        $list=$m->alias('q')->where($where)->limit($p->firstRow.','.$p->listRows)
                    ->select();
        $page = $p->show();
		$this->assign('list',$list);
        $this->assign('page',$page);
		$this->display();
	}

	//获得卡片信息
    function getcards(){
        $cards = D('cards');
        $sex    = trim(I('post.sex',''));
        $cardno  = trim(I('post.cardno',''));
        $cname  = trim(I('post.cname',''));
        $customsid = trim(I('post.customsid',''));
				$cardno = $cardno=="卡号"?"":$cardno;
				$cname = $cname=="会员名称"?"":$cname;
				$customsid = $customsid=="会员编号"?"":$customsid;
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
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $where['a.status']='Y';
        $field='a.cardno,a.status,a.customid as customid1,b.customid,';
        $field.='b.namechinese cuname,b.customlevel,b.linktel,a.exdate';
        $card_list=$cards->alias('a')
                ->join('left join customs_c c on a.customid=c.cid')
                ->join('left join customs b on c.customid=b.customid')
                ->join('__PANTERS__ p on p.panterid=a.panterid')
                ->where($where)->field($field)
                ->order('a.cardno desc')->select();
        //echo $cards->getLastSql();
        if($card_list){
            $res['data']=$card_list;
            $res['success']=1;
        }else{
            $res['msg']='没有此记录';
        }
        echo json_encode($res);
    }
	//营销产品充值
	function productpay(){
		$quankind = D('quankind');
		$account = D('account');
		$quancz	= D('quancz');
		$nowdate=date('Ymd');
		$wquank['startdate']=array('elt',$nowdate);
		$wquank['enddate']=array('egt',$nowdate);
        //$wquank['_string']=" (q.startdate<='".$nowdate."' and q.enddate>='".$nowdate."') or q.startdate is null ";
        if($this->panterid!='FFFFFFFF'){
            $wquank['_string'].=" p.panterid='".$this->panterid."' OR p.parent='".$this->panterid."'";
        }
        $wquank['q.atype']=1;
		$quankinds= $quankind->alias('q')->join('left join __PANTERS__ p on p.panterid=q.panterid')
            ->where($wquank)->field('quanid,quanname')->order('quanid desc')->select();
		$this->assign('quankinds',$quankinds);
		if(IS_POST){
			$status = trim(I('post.status',''));
			$customid = trim(I('post.customid',''));
			$paynumber = trim(I('post.paynumber',''));
            if($paynumber==''){
                $this->error('充值数量必须填写',U('Seller/productpay'));
            }
			$quanid = I('post.quanid','');
            if($quanid==''){
                $this->error('券类必选',U('Seller/productpay'));
            }
			$cardno = trim(I('post.cardno',''));
			//卡号不能为空
		  if($cardno=="")
			{
				$this->error('卡号不能为空',U('Seller/productpay'));
			}
			$waccount['customid']=$customid;
			$waccount['type']='02';
			$waccount['quanid']=$quanid;
			$userid =  $this->userid;
        	$panterid=$this->panterid;
			$accounts=$account->where($waccount)->field('accountid')->find();
			if($accounts==false){
				//$acoid = $this->getnextcode('account',8);
                $acoid = $this->getFieldNextNumber('accountid');
                $accountSql="INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acoid."','".$customid."','".$paynumber."','02','".$quanid."')";
            	$acountsif= $account->execute($accountSql);
			}else{
                $accountSql="update account set amount=nvl(amount,0)+".$paynumber." where customid='".$customid."' and type='02' and quanid='".$quanid."'";
                $acountsif= $account->execute($accountSql);
			}
            $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);

            $sql="insert into quancz values('".$quanid."','".$paynumber."','".date('Ymd')."','".date('H:i:s')."','".$panterid."','".$userid."','".$customid."','".$quanpurchaseid."',0)";
            $quanczs=$quancz->execute($sql);
			if($acountsif==false || $quanczs==false){
				$this->error('充值失败',U('Seller/productpay'));
			}else{
               $this->success('充值成功',U('Seller/productpay'));
            }
		}else{
            $quancz=D('quancz');
            $start = I('get.startdate','');
            $end = I('get.enddate','');
            $customidc = trim(I('get.customidc',''));
            $cnamec	= trim(I('get.cnamec',''));
            $cardnoc    = trim(I('get.cardnoc',''));
            $linktel	= trim(I('get.linktel',''));
            $quanname	= trim(I('get.quanname',''));
            $customid = $customidc=="会员编号"?"":$customidc;
            $cname = $cnamec=="会员名称"?"":$cnamec;
            $cardno = $cardnoc=="卡号"?"":$cardnoc;
            $linktel = $linktel=="手机号"?"":$linktel;
            $quanname = $quanname=="劵名称"?"":$quanname;
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
            if($customid!=''){
                $where['b.customid'] = $customid;
                $this->assign('customidc',$customid);
                $map['customidc']=$customid;
            }
            if($cname!=''){
                $where['b.namechinese']=array('like','%'.$cname.'%');
                $this->assign('cnamec',$cname);
                $map['cnamec']=$cname;
            }
            if($cardno!=''){
                $where['f.cardno']=$cardno;
                $this->assign('cardnoc',$cardno);
                $map['cardnoc']=$cardno;
            }
            if($linktel!=''){
                $where['b.linktel']=$linktel;
                $this->assign('linktel',$linktel);
                $map['linktel']=$linktel;
            }
            if($quanname!=''){
                $where['d.quanname']=array('like','%'.$quanname.'%');
                $this->assign('quanname',$quanname);
                $map['quanname']=$quanname;
            }
            if($this->panterid!='FFFFFFFF'){
                $where1['c.panterid']=$this->panterid;
                $where1['c.parent']=$this->panterid;
                $where1['_logic']='OR';
                $where['_complex']=$where1;
            }
            $field='f.cardno,a.customid,a.placeddate,a.placedtime,a.quanid,a.userid,a.amount,';
            $field.='b.customid as customid1,b.namechinese,b.linktel,d.quanname,d.startdate,d.enddate,d.amount quanidprice,c.namechinese as pantername';
            $count=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')
                ->join('left join customs_c m on a.customid=m.cid')
                ->join('left join customs b on m.customid=b.customid')
                ->join('left join panters c on a.panterid=c.panterid')
                ->join('left join quankind d on a.quanid=d.quanid')->where($where)
                ->field($field)->count();
            $p=new \Think\Page($count, 12);
            $quancz_list=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')
                ->join('left join customs_c m on a.customid=m.cid')
                ->join('left join customs b on m.customid=b.customid')
                ->join('left join panters c on a.panterid=c.panterid')
                ->join('left join quankind d on a.quanid=d.quanid')->where($where)
                ->field($field)->limit($p->firstRow.','.$p->listRows)
                ->order('a.placeddate desc,a.placedtime desc')->select();
            foreach($quancz_list as $k=>$v){
                $v['amount']=floatval($v['amount']);
                $v['quanidprice']=floatval($v['quanidprice']);
                $v['totalamount']=$v['quanidprice']*$v['amount'];
                $result[]=$v;
            }
            session('quxcel',$where);
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $page = $p->show();
            $this->assign('list',$result);
            $this->assign('page',$page);
            $this->display();
        }
	}
	function getquank(){
		$customs = D('customs');
        $cardno = I('post.cardno','');
        if($cardno!=''){
            $where['d.cardno']=$cardno;
        }
        $account_list=$customs->alias('b')->join('left join customs_c m on b.customid=m.customid')
                ->join('left join cards d on m.cid=d.customid')
                ->where($where)->field('d.cardno,d.customid,b.namechinese,b.personidtype,b.personid,b.linktel,d.status')
                ->order('d.cardno desc')->find();
        if($account_list){
            $res['data']=$account_list;
            $res['success']=1;
        }else{
            $res['msg']='信息不能为空';
        }
        echo json_encode($res);
	}
	//营销产品批量充值
	function batchproductpay(){
		$panterid=$this->panterid;
        $userid =  $this->userid;
        if (!empty( $_FILES['file_stu']['name'])){
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_types [count ( $file_types ) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower ( $file_type ) != "xls"&&strtolower ( $file_type ) != "xlsx")
            {
               $this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
            $savePath = './Public/upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file,$savePath.$file_name ))
            {
                $this->error('上传失败');
            }
            $exceldate=$this->import_excel($savePath.$file_name,1);
            $cards  = D('cards');
            $quankind = D('quankind');
            $panters  = D('panters');
            $account  = D('account');
            $quancz   = D('quancz');
            $counts=0;
            $batchbuyBatchLog=array();
            foreach ($exceldate as $key => $value) {
                $batchbuyBatchLog[$key]['datetime']=date('Y-m-d H:i:s',time());
                $batchbuyBatchLog[$key]['cardno']=$cardno=$value[0];
                $batchbuyBatchLog[$key]['tradeamount']=$tradeamount=$value[1];
                $batchbuyBatchLog[$key]['quanid']=$quanid=$value[2];
                $cards->startTrans();
                if($panterid!='FFFFFFFF'){
                    $where['a.panterid']=$panterid;
                    $wquank['a.panterid']=$panterid;
                    $wquank['b.parent'] = $panterid;
                }
                if($cardno!=''){
                    $where['a.cardno']=$cardno;
                }else{
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号不能为空，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                if($tradeamount=='' || floatval($tradeamount)<=0){
                	$batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='充值数量错误!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $where['a.status']='Y';
                $card=$cards->alias('a')->join('left join customs_c c on a.customid=c.cid')
                			->join('left join customs b on c.customid=b.customid')->where($where)->field('a.*,b.customid as customid1')->find();
                if($card==false){
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'错误，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $customid = $card['customid'];
                $customid1= $card['customid1'];
                $nowdate=date('Ymd');
				$wquank['a.startdate']=array('elt',$nowdate);
				$wquank['a.enddate']=array('egt',$nowdate);
				$wquank['a.quanid'] =$quanid;
                $quankinds= $quankind->alias('a')->join('left join panters b on a.panterid=b.panterid')->where($wquank)->field('a.quanid,a.quanname,a.panterid')->find();
                if($quankinds==false){
                	$batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='营销券'.$quanid.'错误，请核实!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
                }
                $quanname=$quankinds['quanname'];
                $waccount['customid']=$customid;
				$waccount['type']='02';
				$waccount['quanid']=$quanid;
				$accounts=$account->where($waccount)->field('accountid')->find();
				if($accounts==false){
					//$acoid = $this->getnextcode('account',8);
                    $acoid=$this->getFieldNextNumber('accountid');
	            	$acountsif= $account->execute("INSERT INTO account(accountid,customid,amount,type,quanid) VALUES('".$acoid."','".$customid."','".$tradeamount."','02','".$quanid."')");
				}else{
					$acountsif= $account->execute("update account set amount=nvl(amount,0)+".$tradeamount." where customid='".$customid."' and type='02' and quanid='".$quanid."'");
				}
                $quanpurchaseid = $this->getnextcode('quanpurchaseid',8);
				$quanczs=$quancz->execute("insert into quancz values('".$quanid."','".$tradeamount."','".date('Ymd')."','".date('H:i:s')."','".$panterid."','".$userid."','".$customid."','".$quanpurchaseid."')");
				if($acountsif==false || $quanczs==false){
					$cards->rollback();
                    $batchbuyBatchLog[$key]['status']=0;
                    $batchbuyBatchLog[$key]['msg']='卡号'.$cardno.'营销产品批量充值有异常,请联系系统管理员!';
                    $this->batchbuyLogs($batchbuyBatchLog);
                    continue;
				}else{
                    $cards->commit();
                    $batchbuyBatchLog[$key]['status']=1;
                    $batchbuyBatchLog[$key]['msg']='营销产品批量充值成功!';
                }
                if(!empty($batchbuyBatchLog)){
                    $this->batchbuyLogs($batchbuyBatchLog);
                }
                $counts++;
            }
            $this->success('营销产品批量充值成功'.$counts.'条',U('Seller/batchproductpay'));
        }
		$this->display();
	}
	//营销产品充值查询
	function productquery(){
        $cardnoc = trim(I('get.cardnoc',''));
        $customsid=trim(I('get.customsid',''));
        $cuname  = trim(I('get.cuname',''));
        $cquanid = trim(I('get.cquanid',''));
        $cquanname=trim(I('get.cquanname',''));
        $cardnoc = $cardnoc=="卡号"?"":$cardnoc;
        $customsid = $customsid=="会员编号"?"":$customsid;
        $cuname = $cuname=="会员名称"?"":$cuname;
        $cquanid = $cquanid=="营销产品编号"?"":$cquanid;
        $cquanname = $cquanname=="营销产品名称"?"":$cquanname;
        if($cardnoc!=''){
            $where['d.cardno']=$cardnoc;
            $this->assign('cardnoc',$cardnoc);
            $map['cardnoc']=$cardnoc;
        }
        if($customsid!=''){
            $where['b.customid']=$customsid;
            $this->assign('customsid',$customsid);
            $map['customsid']=$customsid;
        }
        if($cuname!=''){
            $where['b.namechinese']=array('like','%'.$cuname.'%');
            $this->assign('cuname',$cuname);
            $map['cuname']=$cuname;
        }
        if($cquanid!=''){
            $where['a.quanid']=$cquanid;
            $this->assign('cquanid',$cquanid);
            $map['cquanid']=$cquanid;
        }
        if($cquanname!=''){
            $where['c.quanname']=array('like','%'.$cquanname.'%');
            $this->assign('cquanname',$cquanname);
            $map['cquanname']=$cquanname;
        }
        if($this->panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $account=M('account');
        $where['a.type']='02';
        $field='d.cardno,a.customid as customid1,b.customid,b.namechinese,a.quanid,a.accountid,a.amount,c.quanname';
        $count=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)
            ->order('d.cardno desc')->count();
        $p=new \Think\Page($count, 15);
        $account_list=$account->alias('a')->join('quankind c on a.quanid=c.quanid')
            ->join('cards d on d.customid=a.customid')
            ->join('customs_c m on m.cid=d.customid')
            ->join('customs b on b.customid=m.customid')
            ->join('__PANTERS__ p on p.panterid=c.panterid')
            ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)
            ->order('d.cardno desc')->select();
        //echo $account->getLastSql();
        $page = $p->show();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $quancz=M('quancz');
        $tradeWB=M('trade_wastebooks');
        foreach($account_list as $key=>$val){
            $map1=array('customid'=>$val['customid1'],'quanid'=>$val['quanid']);
            $account_list[$key]['czamount']=intval($quancz->where($map1)->sum('amount'));

            $map2=array('cardno'=>$val['cardno'],'tradetype'=>'02','quanid'=>$val['quanid']);
            $account_list[$key]['consumeamount']=intval($tradeWB->where($map2)->sum('tradeamount'));
        }
        $this->assign('page',$page);
        $this->assign('list',$account_list);
        $this->display();
	}

    //营销产品充值报表
	function payquery_excel(){
		$quancz=D('quancz');
        $smap=session('quxcel');
        foreach($smap as $key=>$val) {
            $where[$key]=$val;
        }
        $quancz_list=$quancz->alias('a')->join('left join cards f on a.customid=f.customid')->join('left join customs_c m on a.customid=m.cid')
        			->join('left join customs b on m.customid=b.customid')->join('left join panters c on a.panterid=c.panterid')
        			->join('left join quankind d on a.quanid=d.quanid')->where($where)
        			->field('f.cardno,a.customid,a.placeddate,a.placedtime,a.quanid,a.userid,a.amount,b.customid as customid1,b.namechinese,b.linktel,d.quanname,d.startdate,d.enddate,d.amount quanidprice,c.namechinese as pantername')
        			->order('a.placeddate asc,a.placedtime asc')->select();
        foreach($quancz_list as $k=>$v){
            $v['amount']=floatval($v['amount']);
            $v['quanidprice']=floatval($v['quanidprice']);
            $v['totalamount']=$v['quanidprice']*$v['amount'];
            $slist[]=$v;
        }
        $strlist="会员编号,会员名称,手机号,卡号,充值日期,充值时间,充值券编号,充值券名称,充值数量,劵单价,劵总价,券起始日期,券终止日期,充值机构";
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        $sellCard_list=array();
        foreach ($slist as $key => $sell_info) {
            $sell_info['namechinese']=iconv('utf-8','gbk',$sell_info['namechinese']);
            $sell_info['quanname']=iconv('utf-8','gbk',$sell_info['quanname']);
            $sell_info['amount']=floatval($sell_info['amount']);
            $sell_info['quanidprice']=floatval($sell_info['quanidprice']);
            $sell_info['totalamount']=floatval($sell_info['totalamount']);
            $sell_info['pantername']=iconv('utf-8','gbk',$sell_info['pantername']);
            $sellCard_list[$key]['userid']=$sell_info['userid'];
            $strlist.=$sell_info['customid']."\t,".$sell_info['namechinese'].",";
            $strlist.=$sell_info['linktel'].",".$sell_info['cardno']."\t,".$sell_info['placeddate'].",";
            $strlist.=$sell_info['placedtime'].",".$sell_info['quanid']."\t,";
            $strlist.=$sell_info['quanname'].",".$sell_info['amount'].','.$sell_info['quanidprice'].',';
            $strlist.=$sell_info['totalamount'].','.$sell_info['startdate'].','.$sell_info['enddate'].',';
            $strlist.=$sell_info['pantername']."\n";
        }
        $filename='营销产品报表'.date('YmdHis');
        $filename = iconv("utf-8","gbk",$filename);
        unset($slist);
        $this->load_csv($strlist,$filename);
	}
	//写入批量申请购卡日志
    function batchbuyLogs($array){
        $msgString='';
        foreach($array as $key=>$val){
            $msgString.='卡号：'.$val['cardno']."  ";
            $msgString.='充值数量：'.$val['tradeamount']."  ";
            if($val['status']==0){
                $msgString.='状态：营销产品批量充值失败'."  ";
                $msgString.='原因：'.$val['msg']."  ";
            }elseif($val['status']==1){
                $msgString.='状态：营销产品批量充值成功'."  ";
            }
            $msgString.='时间：'.$val['datetime']."  ";
            $msgString.="\r\n\r\n";
        }
        $this->writeLogs('cardbatchbuy',$msgString);
    }
}
