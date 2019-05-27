<?php
use think\Db;
function get_pinyin($str){
	require_once VENDOR_PATH.'/overtrue/pinyin/src/Pinyin.php';
	$pinyin = new Overtrue\Pinyin\Pinyin();
	$str_py = $pinyin -> abbr($str);
	return $str_py;
}

function ToUrlParams( $arr ){
    $buff = "";
    foreach ($arr as $k => $v){
        if($k != "sign" && $v != "" && !is_array($v)){
            $buff .= $k . "=" . $v . "&";
        }
    }

    $buff = trim($buff, "&");
	return $buff;
}

function get_rand_code($length,$type='num'){
	switch($type){
		case 'num':
			$chars = '0123456789';
			break;
		case 'str':
		default:
			$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
	}
	$code = '';
	for ($i = 0; $i < $length; $i++) {
		$code .= $chars[mt_rand(0, strlen($chars) - 1)];
	}
	return $code;
}

function get_address_by_latlng($longitude,$latitude){
	if(!$longitude){
		$longitude = SC('longitude_default');
	}
	if(!$latitude){
		$latitude = SC('latitude_default');
	}
	$res = CURL_GET('http://apis.map.qq.com/ws/geocoder/v1/?location=' . $latitude . ',' . $longitude . '&key=' . SC('tx_map_key'));
	$result = json_decode($res, TRUE);
	if ($result['status'] != 0) {
		return FALSE;
	}
	$list = array(
		'nation' => $result['result']['address_component']['nation'], 
		'province' => $result['result']['address_component']['province'], 
		'city' => $result['result']['address_component']['city'], 
		'district' => $result['result']['address_component']['district'], 
		'street' => $result['result']['address_component']['street'], 
		'address' => $result['result']['address'], 
		'formatted_addresses' => $result['result']['formatted_addresses']['recommend'], 
	);
	return $list;
}

function getConfig($school_id,$column = ''){
	if(!$school_id){
		return FALSE;
	}
	$config = db('school_config') -> where(array('school_id'=>$school_id)) -> find();
	if(!$column){
		return $config;
	}else{
		return $config[$column];
	}
}
// 阿拉伯数字转中文大写
// num：数字
// mode：有 五十八万五千四百五十六点一二；无 五八五四五六点一二
// sim：大写数字的繁简体
function NumToCapitals($num, $mode = true, $sim = true) {
	if (!is_numeric($num))
		return '含有非数字非小数点字符！';
	$char = $sim ? array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九') : array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
	$unit = $sim ? array('', '十', '百', '千', '', '万', '亿', '兆') : array('', '拾', '佰', '仟', '', '萬', '億', '兆');
	//小数部分
	if (strpos($num, '.')) {
		$retval = '点';
		list($num, $dec) = explode('.', $num);
		$dec = strval(round($dec, 2));
		if ($mode) {
			$retval .= "{$char[$dec['0']]}{$char[$dec['1']]}";
		} else {
			for ($i = 0, $c = strlen($dec); $i < $c; $i++) {
				$retval .= $char[$dec[$i]];
			}
		}
	}
	//整数部分
	$str = $mode ? strrev(intval($num)) : strrev($num);
	for ($i = 0, $c = strlen($str); $i < $c; $i++) {
		$out[$i] = $char[$str[$i]];
		if ($mode) {
			$out[$i] .= $str[$i] != '0' ? $unit[$i % 4] : '';
			if ($i > 1 and $str[$i] + $str[$i - 1] == 0) {
				$out[$i] = '';
			}
			if ($i % 4 == 0) {
				$out[$i] .= $unit[4 + floor($i / 4)];
			}
		}
	}
	$retval = join('', array_reverse($out)) . $retval;
	return $retval;
}

