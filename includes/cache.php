<?php

class Cache {

	protected $redis;
	
	protected $is_connected = false;
	
	private $cacheconfig = [
		  'host' 	  => '127.0.0.1',
		  'port'	  => '6379',
		  'expire'	  => '86400',
	];

	public function __construct() {
		if ($this->is_connected==FALSE)
		{
			$this->connect();
		}			
	}

	public function __destruct() {
		$this->close();
	}

	public function connect() {
		if (extension_loaded('redis') && class_exists('Redis'))
		{			
			$this->redis = new Redis();
		}
		else
		{
			return false;
		}		
		$this->redis->connect($this->cacheconfig['host'], $this->cacheconfig['port']);
		$this->is_connected = true;
		return true;
	}

	public function set($key, $value, $ttl=86400) {
		if (!$this->is_connected)
			return false;
		
		if($ttl>0){
			return $this->redis->setEx($key, $ttl, json_encode($value, JSON_UNESCAPED_UNICODE));
		}else{
			return $this->redis->set($key, json_encode($value, JSON_UNESCAPED_UNICODE));
		}
	}

	public function get($key) {
		if (!$this->is_connected)
			return false;
		return json_decode($this->redis->get($key), TRUE);
	}
	
	public function incr($key) {
		if (!$this->is_connected)
			return false;		
		return $this->redis->incr($key);
	}

	public function expire($key, $ttl = 86400) {
		if (!$this->is_connected)
			return false;
		
		return $this->redis->expire($key, $ttl);
	}
	
	public function exists($key) {		
		if (!$this->is_connected)
			return false;
		
		return $this->redis->exists($key);
	}

	public function delete($key) {
		if (!$this->is_connected)
			return false;

		return $this->redis->delete($key);
	}

	public function flush() {
		if (!$this->is_connected)
			return false;

		return $this->redis->flush();
	}

	public function close() {
		if (!$this->is_connected)
			return false;

		return $this->redis->close();
	}
	
}
