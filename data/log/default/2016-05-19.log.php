<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code FNF
 * @date 2016-05-19 20:30:27 
 * @desc 文件 template\default\index.tpl.html 不存在
 * @extra template::_getsfilepath

 # 0 line:0511 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(文件 template\default\index.tpl.html 不存在,FNF,1,template::_getsfilepath)
 # 1 line:0160 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_getsfilepath(index.tpl.html)
 # 2 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 3 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 4 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 5 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code 
 * @date 2016-05-19 20:50:18 
 * @desc 未定义的模板常量 template\default\index.tpl.html : STATIC_PATH
 * @extra 

 # 0 line:0334 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(未定义的模板常量 template\default\index.tpl.html : STATIC_PATH,,1)
 # 1 line:0000 file :  template->_parseconstant(Array)
 # 2 line:0246 file : E:\git_workspace\reginx\reginx\class\template.class.php preg_replace_callback(/\{\s?__([\w\-_]*?)__\s?\}/is,Array,<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : {__CTPL_URL__} <br/>
    对应的物理路径为 : {__TPL_PATH__}default
</p>
<p>
    静态资源目录 : {__STATIC_URL__} <br/>
    对应的物理路径为 : {__STATIC_PATH__}
</p>
<p>
    当前页面绝对路径Url : {url:'index'}<br/>
    当前页面相对路径Url : {url:'@index'}
</p>
<p>
    拼接地址 : {url:'@index-foo'}<br/>
    对应模块 : {__APP_PATH__}module/index.module.php<br/>
    对应动作 : fooAction ()
</p>
<p>
    使用url标签构造JS变量 :　<br/>
    &lt;script&gt;
        var ajax_url = '{url:'@index-ajax'}';
    &lt;/script&gt;
</p>
</body>
</html>)
 # 3 line:0188 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_parsetpl(E:\git_workspace\reginx\data\temp\default\8A400C2A.php,E:\git_workspace\reginx\wap\template\default\index.tpl.html)
 # 4 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 5 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 6 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 7 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code 
 * @date 2016-05-19 20:50:19 
 * @desc 未定义的模板常量 template\default\index.tpl.html : STATIC_PATH
 * @extra 

 # 0 line:0334 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(未定义的模板常量 template\default\index.tpl.html : STATIC_PATH,,1)
 # 1 line:0000 file :  template->_parseconstant(Array)
 # 2 line:0246 file : E:\git_workspace\reginx\reginx\class\template.class.php preg_replace_callback(/\{\s?__([\w\-_]*?)__\s?\}/is,Array,<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : {__CTPL_URL__} <br/>
    对应的物理路径为 : {__TPL_PATH__}default
</p>
<p>
    静态资源目录 : {__STATIC_URL__} <br/>
    对应的物理路径为 : {__STATIC_PATH__}
</p>
<p>
    当前页面绝对路径Url : {url:'index'}<br/>
    当前页面相对路径Url : {url:'@index'}
</p>
<p>
    拼接地址 : {url:'@index-foo'}<br/>
    对应模块 : {__APP_PATH__}module/index.module.php<br/>
    对应动作 : fooAction ()
</p>
<p>
    使用url标签构造JS变量 :　<br/>
    &lt;script&gt;
        var ajax_url = '{url:'@index-ajax'}';
    &lt;/script&gt;
</p>
</body>
</html>)
 # 3 line:0188 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_parsetpl(E:\git_workspace\reginx\data\temp\default\8A400C2A.php,E:\git_workspace\reginx\wap\template\default\index.tpl.html)
 # 4 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 5 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 6 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 7 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code 
 * @date 2016-05-19 20:50:20 
 * @desc 未定义的模板常量 template\default\index.tpl.html : STATIC_PATH
 * @extra 

 # 0 line:0334 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(未定义的模板常量 template\default\index.tpl.html : STATIC_PATH,,1)
 # 1 line:0000 file :  template->_parseconstant(Array)
 # 2 line:0246 file : E:\git_workspace\reginx\reginx\class\template.class.php preg_replace_callback(/\{\s?__([\w\-_]*?)__\s?\}/is,Array,<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : {__CTPL_URL__} <br/>
    对应的物理路径为 : {__TPL_PATH__}default
