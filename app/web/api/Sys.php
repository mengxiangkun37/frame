<?php

/*
 * 用户接口
 */


namespace app\web\api;
use think\Db;

require_once ADDON_PATH . '/Dysms/SignatureHelper.php';
use Aliyun\DySDKLite\SignatureHelper;


class Sys extends WebBase{

    public function _initialize() {
        parent::_initialize();
    }

    public function get_site_info( $param = null ) {

        $banners = [];
        $arc = getArcs(39, 6, null, false, 'litpic');
        $info = [
            'site_name' => SC('site_name'),
            'logo_banner' => SC('logo_banner'),
            'site_banner' => $arc,
            'site_phone' => SC('site_phone'),
            'site_address' => SC('site_address'),
            'site_company' => SC('site_company'),
            'site_zip' => SC('site_zip'),
            'public_qr' => SC('public_qr')
        ];
        return ret(0,$info);
    }
//
//    public function check_phone_code( $param = null ){
//        $se = session('phone_code');
//        if ( !$se || $se['status'] == 0 || ( time() - $se['time'] ) > 600 || $param['phone'] != $se['phone'] ){
//            session('phone_code', null);
//            return ret(46003);
//        }
//        if ( $param['phone_code'] != $se['code'] ){
//            return ret(46004);
//        }
//        session('phone_code', null);
//        return ret(0);
//    }

    //发送短信接口，暂时不存数据库记录，依据大鱼发送记录
//     public function send_phone_code( $param = null ){
//         if( !preg_match("/^1[3456789]\d{9}$/", $param['phone']) ){
//             return ret(46001);
//         }

//         //手机号不可重复
//         if( Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> count() > 0 ){
//             return ret(40041);
//         }

//         $se = session('phone_code');

//         $tpl = Db::name('code_template') -> where(array('type' => $param['type'])) -> find();
//         if ( !$tpl ){
//             return ret( 46006 );
//         }
//         $param['SignName'] = $tpl['SignName'];
//         $param['product'] = $tpl['product'];
//         $param['TemplateCode'] = $tpl['TemplateCode'];

//         if ( $se ){
//             if ( ( time() - $se['time'] ) < 60 ){
//                 return ret( 46005 );
//             }else{
//                 $code = $se['code'];
//                 $res = $this->do_send_dysms( $param, $code );
//             }
//         }else{
//             $code = get_rand_code( 6, 'num' ); //生成code，存入session
//             session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 0 ]);
//             //执行发送短信
//             $res = $this->do_send_dysms( $param, $code );
//         }

// //        {"Recommend":"https:\/\/error-center.aliyun.com\/status\/search?Keyword=MissingSignName&source=PopGw","Message":"SignName is mandatory for this action.","RequestId":"02F5066C-4786-4053-B07F-CEB1651CF777","HostId":"dysmsapi.aliyuncs.com","Code":"MissingSignName"}
// //        {"Message":"OK","RequestId":"5EEC3B4C-9E1B-4E73-A77A-F143B2AD31C9","BizId":"853819343905671399^0","Code":"OK"}
// //        inlog( json_encode($res) );
//         if ( $res->Code == 'OK' ){
//             Db::name('code')->insert([
//                 'phone' => $param['phone'],
//                 'code' => $code,
//                 'type' => 'reg',f
//                 'ctime' => time(),
//                 'phone' => $param['phone'],
//                 'phone' => $param['phone'],

//             ]);
//             session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 1 ]);
//             return ret();
//         }else{
//             return ret(46002, '', $res->Message);
//         }
//     }

     //发送短信接口，暂时不存数据库记录，依据大鱼发送记录
     public function send_phone_code( $param = null ){
        if( !preg_match("/^1[3456789]\d{9}$/", $param['phone']) ){
            return ret(46001);
        }

        //手机号不可重复
        if( Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> count() > 0 ){
            return ret(40041);
        }

        $se = session('phone_code');

        if ( $se ){
            if ( ( time() - $se['time'] ) < 60 ){
                return ret( 46005 );
            }else{
                $code = $se['code'];
                $template= Array (
                    "product" => '代账行业协税者',
                    "code" => $code
                ); 
                $res = $this->do_send_dysms($param['phone'], 'SMS_4996379', $template );
            }
        }else{
            $code = get_rand_code( 6, 'num' ); //生成code，存入session
            session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 0 ]);
            //执行发送短信
            $template= Array (
                "product" => '代账行业协税者',
                "code" => $code
            ); 
            $res = $this->do_send_dysms($param['phone'], 'SMS_4996379', $template );
        }

