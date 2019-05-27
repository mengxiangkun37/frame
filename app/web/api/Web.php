<?php

/*
 * 用户接口
 */


namespace app\web\api;
use think\Db;

class Web extends WebBase{


    public function _initialize() {
        parent::_initialize();
    }

    //通用获取配置的方法，传数组，返回数组对应列表，传单字符串，返回列表
    public function tags_list($param = null){
        $res = $this->com_get_tags( $param );
        return ret(0, $res);
    }

       //左侧导航

    
    public function left_news( $param = null ) {
    
        $list['left_news_list'] = Db::name('article') -> where( 'classid','in','103,116,117' )->order('utime desc')->limit(8) -> select();
        return ret(0,$list);
    }

    //申请退出
    
    public function apply_quit( $param = null, $member_id){
        $res = Db::name('member')->where(['id'=>$member_id])->find();
        Db::name('member')->where(['id'=>$member_id])->update(['original_status'=> $res['status']]);
        Db::name('member')->where(['id'=>$member_id])->update(['status'=>2]);
        $out = Db::name('member_out')->where(['member_id'=>$member_id])->find();
        if($out){
            return ret(47162);
        }
        $data['member_id'] = $member_id;
        $data['experience'] = $param['experience'];
        $data['cause'] = $param['cause'];
        $data['type'] = $res['type'];
        $data['ctime'] = time();
        Db::name('member_out')->insert($data);
        return ret(0);
    }

    //分团
    public function get_sub_group( $param = null, $member_id){
        $list['sub_group_list'] = Db::name('sub_group')->select();
        $list['member_group_list'] = Db::name('member_group')->where(['member_id'=>$member_id])->select();
        return ret(0,$list);
    }
    public function join_group( $param = null, $member_id){
        $data['member_id'] = $member_id;
        $data['sub_group_id'] = $param['member_group'];
        $data['ctime'] = time();
        $is_join = Db::name('member_group')->where(['member_id'=>$member_id,'sub_group_id'=>$param['member_group']])->count();
        if($is_join >0 ){
            return ret(47161);
        }else{
            Db::name('member_group')->where(['member_id'=>$member_id,'sub_group_id'=>$param['member_group']])->insert($data);
            return ret(0);
        }
    }

    //视频
    public function get_video($param = null, $member_id){
        $list['member'] = Db::name('member')->where(['id'=>$member_id])->find();
        $list['video'] = Db::name('video')->where(['status'=>1])->find();
        if($list['video']){
            return ret(0,$list);
        }
        return ret(0);
    }

    public function member_video_over($param = null, $member_id){
        $member = Db::name('member')->where(['id'=>$member_id])->find();
        $num_order = Db::name('member')->max('v_number_order');
        if($member['is_video_pass']>0){
            if($member['is_test_pass']>0){
                Db::name('member')->where(['id'=>$member_id])->update(['v_number'=>$this->get_v_number($num_order),'status'=>1,'v_number_order'=>$num_order+1]);  
                return ret(0);
            }
            return ret(0);
        }else{
            Db::name('member')->where(['id'=>$member_id])->update(['is_video_pass'=>1]);
            if($member['is_test_pass']>0){
                Db::name('member')->where(['id'=>$member_id])->update(['v_number'=>$this->get_v_number($num_order),'status'=>1,'v_number_order'=>$num_order+1]);  
                return ret(0);
            }
            return ret(0);
        }
        
    }
    
    public function submit_answer($param = null, $member_id){
        $member = Db::name('member')->where(['id'=>$member_id])->find();
        $num_order = Db::name('member')->max('v_number_order');
        if($member['is_test_pass']>0){
            if($member['is_video_pass']>0){
                Db::name('member')->where(['id'=>$member_id])->update(['v_number'=>$this->get_v_number($num_order),'status'=>1,'v_number_order'=>$num_order+1]);  
                return ret(0);
            }
            return ret(0);
        }else{
            Db::name('member')->where(['id'=>$member_id])->update(['is_test_pass'=>1]);
            if($member['is_video_pass']>0){
                Db::name('member')->where(['id'=>$member_id])->update(['v_number'=>$this->get_v_number($num_order),'status'=>1,'v_number_order'=>$num_order+1]);  
                return ret(0);
            }
            return ret(0);
        }
    }

    //生成志愿者编号
    public function get_v_number($num_order){
        $str = '';
        $year = date('Y');
        $city = 'text';
        $num = $num_order + 1;
        $str = $year.$city.sprintf("%06d", $num);
        return $str;
    }

