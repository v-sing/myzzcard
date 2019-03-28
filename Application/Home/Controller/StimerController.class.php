<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class StimerController extends Controller {
    private $cash;//现金
    private $transfer;//转账
    private $cash_hz;//现金交易频次
    private $transfer_hz;//转账频次
    public function _initialize()
    {
        $this->cash = 50000;
        $this->transfer = 50000;
        $this->cash_hz = 20;
        $this->transfer_hz = 20;
//        $this->status = "'Y'";
//        $this->point = 0;
//        $this->cardk = "('6882','2081','6880','6666','6999','6668')";

    }
    //获取反洗钱数据 1.备付金商户信息表
    public function Anti_money()
    {
        header("content-type:text/html;charset=utf-8");

        $model = M('panters');
        $greenPanter = '00000483';
        $soonE = '00000243';
        $where['accounttype'] = 'B';
        $where['flag'] = '3';
        $where['status'] = '1';
        $where['panterid'] = array('not in', 'FFFFFFFF,EEEEEEEE');
        $where['revorkflg'] = 'N';
        $where['parent'] = ['not in', [$greenPanter, $soonE]];
//        $startdate='20180403';
//        $enddate='20180424';
//        $where['placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['placeddate']=date('Ymd',strtotime('-1day'));
        $field = 'panterid Join_code,namechinese Acc_name,nameenglish Acc_name1,placeddate Open_time,address,operatescope Operate,organizationcode Org_no,business License,timevalue License_deadline,conpername Man_name,conperbpno Id_no1,conperbtype Id_type1,period Id_deadline1,legalperson Rep_legal,conperbtype Id_type2,conperbpno Id_no2,period Id_deadline2,settlebankid Self_acc_no,settleaccountname Bank_acc_name';
        $panters_list = $model->where($where)->field($field)->order('panterid desc')->select();
//           echo $model->getLastSql();exit;
//            dump($panters_list);exit;
        if(empty($panters_list)){
            echo '没有数据';exit;
        }
        $array = array('prof_type' => '11', 'acc_type' => '12', 'bord_flag' => '11', 'web_info' => '@N', 'close_time' => '20301228', 'id_type' => '@N', 'id_no' => '@N', 'id_deadline' => '@N', 'nation1' => '@N', 'cst_sex' => '@N', 'occupation' => '@N', 'contact' => '@N', 'id_type3' => '@N', 'id_no3' => '@N', 'id_deadline3' => '@N', 'reg_amt' => '50000000.00', 'set_file' => '11', 'handler_name' => '@N', 'code' => 'RMB', 'Acc_type1' => '12', 'Nation' => 'CHN');
        foreach ($panters_list as $key => $val) {
            $panters_list[$key]['Prof_type'] = $array['prof_type'];
            $panters_list[$key]['Acc_type'] = $array['acc_type'];
            $panters_list[$key]['Bord_flag'] = $array['bord_flag'];
            $panters_list[$key]['Web_info'] = $array['web_info'];
            $panters_list[$key]['Close_time'] = $array['close_time'];
            $panters_list[$key]['Id_type'] = $array['id_type'];
            $panters_list[$key]['Id_no'] = $array['id_no'];
            $panters_list[$key]['Id_deadline'] = $array['id_deadline'];
            $panters_list[$key]['Nation1'] = $array['nation1'];
            $panters_list[$key]['Cst_sex'] = $array['cst_sex'];
            $panters_list[$key]['Occupation'] = $array['occupation'];
            $panters_list[$key]['Contact'] = $array['contact'];
            $panters_list[$key]['Id_type3'] = $array['id_type3'];
            $panters_list[$key]['Id_no3'] = $array['id_no3'];
            $panters_list[$key]['Id_deadline3'] = $array['id_deadline3'];
            $panters_list[$key]['Reg_amt'] = $array['reg_amt'];
            $panters_list[$key]['Set_file'] = $array['set_file'];
            $panters_list[$key]['Handler_name'] = $array['handler_name'];
            $panters_list[$key]['Code'] = $array['code'];
            $panters_list[$key]['Acc_type1'] = $array['Acc_type1'];
            $panters_list[$key]['Nation'] = $array['Nation'];
            unset($panters_list[$key]['numrow']);
            $data = json_encode($panters_list[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, 'http://anti.dylucas.com/index/conhis/api_add.html');
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/conhis/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);

        }
    }
    //记名预付卡信息
    public function Registered(){
        $model = new Model();
        $where['a.tradeflag'] = array('in', '0,1,2');
        $where['card.cardkind'] = array('not in', '6882,2081,6880,6666,6999,6668');
        $where['b.amount']=array('egt',1000);
        $where['ac.type'] = '00';
        $condition['b.amount'] = array('egt', 1000);
        $condition['ac.amount'] = array('egt', 1000);
        $condition['_logic'] = 'or';
        $where['_complex'] = $condition;
//        $startdate='20180403';
//        $enddate='20180424';
//        $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['a.placeddate']=date('Ymd',strtotime('-1day'));
        $field = 'c.namechinese Acc_name,b.cardno Card_no,a.tradeflag Prof_type,a.placeddate Date1,a.placedtime Time1,b.amount Amt,c.sex Cst_sex,c.career Occupation,c.personidtype Id_type,c.personid Id_no,c.personidexdate Id_deadline,c.linktel Contact,c.residaddress Address,a.paymenttype Tsf_flag,a.termposno Mac_info,c.aplysrc attribute';
//
        $sell_list = $model->table('card_active_logs ')->alias('ca')
            ->join('cards card on card.cardno=ca.cardno')
            ->join('left join users u on u.userid=ca.userid')
            ->join('customs_c f on f.cid=card.customid')
            ->join('customs c on c.customid=f.customid')
            ->join('left join account ac on ac.customid=f.cid')
            ->join('left join panters p on p.panterid=ca.panterid')
            ->join('card_purchase_logs b on ca.cardno=b.cardno')
            ->join('custom_purchase_logs a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();
        if(empty($sell_list)){
            echo '没有数据';exit;
        }
        $arr = array();
        foreach ($sell_list as $key => $val) {
            $arr[$val['card_no']] = $val;
        }
        foreach ($arr as $k => $v) {
            $arr[$k]['Date'] = $v['date1'];
            $arr[$k]['Time'] = date('His', strtotime($v['time1']));
            if ($arr[$k]['prof_type'] = 1) {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 12;
            } else {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 11;
            }
            if ($arr[$k]['tsf_flag'] = '现金' or $arr[$k]['tsf_flag'] = '00') {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 10;
            } else {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 11;
            }
            if ($arr[$k]['cst_sex'] = '男') {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 11;
            } else {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 12;
            }
            if ($arr[$k]['id_type'] = '身份证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 11;
            } elseif ($arr[$k]['id_type'] = '军官证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 12;
            } elseif ($arr[$k]['id_type'] = '护照') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 13;
            } else {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 19;
            }
            if ($arr[$k]['id_deadline'] = 'null') {
                unset($arr[$k]['id_deadline']);
                $arr[$k]['Id_deadline'] = '99991231';
            }
            if($arr[$k]['attribute']=='J'){
                $arr[$k]['attribute'] = '2';
            }else{
                $arr[$k]['attribute'] = '1';
            }
            unset($arr[$k]['date1']);
            unset($arr[$k]['time1']);
        }
//            echo $model->getLastSql();exit;
        $array = array('Acc_type' => '11', 'Operate' => '@N', 'Org_no' => '@N', 'Set_file' => '@N', 'License' => '@N', 'License_deadline' => '@N', 'Rep_legal' => '@N', 'Id_type2' => '@N', 'Id_no2' => '@N', 'Id_deadline2' => '@N', 'Handler_name' => '@N', 'Id_type3' => '@N', 'Id_no3' => '@N', 'Id_deadline3' => '@N', 'Sales_name' => '@N', 'Cst_sex2' => '@N', 'Nation2' => '@N', 'Occupation2' => '@N', 'Id_type4' => '@N', 'Id_no4' => '@N', 'Id_deadline4' => '@N', 'Contact2' => '@N', 'Address2' => '@N', 'Sales_flag' => '11', 'Acc_no' => '@N', 'Nation' => 'CHN');
        foreach ($arr as $key => $val) {
            $arr[$key]['Acc_type'] = $array['Acc_type'];
            $arr[$key]['Operate'] = $array['Operate'];
            $arr[$key]['Org_no'] = $array['Org_no'];
            $arr[$key]['Set_file'] = $array['Set_file'];
            $arr[$key]['License'] = $array['License'];
            $arr[$key]['License_deadline'] = $array['License_deadline'];
            $arr[$key]['Rep_legal'] = $array['Rep_legal'];
            $arr[$key]['Id_no2'] = $array['Id_no2'];
            $arr[$key]['Id_type2'] = $array['Id_type2'];
            $arr[$key]['Id_deadline2'] = $array['Id_deadline2'];
            $arr[$key]['Handler_name'] = $array['Handler_name'];
            $arr[$key]['Id_type3'] = $array['Id_type3'];
            $arr[$key]['Id_no3'] = $array['Id_no3'];
            $arr[$key]['Id_deadline3'] = $array['Id_deadline3'];
            $arr[$key]['Sales_name'] = $array['Sales_name'];
            $arr[$key]['Cst_sex2'] = $array['Cst_sex2'];
            $arr[$key]['Nation2'] = $array['Nation2'];
            $arr[$key]['Occupation2'] = $array['Occupation2'];
            $arr[$key]['Id_type4'] = $array['Id_type4'];
            $arr[$key]['Id_no4'] = $array['Id_no4'];
            $arr[$key]['Id_deadline4'] = $array['Id_deadline4'];
            $arr[$key]['Contact2'] = $array['Contact2'];
            $arr[$key]['Address2'] = $array['Address2'];
            $arr[$key]['Sales_flag'] = $array['Sales_flag'];
            $arr[$key]['Acc_no'] = $array['Acc_no'];
            $arr[$key]['Nation'] = $array['Nation'];
            unset($arr[$key]['numrow']);
            $data = json_encode($arr[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, 'http://anti.dylucas.com/index/payrec/api_add.html');
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/payrec/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);
        }
    }
    //记名信息 充值购卡小于1000但卡余额大于等于1000的
    public function Register(){
        $model = new Model();
        $where['a.tradeflag'] = array('in', '0,1,2');
        $where['card.cardkind'] = array('not in', '6882,2081,6880,6666,6999,6668');
        $where['b.amount']=array('lt',1000);
        $where['ac.amount']=array('egt',1000);
        $where['ac.type'] = '00';
//        $startdate='20180403';
//        $enddate='20180424';
//        $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['a.placeddate']=date('Ymd',strtotime('-1day'));
        $field = 'c.namechinese Acc_name,b.cardno Card_no,a.tradeflag Prof_type,a.placeddate Date1,a.placedtime Time1,b.amount Amt,c.sex Cst_sex,c.career Occupation,c.personidtype Id_type,c.personid Id_no,c.personidexdate Id_deadline,c.linktel Contact,c.residaddress Address,a.paymenttype Tsf_flag,a.termposno Mac_info,c.aplysrc attribute';
//
        $sell_list = $model->table('card_active_logs ')->alias('ca')
            ->join('cards card on card.cardno=ca.cardno')
            ->join('left join users u on u.userid=ca.userid')
            ->join('customs_c f on f.cid=card.customid')
            ->join('customs c on c.customid=f.customid')
            ->join('left join account ac on ac.customid=f.cid')
            ->join('left join panters p on p.panterid=ca.panterid')
            ->join('card_purchase_logs b on ca.cardno=b.cardno')
            ->join('custom_purchase_logs a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();
        if(empty($sell_list)){
            echo '没有数据';exit;
        }
        $arr = array();
        foreach ($sell_list as $key => $val) {
            $arr[$val['card_no']] = $val;
        }
        foreach ($arr as $k => $v) {
            $arr[$k]['Date'] = $v['date1'];
            $arr[$k]['Time'] = date('His', strtotime($v['time1']));
            if ($arr[$k]['prof_type'] = 1) {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 12;
            } else {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 11;
            }
            if ($arr[$k]['tsf_flag'] = '现金' or $arr[$k]['tsf_flag'] = '00') {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 10;
            } else {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 11;
            }
            if ($arr[$k]['cst_sex'] = '男') {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 11;
            } else {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 12;
            }
            if ($arr[$k]['id_type'] = '身份证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 11;
            } elseif ($arr[$k]['id_type'] = '军官证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 12;
            } elseif ($arr[$k]['id_type'] = '护照') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 13;
            } else {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 19;
            }
            if ($arr[$k]['id_deadline'] = 'null') {
                unset($arr[$k]['id_deadline']);
                $arr[$k]['Id_deadline'] = '99991231';
            }
            if($arr[$k]['attribute']='J'){
                $arr[$k]['attribute'] = '2';
            }
            unset($arr[$k]['date1']);
            unset($arr[$k]['time1']);
        }
//            echo $model->getLastSql();exit;
        $array = array('Acc_type' => '11', 'Operate' => '@N', 'Org_no' => '@N', 'Set_file' => '@N', 'License' => '@N', 'License_deadline' => '@N', 'Rep_legal' => '@N', 'Id_type2' => '@N', 'Id_no2' => '@N', 'Id_deadline2' => '@N', 'Handler_name' => '@N', 'Id_type3' => '@N', 'Id_no3' => '@N', 'Id_deadline3' => '@N', 'Sales_name' => '@N', 'Cst_sex2' => '@N', 'Nation2' => '@N', 'Occupation2' => '@N', 'Id_type4' => '@N', 'Id_no4' => '@N', 'Id_deadline4' => '@N', 'Contact2' => '@N', 'Address2' => '@N', 'Sales_flag' => '11', 'Acc_no' => '@N', 'Nation' => 'CHN');
        foreach ($arr as $key => $val) {
            $arr[$key]['Acc_type'] = $array['Acc_type'];
            $arr[$key]['Operate'] = $array['Operate'];
            $arr[$key]['Org_no'] = $array['Org_no'];
            $arr[$key]['Set_file'] = $array['Set_file'];
            $arr[$key]['License'] = $array['License'];
            $arr[$key]['License_deadline'] = $array['License_deadline'];
            $arr[$key]['Rep_legal'] = $array['Rep_legal'];
            $arr[$key]['Id_no2'] = $array['Id_no2'];
            $arr[$key]['Id_type2'] = $array['Id_type2'];
            $arr[$key]['Id_deadline2'] = $array['Id_deadline2'];
            $arr[$key]['Handler_name'] = $array['Handler_name'];
            $arr[$key]['Id_type3'] = $array['Id_type3'];
            $arr[$key]['Id_no3'] = $array['Id_no3'];
            $arr[$key]['Id_deadline3'] = $array['Id_deadline3'];
            $arr[$key]['Sales_name'] = $array['Sales_name'];
            $arr[$key]['Cst_sex2'] = $array['Cst_sex2'];
            $arr[$key]['Nation2'] = $array['Nation2'];
            $arr[$key]['Occupation2'] = $array['Occupation2'];
            $arr[$key]['Id_type4'] = $array['Id_type4'];
            $arr[$key]['Id_no4'] = $array['Id_no4'];
            $arr[$key]['Id_deadline4'] = $array['Id_deadline4'];
            $arr[$key]['Contact2'] = $array['Contact2'];
            $arr[$key]['Address2'] = $array['Address2'];
            $arr[$key]['Sales_flag'] = $array['Sales_flag'];
            $arr[$key]['Acc_no'] = $array['Acc_no'];
            $arr[$key]['Nation'] = $array['Nation'];
            unset($arr[$key]['numrow']);
            $data = json_encode($arr[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, 'http://anti.dylucas.com/index/payrec/api_add.html');
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/payrec/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);
        }
    }

//不记名
    public function No_registered(){
        $model=new Model();
        $where['a.tradeflag']=array('in','0,1,2');
        $where['card.cardkind']=array('not in','6882,2081,6880,6666,6999,6668');
        $where['ac.type']='00';
        $where['b.amount']=array('lt',1000);
        $where['ac.amount']=array('lt',1000);
//        $startdate='20180401';
//        $enddate='20180424';
//        $where['a.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['a.placeddate']=date('Ymd',strtotime('-1day'));
        $field='b.cardno Card_no,a.tradeflag Prof_type,a.placeddate Date1,a.placedtime Time1,b.amount Amt,c.namechinese Sales_name,c.sex Cst_sex,c.career Occupation,c.personidtype Id_type,c.personid Id_no,c.personidexdate Id_deadline,c.linktel Contact,c.residaddress Address,a.paymenttype Tsf_flag,a.termposno Mac_info';
        $sell_list = $model->table('card_active_logs ')->alias('ca')
            ->join('cards card on card.cardno=ca.cardno')
            ->join('left join users u on u.userid=ca.userid')
            ->join('customs_c f on f.cid=card.customid')
            ->join('customs c on c.customid=f.customid')
            ->join('left join account ac on ac.customid=f.cid')
            ->join('left join panters p on p.panterid=ca.panterid')
            ->join('card_purchase_logs b on ca.cardno=b.cardno')
            ->join('custom_purchase_logs a on a.purchaseid=b.purchaseid')
            ->where($where)->field($field)->order('b.card_purchaseid desc')->select();
//            echo $model->getLastSql();exit;
        if(empty($sell_list)){
            echo '没有数据';exit;
        }
        $arr = array();
        foreach ($sell_list as $key => $val) {
            $arr[$val['card_no']] = $val;
        }
        foreach ($arr as $k => $v) {
            $arr[$k]['Date'] = $v['date1'];
            $arr[$k]['Time'] = date('His', strtotime($v['time1']));
            if ($arr[$k]['prof_type'] = 1) {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 12;
            } else {
                unset($arr[$k]['prof_type']);
                $arr[$k]['Prof_type'] = 11;
            }
            if ($arr[$k]['tsf_flag'] = '现金' or $arr[$k]['tsf_flag'] = '00') {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 10;
            } else {
                unset($arr[$k]['tsf_flag']);
                $arr[$k]['Tsf_flag'] = 11;
            }
            if ($arr[$k]['cst_sex'] = '男') {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 11;
            } else {
                unset($arr[$k]['cst_sex']);
                $arr[$k]['Cst_sex'] = 12;
            }
            if ($arr[$k]['id_type'] = '身份证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 11;
            } elseif ($arr[$k]['id_type'] = '军官证') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 12;
            } elseif ($arr[$k]['id_type'] = '护照') {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 13;
            } else {
                unset($arr[$k]['id_type']);
                $arr[$k]['Id_type'] = 19;
            }
            unset($arr[$k]['date1']);
            unset($arr[$k]['time1']);
        }
//            echo $model->getLastSql();exit;
        $array = array('Sales_flag' => '12', 'Acc_no' => '@N','Nation'=>'CHN');
        foreach ($arr as $key => $val) {
            $arr[$key]['Sales_flag'] = $array['Sales_flag'];
            $arr[$key]['Acc_no'] = $array['Acc_no'];
            $arr[$key]['Nation'] = $array['Nation'];
            unset($arr[$key]['numrow']);
            $data = json_encode($arr[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
//            curl_setopt($curl, CURLOPT_URL, 'http://anti.dylucas.com/index/paynorec/api_add.html');
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/paynorec/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);


        }

    }
// 预付卡商户交易流水
    public function Trading_flow(){
        $model=new Model();
        $greenPanter='00000483';
        $soonE='00000243';
        $where['p.accounttype'] = 'B';
        $where['p.flag'] = '3';
        $where['p.panterid']=array('not in','FFFFFFFF,EEEEEEEE');
        $where['p.revorkflg'] ='N';
        $where['p.status']=1;
        $where['p.parent']=['not in',[$greenPanter,$soonE]];
        $where['t.flag']=0;
        $where['t.tradetype']=array('in','00,13,17,21,07');
        $where['t.tradeamount']=array('neq',0);
//        $startdate='20180401';
//        $enddate='20180424';
//        $where['t.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['card.cardkind']=array('in','6888,2336,6886,6889,6688');
        $where['t.placeddate']=date('Ymd',strtotime('-1day'));
        $field ='p.namechinese Self_acc_name,p.nameenglish Self_acc_name1,p.settlebankid Self_acc_no,p.settleaccountname Self_bank_name,p.settlebankname Self_bank,pl.panterid Join_code,p.panterid Join_code1,t.placeddate Date1,t.placedtime Time1,t.tradeamount Amt,cu.namechinese Part_acc_name,t.cardno Part_acc_no,t.tradememo Purpose,t.tradeid Trans_no';
        $consume_list = $model->table('trade_wastebooks')->alias('t')
            ->join('left join __PANTERS__ p on t.panterid=p.panterid')
            ->join('left join __CARDS__ card on card.cardno = t.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ cu on cu.customid = cc.customid')
            ->join('left join __PANTERS__ pl on card.panterid = pl.panterid')
            ->where($where)->field($field)->order('t.cardno desc')->select();
        if(empty($consume_list)){
            echo '没有数据';exit;
        }
        $array = array('Lend_flag' => '10');
        foreach ($consume_list as $key => $val) {
            $consume_list[$key]['Date'] = $val['date1'];
            $consume_list[$key]['Time'] = date('His', strtotime($val['time1']));
            $consume_list[$key]['Lend_flag'] = $array['Lend_flag'];
            unset($consume_list[$key]['date1']);
            unset($consume_list[$key]['time1']);
            unset($consume_list[$key]['numrow']);
            $data = json_encode($consume_list[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/paytxn/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);

        }

    }
//商户风险等级划分记录
    public function Panter_grade(){
        $model=new model;
        $greenPanter='00000483';
        $soonE='00000243';
        $where['p.accounttype'] = 'B';
        $where['p.flag'] = '3';
        $where['p.panterid']=array('not in','FFFFFFFF,EEEEEEEE');
        $where['p.revorkflg'] ='N';
        $where['p.status']=1;
        $where['p.parent']=['not in',[$greenPanter,$soonE]];
//        $startdate='20180403';
//        $enddate='20180424';
//        $where['tp.statdate']=array(array('egt',$startdate),array('elt',$enddate));
            $where['tp.statdate']=date('Ymd',strtotime('-1day'));
        $field='tp.tradeamount Amount,p.namechinese Acc_name,p.panterid Join_code,p.placeddate Date1,p.business Id_no,tp.statdate Time1,tp.tradequantity Second';
        $p_list=$model->table('trade_panter_day_books')->alias('tp')
            ->join('__PANTERS__ p on p.panterid=tp.panterid')
            ->where($where)->field($field)->order('tp.statdate desc')->select();
        if(empty($p_list)){
            echo '没有数据';exit;
        }
//            echo $model->getLastSql();exit;
        $array = array('Prof_type' => '11',);
        foreach ($p_list as $key => $val) {
            $p_list[$key]['Date'] = $val['date1'];
            $p_list[$key]['Time'] = $val['time1'];
//                $p_list[$key]['Time'] = date('His', strtotime($val['time1']));
            $p_list[$key]['Prof_type'] = $array['Prof_type'];
            unset($p_list[$key]['date1']);
            unset($p_list[$key]['time1']);
            unset($p_list[$key]['numrow']);
            $r=8.00+mt_rand()/mt_getrandmax()*(10-8.00);
            $r = sprintf("%.2f", $r);
            $a=4.00+mt_rand()/mt_getrandmax()*(7-4.00);
            $a = sprintf("%.2f", $a);
            $b =0.00+mt_rand()/mt_getrandmax()*(3-0.00);
            $b = sprintf("%.2f", $b);
            if($p_list[$key]['Amount']>=2000000 and $p_list[$key]['Second'] >=100000){
                $p_list[$key]['Risk'] = '10';
                $p_list[$key]['Score'] =floatval($r);
                $p_list[$key]['Norm'] ='日交易次数频繁';
            }elseif((2000000< $p_list[$key]['Amount'] and $p_list[$key]['Amount'] <1000000) and
                (50000<=$p_list[$key]['Second'] and $p_list[$key]['Second']<100000)){
                $p_list[$key]['Risk'] = '11';
                $p_list[$key]['Score'] = floatval($a);
                $p_list[$key]['Norm'] ='日交易次数适中';
            }else{
                $p_list[$key]['Risk'] = '12';
                $p_list[$key]['Score'] =floatval($b);
                $p_list[$key]['Norm'] ='日交易次数正常';
            }
            unset($p_list[$key]['amount']);
            unset($p_list[$key]['second']);
            $data = json_encode($p_list[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/conrisk/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);

        }

    }
//客户风险等级划分记录
    public function customs_grade(){
        $model=new model;
        $where['cpl.tradeflag'] = 0;
        $where['tw.tradeamount']=array('gt',0);
        $where['c.namechinese'] = array('neq','NULL');
        $where['card.cardkind'] = array('not in', '6882,2081,6880,6666,6999,6668');
//        $startdate='20180401';
//        $enddate='20180424';
//        $where['tw.placeddate']=array(array('egt',$startdate),array('elt',$enddate));
        $where['tw.placeddate']=date('Ymd',strtotime('-1day'));
        $field='tw.tradeamount Amount,c.namechinese Acc_name,c.customid Acc_no,cpl.placeddate Date1,c.personid Id_no,tw.placeddate Time1';
        $cou_list = $model->table('trade_wastebooks')->alias('tw')
            ->join('left join __CARDS__ card on card.cardno=tw.cardno')
            ->join('left join __CUSTOMS_C__ cc on cc.cid=card.customid')
            ->join('left join __CUSTOMS__ c on c.customid=cc.customid')
            ->join('left join __CARD_PURCHASE_LOGS__ cp on card.cardno=cp.cardno')
            ->join('left join __CUSTOM_PURCHASE_LOGS__ cpl on cpl.purchaseid=cp.purchaseid')
            ->where($where)->field($field)->order("tw.placeddate desc")->select();
//            echo $model->getLastSql();exit;
        $array = array('Prof_type' => '11');
        foreach ($cou_list as $key => $val) {
            $cou_list[$key]['Date'] = $val['date1'];
            $cou_list[$key]['Time'] = $val['time1'];
//                $cou_list[$key]['Time'] = date('His', strtotime($val['time1']));
            $cou_list[$key]['Prof_type'] = $array['Prof_type'];
            unset($cou_list[$key]['date1']);
            unset($cou_list[$key]['time1']);
            unset($cou_list[$key]['numrow']);
            $r=8.00+mt_rand()/mt_getrandmax()*(10-8.00);
            $r = sprintf("%.2f", $r);
            $a=4.00+mt_rand()/mt_getrandmax()*(7-4.00);
            $a = sprintf("%.2f", $a);
            $b =0.00+mt_rand()/mt_getrandmax()*(3-0.00);
            $b = sprintf("%.2f", $b);
            if($cou_list[$key]['Amount']>=100000){
                $cou_list[$key]['Risk'] = '10';
                $cou_list[$key]['Score'] =floatval($r);
                $cou_list[$key]['Norm'] ='日交易金额超过限定金额';
            }elseif(50000<= $cou_list[$key]['Amount'] and $cou_list[$key]['Amount'] <100000){
                $cou_list[$key]['Risk'] = '11';
                $cou_list[$key]['Score'] = floatval($a);
                $cou_list[$key]['Norm'] ='交易警告';
            }else{
                $cou_list[$key]['Risk'] = '12';
                $cou_list[$key]['Score'] =floatval($b);
                $cou_list[$key]['Norm'] ='交易正常';
            }
            unset($cou_list[$key]['amount']);
            $data = json_encode($cou_list[$key]);
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/clirisk/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);
        }
//            dump($cou_list);exit;

    }
//可疑交易报告明细
    public function Suspicious(){
        $date = date('Ymd',strtotime('-1day'));
//        $startdate='20180401';
//        $enddate='20180424';
//        $date =array(array('egt',$startdate),array('elt',$enddate));
        $sus = D("Stimer");
        $charge_arr = $sus->getCharger($this->transfer, $this->transfer_hz, null, $date);
//            dump($charge_arr);exit;
        $arr = array();
        $array = array('RINM' => '郑州建业至尊商务服务有限公司','FIRC'=>'410105','FICD'=>'@N','RFSG'=>'1','ORXN'=>'可疑交易报告初次','SSTM'=>'01','STCR'=>'01','SSDS'=>'交易金额超出限制或交易次数频繁且次数已超出限制','UDSI'=>'ZZ01','SCTN'=>'1','TTNM'=>'1','SMID'=>'@N','CRNM'=>'@N','CRIT'=>'@N','CRID'=>'@N','CBAT'=>'@N','CBAC'=>'@N','CABM'=>'@N','CTAT'=>'@N','CPIN'=>'郑州建业至尊商务服务有限公司客户备付金','CPBA'=>'76190154800002519','CPBN'=>'上海浦东发展银行股份有限公司郑州郑东新区支行 ','TSDR'=>'02','CRPP'=>'备付金','CRTP'=>'RMB','TSMI'=>'@N','TCIT'=>'21','TCAT'=>'03','TCTT'=>'01','TCPN'=>'郑州建业至尊商务服务有限公司客户备付金','TCPA'=>'76190154800002519','TPBN'=>'上海浦东发展银行股份有限公司郑州郑东新区支行','TMNM'=>'预付卡备付金充值','BPTC'=>'@N');
        foreach ($charge_arr as $key => $val) {
            $arr[$key]['CTIF'] = $val['customid'];
            $arr[$key]['CTNM'] = $val['namechinese'];
            $arr[$key]['CITP'] = $val['personidtype'];
            $arr[$key]['CTID'] = $val['personid'];
            $arr[$key]['CTAR'] = $val['residaddress'];
            $arr[$key]['CCTL'] = $val['linktel'];
            $arr[$key]['CEML'] = $val['email'];
            if($val['personidtype']='身份证'){
                $arr[$key]['CITP']='11';
            }else{
                $arr[$key]['CITP']='12';
            }
            if($val['career']='司机' or $val['career']='技术工人'){
                $arr[$key]['CTVC']='1A';
            }elseif($val['career']='公务员'or $val['career']='企业高管'){
                $arr[$key]['CTVC']='1B';
            }elseif($val['career']='医生' or $val['career']='护士'){
                $arr[$key]['CTVC']='1E';
            }elseif($val['career']='公司职员' or $val['career']='律师'){
                $arr[$key]['CTVC']='1D';
            }else{
                $arr[$key]['CTVC']='1H';
            }
            $arr[$key]['CTAC'] = $val['cardno'];
            $arr[$key]['CTIP'] = $val['ip'];
            $arr[$key]['TSTM'] = $val['placeddate'].date('His', strtotime($val['placedtime']));
            if($val['paymenttype']='00' or $val['paymenttype']='现金'){
                $arr[$key]['CTTP'] ='0500';
            }else{
                $arr[$key]['CTTP'] ='0501';
            }
            $arr[$key]['CRAT'] = $val['amount'];
            $arr[$key]['TCNM'] = $val['pname'];
            $arr[$key]['TCID'] = str_replace("-",'',$val['po']);
            $arr[$key]['TCBA'] = $val['ps'];
            $arr[$key]['TCBN'] = $val['psb'];
            $arr[$key]['TCTA'] = $val['ps'];
            $arr[$key]['TCIP'] = $val['ip'];
            $arr[$key]['PMTC'] = $val['purchaseid'];
            $arr[$key]['TICD'] = $val['purchaseid'];
            $arr[$key]['RINM'] = $array['RINM'];
            $arr[$key]['FIRC'] = $array['FIRC'];
            $arr[$key]['FICD'] = $array['FICD'];
            $arr[$key]['RFSG'] = $array['RFSG'];
            $arr[$key]['SSTM'] = $array['SSTM'];
            $arr[$key]['STCR'] = $array['STCR'];
            $arr[$key]['SSDS'] = $array['SSDS'];
            $arr[$key]['UDSI'] = $array['UDSI'];
            $arr[$key]['SCTN'] = $array['SCTN'];
            $arr[$key]['TTNM'] = $array['TTNM'];
            $arr[$key]['SMID'] = $array['SMID'];
            $arr[$key]['CRNM'] = $array['CRNM'];
            $arr[$key]['CRIT'] = $array['CRIT'];
            $arr[$key]['CRID'] = $array['CRID'];
//                $arr[$key]['STIF'] = $array['STIF'];
            $arr[$key]['CBAT'] = $array['CBAT'];
            $arr[$key]['CBAC'] = $array['CBAC'];
            $arr[$key]['CABM'] = $array['CABM'];
            $arr[$key]['CTAT'] = $array['CTAT'];
            $arr[$key]['CPIN'] = $array['CPIN'];
            $arr[$key]['CPBA'] = $array['CPBA'];
            $arr[$key]['CPBN'] = $array['CPBN'];
            $arr[$key]['TSDR'] = $array['TSDR'];
            $arr[$key]['CRPP'] = $array['CRPP'];
            $arr[$key]['CRTP'] = $array['CRTP'];
            $arr[$key]['TSMI'] = $array['TSMI'];
            $arr[$key]['TCIT'] = $array['TCIT'];
            $arr[$key]['TCAT'] = $array['TCAT'];
            $arr[$key]['TCTT'] = $array['TCTT'];
            $arr[$key]['TCPN'] = $array['TCPN'];
            $arr[$key]['TCPA'] = $array['TCPA'];
            $arr[$key]['TPBN'] = $array['TPBN'];
            $arr[$key]['TMNM'] = $array['TMNM'];
            $arr[$key]['BPTC'] = $array['BPTC'];
            $arr[$key]['ORXN'] = $array['ORXN'];
//                dump($arr);exit;
        }
//            dump($arr);exit;

        $trade_arr = $sus->getTrader($this->transfer, $this->transfer_hz, null, $date);
        $trade_queue = array();
        $array1 = array('RINM' => '郑州建业至尊商务服务有限公司','FIRC'=>'410105','FICD'=>'NULL','RFSG'=>'1','ORXN'=>'可疑交易报告初次','SSTM'=>'01','STCR'=>'01','SSDS'=>'交易金额超出限制或交易次数频繁且次数已超出限制','UDSI'=>'ZZ01','SCTN'=>'1','TTNM'=>'1','SMID'=>'@N','CRNM'=>'@N','CRIT'=>'@N','CRID'=>'@N','CBAT'=>'@N','CBAC'=>'@N','CABM'=>'@N','CTAT'=>'@N','CPIN'=>'郑州建业至尊商务服务有限公司客户备付金','CPBA'=>'76190154800002519','CTIP'=>'192.168.0.0.66','CTTP'=>'0600','CPBN'=>'上海浦东发展银行股份有限公司郑州郑东新区支行 ','TSDR'=>'02','CRPP'=>'预付卡消费','CRTP'=>'RMB','TSMI'=>'@N','TCIT'=>'21','TCAT'=>'03','TCTT'=>'01','TCPN'=>'郑州建业至尊商务服务有限公司客户备付金','TCPA'=>'76190154800002519','TPBN'=>'上海浦东发展银行股份有限公司郑州郑东新区支行','TCIP'=>'192.168.0.0.266','TMNM'=>'预付卡消费交易',);
        foreach ($trade_arr as $k =>$v){
            $trade_queue[$k]['CTIF'] = $v['customid'];
            $trade_queue[$k]['CTNM'] = $v['namechinese'];
            $trade_queue[$k]['CITP'] = $v['personidtype'];
            $trade_queue[$k]['CTID'] = $v['personid'];
            $trade_queue[$k]['CTAR'] = $v['residaddress'];
            $trade_queue[$k]['CCTL'] = $v['linktel'];
            $trade_queue[$k]['CEML'] = $v['email'];
            if($v['personidtype']='身份证'){
                $trade_queue[$k]['CITP']='11';
            }else{
                $trade_queue[$k]['CITP']='12';
            }
            if($v['career']='司机' or $v['career']='技术工人'){
                $trade_queue[$k]['CTVC']='1A';
            }elseif($v['career']='公务员'or $v['career']='企业高管'){
                $trade_queue[$k]['CTVC']='1B';
            }elseif($v['career']='医生' or $v['career']='护士'){
                $trade_queue[$k]['CTVC']='1E';
            }elseif($v['career']='公司职员' or $v['career']='律师'){
                $trade_queue[$k]['CTVC']='1D';
            }else{
                $trade_queue[$k]['CTVC']='1H';
            }
            $trade_queue[$k]['CTAC'] =$v['cardno'];
            $trade_queue[$k]['TSTM'] = $v['placeddate'].date('His', strtotime($v['placedtime']));
            $trade_queue[$k]['CRAT'] = $v['amount'];
            $trade_queue[$k]['TCNM'] = $v['pname'];
            $trade_queue[$k]['TCID'] = str_replace("-",'',$v['po']);
            $trade_queue[$k]['TCBA'] = $v['ps'];
            $trade_queue[$k]['TCBN'] = $v['psb'];
            $trade_queue[$k]['TCTA'] = $v['ps'];
            $trade_queue[$k]['BPTC'] = $v['eorderid'];
            $trade_queue[$k]['PMTC'] = $v['tradeid'];
            $trade_queue[$k]['TICD'] = $v['tradeid'];
            $trade_queue[$k]['RINM'] = $array1['RINM'];
            $trade_queue[$k]['CRID'] = $array1['CRID'];
            $trade_queue[$k]['FIRC'] = $array1['FIRC'];
            $trade_queue[$k]['FICD'] = $array1['FICD'];
            $trade_queue[$k]['RFSG'] = $array1['RFSG'];
            $trade_queue[$k]['ORXN'] = $array1['ORXN'];
            $trade_queue[$k]['SSTM'] = $array1['SSTM'];
            $trade_queue[$k]['STCR'] = $array1['STCR'];
            $trade_queue[$k]['SSDS'] = $array1['SSDS'];
            $trade_queue[$k]['UDSI'] = $array1['UDSI'];
            $trade_queue[$k]['SCTN'] = $array1['SCTN'];
            $trade_queue[$k]['TTNM'] = $array1['TTNM'];
            $trade_queue[$k]['SMID'] = $array1['SMID'];
            $trade_queue[$k]['CRNM'] = $array1['CRNM'];
            $trade_queue[$k]['CRIT'] = $array1['CRIT'];
            $trade_queue[$k]['CBAT'] = $array1['CBAT'];
            $trade_queue[$k]['CBAC'] = $array1['CBAC'];
            $trade_queue[$k]['CABM'] = $array1['CABM'];
            $trade_queue[$k]['CTAT'] = $array1['CTAT'];
            $trade_queue[$k]['CPIN'] = $array1['CPIN'];
            $trade_queue[$k]['CPBA'] = $array1['CPBA'];
            $trade_queue[$k]['CTIP'] = $array1['CTIP'];
            $trade_queue[$k]['CTTP'] = $array1['CTTP'];
            $trade_queue[$k]['CPBN'] = $array1['CPBN'];
            $trade_queue[$k]['TSDR'] = $array1['TSDR'];
            $trade_queue[$k]['CRPP'] = $array1['CRPP'];
            $trade_queue[$k]['CRTP'] = $array1['CRTP'];
            $trade_queue[$k]['TSMI'] = $array1['TSMI'];
            $trade_queue[$k]['TCIT'] = $array1['TCIT'];
            $trade_queue[$k]['TCAT'] = $array1['TCAT'];
            $trade_queue[$k]['TCTT'] = $array1['TCTT'];
            $trade_queue[$k]['TCPN'] = $array1['TCPN'];
            $trade_queue[$k]['TCPA'] = $array1['TCPA'];
            $trade_queue[$k]['TPBN'] = $array1['TPBN'];
            $trade_queue[$k]['TCIP'] = $array1['TCIP'];
            $trade_queue[$k]['TMNM'] = $array1['TMNM'];
//                dump($trade_queue);exit;
        }
        $da = array_merge($arr, $trade_queue);
//        dump($da);exit;
        foreach ($da as $key=>$val){
            $data = json_encode($da[$key]);
//           dump($data);exit;
            $post_data = $this->aes_encrypt($data,'4b47a16ec460838655af05809252219d97b456aa');
            //dump($post_data);exit;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://10.1.1.81:8080/index/susreport/api_add.html');
            //设置头文件的信息作为数据流输出
//            curl_setopt($curl, CURLOPT_HEADER, 1);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $res = array(
                "data" => $post_data
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $res);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            //显示获得的数据
            var_dump($data);

        }

    }
    /**
     * AES加密
     *
     * @param string $input 要加密的数据
     * @param string $key 加密KEY
     * @return string
     */
    function aes_encrypt($input, $key)
    {
//        dump($input);exit;
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);

        $data = openssl_encrypt($input, $cipher, $key, OPENSSL_RAW_DATA, $iv);
//        echo $data;exit;
        return base64_encode($iv . $data);
    }

}