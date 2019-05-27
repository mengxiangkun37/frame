<?php

/*
 * 在线考试接口，包括：exam、testing、paper、question等系列方法
 * 20190228
 *
 */


namespace app\web\api;
use think\Db;

class Paper extends WebBase{


    public function _initialize() {
        parent::_initialize();
    }


    //在线考试方法
    public function exam_list( $param = null, $member_id ) {
        $param['search'][] = ['field'=>'status', 'value' => 18, 'type'=>'select'];

        $list = $this->com_page_list( 'exam',  $param );
        $exam_ids = [];
        $teaching_ids = [];
        foreach ($list['data_list'] as $v) {
            $exam_ids[] = $v['id'];
            if ( $v['teaching_id'] > 0 ){
                $teaching_ids[] = $v['teaching_id'];
            }
        }
        $list['teaching_list'] = $teaching_ids ? Db::name('teaching')->where( ['id' => ['in', $teaching_ids] ] )->field('id, title') -> select() : [];
        $list['exam_sign_list'] = $member_id ? Db::name('exam_sign')->where( ['member_id' => $member_id ] ) -> select() : [];
        $list['cert_list'] = Db::name('cert') -> select();

        return ret(0,$list);
    }

    public function sign_exam( $param = null, $member_id ) {
        if ( !$param['id'] ){
            return ret( 40001, null, '考试ID有误');
        }

        $now_time = time();
        $exam_info = Db::name('exam') ->where(['id' => $param['id'], 'status' => 18 ])->find();
        if ( !$exam_info ){
            return ret( 40001, null, '考试不存在或已过期');
        }

        //普通考试的时间限定
        if ( $exam_info['set_time'] ){
            if ( $now_time < $exam_info['start_time'] ){
                return ret(46111);
            }
            if ( $now_time > ( $exam_info['start_time'] + $exam_info['duration'] * 60 + 3600 ) ){
                return ret(46112);
            }
        }

        //session还在，适用于开始了考试，误操作退出了试卷，这时有了sign_id，不可参加其他考试，除非该考试交卷
        $exam_LOCK = session('exam_LOCK');
        if ( $exam_LOCK && $exam_LOCK['sign_id'] > 0 ){
            if ( $exam_LOCK['exam_id'] == $param['id'] ){
                return ret(46102, $exam_LOCK['exam_id'], '你有一个进行中的考试【'.$exam_info['title'].'】，点击确定继续考试。');
            }else{
                $has_exam_name = Db::name('exam') -> where([ 'id' => $exam_LOCK['exam_id'] ]) -> field('id, title') -> find();
                return ret(46102, $exam_LOCK['exam_id'], '你有一个进行中的考试【'.$has_exam_name['title'].'】，点击确定继续考试。');
            }
        }

        // 查找sign记录里etime=0的记录，如未截止则继续（检测以往所有etime=0的，无差别）
        $un_submit = Db::name('exam_sign') -> where(['member_id' => $member_id, 'etime' => 0])->order('id desc')->find();
        if ( $un_submit ){
            //查找考试，如考试状态有误，则直接空卷
            $un_submit_exam = $un_submit['exam_id'] == $param['id'] ? $exam_info : Db::name('exam')->where(['id' => $un_submit['exam_id'], 'status' => 18 ])->find();
            if ( !$un_submit_exam ){
                Db::name('exam_sign') -> where(['id' => $un_submit['id']])->update( ['etime' => 404] );   //取不到时长，写404做标志位
            }

            //未到结束时间，继续进入考试
            if ( ( $un_submit['ctime'] + $un_submit_exam['duration'] * 60 ) > $now_time ){
                $exam_LOCK = [
                    'lock_key' => $un_submit['lock_key'],
                    'sign_id' => $un_submit['id'],
                    'exam_id' => $un_submit['exam_id'],
                    'ctime' => $un_submit['ctime'],                 //创建时间
                    'duration' => $un_submit_exam['duration'],      //考试时长，预先保存
                ];
                session('exam_LOCK', $exam_LOCK);
                return ret(46102, $exam_LOCK['exam_id'], '你有一个进行中的考试【'.$un_submit_exam['title'].'】，点击确定继续考试e。');
            }else{
                Db::name('exam_sign') -> where( ['id' => $un_submit['id'] ])->update(['etime' => $un_submit['ctime'] + $un_submit_exam['duration'] * 60]);
                session('exam_LOCK', null);
            }
        }

        //---------------正常新开始考试业务-------------
        //检测是否通过考试前置条件
        if ( $exam_info['type'] == 1011 && $exam_info['teaching_id'] ){
            $teaching_sign = Db::name('teaching_sign')->where(['teaching_id'=>$exam_info['teaching_id'], 'member_id'=>$member_id])->find();
            if ( $teaching_sign['teaching_rate'] < 100 ){
                return ret(46107);
            }
        }

        //如果已经参加过考试
        $sign_list = Db::name('exam_sign') -> where(['member_id' => $member_id, 'exam_id' => $param['id']])->order('id desc')->select();
        //已通过考试
        if ( $sign_list[0]['passed'] == 1 ) {
            return ret(46101);
        }
        //已获得同证书
        if ( $exam_info['cert_id'] &&  Db::name('exam_sign')->where( [ 'member_id' => $member_id, 'cert_id' => $exam_info['cert_id'] ] )->count() ){
            return ret(46110, $exam_info['cert_id']);
        }
        if ( $sign_list ){
            //为处理掉线、死机情况，session丢失，etime=0，且未超时，继续考试，否则自动交白卷，确保有效的记录都是交卷的
            if ( $sign_list[0]['etime'] == 0 ) {
                if ( ( $sign_list[0]['ctime'] + $exam_info['duration'] * 60 ) > $now_time ){  //未到结束时间，继续进入考试
                    $exam_LOCK = [
                        'lock_key' => $sign_list[0]['lock_key'],
                        'sign_id' => $sign_list[0]['id'],
                        'exam_id' => $sign_list[0]['exam_id'],
                        'ctime' => $sign_list[0]['ctime'],             //创建时间
                        'duration' => $exam_info['duration'],       //考试时长，预先保存
                    ];
                    session('exam_LOCK', $exam_LOCK);
                    return ret(46102, $exam_LOCK['exam_id'], '你有一个进行中的考试【'.$exam_info['title'].'】，点击确定继续考试。');
                }else{
                    Db::name('exam_sign') -> where( ['id' => $sign_list[0]['id'] ])->update(['etime' => $sign_list[0]['ctime'] + $exam_info['duration'] * 60]);
                    session('exam_LOCK', null);
                    // TODO 如一直未回到考试，可能会发生 etime 始终为0的情况，需要后台在刷新列表的时候处理一下
                    return ret(46103, $sign_list[0]['id']);
                }
            }

            //不允许再次参加考试
            if ( !$exam_info['can_retry'] ){
                return ret(46104);
            }else{
                if ( count( $sign_list ) >= $exam_info['retry_count'] ){
                    return ret(46105, '', '该考试限定只能重复参加'.$exam_info['retry_count'].'次');
                }

                $time_space = ( $sign_list[0]['ctime'] + $exam_info['unpass_delay'] * 86400 ) - $now_time;
                if ( $time_space > 0 && $sign_list[0]['exam_id'] == $param['id'] ){
                    return ret(46106, '', '近一次考试未通过，请等待' . intval($time_space / 3600 / 24) . '天后重试' );
                }
            }

        }

        //一切正常，开始走开始考试的流程
        $lock_key = genRandomString(12);  //随机字符串做检验
        $exam_LOCK = [
            'lock_key' => $lock_key,
            'sign_id' => 0,
            'exam_id' => $param['id'],
            'ctime' => $now_time,                      //创建时间
            'duration' => $exam_info['duration'],   //考试时长，预先保存
        ];
        session('exam_LOCK', $exam_LOCK);

        return ret(0);
    }

