<?php
if (!defined('IS_CLI')) {
    define('IS_CLI', false);
}
/**
 * 核心库
 * @copyright reginx.com
 * $Id: core.class.php 5733 2015-10-26 07:29:44Z reginx $
 */
class core {
    
    /**
     * 写入 cookie
     *
     * @param unknown_type $key
     * @param unknown_type $val
     * @param unknown_type $ttl
     * @param unknown_type $path
     * @param unknown_type $domain
     */
    public static function set_cookie ($key, $val, $ttl = 86400, $path = '/', $domain = null) {
        setcookie($key, $val, REQUEST_TIME + $ttl , $path, $domain ? $domain : sess::get_domain());
    }

    /**
     * 获取基本 URL
     *
     * @return unknown
     */
    public static function get_base_url () {
        $ret = APP_NAME == 'default' ? APP_URL : (dirname(APP_URL) . '/');
        if (IS_ALIAS) {
            $ret = 'http://' . (empty($GLOBALS['_RC']['router']['def_domain']) ? '' : ($GLOBALS['_RC']['router']['def_domain'] . '.')) 
                        . $GLOBALS['_RC']['router']['rootdomain'];
        }
        return $ret;
    }

    /**
     * 获取 app url
     *
     * @return unknown
     */
    public static function getappurl () {
        if (isset($GLOBALS['_RC']['app_url']) && !empty($GLOBALS['_RC']['app_url'])) {
            return $GLOBALS['_RC']['app_url'];
        }
        else {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            if (defined('D_ALIAS') && defined('D_MAP')) {
                $dir = str_replace('/' . D_ALIAS , "/" . D_MAP, $dir);
                $dir = empty($dir) ? '/' : $dir;
            }
            $dir = substr($dir, -1) == '/' ? substr($dir, 0, -1) : $dir;
            return "http://{$_SERVER['HTTP_HOST']}"
                    . ($dir == '/' ? '' : $dir) . '/';
        }
    }

    /**
     * 加载语言包文件
     *
     * @param unknown_type $key
     */
    public static function loadlang ($key = '@default:zh-cn') {
        static $loaded = array();
        if (!isset($loaded[$key])) {
            $file = self::_getlangfile($key);
            if (is_file($file)) {
                $loaded[$key] = true;
                $GLOBALS['_RL'] = array_merge(
                    empty($GLOBALS['_RL']) ? array() : $GLOBALS['_RL'], include($file));
            }
            else {
                throw_error(LANG('file not found', self::relpath($file)), 'FNF');
            }
        }
    }

    /**
     * 获取语言文件
     *
     * @param unknown_type $key
     * @return unknown
     */
    private static function _getlangfile ($key) {
        // app name
        if(($pos = strpos($key, '@')) !== false){
            $app = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
        }
        // 默认app
        if($app == 'default'){
            $app = (BASE_PATH . 'lang/');
        }else{
            $app  = empty($app) ? (REX_PATH . 'extra/lang/') : (BASE_PATH . $app . '/lang/');
        }
        // mod name
        if(($pos = strpos($key , ':')) !== false){
            $mod   = substr($key, 0, $pos);
            $key   = substr($key, $pos + 1);
        }
        return $app . (empty($mod) ? 'default' : $mod) . '.' . $key . '.php';
    }

    /**
     * 判断 模块文件是否存在
     *
     * @param unknown_type $module
     * @return unknown
     */
    public static function module_file_exists ($module) {
        return file_exists(APP_PATH . 'module' . DS . $module . '.module.php');
    }

    /**
     * 自动装载 (*.mod.php, *.lib.php, *.cls.php, *.tab.php)
     *
     * @param unknown_type $class
     */
    public static function loader ($class) {
        $paths = explode('_', $class);
        $name  = array_shift($paths);
        if (empty($paths)) {
            throw_error(LANG('class file not found', $class), 'CFNF');
        }
        
        $type  = array_pop($paths);
        if (!in_array($type, array('lib', 'log', 'cls', 'module', 'table', 'block', 'iface', 'test'))) {
            throw_error(LANG('class type is not allowed', $type), 'CTINTNA');
        }
        if (!empty($paths)) {
            $file = $type . DS . $name . DS . join(DS, $paths) . '.' . $type . '.php';
        }
        else {
            $file = $type . DS . $name . '.' . $type . '.php';
        }
        $file = (in_array($type, array('module', 'iface', 'test')) ? APP_PATH : INC_PATH) . $file;
        if (!is_file($file)) {
            if ($type == 'module') {
                plugin::notify('MOD_404');
                $GLOBALS['_TPL']->display($GLOBALS['_RC']['404_tpl']);
            }
            else {
                throw_error(LANG('class file not found', self::relpath($file)), 'CFNF');
            }
        }
        include ($file);
    }

    /**
     * 加载扩展文件
     *
     * @param unknown_type $file
     */
    public static function loadfile ($file) {
        $key  = explode('/', $file);
        $path = sprintf('%sextra%s%s%s%s.%s.php', REX_PATH, DS, $key[0], DS, $key[1], $key[0]);
        if (!is_file($path)) {
            new error($path);
        }
        return include($path);
    }

    /**
     * 命令行参数解析
     *
     * @param unknown_type $file
     */
    public static function cliparse () {
        if ($_SERVER['argc'] == 1) {
            exit (
                "< Reginx v " . REX_VER . " CLI Mode > " . PHP_EOL  .
                "Usage: php cli.php <index/index/key=value/key1=value1...>" . PHP_EOL
            );
        }
        
        $GLOBALS['_RP'] = array();
        $path = explode('/', $_SERVER['argv'][1]);
        for ($i = 0; $i < count($path); $i ++) {
            if ((int)$i === 0) {
                $GLOBALS['_MOD'] = $path[$i];
            }
            else if ((int)$i === 1) {
                $GLOBALS['_ACT'] = $path[$i];
            }
            else {
                $tmp = explode('=', $path[$i]);
                $GLOBALS['_RP'][$tmp[0]] = $tmp[1];
            }
        }
        
        $GLOBALS['_MOD'] = empty($GLOBALS['_MOD']) ? 'index' : $GLOBALS['_MOD'];
        $GLOBALS['_ACT'] = empty($GLOBALS['_ACT']) ? 'index' : $GLOBALS['_ACT'];
    }

    /**
     * 路由解析
     *
     * @param unknown_type $conf
     */
    public static function routerparse ($conf = array()) {
        $GLOBALS['_RP'] = array();
        // 解析配置
        if (RUN_MODE == 'debug' || !isset($GLOBALS['_RC']['router']['MA'])) {
            // '2:M/A/P_V&:.html:1:.reginx.com/:1'; 带有子域名绑定且使用 ssl的规则
            if ($conf['pattern'] == '1') {
                $conf['pattern'] = '1:M-A/P-V-:.html:0';
            }
            else if ($conf['pattern'] == '2') {
                $conf['pattern'] = '2:M/A?P=V&:.html:0';
            }
            $pattern = explode(':', $conf['pattern']);
            // 路由类型
            $conf['type'] = intval($pattern[0]);
            // url 后缀
            $conf['suf']  = isset($pattern[2]) ? $pattern[2] : ''; 
            // 是否启用 rewrite
            $conf['rewrite'] = isset($pattern[3]) && $pattern[3] ? true : false;
            // 根域名, 如果设置了根域名, 则会自动处理含子域名的url拼接
            $conf['rootdomain'] = isset($pattern[4]) && $pattern[4] ? $pattern[4] : false;
            // 协议, 根域名配置后如果加上了非 false 的字串, 则表示使用 ssl
            $conf['protocol'] = isset($pattern[5]) && $pattern[5] ? 'https://' : 'http://';

            // 解析分隔符
            if ($conf['type'] > 0) {
                $ints = preg_split('/[a-z]/i', substr($pattern[1], 1));
                if (count($ints) != 4) {
                    throw_error(LANG('invalid routing configuration'));
                }
                $conf['MA'] = $ints[0];
                $conf['AP'] = $ints[1];
                $conf['PP'] = $ints[2];
                $conf['PG'] = $ints[3];
            }
            $GLOBALS['_RC']['router'] = $conf;
            if (RUN_MODE != 'debug') {
                core::setconfig(APP_NAME, 'router', $conf);
            }
        }
        // 普通模式
        if ($GLOBALS['_RC']['router']['type'] == 0) {
            $GLOBALS['_RP'] = $_GET;
        }
        // 特殊模式
        else {
            // query string & 兼容模式
            if ($GLOBALS['_RC']['router']['type'] == 1) {
                $requrl  = $_SERVER['QUERY_STRING'];
                // compatible for iis
                if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                    $requrl = explode('?', $_SERVER['HTTP_X_REWRITE_URL']);
                    $requrl = $requrl[1];
                }
            }
             // PATH INFO
            else if ($GLOBALS['_RC']['router']['type'] == 2) {
                if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != "/") {
                    // compatible for nginx PATH_INFO
                    $requrl = substr($_SERVER['PATH_INFO'], 1);
                    // 去除后缀
                    if (substr($requrl, 0 - strlen($GLOBALS['_RC']['router']['suf'])) == $GLOBALS['_RC']['router']['suf']) {
                        $requrl = substr ($requrl, 0, 0 - strlen($GLOBALS['_RC']['router']['suf']));
                    }
                    if (substr($requrl, - 1) == '/') {
                        $requrl = substr($requrl, 0, -1);
                    }
                    if ($GLOBALS['_RC']['router']['AP'] == '?' && !empty($_SERVER['QUERY_STRING'])) {
                        $requrl .= $GLOBALS['_RC']['router']['AP'] . str_replace($pattern[2], '', urldecode($_SERVER['QUERY_STRING']));
                    }
                }
                else {
                    $requrl = $GLOBALS['_RC']['router']['def_mod'] . $GLOBALS['_RC']['router']['MA']
                                 . $GLOBALS['_RC']['router']['def_act'];
                }
            }
            // 去除后缀
            if (substr($requrl, 0 - strlen($GLOBALS['_RC']['router']['suf'])) == $GLOBALS['_RC']['router']['suf']) {
                $requrl = substr ($requrl, 0, 0 - strlen($GLOBALS['_RC']['router']['suf']));
            }

            // 解析出 module , action 名称
            $mastring = '';
            if (($pos = stripos($requrl, $GLOBALS['_RC']['router']['AP'])) !== false){
                $mastring = substr($requrl, 0, $pos);
                $requrl = substr($requrl, $pos + strlen($GLOBALS['_RC']['router']['AP']));
                // 分隔符相同的情况 
                if ($GLOBALS['_RC']['router']['MA'] == $GLOBALS['_RC']['router']['AP'] 
                    && ($pos = stripos($requrl, $GLOBALS['_RC']['router']['AP'])) !== false) {
                    $mastring .= $GLOBALS['_RC']['router']['AP'] . substr($requrl, 0, $pos);
                    $requrl = substr($requrl, $pos + strlen($GLOBALS['_RC']['router']['AP']));
                }
            }
            else if (($pos = stripos($requrl, $GLOBALS['_RC']['router']['MA'])) !== false) {
                 $mastring = $requrl;
                 $requrl = '';
            }
            else {
                $mastring = $requrl;
                $requrl = '';
            }

            // 存在 MA 标识
            if (($pos = strpos($mastring, $GLOBALS['_RC']['router']['MA'])) !== false) {
                $GLOBALS['_MOD'] = substr($mastring, 0, $pos);
                $GLOBALS['_ACT'] = substr($mastring, $pos + strlen($GLOBALS['_RC']['router']['MA']));
            }
            // 不存在则认作为 _MOD
            else if (empty($requrl)){
                $GLOBALS['_MOD'] = $mastring;
                $GLOBALS['_ACT'] = $GLOBALS['_RC']['router']['def_act'];
            }
            else {
                $GLOBALS['_MOD'] = $mastring;
                $GLOBALS['_ACT'] = $requrl;
            }
            $GLOBALS['_MOD'] = empty($GLOBALS['_MOD']) ? $GLOBALS['_RC']['router']['def_mod'] : $GLOBALS['_MOD'];
            $GLOBALS['_ACT'] = empty($GLOBALS['_ACT']) ? $GLOBALS['_RC']['router']['def_act'] : $GLOBALS['_ACT'];
            
            // request parameters
            if (!empty($requrl)) {
                $vals = explode($GLOBALS['_RC']['router']['PG'], $requrl);
                if ($GLOBALS['_RC']['router']['PG'] == $GLOBALS['_RC']['router']['PP']) {
                    for ($i = 0; $i < count($vals); $i +=2) {
                        $GLOBALS['_RP'][$vals[$i]] = isset($vals[$i + 1]) ? $vals[$i + 1] : null;
                    }
                }
                else {
                    foreach ((array)$vals as $v) {
                        if (($v = trim($v)) != '') {
                            $tmp = explode($GLOBALS['_RC']['router']['PP'], $v);
                            $GLOBALS['_RP'][$tmp[0]] = isset($tmp[1]) ? $tmp[1] : null;
                        }
                    }
                }
            }
        }
    }

    /**
     * url 生成
     *
     * @return unknown
     */
    public static function url () {
        // 路由配置信息
        static $conf = null;
        if ($conf === null) {
            $conf = $GLOBALS['_RC']['router'];
        }

        $params = func_get_args();
        if (empty($params)) {
            return 'javascript:;';
        }
        $pattern = str_replace('-', '{^_^}', array_shift($params));
        $pattern = vsprintf($pattern, $params);
        // absolute
        $prefix  = BASE_URL;
        
        $suffix  = $conf['suf'];

        // relative
        $isrelpath = substr($pattern, 0, 1) == '@';
        if ($isrelpath) {
            $pattern = substr($pattern, 1);
        }
        
        // no alias  
        $noalias = substr($pattern, 0, 1) == '^';
        if ($noalias) {
            $pattern = substr($pattern, 1);
            $prefix  = BASE_URL;
        }
        
        // bind subdomain
        $subdomainpos = strpos($pattern, '.');
        $subdomain = null;
        if ($subdomainpos !== false) {
            if ($subdomainpos === 0 || !$conf['rootdomain']) {
                $pattern = substr($pattern, $subdomainpos + 1);
                $subdomainpos = false;
            }
            else {
                $subdomain = trim(substr($pattern, 0, $subdomainpos));
                $pattern = substr($pattern, $subdomainpos + 1);
                if (!empty($subdomain)) {
                    $isrelpath = false;
                    $prefix = $conf['protocol'] . $subdomain . '.' . $conf['rootdomain'];
                }
            }
        }
    
        // relative
        if ($isrelpath) {
            $prefix  = dirname($_SERVER['SCRIPT_NAME']) . '/';
            if (APP_NAME != 'default') {
                $prefix = dirname($prefix) . '/';
            }
            if ($prefix == '\\/' || $prefix == '//') {
                $prefix = '/';
            }
            if ($noalias) {
                $prefix = str_replace(D_ALIAS . '/', '', $prefix);
            }
        }

        // APP
        $appname = APP_NAME;
        if (IS_ALIAS) {
            $appname = D_ALIAS_APPEND;
        }
        if (($pos = stripos($pattern, ':')) !== false) {
            $appname = substr($pattern, 0, $pos);
            $appname = $appname == '' ? 'default' : $appname;
            $pattern = substr($pattern, $pos + 1);
        }
        $prefix .= $appname == 'default' ? '' : ($appname . '/');
        
        // suffix 
        if (substr($pattern, -1) == '~') {
            $suffix  = '';
            $pattern = substr($pattern, 0, -1);
        }
        
        $pattern = explode('{^_^}', str_replace(' ', '_', $pattern));
        
        if ($conf['type'] == '0') {
            $prefix .= $conf['rewrite'] ? '' : '?';
        }
        else {
            if ($conf['type'] == '1') {
                $prefix .= ($conf['rewrite']) ? '' : '?';
            }
            else if ($conf['type'] == '2'){
                $prefix .= ($conf['rewrite']) ? '' : 'index.php/';
            }
            $nums = count($pattern);
            for ($i = 0; $i < $nums; $i ++) {
                switch ($i) {
                    case 0:
                        $prefix .= $pattern[$i];
                        if ($nums > 1) {
                            $prefix .= $conf['MA'] ;
                        }
                        break;
                    case 1:
                        $prefix .= $pattern[$i];
                        if ($nums > 2 && !empty($pattern[$i])) {
                            $prefix .= $conf['AP'] ;
                        }
                        break;
                    default:
                        if ($i + 1 < $nums) {
                            if ($i > 2) {
                                $prefix .= $conf['PG'];
                            }
                            $prefix .= $pattern[$i]  . $conf['PP']
                                     . $pattern[$i + 1];
                            $i ++;
                        }
                        else {
                            $prefix .= $pattern[$i];
                        }
                        break;
                }
            }
            
        }
        return $prefix . $suffix;
    }

    /**
     * 实例化对象
     * @param unknown_type $class
     * @param unknown_type $single
     * @param unknown_type $extra
     * @return Ambigous <unknown>|unknown
     */
    public static function getobj ($class, $single = true, $extra = null) {
        static $objbus = array();
        if ($single) {
            if (!isset($objbus[$class])) {
                $objbus[$class] = new $class($extra);
            }
            return $objbus[$class];
        }
        return new $class($extra);
    }

    /**
     * 获取 ip 
     *
     * @return unknown
     */
    public static function getip () {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            }
            else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            }
            else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        }
        return filter_var(isset($ip) ? $ip : '0.0.0.0', FILTER_VALIDATE_IP);
    }

    /**
     * 创建文件夹
     *
     * @param String $dir
     */
    public static function mkdir ($dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0700, true)) {
                file_put_contents($dir . '/index.html', '', LOCK_EX);
            }
            else {
                throw_error(LANG('failed to create directory', core::relpath($dir)), 'FTCD', $dir);
            }
        }
    }

    /**
     * 获取相对路径 (仅供错误输出用)
     *
     * @param unknown_type $path
     * @return unknown
     */
    public static function relpath ($path) {
        return str_replace(BASE_PATH, '', $path);
    }

    /**
     * 获取随机字串
     * @param unknown_type $len
     * @return string
     */
    public static function randstr ($len = 4) {
        $ret = '';
        $randstr = '23456789abcdefghjkmnpqrstuvwxyABCDEFGHJKLMNPQRSTUVW';
        for ($i = 0; $i < $len; $i ++) {
            $ret .= $randstr[mt_rand(0, strlen($randstr) - 1)];
        }
        return $ret;
    }

    /**
     * 获取 GPCR 参数
     *
     * @param unknown_type $k
     * @param unknown_type $type
     * @return unknown
     */
    public static function getrp ($k, $type = 'R') {
        $var = null;
        $key = (array)explode(':', $k);
        switch (strtoupper($type)) {
            // GET
            case 'G' : $var = &$_GET; break;
            // POST
            case 'P' : $var = &$_POST; break;
            // COOKIE
            case 'C' : $var = &$_COOKIE; break;
            // REGINX ROUTE PARAM
            case 'R' : $var = &$GLOBALS['_RP']; break;
            // all
            case '*' :  break;
            // DEFAULT
            default : 
                throw_error(LANG('unknown parameter types', $type), 'UPT', 1);
                break;
        }
        if ($type != '*') {
            $ret = $var;
            foreach ($key as $v) {
                if (!isset($ret[$v])) {
                    break;
                }
                $ret = $ret[$v];
            }
            if ($ret == $var) {
                $ret = null;
            }
        }
        else {
            foreach (array($_POST, $_COOKIE, $GLOBALS['_RP']) as $v) {
                $ret = $v;
                foreach ($key as $val) {
                    if (!isset($ret[$val])) {
                        break;
                    } 
                    $ret = $ret[$val];
                }
                if ($ret != $v) {
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * 是否存在GPCR值
     *
     * @param unknown_type $k
     * @param unknown_type $type
     * @return unknown
     */
    public static function hasrp ($k, $type = 'R') {
        $var = null;
        $key = explode(':', $k);
        switch (strtoupper($type)) {
            // GET
            case 'G' : $var = &$_GET; break;
            // POST
            case 'P' : $var = &$_POST; break;
            // COOKIE
            case 'C' : $var = &$_COOKIE; break;
            // REGINX ROUTE PARAM
            case 'R' : $var = &$GLOBALS['_RP']; break;
            // all
            case '*' :  break;
            // DEFAULT
            default :
                throw_error(LANG('unknown parameter types', $type), 'UPT', 1);
                break;
        }
        if ($type != '*') {
            $ret = $var;
            foreach ($key as $v) {
                if (!isset($ret[$v])) {
                    break;
                }
                $ret = $ret[$v];
            }
            if ($ret == $var) {
                $ret = null;
            }
        }
        else {
            foreach (array($_POST, $_COOKIE, $GLOBALS['_RP']) as $v) {
                $ret = $v;
                foreach ($key as $val) {
                    if (!isset($ret[$val])) {
                        break;
                    }
                    $ret = $ret[$val];
                }
                if ($ret != $v) {
                    break;
                }
            }
        }
        return $ret !== null;
    }

    /**
     * 获取当前 Url 的路由字串
     *
     * @param unknown_type $url
     * @return unknown
     */
    public static function geturl () {
        $temp = array();
        $temp[] = $GLOBALS['_MOD'];
        $temp[] = $GLOBALS['_ACT'];
        if (isset($GLOBALS['_RP']) && !empty($GLOBALS['_RP'])) {
            foreach ($GLOBALS['_RP'] as $k => $v) {
                if (!empty($k) && !empty($v)) {
                    $temp[] = $k;
                    $temp[] = $v;
                }
            }
        }
        if (defined('D_ALIAS')) {
            return (D_ALIAS) . '.' . implode('-', $temp);
        }
        else {
            return (APP_NAME == 'default' ? '' : APP_NAME) . ':' . implode('-', $temp);
        }
    }

    /**
     * 获取大小
     *
     * @param string $val
     * @return Integer
     */
    public static function byteslen ($val) {
        $len  = floatval($val);
        switch (strtolower($val{strlen($val) - 1})) {
            case 'g': $len *= 1024;
            case 'm': $len *= 1024;
            case 'k': $len *= 1024;
        }
        return ceil($len);
    }

    /**
     * 解析生成缩略图对应的url
     * @param string $image
     * @param string $size
     * @return string
     */
    public static function thumburl ($image, $size = '') {
        $ret = 'null.gif';
        $size = $size == 'auto' ? '' : $size;
        // 完整的 url 不做处理
        if (strpos($image, 'http://') !== false) {
            $ret = $image;
        }
        // 带有 null.gif 的 url 不做处理
        else if (strpos($image, 'null.gif') !== false) {
            $ret = $image;
        }
        // foo/a.gif
        else if (!empty($image)) {
            $tmp = explode('.', $image);
            $ret = $tmp[0] . (empty($size) ? "_thumb." : ('.' . $size . '.')) . $tmp[1];
        }
        return $ret;
    }
    
    /**
     * 设置配置项目值
     *
     * @param unknown_type $app
     * @param unknown_type $key
     * @param unknown_type $value
     */
    public static function setconfig ($app, $key, $value) {
        $file = ($app == 'default' ? BASE_PATH : (BASE_PATH . $app . DS)). 'config' . DS . 'config.php';
        $data = include($file);
        $data[$key] = $value;
        // write
        $out  = "<?php" . PHP_EOL
              . "/**" . PHP_EOL
              . " * app " . APP_NAME ." config file " . PHP_EOL
              . " * @modified by reginx v" . REX_VER . PHP_EOL
              . " */" . PHP_EOL
              . " return " . var_export($data, true) . ";";
        // 写入文件
        file_put_contents($file, $out, LOCK_EX);

        // 更新配置文件
        $rtfile = CACHE_PATH . $app . DS . '~config.php';
        if (file_exists($rtfile)) {
            unlink($rtfile);
            clearstatcache();
        }
    }

    /**
     * 获取 app 配置信息
     *
     * @param unknown_type $app
     * @return unknown
     */
    public static function loadconfig ($app) {
        $file = ($app == 'default' ? BASE_PATH : (BASE_PATH . $app . DS)). 'config' . DS . 'config.php';
        if (is_file($file)) {
            return include($file);
        }
        return null;
    }
    
    /**
     * 删除目录及文件 (仅限 data 目录下)
     *
     * @param unknown_type $dir
     * @return unknown
     */
    public static function rmrf ($dir) {
        $nums = 0;
        if (is_dir($dir)) {
            $dir = realpath($dir) . DS;
            if ((strpos($dir, DATA_PATH) !== false && $dir != DATA_PATH) || (strpos($dir, '/dev/shm') !== false && $dir != '/dev/shm')) {
                foreach (glob($dir .'*') as $v) {
                    if (is_dir($v)) {
                        $nums += self::rmrf($v);
                    }
                    else {
                        unlink($v);
                        $nums ++;
                    }
                }
                rmdir($dir);
            }
        }
        return $nums;
    }
    
    /**
     * 获取系统运行产生的缓存目录
     *
     * @return unknown
     */
    public static function get_cache_list () {
        $ret = array();
        foreach (glob(CACHE_PATH . '/*') as $k => $v) {
            if (is_dir($v)) {
                $ret['cache'][] = basename($v);
            }
        }
        foreach (glob(TEMP_PATH . '/*') as $k => $v) {
            if (is_dir($v)) {
                $ret['temp'][] = basename($v);
            }
        }
        return $ret;
    }
    
    /**
     * 时长格式化
     *
     * @param unknown_type $ts
     * @param unknown_type $sdate
     * @return unknown
     */
    public static function duration ($ts, $sdate = null) {
        $ret   = '';
        $sdate = empty($sdate) ? REQUEST_TIME : $sdate;
        $years = (int)((($sdate - $ts) / (7 * 86400)) / 52.177457);
        $rem   = (int)(($sdate - $ts) - ($years * 52.177457 * 7 * 86400));
        $weeks = (int)(($rem) / (7 * 86400));
        $days  = (int)(($rem) / 86400) - $weeks * 7;
        $hours = (int)(($rem) / 3600) - $days * 24 - $weeks * 7 * 24;
        $mins  = (int)(($rem) / 60) - $hours * 60 - $days * 24 * 60 - $weeks * 7 * 24 * 60;
        if ($years == 1) {
            $ret .= "$years year, ";
        }
        if ($years > 1) {
            $ret .= "$years years, ";
        }
        if ($weeks == 1) {
            $ret .= "$weeks week, ";
        }
        if ($weeks > 1) {
            $ret .= "$weeks weeks, ";
        }
        if ($days == 1) {
            $ret .= "$days day,";
        }
        if ($days > 1) {
            $ret .= "$days days,";
        }
        if ($hours == 1) {
            $ret .= " $hours hour and";
        }
        if ($hours > 1) {
            $ret .= " $hours hours and";
        }
        if ($mins == 1) {
            $ret .= " 1 minute";
        }
        else {
            $ret .= " $mins minutes";
        }
        return $ret;
    }
    
    
    /**
     * 字串解密
     *
     * @param unknown_type $str
     * @param unknown_type $date
     * @return unknown
     */
    public static function rex_decode ($str, $date = null) {
        $ret  = false;
        $str  = base64_decode($str);
        if ($str) {
            $info = unpack('nlen/Ndate', substr($str, 0, 6));
            if ($date === null || $info['date'] >= $date) {
                $ret  = unpack('S' . $info['len'], substr($str, 6));
                foreach($ret as $k => $v) {
                    $ret[$k] = chr($v);
                }
                $ret = join('', $ret);
            }
        }
        return $ret; 
    }
    
    /**
     * 字串加密
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function rex_encode ($str, $date = null) {
        $len = strlen($str);
        $ret = pack("nN", $len, empty($date) ? strtotime(date('Y-m-d')) : $date);
        for ($i = 0; $i < $len; $i ++) {
            $ret .= pack("S", ord($str{$i}));
        }
        return base64_encode($ret);
    }
} // end class

/**
 * lang 
 *
 * @return unknown
 */
function LANG () {
    if (!isset($GLOBALS['_TPL'])) {
        $GLOBALS['_TPL'] = template::getobj($GLOBALS['_RC']['tpl']);
    }
    return $GLOBALS['_TPL']->lang(func_get_args());
}

/**
 * 获取对象 (默认单例)
 *
 * @param unknown_type $class
 * @param unknown_type $single
 * @param unknown_type $extra
 * @return unknown
 */
function OBJ ($class, $single = true, $extra = null) {
    return core::getobj($class, $single, $extra);
}

/**
 * 错误
 *
 * @param unknown_type $msg
 * @param unknown_type $from
 * @param unknown_type $log
 * @param unknown_type $extra
 */
function throw_error ($msg, $code, $record = false, $extra = '') {
    new error($msg, $code, $record, $extra);
}
?>
