<?php

include "../common/config.inc.php";

ini_set('memory_limit','1536M');

/* Config file name should be the basename
 * of your path where you installed rslight
 * plus .inc.php.
 * So if installed in /var/www/html/rocksolid
 * it's rocksolid.inc.php in $config_dir
 */
$config_name = basename(getcwd());
if(file_exists($config_dir.$config_name.'.inc.php')) {
  $config_file = $config_dir.$config_name.'.inc.php';
} else {
  $config_file = $config_dir.'rslight.inc.php';
}

// install path looks like this: /home/user/tomobbs/www/tomonet
$installed_path = getcwd();

/* $config_path is a directory off the $config_dir
 * where specific files such as groups.txt
 * are located
 */
$config_path = $config_dir.$config_name."/";
$script_path = $bbsroot_dir."/admintools/";
$CONFIG = include($config_file);

$logdir=$spooldir.'/log';
$lockdir=$spooldir.'/lock';
$ssl_dir = $spooldir."/ssl";
	
/* Permanent configuration changes */
@mkdir($logdir,0755,'recursive');
@mkdir($spooldir.'/upload',0755,'recursive');
chown($logdir,$CONFIG['webserver_user']);
chown($spooldir.'/upload',$CONFIG['webserver_user']);

date_default_timezone_set('UTC');
$overboard=true;
$spoolnews=true;
if(isset($CONFIG['enable_nntp']) && $CONFIG['enable_nntp'] == true) {
  $server=$CONFIG['local_server'];
  $port=$CONFIG['local_port'];
} else {
  $server=$CONFIG['remote_server'];
  $port=$CONFIG['remote_port'];
  $CONFIG['server_auth_user']=$CONFIG['remote_auth_user'];
  $CONFIG['server_auth_pass']=$CONFIG['remote_auth_pass'];
}

/*
 * Frames (frames is not up to date and probably not so great)
 */

// Set to true to use framed version of rslight
$frames_on=false;

// The default content for the left side 'menu' frame
$default_menu="/tomonet/index.php";

if (isset($frames_on) && $frames_on === true) {
  $style_css="style-frames.css";
  $frame['content']="content";
  $frame['menu']="menu";
  $frame['header']="header";
} else {
  $style_css="style.css";
  $frame['content']="_self";
  $frame['menu']="_self";
  $frame['header']="_self";
}
$frame_externallink="_blank";

/*
 * directories and files
 */
$imgdir="img";

$file_newsportal="newsportal.php";
$file_index="index.php";
$file_thread="thread.php";
$file_article="article-flat.php";
$file_article_full="article.php";
$file_attachment="attachment.php";
$file_post="post.php";
$file_cancel="cancel.php";
$file_language="lang/english.lang";
$file_footer="footer.inc";
$file_groups=$config_path."groups.txt";

$title = $CONFIG['title_full'];

/*
 * Grouplist Layout
 */
$gl_age=true;

/*
 * Thread layout
 */
# When viewing a thread should the articles be sorted by subthreads, or
# simply by date, oldest to newest?
# Set to false to sort by date, true to sort into subthreads.
# Generally, false makes it easier to find the latest posts at the bottom.
$thread_articles=false;

$thread_treestyle=7;
$thread_show["date"]=false;
$thread_show["subject"]=true;
$thread_show["author"]=true;
$thread_show["authorlink"]=false;
$thread_show["replies"]=false;
$thread_show["lastdate"]=true; // makes only sense with $thread_show["replies"]=false
$thread_show["threadsize"]=true;
$thread_show["latest"]=true;
$thread_maxSubject=70;
$maxfetch=1000;
$maxarticles=0;
$maxarticles_extra=0;
$age_count=3;
$age_time[1]=86400; //24 hours
$age_color[1]="red";
$age_time[2]=259200; //3 days
$age_color[2]="darkgoldenrod";
$age_time[3]=604800; //7 days
$age_color[3]="darkgreen";
$thread_sort_order=-1;
$thread_sort_type="thread";
$articles_per_page=200;
$startpage="first";

/* 
 * article layout 
 */
$article_show["Subject"]=true;
$article_show["From"]=true;
$article_show["Newsgroups"]=true;
$article_show["Followup"]=true;
$article_show["Organization"]=true;
$article_show["Date"]=true;
$article_show["Message-ID"]=false;
$article_show["User-Agent"]=false;
$article_show["References"]=true;
$article_show["From_link"]=false;
$article_show["trigger_headers"]=true;
//$article_show["From_rewrite"]=array('@',' (at) ');
$article_showthread=true;
$article_graphicquotes=true;

/*
 * settings for the article flat view, if used
 */
$articleflat_articles_per_page=25;
$articleflat_chars_per_articles=10000;

/*
 * Message posting
 */
$send_poster_host=false;
$testgroup=true; // don't disable unless you really know what you are doing!
$validate_email=1;
$setcookies=true;
$anonym_address="AnonUser@retrobbs.rocksolidbbs.com";
$msgid_generate="md5";
$msgid_fqdn=$_SERVER["HTTP_HOST"];
$post_autoquote=false;
$post_captcha=false;

/* 
 * Attachments
 */
$attachment_show=true;
$attachment_delete_alternative=true; // delete non-text mutipart/alternative
$attachment_uudecode=true;  // experimental!

/*
 * Security settings
 */
$block_xnoarchive=false;

/*
 * User registration and database
 */
// $npreg_lib="lib/npreg.inc.php";

/*
 * Cache
 */
$cache_articles=false;  // article cache, experimental!
$cache_index=600; // cache the group index for ten minutes before reloading
$cache_thread=60; // cache the thread for one minute reloading

/*
 * Misc 
 */
$cutsignature=true;
$compress_spoolfiles=false;

if(isset($spoolnews) && ($spoolnews === true)) {
    $spoolpath = $spooldir."/articles/";
    $localeol=PHP_EOL.PHP_EOL;
} else {
    $spoolpath = "/var/spool/news/articles/";
    $localeol="\r\n\r\n";
}

// website charset, "koi8-r" for example
//$www_charset = "iso-8859-15";
$www_charset = "utf-8";
// Use the iconv extension for improved charset conversions
$iconv_enable=true;
/*
 * Group specific config 
 */
//$group_config=array(
//  '^de\.alt\.fan\.aldi$' => "aldi.inc",
//  '^de\.' => "german.inc"
//);

/*
 * Do not edit anything below this line
 */
// Load group specifig config files
if((isset($group)) && (isset($group_config))) {
  foreach ($group_config as $key => $value) {
    if (ereg($key,$group)) {
      include $value;
      break;
    }
  }
}

// load the english language definitions first because some of the other
// definitions are incomplete
include("lang/english.lang"); 
include($file_language);
?>
