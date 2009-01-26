<?php
// billing.inc.php - functions relating to billing
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

define ("APPROVED", 0);
define ("AWAITINGAPPROVAL", 5);
define ("RESERVED", 10);

/**
* Returns if the contact has a timed contract or if the site does in the case of the contact not.
* @author Paul Heaney
* @return either NO_BILLABLE_CONTRACT, CONTACT_HAS_BILLABLE_CONTRACT or SITE_HAS_BILLABLE_CONTRACT the latter is if the site has a billable contract by the contact isn't a named contact
*/
function does_contact_have_billable_contract($contactid)
{
    global $now;
    $return = NO_BILLABLE_CONTRACT;

    $siteid = contact_siteid($contactid);
    $sql = "SELECT DISTINCT m.id FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE m.servicelevelid = sl.id AND sl.timed = 'yes' AND m.site = {$siteid} ";
    $sql .= "AND m.expirydate > {$now} AND m.term != 'yes'";
    $result = mysql_query($sql);

    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        // We have some billable/timed contracts
        $return = SITE_HAS_BILLABLE_CONTRACT;

        // check if the contact is listed on one of these

        while ($obj = mysql_fetch_object($result))
        {
            $sqlcontact = "SELECT * FROM `{$GLOBALS['dbSupportContacts']}` ";
            $sqlcontact .= "WHERE maintenanceid = {$obj->id} AND contactid = {$contactid}";

            $resultcontact = mysql_query($sqlcontact);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            if (mysql_num_rows($resultcontact) > 0)
            {
                $return = CONTACT_HAS_BILLABLE_CONTRACT;
                break;
            }
        }
    }

    return $return;
}


/**
* Gets the billable contract ID for a contact, if multiple exist then the first one is choosen
* @author Paul Heaney
* @param int $contactid - The contact ID you want to find the contract for
* @return int the ID of the contract, -1 if not found
*/
function get_billable_contract_id($contactid)
{
    global $now;

    $return = -1;

    $siteid = contact_siteid($contactid);
    $sql = "SELECT DISTINCT m.id FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE m.servicelevelid = sl.id AND sl.timed = 'yes' AND m.site = {$siteid} ";
    $sql .= "AND m.expirydate > {$now} AND m.term != 'yes'";

    $result = mysql_query($sql);

    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $return = mysql_fetch_object($result)->id;
    }

    return $return;
}


/**
* Returns the percentage remaining for ALL services on a contract
* @author Kieran Hogg
* @param string $mainid - contract ID
* @return mixed - percentage between 0 and 1 if services, FALSE if not
*/
function get_service_percentage($maintid)
{
    global $dbService;
    
    $sql = "SELECT * FROM `{$dbService}` ";
    $sql .= "WHERE contractid = '{$maintid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    
    if (mysql_num_rows($result) > 0)
    {
        $num = 0;
        while ($service = mysql_fetch_object($result))
        {
            $total += (float) $service->balance / (float) $service->creditamount;
            $num++;
        }
        $return = (float) $total / (float) $num;
    }
    else
    {
    	$return = FALSE;
    }

    return $return;
}


/**
 * Set the last billing time on a service
 * @param int $serviceid - service ID
 * @param string $date -  Date (in format YYYY-MM-DD) to set the last billing time to
 * @return boolean - TRUE if sucessfully updated, false otherwise
 */
function update_last_billed_time($serviceid, $date)
{
    global $dbService;

    $rtnvalue = FALSE;

    if (!empty($serviceid) AND !empty($date))
    {
        $rtnvalue = TRUE;
        $sql .= "UPDATE `{$dbService}` SET lastbilled = '{$date}' WHERE serviceid = {$serviceid}";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $rtnvalue = FALSE;
        }

        if (mysql_affected_rows() < 1)
        {
            trigger_error("Approval failed",E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}

/**
 * Find the billing multiple that should be applied given the day, time and matrix in use 
 * @author Paul Heaney
 * @param string $dayofweek 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' or 'holiday'
 * @return float - The applicable multiplier for the time of day and billing matrix being used 
*/
function get_billable_multiplier($dayofweek, $hour, $billingmatrix = 1)
{
    $sql = "SELECT `{$dayofweek}` AS rate FROM {$GLOBALS['dbBillingMatrix']} WHERE hour = {$hour} AND id = {$billingmatrix}";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    $rate = 1;

    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);
        $rate = $obj->rate;
    }

    return $rate;
}


