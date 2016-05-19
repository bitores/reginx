<?php
/**
 * 首页
 * @copyright reginx.com
 * $Id$
 */
class index_module extends module {

    public function indexAction () {
        $this->display('index.tpl.html');
    }
}