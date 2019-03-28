<?php
namespace Home\Model;
use Think\Model;
class CardActiveLogsModel extends Model {
	protected $tableName = '__CARD_ACTIVE_LOGS__';
	protected $where = array();
	protected $field = '';
	protected $p = null;
	protected $model;
	
	public function __construct($where = array(), $field = '', $page = null){
		$this->where = $where;
		$this->field = $field;
		$this->p = $page;
		$this->model = new Model();
	}
	
	/**
	 * 返回售卡报表的记录数
	 * @return unknown
	 */
	public function getSellCount(){
		$count = $this->model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
			->join('__CARDS__ card on card.cardno=ca.cardno')
			->join('left join __USERS__ u on u.userid=ca.userid')
			->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
			->join('__CUSTOMS__ c on c.customid=f.customid')
			->join('left join __PANTERS__ p on p.panterid=ca.panterid')
			->join('__CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
			->join('__CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field($this->field)->count();		
		return $count;
	}
	
	/**
	 * 返回售卡报表的总额
	 * @return unknown
	 */
	public function getSellAmountSum(){
		$amount_sum = $this->model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
			->join('__CARDS__ card on card.cardno=ca.cardno')
			->join('left join __USERS__ u on u.userid=ca.userid')
			->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
			->join('__CUSTOMS__ c on c.customid=f.customid')
			->join('left join __PANTERS__ p on p.panterid=ca.panterid')
			->join('__CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
			->join('__CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field("sum(b.amount) amount_sum")->find();
			//echo $this->model->getLastSql();
		return $amount_sum;
	}
	
	/**
	 * 返回售卡报表的数据数组
	 * @return unknown
	 */
	public function getSellList(){
		$sell_list = $this->model->table('__CARD_ACTIVE_LOGS__ ')->alias('ca')
			->join('__CARDS__ card on card.cardno=ca.cardno')
			->join('left join __USERS__ u on u.userid=ca.userid')
			->join('__CUSTOMS_C__ f on f.cid=card.customid')
			->join('__CUSTOMS__ c on c.customid=f.customid')
			->join('left join __PANTERS__ p on p.panterid=ca.panterid')
			->join('__CARD_PURCHASE_LOGS__ b on ca.cardno=b.cardno')
			->join('__CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field($this->field)->limit($this->p->firstRow.','.$this->p->listRows)
			->order('b.card_purchaseid desc')->select();
			//echo $this->model->getLastSql();
		return $sell_list;
	}
	
	/**
	 * 返回充值报表的记录数
	 * @return unknown
	 */
	public function getRechargeCount(){
		$count = $this->model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
			->join('left join __CARDS__ card on card.cardno=b.cardno')
			->join('left join __PANTERS__ p on p.panterid=b.panterid')
			->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
			->join(' __CUSTOMS__ c on c.customid=f.customid')
			->join('left join __USERS__ u on u.userid=b.userid')
			->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field($this->field)->count();
		return $count;
	}
	
	/**
	 * 返回充值报表的总额
	 */
	public function getRechargeAmountSum(){
		$amount_sum = $this->model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
			->join('left join __CARDS__ card on card.cardno=b.cardno')
			->join('left join __PANTERS__ p on p.panterid=b.panterid')
			->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
			->join(' __CUSTOMS__ c on c.customid=f.customid')
			->join('left join __USERS__ u on u.userid=b.userid')
			->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field("sum(b.amount) amount_sum")->find();
		return $amount_sum;
	}
	
	/**
	 * 返回充值报表的数据数组
	 */
	public function getRechargeList(){
		$recharge_list = $this->model->table('__CARD_PURCHASE_LOGS__ ')->alias('b')
			->join('left join __CARDS__ card on card.cardno=b.cardno')
			->join('left join __PANTERS__ p on p.panterid=b.panterid')
			->join('left join __CUSTOMS_C__ f on f.cid=card.customid')
			->join(' __CUSTOMS__ c on c.customid=f.customid')
			->join('left join __USERS__ u on u.userid=b.userid')
			->join('left join __CUSTOM_PURCHASE_LOGS__ a on a.purchaseid=b.purchaseid')
			->where($this->where)->field($this->field)->limit($this->p->firstRow.','.$this->p->listRows)
			->order('b.card_purchaseid desc')->select();
        //echo $this->model->getLastSql();
		return $recharge_list;
	}
	
	/**
	 * 返回卡余额报表的记录数
	 */
	public function getBalanceCount(){
		
	}
	
}




















