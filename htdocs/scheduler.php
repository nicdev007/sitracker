<?php
// scheduler.php - List and allow editing of scheduled actions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2008 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

@include ('set_include_path.inc.php');
$permission = 22; // Admin

require ('db_connect.inc.php');
require ('functions.inc.php');

// This page requires authentication
require ('auth.inc.php');

// External vars
$id = cleanvar($_REQUEST['id']);


switch ($_REQUEST['mode'])
{
    case 'edit':
        $sql = "SELECT * FROM `{$dbScheduler}` WHERE id = $id LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            $saction = mysql_fetch_object($result);
            include ('htmlheader.inc.php');
            echo "<h2>{$strScheduler}</h2>";
            echo "<form name='scheduleform' action='{$_SERVER['PHP_SELF']}' method='post'>";
            echo "<table class='vertical' width='350'>";
            echo "<tr><th>{$strAction}</th>";
            echo "<td><strong>{$saction->action}</strong><br />{$saction->description}</td></tr>\n";
            echo "<tr><th><label for='status'>{$strStatus}</label></th>";
            $statuslist = array('enabled' => $strEnabled ,'disabled' => $strDisabled);
            echo "<td>".array_drop_down($statuslist, 'status', $saction->status);
            echo "</td></tr>\n";
            echo "<tr><th><label for='startdate'>{$strStartDate}</label></th>";
            $startdate = date('Y-m-d',mysql2date($saction->start));
            $starttime = date('H:i',mysql2date($saction->start));
            echo "<td><input type='text' id='startdate' name='startdate' value='{$startdate}' size='10' /> ";
            echo date_picker('scheduleform.startdate');
            echo " <input type='text' id='starttime' name='starttime' value='{$starttime}' size='5' /> ";
            echo "</td></tr>\n";
            echo "<tr><th><label for='enddate'>{$strEndDate}</label></th>";
            if (mysql2date($saction->end) > 0) $enddate = date('Y-m-d',mysql2date($saction->end));
            else $enddate = '';
            if (mysql2date($saction->end) > 0) $endtime = date('H:i',mysql2date($saction->end));
            else $endtime = '';
            echo "<td><input type='text' id='enddate' name='enddate' value='{$enddate}' size='10' /> ";
            echo date_picker('scheduleform.enddate');
            echo " <input type='text' id='endtime' name='endtime' value='{$endtime}' size='5' /> ";
            echo "</td></tr>\n";
            echo "<tr><th><label for='interval'>{$strInterval}</label></th>";
            echo "<td><input type='text' id='interval' name='interval' value='{$saction->interval}' size='5' /> ({$strSeconds})";
            echo "</td></tr>\n";
            echo "</table>";
            echo "<input type='hidden' name='mode' value='save' />";
            echo "<input type='hidden' name='id' value='{$id}' />";
            echo "<p><input type='reset' value=\"{$strReset}\" /> <input type='submit' value=\"{$strSave}\" /></p>";
            echo "</form>";
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}'>{$strReturnWithoutSaving}</a></p>";
            include ('htmlfooter.inc.php');
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
    break;

    case 'save':
        $start = strtotime($_REQUEST['startdate'].' '.$_REQUEST['starttime']);
        if ($start > 0) $start = date('Y-m-d H:i', $start);
        else $tart = $now;
        $end = strtotime($_REQUEST['enddate'].' '.$_REQUEST['endtime']);
        if ($end > 0) $end = date('Y-m-d H:i', $end);
        else $end = '0000-00-00 00:00';

        $status = cleanvar($_REQUEST['status']);
        $interval = cleanvar($_REQUEST['interval']);
        if ($interval <= 0)
        {
            $status = 'disabled';
            $interval = 0;
        }

        $sql = "UPDATE `{$dbScheduler}` SET `status`='{$status}', `start`='{$start}', `end`='{$end}', `interval`='{$interval}'";
        if ($status = 'enabled')
        {
            $sql .= " , `success` = '1'";
        }
        $sql .= " WHERE `id` = $id LIMIT 1";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
        else html_redirect($_SERVER['PHP_SELF'], TRUE);
    break;

    case 'list':
    default:
        include ('htmlheader.inc.php');
        echo "<h2>{$strScheduler}</h2>";

        $sql = "SELECT * FROM `{$dbScheduler}` ORDER BY action";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) >= 1)
        {
            echo "<table align='center'>";
            echo "<tr><th>{$strAction}</th><th>{$strStartDate}</th><th>{$strInterval}</th><th>{$strEndDate}</th><th>{$strLastRan}</th></tr>\n";
            $shade = 'shade1';
            while ($schedule = mysql_fetch_object($result))
            {
                $lastruntime = mysql2date($schedule->lastran);
                if ($schedule->success == 0) $shade = 'critical';
                elseif ($schedule->status == 'disabled') $shade = 'expired';
                elseif ($lastruntime + $schedule->interval < $now) $shade = 'notice';
                echo "<tr class='{$shade}'>";
                echo "<td><a class='info' href='{$_SERVER['PHP_SELF']}?mode=edit&amp;id={$schedule->id}'>{$schedule->action}<span>{$schedule->description}</span></a></td>";
                echo "<td>{$schedule->start}</td>";
                echo "<td>".format_seconds($schedule->interval)."</td>";
                echo "<td>";
                if (mysql2date($schedule->end) > 0) echo "{$schedule->end}";
                else echo "-";
                echo "</td>";
                echo "<td>";
                $lastruntime = mysql2date($schedule->lastran);
                if ($lastruntime > 0) echo "{$schedule->lastran}";
                else echo $strNever;
                echo "</td>";
                echo "</tr>";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>\n";
        }
        $actions = schedule_actions_due();
        echo "<pre>".print_r($actions,true)."</pre>";
        include ('htmlfooter.inc.php');

}


?>