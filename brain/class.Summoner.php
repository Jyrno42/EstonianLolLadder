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
    
    public $Tracker;
    
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
            
            self::$Fields->Tracker = new BooleanField("Tracker");
        }
        return self::$Fields;
    }
    
    public function GetChampions()
    {
        $champs = json_decode($this->Champions);
        if($champs)
        {
            return smart_implode($champs, ", ", function ($k, $v, $last, $db) {
            
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
        
        if ($this->Tier == 6) {
            return "";
        }
        
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

    
    function get_team($teamId)
    {
        return ($teamId / 100) - 1;
    }
    function other_team($teamId)
    {
        return $teamId == 1 ? 0 : 1;
    }
    
    private function GetPlayer($name, $data, $api, $gameId, &$nodata)
    {
        if (!isset($data->statistics))
        {
            $summoner = $api->getSummonerByName(trim($name));
            if ($summoner && isset($summoner->data))
            {
                $info = $api->getRecentGames($summoner->data->acctId);
                if ($info && isset($info->data))
                {
                    $Games = array_reverse($info->data->gameStatistics);
                    foreach($Games as $v)
                    {
                        if ($v->gameId == $gameId)
                        {
                            $data = $v;
                            break;
                        }
                    }
                }
            }
            $nodata = $nodata || !isset($data->statistics);
        }
        
        $p = new Player;
        $p->ID = $data->summonerId;
        $p->Name = $name;
        $p->Champion = $data->championId;
        $p->Spell1 = isset($data->spell1) ? $data->spell1 : null;
        $p->Spell2 = isset($data->spell2) ? $data->spell2 : null;
        
        $p->Stats = isset($data->statistics) ? $data->statistics : null;
        
        return $p;
    }
    
    public function Tracker($api, $DB)
    {
        @ini_set("max_execution_time", 60000);
        if (!$this->Tracker)
        {
            $RecentGames = $api->getRecentGames($this->AID);
            if ($RecentGames)
            {
                $Games = array_reverse($RecentGames->data->gameStatistics);
                
                foreach($Games as $v)
                {
                    if ($v->queueType == "RANKED_SOLO_5x5")
                    {
                        $game = new Game;
                        $game->ID = $v->gameId;
                        $game->Myself = $this->AID;
                        $game->Region = $this->Region;
                        $game->UnknownData = false;
                        
                        $mat = array();
                        if (preg_match("/(\d+)/", $v->createDate, $mat) == 1)
                        {
                            $game->GameDate = floor(bigintval(trim($mat[1])) / 1000);
                        }
                        
                        // If game exists then skip this.
                        $Games = Game::objects($DB)->filter(array("ID" => $game->ID))->get(1);
                        if ($Games)
                        {
                            continue;
                        }
                        
                        $m_team = $v->teamId;
                        $o_team = $m_team == TEAM_BLUE ? TEAM_PURPLE : TEAM_BLUE;
                        
                        $game->Winner = $o_team;
                        foreach($v->statistics as $v2)
                        {
                            if ($v2->statType == "WIN" && $v2->value == 1)
                            {
                                $game->Winner = $m_team;
                            }
                        }
                        
                        $game->Teams[$m_team] = new Team;
                        $game->Teams[$m_team]->ID = $m_team;
                        
                        $game->Teams[$o_team] = new Team;
                        $game->Teams[$o_team]->ID = $o_team;
                        
                        // Get Myself
                        $game->Teams[$m_team]->Players[] = $this->GetPlayer($this->Name, $v, $api, $game->ID, $game->UnknownData);
                        
                        // Get fellowplayers
                        foreach($v->fellowPlayers as $v2)
                        {
                            $game->Teams[$v2->teamId]->Players[] = $this->GetPlayer($v2->summonerName, $v2, $api, $game->ID, $game->UnknownData);
                        }
                        
                        $game->WonGame = $game->Winner == $m_team;
                        Game::save($game, $DB);
                    }
                }
            }
            return true;
        }
        return false;
    }
    
    protected static function class_name()
    {
        return __CLASS__; 
    }
}
