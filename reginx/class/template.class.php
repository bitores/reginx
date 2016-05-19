<?php
/**
 * 模板标签处理
 * @copyright reginx.com
 * $Id: template.class.php 5268 2015-09-29 10:56:30Z reginx $
 */
class template {

    /**
     * 配置
     *
     * @var unknown_type
     */
    private $_conf = array(
        'ob'       => true,
        'native'   => false,
        'style'    => 'default',
        'tpl_pre'  => '{',
        'tpl_suf'  => '}',
        'cmod'     => false,
        'lang'     => 'zh-cn',
        'charset'  => 'utf-8',
        'allow_php' => false
    );

    /**
     * 数据变量
     *
     * @var unknown_type
     */
    private $_vars = array();

    /**
     * 当前模板原文件
     *
     * @var unknown_type
     */
    private $_ctplfile = 'unkown';

    /**
     * 获取但单例模板引擎对象
     *
     * @param array $conf
     * @return object
     */
    public static function getobj ($conf = array()) {
        static $tplobj = null;
        if (empty($tplobj)) {
            $tplobj = new template($conf);
        }
        return $tplobj;
    }

    /**
     * 删除临时文件
     *
     * @param unknown_type $app
     * @return unknown
     */
    public static function flush ($app = 'default') {
        return core::rmrf(TEMP_PATH . $app);
    }

    /**
     * 架构函数
     *
     * @param array $conf
     */
    private function __construct ($conf = array()) {
        if (!empty($conf)) {
            $this->_conf = array_merge($this->_conf, $conf);
        }
        $this->_conf['app_name'] = IS_ALIAS ? D_ALIAS_ID : APP_NAME;

        if (!is_dir(TEMP_PATH . $this->_conf['app_name'])) {
            core::mkdir(TEMP_PATH . $this->_conf['app_name']);
        }
        // 输出的缓存目录
        $this->_conf['out_dir'] = realpath(TEMP_PATH . $this->_conf['app_name']) . DS;
        // 源模板文件所在目录
        if (defined('D_ALIAS_TPL')) {
            $this->_conf['tpl_dir'] = IS_ALIAS && IS_ALIAS_TPL ? D_ALIAS_TPL : TPL_PATH;
        }
        else {
            $this->_conf['tpl_dir'] = TPL_PATH;
        }

        plugin::notify('TPL_INIT', 0, $this);
    }

    /**
     * 设置/获取 style 值
     *
     * @param unknown_type $val
     * @return unknown
     */
    public function style ($val = null) {
        if (!empty($val)) {
            $this->_conf['style'] = $val;
        }
        else {
            return $this->_conf['style'];
        }
    }

    /**
     * 数据变量赋值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     */
    public function assign ($key, $value) {
        if (($pos1 = strpos($key, '[')) !== false && ($pos2 = strpos($key, ']')) !== false) {
            $skey = substr($key, $pos1 + 1, $pos2 - $pos1 - 1);
            $key = substr($key, 0, $pos1);
            if ($skey == '') {
                $this->_vars[$key][] = $value;
            }
            else {
                $this->_vars[$key][$skey] = $value;
            }
        }
        else {
            $this->_vars[$key] = $value;
        }
    }

    /**
     * 输出模板
     *
     * @param unknown_type $file
     * @param unknown_type $ctype
     * @param unknown_type $obuf
     */
    public function display ($file, $ctype = "text/html") {
        header('X-Powered-By: REGINX v' . REX_VER);
        header("Content-Type: " . $ctype . "; charset=utf-8");
        exit($this->fetch($file, false));
    }

