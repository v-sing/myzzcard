<?php
	function node_merge($node,$access=null,$pid=0){
		$arr=array();
		foreach($node as $v){
			if(is_array($access)){
				$v['access']=in_array($v['id'],$access)?1:0;
			}
			if($v['pid']==$pid){
				$v['child']=node_merge($node,$access,$v['id']);
				$arr[]=$v;
			}
		}
		return $arr;
	}
	
	function dbExe($sql){
		$con=oci_connect('jycs','jycs','127.0.0.1/ORClE');
		$res=oci_parse($con,$sql);
		$r=oci_execute($res);
		return $r;
	}
	
	/**
	 * 格式化输出数组
	 * @param unknown $arr
	 */
	function P($arr){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}
	
	/**
	 * 根据用户的IP地址查找到他所在的地区
	 */
	function getIPLoc_sina($queryIP){
		$url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $queryIP;
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_ENCODING ,'utf8');
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
		$location = curl_exec($ch);
		$location = json_decode($location);
		curl_close($ch);
		if($location === FALSE) {
			return "";
		} else {
			if(is_object($location))
				return $location;
		}
	}
    function encode($string){
        $string=base64_encode($string);
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), $string);
    }
    function decode($string){
        $string=str_replace(array('O0O0O', 'o000o', 'oo00o'),array('=', '+', '/'), $string);
        return base64_decode($string);
    }

    function getPostJson(){
        $data=file_get_contents('php://input');
        $data=json_decode($data,1);
        return $data;
    }

    function returnMsg($msgArr){
        ob_clean();
        exit(json_encode($msgArr));
    }
    function  crul_post($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res=curl_exec ($ch);
        curl_close ($ch);
        return $res;
    }
	function file_type($filename)
		{
		    $file = fopen($filename, "rb");
		    $bin = fread($file, 2); //只读2字节
		    fclose($file);
		    $strInfo = @unpack("C2chars", $bin);
		    $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
		    $fileType = '';
		    switch ($typeCode)
		    {
		        case 7790:
		            $fileType = 'exe';
		            break;
		        case 7784:
		            $fileType = 'midi';
		            break;
		        case 8297:
		            $fileType = 'rar';
		            break;
		        case 8075:
		            $fileType = 'zip';
		            break;
		        case 255216:
		            $fileType = 'jpg';
		            break;
		        case 7173:
		            $fileType = 'gif';
		            break;
		        case 6677:
		            $fileType = 'bmp';
		            break;
		        case 13780:
		            $fileType = 'png';
		            break;
		        default:
		            $fileType = 'unknown: '.$typeCode;
		    }
		    //Fix
		    if ($strInfo['chars1']=='-1' AND $strInfo['chars2']=='-40' ) return 'jpg';
		    if ($strInfo['chars1']=='-119' AND $strInfo['chars2']=='80' ) return 'png';
		    return $fileType;
		}

?>