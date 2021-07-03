<?php

  include "config.inc.php";
  include ("$file_newsportal");

  if(filemtime($spooldir.'/'.$config_name.'-expire-timer')+86400 > time()) {
    exit;
  }
  $lockfile = $lockdir . '/'.$config_name.'-spoolnews.lock';
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

  $grouplist = file($config_dir.'/'.$config_name.'/groups.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($grouplist as $groupline) {
    $expireme = 0;
    if($CONFIG['expire_days'] > 0) {
	$expireme=time() - ($CONFIG['expire_days'] * 86400);
    }
    $groupname=explode(' ', $groupline);
    $group=$groupname[0];
    if(($days = get_config_value('expire.conf', $group)) !== false) {
      if($days > 0) {
	$expireme = time() - ($days * 86400);
      }
    }
    if($expireme < 1) {
	continue;
    }
  $database = $spooldir.'/articles-overview.db3';
  $dbh = rslight_db_open($database);
  $query = $dbh->prepare('DELETE FROM overview WHERE newsgroup=:newsgroup AND date<:expireme');
  $query->execute([':newsgroup' => $group, ':expireme' => $expireme]);
  $dbh = null;
  if($CONFIG['article_database'] == '1') {
    $database = $spooldir.'/'.$group.'-articles.db3';
    $articles_dbh = article_db_open($database);
    $articles_query = $articles_dbh->prepare('DELETE FROM articles WHERE newsgroup=:newsgroup AND date<:expireme');
    $articles_query->execute([':newsgroup' => $group, ':expireme' => $expireme]);
    $articles_dbh = null;
  }
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
  unlink($lockfile);
  touch($spooldir.'/'.$config_name.'-expire-timer');
?>
