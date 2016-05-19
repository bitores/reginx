<?php
/**
 * 表模型类
 *
 * @copyright reginx.com
 * $Id: table.class.php 11699 2016-01-23 06:19:20Z fanyilong $
 */
// 低优先级
define('RGX_SQL_LOW', 'low_priority');
// 高优先级
define('RGX_SQL_HIGH', 'high_priority');
// 延迟
define('RGX_SQL_DELAY', 'delayed');

class table {

    /**
     * 表 DB 配置
     *
     * @var unknown_type
     */
    public $dbconf = array();

    /**
     * 表数据
     *
     * @var unknown_type
     */
    public $data = array();

    /**
     * 字段输入验证
     *
     * @var unknown_type
     */
    public $validate = array();

    /**
     * 字段默认
     *
     * @var unknown_type
     */
    public $defaults = array();

    /**
     * 字段过滤规则
     *
     * @var unknown_type
     */
    public $filter = array();

    /**
     * 数据验证错误信息
     *
     * @var unknown_type
     */
    private $_errmsg = array();

    /**
     * 数据库连接对象
     *
     * @var unknown_type
     */
    private $_dbobj = null;

    /**
     * sql语句构造数组
     *
     * @var unknown_type
     */
    private $_sql = array();

    /**
     * 表模型配置信息
     *
     * @var unknown_type
     */
    public $_conf = array();

    /**
     * 是否保留上一次查询的条件
     *
     * @var unknown_type
     */
    private $_keep = FALSE;

    /**
     * 执行一次sql之后, 是否清空当前对象data
     * 生效次数一次
     *
     * @var unknown_type
     */
    private $_initdata = true;
    
    /**
     * 数组键字段
     *
     * @var unknown_type
     */
    private $_akey = null;
    
    /**
     * 是否使用静默模式
     *
     * @var unknown_type
     */
    private $_quiet = false;

    /**
     * 是否插入多条值
     * @var boolean
     */
    private $_is_multi_val = false;


    /**
     * 构造函数
     * 支持单表定义配置
     */
    public function __construct ($class = null) {
        $this->_dbobj = database::getobj($GLOBALS['_RC']['db']);
        if (empty($this->_conf)) {
            $this->_conf['pre'] = $GLOBALS['_RC']['db']['pre'];
        }
        $class = empty($class) ? get_class($this) : $class;
        if ($class != __CLASS__) {
            $this->_conf['table'] = substr($class, 0, -6);
            $this->_conf['table_name'] = '`' . $this->_conf['pre'] . $this->_conf['table'] . '`';
            $this->_initab();
        }
    }

    /**
     * 获取表名称
     *
     * @return unknown
     */
    public function getname () {
        return $this->_conf['table_name'];
    }
    
    /**
     * 获取所有表
     *
     * @return unknown
     */
    public function gettables () {
        return $this->_dbobj->gettables($this->_conf['pre'], $this->_getquiet());
    }

    /**
     * 设置 _initdata
     *
     * @param unknown_type $bool
     */
    public function initdata ($bool = false) {
        $this->_initdata = (bool) $bool;
        return $this;
    }

    /**
     * 获取表结构
     */
    private function _initab () {
        $this->_conf['fields'] = $GLOBALS['_CACHE']->get('rex@' . $this->_conf['table'] . '_struct');
        if (RUN_MODE == 'debug' || !$this->_conf['fields']) {
            $this->_conf['fields'] = $this->_dbobj->getfieldlist(
                    $this->_conf['table_name']);
            $GLOBALS['_CACHE']->set('rex@' . $this->_conf['table'] . '_struct',
                    $this->_conf['fields'], 0);
        }
    }

    /**
     * 获取字段信息
     *
     * @return array
     */
    public function getfields () {
        return $this->_conf['fields'];
    }

    /**
     * where
     *
     * @param unknown_type $where
     * @return unknown
     */
    public function where ($str) {
        $str = trim($str);
        if ($str != '') {
            $str = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $str);
            $instr = $in = $fun = $funstr = $blocks = array();
            // in , not in
            preg_match_all('/([\w\.]+\s+(not)?\s+in\s+\(.+?\))/i', $str, $in);
            foreach ($in[1] as $k => $v) {
                $str = str_replace($v, '#####' . $k . '#####', $str);
                $tmp = preg_split('/\s*(not\s*)?in\s*/i', $v);
                $instr[$k] = str_replace(trim($tmp[0]), $this->escape($tmp[0]), $v);
            }
            // exists , not exists
            preg_match_all('/((?:not\s*)?exists\s*\(.+?\))/i', $str, $fun);
            foreach ($fun[1] as $k => $v) {
                $str = str_replace($v, '@@@@@' . $k . '@@@@@', $str);
                $funstr[$k] = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $v);
            }

