<?php
/**
 * 数据库访问接口抽象类
 * @copyright reginx.com
 * $Id: database.class.php 198 2015-01-21 10:16:58Z reginx $
 */
abstract class database {

    /**
     * 获取数据操作对象
     *
     * @param array $conf
     * @param boolean $mode
     * @return mixed
     */
    public static final function getobj ($conf = array()) {
        static $dbobj = null;
        if (empty($dbobj)) {
            if (empty($conf)) {
                $conf = $GLOBALS['_RC']['db'];
            }
            if (RUN_MODE == 'debug') {
                $file = REX_PATH . 'extra/db/' . $conf['type'] . '.db.php';
                if (!is_file($file)) {
                    throw_error(LANG('driver file not found', $conf['type']), 'DFNF', 1, __METHOD__);
                }
                include($file);
            }
            $class = $conf['type'] . '_db';
            $dbobj = new $class($conf[$conf['type']]);
        }
        return $dbobj;
    }
    
    /**
     * 连接
     *
     * @param unknown_type $conf
     */
    abstract public function connect ($conf = array());
    
    /**
     * 空操作
     *
     */
    abstract public function noop ();
    
    /**
     * 切换数据库
     *
     * @param unknown_type $db
     */
    abstract public function usedb ($db);
    
    /**
     * 判断是否连通
     *
     */
    abstract public function ping ();
    
    /**
     * 释放连接
     *
     */
    abstract public function close ();
    
    /**
     * 返回错误信息
     *
     */
    abstract public function geterror ();
    
    /**
     * 返回查询统计
     *
     */
    abstract public function getcounter ();
    
    /**
     * 执行 sql
     *
     */
    abstract public function query ($sql);
    
    /**
     * 获取单条记录
     *
     * @param unknown_type $sql
     */
    abstract public function get ($sql);
    
    /**
     * 获取批量记录
     *
     * @param unknown_type $sql
     */
    abstract public function getlist ($sql, $key = null);
    
    /**
     * 获取结果统计
     *
     * @param unknown_type $sql
     */
    abstract public function count ($sql);
    
    /**
     * 获取sql执行影响行数
     *
     */
    abstract public function getaffects ();
    
    /**
     * 插入记录
     *
     * @param unknown_type $sql
     */
    abstract public function insert ($sql);
    
    /**
     * 更新记录
     *
     * @param unknown_type $sql
     */
    abstract public function update ($sql);
    
    /**
     * 删除记录
     *
     * @param unknown_type $sql
     */
    abstract public function delete ($sql);
    
    
    /**
     * 获取表结构中字段列表
     *
     * @param unknown_type $tab
     */
    abstract public function getfieldlist ($tab);
    
    
    /**
     * 获取所有表
     *
     * @param unknown_type $pre
     */
    abstract public function gettables ();
    
    /**
     * 获取数据库硬盘占用
     *
     */
    abstract public function getdbsize ();
    
    /**
     * 服务器统计信息
     *
     */
    abstract public function stat ($mode = null);
    
} // end class
?>