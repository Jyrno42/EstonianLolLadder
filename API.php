<?php

define("CLI", PHP_SAPI === 'cli');
define("VERSION", "231512032013");

if (PHP_SAPI === 'cli')
{
    //print "\n\n###############################################################################\n";
    //print "#####            LoL Estonian Database Command Line interface             #####\n";
    //print "###############################################################################\n\n";
    
    $dir = dirname(__FILE__);
    chdir($dir);
    //print "Changing working directory to $dir\n";
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
        $updator = new UpdateManager($Init->Datamanager, $argv);
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