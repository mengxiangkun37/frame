<?php

/*
 * loan接口
 */


namespace app\manager\api;
use think\Db;

class Home extends ApiBase{
    public $question_types = [
        ['text' => '单选题', 'text_short' => '单', 'value' => 1, 'style' => 'blue--text'],
        // ['text' => '多选题', 'text_short' => '多', 'value' => 2, 'style' => 'purple--text'],
        ['text' => '判断题', 'text_short' => '判', 'value' => 3, 'style' => 'teal--text'],
        // ['text' => '填空', 'text_short' => '填', 'value' => 4, 'style' => 'lime--text']
    ];

    public function _initialize() {
        parent::_initialize();

//        $this->usefor_list = $this->com_get_tags('usefor');
    }

    //协税者管理
    //个人协税者
    public function get_member_list( $param = null ){
        $list = $this->com_page_list( 'member',  $param );
        return ret(0, $list);
    }
    
    public function expel_member( $param = null ){
        $res = Db::name('member')->where(['id'=>$param['id']])->find();
        Db::name('member')->where(['id'=>$param['id']])->update(['original_status'=> $res['status']]);
        Db::name('member')->where(['id'=>$param['id']])->update(['status'=>-2]);
        return ret(0);
    }
    public function recovery_member( $param = null ){
        $res = Db::name('member')->where(['id'=>$param['id']])->find();
        Db::name('member_out')->where(['member_id'=>$param['id']])->delete();
        Db::name('member')->where(['id'=>$param['id']])->update(['status'=> $res['original_status']]);
        return ret(0);
    }

    //协税者退出列表
    public function member_out_list( $param = null ){
        $list = $this->com_page_list( 'member_out',  $param );
        foreach ($list['data_list'] as $key ) {
            $ids[] = $key['member_id'];
        }
        $list['member'] = Db::name('member') -> where( 'id', 'in' ,$ids) -> select();
        return ret(0, $list);
    }

    public function set_member_out( $param = null ){
        $res = Db::name('member_out')->where(['id'=>$param['id']])->find();
        $member = Db::name('member')->where(['id'=>$res['member_id']])->find();
        Db::name('member_out')->where(['id'=>$param['id']])->update(['status'=>-1,'utime'=>time()]);
        Db::name('member')->where(['id'=>$member['id']])->update(['status'=>-1]);

        return ret(0);
    }


    //分团管理
    
    public function sub_group_list( $param = null ){
        $list = $this->com_page_list( 'sub_group',  $param );
        return ret(0, $list);
    }
    
    public function sub_group_create( $param = null ){
        $field = 'name,link,ctime,address,phone,city,province,area';
        $rule = array(
            array('name', 'require', '47157'),
            array('phone', 'require', '47160'),
            array('link', 'require', '47158'),
            array('address', 'require', '47159'),
        );
        $param['ctime'] = time();
        return $this->com_create( 'sub_group', $param, $rule, $field );
    }

    public function sub_group_update($param = null){
        $field = 'name,link,utime,address,phone,city,province,area';
        $rule = [
            array('name', 'require', '47157'),
            array('phone', 'require', '47160'),
            array('link', 'require', '47158'),
            array('address', 'require', '47159'),
        ];
        $param['utime'] = time();
        return $this->com_update( 'sub_group', $param, $rule, $field );
    }

    public function sub_group_delete($param = null) {
        return $this->com_delete( 'sub_group', [ 'id' => $param['id'] ] );
    }

    //视频管理
    public function video_list( $param = null ){
        $list = Db::name('video')->select();
        return ret(0, $list);
    }
    public function video_create( $param = null ){
        $field = 'title,url,ctime ,duration';
        $rule = array(
            array('title', 'require', '47132'),
            array('url', 'require', '47133'),
        );
        $param['ctime'] = time();
        return $this->com_create( 'video', $param, $rule, $field );
    }
    
    public function video_delete( $param = null ){
        $video = Db::name('video')->where(['id'=>$param['id']])->find();
        if($video['status'] == 1){
            return ret(47134);
        }
        Db::name('video')->where(['id'=>$param['id']])->delete();
        return ret(0);
    }
    
    public function set_video_status( $param = null ){
        $video = Db::name('video')->where(['id'=>$param['id']])->find();
        if($video['status'] == 1){
            return ret(47135);
        }
        Db::name('video')->where(['status'=>1 ])->update(['status'=>0]);
        Db::name('video')->where(['id'=>$param['id']])->update(['status'=>1]);
        return ret(0);
    }


