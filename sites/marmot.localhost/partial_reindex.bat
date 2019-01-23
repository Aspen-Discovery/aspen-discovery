@echo off
REM export from sierra (items, holds, and orders)
cd c:/web/vufind-plus/vufind/sierra_export/
java -server -XX:+UseG1GC -jar sierra_export.jar marmot.localhost

REM export from overdrive
cd c:/web/vufind-plus/vufind/overdrive_api_extract/
java -server -XX:+UseG1GC -jar overdrive_extract.jar marmot.localhost

REM run reindex
cd c:/web/vufind-plus/vufind/reindexer
java -server -XX:+UseG1GC -jar reindexer.jar marmot.localhost

cd c:/web/vufind-plus/sites/marmot.localhost