<?php
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

# How many days old should articles be displayed?
if (isset($_GET['thisgroup'])) {
  $article_age = 30;
} else {
  $article_age = 7;
}

# Maximum number of articles to show
$maxdisplay = 1000;

# How many characters of the body to display per article
$snippetlength = 240;

$spoolpath_regexp = '/'.preg_replace('/\//', '\\/', $spoolpath).'/';
$thissite = '.';

$groupconfig=$file_groups;
$cachefile=$spooldir."/".$config_name."-overboard.cache";

$oldest = (time() - (86400 * $article_age));

if (isset($_GET['thisgroup'])) {
  $grouplist = array();
  $grouplist[0] = _rawurldecode(_rawurldecode($_GET['thisgroup']));
  $cachefile=$cachefile.'.'.$grouplist[0];
} else {
  $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
}

/* If cache is less than ? seconds old, use it */
if(is_file($cachefile)) {
  $stats = stat($cachefile);
  if($stats[9] > (time() - 60)) {
    echo file_get_contents($cachefile);
    exit(0);
  }
}
ob_start();
# Iterate through groups

$database = $spooldir.'/articles-overview.db3';
$table = 'overview';
$dbh = rslight_db_open($database, $table);
$query = $dbh->prepare('SELECT * FROM '.$table.' WHERE newsgroup=:findgroup AND date >= '.$oldest.' ORDER BY date DESC LIMIT '.$maxdisplay);
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
	    while (($overviewline = $query->fetch()) !== false) {
	      $articles[] = $spoolpath.$thisgroup.'/'.$overviewline['number'];
	      $db_articles[] = $findgroup.':'.$overviewline['number'].':'.$overviewline['date'].':'.$overviewline['name'];
	    }
	  }	  
}
$dbh = null;

