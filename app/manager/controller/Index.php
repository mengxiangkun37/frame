<?php
namespace app\manager\controller;
use think\Db;

class Index extends ManagerBase {

	public function _initialize() {
		parent::_initialize();
	}
	
	public function _empty($name){
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch( $name );
	}

	public function index() {
		$this -> view -> engine -> layout('public/layout');

//		$apis = Db::name('api_home')->field('id', true)->select();
//		foreach ($apis as $v){
//            $v['MODULE_NAME'] = 'web_vue';
//		    Db::name('api')->insert( $v );
//        }

		return $this -> fetch();
	}


}
