<?php

class FileCache extends BaseCache
{
	private $connection = NULL;
	
	public function FileCache()
	{
		$this->connection = new SimpleCache();
	}
	
	public function get($key)
	{
		return $this->connection->isCached($key) ? $this->connection->retrieve($key) : FALSE;
	}
	public function set($key, $value, $expire)
	{
		return $this->connection->store($key, $value, $expire);
	}
	public function delete($key)
	{
		return $this->connection->erase($key); 
	}
	public function clear()
	{
		return $this->connection->eraseAll(); 
	}
}
