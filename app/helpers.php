<?php

use App\Components\Curl;

define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);
define('PB', 1125899906842624);

define('Minute', 60);
define('Hour', 3600);
define('Day', 86400);

define('Mbps', 125000);

// 生成SS密码
if(!function_exists('makeRandStr')){
	function makeRandStr($length = 6, $isNumbers = false) {
		// 密码字符集，可任意添加你需要的字符
		if(!$isNumbers){
			$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
		}else{
			$chars = '0123456789';
		}

		$char = '';
		for($i = 0; $i < $length; $i++){
			$char .= $chars[mt_rand(0, strlen($chars) - 1)];
		}

		return $char;
	}
}

// base64加密（处理URL）
if(!function_exists('base64url_encode')){
	function base64url_encode($data) {
		return strtr(base64_encode($data), ['+' => '-', '/' => '_', '=' => '']);
	}
}

// base64解密（处理URL）
if(!function_exists('base64url_decode')){
	function base64url_decode($data) {
		return base64_decode(strtr($data, '-_', '+/'));
	}
}

// 根据流量值自动转换单位输出
if(!function_exists('flowAutoShow')){
	function flowAutoShow($value = 0) {
		$value = abs($value);
		if($value >= PB){
			return round($value / PB, 2)."PB";
		}elseif($value >= TB){
			return round($value / TB, 2)."TB";
		}elseif($value >= GB){
			return round($value / GB, 2)."GB";
		}elseif($value >= MB){
			return round($value / MB, 2)."MB";
		}elseif($value >= KB){
			return round($value / KB, 2)."KB";
		}else{
			return round($value, 2)."B";
		}
	}
}

if(!function_exists('toMB')){
	function toMB($traffic) {
		return $traffic * MB;
	}
}

if(!function_exists('toGB')){
	function toGB($traffic) {
		return $traffic * GB;
	}
}

if(!function_exists('flowToGB')){
	function flowToGB($traffic) {
		return $traffic / GB;
	}
}

// 文件大小转换
if(!function_exists('formatBytes')){
	function formatBytes($bytes, $precision = 2) {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes? log($bytes) : 0) / log(KB));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(KB, $pow);

		return round($bytes, $precision).' '.$units[$pow];
	}
}

// 秒转时间
if(!function_exists('seconds2time')){
	function seconds2time($seconds) {
		$day = floor($seconds / Day);
		$hour = floor(($seconds % Day) / Hour);
		$minute = floor((($seconds % Day) % Hour) / Minute);
		if($day > 0){
			return $day.'天'.$hour.'小时'.$minute.'分';
		}else{
			if($hour != 0){
				return $hour.'小时'.$minute.'分';
			}else{
				return $minute.'分';
			}
		}
	}
}

// 获取访客真实IP
if(!function_exists('getClientIP')){
	function getClientIP() {
		/*
		 * 访问时用localhost访问的，读出来的是“::1”是正常情况
		 * ::1说明开启了IPv6支持，这是IPv6下的本地回环地址的表示
		 * 使用IPv4地址访问或者关闭IPv6支持都可以不显示这个
		 */
		if(isset($_SERVER)){
			if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
				$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
				$ip = $_SERVER['REMOTE_ADDR'];
			}elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}elseif(isset($_SERVER['REMOTE_ADDR'])){
				$ip = $_SERVER['REMOTE_ADDR'];
			}else{
				$ip = 'unknown';
			}
		}else{
			// 绕过CDN获取真实访客IP
			if(getenv('HTTP_X_FORWARDED_FOR')){
				$ip = getenv('HTTP_X_FORWARDED_FOR');
			}elseif(getenv('HTTP_CLIENT_IP')){
				$ip = getenv('HTTP_CLIENT_IP');
			}else{
				$ip = getenv('REMOTE_ADDR');
			}
		}

		if(trim($ip) == '::1'){
			$ip = '127.0.0.1';
		}

		return $ip;
	}
}

// 获取IPv6信息
if(!function_exists('getIPv6')){
	/*
	 * {
	 *     "longitude": 105,
	 *     "latitude": 35,
	 *     "area_code": "0",
	 *     "dma_code": "0",
	 *     "organization": "AS23910 China Next Generation Internet CERNET2",
	 *     "country": "China",
	 *     "ip": "2001:da8:202:10::36",
	 *     "country_code3": "CHN",
	 *     "continent_code": "AS",
	 *     "country_code": "CN"
	 * }
	 *
	 * {
	 *     "longitude": 105,
	 *     "latitude": 35,
	 *     "area_code": "0",
	 *     "dma_code": "0",
	 *     "organization": "AS9808 Guangdong Mobile Communication Co.Ltd.",
	 *     "country": "China",
	 *     "ip": "2409:8a74:487:1f30:5178:e5a5:1f36:3525",
	 *     "country_code3": "CHN",
	 *     "continent_code": "AS",
	 *     "country_code": "CN"
	 * }
	 */
	function getIPv6($ip) {
		$url = 'https://api.ip.sb/geoip/'.$ip;

		try{
			$result = json_decode(Curl::send($url), true);
			if(!is_array($result) || isset($result['code'])){
				throw new Exception('解析IPv6异常：'.$ip);
			}

			return $result;
		}catch(Exception $e){
			Log::error($e->getMessage());

			return [];
		}
	}
}

// 随机UUID
if(!function_exists('createGuid')){
	function createGuid() {
		mt_srand((double) microtime() * 10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);
		$uuid = substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid, 12,
				4).$hyphen.substr($charid, 16, 4).$hyphen.substr($charid, 20, 12);

		return strtolower($uuid);
	}
}

// 过滤emoji表情
if(!function_exists('filterEmoji')){
	function filterEmoji($str) {
		$str = preg_replace_callback('/./u', function(array $match) {
			return strlen($match[0]) >= 4? '' : $match[0];
		}, $str);

		return $str;
	}
}

// 验证手机号是否正确
if(!function_exists('isMobile')){
	function isMobile($mobile) {
		if(!is_numeric($mobile)){
			return false;
		}

		return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#',
			$mobile)? true : false;
	}
}
