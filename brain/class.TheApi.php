<?php

function relative_time($timestamp)
{
    if(!$timestamp)
        return "ammu";
    
    $difference = time() - $timestamp;
    $periods = array("sekund", "minut", "tund", "päev", "nädal", "kuu", "aasta", "dekaad");
    $periods2 = array("sekundit", "minutit", "tundi", "päeva", "nädalat", "kuud", "aastat", "dekaadi");
    $lengths = array("60","60","24","7","4.35","12","10");
    if ($difference > 0)
    {
        $ending = "tagasi";
    }
    else
    {
        $difference = -$difference;
        $ending = "pärast";
    }
    $j = 0;
    for($j = 0; $difference >= $lengths[$j]; $j++)
        $difference /= $lengths[$j];
    
    $difference = round($difference);
    return sprintf("%s %s %s", $difference, $difference != 1 ? $periods2[$j] : $periods[$j], $ending);
}

ini_set('default_charset', 'UTF-8');

function print_cli($str)
{
    if(constant("CLI"))
        print "$str";
}

function print_cli_line($str)
{
    print_cli("$str\n");
}

function print_cli_progressbar($current, $target)
{
    if(constant("CLI"))
    {
        $bar_size = floor(56*$current/$target);
        $bar = str_pad(str_repeat("=", $bar_size), 56, " ", STR_PAD_RIGHT);
        print sprintf("Updating %03d/%03d (%s) %03d%%\r", $current, $target, $bar, $current/$target*100);
    }
}
    
class TheApi extends API
{
    /**
     * The bootstrap we use.
     * @var BootStrap
     */
    private $Init = null;
    
    private $Elophant = null;
    
    public function TheApi()
    {
        $this->Elophant = new Elophant(array("apiKey" => "", "lolServer" => "euw"));
    
        $this->AddAction("RunCrons", array($this, "RunCrons"));
        $this->AddAction("render", array($this, "Render"));
        
        $this->AddAction("Login", array($this, "ShowLogin"));
        $this->AddAction("Login2", array($this, "Login"));
        $this->AddAction("Manage", array($this, "Manage"));
        $this->AddAction("GenerateUpdator", array($this, "GenerateUpdator"));
        
        $this->AddAction("AddSummoner", array($this, "AddSummoner"));
        $this->AddAction("test", array($this, "test"));
        
        $this->AddAction("migrate", array($this, "migrate"));
    }
    
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
    
    public function GenerateUpdator()
    {
        if(!$this->Init->UserManager->Can("unused_13"))
            throw new NotAuthorizedException();
        
        $Workers = array();
        
        $Summoners = Summoner::objects($this->Init->Datamanager)->all()->orderby(array("AID"))->reverse()->get();
        
        $chunk = 10;
        for($i = 0; $i < sizeof($Summoners) + $chunk; $i += $chunk)
        {
            $o = new stdClass;
            $o->start = $i;
            $o->amount = $chunk;
            $Workers[] = $o;
        }
        
        $this->Init->Smarty->assign("DIR", getcwd() . DIRECTORY_SEPARATOR);
        $this->Init->Smarty->assign("LOGDIR", dirname(getcwd()) . DIRECTORY_SEPARATOR . "script" . DIRECTORY_SEPARATOR);
        $this->Init->Smarty->assign("DATE", date("c"));
        $this->Init->Smarty->assign("Workers", $Workers);
        
        $content = $this->Init->Smarty->fetch("update.tpl");
        
        file_put_contents(getcwd() . DIRECTORY_SEPARATOR . "update.sh", $content);
        
        print "<pre>$content</pre>";
    }
    
