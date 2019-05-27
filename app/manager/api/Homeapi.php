<?php

/*
 * 用户接口
 */


namespace app\manager\api;
use think\Db;

class Homeapi extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    //API系列业务
    public function api_list( $param = null ) {
        $list = $this->com_page_list( 'api_home',  $param );
        return ret(0,$list);
    }

    //搜索字段的补充，需要：字段名对应前端配置的field，读取出来也要对应，如：过滤type字段，应返回： type:[{type: "login"}]，推荐用group写
    public function api_search_CONTROLLER_NAME( $param = null ){
        $list = Db::name('api_home')->field('CONTROLLER_NAME')->group('CONTROLLER_NAME')->select();
        $res = [];
        foreach ( $list as $v ){
            $res[] = [
                'text' => '控制器 - '.$v['CONTROLLER_NAME'],
                'value' => $v['CONTROLLER_NAME']
            ];
        }
        return ret(0,$res);
    }

    public function api_create($param = null){
        $field = 'title,api,CONTROLLER_NAME,ACTION_NAME,login_require';
        $rule = [
            ['title', 'require', '41012'],
            ['api', 'require|unique:api_home', '41014|41010'],
            ['CONTROLLER_NAME', 'require', '41017'],
            ['ACTION_NAME', 'require', '41018']
        ];
        return $this->com_create( 'api_home', $param, $rule, $field );
    }

    public function api_update($param = null){
        $field = 'title,api,CONTROLLER_NAME,ACTION_NAME,login_require';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require', '41012'],
            ['api', 'require|unique:api_home', '41014|41010'],
            ['CONTROLLER_NAME', 'require', '41017'],
            ['ACTION_NAME', 'require', '41018']
        ];
        return $this->com_update( 'api_home', $param, $rule, $field );
    }

    public function api_delete($param = null) {
        return $this->com_delete( 'api_home', [ 'id' => $param['id'] ] );
    }


}
