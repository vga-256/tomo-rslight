<?php
/* This script allows importing a group .db3 file from a backup
 * or another rslight site, and other features.
 * 
 * Use -help to see other features.
 * 
 * To import a group db3 file:
 * Place the article database file group.name-articles.db3 in 
 * your spool directory, and change user/group to your web user.
 * Run this script as your web user from your $webdir/spoolnews dir:
 * php $config_dir/scripts/import.php group.name
 *
 * To import/upgrade all group.db3 files, do not list group.name 
 * after the above command.
 *
 * This will create the overview files necessary to import the group
 * into your site.
 * Next: Add the group to the groups.txt file of the section you wish
 * it to appear:
 * $config_dir/<section>/groups.txt
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
        case "-remove":
            echo "Removing: ".$argv[2]."\n";
            remove_articles($argv[2]);
            reset_group($argv[2], 1);
            break;
        case "-reset":
            echo "Reset: ".$argv[2]."\n";
            remove_articles($argv[2]);
            reset_group($argv[2], 0);
            break;
        case "-import":
            if(isset($argv[2])) {
                import($argv[2]);
            } else {
                import();
            }
            break;
        case "-clean":
            clean_spool();
            break;
        default:
            echo "-help: This help page\n";
            echo "-version: Display version\n";
            echo "-clean: Remove extraneous group db3 files\n";
            echo "-import: Import articles from a .db3 file (-import alt.test-articles.db3)\n";
            echo "         You must also add group name to <config_dir>/<section>/groups.txt manually\n";
            echo "-remove: Remove all data for a group (-remove alt.test)\n";
            echo "         You must also remove group name from <config_dir>/<section>/groups.txt manually\n";
            echo "-reset: Reset a group to restart from zero messages (-reset alt.test)\n";
            break;
    }
    exit();
} else {
    exit();
}


function clean_spool() {
    global $logfile, $workpath, $spooldir;
    $workpath=$spooldir."/";
    $path=$workpath."articles/";
    $group_list = get_group_list();
    $group = trim($group);
    $group_files = scandir($workpath);
    foreach($group_files as $this_file) {
        if(strpos($this_file, '-articles.db3') === false) {
            continue;
        }
        $group = preg_replace('/-articles.db3/', '', $this_file);
        if (in_array($group, $group_list)) {
            continue;
        } else {
            echo "Removing: ".$this_file."\n";
            remove_articles($group);
            reset_group($group, 1);
        }
    }
    echo "\nImport Done\r\n";
}

function import($group = '') {
  global $logfile, $workpath, $spooldir;
  $workpath=$spooldir."/";
  $path=$workpath."articles/";
  $group_list = get_group_list();
  $group = trim($group);
  if($group == '') {
    $group_files = scandir($workpath);
    foreach($group_files as $this_file) {
      if(strpos($this_file, '-articles.db3') === false) {
        continue;
      } 
      $group = preg_replace('/-articles.db3/', '', $this_file);
      if (in_array($group, $group_list)) {
        echo "Importing: ".$group."\n";
        import_articles($group);
      } else {
        echo "Removing: ".$group."\n";
        remove_articles($group);
        reset_group($group, 1);
      }
    }
  } else {
    echo "Importing: ".$group."\n";
    import_articles($group);
  }
  echo "\nImport Done\r\n";
}

function get_group_list() {
    global $config_dir;
    $grouplist = array();
    $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($menulist as $menu) {
        if($menu[0] == '#') {
            continue;
        }
        $menuitem=explode(':', $menu);
        if($menuitem[2] == '0') {
            continue;
        }
        $glist = file($config_dir.$menuitem[0]."/groups.txt");
        foreach($glist as $gl) {
            if($gl[0] == ':') {
                continue;
            }
            $group_name = preg_split("/( |\t)/", $gl, 2);
            $grouplist[] = trim($group_name[0]);
        }
    }
    return $grouplist;
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
}

function import_articles($group) {
  global $spooldir, $CONFIG, $workpath, $path, $config_name, $logfile;
  $overview_file = $workpath.'/'.$group."-overview";
  # Prepare databases
// Overview db
  $new_article_dbh = article_db_open($spooldir.'/'.$group.'-articles.db3-new');
  $new_article_sql = 'INSERT OR IGNORE INTO articles(newsgroup, number, msgid, date, name, subject, article, search_snippet) VALUES(?,?,?,?,?,?,?,?)';
  $new_article_stmt = $new_article_dbh->prepare($new_article_sql);
  $database = $spooldir.'/articles-overview.db3';
  $table = 'overview';
  $dbh = rslight_db_open($database, $table);
  $clear_stmt = $dbh->prepare("DELETE FROM overview WHERE newsgroup=:group");
  $clear_stmt->bindParam(':group', $group);
  $clear_stmt->execute();
  unlink($overview_file);

  $sql = 'INSERT OR IGNORE INTO '.$table.'(newsgroup, number, msgid, date, name, subject) VALUES(?,?,?,?,?,?)';
  $stmt = $dbh->prepare($sql);
// Incoming db
  $article_dbh = article_db_open($spooldir.'/'.$group.'-articles.db3');
  $article_stmt = $article_dbh->query('SELECT DISTINCT * FROM articles');
  while ($row = $article_stmt->fetch()) {
      $local = $row['number'];
      $this_article = preg_split("/\r\n|\n|\r/", $row['article']);
      $lines=0;
      $bytes=0;
      $ref=0;
      $banned=0;
      $is_header=1;
      $body="";
      foreach($this_article as $response) 
      {
	$bytes = $bytes + mb_strlen($response, '8bit');	
	if(trim($response) == "" || $lines > 0) {
	  $is_header=0;
	  $lines++;
	}
       if($is_header == 1) {
	$response = str_replace("\t", " ", $response);
	// Find article date
	if(stripos($response, "Date: ") === 0) {
	  $finddate=explode(': ', $response, 2);
	  $ref=0;
	}
	// Get overview data
        $mid[1] = $row['msgid'];
	$from[1] = $row['name'];
	$subject[1] = $row['subject'];
	$article_date = $row['date'];
	
	if(stripos($response, "Xref: ") === 0) {
	  if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
	    $response="Xref: ".$CONFIG['pathhost']." ".$group.":".$local;
	  } 
          $xref=$response;
	  $ref=0;
        }
        if(stripos($response, "Content-Type: ") === 0) {
          preg_match('/.*charset=.*/', $response, $te);
          $content_type = explode("Content-Type: text/plain; charset=", $te[0]);
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
       $response=str_replace("\n","",str_replace("\r","",$response));
      } else {
       $body.=$response."\n";
      }
   }
      $lines=$lines-1;
      $bytes = $bytes + ($lines * 2);
