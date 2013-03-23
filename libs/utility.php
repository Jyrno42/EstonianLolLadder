<?php

// http://stackoverflow.com/questions/990406/php-intval-equivalent-for-numbers-2147483647
function bigintval($value) {
    $value = trim($value);
    if (ctype_digit($value)) {
        return $value;
    }
    $value = preg_replace("/[^0-9](.*)$/", '', $value);
    if (ctype_digit($value)) {
        return $value;
    }
    return 0;
}

function startsWith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function smart_implode($array, $glue=", ", $callback=null, $extra=null)
{
    if(!is_array($array))
    {
        return $array;
    }
    else
    {
        $ret = "";
        foreach($array as $k => $v)
        {
            end($array);
            
            $part = ($callback !== null ? $callback($k, $v, $k === key($array), $extra, $glue) : $v);
            $ret .= $part ? $part . ($k !== key($array) ? $glue : "") : "";
        }
        return $ret;
    }
}

function relative_time($timestamp)
{
    if(!$timestamp)
        return "ammu";
    
    $difference = time() - $timestamp;
    $periods = array("sekund", "minut", "tund", "päev", "nädal", "kuu", "aasta", "dekaad");
    $periods2 = array("sekundit", "minutit", "tundi", "päeva", "nädalat", "kuud", "aastat", "dekaadi");
    $lengths = array("60","60","24","7","4.35","12","10");
    if ($difference > 0)
    {
        $ending = "tagasi";
    }
    else
    {
        $difference = -$difference;
        $ending = "pärast";
    }
    $j = 0;
    for($j = 0; $difference >= $lengths[$j]; $j++)
        $difference /= $lengths[$j];
    
    $difference = round($difference);
    return sprintf("%s %s %s", $difference, $difference != 1 ? $periods2[$j] : $periods[$j], $ending);
}