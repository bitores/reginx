<?php
/**
 * 默认 sess 实现类
 * @copyright reginx.com
 * $Id: php.sess.php 7853 2015-11-23 10:08:59Z reginx $
 */
class php_sess extends sess {
    
    
    /**
     * 架构函数
     *
     * @param unknown_type $conf
     */
    public function __construct ($conf, $sess_name = null, $sess_id = null) {
        session_set_cookie_params(86400 , '/', parent::get_domain());
        if (!empty($sess_name)) {
            session_name($sess_name);
        }
        if (!empty($sess_id)) {
            session_id($sess_id);
        }
        session_start();
        if ($conf['cache']) {
            header("Cache-control: private"); // 使用http头控制缓存
        }
        // 更新 cookie ttl , + 30min
        setcookie(session_name(), session_id(), REQUEST_TIME + (RUN_MODE == 'debug' ? 86400 : 1800) , "/", parent::get_domain());
    }
    
    /**
     * 获取当前配置信息
     *
     * @return unknown
     */
    public function get_config () {
        return array(
            'id'        => session_id(),
            'name'      => session_name(),
            'expires'   => 1800
        );
    }
    
    /**
     * 获取 session ID
     *
     * @return unknown
     */
    public function sess_id () {
        return session_id();
    }
    
    /**
     * GC
     *
     */
    public function gc () {}
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function get ($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function set ($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * 删除项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function del ($key) {
        $_SESSION[$key] = null;
    }
    
    /**
     * 验证是否存在某项目
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function exists ($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * 销毁回话
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function remove () {
        return session_destroy();
    }
}// end class