//数组转XML
function ArrToXml($arr) {
	if (!is_array($arr) || count($arr) == 0)
		return '';
	$xml = '<xml>';
	foreach ($arr as $key => $val) {
		if (is_numeric($val)) {
			$xml .= '<' . $key . '>' . $val . '</' . $key . '>';
		} else {
			$xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
		}
	}
	$xml .= '</xml>';
	return $xml;
}
//时间戳格式化，并兼容默认返回无字，适合模板中调用
function tpl_time( $type = 1 ,$timestamp = 0 ){
	$timestamp = $timestamp ?: time();
    switch ($type){
        case 2:
            $str = date('Y-m-d', $timestamp);
            break;
        case 3:
            $str = date('Y-m-d H:i', $timestamp);
            break;
        case 4:
            $str = date('m-d H:i', $timestamp);
            break;
        case 5:
            $str = date('m-d', $timestamp);
            break;
        case 6:
            $str = date('Y年m月d日 H:i', $timestamp);
            break;
        case 7:
            $str = date('m月d日 H:i', $timestamp);
            break;
        case 8:
            $str = date('Y年m月d日', $timestamp);
            break;

        case 1:
        default:
            $str = date('Y-m-d H:i:s', $timestamp);
            break;
    }

    return $str;
}
//获取小程序码
function  get_wxa_code($param){
    $tokenInfo = get_access_token();
	if($tokenInfo['code']){
		return FALSE;
	}
    //获取二维码
    $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.trim($tokenInfo);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	$post_data=json_encode(array(
		'scene'=>$param['data'],
		'width'=>$param['width'] ?: 430,
		'page'=>$param['page'] ?: 'pages/index/index'
	));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	$data = curl_exec($curl);
	curl_close($curl);
	return $data;
}
/**
 * 计算两点地理坐标之间的距离
 * @param  Decimal $longitude1 起点经度
 * @param  Decimal $latitude1  起点纬度
 * @param  Decimal $longitude2 终点经度 
 * @param  Decimal $latitude2  终点纬度
 * @param  Int     $unit       单位 1:米 2:公里
 * @return Decimal
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2){

    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI /180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
    $distance = $distance * $EARTH_RADIUS;

    return round($distance, 2);
}
//--------------------以下为原home中的方法----------------------------

//栏目ID有：直接写classid，逗号间隔多个classid，【.】操作符，取本栏目下所有子栏目的id
//<li>使用方法1：getArcs(92,2,$map);	//根据$map条件获取id为92的栏目下的两条文章</li>
//<li>使用方法2：getArcs('92,93',2,$map);	//根据$map条件获取id为92和93的栏目下的两条文章</li>
//<li>使用方法3：getArcs('70.',2,$map);	//获取pid为70的栏目下的两条文章</li>
function getArcs($classid, $limit = '6', $where = array(), $hascontent = FALSE, $only_field) {
	if (!$classid) {
		return FALSE;
	}
    $map['status'] = 1;
	if (strpos($classid, '.') !== false) {
		$classid_arr = explode('.', $classid);
		$classify_lists = get_classify_list($classid_arr[0], 0);
		foreach ($classify_lists as $v) {
			$class_ids[] = $v['id'];
		}
		$class_ids[] = $classid_arr[0];
		if ($class_ids) {
			$map['classid'] = array('in', $class_ids);
		}
	} elseif (strpos($classid, ',') !== false) {
		$class_ids = explode(',', $classid);
		$map['classid'] = array('in', $class_ids);
	} else {
		$map['classid'] = $classid;
	}
	$arcids = db('article') -> where($map) -> where($where) -> limit($limit) -> order('top desc,sorts asc, utime desc,id desc') -> column('id');
    $arcs = [];
	foreach ($arcids as $v) {
        $arcs[] = getOneArc($v, $hascontent, $only_field);
	}
	if ( $only_field ){
        foreach ( $arcs as $kr => $r){
            $arcs[$kr] = $r[0];
        }
    }
	return $arcs;
}

function getOneArc($arcid, $hascontent = FALSE, $only_field) {
	if (!$arcid) {
		return false;
	}
    $db = db('article') -> where(array('id' => $arcid));
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

function get_tree_classify() {
	$classify = get_classify_list(0);
	foreach ($classify as $k => $v) {
		$classify[$k]['sub'] = get_classify_list($v['id']);
	}
	return $classify;
}

//参数pid，取出pid下子栏目列表
function get_classify_list($pid = 0) {
    $res = [];
    $where = array(
        'pid' => $pid,
        'type' => array('neq', 5 ),
        'status' => 1
    );
    $ids = db('classify') -> where( $where ) -> field('id') -> order('sorts asc, id asc') -> select();
    if (!$ids) return $res;
    foreach ($ids as $k => $v) {
        $res[] = getClassify($v['id']);
    }
	return $res;
}

//获取指定classid下所有子栏目的id，$deep为层级深度，0为一直取到最后
//返回list，逗号间隔，连带自己id
//function classify_all_sub( $classid ){
//    $res = [];
//    $sub = get_classify_list( $classid );
//    if ( $sub ){
//        $sub_sub = [];
//        foreach ( $sub as $s ){
//            $sub_sub = array_merge( $sub_sub, get_classify_list( $s['id'] ) );
//        }
//    }
//    $res = array_merge( $sub, $sub_sub );
//    return $res;
//}

//获取平级菜单，2参数是否强制获取pid = 0 的，否则pid = 0 的会返回本菜单
//function make_classify_brother( $class, $force = false ){
//    $res = '';
//    if ( $class['pid'] != 0 || $force ){
//        $brother = get_classify_list( $class['pid'] );
//    }else{
//        $brother = [];
//    }
//
//    if ( $brother ){
//        foreach ($brother as $v){
//            $res = $res . '<a href="'.$v['class_link'].'" class="'. ($v['id'] == $class['id'] ? 'active' : '' ) .'">'.$v['title'].'</a>';
//        }
//    }else{
//        $res = '<a href="">'.$class['title'].'</a>';
//    }
//    return $res;
//}

function getClassify($classid, $position = false, $field) {
	if (!$classid) {
		return false;
	}
	$res = db('classify') -> where(array('id' => $classid)) ->order('sorts asc, id desc') -> find();
    if (!$res) {
        return false;
    }
    //生成栏目链接，自动识别类型与跳转新页
    switch ( $res['type'] ) {
        case 4 :
            //如果是链接到第一个子栏目则查询第一个子栏目id编写路径。	暂无缓存机制
            $first_sublink = db('classify') -> where(array('pid' => $classid, 'type' => array('neq', 5))) -> order('sorts asc, id asc') -> field('id') -> find();
            $res['class_link'] = $first_sublink ? url('lists', array('classid' => $first_sublink['id'])) : $res['class_link'] = url('lists', array('classid' => $res['id']));
            break;
        case 2 :
            $res['class_link'] = $res['url'];
            break;
        case 3 :
            $res['class_link'] = $res['url'] . '" target="_blank';
            break;
        case 1 :
        default :
        $res['class_link'] = url('lists', array('classid' => $res['id']));
            break;
    }
    if ( $position ){
        $res['position'] = htmlspecialchars_decode(make_position($res['id'], $res));
    }
    if ( $field ){
        return $res[$field];
    }
    return $res;
}

function make_position( $classid, $classify ){
    if ( !$classid || !$classify ) {
        return '';
    }
    $index_url = '/';
    $spacer = ' <span class="position_space">&gt;</span> ';
    $pos = '';

    if ( $classify['pid'] == 0 ){
        $pos = '<a href="/">首页</a> ' . $spacer . '<a href="' . $classify['class_link'] . '">' . $classify['title'] . '</a>';
    }else{
        $pos = make_position( $classify['pid'], getClassify($classify['pid'], false) ) . $spacer . '<a href="' . $classify['class_link'] . '">' . $classify['title'] . '</a>';
    }
    return $pos;
}


//--------------------以上为原home中的方法----------------------------


function time_difference(){
	$now=time();
	$dif=strtotime(date('Ymd',$now))+86399-$now;
	return $dif;
}


//生成返回json
function ret($code = 0, $data = null, $msg = '') {
	return array('code' => $code, 'msg' => $msg ? $msg : FE($code), 'data' => $data);
}

//错误码返回
function FE($code = 0, $type = 'sys', $lang = 'zh') {
	$val = db('error_code') -> where(array('code' => $code, 'type' => $type, 'lang' => $lang)) -> value('val');
	return $val;
}

//自动提取出详情中的第一张图片
//function auto_get_first($info) {
//	//自动获取文章中第一张图片
/*	$pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg|\.GIF|\.JPG|\.PNG|\.JPEG]))[\'|\"].*?[\/]?>/";*/
//	preg_match($pattern, htmlspecialchars_decode($info), $match);
//	return $match[1];
//}

