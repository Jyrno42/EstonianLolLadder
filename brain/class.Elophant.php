<?php
/**
 * Elophant.com - PHP API
 * 
 * @author Kaj <EUW Summoner: Edocsyl>
 * @since 14.11.2012
 * @copyright Kaj <kaj@edocsyl.ch>
 * @website http://edocsyl.ch/
 * @version 1.1
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 * @docs http://elophant.edocsyl.ch/ | http://elophant.com/developers/docs
 */

class Elophant{

  /**
   * The API base URL
   */
	const API_URL = "http://api.elophant.com/v2/";

  /**
   * Lol Server Shortcuts
   * 
   * @var array
   */
	private $_lolServers = array('na', 'euw', 'eune', 'br');
	
  /**
   * Elophant API Key
   * 
   * @var string
   */
	private $_apiKey;

  /**
   * Lol Server
   * 
   * @var string
   */
	private $_lolServer;
    
    public $Remaining = null;
	
  /**
   * Default constructor
   *
   * @param array|string $config - Config Array
   * @return void
   */
	public function __construct($config){
		if (true === is_array($config)){
			$this->setApiKey($config['apiKey']);
			$this->setLolServer($config['lolServer']);
		} else {
			throw new Exception("Error: __construct() - Configuration data is missing.");
		}
		
	}
	
	/***************** API FUNCTIONS ************************************************************/
	
	/**
	* Returns the current status of every region.
	*
	* @return json decode
	*/
	public function getStatus(){
		$status = $this->_makeCall('champions?', true); // dont use cache
        return $status;
	}
	
	/**
	* Returns every items's id and name in Json.
	*
	* @return json decode
	*/
	public function getItems(){
		return $this->_makeCall('items?');
	}
	
	/**
	* Returns every champion's id and name in Json.
	*
	* @return json decode
	*/
	public function getChampions(){
		return $this->_makeCall('champions?');
	}
	
	/**
	* Get the Summoner ID
	*
	* @param string $summonerName - Summoner Name
	* @return string - Summoner ID
	*/
	public function getSummonerId($summonerName){
		return $this->getSummonerByName($summonerName)->summonerId;
	}
	
	/**
	* Get the Account ID
	*
	* @param string $summonerName - Summoner Name
	* @return string - Account ID
	*/
	public function getAccountId($summonerName){
		return $this->getSummonerByName($summonerName)->acctId;
	}

	/**
	* Get the Account ID
	*
	* @param string $summonerName - Summoner Name
	* @return string - Account ID
	*/
	public function getSummonerByName($summonerName){
		$summonerName = str_replace(' ', '%20', $summonerName);
		return $this->_makeCall($this->_lolServer . '/summoner/' . $summonerName . '?');
	}
	
    public function getCombinedRankedStatistics($accountId)
    {
        $allChampions = $this->getRankedStats($accountId, "CURRENT");
        if(isset($allChampions->data) && isset($allChampions->data->lifetimeStatistics))
        {
            $result = array();
            $allChampions = $allChampions->data->lifetimeStatistics;
            foreach($allChampions as $k => $v)
            {
                if($v->championId == 0)
                {
                    $result[$v->statType] = $v->value;
                }
            }
            return $result;
        }
        return null;
    }
	
	/**
	* Returns a summoner's 3 most played champions from their ranked statistics.
	*
	* @param string $accountId - Summoner Account ID
	* @return json decode
	*/
	public function getMostPlayedChampions($accountId){
		//return $this->_makeCall($this->_lolServer . '/getMostPlayedChampions?accountId=' . $accountId . '&');
        //TOTAL_SESSIONS_WON + TOTAL_SESSIONS_LOST
        
        $allChampions = $this->getRankedStats($accountId, "CURRENT");
        if(isset($allChampions->data) && isset($allChampions->data->lifetimeStatistics))
        {
            $allChampions = $allChampions->data->lifetimeStatistics;
            $combinedData = array();
            
            foreach($allChampions as $k => $v)
            {
                if($v->championId)
                {
                    if(!isset($combinedData[$v->championId]))
                        $combinedData[$v->championId] = array();
                    $combinedData[$v->championId][$v->statType] = $v->value;
                }
            }
            
            $result = array();
            
            foreach($combinedData as $k => $v)
            {
                $val = new stdClass;
                $val->championId = $k;
                $val->stats = $v;
                $val->totalGamesPlayed = $v["TOTAL_SESSIONS_PLAYED"];
                $result[] = $val;
            }
            usort($result, function ($a, $b) {
                return $a->totalGamesPlayed < $b->totalGamesPlayed;
            });
            return $result;
        }
        return null;
	}

