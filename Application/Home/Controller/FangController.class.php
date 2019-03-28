<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\DB;
class FangController extends CommonController
{
	protected $panterid;
	protected $fang_goods;
	protected $panters;
	protected $zzk_store;
	protected $order;
	protected $urls;

	/**
	 * [_initialize description]
	 * $this->panterid 商户ID
	 * $this->urls 卡系统接口地址
	 * @return [type] [description]
	 */
	public function _initialize(){
		 parent::_initialize();
		 $this->panterid = session('panterid');
		 //$this->panterid= '00001612';
		 $this->urls = C('Fang_url');
		 $this->fang_goods = M('fang_goods');
		 $this->panters = M('panters');
		 $this->zzk_store = M('zzk_store');
		 $this->order = M('fang_order');
	}
	/**
	 * [index 营销商品]
	 * $goods_name  [商品名称]
	 * $nameenglish [项目简称]
	 * $good_status [商品状态]
	 * @return [array] [营销商品列表]
	 */
	public function index()
	{	
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$data['p.panterid'] = $this->panterid;
		}
		$data['g.isdel'] = 1;
		$goods_name = trim(I('param.goods_name',''));//商品名称
		$nameenglish = trim(I('param.nameenglish',''));//项目简称
		$goods_status = intval(trim(I('param.goods_status','')));//商品状态
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$data['p.nameenglish'] = array('like','%'.$nameenglish.'%');//商品名称模糊查询
		}
		if (!empty($goods_name)) {
			$map['goods_name'] = $goods_name;
			$data['g.goods_name'] = array('like','%'.$goods_name.'%');//项目名称模糊查询
		}
		if (!empty($goods_status)) {
			if ($goods_status != 3) {
				$map['goods_status'] = $goods_status;
				$data['g.goods_status'] = $goods_status;//商品状态
			}
		} else {
			$goods_status = 3;
		}	
		$count=$this->panters->alias('p')
           ->join('left join fang_goods g on g.panterid=p.panterid')
           ->join('left join zzk_store s on s.storeid=g.storeid')
           ->field('g.gid')
           ->where($data)
           ->count();//总记录条数

		$p=new \Think\Page($count, 10);
		$list=$this->panters->alias('p')
           ->join('left join fang_goods g on g.panterid=p.panterid')
           ->join('left join zzk_store s on s.storeid=g.storeid')
           ->field('g.*,p.nameenglish,s.name')
           ->where($data)
           ->limit($p->firstRow.','.$p->listRows)
           ->order('g.gid desc')
           ->select();//营销商品列表
        if(!empty($map)){
         foreach($map as $key=>$val) {
             $p->parameter[$key]= $val;
         }
        }
        foreach ($list as $key => $value) {
        	if ($value['price_type'] == 1) {
        		$list[$key]['type'] = '备付金';
        	} else {
        		$list[$key]['type'] = '自有资金';
        	}
        	$list[$key]['goods_price'] = floatval($value['goods_price']);
        	$list[$key]['time'] = date('Y-m-d H:i:s',$value['update_time']);
        	if ($value['is_freeze'] == 1) {
        		$list[$key]['freeze'] = '冻结';
        	} else {
        		$list[$key]['freeze'] = '非冻结';
        	}
        	if ($value['goods_status'] == 1) {
        		$list[$key]['status'] = '上架';
        	} else {
        		$list[$key]['status'] = '已下架';
        	}
        }
        $page = $p->show();//分页
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('goods_name',$goods_name);
        $this->assign('nameenglish',$nameenglish);
        $this->assign('goods_status',$goods_status);
        $this->display();   
	}

	/**
	 * [addgoods 添加营销商品页面]
	 * $panter ['商户信息']
	 * $store  ['商户下面的分期信息']
	 * @return [type] [description]
	 */
	public function addgoods()
	{
		$model = new Model();
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$panter = $this->panters->where(['revorkflg'=>'N'])->where(array('panterid'=>$this->panterid))->field('panterid,nameenglish')->select();//项目信息
			$store  = $this->zzk_store->where(array('panterid'=>$this->panterid))->order('storeid desc')->select();//分期信息
		} else {
			$panter = $this->panters->where(['revorkflg'=>'N'])->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();//项目信息
			$store  = $this->zzk_store->where(array('panterid'=>$panter[0]['panterid']))->order('storeid desc')->select();//项目信息
		}
	
		$this->assign('panter',$panter);
		$this->assign('store',$store);
		$this->display(); 
	}

	/**
	 * [ajax 项目门店二级联动]
	 * @return [type] [description]
	 */
	public function ajax()
	{
		$id = trim(I('post.panterid',''));//商品名称
		$store  = $this->zzk_store->where(array('panterid'=>$id))->order('storeid desc')->field('storeid,name')->select();//项目信息
		echo  json_encode($store);
		//return $store;
	}
	/**
	 * [insertgoods 添加营销产品入数据库]
	 * $data['goods_name'] 商品名称
	 * $data['price_type'] 资金类型
	 * $data['is_freeze']  资金冻结
	 * $data['is_freeze']  资金冻结
	 * $data['goods_price'] 商品价格
	 * $data['goods_status']  商品状态
	 * $data['goods_msg'] 商品详情
	 * $data['panterid'] 商户ID
	 * $data['storeid']  分期ID
	 * $data['create_time'] 创建时间
	 * $data['isdel'] 是否删除
	 * @return [type] [description]
	 */
	public function insertgoods()
	{
		if(IS_POST){
			//记录日志start
            $logs = I('post.');
            $logs['user'] = session('panterid');
            $js = json_encode($logs,JSON_UNESCAPED_UNICODE);
            $this->recordLog('goods_create',$js); 
            //end
           
			$model = new model();
			$field = 'fang_goods';
			$gid  = $this->getFieldNextNumber($field);
            $goods_name = trim(I('post.goods_name','')); 
            $price_type = trim(I('post.price_type','')); 
            $is_freeze = trim(I('post.is_freeze',''));   
            $goods_price = trim(I('post.goods_price',''));
            $goods_status = trim(I('post.goods_status',''));
            $goods_msg = trim(I('post.goods_msg',''));
            $panterids = trim(I('post.panterid',''));
            $create_time = time();
            $update_time = time();
            $isdel = 1;
            $storeid = trim(I('post.storeid',''));
            $goods_tx = trim(I('post.goods_tx',''));
            $sql="INSERT INTO fang_goods(gid,goods_name,price_type,is_freeze,goods_price,goods_status,goods_msg,panterid,create_time,isdel,storeid,goods_tx,update_time) ";
            $sql.="values ('{$gid}','{$goods_name}','{$price_type}','{$is_freeze}','{$goods_price}','{$goods_status}','{$goods_msg}','{$panterids}','{$create_time}','{$isdel}','{$storeid}','{$goods_tx}','{$update_time}')";
           	$customIf=$model->execute($sql);
           	if ($customIf) {
           		$this->success("创建成功",U('Fang/index'));
           	} else {
           		$this->error("创建失败",U('Fang/index'));
           	}
           	
        }
	}

	/**
	 * [goods_list 查看详情]
	 * $data['goods_name'] 商品名称
	 * $data['price_type'] 资金类型
	 * $data['is_freeze']  资金冻结
	 * $data['is_freeze']  资金冻结
	 * $data['goods_price'] 商品价格
	 * $data['goods_status']  商品状态
	 * $data['goods_msg'] 商品详情
	 * $data['panterid'] 商户ID
	 * $data['storeid']  分期ID
	 * $data['create_time'] 创建时间
	 * $data['isdel'] 是否删除
	 * @return [type] [description]
	 */
	public function goods_list()
	{
		
	}
	/**
	 * [goods_edit 商品编辑]
	 * $goods 商品信息
	 * $panter 商户信息
	 * $store 分期信息
	 * @return [type] [description]
	 */
	public function goods_edit()
	{
		$gid['gid'] = trim(I('get.gid',''));
		$goods = $this->fang_goods->where($gid)->find();
		$panter = $this->panters->where(['panterid'=>$goods['panterid']])->field('panterid,nameenglish')->find();
		$store = $this->zzk_store->where(['storeid'=>$goods['storeid']])->field('storeid,name')->find();
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$panterid = $this->panters->where(array('panterid'=>$this->panterid))->field('panterid,nameenglish')->find();//项目信息
			$stores  = $this->zzk_store->where(array('panterid'=>$this->panterid))->order('storeid desc')->select();//分期信息
		} else {
			$panterid = $this->panters->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();//项目信息
			$stores  = $this->zzk_store->where(array('panterid'=>$panter[0]['panterid']))->order('storeid desc')->select();//项目信息
		}
        $goods['goods_price'] = floatval($goods['goods_price']);
		$this->assign('goods',$goods);
		$this->assign('panterid',$panterid);
		$this->assign('panter',$panter);
		$this->assign('stores',$stores);
		$this->assign('store',$store);
		$this->display();
	} 

	/**
	 * [goods_update 更改商品信息]
	 * $data['goods_name'] 商品名称
	 * $data['price_type'] 资金类型
	 * $data['is_freeze']  资金冻结
	 * $data['is_freeze']  资金冻结
	 * $data['goods_price'] 商品价格
	 * $data['goods_status']  商品状态
	 * $data['goods_msg'] 商品详情
	 * $data['panterid'] 商户ID
	 * $data['storeid']  分期ID
	 * $data['create_time'] 创建时间
	 * $data['isdel'] 是否删除
	 * @return [type] [description]
	 */
	public function goods_update()
	{
		$gid['gid'] = trim(I('post.gid',''));

		//记录日志start
		$logs = I('post.');
        $logs['user'] = session('panterid');
        $logs['gid'] = $gid['gid'];
        $js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        //end

		$data['goods_name'] = trim(I('post.goods_name','')); 
        $data['price_type'] = trim(I('post.price_type','')); 
        $data['is_freeze'] = trim(I('post.is_freeze',''));   
        $data['goods_price'] = trim(I('post.goods_price',''));
        $data['goods_status'] = trim(I('post.goods_status',''));
        $data['goods_msg'] = trim(I('post.goods_msg',''));
        $data['panterid'] = trim(I('post.panterids',''));
        $data['storeid'] = trim(I('post.storeid',''));
        $data['goods_tx'] = trim(I('post.goods_tx',''));
        $data['update_time'] = time();
        $this->recordLog('goods_update',$js); 
        $update = $this->fang_goods->where($gid)->save($data);
        if ($update) {
        	$this->success("修改成功",U('Fang/index'));
        } else {
        	$this->error("修改失败",U('Fang/index'));
        }
	}

	/**
	 * [order_list 订单列表]
	 * o_sn    订单号
	 * o_name  姓名
	 * o_phone 手机号
	 * o_card  身份证号码
	 * nameenglish 项目名称
	 * store_name 门店姓名
	 * o_status  订单状态
	 * is_freeze 冻结状态
	 * zy_name  置业顾问姓名
	 * zy_phone 置业顾问手机号
	 * mingyuan 明源
	 *
	 *
	 *
	 * 
	 * @return [type] [description]
	 */
	public function order_list()
	{
		$o_sn = trim(I('param.o_sn','','htmlspecialchars','addslashes')); 
		$o_name = trim(I('param.o_name','','htmlspecialchars','addslashes')); 
		$o_phone = trim(I('param.o_phone','','htmlspecialchars','addslashes')); 
		$o_card = trim(I('param.o_card','','htmlspecialchars','addslashes')); 
		$nameenglish = trim(I('param.nameenglish','','htmlspecialchars','addslashes')); 
		$store_name = trim(I('param.store_name','','htmlspecialchars','addslashes')); 
		$o_status = trim(I('param.o_status','5','htmlspecialchars','addslashes')); 
		$is_freeze = trim(I('param.is_freeze','3','htmlspecialchars','addslashes')); 
		$zy_name = trim(I('param.zy_name','','htmlspecialchars','addslashes')); 
		$zy_phone = trim(I('param.zy_phone','','htmlspecialchars','addslashes')); 
		$mingyuan = trim(I('param.mingyuan','','htmlspecialchars','addslashes'));
		$startdate = trim(I('param.startdate','','htmlspecialchars','addslashes'));
        $enddate = trim(I('param.enddate','','htmlspecialchars','addslashes'));
        if (!empty($enddate) && !empty($startdate)) {
        	$start = strtotime($startdate);
        	$end = strtotime($enddate . ' 23:59:59');
            $data['o.o_createtime'] = array(array('egt',$start), array('elt', $end));
            $map['startdate'] = $startdate;
            $map['enddate'] = $enddate;
        }
		if (!empty($o_sn)) {
			$map['o_sn'] = $o_sn;
			$data['o.o_sn'] = $o_sn;
		}
		if (!empty($o_name)) {
			$map['o_name'] = $o_name;
			$data['o.o_name'] = $o_name;
		}
		if (!empty($o_phone)) {
			$map['o_phone'] = $o_phone;
			$data['o.o_phone'] = $o_phone;
		}
		if (!empty($o_card)) {
			$map['o_card'] = $o_card;
			$data['o.o_card'] = $o_card;
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$data['p.nameenglish'] = array('like','%'.$nameenglish.'%');;
		}
		if (!empty($o_status) ||  $o_status == 0) {
			$map['o_status'] = $o_status;
			if ($o_status != 5) {
				$data['o.o_status'] = $o_status;
			} else {
				$data['o.o_status'] = array('in' , array(0,1,2,3,4,6));
			}
		} else {
			$data['o.o_status'] = array('in' , array(0,1,2,3,4,6));
		}
	
		if (!empty($store_name)) {
			$map['store_name'] = $store_name;
			$data['s.name'] = array('like','%'.$store_name.'%');;
		}
		if (!empty($is_freeze)) {
			if ($is_freeze !=3) {
				$map['is_freeze'] = $is_freeze;
				$data['o.is_freeze'] = $is_freeze;
			}
		}
		if (!empty($zy_name)) {
			$map['zy_name'] = $zy_name;
			$data['o.zy_name'] = $zy_name;
		}
		if (!empty($zy_phone)) {
			$map['zy_phone'] = $zy_phone;
			$data['o.zy_phone'] = $zy_phone;
		}
		// if (!empty($mingyuan)) {
		// 	$data['o.my_tb'] = $mingyuan;
		// }
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$data['o.panterid'] = $this->panterid;
		}
		
  		session('order_select',$data);
		$count=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name')
           ->where($data)
           ->count();//总记录条数

		$p=new \Think\Page($count, 10);
		$list=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name')
           ->where($data)
           ->limit($p->firstRow.','.$p->listRows)
           ->order('o.o_createtime desc')
           ->select();//营销商品列表
        if(!empty($map)){
         foreach($map as $key=>$val) {
             $p->parameter[$key]= $val;
         }
        }
        $page = $p->show();//分页
         foreach ($list as $key => $value) {
        	if ($value['price_type'] == 1) {
        		$list[$key]['type'] = '备付金';
        	} else {
        		$list[$key]['type'] = '自有资金';
        	}
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	$list[$key]['o_paymoney'] = floatval($value['o_paymoney']);
        	if ($value['my_tb'] == 1) {
        		$list[$key]['my'] = '未同步';
        	} else {
        		$list[$key]['my'] = '已同步';
        	}
        	
        	$list[$key]['ctime'] = date('Y-m-d H:i:s',$value['o_createtime']);
        	$list[$key]['ptime'] = date('Y-m-d H:i:s',$value['o_paytime']);
        	$fang_paylog = M('fang_paylog');
        	$paylog = $fang_paylog->where(['o_sn'=>$value['o_sn']])->select();
        	$list[$key]['snno'] = '';
        	// if ($paylog) {
        	// 	$str = strstr($paylog['sn_no'],'-');
        	// 	if ($str) {
        	// 		$arrs = explode('-', $paylog['sn_no']);
        	// 		if (is_array($arrs)) {
        	// 			$list[$key]['snno'] = end($arrs);
        	// 		} else {
        	// 			$list[$key]['snno'] = $paylog['sn_no'];
        	// 		}
        	// 	} else {
        	// 		$list[$key]['snno'] = $paylog['sn_no'];
        	// 	}
        	// }
        	

        	if ($paylog) {
        		foreach ($paylog as $k => $val) {
        			$str[$k] = strstr($val['sn_no'],'-');
        			if ($str[$k]) {
        				$arrs[$k] = explode('-', $val['sn_no']);
        				if (is_array($arrs[$k])) {
        					$list[$key]['snno'] .= end($arrs[$k]) . ' - ';
        				} else {
        					$list[$key]['snno'] .= $val['sn_no'] . ' - ';
        				}
        			} else {
        				$list[$key]['snno'] .= $val['sn_no'] . ' - ';
        			}
        		}
        	}
        	$list[$key]['snno'] = rtrim($list[$key]['snno'],' - ');
        	if ($value['is_freeze'] == 1) {
        		$list[$key]['freeze'] = '冻结';
        		$list[$key]['freeze_link'] ='<button type="button" class="btn btn-mini btn-primary jds" onclick="changes('.$value['o_id'].')">解冻</button>';
        	} else {
        		$list[$key]['freeze'] = '非冻结';
        	}
        	if ($value['o_status'] == 0) {
        		$list[$key]['status'] = '未支付';
        	} elseif($value['o_status'] == 1) {
        		// $list[$key]['edit'] ='<button type="button" class="btn btn-mini btn-primary" onclick="window.location='{:U('order_edit',array('o_id'=>$vo['o_id']))}'">编辑</button>&nbsp&nbsp';
        		$list[$key]['edit'] ='<button type="button" class="btn btn-mini btn-primary" onclick="window.location=\'/zzkp.php/Fang/order_edit/o_id/'.$value['o_id'].'\'">编辑</button>';
        		$list[$key]['status'] = '已支付';
        	} elseif($value['o_status'] == 2) {
        		$list[$key]['status'] = '已完成';
        	} elseif($value['o_status'] == 3) {
        		$list[$key]['status'] = '已取消（超时取消）';
        	}  elseif($value['o_status'] == 4) {
        		$list[$key]['status'] = '付款中';
        	}  elseif($value['o_status'] == 6) {
        		$list[$key]['status'] = '已退款';
        	}
        }
       	
       	$this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('o_sn',$o_sn);  
		$this->assign('o_name',$o_name);  
		$this->assign('o_phone',$o_phone);  
		$this->assign('o_card',$o_card);  
		$this->assign('nameenglish',$nameenglish);
		$this->assign('store_name', $store_name);
		$this->assign('o_status',$o_status);   
		$this->assign('is_freeze',$is_freeze);  
		$this->assign('zy_name',$zy_name);  
		$this->assign('zy_phone',$zy_phone);   
		//$this->assign('mingyuan',$mingyuan);  
        $this->display();   

	}

	/**
	 * [order_detail 订单详情]
	 * @return [type] [description]
	 */
	public function order_detail()
	{
		$data['o.o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));//订单号
		$fang_paylog = M('fang_paylog');
		$fang_order_log = M('fang_order_log');
		$list=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name,p.parent')
           ->where($data)
           ->find();//订单列表
        switch ($list['o_status']) {
   			case '0':
   				$list['status'] = '未付款';
   				break;
   			case '1':
   				$list['status'] = '已付款';
   				break;
   			case '2':
   				$list['status'] = '已完成';
   				break;
   			case '3':
   				$list['status'] = '已取消(超时取消)';
   				break;
			case '4':
				$list['status'] = '付款中';
   				break;
   			case '6':
				$list['status'] = '已退款';
   				break;
   		}
   		 if ($list ['my_tb'] == 1) {
        	$list['tb'] = '未同步';
        } else {
        	$list['tb'] = '已同步';
        }
        $list['o_price'] = floatval($list['o_price']);
   		$list['ctime'] = date('Y-m-d H:i:s',$list['o_createtime']);//创建时间
        $list['ptime'] = date('Y-m-d H:i:s',$list['o_paytime']); //更改时间
        $paylog = $fang_paylog->where(['o_sn'=>$list['o_sn']])->select();
        $fang_order_log = $fang_order_log->where(['o_sn'=>$list['o_sn']])->select();
        foreach ($fang_order_log as $key => $value) {
        	$fang_order_log[$key]['time'] = date('Y-m-d H:i:s',$value['createtime']);
        	$fang_order_log[$key]['mark'] = rtrim($value['mark'],"| ");
        }
        if($paylog) {
   			foreach ($paylog as $key => $value) {

   				$paylog[$key]['time'] = date('Y-m-d H:i:s',$value['createtime']);
   				$paylog[$key]['price'] = floatval($value['price']);
   				switch ($value['banknum']) {
   					case '-1':
   						$paylog[$key]['type'] = '现金';
   						break;
   					case '1':
   						$paylog[$key]['type'] = '银行卡';
   						break;
   					case '2':
   						$paylog[$key]['type'] = '微信';
   						break;
   					case '3':
   						$paylog[$key]['type'] = '支付宝';
   						break;
   				}

   				$str = strstr($value['sn_no'],'-');
        		if ($str) {
        			$arrs = explode('-', $value['sn_no']);
        			if (is_array($arrs)) {
        				$paylog[$key]['snno'] = "\t" . end($arrs) . "\t";
        			} else {
        				$paylog[$key]['snno'] = "\t" .$value['sn_no']. "\t";
        			}
        		} else {
        			$paylog[$key]['snno'] = "\t" .$value['sn_no']. "\t";
        		}
   			}
   		}
   		$company = $this->panters->where(['panterid'=>$list['parent']])->field('nameenglish')->find();
   		$this->assign('company',$company);
        $this->assign('list',$list);
		$this->assign('paylog',$paylog);
		$this->assign('fang_order_log',$fang_order_log);
		$this->display();
	}
	/**
	 * [order_edit 订单编辑]
	 * @return [type] [description]
	 */
	public function order_edit()
	{
		$fang_paylog = M('fang_paylog');
		$data['o.o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));//订单号
		$list=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,p.parent,s.name')
           ->where($data)
           ->find();//订单详情信息
        $list['ctime'] = date('Y-m-d H:i:s',$list['o_createtime']);
        $list['ptime'] = date('Y-m-d H:i:s',$list['o_paytime']);
        if (empty($list['zy_name'])) {
        	$list['gw_name'] = '';
        } else {
        	$list['gw_name'] = '<div class="xinxi">置业顾问：'.$list['zy_name'].'</div>';
        }
        if (empty($list['zy_phone'])) {
        	$list['gw_phone'] = '';
        } else {
        	$list['gw_phone'] = '<div class="xinxi">置业顾问：'.$list['zy_phone'].'</div>';
        }
        if ($list ['is_freeze'] == 1) {
        	$list['freeze'] = '冻结';
        } else {
        	$list['freeze'] = '不冻结';
        }
        if ($list ['my_tb'] == 1) {
        	$list['tb'] = '未同步';
        } else {
        	$list['tb'] = '已同步';
        }
        $list['o_price'] = floatval($list['o_price']);
        switch ($list['o_status']) {
   			case '0':
   				$list['status'] = '未付款';
   				break;
   			case '1':
   				$list['status'] = '已付款';
   				break;
   			case '2':
   				$list['status'] = '已完成';
   				break;
   			case '3':
   				$list['status'] = '已取消（超时取消）';
   				break;
			case '4':
   				$list['status'] = '付款中';
   				break;
   			case '6':
   				$list['status'] = '已退款';
   				break;
   		}
        $o_sn['o_sn'] = $list['o_sn'];
   		$paylog = $fang_paylog->where($o_sn)->select();
   		if($paylog) {
   			foreach ($paylog as $key => $value) {
   				$paylog[$key]['time'] = date('Y-m-d H:i:s',$value['createtime']);
   				switch ($value['banknum']) {
   					case '-1':
   						$paylog[$key]['type'] = '现金';
   						break;
   					case '1':
   						$paylog[$key]['type'] = '银行卡';
   						break;
   					case '2':
   						$paylog[$key]['type'] = '微信';
   						break;
   					case '3':
   						$paylog[$key]['type'] = '支付宝';
   						break;
   				}
   			}
   		}
   		$company = $this->panters->where(['panterid'=>$list['parent']])->field('nameenglish')->find();
   		$user=M('fang_order_user')->where(['o_sn'=>$list['o_sn']])->where(['levels'=>2])->select();//营销商品列表
        $this->assign('list',$list);
		$this->assign('paylog',$paylog);
		$this->assign('company',$company);
		$this->assign('user',$user);
        $this->display();
	}

	/**
	 * [order_update 订单更新]
	 * @return [type] [description]
	 */
	public function order_update()
	{
		$o_id['o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));

		//记录日志 start
		$logs = I('post.');
        $logs['user'] = session('panterid');
        $logs['o_id'] = $o_id['o_id'];
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        $this->recordLog('order_update',$logs_js); 
        //end

		$data = $_POST;
		$order = $this->order->where($o_id)->find();
		if ($order['o_status'] == 1) {
			if ($order['is_freeze'] != $data['is_freeze']) {
				if ($order['is_freeze'] == 1 && $data['is_freeze'] == 2) {
					$data['phone'] = $order['o_phone'];	
					$data['amount'] = floatval($order['o_price']);	
					$data['order_sn'] = $order['o_sn'];	
					$data['panterid'] = $order['panterid'];	
					$data['storeid'] = $order['storeid'];	
					$data['source'] = '05';	
					$data['status'] = '1';	
					$data['type'] = 'unfreeze';	
					$data['description'] = '订单解冻';
					$code = 'JYO2O01';
    				$str = $code . $data['order_sn'] . $data['phone'] . $data['amount'] . $data['panterid']  . $data['storeid'] . $data['type'];
    				$data['key'] = md5($str);	
					//解冻接口
					$url =  $this->urls . '/index.php/index/index/freeze_un';
					$js = json_encode($data);
					$res = $this->CurlPost($url,$js);
					$result = json_decode($res,true);
					if ($result['status']) {
						$status['is_freeze'] = 2;
						$update = $this->order->where($o_id)->save($status);
       					$this->success("解冻成功",U('Fang/order_list'));
       				} else {
       					$this->error("解冻失败",U('Fang/order_list'));
        			}
				}
				if ($order['is_freeze'] == 2 && $data['is_freeze'] == 1) {
					$data['phone'] = $order['o_phone'];	
					$data['amount'] = floatval($order['o_price']);	
					$data['order_sn'] = $order['o_sn'];	
					$data['panterid'] = $order['panterid'];	
					$data['storeid'] = $order['storeid'];	
					$data['source'] = '05';	
					$data['status'] = '1';	
					$data['type'] = 'freeze';	
					$data['description'] = '订单冻结';
					$code = 'JYO2O01';
    				$str = $code . $data['order_sn'] . $data['phone'] . $data['amount'] . $data['panterid']  . $data['storeid'] . $data['type'];
    				$data['key'] = md5($str);
					//解冻接口
					$url =  $this->urls . '/index.php/index/index/freeze_un';
					$js = json_encode($data);
					$res = $this->CurlPost($url,$js);
					$result = json_decode($res,true);
					if ($result['status']) {
						$status['is_freeze'] = 1;
						$update = $this->order->where($o_id)->save($status);
       					$this->success("修改成功",U('Fang/order_list'));
       				} else {
       					$this->error("修改失败" . $result['message'],U('Fang/order_list'));
        			}
				}
			} else {
				$this->success("修改成功",U('Fang/order_list'));
			}	
		} else {
			$this->success("修改失败，订单状态为已完成或者已取消",U('Fang/order_list'));
			if ($order['o_status'] == 2 ||  $order['o_status']) {
				$this->error("修改失败，订单状态为已完成或者已取消",U('Fang/order_list'));
			}
			if ($order['is_freeze'] == 1 && $data['is_freeze'] == 2) {
				$status['is_freeze'] = 2;
				$update = $this->order->where($o_id)->save($status);
       			$this->success("解冻成功",U('Fang/order_list'));
			}
			if ($order['is_freeze'] == 2 && $data['is_freeze'] == 1) {
				$status['is_freeze'] = 1;
				$update = $this->order->where($o_id)->save($status);
       			$this->success("修改成功",U('Fang/order_list'));
       			
			}
		}	
	}
	/**
	 * [update_name 更名]
	 * @return [type] [description]
	 */
	public function update_name()
	{
		$o_id['o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));
		$order = $this->order->where($o_id)->find();
		$user=M('fang_order_user')->where(['o_sn'=>$order['o_sn']])->where(['levels'=>2])->select();//订单关联用户
        $this->assign('order',$order);
		$this->assign('user',$user);
        $this->display();
	}


	/**
	 * [update_master 更改订单主用户]
	 * @return [type] [description]
	 */
	public function update_master()
	{
		$data['ouid'] = trim(I('get.ouid','','htmlspecialchars','addslashes'));//用户ID
		$data['o_sn'] = trim(I('get.o_sn','','htmlspecialchars','addslashes'));//订单号
		$model = new model();
		//日志记录start
        $logs['user'] = session('panterid');
        $logs['ouid'] = $data['ouid'];
        $logs['o_sn'] = $data['o_sn'];
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        $this->recordLog('update_master',$logs_js); 
        //end

		if (empty($data['ouid']) || empty($data['o_sn'])) {
			$this->error("更改失败",U('Fang/order_list'));
		}
		$order = $this->order->where(['o_sn'=>$data['o_sn']])->find();
		$user = M('fang_order_user')->Where(['ouid'=>$data['ouid']])->find();
		if (!$order || !$user) {
			$this->error("更改失败",U('Fang/order_list'));
		}
		if ($order['o_status'] == 1) {
			$transfer['linktel'] = $order['o_phone'];
			if ($order['price_type']== 1) {
        		$transfer['moneytype']  = '02';//01自有资金 02备付金
        	} else {
        		$transfer['moneytype']  = '01';//01自有资金 02备付金
        	}
			$transfer['name'] = $user['username'];
			$transfer['personid'] = $user['usercard'];
			$transfer['residaddress'] = $order['o_address'];
			$transfer['order_sn'] = $order['o_sn'];
			$transfer['type'] = '05';
			$transfer['phone'] = $user['usertel'];
			$transfer['amount'] = floatval($order['o_price']);
			if ($order['price_type'] == 2) {
				if ($order['is_freeze']  == 1) {
					$transfer['amounttype'] = '01';//资金类型 01 冻结 02提现 03消费 04备付金
				} else {
					if ($order['is_tx'] == 1) {
						$transfer['amounttype'] = '03';//资金类型 01 冻结 02提现 03消费 04备付金
					} else {
						$transfer['amounttype'] = '02';//资金类型 01 冻结 02提现 03消费 04备付金
					}
				}
			} else {
				$transfer['amounttype'] = '04';//资金类型 01 冻结 02提现 03消费 04备付金
			}
			$paylog = M('fang_paylog')->where(['o_sn'=>$order['o_sn']])->find();
			if ($paylog) {
				switch ($paylog) {
				  case '-1':
				    $transfer['paytype'] = '01';//资金类型 01 冻结 02提现 03消费 04备付金
				    break;
				  case '1':
				    $transfer['paytype'] = '02';//资金类型 01 冻结 02提现 03消费 04备付金
				    break;
				  case '2':
				    $transfer['paytype'] = '03';//资金类型 01 冻结 02提现 03消费 04备付金
				    break;
				  case '3':
				    $transfer['paytype'] = '04';//资金类型 01 冻结 02提现 03消费 04备付金
				    break;
				  default:
				    $transfer['paytype'] = '04';//资金类型 01 冻结 02提现 03消费 04备付金
				    break;
				}				
			} else {
			 	$transfer['paytype'] = '04';	
			}
    		$transfer['source'] = '05';	
    		$transfer['description'] = '订单转账';
    		$code = 'JYO2O01';
   			$str = $code . $transfer['order_sn'] . $transfer['linktel'] . $transfer['personid'] . $transfer['phone'] . $transfer['amount'] . $transfer['amounttype'];
    		$transfer['key'] = md5($str);
    		$js = json_encode($transfer);
       		$url = $this->urls . "/index.php/index/index/transfer";//转账接口
			$res = $this->CurlPost($url,$js);
    		$result = json_decode($res,true);
			if ($result['status']) {
				$update['o_name'] = $user['username'];
				$update['o_card'] = $user['usercard'];
				$update['o_phone'] = $user['usertel'];
				$update['userid'] = $data['ouid'];
				$mark .= '姓名由 ' . $order['o_name'] . ' 更换为 ' . $update['o_name'] .  ' | ';
				$mark .= '手机号由 ' . $order['o_phone'] . ' 更换为 ' . $update['o_phone'] .  ' | ';
				$mark .= '身份证号由 ' . $order['o_card'] . ' 更换为 ' . $update['o_card'] .  ' (更改主用户)| ';
				$order_update = $this->order->where(['o_sn'=>$data['o_sn']])->save($update);
				if (!empty($mark)) {
					$upid = $this->getFieldNextNumber('fang_order_log');
					$createtime = time();
					$sql = "INSERT INTO fang_order_log (upid , o_sn , mark, createtime ) VALUES (%d , %d , '%s' , %d )";
           			$bind = ['upid'=>$upid,'o_sn'=>$data['o_sn'],'mark'=>$mark,'createtime'=>$createtime];
      				$res = $model->execute($sql,$bind);
				}
				M('fang_order_user')->where(['ouid'=>$order['userid']])->save(['levels'=>2]);
				M('fang_order_user')->where(['ouid'=>$data['ouid']])->save(['levels'=>1]);
				$this->success("修改成功",U('Fang/order_list'));
			} else {
				//$stringData .="状态:{$result['message']}<hr/>";
				$this->error("修改失败".$result['message'],U('Fang/order_list'));
			}	
		} else {
			$update['o_name'] = $user['username'];
			$update['o_card'] = $user['usercard'];
			$update['o_phone'] = $user['usertel'];
			$update['userid'] = $data['ouid'];
			$order_update = $this->order->where(['o_sn'=>$data['o_sn']])->save($update);
			M('fang_order_user')->where(['ouid'=>$order['userid']])->save(['levels'=>2]);
			M('fang_order_user')->where(['ouid'=>$data['ouid']])->save(['levels'=>1]);
			$this->success("修改成功",U('Fang/order_list'));
		}		
	}

	/**
	 * [update_usermsg 更改订单用户信息]
	 * @return [type] [description]
	 */
	public function update_usermsg()
	{
		$model = new model();
		$data = I('post.');
		$mark = '';
		$user = array();

		//日志start
		$logs = $data ;
		$logs['user'] = session('panterid');
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        $this->recordLog('update_usermsg',$logs_js); 
        //end

		$o_id['o_id'] = trim($data['maste_id']);//用户ID
		$master['o_name'] = trim($data['maste_name']);//用户姓名
		$master['o_phone'] = trim($data['maste_phone']);//用户手机号
		$master['o_card'] = trim($data['maste_card']);//用户身份证号码
		$order = $this->order->where($o_id)->find();
		if (isset($data['name'])) {
			foreach ($data['name'] as $key => $value) {
				$user[$key]['username'] = $data['name'][$key];
				$user[$key]['usertel'] = $data['phone'][$key];
				$user[$key]['usercard'] = $data['card'][$key];
				$user[$key]['ouid'] = $data['id'][$key];
			}
			foreach ($user as $key => $value) {
				$user_msg['username']  = $value['username'];
				$user_msg['usertel']  = $value['usertel'];
				$user_msg['usercard']  = $value['usercard'];
				M('fang_order_user')->where(['ouid'=>$value['ouid']])->save($user_msg);
			}
		}
		if ($order['o_status'] == 1) {
			if ($master['o_phone'] == $order['o_phone']) {
				if ($master['o_name'] != $order['o_name'] || $master['o_card'] != $order['o_card']) {
					if ($master['o_name'] != $order['o_name']) {
						$update['o_name'] = $master['o_name'];
						$mark .= '姓名由 ' . $order['o_name'] . ' 更换为 ' . $master['o_name'] .  ' | ';
					}
					if ($master['o_card'] != $order['o_card']) {
						$update['o_card'] = $master['o_card'];
						$mark .= '身份证号由 ' . $order['o_card'] . ' 更换为 ' . $master['o_card'] . ' | ';
					}
					if (!empty($mark)) {
						$upid = $this->getFieldNextNumber('fang_order_log');
						$createtime = time();
						$sql = "INSERT INTO fang_order_log (upid , o_sn , mark, createtime ) VALUES (%d , %d , '%s' , %d )";
           				$bind = ['upid'=>$upid,'o_sn'=>$order['o_sn'],'mark'=>$mark,'createtime'=>$createtime];
      					$res = $model->execute($sql,$bind);
					}
					$order_update = $this->order->where($o_id)->save($update);
					$updates['username'] = $master['o_name'];
					$updates['usercard'] = $master['o_card'];
					$user_update = M('fang_order_user')->where(['ouid'=>$order['userid']])->save($updates);
				} 
			} else {
				$transfer['linktel'] = $order['o_phone'];
				if ($order['price_type']== 1) {
        			$transfer['moneytype']  = '02';//01 自有资金 02备付金
        		} else {
        			$transfer['moneytype']  = '01';//01 自有资金 02备付金
        		}
				$transfer['name'] = $master['o_name'];//姓名
				$transfer['personid'] = $master['o_card'];//身份证号码
				$transfer['residaddress'] = $order['o_address'];//住址
				$transfer['order_sn'] = $order['o_sn'];//订单号
				$transfer['type'] = '05';//房掌柜
				$transfer['phone'] = $master['o_phone'];
				$transfer['amount'] = floatval($order['o_price']);
				if ($order['price_type'] == 2) {
					if ($order['is_freeze']  == 1) {
						$transfer['amounttype'] = '01';//资金类型 01 冻结 02提现 03消费 04备付金
					} else {
						if ($order['is_tx'] == 1) {
							$transfer['amounttype'] = '03';//资金类型 01 冻结 02提现 03消费 04备付金
						} else {
							$transfer['amounttype'] = '02';//资金类型 01 冻结 02提现 03消费 04备付金
						}
					}
				} else {
					$transfer['amounttype'] = '04';//资金类型 01 冻结 02提现 03消费 04备付金
				}
				$paylog = M('fang_paylog')->where(['o_sn'=>$order['o_sn']])->find();
				if ($paylog) {
					switch ($paylog) {
					  case '-1':
					    $transfer['paytype'] = '01';
					    break;
					  case '1':
					    $transfer['paytype'] = '02';
					    break;
					  case '2':
					    $transfer['paytype'] = '03';
					    break;
					  case '3':
					    $transfer['paytype'] = '04';
					    break;
					  default:
					    $transfer['paytype'] = '04';
					    break;
					}				
				} else {
				 	$transfer['paytype'] = '04';	
				}
    			$transfer['source'] = '05';	
    			$transfer['description'] = '订单转账';
    			$code = 'JYO2O01';
   				$str = $code . $transfer['order_sn'] . $transfer['linktel'] . $transfer['personid'] . $transfer['phone'] . $transfer['amount'] . $transfer['amounttype'];
    			$transfer['key'] = md5($str);
    			$js = json_encode($transfer);
       			$url = $this->urls . "/index.php/index/index/transfer";//转账接口
				$res = $this->CurlPost($url,$js);
    			$result = json_decode($res,true);
				if ($result['status']) {
					if ($master['o_name'] != $order['o_name']) {
						$update['o_name'] = $master['o_name'];
						$mark .= '姓名由 ' . $order['o_name'] . ' 更换为 ' . $master['o_name'] .  ' | ';	
					} 
					if ($master['o_phone'] != $order['o_phone']) {
						$update['o_phone'] = $master['o_phone'];
						$mark .= '手机号由 ' . $order['o_phone'] . ' 更换为 ' . $master['o_phone'] . ' | ';	
					}
					if ($master['o_card'] != $order['o_card']) {
						$update['o_card'] = $master['o_card'];
						$mark .= '身份证号由 ' . $order['o_card'] . ' 更换为 ' . $master['o_card'] . ' | ';
					}
					if (!empty($mark)) {
						$upid = $this->getFieldNextNumber('fang_order_log');
						$createtime = time();
						$sqls = "INSERT INTO fang_order_log (upid , o_sn , mark, createtime ) VALUES (%d , %d , '%s' , %d )";
           				$binds = ['upid'=>$upid,'o_sn'=>$order['o_sn'],'mark'=>$mark,'createtime'=>$createtime];
      					$res = $model->execute($sqls,$binds);
					}
					$order_update = $this->order->where($o_id)->save($update);
					$updates['username'] = $master['o_name'];
					$updates['usercard'] = $master['o_card'];
					$updates['usertel'] = $master['o_phone'];
					$user_update = M('fang_order_user')->where(['ouid'=>$order['userid']])->save($updates);
					$this->success("修改成功",U('Fang/order_list'));
				} else {
					if (isset($result['codemsg'])) {
						$this->error("修改失败," . $result['codemsg'],U('Fang/order_list'));
					} else {
						$this->error("修改失败",U('Fang/order_list'));
					}
					
				}
			}
		} else {
			if ($master['o_name'] != $order['o_name']) {
				$update['o_name'] = $master['o_name'];
				$mark .= '姓名由 ' . $order['o_name'] . ' 更换为 ' . $master['o_name'] .  ' | ';	
			} 
			if ($master['o_phone'] != $order['o_phone']) {
				$update['o_phone'] = $master['o_phone'];
				$mark .= '手机号由 ' . $order['o_phone'] . ' 更换为 ' . $master['o_phone'] . ' | ';	
			}
			if ($master['o_card'] != $order['o_card']) {
				$update['o_card'] = $master['o_card'];
				$mark .= '身份证号由 ' . $order['o_card'] . ' 更换为 ' . $master['o_card'] . ' | ';
			}
			if (!empty($mark)) {
				$upid = $this->getFieldNextNumber('fang_order_log');
				$createtime = time();
				$sqles = "INSERT INTO fang_order_log (upid , o_sn , mark, createtime ) VALUES (%d , %d , '%s' , %d )";
           		$bindes = ['upid'=>$upid,'o_sn'=>$order['o_sn'],'mark'=>$mark,'createtime'=>$createtime];
      			$res = $model->execute($sqles,$bindes);
			}
			if (isset($update)) {
				$order_update = $this->order->where($o_id)->save($update);
				$updates['username'] = $master['o_name'];
				$updates['usercard'] = $master['o_card'];
				$updates['usertel'] = $master['o_phone'];
				$user_update = M('fang_order_user')->where(['ouid'=>$order['userid']])->save($updates);
				$this->success("修改成功",U('Fang/order_list'));

			}
		}


		$this->success("修改成功",U('Fang/order_list'));
		
	}
	/**
	 * [user_add 添加订单用户]
	 * @return [type] [description]
	 */
	public function user_add()
	{
		$o_id['o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));
		$data = $_POST;

		//日志记录start
		$logs = $data ;
		$logs['user'] = session('panterid');
		$logs['o_id'] = $o_id['o_id'];
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        $this->recordLog('user_add',$logs_js); 
        //end
        
		$order = $this->order->where($o_id)->find();
		if (empty($data['o_name']) || empty($data['o_phone']) || empty($data['o_card']))$this->error("添加失败",U('Fang/order_list'));
		$usermsg['username'] = trim($data['o_name']); 
        $usermsg['usertel'] = trim($data['o_phone']); 
        $usermsg['usercard'] = trim($data['o_card']); 
        $usermsg['o_sn'] = trim($order['o_sn']);
        $usermsg['levels'] = 2;
		$user = M('fang_order_user')->Where($usermsg)->find();
		$model = new model();
       	if (!$user) {
       		$usermsg['ouid'] = $this->getFieldNextNumber('fang_order_user');
        	$usermsg['createtime'] = time();
            $sqls = "INSERT INTO fang_order_user (ouid , o_sn , createtime  , username ,usertel,usercard, levels) VALUES (%d , %d , %d , '%s' , %d , %d ,%d)";
            $binds = ['ouid'=>$usermsg['ouid'],'o_sn'=>$usermsg['o_sn'],'createtime'=>$usermsg['createtime'],'username'=>$usermsg['username'],'usertel'=>$usermsg['usertel'],'usercard'=>$usermsg['usercard'],'levels'=>$usermsg['levels']];
      		$res2 = $model->execute($sqls,$binds);
      		if ($res2) {
       			$this->success("添加成功",U('Fang/order_list'));
       		} else {
       			$this->error("添加失败",U('Fang/order_list'));
        	}
       	} else {
       		$this->error("此用户已存在",U('Fang/order_list'));
       	}
        
        
	}
	/**
	 * [order_verification 核销订单]
	 * @return [type] [description]
	 */
	public function order_verification()
	{
		$str = array();
		$id = I('post.','');
		if (empty($id)) {
			$this->error("请选择订单",U('Fang/order_list'));
		}
		//日志记录start
		$logs = $id ;
		$logs['user'] = session('panterid');
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
        $this->recordLog('order_verification',$logs_js); 
        //end

		$where['o_id'] = array('in', $id['o_id']);
		$where['o_status'] = 1;
		$order = $this->order->where($where)->select();
		foreach ($order as $key => $value) {
			if ($value['price_type']== 1) {
        		$data[$key]['type']  = '01'; //01 自有资金 02备付金
        	} else {
        		if ($value['is_freeze']  == 1) {
					$data[$key]['type'] = '02';//资金类型 01 冻结 02提现 03消费 04备付金
				} else {
				    $data[$key]['type'] = '01';//资金类型 01 冻结 02提现 03消费 04备付金
				}
        	}
        	$data[$key]['phone'] = $value['o_phone'];
        	$data[$key]['amount'] = floatval($value['o_price']);
        	$data[$key]['order_sn'] = $value['o_sn'];
        	$data[$key]['panterid'] = $value['panterid'];
        	$data[$key]['storeid'] =  $value['storeid'];
        	$data[$key]['source'] = '05';
        	$data[$key]['description'] = '房掌柜';
        	$code = 'JYO2O01';
    		$str[$key] = $code . $data[$key]['phone'] . $data[$key]['amount'] . $data[$key]['type'] . $data[$key]['order_sn'] . $data[$key]['panterid'] . $data[$key]['storeid'];
    		$data[$key]['key'] = md5($str[$key]);
		}
		if (empty($data)) {
			$this->error("只能核销已支付订单",U('Fang/order_list'));
		}
		$js = json_encode($data);
       	$url =  $this->urls . "/index.php/index/index/charge";//扣款接口
		$res = $this->CurlPost($url,$js);
        $this->recordLog('order_verification',$res); 
		$result = json_decode($res,true);

		foreach ($result as $key => $value) {
			if ($value['status'] == 1 && $value['status'] == 2) {
				$sql = $this->order->where(['o_sn'=>$key])->save(['o_status'=>2]);
			}
		}
		$this->success("核销成功",U('Fang/order_list'));
		
		
		
	}

	/**
	 * [excel_verification 批量核销订单]
	 * @return [type] [description]
	 */
	public function excel_verification()
	{
		$stringData = "";
		$str = array();
		$model = new  Model();
		if (IS_POST) {
			if (empty($_FILES['file_stu']['name'])) {
                $this->error('请上传文件', U('Fang/excel_verification'), 5);
            }
            $upload = $this->_upload('excel');
            $data = array();
            if (is_string($upload)) {
                $this->error($upload, U('Fang/excel_verification'), 5);
            } else {
                $filename = PUBLIC_PATH . $upload['file_stu']['savepath'] . $upload['file_stu']['savename'];
                $exceldate = $this->import_excel($filename);
                $batchRechargeLog = array();
                $count = 0;
                $ksnum = 1;
                $arrs = $this->auth_all();
				if (!in_array($this->panterid, $arrs)) {
					$wheres['panterid'] = $this->panterid;
				}
           		$wheres['o_status'] = 1;
 				$order = $this->order->where($wheres)->select();
				$order_msg=array();
				if (!empty($order)) {
					foreach($order as $v){
       					$order_msg[$v['o_sn']]=$v;
       				}
				}
      			
       			$field = 'fang_ver';
       			$ver = M($field);
       			$fang_refund = $ver->where($wheres)->select();
       			if (!empty($fang_refund)) {
       				foreach($fang_refund as $v){
       					$fang_refund[$v['o_sn']]=$v;
       				}
       			}
                foreach ($exceldate as $key => $value) {
                    if ($ksnum == 500) {
                        $ksnum = 1;
                        sleep(1);
                    }
                    $value[0] = trim($value[0]);
                    $value[2] = trim($value[2]);
                    $value[3] = trim($value[3]);
                    $value[4] = trim($value[4]);
                    $value[5] = trim($value[5]);
                    $value[6] = trim($value[6]);
                    $value[7] = trim($value[7]);
                    $value[8] = trim($value[8]);
          
                    if(array_key_exists(trim($value[0]), $order_msg)){

                    	if(array_key_exists(trim($value[0]), $fang_refund)){
                    		$update['ver_time'] = time();
                    		$update['status'] =  1;
                    		$update['mark']= '核销失败';
                    		$ver->where(['o_sn'=>$value[0]])->save($update);
                    	} else {
                    		$verid  = $this->getFieldNextNumber($field);
                    		$ver_time = time();
                    		$o_sn = $value[0];
                    		$status =  1;
                    		$mark = '核销失败';
                    		$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$fang_refund[$value[0]] = $bind;
                    		$model->execute($sql,$bind);
                    	}
                    	$ksnum++;
                    	if ($order_msg[$value[0]]['o_name']  != trim($value['2']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['ver_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户姓名有误';
                    			$ver->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$verid  = $this->getFieldNextNumber($field);
                    			$ver_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户姓名有误';
                    			$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_phone'] != trim($value['3']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['ver_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户手机号有误';
                    			$ver->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$verid  = $this->getFieldNextNumber($field);
                    			$ver_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户手机号有误';
                    			$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_card']  != trim(str_replace("'","",$value['4'])))   {

                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['ver_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户身份证号码有误';
                    			$ver->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$verid  = $this->getFieldNextNumber($field);
                    			$ver_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户身份证号码有误';
                    			$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_price'] != trim($value['8']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['ver_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户订单金额有误';
                    			$ver->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$verid  = $this->getFieldNextNumber($field);
                    			$ver_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户订单金额有误';
                    			$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}

                
                    	$data[$key]['phone'] = $order_msg[$value[0]]['o_phone'];
                    	$data[$key]['amount'] = floatval($order_msg[$value[0]]['o_price']);
                    	if ($order_msg[$value[0]]['price_type']== 1) {
                    		$data[$key]['type']  = '01';
                    	} else {
                    		if ($order_msg[$value[0]]['is_freeze']  == 1) {
								$data[$key]['type'] = '02';//资金类型 01 冻结 02提现 03消费 04备付金
							} else {
								$data[$key]['type'] = '01';//资金类型 01 冻结 02提现 03消费 04备付金
							}
                    	}
                    	$data[$key]['order_sn'] = $order_msg[$value[0]]['o_sn'];
                    	$data[$key]['panterid'] = $order_msg[$value[0]]['panterid'];
                    	$data[$key]['storeid'] = $order_msg[$value[0]]['storeid'];
                    	$data[$key]['source'] = '05';
                    	$data[$key]['description'] = '房掌柜';
                    	$code = 'JYO2O01';
    					$str[$key] = $code . $data[$key]['phone'] . $data[$key]['amount'] . $data[$key]['type'] . $data[$key]['order_sn'] . $data[$key]['panterid'] . $data[$key]['storeid'];
    					$data[$key]['key'] = md5($str[$key]);
            		} else {
                    	if(array_key_exists(trim($value[0]), $fang_refund)){
                    		$update['ver_time'] = time();
                    		$update['status'] =  1;
                    		$update['mark']= '未查找到项目中此订单或者状态不是已付款';
                    		$ver->where(['o_sn'=>$value[0]])->save($update);
                    	} else {
                    		$verid  = $this->getFieldNextNumber($field);
                    		$ver_time = time();
                    		$o_sn = $value[0];
                    		$status =  1;
                    		$mark = '未查找到项目中此订单或者状态不是已付款';
                    		$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$model->execute($sql,$bind);
                    	}
                    	continue;
            		}
                }
            }
            if (empty($data)) {
				$stringData .="数据为空<hr/>";
			} else {
				$js = json_encode($data);
				$logs_js = json_encode($data,JSON_UNESCAPED_UNICODE);
        		$this->recordLog('excel_verification',$logs_js); 
       			$url =  $this->urls . "/index.php/index/index/charge";//扣款接口
				$res = $this->CurlPost($url,$js);
				$this->recordLog('excel_verification',$res); 
				$result = json_decode($res,true);
				foreach ($result as $key => $value) {
					if ($value['status'] == 1 ||  $value['status'] == 2) {
						$sql = $this->order->where(['o_sn'=>$key])->save(['o_status' => 2]);
						if(array_key_exists(trim($key), $fang_refund)){
                    		$update['ver_time'] = time();
                    		$update['status'] =  2;
                    		$update['mark']= '核销成功';
                    		$ver->where(['o_sn'=>$key])->save($update);
                    	} else {
                    		$verid  = $this->getFieldNextNumber($field);
                    		$ver_time = time();
                    		$o_sn = $key;
                    		$status =  2;
                    		$mark = '核销成功';
                    		$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$model->execute($sql,$bind);
                    	}
					} else {
						if(array_key_exists(trim($key), $fang_refund)){
                    		$update['ver_time'] = time();
                    		$update['status'] =  1;
                    		$update['mark']= $value['message'];
                    		$ver->where(['o_sn'=>$key])->save($update);
                    	} else {
                    		$verid  = $this->getFieldNextNumber($field);
                    		$ver_time = time();
                    		$o_sn = $key;
                    		$status =  1;
                    		$mark = $value['message'];
                    		$sql = "INSERT INTO fang_ver (verid ,ver_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['verid'=>$verid,'ver_time'=>$ver_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$model->execute($sql,$bind);
                    	}
					}
				}
			}
		}

		$nameenglish = trim(I('param.nameenglish','','htmlspecialchars','addslashes')); 
		$store_name = trim(I('param.store_name','','htmlspecialchars','addslashes')); 
		$startdate = trim(I('param.startdate','','htmlspecialchars','addslashes')); 
		$enddate = trim(I('param.enddate','','htmlspecialchars','addslashes')); 
		$o_sn = trim(I('param.o_sn','','htmlspecialchars','addslashes')); 
		$o_name = trim(I('param.o_name','','htmlspecialchars','addslashes')); 
		$o_phone = trim(I('param.o_phone','','htmlspecialchars','addslashes')); 
		$o_card = trim(I('param.o_card','','htmlspecialchars','addslashes'));
		$status = trim(I('param.status','3','htmlspecialchars','addslashes'));
		if (!empty($enddate) && !empty($startdate)) {
        	$start = strtotime($startdate);
        	$end = strtotime($enddate . ' 23:59:59');
            $where['v.ver_time'] = array(array('egt',$start), array('elt', $end));
            $map['startdate'] = $startdate;
            $map['enddate'] = $enddate;
        }
		if (!empty($o_sn)) {
			$map['o_sn'] = $o_sn;
			$where['o.o_sn'] = $o_sn;
		}
		if (!empty($o_name)) {
			$map['o_name'] = $o_name;
			$where['o.o_name'] = $o_name;
		}
		if (!empty($o_phone)) {
			$map['o_phone'] = $o_phone;
			$where['o.o_phone'] = $o_phone;
		}
		if (!empty($o_card)) {
			$map['o_card'] = $o_card;
			$where['o.o_card'] = $o_card;
		}
		if (!empty($status) && $status != 3) {
			$map['status'] = $status;
			$where['v.status'] = $status;
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$where['p.panterid'] = $nameenglish;
			$storename  = $this->zzk_store->where(array('panterid'=>$nameenglish))->order('storeid desc')->field('storeid,name')->select();//项目信息
		}
		if (!empty($store_name)) {
			$map['store_name'] = $store_name;
			$where['s.storeid'] = $store_name;

		}


     	$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$where['o.panterid'] = $this->panterid;
			$pantername =  M('panters')->where(['panterid'=>$where['o.panterid']])->field('panterid,nameenglish')->order('panterid desc')->select();
		} else {
			$pantername =  M('panters')->where(['revorkflg'=>'N'])->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$where['p.panterid'] = $nameenglish;
			$storename  = $this->zzk_store->where(array('panterid'=>$nameenglish))->order('storeid desc')->field('storeid,name')->select();//项目信息
		}

		$data['v.status'] = array('in' , array(1,2));
		if(!empty($map)){
         	foreach($map as $key=>$val) {
            	$p->parameter[$key]= $val;
         	}
        }

        $ver = M('fang_ver');
        session('ver_select',$where);
		$count=$ver->alias('v')
           ->join('left join fang_order o on o.o_sn=v.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,p.nameenglish,s.name,v.*')
           ->where($where)
           ->count();//总记录条数
		$p=new \Think\Page($count, 10);
		$list=$ver->alias('v')
           ->join('left join fang_order o on o.o_sn=v.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('v.*,o.o_card,o.o_phone,o_price,o.o_name,p.nameenglish,s.name')
           ->where($where)
           ->order('ver_time desc')
           ->limit($p->firstRow.','.$p->listRows)
           ->select();//营销商品列表
        if(!empty($map)){
         	foreach($map as $key=>$val) {
            	$p->parameter[$key]= $val;
         	}
        }
        foreach ($list as $key => $value) {
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	if ($value['status'] == 1) {
        		$list[$key]['status'] = '失败';
        	} else {
        		$list[$key]['status'] = '成功';
        	}
        	$list[$key]['ver_time'] = date('Y-m-d H:i:s',$value['ver_time']);
        }

        $page = $p->show();//分页
		$this->assign('startdate',$startdate);
		$this->assign('enddate',$enddate);
		$this->assign('o_sn',$o_sn);
		$this->assign('o_name',$o_name);
		$this->assign('o_phone',$o_phone);
		$this->assign('page',$page);
		$this->assign('o_card',$o_card);
		$this->assign('pantername',$pantername);
		$this->assign('nameenglish',$nameenglish);
		$this->assign('store_name',$store_name);
		$this->assign('storename',$storename);
		$this->assign('status',$status);
		$this->assign('list',$list);
      	$this->display();

		// $logs['user'] = session('panterid');
  //       $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
  //      	$this->recordLog('excel_verification',$logs_js); 
	}


	/**
	 * [is_freeze 解冻]
	 * @return boolean [description]
	 */
	public function is_freeze()
	{
		$o_id['o_id'] = trim(I('get.o_id','','htmlspecialchars','addslashes'));

		//日志记录 start
		$logs['o_id'] = $o_id['o_id'];
		$logs['user'] = session('panterid');
        $logs_js = json_encode($logs,JSON_UNESCAPED_UNICODE);
       	$this->recordLog('is_freeze',$logs_js); 
       	//end

		$o_id['o_status'] = 1;
		$order = $this->order->where($o_id)->find();
		if (!$order) {
			$this->error("订单未支付",U('Fang/order_list'));
		}	
		if ($order['is_freeze'] ==2) {
			$this->error("订单不是冻结状态",U('Fang/order_list'));
		}
		$data['phone'] = $order['o_phone'];	
		$data['amount'] = floatval($order['o_price']);	
		$data['order_sn'] = $order['o_sn'];		//订单号
		$data['panterid'] = $order['panterid'];	//项目ID
		$data['storeid'] = $order['storeid'];	//门店ID
		$data['source'] = '05';	 //房掌柜
		$data['status'] = '1';	 //房掌柜
		$data['type'] = 'unfreeze';	//解冻
		$data['description'] = '订单解冻';	
		$code = 'JYO2O01';
    	$str = $code . $data['order_sn'] . $data['phone'] . $data['amount'] . $data['panterid']  . $data['storeid'] . $data['type'];
    	$data['key'] = md5($str);
		$url =  $this->urls . '/index.php/index/index/freeze_un';//解冻接口
		$js = json_encode($data);
		$res = $this->CurlPost($url,$js);

		$result = json_decode($res,true);
		if ($result['status']) {
			$status['is_freeze'] = 2;
			$update = $this->order->where($o_id)->save($status);
       		$this->success("解冻成功",U('Fang/order_list'));
       	} else {
       		if (isset($result['message'])) {
				$this->error("解冻失败," . $result['message'],U('Fang/order_list'));
			} else {
				$this->error("解冻失败",U('Fang/order_list'));
			}
        }
	}

	public function order_load()
	{	
		$sql = session('order_select');
		$list=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name')
           ->where($sql)
           ->order('o.o_createtime desc')
           ->select();//营销商品列表

        foreach ($list as $key => $value) {
        	$list[$key]['o_price'] = floatval($list[$key]['o_price']);
        	$list[$key]['o_paymoney'] = floatval($list[$key]['o_paymoney']);
        	if ($value['price_type'] == 1) {
        		$list[$key]['type'] = '备付金';
        	} else {
        		$list[$key]['type'] = '自有资金';
        	}
        	if ($value['my_tb'] == 1) {
        		$list[$key]['my'] = '未同步';
        	} else {
        		$list[$key]['my'] = '已同步';
        	}
        	$list[$key]['o_sn'] = "\t" . $value['o_sn'] . "\t";
        	$list[$key]['ctime'] = date('Y-m-d H:i:s',$value['o_createtime']);
        	$list[$key]['ptime'] = date('Y-m-d H:i:s',$value['o_paytime']);
        	if ($value['is_freeze'] == 1) {
        		$list[$key]['freeze'] = '冻结';
        	} else {
        		$list[$key]['freeze'] = '非冻结';
        	}
        	if ($value['o_status'] == 0) {
        		$list[$key]['status'] = '未支付';
        	} elseif($value['o_status'] == 1) {
        		$list[$key]['status'] = '已支付';
        	} elseif($value['o_status'] == 2) {
        		$list[$key]['status'] = '已完成';
        	} elseif($value['o_status'] == 3) {
        		$list[$key]['status'] = '已取消（超时取消）';
        	}elseif($value['o_status'] == 4) {
        		$list[$key]['status'] = '付款中';
        	}elseif($value['o_status'] == 6) {
        		$list[$key]['status'] = '已退款';
        	}
        	$fang_paylog = M('fang_paylog');
        	$paylog = $fang_paylog->where(['o_sn'=>$value['o_sn']])->select();
        	$list[$key]['snno'] = '';
        	if ($paylog) {
        		foreach ($paylog as $k => $val) {
        			$str[$k] = strstr($val['sn_no'],'-');
        			if ($str[$k]) {
        				$arrs[$k] = explode('-', $val['sn_no']);
        				if (is_array($arrs[$k])) {
        					$list[$key]['snno'] .= end($arrs[$k]) . '-';
        				} else {
        					$list[$key]['snno'] .= $val['sn_no'] . '-';
        				}
        			} else {
        				$list[$key]['snno'] .= $val['sn_no'] . '-';
        			}
        		}
        	}
        	$list[$key]['snno'] = rtrim($list[$key]['snno'],'-');


       	}
       	$objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $startCell = 'A1';
        $endCell ='N1';
        //$headerArray=array("订单号","订单时间","姓名","手机号","身份证号","项目名称","项目分期","商品名称","金额(元)","订单状态","冻结状态","置业顾问","置业顾问电话","同步明源");
        $headerArray=array("订单号","订单时间","姓名","手机号","身份证号","项目名称","项目分期","商品名称","金额(元)","订单状态","冻结状态","置业顾问","置业顾问电话","同步明源","交易参考号","已付金额");
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N');
        $setWidth = array(20,20,10,20,23,12,12,15,12,15,15,15,15,15);
        $this->setWidth($setCells, $setWidth);
        $j=2;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['o_sn'])->setCellValue('B'.$j,$val['ctime'])->setCellValue('C'.$j,$val['o_name'])
                   ->setCellValue('D'.$j,$val['o_phone'])->setCellValue('E'.$j,"'".$val['o_card'])->setCellValue('F'.$j,$val['nameenglish'])
                   ->setCellValue('G'.$j,$val['name'])->setCellValue('H'.$j,$val['goods_name'])->setCellValue('I'.$j,$val['o_price'])
                   ->setCellValue('J'.$j,$val['status'])->setCellValue('K'.$j,$val['freeze'])->setCellValue('L'.$j,$val['zy_name'])->setCellValue('M'.$j,$val['zy_phone'])->setCellValue('N'.$j,$val['my'])->setCellValue('O'.$j, "\t" . $val['snno'] . "\t")->setCellValue('P'.$j,$val['o_paymoney']);
            $j++;
        }
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','营销订单.xls');//输出到浏览器
        $objWriter->save("php://output");
	}


	public function fang_auth()
	{
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$data['p.panterid'] = $this->panterid;
		}
		$count = M('fang_auth')->alias('fa')
		 		->join('left join users u on u.userid=fa.userid')
		 		->field('fa.*,u.username,u.description,u.loginname')
				->count();//总记录条数
		$p=new \Think\Page($count, 10);
		$list = M('fang_auth')->alias('fa')
		 		->join('left join users u on u.userid=fa.userid')
		 		->field('fa.*,u.username,u.description,u.loginname')
				->limit($p->firstRow.','.$p->listRows)
           		->select();//列表

        foreach ($list as $key => $value) {
        	$list[$key]['time'] = date('Y-m-d H:i:s',$value['createtime']);
        }
        $page = $p->show();//分页
        $this->assign("page",$page);
        $this->assign("list",$list);
		$this->display();

	}


	public function add_auth_user()
	{
		$this->display(); 
	}

	public function insert_auth_user()
	{
		$model = new Model();
		$loginname['loginname'] = trim(I('param.loginname',''));//登录名
		$data = M('users')->where($loginname)->find();
		if (!$data) {
			$this->error("账号不存在或者输入错误",U('Fang/add_auth_user'));
		}
		$field = 'fang_auth';
		$auth = M($field)->where(['userid'=>$data['userid']])->find();
		if ($auth) {
			$this->error("账号已添加权限",U('Fang/add_auth_user'));
		}
		$time = time();
		$faid  = $this->getFieldNextNumber($field);

		$sql = "INSERT INTO fang_auth (faid , panterid , createtime,userid) VALUES (%d , '%s', %d , '%s' )";
        $bind = ['faid'=>$faid,'panterid'=>$data['panterid'],'createtime'=>$time,'userid'=>$data['userid']];

      	$res = $model->execute($sql,$bind);
      	if ($res) {
      		$this->error("添加权限成功",U('Fang/fang_auth'));
      	}

	}
	public function pos_user()
	{
		$nameenglish = trim(I('param.nameenglish',''));//项目简称
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$data['p.nameenglish'] = array('like','%'.$nameenglish.'%');//商品名称模糊查询
		}
		$data['pu.levels'] = array('in' , array(1,2));
		$count=M('fang_pos_user')->alias('pu')
           ->join('left join zzk_store s on s.storeid=pu.storeid')
           ->join('left join panters p on p.panterid=pu.panterid')
           ->field('pu.*,p.nameenglish,s.name')
           ->where($data)
           ->count();//总记录条数

		$p=new \Think\Page($count, 10);
		$list=M('fang_pos_user')->alias('pu')
           ->join('left join zzk_store s on s.storeid=pu.storeid')
           ->join('left join panters p on p.panterid=pu.panterid')
           ->field('pu.*,p.nameenglish,s.name')
           ->where($data)
           ->limit($p->firstRow.','.$p->listRows)
           ->select();//营销商品列表
        if(!empty($map)){
         foreach($map as $key=>$val) {
             $p->parameter[$key]= $val;
         }
        }
        if (!empty($list)) {
        	foreach ($list as $key => $value) {
        		$list[$key]['time'] = date('Y-m-d H:i:s',$value['createtime']);
        		if ($value['levels'] == 2) {
        			$list[$key]['level'] = '财务';
        		} else {
        			$list[$key]['level'] = '员工';
        		}
        	}
        }

        $page = $p->show();//分页
        $this->assign("page",$page);
        $this->assign("list",$list);
		$this->display();
	}

	public function add_pos_user()
	{
		$model = new Model();
		$arrs = $this->auth_all();
		if (in_array($this->panterid, $arrs)) {
			$panter = $this->panters->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();//项目信息
			$store  = $this->zzk_store->where(array('panterid'=>$panter[0]['panterid']))->order('storeid desc')->select();//项目信息
		}
	
		$this->assign('panter',$panter);
		$this->assign('store',$store);
		$this->display(); 
	}



	public function insert_pos_user()
	{	
		$model = new Model();
		$field = 'fang_pos_user';
		$puid  = $this->getFieldNextNumber($field);
        $username = trim(I('post.username','')); 
        $password = trim(I('post.password','')); 
        $levels = trim(I('post.levels',''));   
        $panterid = trim(I('post.panterid',''));
        $storeid = trim(I('post.storeid',''));
        $createtime = time();
        $data = M($field)->where(['username'=>$username])->find();

        if ($data) {
        	$this->error("添加失败,账号已存在",U('Fang/pos_user'));
        }
        $sql = "INSERT INTO fang_pos_user (puid , username , password, levels,storeid,createtime,panterid ) VALUES (%d , '%s' , '%s' , %d , '%s' , %d , '%s' )";
        $bind = ['puid'=>$puid,'username'=>$username,'password'=>$password,'levels'=>$levels,'storeid'=>$storeid,'createtime'=>$createtime,'panterid'=>$panterid];
      	$res = $model->execute($sql,$bind);
      	if ($res) {
			$this->error("添加成功",U('Fang/pos_user'));
		} else {
			$this->error("添加失败",U('Fang/pos_user'));
		}
	}

	public function pos_user_del()
	{
		$model = new Model();
		$field = 'fang_pos_user';
		$puid = trim(I('get.puid','')); 
	}
	/**
	 * [getFieldNextNumber 序列自增]
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 */
	public function getFieldNextNumber($field)
	{
    	$seq_field='seq_'.$field.'_id.nextval';
    	$sql="select {$seq_field} from dual";
    	$list=Db::query($sql);
    	$lastNumber=$list[0]['nextval'];
    	return  $lastNumber;
	}

	public function curl_postapis($MENU_URL,$data)
 	{
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $MENU_URL);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
    	$info = curl_exec($ch);
	
    	if (curl_errno($ch)) {
    	    echo 'Errno'.curl_error($ch);
    	}
    	curl_close($ch);
    	return json_decode($info,true);
	}


 	/**
 	 * [recordLog 日志]
 	 * @param  [type] $fileName [日志文件名]
 	 * @param  [type] $files    [文件夹名]
 	 * @param  [type] $data     [日志内容]
 	 * @return [type]           [写入文件]
 	 */
 	public function recordLog($fileName,$data)
 	{
        $time = date("Ymd",time());
        $filePath = "./log/fang/{$time}/";
        if(!is_dir($filePath)){
            mkdir($filePath,0777);
        }

        $rtime = date('H:i:s',time());
        $str = $rtime."--message:{$_SERVER['REMOTE_ADDR']}--".$data."\t\n";
        file_put_contents("{$filePath}{$fileName}.log", $str,FILE_APPEND);
    }

    public function auth_all()
    {
    	$arr = array();
    	$data = M('fang_auth')->field('panterid')->select();
    	$data[]['panterid'] = 'FFFFFFFF';
    	foreach ($data as $key => $value) {
    		$arr[] = $value['panterid'];
    	}
    	return $arr;
    }


	function CurlPost($url,$data = null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        if (!empty($data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $info = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno'.curl_error($ch);
        }
        curl_close($ch);
        return $info;
     } 

      public function paylog_add()
     {
     	$o_sn = trim(I('post.o_sn','','htmlspecialchars','addslashes'));
     	$o_paymoney = trim(I('post.o_paymoney','','htmlspecialchars','addslashes'));
     	$paytype = trim(I('post.paytype','','htmlspecialchars','addslashes'));
     	$startdate = trim(I('post.startdates','','htmlspecialchars','addslashes'));
     	$sn_no = trim(I('post.snno','','htmlspecialchars','addslashes'));
     	$model = new Model();
  	    $field = 'fang_paylog';
  	    $fang_paylog = M($field);
  	    $paylog = $fang_paylog->where(['sn_no'=>$sn_no])->find();
  	    $createtime = strtotime($startdate);
  	    // var_dump($paylog);exit;
  	    if ($paylog) {
  	    	$this->error("添加失败,流水号重复",U('Fang/order_lists'));	
  	    } else {
  	    	$order = $this->order->where(['o_sn'=>$o_sn])->find();
  	    	
  	    	$money =  bcadd(floatval($o_paymoney),floatval($order['o_paymoney']),2);
  	    	
  	    	
  	    	if (floatval($money) > floatval($order['o_price'])) {
  	    		$this->error("添加失败,支付金额大于订单金额",U('Fang/order_lists'));
  	    	}
  	    	if ($order['o_createtime'] > $createtime) {
  	    		$this->error("添加失败,添加时间小于订单时间",U('Fang/order_lists'));
  	    	}
  	    	if ($order['o_status'] == 3) {
  	    		$status['o_status'] = 0;
  	    		$this->order->where(['o_sn'=>$o_sn])->save($status);
  	    	}
  	    	if($order['o_status'] == 1 || $order['o_status'] == 2 || $order['o_status'] == 6) {
  	    		$this->error("添加失败,订单状态错误",U('Fang/order_lists'));
  	    	}
  	    	
  	    }
  	    $data['o_sn'] = $o_sn; 
  	    $data['o_paymoney'] = $o_paymoney; 
  	    $data['key'] = '65f0527d1f6bcf8071823e2247c7c526';
  	    $js = json_encode($data);
  	    $url = $this->urls . '/fang/pos/updateStatus';
  	    $result = $this->CurlPost($url,$js);
  	    $results = json_decode($result,true);
  	    if ($results['errorcode'] == 1) {
  	    	$this->error("添加失败," . $results['errormsg'] ,U('Fang/order_lists'));
  	    }
   		
		$payid  = $this->getFieldNextNumber($field);
		$price = floatval($o_paymoney);
		$posnum = '手动添加';
		$bankcard = '手动添加';
   		$sql = "INSERT INTO fang_paylog (payid ,o_sn ,price ,banknum ,posnum ,createtime ,bankcard ,sn_no) VALUES (%d ,'%s', '%s' , '%s' , '%s' , '%s' , '%s' , '%s')";
        $bind = ['payid'=>$payid,'o_sn'=>$o_sn,'price'=>$price,'banknum'=>$paytype,'posnum'=>$posnum,'createtime'=>$createtime,'bankcard'=>$bankcard,'sn_no'=>$sn_no];
      	$res = $model->execute($sql,$bind);
     	if ($res) {
     		$this->success("添加成功",U('Fang/order_lists'));
     	} else {
     		$this->error("添加失败",U('Fang/order_lists'));
     	}
     }


    public function excel_isfreezn()
    {
    	$stringData = "";
    	$model = new Model();
		$str = array();
		if (IS_POST) {
			$types = trim(I('post.types','','htmlspecialchars','addslashes'));
			if (empty($_FILES['file_stu']['name'])) {
                $this->error('请上传文件', U('Fang/excel_isfreezn'), 5);
            }
            $upload = $this->_upload('excel');
            $data = array();
            if (is_string($upload)) {
                $this->error($upload, U('Fang/excel_isfreezn'), 5);
            } else {
                $filename = PUBLIC_PATH . $upload['file_stu']['savepath'] . $upload['file_stu']['savename'];
                $exceldate = $this->import_excel($filename);
                $batchRechargeLog = array();
                $count = 0;
                $ksnum = 1;
                $arrs = $this->auth_all();
				if (!in_array($this->panterid, $arrs)) {
					$wheres['panterid'] = $this->panterid;
				}
           		$wheres['o_status'] = 1;
           		$wheres['is_freeze'] = 1;
 				$order = $this->order->where($wheres)->select();
 				// var_dump($order);exit;
				$order_msg=array();
				if (!empty($order)) {
					foreach($order as $v){
       					$order_msg[$v['o_sn']]=$v;
       				}
				}
      			
       			$field = 'fang_refund';
       			$refund = M($field);
       			$fang_refund = $refund->where($wheres)->select();
       			if (!empty($fang_refund)) {
       				foreach($fang_refund as $v){
       					$fang_refund[$v['o_sn']]=$v;
       				}
       			}
                foreach ($exceldate as $key => $value) {
                    if ($ksnum == 500) {
                        $ksnum = 1;
                        sleep(1);
                    }

                    $value[0] = trim($value[0]);
                    $value[2] = trim($value[2]);
                    $value[3] = trim($value[3]);
                    $value[4] = trim($value[4]);
                    $value[5] = trim($value[5]);
                    $value[6] = trim($value[6]);
                    $value[7] = trim($value[7]);
                    $value[8] = trim($value[8]);
                    if(array_key_exists(trim($value[0]), $order_msg)){
                    	$ksnum++;

                    	if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			if ($types == '退款') {
                    				$update['mark']= '退款失败';
                    			} else {
                    				$update['mark']= '解冻失败';
                    			}
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    	} else {
                    		$refundid  = $this->getFieldNextNumber($field);
                    		$refund_time = time();
                    		$o_sn = $value[0];
                    		$status =  1;
                    		if ($types == '退款') {
                    			$mark= '退款失败';
                    		} else {
                    			$mark= '解冻失败';
                    		}

                    		$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$fang_refund[$value[0]] = $bind;
                    		$model->execute($sql,$bind);
                    	}

                    	if ($order_msg[$value[0]]['o_name']  != trim($value['2']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户姓名有误';
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$refundid  = $this->getFieldNextNumber($field);
                    			$refund_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户姓名有误';
                    			$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_phone'] != trim($value['3']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户手机号有误';
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$refundid  = $this->getFieldNextNumber($field);
                    			$refund_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户手机号有误';
                    			$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_card']  != trim(str_replace("'","",$value['4'])))   {

                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户身份证号码有误';
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$refundid  = $this->getFieldNextNumber($field);
                    			$refund_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户身份证号码有误';
                    			$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		continue;
                    	}
                    	if ($order_msg[$value[0]]['o_price'] != trim($value['8']))   {
                    		if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '此用户订单金额有误';
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$refundid  = $this->getFieldNextNumber($field);
                    			$refund_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '此用户订单金额有误';
                    			$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    		}
                    		// $stringData .="订单号:{$value[0]};姓名:{$value[2]};手机号:{$value[3]};身份证号码:{$value[4]};金额:{$value[8]}----状态:此用户订单金额有误<hr/>";
                    		continue;
                    	}
                    	$data[$key]['phone'] = $order_msg[$value[0]]['o_phone'];
                    	$data[$key]['amount'] = floatval($order_msg[$value[0]]['o_price']);
                    	$data[$key]['order_sn'] = $order_msg[$value[0]]['o_sn'];
                    	$data[$key]['panterid'] = $order_msg[$value[0]]['panterid'];
                    	$data[$key]['storeid'] = $order_msg[$value[0]]['storeid'];
                    	$data[$key]['type'] = 'unfreeze';
                    	$data[$key]['source'] = '05';
                    	$data[$key]['description'] = '房掌柜订单解冻';
                    	$data[$key]['status'] = 1;
                    	$code = 'JYO2O01';
    					$str[$key] = $code . $data[$key]['order_sn'] . $data[$key]['phone'] . $data[$key]['amount'] . $data[$key]['panterid'] . $data[$key]['storeid'] . $data[$key]['type'];
    					$data[$key]['key'] = md5($str[$key]);
            		} else {
            			if(array_key_exists(trim($value[0]), $fang_refund)){
                    			$update['refund_time'] = time();
                    			$update['status'] =  1;
                    			$update['mark']= '订单状态不是已付款或者已解冻';
                    			$refund->where(['o_sn'=>$value[0]])->save($update);
                    		} else {
                    			$refundid  = $this->getFieldNextNumber($field);
                    			$refund_time = time();
                    			$o_sn = $value[0];
                    			$status =  1;
                    			$mark = '订单状态不是已付款或者已解冻';
                    			$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        						$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    			$model->execute($sql,$bind);
                    	}
            			 continue;
            		}
                }
            }
            if (empty($data)) {
				$stringData .="数据为空<hr/>";
			} else {
				$js = json_encode($data);
       			$logs_js = json_encode($data,JSON_UNESCAPED_UNICODE);
        		$this->recordLog('excel_isfreezn',$logs_js); 
       			$url =  $this->urls . "/index.php/index/index/unfreezes";//扣款接口
				$res = $this->CurlPost($url,$js);
				$this->recordLog('excel_isfreezn',$res); 
				$result = json_decode($res,true);
				foreach ($result as $key => $value) {
					if ($value['status'] == 1 || $value['status'] == 2) {
						if ($types == '退款') {
							$order_update['o_status'] = 6; 
							$order_update['is_freeze'] = 2;
							$this->order->where(['o_sn'=>$key])->save($order_update);
						} else {
							$order_update['is_freeze'] = 2;
							$this->order->where(['o_sn'=>$key])->save($order_update);
						}
						if(array_key_exists(trim($key), $fang_refund)){
                    		$update['refund_time'] = time();
                    		$update['status'] =  2;
                    		if ($types == '退款') {
                    			$update['mark']= '退款成功';
                    		} else {
                    			$update['mark']= '解冻成功';
                    		}
                    		$refund->where(['o_sn'=>$key])->save($update);
                    	} else {
                    		$refundid  = $this->getFieldNextNumber($field);
                    		$refund_time = time();
                    		$o_sn = $key;
                    		$status =  2;
                    		if ($types == '退款') {
                    			$mark = '退款成功';
                    		} else {
                    			$mark = '解冻成功';
                    		}
                    		$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$model->execute($sql,$bind);
                    	}
					} else {
						if(array_key_exists(trim($key), $fang_refund)){
                    		$update['refund_time'] = time();
                    		$update['status'] =  1;
                    		$update['mark']= $value['message'];
                    		$refund->where(['o_sn'=>$key])->save($update);
                    	} else {
                    		$refundid  = $this->getFieldNextNumber($field);
                    		$refund_time = time();
                    		$o_sn = $key;
                    		$status =  1;
                    		$mark = $value['message'];
                    		$sql = "INSERT INTO fang_refund (refundid ,refund_time ,o_sn ,status ,mark ) VALUES (%d ,'%s', '%s' , '%s' , '%s')";
        					$bind = ['refundid'=>$refundid,'refund_time'=>$refund_time,'o_sn'=>$o_sn,'status'=>$status,'mark'=>$mark];
                    		$model->execute($sql,$bind);
                    	}
					}
				}
			}

		}
     	$nameenglish = trim(I('param.nameenglish','','htmlspecialchars','addslashes')); 
		$store_name = trim(I('param.store_name','','htmlspecialchars','addslashes')); 
		$startdate = trim(I('param.startdate','','htmlspecialchars','addslashes')); 
		$enddate = trim(I('param.enddate','','htmlspecialchars','addslashes')); 
		$o_sn = trim(I('param.o_sn','','htmlspecialchars','addslashes')); 
		$o_name = trim(I('param.o_name','','htmlspecialchars','addslashes')); 
		$o_phone = trim(I('param.o_phone','','htmlspecialchars','addslashes')); 
		$o_card = trim(I('param.o_card','','htmlspecialchars','addslashes'));
		$status = trim(I('param.status','3','htmlspecialchars','addslashes'));
		if (!empty($enddate) && !empty($startdate)) {
        	$start = strtotime($startdate);
        	$end = strtotime($enddate . ' 23:59:59');
            $where['r.refund_time'] = array(array('egt',$start), array('elt', $end));
            $map['startdate'] = $startdate;
            $map['enddate'] = $enddate;
        }
		if (!empty($o_sn)) {
			$map['o_sn'] = $o_sn;
			$where['o.o_sn'] = $o_sn;
		}
		if (!empty($o_name)) {
			$map['o_name'] = $o_name;
			$where['o.o_name'] = $o_name;
		}
		if (!empty($o_phone)) {
			$map['o_phone'] = $o_phone;
			$where['o.o_phone'] = $o_phone;
		}
		if (!empty($o_card)) {
			$map['o_card'] = $o_card;
			$where['o.o_card'] = $o_card;
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$where['p.panterid'] = $nameenglish;
			$storename  = $this->zzk_store->where(array('panterid'=>$nameenglish))->order('storeid desc')->field('storeid,name')->select();//项目信息
		}
		if (!empty($store_name)) {
			$map['store_name'] = $store_name;
			$where['s.storeid'] = $store_name;
		}
		if (!empty($status) && $status != 3) {
			$map['status'] = $status;
			$where['r.status'] = $status;
		}

     	$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$where['o.panterid'] = $this->panterid;
			$pantername =  M('panters')->where(['panterid'=>$where['o.panterid']])->field('panterid,nameenglish')->order('panterid desc')->select();
		} else {
			$pantername =  M('panters')->where(['revorkflg'=>'N'])->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$where['p.panterid'] = $nameenglish;
			$storename  = $this->zzk_store->where(array('panterid'=>$nameenglish))->order('storeid desc')->field('storeid,name')->select();//项目信息
		}
		if(!empty($map)){
         	foreach($map as $key=>$val) {
            	$p->parameter[$key]= $val;
         	}
        }
        // var_dump($where);exit;
        $refund = M('fang_refund');
        session('refund_select',$where);
		$count=$refund->alias('r')
           ->join('left join fang_order o on o.o_sn=r.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,p.nameenglish,s.name,r.*')
           ->where($where)
           ->count();//总记录条数
		$p=new \Think\Page($count, 10);
		$list=$refund->alias('r')
           ->join('left join fang_order o on o.o_sn=r.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('r.*,o.o_card,o.o_phone,o_price,o.o_name,p.nameenglish,s.name')
           ->where($where)
           ->order('refund_time desc')
           ->limit($p->firstRow.','.$p->listRows)
           ->select();//营销商品列表
        if(!empty($map)){
         	foreach($map as $key=>$val) {
            	$p->parameter[$key]= $val;
         	}
        }
        foreach ($list as $key => $value) {
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	if ($value['status'] == 1) {
        		$list[$key]['status'] = '失败';
        	} else {
        		$list[$key]['status'] = '成功';
        	}
        	$list[$key]['refund_time'] = date('Y-m-d H:i:s',$value['refund_time']);
        }
        // echo '<pre>';
        // var_dump($list);exit;
        $page = $p->show();//分页
		$this->assign('startdate',$startdate);
		$this->assign('enddate',$enddate);
		$this->assign('o_sn',$o_sn);
		$this->assign('o_name',$o_name);
		$this->assign('o_phone',$o_phone);
		$this->assign('o_card',$o_card);
		$this->assign('page',$page);
		$this->assign('pantername',$pantername);
		$this->assign('nameenglish',$nameenglish);
		$this->assign('store_name',$store_name);
		$this->assign('storename',$storename);
		$this->assign('status',$status);
		$this->assign('list',$list);
      	$this->display();
     }





	/**
	 * [order_list 订单列表]
	 * o_sn    订单号
	 * o_name  姓名
	 * o_phone 手机号
	 * o_card  身份证号码
	 * nameenglish 项目名称
	 * store_name 门店姓名
	 * o_status  订单状态
	 * is_freeze 冻结状态
	 * zy_name  置业顾问姓名
	 * zy_phone 置业顾问手机号
	 * mingyuan 明源
	 *
	 *
	 *
	 * 
	 * @return [type] [description]
	 */
	public function order_lists()
	{
		$o_sn = trim(I('param.o_sn','','htmlspecialchars','addslashes')); 
		$o_name = trim(I('param.o_name','','htmlspecialchars','addslashes')); 
		$o_phone = trim(I('param.o_phone','','htmlspecialchars','addslashes')); 
		$o_card = trim(I('param.o_card','','htmlspecialchars','addslashes')); 
		$nameenglish = trim(I('param.nameenglish','','htmlspecialchars','addslashes')); 
		$store_name = trim(I('param.store_name','','htmlspecialchars','addslashes')); 
		$o_status = trim(I('param.o_status','5','htmlspecialchars','addslashes')); 
		$is_freeze = trim(I('param.is_freeze','3','htmlspecialchars','addslashes')); 
		$zy_name = trim(I('param.zy_name','','htmlspecialchars','addslashes')); 
		$zy_phone = trim(I('param.zy_phone','','htmlspecialchars','addslashes')); 
		$mingyuan = trim(I('param.mingyuan','','htmlspecialchars','addslashes'));
		$startdate = trim(I('param.startdate','','htmlspecialchars','addslashes'));
        $enddate = trim(I('param.enddate','','htmlspecialchars','addslashes'));
        if (!empty($enddate) && !empty($startdate)) {
        	$start = strtotime($startdate);
        	$end = strtotime($enddate . ' 23:59:59');
            $data['o.o_createtime'] = array(array('egt',$start), array('elt', $end));
            $map['startdate'] = $startdate;
            $map['enddate'] = $enddate;
        }
		if (!empty($o_sn)) {    
			$map['o_sn'] = $o_sn;
			$data['o.o_sn'] = $o_sn;
		}
		if (!empty($o_name)) {
			$map['o_name'] = $o_name;
			$data['o.o_name'] = $o_name;
		}
		if (!empty($o_phone)) {
			$map['o_phone'] = $o_phone;
			$data['o.o_phone'] = $o_phone;
		}
		if (!empty($o_card)) {
			$map['o_card'] = $o_card;
			$data['o.o_card'] = $o_card;
		}
		if (!empty($nameenglish)) {
			$map['nameenglish'] = $nameenglish;
			$data['p.panterid'] = $nameenglish;
			$storename  = $this->zzk_store->where(array('panterid'=>$nameenglish))->order('storeid desc')->field('storeid,name')->select();//项目信息

		}
		if (!empty($o_status) ||  $o_status == 0) {
			$map['o_status'] = $o_status;
			if ($o_status != 5) {
				$data['o.o_status'] = $o_status;
			} else {
				$data['o.o_status'] = array('in' , array(0,1,2,3,4));
			}
		} else {
			$data['o.o_status'] = array('in' , array(0,1,2,3,4));
		}
	
		if (!empty($store_name)) {
			$map['store_name'] = $store_name;
			$data['s.storeid'] = $store_name;
		}
		// var_dump($data);exit;
		if (!empty($is_freeze)) {
			if ($is_freeze !=3) {
				$map['is_freeze'] = $is_freeze;
				$data['o.is_freeze'] = $is_freeze;
			}
		}
		if (!empty($zy_name)) {
			$map['zy_name'] = $zy_name;
			$data['o.zy_name'] = $zy_name;
		}
		if (!empty($zy_phone)) {
			$map['zy_phone'] = $zy_phone;
			$data['o.zy_phone'] = $zy_phone;
		}
		$arrs = $this->auth_all();
		if (!in_array($this->panterid, $arrs)) {
			$data['o.panterid'] = $this->panterid;
			$pantername =  M('panters')->where(['panterid'=>$data['o.panterid']])->field('panterid,nameenglish')->order('panterid desc')->select();
		} else {
			$pantername =  M('panters')->where(['revorkflg'=>'N'])->where(['hysx'=>'房产/物业'])->where(['nameenglish'=>array('notlike','%'.'物业'.'%')])->field('panterid,nameenglish')->order('panterid desc')->select();

		}

  		session('order_select',$data);
		$count=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name')
           ->where($data)
           ->count();//总记录条数

		$p=new \Think\Page($count, 10);
		$list=$this->order->alias('o')
           ->join('left join fang_goods g on g.gid=o.gid')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('o.*,g.goods_name,p.nameenglish,s.name')
           ->where($data)
           ->limit($p->firstRow.','.$p->listRows)
           ->order('o.o_createtime desc')
           ->select();//营销商品列表
        if(!empty($map)){
         	foreach($map as $key=>$val) {
            	$p->parameter[$key]= $val;
         	}
        }
        $page = $p->show();//分页
         foreach ($list as $key => $value) {
        	if ($value['price_type'] == 1) {
        		$list[$key]['type'] = '备付金';
        	} else {
        		$list[$key]['type'] = '自有资金';
        	}
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	$list[$key]['o_paymoney'] = floatval($value['o_paymoney']);
        	if ($value['my_tb'] == 1) {
        		$list[$key]['my'] = '未同步';
        	} else {
        		$list[$key]['my'] = '已同步';
        	}
        	$list[$key]['ctime'] = date('Y-m-d H:i:s',$value['o_createtime']);
        	$list[$key]['ptime'] = date('Y-m-d H:i:s',$value['o_paytime']);
        	$fang_paylog = M('fang_paylog');
        	$paylog = $fang_paylog->where(['o_sn'=>$value['o_sn']])->select();
        	$list[$key]['snno'] = '';
        	if ($paylog) {
        		foreach ($paylog as $k => $val) {
        			$str[$k] = strstr($val['sn_no'],'-');
        			if ($str[$k]) {
        				$arrs[$k] = explode('-', $val['sn_no']);
        				if (is_array($arrs[$k])) {
        					$list[$key]['snno'] .= end($arrs[$k]) . ' - ';
        				} else {
        					$list[$key]['snno'] .= $val['sn_no'] . ' - ';
        				}
        			} else {
        				$list[$key]['snno'] .= $val['sn_no'] . ' - ';
        			}
        		}
        	}
        	$list[$key]['snno'] = rtrim($list[$key]['snno'],' - ');
        	if ($value['is_freeze'] == 1) {
        		$list[$key]['freeze'] = '冻结';
        		$list[$key]['freeze_link'] ='<button type="button" class="btn btn-mini btn-primary jds" onclick="changes('.$value['o_id'].')">解冻</button>';
        	} else {
        		$list[$key]['freeze'] = '非冻结';
        	}
        	if ($value['o_status'] == 0) {
        		$list[$key]['status'] = '未支付';
        	} elseif($value['o_status'] == 1) {
        		// $list[$key]['edit'] ='<button type="button" class="btn btn-mini btn-primary" onclick="window.location='{:U('order_edit',array('o_id'=>$vo['o_id']))}'">编辑</button>&nbsp&nbsp';
        		$list[$key]['edit'] ='<button type="button" class="btn btn-mini btn-primary" onclick="window.location=\'/zzkp.php/Fang/order_edit/o_id/'.$value['o_id'].'\'">编辑</button>';
        		$list[$key]['status'] = '已支付';
        	} elseif($value['o_status'] == 2) {
        		$list[$key]['status'] = '已完成';
        	} elseif($value['o_status'] == 3) {
        		$list[$key]['status'] = '已取消（超时取消）';
        	}  elseif($value['o_status'] == 4) {
        		$list[$key]['status'] = '付款中';
        	}
        }

       	$this->assign('startdate',$startdate);
        $this->assign('enddate',$enddate);
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('o_sn',$o_sn);  
		$this->assign('o_name',$o_name);  
		$this->assign('o_phone',$o_phone);  
		$this->assign('o_card',$o_card);  
		$this->assign('pantername',$pantername); 
		$this->assign('storename',$storename);  
		$this->assign('nameenglish',$nameenglish);
		$this->assign('store_name', $store_name);
		$this->assign('o_status',$o_status);   
		$this->assign('is_freeze',$is_freeze);  
		$this->assign('zy_name',$zy_name);  
		$this->assign('zy_phone',$zy_phone);   
        $this->display();   
	}


	public function refund_load()
	{
 
        $sql = session('refund_select');
        $refund = M('fang_refund');
		$list = $refund->alias('r')
           ->join('left join fang_order o on o.o_sn=r.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('r.*,o.o_card,o.o_phone,o_price,o.o_name,p.nameenglish,s.name')
           ->where($sql)
           ->order('r.refund_time desc')
           ->select();//营销商品列表   
        foreach ($list as $key => $value) {
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	$list[$key]['o_sn'] = "\t" .$value['o_sn'] . "\t";
        	$list[$key]['o_phone'] = "\t" .$value['o_phone'] . "\t";
        	$list[$key]['o_card'] = "\t" .$value['o_card'] . "\t";
        	if ($value['status'] == 1) {
        		$list[$key]['status'] = '失败';
        	} else {
        		$list[$key]['status'] = '成功';
        	}
        	$list[$key]['refund_time'] = date('Y-m-d H:i:s',$value['refund_time']);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $startCell = 'A1';
        $endCell ='L1';
        //$headerArray=array("订单号","订单时间","姓名","手机号","身份证号","项目名称","项目分期","商品名称","金额(元)","订单状态","冻结状态","置业顾问","置业顾问电话","同步明源");
        $headerArray=array("序号","退款时间","订单号","姓名","手机号","身份证号","金额(元)","项目ID","项目简称","退款状态","备注");
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K');
        $setWidth = array(20,20,10,20,23,12,12,15,12,15,15);
        $this->setWidth($setCells, $setWidth);
        $j=2;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['refundid'])->setCellValue('B'.$j,$val['refund_time'])->setCellValue('C'.$j,$val['o_sn'])
                   ->setCellValue('D'.$j,$val['o_name'])->setCellValue('E'.$j,$val['o_phone'])->setCellValue('F'.$j,$val['o_card'])
                   ->setCellValue('G'.$j,$val['o_price'])->setCellValue('H'.$j,$val['nameenglish'])->setCellValue('I'.$j,$val['name'])
                   ->setCellValue('J'.$j,$val['status'])->setCellValue('K'.$j,$val['mark']);
            $j++;
        }
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','退款订单.xls');//输出到浏览器
        $objWriter->save("php://output");
	}


	public function ver_load()
	{
 
        $sql = session('ver_select');
        $ver = M('fang_ver');
		$list = $ver->alias('v')
           ->join('left join fang_order o on o.o_sn=v.o_sn')
           ->join('left join zzk_store s on s.storeid=o.storeid')
           ->join('left join panters p on p.panterid=o.panterid')
           ->field('v.*,o.o_card,o.o_phone,o_price,o.o_name,p.nameenglish,s.name')
           ->where($sql)
           ->order('v.ver_time desc')
           ->select();//营销商品列表   
        foreach ($list as $key => $value) {
        	$list[$key]['o_price'] = floatval($value['o_price']);
        	$list[$key]['o_sn'] = "\t" .$value['o_sn'] . "\t";
        	$list[$key]['o_phone'] = "\t" .$value['o_phone'] . "\t";
        	$list[$key]['o_card'] = "\t" .$value['o_card'] . "\t";
        	if ($value['status'] == 1) {
        		$list[$key]['status'] = '失败';
        	} else {
        		$list[$key]['status'] = '成功';
        	}
        	$list[$key]['ver_time'] = date('Y-m-d H:i:s',$value['ver_time']);
        }
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $startCell = 'A1';
        $endCell ='L1';
        //$headerArray=array("订单号","订单时间","姓名","手机号","身份证号","项目名称","项目分期","商品名称","金额(元)","订单状态","冻结状态","置业顾问","置业顾问电话","同步明源");
        $headerArray=array("序号","核销时间","订单号","姓名","手机号","身份证号","金额(元)","项目ID","项目简称","核销状态","备注");
        $this->setHeader($startCell, $endCell,$headerArray);
        //setWidth
        $setCells = array('A','B','C','D','E','F','G','H','I','J','K');
        $setWidth = array(20,20,10,20,23,12,12,15,12,15,15);
        $this->setWidth($setCells, $setWidth);
        $j=2;
        foreach ($list as $key => $val){
            $objSheet->setCellValue('A'.$j,$val['verid'])->setCellValue('B'.$j,$val['ver_time'])->setCellValue('C'.$j,$val['o_sn'])
                   ->setCellValue('D'.$j,$val['o_name'])->setCellValue('E'.$j,$val['o_phone'])->setCellValue('F'.$j,$val['o_card'])
                   ->setCellValue('G'.$j,$val['o_price'])->setCellValue('H'.$j,$val['nameenglish'])->setCellValue('I'.$j,$val['name'])
                   ->setCellValue('J'.$j,$val['status'])->setCellValue('K'.$j,$val['mark']);
            $j++;
        }
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $this->browser_export('Excel5','核销订单.xls');//输出到浏览器
        $objWriter->save("php://output");
	}

}