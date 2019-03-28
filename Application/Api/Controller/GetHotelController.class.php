<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/27 0027
 * Time: 17:42
 */

namespace Api\Controller;
use Think\Controller;
use Think\Model;
use Org\Util\Des;

class GetHotelController extends Controller
{
    /*
    * 查询所有的地势
    */
    protected $parent="00000013";    //所属机构
    protected $hkey="JYO2O01";//接口秘钥
    public function GetCity(){
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami,1);
        $hkey=$this->hkey;//密钥
        $province=trim($datami['province']);
        $key=trim($datami['key']);
        $checkKey=md5($hkey.$province);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        if(empty($province)){
            returnMsg(array('status'=>'02','codemsg'=>'未获取到省份信息'));
        }
        $map['provinceid']=$province;
        $list= M('city')->where($map)->field("cityid,cityname")->select();
        returnMsg(array('status'=>'1','list'=>$list));
    }
    /*
     * 查询所有的地势下面的所有酒店
     * flag ：所属机构的状态 1
     * revorkflg：N表示正常的状态
     * hysx   所在机构 酒店
     */
    public function allHotel(){
        $datami = trim($_POST['datami']);
        $datami = json_decode($datami,1);
        $hkey=$this->hkey;//密钥
        $cityid=trim($datami['cityid']);
        $key=trim($datami['key']);
        $checkKey=md5($hkey.$cityid);
        if($checkKey!=$key){
            returnMsg(array('status'=>'01','codemsg'=>'无效秘钥，非法传入'));
        }
        $parent=$this->parent;
        if(empty($parent)){
            returnMsg(array('status'=>'02','codemsg'=>'未获取到省份信息'));
        }
        //所属机构的状态
        $parentwhere['flag']='3';
        $parentwhere['revorkflg']='N';
        $parentwhere['cityid']=$cityid;
        $parentwhere['parent']=$parent;
        $parents = M('panters')->where($parentwhere)->field("panterid,namechinese,address")->select();
        returnMsg(array('status'=>'1','list'=>$parents));
    }
}