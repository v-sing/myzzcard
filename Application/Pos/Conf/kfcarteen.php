<?php
return array(
//卡所属商户
	'kfcarteen_cardpanterid'=>'00000915',
	//充值用户编号
	'kfcarteen_userid'=>'0000000000000062',
	//前台充值 退卡商户
    'kfcarteen_chongpanterid'=>'00001294',
    //所属机构号
    'kfcarteen_parent'=>'00000915',
	'kfcarteen_empanterid'=>array('00000000','00000000'),
	//烟酒
	'kfcarteen_getDrink'=>'00000000',
	'kfcarteen_qempanterid'=>'00000000',
	'kfcarteen_gift'    =>  array('300'=>'100','1000'=>'100',
		                          '600'=>'200','900'=>'300',
		                          '2000'=>'300','1200'=>'400',
		                          '1500'=>'500','5000'=>'800'),
	'kfcarteen_gift_api'=>  array(array('realmoney'=>'300','gift'=>'100'),array('realmoney'=>'1000','gift'=>'100'),
		                          array('realmoney'=>'600','gift'=>'200'),array('realmoney'=>'900','gift'=>'300'),
		                          array('realmoney'=>'2000','gift'=>'300'),array('realmoney'=>'1200','gift'=>'400'),
		                          array('realmoney'=>'1500','gift'=>'500'),array('realmoney'=>'5000','gift'=>'800')
	                             ),
);

?>