<?php

  include "config.inc.php";
  include ("$file_newsportal");

  if(!isset($CONFIG['expire_days']) || $CONFIG['expire_days'] < 1) {
    exit;
  }

  $lockfile = sys_get_temp_dir() . '/'.$config_name.'-spoolnews.lock';
  $pid = file_get_contents($lockfile);
  if (posix_getsid($pid) === false || !is_file($lockfile)) {
    print "Starting expire...\n";
    file_put_contents($lockfile, getmypid()); // create lockfile
  } else {
    print "expire currently running\n";
    exit;
  }

  $webserver_group=$CONFIG['webserver_user'];
  $logfile=$logdir.'/expire.log';
  
  $expireme=time() - ($CONFIG['expire_days'] * 86400);
  
  $grouplist = file($config_dir.'/'.$config_name.'/groups.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($grouplist as $groupline) {
    $groupname=explode(' ', $groupline);
    $group=$groupname[0];
    $grouppath = preg_replace('/\./', '/', $group); 
    $this_overview=$spooldir.'/'.$group.'-overview';
    $out_overview=$this_overview.'.new';

    $overviewfp=fopen($this_overview, 'r');
    $out_overviewfp=fopen($out_overview, 'w');

    while($line=fgets($overviewfp)) {
      $break=explode("\t", $line);
      if(strtotime($break[3]) < $expireme) {
        echo "Expiring: ".$break[4]." IN: ".$group." #".$break[0]."\r\n";
        file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Expiring: ".$break[4]." IN: ".$group." #".$break[0], FILE_APPEND);
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
  }
// Remove from section overview
  $this_overview=$spooldir.'/'.$config_name.'-overview';
  $out_overview=$this_overview.'.new';
  $overviewfp=fopen($this_overview, 'r');
  $out_overviewfp=fopen($out_overview, 'w');

  while($line=fgets($overviewfp)) {
    $break=preg_split("/(:#rsl#:|\t)/", $line, 6);
//    $break=explode("\t", $line);
    if($break[3] < $expireme) {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Removing from overview: ".$break[2], FILE_APPEND);
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
  unlink($lockfile);
?>
