<?php

class Summoner extends Models 
{
    public $AID;
    public $SID;
    
    public $Region;
    public $Name;
    
    public $WON;
    public $LOST;
    
    public $Kills;
    public $Deaths;
    public $Assists;
    
    public $MaxChampionKills;
    public $MinionKills;
    public $QuadraKills;
    public $PentaKills;
    
    public $Tier;
    public $League;
    public $Rank;
    public $LeaguePoints;
    
    public $HotStreak;
    public $FreshBlood;
    public $Veteran;
    
    public $Score;
    
    public $Modified;
    
    private static $Fields = null;
    public static function ModelFields()
    {
        if(!self::$Fields)
        {
            self::$Fields = (object)null;
            
            self::$Fields->AID = new PrimaryKey("AID", 11);
            self::$Fields->SID = new IntField("SID", 11);
            
            self::$Fields->Region = new TextField("Region", 3);
            self::$Fields->Name = new TextField("Name", 32);
            
            self::$Fields->WON = new IntField("WON", 11);
            self::$Fields->LOST = new IntField("LOST", 11);
            
            self::$Fields->Kills = new IntField("Kills", 11);
            self::$Fields->Deaths = new IntField("Deaths", 11);
            self::$Fields->Assists = new IntField("Assists", 11);
            
            self::$Fields->MaxChampionKills = new IntField("MaxChampionKills", 11);
            self::$Fields->MinionKills = new IntField("MinionKills", 11);
            self::$Fields->QuadraKills = new IntField("QuadraKills", 11);
            self::$Fields->PentaKills = new IntField("PentaKills", 11);
            
            self::$Fields->Tier = new TextField("Tier", 12);
            self::$Fields->League = new TextField("League", 64);
            self::$Fields->Rank = new IntField("Rank", 11);
            self::$Fields->LeaguePoints = new IntField("LeaguePoints", 11);
            
            self::$Fields->HotStreak = new BooleanField("HotStreak");
            self::$Fields->FreshBlood = new BooleanField("FreshBlood");
            self::$Fields->Veteran = new BooleanField("Veteran");
            
            self::$Fields->Score = new IntField("Score", 11);
            
            self::$Fields->Modified = new IntField("Modified", 11);
        }
        return self::$Fields;
    }
    
    public function GetChampions()
    {
        $champs = json_decode($this->Champions);
        if($champs)
        {
            return BootStrap::smart_implode($champs, ", ", function ($k, $v, $last, $db) {
            
                return sprintf("%s (%.1f%%)", $v[0], $v[1]*100);
            
            }, null);
        }
        return "-";
    }
    
    function Update($api)
    {
        $changed = false;
        
        $data = $api->getCombinedRankedStatistics($this->AID);
        $this->safeFieldUpdate($changed, "WON", $data["TOTAL_SESSIONS_WON"], 0);
        $this->safeFieldUpdate($changed, "LOST", intval($data["TOTAL_SESSIONS_PLAYED"]) - intval($data["TOTAL_SESSIONS_WON"]), 0);
        
        $this->safeFieldUpdate($changed, "Kills", $data["TOTAL_CHAMPION_KILLS"], 0);
        $this->safeFieldUpdate($changed, "Deaths", $data["TOTAL_DEATHS_PER_SESSION"], 0);
        $this->safeFieldUpdate($changed, "Assists", $data["TOTAL_ASSISTS"], 0);
        
        $this->safeFieldUpdate($changed, "MaxChampionKills", $data["MOST_CHAMPION_KILLS_PER_SESSION"], 0);
        $this->safeFieldUpdate($changed, "MinionKills", $data["TOTAL_MINION_KILLS"] + $data["TOTAL_NEUTRAL_MINIONS_KILLED"], 0);
        $this->safeFieldUpdate($changed, "QuadraKills", $data["TOTAL_QUADRA_KILLS"], 0);
        $this->safeFieldUpdate($changed, "PentaKills", $data["TOTAL_PENTA_KILLS"], 0);
        
        $leagues = $api->getLeagues($this->SID);
        $ranked = null;
        $me = null;
        if($leagues && $leagues->success)
        {
            $leagues = $leagues->data->summonerLeagues;
            foreach($leagues as $k => $v)
            {
                if($v->queue == "RANKED_SOLO_5x5")
                {
                    $ranked = $v;
                }
            }
            
            if ($ranked) 
            {
                foreach($ranked->entries as $k => $v)
                {
                    // Me
                    if(intval($v->playerOrTeamId) == intval($this->SID))
                    {
                        $me = $v;
                    }
                }
            }
            //var_dump($ranked);
            
            if($me)
            {
                $changed = true;
                $this->Tier = $this->get_tier_from_outername($me->tier);
                $this->League = $ranked->name;
                $this->Rank = $this->get_rank_from_outername($me->rank);
                $this->LeaguePoints = $me->leaguePoints;
                
                $this->HotStreak = $me->hotStreak == true;
                $this->FreshBlood = $me->freshBlood == true;
                $this->Veteran = $me->veteran == true;
            }
        }
        
        $this->safeFieldUpdate($changed, "Score", floor($this->get_estimated_elo()), 0);
        $this->Modified = time();
        return $changed;
    }
    