/**
* Function to get an array of all billing multipliers for a billing matrix
* @author Paul Heaney
*/
function get_all_available_multipliers($matrixid=1)
{
    $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

    foreach ($days AS $d)
    {
        $sql = "SELECT DISTINCT({$d}) AS day FROM `{$GLOBALS['dbBillingMatrix']}` WHERE id = {$matrixid}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_WARNING);
            return FALSE;
        }

        while ($obj = mysql_fetch_object($result))
        {
            $a[$obj->day] = $obj->day;
        }
    }

    ksort($a);

    return $a;
}


/**
* Function to find the most applicable unit rate for a particular contract
* @author Paul Heaney
* @param $contractid - The contract id
* @param $date UNIX timestamp. The function will look for service that is current as of this timestamp
* @return int the unit rate, -1 if non found
*/
function get_unit_rate($contractid, $date='')
{
    $serviceid = get_serviceid($contractid, $date);

    if ($serviceid != -1)
    {
        $sql = "SELECT unitrate FROM `{$GLOBALS['dbService']}` AS p WHERE serviceid = {$serviceid}";

        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_WARNING);
            return FALSE;
        }

        $unitrate = -1;

        if (mysql_num_rows($result) > 0)
        {
            $obj = mysql_fetch_object($result);
            $unitrate = $obj->unitrate;
        }
    }
    else
    {
        $unitrate = -1;
    }

    return $unitrate;
}


/**
* @author Paul Heaney
* @param $contractid  The Contract ID
* @param $date  UNIX timestamp. The function will look for service that is current as of this timestamp
* @return mixed.     Service ID, or -1 if not found, or FALSE on error
*/
function get_serviceid($contractid, $date = '')
{
    global $now, $CONFIG;
    if (empty($date)) $date = $now;

    $sql = "SELECT serviceid FROM `{$GLOBALS['dbService']}` AS s ";
    $sql .= "WHERE contractid = {$contractid} AND UNIX_TIMESTAMP(startdate) <= {$date} ";
    $sql .= "AND UNIX_TIMESTAMP(enddate) > {$date} ";
    $sql .= "AND (balance > 0 OR (select count(1) FROM `{$GLOBALS['dbService']}` WHERE contractid = s.contractid and balance > 0) = 0) ";

    if (!$CONFIG['billing_allow_incident_approval_against_overdrawn_service'])
    {
        $sql .= "AND balance > 0 ";
    }

    $sql .= "ORDER BY priority DESC, enddate ASC, balance DESC LIMIT 1";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    $serviceid = -1;

    if (mysql_num_rows($result) > 0)
    {
        list($serviceid) = mysql_fetch_row($result);
    }

    return $serviceid;
}


