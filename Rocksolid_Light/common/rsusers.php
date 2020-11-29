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
include($config_dir.'/phpmailer.inc.php');

# The following line is required for phpmailer 6.0 or above
#use PHPMailer\PHPMailer\PHPMailer;

include "head.inc";
$CONFIG = include($config_file);

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

$username = $_POST['username'];
$password = $_POST['password'];
$command = $_POST['command'];
$user_email = $_POST['user_email'];

echo '<center>';

$thisusername = $username;
$username = strtolower($username);
$userFilename = $workpath.$username;
$keyFilename = $keypath.$username;

# Check all input
if (empty($_POST['username'])) {
  echo "Please enter a Username\r\n";
  echo '<br /><a href="newuser.php">Back</a>';
  exit(2);
}

if ($_POST['password'] !== $_POST['password2']) {
  echo "Your passwords entered do not match\r\n";
  echo '<br /><a href="newuser.php">Back</a>';
  exit(2);
}

/* Check for existing email address */
$users = scandir($config_dir."/userconfig");
foreach($users as $user) {
  if(!is_file($config_dir."/userconfig/".$user)) {
    continue;
  }
  if ($userFileHandle = @fopen($config_dir."/userconfig/".$user, 'r')) {
    while (!feof($userFileHandle))
    {
      $buffer = fgets($userFileHandle);
      if(strpos($buffer, 'email:') !== FALSE) {
	if(stripos($buffer, $user_email) !== FALSE) {
	  fclose($userFileHandle); 
	  echo "Email exists in database\r\n";
          echo '<br /><a href="newuser.php">Back</a>';
          exit(2);
	}
      }
    }
    fclose($userFileHandle);
  } 
}

if (!preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^",$user_email)) {
  echo "Email must be in the form of an email address\r\n";
  echo '<br /><a href="newuser.php">Back</a>';
  exit(2);
}

# Does user file already exist?
if ($userFileHandle = @fopen($userFilename, 'r'))
{
    if ($command == "Create")
    {
        echo "User:".$thisusername." Already Exists\r\n";
	echo '<br /><a href="newuser.php">Back</a>';
	exit(2);
    }
    $userFileInfo = fread($userFileHandle, filesize($userFilename));
    fclose($userFileHandle);

    # User/Pass is correct
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

# Ok to log in. User authenticated.
if ($ok)
{
    echo "User:".$thisusername."\r\n";
        exit(0);
}

# Using external authentication
if ($external)
{
    $mbox = @imap_open ( $hostname , $username , $password );
    if ($mbox)
    {
        $ok = TRUE;
        imap_close($mbox);
    }
}

# User is authenticated or to be created. Either way, create the file
if ($ok || ($command == "Create") )
{
  echo 'Create account: '.$_POST['username'].'<br/><br />';
/* Generate email */
  $no_verify=explode(' ', $CONFIG['no_verify']);
  foreach($no_verify as $no) {
    if (strlen($_SERVER['HTTP_HOST']) - strlen($no) === strrpos($_SERVER['HTTP_HOST'],$no)) {
      $CONFIG['verify_email'] = false;
    }
  }
  if($CONFIG['verify_email']) {
// Setup mailer
$mail = new PHPMailer();

$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = $mailer['host'];
$mail->SMTPAuth = true;

$mail->Port       = $mailer['port']; 
$mail->Username   = $mailer['username']; 
$mail->Password   = $mailer['password'];;
$mail->SMTPSecure = 'tls';

$mail->setFrom('no-reply@rocksolidbbs.com', 'no-reply');
$mail->addAddress($user_email);

$mail->Subject = "Confirmation code for ".$_SERVER['HTTP_HOST']; 

$mycode = create_code($username);
    $msg="A request to create an account on ".$_SERVER['HTTP_HOST']." has been made using ".$user_email.".\n\nIf you did not request this, please ignore and the request will fail.\n\nThis is your account creation code: ".$mycode."\n\nNote: replies to this email address are not monitored";
$mail->Body = wordwrap($msg,70);

$mail->send();

    echo 'An email has been sent to '.$user_email.'<br />';
    echo 'Please enter the code from the email below:<br />'; 
  }    
    echo '<form name="create1" method="post" action="create.php">';
  if($CONFIG['verify_email'] === true) {
      echo '<input name="code" type="text" id="code">&nbsp;';
  }
    echo '<input name="username" type="hidden" id="username" value="'.$username.'" readonly="readonly">';
    echo '<input name="password" type="hidden" id="password" value="'.$password.'" readonly="readonly">';
    echo '<input name="user_email" type="hidden" id="user_email" value="'.$user_email.'" readonly="readonly">';
    echo '<input type="submit" name="Submit" value="Click Here to Create"></td>';
    echo '<br/><br/><a href="'.$CONFIG['default_content'].'">Cancel and return to home page</a>';
} else {
    echo "Authentication Failed\r\n";
    exit(1);
}

function make_key($username) {
    $key = openssl_random_pseudo_bytes(44);
    return base64_encode($key);
}

function create_code($username) {
  $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $code = substr(str_shuffle($permitted_chars), 0, 16);
  $userfile = sys_get_temp_dir()."/".$username;
  file_put_contents($userfile, $code);
  return $code;
}
?>
</body>
</html> 
