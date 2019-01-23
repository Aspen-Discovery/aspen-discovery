#!/bin/sh

#Retrieve marc records from the FTP server
mount 10.1.2.7:/ftp/sierra /mnt/ftp
# sftp.marmot.org server

cp --preserve=timestamps --update /mnt/ftp/fullexport.marc /data/vufind-plus/marmot.production/marc/fullexport.mrc
umount /mnt/ftp

