<?php
namespace app\manager\controller;
use app\common\controller\Base;
use think\Db;

class Api extends Base {

    public $uid;
    public $user_info = false;

	public function _initialize() {
		parent::_initialize();
		$this->uid = session('uid');
	}
	
	public function _empty(){
		return $this -> index();
	}

    //调用接口主方法
    // input:   action = api    data
    public function index() {
        config([
            'default_return_type'=>'json',
            'default_ajax_return'=>'json'
        ]);

        //用户数据

        //1.声明变量
        $input = input();
        if ( !$input['action'] ){
            return ret(20002);
        }

        //2.检测出要执行的api
        $todo = Db::name('api') -> where( ['api' => $input['action'], 'MODULE_NAME' => MODULE_NAME ] ) -> find();

        //3.检测权限
        $can_use = $this->can_use($todo);
        if ( $can_use != 0 ){
            return ret($can_use, $input['action']);
        }

        //4.执行指令
        $res = action( $todo['CONTROLLER_NAME'].'/'.$todo['ACTION_NAME'], [ 'param' => $input['param'], 'user' => $this->user_info ] , 'api' );

        //5.记录日志
        if( ( $res['code'] == 0 && $todo['success_require'] == 1 ) || ( $res['code'] > 0 && $todo['error_require'] == 1 ) ){
            $this->log_create($res,$todo);
        }

        return $res;

    }

    public function log_create($res,$todo){
        $log_data=[];
        $log_data['api_name']=$todo['ACTION_NAME'];
        $log_data['controller']= $todo['CONTROLLER_NAME'];
        $log_data['code']=$res['code'];
        // if($res['code'] == 40001 ||$res['code'] == 40002){
        //     $log_data['level']= 5;
        // }
        $log_data['user_id']= $this->uid ?: session('uid');
        $log_data['input'] = var_export( input() , true);
        $log_data['ctime'] = time();
        Db::name('log')->insert($log_data);
    }



    //检测api能否使用
    private function can_use( $api ){
        if ( !$api ){
            return 20003;
        }

        //检测登录
        if ( $api['login_require'] == 1 && !$this->uid ){
            return 40006;
        }
        if ( $api['login_require'] == 0 ){
            return 0;
        }

        //检测用户是否正常
        $this->user_info = Db::name('user')->where( ['id' => $this->uid, 'status' => 1] )->field('password', true)->find();
        if ( !$this->user_info ){
            return 40007;
        }

        //检测用户组权限，支持一人多组
        $user_groups = Db::name('user_group')->where( 'find_in_set('.$this->uid.',user)' )->select();
        if ( !$user_groups ){
            return 40008;
        }

        //组合权限，判断是否为管理员
        $merge_api = '';
        $merge_nav = '';
        foreach ($user_groups as $k => $v){
            if ( $v['id'] == 1 ){
                $this->user_info['is_admin'] = true;
                break;
            }
            $merge_api .= $v['api'];
            $merge_nav .= $v['nav'];
        }

        if ( $this->user_info['is_admin'] ){
            return 0;
        }

        $merge_api = explode(',', $merge_api);
        $merge_nav = explode(',', $merge_nav);
        $this->user_info['api'] = array_unique($merge_api);
        $this->user_info['nav'] = array_unique($merge_nav);
        if ( !in_array( $api['id'], $this->user_info['api'] ) ){
            return 40009;
        }

        return 0;
    }


}
