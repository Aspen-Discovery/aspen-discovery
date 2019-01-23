#!/bin/bash
# Script executes continuous re-indexing.
#
# this version emails script output as a round finishes
EMAIL=root@mercuryvm
PIKASERVER=marmot.production
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/extract_and_reindex_output.log"

# Check for conflicting processes currently running
function checkConflictingProcesses() {
	#Check to see if the conflict exists.
	countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
	#subtract one to get rid of our grep command
	countConflictingProcesses=$((countConflictingProcesses-1))

	let numInitialConflicts=countConflictingProcesses
	#Wait until the conflict is gone.
	until ((${countConflictingProcesses} == 0)); do
		countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
		#subtract one to get rid of our grep command
		countConflictingProcesses=$((countConflictingProcesses-1))
		#echo "Count of conflicting process" $1 $countConflictingProcesses
		sleep 300
	done
	#Return the number of conflicts we found initially.
	echo ${numInitialConflicts};
}

# Prohibited time ranges - for, e.g., ILS backup
function checkProhibitedTimes() {
	start=$(date --date=$1 +%s)
	stop=$(date --date=$2 +%s)
	NOW=$(date +%H:%M:%S)
	NOW=$(date --date=$NOW +%s)

	let hasConflicts=0
	if (( $start < $stop ))
	then
		if (( $NOW > $start && $NOW < $stop ))
		then
			#echo "Sleeping:" $(($stop - $NOW))
			sleep $(($stop - $NOW))
			hasConflicts = 1
		fi
	elif (( $start > $stop ))
	then
		if (( $NOW < $stop ))
		then
			sleep $(($stop - $NOW))
			hasConflicts = 1
		elif (( $NOW > $start ))
		then
			sleep $(($stop + 86400 - $NOW))
			hasConflicts = 1
		fi
	fi
	echo ${hasConflicts};
}

while true
do
	#####
	# Check to make sure this is a good time to run.
	#####

	# Make sure we are not running a Full Record Group/Reindex process
	hasConflicts=$(checkConflictingProcesses "full_update.sh")
	#If we did get a conflict, restart the loop to make sure that all tests run
	if (($? != 0)); then
		continue
	fi

	# Do not run while the export from Sierra is running to prevent inconsistencies with MARC records
	# export starts at 10 pm the file is copied to the FTP server at about 11:40
	hasConflicts=$(checkProhibitedTimes "21:50" "23:59")
	#If we did get a conflict, restart the loop to make sure that all tests run
	if (($? != 0)); then
		continue
	fi

	# Full Update should be going in this time period
	hasConflicts=$(checkProhibitedTimes "00:00" "01:50")
	#If we did get a conflict, restart the loop to make sure that all tests run
	if (($? != 0)); then
		continue
	fi


	#####
	# Start of the actual indexing code
	#####

	#truncate the file
	: > $OUTPUT_FILE;

	#echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
	# reset the output file each round
	
	#export from sierra
	#echo "Starting Sierra Export - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/sierra_export/;
	nice -n -10 java -server -XX:+UseG1GC -jar sierra_export.jar ${PIKASERVER} >> ${OUTPUT_FILE}
	
	#export from overdrive
	#echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/;
	nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar ${PIKASERVER} >> ${OUTPUT_FILE}
	
	#run reindex
	#echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/reindexer;
	nice -n -5 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} >> ${OUTPUT_FILE}

	# add any logic wanted for when to send the emails here. (eg errors only)
	FILESIZE=$(stat -c%s ${OUTPUT_FILE})
	if [[ ${FILESIZE} > 0 ]]
	then
		# send mail
		mail -s "Continuous Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
	fi
done


