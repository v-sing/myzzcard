<?php
namespace Home\Behaviors;
use Think\Behavior;

class LogBehavior extends Behavior{

    public function run(&$params)
    {
        $userid = $params['userid'];
        $act_name = $params['act_name'];
        $cont_name = $params['cont_name'];
        $placeddate = date("Ymd",time());
        $placedtime = date("Hsi",time());
        $ip = get_client_ip();

        $think_node = M("think_node");
        $cont_info = $think_node->where(array("name"=>$cont_name, "status"=>1,"lev"=>2))
                   ->field("title,id")
                   ->find();
        if($cont_info){
            $act_info = $think_node->where(array("name"=>$act_name, "status"=>1,"pid"=>$cont_info['id']))
                ->field("title")
                ->find();
            if($act_info){
                $formname = "{$cont_info['title']}/{$act_info['title']}";
                $time = time();
                $strsql="INSERT INTO operator_logs (operatorlogid,placedate,logintime,logouttime,userid,loginip,formname)
                         VALUES ('{$time}','{$placeddate}','{$placeddate}','{$placedtime}','{$userid}','{$ip}','{$formname}')";
                M("operator_logs")->execute($strsql);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


}