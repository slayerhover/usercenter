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
		'opcode'	=> 'login',
		'phone' 	=> '13512351235',
		'password'	=> '12345678',
	);
	
	$postdata['sign'] = (new Encrypt)->encode($postdata);
	
	#dump($postdata);
	
	$result = curl_get($posturl, $postdata);
		
}catch(Exception $e){
	$result	=	[
				'code'	=>	0,
				'msg'	=>	$e->getMessage(),
	];	
}

echo json_encode(msgpack_unpack($result));
