<?php
namespace app\web\controller;
use think\Db;

class Index extends WebBase {

    public $member_id;
    public $member_name;

	public function _initialize() {
		parent::_initialize();

		$this->member_id = session('member_id') ?: 0;
		$this->assign('member_id', $this->member_id);

        $this->member_name = session('member_name') ?: 0;
        $this->assign('member_name', $this->member_name);

        $site_navigation = Db::name('classify')->where( ['type'=>['neq',5],'status' =>1] )->order('sorts asc, id asc')->field('content', true)->select();
        $this->assign( 'site_navigation', json_encode($site_navigation) );
  
        $member_info =[];
        if( $this->member_id ){
            $member_info = Db::name('member')->where( ['id' => $this->member_id ] )->field('password', true)->find();
        }
        $this->assign( 'member_info', json_encode($member_info) );

	}

	public function _empty(){
		return $this -> index();
	}

    public function test(){
	    var_dump(session_id());
        var_dump( session('') );
    }
    public function clear(){
        var_dump( session(null) );
    }

    public function ttt(){
        $arr1 = ['MAC','IP',];
        $arr2 = ['mac','ip'];
        $user_answer = array_map( 'strtoupper', $arr2 );

        sort( $arr1 );
        sort($user_answer);

        var_dump($arr1);
        var_dump($user_answer);
        var_dump($arr1 == $user_answer);
    }

    /*
    获取页面配置
    目前只作为读取页面title、keywords、description
    未来获取css、js等更详细配置与模版样式控制
    */
    public function assign_page_config( $type = 'index', $ext = NULL ){

        //引入公共的脚本与css，自定义的依然由页面模版引入
        //$info['header_import'] = htmlspecialchars_decode(SC('header_import'));
        //$info['footer_import'] = htmlspecialchars_decode(SC('footer_import'));

        //依据类型判断添加title、keywords、description
        switch( $type ){
            case 'index':
                $info['title'] = SC('site_title');
                $info['keywords'] = SC('site_keywords');
                $info['description'] = SC('site_description');
                break;

            case 'lists':
            case 'article':
                $info['title'] = $ext['seotitle'] ? $ext['seotitle'] : $ext['title'];
                $info['keywords'] = $ext['keywords'];
                $info['description'] = $ext['description'];
                break;

            default:
                $info = $ext;
                break;
        }
        $this->assign('page_config', $info);
    }


    public function index() {

        $this->assign_page_config('index');
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch();
    }

    //通用列表页
    public function lists() {
        //注入栏目基本信息
        $classid = input('classid', 0);
        if (!$classid) {
            return $this -> index();
        }
        $this->assign('classid', $classid);

        $ids = [];
        $top = [];
        $pid = Db::name('classify')->where(['id'=>$classid])->find();
        if($pid['pid']>0){
            $p_pid = Db::name('classify')->where(['id'=>$pid['pid']])->find();
            if($p_pid['pid']>0){
                $p_p_pid = Db::name('classify')->where(['id'=>$p_pid['pid']])->find();
                $top = $p_p_pid;
                array_push($ids, $p_p_pid['id']);
            }else{
                $top = $p_pid;
                array_push($ids, $p_pid['id']);
            }
        }else{
            $top = $pid;
            array_push($ids, $pid['id']);
        }
       
        $child = Db::name('classify')->where(['pid'=>$top['id']])->select();
        if(count($child) > 0){
            foreach ($child as $key) {
                array_push($ids,$key['id']);
                $c_child = Db::name('classify')->where(['pid'=>$key['id']])->select();
                if(count($c_child) > 0){
                    foreach ($c_child as $sub_key) {
                        array_push($ids,$sub_key['id']);
                    }
                }
            }
        }
        $menu_list = Db::name('classify') -> where(['id'=>['in',$ids]]) ->select();
        $this->assign('menu_list',json_encode($menu_list));

        
        $now_class = getClassify($classid, true);

        $tpl = $now_class['listtpl'] ? $now_class['listtpl'] : 'lists'; //获取模版名称
        $this -> assign('now_class', $now_class);

        $parent_class = $now_class['pid'] == 0 ? $now_class : getClassify( $now_class['pid'], false );
        $this -> assign('parent_class', $parent_class);

        //如果是父级栏目，尝试获取子级的新闻，综合起来
        $pagesize = SC('pagesize') ?: 20;
//        $pagesize = 2;
        $class_sub = get_classify_list($classid);
        if($class_sub){
            $class_ids = [];
            foreach($class_sub as $v){
                $class_ids[]=$v['id'];
            }
            $arcs=db('article') -> where(array('classid'=>array('in',$class_ids))) ->field('content',TRUE)-> order('top desc,sorts desc,utime desc,id desc')->paginate($pagesize,false,[ 'query' => ['classid' => $classid] ]);
        }else{
            $arcs=db('article') -> where(array('classid'=>$classid)) ->field('content',TRUE)-> order('top desc,sorts asc,utime desc,id desc')->paginate($pagesize, false,[ 'query' => ['classid' => $classid] ]);
        }
        $this->assign('arcs',$arcs);
        $this->assign('class_sub', $class_sub);
        $this->assign_page_config('lists', $now_class);

        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch($tpl);
    }

