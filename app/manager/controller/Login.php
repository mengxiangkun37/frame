<?php
namespace app\manager\controller;
use think\captcha;
class Login extends ManagerBase {

	public function _initialize() {
		parent::_initialize();
	}

	public function index() {
		return view();
	}

}
