<?php
    session_start();
/*  rocksolid overboard - overboard for rslight
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
?>

<?php
  include "config.inc.php";
  include "auth.inc";
  include "$file_newsportal";

  throttle_hits();
  write_access_log();
  if(isset($_COOKIE['mail_name'])) {
    if($userdata = get_user_mail_auth_data($_COOKIE['mail_name'])) {
      $userfile=$spooldir.'/'.strtolower($_COOKIE['mail_name']).'-articleviews.dat';
    }
  }
if(isset($frames_on) && $frames_on === true) {
?>
<script>
    var contentURL=window.location.pathname+window.location.search+window.location.hash;
    if ( window.self !== window.top ) {
        /* Great! now we move along */
    } else {
        window.location.href = '../index.php?content='+encodeURIComponent(contentURL);
    }
    top.history.replaceState({}, 'Title', 'index.php?content='+encodeURIComponent(contentURL));
</script>

<?php
}
if (isset($_GET['thisgroup'])) {
  $title.=" - "._rawurldecode(_rawurldecode($_GET['thisgroup']))." - latest messages";
} else {
  $title.=" - ".$config_name." - overboard"; 
}
include "head.inc";
$CONFIG = include($config_file);
$logfile=$logdir.'/overboard.log';

# How many days old should articles be displayed?
if (isset($_GET['thisgroup'])) {
  $article_age = 30;
} else {
  $article_age = 30;
}

$version = 1.1;

# How long in seconds to cache results
$cachetime = 60;

# Maximum number of articles to show
$maxdisplay = 1000;

# How many characters of the body to display per article
$snippetlength = 240;

$spoolpath_regexp = '/'.preg_replace('/\//', '\\/', $spoolpath).'/';
$thissite = '.';

$groupconfig=$file_groups;
$cachefile=$spooldir."/".$config_name."-overboard.dat";
$oldest = (time() - (86400 * $article_age));
$prune = false;

if (isset($_GET['time'])) {
  $user_time = $_GET['time'];
  if(is_numeric($user_time)) {
    if(($user_time > time()) || ($user_time < $oldest)) {
      unset($user_time);
    }
  } else {
    unset($user_time);
  }
}

if (isset($_GET['thisgroup'])) {
  $grouplist = array();
  $grouplist[0] = _rawurldecode(_rawurldecode($_GET['thisgroup']));
  $cachefile=$spooldir."/".$grouplist[0]."-overboard.dat";
  if($userdata) {
    $userdata[$grouplist[0]] = time();
    file_put_contents($userfile, serialize($userdata));
  }
} else {
  $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
}

show_overboard_header($grouplist);

$results=0;

if(is_file($cachefile)) {
  $stats = stat($cachefile);
  $this_overboard = unserialize(file_get_contents($cachefile));
  $cachedate = ($this_overboard['lastmessage'] - 86400);
  $oldest = $cachedate;
} else {
  $cachedate = ($oldest - 86400);
}
if($this_overboard['version'] !== $version) {
  unset($this_overboard);
  unlink($cachefile);
  $this_overboard['version'] = $version;
  $cachedate = ($oldest - 86400);
}

# Iterate through groups

$database = $spooldir.'/articles-overview.db3';
$table = 'overview';
$dbh = rslight_db_open($database, $table);
$query = $dbh->prepare('SELECT * FROM '.$table.' WHERE newsgroup=:findgroup AND date >= '.$cachedate.' ORDER BY date DESC LIMIT '.$maxdisplay);
$articles = array();
$db_articles = array();
foreach($grouplist as $findgroup) {
	$groups = preg_split("/(\ |\t)/", $findgroup, 2);
	$findgroup = $groups[0];

	$overboard_noshow = explode(' ', $CONFIG['overboard_noshow']);
	foreach($overboard_noshow as $noshow) {
	  if ((strpos($findgroup, $noshow) !== false) && !isset($_GET['thisgroup'])) {
	    continue 2;
	  }
	}
	$thisgroup = preg_replace('/\./', '/', $findgroup);
          if($dbh) {
            $query->execute(['findgroup' => $findgroup]);
	    $i=0;
	    while (($overviewline = $query->fetch()) !== false) {
          $articles[] = $spoolpath.$thisgroup.'/'.$overviewline['number'];
	      $db_articles[] = $findgroup.':'.$overviewline['number'].':'.$overviewline['date'].':'.$overviewline['name'];
	      $i++;
	      if($i > $maxdisplay) {
		break;
	      }
	    }
	  }	  
}
$dbh = null;

