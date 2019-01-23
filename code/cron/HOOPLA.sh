#!/bin/bash

# HOOPLA.sh
# James Staub
# Nashville Public Library

# 20151006: Correct for new ALL directory name 'Only libraries loading All'
# 20150522: Grab USA_ALL_*.marc files for Comic, eBook, and Music
# 20150130: Grab Hoopla marc records for Pika. Read Hoopla ftp user and password from ... site/[site]/config/config.pwd.ini

# TO DO
# 1. For efficiency sake, we could read [site]/conf/config.ini [Hoopla]
#	to determine which files to grab. For now, though, we'll be lazy 
#	and grab everything

if [[ $# -ne 1 ]]; then
	echo "Please provide site directory, e.g., ./HOOPLA.sh opac.marmot.org"
	exit
fi

site=$1 
#echo $site
confpwd=/usr/local/VuFind-Plus/sites/$site/conf/config.pwd.ini
#echo $confpwd
if [ ! -f $confpwd ]; then
	confpwd=/usr/local/vufind-plus/sites/$site/conf/config.pwd.ini
	#echo $confpwd
	if [ ! -f $confpwd ]; then
		echo "Please check spelling of site $site; conf.pwd.ini not found at $confpwd"
		exit
	fi
fi

function trim()
{
    local var=$1;
    var="${var#"${var%%[![:space:]]*}"}";   # remove leading whitespace characters
    var="${var%"${var##*[![:space:]]}"}";   # remove trailing whitespace characters
    echo -n "$var";
}

while read line; do
	if [[ $line =~ ^HooplaFtpUser ]]; then
		HooplaFtpUser=$(trim "${line#*=}");
	fi
	if [[ $line =~ ^HooplaFtpPassword ]]; then
		HooplaFtpPassword=$(trim "${line#*=}");
	fi
done < "$confpwd"
#echo $HooplaFtpUser
#echo $HooplaFtpPassword
if [ -z $HooplaFtpUser ]; then
	echo "HooplaFtpUser not found in $confpwd"
	exit
fi
if [ -z $HooplaFtpPassword ]; then
	echo "HooplaFtpPassword not found in $confpwd"
	exit
fi

cd /data/vufind-plus/hoopla/marc
#wget -N --user=$HooplaFtpUser --password=$HooplaFtpPassword ftp://ftp.midwesttapes.com/*_removed.mrc; # test
#wget -N -q --user=$HooplaFtpUser --password=$HooplaFtpPassword ftp://ftp.midwesttapes.com/USA_*.mrc
wget -N -q --user=$HooplaFtpUser --password=$HooplaFtpPassword 'ftp://ftp.midwesttapes.com/Only libraries loading All/USA_ALL_*.mrc'

# Check that the Hoopla Marc is updating monthly
OLDHOOPLA=$(find /data/vufind-plus/hoopla/marc/ -name "*.mrc" -mtime +30)
if [ -n "$OLDHOOPLA" ]
then
	echo "There are Hoopla Marc files older than 30 days : "
	echo "$OLDHOOPLA"
fi
