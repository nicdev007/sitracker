<?php
// scheduler.php - List and allow editing of scheduled actions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2008 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Paul Heaney <paulheaney[at]users.sourceforge.net>

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
            echo "<tr><th><label for='status'>{$strStatus}</label>".help_link('SchedulerStatus')."</th>";
            $statuslist = array('enabled' => $strEnabled ,'disabled' => $strDisabled);
            echo "<td>".array_drop_down($statuslist, 'status', $saction->status);
            echo "</td></tr>\n";
            if (!empty($saction->paramslabel))
            {
                echo "<tr><th><label for='params'>{$strParameters}</label>".help_link('SchedulerStatus')."</th>"; // FIXME incorrect help
                echo "<td>{$saction->paramslabel}: <input type='text' id='params' name='params' value='{$saction->params}' size='15' maxlength='255' />";
                echo "</tr>";
            }
            echo "<tr><th><label for='startdate'>{$strStartDate}</label></th>";
            $startdate = date('Y-m-d',mysql2date($saction->start));
            $starttime = date('H:i',mysql2date($saction->start));
            echo "<td><input type='text' id='startdate' name='startdate' value='{$startdate}' size='10' /> ";
            echo date_picker('scheduleform.startdate');
            echo " <input type='text' id='starttime' name='starttime' value='{$starttime}' size='5' /> ";
            echo "</td></tr>\n";
            echo "<tr><th><label for='enddate'>{$strEndDate}</label></th>";
            if (mysql2date($saction->end) > 0)
            {
                $enddate = date('Y-m-d',mysql2date($saction->end));
            }
            else
            {
                $enddate = '';
            }
            
            if (mysql2date($saction->end) > 0)
            {
                $endtime = date('H:i',mysql2date($saction->end));
            }
            else
            {
                $endtime = '';
            }
            
            echo "<td><input type='text' id='enddate' name='enddate' value='{$enddate}' size='10' /> ";
            echo date_picker('scheduleform.enddate');
            echo " <input type='text' id='endtime' name='endtime' value='{$endtime}' size='5' /> ";
            echo "</td></tr>\n";
            
            echo "<tr>";
            echo "<th>{$strType}</th><td>";
            echo "<input type='radio' name='type' value='interval' id='interval' onclick=\"$('intervalsection').show(); $('datesection').hide();\" checked='checked' />{$strInterval} ";
            echo "<input type='radio' name='type' value='date' id='date' onclick=\"$('intervalsection').hide(); $('datesection').show();\" />{$strDate} ";
            echo "</td></tr>";
            
            echo "<tbody id='intervalsection'>";
            echo "<tr><th><label for='interval'>{$strInterval}</label></th>";
            echo "<td><input type='text' id='interval' name='interval' value='{$saction->interval}' size='5' /> ({$strSeconds})";
            echo "</td></tr>\n";
            echo "</tbody>";
            
            echo "<tbody id='datesection' style='display:none'>";
            // date_type - month, year
            // date_offset
            // date_time
            echo "<tr><th>{$strFrequency}</th><td>";
            
            if (empty($saction->date_type) OR empty($saction->date_type) == 'month')
            {
                $month = " checked='yes'"; // TODO figure this out
            }
            else
            {
                $year = " checked='yes'"; // TODO figure this out
            }
            
            echo "<select name='frequency'><option value='month' {$month}>{$strMonthly}</option>";
            echo "<option value='year' {$year}>{$strYearly}</option></select>";
            echo "</td></tr>";
            echo "<tr><th>{$strOffset}</th><td><input type='text' id='date_offset' name='date_offset' value='{$saction->date_offset}' size='5' /> ({$strDays})</td></tr>";
            echo "<tr><th>{$strTime}</th><td>";
            //<input type='text' id='date_time' name='date_time' value='{$saction->date_time}' size='5' />
            // TODO select based on DB
            echo "<select name='date_time' id='date_time'>";
            echo "<option value='0'>{$strMidnight}</option>";
            echo "<option value='1'>1:00 AM</option>";
            echo "<option value='2'>2:00 AM</option>";
            echo "<option value='3'>3:00 AM</option>";
            echo "<option value='4'>4:00 AM</option>";
            echo "<option value='5'>5:00 AM</option>";
            echo "<option value='6'>6:00 AM</option>";
            echo "<option value='7'>7:00 AM</option>";
            echo "<option value='8'>8:00 AM</option>";
            echo "<option value='9'>9:00 AM</option>";
            echo "<option value='10'>10:00 AM</option>";
            echo "<option value='11'>11:00 AM</option>";
            echo "<option value='12'>12:00 PM</option>";
            echo "<option value='13'>1:00 PM</option>";
            echo "<option value='14'>2:00 PM</option>";
            echo "<option value='15'>3:00 PM</option>";
            echo "<option value='16'>4:00 PM</option>";
            echo "<option value='17'>5:00 PM</option>";
            echo "<option value='18'>6:00 PM</option>";
            echo "<option value='19'>7:00 PM</option>";
            echo "<option value='20'>8:00 PM</option>";
            echo "<option value='21'>9:00 PM</option>";
            echo "<option value='22'>10:00 PM</option>";
            echo "<option value='23'>11:00 PM</option>";
            echo "</select>";
    
            echo "</td></tr>";
            echo "</tbody>";
            
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
        
        if (!empty($_REQUEST['startdate']))
        {
            echo "moo";
            $start = strtotime($_REQUEST['startdate'].' '.$_REQUEST['starttime']);
            $start = date('Y-m-d H:i', $start);
        }
        else
        {
            $start = date('Y-m-d H:i', $now);
        }
        
        if (!empty($_REQUEST['enddate']))
        {
            $end = strtotime($_REQUEST['enddate'].' '.$_REQUEST['endtime']);
            $end = date('Y-m-d H:i', $end);
        }
        else
        {
            $end = '0000-00-00 00:00';
        }
        
        $status = cleanvar($_REQUEST['status']);
        $params = cleanvar($_REQUEST['params']);
        $interval = cleanvar($_REQUEST['interval']);
        if ($interval <= 0)
        {
            $status = 'disabled';
            $interval = 0;
        }
        $type = cleanvar($_REQUEST['type']);
        $frequency = cleanvar($_REQUEST['frequency']);
        $date_offset = cleanvar($_REQUEST['date_offset']);
        $date_time = cleanvar($_REQUEST['date_time']);
        
        if ($date_time < 10) $date_time = "0{$date_time}:00:00";
        else "{$date_time}:00:00";
        
        if ($type == 'interval')
        {
            $setsql = " `interval` = '{$interval}', `type` = 'interval'  ";
        }
        elseif ($type == 'date')
        {
            $setsql = " `date_type` = '{$frequency}', `date_offset` = '{$date_offset}', ";
            $setsql .= "`date_time` = '{$date_time}', `type` = 'date' ";
        }

        $sql = "UPDATE `{$dbScheduler}` SET `status`='{$status}', `start`='{$start}', `end`='{$end}', {$setsql} ";
        if ($status = 'enabled')
        {
            $sql .= " , `success` = '1'";
        }
        
        if (!empty($params))
        {
            $sql .= " , `params` = '{$params}'";
        }
        $sql .= " WHERE `id` = $id LIMIT 1";

        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], TRUE);
        }
    break;

    case 'list':
    default:
        $refresh = 60;
        include ('htmlheader.inc.php');
        echo "<h2>{$strScheduler}</h2>";
        echo "<h3>".ldate($CONFIG['dateformat_datetime'], $GLOBALS['now'], FALSE)."</h3>";
        $sql = "SELECT * FROM `{$dbScheduler}` ORDER BY action";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) >= 1)
        {
            echo "<table align='center'>";
            echo "<tr><th>{$strAction}</th><th>{$strStartDate}</th><th>{$strInterval}</th>";
            echo "<th>{$strEndDate}</th><th>{$strLastRan}</th><th>Next Run</th></tr>\n";
            $shade = 'shade1';
            while ($schedule = mysql_fetch_object($result))
            {
                $lastruntime = mysql2date($schedule->lastran);
                if ($schedule->success == 0)
                {
                    $shade = 'critical';
                }
                elseif ($schedule->status == 'disabled')
                {
                    $shade = 'expired';
                }
                elseif ($lastruntime > 0 AND $lastruntime + $schedule->interval < $now)
                {
                    $shade = 'notice';
                }
                
                echo "<tr class='{$shade}'>";
                echo "<td><a class='info' href='{$_SERVER['PHP_SELF']}?mode=edit&amp;id={$schedule->id}'>{$schedule->action}";
                echo "<span>";
                echo "{$schedule->description}";
                if (!empty($schedule->params))
                {
                    echo "\n<br /><strong>{$schedule->paramslabel} = {$schedule->params}</strong>";
                }
                
                echo "</span></a></td>";
                echo "<td>{$schedule->start}</td>";
                if ($schedule->type == 'interval')
                {
                    echo "<td>{$strEvery} ".format_seconds($schedule->interval)."</td>";
                }
                elseif ($schedule->type == 'date')
                {
                    echo "<td>";
                    switch ($schedule->date_offset)
                    {
                        case 1: echo $str1st;
                            break;
                        case 2: echo $str2nd;
                            break;
                        case 3: echo $str3rd;
                            break;
                        default:
                            echo sprintf($strXth, $schedule->date_offset);
                            break;
                    }
                    
                    if ($schedule->date_type == 'month')
                    {
                        echo " of month";
                    }
                    elseif ($schedule->date_type == 'year')
                    {
                        echo " of year";
                    }
                    echo "</td>";
                }
                echo "<td>";
                if (mysql2date($schedule->end) > 0) echo "{$schedule->end}";
                else echo "-";
                echo "</td>";
                echo "<td>";
                $lastruntime = mysql2date($schedule->lastran);
                if ($lastruntime > 0)
                {
                    echo ldate($CONFIG['dateformat_datetime'], $lastruntime);
                }
                    
                else echo $strNever;
                echo "</td>";
                echo "<td>";
                if ($schedule->status == 'enabled')
                {
                    if ($lastruntime > 0)
                    {
                        $nextruntime = $lastruntime + $schedule->interval;
                    }
                    else
                    {
                        $nextruntime = $now;
                    }
                    
                    echo ldate($CONFIG['dateformat_datetime'],$nextruntime);
                }                
                else
                {
                    echo $strNever;
                }
                
                echo "</td>";
                echo "</tr>";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>\n";
            echo "<p align='center'>".help_link('Scheduler')."</p>";

            // TODO add a check to see if any of the above actions are long overdue, if they are
            // print a message explaining how to set up cron/scheduling
        }

        include ('htmlfooter.inc.php');
}

?>