<?php

/* Version */
$tomobbs_version = "0.1.0"; // a fork of rslight 0.8.3

/* Location of configuration and spool */
$config_dir = "/usr/local/etc/tomo/";
$spooldir = "/usr/local/var/spool/tomo";

/* the bbsroot_dir is where all php files were installed to 
  which is a PARENT of the web server's root directory */
$bbsroot_dir = __DIR__ . "/../..";

/* Location of admin tools and its libraries */
$admintools_dir = $bbsroot_dir . "/admintools";
$nocem_file = $admintools_dir . "/nocem.php";
$delete_lib = $admintools_dir . "/lib/delete.inc.php"; 
$importdb3_file = $admintools_dir . "/import-db3.php";

/* Location of the common dir and its libraries */
$common_dir = __DIR__;
$common_lib_dir = $common_dir . "/lib";

/* authorization includes */
$auth_file = $common_dir . "/auth.inc.php";
$auth_lib = $common_lib_dir . "/userauth.inc.php";


if(isset($config_name) && file_exists($config_dir.$config_name.'.inc.php')) {
  $config_file = $config_dir.$config_name.'.inc.php';
} else {
  $config_file = $config_dir.'rslight.inc.php';
}
/* Include main config file for rslight */
$CONFIG = include_once $config_file;

$title = $CONFIG['title_full'];

if(!file_exists($config_dir.'/DEBUG')) {
  ini_set('error_reporting', E_ERROR );
}
?>
