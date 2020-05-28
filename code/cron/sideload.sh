#!/bin/bash

# sideload.sh
# James Staub
# Nashville Public Library

# 20200425: changes vufind-plus to aspen-discovery
# 20160113: adds logging
# 20160105: branch off HOOPLA.sh
#	get wget commands from config.pwd.ini
# 20151006: Correct for new ALL directory name 'Only libraries loading All'
# 20150522: Grab USA_ALL_*.marc files for Comic, eBook, and Music
# 20150130: Grab Hoopla marc records for Pika. Read Hoopla ftp user and password from ... site/[site]/config/config.pwd.ini

if [[ $# -ne 1 ]]; then
	echo "Please provide site directory, e.g., ${0} opac.marmot.org"
	exit
fi

site=$1 
#echo $site
confpwd=/usr/local/aspen-discovery/sites/$site/conf/config.pwd.ini
#echo $confpwd
if [ ! -f $confpwd ]; then
	confpwd=/usr/local/aspen-discovery/sites/$site/conf/config.pwd.ini
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
	var="${var%\"}"; # remove leading quotation mark
	var="${var#\"}"; # remove trailing quotation mark
	echo -n "$var";
}

declare -A collections
section=false

while read line; do
	if [[ $line =~ ^\[Sideload\] ]]; then
		section=true;
	fi
	if [[ $line =~ ^\[.+?\] && $line != '[Sideload]' ]]; then
		section=false;
	fi
	if [[ $section == true && ! $line =~ ^\; && $line =~ 'Command' ]]; then
		# key = strip off longest string from end containing Command
                key=$(trim "${line%%Command*}");
		# value = strip off shortest string from beginning containing =
                value=$(trim "${line#*=}");
		collections+=( [$key]=$value );
	fi
	if [[ $section == true && $line =~ 'logFile' ]]; then
		# value = strip off shortest string from beginning containing =
                logFile=$(trim "${line#*=}");
	fi
done < "$confpwd"

# Truncate logFile
: > $logFile;

# Execute MARC download commands found in config.pwd.ini

for key in ${!collections[@]}; do
#	echo ${collections[${key}]}
#	eval ${collections[${key}]}
	eval ${collections[${key}]} >> $logFile;
done

exit 0

