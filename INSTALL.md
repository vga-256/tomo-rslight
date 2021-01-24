Installing Rocksolid Light (rslight) - a web based Usenet news client

Requirements:

You will need a web server: rslight has been tested with apache2, lighttpd, nginx
and synchronet web servers

php is required, and your web server must be configured to serve .php. 

php-mbstring (to support other character sets), sharutils (for uudecode) and 
openssl are required. 
php7.x-sqlite3 (php must support PDO, probably 5.1+ but all testing is on 7.x).
phpmailer is required if email confirmation is to be used.
These are the names for Debian packages. Other distributions should 
also provide these in some way.

If you get errors, check your log files to see what packages I've failed to mention.

For FreeBSD12: php72, php72-extensions, php72-pdo_sqlite-7.2, sharutils, php72-pcntl, php72-sockets, php72-mbstring, php72-openssl
Optional: phpmailer, gnupg

Installation: 

1. Set up your webserver to handle php files

2. Extract rslight into a temporary location

3. Run the provided install script (debian-install.sh or freebsd-install.sh) as root
and answer the prompts. This will configure locations, create directories and move files
into place.

4. Load common/setup.php in your browser to configure your site. The admin password
was displayed during install but can also be found in your config directory in
admin.inc.php

5. Add a cron job for the root user. Change the directories in this line to match your setup
as shown in the installation script. Set the minutes as you wish. The paths must match your
installation:

*/5 * * * * cd /usr/local/www/html/spoolnews ; bash -lc "php /etc/rslight/scripts/cron.php"
This will start the nntp server, then drop privileges to your web user and begin pulling
articles from the remote server to create your spool. You won't see articles immediately
in rslight, please wait 15-30 minutes to begin to see articles appear.

Upgrading:

1. Disable cron job and kill any running nntp servers (ps aux | grep nntp)

2. BACKUP spool dir. Default example: 'cd /var/spool' then 'tar zcvf rslight.tgz rslight/*'

3. Run upgrade script and answer prompts

4. Compare upgrade config files in config dir /upgrade and change your exsiting config as necessary

5. If your current version is pre-0.6.8, you must pull articles again from your upstream server to set up.
Please post in a rocksolid.* group (rocksolid.nodes.help) and we can walk you through this. It's pretty
simple.

6. Enable cron job and check that everything works after it runs the first time

If you have trouble, post to rocksolid.nodes.help and we'll try to help.

Retro Guy
retroguy@novabbs.com
