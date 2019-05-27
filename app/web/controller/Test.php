<?php
namespace app\web\controller;
use think\Controller;
use think\Db;

class Test extends Controller {

	public function _initialize() {
		parent::_initialize();
	}

    public function test(){
        var_dump(session(''));
    }
    public function clear(){
        var_dump(session(null));
    }
    public function show(){
        var_dump(getArcs(39));
    }

	public function qcopy_success(){
		$q1 = Db::name('question_copy') -> select();
		foreach( $q1 as $k => $v){
			$exist = 0;
			$data = [
				'id' => $v['id'],
				'utime' => $v['utime'],
				'subject_id' => 4,
				'type' => $v['type'],
				'difficulty_id' => $v['level'] + 5,
				'content' => $v['content'],
				'usefor' => '1021,1022,1023',
				'total_error' => 0,
				'total_right' => 0
			];

			$exist = Db::name('question_back')->where('id='.$v['id'])->count();
			if ( $exist ){
				echo '<br>update:'.$v['id'] ;
				Db::name('question_back')->update($data);
			}else{
				echo '<br>insert:'.$v['id'] ;
				Db::name('question_back')->insert($data);
			}
		}
		
	}

}
