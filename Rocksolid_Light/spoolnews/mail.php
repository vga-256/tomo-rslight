<?php
session_start();

include "config.inc.php";
include "newsportal.php";

  if(isset($_COOKIE['tzo'])) {
    $offset=$_COOKIE['tzo'];
  } else {
    $offset=$CONFIG['timezone'];
  }

  if(!isset($_POST['command'])) {
      $_POST['command'] = '';
  }
$keyfile = $spooldir.'/keys.dat';
$keys = unserialize(file_get_contents($keyfile));


// How long should cookie allow user to stay logged in?
// 14400 = 4 hours
  $auth_expire = 14400;
  $logged_in = false;
  if(!isset($_POST['username'])) {
    $_POST['username'] = $_COOKIE['mail_name'];
  }
  $name = $_POST['username'];
  if((password_verify($_POST['username'].$keys[0].get_user_config($_POST['username'],'encryptionkey'), $_COOKIE['mail_auth'])) || (password_verify($_POST['username'].$keys[1].get_user_config($_POST['username'],'encryptionkey'), $_COOKIE['mail_auth']))) {
    $logged_in = true;
  } else {
    if(check_bbs_auth($_POST['username'], $_POST['password'])) {
      $authkey = password_hash($_POST['username'].$keys[0].get_user_config($_POST['username'],'encryptionkey'), PASSWORD_DEFAULT);
?>
      <script type="text/javascript">
       if (navigator.cookieEnabled)
         var authcookie = "<?php echo $authkey; ?>";
         var savename = "<?php echo stripslashes($name); ?>";
	 var auth_expire = "<?php echo $auth_expire; ?>";
	 var name_expire = "7776000";
         document.cookie = "mail_auth="+authcookie+"; max-age="+auth_expire+"; path=/";
         document.cookie = "mail_name="+savename+"; max-age="+name_expire+"; path=/";
      </script>
<?php
      $logged_in = true;
    }
  }
$title.=' - Mail';
include "head.inc";

  echo '<h1 class="np_thread_headline">';
    
  echo '<a href="mail.php" target='.$frame['menu'].'>mail</a> / ';
  echo htmlspecialchars($_POST['username']).'</h1>';

echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// New Message button
    if($_POST['command'] !== 'Send') {
      echo '<td>';
      echo '<form target="'.$frame['content'].'" method="post" action="mail.php">';
      echo '<input name="command" type="hidden" id="command" value="Send" readonly="readonly">';
      echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
      echo '<button class="np_button_link" type="submit">New Message</button>';
      echo '</form>';
      echo '</td>';
    }
// Delete Message button
    if(isset($_POST['command']) && $_POST['command'] == 'Message') {
      echo '<td>';
      echo '<form target="'.$frame['content'].'" method="post" action="mail.php">';
      echo '<input name="command" type="hidden" id="command" value="Delete" readonly="readonly">';
      echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
      echo "<input type='hidden' name='id' value='".$_POST['id']."' />";
      echo '<button class="np_button_link" type="submit">Delete This Message</button>';
      echo '</form>';
      echo '</td>';
    }
    echo '<td width=100%></td></tr></table>';

