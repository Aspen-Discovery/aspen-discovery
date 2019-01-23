#!/bin/bash
## Begin Script

## Install PEAR Packages
pear upgrade pear
pear install --onlyreqdeps DB
pear install --onlyreqdeps DB_DataObject-1.11.3
#pear install --onlyreqdeps DB_DataObject
# DB_DataObject has to be installed manually. version 1.11.3 or less; 1.11.4, 1.11.5, etc have errors and break Pika
#echo "Manually Installing DB_DataObject package version 1.11.3"
#cp -r /usr/local/vufind-plus/install/PEAR/DB/* /usr/share/pear/DB/
pear install --onlyreqdeps Log
pear install --onlyreqdeps Pager
pear install --onlyreqdeps Mail
pear install --onlyreqdeps Net_SMTP
pear install --onlyreqdeps HTTP_Request
pear install --onlyreqdeps XML_Serializer-beta
pear install --onlyreqdeps File_Marc
pear channel-discover pear.horde.org
pear channel-update pear.horde.org
pear install Horde/Horde_Yaml-beta


#not installing; These don't look to be used by Pika any longer. pascal 6-24-2016

#pear install --onlyreqdeps Structures_DataGrid-beta
#pear install --onlyreqdeps Structures_DataGrid_DataSource_DataObject-beta
#pear install --onlyreqdeps Structures_DataGrid_DataSource_Array-beta
#pear install --onlyreqdeps Structures_DataGrid_Renderer_HTMLTable-beta
#pear install --onlyreqdeps HTTP_Client
#pear install --onlyreqdeps Mail_Mime
#pear install --onlyreqdeps Console_ProgressBar-beta
