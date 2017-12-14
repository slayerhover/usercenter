<?php
use Illuminate\Database\Capsule\Manager as DB;

final class User{
	/**
     * @api 验证签名
     */
	public static function checksign($request, $sign){						
		$sign	= str_replace(' ', '+', $sign);		
		$encode = (new Encrypt)->encode($request);
		return (strcmp($sign, $encode)===0);
	}
	/**
     * @api 获取城市列表
     */
	public static function getCity($request){
		$up			= $request['up']??0;
		$inputs	= array(
				['name'=>'up', 'value'=>$up, 'fun'=>'isInteger', 'role'=>'gte:0|lte:991400', 'msg'=>'城市ID格式有误'],
		);
		$checkResult	= Validate::check($inputs);		
		if(	!empty($checkResult) )	throw new Exception(json_encode($checkResult, JSON_UNESCAPED_UNICODE));
		$data	=	DB::table('uc_city')->where('up','=',$up)->select('id','name')->get();
		
		$result	=	[
					'opcode'	=>	'getCity',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	$data,
		];
		#$result['sign']	= (new Encrypt)->encode($result);
		return $result;
	}
	/**
     * @api 登陆
     */
	public static function login($request){
		$phone		= $request['phone']??'';
		$password	= $request['password']??'';
		$ip			= $request['ip']??'';
		$inputs	= array(
				['name'=>'phone',	'value'=>$phone,	'fun'=>'isPhone', 'role'=>'min:11|max:11|required', 'msg'=>'手机号码格式有误'],
				['name'=>'password','value'=>$password,	'role'=>'min:6|max:32|required', 'msg'=>'密码格式有误'],
				['name'=>'ip',		'value'=>$isIp,		'fun'=>'isIp', 'msg'=>'IP地址格式有误'],
		);
		$checkResult	= Validate::check($inputs);		
		if(	!empty($checkResult) )	throw new Exception(json_encode($checkResult, JSON_UNESCAPED_UNICODE));		
		$salter= DB::table('uc_user')->where('phone','=',$phone)->first();
		if(empty($salter)){
			throw new Exception('用户不存在.');
		}
		$now   = time();
		if($now<$salter['lockuntil']){
			throw new Exception('该用户当前处于锁定状态.');
		}
		$Redis= new Cache;
		$user = DB::table('uc_user')->where('phone','=',$phone)
							   ->where('password','=',md5($password.$salter['salt']))
							   ->select('uid','token','phone','salt','lockuntil','lastlogintime')
							   ->first();	
		#write_log(json_encode($user, JSON_UNESCAPED_UNICODE));		
		if(empty($user)){
			$failedTimes = $Redis->incr($phone);
			if($failedTimes>5){
				DB::table('uc_user')->where('phone','=',$phone)->update(['lockuntil'=>time()+20*60]);
			}
			throw new Exception('登陆失败.');
		}
		$rows = array(
				'lastlogintime'	=>	$now,
				'lastloginip'	=>	$ip,
				'updated'		=>	$now,				
		);
		$token	= (empty($user['token'])||($now-$user['lastlogintime']>86400)) ? 'auth_' . md5($user['phone'].$now.$ip.$user['salt']) : $user['token'];
		$rows['token']	=	$token;
		if(!($Redis->exists($token))){
			$Redis->set($token, $user);
		}else{
			$Redis->expire($token);
		}
		DB::table('uc_user')->where('uid','=',$user['uid'])->update($rows);
		
		$result	=	[
					'opcode'	=>	'login',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'token'		=>	$token,
		];
		#$result['sign']	= (new Encrypt)->encode($result);
		return $result;
	}
	/**
     * @api 注册
     */
	public static function register($request){
		$phone		= $request['phone']??'';
		$username	= $request['username']??$phone;
		$password	= $request['password']??'';
		$repassword	= $request['repassword']??'';
		$invitecode	= $request['invitecode']??'';
		$realname	= $request['realname']??'';
		$gender		= $request['gender']??'0';
		$email		= $request['email']??'';
		$type		= $request['type']??1;
		$cityid		= $request['cityid']??'410100';
		$idcard		= $request['idcard']??'';
		$comefrom	= $request['comefrom']??'pc';				
		$ip			= $request['ip']??'';
		$inputs	= array(
				['name'=>'phone',  'value'=>$phone,	 'fun'=>'isPhone', 'role'=>'min:11|max:11|required', 'msg'=>'手机号码格式有误'],
				['name'=>'username','value'=>$username,	 'fun'=>'isUsername', 'msg'=>'用户名格式有误'],
				['name'=>'password','value'=>$password,	'role'=>'min:6|max:32|required', 'msg'=>'密码格式有误'],
				['name'=>'repassword','value'=>$repassword,	'role'=>'min:6|max:32|eq:'.$password, 'msg'=>'重复密码格式有误'],
				['name'=>'invitecode', 'value'=>$invitecode,'role'=>'min:6|max:32', 'msg'=>'邀请码格式有误'],
				['name'=>'realname', 'value'=>$realname, 'fun'=>'isName', 'msg'=>'姓名格式有误'],
				['name'=>'gender', 'value'=>$gender, 'fun'=>'isBool', 'msg'=>'性别格式有误'],
				['name'=>'email', 'value'=>$email, 'fun'=>'isEmail', 'msg'=>'电子邮箱格式有误'],
				['name'=>'type', 'value'=>$type, 'fun'=>'isInteger', 'role'=>'in:1,2,3,4', 'msg'=>'会员类别格式有误'],
				['name'=>'cityid', 'value'=>$cityid, 'fun'=>'isInteger', 'role'=>'gt:110000|lt:991400', 'msg'=>'城市ID格式有误'],
				['name'=>'idcard', 'value'=>$idcard, 'fun'=>'isIdcard', 'msg'=>'身份证号码格式有误'],
				['name'=>'ip',		'value'=>$ip,		'fun'=>'isIp', 'msg'=>'IP地址格式有误'],
		);
		$checkResult	= Validate::check($inputs);
		if(DB::table('uc_user')->where('phone','=',$phone)->count()>0)
			array_push($checkResult, ['phone'=>'手机号码'.$phone.'已存在']);
		if(!empty($username)&&DB::table('uc_user')->where('username','=',$username)->count()>0)
			array_push($checkResult, ['username'=>'用户名'.$username.'已存在']);
		if(	!empty($checkResult) )	throw new Exception(json_encode($checkResult, JSON_UNESCAPED_UNICODE));
		
		$salt = str_random(16);
		$now  = time();
        $code = file_get_contents('http://id.scsj.net.cn/uid');
		if(!empty($invitecode)){
			$inviter 	= DB::table('uc_userinfo')->where('invitecode','=',$invitecode)->select('uid', 'invitecode', 'maxlevel')->first();
			$invitcount	= DB::table('uc_userinfo')->where('invitecode','=',$invitecode)->count(); 
			if (!$inviter) Throw new Exception('无效的邀请码');
			if ($invitcount>$inviter->maxlevel) Throw new Exception('无法注册,该邀请码达到最大使用次数');
			$up = $inviter['uid'];
		}else{
			$up = 0;
		}
		$token= 'auth_' . md5($phone.$now.$ip.$salt);
		
        DB::transaction(function () use ($phone,$username,$password,$salt,$email,$token,$type,$up,$cityid,$idcard,$gender,$ip,$now,$code,$realname,$comefrom){
            $rows = [
				'phone'			=> $phone,
				'username'		=> $username,
				'password'		=> md5($password . $salt),
				'salt'			=> $salt,
				'email'			=> $email,
				'token'			=> $token,
				'type'			=> $type,
				'up'			=> $up,
				'cityid'		=> $cityid,
				'idcard'		=> $idcard,
				'gender'		=> $gender,
				'lastlogintime'	=> $now,
				'lastloginip'	=> $ip,
				'created'		=> $now,				
            ];
			$uid = DB::table('uc_user')->insertGetId($rows);
			$inforows = [
				'uid'		=>	$uid,
				'invitecode'=>	$code,
				'realname'	=>	$realname,
				'comefrom'	=>	$comefrom,
			];
			DB::table('uc_userinfo')->insert($inforows);			
			if($up>0){
				$content="恭喜您，成功邀约{$username}注册成为新用户";
				DB::table('uc_notice')->insert(['uid'=>$up, 'content'=>$content, 'created_at'=>date('Y-m-d H:i:s')]);
			}
        });
		
		$user = DB::table('uc_user')->where('phone','=',$phone)
							   ->select('uid','token','phone','salt','lockuntil')
							   ->first();
		(new Cache)->set($token, $user);
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'token'		=>	$token,
		];
		#$result['sign']	= (new Encrypt)->encode($result);
		return $result;
	}	
	/**
     * @api 更新信息
     */
	public static function update($request){				
		$realname	= $request['realname']??'';
		$gender		= $request['gender']??'0';
		$email		= $request['email']??'';
		$cityid		= $request['cityid']??'410100';
		$idcard		= $request['idcard']??'';
		$token		= $request['token']??'';
		$inputs	= array(				
				['name'=>'realname', 'value'=>$realname, 'fun'=>'isName', 'msg'=>'姓名格式有误'],
				['name'=>'gender', 'value'=>$gender, 'fun'=>'isBool', 'msg'=>'性别格式有误'],
				['name'=>'email', 'value'=>$email, 'fun'=>'isEmail', 'msg'=>'电子邮箱格式有误'],
				['name'=>'cityid', 'value'=>$cityid, 'fun'=>'isInteger', 'role'=>'gte:110000|lte:991400', 'msg'=>'城市ID格式有误'],
				['name'=>'idcard', 'value'=>$idcard, 'fun'=>'isIdcard', 'msg'=>'身份证号码格式有误'],		
				['name'=>'token',  'value'=>$token,	'role'=>'min:37|max:37|required', 'msg'=>'token格式有误'],
		);
		$checkResult	= Validate::check($inputs);
		if(	!empty($checkResult) )	throw new Exception(json_encode($checkResult, JSON_UNESCAPED_UNICODE));
		
		if(self::checktokenValid($token)){
			$uid = $Redis->get($token);	
		}
		
		$now  = time();
        DB::transaction(function () use ($email,$cityid,$idcard,$gender,$now,$realname){
            $rows = [
				'email'			=> $email,
				'cityid'		=> $cityid,
				'idcard'		=> $idcard,
				'gender'		=> $gender,
				'updated'		=> $now,
            ];
			$uid = DB::table('uc_user')->where()->update($rows);
			$inforows = [
				'realname'	=>	$realname,
			];
			DB::table('uc_userinfo')->insert($inforows);			
			if($up>0){
				$content="恭喜您，成功邀约{$username}注册成为新用户";
				DB::table('uc_notice')->insert(['uid'=>$up, 'content'=>$content, 'created_at'=>date('Y-m-d H:i:s')]);
			}
        });
		
		$user = DB::table('uc_user')->where('phone','=',$phone)
							   ->select('uid','token','phone','salt','lockuntil')
							   ->first();
		(new Cache)->set($token, $user);
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'token'		=>	$token,
		];
		#$result['sign']	= (new Encrypt)->encode($result);
		return $result;
	}
	/**
     * @api 实名认证
     */
	public static function certification($request){
		$result	=	[
					'opcode'	=>	'certification',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;
	}
	/**
     * @api 重置密码
     */
	public static function resetpwd($request){
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;
	}
	/**
     * @api 验证登陆标记
     */
	private static function checktokenValid($request){
		$token		= $request['token']??'';
		$inputs	= array(
				['name'=>'token','value'=>$token,	'role'=>'min:37|max:37|required', 'msg'=>'token格式有误'],
		);
		$checkResult	= Validate::check($inputs);		
		if(	!empty($checkResult) ){return FALSE;}		
		
		$Redis= new Cache;		
		return $Redis->exists($token);
	}
	/**
     * @api 验证登陆标记
     */
	public static function checktoken($request){
		$token		= $request['token']??'';
		$inputs	= array(
				['name'=>'token','value'=>$token,	'role'=>'min:37|max:37|required', 'msg'=>'token格式有误'],
		);
		$checkResult	= Validate::check($inputs);		
		if(	!empty($checkResult) )	throw new Exception(json_encode($checkResult, JSON_UNESCAPED_UNICODE));		
		
		$Redis= new Cache;
		if(!$Redis->exists($token)){
			throw new Exception('签名验证失败');
		}else{
			$Redis->expire($token);
			$result	=	[
					'opcode'	=>	'checktoken',
					'code'		=>	'200',
					'msg'		=>	'验证成功',
			];
		}
		#$result['sign']	= (new Encrypt)->encode($result);
		return $result;
	}	
	/**
     * @api 用户基本信息
     */
    public static function userinfo($request)
    {
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;	
	}
	/**
     * @api 发送消息
     */
    public static function sendNotice($request)
    {
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;	
	}
	
    /**
     * @api {post} /user/notice.do 获取用户消息
     */
    public static function getNotice()
    {
        return rJson(\user()->Notice()->orderBy('has_read','ASC')->get());
    }



    /**
     * @api {post} /user/readnotice.do 读取某条未读消息
     */
    public static function readNotice(Request $request)
    {
        $notice=UserNotice::find($request->get('id'));
        $notice->has_read=1;
        $notice->save();
        return rJson($notice,'操作成功');
    }

	/**
     * @api 用户级别信息
     */
    public static function userGrade($request)
    {
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;	
	}
	
	/**
     * @api {post}/user/changeuserstatus.do 改变用户状态
     */
    public static function changeUserStatus(Request $request)
    {
		$result	=	[
					'opcode'	=>	'register',
					'code'		=>	'200',
					'msg'		=>	'调用成功',
					'data'		=>	[],
					'sign'		=>	$sign,
		];
		return $result;
    }
	
}
