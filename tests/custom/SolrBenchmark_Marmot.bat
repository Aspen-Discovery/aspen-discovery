rem benchmark Solr Indexing so we can test different solr config settings
@echo off

rem start (or restart) solr
cd c:\web\vufind-plus\sites\marmot.localhost\
@call marmot.localhost.bat restart

rem get the start time
echo starting index at %TIME%

rem run an index
cd c:\web\vufind-plus\vufind\reindexer
java -jar reindexer.jar marmot.localhost fullReindex

echo index finished at %TIME%

rem restart again just to clean up memory
cd c:\web\vufind-plus\sites\marmot.localhost\
@call marmot.localhost.bat restart

rem do some tests within solr
c:\apache-jmeter-2.13\bin\jmeter.bat -n -t "C:\web\VuFind-Plus\tests\jmeter\solr_test.jmx" -l "C:\web\VuFind-Plus\tests\jmeter\results\marmot_localhost\automated_results.jtl"

echo tests finished at %TIME%