    /**
     * 获取模板输出
     *
     * @param string $file
     * @param boolean $outbuf
     * @return string
     */
    public function fetch ($file, $outbuf = true) {
        if (IS_ALIAS && D_ALIAS_APPEND == 'default') {
            //define ('CTPL_URL', BASE_URL . 'template/' . $this->_conf['style'] . '/');
            define ('CTPL_URL', BASE_URL . APP_NAME . '/template/' . $this->_conf['style'] . '/');
        }
        else if (IS_ALIAS && D_ALIAS_APPEND != 'default') {
            define ('CTPL_URL', BASE_URL . D_ALIAS_APPEND . '/template/' . $this->_conf['style'] . '/');
        }
        else {
            define ('CTPL_URL', APP_URL . 'template/' . $this->_conf['style'] . '/');
        }
        // 原始文件路径
        $this->_ctplfile = $sfile = $this->_getsfilepath($file);

        // 非Debug模式下. 清空一切非模板输出
        if (RUN_MODE != 'debug' && $outbuf) {
            ob_end_clean();
        }
        // 默认开启缓冲
        if ($this->_conf['ob']) {
            ob_start();
            ob_implicit_flush(0);
        }
        extract($this->_vars);
        unset($this->_vars);

        // 使用原生 php 作为模板文件
        if ($this->_conf['native']) {
            $cfile = $this->_ctplfile;
        }
        // 使用模板文件
        else {
            // 缓存文件路径
            $cfile = $this->_getcfilepath($file);
            // create temp dir
            if (!is_dir(dirname($cfile))) {
                core::mkdir(dirname($cfile));
            }
            // 检测模板是否过期
            if (RUN_MODE == 'debug' || $this->_chkexpired($cfile, $sfile)) {
                $this->_parsetpl($cfile, $sfile);
            }
        }

        include ($cfile);

        if ($this->_conf['ob'] && $outbuf) {
            $content = ob_get_clean();
            plugin::notify('TPL_OB_END', $content);
            return $content;
        }
    }

    /**
     * 解析 模板文件
     *
     * @param string $tmpfile
     * @param string $sfile
     */
    private function _parsetpl ($tmpfile, $sfile) {
        $html = file_get_contents($sfile);
        // replace <!--{,}--> to { , }
        $html = preg_replace(
                '/\<\!\-\-' . preg_quote($this->_conf['tpl_pre']) .
                         '\s*(.+?)\s*' . preg_quote($this->_conf['tpl_suf']) .
                         '\-\-\>/i', '{\1}', $html);
        // include abc.tpl.html
        $this->_getincfile($html);

        if (!$this->_conf['allow_php']) {
            $html = preg_replace('/<\?php.+\?>/i', '' , $html);
        }
        // note
        $html = preg_replace('/\s*\{\s*\/\/.+?\s*\}\s*/i', "\n", $html);

        //$this->_merge_files($html);

        // $var
        $html = preg_replace_callback('/\{(\$[^\}]*?)\}/i',
                array($this, '_parsevar'), $html);
        // foreach
        $html = preg_replace_callback(
                '/\{\s*foreach\s+(\$[^\s]+)\s+(\$[\w]+)\s+(\$?[\w]+)\s*\}/i',
                array($this, '_parseforeach'), $html);
        // endforeach
        $html = preg_replace('/\{\s*\/foreach\s*\}/i', '<?php endforeach;?>', $html);
        // break
        $html = preg_replace('/\{\s*break\s*\}/i', '<?php break;?>', $html);
        // if
        $html = preg_replace('/\{[\040\t]*if\s+(.+?)\s*\}/i', '<?php if (\1):?>', $html);
        // else if
        $html = preg_replace('/\{\s*else\s*if\s+(.*?)\s*\}/i', '<?php elseif (\1):?>', $html);
        // else
        $html = preg_replace('/\{\s*else\s?\s*\}/i', '<?php else:?>', $html);
        // end if
        $html = preg_replace('/\{\/if\s*?\}\s*/i', '<?php endif;?>', $html);
        // constant
        $html = preg_replace_callback('/\{\s?__([\w\-_]*?)__\s?\}/is',
                array($this, '_parseconstant'), $html);
        // for $x in range(0 , 10 , 1)
        $html = preg_replace(
                '/\{[\040\t]?for\s+\$?(.*?)\s+in\s+range\(\s*([^\s\,]*?)\s*,\s*([^\s\,]*?)\s*,\s*([^\s]*?)\s*\)\s*\}/i',
                '<?php for($\1=\2;(\3>\2 ? ($\1<\3) : ($\1>\3));$\1+=\4):?>',
                $html);
        // for $x in range(0 , 10)
        $html = preg_replace(
                '/\{\s?for\s+\$?(.*?)\s+in\s+range\(\s*([^\s\,]*?)\s*,\s*(.*?)\)\s*\}/i',
                '<?php for($\1=\2;$\1<\3;$\1++):?>', $html);
        // end for
        $html = preg_replace('/\{\/for\s*\}/i', '<?php endfor;?>', $html);
        // lang
        $html = preg_replace_callback('/\{lang\s*\:\s*(.*?)\}/is', array($this, '_parselang'), $html);
        // function
        $html = preg_replace('/\{\s*:(.*?)\s*?\}/i', '<?php echo(\1);?>', $html);
        // dynamic url
        $html = preg_replace('/\{\s*url\:\(?(.*?)\)?\}/i', '<?php echo(core::url(\\1)); ?>', $html);
        // static url
        $html = preg_replace_callback('/\{\s*surl\:\(?(.*?)\)?\}/i', array($this, '_parseurl'), $html);
        // execute time tag
        $html = preg_replace('/\{\s?\@TIME(.*?)\s*?\}/i',
                '<?php printf("%.3f",(microtime(TRUE) - $GLOBALS["_STAT"]["STIME"])\1); ?>',
                $html);
        // dynamic constant
        $html = preg_replace('/\{\s?_([\w\-_]+?)_\s?\}/is',
                '<?php echo(isset($GLOBALS["_RC"][strtolower("\1")]) ? $GLOBALS["_RC"][strtolower("\1")] : strtolower("\1")); ?>',
                $html);
        // token
        $html = preg_replace('/\{\s*token\s+[\'\"]?(.*?)[\'\"]?\s*\}/is', '<?php echo($GLOBALS[\'_CMOD\']->token("\\1")); ?>', $html);
        // eval
        $html = preg_replace('/\{\s*eval\s+(.*?)\s*\}/is', '<?php \1?>', $html);
        // hook
        $html = preg_replace('/\{\s*hook\:\(?(.*?)\)?\}/i',
                '<?php plugin::notify(\\1); ?>', $html);
        // block
        $html = preg_replace(
                '/\{block\s*\:\s*\$([^,\}]+)\s*,\s*([^\}]+)\}/is',
                '<?php \$\1 = block::getdata(\'\2\'); ?>', $html);
        $html = preg_replace('/(\s)+\<\?php(.*?)\?\>/i', "\\1<?php\\2?>", $html);
        $html = preg_replace('/\<\?php(.*?)\?\>(\s)+/i', "<?php\\1?>\\2", $html);
        $html = "<?php !defined('IN_REGINX') && exit('Access Denied'); unset(\$this);?>" . $html;
        file_put_contents($tmpfile, $html, LOCK_EX);
    }

