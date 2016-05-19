<?php
/**
 * 缓存操作接口抽象类
 * @copyright reginx.com
 * $Id $
 */
abstract class cache {

    /**
     * 统计结果
     *
     * @var unknown_type
     */
    protected $_stat = array(
        'write' => 0,
        'read'  => 0
    );

    /**
     * 统计
     *
     * @param unknown_type $type
     */
    public final function count ($type = 'w') {
        $this->_stat[$type == 'w' ? 'write' : 'read'] ++;
    }
    
    /**
     * 架构函数
     *
     * @param unknown_type $config
     */
    public static final function getobj ($config = array()) {
        static $obj = null;
        if (empty( self::$obj)) {
            // 缓存读取统计
            $GLOBALS['_STAT']['cache_reads']  = 0;
            // 缓存写入统计
            $GLOBALS['_STAT']['cache_writes'] = 0;
            $class = $config['type'] . '_cache';
            if (RUN_MODE == 'debug') {
                $file = REX_PATH . '/extra/cache/' . $config['type'] . '.cache.php';
                if (!is_file($file)) {
                    throw_error(LANG('driver File not found', $config['type']), 'DFNF', 1, __METHOD__);
                }
                include($file);
            }
            $obj = new $class($config[$config['type']]);
        }
        return $obj;
    }
    
    public final static function chk () {}

    /**
     * 缓存数据统计
     * 
     * @param unknown_type $type
     */
    abstract public function stat ();

    /**
     * 获取缓存
     *
     * @param String $key
     */
    abstract public function get ($key);

    /**
     * 设置缓存
     *
     * @param String $key
     * @param Mixed $value
     * @param Integer $ttl
     */
    abstract public function set ($key, $value, $ttl = 0);

    /**
     * 删除指定缓存
     *
     * @param String $key
     */
    abstract public function del ($key);

    /**
     * 清除缓存
     */
    abstract public function flush ($group = null);
}// end class
?>