//自动下载详情中的远程图片
function auto_download($value) {
	$img_array = array();
    preg_match_all('/(src)=["|\\\'| ]{0,}(http:\\/\\/(.*)\\.(jpg|jpeg|png|gif))["|\\\'| ]{0,}/isU', $value, $img_array);
    $img_array = array_unique($img_array[2]);
    set_time_limit(0);
    $imgPath = SITE_PATH . '/u/' . date('Ymd');
    if (!is_dir($imgPath)) {
        @mkdir($imgPath, 511);
    }
    foreach ($img_array as $key => $url) {
        $url = trim($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $file = curl_exec($curl);
        curl_close($curl);
		if(strlen($file) < 1024){
			continue;
		}
        $url_pathinfo = pathinfo($url);
        $filename = $imgPath . '/' . md5_file($url) . '.' . $url_pathinfo['extension'];
        $write = @fopen($filename, 'w');
        if ($write == false) {
            return false;
        }
        if (fwrite($write, $file) == false) {
            return false;
        }
        if (fclose($write) == false) {
            return false;
        }
        $filename = str_replace(SITE_PATH, '', $filename);
		$upload_log[] = array(
            'type' => 1,    //后台
            'url' => $filename,
            'size' => strlen($file),
            'ext' => $url_pathinfo['extension'],
            'time' => time(),
            'user_id' => '',
            'upload_ip' => NOWIP,
            'status' => 1,
            'sha1' => md5_file($url),
            'filename'=>$url,
            'group_id'=>2
        );
        $value = str_replace($url, $filename, $value);
    }
    db('files')->insertAll( $upload_log );
//	$value = htmlspecialchars($value);
	return $value;
}

/*
 * 缓存封装方法
 * @param String $key 要处理的缓存键值 存在种形式
 * 1：infotag型：取缓存 
 * 2：@infotag型：更新缓存
 * 3：@:0型：0表示更新全部缓存，其余数字表示更新该等级缓存
 * 4：*型：取得所有缓存
 */
function FS($key = '', $value = '', $timeout = '3600') {
	if (!$key) {
		return false;
	}
	$FS = controller('api/FS');
	//拆分字符串 判断@
	if (stripos($key, '@') > -1) {//@存在并且是第一个
		//此处执行更新缓存的操作
		$key_arr = explode('@', $key);
		//更新对应等级的操作 @1
		return $FS -> clearFS($key_arr[1]);
	}
	//中括号存在
	if (stripos($key, '[')) {
		return $FS -> getVal($key);
	}
	if ($key == '*') {
		return $FS -> allFS();
	}
	if ($value) {
		//执行写入
		return $FS -> setFS($key, $value, $timeout);
	} else {
		//执行正常key查询
		//判断缓存中是否有该key的    没有这个键值或没有值报false
		return $FS -> getFS($key);
	}
}

//------------测试反馈

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
	// 创建Tree
	$tree = array();
	if (is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] = &$list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId = $data[$pid];
			if ($root == $parentId) {
				$tree[] = &$list[$key];
			} else {
				if (isset($refer[$parentId])) {
					$parent = &$refer[$parentId];
					$parent[$child][] = &$list[$key];
				}
			}
		}
	}
	return $tree;
}

