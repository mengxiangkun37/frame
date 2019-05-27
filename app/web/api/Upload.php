<?php

/*
 * 用户接口
 */


namespace app\home\api;
use think\Db;

class Upload extends ApiBase{

    public function _initialize() {
        parent::_initialize();
        require_once ADDON_PATH . '/Qiniu/autoload.php';
    }

    function get_qiniu_token( $param = null ){

        $token = controller('upload')->build_qiniu_token( $param );
        return ret(0, $token );
    }

    function upload_base64( $param = null ){
        $res = controller('upload')->upload_base64( $param['content'], $param['type'] );
        return $res;    //上传后已包含ret信息
    }



}