    public function TierName()
    {
        $arr = array(
            "PLACEMENT",
            "BRONZE",
            "SILVER",
            "GOLD",
            "PLATINUM",
            "DIAMOND",
            "CHALLENGER",
        );
        return isset($arr[$this->Tier]) ? $arr[$this->Tier] : $arr[0];
    }
    public function RankName()
    {
        $arr = array(
            "",
            "I",
            "II",
            "III",
            "IV",
            "V",
        );
        return isset($arr[$this->Rank]) ? $arr[$this->Rank] : $arr[0];
    }
    
    public function get_estimated_elo()
    {
        $ranges = array(
            1 => array(800, 1240), // 800 - 1240: Bronze
            2 => array(1250, 1490), // 1250 - 1490: Silver
            3 => array(1500, 1840), // 1500 - 1840: Gold
            4 => array(1850, 2240), // 1850 - 2240: Plat
            5 => array(2250, 2540), // 2250 - 2540: Diamond
            6 => array(2550, 3000), // 2550++: Challenger
        );
        
        $tier = isset($ranges[$this->Tier]) ? $ranges[$this->Tier] : array(0, 800);
                
        $range = $tier[1] - $tier[0];
        $div = $range / 5;
        
        $rank = 5 - $this->Rank;
        return floor($tier[0] + ($div * $rank) + ($div * ($this->LeaguePoints/100)));
    }
    
    function get_tier_from_outername($name)
    {
        $arr = array(
            "PLACEMENT" => 0,
            "BRONZE" => 1,
            "SILVER" => 2,
            "GOLD" => 3,
            "PLATINUM" => 4,
            "DIAMOND" => 5,
            "CHALLENGER" => 6,
        );
        
        return isset($arr[$name]) ? $arr[$name] : $arr["PLACEMENT"];
    }
    
    function get_rank_from_outername($name)
    {
        $arr = array(
            "UNKNOWN" => 0,
            "I" => 1,
            "II" => 2,
            "III" => 3,
            "IV" => 4,
            "V" => 5,
        );
        
        return isset($arr[$name]) ? $arr[$name] : $arr["UNKNOWN"];
    }
    
    public function safeFieldUpdate(&$changed, $fieldName, $value, $default)
    {
        if($value !== null)
        {
            if($this->$fieldName != $value)
            {
                $changed = true;
                $this->$fieldName = $value;
            }
        }
        else
        {
            $this->$fieldName = $default;
            $changed = true;
        }
    }

    /*
    // Get Champions
    if(true)
    {
        $champions = $api->GetMostPlayedChampions($Summoner->AID);
        if($champions)
        {
            $arr = array();
            foreach($champions as $i => $j)
            {   
                $played = $j->totalGamesPlayed;
                $arr[] = array($this->ChampionName($j->championId, $api), $played);
                if(sizeof($arr) > 3)
                    break;
            }
            $str = json_encode($arr);
            if($Summoner->Champions != $str)
            {
                $changed = true;
                $Summoner->Champions = $str;
            }
        }
    }*/
    
    protected static function class_name()
    {
        return __CLASS__; 
    }
}
