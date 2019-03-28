<?php
namespace Home\Model;
use Think\Model;
class QuanModel extends Model
{
  public function search($where)
  {
    $quan = M('quan_publish');
    $field = 'q.*,p.namechinese,u.username ';
    $count = $quan->alias('q')
                  ->JOIN('left join __PANTERS__ p on p.panterid=q.panterid')
                  ->where($where)->count();
    $p = new \Think\Page($count,10);
    $data['lists']=  $quan->alias('q')
                  ->JOIN('left join __PANTERS__ p on p.panterid=q.panterid')
                  ->JOIN('left join __USERS__ u on u.userid=q.userid')
                  ->where($where)->field($field)->limit($p->firstRow.','.$p->listRows)->select();
    if(!empty($map)){
        foreach($map as $key=>$val) {
            $p->parameter[$key]= $val;
        }
    }
    $data['page']= $p->show();
    return $data;
  }
  public function addProduct($data)
  {
    $quan = M('quan_publish');
    $sql = "INSERT INTO quan_publish (quanid,quanname,panterid,startdate,enddate,amount,description,status,userid) VALUES('";
    $sql.=$data['quanid']."','".$data['quanname']."','".$data['panterid']."','".$data['startdate']."','".$data['enddate']."','".$data['amount'];
    $sql.="','".$data['description']."','".$data['status']."','".$data['userid']."')";
    $res = $quan->execute($sql);
    return $res;
  }
  public function editProduct($quanid){
    $quan = M('quan_publish');
    $lists= $quan->where("quanid=$quanid")->find();
    return $lists;
  }
  public function editsave($quanid,$data)
  {
    $quan = M('quan_publish');
    $res= $quan->where("quanid=$quanid")->save($data);
    return $res;
  }
  //有效期内券类查询
  public function quansearch($where)
  {
    $quan = M('quan_publish');
    $quankinds= $quan->alias('q')->join('__PANTERS__ p on p.panterid=q.panterid')
            ->where($where)->field('quanid,quanname')->select();
    return  $quankinds;
  }
  //购买营销产品查询
  public function pursearch($where,$map){
    $pur = M('quan_purchase');
    $field='a.cardno,a.customid,a.purdate,a.overdate,a.quanid,u.username,a.puramount,a.purtime,';
    $field.='b.customid as customid1,b.namechinese,d.quanname,d.startdate,d.enddate,c.namechinese as pantername';
    $count=$pur->alias('a')
        ->join('left join customs_c m on a.customid=m.cid')
        ->join('left join customs b on m.customid=b.customid')
        ->join('left join quan_publish d on a.quanid=d.quanid')
        ->join('left join panters c on d.panterid=c.panterid')
        ->where($where)
        ->field($field)->count();
    $p=new \Think\Page($count, 10);
    $data['list']=$pur->alias('a')
        ->join('left join customs_c m on a.customid=m.cid')
        ->join('left join customs b on m.customid=b.customid')
        ->join('left join quan_publish d on a.quanid=d.quanid')
        ->join('left join panters c on d.panterid=c.panterid')
        ->join('left join users u on u.userid=a.userid')
        ->where($where)
        ->field($field)->limit($p->firstRow.','.$p->listRows)
        ->order('a.purdate desc,a.purtime desc')->select();
    if(!empty($map)){
        foreach($map as $key=>$val) {
            $p->parameter[$key]= $val;
        }
      }
      $data['page']=$p->show();
      return $data;
  }
  //营销券购买
   public function  purchase($data,$userid){
    $quan = M('quan_publish');
    $pur = M('quan_purchase');
    $data['purchaseid'] = $data['purchaseid'].date('His',time());
    $list = $quan->where("quanid={$data['quanid']}")->find();
    if($list['status']=='1'){
      $purdate = date('Ymd',time());
      $overdate = $purdate +$list['enddate']-$list['startdate'];
    }else{
      $purdate = date('Ymd',time());
      $overdate = $list['enddate'];
    }
    $purtime = date('H:i:s',time());
    $status = '1';//0用于锁定券
    $reamount = $data['puramount'];
    $sql = "INSERT INTO quan_purchase (quanid,cardno,customid,purchaseid,purdate,overdate,userid,status,puramount,purtime,reamount) VALUES('";
    $sql.=$data['quanid']."','".$data['cardno']."','".$data['customid']."','".$data['purchaseid']."','".$purdate."','".$overdate;
    $sql.="','".$userid."','".$status."','".$data['puramount']."','".$purtime."','".$reamount."')";
    $res=$pur->execute($sql);
    return $res;
  }
  //导出充值报表查询
  public function purquery($where){
    $pur = M('quan_purchase');
    $field='a.cardno,a.customid,a.purdate,a.overdate,a.quanid,u.username,a.puramount,a.purtime,';
    $field.='b.customid as customid1,b.namechinese,d.quanname,c.namechinese as pantername';
    $data['list']=$pur->alias('a')
        ->join('left join customs_c m on a.customid=m.cid')
        ->join('left join customs b on m.customid=b.customid')
        ->join('left join quan_publish d on a.quanid=d.quanid')
        ->join('left join panters c on d.panterid=c.panterid')
        ->join('left join users u on u.userid=a.userid')
        ->where($where)
        ->field($field)->limit($p->firstRow.','.$p->listRows)
        ->order('a.purdate desc,a.purtime desc')->select();
    return $data['list'];
  }
  //查询行业是酒店的商户
  public function hysx()
  {
    $panters = M('panters');
    $hysx='酒店';
    $lists = $panters->where("hysx="."'".$hysx."'")->field('panterid,namechinese')->select();
    return $lists;
  }
}
?>
