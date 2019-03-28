<?php
$zzkurl = 'http://zz.9617777.com';//至尊卡公网API
return array(
    //'配置项'=>'配置值'
    // 'Tbcount_URL' => 'http://192.168.2.55/fang/posindex.php/Tbcount', //通宝统计URL
    'greenParent'=>'00000483',
    //soon
    'soonEParent'=>'00000243',
    'Tbcount_URL' => 'https://www.fangzg.cn/posindex.php/tbcount', //正式通宝统计URL
    'e+_balance' => 'http://10.1.1.37/zzkp.php/JyCoin/getBalance', //查询一家 余额充值显示余额
    'e+create_order'=>'http://mall.yijiahn.com/mall/app/goods/quickbuy.json',//余额充值生成订单
    'callbackurl' =>$zzkurl.'/zzkp.php/JyCoin/receiveE',
    'chargeMoney'=>'http://10.1.1.37/zzkp.php/JyCoin/customRecharge',//生产地址
    'netApiUrl' => 'http://192.168.10.50/Web/login.ashx',//net版接口地址，生产，暂时无预生产
    'Fang_url' => '10.1.1.33:8080', //卡系统接口
    'ApiUrl' => $zzkurl,
    'USER_AUTH_KEY'		=>'userid',
    'USER_AUTH_ON'		=>true,
    'USER_AUTH_TYPE'	=>1,
    'RBAC_ROLE_TABLE'	=>'think_role',
    'RBAC_USER_TABLE'	=>'think_role_user',
    'RBAC_ACCESS_TABLE'	=>'think_access',
    'RBAC_NODE_TABLE'	=>'think_node',
    'NOT_AUTH_ARRAY'	=>array(
        array('AfterSales','card_query,check_reissue,checkCustom,lossCardDo'),
        array('Card','getcustompl,getcustomid,getcards,getcustoms,getsellcard,getcardpay'),
        array('Panters','getterminal,getCity'),
        array('PointsMan','pointQuery,pointExchange,getGifttrade,returnGiftDo,getTrade,returnGoodsDo'),
        array('Seller','getquank,getCity,getcards'),
        array('System','upPassword'),
        array('Common','getComplete,getAjaxCitys,getPanterBrands'),
        array('Index','index,header,seller,users,system,cards,welcome,welcome1'),
        array('Mistake','correct,more,less,points,pMore,pLess,market,mMore,mLess,index,indexExcel'),
        array('App','addCustomInfo,test,delData,cleanReturnCards,cleanCards,cleanCoinData,createSql,createSql1,cleanCoinConsume,updateDate'),
        array('Fangzg','test,openCards1'),
        array('CoinAccount','getPanterInfo'),
    ),
    'ADMIN_AUTH_KEY'	=>'superadmin',
    'SESSION_EXPIRE'    =>10,	//60秒
    'DEFAULT_TIMEZONE'	=>'PRC',
    'fangzgIp' => 'https://www.fangzg.cn',//房掌柜接口IP
    'walletIp'=>'http://o2o.yijiahn.com',
    'zfjsIp'=>'http://192.168.10.22:8080',
	'refundCoinUrl' => 'http://10.1.1.33/api/Jycoin/ejiaRefund',
    'LOAD_EXT_CONFIG'=>'ehome',
    'ehome_getorg'=>'http://o2o.yijiahn.com/jyo2o_web/user/mobile/firstorg.json',
    'ecardIssue'=>'http://o2o.yijiahn.com/jyo2o_web',
    'KfAccount'=>array('kfdoufu','kfwanfc','kftieguo','kfzhuangmo','kffanzhuang','kfwangmt','kfqianxc','kfniurou'),
    'jytype' => array( '02' => '劵消费','23'=>'券消费撤销','00' => '至尊卡消费','04'=>'消费撤销', '13' => '现金消费', '17' => '预授权', '21' => '预授权完成'),
    'flag'=>array('0'=>'正常','1'=>'冲正','2'=>'退货','3'=>'撤销','4'=>'退款'),
    'VipQuan'=>array(
        array('quanid'=>'00000149','quanname'=>'经典客房住宿券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000150','quanname'=>'经典客房住宿券','amount'=>1,'month'=>'6'),
        array('quanid'=>'00000151','quanname'=>'客房升级券','amount'=>4,'month'=>'12'),
        array('quanid'=>'00000152','quanname'=>'西餐自助餐券','amount'=>2,'month'=>'6'),
        array('quanid'=>'00000153','quanname'=>'西餐聚会券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000154','quanname'=>'西餐一百元现金券','amount'=>5,'month'=>'12'),
        array('quanid'=>'00000155','quanname'=>'中餐任点任食券','amount'=>3,'month'=>'6'),
        array('quanid'=>'00000156','quanname'=>'中餐聚会券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000157','quanname'=>'日餐聚会券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000158','quanname'=>'日餐一百元现金券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000159','quanname'=>'进口葡萄酒券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000160','quanname'=>'双人下午茶套餐券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000161','quanname'=>'生日蛋糕券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000162','quanname'=>'艾美饼屋十元现金券','amount'=>3,'month'=>'12'),
        array('quanid'=>'00000163','quanname'=>'游泳/健身券','amount'=>2,'month'=>'12'),
    ),
    'VipQuan1'=>array(
        array('quanid'=>'00000149','quanname'=>'经典客房住宿券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000150','quanname'=>'经典客房住宿券','amount'=>1,'month'=>'6'),
        array('quanid'=>'00000151','quanname'=>'客房升级券','amount'=>4,'month'=>'12'),
        array('quanid'=>'00000152','quanname'=>'西餐自助餐券','amount'=>2,'month'=>'6'),
        array('quanid'=>'00000153','quanname'=>'西餐聚会券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000154','quanname'=>'西餐一百元现金券','amount'=>5,'month'=>'12'),
        array('quanid'=>'00000155','quanname'=>'中餐任点任食券','amount'=>3,'month'=>'6'),
        array('quanid'=>'00000156','quanname'=>'中餐聚会券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000157','quanname'=>'日餐聚会券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000158','quanname'=>'日餐一百元现金券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000159','quanname'=>'进口葡萄酒券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000160','quanname'=>'双人下午茶套餐券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000161','quanname'=>'生日蛋糕券','amount'=>1,'month'=>'12'),
        array('quanid'=>'00000162','quanname'=>'艾美饼屋十元现金券','amount'=>3,'month'=>'12'),
        array('quanid'=>'00000163','quanname'=>'游泳/健身券','amount'=>2,'month'=>'12'),
        array('quanid'=>'00000200','quanname'=>'双人自助餐券(续卡送)','amount'=>1,'month'=>'6'),
    ),
    //补全编号（生产环境才有）
    //   'FIELDS_LENGTH'=>array(
    //     'customid'=>8,'purchaseid'=>12,'auditid'=>16,
    //     'cardpurchaseid'=>18,'accountid'=>8,
    //    'coinid'=>8,'coinconsumeid'=>10,'panterid'=>8,
    //   'pointid'=>8,'pointconsumeid'=>10,'withholdid'=>10,
    //    'quanpurchaseid'=>10,'quanaccountid'=>10,'ruleid'=>8,'hbid'=>8
    // ),
    'QuanCate'=>array('1'=>'住宿','2'=>'餐饮','3'=>'饼屋','4'=>'烧烤','5'=>'电影','6'=>'活动','7'=>'其他','8'=>'花卉','9'=>'教育','10'=>'健身房','11'=>'洗衣房'),
    't_ransomType'=>array('0'=>'待补录','1'=>'待记账','2'=>'待复核','3'=>'待授权','4'=>'完成','5'=>'失败','7'=>'已执行退款','8'=>'拒绝','9'=>'撤销','10'=>'未同步','11'=>'已同步'),

    /**
     * 清除实名认证接口
     * http://ysco2o.yijiahn.com/jyo2o_web/app/realnameinfo/del.json
     * http://ysco2o.yijiahn.com/jyo2o_web/app/realnameinfo/delwithcid.json
     *
     * http://o2o.yijiahn.com/jyo2o_web/app/realnameinfo/del.json //正式
     */
   'refreshRate'=>'http://o2o.yijiahn.com/jyo2o_web/app/realnameinfo/del.json',


    //主redis 生产！！！
    'master_redis' => array('host' => '10.1.1.30', 'port' => '1989', 'pwd' => 'Redisjyzz123'),

    //预生产redis
    // 'master_redis' => array('host' => '172.16.1.8', 'port' => '6379', 'pwd' => 'Redisjyzz123'),

    //测试redis
    // 'master_redis' => array('host' => '127.0.0.1', 'port' => '6379', 'pwd' => 'Redisjyzz123'),

);
