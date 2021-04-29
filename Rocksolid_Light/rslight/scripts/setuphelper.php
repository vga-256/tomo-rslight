<?php
# To use a modified config for other 'sections', copy
# this ENTIRE file to a new file in this directory
# named as the section name followed by .inc.php
# So for a section named 'rocksolid', it's rocksolid.inc.php

return [ 
# REMOTE server configuration
'remote_server' => 'The remote news server you connect to for syncing', 
'remote_port' => 'Remote server port', 
'remote_ssl' => 'Enable if connecting to remote server using ssl (1=true, blank=false)', 
'remote_auth_user' => 'Username to authenticate to remote server',
'remote_auth_pass' => 'Password to authenticate to remote server', 
'socks_host' => 'ip address of your socks4a server (use this for tor)',
'socks_port' => 'port for your socks4a server',

# LOCAL server configuration
'enable_nntp' => 'Enable local nntp server (1=true, blank=false)', 
'local_server' => 'Local server ip address', 
'local_port' => 'Local server port', 
'local_ssl_port' => 'Local server ssl port or blank for no ssl', 
'enable_all_networks' => 'Bind local server to all interfaces (1=true, blank=false)',
'server_auth_user' => 'The username on the local server for the forum to use (auto-created)',
'server_auth_pass' => 'The password for the local server user',

# Site configuration
'rslight_title' => 'The tagline at the top of the web page',
'title_full' => 'The site title in the client browser bar', 
'hide_email' => 'Truncate email addresses in From header (1=true, blank=false)',
'email_tail' => 'What to add to a username if not in the form of an email address (include @)',
'anonusername' => 'The username to use for anonymous posting (auto-created)',
'anonuserpass' => 'The password for the anonymous username',
'timezone' => 'A timezone offset from GMT (+5, -3 etc.)',
'default_content' => 'The default page to display when loading the site',
'readonly' => 'Make the site read only (1=true, blank=false)',
'anonuser' => 'Allow anonymous posting (1=true, blank=false)',
'organization' => 'What to add to outgoing message headers for Organization',
'postfooter' => 'What to add to the bottom of posted messages (blank for nothing)',
'synchronet' => 'Enable if your remote server is a Synchronet server (1=true, blank=false)',
'rate_limit' => 'Limit each user to xx posts per hour (number or blank to disable)',
'auto_create' => 'Auto create accounts when first used to post (1=true, blank=false)',
'verify_email' => 'Require new users to verify by email, requires phpmailer (1=true, blank=false)',
'no_verify' => 'Domains that do not require email verification (space separated)',
'auto_return' => 'Return to group automatically after posting (1=true, blank=false)',
'overboard_noshow' => 'Do not show these groups in overboard (space sparated)',

# Spamassassin
'spamassassin' => 'Enable checking messages using local spamassassin install (1=true, blank=false)',
'spamc' => 'The path to spamc, or just spamc if it is already in your path',
'spamgroup' => 'What newsgroup to move messages to that are considered spam (by spamassassin)',

# Executables on your system 
'php_exec' => 'The path to php, or just php if it is already in your path',
'tac' => 'Path to php session files (leave empty to not display number of users online)',
'webserver_user' => 'The user that your webserver runs as',

# NOCEM
'enable_nocem' => 'Enable acting on nocem messages, requires gnupg (1=true, blank=false)',
'nocem_groups' => 'The list of groups to monitor for nocem messages (space separated)',

# Misc
'open_clients' => 'Space separated list of ip addresses of clients allowed to post without authenticating',
'article_database' => 'Enable storing articles in database files (1=database, blank=tradspool)',
'expire_days' => 'Posts should be expired after how many days (zero for never)',
'pathhost' => 'The pathhost to use in your XRef header. (maybe a one word name for your site)',
'thissitekey' => 'An approximately 16 character random key (numbers, letters, else) specific for your site'
];
?>
