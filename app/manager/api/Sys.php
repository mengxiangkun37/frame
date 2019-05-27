<?php

/*
 * 用户接口
 */


namespace app\manager\api;
use think\Db;
use think\Cache;

class Sys extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    //框架方面
    public function get_navigation( $param = null, $user = null ) {
        $where['status'] = 1;
        if ( !$user['is_admin'] ){
            $where['id'] = [ 'in', $user['nav'] ];
        }
        $data_list = Db::name('navigation') -> where($where) ->order('sorts asc, id asc') -> select();
        if ( $param['change_url'] ){
            foreach ($data_list as $k => $v){
                if ( $v['pid'] != 0 && $v['url'] ){
                    $data_list[$k]['url'] = url($v['url']);
                }
            }
        }
        return ret(0,$data_list);
    }

    //系统菜单系列业务
    public function navigation_list( $param = null, $user = null ) {
	    $data_list = Db::name('navigation') -> order('sorts asc, id asc') -> select();
        return ret(0,$data_list);
    }

    public function navigation_create($param = null){
        $field = 'title, icon, pid, status, sorts, url';
        $rule = [
            ['title', 'require|unique:navigation', '41012|41010'],
            ['icon', 'require', '41033']
        ];
        if ( $param['pid'] != 0 && $param['url'] == '' ){
            return ret(41034);
        }
        return $this->com_create( 'navigation', $param, $rule, $field );
    }

    public function navigation_update($param = null){
        $field = 'title, icon, pid, status, sorts, url';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|unique:navigation', '41012|41010'],
            ['icon', 'require', '41033']
        ];
        if ( $param['pid'] != 0 && $param['url'] == '' ){
            return ret(41034);
        }
        return $this->com_update( 'navigation', $param, $rule, $field );
    }

    public function navigation_delete($param = null) {
        $count = Db::name('navigation')->where( ['pid' => $param['id'] ] )->count();
        if ( $count > 0 ){
            return ret(41035);
        }
        return $this->com_delete( 'navigation', [ 'id' => $param['id'] ] );
    }


    //API系列业务

    public function api_list( $param = null ) {
        $list = $this->com_page_list( 'api',  $param );
        $controllers = Db::name('api')->field('CONTROLLER_NAME')->group('CONTROLLER_NAME')->select();
        $res = [];
        foreach ( $controllers as $v ){
            $res[] = [
                'text' => '控制器 - '.$v['CONTROLLER_NAME'],
                'value' => $v['CONTROLLER_NAME']
            ];
        }
        $list['controllers'] = $res;
        return ret(0,$list);
    }


    public function get_table_info( $param = null ){
        return ret( 0 , Db::name( $param['table'] )->getTableInfo());
    }

    public function set_list( $param = null ) {
        $list = $this->com_page_list( 'api',  $param );
        return ret(0,$list);
    }

    //通用获取配置的方法，传数组，返回数组对应列表，传单字符串，返回列表

    public function tags_groups($param = null){
        $groups = Db::name('tags')->where(['status'=>1])->group('group')->field('group,status')->order('id desc')->select();
        $res = [];
        foreach ( $groups as $v ){
            $res[] = $v['group'];
        }
        return ret(0, $res);
    }

    public function tags_list($param = null){
        $res = $this->com_get_tags( $param );
        return ret(0, $res);
    }

    //单次 修改tags值，采用com_update方法
    public function tags_create( $param ){
        $field = 'title,remark,style,color,utime,group,status,link,pic';
        $rule = array(
            array('title', 'require', '41045'),
            array('group', 'require', '41028'),
        );
        $param['utime'] = time();
        $param['status'] = 1;
        return $this->com_create('tags', $param, $rule, $field);
    }

    //单次 修改tags值，采用com_update方法
    public function tags_update( $param ){
        $field = 'title,remark,style,color,utime,link,pic';
        $rule = array(
            array('id', 'require', '40002'),
            array('title', 'require', '41045'),
        );
        $param['utime'] = time();
        return $this->com_update('tags', $param, $rule, $field);
    }

    // input ： id，单个设置status为0，为安全，删除可能会引起意外结果
    public function tags_delete( $param ){
//        return $this->com_delete('tags', $param);
        $field = 'utime,status';
        $rule = array(
            array('id', 'require', '40002'),
        );
        $param['status'] = 0;
        $param['utime'] = time();
        return $this->com_update('tags', $param, $rule, $field);
    }

    //
    public function tags_sort($param){
        $field = 'sort';
        $rule = array(
            array('id', 'require', '40002'),
        );
        foreach($param as $v){
            $this->com_update('tags', $v, $rule, $field);
        }
        return ret(0);
    }

    public function tags_group_delete( $param ){
        $res = Db::name('tags')->where(['group' => $param['group'] ])->update(['status' => 0]);
        return ret(0, $res);
    }

    public function api_create($param = null){
        $field = 'title,api,MODULE_NAME,CONTROLLER_NAME,ACTION_NAME,login_require,success_require,error_require';
        $rule = [
            ['title', 'require', '41012'],
            ['api', 'require', '41014'],
            ['MODULE_NAME', 'require', '41016'],
            ['CONTROLLER_NAME', 'require', '41017'],
            ['ACTION_NAME', 'require', '41018']
        ];
        if ( Db::name('api')->where([ 'api' => $param['api'], 'MODULE_NAME' => $param['MODULE_NAME'] ])->count() ){
            return ret(41015);
        }
        return $this->com_create( 'api', $param, $rule, $field );
    }

    public function api_update($param = null){
        $field = 'title,api,MODULE_NAME,CONTROLLER_NAME,ACTION_NAME,login_require,success_require,error_require';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require', '41012'],
            ['api', 'require', '41014'],
            ['MODULE_NAME', 'require', '41016'],
            ['CONTROLLER_NAME', 'require', '41017'],
            ['ACTION_NAME', 'require', '41018']
        ];
        if ( Db::name('api')->where([ 'api' => $param['api'], 'MODULE_NAME' => $param['MODULE_NAME'], 'id' => ['neq', $param['id']] ])->count() ){
            return ret(41015);
        }
        return $this->com_update( 'api', $param, $rule, $field );
    }

    public function api_delete($param = null) {
        return $this->com_delete( 'api', [ 'id' => $param['id'] ] );
    }

