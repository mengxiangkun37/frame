<?php
namespace app\web\controller;
use app\common\controller\Base;
use think\Config;
use think\Db;
class WebBase extends Base {
	public $openid;
	public $user_info;
	public $redirect_url;
	public $Wechat;

	public function _initialize() {
		parent::_initialize();
		//加载配置
		Config::load(APP_PATH.'/'.BIND_MODULE.'/config.php');
		$this->assign('pagetitle',$this->get_pagetitle());
	}
	
	public function get_pagetitle(){
		return SC('site_title');
	}

}
