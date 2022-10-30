<?php
# Server info and credentials for sending email
# (sending mail requires PHPMailer package installed)

  $phpmailer['phpmailer'] = '/usr/share/php/libphp-phpmailer/src/PHPMailer.php';
  $phpmailer['smtp'] = '/usr/share/php/libphp-phpmailer/src/SMTP.php';
  $phpmailer['exception'] = '/usr/share/php/libphp-phpmailer/src/Exception.php';

$mailer = array();
$mailer['host'] = "mail.example.com";
$mailer['port'] = "587";
$mailer['username'] = "username";
$mailer['password'] = "password";

require $phpmailer['phpmailer'];
require $phpmailer['smtp'];
?>