/**
    * Get the current contract balance
    * @author Ivan Lucas
    * @param int $contractid. Contract ID of the contract to credit
    * @param bool $includenonapproved. Include incidents which have not been approved
    * @param bool $showonlycurrentlyvalue - Show only contracts which have valid NOW() - i.e. startdate less than NOW() and endate greate than NOW()
    * @param bool $includereserved - Deduct the reseved amount from the returned balance
    * @return int The total balance remaining on the contract
    * @note The balance is a sum of all the current service that have remaining balance
    * @todo FIXME add a param that makes this optionally show the incident pool balance
    in the case of non-timed type contracts
*/
function get_contract_balance($contractid, $includenonapproved = FALSE, $showonlycurrentlyvalid = TRUE, $includereserved = TRUE)
{
    global $dbService, $now;
    $balance = 0.00;

    $sql = "SELECT SUM(balance) FROM `{$dbService}` ";
    $sql .= "WHERE contractid = {$contractid} ";
    if ($showonlycurrentlyvalid)
    {
        $sql .= "AND UNIX_TIMESTAMP(startdate) <= {$now} ";
        $sql .= "AND UNIX_TIMESTAMP(enddate) >= {$now}  ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($balance) = mysql_fetch_row($result);

    if ($includenonapproved)
    {
        // Need to get sum of non approved incidents for this contract and deduct
        $balance -= contract_transaction_total($contractid, AWAITINGAPPROVAL);
    }
    
    if ($includereserved)
    {
    	$balance -= contract_transaction_total($contractid, RESERVED);
    }

    return $balance;
}


/**
 * Do the necessary tasks to billable incidents on closure, including creating transactions
 * @author Paul Heaney
 * @param int $incidentid The incident ID to do the close on, if its not a billable incident then no actions are performed
 * @return bool TRUE on sucessful closure, false otherwise
 */
function close_billable_incident($incidentid)
{
    global $now, $sit;
    $rtnvalue = TRUE;
	$sql = "SELECT i.maintenanceid FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE i.servicelevel = sl.tag AND i.priority = sl.priority AND i.id = {$incidentid} AND sl.timed = 'yes'";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("Error identifying if incident was timed ".mysql_error(), E_USER_WARNING);
        $rtnvalue = FALSE;
    }
    
    if (mysql_num_rows($result) > 0)
    {
    	//Was logged against a timed contract
        list($contractid) = mysql_fetch_row($result);
        $duration = 0;
        $sql = "SELECT SUM(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("Error getting duration for billable incident. ".mysql_error(), E_USER_WARNING);
            $rtnvalue = FALSE;
        }
        list($duration) = mysql_fetch_row($result);
        if ($duration > 0)
        {
        	// There where activities on this update so add to the transactions table
            
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
    
            // $rtn = update_contract_balance(incident_maintid($incidentid), $desc, $cost);
            
            // Add transaction
            $serviceid = get_serviceid($contractid);
            if ($serviceid < 1) trigger_error("Invalid service ID",E_USER_ERROR);
            $date = date('Y-m-d H:i:s', $now);
            
            $sql = "INSERT INTO `{$GLOBALS['dbTransactions']}` (serviceid, totalunits, totalbillableunits, totalrefunds, amount, description, userid, dateupdated, transactionstatus) ";
            $sql .= "VALUES ('{$serviceid}', '{$totalunits}',  '{$totalbillableunits}', '{$totalrefunds}', '{$cost}', '{$desc}', '{$_SESSION['userid']}', '{$date}', '".AWAITINGAPPROVAL."')";
    
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error("Error inserting transaction. ".mysql_error(), E_USER_WARNING);
                $rtnvalue = FALSE;
            }

            $transactionid = mysql_insert_id();
    
            if ($transactionid != FALSE)
            {
    
                $sql = "INSERT INTO `{$GLOBALS['dbLinks']}` VALUES (6, {$transactionid}, {$incidentid}, 'left', {$sit[2]})";
                mysql_query($sql);
                if (mysql_error())
                {
                    trigger_error(mysql_error(),E_USER_ERROR);
                    $rtnvalue = FALSE;
                }
                if (mysql_affected_rows() < 1)
                {
                    trigger_error("Approval failed",E_USER_ERROR);
                    $rtnvalue = FALSE;
                }
            }
            
        }
    }

    return $rtnvalue;
}

/**
* Function to approve an incident, this adds a transaction and confirms the 'bill' is correct.
* @author Paul Heaney
* @param incidentid ID of the incident to approve
*/
function approve_incident_transaction($transactionid)
{
    global $dbLinks, $sit, $CONFIG, $strUnits;

    $rtnvalue = TRUE;

    // Check transaction exists, and is awaiting approval and is an incident
    $sql = "SELECT l.linkcolref, t.serviceid FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
    $sql .= "WHERE t.transactionid = l.origcolref AND t.transactionstatus = ".AWAITINGAPPROVAL." AND l.linktype = 6 AND t.transactionid = {$transactionid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error identify incident transaction. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($incidentid, $serviceid) = mysql_fetch_row($result);
        
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
            $totalbillableunits += ($m * $a[$m]);
            $totalunits += $a[$m];
        }

        $unitrate = get_unit_rate(incident_maintid($incidentid));

//        $numberofunits += $bills['refunds'];
        $totalrefunds += $bills['refunds'];

        $cost = (($totalbillableunits += $totalrefunds) * $unitrate) * -1;

        $desc = trim("{$numberofunits} {$strUnits} @ {$CONFIG['currency_symbol']}{$unitrate} for incident {$incidentid}. {$s}"); //FIXME i18n

        $rtn = update_contract_balance(incident_maintid($incidentid), $desc, $cost, $serviceid, $transactionid, $totalunits, $totalbillableunits, $totalrefunds);

        if ($rtn != FALSE)
        {
            $rtnvalue = FALSE;
        }
    }
    else
    {
    	$rtnvalue = FALSE; 
    }

    return $rtnvalue;
}


