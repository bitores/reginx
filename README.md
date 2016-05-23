include ('../reginx/reginx.php');
new reginx ();
<?php
 return array (
    // 语言包名称
    'app_lang' => 'zh-cn',
    'cache_ver' => '009',
    // 模版路径
    'template' => 'default',
    // 404页面路径
    '404_tpl'   => '404.tpl.html',
    'db'    => array (
        // 数据库类型  不支持 PDO
        'type'  => 'mysql',
        // 数据表前缀
        'pre'   => 'pre_',
        'mysql' => array (
            'master'    => array (
                0 => 'host=IP地址&port=端口&db=数据库名称&user=登录用户名&pwd=登录密码&charset=编码方式',
            ),
        ),
    ),
    'router' => array (
        /**
         * 
         *   2:M/A/P_V/:.html:0:work.dev/
         *   路由模式 : 模型/方法/参数名_参数值 : 后缀  :  模式(1 不带 index.php 2 带 index.php)  :  泛域名
         */
        'pattern' => '2:M/A/P_V/:.html:0:work.dev/',
        'def_mod' => 'index',
        'def_act' => 'index',
        'def_domain'    => ''
    ),
    'cache' => array (
        // 'type'  => 'file',
        // 缓存类型 file redis
        'type'  => 'redis',
        // key前缀
        'pre'   => 'rex_',
        'file'  => array (),
        'redis' => array (
            0 => array (
                // IP地址
                'host' => '192.168.0.188',
                // 端口
                'port' => 6379,
                // 数据库
                'db' => 2,
            ),
        ),
    ),
    // session 存储
    'sess'        => array(
        // 存储方式
        'type'      => 'redis',
        // 回收概率参数
        'gc'        => array(10, 500),
        'php'       => array(
            'ttl'   => 3600*24*30
        ),
        // redis形式的存储
        'redis'     => array(
            'host'  => '192.168.0.188',
            'port'  => 6379,
            'db'    => 3,
            // 过期时间
            'ttl'   => 86400 * 30
        )
    ),
    // 常量
    'static_url'    => 'http://static.kuaiqiangche.com/static/',
    'upload_url'    => 'http://static.kuaiqiangche.com/data/attachment/',
);
