<?php
define('IN_REGINX', true);
define('RUN_MODE', 'debug');
define('APP_NAME', 'default');
define("DS", DIRECTORY_SEPARATOR);
define('APP_PATH', realpath('./') . DS);
define('BASE_PATH', realpath('./') . DS);
define('DATA_PATH', realpath('../data') . DS);
define('INC_PATH', realpath('../include') . DS);
include ('../reginx/reginx.php');
new reginx ();