<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends CommonController {
    function index(){
        $userid=$this->urerid;
        if($userid=='0000000000000056'){
            $this->assign('is_qjs',1);
        }
		$this->display();
    }
}