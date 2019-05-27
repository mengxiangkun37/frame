<?php
// +----------------------------------------------------------------------
// | PYSOFT [ 品用软件 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://pyo.gs All rights reserved.
// +----------------------------------------------------------------------
// | Author: guhp
// +----------------------------------------------------------------------

namespace app\web\controller;
use think\Db;

class Upload extends WebBase{

    public $upload_type;
    public $upload_rootpath;
    public $uploadmaxsize;
    public $upload_ext_allow;

    public function _initialize() {
        parent::_initialize();
        require_once ADDON_PATH . '/Qiniu/autoload.php';    //引入autoload的方法
        $this->upload_type = SC('upload_type') ?: 'local';
        $this->upload_rootpath = SC('uploadrootpath') ?: 'u/';
        $this->uploadmaxsize = ( SC('uploadmaxsize') ?: 2048 ) * 1024;
        $this->upload_ext_allow = SC('upload_ext_allow');
    }

    //对应内部调用的
    function build_qiniu_token() {
        $accessKey = SC('qiniu_accessKey');
        $secretKey = SC('qiniu_secrectKey');
        $bucket = SC('qiniu_bucket');
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        return $auth -> uploadToken($bucket);
    }

    //校验文件大小与格式，返回 error_code 格式
    function check_file( $size = 0,  $ext = '' ){
        $allow = explode(',', $this->upload_ext_allow);
        if ( !$ext || !$allow ){
            return ret(40002);
        }
        $ext = strtolower($ext);
        if ( !in_array($ext, $allow) ){
            return ret(41040);
        }
        if ( $this->uploadmaxsize < $size ){
            return ret(41041, '', '文件过大，最大：'.$this->uploadmaxsize.'字节');
        }
        return ret(0, '文件校验成功');
    }

    //新的本地上传方法 20181129  经过测试
    public function up_local(){
        config([
            'default_return_type'=>'json',
            'default_ajax_return'=>'json'
        ]);
        $input = input();
        $fields_name = $input['fields_name'] ?: 'file';

        $file = request()->file( $fields_name );
        $uploaded_url = '';
        if ( ! $file ){
            return ret(42001);
        }
        $file_path = '/' . $this->upload_rootpath . date('Ymd');
        $info = $file->validate(['size'=> $this->uploadmaxsize ,'ext'=> $this->upload_ext_allow ])->rule('uniqid')->move(SITE_PATH . $file_path );
        if( $info ) {
            $uploaded_url = $file_path . '/' . $info->getSaveName();
        }else{
            return ret(42002, '', $file->getError());
        }

        //保存上传记录
        $file_info = $info->getInfo();
        $upload_log = [
            'type' => 1,    //后台
            'url' => $uploaded_url,
            'size' => $file_info['size'],
            'ext' => $info->getExtension(),
            'time' => time(),
            'user_id' => $this->manager_id,
            'upload_ip' => NOWIP,
            'status' => 1,
            'sha1' => $info->hash('sha1'),
            'group_id'=> input('group_id')?:4,
            'filename'=>$file_info['name']
        ];
        Db::name('files')->insert( $upload_log );

        return ret(0,$uploaded_url);
    }

    //七牛文件上传  20181129 去除了原有带界面的上传，采用ret返回     目前分离了base64上传至单独的方法 20170313
    public function upload_qiniu(){
        $file = $_FILES["file"];
        if ( !$file ){
            return ret(41038);
        }
        $file_ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        $checked = $this->check_file( $file["size"] , $file_ext);
        if ( $checked['code'] ){
            return $checked;
        }

        $token = $this->build_qiniu_token();
        $uploadMgr = new \Qiniu\Storage\UploadManager();

        $file_path = $file["tmp_name"];

        $key = date('Ymd') . '/' . uniqid() . '.' . $file_ext;
        $key = ltrim( $key, '/' );	//去掉前面的斜杠
        list($ret, $err) = $uploadMgr->putFile($token, $key, $file_path);
        if ($err !== null) {
            return ret( 41039, '', var_export($err,true) );
        }

        $url = SC('qiniu_domain').$ret['key'];
        //保存上传记录
        $upload_log = [
            'type' => 1,    //后台
            'url' => $url,
            'size' => $file["size"],
            'ext' => $file_ext,
            'time' => time(),
            'user_id' => $this->uid,
            'upload_ip' => NOWIP,
            'status' => 1,
            'sha1' => '',
            'filename' => 'base64',
            'group_id'=>4
        ];
        db('files')->insert( $upload_log );

        return ret( 0, $url );
    }

