<?php

/*
 * 用户接口
 */


namespace app\manager\api;
use think\Db;

class Article extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    public function classify_list( $param = null ) {
        $data_list = Db::query('SELECT py_classify.*,(SELECT count(*) FROM py_article WHERE py_article.classid = py_classify.id) as arc_count FROM py_classify ORDER BY sorts asc, id asc');
        return ret(0,$data_list);
    }

    public function classify_create($param = null){
        $field = 'pid,title,type,seotitle,keywords,description,content,sorts,fmpic,listtpl,arctpl,url,writer,status,ctime,utime';
        $rule = [
            ['title', 'require|max:200', '41036'],
            ['pid', 'require', '41037']
        ];
        $param['ctime'] = $param['utime'] = time();
        return $this->com_create( 'classify', $param, $rule, $field );
    }

    public function classify_update($param = null){
        $field = 'pid,title,type,seotitle,keywords,description,content,sorts,fmpic,listtpl,arctpl,url,writer,status,utime';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|max:200', '41036'],
            ['pid', 'require', '41037']
        ];
        $param['utime'] = time();
        return $this->com_update( 'classify', $param, $rule, $field );
    }

    public function classify_delete($param = null) {
        $count = Db::name('classify')->where(['pid' => $param['id'] ])->count();
        if ( $count > 0 ){
            return ret(41035);
        }
        return $this->com_delete( 'classify', [ 'id' => $param['id'] ] );
    }

    public function classify_resorts( $param = null ) {
        if ( !$param ){
            return ret(40002);
        }
        $count = 0;
        foreach($param as $v){
            $res = Db::name('classify') -> where(array('id' => $v['id'])) -> update(array('sorts' => $v['sorts']));
            if( $res != FALSE ){
                $count++;
            }
        }
        return ret(0, $count);
    }

    public function article_list($param = null){
        $list = $this->com_page_list( 'article',  $param );
        return ret(0,$list);
    }

    public function article_create($param = null, $user){
        $field = 'uid,title,source,seotitle,keywords,description,content,classid,litpic,url,ctime,utime,click,sorts,type,writer,arctpl,hot,top,pic,bold,status';
        $rule = [
            ['title', 'require|max:200', '41036'],
            ['classid', 'require', '41037']
        ];
        $param['ctime'] = $param['utime'] = time();
        $param['uid'] = $user['id'];
        if ( $param['auto_download'] ){
            $param['content'] = auto_download( $param['content'] );
        }
        return $this->com_create( 'article', $param, $rule, $field );
    }

    public function article_update($param = null, $user){
        $field = 'uid,title,source,seotitle,keywords,description,content,classid,litpic,url,utime,click,sorts,type,writer,arctpl,hot,top,pic,bold,status';
        $rule = [
            ['id', 'require', '40002'],
            ['title', 'require|max:200', '41036'],
            ['classid', 'require', '41037']
        ];
        $param['utime'] = time();
        $param['uid'] = $user['id'];
        if ( $param['auto_download'] ){
            $param['content'] = auto_download( $param['content'] );
        }
        return $this->com_update( 'article', $param, $rule, $field );
    }

    public function article_delete($param = null) {
        $map['id'] = ['in', $param['id'] ];
        return $this->com_delete( 'article', $map );
    }

    public function article_move($param = null) {
        return $this->com_batch_set('article', $param['ids'], 'classid', $param['classid'] );
    }


 
}
