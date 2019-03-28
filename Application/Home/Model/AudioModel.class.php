<?php
namespace Home\Model;
use Think\Model;

class AudioModel extends Model
{
	protected $cardlimit = "cardno like '2336%' or cardno like '6886%'";
	protected $type      = ['01'=>'cards','02'=>'panters','03'=>'users'];
	protected $typeRelate = ['01'=>['map'=>'customid','field'=>'cardno'],
	                         '02'=>['map'=>'panterid','field'=>'namechinese'],
		                     '03'=>['map'=>'userid','field'=>'loginname'],
	];
	public function chargeSum($map)
	{
		$map['_string'] = $this->cardlimit;
		return M('card_purchase_logs')->where($map)->field('nvl(sum(amount),0) charge')->find();
	}

	public function consumeSum($map){
		$map['_string'] = $this->cardlimit;
		$map['tradetype'] = ['in',['00','17','21']];
		return M('trade_wastebooks')->where($map)->field('nvl(sum(tradeamount),0) consume')->find();
	}
	public function refundSum($map){
		$map['_string'] = $this->cardlimit;
		$map['tradetype'] = '04';
		return M('trade_wastebooks')->where($map)->field('nvl(sum(tradeamount),0) refund')->find();
	}

    public function securityCount($map){
	    return M('safety_monitoring')->where($map)->count();
    }
	public function security($map,$page){
		return M('safety_monitoring')->where($map)->limit($page->firstRow,$page->listRows)
				                     ->order('placeddate desc,placedtime desc')->select();
	}

	public function safety($type,$uuid){
		if($type=='04'){
			return $uuid;
		}else{
			$map[$this->typeRelate[$type]['map']] = $uuid;
			$field = $this->typeRelate[$type]['field'];
			return M($this->type[$type])->where($map)->field($field)->find()[$field];
		}
	}
}