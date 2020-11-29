<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=<?=$www_charset?>">
<?php
if (file_exists('../common/mods/style.css')) {
  echo '<link rel="stylesheet" type="text/css" href="../common/mods/style.css">';
} else {
  echo '<link rel="stylesheet" type="text/css" href="../common/style.css">';
}
?>
</head>
<body>
<?php

include "config.inc.php";

# $hostname: '{POPaddress:port/pop3}INBOX'
$hostname = '{rocksolidbbs:110/pop3}INBOX';
# $external: Using external POP auth?
$external = 0;
# $workpath: Where to cache users (must be writable by calling program)
$workpath = $config_dir."users/";
$keypath = $config_dir."userconfig/";

# DO NOT EDIT ANYTHING BELOW THIS LINE
$ok = FALSE;
$command = "Login";

$current = $_POST['current'];
$username = $_POST['username'];
$password = $_POST['password'];
$command = $_POST['command'];

echo '<center>';

$thisusername = $username;
$username = strtolower($username);
$userFilename = $workpath.$username;
$keyFilename = $keypath.$username;

# Check all input
if (empty($_POST['username'])) {
  echo "Please enter a Username\r\n";
  echo '<br /><a href="changepw.php">Back</a>';
  exit(2);
}

if (!check_bbs_auth($username, $current)) {
  echo "Failed to authenticate\r\n";
  echo '<br /><a href="changepw.php">Back</a>';
  exit(2);
}

if ($_POST['password'] !== $_POST['password2']) {
  echo "Your passwords entered do not match\r\n";
  echo '<br /><a href="changepw.php">Back</a>';
  exit(2);
}

$ok=true;
# User is authenticated or to be created. Either way, create the file
if ($ok || ($command == "Change") )
{
    if ($userFileHandle = @fopen($userFilename, 'w+'))
    {
        fwrite($userFileHandle, password_hash($password, PASSWORD_DEFAULT));
        fclose($userFileHandle);
		chmod($userFilename, 0666);
    }

    echo "User:".$thisusername." Password changed\r\n";
    echo '<br /><a href="../">Back</a>';
    exit(0);
} else {
    echo "Authentication Failed\r\n";
    exit(1);
}

function make_key($username) {
    $key = openssl_random_pseudo_bytes(44);
    return base64_encode($key);
}

function check_bbs_auth($username, $password) {
  global $config_dir;
  $workpath = $config_dir."users/";
  $username = strtolower($username);
  $userFilename = $workpath.$username;

  if ($userFileHandle = @fopen($userFilename, 'r'))
  {
        $userFileInfo = fread($userFileHandle, filesize($userFilename));
        fclose($userFileHandle);
        if (password_verify ( $password , $userFileInfo))
        {
                touch($userFilename);
                $ok = TRUE;
        } else {
                $ok = FALSE;
        }
  } else {
        $ok = FALSE;
  }
  if ($ok)
  {
        return TRUE;
  } else {
        return FALSE;
  }
}
?>
</body>
</html> 