if(isset($_POST['username'])) {
  $name = $_POST['username'];
// Save name in cookie
  if ($setcookies==true) {
    setcookie("mail_name",stripslashes($name),time()+(3600*24*90),"/");
  }
} else {
  if ($setcookies) {
    if ((isset($_COOKIE["mail_name"])) && (!isset($name))) {
      $name=$_COOKIE["mail_name"];
    } else {
      $name = '';
    }
  }
}
        if($logged_in !== true) {
echo '<table border="0" align="center" cellpadding="0" cellspacing="1">';
echo '<form name="form1" method="post" action="mail.php" enctype="multipart/form-data">';
echo '<tr><td><strong>Please Login<br /></strong></td></tr>';
echo '<tr><td>Username:</td><td><input name="username" type="text" id="username" value="'.$name.'"></td></tr>';
echo '<tr><td>Password:</td><td><input name="password" type="password" id="password"></td></tr>';
echo '<td><input name="command" type="hidden" id="command" value="Login" readonly="readonly"></td>';
echo '<input type="hidden" name="key" value="'.password_hash($CONFIG['thissitekey'].$name, PASSWORD_DEFAULT).'">';
echo '<td>&nbsp;</td>';
echo '<td><input type="submit" name="Submit" value="Login"></td>';
echo '</tr>';
echo '</form>';
echo '</table>';
	exit(0);
      	}

  $user = strtolower($_POST['username']);

  if(isset($_POST['command']) && $_POST['command'] == 'Delete') {
    $database = $spooldir.'/mail.db3';
    $dbh = mail_db_open($database);
    $query = $dbh->prepare('SELECT * FROM messages where id=:id');
    $query->execute(['id' => $_POST['id']]);
    while (($row = $query->fetch()) !== false) {
      if(($row['mail_from'] != $user) && ($row['rcpt_to'] != $user)) {
        continue;
      }
      $istrue = 'true';
      if($row['mail_from'] == $user) {
        $sql_update = $dbh->prepare('UPDATE messages SET from_hide=:from_hide WHERE id=:row_id');
	$sql_update->execute(array(':from_hide' => $istrue, ':row_id' => $row['id']));
      } 
      if($row['rcpt_to'] == $user) {
        $sql_update = $dbh->prepare('UPDATE messages SET to_hide=:to_hide WHERE id=:row_id');
	$sql_update->execute(array(':to_hide' => $istrue, ':row_id' => $row['id']));
      }
    }
    $dbh = null;
  }

  if(isset($_POST['command']) && $_POST['command'] == 'Message') {
    $database = $spooldir.'/mail.db3';
    $dbh = mail_db_open($database);
    $query = $dbh->prepare('SELECT * FROM messages where id=:id');
    $query->execute(['id' => $_POST['id']]);
    while (($row = $query->fetch()) !== false) {
      $ts = new DateTime(date("D, j M Y H:i T", $row["date"]), new DateTimeZone('UTC'));
      $ts->add(DateInterval::createFromDateString($offset.' minutes'));
     
      if($offset != 0) {
        $newdate = $ts->format('D, j M Y H:i');
      } else {
        $newdate = $ts->format('D, j M Y H:i T');
      }
      unset($ts);
      if(($row['mail_from'] != $user) && ($row['rcpt_to'] != $user)) {
	continue;
      } 
      $body = rtrim(nl2br($row['message'])).'<br />'; 
      echo '<div class="np_article_header">';
      echo '<b>Subject:</b> '.$row['subject'].'<br />';  
      echo '<b>From:</b> '.$row['mail_from'].'<br />';
      echo '<b>To:</b> '.$row['rcpt_to'].'<br />';
      echo '<b>Date:</b> '.$newdate.'<br />';
      echo '</div>';

      echo '<div class="np_article_body">';
      echo $body;
      echo '<form action="mail.php" method="post">';
      echo '<button class="np_button_link" type="submit">Reply</button>';
      echo "<input type='hidden' name='id' value='".$row['id']."' />";
      echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
      echo '<input name="command" type="hidden" id="command" value="Send" readonly="readonly">';
      echo '</form>';
      echo '</div>';
      if($row['mail_from'] == $user) {
        $sql_update = $dbh->prepare('UPDATE messages SET mail_viewed=? WHERE msgid=?');
	$sql_update->execute(array('true', $row['msgid']));
      } 
      if($row['rcpt_to'] == $user) {
        $sql_update = $dbh->prepare('UPDATE messages SET rcpt_viewed=? WHERE msgid=?');
	$sql_update->execute(array('true', $row['msgid']));
      }
    }
    $dbh = null;
 
  }
        if (isset($_POST['sendMessage'])) {
                if (isset($_POST['to']) && $_POST['to'] != '' && isset($_POST['from']) && $_POST['from'] != '' && isset($_POST['message']) && $_POST['message'] != '') {
            if(($to = get_config_value('aliases.conf', strtolower($_POST['to']))) == false) {
              $to = strtolower($_POST['to']);
            }
	    $userlist = scandir($config_dir.'/users/');
	    $found = 0;
	    foreach($userlist as $user) {
	      if(trim($to) == trim($user)) {
		$found = 1;
		break;
	      }
	    }
	 if($found == 0) {
	    echo 'User not found: '.$to;
	 } else { 
            $database = $spooldir.'/mail.db3';
            $dbh = mail_db_open($database);
            $from = $_POST['from'];
	    $subject = $_POST['subject'];
	    $message = $_POST['message'];
            $date = time();
	    $message = $_POST['message'];
	    $msgid = '<'.md5(strtolower($to).strtolower($from).strtolower($subject).strtolower($message)).'>';
	    $sql = 'INSERT INTO messages(msgid, mail_from, rcpt_to, rcpt_target, date, subject, message, from_hide, to_hide, mail_viewed, rcpt_viewed) VALUES(?,?,?,?,?,?,?,?,?,?,?)';
	    $stmt = $dbh->prepare($sql);
// For possible future use
	    $target = "local";
	    $mail_viewed = "true";
	    $rcpt_viewed = null;
	    $q = $stmt->execute([$msgid, $from, $to, $target, $date, $subject, $message, null, null, $mail_viewed, $rcpt_viewed]);
            if ($q) {
              echo 'Message sent.';
            }else
              echo 'Failed to send message.';
            }
	    $dbh = null;
	  }
        }
  if(isset($_POST['command']) && $_POST['command'] == 'Send') {
	if(isset($_POST['id'])) {
	  $database = $spooldir.'/mail.db3';
          $dbh = mail_db_open($database);
          $query = $dbh->prepare('SELECT * FROM messages where id=:id');
          $query->execute(['id' => $_POST['id']]);
          while (($row = $query->fetch()) !== false) {
	    $mail_to = $row['mail_from'];
	    if(strpos($row['subject'], 'Re: ') !== 0) {
	      $subject = 'Re: '.$row['subject'];
	    } else {
	      $subject = $row['subject'];
	    }
	    $body=explode("\n",$row['message']);
	    $message = $row['mail_from']." wrote:\n\n";
	    foreach($body as $line) {
	        if(trim($line) !== '') {
		  $line = '>'.$line;
	        } 
		$message.=$line;
	    }
          }
	  $dbh = null;
	}
                echo '<h3>Send Message:</h3>';
                echo "<form action='mail.php' method='POST'>";
                echo '<table><tbody><tr>';
                echo "<td>To: </td><td><input type='text' name='to' value='".$mail_to."'/></td>";
                echo '</tr><tr>';
                echo "<td>Subject: </td><td><input type='text' name='subject' value='".$subject."'/></td>";
                echo '</tr><tr>';
		echo "<td></td><td><textarea class='postbody' id='message' name='message'>$message</textarea></td>";
		echo '</tr><tr>';
		echo "<input type='hidden' name='from' value='".$user."' />";
	        echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
                echo "<td></td><td><input type='submit' value='Send Mail' name='sendMessage' /></td>";
		echo '</tr></tbody></table></form>';
  }
