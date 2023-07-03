<?php
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
  if(!isset($_POST['password'])) {
      $_POST['password'] = null;
  }
  if(!isset($_COOKIE['mail_auth'])) {
      $_COOKIE['mail_auth'] = null;
  }
  if(((get_user_mail_auth_data($_COOKIE['mail_name'])) && password_verify($_POST['username'].$keys[0].get_user_config($_POST['username'],'encryptionkey'), $_COOKIE['mail_auth'])) || (password_verify($_POST['username'].$keys[1].get_user_config($_POST['username'],'encryptionkey'), $_COOKIE['mail_auth']))) {
    $logged_in = true;
  } else {
    if(check_bbs_auth($_POST['username'], $_POST['password'])) {
      $authkey = password_hash($_POST['username'].$keys[0].get_user_config($_POST['username'],'encryptionkey'), PASSWORD_DEFAULT);
      $pkey = hash('crc32', get_user_config($_POST['username'],'encryptionkey'));
      set_user_config(strtolower($_POST['username']), "pkey", $pkey);
?>
      <script type="text/javascript">
       if (navigator.cookieEnabled)
         var authcookie = "<?php echo $authkey; ?>";
         var savename = "<?php echo stripslashes($name); ?>";
         var auth_expire = "<?php echo $auth_expire; ?>";
         var name_expire = "7776000";
         var pkey = "<?php echo $pkey; ?>";
         document.cookie = "mail_auth="+authcookie+"; max-age="+auth_expire+"; path=/";
         document.cookie = "mail_name="+savename+"; max-age="+name_expire+"; path=/";
         document.cookie = "pkey="+pkey+"; max-age="+name_expire+"; path=/";
      </script>
<?php
      $logged_in = true;
    }
    else
    {
        echo 'Authorization failed.';
    }
  }

