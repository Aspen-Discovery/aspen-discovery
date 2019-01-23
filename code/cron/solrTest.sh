#! /bin/bash
###########################################################################
# Some code was liberated from:
# /usr/local/vufind-plus/sites/default/solr/bin
#
# Created by JB Wiese
# Put into production: 02/01/2016
#
# 02/05/2016 - Added pause to verify that a restart is needed.
#              Created emailAlert and solrRestart function
###########################################################################

###########################################################################
# FUNCTION: returns the value of the -Djetty.port parameter from a
# running Solr Process
function jetty_port() {
        local  SOLR_PID="$1"
        local  SOLR_PROC=`ps auxww | grep $SOLR_PID | grep start.jar | grep jetty.port`
        IFS=' ' read -a proc_args <<< "$SOLR_PROC"
           for arg in "${proc_args[@]}"
                do
                IFS='=' read -a pair <<< "$arg"
                if [ "${pair[0]}" == "-Djetty.port" ]; then
                        local jetty_port="${pair[1]}"
                break
                fi
           done
           echo "$jetty_port"
} # end jetty_port func


##############################################################################
# FUNCTION: returns the vale of the site name(-Dsolr.solr.home) parameter
# from a running Solr Process
function siteName() {
        local  SOLR_PID="$1"
        local  SOLR_PROC=`ps auxww | grep $SOLR_PID | grep start.jar | grep jetty.port`
        IFS=' ' read -a proc_args <<< "$SOLR_PROC"
          for arg in "${proc_args[@]}"
                do
                IFS='=' read -a pair <<< "$arg"
                if [ "${pair[0]}" == "-Dsolr.solr.home" ]; then
                        if [[ ${pair[1]} == *"/pika/"* ]]; then
                            local siteName="${pair[1]:11}"
                        else
                            local siteName="${pair[1]:18}"
                        fi
                        break
                fi
           done
           echo "${siteName%/*}"
} # end siteName func


##############################################################################
# FUNCTION: restart Apache Solr
function solrRestart(){
        local nameToRestart=$1
        if [ "$nameToRestart" != "" ]; then  #-- must be wrapped in IF statement or errors on no location
                cd /usr/local/vufind-plus/sites/$nameToRestart/; ./$nameToRestart.sh restart
        fi
} # end of solrRestart func

##############################################################################
# FUNCTION: email error message
function emailAlert(){
        local nameEmail=$1
        local emailText=$2
        local emailSubject="**RECOVERY** Apache Solr $nameEmail was restarted."
        local emailAddress="root@marmot.org, mark@marmot.org, pascal@marmot.org"
        mail -s "$emailSubject" "$emailAddress" <<< $emailText

} # end emailAlert func


##############################################################################
# FUNCTION: get information about all Solr nodes running on this host
# using a snapshot of the current processes
function get_info() {
        local getInfoReturn="ok"
        local numSolrs=`ps auxww | grep java | grep start.jar | wc -l | sed -e 's/^[ \t]*//'`
        local arrayIndex="0"
        declare -a arrayOfNames
                if [ "$numSolrs" != "0" ]; then
                for ID in `ps auxww | grep java | grep start.jar | awk '{print $2}' | sort -r`
                do
                        ##-- assign port number to port
                        local port=`jetty_port "$ID"`

                        ##-- assign solr site name to name
                        local name=`siteName "$ID"`

                        ##-- store site names in array to test for one process running
                        arrayOfNames[$arrayIndex]=$name
                        if [ "$port" != "" ]; then

                        ##-- Test if solr has process runinng but the website
                        ##-- does not show a status of OK echos the solr site name

                                local checkWebsiteStatus=$(curl -s -m30 "http://localhost:$port/solr/grouped/admin/ping" | grep -c OK)
                                if [ "$checkWebsiteStatus" != "1" ]; then
                                        ##-- Apache Solr Restart code --##
                                        getInfoReturn="$name"
                                        ##-- Set email message --##
                                        emailBody="Apache Solr website did not return a status=OK and was restarted"
                                        echo "$getInfoReturn:$emailBody"
                                fi
                        fi
                        arrayIndex=$arrayIndex+1
                done
                fi
                ##-- Test if solr has only one process running
                ##-- if only one process is running initiates a restart

                local arrayCompareIndex="0"
                local arrayCompareIndex2="1"
                local counter=0
                while [ "$counter" -lt "$numSolrs" ];
                do
                        if [ "${arrayOfNames[$arrayCompareIndex]}" != "${arrayOfNames[$arrayCompareIndex2]}" ]; then
                                getInfoReturn="${arrayOfNames[$arrayCompareIndex]}"
                                # arrayCompareIndex=$arrayCompareIndex+1  #--remove
                                # arrayCompareIndex2=$arrayCompareIndex2+1  #--remove

                                ##-- Set email message --##
                                emailBody="Apache Solr had only one open port and was restarted"
                                echo "$getInfoReturn:$emailBody"
                        else
                                arrayCompareIndex=$arrayCompareIndex+2
                                arrayCompareIndex2=$arrayCompareIndex2+2
                        fi
                        let counter=$counter+2
                done
echo "$getInfoReturn"
} # end get_info


#########################################################################
# MAIN
#########################################################################

##--Test Apache Solr if it is stalled or one of the two ports is down --##
solrTestResults="ok"
solrTestResults=`get_info`
solrResultsTemp1=""
solrResultsTemp2=""
 if [ "$solrTestResults" != "ok" ]; then
        sleep 10s
        solrTestResults=`get_info`
        if [ "$solrTestResults" != "ok" ]; then
                solrResultsTemp1=$solrTestResults
                solrResultsTemp2=$solrTestResults
                solrSiteNameToRestart=${solrResultsTemp1%%:*}
                emailBodyTrimmed=${solrResultsTemp2##*:}
                echo "$solrSiteNameToRestart and $emailBody"
                solrRestart "$solrSiteNameToRestart"
                emailAlert "$solrSiteNameToRestart" "$emailBodyTrimmed"
        fi

 fi



##----eof----##