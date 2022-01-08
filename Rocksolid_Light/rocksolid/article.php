<?php
  session_start();
  header("Expires: ".gmdate("D, d M Y H:i:s",time()+(3600*24))." GMT");

  include "config.inc.php";
  include "auth.inc";
  include "$file_newsportal";

  throttle_hits();

  // register parameters
  $id=$_REQUEST["id"];
  $group=_rawurldecode($_REQUEST["group"]);

  $thread_show["replies"]=true;
  $thread_show["lastdate"]=false;
  $thread_show["threadsize"]=false;

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

  $location = $_SERVER['REQUEST_URI'].$_SERVER['REQUEST_STRING'];
  preg_match('/id=(.*)&/', $location, $hash);
  $_SESSION['return_page'] = $location.'#'.$hash[1];

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

  // has the user read-rights on this article?
  if((function_exists("npreg_group_has_read_access") &&
      !npreg_group_has_read_access($group)) ||
     (function_exists("npreg_group_is_visible") &&
      !npreg_group_is_visible($group))) {
    die("access denied");
  }

  echo '<h1 class="np_thread_headline">';
  echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
  echo '<a href="'.$file_thread.'?group='.rawurlencode($group).'" target='.$frame["content"].'>'.htmlspecialchars(group_display_name($group)).'</a> / '.$subject.'</h1>';
  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// Article List button
    echo '<td>';
    echo '<form action="'.$file_thread.'">';
    echo '<input type="hidden" name="group" value="'.rawurlencode($group).'"/>';
    echo '<button class="np_button_link" type="submit">'.htmlspecialchars(group_display_name($group)).'</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr></table>';

  if (!$message)
    // article not found
    echo $text_error["article_not_found"];
  else {
    if($article_showthread)
      $thread=thread_cache_load($group);
    //echo "<br>";
    message_show($group,$id,0,$message);
    if($article_showthread)
      message_thread($message->header->id,$group,$thread); 
  }
  include "tail.inc";
?>
