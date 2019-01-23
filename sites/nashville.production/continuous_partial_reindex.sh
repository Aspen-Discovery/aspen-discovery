#!/bin/bash
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# Script executes continuous re-indexing.
# For Pika discovery partners using Millennium 2014 2.0.0_15

# TO DO:
#
# 20150818 : changes in preparation for pika moving from dev to test
#       + eliminate checkProhibitedTimes; was not being used in continuous
# 20150218 : version 1.0

# CONFIGURATION
# PLEASE SET CONFLICTING PROCESSES AND PROHIBITED TIMES IN FUNCTION CALLS IN SCRIPT MAIN DO LOOP
# this version emails script output as a round finishes
EMAIL=James.Staub@nashville.gov,Mark.Noble@nashville.gov,Pascal.Brammeier@nashville.gov
PIKASERVER=nashville.production
OUTPUT_FILE="/var/log/pika/${PIKASERVER}/continuous_partial_reindex_output.log"

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

while true 
do

	# Make sure we are not running a Full Record Group/Reindex process
	hasConflicts=$(checkConflictingProcesses "full_update.sh")
	#If we did get a conflict, restart the loop to make sure that all tests run
	if (($hasConflicts != 0)); then
		continue
	fi

	#####
	# Start of the actual indexing code
	#####

	#truncate the file
	: > $OUTPUT_FILE;

	#echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
	# reset the output file each round

	#export from overdrive
	#echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar ${PIKASERVER} >> ${OUTPUT_FILE}

	#process the export from CARL.X to give Pika what it needs
	#echo "Starting Millennium Export - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/carlx_export/
	nice -n -10 java -server -XX:+UseG1GC -jar carlx_export.jar ${PIKASERVER} >> ${OUTPUT_FILE}

	#run reindex
	#echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/reindexer
	nice -n -5 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} >> ${OUTPUT_FILE}

	# add any logic wanted for when to send the emails here. (eg errors only)
	FILESIZE=$(stat -c%s ${OUTPUT_FILE})
	if [[ ${FILESIZE} > 0 ]]
	then
			# send mail
			mail -s "Continuous Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
	fi

done
