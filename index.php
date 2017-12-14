<?php
header('content-type:text/html;charset=utf-8');
date_default_timezone_set('PRC');
define('BASEDIR', dirname(__FILE__));
include(BASEDIR."/includes/db.php");
include(BASEDIR."/includes/cache.php");
include(BASEDIR."/includes/tools.php");
include(BASEDIR."/includes/validate.php");
#include(BASEDIR."/includes/encrypt.php");
include(BASEDIR."/includes/function.php");
include(BASEDIR."/user.php");

/**
* 根据传入的参数来调用不同的接口
* 1. 验证调用签名
* 2. 验证参数数据
*
**/
try{
	$request = empty($_GET)?$_POST:$_GET;	
	if( empty($request['opcode']) ){
			throw new Exception('目标参数不能空');
	}	
	/****验证签名,暂时忽略BOF****/	
	#$sign    = $request['sign']??'';
	#unset($request['sign']);
	#if(!User::checksign($request, $sign)){
	#		throw new Exception('验证签名失败');
	#}
	/****验证签名,暂时忽略EOF****/	
	write_log(json_encode($request, JSON_UNESCAPED_UNICODE));
	
	/**
	  * 验证注册参数
	  * 返回执行结果
	  *
	  **/
	switch(strtolower($request['opcode'])){
		case 'getcity':
			$result	=	User::getCity($request);
			break;
		case 'register':			
			$result	=	User::register($request);
			break;
		case 'login':
			$result	=	User::login($request);
			break;
		case 'update':
			$result	=	User::update($request);
			break;	
		case 'certification':
			$result	=	User::certification($request);
			break;
		case 'resetpwd':
			$result	=	User::resetPwd($request);
			break;	
		case 'checktoken':
			$result	=	User::checkToken($request);
			break;
		case 'userinfo':
			$result	=	User::userInfo($request);
			break;
		case 'sendnotice':
			$result	=	User::sendNotice($request);
			break;
		case 'getnotice':
			$result	=	User::getNotice($request);
			break;	
		case 'readnotice':
			$result	=	User::readNotice($request);
			break;	
		case 'usergrade':
			$result	=	User::userGrade($request);
			break;	
		case 'changeuserstatus':
			$result	=	User::changeUserStatus($request);
			break;
		default:
			$result	=	[
				'code'	=>	0,
				'msg'	=>	'参数有误',
			];
	}
}catch(Exception $e){
	$result	=	[
				'code'	=>	0,
				'msg'	=>	$e->getMessage(),
	];	
}

#exit(json_encode($result,JSON_UNESCAPED_UNICODE));
exit(msgpack_pack($result));
