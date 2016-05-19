<?php
/**
 * 插件类
 * @author reginx.com
 * $Id: plugin.class.php 4662 2015-07-15 08:26:34Z reginx $
 */
class plugin {
    
    /**
     * 系统默认Hook配置
     *
     * @var unknown_type
     */
    private static $defhooks = array(
        'REX_LOAD'      => array(),
        'TPL_INIT'      => array(),
        'MOD_INIT'      => array(),
        'MOD_DIS'       => array(),
        'MOD_404'       => array(),
    );
    
    private static $hooks = null;
    
    /**
     * 通知
     *
     * @param unknown_type $ctype
     * @param unknown_type $mod
     * @param unknown_type $act
     * @param unknown_type $param
     */
    public static final function notify ($ctype = 'default', $limit = 0, &$param = array()) {
        if (self::$hooks === null) {
            self::loadplugins();
        }
        if (isset(self::$hooks[$ctype]) && ! empty(self::$hooks[$ctype])) {
            foreach ((array) self::$hooks[$ctype] as $k => $v) {
               OBJ($v[0])->{$v[1]}($param ? $param : null);
                if ($limit > 0) {
                    break;
                }
            }
        }
    }
    

    /**
     * 加载插件配置信息
     */
    public static final function loadplugins () {
        if (is_file(APP_PATH . 'config/plugins.php')) {
            self::$hooks = include (APP_PATH . 'config/plugins.php');
        }
        else {
            self::$hooks = array();
        }
    }
 
}