</p>
<p>
    静态资源目录 : {__STATIC_URL__} <br/>
    对应的物理路径为 : {__STATIC_PATH__}
</p>
<p>
    当前页面绝对路径Url : {url:'index'}<br/>
    当前页面相对路径Url : {url:'@index'}
</p>
<p>
    拼接地址 : {url:'@index-foo'}<br/>
    对应模块 : {__APP_PATH__}module/index.module.php<br/>
    对应动作 : fooAction ()
</p>
<p>
    使用url标签构造JS变量 :　<br/>
    &lt;script&gt;
        var ajax_url = '{url:'@index-ajax'}';
    &lt;/script&gt;
</p>
</body>
</html>)
 # 3 line:0188 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_parsetpl(E:\git_workspace\reginx\data\temp\default\8A400C2A.php,E:\git_workspace\reginx\wap\template\default\index.tpl.html)
 # 4 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 5 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 6 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 7 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code 
 * @date 2016-05-19 20:50:59 
 * @desc 未定义的模板常量 template\default\index.tpl.html : STATIC_PATH
 * @extra 

 # 0 line:0334 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(未定义的模板常量 template\default\index.tpl.html : STATIC_PATH,,1)
 # 1 line:0000 file :  template->_parseconstant(Array)
 # 2 line:0246 file : E:\git_workspace\reginx\reginx\class\template.class.php preg_replace_callback(/\{\s?__([\w\-_]*?)__\s?\}/is,Array,<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : {__CTPL_URL__} <br/>
    对应的物理路径为 : {__TPL_PATH__}default
</p>
<p>
    静态资源目录 : {__STATIC_URL__} <br/>
    对应的物理路径为 : {__STATIC_PATH__}
</p>
<p>
    当前页面绝对路径Url : {url:'index'}<br/>
    当前页面相对路径Url : {url:'@index'}
</p>
<p>
    拼接地址 : {url:'@index-foo'}<br/>
    对应模块 : {__APP_PATH__}module/index.module.php<br/>
    对应动作 : fooAction ()
</p>
<p>
    使用url标签构造JS变量 :　<br/>
    &lt;script&gt;
        var ajax_url = '{url:'@index-ajax'}';
    &lt;/script&gt;
</p>
</body>
</html>)
 # 3 line:0188 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_parsetpl(E:\git_workspace\reginx\data\temp\default\8A400C2A.php,E:\git_workspace\reginx\wap\template\default\index.tpl.html)
 # 4 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 5 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 6 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 7 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
<?php
/**
 * @ip   127.0.0.1
 * @url  /wap/
 * @code 
 * @date 2016-05-19 20:51:53 
 * @desc 未定义的模板常量 template\default\index.tpl.html : STATIC_PATH
 * @extra 

 # 0 line:0334 file : E:\git_workspace\reginx\reginx\class\template.class.php throw_error(未定义的模板常量 template\default\index.tpl.html : STATIC_PATH,,1)
 # 1 line:0000 file :  template->_parseconstant(Array)
 # 2 line:0246 file : E:\git_workspace\reginx\reginx\class\template.class.php preg_replace_callback(/\{\s?__([\w\-_]*?)__\s?\}/is,Array,<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : {__CTPL_URL__} <br/>
    对应的物理路径为 : {__TPL_PATH__}default
</p>
<p>
    静态资源目录 : {__STATIC_URL__} <br/>
    对应的物理路径为 : {__STATIC_PATH__}
</p>
<p>
    当前页面绝对路径Url : {url:'index'}<br/>
    当前页面相对路径Url : {url:'@index'}
</p>

</body>
</html>)
 # 3 line:0188 file : E:\git_workspace\reginx\reginx\class\template.class.php template->_parsetpl(E:\git_workspace\reginx\data\temp\default\8A400C2A.php,E:\git_workspace\reginx\wap\template\default\index.tpl.html)
 # 4 line:0138 file : E:\git_workspace\reginx\reginx\class\template.class.php template->fetch(index.tpl.html,)
 # 5 line:0164 file : E:\git_workspace\reginx\reginx\class\module.class.php template->display(index.tpl.html)
 # 6 line:0010 file : module\index.module.php module->display(index.tpl.html)
 # 7 line:0080 file : E:\git_workspace\reginx\reginx\reginx.php index_module->indexAction()
 */?>
