<?php

/* Version */
$rslight_version = "0.7.2";

/* Location of configuration and spool */
$config_dir = "/etc/rslight/";
$spooldir = "/var/spool/rslight/";

if(isset($config_name) && file_exists($config_dir.$config_name.'.inc.php')) {
  $config_file = $config_dir.$config_name.'.inc.php';
} else {
  $config_file = $config_dir.'rslight.inc.php';
}
/* Include main config file for rslight */
$CONFIG = include $config_file;

ini_set('error_reporting', E_ERROR );
?>
