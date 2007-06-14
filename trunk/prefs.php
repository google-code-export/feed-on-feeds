<?php
/*
 * This file is part of FEED ON FEEDS - http://feedonfeeds.com/
 *
 * prefs.php - display and change preferences
 *
 *
 * Copyright (C) 2004-2007 Stephen Minutillo
 * steve@minutillo.com - http://minutillo.com/steve/
 *
 * Distributed under the GPL - see LICENSE
 *
 */

include_once("fof-main.php");

if(isset($_POST['adminprefs']))
{
	$fof_user_prefs['purge'] = $_POST['purge'];
	$fof_user_prefs['manualtimeout'] = $_POST['manualtimeout'];
	$fof_user_prefs['autotimeout'] = $_POST['autotimeout'];

	fof_db_save_prefs(fof_current_user(), $fof_user_prefs);
    
    $fof_admin_prefs = $fof_user_prefs;
    	
	$message .= ' Saved admin prefs.';
}

if(isset($_POST['tagfeed']))
{
    $tags = $_POST['tag'];
    $feed_id = $_POST['feed_id'];
    $title = $_POST['title'];
    
    foreach(explode(" ", $tags) as $tag)
    {
        fof_tag_feed(fof_current_user(), $feed_id, $tag);
        $message .= " Tagged '$title' as $tag.";
    }
}

if(isset($_GET['untagfeed']))
{
    $feed_id = $_GET['untagfeed'];
    $tags = $_GET['tag'];
    $title = $_GET['title'];
	
    foreach(explode(" ", $tags) as $tag)
    {
        fof_untag_feed(fof_current_user(), $feed_id, $tag);
        $message .= " Dropped $tag from '$title'.";
    }
}

if(isset($_POST['prefs']))
{
	$fof_user_prefs['favicons'] = isset($_POST['favicons']);
	$fof_user_prefs['keyboard'] = isset($_POST['keyboard']);
	$fof_user_prefs['tzoffset'] = intval($_POST['tzoffset']);
	$fof_user_prefs['howmany'] = intval($_POST['howmany']);
	$fof_user_prefs['order'] = $_POST['order'];

    foreach(fof_get_plugin_prefs() as $plugin_pref)
    {
        $key = $plugin_pref[1];
        
        $fof_user_prefs[$key] = $_POST[$key];
    }
     
	fof_db_save_prefs(fof_current_user(), $fof_user_prefs);
    
    if($_POST['password'] && ($_POST['password'] == $_POST['password2']))
    {
        fof_db_change_password($fof_user_name, $_POST['password']);
        setcookie ( "user_password_hash",  md5($_POST['password'] . $fof_user_name), time()+60*60*24*365*10 );
        $message = "Updated password.";
    }
    else if($_POST['password'] || $_POST['password2'])
    {
        $message = "Passwords do not match!";
    }
	
	$message .= ' Saved prefs.';
}


if(isset($_POST['changepassword'])) 
{
    if($_POST['password'] != $_POST['password2'])
    {
        $message = "Passwords do not match!";
    }
    else
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        fof_db_change_password($username, $password);
        
        $message = "Changed password for $username.";
    }
}

if(isset($_POST['adduser']) && $_POST['username'] && $_POST['password']) 
{
    $username = $_POST['username'];
    $password = $_POST['password'];

	fof_db_add_user($username, $password);
	$message = "User '$username' added.";
}


if(isset($_POST['deleteuser']) && $_POST['username'])
{
	$username = $_POST['username'];
	
	fof_db_delete_user($username);
	$message = "User '$username' deleted.";
}

include("header.php");

$favicons = $_POST['favicons'];
?>

<?php if(isset($message)) { ?>

<br><font color="red"><?php echo $message ?></font><br>

<?php } ?>

<br><h1>Feed on Feeds - Preferences</h1>
<form method="post" action="prefs.php" style="border: 1px solid black; margin: 10px; padding: 10px;">
Default display order: <select name="order"><option value=desc>new to old</option><option value=asc <?php if($fof_user_prefs['order'] == "asc") echo "selected";?>>old to new</option></select><br><br>
Number of items in paged displays: <input type="string" name="howmany" value="<?php echo $fof_user_prefs['howmany']?>"><br><br>
Display custom feed favicons? <input type="checkbox" name="favicons" <?php if($fof_user_prefs['favicons']) echo "checked=true";?> ><br><br>
Use keyboard shortcuts? <input type="checkbox" name="keyboard" <?php if($fof_user_prefs['keyboard']) echo "checked=true";?> ><br><br>
Time offset in hours: <input size=3 type=string name=tzoffset value="<?php echo $fof_user_prefs['tzoffset']?>"> (UTC time: <?php echo gmdate("Y-n-d g:ia") ?>, local time: <?php echo gmdate("Y-n-d g:ia", time() + $fof_user_prefs["tzoffset"]*60*60) ?>)<br><br>
<table border=0 cellspacing=0 cellpadding=2><tr><td>New password:</td><td><input type=password name=password> (leave blank to not change)</td></tr>
<tr><td>Repeat new password:</td><td><input type=password name=password2></td></tr></table>
<br>

