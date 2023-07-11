<?php
/* This script allows administrators to manage groups, users and messages */
session_start();

include "config.inc.php";
include "newsportal.php";
// group search module

//include $file_newsportal;
// message & group deletion module
include $delete_lib;

if(isset($_COOKIE['tzo'])) {
	$offset=$_COOKIE['tzo'];
} else {
	$offset=$CONFIG['timezone'];
}

if(!isset($_POST['command'])) {
 	$_POST['command'] = null;
}
/*
if (isset($_POST['network']))
{
	$_POST['network'] = null;
}

if (isset($_POST['group']))
{
	$_POST['group'] = null;
}

if (isset($_POST['description']))
{
	$_POST['description'] = null;
}*/

include($auth_file);
include("head.inc");

$network_list = $config_dir."menu.conf";

// handle commands for logged-in users
if ($logged_in)
{
	if (isset($_POST['command']))
	{
		// store the posted data in a few temp variables
		$network = $_POST['network'];
		$group = $_POST['group'];
		$messageid = $_POST['message_id'];
		$command = $_POST['command'];
		
		// Stage 2: Check whether the network & group are valid
		// and if so, allow the user to continue deleting the message.
		// The second time this script runs (after confirming that everything looks
		// okay), it checks all of the data again and immediately deletes the message.
		// We run it twice in a row because we want to double check that someone else
		// hasn't already tried to delete the message in between requests.
		if ($command == "Create")
		{
			if (!empty($network))
				$_SESSION['network'] = $network;
			if (!empty($group))
				$_SESSION['group'] = $group;
			if (!empty($messageid))
				$_SESSION['message_id'] = $messageid;
		
			$netok = network_exists($_SESSION['network']);
			$groupexists = group_exists($_SESSION['network'], $_SESSION['group']);
			if (!$netok)
			{
				echo "Error: no such network. Typo?";
			    echo '<form name="return1" method="post" action="admin.php">';
			  	echo '<input type="submit" name="Submit" value="Go Back"></td>';
				exit(2);
			}
			else if ($netok && $groupexists)
			{
				// groupexists is an array with key: name, value: description
				if ($groupexists != $group)
				{
					echo "Sorry, " . $network . "." . $group . " could not be found.";
				    echo '<form name="return1" method="post" action="admin.php">';
					echo '<input type="submit" name="Back" value="Go Back"></form>';
					exit(2);
				}
			}
			if ($netok && !$groupexists)
			{
				echo "You are about to create " . $_SESSION['network'] . "." . $_SESSION['group'];
				echo "<br><br>";
				echo $_SESSION['description'];
				echo "<br><br>";
				echo "This all look good to you?";
				echo "<br><br>";
			    echo '<form name="creategroup" method="post" action="admin.php">';
		    	echo '<input name="command" type="hidden" id="command" value="PunchItBishop" readonly="readonly">';
				echo '<input type="submit" name="Submit" value="Looks good. Create it."></td>';

			}	
		}
		// Stage 3: Create the group and force a reload of the group lists
		else if ($command == "PunchItBishop")
		{
			echo "Creating group " . $_SESSION['network'] . "." . $_SESSION['group'] . "...";
			echo "<br><br>";
			admin($_SESSION['network'], $_SESSION['group'], $_SESSION['description']);
			$_SESSION['network'] = null;
			$_SESSION['group'] = null;
			$_SESSION['description'] = null;
		}
		// Handle Delete Message/Thread/Group commands
		else if ($command == "delete_message")
		{
			if ($group != "" && $messageid != "")
			{
				if ($DebugMode)
					echo "Received deletion request for " . $group . $messageid . '<br>';				
				delete_message($messageid, $group);
				echo 'Message successfully deleted.';
				exit(0);
			}
			else
			{
				echo "Message ID not found";
			}
		}
		else if ($command == "delete_group")
		{
			if ($group != "" && $network != "")
			{
				$fullgroupname = $network . "." . $group;
				if (group_exists($network, $group))
				{
					echo 'Received deletion request for group ' . $fullgroupname . '<br>';
					// build networkname.groupname and send it off for deletion
					if (delete_group($fullgroupname))
					{
						// now remove articles from the database
						remove_articles($fullgroupname);
						reset_group($fullgroupname, 1);
						// must reload the groups list so the NNTP server knows the groups are gone
						reload_groups();
						echo 'Group successfully deleted';					
					}
					else
					{
						echo 'Group not found on Message Server.';
					}
					exit(0);				
				}
				echo "<center>Network and/or Group not found.</center><br>";
			}
		}		
		
		echo '<center>';
		if (empty($_POST['network']) && empty($_SESSION['network']))
		{
			echo "Dangit Beavis. You have to tell us which network you want to delete the message on!\r\n";
		    echo '<form name="return1" method="post" action="admin.php">';
		  	echo '<input type="submit" name="Submit" value="Back"></td>';
		    exit(2);
		}
		if (empty($_POST['group']) && empty($_SESSION['group']))
		{
			echo "Dangit Beavis. You forgot to enter a group name to delete the message from.\r\n";
		    echo '<form name="return1" method="post" action="admin.php">';
		  	echo '<input type="submit" name="Submit" value="Back"></td>';
		    exit(2);
		}
		if (empty($_POST['message_id']) && empty($_SESSION['message_id']))
		{
			echo "Dangit Beavis. You forgot to write a Message ID that you'd like to delete.\r\n";
		    echo '<form name="return1" method="post" action="admin.php">';
		  	echo '<input type="submit" name="Submit" value="Back"></td>';
		    exit(2);
		}
	}	
}

