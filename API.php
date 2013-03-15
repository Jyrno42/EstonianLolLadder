<?php

define("CLI", PHP_SAPI === 'cli');
define("VERSION", "1.0.1b");

if (PHP_SAPI === 'cli')
{
    $dir = dirname(__FILE__);
    chdir($dir);
}
else
{
    header('Content-type: text/html; charset=utf-8');
}

require_once("config/config.php");

$Init = null;
try 
{
    $_GET["action"] = isset($_GET["action"]) ? $_GET["action"] : "render";
    
    $API = new TheApi();
    
    $Init = new BootStrap();
    
    $Init->Strap();

    if (PHP_SAPI === 'cli')
    {
        $updator = new UpdateManager($Init->Datamanager, $Init->Cache, $Init->Smarty, $argv);
        exit;
    }

    $API->SetBootstrap($Init);
    $API->Strap($Init);
}

catch(Exception $e)
{
    $API->Error($e);
}

if($Init != null)
    $Init->Detach();