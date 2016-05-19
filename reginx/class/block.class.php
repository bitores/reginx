<?php
/**
 * block 基类
 * @copyright reginx.com
 * $Id: block.class.php 198 2015-01-21 10:16:58Z reginx $
 */
interface block_base {

    /**
     * 获取 block 名称
     *
     */
    public static function getname ();
    
    /**
     * 获取 block Mode
     *
     */
    public static function getmode ();
    
    /**
     * 获取记录
     *
     * @param unknown_type $type  模式
     * @param unknown_type $ext   扩展参数
     */
     public static function get ($type = 0, $ext = array());
     
    
    /**
     * 获取字段信息
     */
     public static function getfields ();
}

/**
 * block 抽象类
 *  (直接使用抽象类在5.4+下会出现提示消息, 多一层接口继承以兼容5.4+)
 */
abstract class block implements block_base {
    
    /**
     * 默认模式
     *
     * @var unknown_type
     */
    protected static $mode = array();
    
    /**
     * 检测是否有效的模块名称
     *
     * @param unknown_type $name
     * @return unknown
     */
    public static final function chk ($name) {
        $list = self::getlist();
        return isset($list[$name]);
    }
    
    /**
     * 获取 block 类信息
     *
     * @return unknown
     */
    public static final function getlist () {
        $ret = $GLOBALS['_CACHE']->get('rex@blocks');
        if (RUN_MODE == 'debug' || empty($ret)) {
            if (!is_dir(INC_PATH . 'block')) {
                throw_error(LANG('directory does not exist', core::relpath(INC_PATH . 'block')), 'BDDNE', 1);
            }
            foreach (glob(INC_PATH . 'block' . DS . '*.block.php') as $v) {
                $clsname = str_replace('.block.php', '_block', basename($v));
                $ret[$clsname] = call_user_func(array($clsname, 'getname'));
            }
            $GLOBALS['_CACHE']->set('rex@blocks', $ret, 86400);
        }
        return $ret;
    }
    
    /**
     * 获取数据
     *
     * @return unknown
     */
    public static final function getdata ($skey = '') {
        $ret = $GLOBALS['_CACHE']->get('block@' . $skey);
        if (RUN_MODE == 'debug' || empty($ret)) {
            $ret = array();
            $block = OBJ('block_table')->where("skey = '{$skey}'")->get();
            if (!empty($block)) {
                $block['extra'] = unserialize($block['extra']);
                $ret = call_user_func_array(array($block['module'], 'get'), array(
                    'type'  => $block['ctype'],
                    'extra' => $block['extra']
                ));
                $GLOBALS['_CACHE']->set('block@' . $skey, $ret, $block['ttl']);
            }
            else {
                throw_error(LANG('invalid block tag'));
            }
        }
        return $ret;
    }
    
    
    /**
     * 获取数据
     *
     * @return unknown
     */
    public static final function dump ($skey = '') {
        $ret = '';
        $block = OBJ('block_table')->where("skey = '{$skey}'")->get();
        if (!empty($block)) {
            $block['extra'] = unserialize($block['extra']);
            $block['extra']['dump'] = 1;
            $ret = call_user_func_array(array($block['module'], 'get'), array(
                'type'  => $block['ctype'],
                'extra' => $block['extra']
            ));
        }
        return $ret;
    }
}
?>