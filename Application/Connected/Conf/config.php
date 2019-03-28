<?php
/**
 * Created by PhpStorm.
 * Author: 紫云沫雪こ
 * Email:email1946367301@163.com
 * Date: 2019/3/18 0018
 * Time: 20:11
 */
return [

    /**
     * 秘钥
     */
    'connectedKey' => 'JYO2O01',
    'cardStatus'   => [
        'A' => [
            'status' => '02',
            'msg'    => '待激活'
        ],
        'D' => [
            'status' => '03',
            'msg'    => '销卡'
        ],
        'R' => [
            'status' => '04',
            'msg'    => '退卡'
        ],
        'S' => [
            'status' => '05',
            'msg'    => '过期'
        ],
        'N' => [
            'status' => '06',
            'msg'    => '新卡'
        ],
        'L' => [
            'status' => '07',
            'msg'    => '锁定'
        ],
        'W' => [
            'status' => '08',
            'msg'    => '无卡'
        ],
        'C' => [
            'status' => '09',
            'msg'    => '已出库'
        ],
        'J' => [
            'status' => '010',
            'msg'    => '入库'
        ],
        'T' => [
            'status' => '011',
            'msg'    => '冻结'
        ],
        'G' => [
            'status' => '012',
            'msg'    => '异常锁定'
        ],
        'B' => [
            'status' => '013',
            'msg'    => '睡眠'
        ]
    ]
];