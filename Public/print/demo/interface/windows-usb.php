<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Install the printer using USB printing support, and the "Generic / Text Only" driver,
 * then share it (you can use a firewall so that it can only be seen locally).
 *
 * Use a WindowsPrintConnector with the share name to print.
 *
 * Troubleshooting: Fire up a command prompt, and ensure that (if your printer is shared as
 * "Receipt Printer), the following commands work:
 *
 *  echo "Hello World" > testfile
 *  copy testfile "\\%COMPUTERNAME%\Receipt Printer"
 *  del testfile
 */
date_default_timezone_set('PRC'); //设置中国时区 
try {
    // Enter the share name for your USB printer here（在这里输入您的USB打印机的共享名 ）
    $connector = new WindowsPrintConnector("GP-L80180 Series");
    $printer = new Printer($connector);
    /* Initialize */
    $printer -> initialize();

    $type=$_GET['type'];
    $panterid=$_GET['panterid'];
    $pantername= $_GET['pantername'];
    $date=date("Y-m-d H:i:s",time());
    if($type!=1){
        $pantername=json_decode(decode($pantername));
    }
    $pantername=json_decode(decode($pantername));
    if($type==1){
        $name='日结算';
    }elseif($type==2){
        $name='已点菜品';
    }elseif($type==3){
        $name='散装菜品';
    }else{
        $name='退货';
    }
    $printer->setPrintLeftMargin(115);
    setGoodsTitles($name,$pantername,$panterid,$printer);
    if($type==1){
        if($_GET['income']){
            $income=$_GET['income'];//收入金额
            $refund=$_GET['refund'];//退款金额
            $str="收入金额（AMOUNT）：\n";
            $printer -> textChinese($str);
            $printer -> setEmphasis(true);
            $printer -> textChinese($income."\n");
            $printer -> setEmphasis(false); // Reset

            $str2="退款金额（AMOUNT）：\n";
            $printer -> textChinese($str2);
            $printer -> setEmphasis(true);
            $printer -> textChinese($refund."\n");
            $printer -> setEmphasis(false); // Reset

            $str3="实际收入金额（AMOUNT）：\n";
            $printer -> textChinese($str3);
            $printer -> setEmphasis(true);
            $printer -> textChinese($income-$refund."\n");
            $printer -> setEmphasis(false); // Reset

            //结算时间
            setTime($printer,2);
        }
    }elseif($type==2){
        $msg=getData($_GET['msg']);
        $order=getData($_GET['order']);
        setGoodsList($msg,$order,$printer);

        $str="总消费：".$msg['total']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str);
        $printer -> setEmphasis(false); // Reset

        $str2="余额：".$msg['balance']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str2);
        $printer -> setEmphasis(false); // Reset

        //交易时间
        setTime($printer,1);
    }elseif($type==3){
        $msg=getData($_GET['msg']);
        $order=getData($_GET['order']);
        setGoodsList($msg,$order,$printer);

        $str="总消费：".$msg['total']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str);
        $printer -> setEmphasis(false); // Reset

        $str2="余额：".$msg['balance']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str2);
        $printer -> setEmphasis(false); // Reset

        //交易时间
        setTime($printer,1);
    }else{
        $msg=getData($_GET['msg']);
        $order=getData($_GET['order']);
        setGoodsList($msg,$order,$printer);

        $str="总数量：".$msg['amount']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str);
        $printer -> setEmphasis(false); // Reset

        $str2="返还金额：".$msg['total']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str2);
        $printer -> setEmphasis(false); // Reset

        $str3="卡内余额：".$msg['balance']."\n";
        $printer -> setEmphasis(true);
        $printer -> textChinese($str3);
        $printer -> setEmphasis(false); // Reset

        //交易时间
        setTime($printer,1);
    }

    $printer -> feed(3);//换行
    $printer -> cut();
    $printer -> pulse();
    $printer -> close();

    //关闭打印窗口
    echo '<script>window.close();</script>';
} catch (Exception $e) {
    echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}

function encode($string){
    $string=base64_encode($string);
    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), $string);
}

function decode($string){
    $string=str_replace(array('O0O0O', 'o000o', 'oo00o'),array('=', '+', '/'), $string);
    return base64_decode($string);
}

//object转数组
function object_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}

//设置标题、商品名称、商品编号
function setGoodsTitles($name,$pantername,$panterid,$printer){
    $str="-------------".$name."-------------\n\n";
    $str.="商户名称（MERCHANT NAME）:\n";//小号字体
    $printer -> textChinese($str);
    $printer -> setEmphasis(true);
    $printer -> textChinese($pantername."\n");
    $printer -> setEmphasis(false); // Reset

    $str2="商户编号（MERCHANT NO）:\n";//小号字体
    $printer -> textChinese($str2);
    $printer -> setEmphasis(true);
    $printer -> textChinese($panterid."\n");
    $printer -> setEmphasis(false); // Reset
}

//数据解密
function getData($data){
    $result=json_decode(decode($data));
    $result=object_array($result);
    return $result;
}

//设置菜品列表
function setGoodsList($msg,$order,$printer){
    $str="卡号（CARD）：\n";
    $printer -> textChinese($str);
    $printer -> setEmphasis(true);
    $printer -> textChinese($msg['cardno']."\n");
    $printer -> setEmphasis(false); // Reset

    $str2="订单号（ORDER）：\n";
    $printer -> textChinese($str2);
    $printer -> setEmphasis(true);
    $printer -> textChinese($msg['tradeid']."\n");
    $printer -> setEmphasis(false); // Reset

    $str3="菜品           数量       总价格\n";
    $printer -> textChinese($str3);

    $total=count($order);
    foreach($order as $k=>$v){
        $printer -> setEmphasis(true);
        $b='         ';
        if(strlen($v[1])==3){
            $a='               ';
        }elseif(strlen($v[1])==6){
            $a='             ';
        }elseif(strlen($v[1])==9){
            $a='           ';
        }elseif(strlen($v[1])==12){
            $a='         ';
        }elseif(strlen($v[1])==15){
            $a='       ';
        }
        if($total==($k+1)){
            $c="\n\n";
        }else{
            $c="\n";
        }
        $printer -> textChinese($v[1].$a.$v[3].$b.$v[4].$c);
        $printer -> setEmphasis(false); // Reset
    }
}

//结算时间
function setTime($printer,$type){
    $date=date('Y-m-d H:i:s',time());
    if($type==1){
        $str="交易时间（DATE/TIME）：\n";
    }else{
        $str="结算时间（DATE/TIME）：\n";
    }
    $printer -> textChinese($str);
    $printer -> setEmphasis(true);
    $printer -> textChinese($date."\n");
    $printer -> setEmphasis(false); // Reset
}