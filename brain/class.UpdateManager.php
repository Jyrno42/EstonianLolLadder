<?php

class UpdateManager
{
    const FILE_NAME = 'update_state';
    const LOG_FILE = 'update_log';
    const STATE_FILE = 'update_status';
    
    private $offline = 0;
    private $updated = 0;
    private $no_sid = 0;
    private $no_updates = 0;
    
    public function __construct($Datamanager, $Cache, $Smarty, $argv)
    {
        $start = ($argv[1] == 'START');
        $work = ($argv[1] == 'WORK');
        $end = ($argv[1] == 'END');
        
        if($start)
        {
            $Summoners = Summoner::objects($Datamanager)->all()->orderby(array("AID"))->get();
            $file = null;
            
            $file = fopen(self::FILE_NAME, 'w+');
            if ($file)
            {
                $str = smart_implode($Summoners, $glue="\r\n", $callback=function ($k, $v, $last, $db, &$glue) {
                    return $v->AID;
                });
                fwrite($file, $str);
                fclose($file);
                $this->loginfo(sprintf("Starting %d workers for %d summoners.", ceil(sizeof($Summoners)/10), sizeof($Summoners)));
            }
            else
            {
                $this->loginfo(sprintf("Problem opening file %s.", self::FILE_NAME));
            }
            sleep(1);
            file_put_contents(self::STATE_FILE, "0");
        }
        else if($work)
        {
            // This is a worker. Lets do work!
            $Summoners = $this->get_state();
            if ($Summoners)
            {
                $count = sizeof($Summoners);
            
                $api = new Elophant(array("apiKey" => "", "lolServer" => "euw"));
                foreach($Summoners as $k => $v)
                {
                    $changed = false;
                    $Summoner = Summoner::objects($Datamanager)->filter(array("AID" => $v))->get(1);
                    
                    if($Summoner->SID == 0)
                    {
                        $this->no_sid++;
                        continue;
                    }
                    
                    try
                    {
                        TheApi::GetAPI($api, $Summoner->Region);
                        
                        $changed = $Summoner->Update($api);
                        if($changed)
                        {
                            $this->updated++;
                            Summoner::save($Summoner, $Datamanager);
                        }
                        else
                        {
                            $this->no_updates++;
                        }
                    }
                    catch(Exception $e)
                    {
                        $this->offline++;
                        continue;
                    }
                }
                $this->loginfo(sprintf("Worker %s: Got %d, Updated %d, No SID %d, Not Changed %d, Offline %d.", $argv[2], $count, $this->updated, $this->no_sid, $this->no_updates, $this->offline));
            }
            $this->state_update();
        }
        else if($end)
        {
            $count = intval(trim($argv[2]));
            do
            {
                $st = file_get_contents(self::STATE_FILE);
                print "Still working...\r\n";
                
                sleep(1);
            } while($st != $count);
            
            $Cache->Set("updated", time(), 0);
            $Cache->Set("updatelog", sprintf("Updated summoners using %d workers.", $count), 0);
            $Smarty->clearAllCache();
            
            print "Complete\r\n";
            $this->loginfo("Complete...");
            $this->loginfo("--------------");
            $this->loginfo("--------------");
        }
    }

    function loginfo($msg)
    {
        $this->log_msg("INFO", $msg);
    }
    function logerror($msg)
    {
        $this->log_msg("ERROR", $msg);
    }

    private function log_msg($type, $msg)
    {
        // Waits until file is free to write into...
        if (!file_exists(self::LOG_FILE))
        {
            file_put_contents(self::LOG_FILE, "\r\n");
        }
        $file = fopen(self::LOG_FILE, 'a+');
        if ($file)
        {
            $block = true;
            if (flock($file, LOCK_EX, $block))
            {
                fwrite($file, sprintf("[%s] %s: %s\r\n", @date("c"), $type, $msg));
            }
            fclose($file);
        }
    }
    private function state_update()
    {
        // Waits until file is free to write into...
        if (!file_exists(self::STATE_FILE))
        {
            file_put_contents(self::STATE_FILE, "0");
        }
        $file = fopen(self::STATE_FILE, 'r+');
        if ($file)
        {
            $block = true;
            if (flock($file, LOCK_EX, $block))
            {
                $cc = intval(trim(fgets($file, 4096)))+1;
                fseek($file, 0);
                fwrite($file, $cc);
            }
            fclose($file);
        }
    }
    
    private function get_state()
    {
        if (file_exists(self::FILE_NAME))
        {
            $file = fopen(self::FILE_NAME, 'rw+');
            if ($file)
            {
                // Lock the file and get the first 11 items.
                $block = true;
                if (flock($file, LOCK_EX, $block))
                {
                    $lines = array();
                    $keep = array();
                    
                    $i = 0;
                    while (!feof($file) && $i < 10) {
                        $val = intval(trim(fgets($file, 4096)));
                        if ($val != 0)
                        {
                            $lines[] = $val; 
                        }
                        $i++;
                    }
                    while(!feof($file))
                    {
                        $keep[] = trim(fgets($file, 4096)); 
                    }
                    
                    fseek($file, 0);
                    ftruncate($file, 0);
                    fwrite($file, implode("\r\n", $keep));
                    fclose($file);
                    return $lines;
                }
                else
                {
                    $this->loginfo(sprintf("Problem acquiring lock on work que file %s.", self::FILE_NAME));
                }
            }
            else
            {
                $this->loginfo(sprintf("Problem opening work que file %s.", self::FILE_NAME));
            }
        }
        return null;
    }
}