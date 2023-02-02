#!/bin/bash
#
# backup
#
#  create a tar ball on the backup server
#
#  Author: Steve Lindemann, Mark Noble
#    Date: 18 May 2006
#
#-------------------------------------------------------------------------
# 18 May 06 - v1.0 - sml - create initial script
# 02 Aug 07 - v1.1 - sml - chg to use nfs mount to backup server
# 03 Aug 07 - v1.2 - sml - chg extension from tar.gz to .tgz
# 10 Aug 07 - v2.0 - sml - updated script to be consistent with others
# 08 Oct 08 - v2.1 - sml - update to log backup
# 08 Apr 10 - v2.2 - sml - update to use exclude feature
# 02 Dec 10 - v2.3 - sml - dump each mysql db seperately
# 02 Feb 11 - v2.4 - sml - add error code check after tar
# 24 Feb 12 - v2.4.1 - sml - add econtent getsmart pmda databases
# 08 Mar 12 - v2.4.2 - sml - added chmod to database dump
# 05 Apr 12 - v2.4.3 - sml - removed getsmart pmda databases
# 25 Jul 12 - v2.4.4 - sml - move backup.log to /var/log
# 16 May 13 - v2.4.5 - sml - add -E to mysqldump
# 11 Dec 13 - v2.4.6 - sml - rewrote to use logger command
# 13 Feb 14 - v2.4.7 - sml - updated mysql backup to better handle innodb
# 24 Nov 14 - v2.5.0 - sml - changed to backup pretty much everything
# 02 Feb 20 - v3.0 - mdn - change to work for Aspen, change file name for easier imports
#-------------------------------------------------------------------------

if [[ $# -eq 0 ]]; then
	echo "Please specify the Aspen Discovery instance"
	echo "eg: $0 model.production"
	echo "If the main Aspen Discovery database is not named 'aspen', please specify the schema name as well"
	echo "eg: $0 model.production aspen"
else
ASPENSERVER=$1

DBNAME=${2:-aspen}
echo "Dumping $DBNAME database"

#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------

#HOST=`hostname -s`
DATE=`date +%y%m%d`
LOG="logger -t $0 -p local5.notice "

DUMPFOLDER="/data/aspen-discovery/${ASPENSERVER}/sql_backup"
if [ ! -e "$DUMPFOLDER" ]
then
  mkdir $DUMPFOLDER
fi
echo "Dumping to $DUMPFOLDER"

DATABASES="$DBNAME"

DEF=/etc/my.cnf
if [ ! -e "$DEF" ]
then
  DEF=/etc/mysql/mariadb.conf.d/60-aspen.cnf
  if [ ! -e "$DEF" ]
  then
    echo "Defaults file not found!"
    exit 1
  fi
fi
DUMPOPT1="--defaults-file=$DEF --events"
DUMPOPT2="--defaults-file=$DEF --events --single-transaction"

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------
$LOG " "
$LOG ">> Backup starting <<"

#-------------------------------------------------------------
#--- backup mysql --------------------------------------------
#-------------------------------------------------------------
$LOG "~> dumping mysql database"
mysqldump $DUMPOPT1 mysql > $DUMPFOLDER/mysql.$DATE.sql
$LOG "~> exit code $?"
$LOG "~> change permissions on dump file"
chmod 400 $DUMPFOLDER/mysql.$DATE.sql
$LOG "~> exit code $?"
#---
for DB in $DATABASES
do
  $LOG "~> dumping $DB database"
  mysqldump $DUMPOPT2 --ignore-table=$DB.cached_values $DB > $DUMPFOLDER/$DB.$DATE.sql
  $LOG "~> exit code $?"
  $LOG "~> change permissions on dump file"
  chmod 400 $DUMPFOLDER/$DB.$DATE.sql
  $LOG "~> exit code $?"
  $LOG "~> compressing dump file"
  gzip -f $DUMPFOLDER/$DB.$DATE.sql
  $LOG "~> exit code $?"
done

# Delete dump files older than 3 days
# $DUMPFOLDER/$DB.$DATE.sql
#uncompressed files
  $LOG "~> deleting dump files older than three days"

find $DUMPFOLDER/ -mindepth 1 -maxdepth 1 -name *.sql -type f -mtime +3 -delete
  $LOG "~> exit code $?"
#compressed files
find $DUMPFOLDER/ -mindepth 1 -maxdepth 1 -name *.sql.gz -type f -mtime +3 -delete
  $LOG "~> exit code $?"


$LOG ">> Backup complete <<"
exit 0
fi
#-------------------------------------------------------------------------
#--- eof ---
