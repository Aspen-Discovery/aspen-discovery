@echo off
if "%1"=="" goto usage

cp -r solr7 c:/data/aspen-discovery/%1

goto done

:usage
echo You must provide the name of the server to setup

:done