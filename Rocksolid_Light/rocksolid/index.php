<?php header("Expires: ".gmdate("D, d M Y H:i:s",time()+7200)." GMT");
session_start();
$_SESSION['isframed'] = 1;

   include "config.inc.php";
   include "auth.inc";
if (isset($frames_on) && $frames_on === true) {
?>
<script>
    var contentURL=window.location.pathname+window.location.search+window.location.hash;
    if ( window.self !== window.top ) {
        /* Great! now we move along */
    } else {
        window.location.href = '../index.php?menu='+encodeURIComponent(contentURL);
    }
    top.history.replaceState({}, 'Title', 'index.php?content='+encodeURIComponent(contentURL));
</script>
<?php
}
$title.=' - '.basename(getcwd());
include "head.inc";
echo '<h1 class="np_thread_headline">'.basename(getcwd()).'</h1>';
echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// View Latest button
  if (isset($overboard) && ($overboard == true)) {
    echo '<td>';
    echo '<form target="'.$frame['content'].'" action="overboard.php">';
    echo '<button class="np_button_link" type="submit">'.$text_thread["button_overboard"].'</button>';
    echo '</form>';
    echo '</td>';
  } else { 
//    echo htmlspecialchars($CONFIG['title_full']);
  } 
// Search button
  echo '<td>';
  echo '<form target="'.$frame['content'].'" action="search.php">';
  echo '<button class="np_button_link" type="submit">'.$text_thread["button_search"].'</button>';
  echo '</form>';
  echo '</td>';
  echo '<td width=100%></td></tr></table>';

include("$file_newsportal");
flush();
$newsgroups=groups_read($server,$port);
echo '<div class="np_index_groups">';
if(isset($frames_on) && $frames_on === true) {
  groups_show_frames($newsgroups);
} else {
  groups_show($newsgroups);
}
echo '</div>';
$sessions_data = file_get_contents($spooldir.'/sessions.dat');
echo '<h1 class="np_thread_headline">'.$sessions_data.'</h1>';
include "tail.inc";
?>

