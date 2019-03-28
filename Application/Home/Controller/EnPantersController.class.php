<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\NumEncode;
class EnPantersController extends CommonController {
    protected $sendMethod;
	public function _initialize()
	{
        parent::_initialize();
		$this->url = C("fangzgIP")."/posindex.php/Panter/dataAdd";
		$this->sendMethod = array('tb'=>'赠送通宝','point'=>'赠送积分');
    }
    /*
     * C('greenParent') 大食堂机构
     * C('soonEParent') 一家机构
     */
    //客户信息管理
    function index(){
        $greenPanter=C('greenParent');
        $soonE=C('soonEParent');
        $model = M('enpanters');
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
        $where['dele'] ='0';
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
        $panters_list=$this->panterDecode($panters_list);
        foreach($panters_list as $key=>$val){
            if(!empty($val['conperteleno'])){
                $panters_list[$key]['conperteleno']=substr($val['conperteleno'],0,3).'****'.substr($val['conperteleno'],-4);
            }
            if(!empty($val['conperbpno'])){
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
            $model = M('enpanters');
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
        $list=M('enpanters')->where($where)->find();
        $parents = M('enpanters')->where(array('flag'=>'1'))->field('panterid,namechinese')->select();
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
    //添加客户信息
    function addpanters(){
        $model=new model();
        ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
        ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
        ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
        ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
        ini_set('upload_max_filesize','8M');//文件上传最大
        ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
        if(IS_POST){
            $model->startTrans();
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
            //$revorkflg = trim(I('post.revorkflg',''));
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
            //$tongbao_rate = trim(I('post.tongbao_rate',''));
            //$ctongbao_rate=trim(I('post.ctongbao_rate',''));
            //$wtongbao_rate=trim(I('post.wtongbao_rate',''));
            $timevalue =trim(I('post.timevalue',''));
            $accounttype = trim(I('post.accounttype',''));
            //商户名字不能重复
            $dataif['namechinese'] = $namechinese;
            $bool = $model->table('panters')->where($dataif)->find();
            if($bool)
            {
                $this->error("商户名重复");
            }
            if($namechinese==''){
                $this->error('商户名称不能为空!');
            }
            if($address=='')
            {
                $this->error("商户地址不能为空!");
            }
            if($operatescope=='')
            {
                $this->error("经营范围不能为空!");
            }
            if($business=="")
            {
                $this->error("营业执照不能为空");
            }
            if($timevalue=="")
            {
                $this->error("营业执照有效期不能为空");
            }
            if(empty($hysx)){
                $this->error('行业属性必选！');
            }
            // if($conpername=="")
            // {
            //   $this->error("实际控制人不能为空!");
            // }
            if($accounttype==''){
                $this->error('账户类型不能为空！');
            }
            if($conperbtype=="")
            {
                $this->error("证件类型不能为空");
            }
            if($conperbpno=="")
            {
                $this->error("法人代表证件号不能为空!");
            }
            if($period=="")
            {
                $this->error("证件有效期不能为空");
            }
            if($legalperson=="")
            {
                $this->error("法人代表不能为空");
            }
            if($conpermobno==''){
                $this->error('法人代表手机号不能为空!');
            }
            if($account=="")
            {
                $this->error("每日消费限制不能为空");
            }
            if($sumnumber=="")
            {
                $this->error("每日消费次数不能为空");
            }
            if($oneaccount=="")
            {
                $this->error("每笔刷卡限额不能为空");
            }
            //手机号格式控制
            $preg = '/^[1][34578][0-9]{9}$/';
            if(!preg_match($preg,$conpermobno))
            {
                $this->error("控制人手机号格式不对");
                exit;
            }
            if($_FILES['licenseimg']['error']!=0){
                $this->error('未上传营业执照');
            }
            elseif($_FILES['doorplateimg']['error']!=0){
                $this->error('未上传商户门头');
            }elseif($_FILES['idface']['error']!=0){
                $this->error('未上法人证件号正面');
            }
            elseif($_FILES['idcon']['error']!=0){
                $this->error('未上法人证件号反面');
            }
            else{
//                 $upInfo=$this->_upload("panter");
//                 dump($_FILES);EXIT;
                $path = PUBLIC_PATH."upfile/panter/";
                $pathsave ="upfile/panter/";
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
                $licenseimg=$pathsave.$newname1;
                $doorplateimg=$pathsave.$newname2;
                $idface=$pathsave.$newname3;
                $idcon=$pathsave.$newname4;
                $doorplateimg_type="./Public/".$doorplateimg;
                $licenseimg_type="./Public/".$licenseimg;
                $idface_type="./Public/".$idface;
                $idcon_type="./Public/".$idcon;
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
                    $orzimg_type="./Public/".$orzimg;
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
                    $taximg_type="./Public/".$taximg;
                    $type_img=file_type($taximg_type);
                    if(!in_array($type_img,array('jpg', 'gif', 'png', 'jpeg'))){
                        unlink($taximg_type);
                        $this->error('税务登记证,请上传图片！');
                    }
                }
            }
            $panterid=$this->getFieldNextNumber("panterid");
            $currentDate=date('Ymd');
            $userid =  $this->userid;
            //数据加密处理

            $numEncode = new  NumEncode();
            $conperteleno  = $numEncode->encode($conperteleno);
            $conperbpno    = $numEncode->encode($conperbpno);
            $conpermobno   = $numEncode->encode($conpermobno);
            $psql="INSERT INTO enpanters (panterid,conpername,namechinese,nameenglish,cityid,conpermobno,conperteleno,address,operatescope,organizationcode,business,taxation,conperbtype,legalperson,";
            //$psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,tongbao_rate,placeddate,idface,idcon,timevalue,orzimg,taximg,ctongbao_rate,wtongbao_rate) VALUES ";
			$psql.="conperbpno,revorkflg,parent,pantergroup,hysx,goingteleno,stoppayflg,RakeRate,SettlementPeriod,flag,licenseimg,doorplateimg,period,tongbaoitem,placeddate,idface,idcon,timevalue,orzimg,taximg,status,accounttype) VALUES ";
            $psql.="('".$panterid."','".$conpername."','".$namechinese."','".$nameenglish."','".$cityid."','".$conpermobno."','";
            $psql.=$conperteleno."','".$address."','".$operatescope."','".$organizationcode."','".$business."','".$taxation."','".$conperbtype."','".$legalperson."','".$conperbpno."','Y','".$parent."','".$pantergroup."','".$hysx."','".$goingteleno."','N',100,30,3,'".$licenseimg."','".$doorplateimg;
            //$psql.="','".$period."','".$tongbaoitem."','".$tongbao_rate."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."','".$ctongbao_rate."','".$wtongbao_rate."')";
			$psql.="','".$period."','".$tongbaoitem."','".$currentDate."','".$idface."','".$idcon."','".$timevalue."','".$orzimg."','".$taximg."',".'0'.",'".$accounttype."')";
            $fxsql="INSERT INTO panter_con_account values ('".$panterid."','".$account."','".$sumnumber."','".$oneaccount."','".$userid."')";
            if($model->execute($psql)){
              if($model->execute($fxsql)){
                //--------20160318-----房掌柜数据同步
					if($syncfz == 1){
						$result = $this->_fzgSync($namechinese,$panterid,$cityid,$conperteleno);
						if($result['returnCode'] != 1){
							$model->rollback();
							$this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('EnPanters/addpanters'));
						}
					}
                    //----------end-----
                    $model->commit();
                    $this->success('商户添加成功',U('Panters/addterminal',array('panterid'=>$panterid)));
                }else{
                    $model->rollback();
                    $this->error('商户添加失败',U('EnPanters/addpanters'));
                }
            }else{
                $model->rollback();
                $this->error('商户添加失败',U('EnPanters/addpanters'));
            }
        }else{
            $hysxs=array('餐饮','娱乐','服装','珠宝','美容','文教','酒店','房产/物业','其他');
            $this->assign('hysxs',$hysxs);
            $province=M('province');
            //查询所属机构  start
            $parentwhere['flag']='1';
						$parentwhere['revorkflg']='N';
            $parents = M('enpanters')->where($parentwhere)->select();
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
    //修改客户信息
    function editpanters(){
    	$model = M('enpanters');
        $pantersca = M('panter_con_account');
        ini_set('file_uploads','ON');//Http上传文件的开关，默认为开
        ini_set('max_input_time','90');//通过post,以及put接收数据时间，默认为60秒
        ini_set('max_execution_time','180');//默认为30秒，脚本执行时间修改为180秒
        ini_set('post_max_size','10M');//修改post变量由2m变成1om,要比upload_max_filesize大
        ini_set('upload_max_filesize','8M');//文件上传最大
        ini_set('memory_limit','90M');//内存使用问题，最好比post_max_size大1.5倍
    	if(IS_POST){
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
            if($namechinese==''){
                $this->error('商户名称不能为空!');
            }
            if($address=='')
            {
              $this->error("商户地址不能为空!");
            }
            if($operatescope=='')
            {
              $this->error("经营范围不能为空!");
            }
            if($business=="")
            {
              $this->error("营业执照不能为空");
            }
            if($timevalue=="")
            {
                $this->error("营业执照有效期不能为空");
            }
            if(empty($hysx)){
                $this->error('行业属性必选！');
            }
            // if($conpername=="")
            // {
            //   $this->error("实际控制人不能为空!");
            // }
            if($conperbtype=="")
            {
              $this->error("证件类型不能为空");
            }
            if($conperbpno=="")
            {
              $this->error("法人代表证件号不能为空!");
            }
            if($period=="")
            {
              $this->error("证件有效期不能为空");
            }
            if($legalperson=="")
            {
              $this->error("法人代表不能为空");
            }
            if($conpermobno==''){
                $this->error('法人代表手机号不能为空!');
            }
            if($account=="")
            {
              $this->error("每日消费限制不能为空");
            }
            if($sumnumber=="")
            {
              $this->error("每日消费次数不能为空");
            }
            if($oneaccount=="")
            {
              $this->error("每笔刷卡限额不能为空");
            }
            $preg = '/^[1][34578][0-9]{9}$/';
            if(!preg_match($preg,$conpermobno))
            {
               $this->error("手机号格式不对");
               exit;
            }
    		if($panterid!=''){
	    		$wheres['panterid']=$panterid;
	    	}else{
	    		$this->error('商户编号不能为空！');
				exit;
	    	}
	    	$path = PUBLIC_PATH."upfile/panter/";
	    	$pathsave ="upfile/panter/";

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
				        unlink('./Public/'.$list['doorplateimg']);
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
				        unlink('./Public/'.$list['idface']);
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
				        unlink('./Public/'.$list['idface']);
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
			        unlink('./Public/'.$list['orzimg']);
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
			        unlink('./Public/'.$list['taximg']);
			    }
			}
			$placeddateif = M('enpanters')->where(array('panterid'=>$panterid))->field('placeddate')->find();
			if($placeddateif['placeddate']===null){
			    $_POST['placeddate'] = date('Ymd',time());
			}
	    	if($model->create()){
// 				var_dump($_POST);exit;
                $_POST['status']='0';
                $_POST = $this->panterencode($_POST);
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
								$this->error("房掌柜数据同步失败!{$result['returnMsg']}",U('EnPanters/addpanters'));
							}
						}
                        $model->commit();
                        $this->success('修改成功！',U('EnPanters/index'));
                        exit;
                    }
				}else{
                    $model->rollback();
					$this->error('修改失败！');
					exit;
				}
			}
    	}
    	$province=M('province');
    	$provinces = $province->order('provinceid asc')->select();
		$this->assign('province',$provinces);
    	$panterid=I('get.panterid','');
    	$this->assign('panterid',$panterid);
    	if($panterid!=''){
    		$where['a.panterid']=$panterid;
    	}
    	$panters = $model->alias('a')->join('left join panter_con_account b on b.panterid=a.panterid')->where($where)->field('a.*,b.d_one_account,b.d_sum_number,b.d_sum_account')->find();
        $panters = $this->panterDecode($panters);
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
        $parents = M('enpanters')->where($parentwhere)->select();
        $this->assign('parents',$parents);
        //================end===============//
		$this->assign('panters',$panters);
        $pantergroup=M('pantergroup');
        $pantergroups= $pantergroup->order('groupid asc')->select();
        $this->assign('pantergroups',$pantergroups);
    	$this->display();
    }
    //商户结算管理
	function balance(){
        $panters=M('enpanters');
        $panterid =trim(I('get.panterid',''));
        $pname  = trim(I('get.pname',''));
        $panterid = $panterid=="商户编号"?"":$panterid;
        $pname = $pname=="商户名称"?"":$pname;
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
        $panters=M('enpanters');
        $city=M('city');
        if(IS_POST){
            $panterid=I('post.panterid','');
            //银行卡号控制16位或者19位数字
            $settlebankid=I('post.settlebankid','');
            $preg = '/^(\d{12,30})$/';
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
        $this->display();
    }
	/*
    function delpanters(){
        $panters=M('enpanters');
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
            $where['tw.tradetype']=array('in','00,21');
            $where['c.cardkind']=array('not in',array('6882','2081','6880'));
			//$where['p.panterid']=array('not in',array('00000286','00000290'));
            $where['tw.tradepoint']=0;
            $where['tw.tradeamount']=array('gt',0);
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
                    $panterTrade=M('trade_panter_day_books')->field('tradeamount,tradepoint,tradequantity,retailamount,proxyamount')->where($map)->find();
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
                        $tradepoint=$panterTrade['tradepoint']+$val['tradepoint'];
                        $tradequantity=$panterTrade['tradequantity']+1;
                        $retailamount=$panterTrade['retailamount']+$jsamount;
                        $proxyamount=$panterTrade['proxyamount']+$sxamount;
                        $data=array('tradeamount'=>$tradeamount,'tradepoint'=>$tradepoint,
                            'tradequantity'=>$tradequantity,'retailamount'=>$retailamount,'proxyamount'=>$proxyamount);
                        $model->table('trade_panter_day_books')->where($map)->save($data);
                    }else{
                        $sql='insert into trade_panter_day_books(panterid,statdate,termposno,tradeamount,tradepoint,tradequantity,rakerate,retailamount,proxyamount)';
                        $sql.=" values('{$val['panterid']}','{$val['placeddate']}','{$val['termposno']}',{$val['tradeamount']},";
                        $sql.="{$val['tradepoint']},1,{$val['rakerate']},'{$jsamount}','{$sxamount}')";
                        $model->execute($sql);
                    }
                    echo $model->getLastSql().'<br/>';
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
            if(M('enpanters')->where($map)->save($data)){
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
            $panter=M('enpanters')->field('panterid,namechinese,pantergroup')->where($where)->find();
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
      $panter = M('enpanters');
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
      $items = M('enpanters')->where($witems)->select();
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
    $panter = M('enpanters');
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
      $panters = M('enpanters');
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
       $res = M('enpanters')->where(array('panterid'=>$panterid,'revorkflg'=>'N'))->find();
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
        $panters=M('enpanters');
        $map=array('panterid'=>$panterid);
        $panter=$panters->where($map)->field('panterid,namechinese')->find();
        if($panter==false){
            $this->error('商户不存在');
        }
        $sql="update enpanters set revorkflg='N' where panterid='{$panterid}'";
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
        $count=M('enpanters')->where($where)->count();
        $p=new \Think\Page($count, 10 );
        $panters=M('enpanters')->where($where)->limit($p->firstRow,$p->listRows)->select();
        $panters=$this->panterDecode($panters);
        foreach($panters as $key=>$val){
            if(!empty($val['conperteleno'])){
                $panters[$key]['conperteleno']=substr($val['conperteleno'],0,3).'****'.substr($val['conperteleno'],-4);
            }
            if(!empty($val['conperbpno'])){
                $panters[$key]['conperbpno']=substr($val['conperbpno'],0,6).'********'.substr($val['conperbpno'],-4);
            }
            if(!empty($val['conpermobno'])){
                $panters[$key]['conpermobno']=substr($val['conpermobno'],0,3).'****'.substr($val['conperteleno'],-4);
            }
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
        $list=M('enpanters')->where(['panterid'=>$panterid])->find();
        $this->assign('panters',$list);
        $this->display();
    }
    /*
     * 商户审核 通过 或者拒绝
     */
    public function apply(){
        $panterid=trim(I('get.panterid',''));
        $panterid==true||$this->error('非法参数传入');
        $list=M('enpanters')->where(['panterid'=>$panterid])->find();
        isset($list)==true||$this->error('非法商户号');
        $type=trim(I('get.type',''));
        if($type=='1'){
            $bool=M('enpanters')->where(['panterid'=>$panterid])->save(['status'=>$type,'revorkflg'=>'N']);
            if($bool==true) $this->success('商户审核通过成功',U('verify'));
                else  $this->success('商户审核通过失败');
        }elseif($type=='2'){
            $bool=M('enpanters')->where(['panterid'=>$panterid])->save(['status'=>$type]);
            if($bool==true) $this->success('商户审核拒绝成功',U('verify'));
            else  $this->success('商户审核拒绝失败');
        }

    }
    protected function panterDecode($arr){
        $numEncode = new NumEncode();
        $oneLevel = $this->arrOneLevel($arr);
        if($oneLevel){
            $arr['conperteleno'] = $numEncode->decode( $arr['conperteleno'],'1');
            $arr['conperbpno'] =$numEncode->decode( $arr['conperbpno']);
            $arr['conpermobno'] = $numEncode->decode( $arr['conpermobno'],'1');
        }else{
            foreach($arr as $key=>$val){
                if($val['conperteleno']){
                    $val['conperteleno'] = $numEncode->decode( $val['conperteleno'],'1');
                }
                if($val['conperbpno']){
                    $val['conperbpno'] =$numEncode->decode( $val['conperbpno']);
                }
                if($val['conpermobno']){
                    $val['conpermobno'] = $numEncode->decode( $val['conpermobno'],'1');
                }
                $arr[$key] = $val;
            }
        }
        return $arr;
    }
    protected function panterEncode($arr){
        $numEncode = new NumEncode();
        $oneLevel = $this->arrOneLevel($arr);
        if($oneLevel){
            $arr['conperteleno'] = $numEncode->encode( $arr['conperteleno'],'1');
            $arr['conperbpno'] =$numEncode->encode( $arr['conperbpno']);
            $arr['conpermobno'] = $numEncode->encode( $arr['conpermobno'],'1');
        }else{
            foreach($arr as $key=>$val){
                if($val['conperteleno']){
                    $val['conperteleno'] = $numEncode->encode( $val['conperteleno'],'1');
                }
                if($val['conperbpno']){
                    $val['conperbpno'] =$numEncode->encode( $val['conperbpno']);
                }
                if($val['conpermobno']){
                    $val['conpermobno'] = $numEncode->encode( $val['conpermobno'],'1');
                }
                $arr[$key] = $val;
            }
        }
        return $arr;
    }
    /*
     * 判定数组是否是一维数组
     */
    protected function arrOneLevel($arr){
        foreach($arr as $val){
            if(!is_array($val)){
                return true;
            }else{
                return false;
            }
        }
    }
    /*
     * 商户删除
     */
    public function panterDelete(){
        $panterid = trim(I('get.panterid',''));
        $map['panterid'] = $panterid;
        if($panterid==''||!(M('enpanters')->where($map)->find())){
            $this->error('非法商户号');
        }
        $bool = M('enpanters')->where($map)->save(['dele'=>'1']);
        if($bool){
            $this->success('删除成功',U('EnPanters/index'));
        }else{
            $this->error('删除失败请重试!');
        }
    }
}
