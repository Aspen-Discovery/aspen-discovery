#!/usr/bin/env bash
#Retrieve marc records from the FTP server
mount 10.1.2.7:/ftp/sierra /mnt/ftp
# sftp.marmot.org server

#copy production extract
cp --preserve=timestamps --update /mnt/ftp/fullexport.marc /data/vufind-plus/marmot.test/marc/fullexport.mrc
umount /mnt/ftp