    //内容页
    public function show(){
        $id=input('arcid');
        if (!$id) {
            return $this -> index();
        }

        //注入文章信息
        $info = getOneArc($id, true);
        $this -> assign('info', $info);

        $session_click_key = 'arc_click_' . $id;
        if(!session($session_click_key)){
            db('article') -> where(array('id' => $id)) -> setInc('click');
            session($session_click_key , 1);
        }

        //注入文章所属栏目
        $classid = $info['classid'];
        $this->assign('classid', $classid);

        $activeclass = getClassify($classid, true);
        $this -> assign('now_class', $activeclass);
        //获取父级
        if ($activeclass['pid'] != 0) {
            $activeclass = getClassify($activeclass['pid']);
        }
        $this -> assign('activeclass', $activeclass);
        //获取模版信息
        $arctpl = $info['arctpl'] ? $info['arctpl'] : $activeclass['arctpl'];
        $arctpl = $arctpl ? $arctpl : 'show';

        //输出标题栏信息
        $this -> assign_page_config('article', $info);
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch($arctpl);
    }

    //注册
    public function reg() {
        //注入栏目基本信息
        $this->assign_page_config('', [ 'title' => '用户注册' ]);
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch();
    }
    //查询
    public function search() {
        //注入栏目基本信息
        $this->assign_page_config('', [ 'title' => '志愿者查询' ]);
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch();
    }
    //登录
    public function login() {
        //注入栏目基本信息
        $this->assign_page_config('', [ 'title' => '登录' ]);
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch();
    }

    public function cn_txt(){
        $this -> view -> engine -> layout('index/cn_txt');
        return $this -> fetch();
    }
    public function guicheng_txt(){
        $this -> view -> engine -> layout('index/guicheng_txt');
        return $this -> fetch();
    }
    //个人页面
    public function personal(){
        $this->assign_page_config('', [ 'title' => '个人中心' ]);
        $this -> view -> engine -> layout('public/layout');
        return $this -> fetch();
    }

    //保存留言
    public function msg_save(){
        $web_message_sended = session('web_message_sended');
        if ( $web_message_sended ){
            return ['code'=>40002, 'msg'=>'您已经留言过了，请耐心等待我们的回复'];
        }
        $input = input();
        $rule = array(
            array('name', 'require|max:50', '请输入名称|名称最多50个字符'),
            array('phone', 'require|max:50', '请输入联系电话|联系电话位数有误'),
            array('content', 'require|max:200', '请输入留言内容|留言内容最多200个字符'),
        );
        $result = $this -> validate($input, $rule);
        if ($result !== TRUE) {
            return ['code'=>40002, 'msg'=>$result];
        }
        $input['ctime'] = time();
        $res = Db::name('message') -> field('name, phone, content, ctime') ->insert($input);
        if($res){
            session('web_message_sended', true);
            return ['code' => 0, 'msg' => '留言成功，我们将尽快回复您！'];
        }else{
            return ['code' => 40002, 'msg' => '保存失败'];
        }
    }

}
