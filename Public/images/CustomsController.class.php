<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class CustomsController extends CommonController {
    public function _initialize(){
        parent::_initialize();
    }

    function index(){
        $panterid=$this->panterid;
        $customid=trim($_REQUEST['customid']);
        $customid=trim($_REQUEST['customid']);
        $cusname=trim($_REQUEST['cusname']);
        $pname=trim($_REQUEST['pname']);
        $orderby=trim($_REQUEST['orderby']);
        $validate=trim($_REQUEST['validate']);
        $risklevel=trim($_REQUEST['risklevel']);
        $customid = $customid=="会员编号"?"":$customid;
        $cusname = $cusname=="会员名称"?"":$cusname;
        $pname = $pname=="所属机构"?"":$pname;
        if(!empty($customid)){
            $where['c.customid']=$customid;
            $map['customid']=$customid;
            $this->assign('customid',$customid);
        }else{
            $where['c.customid']=array('like','0%');
        }

        if(!empty($cusname)){
            $where['c.namechinese']=array('like','%'.$cusname.'%');
            $map['cusname']=$cusname;
            $this->assign('cusname',$cusname);
        }
        if(!empty($pname)){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $map['pname']=$pname;
            $this->assign('pname',$pname);
        }
        $model=new model();
        if($panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
            $is_admin=0;
        }else{
            $where['_string']="(p.panterid not in ('00000125','00000127','00000118','00000270','00000126') or p.panterid is null )and c.namechinese IS NOT NULL ";
            $is_admin=1;
        }
        switch ($orderby) {
            case 'customid':
                $order_by='customid';
                break;
            case 'sex':
                $order_by='sex';
                break;
            case 'birthday':
                $order_by='birthday';
                break;
            case 'cityname':
                $order_by='cityid';
                break;
            default:
                $order_by='customid';
                break;
        }
        $subQuery="(select sum(cpl.amount) rgamount,cu.customid from card_purchase_logs cpl,customs_c cc,cards c,customs cu";
        $subQuery.=" where cpl.cardno=c.cardno and c.customid=cc.cid and cc.customid=cu.customid ";
        $subQuery.=" and c.cardkind <> '6882' group by cu.customid)";
        $this->assign('orderby',$orderby);
        $order='c.'.$order_by.' desc';//c.namechinese asc,
        if($is_admin==1){
            if($validate==1){
                $date=date('Ymd');
                $where['_string']=' (c.personidexdate-'.$date.')<=10';
                $this->assign('validate',1);
            }else{
                $this->assign('validate',0);
            }
            if($risklevel!==0){
                if($risklevel==1){
                    $where['b.rgamount']=array('between',array(0,100000));
                    $this->assign('risklevel',1);
                }
//                elseif($risklevel==2){
//                    $where['b.rgamount']=array('between',array(50000,100000));
//                    $this->assign('risklevel',2);
//                }
                elseif($risklevel==3){
                    $where['b.rgamount']=array('between',array(100000,200000));
                    $this->assign('risklevel',3);
                }elseif($risklevel==4){
                    $where['b.rgamount']=array('gt',200000);
                    $this->assign('risklevel',4);
                }
            }else{
                $this->assign('risklevel',0);
            }
            $count=$model->table('customs')->alias('c')
                ->join('left join __PANTERS__ p on c.panterid=p.panterid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->join('left join __COUNTY__ co on co.countyid=c.countyid')
                ->join('left join '.$subQuery.' b on b.customid=c.customid')
                ->where($where)->field('c.*,p.namechinese pname,ct.cityname,b.rgamount,co.countyname')->count();
            $p=new \Think\Page($count, 10);
            $custom_list=$model->table('customs')->alias('c')
                ->join('left join __PANTERS__ p on c.panterid=p.panterid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->join('left join __COUNTY__ co on co.countyid=c.countyid')
                ->join('left join '.$subQuery.' b on b.customid=c.customid')
                ->where($where)->field('c.*,p.namechinese pname,ct.cityname,p.hysx,b.rgamount,co.countyname')
                ->limit($p->firstRow.','.$p->listRows)
                ->order($order)->select();
            //echo $model->getLastSql();
        }else{
            $count=$model->table('customs')->alias('c')
                ->join('__PANTERS__ p on c.panterid=p.panterid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->where($where)->field('c.*,p.namechinese pname,ct.cityname,p.hysx,co.countyname')->count();
            $p=new \Think\Page($count, 10);
            $custom_list=$model->table('customs')->alias('c')
                ->join('__PANTERS__ p on c.panterid=p.panterid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->join('left join __COUNTY__ co on co.countyid=c.countyid')
                ->where($where)->field('c.*,p.namechinese pname,ct.cityname,p.hysx,co.countyname')
                ->limit($p->firstRow.','.$p->listRows)
                ->order($order)->select();
        }

        //echo $model->getLastSql();

        session('customs_con',array('where'=>$where,'is_admin'=>1));
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$custom_list);
        $this->assign('page',$page);
        $Hysx=$this->getHysx();
        if($Hysx=='酒店'){
            $this->assign('is_hotel',1);
        }
        $this->assign('is_admin',$is_admin);
		$this->display();
    }

    function customs_excel(){
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $model=new Model();
        $customs_con = session('customs_con');
        $where=$customs_con['where'];
        $is_admin=$customs_con['is_admin'];
//        foreach ($customs_con as $key => $value) {
//            $where[$key]=$value;
//        }
        $order='c.customid desc';
        $subQuery="(select sum(cpl.amount) rgamount,cu.customid from card_purchase_logs cpl,customs_c cc,cards c,customs cu";
        $subQuery.=" where cpl.cardno=c.cardno and c.customid=cc.cid and cc.customid=cu.customid ";
        $subQuery.=" and c.cardkind <> '6882' group by cu.customid)";
        if($is_admin==1){
            $field='cards.cardno,cards.status,c.customid,c.career,c.namechinese cuname,c.sex,c.residaddress,co.countyname,';
            $field.='c.linktel,c.personidtype,c.personid,c.staffpaper,c.personidexdate,b.rgamount,ct.cityname,p.namechinese pname';
            $custom_list=$model->table('customs')->alias('c')
                ->join('__PANTERS__ p on c.panterid=p.panterid')
                ->join('customs_c cc on c.customid=cc.customid')
                ->join('cards  on cc.cid=cards.customid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->join('left join __COUNTY__ co on co.countyid=c.countyid')
                ->join('left join '.$subQuery.' b on b.customid=c.customid')
                ->where($where)->field($field)
                ->order($order)->select();
            $title='会员编号,会员名称,性别,住址,手机号,证件类型,身份证号,收入,身份证有效期,职业,风险等级,卡号,卡状态,国籍,城市,区县,所属机构';
        }else{
            $title='会员编号,会员名称,性别,住址,手机号,证件类型,身份证号,收入,身份证有效期,城市,区县,所属机构';
            $field='c.customid,c.namechinese cuname,c.sex,c.residaddress,c.linktel,c.personidtype,c.personid,';
            $field.='co.countyname,c.staffpaper,c.personidexdate,ct.cityname,p.namechinese pname';
            $custom_list=$model->table('customs')->alias('c')
                ->join('__PANTERS__ p on c.panterid=p.panterid')
                ->join('left join __CITY__ ct on ct.cityid=c.cityid')
                ->join('left join __COUNTY__ co on co.countyid=c.countyid')
                ->where($where)->field($field)
                ->order($order)->select();
        }

        //echo $model->getLastSql();exit;
        $strlist=$title;
        $strlist=iconv('utf-8','gbk',$strlist);
        $strlist.="\n";
        foreach($custom_list as $key=>$val){
            if($val['staffpaper']==1){
                $staff='2000以下';
            }elseif($val['staffpaper']==2){
                $staff='2000-3000';
            }elseif($val['staffpaper']==3){
                $staff='3000-5000';
            }elseif($val['staffpaper']==4){
                $staff='5000-8000';
            }elseif($val['staffpaper']==5){
                $staff='8000-10000';
            }elseif($val['staffpaper']==6){
                $staff='10000以上';
            }else{
                $staff=$val['staffpaper'];
            }
            $cuname=iconv('utf-8','gbk',$val['cuname']);
            $sex=iconv('utf-8','gbk',$val['sex']);
            $residaddress=iconv('utf-8','gbk',$val['residaddress']);
            $personidtype=iconv('utf-8','gbk',$val['personidtype']);
            $staff=iconv('utf-8','gbk',$staff);
            $strlist.=$val['customid']."\t,".$cuname.",".$sex.",".$residaddress;
            $strlist.=",".$val['linktel']."\t,".$personidtype.",".$val['personid']."\t,";
            $strlist.=$staff."\t,".$val['personidexdate'].",";
            if($is_admin==1){
                if($val['rgamount']>0&&$val['rgamount']<=100000){
                    $risk='低风险';
                }elseif($val['rgamount']>100000&&$val['rgamount']<=200000){
                    $risk='中风险';
                }elseif($val['rgamount']>=200000){
                    $risk='高风险';
                }
                $risk=iconv("utf-8","gbk",$risk);
                $nation=iconv("utf-8","gbk","中国");
                $career=iconv("utf-8","gbk",$val['career']);
                $strlist.=$career.",".$risk.",".$val['cardno']."\t,".$val['status'].",{$nation},";
            }
            $val['cityname']=iconv('utf-8','gbk',$val['cityname']);
            $val['countyname']=iconv('utf-8','gbk',$val['countyname']);
            $val['pname']=iconv('utf-8','gbk',$val['pname']);
            $strlist.=$val['cityname'].",".$val['countyname'].",".$val['pname']."\n";
        }
        $filename='会员报表'.date("YmdHis");
        $filename=iconv("utf-8","gbk",$filename);
        $strlist.=',,,,'.iconv('utf-8','gbk','总计：').count($custom_list)."\t\n";
        unset($custom_list);
        $this->load_csv($strlist,$filename);
    }
    function cusalert(){
        $panterid=$this->panterid;
        $customid=trim($_REQUEST['customid']);
        $customid=trim($_REQUEST['customid']);
        $cusname=trim($_REQUEST['cusname']);
        $pname=trim($_REQUEST['pname']);

        if(!empty($customid)){
            $where['c.customid']=$customid;
            $map['customid']=$customid;
            $this->assign('customid',$customid);
        }

        if(!empty($cusname)){
            $where['c.namechinese']=array('like','%'.$cusname.'%');
            $map['cusname']=$cusname;
            $this->assign('cusname',$cusname);
        }
        if(!empty($pname)){
            $where['p.namechinese']=array('like','%'.$pname.'%');
            $map['pname']=$pname;
            $this->assign('pname',$pname);
        }
        $where['c.status']='1';
        $model=new model();
        if($panterid!='FFFFFFFF'){
            $where1['p.panterid']=$this->panterid;
            $where1['p.parent']=$this->panterid;
            $where1['_logic']='OR';
            $where['_complex']=$where1;
        }
        $count=$model->table('customs')->alias('c')
                ->join('join __PANTERS__ p on c.panterid=p.panterid')
                ->join('join __CITY__ ct on ct.cityid=c.cityid')
                ->where($where)->field('c.*,p.namechinese pname,ct.cityname')->count();
        $p=new \Think\Page($count, 12);
        $custom_list=$model->table('customs')->alias('c')
                     ->join('join __PANTERS__ p on c.panterid=p.panterid')
                     ->join('join __CITY__ ct on ct.cityid=c.cityid')
                     ->where($where)->field('c.*,p.namechinese pname,ct.cityname,p.hysx')
                     ->limit($p->firstRow.','.$p->listRows)
                     ->order('c.customid desc')->select();
        if(!empty($map)){
            foreach($map as $key=>$val) {
                $p->parameter[$key]= $val;
            }
        }
        $page = $p->show();
        $this->assign('list',$custom_list);
        $this->assign('page',$page);
        $this->display();
    }

    public function add(){
      $panterid_if = $this->panterid;
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      //  $panterid_if = '00000126';
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
        if(IS_POST){
            $customs = M('customs');
            $customs->startTrans();
            $namechinese = trim($_POST['namechinese']);
            $nameenglish = trim($_POST['nameenglish']);
            $cityid = trim($_POST['cityid']);
            $linktel = trim($_POST['linktel']);
            $birthday = trim($_POST['birthday']);
            $customlevel = trim($_POST['customlevel']);
            $sex = trim($_POST['sex']);
            $personidtype = trim($_POST['personidtype']);
            $personid = trim($_POST['personid']);
            $email=trim($_POST['email']);
            $staffpaper=trim($_POST['staffpaper']);
            $residaddress=trim($_POST['residaddress']);
            $unitzip=trim($_POST['unitzip']);
            $countrycode=trim($_POST['countrycode']);
            $linkman=trim($_POST['linkman']);
            $panterid=trim($_POST['panterid']);
            $pname=trim($_POST['pname']);
            $countyid = trim(I('post.countyid',''));
            $careertype=trim(I('post.careertype',''));
            if(!empty($namechinese)||!empty($personid)||!empty($linktel))
            {
              $sl=preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$namechinese);
              if(!$sl)
              {
                $this->error("会员名必须为中文");
              }
              if(!$boolif){
                $datapersonif['personid'] = $personid;
                $personidif = $customs->where($datapersonif)->find();
                if($personidif)
                {
                  $this->error("身份证已经绑定，请更换");
                }
                $datalinktel['linktel'] = $linktel;
                if($customs->where($datalinktel)->find())
                {
                  $this->error("联系电话已绑定，请更换");
                }
              }
            }
            if(empty($panterid)){
                if(empty($pname)){
                    $this->error('所属商户必选');
                }else{
                    $map=array('namechinese'=>$pname);
                    $panter=M('panters')->field('panterid')->where($map)->find();
                    if($panter==false){
                        $this->error('查无此商户记录,请确定商户名称无误');
                    }else{
                        $panterid=$panter['panterid'];
                    }
                }
            }
			//最后判断身份证的数据有没有填写，并上传服务器
			$personidexdate=trim($_POST['personidexdate']);
			// if($_FILES['frontImg']['error']!=0){
			// 	 $this->error('未上传身份证正面');
			// }
			// elseif($_FILES['reserveImg']['error']!=0){
			// 	 $this->error('未上传身份证反面');
			// }
			// elseif(empty($personidexdate)){
			// 	$this->error('未填写身份证有效期');
			// }
			// else{
			// 	$upInfo=$this->_upload("custom");
			// 	$frontImg=$upInfo['frontImg']['savepath'].$upInfo['frontImg']['savename'];
			// 	$reserveImg=$upInfo['reserveImg']['savepath'].$upInfo['reserveImg']['savename'];
			// }
			//2016--wan对会员上传图片严格控制-start
			if($_FILES['frontImg']['error']==0 || $_FILES['reserveImg']['error']==0){
			    $upInfo=$this->_upload("custom");
		    	$frontImg=$upInfo['frontImg']['savepath'].$upInfo['frontImg']['savename'];
		    	$reserveImg=$upInfo['reserveImg']['savepath'].$upInfo['reserveImg']['savename'];
		    	$frontImg_type ="./Public/".$frontImg;
		    	$reserveImg_type ="./Public/".$reserveImg;
		    	$type_img1 = file_type($frontImg_type);
		    	$type_img2 = file_type($reserveImg_type);
		    	if(!in_array($type_img1,array('jpg', 'gif', 'png', 'jpeg'))){
		    	    $this->error('身份证正面图片格式不对！');
		    	    unlink($frontImg_type);
		    	}
		    	if(!in_array($type_img2,array('jpg', 'gif', 'png', 'jpeg'))){
		    	    $this->error('身份证反面图片格式不对！');
		    	    unlink($reserveImg_type);
		    	}
			}
			//----end
            $where=array();
            $where['namechinese']=array('like','%'.$namechinese.'%');
            $where['personid'] = $personid;
            $where['_logic'] = 'or';
            $seach_con['_string']="namechinese ='{$namechinese}' OR personid='{$personid}'";
                //array('namechinese'=>$namechinese,'personid'=>$personid);
            $searchs=M('searchs');
            $search=$searchs->where($seach_con)->select();
            if($search!=false){
                $this->assign('waitSecond',10);
                $this->error('<div style="color:#ff2222;font-size:15px;">该用户疑似恐怖分子，请速与警方联系</div>');
            }
            $currentDate=date('Ymd',time());
            //$customid=$this->getnextcode('customs',8);
            $customid=$this->getFieldNextNumber("customid");
            $sql='insert into customs(customid,namechinese,nameenglish,cityid,linktel,';
            $sql.='birthday,customlevel,sex,personidtype,personid,email,staffpaper,';
            $sql.="residaddress,unitzip,countrycode,linkman,panterid,placeddate,frontimg,reserveimg,personidexdate,countyid,careertype ) values('{$customid}','{$namechinese}',";
            $sql.="'{$nameenglish}','{$cityid}','{$linktel}','{$birthday}','{$customlevel}',";
            $sql.="'{$sex}','{$personidtype}','{$personid}','{$email}','{$staffpaper}'";
            $sql.=",'{$residaddress}','{$unitzip}','{$countrycode}','{$linkman}','{$panterid}','{$currentDate}','{$frontImg}','{$reserveImg}','{$personidexdate}','{$countyid}','{$careertype}')";
            if($customs->execute($sql)){
                $customs->commit();
                $this->assign('waitSecond',5);
                $this->success('会员添加成功<br/><a href="add">继续添加</a>','index');
            }else{
                $customs->rollback();
                $this->error('会员添加失败');
            }
        }
        else{
        	$client_ip = get_client_ip();
        	$location = getIPLoc_sina($client_ip);
        	// $location = getIPLoc_sina('123.161.209.66');   //此IP是郑州市的IP
            $pro = $this->getPro();//返回数据库省
        	foreach ($pro as $p){
        		if (substr_count($p['provincename'], $location->province) > 0){
        			$defaultPro = $p;
        		}
        	}

        	$where['provinceid'] = $defaultPro['provinceid'];
        	$citys = M('city')->field('cityid,cityname')->where($where)->order('cityid asc')->select();
        	foreach ($citys as $c){
        		if (substr_count($c['cityname'], $location->city) > 0){
        			$defaultCity = $c;
        		}
            $where1['cityid'] = $defaultCity['cityid'];
            $countys = M('county')->field('countyid,countyname')->where($where1)->order('countyid asc')->select();
        	}

            $where = array();
            $where['panterid'] = $this->panterid;
            $panters = M('panters')->where($where)->field('panterid,namechinese pname')->find();
            $this->assign('pro',$pro);
            $this->assign('defaultPro',$defaultPro);
            $this->assign('citys',$citys);
            $this->assign('county',$countys);
            $this->assign('defaultCity',$defaultCity);
            $this->assign('panters',$panters);
            $hysx=$this->getHysx();
            if($hysx=='酒店'){
                $this->assign('hysx','酒店');
            }
            $this->display();
        }
    }

    public function edit(){
      $panterid_if = $this->panterid;
      // $panterid_if = '00000126';
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
        if(IS_POST){
            $custom=M('customs');
			//echo $custom->getLastSql();
            $data['namechinese']=trim($_POST['namechinese']);
            $data['nameenglish']=trim($_POST['nameenglish']);
            $data['provinceid']=trim($_POST['provinceid']);
            $data['cityid']=trim($_POST['cityid']);
            $data['linktel']=trim($_POST['linktel']);
            $data['birthday']=trim($_POST['birthday']);
            $data['customlevel']=trim($_POST['customlevel']);
            $data['sex']=trim($_POST['sex']);
            $data['personidtype']=trim($_POST['personidtype']);
            $data['personid']=trim($_POST['personid']);
            $data['email']=trim($_POST['email']);
            $data['staffpaper']=trim($_POST['staffpaper']);
            $data['residaddress']=trim($_POST['residaddress']);
            $data['unitzip']=trim($_POST['unitzip']);
            $data['countrycode']=trim($_POST['countrycode']);
            $data['linkman']=trim($_POST['linkman']);
            $panterid=$data['panterid']=trim($_POST['panterid']);
            $pname=trim($_POST['pname']);
            $data['careertype']=trim(I('post.careertype',''));
            $data['countyid']=trim(I('post.countyid',''));
            //后台编辑验证唯一身份证等
            $customid = trim($_POST['customid']);
            $personid = trim($_POST['personid']);
            $linktel = trim($_POST['linktel']);
            $namechinese = trim($_POST['namechinese']);
            if($namechinese=="")
            {
              $this->error("会员名不能为空");
            }
            if($linktel=="")
            {
              $this->error("联系方式不能为空!");
            }
            if($personid=="")
            {
              $this->error("证件号码不能为空");
            }
            if(!empty($namechinese)||!empty($personid)||!empty($linktel))
            {
              $sl=preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$namechinese);
              if(!$sl)
              {
                $this->error("会员名必须为中文");
              }
              //-----酒店不做身份证 电话唯一性验证
              if(!$boolif){
                $datalinktel['linktel'] = $linktel;
                $datalinktel['customid'] = array("neq",$customid);
                if($custom->where($datalinktel)->find())
                {
                  $this->error("联系电话已绑定，请更换");
                }
                $datapersonif['customid'] = array("neq",$customid);
                $datapersonif['personid'] = $personid;
                $personidif = $custom->where($datapersonif)->find();
                if($personidif)
                {
                  $this->error("身份证已经绑定，请更换");
                }
              }
            }
            if(empty($panterid)){
                if(empty($pname)){
                    $this->error('商户必选');
                }else{
                    $map=array('namechinese'=>$pname);
                    $panter=M('panters')->field('panterid')->where($map)->find();
                    if($panter==false){
                        $this->error('查无此商户记录,请确定商户名称无误');
                    }else{
                        $data['panterid']=$panter['panterid'];
                    }
                }
            }
			//最后判断身份证的数据有没有填写，并上传服务器
			$personidexdate=trim($_POST['personidexdate']);
			if(empty($personidexdate)){
				// $this->error('未填写身份证有效期');
			}
			else{
				$data['personidexdate']=trim($_POST['personidexdate']);
			}
			if($_FILES['frontImg']['error']==0||$_FILES['reserveImg']['error']==0){
				$upInfo=$this->_upload("custom");
				$list=$custom->where("customid='{$customid}'")->find();
				if($_FILES['frontImg']['error']==0){
				    $data['frontimg']=$upInfo['frontImg']['savepath'].$upInfo['frontImg']['savename'];
				    $frontimg_type="./Public/".$data['frontimg'];
				    $type_img1=file_type($frontimg_type);
				    if(!in_array($type_img1,array('jpg', 'gif', 'png', 'jpeg'))){
				        $this->error('身份证正面，请上传合适图片！');
				        unlink($frontimg_type);
				    }
					if(!empty($list['frontimg'])){
						unlink('./Public/'.$list['frontimg']);
					}
				}
				if($_FILES['reserveImg']['error']==0){
				    $data['reserveimg']=$upInfo['reserveImg']['savepath'].$upInfo['reserveImg']['savename'];
				    $reserveimg_type="./Public/".$data['reserveimg'];
				    $type_img2=file_type($reserveimg_type);
				    if(!in_array($type_img2,array('jpg', 'gif', 'png', 'jpeg'))){
				        $this->error('身份证反面，请上传合适图片！');
				        unlink($reserveimg_type);
				    }
					if(!empty($list['reserveimg'])){
						unlink('./Public/'.$list['reserveimg']);
					}
			}
		}
      //后台验证身份证和电话唯一性
            // $customs = M('customs');
            // $wherepersonid['customid'] = array("neq",trim($_POST['customid']));
            // $wherepersonid['personid'] = trim($_POST['personid']);
            // if($customs->where($wherepersonid)->find())
            // {
            //   $this->error("身份证号已绑定，请更换");
            // }
            // //后台验证联系电话唯一性
            // $wherelinktel['customid'] = array("neq",trim($_POST['customid']));
            // $wherelinktel['linktel'] = trim($_POST['linktel']);
            // if($customs->where($wherelinktel)->find())
            // {
            //   $this->error("联系电话已绑定，请更换");
            // }


            $where=array();
            $where['namechinese']=array('like','%'.$data['namechinese'].'%');
            $where['personid'] = $data['personid'];
            $where['_logic'] = 'or';
            $searchs=M('searchs');
            $search=$searchs->where($where)->find();
            if($search==false){
                $data['status']='0';
            }else{
                $data['status']='1';
            }
            if($custom->create()){
				//切忌会员ID需要加单引号 正确格式:customid='01999999' 而不是customid=01999999
				if($custom->where("customid='{$customid}'")->save($data)){
                    $this->success('修改会员资料成功',U('Customs/index'));
                }else{
                    $this->error('修改失败');
                }
            }
        }else{
            $customid=trim($_REQUEST['customid']);
            if(empty($customid)){
                $this->error('会员ID缺失');
            }
            $where['cu.customid']=$customid;
            $field='cu.*,p.namechinese pname';
            $custom_info=M('customs')->alias('cu')->join("left join panters p on p.panterid=cu.panterid")->field($field)->where($where)->find();
            $pro=$this->getPro();//返回所有省列表
            if(!empty($custom_info['cityid'])){
                $current_proid=$this->getProByCityid($custom_info['cityid']); //返回城市关联的省id
                $citys=$this->getCitys($current_proid); //返回当前省对应城市列表
                $this->assign('current_proid',$current_proid);
                $this->assign('citys',$citys);
                if(!empty($custom_info['countyid'])){
                  $county = M('county');
                  $county_lists = $county->where("cityid={$custom_info['cityid']}")->select();
                  $this->assign('county11',$county_lists);
                  $this->assign('countyid',$custom_info['countyid']);
                }
            }
            $this->assign('pro',$pro);
            $this->assign('info',$custom_info);
            $this->display();
        }
    }
    //编辑身份证ajax验证控制
    public function editif()
    {
      $panterid_if = $this->panterid;
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      // $panterid_if = '00000126';
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
      $where['personid'] = $_POST['personid'];
      $where['customid'] = array("neq",$_POST['customid']);
      $customs = M('customs');
      $bool = $customs->where($where)->find();
      $arr['status'] = 0;
      $arr['name'] = "";
          //------2016.04.11 酒店 不做身份证唯一性验证
      if(!$boolif){
        if($bool)
        {
          $arr['status'] = 1;
          $arr['name']="身份证已经绑定，请更换";
        }
      }
      echo json_encode($arr);
    }
    //编辑时联系电话ajax的验证控制
    public function linktelif()
    {
      $panterid_if = $this->panterid;
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      // $panterid_if = '00000126';
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
      $where['linktel'] = $_POST['linktel'];
      $where['customid'] = array("neq",$_POST['customid']);
      $customs = M('customs');
      $bool = $customs->where($where)->find();
      $arr['status'] = 0;
      $arr['name'] = "";
          //------2016.04.11 酒店 不做身份证唯一性验证
      if(!$boolif){
        if($bool)
        {
          $arr['status'] = 1;
          $arr['name']="电话已经绑定，请更换";
        }
      }
      echo json_encode($arr);
    }
    //添加时候身份证号ajax验证
    public function addpersonid()
    {
      $panterid_if = $this->panterid;
      // $panterid_if = '00000126';
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
      $customs = M('customs');
      $where['personid'] = trim($_POST['personid']);
      $data = array('status' =>0 ,'name'=>'' );
      //------2016.04.11 酒店 不做身份证唯一性验证
      if(!$boolif){
        if($customs->where($where)->find())
        {
          $data['status'] = 1;
          $data['name'] = "证件号已经绑定,请更换";
        }
      }
      echo json_encode($data);
    }
    public function addlinktel()
    {
      $panterid_if = $this->panterid;
      $arr  = array('00000270','00000118','00000127','00000125','00000126');
      $boolif = in_array($panterid_if,$arr);
      // $panterid_if = '00000126';
      // $hysxlist = M('panters')->where(array('panterid'=>$panterid_if))->field('hysx')->find();
      $customs = M('customs');
      $where['linktel'] = trim($_POST['linktel']);
      $data = array('status' =>0 ,'name'=>'' );
      //----2016.04.11 酒店会员不验证 联系电话唯一
      if(!$boolif){
        if($customs->where($where)->find())
        {
          $data['status'] = 1;
          $data['name'] = "联系方式已经绑定,请更换";
        }
      }
      echo json_encode($data);
    }
    public function getCountys(){
      $cityid = $_POST['cityid'];
      $county_list = M('county')->where("cityid={$cityid}")->select();
      echo json_encode($county_list);
    }
}
