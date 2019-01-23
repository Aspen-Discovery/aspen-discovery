#!/bin/sh
#
# Startup script for the starting selenium RC server abd X frame buffer on the unix network.
#
# VUFIND_HOME
#   Home of the VuFind installation.

usage()
{
    echo "Usage: $0 need to pass the option startup or shutdown"
    exit 1
}


[ $# -gt 0 ] || usage

TMPJ=/tmp/j$$

##################################################
# Get the action 
##################################################

ACTION=$1

##################################################
# Set VUFIND_HOME
##################################################
if [ -z "$VUFIND_HOME" ]
then
  VUFIND_HOME="/usr/local/vufind"
fi

##################################################
# Set SELENIUM HOME
##################################################
if [ -z "$SELE_HOME" ]
then
  SELE_HOME="/usr/local/selenium"
fi

#####################################################
# Find a PID for the pid file
#####################################################
if [  -z "$SELE_PID" ]
then
  SELE_PID="$VUFIND_HOME/tests/sele.pid"
fi

##################################################
# Do the action
##################################################
case "$ACTION" in
  start)
        echo "Starting X Frame Buffer ... "

        if [ -f $SELE_PID ]
        then
            echo "Already Running!!"
            exit 1
        fi

        # Export variables for X Frame Buffer
        export DISPLAY=:99
        nohup sh -c "exec Xvfb :99 -ac >/dev/null 2>&1" &
        echo $! > $SELE_PID
        echo "X Frame Buffer running pid="`cat $SELE_PID`
        
        # Start the Selenium RC server
        cd $SELE_HOME
        nohup sh -c "exec java -jar selenium-server.jar >/dev/null 2>&1" &
        echo $! >> $SELE_PID
        echo "Selenium RC server running pid="`sed -n '2p' $SELE_PID`
        ;;

  stop)
        PID=`sed -n '1p' $SELE_PID 2>/dev/null`
        echo "Shutting down X FRAME ... "
        kill $PID 2>/dev/null
        sleep 2
        kill -9 $PID 2>/dev/null

        PID=`sed -n '2p' $SELE_PID 2>/dev/null`
        echo "Shutting down Selenium RC server ... "
        kill $PID 2>/dev/null
        sleep 2
        kill -9 $PID 2>/dev/null

        rm -f $SELE_PID
        echo "STOPPED `date`"
        ;;
    *)
        usage
        ;;
esac

exit 0
