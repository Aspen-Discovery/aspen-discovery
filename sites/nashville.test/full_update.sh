#!/bin/bash

# full_update.sh
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# Script handles all aspects of a full index including 
# extracting data from other systems.

# TO DO: 
#	+ add similar isProduction logic to continuous_partial_reindex.sh

# 201509xx : changes below for moving to production
# 	+ until pika is in production, galacto is considered production
#		and pika is considered test
#	+ when pika moves to production: 
#		+ change config.pwd.ini [Site][isProduction]
#			+ of galacto to false
#			+ of catalog to true
#		+ alter scp statements to refer to catalog, not galacto
#		+ ensure SSH keys are set up appropriately

# 20150818 : changes in preparation for pika moving from dev to test
#	+ check isProduction value from config.ini
#	+ eliminate checkProhibitedTimes; Pika uses a different set of 
#		Review Files than VF+ and the non-production pika
#		machine should simply scp files from production server

# 20150219 : version 1.0


# this version emails script output as a round finishes
EMAIL=james.staub@nashville.gov,Mark.Noble@nashville.gov,Pascal.Brammeier@nashville.gov
PIKASERVER=nashville.test
PIKADBNAME=vufind
OUTPUT_FILE="/var/log/pika/${PIKASERVER}/full_update_output.log"
DAYOFWEEK=$(date +"%u")

# Actual CarlX extract size 2017 07 03 - 1021325895  - pascal
MINFILE1SIZE=$((1070000000))
# below values from millennium
# JAMES set MIN 2016 11 03 actual extract size 825177201
# JAMES set MIN 2017 01 31 actual extract size 823662098
# JAMES set MIN 2017 02 01 actual extract size 817883489

# determine whether this server is production or test
CONFIG=/usr/local/VuFind-Plus/sites/${PIKASERVER}/conf/config.pwd.ini
#echo ${CONFIG}
if [ ! -f ${CONFIG} ]; then
        CONFIG=/usr/local/vufind-plus/sites/${PIKASERVER}/conf/config.pwd.ini
        #echo ${CONFIG}
        if [ ! -f ${CONFIG} ]; then
                echo "Please check spelling of site ${PIKASERVER}; conf.pwd.ini not found at $confpwd"
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
        if [[ $line =~ ^isProduction ]]; then
                PRODUCTION=$(trim "${line#*=}");
        fi
done < "${CONFIG}"

# Check for conflicting processes currently running
function checkConflictingProcesses() {
	#Check to see if the conflict exists.
	countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
	countConflictingProcesses=$((countConflictingProcesses-1))

	let numInitialConflicts=countConflictingProcesses
	#Wait until the conflict is gone.
	until ((${countConflictingProcesses} == 0)); do
		countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
		countConflictingProcesses=$((countConflictingProcesses-1))
		#echo "Count of conflicting process" $1 $countConflictingProcesses
		sleep 300
	done
	#Return the number of conflicts we found initially.
	echo ${numInitialConflicts};
}

#Check for any conflicting processes that we shouldn't do a full index during.
#Since we aren't running in a loop, check in the order they run.
checkConflictingProcesses "reindexer.jar" >> ${OUTPUT_FILE}

# truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

# Back-up Solr Master Index
mysqldump ${PIKADBNAME} grouped_work_primary_identifiers > /data/pika/${PIKASERVER}/grouped_work_primary_identifiers.sql
sleep 2m
tar -czf /data/pika/${PIKASERVER}/solr_master_backup.tar.gz /data/pika/${PIKASERVER}/solr_master/grouped/index/ /data/pika/${PIKASERVER}/grouped_work_primary_identifiers.sql >> ${OUTPUT_FILE}
rm /data/pika/${PIKASERVER}/grouped_work_primary_identifiers.sql

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#copy the export from CARL.X
expect copyCarlXExport.exp nashville.test >> ${OUTPUT_FILE}

# Extracts from sideloaded eContent; log defined in config.pwd.ini Sideload
# Problems with full_update starting late 201608: James moved sideload.sh
# initiation to crontab
# cd /usr/local/vufind-plus/vufind/cron; ./sideload.sh ${PIKASERVER}


#Extract Lexile Data
cd /data/pika/; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/pika/lexileTitles.txt https://cassini.marmot.org/lexileTitles.txt

#Extract AR Data
cd /data/pika/accelerated_reader; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/pika/accelerated_reader/RLI-ARDataTAB.txt https://cassini.marmot.org/RLI-ARDataTAB.txt

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

FILE1=$(find /data/pika/${PIKASERVER}/marc -name fullExport.mrc -mtime -1 | sort -n | tail -1)
if [ -n "$FILE1" ]
then
	FILE1SIZE=$(wc -c <"$FILE1")
	if [ $FILE1SIZE -ge $MINFILE1SIZE ]; then
		echo "Latest file is " $FILE1 >> ${OUTPUT_FILE}
		DIFF=$(($FILE1SIZE - $MINFILE1SIZE))
		PERCENTABOVE=$((100 * $DIFF / $MINFILE1SIZE))
		echo "The export file is $PERCENTABOVE (%) larger than the minimum size check." >> ${OUTPUT_FILE}

		#Validate the export
		cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}
		#Full Regroup
		cd /usr/local/vufind-plus/vufind/record_grouping;
		java -server -XX:+UseG1GC -Xmx6G -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}
		#Full Reindex
		#cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}
		cd /usr/local/vufind-plus/vufind/reindexer;
		java -server -XX:+UseG1GC -Xmx6G -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

		# Clean-up Solr Logs
		# (/usr/local/vufind-plus/sites/default/solr/jetty/logs is a symbolic link to /var/log/pika/solr)
		find /var/log/pika/solr -name "solr_log_*" -mtime +7 -delete
		find /var/log/pika/solr -name "solr_gc_log_*" -mtime +7 -delete

		#Restart Solr
		cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

		#Delete Zinio Covers
		cd /usr/local/vufind-plus/vufind/cron; ./zinioDeleteCovers.sh ${PIKASERVER}

	else
		echo $FILE1 " size " $FILE1SIZE "is less than minimum size :" $MINFILE1SIZE "." >> ${OUTPUT_FILE}
	fi

else
	echo "Did not find a CarlX export file from the last 24 hours." >> ${OUTPUT_FILE}
fi

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
	# send mail
	mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi
