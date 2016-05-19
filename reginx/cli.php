<?php
class reginx {
    
    /**
     * 初始化
     *
     * @param unknown_type $appname
     */
    public function __construct() {
        // 开始时间 (ms)
        define('START_TIME', isset($_SERVER["REQUEST_TIME_FLOAT"]) ?
            floatval($_SERVER["REQUEST_TIME_FLOAT"]) : microtime(true));

        // 当前时间 (s)
        define('REQUEST_TIME', isset($_SERVER['REQUEST_TIME']) ?
            intval($_SERVER['REQUEST_TIME']) : time());

        // 请求是否合法
        if (!defined('IN_REGINX')) {
            header('HTTP/1.1 403 Access Forbidden');
            exit ('Access Forbidden !');
        }
        
        // 版本检测
        if (version_compare(PHP_VERSION, '5.3.0' ) < 0) {
            exit( "Requires at least version PHP v5.3" );
        }
        
        if (!defined('RUN_MODE')) {
            define('RUN_MODE', 'normal');
        }
        
        if (RUN_MODE == 'debug') {
            // 开启输出缓冲
            ob_start();
            error_reporting(E_ALL ^ E_NOTICE);
        }
        else {
            error_reporting(0);
        }

        // 初始化环境
        $this->_initenv();

        // 缓存操作对象
        $GLOBALS['_CACHE'] = cache::getobj($GLOBALS['_RC']['cache']);

        // 模板操作对象
        $GLOBALS['_TPL'] = template::getobj($GLOBALS['_RC']['tpl']);

        $mod = $GLOBALS['_MOD'] . '_module';
        $act = $GLOBALS['_ACT'] . 'Action';
        
        if (preg_match(
                '/[~|\!|@|#|\$|%|\^|&|\*|\(|\)|\-|\=|\+|\{|\}|\[|\]|\||\\|\:|;|\"|\'|\<|\>|,|\.|\?|\/]+/', $mod)) {
            throw_error(LANG('class name is invalid'), 'CNII');
        }
        $GLOBALS['_CMOD'] = new $mod();

        // 404
        if(!method_exists($GLOBALS['_CMOD'], $act) && !method_exists($GLOBALS['_CMOD'], '__call')) {
            $GLOBALS['_CMOD']->show404(LANG('module action does not exist', $act));
        }
        else {
            $GLOBALS['_CMOD']->$act();
        }
    }
    
    /**
     * 初始化运行环境
     *
     */
    private function _initenv () {
        define('REX_VER', '1.0.0');
        define('REX_PATH', __DIR__ . DS);
        define('MOD_PATH', APP_PATH . 'module' . DS);
        define('PLU_PATH', APP_PATH . 'plugin' . DS);
        define('TPL_PATH', APP_PATH . 'template' . DS);
        define('TEMP_PATH',  DATA_PATH . 'temp' . DS);
        define('CACHE_PATH', DATA_PATH . 'cache' . DS);
        // reginx config
        $this->_loadconfig();
        // core files
        $this->_loadfiles();
        // auto load
        spl_autoload_register(array('core', 'loader'));
        // router init
        core::cliparse();
        // app url
        define('APP_URL', core::getappurl());
        define('BASE_URL',  APP_NAME == 'default' ? APP_URL : (dirname(APP_URL) . '/'));
        // timezone
        date_default_timezone_set('Etc/GMT' . ($GLOBALS['_RC']['time_offset'] > 0 ? '-' : '+')
             . (abs($GLOBALS['_RC']['time_offset'])));
        
    }
    
    /**
     * 加载app配置信息
     *
     */
    private function _loadconfig () {
        $file = CACHE_PATH . APP_NAME . DS . '~config.php';
        if (!is_file($file) || RUN_MODE == 'debug') {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755);
            }
            $config = include (REX_PATH . 'config' . DS . 'reginx.php');
            if (is_file(APP_PATH . 'config' . DS . 'reginx.php')) {
                $config = array_merge($config, include (APP_PATH . 'config' . DS . 'reginx.php'));
            }
            // app config
            if (is_file(APP_PATH . 'config' . DS . 'config.php')) {
                $config = array_merge($config, include (APP_PATH . 'config' . DS . 'config.php'));
            }
            // write
            $out  = "<?php" . PHP_EOL
                  . "/**" . PHP_EOL
                  . " * app " . APP_NAME ." config file " . PHP_EOL
                  . " * @created by reginx v" . REX_VER . PHP_EOL
                  . " */" . PHP_EOL
                  . " return " . var_export($config, true) . ";";
            file_put_contents($file, $out, LOCK_EX);
        }
        $GLOBALS['_RC'] = include ($file);
    }
    
    /**
     * 加载文件
     *
     */
    private function _loadfiles () {
        $files = glob(REX_PATH . 'class/*.php');
        // DEBUG 模式动态加载
        if (RUN_MODE == 'debug') {
            $files[] = REX_PATH . 'extra' . DS . 'lang'  . DS . 'default.' . $GLOBALS['_RC']['lang'] . '.php';
            // 加载框架核心文件
            foreach ($files as $file) {
                include ($file);
            }
        }
        // 生成 临时文件
        else {
            $runtimefile = CACHE_PATH . APP_NAME . DS . '~runtime.php';
            if (!is_file($runtimefile) || !file_exists($runtimefile)) {
                $out = '<?php' . PHP_EOL;
                // 基本库, 配置所需的驱动库及语言包
                $files[] = REX_PATH . 'extra' . DS . 'lang'  . DS . 'default.' . $GLOBALS['_RC']['lang'] . '.php';
                $files[] = REX_PATH . 'extra' . DS . 'cache' . DS . $GLOBALS['_RC']['cache']['type'] . '.cache.php';
                $files[] = REX_PATH . 'extra' . DS . 'db'    . DS . $GLOBALS['_RC']['db']['type'] . '.db.php';
                $files[] = REX_PATH . 'extra' . DS . 'sess'  . DS . $GLOBALS['_RC']['sess']['type'] . '.sess.php';
                
                $index = (int)array_search(RUN_MODE, array('debug', 'mini', 'normal', 'max', 'full'));
                
                // normal, mini 基础上附加 block 标签库
                if ($index > 1) {
                    $files = array_merge(
                        $files,
                        glob(INC_PATH . 'block' . DS . '*.block.php')
                    );
                }
                
                // max, normal 基础上附加 table 模型文件, lib 扩展库文件
                if ($index > 2) {
                    $files = array_merge(
                        $files,
                        glob(INC_PATH . 'table' . DS . '*.table.php'),
                        glob(INC_PATH . 'lib' . DS . '*.lib.php')
                    );
                }
                
                // full , max 基础上附加 app module 模块文件
                if ($index > 3) {
                    $files = array_merge(
                        $files,
                        glob(APP_PATH . 'module' . DS . '*.module.php')
                    );
                }
                
                foreach ($files as $file) {
                    $gets = trim(file_get_contents($file));
                    if (substr($gets, 0, 5) == '<?php') {
                        $gets = substr($gets, 5);
                    }
                    if (substr($gets, -2) == '?>') {
                        $gets = substr($gets, 0, -2);
                    }
                    $out .= trim($gets) . PHP_EOL;
                }
                
                // 创建 缓存目录
                if (!is_dir(CACHE_PATH)) {
                    mkdir(CACHE_PATH, 0755);
                }
                // 创建 缓存 app 目录
                if (!is_dir(CACHE_PATH . APP_NAME)) {
                    mkdir(CACHE_PATH . APP_NAME, 0755);
                }
                // 写入运行库文件
                if (!file_put_contents($runtimefile, preg_replace('/\?\>\s*\<\?php\s+/is', "\r\n", $out), LOCK_EX)) {
                    exit('Failed to create a runtime file ');
                }
                $out = null;
                file_put_contents($runtimefile, php_strip_whitespace($runtimefile), LOCK_EX);
            }
            include ($runtimefile);
        }
        plugin::notify('REX_LOAD', 0);
    }
}