    /**
     * 解析 url 标签
     *
     * @param unknown_type $matches
     * @return unknown
     */
    private function _parseurl ($matches = array()) {
        $str = trim(str_replace(array( "'", "\""), '', $matches[1]));
        return call_user_func_array(array('core', 'url'), array_map('trim', explode(',', $str)));
    }

    /**
     * 解析 语言 标签
     *
     * @param unknown_type $matches
     * @return unknown
     */
    private function _parselang ($matches = array()) {
        $expression = trim($matches[1]);
        if (substr($expression, 0, 1) == '$') {
            return "<?php echo(\$GLOBALS['_TPL']->lang($expression)); ?>";
        }
        return $this->lang($expression);
    }

    /**
     * 解析 常量 标签
     *
     * @param unknown_type $matches
     * @return unknown
     */
    private function _parseconstant ($matches = array()) {
        // 优先返回常量值
        if (defined(strtoupper($matches[1]))) {
            return constant(strtoupper($matches[1]));
        }
        else if (isset($GLOBALS['_RC'][strtolower($matches[1])]) &&
                 is_string($GLOBALS['_RC'][strtolower($matches[1])])) {
            return $GLOBALS['_RC'][strtolower($matches[1])];
        }
        else {
            throw_error(
                    LANG('template undefined constant', core::relpath($this->_ctplfile), $matches[1])
                    , '', true);
        }
    }

    /**
     * 解析 foreach 标签
     *
     * @param unknown_type $matches
     * @return unknown
     */
    private function _parseforeach ($matches = array()) {
        $ret = "<?php unset({$matches[2]} , {$matches[3]}); {$matches[2]}_index = 0; ";
        $ret .= "foreach ((array){$matches[1]} as {$matches[2]} => {$matches[3]}): ";
        return $ret . "{$matches[2]}_index ++;?>";
    }

