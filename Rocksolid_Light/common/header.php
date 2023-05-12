<html>
	<head>
<?php
session_start();
if (basename(getcwd()) == 'mods') {
  $rootdir = "../../";
} else {
  $rootdir = "../";
}

include($rootdir.'common/config.inc.php');

$CONFIG = include $config_file;
?>
   <script type="text/javascript">
     if (navigator.cookieEnabled)
       document.cookie = "tzo="+ (- new Date().getTimezoneOffset())+"; path=/";
   </script>
<?php

$menulist = file($config_dir."menu.conf", FILE_IGNORE_NEW_LINES);
$linklist = file($config_dir."links.conf", FILE_IGNORE_NEW_LINES);

if(isset($_COOKIE['mail_name']) && isset($_COOKIE['pkey'])) {
  $user = strtolower($_COOKIE['mail_name']);
} else {
  unset($user);
}
if(!isset($_SESSION['theme']) && file_exists($config_dir.'/userconfig/'.$user.'.config')) {
  $user_config = unserialize(file_get_contents($config_dir.'/userconfig/'.$user.'.config'));
  $_SESSION['theme'] = $user_config['theme'];
}

if(trim($_SESSION['theme']) !== '') {
  echo '<link rel="stylesheet" type="text/css" href="../common/themes/'.$_SESSION['theme'].'/style.css">';
} else {
  echo '<link rel="stylesheet" type="text/css" href="'.$rootdir.'common/themes/Default Theme/style.css">';
}

if (file_exists($rootdir.'common/themes/'.$_SESSION['theme'].'/images/rocksolidlight.png')) {
  $header_image=$rootdir.'common/themes/'.$_SESSION['theme'].'/images/rocksolidlight.png';
} else {
  $header_image=$rootdir.'common/images/rocksolidlight.png';
}
?>
	</head>
	<body>
<table width="100%" valign="middle">
	<tr>
		<td width="30%">
			<a href="<?php echo $CONFIG['default_content'];?>"><img src="<?php echo $header_image ?>" alt="Rocksolid Light" class="responsive_image"></a>
		</td>
		<td>


<p align="left"><small>
	<font class="np_title">
	<?php echo $CONFIG['rslight_title']; ?>	
	</font>
</small></p>
		</td>
		<td align="right">
<?php
		if($user && check_unread_mail() == true) {
			$unread = true;
		} else {
			$unread = false;
		}
			foreach($linklist as $link) {
				if($link[0] == '#') {
					continue;
				}
				$linkitem=explode(':', $link, 2);
				if($linkitem[1] == '0') {
					continue;
				}
				if($unread && (strpos($linkitem[1], 'spoolnews/mail.php') !== false)) {
				  echo '<strong>';
           			  echo '<a href="'.trim($linkitem[1]).'">'.trim(strtoupper($linkitem[0])).'</a>&nbsp;&nbsp';
				  echo '</strong>';
				} else {
				  echo '<a href="'.trim($linkitem[1]).'">'.trim($linkitem[0]).'</a>&nbsp;&nbsp';
				}
			}
			echo '<a href="../spoolnews/user.php">';
                        if(isset($user)) {
                                echo '('.$_COOKIE['mail_name'].')';
                        } else {
				echo 'login';
			}
			echo '</a>';
?>
		</td>
	</tr>
</table>
<?php

// You can display fortunes if you have fortune installed. This is disabled by default
$display_fortunes = true;

if($display_fortunes) {
ob_start();

// Select fortunes
// If you want different fortunes for different sections you can do that here

if($config_name == 'computers' || $config_name == 'programming') {
  passthru('/usr/games/fortune -s -n 100 linux debian perl linuxcookie computers');
} else if ($config_name == 'arts' || $config_name == 'interests') {
  passthru('/usr/games/fortune -s -n 100 art magic tao cookie songs-poems work food miscellaneous literature pets science wisdom');
} else if ($config_name =='sport') {
  passthru('/usr/games/fortune -s -n 100 sports');
} else {
  passthru('/usr/games/fortune -s -n 100');
}
$motd = ob_get_contents();
$motd = htmlentities($motd);
ob_end_clean();
}

// If $config_dir/motd.txt is not blank, show it
$m = file_get_contents($config_dir.'/motd.txt');
if (trim($m) !== '' ) {
  $motd = $m;
}

echo '<p align="center">';
echo '<table cellpadding="0" cellspacing="0" class="np_header_bar_large"><tr>';
foreach($menulist as $menu) {
    if($menu[0] == '#') {
      continue;
    }
    $menuitem=explode(':', $menu);
    if($menuitem[1] == '0') { 
      continue;
    }
    echo '<td>';
    echo '<form target="'.$frame['menu'].'" action="'.$rootdir.$menuitem[0].'">';
    echo '<button class="np_header_button_link" type="submit">'.$menuitem[0].'</button>';
    echo '</form>';
    echo '</td>';
}
    echo '</tr></table>';
echo '<table cellpadding="0" cellspacing="0" class="np_header_bar_small"><tr>';

foreach($menulist as $menu) {
    if($menu[0] == '#') {
      continue;
    }
    $menuitem=explode(':', $menu);
    if($menuitem[1] == '0') { 
      continue;
    }
    echo '<td>';
    echo '<form target="'.$frame['content'].'" action="'.$rootdir.$menuitem[0].'">';
    echo '<button class="np_header_button_link" type="submit">'.$menuitem[0].'</button>';
    echo '</form>';
    echo '</td>';
}
if($unread) {
    $motd = 'You have unread mail. <a href="../spoolnews/mail.php">Click Here</a>';
}
    if(strlen($motd) > 0) {
      echo '<div class="np_last_posted_date"><h1 class="np_thread_headline">'.$motd.'</h1></div>';
    }
    echo '</tr></table>';
    echo '</p>';
function check_unread_mail() {
        global $CONFIG, $spooldir;
        if(isset($_COOKIE['mail_name'])) {
          $name = strtolower($_COOKIE['mail_name']);
          $database = $spooldir.'/mail.db3';
          if(is_file($database)) {
            $dbh = head_mail_db_open($database);
            $query = $dbh->prepare('SELECT * FROM messages where rcpt_to=:rcpt_to');
            $query->execute(['rcpt_to' => $name]);
            $newmail = false;
            while (($row = $query->fetch()) !== false) {
              if(($row['rcpt_viewed'] != 'true') && ($row['to_hide'] !='true')) {
                $newmail = true;
              }
            }
            $dbh = null;
            return $newmail;
          } else {
            return false;
        }
    }
}

function head_mail_db_open($database, $table='messages') {
  try {
    $dbh = new PDO('sqlite:'.$database);
  } catch (PDOExeption $e) {
    echo 'Connection failed: '.$e->getMessage();
    exit;
  }
  $dbh->exec("CREATE TABLE IF NOT EXISTS messages(
     id INTEGER PRIMARY KEY,
     msgid TEXT UNIQUE,
     mail_from TEXT,
     mail_viewed TEXT,
     rcpt_to TEXT,
     rcpt_viewed TEXT,
     rcpt_target TEXT,
     date TEXT,
     subject TEXT,
     message TEXT,
     from_hide TEXT,
     to_hide TEXT)");
  return($dbh);
}
?>
</body></html>