// add to database
// CREATE SEARCH SNIPPET
      $this_snippet = get_search_snippet($body, $content_type[1]);
      $new_article_stmt->execute([$group, $local, $mid[1], $article_date, $from[1], $subject[1], $row['article'], $this_snippet]);

      $stmt->execute([$group, $local, $mid[1], $article_date, $from[1], $subject[1]]);
      file_put_contents($overview_file, $local."\t".$subject[1]."\t".$from[1]."\t".$finddate[1]."\t".$mid[1]."\t".$references."\t".$bytes."\t".$lines."\t".$xref."\n", FILE_APPEND);
      echo "\nImported: ".$group." ".$local;
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Imported: ".$group.":".$local, FILE_APPEND);
      $i++;
      $references="";
  }
  $new_article_dbh = null;
  $article_dbh = null;
  $dbh = null;
  unlink($spooldir.'/'.$group.'-articles.db3');
  rename($spooldir.'/'.$group.'-articles.db3-new', $spooldir.'/'.$group.'-articles.db3');
  unlink($spooldir.'/'.$group.'-data.dat');
  unlink($spooldir.'/'.$group.'-info.txt');
  unlink($spooldir.'/'.$group.'-cache.txt');
  unlink($spooldir.'/'.$group.'-lastarticleinfo.dat');
  unlink($spooldir.'/'.$group.'-overboard.dat');
}
?>
