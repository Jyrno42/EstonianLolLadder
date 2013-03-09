<?php

/**
 *
 * @author TH3F0X
 *        
 */
abstract class AuthenticationBase
{
	private $oldH = "";
	private $newH = "";
	
	public function GetHash($id)
	{
		return $id == 0 ? $this->oldH : $this->newH;
	}
	
	protected function start_secure()
	{
		$this->init();
		
		$this->oldH = $this->calculateHash();
		if($this->fromStorage("ohash") !== null)
		{
			if($this->oldH != $this->fromStorage("ohash"))
			{
				//$this->invalidate();
			}
		}
		
		//$this->regenerate();	
		$this->newH = $this->calculateHash();
		$this->toStorage("ohash", $this->newH);
	}
	
	public abstract function invalidate();
	protected abstract function fromStorage($key);
	protected abstract function toStorage($key, $val);
	protected abstract function regenerate();
	protected abstract function init();
	protected abstract function calculateHash();
}

class Authentication extends AuthenticationBase
{	
	protected static $_instance = null;
	
	private function Authentication()
	{
		$this->start_secure();
	}
	
	public static function instance()
	{
		if(self::$_instance == null)
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function invalidate()
	{
		session_destroy();
	}
	
	protected function fromStorage($key)
	{
		return isset($_SESSION[$key])?$_SESSION[$key]:null;
	}
	
	protected function toStorage($key, $val)
	{
		$_SESSION[$key] = $val;
	}
	
	protected function regenerate()
	{
		session_regenerate_id(true);
	}
	
	protected function init()
	{
		ini_set('session.use_trans_sid', 1);
		ini_set('session.use_only_cookies', 1);
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], IS_HTTPS, true);
		session_name(SESSION_NAME);
		session_start();
	}
	
	protected function calculateHash()
	{
		return hash('sha512', SESSION_SALT . session_id() . SESSION_SALT .  $_SERVER["REMOTE_ADDR"] . SESSION_SALT .  $_SERVER["HTTP_USER_AGENT"] . SESSION_SALT);
	}
}

?>