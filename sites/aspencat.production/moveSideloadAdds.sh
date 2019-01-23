#!/bin/sh

# This script is for moving marc files that are adds or deletes from on the ftp server to data directory on the pika server

if [[ $# -ne 2 && $# -ne 3 ]]; then
	echo "To use, add the ftp source directory for the first parameter, the data directory destination as the second parameter, optional third parameter -n to use new ftp server."
	echo "$0 source destination"
	echo "eg: $0 hoopla hoopla"
else

	# Source & Destination set by command line options
	SOURCE=$1
	DESTINATION=$2

	LOG="logger -t $0"
	# tag logging with script name and command line options

	REMOTE="10.1.2.7:/ftp"
	LOCAL="/mnt/ftp"

	$LOG "~~ mount $REMOTE $LOCAL"
	mount $REMOTE $LOCAL

	if [ -d "$LOCAL/$SOURCE/" ]; then
		if [ -d "/data/vufind-plus/$DESTINATION/marc/" ]; then
			if [ $(ls -1A "$LOCAL/$SOURCE/" | grep .mrc | wc -l) -gt 0 ] ; then
			# only do copy command if there are files present to move

				$LOG "~~ Copy sideload adds/deletes marc file(s)."
				$LOG "~~ cp $LOCAL/$SOURCE/*.mrc /data/vufind-plus/$DESTINATION/marc/"
				cp $LOCAL/$SOURCE/*.mrc /data/vufind-plus/$DESTINATION/marc/

				if [ $? -ne 0 ]; then
					$LOG "~~ Copying $SOURCE marc files failed."
					echo "Copying $SOURCE marc files failed."
				else
					$LOG "~~ $SOURCE marc files were copied."
					echo "$SOURCE marc files were copied."
					if [ ! -d "$LOCAL/$SOURCE/processed/" ]; then
						mkdir $LOCAL/$SOURCE/processed/
					fi
					echo "Moving files on ftp server to processed directory."
					mv $LOCAL/$SOURCE/*.mrc $LOCAL/$SOURCE/processed/
				fi

#				TODO: Implement Old Marc File check. Need to change initial parameter test
#				if [[ -z $3 ]]; then
#					# Check that the Marc File(s) are newer than $3 days
#					OLDMARC=$(find /data/vufind-plus/$DESTINATION/marc/ -name "*.mrc" -mtime +$3)
#					if [ -n "$OLDMARC" ]; then
#						echo "There are Marc files older than $3 days : "
#						echo "$OLDMARC"
#					fi
#				fi
			fi
		else
			echo "Path /data/vufind-plus/$DESTINATION/marc/ doesn't exist."
		fi

	else
		echo "Path $LOCAL/$SOURCE/ doesn't exist."
	fi

	# Make sure we undo the mount every time it is mounted in the first place.
	$LOG "~~ umount $LOCAL"
	umount $LOCAL

	$LOG "Finished $0 $*"

fi