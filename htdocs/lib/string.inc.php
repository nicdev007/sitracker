<?php
// string.inc.php - String functions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


function strip_comma($string)
{
    // also strips Tabs, CR's and LF's
    $string = str_replace(",", " ", $string);
    $string = str_replace("\r", " ", $string);
    $string = str_replace("\n", " ", $string);
    $string = str_replace("\t", " ", $string);
    return $string;
}


function leading_zero($length,$number)
{
    $length = $length-strlen($number);
    for ($i = 0; $i < $length; $i++)
    {
        $number = "0" . $number;
    }
    return ($number);
}




function beginsWith( $str, $sub )
{
   return ( substr( $str, 0, strlen( $sub ) ) === $sub );
}


function endsWith( $str, $sub )
{
   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}


function remove_slashes($string)
{
    $string = str_replace("\\'", "'", $string);
    $string = str_replace("\'", "'", $string);
    $string = str_replace("\\'", "'", $string);
    $string = str_replace("\\\"", "\"", $string);

    return $string;
}


// This function doesn't exist for PHP4 so use this instead
if (!function_exists("stripos"))
{
    function stripos($str,$needle,$offset=0)
    {
        return strpos(strtolower($str),strtolower($needle),$offset);
    }
}


function string_find_all($haystack, $needle, $limit=0)
{
    $positions = array();
    $currentoffset = 0;

    $offset = 0;
    $count = 0;
    while (($pos = stripos($haystack, $needle, $offset)) !== false && ($count < $limit || $limit == 0))
    {
        $positions[] = $pos;
        $offset = $pos + strlen($needle);
        $count++;
    }
    return $positions;
}


/**
    * Trims a string so that it is not longer than the length given and
    * add ellipses (...) to the end
    * @author Ivan Lucas
    * @param string $text. Some plain text to shorten
    * @param int $maxlength. Length of the resulting string (in characters)
    * @param bool $html. Set to TRUE to include HTML in the output (for ellipsis)
    *                    Set to FALSE for plain text only
    * @returns string. A shortned string (optionally with html)
*/
function truncate_string($text, $maxlength=255, $html = TRUE)
{

    if (strlen($text) > $maxlength)
    {
        // Leave space for ellipses
        if ($html == TRUE)
        {
            $maxlength -= 1;
        }
        else
        {
            $maxlength -= 3;
        }

        $text = utf8_encode(wordwrap(utf8_decode($text), $maxlength, '^\CUT/^', 1));
        $parts = explode('^\CUT/^', $text);
        $text = $parts[0];

        if ($html == TRUE)
        {
            $text .= '&hellip;';
        }
        else
        {
            $text .= '...';
        }
    }
    return $text;
}


/**
* UTF8 substr() replacement
* @author Anon / Public Domain
* @note see http://www.php.net/manual/en/function.substr.php#57899
*/
function utf8_substr($str, $from, $len)
{
    # utf8 substr
    # www.yeap.lv
    return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
                    '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                    '$1',$str);
}


/**
* UTF8 strlen() replacement
* @author anpaza at mail dot ru / Public Domain
* @note see http://www.php.net/manual/en/function.strlen.php#59258
*/
function utf8_strlen($str)
{
    $i = 0;
    $count = 0;
    $len = strlen ($str);
    while ($i < $len)
    {
    $chr = ord ($str[$i]);
    $count++;
    $i++;
    if ($i >= $len)
        break;

    if ($chr & 0x80)
    {
        $chr <<= 1;
        while ($chr & 0x80)
        {
        $i++;
        $chr <<= 1;
        }
    }
    }
    return $count;
}


?>