    //查询志愿者
    public function search_member( $param = null){
        if($param['type'] == 1){
            $res = Db::name('member')->where(['type'=>1,'p_name'=>$param['p_name'],'p_id_number'=>$param['p_id_number'],'status'=>['>',0]])->select();
        }else{
            $res = Db::name('member')->where(['type'=>2,'c_name'=>$param['c_name'],'c_credit_code'=>$param['c_credit_code'],'status'=>['>',0]])->select();
        }
        return ret(0,$res );
    }
    
    public function get_questions($param = null, $member_id){
        $list['questions'] = Db::name('question')->orderRaw('rand()')->limit(5)->select();
        foreach ($list['questions'] as $key ) {
            $ids[] = $key['id'];
        }
        $list['question_options'] = Db::name('question_option')->where(['question_id'=>['in',$ids]])->select();
        return ret(0,$list);
    }   

    //个人中心
    public function get_member_info($param = null, $member_id){
        $res = Db::name('member')->where(['id'=>$member_id])->find();
        return ret(0,$res);
    }
    public function member_edit($param = null, $member_id){
        $field = 'p_name,type ,p_sex,p_nationality,p_education,p_phone,p_id_number,p_company,p_parttime_job,p_major,
        p_job,p_wx,p_address,p_email,p_resume,p_experience,p_specialty,p_service_content,p_promise,c_name,c_credit_code,c_capital,c_unit_type,c_found_time,
        c_email,c_address,c_workers_number,c_accounting_number,c_customer_number,c_lastyear_income,c_service_income,c_legal_person,c_legal_person_idnumber,c_legal_person_phone,
        c_liaison_person,c_liaison_person_phone,c_liaison_person_wx,c_regd,c_regd_name,c_business_range,c_service_content,area,city,province,address';
        if($param['type']==1){
            $rule = array(
                array('p_name', 'require', '47139'),
                array('p_id_number', 'require', '47140'),
                array('p_sex', 'require', '47141'),
                array('p_phone', 'require', '47142'),
                array('p_education', 'require', '47143'),
                array('p_company', 'require', '47144'),
                array('p_parttime_job', 'require', '47145'),
                array('p_promise', 'require', '47146'),
            );
        }else{
            $rule = array(
                array('c_name', 'require', '47147'),
                array('c_credit_code', 'require', '47148'),
                array('c_legal_person', 'require', '47149'),
                array('c_legal_person_idnumber', 'require', '47150'),
                array('c_legal_person_phone', 'require', '47151'),
                array('c_liaison_person', 'require', '47152'),
                array('c_liaison_person_phone', 'require', '47153'),
                array('c_liaison_person_wx', 'require', '47154'),
            );
        }
    
        $res = $this->com_update( 'member', $param, $rule, $field );
        return ret(0,$res);
    }

    //用户注册、登录方法
    public function member_reg($param = null){

        $se = session('phone_code');
        if ( !$se || $se['status'] == 0 || ( time() - $se['time'] ) > 600 || $param['phone'] != $se['phone'] ){
            session('phone_code', null);
            return ret(46003);
        }
        if ( $param['phone_code'] != $se['code'] ){
            return ret(46004);
        }
  
        if( Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> count() > 0 ){
            return ret(40041);
        }
    
        $field = 'p_name,type ,p_sex,p_nationality,p_education,p_phone,p_id_number,p_company,p_parttime_job,p_major,
        p_job,p_wx,p_email,p_resume,p_experience,p_specialty,p_service_content,p_promise,c_name,c_credit_code,c_capital,c_unit_type,c_found_time,
        c_email,c_workers_number,c_accounting_number,c_customer_number,c_lastyear_income,c_service_income,c_legal_person,c_legal_person_idnumber,c_legal_person_phone,
        c_liaison_person,c_liaison_person_phone,c_liaison_person_wx,c_regd,c_regd_name,c_business_range,c_service_content,phone,area,city,province,address';
        if($param['type'] == 1){
            $rule = array(
                array('p_name', 'require', '47139'),
                array('p_id_number', 'require|unique:member', '47140|47155'),
                array('p_sex', 'require', '47141'),
                array('p_phone', 'require', '47142'),
                array('p_education', 'require', '47143'),
                array('p_company', 'require', '47144'),
                array('p_parttime_job', 'require', '47145'),
                array('p_promise', 'require', '47146'),
                array('phone', 'require', '47136'),
                array('type', 'require', '47137'),
            );
        }else{
            $rule = array(
                array('phone', 'require', '47136'),
                array('type', 'require', '47137'),
                array('c_name', 'require', '47147'),
                array('c_credit_code', 'require|unique:member', '47148|47156'),
                array('c_legal_person', 'require', '47149'),
                array('c_legal_person_idnumber', 'require', '47150'),
                array('c_legal_person_phone', 'require', '47151'),
                array('c_liaison_person', 'require', '47152'),
                array('c_liaison_person_phone', 'require', '47153'),
                array('c_liaison_person_wx', 'require', '47154'),
            );
        }
      
        $res = $this->com_create( 'member', $param, $rule, $field );

        if ( $res['code'] == 0 ){
            session('member_id', $res['data']);
            session('member_name', $param['name']);
            session('phone_code', null);
        }
        return $res;
    }

