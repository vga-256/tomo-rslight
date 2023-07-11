<?php

  include "config.inc.php";
  include "lib/delete.inc.php";
  include ("$file_newsportal");

  if(!isset($CONFIG['enable_nocem']) || $CONFIG['enable_nocem'] != true) {
    exit;
  }

  $lockfile = $lockdir . '/rslight-spoolnews.lock';
  $pid = file_get_contents($lockfile);
  if (posix_getsid($pid) === false || !is_file($lockfile)) {
    print "Starting nocem...\n";
    file_put_contents($lockfile, getmypid()); // create lockfile
  } else {
    print "nocem currently running\n";
    exit;
  }

  putenv("GNUPGHOME=".$config_dir.".gnupg");
  $webserver_group=$CONFIG['webserver_user'];
  $logfile=$logdir.'/nocem.log';
  @mkdir($spooldir."/nocem/processed",0755,'recursive');
  @mkdir($spooldir."/nocem/failed",0755,'recursive');

  $nocem_path=$spooldir."/nocem/";
  $messages=scandir($nocem_path);
  $begin="@@BEGIN NCM BODY";
  $end="@@END NCM BODY";

  foreach($messages as $message) {
    $nocem_file=$nocem_path.$message;
    if(!is_file($nocem_file)) {
      continue;
    }
    $signed_text=file_get_contents($nocem_file);
    if(verify_signature($signed_text) == 1) {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Bad signature  in: ".$message, FILE_APPEND);
      echo "Bad signature in: ".$message."\r\n";
      rename($nocem_file, $nocem_path."failed/".$message);
      continue; 
    } else {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Good signature in: ".$message, FILE_APPEND);
      echo "Good signature in: ".$message."\r\n";
    }
    $nocem_list=file($nocem_file, FILE_IGNORE_NEW_LINES);
    $start=0;
    foreach($nocem_list as $nocem_line) {
      if(strpos($nocem_line, $begin) !== false) {
        $start=1;
        continue;
      } 
      if(strpos($nocem_line, $end) !== false) {
	break;
      }
      if((isset($nocem_line[0]) && $nocem_line[0] == '<') && $start == 1) {
        $found = explode(' ', $nocem_line);
	$msgid = $found[0];
	foreach($found as $found_group) {
	  delete_message($msgid, $found_group);
	}
      }
    }  
  rename($nocem_file, $nocem_path."processed/".$message);
  }
  unlink($lockfile);
  exit;

function verify_signature($signed_text) {
  $plaintext = "";
  $res = gnupg_init();
  $info = gnupg_verify($res,$signed_text,false,$plaintext);

  if($info[0]['status'] == 0 && $info[0]['summary'] == 0) {
    return 0;
  } else {
    return 1;
  }
}

// searches for a specified message ID and returns true (found) or false (not found)
function search_message_id($messageid)
{
	// borrowed from article-flat.php
	// make sure the message-id contains an @, so it has an originating domain
	if(strpos($messageid, '@') !== false) {
      if($CONFIG['article_database'] == '1') {
        $database = $spooldir.'/articles-overview.db3';
        $articles_dbh = rslight_db_open($database);
        $articles_query = $articles_dbh->prepare('SELECT * FROM overview WHERE msgid=:messageid');
        $articles_query->execute(['messageid' => $id]);
        $found = 0;
        while ($row = $articles_query->fetch()) {
          $id = $row['number'];
          $group = $row['newsgroup'];
          $found = 1;
          break;
        }
        $dbh = null;
        if($found) {
          $newurl = 'article-flat.php?id='.$id.'&group='.$group.'#'.$id;
          die();
        }
      }
  }
}

?>