<?php foreach(fof_get_plugin_prefs() as $plugin_pref) { $name = $plugin_pref[0]; $key = $plugin_pref[1]; ?>
<?php echo $name ?>: <input name="<?php echo $key ?>" value="<?php echo $fof_user_prefs[$key]?>"> <i><small>(this preference is from a plugin)</small></i><br><br>
<?php } ?>
<input type=submit name=prefs value="Save Preferences">
</form>

<br><h1>Feed on Feeds - Feeds and Tags</h1>
<div style="border: 1px solid black; margin: 10px; padding: 10px; font-size: 12px; font-family: verdana, arial;">
<table cellpadding=3 cellspacing=0>
<?php
foreach($feeds as $row)
{
   $id = $row['feed_id'];
   $url = $row['feed_url'];
   $title = $row['feed_title'];
   $link = $row['feed_link'];
   $description = $row['feed_description'];
   $age = $row['feed_age'];
   $unread = $row['feed_unread'];
   $starred = $row['feed_starred'];
   $items = $row['feed_items'];
   $agestr = $row['agestr'];
   $agestrabbr = $row['agestrabbr'];
   $lateststr = $row['lateststr'];
   $lateststrabbr = $row['lateststrabbr'];   
   $prefs = $row['prefs'];
   $tags = $row['tags'];
   
   if(++$t % 2)
   {
      print "<tr class=\"odd-row\">";
   }
   else
   {
      print "<tr>";
   }

   if($row['feed_image'] && $fof_user_prefs['favicons'])
   {
	   print "<td><a href=\"$url\" title=\"feed\"><img src='" . $row['feed_image'] . "' width='16' height='16' border='0' /></a></td>";
   }
   else
   {
	   print "<td><a href=\"$url\" title=\"feed\"><img src='image/feed-icon.png' width='16' height='16' border='0' /></a></td>";
   }
    
   print "<td><a href=\"$link\" title=\"home page\">$title</a></td>";
   
   print "<td align=right>";
   
   if($tags)
   {
       foreach($tags as $tag)
       {
           $utag = urlencode($tag);
           $utitle = urlencode($title);
           print "$tag <a href='prefs.php?untagfeed=$id&tag=$utag&title=$utitle'>[x]</a> ";
       }
   }
   else
   {
   }
   
   print "</td>";
   $title = htmlspecialchars($title);
   print "<td><form method=post action=prefs.php><input type=hidden name=title value=\"$title\"><input type=hidden name=feed_id value=$id><input type=string name=tag> <input type=submit name=tagfeed value='Tag Feed'> <small><i>(separate tags with spaces)</i></small></form></td></tr>";
}
?>
</table>
</div>


<?php if(fof_is_admin()) { ?>

<br><h1>Feed on Feeds - Admin Options</h1>
<form method="post" action="prefs.php" style="border: 1px solid black; margin: 10px; padding: 10px;">
Purge read items after <input size=4 type=string name=purge value="<?php echo $fof_admin_prefs['purge']?>"> days (leave blank to never purge)<br><br>
Allow automatic feed updates every <input size=4 type=string name=autotimeout value="<?php echo $fof_admin_prefs['autotimeout']?>"> minutes<br><br>
Allow manual feed updates every <input size=4 type=string name=manualtimeout value="<?php echo $fof_admin_prefs['manualtimeout']?>"> minutes<br><br>
<input type=submit name=adminprefs value="Save Options">
</form>

<br><h1>Add User</h1>
<form method="post" action="prefs.php" style="border: 1px solid black; margin: 10px; padding: 10px;">
Username: <input type=string name=username> Password: <input type=string name=password> <input type=submit name=adduser value="Add user">
</form>

<?php
	$result = fof_db_query("select user_name from $FOF_USER_TABLE where user_id > 1");
	
	while($row = fof_db_get_row($result))
	{
		$username = $row['user_name'];
		$delete_options .= "<option value=$username>$username</option>";
	}

    if(isset($delete_options))
    {
?>

<br><h1>Delete User</h1>
<form method="post" action="prefs.php" style="border: 1px solid black; margin: 10px; padding: 10px;" onsubmit="return confirm('Delete User - Are you sure?')">
<select name=username><?php echo $delete_options ?></select>
<input type=submit name=deleteuser value="Delete user"><br>
</form>

<br><h1>Change User's Password</h1>
<form method="post" action="prefs.php" style="border: 1px solid black; margin: 10px; padding: 10px;" onsubmit="return confirm('Change Password - Are you sure?')">
<table border=0 cellspacing=0 cellpadding=2>
<tr><td>Select user:</td><td><select name=username><?php echo $delete_options ?></select></td></tr>
<tr><td>New password:</td><td><input type=password name=password></td></tr>
<tr><td>Repeat new password:</td><td><input type=password name=password2></td></tr></table>
<input type=submit name=changepassword value="Change"><br>
</form>

<?php } ?>

<br>
<form method="get" action="uninstall.php" onsubmit="return confirm('Really?  This will delete all the database tables!')">
<center><input type=submit name=uninstall value="Uninstall Feed on Feeds" style="background-color: #ff9999"></center>
</form>

<?php } ?>

<?php include("footer.php") ?>
