<?php
namespace app\manager\controller;
use app\common\controller\Base;
use think\Config;
use think\Db;
class ManagerBase extends Base {
	public $manager_id;

	public $uid;
	public $user_info;
	public $login_url;

	public function _initialize() {
		parent::_initialize();
		//加载配置
		Config::load(APP_PATH.'/'.BIND_MODULE.'/config.php');
		//页面title
		$this->assign('pagetitle',$this->get_pagetitle());
		if ( strtolower(CONTROLLER_NAME) != 'login' ){
            $this->login_url = 'login/index';

            //获取用户基本信息、用户组
            $this->uid = session('uid');
            if ( !$this->uid ){
                $this -> redirect( $this->login_url );
            }
            $this->user_info = Db::name('user')->where( ['id' => $this->uid, 'status'=>1] )->find();
            if ( !$this->user_info ){
                $this -> error('用户不存在或禁用，请联系管理员', 'login/index');
            }

            $this->user_group = Db::name('user_group')->where( 'find_in_set('.$this->uid.',user)' )->select();
            if ( !$this->user_group ){
                $this -> error('不在任何用户组，无法使用功能！', 'login/index');
            }
        }

        //验证路径权限，暂时关闭，默认所有页面皆可访问
        if ( false ){
            $page_url = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
            $nav = Db::name('navigation')->where( [ 'url' => $page_url ] )->find();
            if ( !$nav ){
                $this -> error('页面未注册，不可使用');
            }
            if ( ! in_array( $nav['id'], $auth_nav ) ){
                $this -> error('没有访问页面的权限');
            }
        }

	}
	
	public function get_pagetitle(){
		return SC('site_title') ?: SYS_NAME . ' ' . SYS_VERSION;
	}

}
