<?php

spl_autoload_unregister("brain_loader");

function require_lib($fName, $error)
{
    if(!file_exists($fName))
        die(sprintf(_("Missing %s Libary!"), $error));
    
    try {
        ob_start();
        require_once($fName);
        ob_end_clean(); // Make sure we are not outputting anything here...
    }
    catch(Exception $e)
    {
        die(sprintf(_("Problem with %s Libary!"), $e));
    }
}

/**
 * This file includes all libaries used in our project
 */

require_lib("libs/utility.php", "Utility");

require_lib("libs/Smarty/Smarty.class.php", "Smarty");
spl_autoload_register("brain_loader");
