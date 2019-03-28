<?php
namespace Home\Controller;
use Org\Util\Digital;
use Think\Controller;
use Think\Model;
use Think\Page;

class PantersController extends CommonController {
    protected $sendMethod;
	public function _initialize()
	{
        parent::_initialize();
		$this->url = C("fangzgIP")."/posindex.php/Panter/dataAdd";
		$this->sendMethod = array('tb'=>'赠送通宝','point'=>'赠送积分');
		$this->codeUrl ="http://wx.9617777.com/activate.php?"; //生成外拓二维码的地址
		$this->addsideUrl ="http://192.168.10.22:8081/activate.php?"; //生成外拓增加商户的地址
        $this->outsidekey="outSideShop"; //商户外拓的key
    }
	
    /*
     * C('greenParent') 大食堂机构
     * C('sooneEParent') 一家机构
     */
    //客户信息管理
//    function index(){
//        $greenPanter=C('greenParent');
//        $soonE=C('sooneEParent');
//        $model = M('panters');
//        $revork=array('Y'=>'是','N'=>'否');
//        $revork1=array('N'=>'正常商户','Y'=>'禁用商户',$greenPanter=>'大食堂商户',$soonE=>'一家商户','lose'=>'审核未通过');
//        $map['start']       = trim(I('get.startdate',''));
//        $map['end']         = trim(I('get.enddate',''));
//        $map['pname']       = trim(I('get.pname',''));
//        $map['uname']       = trim(I('get.uname',''));
//        $map['khtel']       = trim(I('get.khtel',''));
//        $map['nameenglish'] = trim(I('get.nameenglish',''));
//        $map['accounttype'] = trim(I('get.accounttype',''));
//        $map['revorkflg']   = trim(I('get.revorkflg',''));
//
//        $where = $this->getIndexSearchWhere($map,$greenPanter,$soonE);
//        $where['flag'] = '3';
//        $where['panterid']=array('not in','FFFFFFFF,EEEEEEEE');
//// 		$where['placeddate'] = array('exp','is not null');
//        $count=$model->where($where)->count();
//        $this->assign('count',$count);
//        $p=new \Think\Page($count, 10 );
//        $field='panterid,namechinese,nameenglish,hysx,goingteleno,revorkflg,conpername,conperteleno,conperbpno,address,uppanterid,conpermobno,revorkreason';
//        $panters_list=$model->where($where)->limit($p->firstRow.','.$p->listRows)
//            ->order('panterid desc')->select();
//        $page = $p->show();
//        session("indexcel",$where);
//        if($_SESSION['_ACCESS_LIST']['HOME']['PANTERS']['PANTERCHECK']){
//            $this->assign('pantercheck',1);
//        }
//        foreach($panters_list as $key=>$val){
//            if(!empty($val['conperteleno'])){
//                $panters_list[$key]['conperteleno']=substr($val['conperteleno'],0,3).'****'.substr($val['conperteleno'],-4);
//            }
//            if(!empty($val['conperbpno'])){
//                $panters_list[$key]['conperbpno']=substr($val['conperbpno'],0,6).'********'.substr($val['conperbpno'],-4);
//            }
//            if(!empty($val['conpermobno'])){
//                $panters_list[$key]['conpermobno']=substr($val['conpermobno'],0,3).'****'.substr($val['conperteleno'],-4);
//            }
//        }
//        $this->assign('revork',$revork);
//        $this->assign('revork1',$revork1);
//        $this->assign('list',$panters_list);
//        $this->assign('page',$page);
//        $this->display();
//    }
//    private function getIndexSearchWhere($map,$greenPanter,$soonE){
//        if($map['pname']!='') {
//            $where['namechinese']=array('like','%'.$map['pname'].'%');
//            $this->assign('pname',$map['pname']);
//        }
//        if($map['uname']!=''){
//            $where['conpername']=array('like','%'.$map['uname'].'%');
//            $this->assign('uname',$map['uname']);
//        }
//        if($map['khtel']!=''){
//            $where['conperteleno']=array('like','%'.$map['khtel'].'%');
//            $this->assign('khtel',$map['khtel']);
//        }
//        if($map['start']!=''){
//            $where['placeddate']=array('egt',$map['start']);
//            $this->assign('startdate',$map['start']);
//        }
//        if($map['end']!=''){
//            if(isset($where['placeddate'])){
//                $where['placeddate']= [['egt',$map['start']],['elt',$map['end']]];
//            }else{
//                $where['placeddate'] = array('elt',$map['end']);
//            }
//            $this->assign('enddate',$map['end']);
//        }
//        if($map['nameenglish']!=''){
//            $where['nameenglish']=array('like','%'.$map['nameenglish'].'%');
//            $this->assign('nameenglish',$map['nameenglish']);
//        }
//        if($map['revorkflg']==''||$map['revorkflg']=='N'){
//            $where['revorkflg'] ='N';
//            $where['parent']=['not in',[$greenPanter,$soonE]];
//         }else{
//            switch ($map['revorkflg']){
//                case 'Y':        $where['revorkflg']='Y';break;
//                case 'lose':     $where['status']   = '2';break;
//                case $greenPanter :list($where['revorkflg'],$where['parent']) = ['N',$greenPanter]; break;
//                case $soonE:       list($where['revorkflg'],$where['parent'])= ['N',$soonE];break;
//                default: break;
//            }
//        }
//        if($map['accounttype']!=''){
//            if($map['accounttype']!='all'){
//                $where['accounttype'] = $map['accounttype'];
//            }
//            $this->assign('accounttype',$map['accounttype']);
//        }else{
//            $where['accounttype'] = 'B';
//        }
//        return $where;
//    }
    function index(){
    $greenPanter='00000483';
    $soonE='00000243';
    $model = M('panters');
    $field='panterid,namechinese,nameenglish,hysx,goingteleno,revorkflg,conpername,conperteleno,conperbpno,address,uppanterid,conpermobno,revorkreason';
    $revork=array('Y'=>'是','N'=>'否');
    $revork1=array('N'=>'正常商户','Y'=>'禁用商户',$greenPanter=>'大食堂商户',$soonE=>'一家商户','lose'=>'审核未通过');
    $start = I('get.startdate','');
    $end = I('get.enddate','');
    $pname=trim(I('get.pname',''));
    $uname=trim(I('get.uname',''));
    $khtel= trim(I('get.khtel',''));
    $nameenglish = trim(I('get.nameenglish',''));
    $accounttype = trim(I('get.accounttype',''));
    $revorkflg = trim(I('get.revorkflg',''));
    $pname = $pname=="商户名称"?"":$pname;
    $uname = $uname=="联系人"?"":$uname;
    $khtel = $khtel=="联系电话"?"":$khtel;
    $nameenglish = $nameenglish=="商户简称"?"":$nameenglish;

    if($start!='' && $end==''){
        $startdate = str_replace('-','',$start);
        $where['placeddate']=array('egt',$startdate);
        $this->assign('startdate',$start);
        $map['startdate']=$start;
    }
    if($start=='' && $end!=''){
        $enddate = str_replace('-','',$end);
        $where['placeddate'] = array('elt',$enddate);
        $this->assign('enddate',$end);
        $map['enddate']=$end;
    }
    if($start!='' && $end!=''){
        $startdate = str_replace('-','',$start);
        $enddate = str_replace('-','',$end);
        $where['placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $this->assign('startdate',$start);
        $this->assign('enddate',$end);
        $map['startdate']=$start;
        $map['enddate']=$end;
    }
    if($pname!=''){
        $where['namechinese']=array('like','%'.$pname.'%');
        $this->assign('pname',$pname);
        $map['pname']=$pname;
    }
    if($uname!=''){
        $where['conpername']=array('like','%'.$uname.'%');
        $this->assign('uname',$uname);
        $map['uname']=$uname;
    }
    if($khtel!=''){
        $where['conperteleno']=array('like','%'.$khtel.'%');
        $this->assign('khtel',$khtel);
        $map['khtel']=$khtel;
    }
    if($nameenglish!=''){
        $where['nameenglish']=array('like','%'.$nameenglish.'%');
        $this->assign('nameenglish',$nameenglish);
        $map['nameenglish']=$nameenglish;
    }
    if($revorkflg!=''){
        if($revorkflg=='N'){
            $where['revorkflg']=$revorkflg;
            $where['parent']=['not in',[$greenPanter,$soonE]];
        }elseif($revorkflg==$greenPanter){
            $where['revorkflg']='N';
            $where['parent']=$greenPanter;
        }elseif($revorkflg==$soonE){
            $where['revorkflg']='N';
            $where['parent']=$soonE;
        }elseif($revorkflg=='lose'){
            $where['status']='2';
        }
        else {
            $where['revorkflg']=$revorkflg;
        }
    }else{
        $where['revorkflg'] ='N';
        $where['parent']=['not in',[$greenPanter,$soonE]];
    }
    $where['flag'] = '3';
    $where['panterid']=array('not in','FFFFFFFF,EEEEEEEE');
    if($revorkflg!='lose'){
        $where['status']='1';
    }
    if($accounttype!=''){
        if($accounttype!='all'){
            $where['accounttype'] = $accounttype;
        }
        $this->assign('accounttype',$accounttype);
    }else{
        $where['accounttype'] = 'B';
    }
// 		$where['placeddate'] = array('exp','is not null');
    $count=$model->where($where)->count();
    $this->assign('count',$count);
    $p=new \Think\Page($count, 10 );
    $panters_list=$model->where($where)->limit($p->firstRow.','.$p->listRows)
        ->order('panterid desc')->select();
    if(!empty($map)){
        foreach($map as $key=>$val) {
            $p->parameter[$key]= $val;
        }
    }
    $page = $p->show();
    session("indexcel",$where);
    if($_SESSION['_ACCESS_LIST']['HOME']['PANTERS']['PANTERCHECK']){
        $this->assign('pantercheck',1);
    }
    foreach($panters_list as $key=>$val){
        if(!empty($val['conperteleno'])){
            $panters_list[$key]['conperteleno']=substr($val['conperteleno'],0,3).'****'.substr($val['conperteleno'],-4);
        }
        if(!empty($val['conperbpno'])){
        	$val['conperbpno'] = $this->decodePanterConperbpno($val['conperbpno'],$val['panterid']);
            $panters_list[$key]['conperbpno']=substr($val['conperbpno'],0,6).'********'.substr($val['conperbpno'],-4);
        }
        if(!empty($val['conpermobno'])){
            $panters_list[$key]['conpermobno']=substr($val['conpermobno'],0,3).'****'.substr($val['conperteleno'],-4);
        }
    }
    $this->assign('revork',$revork);
    $this->assign('revork1',$revork1);
    $this->assign('revorkflg',$revorkflg);
    $this->assign('list',$panters_list);
    $this->assign('page',$page);
    $this->display();
}
    //---新商户管理
    function fat__index(){
        if(IS_POST){
            $revork=array('Y'=>'是','N'=>'否');
            $model = M('panters');
            $field = 'panterid,namechiese,nameenglish,hysx,revorkflg,conpername,conperteleno,conperbpno,conpermobno,placeddate';
            $page = $_POST['page'];
            $pageSize = $_POST['rows'];
            $first = $pageSize*($page-1);
            $revorkflg = trim(I('post.revorkflg',''));
            $where['flag']='3';
//             if($revorkflg==''){
//                 $where['revorkflg']='Y';
//             }else{
//                 $where['revorkflg']=$revorkflg;
//             }
            $count = $model->where($where)->count();
            $lists  =$model->where($where)->limit($first,$pageSize)
                     ->order('panterid desc')->select();
            foreach ($lists as $key=>$val){
                $val['revorkflg']=$revork[$val['revorkflg']];
                $rows[]=$val;
            }
        $result=array();
        $result['total']=!empty($count)?$count:0 ;
        $result['rows']=!empty($rows)?array_values($rows):'';
        $this->ajaxReturn($result);
        }
        $this->display();
    }
    function  afte__editpanters(){
        $panterid=trim($_GET['panterid']);
//         $panterid='00000434';
        if(empty($panterid)){
            $this->error('缺失商户id');
        }
        $where['panterid']=$panterid;
        $list=M('panters')->where($where)->find();
        $parents = M('panters')->where(array('flag'=>'1'))->field('panterid,namechinese')->select();
        $pantergroups= M('pantergroup')->order('groupid asc')->select();
        $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
        $revork=array('Y'=>'是','N'=>'否');
        $this->assign('revork',$revork);
        $this->assign('hysxs',$hysxs);
        $this->assign('pantergroups',$pantergroups);
        $this->assign('info',$list);
        $this->assign('parents',$parents);
        $this->display();
    }
    //导出商户excel
    public function index_excel()
    {
      $this->setTimeMemory();
      $panters = M("panters");
      $revork=array('Y'=>'是','N'=>'否');
      $field ="p.panterid,p.namechinese,pg.groupname,p.hysx,p.revorkflg,p.conpername,p.conperbpno,p.conperteleno,p.settleaccountname,p.settlebank,p.settlebankname,p.settlebankid,p.placeddate";
      $where = session("indexcel");
      $strlist = "商户编号,商户名称,所属商圈,行业属性,是否禁用,联系人姓名,联系人证件号,联系人电话,结算银行户名,结算银行,结算银行名称,结算银行账户,添加日期";
      $strlist = iconv("utf-8","gbk",$strlist);
      $strlist.="\n";
      $panters_list = $panters->alias('p')
          ->join('left join __PANTERGROUP__ pg on p.parent = pg.groupid')
          ->where($where)->field($field)->select();
      foreach ($panters_list as $key => $val)
      {
        $val['namechinese'] = iconv("utf-8","gbk",$val['namechinese']);
        $val['groupname'] = iconv("utf-8","gbk",$val['groupname']);
        $val["hysx"] = iconv("utf-8","gbk",$val["hysx"]);
        $val["conpername"] = iconv("utf-8","gbk",$val["conpername"]);
        $val["settleaccountname"] = iconv("utf-8","gbk",$val["settleaccountname"]);
        $val["settlebank"] = iconv("utf-8","gbk",$val["settlebank"]);
        $val["settlebankname"] = iconv("utf-8","gbk",$val["settlebankname"]);
        $val["settlebankid"] = iconv("utf-8","gbk",$val["settlebankid"]);
        $status= iconv("utf-8","gbk",$revork[$val['revorkflg']]);
        $strlist.= $val["panterid"]."\t,".$val["namechinese"]."\t,".$val["groupname"]."\t,";
        $strlist.= $val["hysx"].",".$status."\t,".$val["conpername"]."\t,".$val["conperbpno"]."\t,";
        $strlist.= $val["conperteleno"]."\t,".$val["settleaccountname"]."\t,".$val["settlebank"]."\t,";
        $strlist.= $val["settlebankname"].",".$val["settlebankid"]."\t,".$val['placeddate']."\n";
      }
      $filename ="商户报表".date('YmdHis');
      $filename = iconv("utf-8","gbk",$filename);
      $this->load_csv($strlist,$filename);
    }
    //新增商户
    function addpanters(){
        if(IS_POST){
            $model=new model();
            ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
            ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
            ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
            ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
            ini_set('upload_max_filesize','8M');//文件上传最大
            ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
            //---20160318----房掌柜、收银一体化数据同步---
            $syncfz = trim(I('post.syncfz'));
            $syncsy = trim(I('post.syncsy'));
            $syncfzinfo = array();
            $syncsyinfo = array();
            //------end------
            $conpername = trim(I('post.conpername',''));
            $namechinese = trim(I('post.namechinese',''));
            $nameenglish = trim(I('post.nameenglish',''));
            $cityid = trim(I('post.cityid',''));
            $conpermobno = trim(I('post.conpermobno',''));
            $conperteleno= trim(I('post.conperteleno',''));
            $conperbpno=trim(I('post.conperbpno',''));
            $goingteleno = trim(I('post.goingteleno',''));
            $revorkflg = trim(I('post.revorkflg',''));
            $parent    = trim(I('post.parent'));
            $pantergroup = trim(I('post.pantergroup',''));
            $hysx   = trim(I('post.hysx',''));
            $account = trim(I('post.account',0));
            $sumnumber = trim(I('post.sumnumber',0));
            $oneaccount = trim(I('post.oneaccount',0));
            //新增字段
            $period = trim(I('post.period',''));//证件有效期
            $address = trim(I('post.panteraddress','')); //经营地址
            $operatescope = trim(I('post.operatescope',''));//经营范围
            $organizationcode = trim(I('post.organizationcode',''));//组织机构代码
            $business = trim(I('post.business',''));//营业执照
            $taxation = trim(I('post.taxation',''));//税务登记证
            $conperbtype = trim(I('post.conperbtype',''));//证件类型
            $legalperson = trim(I('post.legalperson',''));//法人代表
            $tongbaoitem = trim(I('post.tongbaoitem',''));
            $timevalue =trim(I('post.timevalue',''));
            $accounttype = trim(I('post.accounttype',''));
            //商户名字不能重复
            $dataif['namechinese'] = $namechinese;
            $bool = $model->table('panters')->where($dataif)->find();
            if($bool) {
                $this->error("商户名重复");
            }
            $valiMap = ['namechinese'=>$namechinese,'address'=>$address,'operatescope'=>$operatescope,
                        'business'=>$business ,'timevalue'=>$timevalue,'hysx'=>$hysx,'accounttype'=>$accounttype,
                        'conperbtype'=>$conperbtype,'conperbpno'=>$conperbpno,'period'=>$period,'legalperson'=>$legalperson,
                        'conpermobno'=>$conpermobno,'account'=>$account,'sumnumber'=>$sumnumber,'oneaccount'=>$oneaccount
            ];
            $valiMsg = ['namechinese'=>'商户名称','address'=>'商户地址','operatescope'=>'经验范围','business'=>'营业执照',
                        'timevalue'=>'营业执照有效期','hysx'=>'行业属性','accounttype'=>'账户类型','conperbtype'=>'证件类型',
                        'conperbpno'=>'法人代表证件号','period'=>'证件有效期','legalperson'=>'法人代表',
                        'conpermobno'=>'法人代表手机号','account'=>'每日消费限制','sumnumber'=>'每日消费次数','oneaccount'=>'每笔刷卡限额'
            ];
            //校验商户字段不能为空
            $this->valiAddPanterFieldEmpty($valiMap,$valiMsg);
            //校验商户添加 那些图片必须上传
            $this->valiImgUpload($_FILES,['licenseimg'=>'营业执照','doorplateimg'=>'商户门头','idface'=>'法人证件正面','idcon'=>'法人证件号反面']);
            //手机号格式控制
            $preg = '/^[1][34578][0-9]{9}$/';
            if(!preg_match($preg,$conpermobno)) {
                $this->error("控制人手机号格式不对");
            }
//            //图片上传到远程服务器
//            $uploadInfo=json_decode($this->uploadPanterImg($_FILES),true);
//            if($uploadInfo['status']==='1'){
//                $licenseimg  = $uploadInfo['data']['licenseimg'];
//                $doorplateimg= $uploadInfo['data']['doorplateimg'];
//                $idface      = $uploadInfo['data']['idface'];
//                $idcon       = $uploadInfo['data']['idcon'];
//                $orzimg      = $uploadInfo['data']['orzimg']?:null;
//                $taximg      = $uploadInfo['data']['taximg']?:null;
//                //删除本地残留图片
//                $this->unlikeLocalImg($uploadInfo['data']);
//            }else{
//                $this->error('上传图片失败!');
//            }
	        $path = '/home/zzcard/images/IMAGES/panters/';
	        $pathsave = 'IMAGES/panters/';
	        $type1 =pathinfo($_FILES['licenseimg']['name'],PATHINFO_EXTENSION);
	        $type2 =pathinfo($_FILES['doorplateimg']['name'],PATHINFO_EXTENSION);
	        $type3 =pathinfo($_FILES['idface']['name'],PATHINFO_EXTENSION);
	        $type4 =pathinfo($_FILES['idcon']['name'],PATHINFO_EXTENSION);
	        do{$newname1=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
	        while(file_exists($path.$newname1));
	        do{$newname2=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
	        while(file_exists($path.$newname2));
	        do{$newname3=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
	        while(file_exists($path.$newname3));
	        do{$newname4=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
	        while(file_exists($path.$newname4));
	        if(is_uploaded_file($_FILES['licenseimg']['tmp_name'])){
		        if(!move_uploaded_file($_FILES['licenseimg']['tmp_name'] ,$path.$newname1)){
			        echo "文件移动到制定位置失败！";
			        exit;
		        }
	        }

	        if(is_uploaded_file($_FILES['doorplateimg']['tmp_name'])){
		        if(!move_uploaded_file($_FILES['doorplateimg']['tmp_name'] ,$path.$newname2)){
			        echo "文件移动到制定位置失败！";
			        exit;
		        }
	        }
	        if(is_uploaded_file($_FILES['idface']['tmp_name'])){
		        if(!move_uploaded_file($_FILES['idface']['tmp_name'] ,$path.$newname3)){
			        echo "文件移动到制定位置失败！";
			        exit;
		        }
	        }
	        if(is_uploaded_file($_FILES['idcon']['tmp_name'])){
		        if(!move_uploaded_file($_FILES['idcon']['tmp_name'] ,$path.$newname4)){
			        echo "文件移动到制定位置失败！";
			        exit;
		        }
	        }
//                 $licenseimg=$upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
//                 $doorplateimg=$upInfo['doorplateimg']['savepath'].$upInfo['doorplateimg']['savename'];
//                 $idface =$upInfo['idface']['savepath'].$upInfo['idface']['savename'];
//                 $idcon =$upInfo['idcon']['savepath'].$upInfo['idcon']['savename'];
	        // 保存路径
	        $licenseimg=$pathsave.$newname1;
	        $doorplateimg=$pathsave.$newname2;
	        $idface=$pathsave.$newname3;
	        $idcon=$pathsave.$newname4;
	        //
	        $doorplateimg_type="/home/zzcard/images/".$doorplateimg;
	        $licenseimg_type="/home/zzcard/images/".$licenseimg;
	        $idface_type="/home/zzcard/images/".$idface;
	        $idcon_type="/home/zzcard/images/".$idcon;
	        $type_img=file_type($licenseimg_type);
	        if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
		        $this->error('营业执照,请上传图片！');
		        unlink($licenseimg_type);
	        }
	        $type_img2=file_type($doorplateimg_type);
	        if(!in_array($type_img2,array('jpg', 'gif', 'png', 'jpeg'))){
		        $this->error('商户门头,请上传图片！');
		        unlink($doorplateimg_type);
	        }
	        $type_img3=file_type($idface_type);
	        if(!in_array($type_img3,array('jpg', 'gif', 'png', 'jpeg'))){
		        unlink($idface_type);
		        $this->error('法人代表证件正面,请上传图片！');
	        }
	        $type_img4=file_type($idcon_type);
	        if(!in_array($type_img4,array('jpg', 'gif', 'png', 'jpeg'))){
		        $this->error('法人代表证件正面,请上传图片！');
		        unlink($idcon_type);
	        }
	        if($_FILES['orzimg']['error']===0){
		        $type =pathinfo($_FILES['orzimg']['name'],PATHINFO_EXTENSION);
		        do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
		        while(file_exists($path.$newname));
		        if(is_uploaded_file($_FILES['orzimg']['tmp_name'])){
			        if(!move_uploaded_file($_FILES['orzimg']['tmp_name'] ,$path.$newname)){
				        $this->error("组织机构代码证上传失败！");
				        exit;
			        }
		        }
		        $orzimg = $pathsave.$newname;
		        $orzimg_type="/home/zzcard/images/".$orzimg;
		        $type_img=file_type($orzimg_type);
		        if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
			        unlink($orzimg_type);
			        $this->error('组织机构代码证,请上传图片！');
		        }
	        }
	        if($_FILES['taximg']['error']===0){
		        $type =pathinfo($_FILES['taximg']['name'],PATHINFO_EXTENSION);
		        do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
		        while(file_exists($path.$newname));
		        if(is_uploaded_file($_FILES['taximg']['tmp_name'])){
			        if(!move_uploaded_file($_FILES['taximg']['tmp_name'] ,$path.$newname)){
				        $this->error("税务登记证上传失败！");
				        exit;
			        }
		        }
		        $taximg = $pathsave.$newname;
		        $taximg_type="/home/zzcard/images/".$taximg;
		        $type_img=file_type($taximg_type);
		        if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
			        unlink($taximg_type);
			        $this->error('税务登记证,请上传图片！');
		        }
	        }
            $panterid=$this->getFieldNextNumber("panterid");
            $currentDate=date('Ymd');
            $userid =  $this->userid;

            $conperbpno = $this->encodePanterConperbpno($conperbpno,$panterid);

            $psql="INSERT INTO panters (panterid,conpername,namechinese,nameenglish,cityid,conpermobno,conperteleno,address,operatescope,organizationcode,business,taxation,conperbtype,legalperson,";
            //$psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,tongbao_rate,placeddate,idface,idcon,timevalue,orzimg,taximg,ctongbao_rate,wtongbao_rate) VALUES ";
            $psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,placeddate,idface,idcon,timevalue,orzimg,taximg,status,accounttype) VALUES ";
            $psql.="('".$panterid."','".$conpername."','".$namechinese."','".$nameenglish."','".$cityid."','".$conpermobno."','";
            $psql.=$conperteleno."','".$address."','".$operatescope."','".$organizationcode."','".$business."','".$taxation."','".$conperbtype."','".$legalperson."','".$conperbpno."','Y','".$parent."','".$pantergroup."','".$hysx."','".$goingteleno."','N',100,30,3,'".$licenseimg."','".$doorplateimg;
            //$psql.="','".$period."','".$tongbaoitem."','".$tongbao_rate."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."','".$ctongbao_rate."','".$wtongbao_rate."')";
            $psql.="','".$period."','".$tongbaoitem."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."',".'0'.",'".$accounttype."')";
            $fxsql="INSERT INTO panter_con_account values ('".$panterid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')";
            $model->startTrans();
            if($model->execute($psql)){
                if($model->execute($fxsql)){
                    //--------20160318-----房掌柜数据同步
                    if($syncfz == 1){
                        $result = $this->_fzgSync($namechinese,$panterid,$cityid,$conperteleno);
                        if($result['returnCode'] != 1){
                            $model->rollback();
                            $this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('Panters/addpanters'));
                        }
                    }
                    //----------end-----
                    $model->commit();
                    $this->success('商户添加成功',U('Panters/addterminal',array('panterid'=>$panterid)));
                }else{
                    $model->rollback();
                    $this->error('商户添加失败',U('Panters/addpanters'));
                }
            }else{
                $model->rollback();
                $this->error('商户添加失败',U('Panters/addpanters'));
            }
        }else{
            $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
            $this->assign('hysxs',$hysxs);
            $province=M('province');
            //查询所属机构  start
            $parentwhere['flag']='1';
            $parentwhere['revorkflg']='N';
            $parents = M('panters')->where($parentwhere)->select();
            $this->assign('parents',$parents);
            //================end===============//
            $provinces = $province->order('provinceid asc')->select();
            $this->assign('province',$provinces);
            $pantergroup=M('pantergroup');
            $pantergroups= $pantergroup->order('groupid asc')->select();
            $this->assign('pantergroups',$pantergroups);
            $this->display();
        }
    }
    private function valiAddPanterFieldEmpty($map,$errorMsg){
        foreach ($map as $key=>$val){
            if($val==''){
                $this->error($errorMsg[$key].'不能为空');
            }
        }
    }
    private function valiImgUpload($files,$index){
        foreach ($index as $key=>$val){
            if($files[$key]['error']!=0){
                $this->error('未上传'.$val);
            }
        }
    }
    private function unlikeLocalImg($info){
        foreach ($info as $val){
            if($val){
                unlink(PUBLIC_PATH.$val);
            }
        }
        return true;
    }
    private function uploadPanterImg($files){
        $path = PUBLIC_PATH.'upimg/';
        foreach ($files as $key=>$val){
            if($val['error']===0){
                $type = pathinfo($val['name'],PATHINFO_EXTENSION);
                do{$newname=$path.date('YmdHis').md5(rand(1,999)).".".$type;}
                while(file_exists($newname));
                if(is_uploaded_file($val['tmp_name'])){
                    if(!move_uploaded_file($val['tmp_name'], $newname)){
                        echo "文件移动到制定位置失败！";
                        exit;
                    }
                }
                $type_if = file_type($newname);
                if(in_array($type_if,array('jpg', 'gif', 'png', 'jpeg'))){
                    $remote[$key] = new \CURLFile(realpath($newname)) ;
                }else{
                    $this->error('不要上传非图片内容');
                    unlink($newname);
                }

            }
        }
        $url = 'http://10.1.1.32:81/ImgServer/public/api/File/index';
      // $url  =  'http://192.168.2.28:8080/api/File/index';
       return  $this->curlUploadImg($remote,$url);
    }
    private function curlUploadImg($data,$curl){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($ch, CURLOPT_URL,$curl);
        curl_setopt($ch, CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); //连接超时
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $data=curl_exec ($ch);
        return $data;
    }
//    function addpanters(){
//        $model=new model();
//        ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
//        ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
//        ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
//        ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
//        ini_set('upload_max_filesize','8M');//文件上传最大
//        ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
//        if(IS_POST){
//            $model->startTrans();
//            //---20160318----房掌柜、收银一体化数据同步---
//            $syncfz = trim(I('post.syncfz'));
//            $syncsy = trim(I('post.syncsy'));
//            $syncfzinfo = array();
//            $syncsyinfo = array();
//            //------end------
//            $conpername = trim(I('post.conpername',''));
//            $namechinese = trim(I('post.namechinese',''));
//            $nameenglish = trim(I('post.nameenglish',''));
//            $cityid = trim(I('post.cityid',''));
//            $conpermobno = trim(I('post.conpermobno',''));
//            $conperteleno= trim(I('post.conperteleno',''));
//            $conperbpno=trim(I('post.conperbpno',''));
//            $goingteleno = trim(I('post.goingteleno',''));
//            //$revorkflg = trim(I('post.revorkflg',''));
//            $parent    = trim(I('post.parent'));
//            $pantergroup = trim(I('post.pantergroup',''));
//            $hysx   = trim(I('post.hysx',''));
//            $account = trim(I('post.account',0));
//            $sumnumber = trim(I('post.sumnumber',0));
//            $oneaccount = trim(I('post.oneaccount',0));
//            //新增字段
//            $period = trim(I('post.period',''));//证件有效期
//            $address = trim(I('post.panteraddress','')); //经营地址
//            $operatescope = trim(I('post.operatescope',''));//经营范围
//            $organizationcode = trim(I('post.organizationcode',''));//组织机构代码
//            $business = trim(I('post.business',''));//营业执照
//            $taxation = trim(I('post.taxation',''));//税务登记证
//            $conperbtype = trim(I('post.conperbtype',''));//证件类型
//            $legalperson = trim(I('post.legalperson',''));//法人代表
//            $tongbaoitem = trim(I('post.tongbaoitem',''));
//            //$tongbao_rate = trim(I('post.tongbao_rate',''));
//            //$ctongbao_rate=trim(I('post.ctongbao_rate',''));
//            //$wtongbao_rate=trim(I('post.wtongbao_rate',''));
//            $timevalue =trim(I('post.timevalue',''));
//            $accounttype = trim(I('post.accounttype',''));
//            //商户名字不能重复
//            $dataif['namechinese'] = $namechinese;
//            $bool = $model->table('panters')->where($dataif)->find();
//            if($bool)
//            {
//                $this->error("商户名重复");
//            }
//            if($namechinese==''){
//                $this->error('商户名称不能为空!');
//            }
//            if($address=='')
//            {
//                $this->error("商户地址不能为空!");
//            }
//            if($operatescope=='')
//            {
//                $this->error("经营范围不能为空!");
//            }
//            if($business=="")
//            {
//                $this->error("营业执照不能为空");
//            }
//            if($timevalue=="")
//            {
//                $this->error("营业执照有效期不能为空");
//            }
//            if(empty($hysx)){
//                $this->error('行业属性必选！');
//            }
//            // if($conpername=="")
//            // {
//            //   $this->error("实际控制人不能为空!");
//            // }
//            if($accounttype==''){
//                $this->error('账户类型不能为空！');
//            }
//            if($conperbtype=="")
//            {
//                $this->error("证件类型不能为空");
//            }
//            if($conperbpno=="")
//            {
//                $this->error("法人代表证件号不能为空!");
//            }
//            if($period=="")
//            {
//                $this->error("证件有效期不能为空");
//            }
//            if($legalperson=="")
//            {
//                $this->error("法人代表不能为空");
//            }
//            if($conpermobno==''){
//                $this->error('法人代表手机号不能为空!');
//            }
//            if($account=="")
//            {
//                $this->error("每日消费限制不能为空");
//            }
//            if($sumnumber=="")
//            {
//                $this->error("每日消费次数不能为空");
//            }
//            if($oneaccount=="")
//            {
//                $this->error("每笔刷卡限额不能为空");
//            }
//            //手机号格式控制
//            $preg = '/^[1][34578][0-9]{9}$/';
//            if(!preg_match($preg,$conpermobno))
//            {
//                $this->error("控制人手机号格式不对");
//                exit;
//            }
//            if($_FILES['licenseimg']['error']!=0){
//                $this->error('未上传营业执照');
//            }
//            elseif($_FILES['doorplateimg']['error']!=0){
//                $this->error('未上传商户门头');
//            }elseif($_FILES['idface']['error']!=0){
//                $this->error('未上法人证件号正面');
//            }
//            elseif($_FILES['idcon']['error']!=0){
//                $this->error('未上法人证件号反面');
//            }
//            else{
////                 $upInfo=$this->_upload("panter");
////                 dump($_FILES);EXIT;
//                $path = PUBLIC_PATH."upfile/panter/";
//                $pathsave ="upfile/panter/";
//                $type1 =pathinfo($_FILES['licenseimg']['name'],PATHINFO_EXTENSION);
//                $type2 =pathinfo($_FILES['doorplateimg']['name'],PATHINFO_EXTENSION);
//                $type3 =pathinfo($_FILES['idface']['name'],PATHINFO_EXTENSION);
//                $type4 =pathinfo($_FILES['idcon']['name'],PATHINFO_EXTENSION);
//                do{$newname1=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
//                while(file_exists($path.$newname1));
//                do{$newname2=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
//                while(file_exists($path.$newname2));
//                do{$newname3=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
//                while(file_exists($path.$newname3));
//                do{$newname4=date('ymdhis',time()).md5(rand(100,999)).".".$type1;}
//                while(file_exists($path.$newname4));
//                if(is_uploaded_file($_FILES['licenseimg']['tmp_name'])){
//                    if(!move_uploaded_file($_FILES['licenseimg']['tmp_name'] ,$path.$newname1)){
//                        echo "文件移动到制定位置失败！";
//                        exit;
//                    }
//                }
//
//                if(is_uploaded_file($_FILES['doorplateimg']['tmp_name'])){
//                    if(!move_uploaded_file($_FILES['doorplateimg']['tmp_name'] ,$path.$newname2)){
//                        echo "文件移动到制定位置失败！";
//                        exit;
//                    }
//                }
//                if(is_uploaded_file($_FILES['idface']['tmp_name'])){
//                    if(!move_uploaded_file($_FILES['idface']['tmp_name'] ,$path.$newname3)){
//                        echo "文件移动到制定位置失败！";
//                        exit;
//                    }
//                }
//                if(is_uploaded_file($_FILES['idcon']['tmp_name'])){
//                    if(!move_uploaded_file($_FILES['idcon']['tmp_name'] ,$path.$newname4)){
//                        echo "文件移动到制定位置失败！";
//                        exit;
//                    }
//                }
////                 $licenseimg=$upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
////                 $doorplateimg=$upInfo['doorplateimg']['savepath'].$upInfo['doorplateimg']['savename'];
////                 $idface =$upInfo['idface']['savepath'].$upInfo['idface']['savename'];
////                 $idcon =$upInfo['idcon']['savepath'].$upInfo['idcon']['savename'];
//                $licenseimg=$pathsave.$newname1;
//                $doorplateimg=$pathsave.$newname2;
//                $idface=$pathsave.$newname3;
//                $idcon=$pathsave.$newname4;
//                $doorplateimg_type="./Public/".$doorplateimg;
//                $licenseimg_type="./Public/".$licenseimg;
//                $idface_type="./Public/".$idface;
//                $idcon_type="./Public/".$idcon;
//                $type_img=file_type($licenseimg_type);
//                if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//                    $this->error('营业执照,请上传图片！');
//                    unlink($licenseimg_type);
//                }
//                $type_img2=file_type($doorplateimg_type);
//                if(!in_array($type_img2,array('jpg', 'gif', 'png', 'jpeg'))){
//                    $this->error('商户门头,请上传图片！');
//                    unlink($doorplateimg_type);
//                }
//                $type_img3=file_type($idface_type);
//                if(!in_array($type_img3,array('jpg', 'gif', 'png', 'jpeg'))){
//                    unlink($idface_type);
//                    $this->error('法人代表证件正面,请上传图片！');
//                }
//                $type_img4=file_type($idcon_type);
//                if(!in_array($type_img4,array('jpg', 'gif', 'png', 'jpeg'))){
//                    $this->error('法人代表证件正面,请上传图片！');
//                    unlink($idcon_type);
//                }
//                if($_FILES['orzimg']['error']===0){
//                    $type =pathinfo($_FILES['orzimg']['name'],PATHINFO_EXTENSION);
//                    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//                    while(file_exists($path.$newname));
//                    if(is_uploaded_file($_FILES['orzimg']['tmp_name'])){
//                        if(!move_uploaded_file($_FILES['orzimg']['tmp_name'] ,$path.$newname)){
//                             $this->error("组织机构代码证上传失败！");
//                            exit;
//                        }
//                    }
//                    $orzimg = $pathsave.$newname;
//                    $orzimg_type="./Public/".$orzimg;
//                    $type_img=file_type($orzimg_type);
//                    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//                        unlink($orzimg_type);
//                        $this->error('组织机构代码证,请上传图片！');
//                    }
//                }
//                if($_FILES['taximg']['error']===0){
//                    $type =pathinfo($_FILES['taximg']['name'],PATHINFO_EXTENSION);
//                    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//                    while(file_exists($path.$newname));
//                    if(is_uploaded_file($_FILES['taximg']['tmp_name'])){
//                        if(!move_uploaded_file($_FILES['taximg']['tmp_name'] ,$path.$newname)){
//                            $this->error("税务登记证上传失败！");
//                            exit;
//                        }
//                    }
//                    $taximg = $pathsave.$newname;
//                    $taximg_type="./Public/".$taximg;
//                    $type_img=file_type($taximg_type);
//                    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//                        unlink($taximg_type);
//                        $this->error('税务登记证,请上传图片！');
//                    }
//                }
//            }
//            $panterid=$this->getFieldNextNumber("panterid");
//            $currentDate=date('Ymd');
//            $userid =  $this->userid;
//            $psql="INSERT INTO panters (panterid,conpername,namechinese,nameenglish,cityid,conpermobno,conperteleno,address,operatescope,organizationcode,business,taxation,conperbtype,legalperson,";
//            //$psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,tongbao_rate,placeddate,idface,idcon,timevalue,orzimg,taximg,ctongbao_rate,wtongbao_rate) VALUES ";
//			$psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,placeddate,idface,idcon,timevalue,orzimg,taximg,status,accounttype) VALUES ";
//            $psql.="('".$panterid."','".$conpername."','".$namechinese."','".$nameenglish."','".$cityid."','".$conpermobno."','";
//            $psql.=$conperteleno."','".$address."','".$operatescope."','".$organizationcode."','".$business."','".$taxation."','".$conperbtype."','".$legalperson."','".$conperbpno."','Y','".$parent."','".$pantergroup."','".$hysx."','".$goingteleno."','N',100,30,3,'".$licenseimg."','".$doorplateimg;
//            //$psql.="','".$period."','".$tongbaoitem."','".$tongbao_rate."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."','".$ctongbao_rate."','".$wtongbao_rate."')";
//			$psql.="','".$period."','".$tongbaoitem."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."',".'0'.",'".$accounttype."')";
//            $fxsql="INSERT INTO panter_con_account values ('".$panterid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')";
//            if($model->execute($psql)){
//              if($model->execute($fxsql)){
//                //--------20160318-----房掌柜数据同步
//					if($syncfz == 1){
//						$result = $this->_fzgSync($namechinese,$panterid,$cityid,$conperteleno);
//						if($result['returnCode'] != 1){
//							$model->rollback();
//							$this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('Panters/addpanters'));
//						}
//					}
//                    //----------end-----
//                    $model->commit();
//                    $this->success('商户添加成功',U('Panters/addterminal',array('panterid'=>$panterid)));
//                }else{
//                    $model->rollback();
//                    $this->error('商户添加失败',U('Panters/addpanters'));
//                }
//            }else{
//                $model->rollback();
//                $this->error('商户添加失败',U('Panters/addpanters'));
//            }
//        }else{
//            $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
//            $this->assign('hysxs',$hysxs);
//            $province=M('province');
//            //查询所属机构  start
//            $parentwhere['flag']='1';
//						$parentwhere['revorkflg']='N';
//            $parents = M('panters')->where($parentwhere)->select();
//            $this->assign('parents',$parents);
//            //================end===============//
//            $provinces = $province->order('provinceid asc')->select();
//            $this->assign('province',$provinces);
//            $pantergroup=M('pantergroup');
//            $pantergroups= $pantergroup->order('groupid asc')->select();
//            $this->assign('pantergroups',$pantergroups);
//            $this->display();
//        }
//    }
    //修改客户信息
    function editpanters(){
        $model = M('panters');
        $pantersca = M('panter_con_account');
        if(IS_POST){
            ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
            ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
            ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
            ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
            ini_set('upload_max_filesize','8M');//文件上传最大
            ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
            $userid =  $this->userid;
            $model->startTrans();
            $panterid=I('post.panterid','');
            //---20160318----房掌柜、收银一体化数据同步---
            $syncfz = trim(I('post.syncfz'));
            $syncsy = trim(I('post.syncsy'));
            $syncfzinfo = array();
            $syncsyinfo = array();
            //------end------
            $nameenglish = trim(I('post.nameenglish',''));
            $conperteleno= trim(I('post.conperteleno',''));
            $goingteleno = trim(I('post.goingteleno',''));
            $revorkflg = trim(I('post.revorkflg',''));
            $parent    = trim(I('post.parent'));
            $pantergroup = trim(I('post.pantergroup',''));
            $account = I('post.account',0);
            $sumnumber = I('post.sumnumber',0);
            $oneaccount = trim(I('post.oneaccount',0));
            $conpermobno = trim(I('post.conpermobno',''));
            $namechinese = trim(I('post.namechinese',''));
            $hysx   = trim(I('post.hysx',''));
            $cityid = trim(I('post.cityid',''));
            $timevalue = trim(I('post.timevalue',''));
            $conpername = trim(I('post.conpername',''));
            $period = trim(I('post.period',''));//证件有效期
            $address = trim(I('post.panteraddress','')); //经营地址
            $_POST['address']=trim(I('post.panteraddress',''));
            $operatescope = trim(I('post.operatescope',''));//经营范围
            $organizationcode = trim(I('post.organizationcode',''));//组织机构代码
            $business = trim(I('post.business',''));//营业执照
            $taxation = trim(I('post.taxation',''));//税务登记证
            $conperbtype = trim(I('post.conperbtype',''));//证件类型
            $legalperson = trim(I('post.legalperson',''));//法人代表
            $conperbpno=trim(I('post.conperbpno',''));
            $tongbaoitem = trim(I('post.tongbaoitem'));
            $tongbao_rate = trim(I('post.tongbao_rate',''));
            $ctongbao_rate = trim(I('post.ctongbao_rate',''));
            $wtongbao_rate = trim(I('post.wtongbao_rate',''));
            //商户名字不能重复
            $dataif['namechinese'] = $namechinese;
            $dataif['panterid'] = array("neq",$panterid);
            $bool = $model->where($dataif)->find();
            if($bool)
            {
                $this->error("商户名重复");
            }
            $valiMap = ['panterid'=>$panterid,
                'namechinese'=>$namechinese,'address'=>$address,'operatescope'=>$operatescope,
                'business'=>$business ,'timevalue'=>$timevalue,'hysx'=>$hysx,
                'conperbtype'=>$conperbtype,'conperbpno'=>$conperbpno,'period'=>$period,'legalperson'=>$legalperson,
                'conpermobno'=>$conpermobno,'account'=>$account,'sumnumber'=>$sumnumber,'oneaccount'=>$oneaccount
            ];
            $valiMsg = ['panterid'=>'商户编号',
                'namechinese'=>'商户名称','address'=>'商户地址','operatescope'=>'经验范围','business'=>'营业执照',
                'timevalue'=>'营业执照有效期','hysx'=>'行业属性','conperbtype'=>'证件类型',
                'conperbpno'=>'法人代表证件号','period'=>'证件有效期','legalperson'=>'法人代表',
                'conpermobno'=>'法人代表手机号','account'=>'每日消费限制','sumnumber'=>'每日消费次数','oneaccount'=>'每笔刷卡限额'
            ];
            //校验商户字段不能为空
            $this->valiAddPanterFieldEmpty($valiMap,$valiMsg);
            $preg = '/^[1][34578][0-9]{9}$/';
            if(!preg_match($preg,$conpermobno)) {
                $this->error("手机号格式不对");
            }
            //验证 是否有图片上传
//            if($this->editPanterIfImgUpload($_FILES)){
//                $uploadInfo=json_decode($this->uploadPanterImg($_FILES),true);
//                if($uploadInfo['status']==='1'){
//                    !($uploadInfo['data']['licenseimg'])||$_POST['licenseimg']  =$uploadInfo['data']['licenseimg'];
//                    !($uploadInfo['data']['doorplateimg'])||$_POST['doorplateimg']=$uploadInfo['data']['doorplateimg'];
//                    !($uploadInfo['data']['idface'])||$_POST['idface']=$uploadInfo['data']['idface'];
//                    !($uploadInfo['data']['idcon'])||$_POST['idcon']=$uploadInfo['data']['idcon'];
//                    !($uploadInfo['data']['orzimg'])||$_POST['orzimg']=$uploadInfo['data']['orzimg'];
//                    !($uploadInfo['data']['taximg'])||$_POST['taximg']=$uploadInfo['data']['taximg'];
//                    //删除本地残留图片
//                    $this->unlikeLocalImg($uploadInfo['data']);
//                }else{
//                    $this->error('上传图片失败!');
//                }
//            }
	        $path = '/home/zzcard/images/IMAGES/panters/';
	        $pathsave = 'IMAGES/panters/';

			if($_FILES['licenseimg']['error']==0||$_FILES['doorplateimg']['error']==0 ||$_FILES['idface']['error']==0 ||$_FILES['idcon']['error']==0){
				$panterif['panterid'] = $panterid;
				$list = $model->where($panterif)->find();
				// $list=$custom->where('panterid='.$panterid")->find();
				if($_FILES['licenseimg']['error']===0){
				    $type =pathinfo($_FILES['licenseimg']['name'],PATHINFO_EXTENSION);
				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
				    while(file_exists($path.$newname));
				    if(is_uploaded_file($_FILES['licenseimg']['tmp_name'])){
				        if(!move_uploaded_file($_FILES['licenseimg']['tmp_name'] ,$path.$newname)){
				            echo "文件移动到制定位置失败！";
				            exit;
				        }
				    }
				    $data['licenseimg']=$path.$newname;
				    $_POST['licenseimg']=$pathsave.$newname;
// 					$data['licenseimg']=$upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
// 					$_POST['licenseimg'] = $upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
// 				    $data['licenseimg'] = "./Public/".$data['licenseimg'];
			        $type_img=file_type($data['licenseimg']);
						if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
						    unlink($data['licenseimg']);
							$this->error('营业执照,请上传图片！');
						}
						if(!empty($list['licenseimg'])){
						    unlink('./Public/'.$list['licenseimg']);
						}
				}
			if($_FILES['doorplateimg']['error']===0){

				    $type =pathinfo($_FILES['doorplateimg']['name'],PATHINFO_EXTENSION);
				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
				    while(file_exists($path.$newname));
				    if(is_uploaded_file($_FILES['doorplateimg']['tmp_name'])){
				        if(!move_uploaded_file($_FILES['doorplateimg']['tmp_name'] ,$path.$newname)){
				            echo "文件移动到制定位置失败！";
				            exit;
				        }
				    }
				    $data['doorplateimg']=$path.$newname;
				    $_POST['doorplateimg']=$pathsave.$newname;
				    $type_img=file_type($data['doorplateimg']);
				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
				        unlink($data['doorplateimg']);
				        $this->error('商户门头,请上传图片！');
				    }
				    if(!empty($list['doorplateimg'])){
				        unlink('/home/zzcard/images/'.$list['doorplateimg']);
				    }
				}
				if($_FILES['idface']['error']===0){
				    $type =pathinfo($_FILES['idface']['name'],PATHINFO_EXTENSION);
				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
				    while(file_exists($path.$newname));
				    if(is_uploaded_file($_FILES['idface']['tmp_name'])){
				        if(!move_uploaded_file($_FILES['idface']['tmp_name'] ,$path.$newname)){
				            echo "文件移动到制定位置失败！";
				            exit;
				        }
				    }
				    $data['idface']=$path.$newname;
				    $_POST['idface']=$pathsave.$newname;
				    $type_img=file_type($data['idface']);
				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
				        unlink($data['idface']);
				        $this->error('法人代表身份正面,请上传图片！');
				    }
				    if(!empty($list['idface'])){
				        unlink('/home/zzcard/images/'.$list['idface']);
				    }
				}
				if($_FILES['idcon']['error']===0){
				    $type =pathinfo($_FILES['idcon']['name'],PATHINFO_EXTENSION);
				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
				    while(file_exists($path.$newname));
				    if(is_uploaded_file($_FILES['idcon']['tmp_name'])){
				        if(!move_uploaded_file($_FILES['idcon']['tmp_name'] ,$path.$newname)){
				            echo "文件移动到制定位置失败！";
				            exit;
				        }
				    }
				    $data['idcon']=$path.$newname;
				    $_POST['idcon']=$pathsave.$newname;
				    $type_img=file_type($data['idcon']);
				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
				        unlink($data['idcon']);
				        $this->error('法人代表身份正面,请上传图片！');
				    }
				    if(!empty($list['idcon'])){
				        unlink('/home/zzcard/images/'.$list['idface']);
				    }
				}
			}
			if($_FILES['orzimg']['error']===0){
			    $type =pathinfo($_FILES['orzimg']['name'],PATHINFO_EXTENSION);
			    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
			    while(file_exists($path.$newname));
			    if(is_uploaded_file($_FILES['orzimg']['tmp_name'])){
			        if(!move_uploaded_file($_FILES['orzimg']['tmp_name'] ,$path.$newname)){
			            echo "文件移动到制定位置失败！";
			            exit;
			        }
			    }
			    $data['orzimg']=$path.$newname;
			    $_POST['orzimg']=$pathsave.$newname;
			    $type_img=file_type($data['orzimg']);
			    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
			        unlink($data['orzimg']);
			        $this->error('组织机构代码证,请上传图片！');
			    }
			    if(!empty($list['orzimg'])){
			        unlink('/home/zzcard/images/'.$list['orzimg']);
			    }
			}
			if($_FILES['taximg']['error']===0){
			    $type =pathinfo($_FILES['taximg']['name'],PATHINFO_EXTENSION);
			    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
			    while(file_exists($path.$newname));
			    if(is_uploaded_file($_FILES['taximg']['tmp_name'])){
			        if(!move_uploaded_file($_FILES['taximg']['tmp_name'] ,$path.$newname)){
			            echo "文件移动到制定位置失败！";
			            exit;
			        }
			    }
			    $data['taximg']=$path.$newname;
			    $_POST['taximg']=$pathsave.$newname;
			    $type_img=file_type($data['taximg']);
			    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
			        unlink($data['taximg']);
			        $this->error('税务登记证,请上传图片！');
			    }
			    if(!empty($list['taximg'])){
			        unlink('/home/zzcard/images/'.$list['taximg']);
			    }
			}
            $placeddateif = M('panters')->where(array('panterid'=>$panterid))->field('placeddate')->find();
            if($placeddateif['placeddate']===null){
                $_POST['placeddate'] = date('Ymd',time());
            }
            if($model->create()){
// 				var_dump($_POST);exit;
                $wheres['panterid']=$panterid;
                $_POST['status']='0';
                if($model->where($wheres)->save($_POST)){
                    $fxpanter=$pantersca->where($wheres)->find();
                    //var_dump($fxpanter);exit;
                    if($fxpanter==false){
                        $fxif=$pantersca->execute("INSERT INTO panter_con_account values ('".$panterid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')");
                    }else{
                        $fxif=$pantersca->execute("UPDATE panter_con_account set d_sum_account=".$account.",d_sum_number=".$sumnumber.",d_one_account=".$oneaccount." where panterid='".$panterid."'");
                    }
                    if($fxif==false){
                        $model->rollback();
                        $this->error('修改失败！');
                    }else{
                        if($syncfz == 1){
                            $result = $this->_fzgSync($namechinese,$panterid,$cityid,$conperteleno);
                            if($result['returnCode'] != 1){
                                $model->rollback();
                                $this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('Panters/addpanters'));
                            }
                        }
                        $model->commit();
                        $this->success('修改成功！',U('Panters/index'));
                        exit;
                    }
                }else{
                    $model->rollback();
                    $this->error('修改失败！');
                    exit;
                }
            }
        }else{
            $province=M('province');
            $provinces = $province->order('provinceid asc')->select();
            $this->assign('province',$provinces);
            $panterid=I('get.panterid','');
            $this->assign('panterid',$panterid);
            if($panterid!=''){
                $where['a.panterid']=$panterid;
            }
            $panters = $model->alias('a')->join('left join panter_con_account b on b.panterid=a.panterid')->where($where)->field('a.*,b.d_one_account,b.d_sum_number,b.d_sum_account')->find();

            if(!is_null($panters['conperbpno'])) $panters['conperbpno'] = $this->decodePanterConperbpno($panters['conperbpno'],$panters['panterid']);
            $cityid=$panters['cityid'];
            if($cityid!=''){
                $wmap['i.cityid']=$cityid;
                $city=M('city');
                $citys = $city->alias('c')
                    ->join('left join __CITY__ i on i.provinceid=c.provinceid')
                    ->where($wmap)->field('c.provinceid,c.cityid,c.cityname')->select();
                $this->assign('city',$citys);

                $wcity=array();
                $wcity['cityid']=$cityid;
                $cityk = $city->where($wcity)->find();
                $provinceid=$cityk['provinceid'];
                $this->assign('provinceid',$provinceid);
            }
            $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
            $this->assign('hysxs',$hysxs);
            //查询所属机构  start
            $parentwhere['flag']='1';
            $parentwhere['revorkflg']='N';
            $parents = M('panters')->where($parentwhere)->select();
            $this->assign('parents',$parents);
            //================end===============//
            $this->assign('panters',$panters);
            $pantergroup=M('pantergroup');
            $pantergroups= $pantergroup->order('groupid asc')->select();
            $this->assign('pantergroups',$pantergroups);
            $this->display();
        }
    }
    private function editPanterIfImgUpload($files){
        foreach ($files as $v){
            if($v['error']===0){
                return true;
            }
        }
        return false;
    }
