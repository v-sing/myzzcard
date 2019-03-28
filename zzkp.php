<?php


// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
function recordLog($fileName){
       $time = date("Ymd",time());
       $filePath = "./log/";
		$data['REMOTE_ADDR'] =$_SERVER['REMOTE_ADDR'];
		$data['REQUEST_URI'] =json_encode($_SERVER['REQUEST_URI'],JSON_UNESCAPED_SLASHES);
		$data['REQUEST_METHOD'] =json_encode($_SERVER['REQUEST_METHOD'],JSON_UNESCAPED_SLASHES);

		$data = serialize($data);
       if(!is_dir($filePath)){
           mkdir($filePath,0777);
       }
       $rtime = date('Y-m-d H:i:s',time());
       $str = $rtime."--ip:{$_SERVER['REMOTE_ADDR']}--".$data."\t\n";
       $bool = file_put_contents("{$filePath}{$fileName}.log", $str,FILE_APPEND);
   }
   recordLog('11.txt');
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);

// 定义应用目录
define('APP_PATH','./Application/');

define('PUBLIC_PATH','./Public/');

define('IMAGE_PATH','./IMAGES/');

define('GREEN','./Public/green');

//define('BIND_MODULE','Home');
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单
