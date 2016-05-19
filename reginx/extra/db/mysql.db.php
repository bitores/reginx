<?php
/**
 * MySQL 驱动类
 * @copyright reginx.com
 * $Id: mysql.db.php 10630 2016-01-07 10:54:39Z shironghui $
 */
class mysql_db extends database {
    
    /**
     * 连接池
     *
     * @var unknown_type
     */
    private $_link = array();
    
    /**
     * 配置信息
     *
     * @var unknown_type
     */
    private $_config = array();

    /**
     * 计数器
     *
     * @var unknown_type
     */
    private $_counter = array(
        'write' => 0,
        'read'  => 0
    );
    
    /**
     * 架构函数
     *
     * @param unknown_type $conf
     */
    public function __construct($conf = array()) {
        
        $this->_config = $conf;
        
        // master 
        if (!isset($this->_config['master'])  || empty($this->_config['master'])) {
            throw_error(LANG('db configuration is unavailable', __CLASS__), 'DCIU', 1, __METHOD__);
        }
        $this->_config['master'] = $this->_parsedsn($this->_config['master'][mt_rand(0, count($conf['master']) - 1)]);
        
        // slave
        if (isset($this->_config['slave']) && count($this->_config['slave']) >= 1) {
            $this->_config['slave'] = $this->_parsedsn($this->_config['slave'][mt_rand(0, count($conf['slave']) - 1)]);
        }
    }
    
    /**
     * 解析 DSN 信息
     *
     * @param unknown_type $dsn
     * @return unknown
     */
    private function _parsedsn ($dsn) {
        $ret = array();
        parse_str($dsn, $ret);
        if (count($ret) != 6) {
            throw_error(LANG('db configuration parsing fails'), 'DCPF', 1, $dsn);
        }
        return $ret;
    }
    
    /**
     * 连接数据库
     *
     * @param unknown_type $mode
     * @param unknown_type $pconn
     */
    public function connect ($mode = 'master', $pconn = false) {
        if (!isset($this->_link[$mode])) {
            $conf = $this->_config[isset($this->_config[$mode]) ? $mode : 'master'];
            // 持久连接
            if ($pconn) {
                $this->_link[$mode] = @mysql_pconnect("{$conf['host']}:{$conf['port']}", $conf['user'], $conf['pwd']);
            }
            // 非持久连接
            else {
                $this->_link[$mode] = @mysql_connect("{$conf['host']}:{$conf['port']}", $conf['user'], $conf['pwd']);
            }
            if (!is_resource($this->_link[$mode])) {
                throw_error(LANG('db server connection failed', 'MySQL'), 'DSCF');
            }
            // fix 在未配置 slave 却调用 slave 查询时 master 为空的BUG
            if (!isset($this->_config[$mode])) {
                $this->_link['master'] = $this->_link[$mode];
            }
            $this->usedb($conf['db'], $mode);
            $this->query("set names `{$conf['charset']}`", false, $mode);
        }
    }
    
    /**
     * 空操作, 保持连接
     *
     * @param unknown_type $mode
     * @return unknown
     */
    public function noop ($mode = 'master') {
        return $this->query('select 1', true, $mode);
    }
    
     /**
     * 切换数据库
     *
     * @param unknown_type $db
     */
    public function usedb ($db, $mode = null) {
        $ret = mysql_select_db($db, $this->_link[empty($mode) ? 'master' : $mode]);
        if (!$ret) {
            throw_error(LANG('db does not exists', $db), 'DNNE', 1, $db, $mode);
        }
        return $ret;
    }
    
    /**
     * 判断是否连通
     *
     */
    public function ping ($mode = 'master') {
        return mysql_ping($this->_config[$mode]);
    }
    
    /**
     * 释放连接
     *
     */
    public function close () {
        // 关闭 主库连接
        if (is_resource($this->_link['master'])) {
            mysql_close( $this->_link['master'] );
        }
        // 关闭 从库连接
        if (is_resource($this->_link['slave'])) {
            mysql_close($this->_link['slave']);
        }
    }
    
