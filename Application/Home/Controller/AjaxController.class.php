<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26 0026
 * Time: 9:54
 */

namespace Home\Controller;


class AjaxController extends BaseController
{

    public function lang()
    {
        return [];
    }

    public function upload()
    {
        header('Content-Type:application/json; charset=utf-8');
        $file = isset($_FILES['file']) ? $_FILES['file'] : array();
        if (empty($file) || $file['error'] != 0) {
            $this->error('文件不存在！');
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        switch ($extension) {
            case 'jpg' || 'png' || 'bmp' || 'jpeg' || 'gif':
                $result = $this->_upload();
                break;
            case 'xls' || 'xlsx':
                $result = $this->_upload('excel');
                break;
            default:
                $result = $this->_upload('custom');
                break;
        }
        if (is_array($result) && isset($result['file'])) {
            $file_path = "./Public/" . $result['file']['savepath'] . $result['file']['savename'];
            $code      = array(
                'code' => 1,
                'msg'  => $result['file']['name'] . ' 上传成功!',
                'data' => array(
                    'url' => $file_path
                ),
                'url'  => ''
            );

        } else {
            $code = array(
                'code' => 0,
                'msg'  => $result['file']['name'] . ' 上传失败!',
                'data' => array(
                    'url' => ''
                ),
                'url'  => ''
            );
        }
        echo json_encode($code, 256);
        exit;
    }
}