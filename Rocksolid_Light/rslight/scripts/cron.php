<?php
// This file runs maintenance scripts and should be executed by cron regularly
  include "config.inc.php";

  $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

# Start or verify NNTP server
  if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
    # Create group list for nntp.php
    $fp1=fopen($spooldir."/".$config_name."/groups.txt", 'w');
      foreach($menulist as $menu) {
      if(($menu[0] == '#') || trim($menu) == "") {
	continue;
      }
      $menuitem=explode(':', $menu);
      if($menuitem[2] == '1') {
        $in_gl = file($config_dir.$menuitem[0]."/groups.txt");
	foreach($in_gl as $ok_group) {
	  if(($ok_group[0] == ':') || (trim($ok_group) == "")) {
	    continue;
	  }
	  $ok_group = preg_split("/( |\t)/", trim($ok_group), 2);
	  fputs($fp1, $ok_group[0]."\r\n");
	}
      }
    }
    fclose($fp1);
    exec($CONFIG['php_exec']." ".$config_dir."/scripts/nntp.php > /dev/null 2>&1");
    if(is_numeric($CONFIG['local_ssl_port'])) {
      exec($CONFIG['php_exec']." ".$config_dir."/scripts/nntp-ssl.php > /dev/null 2>&1");
    }
  }
# Generate user count file (must be root)
  exec($CONFIG['php_exec']." ".$config_dir."/scripts/count_users.php");
  echo "Updated user count\n";

  $cwd = getcwd();
  $webtmp = preg_replace('/spoolnews/','tmp',$cwd);
  @mkdir($webtmp,0755,'recursive');
  @chown($webtmp, $CONFIG['webserver_user']);
  @chgrp($webtmp, $CONFIG['webserver_user']);

# Fix this. It shouldn't be necessary
  $overview = $spooldir.'/articles-overview.db3';
  touch($overview);
  @chown($overview, $CONFIG['webserver_user']);
  @chgrp($overview, $CONFIG['webserver_user']);

/* Change to non root user */
  $uinfo=posix_getpwnam($CONFIG['webserver_user']);
  change_identity($uinfo["uid"],$uinfo["gid"]);
/* Everything below runs as $CONFIG['webserver_user'] */

  @mkdir($logdir,0755,'recursive');
  @mkdir($lockdir,0755,'recursive');

if(isset($CONFIG['enable_nocem']) && $CONFIG['enable_nocem'] == true) {
  @mkdir($spooldir."nocem",0755,'recursive');
  exec($CONFIG['php_exec']." ".$config_dir."/scripts/nocem.php");
}

reset($menulist);
foreach($menulist as $menu) {
  if(($menu[0] == '#') || (trim($menu) == "")) {
    continue;
  }
  $menuitem=explode(':', $menu);
  chdir("../".$menuitem[0]);
 if($CONFIG['remote_server'] !== '') {
# Send articles
  echo "Sending articles\n";
  echo exec($CONFIG['php_exec']." ".$config_dir."/scripts/send.php");
# Refresh spool
  if(isset($spoolnews) && ($spoolnews == true)) {
    exec($CONFIG['php_exec']." ".$config_dir."/scripts/spoolnews.php");
    echo "Refreshed spoolnews\n";
  }
 }
# Expire articles
  exec($CONFIG['php_exec']." ".$config_dir."/scripts/expire.php");
  echo "Expired articles\n";
}
# Run RSS Feeds
  exec($CONFIG['php_exec']." ".$config_dir."/scripts/rss-feeds.php");
  echo "RSS Feeds updated\n";
# Rotate log files
  log_rotate();
  echo "Log files rotated\n";
# Rotate keys
  rotate_keys();
  echo "Keys rotated\n";

function log_rotate() {
  global $logdir;
  $rotate = filemtime($logdir.'/rotate');
  if((time() - $rotate) > 86400) {
    $log_files=scandir($logdir);
    foreach($log_files as $logfile) {
      if(substr($logfile, -4) != '.log' ) {
        continue;
      }
      $logfile=$logdir.'/'.$logfile;
      @unlink($logfile.'.5');
      @rename($logfile.'.4', $logfile.'.5');
      @rename($logfile.'.3', $logfile.'.4');
      @rename($logfile.'.2', $logfile.'.3');
      @rename($logfile.'.1', $logfile.'.2');
      @rename($logfile, $logfile.'.1');
      echo 'Rotated: '.$logfile."\n";
    }
    unlink($logdir.'/rotate');
    touch($logdir.'/rotate');
  }
}

function rotate_keys() {
  global $spooldir;
  $keyfile = $spooldir.'/keys.dat';
  $newkeys = array();
  if(filemtime($keyfile)+14400 > time()) {
    return;
  } else {
    $new = true;
    if(is_file($keyfile)) {
      $keys = unserialize(file_get_contents($keyfile));
      $new = false;
    }  
    if($new !== true) {
      $newkeys[0] = base64_encode(openssl_random_pseudo_bytes(44));
      $newkeys[1] = $keys[0];
    } else {
      $newkeys[0] = base64_encode(openssl_random_pseudo_bytes(44));
      $newkeys[1] = base64_encode(openssl_random_pseudo_bytes(44));
    }
  }
  file_put_contents($keyfile, serialize($newkeys));
  touch($keyfile);
}

function change_identity( $uid, $gid )
    {
        if( !posix_setgid( $gid ) )
        {
            print "Unable to setgid to " . $gid . "!\n";
            exit;
        }

        if( !posix_setuid( $uid ) )
        {
            print "Unable to setuid to " . $uid . "!\n";
            exit;
        }
    }
?>
