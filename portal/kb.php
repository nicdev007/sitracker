<?php
//portal/kb.php - Show knowledgebase entries
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

$lib_path = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
require $lib_path.'db_connect.inc.php';
require $lib_path.'functions.inc.php';

$accesslevel = 'any';

include $lib_path.'portalauth.inc.php';
include '../inc/portalheader.inc.php';

echo "<h2>".icon('kb', 32)." {$strKnowledgeBase}</h2>";
$perpage = 20;
$order = cleanvar($_GET['order']);
$sort = cleanvar($_GET['sort']);

if (!isset($_GET['start']))
{
    $start = 0;
}
else
{
    $start = $_GET['start'];
}

$view = $_GET['view'];
$end = $start + $perpage;
$filter = array('start' => $start, 'view' => $view);

$sql = "SELECT k.*, s.name FROM `{$dbKBArticles}` AS k,
                                `{$dbKBSoftware}` as kbs,
                                `{$dbSoftware}` as s
        WHERE k.docid = kbs.docid AND kbs.softwareid = s.id AND k.distribution = 'public' ";

// $sql = "SELECT DISTINCT k.*, s.name FROM `{$dbKBArticles}` AS k, `{$dbSoftware}` as s ";
// $sql .= "LEFT JOIN `{$dbKBSoftware}` as kbs ";
// $sql .= "ON kbs.softwareid=s.id ";
// $sql .= "WHERE k.docid = kbs.docid AND k.distribution='public' ";

if ($view != 'all')
{
    $softwares = contract_software();
    $sql .= "AND (1=0 ";
    if (is_array($softwares))
    {
        foreach ($softwares AS $software)
        {
            $sql .= "OR kbs.softwareid={$software} ";
        }
    }
    $sql .= ")";

    echo "<p class='info'>{$strShowingOnlyRelevantArticles} - ";
    echo "<a href='{$_SERVER['PHP_SELF']}?view=all'>{$strShowAll}</a></p>";
}
else
{
    echo "<p class='info'>{$strShowingAllArticles} - ";
    echo "<a href='{$_SERVER['PHP_SELF']}'>{$strShowOnlyRelevant}</a></p>";
}

//get the full SQL so we can see the total rows
$countsql = $sql;
$sql .= "GROUP BY k.docid ";
if (!empty($sort))
{
    if ($sort=='title') $sql .= "ORDER BY k.title ";
    elseif ($sort=='date') $sql .= " ORDER BY k.published ";
    elseif ($sort=='author') $sql .= " ORDER BY k.author ";
    elseif ($sort=='keywords') $sql .= " ORDER BY k.keywords ";
    else $sql .= " ORDER BY k.docid ";

    if ($order=='a' OR $order=='ASC' OR $order='') $sql .= "ASC";
    else $sql .= "DESC";
}
else
{
    $sql .= " ORDER BY k.docid DESC ";
}
$sql .= " LIMIT {$start}, {$perpage} ";

if ($result = mysql_query($sql))
{
    $countresult = mysql_query($countsql);
    $numtotal = mysql_num_rows($countresult);
    if ($end > $numtotal)
    {
        $end = $numtotal;
    }
    if ($numtotal > 0)
    {
        echo "<p>".sprintf($strShowingXtoXofX, $start+1, $end, $numtotal)."</p>";

        echo "<p align='center'>";

        if (!empty($_GET['start']))
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?start=";
            echo $start-$perpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}nerw'>{$strPrevious}</a> ";
        }
        else
        {
            echo $strPrevious;
        }
        echo " | ";
        if ($end != $numtotal)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?start=";
            echo $start+$perpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext}</a> ";    }
        else
        {
            echo $strNext;
        }
        echo "</p>";

        echo "<table align='center' width='80%'><tr>";
        echo colheader('id', $strID, $sort, $order, $filter, '', '5');
        echo colheader('title', $strTitle, $sort, $order, $filter);
        echo colheader('date', $strDate, $sort, $order, $filter, '', '15');
        echo colheader('author', $strAuthor, $sort, $order, $filter);
        echo colheader('keywords', $strKeywords, $sort, $order, $filter, '', '15');
        echo "</tr>";
        $shade = 'shade1';
        while($row = mysql_fetch_object($result))
        {
            echo "<tr class='{$shade}'>";
            echo "<td><a href='kbarticle.php?id={$row->docid}'>";
            echo icon('kb', 16, $strID);
            echo " {$CONFIG['kb_id_prefix']}{$row->docid}</a></td>";
            echo "<td>{$row->name}<br />";
            echo "<a href='kbarticle.php?id={$row->docid}'>{$row->title}</a></td>";
            echo "<td>";
            echo ldate($CONFIG['dateformat_date'], mysql2date($row->published));
            echo "</td>";
            echo "<td>".user_realname($row->author)."</td>";
            echo "<td>{$row->keywords}</td></tr>";

            if ($shade == 'shade1')
                $shade = 'shade2';
            else
                $shade = 'shade1';
        }
        echo "</table>";
    }
    else
    {
        echo "<p align='center'>{$strNoRecords}</p>";
    }
}
else
{
    echo "<p align='center'>{$strNoRecords}</p>";
}

include ('../inc/htmlfooter.inc.php');

?>