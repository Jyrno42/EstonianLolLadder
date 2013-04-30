<?php

ini_set('default_charset', 'UTF-8');

function GetReport()
{
    $ret = NULL;
    $dir = getcwd();
    chdir($dir . "/reports");
    
    $files = scandir(".");

    $files = array_filter($files, function ($v) {
        return $v != "." && $v != "..";
    });
    
    if (sizeof($files) > 0)
    {
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $txt = file_get_contents($files[0]);
        if ($txt)
        {
            $ret = json_decode($txt);
            $ret->time = filemtime($files[0]);
            
            if (sizeof($ret->timers) > 0)
            {
                $ret->calcTimers = new stdClass;
                foreach($ret->timers as $k => $v)
                {
                    $line = trim($v); // 131.210105s wall, 112.632722s user + 0.218401s system = 112.851123s CPU (86.0%)
                    $mat = array();
                    if (preg_match("/([\.0-9]+)s wall/", $line, $mat) === 1)
                    {
                        $ret->calcTimers->$k = floatval($mat[1]);
                    }
                }
                foreach($ret->threads as $k => $v)
                {
                    $line = trim($v->time); // 131.210105s wall, 112.632722s user + 0.218401s system = 112.851123s CPU (86.0%)
                    $mat = array();
                    if (preg_match("/([\.0-9]+)s wall/", $line, $mat) === 1)
                    {
                        $ret->threads->$k->pTime = floatval($mat[1]);
                    }
                }
                
                $ret->calcTimers->threads = $ret->calcTimers->total - $ret->calcTimers->chunk - $ret->calcTimers->init - $ret->calcTimers->sql_commit - $ret->calcTimers->sql_update;
                unset($ret->threads->na);
            }
        }
    }
    
    chdir($dir);
    return $ret;
}

class TheApi extends API
{
    /**
     * The bootstrap we use.
     * @var BootStrap
     */
    private $Init = null;
    
    private $Elophant = null;
    
    static $filters = array("euw", "eune");
    static $showtop = array("top", "all");
    
    public function TheApi()
    {
        $this->Elophant = new Elophant(array("apiKey" => "", "lolServer" => "euw"));
    
        $this->AddAction("render", array($this, "Render"));
        
        $this->AddAction("Login", array($this, "ShowLogin"));
        $this->AddAction("Login2", array($this, "Login"));
        $this->AddAction("Manage", array($this, "Manage"));
        $this->AddAction("Tracker", array($this, "Tracker"));
        
        $this->AddAction("AddSummoner", array($this, "AddSummoner"));
        $this->AddAction("test", array($this, "test"));
        
        $this->AddAction("names", array($this, "names"));
        
        $this->AddAction("migrate", array($this, "migrate"));
		
        $this->AddAction("Report", array($this, "Report"));
    }
    
    /**
      * Utility Stuff
      */
    public function SetBootstrap($Init)
    {
        $this->Init = $Init;
    }
    
