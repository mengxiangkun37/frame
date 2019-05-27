<?php

/*
 * 用户接口
 */


namespace app\manager\api;
use think\Db;

class Login extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    public function check_login( $param = null ) {
        $rule = array(
            array('username', 'require', '41018'),
            array('password', 'require', '41019'),
            array('verify', 'require|captcha', '41017|41017'),
        );
        $vaild = $this->validate($param,$rule);
        if($vaild !== true){
            return ret($vaild);
        }

        $where['username']=$param['username'];
        $where['password']=md5($param['password']);
        $where['status']=1;
        $res= Db::name('user') ->where($where)->find();
        if($res){
            session('uid', $res['id']);
            Db::name('user')->where('id',$res['id'])->update(array('last_login_time' => time(),'last_login_ip' => NOWIP));
            return ret(0, url('index/index') );     //可设置默认返回路径，待完善
        }else{
            return ret(41020);
        }
    }

    public function logout() {
        session(null);
        return ret(0);
    }


}
