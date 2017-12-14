<?php
header('content-type:text/html;charset=utf-8');
define('BASEDIR', dirname(__FILE__) . '/../..');
include(BASEDIR."/includes/encrypt.php");
include(BASEDIR."/includes/function.php");
try{
	/**
	* curl post提交数据
	*
	**/
	$posturl = "http://ucenter.scsj.net.cn";
	
	$postdata= array(
		'opcode'	=> 'register',
		'phone' 	=> '13512351237',
		'password'	=> '12345678',
		'repassword'=> '12345678',
		'realname' 	=> '范尔加',
		'gender' 	=> '1',
		'email' 	=> '347594728@qq.com',
		'type' 		=> '4',
		'cityid' 	=> '410100',
		'idcard' 	=> '410100198409071023',
	);
	
	$postdata['sign'] = (new Encrypt)->encode($postdata);
		
	$result = curl_get($posturl, $postdata);
			
}catch(Exception $e){
	$result	=	[
				'code'	=>	0,
				'msg'	=>	$e->getMessage(),
				'data'	=>	[],
	];	
}

echo json_encode(msgpack_unpack($result));