    /**
     * 返回错误信息
     *
     */
    public function geterror ($mode = 'master') {
        return array(mysql_errno( $this->_link[$mode] ), mysql_error($this->_link[$mode]));
    }
    
    /**
     * 返回状态/统计信息
     *
     */
    public function stat ($mode = 'master') {
        $ret = array();
        $mode = $mode == 'master' ? 'master' : 'slave';
        if (!isset($this->_link[$mode])) {
            $this->connect($mode);
        }
        $desc = mysql_stat($this->_link[$mode]);
        if (!empty($desc)) {
            $ret = explode('  ', $desc);
        }
        foreach ($ret as $k => $v) {
            $tmp = explode(':', $v);
            $ret[$k] = array(LANG(strtolower($tmp[0])), $tmp[0] == 'Uptime' ? core::duration(REQUEST_TIME - $tmp[1]) : $tmp[1]);
        }
        $ret[] = array(
            LANG('db size'),
            $this->getdbsize()
        );
        return $ret;
    }
    
    /**
     * 返回查询统计
     *
     */
    public function getcounter () {
        return $this->_counter;
    }
    
    /**
     * unbuf query
     *
     * @param unknown_type $sql
     * @param unknown_type $quiet
     * @param unknown_type $mode
     * @return unknown
     */
    public function unbufquery ($sql, $quiet = false, $mode = 'slave') {
        if (!isset($this->_link[$mode])) {
            $this->connect($mode);
        }
        $ret = mysql_unbuffered_query($sql, $this->_link[$mode]);
        if (!$ret && !$quiet) {
            throw_error(LANG('db query fails', join('.', $this->geterror())), 'DQF', $sql);
        }
        return $ret;
    }
    
    /**
     * 执行 sql
     *
     * @param unknown_type $sql 
     * @param unknown_type $quiet 是否开启静默摸索
     * @param unknown_type $mode  读写模式
     */
    public function query ($sql, $quiet = false, $mode = 'master') {
        if (!isset($this->_link[$mode])) {
            $this->connect($mode);
        }
        $ret = mysql_query($sql, $this->_link[$mode]);
        if (!$ret && !$quiet) {
            throw_error(LANG('db query fails', join('.', $this->geterror())), 'DQF', $sql);
        }
        return $ret;
    }
    
    /**
     * 获取单条记录
     *
     * @param unknown_type $sql
     * @param unknown_type $quiet
     * @param unknown_type $mode
     * @return unknown
     */
    public function get ($sql, $quiet = false, $mode = 'slave') {
        $query = $this->query($sql, $quiet, $mode);
        if ($query) {
            return $this->fetch($query);
        }
        return null;
    }
    
    
    /**
     * 获取一行数据
     *
     * @param unknown_type $res
     * @param unknown_type $t
     * @return unknown
     */
    public function fetch($res, $type = MYSQL_ASSOC ) {
        return mysql_fetch_array($res, $type);
    }
    
    /**
     * 获取批量记录
     *
     * @param unknown_type $sql
     */
    public function getlist ($sql, $akey = null, $quiet = false, $mode = 'slave') {
        $ret = array();
        $query = $this->query($sql, $quiet, $mode);
        while (($row = $this->fetch($query)) !== false) {
            if ($akey && isset($row[$akey])) {
                $ret[$row[$akey]] = $row;
            }
            else {
                $ret[] = $row;
            }
        }
        if ($query) {
            mysql_free_result($query);
        }
        return $ret;
    }
    
    /**
     * 统计数量
     *
     * @param string $sql
     * @param string $type
     * @return integer
     */
    public function count ($sql, $mode = 'slave', $quiet = false) {
        return intval(mysql_result($this->query($sql, $quiet, $mode), 0));
    }
    