/*
 快捷取参数的方法，输入code值，取val，表Sysconfig，无code值返回全部配置
 */
function SC($code = '') {
    $system_config = cache('system_config');
    if ( !$system_config ){
        $_config = config();
        $_sc = Db::name('sysconfig')->field('val,code')->select();
        foreach ($_config as $k => $v){
            $_sc[] = [
                'code' => $k,
                'val' => $v
            ];
        }
        cache('system_config', $_sc);
    }
    $res = null;
    foreach ( $system_config as $c ){
        if ( $c['code'] == $code ){
            $res = $c['val'];
        }
    }
    return $res;
}

/*
 * 快捷取 tag 值的方法，默认取 title，加 .操作符取对应值
 */
function GT($code = '') {

    $_code = explode('.', $code);

    if ( !$_code[0] ){
        return null;
    }
    if ( is_numeric($_code[0]) ){
        $tag = Db::name('tags')->where(['id'=>$_code[0], 'status' => 1 ])->find();
    }else{
        $tag = Db::name('tags')->where(['group'=>$_code[0], 'status' => 1 ])->order('sort asc, id desc')->select();
        return $tag;
    }

    if ( !$tag ){
        return null;
    }
    if ( $_code[1] == '*' ){
        return $tag;
    }
    return $tag[ $_code[1] ? $_code[1] : 'title'];
}

