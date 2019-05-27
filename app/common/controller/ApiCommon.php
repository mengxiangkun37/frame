<?php
namespace app\common\controller;
use think\Controller;
use think\Request;
use think\Db;

require_once ADDON_PATH . '/Dysms/SignatureHelper.php';
use Aliyun\DySDKLite\SignatureHelper;

class ApiCommon extends Controller {
	
	public function _initialize() {
		//php降低报错方法
		error_reporting(E_ERROR | E_PARSE);
		//定义常量，该变量返回当前模块、控制器、操作名称
		$request=Request::instance();

        defined('MODULE_NAME') or define('MODULE_NAME', $request->module() );
        defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $request->controller() );
        defined('ACTION_NAME') or define('ACTION_NAME', $request->action() );
        defined('NOWURL') or define('NOWURL', MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME );
        defined('NOWIP') or define('NOWIP', $request->ip() );
	}

    public function _empty(){
        $this -> error();
    }

    public function com_page_list( $table, $param, $field = '', $ex_field = false ){
        if ( !$table ){
            return [];
        }
        $page = $param['page'] ?: 1;
        $page_size = $param['page_size'] ?: 20;
        $order = 'id desc';

        $where = [];
        $where_string = '';
        if ( $param['search'] ){
            foreach ($param['search'] as $search ){
                switch ( $search['type'] ){
                    case 'multiple':
                        $wherein[$search['field']]=array('in', $search['value']);
                        break;
                    case 'range':
                        if ( $search['value'][0] !== false && $search['value'][1] === false ){
                            $where[$search['field']] = ['EGT', $search['value'][0]];
                        }
                        if ( $search['value'][0] === false && $search['value'][1] !== false ){
                            $where[$search['field']] = ['ELT', $search['value'][1]];
                        }
                        if ( $search['value'][0] !== false && $search['value'][1] !== false ){
                            $where[$search['field']] = [ ['EGT', $search['value'][0]], ['ELT', $search['value'][1]]];
                        }
                        break;
                    case 'select':
                        $where[ $search['field'] ] = $search['value'];
                        break;
                    case 'findinset':
                        $where_string = 'find_in_set("'.$search['value'].'",'.$search['field'].')';     //目前只支持一个findinset查询（多选拼接的逗号）
                        break;
                    case 'order':
                        $order = $search['value'];
                        break;
                    case 'text':
                        $where[ $search['field'] ] = ['like','%'.$search['value'].'%'];
                        break;
                    default:
                        break;
                }
            }
        }
        $list['data_list'] = Db::name($table)
            -> where( $wherein )
            -> where( $where )
            -> where( $where_string )
            -> order($order)
            -> field( $field, $ex_field )
            -> limit( ( $page - 1 ) * $page_size, $page_size)
            -> select();
        $list['total_count'] = Db::name($table) -> where( $where ) -> where( $where_string ) -> where( $wherein ) -> count();
        
        return $list;

    }

    public function com_create($table = false, $data = [], $rule = [], $field = null){
        if ( $rule ){
            $vaild = $this->validate($data,$rule);
            if($vaild !== true){
                return ret($vaild);
            }
        }
        $res = Db::name($table)->field($field)->insertGetId($data);
        if( $res === FALSE ) return ret(40001);
        return ret(0, $res);
    }

    public function com_update($table = false, $data = [], $rule = [], $field){
        $vaild = $this->validate($data,$rule);
        if($vaild !== true){
            return ret($vaild);
        }

        $res = Db::name($table)->field($field)->update($data);
        if( $res === FALSE ) return ret(40001);
        return ret(0,$res);
    }

    public function com_delete( $table = false, $where = false){
        if ( !$table || !$where ){
            return ret(40002);
        }
        $res = Db::name($table)->where($where)->delete();
        if( $res === FALSE ) return ret(40001);
        return ret(0, $res);
    }

    public function com_batch_set( $table = false, $ids = [], $field = '', $value = '', $time_field = '' ) {
        if ( !$table || !$ids || !$field || !$value ){
            return ret(45507);
        }
        $data[] = [$field => $value];
        if ( $time_field ){
            $data[$time_field] = time();
        }

        $count = 0;
        foreach( $ids as $v){
            $res = Db::name($table) ->where('id', $v) ->update( [ $field => $value] );
            if( $res != FALSE ){
                $count++;
            }
        }
        return ret(0, $count);
    }

    //批量获取tags，group为数组，如：['subject', 'difficulty']，返回对象对应tags
    public function com_get_tags( $group ){
        $res = [];
        if ( is_array( $group ) ){
            foreach ( $group as $g ){
                $res[$g] = Db::name('tags')->where( ['group' => $g, 'status' => 1 ] ) ->order('sort asc,id asc') -> select();
            }
        }else{
            $res = Db::name('tags')->where( ['group' => $group, 'status' => 1 ] ) ->order('sort asc,id asc') -> select();
        }
        return $res;
    }

     //短信
     public function do_send_dysms( $phone, $template_code, $template) {

        $params = array ();

        // *** 需用户填写部分 ***
        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = SC('dy_sms_accessKeyId');
        $accessKeySecret = SC('dy_sms_accessKeySecret');

        $tpl = Db::name('code_template') -> where(array('TemplateCode' => $template_code)) -> find();
        if ( !$tpl ){
            return ret( 46006 );
        }
        //    fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $tpl['SignName'];

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] =  $tpl['TemplateCode'];

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = $template;

        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        return $content;
    }



	
}
