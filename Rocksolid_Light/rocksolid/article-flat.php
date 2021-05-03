<?php
  session_start();

  header("Expires: ".gmdate("D, d M Y H:i:s",time()+(3600*24))." GMT");
  header("Cache-Control: max-age=100");
  header("Pragma: cache");

  include "config.inc.php";
  include "auth.inc";
  include "$file_newsportal";

  // register parameters
  $id=$_REQUEST["id"];
  $group=_rawurldecode($_REQUEST["group"]);

  $findsection = get_section_by_group($group);
  if(trim($findsection) !== $config_name) {
    $newurl = preg_replace("|/$config_name/|", "/$findsection/", $_SERVER['REQUEST_URI']);
    header("Location: $newurl");
    die();
  }

  if(isset($_REQUEST["first"]))
    $first=$_REQUEST["first"];

  $logfile=$logdir.'/newsportal.log';
  if(!isset($_SESSION['starttime'])) {
    $_SESSION['starttime'] = time();
    $_SESSION['views'] = 0;
  }
  $_SESSION['views']++;

// $loadrate = allowed article request per second
  $loadrate = 1.5;
  $rate = ($_SESSION['views'] / (time() - $_SESSION['starttime']));
  if (($rate > $loadrate) && ($_SESSION['views'] > 5)) {
    header("HTTP/1.0 429 Too Many Requests");
    if(!isset($_SESSION['throttled'])) {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Too many requests from ".$_SERVER['REMOTE_ADDR']." throttling", FILE_APPEND);
	  $_SESSION['throttled'] = true;
    }
    exit(0);
  }

  $_SESSION['rsactive'] = true;

  $location = $_SERVER['REQUEST_URI'].$_SERVER['REQUEST_STRING'];
  preg_match('/id=(.*)&/', $location, $hash);
  $_SESSION['return_page'] = $location.'#'.$hash[1];

 
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

  $message=message_read($id,0,$group);

  if (!$message) {
    header ("HTTP/1.0 404 Not Found");
    $subject=$title;
    $title.=' - Article not found';
    if($ns!=false)
    nntp_close($ns);
  } else {
    $subject=htmlspecialchars($message->header->subject);
	header("Last-Modified: ".date("r", $message->header->date));
    $title.= ' - '.$group.' - '.$subject;
  }
  include "head.inc";
  echo '<h1 class="np_thread_headline">';
  echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
  echo '<a href="'.$file_thread.'?group='.rawurlencode($group).'" target='.$frame["content"].'>'.htmlspecialchars(group_display_name($group)).'</a> / '.$subject.'</h1>';

if($message) {
  // load thread-data and get IDs of the actual subthread
  $thread=thread_load($group);
  $subthread=thread_getsubthreadids($message->header->id,$thread);
  if($thread_articles == false) {
    sort($subthread);
  }
  // If no page is set, lets look, if we can calculate the page by
  // the message-number
  if(!isset($first)) {
    $first=intval(array_search($id,$subthread)/$articleflat_articles_per_page)*
           $articleflat_articles_per_page+1;
  }

  // which articles are exactly on this page?
  $pageids=array();
  for($i=$first-1; (($i<count($subthread)) && 
                  ($i<$first+$articleflat_articles_per_page-1)); $i++) {  
    $pageids[]=$subthread[$i];
  }

  // display the thread on top
  // change some of the default threadstyle-values
  $thread_show["replies"]=true;
  $thread_show["threadsize"]=false;
  $thread_show["lastdate"]=false;
  $thread_show["latest"]=false;
  $thread_show["author"]=true;
  //message_thread($message->header->id,$group,$thread,$pageids);
    message_thread($message->header->id,$group,$thread,false);
  echo '<br>';
  echo '<a name="start"></a>';
  // navigation line
  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// Article List button
    echo '<td>';
    echo '<form action="'.$file_thread.'">';
    echo '<input type="hidden" name="group" value="'.rawurlencode($group).'"/>';
    echo '<button class="np_button_link" type="submit">'.htmlspecialchars(group_display_name($group)).'</button>';
    echo '</form>';
    echo '</td>';
// Pages
    echo '<td class="np_pages" width="100%" align="right">';
    echo articleflat_pageselect($group,$id,count($subthread),$first);
    echo '</td></tr></table>';
  foreach($pageids as $subid) {
    flush();
    $message=message_read($subid,0,$group);
    echo '<a name="'.$subid.'"> </a>';
    message_show($group,$subid,0,$message,$articleflat_chars_per_articles);
    if ((!$CONFIG['readonly']) && ($message)) {
      echo '<form action="'.$file_post.'">'.
           '<input type="hidden" name="id" value="'.urlencode($subid).'">'.
           '<input type="hidden" name="type" value="reply">'.
           '<input type="hidden" name="group" value="'.urlencode($group).'">'.           
           '<input type="submit" value="'.$text_article["button_answer"].
           '">'.
           '</form>';
    }
  }
  // navigation line
  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// Article List button
    echo '<td>';
    echo '<form action="'.$file_thread.'">';
    echo '<input type="hidden" name="group" value="'.rawurlencode($group).'"/>';
    echo '<button class="np_button_link" type="submit">'.htmlspecialchars(group_display_name($group)).'</button>';
    echo '</form>';
    echo '</td>';
// Pages
    echo '<td class="np_pages" width="100%" align="right">';
    echo articleflat_pageselect($group,$id,count($subthread),$first);
    echo '</td></tr></table>';
}
include "tail.inc";
?>