function inlog($arr) {
	if (is_array($arr)) {
		$data['str'] = var_export($arr, true);
	} elseif ( is_object($arr) ){
        $data['str'] = json_encode($arr);
    } else {
		$data['str'] = $arr;
	}
	$data['ctime'] = date('Y-m-d H:i:s', time());
	$data['ctime_int'] = time();
    $data['url'] = NOWURL;
    $data['input'] = var_export( input() , true);
	$In = db('In');
	$newid = $In -> insert($data);
	return $newid;
}

/**
 * 字符串截取，支持中文和其他编码
 * static
 * access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice . '...' : $slice;
}

//PHP清除html、css、js格式并去除空格的PHP函数
function cutstr_html($string, $sublen) {
	$string = strip_tags($string);
	$string = preg_replace('/\n/is', '', $string);
	$string = preg_replace('/ |　/is', '', $string);
	$string = preg_replace('/&nbsp;/is', '', $string);

	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
	if (count($t_string[0]) - 0 > $sublen)
		$string = join('', array_slice($t_string[0], 0, $sublen)) . "…";
	else
		$string = join('', array_slice($t_string[0], 0, $sublen));
	return $string;
}

function DeleteHtml($str) {
	$str = trim($str);
	//清除字符串两边的空格
	$str = strip_tags($str, "");
	//利用php自带的函数清除html格式
	$str = preg_replace("/\t/", "", $str);
	//使用正则表达式替换内容，如：空格，换行，并将替换为空。
	$str = preg_replace("/\r\n/", "", $str);
	$str = preg_replace("/\r/", "", $str);
	$str = preg_replace("/\n/", "", $str);
	$str = preg_replace("/ /", "", $str);
	$str = preg_replace("/  /", "", $str);
	//匹配html中的空格
	return trim($str);
	//返回字符串
}

/**
 * 调试，用于保存数组到txt文件 正式生产删除
 * 用法：array2file($info, SITE_PATH.'post.txt');
 * @param type $array
 * @param type $filename
 */
function array2file($array, $filename) {
	if (defined("APP_DEBUG") && APP_DEBUG) {
		//修改文件时间
		file_exists($filename) or touch($filename);
		if (is_array($array)) {
			$str = var_export($array, TRUE);
		} else {
			$str = $array;
		}
		return file_put_contents($filename, $str);
	}
	return false;
}

/**
 * 产生一个指定长度的随机字符串,并返回给用户
 * @param type $len 产生字符串的长度
 * @return string 随机字符串
 */
function genRandomString($len = 6) {
	$chars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
	$charsLen = count($chars) - 1;
	// 将数组打乱
	shuffle($chars);
	$output = "";
	for ($i = 0; $i < $len; $i++) {
		$output .= $chars[mt_rand(0, $charsLen)];
	}
	return $output;
}

/**
 * 检测一个数据长度是否超过最小值
 * @param type $value 数据
 * @param type $length 最小长度
 * @return type
 */
function isMin($value, $length) {
	return mb_strlen($value, 'utf-8') >= (int)$length ? true : false;
}

/**
 * 检测一个数据长度是否超过最大值
 * @param type $value 数据
 * @param type $length 最大长度
 * @return type
 */