            $str = str_replace(')', ' ) ', str_replace('(', '( ', $str));
            preg_match_all(
                    '/([0-9a-zA-Z\.\_\#]+)\s*(\>\=|\<\=|\!\=|\=|\>|\<|\slike\s|\sor\s|\sand\s)\s*([^\s]+)/i',
                    $str, $blocks);
            for ($i = 0, $max = count($blocks[0]); $i < $max; $i ++) {
                $temp = $this->escape($blocks[1][$i]) . ' ' . $blocks[2][$i] . ' ';
                if (strpos($blocks[3][$i], '\'') === false && !is_numeric($blocks[3][$i]) 
                        && (strpos($blocks[3][$i], '"') !== false || strpos($blocks[3][$i], '.') !== false)) {
                    $temp .= $this->escape($blocks[3][$i]);
                }
                else {
                    $temp .= $blocks[3][$i];
                }
                $str = preg_replace(
                        '/(\s|^)' . preg_quote($blocks[0][$i], '/') . '(\s|$)/i',
                        ' ' . $temp . ' ', $str);
            }
            if (! empty($instr)) {
                foreach ($instr as $k => $v) {
                    $str = str_replace('#####' . $k . '#####', $v, $str);
                }
                $instr = null;
            }
            if (! empty($funstr)) {
                foreach ($funstr as $k => $v) {
                    $str = str_replace('@@@@@' . $k . '@@@@@', $v, $str);
                }
                $funstr = null;
            }
            $this->_sql['where'][] = $str;
        }
        return $this;
    }

    /**
     * set 字段值
     *
     * @param unknown_type $k
     * @param unknown_type $v
     */
    public function set ($k, $v, $normal = 1) {
        // 检测字段是否存在
        if (! isset($this->_conf['fields']['list'][$k])) {
            throw_error(LANG('table field does not exist', $k,
                            $this->_conf['table_name']), 'TFDNE');
        }
        $this->data[$k] = $v;
        if (!$normal) {
            $this->data[$k] = array(0 => $v);
        }
        return $this;
    }

    /**
     * limit
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function limit ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            $this->_sql['limit'] = array_map('intval', $tmp);
        }
        return $this;
    }

    /**
     * union limit
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function ulimit ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            $this->_sql['ulimit'] = array_map('intval', $tmp);
        }
        return $this;
    }

    /**
     * 获取一条记录
     *
     * @return unknown
     */
    public function get () {
        $this->_sql['limit'] = array(1);
        return $this->_dbobj->get($this->parsesql('select'), $this->_getquiet());
    }

    /**
     * 执行sql
     *
     * @return unknown
     */
    public function exec ($sql, $param = array()) {
        return $this->_dbobj->query($this->_format($sql, $param), $this->_getquiet());
    }

    /**
     * 执行 unbuf sql
     *
     * @return unknown
     */
    public function unbufquery ($sql, $param = array()) {
        return $this->_dbobj->unbufquery($this->_format($sql, $param), $this->_getquiet());
    }

    /**
     * 格式化sql
     *
     * @return unknown
     */
    private function _format ($sql, $param = array()) {
        $sql = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $sql);
        if (!empty($param)) {
            $m = array();
            preg_match_all('/\s*\%[a-zA-Z]\s*/i', $sql, $m);
            if (count($m[0]) > count($param)) {
                throw_error(LANG('wrong number of arguments'), 'WNOA', 1, $sql);
            }
            $sql = vsprintf($sql, $param);
        }
        return $sql;
    }

    /**
     * union
     *
     * @param tab $obj
     * @param boolean $all
     * @return mixed
     */
    public function union (&$obj = null, $ctype = false) {
        if (!empty($obj)) {
            if (!is_array($obj)) {
                $obj = array($obj);
            }
            $ctype = $ctype ? ($ctype == '1' ? 'all' : 'distinct') : '';
            $sql = '( ' . $this->parsesql('select') . ') ';
            foreach ($obj as $v) {
                $sql .= ' union ' . $ctype . '  ( ' . $v->parsesql('select') . ' ) ';
            }
            // order
            if (! empty($this->_sql['uorder'])) {
                $sql .= ' order by ' . implode(' , ', $this->_sql['uorder']);
            }
            // limit
            if (! empty($this->_sql['ulimit'])) {
                $sql .= ' limit  ' . implode(' , ', $this->_sql['ulimit']);
            }
            $this->_sql = array();
            return $this->_dbobj->getlist($sql, $this->_getakey(), $this->_getquiet());
        }
        return array();
    }

    /**
     * 输出sql
     *
     * @param unknown_type $sql
     */
    public function test ($act = 'select') {
        if ($this->_errmsg) {
            return $this->_errmsg;
        }
        return $this->parsesql($act);
    }

    /**
     * sql 优先级
     *
     * @param unknown_type $pri
     */
    public function priority ($pri = RGX_SQL_HIGH) {
        $this->_sql['priority'] = $pri;
        return $this;
    }

    /**
     * 获取数据集合
     *
     * @return unknown
     */
    public function getall ($sql = null, $param = array()) {
        return $this->_dbobj->getlist(
                empty($sql) ? $this->parsesql('select') : $this->_format($sql, $param),
                    $this->_getakey(), $this->_getquiet());
    }

    /**
     * 设置返回数据数组的索引键
     *
     * @param unknown_type $k
     * @return unknown
     */
    public function akey ($k = null) {
        $this->_akey = empty($k) ? $this->_conf['fields']['prikey'] : $k;
        return $this;
    }
    
    
    /**
     * 返回数据数组的索引键
     *
     * @return unknown
     */
    private function _getakey () {
        $akey = $this->_akey;
        $this->_akey = null;
        return $akey;
    }

    /**
     * union all
     *
     * @param tab $obj
     * @return mixed
     */
    public function unionall (&$obj) {
        return $this->union($obj, 1);
    }

    /**
     * union distinct
     *
     * @param unknown_type $obj
     * @return unknown
     */
    public function uniondis (&$obj) {
        return $this->union($obj, 2);
    }

    /**
     * 解析sql
     *
     * @return unknown
     */
    public function parsesql ($act = false) {
        $sep = RUN_MODE == 'debug' ? " \n" : ' ';
        // action
        $this->_sql['act'] = $act ? $act : $this->_sql['act'];
        $sql = $this->_sql['act'] . ' ';

        // select
        if ($this->_sql['act'] == 'select') {
            // priority
            if (isset($this->_sql['priority']) &&
                     $this->_sql['priority'] == RGX_SQL_HIGH) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            // fields
            if (isset($this->_sql['fields']) && ! empty($this->_sql['fields'])) {
                $sql .= implode(' , ', $this->_sql['fields']);
            }
            else {
                $sql .= ' * ';
            }
            $sql .= $sep;
            if (! $this->_keep) {
                $this->_sql['fields'] = null;
            }

            $sql .= 'from ' . $this->_conf['table_name'] . $sep;

            // join
            if (! empty($this->_sql['join'])) {
                $sql .= implode(" \n", $this->_sql['join']);
                if (! $this->_keep) {
                    $this->_sql['join'] = null;
                }
            }
            $sql .= $sep;
            // where
            if (! empty($this->_sql['where'])) {
                $sql .= 'where 1 = 1' . $sep;
                foreach ($this->_sql['where'] as $v) {
                    $sql .= 'and ( ' . $v . ' ) ' . $sep;
                }
                if (! $this->_keep) {
                    $this->_sql['where'] = null;
                }
            }
            // group
            if (! empty($this->_sql['group'])) {
                $sql .= 'group by ' . implode(' , ', $this->_sql['group']) . $sep;
                if (! $this->_keep) {
                    $this->_sql['group'] = null;
                }
            }
            // order
            if (! empty($this->_sql['order'])) {
                $sql .= 'order by ' . implode(' , ', $this->_sql['order']) . $sep;
                if ($this->_keep) {
                    $this->_sql['order'] = null;
                }
            }
            // having
            if (! empty($this->_sql['having'])) {
                $sql .= 'having(' . $this->_sql['having'] . ')' . $sep;
                if ($this->_keep) {
                    $this->_sql['having'] = null;
                }
            }
            // limit
            if (! empty($this->_sql['limit'])) {
                $sql .= 'limit  ' . implode(' , ', $this->_sql['limit']) . $sep;
                if ($this->_keep) {
                    $this->_sql['limit'] = null;
                }
            }
        }
        // update
        else if ($this->_sql['act'] == 'update') {
            // priorty
            if (isset($this->_sql['priority']) &&
                     $this->_sql['priority'] == RGX_SQL_LOW) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            // ignore
            if (isset($this->_sql['ignore'])) {
                $sql .= ' ignore ';
            }
            $sql .= $this->_conf['table_name'] . ' set ';
            // set 数据
            if (!empty($this->data) && is_array($this->data)) {
                foreach ($this->data as $k => $v) {
                    // 非主键 or 非自增
                    if ($k != $this->_conf['fields']['prikey']) {
                        if (!is_array($v)) {
                            $this->data[$k] = $this->escape($k) . " = " . "'" . $v . "'" . $sep;
                        }
                        else {
                            $this->data[$k] = $this->escape($k) . " = " . $v . $sep;
                        }
                    }
                    // 自增的主键
                    else {
                        $this->where("{$k} = '{$v}'");
                        if ($this->_initdata) {
                            unset($this->data[$k]);
                        }
                    }
                }
                $sql .= implode(' , ', $this->data);
                if (! $this->_initdata) {
                    $this->_initdata = true;
                }
            }
            // 无数据 抛出异常信息
            else {
                throw_error(LANG('data to be processed is empty', $this->_conf['table']), 'DTBPIE', 1, $this->_conf['table']);
            }
            // where
            if (! empty($this->_sql['where'])) {
                $sql .= 'where 1 = 1 ' . $sep;
                foreach ($this->_sql['where'] as $v) {
                    $sql .= 'and ( ' . $v . ' ) ' . $sep;
                }
                $this->_sql['where'] = null;
                unset($this->_sql['where']);
            }
        }
        // delete
        else if ($this->_sql['act'] == 'delete') {
            $sql .= ' from ' . $this->_conf['table_name'];
            // where
            if (! empty($this->_sql['where'])) {
                $sql .= 'where 1 = 1 ' . $sep;
                foreach ($this->_sql['where'] as $v) {
                    $sql .= 'and ( ' . $v . ' ) ' . $sep;
                }
                $this->_sql['where'] = null;
                unset($this->_sql['where']);
            }
        }
        // insert && replace
        else if ($this->_sql['act'] == 'insert' || $this->_sql['act'] == 'replace') {
            // insert priority
            if ($this->_sql['act'] == 'insert') {
                $sql .= (isset($this->_sql['priority']) ? $this->_sql['priority'] : '') . ' ';
                if (isset($this->_sql['ignore'])) {
                    $sql .= ' ignore ';
                }
            }
            // replace priority
            else if (isset($this->_sql['priority']) && $this->_sql['priority'] != RGX_SQL_HIGH) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            $sql .= ' into ';
            $sql .= $this->_conf['table_name'] . '( ';
            $keys = $vals = array();
            // form data
            if (! empty($this->data) && is_array($this->data)) {
                if ($this->_is_multi_val) {
                    foreach ($this->data as $index => $data) {
                        $tag = empty($keys);
                        foreach ($data as $k => $v) {
                            if ($tag) {
                                $keys[] = $this->escape($k);
                            }
                            $vals[$index][] = "'" . $v . "'";
                        }
                    }
                }
                else {
                    foreach ($this->data as $k => $v) {
                        $keys[] = $this->escape($k);
                        $vals[] = "'" . $v . "'";
                    }
                }
            }
            else {
                throw_error(LANG('data to be processed is empty', $this->_conf['table']), 'DTBPIE', 1);
            }
            $sql .= implode(' , ', $keys) . ' ) ' . $sep . ' values';
            if ($this->_is_multi_val) {
                foreach ($vals as $val) {
                    $sql .= '( ' . implode(' , ', $val) . ' ),';
                }
                $sql = rtrim($sql, ' , ');
            }
            else {
                $sql .= '( ' . implode(' , ', $vals) . ' )';
            }
            // ODKU
            if ($this->_sql['act'] == 'insert' && !empty($this->_sql['odku'])) {
                $sql .= ' on duplicate key update ' . $this->_sql['odku'];
            }
            $this->_is_multi_val = false;
        }// insert && replace end

        // 是否保留当前装载的from data
        if ($this->_initdata) {
            $this->data = array();
        }
        // 只能保持一次 , 下次执行会被释放掉
        else {
            $this->_initdata = true;
        }
        // 是否保留当前已设置的sql属性条件
        if (!$this->_keep) {
            $this->_sql = array();
        }
        // 只能保持一次, 下次执行将会被释放掉
        else {
            $this->_keep = FALSE;
        }
        //echo $sql;
        return $sql;
    }

    /**
     * ignore for insert
     */
    public function ignore () {
        $this->_sql['ignore'] = 1;
        return $this;
    }

    /**
     * ON DUPLICATE KEY UPDATE
     */
    public function odku ($str) {
        if (is_string($str)) {
            $this->_sql['odku'] = $str;
        }
        else if (is_array($str)) {
            $tmp = array();
            foreach ($str as $k => $v) {
                if (isset($this->_conf['fields']['list'][$k])) {
                    if (preg_match('/(VALUES)/i', $v)) {
                        $tmp[] = $k . '=' . "{$v}"; 
                    }
                    else {
                        $tmp[] = $k . '=' . "'{$v}'"; 
                    }
                }
            }
            $this->_sql['odku'] = join(',', $tmp);
        }
        return $this;
    }

    /**
     * 保存
     * @return boolean
     */
    public function save () {
        $pkval = intval($this->data[$this->_conf['fields']['prikey']]);
        // update (pk 大于 0, 且存在 where 条件)
        if (($pkval > 0 || ! empty($this->_sql['where'])) && $this->_validate(true)) {
            return $this->_dbobj->update($this->parsesql('update'), $this->_getquiet());
        }
        // insert
        else if ($this->_validate()) {
            return $this->_dbobj->insert($this->parsesql('insert'), $this->_getquiet());
        }
        return false;
    }


    /**
     * 执行sql
     *
     * @return unknown
     */
    public function update ($sql = null, $param = array()) {
        return $this->_dbobj->update(
            empty($sql) ? $this->parsesql('update') : $this->_format($sql, $param),
                $this->_getquiet()
        );
    }
    
    /**
     * 删除操作
     *
     * @return unknown
     */
    public function delete ($sql = null, $param = array()) {
        return $this->_dbobj->delete(
            empty($sql) ? $this->parsesql('delete') : $this->_format($sql, $param),
                $this->_getquiet()
        );
    }

    /**
     * 静默模式
     *
     * @param unknown_type $value
     * @return unknown
     */
    public function quiet ($value = true) {
        $this->_quiet = $value ? true : false;
        return $this;
    }
    
    /**
     * 获取静默状态
     *
     * @return unknown
     */
    private function _getquiet () {
        $ret = $this->_quiet;
        $this->_quiet = false;
        return $ret;
    }

    /**
     * 新增
     * @return boolean
     */
    public function insert ($sql = null, $param = array()) {
        // 使用串行操作生成sql
        if (empty($sql)) {
            if ($this->_validate()) {
                return $this->_dbobj->insert($this->parsesql('insert'), $this->_getquiet());
            }
            else {
                return $this->geterr();
            }
        }
        // 直接执行 sql
        else {
            return $this->_dbobj->insert($this->_format($sql, $param), $this->_getquiet());
        }
    }

    /**
     * 统计
     * @param unknown_type $fields
     */
    public function count ($fields = null, $mode = 'master') {
        // 字段
        if (! empty($fields)) {
            $this->fields('count( ' . $fields . ' ) as nums');
        }
        // 主键
        else if (! empty($this->_sql['fields'])) {
            $this->fields('count( ' . $this->_conf['fields']['prikey'] . ' ) as nums');
        }
        // 常量
        else {
            $this->fields('count( 1 ) as nums');
        }
        return $this->_dbobj->count($this->parsesql('select'), $mode, $this->_getquiet());
    }

    /**
     * 保存本次sql值
     */
    public function keep () {
        $this->_keep = true;
        return $this;
    }

    /**
     * replace 操作
     *
     * @return unknown
     */
    public function replace ($sql = null, $param = array()) {
        if (empty($sql)) {
            if ($this->_validate()) {
                return $this->_dbobj->update($this->parsesql('replace'), $this->_getquiet());
            }
            else {
                return $this->geterr();
            }
        }
        else {
            return $this->_dbobj->update($this->_format($sql, $param), $this->_getquiet());
        }
    }

    /**
     * 联合查询
     * @param unknown_type $ctype
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param unknown_type $iseq
     * @return tab
     */
    private function _join ($ctype = 'left', $tab, $key, $fkey, $iseq = true) {
        if (! empty($tab) && ! empty($key) && ! empty($fkey)) {
            if (strpos($tab, ' as ') !== false) {
                $tmp = explode(' as ', $tab);
                $tab = trim($tmp[0]);
                $alias = trim($tmp[1]);
            }
            else {
                $alias = null;
            }
            $fkey = str_replace($tab . '.', '', trim($fkey));
            if (strpos($fkey, '.') !== false) {
                $fkey = $this->escape($fkey);
            }
            else {
                $fkey = $this->escape((empty($alias) ? $tab : $alias) . '.' . $fkey);
            }
            $str = $ctype . " join " . $this->_gettabname($tab) . " ";
            $str .= (empty($alias) ? '' : "as $alias ") . ' on ( ' . $this->escape($key);
            $str .= $iseq ? ' = ' : ' != ';
            $str .= $fkey . ' ) ';
            $this->_sql['join'][] = $str;
        }
        return $this;
    }

    /**
     * left join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param boolean $iseq
     */
    public function leftjoin ($tab, $key, $fkey, $iseq = true) {
        return $this->_join('left', $tab, $key, $fkey, $iseq);
    }

    /**
     * right join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param boolean $iseq
     */
    public function rightjoin ($tab, $key, $fkey, $iseq = true) {
        return $this->_join('right', $tab, $key, $fkey, $iseq);
    }

    /**
     * inner join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     */
    public function innerjoin ($tab, $key, $fkey, $iseq = true) {
        return $this->_join('inner', $tab, $key, $fkey, $iseq = true);
    }

    /**
     * Order By
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function order ($str) {
        $str = trim($str);
        if ($str != '') {
            $match = array();
            preg_match_all('/(.+?)(desc|asc)\s*?\,?/si', $str, $match);
            if (! empty($match[1])) {
                for ($i = 0, $max = count($match[1]); $i < $max; $i ++) {
                    $field = array();
                    $tmp = explode(',', $match[1][$i]);
                    foreach ($tmp as $v) {
                        if (trim($v) != '') {
                            $field[] = $this->escape($v);
                        }
                    }
                    $this->_sql['order'][] = implode(' , ', $field) . ' ' . $match[2][$i];
                }
                $match = null;
                unset($match);
            }
            else {
                // field(name , '1','2','3','4')
                $this->_sql['order'][] = preg_replace('/(\w+?)_table/i',
                        '`' . $this->_conf['pre'] . '\\1' . '`', strtolower(trim($str)));
            }
        }
        return $this;
    }
    /**
     * having
     * @param  string $str [description]
     * @return [type]      [description]
     */
    public function having($str = '') {
        $this->_sql['having'] = $str;
        return $this;
    }

    /**
     * union order
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function uorder ($str) {
        $str = trim($str);
        if ($str != '') {
            $match = array();
            preg_match_all('/(.+?)(desc|asc)\,?/si', $str, $match);
            for ($i = 0, $max = count($match[1]); $i < $max; $i ++) {
                $field = array();
                $tmp = explode(',', $match[1][$i]);
                foreach ($tmp as $v) {
                    $field[] = $v;
                }
                $this->_sql['uorder'][] = implode(' , ', $field) . ' ' . $match[2][$i];
            }
            $match = null;
            unset($match);
        }
        return $this;
    }

    /**
     * Group By
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function group ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            foreach ($tmp as $v) {
                $this->_sql['group'][] = $this->escape($v);
            }
        }
        return $this;
    }

    /**
     * 设置查询字段
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function fields ($str) {
        $tmp = explode(',', trim(strtolower($str)));
        foreach ($tmp as $v) {
            if (strpos($v, ' as ') !== false) {
                $temp = explode(' as ', $v);
                $matchs = array();
                // count() , sum() , max() , min() ...
                preg_match('/(\w+)\(\s*?([^\)]+?)\s*?\)/i', trim($temp[0]), $matchs);
                if ($matchs[1] && $matchs[2]) {
                    $matchs = array_map('trim', $matchs);
                    // distinct
                    if (($pos = strpos($matchs[2], 'distinct')) === false) {
                        $this->_sql['fields'][] = $matchs[1] . '( ' .
                                 $this->escape($matchs[2]) . ' ) as ' . trim(($temp[1]));
                    }
                    else {
                        $keyword = substr($matchs[2], 0, $pos + 8);
                        $field = substr($matchs[2], $pos + 8 - strlen($matchs[2]));
                        $this->_sql['fields'][] = $matchs[1] . '( ' . $keyword . ' ' .
                                 $this->escape($field) . ' ) as ' . trim(($temp[1]));
                    }
                }
                else {
                    $this->_sql['fields'][] = $this->escape($temp[0]) . ' as ' .
                             trim(($temp[1]));
                }
            }
            else {
                $this->_sql['fields'][] = $this->escape($v);
            }
        }
        return $this;
    }

    /**
     * 字段转义
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function escape ($str) {
        $ret = $str = strtolower(trim($str));
        // 数字常量
        if (is_numeric($str)) {
            $ret = intval($str);
        }
        else if (isset($this->_conf['fields']['list'][$str])) {
            $ret = $this->_conf['table_name'] . '.`' . $str . '`';
        }
        else {
            if (strpos($str, '.') !== false) {
                $tmp = explode('.', $str);
                // info_tab.id as iid
                if (strpos($str, ' as ') !== false) {
                    $temp = explode(' as ', str_replace($tmp[0] . '.', '', $str));
                    $ret = $this->_gettabname($tmp[0]) . '.`' . trim($temp[0]) . '` as `' .
                             trim($temp[1]) . '`';
                }
                else {
                    if (trim($tmp[1]) == '*') {
                        $ret = $this->_gettabname($tmp[0]) . '.*';
                    }
                    else {
                        $ret = $this->_gettabname($tmp[0]) . '.`' . trim($tmp[1]) . '`';
                    }
                }
            }
            else {
                $ret = ($str == '*' || substr($str, 0, 5) == '#####') ? $str : ('`' .
                         $str . '`');
            }
        }
        return $ret;
    }

    /**
     * 加载表单数据至当前对象data属性
     *
     * @param unknown_type $var
     * @return unknown
     */
    public function load ($var = null) {
        $this->_errmsg = array();
        if (! empty($var) && is_array($var)) {
            $data = $var;
        }
        else {
            $data = core::getrp(empty($var) ? $this->_conf['table'] : $var, 'P');
        }
        if (isset($data[0])) {
            $this->_is_multi_val = true;
            foreach ($data as $index => $d) {
                foreach ($d as $k => $v) {
                    if (isset($this->_conf['fields']['list'][$k])) {
                        $this->data[$index][$k] = $v;
                    }
                }
            }
        }
        else {
            foreach ((array)$data as $k => $v) {
                // 过滤非当前表字段的内容
                if (isset($this->_conf['fields']['list'][$k])) {
                    $this->data[$k] = $v;
                }
            }
        }
        $data = null;
        $isupdate = false;
        if (isset($this->data[$this->_conf['fields']['prikey']]) && $this->data[$this->_conf['fields']['prikey']] > 0) {
            $isupdate = true;
        }
        return $this->_validate($isupdate);
    }

    /**
     * 数据验证
     *
     * @return unknown
     */
    private function _validate ($isupdate = false) {
        $ret = true;
        // 当操作为 insert 的时候 , 合并默认数据
        if (! empty($this->defaults) && ! $isupdate) {
            if ($this->_is_multi_val) {
                foreach ($this->data as $k => $v) {
                    $this->data[$k] = array_merge($this->defaults, $v);
                }
            }
            else {
                $this->data = array_merge($this->defaults, $this->data);
            }
        }

        // 执行过滤
        if ($this->_is_multi_val) {
            foreach ($this->data as $index => $data) {
                foreach ($data as $k => $v) {
                    if (isset($this->filter[$k])) {
                        $this->data[$index][$k] = call_user_func_array($this->filter[$k], array($v));
                    }
                    // 默认过滤
                    else {
                        $this->data[$index][$k] = filter::normal($v);
                    }
                }
            }
        }
        else {
            foreach ($this->data as $k => $v) {
                if (isset($this->filter[$k])) {
                    $this->data[$k] = call_user_func_array($this->filter[$k], array($this->data[$k]));
                }
                // 默认过滤
                else {
                    $this->data[$k] = filter::normal($v);
                }
            }
        }
        unset($v);

        // 数据验证
        if (!empty($this->validate)) {
            if ($_is_multi_val) {
                foreach ($this->data as $index => $dara) {
                    foreach ($this->validate as $k => $v) {
                        // 执行更新时候,若数据不存在,跳过; 只验证存在的数据
                        if (!isset($this->data[$index][$v['key']]) && $isupdate) {
                            continue;
                        }
                        // 数据验证
                        switch (intval($v['type'])) {
                            case 0:
                                // 使用 filter 类提供的规则验证
                                if (! (bool) preg_match(filter::$rules[$v['rule']], $this->data[$index][$v['key']])) {
                                    $ret = false;
                                    if (substr($v['msg'], 0, 1) == '#') {
                                        $this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                    }
                                    else {
                                        $this->_errmsg[$v['key']] = $v['msg'];
                                    }
                                }
                                break;
                            case 1:
                                // 使用自定义的正则表达式验证
                                try {
                                    $result = preg_match($v['rule'], $this->data[$index][$v['key']]);
                                }
                                catch (Exception $e) {
                                    throw_error(LANG('invalid regular expression', $v['rule']), 'IRE', 1, $v['rule']);
                                }
                                if (!$result) {
                                    $ret = false;
                                    if (substr($v['msg'], 0, 1) == '#') {
                                        $this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                    }
                                    else {
                                        $this->_errmsg[$v['key']] = $v['msg'];
                                    }
                                }
                                break;
                            case 2:
                                // 使用自定义方法验证
                                if ($v['rule'][0] == get_class($this)) {
                                    if (!(bool)call_user_func_array(array($this, $v['rule'][1]),
                                            array($this->data[$index][$v['key']], $this->data[$index][$this->_conf['fields']['prikey']]))) {
                                        $ret = false;
                                        if (substr($v['msg'], 0, 1) == '#') {
                                            $this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                        }
                                        else {
                                            $this->_errmsg[$v['key']] = $v['msg'];
                                        }
                                    }
                                }
                                else if (! (bool) call_user_func_array($v['rule'],
                                        array($this->data[$index][$v['key']], $this->data[$index][$this->_conf['fields']['prikey']]))) {
                                    $ret = false;
                                    if (substr($v['msg'], 0, 1) == '#') {
                                        $this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                    }
                                    else {
                                        $this->_errmsg[$v['key']] = $v['msg'];
                                    }
                                }
                                break;
                        }
                    }
                }
            }
            else {
                foreach ($this->validate as $k => $v) {
                    // 执行更新时候,若数据不存在,跳过; 只验证存在的数据
                    if (!isset($this->data[$v['key']]) && $isupdate) {
                        continue;
                    }
                    // 数据验证
                    switch (intval($v['type'])) {
                        case 0:
                            // 使用 filter 类提供的规则验证
                            if (! (bool) preg_match(filter::$rules[$v['rule']], $this->data[$v['key']])) {
                                $ret = false;
                                if (substr($v['msg'], 0, 1) == '#') {
                                	$this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                }
                                else {
                                	$this->_errmsg[$v['key']] = $v['msg'];
                                }
                            }
                            break;
                        case 1:
                            // 使用自定义的正则表达式验证
                            try {
                                $result = preg_match($v['rule'], $this->data[$v['key']]);
                            }
                            catch (Exception $e) {
                                throw_error(LANG('invalid regular expression', $v['rule']), 'IRE', 1, $v['rule']);
                            }
                            if (!$result) {
                                $ret = false;
                                if (substr($v['msg'], 0, 1) == '#') {
                                	$this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                }
                                else {
                                	$this->_errmsg[$v['key']] = $v['msg'];
                                }
                            }
                            break;
                        case 2:
                            // 使用自定义方法验证
                            if ($v['rule'][0] == get_class($this)) {
                                if (!(bool)call_user_func_array(array($this, $v['rule'][1]),
                                        array($this->data[$v['key']], $this->data[$this->_conf['fields']['prikey']]))) {
                                    $ret = false;
    	                            if (substr($v['msg'], 0, 1) == '#') {
    	                            	$this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
    	                            }
    	                            else {
    	                            	$this->_errmsg[$v['key']] = $v['msg'];
    	                            }
                                }
                            }
                            else if (! (bool) call_user_func_array($v['rule'],
                                    array($this->data[$v['key']], $this->data[$this->_conf['fields']['prikey']]))) {
                                $ret = false;
                                if (substr($v['msg'], 0, 1) == '#') {
                                	$this->_errmsg[$v['key']] = LANG(substr($v['msg'], 1));
                                }
                                else {
                                	$this->_errmsg[$v['key']] = $v['msg'];
                                }
                            }
                            break;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 根据类名获取表名
     *
     * @param unknown_type $class
     * @return unknown
     */
    private function _gettabname ($class) {
        return preg_replace('/(\w+?)_table/i', '`' . $this->_conf['pre'] . '\\1' . '`',
                    strtolower(trim($class)));
    }

    /**
     * 清除sql条件
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function clear ($key) {
        if (isset($this->_sql[$key])) {
            $this->_sql[$key] = null;
            unset($this->_sql[$key]);
        }
        return $this;
    }

    /**
     * 获取错误消息
     *
     * @return unknown
     */
    public function geterr () {
        $errmsg = $this->_errmsg;
        $this->_errmsg = null;
        return $errmsg;
    }
}
?>