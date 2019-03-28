<?php
/**
 * Created by PhpStorm.
 * User: huawei
 * Date: 2018/12/4
 * Time: 10:41
 */

namespace Home\Controller;


class BaseController extends CommonController
{
    public $config;

    public function __construct()
    {
        parent::__construct();
        $this->config->module     = MODULE_NAME;
        $this->config->controller = CONTROLLER_NAME;
        $this->config->action     = ACTION_NAME;
        $this->assign("config", $this->config);
        $this->init();
    }

    protected function buildparams($searchfields = null, $relationSearch = null)
    {

        $filter = htmlspecialchars_decode(I("get.filter"));
        $op     = htmlspecialchars_decode(I("get.op"));
        $filter = json_decode($filter, TRUE);
        $op     = json_decode($op, TRUE);
        $offset = htmlspecialchars_decode(I("get.offset"));
        $limit  = htmlspecialchars_decode(I("get.limit"));
        $filter = $filter ? $filter : [];
        $where  = [];
        foreach ($filter as $k => $v) {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym) {
                case '=':
                    $where[$k] = $v;
                    break;
                case '!=':
                    $where[$k] = ["neq", $v];
                    break;
                case 'LIKE':
                    $where[$k] = ["like", "%" . $v . "%"];
                    break;
                case 'NOT LIKE':
                    $where[$k] = ["not like", $v];
                    break;
                case '>':
                    $where[$k] = ["gt", $v];
                    break;
                case '>=':
                    $where[$k] = ["egt", $v];
                    break;
                case '<':
                    $where[$k] = ["lt", $v];
                    break;
                case '<=':
                    $where[$k] = ["elt", $v];
                    break;
                case 'IN':
                    $where[$k] = ["in", $v];
                    break;
                case 'NOT IN':
                    $where[$k] = ["not in", $v];
                    break;
            }
        }

        return [$where, $offset, $limit];
    }

    /**
     * 初始化requireJs 配置项
     */
    protected function init()
    {
        $config = array(
            "site"           => array(
                'name'     => "",
                'cdnurl'   => "",
                'version'  => '1.0',
                'timezone' => "Asia/Shanghai",
            ),
            'upload'         => array(
                'cdnurl'    => '',
                'uploadurl' => '/zzkp.php/ajax/upload',
                'bucket'    => 'local',
                'maxsize'   => '10mb',
                'mimetype'  => 'jpg,png,bmp,jpeg,gif,zip,rar,xls,xlsx,txt',
                'multipart' => array(),
            ),
            'fastadmin'      => array(
                'api_url'  => str_replace('/zzkp.php', '', U('/', '', true, true)),
                'language' => 'zh-cn'
            ),
            'controllername' => strtolower(CONTROLLER_NAME),
            'actionname'     => strtolower(ACTION_NAME),
            'jsname'         => "/Public/require-backend/js/backend/" . strtolower(CONTROLLER_NAME) . '.js',
            'language'       => "zh-cn"
        );
        $this->assign('require_config', $config);
    }

    /**
     * @param string $msg
     * @param array $data
     * @param string $url
     */
    protected function mistake($msg = '操作失败', $data = [], $url = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        $code = array(
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => ''
        );
        echo json_encode($code, 256);
        exit;
    }

    protected function correct($msg = '操作成功', $data = [], $url = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        $code = array(
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => ''
        );
        echo json_encode($code, 256);
        exit;
    }

}