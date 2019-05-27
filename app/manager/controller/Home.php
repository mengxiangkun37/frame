<?php
namespace app\manager\controller;
use think\Db;

class Home extends ManagerBase {

	public function _initialize() {
		parent::_initialize();
	}
	
	public function _empty( $name ){
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch( $name );
	}


}