function isMax($value, $length) {
	return mb_strlen($value, 'utf-8') <= (int)$length ? true : false;
}

/**
 * 取得文件扩展
 * @param type $filename 文件名
 * @return type 后缀
 */
function fileext($filename) {
	$pathinfo = pathinfo($filename);
	return $pathinfo['extension'];
}

/**
 * 对 javascript escape 解码
 * @param type $str
 * @return type
 */
function unescape($str) {
	$ret = '';
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		if ($str[$i] == '%' && $str[$i + 1] == 'u') {
			$val = hexdec(substr($str, $i + 2, 4));
			if ($val < 0x7f)
				$ret .= chr($val);
			else if ($val < 0x800)
				$ret .= chr(0xc0 | ($val>>6)) . chr(0x80 | ($val & 0x3f));
			else
				$ret .= chr(0xe0 | ($val>>12)) . chr(0x80 | (($val>>6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
			$i += 5;
		} else if ($str[$i] == '%') {
			$ret .= urldecode(substr($str, $i, 3));
			$i += 2;
		} else
			$ret .= $str[$i];
	}
	return $ret;
}

/**
 * 字符截取
 * @param $string 需要截取的字符串
 * @param $length 长度
 * @param $dot
 */
function str_cut($sourcestr, $length, $dot = '...') {
	$returnstr = '';
	$i = 0;
	$n = 0;
	$str_length = strlen($sourcestr);
	//字符串的字节数
	while (($n < $length) && ($i <= $str_length)) {
		$temp_str = substr($sourcestr, $i, 1);
		$ascnum = Ord($temp_str);
		//得到字符串中第$i位字符的ascii码
		if ($ascnum >= 224) {//如果ASCII位高与224，
			$returnstr = $returnstr . substr($sourcestr, $i, 3);
			//根据UTF-8编码规范，将3个连续的字符计为单个字符
			$i = $i + 3;
			//实际Byte计为3
			$n++;
			//字串长度计1
		} elseif ($ascnum >= 192) {//如果ASCII位高与192，
			$returnstr = $returnstr . substr($sourcestr, $i, 2);
			//根据UTF-8编码规范，将2个连续的字符计为单个字符
			$i = $i + 2;
			//实际Byte计为2
			$n++;
			//字串长度计1
		} elseif ($ascnum >= 65 && $ascnum <= 90) {//如果是大写字母，
			$returnstr = $returnstr . substr($sourcestr, $i, 1);
			$i = $i + 1;
			//实际的Byte数仍计1个
			$n++;
			//但考虑整体美观，大写字母计成一个高位字符
		} else {//其他情况下，包括小写字母和半角标点符号，
			$returnstr = $returnstr . substr($sourcestr, $i, 1);
			$i = $i + 1;
			//实际的Byte数计1个
			$n = $n + 0.5;
			//小写字母和半角标点等与半个高位字符宽...
		}
	}
	if ($str_length > strlen($returnstr)) {
		$returnstr = $returnstr . $dot;
		//超过长度时在尾处加上省略号
	}
	return $returnstr;
}

/**
 * 取得URL地址中域名部分
 * @param type $url
 * @return \url 返回域名
 */
function urlDomain($url) {
	if ($url) {
		$pathinfo = parse_url($url);
		return $pathinfo['scheme'] . "://" . $pathinfo['host'] . "/";
	}
	return false;
}

/**
 * 获取当前页面完整URL地址
 * @return type 地址
 */
function get_url() {
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

/**
 * 对URL中有中文的部分进行编码处理
 * @param type $url 地址 http://www.abc3210.com/s?wd=博客
 * @return type ur;编码后的地址 http://www.abc3210.com/s?wd=%E5%8D%9A%20%E5%AE%A2
 */
function cn_urlencode($url) {
	$pregstr = "/[\x{4e00}-\x{9fa5}]+/u";
	//UTF-8中文正则
	if (preg_match_all($pregstr, $url, $matchArray)) {//匹配中文，返回数组
		foreach ($matchArray[0] as $key => $val) {
			$url = str_replace($val, urlencode($val), $url);
			//将转译替换中文
		}
		if (strpos($url, ' ')) {//若存在空格
			$url = str_replace(' ', '%20', $url);
		}
	}
	return $url;
}

//替换字符串中的时间替代符为当前时间
function time_sign_to_show($path) {
	$d = explode('-', date("Y-y-m-d-H-i-s"));
	$format = $path;
	$format = str_replace("{yyyy}", $d[0], $format);
	$format = str_replace("{yy}", $d[1], $format);
	$format = str_replace("{mm}", $d[2], $format);
	$format = str_replace("{dd}", $d[3], $format);
	$format = str_replace("{hh}", $d[4], $format);
	$format = str_replace("{ii}", $d[5], $format);
	$format = str_replace("{ss}", $d[6], $format);
	return $format;
}

/**
 * 根据文件扩展名来判断是否为图片类型
 * @param type $file 文件名
 * @return type 是图片类型返回 true，否则返回 false
 */
function isImage($file) {
	$ext_arr = array('jpg', 'gif', 'png', 'bmp', 'jpeg', 'tiff');
	//取得扩展名
	$ext = fileext($file);
	return in_array($ext, $ext_arr) ? true : false;
}

/**
 * 短信发送
 * @param type $num  手机号码
 * @param type $msg  验证码
 * @param type $type 类型，目前将字段与模版写死
 * @param type $ext  附加内容（无效）
 */

function SendSms($num, $msg, $type, $ext = 'none') {

	import('TopClient');
	import('ResultSet');
	import('RequestCheckUtil');
	import('TopLogger');
	import('AlibabaAliqinFcSmsNumSendRequest');

	//注册
	switch( $type ) {
		case 'reg' :
			$sign = '注册验证';
			$tpl = "SMS_5026190";
			$send = '{"code":"' . $msg['code'] . '","product":"' . $msg['product'] . '"}';
			//验证码${code}，您正在注册成为${product}用户，感谢您的支持！
			break;
		case 'login' :
			$sign = '登录验证';
			$tpl = "SMS_5026192";
			$send = '{"code":"' . $msg['code'] . '","product":"' . $msg['product'] . '"}';
			//验证码${code}，您正在登录${product}，若非本人操作，请勿泄露。
			break;

		default :
			return false;
			break;
	}

	$c = new \TopClient;
	$c -> appkey = SC('duanxin_appkey');
	//内部获取
	$c -> secretKey = SC('duanxin_secret');
	//内部获取
	$c -> format = 'json';
	//返回的错误格式
	$req = new \AlibabaAliqinFcSmsNumSendRequest;

	$req -> setSmsType("normal");
	//	$req->setSmsFreeSignName(SC('duanxin_sign'));	//签名
	//	$req->setSmsTemplateCode(SC('duanxin_template'));	//短信模版

	$req -> setSmsFreeSignName($sign);
	//签名
	$req -> setSmsTemplateCode($tpl);
	//短信模版

	$req -> setExtend($ext);

	//$send = '{"name":"'.$msg.'"}';

	$req -> setSmsParam($send);
	//内容
	$req -> setRecNum($num);
	//发送号码，以逗号间隔多个

	$resp = $c -> execute($req);
	$resp = json_decode(json_encode($resp), TRUE);

	if ($resp['result']['success'] == TRUE) {
		return TRUE;
	} else {
		return '错误码：' . $resp['code'] . ',描述：' . $resp['sub_msg'];
	}
}

/**
 * 邮件发送
 * @param type $address 接收人 单个直接邮箱地址，多个可以使用数组
 * @param type $title 邮件标题
 * @param type $message 邮件内容
 */
function SendMail($address, $title, $message, $files) {
	//  $config = cache('Config');
	//	$config=FS('sysconfig');
	import('PHPMailer');
	try {
		$mail = new \PHPMailer();
		$mail -> IsSMTP();
		// 设置邮件的字符编码，若不指定，则为'UTF-8'
		$mail -> CharSet = C("DEFAULT_CHARSET");
		$mail -> IsHTML(true);
		// 添加收件人地址，可以多次使用来添加多个收件人
		if (is_array($address)) {
			foreach ($address as $k => $v) {
				if (is_array($v)) {
					$mail -> AddAddress($v[0], $v[1]);
				} else {
					$mail -> AddAddress($v);
				}
			}
		} else {
			$mail -> AddAddress($address);
		}
		// 设置邮件正文
		$mail -> Body = $message;
		// 设置邮件头的From字段。
		//      $mail->From = $config['mail_from'];
		$mail -> From = SC('mail_from');
		// 设置发件人名字
		//      $mail->FromName = $config['mail_fname'];
		$mail -> FromName = SC('mail_fname');
		// 设置邮件标题
		$mail -> Subject = $title;
		//附件
		if ($files) {
			foreach ($files as $k => $v) {
				$mail -> AddAttachment($v['url'], $v['name']);
			}
		}
		// 设置SMTP服务器。
		//      $mail->Host = $config['mail_server'];
		$mail -> Host = SC('mail_server');
		// 设置为“需要验证”
		//      if ($config['mail_auth']) {
		if (SC('mail_auth')) {
			$mail -> SMTPAuth = true;
		} else {
			$mail -> SMTPAuth = false;
		}
		// 设置用户名和密码。
		//      $mail->Username = $config['mail_user'];
		$mail -> Username = SC('mail_user');
		//      $mail->Password = $config['mail_password'];
		$mail -> Password = SC('mail_password');
		return $mail -> Send();
	} catch (phpmailerException $e) {
		return $e -> errorMessage();
	}
}

/**************************************************************
 *
 *    使用特定function对数组中所有元素做处理
 *    @param  string  &$array     要处理的字符串
 *    @param  string  $function   要执行的函数
 *    @return boolean $apply_to_keys_also     是否也应用到key上
 *    @access public
 *
 *************************************************************/
function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
	static $recursive_counter = 0;
	if (++$recursive_counter > 1000) {
		die('possible deep recursion attack');
	}
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			arrayRecursive($array[$key], $function, $apply_to_keys_also);
		} else {
			$array[$key] = $function($value);
		}

		if ($apply_to_keys_also && is_string($key)) {
			$new_key = $function($key);
			if ($new_key != $key) {
				$array[$new_key] = $array[$key];
				unset($array[$key]);
			}
		}
	}
	$recursive_counter--;
}

