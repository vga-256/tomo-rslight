<?php
session_cache_limiter('public');
session_start();

include "config.inc.php";
include "newsportal.php";
include $config_dir.'/admin.inc.php';

if(!isset($_POST['key']) || $_POST['key'] !== hash('md5', $admin['key'])) {
include "head.inc"; 
?>

<body>
<table width=100% border="0" align="center" cellpadding="0" cellspacing="1">
<tr>
<form name="form1" method="post" action="search.php">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr>
<td colspan="3"><strong>Search recent messages</strong><br />(searches last <?php echo $maxarticles; ?> articles per group)</td>
</tr>
<tr></tr>
<tr>
<td width="78"><strong>Search Terms</strong></td>
<td width="6">:</td>
<?php
echo '<td width="294"><input name="terms" type="text" id="terms" value="'.$_GET['terms'].'"></td>';
echo '</tr><tr></tr><tr>';
if ($_GET['searchpoint'] == 'Poster') {
  echo '<td><input type="radio" name="searchpoint" value="subject"/>Subject</td>';
  echo '<td><input type="radio" name="searchpoint" value="name" checked="checked"/>Poster</td>';
} else {
  echo '<td><input type="radio" name="searchpoint" value="subject" checked="checked"/>Subject</td>'; 
  echo '<td><input type="radio" name="searchpoint" value="name"/>Poster</td>';
}
?>
<td><input type="radio" name="searchpoint" value="msgid"/>Message-ID</td>
</tr>
<tr>
<td><input name="command" type="hidden" id="command" value="Search" readonly="readonly"></td>
<?php echo '<input type="hidden" name="key" value="'.hash('md5', $admin['key']).'">';?>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td><input type="submit" name="Submit" value="Search"></td>
</tr>
<tr><td>
<td></td><td></td>
</td></tr>
</table>
</td>
</form>
</tr>
</table>
</body>
</html>

<?php exit(0); } 

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

$title.=' - search results for: '.$_POST[terms];
include "head.inc";

ob_start();
if (isset($_POST['thisgroup'])) {
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
    echo 'search results for: '.$_POST['terms'].'</h1>';
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

$results=0;
  if(isset($_COOKIE['tzo'])) {
    $offset=$_COOKIE['tzo'];
  } else {
    $offset=$CONFIG['timezone'];
  }
	$searchterms = "%".$_POST['terms']."%";
	# Prepare search database
  	$database = $spooldir.'/articles-overview.db3';
  	$table = 'overview';
  	$dbh = rslight_db_open($database, $table);
	$overview = array();
	if($dbh) {
	  if(is_multibyte($_POST['terms'])) {
	    $stmt = $dbh->query("SELECT * FROM $table");
	    while($row = $stmt->fetch()) {
	      if(stripos(quoted_printable_decode(mb_decode_mimeheader($row[$_POST['searchpoint']])), $_POST['terms']) !== false) {
		$overview[] = $row;
	      }
	    } 
	  } else {
	    $stmt = $dbh->prepare("SELECT * FROM $table WHERE ".$_POST['searchpoint']." like :terms ORDER BY date DESC");
	    $stmt->bindParam(':terms', $searchterms);
	    $stmt->execute();
	    while($found = $stmt->fetch()) {
	      $overview[] = $found;
	    }
          }
          $dbh = null;
	  foreach($overview as $overviewline) {
/* Find section for links */
    $menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($menulist as $menu) {
      if($menu[0] == '#') {
        continue;
      }
      $menuitem=explode(':', $menu);
      $glfp=fopen($config_dir.$menuitem[0]."/groups.txt", 'r');
      $section="";
      while($gl=fgets($glfp)) {
        $group_name = preg_split("/( |\t)/", $gl, 2);
        if(stripos(trim($overviewline['newsgroup']), trim($group_name[0])) !== false) {
          $section=$menuitem[0];
          break 2;
        }
      }
    }
		    # Generate link
		    $url = "../".$section."/article-flat.php?id=".$overviewline['number']."&group="._rawurlencode($overviewline['newsgroup'])."#".$overviewline['number'];
		    $groupurl = "../".$section."/thread.php?group="._rawurlencode($overviewline['newsgroup']);
		    $fromoutput = explode("<", html_entity_decode($overviewline['name']));

		// Use local timezone if possible
		$ts = new DateTime(date($text_header["date_format"], $overviewline['date']), new DateTimeZone('UTC'));
      		$ts->add(DateInterval::createFromDateString($offset.' minutes'));
      		if($offset != 0) {
        		$newdate = $ts->format('D, j M Y H:i');
      		} else {
        		$newdate = $ts->format($text_header["date_format"]);
      		}
      		unset($ts);
		    
		$fromline=address_decode(headerDecode($overviewline['name']),"nirgendwo");
      		if (!isset($fromline[0]["personal"])) {
        		$lastname=$fromline[0]["mailbox"];;
      		} else {
        		$lastname=$fromline[0]["personal"];
      		}
		if(($results % 2) != 0){
			    echo '<tr class="np_result_line1"><td style="word-wrap:break-word";>';
		    } else {
			    echo '<tr class="np_result_line2"><td style="word-wrap:break-word";>';
		    }
		    echo '<p class=np_ob_subject>';
		    echo '<b><a href="'.$url.'">'.mb_decode_mimeheader($overviewline['subject'])."</a></b>\r\n";
		    echo '</p><p class=np_ob_group>';
		    echo '<a href="'.$groupurl.'">'.$overviewline['newsgroup'].'</a>';
		    echo '</p>';
  
	   	    echo '<p class=np_ob_posted_date>Posted: '.$newdate.' by: '.mb_decode_mimeheader($overviewline['name']).'</p>';
		    echo '</td></tr>';
		    if($results++ > ($maxdisplay - 2))
                        break;
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
?>
</body>
</html>
