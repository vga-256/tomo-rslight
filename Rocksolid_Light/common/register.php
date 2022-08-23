<?php

include "config.inc.php";
include "head.inc";

if(!isset($_POST['command'])) {
if (isset($_COOKIE["ts_limit"])) {
  echo "It appears you already have an active account<br/>";
  echo "More than one account may not be created in 30 days<br/>";
  echo '<br/><a href="/">Return to Home Page</a>';
} else {
  echo '<table border="0" align="center" cellpadding="0" cellspacing="1">';
  echo '<tr>';
  echo '<form name="form1" method="post" action="register.php">';
  echo '<td><tr>';
  echo '<td><strong>Register Username </strong></td>';
  echo '</tr><tr>';
  echo '<td>Username:</td>';
  echo '<td><input name="username" type="text" id="username"></td>';
  echo '</tr><tr>';
  echo '<td>Email:</td>';
  echo '<td><input name="user_email" type="text" id="user_email"></td>';
  echo '</tr><tr>';
  echo '<td>Password:</td>';
  echo '<td><input name="password" type="password" id="password"></td>';
  echo '</tr><tr>';
  echo '<td>Re-enter Password:</td>';
  echo '<td><input name="password2" type="password" id="password2"></td>';
  echo '</tr><tr>';
  echo '<td><input name="command" type="hidden" id="command" value="Create" readonly="readonly"></td>';
  echo '</tr><tr>';
  echo '<td>&nbsp;</td>';
  echo '<td><input type="submit" name="Submit" value="Create"></td>';
  echo '</tr>';
  echo '<tr><td><a href="changepw.php">Change current password</a></td></tr>';
  echo '<tr><td>';
  echo '<td></td><td></td>';
  echo '</td></tr>';
  echo '</td>';
  echo '</form>';
  echo '</tr>';
  echo '</table>';
}
  echo '</body>';
  echo '</html>';
  exit(0);
}

if(isset($_POST['command']) && $_POST['command'] == 'CreateNew') {
  include $config_dir.'/synchronet.conf';
  $workpath = $config_dir."users/";
  $keypath = $config_dir."userconfig/";
  $username = $_POST['username'];
  $password = $_POST['password'];
  $user_email = $_POST['user_email'];
  $code = $_POST['code'];
  $userFilename = $workpath.$username;
  $keyFilename = $keypath.$username;
  @mkdir($workpath.'new/');
  $verified = 0;

  $no_verify=explode(' ', $CONFIG['no_verify']);
  foreach($no_verify as $no) {
    if (strlen($_SERVER['HTTP_HOST']) - strlen($no) === strrpos($_SERVER['HTTP_HOST'],$no)) {
      $CONFIG['verify_email'] = false;
    }
  }
 if($CONFIG['verify_email'] == true) {
  $saved_code = file_get_contents(sys_get_temp_dir()."/".$username);
  if((strcmp(trim($code), trim($saved_code))) !== 0) {
    echo "Code does not match. Try again.<br />";
    echo '<form name="create1" method="post" action="register.php">';
    echo '<input name="code" type="text" id="code">&nbsp;';
    echo '<input name="username" type="hidden" id="username" value="'.$username.'" readonly="readonly">';
    echo '<input name="password" type="hidden" id="password" value="'.$password.'" readonly="readonly">';
    echo '<input name="command" type="hidden" id="command" value="CreateNew" readonly="readonly">';
    echo '<input name="user_email" type="hidden" id="user_email" value="'.$user_email.'" readonly="readonly">';
    echo '<input type="submit" name="Submit" value="Click Here to Create"></td>';
    echo '<br/><br/><a href="'.$CONFIG['default_content'].'">Cancel and return to home page</a>';
    exit(2);
  }
  $verified = 1;
 }
    if ($userFileHandle = @fopen($userFilename, 'w+'))
    {
        fwrite($userFileHandle, password_hash($password, PASSWORD_DEFAULT));
        fclose($userFileHandle);
        chmod($userFilename, 0666);
    }
// Create synchronet account
    if(isset($synch_create) && $synch_create == true) {
        putenv("SBBSCTRL=$synch_path/ctrl");
        $result = shell_exec("$synch_path/exec/makeuser $username -P $password");
    }
    $newkey = make_key($username);
    if ($userFileHandle = @fopen($keyFilename, 'w+'))
    {
        fwrite($userFileHandle, 'encryptionkey:'.$newkey."\r\n");
        fwrite($userFileHandle, 'email:'.$user_email."\r\n");
        if($verified == 1) {
          fwrite($userFileHandle, "email_verified:true\r\n");
        }
        fclose($userFileHandle);
        chmod($userFilename, 0666);
    }
    unlink(sys_get_temp_dir()."/".$username);
    echo "User:".$username." Created\r\n";
    echo '<br /><a href="'.$CONFIG['default_content'].'">Back</a>';

  exit(0);
}

