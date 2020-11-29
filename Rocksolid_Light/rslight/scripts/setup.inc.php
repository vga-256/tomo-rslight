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
include $config_dir.'/admin.inc.php';

$configdata = include($config_dir.'/scripts/setuphelper.php');
$configfile=$config_dir.'rslight.inc.php';

echo 'Main Configuration';
echo '<table width=100% border="1" align="center" cellpadding="0" cellspacing="1">';
echo '<form name="config" method="post" action="setup.php">';
$pass = 'pass';
foreach($CONFIG as $key=>$item) {
  if($key == 'configure') {
    continue;
  }
  $guide=$configdata[$key];
  echo '<tr><td>'.$guide.':&nbsp;&nbsp;</td><td>';
    if(strpos($key, $pass)) {
      echo '<input name="'.$key.'" type="password" id="'.$key.'" value="'.htmlspecialchars($item).'" size="50"><br />';
    } else {
      echo '<input name="'.$key.'" type="text" id="'.$key.'" value="'.htmlspecialchars($item).'" size="50"><br />';
    }
  echo '</td></tr>';
}
echo '</table>';
echo '<input type="hidden" name="configkey" value="'.$admin['key'].'">';
echo '<input type="submit" name="configure" value="Save Configuration">';
echo '</form>';
?>
