<?php
namespace app\common\controller;
use think\Controller;
use think\Request;
class Base extends Controller {
    public function _initialize() {
        //php降低报错方法
        error_reporting(E_ERROR | E_PARSE);
        $request=Request::instance();
        defined('MODULE_NAME') or define('MODULE_NAME', $request->module() );
        defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $request->controller() );
        defined('ACTION_NAME') or define('ACTION_NAME', $request->action() );
        defined('NOWURL') or define('NOWURL', MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME );
        defined('NOWIP') or define('NOWIP', $request->ip() );
    }
}
