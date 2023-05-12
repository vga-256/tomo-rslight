<?php
/* This script allows importing a group .db3 file from a backup
 * or another rslight site, and other features.
 * 
 * Use -help to see features.
*/

include "config.inc.php";
include ("$file_newsportal");

$logfile=$logdir.'/import.log';

$lockfile = $lockdir . '/'.$config_name.'-spoolnews.lock';
$pid = file_get_contents($lockfile);
if (posix_getsid($pid) === false || !is_file($lockfile)) {
   print "Starting Import...\n";
   file_put_contents($lockfile, getmypid()); // create lockfile
} else {
   print "Import currently running\n";
   exit;
}

if(!isset($argv[1])) {
    $argv[1] = "-help";
}
if($argv[1][0] == '-') {
    switch ($argv[1]) {
        case "-version":
            echo 'Version '.$rslight_version."\n";
            break;
        case "-import":
            if(isset($argv[2])) {
                import($argv[2]);
            } else {
                echo "No group selected\n";
                break;
            }
            break;
        default:
            echo "-help: This help page\n";
            echo "-version: Display version\n";
            echo "-import: Import articles manually entered into tradspool\n";
            echo "         (-import alt.test)\n";
            echo "         You must first add group name to <config_dir>/<section>/groups.txt manually\n";
            break;
    }
    exit();
} else {
    exit();
}

function import($group) {
  global $logfile, $workpath, $spooldir;
  $workpath=$spooldir."/";
  $path=$workpath."articles/";
  $group = trim($group);
  if($group == '') {
      echo "No group selected\n";
      return;
  } else {
      $grouppath = preg_replace("/\./", "/", $group);
      $grouparticles = scandir($spooldir.'/articles/'.$grouppath);
      echo "Importing: ".$group."\n";
      import_articles($group, $grouppath, $grouparticles);
      }
  echo "\nImport Done\r\n";
  return;
}

function import_articles($group, $grouppath, $grouparticles) {
  global $spooldir, $CONFIG, $workpath, $path, $config_name, $logfile;
  $group_overviewfile = $spooldir."/".$group."-overview";
  $gover = file($group_overviewfile);
  foreach($gover as $group_overview) {
      $overview_msgid = explode("\t", $group_overview);
      $msgids[trim($overview_msgid[4])] = true;
  }
  $database = $spooldir.'/articles-overview.db3';
  $table = 'overview';
  $dbh = rslight_db_open($database, $table);
  $sql = 'INSERT INTO '.$table.'(newsgroup, number, msgid, date, name, subject) VALUES(?,?,?,?,?,?)';
  $stmt = $dbh->prepare($sql);
  foreach($grouparticles as $article) {
      if($article == '.' || $article == '..') {
          continue;
      }
      $this_article = $spooldir.'articles/'.$grouppath.'/'.$article;
      $article_content = file($this_article);

      $lines=0;
      $bytes=0;
      $ref=0;
      $is_header=1;
      $body="";
      $skip=0;
      unset($mid);
      foreach($article_content as $response) {
          $bytes = $bytes + mb_strlen($response, '8bit');
          if(trim($response) == "") {
              $is_header=0;
              $lines++;
          }
          if($is_header == 1) {
              $response = str_replace("\t", " ", $response);
              if(stripos($response, "Message-ID: ") === 0) {
                  $mid=explode(': ', $response, 2);
                  $ref=0;
              }
              if($msgids[trim($mid[1])] == true) {
                  echo "Duplicate Message-ID for ".$group.":".$article."\n";
                  $skip=1;
                  break;
              }
              if(stripos($response, "From: ") === 0) {
                  $from=explode(': ', $response, 2);
              }
              if(stripos($response, "Date: ") === 0) {
                  $finddate=explode(': ', $response, 2);
                  $article_date = strtotime($finddate[1]);
              }
              if(stripos($response, "Subject: ") === 0) {
                  $subject=explode('Subject: ', $response, 2);
                  $ref=0;
              }
              if(stripos($response, "Xref: ") === 0) {
                  if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
                      $response="Xref: ".$CONFIG['pathhost']." ".$group.":".$article;
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
          } else {
              $lines++;
          }
      }
      if($skip == 0) {
     // Write to overview. Fix $article to proper article number. Check for duplicate.
     echo "Adding ".$group.":".$article." to overview\n";
     $stmt->execute([$group, $article, trim($mid[1]), $article_date, trim($from[1]), trim($subject[1])]);
     file_put_contents($group_overviewfile, $article."\t".trim($subject[1])."\t".trim($from[1])."\t".trim($finddate[1])."\t".trim($mid[1])."\t".trim($references)."\t".$bytes."\t".$lines."\t".$xref."\n", FILE_APPEND);
      continue;
      }
    }
    $dbh = null;
} 
?>
