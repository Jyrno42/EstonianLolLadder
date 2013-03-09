<?php

static $vals = array();

function smarty_block_compresscss($params, $content, &$smarty, &$repeat)
{
	if($content)
	{
		$str = "";
		$links = array();
		
		if(defined("DEBUG")) // In debug mode we dont do anything.
			return $content;
		
		$html = str_get_html($content); // probably too much overhead for this... convert to regex...
		foreach($html->find("link[rel='stylesheet']") as $link)
		{
			$media = $link->media ? $link->media : "-";
			if(!isset($links[$media]))
				$links[$media] = array();
			$links[$media][] = $link->href;
			$link->outertext = ''; // strip links out of html
		}
		
		$time_start = microtime(true);
		foreach($links as $media => $stylesheets)
		{
			$key = "";
			foreach($stylesheets as $css)
			{
				$key .= better_filemtime(str_replace(SITE_HOST, ".", $css));
			}
			
			$fileName = sprintf("./cache/%u_%s.css", crc32($key), $media);
			
			// No valid cache
			if(!file_exists($fileName) || better_filemtime($fileName) > time() + 60*30)
			{
				// Regenerate
				$contents = "";
				foreach($stylesheets as $css)
				{
					$content = file_get_contents($css);
					if($content !== FALSE)
					{
						$contents .= $content;
					}
				}
				
				// Compress
				//$yui = new YUICompressor(getcwd() . "/libs/yuicompressor-2.4.7/yuicompressor-2.4.7.jar", sys_get_temp_dir(), array("type" => "css"));
				//$yui->addString($contents);
				//$contents = $yui->compress();
				
				// Save
				@file_put_contents($fileName, $contents);
			}
			$str .= sprintf("<link rel='stylesheet' media='%s' href='%s' />", $media, $fileName);
		}
		$html->clear();
		unset($html);
		return $str;
	}
}

function better_filemtime($url)
{
	global $Init;
	
	$key = sprintf("filemtime.%u", crc32($url));
	$var = $Init->Cache->get($key);
	if($var === FALSE)
	{
		$var = filemtime($url);
		$Init->Cache->set($key, $var, 60);
	}
	return $var;
}

?>
