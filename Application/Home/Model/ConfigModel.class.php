<?php
namespace Home\Model;
use Think\Model;
class ConfigModel extends Model
{
    protected $config;
    public function _initialize()
    {
        $this->config = M('config');

    }
    /*
     * get config lists
     */
    public function getConfigList(){
        return $this->config->select();
    }
    // add config sql handle
    public function addConfig(array $config){
        $sql = "insert into config (conid,name,value,description) VALUES ('{$config['conid']}','{$config['name']}','{$config['value']}','{$config['description']}')";
        return $this->config->execute($sql);
    }
    public function getPersonExpireConfig($name){
        return $this->config->where(['name'=>$name])->find();
    }
    /*
     *edit config value
     * @param array $map condition
     * @param array $save save value
     * @return affect conlume
     */
    public function save($map,$save){
        return $this->config->where($map)->save($save);
    }
}