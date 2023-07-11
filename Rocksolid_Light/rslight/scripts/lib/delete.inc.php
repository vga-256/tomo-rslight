<?php

$logfile=$logdir.'/newsportal.log';

// Deletes a group. Requires full name of group including network name.
function delete_group($group)
{
	global $logfile,$config_dir;
    $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($menulist as $menu) {
    	if($menu[0] == '#') {
    		continue;
    	}
    	$menuitem=explode(':', $menu);
		
        $grouplist=file($config_dir.$menuitem[0]."/groups.txt");
        $section="";
		// work through the groups.txt line by line, looking for the group
		foreach($grouplist as $groupline)
		{
  			$group_name = preg_split("/( |\t)/", $groupline, 2);
  			if(strtolower(trim($group)) == strtolower(trim($group_name[0]))) {				
	  			$groupText = file_get_contents($config_dir.$menuitem[0]."/groups.txt");
		  		$rebuiltGroupText = str_replace($groupline, "", $groupText);
		  		if ($rebuiltGroupText == $groupText)
		  		{
		  			// search for this group name failed
		  			return false;
		  		}
		  		else
		  		{
					// group deleted from groups.txt
		  			file_put_contents($config_dir.$menuitem[0]."/groups.txt", $rebuiltGroupText, LOCK_EX);
		    		file_put_contents($logfile, "\n".format_log_date()." ".$config_name." DELETED: ".$config_name.'/'.$group, FILE_APPEND);
		  			return true;
		  		}
			}
		}
	}
	return false;
}

// Deletes a message from a group.
function delete_message($messageid, $group) {
  global $logfile,$config_dir,$spooldir, $CONFIG, $webserver_group;

/* Find section */
    $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($menulist as $menu) {
      if($menu[0] == '#') {
        continue;
      }
      $menuitem=explode(':', $menu);
      $glfp=fopen($config_dir.$menuitem[0]."/groups.txt", 'r');
      $section="";
      while($gl=fgets($glfp)) {
		  $group_name = preg_split("/( |\t)/", $gl, 2);
		  if(strtolower(trim($group)) == strtolower(trim($group_name[0]))) {
          	$config_name=$menuitem[0];
	  	  	file_put_contents($logfile, "\n".format_log_date()." ".$config_name." FOUND: ".$messageid." IN: ".$config_name.'/'.$group, FILE_APPEND);
          	break 2;
          }
      }
    }
 if($config_name) {
  $database = $spooldir.'/articles-overview.db3';
  $dbh = rslight_db_open($database);
  $query = $dbh->prepare('DELETE FROM overview WHERE msgid=:messageid');
  $query->execute(['messageid' => $messageid]);
  $dbh = null; 
  thread_cache_removearticle($group,$messageid);
 }
  if($CONFIG['article_database'] == '1') {
    $database = $spooldir.'/'.$group.'-articles.db3';
    if(is_file($database)) {
      $articles_dbh = article_db_open($database);
      $articles_query = $articles_dbh->prepare('DELETE FROM articles WHERE msgid=:messageid');
      $articles_query->execute(['messageid' => $messageid]);
      $articles_dbh = null;
    }
  }
  $this_overview=$spooldir.'/'.$group.'-overview';
  if(false === (is_file($this_overview))) {
    return;
  }
  $out_overview=$this_overview.'.new'; 
  $overviewfp=fopen($this_overview, 'r');
  $out_overviewfp=fopen($out_overview, 'w'); 
  while($line=fgets($overviewfp)) {
    $break=explode("\t", $line);
    if($break[4] == $messageid) {
		// uncomment below line for debug info
      //echo "DELETING: ".$messageid." IN: ".$group." #".$break[0]."\r\n";
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." DELETING: ".$messageid." IN: ".$group." #".$break[0], FILE_APPEND);
      $grouppath = preg_replace('/\./', '/', $group);
      unlink($spooldir.'/articles/'.$grouppath.'/'.$break[0]);
      continue; 
    } else {
      fputs($out_overviewfp, $line);
    }
  }
  fclose($overviewfp);
  fclose($out_overviewfp); 
  rename($out_overview, $this_overview);
  chown($this_overview, $CONFIG['webserver_user']);
  chgrp($this_overview, $webserver_group);
  delete_message_from_overboard($config_name, $group, $messageid);
  return;
}

function delete_message_from_overboard($config_name, $group, $messageid) {
  GLOBAL $spooldir;
  $cachefile=$spooldir."/".$config_name."-overboard.dat";
  if(is_file($cachefile)) {
    $cached_overboard = unserialize(file_get_contents($cachefile));
    if($target = $cached_overboard['msgids'][$messageid]) {
      unset($cached_overboard['threads'][$target['date']]);
      unset($cached_overboard['msgids'][$messageid]);
      unset($cached_overboard['threadlink'][$messageid]);
      file_put_contents($cachefile, serialize($cached_overboard));
    }
  }
  $cachefile=$spooldir."/".$group."-overboard.dat";
  if(is_file($cachefile)) {
    $cached_overboard = unserialize(file_get_contents($cachefile));
    if($target = $cached_overboard['msgids'][$messageid]) {
      unset($cached_overboard['threads'][$target['date']]);
      unset($cached_overboard['msgids'][$messageid]);
      unset($cached_overboard['threadlink'][$messageid]);
      file_put_contents($cachefile, serialize($cached_overboard));
    }
  }
}

function reset_group($group, $remove=0) {
    global $config_dir, $spooldir;
    $group = trim($group);
    
    if(!$section = get_section_by_group($group)) {
        return false;
    }
    $config_location = $spooldir.'/'.$section;
    $config_files = array_diff(scandir($config_location), array('..', '.'));

    foreach($config_files as $config_file) {
        $output = array();
        echo $config_location.'/'.$config_file."\n";
        $thisfile = file($config_location.'/'.$config_file);
        foreach($thisfile as $thisgroupline) {
            $onegroup = explode(':', $thisgroupline);
            if(trim($onegroup[0]) == $group) {
                echo "FOUND: ".$group." in ".$section."\n";
                if($remove == 0) {
                    $output[] = $group."\n";
                }
            } else {
                $output[] = $thisgroupline;
            }
        }
        file_put_contents($config_location.'/'.$config_file, $output);
    }
}

function remove_articles($group) {
    global $spooldir, $CONFIG, $workpath, $path, $config_name, $logfile;
    $group = trim($group);
    $overview_file = $workpath.'/'.$group."-overview";
    # Prepare databases
    $dbh = rslight_db_open($spooldir.'/articles-overview.db3');
    $clear_stmt = $dbh->prepare("DELETE FROM overview WHERE newsgroup=:group");
    $clear_stmt->bindParam(':group', $group);
    $clear_stmt->execute();
    unlink($overview_file);
    rename($spooldir.'/'.$group.'-articles.db3',$spooldir.'/'.$group.'-articles.db3-removed');
    unlink($spooldir.'/'.$group.'-data.dat');
    unlink($spooldir.'/'.$group.'-info.txt');
    unlink($spooldir.'/'.$group.'-cache.txt');
    unlink($spooldir.'/'.$group.'-lastarticleinfo.dat');
    unlink($spooldir.'/'.$group.'-overboard.dat');
    unlink($spooldir.'/'.$group.'-overview');
}

?>