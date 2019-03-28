<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class Pos1Controller extends Controller
{  // public function posapi()
  // {
  //   $config = M('zz_config');
  //   //接收客户端参数  pos编码  $_POST
  //   /*
  //     1、是否post传参  2、参数是否为空  3、其他
  //
  //   */
  //   // file_put_contents('./a.txt',var_export($_POST));
  //   // var_dump($_POST);
  //   if(IS_POST){
  //     $aid = trim(I('post.imei',''));
  //     $wherecard['imei'] = $aid;
  //     $wherecard['payid'] = '01';
  //     $wherezz['imei'] = $aid;
  //     $wherezz['payid'] = '02';
  //     $where2['imei'] = $aid;
  //     $where2['payid'] = '03';
  //     $card= array();
  //     $zzcard = array();
  //     $alipay = array();
  //     //
  //     $res=$config->where($wherecard)->find();
  //     $res1=$config->where($wherezz)->find();
  //     $res2=$config->where($where2)->find();
  //     //银行卡信息
  //     if($res&&$res1&&$res2)
  //     {
        // $card['imei'] =  $res['imei'];
        // $card['pos_id'] =  $res['pos_id'];
        // $card['channel'] = $res['channel'];
        // $card['panterid'] = $res['panterid'];
        // $card['namechinese'] = $res['namechinese'];
        // $card['ip_address'] = $res['ip_address'];
        // $card['num_id'] = $res['num_id'];
        // $card['tpdu'] = $res['tpdu'];
        //
        // $zzcard['imei'] =  $res1['imei'];
        // $zzcard['pos_id'] =  $res1['pos_id'];
        // // $zzcard['channel'] = $res1['channel'];
        // $zzcard['panterid'] = $res1['panterid'];
        // $zzcard['namechinese'] = $res1['namechinese'];
        // $zzcard['ip_address'] = $res1['ip_address'];
        // $zzcard['num_id'] = $res1['num_id'];
        //
        // $alipay['imei'] =  $res2['imei'];
        // $alipay['pos_id'] =  $res2['pos_id'];
        // $alipay['channel'] = $res2['channel'];
        // $alipay['panterid'] = $res2['panterid'];
        // $alipay['namechinese'] = $res2['namechinese'];
        // $alipay['organizationcode'] = $res2['organizationcode'];
        // $alipay['ip_address'] = $res2['ip_address'];
        // $alipay['num_id'] = $res2['num_id'];
        // $alipay['sign'] = $res2['sign'];
  //     }
  //     else
  //     {
  //         echo json_encode(array('code'=>0,'message'=>'请求错误'));exit;
  //         //die(json_encode());
  //     }
  //     //读取数据库数据
  //     $datas = array(
  //               'code'=>1,
  //               'message'=>'ok',
  //               'detail'=>array(
  //                               'card'=>$card,
  //                               'zzcard'=>$zzcard,
  //                               'alipay'=>$alipay
  //             ));
  //   echo json_encode($datas);exit;
  //     }
  //   else
  //   {
  //       echo json_encode(array('code'=>0,'message'=>'请求错误'));exit;
  //       //die(json_encode());
  //   }
  // }
  /*******POSapi接口变更2016年03/11***********/
  public function posapi()
  {
    if(IS_POST){
      $config = M('zz_config');
      $imei = trim(I('post.imei',''));
      // $imei = trim(I('get.imei',''));
      $wherecard['imei'] = $imei;
      $wherecard['payid'] = '01';
      $wherezz['imei'] = $imei;
      $wherezz['payid'] = '02';
      $where2['imei'] = $imei;
      $where2['payid'] = '03';
      $card= array();
      $zzcard = array();
      $alipay = array();
      $detail = array();
      $res=$config->where($wherecard)->find();
      $res1=$config->where($wherezz)->find();
      $res2=$config->where($where2)->find();
      if($res||$res1||$res2){
          if($res)
          {
            if($res&&$res1&&$res2)
            {
              $card['imei'] =  $res['imei'];
              $card['pos_id'] =  $res['pos_id'];
              $card['channel'] = $res['channel'];
              $card['panterid'] = $res['panterid'];
              $card['namechinese'] = $res['namechinese'];
              $card['ip_address'] = $res['ip_address'];
              $card['num_id'] = $res['num_id'];
              $card['tpdu'] = $res['tpdu'];
              $res['forbid']=='0'||$card['panterid']='000000000000000';

              $zzcard['imei'] =  $res1['imei'];
              $zzcard['pos_id'] =  $res1['pos_id'];
              // $zzcard['channel'] = $res1['channel'];
              $zzcard['panterid'] = $res1['panterid'];
              $zzcard['namechinese'] = $res1['namechinese'];
              $zzcard['ip_address'] = $res1['ip_address'];
              $zzcard['num_id'] = $res1['num_id'];
              $res1['forbid']=='0'||$zzcard['panterid']='000000000000000';

              $alipay['imei'] =  $res2['imei'];
              $alipay['pos_id'] =  $res2['pos_id'];
              $alipay['channel'] = $res2['channel'];
              $alipay['panterid'] = $res2['panterid'];
              $alipay['namechinese'] = $res2['namechinese'];
              $alipay['organizationcode'] = $res2['organizationcode'];
              $alipay['ip_address'] = $res2['ip_address'];
              $alipay['num_id'] = $res2['num_id'];
              $alipay['sign'] = $res2['sign'];
              $res2['forbid']=='0'||$alipay['panterid']='000000000000000';
              $detail = array('card'=>$card,'zzcard'=>$zzcard,'alipay'=>$alipay);
            }
            elseif($res&&$res1)
            {
              $card['imei'] =  $res['imei'];
              $card['pos_id'] =  $res['pos_id'];
              $card['channel'] = $res['channel'];
              $card['panterid'] = $res['panterid'];
              $card['namechinese'] = $res['namechinese'];
              $card['ip_address'] = $res['ip_address'];
              $card['num_id'] = $res['num_id'];
              $card['tpdu'] = $res['tpdu'];
              $res['forbid']=='0'||$card['panterid']='000000000000000';

              $zzcard['imei'] =  $res1['imei'];
              $zzcard['pos_id'] =  $res1['pos_id'];
              // $zzcard['channel'] = $res1['channel'];
              $zzcard['panterid'] = $res1['panterid'];
              $zzcard['namechinese'] = $res1['namechinese'];
              $zzcard['ip_address'] = $res1['ip_address'];
              $zzcard['num_id'] = $res1['num_id'];
              $res1['forbid']=='0'||$zzcard['panterid']='000000000000000';
              $detail = array('card'=>$card,'zzcard'=>$zzcard,'alipay'=>'0');
            }
            elseif($res&&$res2)
            {
              $card['imei'] =  $res['imei'];
              $card['pos_id'] =  $res['pos_id'];
              $card['channel'] = $res['channel'];
              $card['panterid'] = $res['panterid'];
              $card['namechinese'] = $res['namechinese'];
              $card['ip_address'] = $res['ip_address'];
              $card['num_id'] = $res['num_id'];
              $card['tpdu'] = $res['tpdu'];
              $res['forbid']=='0'||$card['panterid']='000000000000000';

              $alipay['imei'] =  $res2['imei'];
              $alipay['pos_id'] =  $res2['pos_id'];
              $alipay['channel'] = $res2['channel'];
              $alipay['panterid'] = $res2['panterid'];
              $alipay['namechinese'] = $res2['namechinese'];
              $alipay['organizationcode'] = $res2['organizationcode'];
              $alipay['ip_address'] = $res2['ip_address'];
              $alipay['num_id'] = $res2['num_id'];
              $alipay['sign'] = $res2['sign'];
              $res2['forbid']=='0'||$alipay['panterid']='000000000000000';
              $detail = array('card'=>$card,'zzcard'=>'0','alipay'=>$alipay);
            }
            else
            {
              $card['imei'] =  $res['imei'];
              $card['pos_id'] =  $res['pos_id'];
              $card['channel'] = $res['channel'];
              $card['panterid'] = $res['panterid'];
              $card['namechinese'] = $res['namechinese'];
              $card['ip_address'] = $res['ip_address'];
              $card['num_id'] = $res['num_id'];
              $card['tpdu'] = $res['tpdu'];
              $res['forbid']=='0'||$card['panterid']='000000000000000';
              $detail = array('card'=>$card,'zzcard'=>'0','alipay'=>'0');
            }
          }
          elseif($res1)
          {
            if($res1&&$res2)
            {
              $zzcard['imei'] =  $res1['imei'];
              $zzcard['pos_id'] =  $res1['pos_id'];
              // $zzcard['channel'] = $res1['channel'];
              $zzcard['panterid'] = $res1['panterid'];
              $zzcard['namechinese'] = $res1['namechinese'];
              $zzcard['ip_address'] = $res1['ip_address'];
              $zzcard['num_id'] = $res1['num_id'];
              $res1['forbid']=='0'||$zzcard['panterid']='000000000000000';

              $alipay['imei'] =  $res2['imei'];
              $alipay['pos_id'] =  $res2['pos_id'];
              $alipay['channel'] = $res2['channel'];
              $alipay['panterid'] = $res2['panterid'];
              $alipay['namechinese'] = $res2['namechinese'];
              $alipay['organizationcode'] = $res2['organizationcode'];
              $alipay['ip_address'] = $res2['ip_address'];
              $alipay['num_id'] = $res2['num_id'];
              $alipay['sign'] = $res2['sign'];
              $res2['forbid']=='0'||$alipay['panterid']='000000000000000';
              $detail = array('card'=>'0','zzcard'=>$zzcard,'alipay'=>$alipay);
            }
            else
            {
              $zzcard['imei'] =  $res1['imei'];
              $zzcard['pos_id'] =  $res1['pos_id'];
              // $zzcard['channel'] = $res1['channel'];
              $zzcard['panterid'] = $res1['panterid'];
              $zzcard['namechinese'] = $res1['namechinese'];
              $zzcard['ip_address'] = $res1['ip_address'];
              $zzcard['num_id'] = $res1['num_id'];
              $res1['forbid']=='0'||$zzcard['panterid']='000000000000000';
              $detail = array('card'=>'0','zzcard'=>$zzcard,'alipay'=>'0');
            }
          }
          else
          {
            $alipay['imei'] =  $res2['imei'];
            $alipay['pos_id'] =  $res2['pos_id'];
            $alipay['channel'] = $res2['channel'];
            $alipay['panterid'] = $res2['panterid'];
            $alipay['namechinese'] = $res2['namechinese'];
            $alipay['organizationcode'] = $res2['organizationcode'];
            $alipay['ip_address'] = $res2['ip_address'];
            $alipay['num_id'] = $res2['num_id'];
            $alipay['sign'] = $res2['sign'];
            $res2['forbid']=='0'||$alipay['panterid']='000000000000000';
            $detail = array('card'=>'0','zzcard'=>'0','alipay'=>$alipay);
          }
          //读取数据库数据
              $datas = array(
                        'code'=>1,
                        'message'=>'ok',
                        'detail'=>$detail,
                      );
          returnMsg($datas);exit;
      }
      else
      {
        returnMsg(array('code'=>'0','message'=>'请求错误'));
        exit;
      }
  }
  else
  {
    returnMsg(array('code'=>'0','message'=>'请求错误'));
    exit;
  }

 }
	public function getKey(){
		if(IS_POST){
			$datami  =  trim($_POST['datami']);
			$datami  = $this->decode($datami);
			$datami  =  json_decode($datami,1);
			$imei =  trim($datami['imei']);
			$key     = trim($datami['key']);
			$this->keycode = 'JYO2O01';
			$checkKey=md5($this->keycode.$imei);

			if($checkKey!=$key){
				returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
			}
			if(empty($imei)){
				returnMsg(array('status'=>'03','codemsg'=>'imei号不能为空'));
			}
			$map['imei'] = $imei;

			$info = M('zz_pos')->where($map)->find();
			if($info){
				if($info['keystatus']=='1') returnMsg(array('status'=>'2','codemsg'=>'秘钥已经下载'));
				$result =  M('zz_pos')->where($map)->save(['keystatus'=>'1']);
				if($result){
					returnMsg(['status'=>'1','codemsg'=>'下载秘钥成功','key'=>md5($this->keycode.$imei)]);
				}else{
					returnMsg(array('status'=>'06','codemsg'=>'下载秘钥失败'));
				}
			}else{
				returnMsg(array('status'=>'012','codemsg'=>'无效pos机'));
			}

		}else{
			returnMsg(array('status'=>'011','codemsg'=>'无效参数传入'));
		}
	}
	public function clearKey(){
		if(IS_POST){
			$datami  =  trim($_POST['datami']);
			$datami  = $this->decode($datami);
			$datami  =  json_decode($datami,1);
			$imei =  trim($datami['imei']);
			$key     = trim($datami['key']);
			$this->keycode = 'JYO2O01';
			$checkKey=md5($this->keycode.$imei);

			if($checkKey!=$key){
				returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
			}
			if(empty($imei)){
				returnMsg(array('status'=>'03','codemsg'=>'imei号不能为空'));
			}
			$map['imei'] = $imei;
			$map['keystatus'] = '1';

			$info = M('zz_pos')->where($map)->find();
			if($info){
				$result =  M('zz_pos')->where($map)->save(['keystatus'=>'0']);
				if($result){
					returnMsg(['status'=>'1','codemsg'=>'清除秘钥成功']);
				}else{
					returnMsg(array('status'=>'06','codemsg'=>'清除秘钥失败'));
				}
			}else{
				returnMsg(array('status'=>'012','codemsg'=>'无效pos机'));
			}

		}else{
			returnMsg(array('status'=>'011','codemsg'=>'无效参数传入'));
		}
	}


	protected function decode($string){
		$string=str_replace(PHP_EOL, '', $string);
		$commad = '/opt/swhsm/swhsm  dec '.$string;
		$result = exec($commad);
		$result = base64_decode($result);
		if($result===false){
			returnMsg(array('status'=>'10','codemsg'=>'非法数据传入'));
		}
		return $result;
	}
}
?>
