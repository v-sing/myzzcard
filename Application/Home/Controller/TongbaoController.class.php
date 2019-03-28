<?php

namespace Home\Controller;

use Home\Model\WhiteListModel;
use Think\Controller;
use think\Db;
use Think\Model;
use Org\Util\DESedeCoder;
use Org\Util\YjDes;
use Home\Controller\CoinController;
use Home\Controller\TbrecordGoodsController;
use Home\Controller\CommonController;

class TongbaoController extends CoinController
{
    protected $unActivateDay = 10;//用户没有激活(实名) 十天有效期
    protected $ActivateDay = 730;//用户激活(实名)之后 两年有效期
    protected $model;
    protected $customs;
    protected $cards;
    protected $account;
    protected $panters;
    protected $tb_pool;
    protected $tb_pool_details;
    protected $coin_account;
    protected $coin_consume;
    protected $customs_c;
    protected $keycode = "JYO2O01";//密钥
    protected $payPWdKeyCode = 'JYfghj6789436';//免密秘钥
    protected $objPHPExcel;

    public function _initialize()
    {
        date_default_timezone_set("Asia/Shanghai");
        $this->account         = M('account');
        $this->customs         = M('customs');
        $this->panters         = M("panters");
        $this->account         = M("account");
        $this->cards           = M('cards');
        $this->model           = new model();
        $this->tb_pool         = M("tb_pool");
        $this->tb_pool_details = M("tb_pool_details");
        $this->coin_account    = M("coin_account");
        $this->coin_consume    = M("coin_consume");
        $this->customs_c       = M("customs_c");
        require("./ThinkPHP/Library/Org/Util/PHPExcel.php");
        $this->objPHPExcel = new \PHPExcel();

    }

    public function zhuanhuanPwd($customid, $flag)
    {

        if ($flag == 1) {
            echo decode($customid);

        } else echo encode($customid);
    }

    public function test()
    {

        // $result="哈哈";

        // $coin=new CoinController;
        $flag = $this->getFieldNextNumber('coinconsumeid');
        var_dump($flag);
// echo $this->isRepeat("123");
        // echo "你好";
        // echo date("Ymd",time()+3600*24*$this->unActivateDay);
        // $tbPoolData=$this->tb_pool->max("tb_stock");

        // $tbPoolId=$this->tb_pool->where(array('tb_stock'=>$tbPoolData))->order("time asc")->find();
        // $sql="select max(tb_stock)from tb_pool order by time desc";

        // $memberData['issue_company']=trim($datami['issue_company']);
        //
        // $data= $this->createConsumeOrder(array("time"=>123,"name"=>"哈哈"));

        // var_dump($data);
        // echo encode("00100000");
// $coin=new coinController();
//  $coin->recordError(date("Ymd H:i:s")."脚本测试\n","jiaoben","errorlog");
        // returnMsg(array('status'=>'04','codemsg'=>$tbPoolId));


    }


    public function _upload($moudle)
    {
        switch ($moudle) {
            case 'editor':
                $path = 'upfile/editor/';
                break;
            case 'excel':
                $path = 'upfile/excel/';
                break;
            case 'custom':
                $path = 'upfile/custom/';
                break;
            case 'panter':
                $path = 'upfile/panter/';
                break;
            default:
                $path = 'upimg/';
        }
        $upload           = new \Think\Upload();
        $upload->maxSize  = C('ATTACHSIZE');
        $upload->exts     = array('jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx');
        $upload->rootPath = PUBLIC_PATH;
        $upload->savePath = $path;
        $upload->savename = array('uniqid', time());
        $upload->autoSub  = false;
        $upload->subName  = array('date', 'Ymd');
        $info             = $upload->upload();
        if (!$info) {
            return $upload->getError();
        } else {
            return $info;
        }
    }

    /**
     *用户实名接口 (实名是在赠送通宝之后触发的规则)  如果存在身份证号说明已经实名 如果不存在 那就进行实名认证 存放信息
     */
    public function tbRealName()
    {

        $datami = json_decode(file_get_contents('php://input'), true);
        $coin   = new CoinController;
        $coin->recordData("实名接口:" . implode("#", $datami));
        $des                  = new DESedeCoder();
        $datami               = json_decode($des->decrypt($datami["datami"]), 1);
        $data['namechinese']  = urldecode(trim($datami['name']));
        $data['personid']     = trim($datami['card_id']);
        $data['linktel']      = trim($datami['phone']);
        $data['residaddress'] = urldecode(trim($datami['address']));
        $frontImg             = urldecode(trim($datami['front_img']));
        $reserveImg           = urldecode(trim($datami["reserve_img"]));
        $key                  = trim($datami['key']);
        $checkKey             = md5($this->keycode . $datami['name'] . $data['personid'] . $data['linktel'] . $datami['front_img'] . $datami["reserve_img"] . $datami['address']);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入！'));
        }

