<?php

/*
 * 用户接口
 * 20190101实施com方法
 */

namespace app\manager\api;
use think\Db;

class User extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    public function user_list( $param = null ) {
        $field = 'id, name, username, phone, avatar, utime, ctime, age, sex, address, status, last_login_time, last_login_ip';
        $list = $this->com_page_list('user', $param, $field);
        return ret(0,$list);
    }

    public function user_create($param = null){
        $field = 'name, username, password, phone, avatar, utime, ctime, age, sex, address, status';
        $rule = [
            ['username', 'unique:user', '41001'],
            ['username', 'require|max:20', '41002|41003'],
            ['name', 'require|max:15', '41004|41005'],
        ];

        if ( $param['password'] ){
            $vaild_password = $this->validate($param, [['password', 'require|length:6,12|alphaNum', '41006|41007|41008']]);
            if($vaild_password !== true){
                return ret($vaild_password);
            }
            $param['password'] = md5($param['password']);
        }
        $param['ctime'] = $param['utime'] = time();
        return $this->com_create( 'user', $param, $rule, $field );
    }

    public function user_update($param = null){
        $field = 'name, username, password, phone, avatar, utime, ctime, age, sex, address, status';
        $rule = [
            ['id', 'require', '40002'],
            ['username', 'unique:user', '41001'],
            ['username', 'require|max:20', '41002|41003'],
            ['name', 'require|max:15', '41004|41005'],
        ];

        if ( $param['password'] ){
            $vaild_password = $this->validate($param, [['password', 'require|length:6,12|alphaNum', '41006|41007|41008']]);
            if($vaild_password !== true){
                return ret($vaild_password);
            }
            $param['password'] = md5($param['password']);
        }else{
            unset($param['password']);
        }
        $param['utime'] = time();
        return $this->com_update( 'user', $param, $rule, $field );
    }

    public function user_delete($param = null) {
        if( $param['id'] == 1){
            return ret(41011);
        }
        return $this->com_delete( 'user', [ 'id' => $param['id'] ] );
    }

    public function user_info($param = null, $user) {
        return ret(0, $user);
    }
    public function myself_update($param = null, $user) {
        $field = 'name, username, password, phone, avatar, utime';
        $rule = [
            ['id', 'require', '40002'],
            ['username', 'unique:user', '41001'],
            ['name', 'require', '41004'],
            ['username', 'require|max:20', '41002|41003']
        ];
        $param['id'] = $user['id'];
        $param['utime'] = time();
        if ( $param['password'] ){
            $rule_password = [['password', 'require|length:6,12|alphaNum', '41006|41007|41008']];
            $vaild_password = $this->validate($param,$rule_password);
            if($vaild_password !== true){
                return ret($vaild_password);
            }
            $param['password'] = md5($param['password']);
        }else{
            unset($param['password']);
        }
        return $this->com_update( 'user', $param, $rule, $field );
    }


    //用户组/权限管理系列
    public function user_group_list( $param = null ) {
        $data_list = Db::name('user_group') -> order('id asc') -> select();
        return ret(0,$data_list);
    }
    public function user_group_create( $param = null ){
        $field = 'title';
        $rule = [
            ['title', 'require|unique:user_group', '41012|41010'],
        ];
        return $this->com_create( 'user_group', $param, $rule, $field );
    }
    public function user_group_update($param = null){
        $field = 'title';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|unique:user_group', '41012|41010'],
        ];
        return $this->com_update( 'user_group', $param, $rule, $field );
    }
    public function user_group_delete($param = null) {
        if( $param['id'] == 1 ){
            return ret(41044);
        }
        return $this->com_delete( 'user_group', [ 'id' => $param['id'] ] );
    }
    public function user_group_set($param = null){
        $rule = array(
            array('id', 'require', '40002'),
            array('type', 'require', '40002'),
        );
        $vaild = $this->validate($param,$rule);
        if($vaild !== true){
            return ret($vaild);
        }
        $res = Db::name('user_group')->where( ['id' => $param['id'] ] )->update( [ $param['type']  => implode(',', $param['value']) ] );
        if( $res === FALSE ) return ret(40001);
        return ret();
    }
}
