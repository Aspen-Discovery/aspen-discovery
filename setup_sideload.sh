#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
#  SideLoad Data Directory Set up Script
#-------------------------------------------------------------------------
COLLECTION=$1
LIBRARY=$2

DIR=/data/vufind-plus

if [ $# = 2 ] || [ $# = 3 ];then
  echo ""
  echo "The Side Load Collection is: $COLLECTION"
  echo "The Library is: $LIBRARY"
  if [ $# = 3 ];then
    LOCATION=$3
    echo "The Location is: $LOCATION"
  fi
  echo ""

  #Check that Collection Dir Exists; if not, create
  DIR+="/$COLLECTION"
  if [ ! -d "$DIR" ]; then
    echo "Creating $DIR"
    mkdir "$DIR"
  fi

  #Check that Library Dir Exists, if does exit with error; if not create dir
  DIR+="/$LIBRARY"
  if [ ! -d "$DIR" ]; then
    echo "Creating $DIR"
    mkdir "$DIR"
  fi

  if [ $# = 3 ];then
    DIR+="/$LOCATION"
    echo "Creating $DIR"
    mkdir "$DIR"
    LIBRARY+="\/$LOCATION"
   # escape directory slash for sed replacement
  fi

  echo "The Side Load Data Directory is: $DIR"

	#copy sideload data dir structure to path
	echo "Copying template data directories"
  cp -r /usr/local/vufind-plus/data_dir_setup/sideload_data_dir_template/* $DIR

  #edit the merge configuration file
  echo "Update mergeConfig.ini file"
  sed -i "s/SIDELOADCOLLECTION/$COLLECTION/g" $DIR/mergeConfig.ini
  sed -i "s/LIBRARY/$LIBRARY/g" $DIR/mergeConfig.ini

else
  echo ""
  echo "Usage:  $0 {SideLoadCollection} {Library} {Location (optional)}"
  echo "eg: $0 learning_express evld"
  echo ""
  exit 1
fi
#
#--eof--