/**
    * Update contract balance by an amount and log a transaction to record the change
    * @author Ivan Lucas
    * @param int $contractid. Contract ID of the contract to credit
    * @param string $description. A useful description of the transaction
    * @param float $amount. The amount to credit or debit to the contract balance
                    positive for credit and negative for debit
    * @param int $serviceid.    optional serviceid to use. This is calculated if ommitted.
    * @param int $transaction - the transaction you are approving
    * @param int $totalunits - The number of units that are being approved - before the multiplier
    * @param int $totalbillableunits - The number of units charged to the customer (after the multiplier)
    * @param int $totalrefunds - Total number of units refunded to the customer
    * @return boolean - status of the balance update
    * @note The actual service to credit will be calculated automatically if not specified
*/
function update_contract_balance($contractid, $description, $amount, $serviceid='', $transactionid, $totalunits, $totalbillableunits, $totalrefunds)
{
    global $now, $dbService, $dbTransactions;
    $rtnvalue = TRUE;

    if (empty($totalunits)) $totalunits = -1; 
    if (empty($totalbillableunits)) $totalbillableunits = -1;
    if (empty($totalrefunds)) $totalrefunds = 0;

    if ($serviceid == '')
    {
        // Find the correct service record to update
        $serviceid = get_serviceid($contractid);
        if ($serviceid < 1) trigger_error("Invalid service ID",E_USER_ERROR);
    }

    if (trim($amount) == '') $amount = 0;
    $date = date('Y-m-d H:i:s', $now);

    // Update the balance
    $sql = "UPDATE `{$dbService}` SET balance = (balance + {$amount}) WHERE serviceid = '{$serviceid}' LIMIT 1";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtnvalue = FALSE;
    }

    if (mysql_affected_rows() < 1 AND $amount != 0)
    {
        trigger_error("Contract balance update failed",E_USER_ERROR);
        $rtnvalue = FALSE;
    }

    if ($rtnvalue != FALSE)
    {
        // Log the transaction
        if (empty($transactionid))
        {
            $sql = "INSERT INTO `{$dbTransactions}` (serviceid, totalunits, totalbillableunits, totalrefunds, amount, description, userid, dateupdated, transactionstatus) ";
            $sql .= "VALUES ('{$serviceid}', '{$totalunits}', '{$totalbillableunits}', '{$totalrefunds}', '{$amount}', '{$description}', '{$_SESSION['userid']}', '{$date}', '".APPROVED."')";
            echo $sql;
            $result = mysql_query($sql);
    
            $rtnvalue = mysql_insert_id();
        }
        else
        {
            $sql = "UPDATE `{$dbTransactions}` SET serviceid = {$serviceid}, totalunits = {$totalunits}, totalbillableunits = {$totalbillableunits}, totalrefunds = {$totalrefunds} ";
            $sql .= ", amount = {$amount}, description = '{$description}', userid = {$_SESSION['userid']} , dateupdated = '{$date}', transactionstatus = '".APPROVED."' WHERE transactionid = {$transactionid}";
            $result = mysql_query($sql);
            $rtnvalue = $transactionid;
        }

        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $rtnvalue = FALSE;
        }
        if (mysql_affected_rows() < 1)
        {
            trigger_error("Transaction insert failed",E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}


/**
 * Gets the maintenanceID for a incident transaction
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to get the maintenance id from
 * @return int The maintenanceid or -1
 */
function maintid_from_transaction($transactionid)
{
    $rtnvalue = -1;
	$sql = "SELECT i.maintenanceid FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i WHERE ";
    $sql .= "l.origcolref = {$transactionid} AND l.linkcolref = i.id AND l.linktype = 6";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting maintid for transaction. ".mysql_error(), E_USER_WARNING);
    
    if (mysql_num_rows($result) > 0)
    {
    	list($rtnvalue) = mysql_fetch_row($result);
    }
    
    return $rtnvalue;
}


/**
 * Returns the total value of inicidents in a particular status
 * @author Paul Heaney
 * @param int $contractid. Contract ID of the contract to find total value of inicdents awaiting approval
 * @param int $status The type you are after e.g. AWAITINGAPPROVAL, APPROVED, RESERVED
 * @return int The total value of all incidents awaiting approval logged against the contract
 */
function contract_transaction_total($contractid, $status)
{
    $rtnvalue = FALSE;

    $sql = "SELECT SUM(t.amount) FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbService']}` AS s ";
    $sql .= "WHERE s.serviceid = t.serviceid AND s.contractid = {$contractid} AND t.transactionstatus = '{$status}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting total for type {$status}. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($rtnvalue) = mysql_fetch_row($result);
    }
    
    return $rtnvalue;
}


