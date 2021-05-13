<?php

include "config.inc.php";
include "newsportal.php";
include $config_dir.'/admin.inc.php';

  if(isset($_COOKIE['tzo'])) {
    $offset=$_COOKIE['tzo'];
  } else {
    $offset=$CONFIG['timezone'];
  }
  if($_REQUEST['command'] == 'Show' && $_REQUEST['key'] == hash('md5', $admin['key'])) {
    $getfilename = $spooldir.'/upload/'.$_REQUEST['showfile'];
    $getfh = fopen($getfilename, "rb");
    $getfile = fread($getfh, filesize($getfilename));
    fclose($getfh);
    header('Content-type: '.$_REQUEST[contenttype]);
    header('Content-disposition: filename="'.$_REQUEST[showfilename].'"');
    echo $getfile;
    exit(0);
  }

include "head.inc";
  $directory = $spooldir.'/upload/';
  $users = array();
  if(is_dir($directory)) {
    if($users_list = opendir($directory)) {
      while(($user_dir = readdir($users_list)) !== false) {
	if($user_dir == '.' || $user_dir == '..') {
	  continue;
        }
	$users[] = $user_dir;
      }
      closedir($user_dir);
    }
  }
  sort($users);
  $found = 0;
  echo '<strong><small><a href="upload.php">Click here to upload to your directory</a>, or<br />';
  if(count($users) > 0) {
    echo "Select a user directory to browse</small></strong>";
    echo '<form name="browse" method="post" action="files.php" enctype="multipart/form-data">';
    echo '<input name="command" type="hidden" id="command" value="Browse" readonly="readonly">';
    echo '<input type="hidden" name="key" value="'.hash('md5', $admin['key']).'">';
    echo '<select name="listbox">';  
    foreach($users as $user) {
      $num = count(scandir($spooldir.'/upload/'.$user.'/')) - 2;
      if($user == $_POST['listbox']) {
        echo '<option value="'.$user.'" selected="selected">'.$user.' ('.$num.' files)</option>';
	$found = 1;
      } else {
        echo '<option value="'.$user.'">'.$user.' ('.$num.' files)</option>';
      }
    }
    echo '</select>';
    echo '<input type="submit" name="Submit" value="Browse">';
    echo '</form>';
  }

  if($found == 1 && $_POST['key'] == hash('md5', $admin['key'])) {
    display_user_files($_POST['listbox'], $offset, $admin);
  } 

function display_user_files($user, $offset, $admin) {
  global $CONFIG, $spooldir, $text_header;
  $directory = $spooldir.'/upload/'.$user.'/';
  if(is_dir($directory)) {
    $files = scandir($directory);
  }
  natcasesort($files);
  echo '<table cellspacing="0" class="np_thread_table">';
  echo '<tr class="np_thread_head"><td class="np_thread_head">Filename</td><td>File Type</td><td>Date</td></tr>';
  $i=0;
  foreach($files as $file) {
    if($file == '.' || $file == '..') {
      continue;
    }
    $lineclass="np_thread_line".(($i%2)+1);
    $thisfile = $spooldir.'/upload/'.$user.'/'.$file;
// Use local timezone if possible
    $ts = new DateTime(date("D, j M Y H:i T", filectime($thisfile)), new DateTimeZone('UTC'));
    $ts->add(DateInterval::createFromDateString($offset.' minutes'));
    
    if($offset != 0) {
      $newdate = $ts->format('j M Y');
    } else {
      $newdate = $ts->format('j M Y T');
    }
    unset($ts);
    echo '<tr class="'.$lineclass.'">';
    $mime = mime_content_type($thisfile);
// Link 
    echo '<td class="'.$lineclass.'">';
    echo '<form action="files.php" method="post" target="rslight_view">';
    echo '<button class="np_button_link" type="submit">'.$file.'</button>';
    echo '<input type="hidden" name="showfile" value="'.$user.'/'.$file.'"/>';
    echo '<input type="hidden" name="showfilename" value="'.$file.'"/>';
    echo '<input type="hidden" name="key" value="'.hash('md5', $admin['key']).'">';
    echo '<input type="hidden" name="contenttype" value="'.$mime.'">';
    echo '<input name="command" type="hidden" id="command" value="Show" readonly="readonly">';
    echo '</form>';
    echo '</td>';
//    echo '<td class="'.$lineclass.'"><span class="np_thread_line_text">'.$file.'</span></td>';
    echo '<td class="'.$lineclass.'"><span class="np_thread_line_text">'.$mime.'</span></td>';
    echo '<td class="'.$lineclass.'"><span class="np_thread_line_text">'.$newdate.'</span></td>';
    echo '</tr>';
    $i++;
  }
  echo '</table>';
}
?>
