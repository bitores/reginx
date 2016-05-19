<?php
class file_log extends log {
    
    /**
     * 内容队列
     *
     * @var unknown_type
     */
    private $_queue = array();
    
    /**
     * 资源句柄
     *
     * @var unknown_type
     */
    private $_handle = array();
    
    /**
     * 当前日志文件
     *
     * @var unknown_type
     */
    private $_cur   = 'default';
    
    /**
     * 初始化
     *
     * @param unknown_type $key
     */
    public function init ($key = null) {
        $this->_cur = empty($key) ? 'default' : $key;
        if (!isset($this->_handle[$this->_cur])) {
            $this->_handle[$this->_cur] = fopen(DATA_PATH . 'log/' . $this->_cur . '.log.php', 'a+');
            fwrite($this->_handle[$this->_cur], PHP_EOL . 
                    date('Y-m-d H:i:s ', time()) . "{$this->_cur} Log begins" .  PHP_EOL);
        }
    }
    
    /**
     * 写入日志内容
     *
     * @param unknown_type $content
     */
    public function write ($content) {
        fwrite($this->_handle[$this->_cur],
                date('Y-m-d H:i:s ', time()) . "{$this->_cur} " .
                            (is_string($content) ? $content : var_export($content, 1)) . PHP_EOL);
    }
    
    /**
     * 刷新缓冲区内容到磁盘
     *
     * @param unknown_type $key
     */
    public function flush ($key = null) {
        if (!empty($key) && isset($this->_handle[$key])) {
            fflush($this->_handle[$key]);
        }
        else {
            foreach ($this->_handle as $v) {
                fflush($v);
            }
        }
    }
    
    /**
     * 析构函数
     *
     */
    public function __destruct () {
        foreach ($this->_handle as $v) {
            fflush($v);
            fclose($v);
        }
    }
}