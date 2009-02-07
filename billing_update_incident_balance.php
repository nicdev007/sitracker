<?php
// ???
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>

$lib_path = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
$permission = 80; //Set -ve balances

require_once ($lib_path.'db_connect.inc.php');
require_once ($lib_path.'functions.inc.php');
// This page requires authentication
require_once ($lib_path.'auth.inc.php');
require_once ($lib_path.'billing.inc.php');

$mode = cleanvar($_REQUEST['mode']);
$incidentid = cleanvar($_REQUEST['incidentid']);

if (empty($mode))
{
    include ('../inc/htmlheader.inc.php');

    echo "<h2>".sprintf($strUpdateIncidentXsBalance, $incidentid)."</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='modifyincidentbalance'>";

    echo "<table class='vertical'><tr><td>{$strAmount}<br />{$strForRefundsThisShouldBeNegative}</td><td>";
    echo "<input type='text' name='amount' id='amount' size='10' /> {$strMinutes}</td></tr>";

    echo "<tr><td>{$strDescription}</td><td>";
    echo "<textarea cols='40' name='description' rows='5'></textarea>";
    echo "</tr>";

    echo "</table>";

    echo "<input type='hidden' id='incidentid' name='incidentid' value='{$incidentid}' />";
    echo "<input type='hidden' id='mode' name='mode' value='update' />";

    echo "<p align='center'><input type='submit' name='Sumbit' value='{$strUpdate}'  /></p>";

    echo "</form>";

    include ('../inc/htmlfooter.inc.php');
}
elseif ($mode == 'update')
{
    $amount = cleanvar($_REQUEST['amount']);
    $description = cleanvar($_REQUEST['description']);

    $sql = "SELECT closed, status, owner FROM `{$dbIncidents}` WHERE id = {$incidentid}";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);

        $description = "[b]Amount[/b]: {$amount} minutes\n\n{$description}";

        $amount *= 60; // to seconds
        $sqlInsert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, duration) VALUES ";
        $sqlInsert .= "('{$incidentid}', '{$sit[2]}', 'editing', '{$obj->owner}', '{$obj->status}', '{$description}', '{$now}', '{$amount}')";
        $resultInsert = mysql_query($sqlInsert);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (mysql_affected_rows() > 0) $success = TRUE;
        else $success = FALSE;

        if ($success)
        {
            $bills = get_incident_billable_breakdown_array($incidentid);

            $multipliers = get_all_available_multipliers();

            $totalunits = 0;
            $totalbillableunits = 0;
            $totalrefunds = 0;

            foreach ($bills AS $bill)
            {
                foreach ($multipliers AS $m)
                {
                    $a[$m] += $bill[$m]['count'];
                }
            }

            foreach ($multipliers AS $m)
            {
                $s .= sprintf($GLOBALS['strXUnitsAtX'], $a[$m], $m);
                $totalunits += $a[$m];
                $totalbillableunits += ($m * $a[$m]);
            }

            $unitrate = get_unit_rate(incident_maintid($incidentid));

            $totalrefunds = $bills['refunds'];
            // $numberofunits += $bills['refunds'];

            $cost = (($totalbillableunits + $totalrefunds)  * $unitrate) * -1;

            $desc = trim("{$numberofunits} {$strUnits} @ {$CONFIG['currency_symbol']}{$unitrate} for incident {$incidentid}. {$s}"); //FIXME i18n

            $transactionid = get_incident_transactionid($incidentid);
            if ($transactionid != FALSE)
            {
            	$r = update_transaction($transactionid, $cost, $desc, AWAITINGAPPROVAL);
                if ($r) html_redirect('../billable_incidents.php', TRUE, $strUpdateSuccessful);
                else html_redirect('../billable_incidents.php', FALSE, $strUpdateFailed);
            }
            else
            {
            	html_redirect('../billable_incidents.php', FALSE, $strUpdateFailed);
            }
        }
        else
        {
            html_redirect('../billable_incidents.php', FALSE, $strUpdateFailed);
        }
    }
    else
    {
        html_redirect('../billable_incidents.php', FALSE, "Failed to find date incident closed");
    }
}

?>