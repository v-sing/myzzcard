<?php
/**
 * Created by PhpStorm.
 * Author: 紫云沫雪こ
 * Email:email1946367301@163.com
 * Date: 2019/3/6 0006
 * Time: 16:55
 */

namespace Home\Controller;

/**
 * 通宝业内消费报表
 * Class CirculateexportController
 * @package Home\Controller
 */
class CirculateexportController extends CommonController
{
    /**
     * 父级id
     * 酒店总公司,物业总公司,教育公司,艾佳家居,绿色基地,足球俱乐部,嵩云,商业公司,艾米
     * @var array
     */

    protected $parentName = [
        '00000013' => '酒店总公司',
        '00000451' => '物业总公司',
        '00000130' => '教育公司',
        '00000452' => '艾佳生活',
        '00000453' => '绿色基地',
        '00000014' => '足球俱乐部',
        '00000455' => '嵩云科技(一家)',
        '00000456' => '商业公司',
        '00000457' => '文旅公司(艾米)'
    ];
    protected $otherName = [
        '00000466' => '河南建业新生活旅游服务有限公司'
    ];
    protected $search = [
        array(
            'start' => '2018-01-01',
            'end'   => '2018-12-31'
        ),
        array(
            'start' => '2016-01-01',
            'end'   => '2018-12-31'
        )
    ];
    /**
     * 数据集合
     * @var array
     */
    protected $data = [];

    /**
     * 导出数据
     */
    public function excel()
    {
        ini_set('memory_limit', '520M');
        $array = [];
        foreach ($this->search as $k => $v) {
            $this->combination($v['start'], $v['end'], $k);
        }
        $cellName = array('公司类型');
        //动态添加字段名称
        foreach ($this->search as $k => $v) {
            $start      = date("Y", strtotime($this->search[$k]['start']));
            $end        = date('Y', strtotime($this->search[$k]['end']));
            $cle        = ($end - $start) + 1;
            $cellName[] = '兑换数量' . $cle . '年';
        }
        $cellName[] = '年度';
        $filename   = '各业态兑换年度报表' . date('YmdHis');
        array_unshift($this->data, $cellName);
        $this->load_excel($this->data, $filename);
    }

    /**
     * 组合数据
     */
    protected function combination($starttime, $enddate, $index = 0)
    {
        //通宝消费主表
        $coin_consume = M('coin_consume');
        //查询parent父id的商户消费集合
        $where  = [
            'ps1.parent'    => ['in', implode(',', array_keys($this->parentName))],
            'cc.placeddate' => [
                'between', [str_replace('-', '', $starttime), str_replace('-', '', $enddate)]
            ]
        ];
        $result = $coin_consume->
        alias('cc')->where($where)
            ->join("panters ps1 on ps1.panterid=cc.panterid")->field("ps1.namechinese,ps1.parent,cc.placeddate,cc.amount")
            ->select();

        $array = [];
        //遍历相加
        foreach ($result as $k => $v) {
            $array[$v['parent']] = [
                'name'           => $this->parentName[$v['parent']],
                'point' . $index => $array[$v['parent']]['point' . $index] + $v['amount'],
            ];
        }
        //如果表中不存在消费内容，判断数据数量遍历不存在的商户，数量为0
        if (count($array) != count($this->parentName)) {
            foreach ($this->parentName as $k => $v) {
                if (!in_array($k, array_keys($array))) {
                    $array[$k] = [
                        'name'           => $v,
                        'point' . $index => 0,
                    ];
                }
            }
        }
        //查询PANTERID的商户消费集合
        $where   = [
            'ps1.PANTERID'  => ['in', implode(',', array_keys($this->otherName))],
            'cc.placeddate' => [
                'between', [str_replace('-', '', $starttime), str_replace('-', '', $enddate)]
            ]
        ];
        $result1 = $coin_consume->
        alias('cc')->where($where)
            ->join("panters ps1 on ps1.panterid=cc.panterid")->field("ps1.PANTERID,ps1.namechinese,cc.placeddate,cc.amount")
            ->select();
        $array2  = [];

        foreach ($result1 as $k => $v) {
            $array2[$v['panterid']] = [
                'name'           => $v['namechinese'],
                'point' . $index => $array2[$v['panterid']]['point' . $index] + $v['amount'],
            ];
        }
        //如果表中不存在消费内容，判断数据数量遍历不存在的商户，数量为0
        if (count($array2) != count($this->otherName)) {
            foreach ($this->otherName as $k => $v) {
                if (!in_array($k, array_keys($array2))) {
                    $array2[$k] = [
                        'name'           => $v,
                        'point' . $index => 0,
                    ];
                }
            }
        }
        $data = array_merge($array2, $array);
        //合并数组
        if (!empty($this->data)) {
            $arr = [];
            foreach ($this->data as $k => $v) {
                $add         = array_merge($v, $data[$k]);
                $add['time'] = date("Y", strtotime($this->search[0]['start']));
                //字段排序
                ksort($add);
                $arr[] = $add;
            }
            $data = $arr;
        }
        $this->data = $data;
    }
}