/**
 * Get the total of all transactions on a particular service of a certain type
 * @author Paul Heaney
 * @param int $serviceid The serviceID to report on
 * @param int $status The status' to get the transaction report for'
 * @return int The sum in currency of the transactons
 */
function service_transaction_total($serviceid, $status)
{
    $rtnvalue = FALSE;
	$sql = "SELECT SUM(amount) FROM `{$GLOBALS['dbTransactions']}` ";
    $sql .= "WHERE serviceid = {$serviceid} AND transactionstatus = '{$status}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting total for type {$status}. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($rtnvalue) = mysql_fetch_row($result);
    }
    
    return $rtnvalue;
}


/**
 * Get the current balance of a service
 * @author Paul Heaney
 * @param int $serviceid. Service ID of the service to get the balance for
 * @param int $includeawaitingapproval. Deduct the total awaiting approval from the balance
 * @param int $includereserved. Deduct the total reserved from the balance
 * @return int The remaining balance on the service
 * @todo Add param to take into account unapproved balances
 */
function get_service_balance($serviceid, $includeawaitingapproval = TRUE, $includereserved = TRUE)
{
    global $dbService;

    $sql = "SELECT balance FROM `{$dbService}` WHERE serviceid = {$serviceid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($balance) = mysql_fetch_row($result);

    if ($includeawaitingapproval)
    {
    	$balance -= service_transaction_total($serviceid, AWAITINGAPPROVAL);
    }
    
    if ($includereserved)
    {
    	$balance -= service_transaction_total($serviceid, RESERVED);
    }

    return $balance;
}


