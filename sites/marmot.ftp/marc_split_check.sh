#!/bin/bash

EMAIL=root@ganymede.marmot.org
PIKASERVER=marmot.ftp
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/marc_split_check_output.log"


#truncate the file
: > $OUTPUT_FILE;

if test "`find /data/vufind-plus/${PIKASERVER}/split_marc/ -name "*.mrc" -mtime +1`"; then
	echo "Split Marc files older than a day found in split_marc folder." >> ${OUTPUT_FILE}
	echo "" >> ${OUTPUT_FILE}
	find /data/vufind-plus/${PIKASERVER}/split_marc/ -name "*.mrc" -mtime +1 >> ${OUTPUT_FILE}
fi

# add any logic wanted for when to send the emails here. (eg errors only)
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
		# send mail
		mail -s "Marc Split Check - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

