<?php
return array(
    'MULTI_MODULE'		=>true,
    'DEFAULT_MODULE'	=>'Home',
    'MODULE_ALLOW_LIST'   => array('Home','Pos','Jiekou','Api','Connected'),
    'URL_HTML_SUFFIX'	=>'',
    'DB_TYPE' => 'Oracle', // 数据库类型
//    'DB_HOST' => '192.168.2.1', // 服务器地址
//    'DB_NAME' => 'ORCLE', // 数据库名
//    'DB_USER' => 'jycs', // 用户名
//    'DB_PWD' => 'root', // 密码
//    'DB_PORT' => '1521', // 端口

    //'DB_HOST' => '218.29.116.171', // 服务器地址
    //'DB_NAME' => 'jycard', // 数据库名
    //'DB_USER' => 'jycard', // 用户名
    //'DB_PWD' => 'jycard', // 密码
    //'DB_PORT' => '9008', // 端口

	'DB_HOST' => '171.11.74.204', // 服务器地址
	'DB_NAME' => 'jycard', // 数据库名
	'DB_USER' => 'jycard', // 用户名
	'DB_PWD' => 'Oracle_2019', // 密码
	'DB_PORT' => '1522', // 端口

    'TMPL_ACTION_ERROR'=>'Public:success',
    'TMPL_ACTION_SUCCESS'=>'Public:success',
	'LOAD_EXT_CONFIG' => 'ip',	// 扩展配置文件
	'FIELDS_LENGTH'=>array(
        'customid'=>8,'purchaseid'=>12,'auditid'=>16,
        'cardpurchaseid'=>18,'accountid'=>8,
        'coinid'=>8,'coinconsumeid'=>10,'panterid'=>8,
        'pointid'=>8,'pointconsumeid'=>10,'withholdid'=>10,
        'quanpurchaseid'=>10,'quanaccountid'=>10,'ruleid'=>8,'hbid'=>8
    ),
	'jyhomebalance'=>'http://localhost/zzkp.php/Pos/JyHome/getPointAccount'
	
);