/**
* Function to identify if incident has been approved for billing
* @author Paul Heaney
* @return TRUE for approved, FALSE otherwise
*/
function is_billable_incident_approved($incidentid)
{
    $sql = "SELECT DISTINCT origcolref, linkcolref ";
    $sql .= "FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
    $sql .= "WHERE l.linktype = 6 ";
    $sql .= "AND l.origcolref = t.transactionid ";
    $sql .= "AND linkcolref = {$incidentid} ";
    $sql .= "AND direction = 'left' ";
    $sql .= "AND t.transactionstatus = '".APPROVED."'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}


/**
    * HTML table showing a summary of current contract service periods
    * @author Ivan Lucas
    * @param int $contractid. Contract ID of the contract to show service for
    * @returns string. HTML table
*/
function contract_service_table($contractid)
{
    global $CONFIG, $dbService;

    $sql = "SELECT * FROM `{$dbService}` WHERE contractid = {$contractid} ORDER BY enddate DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $shade = '';
        $html = "\n<table align='center'>";
        $html .= "<tr><th>{$GLOBALS['strStartDate']}</th><th>{$GLOBALS['strEndDate']}</th><th>{$GLOBALS['strRemainingBalance']}</th><th></th>";
        $html .= "</tr>\n";
        while ($service = mysql_fetch_object($result))
        {
            $service->startdate = mysql2date($service->startdate);
            $service->enddate = mysql2date($service->enddate);
            $service->lastbilled = mysql2date($service->lastbilled);
            $html .= "<tr class='$shade'>";
            $html .= "<td><a href='transactions.php?serviceid={$service->serviceid}' class='info'>".ldate($CONFIG['dateformat_date'],$service->startdate);

            $balance = get_service_balance($service->serviceid);
            $awaitingapproval = service_transaction_total($service->serviceid, AWAITINGAPPROVAL);
            $reserved = service_transaction_total($service->serviceid, RESERVED);

            $span = '';
            if (!empty($service->title))
            {
                $span .= "<strong>{$GLOBALS['strTitle']}</strong>: {$service->title}<br />";
            }

            if (!empty($service->notes))
            {
                $span .= "<strong>{$GLOBALS['strNotes']}</strong>: {$service->notes}<br />";
            }

            if (!empty($service->cust_ref))
            {
                $span .= "<strong>{$GLOBALS['strCustomerReference']}</strong>: {$service->cust_ref}";
                if ($service->cust_ref_date != "1970-01-01")
                {
                    $span .= " - <strong>{$GLOBALS['strCustomerReferenceDate']}</strong>: {$service->cust_ref_date}";
                }
                $span .= "<br />";
            }

            if ($service->creditamount != 0)
            {
                $span .= "<strong>{$GLOBALS['strAmount']}</strong>: {$CONFIG['currency_symbol']}".number_format($service->creditamount, 2)."<br />";
            }

            if ($service->unitrate != 0)
            {
                $span .= "<strong>{$GLOBALS['strUnitRate']}</strong>: {$CONFIG['currency_symbol']}{$service->unitrate}<br />";
            }

            if ($awaitingapproval != FALSE)
            {
                $span .= "<strong>{$GLOBALS['strAwaitingApproval']}<strong>: {$CONFIG['currency_symbol']}{$awaitingapproval}<br />";
            }
            
            if ($reserved != FALSE)
            {
                $span .= "<strong>{$GLOBALS['strReserved']}</strong>: {$CONFIG['currency_symbol']}{$reserved}<br />";
            }

            if ($service->lastbilled > 0)
            {
                $span .= "<strong>{$GLOBALS['strLastBilled']}</strong>: ".ldate($CONFIG['dateformat_date'], $service->lastbilled)."<br />";
            }

            if ($service->foc == 'yes')
            {
                $span .= "<strong>{$GLOBALS['strFreeOfCharge']}</strong>";
            }

            if (!empty($span))
            {
                    $html .= "<span>{$span}</span>";
            }

            $html .= "</a></td>";
            $html .= "<td>";
            $html .= ldate($CONFIG['dateformat_date'], $service->enddate)."</td>";

            $html .= "<td>{$CONFIG['currency_symbol']}".number_format($balance, 2)."</td>";
            $html .= "<td><a href='billing/edit_service.php?mode=editservice&amp;serviceid={$service->serviceid}&amp;contractid={$contractid}'>{$GLOBALS['strEditService']}</a> | ";
            $html .= "<a href='billing/edit_service.php?mode=showform&amp;sourceservice={$service->serviceid}&amp;contractid={$contractid}'>{$GLOBALS['strEditBalance']}</a></td>";
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    return $html;
}


/**
* Make a billing array for a incident
* @author Paul Heaney
* @param int $incidentid - Incident number of the incident to create the array from
* @todo Can this be merged into make_incident_billing_array? Does it serve any purpose on its own?
*   -- I would prefer to keep seperate - INL 23Jan09
**/
function get_incident_billing_details($incidentid)
{
    global $dbUpdates;
    /*
    $array[owner][] = array(owner, starttime, duration)
    */
    $sql = "SELECT * FROM `{$dbUpdates}` WHERE incidentid = {$incidentid} AND duration IS NOT NULL";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    if (mysql_num_rows($result) > 0)
    {
        while($obj = mysql_fetch_object($result))
        {
            if ($obj->duration > 0)
            {
                $temparray['owner'] = $obj->userid;
                $temparray['starttime'] = ($obj->timestamp-$obj->duration);
                $temparray['duration'] = $obj->duration;
                $billing[$obj->userid][] = $temparray;
            }
            else
            {
                if (empty($billing['refunds'])) $billing['refunds'] = 0;
                $billing['refunds'] += $obj->duration;
            }
        }
    }

    return $billing;
}


/**
* Takes an array of engineer/times of services and groups them so we have only periods which should be charged for. 
* This takes into account tasks started in the same period by the same engineer e.g. task started at 17:00 for 10 mins 
* another at 17:30 for 10 mins with a period of 60mins only one is reported
* @author Paul Heaney
* @param array $count The element to return into
* @param string $countType The counttype we are doing so either engineer or customer
* @param array $activity The current activity
* @param int $period The billing period to group to 
* @return $count is passed in by reference so nothing is returned
**/
function group_billing_periods(&$count, $countType, $activity, $period)
{
    $duration = $activity['duration'];
    $startTime = $activity['starttime'];

    if (!empty($count[$countType]))
    {
        while ($duration > 0)
        {
            $saved = "false";
            foreach ($count[$countType] AS $ind)
            {
                /*
                echo "<pre>";
                print_r($ind);
                echo "</pre>";
                */
                //echo "IN:{$ind}:START:{$act['starttime']}:ENG:{$engineerPeriod}<br />";

                if($ind <= $activity['starttime'] AND $ind <= ($activity['starttime'] + $period))
                {
                    //echo "IND:{$ind}:START:{$act['starttime']}<br />";
                    // already have something which starts in this period just need to check it fits in the period
                    if($ind + $period > $activity['starttime'] + $duration)
                    {
                        $remainderInPeriod = ($ind + $period) - $activity['starttime'];
                        $duration -= $remainderInPeriod;

                        $saved = "true";
                    }
                }
            }
            //echo "Saved: {$saved}<br />";
            if ($saved == "false" AND $activity['duration'] > 0)
            {
                //echo "BB:".$activity['starttime'].":SAVED:{$saved}:DUR:{$activity['duration']}<br />";
                // need to add a new block
                $count[$countType][$startTime] = $startTime;

                $startTime += $period;

                $duration -= $period;
            }
        }
    }
    else
    {
        $count[$countType][$activity['starttime']] = $activity['starttime'];
        $localDur = $activity['duration'] - $period;

        while ($localDur > 0)
        {
            $startTime += $period;
            $count[$countType][$startTime] = $startTime;
            $localDur -= $period; // was just -
        }
    }
}


/**
* @author Paul Heaney
* @note  based on periods
*/
function make_incident_billing_array($incidentid, $totals=TRUE)
{
    $billing = get_incident_billing_details($incidentid);

//echo "<pre>";
//print_r($billing);
//echo "</pre><hr />";

    $sql = "SELECT servicelevel, priority FROM `{$GLOBALS['dbIncidents']}` WHERE id = {$incidentid}";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    $incident = mysql_fetch_object($result);
    $servicelevel_tag = $incident->servicelevel;
    $priority = $incident->priority;

    if (!empty($billing))
    {
        $billingSQL = "SELECT * FROM `{$GLOBALS['dbBillingPeriods']}` WHERE tag='{$servicelevel_tag}' AND priority='{$priority}'";

        /*
        echo "<pre>";
        print_r($billing);
        echo "</pre>";

        echo "<pre>";
        print_r(make_billing_array($incidentid));
        echo "</pre>";
        */

        //echo $billingSQL;

        $billingresult = mysql_query($billingSQL);
        // echo $billingSQL;
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $billingObj = mysql_fetch_object($billingresult);

        unset($billingresult);

        $engineerPeriod = $billingObj->engineerperiod * 60;  //to seconds
        $customerPeriod = $billingObj->customerperiod * 60;

        if (empty($engineerPeriod) OR $engineerPeriod == 0) $engineerPeriod = 3600;
        if (empty($customerPeriod) OR $customerPeriod == 0) $customerPeriod = 3600;

        /*
        echo "<pre>";
        print_r($billing);
        echo "</pre>";
        */

        foreach ($billing AS $engineer)
        {
            /*
                [eng][starttime]
            */

            if (is_array($engineer))
            {
                $owner = "";
                $duration = 0;

                unset($count);

                $count['engineer'];
                $count['customer'];

                foreach ($engineer AS $activity)
                {
                    $owner = user_realname($activity['owner']);
                    $duration += $activity['duration'];

                    /*
                    echo "<pre>";
                    print_r($count);
                    echo "</pre>";
                    */

                    group_billing_periods($count, 'engineer', $activity, $engineerPeriod);

                    // Optimisation no need to compute again if we already have the details
                    if ($engineerPeriod != $customerPeriod)
                    {
                        group_billing_periods($count, 'customer', $activity, $customerPeriod);
                    }
                    else
                    {
                        $count['customer'] = $count['engineer'];
                    }
                }

                $tduration += $duration;
                $totalengineerperiods += sizeof($count['engineer']);
                $totalcustomerperiods += sizeof($count['customer']);
                /*
                echo "<pre>";
                print_r($count);
                echo "</pre>";
                */

                $billing_a[$activity['owner']]['owner'] = $owner;
                $billing_a[$activity['owner']]['duration'] = $duration;
                $billing_a[$activity['owner']]['engineerperiods'] = $count['engineer'];
                $billing_a[$activity['owner']]['customerperiods'] = $count['customer'];
            }

            if ($totals == TRUE)
            {
                if (empty($totalengineerperiods)) $totalengineerperiods = 0;
                if (empty($totalcustomerperiods)) $totalcustomerperiods = 0;
                if (empty($tduration)) $tduration = 0;

                $billing_a[-1]['totalduration'] = $tduration;
                $billing_a[-1]['totalengineerperiods'] = $totalengineerperiods;
                $billing_a[-1]['totalcustomerperiods'] = $totalcustomerperiods;
                $billing_a[-1]['customerperiod'] = $customerPeriod;
                $billing_a[-1]['engineerperiod'] = $engineerPeriod;
            }

            if (!empty($billing['refunds'])) $billing_a[-1]['refunds'] = $billing['refunds']/$customerPeriod; // return refunds as a number of units

        }

    }

    //echo "<pre>";
    //print_r($billing_a);
    //echo "</pre>";

    return $billing_a;
}


/**
* Returns the amount of billable units used for a site with the option of filtering by date
* @author Paul Heaney
* @param int $siteid The siteid to report on
* @param int $startdate unixtimestamp on the start date to filter by
* @param int $enddate unixtimestamp on the end date to filter by
* @return int Number of units used by site
**/
function billable_units_site($siteid, $startdate=0, $enddate=0)
{
    $sql = "SELECT i.id FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}` AS c ";
    $sql .= "WHERE c.id = i.contact AND c.siteid = {$siteid} ";
    if ($startdate != 0)
    {
        $sql .= "AND closed >= {$startdate} ";
    }

    if ($enddate != 0)
    {
        $sql .= "AND closed <= {$enddate} ";
    }

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    $units = 0;

    if (mysql_num_rows($result) > 0)
    {
        while ($obj = mysql_fetch_object($result))
        {
            $a = make_incident_billing_array($obj->id);
            $units += $a[-1]['totalcustomerperiods'];
        }
    }

    return $units;
}


/**
* Function to make an array with the number of units at each billable multiplier, broken down by engineer
* @author Paul Heaney
* @param int $incidentid The inicident to create the billing breakdown for
* @return array. Array of the billing for this incident broken down by enegineer 
*
*/
function get_incident_billable_breakdown_array($incidentid)
{
    $billable = make_incident_billing_array($incidentid, FALSE);

    //echo "<pre>";
    //print_r($billable);
    //echo "</pre>";

    if (!empty($billable))
    {

        foreach ($billable AS $engineer)
        {
            if (is_array($engineer) AND empty($engineer['refunds']))
            {
                $engineerName = $engineer['owner'];
                foreach ($engineer['customerperiods'] AS $period)
                {
                    // $period is the start time
                    $day = date('D', $period);
                    $hour = date('H', $period);

                    $dayNumber = date('d', $period);
                    $month = date('n', $period);
                    $year = date('Y', $period);
                    // echo "DAY {$day} HOUR {$hour}";

                    $dayofweek = strtolower($day);

                    if (is_day_bank_holiday($dayNumber, $month, $year))
                    {
                        $dayofweek = "holiday";
                    }

                    $multiplier = get_billable_multiplier($dayofweek, $hour, 1); //FIXME make this not hard coded

                    $billing[$engineerName]['owner'] = $engineerName;
                    $billing[$engineerName][$multiplier]['multiplier'] = $multiplier;
                    if (empty($billing[$engineerName][$multiplier]['count']))
                    {
                        $billing[$engineerName][$multiplier]['count'] = 0;
                    }

                    $billing[$engineerName][$multiplier]['count']++;
                }
            }
        }

        if (!empty($billable[-1]['refunds'])) $billing['refunds'] = $billable[-1]['refunds'];

    }

    return $billing;
}


/**
    * @author Ivan Lucas
    * @param int $contractid. Contract ID of the contract to show a balance for
    * @return int. Number of available units according to the service balances and unit rates
    * @todo Check this is correct
**/
function contract_unit_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE)
{
    global $now, $dbService;

    $unitbalance = 0;

    $sql = "SELECT * FROM `{$dbService}` WHERE contractid = {$contractid} ORDER BY enddate DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        while ($service = mysql_fetch_object($result))
        {
            $multiplier = get_billable_multiplier(strtolower(date('D', $now)), date('G', $now));
            $unitamount = $service->unitrate * $multiplier;
            if ($unitamount > 0) $unitbalance += round($service->balance / $unitamount);
        }
    }
    
    if ($includenonapproved)
    {
        $awaiting = contract_transaction_total($contractid, AWAITINGAPPROVAL);
        if ($awaiting != 0) $unitbalance += round($awaiting / $unitamount);
    }

    if ($includereserved)
    {
        $reserved = contract_transaction_total($contractid, RESERVED);
        if ($reserved != 0) $unitbalance += round($reserved / $unitamount);
    }

    return $unitbalance;
}

?>