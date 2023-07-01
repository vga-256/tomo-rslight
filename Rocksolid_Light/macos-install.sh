#!/bin/bash
# ported to bash 3.x for macos by vga256

read -r version < "./version.txt"
webroot="/usr/local/www/html"
spoolpath="/usr/local/var/spool/tomo"
configpath="/usr/local/etc/tomo"
username="tomo"
groupname="nntp"

site_key=$(/usr/bin/openssl rand -base64 48 | cut -c1-16)
anonymous_password=$(/usr/bin/openssl rand -base64 48 | cut -c1-16)
local_password=$(/usr/bin/openssl rand -base64 48 | cut -c1-16)
admin_password=$(/usr/bin/openssl rand -base64 48 | cut -c1-16)
admin_key=$(/usr/bin/openssl rand -base64 48 | cut -c1-16)

echo
echo "This is the main installation script for Rocksolid Light"
echo "and must be run as root from the root directory of the extracted files"
echo
echo "Select installation directories"
echo

echo "Choose a path for your web root for tomo"
read -p "Use default web root $webroot (y/n)? " default; echo
if [ "${default}" != "y" ]
then
  read -p "Enter web root for tomo: " webroot; echo
fi

echo "Choose a path for your spool files for tomo"
read -p "Use default spool path $spoolpath (y/n)? " default; echo
if [ "${default}" != "y" ]
then
  read -p "Enter spool path for tomo: " spoolpath; echo 
fi
echo "Choose a path for tomo configuration files"
read -p "Use default config path $configpath (y/n)? " default; echo
if [ "${default}" != "y" ]
then
  read -p "Enter config path for tomo: " configpath; echo
fi

echo "Choose username used by your tomo server"
read -p "Use default username $username (y/n)? " default; echo
if [ "${default}" != "y" ]
then
  read -p "Enter username used by your web server: " username; echo
fi

echo "Choose group used by your tomo server"
read -p "Use default group $groupname (y/n)? " default; echo
if [ "${default}" != "y" ]
then
  read -p "Enter groupname used by your tomo server: " groupname; echo
fi


echo
echo "You have selected the following options:"
echo
echo "Web root: $webroot"
echo "Spool dir: $spoolpath"
echo "Config dir: $configpath"
echo "Web user: $username"
echo
echo "Are you sure you wish to install to these directories now"
echo "and change permissions as necessary to $username? "
echo
read -p "Type 'yes' to create the directories and move files into place: " default; echo

if [ "$default" != "yes" ]
then
  echo exiting...
  exit
fi

echo "Creating directories"
echo -n "$webroot..."
mkdir -p $webroot
echo "done"
echo -n "$spoolpath..."
mkdir -p $spoolpath
echo "done"
echo -n "$configpath..."
mkdir -p $configpath
mkdir -p $configpath/rss
mkdir -p $configpath/users
mkdir -p $configpath/userconfig
echo "done"
echo
echo -n "Moving files into place..."
cp index.php $webroot
cp -a common $webroot
cp -a rocksolid $webroot
cp -a spoolnews $webroot
cp -a rslight/* $configpath
echo "done"
echo
echo -n "Setting permissions..."
chown $username $webroot
chown $groupname $webroot
chown $username $webroot/index.php
chgrp $groupname $webroot/index.php
chown $username $spoolpath
chgrp $groupname $spoolpath
chown $username "$configpath/users"
chgrp $groupname "$configpath/users"
chmod 700 "$configpath/users"
chown $username "$configpath/userconfig"
chgrp $groupname "$configpath/userconfig"
chmod 700 "$configpath/userconfig"
chown $username $configpath
chgrp $groupname $configpath
chown $username "$configpath/rslight.inc.php"
chgrp $groupname "$configpath/rslight.inc.php"
echo "done"

echo
echo -n "Applying configuration..."
sed -i '' -e "s|<version>|$version|" $webroot/common/config.inc.php
sed -i '' -e "s|<spooldir>|$spoolpath/|" $webroot/common/config.inc.php
sed -i '' -e "s|<config_dir>|$configpath/|" $webroot/common/config.inc.php
sed -i '' -e "s|<webserver_user>|$username|" $configpath/rslight.inc.php
sed -i '' -e "s|<site_key>|$site_key|" $configpath/rslight.inc.php
sed -i '' -e "s|<anonymous_password>|$anonymous_password|" $configpath/rslight.inc.php
sed -i '' -e "s|<local_password>|$local_password|" $configpath/rslight.inc.php
sed -i '' -e "s|<admin_password>|$admin_password|" $configpath/admin.inc.php
sed -i '' -e "s|<admin_key>|$admin_key|" $configpath/admin.inc.php
sed -i '' -e "s|<sessions_path>|/tmp|" $configpath/rslight.inc.php
echo "done"
echo
echo "***************************************************"
echo "******** YOUR ADMIN PASSWORD IS: '$admin_password'"
echo "***************************************************"
echo
echo "Admin password can be changed in $configpath/admin.inc.php"
echo
echo "Next step is to visit your site in your browser: /common/setup.php"
echo "to complete configuration"
echo
echo Add this to crontab for root to link with your remote server, start local
echo server and manage other tasks:
echo "*/5 * * * * cd $webroot/spoolnews ; bash -lc \"php $configpath/scripts/cron.php\""
echo
echo "Once your web server is configured to point to $webroot and serve .php files"
echo "give it a try. If you have trouble, feel free to ask for help in rocksolid.nodes.help"
echo
echo "Note that it may take 10-20 minutes before groups appear on your main page"
echo "If you see files starting to appear in $spoolpath, it should be working"
echo
echo "Installation complete"
