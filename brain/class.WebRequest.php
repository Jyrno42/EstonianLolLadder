<?php

class WebRequest
{
	public static function Regex($pattern, $str)
	{
		$matches = array();
		if(preg_match($pattern, $str, $matches) == 1)
		{
			return $matches[1];
		}
		return "";
	}
	
	public static function Get($url)
	{
		global $Init;
		if(filter_var($url, FILTER_VALIDATE_URL))
		{
			//$key = sprintf("webget_%u", crc32($url));
			//$cached = $Init->Cache->get($key);
			return file_get_html($url);
		}
		return NULL;
	}

	private static function get_data_with_curl($url) 
	{
		print "WTF";
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		//curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$data = curl_exec($ch);
		if(strpos(curl_getinfo($ch, CURLINFO_CONTENT_TYPE), "html") != FALSE)
		{
			print "1";
			curl_close($ch);
			return str_get_html($data);
		}
			print "2";
		curl_close($ch);
		return null;
	}
}