if (isset($_GET['thisgroup'])) {
    echo '<h1 class="np_thread_headline">';
    echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
    echo '<a href="'.$file_thread.'?group='.rawurlencode($grouplist[0]).'" target='.$frame[content].'>'.htmlspecialchars(group_display_name($grouplist[0])).'</a> / ';
    echo ' latest</h1>';
    echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// Refresh button
    echo '<td>';
    echo '<form action="overboard.php">';
    echo '<input type="hidden" name="thisgroup" value="'.$_GET['thisgroup'].'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_article["refresh"].'</button>';
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

$results=0;
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
echo '<table cellspacing="0" width="100%" class="np_results_table">';
//date_default_timezone_set(timezone_name_from_abbr("", $CONFIG['timezone'] * 3600, 0));
foreach($files as $article) {
    if($CONFIG['article_database'] == '1') {
      $data = explode(':', $article);
      $articledata = np_get_db_article($data[1], $data[0], 0);
    } else {
      $articledata = file_get_contents($article);
    }
    $bodystart = strpos($articledata, $localeol);

    $header = substr($articledata, 0, $bodystart);
    $body = substr($articledata, $bodystart+1);
    $body = substr($body, strpos($body, PHP_EOL));

	if(($multi = strpos($body, 'Content-Type: text/plain')) != false) {
		$bodystart = strpos($body, $localeol);
		$body = substr($body, $bodystart+1);
	    $body = substr($body, strpos($body, PHP_EOL));
	}

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
    # Generate link
    $url = $thissite."/article-flat.php?id=".$articlenumber."&group="._rawurlencode($groupname)."#".$articlenumber;
    $groupurl = $thissite."/thread.php?group="._rawurlencode($groupname);
    preg_match('/Subject:.*/', $header, $subject);
    $output = explode("Subject: ",$subject[0], 2);

    preg_match('/Date:.*/', $header, $articledate);
    $dateoutput = explode("Date: ",$articledate[0]);

    preg_match('/Content-Transfer-Encoding:.*/', $header, $te);
    $content_transfer_encoding = explode("Content-Transfer-Encoding: ", $te[0]); 

    preg_match('/.*charset=.*/', $header, $te);
    $content_type = explode("Content-Type: text/plain; charset=", $te[0]);

    $date_interval = get_date_interval($dateoutput[1]);

    preg_match('/Content-Transfer-Encoding:.*/', $header, $encoding);
    $this_encoding = explode("Content-Transfer-Encoding: ", $encoding[0]);
    if(trim($this_encoding[1]) == "base64") {
      $body=base64_decode($body);
    }
    if($CONFIG['article_database'] == '1') {
      $articlefrom[0] = $data[3];
    } else { 
      preg_match('/From:.*/', htmlspecialchars($header), $articlefrom);
      $isfrom = explode("From: ", $articlefrom[0]);    
      $articlefrom[0] = $isfrom[1];
    }
    $fromoutput = explode("<", html_entity_decode($articlefrom[0]));

// Just an email address?
    if(strlen($fromoutput[0]) < 2) {
	preg_match("/\<([^\)]*)\@/", html_entity_decode($articlefrom[0]), $fromaddress);

	$fromoutput[0] = $fromaddress[1];
    }
    if(strpos($fromoutput[0], "(")) {
	preg_match("/\(([^\)]*)\)/", html_entity_decode($articlefrom[0]), $fromaddress);
	$fromoutput[0] = $fromaddress[1];
    }

    if(($results % 2) != 0){
	echo '<tr class="np_result_line1"><td class="np_result_line1" style="word-wrap:break-word";>';
    } else {
	echo '<tr class="np_result_line2"><td class="np_result_line2" style="word-wrap:break-word";>';
    }
    echo '<p class=np_ob_subject>';
    echo '<b><a href="'.$url.'">'.mb_decode_mimeheader($output[1])."</a></b>\r\n";
    echo '</p><p class=np_ob_group>';
    echo '<a href="'.$groupurl.'"><span class="visited">'.$groupname.'</span></a>';
    echo '</p>';

    if((isset($CONFIG['hide_email']) && $CONFIG['hide_email'] == true) && (strpos($fromoutput[0], '@') !== false)) {
      $poster_name = truncate_email($fromoutput[0]);
    } else {
      $poster_name = $fromoutput[0]; 
    }

    echo '<p class=np_ob_posted_date>Posted: '.$date_interval.' by: '.create_name_link(mb_decode_mimeheader($poster_name)).'</p>';
//    echo '<p class=np_ob_posted_date>Posted: '.$date_interval.' by: '.mb_decode_mimeheader($fromoutput[0]).'</p>';
    # Try to display useful snippet
	if($stop=strpos($body, "begin 644 "))
		$body=substr($body, 0, $stop);
    $body = quoted_printable_decode($body);
    $mysnippet = recode_charset($body, $content_type[1], "utf8");
    if($bodyend=strrpos($mysnippet, "\n---\n")) {
	$mysnippet = substr($mysnippet, 0, $bodyend);
    } else {
	    if($bodyend=strrpos($mysnippet, "\n-- ")) {
		$mysnippet = substr($mysnippet, 0, $bodyend);
		} else {
			if($bodyend=strrpos($mysnippet, "\n.")) {
				$mysnippet = substr($mysnippet, 0, $bodyend);
			} 
		}
	}
	$mysnippet = preg_replace('/\n.{0,5}>(.*)/', '', $mysnippet);

	$snipstart = strpos($mysnippet, ":\n");
	if(substr_count(trim(substr($mysnippet, 0, $snipstart)), "\n") < 2) {
		$mysnippet = substr($mysnippet, $snipstart + 1, $snippetlength);
	} else {
		$mysnippet = substr($mysnippet, 0, $snippetlength);
	}
    echo "<p class=np_ob_body>".$mysnippet."</p>\r\n";
    echo '</td></tr>';
    if($results++ > ($maxdisplay - 2))
	break;
}
echo '</table>';
echo "<p class=np_ob_tail><b>".$results."</b> recent articles found.</p>\r\n";
#echo "<center><i>Rocksolid Overboard</i> version ".$version;
$iscached = "<p class=np_ob_tail><font size='1em'>cached copy: ".date("D M j G:i:s T Y", time())."</font></p>\r\n";
include "tail.inc";

$thispage = ob_get_contents();

ob_end_clean();

echo $thispage;

if(count($articles) > 0) {
  $cacheFileHandle = fopen($cachefile, "w+");
  fwrite($cacheFileHandle, $thispage);
  fwrite($cacheFileHandle, $iscached);
  fclose($cacheFileHandle);
}
?>
</body>
</html>
