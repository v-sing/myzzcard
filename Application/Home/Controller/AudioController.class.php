<?php
namespace Home\Controller;
use Home\Model\AudioModel;
use Think\Page;

class AudioController extends CommonController
{
	protected $model;
	public function _initialize() {
		$this->model = new AudioModel();
	}

	//资产监控
	public function assets()
	{
		$startdate= trim(I('get.startdate',''));
		$enddate  = trim(I('get.enddate',''));
		$startdate==true||$startdate=date('Y-m-d');
		$begin    = str_replace('-','',$startdate);
		if($enddate!=''){
			$end  = str_replace('-','',$enddate);
			$map['placeddate'] =[['egt',$begin],['elt',$end]];
			$this->assign('enddate',$enddate);
		}else{
			$map['placeddate'] = ['egt',$begin];
		}
		$consume = $this->model->consumeSum($map);
		$refund  = $this->model->refundSum($map);
		$map['flag'] = '1';
		$charge  = $this->model->chargeSum($map);
		$this->assign('charge',$charge);
		$this->assign('consume',$consume);
		$this->assign('refund',$refund);
		$this->assign('startdate',$startdate);
		$this->display();

	}
	//安全事件
	public function security()
	{
		$startdate= trim(I('get.startdate',''));
		$enddate  = trim(I('get.enddate',''));
		$startdate==true||$startdate=date('Y-m-d');
		$begin    = str_replace('-','',$startdate);
		$url['startdate'] = $startdate;
		if($enddate!=''){
			$end  = str_replace('-','',$enddate);
			$map['placeddate'] =[['egt',$begin],['elt',$end]];
			$url['enddate']    = $enddate;
			$this->assign('enddate',$enddate);
		}else{
			$map['placeddate'] = ['egt',$begin];
		}
		$page  = new Page($this->model->securityCount($map),10, $url);

		$lists = $this->model->security($map,$page);

		foreach ($lists as $key => $val){
			$val['relate'] = $this->model->safety($val['type'],$val['cuid']);
			$lists[$key] = $val;
		}

		$this->assign('startdate',$startdate);
		$this->assign('lists',$lists);
		$this->assign('page',$page->show());
		$this->display();
	}
}