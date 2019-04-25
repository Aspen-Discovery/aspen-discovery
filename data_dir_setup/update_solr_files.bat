@echo off
if "%1"=="" goto usage

cp -r solr7 c:/data/aspen-discovery/%1

cd ../sites/%1
call %1.bat restart
cd ../../data_dir_setup

goto done

:usage
echo You must provide the name of the server to setup

:done