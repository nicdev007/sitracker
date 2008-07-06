<?php
// browse_sites.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2008 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// TODO This page fails XHTML validation because of dojo attributes - INL 12/12/07

@include ('set_include_path.inc.php');
$permission = 11; // View Sites
require ('db_connect.inc.php');
require ('functions.inc.php');
// This page requires authentication
require ('auth.inc.php');

$pagescripts = array('dojo/dojo.js');

// External variables
$search_string = cleanvar($_REQUEST['search_string']);
$owner = cleanvar($_REQUEST['owner']);
$submit_value = cleanvar($_REQUEST['submit']);
$displayinactive = cleanvar($_REQUEST['displayinactive']);
if (empty($displayinactive)) $displayinactive = "false";

if ($submit_value == "go")
{
// build SQL
    $sql  = "SELECT id, name, department FROM `{$dbSites}` ";
    if (!empty($owner))
    {
        $sql .= "WHERE owner = '{$owner}' ";
    }
    elseif ($search_string != '*')
    {
        $sql .= "WHERE ";
        if (strlen($search_string)==1)
        {
            if ($search_string=='0') $sql .= "(SUBSTRING(name,1,1)=('0')
                                            OR SUBSTRING(name,1,1)=('1')
                                            OR SUBSTRING(name,1,1)=('2')
                                            OR SUBSTRING(name,1,1)=('3')
                                            OR SUBSTRING(name,1,1)=('4')
                                            OR SUBSTRING(name,1,1)=('5')
                                            OR SUBSTRING(name,1,1)=('6')
                                            OR SUBSTRING(name,1,1)=('7')
                                            OR SUBSTRING(name,1,1)=('8')
                                            OR SUBSTRING(name,1,1)=('9'))";
            else $sql .= "SUBSTRING(name,1,1)=('$search_string') ";
        }
        else
        {
            $sql .= "name LIKE '%$search_string%' ";
        }
    }
    if (!$displayinactive) $sql .= "AND active = 'true'";
    $sql .= " ORDER BY name ASC";

    // execute query
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 1)
    {
            //go straight to the site
            $row = mysql_fetch_array($result);
            $url = "site_details.php?id=".$row["id"];
            header("Location: $url");
    }
}

include ('htmlheader.inc.php');
if ($search_string=='') $search_string='a';
/*?>
<script type="text/javascript">
//<![CDATA[
    dojo.require ("dojo.widget.ComboBox");
//]]>
</script>
<?php*/
echo "<h2>".icon('site', 32)." ";
echo "{$strBrowseSites}</h2>";
?>
<table summary="alphamenu" align="center">
<tr>
<td align="center">
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
    <!-- <p>Browse sites: <input type="text" name="search_string" /><input type="submit" value="go" /></p>-->
    <?php
    echo "<p>{$strBrowseSites}: ";
    // dojoType='ComboBox' dataUrl='autocomplete.php?action=sites'
    echo "<input style='width: 300px;' name='search_string' onkeyup=\"autocomplete(this, 'comboresults');\" />";
    echo "<input name='submit' type='submit' value='{$strGo}' /></p>";
    echo "</form>\n";
    if ($displayinactive=="true")
    {
        echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=false";
        if (!empty($search_string)) echo "&amp;search_string={$search_string}&amp;owner={$owner}";
        echo "'>{$strShowActiveOnly}</a>";
        $inactivestring="displayinactive=true";
    }
    else
    {
        echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=true";
        if (!empty($search_string)) echo "&amp;search_string={$search_string}&amp;owner={$owner}";
        echo "'>{$strShowAll}</a>";
        $inactivestring="displayinactive=false";
    }
    ?>
</td>
</tr>
<tr>
<td valign="middle">
    <?php echo "<a href='add_site.php'>{$strAddSite}</a> | "; ?>
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=A&amp;<?php echo $inactivestring; ?>">A</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=B&amp;<?php echo $inactivestring; ?>">B</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=C&amp;<?php echo $inactivestring; ?>">C</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=D&amp;<?php echo $inactivestring; ?>">D</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=E&amp;<?php echo $inactivestring; ?>">E</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=F&amp;<?php echo $inactivestring; ?>">F</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=G&amp;<?php echo $inactivestring; ?>">G</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=H&amp;<?php echo $inactivestring; ?>">H</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=I&amp;<?php echo $inactivestring; ?>">I</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=J&amp;<?php echo $inactivestring; ?>">J</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=K&amp;<?php echo $inactivestring; ?>">K</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=L&amp;<?php echo $inactivestring; ?>">L</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=M&amp;<?php echo $inactivestring; ?>">M</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=N&amp;<?php echo $inactivestring; ?>">N</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=O&amp;<?php echo $inactivestring; ?>">O</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=P&amp;<?php echo $inactivestring; ?>">P</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=Q&amp;<?php echo $inactivestring; ?>">Q</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=R&amp;<?php echo $inactivestring; ?>">R</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=S&amp;<?php echo $inactivestring; ?>">S</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=T&amp;<?php echo $inactivestring; ?>">T</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=U&amp;<?php echo $inactivestring; ?>">U</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=V&amp;<?php echo $inactivestring; ?>">V</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=W&amp;<?php echo $inactivestring; ?>">W</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=X&amp;<?php echo $inactivestring; ?>">X</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=Y&amp;<?php echo $inactivestring; ?>">Y</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=Z&amp;<?php echo $inactivestring; ?>">Z</a> |
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>?search_string=0&amp;<?php echo $inactivestring; ?>">#</a> |

