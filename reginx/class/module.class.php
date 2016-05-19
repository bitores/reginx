<?php
/**
 * 核心模块类
 * @copyright reginx.com
 * $Id: module.class.php 5725 2015-10-26 02:33:48Z reginx $
 */
class module {
    
    /**
     * 配置
     *
     * @var unknown_type
     */
    protected $_config = null;
    
    /**
     * 会话操作对象
     *
     * @var unknown_type
     */
    public $_sess = null;
    
    /**
     * 模板操作对象
     *
     * @var unknown_type
     */
    protected $_tpl = null;
    
    
    /**
     * 架构函数
     *
     * @param unknown_type $param
     */
    public function __construct($param = array()) {
        $this->_tpl = $GLOBALS['_TPL'];
        $this->_config = &$GLOBALS['_RC'];
        plugin::notify('MOD_INIT');
        // 设置默认 模板风格目录
        $this->_tpl->style($this->_config['template']);
        $this->assign('_MODULE', $GLOBALS['_MOD']);
        $this->assign('_ACTION', $GLOBALS['_ACT']);
    }
    
    /**
     * 会话开启
     *
     */
    public function initsess ($sess_name = null, $sess_id = null) {
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $GLOBALS['_SESS'] = sess::getobj($this->_config['sess'], $sess_name, $sess_id);
            $this->_sess = &$GLOBALS['_SESS'];
        }
    }

    /**
     * 获取参数
     *
     * @param unknown_type $k
     * @return unknown
     */
    public final function get ($key, $type = 'R') {
        return core::getrp($key, $type);
    }

    /**
     * 是否存在某类型的参数
     *
     * @param unknown_type $k
     * @return unknown
     */
    public final function has ($key, $type = 'R') {
        return core::hasrp($key, $type);
    }

    /**
     * 模板赋值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function assign ($key, $value) {
        return $this->_tpl->assign($key, $value);
    }
    
    /**
     * 加载模块语言包
     *
     * @param unknown_type $name
     */
    public final function lang ($name = 'zh-cn') {
        core::loadlang(APP_NAME . '@' . $name);
    }
    
    /**
     * 禁止客户端缓存当前页
     */
    public final function nocache () {
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", 0) . "GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
    }

    /**
     * 重定向
     *
     * @param unknown_type $url
     * @param unknown_type $parse
     */
    public final function redirect ($url, $parse = true, $code = '303') {
        header("HTTP/1.1 {$code} See Other");
        header("Location: " . ($parse ? core::url($url) : $url));
        header('X-Powered-By: REGINX v' . REX_VER);
        exit(0);
    }
    
    /**
     * 转发
     *
     * @param unknown_type $class
     * @param unknown_type $method
     */
    public function forward ($class, $method, &$extra = array()) {
        $class = $class . '_module';
        if (empty($extra)) {
            $extra = &$this->conf;
        }
        call_user_func(array( new $class($extra), $method . 'Action'));
    }
    
    /**
     * Ajax 输出
     *
     * @abstract 默认不缓存
     * @param unknown_type $data
     * @param unknown_type $type
     */
    public function ajaxout ($data, $type = 'json', $output_header = true) {
        plugin::notify('MOD_AJAXOUT');
        $this->nocache();
        header('X-Powered-By: REGINX V' . REX_VER);
        if ($type == 'json') {
            if ($output_header) {
                header('Content-Type: application/json; charset=utf-8');
            }
            exit(json_encode($data, 256));
        }
        else {
            header('Content-Type: text/html; charset=utf-8');
            exit($data);
        }
    }
    
    /**
     * 渲染模板
     *
     * @param unknown_type $tplfile
     * @return unknown
     */
    public function display ($tplfile) {
        plugin::notify('MOD_DISPLAY');
        return $GLOBALS['_TPL']->display($tplfile);
    }
    
    /**
     * 获取解析后的模板内容
     *
     * @param unknown_type $tplfile
     * @return unknown
     */
    public function fetch ($tplfile) {
        return $GLOBALS['_TPL']->fetch($tplfile, true);
    }
    
    /**
     * form token
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function token ($key) {
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        $token =  md5(REQUEST_TIME . core::randstr(4));
        $this->_sess->set($key . '@' . APP_NAME, $token);
        return "<input type=\"hidden\" name=\"{$key}\" value=\"{$token}\" />";
    }
    
    /**
     * 验证 token
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function verifytoken ($key) {
        $ret = false;
        $val = $this->get($key, '*');
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        if ($val == $this->_sess->get($key . '@' . APP_NAME)) {
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * 删除token
     *
     * @param unknown_type $key
     */
    public function rmtoken ($key) {
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        $this->_sess->del($key . '@' . APP_NAME);
    }
    
    /**
     * 显示404页面
     *
     * @param unknown_type $msg
     */
    public function show404 ($msg) {
        plugin::notify('MOD_404');
        $GLOBALS['_TPL']->display($GLOBALS['_RC']['404_tpl']);
    }
    
    /**
     * 显示提示消息
     *
     * @param unknown_type $msg
     */
    public function showmsg ($msg) {
        throw_error($msg, '404', false);
    }
    
    /**
     * cli echo 
     *
     * @param unknown_type $out
     */
    public function cout ($out) {
        if (!is_array($out)) {
            echo($out . PHP_EOL);
        }
        else {
            foreach ($out as $k => $v) {
                echo($k . ' => ' . $v . PHP_EOL);
            }
        }
    }
}
?>