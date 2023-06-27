@echo off
rem Setup data and logs for a new aspen-discovery server by copying the appropriate files from default.
set HOST=%1
shift
if not "!%HOST%!"=="!!" goto serverset
goto usage

:serverset
set WD=%CD%
echo Working directory is %WD%
echo Server name is %HOST%

echo setting up data directory
cd c:\data
mkdir aspen-discovery
cd c:\data\aspen-discovery
echo creating accelerated reader data folder
mkdir accelerated_reader
mkdir %HOST%
cd %HOST%
copy %WD%/data_dir_setup/* .

echo setting up logs directory
cd c:\var\log
mkdir aspen-discovery
cd c:\var\log\aspen-discovery
mkdir %HOST%
cd %HOST%

echo Installing Solr Files for HOST
cd %WD%/data_dir_setup/
update_solr_files.bat %HOST%

echo In lieu of "Creating symbolic link in /etc/httpd/conf.d to apache config file"
echo Windows apache config file (httpd.conf) needs an Include pointing to %HOST% file, e.g.,
echo Include "c:/web/aspen-discovery/sites/nashville.aspenlocal/httpd-nashville.aspenlocal.conf"

goto end

:usage
echo Usage: setup_server {host}
echo.
goto end

:done