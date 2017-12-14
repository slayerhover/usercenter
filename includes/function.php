<?php
/***
返回一段时间内的，时间列表
***/
function timelist($begin,$end)
{
    $begin = strtotime($begin);//开始时间
    $end = strtotime($end);//结束时间
	$retrun_time = array();
    for($i=$begin; $i<=$end;$i+=(24*3600))
    {
		$date = array();
		$date[0] = date("Ymd",$i).'000000';
		$date[1] = date("Ymd",$i).'235959';
		array_push($retrun_time,$date);
    }
	return $retrun_time;
}
/***
* 根据年月日时分秒返回时间戳
*
*/
function get_ctime($time){
	if($time !='' &&  strlen($time)==14 ){
		$year = substr($time,0,4);
		$month = substr($time,4,2);
		$day = substr($time,6,2);
		$hours = substr($time,8,2);
		$minutes = substr($time,10,2);
		$seconds  = substr($time,12,2);
		$t = mktime($hours,$minutes,$seconds,$month,$day,$year);
		return $t;
	}else return $time;
}
function write_log($msg=''){
	$log_file = date("Y-m-d");
	$handle = @fopen("./logs/".$log_file.".log", "a+");
	@flock($handle, LOCK_EX) ;
	$text = date("Y-m-d H:i:s")." ".$msg."\r\n";
    @fwrite($handle,$text);
	@flock($handle, LOCK_UN);
	@fclose($handle);
}
/**
 * 校验日期格式是否正确
 *
 * @param string $date 日期
 * @param string $formats 需要检验的格式数组
 * @return boolean
 */
function checkDateIsValid($date, $format="Y-m-d") {
    if (!strtotime($date)) { //strtotime转换不对，日期格式显然不对。
        return false;
    }
	
	$strArr = explode("-",$date);
	if(empty($strArr)){	return false; }
	foreach($strArr as $val){
		if(strlen($val)<2){
			$val="0".$val;
		}
		$newArr[]=$val;
	}
	$str =implode("-",$newArr);
	$unixTime=strtotime($str);
	$checkDate= date($format,$unixTime);
	return ($checkDate==$str);
}

function getIp(){
	if(!empty($_SERVER['HTTP_CLIENT_IP'])){
	   return $_SERVER['HTTP_CLIENT_IP']; 
	}elseif(!empty($_SERVER['HTTP_X_FORVARDED_FOR'])){
	   return $_SERVER['HTTP_X_FORVARDED_FOR'];
	}elseif(!empty($_SERVER['REMOTE_ADDR'])){
	   return $_SERVER['REMOTE_ADDR'];
	}else{
	   return "unknow IP";
	}
}

function dump($vars, $label = '', $return = false)
{
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
        $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) { return $content; }
    echo $content;
    return null;
}

function json($vars)
{
	header("Content-type: application/json");
    echo json_encode($data);
	exit;
}

function curl_get($url,$postdata='',$pre_url='http://www.baidu.com',$proxyip=false,$compression='gzip, deflate'){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT,5);
		
		$client_ip	= rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
		$x_ip		= rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$x_ip,'CLIENT-IP:'.$client_ip));//构造IP				
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if($postdata!=''){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}
		$pre_url = $pre_url ? $pre_url : "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		curl_setopt($ch, CURLOPT_REFERER, $pre_url);
		if($proxyip){
			curl_setopt($ch, CURLOPT_PROXY, $proxyip);
		}		
		if($compression!='') {	
			curl_setopt($ch, CURLOPT_ENCODING, $compression);	
		}		
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11'); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
}
