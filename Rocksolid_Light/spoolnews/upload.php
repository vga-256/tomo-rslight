<?php
include "config.inc.php";
include "newsportal.php";

$logfile=$logdir.'/files.log';

unset($name);
if(isset($_POST['username']) && $_POST['username'] !== '') {
  $name = $_POST['username'];
} else {
  if ($setcookies) {
    if (isset($_COOKIE['files_name'])) {
      $name=$_COOKIE['files_name'];
    }
  }
}
if(!isset($name)) {
  $name = '';
}

  $title.=' - Upload file';
include "head.inc";
  echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// Browse button
    echo '<td>';
    echo '<form target="'.$frame['content'].'" method="post" action="files.php">';
    echo '<input name="command" type="hidden" id="command" value="Browse" readonly="readonly">';
    echo '<input type="hidden" name="username" value="'.$_POST['username'].'">';
    echo '<input type="hidden" name="password" value="'.$_POST['password'].'">';
    echo '<button class="np_button_link" type="submit">Browse</button>';
    echo '</form>';
    echo '</td>';
// Upload button
    echo '<td>';
    echo '<form target="'.$frame['content'].'" method="post" action="upload.php">';
    echo '<input name="command" type="hidden" id="command" value="Upload" readonly="readonly">';
    echo '<input type="hidden" name="username" value="'.$_POST['username'].'">';
    echo '<input type="hidden" name="password" value="'.$_POST['password'].'">';
    echo '<button class="np_button_link" type="submit">Upload</button>';
    echo '</form>';
    echo '</td>';
    echo '<td width=100%></td></tr></table>';
    echo '<hr>';

if(isset($_FILES)) {
   $_FILES[photo][name] = preg_replace('/[^a-zA-Z0-9\.]/', '_', $_FILES[photo][name]);
// Check auth here
    if(isset($_POST['key']) && password_verify($CONFIG['thissitekey'].$_POST['username'], $_POST['key'])) {
      if(check_bbs_auth($_POST['username'], $_POST['password'])) {
	$userdir = '/var/spool/rslight/upload/'.strtolower($_POST[username]);
	$upload_to = $userdir.'/'.$_FILES[photo][name];
	if(is_file($upload_to)) {
	  echo $_FILES[photo][name].' already exists in your folder';
	} else {
	  if(!is_dir($userdir)) {
	    mkdir($userdir);
	  }	
	  $success = move_uploaded_file($_FILES[photo][tmp_name], $upload_to);
	  if ($success) {
	    file_put_contents($logfile, "\n".format_log_date()." Saved: ".strtolower($_POST['username'])."/".$_FILES[photo][name], FILE_APPEND);
  	    echo 'Saved '.$_FILES[photo][name].' to your files folder';
	  } else {
  	    echo 'There was an error saving '.$_FILES[photo][name];
	  }
	}
      $authkey = password_hash($_POST['username'].$keys[0].get_user_config($_POST['username'],'encryptionkey'), PASSWORD_DEFAULT);
?>
      <script type="text/javascript">
       if (navigator.cookieEnabled)
         var savename = "<?php echo stripslashes($name); ?>";
         document.cookie = "files_name="+savename+"; path=/";
      </script>
<?php
      } else {
	echo 'Authentication Failed';
      }
    echo '<br /><br />';
    }
}

  echo '<table border="0" align="center" cellpadding="0" cellspacing="1">';
  echo '<form name="form1" method="post" action="upload.php" enctype="multipart/form-data">';

if(!check_bbs_auth($_POST['username'], $_POST['password'])) {  
  echo '<tr><td><strong>Please Login to Upload<br /></strong></td></tr>';
  echo '<tr><td>Username:</td><td><input name="username" type="text" id="username" value="'.$name.'"></td></tr>';
  echo '<tr><td>Password:</td><td><input name="password" type="password" id="password"></td></tr>';
  echo '<td><input name="command" type="hidden" id="command" value="Upload" readonly="readonly"></td>';
  echo '<td><input type="submit" name="Submit" value="Login"></td>';
} else {
  echo '<tr><td><strong>Logged in as '.$_POST['username'].'<br />(max size=2MB)</strong></td></tr>';
  echo '<td><input name="command" type="hidden" id="command" value="Upload" readonly="readonly"></td>';
  echo '<input type="hidden" name="key" value="'.password_hash($CONFIG['thissitekey'].$name, PASSWORD_DEFAULT).'">';
  echo '<input type="hidden" name="username" value="'.$_POST['username'].'">';
  echo '<input type="hidden" name="password" value="'.$_POST['password'].'">';
  echo '<tr><td><input type="file" name="photo" id="fileSelect" value="fileSelect" accept="image/*,audio/*,text/*,application/*"></td>
';
  echo '<td>&nbsp;<input type="submit" name="Submit" value="Upload"></td>';
}
echo '</tr>';
echo '</form>';
echo '</table>';
echo '</body></html>';
?>
