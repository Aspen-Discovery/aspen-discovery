#!/bin/sh

# This script is for moving a marc full export file from on the ftp server to data directory on the pika server

if [[ $# -ne 3 ]]; then
	echo "To use, add the ftp source directory for the first parameter, the data directory destination as the second parameter, and the pika server sitename as the third parameter."
	echo "$0 source destination pika_server"
	echo "eg: $0 hoopla hoopla pika.test"
else

	# Source & Destination set by command line options
	SOURCE=$1
	DESTINATION=$2
	PIKASERVER=$3

	LOG="logger -t $0"
	# tag logging with script name and command line options

	REMOTE="10.1.2.7:/ftp"
	LOCAL="/mnt/ftp"

	$LOG "~~ mount $REMOTE $LOCAL"
	mount $REMOTE $LOCAL

	if [ -d "$LOCAL/$SOURCE/" ]; then
		if [ -d "/data/vufind-plus/$DESTINATION/marc/" ]; then
			if [ $(ls -1A "$LOCAL/$SOURCE/" | grep .mrc | wc -l) -gt 0 ]; then
				# only do copy command if there are files present to move

				FILE1=$(ls -rt $LOCAL/$SOURCE/*.mrc|tail -1)
				# Get only the latest file
				if [ -n "$FILE1" ]; then
					$LOG "~~ Copy fullexport marc file(s)."
					$LOG "~~ cp --update $FILE1 /data/vufind-plus/$DESTINATION/marc/fullexport.mrc"
					cp --update "$FILE1" /data/vufind-plus/$DESTINATION/marc/fullexport.mrc

					if [ $? -ne 0 ]; then
						$LOG "~~ Copying $FILE1 file failed."
						echo "Copying $FILE1 file failed."
					else
						$LOG "~~ $FILE1 file was copied."
						echo "$FILE1 file was copied."
						if [[ ! $PIKASERVER =~ ".test" ]]; then
							# Only move marc files to processed folder for production servers
							# The test server MUST run before production or the file won't exist
							if [ ! -d "$LOCAL/$SOURCE/processed/" ]; then
								mkdir $LOCAL/$SOURCE/processed/
							fi
							echo "Moving $FILE1 on ftp server to processed directory."
							mv "$FILE1" $LOCAL/$SOURCE/processed/
						fi
					fi
#				else
#					echo "No File was found in $SOURCE"
				fi
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