	/**
	* Returns an array with each mastery book page.
	*
	* @param string $summonerId - Summoner ID
	* @return json decode
	*/
	public function getMasteryPages($summonerId){
		return $this->_makeCall($this->_lolServer . '/mastery_pages?summonerId=' . $summonerId . '&');
	}
	
	/**
	* Returns an array with each rune page.
	*
	* @param string $summonerId - Summoner ID
	* @return json decode
	*/
	public function getRunePages($summonerId){
		return $this->_makeCall($this->_lolServer . '/rune_pages?summonerId=' . $summonerId . '&');
	}

	/**
	* Returns an array of summoner names in the same order as provided in the parameter summonerIds.
	*
	* @param array $summonerIds - Summoner ID's
	* @return json decode
	*/
	public function getSummonerNames($summonerIds = array()){
		$summonerId = null;
		foreach($summonerIds as $id){
			$summonerId = $summonerId . $id . ',';
		}
		return $this->_makeCall($this->_lolServer . '/summoner_names?summonerIds=' . substr($summonerId, 0, -1) . '&');
	}

	/**
	* Returns an overview of the statistics for each game mode for the specified summoner.
	*
	* @param string $accountId - Summoner Account ID
	* @param string $lolSeason - Example Values: CURRENT or ONE
	* @return json decode
	*/
	public function getPlayerStats($accountId, $lolSeason){
		return $this->_makeCall($this->_lolServer . '/player_stats/' . $accountId . '/' . $lolSeason . '?');
	}
	
	/**
	* Returns every statistic for every champion accumulated from all ranked game types for a specified summoner and season.
	*
	* @param string $accountId - Summoner Account ID
	* @param string $lolSeason - Example Values: CURRENT or ONE
	* @return json decode
	*/
	public function getRankedStats($accountId, $lolSeason){
		return $this->_makeCall($this->_lolServer . '/ranked_stats/' . $accountId . '/' . $lolSeason . '?');
	}
    
	/***************** SOME STUFF ************************************************************/
	
	/**
	* Makes the Call
	*
	* @param string $function - The function
	* @return json decode
	*/
	private function _makeCall($function, $noCache=false){
		global $Init;
		$cached = $Init->Cache->get(sprintf("lol_makeCall_%u", crc32($function)));
		
		if(!$cached || $noCache)
		{
			$apiCall = self::API_URL . $function . $this->_apiKey;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $apiCall);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $cached = substr($response, $header_size);
            
            $parsed = http_parse_headers($header);
            if(isset($parsed['Developer-Remaining']))
                $this->Remaining = $parsed['Developer-Remaining'];
            
			$Init->Cache->set(sprintf("lol_makeCall_%u", crc32($function)), $cached, 60);
			curl_close($ch);
		}
		return json_decode($cached);
	}
	
	/**
	* Check if server shortcut is valid
	*
	* @param string $lolServer - Server shortcut
	* @return true|false
	*/
	private function checkServer($lolServer){
		if(in_array(strtolower($lolServer), $this->_lolServers)){
			return true;
		} else {
			return false;
		}
	}
	
	public function GetPlayerStat($account_id, $returnVal)
	{
		$allStats = $this->getPlayerStats($account_id, "CURRENT");
        return $allStats;
		if(isset($allStats->data) && isset($allStats->data->playerStatSummaries) && isset($allStats->data->playerStatSummaries->playerStatSummarySet))
        
		{
			$stats = $allStats->data->playerStatSummaries->playerStatSummarySet;
			foreach($stats as $k => $v)
			{
				if($v->playerStatSummaryType == "RankedSolo5x5")
				{
					return $v->$returnVal;
				}
			}
			return 0;
		}
		return null;
	}
	
	/**
	* API Key setter
	*
	* @param string $apiKey - Elophant API Key
	* @return void
	*/
	public function setApiKey($apiKey){
		$this->_apiKey = 'key=' . $apiKey;
	}
	
	/**
	* Server shortcut setter
	*
	* @param string $lolServer - Lol Server shortcut
	* @return void
	*/
	public function setLolServer($lolServer){
		if($this->checkServer($lolServer)){
			$this->_lolServer = $lolServer;
		} else {
			throw new Exception("Error: __construct() - Configuration wrong lol server. " . $lolServer);
		}
	}		

    public function getLeagues($summonerId){
        return $this->_makeCall($this->_lolServer . '/leagues/' . $summonerId . '?');
    }
}

function http_parse_headers( $header )
{
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
    foreach( $fields as $field ) {
        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
            $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
            if( isset($retVal[$match[1]]) ) {
                $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
            } else {
                $retVal[$match[1]] = trim($match[2]);
            }
        }
    }
    return $retVal;
}
