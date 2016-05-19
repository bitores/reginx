<?php
/**
 * session 抽象接口
 * @copyright reginx.com
 * $Id: sess.class.php 7853 2015-11-23 10:08:59Z reginx $
 */
abstract class sess {

    /**
     * 获取项目值
     *
     * @param unknown_type $key
     */
    abstract protected function get ($key);
    
    /**
     * 获取当前配置信息 (sess_name, sess_id, expires)
     *
     */
    abstract protected function get_config ();
    
    /**
     * 设置项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     */
    abstract protected function set ($key, $value);
    
    /**
     * 是否存在某项
     *
     * @param unknown_type $key
     */
    abstract protected function exists ($key);
    
    /**
     * 获取回话ID
     *
     */
    abstract protected function sess_id ();
    
    /**
     * 删除项目值
     *
     * @param unknown_type $key
     */
    abstract protected function del ($key);
    
    /**
     * 删除会话
     *
     */
    abstract protected function remove ();
    
    
    /**
     * GC 
     *
     */
    abstract protected function gc ();
    
    /**
     * 获取域名
     *
     * @return unknown
     */
    public static final function get_domain () {
        static $_domain = null;
        if (empty($_domain)) {
            $domain = $_SERVER['HTTP_HOST'];
            if (!filter_var($domain, FILTER_VALIDATE_IP)) {
                if (substr_count($_SERVER['HTTP_HOST'] , '.') >= 2 ){
                    $_domain = substr($_SERVER['HTTP_HOST'] , strpos($_SERVER['HTTP_HOST'] , '.'));
                }
                else {
                    $_domain = '.' . $_SERVER['HTTP_HOST'];
                }
            }
            else {
                $_domain = $_SERVER['HTTP_HOST'];
            }
            if (preg_match('/^.+\:\d+$/i', $_domain)) {
                $_domain = preg_replace('/\:\d+/i', '', $_domain);
            }
        }
        return $_domain;
    }
    
    /**
     * 获取 sess 操作对象
     *
     * @param unknown_type $conf
     * @return unknown
     */
    public static final function &getobj($conf, $sess_name = null, $sess_id = null) {
        static $sobj = null;
        if(empty($sobj)) {
            $type = $conf['type'] ? $conf['type'] : 'php';
            if (RUN_MODE == 'debug') {
                $file = REX_PATH . 'extra/sess/' . $type . '.sess.php';
                if (!is_file($file)) {
                    throw_error(LANG('driver file not found', $type . '_sess'), 'DFNF');
                }
                include ($file);
            }
            $class = $type . '_sess';
            $sobj = new $class($conf[$conf['type']], $sess_name, $sess_id);
        }
        return $sobj;
    }
}// end class
?>