    public function member_login($param = null){
        $se = session('phone_code');
        if ( !$se || $se['status'] == 0 || ( time() - $se['time'] ) > 600 || $param['phone'] != $se['phone'] ){
            session('phone_code', null);
            return ret(46003);
        }
        if ( $param['phone_code'] != $se['code'] ){
            return ret(46004);
        }
  
        if( Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> count() == 0 ){
            return ret(47138);
        }
       
        $res = Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> find();
        if($res['status'] == -2){
            return ret(47163);
        }
        if($res['status'] == -1){
            return ret(47164);
        }
        if($res['status'] > -1){
            session('member_id', $res['id']);
            session('member_name', $res['name']);
            return ret(0);
        }
    }

    public function member_logout(){
        session(null);  //清理全部的session
        return ret();
    }

    //通用方法

    //栏目ID有：直接写classid，逗号间隔多个classid，【.】操作符，取本栏目下所有子栏目的id
    //<li>使用方法1：getArcs(92,2,$map);	//根据$map条件获取id为92的栏目下的两条文章</li>
    //<li>使用方法2：getArcs('92,93',2,$map);	//根据$map条件获取id为92和93的栏目下的两条文章</li>
    //<li>使用方法3：getArcs('70.',2,$map);	//获取pid为70的栏目下的两条文章</li>
    // public function getArcs($classid, $limit = '6', $where = array(), $hascontent = FALSE, $only_field) {
        public function getArcs($param = null) {  
            if (!$param['classid']) {
                return ret(0);
            }
            $map['status'] = 1;
            if (strpos($param['classid'], '.') !== false) {
                $classid_arr = explode('.', $param['classid']);
                $classify_lists = $this -> get_classify_list($classid_arr[0], 0);
                foreach ($classify_lists as $v) {
                    $class_ids[] = $v['id'];
                }
                $class_ids[] = $classid_arr[0];
                if ($class_ids) {
                    $map['classid'] = array('in', $class_ids);
                }
            } elseif (strpos($param['classid'], ',') !== false) {
                $class_ids = explode(',', $param['classid']);
                $map['classid'] = array('in', $class_ids);
            } else {
                $map['classid'] = $param['classid'];
            }
            // -> where(['pic'=>1])
            $arcids = Db::name('article') -> where($map) -> limit($param['limit']) -> order('top desc,sorts asc, utime desc,id desc') -> column('id');
            $arcs = [];
            foreach ($arcids as $v) {
                $arcs[] = $this -> getOneArc($v, $param['hascontent'], $param['only_field']);
            }
            if ( $param['only_field'] ){
                foreach ( $arcs as $kr => $r){
                    $arcs[$kr] = $r[0];
                }
            }
            return ret(0,$arcs);
        }
    
        public function getOneArc($arcid, $hascontent = FALSE, $only_field) {
            if (!$arcid) {
                return false;
            }
            $db = Db::name('article') -> where(array('id' => $arcid));
            if ( !$hascontent) {
                $db -> field('content', true);
            }
            if ( $only_field ){
                $res = $db->column($only_field);
            }else{
                $res = $db->find();
            }
        
            if (!$res) {
                return false;
            }
        
            switch ( $res['type'] ) {
                case 2 :
                    $res['article_link'] = $res['url'];
                    break;
                case 3 :
                    $res['article_link'] = $res['url'] . '" target="_blank';
                    break;
                case 1 :
                default :
                $res['article_link'] = url('show', array('arcid' => $res['id']));
                    break;
            }
            return $res;
        }
    
//        public function get_tree_classify() {
//            $classify = $this->get_classify_list(0);
//            foreach ($classify as $k => $v) {
//                $classify[$k]['sub'] = $this->get_classify_list($v['id']);
//            }
//            return $classify;
//        }
        
        //参数pid，取出pid下子栏目列表
        public function get_classify_list($pid = 0) {
            $res = [];
            $where = array(
                'pid' => $pid,
                'type' => array('neq', 5 ),
                'status' => 1
            );
            $ids = Db::name('classify') -> where( $where ) -> field('id') -> order('sorts asc, id asc') -> select();
            if (!$ids) return $res;
            foreach ($ids as $k => $v) {
                $res[] = getClassify($v['id']);
            }
            return $res;
        }
        
        public function get_longtext( $param = null){
            $res = Db::name('longtext')->where(['id'=>1])->find();
            return ret(0,$res);
        }

  

}
