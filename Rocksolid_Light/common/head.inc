<html>
<head>
<title><?php echo htmlspecialchars($title); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=<?=$www_charset?>">
<?php

include "config.inc.php";
if(file_exists($config_dir.'/googleanalytics.conf')) {
  include $config_dir.'/googleanalytics.conf';
}

if (file_exists('mods/header.php')) {
  include "mods/header.php";
} else {
  include "header.php";
}

echo '<hr>';
echo '</head>';
