@echo off
echo %1
if "%1"=="start" goto start
if "%1"=="stop" goto stop
if "%1"=="restart" goto restart
goto restart

:start
../default/solr-7.6.0/bin/solr start -m 2g -p 8112 -s "C:\data\aspen-discovery\carnegie.localhost\solr7" -d "c:\web\aspen-discovery\sites\default\solr-7.6.0\server"
goto done

:stop
../default/solr-7.6.0/bin/solr stop -p 8112
goto done

:restart
../default/solr-7.6.0/bin/solr restart -m 2g -p 8112 -s "C:\data\aspen-discovery\carnegie.localhost\solr7" -d "c:\web\aspen-discovery\sites\default\solr-7.6.0\server"
goto done

:usage
echo Please provide a parameter start or stop to start/stop solr or restart to stop solr and then start it again

:done
