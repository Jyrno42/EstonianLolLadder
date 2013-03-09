<?php

define("CLI", PHP_SAPI === 'cli');
define("VERSION", "205109032013");

if (PHP_SAPI === 'cli')
{
    print "\n\n###############################################################################\n";
    print "#####            LoL Estonian Database Command Line interface             #####\n";
    print "###############################################################################\n\n";
    
    $dir = dirname(__FILE__);
    chdir($dir);
    print "Changing working directory to $dir\n";
}

require_once("config/config.php");

$Init = null;
try 
{
    if (PHP_SAPI === 'cli')
    {
        $_GET["action"] = "RunCrons";
    }
    $_GET["action"] = isset($_GET["action"]) ? $_GET["action"] : "render";
    
    $API = new TheApi();
    
    $Init = new BootStrap();
    
    $Init->Strap();
    
    $API->SetBootstrap($Init);
    $API->Strap($Init);
}

catch(Exception $e)
{
	$API->Error($e);
}

if($Init != null)
	$Init->Detach();