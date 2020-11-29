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

# Maximum number of articles to show
$maxdisplay = 1000;

$thissite = '.';

$groupconfig=$config_path."/groups.txt";

if (isset($_GET['thisgroup'])) {
  $grouplist = array();
  $grouplist[0] = _rawurldecode(_rawurldecode($_GET['thisgroup']));
} else {
  $grouplist = file($groupconfig, FILE_IGNORE_NEW_LINES);
}
$title.=' - search results for: '.$_GET[terms];
include "head.inc";

ob_start();
if (isset($_GET['thisgroup'])) {
    echo '<h1 class="np_thread_headline">'.$grouplist[0].' (latest)</h1>';
    echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// Article List button
    echo '<td>';
    echo '<form action="'.$file_thread.'">';
    echo '<input type="hidden" name="group" value="'.$grouplist[0].'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_article["back_to_group"].'</button>';
    echo '</form>';
    echo '</td>';
// Newsgroups button (hidden)
    echo '<td>';
    echo '<form action="'.$file_index.'">';
    echo '<button class="np_button_hidden" type="submit">'.$text_thread["button_grouplist"].'</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr></table>';
} else { 
    echo '<h1 class="np_thread_headline">';
    echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
    echo 'search results for: '.$_GET['terms'].'</h1>';
    echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// Newsgroups button (hidden)
    echo '<td>';
    echo '<form action="'.$file_index.'">';
    echo '<button class="np_button_hidden" type="submit">'.$text_thread["button_grouplist"].'</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr></table>';
}
echo '<table cellspacing="0" width="100%" class="np_results_table">';

# Iterate through groups

$local_groupfile=$spooldir."/".$config_name."/local_groups.txt";
$results=0;
foreach($grouplist as $findgroup) {
	$groups = preg_split("/( |\t)/", $findgroup, 2); 
	$findgroup = $groups[0];

// Find starting article number (last - $maxdisplay)
  $local_grouplist = file($local_groupfile, FILE_IGNORE_NEW_LINES);
  foreach($local_grouplist as $local_findgroup) {
    $name = explode(':', $local_findgroup);
    if (strcmp($name[0], $findgroup) == 0) {
      if (is_numeric($name[1]))
        $local = $name[1];
      else {
    $thisgroup = $path."/".preg_replace('/\./', '/', $findgroup);
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

//	$overviewfp=fopen($spooldir."/".$findgroup."-overview", "r");
        $overviewfp=popen($CONFIG['tac'].' '.$spooldir.'/'.$findgroup.'-overview', 'r');
	if($overviewfp) {
	  while (($overviewline = fgets($overviewfp)) !== false) {
		$article = explode("\t", $overviewline);
		if(intval($article[0]) < ($local - $maxdisplay)) {
			continue;
		}
		if(!strcmp($_GET['searchpoint'], "Subject")) {
			$searchme = quoted_printable_decode(mb_decode_mimeheader($article[1]));
		}
		if(!strcmp($_GET['searchpoint'], "Poster")) {
                        $searchme = quoted_printable_decode(mb_decode_mimeheader($article[2]));
                }
		if(!strcmp($_GET['searchpoint'], "Message-ID")) {
                        $searchme = $article[4];
                }
		if(stripos($searchme, $_GET['terms']) === false) {
			continue;
                }
		    # Generate link
		    $url = $thissite."/article-flat.php?id=".$article[0]."&group="._rawurlencode($findgroup)."#".$article[0];
		    $groupurl = $thissite."/thread.php?group="._rawurlencode($findgroup);
		    $fromoutput = explode("<", html_entity_decode($article[2]));

		// Just an email address?
		    if(strlen($fromoutput[0]) < 2) {
			 preg_match("/\<([^\)]*)\@/", html_entity_decode($article[2]), $fromaddress);
		   	 $fromoutput[0] = $fromaddress[1];
	            }
		    if(strpos($fromoutput[0], "(")) {
			    preg_match("/\(([^\)]*)\)/", html_entity_decode($article[2]), $fromaddress);
			    $fromoutput[0] = $fromaddress[1];
		    }
		    if(($results % 2) != 0){
			    echo '<tr class="np_result_line1"><td style="word-wrap:break-word";>';
		    } else {
			    echo '<tr class="np_result_line2"><td style="word-wrap:break-word";>';
		    }
		    echo '<p class=np_ob_subject>';
		    echo '<b><a href="'.$url.'">'.mb_decode_mimeheader($article[1])."</a></b>\r\n";
		    echo '</p><p class=np_ob_group>';
		    echo '<a href="'.$groupurl.'">'.$findgroup.'</a>';
		    echo '</p>';
		    echo '<p class=np_ob_posted_date>Posted: '.$article[3].' by: '.mb_decode_mimeheader($fromoutput[0]).'</p>';
		    echo '</td></tr>';
		    if($results++ > ($maxdisplay - 2))
                        break;
	  }
	  fclose($overviewfp);
	}
}

echo '</table>';
echo "<p class=np_ob_tail><b>".$results."</b> matching articles found.</p>\r\n";
#echo "<center><i>Rocksolid Overboard</i> version ".$version;
include "tail.inc";

$thispage = ob_get_contents();

ob_end_clean();

echo $thispage;

function highlightStr($haystack, $needle) {
    preg_match_all("/$needle+/i", $haystack, $matches);
    if (is_array($matches[0]) && count($matches[0]) >= 1) {
	foreach ($matches[0] as $match) {
	    $haystack = str_replace($match, '<b>'.$match.'</b>', $haystack);
	}
    }
    return $haystack;
}

function _rawurlencode($string) {
    $string = rawurlencode(str_replace('+','%2B',$string));
    return $string;
}

function _rawurldecode($string) {
    $string = rawurldecode(str_replace('%2B','+',$string));
    return $string;
}

?>
</body>
</html>
