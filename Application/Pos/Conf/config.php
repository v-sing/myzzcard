<?php
return array(
	//'配置项'=>'配置值'
	'USER_AUTH_KEY'		=>'userid',
	'USER_AUTH_ON'		=>true,
	'USER_AUTH_TYPE'	=>1,
	'RBAC_ROLE_TABLE'	=>'think_role',
	'RBAC_USER_TABLE'	=>'think_role_user',
	'RBAC_ACCESS_TABLE'	=>'think_access',
	'RBAC_NODE_TABLE'	=>'think_node',
	'ADMIN_AUTH_KEY'	=>'superadmin',
    'SESSION_EXPIRE'    =>10,	//60秒
    'DEFAULT_TIMEZONE'	=>'PRC',
	'quanall'=>array(
		'qunakind1'=>array(
			'quanid'=>'00000014',
			'panterid'=>'00000013',
			'annotation'=>'1、凭本券可享受建业艾米免费包间观影体验一次；<br/>2、本券不兑现不找零；<br/> 3、本券有效期为君邻会会籍有效期；<br/> 4、此券最终解释权归建业艾米·1895电影街所有。',
		),

		'qunakind2'=>array(
			'quanid'=>'00000015',
			'panterid'=>'00000013',
			'annotation'=>'1、凭本券可享建业艾米主题大厅免费使用一次；<br/> 2、本券不兑现不找零；<br/> 3、本券有效期为君邻会会籍有效期；<br/>  4、此券最终解释权归建业艾米·1895电影街所有。',
		),
		'qunakind3'=>array(
			'quanid'=>'00000016',
			'panterid'=>'00000013',
			'annotation'=>'1、会员凭本券，可在生日当天享建业艾米主题大厅免费使用一次；<br/> 2、本券不兑现不找零；<br/>3、本券有效期为君邻会会籍有效期；<br/> 4、此券最终解释权归建业艾米·1895电影街所有。',
		),
		'qunakind4'=>array(
			'quanid'=>'00000017',
			'panterid'=>'00000013',
			'annotation'=>'1、本券限建业集团旗下酒店使用；<br/> 2、凭本券和君邻会会员卡密码享会员专属价；<br/> 3、本券不兑现不找零，每张券限一房晚；<br/> 4、本券有效期为君邻会会籍有效期；<br/>5、本券最终解释权归建业酒店所有。',
		),
	),
		'LOAD_EXT_CONFIG' => 'green,kfcarteen,catering'	,// 扩展配置文件
		'ecardConsume'=>'http://mall.yijiahn.com/mall'
);
