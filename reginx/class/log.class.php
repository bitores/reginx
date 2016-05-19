<?php
/**
 * 日志
 * @copyright reginx.com
 * $Id$
 */
abstract class log {
    
    /**
     * 写日志
     *
     * @param unknown_type $key
     */
    abstract protected function write ($content);
    
    /**
     * 刷新内容至存储
     *
     */
    abstract protected function flush ($key = null);
    
    /**
     * 初始化
     *
     */
    abstract protected function init ($key = null);
    
    /**
     * 获取日志操作对象
     *
     * @return unknown
     */
    public static function getobj ($key) {
        static $logobj = null;
        if(empty($logobj)) {
            $type = isset($GLOBALS['_RC']['log']['type']) ? $GLOBALS['_RC']['log']['type'] : 'file';
            if (RUN_MODE == 'debug') {
                $file = REX_PATH . 'extra/log/' . $type . '.log.php';
                if (!is_file($file)) {
                    throw_error(LANG('driver file not found', $type . '_log'), 'DFNF');
                }
                include ($file);
            }
            $class = $type . '_log';
            $logobj = new $class();
        }
        $logobj->init($key);
        return $logobj;
    }
}// end class