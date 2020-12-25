<?php
/*  spoolnews NNTP news spool creator
 *  Version: 0.3.0
 *  Download: https://news.novabbs.com/getrslight
 *
 *  E-Mail: retroguy@novabbs.com
 *  Web: https://news.novabbs.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

include "config.inc.php";
include ("$file_newsportal");

$remote_groupfile=$spooldir."/".$config_name."/".$CONFIG['remote_server'].":".$CONFIG['remote_port'].".txt";
$file_groups=$config_path."groups.txt";
$local_groupfile=$spooldir."/".$config_name."/local_groups.txt";
$logfile=$logdir.'/spoolnews.log';

# END MAIN CONFIGURATION
@mkdir($spooldir."/".$config_name,0755,'recursive');

if(!isset($maxarticles_per_run)) {
  $maxarticles_per_run = 100;
}
if(!isset($maxfirstrequest)) {
  $maxfirstrequest = 1000;
}

if(!isset($CONFIG['enable_nntp']) || $CONFIG['enable_nntp'] != true) {
  $maxfirstrequest = $maxarticles;
  $maxarticles_per_run = $maxfetch;
}

$workpath=$spooldir."/";
$path=$workpath."articles/";

$lockfile = sys_get_temp_dir() . '/'.$config_name.'-spoolnews.lock';
$pid = file_get_contents($lockfile);
if (posix_getsid($pid) === false || !is_file($lockfile)) {
   print "Starting Spoolnews...\n";
   file_put_contents($lockfile, getmypid()); // create lockfile
} else {
   print "Spoolnews currently running\n";
   exit;
}

$sem = $spooldir."/".$config_name.".reload";
if(is_file($sem)) {
  unlink($remote_groupfile);
  unlink($sem);
  $maxfirstrequest = 20;
}

# Check for groups file, create if necessary
create_spool_groups($file_groups, $remote_groupfile);
create_spool_groups($file_groups, $local_groupfile);
# Iterate through groups 
$enable_rslight=0;
# Refresh group list
  $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($menulist as $menu) {
    if(($menu[0] == '#') || (trim($menu) == "")) {
      continue;
    }
    $menuitem = explode(':', $menu);
    if(($menuitem[0] == $config_name) && ($menuitem[1] == '1')) {
      groups_read($server,$port,1);
      $enable_rslight = 1;
      echo "Loaded groups\n";
    }
  }
$ns=nntp2_open($CONFIG['remote_server'], $CONFIG['remote_port']);
$ns2=nntp_open();
if($ns == false) {
  file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Failed to connect to ".$CONFIG['remote_server'].":".$CONFIG['remote_port'], FILE_APPEND);
  exit();
}
$grouplist = file($remote_groupfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach($grouplist as $findgroup) {
  $name = explode(':', $findgroup);
  file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Retrieving articles for: ".$name[0]."...", FILE_APPEND);
  echo "\nRetrieving articles for: ".$name[0]."...  \r\n";
  get_articles($ns, $name[0]);

  if($enable_rslight == 1) {
    file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Updating threads for: ".$name[0]."...", FILE_APPEND);
    thread_load_newsserver($ns2,$name[0],0);
  }

}
nntp_close($ns2);
nntp_close($ns);
#expire_overview();
unlink($lockfile);
echo "\nSpoolnews Done\r\n";

function get_articles($ns, $group) {
  global $enable_rslight, $spooldir, $CONFIG, $maxarticles_per_run, $maxfirstrequest, $workpath, $path, $remote_groupfile, $local_groupfile, $local, $logdir, $config_name, $logfile;

  # Prepare search database (this is only for testing atm)
  $database = $spooldir.'/articles-overview.db3';
  $table = 'overview';
  $dbh = rslight_db_open($database, $table);
  $sql = 'INSERT INTO '.$table.'(newsgroup, number, msgid, date, name, subject) VALUES(?,?,?,?,?,?)';
  $stmt = $dbh->prepare($sql);

  if($ns == false) {
    file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Lost connection to ".$CONFIG['remote_server'].":".$CONFIG['remote_port'], FILE_APPEND);
    exit();
  }

  $grouppath = $path.preg_replace('/\./', '/', $group);
  $banned_names = file("/etc/rslight/banned_names.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
 
  $nocem_check="@@NCM";

  # Check if group exists. Open it if it does
  fputs($ns, "group ".$group."\r\n");
  $response = line_read($ns);
  if (strcmp(substr($response,0,3),"411") == 0) {
    echo "\n".$response."\n";
    return(1);
  }
  
  # Get config
  $grouplist = file($remote_groupfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($grouplist as $findgroup) {
    $name = explode(':', $findgroup);
    if (strcmp($name[0], $group) == 0) {
      if (isset($name[1]))
        $article = $name[1] + 1;
      break;
    }
  }
 if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
  $grouplist = file($local_groupfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($grouplist as $findgroup) {
    $name = explode(':', $findgroup);
    if (strcmp($name[0], $group) == 0) {
      if (is_numeric($name[1]))
        $local = $name[1];
      else {
	$thisgroup = $path."/".preg_replace('/\./', '/', $group);
        $articles = scandir($thisgroup);
        $ok_article=array();
        foreach($articles as $this_article) {
        if(!is_numeric($this_article)) {
          continue;
        }
        $ok_article[]=$this_article;
        }
        sort($ok_article);
        $local = $ok_article[key(array_slice($ok_article, -1, 1, true))];
	if(!is_numeric($local))
          $local = 0;
      }
      break;
    }
  } 
  if($local < 1)
    $local = 1;
 } 
  # Split group config line to get last article number
  $detail = explode(" ", $response);
  if (!isset($article)) {
    $article = $detail[2];
  }
  if($article < $detail[3] - $maxfirstrequest) {
    $article = $detail[3] - $maxfirstrequest;
  }
  if($article < $detail[2]) {
    $article = $detail[2];
  }
// Broken message on last run? Let's try again. 
  if($article > ($detail[3] + 1)) {
    $article = $detail[3];
  }
  # Pull articles and save them in our spool
  @mkdir($grouppath,0755,'recursive');
  $i=0;
  while ($article <= $detail[3]) {
      if(!is_numeric($article)) {
	file_put_contents($logfile, "\n".format_log_date()." ".$config_name." DEBUG This should show server group:article number: ".$CONFIG['remote_server']." ".$group.":".$article, FILE_APPEND);
	break;;
      }      
      if($CONFIG['enable_nntp'] != true){
        $local = $article;
      }
// Check for duplicate msgid
      $duplicate=0;
      fputs($ns, "stat ".$article."\r\n");
      $response = line_read($ns);
      $this_msgid = explode(' ', $response);
      $group_overviewfp=fopen($spooldir."/".$group."-overview", 'r');
      while($group_overview=fgets($group_overviewfp, 2048)) {
	$overview_msgid = explode("\t", $group_overview);
	if(strpos($overview_msgid[4], $this_msgid[2]) !== false) {
	  echo "\nDuplicate Message-ID for: ".$CONFIG['remote_server']." ".$group.":".$article."\n";
          file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Duplicate Message-ID for: ".$CONFIG['remote_server']." ".$group.":".$article, FILE_APPEND);
          $article++;
          $duplicate = 1;
          break;
	}
      }
      fclose($group_overviewfp);
      if($duplicate == 1) {
	continue;
      }
      fputs($ns, "article ".$article."\r\n");
      $response = line_read($ns);
      if (strcmp(substr($response,0,3),"220") != 0) {
	echo "\n".$response."\n";
        file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Unexpected response to ARTICLE command: ".$response, FILE_APPEND);
	$article++;
	continue;
      }
      if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true){
        while(is_file($grouppath."/".$local)) {
          $local++;
        }
      }
      $articleHandle = fopen($grouppath."/".$local, 'w+');
      $response = line_read($ns);
      $lines=0;
      $bytes=0;
      $ref=0;
      $banned=0;
      $is_header=1;
      while(strcmp($response,".") != 0) 
      {
	$bytes = $bytes + mb_strlen($response, '8bit');	
	if(trim($response) == "" || $lines > 0) {
	  $is_header=0;
	  $lines++;
	}
       if($is_header == 1) {
	// Find article date
	if(stripos($response, "Date: ") === 0) {
	  $finddate=explode(': ', $response);
	  $article_date = strtotime($finddate[1]);
	}
	// Get overview data
        if(stripos($response, "Message-ID: ") === 0) {
          $mid=explode(': ', $response);
	  $ref=0;
        }
        if(stripos($response, "From: ") === 0) {
          $from=explode(': ', $response);
	  if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) { 
	    foreach($banned_names as $banned_name) {
	      if(stripos($from[1], $banned_name) !== false) {
	        $banned = 1;
	      }
	    }
	  }
	  $ref=0;
        }
        if(stripos($response, "Subject: ") === 0) {
          $subject=explode('Subject: ', $response, 2);
	  $ref=0;
        }
	if(stripos($response, "Newsgroups: ") === 0) {
	  $response=str_ireplace($group,$group,$response);
          $ref=0;
        }
	if(stripos($response, "Xref: ") === 0) {
	  if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
	    $response="Xref: ".$CONFIG['pathhost']." ".$group.":".$local;
	  } 
          $xref=$response;
	  $ref=0;
        }
	if(stripos($response, "References: ") === 0) {
	  $this_references=explode('References: ', $response);
	  $references = $this_references[1];
	  $ref=1;
	}
	if((stripos($response, ':') === false) && (strpos($response, '>'))) {
	  if($ref == 1) {
  	    $references=$references.$response;
	  }
	}
       }
       fputs($articleHandle, $response."\n");
// Check here for broken $ns connection before continuing
       $response=fgets($ns,1200);
       if($response == false) {
	 file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Lost connection to ".$CONFIG['remote_server'].":".$CONFIG['remote_port']." retrieving article ".$article, FILE_APPEND);
	 @fclose($articleHandle);
	 unlink($grouppath."/".$local);
	 continue;
       }
       $response=str_replace("\n","",str_replace("\r","",$response));
      }
      fputs($articleHandle, $response."\n");
      @fclose($articleHandle);
      $lines=$lines-1;
      $bytes = $bytes + ($lines * 2);
// Don't spool article if $banned=1
       if($banned == 1) {
         @fclose($articleHandle);
         unlink($grouppath."/".$local);
         file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Skipping: ".$CONFIG['remote_server']." ".$group.":".$article." user: ".$from[1]." is banned", FILE_APPEND);
	 $article++;
       } else {
      if((strpos($CONFIG['nocem_groups'], $group) !== false) && ($CONFIG['enable_nocem'] == true)) {
	if(strpos($subject[1], $nocem_check) !== false) {
	  $nocem_file = tempnam($spooldir."/nocem", "nocem-".$group."-");
	  copy($grouppath."/".$local, $nocem_file);
        }
      }
// Overview
      $overviewHandle = fopen($workpath.$group."-overview", 'a');
      fputs($overviewHandle, $local."\t".$subject[1]."\t".$from[1]."\t".$finddate[1]."\t".$mid[1]."\t".$references."\t".$bytes."\t".$lines."\t".$xref."\n");
      fclose($overviewHandle);
      $references="";
// add to search database
	$stmt->execute([$group, $local, $mid[1], $article_date, $from[1], $subject[1]]);
// End Overview

      if($article_date > time())
        $article_date = time();
      touch($grouppath."/".$local, $article_date);
      echo "\nRetrieved: ".$group." ".$article."\n";
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Wrote to spool: ".$CONFIG['remote_server']." ".$group.":".$article, FILE_APPEND);
      $i++;
      if($i > $maxarticles_per_run)
	break;
      $article++;
      $local++; 
    }
  }
  $article--;
//  $local--;
// Update title
   if(!is_file($workpath.$group."-title")) {
      fputs($ns, "XGTITLE ".$group."\r\n");
      $response = line_read($ns);
      if (strcmp(substr($response,0,3),"282") == 0) { 
        $overviewHandle = fopen($workpath.$group."-title", 'w');
        $response = line_read($ns);
        while(strcmp($response,".") != 0)
        {
          fputs($overviewHandle, $response."\r\n");
          $response = line_read($ns);
        }
        @fclose($overviewHandle);
      }
   }
  # Save config
  $grouplist = file($remote_groupfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $saveconfig = fopen($remote_groupfile, 'w+');
  foreach($grouplist as $savegroup) {
    $name = explode(':', $savegroup);
    if (strcmp($name[0], $group) == 0) {
      fputs($saveconfig, $group.":".$article."\n");
    } else {
      fputs($saveconfig, $savegroup."\n");
    }
  }
  fclose($saveconfig);
  $grouplist = file($local_groupfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $saveconfig = fopen($local_groupfile, 'w+');
  foreach($grouplist as $savegroup) {
    $name = explode(':', $savegroup);
    if (strcmp($name[0], $group) == 0) {
      fputs($saveconfig, $group.":".$local."\n");
    } else {
      fputs($saveconfig, $savegroup."\n");
    }
  }
  fclose($saveconfig);
  $dbh = null;
}

function create_spool_groups($in_groups, $out_groups) {
  $grouplist = file($in_groups, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $groupout = fopen($out_groups, "a+");
  foreach($grouplist as $group) {
    if($group[0] == ":") {
      continue;
    }
    $thisgroup = preg_split("/( |\t)/", $group, 2);
    fseek($groupout, 0);
    $found=0;
    while (($buffer = fgets($groupout)) !== false) {
      if (stripos($buffer, $thisgroup[0]) !== false) {
        $found = 1;
        break;
      }
    }
    if($found == 0) {
      fwrite($groupout, $thisgroup[0]."\r\n");
      continue;
    }
  }
  fclose($groupout);
  return;
}

function nntp2_open($nserver=0,$nport=0) {
  global $text_error,$CONFIG;
  // echo "<br>NNTP OPEN<br>";
  $authorize=((isset($CONFIG['remote_auth_user'])) && (isset($CONFIG['remote_auth_pass'])) &&
              ($CONFIG['remote_auth_user'] != ""));
  if ($nserver==0) $nserver=$CONFIG['remote_server'];
  if ($nport==0) $nport=$CONFIG['remote_port'];
  if($CONFIG['remote_ssl']) {
    $ns=@fsockopen('ssl://'.$nserver.":".$nport);
  } else {
    $ns=@fsockopen('tcp://'.$nserver.":".$nport);
  }
//  $ns=@fsockopen($nserver,$nport);
  $weg=line_read($ns);  // kill the first line
  if (substr($weg,0,2) != "20") {
    echo "<p>".$text_error["error:"].$weg."</p>";
    fclose($ns);
    $ns=false;
  } else {
    if ($ns != false) {
      fputs($ns,"MODE reader\r\n");
      $weg=line_read($ns);  // and once more
      if ((substr($weg,0,2) != "20") &&
          ((!$authorize) || ((substr($weg,0,3) != "480") && ($authorize)))) {
        echo "<p>".$text_error["error:"].$weg."</p>";
        fclose($ns);
        $ns=false;
      }
    }
    if ((isset($CONFIG['remote_auth_user'])) && (isset($CONFIG['remote_auth_pass'])) &&
        ($CONFIG['remote_auth_user'] != "")) {
      fputs($ns,"AUTHINFO USER ".$CONFIG['remote_auth_user']."\r\n");
      $weg=line_read($ns);
      fputs($ns,"AUTHINFO PASS ".$CONFIG['remote_auth_pass']."\r\n");
      $weg=line_read($ns);
/* Only check auth if reading and posting same server */
      if (substr($weg,0,3) != "281" && !(isset($post_server)) && ($post_server!="")) {
        echo "<p>".$text_error["error:"]."</p>";
        echo "<p>".$text_error["auth_error"]."</p>";
      }
    }
  }
  if ($ns==false) echo "<p>".$text_error["connection_failed"]."</p>";
  return $ns;
}
?>
