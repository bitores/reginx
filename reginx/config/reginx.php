<?php
return array(
    'lang'                  => 'zh-cn',
    'time_offset'           => 8,
    '404_tpl'               => '@404.tpl.html',
    'mg_tpl'                => '@404.tpl.html',
    'router'                => array(
        'pattern'           => '2',
        'def_mod'           => 'index',
        'def_act'           => 'index',
    ),
    
    'cache'                 => array(
        'type'              => 'file',
        'pre'               => 'rex_',
        'file'              => array(),
    ),
    
    'db'                    => array(
        'type'              => 'mysql',
        'pre'               => 'pre_',
        'mysql'             => array()
    ),

    'upload'                => array(
        'allows'            => 'JPG|GIF|JPEG|PNG|ZIP|RAR' ,
        'max_size'          => '10m',
    ),

    'sess'                  => array(
        'type'              => 'php',
        'php'               => array(
            'ttl'           => 600
        )
    ),
    'domains'               => array(
        //'admin.reginx.com'  => 'admin@index',
        //'foo.reginx.com'    => 'user@index:index-uid-1',
        //'*.reginx.com'      => 'user@index:index',
    )
);