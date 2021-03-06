<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/7 0007
 * Time: 下午 16:40
 * content : 门店基本机具管理
 */
namespace Home\Controller;

use Think\Model;
use Think\Page;

class MachinesController extends CommonController
{
	protected $types  = ['1'=>'P8000','2'=>'S90'];
	protected $brands = ['01'=>'旺pos','02'=>'智能果'];
	protected $model;
	public function _initialize() {
		parent::_initialize(); // TODO: Change the autogenerated stub
        $this->model = new Model();
	}

	public function index(){
		$this->display();
	}

	public function add(){
		if(IS_POST){

		}else{
			$this->assign('types',$this->types);
			$this->assign('brands',$this->brands);
			$this->display();
		}

	}

    public function store(){
        $map['pname']    = I('get.pname','');
        $map['panterid'] = I('get.panterid','');
        $map['outid']    = I('get.outid','');
        $map['name']     = I('get.name','');

        if($map['pname']!='') $where['p.namechinese'] = ['like','%'.$map['pname'].'%'];
        if($map['panterid']!='') $where['p.panterid'] = $map['panterid'];
        if($map['outid']!='') $where['s.outid'] = $map['outid'];
        if($map['name']!='') $where['s.name'] = ['like','%'.$map['name'].'%'];

        $count = $this->model->table('zzk_store')->alias('s')
                    ->join('panters p on p.panterid=s.panterid')
                    ->where($where)->count();
        $page = new Page('',10, $map);
        $field = "s.*,p.namechinese pname";
        $list = $this->model->table('zzk_store')->alias('s')
                    ->join('panters p on p.panterid=s.panterid')
                    ->limit($page->firstRow,$page->listRows)
                    ->where($where)->field($field)->select();
      //  echo $this->model->getLastSql();exit;
        $this->assign('map',$map);
        $this->assign('list',$list);
        $this->assign('page',$page->show());
        $this->display();
    }

    public function config(){
	    $map['outid'] = I('get.outid','');
	    $map['imei']  = I('get.imei','');
        $map['pname']    = I('get.pname','');
        $map['panterid'] = I('get.panterid','');
        $map['name']     = I('get.name','');

        if($map['pname']!='') $where['p.namechinese'] = ['like','%'.$map['pname'].'%'];
        if($map['panterid']!='') $where['p.panterid'] = $map['panterid'];
        if($map['outid']!='') $where['s.outid'] = $map['outid'];
        if($map['name']!='') $where['s.name'] = ['like','%'.$map['name'].'%'];
        if($map['outid']!='') $where['c.outid'] = $map['outid'];
        if($map['imei']!='')  $where['c.imei']  = $map['imei'];

        $count = $this->model->table('zzk_pos_config')->alias('c')
                        ->join('left join zzk_store s on s.outid=c.outid')
                        ->join('left join panters p on p.panterid=s.panterid')
                        ->where($where)->field('1')->count();
        if($map['outid'] && $count==0){
            $url = U('addConfig').'/outid/'.$map['outid'];
            redirect($url);
        }else{
            $page = new Page($count,'10',$map);
            $field = $field = "c.imei,c.termno,c.placeddate,c.placedtime,c.forbid,s.outid,s.name,p.panterid,p.namechinese pname";
            $list = $this->model->table('zzk_pos_config')->alias('c')
                            ->join('left join zzk_store s on s.outid=c.outid')
                            ->join('left join panters p on p.panterid=s.panterid')
                            ->where($where)
                            ->field($field)
                            ->limit($page->firstRow,$page->listRows)
                            ->select();
          //  dump($list);exit;
            foreach ($list as  $key => $val){
                switch($list[$key]['forbid']){
                    case 0:
                        $list[$key]['forbid'] = '启用';
                        break;
                    case 1:
                        $list[$key]['forbid'] = '禁用';
                        break;
                }
            }

            $this->assign('list',$list);
            $this->assign('map',$map);
            $this->display();
        }
    }

