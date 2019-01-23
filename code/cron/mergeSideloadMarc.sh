#!/usr/bin/env bash

if [[ $# -ne 1 ]]; then
	echo "To use, add the side load collection data directory for the first parameter (omitting /data/vufind-plus)."
	echo "$0 data_directory"
	echo "eg: $0 lynda/vail"
else

	SIDELOADDIR="/data/vufind-plus/$1"

	LOG="logger -t $0"
	if [ -d "$SIDELOADDIR/" ]; then
		if [ -d "$SIDELOADDIR/merge/marc" ]; then
			if [ "$(ls $SIDELOADDIR/merge/marc)" ] || [ "$(ls $SIDELOADDIR/deletes/marc)" ]; then
				if [ -r "$SIDELOADDIR/mergeConfig.ini" ]; then
					cd /usr/local/vufind-plus/vufind/marcMergeUtility
					java -jar MarcMergeUtility.jar "$SIDELOADDIR/mergeConfig.ini"
				else
					echo    "$1: Merge configuration file not readable: $SIDELOADDIR/mergeConfig.ini"
					$LOG "~~ $1: Merge configuration file not readable: $SIDELOADDIR/mergeConfig.ini"
				fi
			else
				echo    "$1: There are no files to merge"
				$LOG "~~ $1: There are no files to merge"
			fi
		else
			echo    "$1: Merge directory not found: $SIDELOADDIR/merge/marc"
			$LOG "~~ $1: Merge directory not found: $SIDELOADDIR/merge/marc"
		fi
	else
		echo    "$1: Specified directory not found: $SIDELOADDIR"
		$LOG "~~ $1: Specified directory not found: $SIDELOADDIR"
	fi
fi