<?php

/*
 * loan接口
 */


namespace app\manager\api;
use think\Db;

class Template extends ApiBase{

    public function _initialize() {
        parent::_initialize();
    }

    public $view_dir ="../";

    public function is_safety_name($str) {
        return preg_match('/^[_0-9a-zA-Z]*$/',$str);
    }

    //校验文件后缀
    public function getExt1($filename){
        $arr = explode('.',$filename);
        return array_pop($arr);;
    }


    //校验目录名合法性，返回目录完整路径
    public function get_path( $dir,$type ){
        if ( !is_array( $dir ) ){
            return 40001;
        }
        if ( $dir[0] ){
            foreach ( $dir as $v ){
                if ( !$this->is_safety_name( $v ) || !$v ){
                    return 40201;
                }
            }
        }
        if($type === 'html'){
            $dir = $this->view_dir . "app/" . implode('/', $dir);   //拼接路径
        }
        if($type === 'css'){
            $dir = $this->view_dir . "public_html/z/" . implode('/', $dir);   //拼接路径
        }
        if($type === 'js'){
            $dir = $this->view_dir . "public_html/z/" . implode('/', $dir);   //拼接路径
        }
       
        if ( !is_dir( $dir ) ){
            var_dump($dir);
            return 40202;
        }
        return $dir;
    }

    
    // 获取模板列表
    public function get_template_list( $param = null ) {
        $dir = $this->get_path($param['dir'],$param['file_ext_type']);
        if ( is_numeric( $dir ) ){
            return ret($dir);
        }
        //读取目录
        $list = scandir( $dir );
        $res = [];
        foreach($list as $item){
            if($item != '.' && $item != '..'){
                if(is_file( $dir . '/' . $item)){
                    $is_correct_type = $this->getExt1($item);
                    if($is_correct_type === $param['file_ext_type']){
                        $res[] = [
                            'file_name' => $item,
                            'file_type' => is_file( $dir . '/' . $item),
                            'file_time' => filemtime( $dir . '/' . $item)
                        ];
                    }
                }else{
                    $res[] = [
                        'file_name' => $item,
                        'file_type' => is_file( $dir . '/' . $item),
                        'file_time' => filemtime( $dir . '/' . $item)
                    ];
                }
                
            }
        }
        return ret(0, $res);
    }

    //编辑时获取文件内容
    public function get_file_content( $param = null){
        $dir = $this->get_path($param['dir_name'] ,$param['file_ext_type']);
        if ( is_numeric( $dir ) ){
            return ret($dir);
        }

        if ( !$this->is_safety_name($param['file_name']) ){
            return ret(40201);
        }
        $file_url = $dir . '/' . $param['file_name'] . '.' . $param['file_ext_type'];


        $my_file = fopen($file_url, "r");
        if ( $my_file === FALSE ){
            return ret(40200);
        }
        $data = fread($my_file, filesize($file_url));
        fclose($my_file);
        return ret(0, $data);
    }

    //新增或者编辑文件
    public function write_file($param = null){
        $dir = $this->get_path($param['dir_name'] ,$param['file_ext_type']);
        if ( is_numeric( $dir ) ){
            return ret($dir);
        }

        if ( !$this->is_safety_name($param['file_name']) ){
            return ret(40201);
        }
        $file_url = $dir . '/' . $param['file_name'] . '.' . $param['file_ext_type'];

        if($param['type'] == 'create' && file_exists($file_url)){
            return ret(40204);
        }
        if($param['type'] == 'edit'){
            $copy_file_name = $dir . '/' . 'copy_' . $param['file_name'] . '_' . date('Ymd', time()) . '.' . $param['file_ext_type'] . '.bak';
            copy($file_url,$copy_file_name );
        }

        $fp = fopen($file_url, "w");  //w是写入模式，文件不存在则创建文件写入。
        if ( $fp === FALSE ){
            return ret(40200);
        }
        $len = fwrite($fp, $param['file_content']);
        fclose($fp);
        return ret(0, $len);
    }
    //删除文件
    public function delete_file($param = null){
        $dir = $this->get_path($param['dir_name'] ,$param['file_ext_type']);
        if ( is_numeric( $dir ) ){
            return ret($dir);
        }

        if ( !$this->is_safety_name($param['file_name']) ){
            return ret(40201);
        }
        $file_url = $dir . '/' . $param['file_name'] . '.' . $param['file_ext_type'];
        $copy_file_name = $dir . '/' . 'copy_' . $param['file_name'] . '_' . date('Ymd', time()) . '.' . $param['file_ext_type'] . '.bak';
     
        copy($file_url,$copy_file_name );

        $res = unlink($file_url);
        return ret(0, $res);
    }

    //新增文件夹
    public function create_folder($param = null) {
        $dir = $this->get_path($param['dir_name'],$param['file_ext_type']);
        if ( is_numeric( $dir ) ){
            return ret($dir);
        }

        if ( !$this->is_safety_name($param['new_folder_name']) ){
            return ret(40201);
        }

        $new_folder = $dir . '/' . $param['new_folder_name'];
        if(is_dir($new_folder)) return ret(40203);

        mkdir($new_folder, 0777);
        return ret(0,true);
    }

    // //删除文件夹
    // public function delete_folder($param = null){
    //     $dir = $this->get_path($param['dir_name'],$param['file_ext_type']);
    //     if ( is_numeric( $dir ) ){
    //         return ret($dir);
    //     }

    //     if ( !$this->is_safety_name($param['folder_name']) ){
    //         return ret(40201);
    //     }
    //     $folder_path = $dir . '/' . $param['folder_name'] . '/';
    //     $copy_dir_name = $dir . '/' . 'copy_' . $param['folder_name'] . '_' . date('Ymd', time());
    //     $this->copydir($folder_path,$copy_dir_name);
       
    //     $count = $this->do_delete_folder( $folder_path );
    //     return ret(0,$count);
    // }
    
    // //复制文件夹
    // public function copydir($source, $dest){
    //     if (!file_exists($dest)) mkdir($dest);
    //     $handle = opendir($source);
    //     while (($item = readdir($handle)) !== false) {
    //         if ($item == '.' || $item == '..') continue;
    //         $_source = $source . '/' . $item;
    //         $_dest = $dest . '/' . $item;
    //         if (is_file($_source)) copy($_source, $_dest);
    //         if (is_dir($_source)) $this->copydir($_source, $_dest);
    //     }
    //     closedir($handle);
    //     return true;
    // }

    // private function do_delete_folder( $path ){
    //     if ( !$path ){
    //         return false;
    //     }
    //     $count = 0;

    //     $dir = scandir($path);
    //     foreach($dir as $val){
    //         if($val != "." && $val != ".."){
    //             if(is_dir( $path . $val )){
    //                 $count += $this->do_delete_folder( $path . $val . '/' );
    //                 rmdir($path . $val);
    //             }else{
    //                 unlink($path . $val);
    //                 $count++;
    //             }
    //         }
    //     }
    //     rmdir($path);
    //     $count++;
    //     return $count;
    // }

  
}
