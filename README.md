Installing Rocksolid Light (rslight) - a web based news client

Visit https://www.novabbs.com to try Rocksolid Light

![ScreenShot](https://www.novabbs.com/images/rslight-480.png)

rslight is based on NewsPortal, which discontinued development in 2008, and was 
developed by Florian Amrhein https://florian-amrhein.de/newsportal/

rslight contains some major code and feature changes, but would not exist 
without NewsPortal as a basis for development.

Requirements:

You will need a web server: rslight has been tested with apache2, lighttpd, nginx
and synchronet web servers

php is required, and your web server must be configured to serve .php. 

php-mbstring (to support other character sets), sharutils (for uudecode) and 
openssl are required. 
phpmailer is required if email confirmation is to be used.
These are the names for Debian packages. Other distributions should 
also provide these in some way.

If you get errors, check your log files to see what packages I've failed to mention.

For FreeBSD:
pkg install php72
pkg install php72-extensions
pkg install sharutils
pkg install php72-pcntl
pkg install php72-sockets
pkg install php72-mbstring
pkg install php72-openssl

Installation: 

1. Set up your webserver to handle php files

2. Extract rslight into a temporary location

3. Run the provided install script (debian-install.sh or freebsd-install.sh) as root
and answer the prompts. This will configure locations, create directories and move files
into place.

4. Edit configuration file

Load common/setup.php in your browser to configure your site. The admin password
was displayed during install but can also be found in your config directory in
admin.inc.php

5. Add a cron job for the root user. Change the directories in this line to match your setup
as shown in the installation script. Set the minutes as you wish. The paths must match your
installation:

*/5 * * * * cd /usr/local/www/html/spoolnews ; bash -lc "php /etc/rslight/scripts/cron.php"
This will start the nntp server, then drop privileges to your web user and begin pulling
articles from the remote server to create your spool. You won't see articles immediately
in rslight, please wait 15-30 minutes to begin to see articles appear.

If you have trouble, post to rocksolid.nodes.help and we'll try to help.

Retro Guy
retroguy@novabbs.com