/**************************************************************
 *
 *    将数组转换为JSON字符串（兼容中文）
 *    @param  array   $array      要转换的数组
 *    @return string      转换得到的json字符串
 *    @access public
 *
 *************************************************************/
//function JSON($array) {
//	arrayRecursive($array, 'urlencode', true);
//	$json = json_encode($array);
//	return urldecode($json);
//}

/**
 * 发送HTTP请求方法
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params, $method = 'GET', $header = array(), $multi = false) {
	$opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
	/* 根据请求类型设置特定参数 */
	switch(strtoupper($method)) {
		case 'GET' :
			$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
			break;
		case 'POST' :
			//判断是否传输文件
			$params = $multi ? $params : http_build_query($params);
			$opts[CURLOPT_URL] = $url;
			$opts[CURLOPT_POST] = 1;
			$opts[CURLOPT_POSTFIELDS] = $params;
			break;
		default :
			throw new Exception('不支持的请求方式！');
	}
	/* 初始化并执行curl请求 */
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_REFERER, SC('site_url'));
	$data = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($error)
		throw new Exception('请求发生错误：' . $error);
	return $data;
}

function CURL_GET($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function http_express($data){
	$host = SC('express_host');
	$method = "POST";
	$headers = array();
	array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
	$querys = "";
	$sign = md5(SC('express_appid').SC('express_method').time().SC('express_sign'));
	$bodys = [
	"app_id"=> SC('express_appid'),
	"method"=> SC('express_method'),
	"sign"=> $sign,
	"ts"=> time(),
	"data"=>$data
	];
	$bodys = http_build_query($bodys);
	$url = $host;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	if (1 == strpos("$".$host, "https://"))
	{
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
	$output = curl_exec($curl);
	curl_close($curl);
	return ret(0,json_decode($output));
}
