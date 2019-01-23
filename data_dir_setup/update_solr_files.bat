@echo off
if "%1"=="" goto usage

rm -f c:\data\vufind-plus\%1\solr_master\lib\*
cp -r solr_master c:/data/vufind-plus/%1
rm -f c:\data\vufind-plus\%1\solr_searcher\lib\*
cp -r solr_searcher c:/data/vufind-plus/%1

goto done

:usage
echo You must provide the name of the server to setup

:done