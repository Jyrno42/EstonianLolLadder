<?php

class MemCacheEngine extends BaseCache
{
	private $connection = NULL;

	public function MemCacheEngine()
	{
		global $Memcache_Config;
		$this->connection = new Memcache;
		
		if(!constant("MEMCACHE") || !is_array($Memcache_Config))
		{
			throw new Exception("Memcache server pool not configured");
		}
		
		foreach($Memcache_Config as $server)
		{
			$this->connection->connect($server[0], $server[1]);
		}
	}
	
	public function get($key)
	{
		return $this->connection->get($key);
	}
	public function set($key, $value, $expire)
	{
		return $this->connection->set($key, $value, 0, $expire);
	}
	public function delete($key)
	{
		return $this->connection->delete($key); 
	}
	public function clear()
	{
		return $this->connection->flush(); 
	}
}