    // 开始考试的信息获取与条件判定
    public function get_exam_sign( $param = null, $member_id ){

        if ( !$param['id'] ){
            return ret( 40001, null, '考试报名ID有误');
        }
        $now_time = time();

        $res['exam_info'] = Db::name('exam') ->where(['id' => $param['id'], 'status' => 18 ])->find();
        if ( !$res['exam_info'] ){
            return ret( 40001, null, '考试不存在，请联系管理员');
        }

        $exam_LOCK = session('exam_LOCK');
        if ( !$exam_LOCK ){
            return ret(46108);
        }
        if ( $exam_LOCK['exam_id'] != $param['id'] ){
            return ret(40001, '', '作弊行为2，已记录');
        }

        $end_time = $exam_LOCK['ctime'] + $exam_LOCK['duration'] * 60;

        // session 还在，要继续考试
        if ( $exam_LOCK['sign_id'] > 0 ){
            if ( $end_time < $now_time ){
                Db::name('exam_sign') -> where( ['id' => $exam_LOCK['sign_id'] ])->update( ['etime' => $end_time] );
                session('exam_LOCK', null);
                return ret( 46103 );
            }
            $res['sign_info'] = Db::name('exam_sign') ->where(['id' => $exam_LOCK['sign_id' ]])->find();
        }else{
            //刚进入考试，创建exam_sign
            $sign_id = Db::name('exam_sign')->insertGetId(['member_id' => $member_id, 'exam_id' => $param['id'], 'ctime' => $now_time, 'lock_key' => $exam_LOCK['lock_key']]);
            $exam_LOCK['ctime'] = $now_time;
            $exam_LOCK['sign_id'] = $sign_id;
            session('exam_LOCK', $exam_LOCK);
            $res['sign_info'] = Db::name('exam_sign') ->where(['id' => $sign_id ])->find();
        }

        $res['sign_info']['lock_key'] = 0;  //防止 lock_key 泄露
        $res['server_time'] = $now_time;

        return ret(0, $res);
    }