//    系统配置系列
    public function sys_config( $param = null ) {
        $res['config_list'] = Db::name('sysconfig') -> select();
        $res['group_list'] = Db::name('sysconfig_group') ->order('sorts asc') -> select();
        return ret(0,$res);
    }

    public function sys_config_group_create( $param = null ){
        $field = 'title,sorts';
        $rule = [
            ['title', 'require|max:100', '41012|41025'],
            ['sorts', 'require', '41032'],
        ];
        return $this->com_create( 'sysconfig_group', $param, $rule, $field );
    }

    public function sys_config_group_update( $param = null ){
        $field = 'title,sorts';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|max:100', '41012|41025'],
            ['sorts', 'require', '41032'],
        ];
        return $this->com_update( 'sysconfig_group', $param, $rule, $field );
    }

    public function sys_config_group_delete($param = null) {
        $count = Db::name('sysconfig')->where( ['group_id' => $param['id'] ] )->count();
        if ( $count > 0 ){
            return ret(41035);
        }
        return $this->com_delete( 'sysconfig_group', [ 'id' => $param['id'] ] );
    }

    public function sys_config_create($param = null){
        $field = 'title,code, type, options, val, tips, group_id, extclass';
        $rule = [
            ['code', 'require|max:100|alphaDash|unique:sysconfig', '41021|41022|41023|41030'],
            ['title', 'require|max:100', '41012|41025'],
            ['type', 'require', '41026'],
            ['tips', 'max:200', '41027'],
            ['group_id', 'require', '41028']
        ];
        if ( $param['type'] == 'select' || $param['type'] == 'select_multiple' || $param['type'] == 'radio' ){
            if ( !$param['options'] ){
                return ret(41029);
            }
        }
        cache('system_config', null);
        return $this->com_create( 'sysconfig', $param, $rule, $field );
    }

    public function sys_config_update($param = null){
        $field = 'title, type, options, val, tips, group_id, extclass';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|max:100', '41012|41025'],
            ['type', 'require', '41026'],
            ['tips', 'max:200', '41027'],
            ['group_id', 'require', '41028']
        ];
        if ( $param['type'] == 'select' || $param['type'] == 'select_multiple' || $param['type'] == 'radio' ){
            if ( !$param['options'] ){
                return ret(41029);
            }
        }
        cache('system_config', null);
        return $this->com_update( 'sysconfig', $param, $rule, $field );
    }

    //批量修改配置
    public function sys_config_set($param = null){
        if ( !$param ){
            return ret(40002);
        }
        $count = 0;
        foreach($param as $v){
            if ( $v['type'] == 'select_multiple' ){
                $v['val'] = implode(',', $v['val']);
            }
            $res = db('sysconfig') ->where('id', $v['id']) ->update( ['val' => $v['val']] );
            if( $res != FALSE ){
                $count++;
            }
        }
        cache('system_config', null);
        return ret(0, $count);
    }

    public function sys_config_move($param = null){
        return $this->com_batch_set('sysconfig', $param['ids'], 'group_id', $param['group_id'] );
    }

    public function sys_config_delete($param = null){
        $rule = array(
            array('ids', 'require', '41031'),
        );
        $vaild = $this->validate($param,$rule);
        if($vaild !== true){
            return ret($vaild);
        }

        $count = 0;
        foreach($param['ids'] as $v){
            $res = db('sysconfig') ->where('id', $v) ->delete();
            if( $res != FALSE ){
                $count++;
            }
        }
        cache('system_config', null);
        return ret(0, $count);
    }

    //错误码操作
    public function error_code_list( $param = null ) {
        $list = $this->com_page_list( 'error_code',  $param );
        return ret(0,$list);
    }
    //创建之前获取code值
    public function create_get_code( $param = null ) {
        $res = Db::name('error_code')->max('code');
        $code = $res +1;
        return ret(0,$code);
    }
    

    public function error_code_create($param = null){
        $field = 'code,val,level,lang,remark';
        $rule = [
            ['code', 'require|number|unique:error_code', '41042|41052|41043']
        ];
        return $this->com_create( 'error_code', $param, $rule, $field );
    }

    public function error_code_update($param = null){
        $field = 'code,val,level,lang,remark';
        $rule = [
            ['id', 'require', '40002'],
            ['code', 'require|number|unique:error_code', '41042|41052|41043']
        ];
        return $this->com_update( 'error_code', $param, $rule, $field );
    }

    public function error_code_delete($param = null) {
        return $this->com_delete( 'error_code', [ 'id' => $param['id'] ] );
    }

    public function clear_cache( $param = null ){
        deldir( ROOT_PATH.'#r/' );
        return ret();
    }

     //日志操作
    public function log_list( $param = null ) {
        $list = $this->com_page_list( 'log',  $param );
        return ret(0,$list);
    }
    public function log_delete($param = null) {
        return $this->com_delete( 'log', [ 'id' => $param['id'] ] );
    }
    public function clear_log($param = null){
        Db::query("truncate table py_log");
    }

    //资源管理
    public function get_file_group_list($param = null){
        $list = Db::name('files_group')->select();
        return ret(0,$list);
    }
    
    public function get_files_list($param = null){
        $list = $this->com_page_list( 'files',  $param );
        $list['files_group'] = Db::name('files_group')->select();
        return ret(0,$list);
    }
    
    public function select_file_type($param = null){
        $list['data_list'] = Db::name('files')
        -> where( 'ext', 'in' ,$param['extensions'])
        -> limit( ( $param['page'] - 1 ) * $param['page_size'], $param['page_size'])
        -> select();
        $list['total_count'] = Db::name('files') -> where( 'ext', 'in' ,$param['extensions']) -> count();
        return ret(0,$list);
    }
    
    public function file_delete($param = null) {
        $map['id'] = ['in', $param['id'] ];
        return $this->com_delete( 'files', $map );
    }

    public function file_move($param = null) {
        return $this->com_batch_set('files', $param['ids'], 'group_id', $param['group_id'] );
    }
}
