<?php
namespace Home\Controller;

use Org\Util\Barcode\BarcodeGeneratorPNG;
use Think\Controller;
use Org\Util\Des;
use Org\Util\YjDes;
use Think\Model;

class JyCoinController extends CoinController
{
    public function test()
    {
        //echo decode('MDA1MjQxMTAO0O0O').'<br/>';
        //echo encode('00288641').'<br/>';

        $this->redis_link();
        var_dump($this->redis);
        $res = $this->redis->ping(); 
        if ($res !== '+PONG') {
            echo '连接失败';
        } else {
            echo '连接成功';
        }

        $dingding = ['title' => '卡系统主redis', 'ip' => $_SERVER['SERVER_ADDR'], 'data' => '测试推送', 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
        // $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
        $this->https_request('https://10.1.1.82/admin/interfaces/dingding_push', json_encode($dingding));//正式 内网

        // var_dump($this->redis->keys('*'));
    }

    private $redis;
    public function redis_link()
    {
        $this->redis = new \Redis();
        try { //主
            $this->redis->connect(C("master_redis.host"), C("master_redis.port"), 2);
            // $this->redis->connect('172.16.1.8', 6379, 2);
            $this->redis->auth(C("master_redis.pwd"));

            $res = $this->redis->ping(); // 检测当前链接状态，返回PONG或者抛出异常
            if ($res !== '+PONG') {
                $dingding = ['title' => '卡系统主redis', 'ip' => $_SERVER['SERVER_ADDR'], 'data' => '主redis连接失败', 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
                // $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
                $this->https_request('https://10.1.1.82/admin/interfaces/dingding_push', json_encode($dingding));//正式 内网
            }
        } catch (\Exception $e) { //从
            $dingding = ['title' => '卡系统主redis', 'ip' => $_SERVER['SERVER_ADDR'], 'data' => '主redis连接失败', 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
            // $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
            $this->https_request('https://10.1.1.82/admin/interfaces/dingding_push', json_encode($dingding));//正式 内网
            /*try {
                $this->redis->connect('127.0.0.1', 63793, 2);
                // $redis->auth('123456');
                // $res = $this->redis->ping(); // 检测当前链接状态，返回PONG或者抛出异常
                // if ($res !== '+PONG') {
                //     echo '从连接失败';
                // }
            } catch (\Exception $e) {
                $dingding = ['title' => '卡系统从redis', 'ip' => $_SERVER['SERVER_ADDR'], 'data' => '从redis连接失败！卡系统注册接口已挂掉！', 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
                $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
                // $this->https_request('https://10.1.1.82/admin/interfaces/dingding_push', json_encode($dingding));//正式 内网
            }*/
        }
    }

    /**
     * redis lock
     *
     * @param string $key 键
     * @param integer $overtime 过期时间/秒 
     * @return integer sleep时间/秒
     */
    protected function redis_lock($key, $overtime = 10)
    {
        $this->redis_link();
        $new_time = $this->redis->setnx($key, time() + $overtime);
        $this->redis->expire($key, $overtime); //设置过期时间

        if (!$new_time) {
            $out_time = $this->redis->get($key);
            if ($out_time && $out_time < time()) { //时间戳比对是否过期
                $this->redis->del($key); //删除键
                $this->redis->setnx($key, time() + $overtime); //设置新的键
                $this->redis->expire($key, $overtime); //设置过期时间
                return 0;
            } elseif ($out_time) {
                $shleep = $out_time - time();
                return $shleep;
            }
        }
        return 0;
    }


    //创建账户接口
    public function createAccount()
    {
        $data = getPostJson();
        $datami = trim($data['datami']);
        $datami = $this->DESedeCoder->decrypt($datami);

        $this->redis_link();

        /** start 测试数据 */
        // $this->redis->incr('test_num');
        // $test_num = $this->redis->get('test_num');
        // if(($test_num%2)!=0){
        //     $moblie = '18342131' . mt_rand(100, 999);
        //     $this->redis->set('moblie1', $moblie);
        // }
        
        // $moblie = $this->redis->get('moblie1');
        // $this->redis->rpush('moblie_test', $moblie);
        // $datami = json_encode(['mobile'=> $moblie,'pwd'=>'anljYXJkMDIyZDI1NTk2MmI4MDIwODM2Nzg2YTQzZTVmNGIxY2ZqeWNvaW4=','sourceCode'=>'SOON-O2O-0001','key'=>'937c9ef9b1bfa5f2d548721efd076109']);
        /** end 测试数据 */

        //redis队列
        $this->redis->rpush('createAccount_queue', $datami);
        $datami = $this->redis->lpop('createAccount_queue');
        
        $log = '开始时间戳：'.$start_time = time()."\n\t";//总日志记录
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
        $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
        $log .= date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($datami) . "\n\t";
        $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($datami) . "\n\t", "YjTbpost", "createAccount");

        if ($datami == false) {
            returnMsg(array('status' => '07', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
        $datami = json_decode($datami, 1);
        $mobile = trim($datami['mobile']);

        $redis_key = 'zzcard_createAccount_' . $mobile;
        $second = $this->redis_lock($redis_key);
        $log .= "redis_lock__ {$mobile} __" . $second . "\n\t";
        $this->recordError("redis_lock__ {$mobile} __" . $second . "\n\t", "YjTbpost", "createAccount");
        if ($second > 0) {
            $dingding = ['title' => '至尊卡会员注册异常', 'ip' => $_SERVER['SERVER_ADDR'], 'data' => ['sleep' => $second, 'mobile' => $mobile], 'type' => '1', 'chatid' => 'chat8c23191b2c8987c202b74e58e608239a'];
            // $this->https_request('https://tongbao.9617777.com/admin/interfaces/dingding_push', json_encode($dingding));//正式 外网
            $this->https_request('https://10.1.1.82/admin/interfaces/dingding_push', json_encode($dingding));//正式 内网
            returnMsg(array('status' => '04', 'codemsg' => '信息录入失败'));
            // sleep($second);
        }

        $pwd = trim($datami['pwd']);
        $key = trim($datami['key']);
        $sourceCode = trim($datami['sourceCode']);
        $checkKey = md5($this->keycode . $mobile . $pwd . $sourceCode);
        $panterid = $this->panterArr[$sourceCode]['panterid'];
        $pname = $this->panterArr[$sourceCode]['pname'];
        $userid = $this->panterArr[$sourceCode]['userid'];
        //$str=$mobile.'--'.$pwd.'--'.date('Y-m-d H:i:s')."\r\n";
        //file_put_contents('test.txt',$str,FILE_APPEND);exit;
        if (!preg_match("/1[3456789]{1}\d{9}$/", $mobile)) {
            $this->redis->del("$redis_key"); //程序执行完成 删除该键
            returnMsg(array('status' => '01', 'codemsg' => '手机号为空或者格式不对'));
        }
        $pwd = $this->decodePwd($pwd);
        if ($pwd == false) {
            $this->redis->del("$redis_key"); //程序执行完成 删除该键
            returnMsg(array('status' => '02', 'codemsg' => '非法密码传入'));
        }
        if ($checkKey != $key) {
            $this->redis->del("$redis_key"); //程序执行完成 删除该键
            returnMsg(array('status' => '06', 'codemsg' => '无效秘钥，非法传入'));
        }
        $bool = $this->checkMobile($mobile);
        $log .= "查询是否已有该会员： {$mobile} __" . serialize($bool) . "\n\t";
        $this->recordError("查询是否已有该会员： {$mobile} __" . serialize($bool) . "\n\t", "YjTbpost", "createAccount");
        //手机号没被注册时用手机号注册会员；若存在，已绑卡的直接返回会员编号，若没绑卡进行绑卡操作
        if ($bool == true) {
            $customid = $bool;
            $map1 = array('cu.customid' => $customid);
            $cardCount = $this->customs->alias('cu')->join('customs_c cc on cu.customid=cc.customid')
                ->join('cards c on cc.cid=c.customid')->where($map1)->field('c.cardno')->count();
            if ($cardCount > 0) {
                $this->redis->del("$redis_key"); //程序执行完成 删除该键
                returnMsg(array('status' => '1', 'codemsg' => '账户创建成功', 'customid' => encode($customid)));
            }
            //returnMsg(array('status'=>'03','codemsg'=>'此号码已被注册'));
        } else {
            $customArr = array('mobile' => $mobile, 'pwd' => $pwd, 'panterid' => $panterid, 'pname' => $pname);
            $customid = $this->createCustoms($customArr);
            $log .= "注册结果：" . serialize($customid) . "\n\t";
            $this->recordError("注册结果：" . serialize($customid) . "\n\t", "YjTbpost", "createAccount");
            if ($customid == false) {
                $this->redis->del("$redis_key"); //程序执行完成 删除该键
                returnMsg(array('status' => '04', 'codemsg' => '信息录入失败'));
            }
        }
        $getCard = $this->getCard(1, $panterid);
        $this->model->startTrans();
        /*if($getCard==false){
            returnMsg(array('status'=>'06','codemsg'=>'卡池数量不足，请联系至尊'));
        }*/
        $log .= "开卡信息：" . serialize($getCard) . '--' . serialize($panterid) . '--' . serialize($userid) . "\n\t";
        $this->recordError("开卡信息：" . serialize($getCard).'--'. serialize($panterid).'--'. serialize($userid) . "\n\t", "YjTbpost", "createAccount");
        $bool = $this->openCard($getCard, $customid, 0, $panterid, 1, '', $userid, 1);
        $log .= "开卡结果：" . serialize($bool) . "\n\t";
        $this->recordError("开卡结果：" . serialize($bool) . "\n\t", "YjTbpost", "createAccount");

        $log .= '结束时间戳：'.$end_time = time() . "\n\t";//总日志记录
        $log .= '执行秒数：'. ($end_time-$start_time) . "\n\t\n\t";
        $this->recordError($log, "YjTbpost", "createAccount_log");
        if ($bool == true) {
            $this->model->commit();
            $this->redis->del("$redis_key"); //程序执行完成 删除该键
            returnMsg(array('status' => '1', 'codemsg' => '账户创建成功', 'customid' => encode($customid)));
        } else {
            $this->model->rollback();
            $this->redis->del("$redis_key"); //程序执行完成 删除该键
            returnMsg(array('status' => '05', 'codemsg' => '开卡失败'));
        }
    }
//  }

  //充值接口
  public function customRecharge()
  {
    $data = getPostJson();
    //$data=json_decode('{"datami":"ratmHaBNwSP17Jjcb1P37ee32rgWgjqUUQKhKjOjODFMR1tqV6eYToSunCrhHbugEX\/I7z75jckdUUPDCZLfJwPjsp7bs24EODJQ7oLX5V1Ytxih\/BwToEmfFPEphXwbxuXk\/5ABwj4LupQh5lCiEH+1raJ1ZoSdPIpAkNNHu17RPPGgK1jJ390rkDNt1U7bnVKPX\/RRRJ0="}',1);
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '06', 'codemsg' => '非法数据传入'));
    }
    //$datami='{"customid":"'.encode('00126804').'","amount":"100.00","sourceCode":"1001","sourceRechargeId":"SW2017061511565291554","key":"81889c3ec6410c208608d304ea4847f5"}';
    //$datami='{"customid":"'.encode('00126804').'","amount":"50.00","sourceCode":"1001","sourceRechargeId":"SW201611211205437500","key":"e801b0cee90742b8ddf3aa4edf9e9381"}';
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $amount = trim($datami['amount']);
    $key = trim($datami['key']);
    $sourceCode = trim($datami['sourceCode']);
    $sourceRechargeId = trim($datami['sourceRechargeId']);
    //$sourceCode='1001';$amount='9000';$customid='00002479';
    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $prefix = $this->panterArr[$sourceCode]['prefix'];
    $userid = $this->panterArr[$sourceCode]['userid'];
    $checkKey = md5($this->keycode . $customid . $amount . $sourceRechargeId . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) {
      returnMsg(array('status' => '02', 'codemsg' => '用户缺失'));
    }
    if (empty($sourceRechargeId)) {
      returnMsg(array('status' => '10', 'codemsg' => '缺失充值编号'));
    }
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
      returnMsg(array('status' => '03', 'codemsg' => '充值金额格式错误'));
    }
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '04', 'codemsg' => '非法会员编号'));
    }
    $map = array('description' => array('like', '%' . $prefix . $sourceRechargeId . '%'));
    $cpl = M('card_purchase_logs')->where($map)->find();
    if ($cpl != false) {
      returnMsg(array('status' => '09', 'codemsg' => '此充值号已经充值，请勿重复提交'));
    }
    $ownCards = $this->getOwnCards($customid);
    $ownCardsNum = count($ownCards);
    //$ownCardsNum=$this->customCardNum($customid);
    $customAccount = $this->accountQuery($customid, '00');
    $usableRecharge = 5000 * $ownCardsNum - $customAccount;
    $this->model->startTrans();
    if ($usableRecharge < $amount) {
      if ($usableRecharge > 0) {
        $rechargeBool = $this->recharge($ownCards, $customid, $usableRecharge, $prefix . $sourceRechargeId, $userid);
      } else {
        $rechargeBool = true;
      }
      $openRechargeAmount = $amount - $usableRecharge;
      $openNum = $this->getOpenNum($openRechargeAmount);
      $getCards = $this->getCard($openNum, $panterid);
      //echo $openNum.'<br/>';
      //print_r($getCards);
      //echo $openRechargeAmount;exit;
      if (empty($getCards)) {
        returnMsg(array('status' => '08', 'codemsg' => '卡池数量不足'));
      }
      $openBool = $this->openCard($getCards, $customid, $openRechargeAmount, $panterid, 2, $prefix . $sourceRechargeId, $userid, 1);
      if ($rechargeBool == true && $openBool == true) {
        $this->model->commit();
        returnMsg(array('status' => '1', 'codemsg' => '充值成功', 'addamount' => floatval($amount)));
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '05', 'codemsg' => '充值失败'));
      }
    } else {
      if (empty($ownCards)) {
        returnMsg(array('status' => '07', 'codemsg' => '用户无关联至尊卡'));
      }
      $rechargeBool = $this->recharge($ownCards, $customid, $amount, $prefix . $sourceRechargeId, $userid);
      if ($rechargeBool == true) {
        $this->model->commit();
        returnMsg(array('status' => '1', 'codemsg' => '充值成功', 'addamount' => floatval($amount)));
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '05', 'codemsg' => '充值失败'));
      }
    }
  }

  //消费扣款接口
  public function consume()
  {
    try {
      $data = getPostJson();
      $datami = trim($data['datami']);
      $this->recordError(date("H:i:s") . "-记录起始\n\t" . $datami . "\n\t", "YjTbpost", "info");

      $datami = $this->DESedeCoder->decrypt($datami);
      $this->recordError($datami . "\n\t", "YjTbpost", "info");

      if ($datami == false) {
        returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
      }
      $this->recordData($datami);
      $datami = json_decode($datami, 1);
      $customid = trim($datami['customid']);
      $balanceAmount = trim($datami['balanceAmount']);
      $coinAmount = trim($datami['coinAmount']);
      $payPwd = trim($datami['payPwd']);
      $key = trim($datami['key']);
      $orderid = trim($datami['orderId']);
      $backUrl = trim($datami['backUrl']);
      $sourceCode = trim($datami['sourceCode']);
      $panterid = $this->panterArr[$sourceCode]['panterid'];
      $orderPrefix = $this->panterArr[$sourceCode]['prefix'];

      #start
      $desc = '一家消费';
      $termno = '00000000';
      if (empty($panterid) && $sourceCode == '1007') { //当 标识码 为 1007 时
        $amount = $balanceAmount;
        $panterid = trim($datami['panterid']);
        $termno = trim($datami['termno']);
        $desc = '外拓消费';
      }
      #end

      $checkKey = md5($this->keycode . $customid . $balanceAmount . $coinAmount . $orderid . $payPwd . $backUrl . $sourceCode);
      if ($checkKey != $key) {
        returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
      }
      if (empty($customid)) {
        returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
      }
      if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $balanceAmount)) {
        returnMsg(array('status' => '04', 'codemsg' => '消费金额格式有误'));
      }
      if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $coinAmount)) {
        returnMsg(array('status' => '05', 'codemsg' => '建业通宝格式有误'));
      }
      if ($balanceAmount == 0 && $coinAmount == 0) {
        returnMsg(array('status' => '06', 'codemsg' => '消费数据有误'));
      }
      if (empty($orderid)) {
        returnMsg(array('status' => '07', 'codemsg' => '订单编号缺失'));
      }
      $customid = decode($customid);
      $bool = $this->checkCustom($customid);
      if ($bool == false) {
        returnMsg(array('status' => '08', 'codemsg' => '非法会员编号'));
      }
      $paypwd = $this->decodePwd($payPwd);
      if ($paypwd == false) {
        returnMsg(array('status' => '012', 'codemsg' => '非法密码传入'));
      }
      $pwdBool = $this->checkPayPwd($customid, $paypwd);
      if ($pwdBool === '01') {
        returnMsg(array('status' => '013', 'codemsg' => '支付密码错误'));
      } elseif ($pwdBool === '02') {
        returnMsg(array('status' => '016', 'codemsg' => '当天密码错误次数超过三次'));
      }
      //        $customArr= S('customArr');
      //        if(in_array($customArr,$customid)){
      //            returnMsg(array('status'=>'017','codemsg'=>'重复支付订单'));
      //        }
      //        $customArr[]=$customid;
      //        S('customArr',$customArr);
      $tradeLogs = M('trade_logs');
      $where = array('customid' => $customid, 'placeddate' => date('Ymd'), "type" => '2');
      $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
      $date = time();
      if ($trade_logs_list != false) {
        if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
          returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
        }
      }
      $currentDate = date('Ymd');
      $currentTime = date('H:i:s');
      $orderType = '2';
      $sql = "insert into trade_logs (customid,CODECUSTOMID,placeddate,placedtime,datetimes,orderid,type) values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}','{$orderType}')";
      $this->recordError($sql . "\n\t", "YjTbpost", "info");
      $tradeLogs->execute($sql);
      unset($sql);

      #start
      $provisions = $this->accountQuery($customid, '00'); //备付金
      $zmoney = $this->zzkaccount($customid); //自有资金
      $balanceAccount = $provisions + $zmoney['money'];
      $this->recordError('备付金：' . $provisions . '  自有资金：' . serialize($zmoney) . '  总资金：' . $balanceAccount . "\n\t", "YjTbpost", "info");
        #end
        //总资金<消费资金 时查询钱包的钱
        if ($balanceAccount < $balanceAmount) {
            //目前根据需求需要把钱包付款取缔 所以钱包返回值改为0  原程序 $walletAccount = $this->getWalletAccount($customid);
            $walletAccount = 0;
            //$walletAccount 钱包
            if ($walletAccount !== false) {
                //此处不做修改
                if (($balanceAccount + $walletAccount) < $balanceAmount) {
                    returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
                }
            } else {
                returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
            }
        }
      //$coinAccount=$this->accountQuery($customid,'01');
      $coinAccount = $this->coinQuery($customid);
      if ($coinAccount < $coinAmount) {
        returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
      }
      $this->model->startTrans();
      $balanceConsumeIf = $coinConsumeIf = false;

      //余额消费，金额为0不执行
      if ($balanceAmount > 0) {
        $map = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '00');
        $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradeamount');
        if ($balanceConsume > 0) {
          returnMsg(array('status' => '014', 'codemsg' => '此订单已进行余额支付，请勿重复提交'));
        }
        #start  $balanceAmount 要扣除的金额
        //zzk_order 订单信息 主键ID order_sn订单号 tradetype交易方式 panterid商户id storeid门店id source来源 amount金额
        $zzk_orderid = $this->zzkgetnumstr('zzk_orderid', 16);
        $inner_order = $this->zzktradeid('05', '05', $zmoney['accountid']);
        $placeddate = date('Ymd');
        $placedtime = date('H:i:s');
        $orderPrefixorderid = $orderPrefix . '' . $orderid;
        $combined = 0;

        $provisions_walletAccount = $provisions;
        if ($sourceCode == '1001') { //一家
          $provisions_walletAccount = $provisions + $walletAccount; //备付金 + 钱包
        }

        $zzkamount = 0;
        if ($provisions_walletAccount < $balanceAmount) {
          $zzkamount = $balanceAmount - $provisions_walletAccount;
          $balanceAmount = $provisions_walletAccount;
          $zmoney['inner_order'] = $inner_order;
          $zmoney['orderid'] = $orderPrefix . $orderid;
          $zmoney['placeddate'] = $placeddate;
          $zmoney['placedtime'] = $placedtime;
          $zmoney['panterid'] = $panterid;
          $zmoney['desc'] = $desc;
          $rs = $this->equityfund($zzkamount, $zmoney);
          if ($rs['status'] != 1) {
            returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
          }
          if ($provisions_walletAccount > 0) {
            $combined = 1;
          }
        }

        $sql = "INSERT INTO zzk_order(combined,orderid,tradetype,order_sn,placeddate,placedtime,panterid,storeid,accountid,inner_order,source,amount,paytype,description) values ";
        $sql .= "('{$combined}','{$zzk_orderid}','50','{$orderPrefixorderid}','{$placeddate}','{$placedtime}','{$panterid}','{$panterid}','{$zmoney[accountid]}','{$inner_order}','06','{$datami[balanceAmount]}','05','{$desc}')";
        $this->recordError($sql . "\n\t", "YjTbpost", "info");
        $orderInfo = $this->model->execute($sql);

        if ($balanceAmount > $provisions) {
          $walletConsumeAmount = bcsub($balanceAmount, $provisions, 2);
          $balanceConsumeAmount = bcsub($balanceAmount, $walletConsumeAmount, 2);
        } else {
          $walletConsumeAmount = 0;
          $balanceConsumeAmount = $balanceAmount;
        }
        $this->recordError('要扣除的备付金：' . $balanceConsumeAmount . '  要扣除的钱包：' . $walletConsumeAmount . "\n\t", "YjTbpost", "info");

        // if ($balanceAmount > $balanceAccount) {
        //     $walletConsumeAmount = bcsub($balanceAmount, $balanceAccount, 2);
        //     $balanceConsumeAmount = bcsub($balanceAmount, $walletConsumeAmount, 2);
        // } else {
        //     $walletConsumeAmount = 0;
        //     $balanceConsumeAmount = $balanceAmount;
        // }
        #end

        $balanceConsumeArr = array(
          'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
          'panterid' => $panterid, 'type' => '00', 'amount' => $balanceConsumeAmount,
          'walletConsumeAmount' => $walletConsumeAmount, 'termno' => $termno
        ); #start 新增 termno #end
        $this->recordError('consumeExe：' . serialize($balanceConsumeArr) . "\n\t", "YjTbpost", "info");
        $balanceConsumeIf = $this->consumeExe($balanceConsumeArr);

        //var_dump($balanceConsumeIf);exit;
        if ($walletConsumeAmount > 0 && $balanceConsumeIf == true) {
          $wallet = $this->model->table('wallet')->where(array('customid' => $customid))->find();
          $storeid = $wallet['storeid'];
          $custom = $this->model->table('customs')->where(array('customid' => $customid))->field('namechinese')->find();
          $key = md5($this->keycode . $storeid . $walletConsumeAmount . encode($customid) . $orderid . $custom['namechinese']);
          $sendData = array(
            'storeid' => $storeid, 'amount' => $walletConsumeAmount, 'customid' => encode($customid), 'uniqid' => $orderid,
            'name' => $custom['namechinese'], 'key' => $key
          );
          $datami = $this->DESedeCoder->encrypt(json_encode($sendData));
          $datami = json_encode(array('datami' => $datami));
          $url = C('zfjsIp') . '/admin/consume';
          $res = crul_post($url, $datami);
        }
      } else {
        $balanceConsumeIf = true;
      }
      //建业币消费，金额为0不执行
      if ($coinAmount > 0) {
        $map = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '00');
        $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradepoint');
        //echo M('trade_wastebooks')->getLastSql();exit;
        if ($balanceConsume > 0) {
          returnMsg(array('status' => '015', 'codemsg' => '此订单已进行建业通宝支付，请勿重复提交'));
        }

        $coinConsumeArr = array(
          'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
          'panterid' => $panterid, 'type' => '01', 'amount' => $coinAmount, 'termno' => $termno
        ); #start 新增 termno #end
        //$coinConsumeIf=$this->consumeExe($coinConsumeArr);
        $this->recordError('consumeCoin：' . serialize($coinConsumeArr) . "\n\t", "YjTbpost", "info");
        $coinConsumeIf = $this->consumeCoin($coinConsumeArr);
        if ($coinConsumeIf) {
          //---------向一家传输用户通宝信息--17:21----
          $de = new YjDes();
          $tb_info = D("Ehome")->getTbinfo($customid);
          if ($tb_info) {
            $appid = 'SOON-ZZUN-0001';
            $tb_info['customid'] = encode($tb_info['customid']);
            $tb_info['activetype'] = 2;
            $tb_info['appid'] = $appid;
            $tb_sign = $de->encrypt($tb_info);
            $tb_data = json_encode($tb_info, JSON_FORCE_OBJECT);
            $return_yj = $this->curlPost(C('ehome_potb'), $tb_data, $tb_sign);
            $return_arr = json_decode($return_yj, 1);

            if ($return_arr['code'] == '100') {
              $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "success");
            } else {
              $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "failed");
            }
          }

          //---------end-----------------
        }
      } else {
        $coinConsumeIf = true;
      }
      if ($balanceConsumeIf == true && $coinConsumeIf == true) {
        $this->model->commit();
        //            $customArr= S('customArr');
        //            foreach($customArr as $key=>$val){
        //                if($val==$customid){
        //                    unsert($customArr[$key]);
        //                }
        //            }
        //S('customArr',$customArr);
        ob_clean();
        #start
        $hbinfo = array();
        if (empty($coinAmount) && $sourceCode == 1007) {
          $hbinfo = $this->getHbGiftAmount($amount, $panterid, $orderid, $customid);
        }
        #end
        echo $str = json_encode(array('status' => '1', 'info' => array('reduceBalance' => floatval($balanceAmount + $zzkamount), 'reduceCoin' => floatval($coinAmount)), 'publish' => $coinConsumeIf, 'hginfo' => $hbinfo)); #start 新增$zzkamount #end
        $this->recordError('返回信息：' . $str . "\n\t\r\r", "YjTbpost", "info");
        if (!empty($backUrl)) {
          $backUrl = urldecode($backUrl);
          $backData = array('orderid' => $orderid, 'consumeAmount' => $balanceAmount, 'coinAmount' => $coinAmount, 'payRes' => 1);
          crul_post($backUrl, json_encode($backData));
          $this->recordMsg($orderid, $balanceAmount, $coinAmount, $backUrl);
        }
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
      }
    } catch (\Exception $e) {
      $this->recordError(date("H:i:s") . '-' . $e . "\n\t", "YjTbpost", "yichang");
      returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
    }
  }

  #start
  public function getHbGiftAmount($amount, $panterid, $orderid, $customid)
  {
    if ($amount < 10) return 0;
    $hbConig = M('hbrules')->where(array('panterid' => $panterid, 'gtype' => 2, 'is_on' => 1, 'enddate' => array('egt', date('Ymd'))))->find();
    if ($hbConig == false) return false;
    $giftConsumeAmount = round($amount * $hbConig['rate'] / 100, 2);
    $hgid = $this->getFieldNextNumber('hbid');
    $sql = "insert into hb_logs values('{$hgid}','{$customid}','{$giftConsumeAmount}','{$panterid}','{$orderid}',1,0)";
    $this->model->execute($sql);
    return array('hbid' => $hgid, 'amount' => floatval($giftConsumeAmount));
  }
  #end

  //获取订单支付状态
  public function getPayInfo()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $orderid = trim($datami['orderId']);
    $balanceAmount = trim($datami['balanceAmount']);
    $coinAmount = trim($datami['coinAmount']);
    $sourceCode = trim($datami['sourceCode']);
    $key = trim($datami['key']);
    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $checkKey = md5($this->keycode . $orderid . $balanceAmount . $coinAmount . $sourceCode);

    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    //$orderid='201512281513';
    //$amount='6000';
    if (empty($orderid)) {
      returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
    }
    $map['eorderid'] = $orderPrefix . $orderid;
    $map['flag'] = 0;
    $map['tradetype'] = '00';
    $payInfo = M('trade_wastebooks')->where($map)
      ->field("sum(tradeamount) consumebalance,sum(tradepoint) consumecoin")->find();
    if ($payInfo == false) {
      returnMsg(array('status' => '04', 'codemsg' => '无扣款信息'));
    }
    //echo floatval($payInfo['consumebalance']).'--'.$balanceAmount;exit;
    if (floatval($payInfo['consumebalance']) != $balanceAmount) {
      returnMsg(array('status' => '05', 'codemsg' => '账户消费金额与订单金额不一致'));
    }
    //        if(floatval($payInfo['consumecoin'])!=$coinAmount){
    //            returnMsg(array('status'=>'06','codemsg'=>'建业币消费金额与订单金额不一致'));
    //        }
    returnMsg(array('status' => '1', 'payInfo' => array('consumebalance' => floatval($payInfo['consumebalance']), 'consumecoin' => floatval($payInfo['consumecoin']))));
  }

  //查询账户余额接口
  public function getBalance()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '04', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '用户编号缺失'));
    }
    $checkKey = md5($this->keycode . $customid);
    if ($checkKey != $key) {
      returnMsg(array('status' => '03', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '02', 'codemsg' => '用户不存在'));
    }
    $balance = $this->accountQuery($customid, '00');
    file_put_contents('./aa12.txt', $balance);
    $map = array('customid' => $customid);
    $list = M('wallet')->where($map)->find();
    if ($list != false) {
      $balance = floatval($balance) + floatval($list['amount']);
    }
    returnMsg(array('status' => '1', 'balance' => floatval($balance)));
  }

  //查询账户信息接口(通过用户编号)
  public function getAccount()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '04', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    //$customid=trim('MDAwMDAzNTYO0O0O ');
    $key = trim($datami['key']);
    if (empty($customid)) {
      $this->recoreIp();
      returnMsg(array('status' => '01', 'codemsg' => '用户缺失'));
    }
    $checkKey = md5($this->keycode . $customid);
    if ($checkKey != $key) {
      returnMsg(array('status' => '03', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '02', 'codemsg' => '用户不存在'));
    }
    //账户信息
    $accountInfo = $this->accountInfo($customid);

    $map['customid'] = $customid;
    $custom = $this->customs->where($map)->find();
    $hasPaypwd = empty($custom['paypwd']) ? 0 : 1;
    //账户绑卡信息
    $bindCards = $this->getBindCards($customid);
    if ($bindCards == false) {
      $bindList = '';
    } else {
      $bindList = $bindCards;
    }
    $map = array('customid' => $customid);
    #start
    $money = $this->zzkaccount($customid);
    if ($money != false) {
      $accountInfo['balance'] = floatval($accountInfo['balance']) + floatval($money['money']);
    }
    #end
    $list = M('wallet')->where($map)->find();
    if ($list != false) {
      $accountInfo['balance'] = floatval($accountInfo['balance']) + floatval($list['amount']);
    }
    $map1 = array('cu.customid' => $customid, 'c.cardkind' => '6889', 'c.cardfee' => array('in', array(1, 2)));
    $cardList = $this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
      ->join('cards c on c.customid=cc.cid')->where($map1)->field('c.cardno,c.status')->select();
    if ($cardList != false) {
      $isActive = 1;
      $c = 0;
      foreach ($cardList as $key => $val) {
        if ($val['status'] == 'A') {
          $c++;
        }
      }
      if ($c == count($cardList)) $isActive = 0;
    } else {
      $isActive = 2;
    }
    $expired = $this->getExpiredCoinByCustomid($customid);
    if ($expired) {
      returnMsg(
        array(
          'status' => '1', 'balance' => floatval($accountInfo['balance']),
          'jycoin' => floatval($accountInfo['jycoin']), 'hasPaypwd' => $hasPaypwd,
          'bindList' => $bindList, 'isActive' => $isActive, 'amount' => $expired['amount'],
          'enddate' => $expired['enddate'], 'days' => $expired['days']
        )
      );
    } else {
      returnMsg(
        array(
          'status' => '1', 'balance' => floatval($accountInfo['balance']),
          'jycoin' => floatval($accountInfo['jycoin']), 'hasPaypwd' => $hasPaypwd,
          'bindList' => $bindList, 'isActive' => $isActive
        )
      );
    }
  }

  //查询账户信息接口(通过手机号)
  public function getAccountByMobile()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '05', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $linktel = trim($datami['mobile']);
    $key = trim($datami['key']);
    if (!preg_match("/1[34578]{1}\d{9}$/", $linktel)) {
      returnMsg(array('status' => '01', 'codemsg' => '手机号为空或者格式不对'));
    }
    $checkKey = md5($this->keycode . $linktel);
    if ($checkKey != $key) {
      returnMsg(array('status' => '04', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map['cu.linktel'] = $linktel;

    $custom = $this->customs->alias('cu')->join('cards c on cu.customid=c.customid')
      ->field('cu.customid,c.cardno')->where($map)->find();
    if ($custom == false) {
      returnMsg(array('status' => '02', 'codemsg' => '该手机尚未绑定账户'));
    }
    if ($custom['customlevel'] == 'e+会员') {
      returnMsg(array('status' => '03', 'codemsg' => '该手机已绑定，无需重复绑定'));
    }
    $customid = $custom['customid'];
    returnMsg(array('status' => '1', 'cardno' => $custom['cardno'], 'customid' => encode($custom['customid'])));
  }

  //设置支付密码
  public function setPayPwd()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '06', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $paypwd = trim($datami['paypwd']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
    }
    if ($paypwd == false) {
      returnMsg(array('status' => '02', 'codemsg' => '非法密码传入'));
    }
    //        if(!preg_match("/\d{6,}$/",$paypwd)){
    //            returnMsg(array('status'=>'02','codemsg'=>'密码格式错误'));
    //        }
    $checkKey = md5($this->keycode . $customid . $paypwd);
    if ($checkKey != $key) {
      returnMsg(array('status' => '05', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '03', 'codemsg' => '无效会员'));
    }
    $paypwd = $this->decodePwd($paypwd);
    $map = array('customid' => $customid);
    //$data=array('paypwd'=>md5($paypwd));
    $data = array('paypwd' => $paypwd);
    $customIf = $this->customs->where($map)->save($data);
    if ($customIf == false) {
      returnMsg(array('status' => '04', 'codemsg' => '设置失败'));
    } else {
      returnMsg(array('status' => '1', 'codemsg' => '设置成功'));
    }
  }

  //校验支付密码
  public function examPayPwd()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '06', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $paypwd = trim($datami['paypwd']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
    }
    $checkKey = md5($this->keycode . $customid . $paypwd);
    if ($checkKey != $key) {
      returnMsg(array('status' => '04', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '02', 'codemsg' => '无效会员'));
    }
    $paypwd = $this->decodePwd($paypwd);
    if ($paypwd == false) {
      returnMsg(array('status' => '05', 'codemsg' => '非法密码传入'));
    }
    $pwdBool = $this->checkPayPwd($customid, $paypwd);
    if ($pwdBool === '01') {
      returnMsg(array('status' => '03', 'codemsg' => '支付密码错误'));
    } elseif ($pwdBool === '02') {
      returnMsg(array('status' => '07', 'codemsg' => '当天密码错误次数超过三次'));
    } elseif ($pwdBool === 1) {
      returnMsg(array('status' => '1', 'codemsg' => '校验通过'));
    }
    //        if($pwdBool==false){
    //            returnMsg(array('status'=>'03','codemsg'=>'支付密码错误'));
    //        }else{
    //            returnMsg(array('status'=>'1','codemsg'=>'校验通过'));
    //        }
  }

  //校验是否支付密码
  public function hasPayPwd()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '05', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
    }
    $checkKey = md5($this->keycode . $customid);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '03', 'codemsg' => '无效会员'));
    }
    $map['customid'] = $customid;
    $custom = $this->customs->where($map)->find();
    if ($custom['paypwd'] == '') {
      returnMsg(array('status' => '04', 'codemsg' => '未设置支付密码'));
    } else {
      returnMsg(array('status' => '1', 'codemsg' => '支付密码已设置'));
    }
  }

  //修改支付密码
  public function editPayPwd()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '07', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $oldpwd = trim($datami['oldpwd']);
    $newpwd = trim($datami['newpwd']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
    }
    $checkKey = md5($this->keycode . $customid . $oldpwd . $newpwd);
    if ($checkKey != $key) {
      returnMsg(array('status' => '06', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '02', 'codemsg' => '无效会员'));
    }
    $oldpwd = $this->decodePwd($oldpwd);
    $newpwd = $this->decodePwd($newpwd);
    if ($oldpwd == false || $newpwd == false) {
      returnMsg(array('status' => '04', 'codemsg' => '非法密码传入'));
    }
    $pwdBool = $this->checkPayPwd($customid, $oldpwd);
    if ($pwdBool === '01') {
      returnMsg(array('status' => '03', 'codemsg' => '旧支付密码错误'));
    } elseif ($pwdBool === '02') {
      returnMsg(array('status' => '08', 'codemsg' => '旧密码错误次数超过三次'));
    }
    //        if(!preg_match("/\d{6,}$/",$newpwd)){
    //            returnMsg(array('status'=>'04','codemsg'=>'新密码格式错误'));
    //        }
    $customMap = array('customid' => $customid);
    //$pwdData=array('paypwd'=>md5($newpwd));
    $pwdData = array('paypwd' => $newpwd);
    if ($this->customs->where($customMap)->save($pwdData)) {
      returnMsg(array('status' => '1', 'codemsg' => '修改成功'));
    } else {
      returnMsg(array('status' => '05', 'codemsg' => '修改失败'));
    }
  }

  //重置支付密码
  public function resetPayPwd()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '06', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $newpwd = trim($datami['newpwd']);
    $key = trim($datami['key']);
    if (empty($customid)) {
      returnMsg(array('status' => '01', 'codemsg' => '会员编号缺失'));
    }
    $checkKey = md5($this->keycode . $customid . $newpwd);
    if ($checkKey != $key) {
      returnMsg(array('status' => '05', 'codemsg' => '无效秘钥，非法传入'));
    }
    $customid = decode($customid);
    $customBool = $this->checkCustom($customid);
    if ($customBool == false) {
      returnMsg(array('status' => '02', 'codemsg' => '无效会员'));
    }
    $newpwd = $this->decodePwd($newpwd);
    if ($newpwd == false) {
      returnMsg(array('status' => '03', 'codemsg' => '非法密码传入'));
    }
    //        if(!preg_match("/\d{6,}$/",$newpwd)){
    //            returnMsg(array('status'=>'03','codemsg'=>'新密码格式错误'));
    //        }
    $customMap = array('customid' => $customid);
    $pwdData = array('paypwd' => $newpwd);
    if ($this->customs->where($customMap)->save($pwdData)) {
      returnMsg(array('status' => '1', 'codemsg' => '重置成功'));
    } else {
      returnMsg(array('status' => '04', 'codemsg' => '重置失败'));
    }
  }

  //绑定老卡
  public function cardBind_bak()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '09', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    //$datami='{"cardno":"6889379600000219671","pwd":"anljYXJkMjg3Mzg5anljb2lu","key":"8f31946b9f0894d522a682a82ba62d0a","customid":"MDA0NjUyNDAO0O0O","sourceCode":"1001"}';
    $datami = json_decode($datami, 1);
    $cardno = trim($datami['cardno']);
    $pwd = trim($datami['pwd']);
    $customid = trim($datami['customid']);
    $key = trim($datami['key']);
    $sourceCode = trim($datami['sourceCode']);

    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $checkKey = md5($this->keycode . $customid . $cardno . $pwd . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map = array('cardno' => $cardno);
    $card = $this->cards->where($map)->find();
    //returnMsg(array('test'=>$this->cards->getLastSql()));exit;
    if ($card == false) {
      returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
    }
    $pwd = $this->decodePwd($pwd);
    if ($pwd == false) {
      returnMsg(array('status' => '08', 'codemsg' => '非法密码传入'));
    }
    //$url = C('netApiUrl');
    //$url='http://122.0.82.130:8087/Web/login.ashx';//正式环境
    //$url='http://192.168.10.50/Web/login.ashx';//老正式环境
    //$data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
    //$res=crul_post($url,$data);
    //$res=json_decode($res,1);
    //print_r($res);exit;

    $des = new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    $pwd = $des->doEncrypt($pwd);
    if ($pwd != $card['cardpassword']) {
      returnMsg(array('status' => '03', 'codemsg' => '密码错误'));
    }
    $field = 'c.customid cid,cu.customlevel,c.brandid,a.amount,cu.namechinese,cu.personidtype,cu.personid,';
    $field .= 'cu.nameenglish,cu.linkman,cu.linktel,cu.sex,cu.birthday,c.status,c.cardkind,a1.amount coin,cu.customid custid';
    //查询老卡关联会员信息
    $where['c.cardno'] = $cardno;
    $where['a.type'] = '00';
    $where['a1.type'] = '01';
    $card = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
      ->join('customs cu on cu.customid=cc.customid')
      ->join('account a on c.customid=a.customid')
      ->join('account a1 on c.customid=a1.customid')
      ->where($where)->field($field)->find();

    $map1 = array('cardno' => $cardno, 'status' => 1);
    $bindList = M('ecard_bind')->where($map1)->select();
    //echo $this->cards->getLastSql();exit;
    if ($card['status'] != 'Y' && $card['status'] != 'A') {
      returnMsg(array('status' => '04', 'codemsg' => '非正常卡'));
    }
    if ($card['cardkind'] == '6882' || $card['cardkind'] == '2081' || $card['cardkind'] == '6688') {
      returnMsg(array('status' => '05', 'codemsg' => '酒店卡，不允许绑定'));
    }
    if ($bindList != false) {
      returnMsg(array('status' => '06', 'codemsg' => '此卡已绑定'));
    }
    $custid = $card['custid'];
    $this->model->startTrans();
    //将绑定卡关联至注册会员账户上；
    $customid = decode($customid);
    $customCData = array('customid' => $customid);
    $where2 = array('cid' => $card['cid']);
    $customCIf = $this->customs_c->where($where2)->save($customCData);

    //绑定卡的会员信息一直到注册的会员信息中
    $where3['customid'] = $customid;
    $custom = $this->customs->where($where3)->find();
    if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
      $data3 = array(
        'namechinese' => $card['namechinese'],
        'nameenglish' => $card['nameenglish'],
        'personidtype' => $card['personidtype'],
        'personid' => $card['personid'],
        'linkman' => $card['linkman'],
        'sex' => $card['sex'],
        'birthday' => $card['birthday']
      );
      $customIf = $this->customs->where($where3)->save($data3);
    } else {
      $customIf = true;
    }
    $addtime = date('Y-m-d H:i:s');
    $sql = "INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}','{$addtime}','{$custid}')";
    $ecardBindIf = $this->model->execute($sql);

    if ($customCIf == true && $customIf == true && $ecardBindIf == true) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '绑定成功', 'addamount' => floatval($card['amount']), 'addcoin' => floatval($card['coin'])));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '07', 'codemsg' => '绑定失败'));
    }
  }

  public function cardBindtest()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      //returnMsg(array('status'=>'09','codemsg'=>'非法数据传入'));
    }
    $this->recordData('elsa1:' . $datami);
    //$datami='{"cardno":"6889379600000219671","pwd":"anljYXJkMjg3Mzg5anljb2lu","key":"8f31946b9f0894d522a682a82ba62d0a","customid":"MDA0NjUyNDAO0O0O","sourceCode":"1001"}';
    $datami = '{"customid":"MDAxMTY0MDcO0O0O","cardno":"2336370888801607975","pwd":"anljYXJkODg4ODg4anljb2lu","sourceCode":"SOON-O2O-0001","key":"41f8a161a1b4fbb95339943d8fe1a294"}';
    $datami = json_decode($datami, 1);
    $cardno = trim($datami['cardno']);
    $pwd = trim($datami['pwd']);
    $customid = trim($datami['customid']); //新绑会员
    $key = trim($datami['key']);
    $sourceCode = trim($datami['sourceCode']);

    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $checkKey = md5($this->keycode . $customid . $cardno . $pwd . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map = array('cardno' => $cardno);
    $card = $this->cards->where($map)->find();
    //returnMsg(array('test'=>$this->cards->getLastSql()));exit;
    if ($card == false) {
      returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
    }
    $pwd = $this->decodePwd($pwd);
    if ($pwd == false) {
      returnMsg(array('status' => '08', 'codemsg' => '非法密码传入'));
    }
    //$url = C('netApiUrl');
    //$url='http://122.0.82.130:8087/Web/login.ashx';//正式环境
    //$url='http://192.168.10.50/Web/login.ashx';//老正式环境
    //$data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
    //$res=crul_post($url,$data);
    //$res=json_decode($res,1);
    //print_r($res);exit;

    $des = new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    $pwd = $des->doEncrypt($pwd);
    if ($pwd != $card['cardpassword']) {
      returnMsg(array('status' => '03', 'codemsg' => '密码错误'));
    }
    $binStr = "bindcard starting..." . "\n";
    //绑定至尊卡礼品卡处理
    if ($card['cardkind'] == '2336') {
      $binStr .= '2336绑卡：' . serialize($card) . "\n";
      if ($card['status'] != 'Y' && $card['status'] != 'A') {
        returnMsg(array('status' => '004', 'codemsg' => '非正常卡'));
      }
      $map1 = array('cardno' => $cardno, 'status' => 1);
      $bindList = M('ecard_bind')->where($map1)->select();
      if ($bindList != false) {
        returnMsg(array('status' => '006', 'codemsg' => '此卡已绑定'));
      }
      $where['c.status'] = 'Y';
      $where['c.cardno'] = $cardno;
      $field = 'c.cardno,c.customid,cu.customid cuid';
      $custom_data = $this->cards->alias('c')
        ->join(' left join customs_c cc on cc.cid=c.customid')
        ->join(' left join customs cu on cu.customid=cc.customid')
        ->where($where)->field($field)->find();
      //  dump($custom_data);exit;
      if (!$custom_data) {
        $binStr .= '卡会员不存在，请核实！';
        $this->recordData($binStr);
        returnMsg(array('status' => '007', 'codemsg' => '卡会员不存在，请核实！'));
      } else {
        $where1['cu.customid'] = $custom_data['cuid'];
        $where1['a.type'] = '00';
        $where1['a1.type'] = '01';
        $fields = 'c.cardno,c.status,c.cardkind,c.cardfee,c.customid,';
        $fields .= 'cu.namechinese cuname,cu.personid,cu.linktel,cu.customid cuid,p.namechinese pname,a.amount cardbalance,a1.amount cardpoint';
        $card_list = $this->cards->alias('c')
          ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
          ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
          ->join('left join __PANTERS__ p on p.panterid=c.panterid')
          ->join('__ACCOUNT__ a on a.customid=c.customid')
          ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
          ->where($where1)->field($fields)->select();
        // dump($card_list);
        $array = array();
        foreach ($card_list as $k => $v) {
          $array[$v['cardkind']][] = $v;
        }
        $arr = array();
        foreach ($array as $key => $val) {
          // dump(count($val));
          if (count($val) > 2) {
            $strarr = array();
            foreach ($val as $ve) {
              if ($ve['cardfee'] == "1") {
                $strarr[] = $ve;
              }
            }
          } else {
            $arr[] = $val;
          }
        }
        if ($strarr) {
          foreach ($strarr as $a_k => $a_v) {
            if (count($a_v) > 2) {
              $str1 = 'cardno:' . $cardno . "\t,array:" . $strarr . "\t,";
              $str1 .= date('Y-m-d H:i:s', time()) . "\t,";
              $this->writeLogs('bangcard', $str1);
              $binStr .= '至尊实体卡绑卡存在异常，请联系客服解决';
              $this->recordData($binStr);
              returnMsg(array('status' => '07', 'codemsg' => '绑卡异常，请联系客服解决！'));
            }
          }
        }
        $bang_data = array();
        foreach ($arr as $b_k => $b_v) {
          $bang_data = $b_v;
        }
        $this->model->startTrans();
        $customid = decode($customid); //新绑会员
        $binStr .= '获取绑卡列表' . serialize($bang_data) . "\n";
        $amount = 0;
        foreach ($bang_data as $c_k => $c_v) { //循环会员下卡号
          // dump($c_v);exit;
          // 查询卡号是否消费了
          $binStr .= '绑卡处理：' . serialize($c_v) . "\n";
          $where8['cardno'] = $c_v['cardno'];
          $old_customid = $c_v['customid'];
          $card_data = M('trade_wastebooks')->where($where8)->find(); //如果有交易记录则变更到要绑定的会员下
          //   print_r($card_data);exit;
          if ($card_data) {
            $binStr .= '变更交易记录：' . serialize($card_data) . "\n";
            if ($card_data['customid'] != $customid) {
              $cu['customid'] = $customid;
              $trade_data = M('trade_wastebooks')->where($where8)->save($cu);
            } else {
              $trade_data = true;
            }

            if ($trade_data) {
              $customCData = array('customid' => $customid);
              $where2 = array('cid' => $old_customid);
              $customCIf = $this->customs_c->where($where2)->save($customCData);

              //绑定卡的会员信息一直到注册的会员信息中
              $where3['customid'] = $customid;
              $custom = $this->customs->where($where3)->find();
              if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
                $data3 = array(
                  'namechinese' => $card['namechinese'],
                  'nameenglish' => $card['nameenglish'],
                  'personidtype' => $card['personidtype'],
                  'personid' => $card['personid'],
                  'linkman' => $card['linkman'],
                  'sex' => $card['sex'],
                  'birthday' => $card['birthday']
                );
                $customIf = $this->customs->where($where3)->save($data3);
              } else {
                $customIf = true;
              }
              $addtime = date('Y-m-d H:i:s');
              $bindcard = $c_v['cardno'];
              $sql = "INSERT INTO ECARD_BIND VALUES('{$bindcard}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
              $ecardBindIf = $this->model->execute($sql);

              if ($customCIf == true && $customIf == true && $ecardBindIf == true) {

                $binStr .= '绑定成功:' . serialize($c_v);
                $amount = $amount + $c_v['cardbalance'];

                //returnMsg(array('status'=>'1','codemsg'=>'绑定成功','addamount'=>floatval($card['amount']),'addcoin'=>floatval($card['coin'])));
              } else {
                $this->model->rollback();
                $binStr .= '绑定失败:' . serialize($c_v);
                $this->recordData($binStr);
                returnMsg(array('status' => '017', 'codemsg' => '绑定失败'));
              }
            } else {
              $this->model->rollback();
              $binStr .= '更改消费记录失败:' . serialize($c_v);
              $this->recordData($binStr);
              returnMsg(array('status' => '0008', 'codemsg' => '更改消费记录失败'));
            }
          } else {
            $binStr .= '无交易记录变更！' . "\n";
            $customCData = array('customid' => $customid);
            $where2 = array('cid' => $old_customid);
            $customCIf = $this->customs_c->where($where2)->save($customCData);
            $sql1 = $this->customs_c->getLastSql();
            //绑定卡的会员信息一直到注册的会员信息中
            $where3['customid'] = $customid;
            $custom = $this->customs->where($where3)->find();
            if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
              $data3 = array(
                'namechinese' => $card['namechinese'],
                'nameenglish' => $card['nameenglish'],
                'personidtype' => $card['personidtype'],
                'personid' => $card['personid'],
                'linkman' => $card['linkman'],
                'sex' => $card['sex'],
                'birthday' => $card['birthday']
              );
              $customIf = $this->customs->where($where3)->save($data3);
              $sql2 = $this->customs->getLastSql();
            } else {
              $customIf = true;
            }
            $addtime = date('Y-m-d H:i:s');
            $bindcard = $c_v['cardno'];
            $sql = "INSERT INTO ECARD_BIND VALUES('{$bindcard}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
            // $sql="INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
            $ecardBindIf = $this->model->execute($sql);
            $sql3 = $this->model->getLastSql();
            if ($customCIf == true && $customIf == true && $ecardBindIf == true) {
              $binStr .= '绑卡成功' . serialize($c_v) . '---sql:' . $sql1 . "\n";
              $amount = $amount + $c_v['cardbalance'];
              // returnMsg(array('status'=>'1','codemsg'=>'绑定成功','addamount'=>floatval($card['amount']),'addcoin'=>floatval($card['coin'])));
            } else {
              $this->model->rollback();
              $binStr .= '绑定失败' . serialize($c_v) . "\n";
              if (!$customCIf) {
                $binStr .= 'customCIf异常!' . $sql1;
              }
              if (!$customIf) {
                $binStr .= 'customCIf异常!' . $sql2;
              }
              if (!$ecardBindIf) {
                $binStr .= 'customCIf异常!' . $sql3;
              }
              $this->recordData($binStr);
              returnMsg(array('status' => '07', 'codemsg' => '绑定失败'));
            }
          }
        }
        $this->model->commit();
        $binStr .= '绑定成功' . "\n";
        $this->recordData($binStr);
        returnMsg(array('status' => '1', 'codemsg' => '绑定成功', 'addamount' => floatval($amount), 'addcoin' => 0));
      }
    } else {
      $binStr .= '非2336绑卡：' . serialize($card);
      $field = 'c.customid cid,cu.customlevel,c.brandid,a.amount,cu.namechinese,cu.personidtype,cu.personid,';
      $field .= 'cu.nameenglish,cu.linkman,cu.linktel,cu.sex,cu.birthday,c.status,c.cardkind,a1.amount coin,cu.customid custid';
      //查询老卡关联会员信息
      $where['c.cardno'] = $cardno;
      $where['a.type'] = '00';
      $where['a1.type'] = '01';
      $card = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
        ->join('customs cu on cu.customid=cc.customid')
        ->join('account a on c.customid=a.customid')
        ->join('account a1 on c.customid=a1.customid')
        ->where($where)->field($field)->find();

      $map1 = array('cardno' => $cardno, 'status' => 1);
      $bindList = M('ecard_bind')->where($map1)->select();
      //echo $this->cards->getLastSql();exit;
      if ($card['status'] != 'Y' && $card['status'] != 'A') {
        returnMsg(array('status' => '04', 'codemsg' => '非正常卡'));
      }
      if ($card['cardkind'] == '6882' || $card['cardkind'] == '2081' || $card['cardkind'] == '6688') {
        returnMsg(array('status' => '05', 'codemsg' => '酒店卡，不允许绑定'));
      }
      if ($bindList != false) {
        returnMsg(array('status' => '06', 'codemsg' => '此卡已绑定'));
      }
      $custid = $card['custid'];
      $this->model->startTrans();
      //将绑定卡关联至注册会员账户上；
      $customid = decode($customid);
      $customCData = array('customid' => $customid);
      $where2 = array('cid' => $card['cid']);
      $customCIf = $this->customs_c->where($where2)->save($customCData);

      //绑定卡的会员信息一直到注册的会员信息中
      $where3['customid'] = $customid;
      $custom = $this->customs->where($where3)->find();
      if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
        $data3 = array(
          'namechinese' => $card['namechinese'],
          'nameenglish' => $card['nameenglish'],
          'personidtype' => $card['personidtype'],
          'personid' => $card['personid'],
          'linkman' => $card['linkman'],
          'sex' => $card['sex'],
          'birthday' => $card['birthday']
        );
        $customIf = $this->customs->where($where3)->save($data3);
      } else {
        $customIf = true;
      }
      $addtime = date('Y-m-d H:i:s');
      $sql = "INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}','{$addtime}','{$custid}')";
      $ecardBindIf = $this->model->execute($sql);
      //新建转让记录表
      //            $tsql = "INSERT INTO TYING_CARD VALUES('{$cardno}','{$customid}','{$custid}','{$addtime}')";
      //            $tcard = $this->model->execute($tsql);
      //            //绑定的会员和解绑的会员更改转让状态  增加字段 turn  转让状态   1 转入  2转出   0无记录
      //            $map5['turn'] = 1;
      //            $where5['customid'] = $customid;
      //            //新转入
      //            $a_custom = $this->customs->where($where5)->save($map5);
      //            //旧转出
      //            if($a_custom ==true){
      //                $map6['turn'] = 2;
      //                $where5['customid'] = $custid;
      //                $b_custom = $this->customs->where($where5)->save($map6);
      //            }else{
      //                returnMsg(array('status'=>'0017','codemsg'=>'更改状态失败'));
      //            }
      if ($customCIf == true && $customIf == true && $ecardBindIf == true) {
        $this->model->commit();
        returnMsg(array('status' => '1', 'codemsg' => '绑定成功', 'addamount' => floatval($card['amount']), 'addcoin' => floatval($card['coin'])));
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '07', 'codemsg' => '绑定失败'));
      }
    }
  }


  public function cardBind()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '09', 'codemsg' => '非法数据传入'));
    }
    $this->recordData('elsa:' . $datami);
    //$datami='{"cardno":"6889379600000219671","pwd":"anljYXJkMjg3Mzg5anljb2lu","key":"8f31946b9f0894d522a682a82ba62d0a","customid":"MDA0NjUyNDAO0O0O","sourceCode":"1001"}';
    $datami = json_decode($datami, 1);
    $cardno = trim($datami['cardno']);
    $pwd = trim($datami['pwd']);
    $customid = trim($datami['customid']); //新绑会员
    $key = trim($datami['key']);
    $sourceCode = trim($datami['sourceCode']);

    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $checkKey = md5($this->keycode . $customid . $cardno . $pwd . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map = array('cardno' => $cardno);
    $card = $this->cards->where($map)->find();
    //returnMsg(array('test'=>$this->cards->getLastSql()));exit;
    if ($card == false) {
      returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
    }
    $pwd = $this->decodePwd($pwd);
    if ($pwd == false) {
      returnMsg(array('status' => '08', 'codemsg' => '非法密码传入'));
    }
    //$url = C('netApiUrl');
    //$url='http://122.0.82.130:8087/Web/login.ashx';//正式环境
    //$url='http://192.168.10.50/Web/login.ashx';//老正式环境
    //$data=json_encode(array('username'=>$cardno,'pwd'=>$pwd));
    //$res=crul_post($url,$data);
    //$res=json_decode($res,1);
    //print_r($res);exit;

    $des = new  Des('238ab9c87d4569a59b0c8d7e9A0D9C8B238ab9c87d4569a5');
    $pwd = $des->doEncrypt($pwd);
    if ($pwd != $card['cardpassword']) {
      returnMsg(array('status' => '03', 'codemsg' => '密码错误'));
    }
    $binStr = "bindcard starting..." . "\n";
    //绑定至尊卡礼品卡处理
    if ($card['cardkind'] == '2336') {
      $binStr .= '2336绑卡：' . serialize($card) . "\n";
      if ($card['status'] != 'Y' && $card['status'] != 'A') {
        returnMsg(array('status' => '004', 'codemsg' => '非正常卡'));
      }
      $map1 = array('cardno' => $cardno, 'status' => 1);
      $bindList = M('ecard_bind')->where($map1)->select();
      if ($bindList != false) {
        returnMsg(array('status' => '006', 'codemsg' => '此卡已绑定'));
      }
      $where['c.status'] = 'Y';
      $where['c.cardno'] = $cardno;
      $field = 'c.cardno,c.customid,cu.customid cuid';
      $custom_data = $this->cards->alias('c')
        ->join(' left join customs_c cc on cc.cid=c.customid')
        ->join(' left join customs cu on cu.customid=cc.customid')
        ->where($where)->field($field)->find();
      //  dump($custom_data);exit;
      if (!$custom_data) {
        $binStr .= '卡会员不存在，请核实！';
        $this->recordData($binStr);
        returnMsg(array('status' => '007', 'codemsg' => '卡会员不存在，请核实！'));
      } else {
        $where1['cu.customid'] = $custom_data['cuid'];
        $where1['a.type'] = '00';
        $where1['a1.type'] = '01';
        $fields = 'c.cardno,c.status,c.cardkind,c.cardfee,c.customid,';
        $fields .= 'cu.namechinese cuname,cu.personid,cu.linktel,cu.customid cuid,p.namechinese pname,a.amount cardbalance,a1.amount cardpoint';
        $card_list = $this->cards->alias('c')
          ->join('__CUSTOMS_C__ cc on cc.cid=c.customid')
          ->join('__CUSTOMS__ cu on cu.customid=cc.customid')
          ->join('left join __PANTERS__ p on p.panterid=c.panterid')
          ->join('__ACCOUNT__ a on a.customid=c.customid')
          ->join('__ACCOUNT__ a1 on a1.customid=c.customid')
          ->where($where1)->field($fields)->select();
        // dump($card_list);
        $array = array();
        $binStr .= '实体卡处理：' . serialize($card_list) . "\n";
        foreach ($card_list as $k => $v) {
          $array[$v['cardkind']][] = $v;
        }
        $binStr .= '实体卡处理：' . serialize($array) . "\n";
        $arr = array();
        foreach ($array as $key => $val) {

          // dump(count($val));
          if (count($val) > 1) {
            $strarr = array();
            foreach ($val as $ve) {
              if ($ve['cardfee'] == "1") {
                $strarr[] = $ve;
              }
            }
          }
          $arr[] = $val;
        }

        if ($strarr && count($strarr) > 2) {

          $str1 = 'cardno:' . $cardno . "\t,array:" . $strarr . "\t,";
          $str1 .= date('Y-m-d H:i:s', time()) . "\t,";
          $this->writeLogs('bangcard', $str1);
          $binStr .= '至尊实体卡绑卡存在异常，请联系客服解决';
          $this->recordData($binStr);
          returnMsg(array('status' => '07', 'codemsg' => '绑卡异常，请联系客服解决！'));
        }
        $bang_data = array();
        foreach ($arr as $b_k => $b_v) {
          $bang_data = $b_v;
        }
        $this->model->startTrans();
        $customid = decode($customid); //新绑会员
        $binStr .= '获取绑卡列表' . serialize($bang_data) . "\n";
        $amount = 0;
        foreach ($bang_data as $c_k => $c_v) { //循环会员下卡号
          // dump($c_v);exit;
          // 查询卡号是否消费了
          $binStr .= '绑卡处理：' . serialize($c_v) . "\n";
          $where8['cardno'] = $c_v['cardno'];
          $old_customid = $c_v['customid'];
          $card_data = M('trade_wastebooks')->where($where8)->find(); //如果有交易记录则变更到要绑定的会员下
          //   print_r($card_data);exit;
          if ($card_data) {
            $binStr .= '变更交易记录：' . serialize($card_data) . "\n";
            if ($card_data['customid'] != $customid) {
              $cu['customid'] = $customid;
              $trade_data = M('trade_wastebooks')->where($where8)->save($cu);
            } else {
              $trade_data = true;
            }

            if ($trade_data) {
              $customCData = array('customid' => $customid);
              $where2 = array('cid' => $old_customid);
              $customCIf = $this->customs_c->where($where2)->save($customCData);

              //绑定卡的会员信息一直到注册的会员信息中
              $where3['customid'] = $customid;
              $custom = $this->customs->where($where3)->find();
              if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
                $data3 = array(
                  'namechinese' => $card['namechinese'],
                  'nameenglish' => $card['nameenglish'],
                  'personidtype' => $card['personidtype'],
                  'personid' => $card['personid'],
                  'linkman' => $card['linkman'],
                  'sex' => $card['sex'],
                  'birthday' => $card['birthday']
                );
                $customIf = $this->customs->where($where3)->save($data3);
              } else {
                $customIf = true;
              }
              $addtime = date('Y-m-d H:i:s');
              $bindcard = $c_v['cardno'];
              $sql = "INSERT INTO ECARD_BIND VALUES('{$bindcard}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
              $ecardBindIf = $this->model->execute($sql);

              if ($customCIf == true && $customIf == true && $ecardBindIf == true) {

                $binStr .= '绑卡成功:' . serialize($c_v);
                $amount = $amount + $c_v['cardbalance'];

                //returnMsg(array('status'=>'1','codemsg'=>'绑定成功','addamount'=>floatval($card['amount']),'addcoin'=>floatval($card['coin'])));
              } else {
                $this->model->rollback();
                $binStr .= '绑定失败:' . serialize($c_v);
                $this->recordData($binStr);
                returnMsg(array('status' => '017', 'codemsg' => '绑定失败'));
              }
            } else {
              $this->model->rollback();
              $binStr .= '更改消费记录失败:' . serialize($c_v);
              $this->recordData($binStr);
              returnMsg(array('status' => '0008', 'codemsg' => '更改消费记录失败'));
            }
          } else {
            $binStr .= '无交易记录变更！' . "\n";
            $customCData = array('customid' => $customid);
            $where2 = array('cid' => $old_customid);
            $customCIf = $this->customs_c->where($where2)->save($customCData);
            $sql1 = $this->customs_c->getLastSql();
            //绑定卡的会员信息一直到注册的会员信息中
            $where3['customid'] = $customid;
            $custom = $this->customs->where($where3)->find();
            if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
              $data3 = array(
                'namechinese' => $card['namechinese'],
                'nameenglish' => $card['nameenglish'],
                'personidtype' => $card['personidtype'],
                'personid' => $card['personid'],
                'linkman' => $card['linkman'],
                'sex' => $card['sex'],
                'birthday' => $card['birthday']
              );
              $customIf = $this->customs->where($where3)->save($data3);
              $sql2 = $this->customs->getLastSql();
            } else {
              $customIf = true;
            }
            $addtime = date('Y-m-d H:i:s');
            $bindcard = $c_v['cardno'];
            $sql = "INSERT INTO ECARD_BIND VALUES('{$bindcard}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
            // $sql="INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}','{$addtime}','{$old_customid}')";
            $ecardBindIf = $this->model->execute($sql);
            $sql3 = $this->model->getLastSql();
            if ($customCIf == true && $customIf == true && $ecardBindIf == true) {
              $binStr .= '绑卡成功' . serialize($c_v) . '---sql:' . $sql1 . "\n";
              $amount = $amount + $c_v['cardbalance'];
              // returnMsg(array('status'=>'1','codemsg'=>'绑定成功','addamount'=>floatval($card['amount']),'addcoin'=>floatval($card['coin'])));
            } else {
              $this->model->rollback();
              $binStr .= '绑定失败' . serialize($c_v) . "\n";
              if (!$customCIf) {
                $binStr .= 'customCIf异常!' . $sql1;
              }
              if (!$customIf) {
                $binStr .= 'customCIf异常!' . $sql2;
              }
              if (!$ecardBindIf) {
                $binStr .= 'customCIf异常!' . $sql3;
              }
              $this->recordData($binStr);
              returnMsg(array('status' => '0007', 'codemsg' => '绑定失败'));
            }
          }
        }
        $this->model->commit();
        $binStr .= '绑定成功' . "\n";
        $this->recordData($binStr);
        returnMsg(array('status' => '1', 'codemsg' => '绑定成功', 'addamount' => floatval($amount), 'addcoin' => 0));
      }
    } else {
      $binStr .= '非2336绑卡：' . serialize($card);
      $field = 'c.customid cid,cu.customlevel,c.brandid,a.amount,cu.namechinese,cu.personidtype,cu.personid,';
      $field .= 'cu.nameenglish,cu.linkman,cu.linktel,cu.sex,cu.birthday,c.status,c.cardkind,a1.amount coin,cu.customid custid';
      //查询老卡关联会员信息
      $where['c.cardno'] = $cardno;
      $where['a.type'] = '00';
      $where['a1.type'] = '01';
      $card = $this->cards->alias('c')->join('customs_c cc on cc.cid=c.customid')
        ->join('customs cu on cu.customid=cc.customid')
        ->join('account a on c.customid=a.customid')
        ->join('account a1 on c.customid=a1.customid')
        ->where($where)->field($field)->find();

      $map1 = array('cardno' => $cardno, 'status' => 1);
      $bindList = M('ecard_bind')->where($map1)->select();
      //echo $this->cards->getLastSql();exit;
      if ($card['status'] != 'Y' && $card['status'] != 'A') {
        returnMsg(array('status' => '04', 'codemsg' => '非正常卡'));
      }
      if ($card['cardkind'] == '6882' || $card['cardkind'] == '2081' || $card['cardkind'] == '6688') {
        returnMsg(array('status' => '05', 'codemsg' => '酒店卡，不允许绑定'));
      }
      if ($bindList != false) {
        returnMsg(array('status' => '06', 'codemsg' => '此卡已绑定'));
      }
      $custid = $card['custid'];
      $this->model->startTrans();
      //将绑定卡关联至注册会员账户上；
      $customid = decode($customid);
      $customCData = array('customid' => $customid);
      $where2 = array('cid' => $card['cid']);
      $customCIf = $this->customs_c->where($where2)->save($customCData);

      //绑定卡的会员信息一直到注册的会员信息中
      $where3['customid'] = $customid;
      $custom = $this->customs->where($where3)->find();
      if (trim($custom['namechinese']) == false && trim($custom['personid']) == false) {
        $data3 = array(
          'namechinese' => $card['namechinese'],
          'nameenglish' => $card['nameenglish'],
          'personidtype' => $card['personidtype'],
          'personid' => $card['personid'],
          'linkman' => $card['linkman'],
          'sex' => $card['sex'],
          'birthday' => $card['birthday']
        );
        $customIf = $this->customs->where($where3)->save($data3);
      } else {
        $customIf = true;
      }
      $addtime = date('Y-m-d H:i:s');
      $sql = "INSERT INTO ECARD_BIND VALUES('{$cardno}','{$customid}',1,'{$panterid}','{$addtime}','{$custid}')";
      $ecardBindIf = $this->model->execute($sql);
      //新建转让记录表
      //            $tsql = "INSERT INTO TYING_CARD VALUES('{$cardno}','{$customid}','{$custid}','{$addtime}')";
      //            $tcard = $this->model->execute($tsql);
      //            //绑定的会员和解绑的会员更改转让状态  增加字段 turn  转让状态   1 转入  2转出   0无记录
      //            $map5['turn'] = 1;
      //            $where5['customid'] = $customid;
      //            //新转入
      //            $a_custom = $this->customs->where($where5)->save($map5);
      //            //旧转出
      //            if($a_custom ==true){
      //                $map6['turn'] = 2;
      //                $where5['customid'] = $custid;
      //                $b_custom = $this->customs->where($where5)->save($map6);
      //            }else{
      //                returnMsg(array('status'=>'0017','codemsg'=>'更改状态失败'));
      //            }
      if ($customCIf == true && $customIf == true && $ecardBindIf == true) {
        $this->model->commit();
        returnMsg(array('status' => '1', 'codemsg' => '绑定成功', 'addamount' => floatval($card['amount']), 'addcoin' => floatval($card['coin'])));
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '07', 'codemsg' => '绑定失败'));
      }
    }
  }

  //检查老卡
  public function checkCard()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '05', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $cardno = trim($datami['cardno']);
    $key = trim($datami['key']);
    $checkKey = md5($this->keycode . $cardno);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map = array('cardno' => $cardno);
    $card = $this->cards->where($map)->field('cardkind,cardno,status')->find();
    $map1 = array('cardno' => $cardno, 'status' => 1);
    $bindList = M('ecard_bind')->where($map1)->select();
    if ($card['status'] != 'Y' && $card['status'] != 'A') {
      returnMsg(array('status' => '02', 'codemsg' => '无效卡号或者非正常卡号'));
    }
    if ($card['cardkind'] == '6882') {
      returnMsg(array('status' => '03', 'codemsg' => '酒店卡，不允许绑定'));
    }
    if ($bindList != false) {
      returnMsg(array('status' => '04', 'codemsg' => '此卡已经绑定'));
    } else {
      returnMsg(array('status' => '1', 'codemsg' => '校验通过'));
    }
  }

  //营销劵查询
  public function ticketsQuery()
  {
    $data = getPostJson();
    $cardno = trim($data['cardno']);
    //$cardno='2336370888800422152';
    $key = trim($data['key']);
    $checkKey = md5($this->keycode . $cardno);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $map = array('cardno' => $cardno);
    $card = $this->cards->where($map)->find();
    if ($card == false) {
      returnMsg(array('status' => '02', 'codemsg' => '非法卡号'));
    }
    $tickketList = $this->getTicketByCardno($cardno);
    //print_r($tickketList);exit;
    if ($tickketList == false) {
      returnMsg(array('status' => '03', 'codemsg' => '查无卡劵信息'));
    } else {
      returnMsg(array('status' => '1', 'ticketList' => json_encode($tickketList)));
    }
  }

  //撤销订单
  public function cancelOrder()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $orderid = trim($datami['orderId']);
    $sourceCode = trim($datami['sourceCode']);
    $key = trim($datami['key']);
    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $checkKey = md5($this->keycode . $orderid . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    //$orderid='201512281850';
    if (empty($orderid)) {
      returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
    }
    $map['eorderid'] = $orderPrefix . $orderid;
    $map['flag'] = 0;
    $map['tradetype'] = '00';
    $tradeList = M('trade_wastebooks')->where($map)
      ->field("tradeid,tradeamount,tradepoint,cardno")->select();
    if ($tradeList == false) {
      returnMsg(array('status' => '04', 'codemsg' => '无此订单支付信息'));
    }
    $this->model->startTrans();
    $c = 0;
    foreach ($tradeList as $ke => $val) {
      $map['cardno'] = $val['cardno'];
      $cardInfo = $this->cards->where($map)->find();
      $customid = $cardInfo['customid'];
      if ($val['tradeamount'] != 0) {
        $balanceSql = "UPDATE account SET amount=nvl(amount,0)+" . $val['tradeamount'] . " WHERE customid='{$customid}' and type='00'";
        $balanceIf = $this->account->execute($balanceSql);
      } else {
        $balanceIf = true;
      }
      if ($val['tradepoint'] != 0) {
        $coinSql = "UPDATE account SET amount=nvl(amount,0)+" . $val['tradepoint'] . " WHERE customid='{$customid}' and type='01'";
        $coinIf = $this->account->execute($coinSql);
        $coinReturnIf = $this->returnCoin($val['tradeid']);
      } else {
        $coinIf = true;
        $coinReturnIf = true;
      }
      $tradeMap = array('tradeid' => $val['tradeid']);
      $tradeData = array('tradetype' => '04');
      $tradeIf = M('trade_wastebooks')->where($tradeMap)->save($tradeData);
      if ($balanceIf == true && $coinIf == true && $tradeIf == true && $coinReturnIf == true) {
        $c++;
      }
    }
    if ($c == count($tradeList)) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '账户支付撤销成功'));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '05', 'codemsg' => '账户支付撤销失败'));
    }
  }

  //外拓撤销订单
  public function wtCancelOrder()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $orderid = trim($datami['orderid']);
    $key = trim($datami['key']);
    $checkKey = md5($this->keycode . $orderid);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    //$orderid='201512281850';
    if (empty($orderid)) {
      returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
    }
    $map['eorderid'] = "" . $orderid;
    $map['flag'] = 0;
    $map['tradetype'] = '00';
    $tradeList = M('trade_wastebooks')->where($map)
      ->field("tradeid,tradeamount,tradepoint,cardno")->select();
    if ($tradeList == false) {
      returnMsg(array('status' => '04', 'codemsg' => '无此订单支付信息'));
    }
    $this->model->startTrans();
    $c = 0;
    foreach ($tradeList as $ke => $val) {
      $map['cardno'] = $val['cardno'];
      $cardInfo = $this->cards->where($map)->find();
      $customid = $cardInfo['customid'];
      if ($val['tradeamount'] != 0) {
        $balanceSql = "UPDATE account SET amount=nvl(amount,0)+" . $val['tradeamount'] . " WHERE customid='{$customid}' and type='00'";
        $balanceIf = $this->account->execute($balanceSql);
      } else {
        $balanceIf = true;
      }
      if ($val['tradepoint'] != 0) {
        $coinSql = "UPDATE account SET amount=nvl(amount,0)+" . $val['tradepoint'] . " WHERE customid='{$customid}' and type='01'";
        $coinIf = $this->account->execute($coinSql);
        $coinReturnIf = $this->returnCoin($val['tradeid']);
      } else {
        $coinIf = true;
        $coinReturnIf = true;
      }
      $tradeMap = array('tradeid' => $val['tradeid']);
      $tradeData = array('tradetype' => '04');
      $tradeIf = M('trade_wastebooks')->where($tradeMap)->save($tradeData);
      if ($balanceIf == true && $coinIf == true && $tradeIf == true && $coinReturnIf == true) {
        $c++;
      }
    }
    if ($c == count($tradeList)) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '账户支付撤销成功'));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '05', 'codemsg' => '账户支付撤销失败'));
    }
  }

  //订单退款
  public function returnGoods()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordError("\r" . date("H:i:s") . "-记录起始\n\t" . $datami . "\n\t", "YjTbpost", "returnGoods");
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $orderid = trim($datami['orderId']);
    $returnAmount = trim($datami['returnAmount']);
    $returnCoin = trim($datami['returnCoin']);
    $sourceCode = trim($datami['sourceCode']);
    $key = trim($datami['key']);

    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $checkKey = md5($this->keycode . $orderid . $returnAmount . $returnCoin . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($orderid)) {
      returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
    }
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $returnAmount)) {
      returnMsg(array('status' => '04', 'codemsg' => '退款金额格式有误'));
    }
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $returnCoin)) {
      returnMsg(array('status' => '05', 'codemsg' => '退款建业币格式有误'));
    }
    if ($returnAmount == 0 && $returnCoin == 0) {
      returnMsg(array('status' => '06', 'codemsg' => '退款数据有误'));
    }
    //------------------author wan 2017--09-29
    if ($returnCoin > 0) {
      $url = C("refundCoinUrl");
      $returnInfo = $this->getQuery($url, json_encode($data));
      returnMsg($returnInfo);
    } else {
      $orderid = $orderPrefix . $orderid;
      $map = array('eorderid' => $orderid, 'flag' => 0, 'tradetype' => '00');
      $tradeC = M('trade_wastebooks')->where($map)
        ->field("tradeid,tradeamount,tradepoint,cardno")->count();

      $tradeC1 = M('income_books')->where(array('active_id' => $orderid))->count();
      if ($tradeC == 0 && $tradeC == 0) {
        returnMsg(array('status' => '07', 'codemsg' => '无此订单支付信息'));
      }
      $tradeInfo = M('trade_wastebooks')->where($map)
        ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

      $map1 = array('eorderid' => $orderid, 'flag' => array('in', array('2', '3')), 'tradetype' => '00');
      $cancleTrade = M('trade_wastebooks')->where($map1)
        ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

      $zzk_order = M('zzk_order')->alias('zo')->join('zzk_account_detail zd on zo.inner_order=zd.order_sn')
        ->join('zzk_account za on zo.accountid=za.accountid')
        ->where(['zo.order_sn' => $orderid, 'zo.tradetype' => '50'])
        ->field('zo.accountid,zo.amount,zo.inner_order,zo.phone,zo.panterid,zd.cash_balance zdcash,zd.consume_balance zdconsume,za.balance,za.cash_balance,za.freeze_balance')->find();
      if ($zzk_order) {
        if ($zzk_order['amount'] < $returnAmount) {
          returnMsg(array('status' => '08', 'codemsg' => '退款金额大于订单金额'));
        }
      } else {
        if ($tradeInfo['orderamount'] - $cancleTrade['orderamount'] < $returnAmount) {
          returnMsg(array('status' => '08', 'codemsg' => '退款金额大于订单金额'));
        }
      }

      $this->recordError(serialize($tradeInfo) . "\n\t" . serialize($cancleTrade) . "\n\t" . serialize($zzk_order) . "\n\t", "YjTbpost", "returnGoods");
      $orderAmount = $tradeInfo['orderamount'] - $cancleTrade['orderamount'];
      if ($orderAmount < $returnAmount) {
        $zAmount = $returnAmount - $orderAmount;
        $rs = $this->zrefund($orderid, $zAmount, $zzk_order);
        if ($rs['status'] != 1) {
          returnMsg(array('status' => '011', 'codemsg' => '退款失败'));
        }
        $returnAmount = $orderAmount;
      }

      // $zzk_order = M('zzk_order')->alias('zo')->join('zzk_account_detail zd on zo.inner_order=zd.order_sn')
      //     ->where(['zo.order_sn'=>$orderid,'zo.tradetype'=>'57'])
      //     ->field('amount,inner_order')->find();
      // $refund = M('zzk_order')->where(['order_sn'=>$orderid,'tradetype'=>'57'])->field('sum(amount) amount')->find();
      // if (($zzk_order['amount'] - $refund['amount']) < $returnAmount) {
      //     returnMsg(array('status' => '08', 'codemsg' => '可退款金额不足'));
      // }


      $this->customs_c->alias('cc')->join('account a on cc.cid=a.customid')->join('cards c on c.customid=cc.cid')
        ->where($where)->sum('a.amount');

      if ($tradeInfo['ordercoin'] - $cancleTrade['ordercoin'] < $returnCoin) {
        returnMsg(array('status' => '09', 'codemsg' => '退款建业币金额大于订单建业币金额'));
      }
      //echo $orderid.'--'.$returnAmount.'--'.$returnCoin;exit;
      $this->model->startTrans();
      if ($returnAmount > 0) {
        $balanceIf = true;
        $balanceIf = $this->refund($orderid, $returnAmount, 1);
        $this->recordError(serialize($returnAmount) . "\n\t" . serialize($balanceIf) . "\n\t", "YjTbpost", "returnGoods");
      } else {
        $balanceIf = true;
      }
      if ($returnCoin > 0) {
        returnMsg(array('status' => '011', 'codemsg' => '通宝消费不能退款'));
        //$coinIf=$this->refund($orderid,$returnCoin,2);
      } else {
        $coinIf = true;
      }
      if ($balanceIf == true && $coinIf == true) {
        //$this->model->rollback();
        $this->model->commit();
        returnMsg(array('status' => '1', 'info' => array('addBalance' => floatval($datami['returnAmount']), 'addCoin' => floatval($returnCoin))));
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '010', 'codemsg' => '退款失败'));
      }
    }
  }

  //建业币充值
  public function rechargeCoin()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '06', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $amount = trim($datami['amount']);
    $key = trim($datami['key']);
    $sourceCode = trim($datami['sourceCode']);
    $sourceRechargeId = trim($datami['sourceRechargeId']);
    returnMsg(array('status' => '010', 'codemsg' => '非项目方禁止进行通宝充值'));
    //$sourceCode='1001';$amount='9000';$customid='00002479';
    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $prefix = $this->panterArr[$sourceCode]['prefix'];
    $checkKey = md5($this->keycode . $customid . $amount . $sourceRechargeId . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) {
      returnMsg(array('status' => '02', 'codemsg' => '用户缺失'));
    }
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
      returnMsg(array('status' => '03', 'codemsg' => '充值金额格式错误'));
    }
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '04', 'codemsg' => '非法会员编号'));
    }
    $cardno = $this->getMainCard($customid);
    if ($cardno == false) {
      returnMsg(array('status' => '05', 'codemsg' => '会员缺失至尊卡号'));
    }
    $accountInfo = $this->getCoinAccount($cardno);
    if ($accountInfo['accountid'] == false || $accountInfo['cardid'] == false) {
      returnMsg(array('status' => '07', 'codemsg' => '会员账户异常'));
    }
    $map = array('description' => array('like', '%' . $prefix . $sourceRechargeId . '%'));
    $cpl = M('card_purchase_logs')->where($map)->find();
    if ($cpl != false) {
      returnMsg(array('status' => '09', 'codemsg' => '此充值号已经充值，请勿重复提交'));
    }
    $this->model->startTrans();
    $cardInfo = array(
      'cardno' => $cardno, 'cardid' => $accountInfo['cardid'], 'accountid' => $accountInfo['accountid'],
      'orderid' => $prefix . $sourceRechargeId, 'customid' => $customid
    );
    $coinBool = $this->coinRecharge($cardInfo, $amount, $panterid);
    if ($coinBool == true) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '充值成功', 'addcoin' => floatval($amount)));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '08', 'codemsg' => '建业币充值失败'));
    }
  }

  //通过会员编号获得会员下的主卡（卡编号与会员编号一样的卡）；若没有，获取编号最小的卡
  protected function getMainCard($customid)
  {
    $map = array('customid' => $customid);
    $mainCard = $this->cards->where($map)->find();
    if ($mainCard == false) {
      $map1 = array('cu.customid' => $customid);
      $card = $this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
        ->join('cards c on cc.cid=c.customid')->where($map1)->order('c.customid asc')->find();
      if ($card == false) {
        return false;
      } else {
        return $card['cardno'];
      }
    } else {
      return $mainCard['cardno'];
    }
  }

  //一家用户注册接口对接测试
  public function registYjia()
  {
    $datainfo = $_POST['info'];
    $cardno = $datainfo['zzkno'];
    if ($datainfo['key'] == md5(md5(md5($datainfo['uname'] . $datainfo['idcardno'] . 'yjzc')))) {
      $appid = 'SOON-ZZUN-0001';
      $homeinfo = $datainfo['homeinfo']; //项目名称-分期-楼栋号-单元号(可能没有)-房号
      $homearray = explode('-', $homeinfo);
      $orgid = $datainfo['orgid']; //小区id
      if (count($homearray) == 6) {
        $building = $homearray[3]; //楼栋号
        $unint = $homearray[4]; //单元号
        $housenum = $homearray[5]; //房间号
      } elseif (count($homearray) == 5) {
        $building = $homearray[2]; //楼栋号
        $unint = $homearray[3]; //单元号
        $housenum = $homearray[4]; //房间号
      } elseif (count($homearray) == 4) {
        $building = $homearray[2]; //楼栋号
        $unint = ''; //单元号
        $housenum = $homearray[3]; //房间号
      } elseif (count($homearray) == 3) {
        $building = $homearray[1];
        $unint = '';
        $housenum = $homearray[2];
      } else {
        $building = ''; //楼栋号
        $unint = ''; //单元号
        $housenum = ''; //房间号
      }
      $idcardno = $datainfo['idcardno']; //用户身份证号
      $source = $datainfo['source'];
      $password = substr($idcardno, -6); //用户密码
      $mobilephone = $datainfo['mobilephone']; //用户手机号
      $uname = $datainfo['uname']; //用户名字
      $customid = $this->getCustomid($datainfo['zzkno']); //用户会员编号
      if ($source == 'tb') {
        $password = substr($mobilephone, -6);
        $customid = $datainfo['customid'];
      }
      $data = array(
        'appid' => $appid,
        'orgid' => $orgid,
        'building' => $building,
        'unint' => $unint,
        'housenum' => $housenum,
        'mobilephone' => $mobilephone,
        'password' => $password,
        'uname' => $uname,
        'idcardno' => $idcardno,
        'customid' => encode($customid)
      );
      $de = new YjDes();
      $sign = $de->encrypt($data);
      $data = json_encode($data, JSON_FORCE_OBJECT);
      $url = C("ehome_regist");
      //---------向一家传输用户通宝信息--17:21----
      $tb_info = D("Ehome")->getTbinfo($customid);
      if ($tb_info) {
        $tb_info['customid'] = encode($tb_info['customid']);
        $tb_info['activetype'] = 2;
        $tb_info['appid'] = $appid;
        $tb_sign = $de->encrypt($tb_info);
        $tb_data = json_encode($tb_info, JSON_FORCE_OBJECT);
        $return_yj = $this->curlPost(C('ehome_potb'), $tb_data, $tb_sign);
        $return_arr = json_decode($return_yj, 1);

        if ($return_arr['code'] == '100') {
          $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "success");
        } else {
          $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "failed");
        }
      }
      //---------end-----------------
      $result = $this->curlPost($url, $data, $sign);
      $resultinfo = json_decode($result, true);
      $map = array('cardno' => $cardno, 'customid' => $customid, 'panterid' => '00000286');
      $list = $this->model->table('ecard_bind')->where($map)->find();
      if ($resultinfo['code'] == '100') {
        $this->recordError(date("H:i:s") . '-' . $data . '-' . $result . "\n\t", "YjRegist", "success");
      } elseif ($resultinfo['code'] == '500') {
        $customid = $resultinfo['msg'];
        $this->recordError(date("H:i:s") . '-' . $data . '-' . $result . "\n\t", "YjRegist", "registed");
      } elseif ($resultinfo['code'] == '501') {
        $this->recordError(date("H:i:s") . '-' . $data . '-' . $result . "\n\t", "YjRegist", "registed");
      } else {
        $this->recordError(date("H:i:s") . '-' . $data . '-' . $result . '-' . json_encode($datainfo) . "\n\t", "YjRegist", "error");
      }
      echo 1;
    } else {
      echo 0;
    }
  }

  public function mytest()
  {
    $password = '888888'; //用户密码
    $data = array('password' => $password, );
    $de = new YjDes();
    $sign = $de->encrypt($data['password']);
    var_dump($sign);
    $data = json_encode($data, JSON_FORCE_OBJECT);
  }

  private function curlPost($url, $data, $sign)
  {
    if (!$url) {
      return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json', "sign:{$sign}"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $output = curl_exec($ch);
    if ($output == false) {
      return curl_error($ch);
    }
    curl_close($ch);
    return $output;
  }

  public function query_balance()
  {
    $cid = trim(I('get.cid'));
    if (isset($cid)) {
      $customid = $cid;
      $sign = md5($this->keycode . $customid);
      $data = array(
        'customid' => $customid,
        'key' => $sign
      );
      session('access_token', "accessToken={$_GET['access_token']}");
      $data = json_encode($data);
      $data = $this->DESedeCoder->encrypt($data);
      $data = json_encode(array('datami' => $data));
      $url = C('e+_balance');
      $query = $this->getQuery($url, $data);
      $balance = $query['balance'];
      $this->assign('pid', $customid);

      $this->assign('balance', $balance);
    }
    $this->display();
  }

  public function getQuery($url, $data)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //禁用SSL
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    file_put_contents('curl.txt', $output, 8);
    curl_close();
    return json_decode($output, true);
  }

  public function create_order()
  {
    if (IS_POST) {
      // $pid = trim(I('post.customid '));
      $chargeMoney = trim(I('post.chargeMoney'));
      $data = array(
        'from' => 'wallet',
        'goodstype' => 'wallet',
        'name' => '余额充值',
        'price' => floatval($chargeMoney),
        'channel' => 'nosupervip,nojycoin,noqpay',
        'refid' => 'zhizun_yue',
        'storeid' => '32947',
        'callbackurl' => C('callbackurl'),
      );
      //        if($chargeMoney>1000){
      //            exit(json_encode(['msg'=>'充值金额大于1000','code'=>'502','success'=>'false']));
      //        }
      $url = C('e+create_order') . '?' . session('access_token');
      $query = $this->getQuery($url, $data);
      echo json_encode($query);
    }
  }

  public function receiveE()
  {
    $data = file_get_contents('php://input');
    file_put_contents('./orderid', $data . "\r\n", FILE_APPEND);
    $data = explode('&', $data);
    foreach ($data as $key => $val) {
      $val = explode('=', $val);
      $arr[$val[0]] = $val[1];
    }
    $customid = $arr['customid'];
    $amount = $arr['amount'] / 100;
    $orderno = $arr['orderno'];
    $source = $arr['source'];
    // $customid = 'MDAwMDM3NDkO0O0O';
    // $amount = 1;
    // $orderno = 'SW20160425034121004433350011111ssssss';
    // $source ='jyo2o';
    // $amount = $amount/100;
    $amount = number_format($amount, 2, '.', '');
    $str = 'customid:' . $customid . "\t,amount:" . $amount . "\t,orderno:" . $orderno . "\t,source:" . $source;
    $str1 = date('Y-m-d H:i:s', time()) . "\t,";
    if (empty($customid)) {
      $str1 .= '缺失用户id' . "\t,";
      returnMsg(array('status' => '001', 'codemsg' => '缺失用户id'));
    }
    if (empty($amount)) {
      $str1 .= '充值金额为空' . "\t,";
      returnMsg(array('status' => '002', 'codemsg' => '充值金额为空'));
    }
    if ($amount <= 0) {
      $str1 .= '充值金额大于零' . "\t,";
      returnMsg(array('status' => '003', 'codemsg' => '充值金额需大于零'));
    }
    if (empty($orderno)) {
      $str1 .= '缺失订单号' . "\t,";
      returnMsg(array('status' => '004', 'codemsg' => '缺失订单号'));
    }
    if ($source != 'jyo2o') {
      $str1 .= '缺失来源地址' . "\t,";
      returnMsg(array('status' => '005', 'codemsg' => '缺失来源地址'));
    } else {
      $source = '1001';
    }
    $datas = array(
      'customid' => $customid,
      'amount' => $amount,
      'sourceCode' => $source,
      'sourceRechargeId' => $orderno,
      'key' => md5($this->keycode . $customid . $amount . $orderno . $source),
    );
    //      if($amount>1000){
    //          $str1.='amount=>金额大于1000'."\t\n";
    //      }else{
    $datas = json_encode($datas);
    $datas = $this->DESedeCoder->encrypt($datas);
    $datas = json_encode(array('datami' => $datas));
    $url = C('chargeMoney');
    //$url = "http://192.168.10.45/zzkp.php/JyCoin/customRecharge";
    $query = $this->getQuery($url, $datas);
    $str1 .= $str . $query['codemsg'] . "\t\n";
    //      }
    $this->writeLogs('chargeMoney', $str1);
    if ($query['status'] == "1") {
      returnMsg(array('status' => '1', 'codemsg' => '充值成功'));
    } else {
      returnMsg(array('status' => $query['status'], 'codemsg' => $query['codemsg']));
    }
    exit;
  }

  private function writeLogs($module, $msgString)
  {
    $month = date('Ym', time());
    switch ($module) {
      case 'chargeMoney':
        $logPath = PUBLIC_PATH . 'logs/chargeMoney/false/';
        break;
      case 'undo':
        $logPath = PUBLIC_PATH . 'logs/Authorize/undo/';
        break;
      case 'over':
        $logPath = PUBLIC_PATH . 'logs/Authorize/over/';
        break;
      case 'over_undo':
        $logPath = PUBLIC_PATH . 'logs/Authorize/over_undo/';
        break;
      case 'success':
        $logPath = PUBLIC_PATH . 'logs/Authorize/success/';
        break;
      case 'fail':
        $logPath = PUBLIC_PATH . 'logs/Authorize/fail/';
        break;
      default:
        $logPath = PUBLIC_PATH . 'logs/file/';
    }
    $logPath = $logPath . $month . '/';
    $filename = date('Ymd', time()) . '.log';
    $filename = $logPath . $filename;
    if (!file_exists($logPath)) {
      mkdir($logPath, 0777, true);
    }
    file_put_contents($filename, $msgString, FILE_APPEND);
  }

  public function batchRefund()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $orderString = trim($datami['orderString']);
    $sourceCode = trim($datami['sourceCode']);
    $key = trim($datami['key']);

    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $checkKey = md5($this->keycode . $orderString . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    $orderArr = json_decode($orderString);
    if ($orderArr == false) {
      returnMsg(array('status' => '03', 'codemsg' => '订单传入有误'));
    }
    $errorMsg = array();
    foreach ($orderArr as $key => $val) {
      $orderId = $val['orderId'];
      $returnAmount = $val['returnAmount'];
      if (empty($orderId)) {
        $errorMsg[$key]['msg'] = '订单缺失';
        $errorMsg[$key]['stat'] = 0;
        continue;
      }
      if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $returnAmount)) {
        $errorMsg[$key]['msg'] = '退款金额缺失';
        $errorMsg[$key]['stat'] = 0;
        continue;
      }
      $orderId = $orderPrefix . $orderId;
      $map = array('eorderid' => $orderId, 'flag' => 0, 'tradetype' => '00');
      $tradeC = M('trade_wastebooks')->where($map)
        ->field("tradeid,tradeamount,tradepoint,cardno")->count();
      if ($tradeC == 0) {
        $errorMsg[$key]['msg'] = '无此订单信息';
        $errorMsg[$key]['stat'] = 0;
        continue;
      }
      $tradeInfo = M('trade_wastebooks')->where($map)
        ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

      if ($tradeInfo['orderamount'] != $returnAmount) {
        $errorMsg[$key]['msg'] = '退款金额与订单金额不一致';
        $errorMsg[$key]['stat'] = 0;
        continue;
      }
      $this->model->startTrans();
      $balanceIf = $this->refund($orderId, $returnAmount, 1);
      if ($balanceIf == true) {
        $this->model->commit();
        $errorMsg[$key]['msg'] = '订单' . $orderId . '退款成功';
        $errorMsg[$key]['info'] = array('addBalance' => floatval($returnAmount));
        $errorMsg[$key]['stat'] = 1;
        continue;
      } else {
        $this->model->rollback();
        $errorMsg[$key]['msg'] = '订单' . $orderId . '退款失败';
        $errorMsg[$key]['stat'] = 0;
        continue;
      }
    }
    returnMsg(array('status' => 1, 'res' => $errorMsg));
  }

  //绿色基地配卡
  public function allocateCards()
  {
    $data = getPostJson();

    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI'; //访问方式
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; //url
    $uri = $_SERVER['REMOTE_ADDR'] . ' ' . $method . ' ' . $uri;
    $this->recordError("\n\t" . date("Y-m-d H:i:s") . "-$uri \n\t" . serialize($data) . "\n\t", "YjTbpost", "createAccount");

    $panterid = trim($data['panterid']);
    $termno = trim($data['termno']);
    //        $panterid='00000241';
    //        $termno='54700696';
    $key = trim($data['key']);
    $checkKey = md5($this->keycode . $panterid . $termno);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    $userid = '0000000000000197';
    $cardbrand = '6880';
    $termArr = array('50900756' => 1, '50900802' => 2, '50900807' => 3, '50900806' => 4);
    $termflag = empty($termArr[$termno]) ? 8 : $termArr[$termno];
    $cardStart = $cardbrand . '374' . $termflag;
    //$customid=$this->getnextcode('customs',8);
    $customid = $this->getFieldNextNumber("customid");
    $namechinese = '大食堂' . intval($customid);
    $currentDate = date('Ymd', time());
    $this->model->startTrans();
    $sql = "INSERT INTO CUSTOMS(customid,namechinese,placeddate,panterid) values ";
    $sql .= "('{$customid}','{$namechinese}','{$currentDate}','{$panterid}')";
    $this->recordError("注册SQL：" . serialize($sql) . "\n\t", "YjTbpost", "createAccount");
    $customIf = $this->model->execute($sql);
    if ($customIf == true) {
      $cardNo = $this->getLvCards($panterid, $cardStart);
      $cardNo = $this->checkCardUsable($cardNo, $panterid, $cardbrand);
      if (!empty($cardNo)) {
        $bool = $this->opencard(array($cardNo), $customid, 0, $panterid, 1, $userid);
        if ($bool == true) {
          $this->model->commit();
          returnMsg(array('status' => '1', 'cardno' => $cardNo, 'termno' => $termno));
        } else {
          $this->model->rollback();
          returnMsg(array('status' => '04', 'codemsg' => '配卡失败'));
        }
      } else {
        $this->model->rollback();
        returnMsg(array('status' => '02', 'codemsg' => '卡池数量不足'));
      }
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '03', 'codemsg' => '会员创建失败'));
    }
  }

  //一家通宝订单穿透接口
  public function coinOrderAssyn()
  {
    return true;
    $data = getPostJson();
    $datami = trim($data['datami']);
    //$datami='WpLyprjGzj1LL/a90TaeDa2rZh2gTcEjKfazCWbNPSkw/hkKcFdwLVECoSoz ozgx45MmgLnFjwLatvEGtwtQ5nmijRfCz9LxcEm2zXG8JkBtxmun0biDaqoJ kIRWKI25o87YMdnYl8QguCaj1LyZeKrDdACgSitLWcataNnLJd6SYTficLI6 VH8bFG/GHYC+iNi22GtGJNjFPlBK/8axQtfGKYY/tXyWUqZ9G+74swxCAp0Q 6pk94a5lwx6kvoX4nAi7lCXVaQWQXpg4LRJV9PRSK+0QCaJMZbPwhLmVabJ4 NebFODDrBQ==';
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $datami = json_decode($datami, 1);
    $orderListStr = $datami['orderListStr'];
    $customid = $orderListStr['customId'];
    //$customid=encode('00185514');
    $payorder = $orderListStr['payorder'];
    //$payorder='0001-6d4e06730c414a27931d872101177537';
    $orderlist = $orderListStr['orderList'];

    $key = trim($datami['key']);
    $orderPrefix = $this->panterArr['1001']['prefix'];
    $checkKey = md5($this->keycode . json_encode($orderListStr));

    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    $this->recordData($datami);
    if (empty($customid)) {
      returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
    }
    if (empty($orderlist)) {
      returnMsg(array('status' => '04', 'codemsg' => '子订单不能为空'));
    }
    $list = M('eouttrade')->where(array('orderid' => $payorder))->select();
    if ($list != false) {
      returnMsg(array('status' => '05', 'codemsg' => '该订单已同步'));
    }
    $amount = 0;
    foreach ($orderlist as $key => $val) {
      if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $val['coinAmount']) || $val['coinAmount'] == 0) {
        returnMsg(array('status' => '06', 'codemsg' => '子订单通宝数据格式有误'));
      }
      if (empty($val['storeId']) || empty($this->storeArr[$val['storeId']])) {
        //returnMsg(array('status'=>'07','codemsg'=>'子订单店铺id缺失'));
        $orderlist[$key]['panterid'] = '00000286';
      } else {
        $orderlist[$key]['panterid'] = $this->storeArr[$val['storeId']];
      }
      //            if(empty($this->storeArr[$val['storeId']])){
      //                returnMsg(array('status'=>'08','codemsg'=>'子订单无对应店铺商户'));
      //            }
      $amount += $val['coinAmount'];
    }
    //$amount=0.01;
    if (empty($payorder)) {
      returnMsg(array('status' => '09', 'codemsg' => '订单编号缺失'));
    }
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '010', 'codemsg' => '非法会员编号'));
    }
    $trade = M('trade_wastebooks');
    $eorderid = $orderPrefix . $payorder;
    $map1 = array('tw.eorderid' => $eorderid);
    $list = $trade->alias('tw')->where($map1)->select();
    if ($list == false) {
      returnMsg(array('status' => '011', 'codemsg' => '查无订单'));
    }
    $customList = $trade->alias('tw')->join('cards c on c.cardno=tw.cardno')
      ->join('customs_c cc on cc.cid=c.customid')
      ->join('customs cu on cu.customid=cc.customid')
      ->where($map1)->field('cu.customid')->group('cu.customid')->select();
    if (count($customList) != 1) {
      returnMsg(array('status' => '012', 'codemsg' => '异常订单'));
    }
    if ($customid != $customList[0]['customid']) {
      returnMsg(array('status' => '013', 'codemsg' => '非本人订单编号'));
    }
    $map2 = array('eorderid' => $eorderid, 'tradepoint' => array('gt', 0), 'tradeamount' => 0);
    $consumeAmount = $trade->where($map2)->sum('tradepoint');
    if ($consumeAmount != $amount) {
      returnMsg(array('status' => '014', 'codemsg' => '消费金额与传入订单金额不符'));
    }

    $this->model->startTrans();
    $tradeSql = "update trade_wastebooks set flag='3' where eorderid='{$eorderid}'";
    //echo $tradeSql;exit;
    $tradeIf = $this->model->execute($tradeSql);
    //echo $this->model->getLastSql().'<br/>';
    $c = 0;
    foreach ($list as $key => $val) {
      $tradeid = $val['tradeid'];
      $cardno = $val['cardno'];
      $card = $this->cards->where(array('cardno' => $cardno))->field('customid')->find();

      $consumeList = M('coin_consume')->where(array('tradeid' => $tradeid, 'cardid' => $card['customid']))->select();
      $c1 = 0;
      foreach ($consumeList as $k => $v) {
        $coinAccountSql = "update coin_account set remindamount=remindamount+{$v['amount']} where coinid='{$v['coinid']}' and cardid='{$v['cardid']}'";
        $coinAccountIf = $this->model->execute($coinAccountSql);

        //echo $this->model->getLastSql().'<br/>';
        $accountSql = "update account set amount=amount+{$v['amount']} where customid='{$v['cardid']}' and type='01'";
        $accountIf = $this->model->execute($accountSql);
        //echo $this->model->getLastSql().'<br/>';

        $coinConsumecoinSql = "delete from coin_consume  where coinconsumeid='{$v['coinconsumeid']}' and cardid='{$v['cardid']}'";
        $coinConsumeIf = $this->model->execute($coinConsumecoinSql);
        //echo $this->model->getLastSql().'<br/>';

        if ($coinAccountSql == true && $accountSql == true && $coinConsumeIf == true) {
          $c1++;
        }
      }
      $c1 = 1;
      if ($c1 == count($list)) {
        $c++;
      }
    }

    $c2 = 0;
    //print_r($orderlist);exit;
    foreach ($orderlist as $key => $val) {
      $orderid = trim($val['orderId']);
      $coinAmount = trim($val['coinAmount']);
      $storeId = trim($val['storeId']);
      $panterid = empty($this->storeArr[$storeId]) ? '00000286' : $this->storeArr[$storeId];
      $sql = "insert into eouttrade values ('{$storeId}','{$panterid}','{$payorder}','{$coinAmount}','{$orderid}')";
      $eoutIf = $this->model->execute($sql);
      //echo $this->model->getLastSql().'<br/>';

      $coinConsumeArr = array(
        'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
        'panterid' => $panterid, 'type' => '01', 'amount' => $coinAmount, 'termno' => '00000001'
      );
      $coinConsumeIf = $this->coinExe($coinConsumeArr);
      //echo $this->model->getLastSql().'<br/>';
      if ($eoutIf == true && $coinConsumeIf == true) {
        $c2++;
      }
    }
    //exit;
    if ($c == count($list) && $c2 == count($orderlist) && $tradeIf == true) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '同步成功'));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '015', 'codemsg' => '同步失败'));
    }
  }

  //物业通宝消费订单穿透接口
  //物业穿透 bug 修复版本 --by wan
  public function wyOrderAssyn()
  {
    $data = getPostJson();
    //$data=json_decode('{"orderid":"s259960160bda68fd01616877cc7a766","amount":"0.06","areaname":"\u5f00\u5c01\u7199\u548c\u5e9c(\u6d4b\u8bd5)","customid":"MDAxNTAyMzkO0O0O","key":"34a053a1e8467ffb02a92bf0e98d5a92"}',1);
    $orderid = $data['orderid'];
    $amount = $data['amount'];
    $areaname = $data['areaname'];
    $customid = $data['customid'];
    $key = trim($data['key']);
    $checkKey = md5($this->keycode . $orderid . $amount . $areaname . $customid);

    $this->recordData(json_encode($data) . '--' . $this->keycode . '--' . $orderid . '--' . $amount . '--' . $areaname . '--' . $customid . '--' . $checkKey);
    if ($checkKey != $key) {
      returnMsg(array('status' => '01', 'codemsg' => '无效秘钥，非法传入'));
    }
    //eorderid 一家真实支付id
    $eorderid = 'e+_0001-' . $orderid;
    if (empty($orderid)) {
      returnMsg(array('status' => '02', 'codemsg' => '订单编号不能为空'));
    }
    $list = M('eouttrade')->where(array('orderid' => $eorderid))->select();
    if ($list != false) {
      returnMsg(array('status' => '03', 'codemsg' => '该订单已同步'));
    }
    if (empty($areaname)) {
      returnMsg(array('status' => '04', 'codemsg' => '小区名字为空'));
    }
    $map = array('areaname' => $areaname);
    $paterInfo = M('wy_area')->where($map)->find();
    if (empty($paterInfo)) {
      returnMsg(array('status' => '05', 'codemsg' => '未匹配到对应物业公司'));
    }
    $panterid = $paterInfo['panterid'];
    if (empty($panterid)) {
      returnMsg(array('status' => '011', 'codemsg' => '该小区尚未分配物业'));
    }
    //        $panterid='00000280';
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '05', 'codemsg' => '非法会员编号'));
    }
    $trade = M('trade_wastebooks');
    $twMap = array('tw.eorderid' => $eorderid);
    $list = $trade->alias('tw')->where($twMap)->select();
    if ($list == false) {
      returnMsg(array('status' => '06', 'codemsg' => '查无订单'));
    }
    $customList = $trade->alias('tw')->join('cards c on c.cardno=tw.cardno')
      ->join('customs_c cc on cc.cid=c.customid')
      ->join('customs cu on cu.customid=cc.customid')
      ->where($twMap)->field('cu.customid')->group('cu.customid')->select();

    if (count($customList) != 1) {
      returnMsg(array('status' => '07', 'codemsg' => '异常订单'));
    }
    if ($customid != $customList[0]['customid']) {
      returnMsg(array('status' => '08', 'codemsg' => '非本人订单编号'));
    }
    $map2 = array('eorderid' => $eorderid, 'tradepoint' => array('gt', 0), 'tradeamount' => 0);
    $consumeAmount = $trade->where($map2)->sum('tradepoint');
    //echo $trade->getLastSql();exit;
    if ($consumeAmount != $amount) {
      returnMsg(array('status' => '09', 'codemsg' => '消费金额与传入订单金额不符'));
    }

    $this->model->startTrans();
    $tradeSql = "update trade_wastebooks set panterid='{$panterid}' where eorderid='{$eorderid}'";
    $tradeIf = $this->model->execute($tradeSql);
    $coinWhere['tradeid'] = ['in', array_column($list, 'tradeid')];
    //        $card=$this->cards->where(array('cardno'=>$cardno))->field('customid')->find();
    $consumeList = M('coin_consume')->where($coinWhere)->select();

    $c = 0;
    foreach ($consumeList as $k => $v) {

      $coinConsumecoinSql = "update coin_consume set panterid='{$panterid}'  where coinconsumeid='{$v['coinconsumeid']}' and cardid='{$v['cardid']}'";
      $coinConsumeIf = $this->model->execute($coinConsumecoinSql);
      //echo $this->model->getLastSql().'<br/>';

      if ($coinConsumeIf == true) {
        $c++;
      }
    }
    $sql = "insert into eouttrade values ('','{$panterid}','{$eorderid}','{$consumeAmount}','')";
    $eoutIf = $this->model->execute($sql);
    if ($c == count($consumeList) && $tradeIf == true && $eoutIf == true) {
      $this->model->commit();
      returnMsg(array('status' => '1', 'codemsg' => '同步成功'));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '010', 'codemsg' => '同步失败'));
    }
  }
  //    public function wyOrderAssyn(){
  //        $data=getPostJson();
  //        //$data=json_decode('{"orderid":"s259960160bda68fd01616877cc7a766","amount":"0.06","areaname":"\u5f00\u5c01\u7199\u548c\u5e9c(\u6d4b\u8bd5)","customid":"MDAxNTAyMzkO0O0O","key":"34a053a1e8467ffb02a92bf0e98d5a92"}',1);
  //        $orderid=$data['orderid'];
  //        $amount=$data['amount'];
  //        $areaname=$data['areaname'];
  //        $customid=$data['customid'];
  //        $key=trim($data['key']);
  //        $checkKey=md5($this->keycode.$orderid.$amount.$areaname.$customid);
  //
  //        $this->recordData(json_encode($data).'--'.$this->keycode.'--'.$orderid.'--'.$amount.'--'.$areaname.'--'.$customid.'--'.$checkKey);
  //        if($checkKey!=$key){
  //            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
  //        }
  ////        $orderid='0001-561b08e4e3d340b0a2c2f86450859e27';
  ////        $customid=encode('00185514');
  ////        $amount='10';
  ////        $areaname='建业小区';
  //        if(empty($orderid)){
  //            returnMsg(array('status'=>'02','codemsg'=>'订单编号不能为空'));
  //        }
  //        $list=M('eouttrade')->where(array('orderid'=>$orderid))->select();
  //        if($list!=false){
  //            returnMsg(array('status'=>'03','codemsg'=>'该订单已同步'));
  //        }
  //        if(empty($areaname)){
  //            returnMsg(array('status'=>'04','codemsg'=>'小区名字为空'));
  //        }
  //        $map=array('areaname'=>$areaname);
  //        $paterInfo=M('wy_area')->where($map)->find();
  //        if(empty($paterInfo)){
  //            returnMsg(array('status'=>'05','codemsg'=>'未匹配到对应物业公司'));
  //        }
  //        $panterid=$paterInfo['panterid'];
  //        if(empty($panterid)){
  //            returnMsg(array('status'=>'011','codemsg'=>'该小区尚未分配物业'));
  //        }
  ////        $panterid='00000280';
  //        $customid=decode($customid);
  //        $bool=$this->checkCustom($customid);
  //        if($bool==false){
  //            returnMsg(array('status'=>'05','codemsg'=>'非法会员编号'));
  //        }
  //        $trade=M('trade_wastebooks');
  //        $eorderid='e+_0001-'.$orderid;
  //        $map1=array('tw.eorderid'=>$eorderid);
  //        $list=$trade->alias('tw')->where($map1)->find();
  //        if($list==false){
  //            returnMsg(array('status'=>'06','codemsg'=>'查无订单'));
  //        }
  //        $customList=$trade->alias('tw')->join('cards c on c.cardno=tw.cardno')
  //            ->join('customs_c cc on cc.cid=c.customid')
  //            ->join('customs cu on cu.customid=cc.customid')
  //            ->where($map1)->field('cu.customid')->group('cu.customid')->select();
  //
  //        if(count($customList)!=1){
  //            returnMsg(array('status'=>'07','codemsg'=>'异常订单'));
  //        }
  //        if($customid!=$customList[0]['customid']){
  //            returnMsg(array('status'=>'08','codemsg'=>'非本人订单编号'));
  //        }
  //        $map2=array('eorderid'=>$eorderid,'tradepoint'=>array('gt',0),'tradeamount'=>0);
  //        $consumeAmount=$trade->where($map2)->sum('tradepoint');
  //        //echo $trade->getLastSql();exit;
  //        if($consumeAmount!=$amount){
  //            returnMsg(array('status'=>'09','codemsg'=>'消费金额与传入订单金额不符'));
  //        }
  //
  //        $this->model->startTrans();
  //        $tradeSql="update trade_wastebooks set panterid='{$panterid}' where eorderid='{$eorderid}'";
  //        //echo $tradeSql;exit;
  //        $tradeIf=$this->model->execute($tradeSql);
  //        //echo $this->model->getLastSql().'<br/>';
  //        $tradeid=$list['tradeid'];
  //        $cardno=$list['cardno'];
  //        $card=$this->cards->where(array('cardno'=>$cardno))->field('customid')->find();
  //        $consumeList=M('coin_consume')->where(array('tradeid'=>trim($tradeid),'cardid'=>$card['customid']))->select();
  //
  //        $c=0;
  //        foreach($consumeList as $k=>$v){
  //
  //            $coinConsumecoinSql="update coin_consume set panterid='{$panterid}'  where coinconsumeid='{$v['coinconsumeid']}' and cardid='{$v['cardid']}'";
  //            $coinConsumeIf=$this->model->execute($coinConsumecoinSql);
  //            //echo $this->model->getLastSql().'<br/>';
  //
  //            if($coinConsumeIf==true){
  //                $c++;
  //            }
  //        }
  //        $sql="insert into eouttrade values ('','{$panterid}','{$eorderid}','{$list['tradepoint']}','')";
  //        $eoutIf=$this->model->execute($sql);
  //        if($c==count($consumeList)&&$tradeIf==true&&$eoutIf==true){
  //            $this->model->commit();
  //            returnMsg(array('status'=>'1','codemsg'=>'同步成功'));
  //        }else{
  //            $this->model->rollback();
  //            returnMsg(array('status'=>'010','codemsg'=>'同步失败'));
  //        }
  //    }

  public function newtest()
  {
    echo decode('MDAyNDA0NTUO0O0O') . '<br/>';
    exit;
    set_time_limit(0);
    $array = array();
    $panterid = '00001001';
    $userid = '0000000000000000';
    //print_r($array);exit;
    foreach ($array as $key => $val) {
      $customid = $this->getFieldNextNumber("customid");
      $namechinese = '九道弯' . intval($customid);
      $currentDate = date('Ymd', time());
      $this->model->startTrans();
      $sql = "INSERT INTO CUSTOMS(customid,namechinese,placeddate,panterid) values ";
      $sql .= "('{$customid}','{$namechinese}','{$currentDate}','{$panterid}')";
      $customIf = $this->model->execute($sql);
      if ($customIf == true) {
        $cardno = $val['cardno'];
        $bool = $this->opencard(array($cardno), $customid, 0, $panterid, 1, $userid);
        if ($bool == true) {
          $this->model->commit();
          echo $cardno . '开卡成功<br/>';
        } else {
          $this->model->rollback();
          echo $cardno . '开卡失败<br/>';
        }
      } else {
        $this->model->rollback();
        echo $cardno . '创建会员失败<br/>';
      }
    }
  }

  public function getJlhInfo()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $linktel = trim($datami['linktel']);
    //$customid=trim('MDAwMDAzNTYO0O0O ');
    $key = trim($datami['key']);
    $checkKey = md5($this->keycode . $linktel);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    //$linktel='13425715172';
    if (empty($linktel)) {
      returnMsg(array('status' => '03', 'codemsg' => '手机号缺失'));
    }
    $map = array('cu.linktel' => $linktel, 'cu.customlevel' => '建业线上会员', 'c.panterid' => '00000447', 'c.cardkind' => '6888');
    $custom = M('cards')->alias('c')->join('customs_c cc on cc.cid=c.customid')
      ->join('customs cu on cu.customid=cc.customid')->where($map)->field('cc.customid')->find();
    if ($custom == false) {
      returnMsg(array('status' => '04', 'codemsg' => '查无此会员'));
    }
    $card = M('cards')->alias('c')->join('customs_c cc on cc.cid=c.customid')
      ->join('customs cu on cu.customid=cc.customid')
      ->where(array('cu.linktel' => $linktel, 'c.panterid' => '00000447', 'c.cardkind' => '6888', 'c.status' => 'Y'))
      ->field('c.cardno')
      ->find();
    if ($card != false) {
      returnMsg(array('status' => '1', 'customid' => encode($custom['customid']), 'cardno' => $card['cardno']));
    } else {
      returnMsg(array('status' => '05', 'codemsg' => '该会员无君邻会卡'));
    }
  }

  public function test111()
  {
    $datami = '{"customid":"00126804","balanceAmount":"90","coinAmount":"0","orderId":"0001-s23226ed4f3517d27f2df2d1331f85896","sourceCode":"1001"}';
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $balanceAmount = trim($datami['balanceAmount']);
    $coinAmount = trim($datami['coinAmount']);
    $payPwd = trim($datami['payPwd']);
    $key = trim($datami['key']);
    $orderid = trim($datami['orderId']);
    $backUrl = trim($datami['backUrl']);
    $sourceCode = trim($datami['sourceCode']);
    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $tradeLogs = M('trade_logs');
    $where = array('customid' => $customid, 'placeddate' => date('Ymd'));
    $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
    $date = time();
    if ($trade_logs_list != false) {
      if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
        returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
      }
    }
    $currentDate = date('Ymd');
    $currentTime = date('H:i:s');
    $sql = "insert into trade_logs (customid,CODECUSTOMID,placeddate,placedtime,datetimes,orderid) values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}')";
    //echo $sql;exit;
    $tradeLogs->execute($sql);
    unset($sql);


    $balanceAccount = $this->accountQuery($customid, '00');
    echo $balanceAccount;

    if ($balanceAccount < $balanceAmount) {
      $walletAccount = $this->getWalletAccount($customid);
      if ($walletAccount !== false) {
        if (($balanceAccount + $walletAccount) < $balanceAmount) {
          returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
        }
      } else {
        returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
      }
    }
    //$coinAccount=$this->accountQuery($customid,'01');
    $coinAccount = $this->coinQuery($customid);
    if ($coinAccount < $coinAmount) {
      returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
    }
    $this->model->startTrans();
    $balanceConsumeIf = $coinConsumeIf = false;

    //余额消费，金额为0不执行
    if ($balanceAmount > 0) {
      $map = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '00');
      $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradeamount');
      if ($balanceConsume > 0) {
        returnMsg(array('status' => '014', 'codemsg' => '此订单已进行余额支付，请勿重复提交'));
      }
      if ($balanceAmount > $balanceAccount) {
        $walletConsumeAmount = $balanceAmount - $balanceAccount;
        $balanceConsumeAmount = $balanceAmount - $walletConsumeAmount;
      } else {
        $walletConsumeAmount = 0;
        $balanceConsumeAmount = $balanceAmount;
      }
      $balanceConsumeArr = array(
        'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
        'panterid' => $panterid, 'type' => '00', 'amount' => $balanceConsumeAmount,
        'walletConsumeAmount' => $walletConsumeAmount
      );
      $balanceConsumeIf = $this->consumeExe($balanceConsumeArr);

      //var_dump($balanceConsumeIf);exit;
      if ($walletConsumeAmount > 0 && $balanceConsumeIf == true) { }
    } else {
      $balanceConsumeIf = true;
    }
    //建业币消费，金额为0不执行
    if ($coinAmount > 0) { } else {
      $coinConsumeIf = true;
    }
    if ($balanceConsumeIf == true && $coinConsumeIf == true) {
      $this->model->commit();
      ob_clean();
      echo json_encode(array('status' => '1', 'info' => array('reduceBalance' => floatval($balanceAmount), 'reduceCoin' => floatval($coinAmount))));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
    }
  }

  public function test222()
  {
    //$datami='{"customid":"00126804","balanceAmount":"90","coinAmount":"0","orderId":"0001-s23226ed4f3517d27f2df2d1331f8ff96","sourceCode":"1001"}';
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $balanceAmount = trim($datami['balanceAmount']);
    $coinAmount = trim($datami['coinAmount']);
    $payPwd = trim($datami['payPwd']);
    $key = trim($datami['key']);
    $orderid = trim($datami['orderId']);
    $backUrl = trim($datami['backUrl']);
    $sourceCode = trim($datami['sourceCode']);
    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];
    $tradeLogs = M('trade_logs');
    $where = array('customid' => $customid, 'placeddate' => date('Ymd'));
    $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
    $date = time();
    if ($trade_logs_list != false) {
      if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
        returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
      }
    }
    $currentDate = date('Ymd');
    $currentTime = date('H:i:s');
    $sql = "insert into trade_logs (customid,CODECUSTOMID,placeddate,placedtime,datetimes,orderid) values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}')";
    $tradeLogs->execute($sql);
    unset($sql);


    $balanceAccount = $this->accountQuery($customid, '00');

    if ($balanceAccount < $balanceAmount) {
      $walletAccount = $this->getWalletAccount($customid);
      if ($walletAccount !== false) {
        if (($balanceAccount + $walletAccount) < $balanceAmount) {
          returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
        }
      } else {
        returnMsg(array('status' => '09', 'codemsg' => '账户金额不足'));
      }
    }
    //$coinAccount=$this->accountQuery($customid,'01');
    $coinAccount = $this->coinQuery($customid);
    if ($coinAccount < $coinAmount) {
      returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
    }
    $this->model->startTrans();
    $balanceConsumeIf = $coinConsumeIf = false;

    //余额消费，金额为0不执行
    if ($balanceAmount > 0) {
      $map = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '00');
      $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradeamount');
      if ($balanceConsume > 0) {
        returnMsg(array('status' => '014', 'codemsg' => '此订单已进行余额支付，请勿重复提交'));
      }
      if ($balanceAmount > $balanceAccount) {
        $walletConsumeAmount = $balanceAmount - $balanceAccount;
        $balanceConsumeAmount = $balanceAmount - $walletConsumeAmount;
      } else {
        $walletConsumeAmount = 0;
        $balanceConsumeAmount = $balanceAmount;
      }
      $balanceConsumeArr = array(
        'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
        'panterid' => $panterid, 'type' => '00', 'amount' => $balanceConsumeAmount,
        'walletConsumeAmount' => $walletConsumeAmount
      );
      $balanceConsumeIf = $this->consumeExe($balanceConsumeArr);

      //var_dump($balanceConsumeIf);exit;
      if ($walletConsumeAmount > 0 && $balanceConsumeIf == true) { }
    } else {
      $balanceConsumeIf = true;
    }
    //建业币消费，金额为0不执行
    if ($coinAmount > 0) { } else {
      $coinConsumeIf = true;
    }
    if ($balanceConsumeIf == true && $coinConsumeIf == true) {
      $this->model->commit();
      ob_clean();
      echo json_encode(array('status' => '1', 'info' => array('reduceBalance' => floatval($balanceAmount), 'reduceCoin' => floatval($coinAmount))));
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
    }
  }

  public function barcode()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $json = $this->DESedeCoder->decrypt($datami);
    if ($json == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $post = json_decode($json, true);
    $customid = trim($post['customid']);
    $key = trim($post['key']);
    $checkKey = md5($this->keycode . $customid);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) returnMsg(array('status' => '03', 'codemsg' => 'customid 为空'));
    $customid = decode($customid);
    $map['customid'] = $customid;
    if (M('customs')->where($map)->find()) {
      $info = $this->barEncode($customid);
      $barcode = $info['barcode'];
      $placeddate = date('Ymd', $info['time']);
      $placedtime = date('H:i:s', $info['time']);
      $generator = new BarcodeGeneratorPNG();
      $url = ltrim($generator->getBarcode($barcode, $generator::TYPE_CODE_128), '.');
      $sql = "INSERT INTO barcode (codeid,customid,placeddate,placedtime) VALUES ('{$barcode}','{$customid}','{$placeddate}','{$placedtime}')";
      $model = new Model();
      try {
        $bool = $model->execute($sql);
      } catch (\Exception $e) {
        returnMsg(array('status' => '03', 'codemsg' => $e->getMessage()));
      }
      returnMsg(array('status' => '1', 'codemsg' => 'ok', 'barcode' => $barcode, 'url' => $url));
    } else {
      returnMsg(array('status' => '04', 'codemsg' => '无效customid'));
    }
  }

  private function barEncode($customid)
  {
    $arr = str_split($customid);
    $zero = array_keys($arr, '0');
    $customid = str_replace('0', '', $customid);
    $not = str_split($customid);
    $sum = array_sum($not);
    $not[0] = 10 - $not[0];

    $first = implode($not);
    $second = rand(100, 999);

    foreach ($zero as $val) {
      $second .= $val + 1;
    }
    $count = count($not);
    $second = $second . $count;
    //时间记录
    $time = time();
    $third = substr($time, -4) . (100 - $count - $sum);
    return ['barcode' => $first . $second . $third, 'time' => $time];
  }

  //    public function periodvalidity(){
  //        $customid = trim(I('get.cid',''));
  //        $customid != '' || die('缺失用户id');
  //        $customid = decode($customid);
  //
  //        //获取通宝列表
  //        $model   =  new Model();
  //        $subSql     = "(select rechargeamount,remindamount,enddate,panterid,placeddate from coin_account where cardid in (SELECT cid FROM customs_c WHERE customid = '{$customid}') ORDER BY enddate ASC)";
  //        $field      = "coin.rechargeamount,coin.remindamount,coin.placeddate,coin.enddate,p.namechinese";
  //        $list    = $model->table($subSql)->alias('coin')
  //                         ->join('left join panters p on p.panterid=coin.panterid')
  //                         ->field($field)->select() ;
  //        $now     = date('Ymd');
  //
  //        if($list){
  //            foreach ($list as $key=>$val){
  //                if($val['remindamount']<1){
  //                    $val['remindamount'] = bcadd(0,$val['remindamount'],2);
  //                }
  //                if($val['enddate']>=$now){
  //                    $val['period'] = 1;
  //                }else{
  //                    $val['period'] = 0;
  //                }
  //                $list[$key] = $val;
  //            }
  //        }
  //        $this->assign('list',$list);
  //        $this->display();
  //    }

  public function ejiaPeriodvalidity()
  {
    //{"access_token":"428ebb2c94b74734b547fe584cbc563a"}access_token=428ebb2c94b74734b547fe584cbc563a
    $access_token = trim(I('get.access_token', ''));
    $access_token != '' || die('缺失access_token');
    $post['access_token'] = $access_token;

    $url = "http://o2o.yijiahn.com/jyo2o_web/app/user/getcustomid.json";
    $info = $this->getQuery($url, $post);

    if ($info['code'] == '100') {
      $customid = trim($info['data']['customid']);
      $customid = decode($customid);
    } else {
      die("获取一家customid 失败");
    }
    //获取通宝列表
    $model = new Model();
    $subSql = "(select rechargeamount,remindamount,enddate,panterid,placeddate from coin_account where cardid in (SELECT cid FROM customs_c WHERE customid = '{$customid}') ORDER BY enddate ASC)";
    $field = "coin.rechargeamount,coin.remindamount,coin.placeddate,coin.enddate,p.namechinese";
    $list = $model->table($subSql)->alias('coin')
      ->join('left join panters p on p.panterid=coin.panterid')
      ->field($field)->select();
    $now = date('Ymd');

    if ($list) {
      foreach ($list as $key => $val) {
        if ($val['remindamount'] < 1) {
          $val['remindamount'] = bcadd(0, $val['remindamount'], 2);
        }
        if ($val['enddate'] >= $now) {
          $val['period'] = 1;
        } else {
          $val['period'] = 0;
        }
        $list[$key] = $val;
      }
    }
    $this->assign('list', $list);
    $this->display('periodvalidity');
  }

  // 一家通宝消费免密 支付
  public function coinConsumeNoPW()
  {
    $post = getPostJson();
    $datami = trim($post['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $balanceAmount = trim($datami['balanceAmount']);
    $coinAmount = trim($datami['coinAmount']);
    $key = trim($datami['key']);
    $orderid = trim($datami['orderId']);
    $backUrl = trim($datami['backUrl']);
    $sourceCode = trim($datami['sourceCode']);
    $panterid = $this->panterArr[$sourceCode]['panterid'];
    $orderPrefix = $this->panterArr[$sourceCode]['prefix'];

    $checkKey = md5($this->keycode . $customid . $balanceAmount . $coinAmount . $orderid . $backUrl . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) {
      returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
    }
    $balanceAmount == 0 || returnMsg(array('status' => '04', 'codemsg' => '余额金额有误'));
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $coinAmount)) {
      returnMsg(array('status' => '05', 'codemsg' => '建业通宝格式有误'));
    }
    if ($coinAmount <= 0) {
      returnMsg(array('status' => '06', 'codemsg' => '通宝消费数据有误'));
    }
    if (empty($orderid)) {
      returnMsg(array('status' => '07', 'codemsg' => '订单编号缺失'));
    }
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '08', 'codemsg' => '非法会员编号'));
    }
    $tradeLogs = M('trade_logs');
    $where = array('customid' => $customid, 'placeddate' => date('Ymd'));
    $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
    $date = time();
    if ($trade_logs_list != false) {
      if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
        returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
      }
    }
    $currentDate = date('Ymd');
    $currentTime = date('H:i:s');
    $sql = "insert into trade_logs (customid,CODECUSTOMID,placeddate,placedtime,datetimes,orderid) values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}')";
    $tradeLogs->execute($sql);
    unset($sql);
    $coinAccount = $this->coinQuery($customid);
    if ($coinAccount < $coinAmount) {
      returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
    }
    $this->model->startTrans();
    $map = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '00');
    $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradepoint');
    //echo M('trade_wastebooks')->getLastSql();exit;
    if ($balanceConsume > 0) {
      returnMsg(array('status' => '015', 'codemsg' => '此订单已进行建业通宝支付，请勿重复提交'));
    }

    $coinConsumeArr = array(
      'customid' => $customid, 'orderid' => $orderPrefix . $orderid,
      'panterid' => $panterid, 'type' => '01', 'amount' => $coinAmount
    );
    $coinConsumeIf = $this->consumeCoin($coinConsumeArr);
    //        $coinConsumeIf=$this->coinExe($coinConsumeArr);
    if ($coinConsumeIf) {
      //---------向一家传输用户通宝信息--17:21----
      $de = new YjDes();
      $tb_info = D("Ehome")->getTbinfo($customid);
      if ($tb_info) {
        $appid = 'SOON-ZZUN-0001';
        $tb_info['customid'] = encode($tb_info['customid']);
        $tb_info['activetype'] = 2;
        $tb_info['appid'] = $appid;
        $tb_sign = $de->encrypt($tb_info);
        $tb_data = json_encode($tb_info, JSON_FORCE_OBJECT);
        $return_yj = $this->curlPost(C('ehome_potb'), $tb_data, $tb_sign);
        $return_arr = json_decode($return_yj, 1);

        if ($return_arr['code'] == '100') {
          $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "success");
        } else {
          $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "failed");
        }
      }
    }
    if ($coinConsumeIf == true) {
      $this->model->commit();
      ob_clean();
      echo json_encode(array('status' => '1', 'info' => array('reduceBalance' => floatval($balanceAmount), 'reduceCoin' => floatval($coinAmount)), 'publish' => $coinConsumeIf));
      if (!empty($backUrl)) {
        $backUrl = urldecode($backUrl);
        $backData = array('orderid' => $orderid, 'consumeAmount' => $balanceAmount, 'coinAmount' => $coinAmount, 'payRes' => 1);
        crul_post($backUrl, json_encode($backData));
        $this->recordMsg($orderid, $balanceAmount, $coinAmount, $backUrl);
      }
    } else {
      $this->model->rollback();
      returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
    }
  }

  public function coinExpiredInfo()
  {
    $post = getPostJson();
    $datami = trim($post['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    $datami = json_decode($datami, 1);
    $code = trim($datami['code']);
    $key = trim($datami['key']);
    $checkKey = md5($this->keycode . $code);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    $expired = date('Ymd', time() + 60 * 86400);
    $now = date('Ymd');
    $map['enddate'] = [['egt', $now], ['elt', $expired]];
    $field = 'enddate,cardid,sum(remindamount) reamount,placeddate';
    $subSql = M('coin_account')->where($map)->field($field)
      ->group('enddate,cardid,placeddate')->having('sum(remindamount)>=50')->order('enddate asc')->buildSql();
    $model = new Model();
    $field = 'cu.customid,main.enddate,main.cardid,main.reamount,main.placeddate';
    $info = $model->table($subSql . " main")
      ->join('left join customs_c cc on cc.cid=main.cardid')
      ->join('left join customs cu on cu.customid = cc.customid')
      ->field($field)->order('main.enddate asc')->select();
    if (!$info) returnMsg(['status' => '2', 'comdmsg' => '无60天内过期的通宝']);

    //查询账户总额
    $where['cu.customid'] = ['in', array_column($info, 'customid')];
    $where['ca.cardkind'] = '6889';
    $where['ca.cardfee'] = ['in', ['1', '2']];

    $field = 'cu.customid,cu.linktel,count(distinct(ca.cardno)) cardnum,sum(coin.remindamount) total';
    $total = $model->table('customs')->alias('cu')
      ->join('left join customs_c cc on cc.customid=cu.customid')
      ->join('left join cards ca on ca.customid=cc.cid')
      ->join('left join coin_account coin on coin.cardid=ca.customid')
      ->where($where)->group('cu.customid,cu.linktel')->field($field)->select();
    $totalInfo = array_combine(array_column($total, 'customid'), $total);
    $now = strtotime(date('Ymd'));
    $json = [];
    foreach ($info as $key => $val) {
      $days = (strtotime($val['enddate']) - $now) / 86400;
      $unit['customid'] = encode($val['customid']);
      $unit['amounttotal'] = $totalInfo[$val['customid']]['total'];
      $unit['amountadvent'] = $val['reamount'];
      $unit['cardnum'] = $totalInfo[$val['customid']]['cardnum'];
      $unit['days'] = $days;
      $unit['enddate'] = $val['enddate'];
      $unit['linktel'] = $totalInfo[$val['customid']]['linktel'];
      $unit['placeddate'] = $val['placeddate'];
      switch (true) {
        case $days == 1;
          $json['list_1'][] = $unit;
          break;
        case $days == 3;
          $json['list_3'][] = $unit;
          break;
        case $days == 7;
          $json['list_7'][] = $unit;
          break;
        case $days == 15;
          $json['list_15'][] = $unit;
          break;
        case $days == 30;
          $json['list_30'][] = $unit;
          break;
        case $days == 60;
          $json['list_60'][] = $unit;
          break;
        default:
          break;
      }
    }
    returnMsg(['status' => '1', 'expired' => $json]);
  }

  protected function getExpiredCoinByCustomid($customid)
  {
    $now = date('Ymd');
    $map['enddate'] = ['egt', $now];

    $field = 'enddate,cardid,sum(remindamount) reamount';
    $subSql = M('coin_account')->where($map)->field($field)
      ->group('enddate,cardid')->having('sum(remindamount)>0')->order('enddate asc')->buildSql();
    $model = new Model();
    $where['cu.customid'] = $customid;
    $field = 'cu.customid,main.enddate,main.cardid,main.reamount';
    $info = $model->table($subSql . " main")
      ->join('left join customs_c cc on cc.cid=main.cardid')
      ->join('left join customs cu on cu.customid = cc.customid')
      ->where($where)
      ->field($field)->order('main.enddate asc')->select();
    $now = strtotime(date('Ymd'));
    if ($info) {
      return ['amount' => $info[0]['reamount'], 'enddate' => $info[0]['enddate'], 'days' => (strtotime($info[0]['enddate']) - $now) / 86400];
    } else {
      return false;
    }
  }

  //通宝一家查询发行记录
  public function getCoinPublishRecords()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);
    $customid = trim($datami['customid']);
    $linketl = trim($datami['linktel']);
    $date = trim($datami['date']);
    $key = trim($datami['key']);
    $checkKey = md5($this->keycode . $customid . $linketl . $date);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) {
      returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
    }
    $customid = decode($customid);
    $bool = $this->checkCustom($customid);
    if ($bool == false) {
      returnMsg(array('status' => '08', 'codemsg' => '非法会员编号'));
    }
    if ($date == '') $date = date('Ymd');
    $map['placeddate'] = date('Ymd', strtotime($date));

    $cid = $this->model->table('customs_c')->where(['customid' => $customid])->field('cid')->select();
    if ($cid) {
      $cid = array_column($cid, 'cid');
      $map['cardid'] = ['in', $cid];
      $info = $this->model->table('coin_account')->where($map)->field('sum(rechargeamount) amount')->find();
      if ($info) {
        $msg['amount'] = $info['amount'];
        $msg['enddate'] = date('Y-m-d', strtotime($date . "+2 years"));
        returnMsg(['status' => '1', 'amount' => $msg['amount'], 'enddate' => $msg['enddate'], 'codemsg' => '查询成功']);
      } else {
        returnMsg(['status' => '2', 'codemsg' => '无发行记录']);
      }
    } else {
      returnMsg(['status' => '2', 'codemsg' => '无发行记录']);
    }
  }

  //余额充值退款接口
  public function refundCharge()
  {
    $post = getPostJson();
    $datami = trim($post['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $datami = json_decode($datami, 1);

    $customid = trim($datami['customid']);
    $amount = trim($datami['amount']);
    $orderid = trim($datami['orderid']);
    $sourceCode = trim($datami['sourceCode']);
    $key = trim($datami['key']);

    $checkKey = md5($this->keycode . $customid . $amount . $orderid . $sourceCode);
    if ($checkKey != $key) {
      returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
    }
    if (empty($customid)) {
      returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
    }
    $amount != 0 || returnMsg(array('status' => '04', 'codemsg' => '退款金额需大于0'));
    if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $amount)) {
      returnMsg(array('status' => '05', 'codemsg' => '退款金额格式有误'));
    }

    //
    $eorderid = 'e+_' . $orderid;
    $map['description'] = ['like', '%' . $eorderid];
    $purchaseInfo = M('card_purchase_logs')->where($map)->field('purchaseid,amount,cardno')->select();
    if ($purchaseInfo) {
      $count = count($purchaseInfo);
      if ($count == 1) {
        $this->sigleRefund($purchaseInfo[0], $amount, $eorderid);
      } else {
        $this->sameRefund($purchaseInfo, $amount, $eorderid);
      }
    } else {
      returnMsg(array('status' => '06', 'codemsg' => '无效订单号'));
    }
  }

  //单笔充值退款
  protected function sigleRefund($purchaseInfo, $amount, $eorderid)
  {
    $purchaseInfo['amount'] >= $amount || returnMsg(array('status' => '05', 'codemsg' => '退款金额超额'));

    $customInfo = M('cards')->alias('ca')->join('account ac on ac.customid=ca.customid')
      ->where(['ca.cardno' => $purchaseInfo['cardno'], 'ca.status' => 'Y', 'ac.type' => '00'])
      ->field('ac.amount,ac.customid')->find();
    if ($customInfo) {
      $customInfo['amount'] >= $amount || returnMsg(array('status' => '10', 'codemsg' => '账户余额不足,无法退款'));
      $map['purchaseid'] = $purchaseInfo['purchaseid'];
      $this->model->startTrans();
      try {
        if ($purchaseInfo['amount'] == $amount) {
          $cancel['flag'] = '3';
          $capIf = M('card_purchase_logs')->where($map)->save($cancel);
          $cupIf = M('custom_purchase_logs')->where($map)->save($cancel);
        } else {
          $saveAmount = bcsub($purchaseInfo['amount'], $amount, 2);
          $capIf = M('card_purchase_logs')->where($map)->save(['amount' => $saveAmount]);
          $save = [
            'amount' => $saveAmount, 'totalmoney' => $saveAmount,
            'realamount' => $saveAmount, 'writeamount' => $saveAmount
          ];
          $cupIf = M('custom_purchase_logs')->where($map)->save($save);
        }
        $saveBalance = bcsub($customInfo['amount'], $amount, 2);
        $accIf = M('account')->where(['customid' => $customInfo['customid'], 'type' => '00'])->save(['amount' => $saveBalance]);
        $nowdate = date('Ymd');
        $nowtime = date('H:i:s');
        $sql = "INSERT INTO charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)VALUES";
        $sql .= "('{$purchaseInfo['purchaseid']}','{$purchaseInfo['cardno']}','{$amount}','2','{$nowdate}','{$nowtime}','0000000000000000','{$purchaseInfo['amount']}','{$eorderid}')";

        $cancleIf = M('charge_cancle_logs')->execute($sql);
        if ($capIf && $cupIf && $accIf && $cancleIf) {

          $this->model->commit();
          returnMsg(array('status' => '1', 'codemsg' => '退款成功', 'refund' => $amount));
        } else {
          $this->model->rollback();
          returnMsg(array('status' => '10', 'codemsg' => '数据库修改失败'));
        }
      } catch (\Exception $e) {
        $this->model->rollback();
        returnMsg(array('status' => '10', 'codemsg' => $e->getMessage()));
      }
    } else {
      returnMsg(array('status' => '09', 'codemsg' => '用户账户信息异常'));
    }
  }

  //拆弹充值退款
  protected function sameRefund($purchaseInfo, $amount, $eorderid)
  {
    $totalChrage = array_sum(array_column($purchaseInfo, 'amount'));
    $totalChrage >= $amount || returnMsg(array('status' => '05', 'codemsg' => '退款金额超额'));
    $cardArray = array_column($purchaseInfo, 'cardno');
    $customInfo = M('cards')->alias('ca')->join('account ac on ac.customid=ca.customid')
      ->where(['ca.cardno' => ['in', $cardArray], 'ca.status' => 'Y', 'ac.type' => '00'])
      ->field('ac.amount,ac.customid,ca.cardno')->select();

    $linkCustomid = array_combine(array_column($customInfo, 'cardno'), array_column($customInfo, 'customid'));
    $linkAmount = array_combine(array_column($customInfo, 'customid'), array_column($customInfo, 'amount'));
    if ($customInfo) {
      array_sum(array_column($customInfo, 'amount')) >= $amount ||
        returnMsg(array('status' => '10', 'codemsg' => '账户余额不足,无法退款'));
      $nowdate = date('Ymd');
      $nowtime = date('H:i:s');
      $userid = '0000000000000000';
      $cancelSql = '';
      $accSql = '';
      if (bccomp($totalChrage, $amount, 2) === 0) {
        $yuan = false;
        foreach ($purchaseInfo as $key => $val) {
          if ($linkAmount[$linkCustomid[$val['cardno']]] >= $val['amount']) {
            $yuan = true;
          } else {
            $yuan = false;
            break;
          }
        }
        if ($yuan === true) { //可以原路退回

          foreach ($purchaseInfo as $k => $v) {
            $cancelSql .= " into charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)";
            $cancelSql .= "values('{$v['purchaseid']}','{$v['cardno']}','{$v['amount']}','2','{$nowdate}','{$nowtime}','{$userid}','{$amount}','{$eorderid}') ";

            $customid = $linkCustomid[$v['cardno']];
            $accSql .= " when '{$customid}' then '{$v['amount']}'";

            $customidInfo[] = $customid;
          }
        } else {
          //全额 无法原路退款
          foreach ($purchaseInfo as $k => $v) {
            $balance = $linkAmount[$linkCustomid[$v['cardno']]];
            $info[] = $this->checkAmountEnough($v, $balance) ? $this->enough($v, $balance) : $this->notenough($v, $balance);
          }
          //要扣款信息封装
          $decute = [];
          foreach ($info as $ik => $iv) {
            if ($iv['refund'] !== false) {
              $customid = $linkCustomid[$iv['cardno']];
              if ($iv['less'] == 0) {
                $decute[] = [
                  'purchaseid' => $iv['purchaseid'], 'customid' => $customid,
                  'reduce' => $iv['refund'], 'cardno' => $iv['cardno']
                ];
                $accoutAvailable[] = ['customid' => $customid, 'amount' => $iv['remain'], 'cardno' => $iv['cardno']];
              } else {
                $decute[] = [
                  'purchaseid' => $iv['purchaseid'], 'customid' => $customid,
                  'reduce' => $iv['refund'], 'cardno' => $iv['cardno']
                ];
                //未退完 需继续退
                $continueDecute[] = ['purchaseid' => $iv['purchaseid'], 'amount' => $iv['less']];
              }
            } else {
              //原路 一毛未退
              $continueDecute[] = ['purchaseid' => $iv['purchaseid'], 'amount' => $iv['less']];
            }
          }

          // 继续封装退款路径
          foreach ($accoutAvailable as $ak => $av) {
            if (!empty($continueDecute)) {
              foreach ($continueDecute as $pk => $pv) {
                if ($av['amount'] > 0) {
                  if ($av['amount'] >= $pv['amount']) {
                    unset($continueDecute[$pk]);
                    $decute[] = [
                      'purchaseid' => $pv['purchaseid'], 'cardno' => $av['cardno'],
                      'customid' => $av['customid'], 'reduce' => $pv['amount']
                    ];
                    $av['amount'] = bcsub($av['amount'], $pv['amount'], 2);
                  } else {
                    $pv['amount'] = bcsub($pv['amount'], $av['amount'], 2);
                    $decute[] = [
                      'purchaseid' => $pv['purchaseid'], 'customid' => $av['customid'],
                      'reduce' => $av['amount'], 'cardno' => $av['cardno']
                    ];
                    $av['amount'] = 0;
                    $continueDecute[$pk] = $pv;
                  }
                } else {
                  break;
                }
              }
            } else {
              break;
            }
          }

          //
          $account = [];
          foreach ($decute as $dk => $dv) {
            $cancelSql .= "into charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)";
            $cancelSql .= " values ('{$dv['purchaseid']}','{$dv['cardno']}','{$dv['reduce']}','2','{$nowdate}','{$nowtime}','{$userid}','{$amount}','{$eorderid}') ";

            if (isset($account[$dv['customid']])) {
              $account[$dv['customid']]['reduce'] = bcadd($account[$dv['customid']]['reduce'], $dv['reduce'], 2);
            } else {
              $account[$dv['customid']] = ['customid' => $dv['customid'], 'reduce' => $dv['reduce']];
            }
          }

          foreach ($account as $asv) {
            $accSql .= " when '{$asv['customid']}' then '{$asv['reduce']}'";
            $customidInfo[] = $asv['customid'];
          }
        }
        $cancelSql = "insert all " . $cancelSql . "select 1 from dual";
        $customidSting = $this->inString($customidInfo);

        $accSql = "update account set amount=amount - CASE customid" . $accSql . " END where customid in ({$customidSting}) AND type='00' ";

        $map['purchaseid'] = ['in', array_column($purchaseInfo, 'purchaseid')];
        $save['flag'] = '3';
        $this->model->startTrans();
        try {
          $cancleIf = M('charge_cancle_logs')->execute($cancelSql);
          $accIf = M('account')->execute($accSql);
          $cupIf = M('custom_purchase_logs')->where($map)->save($save);
          $capIf = M('card_purchase_logs')->where($map)->save($save);
          if ($capIf && $cupIf && $accIf && $cancleIf) {
            $this->model->commit();
            returnMsg(array('status' => '1', 'codemsg' => '退款成功', 'refund' => $amount));
          } else {
            $this->model->rollback();
            returnMsg(array('status' => '10', 'codemsg' => '数据库修改失败'));
          }
        } catch (\Exception $e) {
          $this->model->rollback();
          returnMsg(array('status' => '10', 'codemsg' => $e->getMessage()));
        }
      } else {
        //部分退款
        $amountInfo = array_column($purchaseInfo, 'amount');
        $max = rsort($amountInfo) ? $amountInfo[0] : false;
        if ($max && $max >= $amount) {
          //可以一单

          foreach ($purchaseInfo as $v) {
            if ($v['amount'] == $max) {
              $continueDecute[] = [
                'purchaseid' => $v['purchaseid'], 'cardno' => $v['cardno'],
                'reduce' => $amount, 'customid' => $linkCustomid[$v['cardno']]
              ];
              break;
            }
          }
          //判定是否可以原路退回
          $yuan = false;
          $reduceAmount = $continueDecute[0]['reduce'];
          $purchaseid = $continueDecute[0]['purchaseid'];
          foreach ($customInfo as $cv) {
            if (in_array($continueDecute[0]['customid'], $cv)) {
              if ($continueDecute[0]['reduce'] <= $cv['amount']) {
                $yuan = true;
                break;
              } else {
                $customid = $linkCustomid[$v['cardno']];
                $decute[] = [
                  'purchaseid' => $continueDecute[0]['purchaseid'], 'cardno' => $v['cardno'],
                  'reduce' => $cv['amount'], 'customid' => $customid
                ];
                $continueDecute[0]['reduce'] = bcsub($continueDecute[0]['reduce'], $cv['amount'], 2);
              }
            }
          }
          if ($yuan == true) {
            $cancelSql = "insert into charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)";
            $cancelSql .= " values('{$purchaseid}','{$continueDecute[0]['cardno']}','{$reduceAmount}','2','{$nowdate}','{$nowtime}','{$userid}','{$amount}','{$eorderid}') ";


            $accSql = "update account SET amount=amount-$reduceAmount where customid={$continueDecute[0]['customid']} and type='00'";
          } else {
            foreach ($customInfo as $cuv) {
              if ($continueDecute[0]['reduce'] > 0) {
                if (in_array($continueDecute[0]['customid'], $cuv)) {
                  continue;
                }
                if ($cuv['amount'] >= $continueDecute[0]['reduce']) {
                  $decute[] = [
                    'purchaseid' => $continueDecute[0]['purchaseid'], 'reduce' => $continueDecute[0]['reduce'],
                    'customid' => $cuv['customid'], 'cardno' => $cuv['cardno']
                  ];
                  $continueDecute[0]['reduce'] = 0;
                } else {
                  $decute[] = [
                    'purchaseid' => $continueDecute[0]['purchaseid'], 'reduce' => $cuv['amount'],
                    'customid' => $cuv['customid'], 'cardno' => $cuv['cardno']
                  ];
                  $continueDecute[0]['reduce'] = bcsub($continueDecute[0]['reduce'], $cuv['amount'], 2);
                }
              }
            }
            $accSql = '';
            foreach ($decute as $dv) {
              $cancelSql .= " into charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)";
              $cancelSql .= "values('{$dv['purchaseid']}','{$dv['cardno']}','{$dv['reduce']}','2','{$nowdate}','{$nowtime}','{$userid}','{$amount}','{$eorderid}') ";

              $accSql .= "when '{$dv['customid']}' then '{$dv['reduce']}'";

              $customidInfo[] = $dv['customid'];
            }

            $accSql = "update account set amount = amount - case customid " . $accSql . " END where type='00' and customid in ({$this->inString($customidInfo)})";

            $cancelSql = "insert all " . $cancelSql . " select 1 from dual";
          }

          $cupSql = "update custom_purchase_logs set  amount=amount-$reduceAmount,totalmoney=totalmoney-$reduceAmount,realamount=realamount-$reduceAmount,writeamount=writeamount-$reduceAmount  where purchaseid='{$purchaseid}'";

          $capSql = "update card_purchase_logs set amount=amount - $reduceAmount where  purchaseid='{$purchaseid}'";

          $this->model->startTrans();
          try {
            $accIf = $this->model->execute($accSql);

            $cancleIf = $this->model->execute($cancelSql);

            $cupIf = $this->model->execute($cupSql);
            $capIf = $this->model->execute($capSql);
            if ($capIf && $cupIf && $accIf && $cancleIf) {
              $this->model->commit();
              returnMsg(array('status' => '1', 'codemsg' => '退款成功', 'refund' => $amount));
            } else {
              $this->model->rollback();
              returnMsg(array('status' => '10', 'codemsg' => '数据库修改失败'));
            }
          } catch (\Exception $e) {
            $this->model->rollback();
            returnMsg(array('status' => '10', 'codemsg' => $e->getMessage()));
          }
        } else {
          $refund = $amount;
          //多单退
          foreach ($purchaseInfo as $v) {
            if ($amount > 0) {
              if ($v['amount'] >= $amount) {
                $continueDecute[] = [
                  'purchaseid' => $v['purchaseid'], 'cardno' => $v['cardno'],
                  'reduce' => $amount, 'customid' => $linkCustomid[$v['cardno']]
                ];
                $amount = 0;
                break;
              } else {
                $continueDecute[] = [
                  'purchaseid' => $v['purchaseid'], 'cardno' => $v['cardno'],
                  'reduce' => $v['amount'], 'customid' => $linkCustomid[$v['cardno']]
                ];
                $amount = bcsub($amount, $v['amount'], 2);
              }
            } else {
              break;
            }
          }
          //充值相关退款
          foreach ($continueDecute as $cov) {
            $cupSql .= " when '{$cov['purchaseid']}' then '{$cov['reduce']}'";

            $capSql .= " when '{$cov['purchaseid']}' then '{$cov['reduce']}'";
          }
          foreach ($customInfo as $ak => $av) {
            if (!empty($continueDecute)) {
              foreach ($continueDecute as $pk => $pv) {
                if ($av['amount'] > 0) {
                  if ($av['amount'] >= $pv['reduce']) {
                    unset($continueDecute[$pk]);
                    $decute[] = [
                      'purchaseid' => $pv['purchaseid'], 'cardno' => $av['cardno'],
                      'customid' => $av['customid'], 'reduce' => $pv['reduce']
                    ];
                    $av['amount'] = bcsub($av['amount'], $pv['reduce'], 2);
                  } else {
                    $pv['reduce'] = bcsub($pv['reduce'], $av['amount'], 2);
                    $decute[] = [
                      'purchaseid' => $pv['purchaseid'], 'customid' => $av['customid'],
                      'reduce' => $av['amount'], 'cardno' => $av['cardno']
                    ];
                    $av['amount'] = 0;
                    $continueDecute[$pk] = $pv;
                  }
                } else {
                  break;
                }
              }
            } else {
              break;
            }
          }
          $account = [];
          foreach ($decute as $dv) {
            $cancelSql .= " into charge_cancle_logs(purchaseid,cardno,amount,flag,placeddate,placedtime,userid,description,eorderid)";
            $cancelSql .= "values('{$dv['purchaseid']}','{$dv['cardno']}','{$dv['reduce']}','2','{$nowdate}','{$nowtime}','{$userid}','{$refund}','{$eorderid}') ";

            if (isset($account[$dv['customid']])) {
              $account[$dv['customid']]['reduce'] = bcadd($account[$dv['customid']]['reduce'], $dv['reduce'], 2);
            } else {
              $account[$dv['customid']] = ['customid' => $dv['customid'], 'reduce' => $dv['reduce']];
            }
          }
          foreach ($account as $val) {
            $accSql .= "when '{$val['customid']}' then '{$val['reduce']}'";

            $customidInfo[] = $val['customid'];
          }
          $purchaseidString = $this->inString(array_column($purchaseInfo, 'purchaseid'));
          $capSql = "update card_purchase_logs set amount=amount -  CASE purchaseid " . $capSql . " END where purchaseid in ($purchaseidString)";

          $cupSql = "update custom_purchase_logs set amount=amount -  CASE purchaseid " . $cupSql . " END where purchaseid in ($purchaseidString)";

          $cancelSql = "insert all " . $cancelSql . "select 1 from dual";

          $accSql = "update account set amount = amount - case customid " . "$accSql" . " END where customid in ({$this->inString($customidInfo)}) and type='00'";
          $this->model->startTrans();

          dump($cupSql);
          dump($capSql);
          try {
            $accIf = $this->model->execute($accSql);

            $cancleIf = $this->model->execute($cancelSql);

            $cupIf = $this->model->execute($cupSql);
            $capIf = $this->model->execute($capSql);
            if ($capIf && $cupIf && $accIf && $cancleIf) {
              $this->model->commit();
              returnMsg(array('status' => '1', 'codemsg' => '退款成功', 'refund' => $refund));
            } else {
              $this->model->rollback();
              returnMsg(array('status' => '10', 'codemsg' => '数据库修改失败'));
            }
          } catch (\Exception $e) {
            $this->model->rollback();
            returnMsg(array('status' => '10', 'codemsg' => $e->getMessage()));
          }
        }
      }
    } else {
      returnMsg(array('status' => '09', 'codemsg' => '用户账户信息异常'));
    }
  }

  protected function checkAmountEnough($value, $balance)
  {
    return $balance >= $value['amount'];
  }

  protected function enough($value, $balance)
  {
    return [
      'refund' => $value['amount'], 'less' => 0, 'remain' => bcsub($balance, $value['amount'], 2),
      'purchaseid' => $value['purchaseid'], 'cardno' => $value['cardno']
    ];
  }

  protected function notenough($value, $balance)
  {
    if ($balance != 0) {
      return [
        'refund' => $balance, 'less' => bcsub($value['amount'], $balance, 2), 'remain' => 0,
        'purchaseid' => $value['purchaseid'], 'cardno' => $value['cardno']

      ];
    } else {
      return [
        'refund' => false, 'less' => $value['amount'], 'remain' => 0,
        'purchaseid' => $value['purchaseid'], 'cardno' => $value['cardno']
      ];
    }
  }

  protected function inString($arr)
  {
    $str = '';
    foreach ($arr as $val) {
      $str .= "'" . $val . "',";
    }
    return rtrim($str, ',');
  }

  public function zhuanhuanPwd($customid, $flag)
  {
    $customid = trim(I('get.customid', ''));
    $flag = trim(I('get.flag', ''));
    $customid != '' || die('缺失用户id');
    // $customid = decode($customid);
    if ($flag == 1) {
      $result = decode($customid);
      var_dump($result);
      exit;
    } else echo encode($customid);
  }
  //------------------------2018--07--18------
  //通宝发行 以及消费查询
  public function periodvalidity()
  {
    $customid = trim(I('get.cid', ''));
    $cid = $customid;
    $customid != '' || die('缺失用户id');
    $customid = decode($customid);

    //获取通宝列表
    $model = new Model();
    $subSql = "(select trigger_rules,placedtime, rechargeamount,remindamount,enddate,sourceorder,panterid,placeddate from coin_account where cardid in (SELECT cid FROM customs_c WHERE customid = '{$customid}') ORDER BY enddate ASC)";
    $field = "coin.rechargeamount,coin.remindamount,coin.placeddate,coin.placedtime,coin.enddate,coin.sourceorder,p.namechinese,coin.trigger_rules";
    $list = $model->table($subSql)->alias('coin')
      ->join('left join panters p on p.panterid=coin.panterid')
      ->field($field)->order("coin.placeddate desc")->select();
    $now = date('Ymd');
    $amount = $this->getCoinAmount($customid);
    $account = $this->customs->where("customid = $customid")->find();
    $amounts = $this->getCoinAmountOn2019($customid);
    if ($list) {
      foreach ($list as $key => $val) {
        if ($val['remindamount'] < 1) {
          $val['remindamount'] = bcadd(0, $val['remindamount'], 2);
        }

        if ($val['rechargeamount'] < 1) {
          $val['rechargeamount'] = bcadd(0, $val['rechargeamount'], 2);
        }

        if ($val['enddate'] >= $now) {
          $val['period'] = 0;
        } else {

          $val['period'] = 1; //失效 过期 2是：没有激活 需要激活 快要失效
        }

        $jianGeTime = strtotime($val["placeddate"] . $val["placedtime"]);
        $timeFlag = strtotime($val['enddate']) - time();
        $activateDay = 10 * 24 * 60 * 60;
        if (empty($account['personid']) && substr($val['sourceorder'], 0, 4) != 'fzg_') {
          if ($timeFlag <= $activateDay) {
            $remainTimeStr = "还有";
            // 10天内，间隔，说明还没有激活s
            $days = intval($timeFlag / 86400);
            //计算小时数
            $remain = $timeFlag % 86400;
            $hours = intval($remain / 3600);
            //计算分钟数
            $remain = $remain % 3600;
            $mins = intval($remain / 60);
            if (!empty($days))
              $remainTimeStr .= $days . "天";

            if (!empty($hours))
              $remainTimeStr .= $hours . "小时";

            if (!empty($mins))
              $remainTimeStr .= $mins . "分钟失效";

            $val['period'] = 2; //没有激活 待激活
            if ($days < 0 || $hours < 0 || $mins < 0) {
                $remainTimeStr = "已失效";
              }
          }
        }


        if ($val['enddate'] < $now && $val["remindamount"] == 0) {
          $val['period'] = 1; //已经失效
        }

        $val["jihuo_time"] = $remainTimeStr;

        // 判断是否实名  通过身份证号码，如果有，就实名，没有就没有实名
        // $userFlag=$model->table("customs")->where(array("customid"=>$))

        $list[$key] = $val;

        //查询通宝消费记录
        $coinSql = "(select coinid,panterid from coin_account where cardid in (SELECT cid FROM customs_c WHERE customid = '{$customid}'))";
        $coinArr = array_column($model->query($coinSql), 'coinid');
        $where['cc.coinid'] = ['in', $coinArr];
        $field = "p.namechinese pname,cc.tradeid,tw.termno,cc.placeddate,cc.placedtime,cc.amount,p1.namechinese cname";
        $consume = $model->table('coin_consume')->alias('cc')
          ->join('left join coin_account coin on cc.coinid=coin.coinid')
          ->join('left join trade_wastebooks tw on tw.tradeid=cc.tradeid')
          ->join('left join panters p on p.panterid=coin.panterid')
          ->join('left join panters p1 on p1.panterid=cc.panterid')
          ->where($where)->field($field)->order('cc.placeddate desc,cc.placedtime desc')->select();
        if ($consume) {
          foreach ($consume as $ck => $value) {
            if (rtrim($value['termno']) == '00000000') {
              $value['description'] = '线上一家订单';
            } else {
              $value['description'] = '线下订单';
            }

            if ($value['amount'] < 1) {
              $value['amount'] = bcadd(0, $value['amount'], 2);
            }

            $consume[$ck] = $value;
          }
        }
        $this->assign('consume', $consume);
      }
    }

    // 判断是否实名
    $userFlag = $model->table("customs")->where(array("customid" => $customid))->field("personid")->find();
    if (empty($userFlag["personid"])) {
      $shimingFlag = 0;
    } else {
      $shimingFlag = 1;
    }


    $this->assign('list', $list);
    $this->assign('cid', $cid);
    $this->assign('shiming', $shimingFlag);
    $this->assign('amount', floatval($amount['amount']));
    $this->assign('amounts', floatval($amounts['amount']));
    $this->display('tbjln');
  }

  protected function getCoinAmount($customid)
  {
    $day = date('Ymd');
    $where['cu.customid'] = $customid;
    $where['ca.cardkind'] = '6889';
    $where['ca.cardfee'] = ['in', ['1', '2']];
    $where['ca.status'] = 'Y';
    $where['coin.enddate'] = ['egt', $day];
    $where['coin.remindamount'] = ['gt', '0'];
    $field = 'nvl(sum(coin.remindamount),0) amount';
    $coinList = $this->model->table('customs')->alias('cu')
      ->join('left join customs_c cc on cc.customid=cu.customid')
      ->join('left join cards ca on ca.customid=cc.cid')
      ->join('left join coin_account coin on coin.cardid=ca.customid')
      ->where($where)->field($field)->find();
    // var_dump($this->model->table('customs')->getlastsql());exit;
    return $coinList;
  }

  public function getLimitJy()
  {
    $data = getPostJson();
    $datami = trim($data['datami']);
    $datami = $this->DESedeCoder->decrypt($datami);
    if ($datami == false) {
      returnMsg(array('status' => '07', 'codemsg' => '非法数据传入'));
    }
    $this->recordData($datami);
    $post     = json_decode($datami, 1);
    $customid = $post['customid'];
    $key      = $post['key'];

    $checkKey = md5($this->keycode . $customid);
    if ($key !== $checkKey) {
      returnMsg(array('status' => '02', 'codemsg' => '密钥错误'));
    }
    $customid = decode($customid);
    $day = date('Ymd');
    $where['cu.customid'] = $customid;
    $where['ca.cardkind'] = '6889';
    $where['ca.cardfee'] = ['in', ['1', '2']];
    $where['ca.status'] = 'Y';
    $where['coin.enddate'] = ['egt', $day];
    $where['coin.remindamount'] = ['gt', '0'];
    $where['coin.placeddate'] = ['lt', '20190101'];
    $field = 'nvl(sum(coin.remindamount),0) amount';
    $coinList = $this->model->table('customs')->alias('cu')
      ->join('left join customs_c cc on cc.customid=cu.customid')
      ->join('left join cards ca on ca.customid=cc.cid')
      ->join('left join coin_account coin on coin.cardid=ca.customid')
      ->where($where)->field($field)->find();
    returnMsg(array('status' => '1', 'codemsg' => '查询成功', 'jycoin' => $coinList['amount']));
  }
  protected function getCoinAmountOn2019($customid)
  {
    $day = date('Ymd');
    $where['cu.customid'] = $customid;
    $where['ca.cardkind'] = '6889';
    $where['ca.cardfee'] = ['in', ['1', '2']];
    $where['ca.status'] = 'Y';
    $where['coin.placeddate'] = ['elt', "20190101"];
    $where['coin.enddate'] = ['egt', $day];
    $where['coin.remindamount'] = ['gt', 0];
    $field = 'nvl(sum(coin.remindamount),0) amount';
    $coinList = $this->model->table('customs')->alias('cu')
      ->join('left join customs_c cc on cc.customid=cu.customid')
      ->join('left join cards ca on ca.customid=cc.cid')
      ->join('left join coin_account coin on coin.cardid=ca.customid')
      ->where($where)->field($field)->find();
    // var_dump($this->model->table('customs')->getlastsql());exit;
    return $coinList;
  }
}

 