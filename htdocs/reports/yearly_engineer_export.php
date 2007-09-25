<?php
// yearly_enginer_export.php - List the numbers and titles of incidents logged to each engineer in the past year.
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2007 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Paul Heaney <paulheaney[at]users.sourceforge.net>

// Notes:
//  Lists incidents that have been logged to specified engineers over the past 12 months
//  Note that this will be inaccurate to a degree because it's only looking at the current owner
//  not the past owners.  ie. it doesn't take into account any reassignments.
//  Escalation will only show if the call was escalated or not will not show if escalated multiple times

// Requested by Rob Shepley, 3 Oct 05

$permission=37; // Run Reports
$title='Yearly Engineer/Incident Report';
require('db_connect.inc.php');
require('functions.inc.php');

// This page requires authentication
require('auth.inc.php');

if (empty($_REQUEST['mode']))
{
    include('htmlheader.inc.php');
    echo "<h2>$title</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='incidentsbyengineer'>";
    echo "<table align='center'>";
    echo "<tr><th>Start Date:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('incidentsbyengineer.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>End Date:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('incidentsbyengineer.enddate');
    echo "</td></tr>\n";
    echo "<tr><th>Dates are:</th><td>";
    echo "<input type='radio' name='type' value='opened' />Opened ";
    echo "<input type='radio' name='type' value='closed' />Closed ";
    echo "<input type='radio' name='type' value='both' checked='yes' />Both";
    echo "</td></tr>";
    echo "<tr><th colspan='2'>Include</th></tr>";
    echo "<tr><td align='center' colspan='2'>";
    $sql = "SELECT * FROM users WHERE status > 0 ORDER BY username";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    echo "<select name='inc[]' multiple='multiple' size='20'>";
    while ($row = mysql_fetch_object($result))
    {
        echo "<option value='{$row->id}'>$row->realname</option>\n";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>\n";
    echo "<tr><th align='right' width='200'><strong>Output</strong>:</td>";
    echo "<td width='400'>";
    echo "<select name='output'>";
    echo "<option value='screen'>Screen</option>";
    echo "<option value='csv'>Disk - Comma Seperated (CSV) file</option>";
    echo "</select>";
    echo "</td></tr>";
    echo "<tr><th align='right' width='200'>Statistics only</th><td><input type='checkbox' name='statistics' /></td></tr>";
    echo "</table>";
    echo "<p align='center'>";
    echo "<input type='hidden' name='table1' value='{$_POST['table1']}' />";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='submit' value='Run Report' />";
    echo "</p>";
    echo "</form>";
    include('htmlfooter.inc.php');
}
elseif ($_REQUEST['statistics'] == 'on')
{
    $startdate = strtotime($_POST['startdate']);
    $enddate = strtotime($_POST['enddate']);
    $type = $_POST['type'];
    if (is_array($_POST['exc']) && is_array($_POST['exc'])) $_POST['inc']=array_values(array_diff($_POST['inc'],$_POST['exc']));  // don't include anything excluded
    $includecount=count($_POST['inc']);
    if ($includecount >= 1)
    {
        // $html .= "<strong>Include:</strong><br />";
        $incsql .= "(";
        $incsql_esc .= "(";
        for ($i = 0; $i < $includecount; $i++)
        {
            // $html .= "{$_POST['inc'][$i]} <br />";
            $incsql .= "users.id={$_POST['inc'][$i]}";
            $incsql_esc .= "incidents.owner={$_POST['inc'][$i]}";
            if ($i < ($includecount-1)) $incsql .= " OR ";
            if ($i < ($includecount-1)) $incsql_esc .= " OR ";
        }
        $incsql .= ")";
        $incsql_esc .= ")";
    }

    $sql = "SELECT COUNT(DISTINCT incidents.id) AS numberOpened, users.id, users.realname ";
    $sql .= "FROM users, incidents ";
    $sql .= "WHERE users.id=incidents.owner AND incidents.opened >= {$startdate} AND incidents.opened <= {$enddate} "; 
    //$sql .= "WHERE users.id=incidents.owner AND incidents.opened > ($now-60*60*24*365.25) ";
    /*$sql .= "WHERE users.id=incidents.owner "; // AND incidents.opened > ($now-60*60*24*365.25) ";
    if($type == "opened")
    {
        $sql .= " AND incidents.opened >= {$startdate} AND incidents.opened <= {$enddate} ";
    }
    else if($type == "closed")
    {
        $sql .= " AND incidents.closed >= {$startdate} AND incidents.closed <= {$enddate} ";
    }
    else if($type == "both")
    {
        $sql .= " AND ((incidents.opened >= {$startdate} AND incidents.opened <= {$enddate}) ";
        $sql .= " OR (incidents.closed >= {$startdate} AND incidents.closed <= {$enddate})) ";
    }*/

    if (empty($incsql)==FALSE OR empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql)==FALSE AND empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " GROUP BY users.id ";

    //echo $sql;

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $numrows = mysql_num_rows($result);

    $totalOpened = 0;
    if($numrows > 0)
    {
        while($obj = mysql_fetch_object($result))
        {
            $data[$obj->id]['realname'] = $obj->realname;
            $data[$obj->id]['opened'] = $obj->numberOpened;
            $totalOpened += $obj->numberOpened;
        }
    }

    //
    //    CLOSED
    //

    $sql = "SELECT COUNT(incidents.id) AS numberClosed, users.id, users.realname ";
    $sql .= "FROM users, incidents ";
    $sql .= "WHERE users.id=incidents.owner"; //AND incidents.closed > ($now-60*60*24*365.25) ";
    $sql .= " AND incidents.closed >= {$startdate} AND incidents.closed <= {$enddate} ";

    if (empty($incsql)==FALSE OR empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql)==FALSE AND empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " GROUP BY users.id ";

    //echo $sql;

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $numrows = mysql_num_rows($result);

    $totalClosed = 0;
    if($numrows > 0)
    {
        while($obj = mysql_fetch_object($result))
        {
            $data[$obj->id]['realname'] = $obj->realname;
            $data[$obj->id]['closed'] = $obj->numberClosed;
            $totalClosed += $obj->numberClosed;
        }
    }

    //mysqldump version
    // Escalated
    //
    $sql = "SELECT COUNT(DISTINCT(incidentid)) AS numberEscalated, users.id, users.realname FROM updates, incidents,users WHERE  users.id=incidents.owner AND updates.incidentid = incidents.id  AND updates.bodytext LIKE \"External ID%\"";
    if($type == "opened")
    {
        $sql .= " AND incidents.opened >= {$startdate} AND incidents.opened <= {$enddate} ";
    }
    else if($type == "closed")
    {
        $sql .= " AND incidents.closed >= {$startdate} AND incidents.closed <= {$enddate} ";
    }
    else if($type == "both")
    {
        $sql .= " AND ((incidents.opened >= {$startdate} AND incidents.opened <= {$enddate}) ";
        $sql .= " OR (incidents.closed >= {$startdate} AND incidents.closed <= {$enddate})) ";
    }
    if (empty($incsql)==FALSE OR empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql)==FALSE AND empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " GROUP BY users.id ";

    //echo $sql;

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $numrows = mysql_num_rows($result);

    $totalEscalated = 0;
    if($numrows > 0)
    {
        while($obj = mysql_fetch_object($result))
        {
            $data[$obj->id]['realname'] = $obj->realname;
            $data[$obj->id]['escalated'] = $obj->numberEscalated;
            $totalEscalated += $obj->numberEscalated;
        }
    }


    /*echo "<pre>";
    print_r($data);
    echo "</pre>";*/

    if(sizeof($data) > 0)
    {
        $html .= "<table align='center'>";
        $html .= "<tr>";
        $html .= "<th>Engineer name</th>";
        $html .= "<th>Assigned</th>";
        $html .= "<th>Escalated</th>";
        $html .= "<th>Closed</th>";
        $html .= "<th>Avg Assigned (Month)</th>";
        $html .= "<th>Avg Escalated (Month)</th>";
        $html .= "<th>Avg Closed (Month)</th>";
        $html .= "<th>Percentage escalated</th>";
        $html .= "<tr>";

        $csv .= "Engineer name,Assigned,Escalated,Closed,Avg Assigned (Month),Avg Escalated (Month),";
        $csv .= "Avg Closed (Month),Percentage escalated\n";

        $class="class='shade1'";
        foreach($data AS $engineer)
        {
            $html .= "<tr>";
            $html .= "<td {$class}>".$engineer['realname']."</td>";
            if(empty($engineer['opened'])) $open = 0;
            else $open = $engineer['opened'];
            $html .= "<td {$class}>{$open}</td>";
            if(empty($engineer['escalated'])) $escalated = 0;
            else $escalated = $engineer['escalated'];
            $html .= "<td {$class}>{$escalated}</td>";
            if(empty($engineer['closed'])) $closed = 0;
            else $closed = $engineer['closed'];
            $html .= "<td {$class}>{$closed}</td>";
            $html .= "<td {$class}>".round($engineer['opened']/12,2)."</td>"; //The average over a 12mnth period
            $html .= "<td {$class}>".round($engineer['escalated']/12,2)."</td>"; //The average over a 12mnth period
            $html .= "<td {$class}>".round($engineer['closed']/12,2)."</td>"; //The average over a 12mnth period
            $html .= "<td {$class}>".round(($engineer['escalated']/$engineer['opened'])*100,2)."%</td>";
            $html .= "</tr>";

            $csv .= $engineer['realname'].",";
            $csv .= "{$opened},";
            $csv .= "{$escalated},";
            $csv .= "{$closed},";
            $csv .= round($engineer['opened']/12,2).","; //The average over a 12mnth period
            $csv .= round($engineer['escalated']/12,2).","; //The average over a 12mnth period
            $csv .= round($engineer['closed']/12,2).","; //The average over a 12mnth period
            $csv .= round(($engineer['escalated']/$engineer['opened'])*100,2)."%\n";


            if($class=="class='shade1'") $class="class='shade2'";
            else $class="class='shade1'";
        }
        $html .= "<tr>";
        $html .= "<td {$class} align='right'><super>TOTALS:</super></td>";
        $html .= "<td {$class}>$totalOpened</td>";
        $html .= "<td {$class}>$totalEscalated</td>";
        $html .= "<td {$class}>$totalClosed</td>";
        $html .= "<td {$class}>".round($totalOpened/12,2)."</td>"; //The average over a 12mnth period
        $html .= "<td {$class}>".round($totalEscalated/12,2)."</td>"; //The average over a 12mnth period
        $html .= "<td {$class}>".round($totalClosed/12,2)."</td>"; //The average over a 12mnth period
        $html .= "<td {$class}>".round(($totalEscalated/$totalOpened)*100,2)."%</td>";
        $html .= "</tr>";
        $html .= "</table>";

        $csv .= "TOTALS:,";
        $csv .= $totalOpened.",";
        $csv .= $totalEscalated.",";
        $csv .= $totalClosed.",";
        $csv .= round($totalOpened/12,2).","; //The average over a 12mnth period
        $csv .= round($totalEscalated/12,2).","; //The average over a 12mnth period
        $csv .= round($totalClosed/12,2).","; //The average over a 12mnth period
        $csv .= round(($totalEscalated/$totalOpened)*100,2)."%\n";


        $html .= "<p align='center'>The statistics are approximation only. They don't take into consideration incidents reassigned</p>";
        $csv .= "The statistics are approximation only. They don't take into consideration incidents reassigned\n";


    }

    if ($_POST['output']=='screen')
    {
        include('htmlheader.inc.php');
        echo "<h2>Engineer statistics for past year</h2>";
        echo $html;
        include('htmlfooter.inc.php');
    }
    elseif ($_POST['output']=='csv')
    {
        // --- CSV File HTTP Header
        header("Content-type: text/csv\r\n");
        header("Content-disposition-type: attachment\r\n");
        header("Content-disposition: filename=yearly_incidents.csv");
        echo $csv;
    }
}
elseif ($_REQUEST['mode']=='report')
{
    $startdate = strtotime($_POST['startdate']);
    $enddate = strtotime($_POST['enddate']);
    $type = $_POST['type'];
    if (is_array($_POST['exc']) && is_array($_POST['exc'])) $_POST['inc']=array_values(array_diff($_POST['inc'],$_POST['exc']));  // don't include anything excluded
    $includecount=count($_POST['inc']);
    if ($includecount >= 1)
    {
        // $html .= "<strong>Include:</strong><br />";
        $incsql .= "(";
	    $incsql_esc .= "(";
        for ($i = 0; $i < $includecount; $i++)
        {
            // $html .= "<strong>Include:</strong><br />";
            $incsql .= "(";
	        $incsql_esc .= "(";
            for ($i = 0; $i < $includecount; $i++)
            {
                // $html .= "{$_POST['inc'][$i]} <br />";
                $incsql .= "users.id={$_POST['inc'][$i]}";
		        $incsql_esc .= "incidents.owner={$_POST['inc'][$i]}";
                if ($i < ($includecount-1)) $incsql .= " OR ";
		        if ($i < ($includecount-1)) $incsql_esc .= " OR ";
            }
            $incsql .= ")";
	        $incsql_esc .= ")";
        }
        $incsql .= ")";
        $incsql_esc .= ")";
    }
//
    $sql = "SELECT incidents.id AS incid, incidents.title AS title,users.realname AS realname, users.id AS userid, ";
    $sql .= "incidents.opened AS opened, incidents.closed AS closed FROM users, incidents ";
    $sql .= "WHERE users.id=incidents.owner "; // AND incidents.opened > ($now-60*60*24*365.25) ";
    if($type == "opened")
    {
        $sql .= " AND incidents.opened >= {$startdate} AND incidents.opened <= {$enddate} ";
    }
    else if($type == "closed")
    {
        $sql .= " AND incidents.closed >= {$startdate} AND incidents.closed <= {$enddate} ";
    }
    else if($type == "both")
    {
        $sql .= " AND ((incidents.opened >= {$startdate} AND incidents.opened <= {$enddate}) ";
        $sql .= " OR (incidents.closed >= {$startdate} AND incidents.closed <= {$enddate})) ";
    }



    if (empty($incsql)==FALSE OR empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql)==FALSE AND empty($excsql)==FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " ORDER BY realname, incidents.id ASC ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error: $sql ".mysql_error(), E_USER_ERROR);
    $numrows = mysql_num_rows($result);

    //$sql_esc = "SELECT distinct(incidentid) AS incid FROM updates, incidents WHERE updates.incidentid = incidents.id AND incidents.opened > ($now-60*60*24*365.25)  AND updates.bodytext LIKE \"External ID%\"";
    $sql_esc = "SELECT distinct(incidentid) AS incid FROM updates, incidents WHERE updates.incidentid = incidents.id AND updates.bodytext LIKE \"External ID%\" ";
    if($type == "opened")
    {
        $sql_esc .= " AND incidents.opened >= {$startdate} AND incidents.opened <= {$enddate} ";
    }
    else if($type == "closed")
    {
        $sql_esc .= " AND incidents.closed >= {$startdate} AND incidents.closed <= {$enddate} ";
    }
    else if($type == "both")
    {
        $sql_esc .= " AND ((incidents.opened >= {$startdate} AND incidents.opened <= {$enddate}) ";
        $sql_esc .= " OR (incidents.closed >= {$startdate} AND incidents.closed <= {$enddate})) ";
    }


    if (empty($incsql_esc)==FALSE OR empty($excsql)==FALSE) $sql_esc .= " AND ";
    if (!empty($incsql)) $sql_esc .= "$incsql_esc";
    if (empty($incsql_sc)==FALSE AND empty($excsql)==FALSE) $sql_esc .= " AND ";
    if (!empty($excsql)) $sql_esc .= "$excsql";

    $sql_esc .= " GROUP BY incidentid";

    $result_esc = mysql_query($sql_esc);
    if (mysql_error()) throw_error("!Error: MySQL Query Error in ($sql_esc)",mysql_error());
    $numrows_esc = mysql_num_rows($result_esc);

    $escalated_array = array($numrows_esc);
    $count = 0;
    while($row = mysql_fetch_object($result_esc)){
        $escalated_array[$count] = $row->incid;
        $count++;
    }


    $html .= "<p align='center'>This report is a list of ($numrows) incidents for your selections of which ($numrows_esc) where escalated</p>";
    $html .= "<table width='99%' align='center'>";
    $html .= "<tr><th>Opened</th><th>Closed</th><th>Incident</th><th>Title</th><th>Engineer</th><th>Escalated</th></tr>";
    $csvfieldheaders .= "opened,closed,id,title,engineer,escalated\r\n";
    $rowcount=0;
    while ($row = mysql_fetch_object($result))
    {
        $nicedate=date('d/m/Y',$row->opened);
        $niceclose = date('d/m/Y',$row->closed);
	$ext = external_escalation($escalated_array, $row->incid);
        $html .= "<tr class='shade2'><td>$nicedate</td><td>{$niceclose}</td><td><a href='../incident_details.php?id={$row->incid}'>{$row->incid}</a></td><td>{$row->title}</td><td>{$row->realname}</td><td>$ext</td></tr>";
        $csv .="'".$nicedate."','".$niceclose."', '{$row->incid}','{$row->title}','{$row->realname},'$ext'\n";
    }
    $html .= "</table>";

    //  $html .= "<p align='center'>SQL Query used to produce this report:<br /><code>$sql</code></p>\n";

    if ($_POST['output']=='screen')
    {
        include('htmlheader.inc.php');
        echo $html;
        include('htmlfooter.inc.php');
    }
    elseif ($_POST['output']=='csv')
    {
        // --- CSV File HTTP Header
        header("Content-type: text/csv\r\n");
        header("Content-disposition-type: attachment\r\n");
        header("Content-disposition: filename=yearly_incidents.csv");
        echo $csvfieldheaders;
        echo $csv;
    }
}
?>