    private function TryAddSummoner($theSummoner, $region, $name, $api)
    {
        $s = new Summoner();
        $s->AID = $theSummoner->acctId;
        $s->Region = $region;
        $s->SID = $theSummoner->summonerId;
        $s->Name = $theSummoner->name;
        
        try 
        {
            $result = Summoner::objects($this->Init->Datamanager)->filter(array("AID" => $s->AID))->get(1);
            if ($result) {
                throw new Exception(sprintf("Summoner %s added already.", $name));
            }
            
            $s->Update($api);
            Summoner::save($s, $this->Init->Datamanager);
            return array("success", sprintf("Summoner %s added to region %s!", $name, strtoupper($region)));
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }
    
    public function ClearCache()
    {
        $this->Init->Smarty->clearAllCache();
    }
    
    public static function GetAPI(&$Elophant, $region)
    {
        // */30 * * * * php -f /home/ec2-user/www/API.php
        $keys = array(
            "t8YPX5AJShzN5tmCtKfe",
            "2sh32goq8UT5sdQSPO2v",
            "teAa9uWN7ZVAQrAFTvJ3",
            "0QDhbHL6Bts2TSCDJeR7",
            "ZVJzQjanJp5biXMbAgiq",
        );
        
        while(true)
        {
            $key = next($keys);
            if(!$key)
                break;

            $Elophant->setApiKey($key); // Selected key
            $Elophant->setLolServer($region);
            
            // Verify that api is online...
            if(!$Elophant->getStatus())
            {
                throw new Exception("Elophant API is Down!");
            }
            
            if($Elophant->Remaining < 30)
            {
                continue;
            }
            return true;
        }
        
        throw new Exception("Out of API keys!");
    }
    
    public function GetName($aid, $region)
    {
        $cached = $this->Init->Cache->Get(sprintf("lol_SummonerName_%s_%s", $aid, $region));
        if(!$cached)
        {
            $Summoner = Summoner::objects($this->Init->Datamanager)->filter(array("AID" => $aid))->get(1);
            if ($Summoner)
            {
                $cached = $Summoner->Name;
            }
            else
            {
                //self::GetAPI($this->Elophant, $region);
                throw new Exception("NOTFOUND");
            }
            
            $this->Init->Cache->Set(sprintf("lol_SummonerName_%s_%s", $aid, $region), $cached, 86400);
        }
        return $cached;
    }
    
    private function ChampionName($id, $api)
    {
        $cached = unserialize($this->Init->Cache->Get("lol_ChampionName"));
        if(!$cached)
        {
            $ret = $api->getChampions();
            $cached = array();
            foreach($ret->data as $champ)
            {
                $cached[$champ->id] = $champ->name;
            }
            $this->Init->Cache->Set("lol_ChampionName", serialize($cached), 86400);
        }
        return isset($cached[$id]) ? $cached[$id] : "-";
    }
    
    /**
      * Views
      */
    public function ShowLogin()
    {
        if($this->Init->UserManager->Can("unused_13"))
        {
            $_GET["redirect"] = "API.php?action=Manage";
            return true;
        }
        $this->Init->Smarty->display("login.tpl");
    }
    
    public function Manage()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();
        
        $this->Init->Smarty->display("manage.tpl");
    }
    
    public function Tracker()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();
        
        $me = 25924518;
        $Games = Game::objects($this->Init->Datamanager)->filter(array("Myself" => $me))->orderby(array("GameDate"))->reverse()->get();
        if ($Games)
        {
            foreach($Games as $k => $v)
            {
                $v->Api = $this;
            }
        }
        
        $this->Init->Smarty->assign("Games", $Games);
        