$files = array();
if($CONFIG['article_database'] == '1') {
  foreach($db_articles as $article) {
    $order=explode(':', $article);
    $files[$order[2]] = $article;
  }
} else {
  foreach($articles as $article) {
    if(is_dir($article)) {
		continue;
    }
    $files[filemtime($article)] = $article;
  }
}
krsort($files);

foreach($files as $article) {
    if($CONFIG['article_database'] == '1') {
      $data = explode(':', $article);
      $articledata = np_get_db_article($data[1], $data[0], 0);
    } else {
      $articledata = file_get_contents($article);
    }
    $bodystart = strpos($articledata, $localeol);
    $header = substr($articledata, 0, $bodystart);

    # Find group name and article number
    if($CONFIG['article_database'] == '1') {
      $group = $data[0];
      $articlenumber = $data[1];
      $groupname = $group;
    } else {
      $group = preg_replace($spoolpath_regexp, '', $article);
      $group = preg_replace('/\//', '.', $group);
      $findme = strrpos($group, '.');
      $groupname = substr($group, 0, $findme);
      $articlenumber = substr($group, $findme+1);
    }

    preg_match('/Message-ID:.*/i', $header, $articleid);
    $getid = explode(": ", $articleid[0]);
    $thismsgid = $getid[1];
    if(isset($this_overboard['msgids'][$thismsgid])) {
      continue;
    }
    
    preg_match('/References:.*/i', $header, $ref);
    $getrefs = explode(': ', $ref[0]);
    $ref = preg_split("/[\s]+/", $getrefs[1]);
    if($getrefs[1] && $refid = get_data_from_msgid($ref[0])) {
      // Check that article to link is new enough for newsportal to display
      $groupinfo = file($spooldir.'/'.$refid[newsgroup].'-info.txt');
      $range = explode(' ', $groupinfo[1]);
      if($refid[number] > ($range[0] - 1)) {
        $threadref = $ref[0];
      } else {
        $threadref = false;
      }
    } else {
      $threadref = false;
    }

    $target = get_data_from_msgid($thismsgid);
    if($target['date'] > time()) {
      continue;
    }
    if($target['date'] > $this_overboard['lastmessage']) {
      $this_overboard['lastmessage'] = $target['date'];
    }
    if(!isset($this_overboard['threads'][$target['date']])) {
      $this_overboard['threads'][$target['date']] = $thismsgid;
      $this_overboard['msgids'][$thismsgid] = $target;
      if($threadref) {
        $this_overboard['threadlink'][$thismsgid] = $threadref;
      }
      if($results++ > ($maxdisplay - 2)) {
  	    break;
      }
    }
}

file_put_contents($cachefile, serialize($this_overboard));
if(isset($user_time)) {
  $oldest = ($user_time - 900);
} else {
  $oldest = (time() - (86400 * $article_age));
}
$results = display_threads($this_overboard['threads'], $oldest);
show_overboard_footer(null, $results, null);
echo '</body></html>';
expire_overboard($cachefile);

function expire_overboard($cachefile) {
  global $article_age, $logfile, $config_name, $prune, $this_overboard;
    if($this_overboard['expire'] < (time() - 86400)) {
      $prune = true;
      foreach($this_overboard['msgids'] as $key => $value) {
        $target = $this_overboard['msgids'][$key];
        if($target['date'] < (time() - (86400 * $article_age))) {
          file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Expiring: ".$key, FILE_APPEND);
          unset($this_overboard['threads'][$target['date']]);
          unset($this_overboard['msgids'][$key]);
          unset($this_overboard['threadlink'][$key]);
        }
      }
      $this_overboard['expire'] = time();
    }
    if($prune) {
      file_put_contents($cachefile, serialize($this_overboard));
    }
}