    private function TryAddSummoner($theSummoner, $region, $name)
    {
        $s = new Summoner();
        $s->AID = $theSummoner->acctId;
        $s->Region = $region;
        $s->SID = $theSummoner->summonerId;
        $s->Name = $theSummoner->name;
        
        try 
        {
            Summoner::save($s, $this->Init->Datamanager);
            return array("success", sprintf("Summoner %s added to region %s!", $name, strtoupper($region)));
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }
    
    static $filters = array("euw", "eune");
    static $showtop = array("top", "all");
    
    function array_cartesian_product($arrays)
    {
        $result = array();
        $arrays = array_values($arrays);
        $sizeIn = sizeof($arrays);
        $size = $sizeIn > 0 ? 1 : 0;
        foreach ($arrays as $array)
            $size = $size * sizeof($array);
        for ($i = 0; $i < $size; $i ++)
        {
            $result[$i] = array();
            for ($j = 0; $j < $sizeIn; $j ++)
                array_push($result[$i], current($arrays[$j]));
            for ($j = ($sizeIn -1); $j >= 0; $j --)
            {
                if (next($arrays[$j]))
                    break;
                elseif (isset ($arrays[$j]))
                    reset($arrays[$j]);
            }
        }
        return $result;
    }
    
    public function ClearCache()
    {       
        $this->Init->Smarty->clearAllCache();
    }

    public function Render()
    {
        $Summoners = array();
        $filter = ApiHelper::GetParam("filter", false, null, false);
        $showtop = ApiHelper::GetParam("showtop", false, null, false);
        $more = ApiHelper::GetParam("more", false, null, false);
        
        $tplName = $showtop ? "show_5.tpl" : "show_all.tpl";
        
        $filter = array_search($filter, self::$filters) !== FALSE ? $filter : null;
        
        if(true || ApiHelper::GetParam("inval", false))
        {
            $this->ClearCache();
        }
        else 
        {
            $this->Init->Smarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
            $this->Init->Smarty->setCompileCheck(false);
        }
        
        $key = sprintf("ren_%s_%s_%s", $filter, $showtop ? self::$showtop[0] : self::$showtop[1], $more == true ? "y" : "n");
        if(!$this->Init->Smarty->isCached($tplName, $key))
        {
            if($filter !== null)
            {
                $Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Region" => $filter, "Name" => QueryObject::NotEqual(null)))->orderby(array("Score"))->reverse()->get($showtop ? 5 : null);
            }
            else
            {
                $Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => QueryObject::NotEqual(null)))->orderby(array("Score"))->reverse()->get($showtop ? 5 : null);
            }
            
            $upd = $this->Init->Cache->Get("updated");
            $updatelog = $this->Init->Cache->Get("updatelog");
            
            $this->Init->Smarty->assign("Label", $filter ? strtoupper($filter) : "EESTI");
            $this->Init->Smarty->assign("Summoners", $Summoners);
            $this->Init->Smarty->assign("Filter", $filter);
            $this->Init->Smarty->assign("More", $more == true);
            $upd = $this->Init->Cache->Get("updated");
            $updatelog = $this->Init->Cache->Get("updatelog");
            $this->Init->Smarty->assign("Update", $upd);
            $this->Init->Smarty->assign("UpdateLog", $updatelog);
        }
        
