<?php

/* Version */
$rslight_version = "<version>";

/* Location of configuration and spool */
$config_dir = "<config_dir>";
$spooldir = "<spooldir>";

if(isset($config_name) && file_exists($config_dir.$config_name.'.inc.php')) {
  $config_file = $config_dir.$config_name.'.inc.php';
} else {
  $config_file = $config_dir.'rslight.inc.php';
}
/* Include main config file for rslight */
$CONFIG = include $config_file;

$title = $CONFIG['title_full'];

if(!file_exists($config_dir.'/DEBUG')) {
  ini_set('error_reporting', E_ERROR );
}
?>
