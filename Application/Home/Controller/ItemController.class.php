<?php
/**
 * 系统配置-常规配置
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/23 0023
 * Time: 9:01
 */

namespace Home\Controller;

use Home\Model\ItemModel;

class ItemController extends BaseController
{
    protected $model = null;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ItemModel();
    }


    /**
     * 配置页面
     */
    public function index()
    {
        $condition = array();
        $data      = $this->model->where($condition)->select();
        //如果为空添加基础数据
        if (empty($data)) {
            $inster_array = array(
                'id'      => 'seq_item.nextval',
                'name'    => 'configgroup',
                'group'   => 'dictionary',
                'title'   => '数据字典',
                'tip'     => '',
                'type'    => "array",
                'value'   => json_encode(array('dictionary' => '数据字典'), 256),
                'content' => '',
                'extend'  => ""
            );
            $result       = $this->model->oracleInsert($inster_array);
            $data         = $this->model->where($condition)->select();

        }
        $condition = array('name' => 'configgroup');
        $group     = $this->model->where($condition)->find();
        $siteList  = [];
        $groupList = json_decode($group['value'], true);
        foreach ($groupList as $k => $v) {
            $siteList[$k]['name']  = $k;
            $siteList[$k]['title'] = $v;
            $siteList[$k]['list']  = array();
        }
        foreach ($data as $k => $v) {
            if (!isset($siteList[$v['group']])) {
                continue;
            }
            $value          = $v;
            $value['title'] = $value['title'];
            if (in_array($value['type'], array('select', 'selects', 'checkbox', 'radio'))) {
                $value['value'] = explode(',', $value['value']);
            }
            $value['content']                = json_decode($value['content'], TRUE);
            $siteList[$v['group']]['list'][] = $value;
        }
        $index = 0;
        foreach ($siteList as $k => &$v) {
            $v['active'] = !$index ? true : false;
            $index++;
        }
//        dump($siteList);exit;
        $this->assign('siteList', $siteList);
        $this->assign('typeList', ItemModel::getTypeList());
        $this->assign('groupList', $groupList);
        $this->display();
    }

    /**
     * 添加分组
     */
    public function addGroup()
    {
        $params = $_POST['row'];
        if ($params) {
            foreach ($params as $k => &$v) {
                $v = is_array($v) ? implode(',', $v) : $v;
            }
            try {
                if (in_array($params['type'], ['select', 'selects', 'checkbox', 'radio', 'array'])) {
                    $params['content'] = json_encode(ItemModel::decode($params['content']), JSON_UNESCAPED_UNICODE);
                } else {
                    $params['content'] = '';
                }
                $params['id'] = 'seq_item.nextval';
                $result       = $this->model->oracleInsert($params);
                if ($result !== false) {
                    try {
                    } catch (Exception $e) {
                        $this->mistake($e->getMessage());
                    }
                    $this->correct('添加成功');
                } else {
                    $this->mistake($this->model->getError());
                }
            } catch (Exception $e) {
                $this->mistake($e->getMessage());
            }
        }
    }

    /**
     * 保存配置
     */
    public function editItem()
    {
        $row = $_POST["row"];

        if ($row) {
            try {
                $configList = [];
                $all        = $this->model->select();
                foreach ($all as $v) {
                    if (isset($row[$v['name']])) {
                        $value = $row[$v['name']];
                        if (is_array($value) && isset($value['field'])) {
                            $value = json_encode(ItemModel::getArrayData($value), JSON_UNESCAPED_UNICODE);
                        } else {
                            $value = is_array($value) ? implode(',', $value) : $value;
                        }
                        $v['value']   = $value;
                        $configList[] = $v;
                    }
                }
                foreach ($configList as $v) {
                    $model = new ItemModel();
                    unset($v['numrow']);
                    $model->oracleUpdate($v, array('id' => $v['id']));
                }

            } catch (Exception $e) {
                $this->mistake($e->getMessage());
            }
            $this->correct('更新成功！');
        }
    }

    /**
     * 验证唯一性
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check()
    {
        $data = $_POST;
        if (preg_match("/[\x7f-\xff]/", $data['row']['name'])) {  //判断字符串中是否有中文
            return $this->error('变量名不能为中文！');
        }
        $info = $this->model->where(array('name' => $data['row']['name']))->find();
        if ($info) {
            return $this->mistake('变量名已经存在！');
        }
        return $this->correct();
    }

    public function del()
    {
        $data   = $_POST;
        $result = $this->model->execute("DELETE FROM ITEM WHERE " . '"NAME"=' . "'" . $data['name'] . "'");
        if ($result) {
            $this->correct('删除成功！');
        } else {
            $this->mistake('删除失败！');
        }
    }

}