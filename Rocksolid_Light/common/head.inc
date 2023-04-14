<?php 
include "config.inc.php";
echo '<html>';
echo '<head>';
echo '<title>'.htmlspecialchars($title).'</title>';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=<?=$www_charset?>">';

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