    /**
     * 获取sql执行影响行数
     *
     */
    public function getaffects ($mode = 'master') {
        return intval(mysql_affected_rows($this->_link[$mode]));
    }
    

    /**
     * 获取上步 insert 操作产生的ID
     *
     * @return unknown
     */
    private function _insertid ($mode = 'master') {
        $lastid = mysql_insert_id($this->_link[$mode]);
        if ( $lastid <= 0 ) {
            $lastid = mysql_result($this->query("SELECT last_insert_id()", false, $mode), 0);
        }
        return $lastid;
    }
    
    /**
     * 插入记录
     *
     * @param unknown_type $sql
     */
    public function insert ($sql, $quiet = false, $mode = 'master') {
        $ret = array(
            'code'      => 1,
            'msg' 	    => '',
            'affects'   => 0
        );
        if (($query = $this->query($sql, $quiet)) != false) {
            $ret['code'] = 0;
            $ret['msg']  = $this->_insertid($mode);
            $ret['affects'] = $this->getaffects($mode);
        }
        else {
            $ret['msg']  = join(',', $this->geterror());
        }
        return $ret;
    }
    
    /**
     * 更新记录
     *
     * @param unknown_type $sql
     */
    public function update ($sql, $quiet = false, $mode = 'master') {
        $ret = array(
            'code'      => 1,
            'msg' 	    => '',
            'affects'   => 0
        );
        if (($query = $this->query($sql, $quiet)) != false) {

            /**
             * Fix inaccurate problems for the field affects and code
             */
            $ret['affects'] = $this->getaffects($mode);

            if (!empty($ret['affects'])) $ret['code'] = 0;

            $ret['msg']  = $this->_insertid($mode);
        }
        else {
            $ret['msg']  = join(',', $this->geterror());
        }
        return $ret;
    }
    
    /**
     * 删除记录
     *
     * @param unknown_type $sql
     */
    public function delete ($sql, $quiet = false, $mode = 'master') {
        $ret = array(
            'code'      => 1,
            'msg' 	    => '',
            'affects'   => 0
        );
        if (($query = $this->query($sql, $quiet)) != false) {
            $ret['code'] = 0;
            $ret['affects'] = $this->getaffects($mode);
        }
        else {
            $ret['msg']  = join(',', $this->geterror());
        }
        return $ret;
    }

    
    /**
     * 获取表结构中字段列表
     *
     * @param unknown_type $tab
     */
    public function getfieldlist ($tab) {
        $ret = array();
        $query = $this->query('SHOW FULL FIELDS FROM ' . $tab, false, 'slave');
        while (($row = mysql_fetch_array($query, MYSQL_ASSOC)) != false) {
            // 主键
            if ($row['Key'] === 'PRI') {
                $ret['pk'][] = strtolower($row['Field']);
            }
            // 自增
            if ($row['Extra'] === 'auto_increment') {
                $ret['autoinc'] = strtolower($row['Field']);
            }
            // 主键
            if ($row['Key'] === 'PRI' && $row['Extra'] === 'auto_increment') {
                $ret['prikey'] = strtolower($row['Field']);
            }
            $ret['list'][strtolower($row['Field'])] = empty($row['Comment']) ? strtolower($row['Field']) : trim($row['Comment']);
        }
        return $ret;
    }
    
    
    /**
     * 获取所有表
     *
     * @param unknown_type $pre
     */
    public function gettables () {}
    
    
    /**
     * 获取数据库大小
     *
     * @param unknown_type $db
     * @param unknown_type $mode
     * @return unknown
     */
    public function getdbsize ($db = null, $mode = 'master') {
        if ($db === null) {
            $db = $this->_config[$mode]['db'];
        }
        $sql = "select concat(round(sum(DATA_LENGTH/1024/1024),2), ' M') as dbsize from information_schema.TABLES " .
               " where table_schema= '{$db}' ";
        $ret = $this->get($sql, 1, $mode);
        return $ret['dbsize'];
    }
    
}