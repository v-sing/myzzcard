<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/30
 * Time: 9:42
 */

namespace Service;


use Home\Model\BaseModel;
use Think\Exception;

class BaseService
{

        public   $model;
        public   $code;
        public   $msg;
        public $info;
        public $page;
     private static  $_instances       =[];

        public function  checkIntOrArray($param =null)
        {

            if(is_array($param) || is_numeric($param)){
                if(is_array($param)){
                    return 1;
                }
                if(is_numeric($param)){
                    return 2;
                }
            }
            return false;
        }

        public function model($model)
        {
             if(in_array($model,self::$_instances)){
                        return self::$_instances[$model];
             }else{
                     $obj = "\\Home\\Model\\".$model;
                     self::$_instances[$model] = new $obj;
                     return self::$_instances[$model];
             }
        }

    public function  __get($name)
    {
        // TODO: Implement __get() method.
        if(is_null($this->info)){
            return false;
        }else{
            $name =  array_column($this->info,$name);
            if(!empty($name) || !is_null($name)){
                return $name;
            }
        }
    }
    public function  get($name)
    {
        return    $this->__get($name)[0];
    }

    /**
     * 拼接Sql
     */
    public function getInsertSql($data,$table)
    {
        $sql = "insert into $table(".implode(',',array_keys($data)).")values(".implode(',',array_values($data)).")";
        return $sql;
    }

    /**
     * 拼接Sql
     */
    public function getUpdateSql($data,$table,$id=[])
    {

        $sql = "update  $table(".implode(',',array_keys($data)).")values(".implode(',',array_values($data)).")";
        return $sql;
    }

    /** 过滤输入
     * @param string $type
     * @return mixed
     */
    public function  filterInput($type = 'get',$data=null,$filterField=[])
    {
        if(is_null($data)){
            $data = I("$type.");
        }
        foreach ($data as $key=>&$val){
            if(!is_numeric($val) || in_array($key,$filterField) ){
                $val =  trim($val,"'");
                $val =  trim($val);
                $val =  "'$val'";
            }
        }
        return $data;
    }


    public function  getInfo($where=null,$page=null)
    {
        if (is_null($page)) {
            $this->info = $this->model($this->modelName)->getInfo($where, $page);
            return $this->info;
        } else {
            list($this->info, $this->page) = $this->model($this->modelName)->getInfo($where, $page);
            return $this->info;
        }

    }

    public function  Excel()
    {
        $th = I('get.th/a');
        $data = I('get.data');
        $title = I('get.title');
        $title = $title[0];
        unset($th[count($th)-1]);
        $excelData = [];
        $datas     = [];
        $k = count($th);

        foreach ($data as $key=>$val){
            if($key<$k-1){
                array_push($datas,$val);
            }else{
                array_push($datas,$val);
                $k += count($th);
                array_push($excelData,$datas);
                $datas= [];
            }
        }

        $this->exportExcel($th,$excelData, $title, './', true);
    }

     /**

     * 数据导出

     * @param array $title   标题行名称

     * @param array $data   导出数据

     * @param string $fileName 文件名

     * @param string $savePath 保存路径

     * @param $type   是否下载  false--保存   true--下载

     * @return string   返回文件全路径

     * @throws PHPExcel_Exception

     * @throws PHPExcel_Reader_Exception

     */

    public function exportExcel($title=array(), $data=array(), $fileName='', $savePath='./', $isDown=false){

        require("./ThinkPHP/Library/Org/Util/Autoloader.php");
        $obj = new \PHPExcel();



        //横向单元格标识

        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');



        $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称

        $_row = 1;   //设置纵向单元格标识

        if($title){

            $_cnt = count($title);

            $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格

            $obj->setActiveSheetIndex(0)->setCellValue('A'.$_row, '数据导出：'.date('Y-m-d H:i:s'));  //设置合并后的单元格内容

            $_row++;

            $i = 0;

            foreach($title AS $v){   //设置列标题

                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);

                $i++;

            }

            $_row++;

        }



        //填写数据

        if($data){

            $i = 0;

            foreach($data AS $_v){

                $j = 0;

                foreach($_v AS $_cell){

                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i+$_row), $_cell);

                    $j++;

                }

                $i++;

            }

        }



        //文件名处理

        if(!$fileName){

            $fileName = uniqid(time(),true);

        }



        $objWrite = \PHPExcel_IOFactory::createWriter($obj, 'Excel2007');



        if($isDown){   //网页下载

            header('pragma:public');

            header("Content-Disposition:attachment;filename=$fileName.xls");

            $objWrite->save('php://output');exit;

        }



        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码

        $_savePath = $savePath.$_fileName.'.xlsx';

        $objWrite->save($_savePath);



        return $savePath.$fileName.'.xlsx';

    }


    public function  filePost($url,$data)
    {

        $options['http'] = array(
            'timeout'=>60,
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $data
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    /**
     *数字金额转换成中文大写金额的函数
     *String Int  $num  要转换的小写数字或小写字符串
     *return 大写字母
     *小数位为两位
     **/
    public function get_amount($num){
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "数据太长，没有这么大的钱吧，检查下";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num)-1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j-3;
                $slen = $slen-3;
            }
            $j = $j + 3;
        }

        if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
        }
        if (empty($c)) {
            return "零元整";
        }else{
            return $c . "整";
        }
    }
    /**XML转数组
     * @param $xmlstring
     * @return mixed
     */
   function simplest_xml_to_array($xml) {
        $xml = iconv( "UTF-8","gb2312",$xml);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;

    }


    function  curlRequest($url,$header=null,$data=null,$method="POST")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        return  json_decode($result,true);
    }

    function base64EncodeImage ($image_file) {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }


    #导入数据 到数据库
    public function excelExport($tableName = null,\Closure $closure,$Preview = null)
    {
        #初始化EXCEL
        $excelService = new PhpExcelService();
        $upload_handler = new UploadHandler(['upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']).'/Public/ExcelFile/']);
        $str            = json_decode($upload_handler->str,true);
        #识别图片内容
        $files = dirname($_SERVER['SCRIPT_FILENAME']).'/Public/ExcelFile/'.$str['files'][0]['name'];
        if(file_exists($files)){
            $data =  $excelService->importExecl($files);
            @unlink($files);
            foreach($data as &$v)
            {
                try{
                    #过滤数据
                    if(is_null($Preview) || !$Preview){
                        $v    =   $closure($v,$data);
                        $v    =  $excelService->filterInput(null,$v);
                        $sql = $excelService->getInsertSql($v,$tableName);
                        (new BaseModel())->execute($sql);
                    }else{
                           $v    =   $closure($v,$data);
                    }
                }catch (\Exception $exception){
                    return false;
                }

            }
        }else{
           return false;
        }
        return true;
    }

    public function ExceptionMsg($msg)
    {
        throw new Exception($msg);
    }

}