// Show My Messages
            $database = $spooldir.'/mail.db3';
            $dbh = mail_db_open($database);
    echo '<hr><h1 class="np_thread_headline">My Messages:</h1>';
    echo '<table cellspacing="0" width="100%" class="np_results_table">';
    $query = $dbh->prepare('SELECT * FROM messages WHERE mail_from=:mail_from OR rcpt_to=:mail_from ORDER BY date DESC');
    $query->execute(['mail_from' => $user]);
    echo '<tr class="np_thread_head"><td class="np_thread_head">Subject</td><td class="np_thread_head">From</td><td class="np_thread_head">To</td><td class="np_thread_head">Date</td></tr>';
    $i=1;
    while (($row = $query->fetch()) !== false) { 
      if(($row['mail_from'] == $user) && ($row['from_hide'] == 'true')) { 
        continue;
      }
      if(($row['rcpt_to'] == $user) && ($row['to_hide'] == 'true')) {
        continue;
      }
      if(($i % 2) != 0){
        echo '<tr class="np_result_line1"><td class="np_result_line1" style="word-wrap:break-word";>';
      } else {
        echo '<tr class="np_result_line2"><td class="np_result_line2" style="word-wrap:break-word";>';
      }
    $button_link = 'np_mail_button_link';;
    if(($row['mail_from'] == $user) && ($row['mail_viewed'] == 'true')) {
      $button_link = 'np_mail_button_read';
    } elseif(($row['rcpt_to'] == $user) && ($row['rcpt_viewed'] == 'true')) {
      $button_link = 'np_mail_button_read';
    }
// Use local timezone if possible
    $ts = new DateTime(date("D, j M Y H:i T", $row["date"]), new DateTimeZone('UTC'));
    $ts->add(DateInterval::createFromDateString($offset.' minutes'));
    
    if($offset != 0) {
      $newdate = $ts->format('D, j M Y H:i');
    } else {
      $newdate = $ts->format('D, j M Y H:i T');
    }
    unset($ts);
    echo '<form action="mail.php" method="post">';
    echo '<button class="'.$button_link.'" type="submit">'.$row["subject"].'</button>';
    echo "<input type='hidden' name='id' value='".$row['id']."' />";
    echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
    echo '<input name="command" type="hidden" id="command" value="Message" readonly="readonly">';
    echo '</form>';
    echo '</td><td>'.$row["mail_from"].'</td><td>'.$row["rcpt_to"].'</td><td>'.$newdate.'</td></tr>';
    $i++;
    }
    echo '</tbody></table><br />';
    include "tail.inc";
?>
