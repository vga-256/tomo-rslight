<?php
# Server info and credentials for sending email
# (sending mail requires PHPMailer package installed)
# PHPMailer settings (comment out the versions that do not apply)

# PHPMailer 6.0 and above:
#$phpmailer['phpmailer'] = '/usr/share/php/libphp-phpmailer/src/PHPMailer.php';
#$phpmailer['smtp'] = '/usr/share/php/libphp-phpmailer/src/SMTP.php';

# PHPMailer pre 6.0
#$phpmailer['phpmailer'] = '/usr/share/php/libphp-phpmailer/class.phpmailer.php';
#$phpmailer['smtp'] = '/usr/share/php/libphp-phpmailer/class.smtp.php';

# FreeBSD 12
$phpmailer['phpmailer'] = '/usr/local/share/phpmailer/class.phpmailer.php';
$phpmailer['smtp'] = '/usr/local/share/phpmailer/class.smtp.php';

$mailer = array();
$mailer['host'] = "mail.example.com";
$mailer['port'] = "587";
$mailer['username'] = "username";
$mailer['password'] = "password";

require $phpmailer['phpmailer'];
require $phpmailer['smtp'];
?>
