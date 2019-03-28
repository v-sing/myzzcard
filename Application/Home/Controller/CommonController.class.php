<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class CommonController extends Controller {
    protected $userid;
    protected $username;
    protected $panterid;
    protected $code;
    protected $objPHPExcel;


     //网联加密
    function pubwRas($data){
        $pubKey=openssl_get_publickey(file_get_contents("./Public/cer/SignCert1.cer"));
        openssl_public_encrypt($data, $encrypted, $pubKey);
        openssl_free_key($pubKey);
        // var_dump($encrypted);exit;
        // var_dump(base64_encode($encrypted));exit;
        return base64_encode($encrypted);
    }
    //银联加密
    function pubyRas($data){
        // var_dump($data);
        // var_dump(file_get_contents("./Public/cer/pub.cer"));exit;
        $pubKey=openssl_get_publickey(file_get_contents("./Public/cer/pub.cer"));
        openssl_public_encrypt($data, $encrypted, $pubKey);
        openssl_free_key($pubKey);
        // var_dump(base64_encode($encrypted));exit;
        return base64_encode($encrypted);
    }

    /**
     * curl请求数据
     *
     * @param string $url 请求地址
     * @param array $param 请求参数
     * @param string $contentType 请求参数格式(json)
     * @return boolean|mixed
     */
    function https_request($url = '', $param = [], $contentType = '')
    {
        $ch = curl_init();

        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $url);

        // 请求参数类型
        $param = $contentType == 'json' ? json_encode($param) : $param;

        // 关闭https验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // post提交
        if ($param) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }

        // 返回的数据是否自动显示
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行并接收响应结果
        $output = curl_exec($ch);
        // echo 4321;
        // var_dump($param);exit;
        // var_dump($output);exit;
        // 关闭curl
        curl_close($ch);

        return $output !== false ? $output : false;
    }


    public function _initialize(){
        header('content-type:text/html;charset=utf-8');
        if (empty($_SESSION['userid'])) {
//            echo $_SESSION['userid'];exit;
//            $this->assign("jumpUrl",__APP__."/Public/login");
            $this->success("未登录，请先登录",U('Public/login'));
        } else {
            //取到系统此时的session_id
            $where['userid']=$_SESSION['userid'];
//            echo $where['userid'];exit;
            $m=M('users');
            $rows= $m->where($where)->find();
//            dump($rows);exit;
//            $session_id = file_get_contents("sessionid",$session_id);
            //进行对比，如果判断到B的session_id,则清除A的session_name,导致A不能进行操作
            if (session_id() != $rows['session_id']){
                unset($_SESSION['userid']);
                unset($_SESSION['username']);
                $this->success("账号异地登录，请核对！",U('Public/login'));
//                echo "<script type='text/javascript'>alert('账号异地登录，请核对！');parent.window.location='zzkp.php/public/login'</script>";
            }
        }
        require("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        $this->objPHPExcel = new \PHPExcel();
        $this->code=M('code_generators');
		$this->checkLogin();
		$che=false;
		foreach(C(NOT_AUTH_ARRAY) as $v){
			$actionArr=explode(',',$v[1]);
			if(in_array(CONTROLLER_NAME,$v) && in_array(ACTION_NAME,$actionArr)){
				$che=true;
				break;
			}
		}
		if(!$che){
			\Org\Util\Rbac::AccessDecision()||$this->error('对不起，您没有权限');
		}
        $act_arr = array(
            "cont_name"=>CONTROLLER_NAME,
            "act_name"=>ACTION_NAME,
            "userid"=>$this->userid
        );
		tag("action_log",$act_arr);
    }
    protected function setTimeMemory(){
    	set_time_limit(0);
    	ini_set('memory_limit', '-1');
    }
    protected function changeCode($input){
    	return iconv('utf-8','gbk',$input);
    }
    public function checkIp(){
         $ip = get_client_ip();
     	$allow_ips = C('ALLOW_IPS');
     	$deny_ips = C('DENY_IPS');
        if(!in_array($ip, $allow_ips)){
            if (in_array($ip, $deny_ips)) {
                exit('Deny Access!');
            } else {
                exit('Error Website!');
            }
        }
     }
	public function checkLogin(){
        header('content-type:text/html;charset=utf-8');
        $userid=session('userid');
        if(empty($userid)){
            $this->redirect('Public/login');
        }else{
            $lastTime=session('lastTime');
            $currentTime=time();
            $takingTime=$currentTime-$lastTime;
            $takingTime=floor(($currentTime-$lastTime)/60);
            //echo $lastTime.'---'.$takingTime;
            if($takingTime>=30){
                session('[destroy]');
                echo "<script type='text/javascript'>alert('操作超时，请重新登录');parent.window.location='zzkp.php/public/login'</script>";
                exit;
            }
            session('lastTime',time());
            $this->userid=session('userid');
            $this->username=session('username');
            $this->panterid=session('panterid');
            $this->assign('user_name',$this->username);
            $this->assign('userid',$this->userid);
        }
    }
    public function getUserid(){
        return $this->userid;
    }
    public function getUsername(){
        return $this->username;
    }
    public function getPanterid(){
        return $this->panterid;
    }
    public function getHysx(){
        $panterid=$this->panterid;
        $where['panterid']=$panterid;
        $panterInfo=D('panters')->where($where)->find();
        return $panterInfo['hysx'];
    }
	public function load_excel($arrList,$tableName,$arrString,$arrSUM,$titleTime){
        header("Content-type: text/html; charset=utf-8");
		if(!is_array($arrList)){exit("<script type='text/javascript'>alert('传入参数有误');window.close();</script>");}
		require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
		require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
		$tableName?'':$tableName=date("Y-m-d",time());
		$objPHPExcel = new \PHPExcel();
		$objPHPExcel->getProperties()->setCreator("yyh");
		$objPHPExcel->getProperties()->setLastModifiedBy("yyh");
		$objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
		$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
		$objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		//$arrList=array(0=>array('姓名','年龄','性别'),1=>array('name'=>'张三','age'=>'10','sex'=>'男'),2=>array('name'=>'李四','age'=>'15','sex'=>'女'));
		//设置宽度为自动适应
 		$zm='A';
		for($n=0;$n<count($arrList[0]);$n++){
			$objPHPExcel->getActiveSheet()->getColumnDimension($zm)->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getStyle($zm)->getAlignment()->setHorizontal('left');
			$zm++;
		}
		$objPHPExcel->getActiveSheet()->SetCellValue('A1',$tableName);
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);

		$i=2;
		foreach($arrList as $k=>$v){
			$i++;
			$z='A';
			foreach($v as $kk=>$vv){
				if(in_array($z,$arrString)){
					$objPHPExcel->getActiveSheet()->SetCellValueExplicit($z.$i,$vv,'s');
				}
				else{
					$objPHPExcel->getActiveSheet()->SetCellValue($z.$i,$vv);
				}
				$z++;
			}
			if($k!=0){
				if($titleTime){
					$titleTime_arr[]=strtotime($v[$titleTime]);
				}
			}
		}
		if($titleTime){
			$titleTime_start=date("Y.m.d",min($titleTime_arr));
			$titleTime_end=date("Y.m.d",max($titleTime_arr));
			$objPHPExcel->getActiveSheet()->SetCellValue('A2',$titleTime_start.'-'.$titleTime_end);
		}
		else{
			$objPHPExcel->getActiveSheet()->SetCellValue('A2',date("Y-m-d"),time());
		}
		$x=chr(ord($z)-1);
		$objPHPExcel->getActiveSheet()->mergeCells('A1:'.$x.'1');
		$objPHPExcel->getActiveSheet()->mergeCells('A2:'.$x.'2');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('center');
		$objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('right');
		//合计
		foreach($arrSUM as $va){
			$objPHPExcel->getActiveSheet()->SetCellValue($va.($i+1),"=SUM({$va}2:{$va}{$i})");
		}
		$objPHPExcel->getActiveSheet()->setTitle("报表");
		//$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save(str_replace('.php', '.xls', __FILE__));
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header("Content-Disposition:attachment;filename=".$tableName.".xls");
        //header("Content-Disposition:attachment;filename=".$tableName.".csv");
		header("Content-Transfer-Encoding:binary");
		$objWriter->save("php://output");
	}
	public function load_csv($arrList,$tableName){
		header("Content-type: text/html; charset=gbk");
		header("Content-type:text/csv");
    	header("Content-Disposition:attachment;filename=".$tableName.".csv");
    	header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    	header('Expires:0');
    	header('Pragma:public');
    	echo $arrList;
	}
    function getPro(){
        $pro_list=D('province')->order('provinceid asc')->select();
        return $pro_list;
    }
    function getCitys($provinceid){
        $city_list=D('city')->where("provinceid='{$provinceid}'")->select();
        return $city_list;
    }
    function getProByCityid($cityid){
        $city_info=D('city')->where("cityid='{$cityid}'")->find();
        return $city_info['provinceid'];
    }
    function getAjaxCitys(){
        $provinceid=$_REQUEST['provinceid'];
        $city_list=D('city')->where("provinceid='{$provinceid}'")->select();
        echo json_encode($city_list);
    }
    //获取所有机构名称
    public function getAllPnames(){
        $allnames=M('panters')->field('panterid,namechinese')->select(); 
        echo $this->ajaxReturn($allnames);
    }
    //调取商户名称
    public function getComplete(){
        $keys=$_GET['keys'];
        if(!empty($keys)){
            $where['namechinese']=array('like','%'.$keys.'%');
        }
        if($this->panterid!='FFFFFFFF'){
        	 $where['_string']="panterid='".$this->panterid."' or parent='".$this->panterid."'";
        }
        $panters=M('panters')->where($where)
        ->field('panterid,namechinese pname')->select();
        echo  json_encode($panters);
    }
    //获取下一个序号  tablename 表名 主键自增一
	public function getnextcode($tablename,$lennum=0){
		if($tablename!=''){
			$dmap['keyname']=$tablename;
			$datatable=$this->code->where($dmap)->getfield('current_seq');
			if($datatable==null){
				$mapp=array(
					'keyname'=>$tablename,
					'current_seq'=>1,
				);
				$strsql="INSERT INTO code_generators (keyname,current_seq) VALUES ('".$tablename."', 1)";
				$codeid=$this->code->execute($strsql);
				if ($codeid==false) { //判断是否成功
			        $this->error('失败！');
			        exit;
			    }
			}
			$map['current_seq']=$datatable+1;
			if($this->code->where($dmap)->save($map)){
				if($lennum!=0){
					$datatable=$this->getnumstr($datatable,$lennum);
				}
				return $datatable;
			}else{
				$this->error('表主键更新失败');
				exit;
			}
		}else{
			$this->error('表名不能为空');
			exit;
		}
	}
	//获得增加长度的字符 $numstr编号    $lennum字符长度
	public function getnumstr($numstr,$lennum){
		$snum=strlen($numstr);
		for($i=1;$i<=$lennum-$snum;$i++){
			$x.='0';
		}
		return $x.$numstr;
	}
	//$filename带路径文件名   $qshnum 读取文件起始行
	public function import_excel($filename,$qshnum=1){
		require_once("./ThinkPHP/Library/Org/Util/PHPExcel.php");
		require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel5.php");
		require_once("./ThinkPHP/Library/Org/Util/PHPExcel/Writer/Excel2007.php");
		$PHPExcel = new \PHPExcel();
		/**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
		$PHPReader = new \PHPExcel_Reader_Excel2007();
		if(!$PHPReader->canRead($filename)){
			$PHPReader = new \PHPExcel_Reader_Excel5();
			if(!$PHPReader->canRead($filename)){
				echo 'no Excel';
				return;
			}
		}
		$PHPExcel = $PHPReader->load($filename);
		$currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $currentDate=$currentSheet->toArray();
        $erp_orders_id=array();
        foreach($currentDate as $key=>$val){
            if($key>=$qshnum){
                $testString=implode('',$val);
                if(empty($testString)){
                    continue;
                }else{
                    $erp_orders_id[]=$val;
                }
            }
        }
		/*$allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
		$allRow = $currentSheet->getHighestRow(); //取得一共有多少行
		$erp_orders_id = array();  //声明数组
		//从第二行开始输出，因为excel表中第一行为列名
		for($currentRow = $qshnum;$currentRow <= $allRow;$currentRow++){
		  	//从第A列开始输出
			for($currentColumn= 'A';$currentColumn<= $allColumn; $currentColumn++){
				//数据坐标
                $address=$currentColumn.$currentRow;
                //读取到的数据，保存到数组$arr中
                $val=$currentSheet->getCell($address)->getValue();

				//$val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65,$currentRow)->getValue();
		        //ord()将字符转为十进制数
			  	if($val!=''){
			  		$erp_orders_id[$currentRow][$currentColumn] = $val;
			  	}
			  //如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*
			  //echo iconv('utf-8','gb2312', $val)."\t";
			}
		} */
		return $erp_orders_id;
	}
    public function _upload($moudle){
        switch($moudle){
            case 'editor':$path='upfile/editor/';break;
            case 'excel':$path='upfile/excel/';break;
            case 'custom':$path='upfile/custom/';break;
            case 'panter':$path='upfile/panter/';break;
            default: $path='upimg/';
        }
        $upload=new \Think\Upload();
        $upload->maxSize=C('ATTACHSIZE');
        $upload->exts=array('jpg', 'gif', 'png', 'jpeg','xls','xlsx');
        $upload->rootPath=PUBLIC_PATH;
        $upload->savePath=$path;
        $upload->savename=array('uniqid',time());
        $upload->autoSub=false;
        $upload->subName=array('date','Ymd');
        $info=$upload->upload();
        if(!$info){
            return $upload->getError();
        }else{
            return $info;
        }
    }
    //多维数组转指定字段的一维数组 $vararray多维数组 $zdname指定字段
	public function getonearray($vararray,$zdname){
		foreach ($vararray as $key => $value) {
			$onearray[$key]=$value[$zdname];
		}
		return $onearray;
	}

    public function writeLogs($module,$msgString){
        $month=date('Ym',time());
        switch($module){
            case 'batchConsume':$logPath=PUBLIC_PATH.'logs/batchConsume/';break;
            case 'batchRecharge':$logPath=PUBLIC_PATH.'logs/batchRecharge/';break;
            case 'cardbatchbuy':$logPath=PUBLIC_PATH.'logs/cardbatchbuy/';break;
            case 'createCards':$logPath=PUBLIC_PATH.'logs/createCards/';break;
            case 'vipCard':$logPath=PUBLIC_PATH.'logs/vipCard/';break;
            case 'cardinexcel':$logPath=PUBLIC_PATH.'logs/cardinexcel/';break;
            case 'carduppwd':$logPath=PUBLIC_PATH.'logs/carduppwd/';break;
            case 'tzpointLogs':$logPath=PUBLIC_PATH.'logs/tzpointLogs/';break;
            case 'tzcxpointLogs':$logPath=PUBLIC_PATH.'logs/tzcxpointLogs/';break;
            case 'opencard':$logPath=PUBLIC_PATH.'logs/opencard/';break;
            default :$logPath=PUBLIC_PATH."logs/$module/";
        }
        $logPath=$logPath.$month.'/';
        $filename=date('Ymd',time()).'.log';
        $filename=$logPath.$filename;
        if(!file_exists($logPath)){
            mkdir($logPath,0777,true);
        }
        file_put_contents($filename,$msgString,FILE_APPEND);
    }
    public function subposstr($ystring,$soustr){
    	if(!empty($ystring)){
    		$strn=strpos($ystring,$soustr);
    		return str_replace(' ','',substr($ystring,$strn+1));
    	}
    }
    public function DateDiff($part, $begin, $end){
		$diff = strtotime($end) - strtotime($begin);
		switch($part)
		{
			case "y": $retval = bcdiv($diff, (60 * 60 * 24 * 365)); break;
			case "m": $retval = bcdiv($diff, (60 * 60 * 24 * 30)); break;
			case "w": $retval = bcdiv($diff, (60 * 60 * 24 * 7)); break;
			case "d": $retval = bcdiv($diff, (60 * 60 * 24)); break;
			case "h": $retval = bcdiv($diff, (60 * 60)); break;
			case "n": $retval = bcdiv($diff, 60); break;
			case "s": $retval = $diff; break;
		}
		return $retval;
	}

    function export_xls($filename,$string){
        //可以修改样式，控制字号、字体、表格线、对齐方式、表格宽度、单元格padding等，在下边的<style></style>
        $header="<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\nxmlns=\"http://www.w3.org/TR/REC-html40\">\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html>\n<head>\n<meta http-equiv=\"Content-type\" content=\"text/html;charset=GBK\" />\n<style>\ntd{padding:14px;mso-ignore:padding;color:windowtext;font-size:14.0pt;font-weight:400;font-style:normal;text-decoration:none;font-family:Arial;mso-generic-font-family:auto;mso-font-charset:134;mso-number-format:General;text-align:general;vertical-align:middle;border:.5pt solid windowtext;mso-background-source:auto;mso-pattern:auto;mso-protection:locked visible;white-space:nowrap;mso-rotate:0;}\n</style>\n</head><body>\n<table x:str border=0 cellpadding=0 cellspacing=0 width=100% style=\"border-collapse: collapse\">";
        $footer="</table>\n</body></html>";
        $exportString=$header.$string.$footer;

        header("Cache-Control:public");
        header("Pragma:public");
        header("Content-type:application/vnd.ms-excel");
        header("Accept-Ranges: bytes");
        header("Content-Disposition:attachment; filename=".$filename);
        header("Content-length:".strlen($exportString));
        echo $exportString;
        exit;
    }

    public function getNextcardno($keyName,$keyLen){
        $map=array('keyname'=>$keyName);
        $cardCode=$this->code->where($map)->field('keyname,prefix,current_seq')->find();
        $kcode=intval($cardCode['current_seq']);
        $kcodeLen=strlen($kcode);
        //echo $kcodeLen.'<br/>';
        if($kcodeLen==1){
            if($kcode==4){
                $kcode=$kcode+1;
            }
        }elseif($kcodeLen==2){
            $kcode1=substr($kcode,0,1);
            if($kcode1==4){
                $kcode1=$kcode1+1;
            }
            $kcode2=substr($kcode,1,1);
            if($kcode2==4){
                $kcode2=$kcode2+1;
            }
            $kcode=$kcode1.$kcode2;
        }elseif($kcodeLen>=3){
            for($i=0;$i<=$kcodeLen-1;$i++){
                $kcode1=substr($kcode,$i,1);
                if($kcode1==4){
                    $kcode1=$kcode1+1;
                }
                if($i==0){
                    $kcode2=$kcode1;
                }elseif($i>0){
                    $kcode2=$kcode2.$kcode1;
                }
            }
            $kcode=$kcode2;
        }
        $kcode=intval($kcode);
        $current_seq=$kcode+1;
        $data=array('current_seq'=>$current_seq);
        $this->code->where($map)->save($data);
        return $this->getnumstr($kcode,$keyLen);
    }

    public function creatRandNum($len){
        $array=array('0','1','2','3','4','5','6','7','8','9');
        //$array=array('X','A','B','C');
        $a="";
        for($i=0;$i<$len;$i++){
            $key=array_rand($array,1);
            $a.=$array[$key];
        }
        return strval($a);
    }
    public function getPanterBrands(){
        $panterid=$_REQUEST['panterid'];
        $map=array('pb.panterid'=>$panterid);
        $field='b.brandid,b.brandname';
        $list=M('brand')->alias('b')->join('panter_brands pb on pb.brandid=b.brandid')
            ->where($map)->field($field)->select();
        $string='';
        if(!empty($list)){
            $string.='<option value="">请选择</option>';
            foreach($list as $key=>$val){
                $string.="<option value='{$val['brandid']}' gname='{$val['brandname']}'>{$val['brandid']}</option>";
            }
        }
        echo $string;
    }
    protected function setTitle($cellMerge,$titleName){
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        //合并单元格
        $objSheet->getRowDimension(1)->setRowHeight(10);//设置第二行行高
        $objSheet->getRowDimension(2)->setRowHeight(10);//设置第二行行高
        $objSheet->getRowDimension(3)->setRowHeight(10);//设置第三行行高
        $objPHPExcel->getActiveSheet()->mergeCells($cellMerge);
        $objSheet->setCellValue('A1',$titleName);
        $styleArray1 = array(
            'font' => array(
                'bold' => true,
                'size'=>20,
                'color'=>array(
                    'rgb' => '000000',
                ),
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
        $objSheet->setCellValue('A1',$titleName);
    }
    //设置报表第一行内容字段描述
    protected function setHeader($startCell,$endCell,$headerArray){
        $merge = $startCell.':'.$endCell;
        $objPHPExcel = $this->objPHPExcel;
        $objSheet=$objPHPExcel->getActiveSheet();
        $objSheet->getStyle($merge)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');//
        $objPHPExcel->getActiveSheet()->getStyle($merge)->getBorders()->getTop()->getColor()->setARGB('E8E8E8');
        $cellArray = range($startCell,$endCell);
        $str = substr($startCell,1,1);
        //设置样式
        $styleArray1 = array(
            'font' => array(
                'bold' => true,
                'color'=>array(
                    'rgb' => '000000',
                ),
            ));
        foreach ($cellArray as $key =>$val){
            $objSheet->getStyle($val.'4')->applyFromArray($styleArray1);
        }
        $i=0;
        for($i=1;$i<$str;$i++){
            $arr[$i]=array();
        }
        $arr[$i]=$headerArray;
//         foreach ($cellArray as $key=>$val){
//             $val.=$str;
//             $i++;
//             $cellArray[$key]=$val;
//         }
//         file_put_contents('./te.txt',$headerArray[10]);
//         $sr='';
//         for($j=0;$j<$i;$j++){
//             $sr.="setCellValue($cellArray[$j],$headerArray[$j])->";
//         }
//         $sr = substr($sr,0,strlen($sr)-2);
//         file_put_contents('./te.txt',$sr);
        $objSheet->fromArray($arr);
       }

    protected function setWidth($setCells,$setWidth){
        $objPHPExcel = $this->objPHPExcel;
        $i=0;
        foreach ($setCells as $key=>$val){
                        $i++;
                    }
        for($j=0;$j<$i;$j++){
            $objPHPExcel->getActiveSheet()->getColumnDimension($setCells[$j])->setWidth($setWidth[$j]);
        }
      }
   protected  function browser_export($type,$filename){
//           header("Content-type:text/html;charset=utf-8");
          if($type=="Excel5"){
              header('Content-Type: application/vnd.ms-excel;charset=gbk');//告诉浏览器将要输出excel03文件
          }else{
              header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
          }
          $filename = iconv('utf-8', 'gbk', $filename);
          header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
          header('Cache-Control: max-age=0');//禁止缓存
      }

    //获取所有省份编号、名称
    public function getAllProvince(){
        $result=M('province')->field('provinceid,provincename')->select();
        echo $this->ajaxReturn($result);
    }
    //根据省份编号获取城市编号、名称
    public function getAllCity($provinceid){
        if(!empty($provinceid)){
            $where['provinceid']=$provinceid;
        }
        $result=M('city')->where($where)->field('cityid,cityname')->select();
        echo $this->ajaxReturn($result);
    }
    //根据省份编号获取城市编号、名称
    public function getAllCounty($cityid){
        if(!empty($cityid)){
            $where['cityid']=$cityid;
        }
        $result=M('county')->where($where)->field('countyid,countyname')->select();
        echo $this->ajaxReturn($result);
    }

    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";
        $model=new Model();
        $list=$model->query($sql);
        $fieldsLength=C('FIELDS_LENGTH');
        $fieldLength=$fieldsLength[$field];
        $lastNumber=$list[0]['nextval'];
        return  $this->getnumstr($lastNumber,$fieldLength);
    }

    /** #
     * 序列号
     * 
     * @param $table string 表名
     * @param $lennum int 字符长度
     * @return $str string 增加长度后的字符
     */
    function zzkgetnumstr($table, $lennum)
    {
        $model = new Model();
        $id = $model->query("select $table.nextval from dual")[0]['nextval'];
        $snum = strlen($id);
        $x = '';
        for ($i = 1; $i <= $lennum - $snum; $i++) {
            $x .= '0';
        }
        return $x . $id;
    }

    //curl抓取数据
    function getQuery($data,$url){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//禁用SSL
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }
    
    function excelToArray($filename){
        // require_once './Public/uploadexcel/Classes/PHPExcel/IOFactory.php';
        require_once("./ThinkPHP/Library/Org/Util/PHPExcel/IOFactory.php");
        
        //加载excel文件
        $filename1 = './Public/uploadexcel/'.$filename;
        $objPHPExcelReader = \PHPExcel_IOFactory::load($filename1);  
     
        $sheet = $objPHPExcelReader->getSheet(0); 		// 读取第一个工作表(编号从 0 开始)
        $highestRow = $sheet->getHighestRow(); 			// 取得总行数
        $highestColumn = $sheet->getHighestColumn(); 	// 取得总列数
        if($highestColumn != 'S') {
            echo '<br>';
            exit('列数不准确！');
        }
     
        $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M', 'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // 一次读取一列
        $res_arr = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $row_arr = array();
            for ($column = 0; $arr[$column] <= $highestColumn; $column++) {
                $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                $row_arr[] = ltrim($val,"'");
            }
            
            $res_arr[] = $row_arr;
        }
        
        return $res_arr;
    }
}
?>
