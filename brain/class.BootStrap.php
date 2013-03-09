<?php

//require_once("brain/class.Cache.php");

/**
 * Require libs.
 */

require_once("./libs/All.php");

class BootStrap
{
	/**
	 * The templateengine.
	 * 
	 * @var Smarty
	 */
	public $Smarty = null;
	
	/**
	 * The usermanager. 
	 * 
	 * @var UserManager
	 */
	public $UserManager = null;
	
	/**
	 * 
	 * @var TourneyManager
	 */
	public $Datamanager = null;
	
	/**
	 *
	 * @var BaseCache
	 */
	public $Cache = null;
	
	/**
	 * The constructor for this class.
	 */
	public function BootStrap()
	{
        if(!constant("CLI"))
        {
            Authentication::instance();
            ob_start();
        }
		
		$this->Smarty = new Smarty();
		$this->Smarty->setCaching(Smarty::CACHING_OFF);
		
		$this->Cache = new MemCacheEngine();
		
		$this->Smarty->Assign("SITE_NAME", SITE_NAME);
		$this->Smarty->Assign("SITE_URL", SITE_HOST);
		$this->Smarty->Assign("MEDIA_URL", sprintf("%s/media", SITE_HOST));
		
		$this->Smarty->Assign("page", "");
	}
	
	public static function smart_implode($array, $glue=", ", $callback=null, $extra=null)
	{
		if(!is_array($array))
		{
			return $array;
		}
		else
		{
			$ret = "";
			foreach($array as $k => $v)
			{
				end($array);
				
				$part = ($callback !== null ? $callback($k, $v, $k === key($array), $extra, $glue) : $v);
				$ret .= $part ? $part . ($k !== key($array) ? $glue : "") : "";
			}
			return $ret;
		}
	}
	
	public function Strap()
	{	
		if(!constant("IS_INSTALLED"))
		{
			$this->DeployConfig->HandleInstall();
			$this->Smarty->display("install.tpl");
			
			die("INSTALLPLX!");
		}
		else
		{
			$this->Datamanager = new DataManager(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
			
            if(!constant("CLI"))
            {
                $this->UserManager = new UserManager($this->Datamanager);
                $this->Smarty->Assign("USER", $this->UserManager);
            }
		}
	}
	
	public function Detach()
	{
		if($this->UserManager)
			$this->UserManager->__destruct();
		unset($this->Datamanager);
	}
}

