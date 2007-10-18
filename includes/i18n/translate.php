<?php
// translate.php - A simple interface for aiding translation.
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2007 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Kieran Hogg <kieran_hogg[at]users.sourceforge.net>

$permission=0; // not required
require('db_connect.inc.php');
require('functions.inc.php');

require('auth.inc.php');
include('htmlheader.inc.php');

if(!$_REQUEST['mode'])
{
    echo "<h2>{$strTranslation}</h2>";
    echo "<div align='center'><p><{$strHelpToTranslate}</p>";
    echo "<p>{$strChooseLanguage}</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}?mode=show&' method='get'>";
    //FIXME
    echo "<input name='mode' value='show' type='hidden'>";
    echo "<select name='lang'></div>";
    if ($handle = opendir('.'))
    {
        while (false !== ($file = readdir($handle)))
        {
            $ext = explode(".", $file);
            if($ext[1] == "inc" && $ext[2] == "php")
                echo "<option value='$ext[0]'>$ext[0]<br />";
        }
        closedir($handle);
    }
    echo "</select><br />";
    echo "<input type='submit' value='$strTranslate'>";
}
elseif($_REQUEST['mode'] == "show")
{
    //open english file
    $englishfile = "en-gb.inc.php";
    $fh = fopen($englishfile, 'r');
    $theData = fread($fh, filesize($englishfile));
    fclose($fh);
    $lines = explode(";", $theData);
    $langstrings['en-gb'];
    $englishvalues = array();
    foreach($lines as $values)
    {
        $badchars = array("$", "\"", "\\", "<?php", "?>");
        $values = trim(str_replace($badchars, '', $values));

        //get variable and value
        $vars = explode("=", $values);

        //remove spaces
        $vars[0] = trim($vars[0]);
        $vars[1] = trim($vars[1]);

        if(substr($vars[0], 0, 3) == "str")
        {
            //remove leading and trailing quotation marks
            $vars[1] = substr_replace($vars[1], "",-1);
            $vars[1] = substr_replace($vars[1], "",0, 1);

            $englishvalues[$vars[0]] = $vars[1];
        }
        else
        {
            if(substr($values, 0, 4) == "lang")
                $languagestring=$values;
            if(substr($values, 0, 4) == "i18n")
                $i18ncharset=$values;
        }
    }

    //open foreign file
    $myFile = "{$_REQUEST['lang']}.inc.php";
    $fh = fopen($myFile, 'r');
    $theData = fread($fh, filesize($myFile));
    fclose($fh);
    $lines = explode(";", $theData);
    //print_r($lines);
    $foreignvalues = array();
    foreach($lines as $values)
    {
        $badchars = array("$", "\"", "\\", "<?php", "?>");
        $values = trim(str_replace($badchars, '', $values));
        if(substr($values, 0, 3) == "str")
        {
            $vars = explode("=", $values);
            $vars[0] = trim($vars[0]);
            $vars[1] = trim(substr_replace($vars[1], "",-1));
            $vars[1] = substr_replace($vars[1], "",0, 1);
            $foreignvalues[$vars[0]] = $vars[1];
        }
    }

echo "<h2>Word List</h2>";
echo "<p align='center'>{$strTranslateTheString}</p>";
echo "<form method='post' action='{$_SERVER[PHP_SELF]}?mode=save'>";
echo "<table align='center'><tr><th>{$strVariable}</th><th>{$strEnglish}</th><th>{$_REQUEST['lang']}</th></tr>";

$shade = 'shade1';
foreach(array_keys($englishvalues) as $key)
{
    echo "<tr class='$shade'><td><code>{$key}</code></td><td><input name='english_{$key}' value=\"".htmlentities($englishvalues[$key], ENT_QUOTES, 'UTF-8')."\" size=\"40\" /></td>";
    echo "<td><input name=\"{$key}\" value=\"".htmlentities($foreignvalues[$key], ENT_QUOTES, 'UTF-8')."\" size=\"40\" /></td></tr>\n";
    if ($shade=='shade1') $shade='shade2';
    else $shade='shade1';
}

echo "</table>";
echo "<input name='lang' value='{$_REQUEST['lang']}' type='hidden' /><input name='mode' value='save' type='hidden' />";
echo "<div align='center'><input type='submit' value='{$strSave}' /></div>";
echo "</form>\n";
}
elseif($_REQUEST['mode'] == "save")
{
    printf($strSendTranslation, $_REQUEST['lang']);
    $i18nfile = '';
    $i18nfile .= "<?php\n";
    $i18nfile .= "\$languagestring = '{$_REQUEST['lang']}';\n";
    $i18nfile .= "\$i18ncharset = 'UTF-8';\n\n";

    $lastchar='';
    foreach(array_keys($_POST) as $key)
    {
        if(!empty($_POST[$key]) AND substr($key, 0, 3) == "str")
        {
            if ($lastchar!='' AND substr($key, 4, 1) != $lastchar) $i18nfile .= "\n";
            $i18nfile .= "\${$key} = '{$_POST[$key]};'\n";
            $lastchar = substr($key, 4, 1);
        }
    }
    $i18nfile .= "?>\n";
    echo "<div style='margin-left: 5%; margin-right: 5%; background-color: white; border: 1px solid #ccc; padding: 1em;'>";
    highlight_string($i18nfile);
    echo "</div>";
}
else
{
    die('Invalid mode');
}
include('htmlfooter.inc.php');
?>