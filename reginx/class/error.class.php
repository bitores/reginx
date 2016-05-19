<?php
/**
 * 错误异常类
 * @copyright reginx.com
 */
class error {
    
    public function __construct($msg, $code = 'unkown', $log = false, $extra = '') {
        if ($log || IS_CLI) {
            $this->log($msg, $code, $extra, IS_CLI);
        }
        
        if (RUN_MODE != 'debug') {
            ob_end_clean();
        }
        
        header('X-Powered-By: REGINX v' . REX_VER);
        header('HTTP/1.1 404 Internal Server Error');
        
        $temp = '<!DOCTYPE html><html><head><title>reginx error </title>' .
                 '<meta charset="utf-8"/>' .
                 '<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" /><style type="text/css">body { background-color:' .
                 ' white; color: black;font: 12px verdana, arial, sans-serif;text-align:left !important;}' .
                 ' #container { width: 100%;} #message {background-color:' .
                 ' #FFFFCC; } #bodytitle {vertical-align: top; } b{font-weight:normal}' .
                 ' .help  {font-size:12px; color: red;} .red {color: red;} .trace{font-size:11px;}' .
                 ' a:link { font-size:12px; color: red; } a:visited{font-size:11px; color: #4e4e4e; ' .
                 '} </style></head><body><table cellpadding="1" cellspacing="5" ' .
                 'id="container"><tr><td class="bodytext"><br/>error' .
                 ' messages: </td></tr><tr><td class="bodytext" id="message"><ul style="padding-top:3px\9;*padding-top:10px;"><li>' .
                 (is_array($msg) ? join('</li><li>', $msg) : $msg) .
                 ' &nbsp;&nbsp; </li></ul></td></tr>' . '<TRACE/>' .
                 '<tr><td class="help"><a href="' . APP_URL .
                 '">' . $_SERVER['HTTP_HOST'] . '</a> ' . LANG('error has been recorded') .
                 ' </td></tr></table></body></html>';
        if (RUN_MODE == 'debug') {
            $trace = debug_backtrace();
            $str = '<tr><td class="bodytext"><br/>trace information: <br/><ul class="trace">';
            for ($i = 2; $i < sizeof($trace); $i ++) {
                $str .= '<li>[ #' . ($i - 2) . ' line:' .
                         sprintf('%04d', empty($trace[$i]['line']) ? 0 : $trace[$i]['line']) . ' ] ';
                $str .= ' file: ' . core::relpath($trace[$i]['file']);
                $str .= ' ' . (empty($trace[$i]['class']) ? '' : $trace[$i]['class']);
                $str .= (empty($trace[$i]['type']) ? '' : $trace[$i]['type']);
                $str .= (empty($trace[$i]['function']) ? '' : $trace[$i]['function']) .
                         '()</li>';
            }
            $temp = str_replace('<TRACE/>', $str . '</ul></td></tr>', $temp);
        }
        exit($temp);
    }
    
    /**
     * 错误日志
     *
     * @param unknown_type $error
     * @param unknown_type $code
     * @param unknown_type $extra
     */
    public function log ($error, $code = 'unkwon', $extra = '', $output = false) {
        if (!is_dir(DATA_PATH . 'log/')) {
            core::mkdir(DATA_PATH . 'log/');
        }
        if (!is_dir(DATA_PATH . 'log/' . APP_NAME)) {
            core::mkdir(DATA_PATH . 'log/' . APP_NAME);
        }
        
        $msg  = "<?php" . PHP_EOL
            . "/**" . PHP_EOL
            . " * @ip   " . core::getip() . PHP_EOL
            . " * @url  {$_SERVER['REQUEST_URI']}" . PHP_EOL
            . " * @code {$code}" . PHP_EOL
            . " * @date " . (date('Y-m-d H:i:s ', REQUEST_TIME)) . PHP_EOL
            . " * @desc " . $error . PHP_EOL
            . " * @extra {$extra}" . PHP_EOL . PHP_EOL;
        
        /// trace info
        $trace = debug_backtrace();
        for ($i = 2; $i < count($trace) - 1; $i ++) {
            $msg .= " # " . ($i - 2) . ' line:' .
                     sprintf('%04d', empty($trace[$i]['line']) ? 0 : $trace[$i]['line']);
            $msg .= ' file : ' . core::relpath($trace[$i]['file']);
            $msg .= ' ' . (empty($trace[$i]['class']) ? '' : $trace[$i]['class']);
            $msg .= (empty($trace[$i]['type']) ? '' : $trace[$i]['type']);
            $msg .= (empty($trace[$i]['function']) ? '' : $trace[$i]['function']) . "(";
            $msg .= empty($trace[$i]['args']) ? '' : join(',', $trace[$i]['args']);
            $msg .= ")" . PHP_EOL;
        }
        
        if ($output) {
            exit ($msg);
        }
        
        $fp  = fopen(DATA_PATH . 'log/' . APP_NAME . DS . (date('Y-m-d', REQUEST_TIME)) . '.log.php', 'a+');
        fwrite($fp, $msg . " */?>" . PHP_EOL);
        fclose($fp);
    }
}