    /**
     * 解析 变量 标签
     *
     * @example $foo , $foo ? 1 : 2 , $foo|cut,'200','' ,
     *          $foo|thumb,'200x200',$foo|html,$foo|qhtml
     *          $foo|date , 'Y-m-d H:i:s'
     * @param mixed $matches
     * @return string
     */
    private function _parsevar ($matches) {
        $allows = array( 'cut', 'thumb', 'html', 'qhtml', 'date', 'amount');
        $tag = trim($matches[1]);
        if (empty($tag)) {
            throw_error(LANG('tpl-syntax-error', str_replace(BASE_PATH, '', $this->_ctplfile)),
                     4, 'tpl::fetch', 0);
        }
        $tag = explode('|', $tag);
        $var = trim(array_shift($tag));
        foreach ((array) $tag as $k => $v) {
            $temp = explode(',', trim($v));
            $temp[0] = trim($temp[0]);
            if (empty($temp[1]) && $temp[0] == 'thumb') {
                $var = "core::thumburl({$var})";
            }
            if ($temp[0] == 'amount') {
                $var = "template::format_amount({$var}" . (isset($temp[1]) ? ", '{$temp[1]}'" : '') . ")";
            }
            if (empty($temp[1]) && $temp[0] == 'date') {
                $var = "date('Y-m-d' , {$var})";
            }
            if (empty($temp[1]) && $temp[0] == 'html') {
                $var = "htmlspecialchars_decode({$var} , ENT_QUOTES)";
            }
            if (empty($temp[1]) && $temp[0] == 'qhtml') {
                $var = "htmlspecialchars({$var} , ENT_QUOTES)";
            }
            if (isset($temp[1]) && ! empty($temp[1])) {
                $temp[0] = trim($temp[0]);
                if (! in_array($temp[0], $allows)) {
                    throw_error(LANG('invalid variable processing tag', $temp[0], core::relpath($this->_ctplfile)),
                        'TSE', 1, __METHOD__);
                }
                if ($temp[0] == 'thumb') {
                    $var = "core::thumburl({$var} , " .
                             (empty($temp[1]) ? null : ("'" . trim($temp[1]) .
                             "'")) . ")";
                }
                if ($temp[0] == 'cut') {
                    $temp[1] = intval($temp[1]);
                    $var = "mb_substr(filter::text({$var}) , 0 , {$temp[1]} , 'utf-8')";
                }
                if ($temp[0] == 'date') {
                    $var = "date('{$temp[1]}' , {$var})";
                }
            }
        }
        return "<?php echo($var);?>";
    }

    /**
     * 解析 include 标签
     *
     * @param ref $html
     * @param integer $limit
     */
    private function _getincfile (&$html, $limit = 1) {
        $files = $tmp = array();
        preg_match_all('/\{\s*?include\s+(.+?)\s*?\}/i', $html, $files);
        for ($j = 0; $j < sizeof($files[1]); $j ++) {
            $repl = array();
            // 新增 include 传参特性
            // {include abc.tpl.html #param0, param1, param2} 参数不限
            // 调用方式 ${0}, ${1}, ${2} ...
            if (($pos = strpos($files[1][$j], '#')) !== false) {
                $file = $this->_getsfilepath(substr($files[1][$j], 0, $pos - 1));
                $param = explode(',', trim(substr($files[1][$j], $pos + 1)));
                if (!empty($param)) {
                    // 动态参数列表
                    foreach ($param as $k => $v) {
                        $repl['${' . $k . '}'] = trim($v);
                        // 变量
                        if (substr($repl['${' . $k . '}'], 0, 1) == '$') {
                            $repl['${' . $k . '}'] = '{' . $repl['${' . $k . '}'] . '}';
                        }
                    }
                }
            }
            else {
                $file = $this->_getsfilepath($files[1][$j]);
            }
            if (!file_exists($file) ) {
                throw_error(LANG('file not found', core::relpath($file)), 'TFNF', 1, __METHOD__);
            }
            $temp = file_get_contents($file);
            $temp = preg_replace(
                    '/\s*\<\!\-\-' . preg_quote($this->_conf['tpl_pre']) .
                             '\s*(.+?)\s*' . preg_quote($this->_conf['tpl_suf']) .
                             '\-\-\>\s*/i', '{\1}', $temp);
            if (!empty($repl)) {
                $temp = str_replace(array_keys($repl), $repl, $temp);
            }
            // 默认填充 (防止出现语法错误)
            else {
                $temp = preg_replace('/\$\{\d+\}/', 'nil', $temp);
            }
            $html = str_replace($files[0][$j], $temp, $html);
            $temp = null;
            unset($temp);
        }
        // max limit 3
        if (preg_match('/\{\s*?include\s+(.*?)\s*?\}/i', $html) && $limit <= 3) {
            $this->_getincfile($html, $limit + 1);
        }
    }

