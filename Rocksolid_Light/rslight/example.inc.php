<?php
/* SECTION configuration file
 * Rename this file as your section name followed
 * by .inc.php:
 * section_name.inc.php
*/

$CONFIG = include("rslight.inc.php");
return [
/*
 * To change a value, replace with your own
 * value. Place in 'single quotes':
 * 'remote_server' => 'news.example.com',
*/
  'remote_server' => $CONFIG['remote_server'],
  'remote_port' => $CONFIG['remote_port'],
  'remote_ssl' => $CONFIG['remote_ssl'],
  'socks_host' => $CONFIG['socks_host'],
  'socks_port' => $CONFIG['socks_port'],
  'remote_auth_user' => $CONFIG['remote_auth_user'],
  'remote_auth_pass' => $CONFIG['remote_auth_pass'],
  'rslight_title' => $CONFIG['rslight_title'],
  'title_full' => $CONFIG['title_full'],
  'hide_email' => $CONFIG['hide_email'],
  'email_tail' => $CONFIG['email_tail'],
  'readonly' => $CONFIG['readonly'],
  'anonuser' => $CONFIG['anonuser'],
  'postfooter' => 'Posted on Rocksolid Light',
  'synchronet' => $CONFIG['synchronet'],
  'rate_limit' => $CONFIG['rate_limit'],
  'auto_return' => $CONFIG['auto_return'],

/* The config options below are not meant to
 * be changed per section. Please leave them as
 * they are in this file.
*/
  'enable_nntp' => $CONFIG['enable_nntp'],
  'local_server' => $CONFIG['local_server'],
  'local_port' => $CONFIG['local_port'],
  'local_ssl_port' => $CONFIG['local_ssl_port'],
  'enable_all_networks' => $CONFIG['enable_all_networks'],
  'server_auth_user' => $CONFIG['server_auth_user'],
  'server_auth_pass' => $CONFIG['server_auth_pass'],
  'anonusername' => $CONFIG['anonusername'],
  'anonuserpass' => $CONFIG['anonuserpass'],
  'timezone' => $CONFIG['timezone'],
  'default_content' => $CONFIG['default_content'],
  'organization' => $CONFIG['organization'],
  'verify_email' => $CONFIG['verify_email'],
  'no_verify' => $CONFIG['no_verify'],
  'auto_create' => $CONFIG['auto_create'],
  'overboard_noshow' => $CONFIG['overboard_noshow'],
  'spamassassin' => $CONFIG['spamassassin'],
  'spamc' => $CONFIG['spamc'],
  'spamgroup' => $CONFIG['spamgroup'],
  'php_exec' => $CONFIG['php_exec'],
  'tac' => $CONFIG['tac'],
  'webserver_user' => $CONFIG['webserver_user'],
  'enable_nocem' => $CONFIG['enable_nocem'],
  'nocem_groups' => $CONFIG['nocem_groups'],
  'expire_days' => $CONFIG['expire_days'],
  'pathhost' => $CONFIG['pathhost'],
  'article_database' => $CONFIG['article_database'],
  'open_clients' => $CONFIG['open_clients'],
  'thissitekey' => $CONFIG['thissitekey']
];
?>
