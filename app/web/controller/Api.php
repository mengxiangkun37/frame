<?php
namespace app\web\controller;
use app\common\controller\Base;
use think\Db;

class Api extends Base {

    protected $deny_unregistered = true;   //如请求中有数据库中不存在的接口，则整个接口拒绝访问！否则其他接口将继续执行（理论上执行不了，会报错）
    protected $error_report = true;     //错误返回真实错误码
    protected $error_skip = false;      //如请求中有某个接口错误，则全部请求失败且不报错

    public $member_id;

	public function _initialize() {
		parent::_initialize();
		$this->member_id = session('member_id') ?: 0;
	}
	
	public function _empty(){
		return $this -> index();
	}

	public function can_use( $api ){
        if ( !$api ){
            return 20003;
        }

        //检测登录
        if ( $api['login_require'] == 0 ){
            return 0;
        }
        if ( $api['login_require'] == 1 && !$this->member_id ){
            return 40006;
        }
        return 0;
    }

    public function get_api( $api ){
        //限定MODULE_NAME
        $data = Db::name('api') -> where( ['api' => $api] ) -> find();
        return $data;
    }


    //调用接口主方法
    // input:   action = api    data
    public function index() {
        config([
            'default_return_type'=>'json',
            'default_ajax_return'=>'json'
        ]);

        //1.声明变量
        $input = input();
        if ( !$input['action'] ){
            return ret(20002);
        }

        //2.检测出要执行的api
        $todo = $this->get_api( $input['action'] );

//        $api_list = $this->api_list();

//        foreach ( $api_list as $va ){
//            if ( $va['api'] == $input['action'] ){
//                $todo = $va;
//                break;
//            }
//        }


        //3.检测权限
        $can_use = $this->can_use($todo);
        if ( $can_use != 0 ){
            return ret($can_use, $input['action']);
        }

        //4.执行指令
        $res = action( $todo['CONTROLLER_NAME'].'/'.$todo['ACTION_NAME'], [ 'param' => $input['param'], 'member_id' => $this->member_id ] , 'api' );
        return $res;
    }

}
