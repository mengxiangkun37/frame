<?php
/**
 *	微信公众平台PHP-SDK, ThinkPHP实例
 *  @author dodgepudding@gmail.com
 *  @link https://github.com/dodgepudding/wechat-php-sdk
 *  @version 1.2
 *  usage:
 *   $options = array(
 *			'token'=>'tokenaccesskey', //填写你设定的key
 *			'encodingaeskey'=>'encodingaeskey', //填写加密用的EncodingAESKey
 *			'appid'=>'wxdk1234567890', //填写高级调用功能的app id
 *			'appsecret'=>'xxxxxxxxxxxxxxxxxxx' //填写高级调用功能的密钥
 *		);
 *	 $weObj = new TPWechat($options);
 *   $weObj->valid();
 *   ...
 *
 */
namespace app\addons\Wechat;
use app\addons\Wechat\Wechat;

class TPWechat extends Wechat {
	
	/**
	 * 自定义方法，方便wm迁移来的数组拼接形式，并且捏合了2个方法成为1个
	 * @see Wechat::log()
	 */
	public function auto_reply( $data = array() ){
		if ( !$data ){
			return false;
		}
		switch( $data[1] ){
			case 'image':
				$this->image( $data[0] )->reply();
				break;
			case 'voice':
				$this->voice( $data[0] )->reply();
				break;
			case 'video':
				$this->video( $data[0] )->reply();
				break;
			case 'music':
				$this->music( $data[0] )->reply();
				break;
			case 'news':
				/**array(array('title','info','pic','url'),'image')
				 * 格式化数组结构，由wm的序列数组转换为键值数组，由SDK发送。	
				 * 数组结构:
				 *  array(
				 *  	"0"=>array(
				 *  		'Title'=>'msg title',
				 *  		'Description'=>'summary text',
				 *  		'PicUrl'=>'http://www.domain.com/1.jpg',
				 *  		'Url'=>'http://www.domain.com/1.html'
				 *  	),
				 *  	"1"=>....
				 *  )
				 */
				$news_format = array();
				foreach( $data[0] as $k_news => $v_news ){
					$news_format[] = array(
						'Title'=> $v_news[0],
						'Description'=> $v_news[1],
						'PicUrl'=> $v_news[2],
						'Url'=> $v_news[3]
					);
				}
				$this->news( $news_format )->reply();
				break;
			case 'text':
			default:
				$this->text( $data[0] )->reply();
				break;
		}
		exit;
	}
	
	/**
	 * log overwrite
	 * @see Wechat::log()
	 */
	protected function log($log){
		if ($this->debug) {
			if (function_exists($this->logcallback)) {
				if (is_array($log)) $log = print_r($log,true);
				return call_user_func($this->logcallback,$log);
			}elseif (class_exists('Log')) {
				Log::write('wechat：'.$log, Log::DEBUG);
				return true;
			}
		}
		return false;
	}

	/**
	 * 重载设置缓存
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expired){
		return cache($cachename,$value,$expired);
	}

	/**
	 * 重载获取缓存
	 * @param string $cachename
	 * @return mixed
	 */
	protected function getCache($cachename){
		return cache($cachename);
	}

	/**
	 * 重载清除缓存
	 * @param string $cachename
	 * @return boolean
	 */
	protected function removeCache($cachename){
		return cache($cachename,null);
	}

}



