#!/bin/bash
#
# backup
#
#  create a tar ball on the backup server
#
#  Author: Steve Lindemann
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
#-------------------------------------------------------------------------

if [[ $# -eq 0 ]]; then
	echo "Please specify the Pika instance"
	echo "eg: $0 marmot.production"
	echo "If the main Pika database is not named 'pika', please specify the schema name as well"
		echo "eg: $0 marmot.production vufind"
else
PIKASERVER=$1

if [[ $# -eq 2 ]]; then
	DBNAME=$2
else
	DBNAME="pika"
fi
echo "Dumping $DBNAME database"

#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------

#HOST=`hostname -s`
DATE=`date +%y%m%d`
LOG="logger -t $0 -p local5.notice "

DUMPFOLDER="/data/vufind-plus/${PIKASERVER}/sql_backup"
if [ ! -e "$DUMPFOLDER" ]
then
	DUMPFOLDER="/data/pika/${PIKASERVER}/sql_backup"
fi
echo "Dumping to $DUMPFOLDER"

#REMOTE="10.1.2.2:/home/backup/venus"
#LOCAL="/mnt/backup"
#FILES="/data/vufind-plus/marmot.test/solr_searcher /data/vufind-plus/marmot.test/econtent  /var /home /etc /root /usr /bin /boot /lib /lib64 /opt /sbin"
#TAROPT="-X /root/cron/exclude.txt --exclude-caches --ignore-failed-read --absolute-names"

DATABASES="$DBNAME econtent"
DUMPOPT1="-u root --events"
DUMPOPT2="-u root --events --single-transaction"

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------
$LOG " "
$LOG ">> Backup starting <<"

#-------------------------------------------------------------
#--- backup mysql --------------------------------------------
#-------------------------------------------------------------
#$LOG "~> purge yesterdays mysql dumps"
#/bin/rm -f $DUMPFOLDER/*
#$LOG "~> exit code $?"
#---
$LOG "~> dumping mysql database"
mysqldump $DUMPOPT1 mysql > $DUMPFOLDER/mysql.$DATE.mysql.dump
$LOG "~> exit code $?"
$LOG "~> change permissions on dump file"
chmod 400 $DUMPFOLDER/mysql.$DATE.mysql.dump
$LOG "~> exit code $?"
#---
for DB in $DATABASES
do
  $LOG "~> dumping $DB database"
  mysqldump $DUMPOPT2 $DB > $DUMPFOLDER/$DB.$DATE.mysql.dump
  $LOG "~> exit code $?"
  $LOG "~> change permissions on dump file"
  chmod 400 $DUMPFOLDER/$DB.$DATE.mysql.dump
  $LOG "~> exit code $?"
  $LOG "~> compressing dump file"
  gzip $DUMPFOLDER/$DB.$DATE.mysql.dump
  $LOG "~> exit code $?"
done

#-------------------------------------------------------------
#--- make tarball --------------------------------------------
#-------------------------------------------------------------
#$LOG "~> mounting $REMOTE to $LOCAL"
#mount $REMOTE $LOCAL
#EXITCODE=$?
#$LOG "~> exit code $EXITCODE"
#if [ $EXITCODE -ne 0 ];then
#  $LOG "!! script terminated abnormally"
#  exit 1
#else
#  $LOG "~> backup filesystem starting"
#  tar -czf $LOCAL/backup_$HOST.$DATE.tgz $TAROPT $FILES
#  $LOG "~> exit code $?"
#  $LOG "~> changing permissions to read-only on backup file"
#  chmod 400 $LOCAL/backup_$HOST.$DATE.tgz
#  $LOG "~> exit code $?"
#  $LOG "~> unmounting $LOCAL"
#  umount $LOCAL
#  EXITCODE=$?
#  $LOG "~> exit code $EXITCODE"
#  if [ $EXITCODE -ne 0 ];then
#    $LOG "!! script terminated abnormally"
#    $LOG "!! $LOCAL needs UNMOUNTED BEFORE the next backup"
#    exit 2
#  fi
#fi
#-------------------------------------------------------------

# Delete dump files older than 3 days
# $DUMPFOLDER/$DB.$DATE.mysql.dump
#uncompressed files
  $LOG "~> deleting dump files older than three days"

find $DUMPFOLDER/ -mindepth 1 -maxdepth 1 -name *.mysql.dump -type f -mtime +3 -delete
  $LOG "~> exit code $?"
#compressed files
find $DUMPFOLDER/ -mindepth 1 -maxdepth 1 -name *.mysql.dump.gz -type f -mtime +3 -delete
  $LOG "~> exit code $?"


$LOG ">> Backup complete <<"
exit 0
fi
#-------------------------------------------------------------------------
#--- eof ---