if($CONFIG['verify_email'] == true) {
  include($config_dir.'/phpmailer.inc.php');
  if(class_exists('PHPMailer')) {
    $mail = new PHPMailer();
  } else {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
  }
}

# $hostname: '{POPaddress:port/pop3}INBOX'
$hostname = '{mail.example.com:110/pop3}INBOX';
# $external: Using external POP auth?
$external = 0;
# $workpath: Where to cache users (must be writable by calling program)
$workpath = $config_dir."users/";
$keypath = $config_dir."userconfig/";

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
  echo '<br /><a href="register.php">Back</a>';
  exit(2);
}

if ($_POST['password'] !== $_POST['password2']) {
  echo "Your passwords entered do not match\r\n";
  echo '<br /><a href="register.php">Back</a>';
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
          echo '<br /><a href="register.php">Back</a>';
          exit(2);
	}
      }
    }
    fclose($userFileHandle);
  } 
}

if (!preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z0-9]{2,3})$^",$user_email)) {
  echo "Email must be in the form of an email address\r\n";
  echo '<br /><a href="register.php">Back</a>';
  exit(2);
}

# Does user file already exist?
if (($userFileHandle = @fopen($userFilename, 'r')) || (get_config_value('aliases.conf', strtolower($thisusername)) !== false)) 
{
    if ($command == "Create")
    {
        echo "User:".$thisusername." Already Exists\r\n";
	echo '<br /><a href="register.php">Back</a>';
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

$mail->setFrom($mailer['username'].'@'.$mailer['host'], $mailer['username']);
$mail->addAddress($user_email);

$mail->Subject = "Confirmation code for ".$_SERVER['HTTP_HOST']; 

$mycode = create_code($username);
    $msg="A request to create an account on ".$_SERVER['HTTP_HOST']." has been made using ".$user_email.".\n\nIf you did not request this, please ignore and the request will fail.\n\nThis is your account creation code: ".$mycode."\n\nNote: replies to this email address are not monitored";
$mail->Body = wordwrap($msg,70);

$mail->send();

    echo 'An email has been sent to '.$user_email.'<br />';
    echo 'Please enter the code from the email below:<br />'; 
  }    
    echo '<form name="create1" method="post" action="register.php">';
  if($CONFIG['verify_email'] == true) {
      echo '<input name="code" type="text" id="code">&nbsp;';
  }
    echo '<input name="username" type="hidden" id="username" value="'.$username.'" readonly="readonly">';
    echo '<input name="password" type="hidden" id="password" value="'.$password.'" readonly="readonly">';
    echo '<input name="command" type="hidden" id="command" value="CreateNew" readonly="readonly">';
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

function get_config_value($configfile,$request) {
  global $config_dir;
 
  if ($configFileHandle = @fopen($config_dir.'/'.$configfile, 'r'))
  {
    while (!feof($configFileHandle))
    {
      $buffer = fgets($configFileHandle);
      if(strpos($buffer, $request.':') !== FALSE) {
        $dataline=$buffer;
        fclose($configFileHandle);
        $datafound = explode(':',$dataline);
        return $datafound[1];
      }
    } 
    fclose($configFileHandle);
    return FALSE;
  } else {
    return FALSE;
  }
}
?>
