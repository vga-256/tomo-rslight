#!/bin/bash

webroot="/var/www/html"
spoolpath="/var/spool/rslight"
configpath="/etc/rslight"
username="www-data"

randpw(){ < /dev/urandom tr -dc _A-Z-a-z-0-9{} | head -c${1:-16};echo;}
site_key=$(randpw)
anonymous_password=$(randpw)
local_password=$(randpw)
admin_password=$(randpw)
admin_key=$(randpw)

echo
echo "This is the main installation script for Rocksolid Light"
echo "and must be run as root from the root directory of the extracted files"
echo
echo "Select installation directories"
echo

echo "Choose a path for your web root for rslight"
read -p "Use default web root $webroot (y/n)? " default; echo
if [ "${default^^}" != "Y" ]
then
  read -p "Enter web root for rslight: " webroot; echo
fi

echo "Choose a path for your spool files for rslight"
read -p "Use default spool path $spoolpath (y/n)? " default; echo
if [ "${default^^}" != "Y" ]
then
  read -p "Enter spool path for rslight: " spoolpath; echo 
fi
echo "Choose a path for rslight configuration files"
read -p "Use default config path $configpath (y/n)? " default; echo
if [ "${default^^}" != "Y" ]
then
  read -p "Enter config path for rslight: " configpath; echo
fi

echo "Choose username used by your web server"
read -p "Use default username $username (y/n)? " default; echo
if [ "${default^^}" != "Y" ]
then
  read -p "Enter username used by your web server: " username; echo
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
read -p "Type 'YES' to create the directories and move files into place: " default; echo

if [ "$default" != "YES" ]
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
echo "done"
echo
echo -n "Moving files into place..."
cp index.php $webroot
cp -a common $webroot
cp -a rocksolid $webroot
cp -a spoolnews $webroot
cp rslight/scripts/*.php $configpath/scripts
mkdir $configpath/upgrade
cp rslight/*.php $configpath/upgrade
cp rslight/*.conf $configpath/upgrade
echo "done"
echo
echo -n "Setting permissions..."
chown $username $spoolpath
chgrp $username $spoolpath
chown $username "$configpath/users"
chgrp $username "$configpath/users"
chmod 700 "$configpath/users"
chown $username "$configpath/userconfig"
chgrp $username "$configpath/userconfig"
chmod 700 "$configpath/userconfig"
chown $username "$configpath/rslight.inc.php"
chgrp $username "$configpath/rslight.inc.php"
echo "done"

echo
echo -n "Applying configuration..."
sed -i '' -e "s|<spooldir>|$spoolpath/|" $webroot/common/config.inc.php
sed -i '' -e "s|<config_dir>|$configpath/|" $webroot/common/config.inc.php
sed -i '' -e "s|<webserver_user>|$username|" $configpath/upgrade/rslight.inc.php
sed -i '' -e "s|<site_key>|$site_key|" $configpath/upgrade/rslight.inc.php
sed -i '' -e "s|<anonymous_password>|$anonymous_password|" $configpath/upgrade/rslight.inc.php
sed -i '' -e "s|<local_password>|$local_password|" $configpath/upgrade/rslight.inc.php
sed -i '' -e "s|<admin_password>|$admin_password|" $configpath/upgrade/admin.inc.php 
sed -i '' -e "s|<admin_key>|$admin_key|" $configpath/upgrade/admin.inc.php
echo "done"
echo
echo "***************************************************"
echo "******** YOUR ADMIN PASSWORD IS: '$admin_password'"
echo "***************************************************"
echo
echo "Admin password can be changed in $configpath/upgrade/admin.inc.php"
echo
echo "All new configuration files have been placed in $configpath/upgrade."
echo
echo "Please review these files and make changes to existing files as may be necessary"
echo
echo "Upgrade complete"
