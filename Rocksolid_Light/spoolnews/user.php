<?php
session_start();

include "config.inc.php";
include "newsportal.php";

  if(isset($_COOKIE['tzo'])) {
    $offset=$_COOKIE['tzo'];
  } else {
    $offset=$CONFIG['timezone'];
  }

$keyfile = $spooldir.'/keys.dat';
$keys = unserialize(file_get_contents($keyfile));
if($_POST['command'] == 'Logout') {
  unset($_COOKIE['mail_name']); 
  setcookie('mail_name', null, -1, '/');
  unset($_COOKIE['mail_auth']);
  setcookie('mail_auth', null, -1, '/');
  unset($_COOKIE['cookie_name']);
  setcookie('cookie_name', null, -1, '/');
  unset($_SESSION['theme']);
  unset($_POST['username']);
  include "head.inc";
  echo 'You have been logged out';
  exit(0);
}
include "head.inc";

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
  echo '<h1 class="np_thread_headline">';
    
  echo '<a href="user.php" target='.$frame['menu'].'>Configuration</a> / ';
  echo htmlspecialchars($_POST['username']).'</h1>';

echo '<table cellpadding="0" cellspacing="0" class="np_buttonbar"><tr>';
// Mail button
    if($logged_in == true) {
      echo '<td>';
      echo '<form target="'.$frame['content'].'" method="post" action="mail.php">';
      echo '<input name="command" type="hidden" id="command" value="Mail" readonly="readonly">';
      echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
      echo "<input type='hidden' name='password' value='".$_POST['password']."' />";
      echo '<button class="np_button_link" type="submit">Mail</button>';
      echo '</form>';
      echo '</td>';
// Logout button
      echo '<td>';
      echo '<form target="'.$frame['content'].'" method="post" action="user.php">';
      echo '<input name="command" type="hidden" id="command" value="Logout" readonly="readonly">';
      echo "<input type='hidden' name='username' value='".$_POST['username']."' />";
      echo "<input type='hidden' name='password' value='".$_POST['password']."' />";
      echo "<input type='hidden' name='id' value='".$_POST['id']."' />";
      echo '<button class="np_button_link" type="submit">Logout</button>';
      echo '</form>';
      echo '</td>';
    }
    echo '<td width=100%></td></tr></table>';

if(isset($_POST['username'])) {
  $name = $_POST['username'];
// Save name in cookie
  if ($setcookies==true) {
    setcookie("mail_name",stripslashes($name),time()+(3600*24*90));
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
echo '<form name="form1" method="post" action="user.php" enctype="multipart/form-data">';
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
  $_SESSION['username'] = $user;
  unset($user_config);
  $userfile=$spooldir.'/'.$user.'-articleviews.dat';
  $userdata = unserialize(file_get_contents($userfile));
  ksort($userdata);

// Apply Config
    if(isset($_POST['command']) && $_POST['command'] == 'SaveConfig') {
	$user_config['signature'] = $_POST['signature'];
        $user_config['xface'] = $_POST['xface'];
        $user_config['timezone'] = $_POST['timezone'];
	$user_config['theme'] = $_POST['listbox'];
	file_put_contents($config_dir.'/userconfig/'.$user.'.config', serialize($user_config));
	$_SESSION['theme'] = $user_config['theme'];
	$mysubs = explode("\n", $_POST['subscribed']);
	foreach($mysubs as $sub) {
	  if(trim($sub) == '') {
	    continue;
	  }
          $sub = trim($sub);
          if(!isset($userdata[$sub])) {
            $userdata[$sub] = 0;
          }
          $newsubs[$sub] = $userdata[$sub];
	}
	file_put_contents($spooldir.'/'.$user.'-articleviews.dat', serialize($newsubs));
	$userdata = unserialize(file_get_contents($userfile));
	ksort($userdata);
	echo 'Configuration Saved for '.$_POST[username];
    } else {
	$user_config = unserialize(file_get_contents($config_dir.'/userconfig/'.$user.'.config'));
    }
// Get themes
  $themedir = $rootdir.'/common/themes';
  if(is_dir($themedir)) {
    if($theme_list = opendir($themedir)) {
      while(($theme_dir = readdir($theme_list)) !== false) {
        if($theme_dir == '.' || $theme_dir == '..') {
          continue;
        }
        $themes[] = $theme_dir;
      }
      closedir($theme_dir);
    }
  }
  sort($themes);

// Show Config 
    echo '<hr><h1 class="np_thread_headline">Configuration:</h1>';
    echo '<table cellspacing="0" width="100%" class="np_results_table">';
    echo '<tr class="np_thread_head"><td class="np_thread_head">Settings for '.$_POST[username].' (leave blank for none):</td></tr>';
    echo '<form method="post" action="user.php">';
    echo '<tr class="np_result_line1">';
// Signature
      echo '<td class="np_result_line1" style="word-wrap:break-word";>Signature:</td>';
        echo '</tr><tr><td class="np_result_line1" style="word-wrap:break-word";><textarea class="configuration" id="signature" name="signature" rows="6" cols="70">'.$user_config[signature];
	echo '</textarea></td>'; 
	echo '</tr>';
// X-Face
      echo '<td class="np_result_line1" style="word-wrap:break-word";>X-Face:</td>';
        echo '</tr><tr><td class="np_result_line1" style="word-wrap:break-word";><textarea class="configuration" id="xface" name="xface" rows="4" cols="80">'.$user_config[xface];
        echo '</textarea></td>';	
        echo '</tr>';
// Theme
      echo '<td class="np_result_line1" style="word-wrap:break-word";>Theme: ('.$user_config['theme'].')</td>';
        echo '</tr><tr><td class="np_result_line1" style="word-wrap:break-word">';
	echo '<select name="listbox" class="theme_listbox" size="10">';
	foreach ($themes as $theme) {
	  if($theme == $user_config['theme']) {
	    echo '<option value="'.$theme.'" selected="selected">'.$theme.'</option>';
	  } else {
	    echo '<option value="'.$theme.'">'.$theme.'</option>';
	  }
	}
	echo '</select>';
	echo '</td>';
        echo '</tr>';
// Subscriptions
      echo '<td class="np_result_line1" style="word-wrap:break-word";>Subscribed:</td>';
        echo '</tr><tr><td class="np_result_line1" style="word-wrap:break-word";><textarea class="configuration" id="subscribed" name="subscribed" rows="10" cols="40">';
        foreach($userdata as $key => $value) {
          echo $key."\n";
        }
        echo '</textarea></td>';	
        echo '</tr>';
/*
  // Timezone
      echo '<td class="np_result_line1" style="word-wrap:break-word";>Timezone offset (+/- hours from UTC):</td>';
	echo '</tr><tr><td class="np_result_line1" style="word-wrap:break-word";><input type="text" name="timezone" value="'.$user_config[timezone].'"></td>';
        echo '</tr>';
*/
      echo '<td class="np_result_line2" style="word-wrap:break-word";>';
	echo '<button class="np_button_link" type="submit">Save Configuration</button>';
	echo '<a href="'.$_SERVER['PHP_SELF'].'">Cancel</a>';
      echo '</td></tr>';
      echo '<input name="command" type="hidden" id="command" value="SaveConfig" readonly="readonly">';
    echo '</form>';
    echo '</tbody></table><br />';
    include "tail.inc";
?>
