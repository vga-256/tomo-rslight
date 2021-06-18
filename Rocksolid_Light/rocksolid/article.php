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


?>



<h1 class="np_article_headline"><?php echo htmlspecialchars(group_display_name($group)." / ".$subject) ?></h1>

<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>
<?php 
  if(!$frames_on) {
    echo '<td class="np_button"><a class="np_button" href="'.
         $file_index.'">'.$text_thread["button_grouplist"].'</a></td>';
  }
  echo '<td class="np_button"><a class="np_button" href="'.
       $file_thread.'?group='.urlencode($group).'">'.$text_article["back_to_group"].'</a></td>';
  if ((!$CONFIG['readonly']) && ($message) &&
      (!function_exists("npreg_group_has_write_access") ||
             npreg_group_has_write_access($group)))
    echo '<td class="np_button"> <a class="np_button" href="'.
         $file_post.'?type=reply&id='.urlencode($id).
         '&group='.urlencode($group).'">'.$text_article["button_answer"].
         '</a></td>';

   if(function_exists(npreg_user_is_moderator) && npreg_user_is_moderator($group)) {
     echo '<td class="np_button"><a class="np_button" href="'.$file_cancel.'?type=reply&id='.urlencode($id).
          '&group='.urlencode($group).'">'.$text_article["button_cancel"].'</a></td>';
    }

?>
<td width="100%">&nbsp;</td></tr></table>

<?php 
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