    //为字符串上传单写的方法
    public function upload_base64( $content ){
        $file_ext = '_64.png';
        $file_name =  uniqid() . $file_ext;
        $file_content = ACTION_NAME == 'ueditor' ? base64_decode( input('file') ) : base64_decode( $content );

        $checked = $this->check_file( strlen($file_content) , 'png');
        if ( $checked['code'] ){
            return $checked;
        }

        if ( $this->upload_type == 'qiniu' ){
            $token = $this->build_qiniu_token();
            //引UploadManager类，为file与string类型
            $uploadMgr = new \Qiniu\Storage\UploadManager();
            list($ret, $err) = $uploadMgr->put($token, date('Ymd') . '/' . $file_name , $file_content  );
            $url = SC('qiniu_domain') . $ret['key'];
        }else{
            $file_path = $this->upload_rootpath . date('Ymd');
            if (!is_dir($file_path)) {
                @mkdir($file_path, 511);
            }
            $ret = file_put_contents( SITE_PATH . '/' . $file_path . '/' . $file_name , $file_content );
            $url = '/' . $file_path . '/' . $file_name;
        }

        //保存上传记录
        $upload_log = [
            'type' => 1,    //后台
            'url' => $url,
            'size' => strlen($file_content),
            'ext' => 'png',
            'time' => time(),
            'user_id' => $this->uid,
            'upload_ip' => NOWIP,
            'status' => 1,
            'sha1' => '',
            'filename' => 'base64',
            'group_id'=>4
        ];
        db('files')->insert( $upload_log );

        return ret(0, $url);
    }


    //提供编辑器的初始化配置
    public function ueditor(){
        $action = htmlspecialchars($_GET['action']);
        $CONFIG = array (
            'imageActionName' => 'uploadimage',
            'imageFieldName' => 'file',
            'imageMaxSize' => $this->uploadmaxsize,
            'imageAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
            'imageCompressEnable' => true,
            'imageCompressBorder' => 1600,
            'imageInsertAlign' => 'none',
            'imageUrlPrefix' => '',
            'imagePathFormat' => '/ueditor/image/{yyyy}{mm}{dd}/',
            'scrawlActionName' => 'uploadscrawl',
            'scrawlFieldName' => 'file',
            'scrawlPathFormat' => '/ueditor/image/{yyyy}{mm}{dd}/',
            'scrawlMaxSize' => $this->uploadmaxsize,
            'scrawlUrlPrefix' => '',
            'scrawlInsertAlign' => 'none',
            'fileActionName' => 'uploadfile',
            'fileFieldName' => 'file',
            'filePathFormat' => '/ueditor/file/{yyyy}{mm}{dd}/',
            'fileUrlPrefix' => '',
            'fileMaxSize' => $this->uploadmaxsize,
            'fileAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar',
                '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'],
        );
        $up_res = false;
        switch($action){
            case 'config':
                $result = json_encode($CONFIG);
                break;
            case 'uploadimage':
            case 'uploadfile':
                if ( $this->upload_type == 'qiniu' ){
                    $up_res = $this->upload_qiniu();
                }else {
                    $up_res = $this->up_local();
                }
                break;
            case 'uploadscrawl':
                $up_res = $this->upload_base64();
                break;
            default:
                $result = json_encode(array('state'=> 'wrong require'));
                break;
        }

        if ( $up_res ){
            if ($up_res['code']) {
                $result = json_encode(['state' => $up_res['msg'].$up_res['code']]);
            } else {
                $result = json_encode(['state' => 'SUCCESS', 'url' => $up_res['data']]);
            }
        }

//        if (isset($_GET["callback"])) {
//            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
//                $result = htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
//            } else {
//                $result = json_encode(['state'=> 'callback参数不合法']);
//            }
//        }
        echo $result;
    }


}
// 20181129 原引用 https://github.com/Nintendov/Ueditor-thinkphp 的 Ueditor 类，已经整合。