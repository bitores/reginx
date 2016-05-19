<?php !defined('IN_REGINX') && exit('Access Denied'); unset($this);?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Wap首页</title>
</head>
<body>
<h3>模板相关常量</h3>
<p>
    当前模板Url : http://m.reginx.dev/wap/template/default/ <br/>
    对应的物理路径为 : E:\git_workspace\reginx\wap\template\default
</p>

<p>
    当前页面绝对路径Url : <?php echo(core::url('index')); ?><br/>
    当前页面相对路径Url : <?php echo(core::url('@index')); ?>
</p>
<p>
    拼接地址 : <?php echo(core::url('@index-foo')); ?><br/>
    对应模块 : E:\git_workspace\reginx\wap\module/index.module.php<br/>
    对应动作 : fooAction ()
</p>
<p>
    使用url标签构造JS变量 :　<br/>
    &lt;script&gt;
        var ajax_url = '<?php echo(core::url('@index-ajax')); ?>';
    &lt;/script&gt;
</p>
</body>
</html>