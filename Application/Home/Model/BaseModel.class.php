<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/5
 * Time: 18:18
 */

namespace Home\Model;


use Think\Model;
use Think\Page;

class BaseModel extends Model
{
    public function  getInfo($where=null,$page=null)
    {
        if(is_null($page)){
            $sql = $this->where($where)->fetchSql()->select();
            $data = $this->where($where)->select();
            return    $data;
        }else{
            $count = $this->where($where)->count();
            $page = new  Page($count,$page);
            $sql = $this->where($where)->limit($page->firstRow.','.$page->listRows)->fetchSql()->select();
            $data = $this->where($where)->limit($page->firstRow.','.$page->listRows)->select();
            return [$data,$page];
        }
    }

    /**
     * 插入
     * @param array $data
     * @param string $pk 自增
     * @param bool $flag
     * @return false|int
     */
    public function oracleInsert($data = array(),$pk='id', $flag = true)
    {
        if (empty($data))
            return 0;
        $key = array_keys($data);
        $value = array_values($data);
        $inster_key = array();
        $inster_value = array();
        $index=-1;
        foreach ($key as $k=> $v) {
            if($pk==$v){
                $index=$k;
            }
            $inster_key[] = '"' . $v . '"';
        }
        foreach ($value as $k=> $v) {
            if($index>-1&&$index==$k){
                $inster_value[] = $v;
            }else{
                $inster_value[] = "'" . $v . "'";
            }
        }
        $key_string = !$flag ? implode(',', $inster_key) : strtoupper(implode(',', $inster_key));
        $value_string = implode(',', $inster_value);
        $inster = 'INSERT INTO ' . $this->tableName . ' ( ' . $key_string . ' ) VALUES (' . $value_string . ')';
        return $this->execute($inster);
    }

    /**
     * 更新
     */
    public function oracleUpdate($data = array(), $where = array(), $flag = true)
    {
        if (empty($data) || empty($where))
            return 0;
        $update_string = '';
        $update_array = array();
        foreach ($data as $key => $value) {
            $update_key_string = !$flag ? $key : strtoupper($key);
            $update_value = $value;
            $update_array[] = '"' . $update_key_string . '"=' . "'" . $update_value . "'";
        }
        $update_string = implode(',', $update_array);
        $where_array = array();
        foreach ($where as $key => $value) {
            $where_key_string = !$flag ? $key : strtoupper($key);
            $where_value = $value;
            $where_array[] = '"' . $where_key_string . '"=' . "'" . $where_value . "'";
        }
        $where_string = implode(' AND ', $where_array);
        $updatesql = 'UPDATE ' . $this->tableName . ' SET ' . $update_string . ' WHERE ' . $where_string;

        return $this->execute($updatesql);
    }
}