    public function get_paper( $param = null ){
        if ( !$param['id'] ){
            return ret( 40001, null, '试卷ID有误');
        }
        $info['paper'] = Db::name('paper') -> where( [ 'id' => $param['id'], 'status' => 14 ] ) -> find();
        if ( !$info['paper'] ){
            return ret(46109);
        }
        $info['paper_items'] = Db::name('paper_items') -> where( [ 'paper_id' => $param['id'] ] ) -> order('sorts asc, id desc') -> select();
        $question_ids = [];
        foreach ( $info['paper_items'] as $v ){
            if ( $v['type'] == 1 ){
                $question_ids[] = $v['question_id'];
            }
        }
        $info['question_list'] = Db::name('certain_question') -> where( [ 'id' => ['in', $question_ids] ] ) -> select();
        $info['question_option'] = Db::name('certain_question_option') -> where( [ 'question_id' => ['in', $question_ids] ] ) -> order('id asc') ->field('correct', true) -> select();
        return ret(0, $info);
    }

    public function paper_submit( $param = null, $member_id ){
        if ( !$param['id'] ){
            return ret( 40001, null, '试卷ID有误');
        }

        $question_ids = [];
        $paper_questions = Db::name('paper_items')->where( [ 'paper_id' => $param['id'], 'type' => 1 ] )->field('id,question_id,score')->select();
        foreach ( $paper_questions as $v ){
            $question_ids[] = $v['question_id'];
        }
        if ( count( $question_ids ) <= 0 ){
            return ret( 40001, null, '试卷没有题目，请联系管理员' );
        }

        //验证考试是否合理
        $exam = Db::name('exam')->where(['id' =>$param['exam_id'] ])->find();

        $now_time = time();
        if ( $param['exam_sign_id'] ){
            $exam_LOCK = session('exam_LOCK');
            if ( !$exam_LOCK ){
                return ret(46108);
            }
            if ( $exam_LOCK['sign_id'] != $param['exam_sign_id'] || $exam_LOCK['exam_id'] != $param['exam_id'] ){
                return ret(40001,'', '作弊行为3，已记录');
            }
            if ( !$exam || $exam['paper_id'] != $param['id'] ){
                return ret(40001,'', '作弊行为4，已记录');
            }
            $end_time = $exam_LOCK['ctime'] + ( $exam_LOCK['duration'] + 2 ) * 60; //额外给2分钟交卷时间，防止出现网络延时造成自动交卷失败。
            if ( $now_time > $end_time ){
                Db::name('exam_sign') -> where( ['id' => $exam_LOCK['sign_id'] ])->update( ['etime' => $end_time] );
                session('exam_LOCK', null);
                return ret( 46103 );
            }
        }else{
            if ( $exam['set_time'] ){
                if ( $now_time < $exam['start_time'] ){
                    $res['has_error'] = true;
                    $res['msg'] = '还没有开始考试';
                    return ret(46111);
                }
                if ( $now_time > ( $exam['start_time'] + $exam['duration'] * 60 + 3600 ) ){
                    return ret(46112);
                }
            }
        }

        $questions = Db::name('certain_question')->where( [ 'id' => ['in', $question_ids] ] )->field('id,type,relation_id')->select();
        $question_options = Db::name('certain_question_option') -> where( [ 'question_id' => ['in', $question_ids] ] ) -> order('id asc') -> select();

        //拼装数据
        foreach ($questions as $qk => $qv ) {
            $questions[$qk]['option'] = [];
            foreach ( $question_options as $ok => $ov ){
                if ( $qv['id'] == $ov['question_id'] ){
                    $questions[$qk]['option'][] = $ov;
                }
            }
            foreach ( $paper_questions as $pk => $pv ){
                if ( $qv['id'] == $pv['question_id'] ){
                    $questions[$qk]['score'] = $pv['score'];
                }
            }
        }

        //判题
        $total_score = 0;
        foreach ( $questions as $qk => $qst ){
            foreach ( $param['answer'] as $an ){
                if ( $qst['id'] == $an['question_id'] ){
                    switch ( intval($qst['type']) ) {
                        case 1:
                        case 3:
                            foreach ( $qst['option'] as $v ){
                                if ( $v['correct'] && $v['id'] == $an['answer'] ){
                                    $questions[$qk]['result'] = true;
                                    $total_score = $total_score + intval($qst['score']);
                                }
                            }
                            break;
                        case 2:
                            $should_right = 0;
                            $right_count = 0;

                            foreach ( $qst['option'] as $v ){
                                if ( $v['correct'] ){
                                    $should_right++;
                                    if ( in_array( $v['id'], $an['answer'] ) ){
                                        $right_count++;
                                    }
                                }
                            }
                            if ( $right_count == $should_right && count($an['answer']) == $should_right ){
                                $questions[$qk]['result'] = true;
                                $total_score = $total_score + intval($qst['score']);
                            }
                            break;
                        case 4:
                            $right_answer = [];
                            foreach ( $qst['option'] as $v ){
                                $right_answer[] = strtoupper($v['title']);
                            }
                            $user_answer = array_map( 'strtoupper', $an['answer'] );
                            sort( $right_answer );
                            sort( $user_answer );

                            if ( $right_answer == $user_answer ){
                                $questions[$qk]['result'] = true;
                                $total_score = $total_score + intval($qst['score']);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $res['total_score'] = $total_score;

        if ( $param['exam_sign_id'] ){

            //保存试卷提交数据
            $data['paper_id'] = $param['id'];
            $data['exam_id'] = $param['exam_id'];
            $data['exam_sign_id'] = $param['exam_sign_id'];
            $data['member_id'] = $member_id;
            $data['answer'] = json_encode( $param['answer'] );
            $data['ctime'] = time();
            $data['score'] = $res['total_score'];

            $paper_submit_id = Db::name('paper_submit')->insertGetId($data);

            //数据统计
            $is_passed = $total_score >= $exam['pass_score'];
            if ( $is_passed ){
                Db::name('exam')->where( [ 'id' => $exam['id'] ] )->setInc('count_passed');
            }
            Db::name('exam_sign')->where( ['id' => $param['exam_sign_id']] )->update([
                'etime' => $now_time,
                'paper_submit_id' => $paper_submit_id,
                'score' => $total_score,
                'passed' => $is_passed ? 1 : 0,
                'cert_id' => $is_passed ? $exam['cert_id'] : 0,  //发证书
                'teaching_id' => $exam['teaching_id'] ?: 0
            ]);

            if ( $exam['teaching_id'] > 0 ){
                Db::name('teaching')->where( [ 'id' => $exam['teaching_id'] ] )->setInc('cert_get_number');
            }
            Db::name('exam')->where( [ 'id' => $exam['id'] ] )->setInc('count_history');

            foreach ( $questions as $v ){
                if ( $v['result'] == true ){
                    Db::name('question')->where('id', $v['relation_id'])->setInc('total_right');
                }else{
                    Db::name('question')->where('id', $v['relation_id'])->setInc('total_error');
                }
            }

            session('exam_LOCK', null);
        }else{
            $res['questions'] = $questions; //答案传回前端
        }

        return ret(0, $res);
    }

}