        $this->Init->Smarty->display("tracker.html");
    }

    public function Render()
    {
        $Summoners = array();
        $filter = ApiHelper::GetParam("filter", false, null, false);
        $showtop = ApiHelper::GetParam("showtop", false, null, false);
        
        $tplName = $showtop ? "show_5.tpl" : "show_all.tpl";
        $filter = array_search($filter, self::$filters) !== FALSE ? $filter : null;
        
        if($filter !== null)
        {
            $Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Region" => $filter, "Name" => QueryObject::NotEqual(null)))->orderby(array("Score"))->reverse()->get($showtop ? 5 : null);
        }
        else
        {
            $Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => QueryObject::NotEqual(null)))->orderby(array("Score"))->reverse()->get($showtop ? 5 : null);
        }

        // Get report
        $report = GetReport();
        $this->Init->Smarty->assign("Update", $report !== NULL ? $report->time : 0);
        $this->Init->Smarty->assign("Report", $report);
        
        $this->Init->Smarty->assign("Label", $filter ? strtoupper($filter) : "EESTI");
        $this->Init->Smarty->assign("Summoners", $Summoners);
        $this->Init->Smarty->assign("Filter", $filter);
        $this->Init->Smarty->display($tplName);
    }
    
    public function migrate()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();
        
        define("MIGRATE", true);
        
        $dirs = scandir("brain");
        foreach($dirs as $v)
        {
            if($v == "." || $v == "..")
                continue;
            
            if (!startsWith($v, "class.") || !endsWith($v, ".php"))
                continue;
            require_once($v);
        }
        
        $classes = get_declared_classes();
        $classes = array_filter($classes, function ($cl) {
            return get_parent_class($cl) == "Models";
        });
        
        foreach($classes as $k => $v)
        {
            $classes[$k] = $v::objects($this->Init->Datamanager)->all()->get();
        }
        
    }
    
    public function test()
    {
        self::GetAPI($this->Elophant, "euw");
        $Summoner  = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => "TH3F0X"))->get(1);

        //$Games = Game::objects($this->Init->Datamanager)->all()->get();
        //var_dump($Games);
        
        $ret = $Summoner->Tracker($this->Elophant, $this->Init->Datamanager);
        //var_dump($ret);
    }
    
    public function names()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();
        
        $dir = "C:\Dropbox\Serverid\www\LOL\media\lol";
        $dir2 = "C:\Dropbox\Serverid\www\LOL\media\champions";
        
        self::GetAPI($this->Elophant, "euw");
        
        $champs = $this->Elophant->getChampions();
        $nchamps = array();
        
        foreach($champs->data as $k => $v)
        {
            $name = $v->name;
            $name = str_replace(".", "", $name);
            $name = str_replace(" ", "", $name);
            $name = str_replace("'", "", $name);
            $name = strtolower($name);
            $nchamps[$name] = $v->id;
        }
        $files = scandir($dir);
        foreach($files as $v)
        {
            if ($v == "." || $v == "..")
                continue;
            
            $cName = str_replace("_Square_0.png", "", $v);
            $cName = str_replace("_square_0.png", "", $cName);
            $cName = strtolower($cName);
            
            if (isset($nchamps[$cName]))
            {
                $f = file_get_contents($dir . '\\' . $v);
                file_put_contents($dir2 . "\\" . $nchamps[$cName] . ".png", $f);
            }
            else
            {
                print sprintf("Didn't copy: %s<br>", $v);
            }
        }
        //var_dump($nchamps);
        //var_dump($champs);
    }
    
    public function Logout()
    {
        $this->Init->UserManager->Logout();
        return array("result" => "Success");
    }
    
    public function Login()
    {
        $email = ApiHelper::GetParam("login_args_email", true);
        $pass = ApiHelper::GetParam("login_args_password", true);
        
        if(strlen($pass) != 128)
            $pass = hash('sha512', $pass);
        
        $pass = hash('sha512', PASSWORD_SALT . $pass . PASSWORD_SALT);
        
        if(($user = $this->Init->UserManager->CheckForRegistration($email, $pass)) === null)
            throw new Exception(_("Wrong email or password!"));
        
        $this->Init->UserManager->Login($user->UserID, true);
        return array("result" => "Success");
    }
    
    public function AddSummoner()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();

        $region = strtolower(ApiHelper::GetParam("region", true));
        $Name = urldecode(ApiHelper::GetParam("name", true));
    
        $theSummoner = null;
        $api = null;
        $problem = false;
        
        try
        {
            $api = $this->Elophant;
            self::GetAPI($api, $region);
            $theSummoner = $api->getSummonerByName(utf8_decode(trim($Name)));
            $theSummoner = $theSummoner->data;
        }
        catch(Exception $e)
        {
            //throw $e;
            $url = sprintf("http://www.lolking.net/search?name=%s&x=0&y=0", str_replace(" ", "+", $Name));
            
            $str = file_get_contents($url);
            $mat = array();
            
            $part = sprintf("summoner\/%s\/([\d]+)\">%s<\/a>", $region, $Name);
            $regex = sprintf("/%s/i", $part);
            $res = preg_match($regex, $str, $mat);
            if($res > 0)
            {
                $theSummoner = new stdClass;
                $theSummoner->acctId = $mat[1];
                $theSummoner->summonerId = $mat[1];
                $theSummoner->name = $Name;
            }
        }
        if($theSummoner === null)
            throw new Exception(sprintf("Summoner %s not found in region %s", $Name, strtoupper($region)));
            
        $this->TryAddSummoner($theSummoner, $region, $Name, $api);
        
        return array("result" => "Success", "summoner" => $theSummoner);
    }
	
	public function Report()
	{
        $key = ApiHelper::GetParam("key", true);
		
		if(strcmp($key, REPORT_PASSCODE) !== 0)
            throw new Exception("Wrong passcode!");
			
		$dir = getcwd();
		chdir($dir . "/reports");
		
		$report = file_get_contents("php://input");
		$fName = sprintf("report_%d.json", time());
		file_put_contents($fName, $report);
		
		chdir($dir);
        return array("result" => "Success", "file" => $fName);
	}
}
