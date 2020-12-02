<?php
//  setcookie("ts_limit",4096,time()+(3600*24*30),"/");
  include "config.inc.php";
  include "head.inc";
  $CONFIG = include($config_file);
  $workpath = $config_dir."users/";
  $keypath = $config_dir."userconfig/";
  $username = $_POST['username'];
  $password = $_POST['password'];
  $user_email = $_POST['user_email'];
  $code = $_POST['code'];
  $userFilename = $workpath.$username;
  $keyFilename = $keypath.$username;
  @mkdir($workpath.'new/');
  $tmpFilename = $workpath."new/".$username;
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
    echo '<form name="create1" method="post" action="create.php">';
    echo '<input name="code" type="text" id="code">&nbsp;';
    echo '<input name="username" type="hidden" id="username" value="'.$username.'" readonly="readonly">';
    echo '<input name="password" type="hidden" id="password" value="'.$password.'" readonly="readonly">';
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
    if(isset($synch_create) && $synch_create === true) {
        $result = shell_exec('/sbbs/rscreate/createbbsuser '.$username.' '.$password);
    }
    if ($userFileHandle = @fopen($tmpFilename, 'w+'))
    {
        fwrite($userFileHandle, $password);
        fclose($userFileHandle);
        chmod($tmpFilename, 0666);
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

function make_key($username) {
    $key = openssl_random_pseudo_bytes(44);
    return base64_encode($key);
}
?>