// Stage 1: Present user with group creation options.

if (!isset($_POST['command']))
{
	if ($logged_in)
	{
		echo '<table border="0" align="center" cellpadding="0" cellspacing="1">';
		echo '<tr>';
		echo '<form name="form1" method="post" action="admin.php">';
		echo '<td><tr>';
		echo '<td><strong>Delete a Message:</strong></td>';
		echo '</tr><tr>';
		echo '<td>On Network:</td>';
		echo '<td><input name="network" type="text" id="network"value="'.$_POST['network'].'"></td>';
		echo '</tr><tr>';
		echo '<td>Group Name:</td>';
		echo '<td><input name="group" type="text" id="group"value="'.$_POST['group'].'"></td>';
		echo '</tr><tr>';
		echo '<td>Message ID:</td>';
		echo '<td><input name="messageid" type="text" id="messageid" value="'.$_POST['message_id'].'"></td>';
		echo '</tr><tr>';
		//echo '<td>Password:</td>';
		//echo '<td><input name="password" type="password" id="password"></td>';
		//echo '</tr><tr>';
		//echo '<td>Re-enter Password:</td>';
		//echo '<td><input name="password2" type="password" id="password2"></td>';
		echo '</tr><tr>';
		//echo '<td><img src="'.$captchaImage.'" /></td>';
		//echo '<td><input name="captcha" type="text" id="captcha"></td>';
		echo '</tr><tr>';
		//echo '<td><input name="captchacode" type="hidden" id="captchacode" value="'.$captchacode.'" readonly="readonly"></td>';
		//echo '<td><input name="captchaimage" type="hidden" id="captchaimage" value="'.$captchaImage.'" readonly="readonly"></td>';
		echo '<td><input name="command" type="hidden" id="command" value="Create" readonly="readonly"></td>';
		//echo '<td><input name="key" type="hidden" value="'.password_hash($keys[0], PASSWORD_DEFAULT).'"></td>';
		echo '</tr><tr>';
		echo '<td>&nbsp;</td>';
		echo '<td><input type="submit" name="Submit" value="Delete"></td>';
		echo '</tr>';
		echo '</td></tr>';
		echo '</td>';
		echo '</form>';
		echo '</tr>';
		echo '</table>';
	}
	else
	{
		echo "You must <a href=../spoolnews/user.php>login</a> or <a href='../common/register.php'>register</a> to delete a message.";
	}
    echo '</body>';
    echo '</html>';
    exit(0);
}

echo '</center>';

include "tail.inc";



/* Check if a network exists in the top level of the hierarchy */
function network_exists($networkname) {
	global $CONFIG,$file_groups,$config_dir;
	/* Find network in list */
	$networklist = "";
	$networklist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach($networklist as $menu) {
	  if($menu[0] == '#') {
	    continue;
	  }
	  $networklistitem=explode(':', $menu);
	  if ($networkname == $networklistitem[0])
	  {
		  return true;
	  }
	  else
	  {
		  continue;
	  }
	}
	return false;
}

/* Returns true if the supplied group exists in a specific network */
function group_exists($networkname, $groupname)
{
	global $CONFIG,$config_dir;
    $networklist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($networklist as $menu) {
	  // skip over comments
      if($menu[0] == '#') {
        continue;
      }
      $networklistitem=explode(':', $menu);
	  if ($networkname == $networklistitem[0])
	  {
		  $group_matches = array();
	      $glfp=fopen($config_dir.$networklistitem[0]."/groups.txt", 'r');
	      while($gl=fgets($glfp)) {
			// split group entries up by spaces or tabs, and only look at the first two chunks
			$full_group_name = preg_split("/( |\t)/", $gl, 2);
			
			// check for exact matches to names on the network
			if (trim($networkname . '.' . $groupname) == trim($full_group_name[0]))
			{
				fclose($glfp);
				return $groupname;
			}
			// now compare the full network+groupname and the group list
			if(stripos(trim($networkname . '.' . $groupname), trim($full_group_name[0])) !== false) {
				// add any potential matches to the list
				// crucial: MUST trim before using a variable as a key name!
				$group_matches += [trim($full_group_name[0]) => trim($full_group_name[1])];
			}
	      }
		  
		  if ($group_matches)
		  {
		      fclose($glfp);
			  return $group_matches;
		  }
		  else
		  {
		      fclose($glfp);
			  return false;
		  }
	  }
    }
}

/* Creates a new group using the supplied parameters */
function create_group($networkname, $group, $description)
{
	global $CONFIG,$config_dir;
	// open the file in append mode
	$grouptextfile = fopen($config_dir.$networkname."/groups.txt", 'a');
	fwrite($grouptextfile, $networkname . "." . $group . " " . $description . "\n");
	fclose($grouptextfile);
	reload_groups();
	echo "Group created successfully.";	
}

?>
