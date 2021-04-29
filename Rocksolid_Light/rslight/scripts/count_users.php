<?php
  include "config.inc.php";
  include ("$file_newsportal");
  if(trim($CONFIG['tac'] == '')) {
	if(is_file($spooldir.'/sessions.dat')) {
	  unlink($spooldir.'/sessions.dat');
	}
	exit(0);
  }
count_users();

function count_users() {
	GLOBAL $CONFIG, $spooldir;
	$session_age = 300;
	$session_save_file = $spooldir.'/sessions.dat';
    $session_dir = $CONFIG['tac'];
    $session_files = scandir($session_dir);
    $count = 0;
    foreach($session_files as $session_file) {
		if(filemtime($session_dir.'/'.$session_file) < time() - $session_age) {
			continue;
		}
        if(strpos($session_file, 'sess_') === 0) {
            $contents = file_get_contents($session_dir.'/'.$session_file);
            if(strpos($contents, 'starttime') !== false) {
                $count++;
            }
        }
    }
        if($count == 1) {
            $are = 'is';
            $users = 'user';
        } else {
            $are = 'are';
            $users = 'users';
        }
		$session_info = '<h1 class="np_thread_headline">There '.$are.' currently '.$count.' '. $users.' online</h1>'."\r\n";
        file_put_contents($session_save_file, $session_info);
}
?>
