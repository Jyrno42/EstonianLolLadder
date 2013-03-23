<?php

class UserManager
{
    public $User = NULL;
    private $sessionMan = null;
    
    /**
     * 
     * @param DataManager $connection
     */
    public function UserManager($connection)
    {
        $this->connection = $connection;
        
        $this->User = (object)null;
        $this->Authenticate($connection);
        
        if($this->User->Valid)
        {
            $this->User->MySql = User::objects($connection)->filter(array("UserID" => $this->User->Id))->get(1);
            $r = new Rights();
            $this->User->Rights = $r->ParseRights($this->User->MySql->Rights);
        }
        else
        {
            $this->User->MySql = (object)null;
        }
    }
    
    public function __destruct()
    {
        //parent::__destruct();
    }
    
    private function GetSession()
    {
        $hash = Authentication::instance()->GetHash(0);
        if($hash !== null)
        {
            $query = "SELECT SID, SKEY, UID FROM " . TABLE_PREFIX . "sessions WHERE SKEY = '$hash'";
            $result = $this->connection->mysql_query($query);
            if($result !== FALSE)
            {
                if($result->num_rows > 0)
                {
                    while (($row = $result->fetch_assoc()) !== NULL)
                    {
                        $this->sessionMan = $row;
                    }
                }
                $result->close();
            }
        }
    }
    
    public function Login($userid, $nored = false)
    {
        $userid = $this->connection->mysql_escape_string($userid);
        if(!$this->User->Valid)
        {
            $hash = Authentication::instance()->GetHash(1); // Get new hash and store it...
            
            $this->GetSession();
                    
            if(!$this->sessionMan)
            {
                $query = "INSERT INTO " . TABLE_PREFIX . "sessions(SID, SKEY, UID) VALUES(NULL, '$hash', '$userid');";
                $this->connection->mysql_query($query);
                $this->sessionMan = array("SID" => $this->connection->mysql_insert_id(), "SKEY" => $hash, "UID" => $userid);
            }
            else
            {
                $query = "UPDATE " . TABLE_PREFIX . "sessions SET SKEY = '$hash' WHERE SID = '" . $this->sessionMan["SID"] . "';";
                $this->connection->mysql_query($query);
            }
            
            if(!$nored)
                header("Location: index.php?page=index");
        }
    }
    
    public function Logout()
    {
        if($this->User->Valid)
        {
            $query = "DELETE FROM " . TABLE_PREFIX . "sessions WHERE SID = '" . $this->sessionMan["SID"] . "'";
            $this->connection->mysql_query($query);
            
            Authentication::instance()->invalidate();
            header("Location: " . SITE_HOST);
        }
    }
    
    /**
     *
     * @param DataManager $connection
     */
    private function Authenticate($connection)
    {
        // Get the clients old unique identity
        $hash = Authentication::instance()->GetHash(0);
        
        if($hash !== null)
        {
            $this->GetSession();
            
            if($this->sessionMan)
            {
                $this->User->Valid = true;
                $this->User->Id = $this->sessionMan["UID"];
                
                $this->sessionMan["SKEY"] = Authentication::instance()->GetHash(1);
                
                $query = "UPDATE " . TABLE_PREFIX . "sessions SET SKEY = '" . $this->sessionMan["SKEY"] . "' WHERE SID = '" . $this->sessionMan["SID"] . "';";
                $this->connection->mysql_query($query);
                return;
            }
        }
        
        $this->User->Valid = false;
    }
    
    private function formItem($label, $type="text", $name=null, $items=null, $value=null)
    {
        $i = new stdClass();
        $i->label = $label;
        $i->name = $name;
        $i->type = $type;
        $i->value = $value;
        
        if($items && is_array($items))
            $i->items = $items;
        
        return $i;
    }
    
    public function LoginForm()
    {
        return array(
            $this->formItem(_("Email:"), "text", "email"),
            $this->formItem(_("Password:"), "password", "password")	
        );
    }
    
    public function RegisterForm()
    {
        $arr = array(
            $this->formItem(_("Email:"), "text", "email", null, isset($_SESSION["USER_DATA_EMAIL"]) ? $_SESSION["USER_DATA_EMAIL"] : null),
            $this->formItem(_("Name:"), "text", "name", null, isset($_SESSION["USER_DATA_NAME"]) ? $_SESSION["USER_DATA_NAME"] : null),
            $this->formItem(_("Password:"), "password", "password", null)
        );

        if(isset($_SESSION["USER_DATA_EMAIL"]))
            unset($_SESSION["USER_DATA_EMAIL"]);
        if(isset($_SESSION["USER_DATA_NAME"]))
            unset($_SESSION["USER_DATA_NAME"]);
        
        return $arr;
    }
    
    public function Can($var)
    {
        return $this->User->Valid && isset($this->User->Rights) && isset($this->User->Rights[$var]) && $this->User->Rights[$var];
    }
    
    public function CheckForRegistration($email, $password = null)
    {
        $d = User::objects($this->connection)->filter(array("Email" => $email))->get(1);
        if($d)
        {
            if($password != null)
            {
                if(strcmp($d->passkey, $password) != 0)
                {
                    return null;
                }
            }
            return $d;
        }
        return null;
    }

    public function UseStd()
    {
        return true;
    }
    
    protected function LoadCode($row)
    {
        $this->User->MySql = (object)null;
        
        foreach($row as $k2 => $v2)
        {
            $this->User->MySql->$k2 = $v2;
        }
        return $this->User->MySql;
    }
    
    protected function UpdateCode($k, $v)
    {
        $r = new Rights();
        $v->Rights = $r->GetRights($this->User->Rights);
        
        foreach($v as $k2 => $v2)
        {
            $this->result[$k][$k2] = $v2;
        }
    }
    
    protected function InsertCode($value)
    {
        return array(
            "Email"=>$value->Email,
            "Rights" =>"0"		
        );
    }
    
    protected function PostInsert($id, &$value)
    {
        $value->UserID = $id;
    }
    
    protected function DeleteCode($id, $row)
    {
    }
    
    public function CanModifyTeam(Team $Team)
    {
        return $this->Can("manage") || ($Team->OwnerID != 0 && $this->User->MySql->UserID == $Team->OwnerID);
    }
};

class Rights
{
    public $Rights = array(
        "manage" => 0,
        "manage_templates" => 1,
        "manage_users" => 2,
        "send_emails" => 3,
        "unused_4" => 4,
        "unused_5" => 5,
        "unused_6" => 6,
        "unused_7" => 7,
        "unused_8" => 8,
        "unused_9" => 9,
        "unused_10" => 10,
        "unused_11" => 11,
        "unused_12" => 12,
        "unused_13" => 13,
        "unused_14" => 14,
        "unused_15" => 15
    );
    
    public function GetRights($array)
    {
        $ret = 0;
        foreach ($this->Rights as $k => $v)
        {
            $ret |= isset($array[$k]) && $array[$k] ? 1 << $v : 0 << $v;
        }
        return $ret;
    }
    
    public function ParseRights($val)
    {
        $ret = array();
        foreach ($this->Rights as $k => $v)
        {
            $ret[$k] = (bool)($val & 1 << $v);
        }
        return $ret;
    }
}

class NotLoggedException extends Exception
{
    public function __construct()
    {
         parent::__construct("You are not logged in!", 1337);
    }
}
class NotAuthorizedException extends Exception
{
    public function __construct()
    {
         parent::__construct("You are not authorized to preform this action!", 1338);
    }
}

?>