        $this->Init->Smarty->setCacheLifetime(300);
        $this->Init->Smarty->display($tplName, $key);
    }
    
    private function GetAPI(&$Elophant, $region)
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

    function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
    
    public function migrate()
    {
        //if(!$this->Init->UserManager->Can("unused_13"))
        //    throw new NotAuthorizedException();
        
        define("MIGRATE", true);
        
        $dirs = scandir("brain");
        foreach($dirs as $v)
        {
            if($v == "." || $v == "..")
                continue;
            
            if (!$this->startsWith($v, "class.") || !$this->endsWith($v, ".php"))
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
        $this->GetAPI($this->Elophant, "euw");
        $Summoner  = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => "AD Kringel"))->get(1);
        
        //var_dump($this->Elophant->getPlayerStats($Summoner->AID, "current")); // Status: 
        //var_dump($this->Elophant->GetMostPlayedChampions($Summoner->AID)); // Status: 
        //var_dump($this->Elophant->getCombinedRankedStatistics($Summoner->AID)); // Status:
        //var_dump($this->Elophant->getLeagues($Summoner->SID)); // Status:
        
        $changed = $Summoner->Update($this->Elophant);
        Summoner::save($Summoner, $this->Init->Datamanager);
        var_dump($changed);
        var_dump($Summoner);
    }
    
    public function RunCrons()
    {
        $this->ClearCache();
        print_cli_line("Starting RunCrons");
        $start_time = microtime(true);
        
        @ini_set("max_execution_time", 60000);
        
        $filter = ApiHelper::GetParam("name", false, null, false);
        
        if($filter !== false)
        {
            $Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => $filter))->orderby(array("AID"))->reverse()->get();
        }
        else 
        {
            $Summoners = Summoner::objects($this->Init->Datamanager)->all()->orderby(array("AID"))->reverse()->get();
        }
        
        if(constant("CLI"))
        {
            global $argv;
            $Summoners = array_slice($Summoners, $argv[1], $argv[2]);
        }
        
        //$Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Name" => "TH3F0X"))->orderby(array("ELO"))->reverse()->get();
        //$Summoners = Summoner::objects($this->Init->Datamanager)->filter(array("Champions" => ""))->orderby(array("ELO"))->reverse()->get();
        //$Summoners = Summoner::objects($this->Init->Datamanager)->all()->orderby(array("ELO"))->reverse()->get();
        
        print_cli_line("");
        print_cli_line(sprintf("Got %d summoners from DB to check for updates!", sizeof($Summoners)));
        print_cli_line("");
        
        print_cli_line("###############################################################################");
        print_cli_line("#####                           Starting update                           #####");
        print_cli_line("###############################################################################");
        print_cli_line("");
        print_cli_line("");
        
        $n = 0;
        $problem = "";
        try
        {
            print_cli("Checking if elophant is online");
            $APITYPE = "Elophant.com";
            $this->GetAPI($this->Elophant, "euw");
            print_cli_line("\t\t\t\t\t[SUCCESS]");
                        
            foreach($Summoners as $k => $Summoner)
            {
                print_cli_progressbar($k+1, sizeof($Summoners));
                
                try
                {
                    $this->GetAPI($this->Elophant, $Summoner->Region);
                }
                catch(Exception $e)
                {
                    continue;
                }
                $api = $this->Elophant;
                $changed = false;
                
                if($Summoner->SID == 0)
                {
                    continue;
                }
                
                if(!$api->getPlayerStats($Summoner->AID, "CURRENT"))
                {
                    $problem = $Summoner->Region . " Api seems to be down!";
                    continue;
                }
                
                $changed = $Summoner->Update($api);
                
                // Save
                if($changed)
                {
                    Summoner::save($Summoner, $this->Init->Datamanager);
                    $this->ClearCache();
                    $n++;
                }
            }
        }
        catch(Exception $e)
        {
            print $e->getMessage();
            /*
            $APITYPE = "Lolking.com";
            print_cli_line("\t\t\t\t\t[FAIL]");
            $problem = "Using Lolking.com as fallback since Elophant API is down!";
            print_cli_line($problem . "\t[SUCCESS]");
            
            foreach($Summoners as $k => $Summoner)
            {
                print_cli_progressbar($k+1, sizeof($Summoners));
            
                $changed = false;
                $url = "http://www.lolking.net/summoner/%s/%d";
                
                $str = file_get_contents(sprintf($url, $Summoner->Region, $Summoner->SID));
                $body = substr($str, strpos($str, '<div class="summoner_titlebar" style="position: relative;">'), strpos($str, '<div style="float: left; width: 300px; margin-right: 0px;">'));
                $body = str_replace("\t", "", $body);
                $body = str_replace("  ", " ", $body);
                $body = str_replace("\r", "", $body);
                $body = str_replace("\n", "", $body);
                
                // TODO: Rewrite this to use regex instead of dom parsing so it will be less time/memory/cpu consuming.
                $html = str_get_html($body);
                if(!$html)
                    continue;
                foreach($html->find(".featured") as $li)
                {
                    $heading = $li->find(".personal_ratings_heading", 0);
                    if(!$heading || $heading->plaintext != "Solo 5v5")
                        continue;
                    
                    foreach($li->find("div") as $div)
                    {
                        if(strpos($div->plaintext, "Rating") !== FALSE)
                        {
                            $elo = trim($div->find("span", 0)->plaintext);
                            if($elo && is_numeric($elo) && $elo > 0 && $Summoner->ELO != $elo)
                            {
                                $changed = true;
                                $Summoner->ELO = $elo;
                            }
                        }
                        if(strpos($div->plaintext, "Wins") !== FALSE)
                        {
                            $data = trim($div->find("span", 0)->plaintext);
                            if($data && is_numeric($data) && $data > 0 && $Summoner->WON != $data)
                            {
                                $changed = true;
                                $Summoner->WON = $data;
                            }
                        }
                        if(strpos($div->plaintext, "Losses") !== FALSE)
                        {
                            $data = trim($div->find("span", 0)->plaintext);
                            if($data && is_numeric($data) && $data > 0 && $Summoner->LOST != $data)
                            {
                                $changed = true;
                                $Summoner->LOST = $data;
                            }
                        }
                    }
                }
                
                $html->clear();
                
                // Save
                if($changed)
                {
                    Summoner::save($Summoner, $this->Init->Datamanager);
                    $this->ClearCache();
                    $n++;
                }
            }
            */
            print "WETF";
        }
        
        print_cli_line(sprintf("Finished updating, modified %d summoners in %f s", $n, microtime(true) - $start_time));
        if($n > 0)
        {
            $this->Init->Cache->Set("updated", time(), 0);
            $this->Init->Cache->Set("updatelog", sprintf("Updated %d summoners using %s.", $n, $APITYPE), 0);
            $this->ClearCache();
            return array("result" => sprintf("Updated %d summoners.", $n, $APITYPE));
        }
        else
        {
            $this->Init->Cache->Set("updatelog", strlen($problem) > 0 ? $problem : "Nothing to update", 0);
            return array("result" => strlen($problem) > 0 ? $problem : "Nothing to update");
        }
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
    
    private function findStat($stats, $key) 
    {
        foreach($stats as $stat)
        {
            if($stat->statType == $key)
                return $stat->value;
        }
        return 0;
    }
    
    public function SetBootstrap($Init)
    {
        $this->Init = $Init;
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
        $problem = false;
        
        try
        {
            $this->GetAPI($this->Elophant, $region);
            $theSummoner = $this->Elophant->getSummonerByName(utf8_decode(trim($Name)));
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
            
        $this->TryAddSummoner($theSummoner, $region, $Name);
        
        return array("result" => "Success", "summoner" => $theSummoner);
    }
}