<?php
/**
 * app default config file
 * @modified by reginx v1.0.0
 */
 return array (
  'app_lang' => 'zh-cn',
  'cache_ver' => 'RGX',
  'template' => 'default',
  'db' =>
  array (
    'type' => 'mysql',
    'pre' => 'pre_',
    'mysql' =>
    array (
      'master' =>
      array (
        0 => 'host=127.0.0.1&port=3306&db=pre_kqc&user=root&pwd=root&charset=utf8',
      ),
    ),
  ),
  'cache' =>
  array (
    'type' => 'file',
    'pre' => 'rex_',
    'file' =>
    array (
    ),
    'redis' =>
    array (
      0 =>
      array (
        'host' => '192.168.0.188',
        'port' => 6379,
        'db' => 2,
      ),
    ),
  ),
  'router' =>
  array (
    'pattern' => '2:M/A/P_V/:.html:0',
    'def_mod' => 'index',
    'def_act' => 'index',
    'type' => 2,
    'suf' => '.html',
    'rewrite' => false,
    'rootdomain' => false,
    'protocol' => 'http://',
    'MA' => '/',
    'AP' => '/',
    'PP' => '_',
    'PG' => '/',
  ),
  'sess' =>
  array (
    'type' => 'php',
    'php' =>
    array (
      'ttl' => 86400,
    ),
    'redis' =>
    array (
      'host' => '192.168.0.188',
      'port' => 6379,
      'db' => 3,
      'ttl' => 86400,
    ),
  ),
    'sess_online'   => array(
        'type'      => 'mysql',
        'gc'        => array(10, 500),
        'ttl'       => 86400,
        'redis'     => array(
            'host'  => '127.0.0.1',
            'port'  => 6379,
            'db'    => 4,
        ),
        'mysql'     => array()
    ),
  'upload_url' => 'http://static.kuaiqiangche.cn/data/attachment/',
  'static_url' => 'http://static.kuaiqiangche.cn/static/',
);