function display_threads($threads, $oldest) {
    global $thissite, $logfile, $config_name, $snippetlength, $maxdisplay, $prune, $this_overboard;
    echo '<table cellspacing="0" width="100%" class="np_results_table">';
    krsort($threads);
    $results = 0;
    foreach($threads as $key => $value) {
      $target = $this_overboard['msgids'][$value];
      if(!isset($target['msgid'])) {
        $target = get_data_from_msgid($value);
      }
      if($target['date'] < $oldest) {
          continue;
      }
      if($results > $maxdisplay) {
        $prune = true;
        unset($this_overboard['threads'][$target['date']]);
        unset($this_overboard['threadlink'][$value]);
        file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Pruning: ".$value, FILE_APPEND);
      }
      $article = get_db_data_from_msgid($target['msgid'], $target['newsgroup'], 1);
        $poster = get_poster_name(mb_decode_mimeheader($target['name']));
        $groupurl = $thissite."/thread.php?group="._rawurlencode($target['newsgroup']);
        if(($results % 2) == 0){
          echo '<tr class="np_result_line2"><td class="np_result_line2" style="word-wrap:break-word";>';
        } else {
          echo '<tr class="np_result_line1"><td class="np_result_line1" style="word-wrap:break-word";>';
        }
        $url = $thissite."/article-flat.php?id=".$target['number']."&group="._rawurlencode($target['newsgroup'])."#".$target['number'];
        echo '<p class=np_ob_subject>';
        echo '<b><a href="'.$url.'"><span>'.mb_decode_mimeheader($target['subject']).'</span></a></b>';
        if(isset($this_overboard['threadlink'][$value])) {
          $thread = get_data_from_msgid($this_overboard['threadlink'][$value]);
          echo '<font class="np_ob_group"><a href="article-flat.php?id='.$thread[number].'&group='.rawurlencode($thread[newsgroup]).'#'.$thread[number].'"> (thread)</a></font>';
        } 
        echo '</p>';
        echo '</p><p class=np_ob_group>';
        echo '<a href="'.$groupurl.'"><span class="visited">'.$target['newsgroup'].'</span></a>';
        echo '</p>';
        echo '<p class=np_ob_posted_date>Posted: '.get_date_interval(date("D, j M Y H:i T",$target['date'])).' by: '.create_name_link($poster['name'], $poster['from']).'</p>';
	    echo htmlentities(substr($article['search_snippet'], 0, $snippetlength));
	    $results++;
    }
    echo "</table>";
    return($results);
}

function show_overboard_header($grouplist) {
  global $text_thread, $text_article, $file_index, $file_thread, $user_time;

if (isset($_GET['thisgroup'])) {
    echo '<h1 class="np_thread_headline">';
    echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
    echo '<a href="'.$file_thread.'?group='.rawurlencode($grouplist[0]).'" target='.$frame[content].'>'.htmlspecialchars(group_displaY_name($grouplist[0])).'</a> / ';
    if (isset($user_time)) {
      echo ' new messages</h1>';
    } else {
      echo ' latest</h1>';
    }
    echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// Refresh button
    echo '<td>';
    echo '<form action="overboard.php">';
    echo '<input type="hidden" name="thisgroup" value="'.$_GET['thisgroup'].'"/>';
    if (isset($user_time)) {
      echo '<button class="np_button_link" type="submit">overboard</button>';
    } else {
      echo '<button class="np_button_link" type="submit">'.$text_article["refresh"].'</button>';
    }
    
    echo '</form>';
    echo '</td>';
// Article List button
    echo '<td>';
    echo '<form action="'.$file_thread.'">';
    echo '<input type="hidden" name="group" value="'.$grouplist[0].'"/>';
    echo '<button class="np_button_link" type="submit">'.htmlspecialchars(group_display_name($grouplist[0])).'</button>';
    echo '</form>';
    echo '</td>';
// Newsgroups button (hidden)
  if(isset($frames_on) && $frames_on === true) {
    echo '<td>';
    echo '<form action="'.$file_index.'">';
    echo '<button class="np_button_hidden" type="submit">'.$text_thread["button_grouplist"].'</button>';
    echo '</form>';
    echo '</td>';
  }
    echo '<td width=100%></td></tr></table>';
} else {
    echo '<h1 class="np_thread_headline">';
    echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
    echo 'latest messages</h1>';
    echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// Refresh button
    echo '<td>';
    echo '<form action="overboard.php">';
    echo '<button class="np_button_link" type="submit">'.$text_article["refresh"].'</button>';
    echo '</form>';
    echo '</td>';
// Newsgroups button (hidden)
  if(isset($frames_on) && $frames_on === true) {
    echo '<td>';
    echo '<form action="'.$file_index.'">';
    echo '<button class="np_button_hidden" type="submit">'.$text_thread["button_grouplist"].'</button>';
    echo '</form>';
    echo '</td>';
  }
    echo '<td width=100%></td></tr></table>';
}
}

function show_overboard_footer($stats, $results, $iscached) {
    global $user_time;
    if(isset($user_time)) {
      $recent = 'new';
    } else {
      $recent = 'recent';
    }
    if($results == '1') {
      $arts = 'article';
    } else {
      $arts = 'articles';
    }
    echo '</table>';
    echo "<p class=np_ob_tail><b>".$results."</b> ".$recent." ".$arts." found.</p>\r\n";
    #echo "<center><i>Rocksolid Overboard</i> version ".$version;
    include "tail.inc";
    if($iscached) {
      echo "<p class=np_ob_tail><font size='1em'>cached copy: ".date("D M j G:i:s T Y", $stats[9])."</font></p>\r\n";
    }
}
?>
