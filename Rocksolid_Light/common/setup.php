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
include "head.inc";
include ($config_dir.'/admin.inc.php');

// Accept new config
if(($_POST['configure'] == "Save Configuration") && ($_POST['configkey'] == $admin['key'])) {
  $configfile=$config_dir.'rslight.inc.php';
  $return = "<?php\n";
  $return.="return [\n";
  foreach($_POST as $key => $value) {
    if(($key !== 'configure') && ($key !== 'configkey')) {
      $value = preg_replace('/\'/', '\\\'', $value);
      $return.='  \''.$key.'\' => \''.trim($value).'\''.",\n";
    }
  }
  $return = rtrim(rtrim($return),',');
  $return.="\n];\n";
  $return.='?>';
  rename($configfile, $configfile.'.bak');
  file_put_contents($configfile, $return);
  echo '<center>';
  echo 'New Configuration settings saved in '.$configfile.'<br />';
  echo '<a href="'.$CONFIG['default_content'].'">Home</a>';
  echo '</center>';
  $CONFIG = $_POST;
  exit(0);
}

if (isset($_POST["password"]) && ($_POST["password"]==$admin["password"])) { 
  include($config_dir.'/scripts/setup.inc.php');
  exit(0);
} else{ 
//Show the wrong password notice
  if($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo '<center>';
    echo '<h2>Password Incorrect</h2>';
    echo '<a href="'.$_SERVER['PHP_SELF'].'">Retry</a>&nbsp;<a href="'.$CONFIG['default_content'].'">Home</a>';
    echo '</center>';
    exit(0);
  }
  echo '<p align="left">';
  echo '<form id ="myForm" method="post"><p align="left">';
  echo 'Enter password to access configuration: ';
  echo '<input name="password" type="password" size="25" maxlength="20"><input value="Submit" type="submit"></p>';
  echo '</form>';
 } 
?>