        $sl = preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $data['namechinese']);
        if (!$sl) {
            returnMsg(array('status' => '03', 'codemsg' => "会员名必须为中文！"));
        }

        $d = preg_match("/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/", $data['linktel']);
        if (!$d) {
            returnMsg(array('status' => '04', 'codemsg' => "手机号码格式错误！"));

        }
        $ws = preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i", $data['personid']);
        if (!$ws) {
            returnMsg(array('status' => '05', 'codemsg' => "身份证号码格式错误！"));
        }
        $image1 = preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $frontImg);

        if (!$image1) {
            returnMsg(array('status' => '05', 'codemsg' => "非法数据,没有上传正面身份信息！"));

        }

        $image2 = preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $reserveImg);

        if (!$image2) {
            returnMsg(array('status' => '06', 'codemsg' => "非法数据,没有上传反面身份信息！"));

        }

        // 先判断用户这个身份证号是否已经存在 存在说明已经实名 不存在 说明还没有实名，可以继续往下执行

        $userData = $this->customs->where(array("linktel" => $data['linktel'], "namechinese" => $data["namechinese"], "personid" => $data["personid"]))->field("customid")->find();

        $uploadFrontImage = $this->crabImage($frontImg);
        $data["frontimg"] = $uploadFrontImage["save_path"];

        $uploadReseveImage  = $this->crabImage($reserveImg);
        $data["reserveimg"] = $uploadReseveImage["save_path"];
        //用户已经实名存在
        if (!empty($userData['customid'])) {

            $this->customs->where(array("linktel" => $data['linktel']))->save($data);

            returnMsg(array('status' => '01', 'codemsg' => "用户已经存在，实名过了！"));
        }


        $data["rate"]    = 0;//实名状态更新
        $data["linkman"] = 1;

        //更新用户实名信息

        $updateFlag = $this->customs->where(array("linktel" => $data['linktel']))->save($data);

        $updateCustomid = $this->customs->where(array("linktel" => $data['linktel']))->field("customid")->find();

        $coin->recordData("updataFlag:" . $updateFlag . "\n");
        if ($updateFlag) {

            //更新用户赠送的通宝 失效时间
            $cardIdRow = $this->customs->alias("c")->join("customs_c cc on cc.customid=c.customid")->join("account a on a.customid=cc.cid")->join("cards ca on ca.customid=cc.cid")->where(array("c.customid" => $updateCustomid['customid'], "a.type" => "01", "ca.status" => "Y", "ca.cardkind" => "6889", "ca.cardfee" => 2))->field("cc.cid,a.accountid")->select();
            $coin->recordData("updataFlag:" . implode("#", $cardIdRow) . "\n");
            if ($cardIdRow == false) {
                returnMsg(array('status' => '07', 'codemsg' => "用户没有实名！", "data" => $this->customs));
            }

            $customCid = array_column($cardIdRow, "cid");
            $coin->recordData("updataFlag:" . implode("#", $customCid) . "\n");
            $tbPoolData = $this->coin_account->where(array("cardid" => array("in", $customCid), "tb_version" => "2"))->field('accountid,enddate,placedtime,placeddate,coinid')->select();
            $coin->recordData("updataFlag:" . implode("#", $tbPoolData) . "\n");
            foreach ($tbPoolData as $key => $value) {

                $upDateTime          = strtotime($value["placeddate"] . $value["placedtime"]);
                $upTime              = $upDateTime + 3600 * 24 * $this->ActivateDay;
                $coinData['enddate'] = date("Ymd", $upTime);

                $temp = $this->coin_account->where(array("coinid" => $value['coinid']))->save($coinData);
                $coin->recordData("updataFlag:" . $value["coinid"] . "enddate" . $coinDatap["enddate"] . "temp:" . $temp . "\n");
            }

            returnMsg(array('status' => '07', 'codemsg' => "用户没有实名！"));

        } else {

            returnMsg(array('status' => '08', 'codemsg' => "匹配不到此人信息！"));
        }


    }

    /**
     * 主要实时统计各个发行商户的发行总量
     * author:sql  time:2018年8月18日10:06:30
     */
    public function tbPool()
    {
        $startTime    = I('get.startdate', '');
        $endTime      = I('get.enddate', '');
        $issueCompany = trim(I('get.pname', ''));
        $issueItem    = trim(I('get.uname', ''));

        $issueCompany = $issueCompany == "商户名称" ? "" : $issueCompany;
        $issueItem    = $issueItem == "发行项目" ? "" : $issueItem;


        if ($startTime != '' && $endTime == '') {
            $startdate = str_replace('-', '', $startTime);;
            $where['cellin_time'] = array('egt', strtotime($startdate));
            $this->assign('startdate', $startdate);
            $map['startdate'] = $start;
        }
        if ($startTime == '' && $endTime != '') {
            $enddate              = str_replace('-', '', $endTime);
            $where['cellin_time'] = array('elt', strtotime($enddate));
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($startTime != '' && $endTime != '') {
            $startdate            = str_replace('-', '', $startTime);
            $enddate              = str_replace('-', '', $endTime);
            $where['cellin_time'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }

        if ($issueCompany != '') {
            $where['issue_company'] = array('like', '%' . $issueCompany . '%');
            $this->assign('pname', $issueCompany);
            $map['issue_company'] = $issueCompany;
        }
        if ($issueItem != '') {
            $where['issue_item'] = array('like', '%' . $issueItem . '%');
            $this->assign('uname', $issueItem);
            $map['issue_item'] = $issueItem;
        }

        $_SESSION['tbPoolWhere'] = $where;
        $tbpool_total            = $this->tb_pool->sum("tb_stock");
        $count                   = $this->tb_pool->where($where)->count();
        $this->assign('count', $count);
        $p          = new \Think\Page($count, 10);
        $tbPoolList = $this->tb_pool->where($where)->limit($p->firstRow . ',' . $p->listRows)
            ->order('cellin_time desc')->select();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign('tbpool_total', $tbpool_total);
        $this->assign('list', $tbPoolList);
        $this->assign('page', $page);
        $this->display("Tongbao/tbPool");
    }

    public function tbpool_excel()
    {

        if (isset($_SESSION['tbPoolWhere'])) {
            $tbPoolWhere = session('tbPoolWhere');
            foreach ($tbPoolWhere as $key => $val) {
                $where[$key] = $val;
            }
        }

        $tbPoolMessage = M("tb_pool");

        $list = $this->tb_pool->where($where)->field("id,issue_company,issue_item,tb_stock,time,cellin_time")->select();

        $strList = "编号,发行单位,发行项目,存量,更新时间,入池时间";
        $strlist = iconv("utf-8", "gbk", $strList);
        $strlist .= "\n";

        foreach ($list as $key => $val) {
            $val['issue_company'] = iconv("utf-8", "gbk", $val['issue_company']);
            $val['issue_item']    = iconv("utf-8", "gbk", $val['issue_item']);
            $strlist              .= $val["id"] . "," . $val["issue_company"] . "," . $val["issue_item"] . "," . $val["tb_stock"] . "," . date("Ymd H:i:s", $val["time"]) . "\t," . date("Ymd H:i:s", $val["cellin_time"]) . "\t\n";
        }

        $filename = '建业通宝2.0发行报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);


    }

    private function crabImage($imgUrl, $saveDir = './', $fileName = null)
    {
        if (empty($imgUrl)) {
            return false;
        }

        $saveDir = "./IMAGES" . "/" . date("Ymd") . "/";

        //获取图片信息大小
        $imgSize = getimagesize($imgUrl);


        if (!in_array($imgSize['mime'], array('image/jpg', 'image/gif', 'image/png', 'image/jpeg'), true)) {
            return false;
        }

        //获取后缀名
        $_mime = explode('/', $imgSize['mime']);
        $_ext  = '.' . end($_mime);

        if (empty($fileName)) {  //生成唯一的文件名
            $fileName = uniqid(time(), true) . $_ext;
        }
        //开始攫取
        ob_start();
        readfile($imgUrl);
        $imgInfo = ob_get_contents();
        ob_end_clean();
        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0777, true);
        }
        $fp     = fopen($saveDir . $fileName, 'a');
        $imgLen = strlen($imgInfo);    //计算图片源码大小
        $_inx   = 1024;   //每次写入1k
        $_time  = ceil($imgLen / $_inx);
        for ($i = 0; $i < $_time; $i++) {
            fwrite($fp, substr($imgInfo, $i * $_inx, $_inx));
        }
        fclose($fp);
        return array('file_name' => $fileName, 'save_path' => ltrim($saveDir . $fileName, "."));
    }

    /**
     *  请求会员编号，返回用户 身份证照片正面、身份证照片反面、姓名、性别、民族、出生年月、住址、身份证号码,身份证有效期
     *
     * @access    public
     * @param     string $member_id 加密的 记录类型
     * @return    json
     */
    public function getUserMessage()
    {

        $datami = json_decode(file_get_contents('php://input'), true);

        $des    = new DESedeCoder();
        $datami = json_decode($des->decrypt($datami["datami"]), 1);
        $this->recordData($datami);
        $member_id = trim($datami["member_id"]);

        $key = trim($datami["key"]);

        $checkKey = md5($this->keycode . $member_id);

        if ($checkKey != $key) {

            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入!'));
        }

        $member_id = decode(trim($member_id));
        if (empty($member_id))
            returnMsg(array('status' => '03', 'codemsg' => '请求参数格式错误！'));

        $userData = $this->customs->where(array("customid" => $member_id))->field("namechinese,sex,birthday,personidexdate,residaddress,personid,frontimg,reserveimg,linktel")->find();
        if (!$userData["linktel"]) {

            returnMsg(array('status' => '04', 'codemsg' => '用户不存在!~'));
        }
        returnMsg(array('status' => '01', 'codemsg' => '查询成功~', "data" => $userData));


    }


    /**
     * 说明:通宝池子“入池”每一笔明细，信息来源 房掌柜
     * author:sql  time:2018年8月18日10:25:55
     */
    public function tbPoolDetail()
    {

        $startTime    = I('get.startdate', '');
        $endTime      = I('get.enddate', '');
        $issueCompany = trim(I('get.pname', ''));
        $issueItem    = trim(I('get.uname', ''));

        $issueCompany = $issueCompany == "商户名称" ? "" : $issueCompany;
        $issueItem    = $issueItem == "发行项目" ? "" : $issueItem;
        $searchStatus = trim(I('get.state'));

        if ($startTime != '' && $endTime == '') {
            $startdate = str_replace('-', '', $startTime);;
            $where['cellin_time'] = array('egt', strtotime($startdate));
            $this->assign('startdate', $startdate);
            $map['startdate'] = $start;
        }
        if ($startTime == '' && $endTime != '') {
            $enddate              = str_replace('-', '', $endTime);
            $where['cellin_time'] = array('elt', strtotime($enddate));
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($startTime != '' && $endTime != '') {
            $startdate            = str_replace('-', '', $startTime);
            $enddate              = str_replace('-', '', $endTime);
            $where['cellin_time'] = array(array('egt', strtotime($startdate)), array('elt', strtotime($enddate)));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }

        if ($issueCompany != '') {
            $where['issue_company'] = array('like', '%' . $issueCompany . '%');
            $this->assign('pname', $issueCompany);
            $map['issue_company'] = $issueCompany;
        }
        if ($issueItem != '') {
            $where['issue_item'] = array('like', '%' . $issueItem . '%');
            $this->assign('uname', $issueItem);
            $map['issue_item'] = $issueItem;
        }
        if ($searchStatus != "") {
            if ($searchStatus == 2)
                $where['state'] = array('eq', '0');
            if ($searchStatus == 1)
                $where['state'] = array('eq', '1');
            $this->assign('state', $searchStatus);
            $map['state'] = $searchStatus;
        }
        $_SESSION['tbPoolDetailWhere'] = $where;
        $count                         = $this->tb_pool_details->where($where)->count();
        $this->assign('count', $count);
        $p          = new \Think\Page($count, 10);
        $tbPoolList = $this->tb_pool_details->where($where)->limit($p->firstRow . ',' . $p->listRows)
            ->order('cellin_time desc')->select();
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        foreach ($tbPoolList as $key => $value) {
            $tbPoolList[$key]["tb_nums"] = sprintf("%.2f", floatval($value["tb_nums"]));
        }
        $searchAmount = $this->tb_pool_details->where($where)->field("sum(tb_nums) amount")->find();
        $this->assign("searchAmount", $searchAmount['amount']);
        $page = $p->show();
        $this->assign('list', $tbPoolList);
        $this->assign('page', $page);
        $this->display();


    }

    public function tbPoolDetail_excel()
    {

        if (isset($_SESSION['tbPoolDetailWhere'])) {
            $tbPoolDetailWhere = session('tbPoolDetailWhere');
            foreach ($tbPoolDetailWhere as $key => $val) {
                $where[$key] = $val;
            }
        }


        $list = $this->tb_pool_details->where($where)->select();

        $strList = "订单编号,商户名称,商户简称,入池量,合同编号,状态,时间";
        $strlist = iconv("utf-8", "gbk", $strList);
        $strlist .= "\n";

        foreach ($list as $key => $val) {
            $val['issue_company'] = iconv("utf-8", "gbk", $val['issue_company']);
            $val['issue_item']    = iconv("utf-8", "gbk", $val['issue_item']);
            $val['contract_num']  = iconv("utf-8", "gbk", $val['contract_num']);
            $val["state"]         = $val["state"] == 1 ? iconv("utf-8", "gbk", '用户过期') : iconv("utf-8", "gbk", '正常');
            // $cardfee=$val['cardfee']==2?iconv("utf-8","gbk",'电子卡'):iconv("utf-8","gbk",'实体卡');
            $strlist .= $val["tb_number"] . "," . $val["issue_company"] . "," . $val["issue_item"] . "," . $val["tb_nums"] . "," . $val["contract_num"] . "," . $val["state"] . "," . date("Ymd H:i:s", $val["cellin_time"]) . "\n";
        }

        $filename = '建业通宝2.0入池明细' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);

    }


    /**
     * 获取通宝入池每一单明细 数据来源by房掌柜，请求数据
     * author:sql  time:2018年8月18日11:39:27
     * parameter contract_num 合同编号；
     * parameter issue_company 发行商户；
     * parameter issue_item 发行项目；
     * parameter tb_nums 发行存量
     * datami={'contract_num':'20180818154934367723','issue_company':"郑州公司",'issue_item':"建业半岛",'tb_nums':'230'}
     */
    public function getTbMessage()
    {

        $datami = trim($_POST['datami']);

        $this->recordData($datami);
        $datami                = json_decode($datami, 1);
        $data['contract_num']  = trim($datami['contract_num']);
        $data['issue_item']    = trim($datami['issue_item']);
        $data['issue_company'] = trim($datami['issue_company']);
        $data['tb_nums']       = trim($datami['tb_nums']);
        $key                   = trim($datami['key']);

        $checkKey = md5($data['issue_company'] . $data['issue_item'] . $data['contract_num'] . $data['tb_nums']);

        if ($checkKey != $key) {
            returnMsg(array('status' => '04', 'codemsg' => '无效秘钥，非法传入'));
        }


        if (empty($data['contract_num']) || empty($data['issue_item']) || empty($data['issue_company']) || empty($data['tb_nums'])) {
            returnMsg(array('status' => '02', 'codemsg' => '非法数据！', 'data' => $data));
        }


        // 获取发行商户id
        $issuePanterid = $this->panters->where(array("namechinese" => $data["issue_company"]))->field("panterid")->find();

        if (empty($issuePanterid["panterid"])) {
            returnMsg(array('status' => '06', 'codemsg' => '发行商户不存在！'));
        }

        // 判断是否重复提交
        $flag = $this->tb_pool_details->where(array("contract_num" => $data["contract_num"]))->field("id")->find();

        if (!empty($flag))

            returnMsg(array('status' => '05', 'codemsg' => '重复订单信息！'));

        $data['tb_number'] = create_order_num();

        $data['state'] = "0";

        $data['cellin_time'] = time();

        $id = uniqid();


        $sql = "INSERT INTO tb_pool_details(id,issue_company,issue_item,tb_nums,contract_num,tb_number,state,cellin_time)VALUES";

        $sql .= "('" . $id . "','" . $data["issue_company"] . "','" . $data["issue_item"] . "','" . $data["tb_nums"] . "','" . $data["contract_num"] . "','" . $data["tb_number"] . "','" . $data["state"] . "','" . $data["cellin_time"] . "')";


        $resultFlag = $this->model->execute($sql);

        //更新存量总量信息

        $poolFlag = $this->tb_pool->where(array('issue_company' => $data['issue_company'], 'issue_item' => $data['issue_item']))->field('id,tb_stock')->find();

        if (empty($poolFlag['id'])) {
            //空 增加tb_pool
            $tbPoolData['issue_company'] = $data['issue_company'];
            $tbPoolData['issue_item']    = $data['issue_item'];
            $tbPoolData['tb_stock']      = $data['tb_nums'];
            $tbPoolData['cellin_time']   = time();
            $tbPoolData['time']          = time();
            $id                          = uniqid();

            $sql = "INSERT INTO tb_pool(id,issue_company,issue_item,tb_stock,time,cellin_time)VALUES";

            $sql .= "('" . $id . "','" . $tbPoolData["issue_company"] . "','" . $tbPoolData["issue_item"] . "','" . $tbPoolData["tb_stock"] . "','" . $tbPoolData["time"] . "','" . $tbPoolData["cellin_time"] . "')";

            $temp = $this->model->execute($sql);

        } else {
            //不空 更新tb_pool

            $tbPoolData['time']     = time();
            $tbPoolData['tb_stock'] = floatval($data['tb_nums']) + floatval($poolFlag['tb_stock']);
            $where1['id']           = $poolFlag['id'];
            $temp                   = $this->tb_pool->where($where1)->save($tbPoolData);

        }

        if ($resultFlag) {

            returnMsg(array('status' => '01', 'codemsg' => "记录成功！"));

        } else {
            returnMsg(array('status' => '03', 'codemsg' => "记录失败！"));
        }

    }


    public function tbWithdrawDetails()
    {


        $datami = json_decode(file_get_contents('php://input'), true);

        $des    = new DESedeCoder();
        $datami = json_decode($des->decrypt($datami["datami"]), 1);
        $this->recordData("赠送通宝请求数据:" . implode("#", $datami));

        // 获取外面信息
        $coin                    = new coinController();
        $memberData['member_id'] = trim($datami['member_id']);

        $customid = $memberData["member_id"];

        $data['external_order'] = trim($datami['source_order']);// 是外部的订单编号 注意
        $data["sourceorder"]    = trim($datami['source_order']);//老的是房掌柜的合同编号，新的是就是外部的触发合同编号
        $data["cardpurchaseid"] = $coin->getFieldNextNumber('cardpurchaseid');
        $data['pantercheck']    = "1";
        $data['checkdate']      = time();
        $data['trigger_rules']  = urldecode(trim($datami['trigger_rules']));//触发规则
        $data['rechargeamount'] = trim($datami['recharge_amount']);
        $data['tb_version']     = "2";
        $triggerPanters         = trim($datami["source_code"]);

        $triggerPanterid  = $coin->panterArr[$triggerPanters]['panterid'];
        $triggerIssueName = $coin->panterArr[$triggerPanters]['pname'];
        // 获取触发商户id

        if (empty($triggerPanterid)) {
            returnMsg(array('status' => '07', 'codemsg' => '触发商户不存在！'));
        }

        $key = trim($datami["key"]);

        $checkKey = md5($this->keycode . $customid . $data['external_order'] . $triggerPanters . $data['trigger_rules'] . $data['rechargeamount']);

        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }


        // 检测商户订单是否重复
        $shopOrderRow = $this->coin_account->where(array("sourceorder" => $data["external_order"]))->field("accountid")->find();

        if (!empty($shopOrderRow["accountid"]))
            returnMsg(array('status' => '04', 'codemsg' => '用户订单已经存在,请勿重复提交！'));

        //获取最新的卡id
        $customid = decode(trim($customid));

        $userDataSql = $this->customs->where(array("customid" => $customid))->find();

        if ($userDataSql == false) {
            returnMsg(array('status' => "010", 'codemsg' => '用户不存在！'));
        }

        $map   = array('cu.customid' => $customid, 'c.cardkind' => '6889', 'c.cardfee' => 2);
        $ecard = $this->customs->alias('cu')->join('customs_c cc on cc.customid=cu.customid')
            ->join('cards c on c.customid=cc.cid')->where($map)->find();

        if ($ecard == false) {
            // 如果不存在，就开卡
            $map1  = array('cardkind' => '6889', 'cardfee' => 2, 'status' => 'N');
            $cards = $this->cards->where($map1)->field('cardno')->select();
            $c     = $this->cards->where($map1)->count();
            if ($c == 0) {
                returnMsg(array('status' => '011', 'codemsg' => '卡池数量不足'));
            }
            $rand   = mt_rand(0, $c - 1);
            $cardno = $cards[$rand]['cardno'];

            $sql = "update cards set status='D' where cardno='{$cardno}'";
            $this->model->execute($sql);
            $this->model->startTrans();
            $openCardArr = array($cardno);
            $this->recordData(json_encode(array('customid' => $customid, 'cardno' => $cardno)), 1);
            $bool = $coin->openCard($openCardArr, $customid, 0, '', 1, '', '', 1);
            if ($bool == false) {
                $this->model->rollback();
                returnMsg(array('status' => '012', 'codemsg' => '新卡开卡失败'));
            }
            $ecardTime = date('Y-m-d H:i:s');
            $sql       = "insert into ecard_bind values ('{$cardno}','{$customid}',1,'{$triggerPanterid}','{$ecardTime}','{$customid}')";
            $this->model->execute($sql);
            $this->model->commit();
        }
        $cardIdRow = $this->customs->alias("c")->join("customs_c cc on cc.customid=c.customid")->join("account a on a.customid=cc.cid")->join("cards ca on ca.customid=cc.cid")->where(array("c.customid" => $customid, "a.type" => "01", "ca.status" => "Y", "ca.cardkind" => "6889", "ca.cardfee" => 2))->field("cc.cid,a.accountid,c.personid,a.amount")->find();
        if (empty($cardIdRow["cid"]) || empty($cardIdRow["accountid"])) {

            returnMsg(array('status' => '06', 'codemsg' => '用户卡信息不存在或者卡已经被锁定！'));
        }


        // 检测重复订单信息
        $tradeLogs       = M('trade_logs');
        $where           = array('customid' => $customid, 'placeddate' => date('Ymd'), "type" => "3", "orderid" => $data['external_order']);
        $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
        $date            = time();
        if ($trade_logs_list != false) {
            if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
                returnMsg(array('status' => '017', 'codemsg' => '重复支付请求'));
            }
        }
        $currentDate = date('Ymd');
        $currentTime = date('H:i:s');
        $orderType   = '3';
        $orderid     = $data['external_order'];
        $sql         = "insert into trade_logs values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}','{$orderType}')";
        $tradeLogs->execute($sql);
        unset($sql);

        //判断用户是否实名
        if (empty($cardIdRow['personid']))
            $shimingFlag = 0;
        else $shimingFlag = 1;

        //2、发行商户 扣除金额以及获取商户id
        //扣除发行商户的金额 计算发行商户的金额大小 最大量以及入池时间最早的
        $tbStockSum = $this->tb_pool->sum("tb_stock");
        // 通宝预警
        $tbEmailDate = date('Ymd', time());
        if ($tbStockSum <= 1000) {

            $sendFlag = bcdiv($tbStockSum, 100, 0);
            if ($sendFlag == 10 || $sendFlag == 2 || $tbStockSum < 50) {

                $this->think_send_mail('511416304@qq.com', $tbEmailDate, "通宝池消耗预警提示", '<h1>现在还有' . $tbStockSum . '个通宝,马上不足，请尽快补仓！！！</h1>', '');
            }

        }
        // 结束
        if ($data["rechargeamount"] > $tbStockSum || $tbStockSum <= 0) {

            returnMsg(array('status' => '09', 'codemsg' => '通宝池不足,赠送失败！'));
        }


        $remainMoeny = $data["rechargeamount"];
        $i           = 0;

        while (!empty($remainMoeny)) {

            $tbPoolData = $this->tb_pool->max("tb_stock");

            $tbPoolId = $this->tb_pool->where(array('tb_stock' => $tbPoolData))->order("cellin_time asc")->find();

            if ($tbPoolId["tb_stock"] >= $remainMoeny) {
                $tbPoolWhere['tb_stock'] = bcsub($tbPoolId['tb_stock'], $remainMoeny, 2);
                $this->recordData("customid:" . $customid . "-order:" . $data['external_order'] . "-deduce:" . $remainMoeny . "-from:" . $tbPoolId['issue_company']);
                $data["issue_item"] = trim($tbPoolId["issue_item"]);

                // 获取发行商户id
                $issuePanterid = $this->panters->where(array("namechinese" => $tbPoolId['issue_company']))->field("panterid")->find();

                if (empty($issuePanterid["panterid"])) {
                    returnMsg(array('status' => '08', 'codemsg' => '发行商户不存在！'));
                }

                $data['panterid']    = $issuePanterid['panterid'];
                $tbPoolWhere["time"] = time();//更新扣除时间
                // 扣除发行商户信息
                $this->tb_pool->where(array('id' => $tbPoolId['id']))->save($tbPoolWhere);

// 插入信息 注意L有两个编号没有写入
                $data['placeddate']   = date("Ymd", time());
                $data['placedtime']   = date("H:i:s", time());
                $data['status']       = 1;
                $data["remindamount"] = sprintf("%.2f", floatval($remainMoeny));

                if ($shimingFlag == 1) {
                    $shimingTime     = ($this->ActivateDay) * 3600 * 24 + time();
                    $data['enddate'] = date("Ymd", $shimingTime);
                } else {
                    $shimingTime     = ($this->unActivateDay) * 3600 * 24 + time();
                    $data['enddate'] = date("Ymd", $shimingTime);
                }

                $sql = "INSERT INTO coin_account(accountid,rechargeamount,remindamount,placeddate,placedtime,status,panterid,pantercheck,checkdate,issue_item,trigger_rules,trigger_panterid,recipient_item,tb_version,enddate,cardid,sourceorder,coinid,cardpurchaseid)VALUES";
                $sql .= "('" . trim($cardIdRow["accountid"]) . "','" . $remainMoeny . "','" . $data["remindamount"] . "','" . $data["placeddate"] . "','" . $data["placedtime"] . "','" . $data['status'] . "','" . $data['panterid'] . "','" . "1" . "','" . time() . "','" . $data['issue_item'] . "','" . $data['trigger_rules'] . "','" . $triggerPanterid . "','" . $triggerIssueName . "','" . $data['tb_version'] . "','" . $data['enddate'] . "','" . $cardIdRow["cid"] . "','" . $data["sourceorder"] . "','" . $this->getFieldNextNumber('coinid') . "','" . $this->getFieldNextNumber('cardpurchaseid') . "')";

                $temp = $this->model->execute($sql);

                if (!$temp) {

                    returnMsg(array('status' => '011', 'codemsg' => '失败'));
                    break;

                }
                $sql         = '';
                $remainMoeny = 0;


            } else {

                $remainMoeny = bcsub($remainMoeny, $tbPoolId["tb_stock"], 2);
                $this->recordData("customid:" . $customid . "-order:" . $data['external_order'] . "-deduce:" . $tbPoolId["tb_stock"] . "-from:" . $tbPoolId['issue_company']);
                $tbPoolWhere["tb_stock"] = 0;
                $data["issue_item"]      = trim($tbPoolId["issue_item"]);

                // 获取发行商户id
                $issuePanterid = $this->panters->where(array("namechinese" => $tbPoolId['issue_company']))->field("panterid")->find();

                if (empty($issuePanterid["panterid"])) {
                    returnMsg(array('status' => '08', 'codemsg' => '发行商户不存在！'));
                }

                $data['panterid'] = $issuePanterid['panterid'];
                // 扣除发行商户信息
                $this->tb_pool->where(array('id' => $tbPoolId['id']))->save($tbPoolWhere);

// 插入信息 注意L有两个编号没有写入
                $data['placeddate']   = date("Ymd", time());
                $data['placedtime']   = date("H:i:s", time());
                $data['status']       = 1;
                $data["remindamount"] = sprintf("%.2f", floatval($tbPoolId["tb_stock"]));
                if ($shimingFlag == 1) {
                    $shimingTime     = ($this->ActivateDay) * 3600 * 24 + time();
                    $data['enddate'] = date("Ymd", $shimingTime);
                } else {
                    $shimingTime     = ($this->unActivateDay) * 3600 * 24 + time();
                    $data['enddate'] = date("Ymd", $shimingTime);
                }


                $sql = "INSERT INTO coin_account(accountid,rechargeamount,remindamount,placeddate,placedtime,status,panterid,pantercheck,checkdate,issue_item,trigger_rules,trigger_panterid,recipient_item,tb_version,enddate,cardid,sourceorder,coinid,cardpurchaseid)VALUES";
                $sql .= "('" . trim($cardIdRow["accountid"]) . "','" . $tbPoolId["tb_stock"] . "','" . $data["remindamount"] . "','" . $data["placeddate"] . "','" . $data["placedtime"] . "','" . $data['status'] . "','" . $data['panterid'] . "','" . "1" . "','" . time() . "','" . $data['issue_item'] . "','" . $data['trigger_rules'] . "','" . $triggerPanterid . "','" . $triggerIssueName . "','" . $data['tb_version'] . "','" . $data['enddate'] . "','" . $cardIdRow["cid"] . "','" . $data["sourceorder"] . "','" . $this->getFieldNextNumber('coinid') . "','" . $this->getFieldNextNumber('cardpurchaseid') . "')";

                $temp = $this->model->execute($sql);
                if (!$temp) {

                    returnMsg(array('status' => '01', 'codemsg' => '失败', "sql" => $sql));
                    break;

                }

                $sql = '';


            }


        }


        //结束

        // 记录赠送的通宝过期时间值，判断用户是否已经实名，实名通过身份证号是否存在

        $ws = preg_match("/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[12])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/i", $cardIdRow["personid"]);

        if ($ws) {//实名
            $data['enddate'] = date("Ymd", time() + 3600 * 24 * $this->ActivateDay);
        } else {
            $data['enddate'] = date("Ymd", time() + 3600 * 24 * $this->unActivateDay);
        }

        $updateUserAmount = bcadd($cardIdRow["amount"], $data["rechargeamount"], 2);


        // 数据操作 更新用户余额
        $temp = $this->account->where(array("customid" => $cardIdRow["cid"], "type" => "01"))->save(array("amount" => $updateUserAmount));


        if ($temp) {

            returnMsg(array('status' => '01', 'codemsg' => '出金SUCCESS！'));

        } else {
            returnMsg(array('status' => '05', 'codemsg' => '出金失败！'));

        }

    }


    public function issue()
    {
        $start            = I('get.startdate', '');
        $end              = I('get.enddate', '');
        $customid         = trim(I('get.customid', ''));
        $cuname           = trim(I('get.cuname', ''));//用户名称
        $cardno           = trim(I('get.cardno', ''));//卡号
        $pname            = trim(I('get.pname', ''));//商户名称
        $telphone         = trim(I('get.telphone', ''));//商户手机号码
        $triggerRules     = trim(I('get.triggerRules', ''));
        $sourceorderWhere = trim(I('get.sourceorderWhere', ''));
        $tbVersion        = trim(I('get.tbVersion', ''));
        $coinAccount      = M('coin_account');
        if ($start != '' && $end == '') {
            $startdate              = str_replace('-', '', $start);
            $where['ca.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate                = str_replace('-', '', $end);
            $where['ca.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate              = str_replace('-', '', $start);
            $enddate                = str_replace('-', '', $end);
            $where['ca.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }

        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($triggerRules != '') {
            $where['ca.trigger_rules'] = array('like', '%' . $triggerRules . '%');
            $this->assign('triggerRules', $triggerRules);
            $map['triggerRules'] = $triggerRules;
        }
        if ($sourceorderWhere != '') {
            $where['ca.sourceorder'] = $sourceorderWhere;
            $this->assign('sourceorderWhere', $sourceorderWhere);
            $map['sourceorderWhere'] = $sourceorderWhere;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($pname != '') {
            $where['p.namechinese'] = array('like', '%' . $pname . '%');
            $this->assign('pname', $pname);
            $map['pname'] = $pname;
        }

        if ($telphone != '') {
            $where['cu.linktel'] = array('like', '%' . $telphone . '%');
            $this->assign('telphone', $telphone);
            $map['telphone'] = $telphone;
        }

        if ($tbVersion != 1) {
            if ($tbVersion == 2)
                $where['ca.tb_version'] = array("eq", $tbVersion);
            if ($tbVersion == 3)
                $where['ca.tb_version'] = array("exp", "is null");
            $map['tbVersion'] = $tbVersion;
        }
        if ($tbVersion == '')
            $this->assign('tbVersion', 1);
        else
            $this->assign('tbVersion', $tbVersion);

        $_SESSION['issueCon'] = $where;

        $field = 'ca.*,c.cardno,c.cardfee,cu.namechinese cuname,cu.linktel mobile,p.namechinese pname';
        $count = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->count();


        $p    = new \Think\Page($count, 15);
        $list = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('ca.placeddate desc,ca.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->select();


        //--------计算搜索发行额---------------

        $searchAmount = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------

        //--------计算昨天发行额---------------
        // $where_16 = $where;
        // $yesterday = date("Ymd", strtotime(date("Y-m-d"))-86400);
        // $where_16['ca.placeddate']=array(array('eq',$yesterday));
        // $yesterdayAmount =  $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
        //         ->join('cards c on c.customid=ca.cardid')
        //         ->join('customs_c cc on cc.cid=c.customid')
        //         ->join('customs cu on cu.customid=cc.customid')
        //         ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------

        //--------计算今天发行额---------------
        // $where_16 = $where;
        // $today = date("Ymd", strtotime(date("Y-m-d")));
        // $where_16['ca.placeddate']=array(array('eq',$today));
        // $todayAmount =  $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
        //         ->join('cards c on c.customid=ca.cardid')
        //         ->join('customs_c cc on cc.cid=c.customid')
        //         ->join('customs cu on cu.customid=cc.customid')
        //         ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------


        //--------计算上个月发行额---------------
        // $where_16 = $where;
        // $lastmonth_start = date("Ymd",mktime(0, 0 , 0,date("m")-1,1,date("Y")));
        // $lastmonth_end = date("Ymd",mktime(23,59,59,date("m") ,0,date("Y")));
        // $where_16['ca.placeddate']=array(array('egt', $lastmonth_start),array('elt', $lastmonth_end));
        // $lastMothAmount =  $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
        //         ->join('cards c on c.customid=ca.cardid')
        //         ->join('customs_c cc on cc.cid=c.customid')
        //         ->join('customs cu on cu.customid=cc.customid')
        //         ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------

        //--------计算本月发行额---------------
        // $where_16 = $where;
        $thismonth_start           = date("Ymd", mktime(0, 0, 0, date("m"), 1, date("Y")));
        $thismonth_end             = date("Ymd", mktime(23, 59, 59, date("m"), date("t"), date("Y")));
        $where_16['ca.placeddate'] = array(array('egt', $thismonth_start), array('elt', $thismonth_end));
        $thisMothAmount            = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------


        //--------计算2016年发行额---------------
        // $where_16 = $where;
        $where_16['ca.placeddate'] = array(array('egt', "20160101"), array('elt', "20161231"));
        $sum16                     = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where_16)->field("sum(rechargeamount) amount")->find();
        //-------end----------------------
        //--------截止目前发行额---------------
        // $where_curr = $where;
        unset($where_curr['ca.placeddate']);
        $sum_curr = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where_curr)->field("sum(rechargeamount) amount")->find();
        // //-------end----------------------


        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }


        foreach ($list as $key => $value) {
            $triggerCompany = $this->panters->where(array("panterid" => $value["trigger_panterid"]))->field("namechinese")->find();

            $list[$key]["triggerCompany"] = $triggerCompany["namechinese"];
        }


        $page     = $p->show();
        $sum16    = number_format($sum16['amount'], '2', '.', '');
        $sum_curr = number_format($sum_curr['amount'], '2', '.', '');
        $this->assign("sum16", $sum16);
        $this->assign("sum_curr", $sum_curr);
        // $this->assign("todayAmount",number_format($todayAmount["amount"],2));
        // $this->assign("yesterdayAmount",number_format($yesterdayAmount["amount"],2));
        // $this->assign("lastMothAmount",number_format($lastMothAmount["amount"],2));
        $this->assign("thisMothAmount", number_format($thisMothAmount["amount"], 2));
        $this->assign("searchAmount", number_format($searchAmount["amount"], 2));
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }


    //发行报表导出
    public function issue_excel()
    {
        if (isset($_SESSION['issueCon'])) {
            $issueCon = session('issueCon');
            foreach ($issueCon as $key => $val) {
                $where[$key] = $val;
            }
        }
        $coinAccount = M('coin_account');
        $field       = 'ca.*,c.cardno,c.cardfee,cu.namechinese cuname,p.namechinese pname,cu.linktel';
        $list        = $coinAccount->alias('ca')->join('panters p on p.panterid=ca.panterid')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->where($where)->field($field)->order('ca.placeddate desc')->select();

        //print_r($list);exit;
        $strlist = "会员姓名,手机号,卡号,发行金额,发行编号,发行机构,发行时间,卡类型,领取机构，触发规则，通宝版本";

        $strlist = iconv("utf-8", "gbk", $strlist);
        $strlist .= "\n";

        foreach ($list as $key => $value) {
            $triggerCompany = $this->panters->where(array("panterid" => $value["trigger_panterid"]))->field("namechinese")->find();

            $list[$key]["triggerCompany"] = $triggerCompany["namechinese"];
        }

        foreach ($list as $key => $val) {
            $val['cuname']         = iconv("utf-8", "gbk", $val['cuname']);
            $val['pname']          = iconv("utf-8", "gbk", $val['pname']);
            $val['sourceorder']    = iconv("utf-8", "gbk", $val['sourceorder']);
            $cardfee               = $val['cardfee'] == 2 ? iconv("utf-8", "gbk", '电子卡') : iconv("utf-8", "gbk", '实体卡');
            $val["triggerCompany"] = iconv("utf-8", "gbk", $val['triggerCompany']);
            $val["trigger_rules"]  = iconv("utf-8", "gbk", $val['trigger_rules']);
            $version               = $val['tb_version'] == 2 ? iconv("utf-8", "gbk", 'v2.0') : iconv("utf-8", "gbk", 'v1.0');
            $strlist               .= $val['cuname'] . "," . $val['linktel'] . "," . $val['cardno'] . "\t," . floatval($val['rechargeamount']) . "," . $val['sourceorder'];
            $strlist               .= "," . $val['pname'] . "," . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime']));
            $strlist               .= "," . $cardfee . "," . $val["triggerCompany"] . "," . $val["trigger_rules"] . "," . $version . "\n";
        }
        $filename = '建业通宝赠送(入金)报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->load_csv($strlist, $filename);
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

    /**
     * CURL请求
     * @param $url 请求url地址
     * @param $method 请求方法 get post
     * @param null $postfields post数据数组
     * @param array $headers 请求header信息
     * @param bool|false $debug 调试开启 默认false
     * @return mixed
     */
    protected function http_request($url, $method = "GET", $postfields = null, $headers = array(), $debug = false)
    {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /* curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);
        return $response;
    }

    public function tbUserConsume()
    {

        $datami = json_decode(file_get_contents('php://input'), true);

        $des = new DESedeCoder();
        $this->recordData($des->decrypt($datami["datami"]));
        $datami = json_decode($des->decrypt($datami["datami"]), 1);

        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }


        $customid = trim($datami['Member_id']);
        $payPwd   = trim($datami["Pay_password"]);

        $coin        = new CoinController();
        $coinAmount  = trim($datami['consume_amount']);
        $orderid     = trim($datami['order_number']);//获取外部订单
        $sourceCode  = trim($datami['Source_code']);
        $panterid    = $coin->panterArr[$sourceCode]['panterid'];
        $orderPrefix = $coin->panterArr[$sourceCode]['prefix'];
        $backUrl     = urldecode(trim($datami['backUrl']));
        $key         = trim($datami['key']);

        $key1 = md5($this->keycode . $customid . $orderid . $sourceCode . $coinAmount . $payPwd . $datami['backUrl']);

        if ($key1 != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入！'));
        }
        if (empty($customid)) {
            returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $coinAmount)) {
            returnMsg(array('status' => '04', 'codemsg' => '消费金额格式有误'));
        }
        if ($coinAmount == 0) {
            returnMsg(array('status' => '06', 'codemsg' => '消费数据有误'));
        }
        if (empty($orderid)) {
            returnMsg(array('status' => '07', 'codemsg' => '订单编号缺失'));
        }
        $customid = decode($customid);
        $bool     = $this->checkCustom($customid);
        if ($bool == false) {
            returnMsg(array('status' => '08', 'codemsg' => '非法会员编号'));
        }

        // 免密支付 固定传参：JYfghj6789436
        // $checkPayPwdKey=md5($this->keyCode.$this->payPWdKeyCode);

        $checkPayPwdKey = base64_encode("jycard" . md5($this->payPWdKeyCode) . "jycoin");
        if ($checkPayPwdKey !== $payPwd) {

            $paypwd = $this->decodePwd($payPwd);
            if ($paypwd == false) {
                returnMsg(array('status' => '012', 'codemsg' => '非法密码传入'));
            }

            // returnMsg(array('status'=>'013','codemsg'=>'支付密码错误',"custom"=>$customid,"payPwd"=>$paypwd));
            $pwdBool = $this->checkPayPwd($customid, $paypwd);
            if ($pwdBool === '01') {
                returnMsg(array('status' => '013', 'codemsg' => '支付密码错误', "custom" => $customid, "payPwd" => $payPwd));
            } elseif ($pwdBool === '02') {
                returnMsg(array('status' => '016', 'codemsg' => '当天密码错误次数超过三次'));
            }

        }

        // 检测重复订单信息
        $tradeLogs       = M('trade_logs');
        $where           = array('customid' => $customid, 'placeddate' => date('Ymd'), "type" => "1");
        $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
        $date            = time();
        if ($trade_logs_list != false) {
            if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
                returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
            }
        }
        $tradeThenCount = M('trade_wastebooks')->where("eorderid like '%{$orderid}' and tradetype = '00'")->count();
        if ($tradeThenCount > 0) {
            returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
        }
        $currentDate = date('Ymd');
        $currentTime = date('H:i:s');
        $orderType   = '1';
        $sql         = "insert into trade_logs values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}','{$orderType}')";
        $tradeLogs->execute($sql);
        unset($sql);

        $coinAccount = $this->accountQuery($customid, '01');

        if ($coinAccount < $coinAmount) {
            returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
        }
        $this->model->startTrans();
        $coinConsumeIf = false;

        //建业币消费，金额为0不执行
        if ($coinAmount > 0) {
            $map            = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '01');
            $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradepoint');
            if ($balanceConsume > 0) {
                returnMsg(array('status' => '015', 'codemsg' => '此订单已进行建业通宝支付，请勿重复提交'));
            }

            $coinConsumeArr = array('customid' => $customid, 'orderid' => $orderPrefix . $orderid,
                                    'panterid' => $panterid, 'type' => '01', 'amount' => $coinAmount);
            $coinConsumeIf  = $this->consumeCoin($coinConsumeArr);
            if ($coinConsumeIf) {
                //---------向一家传输用户通宝信息--17:21----
                $de      = new YjDes();
                $tb_info = D("Ehome")->getTbinfo($customid);
                if ($tb_info) {
                    $appid                 = 'SOON-ZZUN-0001';
                    $tb_info['customid']   = encode($tb_info['customid']);
                    $tb_info['activetype'] = 2;
                    $tb_info['appid']      = $appid;
                    $tb_sign               = $de->encrypt($tb_info);
                    $tb_data               = json_encode($tb_info, JSON_FORCE_OBJECT);
                    $return_yj             = $this->curlPost(C('ehome_potb'), $tb_data, $tb_sign);
                    $return_arr            = json_decode($return_yj, 1);

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


        if ($coinConsumeIf == true) {
            $this->model->commit();

            ob_clean();
            echo json_encode(array('status' => '1', 'info' => array('reduceCoin' => floatval($coinAmount)), 'publish' => $coinConsumeIf));
            if (!empty($backUrl)) {
                // $backUrl=urldecode($backUrl);
                $backData = array('orderid' => $orderid, 'consumeAmount' => $balanceAmount, 'coinAmount' => $coinAmount, 'payRes' => 1);
                crul_post($backUrl, json_encode($backData));
                $this->recordMsg($orderid, $balanceAmount, $coinAmount, $backUrl);
            }
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
        }


    }


    
    public function tbUserNewConsume()
    {

        $datami = json_decode(file_get_contents('php://input'), true);

        $des = new DESedeCoder();
        $this->recordData($des->decrypt($datami["datami"]));
        $datami = json_decode($des->decrypt($datami["datami"]), 1);

        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }
 
    
        $customid = trim($datami['Member_id']);
        $payPwd   = trim($datami["Pay_password"]);

        $coin        = new CoinController();
        $coinAmount  = trim($datami['consume_amount']);
        $orderid     = trim($datami['order_number']);//获取外部订单
        $sourceCode  = trim($datami['Source_code']);
        $panterid    = $coin->panterArr[$sourceCode]['panterid'];
        $orderPrefix = $coin->panterArr[$sourceCode]['prefix'];
        $backUrl     = urldecode(trim($datami['backUrl']));
        $key         = trim($datami['key']);

        $key1 = md5($this->keycode . $customid . $orderid . $sourceCode . $coinAmount . $payPwd . $datami['backUrl']);
        if(empty($panterid) && $sourceCode=='1007') { //当 标识码 为 1007 时
          $panterid = trim($datami['panterid']);
       }    
        if ($key1 != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入！'));
        }
        if (empty($customid)) {
            returnMsg(array('status' => '03', 'codemsg' => '用户缺失'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $coinAmount)) {
            returnMsg(array('status' => '04', 'codemsg' => '消费金额格式有误'));
        }
        if ($coinAmount == 0) {
            returnMsg(array('status' => '06', 'codemsg' => '消费数据有误'));
        }
        if (empty($orderid)) {
            returnMsg(array('status' => '07', 'codemsg' => '订单编号缺失'));
        }
        $customid = decode($customid);
        $bool     = $this->checkCustom($customid);
        if ($bool == false) {
            returnMsg(array('status' => '08', 'codemsg' => '非法会员编号'));
        }

        // 免密支付 固定传参：JYfghj6789436
        // $checkPayPwdKey=md5($this->keyCode.$this->payPWdKeyCode);

        $checkPayPwdKey = base64_encode("jycard" . md5($this->payPWdKeyCode) . "jycoin");
        if ($checkPayPwdKey !== $payPwd) {

            $paypwd = $this->decodePwd($payPwd);
            if ($paypwd == false) {
                returnMsg(array('status' => '012', 'codemsg' => '非法密码传入'));
            }
            // returnMsg(array('status'=>'013','codemsg'=>'支付密码错误',"custom"=>$customid,"payPwd"=>$paypwd));
            $pwdBool = $this->checkPayPwd($customid, $paypwd);
            if ($pwdBool === '01') {
                returnMsg(array('status' => '013', 'codemsg' => '支付密码错误', "custom" => $customid, "payPwd" => $payPwd));
            } elseif ($pwdBool === '02') {
                returnMsg(array('status' => '016', 'codemsg' => '当天密码错误次数超过三次'));
            }

        }

        // 检测重复订单信息
        $tradeLogs       = M('trade_logs');
        $where           = array('customid' => $customid, 'placeddate' => date('Ymd'), "type" => "1");
        $trade_logs_list = $tradeLogs->where($where)->order('datetimes desc')->select();
        $date            = time();
        if ($trade_logs_list != false) {
            if (intval($date - $trade_logs_list[0]['datetimes']) <= 10) {
                returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
            }
        }
        $tradeThenCount = M('trade_wastebooks')->where("eorderid like '%{$orderid}' and tradetype = '00'")->count();
        if ($tradeThenCount > 0) {
            returnMsg(array('status' => '017', 'codemsg' => '重复支付订单'));
        }
        $currentDate = date('Ymd');
        $currentTime = date('H:i:s');
        $orderType   = '1';
        $sql         = "insert into trade_logs values('{$customid}','" . encode($customid) . "','{$currentDate}','{$currentTime}','{$date}','{$orderid}','{$orderType}')";
        $tradeLogs->execute($sql);
        unset($sql);

        $coinAccount = $this->accountQuery($customid, '01');

        if ($coinAccount < $coinAmount) {
            returnMsg(array('status' => '010', 'codemsg' => '建业通宝余额不足'));
        }
        $this->model->startTrans();
        $coinConsumeIf = false;

        //建业币消费，金额为0不执行
        if ($coinAmount > 0) {
            $map            = array('eorderid' => $orderPrefix . $orderid, 'tradetype' => '01');
            $balanceConsume = M('trade_wastebooks')->where($map)->sum('tradepoint');
            if ($balanceConsume > 0) {
                returnMsg(array('status' => '015', 'codemsg' => '此订单已进行建业通宝支付，请勿重复提交'));
            }

            $coinConsumeArr = array('customid' => $customid, 'orderid' => $orderPrefix . $orderid,
                                    'panterid' => $panterid, 'type' => '01', 'amount' => $coinAmount);
            $coinConsumeIf  = $this->consumeCoin($coinConsumeArr);
            // if ($coinConsumeIf) {
            //     //---------向一家传输用户通宝信息--17:21----
            //     $de      = new YjDes();
            //     $tb_info = D("Ehome")->getTbinfo($customid);
            //     if ($tb_info) {
            //         $appid                 = 'SOON-ZZUN-0001';
            //         $tb_info['customid']   = encode($tb_info['customid']);
            //         $tb_info['activetype'] = 2;
            //         $tb_info['appid']      = $appid;
            //         $tb_sign               = $de->encrypt($tb_info);
            //         $tb_data               = json_encode($tb_info, JSON_FORCE_OBJECT);
            //         $return_yj             = $this->curlPost(C('ehome_potb'), $tb_data, $tb_sign);
            //         $return_arr            = json_decode($return_yj, 1);

            //         if ($return_arr['code'] == '100') {
            //             $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "success");
            //         } else {
            //             $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "failed");
            //         }
            //     }

            //     //---------end-----------------
            // }
        } else {
            $coinConsumeIf = true;
        }


        if ($coinConsumeIf == true) {
            $this->model->commit();

            ob_clean();
            echo json_encode(array('status' => '1', 'info' => array('reduceCoin' => floatval($coinAmount)), 'publish' => $coinConsumeIf));
            if (!empty($backUrl)) {
                // $backUrl=urldecode($backUrl);
                $backData = array('orderid' => $orderid, 'consumeAmount' => $balanceAmount, 'coinAmount' => $coinAmount, 'payRes' => 1);
                crul_post($backUrl, json_encode($backData));
                $this->recordMsg($orderid, $balanceAmount, $coinAmount, $backUrl);
            }
        } else {
            $this->model->rollback();
            returnMsg(array('status' => '011', 'codemsg' => '消费扣款失败'));
        }


    }


// 产生订单数据 $consume是array类型
    protected function createConsumeOrder($consume)
    {

        $consume['tb_version'] = "2";

        $sql = "INSERT INTO coin_consume(coinconsumid,custom_id,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate,tb_time,consume_order,external_order,consume_item,trigger_rules,tb_version,issue_item,issue_company,tb_comment,issue_order)VALUES";

        $sql .= "('" . $this->getFieldNextNumber('coinconsumeid') . "','" . $consume["custom_id"] . "','" . $consume["amount"] . "','" . date("Ymd", time()) . "','" . date("H:i:s", time()) . "','" . $consume['panterid'] . "','" . "1" . "','" . "1" . "','" . "1" . "','" . time() . "','" . time() . "','" . create_order_num() . "','" . $consume['external_order'] . "','" . $consume['consume_item'] . "','" . $consume['trigger_rules'] . "','" . $consume['tb_version'] . "','" . $consume['issue_item'] . "','" . $consume['issue_company'] . "','" . $consume['tb_comment'] . "','" . $consume['issue_order'] . "')";

        $temp = $this->model->execute($sql);


    }

    public function consume()
    {
        $coinConsume  = M('coin_consume');
        $start        = I('get.startdate', '');
        $end          = I('get.enddate', '');
        $customid     = trim(I('get.customid', ''));
        $cuname       = trim(I('get.cuname', ''));
        $cardno       = trim(I('get.cardno', ''));
        $issuepname   = trim(I('get.issuepname', ''));//发行商户名称
        $consumepname = trim(I('get.consumepname', ''));//受理商户名称
        $telphone     = trim(I('get.telphone', ''));
        $linktel      = trim(I('get.linktel', ''));
        $num          = trim(I('get.num', ''));
        $version      = I('get.version', '');
        // 修改“建业通宝兑换机构” 显示“建业至尊外拓” 2018年8月14日19:47:58
        // author@sql
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();//获取外拓商户信息 固定标识 000927
        $outnames   = array_column($outPanters, 'namechinese');
        // end
        if ($start != '' && $end == '') {
            $startdate              = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate                = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate              = str_replace('-', '', $start);
            $enddate                = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }
        if ($start == '' && $end == '') {
            $startdate              = date('Ym01', strtotime(date('Ymd')));
            $enddate                = date('Ymd', time());
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', date('Y-m-d', strtotime($startdate)));
            $this->assign('enddate', date('Y-m-d', strtotime($enddate)));
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }

        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }
        if ($telphone != '') {
            $where['cu.linktel'] = array('like', '%' . $telphone . '%');
            $this->assign('telphone', $telphone);
            $map['telphone'] = $telphone;
        }
        if ($cardno != '') {
            $where['c.cardno'] = $cardno;
            $this->assign('cardno', $cardno);
            $map['cardno'] = $cardno;
        }
        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        // if($status!=''&&$status!='-1'){
        //     $where['cc.status']=$status;
        //     $this->assign('status',$status);
        //     $map['status']=$status;
        // }

        $this->assign('version', $version);
        $map['version'] = $version;


        // $this->assign('status',$status);
        // if($this->panterid!='FFFFFFFF'){
        //     $where1['cc.panterid']=$this->panterid;
        //     $where1['p.parent']=$this->panterid;
        //     $where1['_logic']='or';
        //     $where['_complex']=$where1;
        //     $this->assign('is_admin',0);

        // }else{
        //     $this->assign('is_admin',1);
        // }
        $_SESSION['consumeCon']      = $where;
        $_SESSION['consume_version'] = $version;

        if (!empty($num)) {
            $map['num'] = $num;
            $this->assign('num', $num);
        }
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.status,cc.flag,c.cardno,c.cardfee,cu.namechinese cuname,cu.linktel ptelphone,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,a.remindamount remindamount,tw.termposno,cc.pantercheck';

        $subQuery = "(select sum(remindamount) remindamount,cardid from coin_account group by cardid)";

        if ($version == 1) {

            $tbPoolDetails = $this->tb_pool_details->field("issue_company")->select();


            $issueCompanyData = array_unique(array_column($tbPoolDetails, 'issue_company'));

            $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where(array("p1.namechinese" => array("in", $issueCompanyData)))->where($where)->count();
            $sum   = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->join("tb_pool_details tb on tb.issue_company=p1.namechinese")->where($where)->sum('cc.amount');
            $num   = !empty($num) ? $num : 30;
            $p     = new \Think\Page($count, $num);
            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->join("tb_pool_details tb on tb.issue_company=p1.namechinese")->field($field)
                ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->distinct(true)->where($where)->select();
            // var_dump($where); var_dump($list);

            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->join("tb_pool tb on tb.issue_company=p1.namechinese")->where($where_16)->sum('cc.amount');
            //-------end----------------------


        } else if ($version == 2) {

            $tbPoolDetails = $this->tb_pool_details->field("issue_company")->select();


            $issueCompanyData = array_unique(array_column($tbPoolDetails, 'issue_company'));

            $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->where($where)->count("cc.coinconsumeid");
            $sum   = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->sum('cc.amount');
            $num   = !empty($num) ? $num : 30;
            $p     = new \Think\Page($count, $num);
            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')
                ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->field($field)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->where($where)->select();


            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where_16)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->sum('cc.amount');
            //-------end----------------------

        } else {

            $count = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)->count();
            $sum   = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where)->sum('cc.amount');
            $num   = !empty($num) ? $num : 30;
            $p     = new \Think\Page($count, $num);
            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
                ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();
            // var_dump($where); var_dump($list);

            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where_16)->sum('cc.amount');
            //-------end----------------------
        }


        //-------计算2016年兑换数量---------
        //$where16 = $where;
        $where16['cc.placeddate'] = array(array('egt', "20160101"), array('elt', "20161231"));
        $sum16                    = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
            ->join('panters p1 on p1.panterid=ca.panterid')->where($where16)->sum('cc.amount');
        $sum16                    = number_format($sum16, '2', '.', '');
        //-------end--------------
        // session('consumeCon',$where);
        $c = 0;
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) $c++;

            // 修改“建业通宝兑换机构” 显示“建业至尊外拓” 2018年8月14日19:47:58
            // author@sql
            if (in_array($val['consumepname'], $outnames)) {
                $list[$key]['consumepname'] = '建业至尊（外拓商户简称）';
            }

            // end
        }
        if ($c == 0) $disabled = 1;
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();
        $this->assign("sum16", $sum16);
        $this->assign('page', $page);
        $this->assign('disabled', $disabled);
        $this->assign('list', $list);
        $sum = number_format($sum, '2', '.', '');
        $this->assign('sum', $sum);
        $this->assign('count', $count);
        $this->assign("searchAmount", number_format($searchAmount, 2));
        $this->display();
    }

// 通宝2.0兑换消费明细
    public function consumeOld()
    {

        $coinConsume  = M('coin_consume');
        $start        = I('get.startdate', '');
        $end          = I('get.enddate', '');
        $cuname       = trim(I('get.cuname', ''));
        $issuepname   = trim(I('get.issuepname', ''));//发行商户名称
        $consumepname = trim(I('get.consumepname', ''));//受理商户名称
        $num          = trim(I('get.num', ''));

        if ($start != '' && $end == '') {
            $startdate              = str_replace('-', '', $start);
            $where['cc.placeddate'] = array('egt', $startdate);
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
        }
        if ($start == '' && $end != '') {
            $enddate                = str_replace('-', '', $end);
            $where['cc.placeddate'] = array('elt', $enddate);
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
        }
        if ($start != '' && $end != '') {
            $startdate              = str_replace('-', '', $start);
            $enddate                = str_replace('-', '', $end);
            $where['cc.placeddate'] = array(array('egt', $startdate), array('elt', $enddate));
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
        }

        if ($cuname != '') {
            $where['cu.namechinese'] = array('like', '%' . $cuname . '%');
            $this->assign('cuname', $cuname);
            $map['cuname'] = $cuname;
        }

        if ($issuepname != '') {
            $where['p1.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            $where['p.namechinese'] = array('like', '%' . $consumepname . '%');
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if ($status != '' && $status != '-1') {
            $where['cc.status'] = $status;
            $this->assign('status', $status);
            $map['status'] = $status;
        }

        if (!empty($num)) {
            $map['num'] = $num;
            $this->assign('num', $num);
        }
        $field = 'cc.tb_time,cc.consume_order,cc.trigger_rules,cc.external_order,cc.tb_version,cc.issue_item,cc.issue_company,cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.status,cc.flag,c.cardno,c.cardfee,cu.namechinese cuname,cu.linktel,cu.personid';
        $field .= ',cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.tb_oder_numbe,ca.placeddate issuedate,ca.placedtime issuetime,a.remindamount remindamount,cc.pantercheck';

        $subQuery = "(select sum(remindamount) remindamount,cardid from coin_account group by cardid)";
        /*   $count=$coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')

            ->join($subQuery.' a on a.cardid=c.customid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)->where($where)->count();
        $sum=$coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')

            ->join('panters p1 on p1.panterid=ca.panterid')->where($where)->sum('cc.amount'); */
        $num = !empty($num) ? $num : 30;
        $p   = new \Think\Page($count, $num);
        //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
        $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
            ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
            ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
            ->join($subQuery . ' a on a.cardid=c.customid')
            ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
            ->order('cc.placeddate desc,cc.placedtime desc')->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();

        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $page = $p->show();

        $this->assign('page', $page);

        $this->assign('list', $list);
        $sum = number_format($sum, '2', '.', '');
        $this->assign('sum', $sum);
        $this->assign('count', $count);
        $this->display();
    }

    //通宝消费报表导出
    public function consume_excel()
    {

        set_time_limit(0);
        ini_set('memory_limit', '-1');
        if (isset($_SESSION['consumeCon'])) {
            $consumeCon = session('consumeCon');
            foreach ($consumeCon as $key => $val) {
                $where[$key] = $val;
            }
        }

        if (isset($_SESSION['consume_version'])) {
            $version = $_SESSION['consume_version'];
        } else {
            $version = '';
        }
        $coinConsume = M('coin_consume');
        // 修改“建业通宝兑换机构” 显示“建业至尊外拓” 2018年8月14日19:47:58
        // author@sql
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();//获取外拓商户信息 固定标识 000927
        $outnames   = array_column($outPanters, 'namechinese');
        // end
        $field = 'cc.tradeid,cc.amount,cc.placeddate,cc.placedtime,cc.status,cc.flag,c.cardno,c.cardfee,cu.namechinese cuname,cu.linktel ptelphone,';
        $field .= 'cc.coinconsumeid,p.namechinese consumepname,p1.namechinese issuepname,';
        $field .= 'ca.placeddate issuedate,ca.placedtime issuetime,a.remindamount remindamount,tw.termposno,cc.pantercheck';

        $subQuery = "(select sum(remindamount) remindamount,cardid from coin_account group by cardid)";

        if ($version == 1) {


            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->join("tb_pool_details tb on tb.issue_company=p1.namechinese")->field($field)
                ->order('cc.placeddate desc,cc.placedtime desc')->distinct(true)->where($where)->select();
            // var_dump($where); var_dump($list);

            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->join("tb_pool tb on tb.issue_company=p1.namechinese")->where($where_16)->sum('cc.amount');
            //-------end----------------------


        } else if ($version == 2) {

            $tbPoolDetails = $this->tb_pool_details->field("issue_company")->select();


            $issueCompanyData = array_unique(array_column($tbPoolDetails, 'issue_company'));


            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')
                ->order('cc.placeddate desc,cc.placedtime desc')->field($field)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->where($where)->select();


            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where_16)->where(array("p1.namechinese" => array("not in", $issueCompanyData)))->sum('cc.amount');
            //-------end----------------------

        } else {


            //通宝消费表(兑换时间)--通宝账户表(发行时间)--卡表--会员编号对应表--会员信息表--商户表--正常交易表--商户表
            $list = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join($subQuery . ' a on a.cardid=c.customid')
                ->join('panters p1 on p1.panterid=ca.panterid')->field($field)
                ->order('cc.placeddate desc,cc.placedtime desc')->where($where)->select();
            // var_dump($where); var_dump($list);

            //--------计算搜索兑换额---------------
            $where_16     = $where;
            $searchAmount = $coinConsume->alias('cc')->join('coin_account ca on cc.coinid=ca.coinid')
                ->join('cards c on cc.cardid=c.customid')->join('customs_c csc on csc.cid=c.customid')
                ->join('customs cu on cu.customid=csc.customid')->join('panters p on p.panterid=cc.panterid')
                ->join('trade_wastebooks tw on tw.tradeid=cc.tradeid')
                ->join('panters p1 on p1.panterid=ca.panterid')->where($where_16)->sum('cc.amount');
            //-------end----------------------
        }
        //$strlist="会员姓名,卡号,消费金额,余额,订单编号,受理机构,受理时间,发行机构,发行时间,订单状态,状态";


        // $objPHPExcel = new \PHPExcel();

        $objPHPExcel = $this->objPHPExcel;
        $objSheet    = $objPHPExcel->getActiveSheet();
        $cellMerge   = "A1:N3";
        $titleName   = '通宝兑换报表';
        $this->setTitle($cellMerge, $titleName);
        $startCell   = 'A4';
        $endCell     = 'N4';
        $headerArray = array('会员姓名', '卡号', '消费金额', '余额', '订单编号', '终端号', '受理机构',
            '受理时间', '发行机构', '发行时间', '订单状态', '项目审核', '状态', '卡类型');
        $this->setHeader($startCell, $endCell, $headerArray);
        $setCells = array('A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L');
        $setWidth = array(12, 25, 12, 12, 25, 40, 25, 30, 25, 12, 12);
        $this->setWidth($setCells, $setWidth);
        $total = 0;
        $j     = 5;
        foreach ($list as $key => $val) {
            if ($val['status'] == 0) {
                $status = '未结算';
            } elseif ($val['status'] == 1) {
                $status = '已结算';
            }
            if ($val['flag'] == 1) {
                $flag = '通宝消费';
            } elseif ($val['flag'] == 2) {
                $flag = '消费撤销';
            } elseif ($val['flag'] == 3) {
                $flag = '退款';
            }
            if ($val['pantercheck'] == '1') {
                $pantercheck = '已审核';
            } else {
                $pantercheck = '未审核';
            }
            $total   += $val['amount'];
            $cardfee = $val['cardfee'] == 2 ? '电子卡' : '实体卡';

            // 修改“建业通宝兑换机构” 显示“建业至尊外拓” 2018年8月14日19:47:58
            // author@sql
            if (in_array($val['consumepname'], $outnames)) {
                $list[$key]['consumepname'] = '建业至尊（外拓商户简称）';
            }
            // end

            $objSheet->setCellValue('A' . $j, "'" . $val['cuname'])->setCellValue('B' . $j, "'" . $val['cardno'])
                ->setCellValue('C' . $j, $val['amount'])->setCellValue('D' . $j, $val['remindamount'])
                ->setCellValue('E' . $j, "'" . $val['tradeid'])->setCellValue('F' . $j, "'" . $val['termposno'])
                ->setCellValue('G' . $j, "'" . $val['consumepname'])
                ->setCellValue('H' . $j, "'" . date('Y-m-d H:i:s', strtotime($val['placeddate'] . $val['placedtime'])))
                ->setCellValue('I' . $j, "'" . $val['issuepname'])->setCellValue('J' . $j, "'" . date('Y-m-d H:i:s', strtotime($val['issuedate'] . $val['issuetime'])))
                ->setCellValue('K' . $j, "'" . $flag)->setCellValue('L' . $j, $pantercheck)->setCellValue('M' . $j, $status)->setCellValue('N' . $j, $cardfee);
            $j++;
        }
        $objSheet->getStyle('B' . $j)->applyFromArray(array('font' => array('bold' => true)));
        $objSheet->setCellValue('B' . $j, '合计金额:');
        $objSheet->setCellValue('C' . $j, $searchAmount);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $this->browser_export('Excel5', '通宝兑换报表.xls');//输出到浏览器
        $objWriter->save("php://output");


    }


    // 定时清除过期通宝，1、更新用户总余额 2、更新赠送通宝状态 3、退回通宝池 更新总量
    // 注意：只要是实名的客户，虽然过期时间是二年，guichi_status=2！

    public function clearInvalidCoin()
    {

        $invalidWhere = array("tb_version" => "2", "enddate" => array("elt", date("Ymd")), "remindamount" => array("gt", 0));

        $coinAccount = $this->coin_account->where($invalidWhere)->field("accountid,coinid,panterid,cardid,enddate,placeddate,placedtime,remindamount,issue_item,sourceorder")->select();
        $coin        = new coinController();
// returnMsg(array('status'=>'01','codemsg'=>$coinAccount));
        $errorRecord         = array();
        $invalidCount        = count($coinAccount);
        $invalidSuccessCount = 0;
        foreach ($coinAccount as $key => $value) {

            $jianGeTime    = strtotime($value["placeddate"] . $value["placedtime"]);
            $endTime       = strtotime($value['enddate']);
            $timeFlag      = $endTime - $jianGeTime;
            $activateDay   = ($this->ActivateDay) * 24 * 60 * 60;
            $unactivateDay = ($this->unActivateDay) * 24 * 60 * 60;

            if ($timeFlag >= $activateDay) {
                // 两年
                $this->model->startTrans();
                try {
                    $invalidIf = $this->overDueConsume($value["coinid"], $value["remindamount"], $value["cardid"]);

                    if (!$invalidIf) {
                        $this->model->rollback();
                        $coin->recordError(date("YmdHis") . implode("#", $value) . "两年记录失败！\n", "tbguoqi", "errorlog" . $value['cardid']);
                        continue;
                    } else {
                        $this->model->commit();
                    }
                } catch (\Exception $e) {
                    $this->model->rollback();
                    $coin->recordError(date("YmdHis") . implode("#", $value) . "两年记录失败！\n", "tbguoqi", "errorlog" . $value['cardid'] . "error:" . $e);
                }


            }

            // 十天过期
            if ($timeFlag <= $unactivateDay) {

                $this->model->startTrans();
                try {

                    $invalidIf = $this->overDueConsume($value["coinid"], $value["remindamount"], $value["cardid"]);

                    if (!$invalidIf) {
                        $this->model->rollback();
                        $coin->recordError(date("YmdHis") . implode("#", $value) . "十天过期记录失败\n", "tbguoqi", "errorlog" . $value['cardid']);
                        continue;
                    }

                    // 归池操作
                    $panterMessage = $this->panters->where(array("panterid" => $value["panterid"]))->field("namechinese")->find();
                    $state         = 1;
                    $id            = uniqid();
                    $sql           = "INSERT INTO tb_pool_details(id,issue_company,issue_item,tb_nums,contract_num,tb_number,state,cellin_time)VALUES";

                    $sql .= "('" . $id . "','" . $panterMessage["namechinese"] . "','" . $value["issue_item"] . "','" . $value["remindamount"] . "','" . $value["sourceorder"] . "','" . create_order_num() . "','" . $state . "','" . time() . "')";

                    $resultFlag = $this->model->execute($sql);

                    if (!$resultFlag) {
                        $coin->recordError(date("YmdHis") . implode("#", $value) . "归池失败操作\n", "tbguoqi", "errorlog" . $value['cardid']);
                        continue;
                    }

                    $updateSql = "UPDATE tb_pool set tb_stock=tb_stock+'" . $value['remindamount'] . "'" . "where issue_company='" . $panterMessage["namechinese"] . "' and issue_item='" . $value["issue_item"] . "'";

                    $resultFlag1 = $this->model->execute($updateSql);
                    unset($updateSql);
                    if (!$resultFlag1) {
                        $coin->recordError(date("YmdHis") . implode("#", $value) . "更新池子总量失败\n", "tbguoqi", "errorlog" . $value['cardid']);
                        continue;

                    }

                    $this->model->commit();

                } catch (\Exception $e) {
                    $this->model->rollback();
                    $coin->recordError(date("YmdHis") . implode("#", $value) . "十天记录失败！\n", "tbguoqi", "errorlog" . $value['cardid'] . "error:" . $e);
                }


            }
            $invalidSuccessCount++;

        }
        returnMsg(array('status' => '01', 'codemsg' => '过期操作成功！', "invalidCount" => $invalidCount, "invalidSuccessCount" => $invalidSuccessCount));

    }


// coinid合同编号，amount 消费金额，panterid 过期商户  cardid 卡id
// 业务逻辑  生成 coin_consume 和trade_wasterbooks记录

    private function overDueConsume($coinid, $amount, $cardid)
    {

        if (empty($coinid) || empty($amount) || empty($cardid)) {
            return false;
        }
        $coinconsumeid = $this->getFieldNextNumber('coinconsumeid');
        $card          = M('cards')->where(['customid' => $cardid])->field('cardno')->find();

        $tradeid    = substr($card['cardno'], 15, 4) . date('YmdHis', time());
        $placeddate = date("Ymd", time());
        $placedtime = date("H:i:s", time());;
        $status         = 1;
        $flag           = 1;
        $pantercheck    = 1;
        $checkDate      = date("Ymd", time());
        $panterid       = "00004616";
        $checkdate      = date("Ymd");
        $amount         = sprintf("%.2f", floatval($amount));
        $coinConsumeSql = "INSERT INTO coin_consume(coinconsumeid,tradeid,cardid,coinid,amount,placeddate,placedtime,panterid,status,flag,pantercheck,checkdate)VALUES";
        $coinConsumeSql .= "('" . $coinconsumeid . "','" . $tradeid . "','" . $cardid . "','" . $coinid . "','" . $amount . "','" . $placeddate . "','" . $placedtime . "','" . $panterid . "','" . $status . "','" . $flag . "','" . $pantercheck . "','" . $checkdate . "')";

        $termposno         = '00000002';
        $tradetype         = "27";//通宝消费标志
        $tradepoint        = $amount;
        $flag              = 0;
        $eorderid          = "";
        $tac               = 'abcdefgh';
        $termno            = "00000002";
        $tradeWasteBookSql = "INSERT INTO trade_wastebooks(panterid,termposno,cardno,customid,tradeid,placeddate,placedtime,tradetype,tradepoint,flag,tac,eorderid,termno)VALUES";

        $tradeWasteBookSql .= "('" . $panterid . "','" . $termposno . "','" . $card["cardno"] . "','" . $cardid . "','" . $tradeid . "','" . $placeddate . "','" . $placedtime . "','" . $tradetype . "','" . $tradepoint . "','" . $flag . "','" . $tac . "','" . $eorderid . "','" . $termno . "')";


        //更新用户account表
        $userAccountJoin       = M("account")->where(array("type" => "01", "customid" => $cardid))->field("amount")->find();
        $userAccountUpdateJoin = bcsub($userAccountJoin["amount"], $amount, 2);
        if ($userAccountUpdateJoin < 0) {
            $userAccountUpdateIf = false;
        } else {

            $userAccountUpdateIf = M("account")->where(array("type" => "01", "customid" => $cardid))->save(array("amount" => $userAccountUpdateJoin));

        }
// returnMsg(array('status'=>$sql,'codemsg'=>$tradeWasteBookSql,"un"=>$cardid,"data"=>"出现识别"));
        $coinSqlIf  = $this->model->execute($coinConsumeSql);
        $tradeSqlIf = $this->model->execute($tradeWasteBookSql);

        $this->coin_account->where(array("coinid" => $coinid))->save(array("remindamount" => 0));

        if ($coinSqlIf == true && $tradeSqlIf == true && $userAccountUpdateIf = true)
            return true;
        else  return false;


    }


// 退款

    public function returnGoodsOld()
    {
        $data   = getPostJson();
        $datami = trim($data['datami']);
        $des    = new DESedeCoder();
        $datami = $des->decrypt($datami);
        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }
        $this->recordData($datami);
        $datami      = json_decode($datami, 1);
        $orderid     = trim($datami['orderId']);//获取退款订单号
        $returnCoin  = trim($datami['returnCoin']);//获取退款通宝数量
        $sourceCode  = trim($datami['sourceCode']);//获取识别码
        $key         = trim($datami['key']);
        $coin        = new coinController();
        $orderPrefix = $coin->panterArr[$sourceCode]['prefix'];
        $checkKey    = md5($this->keycode . $orderid . $returnCoin . $sourceCode);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        if (empty($orderid)) {
            returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $returnCoin)) {
            returnMsg(array('status' => '04', 'codemsg' => '退款建业币格式有误'));
        }
        //------------------author sql  20180914
        if ($returnCoin <= 0) {
            returnMsg(array('status' => '05', 'codemsg' => '退款数据有误'));
        } else {
            $orderid = $orderPrefix . $orderid;
            $map     = array('eorderid' => $orderid, 'flag' => 0, 'tradetype' => '00');
            $tradeC  = M('trade_wastebooks')->where($map)
                ->field("tradeid,tradeamount,tradepoint,cardno")->count();

            $tradeC1 = M('income_books')->where(array('active_id' => $orderid))->count();
            if ($tradeC == 0 && $tradeC == 0) {
                returnMsg(array('status' => '06', 'codemsg' => '无此订单支付信息'));
            }
            $tradeInfo = M('trade_wastebooks')->where($map)
                ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

            $map1        = array('eorderid' => $orderid, 'flag' => array('in', array('2', '3')), 'tradetype' => '00');
            $cancleTrade = M('trade_wastebooks')->where($map1)
                ->field("sum(tradeamount) orderamount,sum(tradepoint) ordercoin")->find();

            if ($tradeInfo['ordercoin'] - $cancleTrade['ordercoin'] < $returnCoin) {
                returnMsg(array('status' => '07', 'codemsg' => '退款建业币金额大于订单建业币金额'));
            }
            //echo $orderid.'--'.$returnAmount.'--'.$returnCoin;exit;
            $this->model->startTrans();
            if ($returnCoin > 0) {
                $coinIf = true;
                $coinIf = $coin->refund($orderid, $returnAmount, 2);
            } else {
                $coinIf = false;
            }

            if ($coinIf == true) {

                $this->model->commit();
                returnMsg(array('status' => '1', 'info' => array('addCoin' => floatval($returnCoin))));
            } else {
                $this->model->rollback();
                returnMsg(array('status' => '08', 'codemsg' => '退款失败'));
            }
        }
    }


    public function returnGoods()
    {
        $data = getPostJson();
        $coin = new coinController();
        $coin->recordData("退款datami:" . implode("#", $data));

        $datami = trim($data['datami']);
        $des    = new DESedeCoder();
        $datami = $des->decrypt($datami);

        // returnMsg(array('status'=>$datami,'codemsg'=>'非法数据传入'));
        if ($datami == false) {
            returnMsg(array('status' => '01', 'codemsg' => '非法数据传入'));
        }

        $datami     = json_decode($datami, 1);
        $orderid    = trim($datami['orderId']);//获取退款订单号
        $returnCoin = trim($datami['returnCoin']);//获取退款通宝数量
        $sourceCode = trim($datami['sourceCode']);//获取识别码
        $key        = trim($datami['key']);

        $orderPrefix = $coin->panterArr[$sourceCode]['prefix'];
        $checkKey    = md5($this->keycode . $orderid . $returnCoin . $sourceCode);
        if ($checkKey != $key) {
            returnMsg(array('status' => '02', 'codemsg' => '无效秘钥，非法传入'));
        }
        if (empty($orderid)) {
            returnMsg(array('status' => '03', 'codemsg' => '订单编号缺失'));
        }
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $returnCoin)) {
            returnMsg(array('status' => '04', 'codemsg' => '退款建业币格式有误'));
        }
        //------------------author sql  20180914
        $TbrecordGoods = new TbrecordGoodsController();
        $result        = $TbrecordGoods->ejiaRefund($datami);

        returnMsg($result);

    }

    // ********************************excel******************/
    protected function setTitle($cellMerge, $titleName)
    {
        $objPHPExcel = $this->objPHPExcel;
        $objSheet    = $objPHPExcel->getActiveSheet();
        //合并单元格
        $objSheet->getRowDimension(1)->setRowHeight(10);//设置第二行行高
        $objSheet->getRowDimension(2)->setRowHeight(10);//设置第二行行高
        $objSheet->getRowDimension(3)->setRowHeight(10);//设置第三行行高
        $objPHPExcel->getActiveSheet()->mergeCells($cellMerge);
        $objSheet->setCellValue('A1', $titleName);
        $styleArray1 = array(
            'font'      => array(
                'bold'  => true,
                'size'  => 20,
                'color' => array(
                    'rgb' => '000000',
                ),
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
        $objSheet->setCellValue('A1', $titleName);
    }

    //设置报表第一行内容字段描述
    protected function setHeader($startCell, $endCell, $headerArray)
    {
        $merge       = $startCell . ':' . $endCell;
        $objPHPExcel = $this->objPHPExcel;
        $objSheet    = $objPHPExcel->getActiveSheet();
        $objSheet->getStyle($merge)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');//
        $objPHPExcel->getActiveSheet()->getStyle($merge)->getBorders()->getTop()->getColor()->setARGB('E8E8E8');
        $cellArray = range($startCell, $endCell);
        $str       = substr($startCell, 1, 1);
        //设置样式
        $styleArray1 = array(
            'font' => array(
                'bold'  => true,
                'color' => array(
                    'rgb' => '000000',
                ),
            ));
        foreach ($cellArray as $key => $val) {
            $objSheet->getStyle($val . '4')->applyFromArray($styleArray1);
        }
        $i = 0;
        for ($i = 1; $i < $str; $i++) {
            $arr[$i] = array();
        }
        $arr[$i] = $headerArray;
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

    protected function setWidth($setCells, $setWidth)
    {
        $objPHPExcel = $this->objPHPExcel;
        $i           = 0;
        foreach ($setCells as $key => $val) {
            $i++;
        }
        for ($j = 0; $j < $i; $j++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($setCells[$j])->setWidth($setWidth[$j]);
        }
    }

    protected function browser_export($type, $filename)
    {
//           header("Content-type:text/html;charset=utf-8");
        if ($type == "Excel5") {
            header('Content-Type: application/vnd.ms-excel;charset=gbk');//告诉浏览器将要输出excel03文件
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
        }
        $filename = iconv('utf-8', 'gbk', $filename);
        header('Content-Disposition: attachment;filename="' . $filename . '"');//告诉浏览器将输出文件的名称
        header('Cache-Control: max-age=0');//禁止缓存
    }

    public function load_csv($arrList, $tableName)
    {
        header("Content-type: text/html; charset=gbk");
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $tableName . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $arrList;
    }

    public function export_xls($filename, $string)
    {
        //可以修改样式，控制字号、字体、表格线、对齐方式、表格宽度、单元格padding等，在下边的<style></style>
        $header       = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\nxmlns=\"http://www.w3.org/TR/REC-html40\">\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html>\n<head>\n<meta http-equiv=\"Content-type\" content=\"text/html;charset=GBK\" />\n<style>\ntd{padding:14px;mso-ignore:padding;color:windowtext;font-size:14.0pt;font-weight:400;font-style:normal;text-decoration:none;font-family:Arial;mso-generic-font-family:auto;mso-font-charset:134;mso-number-format:General;text-align:general;vertical-align:middle;border:.5pt solid windowtext;mso-background-source:auto;mso-pattern:auto;mso-protection:locked visible;white-space:nowrap;mso-rotate:0;}\n</style>\n</head><body>\n<table x:str border=0 cellpadding=0 cellspacing=0 width=100% style=\"border-collapse: collapse\">";
        $footer       = "</table>\n</body></html>";
        $exportString = $header . $string . $footer;

        header("Cache-Control:public");
        header("Pragma:public");
        header("Content-type:application/vnd.ms-excel");
        header("Accept-Ranges: bytes");
        header("Content-Disposition:attachment; filename=" . $filename);
        header("Content-length:" . strlen($exportString));
        echo $exportString;
        exit;
    }

    protected function changeCode($input)
    {
        return iconv('utf-8', 'gbk', $input);
    }

// *******************************************************清结算***********************************************//
    function qjs()
    {
        $start        = I('get.startdate', '');
        $end          = I('get.enddate', '');
        $issuepname   = trim(I('get.issuepname', ''));//受理商户名称
        $consumepname = trim(I('get.consumepname', ''));//受理商户名称
        $proname      = trim(I('get.proname', ''));
        $coinAccount  = M('coin_account');
        //echo $proname;

        //联盟商家替换
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames   = array_column($outPanters, 'namechinese');
        $outpanters = array_column($outPanters, 'panterid');
        if ($start != '' && $end == '') {
            $startdate     = str_replace('-', '', $start);
            $subCondition  = " where placeddate>='{$startdate}' ";
            $subCondition1 = " where cc.placeddate>='{$startdate}' ";
            $subCondition2 = "  cc.placeddate>='{$startdate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
            $sendDate         = str_replace('-', '.', $start) . '-' . date('Y.m.d');
        }
        if ($start == '' && $end != '') {
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where placeddate<='{$enddate}' ";
            $subCondition1 = " where cc.placeddate<='{$enddate}' ";
            $subCondition2 = "  cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
            $earlyDate      = M('coin_account')->min('placeddate');
            $sendDate       = date('Y-m-d', strtotime($earlyDate)) . str_replace('-', '.', $end);
        }
        if ($start != '' && $end != '') {
            $startdate     = str_replace('-', '', $start);
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where (placeddate>='{$startdate}' and placeddate<='{$enddate}' )";
            $subCondition1 = " where (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $subCondition2 = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
            $sendDate         = str_replace('-', '.', $start) . '-' . str_replace('-', '.', $end);
        }
        if ($start == '' && $end == '') {
            $earlyDate = M('coin_account')->min('placeddate');
            $sendDate  = date('Y.m.d', strtotime($earlyDate)) . '-' . date('Y.m.d');
        }
        // 判定最后时间
        if ($end !== '') {
            $date = str_replace('-', '', $end);
            session('qjs_date', $date);
        } else {
            $date = null;
        }
        if ($issuepname != '') {
            $where['p.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            if ($consumepname == '建业至尊（外拓商户简称）') {
                $where['p1.namechinese'] = ['in', $outnames];
            } else {
                $where['p1.namechinese'] = array('like', '%' . $consumepname . '%');
            }
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if (!empty($proname)) {
            $this->assign('proname', $proname);
            $data   = array('pantername' => $proname, 'key' => md5('cxfang' . $proname));
            $url    = C('fangzgIP') . '/posindex.php/search/getPanterid';
            $result = crul_post($url, $data);
            $result = json_decode($result, 1);
            if ($result['code'] == 5) {
                $panterArr = $result['msg'];
                foreach ($panterArr as $key => $val) {
                    if (!empty($val['parentid']) && $val['parentid'] != null) {
                        $list[] = $val['parentid'];
                    }
                }
                $where['p.panterid'] = array('in', $list);
            } else {
                $this->error('无该项目的发行记录');
            }
        }

        $tbPoolDetails     = M("tb_pool")->alias("tb")->join("left join panters p on tb.issue_company=p.namechinese")->distinct(true)->field("tb.issue_company,p.panterid")->select();
        $issueCompanyData  = array_unique(array_column($tbPoolDetails, 'panterid'));
        $issueCompanyWhere = "(";
        foreach ($issueCompanyData as $key => $value) {
            if (!$value)
                unset($issueCompanyData[$key]);
            else
                $issueCompanyWhere .= "'" . $value . "',";
        }
        $issueCompanyWhere = rtrim($issueCompanyWhere, ",");
        $issueCompanyWhere .= ")";

        if (empty($subCondition)) {
            $issueCompanyWhere0 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere0 = "and panterid not in {$issueCompanyWhere} ";
        }

        if (empty($subCondition3)) {
            $issueCompanyWhere1 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere1 = "and panterid not in {$issueCompanyWhere} ";
        }


        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} {$issueCompanyWhere0}  group by panterid) ";
        // var_dump($subQuery);
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} {$issueCompanyWhere1} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";

        $field = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";
        $model  = new Model();

        if (empty($subCondition3)) {
            $count = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')->count();
            $p     = new \Think\Page($count, 15);
            $list  = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $p     = isset($_GET['p']) ? $_GET['p'] : 1;
            $list0 = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->select();
            echo "subQuery:";
            var_dump($subQuery);
            echo "subQuery2:";
            var_dump($subQuery2);
            echo "subQuery1:";
            var_dump($subQuery1);
            $list1      = array();
            $consumeArr = array();
            $issueArr   = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[]    = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }

            if (!empty($consumeArr)) {
                $where1                = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid']  = array('not in', $issueArr);
                echo "where:";
                var_dump($where1);
                $list2 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('p.namechinese asc')
                    ->select();

                echo "list2:";
                var_dump($list2);
                echo "sql:";
                var_dump($model->table($subQuery2)->alias('a')->getlastsql());
            } else {
                $list2 = array();
            }
            $list3 = array_merge($list1, $list2);
            $s     = ($p - 1) * 15;
            $e     = $s + 15;
            $list  = array();
            $count = count($list3);
            for ($i = $s; $i < $e; $i++) {
                if ($i > $count - 1) break;
                $list[] = $list3[$i];
            }
            $p = new \Think\Page($count, 15);
        }
        session('qjsCon', array('subCondition'  => $subCondition, 'subCondition2' => $subCondition2,
                                'subCondition3' => $subCondition3, 'where' => $where, 'subCondition1' => $subCondition1, 'date' => $sendDate));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $fzgurl       = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult     = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer        = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
//            $payable =  $this->newProvisions($val['issuepid'],$date);

            $paydata                        = $this->newProvisions($val['issuepid'], $date);
            $payable                        = $paydata['val'];
            $str                            = $paydata['msg'];
            $settleExpire                   = $paydata['expire'];
            $calculatedamount               = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $list[$key]['payable']          = round($payable);
            $list[$key]['str']              = $str;
            $list[$key]['settleExpire']     = $settleExpire;
            $k                              = $refer['key'];
            $list[$key]['issuepname']       = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan']   += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }

            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $list[$key]['pname1']            = '建业至尊（外拓商户简称）';
                $list[$key]['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $list[$key]['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $list[$key]['settlebankid']      = '76190154800003664';

            }
        }
        $page = $p->show();
        $this->assign('date', $sendDate);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    function qjsOld()
    {
        $start        = I('get.startdate', '');
        $end          = I('get.enddate', '');
        $issuepname   = trim(I('get.issuepname', ''));//受理商户名称
        $consumepname = trim(I('get.consumepname', ''));//受理商户名称
        $proname      = trim(I('get.proname', ''));
        $coinAccount  = M('coin_account');
        //echo $proname;

        //联盟商家替换
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames   = array_column($outPanters, 'namechinese');
        $outpanters = array_column($outPanters, 'panterid');
        if ($start != '' && $end == '') {
            $startdate     = str_replace('-', '', $start);
            $subCondition  = " where placeddate>='{$startdate}' ";
            $subCondition1 = " where cc.placeddate>='{$startdate}' ";
            $subCondition2 = "  cc.placeddate>='{$startdate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
            $sendDate         = str_replace('-', '.', $start) . '-' . date('Y.m.d');
        }
        if ($start == '' && $end != '') {
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where placeddate<='{$enddate}' ";
            $subCondition1 = " where cc.placeddate<='{$enddate}' ";
            $subCondition2 = "  cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
            $earlyDate      = M('coin_account')->min('placeddate');
            $sendDate       = date('Y-m-d', strtotime($earlyDate)) . str_replace('-', '.', $end);
        }
        if ($start != '' && $end != '') {
            $startdate     = str_replace('-', '', $start);
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where (placeddate>='{$startdate}' and placeddate<='{$enddate}' )";
            $subCondition1 = " where (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $subCondition2 = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
            $sendDate         = str_replace('-', '.', $start) . '-' . str_replace('-', '.', $end);
        }
        if ($start == '' && $end == '') {
            $earlyDate = M('coin_account')->min('placeddate');
            $sendDate  = date('Y.m.d', strtotime($earlyDate)) . '-' . date('Y.m.d');
        }
        // 判定最后时间
        if ($end !== '') {
            $date = str_replace('-', '', $end);
            session('qjs_date', $date);
        } else {
            $date = null;
        }
        if ($issuepname != '') {
            $where['p.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            if ($consumepname == '建业至尊（外拓商户简称）') {
                $where['p1.namechinese'] = ['in', $outnames];
            } else {
                $where['p1.namechinese'] = array('like', '%' . $consumepname . '%');
            }
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if (!empty($proname)) {
            $this->assign('proname', $proname);
            $data   = array('pantername' => $proname, 'key' => md5('cxfang' . $proname));
            $url    = C('fangzgIP') . '/posindex.php/search/getPanterid';
            $result = crul_post($url, $data);
            $result = json_decode($result, 1);
            if ($result['code'] == 5) {
                $panterArr = $result['msg'];
                foreach ($panterArr as $key => $val) {
                    if (!empty($val['parentid']) && $val['parentid'] != null) {
                        $list[] = $val['parentid'];
                    }
                }
                $where['p.panterid'] = array('in', $list);
            } else {
                $this->error('无该项目的发行记录');
            }
        }

        $tbPoolDetails     = M("tb_pool")->alias("tb")->join("left join panters p on tb.issue_company=p.namechinese")->distinct(true)->field("tb.issue_company,p.panterid")->select();
        $issueCompanyData  = array_unique(array_column($tbPoolDetails, 'panterid'));
        $issueCompanyWhere = "(";
        foreach ($issueCompanyData as $key => $value) {
            if (!$value)
                unset($issueCompanyData[$key]);
            else
                $issueCompanyWhere .= "'" . $value . "',";
        }
        $issueCompanyWhere = rtrim($issueCompanyWhere, ",");
        $issueCompanyWhere .= ")";

        if (empty($subCondition)) {
            $issueCompanyWhere0 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere0 = "and panterid not in {$issueCompanyWhere} ";
        }

        if (empty($subCondition3)) {
            $issueCompanyWhere1 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere1 = "and panterid not in {$issueCompanyWhere} ";
        }


        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} {$issueCompanyWhere0}  group by panterid) ";
        // var_dump($subQuery);
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} {$issueCompanyWhere1} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";

        $field = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";
        $model  = new Model();

        if (empty($subCondition3)) {
            $count = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')->count();
            $p     = new \Think\Page($count, 15);
            $list  = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $p     = isset($_GET['p']) ? $_GET['p'] : 1;
            $list0 = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->select();

            $list1      = array();
            $consumeArr = array();
            $issueArr   = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[]    = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1                = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid']  = array('not in', $issueArr);
                $list2                 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('p.namechinese asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list3 = array_merge($list1, $list2);
            $s     = ($p - 1) * 15;
            $e     = $s + 15;
            $list  = array();
            $count = count($list3);
            for ($i = $s; $i < $e; $i++) {
                if ($i > $count - 1) break;
                $list[] = $list3[$i];
            }
            $p = new \Think\Page($count, 15);
        }
        session('qjsCon', array('subCondition'  => $subCondition, 'subCondition2' => $subCondition2,
                                'subCondition3' => $subCondition3, 'where' => $where, 'subCondition1' => $subCondition1, 'date' => $sendDate));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $fzgurl       = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult     = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer        = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
//            $payable =  $this->newProvisions($val['issuepid'],$date);

            $paydata                        = $this->newProvisions($val['issuepid'], $date);
            $payable                        = $paydata['val'];
            $str                            = $paydata['msg'];
            $settleExpire                   = $paydata['expire'];
            $calculatedamount               = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $list[$key]['payable']          = round($payable);
            $list[$key]['str']              = $str;
            $list[$key]['settleExpire']     = $settleExpire;
            $k                              = $refer['key'];
            $list[$key]['issuepname']       = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan']   += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }

            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $list[$key]['pname1']            = '建业至尊（外拓商户简称）';
                $list[$key]['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $list[$key]['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $list[$key]['settlebankid']      = '76190154800003664';

            }
        }
        $page = $p->show();
        $this->assign('date', $sendDate);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    public function qjs_excel()
    {
        $qjsCon = $_SESSION['qjsCon'];
        if (isset($qjsCon['where'])) {
            $where = $qjsCon['where'];
        }
        $qjsdate       = isset($_SESSION['qjs_date']) ? $_SESSION['qjs_date'] : null;
        $subCondition  = $qjsCon['subCondition'];
        $subCondition1 = $qjsCon['subCondition1'];
        $subCondition2 = $qjsCon['subCondition2'];
        $subCondition3 = $qjsCon['subCondition3'];
        $date          = $qjsCon['date'];

        $tbPoolDetails     = M("tb_pool")->alias("tb")->join("left join panters p on tb.issue_company=p.namechinese")->distinct(true)->field("tb.issue_company,p.panterid")->select();
        $issueCompanyData  = array_unique(array_column($tbPoolDetails, 'panterid'));
        $issueCompanyWhere = "(";
        foreach ($issueCompanyData as $key => $value) {
            if (!$value)
                unset($issueCompanyData[$key]);
            else
                $issueCompanyWhere .= "'" . $value . "',";
        }
        $issueCompanyWhere = rtrim($issueCompanyWhere, ",");
        $issueCompanyWhere .= ")";

        if (empty($subCondition)) {
            $issueCompanyWhere0 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere0 = "and panterid not in {$issueCompanyWhere} ";
        }

        if (empty($subCondition3)) {
            $issueCompanyWhere1 = "where panterid not in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere1 = "and panterid not in {$issueCompanyWhere} ";
        }


        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} {$issueCompanyWhere0}  group by panterid) ";
        // var_dump($subQuery);
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} {$issueCompanyWhere1} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";
        $model     = new Model();
        $field     = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field     .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";


        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames   = array_column($outPanters, 'namechinese');
        $outpanters = array_column($outPanters, 'panterid');
        if (empty($subCondition3)) {
            $list = $model->table($subQuery)->alias('a')
                ->join(' full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')->select();
        } else {
            $list0      = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')
                ->select();
            $list1      = array();
            $consumeArr = array();
            $issueArr   = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[]    = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1                = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid']  = array('not in', $issueArr);
                $list2                 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('a.panterid asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list = array_merge($list1, $list2);
        }
        $fzgurl       = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult     = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer        = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
            $calculatedamount               = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $k                              = $refer['key'];
            $list[$key]['issuepname']       = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan']   += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }
        }
        $strlist = "<tr><th>日期</th><th>发行机构</th><th>发行项目</th><th>需补缴备付金金额</th><th>发行金额</th><th>通宝过期金额</th><th>受理机构</th><th>受理金额</th>";
        $strlist .= "<th>手续费</th><th>已结算金额</th><th>开户行</th><th>开户名</th>银行卡号</th><th>备注</th></tr>";
        $strlist = $this->changeCode($strlist);
        $date    = $this->changeCode($date);
        foreach ($list as $key => $val) {
            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $val['pname1']            = '建业至尊（外拓商户简称）';
                $val['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $val['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $val['settlebankid']      = '76190154800003664';

            }
            $paydata                  = $this->newProvisions($val['issuepid'], $qjsdate);
            $payable                  = round($paydata['val']);
            $settleExpire             = $paydata['expire'];
            $val['pname']             = iconv("utf-8", "gbk", $val['pname']);
            $val['pname1']            = iconv("utf-8", "gbk", $val['pname1']);
            $val['issuepname']        = iconv("utf-8", "gbk", $val['issuepname']);
            $val['settlebankname']    = iconv("utf-8", "gbk", $val['settlebankname']);
            $val['settleaccountname'] = iconv("utf-8", "gbk", $val['settleaccountname']);
            if ($val['rowspan'] != '-1') {
                if (!empty($val['rowspan'])) {
                    $rowspan = " rowspan='{$val['rowspan']}'";
                } else {
                    $rowspan = "";
                }
                $strlist .= "<tr><td {$rowspan}>{$date}</td><td {$rowspan}>{$val['pname']}</td><td {$rowspan}>{$val['issuepname']}</td><td {$rowspan}>{$payable}</td><td {$rowspan}>" . floatval($val['rechargeamount']) . "</td><td {$rowspan}>" . $settleExpire . "</td>";
            }
            $strlist .= "<td>{$val['pname1']}</td><td>" . floatval($val['consumeamount']) . "</td><td></td><td>" . floatval($val['calculatedamount']) . "</td>";
            $strlist .= "<td>{$val['settlebankname']}</td><td>{$val['settleaccountname']}</td><td>{$val['settlebankid']}</td><td></td></tr>";
        }
        $filename = '建业通宝发行受理清结算报表' . date('YmdHis') . '.xls';
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);
        $this->export_xls($filename, $strlist);
    }

    /********************************清结算2.0数据*******************************************/
    public function qjsNew()
    {
        $start        = I('get.startdate', '');
        $end          = I('get.enddate', '');
        $issuepname   = trim(I('get.issuepname', ''));//受理商户名称
        $consumepname = trim(I('get.consumepname', ''));//受理商户名称
        $proname      = trim(I('get.proname', ''));
        $coinAccount  = M('coin_account');
        //echo $proname;

        //联盟商家替换
        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames   = array_column($outPanters, 'namechinese');
        $outpanters = array_column($outPanters, 'panterid');
        if ($start != '' && $end == '') {
            $startdate     = str_replace('-', '', $start);
            $subCondition  = " where placeddate>='{$startdate}' ";
            $subCondition1 = " where cc.placeddate>='{$startdate}' ";
            $subCondition2 = "  cc.placeddate>='{$startdate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $map['startdate'] = $start;
            $sendDate         = str_replace('-', '.', $start) . '-' . date('Y.m.d');
        }
        if ($start == '' && $end != '') {
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where placeddate<='{$enddate}' ";
            $subCondition1 = " where cc.placeddate<='{$enddate}' ";
            $subCondition2 = "  cc.placeddate<='{$enddate}' ";
            $this->assign('enddate', $end);
            $map['enddate'] = $end;
            $earlyDate      = M('coin_account')->min('placeddate');
            $sendDate       = date('Y-m-d', strtotime($earlyDate)) . str_replace('-', '.', $end);
        }
        if ($start != '' && $end != '') {
            $startdate     = str_replace('-', '', $start);
            $enddate       = str_replace('-', '', $end);
            $subCondition  = " where (placeddate>='{$startdate}' and placeddate<='{$enddate}' )";
            $subCondition1 = " where (cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' )";
            $subCondition2 = " cc.placeddate>='{$startdate}' and cc.placeddate<='{$enddate}' ";
            $subCondition3 = " where placeddate<'{$startdate}' ";
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
            $map['startdate'] = $start;
            $map['enddate']   = $end;
            $sendDate         = str_replace('-', '.', $start) . '-' . str_replace('-', '.', $end);
        }
        if ($start == '' && $end == '') {
            $earlyDate = M('coin_account')->min('placeddate');
            $sendDate  = date('Y.m.d', strtotime($earlyDate)) . '-' . date('Y.m.d');
        }
        // 判定最后时间
        if ($end !== '') {
            $date = str_replace('-', '', $end);
            session('qjs_date', $date);
        } else {
            $date = null;
        }
        if ($issuepname != '') {
            $where['p.namechinese'] = array('like', '%' . $issuepname . '%');
            $this->assign('issuepname', $issuepname);
            $map['issuepname'] = $issuepname;
        }
        if ($consumepname != '') {
            if ($consumepname == '建业至尊（外拓商户简称）') {
                $where['p1.namechinese'] = ['in', $outnames];
            } else {
                $where['p1.namechinese'] = array('like', '%' . $consumepname . '%');
            }
            $this->assign('consumepname', $consumepname);
            $map['consumepname'] = $consumepname;
        }
        if (!empty($proname)) {
            $this->assign('proname', $proname);
            $data   = array('pantername' => $proname, 'key' => md5('cxfang' . $proname));
            $url    = C('fangzgIP') . '/posindex.php/search/getPanterid';
            $result = crul_post($url, $data);
            $result = json_decode($result, 1);
            if ($result['code'] == 5) {
                $panterArr = $result['msg'];
                foreach ($panterArr as $key => $val) {
                    if (!empty($val['parentid']) && $val['parentid'] != null) {
                        $list[] = $val['parentid'];
                    }
                }
                $where['p.panterid'] = array('in', $list);
            } else {
                $this->error('无该项目的发行记录');
            }
        }

        $tbPoolDetails     = M("tb_pool")->alias("tb")->join("left join panters p on tb.issue_company=p.namechinese")->distinct(true)->field("tb.issue_company,p.panterid")->select();
        $issueCompanyData  = array_unique(array_column($tbPoolDetails, 'panterid'));
        $issueCompanyWhere = "(";
        foreach ($issueCompanyData as $key => $value) {
            if (!$value)
                unset($issueCompanyData[$key]);
            else
                $issueCompanyWhere .= "'" . $value . "',";
        }
        $issueCompanyWhere = rtrim($issueCompanyWhere, ",");
        $issueCompanyWhere .= ")";

        if (empty($subCondition)) {
            $issueCompanyWhere0 = "where panterid  in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere0 = "and panterid  in {$issueCompanyWhere} ";
        }

        if (empty($subCondition3)) {
            $issueCompanyWhere1 = "where panterid  in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere1 = "and panterid  in {$issueCompanyWhere} ";
        }


        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} {$issueCompanyWhere0}  group by panterid) ";
        // var_dump($subQuery);
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} {$issueCompanyWhere1} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";

        $field = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";
        $model  = new Model();

        if (empty($subCondition3)) {
            $count = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')->count();
            $p     = new \Think\Page($count, 15);
            $list  = $model->table($subQuery)->alias('a')
                ->join('left join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $p     = isset($_GET['p']) ? $_GET['p'] : 1;
            $list0 = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('p.namechinese asc')
                ->select();

            $list1      = array();
            $consumeArr = array();
            $issueArr   = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[]    = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1                = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid']  = array('not in', $issueArr);
                $list2                 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('p.namechinese asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list3 = array_merge($list1, $list2);
            $s     = ($p - 1) * 15;
            $e     = $s + 15;
            $list  = array();
            $count = count($list3);
            for ($i = $s; $i < $e; $i++) {
                if ($i > $count - 1) break;
                $list[] = $list3[$i];
            }
            $p = new \Think\Page($count, 15);
        }
        session('qjsCon', array('subCondition'  => $subCondition, 'subCondition2' => $subCondition2,
                                'subCondition3' => $subCondition3, 'where' => $where, 'subCondition1' => $subCondition1, 'date' => $sendDate));
        if (!empty($map)) {
            foreach ($map as $key => $val) {
                $p->parameter[$key] = $val;
            }
        }
        $fzgurl       = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult     = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer        = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
//            $payable =  $this->newProvisions($val['issuepid'],$date);

            $paydata                        = $this->newProvisions($val['issuepid'], $date);
            $payable                        = $paydata['val'];
            $str                            = $paydata['msg'];
            $settleExpire                   = $paydata['expire'];
            $calculatedamount               = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $list[$key]['payable']          = round($payable);
            $list[$key]['str']              = $str;
            $list[$key]['settleExpire']     = $settleExpire;
            $k                              = $refer['key'];
            $list[$key]['issuepname']       = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan']   += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }

            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $list[$key]['pname1']            = '建业至尊（外拓商户简称）';
                $list[$key]['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $list[$key]['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $list[$key]['settlebankid']      = '76190154800003664';

            }
        }
        $page = $p->show();
        $this->assign('date', $sendDate);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    public function qjsNew_excel()
    {
        $qjsCon = $_SESSION['qjsCon'];
        if (isset($qjsCon['where'])) {
            $where = $qjsCon['where'];
        }
        $qjsdate       = isset($_SESSION['qjs_date']) ? $_SESSION['qjs_date'] : null;
        $subCondition  = $qjsCon['subCondition'];
        $subCondition1 = $qjsCon['subCondition1'];
        $subCondition2 = $qjsCon['subCondition2'];
        $subCondition3 = $qjsCon['subCondition3'];
        $date          = $qjsCon['date'];

        $tbPoolDetails     = M("tb_pool")->alias("tb")->join("left join panters p on tb.issue_company=p.namechinese")->distinct(true)->field("tb.issue_company,p.panterid")->select();
        $issueCompanyData  = array_unique(array_column($tbPoolDetails, 'panterid'));
        $issueCompanyWhere = "(";
        foreach ($issueCompanyData as $key => $value) {
            if (!$value)
                unset($issueCompanyData[$key]);
            else
                $issueCompanyWhere .= "'" . $value . "',";
        }
        $issueCompanyWhere = rtrim($issueCompanyWhere, ",");
        $issueCompanyWhere .= ")";

        if (empty($subCondition)) {
            $issueCompanyWhere0 = "where panterid  in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere0 = "and panterid  in {$issueCompanyWhere} ";
        }

        if (empty($subCondition3)) {
            $issueCompanyWhere1 = "where panterid  in {$issueCompanyWhere} ";
        } else {
            $issueCompanyWhere1 = "and panterid  in {$issueCompanyWhere} ";
        }


        $subQuery = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition} {$issueCompanyWhere0}  group by panterid) ";
        // var_dump($subQuery);
        $subQuery2 = "(select sum(rechargeamount) rechargeamount,panterid from  coin_account {$subCondition3} {$issueCompanyWhere1} group by panterid) ";
        $subQuery1 = "(select ca.panterid pid1,cc.panterid pid2,sum(amount) consumeamount from coin_consume cc inner join ";
        $subQuery1 .= "coin_account ca on cc.coinid=ca.coinid {$subCondition1} group by ca.panterid,cc.panterid)";
        $model     = new Model();
        $field     = "a.rechargeamount,a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field     .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";

        $field2 = "a.panterid issuepid,p.namechinese pname,b.pid2 consumepid,p1.namechinese pname1,";
        $field2 .= "p1.settlebankname,p1.settlebankid,p1.settleaccountname,b.consumeamount";


        $outPanters = M('panters')->where(['parent' => '00000927'])->field('panterid,namechinese')->select();
        $outnames   = array_column($outPanters, 'namechinese');
        $outpanters = array_column($outPanters, 'panterid');
        if (empty($subCondition3)) {
            $list = $model->table($subQuery)->alias('a')
                ->join(' full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')->select();
        } else {
            $list0      = $model->table($subQuery)->alias('a')
                ->join('full join' . $subQuery1 . ' b on a.panterid=b.pid1')
                ->join('left join panters p on a.panterid=p.panterid')
                ->join('left join panters p1 on b.pid2=p1.panterid')
                ->where($where)->field($field)->order('a.panterid asc')
                ->select();
            $list1      = array();
            $consumeArr = array();
            $issueArr   = array();
            foreach ($list0 as $key => $val) {
                if (!empty($val['issuepid'])) {
                    $list1[]    = $val;
                    $issueArr[] = $val['issuepid'];
                } else {
                    $consumeArr[] = $val['consumepid'];
                }
            }
            if (!empty($consumeArr)) {
                $where1                = $where;
                $where1['p1.panterid'] = array('in', $consumeArr);
                $where1['p.panterid']  = array('not in', $issueArr);
                $list2                 = $model->table($subQuery2)->alias('a')
                    ->join('right join' . $subQuery1 . ' b on a.panterid=b.pid1')
                    ->join('left join panters p on a.panterid=p.panterid')
                    ->join('left join panters p1 on b.pid2=p1.panterid')
                    ->where($where1)->field($field2)->order('a.panterid asc')
                    ->select();
            } else {
                $list2 = array();
            }
            $list = array_merge($list1, $list2);
        }
        $fzgurl       = "https://www.fangzg.cn/posindex.php/panterInfo/getInfo";
        $fzresult     = $this->getQuery($fzgurl, array("key" => md5(md5("getInfo"))));
        $refer        = array();
        $coin_account = M('coin_account');
        foreach ($list as $key => $val) {
            $where1 = array('ca.panterid' => $val['issuepid'], 'cc.panterid' => $val['consumepid'], 'cc.status' => 1);
            if (!empty($subCondition2)) {
                $where1['_string'] = $subCondition2;
            }
            $calculatedamount               = $coin_account->alias('ca')->join('coin_consume cc on ca.coinid=cc.coinid')
                ->where($where1)->sum('cc.amount');
            $list[$key]['calculatedamount'] = empty($calculatedamount) ? 0 : $calculatedamount;
            $k                              = $refer['key'];
            $list[$key]['issuepname']       = $fzresult[$val['issuepid']];
            if ($val['issuepid'] == $refer['panterid']) {
                $list[$k]['rowspan']   += empty($list[$k]['rowspan']) ? 2 : 1;
                $list[$key]['rowspan'] += -1;
            } else {
                $refer = array('panterid' => $val['issuepid'], 'key' => $key);
            }
        }
        $strlist = "<tr><th>日期</th><th>发行机构</th><th>发行项目</th><th>需补缴备付金金额</th><th>发行金额</th><th>通宝过期金额</th><th>受理机构</th><th>受理金额</th>";
        $strlist .= "<th>手续费</th><th>已结算金额</th><th>开户行</th><th>开户名</th>银行卡号</th><th>备注</th></tr>";
        $strlist = $this->changeCode($strlist);
        $date    = $this->changeCode($date);
        foreach ($list as $key => $val) {
            //联盟商家替换
            if (in_array($val['pname1'], $outnames)) {
                $val['pname1']            = '建业至尊（外拓商户简称）';
                $val['settlebankname']    = '上海浦东发展银行股份有限公司郑州郑东新区支行';
                $val['settleaccountname'] = '郑州建业至尊商务服务有限公司';
                $val['settlebankid']      = '76190154800003664';

            }
            $paydata                  = $this->newProvisions($val['issuepid'], $qjsdate);
            $payable                  = round($paydata['val']);
            $settleExpire             = $paydata['expire'];
            $val['pname']             = iconv("utf-8", "gbk", $val['pname']);
            $val['pname1']            = iconv("utf-8", "gbk", $val['pname1']);
            $val['issuepname']        = iconv("utf-8", "gbk", $val['issuepname']);
            $val['settlebankname']    = iconv("utf-8", "gbk", $val['settlebankname']);
            $val['settleaccountname'] = iconv("utf-8", "gbk", $val['settleaccountname']);
            if ($val['rowspan'] != '-1') {
                if (!empty($val['rowspan'])) {
                    $rowspan = " rowspan='{$val['rowspan']}'";
                } else {
                    $rowspan = "";
                }
                $strlist .= "<tr><td {$rowspan}>{$date}</td><td {$rowspan}>{$val['pname']}</td><td {$rowspan}>{$val['issuepname']}</td><td {$rowspan}>{$payable}</td><td {$rowspan}>" . floatval($val['rechargeamount']) . "</td><td {$rowspan}>" . $settleExpire . "</td>";
            }
            $strlist .= "<td>{$val['pname1']}</td><td>" . floatval($val['consumeamount']) . "</td><td></td><td>" . floatval($val['calculatedamount']) . "</td>";
            $strlist .= "<td>{$val['settlebankname']}</td><td>{$val['settleaccountname']}</td><td>{$val['settlebankid']}</td><td></td></tr>";
        }
        $filename = '建业通宝发行受理清结算报表' . date('YmdHis') . '.xls';
        $filename = iconv("utf-8", "gbk", $filename);
        unset($list);

        $this->export_xls($filename, $strlist);
    }

    public function getQuery($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//禁用SSL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close();
        return json_decode($output, true);
    }

    /*
   * @author  wanqk
   * @date    2018-07-28
   * @content 商户应交备付金计算
   * @formula (截止到当前发行通宝金额 - 截止当前已经兑换)*0.2 + 截止当前已经兑换 - 已收到备付金总额
   * @param   $panterid issue panterid
   */
    private function newProvisions($panterid, $date = null)
    {
        if (is_null($date)) {
            $nowtime = date('Ymd', time());//现在时间
            $subTime = $nowtime;//获取要查询的时间
        } else {
            $nowtime = $date;
            $subTime = $date;
        }
        $endLastMonth   = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 day'));//上个月最后一天
        $startLastMonth = date('Ymd', strtotime(date('Y-m-01', strtotime($subTime)) . ' -1 month'));//上个月第一天

        $model = new Model();

        $map['panterid']   = $panterid;
        $map['placeddate'] = ['elt', $nowtime];
        $issueTotalAmount  = $model->table('coin_account')
            ->where($map)
            ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];


        if ($issueTotalAmount != 0) {

            $last['panterid']   = $panterid;
            $last['placeddate'] = ['elt', $endLastMonth];

            //截止上月
            $issueLastAmount             = $model->table('coin_account')
                ->where($last)
                ->field('nvl(sum(rechargeamount),0) issueamount')->find()['issueamount'];
            $settle['ca.panterid']       = $panterid;
            $settle['cc.placeddate']     = ['elt', $nowtime];
            $settleTotalAmount           = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settle)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额
            $settleLast['ca.panterid']   = $panterid;
            $settleLast['cc.placeddate'] = ['elt', $endLastMonth];
            $settleLastAmount            = $model->table('coin_account')->alias('ca')
                ->join('left join coin_consume cc on cc.coinid=ca.coinid')
                ->where($settleLast)
                ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额


        } else {
            $settleTotalAmount = 0;
            $issueLastAmount   = 0;
        }
        //已收到备付金总额 = 截至上月通宝发行总金额-截止上月已经兑换）*0.2+截止上月已经兑换
        $provisionAmount = bcadd(bcmul(bcsub($issueLastAmount, $settleLastAmount, 2), 0.2, 2), $settleLastAmount, 2);
        //(截止到当前发行通宝金额 - 截止当前已经兑换)*0.2 + 截止当前已经兑换
        $provisionNow = bcadd(bcmul(bcsub($issueTotalAmount, $settleTotalAmount, 2), 0.2, 2), $settleTotalAmount, 2);
        $value        = bcsub($provisionNow, $provisionAmount, 2);
        $str          = "目前发行:$issueTotalAmount, 截止上月发行:$issueLastAmount,目前兑换:$settleTotalAmount,截止上月兑换:$settleLastAmount";
        //返回通宝过期消费商户 目前 已经兑换的金额
        $settle['cc.panterid']   = '00004616';
        $settle['ca.panterid']   = $panterid;
        $settle['cc.placeddate'] = ['elt', $nowtime];
        $expire                  = $model->table('coin_account')->alias('ca')
            ->join('left join coin_consume cc on cc.coinid=ca.coinid')
            ->where($settle)
            ->field('nvl(sum(cc.amount),0) amount')->find()['amount'];//累计已经兑换金额

        return ['val' => $value, 'msg' => $str, 'expire' => $expire];
    }

    /**
     * 白名单列表
     */
    public function whiteList()
    {
        $model       = M('whitelist');
        $start       = I('get.startdate', '');
        $end         = I('get.enddate', '');
        $namechinese = I('get.namechinese', '');
        $sourceorder = I('get.sourceorder', '');
        $linktel     = I('get.linktel', '');
        $where       = [];
        if ($start != '' && $end != '') {
            $where['createdate'] = [
                'between', [
                    str_replace('-', '', $start),
                    str_replace('-', '', $end)
                ]
            ];
            $this->assign('startdate', $start);
            $this->assign('enddate', $end);
        }
        if ($namechinese != '') {
            $where['p1.namechinese'] = [
                'like', '%' . $namechinese . '%'
            ];
            $this->assign('namechinese', $namechinese);
        }
        if ($sourceorder != '') {
            $where['cc.sourceorder'] = [
                'like', '%' . $sourceorder . '%'
            ];
            $this->assign('sourceorder', $sourceorder);
        }
        if ($linktel != '') {
            $where['en.linktel'] = [
                'like', '%' . $linktel . '%'
            ];
            $this->assign('linktel', $linktel);
        }
        session('whiteList', $where);
        $filed = 'en.namechinese cuname,en.linktel,cc.sourceorder,p1.namechinese,wl.status,wl.createtime,wl.createdate  ';
        $count = $model
            ->alias('wl')
            ->join('left join account c on wl.accountid=c.accountid')
            ->join('left join customs en on en.customid=wl.customid')
            ->join('left join coin_account cc on cc.sourceorder=wl.sourceorder')
            ->join('left join panters  p1 on p1.panterid=cc.panterid ')
            ->where($where)
            ->order('wl.createdate')
            ->count();
        $p     = new \Think\Page($count, 15);
        $list  = $model
            ->alias('wl')
            ->join('left join account c on wl.accountid=c.accountid')
            ->join('left join customs en on en.customid=wl.customid')
            ->join('left join coin_account cc on cc.sourceorder=wl.sourceorder')
            ->join('left join panters  p1 on p1.panterid=cc.panterid ')
            ->where($where)
            ->field($filed)
            ->order('wl.createdate')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        $page  = $p->show();
//        echo '<pre>';
//        var_dump($list);
//        echo '</pre>';
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 根据合同编号获取
     */
    public function getContent()
    {
        $sourceorder = I('post.sourceorder');
        if (!$sourceorder) {
            return $this->error('请输入合同编号');
        }
        $where  = [
            'ca.sourceorder' => $sourceorder
        ];
        $result = M('coin_account')
            ->alias('ca')
            ->join('cards c on c.customid=ca.cardid')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->join('left join panters  p1 on p1.panterid=ca.panterid ')
            ->where($where)->field('p1.namechinese,ca.accountid,cu.namechinese cuname,ca.sourceorder,cu.customid')->find();
        if ($result) {
            return $this->correct('正常返回', $result);
        } else {
            return $this->mistake('该合同不存在');
        }
    }

    /**
     * 搜索十个合同编号
     */
    public function search()
    {
//        header('Content-Type:application/json; charset=utf-8');
        $callback    = I('get.cb');
        $sourceorder = I('get.sourceorder');
        $data        = M('coin_account')->where(['sourceorder' => array('like', '%' . $sourceorder . '%')])->field('sourceorder')->limit(0, 10)->select();
        $array       = [];
        foreach ($data as $v) {
            $array[] = $v['sourceorder'];
        }
        echo $callback . '(' . json_encode(['p' => false, 'q' => $sourceorder, 's' => $array], 256) . ')';;
        exit;
    }

    /**
     * 白名单添加
     *
     */
    public function whiteListAdd()
    {
        $data        = I();
        $accountid   = isset($data['accountid']) ? $data['accountid'] : $this->mistake('请输入正确的合同编号');
        $sourceorder = isset($data['sourceorder']) ? $data['sourceorder'] : $this->mistake('请输入正确的合同编号');
        $customid    = isset($data['customid']) ? $data['customid'] : $this->mistake('请输入正确的合同编号');
        $model       = new WhiteListModel();
        $result      = $model->where(['sourceorder' => $sourceorder])->find();
        if ($result) {
            $this->mistake('该合同已加入白名单');
        }
        $result = $model->oracleInsert([
            'accountid'   => $accountid,
            'sourceorder' => $sourceorder,
            'customid'    => $customid,
            'createtime'  => date('H:i:s'),
            'createdate'  => date('Ymd'),
            'status'      => 0
        ]);
        if ($result) {
            $this->correct('添加成功');
        } else {
            $this->mistake('添加失败');
        }
    }

    /**
     *
     * 白名单修改状态
     */
    public function whiteListChange()
    {
        $sourceorder = I('post.sourceorder', '');
        $status      = I('post.status', '');
        if ($sourceorder == '' || $status == '') {
            $this->mistake();
        }
        $model  = new WhiteListModel();
        $result = $model->oracleUpdate(['status' => $status], ['sourceorder' => $sourceorder]);
        if ($result) {
            $this->correct();
        } else {
            $this->mistake();
        }
    }

    public function whiteListExcel()
    {
        $where = session('whiteList');
        $filed = 'en.namechinese cuname,en.linktel,cc.sourceorder,p1.namechinese,wl.status,wl.createtime,wl.createdate  ';
        $model = M('whitelist');
        $list  = $model
            ->alias('wl')
            ->join('left join account c on wl.accountid=c.accountid')
            ->join('left join customs en on en.customid=wl.customid')
            ->join('left join coin_account cc on cc.sourceorder=wl.sourceorder')
            ->join('left join panters  p1 on p1.panterid=cc.panterid ')
            ->where($where)
            ->field($filed)
            ->order('wl.createdate')
            ->select();
        if (empty($list)) {
            return $this->error('该条件暂无数据');
        }
        $array = [];
        foreach ($list as $k => $v) {
            $array[$k] = [
                'cuname'      => $v['cuname'],
                'linktel'     => $v['linktel'],
                'sourceorder' => $v['sourceorder'],
                'namechinese' => $v['namechinese'],
                'status'      => $v['status'] ? '正常' : '未启用',
                'createtime'  => date('Y-m-d', strtotime($v['createdate'])) . ' ' . $v['createtime']
            ];
        }
        $cellName = [
            '会员名称',
            '手机号',
            '合同编号',
            '发行项目',
            '状态',
            '添加时间'
        ];
        $filename = 'whitelist' . date('YmdHis');
        array_unshift($array, $cellName);
        return $this->load_excel($array, $filename);
    }

    /**
     * 批量导入
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excel()
    {
        if (!empty($_FILES['file_stu']['name'])) {
            $m = $n = 0;
            set_time_limit(0);
            $tmp_file   = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['file_stu'] ['name']);
            $file_type  = $file_types [count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xls" && strtolower($file_type) != "xlsx") {
                $this->error('不是Excel文件，重新上传');
            }
            /*设置上传路径*/
            $savePath = './Public/upfile/Excel/';
            /*以时间来命名上传的文件*/
            $str       = date('Ymdhis');
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file, $savePath . $file_name)) {
                $this->error('上传失败');
            }
            $exceldate = $this->import_excel($savePath . $file_name, 1);
            $arr       = $array = array();

            $counts = 0;
            foreach ($exceldate as $k => $v) {
                $model  = new WhiteListModel();
                $result = $model->where(['sourceorder' => $v[1]])->find();
                if ($result) {
                    $counts++;
                    continue;
                }
                $where  = [
                    'ca.sourceorder' => $v[1]
                ];
                $result = M('coin_account')
                    ->alias('ca')
                    ->join('cards c on c.customid=ca.cardid')
                    ->join('customs_c cc on cc.cid=c.customid')
                    ->join('customs cu on cu.customid=cc.customid')
                    ->join('left join panters  p1 on p1.panterid=ca.panterid ')
                    ->where($where)->field('p1.namechinese,ca.accountid,cu.namechinese cuname,ca.sourceorder,cu.customid')->find();
                if (!$result) {
                    break;
                } else {
                    $result = $model->oracleInsert([
                        'accountid'   => $result['accountid'],
                        'sourceorder' => $result['sourceorder'],
                        'customid'    => $result['customid'],
                        'createtime'  => date('H:i:s'),
                        'createdate'  => date('Ymd'),
                        'status'      => 1
                    ]);
                    if (!$result) {
                        break;
                    }
                }
                $counts++;
            }
            $this->success('批量导入白名单成功' . $counts . '条', U('Tongbao/whiteList'));
        }
    }

    /**
     * @param string $msg
     * @param array $data
     * @param string $url
     */
    protected function mistake($msg = '操作失败', $data = [], $url = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        $code = array(
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => ''
        );
        echo json_encode($code, 256);
        exit;
    }

    protected function correct($msg = '操作成功', $data = [], $url = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        $code = array(
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => ''
        );
        echo json_encode($code, 256);
        exit;
    }

    /**
     * 刷新重置认证
     */
    public function refreshReal()
    {
        $model = new Model();
        $where = [];

        $namechinese = I('get.namechinese', '');
        if ($namechinese != '') {
            $where['namechinese'] = array(
                'like', '%' . $namechinese . '%'
            );
            $this->assign('namechinese', $namechinese);
        }
        $personid = I('get.personid', '');
        if ($personid != '') {
            $where['personid'] = array(
                'like', '%' . $personid . '%'
            );
            $this->assign('personid', $personid);
        }

        $linktel = I('get.linktel', '');
        if ($linktel != '') {
            $where['linktel'] = array(
                'like', '%' . $linktel . '%'
            );
            $this->assign('linktel', $linktel);
        }
     if($where){
         $field = 'namechinese,customid,personid,rate,linktel';
         $count = $model->table('customs')->where($where)->count();
         $p     = new \Think\Page($count, 15);
         $list  = $model->table('customs')
             ->where($where)
             ->limit($p->firstRow . ',' . $p->listRows)->field($field)
             ->order('customid desc')
             ->select();
         $page  = $p->show();
         $this->assign('page', $page);
         $this->assign('list', $list);
     }
        $this->display();
    }

    /**
     * 重置
     *
     */
    public function refresh()
    {

        $customid = I('post.customid', '');
        if ($customid == "") {
            $this->mistake('参数错误！');
        }
        $data = M('customs')->where(['customid' => $customid])->find();
        if (!$data) {
            $this->mistake('用户不存在！');
        }
        if ($data['rate'] == 1) {
            $this->mistake('用户未认证！');
        }

        if(!$data){
            $this->mistake('缺少参数！');
        }
        $result = $this->http_request(C('refreshRate'),'POST', ['cardno' => $data['personid'], 'mobilephone' => $data['linktel']]);
        $result=json_decode($result,true);

        if (isset($result['code']) && $result['code'] == 100) {
            $result = M('customs')->execute('update customs set rate = 1,personid=null where customid=' . "'" . $customid . "'");
            if ($result) {
                $this->correct('重置成功！');
            } else {
                $this->mistake('重置失败！');
            }
            $this->mistake($result);
        } else {
            if (isset($result['code'])) {
                $this->mistake($result['msg']);
            }
            $this->mistake('接口无返回错误码！');
        }
    }
}









