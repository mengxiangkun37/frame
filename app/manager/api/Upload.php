<?php

/*
 * 用户接口
 */


namespace app\manager\api;
use think\Db;

class Upload extends ApiBase{

    public function _initialize() {
        parent::_initialize();
        require_once ADDON_PATH . '/Qiniu/autoload.php';
    }

    function get_qiniu_token(){
        $token = controller('upload')->build_qiniu_token();
        return ret(0, $token );
    }
}
