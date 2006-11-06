<?php
// delete_note.php - Delete note
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2006 Salford Software Ltd.
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission=0; // Allow all auth users

require('db_connect.inc.php');
require('functions.inc.php');

// This page requires authentication
require('auth.inc.php');

// External variables
$id = cleanvar($_REQUEST['id']);
$rpath = cleanvar($_REQUEST['rpath']);

$sql = "DELETE FROM notes WHERE id='{$id}' AND userid='{$sit[2]}' LIMIT 1";
mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
if (mysql_affected_rows() >= 1)
{
    confirmation_page("2", "$rpath", "<h2>Note deleted successfully</h2><p align='center'>Please wait while you are redirected...</p>");
}
else
{
    confirmation_page("2", "$rpath", "<h2>Note was not deleted</h2><p align='center'>Please wait while you are redirected...</p>");
}
?>