//    function editpanters(){
//    	$model = M('panters');
//        $pantersca = M('panter_con_account');
//        ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
//        ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
//        ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
//        ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
//        ini_set('upload_max_filesize','8M');//文件上传最大
//        ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
//    	if(IS_POST){
//            $userid =  $this->userid;
//            $model->startTrans();
//    		    $panterid=I('post.panterid','');
//			//---20160318----房掌柜、收银一体化数据同步---
//            $syncfz = trim(I('post.syncfz'));
//            $syncsy = trim(I('post.syncsy'));
//            $syncfzinfo = array();
//            $syncsyinfo = array();
//            //------end------
//            $nameenglish = trim(I('post.nameenglish',''));
//            $conperteleno= trim(I('post.conperteleno',''));
//            $goingteleno = trim(I('post.goingteleno',''));
//            $revorkflg = trim(I('post.revorkflg',''));
//            $parent    = trim(I('post.parent'));
//            $pantergroup = trim(I('post.pantergroup',''));
//            $account = I('post.account',0);
//            $sumnumber = I('post.sumnumber',0);
//            $oneaccount = trim(I('post.oneaccount',0));
//            $conpermobno = trim(I('post.conpermobno',''));
//            $namechinese = trim(I('post.namechinese',''));
//            $hysx   = trim(I('post.hysx',''));
//			     $cityid = trim(I('post.cityid',''));
//			     $timevalue = trim(I('post.timevalue',''));
//            $conpername = trim(I('post.conpername',''));
//            $period = trim(I('post.period',''));//证件有效期
//            $address = trim(I('post.panteraddress','')); //经营地址
//            $_POST['address']=trim(I('post.panteraddress',''));
//            $operatescope = trim(I('post.operatescope',''));//经营范围
//            $organizationcode = trim(I('post.organizationcode',''));//组织机构代码
//            $business = trim(I('post.business',''));//营业执照
//            $taxation = trim(I('post.taxation',''));//税务登记证
//            $conperbtype = trim(I('post.conperbtype',''));//证件类型
//            $legalperson = trim(I('post.legalperson',''));//法人代表
//            $conperbpno=trim(I('post.conperbpno',''));
//            $tongbaoitem = trim(I('post.tongbaoitem'));
//            $tongbao_rate = trim(I('post.tongbao_rate',''));
//            $ctongbao_rate = trim(I('post.ctongbao_rate',''));
//            $wtongbao_rate = trim(I('post.wtongbao_rate',''));
//            //商户名字不能重复
//            $dataif['namechinese'] = $namechinese;
//            $dataif['panterid'] = array("neq",$panterid);
//            $bool = $model->where($dataif)->find();
//            if($bool)
//            {
//              $this->error("商户名重复");
//            }
//            if($namechinese==''){
//                $this->error('商户名称不能为空!');
//            }
//            if($address=='')
//            {
//              $this->error("商户地址不能为空!");
//            }
//            if($operatescope=='')
//            {
//              $this->error("经营范围不能为空!");
//            }
//            if($business=="")
//            {
//              $this->error("营业执照不能为空");
//            }
//            if($timevalue=="")
//            {
//                $this->error("营业执照有效期不能为空");
//            }
//            if(empty($hysx)){
//                $this->error('行业属性必选！');
//            }
//            // if($conpername=="")
//            // {
//            //   $this->error("实际控制人不能为空!");
//            // }
//            if($conperbtype=="")
//            {
//              $this->error("证件类型不能为空");
//            }
//            if($conperbpno=="")
//            {
//              $this->error("法人代表证件号不能为空!");
//            }
//            if($period=="")
//            {
//              $this->error("证件有效期不能为空");
//            }
//            if($legalperson=="")
//            {
//              $this->error("法人代表不能为空");
//            }
//            if($conpermobno==''){
//                $this->error('法人代表手机号不能为空!');
//            }
//            if($account=="")
//            {
//              $this->error("每日消费限制不能为空");
//            }
//            if($sumnumber=="")
//            {
//              $this->error("每日消费次数不能为空");
//            }
//            if($oneaccount=="")
//            {
//              $this->error("每笔刷卡限额不能为空");
//            }
//            $preg = '/^[1][34578][0-9]{9}$/';
//            if(!preg_match($preg,$conpermobno))
//            {
//               $this->error("手机号格式不对");
//               exit;
//            }
//    		if($panterid!=''){
//	    		$wheres['panterid']=$panterid;
//	    	}else{
//	    		$this->error('商户编号不能为空！');
//				exit;
//	    	}
//	    	$path = PUBLIC_PATH."upfile/panter/";
//	    	$pathsave ="upfile/panter/";
//
//			if($_FILES['licenseimg']['error']==0||$_FILES['doorplateimg']['error']==0 ||$_FILES['idface']['error']==0 ||$_FILES['idcon']['error']==0){
//				$panterif['panterid'] = $panterid;
//				$list = $model->where($panterif)->find();
//				// $list=$custom->where('panterid='.$panterid")->find();
//				if($_FILES['licenseimg']['error']===0){
//				    $type =pathinfo($_FILES['licenseimg']['name'],PATHINFO_EXTENSION);
//				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//				    while(file_exists($path.$newname));
//				    if(is_uploaded_file($_FILES['licenseimg']['tmp_name'])){
//				        if(!move_uploaded_file($_FILES['licenseimg']['tmp_name'] ,$path.$newname)){
//				            echo "文件移动到制定位置失败！";
//				            exit;
//				        }
//				    }
//				    $data['licenseimg']=$path.$newname;
//				    $_POST['licenseimg']=$pathsave.$newname;
//// 					$data['licenseimg']=$upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
//// 					$_POST['licenseimg'] = $upInfo['licenseimg']['savepath'].$upInfo['licenseimg']['savename'];
//// 				    $data['licenseimg'] = "./Public/".$data['licenseimg'];
//			        $type_img=file_type($data['licenseimg']);
//						if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//						    unlink($data['licenseimg']);
//							$this->error('营业执照,请上传图片！');
//						}
//						if(!empty($list['licenseimg'])){
//						    unlink('./Public/'.$list['licenseimg']);
//						}
//				}
//			if($_FILES['doorplateimg']['error']===0){
//
//				    $type =pathinfo($_FILES['doorplateimg']['name'],PATHINFO_EXTENSION);
//				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//				    while(file_exists($path.$newname));
//				    if(is_uploaded_file($_FILES['doorplateimg']['tmp_name'])){
//				        if(!move_uploaded_file($_FILES['doorplateimg']['tmp_name'] ,$path.$newname)){
//				            echo "文件移动到制定位置失败！";
//				            exit;
//				        }
//				    }
//				    $data['doorplateimg']=$path.$newname;
//				    $_POST['doorplateimg']=$pathsave.$newname;
//				    $type_img=file_type($data['doorplateimg']);
//				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//				        unlink($data['doorplateimg']);
//				        $this->error('商户门头,请上传图片！');
//				    }
//				    if(!empty($list['doorplateimg'])){
//				        unlink('./Public/'.$list['doorplateimg']);
//				    }
//				}
//				if($_FILES['idface']['error']===0){
//				    $type =pathinfo($_FILES['idface']['name'],PATHINFO_EXTENSION);
//				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//				    while(file_exists($path.$newname));
//				    if(is_uploaded_file($_FILES['idface']['tmp_name'])){
//				        if(!move_uploaded_file($_FILES['idface']['tmp_name'] ,$path.$newname)){
//				            echo "文件移动到制定位置失败！";
//				            exit;
//				        }
//				    }
//				    $data['idface']=$path.$newname;
//				    $_POST['idface']=$pathsave.$newname;
//				    $type_img=file_type($data['idface']);
//				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//				        unlink($data['idface']);
//				        $this->error('法人代表身份正面,请上传图片！');
//				    }
//				    if(!empty($list['idface'])){
//				        unlink('./Public/'.$list['idface']);
//				    }
//				}
//				if($_FILES['idcon']['error']===0){
//				    $type =pathinfo($_FILES['idcon']['name'],PATHINFO_EXTENSION);
//				    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//				    while(file_exists($path.$newname));
//				    if(is_uploaded_file($_FILES['idcon']['tmp_name'])){
//				        if(!move_uploaded_file($_FILES['idcon']['tmp_name'] ,$path.$newname)){
//				            echo "文件移动到制定位置失败！";
//				            exit;
//				        }
//				    }
//				    $data['idcon']=$path.$newname;
//				    $_POST['idcon']=$pathsave.$newname;
//				    $type_img=file_type($data['idcon']);
//				    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//				        unlink($data['idcon']);
//				        $this->error('法人代表身份正面,请上传图片！');
//				    }
//				    if(!empty($list['idcon'])){
//				        unlink('./Public/'.$list['idface']);
//				    }
//				}
//			}
//			if($_FILES['orzimg']['error']===0){
//			    $type =pathinfo($_FILES['orzimg']['name'],PATHINFO_EXTENSION);
//			    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//			    while(file_exists($path.$newname));
//			    if(is_uploaded_file($_FILES['orzimg']['tmp_name'])){
//			        if(!move_uploaded_file($_FILES['orzimg']['tmp_name'] ,$path.$newname)){
//			            echo "文件移动到制定位置失败！";
//			            exit;
//			        }
//			    }
//			    $data['orzimg']=$path.$newname;
//			    $_POST['orzimg']=$pathsave.$newname;
//			    $type_img=file_type($data['orzimg']);
//			    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//			        unlink($data['orzimg']);
//			        $this->error('组织机构代码证,请上传图片！');
//			    }
//			    if(!empty($list['orzimg'])){
//			        unlink('./Public/'.$list['orzimg']);
//			    }
//			}
//			if($_FILES['taximg']['error']===0){
//			    $type =pathinfo($_FILES['taximg']['name'],PATHINFO_EXTENSION);
//			    do{$newname=date('ymdhis',time()).md5(rand(100,999)).".".$type;}
//			    while(file_exists($path.$newname));
//			    if(is_uploaded_file($_FILES['taximg']['tmp_name'])){
//			        if(!move_uploaded_file($_FILES['taximg']['tmp_name'] ,$path.$newname)){
//			            echo "文件移动到制定位置失败！";
//			            exit;
//			        }
//			    }
//			    $data['taximg']=$path.$newname;
//			    $_POST['taximg']=$pathsave.$newname;
//			    $type_img=file_type($data['taximg']);
//			    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
//			        unlink($data['taximg']);
//			        $this->error('税务登记证,请上传图片！');
//			    }
//			    if(!empty($list['taximg'])){
//			        unlink('./Public/'.$list['taximg']);
//			    }
//			}
//			$placeddateif = M('panters')->where(array('panterid'=>$panterid))->field('placeddate')->find();
//			if($placeddateif['placeddate']===null){
//			    $_POST['placeddate'] = date('Ymd',time());
//			}
//	    	if($model->create()){
//// 				var_dump($_POST);exit;
//                $_POST['status']='0';
//	    		if($model->where($wheres)->save($_POST)){
//                    $fxpanter=$pantersca->where($wheres)->find();
//					//var_dump($fxpanter);exit;
//                    if($fxpanter==false){
//                        $fxif=$pantersca->execute("INSERT INTO panter_con_account values ('".$panterid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')");
//                    }else{
//                        $fxif=$pantersca->execute("UPDATE panter_con_account set d_sum_account=".$account.",d_sum_number=".$sumnumber.",d_one_account=".$oneaccount." where panterid='".$panterid."'");
//                    }
//                    if($fxif==false){
//                        $model->rollback();
//                        $this->error('修改失败！');
//                    }else{
//						if($syncfz == 1){
//							$result = $this->_fzgSync($namechinese,$panterid,$cityid,$conperteleno);
//							if($result['returnCode'] != 1){
//								$model->rollback();
//								$this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('Panters/addpanters'));
//							}
//						}
//                        $model->commit();
//                        $this->success('修改成功！',U('Panters/index'));
//                        exit;
//                    }
//				}else{
//                    $model->rollback();
//					$this->error('修改失败！');
//					exit;
//				}
//			}
//    	}
//    	$province=M('province');
//    	$provinces = $province->order('provinceid asc')->select();
//		$this->assign('province',$provinces);
//    	$panterid=I('get.panterid','');
//    	$this->assign('panterid',$panterid);
//    	if($panterid!=''){
//    		$where['a.panterid']=$panterid;
//    	}
//    	$panters = $model->alias('a')->join('left join panter_con_account b on b.panterid=a.panterid')->where($where)->field('a.*,b.d_one_account,b.d_sum_number,b.d_sum_account')->find();
//        $cityid=$panters['cityid'];
//    	if($cityid!=''){
//    		$wmap['i.cityid']=$cityid;
//    		$city=M('city');
//			$citys = $city->alias('c')
//			->join('left join __CITY__ i on i.provinceid=c.provinceid')
//			->where($wmap)->field('c.provinceid,c.cityid,c.cityname')->select();
//			$this->assign('city',$citys);
//
//            $wcity=array();
//            $wcity['cityid']=$cityid;
//            $cityk = $city->where($wcity)->find();
//            $provinceid=$cityk['provinceid'];
//            $this->assign('provinceid',$provinceid);
//		}
//        $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
//        $this->assign('hysxs',$hysxs);
//        //查询所属机构  start
//        $parentwhere['flag']='1';
//				$parentwhere['revorkflg']='N';
//        $parents = M('panters')->where($parentwhere)->select();
//        $this->assign('parents',$parents);
//        //================end===============//
//		$this->assign('panters',$panters);
//        $pantergroup=M('pantergroup');
//        $pantergroups= $pantergroup->order('groupid asc')->select();
//        $this->assign('pantergroups',$pantergroups);
//    	$this->display();
//    }
    //商户结算管理
	function balance(){
        $panters=M('panters');
        $panterid =trim(I('get.panterid',''));
        $pname  = trim(I('get.pname',''));
        $sname  = trim(I('get.sname',''));
        $panterid = $panterid=="商户编号"?"":$panterid;
        $pname = $pname=="商户名称"?"":$pname;
        $sname = $sname=="结算名称"?"":$sname;
		$where['revorkflg']='N';
        if($panterid!=''){
            $where['panterid']=$panterid;
            $this->assign('panterid',$panterid);
            $map['panterid']=$panterid;
        }
        //$where['_string']=" panterid not in ('FFFFFFFF','EEEEEEEE')";
        if($pname!=''){
            $where['namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($sname!=''){
            $where['settleaccountname']=array('like','%'.$sname.'%');
            $this->assign('sname',$sname);
            $map['sname']=$sname;
        }
        $where['flag']='3';
        $count=$panters->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $panters_list=$panters->where($where)->limit($p->firstRow.','.$p->listRows)->order('panterid desc')->select();
        foreach($panters_list as $key=>$val){
            $fwrate=$val['servicerate'];
            if($fwrate!=''){
                $fuamount=$fwrate;
            }else{
                $fuamount=0;
            }
            $panters_list[$key]['servicerate']=$fuamount;
        }
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$panters_list);
        $this->assign('page',$page);
        $this->display();
    }
    //修改商户结算信息
    function editbalance(){
        $panters=M('panters');
        $city=M('city');
        if(IS_POST){
            $panterid=I('post.panterid','');
            //银行卡号控制16位或者19位数字
            $settlebankid=I('post.settlebankid','');
            $b_flag = I('post.b_flag');
            if($b_flag ==''){
                $this->error("请选择日结算时间");
            }
            $preg = '/^(\d{6,30})$/';
            if(!preg_match($preg,$settlebankid))
            {
              $this->error("银行卡格式不正确");
            }
            if($panterid!=''){
                $wheres['panterid']=$panterid;
            }else{
                $this->error('商户编号不能为空！');
                exit;
            }
            if($panters->create()){
                if($panters->where($wheres)->save($_POST)){
                    $this->success('修改成功！',U('Panters/balance'));
                    exit;
                }else{
                    $this->error('修改失败！');
                    exit;
                }
            }
        }
        $panterid=I('get.panterid','');
        $this->assign('panterid',$panterid);
        if($panterid!=''){
            $where['panterid']=$panterid;
        }
        $panter = $panters->where($where)->find();
        $this->assign('panters',$panter);
        $this->assign('b_flag',$panter['b_flag']);
        $this->display();
    }
	/*
    function delpanters(){
        $panters=M('panters');
        $panterid=trim($_REQUEST['panterid']);
        if(empty($panterid)){
            $this->error('请选择删除的商户');
        }
        $map['panterid']=$panterid;
        if($panters->where($map)->delete()){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
	*/
    //商圈信息管理
    function group(){
        $model=M('__PANTERGROUP__');
        $groupid=trim(I("get.groupid"));
        $groupname=trim(I("get.groupname"));
        $panters=trim(I("get.panters"));
        $namechinese=trim(I("get.namechinese"));
        $hysx=I("get.hysx");
        $groupid = $groupid=="商圈编号"?"":$groupid;
        $groupname = $groupname=="商圈名称"?"":$groupname;
        if(!empty($groupid)){
            $where['pg.groupid']=$groupid;
            $this->assign('groupid',$groupid);
            $map['groupid']=$groupid;
        }
        if(!empty($groupname)){
            $where['pg.groupname']=array('like','%'.$groupname.'%');
            $this->assign('groupname',$groupname);
            $map['groupname']=$groupname;
        }
        $count=$model->table('__PANTERGROUP__')->alias('pg')
            ->where($where)
            ->count();
        $p=new \Think\Page($count, 10 );
        $list=$model->table('__PANTERGROUP__')->alias('pg')
            ->where($where)->order('pg.groupid asc')
            ->limit($p->firstRow.','.$p->listRows)->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page=$p->show();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }
    //新增商圈
    function addgroup(){
        $model=M('pantergroup');
        if(IS_POST){
            $groupname=trim($_POST['groupname']);
            if($groupname==''){
                $this->error('商圈名称不能为空',U('Panters/addgroup'));
            }
            $id=$this->getnextcode('pantergroup',8);
            $sql="insert into pantergroup(groupid,groupname) values('".$id."','".$groupname."')";
            if($model->execute($sql)){
                $model->commit();
                $this->success('商圈添加成功',U('Panters/group'));
            }else{
                $model->rollback();
                $this->error('商圈添加失败');
            }
        }else{
            $this->display();
        }
    }
    //修改商圈信息
    function editgroup(){
        $model=M('pantergroup');
        if(IS_POST){
            $groupid=I('post.groupid');
            if($groupid!=''){
                $wheres['groupid']=$groupid;
            }else{
                $this->error('商圈编号不能为空！');
            }
            if($model->create()){
                $_POST['groupid']=trim($_POST['groupid']);
                $_POST['groupname']=trim($_POST['groupname']);
                if($model->where($wheres)->save($_POST)){
                    $this->success('修改成功！',U('Panters/group'));
                }else{
                    $this->error('修改失败！',U('Panters/editgroup'));
                }
            }
        }else{
            $groupid=I('get.groupid');
            if($groupid!=''){
                $where['groupid']=$groupid;
            }
            $list=$model->where($where)->select();
            $this->assign('list',$list[0]);
            $this->display();
        }
    }
    //终端信息管理
    function terminal(){
        $model=new Model();
        $namechinese=trim(I('get.namechinese'));
        $conpername=trim(I('get.conpername'));
        $panterid = trim(I('get.panterid',''));
        $namechinese = $namechinese=="商户名称"?"":$namechinese;
        $conpername = $conpername=="联系人"?"":$conpername;
        $panterid = $panterid=="商户编号"?"":$panterid;
        if(IS_POST){//修改
            $terminal=M('__PANTER_TERMINALS__');
            $terminal_id=I('post.terminal_id','');
            $panterid=I('post.panterid','');
            $description=I('post.description','');
            $ip=I('post.ip','');
            $flag=I('post.flag','');
            if($panterid==''){
                $this->error('商户号不能为空!');
            }else{
                if($flag==''){
                    $this->error('终端标识符不能为空!');
                }
                $re=$terminal->execute("update __PANTER_TERMINALS__ set flag='".$flag."',ip='".$ip."',description='".$description."',panterid='".$panterid."' where terminal_id='".$terminal_id."' ");
                if($re){
                    $this->success('更新成功',U('Panters/terminal'));
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
            $where['p.revorkflg']='N';
            $field="p.panterid,p.namechinese,p.nameenglish,p.conpername,p.conperteleno";
            $count=$model->table('__PANTERS__')->alias('p')
                ->field($field)->where($where)->count();
            $p=new \Think\Page($count, 10 );
            $list=$model->table('__PANTERS__')->alias('p')
                ->field($field)->where($where)
                ->limit($p->firstRow.','.$p->listRows)->select();

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

    function panterTerminal(){
        $panterid=trim($_REQUEST['panterid']);
        if(empty($panterid)){
            $this->error('商户编号缺失');
        }
        $where['panterid']=$panterid;
        $model=M('panter_terminals');
        $count = $model->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $list=$model->where($where)->order('terminal_id desc')
                      ->limit($p->firstRow.','.$p->listRows)
                      ->select();
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('panterid',$panterid);
        $this->assign('list',$list);
        $this->display();
    }
	
	//--------5-29-添加开始---------------------------
	
	//生成二维码
    function outsideCode(){
        $model=M('panter_terminals'); 
        $terminalid=I('get.terminalid','');
        $panterid=I('get.panterid','');
        if($terminalid==''){
            $this->error('终端编号不能为空',U('Panters/terminal'));
        }
        if($panterid==''){
            $this->error('商户编号不能为空',U('Panters/terminal'));
        }

        $where['panterid']=$panterid;
        $where['terminalid']=$terminalid;
        $panterdata=$model->where($where)->find();
        if(empty($panterdata)){
            $this->error('商户信息未查询到',U('Panters/terminal'));
        }
        $ip=$panterdata['ip'];
		
		//如果商户的信息不存在则添加
		$panters=M('panters');
		$panterdata=$panters->where(array('panterid'=>$panterid))->field("nameenglish")->find();
        $nameenglish=$panterdata['nameenglish'];//商户名称简称
        $this->outSideMerchant($ip,$terminalid,$panterid,$nameenglish);
		
        $parterarr=array(
            'ip'=>$ip,
            'terminalid'=>$terminalid,
            'panterid'=>$panterid,
        );
        $string=json_encode($parterarr);
        $enparameter=$this->encode($string);
        $content=$this->codeUrl.'app=wxhousehold&act=ScanData&param='.$enparameter."&_noconfirm_=1";
        $ErweimaName=$this->getparameter($content,$terminalid);
        $dir_path=$ErweimaName['pathfinish'];
        $fileName=$ErweimaName['finishName'];
        $this->download($dir_path,$fileName);
    }
    //版本二维码下载
    function download($dir_path,$fileName)
    {
        // 下载二维码
        $contenttype = 'image/jpeg';
        $fileurl = $dir_path.$fileName;
        header("Cache-control: private");
        header("Content-type: $contenttype"); //设置要下载的文件类型
        header("Content-Length:" . filesize($fileurl)); //设置要下载文件的文件大小
        header("Content-Disposition: attachment; filename=" . urldecode($fileName)); //设置要下载文件的文件名
        readfile($fileurl);
    }

    /**
     * 函数用途描述
     * @date: 2017年6月14日 下午3:56:50
     * @author: Administrator
     * @param: variable
     * @return:加密解密
     */
    function encode($string){
        $string=base64_encode($string);
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), $string);
    }
    function decode($string){
        $string=str_replace(array('O0O0O', 'o000o', 'oo00o'),array('=', '+', '/'), $string);
        return base64_decode($string);
    }
    /**
     * 函数用途描述
     * @date: 2017年7月1日 上午9:26:54
     * @author: Administrator
     * @param: variable
     * @return:拼接参数加密
     */
    function getparameter($content,$terminalid){
        include_once 'ImgController.class.php';

        $value =$content; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 8;//生成图片大小
        //生成二维码图片
        $month=date('Ym',time());
        $path=PUBLIC_PATH.'balance/getIp/';//原始二维码图 名称路径
        $filename=$path.$terminalid.".png";
        if(!file_exists($path)){
            mkdir($path,0777,true);
        }
        $objectcode = new QRcode();
        $objectcode->png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

        $logo =  PUBLIC_PATH.'balance/logo.jpg';//需要显示在二维码中的Logo图像
        $QR = $filename;
        if ($logo !== FALSE) {
            $QR = imagecreatefromstring ( file_get_contents ( $QR ) );
            $logo = imagecreatefromstring ( file_get_contents ( $logo ) );
            $QR_width = imagesx ( $QR );
            $QR_height = imagesy ( $QR );
            $logo_width = imagesx ( $logo );
            $logo_height = imagesy ( $logo );
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            imagecopyresampled ( $QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height );
        }
        imagepng ( $QR, 'logo.jpg');//带Logo二维码的文件名
        //输出图片
        $pathfinish=PUBLIC_PATH.'balance/getIp/finish/';//原始二维码图 名称路径
        $finishName="finish".$terminalid.".png";
        $filenamefinish=$pathfinish.$finishName;

        if(!file_exists($pathfinish)){
            mkdir($pathfinish,0777,true);
        }
        imagepng($QR, $filenamefinish);
        $res=array('pathfinish'=>$pathfinish,'finishName'=>$finishName);
        return $res;
    }
 
	//----------------------添加结束-------------------------
    //添加终端号
    function addterminal(){
        $model=M('panter_terminals');
        if(IS_POST){
            $panterid=I('post.panterid','');
            if($panterid==''){
                $this->error('商户号不能为空',U('Panters/addterminal'));
            }
            $ip=$_POST['ip'];
            $flag=$_POST['flag'];
            $description=$_POST['description'];
            $id=$this->getnextcode('terminals',8);
            $terminalid=substr($panterid,5,3).substr($id,3,5);
            $sql="insert into panter_terminals(terminal_id,panterid,ip,flag,description) values('".$terminalid."','".$panterid."','".$ip."','".$flag."','".$description."')";
            if($model->execute($sql)){
                $model->commit();
				//--------------添加开始---5-29------------
				$panters=M('panters');
                $panterdata=$panters->where(array('panterid'=>$panterid))->field("nameenglish")->find();
                $nameenglish=$panterdata['nameenglish'];//商户名称简称
                $this->outSideMerchant($ip,$terminalid,$panterid,$nameenglish);
				//--------------添加结束---5-29------------
				
                $this->success('终端添加成功',U('Panters/panterTerminal', array('panterid' =>$panterid)));
            }else{
                $model->rollback();
                $this->error('终端添加失败');
            }
        }else{
          $panterid=I('get.panterid','');
          $ip=$_SERVER['REMOTE_ADDR'];
          $this->assign('panterid',$panterid);
          $this->assign('ip',$ip);
          $this->display();
        }

    } 
	
	//--------5-29-添加开始---------------------------
	
	 /*
     *外拓添加商户
     */
    function outSideMerchant($ip,$terminalid,$panterid,$merchant){
        $addsideUrl=$this->addsideUrl;
        $key=$this->outsidekey;
        $sign=md5($key.$ip.$terminalid.$panterid.$merchant);
        $data['ip']=$ip;
        $data['terminalid']=$terminalid;
        $data['panterid']=$panterid;
        $data['merchant']=$merchant;
        $data['sign']=$sign;
        $jsondata=json_encode($data);
        $menddata=$this->encode($jsondata);
        $ourUrl=$addsideUrl."app=wxoutside&act=addOutSide";
        $setdata['encrypt']=$menddata;
        $curldata=$this->curlUploadImg($setdata,$ourUrl);
        $res="提交的数据".json_encode($data)."返回的信息".json_encode($curldata); 
        $this->writeLogs("outSideMerchant",$res);
    }
    /*
     *日志
     */
    function writeLogs($module,$data){
        $jsonData=$data;
        $logPath=PUBLIC_PATH.'balance/logs/'.$module."/";
        $month=date('Ym',time());
        $msgString = date('H:i:s',time()).$_SERVER["REMOTE_ADDR"].$jsonData."\t\n";
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents( $filename,$msgString,FILE_APPEND);
    }
	
	//--------5-29-添加结束---------------------------
	
    function editterminal(){
        $model=M('panter_terminals');
        $terminalid=I('get.terminalid','');
        if($terminalid!=''){
            $where['a.terminal_id']=$terminalid;
        }else{
            $this->error('终端编号不能为空',U('Panters/terminal'));
        }
        $pantert=$model->alias('a')->join('left join panters b on b.panterid=a.panterid')->where($where)->field('a.*,b.namechinese,b.nameenglish,b.conpername,b.conperteleno')->find();
        $this->assign('pantert',$pantert);
        $this->display();
    }
    //ajax获取终端
    function getterminal(){
        $model=M('panter_terminals');
        $panterid=I('post.panterid','');
        if($panterid!=''){
            $where['panterid']=$panterid;
        }
        $list=$model->where($where)->order('terminal_id asc')->select();
        if($list){
            $res['data'] =$list;
            $res['success'] = 1;
        }else{
            $res['msg']='商户编号不能为空!';
        }
        echo json_encode($res);
    }
    //获得省市县
	public function getCity(){
		$model = new Model();
		$map['provinceid']=$_REQUEST["pid"];
		//$map['type']=$_REQUEST["type"];
		$list=$model->table('__CITY__')->where($map)->select();
		echo json_encode($list);
	}

    //商户日结处理程序
    public function panterTradeDaily(){
        if(IS_POST){
            set_time_limit(0);
            ini_set ('memory_limit', '128M');
            $start = I('post.startdate','');
            $end = I('post.enddate','');
            $model=new Model();
            $where['tw.dayflag']=array('EXP','IS NULL');
            if($start!='' && $end==''){
                $startdate = str_replace('-','',$start);
                $where['tw.placeddate']=array('egt',$startdate);
            }
            if($start=='' && $end!=''){
                $enddate = str_replace('-','',$end);
                $where['tw.placeddate'] = array('elt',$enddate);
            }
            if($start!='' && $end!=''){
                $startdate = str_replace('-','',$start);
                $enddate = str_replace('-','',$end);
                $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
            }
            if($start==''&&$end==''){
                $where['tw.placeddate']=date('Ymd',time());
            }
            //$where['tw.placeddate']=date('Ymd',time());
            //$where['tw.flag']=array('neq',1);
            $where['tw.flag']=0;
            $where['tw.tradetype']=array('in','00,21,07');
            $where['c.cardkind']=array('not in',array('6882','2081','6880','6997'));
			//$where['p.panterid']=array('not in',array('00000286','00000290'));
            $where['tw.tradepoint']=0;
//            $c=$model->table('trade_wastebooks')->alias('tw')
//                ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
//                ->where($where)->field('tw.*,p.rakerate')->count();
//            echo $model->getLastSql();exit;
            $unbalancedTrade=$model->table('trade_wastebooks')->alias('tw')
                ->join('left join __PANTERS__ p on p.panterid=tw.panterid')
				->join('left join __CARDS__ c on c.cardno=tw.cardno')
                ->where($where)->field('tw.cardno,tw.panterid,tw.termposno,tw.placeddate,tw.tradeid,tw.tradeamount,tw.tradepoint,p.rakerate')
                ->limit(0,5000)->select();
            if($unbalancedTrade==false){
                $this->error('没有未结算交易',U("Panters/panterTradeDaily"));
            }else{
                foreach($unbalancedTrade as $key=>$val){
                    $map=array('panterid'=>$val['panterid'],'termposno'=>$val['termposno'],'statdate'=>$val['placeddate']);
                    $panterTrade=M('trade_panter_day_books')->field('tradeamount,tradequantity,retailamount,proxyamount')->where($map)->find();
                    $model->startTrans();
//                    if(substr($val['cardno'],0,4)!='6688'){
//                        if(!empty($val['rakerate'])&&$val['rakerate']!==0){
//                            $jsamount=floatval(round($val['tradeamount']*$val['rakerate']/100,2));
//                            $sxamount=floatval($val['tradeamount']-$jsamount);
//                        }else{
//                            $jsamount=$val['tradeamount'];
//                            $sxamount=0;
//                        }
//                    }else{
//                        $jsamount= floatval(round($val['tradeamount']*98.4/100,2));
//                        $sxamount=floatval($val['tradeamount']-$jsamount);
//                    }
                    if(!empty($val['rakerate'])&&$val['rakerate']!==0){
                        $jsamount=floatval(round($val['tradeamount']*$val['rakerate']/100,2));
                        $sxamount=floatval($val['tradeamount']-$jsamount);
                    }else{
                        $jsamount=$val['tradeamount'];
                        $sxamount=0;
                    }
                    //echo $jsamount.'--'.$sxamount.'--'.$val['tradeid'].'<br/>';
                    if($panterTrade!=false){
                        $tradeamount=$panterTrade['tradeamount']+$val['tradeamount'];
                        $tradequantity=$panterTrade['tradequantity']+1;
                        $retailamount=$panterTrade['retailamount']+$jsamount;
                        $proxyamount=$panterTrade['proxyamount']+$sxamount;
                        $data=array('tradeamount'=>$tradeamount,'tradequantity'=>$tradequantity,'retailamount'=>$retailamount,'proxyamount'=>$proxyamount);
                        $model->table('trade_panter_day_books')->where($map)->save($data);
                    }else{
                        $sql='insert into trade_panter_day_books(panterid,statdate,termposno,tradeamount,tradequantity,rakerate,retailamount,proxyamount)';
                        $sql.=" values('{$val['panterid']}','{$val['placeddate']}','{$val['termposno']}',{$val['tradeamount']},";
                        $sql.="1,{$val['rakerate']},'{$jsamount}','{$sxamount}')";
                        $model->execute($sql);
                    }
                    //echo $model->getLastSql().'<br/>';
                    $data1['dayflag']='Y';
                    $map1['tradeid']=$val['tradeid'];
                    if($model->table('trade_wastebooks')->where($map1)->save($data1)){
                        $model->commit();
                    }
                }
                //$this->success('结算完毕');
            }
        }else{
            $this->display();
        }
    }

    public function pantergroupSet(){
        if(IS_POST){
            $panterid=trim($_POST['panterid']);
            $pantergroup=trim($_POST['pantergroup']);
            if(empty($panterid)){
                $this->error('商户id缺失');
            }
            if(empty($pantergroup)){
                $this->error('请选择商圈');
            }
            $data['pantergroup']=$pantergroup;
            $map['panterid']=$panterid;
            if(M('panters')->where($map)->save($data)){
                $this->success('商圈设置成功');
            }else{
                $this->error('设置失败');
            }
        }else{
            $panterid=trim($_REQUEST['panterid']);
            if(empty($panterid)){
                $this->error('商户id缺失');
            }
            $where['panterid']=$panterid;
            $panter=M('panters')->field('panterid,namechinese,pantergroup')->where($where)->find();
            if($panter==false){
                $this->error('查无此商户');
            }
            $pantergroup=M('pantergroup')->select();
            $this->assign('panter',$panter);
            $this->assign('pantergroup',$pantergroup);
            $this->display();
        }
    }
    //商户添加商户名ajax验证
    public function pantername()
    {
      $panter = M('panters');
      $where['namechinese'] = trim($_POST['namechinese']);
      $data['status']=0;
      $data['name']="";
      if($panter->where($where)->find())
      {
        $data['status'] = 1;
        $data['name'] = "商户名已存在";
      }
      echo json_encode($data);
    }
    //pos
    public function poscontrol()
    {
      $pos = M("zz_pos");
      // $positem = M('zz_item');
      $witems['tongbaoitem'] = 'Y';
      $items = M('panters')->where($witems)->select();
        $item = trim(I('get.item',''));
        $posnum = trim(I('get.posnum',''));
        $posnum = $posnum=="型号"?"":$posnum;
        $imei = trim(I('get.imei',''));
        $pos_id =  trim(I('get.pos_id',''));
        if($item!='')
        {
          $where['item_id']=$item;
          $map['item']=$item;
          $this->assign('item',$item);
        }
        if($posnum!='')
        {
          $where['a.posnum']=$posnum;
          $map['posnum'] = $posnum;
          $this->assign("posnum",$posnum);
        }
        if($pos_id!=''){
            $where['a.pos_id'] = ['like','%'.$pos_id.'%'];
            $this->assign('pos_id',$pos_id);
        }
        $status = trim(I('get.status',''));
        if($status!='')
        {
          if($status=='全部'){
            $where['a.status']=array(in,array('入库','出库','故障','退货'));
          }else  $where['a.status']=$status;
          $map['status']= $status;
          $this->assign('status',$status);
        }else{
          $status='入库';
          $where['a.status']=$status;
          $map['status']= $status;
          $this->assign('status',$status);
        }
        if($imei!=''){
          $where['a.imei']=$imei;
          $map['imei']= $imei;
          $this->assign('imei',$imei);
        }
        session('config_index',$where);
      $field = 'a.aid,a.pos_id,a.posnum,a.type,a.imei,a.status,a.remark,p.namechinese';
      $count = $pos->alias('a')
                   ->JOIN('left join __PANTERS__ p on a.item_id=p.panterid')
                   ->where($where)->count();
      $page = new \Think\Page($count,10);
      $pos_lists = $pos->alias('a')
                   ->JOIN('left join __PANTERS__ p on a.item_id=p.panterid')
                   ->where($where)->order('aid desc')->field($field)->limit($page->firstRow.','.$page->listRows)->select();
      $shows = $page->show();
      $this->assign('page',$shows);
      $this->assign('count',$count);
      $this->assign('items',$items);
      $this->assign("list",$pos_lists);
      $this->display();
    }
    //添加pos
    public function addpos()
    {
      $pos = M("zz_pos");
      // $type= array('O' =>'传统POS','N'=>'智能POS' );
      $postype = array('01' =>'S58' ,'02'=>'S90','03'=>"V1800",'04'=>'P1800');
      $brand = array("01"=>'海科融通',"02"=>'百福',"03"=>"通联","04"=>"银联");
      $this->assign("postype",$postype);
      $this->assign("brand",$brand);
      if(IS_POST)
      {
        $posnum = trim(I('post.posnum',''));
        $type = trim(I('post.type'),'');
        $brand_id = trim(I('post.brand_id',''));
        $imei = trim(I('post.imei',''));
        $status = trim(I('post.status',''));
        $price = trim(I('post.price',''));
        $add_time = trim(I('post.add_time',''));
        $consignee = trim(I('post.consignee',''));
        $pos_id = trim(I('post.pos_id',''));
        $remark = trim(I('post.remark',''));
        if($pos_id=='')
        {
          $this->error("设备ID不能为空!");
        }
        if($posnum=='')
        {
          $this->error("POS型号不能为空!");
        }
        if($brand_id=='')
        {
          $this->error("POS品牌不能为空!");
        }
        if($imei=='')
        {
          $this->error('IMEI不能为空!');
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
          $aid = $this->getnextcode("zz_pos",11);
          $sql = "INSERT INTO zz_pos (aid,type,brand_id,imei,posnum,status,pos_id,consignee,price,add_time，remark) VALUES";
          $sql.="('".$aid."','".$type."','".$brand_id."','".$imei."','".$posnum."','".$status."','".$pos_id."','".$consignee."','".$price."','".$add_time."','".$remark."')";
          if($m->execute($sql))
          {
            $this->success('设备新增成功',U('Panters/poscontrol'));
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
          $aid = $this->getnextcode("zz_pos",11);
          $arr1=fasle;$arr2=false;
          $sql = "INSERT INTO zz_pos (aid,type,brand_id,imei,posnum,status,pos_id,consignee,price,add_time，remark) VALUES";
          $sql.="('".$aid."','".$type."','".$brand_id."','".$imei."','".$posnum."','".$status."','".$pos_id."','".$consignee."','".$price."','".$add_time."','".$remark."')";
          if($pos->execute($sql))
          {
              $arr1 = true;
              $time = time();
              $sql = "INSERT INTO zz_change (aid,posnum,status,time) VALUES" ;
              $sql.="('".$aid."','".$posnum."','".$status."','".$time."')";
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
            $this->success('入库成功!',U('Panters/poscontrol'));
          }
        }
      }
      $this->display();
    }
    public function editpos()
    {
      $postype = array('01' =>'S58' ,'02'=>'S90','03'=>"V1800",'04'=>'P1800');
      $brand = array("01"=>'海科融通',"02"=>'百福',"03"=>"通联","04"=>"银联");
      $pos = M("zz_pos");
      $aid = trim(I('get.aid',''));
      $where['aid']=$aid;
      $posid = $pos->where($where)->find();
      if(IS_POST)
      {
        $posnum = trim(I('post.posnum',''));
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
        if($pos_id=='')
        {
          $this->error("设备ID不能为空!");
        }
        if($posnum=='')
        {
          $this->error("POS型号不能为空!");
        }
        if($brand_id=='')
        {
          $this->error("POS品牌不能为空!");
        }
        if($imei=='')
        {
          $this->error('IMEI不能为空!');
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
      $this->assign("brand",$brand);
      $this->assign("postype",$postype);
      $this->assign("posid",$posid);
      $this->display();

    }
    //posID 添加ajax 验证唯一性
    public function posajaxid()
    {
      $pos=M('zz_pos');
      $where['pos_id']=trim(I('post.pos_id',''));
      $arr = array('status'=>0,'name'=>'');
      if($pos->where($where)->find())
      {
        $arr['status'] = 1;
        $arr['name'] = "设备ID已存在,请更换!";
      }
      echo json_encode($arr);
    }
  //posID 编辑 ajax验证唯一性
  public function poseditid()
  {
    $pos=M('zz_pos');
    $where['pos_id']=trim(I('post.pos_id',''));
    $aid = trim(I('post.aid'));
    $where['aid'] = array('neq',$aid );
    $arr = array('status'=>0,'name'=>'');
    if($pos->where($where)->find())
    {
      $arr['status'] = 1;
      $arr['name'] = "设备ID已存在,请更换!";
    }
    echo json_encode($arr);
  }
  //配置POS主页面银行卡pos页面展示
  public function configpos()
  {
    $pos = M('zz_pos');
    $config = M('zz_config');
    $m = M('Zz_channel');
    $channeltype = $m->select();
    $aid = trim(I('get.aid',''));
    // $pos_id = trim(I('get.pos_id',''));
    $where['aid'] = $aid;
    $where['status'] = '1';
    $where['payid'] = '01';
    if($res=$config->where($where)->find())
    {
        if($res['tb_rate']==null){
            $res['tb_rate']='';
        }else{
            $res['tb_rate']=floatval($res['tb_rate']);
        }
      $this->assign('res',$res);
    }
    $this->assign('pos_id',$pos_id);
    $this->assign('aid',$aid);
    $this->assign('channel',$channeltype);
    $this->assign('sendMethod',$this->sendMethod);
    //判断pos status是否为正常，不正常的不让配置参数
    $stat['aid'] = $aid;
    $result = $pos->where($stat)->find();
    if($result['status']=='正常'||$result['status']=='入库')
    {
    }
    else
    {
      $this->error('POS状态不是正常,不可配置参数!',U("Panters/poscontrol"));
      exit;
    }

    $this->display();
  }

  //支付宝/微信POS配置 页面
  public function alipos()
  {
    $m = M('Zz_channel');
    $channeltype =$m->select();
    $pos = M('zz_pos');
    $config = M('zz_config');
    $aid = trim(I('get.aid',''));
    // $pos_id = trim(I('get.pos_id',''));
    $where['aid'] = $aid;
    $where['status'] = '1';
    $where['payid'] = '03';
    if($res=$config->where($where)->find())
    {
    if($res['tb_rate']==null){
           $res['tb_rate']='';
        }else{
            $res['tb_rate']=floatval($res['tb_rate']);
        }
      $this->assign('res',$res);
    }
    $this->assign('pos_id',$pos_id);
    $this->assign('aid',$aid);
    $this->assign('sendMethod',$this->sendMethod);
    $this->assign('channel',$channeltype);
     $this->display();
  }
  //至尊卡POS 配置页面
  public function cardpos()
  {
    $panter = M('panters');
    $pos = M('zz_pos');
    $m = M('Zz_channel');
    $config = M('zz_config');
    //检查设备序号aid
    $aid = trim(I('get.aid',''));
    // $pos_id = trim(I('get.pos_id',''));
    // $channeltype = $m->select();
    $where['aid'] = $aid;
    $where['status'] = '1';
    $where['payid'] = '02';
    if($res=$config->where($where)->find())
    {
       if($res['tb_rate']==null){
           $res['tb_rate']='';
        }else{
            $res['tb_rate']=floatval($res['tb_rate']);
        }
      $this->assign('res',$res);
    }
    // $this->assign('channel',$channeltype);
    $this->assign('pos_id',$pos_id);
    $this->assign('sendMethod',$this->sendMethod);
    $this->assign('aid',$aid);
    $this->display();
  }
  //修改配置页 修改银行卡配置页面
//   public function editcard()
//   {
//     $config = M('zz_config');
//     $pos = M('zz_pos');
//     $aid = trim(I('get.aid',''));
//     $pos_id = trim(I('get.pos_id',''));
//     // $channeltype = array('01'=>'迅联','02'=>'通联','03'=>'慧银','04'=>'快钱','05'=>'华势');
//     $this->assign('channel',$channeltype);
//     $this->assign('aid',$aid);
//     $this->assign('pos_id',$pos_id);
//     //判断pos status是否为正常，不正常的不让配置参数
//     $stat['aid'] = $aid;
//     $result = $pos->where($stat)->find();
//     if($result['status']!='正常')
//     {
//       $this->error('POS状态不是正常,不可配置参数!');
//       exit;
//     }
//     $where['aid'] = $aid;
//     $where['status'] = '1';
//     $where['payid'] = '01';
//     if($res=$config->where($where)->find())
//     {
//       $this->assign('res',$res);
//     }
//     else
//     {
//       $this->error('银行卡配置未添加',U("Panters/configpos?aid=$aid&pos_id=$pos_id"));exit;
//     }
//     $this->display();
//   }
  //POS配置银行卡操作
  public function editcard1()
  {
    $config = M('zz_config');
    $pos = M('zz_pos');
    if(IS_POST)
    {
      $panterid = trim(I('post.panterid',''));
      $namechinese = trim(I('post.namechinese',''));
      $payid = trim(I('post.payid',''));
      $ip_address = trim(I('post.ip_address',''));
      $num_id = trim(I('post.num_id',''));
      $tpdu = trim(I('post.tpdu',''));
      $aid = trim(I('post.aid',''));
      $pos_id = trim(I('post.pos_id',''));
      $channel = trim(I('post.channel',''));
      $forbid = trim(I('post.forbid'));
      $list = $pos->where("aid=$aid")->find();
      $imei = $list['imei'];
      $where['aid'] = $aid;
      $where['status'] = '1';
      $where['payid'] = $payid;
      if($config->where($where)->find())
      {
        $data['panterid'] = trim(I('post.panterid',''));
        $data['namechinese'] = trim(I('post.namechinese',''));
        $data['ip_address'] = trim(I('post.ip_address',''));
        $data['num_id'] = trim(I('post.num_id',''));
        $data['tpdu'] = trim(I('post.tpdu',''));
        $data['channel'] = trim(I('post.channel',''));
        $wheres['payid'] = trim(I('post.payid',''));
        $wheres['aid'] = trim(I('post.aid',''));
        $data['pos_id'] = trim(I('post.pos_id',''));
        $data['forbid'] = trim(I('post.forbid'));
        $data['imei'] =$imei;
        if($config->where($wheres)->save($data))
        {
          $this->success('银行修改配置成功','poscontrol');
        }
        else
        {
          $this->error('银行配置修改失败');
        }
      }
      else
      {     //配置成功才修改status为1 默认为NULL
            $status = 1;
            $sql = "INSERT INTO zz_config (aid,pos_id,panterid,namechinese,ip_address,num_id,tpdu,status,payid,channel,imei,forbid) VALUES";
            $sql.="('".$aid."','".$pos_id."','".$panterid."','".$namechinese."','".$ip_address."','".$num_id."','".$tpdu."','".$status."','".$payid."','".$channel."','".$imei."','".$forbid."')";
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
  //至尊卡pos配置修改操作
  public function editzzcard1()
  {
    $config = M('zz_config');
    $pos = M('zz_pos');
    if(IS_POST)
    {
      $panterid = trim(I('post.panterid',''));
      $namechinese = trim(I('post.namechinese',''));
      $payid = trim(I('post.payid',''));
      $ip_address = trim(I('post.ip_address',''));
      $num_id = trim(I('post.num_id',''));
      $aid = trim(I('post.aid',''));
      $pos_id = trim(I('post.pos_id',''));
      $channel = trim(I('post.channel',''));
      if($panterid!=''){
          $realPanterid = substr($panterid,-8);
      }else $this->error('商户id不能为空！');
      $list = $pos->where("aid=$aid")->find();
      $imei = $list['imei'];
      $forbid = trim(I('post.forbid'));
      $where['aid'] = $aid;
      $where['status'] = '1';
      $where['payid'] = $payid;
      if($config->where($where)->find())
      {
        $data['panterid'] = trim(I('post.panterid',''));
        $data['namechinese'] = trim(I('post.namechinese',''));
        $data['ip_address'] = trim(I('post.ip_address',''));
        $data['num_id'] = trim(I('post.num_id',''));
        $data['channel'] = trim(I('post.channel',''));
        $data['forbid']=trim(I('post.forbid'));
        $data['imei'] = $imei;
        $wheres['payid'] = trim(I('post.payid',''));
        $wheres['aid'] = trim(I('post.aid',''));
        $data['pos_id'] = trim(I('post.pos_id',''));
        if($config->where($wheres)->save($data))
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
        $status = 1;
          $sql = "INSERT INTO zz_config (aid,pos_id,panterid,namechinese,ip_address,num_id,status,payid,channel,imei,forbid) VALUES";
          $sql.="('".$aid."','".$pos_id."','".$panterid."','".$namechinese."','".$ip_address."','".$num_id."','".$status."','".$payid."','".$channel."','".$imei."','".$forbid."')";
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
   public function editali1()
   {
     $config = M('zz_config');
     $pos = M('zz_pos');
     if(IS_POST)
     {
       $panterid = trim(I('post.panterid',''));
       $namechinese = trim(I('post.namechinese',''));
       $payid = trim(I('post.payid',''));
       $ip_address = trim(I('post.ip_address',''));
       $num_id = trim(I('post.num_id',''));
       $aid = trim(I('post.aid',''));
       $pos_id = trim(I('post.pos_id',''));
       $organizationcode = trim(I('post.organizationcode',''));
       $sign = trim(I('post.sign',''));
       $channel = trim(I('post.channel',''));
       $list = $pos->where("aid=$aid")->find();
       $imei = $list['imei'];
       $forbid = trim(I('post.forbid'));
       $where['aid'] = $aid;
       $where['status'] = '1';
       $where['payid'] = $payid;
       if($config->where($where)->find())
       {
         $data['panterid'] = trim(I('post.panterid',''));
         $data['namechinese'] = trim(I('post.namechinese',''));
         $data['ip_address'] = trim(I('post.ip_address',''));
         $data['num_id'] = trim(I('post.num_id',''));
         $data['channel'] = trim(I('post.channel',''));
         $data['organizationcode'] = trim(I('post.organizationcode',''));
         $data['sign'] = trim(I('post.sign',''));
         $data['forbid']=trim(I('post.forbid'));
         $wheres['payid'] = trim(I('post.payid',''));
         $wheres['aid'] = trim(I('post.aid',''));
         $data['pos_id'] = trim(I('post.pos_id',''));
         $data['imei'] = $imei;
         if($config->where($wheres)->save($data))
         {
           $this->success('支付宝/微信修改配置成功','poscontrol');
         }
         else
         {
           $this->error('支付宝/微信配置修改失败');
         }
       }
       else
       {
         $status = 1;
           $sql = "INSERT INTO zz_config (aid,pos_id,panterid,namechinese,ip_address,num_id,status,payid,organizationcode,sign,channel,imei,forbid) VALUES";
           $sql.="('".$aid."','".$pos_id."','".$panterid."','".$namechinese."','".$ip_address."','".$num_id."','".$status."','".$payid."','".$organizationcode."','".$sign."','".$channel."','".$imei."','".$forbid."')";
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
   // pos通道管理
   public function channel()
   {
     $m = M('Zz_channel');
     if(IS_POST)
     {
       $channel_id = trim(I('post.channel_id',''));
       $channel =trim(I('post.channel',''));
       if($channel_id!='')
       {
         $where['channel_id'] = $channel_id;
         $map['channel_id'] = $channel_id;
         $this->assign('channel_id',$channel_id);
       }
       if($channel!='')
       {
         $where['channel'] = $channel;
         $map['channel'] = $channel;
         $this->assign('channel',$channel);
       }
     }
    $count = $m->where($where)->count();
     $p=new \Think\Page($count, 10);
     $lists=$m->where($where)->limit($p->firstRow.','.$p->listRows)->select();

     if(!empty($map)){
         foreach($map as $key=>$val) {
             $p->parameter[$key]= $val;
         }
     }
     $page = $p->show();
     $this->assign('count',$count);
     $this->assign('page',$page);
     $this->assign('lists',$lists);
     $this->display();
   }
   public function addchannel()
   {
     $m = M('Zz_channel');
     if(IS_POST)
     {
       $channel_id = $this->getnextcode("zz_channel",6);
       $channel = trim(I('channel',''));
       if($channel=='')
       {
         $this->error('通道名不能为空!');
       }
       else
       {
        $sql = "INSERT INTO zz_channel(channel_id,channel)VALUES";
        $sql.="('".$channel_id."','".$channel."')";
        $bool = $m->execute($sql);
        if($bool)
        {
          $this->success('通道添加成功','channel');
        }
        else
        {
          $this->error('通道添加失败');
        }
       }
     }

     $this->display();
   }
   public function editchannel()
   {
     $m = M('Zz_channel');
     $channel_id = trim(I('get.channel_id',''));
     $where['channel_id'] = $channel_id;
     $lists = $m->where("channel_id=$channel_id")->find();
     $this->assign('channel_id',$channel_id);
     $this->assign('lists',$lists);
     $this->display();
   }
   // 编辑通道操作
   public function editchannel1()
   {
      $m = M('Zz_channel');
     if(IS_POST){
       $channel_id = trim(I('post.channel_id',''));
       $channel = trim(I('post.channel',''));
       $wheres['channel_id'] = $channel_id;
       if($channel=='')
       {
         $this->error('通道不能为空');
       }
       else
       {
         $data['channel'] = $channel;
         $bool = $m->where($wheres)->save($data);
         if($bool)
         {
            $this->success('修改通道成功',U("Panters/channel"));

         }else{
           $this->error('修改失败');
         }
       }
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
   //pos入库
   public function inhandle()
   {
     $select = $_POST['select'];
     if(empty($select))
     {
       $this->error('未选pos');
     }
     else
     {
      foreach ($select as $key => $value)
      {
        $aid = $value;
        $m = M('zz_change');
        $pos = M('zz_pos');
        $where['aid'] = $aid;
        $list = $pos ->where($where)->find();
        if($list['status']!='出库')
        {
          $this->error('该pos不是出库状态!');
        }
        $m->startTrans();
        $arr1=false;
        $arr2=false;
        $data['item_id']='';
        $data['status'] = '入库';
        if($pos->where($where)->save($data))
        {
          $arr1=true;
          $time = time();
          $status = '入库';
          $posnum = $list['posnum'];
          $sql = "INSERT INTO zz_change (aid,posnum,status,time) VALUES" ;
          $sql.="('".$aid."','".$posnum."','".$status."','".$time."')";
          $temp=$m->execute($sql);
          if($temp)
          {
            $arr2=true;
          }
          else
          {
            $arr2 = false;
            $m->rollback();
            $this->error('入库失败');

          }
        }
        else
        {
          $arr1 = false;
          $m->rollback();
          $this->error('入库失败!');
        }
        //-----2016.3.24---出库后入库 所有配置开启禁用状态--start//
        $sql1="UPDATE zz_config set forbid='1' where aid='{$aid}' ";
        $arr3 = M('zz_config')->execute($sql1);
        //--------------end-------------------//
        if($arr1 && $arr2 && $arr3)
        {
          $m->commit();
        }
      }
        $this->success('入库成功',U('Panters/poscontrol'));
     }
   }
  //  //入库操作
  //  public function inhandle()
  //  {
  //    $m = M('zz_pos');
  //    if(IS_POST)
  //    {
  //      $aid = trim(I('post.aid',''));
  //      $status = trim(I('post.status',''));
  //      $list = $m->where("aid=$aid")->find();
  //      $posnum = $list['posnum'];
  //      //开启事务
  //      $m->startTrans();
  //      $arr1 =false;
  //      $arr2 =false;
  //      if($status=='入库')
  //      {
  //        $data['status'] = '入库';
  //        $data['input_time'] = date('Ymd',time());
  //        $data['item_id']='';
  //        $bool = $m->where("aid=$aid")->save($data);
  //        if($bool)
  //        {
  //          $arr1 = true;
  //          $time = time();
  //          $sql = "INSERT INTO zz_change (aid,posnum,status,time) VALUES" ;
  //          $sql.="('".$aid."','".$posnum."','".$status."','".$time."')";
  //          $change = M('zz_change');
  //          $temp = $change->execute($sql);
  //          if($temp)
  //          {
  //            $arr2 = true;
  //          }
  //          else
  //          {
  //            $arr2=false;
  //            $m->rollback();
  //            $this->error('入库失败');
  //          }
  //        }
  //        else
  //        {
  //          $arr1 =false;
  //          $m->rollback();
  //          $this->error('入库失败!');
  //          exit;
  //        }
  //        if($arr1 && $arr2)
  //        {
  //          $m->commit();
  //          $this->success('入库成功!',U('Panters/item1',array('aid'=>$aid)));
  //        }
  //      }
  //      else
  //      {
  //        $this->error('非入库状态!',U("Panters/poscontrol"));
  //      }
  //    }
  //  }
   //pos出库页面
  //  public function outitem()
  //  {
  //    $pos = M('zz_pos');
  //    $positem= M('zz_item');
  //    $items = $positem->select();
  //    $lists = $_POST['select'];
  //    if($lists==NUll)
  //    {
  //      $this->error('未选中pos!');
  //    }
  //    foreach ($lists as $key => $value)
  //    {
  //      $aid=$value;
  //      $list = $pos->where("aid=$aid")->find();
  //       if($list['status']!='入库')
  //       {
  //         $this->error('请先入库状态，才能出库!');
  //         exit;
  //       }
  //       //判断是否配置参数
  //       $config = M('zz_config');
  //       $argument = $config->where("aid=$aid")->find();
  //       if($argument)
  //        {
  //            if($argument['status']=='0')
  //            {
  //              $this->error('未配置参数!');
  //            }
  //        }
  //      else
  //      {
  //        $this->error('未配置参数！');
  //      }
  //    }
     public function outitem()
     {
       $pos = M('zz_pos');
      //  $positem= M('zz_item');
      //  $items = $positem->select();
      $panters = M('panters');
      $witems['tongbaoitem'] = 'Y';
      $items = $panters->where($witems)->field('panterid,namechinese')->select();
       $lists = $_POST['select'];
       if($lists==NUll)
       {
         $this->error('未选中pos!');
       }
       foreach ($lists as $key => $value)
       {
         $aid=$value;
         $list = $pos->where("aid=$aid")->find();
          if($list['status']!='入库')
          {
            $this->error('请先入库状态，才能出库!');
            exit;
          }
          //判断是否配置参数
          $config = M('zz_config');
          $argument = $config->where("aid=$aid")->find();
          if($argument)
           {
               if($argument['status']=='0')
               {
                 $this->error('未配置参数!');
               }
           }
         else
         {
           $this->error('未配置参数！');
         }
         //=====2016.3.24所有配置参数状态  都被禁用不能出库=========//
         $wheres['aid']=$aid;
         $wheres['forbid']='0';
         $bool=$config->where($wheres)->select();
         $bool||$this->error('pos配置被禁用,请修改',U('Panters/configpos',array('aid'=>$aid)));
       }
    //
    // $list = $pos->where("aid=$aid")->find();
    //  if($list['status']!='入库')
    //  {
    //    $this->error('请先入库状态，才能出库!');
    //    exit;
    //  }
     //判断是否配置参数
    //  $config = M('zz_config');
    //  $argument = $config->where("aid=$aid")->find();
    //  if($argument)
    // {
    //     if($argument['status']=='0')
    //     {
    //       $this->error('未配置参数!');
    //     }
    // }
    // else
    // {
    //   $this->error('未配置参数！');
    // }
     $this->assign('list',$lists);
     $this->assign('aid',$aid);
     $this->assign('items',$items);
     $this->display();
   }
   //出库操作
   public function outhandle()
   {
     $m = M('zz_pos');
     if(IS_POST)
     {
       $lists = $_POST['select'];
       if($lists==NULL)
       {
         $this->error('未选中对应pos!');
         exit;
       }
      $item = trim(I('post.item',''));
       foreach ($lists as $key => $value){
         $aid = $value;
         $list =$m->where("aid=$aid")->find();
         $posnum = $list['posnum'];
         $status = '出库';
         $data['status'] = $status;
         $data['out_time'] = date('Ymd',time());
         $data['item_id'] = $item;
         $arr1=false;$arr1=false;
         $m->startTrans();
         if($m->where("aid=$aid")->save($data))
         {
           $arr1 = true;
           $time1 = time();
           $sql = "INSERT INTO zz_change (aid,posnum,status,time,item) VALUES" ;
           $sql.="('".$aid."','".$posnum."','".$status."','".$time1."','".$item."')";
           $change = M('zz_change');
           $temp = $change->execute($sql);
           if($temp)
           {
             $arr2=true;
           }
           else
           {
             $m->rollback();
             $this->error('出库失败!');
           }
         }
         else
         {
           $m->rollback();
           $this->error('出库失败');
         }
         //提交
         if($arr1 && $arr2)
         {
           $m->commit();
           $this->success('出库成功',U('Panters/poscontrol'));
         }
       }
     }
   }
   public function positem()
   {
     $positem = M('zz_item');
     if(IS_POST)
     {
       $item_id=trim(I('post.item_id',''));
       $item = trim(I('post.item',''));
       if($item_id!='')
       {
         $where['item_id']=$item_id;
         $map['item_id'] = $item_id;
         $this->assign('item_id',$item_id);
       }
       if($item!='')
       {
         $where['item'] = $item;
         $map['item'] = $item;
         $this->assign('item',$item);
       }
     }
     $count = $positem->where($where)->count();
     $p = new \Think\Page($count,10);
     $lists=$positem->where($where)->order('item_id desc')->limit($p->firstRow.','.$p->listRows)->select();
     if(!empty($map))
     {
       foreach($map as $key=>$val)
       {
       $p->parameter[$key]   =   urlencode($val);
       }
     }
     $show=$p->show();
     $this->assign('show',$show);
     $this->assign('count',$count);
     $this->assign('lists',$lists);
     $this->display();
   }
   public function additem()
   {
     $add = M('zz_item');
     if(IS_POST)
     {
        $item_id = $this->getnextcode("zz_item",6);
        $item = trim(I('post.item',''));
        if($item=='')
        {
          $this->error('项目名不能为空!');
        }
        $sql = "INSERT INTO zz_item (item_id,item)VALUES";
        $sql.="('".$item_id."','".$item."')";
        $bool=$add->execute($sql);
        if($bool)
        {
          $this->success('添加项目成功',U('Panters/positem'));
        }
        else
        {
          $this->error('项目添加失败!');
        }

     }
     $this->display();
   }
   public function edititem()
   {
     $positem = M('zz_item');
     if(IS_POST)
     {
       $item_id = trim(I('post.item_id',''));
       $item = trim(I('post.item',''));
       if($item_id=='')
       {
         $this->error('项目序号不能为空!');
       }
       $where['item_id'] = $item_id;
       if($item=='')
       {
         $this->error('项目名称不能为空!');
       }
       $data['item'] = $item;
       if($positem->where($where)->save($data))
       {
         $this->success('项目编辑成功',U('Panters/positem'));
       }
       else
       {
         $this->error('项目编辑失败！');
       }
     }
     else
     {
       $item_id = trim($_REQUEST['item_id']);
       if(empty($_REQUEST['item_id'])){
         $this->error('异常访问!');
       }
       $where['item_id'] = $item_id;
       $list = $positem->where($where)->find();
       $this->assign('list',$list);
       $this->assign('item_id',$item_id);
       $this->display();
     }
   }

   public function _fzgSync($namechinese='',$panterid='',$cityid='',$conperteleno=''){
		$signKey="KagbKlMZM1NsgPHOBfgja6bXEftBN";
		$info = M('city')->alias('c')
						->join('province p on c.provinceid=p.provinceid')
						->field('c.cityname as cityname,p.provincename as provincename')
						->where("c.cityid={$cityid}")
						->find();
		$sign = md5($namechinese.$panterid.$namechinese.$info['provincename'].$info['cityname'].$conperteleno.$signKey);
		$syncfzinfo = array(
			"pname" => $namechinese,
			"panterid" => $panterid,
			"keyword" => $namechinese,
			"p_provice" => $info['provincename'],
			"p_city" => $info['cityname'],
      "mobile" => $conperteleno,
			"sign"  =>$sign
		);
		$url = $this->url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
		// post数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// post的变量
		curl_setopt($ch, CURLOPT_POSTFIELDS, $syncfzinfo);
		$output = curl_exec($ch);
		curl_close($ch);

		return json_decode($output,true);
   }
   //pos管理导出pos配置报表
  public function config_index(){
    $pos = M('zz_config');
    $where = $_SESSION['config_index'];
    $where['c.status'] = '1';
    $where['c.forbid'] = '0'; // 是否开启禁止
    $field='c.aid,a.status,c.panterid,c.payid,c.pos_id,c.imei,c.namechinese as pname,p.namechinese';
    $lists = $pos->alias('c')
                 ->join('left join zz_pos a on a.imei=c.imei')
                 ->join('left join panters p on p.panterid=a.item_id')
                 ->where($where)->field($field)->order('aid desc')
                 ->select();
    $strlist = '序号,imei号,pos状态,配置位置,商户号,商户名,终端号,所在项目';
    $strlist = iconv('utf-8','gbk',$strlist);
    $strlist.="\n";
    foreach ($lists as $key => $val) {
      $val['status'] = iconv('utf-8','gbk',$val['status']);
      switch ($val['payid']) {
        case '01':  $val['payid']=iconv('utf-8','gbk','银行卡配置');
          break;
        case '02':  $val['payid']=iconv('utf-8','gbk','至尊卡配置');
          break;
        case '03':  $val['payid']=iconv('utf-8','gbk','微信/支付宝');
          break;
         default: $val['payid']=iconv('utf-8','gbk','错误配置');
          break;
      }
      $val['pname']=iconv('utf-8','gbk',$val['pname']);
      $val['namechinese']=iconv('utf-8','gbk',$val['namechinese']);
      $strlist.=$val['aid']."\t,".$val['imei']."\t,".$val['status'].",".$val['payid'].','.$val['panterid']."\t,".$val['pname'];
      $strlist.=','.$val['pos_id']."\t,".$val['namechinese']."\n";
    }
    $filename ='pos配置报表'.date('YmdHis',time());
    $filename=iconv('utf-8','gbk',$filename);
    $this->load_csv($strlist,$filename);
   }
	 function get_extension($file)
   {
     return substr(strrchr($file, '.'), 1);
    }

    public function PointConfig(){
        $panterid=trim(I('post.panterid',''));
        $pname=trim(I('post.pname',''));
        if(!empty($panterid)){
            $where['pc.panterid']=array('like','%'.$panterid.'%');
            $this->assign('panterid',$panterid);
        }
        if(!empty($pname)){
            $where['p.namechinese']=$pname;
            $this->assign('pname',$pname);
        }
        $list=M('point_config')->alias('pc')->join('panters p on p.panterid=pc.panterid')
            ->field('pc.*,p.namechinese pname')->where($where)->select();
        $this->assign('list',$list);
        $this->display();
    }
    public function addPointConfig(){
        if(IS_POST){
            $panterid=trim(I('post.panterid',''));
            $zsrate=trim(I('post.zsrate',''));
            $validity=trim(I('post.validity',''));
            $xfrate=trim(I('post.xfrate',''));
            $type=trim(I('post.type',''));
            if(empty($panterid)){
                $this->error('商户必填');
            }
            if(empty($zsrate)){
                $this->error('赠送比例必填');
            }
            if(empty($validity)){
                $this->error('有效期必填');
            }
            if(empty($xfrate)){
                $this->error('消费比例必填');
            }
            if(empty($type)){
                $this->error('积分类型必选');
            }
            $point_congig=M('point_config');
            $map=array('panterid'=>$panterid);
            $list=$point_congig->where($map)->find();
            if($list!=false){
                $this->error('该商户已添加配置');
            }
            $sql="INSERT INTO POINT_CONFIG VALUES('{$panterid}','{$zsrate}','{$validity}','{$xfrate}','{$type}')";
            if($point_congig->execute($sql)){
                $this->error('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }
    public function editPointConfig(){
        if(IS_POST){
            //print_r($_POST);exit;
            $panterid=trim(I('post.panterid',''));
            $zsrate=trim(I('post.zsrate',''));
            $validity=trim(I('post.validity',''));
            $xfrate=trim(I('post.xfrate',''));
            $type=trim(I('post.type',''));
            if(empty($panterid)){
                $this->error('商户必填');
            }
            if(empty($zsrate)){
                $this->error('赠送比例必填');
            }
            if(empty($validity)){
                $this->error('有效期必填');
            }
            if(empty($xfrate)){
                $this->error('消费比例必填');
            }
            $point_config=M('point_config');
            $data=array('xfrate'=>$xfrate,'zsrate'=>$zsrate,'validity'=>$validity);
            $map=array('panterid'=>$panterid);
            if($point_config->where($map)->save($data)){
                $this->success('配置修改成功');
            }else{
                $this->success('配置修改失败');
            }
        }else{
            $panterid=trim($_REQUEST['panterid']);
            if(empty($panterid)){
                $this->error('商户缺失');
            }
            $where['pc.panterid']=$panterid;
            $list=M('point_config')->alias('pc')->join('panters p on p.panterid=pc.panterid')
                ->field('pc.*,p.namechinese pname')->where($where)->find();
            $list['zsrate']=floatval($list['zsrate']);
            $list['xfrate']=floatval($list['xfrate']);
            $this->assign('list',$list);
            $this->display();
        }
    }
    //查询商户赠送tb还是积分
    private function sendMethod($panterid){
       $res = M('panters')->where(array('panterid'=>$panterid,'revorkflg'=>'N'))->find();
       if($res==true){
         return $res;
       }else{
         $str = '商户号：'.$panterid."查询赠送方式失败   ".date('Y-m-d H:i:s')."\n";
         $path = PUBLIC_PATH.'logs/file/sendMethod.txt';
         file_put_contents($path,$str,FILE_APPEND);
       }
    }
    //查询银行卡，微信支付宝赠送tb还是积分
    private function otherMethod($aid,$payid='02'){
        $res=M('zz_config')->where(array('aid'=>$aid,'payid'=>$payid))->find();
        if($res==true){
            $panterid=$res['panterid'];
            $realPanterid = substr($panterid,-8);
            $data=$this->sendMethod($realPanterid);
            if($data==true){
                return $data;
            }


        }
    }
    public function jsondata(){
        $name=$_GET['name'];
        $arrdata=array();
        $config=M('zz_config');
        $terminal=M('__PANTER_TERMINALS__');
        $configdata=$config->distinct(true)->where(array('panterid'=>$name))->getField('pos_id',true);
        if(!empty($configdata)){
            $subname = substr($name,7,8);
            $dd= $terminal->query("select * from __PANTER_TERMINALS__ where panterid={$subname}");
            foreach($dd as $vv){
                $arrdata[]=$vv['terminal_id'];
            }
            $result=array_diff($arrdata,$configdata);
            echo  json_encode($result);
            exit;
        }else{
            $subname = substr($name,7,8);
            $dd= $terminal->query("select * from __PANTER_TERMINALS__ where panterid={$subname}");
            foreach($dd as $vv){
                $arrdata[]=$vv['terminal_id'];
            }
            $result=$arrdata;
            echo  json_encode($result);
            exit;
        }
        // echo  json_encode($name);
    }

    public function pantercheck(){
        $panterid=trim($_REQUEST['panterid']);
        if(empty($panterid)){
            $this->error('商户编号缺失');
        }
        $panters=M('panters');
        $map=array('panterid'=>$panterid);
        $panter=$panters->where($map)->field('panterid,namechinese')->find();
        if($panter==false){
            $this->error('商户不存在');
        }
        $sql="update panters set revorkflg='N' where panterid='{$panterid}'";
        if($panters->execute($sql)){
            $this->success('审核成功');
        }else{
            $this->error('审核失败');
        }
    }
    /*
     * 商户新增时 需要审核
     *
     */
    public function verify(){
        $start=trim(I('get.startdate',''));
        $end=trim(I('get.enddate',''));
        $pname=trim(I('get.pname',''));
        $nameenglish=trim(I('get.nameenglish',''));
        if($start!='' && $end==''){
            $startdate = str_replace('-','',$start);
            $where['placeddate']=array('egt',$startdate);
        }
        if($start=='' && $end!=''){
            $enddate = str_replace('-','',$end);
            $where['placeddate'] = array('elt',$enddate);
        }
        if($start!='' && $end!=''){
            $startdate = str_replace('-','',$start);
            $enddate = str_replace('-','',$end);
            $where['placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        }
        if($pname!=''){
            $where['namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
        }
        if($nameenglish!=''){
            $where['nameenglish']=array('like','%'.$nameenglish.'%');
            $this->assign('nameenglish',$nameenglish);
        }
        $where['status']=0;
        $count=M('panters')->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $panters=M('panters')->where($where)->limit($p->firstRow,$p->listRows)->select();

        foreach ($panters as $key =>$val){
        	$val['conperbpno'] = $this->decodePanterConperbpno($val['conperbpno'],$val['panterid']);
	        $panters[$key] = $val;
        }
        $page=$p->show();
        $this->assign('panters',$panters);
        $this->assign('page',$page);
        $this->assign('startdate',$start);
        $this->assign('startdate',$end);
        $this->display();
    }
    /*商户审核时查看详情功能
     *
     */
    public function verify_detail(){
        $panterid=trim(I('get.panterid',''));
        $panterid==true||$this->error('非法参数传入');
        $list=M('panters')->where(['panterid'=>$panterid])->find();
        $this->assign('panters',$list);
        $this->display();
    }
    /*
     * 商户审核 通过 或者拒绝
     */
    public function apply(){
        $panterid=trim(I('get.panterid',''));
        $panterid==true||$this->error('非法参数传入');
        $list=M('panters')->where(['panterid'=>$panterid])->find();
        isset($list)==true||$this->error('非法商户号');
        $type=trim(I('get.type',''));
        if($type=='1'){
            $bool=M('panters')->where(['panterid'=>$panterid])->save(['status'=>$type,'revorkflg'=>'N']);
            if($bool==true) $this->success('商户审核通过成功',U('verify'));
                else  $this->success('商户审核通过失败');
        }elseif($type=='2'){
            $bool=M('panters')->where(['panterid'=>$panterid])->save(['status'=>$type]);
            if($bool==true) $this->success('商户审核拒绝成功',U('verify'));
            else  $this->success('商户审核拒绝失败');
        }

    }


    /*
     * 商户身份证号处理
     * encodePanterConperbpno
     * decodePanterConperbpno
     */

    private function encodePanterConperbpno($conperbpno,$panterid){
    	$digital = new Digital();

	    if($panterid=='00000126'){
		    $figure = substr($conperbpno,1);

		    $encode = substr($conperbpno,0,1).$digital->encode($figure);
	    }else{
		    $encode = $digital->encode($conperbpno);
	    }
	    return $encode;
    }

    private function decodePanterConperbpno($conperbpno,$panterid){
	    $digital = new Digital();
	    if($panterid=='00000126'){
		    $figure = substr($conperbpno,1);

		    $decode = substr($conperbpno,0,1).$digital->decode($figure);
	    }else{
		    $decode = $digital->decode($conperbpno);
	    }
	    return $decode;
    }


     /**
     * 门店列表
     */
    public function store()
    {
        $zzk_store = M('zzk_store');
        $panterid = trim(I('get.panterid', ''));
        $name = trim(I('get.name', ''));
        // $pname = trim(I('get.pname', ''));
        if ($panterid != '') {
            $where['panterid'] = $panterid;
            $this->assign('panterid', $panterid);
            $map['panterid'] = $panterid;
        } 
        if ($name != '') {
            $where['name'] = array('like', '%' . $name . '%');
            $this->assign('name', $name);
            $map['name'] = $name;
        }
        $count = $zzk_store->where($where)->count();
        $p = new \Think\Page($count, 15);
        $panters_list = $zzk_store->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('storeid')->select();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        // print_r($panters_list);
        $this->assign('list', $panters_list);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 门店添加
     */
    public function storeadd()
    {
        if (IS_POST) {
            $panterid = trim(I('post.panterid', ''));
            $name = trim(I('post.name'), '');
            
            if (empty($panterid) || empty($name)) {
                $this->error("商户ID和店铺名称必填!");
            }
            $rs = M('panters')->where(array('panterid' => $panterid))->field('panterid')->find();
            // echo $res = M()->getLastSql();
            // print_r($rs);exit;
            if ($rs) {
                $PLACEDDATE = date('Ymd');
                $PLACEDTIME = date('H:i:s');
                $m = M("zzk_store");
                $storeid = $this->zzkgetnumstr("zzk_storeid",8);
                $sql = "INSERT INTO zzk_store (STOREID,PANTERID,NAME,PLACEDDATE,PLACEDTIME) VALUES";
                $sql .= "('" . $storeid . "','" . $panterid . "','" . $name . "','" . $PLACEDDATE . "','" . $PLACEDTIME . "')";
                if ($m->execute($sql)) {

                    $list = I('post.');
                    $bankCharges =trim($list['bankCharges']);
                    $chargesid = $this->getFieldNextNumbers('zzk_charges');
                    $nameenglish = $rs['nameenglish'];
                    $namechinese = $rs['namechinese'];
                    $time = time();
                    $charges = M('zzk_charges');
                    $charges_sql = "INSERT INTO zzk_charges (CHARGESID,NAMEENGLISH,NAMECHINESE,PANTERID,STORENAME,STOREID,TYPE,TIMES) VALUES";
                    $charges_sql .= "('" . $chargesid . "','" . $nameenglish . "','" . $namechinese . "','" . $panterid . "','" . $name . "','" . $storeid . "','" . $bankCharges . "','" . $time . "')";
                    if ($charges->execute($charges_sql)) {
                        bcscale(5);
                        $trade['tradeid'] = $this->getFieldNextNumbers('zzk_tradetype');
                        $trade['debitCardRate'] =       bcdiv(trim($list['t_debitCardRate']),100); 
                        $trade['creditCardRate'] =      bcdiv(trim($list['t_creditCardRate']),100);  
                        $trade['weChatRate'] =          bcdiv(trim($list['t_weChatRate']),100);  
                        $trade['aliPayRate'] =          bcdiv(trim($list['t_aliPayRate']),100); 
                        $trade['debitCardLimit'] =      empty(trim($list['t_debitCardLimit']))?0:$list['t_debitCardLimit']; 
                        $trade['creditCardLimit'] =     empty(trim($list['t_creditCardLimit']))?0:$list['t_creditCardLimit']; 
                        $trade['weChatLimit'] =         empty(trim($list['t_weChatLimit']))?0:$list['t_weChatLimit']; 
                        $trade['aliPayLimit'] =         empty(trim($list['t_aliPayLimit']))?0:$list['t_aliPayLimit']; 
                        $trade['panterid'] = $panterid;
                        $trade['storeid'] = $storeid;
                        $trade['chargesid'] = $chargesid;
                        $trade['times'] = time();
                        $tradetype = M('zzk_tradetype');
                        $tradetype_sql = "INSERT INTO zzk_tradetype (TRADEID,DEBITCARDRATE,CREDITCARDRATE,WECHATRATE,ALIPAYRATE,DEBITCARDLIMIT,CREDITCARDLIMIT,WECHATLIMIT,ALIPAYLIMIT,CHARGESID,STOREID,PANTERID,TIMES) VALUES";
                        $tradetype_sql .= "('" . $trade['tradeid'] . "','" . $trade['debitCardRate'] . "','" . $trade['creditCardRate'] . "','" . $trade['weChatRate'] . "','" . $trade['aliPayRate'] . "','" .  $trade['debitCardLimit'] . "','" . $trade['creditCardLimit'] . "','" . $trade['weChatLimit'] . "','" . $trade['aliPayLimit'] . "','" . $trade['chargesid'] . "','"  . $trade['storeid'] . "','" . $trade['panterid'] . "','" . $trade['times'] . "')";
                        $tradetype->execute($tradetype_sql);

                        $day['dayid'] = $this->getFieldNextNumbers('zzk_daytype');
                        $day['debitCardRate'] =         bcdiv(trim($list['d_debitCardRate']),100); 
                        $day['creditCardRate'] =        bcdiv(trim($list['d_creditCardRate']),100);
                        $day['weChatRate'] =            bcdiv(trim($list['d_weChatRate']),100); 
                        $day['aliPayRate'] =            bcdiv(trim($list['d_aliPayRate']),100); 
                        $day['debitCardLimit'] =        empty(trim($list['d_debitCardLimit']))?0:$list['d_debitCardLimit']; 
                        $day['creditCardLimit'] =       empty(trim($list['d_creditCardLimit']))?0:$list['d_creditCardLimit'];  
                        $day['weChatLimit'] =           empty(trim($list['d_weChatLimit']))?0:$list['d_weChatLimit'];  
                        $day['aliPayLimit'] =           empty(trim($list['d_aliPayLimit']))?0:$list['d_aliPayLimit']; 
                        $day['debitCardExtraRate'] =    bcdiv(trim($list['debitCardExtraRate']),100); 
                        $day['creditCardExtraRate'] =   bcdiv(trim($list['creditCardExtraRate']),100);
                        $day['weChatExtraRate'] =       bcdiv(trim($list['weChatExtraRate']),100); 
                        $day['aliPayExtraRate'] =       bcdiv(trim($list['aliPayExtraRate']),100); 
                        $day['debitCardLeast'] =        empty(trim($list['debitCardLeast']))?0:$list['debitCardLeast'];  
                        $day['creditCardLeast'] =       empty(trim($list['creditCardLeast']))?0:$list['creditCardLeast'];  
                        $day['weChatLeast'] =           empty(trim($list['weChatLeast']))?0:$list['weChatLeast'];  
                        $day['aliPayLeast'] =           empty(trim($list['aliPayLeast']))?0:$list['aliPayLeast'];  
                        $day['panterid'] = $panterid;
                        $day['storeid'] = $storeid;
                        $day['chargesid'] = $chargesid;
                        $day['times'] = time();
                        $daytype = M('zzk_daytype');
                        $daytype_sql = "INSERT INTO zzk_daytype (DAYID,DEBITCARDRATE,CREDITCARDRATE,WECHATRATE,ALIPAYRATE,DEBITCARDLIMIT,CREDITCARDLIMIT,WECHATLIMIT,ALIPAYLIMIT,DEBITCARDEXTRARATE,CREDITCARDEXTRARATE,WECHATEXTRARATE,ALIPAYEXTRARATE,DEBITCARDLEAST,CREDITCARDLEAST,WECHATLEAST,ALIPAYLEAST,CHARGESID,STOREID,PANTERID,TIMES) VALUES";
                        $daytype_sql .= "('" . $day['dayid'] . "','" . $day['debitCardRate'] . "','" . $day['creditCardRate'] . "','" . $day['weChatRate'] . "','" . $day['aliPayRate'] . "','" .  $day['debitCardLimit'] . "','" . $day['creditCardLimit'] . "','" . $day['weChatLimit'] . "','" . $day['aliPayLimit'] . "','" . $day['debitCardExtraRate'] . "','" . $day['creditCardExtraRate'] . "','" . $day['weChatExtraRate'] . "','" . $day['aliPayExtraRate'] . "','" . $day['debitCardLeast'] . "','" . $day['creditCardLeast'] . "','" . $day['weChatLeast'] . "','" . $day['aliPayLeast'] . "','" . $day['chargesid']  . "','" . $day['storeid'] . "','" . $day['panterid'] . "','"   . $day['times'] . "')";
                        $daytype->execute($daytype_sql);
                    }
                    $this->success('添加成功', U('Panters/store'));
                } else {
                    $this->error('添加失败!');
                }
            } else {
                $this->error('没有该商铺ID!');
            }
        }

        $this->display();
    }

    /**
     * 门店编辑
     */
    public function storeEdit()
    {
        if (IS_POST) {
            $panterid = trim(I('post.panterid', ''));
            $storeid = trim(I('post.storeid', ''));
            $name = trim(I('post.name'), '');
            
            if (empty($panterid) || empty($name)) {
                $this->error("商户ID和店铺名称必填!");
            }
            $rs = M('panters')->where(array('panterid' => $panterid))->field('panterid')->find();
            if ($rs) {
                $m = M("zzk_store");
                $sql = "update zzk_store set panterid='$panterid',name='$name' where storeid=$storeid";
                 bcscale(5);
                $list = I('post.');
                $bankcharges['type'] = trim($list['bankCharges']);
                $chargesid['chargesid'] =   trim($list['chargesid']);
                if (!empty($chargesid['chargesid'])) {
                    $bankcharges['type'] = trim($list['bankCharges']);
                    $bankcharges['nameenglish'] = $rs['nameenglish'];
                    $bankcharges['namechinese'] = $rs['namechinese'];
                    $bankcharges['storename'] = $name;
                    $bankcharges['times'] = time();
                    $charges = M('zzk_charges');
                    $update_charges = $charges->where($chargesid)->save($bankcharges);
                    $trade['debitcardrate'] =       bcdiv(trim($list['t_debitCardRate']),100); 
                    $trade['creditcardrate'] =      bcdiv(trim($list['t_creditCardRate']),100);  
                    $trade['wechatrate'] =          bcdiv(trim($list['t_weChatRate']),100);  
                    $trade['alipayrate'] =          bcdiv(trim($list['t_aliPayRate']),100); 
                    $trade['debitcardlimit'] =      trim($list['t_debitCardLimit']); 
                    $trade['creditcardlimit'] =     trim($list['t_creditCardLimit']); 
                    $trade['wechatlimit'] =         trim($list['t_weChatLimit']); 
                    $trade['alipaylimit'] =         trim($list['t_aliPayLimit']); 
                    $trade['panterid'] = $panterid;
                    $trade['times'] = time();
                    $tradetype = M('zzk_tradetype');
                    $update_tradetype = $tradetype->where($chargesid)->save($trade);
                    $day['debitcardrate'] =         bcdiv(trim($list['d_debitCardRate']),100); 
                    $day['creditcardrate'] =        bcdiv(trim($list['d_creditCardRate']),100);
                    $day['wechatrate'] =            bcdiv(trim($list['d_weChatRate']),100); 
                    $day['alipayrate'] =            bcdiv(trim($list['d_aliPayRate']),100); 
                    $day['debitcardlimit'] =        trim($list['d_debitCardLimit']); 
                    $day['creditcardlimit'] =       trim($list['d_creditCardLimit']); 
                    $day['wechatlimit'] =           trim($list['d_weChatLimit']); 
                    $day['alipaylimit'] =           trim($list['d_aliPayLimit']);
                    $day['debitcardextrarate'] =    bcdiv(trim($list['debitCardExtraRate']),100); 
                    $day['creditcardextrarate'] =   bcdiv(trim($list['creditCardExtraRate']),100);
                    $day['wechatextrarate'] =       bcdiv(trim($list['weChatExtraRate']),100); 
                    $day['alipayextrarate'] =       bcdiv(trim($list['aliPayExtraRate']),100); 
                    $day['debitcardleast'] =        trim($list['debitCardLeast']); 
                    $day['creditcardleast'] =       trim($list['creditCardLeast']); 
                    $day['wechatleast'] =           trim($list['weChatLeast']); 
                    $day['alipayleast'] =           trim($list['aliPayLeast']); 
                    $day['panterid'] = $panterid;
                    $day['times'] = time();
                    $daytype = M('zzk_daytype');
                    $update_daytype = $daytype->where($chargesid)->save($day);
                } else {
                    $bankCharges = empty(trim($list['bankCharges']))?'T':trim($list['bankCharges']);
                    $chargesid = $this->getFieldNextNumbers('zzk_charges');
                    $nameenglish = $rs['nameenglish'];
                    $namechinese = $rs['namechinese'];
                    $time = time();
                    $charges = M('zzk_charges');
                    $charges_sql = "INSERT INTO zzk_charges (CHARGESID,NAMEENGLISH,NAMECHINESE,PANTERID,STORENAME,STOREID,TYPE,TIMES) VALUES";
                    $charges_sql .= "('" . $chargesid . "','" . $nameenglish . "','" . $namechinese . "','" . $panterid . "','" . $name . "','" . $storeid . "','" . $bankCharges . "','" . $time . "')";
                    $update_charges =  $charges->execute($charges_sql); 
                    bcscale(5);
                    $trade['tradeid'] = $this->getFieldNextNumbers('zzk_tradetype');
                    $trade['debitCardRate'] =       bcdiv(trim($list['t_debitCardRate']),100); 
                    $trade['creditCardRate'] =      bcdiv(trim($list['t_creditCardRate']),100);  
                    $trade['weChatRate'] =          bcdiv(trim($list['t_weChatRate']),100);  
                    $trade['aliPayRate'] =          bcdiv(trim($list['t_aliPayRate']),100); 
                    $trade['debitCardLimit'] =      empty(trim($list['t_debitCardLimit']))?0:$list['t_debitCardLimit']; 
                    $trade['creditCardLimit'] =     empty(trim($list['t_creditCardLimit']))?0:$list['t_creditCardLimit']; 
                    $trade['weChatLimit'] =         empty(trim($list['t_weChatLimit']))?0:$list['t_weChatLimit']; 
                    $trade['aliPayLimit'] =         empty(trim($list['t_aliPayLimit']))?0:$list['t_aliPayLimit']; 
                    $trade['panterid'] = $panterid;
                    $trade['storeid'] = $storeid;
                    $trade['chargesid'] = $chargesid;
                    $trade['times'] = time();
                    $tradetype = M('zzk_tradetype');
                    $tradetype_sql = "INSERT INTO zzk_tradetype (TRADEID,DEBITCARDRATE,CREDITCARDRATE,WECHATRATE,ALIPAYRATE,DEBITCARDLIMIT,CREDITCARDLIMIT,WECHATLIMIT,ALIPAYLIMIT,CHARGESID,STOREID,PANTERID,TIMES) VALUES";
                    $tradetype_sql .= "('" . $trade['tradeid'] . "','" . $trade['debitCardRate'] . "','" . $trade['creditCardRate'] . "','" . $trade['weChatRate'] . "','" . $trade['aliPayRate'] . "','" .  $trade['debitCardLimit'] . "','" . $trade['creditCardLimit'] . "','" . $trade['weChatLimit'] . "','" . $trade['aliPayLimit'] . "','" . $trade['chargesid'] . "','"  . $trade['storeid'] . "','" . $trade['panterid'] . "','" . $trade['times'] . "')";
                    $update_tradetype = $tradetype->execute($tradetype_sql);
                    $day['dayid'] = $this->getFieldNextNumbers('zzk_daytype');
                    $day['debitCardRate'] =         bcdiv(trim($list['d_debitCardRate']),100); 
                    $day['creditCardRate'] =        bcdiv(trim($list['d_creditCardRate']),100);
                    $day['weChatRate'] =            bcdiv(trim($list['d_weChatRate']),100); 
                    $day['aliPayRate'] =            bcdiv(trim($list['d_aliPayRate']),100); 
                    $day['debitCardLimit'] =        empty(trim($list['d_debitCardLimit']))?0:$list['d_debitCardLimit']; 
                    $day['creditCardLimit'] =       empty(trim($list['d_creditCardLimit']))?0:$list['d_creditCardLimit']; 
                    $day['weChatLimit'] =           empty(trim($list['d_weChatLimit']))?0:$list['d_weChatLimit']; 
                    $day['aliPayLimit'] =           empty(trim($list['d_aliPayLimit']))?0:$list['d_aliPayLimit'];
                    $day['debitCardExtraRate'] =    bcdiv(trim($list['debitCardExtraRate']),100); 
                    $day['creditCardExtraRate'] =   bcdiv(trim($list['creditCardExtraRate']),100);
                    $day['weChatExtraRate'] =       bcdiv(trim($list['weChatExtraRate']),100); 
                    $day['aliPayExtraRate'] =       bcdiv(trim($list['aliPayExtraRate']),100); 
                    $day['debitCardLeast'] =        empty(trim($list['debitCardLeast']))?0:$list['debitCardLeast']; 
                    $day['creditCardLeast'] =       empty(trim($list['creditCardLeast']))?0:$list['creditCardLeast']; 
                    $day['weChatLeast'] =           empty(trim($list['weChatLeast']))?0:$list['weChatLeast']; 
                    $day['aliPayLeast'] =           empty(trim($list['aliPayLeast']))?0:$list['aliPayLeast']; 
                    $day['panterid'] = $panterid;
                    $day['storeid'] = $storeid;
                    $day['chargesid'] = $chargesid;
                    $day['times'] = time();
                    $daytype = M('zzk_daytype');
                    $daytype_sql = "INSERT INTO zzk_daytype (DAYID,DEBITCARDRATE,CREDITCARDRATE,WECHATRATE,ALIPAYRATE,DEBITCARDLIMIT,CREDITCARDLIMIT,WECHATLIMIT,ALIPAYLIMIT,DEBITCARDEXTRARATE,CREDITCARDEXTRARATE,WECHATEXTRARATE,ALIPAYEXTRARATE,DEBITCARDLEAST,CREDITCARDLEAST,WECHATLEAST,ALIPAYLEAST,CHARGESID,STOREID,PANTERID,TIMES) VALUES";
                    $daytype_sql .= "('" . $day['dayid'] . "','" . $day['debitCardRate'] . "','" . $day['creditCardRate'] . "','" . $day['weChatRate'] . "','" . $day['aliPayRate'] . "','" .  $day['debitCardLimit'] . "','" . $day['creditCardLimit'] . "','" . $day['weChatLimit'] . "','" . $day['aliPayLimit'] . "','" . $day['debitCardExtraRate'] . "','" . $day['creditCardExtraRate'] . "','" . $day['weChatExtraRate'] . "','" . $day['aliPayExtraRate'] . "','" . $day['debitCardLeast'] . "','" . $day['creditCardLeast'] . "','" . $day['weChatLeast'] . "','" . $day['aliPayLeast'] . "','" . $day['chargesid']  . "','" . $day['storeid'] . "','" . $day['panterid'] . "','"   . $day['times'] . "')";
                    $update_daytype = $daytype->execute($daytype_sql);
                }
                if ($m->execute($sql) || $update_daytype || $update_tradetype || $update_charges) {
                    $this->success('修改成功', U('Panters/store'));
                } else {
                    $this->error('修改失败!');
                }
            } else {
                $this->error('没有该商铺ID!');
            }
        }

        $storeid = trim(I('get.storeid', ''));
        $zzk_store = M('zzk_store')->where(array('storeid' => $storeid))->field('panterid,name,storeid')->find();
        $zzk_charges = M('zzk_charges')->where(array('storeid' => $storeid))->find();
        $zzk_tradetype = M('zzk_tradetype')->where(array('chargesid' => $zzk_charges['chargesid']))->find();
        $zzk_daytype = M('zzk_daytype')->where(array('chargesid' => $zzk_charges['chargesid']))->find();
        bcscale(5);
        $zzk_tradetype['debitcardrate'] =       is_string($zzk_tradetype['debitcardrate'])? bcmul('0'.$zzk_tradetype['debitcardrate'],100):bcmul($zzk_tradetype['debitcardrate'],100); 
        $zzk_tradetype['creditcardrate'] =      is_string($zzk_tradetype['creditcardrate'])? bcmul('0'.$zzk_tradetype['creditcardrate'],100):bcmul($zzk_tradetype['creditcardrate'],100); 
        $zzk_tradetype['wechatrate'] =          is_string($zzk_tradetype['wechatrate'])? bcmul('0'.$zzk_tradetype['wechatrate'],100):bcmul($zzk_tradetype['wechatrate'],100);
        $zzk_tradetype['alipayrate'] =          is_string($zzk_tradetype['alipayrate'])? bcmul('0'.$zzk_tradetype['alipayrate'],100):bcmul($zzk_tradetype['alipayrate'],100);
        $zzk_daytype['debitcardrate'] =         is_string($zzk_daytype['debitcardrate'])? bcmul('0'.$zzk_daytype['debitcardrate'],100):bcmul($zzk_daytype['debitcardrate'],100);
        $zzk_daytype['creditcardrate'] =        is_string($zzk_daytype['creditcardrate'])? bcmul('0'.$zzk_daytype['creditcardrate'],100):bcmul($zzk_daytype['creditcardrate'],100);
        $zzk_daytype['wechatrate'] =            is_string($zzk_daytype['wechatrate'])? bcmul('0'.$zzk_daytype['wechatrate'],100):bcmul($zzk_daytype['wechatrate'],100);
        $zzk_daytype['alipayrate'] =            is_string($zzk_daytype['alipayrate'])? bcmul('0'.$zzk_daytype['alipayrate'],100):bcmul($zzk_daytype['alipayrate'],100);
        $zzk_daytype['debitcardextrarate'] =    is_string($zzk_daytype['debitcardextrarate'])? bcmul('0'.$zzk_daytype['debitcardextrarate'],100):bcmul($zzk_daytype['debitcardextrarate'],100);
        $zzk_daytype['creditcardextrarate'] =   is_string($zzk_daytype['creditcardextrarate'])? bcmul('0'.$zzk_daytype['creditcardextrarate'],100):bcmul($zzk_daytype['creditcardextrarate'],100);
        $zzk_daytype['wechatextrarate'] =       is_string($zzk_daytype['wechatextrarate'])? bcmul('0'.$zzk_daytype['wechatextrarate'],100):bcmul($zzk_daytype['wechatextrarate'],100);
        $zzk_daytype['alipayextrarate'] =       is_string($zzk_daytype['alipayextrarate'])? bcmul('0'.$zzk_daytype['alipayextrarate'],100):bcmul($zzk_daytype['alipayextrarate'],100);
        $this->assign('zzk_store', $zzk_store);
        $this->assign('zzk_charges', $zzk_charges);
        $this->assign('zzk_tradetype', $zzk_tradetype);
        $this->assign('zzk_daytype', $zzk_daytype);

        $this->display();
    }

    /**
     * [getFieldNextNumber 序列自增]
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    public function getFieldNextNumbers($field)
    {

        $model = new Model();
        $seq_field='seq_'.$field.'_id.nextval';
        $sql="select {$seq_field} from dual";
        $list=$model->query($sql);
        $lastNumber=$list[0]['nextval'];
        return  $lastNumber;
    }
    //
    public function jyAcceptance(){
        $pname=trim(I('get.pname',''));
        $uname=trim(I('get.uname',''));

        if($pname!=''){
            $where['namechinese']=array('like','%'.$pname.'%');
            $this->assign('pname',$pname);
            $map['pname']=$pname;
        }
        if($uname!=''){
            $where['conpername']=array('like','%'.$uname.'%');
            $this->assign('uname',$uname);
            $map['uname']=$uname;
        }

        $count = M('panters')->where($where)->count();
        $page  = new Page($count,10,$map);

        $list = M('panters')->where($where)
                            ->field('namechinese,nameenglish,panterid,jyacceptance')
                            ->limit($page->firstRow,$page->listRows)
                            ->select();
        $this->assign('list',$list);
        $this->assign('config',['0'=>'受理','1'=>'选择受理','2'=>'不受理']);
        $this->assign('page',$page->show());
        $this->display();
    }

    public function editJyAcceptance(){
        if(IS_POST){
            $panterid     = trim(I('post.panterid',''));
            $jyacceptance = trim(I('post.jyacceptance',''));
            $map['panterid'] = $panterid;
            $save['jyacceptance'] = $jyacceptance;
            if(M('panters')->where($map)->save($save)){
                $this->success('修改成功',U('Panters/jyAcceptance'));
            }else{
                $this->error('修改通宝受理配置失败');
            }
        }else{
            $panterid = I('get.panterid','');
            $info     = M('panters')->where(['panterid'=>$panterid])
                                    ->field('namechinese,nameenglish,panterid,jyacceptance')
                                    ->find();
            $this->assign('info',$info);
            $this->assign('config',['0'=>'受理','1'=>'选择受理','2'=>'不受理']);
            $this->display();
        }
    }

}
