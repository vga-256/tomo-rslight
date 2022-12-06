<?php
/*  spoolnews NNTP news spool creator
 *  Download: https://news.novabbs.com/getrslight
 *
 *  E-Mail: retroguy@novabbs.com
 *  Web: https://news.novabbs.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

set_time_limit(900);

include "config.inc.php";
include ("$file_newsportal");
 
$logfile=$logdir.'/spoolnews.log';

@mkdir($spooldir."/".$config_name,0755,'recursive');

$lockfile = $lockdir . '/rslight-send.lock';
$pid = file_get_contents($lockfile);
if (posix_getsid($pid) === false || !is_file($lockfile)) {
   file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Starting Send...", FILE_APPEND);    
   print "Starting Send...\n";
   file_put_contents($lockfile, getmypid()); // create lockfile
} else {
        file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Send currently running...", FILE_APPEND);
        print "Send currently running\n";
       exit;
}
$ns=nntp2_open($CONFIG['remote_server'], $CONFIG['remote_port']);
if($ns == false) {
  file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Failed to connect to ".$CONFIG['remote_server'].":".$CONFIG['remote_port'], FILE_APPEND);
  exit();
}
echo "\nPosting articles\r\n";
post_articles($ns, $spooldir);
nntp_close($ns);
unlink($lockfile);
file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Exiting Send...", FILE_APPEND);
echo "\nSend Done\r\n";

function post_articles($ns, $spooldir) {
  global $logfile,$config_name;
  if(!is_dir($spooldir."/".$config_name."/outgoing/")) {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." No messages to send", FILE_APPEND);
      return "No messages to send\r\n";
  }
  $outgoing_dir = $spooldir."/".$config_name."/outgoing/";
  $failed_dir = $outgoing_dir.'/failed';
  @mkdir($failed_dir);
  $messages = scandir($outgoing_dir);
  foreach($messages as $message) {
    if(!is_file($outgoing_dir.$message)) {
      continue;
    }
    file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Sending: ".$outgoing_dir.$message, FILE_APPEND);
    echo "Sending: ".$outgoing_dir.$message."\r\n";
    fputs($ns, "MODE READER\r\n");
    $response = line_read($ns);
    if (strcmp(substr($response,0,3),"200") != 0) {
	file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Unexpected response to MODE command: ".$response, FILE_APPEND);
      return $response;
    }
    fputs($ns, "POST\r\n");
    $response = line_read($ns);
    if (strcmp(substr($response,0,3),"340") != 0) {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Unexpected response to POST command: ".$response, FILE_APPEND);
      return $response;
    } 
    $message_fp = fopen($outgoing_dir.$message, "rb");
    while (($msgline = fgets($message_fp, 4096)) !== false) {
      fputs($ns, $msgline);
    }
    fputs($ns, ".\r\n");
    fclose($message_fp);
    $response = line_read($ns);
    if (strcmp(substr($response,0,3),"240") == 0) {
      unlink($outgoing_dir.$message);
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Posted: ".$message.": ".$response, FILE_APPEND);
    } else {
      file_put_contents($logfile, "\n".format_log_date()." ".$config_name." Failed to POST: ".$message.": ".$response, FILE_APPEND);
      rename($outgoing_dir.$message, $failed_dir.'/'.$message);
      continue;
    }
  }
  return "Messages sent\r\n";
}
?>