    //题目方法
    public function question_list( $param = null ) {

        $list = $this->com_page_list( 'question',  $param );

        $question_ids = [];
        foreach ( $list['data_list'] as $k => $v ){
            $question_ids[] = $v['id'];
        }
        $list['option_list'] = $question_ids ? Db::name('question_option') -> where( ['question_id' => ['in', $question_ids]] ) -> select() : [];
        $list['question_types'] = $this->question_types;

        return ret(0,$list);
    }
    //option的check
    private function question_check( $param = null ){
        //验证选项
        if ( count($param['options']) == 0 ){
            return ret( 45501 );
        }
        $title_check = true;
        $correct_count = 0;
        foreach ($param['options'] as $k => $v) {
            if ( !$v['title'] ){
                $title_check = false;
            }
            if ( $v['correct'] ){
                $correct_count++;
            }
        }
        if ( !$title_check ){
            return ret( 45504 );
        }

        $param['type'] = intval($param['type']);
        switch ( $param['type'] ){
            case 1:
            case 3:
                if ( $correct_count != 1 ){
                    return ret(45503);
                }
                break;
            case 2:
                if ( $correct_count < 1 ){
                    return ret(45506);
                }
                break;
            default:
                break;

        }
        return ret(0);
    }

    public function question_create($param = null){

        $check_res = $this->question_check( $param );
        if ( $check_res['code'] ){
            return $check_res;
        }

        //问题部分
        $field = 'type, utime, content';
        $rule = [
            ['content', 'require', '45504'],
            ['type', 'require', '45505'],
        ];
        $param['utime'] = time();
        $question_res = $this->com_create( 'question', $param, $rule, $field );
        if ( $question_res['code'] ){
            return $question_res;
        }

        $opt_field = 'title, remark, question_id, correct';
        foreach ($param['options'] as $k => $v) {
            $v['question_id'] = $question_res['data'];
            $this->com_create( 'question_option', $v, [], $opt_field );
        }
        return ret(0, $question_res);

    }
    public function question_update($param = null){

        $check_res = $this->question_check( $param );
        if ( $check_res['code'] ){
            return $check_res;
        }

        $field = ' type, utime, content';
        $rule = [
            ['id', 'require', '40002'],
            ['content', 'require', '45504'],
            ['type', 'require', '45505'],
        ];
        $param['utime'] = time();
        $question_res = $this->com_update( 'question', $param, $rule, $field );
        if ( $question_res['code'] ){
            return $question_res;
        }

        $opt_field = 'title, remark, question_id, correct';
        //先删除原有option，再新增，以应对选项数量的减少
        $this->com_delete( 'question_option', [ 'question_id' => $param['id'] ] );
        foreach ($param['options'] as $k => $v) {
            $this->com_create( 'question_option', $v, [], $opt_field );
        }
        return ret(0, $question_res);
    }

    public function question_delete($param = null) {
        if( !$param['id'] ){
            return ret(40002);
        }
        $this->com_delete( 'question_option', [ 'question_id' => ['in', $param['id'] ] ] );
        return $this->com_delete( 'question', [ 'id' => ['in', $param['id'] ] ] );
    }



    //首页
    public function get_index_data( $param = null ) {
        $list['question_count'] = Db::name('question')->count();
        $list['sub_group_count'] = Db::name('sub_group')->count();
        $list['member_count'] = Db::name('member')->count();
        $list['personal_count'] = Db::name('member')->where(['type'=>1])->count();
        $list['company_count'] = Db::name('member')->where(['type'=>2])->count();
        $list['out_count'] = Db::name('member_out')->count();

        // $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        // $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        // $list['rece_count'] = Db::name('order')->where('ctime','between time',[$beginToday,$endToday])->where(['status'=>['>',0]])->count();
        // $list['no_rece_count'] = Db::name('order')->where('ctime','between time',[$beginToday,$endToday])->where(['status'=>['>',-1]])->count();
        // $list['sell_goods'] = Db::name('order_item')->where('ctime','between time',[$beginToday,$endToday])->where(['status'=>1])->sum('buys');
        // $list['sales_money'] = Db::name('order')->where('ctime','between time',[$beginToday,$endToday])->where(['status'=>['>',0]])->sum('received_amount');
        // $account_arr = array();
        // $order_arr = array();
        // $date = array();
        // for($i = 6; $i >= 0; $i --){
        //     $begin_time =  mktime(0,0,0,date('m'),date('d')-$i,date('Y'));
        //     $end_time =  mktime(0,0,0,date('m'),date('d')+1-$i,date('Y'))-1;
        //     $account_arr[] = Db::name('order')->where('ctime','between time',[$begin_time,$end_time])->where(['status'=>['>',0]])->sum('received_amount');
        //     $order_arr[] = Db::name('order')->where('ctime','between time',[$begin_time,$end_time])->where(['status'=>['>',-1]])->count();
        //     $date[] = date('Y-m-d',$begin_time);
        // }
        // $list['account']= $account_arr;
        // $list['order']= $order_arr;
        // $list['date']= $date;
        return ret(0,$list);
    }

    public function index_config( $param = null ){
        $res['goods_list'] = Db::name('goods')->where(['status'=>1])->order('id desc')->field('id, title')->select();

        return ret(0, $res);
    }
}