    /**
     * 检查缓存模板文件是否可用
     *
     * @param unknown_type $tmpfile
     * @param unknown_type $sfile
     * @return unknown
     */
    private function _chkexpired ($tmpfile, $sfile) {
        $ret = false;
        if (!is_file($tmpfile) || !file_exists($tmpfile)) {
            $ret = true;
        }
        return $ret ? $ret : filemtime($tmpfile) < filemtime($sfile);
    }

    /**
     * 解析模板文件路径
     *
     * @example index.html , default:index.html , admin@default:index.html
     * @abstract APP@style:tplfile
     * @param string $sfile
     * @return string
     */
    private function _getsfilepath ($sfile) {
        $fpath = $this->_conf['tpl_dir'];
        if (($pos = strpos($sfile, '@')) !== false) {
            if ($pos > 0) {
                $app = substr($sfile, 0, $pos);
                $fpath = BASE_PATH . $app . '/template' . DS;
                $sfile = substr($sfile, $pos + 1, strlen($sfile));
            }
            // @default:abc.tpl.html
            else {
                $fpath = BASE_PATH . 'template' . DS;
                $sfile = substr($sfile, 1, strlen($sfile));
            }
        }
        if (($pos = strpos($sfile, ':')) !== false) {
            $fpath .= substr($sfile, 0, $pos) . DS;
            $sfile = substr($sfile, $pos + 1, strlen($sfile));
        }
        else {
            $fpath .= $this->_conf['style'] . DS;
        }
        $fpath = $fpath . $sfile;
        if ($fpath === false || ! is_file($fpath)) {
            throw_error(LANG('file not found', core::relpath($fpath)), 'FNF', 1,  __METHOD__);
        }
        return realpath($fpath);
    }

    /**
     * 语言解析
     */
    public function lang ($args) {
        $keys = is_array($args) ? $args : explode(',',
                preg_replace('/[\'|\"]*/', '', $args));
        if (!empty($keys)) {
            $keys = array_map('trim', $keys);
            for ($i = 0; $i < sizeof($keys); $i ++) {
                if (!empty($keys[$i]) && $keys[$i] != '') {
                    $keys[$i] = isset($GLOBALS['_RL'][$keys[$i]]) ? $GLOBALS['_RL'][$keys[$i]] : $keys[$i];
                }
            }
            $format = array_shift($keys);
            $flen = sizeof(preg_split('/\%\w/i', $format));
            // 使数组元素总数与占位符数目相对应
            while (sizeof($keys) < $flen) {
                $keys[] = '';
            }
            return vsprintf($format, $keys);
        }
        return empty($keys) ? '' : join('-', $keys);
    }

    /**
     * 设置当前模块名称
     *
     * @param unknown_type $mod
     */
    public function setcmod ($mod = false) {
        $this->_conf['cmod'] = (bool) $mod;
    }

    /**
     * 获取模板文件对应的缓存文件路径
     *
     * @param unknown_type $file
     * @return unknown
     */
    private function _getcfilepath ($file) {
        $mod = $this->_conf['cmod'] ? $this->_conf['cmod'] : $GLOBALS['_MOD'];
        $key = $this->_conf['lang'] . '_' . $mod . '_' . $this->_conf['style'];
        return $this->_conf['out_dir'] .
                 sprintf('%X', crc32(APP_NAME . $this->_conf['tpl_dir'] . $key . $file))
                     .'.php';
    }

    /**
     * 金额格式化
     *
     * @param unknown_type $file
     * @return unknown
     */
    public static function format_amount ($val, $unit = 'k') {
        if (!empty($val)) {
            switch (strtolower(trim($unit))) {
                case 'm':
                    $len = 4;
                    break;
                default :
                    $len = 3;
                    break;
            }
            $tmp = explode('.', $val);
            return strrev(join(' ,', str_split(strrev($tmp[0]), $len))) . (isset($tmp[1]) ? ".{$tmp[1]}" : $tmp[1]);
        }
        return 'nul';
    }
}
?>