    public function addConfig(){
        if(IS_POST){
          //  dump($_POST);exit;
            $outid = I('post.outid','');
            $imei  = I('post.imei','');
            $acceptance  = I('post.acceptance','');
            if(!$outid || !$imei) $this->error('店铺id或者imei,不能为空');
            if(!$acceptance) $this->error('请选择受理场景');
            $configid = $this->getPrimarykey('zzk_configid','10');
            $termno = 'f'.$this->getPrimarykey('zzk_termno','7');
            $placeddate = date('Ymd');
            $placedtime = date('H:i:s');
            $sql = "insert when(not exists(select 1 from  zzk_pos_config where imei='{$imei}')) then ";
            $sql.=" into zzk_pos_config(configid,outid,imei,termno,placeddate,placedtime,forbid,acceptance) ";
            $sql.=" select '{$configid}' ,'{$outid}','{$imei}','{$termno}','{$placeddate}','{$placedtime}','0','{$acceptance}' from dual";

            try{
                $result = $this->model->execute($sql);
            }catch (\Exception $e){
               $this->error($e->getMessage());

            }

            if($result) $this->success('新增成功',U('Machines/config',array('outid' => $outid)));
            else $this->error('imei必须唯一');
        }else{
            $outid = I('get.outid','');
            if($outid) $this->assign('outid',$outid);
            $this->display();
        }
    }
    protected function getPrimarykey($field, $length){
        $sql   = "select $field.nextval from dual";
        $model = new Model();
        $result= $model->query($sql);
        return str_pad($result[0]['nextval'],$length,'0',STR_PAD_LEFT);
    }