<?php
echo "<a href='{$_SERVER['PHP_SELF']}?search_string=*&amp;{$inactivestring}'>{$strAll}</a>\n";
$sitesql = "SELECT COUNT(id) FROM `{$dbSites}` WHERE owner='{$sit[2]}'";
$siteresult = mysql_query($sitesql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
list($ownedsites) = mysql_fetch_row($siteresult);
if ($ownedsites > 0) echo " | <a href='browse_sites.php?owner={$sit[2]}' title='Sites'>{$strMine}</a> ";
?>
    </td>
</tr>
</table>

<script type="text/javascript">
//<![CDATA[
    function site_details_window(siteid)
    {
        URL = "site_details.php?action=edit&amp;site=" + siteid;
        window.open(URL, "site_details_window", "toolbar=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=450,height=240");
    }
//]]>
</script>
<?php
// check input
if ($search_string == '')
{
        $errors = 1;
        echo "<p class='error'>You must enter a search string</p>\n";
}

// search for criteria
if ($errors == 0)
{
    if ($submit_value != 'go')
    {
        // Don't  need to do this again, already done above, us the results of that
        // build SQL
        $sql  = "SELECT id, name, department, active FROM `{$dbSites}` ";

        if (!empty($owner))
        {
            $sql .= "WHERE owner = '{$owner}' ";
        }
        elseif ($search_string != '*')
        {
            $sql .= "WHERE ";
            if (strlen($search_string)==1)
            {
                if ($search_string=='0') $sql .= "(SUBSTRING(name,1,1)=('0')
                                                OR SUBSTRING(name,1,1)=('1')
                                                OR SUBSTRING(name,1,1)=('2')
                                                OR SUBSTRING(name,1,1)=('3')
                                                OR SUBSTRING(name,1,1)=('4')
                                                OR SUBSTRING(name,1,1)=('5')
                                                OR SUBSTRING(name,1,1)=('6')
                                                OR SUBSTRING(name,1,1)=('7')
                                                OR SUBSTRING(name,1,1)=('8')
                                                OR SUBSTRING(name,1,1)=('9'))";
                else $sql .= "SUBSTRING(name,1,1)=('$search_string') ";
            }
            else
            {
                $sql .= "name LIKE '%$search_string%' ";
            }
        }
        if ($displayinactive=="false")
        {
            if ($search_string == '*') $sql .= " WHERE ";
            else $sql .= " AND ";
            $sql .= " active = 'true'";
        }
        $sql .= " ORDER BY name ASC";

//echo "  ^^".$displayinactive."^^";
        // execute query
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    }

    if (mysql_num_rows($result) == 0)
    {
        echo "<p align='center'>Sorry, unable to find any sites ";
        if ($owner > 0) echo " owned by <strong>".user_realname($owner)."</strong></p>\n";
        elseif ($search_string=='0') echo " matching <strong><em>Number</em></strong>";
        else echo "matching <strong>'$search_string</strong>'</p>\n";
    }
    else
    {
        $countsites = mysql_num_rows($result);
        echo "<p align='center'>Displaying $countsites site";
        if ($countsites > 1) echo "s";

        if ($owner > 0)
        {
            echo " owned by <strong>".user_realname($owner)."</strong>";
        }
        elseif ($search_string=='0')
        {
            echo " matching <strong><em>Number</em></strong>";
        }
        else
        {
            echo " matching <strong>'{$search_string}'</strong>";
        }
        echo "</p>";
        echo "<table align='center'>";
        echo "<tr>";
        echo "<th>{$strID}</th>";
        echo "<th>{$strSiteName}</th>";
        echo "<th>{$strDepartment}</th>";
        echo "</tr>";
        $shade = 0;
        while ($results = mysql_fetch_array($result))
        {
            // define class for table row shading
            if ($shade) $class = "shade1";
            else $class = "shade2";
            if ($results['active'] == 'false') $class='expired';
            echo "<tr class='{$class}'>";
            echo "<td align='center'>{$results['id']}</td>";
            echo "<td><a href='site_details.php?id={$results['id']}&amp;action=show'>{$results['name']}</a></td>";
            echo "<td>".nl2br($results["department"])."</td>";
            echo "</tr>";
            // invert shade
            if ($shade == 1) $shade = 0;
            else $shade = 1;
        }
        echo "</table>\n";
    }
}

include ('htmlfooter.inc.php');
?>
