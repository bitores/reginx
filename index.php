<?php
// 是否映射
define('IS_ALIAS', true);
// 是否映射模板
define('IS_ALIAS_TPL', false);
// 模板目录
define('D_ALIAS_TPL', realpath('./template') . DIRECTORY_SEPARATOR);

// 映射ID
define('D_ALIAS_ID', 'wap');
// 映射为的应用名
define('D_ALIAS_APPEND', 'default');

/** }}} **/

/** {{{ 载入应用 **/

chdir(realpath('./wap'));
include('./index.php');