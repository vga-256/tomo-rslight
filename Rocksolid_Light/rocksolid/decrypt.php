<?php
  header("Expires: ".gmdate("D, d M Y H:i:s",time()+(3600*24))." GMT");


  // register parameters
  $id=$_REQUEST["id"];
  $group=$_REQUEST["group"];

  include "config.inc.php";

  $thread_show["replies"]=true;
  $thread_show["lastdate"]=false;
  $thread_show["threadsize"]=false;

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
    $title.= ' - '.$subject;
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
  $ok = check_bbs_auth($_POST['decryptuser'], $_POST['decryptpass']);
  if ($ok === TRUE) {
	$key = get_user_config($_POST['decryptuser'],'encryptionkey');
  	message_decrypt($key,$group,$id,0,$message);
  } else {
        echo "Failed to authenticate";
  }
//    if($article_showthread)
//      message_thread($message->header->id,$group,$thread); 

  }
  include "tail.inc";
?>
