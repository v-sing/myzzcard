<?php
/**
 * Created by PhpStorm.
 * Author: 紫云沫雪こ
 * Email:email1946367301@163.com
 * Date: 2019/3/18 0018
 * Time: 19:57
 */

namespace Connected\Controller;


use Think\Controller;
use Think\Model;
class BaseController extends Controller
{
    /**
     * 返回数据类型
     * @var string
     */
    protected $type = 'json';
    /**
     * 是否是调试模式
     * @var bool
     */
    protected $app_debug = true;

    public function __construct()
    {

        $this->_initialize();
    }

    /**
     * 初始化
     */
    protected function _initialize()
    {

    }
    protected function getFieldNextNumber($field){
        $seq_field='seq_'.$field.'.nextval';
        $sql="select {$seq_field} from dual";

        $model=new Model();

        $list=$model->execute($sql);
        $fieldsLength=C('FIELDS_LENGTH');
        $fieldLength=$fieldsLength[$field];

        $lastNumber=$list[0]['nextval'];
        return  $this->getnumstr($lastNumber,$fieldLength);
    }

    //获得增加长度的字符 $numstr编号    $lennum字符长度
    public function getnumstr($numstr,$lennum){
        $snum=strlen($numstr);
        for($i=1;$i<=$lennum-$snum;$i++){
            $x.='0';
        }
        return $x.$numstr;
    }
    /**
     * 验证
     * @param array $data
     * @return mixed
     */
    protected function checkKey($data)
    {
        if (false) {

            if (!is_array($data)) {
                $this->returnMsg('014', '请求错误，缺少参数！');
            }
            if (!isset($data['sign'])) {
                $this->returnMsg('015', '缺少签名！');
            }

            $data['key'] = C('connectedKey');
            ksort($data);
            $sign = $data['sign'];
            unset($data['sign']);
            $str = '';
            foreach ($data as $k => $v) {
                if (is_string($v)) {
                    $str .= $v;
                }
            }
            if (!$this->app_debug) {
                if (md5($str) != $sign) {
                    $this->returnMsg('016', '无效秘钥，非法传入');
                }
            }
            unset($data['connectedKey']);


        }
        return $data;
    }

    /**
     * 统一返回方法
     * @param $code
     * @param string $msg
     * @param array $data
     */
    protected function returnMsg($code, $msg = '', $data = [])
    {
        $data = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        $this->ajaxReturn($data, $this->type);
    }


}