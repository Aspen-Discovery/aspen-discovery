@echo off
echo %1
if "%1"=="start" goto start
if "%1"=="stop" goto stop
if "%1"=="restart" goto restart
goto restart

:start
solr start -m 2g -p {solrPort} -s "C:\data\aspen-discovery\{sitename}\solr7" 
goto done

:stop
solr stop -p {solrPort}
goto done

:restart
solr restart -m 2g -p {solrPort} -s "C:\data\aspen-discovery\{sitename}\solr7" 
goto done

:usage
echo Please provide a parameter start or stop to start/stop solr or restart to stop solr and then start it again

:done