    //终端信息管理
    function terminal(){
        $model=new Model();
        $namechinese=trim(I('get.namechinese'));
        $conpername=trim(I('get.conpername'));
        $panterid = trim(I('get.panterid',''));
        $storeid    = trim(I('get.storeid',''));
        $outid    = trim(I('get.outid',''));
        $name     = trim(I('get.name',''));
        $namechinese = $namechinese=="商户名称"?"":$namechinese;
        $conpername = $conpername=="联系人"?"":$conpername;
        $panterid = $panterid=="商户编号"?"":$panterid;
        $outid = $outid =="外部店铺号"?"":$outid;
        $name = $name =="店铺名"?"":$name;
        $storeid = $storeid =="店铺号"?"":$storeid;
        if(IS_POST){//修改
            $terminal=M('store_terminals');
            $terminal_id=I('post.terminal_id','');
            $outid=I('post.outid','');
            $storeid=I('post.storeid','');
            $description=I('post.description','');
            $ip=I('post.ip','');
            $flag=I('post.flag','');
            $placeddate = date('Ymd');
            $placedtime = date('H:i:s');
            if($storeid==''){
                $this->error('门店编号不能为空!');
            }else{
                if($flag==''){
                    $this->error('终端标识符不能为空!');
                }
                $re=$terminal->execute("update store_terminals set flag='".$flag."',ip='".$ip."',description='".$description."',outid='".$outid."',placeddate ='".$placeddate."',placedtime = '".$placedtime."',storeid = '".$storeid."' where terminal_id='".$terminal_id."' ");
                if($re){
                    $this->success('更新成功',U('Machines/terminal'));
                }else{
                    $this->error('更新失败');
                }
            }
        }else{
            if(!empty($namechinese)){
                $where['p.namechinese']=array('like','%'.$namechinese.'%');
                $this->assign('namechinese',$namechinese);
                $map['namechinese']=$namechinese;
            }
            if(!empty($conpername)){
                $where['p.conpername']=array('like','%'.$conpername.'%');
                $this->assign('conpername',$conpername);
                $map['conpername']=$conpername;
            }
            if($panterid!=''){
                $where['p.panterid']=$panterid;
                $this->assign('panterid',$panterid);
                $map['panterid']=$panterid;
            }
            if($outid!=''){
                $where['s.outid']=$outid;
                $this->assign('outid',$outid);
                $map['outid']=$outid;
            }
            if($storeid!=''){
                $where['s.storeid']=$storeid;
                $this->assign('storeid',$storeid);
                $map['storeid']=$storeid;
            }
            if($name!=''){
                $where['s.name']=$name;
                $this->assign('name',$name);
                $map['name']=$name;
            }
            $where['p.revorkflg']='N';
            $count = $this->model->table('zzk_store')->alias('s')
                ->join('panters p on p.panterid=s.panterid')
                ->where($where)->count();
           // $page = new Page($count,10, $map);
            $p=new \Think\Page($count, 10 );
            $field = "s.*,p.namechinese pname,p.nameenglish,p.conpername,p.conperteleno";
            $list = $this->model->table('zzk_store')->alias('s')
                ->join('panters p on p.panterid=s.panterid')
                ->limit($p->firstRow,$p->listRows)
                ->where($where)->order('storeid desc')->field($field)->select();
            if(!empty($map)){
                foreach($map as $key=>$val) {
                    $p->parameter[$key]= $val;
                }
            }
            $page = $p->show();
            $this->assign('page',$page);
            $this->assign('list',$list);
        }
        $this->display();
    }
    //pos管理
    public function poscontrol()
    {
        $pos = M("zzk_tpos");
        // $positem = M('zz_item');
        $brand = array("01"=>'旺pos',"02"=>'付临门',"03"=>"通联","04"=>"旺net");
        $this->assign("brand",$brand);
        $imei = trim(I('get.imei',''));
        $pos_id =  trim(I('get.pos_id',''));
        $brand_id = trim(I('get.brand_id',''));
        $pos_en = trim(I('get.pos_en',''));
        if($pos_id!=''){
            $where['pos_id'] = ['like','%'.$pos_id.'%'];
            $this->assign('pos_id',$pos_id);
        }
        if($brand_id !=''){
        $where['brand_id'] = $brand_id;
        $map['brand_id']= $brand_id;
        $this->assign('brand_id',$brand_id);
        }
        if($pos_en !=''){
            $where['pos_en'] = $pos_en;
            $map['pos_en']= $pos_en;
            $this->assign('pos_en',$pos_en);
        }
        $status = trim(I('get.status',''));
        if($status!='')
        {
            if($status=='全部'){
                $where['status']=array(in,array('入库','出库','故障','退货'));
            }else  $where['status']=$status;
            $map['status']= $status;
            $this->assign('status',$status);
        }else{
            $status='入库';
            $where['status']=$status;
            $map['status']= $status;
            $this->assign('status',$status);
        }
        if($imei!=''){
            $where['imei']=$imei;
            $map['imei']= $imei;
            $this->assign('imei',$imei);
        }
        session('config_index',$where);
        $field = 'aid,pos_id,type,imei,status,remark,brand_id,pos_en,auth_status';
        $count = $pos->where($where)->count();
        $page = new \Think\Page($count,10);
        $pos_lists = $pos->where($where)->order('aid desc')->field($field)->limit($page->firstRow.','.$page->listRows)->select();
//        echo M('zzk_tpos')->getLastSql();exit;

     //   dump($pos_lists);exit;
        $shows = $page->show();
        $this->assign('page',$shows);
        $this->assign('count',$count);
        $this->assign("list",$pos_lists);
        $this->display();
    }
    //pos管理导出pos配置报表
    public function config_index(){
        $pos = M('zzk_pos_config');
      //  $where = $_SESSION['config_index'];
//        $where['c.aid']=array('neq','');
//        $where['a.status']=array('neq','');
        $where['c.forbid'] = '0'; // 是否开启禁止
        $where['_string'] = 'c.aid is not null and a.status is not null';
        $field='c.aid,a.status,c.termno,c.imei,p.panterid,p.namechinese pname,z.outid,z.name,c.pantername';
        $lists = $pos->alias('c')
            ->join('left join zzk_tpos a on a.imei=c.imei')
            ->join('left join zzk_store z on z.outid=c.outid')
            ->join('left join panters p on p.panterid=z.panterid')
            ->where($where)->field($field)->order('aid desc')
            ->select();
       // echo M('zzk_pos_config')->getLastSql();exit;
        $strlist = '序号,imei号,pos状态,商户号,商户名,店铺号,店铺名称,终端号,所属商户';
      //  $strlist = iconv('utf-8','utf-8',$strlist);
        $strlist.="\n";
        foreach ($lists as $key => $val) {
         //   $val['status'] = iconv('utf-8','gbk',$val['status']);
         //   $val['status'] = iconv('utf-8','gbk',$val['status']);

            $val['pname']=iconv('utf-8','gbk',$val['pname']);
//            $val['namechinese']=iconv('utf-8','gbk',$val['namechinese']);
            $strlist.=$val['aid']."\t,".$val['imei']."\t,".$val['status']."\t,".$val['panterid']."\t,".$val['pname']."\t,".$val['outid']."\t,".$val['name'];
            $strlist.=','.$val['termno']."\t,".$val['pname']."\n";
        }
        $filename ='pos配置报表'.date('YmdHis',time());
      //  $filename=iconv('utf-8','utf-8',$filename);
        $this->load_csv($strlist,$filename);
    }
    function panterTerminal(){
        $storeid=trim($_REQUEST['storeid']);
        $outid=trim($_REQUEST['outid']);
        if(empty($storeid)){
            $this->error('门店编号缺失');
        }
        $where['storeid']=$storeid;
       // $where['outid']=$outid;
        $model=M('store_terminals');
        $count = $model->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $list=$model->where($where)->order('terminal_id desc')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('storeid',$storeid);
        $this->assign('outid',$outid);
        $this->assign('list',$list);
        $this->display();
    }
    //添加终端号
    function addterminal(){
        $model=M('store_terminals');
        if(IS_POST){
           // dump($_POST);exit;
            $storeid=I('post.storeid','');
            $outid = I('post.outid','');
            if($storeid==''){
                $this->error('门店编号不能为空',U('Machines/addterminal'));
            }
            $ip=$_POST['ip'];
            $flag=$_POST['flag'];
            $description=$_POST['description'];
            $terminalid = 'f'.$this->getPrimarykey('zzk_termno','7');
//            $id=$this->getnextcode('terminals',8);
//            $terminalid=substr($outid,5,3).substr($id,3,5);
            $placeddate = date('Ymd');
            $placedtime = date('H:i:s');
            $sql="insert into store_terminals(terminal_id,outid,ip,flag,description,placeddate,placedtime,storeid) values('"
                .$terminalid."','"
                .$outid."','".$ip."',
'".$flag."','".$description."','".$placeddate."','".$placedtime."','".$storeid."')";
            if($model->execute($sql)){
                $model->commit();
//                //--------------添加开始---5-29------------
//                $panters=M('zzk_store');
//                $panterdata=$panters->where(array('outid'=>$outid))->field("nameenglish")->find();
//                $nameenglish=$panterdata['nameenglish'];//商户名称简称
//                $this->outSideMerchant($ip,$terminalid,$panterid,$nameenglish);
//                //--------------添加结束---5-29------------

                $this->success('终端添加成功',U('Machines/panterTerminal', array('storeid'=>$storeid)));
            }else{
                $model->rollback();
                $this->error('终端添加失败');
            }
        }else{
          //  dump($_GET);exit;
            $outid=I('get.outid','');
            $storeid=I('get.storeid','');
            $ip=$_SERVER['REMOTE_ADDR'];
            $this->assign('storeid',$storeid);
            $this->assign('outid',$outid);
            $this->assign('ip',$ip);
            $this->display();
        }

    }
    //编辑
    function editterminal(){
        $model=M('store_terminals');
        $terminalid=I('get.terminalid','');
        if($terminalid!=''){
            $where['a.terminal_id']=$terminalid;
        }else{
            $this->error('终端编号不能为空',U('Machines/terminal'));
        }
//        $list = $this->model->table('zzk_store')->alias('s')
//            ->join('panters p on p.panterid=s.panterid')
//            ->limit($page->firstRow,$page->listRows)
//            ->where($where)->field($field)->select();
        $pantert=$model->alias('a')->join('left join zzk_store z on z.storeid=a.storeid')
            ->join('left join panters p on p.panterid=z.panterid')->where($where)->field('a.*,z.name storename,p.panterid,p.namechinese,p.nameenglish,p.conpername,p.conperteleno')->find();
        $this->assign('pantert',$pantert);
        $this->display();
    }
    //添加pos
    public function addpos()
    {
        $pos = M("zzk_tpos");
        // $type= array('O' =>'传统POS','N'=>'智能POS' );
        $brand = array("01"=>'旺pos',"02"=>'付临门',"03"=>"通联","04"=>"旺net");
        $this->assign("brand",$brand);
        if(IS_POST)
        {
            $type = trim(I('post.type'),'');
            $brand_id = trim(I('post.brand_id',''));
            $imei = trim(I('post.imei',''));
            $status = trim(I('post.status',''));
            $price = trim(I('post.price',''));
            $add_time = trim(I('post.add_time',''));
            $consignee = trim(I('post.consignee',''));
            $pos_id = trim(I('post.pos_id',''));
            $remark = trim(I('post.remark',''));
            $pos_en = trim(I('post.pos_en',''));
            $business =trim(I('post.business',''));
            if($pos_id=='')
            {
                $this->error("设备ID不能为空!");
            }
            if($brand_id=='')
            {
                $this->error("POS品牌不能为空!");
            }
            if($imei=='')
            {
                $this->error('IMEI不能为空!');
            }
            if($pos_en=='')
            {
                $this->error('pos唯一标识不能为空!');
            }
            if($price=='')
            {
                $this->error("POS价格不能为空!");
            }
            if($consignee=='')
            {
                $this->error('收货人不能为空!');
            }
            if($imei!='')
            {
                $whe['imei']=$imei;
                if($pos->where($whe)->find())
                {
                    $this->error("imei必须唯一!");
                }
            }
            if($pos_en!='')
            {
                $whe1['pos_en']=$pos_en;
                if($pos->where($whe1)->find())
                {
                    $this->error("pos唯一标识已经存在!");exit;
                }
            }
            if($status=='故障'||$status=='退货')
            {
                if($remark=='')
                {
                    $this->error('备注必填!');
                    exit;
                }
            }
            if($status=='故障'||$status=='退货')
            {
                $m = M("zz_change");
                $aid = $this->getnextcode("zzk_tpos",11);
                $sql = "INSERT INTO zzk_tpos (aid,type,brand_id,imei,status,pos_id,consignee,price,add_time,remark,business,pos_en) VALUES";
                $sql.="('".$aid."','".$type."','".$brand_id."','".$imei."','".$status."','".$pos_id."','".$consignee."','".$price."','".$add_time."','".$remark."','".$business."','".$pos_en."')";
                if($m->execute($sql))
                {
                    $this->success('设备新增成功',U('Machines/poscontrol'));
                }
                else
                {
                    $this->error('设备新增失败!');
                }
            }
            if($status=='入库')
            {
                $m = M("zz_change");
                $m->startTrans();
                $aid = $this->getnextcode("zzk_tpos",11);
                $arr1=false;$arr2=false;
                $sql = "INSERT INTO zzk_tpos (aid,type,brand_id,imei,status,pos_id,consignee,price,add_time,remark,business,pos_en) VALUES";
                $sql.="('".$aid."','".$type."','".$brand_id."','".$imei."','".$status."','".$pos_id."','".$consignee."','".$price."','".$add_time."','".$remark."','".$business."','".$pos_en."')";
                if($pos->execute($sql))
                {
                    $arr1 = true;
                    $time = time();
                    $sql = "INSERT INTO zz_change (aid,posnum,status,time) VALUES" ;
                    $sql.="('".$aid."','新型','".$status."','".$time."')";
                    $change = M('zz_change');
                    $temp = $change->execute($sql);
                    if($temp)
                    {
                        $arr2 = true;
                    }
                    else
                    {
                        $arr2=false;
                        $m->rollback();
                        $this->error('入库失败');
                    }
                }
                else
                {
                    $arr1 =false;
                    $m->rollback();
                    $this->error('入库失败!');
                    exit;
                }
                if($arr1 && $arr2)
                {
                    $m->commit();
                    $this->success('入库成功!',U('Machines/poscontrol'));
                }
            }
        }
        $this->display();
    }
    public function editpos()
    {
        $brand = array("01"=>'旺pos',"02"=>'付临门',"03"=>"通联","04"=>"旺net");
        $this->assign("brand",$brand);
        $pos = M("zzk_tpos");
        $aid = trim(I('get.aid',''));
        $where['aid']=$aid;
        $posid = $pos->where($where)->find();
        if(IS_POST)
        {
            $type = trim(I('post.type'),'');
            $brand_id = trim(I('post.brand_id',''));
            $imei = trim(I('post.imei',''));
            $status = trim(I('post.status',''));
            $price = trim(I('post.price',''));
            $add_time = trim(I('post.add_time',''));
            $consignee = trim(I('post.consignee',''));
            $pos_id = trim(I('post.pos_id',''));
            $aid = trim(I('post.aid',''));
            $remark = trim(I('post.remark',''));
            $pos_en = trim(I('post.pos_en',''));
            $business =trim(I('post.business',''));
            if($pos_id=='')
            {
                $this->error("设备ID不能为空!");
            }
            if($type=='')
            {
                $this->error("请选择类型!");
            }
            if($brand_id=='')
            {
                $this->error("POS品牌不能为空!");
            }
            if($imei=='')
            {
                $this->error('IMEI不能为空!');
            }
            if($pos_en=='')
            {
                $this->error("pos唯一标识不能为空!");
            }
            if($price=='')
            {
                $this->error("POS价格不能为空!");
            }
            if($consignee=='')
            {
                $this->error('收货人不能为空!');
            }
            if($aid!=''&&$imei!='')
            {
                $data['imei'] = $imei;
                $data['aid'] = array('neq',$aid);
                if($pos->where($data)->find())
                {
                    $this->error('imei重复,请更换!');
                }
            }
            if($aid!=''&&$pos_en!='')
            {
                $data1['pos_en'] = $pos_en;
                $data1['aid'] = array('neq',$aid);
                if($pos->where($data1)->find())
                {
                    $this->error('pos唯一标识en号重复!');
                }
            }
            if($aid!='')
            {
                $where['aid'] =$aid;
            }
            if($status=='故障'||$status=='退货')
            {
                if($remark=='')
                {
                    $this->error('备注必填!');
                    exit;
                }
            }
            if($pos->create())
            {
                if($pos->where($where)->save($_POST))
                {
                    $this->success("修改成功",'poscontrol');
                }
                else
                {
                    $this->error("修改失败");
                }
            }
        }
        $this->assign("posid",$posid);
        $this->display();

    }
    //至尊卡POS 配置页面
    public function cardpos()
    {
        $acceptance = array("00"=>'大食堂充值',"01"=>'大食堂消费',"02"=>'桌台点餐',"03"=>'房掌柜');
        $this->assign("acceptance",$acceptance);
        $config = M('zzk_pos_config');
        //检查设备序号aid
        $aid = trim(I('get.aid',''));
        $where['aid'] = $aid;
        if($res=$config->where($where)->find())
        {
            $this->assign('res',$res);
        }
        $this->assign('aid',$aid);
        $this->display();
    }
    //至尊卡pos配置修改操作
    public function editzzcard1()
    {
        $config = M('zzk_pos_config');
        $pos = M('zzk_tpos');
        if(IS_POST)
        {
            $panterid = trim(I('post.panterid',''));
            $pantername = trim(I('post.pantername',''));
            $storeid = trim(I('post.storeid',''));
            $outid = trim(I('post.outid',''));
            $storename = trim(I('post.storename',''));
            $acceptance = trim(I('post.acceptance_id',''));
            $ip_address = trim(I('post.ip_address',''));
            $num_id = trim(I('post.num_id',''));
            $aid = trim(I('post.aid',''));
            $termno = trim(I('post.termno',''));
            $list = $pos->where("aid=$aid")->find();
            $imei = $list['imei'];
            if(!$imei){
                $this->error('imei不存在或未添加!');
            }

            $forbid = trim(I('post.forbid'));
            $remark = trim(I('post.remark'));
            $placeddate = date('Ymd');
            $placedtime = date('H:i:s');
            $where1['storeid'] = $storeid;
            $termno_id = M('store_terminals')->where($where1)->select();
            $termno_ids = array_column($termno_id,'terminal_id');
          //  dump($termno_ids);exit;
            if(!in_array($termno,$termno_ids)){
                $this->error('终端号不匹配!');
            }
            $where['aid'] = $aid;
            if($config->where($where)->find())
            {
                $data['panterid'] = $panterid;
                $data['pantername'] = $pantername;
                $data['storeid'] = $storeid;
                $data['outid'] = $outid;
                $data['storename'] = $storename;
                $data['acceptance'] = $acceptance;
                $data['remark'] = $remark;
                $data['placeddate'] = $placeddate;
                $data['placedtime'] = $placedtime;
                $data['ip_address'] = $ip_address;
                $data['num_id'] = $num_id;
                $data['termno'] = $termno;
                $data['forbid']=$forbid;
                $data['imei'] = $imei;

//                $d = $config->fetchSql()->where($where)->save($data);
//                var_dump($d);exit;
                if($config->where($where)->save($data))
                {
                    $this->success('至尊卡修改配置成功','poscontrol');
                }
                else
                {
                    $this->error('至尊卡配置修改失败');
                }
            }
            else
            {
                $configid = $this->getPrimarykey('zzk_configid','10');
                $sql = "INSERT INTO zzk_pos_config(configid,aid,termno,panterid,pantername,outid,storename,ip_address,num_id,acceptance,placeddate,
placedtime,imei,
forbid,remark,storeid) 
VALUES";
                $sql.="('".$configid."','".$aid."','".$termno."','".$panterid."','".$pantername."','".$outid."','".$storename."','".$ip_address."','".$num_id."','".$acceptance."','".$placeddate."','".$placedtime."','".$imei."','".$forbid."','".$remark."','" .$storeid."')";
                if($config->execute($sql))
                {
                    $this->success("配置添加成功",'poscontrol');
                }
                else
                {
                    $this->error('配置添加失败!');
                }
            }

        }

    }
    //开启关闭 远程打印
    public function  auth_status_pos(){
        $data = I('post.');
        $pos_en = $data['pos_en'];
        $data_status = M('zzk_tpos')->where(array('pos_en' => $pos_en))->find();
        if ($data_status) {
            if($data_status['auth_status'] == 1){
//                echo '1212';exit;
                $data['auth_status'] = 0;
            }else{
    //            echo '12125';exit;
                $data['auth_status'] = 1;
            }
        } else {
            $this->error('该en号不存在');
        }
        $res = M('zzk_tpos')->where(array('pos_en' => $pos_en))->save($data);
        if ($res) {
//            echo '状态更新成功';exit;
            $this->success('状态更新成功');
        } else {
//            echo '状态更新失败';exit;
            $this->error('状态更新失败');
        }

}
    //pos变更记录配置项目
    public function item1()
    {
        $m = M('zz_change');
        $aid = trim(I('get.aid',''));
        $where['aid'] = $aid;
        $count= $m->where($where)->count();
        $p = new \Think\Page($count,10);
        $page = $p->show();
        $lists = $m->where($where)->order('time desc')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('count',$count);
        $this->assign('page',$page);
        $this->assign('lists',$lists);
        $this->assign('aid',$aid);
        $this->display();
    }
}

