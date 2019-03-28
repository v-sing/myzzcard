<?php
namespace Home\Controller;

use Think\Controller;
use Think\Model;

class EquityFundController extends CommonController
{
	/**
	 * 赎回审核
	 */
	public function withdraw_audit()
	{
		if (IS_POST) {
			$list = I('post.');
			foreach ($list['tradeid'] as $k => $v) {
				$t_ransom = M("t_ransom"); // 实例化User对象
				$data['status'] = '1';
				$rs[] = $t_ransom->where('id=' . $v)->save($data); // 根据条件更新记录
			}
		}
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$bankname = I('get.bankname');
		$customid = I('get.customid');
		$type = I('get.type');
		$num = I('get.num');
		$where['status'] = 0;
		$where['source'] = '06';
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($bankname)) {
			$where['bankname'] = $bankname;
		}
		if (!empty($type)) {
			$where['status'] = $type;
		}
		if (!empty($num)) {
			$where['num'] = $num;
		}
		if (!empty($customid)) {
			$where['customid'] = $customid;
		}
		$count = M('t_ransom')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('t_ransom')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('datetime desc')->select();
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$list[$k]['name'] = $customs['namechinese'];
			$list[$k]['elecchequeno'] = md5("zyzj" . time() . rand(10000, 99999));
			$list[$k]['cause'] = '5';
			$list[$k]['account_type'] = '1';
			$list[$k]['bankno'] = $this->aes_decrypt($v['bankno'], md5('bvhke$#67jsd！43_)'));
			$list[$k]['phone'] = $this->aes_decrypt($v['phone'], md5('bvhke$#67jsd！43_)'));
		}
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 赎回确认
	 */
	public function withdraw_confirm()
	{
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$bankname = I('get.bankname');
		$num = I('get.num');
		$type = I('get.type');
		$status = I('get.status');
		$batchno = I('get.batchno');
		$customid = I('get.customid');
		$where = array('status' => array('neq', 0));
		// , 'type' => 10
		$where['source'] = '06';
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($bankname)) {
			$where['bankname'] = $bankname;
		}
		if (!empty($type)) {
			$where['type'] = $type;
		}
		if (!empty($batchno)) {
			$where['batchno'] = $batchno;
		}
		if (!empty($num)) {
			$where['num'] = $num;
		}
		if (!empty($status)) {
			$where['status'] = $status;
		}
		if (!empty($customid)) {
			$where['customid'] = $customid;
		}
		session('withdraw_confirm_excel', $where);            //将查询条件保存到session中，导出EXCEL时候取出来使用
		$count = M('t_ransom')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('t_ransom')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('datetime desc')->select();
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$list[$k]['name'] = $customs['namechinese'];
			// $list[$k]['elecchequeno'] = "fzgsh_yq_" . time() . rand(10000, 99999);
			$list[$k]['cause'] = '4';
			$list[$k]['account_type'] = '1';
			$list[$k]['bankno'] = $this->aes_decrypt($v['bankno'], md5('bvhke$#67jsd！43_)'));
			$list[$k]['phone'] = $this->aes_decrypt($v['phone'], md5('bvhke$#67jsd！43_)'));
			$list[$k]['amount'] = floatval($v['amount']);
		}
		$this->assign("list", $list);
		$this->display();
	}

	public function withdraw_confirm_excel()
	{
		$where = session('withdraw_confirm_excel');
		$list = M('t_ransom')->where($where)->order('datetime desc')->select();
		$data = [['赎回流水号', '赎回时间', '批次号', '姓名', '手机号', '银行名称', '银行卡号', '赎回金额(元)', '业务名称', '赎回状态']];
		$i = 1;
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$data[$i]['num'] = $v['num'];
			$data[$i]['datetime'] = date('Y-m-d H:i:s', $v['datetime']);
			$data[$i]['batchno'] = $v['batchno'];
			$data[$i]['name'] = $customs['namechinese'];
			$data[$i]['phone'] = $this->aes_decrypt($v['phone'], md5('bvhke$#67jsd！43_)'));
			$data[$i]['bankname'] = $v['bankname'];
			$data[$i]['bankno'] = $this->aes_decrypt($v['bankno'], md5('bvhke$#67jsd！43_)'));
			$data[$i]['amount'] = floatval($v['amount']);
			$data[$i]['a_name'] = $v['a_name'];
			$data[$i]['type'] = C("t_ransomType." . $v['type']);
			$i++;
		}
		return $this->export_csv('赎回确认' . date('Y-m-d'), 'withdraw_confirm_excel', $data);
	}

	public function withdrawCode()
	{
		$data = I('post.');
		$sql = "update t_ransom set manner=1,code='$data[code]',batchno='$data[batchno]',type=11 where id='$data[id]'";
		$model = new model();
		$res = $model->execute($sql);
		if ($res) {
			exit('同步成功！');
			// echo json_encode(array('status' => $res, 'msg' => '同步成功！'));
		} else {
			exit('同步失败！');
			// exit(json_encode(array('status' => $res, 'msg' => '同步失败！')));
		}
	}

	public function withdraw_bfj()
	{
		$data = I('post.');
		if ($data['batchno'] == '') {
			$batchno = 'fzgsh_bfj_' . date('Ymd') . '_' . session('username');
		} else {
			$batchno = $data['batchno'] . '_' . session('username');
		}

		$this->writeLogs("WdailyReport", date("Y-m-d H:i:s") . "-记录起始--房掌柜个人赎回结转备付金\n\tID：" . serialize($data['id']) . ' 批次号：' . serialize($batchno) . "\n\t");

		$where = ['id' => $data['id']];
		$list = M('t_ransom')->alias('r')
			->join('left join customs c on r.customid=c.customid')
			->field('r.*,c.namechinese')
			->where($where)->find();
		$this->writeLogs("WdailyReport", "查询的数据：\n\t" . serialize($list) . "\n\t");

		if ($list == false) {
			exit(json_encode(['code' => '0', 'msg' => '订单查询失败']));
		}
		if ($list['type'] != '10') {
			exit(json_encode(['code' => '0', 'msg' => '订单已经同步']));
		}

		$data = [
			[
				'user_id' => $list['customid'],
				'name' => $list['namechinese'],
				'phone' => $this->aes_decrypt($list['phone'], md5('bvhke$#67jsd！43_)')),
				'bank_no' => $this->aes_decrypt($list['bankno'], md5('bvhke$#67jsd！43_)')),
				'bank_name' => $list['bankname'],
				'amount' => (float)$list['amount'],
				'source' => '1',
				'type' => '3',
				'shop_id' => '',
				'order_no' => $list['num'],
				'batch_no' => $batchno,
			]
		];
		$md = 'JYO2O01' . $data[0]['user_id'] . $data[0]['source'] . $data[0]['type'] . $data[0]['amount'] . $data[0]['shop_id'] . $data[0]['order_no'] . $data[0]['batch_no'];
		$data[0]['key'] = md5($md);
		// user_id . source . type . amount . shop_id . order_no . batch_no)|

		$this->writeLogs("WdailyReport", "请求的数据：\n\t" . json_encode($data) . "\n\t");
		// $rs = $this->https_request('http://10.1.1.33:8080/index.php/index/index/carried', json_encode($data));//正式
		$rs = $this->https_request('http://106.3.45.147:9010/index.php/index/index/carried', json_encode($data));//测试 预生产
		$this->writeLogs("WdailyReport", "返回的信息：" . serialize($rs) . "\n\t");
		$rs = json_decode($rs, true);

		$dingding = ['title' => '至尊卡会员招募个人赎回结转备付金', 'data' => $rs, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
		$dingding = ['title' => '至尊卡会员招募个人赎回结转备付金', 'data' => $rs, 'type' => '1'];//测试群聊
		$this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网

		if ($rs['status'] == 1) {
			$this->writeLogs("WdailyReport", "返回的信息：" . serialize($rs) . "\n\t");
			$save_data = ['type' => '结转备付金', 'batchno' => $batchno];
			$list = M('t_ransom')->where($where)->save($save_data);
			$this->writeLogs("WdailyReport", "修改结算表的状态：" . serialize($list) . '-修改的数据：-' . serialize($save_data) . "\n\t\n\t");
			if ($list == true) {
				exit(json_encode(['code' => '1', 'msg' => '结转备付金成功']));
			} else {
				exit(json_encode(['code' => '0', 'msg' => '结转备付金失败']));
			}
			exit(json_encode(['code' => '1', 'msg' => $rs['message']]));
		}
		$this->writeLogs("WdailyReport", "返回的信息：" . serialize($rs) . "\n\t\n\t");
		exit(json_encode(['code' => '0', 'msg' => $rs['message']]));
	}

	/**
	 * AES解密
	 *
	 * @param string $data 要解密的数据
	 * @param string $key 解密KEY
	 * @param string $iv 非NULL的初始化向量
	 * @return string
	 */
	function aes_decrypt($data, $key)
	{
		$data = base64_decode($data);
		$ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
		$iv = substr($data, 0, $ivlen);
		$data = substr($data, $ivlen);
		return openssl_decrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	}

	/**
	 * 充值报表
	 */
	public function recharge()
	{
		$startdate = str_replace('-', '', I('get.startdate'));
		$enddate = str_replace('-', '', I('get.enddate'));
		$inner_order = I('get.inner_order');
		$customid = I('get.customid');
		$tradetype = I('get.tradetype');
		$phone = I('get.phone');
		$amount = I('get.amount');
		$panterid = I('get.panterid');
		$ordersn = I('get.ordersn');
		$where = array('zd.tradetype' => array('IN', '51,56,57'), 'za.type' => '05');
		if (!empty($enddate) && !empty($startdate)) {
			$where['zd.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
		}
		if (!empty($inner_order)) {
			$where['zo.inner_order'] = $inner_order;
		}
		if (!empty($ordersn)) {
			$where['zo.order_sn'] = $ordersn;
		}
		if (!empty($customid)) {
			$where['za.customid'] = $customid;
		}
		if (!empty($tradetype)) {
			$where['zd.tradetype'] = $tradetype;
		}
		if (!empty($phone)) {
			$where['za.phone'] = $phone;
		}
		if (!empty($panterid)) {
			$where['zo.panterid'] = $panterid;
		}
		if (!empty($amount)) {
			switch ($amount) {
				case 1:
					$where['zo.amount'] = array('lt', 1000);
					break;
				case 2:
					$where['zo.amount'] = array(array('egt', 1000), array('lt', 3000));
					break;
				case 3:
					$where['zo.amount'] = array(array('egt', 3000), array('elt', 5000));
					break;
				case 4:
					$where['zo.amount'] = array('gt', 5000);
					break;
				default:
					break;
			}
		}
		session('recharge_excel', $where);            //将查询条件保存到session中，导出EXCEL时候取出来使用

		$count = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->count();

		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->limit($p->firstRow . ',' . $p->listRows)
			->field('zd.*,za.customid,zo.inner_order,za.phone,zo.panterid,zo.order_sn as ordersn,zo.combined,zo.amount as zoamount,zo.description')
			->order('detailid desc')
			->select();
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$list[$k]['name'] = $customs['namechinese'];
		}
		$this->assign("list", $list);
		$this->display();
	}

	public function recharge_excel()
	{
		$where = session('recharge_excel');
		$data = [['发卡机构编号', '会员名称', '会员编号', '会员手机号', '充值交易流水号', '充值时间', '充值单编号', '充值前金额', '充值金额', '只可消费', '可赎回', '冻结', '充值后金额', '交易类型', '备注']];

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->field('zd.*,za.customid,zo.inner_order,za.phone,zo.panterid,zo.order_sn as ordersn,zo.combined,zo.amount as zoamount,zo.description')
			->order('detailid desc')
			->select();
		$i = 1;
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$data[$i]['panterid'] = $v['panterid'];
			$data[$i]['name'] = $customs['namechinese'];
			$data[$i]['customid'] = $v['customid'];
			$data[$i]['phone'] = $v['phone'];
			$data[$i]['inner_order'] = $v['inner_order'];
			$data[$i]['datetime'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
			$data[$i]['ordersn'] = $v['ordersn'];
			$data[$i]['before_amount'] = floatval($v['before_amount']);
			$data[$i]['charge_amount'] = floatval($v['charge_amount']);
			$data[$i]['consume_balance'] = floatval($v['consume_balance']);
			$data[$i]['cash_balance'] = floatval($v['cash_balance']);
			$data[$i]['freeze_balance'] = floatval($v['freeze_balance']);
			$data[$i]['balance'] = floatval($v['balance']);
			switch ($v['tradetype']) {
				case '51':
					$data[$i]['tradetype'] = '正常充值';
					break;
				case '56':
					$data[$i]['tradetype'] = '转账充值';
					break;
				case '57':
					$data[$i]['tradetype'] = '退款';
					break;
			}
			$data[$i]['description'] = $v['description'];
			$i++;
		}
		return $this->export_csv('充值报表' . date('Y-m-d'), 'recharge_excel', $data);
	}

	/**
	 * 消费报表
	 */
	public function consume()
	{
		$startdate = str_replace('-', '', I('get.startdate'));
		$enddate = str_replace('-', '', I('get.enddate'));;
		$inner_order = I('get.inner_order');
		$customid = I('get.customid');
		$tradetype = I('get.tradetype');
		$amount = I('get.amount');
		$phone = I('get.phone');
		$panterid = I('get.panterid');
		$zoorder = I('get.zoorder');
		$where = array('zd.tradetype' => array('IN', '50,52,55'), 'za.type' => '05');
		if (!empty($enddate) && !empty($startdate)) {
			$where['zd.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
		}
		if (!empty($inner_order)) {
			$where['zo.inner_order'] = $inner_order;
		}
		if (!empty($customid)) {
			$where['za.customid'] = $customid;
		}
		if (!empty($tradetype)) {
			$where['zd.tradetype'] = $tradetype;
		}
		if (!empty($phone)) {
			$where['za.phone'] = $phone;
		}
		if (!empty($panterid)) {
			$where['zo.panterid'] = $panterid;
		}
		if (!empty($zoorder)) {
			$where['zo.order_sn'] = $zoorder;
		}
		if (!empty($amount)) {
			switch ($amount) {
				case 1:
					$where['zo.amount'] = array('lt', 1000);
					break;
				case 2:
					$where['zo.amount'] = array(array('egt', 1000), array('lt', 3000));
					break;
				case 3:
					$where['zo.amount'] = array(array('egt', 3000), array('elt', 5000));
					break;
				case 4:
					$where['zo.amount'] = array('gt', 5000);
					break;
				default:
					break;
			}
		}
		session('consume_excel', $where);            //将查询条件保存到session中，导出EXCEL时候取出来使用

		$count = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->count();

		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->limit($p->firstRow . ',' . $p->listRows)
			->field('zd.*,za.customid,zo.inner_order,za.phone,zo.panterid,zo.order_sn as zoorder,zo.combined,zo.amount as zoamount,zo.description')
			->order('detailid desc')
			->select();
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese,linktel')->find();
			$list[$k]['name'] = $customs['namechinese'];
			$list[$k]['phone'] = $customs['linktel'];
		}
		$this->assign("list", $list);
		$this->display();
	}

	public function consume_excel()
	{
		$where = session('consume_excel');
		$data = [['发卡机构编号', '会员名称', '会员编号', '会员手机号', '消费交易流水号', '消费时间', '消费单编号', '消费金额', '订单总金额', '只可消费', '可赎回', '消费类型', '组合支付', '备注']];

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->field('zd.*,za.customid,zo.inner_order,za.phone,zo.panterid,zo.order_sn as zoorder,zo.combined,zo.amount as zoamount,zo.description')
			->order('detailid desc')
			->select();
		$i = 1;
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$data[$i]['panterid'] = $v['panterid'];
			$data[$i]['name'] = $customs['namechinese'];
			$data[$i]['customid'] = $v['customid'];
			$data[$i]['phone'] = $v['phone'];
			$data[$i]['order_sn'] = $v['order_sn'];
			$data[$i]['datetime'] = date('Y-m-d H:i:s', strtotime($v['placeddate'] . $v['placedtime']));
			$data[$i]['zoorder'] = $v['zoorder'];
			$data[$i]['charge_amount'] = floatval($v['charge_amount']);
			$data[$i]['zoamount'] = floatval($v['zoamount']);
			$data[$i]['consume_balance'] = floatval($v['consume_balance']);
			$data[$i]['cash_balance'] = floatval($v['cash_balance']);
			switch ($v['tradetype']) {
				case '50':
					$data[$i]['tradetype'] = '正常消费';
					break;
				case '52':
					$data[$i]['tradetype'] = '赎回扣款';
					break;
				case '55':
					$data[$i]['tradetype'] = '转账扣款';
					break;
			}
			switch ($v['combined']) {
				case '0':
					$data[$i]['combined'] = 'NO';
					break;
				case '1':
					$data[$i]['combined'] = 'YES';
					break;
			}
			$data[$i]['description'] = $v['description'];
			$i++;
		}
		return $this->export_csv('消费报表' . date('Y-m-d'), 'consume_excel', $data);
	}

	/**
	 * 结算报表
	 */
	public function settlement()
	{
		// $this->archive();
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$panterid = I('get.panterid');
		$settlebankid = I('get.settlebankid');
		// $where = array('tradetype' => array('IN', '50'));
		$where = array();
		if (!empty($enddate) && !empty($startdate)) {
			$where['statdate'] = array(array('egt', $startdate), array('elt', $enddate));
		}
		if (!empty($panterid)) {
			$where['zs.panterid'] = $panterid;
		}
		if (!empty($settlebankid)) {
			$where['settlebankid'] = $settlebankid;
		}
		session('settlement_excel', $where);            //将查询条件保存到session中，导出EXCEL时候取出来使用

		$count = M('zzk_settlement')->alias('zs')
			->join('PANTERS p on p.panterid=zs.panterid')
			->where($where)->count();
		$p = new \Think\Page($count, 20);
		$page = $p->show();
		$this->assign('page', $page);

		$list = M('zzk_settlement')->alias('zs')
			->join('PANTERS p on p.panterid=zs.panterid')
			->where($where)->limit($p->firstRow . ',' . $p->listRows)
			->field('zs.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate,p.conpermobno')
			->order('statdate desc')
			->select();

		$this->assign("list", $list);
		$this->display();
	}

	public function settlement_excel()
	{
		$where = session('settlement_excel');
		$data = [['商户编码', '结算商户', '结算日期', '交易笔数', '交易金额', '结算比例', '结算手续费', '结算金额', '结算户名', '结算银行', '结算账户', '开户行', '状态']];
		$list = M('zzk_settlement')->alias('zs')
			->join('PANTERS p on p.panterid=zs.panterid')
			->where($where)
			->field('zs.*,p.namechinese pname,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.rakerate rate,p.servicerate,p.conpermobno')
			->order('statdate desc')
			->select();
		$i = 1;
		foreach ($list as $k => $v) {
			$data[$i]['panterid'] = $v['panterid'];
			$data[$i]['pname'] = $v['pname'];
			$data[$i]['statdate'] = $v['statdate'];
			$data[$i]['num'] = $v['num'];
			$data[$i]['charge_amount'] = floatval($v['tradeamount']);
			$data[$i]['rate'] = $v['rate'] . '%';
			$data[$i]['proxyamount'] = floatval($v['proxyamount']);
			$data[$i]['retailamount'] = floatval($v['retailamount']);
			$data[$i]['settleaccountname'] = $v['settleaccountname'];
			$data[$i]['settlebank'] = $v['settlebank'];
			$data[$i]['settlebankid'] = $v['settlebankid'];
			$data[$i]['settlebankname'] = $v['settlebankname'];
			switch ($v['status']) {
				case '0':
					$data[$i]['status'] = '未同步';
					break;
				case '1':
					$data[$i]['status'] = '已同步';
					break;
			}
			$i++;
		}
		return $this->export_csv('结算报表' . date('Y-m-d'), 'settlement_excel', $data);
	}

	public function settlementCode()
	{
		$data = I('post.');
		$sql = "update zzk_settlement set remarks='$data[code]_$data[batchno]',status=1 where settlementid='$data[id]'";
		$model = new model();
		$res = $model->execute($sql);
		if ($res) {
			exit('同步成功！');
			// echo json_encode(array('status' => $res, 'msg' => '同步成功！'));
		} else {
			exit('同步失败！');
			// exit(json_encode(array('status' => $res, 'msg' => '同步失败！')));
		}
	}

	/**
	 * 冻结解冻
	 */
	public function freeze_un()
	{
		$startdate = str_replace('-', '', I('get.startdate'));
		$enddate = str_replace('-', '', I('get.enddate'));;
		$inner_order = I('get.inner_order');
		$customid = I('get.customid');
		$tradetype = I('get.tradetype');
		$phone = I('get.phone');
		$panterid = I('get.panterid');
		$zoorder = I('get.zoorder');
		$where = array('zd.tradetype' => array('IN', '53,54'), 'za.type' => '05');
		if (!empty($enddate) && !empty($startdate)) {
			$where['zd.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
		}
		if (!empty($inner_order)) {
			$where['zo.inner_order'] = $inner_order;
		}
		if (!empty($customid)) {
			$where['za.customid'] = $customid;
		}
		if (!empty($tradetype)) {
			$where['zd.tradetype'] = $tradetype;
		}
		if (!empty($phone)) {
			$where['za.phone'] = $phone;
		}
		if (!empty($panterid)) {
			$where['zo.panterid'] = $panterid;
		}
		if (!empty($zoorder)) {
			$where['zo.order_sn'] = $zoorder;
		}

		// session('consume_excel', $where);			//将查询条件保存到session中，导出EXCEL时候取出来使用

		$count = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->count();

		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_account za on zd.accountid=za.accountid')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->where($where)
			->limit($p->firstRow . ',' . $p->listRows)
			->field('zd.*,za.customid,zo.inner_order,za.phone,zo.panterid,zo.order_sn as zoorder,zo.combined,zo.amount as zoamount,zo.description')
			->order('detailid desc')
			->select();
		foreach ($list as $k => $v) {
			$customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese,linktel')->find();
			$list[$k]['name'] = $customs['namechinese'];
			$list[$k]['phone'] = $customs['linktel'];
		}
		$this->assign("list", $list);
		$this->display();
	}


	/**
	 * 消费结算归档结算
	 */
	public function archive1()
	{
		exit('禁止访问');
		// echo date('Ymd', time() - 86400);
		// exit; 'zpd.placeddate' => date('Ymd', time()), 
		// 'zpd.placeddate' => date('Ymd', time() - 86400), 
		$ymd = date('Ymd', time() - 86400);
		$data = M('zzk_settlement')->where(array('statdate' => $ymd))->field('statdate')->find();
		if ($data) {
			return false;
		}

		$where = array('zpd.placeddate' => $ymd, 'zpd.paytype' => '05', 'zo.tradetype' => '50');
		$list = M('zzk_pay_detail')->alias('zpd')
			->join('zzk_order zo on zpd.order_sn=zo.inner_order')
			->join('zzk_account za on zo.accountid=za.accountid')
			->join('panters p on zpd.panterid=p.panterid')
			->where($where)
			->field('zpd.panterid,sum(zpd.amount) as money,p.rakerate,zpd.placeddate,count(*) as num')
			->group('zpd.panterid,p.rakerate,zpd.placeddate')->select();

		// print_r($list);exit;
		$model = new model();
		foreach ($list as $key => $val) {
			$money = (float)$val['money'];
			$jsamount = $money * ($val['rakerate'] / 100);
			$sxamount = $money - $jsamount;
			$settlementid = $this->zzkgetnumstr('ZZK_SETTLEMENTID', 8);
			//商户id 开始日期 终端号 实际交易金额 交易积分 交易卷数量 折扣率 与商户结算金额 原金额 
			$sql = 'insert into ZZK_SETTLEMENT(settlementid,panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount,num)';
			$sql .= " values('{$settlementid}','{$val['panterid']}','{$val['placeddate']}','00000000',{$money},";
			$sql .= "1,{$val['rakerate']},'{$jsamount}','{$sxamount}','{$val['num']}')";
			$model->execute($sql);
		}
	}

	/**
	 * 消费结算归档结算
	 */
	public function archive()
	{
		exit('禁止访问');
		// echo date('Ymd', time() - 86400);
		// exit; 'zpd.placeddate' => date('Ymd', time()), 
		// 'zpd.placeddate' => date('Ymd', time() - 86400), 
		$ymd = date('Ymd', time() - 86400); //凌晨2点执行
		$ymd = I('get.statdate');
		if (empty($ymd)) {
			$ymd = date('Ymd', time() - 86400); //凌晨二点执行！！！
		}
		$data = M('zzk_settlement')->where(array('statdate' => $ymd))->field('statdate')->find();
		if ($data) {
			return false;
		}

		$where = array('zd.placeddate' => $ymd, 'zd.tradetype' => '50');

		$list = M('zzk_account_detail')->alias('zd')
			->join('zzk_order zo on zd.order_sn=zo.inner_order')
			->join('panters p on zo.panterid=p.panterid')
			->where($where)
			->field('zo.panterid,sum(zd.charge_amount) as money,p.rakerate,zd.placeddate,count(*) as num')
			->group('zo.panterid,p.rakerate,zd.placeddate')->select();

		// print_r($list);exit;
		$model = new model();
		foreach ($list as $key => $val) {
			// $refund = M('zzk_account_detail')->alias('zd')
			//     ->join('zzk_order zo on zd.order_sn=zo.inner_order')
			//     ->where(array('zo.panterid' => $val['panterid'], 'zd.placeddate' => $ymd, 'zd.tradetype' => '57'))
			//     ->field('zo.panterid,sum(zd.charge_amount) as money,zd.placeddate,count(*) as num')
			//     ->group('zo.panterid,zd.placeddate')->find();

			$money = (float)$val['money'];
			$jsamount = $money * ($val['rakerate'] / 100);
			$sxamount = $money - $jsamount;
			$settlementid = $this->zzkgetnumstr('ZZK_SETTLEMENTID', 8);
			//商户id 开始日期 终端号 实际交易金额 交易积分 交易卷数量 折扣率 与商户结算金额 原金额 
			$sql = 'insert into ZZK_SETTLEMENT(settlementid,panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount,num)';
			$sql .= " values('{$settlementid}','{$val['panterid']}','{$val['placeddate']}','00000000',{$money},";
			$sql .= "1,{$val['rakerate']},'{$jsamount}','{$sxamount}','{$val['num']}')";
			$model->execute($sql);
		}
	}

	/**
	 * 用户账户金额
	 */
	public function balance()
	{
		$linktel = I('get.linktel');
		$customid = I('get.customid');
		$where = array('a.type' => '00'); //, 'a.amount' => array('gt', 0) , 'ca.cardkind' => ['in', '2336,6888']

		if (!empty($linktel)) {
			$where['c.linktel'] = $linktel;
		}
		if (!empty($customid)) {
			$where['c.customid'] = $customid;
		}

		$count = M('customs')
			->alias('c')
			->join('customs_c cc on c.customid=cc.customid')
			->join('account a on cc.cid=a.customid')
			->join('cards ca on cc.cid=ca.customid')
			->where($where)
			->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);

		$list = M('customs')
			->alias('c')
			->join('customs_c cc on c.customid=cc.customid')
			->join('account a on cc.cid=a.customid')
			->join('cards ca on cc.cid=ca.customid')
			->where($where)
			->limit($p->firstRow . ',' . $p->listRows)
			->field('c.customid,c.linktel,c.namechinese,sum(a.amount) money')
			->group('c.customid,c.linktel,c.namechinese')
			->select();
		// print_r($list);
		foreach ($list as $k => $v) {
			$zzk_account = M('zzk_account')->where(array('customid' => $v['customid'], 'type' => '05'))->find();
			$list[$k]['cash_balance'] = (float)$zzk_account['cash_balance'];
			$list[$k]['balance'] = (float)$zzk_account['balance'];
			$list[$k]['freeze_balance'] = (float)$zzk_account['freeze_balance'];
			// $list[$k]['money'] = (float)$v['money'];
			$list[$k]['summoney'] = (float)$v['money'] + (float)$zzk_account['cash_balance'] + (float)$zzk_account['balance'] + (float)$zzk_account['freeze_balance'];
		}

		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * @desc   CSV格式导出数据
	 * @param  $title string 工作簿名称
	 * @param  $tableName string 表名
	 * @param  $list array 导出数据
	 *
	 * @return 将字符串输出，即输出字节流，下载CSV文件
	 */
	public function export_csv($title, $tableName, $list = 0)
	{
		$string = '';
		foreach ($list as $key => $value) {
			$string .= "'";
			$string .= implode(",'", $value) . "\n"; //用英文逗号分开 
		}
		// $string = iconv('utf-8', 'gb2312', strval(' ' . $string));
		$string = iconv('utf-8', 'gbk', $string);
		$filename = $title . '.csv'; //设置文件名
		header("Content-type:text/csv");
		header("Content-Disposition:attachment;filename=" . $filename);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');
		echo $string;
	}


	/**
	 * 结转备付金数据聚合
	 */
	public function mashup()
	{
		$source_str = ['1'=>'房掌柜个人赎回','2'=>'房掌柜商家结算','3'=>'通宝+个人赎回','4'=>'通宝+商家结算','5'=>'通宝商家结算'];
		$type_str  = ['1'=>'消费','2'=>'退款','3'=>'赎回'];
		$status_str  = ['1'=>'未处理','2'=>'已处理','3'=>'已核销','4'=>'审核通过','5'=>'审核拒绝'];
		
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$user_id = I('get.user_id');
		$shop_id = I('get.shop_id');
		$order_no = I('get.order_no');
		$batch_no = I('get.batch_no');
		$source = I('get.source');
		$type = I('get.type');
		$status = I('get.status');

		$where = [];
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($user_id)) {
			$where['user_id'] = $user_id;
		}
		if (!empty($shop_id)) {
			$where['shop_id'] = $shop_id;
		}
		if (!empty($order_no)) {
			$where['order_no'] = $order_no;
		}
		if (!empty($batch_no)) {
			$where['batch_no'] = $batch_no;
		}
		if (!empty($source)) {
			$where['source'] = $source;
		}
		if (!empty($type)) {
			$where['type'] = $type;
		}
		if (!empty($status)) {
			$where['status'] = $status;
		}
		$count = M('mashup')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('mashup')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('to_number(ID) desc')->select();
		
		foreach ($list as $k => $v) {
			// $customs = M('customs')->where(array('customid' => $v['customid']))->field('namechinese')->find();
			$panters = M('panters')->where(array('panterid' => $v['shop_id']))->field('namechinese')->find();
			
			// $list[$k]['name'] = $customs['namechinese'];
			$list[$k]['shop_name'] = $panters['namechinese'];
			$list[$k]['source'] = $source_str[$v['source']];
			$list[$k]['type'] = $type_str[$v['type']];
			$list[$k]['status'] = $status_str[$v['status']];
			$list[$k]['datetime'] = date('Y-m-d H:i:s',$v['datetime']);
			$list[$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
			$list[$k]['amount'] = bcdiv($v['amount'] , 100,2);
			$list[$k]['pay_amount'] = bcdiv($v['pay_amount'] , 100,2);
		}
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 结转备付金充值
	 */
	public function mashup_recharge()
	{
		$model=new Model();
        $model->startTrans();
		if (IS_POST) {
			$list = I('post.');

			if(empty($list['type'])){
				if(empty($list['shop_id'])){
					exit(json_encode(['code' => '0', 'msg' => '请筛选店铺ID！']));
				}
				if(!in_array($list['source'],['2','4','5'])){
					exit(json_encode(['code' => '0', 'msg' => '来源请选择商家结算！']));
				}
			}else if($list['type'] != 3){
				exit(json_encode(['code' => '0', 'msg' => '类型请选择赎回！']));
			}

            $this->writeLogs("jiezhuanbeifujin", date("Y-m-d H:i:s") . "-记录起始--结转备付金充值\n\t" . serialize($list) . "\n\t");

			$id = rtrim($list['id'],',');
			$data = M('mashup')->where(['id'=>['in',"$id"],'status'=>1])->order('to_number(type) desc')->select();
		   
			$this->writeLogs("jiezhuanbeifujin", "select mashup：\n\t" . serialize($data).'_'.  M('mashup')->getLastSql() . "\n\t");

			$arr = [];
			//商家结算金额核算
			if(in_array($list['source'],['2','4','5'])){
				$refund_amount = 0;
				$i = 0;
				$amount = 0;
				foreach($data as $k=>$v){
					if(!in_array($v['source'],['2','4','5'])){
						exit(json_encode(['code' => '0', 'msg' => '来源错误，请联系开发者']));
					}
					if($data[0]['shop_id'] != $v['shop_id']){
						exit(json_encode(['code' => '0', 'msg' => '商铺ID错误，请联系开发者']));
					}

					$v['amount'] = bcdiv($v['amount'] , 100,2);

					if($v['type'] == 2){ //2 退款
						// $refund_amount += $v['amount'];
						$refund_amount = bcadd($refund_amount,$v['amount'],2);

                        $save_data = ['status' => '2','last_time'=>time()];
						$res = M('mashup')->where(['id' => $v['id']])->save($save_data);

					} else if($v['type'] == 1){//消费
						
						if($refund_amount > $v['amount']) {
							$refund_amount = bcsub($refund_amount,$v['amount'],2);

                            $save_data = ['status' => '2','last_time'=>time()] ; 
                            $res = M('mashup')->where(['id' => $v['id']])->save($save_data);

						} else if($v['amount'] > $refund_amount) {

							$v['amount'] = bcsub($v['amount'],$refund_amount,2);
							$refund_amount = 0;

							// $amount += $v['amount'];
							$amount = bcadd($amount,$v['amount'],2);
							$arr[$i]['customid'] = $v['user_id'];
							$arr[$i]['amount'] = $v['amount'];
							$arr[$i]['panterid'] = '00000936';
							$arr[$i]['order_sn'] = $v['order_no'];
							$arr[$i]['description'] = '结转备付金商家结算';
							$arr[$i]['key'] = md5('JYO2O01' . $arr[$i]['customid'] . $arr[$i]['amount'] . $arr[$i]['panterid'] . $arr[$i]['order_sn']);
							$i++;

                            $save_data = ['status' => '2','last_time' =>time(),'pay_amount'=>($v['amount']*100)]; 
							$res = M('mashup')->where(['id' => $v['id']])->save($save_data);
                        }
					}
					$this->writeLogs("jiezhuanbeifujin", "save mashup：\n\t" . serialize($res).'_'.  M('mashup')->getLastSql() . "\n\t");
				}

				if($refund_amount != 0){
					exit(json_encode(['code' => '0', 'msg' => '退款金额不足以抵扣！']));
				}
																								  //费率    服务费  
				$panters = M('panters')->where(array('panterid' => $data[0]['shop_id']))->field('RAKERATE,SERVICERATE')->find();
				$this->writeLogs("jiezhuanbeifujin", "panters：\n\t" . serialize($panters) . "\n\t");
			   
				$jsamount = bcsub(($amount * ($panters['rakerate'] / 100)) , $panters['servicerate'],2); //结算金额
				$sxamount = bcsub($amount,$jsamount,2); //手续费 

				$verification_id = $this->zzkgetnumstr('seq_verification_id', 0);
				$order_no = 'jzsj_'.$list['source'].'_'.date("Ymd").'_'.$verification_id;
				$time = time();
				// $model = new model();

				$amount = bcmul($amount,100);
				$jsamount = bcmul($jsamount,100);
				$sxamount = bcmul($sxamount,100);
				$panters['servicerate'] = bcmul($panters['servicerate'],100);

				$sql = 'insert into VERIFICATION(ID,MASHUP_ID,TYPE_ID,AMOUNT,RAKERATE,SERVICERATE,CLOSEMYNEY,SERVICEMONEY,SOURCE,ORDER_NO,DATETIME,LAST_TIME,STATUS)';
				$sql .= " values('{$verification_id}','{$id}','{$data[0]['shop_id']}','{$amount}',{$panters['rakerate']},{$panters['servicerate']},";
				$sql .= "'{$jsamount}','{$sxamount}','{$list['source']}','{$order_no}','{$time}','{$time}','2')";
				$model->execute($sql);

				$this->writeLogs("jiezhuanbeifujin", "insert into VERIFICATION：\n\t" .  $sql . "\n\t");
				
			}
			//个人赎回
			if($list['type'] == 3){
				$i = 0;
				foreach($data as $k=>$v){
					if(!in_array($v['source'],['1','3'])){
						exit(json_encode(['code' => '0', 'msg' => '来源错误，请联系开发者']));
					}
					if($v['type'] != 3){
						exit(json_encode(['code' => '0', 'msg' => '类型错误，请联系开发者']));
					}

					$v['amount'] = bcdiv($v['amount'] , 100,2);

					$arr[$i]['customid'] = $v['user_id'];
					$arr[$i]['amount'] = $v['amount'];
					$arr[$i]['panterid'] = '00000936';
					$arr[$i]['order_sn'] = $v['order_no'];
					$arr[$i]['description'] = '结转备付金个人赎回';
					$arr[$i]['key'] = md5('JYO2O01' . $arr[$i]['customid'] . $arr[$i]['amount'] . $arr[$i]['panterid'] . $arr[$i]['order_sn']);
					$i++;

					$verification_id = $this->zzkgetnumstr('seq_verification_id', 0);
					$order_no = 'jzgr_'.$v['source'].'_'.date("Ymd").'_'.$verification_id;
					$time = time();
					$model = new model();
					$amount = bcmul($v['amount'],100);
					$sql = 'insert into VERIFICATION(ID,MASHUP_ID,TYPE_ID,AMOUNT,CLOSEMYNEY,SOURCE,ORDER_NO,DATETIME,LAST_TIME,STATUS)';
					$sql .= " values('{$verification_id}','{$v['id']}','{$v['user_id']}','{$amount}',{$amount},{$v['source']},";
					$sql .= "'{$order_no}','{$time}','{$time}','2')";
					$model->execute($sql);
                    $this->writeLogs("jiezhuanbeifujin", "insert into VERIFICATION：\n\t" .  $sql . "\n\t");

                    $save_data = ['status' => '2','last_time'=>time(), 'pay_amount'=>$amount];
					$res = M('mashup')->where(['id' => $v['id']])->save($save_data);
					$this->writeLogs("jiezhuanbeifujin", "save mashup：\n\t" . serialize($res).'_'.  M('mashup')->getLastSql() . "\n\t");
				}
                
			}

            $this->writeLogs("jiezhuanbeifujin", "https_request excess_recharge：\n\t" .  serialize($arr) . "\n\t");
            // $rs = $this->https_request('http://10.1.1.33:8080/index.php/index/index/excess_recharge', json_encode($arr));//正式
            $rs = $this->https_request('http://106.3.45.147:9010/index.php/index/index/excess_recharge', json_encode($arr));//测试 预生产
            $this->writeLogs("jiezhuanbeifujin", "https_request excess_recharge：\n\t" .  serialize($rs) . "\n\t");

			$model->commit();

			$dingding = ['title' => '结转备付金充值', 'data' => $rs, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
			$dingding = ['title' => '结转备付金充值', 'data' => $rs, 'type' => '1'];//测试群聊
			$this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
			
			exit($rs);
		}
	}

	/**
	 * 结转备付金核销
	 */
	public function cancel()
	{
		$source_str = ['1'=>'房掌柜个人赎回','2'=>'房掌柜商家结算','3'=>'通宝+个人赎回','4'=>'通宝+商家结算','5'=>'通宝商家结算'];
		$type_str  = ['1'=>'消费','2'=>'退款','3'=>'赎回'];
		$status_str  = ['1'=>'未处理','2'=>'已处理','3'=>'已核销','4'=>'审核通过','5'=>'审核拒绝','6'=>'同步网联','7'=>'同步银联'];
		
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$type_id = I('get.type_id');
		$order_no = I('get.order_no');
		$source = I('get.source');
		$status = I('get.status');

		$where = [];
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($type_id)) {
			$where['type_id'] = $type_id;
		}
		if (!empty($order_no)) {
			$where['order_no'] = $order_no;
		}
		if (!empty($source)) {
			$where['source'] = $source;
		}
		if (!empty($status)) {
			$where['status'] = $status;
		}
		$count = M('verification')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('verification')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('to_number(ID) desc')->select();
		
		foreach ($list as $k => $v) {
			if(in_array($v['source'],['2','4','5'])){
				$type = M('panters')->where(array('panterid' => $v['type_id']))->field('namechinese')->find();
			} else if(in_array($v['source'],['1','3'])){
				// $type = M('customs')->where(array('customid' => $v['type_id']))->field('namechinese')->find();
                $type = M('mashup')->where(array('id' => $v['mashup_id']))->field('name')->find();
                $type['namechinese'] = $type['name'];
			}
			$list[$k]['name'] = $type['namechinese'];
			$list[$k]['rakerate'] = $v['rakerate'] . '%';
			$list[$k]['source'] = $source_str[$v['source']];
			$list[$k]['status'] = $status_str[$v['status']];
			$list[$k]['datetime'] = date('Y-m-d H:i:s',$v['datetime']);
			$list[$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
			$list[$k]['amount'] = bcdiv($v['amount'] , 100,2);
			$list[$k]['servicerate'] = bcdiv($v['servicerate'] , 100,2);
			$list[$k]['closemyney'] = bcdiv($v['closemyney'] , 100,2);
			$list[$k]['servicemoney'] = bcdiv($v['servicemoney'] , 100,2);
		}
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 结转备付金核销扣款
	 */
	public function charge()
	{
		if (IS_POST) {
			$list = I('post.');
            $this->writeLogs("jiezhuanbeifujin", date("Y-m-d H:i:s") . "-记录起始--结转备付金核销扣款\n\t" . serialize($list) . "\n\t");
			$id = rtrim($list['id'],',');
			$data = M('verification')->field('mashup_id,id,type_id')->where(['id'=>['in',"$id"]])->select();
            $this->writeLogs("jiezhuanbeifujin", "verification查询的数据：\n\t" . serialize($data) . "\n\t");
			
			$res = '';
			$sum_amount = 0;
			$sum_num = 0;
			foreach($data as $k=>$v){
				$rs = M('mashup')->where(['id'=>['in',"$v[mashup_id]"], 'status' => '2'])->select();
                $this->writeLogs("jiezhuanbeifujin", "mashup查询的数据：\n\t" . serialize($rs) . "\n\t");
				if(empty($rs)){
                    break; //终止循环
				}
				$arr = [];
				$i = 0;
				foreach($rs as $val){
                    if ($val['pay_amount'] == 0) {
                        break; //终止循环
					}
					$panterid = empty($val['shop_id']) ? '00000936' : $val['shop_id'];
					$arr[$i]['customid'] = $val['user_id'];
					$arr[$i]['amount'] = bcdiv($val['pay_amount'] , 100,2);
					$arr[$i]['panterid'] = $panterid;
					$arr[$i]['order_sn'] = $val['order_no'];
					$arr[$i]['key'] = md5('JYO2O01' . $arr[$i]['customid'] . $arr[$i]['amount'] . $arr[$i]['panterid'] . $arr[$i]['order_sn']);

					$sum_amount += $arr[$i]['amount'];

					$i++;
                    $sum_num ++;

                    $save_data = ['status' => '3','last_time'=>time()]; 
					$mashup  =  M('mashup')->where(['id' => $val['id']])->save($save_data);
                    $this->writeLogs("jiezhuanbeifujin", "save mashup：\n\t" . serialize($mashup).'_'. M('mashup')->getLastSql() . "\n\t");
				}
				$this->writeLogs("jiezhuanbeifujin", "excess_charge：\n\t" . serialize($arr) . "\n\t");
				
				// $charge = $this->https_request('http://10.1.1.33:8080/index.php/index/index/excess_charge', json_encode($arr));//正式
				$charge = $this->https_request('http://106.3.45.147:9010/index.php/index/index/excess_charge', json_encode($arr));//测试 预生产
				
                $this->writeLogs("jiezhuanbeifujin", "excess_charge：\n\t" . serialize($charge) . "\n\t");
				$charge = json_decode($charge,true);
				$res .= '起始：'.$v['id'].'__'.$v['type_id'].'：'.$charge['message'];
			
				$save_data = ['status' => '3','last_time'=>time()];
				$verification = M('verification')->where(['id' => $v['id'], 'status' => '2'])->save($save_data);
                $this->writeLogs("jiezhuanbeifujin", "save verification：\n\t" . serialize($verification). '_'.  M('verification')->getLastSql() . "\n\t");
				
			}

			$res .= '总计：'.$sum_num.'条，金额'.$sum_amount.'元';
			$str = json_encode(['code'=>1,'msg'=>$res]);
			$this->writeLogs("jiezhuanbeifujin", "返回的信息：\n\t" . serialize(json_decode($str,true)) . "\n\t");

			$dingding = ['title' => '结转备付金核销扣款', 'data' => $res, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
			$dingding = ['title' => '结转备付金核销扣款', 'data' => $res, 'type' => '1'];//测试群聊
			$this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网

            exit($str);
		}
	}

	/**
	 * 结转备付金审核
	 */
	public function audit()
	{
		$source_str = ['1'=>'房掌柜个人赎回','2'=>'房掌柜商家结算','3'=>'通宝+个人赎回','4'=>'通宝+商家结算','5'=>'通宝商家结算'];
		$type_str  = ['1'=>'消费','2'=>'退款','3'=>'赎回'];
		$status_str  = ['1'=>'未处理','2'=>'已处理','3'=>'已核销','4'=>'审核通过','5'=>'审核拒绝','6'=>'同步网联','7'=>'同步银联'];
		
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$type_id = I('get.type_id');
		$order_no = I('get.order_no');
		$source = I('get.source');
		$status = I('get.status');

		$where = [];
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($type_id)) {
			$where['type_id'] = $type_id;
		}
		if (!empty($order_no)) {
			$where['order_no'] = $order_no;
		}
		if (!empty($source)) {
			$where['source'] = $source;
		}
		if (!empty($status)) {
			$where['status'] = $status;
		}
		$count = M('verification')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('verification')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('to_number(ID) desc')->select();
		
		foreach ($list as $k => $v) {
			if(in_array($v['source'],['2','4','5'])){
				$type = M('panters')->where(array('panterid' => $v['type_id']))->field('namechinese')->find();
			} else if(in_array($v['source'],['1','3'])){
                // $type = M('customs')->where(array('customid' => $v['type_id']))->field('namechinese')->find();
                $type = M('mashup')->where(array('id' => $v['mashup_id']))->field('name')->find();
				$type['namechinese'] = $type['name'];
			}
			$list[$k]['name'] = $type['namechinese'];
			$list[$k]['rakerate'] = $v['rakerate'] . '%';
			$list[$k]['source'] = $source_str[$v['source']];
			$list[$k]['status'] = $status_str[$v['status']];
			$list[$k]['datetime'] = date('Y-m-d H:i:s',$v['datetime']);
			$list[$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
			$list[$k]['amount'] = bcdiv($v['amount'] , 100,2);
			$list[$k]['servicerate'] = bcdiv($v['servicerate'] , 100,2);
			$list[$k]['closemyney'] = bcdiv($v['closemyney'] , 100,2);
			$list[$k]['servicemoney'] = bcdiv($v['servicemoney'] , 100,2);
		}
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 结转备付金审批
	 */
	public function approval()
	{
        if (IS_POST) {
			$list = I('post.');
			$this->writeLogs("jiezhuanbeifujin", date("Y-m-d H:i:s") . session('username')."-记录起始--结转备付金审批\n\t" . serialize($list) . "\n\t");
			$id = rtrim($list['id'],',');
			$data = M('verification')->field('mashup_id,id,type_id')->where(['id'=>['in',"$id"]])->select();
            $this->writeLogs("jiezhuanbeifujin", "verification查询的数据：\n\t" . serialize($data) . "\n\t");
			
			$res = '';
			foreach($data as $k=>$v){
				$save_data = ['status' => $list['status'],'last_time'=>time()]; 
				$mashup  =  M('mashup')->where(['id'=>['in',"$v[mashup_id]"], 'status' => '3'])->save($save_data);
                $this->writeLogs("jiezhuanbeifujin", "save mashup：\n\t" . serialize($mashup).'_'.  M('mashup')->getLastSql() . "\n\t");
			}

			$save_data = ['status' => $list['status'],'last_time'=>time()];
			$verification = M('verification')->where(['id' => ['in', "$id"], 'status' => '3'])->save($save_data);
            $this->writeLogs("jiezhuanbeifujin", "save verification：\n\t" . serialize($verification).'_'.   M('verification')->getLastSql() . "\n\t");
			
			$str = json_encode(['code'=>1,'msg'=>'成功']);
			$this->writeLogs("jiezhuanbeifujin", "返回的信息：\n\t" . serialize(json_decode($str,true)) . "\n\t");

			$dingding = ['title' => '结转备付金审批', 'data' => $str, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
			$dingding = ['title' => '结转备付金审批', 'data' => $str, 'type' => '1'];//测试群聊
			$this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网

            exit($str);
		}
	}

	/**
	 * 结转备付金结算
	 */
	public function close()
	{
		$source_str = ['1'=>'房掌柜个人赎回','2'=>'房掌柜商家结算','3'=>'通宝+个人赎回','4'=>'通宝+商家结算','5'=>'通宝商家结算'];
		$type_str  = ['1'=>'消费','2'=>'退款','3'=>'赎回'];
		$status_str  = ['1'=>'未处理','2'=>'已处理','3'=>'已核销','4'=>'审核通过','5'=>'审核拒绝','6'=>'同步网联','7'=>'同步银联'];
		
		$startdate = I('get.startdate');
		$enddate = I('get.enddate');
		$type_id = I('get.type_id');
		$order_no = I('get.order_no');
		$source = I('get.source');
		$status = I('get.status');
		$batch_no = I('get.batch_no');
		$remit_status = I('get.remit_status');

		$where = [];
		if (!empty($enddate) && !empty($startdate)) {
			$where['datetime'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
		}
		if (!empty($type_id)) {
			$where['type_id'] = $type_id;
		}
		if (!empty($order_no)) {
			$where['order_no'] = array('like','%'.$order_no.'%');
		}
		if (!empty($source)) {
			$where['source'] = $source;
		}
		if (!empty($status)) {
			$where['status'] = $status;
		}
		if (!empty($batch_no)) {
			$where['batch_no'] = array('like','%'.$batch_no.'%');
		}
		if (!empty($remit_status)) {
			$where['remit_status'] = $remit_status;
		}
		$count = M('verification')->where($where)->count();
		$p = new \Think\Page($count, 10);
		$page = $p->show();
		$this->assign('page', $page);
		$list = M('verification')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('to_number(ID) desc')->select();
		
		foreach ($list as $k => $v) {
			if(in_array($v['source'],['2','4','5'])){
				$type = M('panters')->where(array('panterid' => $v['type_id']))->field('namechinese')->find();
			} else if(in_array($v['source'],['1','3'])){
				// $type = M('customs')->where(array('customid' => $v['type_id']))->field('namechinese')->find();
				$type = M('mashup')->where(array('id' => $v['mashup_id']))->field('name')->find();
				$type['namechinese'] = $type['name'];
			}
			$list[$k]['name'] = $type['namechinese'];
			$list[$k]['rakerate'] = $v['rakerate'] . '%';
			$list[$k]['source'] = $source_str[$v['source']];
			$list[$k]['status'] = $status_str[$v['status']];
			$list[$k]['datetime'] = date('Y-m-d H:i:s',$v['datetime']);
			$list[$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
			$list[$k]['amount'] = bcdiv($v['amount'] , 100,2);
			$list[$k]['servicerate'] = bcdiv($v['servicerate'] , 100,2);
			$list[$k]['closemyney'] = bcdiv($v['closemyney'] , 100,2);
			$list[$k]['servicemoney'] = bcdiv($v['servicemoney'] , 100,2);
		}
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 同步到网联 银联
	 */
	public function beifujin()
	{
		$source_str = ['1'=>'房掌柜个人赎回','2'=>'房掌柜商家结算','3'=>'通宝+个人赎回','4'=>'通宝+商家结算','5'=>'通宝商家结算'];

        $id = trim(I('post.id', ''));
        $batchno = trim(I('post.batchno', ''));
		$status = trim(I('post.status', ''));
		
        if ($batchno == '') {
            if ($status == 6) {
                $batchno = 'kxt_wl_'.date('Ymd').'_'.session('username');
            } else if ($status == 7) {
                $batchno = 'kxt_yl_'.date('Ymd').'_'.session('username');
            }
        } else {
            $batchno = $batchno.'_'.session('username');
		}
		
		$list = M('verification')->where(['id'=>$id])->find();

        if ($status == 6) {
            $trxsmmry = $source_str[$list['source']].'_'.'网联';
            $type = 1;
        } else if ($status == 7) {
            $trxsmmry = $source_str[$list['source']].'_'.'银联';
            $type = 2;
        }
        $this->writeLogs("jiezhuanbeifujin",date("Y-m-d H:i:s") . "-记录起始--{$trxsmmry}\n\tID：" . serialize($id).' 同步到哪：'. serialize($type).' 批次号：'. serialize($batchno) . "\n\t");

        $this->writeLogs("jiezhuanbeifujin","查询的数据：\n\t" . serialize($list) . "\n\t");

        if ($list == false) {
            exit(json_encode(['code' => '5', 'msg' => '订单查询失败']));
        }
        if ($list['status'] == '6') {
            exit(json_encode(['code' => '4', 'msg' => '订单已经同步']));
        }
        if ($list['status'] == '7') {
            exit(json_encode(['code' => '4', 'msg' => '订单已经同步']));
        }

        $rsa = md5(rand(1000000, 999999999));//得到aes对称秘钥  可以是随机的
		$aes = new \Home\Controller\AesController($rsa);

		// 1房掌柜个人赎回、2房掌柜商家结算、3通宝+个人赎回、4通宝+商家结算、5通宝商家结算
		$cause = ['1'=>'4','2'=>'5','3'=>'6','4'=>'7','5'=>'2'];

		$list['closemyney'] = 1;

		if(in_array($list['source'],['2','4','5'])){ //商家结算
			$field = 'ConPerTeleNo,GoingTeleNo,settlebankname,conpermobno,settlebank,settleaccountname,settlebankid,namechinese';
			$res = M('panters')->where(['panterid'=>$list['type_id']])->field($field)->find();

			$res['settleaccountname'] = 4324;

			//加密数据
			$data = [
				'batchno' => $batchno,
				'trxsmmry' => $trxsmmry,
				'trxustrd' => $list['order_no'],
				'user_phone' => $res['conpermobno'],
				'bankname' => $res['settlebank'],
				'pyeebknm' => $res['settleaccountname'],
				'pyeebkno' => $res['settlebankid'],
				'biztp' => '140001',
				'trxprps' => '0004',
				'cause' => $cause[$list['source']],
				'posttime' => time(),
				'affiliation' => $res['namechinese'],
				'trxamt' => bcdiv($list['closemyney'] , 100,2), //CLOSEMYNEY
				'type' => $type,
				'settleMethod' => '00'
			];
		} else if( in_array($list['source'], ['1', '3'])){ //个人赎回
			$res = M('mashup')->where(['id'=>$list['mashup_id']])->find();

			$res['name'] = 4324;

			//加密数据
			$data = [
				'batchno' => $batchno,//批次号
				'trxsmmry' => $trxsmmry,//交易摘要
				'trxustrd' => $list['order_no'], //业务订单号
				'user_phone' => $res['phone'], //用户手机号
				'bankname' => $res['bank_name'], //银行名称
				'pyeebknm' => $res['name'], //银行卡持有人名字
				'pyeebkno' => $res['bank_no'], //银行卡号
				'biztp' => '130005',//业务种类
				'trxprps' => '0006',//交易用途
				'cause' => $cause[$list['source']], //业务来源
				'posttime' => time(),
				'affiliation' => $source_str[$list['source']],    //项目来源
				'trxamt' => bcdiv($list['closemyney'] , 100,2), //金额
				'type' => $type, //处理类型
				'settleMethod' => '03' //结算方式
			];
		}
        $arr = [];
        foreach($data as $k=>$v) {
            $arr[$k] = $aes->encrypt($v);
        }
        $this->writeLogs("jiezhuanbeifujin","请求的数据：\n\t" . serialize($data) . "\n\t");
        $this->writeLogs("jiezhuanbeifujin","请求的数据：\n\t" . serialize($arr) . "\n\t");
        $arr['signature'] = $this->pubyRas('1205472369' . $rsa);//使用银联公钥进行加密
        $arr['rsa'] = $this->pubwRas($rsa);//用网联公钥 对aes对称秘钥进行加密
        // $data = json_encode($arr);
        //使用post 进行数据传输
        //注:接口中新添加两个字段 A:type(1:网联 2:银联)  B:rsa
        $rs = $this->https_request('https://123.161.209.66:8443/admin/api/transfer', $arr);
        // $rs = $this->https_request('https://192.168.2.4/admin/api/transfer', $arr);
        $rs = json_decode($rs, true);
        if($rs['status'] == 2) {
			$save_data = ['status' => $status,'last_time'=>time(),'code'=>$rs['code'], 'batch_no' => $batchno];
			$verification = M('verification')->where(['id' => $list['id']])->save($save_data);
			$this->writeLogs("jiezhuanbeifujin", "save verification：\n\t" . serialize($verification).'_'.   M('verification')->getLastSql() . "\n\t");
           
            $this->writeLogs("jiezhuanbeifujin","返回的信息：" . serialize($rs) . "\n\t");
            if ($list == true) {
                exit(json_encode(['code' => '1', 'msg' => '结算成功']));
            } else {
                exit(json_encode(['code' => '0', 'msg' => '结算失败']));
            }
            exit(json_encode(['code' => '1', 'msg' => $rs['msg']]));
        }
		$this->writeLogs("jiezhuanbeifujin","返回的信息：" . serialize($rs) . "\n\t\n\t");

		$dingding = ['title' => '结转备付金同步到网联', 'data' => $rs, 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
		$dingding = ['title' => '结转备付金同步到网联', 'data' => $rs, 'type' => '1'];//测试群聊
		$this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网

        exit(json_encode(['code' => '0', 'msg' => $rs['msg']]));
	}


}
