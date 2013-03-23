<?php

define("TEAM_BLUE",   100);
define("TEAM_PURPLE", 200);

class Game extends Models
{
    // Sql fields
    public $ID;
    public $Winner;
    public $WonGame;
    public $Team1;
    public $Team2;
    public $Myself;
    public $Region;
    public $GameDate;
    
    public $UnknownData;
    
    // Calculated fields
    public $Teams = array();
    
    // other
    public $Api = null;
    
    private static $Fields = null;
    public static function ModelFields()
    {
        if(!self::$Fields)
        {
            self::$Fields = (object)null;
            
            self::$Fields->ID = new PrimaryKey("ID", 11);
            
            self::$Fields->Winner = new IntField("Winner", 4);
            self::$Fields->Team1 = new LongTextField("Team1");
            self::$Fields->Team2 = new LongTextField("Team2");
            
            self::$Fields->Myself = new IntField("Myself", 11);
            self::$Fields->Region = new TextField("Region", 11);
            
            self::$Fields->WonGame = new BooleanField("WonGame");
            
            self::$Fields->GameDate = new IntField("GameDate", 22);
            
            self::$Fields->UnknownData = new BooleanField("UnknownData");
        }
        return self::$Fields;
    }
    
    protected static function class_name()
    {
        return __CLASS__; 
    }
    
    public function get_Myself()
    {
        return $this->Api ? $this->Api->GetName($this->Myself, $this->Region) : $this->Myself;
    }
    
    public function TeamScore($teamId)
    {
        if (isset($this->Teams[$teamId]))
        {
            $kills = 0;
            $deaths = 0;
            $assists = 0;
            
            foreach($this->Teams[$teamId]->Players as $v)
            {
                $arr = Player::GetKDA($v);
                $kills += $arr[0];
                $deaths += $arr[1];
                $assists += $arr[2];
            }
            
            return sprintf("%d/%d/%d", $kills, $deaths, $assists);
        }
        return "0/0/0";
    }
    
    public function pre_save()
    {
        $this->Team1 = json_encode($this->Teams[TEAM_BLUE]);
        $this->Team2 = json_encode($this->Teams[TEAM_PURPLE]);
    }
    
    public function post_load()
    {
        $team1 = json_decode($this->Team1);
        $team2 = json_decode($this->Team2);
        
        if ($team1 && $team2)
        {
            $this->Teams[$team1->ID] = new Team;
            $this->Teams[$team2->ID] = new Team;
            
            foreach($team1 as $k => $v)
            {
                $this->Teams[$team1->ID]->$k = $v;
            }
            foreach($team2 as $k => $v)
            {
                $this->Teams[$team2->ID]->$k = $v;
            }
        }
    }
    
    public function TeamName($tid)
    {
        return ($tid == TEAM_BLUE) ? "blue" : "purple";
    }
}

class Team
{
    public $ID;
    
    public $Players = array();
}

class Player
{
    public $ID;
    public $Name;
    public $Champion;
    public $Spell1;
    public $Spell2;
    
    public $Stats;
    
    public static function GetKDA($Player)
    {
        $kills = 0;
        $deaths = 0;
        $assists = 0;
        if ($Player->Stats)
        {
            foreach($Player->Stats as $stat)
            {
                if ($stat->statType == "CHAMPIONS_KILLED")
                {
                    $kills += $stat->value;
                }
                if ($stat->statType == "NUM_DEATHS")
                {
                    $deaths += $stat->value;
                }
                if ($stat->statType == "ASSISTS")
                {
                    $assists += $stat->value;
                }
            }
        }
        return array($kills, $deaths, $assists);
    }
    
    public static function GetKDAStr($Player)
    {
        $arr = Player::GetKDA($Player);
        return sprintf("%d/%d/%d", $arr[0], $arr[1], $arr[2]);
    }
    
    public static function GetStat($Player, $name)
    {
        if ($Player->Stats)
        {
            foreach($Player->Stats as $stat)
            {
                if ($stat->statType == $name)
                {
                    return $stat->value;
                }
            }
        }
        return null;
    }
    
    public static function GoldFormat($gold)
    {
        if ($gold > 1000)
        {
            return number_format($gold / 1000, 1) . "k";
        }
        return $gold;
    }
}