//        {"Recommend":"https:\/\/error-center.aliyun.com\/status\/search?Keyword=MissingSignName&source=PopGw","Message":"SignName is mandatory for this action.","RequestId":"02F5066C-4786-4053-B07F-CEB1651CF777","HostId":"dysmsapi.aliyuncs.com","Code":"MissingSignName"}
//        {"Message":"OK","RequestId":"5EEC3B4C-9E1B-4E73-A77A-F143B2AD31C9","BizId":"853819343905671399^0","Code":"OK"}
//        inlog( json_encode($res) );
        if ( $res->Code == 'OK' ){
            Db::name('code')->insert([
                'phone' => $param['phone'],
                'code' => $code,
                'type' => 'reg',
                'ctime' => time(),
             
            ]);
            session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 1 ]);
            return ret();
        }else{
            return ret(46002, '', $res->Message);
        }
    }

    public function login_phone_code( $param = null ){
        if( !preg_match("/^1[3456789]\d{9}$/", $param['phone']) ){
            return ret(46001);
        }

        //手机号未注册
        if( Db::name('member') -> where( [ 'phone' => $param['phone'] ] ) -> count() == 0 ){
            return ret(47138);
        }

        $se = session('phone_code');

        if ( $se ){
            if ( ( time() - $se['time'] ) < 60 ){
                return ret( 46005 );
            }else{
                $code = $se['code'];
                $template= Array (
                    "product" => '代账行业协税者',
                    "code" => $code
                ); 
                $res = $this->do_send_dysms($param['phone'], 'SMS_4996379', $template );
            }
        }else{
            $code = get_rand_code( 6, 'num' ); //生成code，存入session
            session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 0 ]);
            //执行发送短信
            $template= Array (
                "product" => '代账行业协税者',
                "code" => $code
            ); 
            $res = $this->do_send_dysms($param['phone'], 'SMS_4996379', $template );
        }

//        {"Recommend":"https:\/\/error-center.aliyun.com\/status\/search?Keyword=MissingSignName&source=PopGw","Message":"SignName is mandatory for this action.","RequestId":"02F5066C-4786-4053-B07F-CEB1651CF777","HostId":"dysmsapi.aliyuncs.com","Code":"MissingSignName"}
//        {"Message":"OK","RequestId":"5EEC3B4C-9E1B-4E73-A77A-F143B2AD31C9","BizId":"853819343905671399^0","Code":"OK"}
//        inlog( json_encode($res) );
        if ( $res->Code == 'OK' ){
            Db::name('code')->insert([
                'phone' => $param['phone'],
                'code' => $code,
                'type' => 'login',
                'ctime' => time(),
             
            ]);
            session('phone_code', [ 'code' => $code, 'time' => time(), 'phone' => $param['phone'], 'status' => 1 ]);
            return ret();
        }else{
            return ret(46002, '', $res->Message);
        }
    }



    // public function do_send_dysms( $param = null, $code) {

    //     $params = array ();

    //     // *** 需用户填写部分 ***
    //     // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    //     $accessKeyId = SC('dy_sms_accessKeyId');
    //     $accessKeySecret = SC('dy_sms_accessKeySecret');

    //     // fixme 必填: 短信接收号码
    //     $params["PhoneNumbers"] = $param['phone'];

    //     // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
    //     $params["SignName"] = $param['SignName'];

    //     // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    //     $params["TemplateCode"] = $param['TemplateCode'];

    //     // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
    //     $params['TemplateParam'] = Array (
    //         "code" => $code,
    //         "product" => $param['product']
    //     );

    //     // fixme 可选: 设置发送短信流水号
    //     $params['OutId'] = "";

    //     // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
    //     $params['SmsUpExtendCode'] = "";


    //     // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
    //     if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
    //         $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
    //     }

    //     // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
    //     $helper = new SignatureHelper();

    //     // 此处可能会抛出异常，注意catch
    //     $content = $helper->request(
    //         $accessKeyId,
    //         $accessKeySecret,
    //         "dysmsapi.aliyuncs.com",
    //         array_merge($params, array(
    //             "RegionId" => "cn-hangzhou",
    //             "Action" => "SendSms",
    //             "Version" => "2017-05-25",
    //         ))
    //     // fixme 选填: 启用https
    //     // ,true
    //     );
    //     return $content;
    // }

}
