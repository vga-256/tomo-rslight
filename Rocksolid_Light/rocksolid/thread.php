<?php 
//header("Expires: ".gmdate("D, d M Y H:i:s",time()+7200)." GMT");
session_start();

$_SESSION['group'] = $_SERVER['REQUEST_URI'];
$_SESSION['rsactive'] = true;

include "config.inc.php";
include("$file_newsportal");
include "auth.inc";

$logfile=$logdir.'/newsportal.log';
throttle_hits();

// register parameters
$group=_rawurldecode($_REQUEST["group"]);
if(isset($_REQUEST["first"]))
  $first=intval($_REQUEST["first"]);
if(isset($_REQUEST["last"]))
  $last=intval($_REQUEST["last"]);

  $findsection = get_section_by_group($group);
  if(trim($findsection) !== $config_name) {
    $newurl = preg_replace("|/$config_name/|", "/$findsection/", $_SERVER['REQUEST_URI']);
    header("Location: $newurl");
    die();
  }

  if(isset($_COOKIE['mail_name'])) {
    if($userdata = get_user_mail_auth_data($_COOKIE['mail_name'])) {
      $userfile=$spooldir.'/'.strtolower($_COOKIE['mail_name']).'-articleviews.dat';
    }
  }
  
$thread_show["latest"]=true;
$title.= ' - '.$group;
include "head.inc";

$CONFIG = include($config_file);

if((!function_exists("npreg_group_has_read_access") ||
    npreg_group_has_read_access($group)) &&
   (!function_exists("npreg_group_is_visible") ||
    npreg_group_is_visible($group))) {

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
  if($userdata) {
    $userdata[$group] = time();
    file_put_contents($userfile, serialize($userdata));
  }
  
  $_SESSION['return_page'] = $_SERVER['REQUEST_URI'].$_SERVER['REQUEST_STRING'];

  echo '<a name="top"></a>';
  echo '<h1 class="np_thread_headline">';

  echo '<a href="'.$file_index.'" target='.$frame['menu'].'>'.basename(getcwd()).'</a> / ';
  echo htmlspecialchars(group_display_name($group)).'</h1>';

  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>';
// View Latest button
  if (isset($overboard) && ($overboard == true)) {
    echo '<td>';
    echo '<form action="overboard.php">';
    echo '<input type="hidden" name="thisgroup" value="'._rawurlencode($group).'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_thread["button_latest"].'</button>';
    echo '</form>';
    echo '</td>';
  }
 if (!$CONFIG['readonly'] &&
      (!function_exists("npreg_group_has_write_access") ||
       npreg_group_has_write_access($group)))
 {
// New Thread button
    echo '<td>';
    echo '<form action="'.$file_post.'">';
    echo '<input type="hidden" name="group" value="'.urlencode($group).'"/>';
    echo '<button class="np_button_link" type="submit">'.$text_thread["button_write"].'</button>';
    echo '</form>';
    echo '</td>';
 }
// Search button
  echo '<td>';
  echo '<form target="'.$frame['content'].'" action="search.php">';
  echo '<button class="np_button_link" type="submit">'.$text_thread["button_search"].'</button>';
  echo '<input type="hidden" name="group" value="'.urlencode($group).'"/>';
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
// $ns=nntp_open($server,$port);
  flush();
  $headers = thread_load($group);
  $article_count=count($headers);
  if ($articles_per_page != 0) { 
    if ((!isset($first)) || (!isset($last))) {
      if ($startpage=="first") {
        $first=1;
        $last=$articles_per_page;
      } else {
        $first=$article_count - (($article_count -1) % $articles_per_page);
        $last=$article_count;
      }
    }
    echo '<td class="np_pages" width="100%" align="right">';
    // Show the replies to an article in the thread view?
    if($thread_show["replies"]) {
      // yes, so the counting of the shown articles is very easy
      $pagecount=count($headers);
    } else {
      // oh no, the replies will not be shown, this makes life hard...
      $pagecount=0;
      if(count($headers) > 0 && is_array($headers)) {
        foreach($headers as $h) {
          if($h->isAnswer==false)
            $pagecount++;
        }
      }
    }
  
    thread_pageselect($group,$pagecount,$first);
    echo '</td>';
  } else {
    $first=0;
    $last=$article_count;
  }
  echo '</tr></table>';
  thread_show($headers,$group,$first,$last);
  echo '<table cellpadding="0" cellspacing="0" width="100%" class="np_buttonbar"><tr>'; 
  echo '<td class="np_pages" width="100%" align="right">';
  thread_pageselect($group,$pagecount,$first);
  echo '</td></tr></table>';
} else {
  echo $text_register["no_access_group"];
}
$sessions_data = file_get_contents($spooldir.'/sessions.dat');
echo '<h1 class="np_thread_headline">'.$sessions_data.'</h1>';
include "tail.inc"; 
?>
