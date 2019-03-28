<?php
/**
 * Created by PhpStorm.
 * Author: 紫云沫雪こ
 * Email:email1946367301@163.com
 * Date: 2019/3/18 0018
 * Time: 19:56
 */

namespace Connected\Controller;

use App\Connected\Model\CardModel;
use App\Connected\Model\CustomPurchseLogsModel;
use Home\Model\Customs;

class IndexController extends BaseController
{
    public function index()
    {
        //容错
    }

    /**
     * 获取卡信息
     * @param cardno 卡号 必填 notnull
     * @param sign 签名 必填 notnull
     *
     */
    public function getCardInfo()
    {
        $data = $this->checkKey($_POST);
        if (!isset($data['cardno'])) {
            $this->returnMsg('017', '请输入卡号');
        }
        $cardno = $data['cardno'];

        $cardModel = M('cards');

        $cardsdata = $cardModel->where(['cardno' => $cardno])->find();
        if (!$cardsdata) $this->returnMsg('018', '卡号不存在');
        $cardStatus = C('cardStatus');
        if ($cardsdata['status'] != 'Y') {
            $msgdata = $cardStatus[$cardsdata['status']];
            $this->returnMsg($msgdata['status'], $msgdata['msg']);
        }

        $customid = $cardsdata['customid'];//获取会员编号

        $customdata = $cardModel->alias('c')->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->field('cu.linktel,cu.namechinese,cu.personid,c.cardkind')->where(array("c.cardno" => $cardno))->find();
        if (!$customdata) {
            $this->returnMsg(array('status' => '019', 'codemsg' => '此会员不存在'));
        }
        if ($customdata['cardkind'] == '6688') {
            $flag = 1;
        } else {
            $flag = 0;
        }
        $data = [
            'linktel'     => $customdata['linktel'],
            'namechinese' => $customdata['namechinese'],
            'personid'    => $customdata['personid'],
            'flag'        => $flag
        ];
        $this->returnMsg('1', '正常返回', $data);
    }

    /**
     * 注册卡系统/购卡
     */
    public function registerCard()
    {
        $data = $this->checkKey($_POST);
        if (!isset($data['namechinese']) || $data['namechinese'] == '') return $this->returnMsg('03', '用户名不能为空');
        if (!isset($data['linktel']) || $data['linktel'] == '') $this->returnMsg('04', '手机号不能为空');
        if (!isset($data['personid']) || $data['personid'] == '') $this->returnMsg('05', '证件号不能为空');
        if (!isset($data['personidexdate']) || $data['personidexdate'] == '') $this->returnMsg('06', '证件期限不能为空');
        if (!isset($data['frontimg']) || $data['frontimg'] == '') $this->returnMsg('07', '身份证的正面不能为空');
        if (!isset($data['reserveimg']) || $data['reserveimg'] == '') $this->returnMsg('08', '身份证的反面不能为空');
        if (!isset($data['paypassword']) || $data['paypassword'] == '') $this->returnMsg('011', '支付密码不能为空');
        if ($data['paypassword'] != $data['confirmpaypassword']) $this->returnMsg('012', '两次输入支付密码不一致');
        if (!isset($data['panterid'])) $this->returnMsg('020', '请设置商户id');
        //检查用户是否存在
        $sl = preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $data['namechinese']);
        if (!$sl) {
            $this->returnMsg('013', "会员名必须为中文");
        }
        $result = M('customs')->where(['linktel' => $data['linktel'], 'personid' => $data['personid']])->find();
        //不存在时添加
        // //-----酒店不做身份证 电话唯一性验证
        if (!$result) {
            //执行注册
            $Customs  = new Customs();
            $customid = $this->getFieldNextNumber('customid');
            $result1  = $Customs->oracleInsert([
                'customid'       => $customid,
                'namechinese'    => $data['namechinese'],
                'linktel'        => $data['linktel'],
                'personid'       => $data['personid'],
                'personidexdate' => $data['personidexdate'],
                'frontimg'       => $data['frontimg'],
                'reserveimg'     => $data['reserveimg'],
                'personidtype'   => '身份证',
                'customlevel'    => "一般顾客"
            ]);
            if (!$result1) {
                $this->returnMsg('会员注册失败');
            }
        }
        //需要的操作的表 // custom_c 会员编号对应表 card 卡主表  card_active_logs 激活记录表  ecard_bind绑卡记录  card_purchase_logs 充值表 CUSTOM_PURCHASE_LOGS/购卡单
        //在此开卡的客户默认为一般顾客
        $customflag = 0;
        $customid   = $result['customid'];
        //模拟购卡
        $paytype = array('00' => '现金', '01' => '银行卡', '02' => '支票', '03' => '汇款', '04' => '网上支付', '05' => '转账', '06' => '内部转账', '07' => '赠送', '08' => '其他', '09' => '批量充值');
        //操作人员默认为ztladmin
        $userid                 = '0000000000001202';
        $userstr                = substr($userid, 12, 4);
        $purchaseid             = $this->getFieldNextNumber("purchaseid");
        $purchaseid             = $userstr . $purchaseid;
        $CustomPurchseLogsModel = new CustomPurchseLogsModel();
        $insterData             = [
            'customid'    => $customid,
            'purchaseid'  => $purchaseid,
            'placeddate'  => date('Ymd'),
            'paymenttype' => $paytype['08'],
            'userid'      => $userid,
            'amount'      => 0,
            'totalmoney'  => 0,
            'point'       => 0,
            'realamount'  => 0,
            'writeamount' => 0,
            'writenumber' => 0,
            'checkno'     => 0,
            'description' => '酒店管理在线注册开卡',
            'flag'        => 1,//无需审核
            'panterid'    => $data['panterid'],
            'tradeflag'   => 0,//0购卡1充值
            'customflag'  => $customflag,//默认一般顾客
            'termposno'   => '00000000',
            'placedtime'  => date('H:i:s')
        ];

        $result2 = $CustomPurchseLogsModel->oracleInsert($insterData);
        if (!$result2) {
            $this->returnMsg('014', '生成购卡单失败');
        }
        //生成单据成功


        $cardModel = new CardModel();

        /**
         * 项目暂停
         */


        //获取卡号

        //获取卡号

        //添加购卡记录与审核记录

        //开卡
    }

    /**
     * 绑定卡片
     */
    public function bindCard()
    {

    }

    /**
     * 取消绑定
     */
    public function removeBindCard()
    {

    }

    /**
     * 充值不限金额
     */
    public function recharge()
    {

    }

    /**
     * 转赠
     */
    public function transferring()
    {

    }

    /**
     * 至尊卡支付
     */
    public function supremePay()
    {

    }

    /**
     *至尊卡退款
     */
    public function supremeRefund()
    {

    }

    /**
     * 通宝付款
     */
    public function currencyPay()
    {

    }
}